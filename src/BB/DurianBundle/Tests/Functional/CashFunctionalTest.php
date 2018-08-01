<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\Cash;
use BB\DurianBundle\Entity\CashEntry;
use BB\DurianBundle\Entity\User;

class CashFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCardData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashTransData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentDepositWithdrawEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankInfoData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankCurrencyData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserDetailData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserPaywayData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserHasDepositWithdrawData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashNegativeData'
        ];

        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashEntryData'
        ];

        $this->loadFixtures($classnames, 'entry');
        $this->loadFixtures($classnames, 'his');

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadExchangeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashErrorData'
        ];

        $this->loadFixtures($classnames, 'share');

        $redis = $this->getContainer()->get('snc_redis.sequence');

        $redis->set('cash_seq', 1000);
        $redis->set('card_seq', 1000);

        $redis = $this->getContainer()->get('snc_redis.total_balance');
        $redis->hset('cash_total_balance_2_901', 'normal', 10000000);
        $redis->hset('cash_total_balance_2_901', 'test', 0);
    }

    /**
     * 測試新增現金資料
     */
    public function testNewCashCorrect()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $parameters = array('currency' => 'TWD');

        $client->request('POST', '/api/user/10/cash', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $cash = $em->find('BBDurianBundle:Cash', $ret['ret']['id']);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals("cash", $logOperation->getTableName());
        $this->assertEquals("@user_id:10", $logOperation->getMajorKey());
        $this->assertEquals("@currency:TWD", $logOperation->getMessage());

        $this->assertEquals($cash->getUser()->getId(), $ret['ret']['user_id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['pre_add']);
        $this->assertEquals('TWD', $ret['ret']['currency']);

        // 檢查 payway
        $payway = $em->find('BBDurianBundle:UserPayway', 10);
        $this->assertTrue($payway->isCashEnabled());
        $this->assertFalse($payway->isCashFakeEnabled());
        $this->assertFalse($payway->isCreditEnabled());

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals("user_payway", $logOperation->getTableName());
        $this->assertEquals("@user_id:10", $logOperation->getMajorKey());
        $this->assertEquals("@cash:false=>true", $logOperation->getMessage());

        // 測試user本身有上層,但沒有payway
        $client->request('POST', '/api/user/50/cash', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        // 新增帳號
        $user = new User();
        $user->setId(30);
        $user->setUsername('mtester');
        $user->setAlias('mtester');
        $user->setPassword('mtester');
        $user->setDomain(30);
        $em->persist($user);

        $em->flush();
        $em->clear();

        // 測試user本身沒有上層,且沒有payway
        $client->request('POST', '/api/user/30/cash', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
    }

    /**
     * 測試回傳現金物件
     */
    public function testGetCashInfo()
    {
        $client = $this->createClient();

        $parameters = array(
            'users'  => array(2),
            'fields' => array('cash')
        );

        $client->request('GET', '/api/users', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals(2, $ret['ret'][0]['id']);
        $this->assertEquals(2, $ret['ret'][0]['cash']['user_id']);
        $this->assertEquals(1000, $ret['ret'][0]['cash']['balance']);
        $this->assertEquals(0, $ret['ret'][0]['cash']['pre_sub']);
        $this->assertEquals(0, $ret['ret'][0]['cash']['pre_add']);
        $this->assertEquals('TWD', $ret['ret'][0]['cash']['currency']);
    }

    /**
     * 測試傳入id回傳現金物件
     */
    public function testGetCashById()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/cash/1');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals(2, $ret['ret']['user_id']);
        $this->assertEquals(1000, $ret['ret']['balance']);
        $this->assertEquals(0, $ret['ret']['pre_sub']);
        $this->assertEquals(0, $ret['ret']['pre_add']);
        $this->assertEquals('TWD', $ret['ret']['currency']);
        $this->assertEquals(20120101120000, $ret['ret']['last_entry_at']);
    }

    /**
     * 測試回傳現金資料,發生找不到cash的狀況
     */
    public function testGetCashButNoCashFound()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/cash/100');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150040002, $ret['code']);
        $this->assertEquals('No cash found', $ret['msg']);
    }

    /**
     * 測試帶入userId回傳現金餘額
     */
    public function testGetCashByUserId()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/2/cash');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertEquals(2, $output['ret']['user_id']);
        $this->assertEquals(1000, $output['ret']['balance']);
        $this->assertEquals(0, $output['ret']['pre_sub']);
        $this->assertEquals(0, $output['ret']['pre_add']);
        $this->assertEquals('TWD', $output['ret']['currency']);
        $this->assertEquals(20120101120000, $output['ret']['last_entry_at']);
    }

    /**
     * 測試帶入userId回傳現金餘額，但現金不存在
     */
    public function testGetCashByUserIdButNoCashFound()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/50/cash');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150040002, $output['code']);
        $this->assertEquals('No cash found', $output['msg']);
    }

    /**
     * Data provider
     * @return array
     */
    public function provider()
    {
        /**
         * opcode, amount, before_balance, after_balance, tag_info
         */
        return array(
            array(1001, 999, 1000, 1999), // 1001 DEPOSIT
            array(1002, -200, 1000, 800, '123456789'), // 1002 WITHDRAWAL
            array(10001, 400, 1000, 1400), //10001 PAYOFF
            array(10002, -400, 1000, 600), // 10002 BETTING
            array(10003, -400, 1000, 600), // 10003 RE_PAYOFF
            array(10005, -400, 1000, 600), // 10005 UNCANCEL
        );
    }

    /**
     * 測試各種出入款項目。資料由provider提供
     *
     * @dataProvider provider
     */
    public function testMultipleOpCase($opcode, $amount, $before, $after, $tag = null)
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BB\DurianBundle\Entity\User', 8);
        $this->assertEquals($before, $user->getCash()->getBalance());

        $em->clear();

        $parameters = array(
            'opcode'      => $opcode,
            'amount'      => $amount,
            'tag'         => $tag,
            'auto_commit' => true
        );

        $client->request('PUT', '/api/user/8/cash/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret  = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals($opcode, $ret['ret']['entry']['opcode']);
        $this->assertEquals($amount, $ret['ret']['entry']['amount']);
        $this->assertEquals($after, $ret['ret']['entry']['balance']);
        $this->assertEquals($tag, $ret['ret']['entry']['tag']);
        $this->assertEquals('', $ret['ret']['entry']['ref_id']);
        $this->assertEquals(3, $ret['ret']['entry']['cash_version']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['cash']['user_id']);
        $this->assertEquals($after, $ret['ret']['cash']['balance']);
        $this->assertEquals(0, $ret['ret']['cash']['pre_sub']);
        $this->assertEquals(0, $ret['ret']['cash']['pre_add']);
        $this->assertEquals('TWD', $ret['ret']['cash']['currency']);
    }

    /**
     * Data provider
     * @return array
     */
    public function providerByTrans()
    {
        /**
         * opcode, amount, preSub, preAdd, afterBalance
         */
        return array(
            array(1001,  999, 0, 999, 1999),//1001 DEPOSIT
            array(1002, -200, 200, 0, 800),//1002 WITHDRAWAL
            array(10001, 400, 0, 400, 1400),//10001 PAYOFF
            array(10002, -400, 400, 0, 600),//10002 BETTING
            array(10003, -400, 400, 0, 600),//10003 RE_PAYOFF
            array(10005, -400, 400, 0, 600),//10005 UNCANCEL
        );
    }

    /**
     * 測試各種出入款項目。資料由providerByTrans提供
     *
     * @dataProvider providerByTrans
     */
    public function testMultipleOpCaseByTrans($opcode, $amount, $preSub, $preAdd)
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BB\DurianBundle\Entity\User', 8);
        $this->assertEquals(1000, $user->getCash()->getBalance());
        $this->assertEquals(0, $user->getCash()->getPreAdd());
        $this->assertEquals(0, $user->getCash()->getPreSub());

        $em->clear();

        $parameters = array(
            'opcode'      => $opcode,
            'amount'      => $amount,
            'auto_commit' => false
        );

        $client->request('PUT', '/api/user/8/cash/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret  = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals($opcode, $ret['ret']['entry']['opcode']);
        $this->assertEquals($amount, $ret['ret']['entry']['amount']);
        $this->assertEquals('', $ret['ret']['entry']['ref_id']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['cash']['user_id']);
        $this->assertEquals(1000, $ret['ret']['cash']['balance']);
        $this->assertEquals('TWD', $ret['ret']['cash']['currency']);
    }

    /**
     * 測試各種出入款確認狀態。資料由providerByTrans提供
     *
     * @dataProvider providerByTrans
     */
    public function testMultipleTransactionCommit($opcode, $amount, $preSub, $preAdd, $afterBalance)
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redisWallet = $this->getContainer()->get('snc_redis.wallet4');
        $client = $this->createClient();
        $user = $em->find('BB\DurianBundle\Entity\User', 8);

        $parameters = array(
            'opcode'      => $opcode,
            'amount'      => $amount,
            'auto_commit' => false
        );

        $client->request('PUT', '/api/user/8/cash/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret  = json_decode($json, true);

        //commit之前值只會存在redis,所以檢查redis中的資料
        $cash = $user->getCash();
        $cashKey = 'cash_balance_8_901';
        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $redisBalance = $redisWallet->hget($cashKey, 'balance')/10000;
        $this->assertEquals($redisBalance, $ret['ret']['cash']['balance']);
        $this->assertEquals($preAdd, $redisWallet->hget($cashKey, 'pre_add')/10000);
        $this->assertEquals($preSub, $redisWallet->hget($cashKey, 'pre_sub')/10000);

        $em->clear();

        $transId = $ret['ret']['entry']['id'];

        $client->request('PUT', '/api/cash/transaction/'.$transId.'/commit');

        $json = $client->getResponse()->getContent();
        $ret  = json_decode($json, true);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals(8, $ret['ret']['entry']['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry']['currency']);
        $this->assertEquals($opcode, $ret['ret']['entry']['opcode']);
        $this->assertEquals($amount, $ret['ret']['entry']['amount']);
        $this->assertEquals($afterBalance, $ret['ret']['entry']['balance']);
        $this->assertEquals(4, $ret['ret']['entry']['cash_version']);
        $this->assertEquals($afterBalance, $ret['ret']['cash']['balance']);
        $this->assertEquals('', $ret['ret']['entry']['ref_id']);

        $user = $em->find('BB\DurianBundle\Entity\User', 8);

        $this->assertEquals($afterBalance, $user->getCash()->getBalance());
    }

    /**
     * 測試交易機制的op
     */
    public function testOpWithTransaction()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default');
        $redisWallet = $this->getContainer()->get('snc_redis.wallet4');
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $entryQueueLogPath = $logsDir . '/test/queue/sync_cash_entry_queue.log';
        $queueLogPath = $logsDir . '/test/queue/sync_cash_queue.log';
        $client = $this->createClient();

        // 設定最後交易時間
        $preAt = 19990101235959;
        $cash = $em->find('BBDurianBundle:Cash', 7);
        $cash->setLastEntryAt($preAt);
        $em->flush();
        $em->clear();

        $user = $em->find('BBDurianBundle:User', 8);

        $this->assertEquals(1000, $user->getCash()->getBalance());
        $this->assertEquals(0, $user->getCash()->getPreSub());
        $this->assertEquals(0, $user->getCash()->getPreAdd());

        $parameters = [
            'opcode'      => 1041,
            'amount'      => -999,
            'operator'    => 'tester',
            'auto_commit' => false
        ];

        $client->request('PUT', '/api/user/8/cash/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret  = json_decode($json, true);
        $at = (new \DateTime($ret['ret']['entry']['created_at']))->format('YmdHis');

        $this->assertEquals(8, $ret['ret']['entry']['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry']['currency']);
        $this->assertEquals(1041, $ret['ret']['entry']['opcode']);
        $this->assertEquals(-999, $ret['ret']['entry']['amount']);
        $this->assertEquals('', $ret['ret']['entry']['tag']);
        $this->assertEquals(1000, $ret['ret']['cash']['balance']);
        $this->assertEquals(999, $ret['ret']['cash']['pre_sub']);
        $this->assertEquals(0, $ret['ret']['cash']['pre_add']);

        $key = 'cash_balance_8_901';

        $this->assertEquals(1000, $redisWallet->hget($key, 'balance')/10000);
        $this->assertEquals(999, $redisWallet->hget($key, 'pre_sub')/10000);
        $this->assertEquals(0, $redisWallet->hget($key, 'pre_add')/10000);

        $this->assertEquals(1, $redis->llen('cash_sync_queue'));

        $transId = $ret['ret']['entry']['id'];

        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $entryQueueContent = file_get_contents($entryQueueLogPath);
        $entryQueueResults = explode(PHP_EOL, $entryQueueContent);

        $this->assertContains('cash_trans', $entryQueueResults[0]);

        $queueContent = file_get_contents($queueLogPath);
        $queueResults = explode(PHP_EOL, $queueContent);

        $this->assertContains($key, $queueResults[0]);

        $cash = $em->find('BBDurianBundle:Cash', 7);
        $this->assertEquals($preAt, $cash->getLastEntryAt());
        $em->clear();

        $user = $em->find('BBDurianBundle:User', 8);

        $this->assertEquals(1000, $user->getCash()->getBalance());
        $this->assertEquals(999, $user->getCash()->getPreSub());
        $this->assertEquals(0, $user->getCash()->getPreAdd());

        // 檢查cash trans
        $trans = $em->getRepository('BBDurianBundle:CashTrans')
            ->findBy(['userId' => $user->getId()], ['id' => 'desc']);

        $this->assertEquals(8, $trans[0]->getUserId());
        $this->assertEquals($parameters['opcode'], $trans[0]->getOpcode());
        $this->assertEquals($parameters['amount'], $trans[0]->getAmount());
        $this->assertEquals(false, $trans[0]->isChecked());

        $em->clear();

        // commit
        $client->request('PUT', '/api/cash/transaction/' . $transId . '/commit');

        $this->assertEquals(1, $redisWallet->hget($key, 'balance')/10000);
        $this->assertEquals(0, $redisWallet->hget($key, 'pre_sub')/10000);
        $this->assertEquals(0, $redisWallet->hget($key, 'pre_add')/10000);

        $json = $client->getResponse()->getContent();
        $ret  = json_decode($json, true);

        // commit後跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $entryQueueContent = file_get_contents($entryQueueLogPath);
        $entryQueueResults = explode(PHP_EOL, $entryQueueContent);

        $this->assertContains('cash_entry', $entryQueueResults[1]);
        $this->assertContains('cash_entry_operator', $entryQueueResults[2]);
        $this->assertContains('payment_deposit_withdraw_entry', $entryQueueResults[3]);
        $this->assertContains('cash_trans', $entryQueueResults[4]);

        $queueContent = file_get_contents($queueLogPath);
        $queueResults = explode(PHP_EOL, $queueContent);

        $this->assertContains($key, $queueResults[1]);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals(1041, $ret['ret']['entry']['opcode']);
        $this->assertEquals(-999, $ret['ret']['entry']['amount']);
        $this->assertEquals('', $ret['ret']['entry']['tag']);
        $this->assertEquals(5, $ret['ret']['entry']['cash_version']);
        $this->assertEquals(1, $ret['ret']['cash']['balance']);

        // 檢查payment deposit withdraw entry
        $criteria = ['id' => 1001];
        $pdweRepo = $em->getRepository('BBDurianBundle:PaymentDepositWithdrawEntry');
        $pdwe = $pdweRepo->findOneBy($criteria);

        $this->assertEquals(0, $pdwe->getMerchantId());
        $this->assertEquals(1041, $pdwe->getOpcode());
        $this->assertEquals(-999, $pdwe->getAmount());
        $this->assertEquals($parameters['operator'], $pdwe->getOperator());

        // 檢查餘額
        $user = $em->find('BBDurianBundle:User', 8);

        $this->assertEquals(1, $user->getCash()->getBalance());
        $this->assertEquals(0, $user->getCash()->getPreSub());
        $this->assertEquals(0, $user->getCash()->getPreAdd());

        // 檢查cash trans
        $trans = $em->getRepository('BBDurianBundle:CashTrans')
            ->findBy(['userId' => $user->getId()], ['id' => 'desc']);

        $this->assertEquals(8, $trans[0]->getUserId());
        $this->assertEquals($parameters['opcode'], $trans[0]->getOpcode());
        $this->assertEquals($parameters['amount'], $trans[0]->getAmount());
        $this->assertEquals(true, $trans[0]->isChecked());
    }

    /**
     * 測試兩階段交易opcode為1098時不更新明細時間
     */
    public function testTransactionWithoutUpdateAt()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $parameters = [
            'opcode'      => 1098,
            'amount'      => -1,
            'operator'    => 'tester',
            'auto_commit' => false
        ];

        $ret = $this->getResponse('PUT', '/api/user/8/cash/op', $parameters);

        $this->assertEquals('ok', $ret['result']);

        $output = $this->getResponse('PUT', '/api/cash/transaction/' . $ret['ret']['entry']['id'] . '/commit');

        $this->assertEquals('ok', $output['result']);

        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $cash = $em->find('BBDurianBundle:Cash', 7);
        $this->assertEquals(20120101120000, $cash->getLastEntryAt());
    }

    /**
     * 測試使用測試帳號兩階段交易opcode為1098時不更新明細時間
     */
    public function testTransactionTestUserWithoutUpdateAt()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setTest(true);
        $em->flush();

        $parameters = [
            'opcode'      => 1098,
            'amount'      => -1,
            'operator'    => 'tester',
            'auto_commit' => false
        ];

        $ret = $this->getResponse('PUT', '/api/user/8/cash/op', $parameters);

        $this->assertEquals('ok', $ret['result']);

        $output = $this->getResponse('PUT', '/api/cash/transaction/' . $ret['ret']['entry']['id'] . '/commit');

        $this->assertEquals('ok', $output['result']);

        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $cash = $em->find('BBDurianBundle:Cash', 7);
        $this->assertEquals(20120101120000, $cash->getLastEntryAt());
    }

    /**
     * 測試各種出入款取消狀態。
     */
    public function testTransactionRollBack()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redisWallet = $this->getContainer()->get('snc_redis.wallet4');
        $client = $this->createClient();

        $user = $em->find('BBDurianBundle:User', 8);
        $cash = $user->getCash();

        $this->assertEquals(7, $cash->getId());
        $this->assertEquals(1000, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreAdd());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(2, $cash->getVersion());

        $cashKey = 'cash_balance_8_901';

        $parameters = array(
            'opcode'      => 1002, //WITHDRAWAL
            'amount'      => -999,
            'operator'    => 'tester',
            'auto_commit' => false
        );

        $client->request('PUT', '/api/user/8/cash/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret  = json_decode($json, true);

        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals($redisWallet->hget($cashKey, 'balance') / 10000, $ret['ret']['cash']['balance']);
        $this->assertEquals($redisWallet->hget($cashKey, 'pre_add') / 10000, $ret['ret']['cash']['pre_add']);
        $this->assertEquals($redisWallet->hget($cashKey, 'pre_sub') / 10000, $ret['ret']['cash']['pre_sub']);
        $this->assertEquals(3, $redisWallet->hget($cashKey, 'version'));

        $transId = $ret['ret']['entry']['id'];

        $client->request('PUT', '/api/cash/transaction/'.$transId.'/rollback');

        $json = $client->getResponse()->getContent();
        $ret  = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals(8, $ret['ret']['entry']['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry']['currency']);
        $this->assertEquals(1002, $ret['ret']['entry']['opcode']);
        $this->assertEquals(-999, $ret['ret']['entry']['amount']);
        $this->assertEquals(1000, $ret['ret']['cash']['balance']);
        $this->assertEquals('', $ret['ret']['entry']['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry']['operator']['username']);

        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals($redisWallet->hget($cashKey, 'balance') / 10000, $ret['ret']['cash']['balance']);
        $this->assertEquals(0, $redisWallet->hget($cashKey, 'pre_add') / 10000);
        $this->assertEquals(0, $redisWallet->hget($cashKey, 'pre_sub') / 10000);
        $this->assertEquals(4, $redisWallet->hget($cashKey, 'version'));
    }

    /**
     * 測試op可以送memo
     */
    public function testCashOpCanSubmitMemo()
    {
        $client = $this->createClient();

        $parameters = array(
            'opcode'      => 1001, // 1001 DEPOSIT
            'amount'      => 999,
            'memo'        => 'Test memo',
            'auto_commit' => true
        );

        $client->request('PUT', '/api/user/8/cash/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals('Test memo', $ret['ret']['entry']['memo']);
    }

    /**
     * 測試op的memo設0時是否寫入正確
     */
    public function testCashOpMemoIsZero()
    {
        $client = $this->createClient();

        $parameters = array(
            'opcode'      => 1001, // 1001 DEPOSIT
            'amount'      => 999,
            'memo'        => '0',
            'auto_commit' => true
        );

        $client->request('PUT', '/api/user/8/cash/op', $parameters);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals('0', $ret['ret']['entry']['memo']);

        // 檢查明細的memo是否為0
        $em = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $entries = $em->getRepository('BBDurianBundle:CashEntry')
            ->findBy(['cashId' => 7], ['id' => 'desc']);
        $entry = $entries[0]->toArray();
        $this->assertEquals('0', $entry['memo']);
    }

    /**
     * 測試OP當force_copy = true時,refId為明細編號
     */
    public function testCashOpWithForceCopy()
    {
        $client = $this->createClient();

        $parameters = [
            'opcode'      => 1001, // 1001 DEPOSIT
            'amount'      => 99,
            'memo'        => '0',
            'auto_commit' => false,
            'force_copy'  => true
        ];

        $client->request('PUT', '/api/user/8/cash/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);
        $this->assertEquals($ret['ret']['entry']['id'], $ret['ret']['entry']['ref_id']);

        $transId = $ret['ret']['entry']['id'];

        // commit
        $client->request('PUT', '/api/cash/transaction/'.$transId.'/commit');

        $json = $client->getResponse()->getContent();
        $ret  = json_decode($json, true);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals('0', $ret['ret']['entry']['memo']);
        $this->assertEquals('99', $ret['ret']['entry']['amount']);
        $this->assertEquals($ret['ret']['entry']['id'], $ret['ret']['entry']['ref_id']);

        // 檢查明細memo是否為0
        $em = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $entries = $em->getRepository('BBDurianBundle:CashEntry')
            ->findBy(['cashId' => 7], ['id' => 'desc']);
        $entry = $entries[0]->toArray();

        $this->assertEquals('0', $entry['memo']);
        $this->assertEquals('99', $entry['amount']);
        $this->assertEquals($entry['id'], $entry['ref_id']);

        $parameters = [
            'opcode'      => 1001, // 1001 DEPOSIT
            'amount'      => 99,
            'memo'        => '0',
            'auto_commit' => true,
            'force_copy'  => true
        ];

        $client->request('PUT', '/api/user/8/cash/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);
        $this->assertEquals($ret['ret']['entry']['id'], $ret['ret']['entry']['ref_id']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals('0', $ret['ret']['entry']['memo']);
        $this->assertEquals('99', $ret['ret']['entry']['amount']);
        $this->assertEquals($ret['ret']['entry']['id'], $ret['ret']['entry']['ref_id']);

        // 檢查明細memo是否為0
        $entries = $em->getRepository('BBDurianBundle:CashEntry')
            ->findBy(['cashId' => 7], ['id' => 'desc']);
        $entry = $entries[0]->toArray();

        $this->assertEquals('0', $entry['memo']);
        $this->assertEquals('99', $entry['amount']);
        $this->assertEquals($entry['id'], $entry['ref_id']);
    }

    /**
     * 測試當auto commit = false時, op的memo設0時是否寫入正確
     */
    public function testCashOpMemoIsZeroWithTransaction()
    {
        $client = $this->createClient();

        $parameters = array(
            'opcode'      => 1001, // 1001 DEPOSIT
            'amount'      => 99,
            'memo'        => '0',
            'auto_commit' => false
        );

        $client->request('PUT', '/api/user/8/cash/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);
        $transId = $ret['ret']['entry']['id'];

        // commit
        $client->request('PUT', '/api/cash/transaction/'.$transId.'/commit');

        $json = $client->getResponse()->getContent();
        $ret  = json_decode($json, true);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals('0', $ret['ret']['entry']['memo']);
        $this->assertEquals('99', $ret['ret']['entry']['amount']);

        // 檢查明細memo是否為0
        $em = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $entries = $em->getRepository('BBDurianBundle:CashEntry')
            ->findBy(['cashId' => 7], ['id' => 'desc']);
        $entry = $entries[0]->toArray();

        $this->assertEquals('0', $entry['memo']);
        $this->assertEquals('99', $entry['amount']);
    }

    /**
     * 測試當auto commit = true時, op的memo字數超過預設值時是否寫入正確
     */
    public function testCashOpMemoIsOverSize()
    {
        $client = $this->createClient();

        $memo = '';
        for ($i = 0; $i < 100; $i++) {
            $memo .= 'a';
        }

        $parameters = [
            'opcode' => 1001,
            'amount' => 999,
            'memo' => $memo . '012',
            'auto_commit' => true
        ];

        $client->request('PUT', '/api/user/8/cash/op', $parameters);

        // 跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($memo, $ret['ret']['entry']['memo']);

        // 檢查明細的memo是否正確
        $em = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $entry = $em->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $ret['ret']['entry']['id']]);

        $this->assertEquals($memo, $entry->getMemo());
    }

    /**
     * 測試當auto commit = false時, op的memo字數超過預設值時是否寫入正確
     */
    public function testCashOpMemoIsOverSizeWithTransaction()
    {
        $client = $this->createClient();

        $memo = '';
        for ($i = 0; $i < 100; $i++) {
            $memo .= 'a';
        }

        $parameters = [
            'opcode' => 1001,
            'amount' => 99,
            'memo' => $memo . '012',
            'auto_commit' => false
        ];

        $client->request('PUT', '/api/user/8/cash/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);
        $transId = $ret['ret']['entry']['id'];

        // commit
        $client->request('PUT', '/api/cash/transaction/'.$transId.'/commit');

        $json = $client->getResponse()->getContent();
        $ret  = json_decode($json, true);

        // 跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($memo, $ret['ret']['entry']['memo']);
        $this->assertEquals('99', $ret['ret']['entry']['amount']);

        // 檢查明細的memo是否正確
        $em = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $entry = $em->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $ret['ret']['entry']['id']]);

        $this->assertEquals($memo, $entry->getMemo());
    }

    /**
     * 測試op但輸入金額為臨界值
     */
    public function testCashOpWithCriticalBalance()
    {
        $client = $this->createClient();

        //測試臨界值,MAX_BALANCE為10000000000,一開始load進來的資料balance為1000
        //所以最大只能允許到9999999000
        $parameters = [
            'opcode'      => 1001, // 1001 DEPOSIT
            'amount'      => 9999999000,
            'memo'        => 'Oops, you are too rich, I hate you',
            'auto_commit' => true
        ];

        $client->request('PUT', '/api/user/8/cash/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);
        $this->assertEquals('ok', $ret['result']);
    }

    /**
     * 測試輸入金額過大,導致餘額超出範圍的錯誤
     */
    public function testCashOpButBalanceExceedMAXBalance()
    {
        $client = $this->createClient();

        //測試臨界值,MAX_BALANCE為10000000000,一開始load進來的資料balance為1000
        //加總後為10000000001
        $parameters = [
            'opcode'      => 1001, // 1001 DEPOSIT
            'amount'      => 9999999001,
            'memo'        => 'Oops, you are too rich, I hate you',
            'auto_commit' => true
        ];

        $client->request('PUT', '/api/user/8/cash/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150580009, $ret['code']);
        $this->assertEquals('The balance exceeds the MAX amount', $ret['msg']);
    }

    /**
     * 測試op可以送參考編號
     */
    public function testCashOpCanSubmitRefId()
    {
        $client = $this->createClient();

        $parameters = array(
            'opcode'      => 1001, // 1001 DEPOSIT
            'amount'      => 999,
            'ref_id'      => 1234567890123456789,
            'auto_commit' => true
        );

        $client->request('PUT', '/api/user/8/cash/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(1234567890123456789, $ret['ret']['entry']['ref_id']);
    }

    /**
     * 測試op檢查redis是否新增會員總餘額紀錄
     */
    public function testCashOpWithRedisCashTotalBalance()
    {
        $redis = $this->getContainer()->get('snc_redis.total_balance');
        $client = $this->createClient();

        $amount = $redis->hget('cash_total_balance_2_901', 'normal');
        $this->assertEquals(1000, $amount / 10000);

        $parameters = [
            'opcode'      => 1001, // 1001 DEPOSIT
            'amount'      => 999,
            'ref_id'      => 1234567890123456789,
            'auto_commit' => true
        ];

        $client->request('PUT', '/api/user/8/cash/op', $parameters);

        $amount = $redis->hget('cash_total_balance_2_901', 'normal');
        $this->assertEquals(1999, $amount / 10000);
    }

    /**
     * 測試op時ref_id帶空字串會送0到queue並回傳空字串
     */
    public function testCashOpWithEmptyRefId()
    {
        $client = $this->createClient();
        $redis = $this->getContainer()->get('snc_redis.default');

        $parameters = [
            'opcode'      => 1001,
            'amount'      => 100,
            'ref_id'      => '',
            'auto_commit' => true
        ];

        $client->request('PUT', '/api/user/8/cash/op', $parameters);

        $queue = json_decode($redis->rpop('cash_queue'), true);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertSame(0, $queue['ref_id']);
        $this->assertEquals('', $ret['ret']['entry']['ref_id']);
    }

    /**
     * 測試cash op可以強制扣款
     */
    public function testCashOpWithForce()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // auto_commit = true時, 若加force參數, 則可以允許金額為0
        $parameters = [
            'opcode' => 1001, // 1001 DEPOSIT
            'amount' => 0,
            'ref_id' => '1234567',
            'auto_commit' => true,
            'force' => true
        ];

        $client->request('PUT', '/api/user/8/cash/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);
        $at = (new \DateTime($ret['ret']['entry']['created_at']))->format('YmdHis');

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret']['entry']['cash_id']);
        $this->assertEquals(1001, $ret['ret']['entry']['opcode']);
        $this->assertEquals(0, $ret['ret']['entry']['amount']);
        $this->assertEquals(1000, $ret['ret']['entry']['balance']);
        $this->assertEquals(3, $ret['ret']['entry']['cash_version']);

        // 跑背景更新餘額
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        // 確認交易時間有更新
        $cash = $em->find('BBDurianBundle:Cash', 7);
        $this->assertEquals($at, $cash->getLastEntryAt());

        // auto_commit = true時, 若加force參數, 則可以允許使用者停權
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setBankrupt(true);
        $em->persist($user);
        $em->flush();
        $em->clear();

        $parameters = [
            'opcode' => 1098,
            'amount' => -100,
            'ref_id' => '1234567',
            'auto_commit' => true,
            'force' => true
        ];

        $client->request('PUT', '/api/user/8/cash/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret']['entry']['cash_id']);
        $this->assertEquals(1098, $ret['ret']['entry']['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry']['amount']);
        $this->assertEquals(900, $ret['ret']['entry']['balance']);
        $this->assertEquals(4, $ret['ret']['entry']['cash_version']);

        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        // 確認opcode為1098會抓到上一筆時間
        $cash = $em->find('BBDurianBundle:Cash', 7);
        $this->assertEquals($at, $cash->getLastEntryAt());

        // auto_commit = true時, 若加force參數, 則可以強制把餘額扣到負數
        $user->setBankrupt(false);
        $em->flush();

        $parameters = [
            'opcode' => 1001, // 1001 DEPOSIT
            'amount' => -9999,
            'ref_id' => '1234567',
            'auto_commit' => true,
            'force' => true
        ];

        $client->request('PUT', '/api/user/8/cash/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret']['entry']['cash_id']);
        $this->assertEquals(1001, $ret['ret']['entry']['opcode']);
        $this->assertEquals(-9999, $ret['ret']['entry']['amount']);
        $this->assertEquals(-9099, $ret['ret']['entry']['balance']);
        $this->assertEquals(5, $ret['ret']['entry']['cash_version']);

        // auto_commit = false時, 若加force參數, 則可以允許金額為0
        $parameters = [
            'opcode' => 1001, // 1001 DEPOSIT
            'amount' => 0,
            'ref_id' => '1234567',
            'auto_commit' => false,
            'force' => true
        ];

        $client->request('PUT', '/api/user/7/cash/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(1001, $ret['ret']['entry']['opcode']);
        $this->assertEquals(0, $ret['ret']['entry']['amount']);
        $this->assertEquals(7, $ret['ret']['cash']['user_id']);
        $this->assertEquals(1000, $ret['ret']['cash']['balance']);
        $this->assertEquals(0, $ret['ret']['cash']['pre_sub']);
        $this->assertEquals(0, $ret['ret']['cash']['pre_add']);

        // auto_commit = false時, 若加force參數, 則可以允許使用者停權
        $user = $em->find('BBDurianBundle:User', 7);
        $user->setBankrupt(true);
        $em->persist($user);
        $em->flush();

        $parameters = [
            'opcode' => 40000,
            'amount' => -100,
            'ref_id' => '1234567',
            'auto_commit' => false,
            'force' => true
        ];

        $client->request('PUT', '/api/user/7/cash/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(40000, $ret['ret']['entry']['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry']['amount']);
        $this->assertEquals(7, $ret['ret']['cash']['user_id']);
        $this->assertEquals(1000, $ret['ret']['cash']['balance']);
        $this->assertEquals(100, $ret['ret']['cash']['pre_sub']);
        $this->assertEquals(0, $ret['ret']['cash']['pre_add']);

        // auto_commit = false時, 若加force參數, 則可以強制把餘額扣到負數
        $user->setBankrupt(false);
        $em->flush();

        $parameters = [
            'opcode' => 1001, // 1001 DEPOSIT
            'amount' => -9999,
            'ref_id' => '1234567',
            'auto_commit' => false,
            'force' => true
        ];

        $client->request('PUT', '/api/user/7/cash/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(1001, $ret['ret']['entry']['opcode']);
        $this->assertEquals(-9999, $ret['ret']['entry']['amount']);
        $this->assertEquals(7, $ret['ret']['cash']['user_id']);
        $this->assertEquals(1000, $ret['ret']['cash']['balance']);
        $this->assertEquals(10099, $ret['ret']['cash']['pre_sub']);
        $this->assertEquals(0, $ret['ret']['cash']['pre_add']);
    }

    /**
     * 測試現金操作,輸入沒有cash資料的user
     */
    public function testCashOpWithNoCashUser()
    {
        $client = $this->createClient();

        $parameters = [
            'opcode' => 1001,
            'amount' => 100
        ];

        $client->request('PUT', '/api/user/10/cash/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150040002, $output['code']);
        $this->assertEquals('No cash found', $output['msg']);
    }

    /**
     * 測試回傳交易預扣存
     */
    public function testGetTransaction()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user   = $em->find('BB\DurianBundle\Entity\User', 8);

        $em->clear();

        $parameters = array(
            'opcode'      => 10002, // BETTING
            'amount'      => -100,
            'memo'        => 'testMemo',
            'ref_id'      => 123456789,
            'auto_commit' => false
        );

        $client->request('PUT', '/api/user/8/cash/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);
        $cashTransId = $ret['ret']['entry']['id'];

        $client->request('GET', '/api/cash/transaction/' . $cashTransId);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $cash = $user->getCash();

        $this->assertEquals($cash->getId(), $ret['ret']['cash_id']);
        $this->assertEquals(8, $ret['ret']['user_id']);
        $this->assertEquals('TWD', $ret['ret']['currency']);
        $this->assertEquals(10002, $ret['ret']['opcode']);
        $this->assertEquals(-100, $ret['ret']['amount']);
        $this->assertEquals('testMemo', $ret['ret']['memo']);
        $this->assertEquals('123456789', $ret['ret']['ref_id']);
        $this->assertFalse($ret['ret']['checked']);
        $this->assertEquals('', $ret['ret']['checked_at']);

        $client->request('GET', '/api/cash/transaction/666');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);
        $this->assertEquals('error', $ret['result']);
        $this->assertEquals('No cashTrans found', $ret['msg']);
        $this->assertEquals(150040042, $ret['code']);
    }

    /**
     * 測試使用者存提款紀錄queue
     */
    public function testUserDepositWithdrawQueue()
    {
        $redis  = $this->getContainer()->get('snc_redis.default');
        $client = $this->createClient();

        // 測試存款
        $parameters = [
            'opcode'      => 1001,
            'amount'      => 100,
            'operator'    => 'tester',
            'auto_commit' => true
        ];

        $client->request('PUT', '/api/user/8/cash/op', $parameters);

        $queueName = 'cash_deposit_withdraw_queue';
        $this->assertEquals(1, $redis->llen($queueName));

        $queue = json_decode($redis->rpop($queueName), true);

        $this->assertEquals(0, $queue['ERRCOUNT']);
        $this->assertEquals(8, $queue['user_id']);
        $this->assertTrue($queue['deposit']);
        $this->assertFalse($queue['withdraw']);
        $this->assertNotNull($queue['deposit_at']);
    }

    /**
     * 測試force_copy = tru轉帳至沙巴,refId為明細編號
     */
    public function testTransferSabahWithForceCopy()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $client = $this->createClient();
        $user = $em->find('BB\DurianBundle\Entity\User', 8);
        $cash = $em->find('BB\DurianBundle\Entity\Cash', 7);

        $this->assertEquals(1000, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());

        $memo = '';
        for ($i = 0; $i < 100; $i++) {
            $memo .= 'a';
        }

        $parameters = [
            'vendor'    => 'SABAH',
            'amount'    => -100,
            'memo'      => $memo . '012',
            'ref_id'    => '',
            'operator'  => 'tester',
            'force_copy' => true
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $cashEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')->findBy(['cashId' => 7]);

        $em->refresh($cash);

        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1046, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1047, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals($memo, $ret['ret']['entry'][0]['memo']);
        $this->assertEquals($cashEntry[1]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('1001', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals($memo, $ret['ret']['entry'][1]['memo']);
        $this->assertEquals($cashEntry[2]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('1001', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);
        $this->assertEquals($cashEntry[1]->getCashVersion(), $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals($cashEntry[2]->getCashVersion(), $ret['ret']['entry'][1]['cash_version']);

        $parameters = [
            'vendor'   => 'SABAH',
            'amount'   => 100,
            'memo'     => $memo . '012',
            'ref_id'   => '',
            'operator' => 'tester',
            'force_copy' => true
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $cash = $user->getCash();

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $cashEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')->findBy(['cashId' => 7]);

        $em->refresh($cash);

        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1044, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1045, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals($memo, $ret['ret']['entry'][0]['memo']);
        $this->assertEquals($cashEntry[3]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('1003', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals($memo, $ret['ret']['entry'][1]['memo']);
        $this->assertEquals($cashEntry[4]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('1003', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);
        $this->assertEquals($cashEntry[3]->getCashVersion(), $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals($cashEntry[4]->getCashVersion(), $ret['ret']['entry'][1]['cash_version']);

        //測試非自動確認交易
        $parameters = [
            'vendor'      => 'SABAH',
            'amount'      => 100,
            'memo'        => $memo . '012',
            'ref_id'      => '',
            'operator'    => 'tester',
            'force_copy' => true,
            'auto_commit' => 0
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $em->refresh($cash);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals(100, $ret['ret']['cash']['pre_sub']);
        $this->assertEquals(100, $ret['ret']['cash']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1044, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals($memo, $ret['ret']['entry'][0]['memo']);
        $this->assertEquals(1005, $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals(1045, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals($memo, $ret['ret']['entry'][1]['memo']);
        $this->assertEquals(1005, $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);
    }

    /**
     * 測試轉帳至沙巴
     */
    public function testTransferSabah()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $client = $this->createClient();
        $user = $em->find('BB\DurianBundle\Entity\User', 8);
        $cash = $em->find('BB\DurianBundle\Entity\Cash', 7);

        $this->assertEquals(1000, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());

        $memo = '';
        for ($i = 0; $i < 100; $i++) {
            $memo .= 'a';
        }

        $parameters = array(
            'vendor'   => 'SABAH',
            'amount'   => -100,
            'memo'     => $memo . '012',
            'ref_id'   => 123456789,
            'operator' => 'tester',
        );

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $cashEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')->findBy(['cashId' => 7]);

        $em->refresh($cash);

        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1046, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1047, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals($memo, $ret['ret']['entry'][0]['memo']);
        $this->assertEquals($cashEntry[1]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals($memo, $ret['ret']['entry'][1]['memo']);
        $this->assertEquals($cashEntry[2]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);
        $this->assertEquals($cashEntry[1]->getCashVersion(), $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals($cashEntry[2]->getCashVersion(), $ret['ret']['entry'][1]['cash_version']);

        $parameters = array(
            'vendor'   => 'SABAH',
            'amount'   => 100,
            'memo'     => $memo . '012',
            'ref_id'   => 123456789,
            'operator' => 'tester',
        );

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $cash = $user->getCash();

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $cashEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')->findBy(['cashId' => 7]);

        $em->refresh($cash);

        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1044, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1045, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals($memo, $ret['ret']['entry'][0]['memo']);
        $this->assertEquals($cashEntry[3]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals($memo, $ret['ret']['entry'][1]['memo']);
        $this->assertEquals($cashEntry[4]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);
        $this->assertEquals($cashEntry[3]->getCashVersion(), $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals($cashEntry[4]->getCashVersion(), $ret['ret']['entry'][1]['cash_version']);
    }

    /**
     * 測試轉帳至AG視訊
     */
    public function testTransferAg()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user   = $em->find('BBDurianBundle:User', 8);
        $cash   = $em->find('BBDurianBundle:Cash', 7);

        $this->assertEquals(1000, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());

        $parameters = [
            'vendor'   => 'AG',
            'amount'   => -100,
            'memo'     => 'testMemo',
            'ref_id'   => 123456789,
            'operator' => 'tester',
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cash);

        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1078, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1079, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(3, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(4, $ret['ret']['entry'][1]['cash_version']);

        $parameters = [
            'vendor'   => 'AG',
            'amount'   => 100,
            'memo'     => 'testMemo2',
            'ref_id'   => 123456789,
            'operator' => 'tester',
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $cash = $user->getCash();

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cash);

        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1076, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(5, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(1077, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(6, $ret['ret']['entry'][1]['cash_version']);
    }

    /**
     * 測試轉帳至PT
     */
    public function testTransferToPT()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 8);
        $cash = $em->find('BBDurianBundle:Cash', 7);

        $this->assertEquals(1000, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());

        //測試PT人工提出
        $parameters = [
            'vendor' => 'PT',
            'amount' => -100,
            'memo' => 'testMemo',
            'ref_id' => 123456789,
            'operator' => 'tester',
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cash);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1089, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(3, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(1090, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(4, $ret['ret']['entry'][1]['cash_version']);

        //測試PT人工存入
        $parameters = [
            'vendor' => 'PT',
            'amount' => 100,
            'memo' => 'testMemo2',
            'ref_id' => 123456789,
            'operator' => 'tester',
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $cash = $user->getCash();
        $em->refresh($cash);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1087, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(5, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(1088, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(6, $ret['ret']['entry'][1]['cash_version']);
    }

    /**
     * 測試轉帳至歐博視訊
     */
    public function testTransferToAB()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 8);
        $cash = $em->find('BBDurianBundle:Cash', 7);

        $this->assertEquals(1000, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());

        //測試AB人工提出
        $parameters = [
            'vendor' => 'AB',
            'amount' => -100,
            'memo' => 'testMemo',
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cash);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1106, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(3, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(1107, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(4, $ret['ret']['entry'][1]['cash_version']);

        //測試AB人工存入
        $parameters = [
            'vendor' => 'AB',
            'amount' => 100,
            'memo' => 'testMemo2',
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $cash = $user->getCash();
        $em->refresh($cash);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1104, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(5, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(1105, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(6, $ret['ret']['entry'][1]['cash_version']);
    }

    /**
     * 測試轉帳至MG電子
     */
    public function testTransferToMG()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 8);
        $cash = $em->find('BBDurianBundle:Cash', 7);

        $this->assertEquals(1000, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());

        //測試MG人工提出
        $parameters = [
            'vendor' => 'MG',
            'amount' => -100,
            'memo' => 'testMemo',
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cash);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1114, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(3, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(1115, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(4, $ret['ret']['entry'][1]['cash_version']);

        //測試MG人工存入
        $parameters = [
            'vendor' => 'MG',
            'amount' => 100,
            'memo' => 'testMemo2',
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $cash = $user->getCash();
        $em->refresh($cash);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1112, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(5, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(1113, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(6, $ret['ret']['entry'][1]['cash_version']);
    }

    /**
     * 測試轉帳至東方視訊
     */
    public function testTransferToOG()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 8);
        $cash = $em->find('BBDurianBundle:Cash', 7);

        $this->assertEquals(1000, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());

        //測試OG人工提出
        $parameters = [
            'vendor' => 'OG',
            'amount' => -100,
            'memo' => 'testMemo',
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cash);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1122, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(3, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(1123, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(4, $ret['ret']['entry'][1]['cash_version']);

        //測試OG人工存入
        $parameters = [
            'vendor' => 'OG',
            'amount' => 100,
            'memo' => 'testMemo2',
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $cash = $user->getCash();
        $em->refresh($cash);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1120, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(5, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(1121, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(6, $ret['ret']['entry'][1]['cash_version']);
    }

    /**
     * 測試轉帳至GD視訊
     */
    public function testTransferToGD()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 8);
        $cash = $em->find('BBDurianBundle:Cash', 7);

        $this->assertEquals(1000, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());

        //測試GD人工提出
        $parameters = [
            'vendor' => 'GD',
            'amount' => -100,
            'memo' => 'testMemo',
            'ref_id' => 123456789
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cash);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1141, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(3, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(1142, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(4, $ret['ret']['entry'][1]['cash_version']);

        //測試GD人工存入
        $parameters = [
            'vendor' => 'GD',
            'amount' => 100,
            'memo' => 'testMemo2',
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $cash = $user->getCash();
        $em->refresh($cash);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1148, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(5, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(1140, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(6, $ret['ret']['entry'][1]['cash_version']);
    }

    /**
     * 測試轉帳至Gns機率
     */
    public function testTransferToGns()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 8);
        $cash = $em->find('BBDurianBundle:Cash', 7);

        $this->assertEquals(1000, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());

        //測試Gns人工提出
        $parameters = [
            'vendor' => 'Gns',
            'amount' => -100,
            'memo' => 'testMemo',
            'ref_id' => 123456789
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cash);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1163, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(3, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(1164, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(4, $ret['ret']['entry'][1]['cash_version']);

        //測試Gns人工存入
        $parameters = [
            'vendor' => 'Gns',
            'amount' => 100,
            'memo' => 'testMemo2',
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $cash = $user->getCash();
        $em->refresh($cash);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1161, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(5, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(1162, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(6, $ret['ret']['entry'][1]['cash_version']);
    }

    /**
     * 測試轉帳至ISB電子
     */
    public function testTransferToISB()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 8);
        $cash = $em->find('BBDurianBundle:Cash', 7);

        $this->assertEquals(1000, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());

        //測試ISB人工提出
        $parameters = [
            'vendor' => 'ISB',
            'amount' => -100,
            'memo' => 'testMemo',
            'ref_id' => 123456789
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cash);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1183, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1184, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);

        //測試ISB人工存入
        $parameters = [
            'vendor' => 'ISB',
            'amount' => 100,
            'memo' => 'testMemo2',
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $cash = $user->getCash();
        $em->refresh($cash);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1181, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1182, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
    }

    /**
     * 測試轉帳至888捕魚
     */
    public function testTransferTo888()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 8);
        $cash = $em->find('BBDurianBundle:Cash', 7);

        $this->assertEquals(1000, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());

        //測試888人工提出
        $parameters = [
            'vendor' => '888',
            'amount' => -100,
            'memo' => 'testMemo',
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cash);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1218, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(3, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(1219, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(4, $ret['ret']['entry'][1]['cash_version']);

        //測試888人工存入
        $parameters = [
            'vendor' => '888',
            'amount' => 100,
            'memo' => 'testMemo2',
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $cash = $user->getCash();
        $em->refresh($cash);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1216, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(5, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(1217, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(6, $ret['ret']['entry'][1]['cash_version']);
    }


    /**
     * 測試轉帳至HB電子
     */
    public function testTransferToHB()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 8);
        $cash = $em->find('BBDurianBundle:Cash', 7);

        $this->assertEquals(1000, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());

        //測試HB人工提出
        $parameters = [
            'vendor' => 'HB',
            'amount' => -100,
            'memo' => 'testMemo',
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cash);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1256, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(3, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(1257, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(4, $ret['ret']['entry'][1]['cash_version']);

        //測試HB人工存入
        $parameters = [
            'vendor' => 'HB',
            'amount' => 100,
            'memo' => 'testMemo2',
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $cash = $user->getCash();
        $em->refresh($cash);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1254, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(5, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(1255, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(6, $ret['ret']['entry'][1]['cash_version']);
    }

    /**
     * 測試轉帳至BG視訊
     */
    public function testTransferToBG()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user   = $em->find('BBDurianBundle:User', 8);
        $cash   = $em->find('BBDurianBundle:Cash', 7);

        $this->assertEquals(1000, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());

        //測試BG人工提出
        $parameters = [
            'vendor'   => 'BG',
            'amount'   => -100,
            'memo'     => 'testMemo',
            'ref_id'   => 123456789,
            'operator' => 'tester',
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cash);

        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1270, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1271, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(3, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(4, $ret['ret']['entry'][1]['cash_version']);

        //測試BG人工存入
        $parameters = [
            'vendor'   => 'BG',
            'amount'   => 100,
            'memo'     => 'testMemo2',
            'ref_id'   => 123456789,
            'operator' => 'tester',
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $cash = $user->getCash();

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cash);

        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1268, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1269, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(5, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(6, $ret['ret']['entry'][1]['cash_version']);
    }

    /**
     * 測試轉帳至PP電子
     */
    public function testTransferToPP()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user   = $em->find('BBDurianBundle:User', 8);
        $cash   = $em->find('BBDurianBundle:Cash', 7);

        $this->assertEquals(1000, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());

        //測試PP人工提出
        $parameters = [
            'vendor'   => 'PP',
            'amount'   => -100,
            'memo'     => 'testMemo',
            'ref_id'   => 123456789,
            'operator' => 'tester',
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cash);

        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1280, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1281, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(3, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(4, $ret['ret']['entry'][1]['cash_version']);

        //測試PP人工存入
        $parameters = [
            'vendor'   => 'PP',
            'amount'   => 100,
            'memo'     => 'testMemo2',
            'ref_id'   => 123456789,
            'operator' => 'tester',
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $cash = $user->getCash();

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cash);

        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1278, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1279, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(5, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(6, $ret['ret']['entry'][1]['cash_version']);
    }

     /**
     * 測試轉帳至JDB電子
     */
    public function testTransferToJDB()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user   = $em->find('BBDurianBundle:User', 8);
        $cash   = $em->find('BBDurianBundle:Cash', 7);

        $this->assertEquals(1000, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());

        //測試JDB人工提出
        $parameters = [
            'vendor'   => 'JDB',
            'amount'   => -100,
            'memo'     => 'testMemo',
            'ref_id'   => 123456789,
            'operator' => 'tester',
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cash);

        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1298, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1299, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(3, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(4, $ret['ret']['entry'][1]['cash_version']);

        //測試JDB人工存入
        $parameters = [
            'vendor'   => 'JDB',
            'amount'   => 100,
            'memo'     => 'testMemo2',
            'ref_id'   => 123456789,
            'operator' => 'tester',
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $cash = $user->getCash();

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cash);

        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1296, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1297, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(5, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(6, $ret['ret']['entry'][1]['cash_version']);
    }

    /**
     * 測試轉帳至AG電子
     */
    public function testTransferToAG()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user   = $em->find('BBDurianBundle:User', 8);
        $cash   = $em->find('BBDurianBundle:Cash', 7);

        $this->assertEquals(1000, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());

        //測試AG人工提出
        $parameters = [
            'vendor'   => 'AG_CASINO',
            'amount'   => -100,
            'memo'     => 'testMemo',
            'ref_id'   => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cash);

        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1306, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1307, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(3, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(4, $ret['ret']['entry'][1]['cash_version']);

        //測試AG人工存入
        $parameters = [
            'vendor'   => 'AG_CASINO',
            'amount'   => 100,
            'memo'     => 'testMemo2',
            'ref_id'   => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cash);

        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1304, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1305, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(5, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(6, $ret['ret']['entry'][1]['cash_version']);
    }

    /**
     * 測試轉帳至MW電子
     */
    public function testTransferToMW()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user   = $em->find('BBDurianBundle:User', 8);
        $cash   = $em->find('BBDurianBundle:Cash', 7);

        $this->assertEquals(1000, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());

        //測試MW人工提出
        $parameters = [
            'vendor'   => 'MW',
            'amount'   => -100,
            'memo'     => 'testMemo',
            'ref_id'   => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cash);

        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1314, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1315, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(3, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(4, $ret['ret']['entry'][1]['cash_version']);

        //測試MW人工存入
        $parameters = [
            'vendor'   => 'MW',
            'amount'   => 100,
            'memo'     => 'testMemo2',
            'ref_id'   => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cash);

        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1312, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1313, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(5, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(6, $ret['ret']['entry'][1]['cash_version']);
    }

    /**
     * 測試轉帳至RT電子
     */
    public function testTransferToRT()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user   = $em->find('BBDurianBundle:User', 8);
        $cash   = $em->find('BBDurianBundle:Cash', 7);

        $this->assertEquals(1000, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());

        //測試RT人工提出
        $parameters = [
            'vendor'   => 'RT',
            'amount'   => -100,
            'memo'     => 'testMemo',
            'ref_id'   => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cash);

        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1358, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1359, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(3, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(4, $ret['ret']['entry'][1]['cash_version']);

        //測試RT人工存入
        $parameters = [
            'vendor'   => 'RT',
            'amount'   => 100,
            'memo'     => 'testMemo2',
            'ref_id'   => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cash);

        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1356, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1357, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(5, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(6, $ret['ret']['entry'][1]['cash_version']);
    }

    /**
     * 測試轉帳至SG電子
     */
    public function testTransferToSG()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user   = $em->find('BBDurianBundle:User', 8);
        $cash   = $em->find('BBDurianBundle:Cash', 7);

        $this->assertEquals(1000, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());

        //測試SG人工提出
        $parameters = [
            'vendor'   => 'SG',
            'amount'   => -100,
            'memo'     => 'testMemo',
            'ref_id'   => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cash);

        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1366, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1367, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(3, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(4, $ret['ret']['entry'][1]['cash_version']);

        //測試SG人工存入
        $parameters = [
            'vendor'   => 'SG',
            'amount'   => 100,
            'memo'     => 'testMemo2',
            'ref_id'   => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cash);

        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1364, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1365, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(5, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(6, $ret['ret']['entry'][1]['cash_version']);
    }

    /**
     * 測試轉帳至VR彩票
     */
    public function testTransferToVR()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user   = $em->find('BBDurianBundle:User', 8);
        $cash   = $em->find('BBDurianBundle:Cash', 7);

        $this->assertEquals(1000, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());

        //測試VR人工提出
        $parameters = [
            'vendor'   => 'VR',
            'amount'   => -100,
            'memo'     => 'testMemo',
            'ref_id'   => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cash);

        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1375, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1376, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(3, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(4, $ret['ret']['entry'][1]['cash_version']);

        //測試VR人工存入
        $parameters = [
            'vendor'   => 'VR',
            'amount'   => 100,
            'memo'     => 'testMemo2',
            'ref_id'   => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cash);

        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1373, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1374, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(5, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(6, $ret['ret']['entry'][1]['cash_version']);
    }

    /**
     * 測試轉帳至PTⅡ電子
     */
    public function testTransferToSW()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user   = $em->find('BBDurianBundle:User', 8);
        $cash   = $em->find('BBDurianBundle:Cash', 7);

        $this->assertEquals(1000, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());

        //測試PTⅡ人工提出
        $parameters = [
            'vendor'   => 'PTⅡ',
            'amount'   => -100,
            'memo'     => 'testMemo',
            'ref_id'   => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cash);

        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1417, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1418, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(3, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(4, $ret['ret']['entry'][1]['cash_version']);

        //測試PTⅡ人工存入
        $parameters = [
            'vendor'   => 'PTⅡ',
            'amount'   => 100,
            'memo'     => 'testMemo2',
            'ref_id'   => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cash);

        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1415, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1416, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(5, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(6, $ret['ret']['entry'][1]['cash_version']);
    }

    /**
     * 測試轉帳至EVO視訊
     */
    public function testTransferToEVO()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user   = $em->find('BBDurianBundle:User', 8);
        $cash   = $em->find('BBDurianBundle:Cash', 7);

        $this->assertEquals(1000, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());

        //測試EVO人工提出
        $parameters = [
            'vendor'   => 'EVO',
            'amount'   => -100,
            'memo'     => 'testMemo',
            'ref_id'   => 123456789,
            'operator' => 'tester',
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cash);

        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1401, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1402, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(3, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(4, $ret['ret']['entry'][1]['cash_version']);

        //測試EVO人工存入
        $parameters = [
            'vendor'   => 'EVO',
            'amount'   => 100,
            'memo'     => 'testMemo2',
            'ref_id'   => 123456789,
            'operator' => 'tester',
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $cash = $user->getCash();

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cash);

        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1399, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1400, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(5, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(6, $ret['ret']['entry'][1]['cash_version']);
    }

    /**
     * 測試轉帳至BNG電子
     */
    public function testTransferToBNG()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user   = $em->find('BBDurianBundle:User', 8);
        $cash   = $em->find('BBDurianBundle:Cash', 7);

        $this->assertEquals(1000, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());

        //測試BNG人工提出
        $parameters = [
            'vendor'   => 'BNG',
            'amount'   => -100,
            'memo'     => 'testMemo',
            'ref_id'   => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cash);

        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1409, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1410, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(3, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(4, $ret['ret']['entry'][1]['cash_version']);

        //測試BNG人工存入
        $parameters = [
            'vendor'   => 'BNG',
            'amount'   => 100,
            'memo'     => 'testMemo2',
            'ref_id'   => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cash);

        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1407, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1408, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(5, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(6, $ret['ret']['entry'][1]['cash_version']);
    }

    /**
     * 測試轉帳至開元 棋牌
     */
    public function testTransferToKY()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user   = $em->find('BBDurianBundle:User', 8);
        $cash   = $em->find('BBDurianBundle:Cash', 7);

        $this->assertEquals(1000, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());

        //測試KY人工提出
        $parameters = [
            'vendor'   => 'KY',
            'amount'   => -100,
            'memo'     => 'testMemo',
            'ref_id'   => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cash);

        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1442, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1443, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(3, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(4, $ret['ret']['entry'][1]['cash_version']);

        //測試KY人工存入
        $parameters = [
            'vendor'   => 'KY',
            'amount'   => 100,
            'memo'     => 'testMemo2',
            'ref_id'   => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cash);

        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1440, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1441, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(5, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(6, $ret['ret']['entry'][1]['cash_version']);
    }

    /**
     * 測試轉帳至WM電子
     */
    public function testTransferToWM()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user   = $em->find('BBDurianBundle:User', 8);
        $cash   = $em->find('BBDurianBundle:Cash', 7);

        $this->assertEquals(1000, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());

        //測試WM人工提出
        $parameters = [
            'vendor'   => 'WM',
            'amount'   => -100,
            'memo'     => 'testMemo',
            'ref_id'   => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cash);

        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1456, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1457, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(3, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(4, $ret['ret']['entry'][1]['cash_version']);

        //測試WM人工存入
        $parameters = [
            'vendor'   => 'WM',
            'amount'   => 100,
            'memo'     => 'testMemo2',
            'ref_id'   => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cash);

        $this->assertEquals($cash->getId(), $ret['ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1454, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1455, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(5, $ret['ret']['entry'][0]['cash_version']);
        $this->assertEquals(6, $ret['ret']['entry'][1]['cash_version']);
    }

    /**
     * 測試轉帳時ref_id帶空字串會送0到queue並回傳空字串
     */
    public function testTransferWithEmptyRefId()
    {
        $client = $this->createClient();
        $redis = $this->getContainer()->get('snc_redis.default');

        $parameters = [
            'vendor'   => 'SABAH',
            'amount'   => -100,
            'memo'     => 'testMemo',
            'ref_id'   => '',
            'operator' => 'tester',
        ];

        $client->request('PUT', '/api/user/8/transfer_out', $parameters);

        $queue = json_decode($redis->rpop('cash_queue'), true);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertSame(0, $queue['ref_id']);
        $this->assertEquals('', $ret['ret']['entry'][0]['ref_id']);
    }

    /**
     * 測試轉帳,輸入錯誤的user,找不到cash資料
     */
    public function testTransferWithNoCashUser()
    {
        $client = $this->createClient();

        $parameters = [
            'vendor' => 'SABAH',
            'amount' => 100
        ];

        $client->request('PUT', '/api/user/10/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150040002, $output['code']);
        $this->assertEquals('No cash found', $output['msg']);
    }

    /**
     * 測試抓現金交易記錄
     */
    public function testGetEntries()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = [
            'first_result' => 0,
            'max_results'  => 20,
            'sub_ret'      => 1,
            'sub_total'    => 1,
            'sort'         => 'id',
            'order'        => 'asc',
            'fields'       => ['operator']
        ];

        $client->request('GET', '/api/user/2/cash/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals(1, $ret['ret'][0]['id']);
        $this->assertEquals(1, $ret['ret'][0]['cash_id']);
        $this->assertEquals(2, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(1001, $ret['ret'][0]['opcode']); // 1001 DEPOSIT
        $this->assertEquals('1000', $ret['ret'][0]['amount']);
        $this->assertEquals('2000', $ret['ret'][0]['balance']);
        $this->assertEquals('', $ret['ret'][0]['memo']);
        $this->assertEquals(238030097, $ret['ret'][0]['ref_id']);
        $this->assertEquals(1, $ret['ret'][0]['operator']['entry_id']);
        $this->assertEquals('company', $ret['ret'][0]['operator']['username']);

        $this->assertEquals(9, $ret['ret'][1]['id']);
        $this->assertEquals(1, $ret['ret'][1]['cash_id']);
        $this->assertEquals(2, $ret['ret'][1]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][1]['currency']);
        $this->assertEquals(1001, $ret['ret'][1]['opcode']); // 1001 DEPOSIT
        $this->assertEquals('100', $ret['ret'][1]['amount']);
        $this->assertEquals('1100', $ret['ret'][1]['balance']);
        $this->assertEquals('', $ret['ret'][1]['memo']);
        $this->assertEquals(11509530, $ret['ret'][1]['ref_id']);
        $this->assertEquals(9, $ret['ret'][1]['operator']['entry_id']);
        $this->assertEquals('company', $ret['ret'][1]['operator']['username']);

        $this->assertEquals(10, $ret['ret'][2]['id']);
        $this->assertEquals(1, $ret['ret'][2]['cash_id']);
        $this->assertEquals(2, $ret['ret'][2]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][2]['currency']);
        $this->assertEquals(1002, $ret['ret'][2]['opcode']); // 1002 WITHDRAWAL
        $this->assertEquals('-80', $ret['ret'][2]['amount']);
        $this->assertEquals('920', $ret['ret'][2]['balance']);
        $this->assertEquals('123', $ret['ret'][2]['memo']);
        $this->assertEquals(5150840319, $ret['ret'][2]['ref_id']);
        $this->assertEquals(10, $ret['ret'][2]['operator']['entry_id']);
        $this->assertEquals('company', $ret['ret'][2]['operator']['username']);

        $this->assertEquals(3, count($ret['ret']));
        $this->assertEquals(2, count($ret['sub_ret']));

        $user = $em->find('BB\DurianBundle\Entity\User', 2);
        $cash = $user->getCash();

        $this->assertEquals($user->getUsername(), $ret['sub_ret']['user']['username']);
        $this->assertEquals($user->getAlias(), $ret['sub_ret']['user']['alias']);
        $this->assertEquals($cash->getId(), $ret['sub_ret']['cash']['id']);
        $this->assertEquals($cash->getBalance(), $ret['sub_ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['sub_ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['sub_ret']['cash']['pre_add']);
        $this->assertEquals($cash->getUser()->getId(), $ret['sub_ret']['cash']['user_id']);
        $this->assertEquals('TWD', $ret['sub_ret']['cash']['currency']);

        //驗證sub_total
        $deposite = 0;
        $withdraw = 0;
        foreach ($ret['ret'] as $entry) {
            if ($entry['amount'] > 0) {
                $deposite += $entry['amount'];
            }
            if ($entry['amount'] < 0) {
                $withdraw += $entry['amount'];
            }
        }
        $this->assertEquals($ret['sub_total']['deposite'], $deposite);
        $this->assertEquals($ret['sub_total']['withdraw'], $withdraw);
        $this->assertEquals($ret['sub_total']['total'], $withdraw+$deposite);

        //驗證分頁
        $this->assertEquals(3, $ret['pagination']['total']);
        $this->assertEquals(0, $ret['pagination']['first_result']);
        $this->assertEquals(20, $ret['pagination']['max_results']);
    }

    /**
     * 測試回傳條件限制交易紀錄
     */
    public function testGetEntriesByLimit()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emHis = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $idGenerator = $this->getContainer()->get('durian.cash_entry_id_generator');
        $client = $this->createClient();

        $cash = $em->find('BB\DurianBundle\Entity\Cash', 1);

        //加入五筆交易紀錄到歷史資料庫
        for ($i = 0; $i < 5; $i++) {
            $entry = new CashEntry($cash, 1001, 100, '');
            $entry->setId($idGenerator->generate());
            $entry->setRefId(0);
            $cash->setBalance($entry->getBalance());
            $emHis->persist($entry);
        }

        $emHis->flush();
        $emHis->clear();

        $parameters = array(
            'first_result' => 2,
            'max_results'  => 4,
            'sort'         => 'id',
            'order'        => 'asc'
        );

        $client->request('GET', '/api/user/2/cash/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //測試交易紀錄是否只回傳四筆
        $this->assertEquals(4, count($ret['ret']));

        $this->assertEquals(10, $ret['ret'][0]['id']);
        $this->assertEquals(1, $ret['ret'][0]['cash_id']);
        $this->assertEquals(2, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(1002, $ret['ret'][0]['opcode']); // 1002 WITHDRAWAL
        $this->assertEquals('-80', $ret['ret'][0]['amount']);
        $this->assertEquals('920', $ret['ret'][0]['balance']);
        $this->assertEquals('123', $ret['ret'][0]['memo']);
        $this->assertEquals(5150840319, $ret['ret'][0]['ref_id']);

        $this->assertEquals(1001, $ret['ret'][1]['id']);
        $this->assertEquals(1, $ret['ret'][1]['cash_id']);
        $this->assertEquals(2, $ret['ret'][1]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][1]['currency']);
        $this->assertEquals(1001, $ret['ret'][1]['opcode']); // 1001 DEPOSIT
        $this->assertEquals('100', $ret['ret'][1]['amount']);
        $this->assertEquals('1100', $ret['ret'][1]['balance']);

        $this->assertEquals(1002, $ret['ret'][2]['id']);
        $this->assertEquals(1, $ret['ret'][2]['cash_id']);
        $this->assertEquals(2, $ret['ret'][2]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][2]['currency']);
        $this->assertEquals(1001, $ret['ret'][2]['opcode']);
        $this->assertEquals('100', $ret['ret'][2]['amount']);
        $this->assertEquals('1200', $ret['ret'][2]['balance']);

        $this->assertEquals(1003, $ret['ret'][3]['id']);
        $this->assertEquals(1, $ret['ret'][3]['cash_id']);
        $this->assertEquals(2, $ret['ret'][3]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][3]['currency']);
        $this->assertEquals(1001, $ret['ret'][3]['opcode']);
        $this->assertEquals('100', $ret['ret'][3]['amount']);
        $this->assertEquals('1300', $ret['ret'][3]['balance']);

        $this->assertEquals(8, $ret['pagination']['total']);
        $this->assertEquals(2, $ret['pagination']['first_result']);
        $this->assertEquals(4, $ret['pagination']['max_results']);

        $parameters = array('opcode' => 1002); // 1002 WITHDRAWAL

        $client->request('GET', '/api/user/2/cash/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals(10, $ret['ret'][0]['id']);
        $this->assertEquals(1, $ret['ret'][0]['cash_id']);
        $this->assertEquals(2, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(1002, $ret['ret'][0]['opcode']);
        $this->assertEquals('920', $ret['ret'][0]['balance']);
        $this->assertEquals(-80, $ret['ret'][0]['amount']);

        $this->assertEquals(1, $ret['pagination']['total']);
        $this->assertNull($ret['pagination']['first_result']);
        $this->assertNull($ret['pagination']['max_results']);
    }

    /**
     * 測試從歷史資料庫回傳指定的ref_id交易紀錄
     */
    public function testGetHisEntriesWithRefId()
    {
        $client = $this->createClient();

        $parameters = ['ref_id' => 11509530];

        $client->request('GET', '/api/user/2/cash/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(9, $ret['ret'][0]['id']);
        $this->assertEquals(1, $ret['ret'][0]['cash_id']);
        $this->assertEquals(2, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(1001, $ret['ret'][0]['opcode']);
        $this->assertEquals(100, $ret['ret'][0]['amount']);
        $this->assertEquals(1100, $ret['ret'][0]['balance']);
        $this->assertEquals(11509530, $ret['ret'][0]['ref_id']);
    }

    /**
     * 測試從現今資料庫回傳交易紀錄，時間區間須在45天內
     */
    public function testGetEntriesBySortingAndPagination()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $cashHelper = $this->getContainer()->get('durian.cash_helper');
        $client = $this->createClient();

        // 新增三筆資料進目前資料庫
        $cash = $em->find('BBDurianBundle:Cash', 1);
        $cashHelper->addCashEntry($cash, 1001, 11, '', 9529);
        $cashHelper->addCashEntry($cash, 1001, 22, '', 9528);
        $cashHelper->addCashEntry($cash, 1001, 33, '', 9527);

        $em->flush();
        $em->clear();
        $emEntry->flush();

        $start = new \DateTime('now');
        $start = $start->sub(new \DateInterval('PT1H'));
        $end = new \DateTime('now');

        // 測試交易紀錄照時間排序
        $parameters = [
            'start' => $start->format('YmdHis'),
            'end' => $end->format('YmdHis'),
            'order' => 'desc',
            'sort' => 'created_at'
        ];

        $client->request('GET', '/api/user/2/cash/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(1003, $ret['ret'][0]['id']);
        $this->assertEquals(33, $ret['ret'][0]['amount']);
        $this->assertEquals(1066, $ret['ret'][0]['balance']);
        $this->assertEquals(9527, $ret['ret'][0]['ref_id']);

        $this->assertEquals(1002, $ret['ret'][1]['id']);
        $this->assertEquals(22, $ret['ret'][1]['amount']);
        $this->assertEquals(1033, $ret['ret'][1]['balance']);
        $this->assertEquals(9528, $ret['ret'][1]['ref_id']);

        $this->assertEquals(1001, $ret['ret'][2]['id']);
        $this->assertEquals(11, $ret['ret'][2]['amount']);
        $this->assertEquals(1011, $ret['ret'][2]['balance']);
        $this->assertEquals(9529, $ret['ret'][2]['ref_id']);

        // 測試照ref_id排序及限制筆數
        $parameters = [
            'first_result' => 1,
            'max_results' => 2,
            'start' => $start->format('YmdHis'),
            'end' => $end->format('YmdHis'),
            'order' => 'desc',
            'sort' => 'ref_id'
        ];

        $client->request('GET', '/api/user/2/cash/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(1, $ret['pagination']['first_result']);
        $this->assertEquals(2, $ret['pagination']['max_results']);
        $this->assertEquals(3, $ret['pagination']['total']);

        $this->assertEquals(1002, $ret['ret'][0]['id']);
        $this->assertEquals(22, $ret['ret'][0]['amount']);
        $this->assertEquals(1033, $ret['ret'][0]['balance']);
        $this->assertEquals(9528, $ret['ret'][0]['ref_id']);

        $this->assertEquals(1003, $ret['ret'][1]['id']);
        $this->assertEquals(33, $ret['ret'][1]['amount']);
        $this->assertEquals(1066, $ret['ret'][1]['balance']);
        $this->assertEquals(9527, $ret['ret'][1]['ref_id']);
        $this->assertFalse(isset($ret['ret'][2]));
    }

    /**
     * 測試回傳時間限制交易紀錄
     */
    public function testGetEntriesByTime()
    {
        $client = $this->createClient();

        $parameters = array(
            'start' => '2012-01-01T11:00:00+0800',
            'end'   => '2012-01-01T13:00:00+0800'
        );

        $client->request('GET', '/api/user/2/cash/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals(9, $ret['ret'][0]['id']);
        $this->assertEquals(1, $ret['ret'][0]['cash_id']);
        $this->assertEquals(2, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(1001, $ret['ret'][0]['opcode']); // 1001 DEPOSIT
        $this->assertEquals(100, $ret['ret'][0]['amount']);
        $this->assertEquals(1100, $ret['ret'][0]['balance']);
        $this->assertEquals('2012-01-01T12:00:00+0800', $ret['ret'][0]['created_at']);

        $this->assertEquals(10, $ret['ret'][1]['id']);
        $this->assertEquals(1, $ret['ret'][1]['cash_id']);
        $this->assertEquals(2, $ret['ret'][1]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][1]['currency']);
        $this->assertEquals(1002, $ret['ret'][1]['opcode']);// 1002 WITHDRAWAL
        $this->assertEquals(-80, $ret['ret'][1]['amount']);
        $this->assertEquals(920, $ret['ret'][1]['balance']);
        $this->assertEquals('2012-01-01T12:00:00+0800', $ret['ret'][0]['created_at']);

        $this->assertEquals(2, $ret['pagination']['total']);
        $this->assertNull($ret['pagination']['first_result']);
        $this->assertNull($ret['pagination']['max_results']);

        //測試帶入美東時間是否結果相同
        $parameters = array(
            'start' => '2011-12-31T23:00:00-0500',
            'end'   => '2012-01-01T01:00:00-0500'
        );

        $client->request('GET', '/api/user/2/cash/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals(9, $ret['ret'][0]['id']);
        $this->assertEquals(1, $ret['ret'][0]['cash_id']);
        $this->assertEquals(2, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(1001, $ret['ret'][0]['opcode']); // 1001 DEPOSIT
        $this->assertEquals(100, $ret['ret'][0]['amount']);
        $this->assertEquals(1100, $ret['ret'][0]['balance']);
        $this->assertEquals('2012-01-01T12:00:00+0800', $ret['ret'][0]['created_at']);

        $this->assertEquals(10, $ret['ret'][1]['id']);
        $this->assertEquals(1, $ret['ret'][1]['cash_id']);
        $this->assertEquals(2, $ret['ret'][1]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][1]['currency']);
        $this->assertEquals(1002, $ret['ret'][1]['opcode']);// 1002 WITHDRAWAL
        $this->assertEquals(-80, $ret['ret'][1]['amount']);
        $this->assertEquals(920, $ret['ret'][1]['balance']);
        $this->assertEquals('2012-01-01T12:00:00+0800', $ret['ret'][0]['created_at']);

        $this->assertEquals(2, $ret['pagination']['total']);
    }

    /**
     * 測試回傳交易紀錄與操作者, 在沒有交易明細的情況下
     */
    public function testGetEntryAndItsOperatorWhileThereIsNoEntry()
    {
        // 因為有超過一個月資料, 所以會查詢歷史資料庫
        $em = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $client = $this->createClient();

        // 先把交易明細砍掉
        $entries = $em->getRepository('BB\DurianBundle\Entity\CashEntry')
                      ->findBy(array('cashId' => 1));
        foreach ($entries as $entry) {
            $em->remove($entry);
        }

        $em->flush();
        $em->clear();

        $client = $this->createClient();

        $parameters = array('fields' => array('operator'));

        $client->request('GET', '/api/user/2/cash/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(0, $ret['pagination']['total']);
    }

    /**
     * 測試取得現金交易記錄,輸入沒有cash資料的user
     */
    public function testGetEntriesWithNoCahUser()
    {
        $client = $this->createClient();

        $parameters = ['opcode' => 1001];
        $client->request('GET', '/api/user/10/cash/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150040002, $output['code']);
        $this->assertEquals('No cash found', $output['msg']);
    }

    /**
     * 測試取得總計資訊帶入時間區間
     */
    public function testGetTotalAmountWithTimeInterval()
    {
        $client = $this->createClient();

        $parameters = array(
            'start' => '2012-01-01T00:00:00+0800',
            'end'   => '2012-01-01T23:59:59+0800'
        );

        $client->request('GET', '/api/user/2/cash/total_amount', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals(100, $ret['ret']['deposite']);
        $this->assertEquals(-80, $ret['ret']['withdraw']);
        $this->assertEquals(20, $ret['ret']['total']);
    }

    /**
     * 測試取得總計資訊帶入opcode和時間區間
     */
    public function testGetTotalAmountWithOpcodeAndTimeInterval()
    {
        $client = $this->createClient();

        $parameters = array(
            'opcode' => '1001',
            'start'  => '2012-01-01T00:00:00+0800',
            'end'    => '2012-01-01T23:59:59+0800'
        );

        $client->request('GET', '/api/user/2/cash/total_amount', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals(100, $ret['ret']['deposite']);
        $this->assertEquals(0, $ret['ret']['withdraw']);
        $this->assertEquals(100, $ret['ret']['total']);

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $cashHelper = $this->getContainer()->get('durian.cash_helper');

        $cash = $em->find('BBDurianBundle:Cash', 1);
        $cashHelper->addCashEntry($cash, 1001, 100, '', 9527);
        $em->flush();
        $em->clear();
        $emEntry->flush();

        $now = new \DateTime('now');
        $start = $now->sub(new \DateInterval('PT1H'));
        $end = new \DateTime('now');

        // 測試指定區間在45天內
        $parameters = [
            'opcode' => '1001',
            'start'  => $start->format(\DateTime::ISO8601),
            'end'    => $end->format(\DateTime::ISO8601)
        ];

        $client->request('GET', '/api/user/2/cash/total_amount', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(100, $ret['ret']['deposite']);
        $this->assertEquals(0, $ret['ret']['withdraw']);
        $this->assertEquals(100, $ret['ret']['total']);
    }

    /**
     * 測試取得總計資訊,輸入錯誤的user
     */
    public function testGetTotalAmountWithErrorUser()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/10/cash/total_amount');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150040002, $ret['code']);
        $this->assertEquals('No cash found', $ret['msg']);
    }

    /**
     * 測試取得明細餘額加總，若沒有資訊則回傳空陣列
     */
    public function testGetTotalAmountButFeedbackEmptyArray()
    {
        $client = $this->createClient();

        // 指定opcode做查詢
        $parameters = ['opcode' => 1029];

        $client->request('GET', '/api/user/2/cash/total_amount', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals([], $ret['ret']);
    }

    /**
     * 測試取得總計(opcode 9890以下)資訊
     */
    public function testGetTotalTransfer()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/2/cash/transfer_total_amount');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals(1100, $ret['ret']['deposite']);
        $this->assertEquals(-80, $ret['ret']['withdraw']);
        $this->assertEquals(1020, $ret['ret']['total']);
    }

    /**
     * 測試取得總計(opcode 9890以下)資訊,發生找不到cash的狀況
     */
    public function testGetTotalTransferButNoCashFound()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/10/cash/transfer_total_amount');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150040002, $ret['code']);
        $this->assertEquals('No cash found', $ret['msg']);
    }

    /**
     * 測試回傳參考編號交易紀錄
     */
    public function testGetEntriesByRefId()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $cashHelper = $this->getContainer()->get('durian.cash_helper');
        $client = $this->createClient();

        $cash = $em->find('BB\DurianBundle\Entity\Cash', 1);

        // 1001 DEPOSIT
        $cashHelper->addCashEntry($cash, 1001, 100, '', 9527);

        $em->flush();
        $em->clear();
        $emEntry->flush();

        $start = new \DateTime('now');
        $start = $start->sub(new \DateInterval('PT1H'));
        $end = new \DateTime('now');

        // 加上時間範圍, 讓程式不會去搜尋 his 資料庫
        $parameters = array(
            'ref_id' => '9527',
            'start'  => $start->format(\DateTime::ISO8601),
            'end'    => $end->format(\DateTime::ISO8601)
        );

        $client->request('GET', '/api/user/2/cash/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals(1001, $ret['ret'][0]['id']);
        $this->assertEquals(1, $ret['ret'][0]['cash_id']);
        $this->assertEquals(2, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(1001, $ret['ret'][0]['opcode']); // 1001 DEPOSIT
        $this->assertEquals(100, $ret['ret'][0]['amount']);
        $this->assertEquals(1100, $ret['ret'][0]['balance']);
        $this->assertEquals(9527, $ret['ret'][0]['ref_id']);
        $this->assertEquals(1, $ret['pagination']['total']);
        $this->assertNull($ret['pagination']['first_result']);
        $this->assertNull($ret['pagination']['max_results']);
    }

    /**
     * 測試回傳排序交易紀錄
     */
    public function testGetEntriesByOrderBy()
    {
        $client = $this->createClient();

        $parameters = array(
            'order' => 'asc',
            'sort'  => array('opcode', 'id')
        );

        $client->request('GET', '/api/user/2/cash/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals(1, $ret['ret'][0]['id']);
        $this->assertEquals(1, $ret['ret'][0]['cash_id']);
        $this->assertEquals(2, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(1001, $ret['ret'][0]['opcode']); // 1001 DEPOSIT
        $this->assertEquals('1000', $ret['ret'][0]['amount']);
        $this->assertEquals('2000', $ret['ret'][0]['balance']);
        $this->assertEquals(238030097, $ret['ret'][0]['ref_id']);

        $this->assertEquals(9, $ret['ret'][1]['id']);
        $this->assertEquals(1, $ret['ret'][1]['cash_id']);
        $this->assertEquals(2, $ret['ret'][1]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][1]['currency']);
        $this->assertEquals(1001, $ret['ret'][1]['opcode']);
        $this->assertEquals('100', $ret['ret'][1]['amount']);
        $this->assertEquals('1100', $ret['ret'][1]['balance']);
        $this->assertEquals(11509530, $ret['ret'][1]['ref_id']);

        $this->assertEquals(10, $ret['ret'][2]['id']);
        $this->assertEquals(1, $ret['ret'][2]['cash_id']);
        $this->assertEquals(2, $ret['ret'][2]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][2]['currency']);
        $this->assertEquals(1002, $ret['ret'][2]['opcode']); // 1002 WITHDRAWAL
        $this->assertEquals('-80', $ret['ret'][2]['amount']);
        $this->assertEquals('920', $ret['ret'][2]['balance']);
        $this->assertEquals('123', $ret['ret'][2]['memo']);

        $parameters = array(
            'order' => 'asc',
            'sort'  => array('opcode', 'amount')
        );

        $client->request('GET', '/api/user/2/cash/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(1002, $ret['ret'][2]['opcode']);// 1002 WITHDRAWAL
        $this->assertEquals('-80', $ret['ret'][2]['amount']);
        $this->assertEquals(1001, $ret['ret'][1]['opcode']);// 1001 DEPOSIT
        $this->assertEquals('1000', $ret['ret'][1]['amount']);
        $this->assertEquals(1001, $ret['ret'][0]['opcode']);// 1001 DEPOSIT
        $this->assertEquals('100', $ret['ret'][0]['amount']);
    }

    /**
     * 測試回傳下層轉帳交易紀錄(opcode 9890以下)
     */
    public function testGetTransferEntriesList()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 加入幾筆交易紀錄
        $parameters = [
            'opcode'   => 1036,
            'amount'   => 1111,
            'tag'      => 1234,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $parameters = [
            'opcode'   => 1039,
            'amount'   => 2222,
            'tag'      => 5678,
            'operator' => 'visiter'
        ];

        $client->request('PUT', '/api/user/8/cash/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->runCommand('durian:run-cash-poper');

        $start = new \DateTime('now');
        $start = $start->sub(new \DateInterval('PT1H'));
        $end = new \DateTime('now');

        $parameters = [
            'parent_id'    => 2,
            'depth'        => 5,
            'currency'     => 'TWD',
            'start'        => $start->format('YmdHis'),
            'end'          => $end->format('YmdHis'),
            'opcode'       => [1036, 1039],
            'sort'         => 'tag',
            'order'        => 'asc',
            'first_result' => 0,
            'max_results'  => 20,
            'fields'       => ['operator'],
            'sub_total'    => 1,
            'sub_ret'      => 1
        ];

        $client->request('GET', '/api/cash/transfer_entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $cash0 = $em->find('BB\DurianBundle\Entity\Cash', $ret['sub_ret']['cash'][0]['id']);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(1002, $ret['ret'][0]['id']);
        $this->assertEquals(5678, $ret['ret'][0]['merchant_id']);
        $this->assertEquals('', $ret['ret'][0]['remit_account_id']);
        $this->assertEquals(1039, $ret['ret'][0]['opcode']);
        $this->assertEquals(2222, $ret['ret'][0]['amount']);
        $this->assertEquals(4333, $ret['ret'][0]['balance']);
        $this->assertEquals(5678, $ret['ret'][0]['tag']);
        $this->assertEquals(1002, $ret['ret'][0]['operator']['entry_id']);
        $this->assertEquals('visiter', $ret['ret'][0]['operator']['username']);
        $this->assertEquals(1001, $ret['ret'][1]['id']);
        $this->assertEquals('', $ret['ret'][1]['merchant_id']);
        $this->assertEquals(1234, $ret['ret'][1]['remit_account_id']);
        $this->assertEquals(1036, $ret['ret'][1]['opcode']);
        $this->assertEquals(1111, $ret['ret'][1]['amount']);
        $this->assertEquals(2111, $ret['ret'][1]['balance']);
        $this->assertEquals(1234, $ret['ret'][1]['tag']);
        $this->assertEquals(1001, $ret['ret'][1]['operator']['entry_id']);
        $this->assertEquals('tester', $ret['ret'][1]['operator']['username']);
        $this->assertEquals(8, $ret['sub_ret']['user'][0]['id']);
        $this->assertEquals('tester', $ret['sub_ret']['user'][0]['username']);
        $this->assertEquals(8, $ret['sub_ret']['cash'][0]['user_id']);
        $this->assertEquals($cash0->getBalance() - $cash0->getPreSub(), $ret['sub_ret']['cash'][0]['balance']);

        //驗證sub_total
        $deposite = 0;
        $withdraw = 0;
        foreach ($ret['ret'] as $entry) {
            if ($entry['amount'] > 0) {
                $deposite += $entry['amount'];
            }
            if ($entry['amount'] < 0) {
                $withdraw += $entry['amount'];
            }
        }
        $this->assertEquals($ret['sub_total']['deposite'], $deposite);
        $this->assertEquals($ret['sub_total']['withdraw'], $withdraw);
        $this->assertEquals($ret['sub_total']['total'], $withdraw+$deposite);

        //驗證分頁
        $this->assertEquals(2, $ret['pagination']['total']);
        $this->assertEquals(0, $ret['pagination']['first_result']);
        $this->assertEquals(20, $ret['pagination']['max_results']);

        $parameters = [
            'parent_id'    => 2,
            'depth'        => 5,
            'currency'     => 'TWD',
            'tag'          => 1234,
            'start'        => $start->format('YmdHis'),
            'end'          => $end->format('YmdHis'),
            'opcode'       => [1036, 1039],
            'sort'         => 'tag',
            'order'        => 'asc',
            'first_result' => 0,
            'max_results'  => 20
        ];

        $client->request('GET', '/api/cash/transfer_entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(0, $ret['pagination']['total']);
        $this->assertEquals(0, $ret['pagination']['first_result']);
        $this->assertEquals(20, $ret['pagination']['max_results']);
    }

    /**
     * 測試回傳轉帳交易紀錄(opcode 9890以下)
     */
    public function testGetTransferEntries()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 加入幾筆交易紀錄
        $parameters = [
            'opcode'   => 1036,
            'amount'   => 1111,
            'tag'      => 1234,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $parameters = [
            'opcode'   => 1039,
            'amount'   => 2222,
            'tag'      => 5678,
            'operator' => 'visiter'
        ];

        $client->request('PUT', '/api/user/8/cash/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->runCommand('durian:run-cash-poper');

        $start = new \DateTime('now');
        $start = $start->sub(new \DateInterval('PT1H'));
        $end = new \DateTime('now');

        $parameters = [
            'currency'     => 'TWD',
            'start'        => $start->format('YmdHis'),
            'end'          => $end->format('YmdHis'),
            'opcode'       => [1036, 1039],
            'sort'         => 'tag',
            'order'        => 'asc',
            'first_result' => 0,
            'max_results'  => 20,
            'fields'       => ['operator'],
            'sub_total'    => 1,
            'sub_ret'      => 1
        ];

        $client->request('GET', '/api/user/8/cash/transfer_entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $cash0 = $em->find('BBDurianBundle:Cash', $ret['sub_ret']['cash']['id']);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(1002, $ret['ret'][0]['id']);
        $this->assertEquals(5678, $ret['ret'][0]['merchant_id']);
        $this->assertEquals('', $ret['ret'][0]['remit_account_id']);
        $this->assertEquals(1039, $ret['ret'][0]['opcode']);
        $this->assertEquals(2222, $ret['ret'][0]['amount']);
        $this->assertEquals(4333, $ret['ret'][0]['balance']);
        $this->assertEquals(5678, $ret['ret'][0]['tag']);
        $this->assertEquals(1002, $ret['ret'][0]['operator']['entry_id']);
        $this->assertEquals('visiter', $ret['ret'][0]['operator']['username']);
        $this->assertEquals(1001, $ret['ret'][1]['id']);
        $this->assertEquals('', $ret['ret'][1]['merchant_id']);
        $this->assertEquals(1234, $ret['ret'][1]['remit_account_id']);
        $this->assertEquals(1036, $ret['ret'][1]['opcode']);
        $this->assertEquals(1111, $ret['ret'][1]['amount']);
        $this->assertEquals(2111, $ret['ret'][1]['balance']);
        $this->assertEquals(1234, $ret['ret'][1]['tag']);
        $this->assertEquals(1001, $ret['ret'][1]['operator']['entry_id']);
        $this->assertEquals('tester', $ret['ret'][1]['operator']['username']);
        $this->assertEquals(8, $ret['sub_ret']['user']['id']);
        $this->assertEquals('tester', $ret['sub_ret']['user']['username']);
        $this->assertEquals(8, $ret['sub_ret']['cash']['user_id']);
        $this->assertEquals($cash0->getBalance() - $cash0->getPreSub(), $ret['sub_ret']['cash']['balance']);

        //驗證sub_total
        $deposite = 0;
        $withdraw = 0;
        foreach ($ret['ret'] as $entry) {
            if ($entry['amount'] > 0) {
                $deposite += $entry['amount'];
            }
            if ($entry['amount'] < 0) {
                $withdraw += $entry['amount'];
            }
        }
        $this->assertEquals($ret['sub_total']['deposite'], $deposite);
        $this->assertEquals($ret['sub_total']['withdraw'], $withdraw);
        $this->assertEquals($ret['sub_total']['total'], $withdraw+$deposite);

        //驗證分頁
        $this->assertEquals(2, $ret['pagination']['total']);
        $this->assertEquals(0, $ret['pagination']['first_result']);
        $this->assertEquals(20, $ret['pagination']['max_results']);

        $parameters = [
            'currency'     => 'TWD',
            'tag'          => 1234,
            'start'        => $start->format('YmdHis'),
            'end'          => $end->format('YmdHis'),
            'opcode'       => [1036, 1039],
            'sort'         => 'tag',
            'order'        => 'asc',
            'first_result' => 0,
            'max_results'  => 20
        ];

        $client->request('GET', '/api/user/8/cash/transfer_entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(0, $ret['pagination']['total']);
        $this->assertEquals(0, $ret['pagination']['first_result']);
        $this->assertEquals(20, $ret['pagination']['max_results']);
    }

    /**
     * 測試以幣別查尋轉帳交易紀錄(opcode 9890以下)，沒有cash資料
     */
    public function testGetTransferEntryWithCurrencyButNoCashFound()
    {
        $client = $this->createClient();

        // 測試帶入的user找不到cash資料
        $client->request('GET', '/api/user/10/cash/transfer_entry');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150040002, $ret['code']);
        $this->assertEquals('No cash found', $ret['msg']);
    }

    /**
     * 測試回傳下層轉帳明細總額
     */
    public function testGetTransferTotalBelow()
    {
        $client = $this->createClient();

        // 加入幾筆交易紀錄
        $parameters = [
            'opcode' => 1036,
            'amount' => 1111,
            'tag'    => 1234
        ];

        $client->request('PUT', '/api/user/8/cash/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $parameters = [
            'opcode' => 1039,
            'amount' => 2222,
            'tag'    => 5678
        ];

        $client->request('PUT', '/api/user/8/cash/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->runCommand('durian:run-cash-poper');

        $start = new \DateTime('now');
        $start = $start->sub(new \DateInterval('PT1H'));
        $end = new \DateTime('now');

        $parameters = [
            'parent_id' => 2,
            'depth'     => 5,
            'currency'  => 'TWD',
            'start'     => $start->format('YmdHis'),
            'end'       => $end->format('YmdHis'),
            'opcode'    => 1036,
            'group_by'  => ['tag']
        ];

        $client->request('GET', '/api/cash/transfer_total_below', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(1111, $ret['ret'][0]['total_amount']);
        $this->assertEquals(1, $ret['ret'][0]['total_user']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(1234, $ret['ret'][0]['tag']);
        $this->assertEquals(1234, $ret['ret'][0]['remit_account_id']);
        $this->assertEquals(1111, $ret['sub_total']['total_amount']);
        $this->assertEquals(1, $ret['sub_total']['total']);

        $parameters = [
            'parent_id' => 2,
            'depth'     => 5,
            'currency'  => 'TWD',
            'start'     => $start->format('YmdHis'),
            'end'       => $end->format('YmdHis'),
            'opcode'    => 1039,
            'group_by'  => ['merchant_id']
        ];

        $client->request('GET', '/api/cash/transfer_total_below', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2222, $ret['ret'][0]['total_amount']);
        $this->assertEquals(1, $ret['ret'][0]['total_user']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(5678, $ret['ret'][0]['tag']);
        $this->assertEquals(5678, $ret['ret'][0]['merchant_id']);
        $this->assertEquals(2222, $ret['sub_total']['total_amount']);
        $this->assertEquals(1, $ret['sub_total']['total']);

        $parameters = [
            'parent_id' => 2,
            'depth'     => 5,
            'currency'  => 'TWD',
            'start'     => $start->format('YmdHis'),
            'end'       => $end->format('YmdHis'),
            'opcode'    => [1036, 1039]
        ];

        $client->request('GET', '/api/cash/transfer_total_below', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(3333, $ret['ret'][0]['total_amount']);
        $this->assertEquals(1, $ret['ret'][0]['total_user']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(3333, $ret['sub_total']['total_amount']);
        $this->assertEquals(1, $ret['sub_total']['total']);
    }

    /**
     * 測試回傳餘額為負數現金的資料
     */
    public function testGetNegativeBalance()
    {
        $client = $this->createClient();

        // 設定cash2餘額為負數
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $cash2 = $em->find('BBDurianBundle:Cash', 1);
        $cash2->setBalance(-100.5);
        $cash2->setNegative(true);

        $cash2 = $em->find('BBDurianBundle:Cash', 2);
        $cash2->setBalance(-300.5);
        $cash2->setNegative(true);
        $em->flush();

        $client->request('GET', '/api/cash/negative_balance', ['sub_ret' => 1]);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(1, $ret['ret'][0]['id']);
        $this->assertEquals(2, $ret['ret'][0]['user_id']);
        $this->assertEquals(-100.5, $ret['ret'][0]['balance']);
        $this->assertEquals('0.0000', $ret['ret'][0]['pre_sub']);
        $this->assertEquals('0.0000', $ret['ret'][0]['pre_add']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(2, $ret['sub_ret']['user'][0]['id']);
        $this->assertEquals(20120101120000, $ret['ret'][0]['last_entry_at']);

        $this->assertEquals(2, $ret['ret'][1]['id']);
        $this->assertEquals(3, $ret['ret'][1]['user_id']);
        $this->assertEquals(-300.5, $ret['ret'][1]['balance']);
        $this->assertEquals('0.0000', $ret['ret'][1]['pre_sub']);
        $this->assertEquals('0.0000', $ret['ret'][1]['pre_add']);
        $this->assertEquals('TWD', $ret['ret'][1]['currency']);
        $this->assertEquals(3, $ret['sub_ret']['user'][1]['id']);
        $this->assertEquals(20120101120000, $ret['ret'][1]['last_entry_at']);

        $this->assertEquals(2, $ret['pagination']['total']);
    }

    /**
     * 測試更新會員總餘額
     */
    public function testUpdateTotalBalance()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 改為停用
        $user = $em->find('BBDurianBundle:User', 8);
        $user->disable();
        $em->flush();

        $parameters = array(
            'parent_id' => 2,
            'force'     => 1
        );

        $client->request('PUT', '/api/cash/total_balance', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret'][0]['parent_id']);
        $this->assertEquals(0, $ret['ret'][0]['enable_balance']);
        $this->assertEquals(1000, $ret['ret'][0]['disable_balance']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);

        //測試新增其他幣別大股東是否會新增一筆紀錄CashTotalBalance

        $parent = $em->find('BB\DurianBundle\Entity\User', 2);
        //新增CNY幣別大股東
        $user = new \BB\DurianBundle\Entity\User();
        $user->setId(12);
        $user->setParent($parent);
        $user->setUserName('cnyuser');
        $user->setPassword('cnyuser');
        $user->setAlias('cnyuser');
        $user->setDomain(2);

        $cash = new \BB\DurianBundle\Entity\Cash($user, 156); // CNY

        $em->persist($user);
        $em->persist($cash);

        $em->flush();

        $parameters = array(
            'parent_id' => 2,
            'force'     => 1
        );

        $client->request('PUT', '/api/cash/total_balance', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret'][0]['parent_id']);
        $this->assertEquals(0, $ret['ret'][0]['enable_balance']);
        $this->assertEquals(1000, $ret['ret'][0]['disable_balance']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(2, $ret['ret'][1]['parent_id']);
        $this->assertEquals(0, $ret['ret'][1]['enable_balance']);
        $this->assertEquals(0, $ret['ret'][1]['disable_balance']);
        $this->assertEquals('CNY', $ret['ret'][1]['currency']);
        $this->assertEquals(2, count($ret['ret']));
    }

    /**
     * 測試更新會員總餘額(是否計算測試體系)
     */
    public function testUpdateTotalBalanceTestIndicate()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $redis = $this->getContainer()->get('snc_redis.total_balance');
        $redis->hset('cash_total_balance_2_901', 'normal', 200000);
        $redis->hset('cash_total_balance_2_901', 'test', 10000000);

        // 改為測試
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setTest(true);
        $em->flush();

        $parameters = array('parent_id' => 2);

        $client->request('PUT', '/api/cash/total_balance', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret'][0]['parent_id']);
        $this->assertEquals(20, $ret['ret'][0]['enable_balance']);
        $this->assertEquals(0, $ret['ret'][0]['disable_balance']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);

        $parameters = array(
            'parent_id'    => 2,
            'include_test' => 1,
            'force'        => 1
        );

        $client->request('PUT', '/api/cash/total_balance', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret'][0]['parent_id']);
        $this->assertEquals(1020, $ret['ret'][0]['enable_balance']);
        $this->assertEquals(0, $ret['ret'][0]['disable_balance']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
    }

    /**
     * 測試連續更新會員總餘額
     */
    public function testUpdateTotalBalanceFrequently()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = array('parent_id' => 2);

        //第一次更新會員總餘額
        $client->request('PUT', '/api/cash/total_balance', $parameters);

        // 操作紀錄檢查
        // 檢查是否紀錄新增二筆資料在cash_total_balance
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals("cash_total_balance", $logOperation->getTableName());
        $this->assertEquals("@parent_id:2", $logOperation->getMajorKey());
        $this->assertEquals("@currency:TWD", $logOperation->getMessage());
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals("cash_total_balance", $logOperation->getTableName());
        $this->assertEquals("@parent_id:2", $logOperation->getMajorKey());
        $this->assertEquals("@enable_balance:TWD 0=>1000", $logOperation->getMessage());

        //第二次更新會員總餘額
        $client->request('PUT', '/api/cash/total_balance', $parameters);

        // 測試是否有寫操作紀錄
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertEmpty($logOperation);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(array(), $ret['ret']);

        // 相差6分鐘即可再更新
        $ctb = $em->find('BB\DurianBundle\Entity\CashTotalBalance', 1);
        $now = new \Datetime('now');
        $ctb->setAt($now->modify('-6 mins'));
        $em->flush();

        $parameters = array('parent_id' => 2);

        $client->request('PUT', '/api/cash/total_balance', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret'][0]['parent_id']);
        $this->assertEquals(1000, $ret['ret'][0]['enable_balance']);
        $this->assertEquals(0, $ret['ret'][0]['disable_balance']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
    }

    /**
     * 測試更新會員總餘額帶入domain
     */
    public function testUpdateTotalBalanceWithDomain()
    {
        $client = $this->createClient();

        $parameters = array('parent_id' => 3);

        $client->request('PUT', '/api/cash/total_balance', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals('150040006', $ret['code']);
        $this->assertEquals('Not support this user', $ret['msg']);
    }

    /**
     * 測試更新會員總餘額帶入的上層ID不存在
     */
    public function testUpdateTotalBalanceWhenParentNotExist()
    {
        $client = $this->createClient();

        $parameters = array('parent_id' => 999);

        $client->request('PUT', '/api/cash/total_balance', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150040041, $ret['code']);
        $this->assertEquals('No such user', $ret['msg']);
    }

    /**
     * 測試取得會員總餘額
     */
    public function testGetTotalBalance()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = array('parent_id' => 2);

        $client->request('GET', '/api/cash/total_balance', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals('No cash total balance found', $ret['msg']);
        $this->assertEquals(150040022, $ret['code']);

        // 更新後再抓
        $parameters = array(
            'parent_id' => 2,
            'currency'  => 'TWD'
        );

        $client->request('PUT', '/api/cash/total_balance', $parameters);
        $client->request('GET', '/api/cash/total_balance', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret'][2][0]['parent_id']);
        $this->assertEquals(1000, $ret['ret'][2][0]['enable_balance']);
        $this->assertEquals(0, $ret['ret'][2][0]['disable_balance']);
        $this->assertEquals('TWD', $ret['ret'][2][0]['currency']);

        // 抓所有第一層的使用者
        $client->request('GET', '/api/cash/total_balance');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret'][2][0]['parent_id']);
        $this->assertEquals(1000, $ret['ret'][2][0]['enable_balance']);
        $this->assertEquals(0, $ret['ret'][2][0]['disable_balance']);
        $this->assertEquals('TWD', $ret['ret'][2][0]['currency']);

        $ctb = $em->find('BB\DurianBundle\Entity\User', 2);
        $ctb->disable();
        $em->flush();

        // 抓所有第一層的啟用使用者
        $parameters = array('enable' => 1);

        $client->request('GET', '/api/cash/total_balance', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(0, count($ret['ret'][9]));

        // 抓所有第一層的停用使用者
        $parameters = array('enable' => 0);

        $client->request('GET', '/api/cash/total_balance', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret'][2][0]['parent_id']);
        $this->assertEquals(1000, $ret['ret'][2][0]['enable_balance']);
        $this->assertEquals(0, $ret['ret'][2][0]['disable_balance']);
        $this->assertEquals('TWD', $ret['ret'][2][0]['currency']);
        $this->assertEquals(1, count($ret['ret'][2]));

        // 測試帶入已停用的parent_id，參數帶入 enable = 1
        $parameters = array(
            'parent_id' => 2,
            'enable'    => 1
        );

        $client->request('GET', '/api/cash/total_balance', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals('User is disabled', $ret['msg']);
        $this->assertEquals(150040039, $ret['code']);

        // 測試帶入已停用的parent_id，參數帶入 enable = 0
        $parameters = array(
            'parent_id' => 2,
            'enable'    => 0
        );

        $client->request('GET', '/api/cash/total_balance', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret'][2][0]['parent_id']);
        $this->assertEquals(1000, $ret['ret'][2][0]['enable_balance']);
        $this->assertEquals(0, $ret['ret'][2][0]['disable_balance']);
        $this->assertEquals('TWD', $ret['ret'][2][0]['currency']);

        // 測試帶入不存在的幣別
        $parameters = array(
            'parent_id' => 2,
            'enable'    => 0,
            'currency'  => 'CNY'
        );

        $client->request('GET', '/api/cash/total_balance', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals('No cash total balance found', $ret['msg']);
        $this->assertEquals(150040022, $ret['code']);

        // 測試帶入已啟用的parent_id，參數帶入 enable = 0
        $user = $em->find('BBDurianBundle:User', 2);
        $user->enable();
        $em->flush();

        $parameters = [
            'parent_id' => 2,
            'enable'    => 0
        ];

        $client->request('GET', '/api/cash/total_balance', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150040038, $ret['code']);
        $this->assertEquals('User is enabled', $ret['msg']);
    }

    /**
     * 測試不輸入父層查詢會員總餘額紀錄
     */
    public function testGetTotalBalanceWithoutParentId()
    {
        $client = $this->createClient();

        // 更新會員總餘額
        $parameters = [
            'parent_id' => 2,
            'currency' => 'TWD'
        ];

        $client->request('PUT', '/api/cash/total_balance', $parameters);

        // 回傳會員現金總餘額記錄
        $parameters = ['currency'  => 'TWD'];

        $client->request('GET', '/api/cash/total_balance', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret'][2][0]['parent_id']);
        $this->assertEquals(1000, $ret['ret'][2][0]['enable_balance']);
        $this->assertEquals(0, $ret['ret'][2][0]['disable_balance']);
        $this->assertEquals('TWD', $ret['ret'][2][0]['currency']);
    }

    /**
     * 測試更新會員總餘額，查不到幣別資訊時回傳空陣列
     */
    public function testGetTotalBalanceWithoutCurrency()
    {
        $client = $this->createClient();

        $parameters = ['parent_id' => 9];

        $client->request('PUT', '/api/cash/total_balance', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals([], $ret['ret']);
    }

    /**
     * 測試即時回傳會員總餘額
     */
    public function testGetTotalBalanceLive()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $redis = $this->getContainer()->get('snc_redis.total_balance');
        $redis->hset('cash_total_balance_2_156', 'test', 0);
        $redis->hset('cash_total_balance_2_901', 'test', 10000000);

        // 改為測試
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setTest(true);
        $em->flush();

        // 更新資料庫
        $parameters = [
            'parent_id' => 2,
            'include_test' => 1
        ];
        $client->request('PUT', '/api/cash/total_balance', $parameters);

        // 測試撈啟用人民幣餘額
        $parameters = array(
            'parent_id'    => 2,
            'include_test' => 1,
            'enable'       => 1,
            'currency'     => 'CNY'
        );

        $client->request('GET', '/api/cash/total_balance_live', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret'][0]['parent_id']);
        $this->assertEquals(0, $ret['ret'][0]['balance']);
        $this->assertEquals('CNY', $ret['ret'][0]['currency']);

        // 測試撈啟用台幣餘額
        $parameters = array(
            'parent_id'    => 2,
            'include_test' => 1,
            'enable'       => 1,
            'currency'     => 'TWD'
        );

        $client->request('GET', '/api/cash/total_balance_live', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret'][0]['parent_id']);
        $this->assertEquals(1000, $ret['ret'][0]['balance']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);

        // 測試停用會員後，同條件撈啟用餘額為0
        $user->disable();
        $em->flush();

        $client->request('GET', '/api/cash/total_balance_live', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret'][0]['parent_id']);
        $this->assertEquals(0, $ret['ret'][0]['balance']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);

        // 測試撈所有啟用會員台幣餘額
        $parameters = [
            'parent_id' => 2,
            'enable'    => 1,
            'currency'  => 'TWD'
        ];

        $redis->hset('cash_total_balance_2_901', 'normal', 12340000);
        $client->request('GET', '/api/cash/total_balance_live', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret'][0]['parent_id']);
        $this->assertEquals(1234, $ret['ret'][0]['balance']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);

        // 測試撈啟用一般會員台幣餘額
        $parameters = [
            'parent_id'    => 2,
            'include_test' => 0,
            'enable'       => 1,
            'currency'     => 'TWD'
        ];

        $client->request('GET', '/api/cash/total_balance_live', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret'][0]['parent_id']);
        $this->assertEquals(1234, $ret['ret'][0]['balance']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);

        // 測試撈不分停啟用全部台幣餘額
        $parameters = [
            'parent_id' => 2,
            'currency'  => 'TWD'
        ];

        $client->request('GET', '/api/cash/total_balance_live', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret'][0]['parent_id']);
        $this->assertEquals(2234, $ret['ret'][0]['balance']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);

        // 測試撈停用台幣餘額
        $parameters = [
            'parent_id'    => 2,
            'include_test' => 1,
            'enable'       => 0,
            'currency'     => 'TWD'
        ];

        $client->request('GET', '/api/cash/total_balance_live', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret'][0]['parent_id']);
        $this->assertEquals(1000, $ret['ret'][0]['balance']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);

        // 測試撈不分停啟用
        $redis->hset('cash_total_balance_2_901', 'test', 20000000);

        $parameters = [
            'parent_id'    => 2,
            'include_test' => 1,
            'currency'     => 'TWD'
        ];
        $client->request('GET', '/api/cash/total_balance_live', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret'][0]['parent_id']);
        $this->assertEquals(2000, $ret['ret'][0]['balance']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);

        // 測試撈非測試體系
        $parameters = [
            'parent_id'    => 2,
            'include_test' => 0,
            'currency'     => 'TWD'
        ];
        $client->request('GET', '/api/cash/total_balance_live', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret'][0]['parent_id']);
        $this->assertEquals(1234, $ret['ret'][0]['balance']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
    }

    /**
     * 測試取得會員即時總餘額記錄,帶入錯誤的parent_id
     */
    public function testGetTotalBalanceLiveWithErrorParentId()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id'    => 100,
            'include_test' => 1,
            'enable'       => 1,
            'currency'     => 'TWD'
        ];

        $client->request('GET', '/api/cash/total_balance_live', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150040040, $ret['code']);
        $this->assertEquals('Not a domain', $ret['msg']);
    }

    /**
     * 測試取得最近一筆額度為負的交易紀錄
     *
     * @author Chuck <jcwshih@gmail.com> 2013.07.29
     */
    public function testGetNegativeEntry()
    {
        $client = $this->createClient();

        //加入幾筆交易紀錄
        // balance = -1000
        $parameters = array(
            'at'          => '2012-12-01 12:00:00',
            'opcode'      => 1052,
            'memo'        => 'testMemo',
            'amount'      => -2000,
            'ref_id'      => 0,
            'auto_commit' => true
        );

        $client->request('PUT', '/api/user/2/cash/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        // balance = 1000
        $parameters = array(
            'at'          => '2012-12-01 12:00:00',
            'opcode'      => 1010,
            'memo'        => 'testMemo',
            'amount'      => 2000,
            'ref_id'      => 0,
            'auto_commit' => true
        );

        $client->request('PUT', '/api/user/2/cash/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        // balance = -1000
        $parameters = array(
            'at'          => '2012-12-01 12:00:00',
            'opcode'      => 1052,
            'memo'        => 'testMemo',
            'amount'      => -2000,
            'ref_id'      => 0,
            'auto_commit' => true
        );

        $client->request('PUT', '/api/user/2/cash/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $targetId1 = $ret['ret']['entry']['id'];     // 目標值

        // balance = -3000
        $parameters = array(
            'at'          => '2012-12-01 12:00:00',
            'opcode'      => 1052,
            'memo'        => 'testMemo',
            'amount'      => -2000,
            'ref_id'      => 0,
            'auto_commit' => true
        );

        $client->request('PUT', '/api/user/2/cash/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        // balance = -500
        $parameters = array(
            'at'          => '2012-12-02 12:00:00',
            'opcode'      => 1052,
            'amount'      => -1500,
            'ref_id'      => 0,
            'auto_commit' => true
        );

        $client->request('PUT', '/api/user/3/cash/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $targetId2 = $ret['ret']['entry']['id'];     // 目標值

        $this->assertEquals('ok', $ret['result']);

        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 1]);
        $this->runCommand('durian:sync-his-poper');

        $em = $this->getContainer()->get('doctrine.orm.his_entity_manager');

        // 修改infobright的資料，用來檢查搜尋的資料庫是否為infobright
        $entry = $em->getRepository('BBDurianBundle:CashEntry')->findOneBy(['id' => 1003]);
        $entry->setRefId(12345);

        $em->flush();

        $end = new \DateTime('now');

        // 查詢負數的明細，由於開始時間距今超過45天，會搜尋infobright
        $parameters = [
            'cash_id' => [1, 2, 3, 97],
            'start' => '2012-01-01T12:00:00+0800',
            'end' => $end->format(\DateTime::ISO8601)
        ];

        $client->request('GET', '/api/cash/negative_entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        // cash id 1
        $this->assertEquals($targetId1, $ret['ret'][0]['id']);
        $this->assertEquals(1, $ret['ret'][0]['cash_id']);
        $this->assertEquals(2, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(1052, $ret['ret'][0]['opcode']);
        $this->assertEquals('-2000', $ret['ret'][0]['amount']);
        $this->assertEquals('-1000', $ret['ret'][0]['balance']);
        $this->assertEquals(12345, $ret['ret'][0]['ref_id']);

        // cash id 2
        $this->assertEquals($targetId2, $ret['ret'][1]['id']);
        $this->assertEquals(2, $ret['ret'][1]['cash_id']);
        $this->assertEquals(3, $ret['ret'][1]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][1]['currency']);
        $this->assertEquals(1052, $ret['ret'][1]['opcode']);
        $this->assertEquals('-1500', $ret['ret'][1]['amount']);
        $this->assertEquals('-500', $ret['ret'][1]['balance']);
        $this->assertEquals('', $ret['ret'][1]['ref_id']);

        // cash id 3
        $this->assertEquals(false, isset($ret['ret'][2]));

        $start = new \DateTime('now');
        $start = $start->sub(new \DateInterval('PT1H'));
        $end = new \DateTime('now');

        // 查詢負數的明細，由於開始時間距今不超過45天，會搜尋原資料庫
        $parameters = [
            'cash_id' => [1, 2, 3],
            'start' => $start->format(\DateTime::ISO8601),
            'end' => $end->format(\DateTime::ISO8601)
        ];

        $client->request('GET', '/api/cash/negative_entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        // cash id 1
        $this->assertEquals($targetId1, $ret['ret'][0]['id']);
        $this->assertEquals(1, $ret['ret'][0]['cash_id']);
        $this->assertEquals(2, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(1052, $ret['ret'][0]['opcode']);
        $this->assertEquals('-2000', $ret['ret'][0]['amount']);
        $this->assertEquals('-1000', $ret['ret'][0]['balance']);
        $this->assertEquals('', $ret['ret'][0]['ref_id']);

        // cash id 2
        $this->assertEquals($targetId2, $ret['ret'][1]['id']);
        $this->assertEquals(2, $ret['ret'][1]['cash_id']);
        $this->assertEquals(3, $ret['ret'][1]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][1]['currency']);
        $this->assertEquals(1052, $ret['ret'][1]['opcode']);
        $this->assertEquals('-1500', $ret['ret'][1]['amount']);
        $this->assertEquals('-500', $ret['ret'][1]['balance']);
        $this->assertEquals('', $ret['ret'][1]['ref_id']);

        // cash id 3
        $this->assertEquals(false, isset($ret['ret'][2]));
    }

    /**
     * 測試回傳現金出款統計紀錄
     */
    public function testGetUncommitTransactionList()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $cash = $em->find('BBDurianBundle:CashTrans', 2);
        $cash->setRefId(0);
        $em->flush();

        $client = $this->createClient();

        // 測試只顯示前兩筆
        $parameters = [
            'first_result' => 0,
            'max_results' => 2
        ];

        $client->request('GET', '/api/cash/transaction/uncommit', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(2, $output['pagination']['max_results']);
        $this->assertEquals(3, $output['pagination']['total']);

        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(1, $output['ret'][0]['cash_id']);
        $this->assertEquals(2, $output['ret'][0]['user_id']);
        $this->assertEquals('TWD', $output['ret'][0]['currency']);
        $this->assertEquals(30001, $output['ret'][0]['opcode']);
        $this->assertEquals(101, $output['ret'][0]['amount']);
        $this->assertEquals(951, $output['ret'][0]['ref_id']);
        $this->assertEquals('2013-01-05T12:00:00+0800', $output['ret'][0]['created_at']);

        $this->assertEquals(2, $output['ret'][1]['id']);
        $this->assertEquals(2, $output['ret'][1]['cash_id']);
        $this->assertEquals(3, $output['ret'][1]['user_id']);
        $this->assertEquals('TWD', $output['ret'][1]['currency']);
        $this->assertEquals(30002, $output['ret'][1]['opcode']);
        $this->assertEquals(777, $output['ret'][1]['amount']);
        $this->assertEquals('', $output['ret'][1]['ref_id']);
        $this->assertEquals('2013-01-08T14:25:00+0800', $output['ret'][1]['created_at']);
        $this->assertFalse(isset($output['ret'][2]));
    }

    /**
     * 測試傳回現金額度不符記錄
     */
    public function testGetCashError()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/cash/error', ['sub_ret' => 1]);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals(3, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(2000, $ret['ret'][0]['balance']);
        $this->assertEquals(0, $ret['ret'][0]['pre_sub']);
        $this->assertEquals(0, $ret['ret'][0]['pre_add']);

        $this->assertEquals(4, $ret['ret'][1]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(3000, $ret['ret'][1]['balance']);
        $this->assertEquals(0, $ret['ret'][1]['pre_sub']);
        $this->assertEquals(0, $ret['ret'][1]['pre_add']);

        $this->assertEquals(count($ret['ret']), $ret['pagination']['total']);
    }

    /**
     * 測試修改現金明細備註欄位
     */
    public function testSetCashEntryMemo()
    {
        $client = $this->createClient();

        $memo = '';
        for ($i = 0; $i < 100; $i++) {
            $memo .= 'a';
        }
        $parameter = ['memo' => $memo . '012'];

        $client->request('PUT', '/api/cash/entry/9', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($memo, $output['ret']['memo']);

        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $repo = $emEntry->getRepository('BBDurianBundle:CashEntry');
        $ce = $repo->findOneBy(array('id' => 9));

        $this->assertEquals($memo, $ce->getMemo());

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $pRepo = $em->getRepository('BBDurianBundle:PaymentDepositWithdrawEntry');
        $pdwe = $pRepo->findOneBy(['id' => 9]);

        $this->assertEquals($memo, $pdwe->getMemo());

        $emHis = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $repoHis = $emHis->getRepository('BBDurianBundle:CashEntry');
        $ceHis = $repoHis->findOneBy(array('id' => 9));

        $this->assertEquals($memo, $ceHis->getMemo());
    }

    /**
     * 測試修改不存在的明細
     */
    public function testSetNotExistCashEntryMemo()
    {
        $client = $this->createClient();

        $parameter = array('memo' => 'hrhrhr');

        $client->request('PUT', '/api/cash/entry/999', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150040024, $output['code']);
        $this->assertEquals('No cash entry found', $output['msg']);
    }

    /**
     * 測試取得現金明細
     */
    public function testGetCashEntry()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/cash/entry/9');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(9, $output['ret']['id']);
        $this->assertEquals(1, $output['ret']['cash_id']);
        $this->assertEquals(11509530, $output['ret']['ref_id']);
    }

    /**
     * 測試取單筆現金明細時，此筆明細不存在的情況
     */
    public function testGetCashEntryNotFound()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/cash/entry/999');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150040024, $output['code']);
        $this->assertEquals('No cash entry found', $output['msg']);
    }

    /**
     * 測試確認現金交易時, 不需讀取資料庫
     */
    public function testTransactionCommitNoNeedToReadDb()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BBDurianBundle:User', 8);
        $cash = $user->getCash();
        $cashId = $cash->getId();

        $parameters = [
            'opcode'      => 10002,
            'amount'      => 9000,
            'auto_commit' => false
        ];

        $client->request('PUT', '/api/user/8/cash/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret  = json_decode($json, true);

        $this->assertEquals(8, $ret['ret']['entry']['user_id']);
        $this->assertEquals('TWD', $ret['ret']['entry']['currency']);
        $this->assertEquals(10002, $ret['ret']['entry']['opcode']);
        $this->assertEquals(9000, $ret['ret']['entry']['amount']);
        $this->assertEquals(1000, $ret['ret']['cash']['balance']);
        $this->assertEquals(0, $ret['ret']['cash']['pre_sub']);
        $this->assertEquals(9000, $ret['ret']['cash']['pre_add']);

        $em->remove($cash);
        $em->flush();
        $em->clear();

        $transId = $ret['ret']['entry']['id'];
        $client->request('PUT', "/api/cash/transaction/$transId/commit");

        $json = $client->getResponse()->getContent();
        $ret  = json_decode($json, true);

        $this->assertNull($user->getCash());
        $this->assertEquals($cashId, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['cash']['user_id']);
        $this->assertEquals(10000, $ret['ret']['cash']['balance']);
        $this->assertEquals('TWD', $ret['ret']['cash']['currency']);
    }

    /**
     * 測試ref_id取得現金明細
     */
    public function testGetEntriesRefId()
    {
        $client = $this->createClient();

        //測試ref_id取得明細，帶入條件first_result, max_results
        $params = [
            'ref_id' => 238030097,
            'first_result' => 0,
            'max_results' => 1
        ];

        $client->request('GET', '/api/cash/entries_by_ref_id', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(1001, $output['ret'][0]['opcode']);
        $this->assertEquals('2013-01-01T12:00:00+0800', $output['ret'][0]['created_at']);
        $this->assertEquals(2, $output['ret'][0]['user_id']);
        $this->assertEquals(238030097, $output['ret'][0]['ref_id']);
        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(1, $output['pagination']['max_results']);
        $this->assertEquals(1, $output['pagination']['total']);
    }

    /**
     * 測試取得現金列表
     */
    public function testGetCashList()
    {
        $client = $this->createClient();

        $parameters = ['parent_id' => '2'];

        $client->request('GET', '/api/cash/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertEquals(3, $output['ret'][0]['user_id']);
        $this->assertEquals(901, $output['ret'][0]['currency']);
        $this->assertEquals(0, $output['ret'][0]['pre_sub']);
        $this->assertEquals(0, $output['ret'][0]['pre_add']);
        $this->assertFalse($output['ret'][0]['negative']);
        $this->assertEquals(1000, $output['ret'][0]['balance']);
        $this->assertEquals(20120101120000, $output['ret'][0]['last_entry_at']);

        $this->assertEquals(3, $output['ret'][1]['id']);
        $this->assertEquals(4, $output['ret'][1]['user_id']);
        $this->assertEquals(901, $output['ret'][1]['currency']);
        $this->assertEquals(0, $output['ret'][1]['pre_sub']);
        $this->assertEquals(0, $output['ret'][1]['pre_add']);
        $this->assertFalse($output['ret'][1]['negative']);
        $this->assertEquals(1000, $output['ret'][1]['balance']);
        $this->assertEquals(20120101120000, $output['ret'][1]['last_entry_at']);

        $this->assertEquals(4, $output['ret'][2]['id']);
        $this->assertEquals(5, $output['ret'][2]['user_id']);
        $this->assertEquals(901, $output['ret'][2]['currency']);
        $this->assertEquals(0, $output['ret'][2]['pre_sub']);
        $this->assertEquals(0, $output['ret'][2]['pre_add']);
        $this->assertFalse($output['ret'][2]['negative']);
        $this->assertEquals(1000, $output['ret'][2]['balance']);
        $this->assertEquals(20120101120000, $output['ret'][2]['last_entry_at']);

        $this->assertEquals(5, $output['ret'][3]['id']);
        $this->assertEquals(6, $output['ret'][3]['user_id']);
        $this->assertEquals(901, $output['ret'][3]['currency']);
        $this->assertEquals(0, $output['ret'][3]['pre_sub']);
        $this->assertEquals(0, $output['ret'][3]['pre_add']);
        $this->assertFalse($output['ret'][3]['negative']);
        $this->assertEquals(1000, $output['ret'][3]['balance']);
        $this->assertEquals(20120101120000, $output['ret'][3]['last_entry_at']);

        $this->assertEquals(6, $output['ret'][4]['id']);
        $this->assertEquals(7, $output['ret'][4]['user_id']);
        $this->assertEquals(901, $output['ret'][4]['currency']);
        $this->assertEquals(0, $output['ret'][4]['pre_sub']);
        $this->assertEquals(0, $output['ret'][4]['pre_add']);
        $this->assertFalse($output['ret'][4]['negative']);
        $this->assertEquals(1000, $output['ret'][4]['balance']);
        $this->assertEquals(20120101120000, $output['ret'][4]['last_entry_at']);

        $this->assertEquals(7, $output['ret'][5]['id']);
        $this->assertEquals(8, $output['ret'][5]['user_id']);
        $this->assertEquals(901, $output['ret'][5]['currency']);
        $this->assertEquals(0, $output['ret'][5]['pre_sub']);
        $this->assertEquals(0, $output['ret'][5]['pre_add']);
        $this->assertFalse($output['ret'][5]['negative']);
        $this->assertEquals(1000, $output['ret'][5]['balance']);
        $this->assertEquals(20120101120000, $output['ret'][5]['last_entry_at']);

        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(0, $output['pagination']['max_results']);
        $this->assertEquals(7, $output['pagination']['total']);
    }

    /**
     * 測試取得指定條件的現金列表
     */
    public function testGetCashListWithSpecifiedCriteria()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id'    => '2',
            'depth'        => '6',
            'currency'     => 'TWD',
            'first_result' => 0,
            'max_results'  => 20
        ];

        $client->request('GET', '/api/cash/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(7, $output['ret'][0]['id']);
        $this->assertEquals(8, $output['ret'][0]['user_id']);
        $this->assertEquals(901, $output['ret'][0]['currency']);
        $this->assertEquals(0, $output['ret'][0]['pre_sub']);
        $this->assertEquals(0, $output['ret'][0]['pre_add']);
        $this->assertFalse($output['ret'][0]['negative']);
        $this->assertEquals(1000, $output['ret'][0]['balance']);

        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(20, $output['pagination']['max_results']);
        $this->assertEquals(2, $output['pagination']['total']);
    }

    /**
     * 測試依使用者取得最近一筆額度為負的交易紀錄
     */
    public function testGetNegativeEntryByUser()
    {
        $client = $this->createClient();

        // 加入幾筆交易紀錄
        // balance = -1000
        $parameters = [
            'at'          => '2012-12-01 12:00:00',
            'opcode'      => 1052,
            'memo'        => 'testMemo',
            'amount'      => -2000,
            'ref_id'      => 0,
            'auto_commit' => true
        ];

        $client->request('PUT', '/api/user/2/cash/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        // balance = 1000
        $parameters = [
            'at'          => '2012-12-01 12:00:00',
            'opcode'      => 1010,
            'memo'        => 'testMemo',
            'amount'      => 2000,
            'ref_id'      => 0,
            'auto_commit' => true
        ];

        $client->request('PUT', '/api/user/2/cash/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        // balance = -1000
        $parameters = [
            'at'          => '2012-12-01 12:00:00',
            'opcode'      => 1052,
            'memo'        => 'testMemo',
            'amount'      => -2000,
            'ref_id'      => 0,
            'auto_commit' => true
        ];

        $client->request('PUT', '/api/user/2/cash/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        // 目標值
        $targetId1 = $ret['ret']['entry']['id'];

        // balance = -3000
        $parameters = [
            'at'          => '2012-12-01 12:00:00',
            'opcode'      => 1052,
            'memo'        => 'testMemo',
            'amount'      => -2000,
            'ref_id'      => 0,
            'auto_commit' => true
        ];

        $client->request('PUT', '/api/user/2/cash/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        // balance = -500
        $parameters = [
            'at'          => '2012-12-02 12:00:00',
            'opcode'      => 1052,
            'amount'      => -1500,
            'ref_id'      => 0,
            'auto_commit' => true
        ];

        $client->request('PUT', '/api/user/3/cash/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        // 目標值
        $targetId2 = $ret['ret']['entry']['id'];

        $this->assertEquals('ok', $ret['result']);

        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 1]);
        $this->runCommand('durian:sync-his-poper');

        $em = $this->getContainer()->get('doctrine.orm.his_entity_manager');

        // 修改infobright的資料，用來檢查搜尋的資料庫是否為infobright
        $entry = $em->getRepository('BBDurianBundle:CashEntry')->findOneBy(['id' => 1003]);
        $entry->setRefId(12345);

        $em->flush();

        $end = new \DateTime('now');

        // 查詢負數的明細，由於開始時間距今超過45天，會搜尋infobright
        $parameters = [
            'user_id' => [2, 3, 4],
            'start' => '2012-01-01T12:00:00+0800',
            'end' => $end->format(\DateTime::ISO8601)
        ];

        $client->request('GET', '/api/user/cash/negative_entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        // cash id 1
        $this->assertEquals($targetId1, $ret['ret'][0]['id']);
        $this->assertEquals(1, $ret['ret'][0]['cash_id']);
        $this->assertEquals(2, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(1052, $ret['ret'][0]['opcode']);
        $this->assertEquals('-2000', $ret['ret'][0]['amount']);
        $this->assertEquals('-1000', $ret['ret'][0]['balance']);
        $this->assertEquals(12345, $ret['ret'][0]['ref_id']);

        // cash id 2
        $this->assertEquals($targetId2, $ret['ret'][1]['id']);
        $this->assertEquals(2, $ret['ret'][1]['cash_id']);
        $this->assertEquals(3, $ret['ret'][1]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][1]['currency']);
        $this->assertEquals(1052, $ret['ret'][1]['opcode']);
        $this->assertEquals('-1500', $ret['ret'][1]['amount']);
        $this->assertEquals('-500', $ret['ret'][1]['balance']);
        $this->assertEquals('', $ret['ret'][1]['ref_id']);

        // cash id 3
        $this->assertEquals(false, isset($ret['ret'][2]));

        $start = new \DateTime('now');
        $start = $start->sub(new \DateInterval('PT1H'));
        $end = new \DateTime('now');

        // 查詢負數的明細，由於開始時間距今不超過45天，會搜尋原資料庫
        $parameters = [
            'user_id' => [2, 3, 4, 77],
            'start' => $start->format(\DateTime::ISO8601),
            'end' => $end->format(\DateTime::ISO8601)
        ];

        $client->request('GET', '/api/user/cash/negative_entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        // cash id 1
        $this->assertEquals($targetId1, $ret['ret'][0]['id']);
        $this->assertEquals(1, $ret['ret'][0]['cash_id']);
        $this->assertEquals(2, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(1052, $ret['ret'][0]['opcode']);
        $this->assertEquals('-2000', $ret['ret'][0]['amount']);
        $this->assertEquals('-1000', $ret['ret'][0]['balance']);
        $this->assertEquals('', $ret['ret'][0]['ref_id']);

        // cash id 2
        $this->assertEquals($targetId2, $ret['ret'][1]['id']);
        $this->assertEquals(2, $ret['ret'][1]['cash_id']);
        $this->assertEquals(3, $ret['ret'][1]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][1]['currency']);
        $this->assertEquals(1052, $ret['ret'][1]['opcode']);
        $this->assertEquals('-1500', $ret['ret'][1]['amount']);
        $this->assertEquals('-500', $ret['ret'][1]['balance']);
        $this->assertEquals('', $ret['ret'][1]['ref_id']);

        // cash id 3
        $this->assertEquals(false, isset($ret['ret'][2]));
    }

    /**
     * 測試回傳負數餘額與第一筆導致額度為負的明細
     */
    public function testGetNegative()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $cash = $em->find('BBDurianBundle:Cash', 1);
        $cash->setBalance(-1);
        $cash->setNegative(true);
        $em->flush();

        $res = $this->getResponse('GET', '/api/cash/negative', ['first_result' => 0, 'max_results' => 1]);

        $this->assertEquals('ok', $res['result']);
        $this->assertEquals(1, $res['ret'][0]['cash']['id']);
        $this->assertEquals(2, $res['ret'][0]['cash']['user_id']);
        $this->assertEquals('TWD', $res['ret'][0]['cash']['currency']);
        $this->assertEquals(-1, $res['ret'][0]['cash']['balance']);
        $this->assertEquals(3, $res['ret'][0]['entry']['id']);
        $this->assertEquals(1, $res['ret'][0]['entry']['cash_id']);
        $this->assertEquals(2, $res['ret'][0]['entry']['user_id']);
        $this->assertEquals('TWD', $res['ret'][0]['entry']['currency']);
        $this->assertEquals(1023, $res['ret'][0]['entry']['opcode']);
        $this->assertEquals('2016-11-17T22:36:01+0800', $res['ret'][0]['entry']['created_at']);
        $this->assertEquals(-2, $res['ret'][0]['entry']['amount']);
        $this->assertEquals(-1, $res['ret'][0]['entry']['balance']);
        $this->assertEquals(2345, $res['ret'][0]['entry']['ref_id']);
        $this->assertEquals('測試備註', $res['ret'][0]['entry']['memo']);
        $this->assertEquals(0, $res['pagination']['first_result']);
        $this->assertEquals(1, $res['pagination']['max_results']);
        $this->assertEquals(1, $res['pagination']['total']);
    }
}
