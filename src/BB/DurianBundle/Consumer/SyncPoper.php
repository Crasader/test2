<?php
namespace BB\DurianBundle\Consumer;

/**
 * 此物件的主要功能是用來同步現金/快開額度/租卡的餘額、預扣及預存，
 * 明細或交易機制紀錄的更新應讓Poper處理
 */
class SyncPoper extends PoperBase
{
    /**
     * 切分連線數
     */
    const SHARD_COUNT = 2;

    /**
     * @var Array
     */
    protected $poolKey;

    /**
     * @var string
     */
    protected $executeQueue = null;

    /**
     * @var Array
     */
    private $syncQueue = [];

    /**
     * 開始處理訊息(queue)的時間
     *
     * @var float
     */
    private $startProcessQueueTime;

    /**
     * 結束處理訊息(queue)的時間
     *
     * @var float
     */
    private $endProcessQueueTime;

    /**
     * 開始下更新語法的時間
     *
     * @var float
     */
    private $startUpdateSqlTime;

    /**
     * 結束下更新語法的時間
     *
     * @var float
     */
    private $endUpdateSqlTime;

    /**
     * 處理 queue 的數量
     *
     * @var float
     */
    private $queueCount;

    /**
     * 處理 sql 的數量
     *
     * @var float
     */
    private $sqlCount;

    /**
     * 設定container，並以傳入的payway不同作存取對應的queue
     * 先執行需重試的 queue, 避免一推到 retry queue 馬上又重試一次
     *
     * @param Container $container
     * @param String $payway
     * @param String $executeQueue
     *
     * @return int 處理訊息的個數
     */
    public function runPop($container, $payway, $executeQueue = null)
    {
        $this->container = $container;
        $this->payway = $payway;
        $this->executeQueue = $executeQueue;
        $this->poolKey = array();

        // 先處理 retry queue, 避免一進入retry queue後又馬上處理
        $retryQueueName = $this->getRetryQueueName();
        $executeCount = $this->processMessage($retryQueueName);

        $queueName = $this->getQueueName();
        $executeCount += $this->processMessage($queueName);

        return $executeCount;
    }

    /**
     * 分配 cash sync queue 的訊息
     *
     * @param Container $container
     *
     * @return int 處理訊息的個數
     */
    public function departQueue($container)
    {
        $redis = $container->get('snc_redis.default');
        $logManager = $container->get('durian.logger_manager');
        $syncLogger = $logManager->setUpLogger('sync_cash_queue.log', null, 'queue');
        $executeCount = 0;

        $time = microtime(true);

        while ($executeCount < 20000) {
            try {
                $queue = $redis->rpop('cash_sync_queue');
                $queueMsg = json_decode($queue, true);

                if (empty($queueMsg)) {
                    break;
                }

                $syncLogger->addInfo($queue);

                $shareCount = (int) $queueMsg['user_id'] % self::SHARD_COUNT;
                $queueName = 'cash_sync_queue' . $shareCount;
                $redis->lpush($queueName, $queue);
            } catch (\Exception $e) {
                $exMsg = ['error', $e->getCode(), $e->getMessage(), $queue];
                \Doctrine\Common\Util\Debug::dump($exMsg);

                //送訊息至 italking
                $italkingOperator = $container->get('durian.italking_operator');
                $exceptionType = get_class($e);
                $message = $e->getMessage();
                $server = gethostname();
                $now = date('Y-m-d H:i:s');

                $italkingOperator->pushExceptionToQueue(
                    'developer_acc',
                    $exceptionType,
                    "[$server] [$now] cash depart sync queue failed: $message"
                );
            }

            $executeCount++;
        }

        if ($container->getParameter('kernel.environment') != 'test') {
            $str = sprintf(
                '[%s] queueCount: %s, departTime: %s sec.',
                date('Y-m-d H:i:s'),
                $executeCount,
                round(microtime(true) - $time, 1)
            );
            echo $str, PHP_EOL;
        }

        return $executeCount;
    }

