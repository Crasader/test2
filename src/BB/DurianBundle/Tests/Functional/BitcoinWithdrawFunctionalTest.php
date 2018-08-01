<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Consumer\Poper;

class BitcoinWithdrawFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBitcoinWithdrawEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserDetailData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBitcoinWalletData',
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
        $redis->set('bitcoin_withdraw_seq', 10);
        $redis->set('cash_seq', 1000);
    }

    /**
     * 測試新增比特幣出款記錄
     */
    public function testCreate()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $client = $this->createClient();

        $parameters = [
            'currency' => 'TWD',
            'amount' => -50,
            'bitcoin_amount' => '6.6947155',
            'bitcoin_rate' => '0.13524678',
            'rate_difference' => '0.00135247',
            'deduction' => 0,
            'audit_charge' => 0,
            'audit_fee' => 0,
            'ip' => '127.0.0.1',
            'withdraw_address' => 'address1',
            'memo' => 'test',
        ];
        $client->request('POST', '/api/user/4/bitcoin_withdraw', $parameters);

        // 跑背景程式讓queue被消化
        $cashPoper = new Poper();
        $cashPoper->runPop($this->getContainer(), 'cash');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $id = $ret['ret']['id'];
        $entry = $em->getRepository('BBDurianBundle:BitcoinWithdrawEntry')->findOneBy(['id' => $id]);

        $this->assertEquals(4, $ret['ret']['user_id']);
        $this->assertEquals(2, $ret['ret']['domain']);
        $this->assertEquals(2, $ret['ret']['level_id']);
        $this->assertNull($ret['ret']['confirm_at']);
        $this->assertTrue($ret['ret']['process']);
        $this->assertFalse($ret['ret']['confirm']);
        $this->assertFalse($ret['ret']['cancel']);
        $this->assertFalse($ret['ret']['locked']);
        $this->assertFalse($ret['ret']['manual']);
        $this->assertTrue($ret['ret']['first']);
        $this->assertFalse($ret['ret']['detailModified']);
        $this->assertEquals(1001, $ret['ret']['amount_entry_id']);
        $this->assertEquals(0, $ret['ret']['previous_id']);
        $this->assertEquals('TWD', $ret['ret']['currency']);
        $this->assertEquals(-50, $ret['ret']['amount']);
        $this->assertEquals(6.6947155, $ret['ret']['bitcoin_amount']);
        $this->assertEquals(0.22300000, $ret['ret']['rate']);
        $this->assertEquals(0.13524678, $ret['ret']['bitcoin_rate']);
        $this->assertEquals(0.00135247, $ret['ret']['rate_difference']);
        $this->assertEquals(-11.15, $ret['ret']['amount_conv']);
        $this->assertEquals(0, $ret['ret']['deduction_conv']);
        $this->assertEquals(0, $ret['ret']['audit_charge_conv']);
        $this->assertEquals(0, $ret['ret']['audit_fee_conv']);
        $this->assertEquals(-11.15, $ret['ret']['real_amount_conv']);
        $this->assertEquals(0, $ret['ret']['deduction']);
        $this->assertEquals(0, $ret['ret']['audit_charge']);
        $this->assertEquals(0, $ret['ret']['audit_fee']);
        $this->assertEquals(-50, $ret['ret']['real_amount']);
        $this->assertEquals('127.0.0.1', $ret['ret']['ip']);
        $this->assertFalse($ret['ret']['control']);
        $this->assertEmpty($ret['ret']['operator']);
        $this->assertEquals('address1', $ret['ret']['withdraw_address']);
        $this->assertEmpty($ret['ret']['ref_id']);
        $this->assertEquals('test', $ret['ret']['memo']);
        $this->assertEmpty($ret['ret']['note']);

        $this->assertEquals($entry->getAt()->format(\DateTime::ISO8601), $ret['ret']['at']);

        $cEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $ret['ret']['amount_entry_id']]);

        $this->assertEquals(1341, $cEntry->getOpcode());
        $this->assertEquals('test', $cEntry->getMemo());
        $this->assertEquals(-50, $cEntry->getAmount());
        $this->assertEquals($id, $cEntry->getRefId());

        /**
         * 測試再次出款時，首次出款欄位(first)就不為true
         * 並測試使用者第二次出款使用不同的出款位置，isDetailModified是否為true
         */
        $secondParameters = [
            'currency' => 'CNY',
            'amount' => -100,
            'bitcoin_amount' => '0.133894',
            'bitcoin_rate' => '0.00135246',
            'rate_difference' => '0.00001352',
            'deduction' => 0,
            'audit_charge' => 0,
            'audit_fee' => 0,
            'ip' => '127.0.0.1',
            'withdraw_address' => 'address2',
            'memo' => '',
        ];
        $client->request('POST', '/api/user/4/bitcoin_withdraw', $secondParameters);

        $secondJson = $client->getResponse()->getContent();
        $secondRet = json_decode($secondJson, true);

        $this->assertEquals('ok', $secondRet['result']);
        $this->assertFalse($secondRet['ret']['first']);
        $this->assertTrue($secondRet['ret']['detailModified']);
        $this->assertEquals($id, $secondRet['ret']['previous_id']);
        $this->assertEquals('address2', $secondRet['ret']['withdraw_address']);
    }

    /**
     * 測試新增比特幣出款記錄,出現flush錯誤,執行RollBack CashTrans
     */
    public function testCreateButRollBack()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $user8 = $em->find('BBDurianBundle:User', 8);
        $userLevel = $em->find('BBDurianBundle:UserLevel', 8);
        $userDetail = $em->find('BBDurianBundle:UserDetail', 8);

        $idGenerator = $this->getContainer()->get('durian.cash_entry_id_generator');
        $cashEntryId = $idGenerator->generate();

        // mock entity manager
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods([
                'beginTransaction',
                'find',
                'persist',
                'flush',
                'rollback',
                'getRepository',
                'clear'
            ])
            ->getMock();

        $mockEm->expects($this->at(1))
            ->method('find')
            ->willReturn($user8);

        $mockEm->expects($this->at(2))
            ->method('find')
            ->willReturn($userLevel);

        $entityRepo= $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods([
                'findOneByUser',
                'getPreviousWithdrawEntry'
            ])
            ->getMock();

        $entityRepo->expects($this->any())
            ->method('findOneByUser')
            ->willReturn($userDetail);

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $mockEm->expects($this->at(7))
            ->method('flush')
            ->willThrowException(new \Exception('SQLSTATE[28000] [1045]'));

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);

        $parameters = [
            'currency' => 'CNY',
            'amount' => -50,
            'bitcoin_amount' => '6.6947155',
            'bitcoin_rate' => '0.13524678',
            'rate_difference' => '0.00135247',
            'deduction' => 0,
            'audit_charge' => 0,
            'audit_fee' => 0,
            'ip' => '127.0.0.1',
            'withdraw_address' => 'address1',
            'memo' => 'test',
        ];
        $client->request('POST', '/api/user/8/bitcoin_withdraw', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $redisWallet = $this->getContainer()->get('snc_redis.wallet4');

        // 檢查pre_sub資料是否有rollback
        $this->assertEquals(0, $redisWallet->hget('cash_balance_8_901', 'pre_sub'));

        // 檢查餘額資料是否有rollback
        $this->assertEquals(10000000, $redisWallet->hget('cash_balance_8_901', 'balance'));

        // 檢查cash_sync_queue內容
        $syncMsg = $redis->lpop('cash_sync_queue');
        $msg = [
            'HEAD' => 'CASHSYNCHRONIZE',
            'KEY' => 'cash_balance_8_901',
            'ERRCOUNT' => 0,
            'id' => '7',
            'user_id' => '8',
            'balance' => 1000,
            'pre_sub' => 0,
            'pre_add' => 0,
            'version' => 4,
            'currency' => '901'
        ];
        $this->assertEquals(json_encode($msg), $syncMsg);

        // 檢查cash_queue是否有資料
        $queueMsg = $redis->lpop('cash_queue');
        $this->assertInternalType('string', $queueMsg);

        // 檢查key是否有刪除
        $tRedisWallet = $this->getContainer()->get('snc_redis.wallet1');
        $this->assertNull($tRedisWallet->get("en_cashtrans_id_$cashEntryId"));

        // 檢查輸出結果
        $this->assertEquals('error', $ret['result']);
        $this->assertEquals('SQLSTATE[28000] [1045]', $ret['msg']);
    }

    /**
     * 測試比特幣連動鎖定出款紀錄
     */
    public function testLockedWithdrawForSameUser()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $lockedParameters = [
            'operator' => 'test',
            'control' => 1,
        ];
        // 鎖定出款資料
        $client->request('PUT', '/api/bitcoin_withdraw/entry/201712120000000005/locked', $lockedParameters);

        $lockedJson = $client->getResponse()->getContent();
        $lockedOutput = json_decode($lockedJson, true);

        $this->assertEquals('ok', $lockedOutput['result']);
        $this->assertCount(4, $lockedOutput['ret']);

        $this->assertEquals(201712120000000005, $lockedOutput['ret'][0]['id']);
        $this->assertEquals(7, $lockedOutput['ret'][0]['user_id']);
        $this->assertTrue($lockedOutput['ret'][0]['locked']);
        $this->assertEquals('test', $lockedOutput['ret'][0]['operator']);
        $this->assertTrue($lockedOutput['ret'][0]['control']);

        $this->assertEquals(201712120000000006, $lockedOutput['ret'][1]['id']);
        $this->assertEquals(7, $lockedOutput['ret'][1]['user_id']);
        $this->assertTrue($lockedOutput['ret'][1]['locked']);
        $this->assertEquals('test', $lockedOutput['ret'][1]['operator']);
        $this->assertTrue($lockedOutput['ret'][1]['control']);

        $this->assertEquals(201712120000000007, $lockedOutput['ret'][2]['id']);
        $this->assertEquals(7, $lockedOutput['ret'][2]['user_id']);
        $this->assertTrue($lockedOutput['ret'][2]['locked']);
        $this->assertEquals('test', $lockedOutput['ret'][2]['operator']);
        $this->assertTrue($lockedOutput['ret'][2]['control']);

        $this->assertEquals(201712120000000008, $lockedOutput['ret'][3]['id']);
        $this->assertEquals(7, $lockedOutput['ret'][3]['user_id']);
        $this->assertTrue($lockedOutput['ret'][3]['locked']);
        $this->assertEquals('test', $lockedOutput['ret'][3]['operator']);
        $this->assertTrue($lockedOutput['ret'][3]['control']);

        // 操作紀錄檢查
        $message = [
            '@locked:false=>true',
            '@operator:=>test',
            '@control:1',
        ];
        $logOperation1 = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('bitcoin_withdraw_entry', $logOperation1->getTableName());
        $this->assertEquals('@id:201712120000000005', $logOperation1->getMajorKey());
        $this->assertEquals(implode(', ', $message), $logOperation1->getMessage());

        $logOperation2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('bitcoin_withdraw_entry', $logOperation2->getTableName());
        $this->assertEquals('@id:201712120000000006', $logOperation2->getMajorKey());
        $this->assertEquals(implode(', ', $message), $logOperation2->getMessage());

        $logOperation3 = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertEquals('bitcoin_withdraw_entry', $logOperation3->getTableName());
        $this->assertEquals('@id:201712120000000007', $logOperation3->getMajorKey());
        $this->assertEquals(implode(', ', $message), $logOperation3->getMessage());

        $logOperation4 = $emShare->find('BBDurianBundle:LogOperation', 4);
        $this->assertEquals('bitcoin_withdraw_entry', $logOperation4->getTableName());
        $this->assertEquals('@id:201712120000000008', $logOperation4->getMajorKey());
        $this->assertEquals(implode(', ', $message), $logOperation4->getMessage());
    }

    /**
     * 測試比特幣解除鎖定出款紀錄
     */
    public function testUnlockedWithdraw()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $lockedParameters = [
            'operator' => 'test',
            'control' => 1,
        ];
        // 鎖定出款資料
        $client->request('PUT', '/api/bitcoin_withdraw/entry/201712120000000005/locked', $lockedParameters);

        $lockedJson = $client->getResponse()->getContent();
        $lockedOutput = json_decode($lockedJson, true);

        $this->assertEquals('ok', $lockedOutput['result']);
        $this->assertTrue($lockedOutput['ret'][0]['locked']);

        $unlockedParameters = [
            'operator' => 'test',
            'control' => 1,
            'force' => 0,
        ];
        // 解除鎖定出款資料
        $client->request('PUT', '/api/bitcoin_withdraw/entry/201712120000000005/unlocked', $unlockedParameters);

        $unlockedJson = $client->getResponse()->getContent();
        $unlockedOutput = json_decode($unlockedJson, true);

        $this->assertEquals('ok', $unlockedOutput['result']);
        $this->assertFalse($unlockedOutput['ret']['locked']);
        $this->assertEquals('', $unlockedOutput['ret']['operator']);
        $this->assertFalse($unlockedOutput['ret']['control']);

        // 檢查解除鎖定操作紀錄
        $message = [
            '@locked:true=>false',
            '@operator:test=>',
            '@control:1=>',
        ];
        $logOperation1 = $emShare->find('BBDurianBundle:LogOperation', 5);
        $this->assertEquals('bitcoin_withdraw_entry', $logOperation1->getTableName());
        $this->assertEquals('@id:201712120000000005', $logOperation1->getMajorKey());
        $this->assertEquals(implode(', ', $message), $logOperation1->getMessage());
    }

    /**
     * 測試比特幣強制解除鎖定出款紀錄
     */
    public function testForceUnlockedWithdraw()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $lockedParameters = [
            'operator' => 'test',
            'control' => 1,
        ];
        // 鎖定出款資料
        $client->request('PUT', '/api/bitcoin_withdraw/entry/201712120000000005/locked', $lockedParameters);

        $lockedJson = $client->getResponse()->getContent();
        $lockedOutput = json_decode($lockedJson, true);

        $this->assertEquals('ok', $lockedOutput['result']);
        $this->assertTrue($lockedOutput['ret'][0]['locked']);

        $unlockedParameters = [
            'operator' => 'differentOperator',
            'control' => 1,
            'force' => 1,
        ];
        // 解除鎖定出款資料
        $client->request('PUT', '/api/bitcoin_withdraw/entry/201712120000000005/unlocked', $unlockedParameters);

        $unlockedJson = $client->getResponse()->getContent();
        $unlockedOutput = json_decode($unlockedJson, true);

        $this->assertEquals('ok', $unlockedOutput['result']);
        $this->assertFalse($unlockedOutput['ret']['locked']);

        // 檢查解除鎖定操作紀錄
        $message = [
            '@locked:true=>false',
            '@operator:test=>',
            '@control:1=>',
        ];
        $logOperation1 = $emShare->find('BBDurianBundle:LogOperation', 5);
        $this->assertEquals('bitcoin_withdraw_entry', $logOperation1->getTableName());
        $this->assertEquals('@id:201712120000000005', $logOperation1->getMajorKey());
        $this->assertEquals(implode(', ', $message), $logOperation1->getMessage());
    }

    /**
     * 測試比特幣取消出款會連動取消該會員該筆明細之後所有未處理明細
     */
    public function testCancelWithdrawByCancelNextProcessedEntries()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $ceRepo = $emEntry->getRepository('BBDurianBundle:CashEntry');
        $client = $this->createClient();

        $lockedParameters = [
            'operator' => 'test',
            'control' => 1,
        ];
        // 鎖定第一筆出款資料
        $client->request('PUT', '/api/bitcoin_withdraw/entry/201712120000000008/locked', $lockedParameters);

        // 申請第二筆出款
        $parameters2 = [
            'currency' => 'TWD',
            'amount' => -50,
            'bitcoin_amount' => '6.6947155',
            'bitcoin_rate' => '0.13524678',
            'rate_difference' => '0.00135247',
            'deduction' => 0,
            'audit_charge' => 0,
            'audit_fee' => 0,
            'ip' => '127.0.0.1',
            'withdraw_address' => 'address1',
            'memo' => 'test',
        ];
        $client->request('POST', '/api/user/7/bitcoin_withdraw', $parameters2);

        // 跑背景程式讓queue被消化
        $cashPoper2 = new Poper();
        $cashPoper2->runPop($this->getContainer(), 'cash');

        $createJson2 = $client->getResponse()->getContent();
        $createRet2 = json_decode($createJson2, true);
        $secondId = $createRet2['ret']['id'];

        // 不同操作者鎖定第二筆出款資料
        $lockedParameters2 = [
            'operator' => 'test2',
            'control' => 1,
        ];
        $client->request('PUT', "/api/bitcoin_withdraw/entry/$secondId/locked", $lockedParameters2);

        // 申請第三筆出款
        $parameters3 = [
            'currency' => 'TWD',
            'amount' => -100,
            'bitcoin_amount' => '13.389431',
            'bitcoin_rate' => '0.13524678',
            'rate_difference' => '0.00135247',
            'deduction' => 0,
            'audit_charge' => 0,
            'audit_fee' => 0,
            'ip' => '127.0.0.1',
            'withdraw_address' => 'address1',
            'memo' => 'test',
        ];
        $client->request('POST', '/api/user/7/bitcoin_withdraw', $parameters3);

        // 跑背景程式讓queue被消化
        $cashPoper3 = new Poper();
        $cashPoper3->runPop($this->getContainer(), 'cash');

        $createJson3 = $client->getResponse()->getContent();
        $createRet3 = json_decode($createJson3, true);
        $thridId = $createRet3['ret']['id'];

        // 第二筆資料取消出款
        $params = [
            'operator' => 'test2',
            'control' => 1,
        ];
        $client->request('PUT', "/api/bitcoin_withdraw/entry/$secondId/cancel", $params);

        // 跑背景程式讓queue被消化
        $cashPoper = new Poper();
        $cashPoper->runPop($this->getContainer(), 'cash');

        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals($secondId, $ret['ret']['withdraw_entry'][0]['id']);
        $this->assertTrue($ret['ret']['withdraw_entry'][0]['cancel']);
        $this->assertFalse($ret['ret']['withdraw_entry'][0]['locked']);
        $this->assertEquals(1000, $ret['ret']['cash']['balance']);
        $this->assertEquals(0, $ret['ret']['cash']['pre_sub']);
        $this->assertEquals(0, $ret['ret']['cash']['pre_add']);
        $this->assertEquals(50, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(900, $ret['ret']['entry'][0]['balance']);
        $this->assertEquals(1342, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals($secondId, $ret['ret']['entry'][0]['ref_id']);

        $cash = $em->find('BBDurianBundle:Cash', 7);
        $centry = $ceRepo->findOneBy(['id' => $ret['ret']['entry'][1]['id']]);

        $this->assertEquals($thridId, $ret['ret']['withdraw_entry'][1]['id']);
        $this->assertTrue($ret['ret']['withdraw_entry'][1]['cancel']);
        $this->assertFalse($ret['ret']['withdraw_entry'][1]['locked']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(1000, $ret['ret']['entry'][1]['balance']);
        $this->assertEquals($centry->getOpcode(), $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals($centry->getRefId(), $ret['ret']['entry'][1]['ref_id']);

        // 檢查redis內餘額
        $redisWallet = $this->getContainer()->get('snc_redis.wallet3');
        $cashKey = 'cash_balance_7_901';
        $this->assertEquals($cash->getBalance()* 10000, $redisWallet->hget($cashKey, 'balance'));
        $this->assertEquals($cash->getPreAdd() * 10000, $redisWallet->hget($cashKey, 'pre_add'));
        $this->assertEquals($cash->getPreSub() * 10000, $redisWallet->hget($cashKey, 'pre_sub'));

        // 檢查第一筆出款明細還是鎖定狀態
        $entry = $em->getRepository('BBDurianBundle:BitcoinWithdrawEntry')->findOneBy(['id' => 201712120000000008]);
        $this->assertTrue($entry->isLocked());

        // 驗證operationLog且兩筆都有記錄
        $message = [
            '@cancel:false=>true',
            '@operator:=>test2',
            '@control:=>1',
        ];
        $logOperation6 = $emShare->find('BBDurianBundle:LogOperation', 6);
        $this->assertEquals('bitcoin_withdraw_entry', $logOperation6->getTableName());
        $this->assertEquals(sprintf('@id:%s', $secondId), $logOperation6->getMajorKey());
        $this->assertEquals('@cancel:false=>true', $logOperation6->getMessage());

        $logOperation7 = $emShare->find('BBDurianBundle:LogOperation', 7);
        $this->assertEquals('bitcoin_withdraw_entry', $logOperation7->getTableName());
        $this->assertEquals(sprintf('@id:%s', $thridId), $logOperation7->getMajorKey());
        $this->assertEquals(implode(', ', $message), $logOperation7->getMessage());
    }

    /**
     * 測試比特幣確認人工出款
     */
    public function testConfirmWithdrawWithManual()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default');
        $client = $this->createClient();

        // 鎖定出款資料
        $lockedParameters = [
            'operator' => 'test',
            'control' => 1,
        ];
        $client->request('PUT', '/api/bitcoin_withdraw/entry/201712120000000008/locked', $lockedParameters);

        $lockedJson = $client->getResponse()->getContent();
        $lockedOutput = json_decode($lockedJson, true);

        $this->assertEquals('ok', $lockedOutput['result']);
        $this->assertTrue($lockedOutput['ret'][3]['locked']);

        // 測試確認出款成功
        $parameters = [
            'operator' => 'test',
            'control' => 1,
            'manual' => 1,
        ];
        $client->request('PUT', '/api/bitcoin_withdraw/entry/201712120000000008/confirm', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertTrue($ret['ret']['confirm']);
        $this->assertFalse($ret['ret']['locked']);
        $this->assertTrue($ret['ret']['manual']);
        $this->assertEquals('', $ret['ret']['ref_id']);

        // 檢查使用者出入款統計資料
        $userStat = $em->find('BBDurianBundle:UserStat', 7);
        $this->assertEquals(1, $userStat->getBitcoinWithdrawCount());
        $this->assertEquals(400*6.34, $userStat->getBitcoinWithdrawTotal());
        $this->assertEquals(400*6.34, $userStat->getBitcoinWithdrawMax());

        // 檢查操作紀錄
        $logOperation5 = $emShare->find('BBDurianBundle:LogOperation', 5);
        $this->assertEquals('bitcoin_withdraw_entry', $logOperation5->getTableName());
        $this->assertEquals('@id:201712120000000008', $logOperation5->getMajorKey());
        $this->assertEquals('@confirm:true, @manual:false=>true', $logOperation5->getMessage());

        // 檢查出入款統計資料操作紀錄
        $message = [
            '@bitcoin_withdraw_count:0=>1',
            '@bitcoin_withdraw_total:0=>2536',
            '@bitcoin_withdraw_max:0=>2536.0000',
            '@modified_at:',
        ];
        $logOperation6 = $emShare->find('BBDurianBundle:LogOperation', 6);
        $this->assertEquals('user_stat', $logOperation6->getTableName());
        $this->assertEquals('@user_id:7', $logOperation6->getMajorKey());
        $this->assertContains(implode(', ', $message), $logOperation6->getMessage());

        $queueName = 'cash_deposit_withdraw_queue';
        $this->assertEquals(1, $redis->llen($queueName));

        $queue = json_decode($redis->rpop($queueName), true);

        $this->assertEquals(0, $queue['ERRCOUNT']);
        $this->assertEquals(7, $queue['user_id']);
        $this->assertFalse($queue['deposit']);
        $this->assertTrue($queue['withdraw']);
        $this->assertNotNull($queue['withdraw_at']);
    }

    /**
     * 測試比特幣確認自動出款
     */
    public function testConfirmWithdrawWithAuto()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default');
        $client = $this->createClient();

        // 鎖定出款資料
        $lockedParameters = [
            'operator' => 'test',
            'control' => 1,
        ];
        $client->request('PUT', '/api/bitcoin_withdraw/entry/201712120000000008/locked', $lockedParameters);

        $lockedJson = $client->getResponse()->getContent();
        $lockedOutput = json_decode($lockedJson, true);

        $this->assertEquals('ok', $lockedOutput['result']);
        $this->assertTrue($lockedOutput['ret'][3]['locked']);

        $mockBlockChain = $this->getMockBuilder('BB\DurianBundle\Payment\BlockChain')
            ->disableOriginalConstructor()
            ->setMethods(['makePayment'])
            ->getMock();
        $mockBlockChain->expects($this->any())
            ->method('makePayment')
            ->willReturn('txId');
        $confirmClient = $this->createClient();
        $confirmClient->getContainer()->set('durian.block_chain', $mockBlockChain);

        // 測試確認出款成功
        $parameters = [
            'operator' => 'test',
            'control' => 1,
            'manual' => 0,
            'bitcoin_wallet_id' => 4,
        ];
        $confirmClient->request('PUT', '/api/bitcoin_withdraw/entry/201712120000000008/confirm', $parameters);

        $json = $confirmClient->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertTrue($ret['ret']['confirm']);
        $this->assertFalse($ret['ret']['locked']);
        $this->assertFalse($ret['ret']['manual']);
        $this->assertEquals('txId', $ret['ret']['ref_id']);

        // 檢查使用者出入款統計資料
        $userStat = $em->find('BBDurianBundle:UserStat', 7);
        $this->assertEquals(1, $userStat->getBitcoinWithdrawCount());
        $this->assertEquals(400*6.34, $userStat->getBitcoinWithdrawTotal());
        $this->assertEquals(400*6.34, $userStat->getBitcoinWithdrawMax());

        // 檢查操作紀錄
        $logOperation5 = $emShare->find('BBDurianBundle:LogOperation', 5);
        $this->assertEquals('bitcoin_withdraw_entry', $logOperation5->getTableName());
        $this->assertEquals('@id:201712120000000008', $logOperation5->getMajorKey());
        $this->assertEquals('@confirm:true, @ref_id:txId', $logOperation5->getMessage());

        // 檢查出入款統計資料操作紀錄
        $message = [
            '@bitcoin_withdraw_count:0=>1',
            '@bitcoin_withdraw_total:0=>2536',
            '@bitcoin_withdraw_max:0=>2536.0000',
            '@modified_at:',
        ];
        $logOperation6 = $emShare->find('BBDurianBundle:LogOperation', 6);
        $this->assertEquals('user_stat', $logOperation6->getTableName());
        $this->assertEquals('@user_id:7', $logOperation6->getMajorKey());
        $this->assertContains(implode(', ', $message), $logOperation6->getMessage());

        $queueName = 'cash_deposit_withdraw_queue';
        $this->assertEquals(1, $redis->llen($queueName));

        $queue = json_decode($redis->rpop($queueName), true);

        $this->assertEquals(0, $queue['ERRCOUNT']);
        $this->assertEquals(7, $queue['user_id']);
        $this->assertFalse($queue['deposit']);
        $this->assertTrue($queue['withdraw']);
        $this->assertNotNull($queue['withdraw_at']);
    }

    /**
     * 測試使用id取得比特幣出款記錄
     */
    public function testGetEntry()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/bitcoin_withdraw/entry/201712120000000008');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(201712120000000008, $ret['ret']['id']);
        $this->assertEquals(7, $ret['ret']['user_id']);
        $this->assertEquals(-400, $ret['ret']['amount']);
        $this->assertEquals(92.888884, $ret['ret']['bitcoin_amount']);
        $this->assertEquals(6.34, $ret['ret']['rate']);
        $this->assertEquals(0.23456789, $ret['ret']['bitcoin_rate']);
        $this->assertEquals(0.00234568, $ret['ret']['rate_difference']);
        $this->assertEquals(-2536, $ret['ret']['amount_conv']);
        $this->assertEquals(-2536, $ret['ret']['real_amount_conv']);
        $this->assertEquals(0, $ret['ret']['deduction']);
        $this->assertEquals(0, $ret['ret']['audit_charge']);
        $this->assertEquals(0, $ret['ret']['audit_fee']);
        $this->assertEquals(-400, $ret['ret']['real_amount']);
        $this->assertEquals('address8', $ret['ret']['withdraw_address']);
        $this->assertEquals('USD', $ret['ret']['currency']);
        $this->assertTrue($ret['ret']['detailModified']);
    }

    /**
     * 測試修改比特幣出款明細(只有備註)
     */
    public function testSetBitcoinWithdrawEntryMemo()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $client = $this->createClient();

        $client->request('PUT', '/api/bitcoin_withdraw/201712120000000008/memo', ['memo' => 'testmemo']);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals("bitcoin_withdraw_entry", $logOperation->getTableName());
        $this->assertEquals("@id:201712120000000008", $logOperation->getMajorKey());
        $this->assertEquals("@memo:=>testmemo", $logOperation->getMessage());

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals('testmemo', $ret['ret']['memo']);
    }

    /**
     * 測試回傳比特幣出款紀錄
     */
    public function testGetWithdrawEntriesList()
    {
        $client = $this->createClient();

        $parameters1 = ['domain' => 2];
        $client->request('GET', '/api/bitcoin_withdraw/entry/list', $parameters1);

        $json1 = $client->getResponse()->getContent();
        $ret1 = json_decode($json1, true);

        $this->assertEquals('ok', $ret1['result']);
        $this->assertCount(10, $ret1['ret']);
        $this->assertEquals(201712120000000001, $ret1['ret'][9]['id']);
        $this->assertEquals(2, $ret1['ret'][9]['domain']);
        $this->assertEquals('CNY', $ret1['ret'][9]['currency']);
        $this->assertEquals(-100, $ret1['ret'][9]['amount']);
        $this->assertEquals(12.222221, $ret1['ret'][9]['bitcoin_amount']);
        $this->assertEquals(1, $ret1['ret'][9]['rate']);
        $this->assertEquals(0.12345678, $ret1['ret'][9]['bitcoin_rate']);
        $this->assertEquals(0.00123457, $ret1['ret'][9]['rate_difference']);
        $this->assertEquals(-100, $ret1['ret'][9]['amount_conv']);
        $this->assertEquals('address1', $ret1['ret'][9]['withdraw_address']);

        // 測試搜尋幣別帶入小計資料
        $parameters2 = [
            'domain' => 2,
            'currency' => 'CNY',
            'sub_total' => 1,
        ];
        $client->request('GET', '/api/bitcoin_withdraw/entry/list', $parameters2);

        $json2 = $client->getResponse()->getContent();
        $ret2 = json_decode($json2, true);

        $this->assertEquals('ok', $ret2['result']);
        $this->assertCount(4, $ret2['ret']);
        $this->assertEquals(201712120000000001, $ret2['ret'][3]['id']);
        $this->assertEquals(2, $ret2['ret'][3]['domain']);
        $this->assertEquals('CNY', $ret2['ret'][3]['currency']);
        $this->assertEquals(-100, $ret2['ret'][3]['amount']);
        $this->assertEquals(12.222221, $ret2['ret'][3]['bitcoin_amount']);
        $this->assertEquals(1, $ret2['ret'][3]['rate']);
        $this->assertEquals(0.12345678, $ret2['ret'][3]['bitcoin_rate']);
        $this->assertEquals(0.00123457, $ret2['ret'][3]['rate_difference']);
        $this->assertEquals(-100, $ret2['ret'][3]['amount_conv']);
        $this->assertEquals('address1', $ret2['ret'][3]['withdraw_address']);
        $this->assertEquals(-1000, $ret2['sub_total']['amount']);

        // 測試搜尋確認過的單
        $parameters3 = [
            'domain' => 2,
            'confirm' => 1,
        ];
        $client->request('GET', '/api/bitcoin_withdraw/entry/list', $parameters3);

        $json3 = $client->getResponse()->getContent();
        $ret3 = json_decode($json3, true);

        $this->assertEquals('ok', $ret3['result']);
        $this->assertCount(2, $ret3['ret']);
        $this->assertEquals(201712120000000002, $ret3['ret'][1]['id']);
        $this->assertEquals(2, $ret3['ret'][1]['domain']);
        $this->assertEquals('CNY', $ret3['ret'][1]['currency']);
        $this->assertEquals(-200, $ret3['ret'][1]['amount']);
        $this->assertEquals(24.444442, $ret3['ret'][1]['bitcoin_amount']);
        $this->assertEquals(1, $ret3['ret'][1]['rate']);
        $this->assertEquals(0.12345678, $ret3['ret'][1]['bitcoin_rate']);
        $this->assertEquals(0.00123457, $ret3['ret'][1]['rate_difference']);
        $this->assertEquals(-200, $ret3['ret'][1]['amount_conv']);
        $this->assertEquals('address2', $ret3['ret'][1]['withdraw_address']);
        $this->assertTrue($ret3['ret'][1]['confirm']);

        // 測試搜尋已取消的單
        $parameters4 = [
            'domain' => 2,
            'cancel' => 1,
        ];
        $client->request('GET', '/api/bitcoin_withdraw/entry/list', $parameters4);

        $json4 = $client->getResponse()->getContent();
        $ret4 = json_decode($json4, true);

        $this->assertEquals('ok', $ret4['result']);
        $this->assertCount(1, $ret4['ret']);
        $this->assertEquals(201712120000000001, $ret4['ret'][0]['id']);
        $this->assertEquals(2, $ret4['ret'][0]['domain']);
        $this->assertEquals('CNY', $ret4['ret'][0]['currency']);
        $this->assertEquals(-100, $ret4['ret'][0]['amount']);
        $this->assertEquals(12.222221, $ret4['ret'][0]['bitcoin_amount']);
        $this->assertEquals(1, $ret4['ret'][0]['rate']);
        $this->assertEquals(0.12345678, $ret4['ret'][0]['bitcoin_rate']);
        $this->assertEquals(0.00123457, $ret4['ret'][0]['rate_difference']);
        $this->assertEquals(-100, $ret4['ret'][0]['amount_conv']);
        $this->assertEquals('address1', $ret4['ret'][0]['withdraw_address']);
        $this->assertTrue($ret4['ret'][0]['cancel']);

        // 測試搜尋鎖定的單
        $parameters5 = [
            'domain' => 2,
            'locked' => 1,
        ];
        $client->request('GET', '/api/bitcoin_withdraw/entry/list', $parameters5);

        $json5 = $client->getResponse()->getContent();
        $ret5 = json_decode($json5, true);

        $this->assertEquals('ok', $ret5['result']);
        $this->assertCount(2, $ret5['ret']);
        $this->assertEquals(201712120000000003, $ret5['ret'][1]['id']);
        $this->assertEquals(2, $ret5['ret'][1]['domain']);
        $this->assertEquals('CNY', $ret5['ret'][1]['currency']);
        $this->assertEquals(-300, $ret5['ret'][1]['amount']);
        $this->assertEquals(36.666663, $ret5['ret'][1]['bitcoin_amount']);
        $this->assertEquals(1, $ret5['ret'][1]['rate']);
        $this->assertEquals(0.12345678, $ret5['ret'][1]['bitcoin_rate']);
        $this->assertEquals(0.00123457, $ret5['ret'][1]['rate_difference']);
        $this->assertEquals(-300, $ret5['ret'][1]['amount_conv']);
        $this->assertEquals('address3', $ret5['ret'][1]['withdraw_address']);
        $this->assertTrue($ret5['ret'][1]['locked']);

        // 測試搜尋處理中的單
        $parameters6 = [
            'domain' => 2,
            'process' => 1,
        ];
        $client->request('GET', '/api/bitcoin_withdraw/entry/list', $parameters6);

        $json6 = $client->getResponse()->getContent();
        $ret6 = json_decode($json6, true);

        $this->assertEquals('ok', $ret6['result']);
        $this->assertCount(7, $ret6['ret']);
        $this->assertEquals(201712120000000003, $ret6['ret'][6]['id']);
        $this->assertEquals(2, $ret6['ret'][6]['domain']);
        $this->assertEquals('CNY', $ret6['ret'][6]['currency']);
        $this->assertEquals(-300, $ret6['ret'][6]['amount']);
        $this->assertEquals(36.666663, $ret6['ret'][6]['bitcoin_amount']);
        $this->assertEquals(1, $ret6['ret'][6]['rate']);
        $this->assertEquals(0.12345678, $ret6['ret'][6]['bitcoin_rate']);
        $this->assertEquals(0.00123457, $ret6['ret'][6]['rate_difference']);
        $this->assertEquals(-300, $ret6['ret'][6]['amount_conv']);
        $this->assertEquals('address3', $ret6['ret'][6]['withdraw_address']);
        $this->assertTrue($ret6['ret'][6]['process']);

        // 測試搜尋首次出款的單
        $parameters7 = [
            'domain' => 2,
            'first' => 1,
        ];
        $client->request('GET', '/api/bitcoin_withdraw/entry/list', $parameters7);

        $json7 = $client->getResponse()->getContent();
        $ret7 = json_decode($json7, true);

        $this->assertEquals('ok', $ret7['result']);
        $this->assertCount(4, $ret7['ret']);
        $this->assertEquals(201712120000000001, $ret7['ret'][3]['id']);
        $this->assertEquals(2, $ret7['ret'][3]['domain']);
        $this->assertEquals('CNY', $ret7['ret'][3]['currency']);
        $this->assertEquals(-100, $ret7['ret'][3]['amount']);
        $this->assertEquals(12.222221, $ret7['ret'][3]['bitcoin_amount']);
        $this->assertEquals(1, $ret7['ret'][3]['rate']);
        $this->assertEquals(0.12345678, $ret7['ret'][3]['bitcoin_rate']);
        $this->assertEquals(0.00123457, $ret7['ret'][3]['rate_difference']);
        $this->assertEquals(-100, $ret7['ret'][3]['amount_conv']);
        $this->assertEquals('address1', $ret7['ret'][3]['withdraw_address']);
        $this->assertTrue($ret7['ret'][3]['first']);

        // 測試搜尋自動出款的單
        $parameters8 = [
            'domain' => 2,
            'confirm' => 1,
            'manual' => 0,
        ];
        $client->request('GET', '/api/bitcoin_withdraw/entry/list', $parameters8);

        $json8 = $client->getResponse()->getContent();
        $ret8 = json_decode($json8, true);

        $this->assertEquals('ok', $ret8['result']);
        $this->assertCount(1, $ret8['ret']);
        $this->assertEquals(201712120000000009, $ret8['ret'][0]['id']);
        $this->assertEquals(2, $ret8['ret'][0]['domain']);
        $this->assertEquals('USD', $ret8['ret'][0]['currency']);
        $this->assertEquals(-100, $ret8['ret'][0]['amount']);
        $this->assertEquals(23.222221, $ret8['ret'][0]['bitcoin_amount']);
        $this->assertEquals(6.34, $ret8['ret'][0]['rate']);
        $this->assertEquals(0.23456789, $ret8['ret'][0]['bitcoin_rate']);
        $this->assertEquals(0.00234568, $ret8['ret'][0]['rate_difference']);
        $this->assertEquals(-634, $ret8['ret'][0]['amount_conv']);
        $this->assertEquals('address9', $ret8['ret'][0]['withdraw_address']);
        $this->assertTrue($ret8['ret'][0]['confirm']);
        $this->assertFalse($ret8['ret'][0]['manual']);

        // 測試搜尋同一使用者詳細資料被修改過出款的單
        $parameters9 = [
            'domain' => 2,
            'user_id' => 6,
            'detail_modified' => 1,
        ];
        $client->request('GET', '/api/bitcoin_withdraw/entry/list', $parameters9);

        $json9 = $client->getResponse()->getContent();
        $ret9 = json_decode($json9, true);

        $this->assertEquals('ok', $ret9['result']);
        $this->assertCount(2, $ret9['ret']);
        $this->assertEquals(201712120000000002, $ret9['ret'][1]['id']);
        $this->assertEquals(2, $ret9['ret'][1]['domain']);
        $this->assertEquals('CNY', $ret9['ret'][1]['currency']);
        $this->assertEquals(-200, $ret9['ret'][1]['amount']);
        $this->assertEquals(24.444442, $ret9['ret'][1]['bitcoin_amount']);
        $this->assertEquals(1, $ret9['ret'][1]['rate']);
        $this->assertEquals(0.12345678, $ret9['ret'][1]['bitcoin_rate']);
        $this->assertEquals(0.00123457, $ret9['ret'][1]['rate_difference']);
        $this->assertEquals(-200, $ret9['ret'][1]['amount_conv']);
        $this->assertEquals('address2', $ret9['ret'][1]['withdraw_address']);
        $this->assertTrue($ret9['ret'][1]['detailModified']);

        // 測試使用出款金額交易明細id搜尋比特幣出款單
        $parameters10 = [
            'domain' => 2,
            'amount_entry_id' => 9,
        ];
        $client->request('GET', '/api/bitcoin_withdraw/entry/list', $parameters10);

        $json10 = $client->getResponse()->getContent();
        $ret10 = json_decode($json10, true);

        $this->assertEquals('ok', $ret10['result']);
        $this->assertCount(1, $ret10['ret']);
        $this->assertEquals(201712120000000009, $ret10['ret'][0]['id']);
        $this->assertEquals(2, $ret10['ret'][0]['domain']);
        $this->assertEquals('USD', $ret10['ret'][0]['currency']);
        $this->assertEquals(-100, $ret10['ret'][0]['amount']);
        $this->assertEquals(23.222221, $ret10['ret'][0]['bitcoin_amount']);
        $this->assertEquals(6.34, $ret10['ret'][0]['rate']);
        $this->assertEquals(0.23456789, $ret10['ret'][0]['bitcoin_rate']);
        $this->assertEquals(0.00234568, $ret10['ret'][0]['rate_difference']);
        $this->assertEquals(-634, $ret10['ret'][0]['amount_conv']);
        $this->assertEquals('address9', $ret10['ret'][0]['withdraw_address']);
        $this->assertEquals(9, $ret10['ret'][0]['amount_entry_id']);

        // 測試使用上一筆出款明細id搜尋比特幣出款單
        $parameters11 = [
            'domain' => 2,
            'previous_id' => 201712120000000007,
        ];
        $client->request('GET', '/api/bitcoin_withdraw/entry/list', $parameters11);

        $json11 = $client->getResponse()->getContent();
        $ret11 = json_decode($json11, true);

        $this->assertEquals('ok', $ret11['result']);
        $this->assertCount(1, $ret11['ret']);
        $this->assertEquals(201712120000000008, $ret11['ret'][0]['id']);
        $this->assertEquals(2, $ret11['ret'][0]['domain']);
        $this->assertEquals('USD', $ret11['ret'][0]['currency']);
        $this->assertEquals(-400, $ret11['ret'][0]['amount']);
        $this->assertEquals(92.888884, $ret11['ret'][0]['bitcoin_amount']);
        $this->assertEquals(6.34, $ret11['ret'][0]['rate']);
        $this->assertEquals(0.23456789, $ret11['ret'][0]['bitcoin_rate']);
        $this->assertEquals(0.00234568, $ret11['ret'][0]['rate_difference']);
        $this->assertEquals(-2536, $ret11['ret'][0]['amount_conv']);
        $this->assertEquals('address8', $ret11['ret'][0]['withdraw_address']);
        $this->assertEquals(201712120000000007, $ret11['ret'][0]['previous_id']);

        // 測試建單ip搜尋比特幣出款單
        $parameters12 = [
            'domain' => 2,
            'ip' => '127.0.0.1',
        ];
        $client->request('GET', '/api/bitcoin_withdraw/entry/list', $parameters12);

        $json12 = $client->getResponse()->getContent();
        $ret12 = json_decode($json12, true);

        $this->assertEquals('ok', $ret12['result']);
        $this->assertCount(10, $ret12['ret']);
        $this->assertEquals(201712120000000001, $ret12['ret'][9]['id']);
        $this->assertEquals(2, $ret12['ret'][9]['domain']);
        $this->assertEquals('CNY', $ret12['ret'][9]['currency']);
        $this->assertEquals(-100, $ret12['ret'][9]['amount']);
        $this->assertEquals(12.222221, $ret12['ret'][9]['bitcoin_amount']);
        $this->assertEquals(1, $ret12['ret'][9]['rate']);
        $this->assertEquals(0.12345678, $ret12['ret'][9]['bitcoin_rate']);
        $this->assertEquals(0.00123457, $ret12['ret'][9]['rate_difference']);
        $this->assertEquals(-100, $ret12['ret'][9]['amount_conv']);
        $this->assertEquals('address1', $ret12['ret'][9]['withdraw_address']);
        $this->assertEquals('127.0.0.1', $ret12['ret'][9]['ip']);

        // 測試搜尋同一操作者處理出款的單
        $parameters13 = [
            'domain' => 2,
            'operator' => 'operatorTest2',
            'control' => 1,
        ];
        $client->request('GET', '/api/bitcoin_withdraw/entry/list', $parameters13);

        $json13 = $client->getResponse()->getContent();
        $ret13 = json_decode($json13, true);

        $this->assertEquals('ok', $ret13['result']);
        $this->assertCount(2, $ret13['ret']);
        $this->assertEquals(201712120000000002, $ret13['ret'][1]['id']);
        $this->assertEquals(2, $ret13['ret'][1]['domain']);
        $this->assertEquals('CNY', $ret13['ret'][1]['currency']);
        $this->assertEquals(-200, $ret13['ret'][1]['amount']);
        $this->assertEquals(24.444442, $ret13['ret'][1]['bitcoin_amount']);
        $this->assertEquals(1, $ret13['ret'][1]['rate']);
        $this->assertEquals(0.12345678, $ret13['ret'][1]['bitcoin_rate']);
        $this->assertEquals(0.00123457, $ret13['ret'][1]['rate_difference']);
        $this->assertEquals(-200, $ret13['ret'][1]['amount_conv']);
        $this->assertEquals('address2', $ret13['ret'][1]['withdraw_address']);
        $this->assertEquals('operatorTest2', $ret13['ret'][1]['operator']);
        $this->assertTrue($ret13['ret'][1]['control']);

        // 測試搜尋同一操作者處理出款的單
        $parameters14 = [
            'domain' => 2,
            'ref_id' => 'refId1',
        ];
        $client->request('GET', '/api/bitcoin_withdraw/entry/list', $parameters14);

        $json14 = $client->getResponse()->getContent();
        $ret14 = json_decode($json14, true);

        $this->assertEquals('ok', $ret14['result']);
        $this->assertCount(1, $ret14['ret']);
        $this->assertEquals(201712120000000009, $ret14['ret'][0]['id']);
        $this->assertEquals(2, $ret14['ret'][0]['domain']);
        $this->assertEquals('USD', $ret14['ret'][0]['currency']);
        $this->assertEquals(-100, $ret14['ret'][0]['amount']);
        $this->assertEquals(23.222221, $ret14['ret'][0]['bitcoin_amount']);
        $this->assertEquals(6.34, $ret14['ret'][0]['rate']);
        $this->assertEquals(0.23456789, $ret14['ret'][0]['bitcoin_rate']);
        $this->assertEquals(0.00234568, $ret14['ret'][0]['rate_difference']);
        $this->assertEquals(-634, $ret14['ret'][0]['amount_conv']);
        $this->assertEquals('address9', $ret14['ret'][0]['withdraw_address']);
        $this->assertEquals('refId1', $ret14['ret'][0]['ref_id']);

        // 測試搜尋同一出款位址的單
        $parameters15 = [
            'domain' => 2,
            'withdraw_address' => 'address3',
        ];
        $client->request('GET', '/api/bitcoin_withdraw/entry/list', $parameters15);

        $json15 = $client->getResponse()->getContent();
        $ret15 = json_decode($json15, true);

        $this->assertEquals('ok', $ret15['result']);
        $this->assertCount(2, $ret15['ret']);
        $this->assertEquals(201712120000000003, $ret15['ret'][1]['id']);
        $this->assertEquals(2, $ret15['ret'][1]['domain']);
        $this->assertEquals('CNY', $ret15['ret'][1]['currency']);
        $this->assertEquals(-300, $ret15['ret'][1]['amount']);
        $this->assertEquals(36.666663, $ret15['ret'][1]['bitcoin_amount']);
        $this->assertEquals(1, $ret15['ret'][1]['rate']);
        $this->assertEquals(0.12345678, $ret15['ret'][1]['bitcoin_rate']);
        $this->assertEquals(0.00123457, $ret15['ret'][1]['rate_difference']);
        $this->assertEquals(-300, $ret15['ret'][1]['amount_conv']);
        $this->assertEquals('address3', $ret15['ret'][1]['withdraw_address']);
    }

    /**
     * 測試回傳比特幣出款紀錄帶入排序欄位
     */
    public function testGetWithdrawEntriesListWithSort()
    {
        $client = $this->createClient();

        $parameters1 = [
            'domain' => 2,
            'level_id' => 1,
            'sort' => ['id'],
            'order' => ['asc']
        ];
        $client->request('GET', '/api/bitcoin_withdraw/entry/list', $parameters1);

        $json1 = $client->getResponse()->getContent();
        $ret1 = json_decode($json1, true);

        $this->assertEquals('ok', $ret1['result']);
        $this->assertCount(5, $ret1['ret']);

        $this->assertEquals(201712120000000001, $ret1['ret'][0]['id']);
        $this->assertEquals(2, $ret1['ret'][0]['domain']);
        $this->assertEquals('CNY', $ret1['ret'][0]['currency']);
        $this->assertEquals(-100, $ret1['ret'][0]['amount']);
        $this->assertEquals(12.222221, $ret1['ret'][0]['bitcoin_amount']);
        $this->assertEquals(1, $ret1['ret'][0]['rate']);
        $this->assertEquals(0.12345678, $ret1['ret'][0]['bitcoin_rate']);
        $this->assertEquals(0.00123457, $ret1['ret'][0]['rate_difference']);
        $this->assertEquals(-100, $ret1['ret'][0]['amount_conv']);
        $this->assertEquals('address1', $ret1['ret'][0]['withdraw_address']);

        $this->assertEquals(201712120000000002, $ret1['ret'][1]['id']);
        $this->assertEquals(2, $ret1['ret'][1]['domain']);
        $this->assertEquals('CNY', $ret1['ret'][1]['currency']);
        $this->assertEquals(-200, $ret1['ret'][1]['amount']);
        $this->assertEquals(24.444442, $ret1['ret'][1]['bitcoin_amount']);
        $this->assertEquals(1, $ret1['ret'][1]['rate']);
        $this->assertEquals(0.12345678, $ret1['ret'][1]['bitcoin_rate']);
        $this->assertEquals(0.00123457, $ret1['ret'][1]['rate_difference']);
        $this->assertEquals(-200, $ret1['ret'][1]['amount_conv']);
        $this->assertEquals('address2', $ret1['ret'][1]['withdraw_address']);

        $this->assertEquals(201712120000000003, $ret1['ret'][2]['id']);
        $this->assertEquals(2, $ret1['ret'][2]['domain']);
        $this->assertEquals('CNY', $ret1['ret'][2]['currency']);
        $this->assertEquals(-300, $ret1['ret'][2]['amount']);
        $this->assertEquals(36.666663, $ret1['ret'][2]['bitcoin_amount']);
        $this->assertEquals(1, $ret1['ret'][2]['rate']);
        $this->assertEquals(0.12345678, $ret1['ret'][2]['bitcoin_rate']);
        $this->assertEquals(0.00123457, $ret1['ret'][2]['rate_difference']);
        $this->assertEquals(-300, $ret1['ret'][2]['amount_conv']);
        $this->assertEquals('address3', $ret1['ret'][2]['withdraw_address']);

        $this->assertEquals(201712120000000004, $ret1['ret'][3]['id']);
        $this->assertEquals(2, $ret1['ret'][3]['domain']);
        $this->assertEquals('CNY', $ret1['ret'][3]['currency']);
        $this->assertEquals(-400, $ret1['ret'][3]['amount']);
        $this->assertEquals(48.888884, $ret1['ret'][3]['bitcoin_amount']);
        $this->assertEquals(1, $ret1['ret'][3]['rate']);
        $this->assertEquals(0.12345678, $ret1['ret'][3]['bitcoin_rate']);
        $this->assertEquals(0.00123457, $ret1['ret'][3]['rate_difference']);
        $this->assertEquals(-400, $ret1['ret'][3]['amount_conv']);
        $this->assertEquals('address3', $ret1['ret'][3]['withdraw_address']);

        $this->assertEquals(201712120000000010, $ret1['ret'][4]['id']);
        $this->assertEquals(2, $ret1['ret'][4]['domain']);
        $this->assertEquals('USD', $ret1['ret'][4]['currency']);
        $this->assertEquals(-100, $ret1['ret'][4]['amount']);
        $this->assertEquals(23.222221, $ret1['ret'][4]['bitcoin_amount']);
        $this->assertEquals(6.34, $ret1['ret'][4]['rate']);
        $this->assertEquals(0.23456789, $ret1['ret'][4]['bitcoin_rate']);
        $this->assertEquals(0.00234568, $ret1['ret'][4]['rate_difference']);
        $this->assertEquals(-634, $ret1['ret'][4]['amount_conv']);
        $this->assertEquals('address10', $ret1['ret'][4]['withdraw_address']);

        $parameters2 = [
            'domain' => 2,
            'sub_total' => 1,
            'total' => 1,
            'first_result' => 0,
            'max_results' => 2,
            'sort' => ['id'],
            'order' => ['asc']
        ];
        $client->request('GET', '/api/bitcoin_withdraw/entry/list', $parameters2);

        $json2 = $client->getResponse()->getContent();
        $ret2 = json_decode($json2, true);

        $this->assertEquals('ok', $ret2['result']);
        $this->assertCount(2, $ret2['ret']);
        $this->assertEquals(-300, $ret2['sub_total']['amount']);
        $this->assertEquals(-2200, $ret2['total']['amount']);
        $this->assertEquals(0, $ret2['pagination']['first_result']);
        $this->assertEquals(2, $ret2['pagination']['max_results']);
        $this->assertEquals(10, $ret2['pagination']['total']);

        $this->assertEquals(201712120000000001, $ret2['ret'][0]['id']);
        $this->assertEquals(2, $ret2['ret'][0]['domain']);
        $this->assertEquals('CNY', $ret2['ret'][0]['currency']);
        $this->assertEquals(-100, $ret2['ret'][0]['amount']);
        $this->assertEquals(12.222221, $ret2['ret'][0]['bitcoin_amount']);
        $this->assertEquals(1, $ret2['ret'][0]['rate']);
        $this->assertEquals(0.12345678, $ret2['ret'][0]['bitcoin_rate']);
        $this->assertEquals(0.00123457, $ret2['ret'][0]['rate_difference']);
        $this->assertEquals(-100, $ret2['ret'][0]['amount_conv']);
        $this->assertEquals('address1', $ret2['ret'][0]['withdraw_address']);

        $this->assertEquals(201712120000000002, $ret2['ret'][1]['id']);
        $this->assertEquals(2, $ret2['ret'][1]['domain']);
        $this->assertEquals('CNY', $ret2['ret'][1]['currency']);
        $this->assertEquals(-200, $ret2['ret'][1]['amount']);
        $this->assertEquals(24.444442, $ret2['ret'][1]['bitcoin_amount']);
        $this->assertEquals(1, $ret2['ret'][1]['rate']);
        $this->assertEquals(0.12345678, $ret2['ret'][1]['bitcoin_rate']);
        $this->assertEquals(0.00123457, $ret2['ret'][1]['rate_difference']);
        $this->assertEquals(-200, $ret2['ret'][1]['amount_conv']);
        $this->assertEquals('address2', $ret2['ret'][1]['withdraw_address']);
    }

    /**
     * 測試回傳比特幣出款紀錄帶入金額區間
     */
    public function testGetWithdrawEntriesListWithAmount()
    {
        $client = $this->createClient();

        $parameters = [
            'domain' => 2,
            'amount_min' => '-400',
            'amount_max' => '-300',
            'sub_total' => 1
        ];
        $client->request('GET', '/api/bitcoin_withdraw/entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertCount(4, $ret['ret']);
        $this->assertEquals(-1400, $ret['sub_total']['amount']);

        $this->assertEquals(201712120000000008, $ret['ret'][0]['id']);
        $this->assertEquals(2, $ret['ret'][0]['domain']);
        $this->assertEquals('USD', $ret['ret'][0]['currency']);
        $this->assertEquals(-400, $ret['ret'][0]['amount']);
        $this->assertEquals(92.888884, $ret['ret'][0]['bitcoin_amount']);
        $this->assertEquals(6.34, $ret['ret'][0]['rate']);
        $this->assertEquals(0.23456789, $ret['ret'][0]['bitcoin_rate']);
        $this->assertEquals(0.00234568, $ret['ret'][0]['rate_difference']);
        $this->assertEquals(-2536, $ret['ret'][0]['amount_conv']);
        $this->assertEquals('address8', $ret['ret'][0]['withdraw_address']);

        $this->assertEquals(201712120000000007, $ret['ret'][1]['id']);
        $this->assertEquals(2, $ret['ret'][1]['domain']);
        $this->assertEquals('USD', $ret['ret'][1]['currency']);
        $this->assertEquals(-300, $ret['ret'][1]['amount']);
        $this->assertEquals(69.666663, $ret['ret'][1]['bitcoin_amount']);
        $this->assertEquals(6.34, $ret['ret'][1]['rate']);
        $this->assertEquals(0.23456789, $ret['ret'][1]['bitcoin_rate']);
        $this->assertEquals(0.00234568, $ret['ret'][1]['rate_difference']);
        $this->assertEquals(-1902, $ret['ret'][1]['amount_conv']);
        $this->assertEquals('address7', $ret['ret'][1]['withdraw_address']);

        $this->assertEquals(201712120000000004, $ret['ret'][2]['id']);
        $this->assertEquals(2, $ret['ret'][2]['domain']);
        $this->assertEquals('CNY', $ret['ret'][2]['currency']);
        $this->assertEquals(-400, $ret['ret'][2]['amount']);
        $this->assertEquals(48.888884, $ret['ret'][2]['bitcoin_amount']);
        $this->assertEquals(1, $ret['ret'][2]['rate']);
        $this->assertEquals(0.12345678, $ret['ret'][2]['bitcoin_rate']);
        $this->assertEquals(0.00123457, $ret['ret'][2]['rate_difference']);
        $this->assertEquals(-400, $ret['ret'][2]['amount_conv']);
        $this->assertEquals('address3', $ret['ret'][2]['withdraw_address']);

        $this->assertEquals(201712120000000003, $ret['ret'][3]['id']);
        $this->assertEquals(2, $ret['ret'][3]['domain']);
        $this->assertEquals('CNY', $ret['ret'][3]['currency']);
        $this->assertEquals(-300, $ret['ret'][3]['amount']);
        $this->assertEquals(36.666663, $ret['ret'][3]['bitcoin_amount']);
        $this->assertEquals(1, $ret['ret'][3]['rate']);
        $this->assertEquals(0.12345678, $ret['ret'][3]['bitcoin_rate']);
        $this->assertEquals(0.00123457, $ret['ret'][3]['rate_difference']);
        $this->assertEquals(-300, $ret['ret'][3]['amount_conv']);
        $this->assertEquals('address3', $ret['ret'][3]['withdraw_address']);
    }

    /**
     * 測試回傳比特幣出款紀錄帶入比特幣金額區間
     */
    public function testGetWithdrawEntriesListWithBitcoinAmount()
    {
        $client = $this->createClient();

        $parameters = [
            'domain' => 2,
            'bitcoin_amount_min' => '36.00000000',
            'bitcoin_amount_max' => '50.00000000',
            'sub_total' => 1
        ];
        $client->request('GET', '/api/bitcoin_withdraw/entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertCount(3, $ret['ret']);
        $this->assertEquals(-900, $ret['sub_total']['amount']);

        $this->assertEquals(201712120000000006, $ret['ret'][0]['id']);
        $this->assertEquals(2, $ret['ret'][0]['domain']);
        $this->assertEquals('USD', $ret['ret'][0]['currency']);
        $this->assertEquals(-200, $ret['ret'][0]['amount']);
        $this->assertEquals(46.444442, $ret['ret'][0]['bitcoin_amount']);
        $this->assertEquals(6.34, $ret['ret'][0]['rate']);
        $this->assertEquals(0.23456789, $ret['ret'][0]['bitcoin_rate']);
        $this->assertEquals(0.00234568, $ret['ret'][0]['rate_difference']);
        $this->assertEquals(-1268, $ret['ret'][0]['amount_conv']);
        $this->assertEquals('address6', $ret['ret'][0]['withdraw_address']);

        $this->assertEquals(201712120000000004, $ret['ret'][1]['id']);
        $this->assertEquals(2, $ret['ret'][1]['domain']);
        $this->assertEquals('CNY', $ret['ret'][1]['currency']);
        $this->assertEquals(-400, $ret['ret'][1]['amount']);
        $this->assertEquals(48.888884, $ret['ret'][1]['bitcoin_amount']);
        $this->assertEquals(1, $ret['ret'][1]['rate']);
        $this->assertEquals(0.12345678, $ret['ret'][1]['bitcoin_rate']);
        $this->assertEquals(0.00123457, $ret['ret'][1]['rate_difference']);
        $this->assertEquals(-400, $ret['ret'][1]['amount_conv']);
        $this->assertEquals('address3', $ret['ret'][1]['withdraw_address']);

        $this->assertEquals(201712120000000003, $ret['ret'][2]['id']);
        $this->assertEquals(2, $ret['ret'][2]['domain']);
        $this->assertEquals('CNY', $ret['ret'][2]['currency']);
        $this->assertEquals(-300, $ret['ret'][2]['amount']);
        $this->assertEquals(36.666663, $ret['ret'][2]['bitcoin_amount']);
        $this->assertEquals(1, $ret['ret'][2]['rate']);
        $this->assertEquals(0.12345678, $ret['ret'][2]['bitcoin_rate']);
        $this->assertEquals(0.00123457, $ret['ret'][2]['rate_difference']);
        $this->assertEquals(-300, $ret['ret'][2]['amount_conv']);
        $this->assertEquals('address3', $ret['ret'][2]['withdraw_address']);
    }

    /**
     * 測試以申請出款時間及確認出款時間取比特幣出款明細列表
     */
    public function testGetWithdrawEntriesListByAtAndConfirmAt()
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
        $client->request('GET', '/api/bitcoin_withdraw/entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertCount(3, $ret['ret']);

        $this->assertEquals(201712120000000009, $ret['ret'][0]['id']);
        $this->assertEquals(2, $ret['ret'][0]['domain']);
        $this->assertEquals('USD', $ret['ret'][0]['currency']);
        $this->assertEquals(-100, $ret['ret'][0]['amount']);
        $this->assertEquals(23.222221, $ret['ret'][0]['bitcoin_amount']);
        $this->assertEquals(6.34, $ret['ret'][0]['rate']);
        $this->assertEquals(0.23456789, $ret['ret'][0]['bitcoin_rate']);
        $this->assertEquals(0.00234568, $ret['ret'][0]['rate_difference']);
        $this->assertEquals(-634, $ret['ret'][0]['amount_conv']);
        $this->assertEquals('address9', $ret['ret'][0]['withdraw_address']);

        $this->assertEquals(201712120000000002, $ret['ret'][1]['id']);
        $this->assertEquals(2, $ret['ret'][1]['domain']);
        $this->assertEquals('CNY', $ret['ret'][1]['currency']);
        $this->assertEquals(-200, $ret['ret'][1]['amount']);
        $this->assertEquals(24.444442, $ret['ret'][1]['bitcoin_amount']);
        $this->assertEquals(1, $ret['ret'][1]['rate']);
        $this->assertEquals(0.12345678, $ret['ret'][1]['bitcoin_rate']);
        $this->assertEquals(0.00123457, $ret['ret'][1]['rate_difference']);
        $this->assertEquals(-200, $ret['ret'][1]['amount_conv']);
        $this->assertEquals('address2', $ret['ret'][1]['withdraw_address']);

        $this->assertEquals(201712120000000001, $ret['ret'][2]['id']);
        $this->assertEquals(2, $ret['ret'][2]['domain']);
        $this->assertEquals('CNY', $ret['ret'][2]['currency']);
        $this->assertEquals(-100, $ret['ret'][2]['amount']);
        $this->assertEquals(12.222221, $ret['ret'][2]['bitcoin_amount']);
        $this->assertEquals(1, $ret['ret'][2]['rate']);
        $this->assertEquals(0.12345678, $ret['ret'][2]['bitcoin_rate']);
        $this->assertEquals(0.00123457, $ret['ret'][2]['rate_difference']);
        $this->assertEquals(-100, $ret['ret'][2]['amount_conv']);
        $this->assertEquals('address1', $ret['ret'][2]['withdraw_address']);

        $entry = $em->getRepository('BBDurianBundle:BitcoinWithdrawEntry')->findOneBy(['id' => 201712120000000001]);
        $this->assertEquals($entry->getAt()->format(\DateTime::ISO8601), $ret['ret'][2]['at']);
        $this->assertEquals($entry->getConfirmAt()->format(\DateTime::ISO8601), $ret['ret'][2]['confirm_at']);
    }
}
