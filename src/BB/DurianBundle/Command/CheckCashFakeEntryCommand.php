<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 檢查現金交易明細資料
 */
class CheckCashFakeEntryCommand extends ContainerAwareCommand
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
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * 目前的DB連線設定
     *
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $historyConn;

    /**
     * 時間區間開始的時間
     *
     * @var int
     */
    private $startTime = null;

    /**
     * 時間區間結束的時間
     *
     * @var int
     */
    private $endTime = null;

    /**
     * 直接更新差異資料
     *
     * @var boolean
     */
    private $update = false;

    /**
     * 顯示更新語法
     *
     * @var boolean
     */
    private $writeSql = false;

    /**
     * 顯示更新語法
     *
     * @var boolean
     */
    private $fill = false;

    /**
     * 檢查到缺失的資料時自動補資料
     *
     * @var boolean
     */
    private $dryRun = false;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:cronjob:check-cash-fake-entry')
            ->setDescription('檢查CashFakeEntry及history資料庫資料')
            ->addOption('starttime', null, InputOption::VALUE_OPTIONAL, '從指定的時間開始')
            ->addOption('endtime', null, InputOption::VALUE_OPTIONAL, '到指定的時間結束')
            ->addOption('update', null, InputOption::VALUE_NONE, '更新有差異的資料')
            ->addOption('write-sql', null, InputOption::VALUE_NONE, '顯示更新語法')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, '執行但不更新資料庫')
            ->addOption('fill-up', null, InputOption::VALUE_NONE, '檢查到缺失的資料時自動補資料')
            ->setHelp(<<<EOT
檢查CashFakeEntry及history資料庫資料
$ ./console durian:cronjob:check-cash-fake-entry

指定要檢查的時間區間
$ ./console durian:cronjob:check-cash-fake-entry --starttime="2012/1/1 12:00:00" --endtime="2012/1/2 12:00:00"
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

        $bgMonitor = $this->getContainer()->get('durian.monitor.background');
        $bgMonitor->commandStart('check-cash-fake-entry');

        // 檢查/更新 cash_fake_entry 資料
        $this->getOpt();

        if ($this->update) {
            $startTime = new \DateTime('now');
            $this->log("Update CashFakeEntry difference start at ".$startTime->format('Y-m-d H:i:s'));
            $msgNum = $this->updateCashFakeEntry();
            $endTime = new \DateTime('now');
            $this->log("Update CashFakeEntry difference finish at ".$endTime->format('Y-m-d H:i:s'));
        } else {
            $startTime = new \DateTime('now');
            $this->log("Check CashFakeEntry difference start at ".$startTime->format('Y-m-d H:i:s'));
            $msgNum = $this->checkCashFakeEntry();
            $endTime = new \DateTime('now');
            $this->log("Check CashFakeEntry difference finish at ".$endTime->format('Y-m-d H:i:s'));
        }

        $this->logger->popHandler()->close();

        $bgMonitor->setMsgNum($msgNum);
        $bgMonitor->setLastEndTime($this->endTime);
        $bgMonitor->commandEnd();
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager()
    {
        if ($this->em) {
            return $this->em;
        }

        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');

        return $this->em;
    }

    /**
     * 回傳Default DB連線
     *
     * @return \Doctrine\DBAL\Connection
     */
    private function getConnection()
    {
        if ($this->conn) {
            return $this->conn;
        }

        $this->conn = $this->getContainer()->get('doctrine.dbal.default_connection');
        return $this->conn;
    }

    /**
     * 回傳歷史DB連線
     *
     * @return \Doctrine\DBAL\Connection
     */
    protected function getHistoryConnection()
    {
        if ($this->historyConn) {
            return $this->historyConn;
        }

        $this->historyConn = $this->getContainer()->get('doctrine.dbal.his_connection');
        return $this->historyConn;
    }

    /**
     * 取得區間參數
     *
     * @throws \Exception
     */
    private function getOpt()
    {
        $startTime = $this->input->getOption('starttime') ? $this->input->getOption('starttime') : null;
        $endTime = $this->input->getOption('endtime') ? $this->input->getOption('endtime') : null;
        $this->update = $this->input->getOption('update');
        $this->writeSql = $this->input->getOption('write-sql');
        $this->dryRun = $this->input->getOption('dry-run');
        $this->fill = $this->input->getOption('fill-up');

        if ($this->update && ($startTime || $endTime)) {
            throw new \Exception('--starttime 及 --endtime 參數不可同時與 --update 一起使用');
        }

        if (empty($startTime) && !empty($endTime)) {
            throw new \Exception('需同時指定開始及結束時間');
        }

        if (empty($endTime)) {
            $this->endTime = new \DateTime('now');
        } else {
            $this->endTime = new \DateTime($endTime);
        }

        if (empty($startTime)) {
            $this->startTime = clone $this->endTime;
            // 預設檢查從24小時前開始的資料
            $this->startTime->sub(new \DateInterval('PT24H'));
        } else {
            $this->startTime = new \DateTime($startTime);
        }
    }

    /**
     * 檢查cash_fake_entry資料
     *
     * @throws \Exception
     */
    private function checkCashFakeEntry()
    {
        $italkingOperator = $this->getContainer()->get('durian.italking_operator');

        // 不要記SQL log
        $em = $this->getEntityManager();
        $conn = $this->getConnection();
        $historyConn = $this->getHistoryConnection();

        $insertResults = null;
        $updateResults = null;
        do {
            $startTime = $this->startTime->format('YmdHis');
            if ($this->startTime->format('YmdHis') > $this->endTime->format('YmdHis')) {
                continue;
            }

            /* 每次處理一分鐘的資料 */
            $this->startTime->add(new \DateInterval('PT1M'));
            if ($this->startTime->format('YmdHis') > $this->endTime->format('YmdHis')) {
                $endTime = $this->endTime->format('YmdHis');
            } else {
                $endTime = $this->startTime->format('YmdHis');
            }

            $query = "SELECT id, created_at, at, cash_fake_id, user_id, currency, opcode, amount, memo, balance, ref_id, cash_fake_version ".
                     "FROM cash_fake_entry ".
                     "WHERE at >= ? ".
                     "AND at < ? ".
                     "ORDER BY id;";
            $results = $conn->fetchAll($query, array($startTime, $endTime));

            // 如果區間範圍內沒有資料則不檢查
            if (!$results) {
                continue;
            }

            // 因目前只有 history 資料庫改為 bigint, 所以需把原本資料庫的空字串改為 0
            for ($i = 0; $i < count($results); $i++) {
                if (empty($results[$i]['ref_id'])) {
                    $results[$i]['ref_id'] = 0;
                }
            }

            $hisQuery = "SELECT id, created_at, at, cash_fake_id, user_id, currency, opcode, amount, memo, balance, ref_id, cash_fake_version ".
                        "FROM cash_fake_entry ".
                        "WHERE at >= ? ".
                        "AND at < ? ".
                        "ORDER BY id;";

            $hisResults = $historyConn->fetchAll($hisQuery, array($startTime, $endTime));

            // 檢查資料是否不存在或被修改
            foreach ($results as $result) {
                // 如果資料完全相同則不需處理
                $keyHis = array_search($result, $hisResults);
                if (is_int($keyHis) && $keyHis >= 0) {
                    // 將不需處理的資料去除, 增加比對速度
                    unset($hisResults[$keyHis]);
                    continue;
                }

                $existQuery = "SELECT id FROM cash_fake_entry WHERE id = ? LIMIT 0, 1;";
                $existResult = $historyConn->fetchAll($existQuery, array($result['id']));

                if (!$existResult) {
                    // 如果有帶入 fill-up 參數才自動補資料
                    if ($this->fill) {
                        $insertResults[] = $result;
                    }
                } else {
                    $updateResults[] = $result;
                }

                $msg = sprintf(
                    'CashFakeEntry: id: %d, cashFakeId: %d, userId: %d, currency: %d, opcode: %d, amount: %d, balance: %d, cash_fake_version: %d',
                    $result['id'],
                    $result['cash_fake_id'],
                    $result['user_id'],
                    $result['currency'],
                    $result['opcode'],
                    $result['amount'],
                    $result['balance'],
                    $result['cash_fake_version']
                );

                $queueMsg = "快開明細有差異, $msg";
                $italkingOperator->pushMessageToQueue('developer_acc', $queueMsg);
            }
        } while ($this->startTime->format('YmdHis') < $this->endTime->format('YmdHis'));

        $executeCount = 0;
        $historyConn->beginTransaction();
        $em->beginTransaction();
        try {
            // 補回缺少的資料
            if ($insertResults) {
                $columns = [
                    'id',
                    'cash_fake_id',
                    'user_id',
                    'currency',
                    'opcode',
                    'created_at',
                    'at',
                    'amount',
                    'memo',
                    'balance',
                    'ref_id',
                    'cash_fake_version'
                ];
                $insertSql = $this->setInsertSql('cash_fake_entry', $columns, $insertResults);

                // 如果有帶 --write-sql 參數則顯示更新差異的 update 語法
                if ($this->writeSql) {
                    $this->output->write(str_replace(";", ";\n", $insertSql));
                }

                if (!$this->dryRun) {
                    $insertResult = $historyConn->executeUpdate($insertSql);

                    $this->log(str_replace(";", ";\n", $insertSql));
                    $this->log("Return result: ".$insertResult);

                    if ($insertResult != count($insertResults)) {
                        $this->log("Insert failed.");
                        throw new \Exception("Insert failed.");
                    }
                    $executeCount += $insertResult;
                }
            }
            // 記錄有異動的資料
            if ($updateResults) {
                $columns = array('id', 'check_time');
                $insertSql = $this->setInsertSql('cash_fake_entry_diff', $columns, $updateResults, true);
                $insertResult = $em->getConnection()->executeUpdate($insertSql);
                $executeCount += $insertResult;

                $this->log(str_replace(";", ";\n", $insertSql));
                $this->log("Return result: ".$insertResult);
            }
            $em->flush();
            $em->commit();
            $historyConn->commit();
        } catch (\Exception $e) {
            $em->rollback();
            $historyConn->rollback();
            throw $e;
        }

        return $executeCount;
    }

    /**
     * 更新cash_fake_entry差異資料
     *
     * @throws \Exception
     */
    private function updateCashFakeEntry()
    {
        $em = $this->getEntityManager();
        $conn = $this->getConnection();
        $historyConn = $this->getHistoryConnection();

        $diffQuery = "SELECT id FROM cash_fake_entry_diff";
        $diffResults = $conn->fetchAll($diffQuery);
        // 如果區間範圍內沒有資料則不檢查
        if (!$diffResults) {
            return;
        }

        $diffIds = null;
        foreach ($diffResults as $row) {
            $diffIds[] = $row['id'];
        }

        // 只撈出同 id 只有一筆的資料
        $query = "SELECT id, created_at, at ".
                 "FROM cash_fake_entry ".
                 "WHERE id IN (?) ".
                 "GROUP BY id ".
                 "HAVING count(*) = 1 ".
                 "ORDER BY id;";
        $params = array($diffIds);
        $types = array(\Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
        $results = $conn->executeQuery($query, $params, $types)->fetchAll();

        // 只更新 created_at 及 at 欄位
        $columns = array('created_at', 'at');
        $sql = $this->setUpdateSql('cash_fake_entry', $columns, $results);
        $executeCount = 0;

        // 如果有帶 --write-sql 參數則顯示更新差異的 update 語法
        if ($this->writeSql) {
            $this->output->write(str_replace(";", ";\n", $sql));
        }
        // 如果有帶 --update 參數則直接更新差異並清空差異記錄
        if ($this->update && !$this->dryRun) {
            $result = $historyConn->executeUpdate($sql);

            $this->log(str_replace(";", ";\n", $sql));
            $this->log("Return result: ".$result);

            if ($result != 1) {
                $this->log("Update differences failed.");
                throw new \Exception("Update differences failed.");
            }

            $deleteSql = $this->setDeleteSql('cash_fake_entry_diff', $results);
            $result = $em->getConnection()->executeUpdate(
                $deleteSql['sql'],
                array($deleteSql['param']),
                array($deleteSql['type'])
            );

            $fullSql = $deleteSql['sql'];
            $strParam = implode(',', $deleteSql['param']);
            $fullSql = str_replace('?', $strParam, $fullSql);

            $this->log($fullSql);
            $this->log("Return result: ".$result);

            $em->flush();

            $executeCount = count($results);
        }

        return $executeCount;
    }

    /**
     * 組合 insert sql
     *
     * @return string
     */
    private function setInsertSql($table, $columns, $results, $duplicate = null)
    {
        // 明確指定這裡會用到的資料表，以避免語法錯誤
        $this->isAllowTable($table);

        $em = $this->getEntityManager();
        $valueSql = null;

        foreach ($results as $result) {
            $values = null;
            foreach ($columns as $column) {
                if ($column == 'check_time') {
                    $values[] = "'".date('Y-m-d H:i:s')."'";
                } elseif (gettype($result[$column]) == 'NULL') {
                    $values[] .= "null";
                } else {
                    $values[] .= "'".addslashes($result[$column])."'";
                }
            }
            $valueSql[] = "(".implode(", ", $values).")";
        }
        $sql = "INSERT INTO ".$table." (".implode(", ", $columns).") VALUES ";
        $sql .= implode(", ", $valueSql);

        // 如果是測試環境則不加上下面語法
        if ($em->getConnection()->getDatabasePlatform()->getName() != 'sqlite') {
            // 如果資料已經存在, 則只更新 check_time 欄位
            if ($duplicate) {
                $sql .= " ON DUPLICATE KEY UPDATE check_time = '".date('Y-m-d H:i:s')."'";
            }
        }
        $sql .= ";";

        return $sql;
    }

    /**
     * 組合 update sql
     *
     * @return string
     */
    private function setUpdateSql($table, $columns, $results)
    {
        // 明確指定這裡會用到的資料表，以避免語法錯誤
        $this->isAllowTable($table);

        $sql = '';
        foreach ($results as $result) {
            $sql .= "UPDATE $table SET ";
            $values = null;
            foreach ($columns as $column) {
                if (gettype($result[$column]) == 'NULL') {
                    $values[] = $column." = null";
                } else {
                    $values[] = $column." = '".addslashes($result[$column])."'";
                }
            }
            $sql .= implode(", ", $values);
            $sql .= " WHERE id = ".$result['id'].";";
        }

        return $sql;
    }

    /**
     * 組合 delete sql
     *
     * @return string
     */
    private function setDeleteSql($table, $results)
    {
        // 明確指定這裡會用到的資料表，以避免語法錯誤
        $this->isAllowTable($table);

        $values = null;
        foreach ($results as $result) {
            $values[] = $result['id'];
        }

        $sql = "DELETE FROM $table WHERE id IN (?);";

        return array(
            'sql'   => $sql,
            'param' => $values,
            'type'  => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY
        );
    }

    /**
     * 設定並記錄log
     *
     * @param String $message
     */
    private function log($msg)
    {
        if (null === $this->logger) {
            $this->logger = $this->getContainer()->get('durian.logger_manager')
                ->setUpLogger('check_cash_fake_entry.log');
        }

        $this->logger->addInfo($msg);
    }

    /**
     * 是否為允許使用的資料表
     *
     * @param string $tableName 資料表名稱
     * @return boolean
     */
    private function isAllowTable($tableName)
    {
        $allowTables = array(
            'cash_fake_entry_diff',
            'cash_fake_entry'
        );

        if (in_array($tableName, $allowTables)) {
            return true;
        }

        return false;
    }
}