    /**
     * 批次處理來自於queue的訊息
     * 以llen判斷redis的list中是否有訊息，如果有則進入迴圈，否則結束。
     * 若執行期間出錯的話則將該次Msg的後方加入錯誤計數後，重新推回queue。
     * 若錯滿10次以上則改推到FailedQueue後不再回推。
     * 每連續執行滿1000次則自動停止執行以釋放記憶體。
     *
     * 目前 SyncPoper 支援的指令有：
     *      SYNCHRONIZE : 同步對應key值的record(in mysql)，內容傳入key值
     *
     * @param String $queueName
     * @return int 處理訊息的個數
     */
    public function processMessage($queueName)
    {
        $redis = $this->getRedis();
        $queueCount = 0;
        $userCount = 0;

        $this->startProcessQueueTime = microtime(true);

        try {
            while ($queueCount < 10000 && $userCount < 1100) {
                $queue = $redis->rpop($queueName);
                $queueMsg = json_decode($queue, true);

                if (empty($queueMsg)) {
                    break;
                }

                if ($queueMsg['ERRCOUNT'] >= 10) {
                    $this->pushToFailedQueue($queueMsg);
                    continue;
                }

                // 目前 SyncPoper 只支援 SYNCHRONIZE & CASHSYNCHRONIZE, 其他的直接丟入 failed queue
                if ($queueMsg['HEAD'] == 'SYNCHRONIZE' || $queueMsg['HEAD'] == 'CASHSYNCHRONIZE') {
                    $userCount += $this->prepareSyncQueue($queueMsg);

                    $queueCount++;
                } else {
                    $this->pushToFailedQueue($queueMsg);
                }
            }
        } catch (\Exception $e) {
            // 若發生連線逾時,且無法從queue抓出訊息,則印出例外訊息
            if (!isset($queueMsg) && $e->getCode() === SOCKET_ETIMEDOUT) {
                $exMsg = ['error', $e->getCode(), $e->getMessage()];
                \Doctrine\Common\Util\Debug::dump($exMsg);

                //送訊息至 italking
                $italkingOperator = $this->container->get('durian.italking_operator');
                $exceptionType = get_class($e);
                $payway = ucwords(str_replace('_', ' ', $this->payway));
                $message = $e->getMessage();
                $server = gethostname();
                $now = date('Y-m-d H:i:s');

                $italkingOperator->pushExceptionToQueue(
                    'developer_acc',
                    $exceptionType,
                    "[$server] [$now] $payway SyncPoper failed: $message"
                );
            }
        }

        $this->endProcessQueueTime = microtime(true);
        $this->queueCount = $queueCount;

        $executeCount = 0;
        $isRetry = strpos($queueName, 'retry');

        if ($isRetry && $this->syncQueue) {
            $executeCount += $this->querySynchronize();

            $this->syncQueue = [];
        }

        // 若不為 retry queue, 則採批次處理
        if (!$isRetry && $this->syncQueue) {
            $executeCount += $this->queryBatchSynchronize();

            $this->syncQueue = [];
            $this->sqlCount = $executeCount;

            $this->logBatchSyncTime();
        }

        return $executeCount;
    }

    /**
     * 批次處理 SYNCHRONIZE 資料, 如果執行失敗則推到 retry queue
     *
     * @return int 處理訊息的個數
     */
    protected function queryBatchSynchronize()
    {
        $executeCount = 0;

        $this->startUpdateSqlTime = microtime(true);

        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            foreach ($this->syncQueue as $queue) {
                $result = $this->synchronizeBalance($queue);
                $executeCount += $result;
            }
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            // 如果跑測試時不顯示錯誤訊息
            if ($this->container->getParameter('kernel.environment') != 'test') {
                $exMsg = array('error', $e->getCode(),$e->getMessage());
                \Doctrine\Common\Util\Debug::dump($exMsg);
            }
            $this->retry($this->syncQueue);
        }

        $this->endUpdateSqlTime = microtime(true);

