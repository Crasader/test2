<?php
namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Consumer\Poper;
use BB\DurianBundle\Controller\OrderController;

class OrderFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCardData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCardEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCreditData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCreditPeriodData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareLimitData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareLimitNextData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeDataTwo',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeEntryDataTwo',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserRentData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareUpdateCronForControllerData'
        ];
        $this->loadFixtures($classnames);

        $this->loadFixtures([], 'entry');

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadExchangeData'
        ];
        $this->loadFixtures($classnames, 'share');

        $redis = $this->getContainer()->get('snc_redis.sequence');

        $redis->set('cash_seq', 1000);
        $redis->set('cashfake_seq', 1000);
        $redis->set('card_seq', 1000);
    }

    /**
     * 測試以現金下注
     */
    public function testDoOrderByCash()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $client = $this->createClient();
        $now = new \DateTime('now');
        $now = $now->format(\DateTime::ISO8601);

        $user = $em->find('BB\DurianBundle\Entity\User', 7);
        $cash = $user->getCash();

        $this->assertEquals(1000, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());

        // 啟用租卡
        $card = $em->find('BB\DurianBundle\Entity\User', 6)
                   ->getCard();
        $card->enable();

        $cardEntry = $card->addEntry('9901', 'tester', 200);
        $cardEntry->setId(4);
        $em->persist($cardEntry);

        $em->flush();
        $em->clear();

        $memo = '';
        for ($i = 0; $i < 100; $i++) {
            $memo .= 'a';
        }

        $parameters = [
            'pay_way' => 'cash',
            'amount' => '-10',
            'opcode' => '1001',
            'sharelimit_group_num' => '1',
            'card_amount' => -1,
            'memo' => $memo . '012',
            'ref_id' => '1',
            'auto_commit' => '1',
            'operator' => 'ztester',
            'at' => $now
        ];

        $client->request('POST', '/api/user/7/order', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $poper = new Poper();
        $poper->runPop($this->getContainer(), 'cash');

        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 1]);

        $user = $em->find('BB\DurianBundle\Entity\User', 7);
        $cash = $user->getCash();
        $cashEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $output['ret']['cash_entry']['id']]);
        $pdwe = $em->getRepository('BBDurianBundle:PaymentDepositWithdrawEntry')
            ->findOneBy(['id' => $output['ret']['cash_entry']['id']]);

        $this->assertEquals($cash->getBalance(), $output['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $output['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $output['ret']['cash']['pre_add']);

        $this->assertEquals($cashEntry->getOpcode(), $output['ret']['cash_entry']['opcode']);
        $this->assertEquals($cashEntry->getAmount(), $output['ret']['cash_entry']['amount']);
        $this->assertEquals($cashEntry->getBalance(), $output['ret']['cash_entry']['balance']);
        $this->assertEquals($memo, $output['ret']['cash_entry']['memo']);
        $this->assertEquals($cashEntry->getMemo(), $output['ret']['cash_entry']['memo']);
        $this->assertEquals($cashEntry->getRefId(), $output['ret']['cash_entry']['ref_id']);
        $this->assertEquals($pdwe->getOperator(), $output['ret']['cash_entry']['operator']['username']);
        $this->assertEquals($cashEntry->getCashVersion(), $output['ret']['cash_entry']['cash_version']);

        $this->assertEquals(5, $output['ret']['card_entry']['card_id']);
        $this->assertEquals(6, $output['ret']['card_entry']['user_id']);
        $this->assertEquals(-1, $output['ret']['card_entry']['amount']);
        $this->assertEquals(199, $output['ret']['card_entry']['balance']);
        $this->assertEquals('ztester', $output['ret']['card_entry']['operator']);
        $this->assertEquals(2, $output['ret']['card_entry']['card_version']);

        $this->assertEquals(20, $output['ret']['sharelimit'][0]);
        $this->assertEquals(30, $output['ret']['sharelimit'][1]);
        $this->assertEquals(20, $output['ret']['sharelimit'][2]);
        $this->assertEquals(20, $output['ret']['sharelimit'][3]);
        $this->assertEquals(10, $output['ret']['sharelimit'][4]);
        $this->assertEquals(0, $output['ret']['sharelimit'][5]);
        $this->assertEquals(0, $output['ret']['sharelimit'][6]);

        $this->assertEquals(6, $output['ret']['all_parents'][0]);
        $this->assertEquals(5, $output['ret']['all_parents'][1]);
        $this->assertEquals(4, $output['ret']['all_parents'][2]);
        $this->assertEquals(3, $output['ret']['all_parents'][3]);
        $this->assertEquals(2, $output['ret']['all_parents'][4]);
    }

    /**
     * 測試以快開額度下注
     */
    public function testDoOrderByCashFake()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BB\DurianBundle\Entity\User', 7);
        $cashfake = $user->getCashFake();

        $now = new \DateTime('now');
        $now = $now->format(\DateTime::ISO8601);

        // 啟用租卡
        $card = $em->find('BB\DurianBundle\Entity\User', 6)
                   ->getCard();
        $card->enable();

        $cardEntry = $card->addEntry('9901', 'tester', 200);
        $cardEntry->setId(4);
        $em->persist($cardEntry);

        $em->flush();
        $em->clear();

        $memo = '';
        for ($i = 0; $i < 100; $i++) {
            $memo .= 'a';
        }

        $parameters = [
            'pay_way' => 'cashfake',
            'amount' => '-10',
            'opcode' => '1001',
            'card_amount' => -1,
            'memo' => $memo . '012',
            'sharelimit_group_num' => '1',
            'ref_id' => '1',
            'operator' => 'ztester',
            'auto_commit' => '1',
            'at' => $now
        ];

        $client->request('POST', '/api/user/7/order', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 跑背景程式讓queue被消化
        $params = [
            '--entry' => true,
            '--balance' => true
        ];
        $this->runCommand('durian:sync-cash-fake', $params);

        $user = $em->find('BB\DurianBundle\Entity\User', 7);
        $cashfake = $user->getCashFake();
        $cashfakeEntry = $em->getRepository('BB\DurianBundle\Entity\CashFakeEntry')
                ->findOneBy(array('id' => $output['ret']['cash_fake_entry'][0]['id']));
        $operator = $em->getRepository('BB\DurianBundle\Entity\CashFakeEntryOperator')
                ->findOneBy(array('entryId' => $output['ret']['cash_fake_entry'][0]['id']));

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($cashfake->getId(), $output['ret']['cash_fake']['id']);
        $this->assertEquals('CNY', $output['ret']['cash_fake']['currency']);

        $this->assertEquals($cashfake->getBalance(), $output['ret']['cash_fake']['balance']);
        $this->assertEquals($cashfake->getPreSub(), $output['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashfake->getPreAdd(), $output['ret']['cash_fake']['pre_add']);

        $this->assertEquals($cashfakeEntry->getOpcode(), $output['ret']['cash_fake_entry'][0]['opcode']);
        $this->assertEquals($cashfakeEntry->getAmount(), $output['ret']['cash_fake_entry'][0]['amount']);
        $this->assertEquals($memo, $output['ret']['cash_fake_entry'][0]['memo']);
        $this->assertEquals($cashfakeEntry->getMemo(), $output['ret']['cash_fake_entry'][0]['memo']);
        $this->assertEquals($cashfakeEntry->getRefId(), $output['ret']['cash_fake_entry'][0]['ref_id']);
        $this->assertEquals($operator->getUsername(), $output['ret']['cash_fake_entry'][0]['operator']['username']);
        $this->assertEquals($cashfakeEntry->getCashFakeVersion(), $output['ret']['cash_fake_entry'][0]['cash_fake_version']);

        $this->assertEquals(5, $output['ret']['card_entry']['card_id']);
        $this->assertEquals(6, $output['ret']['card_entry']['user_id']);
        $this->assertEquals(-1, $output['ret']['card_entry']['amount']);
        $this->assertEquals(199, $output['ret']['card_entry']['balance']);
        $this->assertEquals('ztester', $output['ret']['card_entry']['operator']);
        $this->assertEquals(2, $output['ret']['card_entry']['card_version']);

        $this->assertEquals(20, $output['ret']['sharelimit'][0]);
        $this->assertEquals(30, $output['ret']['sharelimit'][1]);
        $this->assertEquals(20, $output['ret']['sharelimit'][2]);
        $this->assertEquals(20, $output['ret']['sharelimit'][3]);
        $this->assertEquals(10, $output['ret']['sharelimit'][4]);
        $this->assertEquals(0, $output['ret']['sharelimit'][5]);
        $this->assertEquals(0, $output['ret']['sharelimit'][6]);

        $this->assertEquals(6, $output['ret']['all_parents'][0]);
        $this->assertEquals(5, $output['ret']['all_parents'][1]);
        $this->assertEquals(4, $output['ret']['all_parents'][2]);
        $this->assertEquals(3, $output['ret']['all_parents'][3]);
        $this->assertEquals(2, $output['ret']['all_parents'][4]);
    }

    /**
     * 測試以信用額度下注
     */
    public function testDoOrderByCredit()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $card = $em->find('BB\DurianBundle\Entity\Card', 7);
        $card->enable();
        $cardEntry = $card->addEntry('9901', 'tester', 200);
        $cardEntry->setId(4);
        $em->persist($cardEntry);

        $em->flush();
        $em->clear();
        $now = new \DateTime('now');
        $morn = clone $now;
        $morn->add(new \DateInterval('P1D'));

        $memo = '';
        for ($i = 0; $i < 100; $i++) {
            $memo .= 'a';
        }

        $parameters = [
            'pay_way' => 'credit',
            'amount' => -200,
            'opcode' => '1001',
            'credit_group_num' => '1',
            'sharelimit_group_num' => '1',
            'card_amount' => -1,
            'memo' => $memo . '012',
            'ref_id' => '1',
            'at' => $now->format(\DateTime::ISO8601),
            'credit_at' => $morn->format(\DateTime::ISO8601),
            'auto_commit' => '1'
        ];

        $client->request('POST', '/api/user/8/order', $parameters);

        $this->runCommand('durian:sync-credit', ['--entry' => 1]);

        $this->runCommand('durian:run-card-poper');
        $this->runCommand('durian:run-card-sync');

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $credit = $em->find('BB\DurianBundle\Entity\Credit', 5);
        $creditEntry = $em->find('BBDurianBundle:CreditEntry', 1);
        $card = $em->find('BB\DurianBundle\Entity\Card', 7);
        $cardEntry = $em->find('BB\DurianBundle\Entity\CardEntry', $output['ret']['card_entry']['id']);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($credit->getId(), $output['ret']['credit']['id']);
        $this->assertEquals(4800, $output['ret']['credit']['balance']);
        $this->assertEquals($credit->getGroupNum(), $output['ret']['credit']['group']);
        $this->assertEquals($memo, $creditEntry->getMemo());

        $this->assertEquals($card->getId(), $output['ret']['card']['id']);
        $this->assertEquals($card->getUser()->getId(), $output['ret']['card']['user_id']);
        $this->assertEquals($card->getBalance(), $output['ret']['card']['balance']);
        $this->assertEquals($card->isEnabled(), $output['ret']['card']['enable']);

        $this->assertEquals($cardEntry->getId(), $output['ret']['card_entry']['id']);
        $this->assertEquals($cardEntry->getOpcode(), $output['ret']['card_entry']['opcode']);
        $this->assertEquals($cardEntry->getAmount(), $output['ret']['card_entry']['amount']);
        $this->assertEquals($cardEntry->getRefId(), $output['ret']['card_entry']['ref_id']);
        $this->assertEquals('tester', $output['ret']['card_entry']['operator']);
        $this->assertEquals($cardEntry->getCardVersion(), $output['ret']['card_entry']['card_version']);
    }

    /**
     * 測試以信用額度批次下注時, 使用者慣用幣別為台幣，交易及回傳皆為台幣幣值
     */
    public function testDoOrderByCreditAndCurrencyIsTWD()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $user = $em->find('BBDurianBundle:User', 8);
        $user->setCurrency(901);
        $em->flush();

        $periodAt = new \DateTime('30 days ago-0400');
        $periodAt->setTime(12, 00, 00);

        // 先檢查目前的金額
        $parameters = ['at' => $periodAt->format(\DateTime::ISO8601)];
        $client->request('GET', '/api/user/8/credit/2', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('13452', $output['ret']['line']);
        $this->assertEquals('13452.91', $output['ret']['balance']);

        // 執行並測試
        $parameters = [
            'pay_way'          => 'credit',
            'amount'           => -1000,
            'opcode'           => 40000,
            'credit_group_num' => 2,
            'credit_at'        => $periodAt->format(\DateTime::ISO8601),
        ];

        $client->request('POST', '/api/user/8/order', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(6, $output['ret']['credit']['id']);
        $this->assertEquals(13452, $output['ret']['credit']['line']);
        $this->assertEquals(12452.91, $output['ret']['credit']['balance']);
    }

    /**
     * 測試信用額度下注時可強制扣款
     */
    public function testDoOrderCreditWithForce()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $now = new \DateTime('now');
        $morn = clone $now;
        $morn->add(new \DateInterval('P1D'));

        // 允許信用額度停用, 以及允許額度扣到負數
        $user = $em->find('BBDurianBundle:User', 8);
        $user->getCredit(1)->disable();
        $em->flush();

        $parameters = [
            'pay_way' => 'credit',
            'amount' => -10000,
            'opcode' => '40000',
            'credit_group_num' => '1',
            'credit_at' => $morn->format(\DateTime::ISO8601),
            'auto_commit' => '1',
            'force' => true
        ];

        $client->request('POST', '/api/user/8/order', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['credit']['user_id']);
        $this->assertEquals(1, $output['ret']['credit']['group']);
        $this->assertEquals(5000, $output['ret']['credit']['line']);
        $this->assertEquals(-5000, $output['ret']['credit']['balance']);
    }

    /**
     * 測試傳入幣別轉換時，找不到匯率
     */
    public function testExchangeNotFound()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setCurrency(840);

        $em->persist($user);
        $em->flush();

        $now = new \DateTime('now');
        $now = $now->format(\DateTime::ISO8601);

        $parameters = [
            'pay_way'          => 'credit',
            'amount'           => -1000,
            'opcode'           => 40000,
            'credit_group_num' => 1,
            'credit_at'        => $now
        ];

        $client->request('POST', '/api/user/8/order', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150140020, $output['code']);
        $this->assertEquals('No such exchange', $output['msg']);
    }

    /**
     * 測試轉換匯率，當輸入amount為0時，則不需要轉換直接return 0
     */
    public function testExchangeWhenAmountIsZero()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setCurrency(840);

        $em->persist($user);
        $em->flush();

        $now = new \DateTime('now');
        $now = $now->format(\DateTime::ISO8601);

        $parameters = [
            'pay_way'          => 'credit',
            'amount'           => 0,
            'opcode'           => 40000,
            'credit_group_num' => 1,
            'credit_at'        => $now
        ];

        $client->request('POST', '/api/user/8/order', $parameters);
    }

    /**
     * 測試下注輸入無效ref_id的情況
     */
    public function testDoOrderInvalidRefId()
    {
        $client = $this->createClient();

        $parameters = array (
            'pay_way' => 'cash',
            'amount' => '-10',
            'opcode' => '1001',
            'ref_id' => '我是字串'
        );

        $client->request('POST', '/api/user/7/order', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150140016, $output['code']);
        $this->assertEquals('Invalid ref_id', $output['msg']);

        $client = $this->createClient();

        $parameters = array (
            'pay_way' => 'cash',
            'amount' => '-10',
            'opcode' => '1001',
            'ref_id' => -1
        );

        $client->request('POST', '/api/user/7/order', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150140016, $output['code']);
        $this->assertEquals('Invalid ref_id', $output['msg']);

        $parameters = array (
            'pay_way' => 'cash',
            'amount' => '-10',
            'opcode' => '1001',
            'ref_id' => 9223372036854775807
        );

        $client->request('POST', '/api/user/7/order', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150140016, $output['code']);
        $this->assertEquals('Invalid ref_id', $output['msg']);
    }

    /**
     * 測試下注memo非UTF8
     */
    public function testDoOrderMemoNotUtf8()
    {
        $client = $this->createClient();

        $parameters = array (
            'pay_way'       =>  'cash',
            'amount'        =>  '-10',
            'opcode'        =>  '1001',
            'memo'          =>  mb_convert_encoding('大吉大利', 'GB2312', 'UTF-8')
        );

        $client->request('POST', '/api/user/7/order', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150610002, $output['code']);
        $this->assertEquals('String must use utf-8 encoding', $output['msg']);
    }

    /**
     * 測試下注但帶入錯誤佔成群組
     */
    public function testDoOrderButNoInvalidSharelimitGroupNum()
    {
        $client = $this->createClient();

        $now = new \DateTime('now');
        $now = $now->format(\DateTime::ISO8601);
        $parameters = array (
            'pay_way'       =>  'cash',
            'amount'        =>  -200,
            'opcode'        =>  '1001',
            'sharelimit_group_num'     =>  '12',
            'card_amount'   =>  -1,
            'ref_id'        =>  '1',
            'at'            =>  $now,
            'auto_commit'   =>  '1'
        );

        $client->request('POST', '/api/user/8/order', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150080028, $output['code']);
        $this->assertEquals('User 8 has no sharelimit of group 12', $output['msg']);
    }

    /**
     * 測試下注但帶入錯誤信用額度群組
     */
    public function testDoOrderButNoInvalidCreditGroupNum()
    {
        $client = $this->createClient();

        $now = new \DateTime('now');
        $now = $now->format(\DateTime::ISO8601);
        $parameters = array (
            'pay_way'       =>  'credit',
            'amount'        =>  -200,
            'opcode'        =>  '1001',
            'credit_group_num'     =>  '3',
            'sharelimit_group_num'     =>  '1',
            'card_amount'   =>  -1,
            'ref_id'        =>  '1',
            'at'            =>  $now,
            'credit_at'     =>  $now,
            'auto_commit'   =>  '1'
        );

        $client->request('POST', '/api/user/8/order', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150140026, $output['code']);
        $this->assertEquals('No credit found', $output['msg']);
    }

    /**
     * 測試下注輸入無效amount的情況
     */
    public function testDoOrderWithInvalidAmount()
    {
        $client = $this->createClient();

        $parameters = array (
            'pay_way'   => 'cash',
            'amount'    => -10.00005566,
            'opcode'    => 1001
        );

        $client->request('POST', '/api/user/7/order', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150610003, $output['code']);
        $this->assertEquals('The decimal digit of amount exceeds limitation', $output['msg']);
    }

    /**
     * 測試下注但帶入過大/過小的金額
     */
    public function testDoOrderButOverSizedAmount()
    {
        $client = $this->createClient();

        $now = new \DateTime('now');
        $now = $now->format(\DateTime::ISO8601);

        //測極小
        $parameters = [
            'pay_way'       =>  'cash',
            'amount'        =>  -10000000001,
            'opcode'        =>  '1001',
            'ref_id'        =>  '1',
            'at'            =>  $now,
            'auto_commit'   =>  '1'
        ];

        $client->request('POST', '/api/user/8/order', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150140022, $output['code']);
        $this->assertEquals('Oversize amount given which exceeds the MAX', $output['msg']);

        //測極大
        $parameters = [
            'pay_way'       =>  'cash',
            'amount'        =>  10000000001,
            'opcode'        =>  '1001',
            'ref_id'        =>  '1',
            'at'            =>  $now,
            'auto_commit'   =>  '1'
        ];

        $client->request('POST', '/api/user/8/order', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150140022, $output['code']);
        $this->assertEquals('Oversize amount given which exceeds the MAX', $output['msg']);
    }

    /**
     * 測試下注餘額不足時不會誤扣租卡
     */
    public function testDoOrderWhenBalanceNotEnoughCannotReductCard()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redisWallet = $this->getContainer()->get('snc_redis.wallet4');
        $client = $this->createClient();

        $card = $em->find('BBDurianBundle:Card', 7);
        $card->enable();
        $cardEntry = $card->addEntry('9901', 'tester', 200);
        $cardEntry->setId(4);
        $em->persist($cardEntry);
        $originPoint = $card->getBalance();

        $em->flush();
        $em->clear();

        $now = new \DateTime('now');
        $morn = clone $now;
        $morn->add(new \DateInterval('P1D'));
        $parameters = [
            'pay_way' => 'cash',
            'amount' => -99999,
            'opcode' => 1001,
            'card_amount' => -1,
            'auto_commit' => 1
        ];

        $client->request('POST', '/api/user/8/order', $parameters);

        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-card-poper');

        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-card-sync');

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150580020, $output['code']);
        $this->assertEquals('Not enough balance', $output['msg']);

        // 確認租卡沒有誤扣
        $afterCard = $em->find('BB\DurianBundle\Entity\Card', 7);
        $cardEntry = $em->find('BB\DurianBundle\Entity\CardEntry', 5);

        $this->assertEquals($originPoint, $afterCard->getBalance());
        $this->assertNull($cardEntry);
    }

    /**
     * 測試下注時, 現金, 租卡可以強制扣款
     */
    public function testDoOrderCashCardWithForce()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BBDurianBundle:User', 8);
        $user->getCard()->enable();
        $em->flush();
        $em->clear();

        // auto_commit = 1, 允許使用者停權, 額度扣到負數
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setBankrupt(true);
        $em->flush();

        $parameters = [
            'pay_way' => 'cash',
            'amount' => -99999,
            'card_amount' => -1000,
            'opcode' => '40000',
            'auto_commit' => '1',
            'force' => true
        ];

        $client->request('POST', '/api/user/8/order', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(7, $output['ret']['cash']['id']);
        $this->assertEquals(8, $output['ret']['cash']['user_id']);
        $this->assertEquals(-98999, $output['ret']['cash']['balance']);
        $this->assertEquals(0, $output['ret']['cash']['pre_sub']);
        $this->assertEquals(0, $output['ret']['cash']['pre_add']);

        $this->assertEquals(7, $output['ret']['cash_entry']['cash_id']);
        $this->assertEquals(40000, $output['ret']['cash_entry']['opcode']);
        $this->assertEquals(-99999, $output['ret']['cash_entry']['amount']);
        $this->assertEquals(-98999, $output['ret']['cash_entry']['balance']);
        $this->assertEquals(3, $output['ret']['cash_entry']['cash_version']);

        $this->assertEquals(8, $output['ret']['card']['user_id']);
        $this->assertEquals(-1000, $output['ret']['card']['balance']);

        $this->assertEquals(8, $output['ret']['card_entry']['user_id']);
        $this->assertEquals(-1000, $output['ret']['card_entry']['amount']);
        $this->assertEquals(-1000, $output['ret']['card_entry']['balance']);
        $this->assertEquals(40000, $output['ret']['card_entry']['opcode']);
        $this->assertEquals(2, $output['ret']['card_entry']['card_version']);

        $user = $em->find('BBDurianBundle:User', 8);
        $user->setBankrupt(false);
        $em->flush();

        // auto_commit = 0, 允許使用者停權, 以及額度扣到負數
        // 走交易機制時, 無法進行租卡扣點, 故不測試租卡部分
        $user = $em->find('BBDurianBundle:User', 7);
        $user->setBankrupt(true);
        $em->flush();

        $parameters = [
            'pay_way' => 'cash',
            'amount' => -2000,
            'opcode' => '1001',
            'auto_commit' => '0',
            'force' => true
        ];

        $client->request('POST', '/api/user/7/order', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(6, $output['ret']['cash']['id']);
        $this->assertEquals(7, $output['ret']['cash']['user_id']);
        $this->assertEquals(1000, $output['ret']['cash']['balance']);
        $this->assertEquals(2000, $output['ret']['cash']['pre_sub']);
        $this->assertEquals(0, $output['ret']['cash']['pre_add']);

        $this->assertEquals(6, $output['ret']['cash_entry']['cash_id']);
        $this->assertEquals(1001, $output['ret']['cash_entry']['opcode']);
        $this->assertEquals(-2000, $output['ret']['cash_entry']['amount']);
    }

    /**
     * 測試快開額度下注時可以強制扣款
     */
    public function testDoOrderCashFakeWithForce()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // auto_commit = 1, 允許使用者停權, 以及額度扣到負數
        $user = $em->find('BBDurianBundle:User', 7);
        $user->setBankrupt(true);
        $em->flush();

        $parameters = [
            'pay_way' => 'cashfake',
            'amount' => '-600',
            'opcode' => '40000',
            'auto_commit' => '1',
            'force' => true
        ];

        $client->request('POST', '/api/user/7/order', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(7, $output['ret']['cash_fake']['user_id']);
        $this->assertEquals(-100, $output['ret']['cash_fake']['balance']);
        $this->assertEquals(0, $output['ret']['cash_fake']['pre_sub']);
        $this->assertEquals(0, $output['ret']['cash_fake']['pre_add']);

        $this->assertEquals(40000, $output['ret']['cash_fake_entry'][0]['opcode']);
        $this->assertEquals(-600, $output['ret']['cash_fake_entry'][0]['amount']);
        $this->assertEquals(-100, $output['ret']['cash_fake_entry'][0]['balance']);
        $this->assertEquals(2, $output['ret']['cash_fake_entry'][0]['cash_fake_version']);

        // auto_commit = 0, 允許使用者停權, 以及額度扣到負數
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setBankrupt(true);
        $em->flush();

        $parameters = [
            'pay_way' => 'cashfake',
            'amount' => '-1000',
            'opcode' => '1001',
            'auto_commit' => '0',
            'force' => true
        ];

        $client->request('POST', '/api/user/8/order', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['cash_fake']['user_id']);
        $this->assertEquals(500, $output['ret']['cash_fake']['balance']);
        $this->assertEquals(1000, $output['ret']['cash_fake']['pre_sub']);
        $this->assertEquals(0, $output['ret']['cash_fake']['pre_add']);

        $this->assertEquals(1001, $output['ret']['cash_fake_entry'][0]['opcode']);
        $this->assertEquals(-1000, $output['ret']['cash_fake_entry'][0]['amount']);
    }

    /**
     * 測試下注租卡點數不足時不會誤扣原本的額度
     */
    public function testDoOrderWhenCardPointNotEnough()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $client = $this->createClient();
        $user = $em->find('BB\DurianBundle\Entity\User', 7);
        $cashId = $user->getCash()->getId();
        $entries = $emEntry->getRepository('BBDurianBundle:CashEntry')->findBy(['cashId' => $cashId]);
        $originEntries = count($entries);
        $originBalance = $user->getCash()->getBalance();
        $user->getCard()->enable();
        $em->flush();
        $em->clear();

        $parameters = array (
            'pay_way'     =>  'cash',
            'amount'      =>  '-10',
            'opcode'      =>  '1001',
            'card_amount' =>  -9999,
            'memo'        =>  '大吉大利',
            'ref_id'      =>  '1',
            'auto_commit' =>  '1',
            'operator'    =>  'ztester',
        );

        $client->request('POST', '/api/user/7/order', $parameters);

        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-card-poper');

        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-card-sync');

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150030011, $output['code']);
        $this->assertEquals('Not enough card balance', $output['msg']);

        // 確認現金沒有誤扣
        $afterCash = $em->find('BB\DurianBundle\Entity\Cash', $cashId);
        $this->assertEquals($originBalance, $afterCash->getBalance());

        $afterEntries = $emEntry->getRepository('BBDurianBundle:CashEntry')->findBy(['cashId' => $cashId]);
        $this->assertEquals($originEntries, count($afterEntries));
    }

    /**
     * 測試下注相關例外
     */
    public function testOrderException()
    {
        $client = $this->createClient();

        //測試參數沒帶payway的例外
        $parameters = [
            'amount' => 'amount',
            'opcode' => '30002',
            'memo' => '大吉大利',
            'ref_id' => '1',
            'auto_commit' => '1'
        ];

        $client->request('POST', '/api/user/7/order', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150140001, $output['code']);
        $this->assertEquals('Plz chose a pay way', $output['msg']);

        //測試amount帶入非數字
        $parameters = [
            'pay_way' => 'cash',
            'amount' => 'amount',
            'opcode' => '30002',
            'memo' => '大吉大利',
            'ref_id' => '1',
            'auto_commit' => '1'
        ];

        $client->request('POST', '/api/user/7/order', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150140010, $output['code']);
        $this->assertEquals('Amount must be numeric', $output['msg']);

        //測試未帶opcode
        $parameters = [
            'pay_way' => 'cash',
            'amount' => 1,
            'memo' => '大吉大利',
            'ref_id' => '1',
            'auto_commit' => '1'
        ];

        $client->request('POST', '/api/user/7/order', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150140019, $output['code']);
        $this->assertEquals('No opcode specified', $output['msg']);

        //測試帶入不合法的opcode
        $parameters = [
            'pay_way' => 'cash',
            'amount' => 1,
            'opcode' => '交易代碼',
            'memo' => '大吉大利',
            'ref_id' => '1',
            'auto_commit' => '1'
        ];

        $client->request('POST', '/api/user/7/order', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150140015, $output['code']);
        $this->assertEquals('Invalid opcode', $output['msg']);

        //測試下注時帶入opcode 1003
        $parameters = [
            'pay_way' => 'cash',
            'amount' => 1,
            'opcode' => 1003,
            'memo' => '大吉大利',
            'ref_id' => '1',
            'auto_commit' => '1'
        ];

        $client->request('POST', '/api/user/7/order', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150140015, $output['code']);
        $this->assertEquals('Invalid opcode', $output['msg']);

        //測試信用額度下注時，帶入不合法的group num
        $parameters = [
            'pay_way' => 'credit',
            'amount' => 10000,
            'opcode' => '40000',
            'credit_group_num' => 'test',
        ];

        $client->request('POST', '/api/user/8/order', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150140013, $output['code']);
        $this->assertEquals('Invalid group number', $output['msg']);

        //測試信用額度下注時，creditGroupNum存在時，creditAt不可為空值
        $parameters = [
            'pay_way' => 'credit',
            'amount' => -10000,
            'opcode' => '40000',
            'credit_group_num' => '1',
            'credit_at' => null
        ];

        $client->request('POST', '/api/user/8/order', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150140023, $output['code']);
        $this->assertEquals('Must send timestamp', $output['msg']);

        //測試下注時，找不到使用者
        $parameters = [
            'pay_way' => 'cash',
            'amount' => 1,
            'opcode' => 1001,
            'memo' => '大吉大利',
            'ref_id' => '1',
            'auto_commit' => '1'
        ];

        $client->request('POST', '/api/user/100/order', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150140028, $output['code']);
        $this->assertEquals('No such user', $output['msg']);

        //測試cash下注時，該使用者非cash
        $parameters = [
            'pay_way' => 'cash',
            'amount' => 1,
            'opcode' => 1001,
            'memo' => '大吉大利',
            'ref_id' => '1',
            'auto_commit' => '1'
        ];

        $client->request('POST', '/api/user/10/order', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150140025, $output['code']);
        $this->assertEquals('No cash found', $output['msg']);

        //測試cashFake下注時，該使用者非cashFake
        $parameters = [
            'pay_way' => 'cashfake',
            'amount' => 1,
            'opcode' => 1001,
            'memo' => '大吉大利',
            'ref_id' => '1',
            'auto_commit' => '1'
        ];

        $client->request('POST', '/api/user/6/order', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150140024, $output['code']);
        $this->assertEquals('No cashFake found', $output['msg']);
    }

    /**
     * 測試多重下注
     */
    public function testMutliOrder()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BB\DurianBundle\Entity\User', 7);
        $cashfake = $user->getCashFake();

        $now = new \DateTime('now');
        $now = $now->format(\DateTime::ISO8601);

        // 啟用租卡
        $card = $em->find('BB\DurianBundle\Entity\User', 6)
                   ->getCard();
        $card->enable();

        $entry = $card->addEntry(9901, 'ytester', 1000); // TRADE_IN
        $entry->setId(4);
        $em->persist($entry);

        $em->flush();
        $em->clear();

        $memo = '';
        for ($i = 0; $i < 100; $i++) {
            $memo .= 'a';
        }

        $parameters['orders'][] = array (
            'user_id'       => 8,
            'pay_way'       =>  'credit',
            'amount'        =>  -200,
            'opcode'        =>  '1001',
            'credit_group_num'     =>  '3',
            'sharelimit_group_num'     =>  '1',
            'card_amount'   =>  -1,
            'ref_id'        =>  '1',
            'memo'          =>  $memo . '012',
            'at'            =>  $now,
            'credit_at'     =>  $now,
            'auto_commit'   =>  '1'
        );

        $parameters['orders'][] = array (
            'user_id'       => 8,
            'pay_way'       =>  'credit',
            'amount'        =>  -200,
            'opcode'        =>  '1001',
            'credit_group_num'     =>  '1',
            'sharelimit_group_num'     =>  '12',
            'card_amount'   =>  -1,
            'memo'          =>  $memo . '012',
            'ref_id'        =>  '1',
            'at'            =>  $now,
            'credit_at'     =>  $now,
            'auto_commit'   =>  '1'
        );

        $parameters['orders'][] = array (
            'user_id'       => 7,
            'pay_way'       =>  'cashfake',
            'amount'        =>  -10,
            'opcode'        =>  '1001',
            'card_amount'   =>  -1,
            'sharelimit_group_num'     =>  '1',
            'memo'          =>  $memo . '012',
            'ref_id'        =>  '1',
            'operator'      =>  'ztester',
            'auto_commit'   =>  '1',
            'at'            =>  $now,
        );

        $parameters['orders'][] = array (
            'user_id'       => 7,
            'pay_way'       =>  'cash',
            'amount'        =>  -10,
            'opcode'        =>  '1001',
            'sharelimit_group_num'     =>  '1',
            'card_amount'   =>  -1,
            'memo'          =>  $memo . '012',
            'ref_id'        =>  '1',
            'operator'      =>  'ztester',
            'auto_commit'   =>  '1',
            'at'            =>  $now
        );

        $parameters['orders'][] = array (
            'pay_way'       =>  'cash',
            'amount'        =>  -10,
            'opcode'        =>  '1001',
            'sharelimit_group_num'     =>  '1',
            'card_amount'   =>  -1,
            'memo'          =>  $memo . '012',
            'ref_id'        =>  '1',
            'auto_commit'   =>  '1',
            'at'            =>  $now
        );

        $parameters['orders'][] = array (
            'user_id'       =>  7,
            'pay_way'       =>  'cash',
            'amount'        =>  10,
            'opcode'        =>  '10001',
            'memo'          =>  $memo . '012',
            'ref_id'        =>  '1',
            'auto_commit'   =>  '1'
        );

        // auto_commit預設為自動交易
        $parameters['orders'][] = [
            'user_id' => 8,
            'amount' => 10,
            'opcode' => '1001',
            'memo' => $memo . '012',
            'ref_id' => '1',
            'operator' => 'ztester',
            'at' => $now
        ];

        $client->request('PUT', '/api/orders', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $poper = new Poper();
        $poper->runPop($this->getContainer(), 'cash');

        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        // 跑背景程式讓queue被消化
        $params = [
            '--entry' => true,
            '--balance' => true
        ];
        $this->runCommand('durian:sync-cash-fake', $params);

        $this->assertEquals('error', $output[0]['result']);
        $this->assertEquals(150140026, $output[0]['code']);
        $this->assertEquals('No credit found', $output[0]['msg']);

        $this->assertEquals('error', $output[1]['result']);
        $this->assertEquals(150080028, $output[1]['code']);
        $this->assertEquals('User 8 has no sharelimit of group 12', $output[1]['msg']);

        $cashfake = $em->find('BB\DurianBundle\Entity\CashFake', 1);

        $this->assertEquals('ok', $output[2]['result']);
        $this->assertEquals($cashfake->getId(), $output[2]['ret']['cash_fake']['id']);
        $this->assertEquals('CNY', $output[2]['ret']['cash_fake']['currency']);

        $this->assertEquals($cashfake->getBalance(), $output[2]['ret']['cash_fake']['balance']);
        $this->assertEquals($cashfake->getPreSub(), $output[2]['ret']['cash_fake']['pre_sub']);
        $this->assertEquals($cashfake->getPreAdd(), $output[2]['ret']['cash_fake']['pre_add']);

        $this->assertEquals('1001', $output[2]['ret']['cash_fake_entry'][0]['opcode']);
        $this->assertEquals('-10', $output[2]['ret']['cash_fake_entry'][0]['amount']);
        $this->assertEquals('1', $output[2]['ret']['cash_fake_entry'][0]['ref_id']);
        $this->assertEquals($memo, $output[2]['ret']['cash_fake_entry'][0]['memo']);
        $this->assertEquals(2, $output[2]['ret']['cash_fake_entry'][0]['cash_fake_version']);
        $this->assertEquals('ztester', $output[2]['ret']['cash_fake_entry'][0]['operator']['username']);

        $this->assertEquals(5, $output[2]['ret']['card']['id']);
        $this->assertEquals(6, $output[2]['ret']['card']['user_id']);
        $this->assertEquals(1, $output[2]['ret']['card']['enable']);
        $this->assertEquals(999, $output[2]['ret']['card']['balance']);
        $this->assertEquals(5, $output[2]['ret']['card_entry']['card_id']);
        $this->assertEquals(6, $output[2]['ret']['card_entry']['user_id']);
        $this->assertEquals(-1, $output[2]['ret']['card_entry']['amount']);
        $this->assertEquals(999, $output[2]['ret']['card_entry']['balance']);
        $this->assertEquals('ztester', $output[2]['ret']['card_entry']['operator']);
        $this->assertEquals(2, $output[2]['ret']['card_entry']['card_version']);

        $this->assertEquals(20, $output[2]['ret']['sharelimit'][0]);
        $this->assertEquals(30, $output[2]['ret']['sharelimit'][1]);
        $this->assertEquals(20, $output[2]['ret']['sharelimit'][2]);
        $this->assertEquals(20, $output[2]['ret']['sharelimit'][3]);
        $this->assertEquals(10, $output[2]['ret']['sharelimit'][4]);
        $this->assertEquals(0, $output[2]['ret']['sharelimit'][5]);
        $this->assertEquals(0, $output[2]['ret']['sharelimit'][6]);

        $this->assertEquals(1001, $output[3]['ret']['cash_entry']['opcode']);
        $this->assertEquals(-10, $output[3]['ret']['cash_entry']['amount']);
        $this->assertEquals(990, $output[3]['ret']['cash_entry']['balance']);
        $this->assertEquals($memo, $output[3]['ret']['cash_entry']['memo']);
        $this->assertEquals(1, $output[3]['ret']['cash_entry']['ref_id']);
        $this->assertEquals('ztester', $output[3]['ret']['cash_entry']['operator']['username']);
        $this->assertEquals(3, $output[3]['ret']['cash_entry']['cash_version']);

        $this->assertEquals(5, $output[3]['ret']['card']['id']);
        $this->assertEquals(6, $output[3]['ret']['card']['user_id']);
        $this->assertEquals(1, $output[3]['ret']['card']['enable']);
        $this->assertEquals(998, $output[3]['ret']['card']['balance']);
        $this->assertEquals(5, $output[3]['ret']['card_entry']['card_id']);
        $this->assertEquals(6, $output[3]['ret']['card_entry']['user_id']);
        $this->assertEquals(-1, $output[3]['ret']['card_entry']['amount']);
        $this->assertEquals(998, $output[3]['ret']['card_entry']['balance']);
        $this->assertEquals('ztester', $output[3]['ret']['card_entry']['operator']);
        $this->assertEquals(3, $output[3]['ret']['card_entry']['card_version']);

        $this->assertEquals('error', $output[4]['result']);
        $this->assertEquals('No user_id specified', $output[4]['msg']);
        $this->assertEquals(150140004, $output[4]['code']);

        $this->assertEquals(10001, $output[5]['ret']['cash_entry']['opcode']);
        $this->assertEquals(10, $output[5]['ret']['cash_entry']['amount']);
        $this->assertEquals(1000, $output[5]['ret']['cash_entry']['balance']);
        $this->assertEquals($memo, $output[5]['ret']['cash_entry']['memo']);
        $this->assertEquals(1, $output[5]['ret']['cash_entry']['ref_id']);
        $this->assertEquals(4, $output[5]['ret']['cash_entry']['cash_version']);
    }

    /**
     * 測試多重下注時, 可以強制扣款
     */
    public function testMutliOrderWithForce()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BBDurianBundle:User', 6);
        $user->getCard()->enable();

        $user = $em->find('BBDurianBundle:User', 5);
        $user->setBankrupt(true);
        $user = $em->find('BBDurianBundle:User', 6);
        $user->setBankrupt(true);
        $user = $em->find('BBDurianBundle:User', 7);
        $user->setBankrupt(true);
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setBankrupt(true);
        $user->getCredit(1)->disable();

        $em->flush();
        $em->clear();

        // 現金, auto_commit = 1
        $parameters['orders'][] = [
            'user_id' => 6,
            'pay_way' => 'cash',
            'amount' => -2000,
            'opcode' => '1001',
            'auto_commit' => '1',
            'force' => true,
            'card_amount' => -1000,
        ];

        // 現金, auto_commit = 0
        $parameters['orders'][] = [
            'user_id' => 5,
            'pay_way' => 'cash',
            'amount' => -2000,
            'opcode' => '1001',
            'auto_commit' => '0',
            'force' => true
        ];

        // 快開額度, auto_commit = 1
        $parameters['orders'][] = [
            'user_id' => 7,
            'pay_way' => 'cashfake',
            'amount' => -600,
            'opcode' => '1001',
            'auto_commit' => '1',
            'force' => true
        ];

        // 快開額度, auto_commit = 0
        $parameters['orders'][] = [
            'user_id' => 8,
            'pay_way' => 'cash_fake',
            'amount' => -600,
            'opcode' => '1001',
            'auto_commit' => '0',
            'force' => true
        ];

        // 信用額度, auto_commit = 1
        $now = new \DateTime('now');
        $morn = clone $now;
        $morn->add(new \DateInterval('P1D'));
        $parameters['orders'][] = [
            'user_id' => 8,
            'pay_way' => 'credit',
            'amount' => -10000,
            'opcode' => '40000',
            'credit_group_num' => '1',
            'credit_at' => $morn->format(\DateTime::ISO8601),
            'auto_commit' => '1',
            'force' => true
        ];

        $client->request('PUT', '/api/orders', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output[0]['result']);
        $this->assertEquals(5, $output[0]['ret']['cash']['id']);
        $this->assertEquals(6, $output[0]['ret']['cash']['user_id']);
        $this->assertEquals(-1000, $output[0]['ret']['cash']['balance']);
        $this->assertEquals(1001, $output[0]['ret']['cash_entry']['opcode']);
        $this->assertEquals(-2000, $output[0]['ret']['cash_entry']['amount']);
        $this->assertEquals(-1000, $output[0]['ret']['cash_entry']['balance']);
        $this->assertEquals(3, $output[0]['ret']['cash_entry']['cash_version']);

        $this->assertEquals(6, $output[0]['ret']['card']['user_id']);
        $this->assertEquals(-1000, $output[0]['ret']['card']['balance']);
        $this->assertEquals(6, $output[0]['ret']['card_entry']['user_id']);
        $this->assertEquals(-1000, $output[0]['ret']['card_entry']['amount']);
        $this->assertEquals(-1000, $output[0]['ret']['card_entry']['balance']);
        $this->assertEquals(1001, $output[0]['ret']['card_entry']['opcode']);
        $this->assertEquals(2, $output[0]['ret']['card_entry']['card_version']);

        $this->assertEquals('ok', $output[1]['result']);
        $this->assertEquals(4, $output[1]['ret']['cash']['id']);
        $this->assertEquals(5, $output[1]['ret']['cash']['user_id']);
        $this->assertEquals(1000, $output[1]['ret']['cash']['balance']);
        $this->assertEquals(2000, $output[1]['ret']['cash']['pre_sub']);
        $this->assertEquals(1001, $output[1]['ret']['cash_entry']['opcode']);
        $this->assertEquals(-2000, $output[1]['ret']['cash_entry']['amount']);

        $this->assertEquals('ok', $output[2]['result']);
        $this->assertEquals(1, $output[2]['ret']['cash_fake']['id']);
        $this->assertEquals(7, $output[2]['ret']['cash_fake']['user_id']);
        $this->assertEquals(-100, $output[2]['ret']['cash_fake']['balance']);
        $this->assertEquals(1001, $output[2]['ret']['cash_fake_entry'][0]['opcode']);
        $this->assertEquals(-600, $output[2]['ret']['cash_fake_entry'][0]['amount']);
        $this->assertEquals(-100, $output[2]['ret']['cash_fake_entry'][0]['balance']);
        $this->assertEquals(2, $output[2]['ret']['cash_fake_entry'][0]['cash_fake_version']);

        $this->assertEquals('ok', $output[3]['result']);
        $this->assertEquals(2, $output[3]['ret']['cash_fake']['id']);
        $this->assertEquals(8, $output[3]['ret']['cash_fake']['user_id']);
        $this->assertEquals(500, $output[3]['ret']['cash_fake']['balance']);
        $this->assertEquals(600, $output[3]['ret']['cash_fake']['pre_sub']);
        $this->assertEquals(1001, $output[3]['ret']['cash_fake_entry'][0]['opcode']);
        $this->assertEquals(-600, $output[3]['ret']['cash_fake_entry'][0]['amount']);

        $this->assertEquals('ok', $output[4]['result']);
        $this->assertEquals(8, $output[4]['ret']['credit']['user_id']);
        $this->assertEquals(-5000, $output[4]['ret']['credit']['balance']);
    }

    /**
     * 測試以Order派彩
     */
    public function testOrderByCashAndPayOff()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BB\DurianBundle\Entity\User', 7);
        $cash = $user->getCash();

        $this->assertEquals(1000, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());

        $parameters = array (
            'pay_way'       =>  'cash',
            'amount'        =>  '10',
            'opcode'        =>  '30002',
            'memo'          =>  '大吉大利',
            'ref_id'        =>  '1',
            'auto_commit'   =>  '1',
        );

        $client->request('POST', '/api/user/7/order', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(30002, $output['ret']['cash_entry']['opcode']);
        $this->assertEquals(10, $output['ret']['cash_entry']['amount']);
        $this->assertEquals(1010, $output['ret']['cash_entry']['balance']);
        $this->assertEquals('大吉大利', $output['ret']['cash_entry']['memo']);
        $this->assertEquals(1, $output['ret']['cash_entry']['ref_id']);
    }

    /**
     * 測試以Order派彩但card_amount參數送null
     */
    public function testOrderByCashAndPayOffButCardAmountSentNull()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BB\DurianBundle\Entity\User', 7);
        $cash = $user->getCash();

        $card = $user->getCard();
        $card->enable();

        $em->flush();

        $this->assertEquals(1000, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());

        $parameters = array (
            'card_amount'   =>  null,
            'pay_way'       =>  'cash',
            'amount'        =>  '10',
            'opcode'        =>  '30002',
            'memo'          =>  '大吉大利',
            'ref_id'        =>  '1',
            'auto_commit'   =>  '1',
        );

        $client->request('POST', '/api/user/8/order', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(30002, $output['ret']['cash_entry']['opcode']);
        $this->assertEquals(10, $output['ret']['cash_entry']['amount']);
        $this->assertEquals(1010, $output['ret']['cash_entry']['balance']);
        $this->assertEquals('大吉大利', $output['ret']['cash_entry']['memo']);
        $this->assertEquals(1, $output['ret']['cash_entry']['ref_id']);
    }

    /**
     * 測試租卡噴錯時redis不會將注單寫入DB
     */
    public function testOrderWillMakeEntryWhenCardError()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $client = $this->createClient();

        $entry = $emEntry->getRepository('BBDurianBundle:CashEntry')->findOneBy(['id' => 11]);

        $this->assertNull($entry);

        $user = $em->find('BB\DurianBundle\Entity\User', 7);
        $cash = $user->getCash();

        $card = $user->getCard();
        $card->enable();

        $em->flush();

        $this->assertEquals(1000, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());

        $parameters = array (
            'card_amount'   => 'h3h3h3',
            'pay_way'       => 'cash',
            'amount'        => '10',
            'opcode'        => '30002',
            'memo'          => '大吉大利',
            'ref_id'        => '1',
            'auto_commit'   => '1',
        );

        $client->request('POST', '/api/user/8/order', $parameters);

        // 跑背景程式讓queue被消化
        $cashPoper = new Poper();
        $cashPoper->runPop($this->getContainer(), 'cash');

        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);

        // 單並未寫入
        $entry = $emEntry->getRepository('BBDurianBundle:CashEntry')->findOneBy(['id' => 11]);
        $this->assertNull($entry);
    }

    /**
     * 測試以現金下注但帶入租卡點數為浮點數
     */
    public function testDoOrderByCashButCardAmountIsFloat()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $now = new \DateTime('now');
        $now = $now->format(\DateTime::ISO8601);

        $user = $em->find('BB\DurianBundle\Entity\User', 7);
        $cash = $user->getCash();
        $card = $user->getCard();
        $card->enable();

        $this->assertEquals(1000, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());

        $em->flush();
        $em->clear();

        $parameters = array (
            'pay_way'       =>  'cash',
            'amount'        =>  '-10',
            'opcode'        =>  '1001',
            'sharelimit_group_num'     =>  '1',
            'card_amount'   =>  -1.5,
            'memo'          =>  '大吉大利',
            'ref_id'        =>  '1',
            'auto_commit'   =>  '1',
            'at'            =>  $now

        );

        $client->request('POST', '/api/user/7/order', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Card amount must be an integer', $output['msg']);
        $this->assertEquals(150140011, $output['code']);

        $em->clear();

        $parameters = array (
            'pay_way'       =>  'cash',
            'amount'        =>  '-10',
            'opcode'        =>  '1001',
            'sharelimit_group_num'     =>  '1',
            'card_amount'   =>  1.00,
            'memo'          =>  '大吉大利',
            'ref_id'        =>  '1',
            'auto_commit'   =>  '1',
            'at'            =>  $now

        );

        $client->request('POST', '/api/user/7/order', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($card->getId(), $output['ret']['card']['id']);
        $this->assertEquals($user->getId(), $output['ret']['card']['user_id']);
        $this->assertEquals($card->getId(), $output['ret']['card_entry']['card_id']);
        $this->assertEquals($user->getId(), $output['ret']['card_entry']['user_id']);
        $this->assertEquals(1, $output['ret']['card_entry']['amount']);
        $this->assertEquals('ztester', $output['ret']['card_entry']['operator']);
    }

    /**
     * 測試以現金下注但帶入租卡餘額不足
     */
    public function testDoOrderByCashButCardBalanceNotEnougth()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $now = new \DateTime('now');
        $now = $now->format(\DateTime::ISO8601);

        $user = $em->find('BB\DurianBundle\Entity\User', 7);
        $cash = $user->getCash();
        $card = $user->getCard();
        $card->enable();

        $this->assertEquals(1000, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());

        $em->flush();
        $em->clear();

        $parameters = array (
            'pay_way'       =>  'cash',
            'amount'        =>  '-10',
            'opcode'        =>  '1001',
            'sharelimit_group_num'     =>  '1',
            'card_amount'   =>  -100,
            'memo'          =>  '大吉大利',
            'ref_id'        =>  '1',
            'auto_commit'   =>  '1',
            'at'            =>  $now

        );

        $client->request('POST', '/api/user/7/order', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Not enough card balance', $output['msg']);
        $this->assertEquals(150030011, $output['code']);
    }

    /**
     * 測試帶入租卡點數但走交易機制，應噴例外
     */
    public function testOrderWithTransactionAndCardAmount()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $now = new \DateTime('now');
        $now = $now->format(\DateTime::ISO8601);

        $user = $em->find('BB\DurianBundle\Entity\User', 7);
        $cash = $user->getCash();
        $card = $user->getCard();
        $card->enable();

        $this->assertEquals(1000, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());

        $em->flush();
        $em->clear();

        $parameters = array (
            'pay_way'       =>  'cash',
            'amount'        =>  '-10',
            'opcode'        =>  '1001',
            'sharelimit_group_num'     =>  '1',
            'card_amount'   =>  -1.5,
            'memo'          =>  '是該噴個例外',
            'ref_id'        =>  '1',
            'auto_commit'   =>  0,
            'at'            =>  $now

        );

        $client->request('POST', '/api/user/7/order', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Cannot do card operation while order by transaction(auto_commit=0)', $output['msg']);
        $this->assertEquals(150140006, $output['code']);
    }

    /**
     * 測試下注時帶入的租卡金額超出最大許可整數
     */
    public function testOrderWithCardAmountExceedMaxAllowedInteger()
    {
        $client = $this->createClient();

        $parameters = [
            'pay_way'     => 'cash',
            'amount'      => 10,
            'opcode'      => 1001,
            'card_amount' => 10000000000000000
        ];

        $client->request('POST', '/api/user/7/order', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Oversize amount given which exceeds the MAX', $output['msg']);
        $this->assertEquals(150140033, $output['code']);
    }

    /**
     * 測試多重下注時帶入的租卡金額超出最大許可整數
     */
    public function testMultiOrderWithCardAmountExceedMaxAllowedInteger()
    {
        $client = $this->createClient();

        $parameters['orders'][] = [
            'user_id'     => 7,
            'pay_way'     => 'cash',
            'amount'      => 10,
            'opcode'      => 1001,
            'card_amount' => 10000000000000000
        ];

        $client->request('PUT', '/api/orders', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output[0]['result']);
        $this->assertEquals('Oversize amount given which exceeds the MAX', $output[0]['msg']);
        $this->assertEquals(150140033, $output[0]['code']);
    }

    /**
     * 測試現金批次下注時未帶注單參數
     */
    public function testBunchOrderByCashWithoutOd()
    {
        $client = $this->createClient();

        $parameters = [
            'pay_way'  => 'cash',
            'opcode'   => 1001,
            'od_count' => 0
        ];

        $client->request('PUT', '/api/user/7/multi_order_bunch', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Invalid order', $output['msg']);
        $this->assertEquals(150140034, $output['code']);
    }

    /**
     * 測試批次下注時帶入的租卡金額超出最大許可整數
     */
    public function testBunchOrderWithCardAmountExceedMaxAllowedInteger()
    {
        $client = $this->createClient();

        $odParameters = [
            [
                'am'   => 10,
                'card' => 10000000000000000
            ]
        ];

        $parameters = [
            'pay_way'  => 'cash',
            'opcode'   => 1001,
            'od_count' => count($odParameters),
            'od'       => $odParameters
        ];

        $client->request('PUT', '/api/user/7/multi_order_bunch', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Oversize amount given which exceeds the MAX', $output['msg']);
        $this->assertEquals(150140033, $output['code']);
    }

    /**
     * 測試批次下注相關exception
     */
    public function testOrderBunchException()
    {
        $client = $this->createClient();

        //測試假現金批次下注，但找不到此假現金
        $odParameters = [
            [
                'am' => -50,
                'card' => -1,
                'ref' => '15569985',
                'memo' => 'test bunch order data 1'
            ]
        ];

        $parameters = [
            'pay_way' => 'cashfake',
            'opcode' => '1001',
            'od_count' => count($odParameters),
            'od' => $odParameters,
        ];

        $client->request('PUT', '/api/user/5/multi_order_bunch', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('No cashFake found', $output['msg']);
        $this->assertEquals(150140024, $output['code']);

        //測試現金批次下注，但找不到此現金
        $odParameters = [
            [
                'am' => -100,
                'card' => -1,
                'ref' => '166569984',
                'memo' => 'test bunch order data 1'
            ]
        ];
        $parameters = [
            'pay_way' => 'cash',
            'opcode' => '30001',
            'od_count' => count($odParameters),
            'od' => $odParameters,
        ];

        $client->request('PUT', '/api/user/10/multi_order_bunch', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('No cash found', $output['msg']);
        $this->assertEquals(150140025, $output['code']);
    }

    /**
     * 測試以信用額度批次下注(有錯則全部回溯並且中斷程式)
     */
    public function testDoOrderBunchByCredit()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BB\DurianBundle\Entity\User', 8);

        $card = $em->find('BB\DurianBundle\Entity\Card', 7);
        $card->enable();
        $cardEntry = $card->addEntry('9901', $user->getUsername(), 200);
        $cardEntry->setId(4);
        $em->persist($cardEntry);

        $em->flush();
        $em->clear();
        $now = new \DateTime('now');

        $memo = '';
        for ($i = 0; $i < 100; $i++) {
            $memo .= 'a';
        }

        $odParameters = [
            [
                'am'     => -200,
                'card'   => -1,
                'ref'    => '15569985',
                'memo'   => 'test order data 1'
            ],
            [
                'am'     => -200,
                'card'   => -1,
                'ref'    => '15569986',
                'memo'   => 'test order data 2'
            ],
            [
                'am'     => -200,
                'card'   => -1,
                'ref'    => '15569987',
                'memo'   => $memo . '012'
            ]
        ];

        $parameters = array (
            'pay_way'              => 'credit',
            'opcode'               => '1001',
            'credit_group_num'     => '1',
            'od_count'             => count($odParameters),
            'at'                   => $now->format(\DateTime::ISO8601),
            'operator'             => 'thorblack',
            'od'                   => $odParameters,
        );

        $client->request('PUT', '/api/user/8/multi_order_bunch', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->runCommand('durian:run-card-poper');
        $this->runCommand('durian:run-card-sync');

        $cmdParams = [
            '--entry' => 1,
            '--credit' => 1,
            '--period' => 1
        ];
        $this->runCommand('durian:sync-credit', $cmdParams);

        $credit = $em->find('BB\DurianBundle\Entity\Credit', 5);
        $card = $em->find('BB\DurianBundle\Entity\Card', 7);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($credit->getId(), $output['ret']['credit']['id']);
        $this->assertEquals(4400, $output['ret']['credit']['balance']);
        $this->assertEquals($credit->getGroupNum(), $output['ret']['credit']['group']);
        $this->assertEquals($parameters['od_count'], count($output['ret']['card_entry']));

        $creditEntry = $em->find('BBDurianBundle:CreditEntry', 3);
        $this->assertEquals($memo, $creditEntry->getMemo());

        $this->assertEquals($card->getId(), $output['ret']['card']['id']);
        $this->assertEquals($card->getUser()->getId(), $output['ret']['card']['user_id']);
        $this->assertEquals($card->getBalance(), $output['ret']['card']['balance']);
        $this->assertEquals(197, $output['ret']['card']['balance']);
        $this->assertEquals($card->isEnabled(), $output['ret']['card']['enable']);

        $cardEntry = $em->find('BB\DurianBundle\Entity\CardEntry', $output['ret']['card_entry'][0]['id']);
        $this->assertEquals($cardEntry->getId(), $output['ret']['card_entry'][0]['id']);
        $this->assertEquals(1001, $cardEntry->getId());
        $this->assertEquals($cardEntry->getOpcode(), $output['ret']['card_entry'][0]['opcode']);
        $this->assertEquals(1001, $cardEntry->getOpcode());
        $this->assertEquals($cardEntry->getAmount(), $output['ret']['card_entry'][0]['amount']);
        $this->assertEquals($cardEntry->getRefId(), $output['ret']['card_entry'][0]['ref_id']);
        $this->assertEquals($cardEntry->getBalance(), $output['ret']['card_entry'][0]['balance']);
        $this->assertEquals('tester', $output['ret']['card_entry'][0]['operator']);
        $this->assertEquals($cardEntry->getCardVersion(), $output['ret']['card_entry'][0]['card_version']);

        $cardEntry = $em->find('BB\DurianBundle\Entity\CardEntry', $output['ret']['card_entry'][1]['id']);
        $this->assertEquals($cardEntry->getId(), $output['ret']['card_entry'][1]['id']);
        $this->assertEquals(1002, $cardEntry->getId());
        $this->assertEquals($cardEntry->getOpcode(), $output['ret']['card_entry'][1]['opcode']);
        $this->assertEquals(1001, $cardEntry->getOpcode());
        $this->assertEquals($cardEntry->getAmount(), $output['ret']['card_entry'][1]['amount']);
        $this->assertEquals($cardEntry->getRefId(), $output['ret']['card_entry'][1]['ref_id']);
        $this->assertEquals($cardEntry->getBalance(), $output['ret']['card_entry'][1]['balance']);
        $this->assertEquals('tester', $output['ret']['card_entry'][1]['operator']);
        $this->assertEquals($cardEntry->getCardVersion(), $output['ret']['card_entry'][1]['card_version']);
    }

    /**
     * 測試以信用額度批次下注，使用者慣用幣別為台幣，交易及回傳皆為台幣幣值
     */
    public function testDoOrderBunchByCreditAndCurrencyIsTWD()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BBDurianBundle:User', 8);
        $user->setCurrency(901); // TWD
        $em->flush();

        $now = new \DateTime('now');

        // 先檢查目前的金額
        $parameters = ['at' => $now->format(\DateTime::ISO8601)];
        $client->request('GET', '/api/user/8/credit/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(22421, $output['ret']['line']);
        $this->assertEquals(22421.52, $output['ret']['balance']);

        $odParameters = [
            ['am' => -5000],
            ['am' => -6000]
        ];

        // 執行
        $parameters = [
            'pay_way'          => 'credit',
            'opcode'           => '40000',
            'credit_group_num' => '1',
            'od_count'         => count($odParameters),
            'at'               => $now->format(\DateTime::ISO8601),
            'od'               => $odParameters,
        ];

        $client->request('PUT', '/api/user/8/multi_order_bunch', $parameters);

        $cmdParams = [
            '--entry' => 1,
            '--credit' => 1,
            '--period' => 1
        ];
        $this->runCommand('durian:sync-credit', $cmdParams);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['credit']['user_id']);
        $this->assertEquals(1, $output['ret']['credit']['group']);
        $this->assertEquals(22421, $output['ret']['credit']['line']);
        $this->assertEquals(11421.52, $output['ret']['credit']['balance']);

        $entry = $em->find('BBDurianBundle:CreditEntry', 1)->toArray();
        $this->assertEquals(8, $entry['user_id']);
        $this->assertEquals(1, $entry['group']);
        $this->assertEquals(40000, $entry['opcode']);
        $this->assertEquals(-1115, $entry['amount']);
        $this->assertEquals(3885, $entry['balance']);
        $this->assertEquals(5000, $entry['line']);

        $entry = $em->find('BBDurianBundle:CreditEntry', 2)->toArray();
        $this->assertEquals(8, $entry['user_id']);
        $this->assertEquals(1, $entry['group']);
        $this->assertEquals(40000, $entry['opcode']);
        $this->assertEquals(-1338, $entry['amount']);
        $this->assertEquals(2547, $entry['balance']);
        $this->assertEquals(5000, $entry['line']);
    }

    /**
     * 測試以信用額度批次下注時, 可以強制扣款
     */
    public function testDoOrderBunceCreditWithForce()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BBDurianBundle:User', 8);
        $user->getCredit(1)->disable();
        $em->flush();

        $now = new \DateTime('now');

        $odParameters = [
            ['am' => -5000],
            ['am' => -5000]
        ];

        // 測試下注時, 可允許在使用者停權, 以及額度扣到負數的情況下強制扣款
        $parameters = [
            'pay_way' => 'credit',
            'opcode' => '40000',
            'force' => true,
            'credit_group_num' => '1',
            'od_count' => count($odParameters),
            'at'  => $now->format(\DateTime::ISO8601),
            'od' => $odParameters,
        ];

        $client->request('PUT', '/api/user/8/multi_order_bunch', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['credit']['user_id']);
        $this->assertEquals(1, $output['ret']['credit']['group']);
        $this->assertEquals(5000, $output['ret']['credit']['line']);
        $this->assertEquals(-5000, $output['ret']['credit']['balance']);
    }

    /**
     * 測試信用額度批次下注時信用額度餘額不足(有錯則全部回溯並且中斷程式)
     * 並檢查租卡及信用額度的餘額為扣款前
     */
    public function testDoOrderBunchByCreditButNotEnoughBalance()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redisWallet = $this->getContainer()->get('snc_redis.wallet4');
        $client = $this->createClient();
        $user = $em->find('BB\DurianBundle\Entity\User', 8);
        $user->setCurrency(156); // CNY

        $card = $em->find('BB\DurianBundle\Entity\Card', 7);
        $card->enable();
        $cardEntry = $card->addEntry('9901', $user->getUsername(), 200);
        $cardEntry->setId(4);
        $em->persist($cardEntry);

        $em->flush();
        $em->clear();
        $now = new \DateTime('now');

        $odParameters = [
            [
                'am'     => -2000,
                'card'   => -1,
                'ref'    => '155699875',
                'memo'   => 'blow it'
            ],
            [
                'am'     => -2000,
                'card'   => -1,
                'ref'    => '155699876',
                'memo'   => 'blow it'
            ],
            [
                'am'     => -2000,
                'card'   => -1,
                'ref'    => '155699877',
                'memo'   => 'blow it'
            ]
        ];

        $parameters = array (
            'pay_way'              => 'credit',
            'opcode'               => '1001',
            'credit_group_num'     => '1',
            'od_count'             => count($odParameters),
            'at'                   => $now->format(\DateTime::ISO8601),
            'operator'             => 'thorblack',
            'od'                   => $odParameters,
        );

        $client->request('PUT', '/api/user/8/multi_order_bunch', $parameters);

        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-card-poper');

        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-card-sync');

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $credit = $em->find('BB\DurianBundle\Entity\Credit', 5);
        $card = $em->find('BB\DurianBundle\Entity\Card', 7);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150060034, $output['code']);
        $this->assertEquals('Not enough balance', $output['msg']);
        $this->assertEquals(200, $card->getBalance());
        $this->assertEquals(5000, $credit->getBalance());
    }

    /**
     * 測試以快開額度批次下注(有錯則全部回溯並且中斷程式)
     */
    public function testDoOrderBunchByCashfake()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BB\DurianBundle\Entity\User', 7);

        $card = $em->find('BB\DurianBundle\Entity\Card', 6);
        $card->enable();
        $cardEntry = $card->addEntry('9901', $user->getUsername(), 200);
        $cardEntry->setId(4);
        $em->persist($cardEntry);

        $em->flush();
        $em->clear();

        $memo = '';
        for ($i = 0; $i < 100; $i++) {
            $memo .= 'a';
        }

        $odParameters = [
            [
                'am'     => -50,
                'card'   => -1,
                'ref'    => '15569985',
                'memo'   => 'test bunch order data 1'
            ],
            [
                'am'     => -50,
                'card'   => -1,
                'ref'    => '15569986',
                'memo'   => 'test bunch order data 2'
            ],
            [
                'am'     => -50,
                'card'   => -1,
                'ref'    => '15569987',
                'memo'   => $memo . '012'
            ]
        ];

        $parameters = array (
            'pay_way'              => 'cashfake',
            'opcode'               => '1001',
            'od_count'             => count($odParameters),
            'operator'             => 'thorblack',
            'od'                   => $odParameters,
        );

        $client->request('PUT', '/api/user/7/multi_order_bunch', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $at = (new \DateTime($output['ret']['cash_fake_entry'][0]['created_at']))->format('YmdHis');

        $this->runCommand('durian:run-card-poper');
        $this->runCommand('durian:run-card-sync');

        // 跑背景程式讓queue被消化
        $params = [
            '--entry' => true,
            '--balance' => true
        ];
        $this->runCommand('durian:sync-cash-fake', $params);

        $card = $em->find('BB\DurianBundle\Entity\Card', 6);
        $cashfake = $em->find('BBDurianBundle:CashFake', 1);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(1, $output['ret']['cash_fake']['id']);
        $this->assertEquals(350, $output['ret']['cash_fake']['balance']);
        $this->assertEquals(0, $output['ret']['cash_fake']['pre_sub']);
        $this->assertEquals(0, $output['ret']['cash_fake']['pre_add']);
        $this->assertEquals($parameters['od_count'], count($output['ret']['cash_fake_entry']));
        $this->assertEquals(4, $cashfake->getVersion());
        $this->assertEquals($at, $cashfake->getLastEntryAt());


        $cashfakeEntry = $em->getRepository('BBDurianBundle:CashFakeEntry')
            ->findOneBy(['id' => $output['ret']['cash_fake_entry'][0]['id']]);
        $this->assertEquals(7, $output['ret']['cash_fake_entry'][0]['user_id']);
        $this->assertEquals('CNY', $output['ret']['cash_fake_entry'][0]['currency']);
        $this->assertEquals(15569985, $output['ret']['cash_fake_entry'][0]['ref_id']);
        $this->assertEquals(450, $output['ret']['cash_fake_entry'][0]['balance']);
        $this->assertEquals(-50, $output['ret']['cash_fake_entry'][0]['amount']);
        $this->assertEquals(2, $cashfakeEntry->getCashFakeVersion());
        $this->assertEquals(2, $output['ret']['cash_fake_entry'][0]['cash_fake_version']);
        $cashfakeEntry = $em->getRepository('BBDurianBundle:CashFakeEntry')
            ->findOneBy(['id' => $output['ret']['cash_fake_entry'][1]['id']]);
        $this->assertEquals(7, $output['ret']['cash_fake_entry'][1]['user_id']);
        $this->assertEquals('CNY', $output['ret']['cash_fake_entry'][1]['currency']);
        $this->assertEquals(15569986, $output['ret']['cash_fake_entry'][1]['ref_id']);
        $this->assertEquals(400, $output['ret']['cash_fake_entry'][1]['balance']);
        $this->assertEquals(-50, $output['ret']['cash_fake_entry'][1]['amount']);
        $this->assertEquals(3, $cashfakeEntry->getCashFakeVersion());
        $this->assertEquals(3, $output['ret']['cash_fake_entry'][1]['cash_fake_version']);
        $cashfakeEntry = $em->getRepository('BBDurianBundle:CashFakeEntry')
            ->findOneBy(['id' => $output['ret']['cash_fake_entry'][2]['id']]);
        $this->assertEquals(7, $output['ret']['cash_fake_entry'][2]['user_id']);
        $this->assertEquals('CNY', $output['ret']['cash_fake_entry'][2]['currency']);
        $this->assertEquals($memo, $output['ret']['cash_fake_entry'][2]['memo']);
        $this->assertEquals(15569987, $output['ret']['cash_fake_entry'][2]['ref_id']);
        $this->assertEquals(350, $output['ret']['cash_fake_entry'][2]['balance']);
        $this->assertEquals(-50, $output['ret']['cash_fake_entry'][2]['amount']);
        $this->assertEquals(4, $cashfakeEntry->getCashFakeVersion());
        $this->assertEquals(4, $output['ret']['cash_fake_entry'][2]['cash_fake_version']);
        $this->assertEquals($memo, $cashfakeEntry->getMemo());

        $this->assertEquals($card->getId(), $output['ret']['card']['id']);
        $this->assertEquals($card->getUser()->getId(), $output['ret']['card']['user_id']);
        $this->assertEquals($card->getBalance(), $output['ret']['card']['balance']);
        $this->assertEquals(197, $output['ret']['card']['balance']);
        $this->assertEquals($card->isEnabled(), $output['ret']['card']['enable']);
        $this->assertEquals(4, $card->getVersion());

        $cardEntry = $em->find('BB\DurianBundle\Entity\CardEntry', $output['ret']['card_entry'][0]['id']);
        $this->assertEquals($cardEntry->getId(), $output['ret']['card_entry'][0]['id']);
        $this->assertEquals(1001, $cardEntry->getId());
        $this->assertEquals($cardEntry->getOpcode(), $output['ret']['card_entry'][0]['opcode']);
        $this->assertEquals($cardEntry->getAmount(), $output['ret']['card_entry'][0]['amount']);
        $this->assertEquals($cardEntry->getRefId(), $output['ret']['card_entry'][0]['ref_id']);
        $this->assertEquals('ztester', $output['ret']['card_entry'][0]['operator']);
        $this->assertEquals(2, $cardEntry->getCardVersion());
        $this->assertEquals($cardEntry->getCardVersion(), $output['ret']['card_entry'][0]['card_version']);

        $cardEntry = $em->find('BB\DurianBundle\Entity\CardEntry', $output['ret']['card_entry'][1]['id']);
        $this->assertEquals($cardEntry->getId(), $output['ret']['card_entry'][1]['id']);
        $this->assertEquals(1002, $cardEntry->getId());
        $this->assertEquals($cardEntry->getOpcode(), $output['ret']['card_entry'][1]['opcode']);
        $this->assertEquals($cardEntry->getAmount(), $output['ret']['card_entry'][1]['amount']);
        $this->assertEquals($cardEntry->getRefId(), $output['ret']['card_entry'][1]['ref_id']);
        $this->assertEquals('ztester', $output['ret']['card_entry'][1]['operator']);
        $this->assertEquals(3, $cardEntry->getCardVersion());
        $this->assertEquals($cardEntry->getCardVersion(), $output['ret']['card_entry'][1]['card_version']);

        $cardEntry = $em->find('BB\DurianBundle\Entity\CardEntry', $output['ret']['card_entry'][2]['id']);
        $this->assertEquals($cardEntry->getId(), $output['ret']['card_entry'][2]['id']);
        $this->assertEquals(1003, $cardEntry->getId());
        $this->assertEquals($cardEntry->getOpcode(), $output['ret']['card_entry'][2]['opcode']);
        $this->assertEquals($cardEntry->getAmount(), $output['ret']['card_entry'][2]['amount']);
        $this->assertEquals($cardEntry->getRefId(), $output['ret']['card_entry'][2]['ref_id']);
        $this->assertEquals('ztester', $output['ret']['card_entry'][2]['operator']);
        $this->assertEquals(4, $cardEntry->getCardVersion());
        $this->assertEquals($cardEntry->getCardVersion(), $output['ret']['card_entry'][2]['card_version']);
    }

    /**
     * 測試以快開額度批次下注但快開額度餘額不足(有錯則全部回溯並且中斷程式)，
     * 並檢查租卡及快開額度的餘額為扣款前
     */
    public function testDoOrderBunchByCashfakeButNotEnoughBalance()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redisWallet = $this->getContainer()->get('snc_redis.wallet3');
        $client = $this->createClient();
        $user = $em->find('BB\DurianBundle\Entity\User', 7);
        $user->setCurrency(156); // CNY

        $card = $em->find('BB\DurianBundle\Entity\Card', 6);
        $card->enable();
        $cardEntry = $card->addEntry('9901', $user->getUsername(), 200);
        $cardEntry->setId(4);
        $em->persist($cardEntry);

        $em->flush();
        $em->clear();

        $odParameters = [
            [
                'am'     => -2000,
                'card'   => -1,
                'ref'    => '155699875',
                'memo'   => 'blow it'
            ],
            [
                'am'     => -2000,
                'card'   => -1,
                'ref'    => '155699876',
                'memo'   => 'blow it'
            ],
            [
                'am'     => -2000,
                'card'   => -1,
                'ref'    => '155699877',
                'memo'   => 'blow it'
            ]
        ];

        $parameters = array (
            'pay_way'              => 'cashfake',
            'opcode'               => '1001',
            'od_count'             => count($odParameters),
            'operator'             => 'thorblack',
            'od'                   => $odParameters,
        );

        $client->request('PUT', '/api/user/7/multi_order_bunch', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150050031, $output['code']);
        $this->assertEquals('Not enough balance', $output['msg']);

        // 確認租卡沒有誤扣
        $afterCard = $em->find('BB\DurianBundle\Entity\Card', 6);
        $cardEntry = $em->find('BB\DurianBundle\Entity\CardEntry', 5);

        $this->assertEquals(200, $afterCard->getBalance());
        $this->assertNull($cardEntry);
    }

    /**
     * 測試以快開額度批次下注時, 可以強制扣款
     */
    public function testDoOrderBunchCashFakeWithForce()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BBDurianBundle:User', 7);
        $user->setBankrupt(true);
        $em->flush();

        $odParameters = [
            ['am' => -100],
            ['am' => -2000]
        ];

        // 測試下注時, 可允許在使用者停權, 以及額度扣到負數的情況下強制扣款
        $parameters = [
            'pay_way' => 'cashfake',
            'opcode' => '40000',
            'od_count' => count($odParameters),
            'od' => $odParameters,
            'force' => true
        ];

        $client->request('PUT', '/api/user/7/multi_order_bunch', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(7, $output['ret']['cash_fake']['user_id']);
        $this->assertEquals(-1600, $output['ret']['cash_fake']['balance']);
        $this->assertEquals(0, $output['ret']['cash_fake']['pre_sub']);

        $this->assertEquals(40000, $output['ret']['cash_fake_entry'][0]['opcode']);
        $this->assertEquals(-100, $output['ret']['cash_fake_entry'][0]['amount']);
        $this->assertEquals(400, $output['ret']['cash_fake_entry'][0]['balance']);
        $this->assertEquals(2, $output['ret']['cash_fake_entry'][0]['cash_fake_version']);

        $this->assertEquals(40000, $output['ret']['cash_fake_entry'][1]['opcode']);
        $this->assertEquals(-2000, $output['ret']['cash_fake_entry'][1]['amount']);
        $this->assertEquals(-1600, $output['ret']['cash_fake_entry'][1]['balance']);
        $this->assertEquals(3, $output['ret']['cash_fake_entry'][1]['cash_fake_version']);
    }

    /**
     * 測試批次下注但租卡餘額不足
     */
    public function testBunchOrderButCardBalanceNotEnougth()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user   = $em->find('BB\DurianBundle\Entity\User', 8);
        $user->setCurrency(901); // TWD

        $card = $em->find('BB\DurianBundle\Entity\Card', 7);
        $card->enable();
        $em->flush();
        $em->clear();
        $now = new \DateTime('now');
        $morn = clone $now;
        $morn->add(new \DateInterval('P1D'));

        $odParameters = array (
            array('am'   => -10,
                  'card' => -9999,
                  'ref'  => '15569985',
                  'memo' => 'test order data 1'),
        );

        $parameters = array (
            'pay_way'          => 'credit',
            'opcode'           => '1001',
            'credit_group_num' => '1',
            'od_count'         => count($odParameters),
            'at'               => $now->format(\DateTime::ISO8601),
            'operator'         => 'thorblack',
            'od'               => $odParameters,
        );

        $client->request('PUT', '/api/user/8/multi_order_bunch', $parameters);
        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);


        $cashfake = $em->find('BB\DurianBundle\Entity\CashFake', 1);
        $card = $em->find('BB\DurianBundle\Entity\Card', 7);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150030011', $output['code']);
        $this->assertEquals('Not enough card balance', $output['msg']);
        $this->assertEquals(0, $card->getBalance());
        $this->assertEquals(500, $cashfake->getBalance());
        $this->assertEquals(0, $cashfake->getPreSub());
        $this->assertEquals(0, $cashfake->getPreAdd());
    }

    /**
     * 測試以現金批次下注(有錯則全部回溯並且中斷程式)
     */
    public function testDoOrderBunchByCash()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $client = $this->createClient();
        $user = $em->find('BB\DurianBundle\Entity\User', 7);

        $card = $em->find('BB\DurianBundle\Entity\Card', 6);
        $card->enable();
        $cardEntry = $card->addEntry('9901', $user->getUsername(), 200);
        $cardEntry->setId(4);
        $em->persist($cardEntry);

        $em->flush();
        $em->clear();

        $memo = '';
        for ($i = 0; $i < 100; $i++) {
            $memo .= 'a';
        }

        $odParameters = [
            [
                'am'     => -100,
                'card'   => -1,
                'ref'    => '166569984',
                'memo'   => 'test bunch order data 1'
            ],
            [
                'am'     => -100,
                'card'   => -1,
                'ref'    => '166569985',
                'memo'   => 'test bunch order data 2'
            ],
            [
                'am'     => -100,
                'card'   => -1,
                'ref'    => '166569986',
                'memo'   => $memo . '012'
            ]
        ];

        $parameters = array (
            'pay_way'              => 'cash',
            'opcode'               => '30001',
            'od_count'             => count($odParameters),
            'od'                   => $odParameters,
        );

        $client->request('PUT', '/api/user/7/multi_order_bunch', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $at = (new \DateTime($output['ret']['cash_entry'][2]['created_at']))->format('YmdHis');

        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-card-poper');

        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 1]);
        $this->runCommand('durian:run-card-sync');

        $card = $em->find('BB\DurianBundle\Entity\Card', 6);
        $cash = $em->find('BBDurianBundle:Cash', 6);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(6, $output['ret']['cash']['id']);
        $this->assertEquals(700, $output['ret']['cash']['balance']);
        $this->assertEquals(0, $output['ret']['cash']['pre_sub']);
        $this->assertEquals(0, $output['ret']['cash']['pre_add']);
        $this->assertEquals($parameters['od_count'], count($output['ret']['cash_entry']));
        $this->assertEquals(5, $cash->getVersion());
        $this->assertGreaterThanOrEqual($at, $cash->getLastEntryAt());

        $cashEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $output['ret']['cash_entry'][0]['id']]);
        $this->assertEquals(7, $output['ret']['cash_entry'][0]['user_id']);
        $this->assertEquals('TWD', $output['ret']['cash_entry'][0]['currency']);
        $this->assertEquals(166569984, $output['ret']['cash_entry'][0]['ref_id']);
        $this->assertEquals(900, $output['ret']['cash_entry'][0]['balance']);
        $this->assertEquals(-100, $output['ret']['cash_entry'][0]['amount']);
        $this->assertEquals(3, $cashEntry->getCashVersion());
        $this->assertEquals(3, $output['ret']['cash_entry'][0]['cash_version']);
        $cashEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $output['ret']['cash_entry'][1]['id']]);
        $this->assertEquals(7, $output['ret']['cash_entry'][1]['user_id']);
        $this->assertEquals('TWD', $output['ret']['cash_entry'][1]['currency']);
        $this->assertEquals(166569985, $output['ret']['cash_entry'][1]['ref_id']);
        $this->assertEquals(800, $output['ret']['cash_entry'][1]['balance']);
        $this->assertEquals(-100, $output['ret']['cash_entry'][1]['amount']);
        $this->assertEquals(4, $cashEntry->getCashVersion());
        $this->assertEquals(4, $output['ret']['cash_entry'][1]['cash_version']);
        $cashEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $output['ret']['cash_entry'][2]['id']]);
        $this->assertEquals(7, $output['ret']['cash_entry'][2]['user_id']);
        $this->assertEquals('TWD', $output['ret']['cash_entry'][2]['currency']);
        $this->assertEquals($memo, $output['ret']['cash_entry'][2]['memo']);
        $this->assertEquals(166569986, $output['ret']['cash_entry'][2]['ref_id']);
        $this->assertEquals(700, $output['ret']['cash_entry'][2]['balance']);
        $this->assertEquals(-100, $output['ret']['cash_entry'][2]['amount']);
        $this->assertEquals(5, $cashEntry->getCashVersion());
        $this->assertEquals($memo, $cashEntry->getMemo());
        $this->assertEquals(5, $output['ret']['cash_entry'][2]['cash_version']);

        $this->assertEquals($card->getId(), $output['ret']['card']['id']);
        $this->assertEquals($card->getUser()->getId(), $output['ret']['card']['user_id']);
        $this->assertEquals($card->getBalance(), $output['ret']['card']['balance']);
        $this->assertEquals(197, $output['ret']['card']['balance']);
        $this->assertEquals($card->isEnabled(), $output['ret']['card']['enable']);
        $this->assertEquals(4, $card->getVersion());

        $cardEntry = $em->find('BB\DurianBundle\Entity\CardEntry', $output['ret']['card_entry'][0]['id']);
        $this->assertEquals($cardEntry->getId(), $output['ret']['card_entry'][0]['id']);
        $this->assertEquals(1001, $cardEntry->getId());
        $this->assertEquals($cardEntry->getOpcode(), $output['ret']['card_entry'][0]['opcode']);
        $this->assertEquals($cardEntry->getAmount(), $output['ret']['card_entry'][0]['amount']);
        $this->assertEquals($cardEntry->getRefId(), $output['ret']['card_entry'][0]['ref_id']);
        $this->assertEquals('ztester', $output['ret']['card_entry'][0]['operator']);
        $this->assertEquals(2, $cardEntry->getCardVersion());
        $this->assertEquals($cardEntry->getCardVersion(), $output['ret']['card_entry'][0]['card_version']);

        $cardEntry = $em->find('BB\DurianBundle\Entity\CardEntry', $output['ret']['card_entry'][1]['id']);
        $this->assertEquals($cardEntry->getId(), $output['ret']['card_entry'][1]['id']);
        $this->assertEquals(1002, $cardEntry->getId());
        $this->assertEquals($cardEntry->getOpcode(), $output['ret']['card_entry'][1]['opcode']);
        $this->assertEquals($cardEntry->getAmount(), $output['ret']['card_entry'][1]['amount']);
        $this->assertEquals($cardEntry->getRefId(), $output['ret']['card_entry'][1]['ref_id']);
        $this->assertEquals('ztester', $output['ret']['card_entry'][1]['operator']);
        $this->assertEquals(3, $cardEntry->getCardVersion());
        $this->assertEquals($cardEntry->getCardVersion(), $output['ret']['card_entry'][1]['card_version']);

        $cardEntry = $em->find('BB\DurianBundle\Entity\CardEntry', $output['ret']['card_entry'][2]['id']);
        $this->assertEquals($cardEntry->getId(), $output['ret']['card_entry'][2]['id']);
        $this->assertEquals(1003, $cardEntry->getId());
        $this->assertEquals($cardEntry->getOpcode(), $output['ret']['card_entry'][2]['opcode']);
        $this->assertEquals($cardEntry->getAmount(), $output['ret']['card_entry'][2]['amount']);
        $this->assertEquals($cardEntry->getRefId(), $output['ret']['card_entry'][2]['ref_id']);
        $this->assertEquals('ztester', $output['ret']['card_entry'][2]['operator']);
        $this->assertEquals(4, $cardEntry->getCardVersion());
        $this->assertEquals($cardEntry->getCardVersion(), $output['ret']['card_entry'][2]['card_version']);
    }

    /**
     * 測試以現金批次下注opcode為1098時不更新明細時間
     */
    public function testDoOrderBunchByCashWithoutUpdateAt()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $user = $em->find('BBDurianBundle:User', 7);
        $card = $em->find('BBDurianBundle:Card', 6);
        $card->enable();
        $cardEntry = $card->addEntry('9901', $user->getUsername(), 200);
        $cardEntry->setId(4);
        $em->persist($cardEntry);

        $em->flush();
        $em->clear();

        $odParameters = [
            [
                'am'     => -100,
                'card'   => -1,
                'ref'    => '166569984',
                'memo'   => 'test bunch order data 1'
            ]
        ];

        $parameters = [
            'pay_way'  => 'cash',
            'opcode'   => 1098,
            'od_count' => count($odParameters),
            'operator' => 'thorblack',
            'od'       => $odParameters,
        ];

        $output = $this->getResponse('PUT', '/api/user/7/multi_order_bunch', $parameters);

        $this->assertEquals('ok', $output['result']);

        $this->runCommand('durian:run-cash-sync');

        $em->clear();

        $cash = $em->find('BBDurianBundle:Cash', 6);
        $this->assertEquals(20120101120000, $cash->getLastEntryAt());
    }

    /**
     * 測試以現金批次下注但現金餘額不足(有錯則全部回溯並且中斷程式)，
     * 並檢查租卡及現金的餘額為扣款前
     */
    public function testDoOrderBunchByCashButNotEnoughBalance()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redisWallet = $this->getContainer()->get('snc_redis.wallet3');
        $client = $this->createClient();
        $user = $em->find('BB\DurianBundle\Entity\User', 7);
        $user->setCurrency(156); // CNY

        $card = $em->find('BB\DurianBundle\Entity\Card', 6);
        $card->enable();
        $cardEntry = $card->addEntry('9901', $user->getUsername(), 200);
        $cardEntry->setId(4);
        $em->persist($cardEntry);

        $em->flush();
        $em->clear();

        $odParameters = [
            [
                'am'     => -2000,
                'card'   => -1,
                'ref'    => '155699875',
                'memo'   => 'blow it'
            ],
            [
                'am'     => -2000,
                'card'   => -1,
                'ref'    => '155699876',
                'memo'   => 'blow it'
            ],
            [
                'am'     => -2000,
                'card'   => -1,
                 'ref'   => '155699877',
                 'memo'  => 'blow it'
            ]
        ];

        $parameters = array (
            'pay_way'              => 'cash',
            'opcode'               => '30001',
            'od_count'             => count($odParameters),
            'od'                   => $odParameters,
        );

        $client->request('PUT', '/api/user/7/multi_order_bunch', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $cash = $em->find('BB\DurianBundle\Entity\Cash', 6);
        $card = $em->find('BB\DurianBundle\Entity\Card', 6);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150580020, $output['code']);
        $this->assertEquals('Not enough balance', $output['msg']);
        $this->assertEquals(200, $card->getBalance());
        $this->assertEquals(1000, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());
    }

    /**
     * 測試以現金批次下注時, 可以強制扣款
     */
    public function testDoOrderBunchForceCashWithForce()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BBDurianBundle:User', 7);
        $user->getCard()->enable();
        $user->setBankrupt(true);
        $em->flush();
        $em->clear();

        $odParameters = [
            [
                'am'     =>  -100,
                'card'   => -1000
            ],
            [
                'am'     =>  -2000,
                'card'   => -100
            ]
        ];

        $parameters = [
            'pay_way' => 'cash',
            'opcode' => '30001',
            'force' => true,
            'od_count' => count($odParameters),
            'od' => $odParameters,
        ];

        $client->request('PUT', '/api/user/7/multi_order_bunch', $parameters);

        // 取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(7, $output['ret']['cash']['user_id']);
        $this->assertEquals(-1100, $output['ret']['cash']['balance']);
        $this->assertEquals(0, $output['ret']['cash']['pre_sub']);

        $this->assertEquals(30001, $output['ret']['cash_entry'][0]['opcode']);
        $this->assertEquals(-100, $output['ret']['cash_entry'][0]['amount']);
        $this->assertEquals(900, $output['ret']['cash_entry'][0]['balance']);
        $this->assertEquals(3, $output['ret']['cash_entry'][0]['cash_version']);

        $this->assertEquals(30001, $output['ret']['cash_entry'][1]['opcode']);
        $this->assertEquals(-2000, $output['ret']['cash_entry'][1]['amount']);
        $this->assertEquals(-1100, $output['ret']['cash_entry'][1]['balance']);
        $this->assertEquals(4, $output['ret']['cash_entry'][1]['cash_version']);

        $this->assertEquals(7, $output['ret']['card']['user_id']);
        $this->assertEquals(-1100, $output['ret']['card']['balance']);
        $this->assertEquals(30001, $output['ret']['card']['opcode']);

        $this->assertEquals(7, $output['ret']['card_entry'][0]['user_id']);
        $this->assertEquals(-1000, $output['ret']['card_entry'][0]['amount']);
        $this->assertEquals(-1000, $output['ret']['card_entry'][0]['balance']);
        $this->assertEquals(30001, $output['ret']['card_entry'][0]['opcode']);
        $this->assertEquals(2, $output['ret']['card_entry'][0]['card_version']);

        $this->assertEquals(7, $output['ret']['card_entry'][1]['user_id']);
        $this->assertEquals(-100, $output['ret']['card_entry'][1]['amount']);
        $this->assertEquals(-1100, $output['ret']['card_entry'][1]['balance']);
        $this->assertEquals(30001, $output['ret']['card_entry'][1]['opcode']);
        $this->assertEquals(3, $output['ret']['card_entry'][1]['card_version']);
    }

    /**
     * 測試佔成操作相關例外
     */
    public function testGetShareLimitException()
    {
        //測試帶入不合法的佔成群組
        $params = [
            'pay_way' => 'cash',
            'opcode' => 1001,
            'amount' => 100,
            'auto_commit' => 1,
            'ref_id' => 123,
            'auto_commit' => 1,
            'sharelimit_group_num' => '佔成'
        ];
        $ret = $this->getResponse('POST', '/api/user/8/order', $params);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150140013, $ret['code']);
        $this->assertEquals('Invalid group number', $ret['msg']);

        //測試帶入佔成群組但卻沒帶入時間
        $params = [
            'pay_way' => 'cash',
            'opcode' => 1001,
            'amount' => 100,
            'auto_commit' => 1,
            'ref_id' => 123,
            'auto_commit' => 1,
            'sharelimit_group_num' => 1
        ];
        $ret = $this->getResponse('POST', '/api/user/8/order', $params);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150140023, $ret['code']);
        $this->assertEquals('Must send timestamp', $ret['msg']);

        //測試佔成分配動作已經過期
        $params = [
            'pay_way' => 'cash',
            'opcode' => 1001,
            'amount' => 100,
            'auto_commit' => 1,
            'ref_id' => 123,
            'auto_commit' => 1,
            'sharelimit_group_num' => 1,
            'at' => '2011-10-01 11:59:00'
        ];
        $ret = $this->getResponse('POST', '/api/user/8/order', $params);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150140029, $ret['code']);
        $this->assertEquals('The get sharelimit division action is expired', $ret['msg']);
    }

    /**
     * 測試佔成操作設定是否預改佔成
     */
    public function testGetShareLimitSetIsNext()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:ShareUpdateCron');
        $shareUpdateCron = $repo->findOneBy(['groupNum' => 1]);
        $shareUpdateCron->reRun();
        $em->flush();

        $params = [
            'pay_way' => 'cash',
            'opcode' => 1001,
            'amount' => 100,
            'auto_commit' => 1,
            'ref_id' => 123,
            'sharelimit_group_num' => 1,
            'at' => '2011-10-01 11:59:00'
        ];

        $this->getResponse('POST', '/api/user/8/order', $params);
    }

    /**
     * 測試信用額度批次下注，rollback情況
     */
    public function testMultiOrderCreditRollback()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $now = new \DateTime('now');

        $mockCreditOp = $this->getMockBuilder('BB\DurianBundle\Credit\CreditOperator')
                ->setMethods(['bunchConfirm'])
                ->getMock();

        $mockCreditOp->setContainer($this->getContainer());

        $mockCreditOp->expects($this->any())
            ->method('bunchConfirm')
            ->will($this->throwException(new \Exception('Connection refused')));

        $odParameters = [
            [
                'am' => -200,
                'ref' => '15569985',
                'memo' => 'test order data 1'
            ],
            [
                'am' => -200,
                'ref' => '15569986',
                'memo' => 'test order data 2'
            ],
            [
                'am' => -200,
                'ref' => '15569987',
                'memo' => 'test order data 3'
            ]
        ];

        $parameters = [
            'pay_way' => 'credit',
            'opcode' => '1001',
            'credit_group_num' => '1',
            'od_count' => count($odParameters),
            'at' => $now->format(\DateTime::ISO8601),
            'operator' => 'thorblack',
            'od' => $odParameters
        ];

        $client = $this->createClient();
        $client->getContainer()->set('durian.credit_op', $mockCreditOp);
        $client->request('PUT', '/api/user/8/multi_order_bunch', $parameters);

        $user = $em->find('BBDurianBundle:User', 8);
        $credit = $user->getCredit(1);

        $this->assertEquals(5000, $credit->getBalance());
    }

    /**
     * 測試CashFake批次下注，rollback情況
     */
    public function testMultiOrderCashFakeRollback()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $mockCashFakeOp = $this->getMockBuilder('BB\DurianBundle\CashFake\CashFakeOperator')
            ->setMethods(['bunchConfirm'])
            ->getMock();

        $mockCashFakeOp->setContainer($this->getContainer());

        $mockCashFakeOp->expects($this->any())
            ->method('bunchConfirm')
            ->will($this->throwException(new \Exception('Connection refused')));

        $odParameters = [
            [
                'am' => 100,
                'ref' => '155699875',
                'memo' => 'blow it'
            ],
            [
                'am' => 100,
                'ref' => '155699876',
                'memo' => 'blow it'
            ],
            [
                'am' => 100,
                'ref' => '155699877',
                'memo' => 'blow it'
            ]
        ];

        $parameters = [
            'pay_way' => 'cashfake',
            'opcode' => '1001',
            'od_count' => count($odParameters),
            'operator' => 'thorblack',
            'od' => $odParameters
        ];

        $client = $this->createClient();
        $client->getContainer()->set('durian.cashfake_op', $mockCashFakeOp);
        $client->request('PUT', '/api/user/7/multi_order_bunch', $parameters);

        $user = $em->find('BBDurianBundle:User', 7);
        $cashFake = $user->getCashFake();
        $this->assertEquals(500, $cashFake->getBalance());
    }

    /**
     * 測試Cash批次下注，rollback情況
     */
    public function testMultiOrderCashRollback()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $mockOp = $this->getMockBuilder('BB\DurianBundle\Service\OpService')
            ->setMethods(['cashDirectOpByRedis'])
            ->getMock();

        $mockOp->setContainer($this->getContainer());

        $mockOp->expects($this->any())
            ->method('cashDirectOpByRedis')
            ->will($this->returnValue(true));

        $redis = $this->getMockBuilder('Predis\Client')
            ->disableOriginalConstructor()
            ->setMethods(['lpush'])
            ->getMock();

        $redis->expects($this->at(0))
            ->method('lpush')
            ->will($this->throwException(new \Exception('Connection timed out', SOCKET_ETIMEDOUT)));

        $odParameters = [
            [
                'am' => -100,
                'ref' => '155699875',
                'memo' => 'blow it'
            ],
            [
                'am' => -100,
                'ref' => '155699876',
                'memo' => 'blow it'
            ],
            [
                'am' => -100,
                'ref' => '155699877',
                'memo' => 'blow it'
            ]
        ];

        $parameters = [
            'pay_way' => 'cash',
            'opcode' => '30001',
            'od_count' => count($odParameters),
            'od' => $odParameters
        ];

        $client = $this->createClient();
        $client->getContainer()->set('durian.op', $mockOp);
        $client->getContainer()->set('snc_redis.default', $redis);
        $client->request('PUT', '/api/user/7/multi_order_bunch', $parameters);

        $user = $em->find('BBDurianBundle:User', 7);
        $cash = $user->getCash();
        $this->assertEquals(1000, $cash->getBalance());
    }

    /**
     * 測試下注時ref_id帶空字串會送0到queue並回傳空字串
     */
    public function testOrderWithEmptyRefId()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default');
        $now = new \DateTime();
        $now = $now->format(\DateTime::ISO8601);

        $user = $em->find('BBDurianBundle:User', 8);
        $user->getCard()->enable();
        $user->getCard()->setBalance(1000);
        $em->flush();

        // 測試credit,card
        $parameters = [
            'pay_way'          => 'credit',
            'amount'           => -200,
            'opcode'           => '1001',
            'credit_group_num' => '1',
            'card_amount'      => -1,
            'ref_id'           => '',
            'credit_at'        => $now
        ];

        $output = $this->getResponse('POST', '/api/user/8/order', $parameters);

        $creditQueue = json_decode($redis->rpop('credit_entry_queue'), true);
        $cardQueue = json_decode($redis->rpop('card_queue'), true);

        $this->assertSame(0, $creditQueue['ref_id']);
        $this->assertSame(0, $cardQueue['ref_id']);
        $this->assertEquals('', $output['ret']['card_entry']['ref_id']);
        // 測試cashfake
        $parameters = [
            'pay_way' => 'cashfake',
            'amount'  => -10,
            'opcode'  => '1001',
            'ref_id'  => '',
        ];

        $output = $this->getResponse('POST', '/api/user/7/order', $parameters);

        $cashFakeQueue = json_decode($redis->rpop('cash_fake_entry_queue'), true);

        $this->assertSame(0, $cashFakeQueue['ref_id']);
        $this->assertEquals('', $output['ret']['cash_fake_entry'][0]['ref_id']);

        // 測試cash
        $parameters = [
            'pay_way' => 'cash',
            'amount'  => -10,
            'opcode'  => '1001',
            'ref_id'  => '',
        ];

        $output = $this->getResponse('POST', '/api/user/7/order', $parameters);

        $cashQueue = json_decode($redis->rpop('cash_queue'), true);

        $this->assertSame(0, $cashQueue['ref_id']);
        $this->assertEquals('', $output['ret']['cash_entry']['ref_id']);
    }

    /**
     * 測試多重下注，ref_id帶空字串會送0到queue並回傳空字串
     */
    public function testMultiOrderWithEmptyRefId()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default');
        $now = new \DateTime();
        $now = $now->format(\DateTime::ISO8601);

        $user = $em->find('BBDurianBundle:User', 8);
        $user->getCard()->enable();
        $user->getCard()->setBalance(1000);
        $em->flush();

        // 測試credit,card
        $parameters['orders'][] = [
            'user_id'          => 8,
            'pay_way'          => 'credit',
            'amount'           => -200,
            'opcode'           => '1001',
            'credit_group_num' => '1',
            'card_amount'      => -1,
            'ref_id'           => '',
            'credit_at'        => $now
        ];

        // 測試cashfake
        $parameters['orders'][] = [
            'user_id' => 7,
            'pay_way' => 'cashfake',
            'amount'  => -10,
            'opcode'  => '1001',
            'ref_id'  => '',
        ];

        // 測試cash
        $parameters['orders'][] = [
            'user_id' => 7,
            'pay_way' => 'cash',
            'amount'  => -10,
            'opcode'  => '1001',
            'ref_id'  => '',
        ];

        $output = $this->getResponse('PUT', '/api/orders', $parameters);

        $creditQueue = json_decode($redis->rpop('credit_entry_queue'), true);
        $cardQueue = json_decode($redis->rpop('card_queue'), true);
        $cashFakeQueue = json_decode($redis->rpop('cash_fake_entry_queue'), true);
        $cashQueue = json_decode($redis->rpop('cash_queue'), true);

        $this->assertSame(0, $creditQueue['ref_id']);
        $this->assertSame(0, $cardQueue['ref_id']);
        $this->assertSame(0, $cashFakeQueue['ref_id']);
        $this->assertSame(0, $cashQueue['ref_id']);
        $this->assertEquals('', $output[0]['ret']['card_entry']['ref_id']);
        $this->assertEquals('', $output[1]['ret']['cash_fake_entry'][0]['ref_id']);
        $this->assertEquals('', $output[2]['ret']['cash_entry']['ref_id']);
    }

    /**
     * 測試批次下注，ref_id帶空字串會送0到queue並回傳空字串
     */
    public function testMultiOrderBunchWithEmptyRefId()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default');
        $now = new \DateTime();

        $user = $em->find('BBDurianBundle:User', 8);
        $user->getCard()->enable();
        $user->getCard()->setBalance(1000);
        $em->flush();

        $odParameters[] = [
            'am'     => 123,
            'card'   => -1,
            'ref'    => ''
        ];

        // 測試cash,card
        $parameters = [
            'pay_way'  => 'cash',
            'opcode'   => '1001',
            'od_count' => count($odParameters),
            'od'       => $odParameters
        ];

        $output = $this->getResponse('PUT', '/api/user/8/multi_order_bunch', $parameters);

        $cashQueue = json_decode($redis->rpop('cash_queue'), true);
        $cardQueue = json_decode($redis->rpop('card_queue'), true);

        $this->assertSame(0, $cashQueue['ref_id']);
        $this->assertSame(0, $cardQueue['ref_id']);
        $this->assertEquals('', $output['ret']['cash_entry'][0]['ref_id']);
        $this->assertEquals('', $output['ret']['card_entry'][0]['ref_id']);

        $odParameters[] = [
            'am'  => 123,
            'ref' => ''
        ];

        // 測試cashfake
        $parameters = [
            'pay_way'  => 'cashfake',
            'opcode'   => '1001',
            'od_count' => count($odParameters),
            'od'       => $odParameters
        ];

        $output = $this->getResponse('PUT', '/api/user/8/multi_order_bunch', $parameters);

        $queue = json_decode($redis->rpop('cash_fake_entry_queue'), true);

        $this->assertSame(0, $queue['ref_id']);
        $this->assertEquals('', $output['ret']['cash_fake_entry'][0]['ref_id']);

        // 測試credit
        $odParameters[] = [
            'am'  => -1000,
            'ref' => ''
        ];

        $parameters = [
            'pay_way'          => 'credit',
            'opcode'           => '1001',
            'od_count'         => count($odParameters),
            'credit_group_num' => '1',
            'at'               => $now->format(\DateTime::ISO8601),
            'od'               => $odParameters
        ];

        $output = $this->getResponse('PUT', '/api/user/8/multi_order_bunch', $parameters);

        $queue = json_decode($redis->rpop('credit_entry_queue'), true);

        $this->assertSame(0, $queue['ref_id']);
        $this->assertEquals('ok', $output['result']);
    }

    /**
     * 測試批次下注，但無下注資訊
     */
    public function testMultiOrderBunchWithoutOd()
    {
        $parameters = [
            'pay_way'  => 'cash',
            'opcode'   => '1001',
            'od_count' => 1,
            'od'       => ['ref' => '1']
        ];

        $output = $this->getResponse('PUT', '/api/user/8/multi_order_bunch', $parameters);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150140018, $output['code']);
        $this->assertEquals('No amount specified', $output['msg']);
    }
}
