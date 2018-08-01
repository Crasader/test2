<?php
namespace BB\DurianBundle\Consumer;

use Symfony\Component\DependencyInjection\ContainerAware;

class Poper extends PoperBase
{
    /**
     * 設定container，並以傳入的payway不同作存取對應的queue
     * 先執行需重試的 queue, 避免一推到 retry queue 馬上又重試一次
     *
     * @param Container $container
     * @param String $payway
     *
     * @return int 處理訊息的個數
     */
    public function runPop($container, $payway)
    {
        $this->container = $container;
        $this->payway = $payway;

        // 先處理 retry queue, 避免一進入retry queue後又馬上處理
        $retryQueueName =  $this->getRetryQueueName();
        $executeCount = $this->processRetryMessage($retryQueueName);

        // 因為 SQL Lite 不支援 multi insert, 所以改跑單筆 insert
        $queueName = $this->getQueueName();
        if ($this->getEntityManager()->getConnection()->getDatabasePlatform()->getName() == 'sqlite') {
            $executeCount += $this->processRetryMessage($queueName, true);
        } else {
            $executeCount += $this->processMessage($queueName);
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
     * insert 將會組成 multi insert 語法批次處理,
     * update 將會每次處理一筆
     *
     * 目前 Poper 支援的指令有：
     *      INSERT : consumer接到後會直接以DBAL執行的sql，用來處理所有 insert 語法
     *      UPDATE : consumer接到後會直接以DBAL執行的sql，專門用來處理 update 語法
     *
     * @param String $queueName
     * @return int 處理訊息的個數
     */
    public function processMessage($queueName)
    {
        $redis = $this->getRedis();
        $logManager = $this->container->get('durian.logger_manager');
        $entryLogger = $logManager->setUpLogger('sync_cash_entry_queue.log', null, 'queue');
        $insertQueue = array();
        $updateQueue = array();
        $executeCount = 1;
        $executeNum = 1000; // 處理數量預設為 1000
        $times = [];
        $times[] = microtime(true);

        // 只有現金明細處理數量暫時調整為 1500
        if ($this->payway == 'cash') {
            $executeNum = 1500;
        }

        // 針對同步到歷史資料庫數量調整
        if ($this->payway == 'cash_entry') {
            $executeNum = 5000;
        }

        try {
            while (true) {
                if ($executeCount >= $executeNum) {
                    break;
                }

                $executeCount++;

                $queue = $redis->rpop($queueName);
                $queueMsg = null;
                $queueMsg = json_decode($queue, true);

                if (empty($queueMsg)) {
                    break;
                }

                if ($this->payway == 'cash') {
                    $entryLogger->addInfo($queue);
                }

                if ($queueMsg['ERRCOUNT'] >= 10) {
                    $this->pushToFailedQueue($queueMsg);
                    continue;
                }

                // 目前 Poper 只支援 INSERT 及 UPDATE, 如果其他的直接丟入 failed queue
                if ($queueMsg['HEAD'] == 'INSERT') {
                    $insertQueue[$queueMsg['TABLE']][] = $queueMsg;
                } elseif ($queueMsg['HEAD'] == 'UPDATE') {
                    $updateQueue[] = $queueMsg;
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
                    "[$server] [$now] $payway Poper.processMessage() failed: $message"
                );
            }
        }

        $times[] = microtime(true);

        $executeCount = 0;
        if ($insertQueue) {
            $executeCount += $this->queryInsert($insertQueue);
        }

        if ($updateQueue) {
            $executeCount += $this->queryUpdate($updateQueue);
        }

        $times[] = microtime(true);

        if ($this->container->getParameter('kernel.environment') != 'test') {
            // R: Redis 處理時間, M: Mysql 整體處理時間
            printf(
                "%s R: %0.4f M: %0.4f\n",
                date('Ymd His'),
                ($times[1] - $times[0]) * 1000,
                ($times[2] - $times[1]) * 1000
            );
        }

        return $executeCount;
    }

    /**
     * 處理來自於retry queue的訊息
     * 以llen判斷redis的list中是否有訊息，如果有則進入迴圈，否則結束。
     * 若執行期間出錯的話則將該次Msg的後方加入錯誤計數後，重新推回queue。
     * 若錯滿10次以上則改推到FailedQueue後不再回推。
     * 每連續執行滿1000次則自動停止執行以釋放記憶體。
     * 重試時將每次只處理一筆資料。
     *
     * 目前 Poper 支援的指令有：
     *      INSERT : consumer接到後會直接以DBAL執行的sql，用來處理所有 insert 語法
     *      UPDATE : consumer接到後會直接以DBAL執行的sql，專門用來處理 update 語法
     *
     * @param String $queueName
     * @param Boolean $isLog
     * @return int 處理訊息的個數
     */
    public function processRetryMessage($queueName, $isLog = null)
    {
        $redis = $this->getRedis();
        $logManager = $this->container->get('durian.logger_manager');
        $entryLogger = $logManager->setUpLogger('sync_cash_entry_queue.log', null, 'queue');
        $executeCount = 0;
        try {
            $queueCount = $redis->llen($queueName);
            for ($i = 0; $i < $queueCount; $i++) {
                $queue = $redis->rpop($queueName);
                $queueMsg = null;
                $queueMsg = json_decode($queue, true);
                if (empty($queueMsg)) {
                    break;
                }
                if ($this->payway == 'cash' && $isLog) {
                    $entryLogger->addInfo($queue);
                }
                if ($queueMsg['ERRCOUNT'] >= 10) {
                    $this->pushToFailedQueue($queueMsg);
                    continue;
                }
                // 目前 Poper 只支援 INSERT 及 UPDATE, 如果其他的直接丟入 failed queue
                if ($queueMsg['HEAD'] == 'INSERT') {
                    $queueMsgs = array();
                    $queueMsgs[$queueMsg['TABLE']][] = $queueMsg;
                    $executeCount += $this->queryInsert($queueMsgs);
                } elseif ($queueMsg['HEAD'] == 'UPDATE') {
                    $queueMsgs = array();
                    $queueMsgs[] = $queueMsg;
                    $executeCount += $this->queryUpdate($queueMsgs);
                } else {
                    $this->pushToFailedQueue($queueMsg);
                }
            }
        } catch (\Exception $e) {
            // 如果跑測試時不顯示錯誤訊息
            if ($this->container->getParameter('kernel.environment') != 'test') {
                $exMsg = array('error', $e->getCode(),$e->getMessage());
                \Doctrine\Common\Util\Debug::dump($exMsg);
            }

            // 若發生連線逾時,且無法從retryQueue抓出訊息,則丟出例外訊息
            if (!isset($queueMsg) && $e->getCode() === SOCKET_ETIMEDOUT) {
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
                    "[$server] [$now] $payway Poper.processRetryMessage() failed: $message"
                );

                throw $e;
            }

            $this->retry($queueMsg);
        }

        return $executeCount;
    }

    /**
     * 處理 INSERT 資料, 將資料轉成 sql 語法並執行, 如果執行失敗則推到 retry queue
     *
     * @param Array $queues
     * @return int 處理訊息的個數
     */
    protected function queryInsert($queues)
    {
        $times = [];

        $executeCount = 0;
        try {
            foreach ($queues as $table => $queueMsgs) {
                if ($table == 'cash_entry') {
                    $times[] = microtime(true);
                }

                $sql = $this->setInsertSql($table, $queueMsgs);

                if ($table == 'cash_entry') {
                    $times[] = microtime(true);
                }

                $result = $this->runSql($sql, count($queueMsgs));

                if ($table == 'cash_entry') {
                    $times[] = microtime(true);
                }

                $executeCount += $result;
                if ($result == 0) {
                    $this->retry($queues);
                } else {
                    // 如果寫入現金明細成功則同步到歷史資料庫
                    // 如果餘額正負號異動則同步到負數一覽表
                    if ($table == 'cash_entry' && $this->payway == 'cash') {
                        $this->pushToHisQueue($queueMsgs);
                        $this->pushToNegativeQueue($queueMsgs);
                    }

                    // 如果寫入快開額度明細成功則同步到歷史資料庫
                    if ($table == 'cash_fake_entry' && $this->payway == 'cashfake') {
                        $this->pushToHisQueue($queueMsgs);
                    }

                    unset($queues[$table]);
                }
            }
        } catch (\Exception $e) {
            // 如果跑測試時不顯示錯誤訊息
            if ($this->container->getParameter('kernel.environment') != 'test') {
                $exMsg = array('error', $e->getCode(),$e->getMessage());
                \Doctrine\Common\Util\Debug::dump($exMsg);
            }
            $this->retry($queues);
        }

        if ($times && $this->container->getParameter('kernel.environment') != 'test') {
            // S: 組語法時間, E: 下語法時間
            printf(
                "%s S: %0.4f E: %0.4f\n",
                date('Ymd His'),
                ($times[1] - $times[0]) * 1000,
                ($times[2] - $times[1]) * 1000
            );
        }

        return $executeCount;
    }

    /**
     * 處理 UPDATE 資料, 如果執行失敗則推到 retry queue
     *
     * @param Array $queues
     * @return int 處理訊息的個數
     */
    protected function queryUpdate($queues)
    {
        $executeCount = 0;
        try {
            foreach ($queues as $key => $queue) {
                $sql = $this->setUpdateSql($queue);
                $result = $this->runSql($sql, 1);
                $executeCount += $result;
                if ($result == 0) {
                    $this->retry($queue);
                } else {
                    unset($queues[$key]);
                }
            }
        } catch (\Exception $e) {
            // 如果跑測試時不顯示錯誤訊息
            if ($this->container->getParameter('kernel.environment') != 'test') {
                $exMsg = array('error', $e->getCode(),$e->getMessage());
                \Doctrine\Common\Util\Debug::dump($exMsg);
            }
            $this->retry($queues);
        }
        return $executeCount;
    }

    /**
     * 如果是 insert 現金明細資料, 則新增一個 queue 做為同步到 history 資料庫用
     *
     * @param Array $queues
     */
    protected function pushToHisQueue($queues)
    {
        $redis = $this->getRedis();
        $queueName = $this->payway.'_entry_queue';

        foreach ($queues as $queue) {
            $queue['ERRCOUNT'] = 0;
            $redis->lpush($queueName, json_encode($queue));
        }
    }

    /**
     * 如果是正負餘額改變或負數餘額，都要推到負數佇列
     *
     * @param array $queues 佇列陣列
     */
    protected function pushToNegativeQueue($queues)
    {
        $redis = $this->getRedis();
        $queueName = 'cash_negative_queue';

        foreach ($queues as $queue) {
            $balance = $queue['balance'];
            $amount = $queue['amount'];
            $oriBalance = $balance - $amount;
            $stateChanged = ($balance >= 0 && $oriBalance < 0) || ($balance < 0 && $oriBalance >= 0);

            //正負餘額改變時會需要修改明細內容
            if ($stateChanged) {
                unset($queue['HEAD']);
                unset($queue['TABLE']);
                unset($queue['ERRCOUNT']);
                $redis->lpush($queueName, json_encode($queue));
            }

            //原本餘額即為負數，配合結算等功能更新負數餘額
            if (!$stateChanged && $balance < 0) {
                $negMsg = [
                    'cash_id' => $queue['cash_id'],
                    'user_id' => $queue['user_id'],
                    'currency' => $queue['currency'],
                    'balance' => $balance,
                    'cash_version' => $queue['cash_version']
                ];
                $redis->lpush($queueName, json_encode($negMsg));
            }
        }
    }
}
