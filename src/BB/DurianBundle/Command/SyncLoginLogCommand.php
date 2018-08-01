<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 用來同步登入紀錄
 */
class SyncLoginLogCommand extends ContainerAwareCommand
{
    /**
     * 最多重試次數
     */
    const MAX_RETRY_TIMES = 10;

    /**
     * 用來判斷是否要修復失敗
     *
     * @var boolean
     */
    private $recoverFail;

    /**
     * @var \Symfony\Bridge\Monolog\Logger
     */
    private $logger;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * SQL Logger
     *
     * @var \BB\DurianBundle\Logger\SQL
     */
    private $sqlLogger;

    /**
     * 輸出介面
     *
     * @var OutputInterface
     */
    private $output;

    /**
     * 資料表名稱
     *
     * @var string
     */
    private $table;

    /**
     * 設定 Logger
     */
    private function setLogger()
    {
        $container = $this->getContainer();

        $this->sqlLogger = $container->get('durian.logger_sql');

        $logger = $container->get('logger');
        $logger->popHandler();

        $handler = $container->get('monolog.handler.sync_login_log');
        $logger->pushHandler($handler);

        $this->logger = $logger;
    }

    /**
     * 回傳 EntityManager 物件
     *
     * @param string $name EntityManager 名稱
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager($name = 'default')
    {
        $container = $this->getContainer();
        $em = $container->get("doctrine.orm.{$name}_entity_manager");

        $config = $em->getConnection()->getConfiguration();
        $config->setSQLLogger($this->sqlLogger);

        return $em;
    }

    /**
     * 回傳背景監控服務
     *
     * @return Background
     */
    private function getBackgound()
    {
        return $this->getContainer()->get('durian.monitor.background');
    }

    /**
     * 回傳 Redis 操作物件
     *
     * @return \Predis\Client
     */
    private function getRedis()
    {
        return $this->getContainer()->get('snc_redis.default');
    }

    /**
     * 基本資訊設定
     */
    protected function configure()
    {
        $this->setName('durian:sync-login-log')
            ->setDescription('同步登入紀錄')
            ->addArgument('queue', InputArgument::REQUIRED, '要同步的登入紀錄 default:一般 mobile:行動裝置')
            ->addOption('recover-fail', null, InputOption::VALUE_NONE, '修復錯誤用')
            ->setHelp(<<<EOT
同步登入紀錄
app/console durian:sync-login-log default

同步登入紀錄行動裝置資訊
app/console durian:sync-login-log mobile

在 Redis 中相關的 queue 如下:
    login_log_queue               記錄需要與歷史資料庫同步之登入紀錄
    login_log_queue_retry         等待重試
    login_log_queue_failed        若前項重試失敗，會儲存於此 (--recover-fail)
    login_log_mobile_queue        記錄需要與歷史資料庫同步之登入紀錄行動裝置資訊
    login_log_mobile_queue_retry  等待重試
    login_log_mobile_queue_failed 若前項重試失敗，會儲存於此 (--recover-fail)
EOT
            );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->recoverFail = false;
        $queue = $input->getArgument('queue');

        if ($queue == 'default') {
            $this->table = 'login_log';
        }

        if ($queue == 'mobile') {
            $this->table = 'login_log_mobile';
        }

        if ($input->getOption('recover-fail')) {
            $this->recoverFail = true;
        }

        $this->syncHistory();
    }

    /**
     * 同步登入紀錄至歷史資料庫
     */
    private function syncHistory()
    {
        $backgound = $this->getBackgound();
        $command = 'sync-' . str_replace('_', '-', $this->table);
        $backgound->commandStart($command);
        $this->setLogger();
        $this->sqlLogger->setEnable(true);

        $queueKey = $this->table . '_queue';
        $retryKey = $this->table . '_queue_retry';
        $failedKey = $this->table . '_queue_failed';

        if ($this->recoverFail) {
            $keys = [
                'queue'  => $failedKey,
                'retry'  => $failedKey,
                'failed' => null
            ];
            $count = $this->queryInsertWithRetryOrFailed($keys);
        } else {
            $keys = [
                'queue'  => $retryKey,
                'retry'  => $retryKey,
                'failed' => $failedKey
            ];
            $count = $this->queryInsertWithRetryOrFailed($keys);

            $keys = [
                'queue'  => $queueKey,
                'retry'  => $retryKey,
                'failed' => null
            ];
            $count += $this->queryInsert($keys);
        }

        $this->sqlLogger->setEnable(false);
        $backgound->setMsgNum($count);
        $backgound->commandEnd();
    }

    /**
     * 處理佇列中 queue 的登入紀錄
     *
     * @param array $queueKey 佇列名稱
     * @return integer
     */
    private function queryInsert($queueKey)
    {
        $redis = $this->getRedis();

        $executeCount = 0;
        $jsonInfo = [];
        $arrayInfo = [];

        try {
            while ($executeCount < 1000) {
                $jsonData = $redis->rpop($queueKey['queue']);
                $arrayData = json_decode($jsonData, true);

                if (!$arrayData) {
                    break;
                }

                $jsonInfo[] = $jsonData;
                $arrayInfo[] = $arrayData;

                $executeCount++;
            }

            if ($arrayInfo) {
                $result = $this->newHistoryLog($arrayInfo);

                // 如果執行失敗，queue 需回推
                if ($result[0] == 0) {
                    foreach ($jsonInfo as $jsonData) {
                        $this->pushToFailedOrNot($jsonData, $queueKey);
                    }

                    $msg = "newHistoryEntry failed: $result[1]";
                    $this->output->writeln(print_r($msg, true));
                }
            }
        } catch (\Exception $e) {
            $exception = [
                'time'    => date('Y-m-d H:i:s'),
                'result'  => 'error',
                'code'    => $e->getCode(),
                'message' => $e->getMessage()
            ];
            $this->output->writeln(print_r($exception, true));

            foreach ($jsonInfo as $jsonData) {
                $this->pushToFailedOrNot($jsonData, $queueKey);
            }

            // 送訊息至 italking
            $italkingOperator = $this->getContainer()->get('durian.italking_operator');
            $exceptionType = get_class($e);
            $message = $e->getMessage();
            $server = gethostname();
            $now = date('Y-m-d H:i:s');

            $italkingOperator->pushExceptionToQueue(
                'developer_acc',
                $exceptionType,
                "[$server] [$now] Sync $this->table queryInsert() failed: $message"
            );
        }

        return $executeCount;
    }

