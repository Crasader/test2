<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\OutsidePayway;

class OutsideFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
        ];

        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadOutsideEntryData'
        ];

        $this->loadFixtures($classnames, 'outside');
    }

    /**
     * 測試取得使用者外接額度對應，但找不到使用者
     */
    public function testGetOutsidePaywayButNoSuchUser()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/999/outside/payway');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150820001, $output['code']);
        $this->assertEquals('No such user', $output['msg']);
    }

    /**
     * 測試取得使用者外接額度對應，但找不到外接額度
     */
    public function testGetOutsidePaywayButNoOutsidePayway()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/2/outside/payway');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150820002, $output['code']);
        $this->assertEquals('No outside supported', $output['msg']);
    }

    /**
     * 測試取得使用者外接額度對應(博狗)
     */
    public function testGetOutsidePaywayBodog()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $outsidePayway = new OutsidePayway(2);
        $outsidePayway->setBodog(true);
        $em->persist($outsidePayway);
        $em->flush();

        $client->request('GET', '/api/user/3/outside/payway');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret']['user_id']);
        $this->assertTrue($output['ret']['bodog']);
        $this->assertFalse($output['ret']['suncity']);
    }

    /**
     * 測試取得使用者外接額度對應(太陽城)
     */
    public function testGetOutsidePaywaySuncity()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $outsidePayway = new OutsidePayway(2);
        $outsidePayway->setSuncity(true);
        $em->persist($outsidePayway);
        $em->flush();

        $client->request('GET', '/api/user/3/outside/payway');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret']['user_id']);
        $this->assertTrue($output['ret']['suncity']);
        $this->assertFalse($output['ret']['bodog']);
    }

    /**
     * 測試取得單筆外接額度明細
     */
    public function testGetOutsideEntry()
    {
        $em = $this->getContainer()->get('doctrine.orm.outside_entity_manager');
        $client = $this->createClient();

        $client->request('GET', '/api/outside/entry/100');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $outEntry = $em->getRepository('BBDurianBundle:OutsideEntry')
            ->findOneBy(['id' => 100]);
        $entry = $outEntry->toArray();

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($entry['user_id'], $output['ret']['user_id']);
        $this->assertEquals($entry['currency'], $output['ret']['currency']);
        $this->assertEquals($entry['opcode'], $output['ret']['opcode']);
        $this->assertEquals($entry['created_at'], $output['ret']['created_at']);
        $this->assertEquals($entry['amount'], $output['ret']['amount']);
        $this->assertEquals($entry['balance'], $output['ret']['balance']);
        $this->assertEquals($entry['memo'], $output['ret']['memo']);
        $this->assertEquals($entry['ref_id'], $output['ret']['ref_id']);
        $this->assertEquals($entry['group'], $output['ret']['group']);
    }

    /**
     * 測試取得單筆外接額度明細，沒有明細
     */
    public function testGetOutsideEntryButNoEntry()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $client->request('GET', '/api/outside/entry/9999');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150820003, $output['code']);
        $this->assertEquals('No outside entry found', $output['msg']);
    }

    /**
     * 測試依使用者取得外接額度明細
     */
    public function testGetOutsideEntries()
    {
        $em = $this->getContainer()->get('doctrine.orm.outside_entity_manager');
        $client = $this->createClient();

        $param = [
            'ref_id' => 1,
            'start' => '2017-01-01T00:00:00+0800',
            'end' => '2017-05-01T00:00:00+0800',
            'opcode' => [1001],
            'order' => 'desc',
            'sort' => 'id',
            'first_result' => 0,
            'max_results' => 2
        ];

        $client->request('GET', '/api/user/1/outside/entry', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $outEntry2 = $em->getRepository('BBDurianBundle:OutsideEntry')
            ->findOneBy(['id' => 102]);
        $entry2 = $outEntry2->toArray();

        $outEntry1 = $em->getRepository('BBDurianBundle:OutsideEntry')
            ->findOneBy(['id' => 100]);
        $entry1 = $outEntry1->toArray();

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($entry2['user_id'], $output['ret'][0]['user_id']);
        $this->assertEquals($entry2['currency'], $output['ret'][0]['currency']);
        $this->assertEquals($entry2['opcode'], $output['ret'][0]['opcode']);
        $this->assertEquals($entry2['created_at'], $output['ret'][0]['created_at']);
        $this->assertEquals($entry2['amount'], $output['ret'][0]['amount']);
        $this->assertEquals($entry2['balance'], $output['ret'][0]['balance']);
        $this->assertEquals($entry2['memo'], $output['ret'][0]['memo']);
        $this->assertEquals($entry2['ref_id'], $output['ret'][0]['ref_id']);
        $this->assertEquals($entry2['group'], $output['ret'][0]['group']);

        $this->assertEquals($entry1['user_id'], $output['ret'][1]['user_id']);
        $this->assertEquals($entry1['currency'], $output['ret'][1]['currency']);
        $this->assertEquals($entry1['opcode'], $output['ret'][1]['opcode']);
        $this->assertEquals($entry1['created_at'], $output['ret'][1]['created_at']);
        $this->assertEquals($entry1['amount'], $output['ret'][1]['amount']);
        $this->assertEquals($entry1['balance'], $output['ret'][1]['balance']);
        $this->assertEquals($entry1['memo'], $output['ret'][1]['memo']);
        $this->assertEquals($entry1['ref_id'], $output['ret'][1]['ref_id']);
        $this->assertEquals($entry1['group'], $output['ret'][1]['group']);
        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(2, $output['pagination']['max_results']);
        $this->assertEquals(2, $output['pagination']['total']);
    }

    /**
     * 測試透過ref_id取得外接額度明細
     */
    public function testGetOutsideEntriesByRefId()
    {
        $em = $this->getContainer()->get('doctrine.orm.outside_entity_manager');
        $client = $this->createClient();

        $param = [
            'ref_id' => 1,
            'first_result' => 0,
            'max_results' => 2
        ];

        $client->request('GET', '/api/outside/entries_by_ref_id', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $outEntry2 = $em->getRepository('BBDurianBundle:OutsideEntry')
            ->findOneBy(['id' => 102]);
        $entry2 = $outEntry2->toArray();

        $outEntry1 = $em->getRepository('BBDurianBundle:OutsideEntry')
            ->findOneBy(['id' => 100]);
        $entry1 = $outEntry1->toArray();

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($entry1['user_id'], $output['ret'][0]['user_id']);
        $this->assertEquals($entry1['currency'], $output['ret'][0]['currency']);
        $this->assertEquals($entry1['opcode'], $output['ret'][0]['opcode']);
        $this->assertEquals($entry1['created_at'], $output['ret'][0]['created_at']);
        $this->assertEquals($entry1['amount'], $output['ret'][0]['amount']);
        $this->assertEquals($entry1['balance'], $output['ret'][0]['balance']);
        $this->assertEquals($entry1['memo'], $output['ret'][0]['memo']);
        $this->assertEquals($entry1['ref_id'], $output['ret'][0]['ref_id']);
        $this->assertEquals($entry1['group'], $output['ret'][0]['group']);

        $this->assertEquals($entry2['user_id'], $output['ret'][1]['user_id']);
        $this->assertEquals($entry2['currency'], $output['ret'][1]['currency']);
        $this->assertEquals($entry2['opcode'], $output['ret'][1]['opcode']);
        $this->assertEquals($entry2['created_at'], $output['ret'][1]['created_at']);
        $this->assertEquals($entry2['amount'], $output['ret'][1]['amount']);
        $this->assertEquals($entry2['balance'], $output['ret'][1]['balance']);
        $this->assertEquals($entry2['memo'], $output['ret'][1]['memo']);
        $this->assertEquals($entry2['ref_id'], $output['ret'][1]['ref_id']);
        $this->assertEquals($entry2['group'], $output['ret'][1]['group']);
        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(2, $output['pagination']['max_results']);
        $this->assertEquals(2, $output['pagination']['total']);
    }

    /**
     * 測試取得外接額度交易機制資訊，但沒有紀錄
     */
    public function testGetOutsideTransButNoTransFound()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/outside/transaction/123');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150820007, $output['code']);
        $this->assertEquals('No outsideTrans found', $output['msg']);
    }

    /**
     * 測試取得外接額度交易機制資訊
     */
    public function testGetOutsideTrans()
    {
        $client = $this->createClient();
        $redis = $this->getContainer()->get('snc_redis.bodog');

        $data = [
            'id' => 1,
            'user_id' => 2,
            'currency' => 156,
            'opcode' => 40100,
            'ref_id' => 0,
            'amount' => 2,
            'memo' => 'test',
            'created_at' => '2017-01-01T00:00:00+0800',
            'checked_at' => null,
            'checked' => 0,
            'outside_trans_id' => 2,
            'group_num' => 1
        ];

        $redis->hmset('en_trans_id_1', $data);

        $client->request('GET', '/api/outside/transaction/1');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($data['id'], $output['ret']['id']);
        $this->assertEquals($data['user_id'], $output['ret']['user_id']);
        $this->assertEquals('CNY', $output['ret']['currency']);
        $this->assertEquals($data['opcode'], $output['ret']['opcode']);
        $this->assertEquals($data['ref_id'], $output['ret']['ref_id']);
        $this->assertEquals($data['amount'], $output['ret']['amount']);
        $this->assertEquals($data['memo'], $output['ret']['memo']);
        $this->assertEquals($data['created_at'], $output['ret']['created_at']);
        $this->assertEmpty($output['ret']['checked_at']);
        $this->assertEquals($data['checked'], $output['ret']['checked']);
        $this->assertEquals($data['outside_trans_id'], $output['ret']['outside_trans_id']);
        $this->assertEquals($data['group_num'], $output['ret']['group']);

        //太陽城
        $redis = $this->getContainer()->get('snc_redis.suncity');

        $data = [
            'id' => 2,
            'user_id' => 2,
            'currency' => 156,
            'opcode' => 40100,
            'ref_id' => 0,
            'amount' => 2,
            'memo' => 'test',
            'created_at' => '2017-01-01T00:00:00+0800',
            'checked_at' => null,
            'checked' => 0,
            'outside_trans_id' => 2,
            'group_num' => 2
        ];

        $redis->hmset('en_trans_id_2', $data);

        $client->request('GET', '/api/outside/transaction/2');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($data['id'], $output['ret']['id']);
        $this->assertEquals($data['user_id'], $output['ret']['user_id']);
        $this->assertEquals('CNY', $output['ret']['currency']);
        $this->assertEquals($data['opcode'], $output['ret']['opcode']);
        $this->assertEquals($data['ref_id'], $output['ret']['ref_id']);
        $this->assertEquals($data['amount'], $output['ret']['amount']);
        $this->assertEquals($data['memo'], $output['ret']['memo']);
        $this->assertEquals($data['created_at'], $output['ret']['created_at']);
        $this->assertEmpty($output['ret']['checked_at']);
        $this->assertEquals($data['checked'], $output['ret']['checked']);
        $this->assertEquals($data['outside_trans_id'], $output['ret']['outside_trans_id']);
        $this->assertEquals($data['group_num'], $output['ret']['group']);
    }

    /**
     * 測試取得總計資訊帶入時間區間
     */
    public function testGetTotalAmountWithTimeInterval()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $outsidePayway = new OutsidePayway(2);
        $outsidePayway->setSuncity(true);
        $em->persist($outsidePayway);

        $user = $em->find('BBDurianBundle:User', 8);
        $user->setCurrency(156);
        $em->flush();

        $parameters = [
            'start' => '2017-03-01T00:00:00+0800',
            'end'   => '2017-03-01T23:59:59+0800'
        ];

        $client->request('GET', '/api/user/8/outside/total_amount', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals(10, $ret['ret']['deposite']);
        $this->assertEquals(0, $ret['ret']['withdraw']);
        $this->assertEquals(10, $ret['ret']['total']);
    }

    /**
     * 測試取得總計資訊帶入opcode和時間區間
     */
    public function testGetTotalAmountWithOpcodeAndTimeInterval()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $outsidePayway = new OutsidePayway(2);
        $outsidePayway->setSuncity(true);
        $em->persist($outsidePayway);

        $user = $em->find('BBDurianBundle:User', 8);
        $user->setCurrency(156);
        $em->flush();

        $parameters = [
            'opcode' => 1010,
            'start'  => '2017-01-01T00:00:00+0800',
            'end'    => '2017-05-01T23:59:59+0800'
        ];

        $client->request('GET', '/api/user/8/outside/total_amount', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals(10, $ret['ret']['deposite']);
        $this->assertEquals(-7, $ret['ret']['withdraw']);
        $this->assertEquals(3, $ret['ret']['total']);
    }

    /**
     * 測試取得總計資訊,輸入錯誤的user
     */
    public function testGetTotalAmountWithErrorUser()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/8/outside/total_amount');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150820009, $ret['code']);
        $this->assertEquals('No outside supported', $ret['msg']);
    }

    /**
     * 測試取得總計資訊,輸入不存在的user
     */
    public function testGetTotalAmountButNoSuchUser()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/999/outside/total_amount');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150820010, $ret['code']);
        $this->assertEquals('No such user', $ret['msg']);
    }

    /**
     * 測試取得總計資訊，若沒有資訊則回傳空陣列
     */
    public function testGetTotalAmountButFeedbackEmptyArray()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $outsidePayway = new OutsidePayway(2);
        $outsidePayway->setSuncity(true);
        $em->persist($outsidePayway);

        $user = $em->find('BBDurianBundle:User', 8);
        $user->setCurrency(156);
        $em->flush();

        // 指定opcode做查詢
        $parameters = ['opcode' => 1029];

        $client->request('GET', '/api/user/8/outside/total_amount', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals([], $ret['ret']);
    }
}
