<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use Doctrine\ORM\OptimisticLockException;

class ManualFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData'
        ];

        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashEntryData'
        ];

        $this->loadFixtures($classnames, 'entry');

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadExchangeData'
        ];

        $this->loadFixtures($classnames, 'share');

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $redis->flushdb();

        $redis = $this->getContainer()->get('snc_redis.sequence');

        $redis->set('cash_seq', 1000);
    }

    /**
     * 測試人工存入帶有非法字元
     */
    public function testManualWithIllegalCharacter()
    {
        $client = $this->createClient();

        $parameters = [
            'opcode' => 1010,
            'amount' => 500,
            'memo' => '🔫',
            'offer' => 25,
            'offer_memo' => 'hrhr offer',
            'remit_offer' => 25,
            'remit_offer_memo' => 'hrhr remit offer',
            'ref_id' => 1556899,
            'operator' => 'dindin'
        ];

        $client->request('PUT', '/api/user/8/manual', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150610007, $ret['code']);
        $this->assertEquals('Illegal character', $ret['msg']);

        $parameters['memo'] = '321';
        $parameters['offer_memo'] = '有病就該吃💊';

        $client->request('PUT', '/api/user/8/manual', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150610007, $ret['code']);
        $this->assertEquals('Illegal character', $ret['msg']);

        $parameters['offer_memo'] = '☯8竟然會過';
        $parameters['remit_offer_memo'] = '你不是大俠 吃🍌';

        $client->request('PUT', '/api/user/8/manual', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150610007, $ret['code']);
        $this->assertEquals('Illegal character', $ret['msg']);

        $parameters['remit_offer_memo'] = '321';
        $parameters['operator'] = '這世界是沒有🎅的';

        $client->request('PUT', '/api/user/8/manual', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150610007, $ret['code']);
        $this->assertEquals('Illegal character', $ret['msg']);
    }

    /**
     * 測試人工存入
     */
    public function testManual()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $memo = '';
        for ($i = 0; $i < 100; $i++) {
            $memo .= 'a';
        }

        $parameters = [
            'opcode' => 1010,
            'amount' => 500,
            'memo' => $memo . '012',
            'offer' => 25,
            'offer_memo' => 'hrhr offer',
            'remit_offer' => 25,
            'remit_offer_memo' => 'hrhr remit offer',
            'ref_id' => 1556899,
            'operator' => 'dindin'
        ];

        $client->request('PUT', '/api/user/8/manual', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //驗證cash回傳
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['cash']['user_id']);
        $this->assertEquals(1550, $ret['ret']['cash']['balance']);

        //驗證明細
        $this->assertEquals(1001, $ret['ret']['entry']['id']);
        $this->assertEquals(7, $ret['ret']['entry']['cash_id']);
        $this->assertEquals(8, $ret['ret']['entry']['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry']['currency']);
        $this->assertEquals(1010, $ret['ret']['entry']['opcode']);
        $this->assertEquals(500, $ret['ret']['entry']['amount']);
        $this->assertEquals($memo, $ret['ret']['entry']['memo']);
        $this->assertEquals(1500, $ret['ret']['entry']['balance']);
        $this->assertEquals(1556899, $ret['ret']['entry']['ref_id']);
        $this->assertEquals('dindin', $ret['ret']['entry']['operator']['username']);

        $this->assertEquals(1002, $ret['ret']['offer_entry']['id']);
        $this->assertEquals(7, $ret['ret']['offer_entry']['cash_id']);
        $this->assertEquals(8, $ret['ret']['offer_entry']['user_id']);
        $this->assertEquals('TWD', $ret['ret']['offer_entry']['currency']);
        $this->assertEquals(1011, $ret['ret']['offer_entry']['opcode']);
        $this->assertEquals(25, $ret['ret']['offer_entry']['amount']);
        $this->assertEquals('hrhr offer', $ret['ret']['offer_entry']['memo']);
        $this->assertEquals(1525, $ret['ret']['offer_entry']['balance']);
        $this->assertEquals(1556899, $ret['ret']['offer_entry']['ref_id']);
        $this->assertEquals('dindin', $ret['ret']['offer_entry']['operator']['username']);

        $this->assertEquals(1003, $ret['ret']['remit_offer_entry']['id']);
        $this->assertEquals(7, $ret['ret']['remit_offer_entry']['cash_id']);
        $this->assertEquals(8, $ret['ret']['remit_offer_entry']['user_id']);
        $this->assertEquals('TWD', $ret['ret']['remit_offer_entry']['currency']);
        $this->assertEquals(1012, $ret['ret']['remit_offer_entry']['opcode']);
        $this->assertEquals(25, $ret['ret']['remit_offer_entry']['amount']);
        $this->assertEquals('hrhr remit offer', $ret['ret']['remit_offer_entry']['memo']);
        $this->assertEquals(1550, $ret['ret']['remit_offer_entry']['balance']);
        $this->assertEquals(1556899, $ret['ret']['remit_offer_entry']['ref_id']);
        $this->assertEquals('dindin', $ret['ret']['remit_offer_entry']['operator']['username']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $ceRepo = $emEntry->getRepository('BBDurianBundle:CashEntry');

        $criteria = [
            'userId' => 8,
            'refId' => 1556899
        ];
        $entries = $ceRepo->findBy($criteria);

        //驗證資料庫明細筆數
        $this->assertEquals(3, count($entries));

        //驗證寫入的資料與回傳相同
        $this->assertEquals($entries[0]->getId(), $ret['ret']['entry']['id']);
        $this->assertEquals($entries[0]->getAmount(), $ret['ret']['entry']['amount']);
        $this->assertEquals($entries[0]->getMemo(), $ret['ret']['entry']['memo']);
        $this->assertEquals($entries[0]->getBalance(), $ret['ret']['entry']['balance']);
        $this->assertEquals($entries[1]->getId(), $ret['ret']['offer_entry']['id']);
        $this->assertEquals($entries[1]->getAmount(), $ret['ret']['offer_entry']['amount']);
        $this->assertEquals($entries[1]->getBalance(), $ret['ret']['offer_entry']['balance']);
        $this->assertEquals($entries[2]->getId(), $ret['ret']['remit_offer_entry']['id']);
        $this->assertEquals($entries[2]->getAmount(), $ret['ret']['remit_offer_entry']['amount']);
        $this->assertEquals($entries[2]->getBalance(), $ret['ret']['remit_offer_entry']['balance']);

        $now = new \DateTime();
        $esNowMonth = $now->setTimezone(new \DateTimeZone('Etc/GMT+4'))->format('Ym');

        // 檢查使用者出入款統計資料
        $userStat = $em->find('BBDurianBundle:UserStat', 8);
        $this->assertEquals(1, $userStat->getManualCount());
        $this->assertEquals(111.5, $userStat->getManualTotal());
        $this->assertEquals(111.5, $userStat->getManualMax());
        $this->assertEquals(111.5, $userStat->getFirstDepositAmount());

        //驗證logOperation
        $operationLog = $emShare->find('BBDurianBundle:LogOperation', 1);
        $msg = '@manual_count:0=>1, @manual_total:0=>111.5, @manual_max:0=>111.5';
        $msg .= ', @first_deposit_at:' . $userStat->getFirstDepositAt()->format(\DateTime::ISO8601);
        $msg .= ', @first_deposit_amount:111.5, @modified_at:';
        $this->assertEquals('user_stat', $operationLog->getTableName());
        $this->assertEquals('@user_id:8', $operationLog->getMajorKey());
        $this->assertContains($msg, $operationLog->getMessage());

        // 檢查統計入款金額queue
        $statDeposit = json_decode($redis->rpop('stat_domain_deposit_queue'), true);

        $this->assertEquals(2, $statDeposit['domain']);
        $this->assertEquals(111.5, $statDeposit['amount']);
    }

    /**
     * 測試人工存入金額帶有空白
     */
    public function testManualAmountWithSpace()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $parameters = [
            'opcode' => 1010,
            'amount' => ' 500 ',
            'memo' => '123',
            'offer' => 25,
            'offer_memo' => 'hrhr offer',
            'remit_offer' => 25,
            'remit_offer_memo' => 'hrhr remit offer',
            'ref_id' => 1556899,
            'operator' => 'dindin',
        ];

        $client->request('PUT', '/api/user/8/manual', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        // 驗證cash回傳
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['cash']['user_id']);
        $this->assertEquals(1550, $ret['ret']['cash']['balance']);

        // 驗證明細
        $this->assertEquals(1001, $ret['ret']['entry']['id']);
        $this->assertEquals(7, $ret['ret']['entry']['cash_id']);
        $this->assertEquals(8, $ret['ret']['entry']['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry']['currency']);
        $this->assertEquals(1010, $ret['ret']['entry']['opcode']);
        $this->assertEquals(500, $ret['ret']['entry']['amount']);
        $this->assertEquals('123', $ret['ret']['entry']['memo']);
        $this->assertEquals(1500, $ret['ret']['entry']['balance']);
        $this->assertEquals(1556899, $ret['ret']['entry']['ref_id']);
        $this->assertEquals('dindin', $ret['ret']['entry']['operator']['username']);

        $this->assertEquals(1002, $ret['ret']['offer_entry']['id']);
        $this->assertEquals(7, $ret['ret']['offer_entry']['cash_id']);
        $this->assertEquals(8, $ret['ret']['offer_entry']['user_id']);
        $this->assertEquals('TWD', $ret['ret']['offer_entry']['currency']);
        $this->assertEquals(1011, $ret['ret']['offer_entry']['opcode']);
        $this->assertEquals(25, $ret['ret']['offer_entry']['amount']);
        $this->assertEquals('hrhr offer', $ret['ret']['offer_entry']['memo']);
        $this->assertEquals(1525, $ret['ret']['offer_entry']['balance']);
        $this->assertEquals(1556899, $ret['ret']['offer_entry']['ref_id']);
        $this->assertEquals('dindin', $ret['ret']['offer_entry']['operator']['username']);

        $this->assertEquals(1003, $ret['ret']['remit_offer_entry']['id']);
        $this->assertEquals(7, $ret['ret']['remit_offer_entry']['cash_id']);
        $this->assertEquals(8, $ret['ret']['remit_offer_entry']['user_id']);
        $this->assertEquals('TWD', $ret['ret']['remit_offer_entry']['currency']);
        $this->assertEquals(1012, $ret['ret']['remit_offer_entry']['opcode']);
        $this->assertEquals(25, $ret['ret']['remit_offer_entry']['amount']);
        $this->assertEquals('hrhr remit offer', $ret['ret']['remit_offer_entry']['memo']);
        $this->assertEquals(1550, $ret['ret']['remit_offer_entry']['balance']);
        $this->assertEquals(1556899, $ret['ret']['remit_offer_entry']['ref_id']);
        $this->assertEquals('dindin', $ret['ret']['remit_offer_entry']['operator']['username']);

        // 跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');

        $criteria = [
            'userId' => 8,
            'refId' => 1556899,
        ];
        $entries = $emEntry->getRepository('BBDurianBundle:CashEntry')->findBy($criteria);

        // 驗證資料庫明細筆數
        $this->assertEquals(3, count($entries));

        // 驗證寫入的資料與回傳相同
        $this->assertEquals($entries[0]->getId(), $ret['ret']['entry']['id']);
        $this->assertEquals($entries[0]->getAmount(), $ret['ret']['entry']['amount']);
        $this->assertEquals($entries[0]->getMemo(), $ret['ret']['entry']['memo']);
        $this->assertEquals($entries[0]->getBalance(), $ret['ret']['entry']['balance']);
        $this->assertEquals($entries[1]->getId(), $ret['ret']['offer_entry']['id']);
        $this->assertEquals($entries[1]->getAmount(), $ret['ret']['offer_entry']['amount']);
        $this->assertEquals($entries[1]->getBalance(), $ret['ret']['offer_entry']['balance']);
        $this->assertEquals($entries[2]->getId(), $ret['ret']['remit_offer_entry']['id']);
        $this->assertEquals($entries[2]->getAmount(), $ret['ret']['remit_offer_entry']['amount']);
        $this->assertEquals($entries[2]->getBalance(), $ret['ret']['remit_offer_entry']['balance']);

        // 檢查使用者出入款統計資料
        $userStat = $em->find('BBDurianBundle:UserStat', 8);
        $this->assertEquals(1, $userStat->getManualCount());
        $this->assertEquals(111.5, $userStat->getManualTotal());
        $this->assertEquals(111.5, $userStat->getManualMax());
        $this->assertEquals(111.5, $userStat->getFirstDepositAmount());

        // 驗證logOperation
        $operationLog = $emShare->find('BBDurianBundle:LogOperation', 1);
        $msg = '@manual_count:0=>1, @manual_total:0=>111.5, @manual_max:0=>111.5';
        $msg .= ', @first_deposit_at:' . $userStat->getFirstDepositAt()->format(\DateTime::ISO8601);
        $msg .= ', @first_deposit_amount:111.5, @modified_at:';
        $this->assertEquals('user_stat', $operationLog->getTableName());
        $this->assertEquals('@user_id:8', $operationLog->getMajorKey());
        $this->assertContains($msg, $operationLog->getMessage());

        // 檢查統計入款金額queue
        $statDeposit = json_decode($redis->rpop('stat_domain_deposit_queue'), true);

        $this->assertEquals(2, $statDeposit['domain']);
        $this->assertEquals(111.5, $statDeposit['amount']);
    }

    /**
     * 測試人工存入, 入款金額超過50萬, 需寄發異常入款提醒
     */
    public function testManualWithAbnormalAmount()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $memo = '';
        for ($i = 0; $i < 100; $i++) {
            $memo .= 'a';
        }

        $parameters = [
            'opcode' => 1010,
            'amount' => 2500000,
            'memo' => $memo . '012',
            'offer' => 25,
            'offer_memo' => 'hrhr offer',
            'remit_offer' => 25,
            'remit_offer_memo' => 'hrhr remit offer',
            'ref_id' => 1556899,
            'operator' => 'dindin'
        ];

        $client->request('PUT', '/api/user/8/manual', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        // 驗證cash回傳
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['cash']['user_id']);
        $this->assertEquals(2501050, $ret['ret']['cash']['balance']);

        // 驗證明細
        $this->assertEquals(1001, $ret['ret']['entry']['id']);
        $this->assertEquals(7, $ret['ret']['entry']['cash_id']);
        $this->assertEquals(8, $ret['ret']['entry']['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry']['currency']);
        $this->assertEquals(1010, $ret['ret']['entry']['opcode']);
        $this->assertEquals(2500000, $ret['ret']['entry']['amount']);
        $this->assertEquals($memo, $ret['ret']['entry']['memo']);
        $this->assertEquals(2501000, $ret['ret']['entry']['balance']);
        $this->assertEquals(1556899, $ret['ret']['entry']['ref_id']);
        $this->assertEquals('dindin', $ret['ret']['entry']['operator']['username']);

        $this->assertEquals(1002, $ret['ret']['offer_entry']['id']);
        $this->assertEquals(7, $ret['ret']['offer_entry']['cash_id']);
        $this->assertEquals(8, $ret['ret']['offer_entry']['user_id']);
        $this->assertEquals('TWD', $ret['ret']['offer_entry']['currency']);
        $this->assertEquals(1011, $ret['ret']['offer_entry']['opcode']);
        $this->assertEquals(25, $ret['ret']['offer_entry']['amount']);
        $this->assertEquals('hrhr offer', $ret['ret']['offer_entry']['memo']);
        $this->assertEquals(2501025, $ret['ret']['offer_entry']['balance']);
        $this->assertEquals(1556899, $ret['ret']['offer_entry']['ref_id']);
        $this->assertEquals('dindin', $ret['ret']['offer_entry']['operator']['username']);

        $this->assertEquals(1003, $ret['ret']['remit_offer_entry']['id']);
        $this->assertEquals(7, $ret['ret']['remit_offer_entry']['cash_id']);
        $this->assertEquals(8, $ret['ret']['remit_offer_entry']['user_id']);
        $this->assertEquals('TWD', $ret['ret']['remit_offer_entry']['currency']);
        $this->assertEquals(1012, $ret['ret']['remit_offer_entry']['opcode']);
        $this->assertEquals(25, $ret['ret']['remit_offer_entry']['amount']);
        $this->assertEquals('hrhr remit offer', $ret['ret']['remit_offer_entry']['memo']);
        $this->assertEquals(2501050, $ret['ret']['remit_offer_entry']['balance']);
        $this->assertEquals(1556899, $ret['ret']['remit_offer_entry']['ref_id']);
        $this->assertEquals('dindin', $ret['ret']['remit_offer_entry']['operator']['username']);

        // 跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $ceRepo = $emEntry->getRepository('BBDurianBundle:CashEntry');

        $criteria = [
            'userId' => 8,
            'refId' => 1556899
        ];
        $entries = $ceRepo->findBy($criteria);

        // 驗證資料庫明細筆數
        $this->assertEquals(3, count($entries));

        // 驗證寫入的資料與回傳相同
        $this->assertEquals($entries[0]->getId(), $ret['ret']['entry']['id']);
        $this->assertEquals($entries[0]->getAmount(), $ret['ret']['entry']['amount']);
        $this->assertEquals($entries[0]->getMemo(), $ret['ret']['entry']['memo']);
        $this->assertEquals($entries[0]->getBalance(), $ret['ret']['entry']['balance']);
        $this->assertEquals($entries[1]->getId(), $ret['ret']['offer_entry']['id']);
        $this->assertEquals($entries[1]->getAmount(), $ret['ret']['offer_entry']['amount']);
        $this->assertEquals($entries[1]->getBalance(), $ret['ret']['offer_entry']['balance']);
        $this->assertEquals($entries[2]->getId(), $ret['ret']['remit_offer_entry']['id']);
        $this->assertEquals($entries[2]->getAmount(), $ret['ret']['remit_offer_entry']['amount']);
        $this->assertEquals($entries[2]->getBalance(), $ret['ret']['remit_offer_entry']['balance']);

        $now = new \DateTime();
        $esNowMonth = $now->setTimezone(new \DateTimeZone('Etc/GMT+4'))->format('Ym');

        // 檢查使用者出入款統計資料
        $userStat = $em->find('BBDurianBundle:UserStat', 8);
        $this->assertEquals(1, $userStat->getManualCount());
        $this->assertEquals(557500, $userStat->getManualTotal());
        $this->assertEquals(557500, $userStat->getManualMax());
        $this->assertEquals(557500, $userStat->getFirstDepositAmount());

        // 驗證logOperation
        $operationLog = $emShare->find('BBDurianBundle:LogOperation', 1);
        $msg = '@manual_count:0=>1, @manual_total:0=>557500, @manual_max:0=>557500';
        $msg .= ', @first_deposit_at:' . $userStat->getFirstDepositAt()->format(\DateTime::ISO8601);
        $msg .= ', @first_deposit_amount:557500, @modified_at:';

        $this->assertEquals('user_stat', $operationLog->getTableName());
        $this->assertEquals('@user_id:8', $operationLog->getMajorKey());
        $this->assertContains($msg, $operationLog->getMessage());

        // 檢查異常入款提醒queue
        $abnormalDepositNotify = json_decode($redis->rpop('abnormal_deposit_notify_queue'), true);

        $this->assertEquals(2, $abnormalDepositNotify['domain']);
        $this->assertEquals('tester', $abnormalDepositNotify['user_name']);
        $this->assertEquals(1010, $abnormalDepositNotify['opcode']);
        $this->assertEquals('dindin', $abnormalDepositNotify['operator']);
        $this->assertEquals(557500, $abnormalDepositNotify['amount']);
    }

    /**
     * 測試人工存入不代入存款優惠及匯款優惠
     */
    public function testManualWithoutOfferNorRemitOffer()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entry_entity_manager');

        $parameters = [
            'opcode' => 1001,
            'amount' => 500,
            'memo' => 'hrhr amount',
            'ref_id' => 1556899,
            'operator' => 'dindin'
        ];

        $client->request('PUT', '/api/user/8/manual', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $this->assertEquals('ok', $ret['result']);

        $ceRepo = $em->getRepository('BBDurianBundle:CashEntry');

        //驗證存款優惠不會寫入
        $criteria = [
            'userId' => 8,
            'opcode' => 1011
        ];
        $offerEntry = $ceRepo->findBy($criteria);

        $this->assertEmpty($offerEntry);

        //驗證匯款優惠不會寫入
        $criteria = [
            'userId' => 8,
            'opcode' => 1012
        ];
        $remitOfferEntry = $ceRepo->findBy($criteria);

        $this->assertEmpty($remitOfferEntry);
    }

    /**
     * 測試人工存入未代入opcode
     */
    public function testManualWithoutOpcode()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/user/8/manual');
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(490001, $ret['code']);
        $this->assertEquals('Invalid opcode', $ret['msg']);
    }

    /**
     * 測試人工存入代入不存在的使用者
     */
    public function testManualWithNoneExistUser()
    {
        $client = $this->createClient();

        $parameters = [
            'opcode' => 1010,
            'amount' => 500
        ];

        $client->request('PUT', '/api/user/26998/manual', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(490004, $ret['code']);
        $this->assertEquals('No such user', $ret['msg']);
    }

    /**
     * 測試人工存入代入的使用者沒現金
     */
    public function testManualWithNoCashUser()
    {
        $client = $this->createClient();

        $parameters = [
            'opcode' => 1010,
            'amount' => 500
        ];

        $client->request('PUT', '/api/user/10/manual', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(490003, $ret['code']);
        $this->assertEquals('No cash found', $ret['msg']);
    }

    /**
     * 測試人工存入未代入金額
     */
    public function testManualWithoutAmount()
    {
        $client = $this->createClient();

        $parameters = ['opcode' => 1010];

        $client->request('PUT', '/api/user/8/manual', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(490006, $ret['code']);
        $this->assertEquals('No amount specified', $ret['msg']);
    }

    /**
     * 測試人工存入代入不合法的金額
     */
    public function testManualWithIllegalAmount()
    {
        $client = $this->createClient();

        $parameters = [
            'opcode' => 1010,
            'amount' => 100.55699885
        ];

        $client->request('PUT', '/api/user/8/manual', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150610003, $ret['code']);
        $this->assertEquals('The decimal digit of amount exceeds limitation', $ret['msg']);
    }

    /**
     * 測試人工存入代入不合法的金額
     */
    public function testManualWithExceedMaxAmount()
    {
        $client = $this->createClient();

        $parameters = [
            'opcode' => 1010,
            'amount' => 90000000000000000
        ];

        $client->request('PUT', '/api/user/8/manual', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(490007, $ret['code']);
        $this->assertEquals('Oversize amount given which exceeds the MAX', $ret['msg']);
    }

    /**
     * 測試人工存入代入不合法的RefId
     */
    public function testManualWithIllegalRefId()
    {
        $client = $this->createClient();

        $parameters = [
            'opcode' => 1010,
            'amount' => 900,
            'ref_id' => 'abeiiiek00'
        ];

        $client->request('PUT', '/api/user/8/manual', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(490002, $ret['code']);
        $this->assertEquals('Invalid ref_id', $ret['msg']);
    }

    /**
     * 測試人工存入代入空值的RefId
     */
    public function testManualWithEmptyRefId()
    {
        $client = $this->createClient();

        $parameters = [
            'opcode' => 1010,
            'amount' => 900,
            'ref_id' => ''
        ];

        $client->request('PUT', '/api/user/8/manual', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(490002, $ret['code']);
        $this->assertEquals('Invalid ref_id', $ret['msg']);
    }

    /**
     * 測試人工存入代入不合法的存款優惠
     */
    public function testManualWithIllegalOffer()
    {
        $client = $this->createClient();

        $parameters = [
            'opcode' => 1010,
            'amount' => 500,
            'memo' => 'hrhr amount',
            'offer' => 25.66999696969,
        ];

        $client->request('PUT', '/api/user/8/manual', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150610003, $ret['code']);
        $this->assertEquals('The decimal digit of amount exceeds limitation', $ret['msg']);
    }

    /**
     * 測試人工存入代入不合法的匯款優惠
     */
    public function testManualWithIllegalRemitOffer()
    {
        $client = $this->createClient();

        $parameters = [
            'opcode' => 1010,
            'amount' => 500,
            'memo' => 'hrhr amount',
            'offer' => 25,
            'remit_offer' => 25.7789898,
        ];

        $client->request('PUT', '/api/user/8/manual', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150610003, $ret['code']);
        $this->assertEquals('The decimal digit of amount exceeds limitation', $ret['msg']);
    }

    /**
     * 測試人工存入時找不到相應的匯率
     */
    public function testManualFindNoExchange()
    {
        $client = $this->createClient();
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $exRepo = $emShare->getRepository('BBDurianBundle:Exchange');
        $exchanges = $exRepo->findBy(['currency' => '901']);

        foreach ($exchanges as $exchange) {
            $emShare->remove($exchange);
        }
        $emShare->flush();

        $parameters = [
            'opcode' => 1010,
            'amount' => 500,
            'memo' => 'hrhr amount',
            'ref_id' => 1556899,
            'operator' => 'dindin'
        ];

        $client->request('PUT', '/api/user/8/manual', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(490005, $output['code']);
        $this->assertEquals('No such exchange', $output['msg']);
    }

    /**
     * 測試人工存入時發生重複紀錄的Exception
     */
    public function testManualButDuplicateRecord()
    {
        $idGenerator = $this->getContainer()->get('durian.cash_entry_id_generator');
        $cashEntryId = $idGenerator->generate();
        $client = $this->createClient();

        $pdoExcep = new \PDOException('Duplicate', 1062);
        $pdoExcep->errorInfo[1] = 1062;

        $exception = new \Exception('Database busy', 490008, $pdoExcep);
        $mockEm = $this->mockEntityManagerForException($exception);
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm['default']);
        $client->getContainer()->set('doctrine.orm.share_entity_manager', $mockEm['share']);

        $parameters = [
            'opcode' => 1010,
            'amount' => 500,
            'offer' => 25,
            'remit_offer' => 25,
            'ref_id' => 1556899,
            'operator' => 'dindin'
        ];

        $client->request('PUT', '/api/user/8/manual', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $redisWallet = $this->getContainer()->get('snc_redis.wallet2');

        // 檢查pre_sub是否有資料
        $pre_sub = $redisWallet->hget('cash_balance_8_901', 'pre_sub');
        $this->assertNull($pre_sub);

        // 檢查balance是否有資料
        $balance = $redisWallet->hget('cash_balance_8_901', 'balance');
        $this->assertNull($balance);

        // 檢查cash_sync_queue是否有資料
        $syncMsg = $redis->lpop('cash_sync_queue');
        $this->assertNull($syncMsg);

        // 檢查cash_queue是否有資料
        $queueMsg = $redis->lpop('cash_queue');
        $this->assertNull($queueMsg);

        // 檢查key是否有刪除
        $tRedisWallet = $this->getContainer()->get('snc_redis.wallet1');
        $this->assertNull($tRedisWallet->get("en_cashtrans_id_$cashEntryId"));

        // 檢查輸出資訊
        $this->assertEquals('error', $output['result']);
        $this->assertEquals(490008, $output['code']);
        $this->assertEquals('Database is busy', $output['msg']);
    }

    /**
     * 測試人工存入時發生一般性的Exception(非OptimisticLockException及重複紀錄的例外)
     */
    public function testManualButExceptionOccur()
    {
        $idGenerator = $this->getContainer()->get('durian.cash_entry_id_generator');
        $cashEntryId = $idGenerator->generate();
        $client = $this->createClient();

        $pdoExcep = new \PDOException('Duplicate', 1063);
        $pdoExcep->errorInfo[1] = 1063;

        $exception = new \Exception('MySQL server has gone away', 2006, $pdoExcep);
        $mockEm = $this->mockEntityManagerForException($exception);
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm['default']);
        $client->getContainer()->set('doctrine.orm.share_entity_manager', $mockEm['share']);

        $parameters = [
            'opcode' => 1010,
            'amount' => 500,
            'offer' => 25,
            'remit_offer' => 25,
            'ref_id' => 1556899,
            'operator' => 'dindin'
        ];

        $client->request('PUT', '/api/user/8/manual', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $redisWallet = $this->getContainer()->get('snc_redis.wallet2');

        // 檢查pre_sub是否有資料
        $pre_sub = $redisWallet->hget('cash_balance_8_901', 'pre_sub');
        $this->assertNull($pre_sub);

        // 檢查balance是否有資料
        $balance = $redisWallet->hget('cash_balance_8_901', 'balance');
        $this->assertNull($balance);

        // 檢查cash_sync_queue是否有資料
        $syncMsg = $redis->lpop('cash_sync_queue');
        $this->assertNull($syncMsg);

        // 檢查cash_queue是否有資料
        $queueMsg = $redis->lpop('cash_queue');
        $this->assertNull($queueMsg);

        // 檢查key是否有刪除
        $tRedisWallet = $this->getContainer()->get('snc_redis.wallet1');
        $this->assertNull($tRedisWallet->get("en_cashtrans_id_$cashEntryId"));

        // 檢查輸出資訊
        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150780001, $output['code']);
        $this->assertEquals('Database is busy', $output['msg']);
    }

    /**
     * 回傳mock發生Exception所需的entity manager
     *
     * @param OptimisticLockException | \Exception $exception
     * @return array
     */
    private function mockEntityManagerForException($exception)
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $user8      = $em->find('BBDurianBundle:User', 8);
        $exchangel  = $emShare->find('BBDurianBundle:Exchange', 1);
        $urd        = $em->find('BBDurianBundle:UserRemitDiscount', 1);

        // mock entity manager
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods([
                'find',
                'beginTransaction',
                'getRepository',
                'flush',
                'rollback',
                'persist',
                'commit',
                'clear'
            ])
            ->getMock();

        $mockEmShare = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $mockEm->expects($this->at(0))
            ->method('find')
            ->will($this->returnValue($user8));

        $entityRepo= $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();

        $entityRepoShare= $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findByCurrencyAt'])
            ->getMock();

        $entityRepo->expects($this->at(0))
            ->method('findOneBy')
            ->will($this->returnValue($urd));

        $entityRepoShare->expects($this->any())
            ->method('findByCurrencyAt')
            ->will($this->returnValue($exchangel));

        $mockEm->expects($this->any())
            ->method('flush')
            ->will($this->throwException($exception));

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($entityRepo));

        $mockEmShare->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepoShare);

        return [
            'default' => $mockEm,
            'share' => $mockEmShare
        ];
    }
}
