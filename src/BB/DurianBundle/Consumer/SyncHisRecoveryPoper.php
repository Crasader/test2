<?php
namespace BB\DurianBundle\Consumer;

class SyncHisRecoveryPoper extends RecoveryPoper
{
    /**
     * 目前的DB連線設定
     *
     * @var \Doctrine\DBAL\Connection
     */
    protected $conn;

    /**
     * 目前的EntityManager
     *
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * 此poper專門處理先前執行失敗超過10次的Msg。
     *
     * @param Container $container
     * @param String $payway
     *
     * @return int 處理訊息的個數
     */
    public function runPop($container, $payway)
    {
        $this->container = $container;
        $redis = $this->getRedis();
        $this->payway = $payway;
        $queueName = $this->getQueueName();
        $this->getHisEntityManager();
        $executeCount = 1;

        // infobright 寫入時需關閉 autocommit, 避免發生 insert 沒有寫入 binlog 情況
        if ($this->em->getConnection()->getDatabasePlatform()->getName() == 'mysql') {
            $this->em->getConnection()->executeUpdate('SET autocommit=0;');
        }

        try {
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

                // 目前 SyncHisRecoverPoper 只支援 INSERT, 如果其他的直接丟入 failed queue
                if ($queueMsg['HEAD'] == 'INSERT') {
                    $queueMsgs = [];
                    $queueMsgs[$queueMsg['TABLE']][] = $queueMsg;
                    $result = $this->queryInsert($queueMsgs);
                } else {
                    $result = $this->pushToFailedQueue($queueMsg);
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
                    "[$server] [$now] $payway SyncHisRecoveryPoper failed: $message"
                );
            }
        }

        // 開啟 autocommit 開始寫入
        if ($this->em->getConnection()->getDatabasePlatform()->getName() == 'mysql') {
            $this->em->getConnection()->executeUpdate('SET autocommit=1;');
        }

        return $executeCount - 1;
    }

    /**
     * 印出執行時間，並且以DBAL執行SQL
     *
     * @param String $sql
     * @param Integer $rowCount
     * @return int 處理訊息的個數
     */
    protected function runSql($sql, $rowCount)
    {
        // 如果沒有語法則返回, 防止組字串失敗
        if (!$sql) {
            return 0;
        }

        if ($this->container->getParameter('kernel.environment') != 'test') {
            $now = new \DateTime();
            echo "Time::";
            echo $now->format('Y-m-d H:i:s');
            echo "::ACTION::FLUSH\n";
            echo $sql."\n";
        }

        $affectedRow = $this->em->getConnection()->executeUpdate($sql);

        // 如果要執行筆數與結果不同則為失敗
        if ($rowCount != $affectedRow) {
            $affectedRow = 0;
        }

        return $affectedRow;
    }

    /**
     * 回傳Default DB連線
     *
     * @return \Doctrine\DBAL\Connection
     */
    protected function getConnection()
    {
        if ($this->conn) {
            return $this->conn;
        }

        $this->conn = $this->container->get('doctrine.dbal.default_connection');

        return $this->conn;
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getHisEntityManager()
    {
        if ($this->em) {
            return $this->em;
        }

        $this->em = $this->getService('doctrine.orm.his_entity_manager');

        return $this->em;
    }
}
