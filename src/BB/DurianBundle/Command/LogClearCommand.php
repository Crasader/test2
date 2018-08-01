<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\DBAL\Connection;
use BB\DurianBundle\Entity\CreditPeriod;
use BB\DurianBundle\Entity\RemovedBlacklist;
use BB\DurianBundle\Entity\BlacklistOperationLog;

/**
 * 清除Durian log
 */
class LogClearCommand extends ContainerAwareCommand
{
    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * 每次下語法刪掉的筆數
     *
     * @var integer
     */
    private $numOfDelData = 1000;

    /**
     * 若設為true, 則會分批刪除資料
     *
     * @var bool
     */
    private $slowly = false;

    /**
     * 目前正在執行的資料表名稱
     */
    private $table;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:log:clear')
            ->setDescription('執行例行清durian log語法')
            ->addOption('slow', null, InputOption::VALUE_NONE, '分批刪除資料, 以防卡語法')
            ->setHelp(<<<EOT
$ app/console durian:log:clear
以時間為條件刪除資料
credit_entry, log_operation, card_entry, user_remit_discount, login_log, email_verify_code,
deposit_pay_status_error, credit_period, withdraw_error

以時間和主鍵為條件刪除資料(因時間欄位無Index)
user_created_per_ip, login_error_per_ip

以時間(14天前)、check為條件刪除資料
cash_trans, cash_fake_trans

以時間(180~181天間)、confirm為條件刪除資料
cash_deposit_entry, card_deposit_entry

以最大ID為條件刪除資料
deposit_sequence

以時間(180天前)、被取消的刪除計畫為條件刪除資料
rm_plan, rm_plan_user, rm_plan_user_extra_balance, rm_plan_level

尚無歸類的資料表
exchange, blacklist, account_log, remit_order, login_log_mobile
EOT
            );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input  = $input;
        $this->output = $output;

        if ($this->input->getOption('slow')) {
            $this->slowly = true;
        }

        $allStartTime = microtime(true);
        $this->output->write("LogClearCommand Start.", true);

        $clearCreditDay = CreditPeriod::CLEAR_LOG_DAYS . ' days ago';

        //以時間為條件刪除資料
        $this->delete('credit_entry', 'period_at', $clearCreditDay);
        $this->delete('log_operation', 'at', '14 days ago');
        $this->delete('card_entry', 'created_at', '1 year ago');
        $this->delete('user_remit_discount', 'period_at', '1 month ago');

        $this->deleteLoginLogMobile('60 days ago');
        $this->delete('login_log', 'at', '60 days ago');
        $this->delete('email_verify_code', 'expire_at', 'now');
        $this->delete('deposit_pay_status_error', 'confirm_at', '1 month ago');
        $this->delete('credit_period', 'at', $clearCreditDay);
        $this->delete('withdraw_error', 'at', '3 month ago');

        //以時間和主鍵為條件刪除資料(因時間欄位無Index)
        $this->deleteByPrimary('user_created_per_ip', 'at', '3 month ago');
        $this->deleteByPrimary('login_error_per_ip', 'at', '3 month ago');

        //以時間(14天前)、check為條件刪除資料
        $this->deleteTransaction('cash_trans');
        $this->deleteTransaction('cash_fake_trans');

        //以時間(180~181天間)、confirm為條件刪除資料
        $this->deleteDepositEntry('cash_deposit_entry');
        $this->deleteDepositEntry('card_deposit_entry');

        //以最大ID為條件刪除資料
        $this->deleteSequence('deposit_sequence');

        //以時間(180天前)、完成或被取消的刪除計畫為條件，清除刪除計畫，清除上限為 200 萬個使用者與該使用者相關資料
        $this->deleteRmPlan();

        $this->deleteExchange();
        $this->deleteBlacklist();
        $this->deleteAccountLog();
        $this->deleteRemitOrder();

