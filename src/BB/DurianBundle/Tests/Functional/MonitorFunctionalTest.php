<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class MonitorFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBackgroundProcess',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashEntryDiffData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareUpdateCronData'
        );

        $this->loadFixtures($classnames);
        $this->loadFixtures([], 'share');

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $redis->flushdb();

        $redisKue = $this->getContainer()->get('snc_redis.kue');
        $redisKue->flushdb();
    }

    /**
     * 測試監控背景
     */
    public function testMonitorBackground()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $client->request('GET', '/api/monitor/background');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $background = $em->find(
            'BBDurianBundle:BackgroundProcess',
            array('name' => $output['ret'][1]['name'])
        );

        $this->assertEquals($background->getMemo(), $output['ret'][1]['memo']);
        $this->assertEquals($background->getBeginAt()->format('Y-m-d H:i:s'), $output['ret'][1]['beginAt']);
        $this->assertEquals($background->getEndAt()->format('Y-m-d H:i:s'), $output['ret'][1]['endAt']);
        $this->assertEquals($background->getNum(), $output['ret'][1]['bgNum']);
        $this->assertEquals($background->getMsgNum(), $output['ret'][1]['bgMsgNum']);
        $this->assertEquals('noExecuted', $output['ret'][1]['status']);

        $this->assertEquals('27', count($output['ret']));
    }

    /**
     * 測試執行時間為浮動的排程的狀態，若執行日期為當天，狀態則為正常
     */
    public function testMonitorBackgroundFloatingWorkStatus()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 修改背景執行時間為當天的03:15
        $checkCashError = $em->find('BBDurianBundle:BackgroundProcess', ['name' => 'check-cash-error']);
        $now = new \DateTime();
        $checkCashError->setBeginAt($now->setTime(3, 15));
        $em->flush();

        // 修改背景執行時間為兩天前
        $checkCashFakeError = $em->find('BBDurianBundle:BackgroundProcess', ['name' => 'check-cash-fake-error']);
        $checkCashFakeError->setBeginAt($now->sub(new \DateInterval('P2D')));
        $em->flush();

        $client->request('GET', '/api/monitor/background');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('check-cash-error', $output['ret'][2]['name']);
        $this->assertEquals('normal', $output['ret'][2]['status']);
        $this->assertEquals('check-cash-fake-error', $output['ret'][3]['name']);
        $this->assertEquals('noExecuted', $output['ret'][3]['status']);
        $this->assertEquals('check-card-error', $output['ret'][17]['name']);
        $this->assertEquals('noExecuted', $output['ret'][17]['status']);
    }

    /**
     * 測試執行 execute-rm-plan，若現在時間跟預計執行時間相差20分鐘以內，狀態則為正常
     */
    public function testMonitorBackgroundWithExecuteRmPlan()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 修改背景執行時間為40分鐘前
        $executeRmPlan = $em->find('BBDurianBundle:BackgroundProcess', ['name' => 'execute-rm-plan']);
        $executeRmPlan->setBeginAt(new \DateTime('-40 minutes'));
        $em->flush();

        $client->request('GET', '/api/monitor/background');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('execute-rm-plan', $output['ret'][16]['name']);
        $this->assertEquals('noExecuted', $output['ret'][16]['status']);


        // 修改背景執行時間為15分鐘前
        $executeRmPlan->setBeginAt(new \DateTime('-15 minutes'));
        $em->flush();

        $client->request('GET', '/api/monitor/background');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('execute-rm-plan', $output['ret'][16]['name']);
        $this->assertEquals('normal', $output['ret'][16]['status']);
    }

    /**
     * 測試執行 monitor-stat
     */
    public function testMonitorBackgroundWithMonitorStat()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 修改背景執行時間為排程以外時間
        $monitorStat = $em->find('BBDurianBundle:BackgroundProcess', ['name' => 'monitor-stat']);
        $time = (new \DateTime('-1 day'))->setTime(12, 10, 0);
        $monitorStat->setBeginAt($time);
        $em->flush();

        $client->request('GET', '/api/monitor/background');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('monitor-stat', $output['ret'][18]['name']);
        $this->assertEquals('noExecuted', $output['ret'][18]['status']);


        // 修改背景執行時間為排程內的時間
        $time = (new \DateTime())->setTime(12, 10, 0);
        $monitorStat->setBeginAt($time);
        $em->flush();

        $client->request('GET', '/api/monitor/background');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('monitor-stat', $output['ret'][18]['name']);
        $this->assertEquals('normal', $output['ret'][18]['status']);
    }

    /**
     * 測試監控資料庫資料數量
     */
    public function testMonitorDataBase()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/monitor/database');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals("cash_entry_diff", $output['ret'][0]['name']);
        $this->assertEquals(1, $output['ret'][0]['number']);
        $this->assertEquals('紀錄現行及歷史現金差異的差異資料', $output['ret'][0]['memo']);
        $this->assertEquals('abnormal', $output['ret'][0]['status']);

        $this->assertEquals(5, count($output['ret']));
    }

    /**
     * 測試監控queue
     */
    public function testMonitorQueue()
    {
        $client = $this->createClient();
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $queueName = 'cash_failed_queue';

        // 新增一筆 cash_trans 資料到 failed queue 裡
        $arrData = array(
            'HEAD' => 'INSERT',
            'TABLE' => 'cash_trans',
            'ERRCOUNT' => 0,
            'id' => 1002,
            'cash_id' => 1,
            'opcode' => 1001,
            'created_at' => '2012-01-01 12:00:00',
            'amount' => -100,
            'memo' => '',
            'ref_id' => 0,
            'checked' => 0,
            'checked_at' => null
        );

        $redis->lpush($queueName, json_encode($arrData));

        $client->request('GET', '/api/monitor/queue');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('queue', $output['ret'][0]['name']);
        $this->assertEquals(0, $output['ret'][0]['queueNum']);

        $this->assertEquals('failed_queue', $output['ret'][2]['name']);
        $this->assertEquals(1, $output['ret'][2]['queueNum']);
    }
}