        return $executeCount;
    }

    /**
     * 處理 SYNCHRONIZE 資料, 如果執行失敗則推到 retry queue
     *
     * @return int 處理訊息的個數
     */
    protected function querySynchronize()
    {
        $executeCount = 0;
        try {
            foreach ($this->syncQueue as $queue) {
                $result = $this->synchronizeBalance($queue);
                $executeCount += $result;
            }
        } catch (\Exception $e) {
            // 如果跑測試時不顯示錯誤訊息
            if ($this->container->getParameter('kernel.environment') != 'test') {
                $exMsg = array('error', $e->getCode(),$e->getMessage());
                \Doctrine\Common\Util\Debug::dump($exMsg);
            }
            $this->retry($this->syncQueue);
        }
        return $executeCount;
    }

    /**
     * 將Redis中的資料與MySql同步(Redis為基準)，並製成SQL存入暫存區中。
     *
     * @param Array $queueMsg
     * @return int 處理訊息的個數
     */
    protected function synchronizeBalance($queueMsg)
    {
        if($this->payway == 'card') {
            $tableName = 'card';
        }

        if($this->payway == 'cash') {
            $tableName = 'cash';
        }

        $executeCount = 0;

        if ($tableName == 'card') {
            $userId = $this->getIdFromKey($queueMsg['KEY']);
            $redisWallet = $this->getRedis($userId);
            $fields = $redisWallet->hmget($queueMsg['KEY'], 'balance', 'last_balance', 'version');
            $balance = $fields[0];
            $lastBalance = $fields[1];
            $version = $fields[2];

            $arrData = [
                'TABLE' => $tableName,
                'KEY' => ['user_id' => $userId],
                'balance' => $balance,
                'last_balance' => $lastBalance,
                'version' => $version
            ];
            $sql = $this->setUpdateSql($arrData);
        }

        if ($tableName == 'cash') {
            $arrData = [
                'TABLE' => $tableName,
                'KEY' => [
                    'user_id' => $queueMsg['user_id'],
                    'currency' => $queueMsg['currency']
                ],
                'balance' => $queueMsg['balance'],
                'pre_sub' => $queueMsg['pre_sub'],
                'pre_add' => $queueMsg['pre_add'],
                'version' => $queueMsg['version']
            ];

            $arrData['negative'] = $queueMsg['balance'] < 0;

            if (isset($queueMsg['last_entry_at'])) {
                $arrData['last_entry_at'] = $queueMsg['last_entry_at'];
            }

            $sql = $this->setUpdateSql($arrData);
        }

        $result = $this->runSql($sql, 1);
        $executeCount += $result;

        return $executeCount;
    }

    /**
     * 輸出錯誤到log(目前將由nohup指令自動記錄)
     *
     * @param Array $queueMsg
     */
    protected function pushToFailedQueue($queueMsg)
    {
        // 如果跑測試時不顯示錯誤訊息
        if ($this->container->getParameter('kernel.environment') != 'test') {
            $now = new \DateTime();
            echo "Time::";
            echo $now->format('Y-m-d H:i:s');
            echo "::PUSH TO FAILED QUEUE\n";
            echo json_encode($queueMsg)."\n";
        }

        $redis = $this->getRedis();
        $queueName = $this->payway . '_sync_failed_queue' . $this->executeQueue;

        return $redis->lpush($queueName, json_encode($queueMsg));
    }

    /**
     * 取得 Queue 名稱
     *
     * @return String
     */
    protected function getQueueName()
    {
        $queueName = $this->payway . '_sync_queue' . $this->executeQueue;

        return $queueName;
    }

    /**
     * 取得 Retry Queue 名稱
     *
     * @return String
     */
    protected function getRetryQueueName()
    {
        $retryQueueName = $this->payway . '_sync_retry_queue' . $this->executeQueue;

        return $retryQueueName;
    }

    /**
     * 準備同步餘額的資料
     * 重複的 SYNCHRONIZE 只會執行一次
     *
     * @param Array $queueMsg
     * @return int
     */
    protected function prepareSyncQueue($queueMsg)
    {
        $userCount = 0;

        if ($this->payway == 'cash') {
            $index = sprintf('%s_%s', $queueMsg['user_id'], $queueMsg['currency']);

            if (!isset($this->syncQueue[$index])) {
                $this->syncQueue[$index] = $queueMsg;

                $userCount++;

                return $userCount;
            }

            if ($this->syncQueue[$index]['version'] < $queueMsg['version']) {
                $this->syncQueue[$index] = array_merge($this->syncQueue[$index], $queueMsg);
            } else {
                $this->syncQueue[$index] = $this->syncQueue[$index] + $queueMsg;
            }

            return $userCount;
        }

        // card
        if (!in_array($queueMsg['KEY'], $this->poolKey)) {
            $this->poolKey[] = $queueMsg['KEY'];
            $this->syncQueue[] = $queueMsg;

            $userCount++;
        }

        return $userCount;
    }

    /**
     * 輸出批次同步餘額的時間
     */
    private function logBatchSyncTime()
    {
        $diff1 = ($this->endProcessQueueTime - $this->startProcessQueueTime) * 1000;
        $diff2 = ($this->endUpdateSqlTime - $this->startUpdateSqlTime) * 1000;
        $diff3 = ($this->startUpdateSqlTime - $this->endProcessQueueTime) * 1000;

        if ($this->container->getParameter('kernel.environment') != 'test') {
            echo '[', date('Y-m-d H:i:s'), '] CashBatchSync: ',
                '"queueCount: ', $this->queueCount, 'ms" ',
                '"popQueueTime: ', $diff1, 'ms" ',
                '"RedisToDbTime: ', $diff3, 'ms" ',
                '"sqlCount: ', $this->sqlCount, 'ms" ',
                '"updateSqlTime: ', $diff2, 'ms"', PHP_EOL;
        }
    }
}
