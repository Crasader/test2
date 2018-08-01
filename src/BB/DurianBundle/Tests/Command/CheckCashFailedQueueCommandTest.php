<?php
namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class CheckCashFailedQueueCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashEntryForCheckFailedQueueData',
        ];

        $this->loadFixtures($classnames, 'entry');
        $this->loadFixtures($classnames, 'his');

        $redis = $this->getContainer()->get('snc_redis.default');
        $redis->flushdb();
    }

    /**
     * 檢查已經新增
     */
    public function testMatchDatabase()
    {
        $redis = $this->getContainer()->get('snc_redis.default');

        $key = 'cash_failed_queue';
        $data = "{\"HEAD\":\"INSERT\",\"TABLE\":\"cash_entry\",\"ERRCOUNT\":10," .
            "\"id\":6366520305,\"cash_id\":6,\"user_id\":7,\"currency\":156," .
            "\"opcode\":\"20001\",\"at\":\"20140520143304\",\"created_at\":\"2014-05-20 14:33:04\"," .
            "\"amount\":\"100.0000000001\",\"memo\":\"\",\"balance\":100,\"ref_id\":\"0\"}";

        $redis->lpush($key, $data);

        $ret = $this->runCommand('durian:tools:check-cash-failed-queue');

        $this->assertContains('與現行資料庫比對相同', $ret);
        $this->assertContains('與歷史資料庫比對相同', $ret);
    }

    /**
     * 檢查現行與歷史資料庫沒有資料
     */
    public function testDatabaseWithoutData()
    {
        $redis = $this->getContainer()->get('snc_redis.default');

        $key = 'cash_failed_queue';
        $data = "{\"HEAD\":\"INSERT\",\"TABLE\":\"cash_entry\",\"ERRCOUNT\":10," .
            "\"id\":6366520304,\"cash_id\":12901424,\"user_id\":31646916,\"currency\":156," .
            "\"opcode\":\"40001\",\"at\":\"20140520143304\",\"created_at\":\"2014-05-20 14:33:04\"," .
            "\"amount\":\"0\",\"memo\":\"49000997\",\"balance\":245.4,\"ref_id\":\"3270739931\"}";
        $sql = "INSERT INTO cash_entry (id,cash_id,user_id,currency,opcode,at,created_at,amount," .
            "memo,balance,ref_id) VALUES ('6366520304','12901424','31646916','156','40001','20140520143304'," .
            "'2014-05-20 14:33:04','0','49000997','245.4','3270739931');";

        $redis->lpush($key, $data);

        $ret = $this->runCommand('durian:tools:check-cash-failed-queue');

        $this->assertContains('現行資料庫無資料', $ret);
        $this->assertContains('歷史資料庫無資料', $ret);
        $this->assertContains($sql, $ret);
    }

    /**
     * 檢查與現行或歷史資料庫比對不同
     */
    public function testDataDidnotMatch()
    {
        $redis = $this->getContainer()->get('snc_redis.default');

        $key = 'cash_failed_queue';
        $data = "{\"HEAD\":\"INSERT\",\"TABLE\":\"cash_entry\",\"ERRCOUNT\":10," .
            "\"id\":6366520305,\"cash_id\":12901424,\"user_id\":31646916,\"currency\":156," .
            "\"opcode\":\"40001\",\"at\":\"20140520143304\",\"created_at\":\"2014-05-20 14:33:04\"," .
            "\"amount\":\"0\",\"memo\":\"49000997\",\"balance\":245.4,\"ref_id\":\"3270739931\"}";

        $redis->lpush($key, $data);

        $ret = $this->runCommand('durian:tools:check-cash-failed-queue');

        $this->assertContains('與現行資料庫不同', $ret);
        $this->assertContains('與歷史資料庫不同', $ret);
    }
}
