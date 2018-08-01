<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class UpdateCreditCommand extends ContainerAwareCommand
{
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:update-credit')
            ->setDescription('檢查信用額度的line與total-line')
            ->addOption('total-line', null, InputOption::VALUE_NONE, '檢查資料庫 total_line, 注意: 以資料庫的資料為主')
            ->addOption('check-redis', null, InputOption::VALUE_NONE, '檢查資料庫與Redis的 line & total_line, 注意: 以資料庫的資料為主')
            ->addOption('line', null, InputOption::VALUE_NONE, '檢查資料庫內 line < total_line 的信用額度')
            ->addOption('update', null, InputOption::VALUE_NONE, '直接下語法更新')
            ->setHelp(<<<EOT
--total-line 相關檔案:
    total_line.YYYYMMDDHHIISS.log: 紀錄有錯誤的信用額度，包括原total_line 與正確之 total_line
    tl-sql.YYYYMMDDHHIISS.log:     紀錄要更新資料庫用的語法
    tl-redis.YYYYMMDDHHIISS.log:   紀錄要刪除 Redis 中 credit key 的語法

# 檢查資料庫中的 total_line, 若有錯誤會紀錄在Log檔中
app/console durian:update-credit --total-line

# 同前，但加上 --update 會直接更新資料庫, 並刪除 Redis 中的 credit key
app/console durian:update-credit --total-line


--check-redis 相關檔案:
    check-redis.YYYYMMDDHHIISS.log: 紀錄不一致的信用額度，包括 db line 與 redis line
    cr-redis.YYYYMMDDHHIISS.log:    紀錄要刪除 Redis 中 credit key 的語法

# 檢查資料庫與Redis的 line & total_line 是否一致，若不一致會紀錄在Log檔中
app/console durian:update-credit --check-redis

# 同前，但加上 --update 會直接刪除 Redis 中的 credit key
app/console durian:update-credit --check-redis --update


--line 相關檔案:
    line.YYYYMMDDHHIISS.log: 紀錄Line有問題的信用額度

# 檢查資料庫內 line < total_line 的信用額度
app/console durian:update-credit --line
EOT
                );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $update = $input->getOption('update');
        $checkTotalLine = $input->getOption('total-line');
        $checkRedis = $input->getOption('check-redis');
        $checkLine = $input->getOption('line');

        $beginStr = (new \DateTime)->format('Y-m-d H:i:s');
        $output->write("{$beginStr} : UpdateCreditCommand begin...", true);

        if ($checkTotalLine) {
            $this->checkTotalLineInDb($update);
        }

        if ($checkRedis) {
            $this->checkLineAndTotalLine($update);
        }

        if ($checkLine) {
            $this->checkLineLessThanTotalLine($update);
        }

        $endStr = (new \DateTime)->format('Y-m-d H:i:s');
        $output->write("{$endStr} : UpdateCreditCommand end...", true);
    }

    /**
     * 檢查 line 小於 total_line 的信用額度
     */
    private function checkLineLessThanTotalLine()
    {
        $curDate = new \DateTime();

        $executeCount = 0;
        $errCount = 0;

        $at = $curDate->format('YmdHis');

        // 開啟 Log 檔準備寫入資料
        $logFile = 'line.' . $at . '.log';
        $logger = $this->getContainer()->get('durian.logger_manager')->setUpLogger($logFile);
        $logger->addInfo('user_id, group_num, credit_id, line, total_line');

        // 檢查 Line < TotalLine
        $sql = "SELECT id, user_id, line, total_line, group_num FROM credit WHERE line < total_line";
        $state = $this->getConnection()->query($sql);

        while ($row = $state->fetch()) {
            $executeCount++;

            // 暫停一下
            if (($executeCount % 1000) == 0) {
                sleep(1);
            }

            $log = sprintf(
                "%s,%s,%s,%s,%s",
                $row['user_id'],
                $row['group_num'],
                $row['id'],
                $row['line'],
                $row['total_line']
            );
            $logger->addInfo($log);

            $errCount++;
        }

        $logger->addInfo("Total: $errCount");
        $logger->popHandler()->close();
    }

    /**
     * 檢查資料庫與Redis的 line & total_line 是否一致
     *
     * @param boolean $update 是否直接更新
     */
    private function checkLineAndTotalLine($update = false)
    {
        $curDate = new \DateTime();

        $loggerManager = $this->getContainer()->get('durian.logger_manager');

        $executeCount = 0;
        $errCount = 0;

        $at = $curDate->format('YmdHis');

        // 開啟 Log 檔準備寫入資料
        $redisFile = 'cr-redis.' . $at . '.log';
        $logFile = 'check-redis.' . $at . '.log';

        $loggerCrRedis = $loggerManager->setUpLogger($redisFile);
        $loggerCheckRedis = $loggerManager->setUpLogger($logFile);
        $loggerCheckRedis->addInfo('user_id, group_num, credit_id, line(db), line(redis)');

        // 檢查所有 Credit
        $sql = "SELECT id, user_id, line, total_line, group_num FROM credit";
        $state = $this->getConnection()->query($sql);

        $redisPool = [];

        while ($row = $state->fetch()) {
            $executeCount++;

            // 暫停一下
            if (($executeCount % 1000) == 0) {
                sleep(1);
            }

            $userId = $row['user_id'];
            $groupNum = $row['group_num'];
            $line = $row['line'];
            $totalLine = $row['total_line'];
            $creditId = $row['id'];

            $creditKey = sprintf(
                '%s_%s_%s',
                'credit',
                $userId,
                $groupNum
            );

            $redis = $this->getRedis($userId);
            $creditInfo = $redis->hgetall($creditKey);

            if (!$creditInfo) {
                continue;
            }

            // line & total_line 一樣不刪除
            if ($creditInfo['line'] == $line && $creditInfo['total_line'] == $totalLine) {
                continue;
            }

            // Redis Log
            $redisCmd = sprintf("del %s",  $creditKey);
            $loggerCrRedis->addInfo($redisCmd);
            $loggerCrRedis->popHandler()->close();

            $redisPool[] = $creditKey;

            // Error Log
            $log = sprintf(
                "%s,%s,%s,%s,%s",
                $userId,
                $groupNum,
                $creditId,
                $line,
                $creditInfo['line']
            );
            $loggerCheckRedis->addInfo($log);
            $errCount++;
        }

        // 開始刪除
        if ($update) {
            foreach ($redisPool as $key) {
                $userId = explode("_", $key)[1];
                $redis = $this->getRedis($userId);
                $ret = $redis->del($key);

                $msg = sprintf(
                    "del %s : %s\n",
                    $key,
                    $ret
                );
                $this->output->writeln($msg);
            }
        }

        $loggerCheckRedis->addInfo("Total: $errCount");
        $loggerCheckRedis->popHandler()->close();
    }

    /**
     * 檢查資料庫中的 total_line
     *
     * @param boolean $update 是否直接更新
     */
    private function checkTotalLineInDb($update = false)
    {
        $curDate = new \DateTime();

        $em = $this->getEntityManager();
        $loggerManager = $this->getContainer()->get('durian.logger_manager');

        $executeCount = 0;
        $errCount = 0;

        $at = $curDate->format('YmdHis');

        // 開啟 Log 檔準備寫入資料，sql因為要直接輸出sql語法，所以不使用monolog
        $redisFile = 'tl-redis.' . $at . '.log';
        $logFile = 'total_line.' . $at . '.log';

        $env = $this->getContainer()->getParameter('kernel.environment');
        $logDir = $this->getContainer()->getParameter('kernel.logs_dir') . DIRECTORY_SEPARATOR . $env;
        $sqlFile = $logDir . DIRECTORY_SEPARATOR . 'tl-sql.' . $at . '.log';

        $sqlFh = fopen($sqlFile, 'w');
        $loggerRedis = $loggerManager->setUpLogger($redisFile);
        $loggerTotal = $loggerManager->setUpLogger($logFile);
        $loggerTotal->addInfo('user_id, group_num, credit_id, line, total_line(before), total_line(after)');

        // 針對所有非會員(role != 1) 檢查 total-line
        $sql = 'SELECT c.id, user_id, line, total_line, group_num ' .
            'FROM credit c ' .
            'INNER JOIN user u ON u.id = c.user_id ' .
            'WHERE role != 1';
        $state = $this->getConnection()->query($sql);

        $sqlPool = [];
        $sqlParams = [];
        $redisPool = [];

        while ($row = $state->fetch()) {
            $executeCount++;

            // 暫停一下
            if (($executeCount % 1000) == 0) {
                sleep(1);
            }

            $userId = $row['user_id'];
            $groupNum = $row['group_num'];
            $totalLine = $row['total_line'];
            $line = $row['line'];
            $creditId = $row['id'];

            $qb = $em->createQueryBuilder();
            $qb->select('COALESCE(SUM(c.line), 0)');
            $qb->from('BBDurianBundle:Credit', 'c');
            $qb->from('BBDurianBundle:User', 'u');
            $qb->where('u.parent = :pid');
            $qb->andWhere('u.id = identity(c.user)');
            $qb->andWhere('c.groupNum = :groupNum');
            $qb->setParameter('pid', $userId);
            $qb->setParameter('groupNum', $groupNum);

            $childrenTotalLine = $qb->getQuery()->getSingleScalarResult();

            // 相同則檢查下一筆
            if ($totalLine == $childrenTotalLine) {
                continue;
            }

            // SQL Log
            $updateSql = sprintf(
                "UPDATE credit SET total_line = '%s' WHERE id = '%s';\n",
                $childrenTotalLine,
                $creditId
            );
            fputs($sqlFh, $updateSql);

            $sqlPool[] = "UPDATE credit SET total_line = ? WHERE id = ?;";
            $sqlParams[] = [
                $childrenTotalLine,
                $creditId
            ];

            // Error Log
            $log = sprintf(
                "%s,%s,%s,%s,%s,%s",
                $userId,
                $groupNum,
                $creditId,
                $line,
                $totalLine,
                $childrenTotalLine
            );
            $loggerTotal->addInfo($log);

            $creditKey = sprintf(
                '%s_%s_%s',
                'credit',
                $userId,
                $groupNum
            );

            $redis = $this->getRedis($userId);
            $creditInfo = $redis->hgetall($creditKey);

            if ($creditInfo) {
                $redisPool[] = $creditKey;

                // Redis Log
                $cmd = sprintf("del %s", $creditKey);
                $loggerRedis->addInfo($cmd);
                $loggerRedis->popHandler()->close();
            }

            $errCount++;
        }

        // 開始刪除
        if ($update) {
            $conn = $this->getConnection();

            foreach ($sqlPool as $i => $sql) {
                $params = $sqlParams[$i];
                $ret = $conn->executeUpdate($sql, $params);

                $updateSql = sprintf(
                    "UPDATE credit SET total_line = '%s' WHERE id = '%s';",
                    $params[0],
                    $params[1]
                );

                $msg = $updateSql . ' : ' . $ret;
                $this->output->writeln($msg);
            }

            foreach ($redisPool as $key) {
                $userId = explode("_", $key)[1];
                $redis = $this->getRedis($userId);
                $ret = $redis->del($key);

                $msg = sprintf(
                    "del %s : %s\n",
                    $key,
                    $ret
                );
                $this->output->writeln($msg);
            }
        }

        $loggerTotal->addInfo("Total: $errCount");
        $loggerTotal->popHandler()->close();
        fclose($sqlFh);
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getContainer()->get('doctrine.orm.entity_manager');
    }

    /**
     * 回傳DB連線
     *
     * @return \Doctrine\DBAL\Connection
     */
    private function getConnection()
    {
        return $this->getContainer()->get('doctrine.dbal.default_connection');
    }

    /**
     * 回傳 Redis 操作物件
     *
     * @param string | integer $nameOrUserId Redis 名稱或使用者編號
     * @return \Predis\Client
     */
    private function getRedis($nameOrUserId = 'default')
    {
        // 皆需先強制轉為數字，以避免部分進入的 userId 為字串
        if ((int) $nameOrUserId) {
            if ($nameOrUserId % 4 == 0) {
                $nameOrUserId = 'wallet4';
            } elseif ($nameOrUserId % 4 == 3) {
                $nameOrUserId = 'wallet3';
            } elseif ($nameOrUserId % 4 == 2) {
                $nameOrUserId = 'wallet2';
            } elseif ($nameOrUserId % 4 == 1) {
                $nameOrUserId = 'wallet1';
            }
        }

        return $this->getContainer()->get("snc_redis.{$nameOrUserId}");
    }
}
