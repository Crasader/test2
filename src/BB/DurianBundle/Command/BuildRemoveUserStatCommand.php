<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 統計已刪除會員出入款次數及金額
 */
class BuildRemoveUserStatCommand extends ContainerAwareCommand
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * 目前的DB連線設定
     *
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:build-remove-user-stat')
            ->setDescription('統計已刪除會員出入款次數及金額')
            ->setHelp(<<<EOT
統計已刪除會員出入款次數及金額
app/console durian:build-remove-user-stat
EOT
             );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $startTime = microtime(true);
        $this->output = $output;

        // init
        $this->conn = $this->getContainer()->get('doctrine.dbal.default_connection');
        $connShare = $this->getContainer()->get('doctrine.dbal.share_connection');

        $userIdCriteria = 0;
        $sourceSql = 'SELECT user_id FROM removed_user WHERE user_id > ? AND role = 1 ORDER BY user_id LIMIT 1000';
        $userStatSql = 'SELECT user_id FROM user_stat WHERE user_id IN (?)';
        $exchangeSql = 'SELECT basic FROM exchange WHERE currency = ? AND active_at <= ? ORDER BY active_at DESC LIMIT 1';
        $exchangeNotExistSql = 'SELECT basic FROM exchange WHERE currency = ? AND active_at > ? ORDER BY active_at ASC LIMIT 1';

        $cdeSql = 'SELECT user_id, SUM(amount_conv_basic) AS deposit_total, count(id) AS deposit_count, ';
        $cdeSql .= 'MAX(amount_conv_basic) AS deposit_max ';
        $cdeSql .= 'FROM cash_deposit_entry WHERE user_id IN (?) AND confirm = 1 AND payway = 1 GROUP BY user_id';

        $pdweSql = 'SELECT id, user_id, currency, at, amount ';
        $pdweSql .= 'FROM payment_deposit_withdraw_entry FORCE INDEX (idx_payment_deposit_withdraw_entry_user_id_at) ';
        $pdweSql .= 'WHERE id > ? AND user_id IN (?) AND opcode = 1010 ORDER BY id LIMIT 1000';

        $reSql = 'SELECT user_id, SUM(amount * rate) AS remit_total, count(id) AS remit_count, ';
        $reSql .= 'MAX(amount * rate) AS remit_max ';
        $reSql .= 'FROM remit_entry WHERE user_id IN (?) AND status = 1 GROUP BY user_id';

        $cweSql = 'SELECT user_id, SUM(real_amount * rate * -1) AS withdraw_total, count(id) AS withdraw_count, ';
        $cweSql .= 'MAX(real_amount * rate * -1) AS withdraw_max ';
        $cweSql .= 'FROM cash_withdraw_entry WHERE user_id IN (?) AND status = 1 GROUP BY user_id';

        $type = [
            \PDO::PARAM_INT,
            \Doctrine\DBAL\Connection::PARAM_INT_ARRAY
        ];
        $types = [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY];
        $count = 0;

        while ($result = $connShare->fetchAll($sourceSql, [$userIdCriteria])) {
            $sourceUsers = [];

            // 整理已刪除會員資料
            foreach ($result as $user) {
                $userIdCriteria = $user['user_id'];
                $sourceUsers[$userIdCriteria] = [
                    'deposit_total' => 0,
                    'deposit_count' => 0,
                    'deposit_max' => 0,
                    'manual_total' => 0,
                    'manual_count' => 0,
                    'manual_max' => 0,
                    'remit_total' => 0,
                    'remit_count' => 0,
                    'remit_max' => 0,
                    'withdraw_total' => 0,
                    'withdraw_count' => 0,
                    'withdraw_max' => 0,
                ];
            }
            $users = array_keys($sourceUsers);

            // 檢查會員統計資料是否存在 避免此會員已被統計過
            $userStats = $this->conn->fetchAll($userStatSql, [$users], $types);

            $userIds = [];
            foreach ($userStats as $userStat) {
                $userIds[] = $userStat['user_id'];
            }

            // 檢查userStat是否存在
            $userDiff = array_diff($users, $userIds);

            // 統計線上入款紀錄
            $cdes = $this->conn->fetchAll($cdeSql, [$userDiff], $types);

            // 整理統計資料
            foreach ($cdes as $cde) {
                $userId = $cde['user_id'];
                $sourceUsers[$userId]['deposit_total'] = $cde['deposit_total'];
                $sourceUsers[$userId]['deposit_count'] = $cde['deposit_count'];
                $sourceUsers[$userId]['deposit_max'] = $cde['deposit_max'];
            }

            // 統計人工入款紀錄
            $cteId = 0;
            while ($ctes = $this->conn->fetchAll($pdweSql, [$cteId, $userDiff], $type)) {
                // 整理統計資料
                foreach ($ctes as $cte) {
                    $cteId = $cte['id'];
                    $userId = $cte['user_id'];
                    $amount = $cte['amount'];

                    if ($cte['currency'] != '156') {
                        $rate = $connShare->fetchColumn($exchangeSql, [$cte['currency'], $cte['at']]);

                        if (!$rate) {
                            $rate = $connShare->fetchColumn($exchangeNotExistSql, [$cte['currency'], $cte['at']]);

                            if (!$rate) {
                                $msg = "PaymentDepositWithdrawEntry Id:$cteId Currency:{$cte['currency']} At:{$cte['at']}";
                                $this->output->writeln("$msg Exchange Not Exist");

                                continue;
                            }
                        }
                        $amount = $cte['amount'] * $rate;
                    }

                    $sourceUsers[$userId]['manual_total'] += $amount;
                    $sourceUsers[$userId]['manual_count'] += 1;

                    if ($sourceUsers[$userId]['manual_max'] < $amount) {
                        $sourceUsers[$userId]['manual_max'] = $amount;
                    }
                }
            }

            // 統計公司入款紀錄
            $res = $this->conn->fetchAll($reSql, [$userDiff], $types);

            // 整理統計資料
            foreach ($res as $re) {
                $userId = $re['user_id'];
                $sourceUsers[$userId]['remit_total'] = number_format($re['remit_total'], 4, '.', '');
                $sourceUsers[$userId]['remit_count'] = $re['remit_count'];
                $sourceUsers[$userId]['remit_max'] = number_format($re['remit_max'], 4, '.', '');
            }

            // 統計出款紀錄
            $cwes = $this->conn->fetchAll($cweSql, [$userDiff], $types);

            // 整理統計資料
            foreach ($cwes as $cwe) {
                $userId = $cwe['user_id'];
                $sourceUsers[$userId]['withdraw_total'] = number_format($cwe['withdraw_total'], 4, '.', '');
                $sourceUsers[$userId]['withdraw_count'] = $cwe['withdraw_count'];
                $sourceUsers[$userId]['withdraw_max'] = number_format($cwe['withdraw_max'], 4, '.', '');
            }
            $insertSql = [];

            // 整理全部統計資料
            foreach ($sourceUsers as $userId => $sourceUser) {
                if (array_sum($sourceUser) == 0) {
                    continue;
                }
                $values = [
                    $userId,
                    $sourceUser['deposit_total'],
                    $sourceUser['deposit_count'],
                    $sourceUser['deposit_max'],
                    $sourceUser['manual_total'],
                    $sourceUser['manual_count'],
                    $sourceUser['manual_max'],
                    $sourceUser['remit_total'],
                    $sourceUser['remit_count'],
                    $sourceUser['remit_max'],
                    $sourceUser['withdraw_total'],
                    $sourceUser['withdraw_count'],
                    $sourceUser['withdraw_max']
                ];
                $insertSql[] = '( ' . implode(', ', $values) . ')';

                $count++;
            }

            if (count($insertSql)) {
                $sql = 'INSERT INTO user_stat (user_id, ';
                $sql .= 'deposit_total, deposit_count, deposit_max, ';
                $sql .= 'manual_total, manual_count, manual_max, ';
                $sql .= 'remit_total, remit_count, remit_max, ';
                $sql .= 'withdraw_total, withdraw_count, withdraw_max ';
                $sql .= ') VALUES ';
                $sql .= implode(',', $insertSql) . ';';

                $this->conn->exec($sql);
            }
            usleep(500000);
        }
        $this->output->writeln("Insert Remove User Stat Count: $count");
        $this->printPerformance($startTime);
    }

    /**
     * 印出效能相關訊息
     *
     * @param integer $startTime
     */
    private function printPerformance($startTime)
    {
        $endTime = microtime(true);
        $excutionTime = round($endTime - $startTime, 1);
        $timeString = $excutionTime . ' sec.';

        if ($excutionTime > 60) {
            $timeString = round($excutionTime / 60, 0) . ' mins.';
        }
        $this->output->writeln("\nExecute time: $timeString");

        $memory = memory_get_peak_usage() / 1024 / 1024;
        $usage = number_format($memory, 2);
        $this->output->writeln("Memory MAX use: $usage M");
    }
}