        $this->output->write("\nAll Execute time: " . $this->getExecuteTime($allStartTime), true);
        $this->output->write('Memory MAX use: ' . $this->getMemoryUseage() . ' M', true);
    }

    /**
     * 刪除時間範圍的資料
     *
     * @param string $table      資料表名稱
     * @param string $timeField  資料表時間欄位
     * @param string $deleteTime 指定的刪除區間
     */
    private function delete($table, $timeField, $deleteTime)
    {
        $startTime = $this->start($table);

        $deleteTime = new \DateTime($deleteTime);
        $dateStr = $deleteTime->format('Y-m-d H:i:s');

        $sql = "SELECT COUNT(*) FROM `$table` WHERE $timeField < ?";
        $params = [$dateStr];
        $this->printSql($sql, $params);

        $count = $this->getConnection()->fetchColumn($sql, $params);

        if ($count == 0) {
            $this->printNoData();

            return;
        }

        if ($this->slowly) {
            $sql = "DELETE FROM `$table` " .
                   "WHERE $timeField < ? " .
                   "LIMIT {$this->numOfDelData}";
            $this->deleteSlowly($sql, $params, $count);
        } else {
            $sql = "DELETE FROM `$table` " .
                   "WHERE $timeField < ?";
            $this->deleteDirectly($sql, $params);
        }

        $this->printNumOfData($count);
        $this->showExecuteTime($startTime);
    }

    /**
     * 刪除時間範圍的資料，並以主鍵作為刪除資料的index
     *
     * @param string $table      資料表名稱
     * @param string $timeField  資料表時間欄位
     * @param string $deleteTime 指定的刪除區間
     */
    private function deleteByPrimary($table, $timeField, $deleteTime)
    {
        $startTime = $this->start($table);

        $deleteTime = new \DateTime($deleteTime);
        $dateStr = $deleteTime->format('YmdHis');

        $sql = "SELECT COUNT(id), MAX(id) FROM `$table` WHERE $timeField < ?";
        $params = [$dateStr];
        $this->printSql($sql, $params);

        $ret = $this->getConnection()->fetchArray($sql, $params);
        $count = $ret[0];
        $max = $ret[1];

        if ($count == 0) {
            $this->printNoData();

            return;
        }

        $params = [$max];
        if ($this->slowly) {
            $sql = "DELETE FROM `$table` " .
                   'WHERE id <= ? ' .
                   "LIMIT {$this->numOfDelData}";
            $this->deleteSlowly($sql, $params, $count);
        } else {
            $sql = "DELETE FROM `$table` " .
                   'WHERE id <= ?';
            $this->deleteDirectly($sql, $params);
        }

        $this->printNumOfData($count);
        $this->showExecuteTime($startTime);
    }

    /**
     * 刪除14天前的交易資料
     *
     * @param string $table 資料表名稱
     * @param string $type  createdAt 的欄位類型，目前僅支援 date/integer
     */
    private function deleteTransaction($table, $type = 'date')
    {
        $startTime = $this->start($table);

        if ($type == 'date') {
            $sql = "SELECT COUNT(id), MAX(id) FROM `{$table}` " .
                   'WHERE created_at < SUBDATE(NOW(),INTERVAL 14 DAY) ' .
                   'AND checked != 0';
        }

        if ($type == 'integer') {
            $sql = "SELECT COUNT(id), MAX(id) FROM `{$table}` " .
                   "WHERE created_at < DATE_FORMAT(SUBDATE(NOW(), INTERVAL 14 DAY), '%Y%m%d%H%i%s') " .
                   'AND checked != 0';
        }

        $this->printSql($sql);
        $ret = $this->getConnection()->fetchArray($sql);
        $count = $ret[0];
        $max = $ret[1];

        if ($count == 0) {
            $this->printNoData();

            return;
        }

        $params = [$max];

        if ($this->slowly) {
            $sql = "DELETE FROM `{$table}` " .
                   'WHERE id <= ? ' .
                   'AND checked != 0 ' .
                   'ORDER BY id ' .
                   "LIMIT {$this->numOfDelData}";
            $this->deleteSlowly($sql, $params, $count);
        } else {
            $sql = "DELETE FROM `{$table}` " .
                   'WHERE id <= ? ' .
                   'AND checked != 0';
            $this->deleteDirectly($sql, $params);
        }

        $this->printNumOfData($count);
        $this->showExecuteTime($startTime);
    }

    /**
     * 清掉180~181天前未確認的入款明細資料
     *
     * @param $table 資料表名稱
     */
    private function deleteDepositEntry($table)
    {
        $startTime = $this->start($table);
        $conn = $this->getConnection();

        $defaultStart = new \Datetime('now');
        $defaultStart->sub(new \DateInterval('P181D'));
        $beginAt = $defaultStart->format('Ymd000000');

        $defaultEnd = new \Datetime('now');
        $defaultEnd->sub(new \DateInterval('P180D'));
        $endAt = $defaultEnd->format('Ymd000000');

        $sql = 'SELECT COUNT(*) ';
        $sql .= "FROM `$table` ";
        $sql .= 'WHERE at <= ? ';
        $sql .= 'AND at >= ? ';
        $sql .= 'AND confirm = 0';

        $params = [$endAt, $beginAt];
        $this->printSql($sql, $params);
        $ret = $conn->fetchArray($sql, $params);
        $count = $ret[0];

        if ($count == 0) {
            $this->printNoData();

            return;
        }

        if ($this->slowly) {
            $sql = "DELETE FROM `$table` ";
            $sql .= 'WHERE at <= ? ';
            $sql .= 'AND at >= ? ';
            $sql .= 'AND confirm = 0 ';
            $sql .= "LIMIT {$this->numOfDelData}";

            $this->deleteSlowly($sql, $params, $count);
        } else {
            $sql = "DELETE FROM `$table` ";
            $sql .= 'WHERE at <= ? ';
            $sql .= 'AND at >= ? ';
            $sql .= 'AND confirm = 0';

            $this->deleteDirectly($sql, $params);
        }

        $this->printNumOfData($count);
        $this->showExecuteTime($startTime);
    }

    /**
     * 批次清除多餘的Sequence
     *
     * @param $table 資料表名稱
     */
    private function deleteSequence($table)
    {
        $startTime = $this->start($table);

        $sql = "SELECT MAX(id), COUNT(id) FROM `$table`";
        $this->printSql($sql);
        $ret = $this->getConnection()->fetchArray($sql);
        $max = $ret[0];
        $count = $ret[1];

        if ($count <= 1) {
            $this->printNoData();

            return;
        }

        $count--;

        $sql = "DELETE FROM `$table` " .
               'WHERE id < ? ' .
               "LIMIT {$this->numOfDelData}";
        $params = [$max];

        $this->deleteSlowly($sql, $params, $count);
        $this->printNumOfData($count);
        $this->showExecuteTime($startTime);
    }

    /**
     * 刪除180天前完成或被取消的刪除計畫，清除上限為 200 萬個使用者與該使用者相關資料
     */
    private function deleteRmPlan()
    {
        //開始清除刪除計畫
        $startTime = $this->start('rm_plan');
        $conn = $this->getConnection();
        $deleteLimit = 2000000;
        $isFinish = false;

        //關閉套件的語法記錄功能
        $conn->getConfiguration()->setSQLLogger(null);

        /**
         * 跳出條件：
         * 1. 沒有符合條件的計畫
         * 2. 刪除使用者數量達到清除上限
         */
        while ($deleteLimit > 0) {
            /**
             * 取得符合條件的 刪除計畫
             * 180 天前 被取消 或 已完成 的刪除計畫
             * 時間欄位的優先順序為 finish_at > modified_at > created_at
             *
             * 時間條件判斷需區分：
             * 1. 以finish_at為條件: 計畫執行完成
             * 2. 以modified_at為條件：計畫同步使用者後，沒有待刪除使用者 或 計畫被取消
             * 3. 以created_at為條件：計畫產生時，沒有待刪除使用者
             */
            $sql = 'SELECT id FROM rm_plan ' .
                'WHERE ( ' .
                    '(finish_at < SUBDATE(NOW(),INTERVAL 180 DAY)) ' .
                    'OR (finish_at IS NULL AND modified_at < SUBDATE(NOW(),INTERVAL 180 DAY)) ' .
                    'OR (finish_at IS NULL AND modified_at IS NULL AND created_at < SUBDATE(NOW(),INTERVAL 180 DAY)) ' .
                ' ) ' .
                'AND (cancel = 1 OR finished = 1) ' .
                'LIMIT 1';
            $this->printSql($sql);

            $planId = $conn->fetchColumn($sql);

            if (!$planId) {
                $this->printNoData('rm_plan');

                break;
            }

            $planIdArray = [$planId];

            //取得刪除計畫下，使用者的最小ID、最大ID、使用者數量
            $sql = 'SELECT MIN(id) AS min, MAX(id) AS max, COUNT(id) AS count ' .
                'FROM rm_plan_user WHERE plan_id = ?';
            $this->printSql($sql, $planIdArray);

            $userField = $conn->fetchAssoc($sql, $planIdArray);

            $minUserId = $userField['min'];
            $maxUserId = $userField['max'];
            $userCount = $userField['count'];

            if ($userCount == 0) {
                $startTime = $this->start('rm_plan_user_extra_balance');
                $this->printNoData();
                $startTime = $this->start('rm_plan_user');
                $this->printNoData();
            } else {
                //刪除數量超出上限，限制刪除使用者範圍，並調整完成參數
                if ($deleteLimit < $userCount) {
                    $isFinish = true;
                    $userCount = $deleteLimit;
                    $offset = $userCount - 1;

                    //取得刪除筆數限制內的最大id
                    $sql = 'SELECT id FROM rm_plan_user WHERE plan_id = ? ' .
                        "ORDER BY id ASC LIMIT 1 OFFSET $offset";
                    $this->printSql($sql, $planIdArray);

                    $maxUserId = $conn->fetchColumn($sql, $planIdArray);
                }

                $params = [$planId, $minUserId, $maxUserId];

                //開始清除刪除計畫的使用者外接額度
                $startTime = $this->start('rm_plan_user_extra_balance');

                while (true) {
                    $sql = 'SELECT rb.id ' .
                        'FROM rm_plan_user_extra_balance AS rb ' .
                        'INNER JOIN rm_plan_user AS ru ON rb.id = ru.id ' .
                        'WHERE ru.plan_id = ? AND ru.id >= ? AND ru.id <= ? ' .
                        'ORDER BY rb.id ASC ' .
                        "LIMIT {$this->numOfDelData}";
                    $this->printSql($sql, $params);
                    $balanceArray = $conn->fetchAll($sql, $params);

                    if (!$balanceArray) {
                        $this->printNoData();

                        break;
                    }

                    $balanceIds = array_column($balanceArray, 'id');
                    $minBalanceId = min($balanceIds);
                    $maxBalanceId = max($balanceIds);
                    $balanceParams = [$planId, $minBalanceId, $maxBalanceId];

                    $sql = 'DELETE rb ' .
                        'FROM rm_plan_user_extra_balance AS rb ' .
                        'INNER JOIN rm_plan_user AS ru ON rb.id = ru.id ' .
                        'WHERE ru.plan_id = ? AND rb.id >= ? AND rb.id <= ?';
                    $this->deleteDirectly($sql, $balanceParams);
                    $conn->connect('slave');

                    if ($this->getContainer()->getParameter('kernel.environment') != 'test') {
                        usleep(300000);
                    }
                }

                $this->showExecuteTime($startTime);

                //開始清除刪除計畫的使用者
                $startTime = $this->start('rm_plan_user');

                $sql = "DELETE FROM rm_plan_user WHERE plan_id = ? AND id >= ? AND id <= ? LIMIT {$this->numOfDelData}";
                $this->deleteSlowly($sql, $params, $userCount);
                $this->printNumOfData($userCount);
                $this->showExecuteTime($startTime);

                $deleteLimit -= $userCount;
            }

            if ($isFinish) {
                break;
            }

            //開始清除刪除計畫的層級
            $startTime = $this->start('rm_plan_level');

            $sql = 'SELECT COUNT(id) FROM rm_plan_level WHERE plan_id = ?';
            $this->printSql($sql, $planIdArray);
            $levelCount = $conn->fetchColumn($sql, $planIdArray);

            if ($levelCount == 0) {
                $this->printNoData();
            } else {
                $sql = "DELETE FROM rm_plan_level WHERE plan_id = ? LIMIT {$this->numOfDelData}";
                $this->deleteSlowly($sql, $planIdArray, $levelCount);
                $this->showExecuteTime($startTime);
            }

            //開始清除刪除計畫
            $startTime = $this->start('rm_plan');

            $sql = 'DELETE FROM rm_plan WHERE id = ?';
            $this->deleteDirectly($sql, $planIdArray);
            $this->showExecuteTime($startTime);

            //因測試碼僅驗證語法，所以完整跑過一輪後，不用繼續執行
            if ($this->getContainer()->getParameter('kernel.environment') == 'test') {
                break;
            }
        }
    }

    /**
     * 清掉1年前匯率資料
     */
    private function deleteExchange()
    {
        $startTime = $this->start('exchange');

        $em = $this->getEntityManager();
        $conn = $em->getConnection();
        $repo = $em->getRepository('BBDurianBundle:Exchange');

        $allCurrency = $this->getContainer()->get('durian.currency')->getAvailable();
        $now = new \DateTime('now');

        $currents = [];
        foreach (array_keys($allCurrency) as $num) {
            $exchange = $repo->findByCurrencyAt($num, $now);

            if ($exchange) {
                $currents[] = $exchange->getId();
            }
        }
        $params = [$currents];
        $types = [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY];
        $sql = 'SELECT COUNT(*) ' .
               'FROM `exchange` ' .
               'WHERE active_at <= SUBDATE(NOW(), INTERVAL 1 YEAR) ' .
               'AND id NOT IN (?)';
        $this->printSql($sql, $params);
        $count = $conn->executeQuery($sql, $params, $types)->fetchColumn();

        if ($count == 0) {
            $this->printNoData();

            return;
        }

        $sql = 'SELECT id ' .
               'FROM `exchange` ' .
               'WHERE active_at <= SUBDATE(NOW(), INTERVAL 1 YEAR) ' .
               'AND id NOT IN (?)';
        $exchangeBye = $conn->fetchAll($sql, $params, $types);

        $deleteIds = [];
        foreach ($exchangeBye as $exchange) {
            $deleteIds[] = $exchange['id'];
        }

        $params = [$deleteIds];
        if ($this->slowly) {
            $sql = 'DELETE FROM `exchange` ' .
                   'WHERE id IN (?) ' .
                   "LIMIT {$this->numOfDelData}";
            $this->deleteSlowly($sql, $params, $count, $types);
        } else {
            $sql = 'DELETE FROM `exchange` WHERE id IN (?)';
            $this->deleteDirectly($sql, $params, $types);
        }

        $this->printNumOfData($count);
        $this->showExecuteTime($startTime);
    }

    /**
     * 解封過期黑名單及清除解封黑名單資料
     */
    private function deleteBlacklist()
    {
        $at = (new \DateTime('-2 months'))->format('Y-m-d H:i:s');

        $this->unblockBlacklist($at);
        $this->clearRemovedBlacklist($at);
        $this->clearIpBlacklist($at);
    }

    /**
     * 解除過期的系統封鎖黑名單
     *
     * @string $at 時間
     * @return integer
     */
    private function unblockBlacklist($at)
    {
        $startTime = $this->start('blacklist');
        $emShare = $this->getEntityManager();
        $repo = $emShare->getRepository('BBDurianBundle:Blacklist');

        $limit = [
            'first_result' => 0,
            'max_results' => ($this->numOfDelData/2)
        ];
        $count = 0;

        while (1) {
            $blacklists = $repo->getOverdueBlacklist($at, $limit);

            if (!$blacklists) {
                break;
            }

            $emShare->beginTransaction();
            try {
                foreach ($blacklists as $blacklist) {
                    $rmBlacklist = new RemovedBlacklist($blacklist);
                    $emShare->persist($rmBlacklist);

                    $blacklistLog = new BlacklistOperationLog($blacklist->getId());
                    $blacklistLog->setRemovedOperator('system');
                    $blacklistLog->setNote('2個月期限已到系統自動解除封鎖');
                    $emShare->persist($blacklistLog);

                    $msg = sprintf('系統自動解除過期黑名單，ip:%s', $blacklist->getIp());
                    $this->output->write($msg, true);

                    $emShare->remove($blacklist);
                    $count++;
                }

                $emShare->flush();
                $emShare->commit();

                // 再跑測試的時候，就不sleep了，避免測試碼執行時間過長
                if ($this->getContainer()->getParameter('kernel.environment') != 'test') {
                    usleep(300000);
                }
            } catch (\Exception $e) {
                $emShare->rollback();

                $this->output->write("解除封鎖黑名單失敗:".$e->getMessage(), true);

                return;
            }
        }

        if ($count == 0) {
            $this->printNoData();
        }

        $this->showExecuteTime($startTime);

        return $count;
    }

    /**
     * 清除解除封鎖後的黑名單資料
     *
     * @string $at 時間
     * @return integer
     */
    private function clearRemovedBlacklist($at)
    {
        $startTime = $this->start('removed_blacklist');
        $conn = $this->getConnection();
        $limit = ($this->numOfDelData/2);
        $count = 0;

        while (1) {
            $sql = 'SELECT blacklist_id AS id FROM removed_blacklist ' .
                'WHERE modified_at <= ? ORDER BY blacklist_id ASC LIMIT ?';

            $param = [
                $at,
                $limit,
            ];

            $types = [
                \PDO::PARAM_STR,
                \PDO::PARAM_INT
            ];

            $ids = $conn->fetchAll($sql, $param, $types);
            $count += count($ids);

            if (!$ids) {
                break;
            }

            $ids = array_column($ids, 'id');
            $sql = 'DELETE FROM blacklist_operation_log WHERE blacklist_id IN (?)';
            $types = [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY];
            $this->deleteSlowly($sql, [$ids], count($ids), $types);

            $sql = 'DELETE FROM removed_blacklist WHERE blacklist_id IN (?)';
            $this->deleteSlowly($sql, [$ids], count($ids), $types);
        }

        if ($count == 0) {
            $this->printNoData();
        }

        $this->showExecuteTime($startTime);

        return $count;
    }

    /**
     * 清除過期Ip封鎖列表
     *
     * @string $at 時間
     * @return integer
     */
    private function clearIpBlacklist($at)
    {
        $startTime = $this->start('ip_blacklist');
        $params = [$at];
        $types = [\PDO::PARAM_STR];

        $conn = $this->getConnection();

        $sql = 'SELECT COUNT(id) FROM ip_blacklist WHERE created_at <= ?';
        $this->printSql($sql, $params);

        $count = $conn->fetchColumn($sql, $params, 0, $types);

        if ($count == 0) {
            $this->printNoData();

            return;
        }

        $sql = "DELETE FROM ip_blacklist WHERE created_at <= ? LIMIT {$this->numOfDelData}";
        $this->deleteSlowly($sql, $params, $count, $types);
        $this->printNumOfData($count);
        $this->showExecuteTime($startTime);
    }

    /**
     * 分批清掉30天前AccountLog資料
     */
    private function deleteAccountLog()
    {
        $startTime = $this->start('account_log');

        $sql = 'SELECT COUNT(id), MAX(id) ' .
            'FROM `account_log` ' .
            'WHERE update_at < SUBDATE(NOW(),INTERVAL 30 DAY) AND status = 1';
        $this->printSql($sql);
        $ret = $this->getConnection()->fetchArray($sql);
        $count = $ret[0];
        $max = $ret[1];

        if ($count == 0) {
            $this->printNoData();

            return;
        }

        $params = [$max];

        if ($this->slowly) {
            $sql = 'DELETE FROM `account_log` ' .
                   'WHERE id <= ? ' .
                   'AND status = 1 ' .
                   'ORDER BY id ' .
                   "LIMIT {$this->numOfDelData}";
            $this->deleteSlowly($sql, $params, $count);
        } else {
            $sql = 'DELETE FROM `account_log` ' .
                   'WHERE id <= ? ' .
                   'AND status = 1';
            $this->deleteDirectly($sql, $params);
        }

        $this->printNumOfData($count);
        $this->showExecuteTime($startTime);
    }

    /**
     * 分批清掉一個月前的公司入款訂單號資料
     */
    private function deleteRemitOrder()
    {
        $startTime = $this->start('remit_order');
        $conn = $this->getConnection();

        $datetime = new \DateTime('-1 month');
        $dateString = $datetime->format('Ymd');
        $dateString .= '00000000';

        $sqlCount = 'SELECT COUNT(*) FROM `remit_order` WHERE order_number <= ?';

        $params = [$dateString];
        $this->printSql($sqlCount, $params);
        $count = $conn->fetchColumn($sqlCount, $params);

        if ($count == 0) {
            $this->printNoData();

            return;
        }

        $sqlDelete = "DELETE FROM `remit_order` WHERE order_number <= ? LIMIT {$this->numOfDelData}";
        $this->deleteSlowly($sqlDelete, $params, $count);
        $this->printNumOfData($count);
        $this->showExecuteTime($startTime);
    }

    /**
     * 分批清除時間範圍對應的登入記錄行動裝置資訊
     *
     * @param string $deleteTime 指定的刪除區間
     */
    private function deleteLoginLogMobile($deleteTime)
    {
        $startTime = $this->start('login_log_mobile');

        // 配合login_log的刪除排程，以午夜整點為分界
        $endDateTime = new \DateTime($deleteTime);
        $endDateStr = $endDateTime->format('Y-m-d 00:00:00');
        $startDateStr = $endDateTime->modify('1 day ago')->format('Y-m-d 00:00:00');

        $conn = $this->getConnection();
        $sql = 'SELECT MIN(`id`), MAX(`id`) FROM `login_log` WHERE `at` >= ? AND `at` < ?';
        $params = [$startDateStr, $endDateStr];
        $this->printSql($sql, $params);
        $params = $conn->fetchArray($sql, $params);

        $sql = 'SELECT COUNT(1) FROM `login_log_mobile` WHERE `login_log_id` >= ? AND `login_log_id` < ?';
        $this->printSql($sql, $params);
        $count = $this->getConnection()->fetchColumn($sql, $params);

        if ($count == 0) {
            $this->printNoData();

            return;
        }

        if ($this->slowly) {
            $sql = 'DELETE FROM `login_log_mobile` WHERE `login_log_id` >= ? AND `login_log_id` < ?'
                . " LIMIT {$this->numOfDelData}";
            $this->deleteSlowly($sql, $params, $count);
        } else {
            $sql = 'DELETE FROM `login_log_mobile` WHERE `login_log_id` >= ? AND `login_log_id` < ?';
            $this->deleteDirectly($sql, $params);
        }

        $this->printNumOfData($count);
        $this->showExecuteTime($startTime);
    }

    /**
     * 一次刪除log, 不分批刪除
     *
     * @param string $sql    要執行的刪除語法
     * @param array  $params 參數
     * @param array  $types  參數的型態
     */
    private function deleteDirectly($sql, $params, $types = [])
    {
        $this->printSql($sql, $params);
        $this->getConnection()->executeUpdate($sql, $params, $types);
    }

    /**
     * 顯示執行時間
     *
     * @param integer $startTime 開始時間
     */
    private function showExecuteTime($startTime)
    {
        $this->output->write('Done.', true);
        $this->output->write("Execute time:" . $this->getExecuteTime($startTime), true);
    }

    /**
     * 執行分批刪除的語法
     *
     * @param string  $sql    要執行的刪除語法
     * @param array   $params 參數
     * @param integer $count  要刪除的數量
     * @param array   $types  參數的型態
     */
    private function deleteSlowly($sql, $params, $count, $types = [])
    {
        $printSql = $this->composeSql($sql, $params);

        while ($count > 0) {
            $this->printSql($printSql);

            $this->getConnection()->executeUpdate($sql, $params, $types);
            $count -= $this->numOfDelData;

            // 再跑測試的時候，就不sleep了，避免測試碼執行時間過長
            if ($this->getContainer()->getParameter('kernel.environment') != 'test') {
                usleep(300000);
            }
        }
    }

    /**
     * 回傳執行時間string
     *
     * @param float $startTime 開始時間
     * @return string
     */
    private function getExecuteTime($startTime)
    {
        $executeTime = round(microtime(true) - $startTime, 1);
        $timeString = $executeTime . ' sec.';

        if ($executeTime > 60) {
            $executeTime = round($executeTime / 60, 0);
            $timeString = $executeTime . ' mins.';
        }

        return $timeString;
    }

    /**
     * 回傳記憶體用量
     *
     * @return float
     */
    private function getMemoryUseage()
    {
        $memory = memory_get_peak_usage() / 1024 / 1024;

        return number_format($memory, 2);
    }

    /**
     * 印sql語句
     *
     * @param string $sql    要印出的SQL語法
     * @param array  $params SQL參數
     */
    private function printSql($sql, $params = [])
    {
        if ($params) {
            $sql = $this->composeSql($sql, $params);
        }

        $this->output->write("[sql] $sql", true);
    }

    /**
     * 組合SQL語句
     *
     * @param string $sql    要組合的SQL語法
     * @param array  $params SQL參數
     * @return string
     */
    private function composeSql($sql, $params = [])
    {
        foreach ($params as $param) {
            $paramString = $param;
            if (is_array($param)) {
                $paramString = implode("', '", $param);
            }

            $paramString = "'" . $paramString . "'";
            $sql = preg_replace('/\?/u', $paramString, $sql, 1);
        }

        return $sql;
    }

    /**
     * 顯示刪除筆數
     *
     * @param integer $count 資料筆數
     */
    private function printNumOfData($count)
    {
        $this->output->write("$count data to be deleted.", true);
    }

    /**
     * 顯示沒有資料需要被刪除
     *
     * @param string $table 資料表名稱
     */
    private function printNoData($table = '')
    {
        if (!$table) {
            $table = $this->table;
        }

        $this->output->write("No $table data deleted.", true);
    }

    /**
     * 回傳 EntityManager 物件
     *
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager()
    {
        $shareMap = [
            'rm_plan',
            'rm_plan_level',
            'rm_plan_user',
            'rm_plan_user_extra_balance',
            'blacklist',
            'removed_blacklist',
            'ip_blacklist',
            'exchange',
            'log_operation',
            'login_error_per_ip',
            'user_created_per_ip'
        ];

        $name = 'default';
        if (in_array($this->table, $shareMap)) {
            $name = 'share';
        }

        return $this->getContainer()->get("doctrine.orm.{$name}_entity_manager");
    }

    /**
     * 印刪除資料表的訊息，並設定目前正刪除的資料表
     *
     * @param string $table 資料表名稱
     * @return float
     */
    private function start($table)
    {
        $this->output->write("\n* Deleting $table ...", true);
        $this->table = $table;

        return microtime(true);
    }

    /**
     * 回傳 DB 連線
     *
     * @return \Doctrine\DBAL\Connection
     */
    private function getConnection()
    {
        return $this->getEntityManager()->getConnection();
    }
}
