<?php
namespace BB\DurianBundle\Consumer;

class SyncRecoveryPoper extends SyncPoper
{
    /**
     * 此poper專門處理先前執行失敗超過10次的Msg。若依然失敗的話則跳完錯誤後停止
     * 動作。
     *
     * @param Container $container
     * @param String $payway
     *
     * @return int 處理訊息的個數
     */
    public function runPop($container, $payway, $executeQueue = null)
    {
        $this->container = $container;
        $redis = $this->getRedis();
        $this->payway = $payway;
        $this->executeQueue = $executeQueue;
        $queueName = $this->getQueueName();
        $executeCount = 1;

        try{
            while ($redis->llen($queueName)) {
                if ($executeCount >= 2000) {
                    break;
                }

                $executeCount++;

                $queueMsg = null;
                $queueMsg = json_decode($redis->rpop($queueName), true);

                //檢查這次撈出來的queueMsg是否需要處理，null, 空陣列，空字串皆不處理
                if (empty($queueMsg)) {
                    $this->dumpDropMsg($queueMsg);
                    continue;
                }

                // 目前 SyncPoper 只支援 SYNCHRONIZE & CASHSYNCHRONIZE, 其他的直接丟入 failed queue
                if ($queueMsg['HEAD'] == 'SYNCHRONIZE' || $queueMsg['HEAD'] == 'CASHSYNCHRONIZE') {
                    $result = $this->synchronizeBalance($queueMsg);
                } else {
                    $result = $this->pushToFailedQueue($queueMsg);
                }

                // 如果有錯誤則停止動作
                if ($result == 0) {
                    break;
                }
            }
        } catch (\Exception $e) {
            // 若發生連線逾時,且無法從queue抓出訊息,則跳出例外訊息
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
                    "[$server] [$now] $payway SyncRecoveryPoper failed: $message"
                );

                throw $e;
            }
        }

        return $executeCount - 1;
    }

    /**
     * 取得 Queue 名稱
     *
     * @return String
     */
    protected function getQueueName()
    {
        $failedQueueName = $this->payway . '_sync_failed_queue' . $this->executeQueue;

        return $failedQueueName;
    }
}