    /**
     * 處理佇列中 retry / failed 的登入紀錄
     *
     * @param array $queueKey 佇列名稱
     * @return integer
     */
    private function queryInsertWithRetryOrFailed($queueKey)
    {
        $redis = $this->getRedis();

        $executeCount = 0;
        $jsonInfo = [];
        $arrayInfo = [];
        $limit = $redis->llen($queueKey['queue']);
        $isContinue = false;

        // 若為 recoverFail，處理上限將改為 2000，並不做 break，以防 queue 為 null
        if ($this->recoverFail) {
            $limit = 2000;
            $isContinue = true;
        }

        try {
            while ($redis->llen($queueKey['queue'])) {
                if ($executeCount >= $limit) {
                    break;
                }

                $jsonData = $redis->rpop($queueKey['queue']);
                $arrayData = json_decode($jsonData, true);

                if (!$arrayData && !$isContinue) {
                    break;
                }

                if (!$arrayData && $isContinue) {
                    continue;
                }

                $jsonInfo[] = $jsonData;
                $arrayInfo[] = $arrayData;

                $executeCount++;
            }

            if ($arrayInfo) {
                $result = $this->newHistoryLog($arrayInfo);

                // 如果執行失敗，queue 需回推
                if ($result[0] == 0) {
                    foreach ($jsonInfo as $jsonData) {
                        $this->pushToFailedOrNot($jsonData, $queueKey);
                    }

                    $msg = "newHistoryEntry failed: $result[1]";
                    $this->output->writeln(print_r($msg, true));
                }

                // 如果執行成功，且為 retry queue，則刪除已重試過的資料
                if ($result[0] != 0 && $queueKey['queue'] == $queueKey['retry']) {
                    foreach ($jsonInfo as $jsonData) {
                        $redis->hdel($queueKey['retry'] . '_count', $jsonData);
                    }
                }
            }
        } catch (\Exception $e) {
            $exception = [
                'time'    => date('Y-m-d H:i:s'),
                'result'  => 'error',
                'code'    => $e->getCode(),
                'message' => $e->getMessage()
            ];
            $this->output->writeln(print_r($exception, true));

            foreach ($jsonInfo as $jsonData) {
                $this->pushToFailedOrNot($jsonData, $queueKey);
            }

            // 送訊息至 italking
            $italkingOperator = $this->getContainer()->get('durian.italking_operator');
            $exceptionType = get_class($e);
            $message = $e->getMessage();
            $server = gethostname();
            $now = date('Y-m-d H:i:s');

            $italkingOperator->pushExceptionToQueue(
                'developer_acc',
                $exceptionType,
                "[$server] [$now] Sync $this->table queryInsertWithRetryOrFailed() failed: $message"
            );
        }

        return $executeCount;
    }

    /**
     * 檢查重試佇列的重試次數
     * 若重試太多次則將資料放入失敗佇列，否則放入重試佇列
     *
     * @param string $json     重試資料
     * @param array  $queueKey 佇列名稱
     */
    private function pushToFailedOrNot($json, $queueKey)
    {
        $redis = $this->getRedis();

        $retry = $queueKey['retry'];
        $failed = $queueKey['failed'];

        if ($failed) {
            $times = $redis->hincrby($retry . '_count', $json, 1);

            if ($times >= self::MAX_RETRY_TIMES) {
                $redis->lpush($failed, $json);
                $redis->hdel($retry . '_count', $json);

                return;
            }
        }

        $redis->lpush($retry, $json);
        $redis->hsetnx($retry . '_count', $json, 1);
    }

    /**
     * 建立新增語法
     *
     * @param array $info 登入資料
     * @return string
     */
    private function setInsertSql($info)
    {
        $valuesArray = [];

        // 只記錄一次 column 名稱
        $columns = array_keys($info[0]);

        foreach ($info as $data) {
            $values = null;

            foreach ($data as $value) {
                if (gettype($value) == 'NULL') {
                    $values[] = 'null';
                } elseif (gettype($value) == 'boolean') {
                    $values[] = (int)$value;
                } else {
                    $values[] = "'" . addslashes($value) . "'";
                }
            }

            $valuesArray[] = '(' . implode(', ', $values) . ')';
        }

        $sql = "INSERT INTO $this->table (" . implode(', ', $columns) . ') VALUES ';
        $sql .= implode(', ', $valuesArray);

        return $sql;
    }

    /**
     * 建立歷史資料庫的登入紀錄
     *
     * @param array $info 登入資料
     * @return array
     */
    private function newHistoryLog($info)
    {
        $conn = $this->getEntityManager('his')->getConnection();

        $sql = $this->setInsertSql($info);
        $affectedRow = $conn->executeUpdate($sql);

        // 如果執行筆數與結果不同則為失敗
        if ($affectedRow != count($info)) {
            $affectedRow = 0;
        }

        return [$affectedRow, $sql];
    }
}
