<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Consumer\Poper;
use BB\DurianBundle\Consumer\SyncHisPoper;
use BB\DurianBundle\Entity\CashEntry;
use BB\DurianBundle\Entity\CashFakeEntry;
use BB\DurianBundle\Entity\CreditPeriod;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\DomainConfig;
use BB\DurianBundle\Entity\Cash;
use BB\DurianBundle\Entity\CashFake;

class CheckFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCreditData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCreditPeriodData'
        );

        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashEntryData'
        ];

        $this->loadFixtures($classnames, 'entry');

        $hisClassnames = array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeEntryData'
        );

        $this->loadFixtures($hisClassnames, 'his');

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigData'
        ];

        $this->loadFixtures($classnames, 'share');

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadOutsideEntryData'
        ];

        $this->loadFixtures($classnames, 'outside');

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $redis->flushdb();

        $redis = $this->getContainer()->get('snc_redis.sequence');

        $redis->set('cash_seq', 1000);
        $redis->set('cashfake_seq', 1000);
    }

    /**
     * 測試回傳檢查現金明細總金額
     */
    public function testCashEntryAmount()
    {
        $em = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $client = $this->createClient();

        //修改歷史資料庫明細 refId
        $entry = $em->getRepository('BB\DurianBundle\Entity\CashEntry')
                      ->findOneBy(array('id' => 2));
        $entry->setRefId(12345);

        $entry = $em->getRepository('BB\DurianBundle\Entity\CashEntry')
                      ->findOneBy(array('id' => 3));
        $entry->setRefId(12345);
        $entry->setCreatedAt(new \DateTime('2012-01-01 17:00:00'));
        $entry->setAt(20120101170000);

        $entry = $em->getRepository('BB\DurianBundle\Entity\CashEntry')
                      ->findOneBy(array('id' => 5));
        $entry->setRefId(12344);

        $em->flush();

        //帶入refId opcode
        $parameters = array('ref_id' => '12344', 'opcode' => 1001);

        $client->request('GET', '/api/check/cash/total_amount', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1000, $output['ret'][0]['total_amount']);
        $this->assertEquals(12344, $output['ret'][0]['ref_id']);
    }

    /**
     * 測試回傳45天內檢查現金明細總金額
     */
    public function testCashEntryAmountIn45days()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $hisEm = $this->getContainer()->get('doctrine.orm.his_entity_manager');

        // 設定搜尋的時間範圍
        $now = new \DateTime('now');
        $end = clone $now;
        $start = $now->modify('-5 days')->format('Y-m-d H:i:s');
        $end = $end->format('Y-m-d H:i:s');
        $parameters = [
            'opcode' => 1001,
            'ref_id' => 654321,
            'start' => $start,
            'end' => $end
        ];

        // 建立mysql測試資料，建立時間為4天前
        $cash = $em->find('BBDurianBundle:Cash', 1);
        $dateStr = $now->modify('+1 days');

        $entry = new CashEntry($cash, 1001, 1000);
        $entry->setId(99);
        $entry->setRefId(654321);
        $entry->setCreatedAt($dateStr);
        $entry->setAt($dateStr->format('YmdHis'));

        $emEntry->persist($entry);
        $emEntry->flush();

        // 確認his沒有新資料
        $params = ['id' => 99, 'at' => $dateStr->format('YmdHis')];
        $hisEntry = $hisEm->find('BBDurianBundle:CashEntry', $params);
        $this->assertNull($hisEntry);

        $client->request('GET', '/api/check/cash/total_amount', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['cash_id']);
        $this->assertEquals(654321, $output['ret'][0]['ref_id']);
        $this->assertEquals(1, count($output['ret']));
    }

    /**
     * 測試回傳檢查快開額度明細總金額
     */
    public function testCashFakeEntryAmount()
    {
        $client = $this->createClient();

        $parameters = array('ref_id' => array(12346),
                            'opcode' => array(1001, 1003));

        $client->request('GET', '/api/check/cash_fake/total_amount', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(array(), $output['ret']);
    }

    /**
     * 測試回傳45天內檢查快開額度明細總金額
     */
    public function testCashFakeEntryAmountIn45days()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $hisEm = $this->getContainer()->get('doctrine.orm.his_entity_manager');

        // 設定搜尋的時間範圍
        $now = new \DateTime('now');
        $end = clone $now;
        $start = $now->modify('-5 days')->format('Y-m-d H:i:s');
        $end = $end->format('Y-m-d H:i:s');
        $parameters = [
            'opcode' => 1001,
            'ref_id' => 654321,
            'start' => $start,
            'end' => $end
        ];

        // 建立mysql測試資料，建立時間為4天前
        $cash = $em->find('BBDurianBundle:CashFake', 1);
        $dateStr = $now->modify('+1 days');

        $entry = new CashFakeEntry($cash, 1001, 100);
        $entry->setId(99);
        $entry->setRefId(654321);
        $entry->setCreatedAt($dateStr);
        $entry->setAt($dateStr->format('YmdHis'));

        $em->persist($entry);
        $em->flush();

        // 確認his沒有新資料
        $params = ['id' => 99, 'at' => $dateStr->format('YmdHis')];
        $hisEntry = $hisEm->find('BBDurianBundle:CashFakeEntry', $params);
        $this->assertNull($hisEntry);

        $client->request('GET', '/api/check/cash_fake/total_amount', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['cash_fake_id']);
        $this->assertEquals(654321, $output['ret'][0]['ref_id']);
        $this->assertEquals(1, count($output['ret']));
    }

    /**
     * 測試回傳檢查外接額度明細總金額
     */
    public function testOutsideEntryAmount()
    {
        $em = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $client = $this->createClient();

        $parameters = [
            'ref_id' => '1',
            'opcode' => [1001],
            'group' => 1
        ];

        $client->request('GET', '/api/check/outside/total_amount', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(20, $output['ret'][0]['total_amount']);
        $this->assertEquals(1, $output['ret'][0]['ref_id']);
    }

    /**
     * 檢查信用額度紀錄api帶入不存在的廳主id
     */
    public function testCreditPeriodAmountButNotADomain()
    {
        $client = $this->createClient();

        $parameters = [
            'domain'    => 123456,
            'group_num' => '3',
            'period_at' => '2011-07-20 00:00:00'
        ];
        $client->request('GET', '/api/check/credit/period_amount', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150450023, $output['code']);
        $this->assertEquals('Not a domain', $output['msg']);
    }

    /**
     * 檢查信用額度紀錄區間內的累計交易金額資料
     */
    public function testCreditPeriodAmount()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $credit = $em->find('BBDurianBundle:Credit', 7);
        $date   = new \DateTime('2011-07-20');
        $period = new CreditPeriod($credit, $date);
        $period->addAmount(99);

        $em->persist($period);
        $em->flush();

        $parameters = [
            'domain'       => 2,
            'group_num'    => '1',
            'period_at'    => '2011-07-20 00:00:00',
            'first_result' => 0,
            'max_results'  => 1
        ];
        $client->request('GET', '/api/check/credit/period_amount', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret'][0]['user_id']);
        $this->assertEquals(700, $output['ret'][0]['amount']);
        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(1, $output['pagination']['max_results']);
        $this->assertEquals(1, $output['pagination']['total']);
        $this->assertFalse(isset($output['ret'][1]));
    }

    /**
     * 檢查信用額度紀錄區間內的累計交易金額資料(無資料的情況)
     */
    public function testCreditPeriodAmountWithoutData()
    {
        $client = $this->createClient();

        $parameters = [
            'domain'    => 3,
            'group_num' => '3',
            'period_at' => '2011-07-20 00:00:00'
        ];
        $client->request('GET', '/api/check/credit/period_amount', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals([], $output['ret']);
        $this->assertNull($output['pagination']['first_result']);
        $this->assertNull($output['pagination']['max_results']);
        $this->assertEquals(0, $output['pagination']['total']);
    }

    /**
     * 測試檢查現金明細筆數
     */
    public function testCashCountEntries()
    {
        $client = $this->createClient();

        //新增三筆下注
        for ($i = 0; $i < 3; ++$i) {
            $parameters = array(
                'pay_way' => 'cash',
                'amount'  => -10,
                'opcode'  => 60000,
                'ref_id'  => 100 + $i,
            );

            $client->request('POST', "/api/user/7/order", $parameters);
        }

        //新增一筆派彩
        $parameters = array(
            'pay_way' => 'cash',
            'amount'  => 50,
            'opcode'  => 60001,
            'ref_id'  => 100,
        );

        $client->request('POST', '/api/user/7/order', $parameters);

        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);
        $this->runCommand('durian:sync-his-poper');

        $end = new \DateTime('now');

        //帶入refId opcode 時間
        $parameters = array(
            'ref_id' => 102,
            'opcode' => 60000,
            'start'  => '2013-01-01T12:00:00+0800',
            'end'    => $end->format(\DateTime::ISO8601),
        );

        $client->request('GET', '/api/check/cash/count_entries', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(102, $output['ret'][0]['ref_id']);
        $this->assertEquals(1, $output['ret'][0]['entry_total']);

        //測試帶入 refId陣列 opcode陣列 時間
        $parameters = array(
            'ref_id' => array(100, 101, 102),
            'opcode' => array(60000, 60001),
            'start'  => '2013-01-01T12:00:00+0800',
            'end'    => $end->format(\DateTime::ISO8601),
        );

        $client->request('GET', '/api/check/cash/count_entries', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, count($output['ret']));
        $this->assertEquals(101, $output['ret'][0]['ref_id']);
        $this->assertEquals(1, $output['ret'][0]['entry_total']);
        $this->assertEquals(102, $output['ret'][1]['ref_id']);
        $this->assertEquals(1, $output['ret'][1]['entry_total']);

        //測試帶入不符合條件
        $parameters = array(
            'ref_id' => array(123, 124),
            'opcode' => array(60000, 60001),
            'start'  => '2013-01-01T12:00:00+0800',
            'end'    => $end->format(\DateTime::ISO8601),
        );

        $client->request('GET', '/api/check/cash/count_entries', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(array(), $output['ret']);
    }

    /**
     * 測試檢查45天內現金明細筆數
     */
    public function testCashCountEntriesIn45days()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $hisEm = $this->getContainer()->get('doctrine.orm.his_entity_manager');

        // 設定搜尋的時間範圍
        $now = new \DateTime('now');
        $end = clone $now;
        $start = $now->modify('-5 days')->format('Y-m-d H:i:s');
        $end = $end->format('Y-m-d H:i:s');
        $parameters = [
            'opcode' => 1001,
            'ref_id' => 654321,
            'start' => $start,
            'end' => $end
        ];

        // 建立mysql測試資料，建立時間為4天前
        $cash = $em->find('BBDurianBundle:Cash', 1);
        $dateStr = $now->modify('+1 days');

        $entry = new CashEntry($cash, 1001, 1000);
        $entry->setId(99);
        $entry->setRefId(654321);
        $entry->setCreatedAt($dateStr);
        $entry->setAt($dateStr->format('YmdHis'));

        $emEntry->persist($entry);
        $emEntry->flush();

        // 確認his沒有新資料
        $params = ['id' => 99, 'at' => $dateStr->format('YmdHis')];
        $hisEntry = $hisEm->find('BBDurianBundle:CashEntry', $params);
        $this->assertNull($hisEntry);

        $client->request('GET', '/api/check/cash/count_entries', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(99, $output['ret'][0]['id']);
        $this->assertEquals(654321, $output['ret'][0]['ref_id']);
        $this->assertEquals(1, count($output['ret']));
    }

    /**
     * 測試檢查快開額度明細筆數
     */
    public function testCashFakeCountEntries()
    {
        $client = $this->createClient();

        //新增三筆下注
        for ($i = 0; $i < 3; ++$i) {
            $parameters = array(
                'pay_way' => 'cashfake',
                'amount'  => -10,
                'opcode'  => 60000,
                'ref_id'  => 100 + $i,
            );

            $client->request('POST', "/api/user/7/order", $parameters);
        }

        //新增一筆派彩
        $parameters = array(
            'pay_way' => 'cashfake',
            'amount'  => 50,
            'opcode'  => 60001,
            'ref_id'  => 100,
        );

        $client->request('POST', '/api/user/7/order', $parameters);

        //跑背景程式讓queue被消化
        $params = [
            '--entry' => true,
            '--balance' => true,
            '--history' => true
        ];
        $this->runCommand('durian:sync-cash-fake', $params);

        $end = new \DateTime('now');

        //測試帶入 refId陣列 opcode陣列 時間
        $parameters = array(
            'ref_id' => array(100, 101, 102),
            'opcode' => array(60000, 60001),
            'start'  => '2013-01-01T12:00:00+0800',
            'end'    => $end->format(\DateTime::ISO8601),
        );

        $client->request('GET', '/api/check/cash_fake/count_entries', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, count($output['ret']));
        $this->assertEquals(101, $output['ret'][0]['ref_id']);
        $this->assertEquals(1, $output['ret'][0]['entry_total']);
        $this->assertEquals(102, $output['ret'][1]['ref_id']);
        $this->assertEquals(1, $output['ret'][1]['entry_total']);
    }

    /**
     * 測試檢查45天內快開額度明細筆數
     */
    public function testCashFakeCountEntriesIn45days()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $hisEm = $this->getContainer()->get('doctrine.orm.his_entity_manager');

        // 設定搜尋的時間範圍
        $now = new \DateTime('now');
        $end = clone $now;
        $start = $now->modify('-5 days')->format('Y-m-d H:i:s');
        $end = $end->format('Y-m-d H:i:s');
        $parameters = [
            'opcode' => 1001,
            'ref_id' => 654321,
            'start' => $start,
            'end' => $end
        ];

        // 建立mysql測試資料，建立時間為4天前
        $cash = $em->find('BBDurianBundle:CashFake', 1);
        $dateStr = $now->modify('+1 days');

        $entry = new CashFakeEntry($cash, 1001, 1000);
        $entry->setId(99);
        $entry->setRefId(654321);
        $entry->setCreatedAt($dateStr);
        $entry->setAt($dateStr->format('YmdHis'));

        $em->persist($entry);
        $em->flush();

        // 確認his沒有新資料
        $params = ['id' => 99, 'at' => $dateStr->format('YmdHis')];
        $hisEntry = $hisEm->find('BBDurianBundle:CashFakeEntry', $params);
        $this->assertNull($hisEntry);

        $client->request('GET', '/api/check/cash_fake/count_entries', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(99, $output['ret'][0]['id']);
        $this->assertEquals(654321, $output['ret'][0]['ref_id']);
        $this->assertEquals(1, count($output['ret']));
    }

    /**
     * 測試檢查外接額度明細筆數
     */
    public function testOutsideCountEntries()
    {
        $client = $this->createClient();

        $parameters = [
            'ref_id' => [1, 2],
            'opcode' => [1001],
            'start'  => '2013-01-01T12:00:00+0800',
            'end'    => '2017-06-01T12:00:00+0800'
        ];

        $client->request('GET', '/api/check/outside/count_entries', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, count($output['ret']));
        $this->assertEquals(2, $output['ret'][0]['ref_id']);
        $this->assertEquals(1, $output['ret'][0]['entry_total']);
    }

    /**
     * 測試以ref_id取得現金明細總和
     */
    public function testCashTotalAmountByRefId()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');

        $cash = $em->find('BBDurianBundle:Cash', 1);

        $entry = new CashEntry($cash, 40000, -1000);
        $entry->setId(11);
        $entry->setRefId(123);
        $emEntry->persist($entry);
        $time = new \DateTime('2013-11-14 13:50:00');
        $entry->setCreatedAt($time);
        $entry->setAt(20131114135000);

        $entry = new CashEntry($cash, 40001, 1500);
        $entry->setId(12);
        $entry->setRefId(123);
        $emEntry->persist($entry);
        $entry->setCreatedAt($time);
        $entry->setAt(20131114135000);

        $cash2 = $em->find('BBDurianBundle:Cash', 2);

        $entry1 = new CashEntry($cash2, 40000, -1000);
        $entry1->setId(13);
        $entry1->setRefId(456);
        $emEntry->persist($entry1);
        $entry1->setCreatedAt($time);
        $entry1->setAt(20131114135000);

        $entry1 = new CashEntry($cash2, 40001, 2000);
        $entry1->setId(14);
        $entry1->setRefId(456);
        $emEntry->persist($entry1);
        $entry1->setCreatedAt($time);
        $entry1->setAt(20131114135000);

        $emEntry->flush();

        $start = new \DateTime('2013-11-14');
        $end = new \DateTime('2013-11-15');

        $parameter = array(
            'opcode'       => array(40000, 40001),
            'ref_id_begin' => 123,
            'ref_id_end'   => 456,
            'start'        => $start->format(\DateTime::ISO8601),
            'end'          => $end->format(\DateTime::ISO8601),
            'first_result' => 0,
            'max_results'  => 5
        );

        $client->request('GET', '/api/check/cash/total_amount_by_ref_id', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(123, $output['ret'][0]['ref_id']);
        $this->assertEquals(500, $output['ret'][0]['amount']);
        $this->assertEquals(40001, $output['ret'][0]['opcode']);
        $this->assertEquals('', $output['ret'][0]['memo']);
        $this->assertEquals(2, $output['ret'][0]['user_id']);

        $this->assertEquals(456, $output['ret'][1]['ref_id']);
        $this->assertEquals(1000, $output['ret'][1]['amount']);
        $this->assertEquals(40001, $output['ret'][1]['opcode']);
        $this->assertEquals('', $output['ret'][1]['memo']);
        $this->assertEquals(3, $output['ret'][1]['user_id']);

        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(5, $output['pagination']['max_results']);
        $this->assertEquals(2, $output['pagination']['total']);
    }

    /**
     * 測試以ref_id取得假現金明細總和
     */
    public function testCashFakeTotalAmountByRefId()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $cashFake = $em->find('BBDurianBundle:CashFake', 1);

        $entry = new CashFakeEntry($cashFake, 40000, -500);
        $entry->setId(6);
        $entry->setRefId(123);
        $em->persist($entry);
        $time = new \DateTime('2013-11-14 13:50:00');
        $entry->setCreatedAt($time);
        $entry->setAt(20131114135000);

        $entry = new CashFakeEntry($cashFake, 40001, 500);
        $entry->setId(7);
        $entry->setRefId(123);
        $em->persist($entry);
        $entry->setCreatedAt($time);
        $entry->setAt(20131114135000);

        $cashFake2 = $em->find('BBDurianBundle:CashFake', 2);

        $entry1 = new CashFakeEntry($cashFake2, 40000, -500);
        $entry1->setId(8);
        $entry1->setRefId(456);
        $em->persist($entry1);
        $entry1->setCreatedAt($time);
        $entry1->setAt(20131114135000);

        $entry1 = new CashFakeEntry($cashFake2, 40001, 1000);
        $entry1->setId(9);
        $entry1->setRefId(456);
        $em->persist($entry1);
        $entry1->setCreatedAt($time);
        $entry1->setAt(20131114135000);

        $em->flush();

        $start = new \DateTime('2013-11-14');
        $end = new \DateTime('2013-11-15');

        $parameter = array(
            'opcode'       => array(40000, 40001),
            'ref_id_begin' => 123,
            'ref_id_end'   => 456,
            'start'        => $start->format(\DateTime::ISO8601),
            'end'          => $end->format(\DateTime::ISO8601),
            'first_result' => 0,
            'max_results'  => 5
        );

        $client->request('GET', '/api/check/cash_fake/total_amount_by_ref_id', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(123, $output['ret'][0]['ref_id']);
        $this->assertEquals(0, $output['ret'][0]['amount']);
        $this->assertEquals(40001, $output['ret'][0]['opcode']);
        $this->assertEquals('', $output['ret'][0]['memo']);
        $this->assertEquals(7, $output['ret'][0]['user_id']);

        $this->assertEquals(456, $output['ret'][1]['ref_id']);
        $this->assertEquals(500, $output['ret'][1]['amount']);
        $this->assertEquals(40001, $output['ret'][1]['opcode']);
        $this->assertEquals('', $output['ret'][1]['memo']);
        $this->assertEquals(8, $output['ret'][1]['user_id']);

        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(5, $output['pagination']['max_results']);
        $this->assertEquals(2, $output['pagination']['total']);
    }

    /**
     * 測試以ref_id取得外接額度明細總和
     */
    public function testOutsideTotalAmountByRefId()
    {
        $client = $this->createClient();

        $parameter = [
            'opcode'       => [1001],
            'ref_id_begin' => 1,
            'ref_id_end'   => 2,
            'start'        => '2013-01-01T12:00:00+0800',
            'end'          => '2017-06-01T12:00:00+0800',
            'first_result' => 0,
            'max_results'  => 5
        ];

        $client->request('GET', '/api/check/outside/total_amount_by_ref_id', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(1, $output['ret'][0]['ref_id']);
        $this->assertEquals(20, $output['ret'][0]['amount']);
        $this->assertEquals(1001, $output['ret'][0]['opcode']);
        $this->assertEquals('test-memo', $output['ret'][0]['memo']);
        $this->assertEquals(1, $output['ret'][0]['user_id']);

        $this->assertEquals(2, $output['ret'][1]['ref_id']);
        $this->assertEquals(10, $output['ret'][1]['amount']);
        $this->assertEquals(1001, $output['ret'][1]['opcode']);
        $this->assertEquals('test-memo', $output['ret'][1]['memo']);
        $this->assertEquals(1, $output['ret'][1]['user_id']);

        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(5, $output['pagination']['max_results']);
        $this->assertEquals(2, $output['pagination']['total']);
    }

    /**
     * 測試以ref_id取得現金明細
     */
    public function testCashEntry()
    {
        $client = $this->createClient();

        //測試帶入opcode、ref_id範圍
        $parameter = [
            'opcode'       => [1001],
            'ref_id_begin' => 10000000,
            'ref_id_end'   => 6000000000
        ];
        $client->request('GET', '/api/check/cash/entry', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 以ref_id做排序
        usort($output['ret'], function($a, $b) {
            return $a['ref_id'] - $b['ref_id'];
        });

        $this->assertEquals(11509530, $output['ret'][0]['ref_id']);
        $this->assertEquals(100, $output['ret'][0]['amount']);
        $this->assertEquals(1001, $output['ret'][0]['opcode']);
        $this->assertEquals(2, $output['ret'][0]['user_id']);
        $this->assertEmpty($output['ret'][0]['memo']);

        $this->assertEquals(238030097, $output['ret'][1]['ref_id']);
        $this->assertEquals(1000, $output['ret'][1]['amount']);
        $this->assertEquals(1001, $output['ret'][1]['opcode']);
        $this->assertEquals(2, $output['ret'][1]['user_id']);
        $this->assertEmpty($output['ret'][1]['memo']);

        $this->assertEquals(1899192299, $output['ret'][2]['ref_id']);
        $this->assertEquals(1000, $output['ret'][2]['amount']);
        $this->assertEquals(1001, $output['ret'][2]['opcode']);
        $this->assertEquals(3, $output['ret'][2]['user_id']);
        $this->assertEmpty($output['ret'][2]['memo']);

        $this->assertEmpty($output['pagination']['first_result']);
        $this->assertEmpty($output['pagination']['max_results']);
        $this->assertEquals(3, $output['pagination']['total']);

        //測試帶入opcode、ref_id範圍、時間區間
        $parameter = [
            'opcode'       => [1001],
            'ref_id_begin' => 10000000,
            'ref_id_end'   => 6000000000,
            'start'        => '2012-01-01T00:00:00+0800',
            'end'          => '2012-01-02T00:00:00+0800'
        ];
        $client->request('GET', '/api/check/cash/entry', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(11509530, $output['ret'][0]['ref_id']);
        $this->assertEquals(100, $output['ret'][0]['amount']);
        $this->assertEquals(1001, $output['ret'][0]['opcode']);
        $this->assertEquals(2, $output['ret'][0]['user_id']);
        $this->assertEmpty($output['ret'][0]['memo']);

        $this->assertEquals(1899192299, $output['ret'][1]['ref_id']);
        $this->assertEquals(1000, $output['ret'][1]['amount']);
        $this->assertEquals(1001, $output['ret'][1]['opcode']);
        $this->assertEquals(3, $output['ret'][1]['user_id']);
        $this->assertEmpty($output['ret'][1]['memo']);

        $this->assertEmpty($output['pagination']['first_result']);
        $this->assertEmpty($output['pagination']['max_results']);
        $this->assertEquals(2, $output['pagination']['total']);

        //測試帶入opcode、ref_id範圍、時間區間、回傳筆數
        $parameter = [
            'opcode'       => [1001],
            'ref_id_begin' => 10000000,
            'ref_id_end'   => 6000000000,
            'start'        => '2012-01-01T00:00:00+0800',
            'end'          => '2012-01-02T00:00:00+0800',
            'first_result' => 0,
            'max_results'  => 1
        ];
        $client->request('GET', '/api/check/cash/entry', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(11509530, $output['ret'][0]['ref_id']);
        $this->assertEquals(100, $output['ret'][0]['amount']);
        $this->assertEquals(1001, $output['ret'][0]['opcode']);
        $this->assertEquals(2, $output['ret'][0]['user_id']);
        $this->assertEmpty($output['ret'][0]['memo']);

        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(1, $output['pagination']['max_results']);
        $this->assertEquals(2, $output['pagination']['total']);
    }

    /**
     * 測試以ref_id取得假現金明細
     */
    public function testCashFakeEntry()
    {
        $client = $this->createClient();

        //測試帶入opcode、ref_id範圍、回傳筆數
        $parameter = [
            'opcode'       => [1001],
            'ref_id_begin' => 0,
            'ref_id_end'   => 5,
            'first_result' => 0,
            'max_results'  => 2
        ];
        $client->request('GET', '/api/check/cash_fake/entry', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(0, $output['ret'][0]['ref_id']);
        $this->assertEquals(100, $output['ret'][0]['amount']);
        $this->assertEquals(1001, $output['ret'][0]['opcode']);
        $this->assertEquals(8, $output['ret'][0]['user_id']);
        $this->assertEmpty($output['ret'][0]['memo']);

        $this->assertEquals(1, $output['ret'][1]['ref_id']);
        $this->assertEquals(500, $output['ret'][1]['amount']);
        $this->assertEquals(1001, $output['ret'][1]['opcode']);
        $this->assertEquals(8, $output['ret'][1]['user_id']);
        $this->assertEmpty($output['ret'][1]['memo']);

        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(2, $output['pagination']['max_results']);
        $this->assertEquals(3, $output['pagination']['total']);
    }

    /**
     * 測試以ref_id取得外接額度明細
     */
    public function testOutsideEntry()
    {
        $client = $this->createClient();

        $parameter = [
            'opcode'       => [1001],
            'ref_id_begin' => 0,
            'ref_id_end'   => 5,
            'start'        => '2013-01-01T12:00:00+0800',
            'end'          => '2017-06-01T12:00:00+0800',
            'first_result' => 0,
            'max_results'  => 2
        ];
        $client->request('GET', '/api/check/outside/entry', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(1, $output['ret'][0]['ref_id']);
        $this->assertEquals(10, $output['ret'][0]['amount']);
        $this->assertEquals(1001, $output['ret'][0]['opcode']);
        $this->assertEquals(1, $output['ret'][0]['user_id']);
        $this->assertEquals('test-memo', $output['ret'][0]['memo']);

        $this->assertEquals(1, $output['ret'][1]['ref_id']);
        $this->assertEquals(10, $output['ret'][1]['amount']);
        $this->assertEquals(1001, $output['ret'][1]['opcode']);
        $this->assertEquals(1, $output['ret'][1]['user_id']);
        $this->assertEquals('test-memo', $output['ret'][1]['memo']);

        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(2, $output['pagination']['max_results']);
        $this->assertEquals(3, $output['pagination']['total']);
    }

    /**
     * 測試取得時間區間內現金明細的ref_id
     */
    public function testCashEntryRefId()
    {
        $client = $this->createClient();
        $start = new \DateTime('2012-01-01');
        $end = new \DateTime('2013-01-01 12:00:00');

        $parameter = array(
            'opcode' => array('1001', '1002'),
            'start' => $start->format(\DateTime::ISO8601),
            'end' => $end->format(\DateTime::ISO8601),
            'first_result' => 0,
            'max_results' => 10
        );

        $client->request('GET', '/api/check/cash/entry/ref_id', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(11509530, $output['ret'][0]);
        $this->assertEquals(5150840319, $output['ret'][1]);
        $this->assertEquals(1899192299, $output['ret'][2]);
        $this->assertEmpty($output['ret'][3]);
        $this->assertEquals(238030097, $output['ret'][9]);
        $this->assertEquals(10, count($output['ret']));

        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(10, $output['pagination']['max_results']);
        $this->assertEquals(10, $output['pagination']['total']);
    }

    /**
     * 測試取得45天內現金明細的ref_id的例外情況
     */
    public function testCashEntryRefIdExceptionIn45days()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $hisEm = $this->getContainer()->get('doctrine.orm.his_entity_manager');

        // 設定搜尋的時間範圍
        $now = new \DateTime('now');
        $end = clone $now;
        $start = $now->modify('-5 days')->format('Y-m-d H:i:s');
        $end = $end->format('Y-m-d H:i:s');
        $parameters = [
            'opcode' => [1001],
            'ref_id' => 654321,
            'start' => $start,
            'end' => $end
        ];

        // 建立mysql測試資料，建立時間為4天前
        $cash = $em->find('BBDurianBundle:Cash', 1);
        $dateStr = $now->modify('+1 days');

        $entry = new CashEntry($cash, 1001, 100);
        $entry->setId(99);
        $entry->setRefId(654321);
        $entry->setCreatedAt($dateStr);
        $entry->setAt($dateStr->format('YmdHis'));

        $emEntry->persist($entry);
        $emEntry->flush();

        // 確認his沒有新資料
        $params = ['id' => 99, 'at' => $dateStr->format('YmdHis')];
        $hisEntry = $hisEm->find('BBDurianBundle:CashEntry', $params);
        $this->assertNull($hisEntry);

        $client->request('GET', '/api/check/cash/entry/ref_id', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['pagination']['total']);
        $this->assertEquals(654321, $output['ret'][0]);
    }

    /**
     * 測試取得時間區間內假現金明細的ref_id
     */
    public function testCashFakeEntryRefId()
    {
        $client = $this->createClient();
        $start = new \DateTime('2012-01-01');
        $end = new \DateTime('2013-01-01 12:00:00');

        $parameter = array(
            'opcode' => array('1001', '1003', '1006'),
            'start' => $start->format(\DateTime::ISO8601),
            'end' => $end->format(\DateTime::ISO8601),
            'first_result' => 0,
            'max_results' => 5
        );

        $client->request('GET', '/api/check/cash_fake/entry/ref_id', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(5150840307, $output['ret'][0]);
        $this->assertEquals(5150840544, $output['ret'][1]);
        $this->assertEquals(1899192866, $output['ret'][2]);
        $this->assertEmpty($output['ret'][3]);
        $this->assertEquals(4, count($output['ret']));

        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(5, $output['pagination']['max_results']);
        $this->assertEquals(4, $output['pagination']['total']);
    }

    /**
     * 測試取得45天內假現金明細的ref_id的例外情況
     */
    public function testCashFakeEntryRefIdExceptionIn45days()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $hisEm = $this->getContainer()->get('doctrine.orm.his_entity_manager');

        // 設定搜尋的時間範圍
        $now = new \DateTime('now');
        $end = clone $now;
        $start = $now->modify('-5 days')->format('Y-m-d H:i:s');
        $end = $end->format('Y-m-d H:i:s');
        $parameters = [
            'opcode' => [1001],
            'ref_id' => 654321,
            'start' => $start,
            'end' => $end
        ];

        // 建立mysql測試資料，建立時間為4天前
        $cash = $em->find('BBDurianBundle:CashFake', 1);
        $dateStr = $now->modify('+1 days');

        $entry = new CashFakeEntry($cash, 1001, 1000);
        $entry->setId(99);
        $entry->setRefId(654321);
        $entry->setCreatedAt($dateStr);
        $entry->setAt($dateStr->format('YmdHis'));

        $em->persist($entry);
        $em->flush();

        // 確認his沒有新資料
        $params = ['id' => 99, 'at' => $dateStr->format('YmdHis')];
        $hisEntry = $hisEm->find('BBDurianBundle:CashFakeEntry', $params);
        $this->assertNull($hisEntry);

        $client->request('GET', '/api/check/cash_fake/entry/ref_id', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['pagination']['total']);
        $this->assertEquals(654321, $output['ret'][0]);
    }

    /**
     * 測試取得時間區間內外接額度明細的ref_id
     */
    public function testOutsideEntryRefId()
    {
        $client = $this->createClient();

        $parameter = [
            'opcode' => [1001],
            'start' => '2013-01-01T12:00:00+0800',
            'end' => '2017-07-01T12:00:00+0800',
            'first_result' => 0,
            'max_results' => 10
        ];

        $client->request('GET', '/api/check/outside/entry/ref_id', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]);
        $this->assertEquals(2, $output['ret'][1]);
        $this->assertEquals(1, $output['ret'][2]);

        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(10, $output['pagination']['max_results']);
        $this->assertEquals(3, $output['pagination']['total']);
    }

    /**
     * 測試取得時間區間內的現金明細
     */
    public function testGetCashEntryByTime()
    {
        $client = $this->createClient();
        $start = new \DateTime('2012-01-01');
        $end = new \DateTime('2013-01-02');

        $parameter = [
            'opcode' => ['1001', '1002'],
            'start' => $start->format(\DateTime::ISO8601),
            'end' => $end->format(\DateTime::ISO8601),
            'first_result' => 0,
            'max_results' => 2
        ];

        $client->request('GET', '/api/check/cash/entry_by_time', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(11509530, $output['ret'][0]['ref_id']);
        $this->assertEquals(100, $output['ret'][0]['amount']);
        $this->assertEquals(1001, $output['ret'][0]['opcode']);
        $this->assertEquals(2, $output['ret'][0]['user_id']);

        $this->assertEquals(5150840319, $output['ret'][1]['ref_id']);
        $this->assertEquals(-80, $output['ret'][1]['amount']);
        $this->assertEquals(1002, $output['ret'][1]['opcode']);
        $this->assertEquals(2, $output['ret'][1]['user_id']);
    }

    /**
     * 測試取得時間區間內的假現金明細
     */
    public function testGetCashFakeEntryByTime()
    {
        $client = $this->createClient();
        $start = new \DateTime('2012-01-01');
        $end = new \DateTime('2013-01-02');

        $parameter = [
            'opcode' => ['1001', '1002'],
            'start' => $start->format(\DateTime::ISO8601),
            'end' => $end->format(\DateTime::ISO8601),
            'first_result' => 0,
            'max_results' => 2
        ];

        $client->request('GET', '/api/check/cash_fake/entry_by_time', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(0, $output['ret'][0]['ref_id']);
        $this->assertEquals(100, $output['ret'][0]['amount']);
        $this->assertEquals(1001, $output['ret'][0]['opcode']);
        $this->assertEquals(8, $output['ret'][0]['user_id']);

        $this->assertEquals(0, $output['ret'][1]['ref_id']);
        $this->assertEquals(80, $output['ret'][1]['amount']);
        $this->assertEquals(1002, $output['ret'][1]['opcode']);
        $this->assertEquals(8, $output['ret'][1]['user_id']);
    }

    /**
     * 測試取得時間區間內的外接額度明細
     */
    public function testGetOutsideEntryByTime()
    {
        $client = $this->createClient();

        $parameter = [
            'opcode' => [1001],
            'start' => '2013-01-01T12:00:00+0800',
            'end' => '2017-06-01T12:00:00+0800',
            'first_result' => 0,
            'max_results' => 2
        ];

        $client->request('GET', '/api/check/outside/entry_by_time', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['ref_id']);
        $this->assertEquals(10, $output['ret'][0]['amount']);
        $this->assertEquals(1001, $output['ret'][0]['opcode']);
        $this->assertEquals(1, $output['ret'][0]['user_id']);

        $this->assertEquals(2, $output['ret'][1]['ref_id']);
        $this->assertEquals(10, $output['ret'][1]['amount']);
        $this->assertEquals(1001, $output['ret'][1]['opcode']);
        $this->assertEquals(1, $output['ret'][1]['user_id']);

        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(2, $output['pagination']['max_results']);
        $this->assertEquals(3, $output['pagination']['total']);
    }

    /**
     * 測試根據廳取得假現金明細
     */
    public function testGetCashFakeEntryByDomain()
    {
        $client = $this->createClient();
        $start = new \DateTime('2012-01-01');
        $end = new \DateTime('2013-01-02');

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $user = new User();
        $user->setId(52);
        $user->setUsername('vtester52');
        $user->setAlias('vtester52');
        $user->setPassword('123456');
        $user->setDomain(52);
        $user->setRole(7);
        $em->persist($user);
        $em->flush();

        $config = new DomainConfig($user, 'vtester52', 'test');
        $emShare->persist($config);
        $emShare->flush();

        $fake = new CashFake($user, 156);
        $em->persist($fake);
        $em->flush();

        $entry = new CashFakeEntry($fake, 1010, 1000);
        $entry->setId(12);
        $entry->setRefId(6000000001);
        $em->persist($entry);
        $fake->setBalance(1000);
        $time = new \DateTime('2013-01-01 12:00:00');
        $entry->setCreatedAt($time);
        $entry->setAt(20130101120000);
        $em->persist($fake);
        $em->flush();

        //測試根據廳取得假現金明細，加帶domain條件
        $parameter = [
            'opcode' => ['1001', '1010'],
            'start' => $start->format(\DateTime::ISO8601),
            'end' => $end->format(\DateTime::ISO8601),
            'domain' => 52
        ];

        $client->request('GET', '/api/check/cash_fake/entry_by_domain', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(6000000001, $output['ret'][0]['ref_id']);
        $this->assertEquals(1000, $output['ret'][0]['amount']);
        $this->assertEquals(1010, $output['ret'][0]['opcode']);
        $this->assertEquals(52, $output['ret'][0]['user_id']);
    }

    /**
     * 測試根據廳取得假現金明細，帶入不合法的domain
     */
    public function testGetCashFakeEntryByDomainWithInvalidDomain()
    {
        $client = $this->createClient();
        $start = new \DateTime('2012-01-01');
        $end = new \DateTime('2013-01-02');

        //測試帶入錯誤domain
        $parameter = [
            'opcode' => ['1001', '1002'],
            'start' => $start->format(\DateTime::ISO8601),
            'end' => $end->format(\DateTime::ISO8601),
            'domain' => 'a'
        ];

        $client->request('GET', '/api/check/cash_fake/entry_by_domain', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150450020, $output['code']);
        $this->assertEquals('Not a domain', $output['msg']);

        //測試帶入非domain
        $parameter = [
            'opcode' => ['1001', '1002'],
            'start' => $start->format(\DateTime::ISO8601),
            'end' => $end->format(\DateTime::ISO8601),
            'domain' => 999
        ];

        $client->request('GET', '/api/check/cash_fake/entry_by_domain', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150450020, $output['code']);
        $this->assertEquals('Not a domain', $output['msg']);
    }

    /**
     * 測試根據廳取得假現金明細，找不到使用者
     */
    public function testGetCasFakeEntryByDomainAndUserNotFound()
    {
        $client = $this->createClient();
        $start = new \DateTime('2012-01-01');
        $end = new \DateTime('2013-01-02');

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $user = new User();
        $user->setId(52);
        $em->persist($user);

        $config = new DomainConfig($user, 'vtester52', 'test');
        $emShare->persist($config);
        $emShare->flush();

        //測試帶入的user_id，找不到此使用者
        $parameter = [
            'opcode' => ['1001', '1002'],
            'start' => $start->format(\DateTime::ISO8601),
            'end' => $end->format(\DateTime::ISO8601),
            'domain' => 52,
            'user_id' => 9999
        ];

        $client->request('GET', '/api/check/cash_fake/entry_by_domain', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150450021, $output['code']);
        $this->assertEquals('No such user', $output['msg']);
    }
}
