<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckBalanceCommand extends ContainerAwareCommand
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
     * 交易方式
     *
     * @var string
     */
    private $payWay;

    /**
     * 檢查欄位
     *
     * @var string
     */
    private $column;

    /**
     * 每次檢查的筆數
     *
     * @var integer
     */
    private $limit = 1000;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $entryConn;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this->setName('durian:check-balance')
            ->setDescription('檢查時間區間內有明細的使用者，資料庫的相關的餘額是否正確')
            ->addOption('pay-way', null, InputOption::VALUE_REQUIRED, '交易方式', null)
            ->addOption('check-column', null, InputOption::VALUE_REQUIRED, '預設檢查餘額，檢查預扣/存預需帶入此參數', null)
            ->addOption('start', null, InputOption::VALUE_REQUIRED, '預檢查日期起', null)
            ->addOption('end', null, InputOption::VALUE_REQUIRED, '預檢查日期迄', null)
            ->setHelp(<<<EOT
檢查時間區間內有明細的使用者，在資料庫的現金餘額是否正確
$ app/console durian:check-balance --pay-way=cash --start="2013-01-01 00:00:00" --end="2013-01-01 23:59:59"

檢查現金有預扣存的使用者，查看在有問題的時間區間內明細與交易記錄是否正確
$ app/console durian:check-balance --pay-way=cash --start="2013-01-01 00:00:00" --end="2013-01-01 23:59:59" --check-column=pre_sub
mysql 語法輸出檔: sqlOutput.sql, redis 語法輸出檔依照 wallet 的位置分開放 ex: redis1Output.txt
EOT
             );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $startTime = microtime(true);
        $this->input = $input;
        $this->output = $output;
        $this->payWay = $this->input->getOption('pay-way');
        $this->column = $this->input->getOption('check-column');
        $start = $this->input->getOption('start');
        $end = $this->input->getOption('end');

        $allowPayway = ['cash', 'cash_fake'];

        if (!in_array($this->payWay, $allowPayway)) {
            throw new \Exception('Invalid payway');
        }

        if (!$start || !$end) {
            throw new \Exception('需同時指定開始及結束日期');
        }

        $this->setUpLogger();
        $this->log('CheckBalanceCommand start.');

        $startDate = new \DateTime($start);
        $endDate = new \DateTime($end);

        if ($this->column) {
            $startDateTime = $startDate->format('Y-m-d H:i:s');
            $endDateTime = $endDate->format('Y-m-d H:i:s');

            $this->checkPreSubOrPreAdd($startDateTime, $endDateTime);
        }

        if (!$this->column) {
            $startDateTime = $startDate->format('YmdHis');
            $endDateTime = $endDate->format('YmdHis');

            $this->checkBalance($startDateTime, $endDateTime);
        }

        $this->log('CheckBalanceCommand finish.');
        $handler = $this->logger->popHandler();
        $handler->close();

        $endTime = microtime(true);
        $excutionTime = round($endTime - $startTime, 1);
        $timeString = $excutionTime . ' sec.';

        if ($excutionTime > 60) {
            $timeString = round($excutionTime / 60, 0) . ' mins.';
        }

        $this->output->write("\nExecute time: $timeString", true);

        $memory = memory_get_peak_usage() / 1024 / 1024;
        $usage = number_format($memory, 2);
        $this->output->write("Memory MAX use: $usage M", true);
    }

    /**
     * 檢查預扣/存
     *
     * @param string $start 開始時間
     * @param string $end   結束時間
     */
    private function checkPreSubOrPreAdd($start, $end)
    {
        $conn = $this->getConnection();
        $conn->connect('slave');

        $sql = "SELECT * FROM $this->payWay WHERE $this->column != 0";
        $stmt = $conn->executeQuery($sql, []);
        $results = $stmt->fetchAll();

        $cmd = [
            0 => [],
            1 => [],
            2 => [],
            3 => []
        ];

        foreach ($results as $result) {
            $ret = $this->checkEntry($start, $end, $result);

            if ($ret) {
                $cmd[$result['user_id'] % 4][] = $ret;
            }
        }

        $this->printResult($cmd);
    }

    /**
     * 輸出並記錄語法
     *
     * @param array $cmd 語法資料
     */
    private function printResult($cmd)
    {
        for ($i = 0; $i < count($cmd); $i++) {
            $reroute = $i;

            if ($i === 0) {
                $reroute = 4;
            }

            $msg = "redis-wallet$reroute:";
            $path = $this->getContainer()->get('kernel')->getRootDir() . "/../redis{$reroute}Output.txt";
            $fp = fopen($path, 'a+');
            fwrite($fp, $msg . "\n");
            $this->logger->addInfo($msg);

            foreach ($cmd[$i] as $c) {
                $msg = implode("\n", $c);
                fwrite($fp, $msg . "\n");
                $this->logger->addInfo($msg);
            }

            fclose($fp);
        }
    }

    /**
     * 檢查預扣/存
     *
     * $criteria:
     *   currency 幣別
     *   user_id  使用者編號
     *   balance  餘額
     *   pre_sub  預扣
     *   pre_add  預存
     *
     * @param string $start    開始時間
     * @param string $end      結束時間
     * @param array  $criteria 使用者資料
     */
    private function checkEntry($start, $end, $criteria)
    {
        $conn = $this->getConnection();
        $conn->connect('slave');

        $sql = "SELECT SUM(amount) " .
            "FROM {$this->payWay}_trans " .
            "WHERE checked = 0 AND created_at >= :start AND created_at < :end AND user_id = :user";
        $params = [
            'start' => $start,
            'end' => $end,
            'user' => $criteria['user_id']
        ];
        $stmt = $conn->executeQuery($sql, $params);
        $result = $stmt->fetchColumn();

        if ($result == $criteria[$this->column]) {
            return;
        }

        $log = "UPDATE cash " .
            "SET $this->column = $this->column -{$criteria[$this->column]}, version = {$criteria['version']} " .
            "WHERE user_id = {$criteria['user_id']} AND version < {$criteria['version']};\n";
        $path = $this->getContainer()->get('kernel')->getRootDir() . '/../sqlOutput.sql';
        $fp = fopen($path, 'a+');
        fwrite($fp, $log);
        $this->logger->addInfo($log);

        $dbName = $this->getContainer()->getParameter('database_name');
        $key = "{$this->payWay}_balance_{$criteria['user_id']}_{$criteria['currency']}";
        $redis = $this->getRedis($criteria['user_id']);
        $redisInfo = $redis->hgetall($key);

        if (empty($redisInfo)) {
            return;
        }

        return [
          'multi',
          "hincrBy {$dbName}_{$key} pre_sub -" . 10000 * $criteria[$this->column],
          "hincrBy {$dbName}_{$key} version 1",
          'exec'
        ];
    }

    /**
     * 檢查餘額
     *
     * @param string $start 開始時間
     * @param string $end   結束時間
     */
    private function checkBalance($start, $end)
    {
        $count = $this->countUser($start, $end);

        $lastUserId = 0;

        while ($count > 0) {
            $users = $this->getUser($lastUserId, $start, $end);

            foreach ($users as $user) {
                $this->checkDbAndRedis($user['user_id']);
                $lastUserId = $user['user_id'];
                unset($user['user_id']);
            }

            // 跑測試的時候，不sleep，避免測試碼執行時間過長
            if ($this->getContainer()->getParameter('kernel.environment') != 'test') {
                usleep(500000);
            }

            $count -= $this->limit;
        }
    }

    /**
     * 檢查餘額
     *
     * @param array $userId 使用者編號
     */
    private function checkDbAndRedis($userId)
    {
        $conn = $this->getConnection();
        $conn->connect('slave');

        $sql = "SELECT currency, balance, pre_sub, pre_add, version " .
            "FROM {$this->payWay} " .
            "WHERE user_id = $userId";
        $data = $conn->fetchArray($sql);

        if (!$data) {
            return;
        }

        $currency = $data[0];
        $balance = $data[1];
        $preSub = $data[2];
        $preAdd = $data[3];
        $version = $data[4];

        $redis = $this->getRedis($userId);
        $redisInfo = $redis->hgetall("{$this->payWay}_balance_{$userId}_{$currency}");

        if (empty($redisInfo)) {
            return;
        }

        $errorBalance = $balance != ($redisInfo['balance'] / 10000);
        $errorPreSub = $preSub != ($redisInfo['pre_sub'] / 10000);
        $errorPreAdd = $preAdd != ($redisInfo['pre_add'] / 10000);

        if ($version < $redisInfo['version']) {
            // 餘額不正確
            if ($errorBalance || $errorPreSub || $errorPreAdd) {
                $msg1 = sprintf(
                    "userId: %d 餘額不正確, 若查看資料庫 $this->payWay version 仍低於 %s, 請執行:",
                    $userId,
                    $redisInfo['version']
                );
                $this->log($msg1);

                $iscash = '%s';
                $value = '';
                if ($this->payWay == 'cash') {
                    $iscash = ', last_entry_at = %s';
                    $value = $redisInfo['last_entry_at'];
                }

                $sql = "UPDATE $this->payWay " .
                       "SET balance = %s, pre_sub = %s, pre_add = %s$iscash, version = %s " .
                       "WHERE user_id = %s AND currency = %s AND version < %s;";
                $msg2 = sprintf(
                    $sql,
                    $redisInfo['balance'] / 10000,
                    $redisInfo['pre_sub'] / 10000,
                    $redisInfo['pre_add'] / 10000,
                    $value,
                    $redisInfo['version'],
                    $userId,
                    $currency,
                    $redisInfo['version']
                );
                $this->log($msg2);
            }
        }

        return;
    }

    /**
     * 計算時間區間內有明細的所有使用者
     *
     * @param string $start 時間日期起
     * @param string $end   時間日期迄
     * @return integer
     */
    private function countUser($start, $end)
    {
        $conn = $this->getEntryConnection();
        $conn->connect('slave');

        $sql = "SELECT count(distinct(user_id)) " .
            "FROM {$this->payWay}_entry " .
            "WHERE at >= '$start' " .
            "AND at <= '$end'";

        return $conn->fetchColumn($sql);
    }

    /**
     * 取得時間區間內有明細的所有使用者
     *
     * @param integer $userId 使用者編號
     * @param string  $start  時間日期起
     * @param string  $end    時間日期迄
     * @return array
     */
    private function getUser($userId, $start, $end)
    {
        $conn = $this->getEntryConnection();
        $conn->connect('slave');

        $sql = "SELECT user_id " .
            "FROM {$this->payWay}_entry " .
            "WHERE user_id > $userId " .
            "AND at >= '$start' " .
            "AND at <= '$end' " .
            "GROUP BY user_id " .
            "ORDER BY user_id ASC " .
            "LIMIT $this->limit";

        return $conn->fetchAll($sql);
    }

    /**
     * 設定 logger
     */
    private function setUpLogger()
    {
        $handler = $this->getContainer()->get('monolog.handler.check_balance');
        $logger = $this->getContainer()->get('logger');
        $logger->pushHandler($handler);

        $this->logger = $logger;
    }

    /**
     * 記錄 log
     *
     * @param string $msg 訊息
     */
    private function log($msg)
    {
        $this->logger->addInfo($msg);
        $this->output->write($msg, true);
    }

    /**
     * 回傳 Default DB 連線
     *
     * @return \Doctrine\DBAL\Connection
     */
    private function getConnection()
    {
        $this->conn = $this->getContainer()->get('doctrine.dbal.default_connection');

        return $this->conn;
    }

    /**
     * 回傳明細 DB 連線
     *
     * @return \Doctrine\DBAL\Connection
     */
    protected function getEntryConnection()
    {
        if ($this->entryConn) {
            return $this->entryConn;
        }

        if ($this->payWay == 'cash') {
            $this->entryConn = $this->getContainer()->get('doctrine.dbal.entry_connection');
        } else {
            $this->entryConn = $this->getContainer()->get('doctrine.dbal.default_connection');
        }

        return $this->entryConn;
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
