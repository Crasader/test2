<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\CashFakeEntryOperator;
use BB\DurianBundle\Entity\CashFakeEntry;
use BB\DurianBundle\Entity\CashFake;
use BB\DurianBundle\Entity\User;

class CashFakeFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeDataForTotalCalculate',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeEntryDataForTotalCalculate',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeTransferEntryDataForTotalCalculate',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeEntryOperatorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserPaywayData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeNegativeData'
        ];

        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeEntryDataForTotalCalculate'
        ];

        $this->loadFixtures($classnames, 'his');

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeErrorData'
        ];

        $this->loadFixtures($classnames, 'share');

        $redis = $this->getContainer()->get('snc_redis.sequence');

        $redis->set('cashfake_seq', 1000);

        $redis = $this->getContainer()->get('snc_redis.total_balance');
        $redis->hset('cash_fake_total_balance_2_156', 'normal', 1500000);
        $redis->hset('cash_fake_total_balance_2_156', 'test', 0);
    }

    /**
     * 測試新增快開額度本身有 payway 且尚未啟用
     */
    public function testNewCashFakeWithDisabledPayway()
    {
        $client = $this->createClient();

        $parameters = array(
            'currency'  => 'CNY'
        );

        $client->request('POST', '/api/user/10/cashFake', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertArrayHasKey('id', $ret['ret']);
        $this->assertEquals(10, $ret['ret']['user_id']);
        $this->assertEquals(0, $ret['ret']['balance']);
        $this->assertEquals(0, $ret['ret']['pre_sub']);
        $this->assertEquals(0, $ret['ret']['pre_add']);
        $this->assertEquals('CNY', $ret['ret']['currency']);
        $this->assertEquals(true, $ret['ret']['enable']);

        // 檢查 payway
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $payway = $em->find('BBDurianBundle:UserPayway', 10);
        $this->assertFalse($payway->isCashEnabled());
        $this->assertTrue($payway->isCashFakeEnabled());
        $this->assertFalse($payway->isCreditEnabled());

        // 檢查 logOperation
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals("user_payway", $logOperation->getTableName());
        $this->assertEquals("@user_id:10", $logOperation->getMajorKey());
        $this->assertEquals("@cash_fake:false=>true", $logOperation->getMessage());
    }

    /**
     * 測試新增快開額度廳主本身沒有 payway
     */
    public function testNewCashFakeDomainWithoutPayway()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $user = new User();
        $user->setId(13);
        $user->setUsername('lala');
        $user->setAlias('lala');
        $user->setPassword('lalalala');
        $user->setDomain(13);
        $user->setRole(7);

        $em->persist($user);
        $em->flush();

        $parameters = ['currency' => 'CNY'];

        $client->request('POST', '/api/user/13/cashFake', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertArrayHasKey('id', $ret['ret']);
        $this->assertEquals(13, $ret['ret']['user_id']);
        $this->assertEquals(0, $ret['ret']['balance']);
        $this->assertEquals(0, $ret['ret']['pre_sub']);
        $this->assertEquals(0, $ret['ret']['pre_add']);
        $this->assertEquals('CNY', $ret['ret']['currency']);
        $this->assertEquals(true, $ret['ret']['enable']);

        // 檢查 payway
        $payway = $em->find('BBDurianBundle:UserPayway', 13);
        $this->assertFalse($payway->isCashEnabled());
        $this->assertTrue($payway->isCashFakeEnabled());
        $this->assertFalse($payway->isCreditEnabled());

        // 檢查 logOperation
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals("user_payway", $logOperation->getTableName());
        $this->assertEquals("@user_id:13", $logOperation->getMajorKey());
        $this->assertEquals("@cash_fake:true", $logOperation->getMessage());
    }

    /**
     * 測試新增快開額度本身沒有 payway 且有上層
     */
    public function testNewCashFakeWithParentWithoutPayway()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parent = $em->find('BBDurianBundle:User', 9);

        $user = new User();
        $user->setId(13);
        $user->setUsername('lala');
        $user->setParent($parent);
        $user->setAlias('lala');
        $user->setPassword('lalalala');
        $user->setDomain(9);
        $user->setRole(1);

        $em->persist($user);
        $em->flush();

        $parameters = ['currency' => 'CNY'];

        $client->request('POST', '/api/user/13/cashFake', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertArrayHasKey('id', $ret['ret']);
        $this->assertEquals(13, $ret['ret']['user_id']);
        $this->assertEquals(0, $ret['ret']['balance']);
        $this->assertEquals(0, $ret['ret']['pre_sub']);
        $this->assertEquals(0, $ret['ret']['pre_add']);
        $this->assertEquals('CNY', $ret['ret']['currency']);
        $this->assertEquals(true, $ret['ret']['enable']);

        // 檢查 上層payway
        $payway = $em->find('BBDurianBundle:UserPayway', 9);
        $this->assertTrue($payway->isCashEnabled());
        $this->assertTrue($payway->isCashFakeEnabled());
        $this->assertTrue($payway->isCreditEnabled());
    }

    /**
     * 測試新增快開額度上層無假現金
     */
    public function testNewCashFakeButParentHasNoCashFake()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parent = $em->find('BBDurianBundle:User', 10);

        $user = new User();
        $user->setId(13);
        $user->setUsername('lala');
        $user->setParent($parent);
        $user->setAlias('lala');
        $user->setPassword('lalalala');
        $user->setDomain(9);
        $user->setRole(1);

        $cashFake = new CashFake($user, 901); // TWD

        $em->persist($user);
        $em->persist($cashFake);

        $em->flush();

        $parameters = ['currency' => 'CNY'];

        $client->request('POST', '/api/user/13/cashFake', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150050006, $ret['code']);
        $this->assertEquals('No parent cashFake found', $ret['msg']);
    }

    /**
     * 測試傳回假現金的資料
     */
    public function testGet()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/cash_fake/1');
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(1, $ret['ret']['id']);
        $this->assertEquals(2, $ret['ret']['user_id']);
        $this->assertEquals(5000, $ret['ret']['balance']);
        $this->assertEquals(0, $ret['ret']['pre_sub']);
        $this->assertEquals(0, $ret['ret']['pre_add']);
        $this->assertEquals('CNY', $ret['ret']['currency']);
        $this->assertEquals(1, $ret['ret']['enable']);
        $this->assertEquals(20120101120000, $ret['ret']['last_entry_at']);
    }

    /**
     * 測試傳回無假現金的資料
     */
    public function testGetButCashFakeNotExist()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/cash_fake/10');
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150050001, $ret['code']);
        $this->assertEquals('No cashFake found', $ret['msg']);
    }

    /**
     * 測試帶入userId回傳假現金餘額
     */
    public function testGetCashFakeByUserId()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/2/cash_fake');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertEquals(2, $output['ret']['user_id']);
        $this->assertEquals(5000, $output['ret']['balance']);
        $this->assertEquals(0, $output['ret']['pre_sub']);
        $this->assertEquals(0, $output['ret']['pre_add']);
        $this->assertEquals('CNY', $output['ret']['currency']);
        $this->assertTrue($output['ret']['enable']);
        $this->assertEquals(20120101120000, $output['ret']['last_entry_at']);
    }

    /**
     * 測試帶入userId回傳假現金餘額，但假現金不存在
     */
    public function testGetCashFakeByUserIdButNoCashFakeFound()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/50/cash_fake');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150050001, $output['code']);
        $this->assertEquals('No cashFake found', $output['msg']);
    }

    /**
     * 測試直接由系統轉移額度
     */
    public function testCashFakeDirectTransferWithForce()
    {
        $client = $this->createClient();
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');

        $user = $em->find('BB\DurianBundle\Entity\User', 9);
        $cashFake = $user->getCashFake();

        $this->assertEquals(1000, $cashFake->getBalance());
        $this->assertEquals(0, $cashFake->getPreSub());
        $this->assertEquals(0, $cashFake->getPreAdd());

        $parameters = array(
            'target'   => 9,
            'amount'   => 20,
            'force'    => 1,
            'operator' => 'isolate'
        );

        $client->request('PUT', '/api/cash_fake/transfer', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        //跑背景程式讓queue被消化
        $params = [
            '--entry' => true,
            '--balance' => true
        ];
        $this->runCommand('durian:sync-cash-fake', $params);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(9, $ret['ret']['target_cash_fake']['user_id']);
        $this->assertEquals(1020, $ret['ret']['target_cash_fake']['balance']);
        $this->assertEquals(0, $ret['ret']['target_cash_fake']['pre_sub']);
        $this->assertEquals(0, $ret['ret']['target_cash_fake']['pre_add']);
        $this->assertEquals('CNY', $ret['ret']['target_cash_fake']['currency']);
        $this->assertEquals(true, $ret['ret']['target_cash_fake']['enable']);
        $this->assertEquals(1001, $ret['ret']['target_entries'][0]['id']);
        $this->assertEquals(20, $ret['ret']['target_entries'][0]['amount']);
        $this->assertEquals(1020, $ret['ret']['target_entries'][0]['opcode']);
        $this->assertEquals('isolate', $ret['ret']['target_entries'][0]['operator']['username']);
        $this->assertEquals(2, $ret['ret']['target_entries'][0]['cash_fake_version']);

        $operator = $em->getRepository('BB\DurianBundle\Entity\CashFakeEntryOperator')
            ->findOneBy(array('entryId' => $ret['ret']['target_entries'][0]['id']));

        $this->assertEquals('isolate', $operator->getUsername());
    }

    /**
     * 測試直接由系統轉移額度但帶入force及source參數會進行正常轉帳動作
     */
    public function testCashFakeDirectTransferWithForceAndSource()
    {
        $client = $this->createClient();

        $parameters = array(
            'source'   => 2,
            'target'   => 4,
            'amount'   => 20,
            'force'    => 1
        );

        $client->request('PUT', '/api/cash_fake/transfer', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals(2, $ret['ret']['cash_fake']['user_id']);
        $this->assertEquals(4980, $ret['ret']['cash_fake']['balance']);
        $this->assertEquals(0, $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals(0, $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(1001, $ret['ret']['entries']['id']);
        $this->assertEquals(-20, $ret['ret']['entries']['amount']);
        $this->assertEquals(2, $ret['ret']['entries']['cash_fake_version']);
        $this->assertEquals(4, $ret['ret']['target_cash_fake']['user_id']);
        $this->assertEquals(1270, $ret['ret']['target_cash_fake']['balance']);
        $this->assertEquals(0, $ret['ret']['target_cash_fake']['pre_sub']);
        $this->assertEquals(0, $ret['ret']['target_cash_fake']['pre_add']);
        $this->assertEquals('CNY', $ret['ret']['target_cash_fake']['currency']);
        $this->assertEquals(true, $ret['ret']['target_cash_fake']['enable']);
        $this->assertEquals(1002, $ret['ret']['target_entries']['id']);
        $this->assertEquals(20, $ret['ret']['target_entries']['amount']);
        $this->assertEquals(1003, $ret['ret']['target_entries']['opcode']);
        $this->assertEquals(2, $ret['ret']['target_entries']['cash_fake_version']);
    }

    /**
     * 測試快開額度直接轉帳
     */
    public function testCashFakeDirectTransferWithOpcode1003()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $sourceUser =  $em->find('BB\DurianBundle\Entity\User', 7);
        $targetUser =  $em->find('BB\DurianBundle\Entity\User', 8);

        $client = $this->createClient();

        $parameters = array(
            'source'   => 7,
            'target'   => 8,
            'amount'   => 20,
            'operator' => 'tester'
        );

        $client->request('PUT', '/api/cash_fake/transfer', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        //跑背景程式讓queue被消化
        $params = [
            '--entry' => true,
            '--balance' => true
        ];
        $this->runCommand('durian:sync-cash-fake', $params);

        $this->assertEquals('ok', $ret['result']);

        //驗證目標及來源的額度是否正確
        $sCashfake = $sourceUser->getCashfake();
        $tCashfake = $targetUser->getCashfake();
        $this->assertEquals($ret['ret']['cash_fake']['id'], $sCashfake->getId());
        $this->assertEquals($ret['ret']['target_cash_fake']['id'], $tCashfake->getId());
        $this->assertEquals($ret['ret']['cash_fake']['balance'], $sCashfake->getBalance());
        $this->assertEquals($ret['ret']['cash_fake']['pre_sub'], $sCashfake->getPreSub());
        $this->assertEquals($ret['ret']['cash_fake']['pre_add'], $sCashfake->getPreAdd());
        $this->assertEquals($ret['ret']['target_cash_fake']['balance'], $tCashfake->getBalance());
        $this->assertEquals($ret['ret']['target_cash_fake']['pre_sub'], $tCashfake->getPreSub());
        $this->assertEquals($ret['ret']['target_cash_fake']['pre_add'], $tCashfake->getPreAdd());

        //驗證目標及來源的交易明細已寫入資料庫且正確
        $sEntry = $em->getRepository('BB\DurianBundle\Entity\CashFakeEntry')
                     ->findOneBy(array('id' => $ret['ret']['entries']['id']));
        $tEntry = $em->getRepository('BB\DurianBundle\Entity\CashFakeEntry')
                    ->findOneBy(array('id' => $ret['ret']['target_entries']['id']));
        $sOperator = $em->getRepository('BB\DurianBundle\Entity\CashFakeEntryOperator')
                    ->findOneBy(array('entryId' => $ret['ret']['entries']['operator']['entry_id']));
        $tOperator = $em->getRepository('BB\DurianBundle\Entity\CashFakeEntryOperator')
                    ->findOneBy(array('entryId' => $ret['ret']['target_entries']['operator']['entry_id']));

        $this->assertEquals($ret['ret']['entries']['opcode'], $sEntry->getOpcode());
        $this->assertEquals($ret['ret']['entries']['amount'], $sEntry->getAmount());
        $this->assertEquals($ret['ret']['entries']['balance'], $sEntry->getBalance());
        $this->assertEquals($ret['ret']['entries']['ref_id'], '');
        $this->assertEquals($ret['ret']['entries']['operator']['username'], $targetUser->getUsername());
        $this->assertEquals($ret['ret']['entries']['operator']['username'], $sOperator->getUsername());
        $this->assertEquals($ret['ret']['entries']['flow']['whom'], $sOperator->getWhom());
        $this->assertEquals($ret['ret']['entries']['flow']['whom'], $targetUser->getUsername());
        $this->assertEquals($ret['ret']['entries']['flow']['level'], $sOperator->getLevel());
        $this->assertEquals($ret['ret']['entries']['flow']['level'], 1);
        $this->assertEquals($ret['ret']['entries']['flow']['transfer_out'], $sOperator->getTransferOut());
        $this->assertEquals($ret['ret']['entries']['flow']['transfer_out'], $sEntry->getAmount() < 0);
        $this->assertEquals($ret['ret']['entries']['cash_fake_version'], $sEntry->getCashFakeVersion());

        $this->assertEquals($ret['ret']['target_entries']['opcode'], $tEntry->getOpcode());
        $this->assertEquals($ret['ret']['target_entries']['amount'], $tEntry->getAmount());
        $this->assertEquals($ret['ret']['target_entries']['balance'], $tEntry->getBalance());
        $this->assertEquals($ret['ret']['target_entries']['ref_id'], '');
        $this->assertEquals($ret['ret']['target_entries']['operator']['username'], $targetUser->getUsername());
        $this->assertEquals($ret['ret']['target_entries']['operator']['username'], $tOperator->getUsername());
        $this->assertEquals($ret['ret']['target_entries']['flow']['whom'], $tOperator->getWhom());
        $this->assertEquals($ret['ret']['target_entries']['flow']['whom'], $sourceUser->getUsername());
        $this->assertEquals($ret['ret']['target_entries']['flow']['level'], $tOperator->getLevel());
        $this->assertEquals($ret['ret']['target_entries']['flow']['level'], 2);
        $this->assertEquals($ret['ret']['target_entries']['flow']['transfer_out'], $tOperator->getTransferOut());
        $this->assertEquals($ret['ret']['target_entries']['flow']['transfer_out'], $tEntry->getAmount() < 0);
        $this->assertEquals($ret['ret']['target_entries']['cash_fake_version'], $tEntry->getCashFakeVersion());
    }

    /**
     * 測試快開額度直接轉帳但上層餘額不足
     */
    public function testCashFakeDirectTransferButParentNotEnoughBalance()
    {
        $client = $this->createClient();

        $parameters = array(
            'source'   => 7,
            'target'   => 8,
            'amount'   => 200
        );

        $client->request('PUT', '/api/cash_fake/transfer', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150050031, $ret['code']);
        $this->assertEquals('Not enough balance', $ret['msg']);
    }

    /**
     * 測試直接由系統轉移額度但target無假現金
     */
    public function testCashFakeDirectTransferButTargetNoCashFake()
    {
        $client = $this->createClient();

        $parameters = [
            'target'   => 10,
            'amount'   => 20,
            'operator' => 'isolate'
        ];

        $client->request('PUT', '/api/cash_fake/transfer', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150050001, $ret['code']);
        $this->assertEquals('No cashFake found', $ret['msg']);
    }

    /**
     * 測試直接由系統轉移額度不強制扣款也不帶入source參數
     */
    public function testCashFakeDirectTransferWithoutForceWithoutSource()
    {
        $client = $this->createClient();

        $parameters = [
            'target'   => 9,
            'amount'   => 20,
            'operator' => 'isolate'
        ];

        $client->request('PUT', '/api/cash_fake/transfer', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150050005, $ret['code']);
        $this->assertEquals('No source user specified', $ret['msg']);
    }

    /**
     * 測試直接由系統轉移額度強制扣款但source無假現金
     */
    public function testCashFakeDirectTransferWithForceButSourceNoCashFake()
    {
        $client = $this->createClient();

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $parent = $em->find('BBDurianBundle:User', 10);

        $user = new User();
        $user->setId(11);
        $user->setUsername('haha');
        $user->setParent($parent);
        $user->setAlias('haha');
        $user->setPassword('hahahaha');
        $user->setDomain(9);
        $user->setRole(5);

        $cashFake = new CashFake($user, 156);

        $em->persist($user);
        $em->persist($cashFake);

        $em->flush();

        $parameters = [
            'source'   => 10,
            'target'   => 11,
            'amount'   => 20,
            'operator' => 'isolate',
            'force'    => 1
        ];

        $client->request('PUT', '/api/cash_fake/transfer', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150050001, $ret['code']);
        $this->assertEquals('No cashFake found', $ret['msg']);
    }

    /**
     * 測試直接由系統轉移額度強制扣款但source與target不同體系
     */
    public function testCashFakeDirectTransferWithForceButNotInHierarchy()
    {
        $client = $this->createClient();

        $parameters = [
            'source'   => 9,
            'target'   => 8,
            'amount'   => 20,
            'operator' => 'isolate',
            'force'    => 1
        ];

        $client->request('PUT', '/api/cash_fake/transfer', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150050003, $ret['code']);
        $this->assertEquals('Can not transfer cashFake when User not in the hierarchy', $ret['msg']);
    }

    /**
     * 測試直接由系統轉移額度強制扣款但幣別與上層不同
     */
    public function testCashFakeDirectTransferWithForceButDifferentCurrencyBetweenChildAndParent()
    {
        $client = $this->createClient();

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $parent = $em->find('BBDurianBundle:User', 9);

        $user = new User();
        $user->setId(11);
        $user->setUsername('haha');
        $user->setParent($parent);
        $user->setAlias('haha');
        $user->setPassword('hahahaha');
        $user->setDomain(2);
        $user->setRole(5);

        $cashFake = new CashFake($user, 901);

        $em->persist($user);
        $em->persist($cashFake);

        $em->flush();

        $parameters = [
            'source'   => 9,
            'target'   => 11,
            'amount'   => 20,
            'operator' => 'isolate',
            'force'    => 1
        ];

        $client->request('PUT', '/api/cash_fake/transfer', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150050002, $ret['code']);
        $this->assertEquals('Different currency between child and parent', $ret['msg']);
    }

    /**
     * 測試回傳快開
     */
    public function testGetCashFakeInfo()
    {
        $client = $this->createClient();

        $parameters = array(
            'users'  => array(2),
            'fields' => array('cash_fake')
        );

        $client->request('GET', '/api/users', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals(2, $ret['ret'][0]['id']);
        $this->assertEquals(5000, $ret['ret'][0]['cash_fake']['balance']);
        $this->assertEquals(0, $ret['ret'][0]['cash_fake']['pre_sub']);
        $this->assertEquals(0, $ret['ret'][0]['cash_fake']['pre_add']);
        $this->assertEquals('CNY', $ret['ret'][0]['cash_fake']['currency']);
        $this->assertTrue($ret['ret'][0]['cash_fake']['enable']);
    }

    /**
     * 測試當上層假現金停用時用UserId取得假現金資訊
     */
    public function testGetCashFakeInfoByUserIdButParentCashFakeDisabled()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $cashFake = $em->find('BB\DurianBundle\Entity\CashFake', 1);
        $cashFake->disable();

        $em->flush();

        $parameters = array(
            'users'  => array(4),
            'fields' => array('cash_fake'),
        );

        $client->request('GET', '/api/users', $parameters);

        $json = $client->getResponse()->getContent();
        $ret  = json_decode($json, true);

        $this->assertEquals($ret['result'], 'ok');

        $this->assertEquals(4, $ret['ret'][0]['id']);
        $this->assertEquals('1250', $ret['ret'][0]['cash_fake']['balance']);
        $this->assertEquals('0', $ret['ret'][0]['cash_fake']['pre_sub']);
        $this->assertEquals('0', $ret['ret'][0]['cash_fake']['pre_add']);
        $this->assertEquals('CNY', $ret['ret'][0]['cash_fake']['currency']);
        $this->assertFalse($ret['ret'][0]['cash_fake']['enable']);
    }

    /**
     * 測試op, opcode 1003, 會記錄金錢流向
     */
    public function testCashFakeOpWithOpcode1003()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $sourceUser = $em->find('BB\DurianBundle\Entity\User', 7);
        $targetUser = $em->find('BB\DurianBundle\Entity\User', 8);

        $client = $this->createClient();

        $parameters = array(
            'opcode' => 1003, // 1003 transfer
            'amount' => 100,
            'memo'   => 'This is memo, lalala',
            'auto_commit' => true
        );

        $client->request('PUT', '/api/user/8/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        // 跑背景程式讓queue被消化
        $params = [
            '--entry' => true,
            '--balance' => true
        ];
        $this->runCommand('durian:sync-cash-fake', $params);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret']['entries'][0]['cash_fake_id']);
        $this->assertEquals(8, $ret['ret']['entries'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entries'][0]['currency']);
        $this->assertEquals(100, $ret['ret']['entries'][0]['amount']);
        $this->assertEquals($sourceUser->getUsername(), $ret['ret']['entries'][0]['flow']['whom']);
        $this->assertEquals(2, $ret['ret']['entries'][0]['flow']['level']);
        $this->assertEquals(0, $ret['ret']['entries'][0]['flow']['transfer_out']);
        $this->assertEquals(2, $ret['ret']['entries'][0]['cash_fake_version']);

        // 比對CashFakeEntryOperator
        // id 1002為目標使用者的明細id
        $entryOperator1 = $em->find('BB\DurianBundle\Entity\CashFakeEntryOperator', 1002);

        $this->assertEquals($sourceUser->getUsername(), $entryOperator1->getWhom());
        $this->assertEquals(2, $entryOperator1->getLevel());
        $this->assertEquals(false, $entryOperator1->getTransferOut());

        // id 1001為目標使用者上層的明細id
        $entryOperator2 = $em->find('BB\DurianBundle\Entity\CashFakeEntryOperator', 1001);
        $this->assertEquals($targetUser->getUsername(), $entryOperator2->getWhom());
        $this->assertEquals(1, $entryOperator2->getLevel());
        $this->assertEquals(true, $entryOperator2->getTransferOut());

        // 比對CashFakeEntry
        $entry1 = $em->getRepository('BB\DurianBundle\Entity\CashFakeEntry')
                     ->findOneBy(array('id' => 1002));
        $entry2 = $em->getRepository('BB\DurianBundle\Entity\CashFakeEntry')
                     ->findOneBy(array('id' => 1001));
        $this->assertEquals(100, $entry1->getAmount());
        $this->assertEquals(-100, $entry2->getAmount());

        // 比對CashFakeTransferEntry
        $transferEntry1 = $em->getRepository('BB\DurianBundle\Entity\CashFakeTransferEntry')
                             ->findOneBy(array('id' => 1002));
        $transferEntry2 = $em->getRepository('BB\DurianBundle\Entity\CashFakeTransferEntry')
                             ->findOneBy(array('id' => 1001));
        $this->assertEquals(100, $transferEntry1->getAmount());
        $this->assertEquals(-100, $transferEntry2->getAmount());
    }

    /**
     * 測試轉額度給下層但下層餘額不足
     */
    public function testCashFakeTransferButChildNotEnoughBalance()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $pRedisWallet = $this->getContainer()->get('snc_redis.wallet3');
        $redisWallet = $this->getContainer()->get('snc_redis.wallet4');

        $cashFake = $em->find('BB\DurianBundle\Entity\CashFake', 7);
        $parentCashFake = $em->find('BB\DurianBundle\Entity\CashFake', 6);

        //確認交易前上下層餘額
        $this->assertEquals(150, $cashFake->getBalance());
        $this->assertEquals(0, $cashFake->getPreSub());
        $this->assertEquals(0, $cashFake->getPreAdd());
        $this->assertEquals(150, $parentCashFake->getBalance());
        $this->assertEquals(0, $parentCashFake->getPreSub());
        $this->assertEquals(0, $parentCashFake->getPreAdd());

        $client = $this->createClient();

        $parameters = array(
            'opcode' => 1003, // 1003 transfer
            'amount' => -200,
            'memo'   => 'This is memo, lalala',
            'auto_commit' => true
        );

        $client->request('PUT', '/api/user/8/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $keyName = 'cash_fake_balance_8_156';
        $parentKeyName = 'cash_fake_balance_7_156';

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150050031, $ret['code']);
        $this->assertEquals('Not enough balance', $ret['msg']);

        //因噴例外了所以更新query並沒有推到queue內，檢查redis的上下層交易後值是否正確
        $this->assertEquals(150, $redisWallet->hget($keyName, 'balance')/10000);
        $this->assertEquals(150, $pRedisWallet->hget($parentKeyName, 'balance')/10000);

        $parameters = array(
            'target'   => 8,
            'source'   => 7,
            'amount'   => -200,
            'operator' => 'isolate'
        );

        $client->request('PUT', '/api/cash_fake/transfer', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150050031, $ret['code']);
        $this->assertEquals('Not enough balance', $ret['msg']);

        //因噴例外了所以更新query並沒有推到queue內，檢查redis的上下層交易後值是否正確
        $this->assertEquals(150, $redisWallet->hget($keyName, 'balance')/10000);
        $this->assertEquals(150, $pRedisWallet->hget($parentKeyName, 'balance')/10000);
    }

    /**
     * 測試用傳入id回傳快開額度
     */
    public function testGetCashById()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/cash_fake/1');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals(2, $ret['ret']['user_id']);
        $this->assertEquals(5000, $ret['ret']['balance']);
        $this->assertEquals(0, $ret['ret']['pre_sub']);
        $this->assertEquals(0, $ret['ret']['pre_add']);
        $this->assertEquals('CNY', $ret['ret']['currency']);
        $this->assertTrue($ret['ret']['enable']);
    }

    /**
     * 測試當上層假現金停用時取得假現金資訊
     */
    public function testGetCashByIdButParentCashDisable()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $cashFake = $em->find('BB\DurianBundle\Entity\CashFake', 1);
        $cashFake->disable();

        $em->flush();

        $client->request('GET', '/api/cash_fake/2');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals(3, $ret['ret']['user_id']);
        $this->assertEquals(2500, $ret['ret']['balance']);
        $this->assertEquals(0, $ret['ret']['pre_sub']);
        $this->assertEquals(0, $ret['ret']['pre_add']);
        $this->assertEquals('CNY', $ret['ret']['currency']);
        $this->assertFalse($ret['ret']['enable']);
    }

    /**
     * 測試入款
     */
    public function testDeposit()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BB\DurianBundle\Entity\User', 8);
        $this->assertEquals(150, $user->getCashFake()->getBalance());
        $this->assertEquals(0, $user->getCashFake()->getPreSub());
        $this->assertEquals(0, $user->getCashFake()->getPreAdd());

        $em->clear();

        $memo = '';
        for ($i = 0; $i < 100; $i++) {
            $memo .= 'a';
        }

        $parameters = array(
            'opcode' => 1001, // DEPOSIT
            'amount' => 999,
            'auto_commit' => true,
            'ref_id'    => '1234567890123456789',
            'memo'      => $memo . '012', //測試memo欄位，若超過100個字只會保留前100
            'operator'  => 'tester'
        );

        $client->request('PUT', '/api/user/8/cash_fake/op', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        //跑背景程式讓queue被消化
        $params = [
            '--entry' => true,
            '--balance' => true
        ];
        $this->runCommand('durian:sync-cash-fake', $params);

        $user = $em->find('BB\DurianBundle\Entity\User', 8);
        $cashfake = $user->getCashFake();

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($ret['ret']['cash_fake']['balance'], $cashfake->getBalance());
        $this->assertEquals($ret['ret']['cash_fake']['pre_sub'], $cashfake->getPreSub());
        $this->assertEquals($ret['ret']['cash_fake']['pre_add'], $cashfake->getPreAdd());

        $cashfakeEntry = $em->getRepository('BB\DurianBundle\Entity\CashFakeEntry')
                ->findOneBy(array('id' => $ret['ret']['entries'][0]['id']));
        $operator = $em->getRepository('BB\DurianBundle\Entity\CashFakeEntryOperator')
                ->findOneBy(array('entryId' => $ret['ret']['entries'][0]['id']));

        $this->assertEquals($user->getCashFake()->getId(), $ret['ret']['entries'][0]['cash_fake_id']);

        $this->assertEquals($parameters['amount'], $ret['ret']['entries'][0]['amount']);
        $this->assertEquals($parameters['opcode'], $ret['ret']['entries'][0]['opcode']);
        $this->assertEquals($parameters['ref_id'], $ret['ret']['entries'][0]['ref_id']);
        $this->assertEquals($parameters['operator'], $ret['ret']['entries'][0]['operator']['username']);
        $this->assertEquals($memo, $ret['ret']['entries'][0]['memo']);

        $this->assertEquals($cashfakeEntry->getAmount(), $ret['ret']['entries'][0]['amount']);
        $this->assertEquals($cashfakeEntry->getOpcode(), $ret['ret']['entries'][0]['opcode']);
        $this->assertEquals($cashfakeEntry->getRefId(), $ret['ret']['entries'][0]['ref_id']);
        $this->assertEquals($cashfakeEntry->getMemo(), $ret['ret']['entries'][0]['memo']);
        $this->assertEquals($cashfakeEntry->getBalance(), $ret['ret']['entries'][0]['balance']);
        $this->assertEquals($operator->getUsername(), $ret['ret']['entries'][0]['operator']['username']);
        $this->assertEquals($cashfakeEntry->getCashFakeVersion(), $ret['ret']['entries'][0]['cash_fake_version']);
    }

    /**
     * 測試出款
     */
    public function testWithdraw()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user   = $em->find('BB\DurianBundle\Entity\User', 8);

        $this->assertEquals(150, $user->getCashFake()->getBalance());
        $this->assertEquals(0, $user->getCashFake()->getPreSub());
        $this->assertEquals(0, $user->getCashFake()->getPreAdd());

        $em->clear();

        $parameters = array(
            'opcode' => 1002, // WITHDRAWAL
            'amount' => -100,
            'auto_commit' => true
        );

        $client->request('PUT', '/api/user/8/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);
        $at = (new \DateTime($ret['ret']['entries'][0]['created_at']))->format('YmdHis');

        //跑背景程式讓queue被消化
        $params = [
            '--entry' => true,
            '--balance' => true
        ];
        $this->runCommand('durian:sync-cash-fake', $params);

        $user   = $em->find('BB\DurianBundle\Entity\User', 8);
        $cashfake = $user->getCashFake();
        $cashfakeEntry = $em->getRepository('BB\DurianBundle\Entity\CashFakeEntry')
                ->findOneBy(array('id' => $ret['ret']['entries'][0]['id']));

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($cashfake->getId(), $ret['ret']['entries'][0]['cash_fake_id']);
        $this->assertEquals($cashfake->getUser()->getId(), $ret['ret']['entries'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entries'][0]['currency']);
        $this->assertEquals($parameters['amount'], $ret['ret']['entries'][0]['amount']);
        $this->assertEquals($parameters['opcode'], $ret['ret']['entries'][0]['opcode']);

        $this->assertEquals($cashfakeEntry->getBalance(), $ret['ret']['entries'][0]['balance']);
        $this->assertEquals($cashfakeEntry->getAmount(), $ret['ret']['entries'][0]['amount']);
        $this->assertEquals($cashfakeEntry->getOpcode(), $ret['ret']['entries'][0]['opcode']);
        $this->assertEquals($cashfakeEntry->getCashFakeVersion(), $ret['ret']['entries'][0]['cash_fake_version']);

        $this->assertEquals($cashfake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashfake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashfake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals($cashfake->getLastEntryAt(), $at);
    }

    /**
     * 測試opcode1003時直接(auto_commit = 1)轉帳
     */
    public function testTransfer()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $parent = $em->find('BB\DurianBundle\Entity\User', 7);
        $user   = $em->find('BB\DurianBundle\Entity\User', 8);

        $this->assertEquals(150, $parent->getCashFake()->getBalance());
        $this->assertEquals(0, $parent->getCashFake()->getPreSub());
        $this->assertEquals(0, $parent->getCashFake()->getPreAdd());
        $this->assertEquals(150, $user->getCashFake()->getBalance());
        $this->assertEquals(0, $user->getCashFake()->getPreSub());
        $this->assertEquals(0, $user->getCashFake()->getPreAdd());

        $em->clear();

        $memo = '';
        for ($i = 0; $i < 100; $i++) {
            $memo .= 'a';
        }

        $parameters = array(
            'opcode' => 1003, // TRANSFER
            'amount' => 100,
            'operator' => 'tester',
            'memo' => $memo . '012',
            'auto_commit' => true
        );

        $client->request('PUT', '/api/user/8/cash_fake/op', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);
        $at = (new \DateTime($ret['ret']['entries'][0]['created_at']))->format('YmdHis');

        //跑背景程式讓queue被消化
        $params = [
            '--entry' => true,
            '--balance' => true
        ];
        $this->runCommand('durian:sync-cash-fake', $params);

        $user = $em->find('BB\DurianBundle\Entity\User', 8);
        $cashfake = $user->getCashFake();
        $cashfakeEntry = $em->getRepository('BB\DurianBundle\Entity\CashFakeEntry')
                ->findOneBy(array('id' => $ret['ret']['entries'][0]['id']));
        $operator = $em->getRepository('BB\DurianBundle\Entity\CashFakeEntryOperator')
                ->findOneBy(array('entryId' => $ret['ret']['entries'][0]['id']));

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($cashfake->getId(), $ret['ret']['entries'][0]['cash_fake_id']);
        $this->assertEquals($cashfake->getUser()->getId(), $ret['ret']['entries'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entries'][0]['currency']);
        $this->assertEquals($cashfake->getBalance(), $ret['ret']['entries'][0]['balance']);
        $this->assertEquals($cashfake->getLastEntryAt(), $at);
        $this->assertEquals($cashfakeEntry->getBalance(), $ret['ret']['entries'][0]['balance']);
        $this->assertEquals($cashfakeEntry->getAmount(), $ret['ret']['entries'][0]['amount']);
        $this->assertEquals($cashfakeEntry->getOpcode(), $ret['ret']['entries'][0]['opcode']);
        $this->assertEquals($memo, $cashfakeEntry->getMemo());
        $this->assertEquals($memo, $ret['ret']['entries'][0]['memo']);
        $this->assertEquals($operator->getUsername(), $ret['ret']['entries'][0]['operator']['username']);
        $this->assertEquals($cashfakeEntry->getCashFakeVersion(), $ret['ret']['entries'][0]['cash_fake_version']);

        $parentCashFake = $em->find('BBDurianBundle:CashFake', 6);
        $this->assertEquals($parentCashFake->getLastEntryAt(), $at);
    }

    /**
     * 測試opcode1003時，以交易機制(autocommit = 0)進行轉帳
     */
    public function testTransferByTransaction()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default');
        $pRedisWallet = $this->getContainer()->get('snc_redis.wallet3');
        $redisWallet = $this->getContainer()->get('snc_redis.wallet4');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 8);
        $parent = $em->find('BBDurianBundle:User', 7);

        //設定上一筆交易時間
        $preAt = 20100805153944;
        $parent->getCashFake()->setLastEntryAt($preAt);
        $user->getCashFake()->setLastEntryAt($preAt);
        $em->flush();

        $this->assertEquals(150, $user->getCashFake()->getBalance());
        $this->assertEquals(0, $user->getCashFake()->getPreSub());
        $this->assertEquals(0, $user->getCashFake()->getPreAdd());

        $this->assertEquals(150, $parent->getCashFake()->getBalance());
        $this->assertEquals(0, $parent->getCashFake()->getPreSub());
        $this->assertEquals(0, $parent->getCashFake()->getPreAdd());

        $em->clear();

        $memo = '';
        for ($i = 0; $i < 100; $i++) {
            $memo .= 'a';
        }

        $parameters = [
            'opcode' => 1003, // TRANSFER
            'amount' => 100,
            'operator' => 'tester',
            'memo' => $memo . '012',
            'auto_commit' => false
        ];

        $client->request('PUT', '/api/user/8/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);
        $at = (new \DateTime($ret['ret']['entries'][0]['created_at']))->format('YmdHis');

        $key = 'cash_fake_balance_8_156';
        $parentKey = 'cash_fake_balance_7_156';

        $this->assertEquals('ok', $ret['result']);
        $balance = $redisWallet->hget($key, 'balance')/10000;
        $this->assertEquals($balance, $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($parameters['amount'], $redisWallet->hget($key, 'pre_add')/10000);
        $this->assertEquals($parameters['amount'], $pRedisWallet->hget($parentKey, 'pre_sub')/10000);

        $this->assertEquals(2, $redis->llen('cash_fake_balance_queue'));

        $userRepo = $em->getRepository('BBDurianBundle:User');

        //檢查轉帳明細
        $this->assertEquals($user->getCashFake()->getId(), $ret['ret']['entries'][0]['cash_fake_id']);
        $this->assertEquals($user->getId(), $ret['ret']['entries'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entries'][0]['currency']);
        $this->assertEquals($parameters['amount'], $ret['ret']['entries'][0]['amount']);
        $this->assertEquals($parameters['opcode'], $ret['ret']['entries'][0]['opcode']);
        $this->assertEquals($memo, $ret['ret']['entries'][0]['memo']);
        $this->assertEquals($parameters['operator'], $ret['ret']['entries'][0]['operator']['username']);
        $this->assertEquals($parent->getUsername(), $ret['ret']['entries'][0]['flow']['whom']);
        $this->assertEquals($userRepo->getLevel($parent), $ret['ret']['entries'][0]['flow']['level']);
        $this->assertEquals(false, $ret['ret']['entries'][0]['flow']['transfer_out']);


        //檢查上層轉帳明細
        $this->assertEquals($user->getParent()->getCashFake()->getId(), $ret['ret']['entries'][1]['cash_fake_id']);
        $this->assertEquals($user->getParent()->getId(), $ret['ret']['entries'][1]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entries'][1]['currency']);
        $this->assertEquals($parameters['amount']*-1, $ret['ret']['entries'][1]['amount']);
        $this->assertEquals($parameters['opcode'], $ret['ret']['entries'][1]['opcode']);
        $this->assertEquals($memo, $ret['ret']['entries'][1]['memo']);
        $this->assertEquals($parameters['operator'], $ret['ret']['entries'][1]['operator']['username']);
        $this->assertEquals($user->getUsername(), $ret['ret']['entries'][1]['flow']['whom']);
        $this->assertEquals($userRepo->getLevel($user), $ret['ret']['entries'][1]['flow']['level']);
        $this->assertEquals(true, $ret['ret']['entries'][1]['flow']['transfer_out']);

        //跑背景程式讓queue被消化
        $params = [
            '--entry' => true,
            '--balance' => true
        ];
        $this->runCommand('durian:sync-cash-fake', $params);

        $parentCashFake = $em->find('BBDurianBundle:CashFake', 6);
        $this->assertEquals($parentCashFake->getLastEntryAt(), $preAt);
        $cashFake = $em->find('BBDurianBundle:CashFake', 7);
        $this->assertEquals($cashFake->getLastEntryAt(), $preAt);
        $em->clear();

        // 取得更新資料庫後新的餘額
        $user   = $em->find('BBDurianBundle:User', 8);
        $parent = $em->find('BBDurianBundle:User', 7);

        $this->assertEquals(150, $user->getCashFake()->getBalance());
        $this->assertEquals(0, $user->getCashFake()->getPreSub());
        $this->assertEquals(100, $user->getCashFake()->getPreAdd());

        $this->assertEquals(150, $parent->getCashFake()->getBalance());
        $this->assertEquals(100, $parent->getCashFake()->getPreSub());
        $this->assertEquals(0, $parent->getCashFake()->getPreAdd());

        // 檢查cashfake trans
        $trans = $em->getRepository('BBDurianBundle:CashFakeTrans')
            ->findBy(['userId' => $user->getId()], ['id' => 'desc']);

        $this->assertEquals(8, $trans[0]->getUserId());
        $this->assertEquals($parameters['opcode'], $trans[0]->getOpcode());
        $this->assertEquals($parameters['amount'], $trans[0]->getAmount());
        $this->assertEquals($memo, $trans[0]->getMemo());
        $this->assertEquals(false, $trans[0]->isChecked());

        // 檢查上層cashfake trans
        $trans = $em->getRepository('BBDurianBundle:CashFakeTrans')
            ->findBy(['userId' => $parent->getId()], ['id' => 'desc']);

        $this->assertEquals(7, $trans[0]->getUserId());
        $this->assertEquals($parameters['opcode'], $trans[0]->getOpcode());
        $this->assertEquals($parameters['amount']*-1, $trans[0]->getAmount());
        $this->assertEquals($memo, $trans[0]->getMemo());
        $this->assertEquals(false, $trans[0]->isChecked());

        $em->clear();

        // commit
        $entryId = $ret['ret']['entries'][0]['id'];
        $client->request('PUT', '/api/cash_fake/transaction/' . $entryId . '/commit');
        $json = $client->getResponse()->getContent();
        $ret0 = json_decode($json, true);

        $balance = $redisWallet->hget($key, 'balance')/10000 ;
        $this->assertEquals($balance, $ret0['ret']['entry']['balance']);

        $parentEntryId = $ret['ret']['entries'][1]['id'];
        $client->request('PUT', '/api/cash_fake/transaction/' . $parentEntryId . '/commit');
        $json = $client->getResponse()->getContent();
        $ret1 = json_decode($json, true);

        $redis = $this->getContainer()->get('snc_redis.total_balance');
        $normalTotalBalance = $redis->hget('cash_fake_total_balance_2_156', 'normal')/10000;
        $this->assertEquals(250, $normalTotalBalance);

        $parentBalance = $pRedisWallet->hget($parentKey, 'balance')/10000;
        $this->assertEquals($parentBalance, $ret1['ret']['entry']['balance']);

        // commit後跑背景程式讓queue被消化
        $params = [
            '--entry' => true,
            '--balance' => true
        ];
        $this->runCommand('durian:sync-cash-fake', $params);

        // 取得更新資料庫後新的餘額
        $user   = $em->find('BBDurianBundle:User', 8);
        $parent = $em->find('BBDurianBundle:User', 7);

        $balance = $user->getCashfake()->getBalance();
        $parentBalance = $parent->getCashfake()->getBalance();

        // 檢查cashfake entry
        $entries = $em->getRepository('BBDurianBundle:CashFakeEntry')
            ->findBy(['cashFakeId' => $user->getCashFake()->getId()], ['id' => 'desc']);

        $this->assertEquals(8, $entries[0]->getUserId());
        $this->assertEquals(156, $entries[0]->getCurrency());
        $this->assertEquals($parameters['opcode'], $entries[0]->getOpcode());
        $this->assertEquals($parameters['amount'], $entries[0]->getAmount());
        $this->assertEquals($memo, $entries[0]->getMemo());
        $this->assertEquals(250, $entries[0]->getBalance());
        $this->assertEquals(250, $balance);

        // 檢查上層cashfake entry
        $entries = $em->getRepository('BBDurianBundle:CashFakeEntry')
            ->findBy(['cashFakeId' => $parent->getCashFake()->getId()], ['id' => 'desc']);

        $this->assertEquals(7, $entries[0]->getUserId());
        $this->assertEquals(156, $entries[0]->getCurrency());
        $this->assertEquals($parameters['opcode'], $entries[0]->getOpcode());
        $this->assertEquals($parameters['amount']*-1, $entries[0]->getAmount());
        $this->assertEquals($memo, $entries[0]->getMemo());
        $this->assertEquals(50, $entries[0]->getBalance());
        $this->assertEquals(50, $parentBalance);

        // 檢查cashfake transfer entry
        $transferEntry = $em->getRepository('BBDurianBundle:CashFakeTransferEntry')
            ->findOneById($entryId);

        $this->assertEquals(8, $transferEntry->getUserId());
        $this->assertEquals(156, $transferEntry->getCurrency());
        $this->assertEquals($parameters['opcode'], $transferEntry->getOpcode());
        $this->assertEquals($parameters['amount'], $transferEntry->getAmount());
        $this->assertEquals($memo, $transferEntry->getMemo());
        $this->assertEquals(250, $transferEntry->getBalance());
        $this->assertEquals(250, $balance);

        // 檢查上層cashfake transfer entry
        $transferEntry = $em->getRepository('BBDurianBundle:CashFakeTransferEntry')
            ->findOneById($parentEntryId);

        $this->assertEquals(7, $transferEntry->getUserId());
        $this->assertEquals(156, $transferEntry->getCurrency());
        $this->assertEquals($parameters['opcode'], $transferEntry->getOpcode());
        $this->assertEquals($parameters['amount']*-1, $transferEntry->getAmount());
        $this->assertEquals($memo, $transferEntry->getMemo());
        $this->assertEquals(50, $transferEntry->getBalance());
        $this->assertEquals(50, $parentBalance);

        // 檢查cashfake trans
        $trans = $em->getRepository('BBDurianBundle:CashFakeTrans')
            ->findBy(['userId' => $user->getId()]);

        $this->assertEquals(8, $trans[0]->getUserId());
        $this->assertEquals($parameters['opcode'], $trans[0]->getOpcode());
        $this->assertEquals($parameters['amount'], $trans[0]->getAmount());
        $this->assertEquals($memo, $trans[0]->getMemo());
        $this->assertEquals(true, $trans[0]->isChecked());

        // 檢查上層cashfake trans
        $trans = $em->getRepository('BBDurianBundle:CashFakeTrans')
            ->findBy(['userId' => $parent->getId()]);

        $this->assertEquals(7, $trans[0]->getUserId());
        $this->assertEquals($parameters['opcode'], $trans[0]->getOpcode());
        $this->assertEquals($parameters['amount']*-1, $trans[0]->getAmount());
        $this->assertEquals($memo, $trans[0]->getMemo());
        $this->assertEquals(true, $trans[0]->isChecked());

        // 檢查cashfake entry operator
        $operator = $em->find('BBDurianBundle:CashFakeEntryOperator', $entryId);

        $this->assertEquals($parameters['operator'], $operator->getUsername());
        $this->assertEquals(false, $operator->getTransferOut());
        $this->assertEquals($parent->getUsername(), $operator->getWhom());
        $this->assertEquals($userRepo->getLevel($parent), $operator->getLevel());

        // 檢查上層cashfake entry operator
        $operator = $em->find('BBDurianBundle:CashFakeEntryOperator', $parentEntryId);

        $this->assertEquals($parameters['operator'], $operator->getUsername());
        $this->assertEquals(true, $operator->getTransferOut());
        $this->assertEquals($user->getUsername(), $operator->getWhom());
        $this->assertEquals($userRepo->getLevel($user), $operator->getLevel());

        $parentCashFake = $em->find('BBDurianBundle:CashFake', 6);
        $this->assertEquals($parentCashFake->getLastEntryAt(), $at);
        $cashFake = $em->find('BBDurianBundle:CashFake', 7);
        $this->assertEquals($cashFake->getLastEntryAt(), $at);
    }

    /**
     * 測試轉帳交易時，以交易機制(auto_commit = 0)進行，但上層餘額不足
     */
    public function testTransferByTransactionButParentNotEnoughBalance()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $pRedisWallet = $this->getContainer()->get('snc_redis.wallet3');
        $redisWallet = $this->getContainer()->get('snc_redis.wallet4');
        $client = $this->createClient();

        $parent = $em->find('BBDurianBundle:User', 7);
        $user = $em->find('BBDurianBundle:User', 8);

        $this->assertEquals(150, $parent->getCashFake()->getBalance());
        $this->assertEquals(0, $parent->getCashFake()->getPreSub());
        $this->assertEquals(0, $parent->getCashFake()->getPreAdd());
        $this->assertEquals(150, $user->getCashFake()->getBalance());
        $this->assertEquals(0, $user->getCashFake()->getPreSub());
        $this->assertEquals(0, $user->getCashFake()->getPreAdd());

        $parameters = [
            'opcode' => 1003,
            'amount' => 40000000,
            'operator' => 'tester',
            'memo' => '0',
            'auto_commit' => false
        ];

        $client->request('PUT', '/api/user/8/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150050031, $ret['code']);
        $this->assertEquals('Not enough balance', $ret['msg']);

        $parentKeyName = 'cash_fake_balance_7_156';
        $keyName = 'cash_fake_balance_8_156';

        $this->assertEquals(150, $pRedisWallet->hget($parentKeyName, 'balance') / 10000);
        $this->assertEquals(0, $pRedisWallet->hget($parentKeyName, 'pre_sub') / 10000);
        $this->assertEquals(0, $pRedisWallet->hget($parentKeyName, 'pre_add') / 10000);
        $this->assertEquals(150, $redisWallet->hget($keyName, 'balance') / 10000);
        $this->assertEquals(0, $redisWallet->hget($keyName, 'pre_sub') / 10000);
        $this->assertEquals(0, $redisWallet->hget($keyName, 'pre_add') / 10000);
    }

    /**
     * 測試轉帳交易時，以交易機制(auto_commit = 0)進行，但餘額不足
     */
    public function testTransferByTransactionButNotEnoughBalance()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $pRedisWallet = $this->getContainer()->get('snc_redis.wallet3');
        $redisWallet = $this->getContainer()->get('snc_redis.wallet4');
        $client = $this->createClient();

        $parent = $em->find('BBDurianBundle:User', 7);
        $user = $em->find('BBDurianBundle:User', 8);

        $this->assertEquals(150, $parent->getCashFake()->getBalance());
        $this->assertEquals(0, $parent->getCashFake()->getPreSub());
        $this->assertEquals(0, $parent->getCashFake()->getPreAdd());
        $this->assertEquals(150, $user->getCashFake()->getBalance());
        $this->assertEquals(0, $user->getCashFake()->getPreSub());
        $this->assertEquals(0, $user->getCashFake()->getPreAdd());

        $parameters = [
            'opcode' => 1003,
            'amount' => -40000000,
            'operator' => 'tester',
            'memo' => '0',
            'auto_commit' => false
        ];

        $client->request('PUT', '/api/user/8/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150050031, $ret['code']);
        $this->assertEquals('Not enough balance', $ret['msg']);

        $parentKeyName = 'cash_fake_balance_7_156';
        $keyName = 'cash_fake_balance_8_156';

        $this->assertEquals(150, $pRedisWallet->hget($parentKeyName, 'balance') / 10000);
        $this->assertEquals(0, $pRedisWallet->hget($parentKeyName, 'pre_sub') / 10000);
        $this->assertEquals(0, $pRedisWallet->hget($parentKeyName, 'pre_add') / 10000);
        $this->assertEquals(150, $redisWallet->hget($keyName, 'balance') / 10000);
        $this->assertEquals(0, $redisWallet->hget($keyName, 'pre_sub') / 10000);
        $this->assertEquals(0, $redisWallet->hget($keyName, 'pre_add') / 10000);
    }

    /**
     * 測試轉帳時ref_id若是空字串會送0到queue並回傳空字串
     */
    public function testTransferWithEmptyRefId()
    {
        $client = $this->createClient();
        $redis = $this->getContainer()->get('snc_redis.default');

        // 帶force及不帶source參數
        $parameters = [
            'source' => null,
            'target' => 4,
            'amount' => 20,
            'force'  => 1
        ];

        $client->request('PUT', '/api/cash_fake/transfer', $parameters);

        $queue = json_decode($redis->rpop('cash_fake_entry_queue'), true);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertSame(0, $queue['ref_id']);
        $this->assertEquals('', $ret['ret']['target_entries'][0]['ref_id']);

        // 帶force及帶source參數
        $parameters = [
            'source' => 2,
            'target' => 4,
            'amount' => 20,
            'force'  => 1
        ];

        $client->request('PUT', '/api/cash_fake/transfer', $parameters);

        $queue = json_decode($redis->rpop('cash_fake_entry_queue'), true);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertSame(0, $queue['ref_id']);
        $this->assertEquals('', $ret['ret']['entries']['ref_id']);
    }

    /**
     * 測試CashFake op時ref_id帶空字串會送0到queue並回傳空字串
     */
    public function testCashFakeOpWithEmptyRefId()
    {
        $client = $this->createClient();
        $redis = $this->getContainer()->get('snc_redis.default');

        $parameters = [
            'opcode'      => 1001,
            'amount'      => 100,
            'ref_id'      => '',
            'auto_commit' => false
        ];

        $client->request('PUT', '/api/user/8/cash_fake/op', $parameters);

        $queue = json_decode($redis->rpop('cash_fake_trans_queue'), true);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertSame(0, $queue['ref_id']);
        $this->assertEquals('', $ret['ret']['entries'][0]['ref_id']);
    }

    /**
     * 測試cashFake op無假現金
     */
    public function testCashFakeOpButCashFakeNotExist()
    {
        $client = $this->createClient();

        $parameters = [
            'opcode' => '1003',
            'amount' => '400',
            'ref_id' => '1234567890123456789',
            'auto_commit' => true
        ];

        $client->request('PUT', '/api/user/10/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150050001, $ret['code']);
        $this->assertEquals('No cashFake found', $ret['msg']);
    }

    /**
     * 測試cashFake op上層無假現金
     */
    public function testCashFakeOpButParentHasNoCashFake()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parent = $em->find('BBDurianBundle:User', 10);

        $user = new User();
        $user->setId(13);
        $user->setUsername('lala');
        $user->setParent($parent);
        $user->setAlias('lala');
        $user->setPassword('lalalala');
        $user->setDomain(9);
        $user->setRole(1);

        $cashFake = new CashFake($user, 901); // TWD

        $em->persist($user);
        $em->persist($cashFake);

        $em->flush();

        $parameters = [
            'opcode' => '1003',
            'amount' => '400',
            'ref_id' => '1234567890123456789',
            'auto_commit' => true
        ];

        $client->request('PUT', '/api/user/13/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150050006, $ret['code']);
        $this->assertEquals('No parent cashFake found', $ret['msg']);
    }

    /**
     * 測試cashFake op帶入autoCommit為1, opcode為1003
     */
    public function testCashFakeOpWithAutoCommit1AndOpcode1003NoParent()
    {
        $client = $this->createClient();

        $parameters = [
            'opcode' => '1003',
            'amount' => '400',
            'ref_id' => '1234567890123456789',
            'auto_commit' => true
        ];

        $client->request('PUT', '/api/user/2/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150050032, $ret['code']);
        $this->assertEquals('No parent found', $ret['msg']);
    }

    /**
     * 測試cashFake op帶入autoCommit為0, opcode為1003
     */
    public function testCashFakeOpWithAutoCommit0AndOpcode1003NoParent()
    {
        $client = $this->createClient();

        $parameters = [
            'opcode' => '1003',
            'amount' => '400',
            'ref_id' => '1234567890123456789',
            'auto_commit' => false
        ];

        $client->request('PUT', '/api/user/2/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150050032, $ret['code']);
        $this->assertEquals('No parent found', $ret['msg']);
    }

    /**
     * 測試cashfake op可以強制扣款
     */
    public function testCashFakeOpWithForce()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // auto_commit = 1, opcode = 1003, 可以允許金額為0
        $parameters = [
            'opcode' => 1003,
            'amount' => 0,
            'ref_id' => '123456',
            'auto_commit' => true,
            'force' => true
        ];

        $client->request('PUT', '/api/user/8/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret']['entries'][0]['cash_fake_id']);
        $this->assertEquals(1003, $ret['ret']['entries'][0]['opcode']);
        $this->assertEquals(0, $ret['ret']['entries'][0]['amount']);
        $this->assertEquals(150, $ret['ret']['entries'][0]['balance']);
        $this->assertEquals(2, $ret['ret']['entries'][0]['cash_fake_version']);
        $this->assertEquals(7, $ret['ret']['cash_fake']['id']);
        $this->assertEquals(8, $ret['ret']['cash_fake']['user_id']);
        $this->assertEquals(150, $ret['ret']['cash_fake']['balance']);
        $this->assertEquals(0, $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals(0, $ret['ret']['cash_fake']['pre_add']);

        // auto_commit = 1, opcode = 1003, 可以允許扣到負數
        $parameters = [
            'opcode' => 1003,
            'amount' => -400,
            'ref_id' => '123456',
            'auto_commit' => true,
            'force' => true
        ];

        $client->request('PUT', '/api/user/8/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret']['entries'][0]['cash_fake_id']);
        $this->assertEquals(1003, $ret['ret']['entries'][0]['opcode']);
        $this->assertEquals(-400, $ret['ret']['entries'][0]['amount']);
        $this->assertEquals(-250, $ret['ret']['entries'][0]['balance']);
        $this->assertEquals(3, $ret['ret']['entries'][0]['cash_fake_version']);
        $this->assertEquals(7, $ret['ret']['cash_fake']['id']);
        $this->assertEquals(8, $ret['ret']['cash_fake']['user_id']);
        $this->assertEquals(-250, $ret['ret']['cash_fake']['balance']);
        $this->assertEquals(0, $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals(0, $ret['ret']['cash_fake']['pre_add']);

        // auto_commit = 1, opcode != 1003, 可以允許使用者停權
        $user = $em->find('BBDurianBundle:User', 4);
        $user->setBankrupt(true);
        $em->flush();

        $parameters = [
            'opcode' => 40000,
            'amount' => -10,
            'ref_id' => '123456',
            'auto_commit' => true,
            'force' => true
        ];

        $client->request('PUT', '/api/user/4/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(3, $ret['ret']['entries'][0]['cash_fake_id']);
        $this->assertEquals(40000, $ret['ret']['entries'][0]['opcode']);
        $this->assertEquals(-10, $ret['ret']['entries'][0]['amount']);
        $this->assertEquals(1240, $ret['ret']['entries'][0]['balance']);
        $this->assertEquals(2, $ret['ret']['entries'][0]['cash_fake_version']);
        $this->assertEquals(3, $ret['ret']['cash_fake']['id']);
        $this->assertEquals(4, $ret['ret']['cash_fake']['user_id']);
        $this->assertEquals(1240, $ret['ret']['cash_fake']['balance']);
        $this->assertEquals(0, $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals(0, $ret['ret']['cash_fake']['pre_add']);

        // auto_commit = 1, opcode != 1003, 可以允許餘額扣到負數
        $user->setBankrupt(false);
        $em->flush();

        $parameters = [
            'opcode' => 1001,
            'amount' => -1300,
            'ref_id' => '123456',
            'auto_commit' => true,
            'force' => true
        ];

        $client->request('PUT', '/api/user/4/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(3, $ret['ret']['entries'][0]['cash_fake_id']);
        $this->assertEquals(1001, $ret['ret']['entries'][0]['opcode']);
        $this->assertEquals(-1300, $ret['ret']['entries'][0]['amount']);
        $this->assertEquals(-60, $ret['ret']['entries'][0]['balance']);
        $this->assertEquals(3, $ret['ret']['entries'][0]['cash_fake_version']);
        $this->assertEquals(3, $ret['ret']['cash_fake']['id']);
        $this->assertEquals(4, $ret['ret']['cash_fake']['user_id']);
        $this->assertEquals(-60, $ret['ret']['cash_fake']['balance']);
        $this->assertEquals(0, $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals(0, $ret['ret']['cash_fake']['pre_add']);

        // auto_commit = 0, opcode = 1003, 允許餘額扣到負數
        $parameters = [
            'opcode' => 1003,
            'amount' => -3000,
            'ref_id' => '123456',
            'auto_commit' => false,
            'force' => true
        ];

        $client->request('PUT', '/api/user/3/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret']['entries'][0]['cash_fake_id']);
        $this->assertEquals(1003, $ret['ret']['entries'][0]['opcode']);
        $this->assertEquals(-3000, $ret['ret']['entries'][0]['amount']);

        $this->assertEquals(1, $ret['ret']['entries'][1]['cash_fake_id']);
        $this->assertEquals(1003, $ret['ret']['entries'][1]['opcode']);
        $this->assertEquals(3000, $ret['ret']['entries'][1]['amount']);

        $this->assertEquals(2, $ret['ret']['cash_fake']['id']);
        $this->assertEquals(3, $ret['ret']['cash_fake']['user_id']);
        $this->assertEquals(2500, $ret['ret']['cash_fake']['balance']);
        $this->assertEquals(3000, $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals(0, $ret['ret']['cash_fake']['pre_add']);

        // auto_commit = 0, opcode != 1003, 允許使用者停權
        $user = $em->find('BBDurianBundle:User', 6);
        $user->setBankrupt(true);
        $em->flush();

        $parameters = [
            'opcode' => 40000,
            'amount' => -10,
            'ref_id' => '123456',
            'auto_commit' => false,
            'force' => true
        ];

        $client->request('PUT', '/api/user/6/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(5, $ret['ret']['entries'][0]['cash_fake_id']);
        $this->assertEquals(6, $ret['ret']['entries'][0]['user_id']);
        $this->assertEquals(40000, $ret['ret']['entries'][0]['opcode']);
        $this->assertEquals(-10, $ret['ret']['entries'][0]['amount']);

        $this->assertEquals(5, $ret['ret']['cash_fake']['id']);
        $this->assertEquals(6, $ret['ret']['cash_fake']['user_id']);
        $this->assertEquals(325, $ret['ret']['cash_fake']['balance']);
        $this->assertEquals(10, $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals(0, $ret['ret']['cash_fake']['pre_add']);

        // auto_commit = 0, opcode != 1003的情況, 允許餘額扣到負數
        $user->setBankrupt(false);
        $em->flush();

        $parameters = [
            'opcode' => 1001,
            'amount' => -3000,
            'ref_id' => '123456',
            'auto_commit' => false,
            'force' => true
        ];

        $client->request('PUT', '/api/user/6/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(5, $ret['ret']['entries'][0]['cash_fake_id']);
        $this->assertEquals(6, $ret['ret']['entries'][0]['user_id']);
        $this->assertEquals(1001, $ret['ret']['entries'][0]['opcode']);
        $this->assertEquals(-3000, $ret['ret']['entries'][0]['amount']);

        $this->assertEquals(5, $ret['ret']['cash_fake']['id']);
        $this->assertEquals(6, $ret['ret']['cash_fake']['user_id']);
        $this->assertEquals(325, $ret['ret']['cash_fake']['balance']);
        $this->assertEquals(3010, $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals(0, $ret['ret']['cash_fake']['pre_add']);
    }

    /**
     * 測試當快開額度被停用時交易
     */
    public function testOpCashFakeButDisable()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $cashFake = $em->find('BB\DurianBundle\Entity\CashFake', 7);
        $cashFake->disable();

        $em->flush();

        $parameters = array(
            'opcode' => '10002',
            'amount' => '400',
            'auto_commit' => true
        );

        $client->request('PUT', '/api/user/8/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150050007 , $output['code']);
        $this->assertEquals('CashFake is disabled', $output['msg']);
    }

    /**
     * 測試API業主沙巴轉帳，使用force_copy參數發生RefId重覆
     */
    public function testApiOwnerCashFakeTransferOutDuplicateRefidWithForceCopy()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redisWallet = $this->getContainer()->get('snc_redis.wallet1');
        $user = $em->find('BBDurianBundle:User', 8);

        $refIdKey = "duplicate_refid_{$user->getDomain()}";
        $redisWallet->zadd($refIdKey, time() + 604800, 1001);

        //測試SP人工
        $parameters = [
            'vendor' => 'SABAH',
            'amount' => 100,
            'memo' => '',
            'ref_id' => '',
            'operator' => 'tester',
            'api_owner' => true,
            'force_copy' => true
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150050051, $output['code']);
        $this->assertEquals('Duplicate ref id', $output['msg']);
    }

    /**
     * 測試API業主沙巴轉帳，使用者已被停權
     */
    public function testApiOwnerCashFakeTransferOutWithBankruptedUser()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setBankrupt(true);
        $em->flush();

        //測試SP人工存入
        $parameters = [
            'vendor' => 'SABAH',
            'amount' => 100,
            'memo' => '',
            'ref_id' => '',
            'operator' => 'tester',
            'api_owner' => true,
            'force_copy' => true
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150050052, $output['code']);
        $this->assertEquals('User is bankrupt', $output['msg']);
    }

    /**
     * 測試API業主沙巴轉帳，重覆RefId，重覆廳主及不重覆廳主
     */
    public function testApiOwnerCashFakeTransferOutWithDuplicateRefId()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redisWallet = $this->getContainer()->get('snc_redis.wallet1');
        $user = $em->find('BBDurianBundle:User', 8);

        $refIdKey = "duplicate_refid_{$user->getDomain()}";
        $redisWallet->zadd($refIdKey, time() + 604800, 1001);

        //測試SP人工提出
        $parameters = [
            'vendor' => 'SABAH',
            'amount' => -100,
            'ref_id' => '',
            'operator' => 'tester',
            'api_owner' => true,
            'force_copy' => true
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150050053, $output['code']);
        $this->assertEquals('Duplicate ref id', $output['msg']);
    }

    /**
     * 測試opcode帶1042的快開額度直接交易，重覆RefId，重覆廳主及不重覆廳主
     */
    public function testdirectCashFakeOpWithOpcode1042And1043()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redisWallet = $this->getContainer()->get('snc_redis.wallet1');
        $user = $em->find('BBDurianBundle:User', 8);
        $refId = 5886695;

        //測試餘額不足時refId不會被記錄起來
        $parameters = array(
            'opcode' => '1043',
            'amount' => '-40000000',
            'ref_id' => $refId,
            'auto_commit' => true
        );

        $client->request('PUT', '/api/user/8/cash_fake/op', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150050031, $output['code']);
        $key = 'duplicate_refid_'.$user->getDomain();

        $this->assertNull($redisWallet->zrank($key, 5886695));

        //測試餘額夠，該refId會被記錄起來
        $parameters = array(
            'opcode' => '1042',
            'amount' => '400',
            'ref_id' => $refId,
            'auto_commit' => true
        );

        $client->request('PUT', '/api/user/8/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertGreaterThan(0, $redisWallet->zscore($key, 5886695));

        //同樣的參數同一個廳的User再跑一次就會噴150050008
        $client->request('PUT', '/api/user/7/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Duplicate ref id', $output['msg']);
        $this->assertEquals('150050008', $output['code']);

        //同樣的參數但不同廳，再跑一次就會成功
        $client->request('PUT', '/api/user/9/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
    }

    /**
     * 測試當上層CashFake停用時交易
     */
    public function testOpCashFakeButParentCashFakeDisable()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $cashFake = $em->find('BB\DurianBundle\Entity\CashFake', 6);
        $cashFake->disable();

        $em->flush();

        $parameters = array(
            'opcode' => '10002',
            'amount' => '400',
            'auto_commit' => true
        );

        $client->request('PUT', '/api/user/8/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150050007 , $output['code']);
        $this->assertEquals('CashFake is disabled', $output['msg']);
    }

    /**
     * 測試當上上層CashFake停用時交易
     */
    public function testOpCashFakeButGrandParentCashFakeDisable()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $cashFake = $em->find('BB\DurianBundle\Entity\CashFake', 5);
        $cashFake->disable();

        $em->flush();

        $parameters = array(
            'opcode' => '10002',
            'amount' => '400',
            'auto_commit' => true
        );

        $client->request('PUT', '/api/user/8/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150050007 , $output['code']);
        $this->assertEquals('CashFake is disabled', $output['msg']);
    }

    /**
     * 測試當上上上層CashFake停用時交易
     */
    public function testOpCashFakeButGrandGrandParentCashFakeDisable()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $cashFake = $em->find('BB\DurianBundle\Entity\CashFake', 4);
        $cashFake->disable();

        $em->flush();

        $parameters = array(
            'opcode' => '10002',
            'amount' => '400',
            'auto_commit' => true
        );

        $client->request('PUT', '/api/user/8/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150050007 , $output['code']);
        $this->assertEquals('CashFake is disabled', $output['msg']);
    }

    /**
     * 測試當使用者被停權時做快開額度交易
     */
    public function testOpCashFakeButUserIsBankrupt()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BBDurianBundle:User', 8);
        $user->setBankrupt(1);

        $em->flush();

        $parameters = [
            'opcode' => 10002,
            'amount' => 400,
            'auto_commit' => true
        ];

        $client->request('PUT', '/api/user/8/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150050036 , $output['code']);
        $this->assertEquals('User is bankrupt', $output['msg']);
    }

    /**
     * 測試OpCashFake memo非UTF8
     */
    public function testOpCashFakeMemoNotUtf8()
    {
        $client = $this->createClient();

        $parameters = array(
            'opcode' => '10002',
            'amount' => '400',
            'memo' => mb_convert_encoding('e龜龍鱉', 'GB2312', 'UTF-8'),
            'auto_commit' => true
        );

        $client->request('PUT', '/api/user/8/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150610002, $output['code']);
        $this->assertEquals('String must use utf-8 encoding', $output['msg']);
    }

    /**
     * 測試快開額度下注當force_copy = true時，refId為明細編號
     */
    public function testTransactionCommiWithForceCopyt()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default');
        $redisWallet = $this->getContainer()->get('snc_redis.wallet1');
        $redisWallet4 = $this->getContainer()->get('snc_redis.wallet4');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 8);

        $this->assertEquals(150, $user->getCashFake()->getBalance());
        $this->assertEquals(0, $user->getCashFake()->getPreSub());
        $this->assertEquals(0, $user->getCashFake()->getPreAdd());

        //設定上一筆交易時間
        $cashFake = $user->getCashFake();
        $preAt = 20001112101010;
        $cashFake->setLastEntryAt($preAt);
        $em->flush();
        $em->clear();

        $memo = '';
        for ($i = 0; $i < 100; $i++) {
            $memo .= 'a';
        }

        $parameters = [
            'opcode' => 10002, // BETTING
            'amount' => -100,
            'operator' => 'tester',
            'memo' => $memo . '012',
            'auto_commit' => false,
            'force_copy'  => true
        ];

        $client->request('PUT', '/api/user/8/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $entryId = $ret['ret']['entries'][0]['id'];
        $entryKey = 'cash_fake_trans';
        $trans = json_decode($redisWallet->hget($entryKey, $entryId), true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($trans['opcode'], $ret['ret']['entries'][0]['opcode']);
        $this->assertEquals($trans['cash_fake_id'], $ret['ret']['entries'][0]['cash_fake_id']);
        $this->assertEquals($trans['user_id'], $ret['ret']['entries'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entries'][0]['currency']);
        $this->assertEquals($trans['amount'], $ret['ret']['entries'][0]['amount']);
        $this->assertEquals($trans['memo'], $ret['ret']['entries'][0]['memo']);
        $this->assertEquals($ret['ret']['entries'][0]['id'], $ret['ret']['entries'][0]['ref_id']);
        $this->assertEquals($trans['operator']['username'], $ret['ret']['entries'][0]['operator']['username']);
        $this->assertEquals($parameters['operator'], $ret['ret']['entries'][0]['operator']['username']);

        $key = 'cash_fake_balance_8_156';
        $balance = $redisWallet4->hget($key, 'balance')/10000;
        $this->assertEquals($balance, $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($parameters['amount']*-1, $redisWallet4->hget($key, 'pre_sub')/10000);

        $this->assertEquals(1, $redis->llen('cash_fake_balance_queue'));

        //跑背景程式讓queue被消化
        $params = [
            '--entry' => true,
            '--balance' => true
        ];
        $this->runCommand('durian:sync-cash-fake', $params);

        $cashFake = $em->find('BBDurianBundle:CashFake', 7);
        $this->assertEquals($cashFake->getLastEntryAt(), $preAt);
        $em->clear();

        $user = $em->find('BBDurianBundle:User', 8);
        $cashfake = $user->getCashFake();

        $this->assertEquals(150, $cashfake->getBalance());
        $this->assertEquals(100, $cashfake->getPreSub());
        $this->assertEquals(0, $cashfake->getPreAdd());

        // 檢查cashfake trans
        $trans = $em->getRepository('BBDurianBundle:CashFakeTrans')
            ->findBy(['userId' => $user->getId()], ['id' => 'desc']);

        $this->assertEquals(8, $trans[0]->getUserId());
        $this->assertEquals($parameters['opcode'], $trans[0]->getOpcode());
        $this->assertEquals($parameters['amount'], $trans[0]->getAmount());
        $this->assertEquals($memo, $trans[0]->getMemo());
        $this->assertEquals(false, $trans[0]->isChecked());
        $this->assertEquals($trans[0]->getId(), $trans[0]->getRefId());

        $em->clear();

        // commit
        $client->request('PUT', '/api/cash_fake/transaction/' . $entryId . '/commit');
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $at = (new \DateTime($ret['ret']['entry']['created_at']))->format('YmdHis');

        $balance = $redisWallet4->hget($key, 'balance')/10000 ;
        $this->assertEquals($balance, $ret['ret']['entry']['balance']);

        // commit後跑背景程式讓queue被消化
        $params = [
            '--entry' => true,
            '--balance' => true
        ];
        $this->runCommand('durian:sync-cash-fake', $params);

        $user = $em->find('BBDurianBundle:User', 8);
        $cashfake = $user->getCashFake();

        $this->assertEquals(50, $cashfake->getBalance());
        $this->assertEquals(0, $cashfake->getPreSub());
        $this->assertEquals(0, $cashfake->getPreAdd());

        $cashfakeEntry = $em->getRepository('BBDurianBundle:CashFakeEntry')
            ->findOneBy(['id' => $ret['ret']['entry']['id']]);
        $operator = $em->getRepository('BBDurianBundle:CashFakeEntryOperator')
            ->findOneBy(['entryId' => $ret['ret']['entry']['id']]);

        $this->assertEquals($cashfake->getBalance(), $ret['ret']['entry']['balance']);
        $this->assertEquals($cashfake->getLastEntryAt(), $at);

        $this->assertEquals($cashfakeEntry->getId(), $cashfakeEntry->getRefId());
        $this->assertEquals($cashfakeEntry->getOpcode(), $ret['ret']['entry']['opcode']);
        $this->assertEquals($cashfakeEntry->getAmount(), $ret['ret']['entry']['amount']);
        $this->assertEquals($cashfakeEntry->getMemo(), $ret['ret']['entry']['memo']);
        $this->assertEquals($ret['ret']['entry']['id'], $ret['ret']['entry']['ref_id']);
        $this->assertEquals($operator->getUsername(), $ret['ret']['entry']['operator']['username']);
        $this->assertEquals('tester', $ret['ret']['entry']['operator']['username']);

        // 檢查cashfake trans
        $trans = $em->getRepository('BBDurianBundle:CashFakeTrans')
            ->findBy(['userId' => $user->getId()], ['id' => 'desc']);

        $this->assertEquals(8, $trans[0]->getUserId());
        $this->assertEquals($parameters['opcode'], $trans[0]->getOpcode());
        $this->assertEquals($parameters['amount'], $trans[0]->getAmount());
        $this->assertEquals($memo, $trans[0]->getMemo());
        $this->assertEquals(true, $trans[0]->isChecked());
        $this->assertEquals($trans[0]->getId(), $trans[0]->getRefId());

        // 檢查cashfake entry operator
        $operator = $em->find('BBDurianBundle:CashFakeEntryOperator', $entryId);

        $this->assertEquals($parameters['operator'], $operator->getUsername());
        $this->assertEquals(null, $operator->getTransferOut());
        $this->assertEquals('', $operator->getWhom());
        $this->assertEquals(null, $operator->getLevel());

        $parameters = [
            'opcode' => 10002, // BETTING
            'amount' => -50,
            'operator' => 'tester',
            'memo' => $memo . '012',
            'auto_commit' => true,
            'force_copy'  => true
        ];

        $client->request('PUT', '/api/user/8/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($ret['ret']['entries'][0]['id'], $ret['ret']['entries'][0]['ref_id']);

        // commit後跑背景程式讓queue被消化
        $params = [
            '--entry' => true,
            '--balance' => true
        ];
        $this->runCommand('durian:sync-cash-fake', $params);

        $em->refresh($cashfake);

        $this->assertEquals(0, $cashfake->getBalance());
        $this->assertEquals(0, $cashfake->getPreSub());
        $this->assertEquals(0, $cashfake->getPreAdd());

        $cashfakeEntry = $em->getRepository('BBDurianBundle:CashFakeEntry')
            ->findOneBy(['id' => $ret['ret']['entries'][0]['id']]);

        $this->assertEquals($cashfakeEntry->getId(), $cashfakeEntry->getRefId());
        $this->assertEquals($cashfakeEntry->getOpcode(), $ret['ret']['entries'][0]['opcode']);
        $this->assertEquals($cashfakeEntry->getAmount(), $ret['ret']['entries'][0]['amount']);
        $this->assertEquals($cashfakeEntry->getMemo(), $ret['ret']['entries'][0]['memo']);
        $this->assertEquals($cashfakeEntry->getRefId(), $ret['ret']['entries'][0]['ref_id']);
        $this->assertEquals($operator->getUsername(), $ret['ret']['entries'][0]['operator']['username']);
    }

    /**
     * 測試快開額度以交易機制下注，並且commit
     */
    public function testTransactionCommit()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default');
        $redisWallet = $this->getContainer()->get('snc_redis.wallet1');
        $redisWallet4 = $this->getContainer()->get('snc_redis.wallet4');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 8);

        $this->assertEquals(150, $user->getCashFake()->getBalance());
        $this->assertEquals(0, $user->getCashFake()->getPreSub());
        $this->assertEquals(0, $user->getCashFake()->getPreAdd());

        //設定上一筆交易時間
        $cashFake = $user->getCashFake();
        $preAt = 20001112101010;
        $cashFake->setLastEntryAt($preAt);
        $em->flush();
        $em->clear();

        $memo = '';
        for ($i = 0; $i < 100; $i++) {
            $memo .= 'a';
        }

        $parameters = [
            'opcode' => 10002, // BETTING
            'amount' => -100,
            'operator' => 'tester',
            'memo' => $memo . '012',
            'auto_commit' => false
        ];

        $client->request('PUT', '/api/user/8/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $entryId = $ret['ret']['entries'][0]['id'];
        $entryKey = 'cash_fake_trans';
        $trans = json_decode($redisWallet->hget($entryKey, $entryId), true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($trans['opcode'], $ret['ret']['entries'][0]['opcode']);
        $this->assertEquals($trans['cash_fake_id'], $ret['ret']['entries'][0]['cash_fake_id']);
        $this->assertEquals($trans['user_id'], $ret['ret']['entries'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entries'][0]['currency']);
        $this->assertEquals($trans['amount'], $ret['ret']['entries'][0]['amount']);
        $this->assertEquals($trans['memo'], $ret['ret']['entries'][0]['memo']);
        $this->assertEquals('', $ret['ret']['entries'][0]['ref_id']);
        $this->assertEquals($trans['operator']['username'], $ret['ret']['entries'][0]['operator']['username']);
        $this->assertEquals($parameters['operator'], $ret['ret']['entries'][0]['operator']['username']);

        $key = 'cash_fake_balance_8_156';
        $balance = $redisWallet4->hget($key, 'balance')/10000;
        $this->assertEquals($balance, $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($parameters['amount']*-1, $redisWallet4->hget($key, 'pre_sub')/10000);

        $this->assertEquals(1, $redis->llen('cash_fake_balance_queue'));

        //跑背景程式讓queue被消化
        $params = [
            '--entry' => true,
            '--balance' => true
        ];
        $this->runCommand('durian:sync-cash-fake', $params);

        $cashFake = $em->find('BBDurianBundle:CashFake', 7);
        $this->assertEquals($cashFake->getLastEntryAt(), $preAt);
        $em->clear();

        $user = $em->find('BBDurianBundle:User', 8);
        $cashfake = $user->getCashFake();

        $this->assertEquals(150, $cashfake->getBalance());
        $this->assertEquals(100, $cashfake->getPreSub());
        $this->assertEquals(0, $cashfake->getPreAdd());

        // 檢查cashfake trans
        $trans = $em->getRepository('BBDurianBundle:CashFakeTrans')
            ->findBy(['userId' => $user->getId()], ['id' => 'desc']);

        $this->assertEquals(8, $trans[0]->getUserId());
        $this->assertEquals($parameters['opcode'], $trans[0]->getOpcode());
        $this->assertEquals($parameters['amount'], $trans[0]->getAmount());
        $this->assertEquals($memo, $trans[0]->getMemo());
        $this->assertEquals(false, $trans[0]->isChecked());

        $em->clear();

        // commit
        $client->request('PUT', '/api/cash_fake/transaction/' . $entryId . '/commit');
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);
        $at = (new \DateTime($ret['ret']['entry']['created_at']))->format('YmdHis');

        $balance = $redisWallet4->hget($key, 'balance')/10000 ;
        $this->assertEquals($balance, $ret['ret']['entry']['balance']);

        // commit後跑背景程式讓queue被消化
        $params = [
            '--entry' => true,
            '--balance' => true
        ];
        $this->runCommand('durian:sync-cash-fake', $params);

        $user = $em->find('BBDurianBundle:User', 8);
        $cashfake = $user->getCashFake();

        $this->assertEquals(50, $cashfake->getBalance());
        $this->assertEquals(0, $cashfake->getPreSub());
        $this->assertEquals(0, $cashfake->getPreAdd());

        $cashfakeEntry = $em->getRepository('BBDurianBundle:CashFakeEntry')
            ->findOneBy(['id' => $ret['ret']['entry']['id']]);
        $operator = $em->getRepository('BBDurianBundle:CashFakeEntryOperator')
            ->findOneBy(['entryId' => $ret['ret']['entry']['id']]);

        $this->assertEquals($cashfake->getBalance(), $ret['ret']['entry']['balance']);
        $this->assertEquals($cashfake->getLastEntryAt(), $at);

        $this->assertEquals($cashfakeEntry->getOpcode(), $ret['ret']['entry']['opcode']);
        $this->assertEquals($cashfakeEntry->getAmount(), $ret['ret']['entry']['amount']);
        $this->assertEquals($cashfakeEntry->getMemo(), $ret['ret']['entry']['memo']);
        $this->assertEquals('', $ret['ret']['entry']['ref_id']);
        $this->assertEquals($operator->getUsername(), $ret['ret']['entry']['operator']['username']);
        $this->assertEquals('tester', $ret['ret']['entry']['operator']['username']);

        // 檢查cashfake trans
        $trans = $em->getRepository('BBDurianBundle:CashFakeTrans')
            ->findBy(['userId' => $user->getId()], ['id' => 'desc']);

        $this->assertEquals(8, $trans[0]->getUserId());
        $this->assertEquals($parameters['opcode'], $trans[0]->getOpcode());
        $this->assertEquals($parameters['amount'], $trans[0]->getAmount());
        $this->assertEquals($memo, $trans[0]->getMemo());
        $this->assertEquals(true, $trans[0]->isChecked());

        // 檢查cashfake entry operator
        $operator = $em->find('BBDurianBundle:CashFakeEntryOperator', $entryId);

        $this->assertEquals($parameters['operator'], $operator->getUsername());
        $this->assertEquals(null, $operator->getTransferOut());
        $this->assertEquals('', $operator->getWhom());
        $this->assertEquals(null, $operator->getLevel());
    }

    /**
     * 測試確認交易時ref_id為空字串會送0到queue並回傳空字串
     */
    public function testTransactionCommitWithEmptyRefId()
    {
        $client = $this->createClient();
        $redis  = $this->getContainer()->get('snc_redis.default');

        $parameters = [
            'opcode' => 1001,
            'amount' => 100,
            'auto_commit' => false,
            'ref_id' => ''
        ];

        $client->request('PUT', '/api/user/8/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);
        $entryId = $ret['ret']['entries'][0]['id'];

        $client->request('PUT', '/api/cash_fake/transaction/'.$entryId.'/commit');

        $entryQueue = json_decode($redis->lindex('cash_fake_entry_queue', 2), true);
        $transferEntryQueue = json_decode($redis->lindex('cash_fake_entry_queue', 1), true);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals(0, $entryQueue['ref_id']);
        $this->assertEquals(0, $transferEntryQueue['ref_id']);
        $this->assertEquals('', $ret['ret']['entry']['ref_id']);
    }

    /**
     * 測試交易取消
     */
    public function testTransactionRollback()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redisWallet = $this->getContainer()->get('snc_redis.wallet1');
        $redisWallet4 = $this->getContainer()->get('snc_redis.wallet4');
        $client = $this->createClient();
        $user = $em->find('BB\DurianBundle\Entity\User', 8);

        $this->assertEquals(0, $user->getCashFake()->getPreSub());

        $em->clear();

        $parameters = array(
            'opcode' => 10002, // BETTING
            'amount' => -100,
            'operator' => 'tester',
            'auto_commit' => false
        );

        $client->request('PUT', '/api/user/8/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $entryId = $ret['ret']['entries'][0]['id'];
        $entryKey = 'cash_fake_trans';
        $trans = json_decode($redisWallet->hget($entryKey, $entryId), true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($trans['opcode'], $ret['ret']['entries'][0]['opcode']);
        $this->assertEquals($trans['cash_fake_id'], $ret['ret']['entries'][0]['cash_fake_id']);
        $this->assertEquals($trans['user_id'], $ret['ret']['entries'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entries'][0]['currency']);
        $this->assertEquals($trans['amount'], $ret['ret']['entries'][0]['amount']);
        $this->assertEquals($trans['memo'], $ret['ret']['entries'][0]['memo']);
        $this->assertEquals('', $ret['ret']['entries'][0]['ref_id']);
        $this->assertEquals($trans['operator']['username'], $ret['ret']['entries'][0]['operator']['username']);
        $this->assertEquals($parameters['operator'], $ret['ret']['entries'][0]['operator']['username']);

        $key = 'cash_fake_balance_8_156';
        $balance = $redisWallet4->hget($key, 'balance')/10000;
        $this->assertEquals($balance, $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($parameters['amount']*-1, $redisWallet4->hget($key, 'pre_sub')/10000);

        $client->request('PUT', '/api/cash_fake/transaction/'.$entryId.'/rollback');
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals($balance, $ret['ret']['cash_fake']['balance']);

        $this->assertEmpty($redisWallet->hvals($entryKey));
    }

    /**
     * 測試交易取消時預扣記錄的ref_id為0會回傳空字串
     */
    public function testTransactionRollbackWithEmptyRefId()
    {
        $client = $this->createClient();
        $redis  = $this->getContainer()->get('snc_redis.default');

        $parameters = [
            'opcode' => 1001,
            'amount' => 100,
            'auto_commit' => false,
            'ref_id' => ''
        ];

        $client->request('PUT', '/api/user/8/cash_fake/op', $parameters);

        $queue = json_decode($redis->rpop('cash_fake_trans_queue'), true);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);
        $entryId = $ret['ret']['entries'][0]['id'];

        $client->request('PUT', '/api/cash_fake/transaction/'.$entryId.'/rollback');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertSame(0, $queue['ref_id']);
        $this->assertEquals('', $ret['ret']['entry']['ref_id']);
    }

    /**
     * 測試回傳交易預扣存
     */
    public function testGetTransaction()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user   = $em->find('BB\DurianBundle\Entity\User', 8);

        $this->assertEquals(0, $user->getCashFake()->getPreSub());

        $em->clear();

        $parameters = array(
            'opcode'    => 10002, // BETTING
            'amount'    => -100,
            'operator'  => 'tester',
            'auto_commit' => false
        );

        $client->request('PUT', '/api/user/8/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);
        $cashFakeTransId = $ret['ret']['entries'][0]['id'];

        $client = $this->createClient();
        $client->request('GET', '/api/cash_fake/transaction/'.$cashFakeTransId);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(10002, $ret['ret']['opcode']);
        $this->assertEquals(-100, $ret['ret']['amount']);
        $this->assertEquals('', $ret['ret']['memo']);
        $this->assertEquals('', $ret['ret']['ref_id']);
        $this->assertEquals('tester', $ret['ret']['operator']['username']);

        $client->request('GET', '/api/cash_fake/transaction/666');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);
        $this->assertEquals('error', $ret['result']);
        $this->assertEquals('No cashFakeTrans found', $ret['msg']);
        $this->assertEquals(150050014, $ret['code']);

        $client->request('PUT', '/api/cash_fake/transaction/'.$cashFakeTransId.'/commit');
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);
        $this->assertEquals('ok', $ret['result']);
    }

    /**
     * 測試回傳交易明細
     */
    public function testGetEntries()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = array(
            'first_result' => 0,
            'max_results'  => 20,
            'sub_ret'      => 1,
            'sub_total'    => 1,
            'fields'       => array('operator')
        );

        $client->request('GET', '/api/user/2/cash_fake/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals(1, $ret['ret'][0]['id']);
        $this->assertEquals(1, $ret['ret'][0]['cash_fake_id']);
        $this->assertEquals(2, $ret['ret'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret'][0]['currency']);
        $this->assertEquals(1006, $ret['ret'][0]['opcode']); // 1006 TRANSFER-4-IN 體育投注額度轉入
        $this->assertEquals(10000, $ret['ret'][0]['amount']);
        $this->assertEquals(10000, $ret['ret'][0]['balance']);
        $this->assertEquals('', $ret['ret'][0]['memo']);
        $this->assertEquals('company', $ret['ret'][0]['operator']['username']);
        $this->assertEquals('', $ret['ret'][0]['ref_id']);

        $this->assertEquals(2, $ret['ret'][1]['id']);
        $this->assertEquals(1, $ret['ret'][1]['cash_fake_id']);
        $this->assertEquals(2, $ret['ret'][1]['user_id']);
        $this->assertEquals('CNY', $ret['ret'][1]['currency']);
        $this->assertEquals(1003, $ret['ret'][1]['opcode']); // TRANSFER
        $this->assertEquals(-5000, $ret['ret'][1]['amount']);
        $this->assertEquals(5000, $ret['ret'][1]['balance']);
        $this->assertEquals('', $ret['ret'][1]['memo']);
        $this->assertEquals('company', $ret['ret'][1]['operator']['username']);
        $this->assertEquals(1, $ret['ret'][1]['ref_id']);

        $this->assertFalse(isset($ret['ret'][2]));

        $this->assertEquals(2, count($ret['sub_ret']));

        $user = $em->find('BB\DurianBundle\Entity\User', 2);
        $cashFake = $user->getCashFake();

        $this->assertEquals($user->getUsername(), $ret['sub_ret']['user']['username']);
        $this->assertEquals($user->getAlias(), $ret['sub_ret']['user']['alias']);
        $this->assertEquals($cashFake->getId(), $ret['sub_ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance() - $cashFake->getPreSub(), $ret['sub_ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getUser()->getId(), $ret['sub_ret']['cash_fake']['user_id']);

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
    }

    /**
     * 測試回傳條件限制交易紀錄
     */
    public function testGetEntriesByLimit()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emHis = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $idGenerator = $this->getContainer()->get('durian.cash_fake_entry_id_generator');
        $client = $this->createClient();

        $cashfake = $em->find('BBDurianBundle:CashFake', 1);
        // 加入五筆交易紀錄到歷史資料庫
        for ($i = 0; $i < 5; $i++) {
            $entry = new CashFakeEntry($cashfake, 1001, 100, '');
            $entry->setId($idGenerator->generate());
            $entry->setRefId(0);
            $cashfake->setBalance($entry->getBalance());
            $emHis->persist($entry);
        }

        $emHis->flush();
        $emHis->clear();

        $parameters = array(
            'first_result' => 1,
            'max_results'  => 4,
        );

        $client->request('GET', '/api/user/2/cash_fake/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        //測試交易紀錄是否只回傳四筆
        $this->assertEquals(4, count($ret['ret']));
        $this->assertEquals(1003, $ret['ret'][0]['opcode']); // TRANSFER
        $this->assertEquals(-5000, $ret['ret'][0]['amount']);
        $this->assertEquals(5000, $ret['ret'][0]['balance']);
        $this->assertEquals(1001, $ret['ret'][1]['opcode']); // DEPOSIT
        $this->assertEquals(100, $ret['ret'][1]['amount']);
        $this->assertEquals(5100, $ret['ret'][1]['balance']);
        $this->assertEquals(1001, $ret['ret'][2]['opcode']); // DEPOSIT
        $this->assertEquals(100, $ret['ret'][2]['amount']);
        $this->assertEquals(5200, $ret['ret'][2]['balance']);
        $this->assertEquals(1001, $ret['ret'][3]['opcode']); // DEPOSIT
        $this->assertEquals(100, $ret['ret'][3]['amount']);
        $this->assertEquals(5300, $ret['ret'][3]['balance']);
        $this->assertEquals(7, $ret['pagination']['total']);
        $this->assertEquals(1, $ret['pagination']['first_result']);
        $this->assertEquals(4, $ret['pagination']['max_results']);

        $parameters = array(
            'opcode' => 1006, // 1006 TRANSFER-4-IN 體育投注額度轉入
        );

        $client->request('GET', '/api/user/2/cash_fake/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(1006, $ret['ret'][0]['opcode']); // 1006 TRANSFER-4-IN 體育投注額度轉入
        $this->assertEquals(10000, $ret['ret'][0]['amount']);
        $this->assertEquals(10000, $ret['ret'][0]['balance']);
        $this->assertEquals(1, $ret['pagination']['total']);
        $this->assertNull($ret['pagination']['first_result']);
        $this->assertNull($ret['pagination']['max_results']);

        //測試多重opcode條件
        $parameters = array(
            'opcode' => array(1006, 1003) // 1006 TRANSFER-4-IN 體育投注額度轉入 1003 TRANSFER
        );

        $client->request('GET', '/api/user/2/cash_fake/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(1006, $ret['ret'][0]['opcode']); // 1006 TRANSFER-4-IN 體育投注額度轉入
        $this->assertEquals(10000, $ret['ret'][0]['amount']);
        $this->assertEquals(10000, $ret['ret'][0]['balance']);
        $this->assertEquals(1003, $ret['ret'][1]['opcode']);
        $this->assertEquals(-5000, $ret['ret'][1]['amount']);
        $this->assertEquals(5000, $ret['ret'][1]['balance']);
        $this->assertEquals(2, $ret['pagination']['total']);
        $this->assertNull($ret['pagination']['first_result']);
        $this->assertNull($ret['pagination']['max_results']);
    }

    /**
     * 測試回傳時間限制交易紀錄
     */
    public function testGetEntriesByTime()
    {
        $client = $this->createClient();

        $start = new \Datetime('now');
        $start->sub(new \DateInterval('P15D'));
        $beginAt = $start->format(\DateTime::ISO8601);
        $end = new \Datetime('now');
        $endAt = $end->format(\DateTime::ISO8601);

        $parameters = [
            'start' => $beginAt,
            'end'   => $endAt
        ];

        $client->request('GET', '/api/user/2/cash_fake/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret'][0]['id']);
        $this->assertEquals(1003, $ret['ret'][0]['opcode']);
        $this->assertEquals(-5000, $ret['ret'][0]['amount']);
        $this->assertEquals(1, $ret['pagination']['total']);

        $parameters = array(
            'start' => '2012-01-01T11:00:00+0800',
            'end'   => '2012-01-01T13:00:00+0800'
        );

        $client->request('GET', '/api/user/2/cash_fake/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(1, $ret['ret'][0]['id']);
        $this->assertEquals(1006, $ret['ret'][0]['opcode']); // 1006 TRANSFER-4-IN 體育投注額度轉入
        $this->assertEquals(10000, $ret['ret'][0]['amount']);
        $this->assertEquals('2012-01-01T12:00:00+0800', $ret['ret'][0]['created_at']);
        $this->assertEquals(1, $ret['pagination']['total']);

        //測試帶入美東時間結果是否相同
        $parameters = array(
            'start' => '2011-12-31T23:00:00-0500',
            'end'   => '2012-01-01T01:00:00-0500'
        );

        $client->request('GET', '/api/user/2/cash_fake/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(1, $ret['ret'][0]['id']);
        $this->assertEquals(1006, $ret['ret'][0]['opcode']); // 1006 TRANSFER-4-IN 體育投注額度轉入
        $this->assertEquals(10000, $ret['ret'][0]['amount']);
        $this->assertEquals('2012-01-01T12:00:00+0800', $ret['ret'][0]['created_at']);
        $this->assertEquals(1, $ret['pagination']['total']);
    }

    /**
     * 測試回傳交易紀錄與操作者, 在沒有交易明細的情況下
     */
    public function testGetEntryAndItsOperatorWhileThereIsNoEntry()
    {
        $em = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $client = $this->createClient();

        // 先把交易明細砍掉
        $entries = $em->getRepository('BB\DurianBundle\Entity\CashFakeEntry')
                      ->findBy(array('cashFakeId' => 1));
        foreach ($entries as $entry) {
            $em->remove($entry);
        }

        $em->flush();
        $em->clear();

        $client = $this->createClient();

        $parameters = array(
            'fields' => array('operator')
        );

        $client->request('GET', '/api/user/2/cash_fake/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(0, $ret['pagination']['total']);
    }

    /**
     * 測試回傳參考編號交易紀錄
     */
    public function testGetEntriesByRefId()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emHis = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $idGenerator = $this->getContainer()->get('durian.cash_fake_entry_id_generator');
        $client = $this->createClient();

        $cashFake = $em->find('BBDurianBundle:CashFake', 1);

        // 1001 DEPOSIT
        $entry = new CashFakeEntry($cashFake, 1001, 100, '');
        $entry->setId($idGenerator->generate());
        $entry->setRefId(9527);
        $cashFake->setBalance($entry->getBalance());
        $emHis->persist($entry);

        $emHis->flush();
        $emHis->clear();

        $parameters = array(
            'ref_id' => '9527'
        );

        $client->request('GET', '/api/user/2/cash_fake/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(1001, $ret['ret'][0]['opcode']);
        $this->assertEquals(100, $ret['ret'][0]['amount']);
        $this->assertEquals(5100, $ret['ret'][0]['balance']);
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
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emHis = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $client = $this->createClient();

        $parameters = array(
            'order' => 'asc',
            'sort'  => 'opcode',
        );

        $client->request('GET', '/api/user/2/cash_fake/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, count($ret['ret']));
        $this->assertEquals(1003, $ret['ret'][0]['opcode']); // TRANSFER
        $this->assertEquals(-5000, $ret['ret'][0]['amount']);
        $this->assertEquals(5000, $ret['ret'][0]['balance']);
        $this->assertEquals(1006, $ret['ret'][1]['opcode']); // 1006 TRANSFER-4-IN 體育投注額度轉入
        $this->assertEquals(10000, $ret['ret'][1]['amount']);
        $this->assertEquals(10000, $ret['ret'][1]['balance']);

        $parameters = array(
            'order' => 'desc',
            'sort'  => 'opcode',
        );

        $client->request('GET', '/api/user/2/cash_fake/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, count($ret['ret']));
        $this->assertEquals(1006, $ret['ret'][0]['opcode']); // 1006 TRANSFER-4-IN 體育投注額度轉入
        $this->assertEquals(10000, $ret['ret'][0]['amount']);
        $this->assertEquals(10000, $ret['ret'][0]['balance']);
        $this->assertEquals(1003, $ret['ret'][1]['opcode']); // TRANSFER
        $this->assertEquals(-5000, $ret['ret'][1]['amount']);
        $this->assertEquals(5000, $ret['ret'][1]['balance']);

        //測試可正確排序
        $parameters = [
            'order' => 'asc',
            'sort' => 'created_at',
        ];

        $client->request('GET', '/api/user/2/cash_fake/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, count($ret['ret']));
        $this->assertEquals(1, $ret['ret'][0]['id']);
        $this->assertEquals(1006, $ret['ret'][0]['opcode']);
        $this->assertEquals(2, $ret['ret'][1]['id']);
        $this->assertEquals(1003, $ret['ret'][1]['opcode']);
        $userAt1 = new \Datetime($ret['ret'][0]['created_at']);
        $userAt2 = new \Datetime($ret['ret'][1]['created_at']);
        $this->assertGreaterThan($userAt1, $userAt2);

        $cashFake = $em->find('BBDurianBundle:CashFake', 1);
        $entry = new CashFakeEntry($cashFake, 1006, 1000, '');
        $idGenerator = $this->getContainer()->get('durian.cash_fake_entry_id_generator');
        $entry->setId($idGenerator->generate());
        $entry->setRefId(0);
        $cashFake->setBalance($entry->getBalance());
        $emHis->persist($entry);
        $emHis->flush();

        $parameters = array(
            'order' => 'desc',
            'sort'  => array('opcode', 'amount')
        );
        $client->request('GET', '/api/user/2/cash_fake/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(3, count($ret['ret']));
        $this->assertEquals(1006, $ret['ret'][0]['opcode']);// 1006 TRANSFER-4-IN 體育投注額度轉入
        $this->assertEquals(1006, $ret['ret'][1]['opcode']);// 1006 TRANSFER-4-IN 體育投注額度轉入
        $this->assertEquals(10000, $ret['ret'][0]['amount']);
        $this->assertEquals(1000, $ret['ret'][1]['amount']);
        $this->assertEquals(1003, $ret['ret'][2]['opcode']);// TRANSFER
        $this->assertEquals(-5000, $ret['ret'][2]['amount']);

        $parameters = array(
            'order' => array('desc','asc'),
            'sort'  => array('opcode', 'amount')
        );

        $client->request('GET', '/api/user/2/cash_fake/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(3, count($ret['ret']));
        $this->assertEquals(1006, $ret['ret'][0]['opcode']);// 1006 TRANSFER-4-IN 體育投注額度轉入
        $this->assertEquals(1006, $ret['ret'][1]['opcode']);// 1006 TRANSFER-4-IN 體育投注額度轉入
        $this->assertEquals(1000, $ret['ret'][0]['amount']);
        $this->assertEquals(10000, $ret['ret'][1]['amount']);
        $this->assertEquals(1003, $ret['ret'][2]['opcode']);// TRANSFER
        $this->assertEquals(-5000, $ret['ret'][2]['amount']);
    }

    /**
     * 測試取得交易紀錄回傳空陣列
     */
    public function testGetEntriesEmptyResult()
    {
        $client = $this->createClient();

        $parameters = array(
            'start' => '2010-01-01T00:00:00+0800',
            'end'   => '2010-01-02T00:00:00+0800'
        );
        $client->request('GET', '/api/user/2/cash_fake/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEmpty($output['ret']);
    }

    /**
     * 測試回傳交易明細無假現金
     */
    public function testGetEntriesButCashFakeNotExist()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/10/cash_fake/entry');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150050001, $ret['code']);
        $this->assertEquals('No cashFake found', $ret['msg']);
    }

    /**
     * 測試取得總計資訊
     */
    public function testGetTotalAmount()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/2/cash_fake/total_amount');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals(10000, $ret['ret']['deposite']);
        $this->assertEquals(-5000, $ret['ret']['withdraw']);
        $this->assertEquals(5000, $ret['ret']['total']);
    }

    /**
     * 測試取得總計資訊帶入時間區間為45天內在原資料庫內搜尋
     */
    public function testGetTotalAmountWithTimeIntervalBelow45Days()
    {
        $client = $this->createClient();

        $start = new \Datetime('now');
        $start->sub(new \DateInterval('P15D'));
        $beginAt = $start->format(\DateTime::ISO8601);
        $end = new \Datetime('now');
        $endAt = $end->format(\DateTime::ISO8601);

        $parameters = [
            'start' => $beginAt,
            'end' => $endAt
        ];

        $client->request('GET', '/api/user/2/cash_fake/total_amount', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals(0, $ret['ret']['deposite']);
        $this->assertEquals(-5000, $ret['ret']['withdraw']);
        $this->assertEquals(-5000, $ret['ret']['total']);
    }

    /**
     * 測試取得總計資訊帶入時間區間超過45天在歷史資料庫內搜尋
     */
    public function testGetTotalAmountWithTimeIntervalOver45Days()
    {
        $client = $this->createClient();

        $parameters = array(
            'start' => '2012-01-01T00:00:00+0800',
            'end' => '2012-01-01T23:59:59+0800'
        );

        $client->request('GET', '/api/user/2/cash_fake/total_amount', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals(10000, $ret['ret']['deposite']);
        $this->assertEquals(0, $ret['ret']['withdraw']);
        $this->assertEquals(10000, $ret['ret']['total']);
    }

    /**
     * 測試取得總計資訊帶入時間區間和opcode
     */
    public function testGetTotalAmountWithOpcodeAndTimeInterval()
    {
        $client = $this->createClient();

        $parameters = array(
            'opcode' => '1006',
            'start' => '2012-01-01T00:00:00+0800',
            'end' => '2012-01-01T23:59:59+0800'
        );

        $client->request('GET', '/api/user/2/cash_fake/total_amount', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals(10000, $ret['ret']['deposite']);
        $this->assertEquals(0, $ret['ret']['withdraw']);
        $this->assertEquals(10000, $ret['ret']['total']);
    }

    /**
     * 測試取得總計資訊無假現金
     */
    public function testGetTotalAmountButCashFakeNotExist()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/10/cash_fake/total_amount');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150050001, $ret['code']);
        $this->assertEquals('No cashFake found', $ret['msg']);
    }

    /**
     * 測試取得總計(opcode 9890以下)資訊
     */
    public function testGetTotalTransfer()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/2/cash_fake/transfer_total_amount');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals(10000, $ret['ret']['deposite']);
        $this->assertEquals(-5000, $ret['ret']['withdraw']);
        $this->assertEquals(5000, $ret['ret']['total']);
    }

    /**
     * 測試取得總計(opcode 9890以下)資訊無假現金
     */
    public function testGetTotalTransferButCashFakeNotExist()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/10/cash_fake/transfer_total_amount');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals('150050001', $ret['code']);
        $this->assertEquals('No cashFake found', $ret['msg']);
    }

    /**
     * 測試回傳下層轉帳交易紀錄(opcode 9890以下)
     */
    public function testGetTransferEntriesList()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $operator = new CashFakeEntryOperator(3, 'vtester');
        $operator->setTransferOut(true);
        $operator->setLevel(2);
        $operator->setWhom('vtester');

        $em->persist($operator);
        $em->flush();

        $parameters = [
            'parent_id' => '2',
            'sub_ret'   => 1,
            'currency'  => 'CNY',
            'sub_total' => 1,
            'opcode'    => 1003,
            'fields'    => ['operator']
        ];

        $client->request('GET', '/api/cash_fake/transfer_entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $cashFake0 = $em->find('BB\DurianBundle\Entity\CashFake', $ret['sub_ret']['cash_fake'][0]['id']);
        $cashFake5 = $em->find('BB\DurianBundle\Entity\CashFake', $ret['sub_ret']['cash_fake'][5]['id']);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(3, $ret['ret'][0]['id']);
        $this->assertEquals(2, $ret['ret'][0]['domain']);
        $this->assertEquals(1003, $ret['ret'][0]['opcode']); // TRANSFER
        $this->assertEquals(5000, $ret['ret'][0]['amount']);
        $this->assertEquals(5000, $ret['ret'][0]['balance']);
        $this->assertEquals(3, $ret['ret'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret'][0]['currency']);
        $this->assertEquals(3, $ret['ret'][0]['operator']['entry_id']);
        $this->assertEquals('vtester', $ret['ret'][0]['operator']['username']);
        $this->assertEquals(true, $ret['ret'][0]['operator']['transfer_out']);
        $this->assertEquals('vtester', $ret['ret'][0]['operator']['whom']);
        $this->assertEquals(2, $ret['ret'][0]['operator']['level']);

        $this->assertEquals(3, $ret['sub_ret']['user'][0]['id']);
        $this->assertEquals('vtester', $ret['sub_ret']['user'][0]['username']);
        $this->assertEquals('vtester', $ret['sub_ret']['user'][0]['alias']);

        $this->assertEquals($cashFake0->getId(), $ret['sub_ret']['cash_fake'][0]['id']);
        $this->assertEquals($cashFake0->getUser()->getId(), $ret['sub_ret']['cash_fake'][0]['user_id']);
        $newBalance = $cashFake0->getBalance() - $cashFake0->getPreSub();
        $this->assertEquals($newBalance, $ret['sub_ret']['cash_fake'][0]['balance']);
        $this->assertEquals('CNY', $ret['sub_ret']['cash_fake'][0]['currency']);

        $this->assertEquals(4, $ret['ret'][1]['id']);
        $this->assertEquals(5, $ret['ret'][2]['id']);
        $this->assertEquals(6, $ret['ret'][3]['id']);
        $this->assertEquals(7, $ret['ret'][4]['id']);
        $this->assertEquals(8, $ret['ret'][5]['id']);
        $this->assertEquals(9, $ret['ret'][6]['id']);
        $this->assertEquals(10, $ret['ret'][7]['id']);
        $this->assertEquals(11, $ret['ret'][8]['id']);
        $this->assertEquals(12, $ret['ret'][9]['id']);
        $this->assertEquals(13, $ret['ret'][10]['id']);

        $this->assertEquals(3, $ret['ret'][0]['user_id']);
        $this->assertEquals(3, $ret['ret'][1]['user_id']);
        $this->assertEquals(4, $ret['ret'][2]['user_id']);
        $this->assertEquals(4, $ret['ret'][3]['user_id']);
        $this->assertEquals(5, $ret['ret'][4]['user_id']);
        $this->assertEquals(5, $ret['ret'][5]['user_id']);
        $this->assertEquals(6, $ret['ret'][6]['user_id']);
        $this->assertEquals(6, $ret['ret'][7]['user_id']);
        $this->assertEquals(7, $ret['ret'][8]['user_id']);
        $this->assertEquals(7, $ret['ret'][9]['user_id']);

        foreach ($ret['ret'] as $row) {
            $this->assertEquals('CNY', $row['currency']);
        }

        $this->assertEquals(13, $ret['ret'][10]['id']);
        $this->assertEquals(2, $ret['ret'][10]['domain']);
        $this->assertEquals(1003, $ret['ret'][10]['opcode']); // TRANSFER
        $this->assertEquals(150, $ret['ret'][10]['amount']);
        $this->assertEquals(150, $ret['ret'][10]['balance']);
        $this->assertEquals(8, $ret['ret'][10]['user_id']);
        $this->assertEquals('CNY', $ret['ret'][10]['currency']);

        $this->assertEquals(8, $ret['sub_ret']['user'][5]['id']);
        $this->assertEquals('tester', $ret['sub_ret']['user'][5]['username']);
        $this->assertEquals('tester', $ret['sub_ret']['user'][5]['alias']);

        $this->assertEquals($cashFake5->getId(), $ret['sub_ret']['cash_fake'][5]['id']);
        $this->assertEquals($cashFake5->getUser()->getId(), $ret['sub_ret']['cash_fake'][5]['user_id']);
        $newBalance = $cashFake5->getBalance() - $cashFake5->getPreSub();
        $this->assertEquals($newBalance, $ret['sub_ret']['cash_fake'][5]['balance']);
        $this->assertEquals('CNY', $ret['sub_ret']['cash_fake'][5]['currency']);

        $this->assertEquals(11, count($ret['ret']));
        $this->assertEquals(11, $ret['pagination']['total']);
        $this->assertNull($ret['pagination']['first_result']);
        $this->assertNull($ret['pagination']['max_results']);

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

        //測試帶入TWD 幣別條件
        $parameters = array('parent_id' => '2', 'currency' => 'TWD');

        $client->request('GET', '/api/cash_fake/transfer_entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(0, $ret['pagination']['total']);
    }

    /**
     * 測試回傳下層轉帳交易紀錄(opcode 9890以下)搜尋美金幣別
     */
    public function testGetTransferEntriesListWithCurrencyUSD()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parent = $em->find('BB\DurianBundle\Entity\User', 7);
        $ancestor = $em->find('BB\DurianBundle\Entity\User', 2);

        $user = new \BB\DurianBundle\Entity\User();
        $user->setId(11);
        $user->setUsername('testusd');
        $user->setAlias('testusd');
        $user->setPassword('123456');
        $user->setDomain(2);
        $user->setParent($parent);
        $em->persist($user);

        $ua = new \BB\DurianBundle\Entity\UserAncestor($user, $ancestor, 6);
        $em->persist($ua);

        $cashFake = new \BB\DurianBundle\Entity\CashFake($user, 840); // USD
        $em->persist($cashFake);
        $em->flush();

        $entry = new \BB\DurianBundle\Entity\CashFakeEntry($cashFake, 1001, 10);
        $entry->setRefId(0);
        $entry->setId(20);
        $em->persist($entry);

        $transferEntry = new \BB\DurianBundle\Entity\CashFakeTransferEntry($entry, 2);
        $em->persist($transferEntry);

        $em->flush();
        $em->clear();

        $parameters = array('parent_id'     => '2',
                            'sub_ret'       => 1,
                            'currency'      => 'USD');

        $client->request('GET', '/api/cash_fake/transfer_entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(20, $ret['ret'][0]['id']);
        $this->assertEquals(2, $ret['ret'][0]['domain']);
        $this->assertEquals(1001, $ret['ret'][0]['opcode']);
        $this->assertEquals(10, $ret['ret'][0]['amount']);
        $this->assertEquals('USD', $ret['ret'][0]['currency']);
        $this->assertEquals(11, $ret['ret'][0]['user_id']);

        $this->assertEquals(11, $ret['sub_ret']['user'][0]['id']);
        $this->assertEquals('testusd', $ret['sub_ret']['user'][0]['username']);
        $this->assertEquals('testusd', $ret['sub_ret']['user'][0]['alias']);

        $this->assertEquals(9, $ret['sub_ret']['cash_fake'][0]['id']);
        $this->assertEquals(11, $ret['sub_ret']['cash_fake'][0]['user_id']);
        $this->assertEquals('USD', $ret['sub_ret']['cash_fake'][0]['currency']);
    }

    /**
     * 測試回傳下層轉帳交易紀錄(opcode 9890以下)無假現金
     */
    public function testGetTransferEntriesListButCashFakeNotExist()
    {
        $client = $this->createClient();

        $parameters = ['parent_id' => '10'];

        $client->request('GET', '/api/cash_fake/transfer_entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150050001, $ret['code']);
        $this->assertEquals('No cashFake found', $ret['msg']);
    }

    /**
     * 測試回傳轉帳交易紀錄(opcode 9890以下)
     */
    public function testGetTransferEntries()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = [
            'sub_ret' => 1,
            'sub_total' => 1,
            'fields' => ['operator']
        ];

        $client->request('GET', '/api/user/2/cash_fake/transfer_entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(1, $ret['ret'][0]['id']);
        $this->assertEquals(2, $ret['ret'][0]['domain']);
        $this->assertEquals(1006, $ret['ret'][0]['opcode']); // 1006 TRANSFER-4-IN 體育投注額度轉入
        $this->assertEquals('10000', $ret['ret'][0]['amount']);
        $this->assertEquals('10000', $ret['ret'][0]['balance']);
        $this->assertEquals(2, $ret['ret'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret'][0]['currency']);
        $this->assertEquals(1, $ret['ret'][0]['operator']['entry_id']);
        $this->assertEquals('company', $ret['ret'][0]['operator']['username']);
        $this->assertEquals(true, $ret['ret'][0]['operator']['transfer_out']);
        $this->assertEquals('lala', $ret['ret'][0]['operator']['whom']);
        $this->assertEquals(2, $ret['ret'][0]['operator']['level']);

        $this->assertEquals(2, $ret['ret'][1]['id']);
        $this->assertEquals(2, $ret['ret'][1]['domain']);
        $this->assertEquals(1003, $ret['ret'][1]['opcode']);
        $this->assertEquals('-5000', $ret['ret'][1]['amount']);
        $this->assertEquals('5000', $ret['ret'][1]['balance']);
        $this->assertEquals(2, $ret['ret'][1]['user_id']);
        $this->assertEquals('CNY', $ret['ret'][1]['currency']);
        $this->assertEquals(2, $ret['ret'][1]['operator']['entry_id']);
        $this->assertEquals('company', $ret['ret'][1]['operator']['username']);
        $this->assertEquals(true, $ret['ret'][1]['operator']['transfer_out']);
        $this->assertEquals('lala', $ret['ret'][1]['operator']['whom']);
        $this->assertEquals(2, $ret['ret'][1]['operator']['level']);

        $this->assertEquals(2, count($ret['sub_ret']));

        $user = $em->find('BB\DurianBundle\Entity\User', 2);
        $cashFake = $user->getCashFake();

        $this->assertEquals($user->getId(), $ret['sub_ret']['user']['id']);
        $this->assertEquals($user->getUsername(), $ret['sub_ret']['user']['username']);
        $this->assertEquals($user->getAlias(), $ret['sub_ret']['user']['alias']);

        $this->assertEquals($cashFake->getId(), $ret['sub_ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance() - $cashFake->getPreSub(), $ret['sub_ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getUser()->getId(), $ret['sub_ret']['cash_fake']['user_id']);
        $this->assertEquals('CNY', $ret['sub_ret']['cash_fake']['currency']);

        //驗證sub_total
        $deposite = 0;
        $withdraw = 0;
        foreach ($ret['ret'] as $entry) {
            if ($entry['amount'] > 0)
                $deposite += $entry['amount'];
            if ($entry['amount'] < 0)
                $withdraw += $entry['amount'];
        }
        $this->assertEquals($ret['sub_total']['deposite'], $deposite);
        $this->assertEquals($ret['sub_total']['withdraw'], $withdraw);
        $this->assertEquals($ret['sub_total']['total'], $withdraw+$deposite);

        //驗證分頁
        $this->assertEquals(2, $ret['pagination']['total']);
        $this->assertNull($ret['pagination']['first_result']);
        $this->assertNull($ret['pagination']['max_results']);
    }

    /**
     * 測試以幣別查尋轉帳交易紀錄(opcode 9890以下)
     */
    public function testGetTransferEntryWithCurrency()
    {
        $client = $this->createClient();

        //測試帶入currency作查詢
        $parameters = array('currency' => 'CNY');

        $client->request('GET', '/api/user/2/cash_fake/transfer_entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals(1, $ret['ret'][0]['id']);
        $this->assertEquals(2, $ret['ret'][0]['domain']);
        $this->assertEquals(1006, $ret['ret'][0]['opcode']);
        $this->assertEquals(10000, $ret['ret'][0]['amount']);
        $this->assertEquals(10000, $ret['ret'][0]['balance']);
        $this->assertEquals(2, $ret['ret'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret'][0]['currency']);

        $this->assertEquals(2, $ret['ret'][1]['id']);
        $this->assertEquals(2, $ret['ret'][1]['domain']);
        $this->assertEquals(1003, $ret['ret'][1]['opcode']);
        $this->assertEquals(-5000, $ret['ret'][1]['amount']);
        $this->assertEquals(5000, $ret['ret'][1]['balance']);
        $this->assertEquals(2, $ret['ret'][1]['user_id']);
        $this->assertEquals('CNY', $ret['ret'][1]['currency']);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['pagination']['total']);
    }

    /**
     * 測試以opcode查尋轉帳交易紀錄(opcode 9890以下)
     */
    public function testGetTransferEntryWithOpcode()
    {
        $client = $this->createClient();

        //測試帶入opcode作查詢
        $parameters = ['opcode' => 1006];

        $client->request('GET', '/api/user/2/cash_fake/transfer_entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals(1, $ret['ret'][0]['id']);
        $this->assertEquals(2, $ret['ret'][0]['domain']);
        $this->assertEquals(1006, $ret['ret'][0]['opcode']);
        $this->assertEquals(10000, $ret['ret'][0]['amount']);
        $this->assertEquals(10000, $ret['ret'][0]['balance']);
        $this->assertEquals(2, $ret['ret'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret'][0]['currency']);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(1, $ret['pagination']['total']);

        //測試帶入多個opcode作查詢
        $parameters = ['opcode' => [1006,2088]];

        $client->request('GET', '/api/user/2/cash_fake/transfer_entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals(1, $ret['ret'][0]['id']);
        $this->assertEquals(2, $ret['ret'][0]['domain']);
        $this->assertEquals(1006, $ret['ret'][0]['opcode']);
        $this->assertEquals(10000, $ret['ret'][0]['amount']);
        $this->assertEquals(10000, $ret['ret'][0]['balance']);
        $this->assertEquals(2, $ret['ret'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret'][0]['currency']);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(1, $ret['pagination']['total']);
    }

    /**
     * 測試回傳轉帳交易紀錄(opcode 9890以下)無假現金
     */
    public function testGetTransferEntryButCashFakeNotExist()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/10/cash_fake/transfer_entry');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150050001, $ret['code']);
        $this->assertEquals('No cashFake found', $ret['msg']);
    }

    /**
     * 測試下層假現金餘額總和
     */
    public function testGetTotalBelow()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/2/cash_fake/total_below');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(5000, $ret['ret']['total_below']);
    }

    /**
     * 測試下層假現金餘額總和(限制使用者條件)
     */
    public function testGetTotalBelowWithUnblock()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BB\DurianBundle\Entity\User', 3);
        $user->block();

        $user = $em->find('BB\DurianBundle\Entity\User', 4);
        $user->disable();

        $em->flush();

        $parameters = array(
            'block'  => 0,
            'enable' => 1,
        );

        $client->request('GET', '/api/user/2/cash_fake/total_below', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(1250, $ret['ret']['total_below']);
    }

    /**
     * 測試設定使用者假現金
     */
    public function testEditCashFake()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $pRedisWallet = $this->getContainer()->get('snc_redis.wallet3');
        $redisWallet = $this->getContainer()->get('snc_redis.wallet4');
        $client = $this->createClient();

        $parameters = array(
            'cash_fake' => array('balance' => 200)
        );

        $client->request('PUT', '/api/user/8', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        // 操作紀錄檢查
        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals("cash_fake", $logOperation->getTableName());
        $this->assertEquals("@user_id:8", $logOperation->getMajorKey());
        $this->assertEquals("@balance:150=>200", $logOperation->getMessage());

        // 一般屬性檢查
        $key = 'cash_fake_balance_8_156';
        $pKey = 'cash_fake_balance_7_156';

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(200, $redisWallet->hget($key, 'balance')/10000);
        $this->assertEquals(0, $redisWallet->hget($key, 'pre_sub')/10000);
        $this->assertEquals(0, $redisWallet->hget($key, 'pre_add')/10000);
        $this->assertEquals(100, $pRedisWallet->hget($pKey, 'balance')/10000);
        $this->assertEquals(0, $pRedisWallet->hget($pKey, 'pre_sub')/10000);
        $this->assertEquals(0, $pRedisWallet->hget($pKey, 'pre_add')/10000);
    }

    /**
     * 測試停用
     */
    public function testDisable()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $cashFake = $em->find('BB\DurianBundle\Entity\CashFake', 3);

        $this->assertTrue($cashFake->isEnable());
        $em->clear();

        $client->request('PUT', '/api/cash_fake/3/disable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals("cash_fake", $logOperation->getTableName());
        $this->assertEquals("@user_id:4", $logOperation->getMajorKey());
        $this->assertEquals("@enable:true=>false", $logOperation->getMessage());

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret']['id']);
        $this->assertFalse($output['ret']['enable']);

        $cashFake = $em->find('BB\DurianBundle\Entity\CashFake', 3);
        $this->assertFalse($cashFake->isEnable());
    }

    /**
     * 測試停用無假現金
     */
    public function testDisableButCashFakeNotExist()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/cash_fake/10/disable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150050001, $output['code']);
        $this->assertEquals('No cashFake found', $output['msg']);
    }

    /**
     * 測試啟用
     */
    public function testEnable()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $cashFake = $em->find('BB\DurianBundle\Entity\CashFake', 3);

        $cashFake->disable();

        $em->flush();
        $em->clear();

        $client->request('PUT', '/api/cash_fake/3/enable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals("cash_fake", $logOperation->getTableName());
        $this->assertEquals("@user_id:4", $logOperation->getMajorKey());
        $this->assertEquals("@enable:false=>true", $logOperation->getMessage());

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret']['id']);
        $this->assertTrue($output['ret']['enable']);

        $cashFake = $em->find('BB\DurianBundle\Entity\CashFake', 3);
        $this->assertTrue($cashFake->isEnable());

        //如上層停用，測試是否下層啟用後顯示仍為停用
        $parentCashFake = $em->find('BB\DurianBundle\Entity\CashFake', 2);
        $parentCashFake->disable();
        $cashFake->disable();

        $em->flush();

        $client->request('PUT', '/api/cash_fake/3/enable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret']['id']);
        $this->assertFalse($output['ret']['enable']);
    }

    /**
     * 測試啟用無假現金
     */
    public function testEnableButCashFakeNotExist()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/cash_fake/10/enable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150050001, $output['code']);
        $this->assertEquals('No cashFake found', $output['msg']);
    }

    /**
     * 測試更新會員總餘額
     */
    public function testUpdateTotalBalance()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = array(
            'parent_id' => 2,
            'currency'  => 'CNY'
        );

        $client->request('PUT', '/api/cash_fake/total_balance', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret'][0]['parent_id']);
        $this->assertEquals(150, $ret['ret'][0]['enable_balance']);
        $this->assertEquals(0, $ret['ret'][0]['disable_balance']);
        $this->assertEquals('CNY', $ret['ret'][0]['currency']);

        // 改為停用
        $user = $em->find('BBDurianBundle:User', 8);
        $user->disable();
        $em->flush();

        $parameters = array(
            'parent_id' => 2,
            'force'     => 1
        );

        $client->request('PUT', '/api/cash_fake/total_balance', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret'][0]['parent_id']);
        $this->assertEquals(0, $ret['ret'][0]['enable_balance']);
        $this->assertEquals(150, $ret['ret'][0]['disable_balance']);
        $this->assertEquals('CNY', $ret['ret'][0]['currency']);

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

        $cash = new \BB\DurianBundle\Entity\CashFake($user, 901); // TWD

        $em->persist($user);
        $em->persist($cash);

        $em->flush();

        $parameters = array(
            'parent_id' => 2,
            'force'     => 1
        );

        $client->request('PUT', '/api/cash_fake/total_balance', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret'][0]['parent_id']);
        $this->assertEquals(0, $ret['ret'][0]['enable_balance']);
        $this->assertEquals(150, $ret['ret'][0]['disable_balance']);
        $this->assertEquals('CNY', $ret['ret'][0]['currency']);
        $this->assertEquals(2, $ret['ret'][1]['parent_id']);
        $this->assertEquals(0, $ret['ret'][1]['enable_balance']);
        $this->assertEquals(0, $ret['ret'][1]['disable_balance']);
        $this->assertEquals('TWD', $ret['ret'][1]['currency']);
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
        $redis->hset('cash_fake_total_balance_2_156', 'normal', 200000);
        $redis->hset('cash_fake_total_balance_2_156', 'test', 1500000);

        // 改為測試
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setTest(true);
        $em->flush();

        $parameters = array(
            'parent_id' => 2,
            'currency'  => 'CNY'
        );

        $client->request('PUT', '/api/cash_fake/total_balance', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret'][0]['parent_id']);
        $this->assertEquals(20, $ret['ret'][0]['enable_balance']);
        $this->assertEquals(0, $ret['ret'][0]['disable_balance']);
        $this->assertEquals('CNY', $ret['ret'][0]['currency']);

        $parameters = array(
            'parent_id'    => 2,
            'include_test' => 1,
            'force'        => 1
        );

        $client->request('PUT', '/api/cash_fake/total_balance', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret'][0]['parent_id']);
        $this->assertEquals(170, $ret['ret'][0]['enable_balance']);
        $this->assertEquals(0, $ret['ret'][0]['disable_balance']);
        $this->assertEquals('CNY', $ret['ret'][0]['currency']);
    }

    /**
     * 測試連續更新會員總餘額
     */
    public function testUpdateTotalBalanceFrequently()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = array(
            'parent_id' => 2
        );

        //第一次更新會員總餘額
        $client->request('PUT', '/api/cash_fake/total_balance', $parameters);

        // 操作紀錄檢查
        // 檢查是否紀錄新增二筆資料在cash_total_balance
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals("cash_fake_total_balance", $logOperation->getTableName());
        $this->assertEquals("@parent_id:2", $logOperation->getMajorKey());
        $this->assertEquals("@currency:CNY", $logOperation->getMessage());
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals("cash_fake_total_balance", $logOperation->getTableName());
        $this->assertEquals("@parent_id:2", $logOperation->getMajorKey());
        $this->assertEquals("@enable_balance:CNY 0=>150", $logOperation->getMessage());

        //第二次更新會員總餘額
        $client->request('PUT', '/api/cash_fake/total_balance', $parameters);

        // 測試是否有寫操作紀錄
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertEmpty($logOperation);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(array(), $ret['ret']);

        // 相差6分鐘即可再更新
        $ctb = $em->find('BB\DurianBundle\Entity\CashFakeTotalBalance', 1);
        $now = new \Datetime('now');
        $ctb->setAt($now->modify('-6 mins'));
        $em->flush();

        $parameters = array(
            'parent_id' => 2,
            'currency'  => 'CNY'
        );

        $client->request('PUT', '/api/cash_fake/total_balance', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret'][0]['parent_id']);
        $this->assertEquals(150, $ret['ret'][0]['enable_balance']);
        $this->assertEquals(0, $ret['ret'][0]['disable_balance']);
        $this->assertEquals('CNY', $ret['ret'][0]['currency']);
    }

    /**
     * 測試更新會員帶入parent非第一層
     */
    public function testUpdateTotalBalanceWithUserNotAtLevelOne()
    {
        $client = $this->createClient();

        $parameters = array('parent_id' => 3);
        $client->request('PUT', '/api/cash_fake/total_balance', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150050019, $ret['code']);
        $this->assertEquals('Not support this user', $ret['msg']);

        // user note exist
        $parameters = array('parent_id' => 999);
        $client->request('PUT', '/api/cash_fake/total_balance', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150050015, $ret['code']);
        $this->assertEquals('No such user', $ret['msg']);
    }

    /**
     * 測試取得會員總餘額
     */
    public function testGetTotalBalance()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = array(
            'parent_id' => 2
        );

        $client->request('GET', '/api/cash_fake/total_balance', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals('No cash fake total balance found', $ret['msg']);
        $this->assertEquals(150050010, $ret['code']);

        // 更新後再抓
        $parameters = array(
            'parent_id' => 2
        );

        $client->request('PUT', '/api/cash_fake/total_balance', $parameters);
        $client->request('GET', '/api/cash_fake/total_balance', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret'][2][0]['parent_id']);
        $this->assertEquals(150, $ret['ret'][2][0]['enable_balance']);
        $this->assertEquals(0, $ret['ret'][2][0]['disable_balance']);

        // 抓所有第一層的使用者
        $client->request('GET', '/api/cash_fake/total_balance');
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret'][2][0]['parent_id']);
        $this->assertEquals(150, $ret['ret'][2][0]['enable_balance']);
        $this->assertEquals(0, $ret['ret'][2][0]['disable_balance']);

        $ctb = $em->find('BB\DurianBundle\Entity\User', 2);
        $ctb->disable();
        $em->flush();

        // 抓所有第一層的啟用使用者
        $parameters = array(
            'enable' => 1
        );

        $client->request('GET', '/api/cash_fake/total_balance', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(0, count($ret['ret'][9]));

        // 抓所有第一層的停用使用者
        $parameters = array(
            'enable' => 0
         );

        $client->request('GET', '/api/cash_fake/total_balance', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret'][2][0]['parent_id']);
        $this->assertEquals(150, $ret['ret'][2][0]['enable_balance']);
        $this->assertEquals(0, $ret['ret'][2][0]['disable_balance']);
        $this->assertEquals(1, count($ret['ret'][2]));

        // 測試帶入已停用的parent_id，參數帶入 enable = 1
        $parameters = array(
            'parent_id' => 2,
            'enable'    => 1
        );

        $client->request('GET', '/api/cash_fake/total_balance', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals('User is disabled', $ret['msg']);
        $this->assertEquals(150050025, $ret['code']);

        // 測試帶入已停用的parent_id，參數帶入 enable = 0
        $parameters = array(
            'parent_id' => 2,
            'enable'    => 0
         );

        $client->request('GET', '/api/cash_fake/total_balance', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret'][2][0]['parent_id']);
        $this->assertEquals(150, $ret['ret'][2][0]['enable_balance']);
        $this->assertEquals(0, $ret['ret'][2][0]['disable_balance']);

        // 測試帶入不存在的幣別
        $parameters = array(
            'parent_id' => 2,
            'enable'    => 0,
            'currency'  => 'TWD'
        );

        $client->request('GET', '/api/cash_fake/total_balance', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals('No cash fake total balance found', $ret['msg']);
        $this->assertEquals(150050010, $ret['code']);

        $ctb = $em->find('BBDurianBundle:User', 2);
        $ctb->enable();
        $em->flush();

        // 測試帶入已啟用的parent_id，參數帶入 enable = 0
        $parameters = [
            'parent_id' => 2,
            'enable'    => 0
        ];

        $client->request('GET', '/api/cash_fake/total_balance', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals('User is enabled', $ret['msg']);
        $this->assertEquals(150050024, $ret['code']);
    }

    /**
     * 測試即時回傳會員總餘額
     */
    public function testGetTotalBalanceLive()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $redis = $this->getContainer()->get('snc_redis.total_balance');
        $redis->hset('cash_fake_total_balance_2_156', 'normal', 0);
        $redis->hset('cash_fake_total_balance_2_156', 'test', 1500000);

        // 改為測試
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setTest(true);
        $em->flush();

        $parameters = [
            'parent_id' => 2,
            'include_test' => 1
        ];

        $client->request('GET', '/api/cash_fake/total_balance_live', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret'][0]['parent_id']);
        $this->assertEquals(150, $ret['ret'][0]['balance']);

        $parameters = array(
            'parent_id'    => 2,
            'include_test' => 0
        );

        $client->request('GET', '/api/cash_fake/total_balance_live', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret'][0]['parent_id']);
        $this->assertEquals(0, $ret['ret'][0]['balance']);

        $parameters = array(
            'parent_id'    => 2,
            'include_test' => 1,
            'enable'       => 1
        );

        $client->request('GET', '/api/cash_fake/total_balance_live', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret'][0]['parent_id']);
        $this->assertEquals(150, $ret['ret'][0]['balance']);
    }

    /**
     * 測試即時回傳會員總餘額帶入parentId非廳主
     */
    public function testGetTotalBalanceLiveWithParentIdNotDomain()
    {
        $client = $this->createClient();

        $parameters = ['parent_id' => 1];

        $client->request('GET', '/api/cash_fake/total_balance_live', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150050020, $ret['code']);
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
            'at'            => '2012-12-01 12:00:00',
            'opcode'        => 1052,
            'memo'          => 'testMemo',
            'amount'        => -6000,
            'ref_id'        => 0,
            'auto_commit'   => true
        );

        $client->request('PUT', '/api/user/2/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        // balance = 1000
        $parameters = array(
            'at'            => '2012-12-01 12:00:00',
            'opcode'        => 1010,
            'memo'          => 'testMemo',
            'amount'        => 2000,
            'ref_id'        => 0,
            'auto_commit'   => true
        );

        $client->request('PUT', '/api/user/2/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        // balance = -1000
        $parameters = array(
            'at'            => '2012-12-01 12:00:00',
            'opcode'        => 1052,
            'memo'          => 'testMemo',
            'amount'        => -2000,
            'ref_id'        => 0,
            'auto_commit'   => true
        );

        $client->request('PUT', '/api/user/2/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $targetId1 = $ret['ret']['entries'][0]['id'];     // 目標值

        // balance = -3000
        $parameters = array(
            'at'            => '2012-12-01 12:00:00',
            'opcode'        => 1052,
            'memo'          => 'testMemo',
            'amount'        => -2000,
            'ref_id'        => 0,
            'auto_commit'   => true
        );

        $client->request('PUT', '/api/user/2/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        // balance = -500
        $parameters = array(
            'at'            => '2012-12-02 12:00:00',
            'opcode'        => 1052,
            'amount'        => -3000,
            'ref_id'        => 0,
            'auto_commit'   => true
        );

        $client->request('PUT', '/api/user/3/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $targetId2 = $ret['ret']['entries'][0]['id'];     // 目標值

        $this->assertEquals('ok', $ret['result']);

        // 跑背景程式讓queue被消化
        $params = [
            '--entry' => true,
            '--balance' => true,
            '--history' => true
        ];
        $this->runCommand('durian:sync-cash-fake', $params);

        $em = $this->getContainer()->get('doctrine.orm.his_entity_manager');

        // 修改infobright的資料，用來檢查搜尋的資料庫是否為infobright
        $entry = $em->getRepository('BBDurianBundle:CashFakeEntry')->findOneBy(['id' => 1003]);
        $entry->setRefId(12345);

        $em->flush();

        $start = new \DateTime('now');
        $start = $start->sub(new \DateInterval('P46D'));
        $end = new \DateTime('now');

        // 查詢負數的明細，由於開始時間距今超過45天，會搜尋infobright
        $parameters = [
            'cash_fake_id' => [1, 2, 3],
            'start' => $start->format(\DateTime::ISO8601),
            'end' => $end->format(\DateTime::ISO8601)
        ];

        $client->request('GET', '/api/cash_fake/negative_entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        // cashfake id 1
        $this->assertEquals($targetId1, $ret['ret'][0]['id']);
        $this->assertEquals(1, $ret['ret'][0]['cash_fake_id']);
        $this->assertEquals(2, $ret['ret'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret'][0]['currency']);
        $this->assertEquals(1052, $ret['ret'][0]['opcode']);
        $this->assertEquals('-2000', $ret['ret'][0]['amount']);
        $this->assertEquals('-1000', $ret['ret'][0]['balance']);
        $this->assertEquals(12345, $ret['ret'][0]['ref_id']);

        // cashfake id 2
        $this->assertEquals($targetId2, $ret['ret'][1]['id']);
        $this->assertEquals(2, $ret['ret'][1]['cash_fake_id']);
        $this->assertEquals(3, $ret['ret'][1]['user_id']);
        $this->assertEquals('CNY', $ret['ret'][1]['currency']);
        $this->assertEquals(1052, $ret['ret'][1]['opcode']);
        $this->assertEquals('-3000', $ret['ret'][1]['amount']);
        $this->assertEquals('-500', $ret['ret'][1]['balance']);
        $this->assertEquals('', $ret['ret'][1]['ref_id']);

        // cashfake id 3
        $this->assertEquals(false, isset($ret['ret'][2]));

        $start = new \DateTime('now');
        $start = $start->sub(new \DateInterval('PT1H'));
        $end = new \DateTime('now');

        // 查詢負數的明細，由於開始時間距今不超過45天，會搜尋原資料庫
        $parameters = [
            'cash_fake_id' => [1, 2, 3],
            'start' => $start->format(\DateTime::ISO8601),
            'end' => $end->format(\DateTime::ISO8601)
        ];

        $client->request('GET', '/api/cash_fake/negative_entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        // cashfake id 1
        $this->assertEquals($targetId1, $ret['ret'][0]['id']);
        $this->assertEquals(1, $ret['ret'][0]['cash_fake_id']);
        $this->assertEquals(2, $ret['ret'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret'][0]['currency']);
        $this->assertEquals(1052, $ret['ret'][0]['opcode']);
        $this->assertEquals('-2000', $ret['ret'][0]['amount']);
        $this->assertEquals('-1000', $ret['ret'][0]['balance']);
        $this->assertEquals('', $ret['ret'][0]['ref_id']);

        // cashfake id 2
        $this->assertEquals($targetId2, $ret['ret'][1]['id']);
        $this->assertEquals(2, $ret['ret'][1]['cash_fake_id']);
        $this->assertEquals(3, $ret['ret'][1]['user_id']);
        $this->assertEquals('CNY', $ret['ret'][1]['currency']);
        $this->assertEquals(1052, $ret['ret'][1]['opcode']);
        $this->assertEquals('-3000', $ret['ret'][1]['amount']);
        $this->assertEquals('-500', $ret['ret'][1]['balance']);
        $this->assertEquals('', $ret['ret'][1]['ref_id']);

        // cashfake id 3
        $this->assertEquals(false, isset($ret['ret'][2]));

        // 查詢無假現金的負數明細
        $parameters = [
            'cash_fake_id' => [10],
            'start' => $start->format(\DateTime::ISO8601),
            'end' => $end->format(\DateTime::ISO8601)
        ];

        $client->request('GET', '/api/cash_fake/negative_entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
    }

    /**
     * 測試回傳餘額為負數快開額度的資料
     */
    public function testGetNegativeBalance()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BB\DurianBundle\Entity\User', 10);

        $cashFake = new CashFake($user, 156);
        $cashFake->setBalance(-5000);
        $cashFake->setNegative(true);
        $cashFake->setLastEntryAt(20120101120000);
        $em->persist($cashFake);
        $em->flush();

        $parameters = [
            'first_result' => 0,
            'max_results'  => 20,
            'sub_ret'      => true
        ];

        $client->request('GET', '/api/cash_fake/negative_balance', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals(9, $ret['ret'][0]['id']);
        $this->assertEquals(10, $ret['ret'][0]['user_id']);
        $this->assertEquals(-5000, $ret['ret'][0]['balance']);
        $this->assertEquals(0, $ret['ret'][0]['pre_sub']);
        $this->assertEquals(0, $ret['ret'][0]['pre_add']);
        $this->assertEquals('CNY', $ret['ret'][0]['currency']);
        $this->assertEquals(true, $ret['ret'][0]['enable']);
        $this->assertEquals(20120101120000, $ret['ret'][0]['last_entry_at']);

        $this->assertEquals(10, $ret['sub_ret']['user'][0]['id']);

        $this->assertEquals(0, $ret['pagination']['first_result']);
        $this->assertEquals(20, $ret['pagination']['max_results']);
        $this->assertEquals(1, $ret['pagination']['total']);
    }

    /**
     * 測試取得未完成交易記錄列表
     */
    public function testGetUncommitTransactionList()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $at = new \DateTime('now', new \DateTimeZone('Asia/Taipei'));
        $at = $at->sub(new \DateInterval('PT10M'));

        $parameters = [
            'opcode'      => 30002,
            'amount'      => 600,
            'ref_id'      => 0,
            'auto_commit' => false
        ];

        $client->request('PUT', '/api/user/8/cash_fake/op', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        //跑背景程式讓queue被消化
        $params = [
            '--entry' => true,
            '--balance' => true
        ];
        $this->runCommand('durian:sync-cash-fake', $params);

        $cfTrans = $em->find('BB\DurianBundle\Entity\CashFakeTrans', $output['ret']['entries'][0]['id']);
        $cfTrans->setCreatedAt($at);
        $em->flush();

        //測試分頁參數
        $parameters = [
            'first_result' => 0,
            'max_results' => 10
        ];

        $client->request('GET', '/api/cash_fake/transaction/uncommit', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(10, $output['pagination']['max_results']);
        $this->assertEquals(1, $output['pagination']['total']);

        $this->assertEquals(30002, $output['ret'][0]['opcode']);
        $this->assertEquals('', $output['ret'][0]['ref_id']);
        $this->assertEquals(600, $output['ret'][0]['amount']);
        $this->assertEquals(7, $output['ret'][0]['cash_fake_id']);
        $this->assertEquals(2, $output['ret'][0]['domain']);
        $this->assertEquals('company', $output['ret'][0]['domain_alias']);
        $this->assertEquals(8, $output['ret'][0]['user_id']);
        $this->assertEquals('CNY', $output['ret'][0]['currency']);
        $this->assertEquals('tester', $output['ret'][0]['username']);

        $id = $output['ret'][0]['id'];
        $client->request('PUT', "/api/cash_fake/transaction/$id/commit");
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);
        $this->assertEquals('ok', $ret['result']);
    }

    /**
     * 測試傳回快開額度不符記錄
     */
    public function testGetCashFakeError()
    {
        $client = $this->createClient();

        $parameters = ['sub_ret' => 30002];

        $client->request('GET', '/api/cash_fake/error', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals(2, $ret['ret'][0]['id']);
        $this->assertEquals(3, $ret['ret'][0]['user_id']);
        $this->assertEquals(2000, $ret['ret'][0]['balance']);
        $this->assertEquals(0, $ret['ret'][0]['pre_sub']);
        $this->assertEquals(0, $ret['ret'][0]['pre_add']);
        $this->assertEquals('CNY', $ret['ret'][0]['currency']);
        $this->assertTrue($ret['ret'][0]['enable']);
        $this->assertEquals(1000, $ret['ret'][0]['total_amount']);

        $this->assertEquals(3, $ret['ret'][1]['id']);
        $this->assertEquals(4, $ret['ret'][1]['user_id']);
        $this->assertEquals(3000, $ret['ret'][1]['balance']);
        $this->assertEquals(0, $ret['ret'][1]['pre_sub']);
        $this->assertEquals(0, $ret['ret'][1]['pre_add']);
        $this->assertEquals('CNY', $ret['ret'][1]['currency']);
        $this->assertTrue($ret['ret'][1]['enable']);
        $this->assertEquals(2000, $ret['ret'][1]['total_amount']);

        $this->assertEquals(3, $ret['sub_ret']['user'][0]['id']);
        $this->assertEquals(4, $ret['sub_ret']['user'][1]['id']);

        $this->assertEquals(count($ret['ret']), $ret['pagination']['total']);
    }

    /**
     * 測試修改快開額度明細備註欄位
     */
    public function testSetCashFakeEntryMemo()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $cftEntry = $em->getRepository('BBDurianBundle:CashFakeTransferEntry')
            ->findOneById(1);
        $this->assertEquals('', $cftEntry->getMemo());
        $em->clear();

        $client = $this->createClient();

        $memo = '';
        for ($i = 0; $i < 100; $i++) {
            $memo .= 'a';
        }
        $parameter = ['memo' => $memo . 012];

        $client->request('PUT', '/api/cash_fake/entry/1', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($memo, $output['ret']['memo']);

        $cftEntry = $em->getRepository('BBDurianBundle:CashFakeTransferEntry')
            ->findOneById(1);
        $this->assertEquals($memo, $cftEntry->getMemo());
    }

    /**
     * 測試修改明細時的例外，此筆明細不存在的情況
     */
    public function testSetCashFakeEntryMemoButEntryNotFound()
    {
        $client = $this->createClient();

        $parameter = array('memo' => 'hrhrhr');

        $client->request('PUT', '/api/cash_fake/entry/999', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150050012, $output['code']);
        $this->assertEquals('No cash fake entry found', $output['msg']);
    }

    /**
     * 測試取得單筆快開額度明細
     */
    public function testGetCashFakeEntry()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/cash_fake/entry/1');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertEquals(1, $output['ret']['cash_fake_id']);
        $this->assertEquals('', $output['ret']['ref_id']);
    }

    /**
     * 測試取單筆快開額度明細時，此筆明細不存在的情況
     */
    public function testGetCashFakeEntryNotFound()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/cash_fake/entry/999');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150050012, $output['code']);
        $this->assertEquals('No cash fake entry found', $output['msg']);
    }

    /**
     * 測試指定時間查尋轉帳交易紀錄
     */
    public function testGetTransferEntryWithSpecificTime()
    {
        $client = $this->createClient();

        $parameters = [
            'start' => '2012-01-01T00:00:00+0800',
            'end' => '2012-01-02T00:00:00+0800'
        ];

        $client->request('GET', '/api/user/2/cash_fake/transfer_entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals(1, $ret['ret'][0]['id']);
        $this->assertEquals(2, $ret['ret'][0]['domain']);
        $this->assertEquals(1006, $ret['ret'][0]['opcode']);
        $this->assertEquals(10000, $ret['ret'][0]['amount']);
        $this->assertEquals(10000, $ret['ret'][0]['balance']);
        $this->assertEquals(2, $ret['ret'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret'][0]['currency']);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(1, $ret['pagination']['total']);
    }

    /**
     * 測試指定ref_id查尋轉帳交易紀錄
     */
    public function testGetTransferEntryWithRefId()
    {
        $client = $this->createClient();

        $parameters = ['ref_id' => 1];

        $client->request('GET', '/api/user/2/cash_fake/transfer_entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals(2, $ret['ret'][0]['id']);
        $this->assertEquals(2, $ret['ret'][0]['domain']);
        $this->assertEquals(1003, $ret['ret'][0]['opcode']);
        $this->assertEquals(-5000, $ret['ret'][0]['amount']);
        $this->assertEquals(5000, $ret['ret'][0]['balance']);
        $this->assertEquals(2, $ret['ret'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret'][0]['currency']);
        $this->assertEquals(1, $ret['ret'][0]['ref_id']);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(1, $ret['pagination']['total']);
    }

    /**
     * 測試指定depth查尋轉帳交易紀錄
     */
    public function testGetTransferEntryListWithDepth()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => 2,
            'depth' => 2
        ];

        $client->request('GET', '/api/cash_fake/transfer_entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals(5, $ret['ret'][0]['id']);
        $this->assertEquals(2, $ret['ret'][0]['domain']);
        $this->assertEquals(1003, $ret['ret'][0]['opcode']);
        $this->assertEquals(2500, $ret['ret'][0]['amount']);
        $this->assertEquals(2500, $ret['ret'][0]['balance']);
        $this->assertEquals(4, $ret['ret'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret'][0]['currency']);
        $this->assertEquals('', $ret['ret'][0]['ref_id']);

        $this->assertEquals(6, $ret['ret'][1]['id']);
        $this->assertEquals(2, $ret['ret'][1]['domain']);
        $this->assertEquals(1003, $ret['ret'][1]['opcode']);
        $this->assertEquals(-1250, $ret['ret'][1]['amount']);
        $this->assertEquals(1250, $ret['ret'][1]['balance']);
        $this->assertEquals(4, $ret['ret'][1]['user_id']);
        $this->assertEquals('CNY', $ret['ret'][1]['currency']);
        $this->assertEquals('', $ret['ret'][1]['ref_id']);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['pagination']['total']);
    }

    /**
     * 測試查詢一個廳所有會員的轉帳交易紀錄
     */
    public function testGetTransferEntryListAllMember()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => 2,
            'depth' => 5
        ];

        $client->request('GET', '/api/cash_fake/transfer_entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals(13, $ret['ret'][0]['id']);
        $this->assertEquals(8, $ret['ret'][0]['user_id']);
        $this->assertEquals(2, $ret['ret'][0]['domain']);
        $this->assertEquals('CNY', $ret['ret'][0]['currency']);
        $this->assertEquals(1003, $ret['ret'][0]['opcode']);
        $this->assertEquals(150, $ret['ret'][0]['amount']);
        $this->assertEquals(150, $ret['ret'][0]['balance']);
        $this->assertEquals('', $ret['ret'][0]['ref_id']);
        $this->assertEquals('', $ret['ret'][0]['memo']);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(1, $ret['pagination']['total']);
    }

    /**
     * 測試回傳下層轉帳交易紀錄(opcode 9890以下),並指定排序與資料筆數
     */
    public function testGetTransferEntriesListWithOrderAndPagination()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => '2',
            'sort' => ['amount','created_at'],
            'order' => ['asc','asc'],
            'first_result' => 7,
            'max_results' => 1
        ];

        $client->request('GET', '/api/cash_fake/transfer_entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals(9, $ret['ret'][0]['id']);
        $this->assertEquals(2, $ret['ret'][0]['domain']);
        $this->assertEquals(1003, $ret['ret'][0]['opcode']);
        $this->assertEquals(625, $ret['ret'][0]['amount']);
        $this->assertEquals(625, $ret['ret'][0]['balance']);
        $this->assertEquals(6, $ret['ret'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret'][0]['currency']);
        $this->assertEquals('', $ret['ret'][0]['ref_id']);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['pagination']['first_result']);
        $this->assertEquals(1, $ret['pagination']['max_results']);
        $this->assertEquals(11, $ret['pagination']['total']);
    }

    /**
     * 測試更新會員總餘額,無下層使用者時回傳空陣列
     */
    public function testUpdateTotalBalanceWithNoChild()
    {
        $client = $this->createClient();

        $parameters = ['parent_id' => 9];

        $client->request('PUT', '/api/cash_fake/total_balance', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals([], $ret['ret']);
    }

    /**
     * 測試取得總計資訊,無符合條件時回傳空陣列
     */
    public function testGetTotalAmountWithNoDataConform()
    {
        $client = $this->createClient();

        $parameters = ['end' => '1990-11-09T11:23:33+0800'];

        $client->request('GET', '/api/user/2/cash_fake/total_amount', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals([], $ret['ret']);
    }

    /**
     * 測試ref_id取得假現金明細
     */
    public function testGetEntriesRefId()
    {
        $client = $this->createClient();

        //測試ref_id取得明細，帶入條件first_result, max_results
        $params = [
            'ref_id' => 1,
            'first_result' => 0,
            'max_results' => 1
        ];

        $client->request('GET', '/api/cash_fake/entries_by_ref_id', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertEquals(1003, $output['ret'][0]['opcode']);
        $this->assertEquals(2, $output['ret'][0]['user_id']);
        $this->assertEquals(1, $output['ret'][0]['ref_id']);
        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(1, $output['pagination']['max_results']);
        $this->assertEquals(1, $output['pagination']['total']);
    }

    /**
     * 測試取得假現金列表
     */
    public function testGetCashFakeList()
    {
        $client = $this->createClient();

        $parameters = ['parent_id' => '2'];

        $client->request('GET', '/api/cash_fake/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertEquals(3, $output['ret'][0]['user_id']);
        $this->assertEquals(156, $output['ret'][0]['currency']);
        $this->assertEquals(0, $output['ret'][0]['pre_sub']);
        $this->assertEquals(0, $output['ret'][0]['pre_add']);
        $this->assertEquals(2500, $output['ret'][0]['balance']);
        $this->assertTrue($output['ret'][0]['enable']);
        $this->assertEquals(20120101120000, $output['ret'][0]['last_entry_at']);

        $this->assertEquals(3, $output['ret'][1]['id']);
        $this->assertEquals(4, $output['ret'][1]['user_id']);
        $this->assertEquals(156, $output['ret'][1]['currency']);
        $this->assertEquals(0, $output['ret'][1]['pre_sub']);
        $this->assertEquals(0, $output['ret'][1]['pre_add']);
        $this->assertEquals(1250, $output['ret'][1]['balance']);
        $this->assertTrue($output['ret'][1]['enable']);
        $this->assertEquals(20120101120000, $output['ret'][1]['last_entry_at']);

        $this->assertEquals(4, $output['ret'][2]['id']);
        $this->assertEquals(5, $output['ret'][2]['user_id']);
        $this->assertEquals(156, $output['ret'][2]['currency']);
        $this->assertEquals(0, $output['ret'][2]['pre_sub']);
        $this->assertEquals(0, $output['ret'][2]['pre_add']);
        $this->assertEquals(625, $output['ret'][2]['balance']);
        $this->assertTrue($output['ret'][2]['enable']);
        $this->assertEquals(20120101120000, $output['ret'][2]['last_entry_at']);

        $this->assertEquals(5, $output['ret'][3]['id']);
        $this->assertEquals(6, $output['ret'][3]['user_id']);
        $this->assertEquals(156, $output['ret'][3]['currency']);
        $this->assertEquals(0, $output['ret'][3]['pre_sub']);
        $this->assertEquals(0, $output['ret'][3]['pre_add']);
        $this->assertEquals(325, $output['ret'][3]['balance']);
        $this->assertTrue($output['ret'][3]['enable']);
        $this->assertEquals(20120101120000, $output['ret'][3]['last_entry_at']);

        $this->assertEquals(6, $output['ret'][4]['id']);
        $this->assertEquals(7, $output['ret'][4]['user_id']);
        $this->assertEquals(156, $output['ret'][4]['currency']);
        $this->assertEquals(0, $output['ret'][4]['pre_sub']);
        $this->assertEquals(0, $output['ret'][4]['pre_add']);
        $this->assertEquals(150, $output['ret'][4]['balance']);
        $this->assertTrue($output['ret'][4]['enable']);
        $this->assertEquals(20120101120000, $output['ret'][4]['last_entry_at']);

        $this->assertEquals(7, $output['ret'][5]['id']);
        $this->assertEquals(8, $output['ret'][5]['user_id']);
        $this->assertEquals(156, $output['ret'][5]['currency']);
        $this->assertEquals(0, $output['ret'][5]['pre_sub']);
        $this->assertEquals(0, $output['ret'][5]['pre_add']);
        $this->assertEquals(150, $output['ret'][5]['balance']);
        $this->assertTrue($output['ret'][5]['enable']);
        $this->assertEquals(20120101120000, $output['ret'][5]['last_entry_at']);

        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(0, $output['pagination']['max_results']);
        $this->assertEquals(6, $output['pagination']['total']);
    }

    /**
     * 測試取得指定條件的假現金列表
     */
    public function testGetCashFakeListWithSpecifiedCriteria()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id'    => '2',
            'depth'        => '6',
            'currency'     => 'CNY',
            'enable'       => 1,
            'first_result' => 0,
            'max_results'  => 20
        ];

        $client->request('GET', '/api/cash_fake/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(7, $output['ret'][0]['id']);
        $this->assertEquals(8, $output['ret'][0]['user_id']);
        $this->assertEquals(156, $output['ret'][0]['currency']);
        $this->assertEquals(0, $output['ret'][0]['pre_sub']);
        $this->assertEquals(0, $output['ret'][0]['pre_add']);
        $this->assertEquals(150, $output['ret'][0]['balance']);
        $this->assertTrue($output['ret'][0]['enable']);

        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(20, $output['pagination']['max_results']);
        $this->assertEquals(1, $output['pagination']['total']);
    }

    /**
     * 測試回傳單一會員時間區間內最後交易餘額
     */
    public function testGetUserLastBalance()
    {
        $client = $this->createClient();

        $parameters = [
            'user_id' => '2',
            'start' => '2012-01-01T11:00:00+0800',
            'end' => '2012-01-01T13:00:00+0800'
        ];

        // 測試取得單一會員餘額
        $client->request('GET', '/api/cash_fake/last_balance', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['user_id']);
        $this->assertEquals(10000, $output['ret'][0]['balance']);
    }

    /**
     * 測試回傳廳內所有會員最後交易餘額
     */
    public function testGetUsersLastBalanceInDomain()
    {
        $client = $this->createClient();
        $time = new \DateTime('now');

        $end = clone $time;
        $start = $time->modify('-1 month');
        $end = $end->modify('1 month');

        $parameters = [
            'domain' => '2',
            'start' => $start->format(\DateTime::ISO8601),
            'end' => $end->format(\DateTime::ISO8601),
            'first_result' => 3,
            'max_results' => 2
        ];

        // 測試取得一個廳的會員餘額
        $client->request('GET', '/api/cash_fake/last_balance', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(6, $output['ret'][0]['user_id']);
        $this->assertEquals(325, $output['ret'][0]['balance']);
        $this->assertEquals(7, $output['ret'][1]['user_id']);
        $this->assertEquals(150, $output['ret'][1]['balance']);
        $this->assertEquals(6, $output['pagination']['total']);
        $this->assertEquals(3, $output['pagination']['first_result']);
        $this->assertEquals(2, $output['pagination']['max_results']);
    }

    /**
     * 測試停用
     */
    public function testDisableByUser()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $client->request('PUT', '/api/user/4/cash_fake/disable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals("cash_fake", $logOperation->getTableName());
        $this->assertEquals("@user_id:4", $logOperation->getMajorKey());
        $this->assertEquals("@enable:true=>false", $logOperation->getMessage());

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret']['id']);
        $this->assertFalse($output['ret']['enable']);

        $cashFake = $em->find('BBDurianBundle:CashFake', 3);
        $this->assertFalse($cashFake->isEnable());
    }

    /**
     * 測試停用無假現金
     */
    public function testDisableByUserButCashFakeNotExist()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/user/999/cash_fake/disable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150050001, $output['code']);
        $this->assertEquals('No cashFake found', $output['msg']);
    }

    /**
     * 測試啟用
     */
    public function testEnableByUser()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $cashFake = $em->find('BBDurianBundle:CashFake', 3);
        $cashFake->disable();
        $em->flush();

        $em->clear();

        $client->request('PUT', '/api/user/4/cash_fake/enable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals("cash_fake", $logOperation->getTableName());
        $this->assertEquals("@user_id:4", $logOperation->getMajorKey());
        $this->assertEquals("@enable:false=>true", $logOperation->getMessage());

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret']['id']);
        $this->assertEquals(4, $output['ret']['user_id']);
        $this->assertTrue($output['ret']['enable']);

        $cashFake = $em->find('BBDurianBundle:CashFake', 3);
        $this->assertTrue($cashFake->isEnable());

        // 如上層停用，下層啟用後顯示仍為停用
        $parentCashFake = $em->find('BBDurianBundle:CashFake', 2);
        $parentCashFake->disable();
        $cashFake->disable();

        $em->flush();

        $client->request('PUT', '/api/user/4/cash_fake/enable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret']['id']);
        $this->assertFalse($output['ret']['enable']);
    }

    /**
     * 測試啟用無假現金
     */
    public function testEnsableByUserButCashFakeNotExist()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/user/999/cash_fake/enable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150050001, $output['code']);
        $this->assertEquals('No cashFake found', $output['msg']);
    }

    /**
     * 依使用者測試取得最近一筆額度為負的交易紀錄
     */
    public function testGetNegativeEntryByUser()
    {
        $client = $this->createClient();

        //加入幾筆交易紀錄
        // balance = -1000
        $parameters = [
            'at' => '2012-12-01 12:00:00',
            'opcode' => 1052,
            'memo' => 'testMemo',
            'amount' => -6000,
            'ref_id' => 0,
            'auto_commit' => true
        ];

        $client->request('PUT', '/api/user/2/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        // balance = 1000
        $parameters = [
            'at' => '2012-12-01 12:00:00',
            'opcode' => 1010,
            'memo' => 'testMemo',
            'amount' => 2000,
            'ref_id' => 0,
            'auto_commit' => true
        ];

        $client->request('PUT', '/api/user/2/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        // balance = -1000
        $parameters = [
            'at' => '2012-12-01 12:00:00',
            'opcode' => 1052,
            'memo' => 'testMemo',
            'amount' => -2000,
            'ref_id' => 0,
            'auto_commit' => true
        ];

        $client->request('PUT', '/api/user/2/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $targetId1 = $ret['ret']['entries'][0]['id'];     // 目標值

        // balance = -3000
        $parameters = [
            'at' => '2012-12-01 12:00:00',
            'opcode' => 1052,
            'memo' => 'testMemo',
            'amount' => -2000,
            'ref_id' => 0,
            'auto_commit' => true
        ];

        $client->request('PUT', '/api/user/2/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        // balance = -500
        $parameters = [
            'at' => '2012-12-02 12:00:00',
            'opcode' => 1052,
            'amount' => -3000,
            'ref_id' => 0,
            'auto_commit' => true
        ];

        $client->request('PUT', '/api/user/3/cash_fake/op', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $targetId2 = $ret['ret']['entries'][0]['id'];     // 目標值

        $this->assertEquals('ok', $ret['result']);

        // 跑背景程式讓queue被消化
        $params = [
            '--entry' => true,
            '--balance' => true,
            '--history' => true
        ];
        $this->runCommand('durian:sync-cash-fake', $params);

        $em = $this->getContainer()->get('doctrine.orm.his_entity_manager');

        // 修改infobright的資料，用來檢查搜尋的資料庫是否為infobright
        $entry = $em->getRepository('BBDurianBundle:CashFakeEntry')->findOneBy(['id' => 1003]);
        $entry->setRefId(12345);

        $em->flush();

        $start = new \DateTime('now');
        $start = $start->sub(new \DateInterval('P46D'));
        $end = new \DateTime('now');

        // 查詢負數的明細，由於開始時間距今超過45天，會搜尋infobright
        $parameters = [
            'user_id' => [1, 2, 3, 4, 99],
            'start' => $start->format(\DateTime::ISO8601),
            'end' => $end->format(\DateTime::ISO8601)
        ];

        $client->request('GET', '/api/user/cash_fake/negative_entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        // cashfake id 1
        $this->assertEquals($targetId1, $ret['ret'][0]['id']);
        $this->assertEquals(1, $ret['ret'][0]['cash_fake_id']);
        $this->assertEquals(2, $ret['ret'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret'][0]['currency']);
        $this->assertEquals(1052, $ret['ret'][0]['opcode']);
        $this->assertEquals('-2000', $ret['ret'][0]['amount']);
        $this->assertEquals('-1000', $ret['ret'][0]['balance']);
        $this->assertEquals(12345, $ret['ret'][0]['ref_id']);

        // cashfake id 2
        $this->assertEquals($targetId2, $ret['ret'][1]['id']);
        $this->assertEquals(2, $ret['ret'][1]['cash_fake_id']);
        $this->assertEquals(3, $ret['ret'][1]['user_id']);
        $this->assertEquals('CNY', $ret['ret'][1]['currency']);
        $this->assertEquals(1052, $ret['ret'][1]['opcode']);
        $this->assertEquals('-3000', $ret['ret'][1]['amount']);
        $this->assertEquals('-500', $ret['ret'][1]['balance']);
        $this->assertEquals('', $ret['ret'][1]['ref_id']);

        // cashfake id 3
        $this->assertEquals(false, isset($ret['ret'][2]));

        $start = new \DateTime('now');
        $start = $start->sub(new \DateInterval('PT1H'));
        $end = new \DateTime('now');

        // 查詢負數的明細，由於開始時間距今不超過45天，會搜尋原資料庫
        $parameters = [
            'user_id' => [1, 2, 3, 4, 99],
            'start' => $start->format(\DateTime::ISO8601),
            'end' => $end->format(\DateTime::ISO8601)
        ];

        $client->request('GET', '/api/user/cash_fake/negative_entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        // cashfake id 1
        $this->assertEquals($targetId1, $ret['ret'][0]['id']);
        $this->assertEquals(1, $ret['ret'][0]['cash_fake_id']);
        $this->assertEquals(2, $ret['ret'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret'][0]['currency']);
        $this->assertEquals(1052, $ret['ret'][0]['opcode']);
        $this->assertEquals('-2000', $ret['ret'][0]['amount']);
        $this->assertEquals('-1000', $ret['ret'][0]['balance']);
        $this->assertEquals('', $ret['ret'][0]['ref_id']);

        // cashfake id 2
        $this->assertEquals($targetId2, $ret['ret'][1]['id']);
        $this->assertEquals(2, $ret['ret'][1]['cash_fake_id']);
        $this->assertEquals(3, $ret['ret'][1]['user_id']);
        $this->assertEquals('CNY', $ret['ret'][1]['currency']);
        $this->assertEquals(1052, $ret['ret'][1]['opcode']);
        $this->assertEquals('-3000', $ret['ret'][1]['amount']);
        $this->assertEquals('-500', $ret['ret'][1]['balance']);
        $this->assertEquals('', $ret['ret'][1]['ref_id']);

        // cashfake id 3
        $this->assertEquals(false, isset($ret['ret'][2]));

        // 查詢無假現金的負數明細
        $parameters = [
            'user_id' => [10],
            'start' => $start->format(\DateTime::ISO8601),
            'end' => $end->format(\DateTime::ISO8601)
        ];

        $client->request('GET', '/api/user/cash_fake/negative_entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
    }

    /**
     * 測試回傳負數餘額與第一筆導致額度為負的明細
     */
    public function testGetNegative()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $cashFake = $em->find('BBDurianBundle:CashFake', 6);
        $cashFake->setBalance(-1);
        $cashFake->setNegative(true);
        $em->flush();

        $res = $this->getResponse('GET', '/api/cash_fake/negative', ['first_result' => 0, 'max_results' => 1]);

        $this->assertEquals('ok', $res['result']);
        $this->assertEquals(1, $res['ret'][0]['cash_fake']['id']);
        $this->assertEquals(7, $res['ret'][0]['cash_fake']['user_id']);
        $this->assertEquals('CNY', $res['ret'][0]['cash_fake']['currency']);
        $this->assertEquals(-1, $res['ret'][0]['cash_fake']['balance']);
        $this->assertEquals(3, $res['ret'][0]['entry']['id']);
        $this->assertEquals(1, $res['ret'][0]['entry']['cash_fake_id']);
        $this->assertEquals(7, $res['ret'][0]['entry']['user_id']);
        $this->assertEquals('CNY', $res['ret'][0]['entry']['currency']);
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

    /**
     * 測試轉帳至沙巴
     */
    public function testTransferSabah()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 8);
        $cashFake = $user->getCashFake();

        $this->assertEquals(150, $cashFake->getBalance());
        $this->assertEquals(0, $cashFake->getPreSub());
        $this->assertEquals(0, $cashFake->getPreAdd());

        //測試SP人工提出
        $parameters = [
            'vendor' => 'SABAH',
            'amount' => -100,
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1001]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1002]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1046, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1047, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試SP人工存入
        $parameters = [
            'vendor' => 'SABAH',
            'amount' => 100,
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1003]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1004]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1044, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1045, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試SP API業主提出
        $parameters = [
            'vendor' => 'SABAH',
            'amount' => -100,
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1005]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1006]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1006, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1043, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試SP API業主存入
        $parameters = [
            'vendor' => 'SABAH',
            'amount' => 100,
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1007]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1008]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1042, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1007, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);
    }

    /**
     * 測試force_copy = tru轉帳至沙巴,refId為明細編號
     */
    public function testTransferSabahWithForceCopy()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 8);
        $cashFake = $user->getCashFake();

        $this->assertEquals(150, $cashFake->getBalance());
        $this->assertEquals(0, $cashFake->getPreSub());
        $this->assertEquals(0, $cashFake->getPreAdd());

        //測試SP人工提出
        $parameters = [
            'vendor' => 'SABAH',
            'amount' => -100,
            'ref_id' => '',
            'operator' => 'tester',
            'force_copy' => true
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1001]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1002]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1046, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1047, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('1001', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('1001', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試SP人工存入
        $parameters = [
            'vendor' => 'SABAH',
            'amount' => 100,
            'ref_id' => '',
            'operator' => 'tester',
            'force_copy' => true
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1003]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1004]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1044, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1045, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('1003', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('1003', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試SP API業主提出
        $parameters = [
            'vendor' => 'SABAH',
            'amount' => -100,
            'operator' => 'tester',
            'api_owner' => 1,
            'force_copy' => true
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);;

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1005]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1006]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1006, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1043, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);
        $this->assertEquals($ret['ret']['entry'][0]['ref_id'],  $ret['ret']['entry'][1]['ref_id']);

        //測試SP API業主存入
        $parameters = [
            'vendor' => 'SABAH',
            'amount' => 100,
            'operator' => 'tester',
            'api_owner' => 1,
            'force_copy' => true
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1007]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1008]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1042, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1007, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);
        $this->assertEquals($ret['ret']['entry'][0]['ref_id'],  $ret['ret']['entry'][1]['ref_id']);

       //沙巴存款測試非自動確認交易
        $parameters = [
            'vendor' => 'SABAH',
            'amount' => 100,
            'operator' => 'tester',
            'api_owner' => 1,
            'force_copy' => true,
            'auto_commit' => 0
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $em->refresh($cashFake);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals(100, $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals(100, $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals(1009, $ret['ret']['entry'][0]['id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1042, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals(1007, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(1010, $ret['ret']['entry'][1]['id']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

       //沙巴提款測試非自動確認交易
        $parameters = [
            'vendor' => 'SABAH',
            'amount' => -100,
            'operator' => 'tester',
            'api_owner' => 1,
            'force_copy' => true,
            'auto_commit' => 0
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $em->refresh($cashFake);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals(200, $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals(200, $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals(1011, $ret['ret']['entry'][0]['id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1006, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals(1043, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals(1012, $ret['ret']['entry'][1]['id']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);
    }

    /**
     * 測試轉帳至AG視訊
     */
    public function testTransferAg()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 8);
        $cashFake = $user->getCashFake();

        $this->assertEquals(150, $cashFake->getBalance());
        $this->assertEquals(0, $cashFake->getPreSub());
        $this->assertEquals(0, $cashFake->getPreAdd());

        //測試AG人工提出
        $parameters = [
            'vendor' => 'AG',
            'amount' => -100,
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1001]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1002]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1078, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1079, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試AG人工存入
        $parameters = [
            'vendor' => 'AG',
            'amount' => 100,

            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1003]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1004]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1076, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1077, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試AG API業主提出
        $parameters = [
            'vendor' => 'AG',
            'amount' => -100,
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1005]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1006]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1074, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1043, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試AG API業者存入
        $parameters = [
            'vendor' => 'AG',
            'amount' => 100,
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1007]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1008]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1042, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1075, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);
    }

    /**
     * 測試轉帳至PT
     */
    public function testTransferToPT()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 8);
        $cashFake = $user->getCashFake();

        $this->assertEquals(150, $cashFake->getBalance());
        $this->assertEquals(0, $cashFake->getPreSub());
        $this->assertEquals(0, $cashFake->getPreAdd());

        //測試PT人工提出
        $parameters = [
            'vendor' => 'PT',
            'amount' => -100,
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1001]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1002]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1089, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1090, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試PT人工存入
        $parameters = [
            'vendor' => 'PT',
            'amount' => 100,
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1003]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1004]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1087, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1088, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試PT API業主提出
        $parameters = [
            'vendor' => 'PT',
            'amount' => -100,
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1005]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1006]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1085, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1043, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試PT API業主存入
        $parameters = [
            'vendor' => 'PT',
            'amount' => 100,
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1007]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1008]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1042, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1086, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);
    }

    /**
     * 測試轉帳至歐博視訊
     */
    public function testTransferToAB()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 8);
        $cashFake = $user->getCashFake();

        $this->assertEquals(150, $cashFake->getBalance());
        $this->assertEquals(0, $cashFake->getPreSub());
        $this->assertEquals(0, $cashFake->getPreAdd());

        //測試AB人工提出
        $parameters = [
            'vendor' => 'AB',
            'amount' => -100,
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1001]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1002]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1106, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1107, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試AB人工存入
        $parameters = [
            'vendor' => 'AB',
            'amount' => 100,
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1003]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1004]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1104, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1105, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試AB API業主提出
        $parameters = [
            'vendor' => 'AB',
            'amount' => -100,
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1001]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1002]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1102, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1043, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試AB API業主存入
        $parameters = [
            'vendor' => 'AB',
            'amount' => 100,
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1003]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1004]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1042, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1103, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);
    }

    /**
     * 測試轉帳至MG電子
     */
    public function testTransferToMG()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 8);
        $cashFake = $user->getCashFake();

        $this->assertEquals(150, $cashFake->getBalance());
        $this->assertEquals(0, $cashFake->getPreSub());
        $this->assertEquals(0, $cashFake->getPreAdd());

        //測試MG人工提出
        $parameters = [
            'vendor' => 'MG',
            'amount' => -100,
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1001]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1002]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1114, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1115, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試MG人工存入
        $parameters = [
            'vendor' => 'MG',
            'amount' => 100,
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1003]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1004]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1112, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1113, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試MG API業主提出
        $parameters = [
            'vendor' => 'MG',
            'amount' => -100,
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1005]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1006]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1110, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1043, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試MG API業主存入
        $parameters = [
            'vendor' => 'MG',
            'amount' => 100,
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1007]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1008]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1042, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1111, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);
    }

    /**
     * 測試轉帳至東方視訊
     */
    public function testTransferToOG()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 8);
        $cashFake = $user->getCashFake();

        $this->assertEquals(150, $cashFake->getBalance());
        $this->assertEquals(0, $cashFake->getPreSub());
        $this->assertEquals(0, $cashFake->getPreAdd());

        //測試OG人工提出
        $parameters = [
            'vendor' => 'OG',
            'amount' => -100,
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1001]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1002]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1122, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1123, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試OG人工存入
        $parameters = [
            'vendor' => 'OG',
            'amount' => 100,
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1003]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1004]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1120, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1121, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試OG API業主提出
        $parameters = [
            'vendor' => 'OG',
            'amount' => -100,
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1005]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1006]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1118, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1043, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試OG API業主存入
        $parameters = [
            'vendor' => 'OG',
            'amount' => 100,
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1007]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1008]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1042, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1119, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);
    }

    /**
     * 測試轉帳至GD視訊
     */
    public function testTransferToGD()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 8);
        $cashFake = $user->getCashFake();

        $this->assertEquals(150, $cashFake->getBalance());
        $this->assertEquals(0, $cashFake->getPreSub());
        $this->assertEquals(0, $cashFake->getPreAdd());

        //測試GD人工提出
        $parameters = [
            'vendor' => 'GD',
            'amount' => -100,
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1001]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1002]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1141, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1142, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試GD人工存入
        $parameters = [
            'vendor' => 'GD',
            'amount' => 100,
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1003]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1004]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1148, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1140, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試GD API業主提出
        $parameters = [
            'vendor' => 'GD',
            'amount' => -100,
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1001]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1002]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1146, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1043, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試GD API業主存入
        $parameters = [
            'vendor' => 'GD',
            'amount' => 100,
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1003]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1004]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1042, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1147, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);
    }

    /**
     * 測試轉帳至Gns機率
     */
    public function testTransferToGns()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 8);
        $cashFake = $user->getCashFake();

        $this->assertEquals(150, $cashFake->getBalance());
        $this->assertEquals(0, $cashFake->getPreSub());
        $this->assertEquals(0, $cashFake->getPreAdd());

        //測試Gns人工提出
        $parameters = [
            'vendor' => 'Gns',
            'amount' => -100,
             'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1001]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1002]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1163, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1164, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試Gns人工存入
        $parameters = [
            'vendor' => 'Gns',
            'amount' => 100,
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1003]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1004]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1161, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1162, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試Gns API業主提出
        $parameters = [
            'vendor' => 'Gns',
            'amount' => -100,
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1005]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1006]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1159, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1043, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試Gns API業主存入
        $parameters = [
            'vendor' => 'Gns',
            'amount' => 100,
            'ref_id' => 123456789,
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1007]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1008]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1042, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1160, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);
    }

    /**
     * 測試轉帳至ISB電子
     */
    public function testTransferToISB()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 8);
        $cashFake = $user->getCashFake();

        $this->assertEquals(150, $cashFake->getBalance());
        $this->assertEquals(0, $cashFake->getPreSub());
        $this->assertEquals(0, $cashFake->getPreAdd());

        //測試ISB人工提出
        $parameters = [
            'vendor' => 'ISB',
            'amount' => -100,
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1001]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1002]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1183, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1184, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試ISB人工存入
        $parameters = [
            'vendor' => 'ISB',
            'amount' => 100,
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1003]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1004]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1181, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1182, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試ISB API業主提出
        $parameters = [
            'vendor' => 'ISB',
            'amount' => -100,
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1005]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1006]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1179, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1043, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試ISB API業主存入
        $parameters = [
            'vendor' => 'ISB',
            'amount' => 100,
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1007]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1008]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1042, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1180, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);
    }

    /**
     * 測試轉帳至888捕魚
     */
    public function testTransferTo888()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 8);
        $cashFake = $user->getCashFake();

        $this->assertEquals(150, $cashFake->getBalance());
        $this->assertEquals(0, $cashFake->getPreSub());
        $this->assertEquals(0, $cashFake->getPreAdd());


        //測試888人工提出
        $parameters = [
            'vendor' => '888',
            'amount' => -100,
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1001]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1002]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1218, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1219, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試888人工存入
        $parameters = [
            'vendor' => '888',
            'amount' => 100,
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1003]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1004]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1216, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1217, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試888 API業主提出
        $parameters = [
            'vendor' => '888',
            'amount' => -100,
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1005]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1006]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1214, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1043, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試888 API業主存入
        $parameters = [
            'vendor' => '888',
            'amount' => 100,
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1007]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1008]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1042, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1215, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);
    }

    /**
     * 測試轉帳至HB電子
     */
    public function testTransferToHB()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 8);
        $cashFake = $user->getCashFake();

        $this->assertEquals(150, $cashFake->getBalance());
        $this->assertEquals(0, $cashFake->getPreSub());
        $this->assertEquals(0, $cashFake->getPreAdd());

        //測試HB電子人工提出
        $parameters = [
            'vendor' => 'HB',
            'amount' => -100,
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1001]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1002]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1256, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1257, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試HB電子人工存入
        $parameters = [
            'vendor' => 'HB',
            'amount' => 100,
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1003]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1004]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1254, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1255, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試HB電子 API業主提出
        $parameters = [
            'vendor' => 'HB',
            'amount' => -100,
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1005]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1006]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1252, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1043, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試HB電子 API業主存入
        $parameters = [
            'vendor' => 'HB',
            'amount' => 100,
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1007]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1008]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1042, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1253, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);
    }

    /**
     * 測試轉帳至BG視訊
     */
    public function testTransferToBG()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 8);
        $cashFake = $user->getCashFake();

        $this->assertEquals(150, $cashFake->getBalance());
        $this->assertEquals(0, $cashFake->getPreSub());
        $this->assertEquals(0, $cashFake->getPreAdd());

        //測試BG視訊人工提出
        $parameters = [
            'vendor' => 'BG',
            'amount' => -100,
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1001]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1002]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1270, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1271, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試BG視訊人工存入
        $parameters = [
            'vendor' => 'BG',
            'amount' => 100,
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1003]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1004]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1268, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1269, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試BG視訊 API業主提出
        $parameters = [
            'vendor' => 'BG',
            'amount' => -100,
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1005]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1006]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1266, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1043, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試BG視訊 API業主存入
        $parameters = [
            'vendor' => 'BG',
            'amount' => 100,
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1007]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1008]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1042, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1267, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);
    }

    /**
     * 測試轉帳至PP電子
     */
    public function testTransferToPP()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 8);
        $cashFake = $user->getCashFake();

        $this->assertEquals(150, $cashFake->getBalance());
        $this->assertEquals(0, $cashFake->getPreSub());
        $this->assertEquals(0, $cashFake->getPreAdd());

        //測試PP電子人工提出
        $parameters = [
            'vendor' => 'PP',
            'amount' => -100,
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1001]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1002]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1280, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1281, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試PP電子人工存入
        $parameters = [
            'vendor' => 'PP',
            'amount' => 100,
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1003]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1004]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1278, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1279, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試PP電子 API業主提出
        $parameters = [
            'vendor' => 'PP',
            'amount' => -100,
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1005]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1006]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1276, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1043, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試PP電子 API業主存入
        $parameters = [
            'vendor' => 'PP',
            'amount' => 100,
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1007]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1008]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1042, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1277, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);
    }

    /**
     * 測試轉帳至JDB電子
     */
    public function testTransferToJDB()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 8);
        $cashFake = $user->getCashFake();

        $this->assertEquals(150, $cashFake->getBalance());
        $this->assertEquals(0, $cashFake->getPreSub());
        $this->assertEquals(0, $cashFake->getPreAdd());

        //測試JDB電子人工提出
        $parameters = [
            'vendor' => 'JDB',
            'amount' => -100,
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1001]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1002]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1298, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1299, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試JDB電子人工存入
        $parameters = [
            'vendor' => 'JDB',
            'amount' => 100,
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1003]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1004]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1296, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1297, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試JDB電子 API業主提出
        $parameters = [
            'vendor' => 'JDB',
            'amount' => -100,
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1005]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1006]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1294, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1043, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試JDB電子 API業主存入
        $parameters = [
            'vendor' => 'JDB',
            'amount' => 100,
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1007]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1008]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1042, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1295, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);
    }

     /**
     * 測試轉帳至AG電子
     */
    public function testTransferToAgCasino()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 8);
        $cashFake = $user->getCashFake();

        $this->assertEquals(150, $cashFake->getBalance());
        $this->assertEquals(0, $cashFake->getPreSub());
        $this->assertEquals(0, $cashFake->getPreAdd());

        //測試AG電子人工提出
        $parameters = [
            'vendor' => 'AG_CASINO',
            'amount' => -100,
            'memo' => 'test',
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1001]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1002]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1306, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1307, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals('test', $ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals('test', $ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試AG電子人工存入
        $parameters = [
            'vendor' => 'AG_CASINO',
            'amount' => 100,
            'memo' => 'test',
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1003]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1004]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1304, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1305, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals('test', $ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals('test', $ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試AG電子API業主提出
        $parameters = [
            'vendor' => 'AG_CASINO',
            'amount' => -100,
            'memo' => 'test',
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1005]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1006]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1302, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1043, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals('test', $ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals('test', $ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試AG電子API業主存入
        $parameters = [
            'vendor' => 'AG_CASINO',
            'amount' => 100,
            'memo' => 'test',
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1007]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1008]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1042, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1303, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals('test', $ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals('test', $ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);
    }

    /**
     * 測試轉帳至MW電子
     */
    public function testTransferToMwCasino()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 8);
        $cashFake = $user->getCashFake();

        $this->assertEquals(150, $cashFake->getBalance());
        $this->assertEquals(0, $cashFake->getPreSub());
        $this->assertEquals(0, $cashFake->getPreAdd());

        //測試MW電子人工提出
        $parameters = [
            'vendor' => 'MW',
            'amount' => -100,
            'memo' => 'test',
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1001]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1002]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1314, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1315, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals('test', $ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals('test', $ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試MW電子人工存入
        $parameters = [
            'vendor' => 'MW',
            'amount' => 100,
            'memo' => 'test',
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1003]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1004]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1312, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1313, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals('test', $ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals('test', $ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試MW電子API業主提出
        $parameters = [
            'vendor' => 'MW',
            'amount' => -100,
            'memo' => 'test',
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1005]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1006]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1310, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1043, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals('test', $ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals('test', $ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試MW電子API業主存入
        $parameters = [
            'vendor' => 'MW',
            'amount' => 100,
            'memo' => 'test',
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1007]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1008]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1042, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1311, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals('test', $ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals('test', $ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);
    }

    /**
     * 測試轉帳至RT電子
     */
    public function testTransferToRTCasino()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 8);
        $cashFake = $user->getCashFake();

        $this->assertEquals(150, $cashFake->getBalance());
        $this->assertEquals(0, $cashFake->getPreSub());
        $this->assertEquals(0, $cashFake->getPreAdd());

        //測試RT電子人工提出
        $parameters = [
            'vendor' => 'RT',
            'amount' => -100,
            'memo' => 'test',
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1001]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1002]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1358, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1359, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals('test', $ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals('test', $ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試RT電子人工存入
        $parameters = [
            'vendor' => 'RT',
            'amount' => 100,
            'memo' => 'test',
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1003]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1004]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1356, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1357, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals('test', $ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals('test', $ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試RT電子API業主提出
        $parameters = [
            'vendor' => 'RT',
            'amount' => -100,
            'memo' => 'test',
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1005]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1006]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1354, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1043, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals('test', $ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals('test', $ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試RT電子API業主存入
        $parameters = [
            'vendor' => 'RT',
            'amount' => 100,
            'memo' => 'test',
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1007]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1008]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1042, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1355, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals('test', $ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals('test', $ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);
    }

    /**
     * 測試轉帳至SG電子
     */
    public function testTransferToSGCasino()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 8);
        $cashFake = $user->getCashFake();

        $this->assertEquals(150, $cashFake->getBalance());
        $this->assertEquals(0, $cashFake->getPreSub());
        $this->assertEquals(0, $cashFake->getPreAdd());

        //測試SG電子人工提出
        $parameters = [
            'vendor' => 'SG',
            'amount' => -100,
            'memo' => 'test',
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1001]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1002]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1366, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1367, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals('test', $ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals('test', $ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試SG電子人工存入
        $parameters = [
            'vendor' => 'SG',
            'amount' => 100,
            'memo' => 'test',
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1003]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1004]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1364, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1365, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals('test', $ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals('test', $ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試SG電子API業主提出
        $parameters = [
            'vendor' => 'SG',
            'amount' => -100,
            'memo' => 'test',
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1005]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1006]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1362, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1043, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals('test', $ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals('test', $ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試SG電子API業主存入
        $parameters = [
            'vendor' => 'SG',
            'amount' => 100,
            'memo' => 'test',
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1007]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1008]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1042, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1363, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals('test', $ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals('test', $ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);
    }

    /**
     * 測試轉帳至VR彩票
     */
    public function testTransferToVRLottery()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 8);
        $cashFake = $user->getCashFake();

        $this->assertEquals(150, $cashFake->getBalance());
        $this->assertEquals(0, $cashFake->getPreSub());
        $this->assertEquals(0, $cashFake->getPreAdd());

        //測試VR彩票人工提出
        $parameters = [
            'vendor' => 'VR',
            'amount' => -100,
            'memo' => 'test',
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1001]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1002]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1375, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1376, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals('test', $ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals('test', $ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試VR彩票人工存入
        $parameters = [
            'vendor' => 'VR',
            'amount' => 100,
            'memo' => 'test',
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1003]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1004]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1373, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1374, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals('test', $ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals('test', $ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試VR彩票API業主提出
        $parameters = [
            'vendor' => 'VR',
            'amount' => -100,
            'memo' => 'test',
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1005]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1006]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1371, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1043, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals('test', $ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals('test', $ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試VR彩票API業主存入
        $parameters = [
            'vendor' => 'VR',
            'amount' => 100,
            'memo' => 'test',
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1007]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1008]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1042, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1372, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals('test', $ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals('test', $ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);
    }

    /**
     * 測試轉帳至PTⅡ電子
     */
    public function testTransferToSWCasino()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 8);
        $cashFake = $user->getCashFake();

        $this->assertEquals(150, $cashFake->getBalance());
        $this->assertEquals(0, $cashFake->getPreSub());
        $this->assertEquals(0, $cashFake->getPreAdd());

        //測試PTⅡ電子人工提出
        $parameters = [
            'vendor' => 'PTⅡ',
            'amount' => -100,
            'memo' => 'test',
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1001]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1002]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1417, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1418, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals('test', $ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals('test', $ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試PTⅡ電子人工存入
        $parameters = [
            'vendor' => 'PTⅡ',
            'amount' => 100,
            'memo' => 'test',
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1003]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1004]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1415, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1416, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals('test', $ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals('test', $ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試PTⅡ電子API業主提出
        $parameters = [
            'vendor' => 'PTⅡ',
            'amount' => -100,
            'memo' => 'test',
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1005]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1006]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1413, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1043, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals('test', $ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals('test', $ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試PTⅡ電子API業主存入
        $parameters = [
            'vendor' => 'PTⅡ',
            'amount' => 100,
            'memo' => 'test',
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1007]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1008]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1042, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1414, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals('test', $ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals('test', $ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);
    }

    /**
     * 測試轉帳至EVO視訊
     */
    public function testTransferToEVO()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 8);
        $cashFake = $user->getCashFake();

        $this->assertEquals(150, $cashFake->getBalance());
        $this->assertEquals(0, $cashFake->getPreSub());
        $this->assertEquals(0, $cashFake->getPreAdd());

        //測試EVO視訊人工提出
        $parameters = [
            'vendor' => 'EVO',
            'amount' => -100,
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1001]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1002]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1401, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1402, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試EVO視訊人工存入
        $parameters = [
            'vendor' => 'EVO',
            'amount' => 100,
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1003]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1004]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1399, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1400, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試EVO視訊 API業主提出
        $parameters = [
            'vendor' => 'EVO',
            'amount' => -100,
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1005]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1006]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1397, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1043, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試EVO視訊 API業主存入
        $parameters = [
            'vendor' => 'EVO',
            'amount' => 100,
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1007]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1008]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1042, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1398, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEmpty($ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEmpty($ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);
    }

    /**
     * 測試轉帳至BNG電子
     */
    public function testTransferToBNGCasino()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 8);
        $cashFake = $user->getCashFake();

        $this->assertEquals(150, $cashFake->getBalance());
        $this->assertEquals(0, $cashFake->getPreSub());
        $this->assertEquals(0, $cashFake->getPreAdd());

        //測試BNG電子人工提出
        $parameters = [
            'vendor' => 'BNG',
            'amount' => -100,
            'memo' => 'test',
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1001]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1002]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1409, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1410, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals('test', $ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals('test', $ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試BNG電子人工存入
        $parameters = [
            'vendor' => 'BNG',
            'amount' => 100,
            'memo' => 'test',
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1003]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1004]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1407, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1408, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals('test', $ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals('test', $ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試BNG電子API業主提出
        $parameters = [
            'vendor' => 'BNG',
            'amount' => -100,
            'memo' => 'test',
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1005]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1006]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1405, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1043, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals('test', $ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals('test', $ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試BNG電子API業主存入
        $parameters = [
            'vendor' => 'BNG',
            'amount' => 100,
            'memo' => 'test',
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1007]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1008]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1042, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1406, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals('test', $ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals('test', $ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);
    }

    /**
     * 測試轉帳至開元 棋牌
     */
    public function testTransferToKYGamingCardGame()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 8);
        $cashFake = $user->getCashFake();

        $this->assertEquals(150, $cashFake->getBalance());
        $this->assertEquals(0, $cashFake->getPreSub());
        $this->assertEquals(0, $cashFake->getPreAdd());

        //測試開元 棋牌人工提出
        $parameters = [
            'vendor' => 'KY',
            'amount' => -100,
            'memo' => 'test',
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1001]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1002]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1442, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1443, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals('test', $ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals('test', $ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試開元 棋牌人工存入
        $parameters = [
            'vendor' => 'KY',
            'amount' => 100,
            'memo' => 'test',
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1003]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1004]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1440, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1441, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals('test', $ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals('test', $ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試開元 棋牌API業主提出
        $parameters = [
            'vendor' => 'KY',
            'amount' => -100,
            'memo' => 'test',
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1005]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1006]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1438, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1043, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals('test', $ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals('test', $ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試開元 棋牌API業主存入
        $parameters = [
            'vendor' => 'KY',
            'amount' => 100,
            'memo' => 'test',
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1007]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1008]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1042, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1439, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals('test', $ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals('test', $ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);
    }

    /**
     * 測試轉帳至WM電子
     */
    public function testTransferToWMGamingCardGame()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 8);
        $cashFake = $user->getCashFake();

        $this->assertEquals(150, $cashFake->getBalance());
        $this->assertEquals(0, $cashFake->getPreSub());
        $this->assertEquals(0, $cashFake->getPreAdd());

        //測試WM電子人工提出
        $parameters = [
            'vendor' => 'WM',
            'amount' => -100,
            'memo' => 'test',
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1001]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1002]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1456, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1457, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals('test', $ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals('test', $ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試WM電子人工存入
        $parameters = [
            'vendor' => 'WM',
            'amount' => 100,
            'memo' => 'test',
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1003]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1004]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1454, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1455, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals('test', $ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals('test', $ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('123456789', $ret['ret']['entry'][1]['ref_id']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試WM電子API業主提出
        $parameters = [
            'vendor' => 'WM',
            'amount' => -100,
            'memo' => 'test',
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryOne = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1005]);
        $entryTwo = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1006]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1452, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1043, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals('test', $ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryOne[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals('test', $ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryTwo[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);

        //測試WM電子API業主存入
        $parameters = [
            'vendor' => 'WM',
            'amount' => 100,
            'memo' => 'test',
            'operator' => 'tester',
            'api_owner' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:sync-cash-fake', ['--entry' => 1, '--balance' => 1]);

        $entryThree = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1007]);
        $entryFour = $em->getRepository('BBDurianBundle:CashFakeEntry')->findBy(['id' => 1008]);

        $em->refresh($cashFake);

        $this->assertEquals($cashFake->getId(), $ret['ret']['cash_fake']['id']);
        $this->assertEquals($cashFake->getBalance(), $ret['ret']['cash_fake']['balance']);
        $this->assertEquals($cashFake->getPreSub(), $ret['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashFake->getPreAdd(), $ret['ret']['cash_fake']['pre_add']);
        $this->assertEquals(8, $ret['ret']['entry'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret']['entry'][0]['currency']);
        $this->assertEquals(1042, $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(100, $ret['ret']['entry'][0]['amount']);
        $this->assertEquals(1453, $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(-100, $ret['ret']['entry'][1]['amount']);
        $this->assertEquals('test', $ret['ret']['entry'][0]['memo']);
        $this->assertEquals($entryThree[0]->getMemo(), $ret['ret']['entry'][0]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][0]['operator']['username']);
        $this->assertEquals('test', $ret['ret']['entry'][1]['memo']);
        $this->assertEquals($entryFour[0]->getMemo(), $ret['ret']['entry'][1]['memo']);
        $this->assertEquals('tester', $ret['ret']['entry'][1]['operator']['username']);
    }

    /**
     * 測試轉帳該使用者沒有cashFake
     */
    public function testTransferOutUserWithoutCashFake()
    {
        $client = $this->createClient();

        $parameters = [
            'vendor' => 'SABAH',
            'amount' => 100
        ];

        $client->request('PUT', '/api/user/10/cash_fake/transfer_out', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150050048, $output['code']);
        $this->assertEquals('No cashFake found', $output['msg']);
    }

    /*
     * 測試使用者做api轉入轉出記錄queue
     */
    public function testUserApiTransferInOutQueue()
    {
        $redis  = $this->getContainer()->get('snc_redis.default');
        $client = $this->createClient();

        $parameters = [
            'opcode' => 1042,
            'amount' => 1
        ];

        $client->request('PUT', '/api/user/8/cash_fake/op', $parameters);

        $queueName = 'cash_fake_api_transfer_in_out_queue';
        $this->assertEquals(1, $redis->llen($queueName));

        $queue = json_decode($redis->rpop($queueName), true);
        $this->assertEquals(8, $queue['user_id']);
        $this->assertTrue($queue['api_transfer_in']);
    }

    /**
     * 測試使用者兩階段交易做api轉入轉出記錄queue
     */
    public function testUserApiTransferInOutQueueByCommit()
    {
        $redis  = $this->getContainer()->get('snc_redis.default');
        $client = $this->createClient();

        $parameters = [
            'opcode' => 1043,
            'amount' => 1,
            'auto_commit' => 0
        ];

        $client->request('PUT', '/api/user/8/cash_fake/op', $parameters);

        $queueName = 'cash_fake_api_transfer_in_out_queue';
        $this->assertEquals(0, $redis->llen($queueName));

        $client->request('PUT', '/api/cash_fake/transaction/1001/commit');

        $this->assertEquals(1, $redis->llen($queueName));

        $queue = json_decode($redis->rpop($queueName), true);
        $this->assertEquals(8, $queue['user_id']);
        $this->assertTrue($queue['api_transfer_out']);
    }

    /**
     * 測試寶馬使用者轉移額度時，進api轉入轉出記錄queue
     */
    public function testUserApiTransferInOutQueueByDomain1AndOpcode1003()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setDomain(1);
        $em->flush();

        $redis  = $this->getContainer()->get('snc_redis.default');
        $client = $this->createClient();

        $parameters = [
            'opcode' => 1003,
            'amount' => 1,
            'auto_commit' => 1
        ];

        $queueName = 'cash_fake_api_transfer_in_out_queue';
        $client->request('PUT', '/api/user/8/cash_fake/op', $parameters);

        $this->assertEquals(2, $redis->llen($queueName));

        $queue = json_decode($redis->rpop($queueName), true);
        $this->assertEquals(8, $queue['user_id']);
        $this->assertTrue($queue['api_transfer_in']);

        $queue = json_decode($redis->rpop($queueName), true);
        $this->assertEquals(7, $queue['user_id']);
        $this->assertTrue($queue['api_transfer_out']);
    }

    /**
     * 測試寶馬使用者轉移額度時，來源帳號與目標帳號都進api轉入轉出記錄queue
     */
    public function testUserApiTransferInOutQueueByDomain1Transfer()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setDomain(1);
        $user = $em->find('BBDurianBundle:User', 7);
        $user->setDomain(1);

        $em->flush();

        $redis = $this->getContainer()->get('snc_redis.default');
        $client = $this->createClient();

        $parameters = [
            'source' => 8,
            'target' => 7,
            'amount' => 10
        ];

        $queueName = 'cash_fake_api_transfer_in_out_queue';
        $client->request('PUT', '/api/cash_fake/transfer', $parameters);

        $this->assertEquals(2, $redis->llen($queueName));

        $queue = json_decode($redis->rpop($queueName), true);
        $this->assertEquals(7, $queue['user_id']);
        $this->assertTrue($queue['api_transfer_in']);

        $queue = json_decode($redis->rpop($queueName), true);
        $this->assertEquals(8, $queue['user_id']);
        $this->assertTrue($queue['api_transfer_out']);
    }

    /**
     * 測試寶馬使用者兩階段轉移額度時，來源帳號與目標帳號都進api轉入轉出記錄queue
     */
    public function testUserApiTransferInOutQueueByDomain1TransactionCommit()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setDomain(1);
        $user = $em->find('BBDurianBundle:User', 7);
        $user->setDomain(1);

        $em->flush();

        $redis = $this->getContainer()->get('snc_redis.default');
        $client = $this->createClient();

        $parameters = [
            'amount' => 10,
            'opcode' => 1003,
            'auto_commit' => 0
        ];
        $queueName = 'cash_fake_api_transfer_in_out_queue';

        $client->request('PUT', '/api/user/8/cash_fake/op', $parameters);

        $client->request('PUT', '/api/cash_fake/transaction/1001/commit');
        $client->request('PUT', '/api/cash_fake/transaction/1002/commit');

        $this->assertEquals(2, $redis->llen($queueName));

        $queue = json_decode($redis->rpop($queueName), true);
        $this->assertEquals(7, $queue['user_id']);
        $this->assertTrue($queue['api_transfer_out']);

        $queue = json_decode($redis->rpop($queueName), true);
        $this->assertEquals(8, $queue['user_id']);
        $this->assertTrue($queue['api_transfer_in']);
    }
}
