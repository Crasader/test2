<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\CreditPeriod;
use BB\DurianBundle\Entity\Credit;
use BB\DurianBundle\Entity\CreditEntry;

class CreditFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCreditData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCreditPeriodData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCreditEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserPaywayData'
        ];

        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadExchangeData'
        ];

        $this->loadFixtures($classnames, 'share');
    }

    /**
     * 測試新增額度
     */
    public function testNewCredit()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $repo = $em->getRepository('BBDurianBundle:Credit');
        $client = $this->createClient();

        //測試新增信用額度但上層沒有對應的額度
        $parameters = array(
            'balance'  => 200);

        $client->request('POST', '/api/user/8/credit/3', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('No parent credit found', $output['msg']);
        $this->assertEquals(150060010, $output['code']);

        //測試新增額度
        $parent = $em->find('BB\DurianBundle\Entity\User', 7);
        $credit = new \BB\DurianBundle\Entity\Credit($parent, 3);

        $em->persist($credit);
        $em->flush();

        $repo->addLine($credit->getId(), 500);
        $em->clear();

        $parameters = array(
            'balance'  => 200);

        $client->request('POST', '/api/user/8/credit/3', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $user = $em->find('BB\DurianBundle\Entity\User', 8);
        $credit = $user->getCredit(3);
        $this->assertEquals(200, $credit->getLine());

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals("credit", $logOperation->getTableName());
        $this->assertEquals("@user_id:8", $logOperation->getMajorKey());
        $this->assertEquals("@group_num:3, @line:200", $logOperation->getMessage());

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(10, $output['ret']['id']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals(3, $output['ret']['group']);
        $this->assertEquals(200, $output['ret']['line']);
        $this->assertTrue($output['ret']['enable']);

        $this->runCommand('durian:sync-credit', ['--credit' => 1]);

        // user_id = 7, group_num = 3
        $credit = $em->find('BBDurianBundle:Credit', 9);

        $this->assertEquals(200, $credit->getTotalLine());
    }

    /**
     * 測試重覆新增額度時丟例外上層會正常回復額度
     */
    public function testNewCreditWithErrorWillRollbackTotalline()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:Credit');
        $client = $this->createClient();

        $parent = $em->find('BBDurianBundle:User', 7);
        $credit = new Credit($parent, 3);
        $em->persist($credit);
        $em->flush();

        $repo->addLine($credit->getId(), 500);
        $em->clear();

        $parameters = ['balance' => 200];

        $client->request('POST', '/api/user/8/credit/3', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(10, $output['ret']['id']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals(3, $output['ret']['group']);
        $this->assertEquals(200, $output['ret']['line']);
        $this->assertTrue($output['ret']['enable']);

        $user = $em->find('BBDurianBundle:User', 8);
        $credit = $user->getCredit(3);
        $this->assertEquals(200, $credit->getLine());

        $this->runCommand('durian:sync-credit', ['--credit' => 1]);

        $credit = $em->find('BBDurianBundle:Credit', 9);
        $this->assertEquals(200, $credit->getTotalLine());
        $em->clear();

        $client->request('POST', '/api/user/8/credit/3', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010006, $output['code']);
        $this->assertEquals('Duplicate Credit', $output['msg']);

        $this->runCommand('durian:sync-credit', ['--credit' => 1]);

        $credit = $em->find('BBDurianBundle:Credit', 9);
        $this->assertEquals(200, $credit->getTotalLine());
    }

    /**
     * 測試新增額度但User慣用幣別為台幣
     */
    public function testNewCreditButCurrencyIsTwd()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:Credit');
        $client = $this->createClient();

        //測試新增額度
        $parent = $em->find('BB\DurianBundle\Entity\User', 7);
        $credit = new \BB\DurianBundle\Entity\Credit($parent, 3);

        $em->persist($credit);

        $user = $em->find('BB\DurianBundle\Entity\User', 8);
        $user->setCurrency(901); //把使用者慣用幣別改為台幣

        $em->flush();

        $repo->addLine($credit->getId(), 500);
        $em->clear();

        $parameters = array('balance' => 200);

        $client->request('POST', '/api/user/8/credit/3', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $credit = $user->getCredit(3);
        $this->assertEquals(44, $credit->getLine());

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(10, $output['ret']['id']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals(3, $output['ret']['group']);
        $this->assertEquals(197, $output['ret']['line']);
    }

    /**
     * 測試新增額度時，轉換額度後超過可用餘額，會回復原 total_line
     */
    public function testNewCreditWithNotEnoughLineAndRecoverTotalLine()
    {
        $redisWallet = $this->getContainer()->get('snc_redis.wallet1');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:Credit');
        $client = $this->createClient();

        $parent = $em->find('BBDurianBundle:User', 7);
        $credit = new \BB\DurianBundle\Entity\Credit($parent, 3);

        $em->persist($credit);

        $user = $em->find('BBDurianBundle:User', 8);
        $user->setCurrency(901); //把使用者慣用幣別改為台幣

        $em->flush();

        $repo->addLine($credit->getId(), 500);
        $em->clear();

        $parameters = ['balance' => 2250];
        $client->request('POST', '/api/user/8/credit/3', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150060041, $output['code']);
        $this->assertEquals('TotalLine is greater than parent credit', $output['msg']);

        $creditKey = 'credit_id_9';
        $totalLine = $redisWallet->hget($creditKey, 'total_line');

        // 確保不會因為 floor 而多 -1
        $this->assertEquals(0, $totalLine);
    }

    /**
     * 測試新增額度沒有上層和Payway
     */
    public function testNewCreditWithNoPaywayAndParentWillCreatePayway()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $payway = $em->find('BBDurianBundle:UserPayway', 2);
        $em->remove($payway);
        $em->flush();
        $em->clear();

        $parameters = ['balance' => 200];

        $client->request('POST', '/api/user/2/credit/3', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $payway = $em->find('BBDurianBundle:UserPayway', 2);
        $this->assertTrue($payway->isCreditEnabled());
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret']['user_id']);
        $this->assertEquals(3, $output['ret']['group']);
        $this->assertEquals(200, $output['ret']['balance']);
        $this->assertEquals(200, $output['ret']['line']);
        $this->assertTrue($output['ret']['enable']);
    }

    /**
     * 測試新增額度已修改上層後發生錯誤會rollback redis
     */
    public function testNewCreditWithNotEnoughLineAndRollBack()
    {
        $redisWallet = $this->getContainer()->get('snc_redis.wallet3');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:Credit');
        $client = $this->createClient();

        $parent = $em->find('BBDurianBundle:User', 7);
        $credit = new Credit($parent, 3);

        $em->persist($credit);
        $em->flush();

        $repo->addLine($credit->getId(), 500);
        $repo->addTotalLine($credit->getId(), 100);

        $parameters = ['balance' => -50];
        $client->request('POST', '/api/user/8/credit/3', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150060045, $output['code']);
        $this->assertEquals('Line is less than sum of children credit', $output['msg']);

        $parentTotalLine = $redisWallet->hget('credit_7_3', 'total_line');
        $this->assertEquals(100, $parentTotalLine);
    }

    /**
     * 測試新增額度但上層已停用
     */
    public function testNewCreditButParentDisabled()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:Credit');
        $client = $this->createClient();

        $parent = $em->find('BB\DurianBundle\Entity\User', 7);
        $credit = new \BB\DurianBundle\Entity\Credit($parent, 3);
        $credit->disable();

        $em->persist($credit);
        $em->flush();

        $repo->addLine($credit->getId(), 500);
        $em->clear();

        $parameters = array(
            'balance'  => 200);

        $client->request('POST', '/api/user/8/credit/3', $parameters);

        $user = $em->find('BB\DurianBundle\Entity\User', 8);
        $credit = $user->getCredit(3);
        $this->assertEquals(200, $credit->getLine());

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertFalse($output['ret']['enable']);
    }

    /**
     * 測試新增信用額度，會調整本身與下層的 payway
     */
    public function testNewCreditAndAdjustPayway()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $repo = $em->getRepository('BBDurianBundle:Credit');

        // 初始化資料
        $user = $em->find('BBDurianBundle:User', 2);
        $credit = new Credit($user, 1);
        $em->persist($credit);

        $payway = $em->find('BBDurianBundle:UserPayway', 3);
        $payway->disableCredit();

        $em->flush();

        $repo->addLine($credit->getId(), 5000);
        $em->clear();

        // 執行
        $params = ['balance' => 200];
        $client->request('POST', '/api/user/3/credit/1', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret']['user_id']);
        $this->assertEquals(1, $output['ret']['group']);
        $this->assertEquals(200, $output['ret']['line']);
        $this->assertEquals(200, $output['ret']['balance']);
        $this->assertTrue($output['ret']['enable']);

        // 檢查 payway
        $payway = $em->find('BBDurianBundle:UserPayway', 3);
        $this->assertTrue($payway->isCashEnabled());
        $this->assertFalse($payway->isCashFakeEnabled());
        $this->assertTrue($payway->isCreditEnabled());

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('user_payway', $logOperation->getTableName());
        $this->assertEquals('@user_id:3', $logOperation->getMajorKey());
        $this->assertEquals('@credit:false=>true', $logOperation->getMessage());

        $payway = $em->find('BBDurianBundle:UserPayway', 4);
        $this->assertTrue($payway->isCashEnabled());
        $this->assertFalse($payway->isCashFakeEnabled());
        $this->assertFalse($payway->isCreditEnabled());

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertEquals('user_payway', $logOperation->getTableName());
        $this->assertEquals('@user_id:4', $logOperation->getMajorKey());
        $this->assertEquals('@cash:true', $logOperation->getMessage());
    }

    /**
     * 測試回傳額度
     */
    public function testGetCreditByCreditId()
    {
        $redisWallet = $this->getContainer()->get('snc_redis.wallet4');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $param  = array('at' => '');

        $client->request('GET', '/api/credit/3', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(7, $output['ret']['user_id']);
        $this->assertEquals(1, $output['ret']['group']);
        $this->assertEquals(44843, $output['ret']['line']);
        $this->assertEquals(22421.52, $output['ret']['balance']);

        //上層停用是否下層也顯示停用
        $credit = $em->find('BB\DurianBundle\Entity\Credit', 1);
        $credit->disable();

        $em->flush();

        $client->request('GET', '/api/credit/3');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertFalse($output['ret']['enable']);

        $periodIndexKey = 'credit_period_index_8_1';
        $periodKey = 'credit_period_8_1_20110720';

        $client->request('GET', '/api/credit/5');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertFalse($output['ret']['enable']);
        $this->assertEquals(1, $output['ret']['group']);
        $this->assertEquals(5000, $output['ret']['line']);
        $this->assertEquals(5000, $output['ret']['balance']);

        $this->assertFalse($redisWallet->exists($periodIndexKey));
        $this->assertFalse($redisWallet->exists($periodKey));

        //測試抓過期的歷史period 是否會同步抓回 redis
        $param  = array('at' => '2011-07-20T12:00:00+0800');

        $client->request('GET', '/api/credit/5', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertFalse($output['ret']['enable']);
        $this->assertEquals(1, $output['ret']['group']);
        $this->assertEquals(5000, $output['ret']['line']);
        $this->assertEquals(4300, $output['ret']['balance']);

        $this->assertTrue($redisWallet->exists($periodIndexKey));
        $this->assertTrue($redisWallet->exists($periodKey));

        $this->assertEquals(700, $redisWallet->hget($periodKey, 'amount')/ 10000);
        $this->assertEquals('2011-07-20 00:00:00', $redisWallet->hget($periodKey, 'at'));
    }

    /**
     * 測試回傳額度但ID錯誤
     */
    public function testGetCreditByCreditIdWithCreditNotFound()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/credit/999');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150060001, $output['code']);
        $this->assertEquals('No credit found', $output['msg']);
    }

    /**
     * 測試回傳額度但信用額度正在回收
     */
    public function testGetCreditButIsBeingRecovering()
    {
        $client = $this->createClient();
        $param  = array('at' => '');

        $markName = 'credit_in_recovering';
        $redisWallet = $this->getContainer()->get('snc_redis.wallet1');
        $redisWallet->sadd($markName, ['7_1', '8_1', '10_1']);

        $client->request('GET', '/api/credit/3', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Credit is recovering, please try again', $output['msg']);
        $this->assertEquals(150060018, $output['code']);
    }

    /**
     * 測試回傳額度但信用額度正在轉移
     */
    public function testGetCreditButIsBeingTransfering()
    {
        $client = $this->createClient();
        $param  = array('at' => '');

        $markName = 'credit_in_transfering';
        $redisWallet = $this->getContainer()->get('snc_redis.wallet1');
        $redisWallet->sadd($markName, ['7_1', '8_1', '10_1']);

        $client->request('GET', '/api/credit/3', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Credit is transfering, please try again', $output['msg']);
        $this->assertEquals(150060021, $output['code']);
    }

    /**
     * 測試取得額度資訊
     */
    public function testGetCreditByUser()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = array(
            'users'  => array(7),
            'fields' => array('credit'),
        );

        $client->request('GET', '/api/users', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(7, $output['ret'][0]['id']);
        $this->assertEquals(44843, $output['ret'][0]['credit'][1]['line']);
        $this->assertTrue($output['ret'][0]['credit'][1]['enable']);

        //上層停用是否下層也顯示停用
        $credit = $em->find('BB\DurianBundle\Entity\Credit', 1);
        $credit->disable();

        $em->flush();

        $parameters = array(
            'users'  => array(7),
            'fields' => array('credit'),
        );

        $client->request('GET', '/api/users', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertFalse($output['ret'][0]['credit'][1]['enable']);
    }

    /**
     * 測試該使用者的所有信用額度資料
     */
    public function testGetAllCreditByUserId()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $client->request('GET', '/api/user/6/credit');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $credit = $em->find('BB\DurianBundle\Entity\Credit', $output['ret'][0]['id']);

        $this->assertEquals($credit->getLine(), $output['ret'][0]['line']);
        $this->assertEquals($credit->getBalance(), $output['ret'][0]['balance']);

        $credit = $em->find('BB\DurianBundle\Entity\Credit', $output['ret'][1]['id']);

        $this->assertEquals($credit->getLine(), $output['ret'][1]['line']);
        $this->assertEquals($credit->getBalance(), $output['ret'][1]['balance']);
    }

    /**
     * 測試該使用者的所有信用額度資料但該使用者無信用額度資料
     */
    public function testGetAllCreditByUserIdWithNoCredit()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/2/credit');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150060001, $output['code']);
        $this->assertEquals('No credit found', $output['msg']);
    }

    /**
     * 測試取得使用者的所有信用額度資料且該使用者不使用人民幣
     */
    public function testGetAllCreditByUserIdUserDontUseCNY()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/7/credit');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(44843, $output['ret'][0]['line']);
        $this->assertEquals(22421.52, $output['ret'][0]['balance']);
        $this->assertEquals(22421, $output['ret'][1]['line']);
        $this->assertEquals(8968.61, $output['ret'][1]['balance']);
    }

    /**
     * 測試依使用者ID及群組代碼取得一筆使用者的信用額度
     */
    public function testGetOneCreditByUserIdAndgroupNum()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $client->request('GET', '/api/user/6/credit/2');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $credit = $em->find('BB\DurianBundle\Entity\Credit', $output['ret']['id']);

        $this->assertEquals($credit->getLine(), $output['ret']['line']);
        $this->assertEquals($credit->getBalance(), $output['ret']['balance']);
    }

    /**
     * 測試依使用者ID及群組代碼取得一筆非人民幣使用者的信用額度
     */
    public function testGetOneCreditByUserIdAndgroupNumDontUseCNY()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $client->request('GET', '/api/user/7/credit/2');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $credit = $em->find('BBDurianBundle:Credit', $output['ret']['id']);

        $this->assertEquals(22421, $output['ret']['line']);
        $this->assertEquals(8968.61, $output['ret']['balance']);
    }

    /**
     * 測試依使用者ID及群組代碼取得一筆使用者的信用額度但找不到該信用額度
     */
    public function testGetOneCreditButCreditNotExist()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/user/6/credit/3');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150060001, $output['code']);
        $this->assertEquals('No credit found', $output['msg']);
    }

    /**
     * 測試依使用者ID及群組代碼取得使用者的下層啟用帳號的信用額度分配
     */
    public function testGetUserEnableTotalEnable()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/6/credit/2/get_total_enable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(5000, $output['ret']);
    }

    /**
     * 測試以不存在的使用者ID及正確的群組代碼取得使用者的下層啟用帳號的信用額度分配
     */
    public function testGetUserTotalEnableButUserNotExist()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/1/credit/2/get_total_enable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150060038, $output['code']);
        $this->assertEquals('No such user', $output['msg']);
    }

    /**
     * 測試依正確的使用者ID及不正確的群組代碼取得使用者的下層啟用帳號的信用額度分配
     */
    public function testGetUserTotalEnableButCreditNotExist()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/7/credit/3/get_total_enable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150060001, $output['code']);
        $this->assertEquals('No credit found', $output['msg']);
    }

    /**
     * 測試依正確的使用者ID及群組代碼取得使用者的下層啟用帳號的信用額度分配
     * 總和為零的情況
     */
    public function testGetUserTotalEnableWithTotalIsZeroAndWrongCurrency()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setCurrency(901);
        $em->flush();

        $client->request('GET', '/api/user/8/credit/2/get_total_enable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(0, $output['ret']);
    }

    /**
     * 測試依使用者ID及群組代碼取得使用者的下層啟用帳號的信用額度分配
     * 但幣別設定錯誤的情況
     */
    public function testGetUserTotalEnableWithErrorExchange()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->find('BBDurianBundle:User', 7);
        $user->setCurrency(987);
        $em->flush();

        $client->request('GET', '/api/user/7/credit/2/get_total_enable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150060028, $output['code']);
        $this->assertEquals('No such exchange', $output['msg']);
    }

    /**
     * 測試依使用者ID及群組代碼取得使用者的下層啟用帳號的信用額度分配但使用者不使用人民幣
     */
    public function testGetUserTotalEnableWithUserDontUseCNY()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $user = $em->find('BBDurianBundle:User', 7);
        $user->setCurrency(901);
        $em->flush();

        $exchange = $emShare->getRepository('BBDurianBundle:Exchange')
            ->findByCurrencyAt(901, new \DateTime('now'));
        $valueConverted = $exchange->convertByBasic(3000);

        $client->request('GET', '/api/user/7/credit/2/get_total_enable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($valueConverted, $output['ret']);
    }

    /**
     * 測試依使用者ID及群組代碼取得使用者的下層停用帳號的信用額度分配總和
     */
    public function testGetUserDisableTotalEnable()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->find('BB\DurianBundle\Entity\User', 7);
        $user->disable();
        $em->flush();
        $em->clear();
        $client = $this->createClient();

        $client->request('GET', '/api/user/6/credit/2/get_total_disable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(5000, $output['ret']);
    }

    /**
     * 測試以不存在的使用者ID及正確的群組代碼取得使用者的下層停用帳號的信用額度分配
     */
    public function testGetUserTotalDisableButUserNotExist()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/1/credit/2/get_total_disable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150060038, $output['code']);
        $this->assertEquals('No such user', $output['msg']);
    }

    /**
     * 測試以正確的使用者ID及不存在的群組代碼取得使用者的下層停用帳號的信用額度分配
     */
    public function testGetUserTotalDisableButCreditNotExist()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->find('BBDurianBundle:User', 7);
        $user->disable();
        $em->flush();
        $client = $this->createClient();

        $client->request('GET', '/api/user/7/credit/3/get_total_disable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150060001, $output['code']);
        $this->assertEquals('No credit found', $output['msg']);
    }

    /**
     * 測試以使用者ID及群組代碼取得使用者的下層停用帳號的信用額度分配但不使用人民幣
     */
    public function testGetUserTotalDisableWithUserDontUseCNY()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $user = $em->find('BBDurianBundle:User', 8);
        $user->disable();
        $em->flush();
        $client = $this->createClient();

        $exchange = $emShare->getRepository('BBDurianBundle:Exchange')
            ->findByCurrencyAt(901, new \DateTime('now'));
        $valueConverted = $exchange->convertByBasic(3000);

        $client->request('GET', '/api/user/7/credit/2/get_total_disable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($valueConverted, $output['ret']);
    }

    /**
     * 測試依使用者ID及群組代碼取得使用者的下層帳號的信用額度分配為0的情況
     */
    public function testGetUserTotalNull()
    {
        $client = $this->createClient();

        //測試取得啟用帳號
        $client->request('GET', '/api/user/10/credit/2/get_total_enable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertSame('0', $output['ret']);

        //測試取得停用帳號
        $client->request('GET', '/api/user/10/credit/2/get_total_disable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertSame('0', $output['ret']);
    }

    /**
     * 測試取得使用者所有額度資訊
     */
    public function testGetCreditsByUser()
    {
        $client = $this->createClient();

        $parameters = array(
            'users'  => array(7),
            'fields' => array('credit'));

        $client->request('GET', '/api/users', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(7, $output['ret'][0]['id']);
        $this->assertEquals(22421, $output['ret'][0]['credit'][2]['line']);
        $this->assertEquals(8968.61, $output['ret'][0]['credit'][2]['balance']);
    }

    /**
     * 測試當上層額度停用時取得額度資訊
     */
    public function testGetCreditByUserButParentCreditDisable()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $credit = $em->find('BB\DurianBundle\Entity\Credit', 1);
        $credit->disable();
        $credit = $em->find('BB\DurianBundle\Entity\Credit', 2);
        $credit->disable();

        $em->flush();

        $parameters = array(
            'users'  => array(7),
            'fields' => array('credit'));

        $client->request('GET', '/api/users', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(44843, $output['ret'][0]['credit'][1]['line']);
        $this->assertFalse($output['ret'][0]['credit'][1]['enable']);
        $this->assertEquals(22421, $output['ret'][0]['credit'][2]['line']);
        $this->assertFalse($output['ret'][0]['credit'][2]['enable']);
    }

    /**
     * 測試抓現金交易記錄
     */
    public function testGetEntries()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $nowTime = new \DateTime('now');

        // 新增一筆交易紀錄，負數金額
        $parameters = array('amount' => -100, 'at' => $nowTime->format(\DateTime::ISO8601),
                            'opcode' => 40000);

        $client->request('PUT', '/api/user/8/credit/2/op', $parameters);

        // 新增一筆交易紀錄，正數金額
        $parameters = [
            'amount' => 100,
            'at' => $nowTime->format(\DateTime::ISO8601),
            'opcode' => 40000
        ];

        $client->request('PUT', '/api/user/8/credit/2/op', $parameters);

        //新增一筆交易紀錄，使用不同opcode與red_id
        $parameters = [
            'amount' => -100,
            'at' => $nowTime->format(\DateTime::ISO8601),
            'opcode' => 40001,
            'ref_id' => 1
        ];

        $client->request('PUT', '/api/user/8/credit/2/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $cmdParams = [
            '--entry' => 1,
            '--period' => 1
        ];
        $this->runCommand('durian:sync-credit', $cmdParams);

        $parameters = [
            'first_result' => 0,
            'max_results'  => 20,
            'sub_ret'      => 1,
            'sub_total'    => 1,
            'opcode'       => 40000
        ];

        $client->request('GET', '/api/user/8/credit/2/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals(2, $ret['ret'][0]['id']);
        $this->assertEquals(6, $ret['ret'][0]['credit_id']);
        $this->assertEquals(8, $ret['ret'][0]['user_id']);
        $this->assertEquals(2, $ret['ret'][0]['group']);
        $this->assertEquals(40000, $ret['ret'][0]['opcode']); // 40000 BETTING
        $this->assertEquals('-100', $ret['ret'][0]['amount']);
        $this->assertEquals('2900', $ret['ret'][0]['balance']);
        $this->assertEquals('3000', $ret['ret'][0]['line']);
        $this->assertEquals('', $ret['ret'][0]['memo']);
        $this->assertEquals('', $ret['ret'][0]['ref_id']);

        $this->assertEquals(2, count($ret['ret']));
        $this->assertEquals(2, count($ret['sub_ret']));

        $user = $em->find('BB\DurianBundle\Entity\User', 8);
        $credit = $user->getCredit(2);

        $this->assertEquals($user->getUsername(), $ret['sub_ret']['user']['username']);
        $this->assertEquals($user->getAlias(), $ret['sub_ret']['user']['alias']);
        $this->assertEquals($credit->getId(), $ret['sub_ret']['credit']['id']);
        $this->assertEquals($credit->getBalance(), $ret['sub_ret']['credit']['balance']);
        $this->assertEquals($credit->getUser()->getId(), $ret['sub_ret']['credit']['user_id']);

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
        $client->request('GET', '/api/user/8/credit/2/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEmpty($output['ret']);
    }

    /**
     * 測試設定額度
     */
    public function testSetCredit()
    {
        $redisWallet = $this->getContainer()->get('snc_redis.wallet4');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $client = $this->createClient();

        $parameters = array('line' => 50);

        $client->request('PUT', '/api/user/8/credit/2', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $cmdParams = [
            '--entry' => 1,
            '--credit' => 1,
            '--period' => 1
        ];
        $out = $this->runCommand('durian:sync-credit', $cmdParams);

        $this->assertEquals('ok', $output['result']);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals("credit", $logOperation->getTableName());
        $this->assertEquals("@id:6", $logOperation->getMajorKey());
        $this->assertEquals("@user_id:8, @group_num:2, @line:3000=>50", $logOperation->getMessage());

        $user = $em->find('BB\DurianBundle\Entity\User', 8);
        $credit = $user->getCredit(2);

        $this->assertEquals($credit->getLine(), $output['ret']['line']);

        // 檢查 Redis
        $creditKey = 'credit_8_2';
        $creditInfo = $redisWallet->hgetall($creditKey);
        $this->assertEquals(6, $creditInfo['id']);
        $this->assertEquals(1, $creditInfo['enable']);
        $this->assertEquals(50, $creditInfo['line']);
        $this->assertEquals(0, $creditInfo['total_line']);
        $this->assertEquals(156, $creditInfo['currency']);
        $this->assertEquals(2, $creditInfo['version']);

        //測試使用者為台幣
        $user->setCurrency(901);
        $em->flush();
        $em->clear();

        $redisWallet->hset($creditKey, 'currency', 901);

        $parameters = array('line' => 50);

        $client->request('PUT', '/api/user/8/credit/2', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->runCommand('durian:sync-credit', $cmdParams);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(49, $output['ret']['line']);
        $this->assertEquals(49.33, $output['ret']['balance']);

        // 檢查 Redis
        $creditInfo = $redisWallet->hgetall($creditKey);
        $this->assertEquals(6, $creditInfo['id']);
        $this->assertEquals(1, $creditInfo['enable']);
        $this->assertEquals(11, $creditInfo['line']);
        $this->assertEquals(0, $creditInfo['total_line']);
        $this->assertEquals(901, $creditInfo['currency']);
        $this->assertEquals(3, $creditInfo['version']);
    }

    /**
     * 測試停用信用額度時仍可設定額度
     */
    public function testSetCreditWithDisable()
    {
        $redisWallet = $this->getContainer()->get('snc_redis.wallet4');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $client = $this->createClient();

        $user = $em->find('BBDurianBundle:User', 8);
        $credit = $user->getCredit(2);
        $credit->disable();
        $em->flush();

        $parameters = ['line' => 1000];

        $client->request('PUT', '/api/user/8/credit/2', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->runCommand('durian:sync-credit', ['--credit' => 1]);

        $em->clear();

        $credit = $em->find('BBDurianBundle:Credit', 6);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals($credit->getLine(), $output['ret']['line']);

        // 檢查 Redis
        $creditKey = 'credit_8_2';
        $creditInfo = $redisWallet->hgetall($creditKey);
        $this->assertEquals(6, $creditInfo['id']);
        $this->assertEquals('', $creditInfo['enable']);
        $this->assertEquals(1000, $creditInfo['line']);
        $this->assertEquals(0, $creditInfo['total_line']);
        $this->assertEquals(156, $creditInfo['currency']);
        $this->assertEquals(2, $creditInfo['version']);
    }

    /**
     * 測試設定額度但找不到信用額度資料
     */
    public function testSetCreditWithCreditNotFound()
    {
        $client = $this->createClient();
        $parameters = ['line' => -10];
        $client->request('PUT', '/api/user/8/credit/3', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150060001', $output['code']);
        $this->assertEquals('No credit found', $output['msg']);
    }

    /**
     * 測試設定額度但找不到信用額度資料
     */
    public function testSetCreditWithErrorExchange()
    {
        $client = $this->createClient();
        $parameters = ['line' => -10];
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $user = $em->find('BBDurianBundle:User', 8);
        $user->setCurrency(666);
        $em->flush();

        $client->request('PUT', '/api/user/8/credit/2', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150060028, $output['code']);
        $this->assertEquals('No such exchange', $output['msg']);
    }

    /**
     * 測試設定額度在發生錯誤時會還原redis資料
     * 只會發生在幣別錯誤並且設定值為零的情況
     */
    public function testSetCreditWithErrorAndWillRollBack()
    {
        $redisWallet = $this->getContainer()->get('snc_redis.wallet4');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BBDurianBundle:User', 8);
        $user->setCurrency(987);
        $em->flush();

        $parameters = ['line' => 0];
        $client->request('PUT', '/api/user/8/credit/2', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(470010, $output['code']);
        $this->assertEquals('No such exchange', $output['msg']);

        //測試redis有被還原
        $creditKey = 'credit_8_2';
        $creditInfo = $redisWallet->hgetall($creditKey);
        $this->assertEquals(6, $creditInfo['id']);
        $this->assertEquals(1, $creditInfo['enable']);
        $this->assertEquals(3000, $creditInfo['line']);
        $this->assertEquals(0, $creditInfo['total_line']);
        $this->assertEquals(987, $creditInfo['currency']);
        $this->assertEquals(3, $creditInfo['version']);
    }

    /**
     * 測試修改額度時出現新的額度小於totalLine
     */
    public function testSetCreditButNewLineLessThanTotalLine()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = array('line' => -10);
        $client->request('PUT', '/api/user/8/credit/2', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 測試是否有寫操作紀錄
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEmpty($logOperation);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150060040', $output['code']);
        $this->assertEquals('Line is less than sum of children credit', $output['msg']);

        // 檢查 Redis
        $redisWallet = $this->getContainer()->get('snc_redis.wallet4');
        $creditKey = 'credit_8_2';
        $creditInfo = $redisWallet->hgetall($creditKey);
        $this->assertEquals(6, $creditInfo['id']);
        $this->assertEquals(1, $creditInfo['enable']);
        $this->assertEquals(3000, $creditInfo['line']);
        $this->assertEquals(0, $creditInfo['total_line']);
        $this->assertEquals(156, $creditInfo['currency']);
        $this->assertEquals(3, $creditInfo['version']);
    }

    /**
     * 測試下注後修改額度上限，而新的額度上限使餘額為負數
     */
    public function testSetCreditAfterOrderButNegativeBalance()
    {
        $client = $this->createClient();
        $at = new \DateTime('now');
        $parameters = array('amount' => -1800, 'at' => $at->format('Y-m-d H:i:s'),
                            'opcode' => 40000);

        $client->request('PUT', '/api/user/8/credit/2/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $cmdParams = [
            '--entry' => 1,
            '--period' => 1
        ];
        $this->runCommand('durian:sync-credit', $cmdParams);

        // 檢查 Redis
        $redisWallet = $this->getContainer()->get('snc_redis.wallet4');
        $creditKey = 'credit_8_2';
        $creditInfo = $redisWallet->hgetall($creditKey);
        $this->assertEquals(6, $creditInfo['id']);
        $this->assertEquals(1, $creditInfo['enable']);
        $this->assertEquals(3000, $creditInfo['line']);
        $this->assertEquals(0, $creditInfo['total_line']);
        $this->assertEquals(156, $creditInfo['currency']);
        $this->assertEquals(1, $creditInfo['version']);

        //test 150060016
        $parameters = array('line' => 1000);
        $client->request('PUT', '/api/user/8/credit/2', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150060016', $output['code']);
        $this->assertEquals(
            'Negative balance is illegal (Due to line/total_line changing of self/parent)',
            $output['msg']
        );

        // 檢查 Redis
        $creditInfo = $redisWallet->hgetall($creditKey);
        $this->assertEquals(6, $creditInfo['id']);
        $this->assertEquals(1, $creditInfo['enable']);
        $this->assertEquals(3000, $creditInfo['line']);
        $this->assertEquals(0, $creditInfo['total_line']);
        $this->assertEquals(156, $creditInfo['currency']);
        $this->assertEquals(3, $creditInfo['version']);
    }

    /**
     * 測試修改信用額度超過上層的額度總和(total_line)，會恢復額度
     */
    public function testSetCreditWithNotEnoughLineToBeWithdraw()
    {
        $pRedisWallet = $this->getContainer()->get('snc_redis.wallet2');
        $redisWallet = $this->getContainer()->get('snc_redis.wallet3');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $client->request('PUT', '/api/user/7/credit/1', ['line' => 300000]);

        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals('error', $out['result']);
        $this->assertEquals(150060041, $out['code']);
        $this->assertEquals('TotalLine is greater than parent credit', $out['msg']);

        // 檢查資料庫
        $pCredit = $em->find('BBDurianBundle:Credit', 1);
        $this->assertEquals(6, $pCredit->getUser()->getId());
        $this->assertEquals(15000, $pCredit->getLine());
        $this->assertEquals(10000, $pCredit->getTotalLine());

        $credit = $em->find('BBDurianBundle:Credit', 3);
        $this->assertEquals(7, $credit->getUser()->getId());
        $this->assertEquals(10000, $credit->getLine());
        $this->assertEquals(5000, $credit->getTotalLine());

        // 檢查 Redis
        $pKey = 'credit_6_1';
        $key = 'credit_7_1';

        $pRedisCredit = $pRedisWallet->hgetall($pKey);
        $redisCredit = $redisWallet->hgetall($key);

        $this->assertEquals(1, $pRedisCredit['id']);
        $this->assertEquals(15000, $pRedisCredit['line']);
        $this->assertEquals(10000, $pRedisCredit['total_line']);

        $this->assertEquals(3, $redisCredit['id']);
        $this->assertEquals(10000, $redisCredit['line']);
        $this->assertEquals(5000, $redisCredit['total_line']);
    }

    /**
     * 測試額度相關操作
     */
    public function testOpCredit()
    {
        $redisWallet = $this->getContainer()->get('snc_redis.wallet4');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $nowTime = new \DateTime();
        $today = clone $nowTime;

        $memo = '';
        for ($i = 0; $i < 100; $i++) {
            $memo .= 'a';
        }

        $parameters = [
            'amount' => -100,
            'at' => $today->format('Y-m-d H:i:s'),
            'opcode' => 40000,
            'memo' => $memo . '012'
        ];

        $client->request('PUT', '/api/user/8/credit/2/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $cmdParams = [
            '--entry' => 1,
            '--period' => 1
        ];
        $this->runCommand('durian:sync-credit', $cmdParams);

        $this->assertEquals('ok', $output['result']);

        $cp = $em->find('BB\DurianBundle\Entity\CreditPeriod', 3);

        $this->assertEquals(100, $cp->getAmount());

        // 檢查回傳資料
        $this->assertEquals(6, $output['ret']['id']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals(2, $output['ret']['group']);
        $this->assertEquals(3000, $output['ret']['line']);
        $this->assertEquals(2900, $output['ret']['balance']);

        //抓明細資料回來檢查
        $result = $em->getRepository('BB\DurianBundle\Entity\CreditEntry')
        ->findBy(array('creditId' => $output['ret']['id']));

        $ce = $result[0]->toArray();

        $this->assertEquals(2, $ce['id']);
        $this->assertEquals(6, $ce['credit_id']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals(2, $output['ret']['group']);
        $this->assertEquals(-100, $ce['amount']);
        $this->assertEquals(2900, $ce['balance']);
        $this->assertEquals(3000, $ce['line']);
        $this->assertEquals($memo, $ce['memo']);

        $tomorrow = clone $nowTime->add(new \DateInterval('PT24H'));

        //測試帶入不同日期時間交易，
        $parameters = array('amount' => -100,
                            'at' => $tomorrow->format('Y-m-d H:i:s'),
                            'opcode' => 40000);

        $client->request('PUT', '/api/user/8/credit/2/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->runCommand('durian:sync-credit', $cmdParams);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(6, $output['ret']['id']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals(2, $output['ret']['group']);
        $this->assertEquals(3000, $output['ret']['line']);
        $this->assertEquals(2800, $output['ret']['balance']);

        $yesterday = clone $nowTime->sub(new \DateInterval('PT48H'));

        //測試帶入不同日期時間交易，
        $parameters = array('amount' => -500,
                            'at' => $yesterday->format('Y-m-d H:i:s'),
                            'opcode' => 40000);

        $client->request('PUT', '/api/user/8/credit/2/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->runCommand('durian:sync-credit', $cmdParams);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(6, $output['ret']['id']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals(2, $output['ret']['group']);
        $this->assertEquals(3000, $output['ret']['line']);
        $this->assertEquals(2300, $output['ret']['balance']);

        //測試取得交易時間
        $parameters = array('at' => $today->format('Y-m-d H:i:s'));

        $client->request('GET', '/api/credit/6', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(6, $output['ret']['id']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals(2, $output['ret']['group']);
        $this->assertEquals(3000, $output['ret']['line']);
        $this->assertEquals(2800, $output['ret']['balance']);

        //測試帶入正值amount
        $parameters = array('amount' => 10,
                            'at' => $tomorrow->format('Y-m-d H:i:s'),
                            'opcode' => 40000);

        $client->request('PUT', '/api/user/8/credit/2/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->runCommand('durian:sync-credit', $cmdParams);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(6, $output['ret']['id']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals(2, $output['ret']['group']);
        $this->assertEquals(3000, $output['ret']['line']);
        $this->assertEquals(2810, $output['ret']['balance']);

        $pastTime = new \DateTime('30 days ago-0400');
        $pastTime->setTime(11, 59, 59);
        //測試帶入美東時間是否有轉換為台灣時間(2012-04-01T23:59:59+0800)，且額度正確
        $parameters = array('amount' => -100, 'at' => $pastTime->format(\DateTime::ISO8601),
                            'opcode' => 40000);

        $client->request('PUT', '/api/user/8/credit/2/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->runCommand('durian:sync-credit', $cmdParams);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(6, $output['ret']['id']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals(2, $output['ret']['group']);
        $this->assertEquals(3000, $output['ret']['line']);
        $this->assertEquals(2210, $output['ret']['balance']);

        $pastTime->setTime(12, 00, 00);
        //測試帶入美東時間是否有轉換為台灣時間(2012-04-02T00:00:00+0800)，且額度正確
        $parameters = array('amount' => -100, 'at' => $pastTime->format(\DateTime::ISO8601),
                            'opcode' => 40000);

        $client->request('PUT', '/api/user/8/credit/2/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->runCommand('durian:sync-credit', $cmdParams);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(6, $output['ret']['id']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals(2, $output['ret']['group']);
        $this->assertEquals(3000, $output['ret']['line']);
        $this->assertEquals(2210, $output['ret']['balance']);

        //測試使用者慣用幣別為台幣，交易及回傳皆為台幣幣值
        $user = $em->find('BB\DurianBundle\Entity\User', 8);
        $user->setCurrency(901);

        $creditKey = 'credit_8_2';

        $redisWallet->hset($creditKey, 'currency', 901);

        $em->flush();

        $parameters = array('amount' => -1000, 'at' => $pastTime->format(\DateTime::ISO8601),
                            'opcode' => 40000);

        $client->request('PUT', '/api/user/8/credit/2/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->runCommand('durian:sync-credit', $cmdParams);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(6, $output['ret']['id']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals(2, $output['ret']['group']);
        $this->assertEquals(13452, $output['ret']['line']);
        $this->assertEquals(8910.31, $output['ret']['balance']);

        //測試帶force參數時, 可允許金額為0
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setCurrency(156);
        $redisWallet->hset($creditKey, 'currency', 156);
        $em->flush();

        $parameters = [
            'amount' => 0,
            'at' => $pastTime->format(\DateTime::ISO8601),
            'opcode' => 40000,
            'force' => true
        ];

        $client->request('PUT', '/api/user/8/credit/2/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->runCommand('durian:sync-credit', $cmdParams);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals(2, $output['ret']['group']);
        $this->assertEquals(3000, $output['ret']['line']);
        $this->assertEquals(1987, $output['ret']['balance']);

        //測試帶force參數時, 可強制將餘額扣到負數, 並允許信用額度停用
        $user = $em->find('BBDurianBundle:User', 8);
        $user->getCredit(2)->disable();
        $em->flush();

        $parameters = [
            'amount' => -2000,
            'at' => $pastTime->format(\DateTime::ISO8601),
            'opcode' => 40000,
            'force' => true
        ];

        $client->request('PUT', '/api/user/8/credit/2/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->runCommand('durian:sync-credit', $cmdParams);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals(2, $output['ret']['group']);
        $this->assertEquals(3000, $output['ret']['line']);
        $this->assertEquals(-13, $output['ret']['balance']);
    }

    /**
     * 測試OpCredit的memo輸入非UTF8
     */
    public function testOpCreditMemoNotUtf8()
    {
        $client = $this->createClient();
        $nowTime = new \DateTime();

        $parameters = array(
            'amount' => -100,
            'at'     => $nowTime->format('Y-m-d H:i:s'),
            'opcode' => 40000,
            'memo'   => mb_convert_encoding('e龜龍鱉', 'GB2312', 'UTF-8')
        );

        $client->request('PUT', '/api/user/8/credit/2/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150610002, $output['code']);
        $this->assertEquals('String must use utf-8 encoding', $output['msg']);
    }

    /**
     * 測試額度相關操作輸入無效ref_id
     */
    public function testOpCreditInvalidRefId()
    {
        $client = $this->createClient();
        $nowTime = new \DateTime();

        $parameters = array(
            'amount' => -100,
            'at'     => $nowTime->format('Y-m-d H:i:s'),
            'opcode' => 40000,
            'ref_id' => 'test'
        );

        $client->request('PUT', '/api/user/8/credit/2/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150060031, $output['code']);
        $this->assertEquals('Invalid ref_id', $output['msg']);

        $parameters = array(
            'amount' => -100,
            'at'     => $nowTime->format('Y-m-d H:i:s'),
            'opcode' => 40000,
            'ref_id' => -1
        );

        $client->request('PUT', '/api/user/8/credit/2/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150060031, $output['code']);
        $this->assertEquals('Invalid ref_id', $output['msg']);

        $parameters = array(
            'amount' => -100,
            'at'     => $nowTime->format('Y-m-d H:i:s'),
            'opcode' => 40000,
            'ref_id' => 9223372036854775807
        );

        $client->request('PUT', '/api/user/8/credit/2/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150060031, $output['code']);
        $this->assertEquals('Invalid ref_id', $output['msg']);
    }

    /**
     * 測試額度相關操作，ref_id帶空字串會送0到queue並回傳空字串
     */
    public function testOpCreditWithEmptyRefId()
    {
        $redis = $this->getContainer()->get('snc_redis.default');
        $client = $this->createClient();
        $nowTime = new \DateTime();

        $parameters = [
            'amount' => -100,
            'at'     => $nowTime->format('Y-m-d H:i:s'),
            'opcode' => 40000,
            'ref_id' => ''
        ];

        $client->request('PUT', '/api/user/8/credit/2/op', $parameters);

        $queue = json_decode($redis->rpop('credit_entry_queue'), true);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertSame(0, $queue['ref_id']);
        $this->assertEquals('ok', $output['result']);
    }

    /**
     * 測試下滿後不得再下
     */
    public function testLimitCreditOp()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $nowTime = new \DateTime();

        $parameters = array('amount' => -3000,
                            'at'     => $nowTime->format('Y-m-d H:i:s'),
                            'opcode' => 40000);
        $client->request('PUT', '/api/user/8/credit/2/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $cmdParams = [
            '--entry' => 1,
            '--period' => 1
        ];
        $this->runCommand('durian:sync-credit', $cmdParams);

        $this->assertEquals('ok', $output['result']);

        $cp = $em->find('BB\DurianBundle\Entity\CreditPeriod', 3);

        $this->assertEquals(3000, $cp->getAmount());

        // 檢查回傳資料
        $this->assertEquals(6, $output['ret']['id']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals(2, $output['ret']['group']);
        $this->assertEquals(3000, $output['ret']['line']);
        $this->assertEquals(0, $output['ret']['balance']);

        $client->request('PUT', '/api/user/8/credit/2/op', $parameters);
        $tomorrow = clone $nowTime->add(new \DateInterval('PT24H'));

        $parameters = array('amount' => -3000,
                            'at'     => $tomorrow->format('Y-m-d H:i:s'),
                            'opcode' => 40000);
        $client->request('PUT', '/api/user/8/credit/2/op', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150060034, $output['code']);
        $this->assertEquals('Not enough balance', $output['msg']);

        $yesterday = clone $nowTime->sub(new \DateInterval('PT48H'));

        $parameters = array('amount' => -3000,
                            'at'     => $yesterday->format('Y-m-d H:i:s'),
                            'opcode' => 40000);

        $client->request('PUT', '/api/user/8/credit/2/op', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150060034, $output['code']);
        $this->assertEquals('Not enough balance', $output['msg']);
    }

    /**
     * 測試額度相關操作並帶入當日區間總合最大值(10000000000)
     */
    public function testOpCreditWithMaxPeriodAmuont()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();


        $user = $em->find('BB\DurianBundle\Entity\User', 2);

        $credit = new \BB\DurianBundle\Entity\Credit($user, 1);
        $credit->setLine(16500000000);
        $em->persist($credit);
        $em->flush();
        $em->clear();

        $parameters = [
            'amount' => -10000000000,
            'at'     => date('Y-m-d 11:00:00'),
            'opcode' => 40000
        ];

        $client->request('PUT', '/api/user/2/credit/1/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $cmdParams = [
            '--entry' => 1,
            '--period' => 1
        ];
        $this->runCommand('durian:sync-credit', $cmdParams);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(9, $output['ret']['id']);
        $this->assertEquals(2, $output['ret']['user_id']);
        $this->assertEquals(1, $output['ret']['group']);
        $this->assertEquals(16500000000, $output['ret']['line']);
        $this->assertEquals(6500000000, $output['ret']['balance']);

        $parameters = array('amount' => -10000000000,
                    'at' => date('Y-m-d 11:00:00'),
                    'opcode' => 40000);
        $client->request('PUT', '/api/user/2/credit/1/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150060007, $output['code']);
        $this->assertEquals('Amount exceed the MAX value', $output['msg']);
    }

    /**
     * 測試額度相關操作且在特定的opcode下允許為負值
     */
    public function testOpCreditWithLegalNegativeBalanceOrInactive()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = array('amount' => -1000000, 'at' => date('Y-m-d 11:00:00'),
                            'opcode' => 20003);

        $client->request('PUT', '/api/user/8/credit/2/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $cmdParams = [
            '--entry' => 1,
            '--period' => 1
        ];
        $this->runCommand('durian:sync-credit', $cmdParams);

        $this->assertEquals('ok', $output['result']);

        // 檢查回傳資料
        $this->assertEquals(6, $output['ret']['id']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals(2, $output['ret']['group']);
        $this->assertEquals(3000, $output['ret']['line']);
        $this->assertTrue($output['ret']['balance'] <0);

        $credit = $em->find('BB\DurianBundle\Entity\Credit', 6);
        $credit->disable();
        $em->flush();
        $em->clear();

        $parameters = array('amount' => 1000000, 'at' => date('Y-m-d 11:00:00'),
                            'opcode' => 30008);

        $client->request('PUT', '/api/user/8/credit/2/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->runCommand('durian:sync-credit', $cmdParams);

        $this->assertEquals('ok', $output['result']);

        // 檢查回傳資料
        $this->assertEquals(6, $output['ret']['id']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals(2, $output['ret']['group']);
        $this->assertEquals(3000, $output['ret']['line']);
        $this->assertEquals(3000, $output['ret']['balance']);

        //測試連續op兩次在同一時間點上，只會產生一筆CreditPeriod entity
        $credit = $em->find('BB\DurianBundle\Entity\Credit', 6);
        $this->assertEquals(2, $credit->getPeriods()->count());
    }

    /**
     * 測試額度相關操作在超過credit period保留天數
     */
    public function testOpCreditWithPastDate()
    {
        $client = $this->createClient();

        // 測試額度相關操作在超過credit period保留天數且opcode不正確
        $parameters = array(
            'amount' => -1000000,
            'at' => date('2012-02-01 11:00:00'),
            'opcode' => 1001
        );

        $client->request('PUT', '/api/user/8/credit/2/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        //檢查回傳資料
        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150060022, $output['code']);
        $this->assertEquals('Illegal operation for expired credit period data', $output['msg']);

        // 測試額度相關操作在超過credit period保留天數且opcode為allow balance negative
        $parameters = array(
            'amount' => -1000000,
            'at' => date('2012-02-01 11:00:00'),
            'opcode' => 20003
        );

        $client->request('PUT', '/api/user/8/credit/2/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        //檢查回傳資料
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals(2, $output['ret']['group']);
        $this->assertNull($output['ret']['line']);
        $this->assertNull($output['ret']['balance']);
        $this->assertEquals('2012-02-01 00:00:00', $output['ret']['period']);

        $cmdParams = [
            '--entry' => 1,
            '--period' => 1
        ];
        $this->runCommand('durian:sync-credit', $cmdParams);

        //測試是否有產生entry資料
        $parameters = array(
            'period_start' => '2012-01-31 23:00:00',
            'period_end'   => '2012-02-01 23:00:00'
        );

        $client->request('GET', '/api/user/8/credit/2/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        //檢查回傳資料
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('0', $output['pagination']['total']);

        // 測試額度相關操作在超過credit period保留天數且opcode不正確, 但使用強制扣款參數
        $parameters = [
            'amount' => -1000000,
            'at' => date('2012-02-01 11:00:00'),
            'opcode' => 1001,
            'force' => 1
        ];

        $client->request('PUT', '/api/user/8/credit/2/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        //檢查回傳資料
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals(2, $output['ret']['group']);
        $this->assertNull($output['ret']['line']);
        $this->assertNull($output['ret']['balance']);
        $this->assertEquals('2012-02-01 00:00:00', $output['ret']['period']);
    }

    /**
     * 測試中午更新Group額度相關操作
     */
    public function testOpCreditByNoonUpdateGroup()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user     = $em->find('BB\DurianBundle\Entity\User', 8);

        $credit = new \BB\DurianBundle\Entity\Credit($user, 3);
        $credit->setLine(5000);
        $em->persist($credit);

        $em->flush();
        $em->clear();

        $newTime = new \DateTime('10 days ago');
        $newTime->setTime(11, 59, 0);
        $parameters = array('amount' => -100, 'at' => $newTime->format(\DateTime::ISO8601),
                            'opcode' => 40000);

        $client->request('PUT', '/api/user/8/credit/3/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $cmdParams = [
            '--entry' => 1,
            '--period' => 1
        ];
        $this->runCommand('durian:sync-credit', $cmdParams);

        $this->assertEquals('ok', $output['result']);

        $cp = $em->find('BB\DurianBundle\Entity\CreditPeriod', 3);

        $this->assertEquals(100, $cp->getAmount());

        // 檢查回傳資料
        $this->assertEquals(9, $output['ret']['id']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals(3, $output['ret']['group']);
        $this->assertEquals(5000, $output['ret']['line']);
        $this->assertEquals(4900, $output['ret']['balance']);

        //測試帶入美東時間 (2012-03-31T11:59:00+0800)
        $newTime->setTimezone(new \DateTimeZone('America/Puerto_Rico'));

        $parameters = array('amount' => -100, 'at' => $newTime->format(\DateTime::ISO8601),
                            'opcode' => 40000);

        $client->request('PUT', '/api/user/8/credit/3/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 檢查回傳資料
        $this->assertEquals(9, $output['ret']['id']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals(3, $output['ret']['group']);
        $this->assertEquals(5000, $output['ret']['line']);
        $this->assertEquals(4800, $output['ret']['balance']);

        //測試帶入中午時間是否額度重新計算
        $newTime = new \DateTime('10 days ago');
        $newTime->setTime(12, 0, 0);

        $parameters = array('amount' => -100, 'at' => $newTime->format(\DateTime::ISO8601),
                            'opcode' => 40000);

        $client->request('PUT', '/api/user/8/credit/3/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(9, $output['ret']['id']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals(3, $output['ret']['group']);
        $this->assertEquals(5000, $output['ret']['line']);
        $this->assertEquals(4900, $output['ret']['balance']);

        //測試帶入美東時間 (2012-03-31T12:00:00+0800)
        $newTime->setTimezone(new \DateTimeZone('America/Puerto_Rico'));

        $parameters = array('amount' => -100, 'at' => $newTime->format(\DateTime::ISO8601),
                            'opcode' => 40000);

        $client->request('PUT', '/api/user/8/credit/3/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 檢查回傳資料
        $this->assertEquals(9, $output['ret']['id']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals(3, $output['ret']['group']);
        $this->assertEquals(5000, $output['ret']['line']);
        $this->assertEquals(4800, $output['ret']['balance']);

        //測試帶入過中午時間
        $newTime = new \DateTime('10 days ago');
        $newTime->setTime(12, 1, 0);

        $parameters = array('amount' => -100, 'at' => $newTime->format(\DateTime::ISO8601),
                            'opcode' => 40000);

        $client->request('PUT', '/api/user/8/credit/3/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(9, $output['ret']['id']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals(3, $output['ret']['group']);
        $this->assertEquals(5000, $output['ret']['line']);
        $this->assertEquals(4700, $output['ret']['balance']);

        //測試帶入美東時間 (2012-03-31T12:01:00+0800)
        $newTime->setTimezone(new \DateTimeZone('America/Puerto_Rico'));

        $parameters = array('amount' => -100, 'at' => $newTime->format(\DateTime::ISO8601),
                            'opcode' => 40000);

        $client->request('PUT', '/api/user/8/credit/3/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(9, $output['ret']['id']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals(3, $output['ret']['group']);
        $this->assertEquals(5000, $output['ret']['line']);
        $this->assertEquals(4600, $output['ret']['balance']);
    }

    /**
     * 測試當日期超過, redis資料已被清空
     */
    public function testOpCreditWithOverdueDate()
    {
        $redisWallet = $this->getContainer()->get('snc_redis.wallet2');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $date = new \DateTime('10 days ago');

        $user = $em->find('BB\DurianBundle\Entity\User', 6);
        $credit = $user->getCredit(1);
        $creditPeriod = new CreditPeriod($credit, $date);
        $creditPeriod->addAmount(1000);
        $em->persist($creditPeriod);
        $em->flush();

        $periodKey = sprintf(
            'credit_period_6_1_%s',
            $date->format('Ymd')
        );

        // 先確定 redis 值不在
        $this->assertFalse($redisWallet->exists($periodKey));

        $date = $date->format(\DateTime::ISO8601);
        $parameters = array('amount' => 500, 'at' => $date, 'opcode' => 40003);

        $userId = $credit->getUser()->getId();
        $client->request('PUT', '/api/user/'.$userId.'/credit/1/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(5000000, $redisWallet->hget($periodKey, 'amount'));
        $this->assertEquals($redisWallet->hget($periodKey, 'at'), $output['ret']['period']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($credit->getUser()->getId(), $output['ret']['user_id']);
        $this->assertEquals(1, $output['ret']['group']);
    }

    /**
     * 測試當額度停用時額度操作
     */
    public function testOpCreditButDisable()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $credit = $em->find('BB\DurianBundle\Entity\Credit', 5);
        $credit->disable();
        $credit = $em->find('BB\DurianBundle\Entity\Credit', 6);
        $credit->disable();

        $em->flush();

        $now = new \DateTime();
        $now = $now->format(\DateTime::ISO8601);

        $parameters = array('amount' => 100, 'at' => $now, 'opcode' => 40000);

        $client->request('PUT', '/api/user/8/credit/2/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Credit is disabled', $output['msg']);
        $this->assertEquals(150060012, $output['code']);
    }

    /**
     * 測試當餘額不足會回應150060034的例外
     */
    public function testOpCreditButNotEnoughBalance()
    {
        $redisWallet = $this->getContainer()->get('snc_redis.wallet4');
        $client = $this->createClient();

        $now = new \DateTime();
        $now = $now->format(\DateTime::ISO8601);

        $parameters = array('amount' => -1000000, 'at' => $now, 'opcode' => 40000);

        $client->request('PUT', '/api/user/8/credit/2/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Not enough balance', $output['msg']);
        $this->assertEquals(150060034, $output['code']);

        $cron = \Cron\CronExpression::factory('@daily');
        $exNow = $cron->getPreviousRunDate($now, 0, true);

        $periodKey = sprintf(
            'credit_period_8_2_%s',
            $exNow->format('Ymd')
        );
        $this->assertEquals(0, $redisWallet->hget($periodKey, 'amount'));
    }

    /**
     * 測試當上層額度停用時額度操作
     */
    public function testOpCreditButParentCreditDisable()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $credit = $em->find('BB\DurianBundle\Entity\Credit', 2);
        $credit->disable();

        $em->flush();
        $em->clear();

        $now = new \DateTime();
        $now = $now->format(\DateTime::ISO8601);

        $parameters = array('amount' => 100, 'at' => $now, 'opcode' => 40000);

        $client->request('PUT', '/api/user/8/credit/2/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Credit is disabled', $output['msg']);
        $this->assertEquals(150060012, $output['code']);
    }

    /**
     * 測試以不合法的金額進行操作
     */
    public function testOpCreditWithInvalidAmount()
    {
        $client = $this->createClient();

        $now = new \DateTime();
        $now = $now->format(\DateTime::ISO8601);

        $parameters = array(
            'amount' => 100.00005698,
            'at'     => $now,
            'opcode' => 40000
        );

        $client->request('PUT', '/api/user/8/credit/2/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('The decimal digit of amount exceeds limitation', $output['msg']);
        $this->assertEquals(150610003, $output['code']);
    }

    /**
     * 測試信用額度操作，但沒有該使用者
     */
    public function testOpCreditWithNoSuchUser()
    {
        $client = $this->createClient();

        $now = new \DateTime();
        $now = $now->format(\DateTime::ISO8601);

        $parameters = [
            'amount' => -100,
            'at'     => $now,
            'opcode' => 1001
        ];

        $client->request('PUT', '/api/user/999999/credit/1/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('No such user', $output['msg']);
        $this->assertEquals(150060038, $output['code']);
    }

    /**
     * 測試信用額度操作，但沒有該信用額度
     */
    public function testOpCreditWithNoCreditFound()
    {
        $client = $this->createClient();

        $now = new \DateTime();
        $now = $now->format(\DateTime::ISO8601);

        $parameters = [
            'amount' => -100,
            'at'     => $now,
            'opcode' => 1001
        ];

        $client->request('PUT', '/api/user/8/credit/99/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('No credit found', $output['msg']);
        $this->assertEquals(150060001, $output['code']);
    }

    /**
     * 測試信用額度操作，但累積金額小於零
     */
    public function testOpCreditWithAmountIsNegative()
    {
        $redisWallet = $this->getContainer()->get('snc_redis.wallet4');
        $client = $this->createClient();

        $now = new \DateTime();
        $now = $now->format(\DateTime::ISO8601);

        $parameters = [
            'amount' => 1000,
            'at'     => $now,
            'opcode' => 1001
        ];

        $client->request('PUT', '/api/user/8/credit/2/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Amount of period can not be negative', $output['msg']);
        $this->assertEquals(150060008, $output['code']);

        $cron = \Cron\CronExpression::factory('@daily');
        $exNow = $cron->getPreviousRunDate($now, 0, true);

        $periodKey = sprintf(
            'credit_period_8_2_%s',
            $exNow->format('Ymd')
        );
        $this->assertEquals(0, $redisWallet->hget($periodKey, 'amount'));
    }

    /**
     * 測試額度停用
     */
    public function testDisable()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $credit = $em->find('BB\DurianBundle\Entity\Credit', 3);

        $this->assertTrue($credit->isEnable());

        $em->clear();

        $client->request('PUT', '/api/credit/3/disable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals("credit", $logOperation->getTableName());
        $this->assertEquals("@user_id:7", $logOperation->getMajorKey());
        $this->assertEquals("@group_num:1, @enable:true=>false", $logOperation->getMessage());

        $credit = $em->find('BB\DurianBundle\Entity\Credit', 3);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret']['id']);
        $this->assertEquals(7, $output['ret']['user_id']);
        $this->assertEquals(1, $output['ret']['group']);
        $this->assertEquals(10000, $output['ret']['line']);
        $this->assertEquals(5000.00, $output['ret']['balance']);
        $this->assertFalse($output['ret']['enable']);
        $this->assertFalse($credit->isEnable());
    }

    /**
     * 測試額度停用但找不到該額度資料
     */
    public function testDisableWithCreditNotFound()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $client->request('PUT', '/api/credit/111/disable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150060001, $output['code']);
        $this->assertEquals('No credit found', $output['msg']);
    }

    /**
     * 測試先進行交易，再將額度停用，最後進行交易
     */
    public function testOpAndDisableAndOp()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $credit = $em->find('BBDurianBundle:Credit', 3);
        $this->assertTrue($credit->isEnable());
        $em->clear();

        // 1. 先交易
        $parameters = [
            'amount' => -1000,
            'at'     => (new \DateTime)->format(\DateTime::ISO8601),
            'opcode' => 20001
        ];
        $client->request('PUT', '/api/user/7/credit/1/op', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 2. 停用
        $client->request('PUT', '/api/credit/3/disable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 3. 最後再交易，應該要失敗
        $client->request('PUT', '/api/user/7/credit/1/op', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150060012, $output['code']);
        $this->assertEquals('Credit is disabled', $output['msg']);
    }

    /**
     * 測試額度啟用
     */
    public function testEnable()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $credit = $em->find('BB\DurianBundle\Entity\Credit', 3);

        $credit->disable();

        $em->flush();

        $em->clear();

        $client->request('PUT', '/api/credit/3/enable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals("credit", $logOperation->getTableName());
        $this->assertEquals("@user_id:7", $logOperation->getMajorKey());
        $this->assertEquals("@group_num:1, @enable:false=>true", $logOperation->getMessage());

        $credit = $em->find('BB\DurianBundle\Entity\Credit', 3);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret']['id']);
        $this->assertEquals(7, $output['ret']['user_id']);
        $this->assertEquals(1, $output['ret']['group']);
        $this->assertEquals(10000, $output['ret']['line']);
        $this->assertEquals(5000.00, $output['ret']['balance']);
        $this->assertTrue($output['ret']['enable']);
        $this->assertTrue($credit->isEnable());

        //上層停用是否下層也顯示停用
        $credit = $em->find('BB\DurianBundle\Entity\Credit', 1);
        $credit->disable();

        $em->flush();

        $client->request('PUT', '/api/credit/3/enable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertFalse($output['ret']['enable']);
    }

    /**
     * 測試額度啟用但找不到該額度資料
     */
    public function testEnableWithCreditNotFound()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $client->request('PUT', '/api/credit/111/enable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150060001, $output['code']);
        $this->assertEquals('No credit found', $output['msg']);
    }

    /**
     * 測試先停用，再進行交易，再啟用，最後進行交易
     */
    public function testDisableAndOpAndEnableAndOp()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $credit = $em->find('BBDurianBundle:Credit', 3);
        $this->assertTrue($credit->isEnable());
        $em->clear();

        // 1. 停用
        $client->request('PUT', '/api/credit/3/disable');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 2. 交易
        $parameters = [
            'amount' => -1000,
            'at'     => (new \DateTime)->format(\DateTime::ISO8601),
            'opcode' => 20001
        ];
        $client->request('PUT', '/api/user/7/credit/1/op', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150060012, $output['code']);
        $this->assertEquals('Credit is disabled', $output['msg']);

        // 3. 啟用
        $client->request('PUT', '/api/credit/3/enable');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 4. 最後再交易，應該會成功
        $client->request('PUT', '/api/user/7/credit/1/op', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
    }

    /**
     * 歸零所有下層信用額度
     */
    public function testRecoverChildCredit()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $creditUpper = $em->find('BBDurianBundle:Credit', 1);

        $this->assertEquals(15000, $creditUpper->getLine());
        $this->assertEquals(10000, $creditUpper->getTotalLine());

        $creditMiddle = $em->find('BBDurianBundle:Credit', 3);

        $this->assertEquals(10000, $creditMiddle->getLine());
        $this->assertEquals(5000, $creditMiddle->getTotalLine());

        $creditLower = $em->find('BBDurianBundle:Credit', 5);

        $this->assertEquals(5000, $creditLower->getLine());
        $this->assertEquals(0, $creditLower->getTotalLine());

        $repo = $em->getRepository('BBDurianBundle:Credit');
        $user = $em->find('BBDurianBundle:User', 6);
        $repo->updateChildCreditToZero($user, 1);

        $em->clear();

        //檢查下層額度
        $credit = $em->find('BBDurianBundle:Credit', 3);

        $this->assertEquals(0, $credit->getLine());
        $this->assertEquals(0, $credit->getTotalLine());

        $credit = $em->find('BBDurianBundle:Credit', 5);

        $this->assertEquals(0, $credit->getLine());
        $this->assertEquals(0, $credit->getTotalLine());
    }

    /**
     * 測試額度回收
     */
    public function testRevocerCredit()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $creditUpper = $em->find('BB\DurianBundle\Entity\Credit', 1);

        $this->assertEquals(15000, $creditUpper->getLine());
        $this->assertEquals(10000, $creditUpper->getTotalLine());

        $creditMiddle = $em->find('BB\DurianBundle\Entity\Credit', 3);

        $this->assertEquals(10000, $creditMiddle->getLine());
        $this->assertEquals(5000, $creditMiddle->getTotalLine());

        $creditLower = $em->find('BB\DurianBundle\Entity\Credit', 5);

        $this->assertEquals(5000, $creditLower->getLine());
        $this->assertEquals(0, $creditLower->getTotalLine());

        $redisWallet1 = $this->getContainer()->get('snc_redis.wallet1');
        $redisWallet2 = $this->getContainer()->get('snc_redis.wallet2');
        $redisWallet3 = $this->getContainer()->get('snc_redis.wallet3');
        $redisWallet4 = $this->getContainer()->get('snc_redis.wallet4');

        //上層的Redis信用額度資料
        $creditKeyUpper = 'credit_6_1';
        $indexKeyUpper = 'credit_period_index_6_1';
        $periodKeyUpper1 = 'credit_period_6_1_period_20120717';
        $periodKeyUpper2 = 'credit_period_6_1_period_20120718';
        $periodKeyUpper3 = 'credit_period_6_1_period_20120719';

        $redisWallet2->hset($creditKeyUpper, 'line', $creditUpper->getLine());
        $redisWallet2->hset($creditKeyUpper, 'total_line', $creditUpper->getTotalLine());
        $redisWallet2->hset($creditKeyUpper, 'version', $creditUpper->getVersion());

        $redisWallet2->hset($periodKeyUpper1, 'amount', 10);
        $redisWallet2->hset($periodKeyUpper2, 'amount', 10);
        $redisWallet2->hset($periodKeyUpper3, 'amount', 10);

        $redisWallet2->zadd($indexKeyUpper, 20120717, $periodKeyUpper1);
        $redisWallet2->zadd($indexKeyUpper, 20120718, $periodKeyUpper2);
        $redisWallet2->zadd($indexKeyUpper, 20120719, $periodKeyUpper3);

        //中間的Redis信用額度資料
        $creditKeyMiddle = 'credit_7_1';
        $indexKeyMiddle = 'credit_period_index_7_1';
        $periodKeyMiddle1 = 'credit_period_7_1_period_20120717';
        $periodKeyMiddle2 = 'credit_period_7_1_period_20120718';
        $periodKeyMiddle3 = 'credit_period_7_1_period_20120719';

        $redisWallet3->hset($creditKeyMiddle, 'line', $creditMiddle->getLine());
        $redisWallet3->hset($creditKeyMiddle, 'total_line', $creditMiddle->getTotalLine());
        $redisWallet3->hset($creditKeyMiddle, 'version', $creditMiddle->getVersion());

        $redisWallet3->hset($periodKeyMiddle1, 'amount', 10);
        $redisWallet3->hset($periodKeyMiddle2, 'amount', 10);
        $redisWallet3->hset($periodKeyMiddle3, 'amount', 10);

        $redisWallet3->zadd($indexKeyMiddle, 20120717, $periodKeyMiddle1);
        $redisWallet3->zadd($indexKeyMiddle, 20120718, $periodKeyMiddle2);
        $redisWallet3->zadd($indexKeyMiddle, 20120719, $periodKeyMiddle3);

        //下層的Redis信用額度資料
        $creditKeyLower = 'credit_8_1';
        $indexKeyLower = 'credit_period_index_8_1';
        $periodKeyLower1 = 'credit_period_8_1_period_20120717';
        $periodKeyLower2 = 'credit_period_8_1_period_20120718';
        $periodKeyLower3 = 'credit_period_8_1_period_20120719';

        $redisWallet4->hset($creditKeyLower, 'line', $creditLower->getLine());
        $redisWallet4->hset($creditKeyLower, 'total_line', $creditLower->getTotalLine());
        $redisWallet4->hset($creditKeyLower, 'version', $creditLower->getVersion());

        $redisWallet4->hset($periodKeyLower1, 'amount', 10);
        $redisWallet4->hset($periodKeyLower2, 'amount', 10);
        $redisWallet4->hset($periodKeyLower3, 'amount', 10);

        $redisWallet4->zadd($indexKeyLower, 20120717, $periodKeyLower1);
        $redisWallet4->zadd($indexKeyLower, 20120718, $periodKeyLower2);
        $redisWallet4->zadd($indexKeyLower, 20120719, $periodKeyLower3);

        $client = $this->createClient();
        $client->request('PUT', '/api/credit/1/recover', array('force'=>1));

        $em->clear();

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals("credit", $logOperation->getTableName());
        $this->assertEquals("@user_id:6", $logOperation->getMajorKey());
        $this->assertEquals("@group_num:1", $logOperation->getMessage());

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertEquals(6, $output['ret']['user_id']);
        $this->assertEquals(1, $output['ret']['group']);
        $this->assertEquals(15000, $output['ret']['line']);

        $credit = $em->find('BB\DurianBundle\Entity\Credit', 1);
        $this->assertEquals(15000, $credit->getLine());
        $this->assertEquals(0, $credit->getTotalLine());

        $credit = $em->find('BB\DurianBundle\Entity\Credit', 3);

        $this->assertEquals(0, $credit->getLine());
        $this->assertEquals(0, $credit->getTotalLine());

        $credit = $em->find('BB\DurianBundle\Entity\Credit', 5);

        $this->assertEquals(0, $credit->getLine());
        $this->assertEquals(0, $credit->getTotalLine());

        $this->assertFalse($redisWallet2->exists($creditKeyUpper));
        $this->assertFalse($redisWallet3->exists($creditKeyMiddle));
        $this->assertFalse($redisWallet4->exists($creditKeyLower));
        $this->assertFalse($redisWallet2->exists($indexKeyUpper));
        $this->assertFalse($redisWallet3->exists($indexKeyMiddle));
        $this->assertFalse($redisWallet4->exists($indexKeyLower));

        $this->assertFalse($redisWallet2->exists($periodKeyUpper1));
        $this->assertFalse($redisWallet2->exists($periodKeyUpper2));
        $this->assertFalse($redisWallet3->exists($periodKeyMiddle2));
        $this->assertFalse($redisWallet3->exists($periodKeyMiddle3));
        $this->assertFalse($redisWallet4->exists($periodKeyLower1));
        $this->assertFalse($redisWallet4->exists($periodKeyLower3));

        //測試回收後會將markRecovering自redis中移除
        $markName = 'credit_in_recovering';
        $this->assertFalse($redisWallet1->exists($markName));
    }

    /**
     * 測試額度回收但找不到該額度資料
     */
    public function testRecoverCreditWithCreditNotFound()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $client->request('PUT', '/api/credit/111/recover');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150060001, $output['code']);
        $this->assertEquals('No credit found', $output['msg']);
    }

    /**
     * 測試回收時已有早餐單
     */
    public function testRecoverCreditButStillHaveOrderTomorrow()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $creditUpper = $em->find('BB\DurianBundle\Entity\Credit', 4);

        $this->assertEquals(5000, $creditUpper->getLine());
        $this->assertEquals(3000, $creditUpper->getTotalLine());

        $client = $this->createClient();
        $date = new \DateTime('now', new \DateTimeZone('Asia/Taipei'));
        $parameters = array('amount' => -1000, 'at' => $date->format('Y-m-d H:i:s'),
                            'opcode' => 40000);
        //當天先下一次
        $client->request('PUT', '/api/user/8/credit/2/op', $parameters);

        $cmdParams = [
            '--entry' => 1,
            '--period' => 1
        ];
        $this->runCommand('durian:sync-credit', $cmdParams);

        $client->request('PUT', '/api/credit/4/recover');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 測試是否有寫操作紀錄
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEmpty($logOperation);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Can not recover credit due to none zero amount of children credit', $output['msg']);
        $this->assertEquals(150060017, $output['code']);

        //再一下一筆兩天後的
        $date->add(new \DateInterval('P2D'));

        $parameters = array('amount' => -1000, 'at' => $date->format('Y-m-d H:i:s'),
                            'opcode' => 40000);

        $client->request('PUT', '/api/user/8/credit/2/op', $parameters);

        $this->runCommand('durian:sync-credit', $cmdParams);

        $client->request('PUT', '/api/credit/4/recover');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Can not recover credit due to none zero amount of children credit', $output['msg']);
        $this->assertEquals(150060017, $output['code']);
    }

    /**
     * 測試回收時額度尚未同步
     */
    public function testRecoverCreditButUnsynchronized()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $creditUpper = $em->find('BB\DurianBundle\Entity\Credit', 4);

        $this->assertEquals(5000, $creditUpper->getLine());
        $this->assertEquals(3000, $creditUpper->getTotalLine());

        $redisWallet = $this->getContainer()->get('snc_redis.wallet3');

        //上層的Redis信用額度資料
        $creditKeyUpper = 'credit_7_2';

        $redisWallet->hset($creditKeyUpper, 'line', 3000);
        $redisWallet->hset($creditKeyUpper, 'total_line', 0);
        $redisWallet->hset($creditKeyUpper, 'version', $creditUpper->getVersion());

        $client = $this->createClient();

        $client->request('PUT', '/api/credit/4/recover');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Can not recover credit due to unsynchronised credit data', $output['msg']);
        $this->assertEquals(150060015, $output['code']);
    }

    /**
     * 測試回收信用額度出現ErrorCode60002,檢查是否有從回收狀態中移除
     */
    public function testRecoverButThrowException()
    {
        $redisWallet = $this->getContainer()->get('snc_redis.wallet1');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        //故意把要回收的信用額度line 設定為負值, 小於回收後的totalLine而跳例外
        $sql = "Update credit SET line = -1 WHERE id = ?";

        $em->getConnection()->executeUpdate($sql, ['4']);

        $em->clear();

        $markName = 'credit_in_recovering';

        //測試credit mark是否沒有資料
        $this->assertEquals(0, $redisWallet->scard($markName));

        //回收creditId 4
        $client->request('PUT', '/api/credit/4/recover');

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Not enough line to be dispensed', $output['msg']);
        $this->assertEquals('150060049', $output['code']);

        // 測試是否有從回收狀態中移除
        $this->assertEquals(0, $redisWallet->scard($markName));
    }

    /**
     * 測試修改信用額度明細備註欄位
     */
    public function testSetCreditEntryMemo()
    {
        $client = $this->createClient();

        $memo = '';
        for ($i = 0; $i < 100; $i++) {
            $memo .= 'a';
        }
        $parameter = ['memo' => $memo . '012'];

        $client->request('PUT', '/api/credit/entry/1', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($memo, $output['ret']['memo']);
    }

    /**
     * 測試修改明細時但明細不存在
     */
    public function testSetCreditEntryMemoWithoutEntry()
    {
        $client = $this->createClient();

        $parameter = array('memo' => 'hrhrhr');

        $client->request('PUT', '/api/credit/entry/999', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150060024, $output['code']);
        $this->assertEquals('No credit entry found', $output['msg']);
    }

    /**
     * 測試取得單筆信用額度明細
     */
    public function testGetCreditEntry()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/credit/entry/1');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertEquals(1, $output['ret']['credit_id']);
    }

    /**
     * 測試取單筆信用額度明細時，此筆明細不存在的情況
     */
    public function testGetCreditEntryNotFound()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/credit/entry/999');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150060024, $output['code']);
        $this->assertEquals('No credit entry found', $output['msg']);
    }

    /**
     * 測試輸入錯誤的groupNum,因而找不到使用者的credit
     */
    public function testGetEntriesButNoCreditFound()
    {
        $client = $this->createClient();

        $parameters = [
            'first_result' => 0,
            'max_results' => 4
        ];

        $client->request('GET', '/api/user/8/credit/100/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150060001, $output['code']);
        $this->assertEquals('No credit found', $output['msg']);
    }

    /**
     * 測試ref_id取得信用額度明細
     */
    public function testGetEntriesRefId()
    {
        $client = $this->createClient();

        //測試ref_id取得明細，帶入條件first_result, max_results
        $params = [
            'ref_id' => 1234567,
            'first_result' => 0,
            'max_results' => 1
        ];

        $client->request('GET', '/api/credit/entries_by_ref_id', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(50020, $output['ret'][0]['opcode']);
        $this->assertEquals(6, $output['ret'][0]['user_id']);
        $this->assertEquals(1234567, $output['ret'][0]['ref_id']);
        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(1, $output['pagination']['max_results']);
        $this->assertEquals(1, $output['pagination']['total']);
    }

    /**
     * 測試額度停用
     */
    public function testDisableByUser()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $client->request('PUT', '/api/user/7/credit/1/disable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals("credit", $logOperation->getTableName());
        $this->assertEquals("@user_id:7", $logOperation->getMajorKey());
        $this->assertEquals("@group_num:1, @enable:true=>false", $logOperation->getMessage());

        $credit = $em->find('BBDurianBundle:Credit', 3);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret']['id']);
        $this->assertEquals(7, $output['ret']['user_id']);
        $this->assertEquals(1, $output['ret']['group']);
        $this->assertEquals(10000, $output['ret']['line']);
        $this->assertEquals(5000.00, $output['ret']['balance']);
        $this->assertFalse($output['ret']['enable']);
        $this->assertFalse($credit->isEnable());
    }

    /**
     * 測試額度停用但找不到該額度資料
     */
    public function testDisableByUserWithCreditNotFound()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/user/999/credit/1/disable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150060001, $output['code']);
        $this->assertEquals('No credit found', $output['msg']);
    }

    /**
     * 測試額度啟用
     */
    public function testEnableByUser()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $credit = $em->find('BBDurianBundle:Credit', 3);
        $credit->disable();
        $em->flush();

        $em->clear();

        $client->request('PUT', '/api/user/7/credit/1/enable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals("credit", $logOperation->getTableName());
        $this->assertEquals("@user_id:7", $logOperation->getMajorKey());
        $this->assertEquals("@group_num:1, @enable:false=>true", $logOperation->getMessage());

        $credit = $em->find('BBDurianBundle:Credit', 3);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret']['id']);
        $this->assertEquals(7, $output['ret']['user_id']);
        $this->assertEquals(1, $output['ret']['group']);
        $this->assertEquals(10000, $output['ret']['line']);
        $this->assertEquals(5000.00, $output['ret']['balance']);
        $this->assertTrue($output['ret']['enable']);
        $this->assertTrue($credit->isEnable());

        //上層停用是否下層也顯示停用
        $credit = $em->find('BBDurianBundle:Credit', 1);
        $credit->disable();
        $em->flush();

        $client->request('PUT', '/api/user/7/credit/1/enable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret']['id']);
        $this->assertEquals(7, $output['ret']['user_id']);
        $this->assertFalse($output['ret']['enable']);
    }

    /**
     * 測試額度啟用但找不到該額度資料
     */
    public function testEnableByUserWithCreditNotFound()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $client->request('PUT', '/api/user/999/credit/1/enable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150060001, $output['code']);
        $this->assertEquals('No credit found', $output['msg']);
    }

    /**
     * 測試額度回收
     */
    public function testRevocerCreditByUser()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $creditUpper = $em->find('BBDurianBundle:Credit', 1);

        $this->assertEquals(15000, $creditUpper->getLine());
        $this->assertEquals(10000, $creditUpper->getTotalLine());

        $creditMiddle = $em->find('BBDurianBundle:Credit', 3);

        $this->assertEquals(10000, $creditMiddle->getLine());
        $this->assertEquals(5000, $creditMiddle->getTotalLine());

        $creditLower = $em->find('BBDurianBundle:Credit', 5);

        $this->assertEquals(5000, $creditLower->getLine());
        $this->assertEquals(0, $creditLower->getTotalLine());

        $redisWallet1 = $this->getContainer()->get('snc_redis.wallet1');
        $redisWallet2 = $this->getContainer()->get('snc_redis.wallet2');
        $redisWallet3 = $this->getContainer()->get('snc_redis.wallet3');
        $redisWallet4 = $this->getContainer()->get('snc_redis.wallet4');

        //上層的Redis信用額度資料
        $creditKeyUpper = 'credit_6_1';
        $indexKeyUpper = 'credit_period_index_6_1';
        $periodKeyUpper1 = 'credit_period_6_1_period_20120717';
        $periodKeyUpper2 = 'credit_period_6_1_period_20120718';
        $periodKeyUpper3 = 'credit_period_6_1_period_20120719';

        $redisWallet2->hset($creditKeyUpper, 'line', $creditUpper->getLine());
        $redisWallet2->hset($creditKeyUpper, 'total_line', $creditUpper->getTotalLine());
        $redisWallet2->hset($creditKeyUpper, 'version', $creditUpper->getVersion());

        $redisWallet2->hset($periodKeyUpper1, 'amount', 10);
        $redisWallet2->hset($periodKeyUpper2, 'amount', 10);
        $redisWallet2->hset($periodKeyUpper3, 'amount', 10);

        $redisWallet2->zadd($indexKeyUpper, 20120717, $periodKeyUpper1);
        $redisWallet2->zadd($indexKeyUpper, 20120718, $periodKeyUpper2);
        $redisWallet2->zadd($indexKeyUpper, 20120719, $periodKeyUpper3);

        //中間的Redis信用額度資料
        $creditKeyMiddle = 'credit_7_1';
        $indexKeyMiddle = 'credit_period_index_7_1';
        $periodKeyMiddle1 = 'credit_period_7_1_period_20120717';
        $periodKeyMiddle2 = 'credit_period_7_1_period_20120718';
        $periodKeyMiddle3 = 'credit_period_7_1_period_20120719';

        $redisWallet3->hset($creditKeyMiddle, 'line', $creditMiddle->getLine());
        $redisWallet3->hset($creditKeyMiddle, 'total_line', $creditMiddle->getTotalLine());
        $redisWallet3->hset($creditKeyMiddle, 'version', $creditMiddle->getVersion());

        $redisWallet3->hset($periodKeyMiddle1, 'amount', 10);
        $redisWallet3->hset($periodKeyMiddle2, 'amount', 10);
        $redisWallet3->hset($periodKeyMiddle3, 'amount', 10);

        $redisWallet3->zadd($indexKeyMiddle, 20120717, $periodKeyMiddle1);
        $redisWallet3->zadd($indexKeyMiddle, 20120718, $periodKeyMiddle2);
        $redisWallet3->zadd($indexKeyMiddle, 20120719, $periodKeyMiddle3);

        //下層的Redis信用額度資料
        $creditKeyLower = 'credit_8_1';
        $indexKeyLower = 'credit_period_index_8_1';
        $periodKeyLower1 = 'credit_period_8_1_period_20120717';
        $periodKeyLower2 = 'credit_period_8_1_period_20120718';
        $periodKeyLower3 = 'credit_period_8_1_period_20120719';

        $redisWallet4->hset($creditKeyLower, 'line', $creditLower->getLine());
        $redisWallet4->hset($creditKeyLower, 'total_line', $creditLower->getTotalLine());
        $redisWallet4->hset($creditKeyLower, 'version', $creditLower->getVersion());

        $redisWallet4->hset($periodKeyLower1, 'amount', 10);
        $redisWallet4->hset($periodKeyLower2, 'amount', 10);
        $redisWallet4->hset($periodKeyLower3, 'amount', 10);

        $redisWallet4->zadd($indexKeyLower, 20120717, $periodKeyLower1);
        $redisWallet4->zadd($indexKeyLower, 20120718, $periodKeyLower2);
        $redisWallet4->zadd($indexKeyLower, 20120719, $periodKeyLower3);

        $client = $this->createClient();
        $client->request('PUT', '/api/user/6/credit/1/recover', ['force' => 1]);

        $em->clear();

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals("credit", $logOperation->getTableName());
        $this->assertEquals("@user_id:6", $logOperation->getMajorKey());
        $this->assertEquals("@group_num:1", $logOperation->getMessage());

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertEquals(6, $output['ret']['user_id']);
        $this->assertEquals(1, $output['ret']['group']);
        $this->assertEquals(15000, $output['ret']['line']);

        $credit = $em->find('BBDurianBundle:Credit', 1);
        $this->assertEquals(15000, $credit->getLine());
        $this->assertEquals(0, $credit->getTotalLine());

        $credit = $em->find('BBDurianBundle:Credit', 3);

        $this->assertEquals(0, $credit->getLine());
        $this->assertEquals(0, $credit->getTotalLine());

        $credit = $em->find('BBDurianBundle:Credit', 5);

        $this->assertEquals(0, $credit->getLine());
        $this->assertEquals(0, $credit->getTotalLine());

        $this->assertFalse($redisWallet2->exists($creditKeyUpper));
        $this->assertFalse($redisWallet3->exists($creditKeyMiddle));
        $this->assertFalse($redisWallet4->exists($creditKeyLower));
        $this->assertFalse($redisWallet2->exists($indexKeyUpper));
        $this->assertFalse($redisWallet3->exists($indexKeyMiddle));
        $this->assertFalse($redisWallet4->exists($indexKeyLower));

        $this->assertFalse($redisWallet2->exists($periodKeyUpper1));
        $this->assertFalse($redisWallet2->exists($periodKeyUpper2));
        $this->assertFalse($redisWallet3->exists($periodKeyMiddle2));
        $this->assertFalse($redisWallet3->exists($periodKeyMiddle3));
        $this->assertFalse($redisWallet4->exists($periodKeyLower1));
        $this->assertFalse($redisWallet4->exists($periodKeyLower3));

        //測試回收後會將markRecovering自redis中移除
        $markName = 'credit_in_recovering';
        $this->assertFalse($redisWallet1->exists($markName));
    }

    /**
     * 測試額度回收但找不到該額度資料
     */
    public function testRecoverCreditByUserWithCreditNotFound()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/user/999/credit/1/recover');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150060001, $output['code']);
        $this->assertEquals('No credit found', $output['msg']);
    }

    /**
     * 測試回收時已有早餐單
     */
    public function testRecoverCreditByUserButStillHaveOrderTomorrow()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $creditUpper = $em->find('BBDurianBundle:Credit', 4);

        $this->assertEquals(5000, $creditUpper->getLine());
        $this->assertEquals(3000, $creditUpper->getTotalLine());

        $client = $this->createClient();
        $date = new \DateTime('now', new \DateTimeZone('Asia/Taipei'));

        $parameters = [
            'amount' => -1000,
            'at' => $date->format('Y-m-d H:i:s'),
            'opcode' => 40000
        ];

        //當天先下一次
        $client->request('PUT', '/api/user/8/credit/2/op', $parameters);

        $cmdParams = [
            '--entry' => 1,
            '--period' => 1
        ];

        $this->runCommand('durian:sync-credit', $cmdParams);

        $client->request('PUT', '/api/user/7/credit/2/recover');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 測試是否有寫操作紀錄
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEmpty($logOperation);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Can not recover credit due to none zero amount of children credit', $output['msg']);
        $this->assertEquals(150060017, $output['code']);

        // 再一下一筆兩天後的
        $date->add(new \DateInterval('P2D'));

        $client->request('PUT', '/api/user/8/credit/2/op', $parameters);

        $this->runCommand('durian:sync-credit', $cmdParams);

        $client->request('PUT', '/api/user/7/credit/2/recover');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Can not recover credit due to none zero amount of children credit', $output['msg']);
        $this->assertEquals(150060017, $output['code']);
    }

    /**
     * 測試回收時額度尚未同步
     */
    public function testRecoverCreditByUserButUnsynchronized()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $creditUpper = $em->find('BBDurianBundle:Credit', 4);

        $this->assertEquals(5000, $creditUpper->getLine());
        $this->assertEquals(3000, $creditUpper->getTotalLine());

        $redisWallet = $this->getContainer()->get('snc_redis.wallet3');

        // 上層的Redis信用額度資料
        $creditKeyUpper = 'credit_7_2';

        $redisWallet->hset($creditKeyUpper, 'line', 3000);
        $redisWallet->hset($creditKeyUpper, 'total_line', 0);
        $redisWallet->hset($creditKeyUpper, 'version', $creditUpper->getVersion());

        $client = $this->createClient();

        $client->request('PUT', '/api/user/7/credit/2/recover');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Can not recover credit due to unsynchronised credit data', $output['msg']);
        $this->assertEquals(150060015, $output['code']);
    }

    /**
     * 測試回收信用額度出現ErrorCode60002,檢查是否有從回收狀態中移除
     */
    public function testRecoverByUserButThrowException()
    {
        $redisWallet = $this->getContainer()->get('snc_redis.wallet1');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 故意把要回收的信用額度line設定為負值, 小於回收後的totalLine而跳例外
        $sql = "Update credit SET line = -1 WHERE id = ?";

        $em->getConnection()->executeUpdate($sql, ['4']);

        $em->clear();

        $markName = 'credit_in_recovering';

        // 測試credit mark是否沒有資料
        $this->assertEquals(0, $redisWallet->scard($markName));

        // 回收creditId 4
        $client->request('PUT', '/api/user/7/credit/2/recover');

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Not enough line to be dispensed', $output['msg']);
        $this->assertEquals('150060049', $output['code']);

        // 測試是否有從回收狀態中移除
        $this->assertEquals(0, $redisWallet->scard($markName));
    }
}
