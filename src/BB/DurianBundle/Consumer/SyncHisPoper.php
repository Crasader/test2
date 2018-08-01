<?php
namespace BB\DurianBundle\Consumer;

class SyncHisPoper extends Poper
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
     * 設定container、em，並以傳入的payway不同作存取對應的queue
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
        $this->getHisEntityManager();

        // infobright 寫入時需關閉 autocommit, 避免發生 insert 沒有寫入 binlog 情況
        if ($this->em->getConnection()->getDatabasePlatform()->getName() == 'mysql') {
            $this->em->getConnection()->executeUpdate('SET autocommit=0;');
        }

        // 先處理 retry queue, 避免一進入retry queue後又馬上處理
        $retryQueueName =  $this->getRetryQueueName();
        $executeCount = $this->processRetryMessage($retryQueueName);

        // 因為 SQL Lite 不支援 multi insert, 所以改跑單筆 insert
        $queueName = $this->getQueueName();
        if ($this->em->getConnection()->getDatabasePlatform()->getName() == 'sqlite') {
            $executeCount += $this->processRetryMessage($queueName);
        } else {
            $executeCount += $this->processMessage($queueName);
        }

        // 開啟 autocommit 開始寫入
        if ($this->em->getConnection()->getDatabasePlatform()->getName() == 'mysql') {
            $this->em->getConnection()->executeUpdate('SET autocommit=1;');
        }

        return $executeCount;
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
