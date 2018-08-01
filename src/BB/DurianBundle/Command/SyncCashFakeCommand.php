<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use BB\DurianBundle\Entity\UserHasApiTransferInOut;

/**
 * 用來同步快開的餘額、明細之物件
 *
 * @author Cathy 2015.02.02
 */
class SyncCashFakeCommand extends ContainerAwareCommand
{
    /**
     * 在 Redis 會使用的 Keys
     *
     * @var array
     */
    protected $keys = [
        'balanceQueue' => 'cash_fake_balance_queue', // 餘額佇列 (List) (每筆資料放 JSON)
        'entryQueue' => 'cash_fake_entry_queue', // 明細佇列 (List) (每筆資料放 JSON)
        'transferQueue' => 'cash_fake_transfer_queue', // 轉帳佇列 (List) (每筆資料放 JSON)
        'operatorQueue' => 'cash_fake_operator_queue', // 操作者佇列 (List) (每筆資料放 JSON)
        'historyQueue' => 'cash_fake_history_queue', // 歷史資料庫佇列 (List) (每筆資料放 JSON)
        'transactionQueue' => 'cash_fake_trans_queue', // 兩階段交易佇列 (List) (每筆資料放 JSON)
        'transUpdateQueue' => 'cash_fake_trans_update_queue', // 兩階段交易更新佇列 (List) (每筆資料放 JSON)
        'negativeQueue' => 'cash_fake_negative_queue', // 負數餘額佇列 (List) (每筆資料放 JSON)
        'apiTransferInOutQueue' => 'cash_fake_api_transfer_in_out_queue' // 假現金使用者api轉入轉出統計佇列 (List) (每筆資料放 JSON)
    ];

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
     * 記錄最後一次採用的 Handler
     *
     * @var string
     */
    private $lastHandleName;

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
     * 設定 Logger
     *
     * @param string $handleName Handler 名稱
     */
    private function setLogger($handleName)
    {
        $container = $this->getContainer();

        if (!$this->sqlLogger) {
            $this->sqlLogger = $container->get('durian.logger_sql');
        }

        if ($this->lastHandleName != $handleName) {
            $logger = $container->get('logger');
            $logger->popHandler();

            $handler = $container->get('monolog.handler.' . $handleName);
            $logger->pushHandler($handler);

            $this->logger = $logger;
            $this->lastHandleName = $handleName;
        }
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
        $this->setName('durian:sync-cash-fake')
            ->setDescription('同步快開的(兩階段)交易、轉帳明細、操作者、餘額、歷史資料庫')
            ->addOption('entry', null, InputOption::VALUE_NONE, '同步(兩階段)交易、轉帳明細、操作者')
            ->addOption('balance', null, InputOption::VALUE_NONE, '同步餘額')
            ->addOption('history', null, InputOption::VALUE_NONE, '同步交易明細至歷史資料庫')
            ->addOption('recover-fail', null, InputOption::VALUE_NONE, '修復錯誤用')
            ->addOption('api-transfer-in-out', null, InputOption::VALUE_NONE, '同步api轉入轉出記錄到mysql')
            ->setHelp(<<<EOT
同步快開的(兩階段)交易、轉帳明細、操作者、餘額、歷史資料庫

在 Redis 中相關的 keys 如下:
(Entry)
    cash_fake_entry_queue               記錄需要與資料庫同步之交易明細
    cash_fake_entry_queue_retry         等待重試
    cash_fake_entry_queue_failed        若前項重試失敗，會儲存於此 (--recover-fail)
    cash_fake_transfer_queue            記錄需要與資料庫同步之轉帳明細
    cash_fake_transfer_queue_retry      等待重試
    cash_fake_transfer_queue_failed     若前項重試失敗，會儲存於此 (--recover-fail)
    cash_fake_operator_queue            記錄需要與資料庫同步之明細操作者
    cash_fake_operator_queue_retry      等待重試
    cash_fake_operator_queue_failed     若前項重試失敗，會儲存於此 (--recover-fail)
    cash_fake_trans_queue               記錄需要同步新增的交易編號(List)
    cash_fake_trans_queue_retry         等待重試
    cash_fake_trans_queue_failed        若前項重試失敗，會儲存於此 (--recover-fail)
    cash_fake_trans_update_queue        記錄需要同步更新的交易編號(List)
    cash_fake_trans_update_queue_retry  等待重試
    cash_fake_trans_update_queue_failed 若前項重試失敗，會儲存於此 (--recover-fail)

(Balance)
    cash_fake_balance_queue        記錄需要與資料庫同步之餘額
    cash_fake_balance_queue_retry  等待重試
    cash_fake_balance_queue_failed 若前項重試失敗，會儲存於此 (--recover-fail)

(History)
    cash_fake_history_queue        記錄需要與歷史資料庫同步之交易明細
    cash_fake_history_queue_retry  等待重試
    cash_fake_hisotry_queue_failed 若前項重試失敗，會儲存於此 (--recover-fail)

(ApiTransferInOut)
    api_transfer_in_out_queue        記錄需要與資料庫同步之假現金使用者api轉入轉出統計資料
    api_transfer_in_out_queue_retry  等待重試
    api_transfer_in_out_queue_failed 若前項重試失敗，會儲存於此 (--recover-fail)
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

        if ($input->getOption('recover-fail')) {
            $this->recoverFail = true;
        }

        $this->lastHandleName = null;

        // 同步(兩階段)交易、轉帳明細、操作者
        if ($input->getOption('entry')) {
            $this->syncEntry();
            $this->syncTransfer();
            $this->syncOperator();
            $this->syncTransaction();
            $this->syncTransactionStatus();
        }

        // 同步餘額、預扣、預存
        if ($input->getOption('balance')) {
            $this->syncBalance();
        }

        // 同步交易明細至歷史資料庫
        if ($input->getOption('history')) {
            $this->syncHistory();
        }

        // 同步api-transfer-in-out記錄至mysql
        if ($input->getOption('api-transfer-in-out')) {
            $this->syncApiTransferInOut();
        }
    }

    /**
     * 同步交易明細
     */
    private function syncEntry()
    {
        $this->getBackgound()->commandStart('sync-cash-fake-entry');
        $this->setLogger('sync_cash_fake_entry');
        $this->sqlLogger->setEnable(true);

        $insertFunction = 'newEntry';
        $queueKey = $this->keys['entryQueue'];
        $retryKey = "{$queueKey}_retry";
        $failedKey = "{$queueKey}_failed";

        if ($this->recoverFail) {
            $keys = [
                'queue'  => $failedKey,
                'retry'  => $failedKey,
                'failed' => null
            ];
            $count = $this->queryInsertWithRetryOrFailed($insertFunction, $keys);
        } else {
            $keys = [
                'queue'  => $retryKey,
                'retry'  => $retryKey,
                'failed' => $failedKey
            ];
            $count = $this->queryInsertWithRetryOrFailed($insertFunction, $keys);

            $keys = [
                'queue'  => $queueKey,
                'retry'  => $retryKey,
                'failed' => null
            ];
            $count += $this->queryInsert($insertFunction, $keys);
        }

        $this->sqlLogger->setEnable(false);
        $this->getBackgound()->setMsgNum($count);
        $this->getBackgound()->commandEnd();
    }

    /**
     * 同步轉帳明細
     */
    private function syncTransfer()
    {
        $this->getBackgound()->commandStart('sync-cash-fake-entry');
        $this->setLogger('sync_cash_fake_entry');
        $this->sqlLogger->setEnable(true);

        $insertFunction = 'newTransfer';
        $queueKey = $this->keys['transferQueue'];
        $retryKey = "{$queueKey}_retry";
        $failedKey = "{$queueKey}_failed";

        if ($this->recoverFail) {
            $keys = [
                'queue'  => $failedKey,
                'retry'  => $failedKey,
                'failed' => null
            ];
            $count = $this->queryInsertWithRetryOrFailed($insertFunction, $keys);
        } else {
            $keys = [
                'queue'  => $retryKey,
                'retry'  => $retryKey,
                'failed' => $failedKey
            ];
            $count = $this->queryInsertWithRetryOrFailed($insertFunction, $keys);

            $keys = [
                'queue'  => $queueKey,
                'retry'  => $retryKey,
                'failed' => null
            ];
            $count += $this->queryInsert($insertFunction, $keys);
        }

        $this->sqlLogger->setEnable(false);
        $this->getBackgound()->setMsgNum($count);
        $this->getBackgound()->commandEnd();
    }

    /**
     * 同步操作者
     */
    private function syncOperator()
    {
        $this->getBackgound()->commandStart('sync-cash-fake-entry');
        $this->setLogger('sync_cash_fake_entry');
        $this->sqlLogger->setEnable(true);

        $insertFunction = 'newOperator';
        $queueKey = $this->keys['operatorQueue'];
        $retryKey = "{$queueKey}_retry";
        $failedKey = "{$queueKey}_failed";

        if ($this->recoverFail) {
            $keys = [
                'queue'  => $failedKey,
                'retry'  => $failedKey,
                'failed' => null
            ];
            $count = $this->queryInsertWithRetryOrFailed($insertFunction, $keys);
        } else {
            $keys = [
                'queue'  => $retryKey,
                'retry'  => $retryKey,
                'failed' => $failedKey
            ];
            $count = $this->queryInsertWithRetryOrFailed($insertFunction, $keys);

            $keys = [
                'queue'  => $queueKey,
                'retry'  => $retryKey,
                'failed' => null
            ];
            $count += $this->queryInsert($insertFunction, $keys);
        }

        $this->sqlLogger->setEnable(false);
        $this->getBackgound()->setMsgNum($count);
        $this->getBackgound()->commandEnd();
    }

    /**
     * 同步兩階段交易明細(Transaction)
     */
    private function syncTransaction()
    {
        $this->getBackgound()->commandStart('sync-cash-fake-entry');
        $this->setLogger('sync_cash_fake_entry');
        $this->sqlLogger->setEnable(true);

        $insertFunction = 'newTransaction';
        $queueKey = $this->keys['transactionQueue'];
        $retryKey = "{$queueKey}_retry";
        $failedKey = "{$queueKey}_failed";

        if ($this->recoverFail) {
            $keys = [
                'queue'  => $failedKey,
                'retry'  => $failedKey,
                'failed' => null
            ];
            $count = $this->queryInsertWithRetryOrFailed($insertFunction, $keys);
        } else {
            $keys = [
                'queue'  => $retryKey,
                'retry'  => $retryKey,
                'failed' => $failedKey
            ];
            $count = $this->queryInsertWithRetryOrFailed($insertFunction, $keys);

            $keys = [
                'queue'  => $queueKey,
                'retry'  => $retryKey,
                'failed' => null
            ];
            $count += $this->queryInsert($insertFunction, $keys);
        }

        $this->sqlLogger->setEnable(false);
        $this->getBackgound()->setMsgNum($count);
        $this->getBackgound()->commandEnd();
    }

    /**
     * 同步兩階段交易明細狀態
     */
    private function syncTransactionStatus()
    {
        $this->getBackgound()->commandStart('sync-cash-fake-entry');
        $this->setLogger('sync_cash_fake_entry');
        $this->sqlLogger->setEnable(true);

        $queueKey = $this->keys['transUpdateQueue'];
        $retryKey = "{$queueKey}_retry";
        $failedKey = "{$queueKey}_failed";

        if ($this->recoverFail) {
            $keys = [
                'queue'  => $failedKey,
                'retry'  => $failedKey,
                'failed' => null
            ];
            $count = $this->queryTransactionUpdateWithRetryOrFailed($keys);
        } else {
            $keys = [
                'queue'  => $retryKey,
                'retry'  => $retryKey,
                'failed' => $failedKey
            ];
            $count = $this->queryTransactionUpdateWithRetryOrFailed($keys);

            $keys = [
                'queue'  => $queueKey,
                'retry'  => $retryKey,
                'failed' => null
            ];
            $count += $this->queryTransactionUpdate($keys);
        }

        $this->sqlLogger->setEnable(false);
        $this->getBackgound()->setMsgNum($count);
        $this->getBackgound()->commandEnd();
    }

    /**
     * 同步餘額
     */
    private function syncBalance()
    {
        $this->getBackgound()->commandStart('sync-cash-fake-balance');
        $this->setLogger('sync_cash_fake_balance');
        $this->sqlLogger->setEnable(true);

        $queueKey = $this->keys['balanceQueue'];
        $retryKey = "{$queueKey}_retry";
        $failedKey = "{$queueKey}_failed";

        if ($this->recoverFail) {
            $keys = [
                'queue'  => $failedKey,
                'retry'  => $failedKey,
                'failed' => null
            ];
            $count = $this->queryUpdate($keys);
        } else {
            $keys = [
                'queue'  => $retryKey,
                'retry'  => $retryKey,
                'failed' => $failedKey
            ];
            $count = $this->queryUpdate($keys);

            // queue採批次處理
            $keys = [
                'queue'  => $queueKey,
                'retry'  => $retryKey,
                'failed' => null
            ];
            $count += $this->batchQueryUpdate($keys);
        }

        $this->sqlLogger->setEnable(false);
        $this->getBackgound()->setMsgNum($count);
        $this->getBackgound()->commandEnd();
    }

    /**
     * 同步交易明細至歷史資料庫
     */
    private function syncHistory()
    {
        $this->getBackgound()->commandStart('sync-cash-fake-history');
        $this->setLogger('sync_cash_fake_history');
        $this->sqlLogger->setEnable(true);

        $insertFunction = 'newHistoryEntry';
        $queueKey = $this->keys['historyQueue'];
        $retryKey = "{$queueKey}_retry";
        $failedKey = "{$queueKey}_failed";

        if ($this->recoverFail) {
            $keys = [
                'queue'  => $failedKey,
                'retry'  => $failedKey,
                'failed' => null
            ];
            $count = $this->queryInsertWithRetryOrFailed($insertFunction, $keys);
        } else {
            $keys = [
                'queue'  => $retryKey,
                'retry'  => $retryKey,
                'failed' => $failedKey
            ];
            $count = $this->queryInsertWithRetryOrFailed($insertFunction, $keys);

            $keys = [
                'queue'  => $queueKey,
                'retry'  => $retryKey,
                'failed' => null
            ];
            $count += $this->queryInsert($insertFunction, $keys);
        }

        $this->sqlLogger->setEnable(false);
        $this->getBackgound()->setMsgNum($count);
        $this->getBackgound()->commandEnd();
    }

    /**
     * 同步假現金使用者api轉入轉出統計資料
     */
    private function syncApiTransferInOut()
    {
        $this->getBackgound()->commandStart('sync-user-api-transfer-in-out');

        $queueKey = $this->keys['apiTransferInOutQueue'];
        $retryKey = "{$queueKey}_retry";
        $failedKey = "{$queueKey}_failed";

        if ($this->recoverFail) {
            $keys = [
                'queue'  => $failedKey,
                'retry'  => $failedKey,
                'failed' => null
            ];
            $count = $this->updateOrInsertTransferInOut($keys);
        } else {
            $keys = [
                'queue'  => $retryKey,
                'retry'  => $retryKey,
                'failed' => $failedKey
            ];
            $count = $this->updateOrInsertTransferInOut($keys);

            $keys = [
                'queue'  => $queueKey,
                'retry'  => $retryKey,
                'failed' => null
            ];
            $count += $this->updateOrInsertTransferInOut($keys);
        }

        $this->getBackgound()->setMsgNum($count);
        $this->getBackgound()->commandEnd();
    }

    /**
     * 處理佇列中 queue 的快開明細
     *
     * @param string $insertFunction 新增資料的函數名稱
     * @param array  $queueKey       佇列名稱
     * @return integer
     */
    private function queryInsert($insertFunction, $queueKey)
    {
        $logManager = $this->getContainer()->get('durian.logger_manager');
        $logger = $logManager->setUpLogger('sync_cash_fake_entry_queue.log', null, 'queue');
        $redis = $this->getRedis();

        $limit = 1000;
        $executeCount = 0;
        $jsonEntries = [];
        $arrayEntries = [];

        if (strpos($queueKey['queue'], 'history')) {
            $limit = 2500;
        }

        try {
            while ($executeCount < $limit) {
                $jsonData = $redis->rpop($queueKey['queue']);
                $arrayData = json_decode($jsonData, true);

                if (!$arrayData) {
                    break;
                }

                $logger->addInfo($jsonData);
                $jsonEntries[] = $jsonData;
                $arrayEntries[] = $arrayData;

                $executeCount++;
            }

            if ($arrayEntries) {
                $result = $this->$insertFunction($arrayEntries);

                // 如果執行失敗，queue 需回推
                if ($result[0] == 0) {
                    foreach ($jsonEntries as $jsonData) {
                        $this->pushToFailedOrNot($jsonData, $queueKey);
                    }

                    $msg = "$insertFunction failed: $result[1]";
                    $this->output->writeln(print_r($msg, true));
                }

                // 如果執行成功，且為 entry queue，則處理完成後要放到 history & negative queue
                if ($result[0] != 0 && strpos($queueKey['queue'], 'entry') !== false) {
                    foreach ($jsonEntries as $jsonData) {
                        $redis->lpush($this->keys['historyQueue'], $jsonData);
                    }

                    $this->pushToNegativeQueue($arrayEntries);
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

            foreach ($jsonEntries as $jsonData) {
                $this->pushToFailedOrNot($jsonData, $queueKey);
            }
        }

        return $executeCount;
    }

    /**
     * 處理佇列中 retry / failed 的快開明細
     *
     * @param string $insertFunction 新增資料的函數名稱
     * @param array  $queueKey       佇列名稱
     * @return integer
     */
    private function queryInsertWithRetryOrFailed($insertFunction, $queueKey)
    {
        $redis = $this->getRedis();

        $executeCount = 0;
        $jsonEntries = [];
        $arrayEntries = [];
        $limit = $redis->llen($queueKey['queue']);
        $isContinue = false;

        // 若為 recoverFail，處理上限將改為 2000，並不作 break，以防 queue 為 null
        if (strpos($queueKey['queue'], 'failed') !== false) {
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

                $jsonEntries[] = $jsonData;
                $arrayEntries[] = $arrayData;

                $executeCount++;
            }

            if ($arrayEntries) {
                $result = $this->$insertFunction($arrayEntries);

                // 如果執行失敗，queue 需回推
                if ($result[0] == 0) {
                    foreach ($jsonEntries as $jsonData) {
                        $this->pushToFailedOrNot($jsonData, $queueKey);
                    }

                    $msg = "$insertFunction failed: $result[1]";
                    $this->output->writeln(print_r($msg, true));
                }

                // 如果執行成功，且為 entry queue，則處理完成後要放到 history & negative queue
                if ($result[0] != 0 && strpos($queueKey['queue'], 'entry') !== false) {
                    foreach ($jsonEntries as $jsonData) {
                        $redis->lpush($this->keys['historyQueue'], $jsonData);
                    }

                    $this->pushToNegativeQueue($arrayEntries);
                }

                // 如果執行成功，且為 retry queue，則刪除已重試過的資料
                if ($result[0] != 0 && $queueKey['queue'] == $queueKey['retry']) {
                    foreach ($jsonEntries as $jsonData) {
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

            foreach ($jsonEntries as $jsonData) {
                $this->pushToFailedOrNot($jsonData, $queueKey);
            }
        }

        return $executeCount;
    }

    /**
     * 批次處理佇列中 queue 的快開餘額
     *
     * @param array $queueKey 佇列名稱
     * @return integer
     */
    private function batchQueryUpdate($queueKey)
    {
        $logManager = $this->getContainer()->get('durian.logger_manager');
        $logger = $logManager->setUpLogger('sync_cash_fake_queue.log', null, 'queue');
        $redis = $this->getRedis();

        $executeCount = 0;
        $userCount = 0;
        $userBalance = [];
        $jsonBalance = [];

        try {
            while ($executeCount < 10000 && $userCount < 1000) {
                $jsonData = $redis->rpop($queueKey['queue']);
                $balanceInfo = json_decode($jsonData, true);

                if (!$balanceInfo) {
                    break;
                }

                $logger->addInfo($jsonData);
                $jsonBalance[] = $jsonData;

                $userId = $balanceInfo['user_id'];
                $currency = $balanceInfo['currency'];
                $version = $balanceInfo['version'];

                $index = sprintf('%s_%s', $userId, $currency);

                if (!isset($userBalance[$index])) {
                    $userBalance[$index] = $balanceInfo;

                    $userCount++;

                    continue;
                }

                if ($userBalance[$index]['version'] < $version) {
                    $userBalance[$index] = array_merge($userBalance[$index], $balanceInfo);
                } else {
                    $userBalance[$index] = $userBalance[$index] + $balanceInfo;
                }

                $executeCount++;
            }
        } catch (\Exception $e) {
            $exception = [
                'time'    => date('Y-m-d H:i:s'),
                'result'  => 'error',
                'code'    => $e->getCode(),
                'message' => $e->getMessage()
            ];
            $this->output->writeln(print_r($exception, true));

            foreach ($jsonBalance as $jsonData) {
                $this->pushToFailedOrNot($jsonData, $queueKey);
            }
        }

        if ($userBalance) {
            $this->batchUpdateBalance($queueKey, $userBalance);
        }

        return $executeCount;
    }

    /**
     * 處理佇列中 retry / failed 的快開餘額
     *
     * @param array $queueKey 佇列名稱
     * @return integer
     */
    private function queryUpdate($queueKey)
    {
        $redis = $this->getRedis();

        $executeCount = 0;
        $userCount = 0;
        $userBalance = [];
        $jsonBalance = [];
        $limit = $redis->llen($queueKey['queue']);
        $isContinue = false;

        // 若為 recoverFail，處理上限將改為 2000，並不作 break，以防 queue 為 null
        if (strpos($queueKey['queue'], 'failed') !== false) {
            $limit = 2000;
            $isContinue = true;
        }

        try {
            while ($redis->llen($queueKey['queue'])) {
                if ($executeCount >= $limit || $userCount >= 1000) {
                    break;
                }

                $jsonData = $redis->rpop($queueKey['queue']);
                $balanceInfo = json_decode($jsonData, true);

                if (!$balanceInfo && !$isContinue) {
                    break;
                }

                if (!$balanceInfo && $isContinue) {
                    continue;
                }

                $jsonBalance[] = $jsonData;

                $userId = $balanceInfo['user_id'];
                $currency = $balanceInfo['currency'];
                $version = $balanceInfo['version'];

                $index = sprintf('%s_%s', $userId, $currency);

                if (!isset($userBalance[$index])) {
                    $userBalance[$index] = $balanceInfo;

                    $userCount++;
                    continue;
                }

                if ($userBalance[$index]['version'] < $version) {
                    $userBalance[$index] = array_merge($userBalance[$index], $balanceInfo);
                } else {
                    $userBalance[$index] = $userBalance[$index] + $balanceInfo;
                }

                $executeCount++;
            }
        } catch (\Exception $e) {
            $exception = [
                'time'    => date('Y-m-d H:i:s'),
                'result'  => 'error',
                'code'    => $e->getCode(),
                'message' => $e->getMessage()
            ];
            $this->output->writeln(print_r($exception, true));

            foreach ($jsonBalance as $jsonData) {
                $this->pushToFailedOrNot($jsonData, $queueKey);
            }
        }

        if ($userBalance) {
            $this->updateBalance($queueKey, $userBalance);
        }

        return $executeCount;
    }

    /**
     * 處理佇列中 queue 兩階段交易的快開狀態
     *
     * @param array $queueKey 佇列名稱
     * @return integer
     */
    private function queryTransactionUpdate($queueKey)
    {
        $redis = $this->getRedis();

        $executeCount = 0;
        $jsonStatus = [];

        try {
            while ($executeCount < 1000) {
                $jsonData = $redis->rpop($queueKey['queue']);
                $arrayData = json_decode($jsonData, true);

                if (!$arrayData) {
                    break;
                }

                $jsonStatus[] = $jsonData;

                $result = $this->updateTransactionStatus($arrayData);

                // 如果執行失敗，queue 需回推
                if ($result == 0) {
                    $this->pushToFailedOrNot($jsonData, $queueKey);
                }

                $executeCount++;
            }
        } catch (\Exception $e) {
            $exception = [
                'time'    => date('Y-m-d H:i:s'),
                'result'  => 'error',
                'code'    => $e->getCode(),
                'message' => $e->getMessage()
            ];
            $this->output->writeln(print_r($exception, true));

            foreach ($jsonStatus as $jsonData) {
                $this->pushToFailedOrNot($jsonData, $queueKey);
            }
        }

        return $executeCount;
    }

    /**
     * 處理佇列中 retry / failed 兩階段交易的快開狀態
     *
     * @param array $queueKey 佇列名稱
     * @return integer
     */
    private function queryTransactionUpdateWithRetryOrFailed($queueKey)
    {
        $redis = $this->getRedis();

        $executeCount = 0;
        $jsonStatus = [];
        $limit = $redis->llen($queueKey['queue']);
        $isContinue = false;

        // 若為 recoverFail，處理上限將改為 2000，並不作 break，以防 queue 為 null
        if (strpos($queueKey['queue'], 'failed') !== false) {
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

                $jsonStatus[] = $jsonData;

                $result = $this->updateTransactionStatus($arrayData);

                // 如果執行失敗，queue 需回推
                if ($result == 0) {
                    $this->pushToFailedOrNot($jsonData, $queueKey);
                }

                // 如果執行成功，且為 retry queue，則刪除已重試過的資料
                if ($result != 0 && $queueKey['queue'] == $queueKey['retry']) {
                    $redis->hdel($queueKey['retry'] . '_count', $jsonData);
                }

                $executeCount++;
            }
        } catch (\Exception $e) {
            $exception = [
                'time'    => date('Y-m-d H:i:s'),
                'result'  => 'error',
                'code'    => $e->getCode(),
                'message' => $e->getMessage()
            ];
            $this->output->writeln(print_r($exception, true));

            foreach ($jsonStatus as $jsonData) {
                $this->pushToFailedOrNot($jsonData, $queueKey);
            }
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
     * @param array $table   資料表
     * @param array $entries 明細資料
     * @return string
     */
    private function setInsertSql($table, $entries)
    {
        $columns = [];
        $valuesArray = [];
        $rowCount = 1;

        foreach ($entries as $data) {
            $values = null;

            foreach ($data as $column => $value) {
                // 只記錄一次 column 名稱
                if ($rowCount == 1) {
                    $columns[] = $column;
                }

                if (gettype($value) == 'NULL') {
                    $values[] = 'null';
                } else {
                    $values[] = "'" . addslashes($value) . "'";
                }
            }

            $valuesArray[] = "(" . implode(", ", $values) . ")";

            $rowCount++;
        }

        $sql = "INSERT INTO " . $table . " (" . implode(", ", $columns) . ") VALUES ";
        $sql .= implode(", ", $valuesArray);

        return $sql;
    }

    /**
     * 建立交易明細
     *
     * @param array $entries 明細資料
     * @return array
     */
    private function newEntry($entries)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = $this->setInsertSql('cash_fake_entry', $entries);
        $affectedRow = $conn->executeUpdate($sql);

        // 如果執行筆數與結果不同則為失敗
        if ($affectedRow != count($entries)) {
            $affectedRow = 0;
        }

        return [$affectedRow, $sql];
    }

    /**
     * 建立轉帳明細
     *
     * @param array $entries 明細資料
     * @return array
     */
    private function newTransfer($entries)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = $this->setInsertSql('cash_fake_transfer_entry', $entries);
        $affectedRow = $conn->executeUpdate($sql);

        // 如果執行筆數與結果不同則為失敗
        if ($affectedRow != count($entries)) {
            $affectedRow = 0;
        }

        return [$affectedRow, $sql];
    }

    /**
     * 建立明細操作者
     *
     * @param array $entries 明細資料
     * @return array
     */
    private function newOperator($entries)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = $this->setInsertSql('cash_fake_entry_operator', $entries);
        $affectedRow = $conn->executeUpdate($sql);

        // 如果執行筆數與結果不同則為失敗
        if ($affectedRow != count($entries)) {
            $affectedRow = 0;
        }

        return [$affectedRow, $sql];
    }

    /**
     * 建立兩階段交易明細
     *
     * @param array $entries 明細資料
     * @return array
     */
    private function newTransaction($entries)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = $this->setInsertSql('cash_fake_trans', $entries);
        $affectedRow = $conn->executeUpdate($sql);

        // 如果執行筆數與結果不同則為失敗
        if ($affectedRow != count($entries)) {
            $affectedRow = 0;
        }

        return [$affectedRow, $sql];
    }

    /**
     * 建立歷史資料庫的交易明細
     *
     * @param array $entries 明細資料
     * @return array
     */
    private function newHistoryEntry($entries)
    {
        $conn = $this->getEntityManager('his')->getConnection();

        $sql = $this->setInsertSql('cash_fake_entry', $entries);
        $affectedRow = $conn->executeUpdate($sql);

        // 如果執行筆數與結果不同則為失敗
        if ($affectedRow != count($entries)) {
            $affectedRow = 0;
        }

        return [$affectedRow, $sql];
    }

    /**
     * 批次更新交易餘額
     *
     * @param array $queueKey    佇列名稱
     * @param array $userBalance 使用者餘額資料
     */
    private function batchUpdateBalance($queueKey, $userBalance)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:CashFake');
        $redis = $this->getRedis();

        $jsonBalance = [];
        $arrayBalance = [];
        $id = [];

        /*
         * 先 encode 需 rollback 回推的 queue，以防下語法時產生 deadlock 而被中斷
         * 且為避免 deadlock，需排除正在被更新的使用者，先暫不執行，將 queue 回推，等待下次更新
         */
        foreach ($userBalance as $balanceInfo) {
            $jsonData = json_encode($balanceInfo);

            if (!$redis->sismember('sync_cash_fake', $balanceInfo['user_id'])) {
                $jsonBalance[] = $jsonData;
                $arrayBalance[] = $balanceInfo;

                $id[] = $balanceInfo['user_id'];
            } else {
                $redis->lpush($this->keys['balanceQueue'] . '_retry', $jsonData);
            }
        }

        if ($id) {
            $redis->sadd('sync_cash_fake', $id);
        }

        $em->beginTransaction();

        try {
            foreach ($arrayBalance as $balanceInfo) {
                $repo->updateBalanceData($balanceInfo);
            }

            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();

            $exception = [
                'time'    => date('Y-m-d H:i:s'),
                'result'  => 'error',
                'code'    => $e->getCode(),
                'message' => $e->getMessage()
            ];
            $this->output->writeln(print_r($exception, true));

            foreach ($jsonBalance as $jsonData) {
                $this->pushToFailedOrNot($jsonData, $queueKey);
            }
        }

        if ($id) {
            $redis->srem('sync_cash_fake', $id);
        }
    }

    /**
     * 更新交易餘額
     *
     * @param array $queueKey    佇列名稱
     * @param array $userBalance 使用者餘額資料
     */
    private function updateBalance($queueKey, $userBalance)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:CashFake');
        $redis = $this->getRedis();

        $jsonBalance = [];

        try {
            foreach ($userBalance as $balanceInfo) {
                $jsonData = json_encode($balanceInfo);
                $jsonBalance[] = $jsonData;

                $repo->updateBalanceData($balanceInfo);

                // 只刪除有重試過的資料
                if ($queueKey['queue'] == $queueKey['retry']) {
                    $redis->hdel($queueKey['retry'] . '_count', $jsonData);
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

            foreach ($jsonBalance as $jsonData) {
                $this->pushToFailedOrNot($jsonData, $queueKey);
            }
        }
    }

    /**
     * 更新兩階段交易狀態
     *
     * @param array $statusInfo 狀態資料
     * @return integer
     */
    private function updateTransactionStatus($statusInfo)
    {
        $conn = $this->getEntityManager()->getConnection();

        $trans = [
            'checked'    => $statusInfo['checked'],
            'checked_at' => $statusInfo['checked_at'],
            'commited'   => $statusInfo['commited']
        ];

        $identifier = ['id' => $statusInfo['id']];

        $affectedRow = $conn->update('cash_fake_trans', $trans, $identifier);

        // 如果執行筆數與結果不同則為失敗
        if ($affectedRow != 1) {
            $affectedRow = 0;
        }

        return $affectedRow;
    }

    /**
     * 如果是正負餘額改變或負數餘額，都要推到負數佇列
     *
     * @param array $queues 佇列陣列
     */
    private function pushToNegativeQueue($queues)
    {
        $redis = $this->getRedis();
        $queueName = 'cash_fake_negative_queue';

        foreach ($queues as $queue) {
            $balance = $queue['balance'];
            $amount = $queue['amount'];
            $oriBalance = $balance - $amount;
            $stateChanged = ($balance >= 0 && $oriBalance < 0) || ($balance < 0 && $oriBalance >= 0);

            //正負餘額改變時會需要修改明細內容
            if ($stateChanged) {
                $redis->lpush($queueName, json_encode($queue));
            }

            //原本餘額即為負數，配合結算等功能更新負數餘額
            if (!$stateChanged && $balance < 0) {
                $negMsg = [
                    'cash_fake_id' => $queue['cash_fake_id'],
                    'user_id' => $queue['user_id'],
                    'currency' => $queue['currency'],
                    'balance' => $balance,
                    'cash_fake_version' => $queue['cash_fake_version']
                ];
                $redis->lpush($queueName, json_encode($negMsg));
            }
        }
    }

    /**
     * 更新或寫入假現金api轉入轉出記錄
     *
     * @param array $queueKey 佇列名稱
     * @return integer
     */
    private function updateOrInsertTransferInOut($queueKey)
    {
        $redis = $this->getRedis();
        $em = $this->getEntityManager();
        $queueMsg = null;

        $count = 0;
        while ($count <= 1000) {
            try {
                $transferIn = false;
                $transferOut = false;
                $queueMsg = json_decode($redis->rpop($queueKey['queue']), true);

                if (!$queueMsg) {
                    break;
                }

                if (isset($queueMsg['api_transfer_in'])) {
                    $transferIn = $queueMsg['api_transfer_in'];
                }

                if (isset($queueMsg['api_transfer_out'])) {
                    $transferOut = $queueMsg['api_transfer_out'];
                }

                $userHasApiTransferInOut = $em->find('BBDurianBundle:UserHasApiTransferInOut', $queueMsg['user_id']);

                if (!$userHasApiTransferInOut) {
                    $userHasApiTransferInOut = new UserHasApiTransferInOut($queueMsg['user_id'], $transferIn, $transferOut);
                    $em->persist($userHasApiTransferInOut);
                    $em->flush();
                    $count++;

                    continue;
                }

                if ($transferIn && $userHasApiTransferInOut->isApiTransferIn() == false) {
                    $userHasApiTransferInOut->setApiTransferIn($transferIn);
                    $em->flush();
                    $count++;

                    continue;
                }

                if ($transferOut && $userHasApiTransferInOut->isApiTransferOut() == false) {
                    $userHasApiTransferInOut->setApiTransferOut($transferOut);
                    $em->flush();
                    $count++;
                }
            } catch (\Exception $e) {
                $exception = [
                    'time' => date('Y-m-d H:i:s'),
                    'result' => 'error',
                    'code' => $e->getCode(),
                    'message' => $e->getMessage()
                ];

                $this->output->writeln(print_r($exception, true));

                if ($queueMsg) {
                    $this->pushToFailedOrNot(json_encode($queueMsg), $queueKey);
                }
            }
        }

        return $count;
    }
}
