<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Consumer\Poper;

class BitcoinDepositFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBitcoinAddressData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBitcoinDepositEntryData',
        ];

        $this->loadFixtures($classnames);

        $entryClassnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashEntryData'
        ];

        $this->loadFixtures($entryClassnames, 'entry');

        $shareClassnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadExchangeData'
        ];

        $this->loadFixtures($shareClassnames, 'share');

        $redis = $this->getContainer()->get('snc_redis.sequence');
        $redis->set('bitcoin_deposit_seq', 10);
        $redis->set('cash_seq', 1000);
    }

    /**
     * 測試新增比特幣入款
     */
    public function testCreate()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = [
            'amount' => '100',
            'bitcoin_amount' => '0.12469',
            'bitcoin_rate' => '0.00123456',
            'rate_difference' => '0.00001234',
            'currency' => 'TWD',
            'memo' => '第一次入款'
        ];

        $client->request('POST', '/api/user/4/bitcoin_deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $id = $ret['ret']['id'];
        $entry = $em->getRepository('BBDurianBundle:BitcoinDepositEntry')->findOneBy(['id' => $id]);

        $this->assertEquals(4, $ret['ret']['bitcoin_wallet_id']);
        $this->assertEquals(3, $ret['ret']['bitcoin_address_id']);
        $this->assertEquals('address4', $ret['ret']['bitcoin_address']);
        $this->assertEquals(4, $ret['ret']['user_id']);
        $this->assertEquals(2, $ret['ret']['domain']);
        $this->assertEquals(2, $ret['ret']['level_id']);
        $this->assertNull($ret['ret']['confirm_at']);
        $this->assertTrue($ret['ret']['process']);
        $this->assertFalse($ret['ret']['confirm']);
        $this->assertFalse($ret['ret']['cancel']);
        $this->assertEquals(0, $ret['ret']['amount_entry_id']);
        $this->assertEquals('TWD', $ret['ret']['currency']);
        $this->assertEquals('TWD', $ret['ret']['payway_currency']);
        $this->assertEquals(100, $ret['ret']['amount']);
        $this->assertEquals(22.3, $ret['ret']['amount_conv_basic']);
        $this->assertEquals(100, $ret['ret']['amount_conv']);
        $this->assertEquals(0.12469, $ret['ret']['bitcoin_amount']);
        $this->assertEquals(0.22300000, $ret['ret']['rate']);
        $this->assertEquals(0.22300000, $ret['ret']['payway_rate']);
        $this->assertEquals(0.00123456, $ret['ret']['bitcoin_rate']);
        $this->assertEquals(0.00001234, $ret['ret']['rate_difference']);
        $this->assertFalse($ret['ret']['control']);
        $this->assertEmpty($ret['ret']['operator']);
        $this->assertEquals('第一次入款', $ret['ret']['memo']);

        $this->assertEquals($entry->getAt()->format(\DateTime::ISO8601), $ret['ret']['at']);
    }

    /**
     * 測試比特幣確認入款
     */
    public function testConfirm()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $client = $this->createClient();
        $ceRepo = $emEntry->getRepository('BBDurianBundle:CashEntry');
        $bdeRepo = $em->getRepository('BBDurianBundle:BitcoinDepositEntry');

        $parameters = [
            'operator' => 'test',
            'control' => 1,
        ];

        // 確認入款
        $client->request('PUT', '/api/bitcoin_deposit/entry/201801110000000002/confirm', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 消化Queue
        $poper = new Poper();
        $poper->runPop($this->getContainer(), 'cash');

        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $this->assertTrue($output['ret']['confirm']);
        $this->assertFalse($output['ret']['process']);
        $this->assertFalse($output['ret']['cancel']);
        $this->assertNotNull($output['ret']['confirm_at']);
        $this->assertTrue($output['ret']['control']);
        $this->assertEquals('test', $output['ret']['operator']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals(1001, $output['ret']['amount_entry_id']);
        $this->assertEquals(5484.3049, $output['ret']['amount_entry']['cash']['balance']);
        $this->assertEquals(0, $output['ret']['amount_entry']['cash']['pre_sub']);
        $this->assertEquals(0, $output['ret']['amount_entry']['cash']['pre_add']);
        $this->assertEquals(1001, $output['ret']['amount_entry']['entry']['id']);
        $this->assertEquals(1340, $output['ret']['amount_entry']['entry']['opcode']);
        $this->assertEquals('操作者： test', $output['ret']['amount_entry']['entry']['memo']);
        $this->assertEquals(201801110000000002, $output['ret']['amount_entry']['entry']['ref_id']);
        $this->assertEquals(4484.3049, $output['ret']['amount_entry']['entry']['amount']);
        $this->assertEquals(5484.3049, $output['ret']['amount_entry']['entry']['balance']);

        $cash = $em->find('BBDurianBundle:Cash', 7);
        $centry = $ceRepo->findOneBy(['id' => $output['ret']['amount_entry']['entry']['id']]);
        $bdentry = $bdeRepo->findOneBy(['id' => 201801110000000002]);

        $this->assertEquals($bdentry->getAmountConv(), $centry->getAmount());
        $this->assertEquals($bdentry->getId(), $centry->getRefId());
        $this->assertEquals('操作者： test', $centry->getMemo());
        $this->assertEquals(1340, $centry->getOpcode());
        $this->assertEquals(5484.3049, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());

        // 檢查使用者出入款統計資料
        $userStat = $em->find('BBDurianBundle:UserStat', 8);
        $this->assertEquals(1, $userStat->getBitcoinDepositCount());
        $this->assertEquals(1000, $userStat->getBitcoinDepositTotal());
        $this->assertEquals(1000, $userStat->getBitcoinDepositMax());
        $this->assertEquals(1000, $userStat->getFirstDepositAmount());

        // 檢查操作紀錄
        $userStatMessage = [
            '@bitcoin_deposit_count:0=>1',
            '@bitcoin_deposit_total:0=>1000',
            '@bitcoin_deposit_max:0=>1000.0000',
            '@first_deposit_at:' . $output['ret']['confirm_at'],
            '@first_deposit_amount:1000.0000',
            '@modified_at:',
        ];
        $logOperation1 = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('user_stat', $logOperation1->getTableName());
        $this->assertEquals('@user_id:8', $logOperation1->getMajorKey());
        $this->assertContains(implode(', ', $userStatMessage), $logOperation1->getMessage());

        $message = [
            '@confirm:true',
            '@operator:=>test',
            '@control:1',
            '@amount_entry_id:1001',
        ];
        $logOperation2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('bitcoin_deposit_entry', $logOperation2->getTableName());
        $this->assertEquals('@id:201801110000000002', $logOperation2->getMajorKey());
        $this->assertEquals(implode(', ', $message), $logOperation2->getMessage());
    }

    /**
     * 測試確認入款, 入款金額超過50萬, 需寄發異常入款提醒
     */
    public function testConfirmWithAbnormalAmount()
    {
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $ceRepo = $emEntry->getRepository('BBDurianBundle:CashEntry');
        $client = $this->createClient();
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $parameters = [
            'amount' => 2500000,
            'bitcoin_amount' => '3117.25',
            'bitcoin_rate' => '0.00123456',
            'rate_difference' => '0.00001234',
            'currency' => 'TWD',
        ];

        $client->request('POST', '/api/user/4/bitcoin_deposit', $parameters);

        $createJson = $client->getResponse()->getContent();
        $createOutput = json_decode($createJson, true);

        $id = $createOutput['ret']['id'];
        $this->assertEquals('ok', $createOutput['result']);
        $this->assertTrue($createOutput['ret']['process']);

        $confirmParameters = [
            'operator' => 'test',
            'control' => 1,
        ];

        // 確認入款
        $client->request('PUT', "/api/bitcoin_deposit/entry/$id/confirm", $confirmParameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 跑背景程式讓queue被消化
        $cashPoper = new Poper();
        $cashPoper->runPop($this->getContainer(), 'cash');

        $this->assertTrue($output['ret']['confirm']);
        $this->assertFalse($output['ret']['process']);

        $centry = $ceRepo->findOneBy(['id' => $output['ret']['amount_entry']['entry']['id']]);
        $this->assertEquals('操作者： test', $centry->getMemo());

        // 檢查異常入款提醒queue
        $abnormalDepositNotify = json_decode($redis->rpop('abnormal_deposit_notify_queue'), true);

        $confirmAt = $output['ret']['confirm_at'];
        $this->assertEquals(2, $abnormalDepositNotify['domain']);
        $this->assertEquals($confirmAt, $abnormalDepositNotify['confirm_at']);
        $this->assertEquals('wtester', $abnormalDepositNotify['user_name']);
        $this->assertEquals(1340, $abnormalDepositNotify['opcode']);
        $this->assertEquals('test', $abnormalDepositNotify['operator']);
        $this->assertEquals(557500, $abnormalDepositNotify['amount']);
    }

    /**
     * 測試比特幣取消入款
     */
    public function testCancel()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();
        $bdeRepo = $em->getRepository('BBDurianBundle:BitcoinDepositEntry');

        $parameters = [
            'operator' => 'test',
            'control' => 1,
        ];

        // 確認入款
        $client->request('PUT', '/api/bitcoin_deposit/entry/201801110000000002/cancel', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertTrue($output['ret']['cancel']);
        $this->assertFalse($output['ret']['process']);
        $this->assertFalse($output['ret']['confirm']);
        $this->assertTrue($output['ret']['control']);
        $this->assertEquals('test', $output['ret']['operator']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals(0, $output['ret']['amount_entry_id']);

        $bdentry = $bdeRepo->findOneBy(['id' => 201801110000000002]);
        $this->assertEquals($bdentry->getConfirmAt()->format(\DateTime::ISO8601), $output['ret']['confirm_at']);

        // 檢查操作紀錄
        $userStatMessage = [
            '@cancel:false=>true',
            '@operator:=>test',
            '@control:1',
        ];
        $logOperation1 = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('bitcoin_deposit_entry', $logOperation1->getTableName());
        $this->assertEquals('@id:201801110000000002', $logOperation1->getMajorKey());
        $this->assertEquals(implode(', ', $userStatMessage), $logOperation1->getMessage());
    }

    /**
     * 測試取得一筆比特幣入款記錄
     */
    public function testGetEntry()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/bitcoin_deposit/entry/201801120000000003');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(201801120000000003, $output['ret']['id']);
        $this->assertEquals(4, $output['ret']['bitcoin_wallet_id']);
        $this->assertEquals(6, $output['ret']['bitcoin_address_id']);
        $this->assertEquals('address8', $output['ret']['bitcoin_address']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals(2, $output['ret']['domain']);
        $this->assertEquals(2, $output['ret']['level_id']);
        $this->assertNotNull($output['ret']['at']);
        $this->assertNotNull($output['ret']['confirm_at']);
        $this->assertTrue($output['ret']['cancel']);
        $this->assertFalse($output['ret']['process']);
        $this->assertEquals(0, $output['ret']['amount_entry_id']);
        $this->assertEquals('USD', $output['ret']['currency']);
        $this->assertEquals('USD', $output['ret']['payway_currency']);
        $this->assertEquals(500, $output['ret']['amount']);
        $this->assertEquals(3170, $output['ret']['amount_conv_basic']);
        $this->assertEquals(500, $output['ret']['amount_conv']);
        $this->assertEquals(68.54096, $output['ret']['bitcoin_amount']);
        $this->assertEquals(6.34, $output['ret']['rate']);
        $this->assertEquals(6.34, $output['ret']['payway_rate']);
        $this->assertEquals(0.13572468, $output['ret']['bitcoin_rate']);
        $this->assertEquals(0.00135724, $output['ret']['rate_difference']);
        $this->assertTrue($output['ret']['control']);
        $this->assertEquals('operatorTest', $output['ret']['operator']);
    }

    /**
     * 測試修改比特幣入款明細(只有備註)
     */
    public function testSetBitcoinDepositEntryMemo()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $client = $this->createClient();

        $client->request('PUT', '/api/bitcoin_deposit/201801110000000002/memo', ['memo' => 'testmemo']);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals('testmemo', $ret['ret']['memo']);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals("bitcoin_deposit_entry", $logOperation->getTableName());
        $this->assertEquals("@id:201801110000000002", $logOperation->getMajorKey());
        $this->assertEquals("@memo:=>testmemo", $logOperation->getMessage());
    }

    /**
     * 測試回傳比特幣入款紀錄
     */
    public function testListEntry()
    {
        $client = $this->createClient();

        $parameters1 = ['domain' => 2];
        $client->request('GET', '/api/bitcoin_deposit/entry/list', $parameters1);

        $json1 = $client->getResponse()->getContent();
        $ret1 = json_decode($json1, true);

        $this->assertEquals('ok', $ret1['result']);
        $this->assertCount(3, $ret1['ret']);
        $this->assertEquals(201801110000000001, $ret1['ret'][2]['id']);
        $this->assertEquals(2, $ret1['ret'][2]['domain']);
        $this->assertEquals('TWD', $ret1['ret'][2]['currency']);
        $this->assertEquals('TWD', $ret1['ret'][2]['payway_currency']);
        $this->assertEquals(100, $ret1['ret'][2]['amount']);
        $this->assertEquals(12.469135, $ret1['ret'][2]['bitcoin_amount']);
        $this->assertEquals(0.223, $ret1['ret'][2]['rate']);
        $this->assertEquals(0.223, $ret1['ret'][2]['payway_rate']);
        $this->assertEquals(0.12345678, $ret1['ret'][2]['bitcoin_rate']);
        $this->assertEquals(0.00123457, $ret1['ret'][2]['rate_difference']);
        $this->assertEquals(22.3, $ret1['ret'][2]['amount_conv_basic']);
        $this->assertEquals(100, $ret1['ret'][2]['amount_conv']);
        $this->assertEquals(2, $ret1['ret'][2]['level_id']);

        // 測試搜尋幣別帶入小計資料
        $parameters2 = [
            'domain' => 2,
            'currency' => 'CNY',
            'sub_total' => 1,
        ];
        $client->request('GET', '/api/bitcoin_deposit/entry/list', $parameters2);

        $json2 = $client->getResponse()->getContent();
        $ret2 = json_decode($json2, true);

        $this->assertEquals('ok', $ret2['result']);
        $this->assertCount(1, $ret2['ret']);
        $this->assertEquals(201801110000000002, $ret2['ret'][0]['id']);
        $this->assertEquals(2, $ret2['ret'][0]['domain']);
        $this->assertEquals('CNY', $ret2['ret'][0]['currency']);
        $this->assertEquals('TWD', $ret2['ret'][0]['payway_currency']);
        $this->assertEquals(1000, $ret2['ret'][0]['amount']);
        $this->assertEquals(236.91357, $ret2['ret'][0]['bitcoin_amount']);
        $this->assertEquals(1, $ret2['ret'][0]['rate']);
        $this->assertEquals(0.223, $ret2['ret'][0]['payway_rate']);
        $this->assertEquals(0.23456789, $ret2['ret'][0]['bitcoin_rate']);
        $this->assertEquals(0.00234568, $ret2['ret'][0]['rate_difference']);
        $this->assertEquals(1000, $ret2['ret'][0]['amount_conv_basic']);
        $this->assertEquals(4484.3049, $ret2['ret'][0]['amount_conv']);

        // 測試搜尋相同比特幣錢包的單
        $parameters3 = [
            'domain' => 2,
            'bitcoin_wallet_id' => 4,
        ];
        $client->request('GET', '/api/bitcoin_deposit/entry/list', $parameters3);

        $json3 = $client->getResponse()->getContent();
        $ret3 = json_decode($json3, true);

        $this->assertEquals('ok', $ret3['result']);
        $this->assertCount(3, $ret3['ret']);
        $this->assertEquals(201801110000000001, $ret3['ret'][2]['id']);
        $this->assertEquals(2, $ret3['ret'][2]['domain']);
        $this->assertEquals('TWD', $ret3['ret'][2]['currency']);
        $this->assertEquals('TWD', $ret3['ret'][2]['payway_currency']);
        $this->assertEquals(100, $ret3['ret'][2]['amount']);
        $this->assertEquals(12.469135, $ret3['ret'][2]['bitcoin_amount']);
        $this->assertEquals(0.223, $ret3['ret'][2]['rate']);
        $this->assertEquals(0.223, $ret3['ret'][2]['payway_rate']);
        $this->assertEquals(0.12345678, $ret3['ret'][2]['bitcoin_rate']);
        $this->assertEquals(0.00123457, $ret3['ret'][2]['rate_difference']);
        $this->assertEquals(22.3, $ret3['ret'][2]['amount_conv_basic']);
        $this->assertEquals(100, $ret3['ret'][2]['amount_conv']);
        $this->assertFalse($ret3['ret'][2]['process']);

        // 測試搜尋相同比特幣位址id的單
        $parameters4 = [
            'domain' => 2,
            'bitcoin_address_id' => 6,
        ];
        $client->request('GET', '/api/bitcoin_deposit/entry/list', $parameters4);

        $json4 = $client->getResponse()->getContent();
        $ret4 = json_decode($json4, true);

        $this->assertEquals('ok', $ret4['result']);
        $this->assertCount(2, $ret4['ret']);
        $this->assertEquals(201801110000000002, $ret4['ret'][1]['id']);
        $this->assertEquals(2, $ret4['ret'][1]['domain']);
        $this->assertEquals('CNY', $ret4['ret'][1]['currency']);
        $this->assertEquals('TWD', $ret4['ret'][1]['payway_currency']);
        $this->assertEquals(1000, $ret4['ret'][1]['amount']);
        $this->assertEquals(236.91357, $ret4['ret'][1]['bitcoin_amount']);
        $this->assertEquals(1, $ret4['ret'][1]['rate']);
        $this->assertEquals(0.223, $ret4['ret'][1]['payway_rate']);
        $this->assertEquals(0.23456789, $ret4['ret'][1]['bitcoin_rate']);
        $this->assertEquals(0.00234568, $ret4['ret'][1]['rate_difference']);
        $this->assertEquals(1000, $ret4['ret'][1]['amount_conv_basic']);
        $this->assertEquals(4484.3049, $ret4['ret'][1]['amount_conv']);

        // 測試搜尋相同比特幣位址的單
        $parameters5 = [
            'domain' => 2,
            'bitcoin_address' => 'address8',
        ];
        $client->request('GET', '/api/bitcoin_deposit/entry/list', $parameters5);

        $json5 = $client->getResponse()->getContent();
        $ret5 = json_decode($json5, true);

        $this->assertEquals('ok', $ret5['result']);
        $this->assertCount(2, $ret5['ret']);
        $this->assertEquals(201801110000000002, $ret5['ret'][1]['id']);
        $this->assertEquals(2, $ret5['ret'][1]['domain']);
        $this->assertEquals('CNY', $ret5['ret'][1]['currency']);
        $this->assertEquals('TWD', $ret5['ret'][1]['payway_currency']);
        $this->assertEquals(1000, $ret5['ret'][1]['amount']);
        $this->assertEquals(236.91357, $ret5['ret'][1]['bitcoin_amount']);
        $this->assertEquals(1, $ret5['ret'][1]['rate']);
        $this->assertEquals(0.223, $ret5['ret'][1]['payway_rate']);
        $this->assertEquals(0.23456789, $ret5['ret'][1]['bitcoin_rate']);
        $this->assertEquals(0.00234568, $ret5['ret'][1]['rate_difference']);
        $this->assertEquals(1000, $ret5['ret'][1]['amount_conv_basic']);
        $this->assertEquals(4484.3049, $ret5['ret'][1]['amount_conv']);

        // 測試搜尋相同會員的單
        $parameters6 = [
            'domain' => 2,
            'user_id' => 8,
        ];
        $client->request('GET', '/api/bitcoin_deposit/entry/list', $parameters6);

        $json6 = $client->getResponse()->getContent();
        $ret6 = json_decode($json6, true);

        $this->assertEquals('ok', $ret6['result']);
        $this->assertCount(2, $ret6['ret']);
        $this->assertEquals(201801110000000002, $ret6['ret'][1]['id']);
        $this->assertEquals(2, $ret6['ret'][1]['domain']);
        $this->assertEquals('CNY', $ret6['ret'][1]['currency']);
        $this->assertEquals('TWD', $ret6['ret'][1]['payway_currency']);
        $this->assertEquals(1000, $ret6['ret'][1]['amount']);
        $this->assertEquals(236.91357, $ret6['ret'][1]['bitcoin_amount']);
        $this->assertEquals(1, $ret6['ret'][1]['rate']);
        $this->assertEquals(0.223, $ret6['ret'][1]['payway_rate']);
        $this->assertEquals(0.23456789, $ret6['ret'][1]['bitcoin_rate']);
        $this->assertEquals(0.00234568, $ret6['ret'][1]['rate_difference']);
        $this->assertEquals(1000, $ret6['ret'][1]['amount_conv_basic']);
        $this->assertEquals(4484.3049, $ret6['ret'][1]['amount_conv']);

        // 測試搜尋處理中的單
        $parameters7 = [
            'domain' => 2,
            'process' => 1,
        ];
        $client->request('GET', '/api/bitcoin_deposit/entry/list', $parameters7);

        $json7 = $client->getResponse()->getContent();
        $ret7 = json_decode($json7, true);

        $this->assertEquals('ok', $ret7['result']);
        $this->assertCount(1, $ret7['ret']);
        $this->assertEquals(201801110000000002, $ret7['ret'][0]['id']);
        $this->assertEquals(2, $ret7['ret'][0]['domain']);
        $this->assertEquals('CNY', $ret7['ret'][0]['currency']);
        $this->assertEquals('TWD', $ret7['ret'][0]['payway_currency']);
        $this->assertEquals(1000, $ret7['ret'][0]['amount']);
        $this->assertEquals(236.91357, $ret7['ret'][0]['bitcoin_amount']);
        $this->assertEquals(1, $ret7['ret'][0]['rate']);
        $this->assertEquals(0.223, $ret7['ret'][0]['payway_rate']);
        $this->assertEquals(0.23456789, $ret7['ret'][0]['bitcoin_rate']);
        $this->assertEquals(0.00234568, $ret7['ret'][0]['rate_difference']);
        $this->assertEquals(1000, $ret7['ret'][0]['amount_conv_basic']);
        $this->assertEquals(4484.3049, $ret7['ret'][0]['amount_conv']);

        // 測試搜尋已確認的單
        $parameters8 = [
            'domain' => 2,
            'confirm' => 1,
        ];
        $client->request('GET', '/api/bitcoin_deposit/entry/list', $parameters8);

        $json8 = $client->getResponse()->getContent();
        $ret8 = json_decode($json8, true);

        $this->assertEquals('ok', $ret8['result']);
        $this->assertCount(1, $ret8['ret']);
        $this->assertEquals(201801110000000001, $ret8['ret'][0]['id']);
        $this->assertEquals(2, $ret8['ret'][0]['domain']);
        $this->assertEquals('TWD', $ret8['ret'][0]['currency']);
        $this->assertEquals('TWD', $ret8['ret'][0]['payway_currency']);
        $this->assertEquals(100, $ret8['ret'][0]['amount']);
        $this->assertEquals(12.469135, $ret8['ret'][0]['bitcoin_amount']);
        $this->assertEquals(0.223, $ret8['ret'][0]['rate']);
        $this->assertEquals(0.223, $ret8['ret'][0]['payway_rate']);
        $this->assertEquals(0.12345678, $ret8['ret'][0]['bitcoin_rate']);
        $this->assertEquals(0.00123457, $ret8['ret'][0]['rate_difference']);
        $this->assertEquals(22.3, $ret8['ret'][0]['amount_conv_basic']);
        $this->assertEquals(100, $ret8['ret'][0]['amount_conv']);
        $this->assertEquals(2, $ret8['ret'][0]['level_id']);

        // 測試搜尋已取消的單
        $parameters9 = [
            'domain' => 2,
            'cancel' => 1,
        ];
        $client->request('GET', '/api/bitcoin_deposit/entry/list', $parameters9);

        $json9 = $client->getResponse()->getContent();
        $ret9 = json_decode($json9, true);

        $this->assertEquals('ok', $ret9['result']);
        $this->assertCount(1, $ret9['ret']);
        $this->assertEquals(201801120000000003, $ret9['ret'][0]['id']);
        $this->assertEquals(2, $ret9['ret'][0]['domain']);
        $this->assertEquals('USD', $ret9['ret'][0]['currency']);
        $this->assertEquals('USD', $ret9['ret'][0]['payway_currency']);
        $this->assertEquals(500, $ret9['ret'][0]['amount']);
        $this->assertEquals(68.54096, $ret9['ret'][0]['bitcoin_amount']);
        $this->assertEquals(6.34, $ret9['ret'][0]['rate']);
        $this->assertEquals(6.34, $ret9['ret'][0]['payway_rate']);
        $this->assertEquals(0.13572468, $ret9['ret'][0]['bitcoin_rate']);
        $this->assertEquals(0.00135724, $ret9['ret'][0]['rate_difference']);
        $this->assertEquals(3170, $ret9['ret'][0]['amount_conv_basic']);
        $this->assertEquals(500, $ret9['ret'][0]['amount_conv']);

        // 測試使用出款金額交易明細id搜尋比特幣出款單
        $parameters10 = [
            'domain' => 2,
            'amount_entry_id' => 2,
        ];
        $client->request('GET', '/api/bitcoin_deposit/entry/list', $parameters10);

        $json10 = $client->getResponse()->getContent();
        $ret10 = json_decode($json10, true);

        $this->assertEquals('ok', $ret10['result']);
        $this->assertCount(1, $ret10['ret']);
        $this->assertEquals(201801110000000001, $ret10['ret'][0]['id']);
        $this->assertEquals(2, $ret10['ret'][0]['domain']);
        $this->assertEquals('TWD', $ret10['ret'][0]['currency']);
        $this->assertEquals('TWD', $ret10['ret'][0]['payway_currency']);
        $this->assertEquals(100, $ret10['ret'][0]['amount']);
        $this->assertEquals(12.469135, $ret10['ret'][0]['bitcoin_amount']);
        $this->assertEquals(0.223, $ret10['ret'][0]['rate']);
        $this->assertEquals(0.223, $ret10['ret'][0]['payway_rate']);
        $this->assertEquals(0.12345678, $ret10['ret'][0]['bitcoin_rate']);
        $this->assertEquals(0.00123457, $ret10['ret'][0]['rate_difference']);
        $this->assertEquals(22.3, $ret10['ret'][0]['amount_conv_basic']);
        $this->assertEquals(100, $ret10['ret'][0]['amount_conv']);
        $this->assertEquals(2, $ret10['ret'][0]['level_id']);

        // 測試搜尋同一操作者處理出款的單
        $parameters11 = [
            'domain' => 2,
            'operator' => 'operatorTest',
            'control' => 1,
        ];
        $client->request('GET', '/api/bitcoin_deposit/entry/list', $parameters11);

        $json11 = $client->getResponse()->getContent();
        $ret11 = json_decode($json11, true);

        $this->assertEquals('ok', $ret11['result']);
        $this->assertCount(1, $ret11['ret']);
        $this->assertEquals(201801120000000003, $ret11['ret'][0]['id']);
        $this->assertEquals(2, $ret11['ret'][0]['domain']);
        $this->assertEquals('USD', $ret11['ret'][0]['currency']);
        $this->assertEquals('USD', $ret11['ret'][0]['payway_currency']);
        $this->assertEquals(500, $ret11['ret'][0]['amount']);
        $this->assertEquals(68.54096, $ret11['ret'][0]['bitcoin_amount']);
        $this->assertEquals(6.34, $ret11['ret'][0]['rate']);
        $this->assertEquals(6.34, $ret11['ret'][0]['payway_rate']);
        $this->assertEquals(0.13572468, $ret11['ret'][0]['bitcoin_rate']);
        $this->assertEquals(0.00135724, $ret11['ret'][0]['rate_difference']);
        $this->assertEquals(3170, $ret11['ret'][0]['amount_conv_basic']);
        $this->assertEquals(500, $ret11['ret'][0]['amount_conv']);
    }

    /**
     * 測試回傳比特幣入款紀錄帶入排序欄位
     */
    public function testListEntryWithSort()
    {
        $client = $this->createClient();

        $parameters1 = [
            'domain' => 2,
            'level_id' => 2,
            'sort' => ['id'],
            'order' => ['asc']
        ];
        $client->request('GET', '/api/bitcoin_deposit/entry/list', $parameters1);

        $json1 = $client->getResponse()->getContent();
        $ret1 = json_decode($json1, true);

        $this->assertEquals('ok', $ret1['result']);
        $this->assertCount(3, $ret1['ret']);

        $this->assertEquals(201801110000000001, $ret1['ret'][0]['id']);
        $this->assertEquals(2, $ret1['ret'][0]['domain']);
        $this->assertEquals('TWD', $ret1['ret'][0]['currency']);
        $this->assertEquals('TWD', $ret1['ret'][0]['payway_currency']);
        $this->assertEquals(100, $ret1['ret'][0]['amount']);
        $this->assertEquals(12.469135, $ret1['ret'][0]['bitcoin_amount']);
        $this->assertEquals(0.223, $ret1['ret'][0]['rate']);
        $this->assertEquals(0.223, $ret1['ret'][0]['payway_rate']);
        $this->assertEquals(0.12345678, $ret1['ret'][0]['bitcoin_rate']);
        $this->assertEquals(0.00123457, $ret1['ret'][0]['rate_difference']);
        $this->assertEquals(22.3, $ret1['ret'][0]['amount_conv_basic']);
        $this->assertEquals(100, $ret1['ret'][0]['amount_conv']);
        $this->assertEquals(2, $ret1['ret'][0]['level_id']);

        $this->assertEquals(201801110000000002, $ret1['ret'][1]['id']);
        $this->assertEquals(2, $ret1['ret'][1]['domain']);
        $this->assertEquals('CNY', $ret1['ret'][1]['currency']);
        $this->assertEquals('TWD', $ret1['ret'][1]['payway_currency']);
        $this->assertEquals(1000, $ret1['ret'][1]['amount']);
        $this->assertEquals(236.91357, $ret1['ret'][1]['bitcoin_amount']);
        $this->assertEquals(1, $ret1['ret'][1]['rate']);
        $this->assertEquals(0.223, $ret1['ret'][1]['payway_rate']);
        $this->assertEquals(0.23456789, $ret1['ret'][1]['bitcoin_rate']);
        $this->assertEquals(0.00234568, $ret1['ret'][1]['rate_difference']);
        $this->assertEquals(1000, $ret1['ret'][1]['amount_conv_basic']);
        $this->assertEquals(4484.3049, $ret1['ret'][1]['amount_conv']);

        $this->assertEquals(201801120000000003, $ret1['ret'][2]['id']);
        $this->assertEquals(2, $ret1['ret'][2]['domain']);
        $this->assertEquals('USD', $ret1['ret'][2]['currency']);
        $this->assertEquals('USD', $ret1['ret'][2]['payway_currency']);
        $this->assertEquals(500, $ret1['ret'][2]['amount']);
        $this->assertEquals(68.54096, $ret1['ret'][2]['bitcoin_amount']);
        $this->assertEquals(6.34, $ret1['ret'][2]['rate']);
        $this->assertEquals(6.34, $ret1['ret'][2]['payway_rate']);
        $this->assertEquals(0.13572468, $ret1['ret'][2]['bitcoin_rate']);
        $this->assertEquals(0.00135724, $ret1['ret'][2]['rate_difference']);
        $this->assertEquals(3170, $ret1['ret'][2]['amount_conv_basic']);
        $this->assertEquals(500, $ret1['ret'][2]['amount_conv']);

        $parameters2 = [
            'domain' => 2,
            'sub_total' => 1,
            'total' => 1,
            'first_result' => 0,
            'max_results' => 2,
            'sort' => ['id'],
            'order' => ['asc']
        ];
        $client->request('GET', '/api/bitcoin_deposit/entry/list', $parameters2);

        $json2 = $client->getResponse()->getContent();
        $ret2 = json_decode($json2, true);

        $this->assertEquals('ok', $ret2['result']);
        $this->assertCount(2, $ret2['ret']);
        $this->assertEquals(1100, $ret2['sub_total']['amount']);
        $this->assertEquals(1600, $ret2['total']['amount']);
        $this->assertEquals(0, $ret2['pagination']['first_result']);
        $this->assertEquals(2, $ret2['pagination']['max_results']);
        $this->assertEquals(3, $ret2['pagination']['total']);

        $this->assertEquals(201801110000000001, $ret2['ret'][0]['id']);
        $this->assertEquals(2, $ret2['ret'][0]['domain']);
        $this->assertEquals('TWD', $ret2['ret'][0]['currency']);
        $this->assertEquals('TWD', $ret2['ret'][0]['payway_currency']);
        $this->assertEquals(100, $ret2['ret'][0]['amount']);
        $this->assertEquals(12.469135, $ret2['ret'][0]['bitcoin_amount']);
        $this->assertEquals(0.223, $ret2['ret'][0]['rate']);
        $this->assertEquals(0.223, $ret2['ret'][0]['payway_rate']);
        $this->assertEquals(0.12345678, $ret2['ret'][0]['bitcoin_rate']);
        $this->assertEquals(0.00123457, $ret2['ret'][0]['rate_difference']);
        $this->assertEquals(22.3, $ret2['ret'][0]['amount_conv_basic']);
        $this->assertEquals(100, $ret2['ret'][0]['amount_conv']);
        $this->assertEquals(2, $ret2['ret'][0]['level_id']);

        $this->assertEquals(201801110000000002, $ret2['ret'][1]['id']);
        $this->assertEquals(2, $ret2['ret'][1]['domain']);
        $this->assertEquals('CNY', $ret2['ret'][1]['currency']);
        $this->assertEquals('TWD', $ret2['ret'][1]['payway_currency']);
        $this->assertEquals(1000, $ret2['ret'][1]['amount']);
        $this->assertEquals(236.91357, $ret2['ret'][1]['bitcoin_amount']);
        $this->assertEquals(1, $ret2['ret'][1]['rate']);
        $this->assertEquals(0.223, $ret2['ret'][1]['payway_rate']);
        $this->assertEquals(0.23456789, $ret2['ret'][1]['bitcoin_rate']);
        $this->assertEquals(0.00234568, $ret2['ret'][1]['rate_difference']);
        $this->assertEquals(1000, $ret2['ret'][1]['amount_conv_basic']);
        $this->assertEquals(4484.3049, $ret2['ret'][1]['amount_conv']);
    }

    /**
     * 測試回傳比特幣入款紀錄帶入金額區間
     */
    public function testListEntryWithAmount()
    {
        $client = $this->createClient();

        $parameters = [
            'domain' => 2,
            'amount_min' => '100',
            'amount_max' => '500',
            'sub_total' => 1
        ];
        $client->request('GET', '/api/bitcoin_deposit/entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertCount(2, $ret['ret']);
        $this->assertEquals(600, $ret['sub_total']['amount']);

        $this->assertEquals(201801120000000003, $ret['ret'][0]['id']);
        $this->assertEquals(2, $ret['ret'][0]['domain']);
        $this->assertEquals('USD', $ret['ret'][0]['currency']);
        $this->assertEquals('USD', $ret['ret'][0]['payway_currency']);
        $this->assertEquals(500, $ret['ret'][0]['amount']);
        $this->assertEquals(68.54096, $ret['ret'][0]['bitcoin_amount']);
        $this->assertEquals(6.34, $ret['ret'][0]['rate']);
        $this->assertEquals(6.34, $ret['ret'][0]['payway_rate']);
        $this->assertEquals(0.13572468, $ret['ret'][0]['bitcoin_rate']);
        $this->assertEquals(0.00135724, $ret['ret'][0]['rate_difference']);
        $this->assertEquals(3170, $ret['ret'][0]['amount_conv_basic']);
        $this->assertEquals(500, $ret['ret'][0]['amount_conv']);

        $this->assertEquals(201801110000000001, $ret['ret'][1]['id']);
        $this->assertEquals(2, $ret['ret'][1]['domain']);
        $this->assertEquals('TWD', $ret['ret'][1]['currency']);
        $this->assertEquals('TWD', $ret['ret'][1]['payway_currency']);
        $this->assertEquals(100, $ret['ret'][1]['amount']);
        $this->assertEquals(12.469135, $ret['ret'][1]['bitcoin_amount']);
        $this->assertEquals(0.223, $ret['ret'][1]['rate']);
        $this->assertEquals(0.223, $ret['ret'][1]['payway_rate']);
        $this->assertEquals(0.12345678, $ret['ret'][1]['bitcoin_rate']);
        $this->assertEquals(0.00123457, $ret['ret'][1]['rate_difference']);
        $this->assertEquals(22.3, $ret['ret'][1]['amount_conv_basic']);
        $this->assertEquals(100, $ret['ret'][1]['amount_conv']);
    }

    /**
     * 測試回傳比特幣入款紀錄帶入比特幣金額區間
     */
    public function testListEntryWithBitcoinAmount()
    {
        $client = $this->createClient();

        $parameters = [
            'domain' => 2,
            'bitcoin_amount_min' => '60.00000001',
            'bitcoin_amount_max' => '250.00000000',
            'sub_total' => 1
        ];
        $client->request('GET', '/api/bitcoin_deposit/entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertCount(2, $ret['ret']);
        $this->assertEquals(1500, $ret['sub_total']['amount']);

        $this->assertEquals(201801120000000003, $ret['ret'][0]['id']);
        $this->assertEquals(2, $ret['ret'][0]['domain']);
        $this->assertEquals('USD', $ret['ret'][0]['currency']);
        $this->assertEquals('USD', $ret['ret'][0]['payway_currency']);
        $this->assertEquals(500, $ret['ret'][0]['amount']);
        $this->assertEquals(68.54096, $ret['ret'][0]['bitcoin_amount']);
        $this->assertEquals(6.34, $ret['ret'][0]['rate']);
        $this->assertEquals(6.34, $ret['ret'][0]['payway_rate']);
        $this->assertEquals(0.13572468, $ret['ret'][0]['bitcoin_rate']);
        $this->assertEquals(0.00135724, $ret['ret'][0]['rate_difference']);
        $this->assertEquals(3170, $ret['ret'][0]['amount_conv_basic']);
        $this->assertEquals(500, $ret['ret'][0]['amount_conv']);

        $this->assertEquals(201801110000000002, $ret['ret'][1]['id']);
        $this->assertEquals(2, $ret['ret'][1]['domain']);
        $this->assertEquals('CNY', $ret['ret'][1]['currency']);
        $this->assertEquals('TWD', $ret['ret'][1]['payway_currency']);
        $this->assertEquals(1000, $ret['ret'][1]['amount']);
        $this->assertEquals(236.91357, $ret['ret'][1]['bitcoin_amount']);
        $this->assertEquals(1, $ret['ret'][1]['rate']);
        $this->assertEquals(0.223, $ret['ret'][1]['payway_rate']);
        $this->assertEquals(0.23456789, $ret['ret'][1]['bitcoin_rate']);
        $this->assertEquals(0.00234568, $ret['ret'][1]['rate_difference']);
        $this->assertEquals(1000, $ret['ret'][1]['amount_conv_basic']);
        $this->assertEquals(4484.3049, $ret['ret'][1]['amount_conv']);
    }

    /**
     * 測試以申請出款時間及確認出款時間取得比特幣入款記錄
     */
    public function testListEntryByAtAndConfirmAt()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $dateTime = new \DateTime('now');
        $dateTime->sub(new \DateInterval('PT12H'));
        $startTime = $dateTime->format(\DateTime::ISO8601);
        $dateTime->add(new \DateInterval('PT24H'));
        $endTime = $dateTime->format(\DateTime::ISO8601);

        $parameters = [
            'domain' => 2,
            'at_start' => $startTime,
            'at_end' => $endTime,
            'confirm_at_start' => $startTime,
            'confirm_at_end' => $endTime,
        ];
        $client->request('GET', '/api/bitcoin_deposit/entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertCount(2, $ret['ret']);

        $this->assertEquals(201801120000000003, $ret['ret'][0]['id']);
        $this->assertEquals(2, $ret['ret'][0]['domain']);
        $this->assertEquals('USD', $ret['ret'][0]['currency']);
        $this->assertEquals('USD', $ret['ret'][0]['payway_currency']);
        $this->assertEquals(500, $ret['ret'][0]['amount']);
        $this->assertEquals(68.54096, $ret['ret'][0]['bitcoin_amount']);
        $this->assertEquals(6.34, $ret['ret'][0]['rate']);
        $this->assertEquals(6.34, $ret['ret'][0]['payway_rate']);
        $this->assertEquals(0.13572468, $ret['ret'][0]['bitcoin_rate']);
        $this->assertEquals(0.00135724, $ret['ret'][0]['rate_difference']);
        $this->assertEquals(3170, $ret['ret'][0]['amount_conv_basic']);
        $this->assertEquals(500, $ret['ret'][0]['amount_conv']);
        $this->assertFalse($ret['ret'][0]['process']);

        $this->assertEquals(201801110000000001, $ret['ret'][1]['id']);
        $this->assertEquals(2, $ret['ret'][1]['domain']);
        $this->assertEquals('TWD', $ret['ret'][1]['currency']);
        $this->assertEquals('TWD', $ret['ret'][1]['payway_currency']);
        $this->assertEquals(100, $ret['ret'][1]['amount']);
        $this->assertEquals(12.469135, $ret['ret'][1]['bitcoin_amount']);
        $this->assertEquals(0.223, $ret['ret'][1]['rate']);
        $this->assertEquals(0.223, $ret['ret'][1]['payway_rate']);
        $this->assertEquals(0.12345678, $ret['ret'][1]['bitcoin_rate']);
        $this->assertEquals(0.00123457, $ret['ret'][1]['rate_difference']);
        $this->assertEquals(22.3, $ret['ret'][1]['amount_conv_basic']);
        $this->assertEquals(100, $ret['ret'][1]['amount_conv']);
        $this->assertFalse($ret['ret'][1]['process']);

        $entry = $em->getRepository('BBDurianBundle:BitcoinDepositEntry')->findOneBy(['id' => 201801120000000003]);
        $this->assertEquals($entry->getAt()->format(\DateTime::ISO8601), $ret['ret'][0]['at']);
        $this->assertEquals($entry->getConfirmAt()->format(\DateTime::ISO8601), $ret['ret'][0]['confirm_at']);
    }
}
