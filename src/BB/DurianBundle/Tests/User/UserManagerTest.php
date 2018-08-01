<?php

namespace BB\DurianBundle\Tests\User;

use BB\DurianBundle\Entity\Cash;
use BB\DurianBundle\Entity\CashFake;
use BB\DurianBundle\Entity\Credit;
use BB\DurianBundle\Entity\DomainCurrency;
use BB\DurianBundle\Entity\DomainConfig;
use BB\DurianBundle\Entity\DomainTotalTest;
use BB\DurianBundle\Entity\OauthUserBinding;
use BB\DurianBundle\Entity\ShareLimit;
use BB\DurianBundle\Entity\ShareLimitNext;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\UserDetail;
use BB\DurianBundle\Entity\UserEmail;
use BB\DurianBundle\Entity\UserPassword;
use BB\DurianBundle\Entity\UserPayway;
use BB\DurianBundle\Entity\PresetLevel;
use BB\DurianBundle\Tests\Functional\WebTestCase;

class UserManagerTest extends WebTestCase
{
    /**
     * @var \BB\DurianBundle\User\UserManager
     */
    private $userManager;

    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCardData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeTransferEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCreditData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCreditPeriodData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadOauthUserBindingData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadOauthVendorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareLimitData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareLimitNextData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserDetailData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserEmailData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserPasswordData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserPaywayData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRegisterBonusData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDepositConfirmQuotaData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPromotionData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelCurrencyData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashNegativeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeNegativeData'
        ];
        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemovedUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadChatRoomData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadSlideBindingData'
        ];
        $this->loadFixtures($classnames, 'share');

        $this->loadFixtures([], 'entry');

        $this->userManager = $this->getContainer()->get('durian.user_manager');

        $redis = $this->getContainer()->get('snc_redis.sequence');

        $redis->set('cash_seq', 1000);
        $redis->set('cashfake_seq', 1000);
        $redis->set('card_seq', 1000);
    }

    /**
     * 測試體系查詢專用
     */
    public function testFindByFuzzyName()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $userRepo = $em->getRepository('BBDurianBundle:User');

        $users = $userRepo->findByFuzzyName('%test%');

        $this->assertEquals('vtester', $users[0]->getUsername());
        $this->assertEquals('wtester', $users[1]->getUsername());
        $this->assertEquals('xtester', $users[2]->getUsername());
        $this->assertEquals('ytester', $users[3]->getUsername());
        $this->assertEquals('ztester', $users[4]->getUsername());
        $this->assertEquals('tester', $users[5]->getUsername());
    }

    /**
     * 測試體系查詢專用(指定查詢)
     */
    public function testFindByFuzzyNameWithoutFuzzyParameter()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $userRepo = $em->getRepository('BBDurianBundle:User');
        $users = $userRepo->findByFuzzyName('tester');

        $this->assertEquals('tester', $users[0]->getUsername());
    }

    /**
     * 測試體系查詢專用(指定Domain)
     */
    public function testFindByFuzzyNameWithDomain()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $userRepo = $em->getRepository('BBDurianBundle:User');

        $user = $userRepo->find(5);
        $user->setDomain('999');

        $user = $userRepo->find(8);
        $user->setDomain('999');

        $em->flush();

        $users = $userRepo->findByFuzzyName('%tester', 999);

        $this->assertEquals('tester', $users[0]->getUsername());
        $this->assertEquals('xtester', $users[1]->getUsername());
    }

    /**
     * 測試下層數量計算
     */
    public function testCountChildOf()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $userRepo = $em->getRepository('BBDurianBundle:User');
        $parentZ = $userRepo->findOneByUsername('ztester');

        $this->assertEquals(2, $userRepo->countChildOf($parentZ));

        $parentY = $userRepo->findOneByUsername('ytester');
        $this->assertEquals(3, $userRepo->countChildOf($parentY));
    }

    /**
     * 測試帶null下層數量計算
     */
    public function testCountChildOfWithNull()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $userRepo = $em->getRepository('BBDurianBundle:User');

        $this->assertEquals(2, $userRepo->countChildOf(null));
    }

    /**
     * 測試子層查詢
     */
    public function testfindChildBy()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $userRepo = $em->getRepository('BBDurianBundle:User');
        $parentZ = $userRepo->findOneByUsername('ztester');

        $criteria = array(
          'sub'   => 0,
          'block' => 0
        );
        $params['criteria'] = $criteria;
        $params['depth'] = null;

        $children = $userRepo->findChildBy($parentZ, $params);
        $this->assertEquals('tester', $children[0]->getUsername());

        $parentY = $userRepo->findOneByUsername('ytester');
        $children = $userRepo->findChildBy($parentY, $params);
        $this->assertEquals('ztester', $children[0]->getUsername());
    }

    /**
     * 測試子層查詢 層數錯誤的情況
     */
    public function testfindChildByWhenDepthInvalid()
    {
        $message = 'If you want to get child from any depth,' .
            ' Set parameter depth = null, thx.';
        $this->setExpectedException('InvalidArgumentException', $message, 150010028);

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $userRepo = $em->getRepository('BBDurianBundle:User');
        $parentZ = $userRepo->findOneByUsername('ztester');

        $criteria = array(
            'sub'   => 0,
            'block' => 0
        );

        $params['criteria'] = $criteria;
        $params['depth'] = -1;

        $userRepo->findChildBy($parentZ, $params);
    }

    /**
     * 測試子層查詢
     */
    public function testfindChildByNull()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $userRepo = $em->getRepository('BBDurianBundle:User');

        $criteria = array(
          'sub'   => 0,
          'block' => 0
        );
        $params['criteria'] = $criteria;
        $params['depth'] = null;

        $children = $userRepo->findChildBy(null, $params);

        $this->assertEquals('company', $children[0]->getUsername());
        $this->assertEquals('isolate', $children[1]->getUsername());
    }

    /**
     * 測試根據條件回傳所有子層info陣列層數錯誤的情況
     */
    public function testfindChildArrayByWhenDepthInvalid()
    {
        $message = 'If you want to get child from any depth,' .
            ' Set parameter depth = null, thx.';
        $this->setExpectedException('InvalidArgumentException', $message, 150010028);

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $userRepo = $em->getRepository('BBDurianBundle:User');
        $parentZ = $userRepo->findOneByUsername('ztester');

        $criteria = [
            'sub'   => 0,
            'block' => 0
        ];

        $params['criteria'] = $criteria;
        $params['depth'] = -1;

        $userRepo->findChildArrayBy($parentZ, $params);
    }

    /**
     * 測試以userDetail取得下層user陣列
     */
    public function testfindChildArrayByUserDetail()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $userRepo = $em->getRepository('BBDurianBundle:User');
        $parentZ = $userRepo->findOneByUsername('ztester');

        $criteria = [
            'sub'   => 0,
            'block' => 0
        ];
        $searchSet = [
            'UserDetail' => [
                [
                    'field' => 'nameReal',
                    'value' => '達文西'
                ]
            ]
        ];

        $params['criteria'] = $criteria;
        $params['depth'] = 1;

        $result = $userRepo->findChildArrayBy($parentZ, $params, $searchSet);
        $user = $result[0];

        $this->assertEquals(8, $user['id']);
        $this->assertEquals('tester', $user['alias']);
        $this->assertFalse($user['sub']);
        $this->assertFalse($user['block']);
    }

    /**
     * 測試根據bank取得下層user陣列
     */
    public function testfindChildArrayByBank()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $userRepo = $em->getRepository('BBDurianBundle:User');
        $parentZ = $userRepo->findOneByUsername('ztester');

        $criteria = [
            'sub'   => 0,
            'block' => 0
        ];
        $searchSet = [
            'Bank' => [
                [
                    'field' => 'account',
                    'value' => '6221386170003601228'
                ]
            ]
        ];

        $params['criteria'] = $criteria;
        $params['depth'] = 1;

        $result = $userRepo->findChildArrayBy($parentZ, $params, $searchSet);
        $user = $result[0];

        $this->assertEquals(8, $user['id']);
        $this->assertEquals('tester', $user['alias']);
        $this->assertFalse($user['sub']);
        $this->assertFalse($user['block']);
    }

    /**
     * 測試根據bank取得下層user陣列
     */
    public function testfindChildArrayByUser()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $userRepo = $em->getRepository('BBDurianBundle:User');
        $parentZ = $userRepo->findOneByUsername('ztester');

        $criteria = [
            'sub'   => 0,
            'block' => 0
        ];
        $searchSet = [
            'User' => [
                [
                    'field' => 'username',
                    'value' => 'tester'
                ]
            ]
        ];

        $params['criteria'] = $criteria;
        $params['depth'] = 2;

        $result = $userRepo->findChildArrayBy($parentZ, $params, $searchSet);

        $this->assertEmpty($result);
    }

    /**
     * 測試使用id陣列回傳多使用者代入空陣列
     */
    public function testUserRepoGetMultiUserByIds()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $userRepo = $em->getRepository('BBDurianBundle:User');
        $result = $userRepo->getMultiUserByIds([]);

        $this->assertEmpty($result);
    }

    /**
     * 測試藉由會員ID回傳所有上層username代入空陣列
     */
    public function testUserRepoGetMemberAllParentUsername()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $userRepo = $em->getRepository('BBDurianBundle:User');
        $result = $userRepo->getMemberAllParentUsername([]);

        $this->assertEmpty($result);
    }

    /**
     * 測試取得使用者的階層時代入null
     */
    public function testUserRepoGetLevelWithNullUser()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $userRepo = $em->getRepository('BBDurianBundle:User');
        $result = $userRepo->getLevel(null);

        $this->assertNull($result);
    }

    /**
     * 測試刪除帳號資料
     */
    public function testRemove()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $userRepo = $em->getRepository('BBDurianBundle:User');
        $user = $userRepo->findOneByUsername('tester');
        $userId = $user->getId();

        $cash = new Cash($user, 156); // CNY
        $em->persist($cash);
        $em->flush();

        $cashHelper = $this->getContainer()->get('durian.cash_helper');
        $cashHelper->addCashEntry($cash, 1001, 1000);
        $cashHelper->addCashEntry($cash, 1002, -1000);
        $emEntry->flush();

        // 新增預設層級用於測試刪除帳號時是否會刪除預設層級
        $level = $em->find('BBDurianBundle:Level', 2);
        $presetLevel = new PresetLevel($user, $level);
        $em->persist($presetLevel);

        $em->flush();

        $this->userManager->remove($user);

        $em->flush();
        $emShare->flush();

        $emShare->clear();

        //資料是否備份
        $removedUser = $emShare->find('BBDurianBundle:RemovedUser', 8);
        $this->assertInstanceOf('BB\DurianBundle\Entity\RemovedUser', $removedUser);

        $removedCash = $emShare->find('BBDurianBundle:RemovedCash', 1);
        $this->assertInstanceOf('BB\DurianBundle\Entity\RemovedCash', $removedCash);

        $removedCashFake = $emShare->find('BBDurianBundle:RemovedCashFake', 2);
        $this->assertInstanceOf('BB\DurianBundle\Entity\RemovedCashFake', $removedCashFake);

        $removedCredit = $emShare->find('BBDurianBundle:RemovedCredit', 5);
        $this->assertInstanceOf('BB\DurianBundle\Entity\RemovedCredit', $removedCredit);

        $removedCredit2 = $emShare->find('BBDurianBundle:RemovedCredit', 6);
        $this->assertInstanceOf('BB\DurianBundle\Entity\RemovedCredit', $removedCredit2);

        $removedCard = $emShare->find('BBDurianBundle:RemovedCard', 7);
        $this->assertInstanceOf('BB\DurianBundle\Entity\RemovedCard', $removedCard);

        $removedDetail = $emShare->find('BBDurianBundle:RemovedUserDetail', 8);
        $this->assertInstanceOf('BB\DurianBundle\Entity\RemovedUserDetail', $removedDetail);

        $removedCash = $emShare->find('BBDurianBundle:RemovedCash', 2);
        $this->assertNull($removedCash);

        $removedDetail = $emShare->find('BBDurianBundle:RemovedUserDetail', 2);
        $this->assertNull($removedDetail);

        //資料是否刪除
        $entity = $em->find('BBDurianBundle:Cash', 1);
        $this->assertEquals(null, $entity);

        $entity = $em->find('BBDurianBundle:CashFake', 2);
        $this->assertEquals(null, $entity);

        $entity = $em->find('BBDurianBundle:Credit', 5);
        $this->assertEquals(null, $entity);

        $entity = $em->find('BBDurianBundle:Credit', 6);
        $this->assertEquals(null, $entity);

        $entity = $em->find('BBDurianBundle:Card', 7);
        $this->assertEquals(null, $entity);

        $entity = $em->find('BBDurianBundle:PresetLevel', $userId);
        $this->assertNull($entity);

        $bindings = $emShare->getRepository('BBDurianBundle:SlideBinding')
            ->findByUserId($userId);
        $this->assertEmpty($bindings);

        //預扣
        $criteria = ['cashId' => 1];
        $entries = $em->getRepository('BBDurianBundle:CashTrans')->findBy($criteria);

        $this->assertEquals(0, count($entries));

        $entity = $em->find('BBDurianBundle:CashFake', 2);
        $this->assertEquals(null, $entity);

        //預扣
        $criteria = ['cashFakeId' => 2];
        $entries = $em->getRepository('BBDurianBundle:CashFakeTrans')->findBy($criteria);
        $this->assertEquals(0, count($entries));

        $entity = $em->find('BBDurianBundle:Credit', 5);
        $this->assertEquals(null, $entity);

        $entity = $em->find('BBDurianBundle:User', 8);
        $this->assertEquals(null, $entity);

        $entity = $em->find('BBDurianBundle:UserDetail', 1);
        $this->assertEquals(null, $entity);

        $entity = $em->find('BB\DurianBundle\Entity\Bank', 1);
        $this->assertEquals(null, $entity);

        $entity = $em->find('BBDurianBundle:UserLevel', 8);
        $this->assertNull($entity);

        $registerBonus = $em->find('BBDurianBundle:RegisterBonus', $userId);
        $this->assertNull($registerBonus);

        $confirmQuota = $em->find('BBDurianBundle:DepositConfirmQuota', $userId);
        $this->assertNull($confirmQuota);

        $promotion = $em->find('BBDurianBundle:Promotion', $userId);
        $this->assertNull($promotion);

        $chatRoom = $em->find('BBDurianBundle:ChatRoom', $userId);
        $this->assertNull($chatRoom);

        $cashNeg = $em->find('BBDurianBundle:CashNegative', ['userId' => 8, 'currency' => 156]);
        $this->assertNull($cashNeg);

        $fakeNeg = $em->find('BBDurianBundle:CashFakeNegative', ['userId' => 8, 'currency' => 156]);
        $this->assertNull($fakeNeg);
    }

    /**
     * 測試刪除測試帳號時會備份其資料
     */
    public function testRemoveTestUser()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');

        $userRepo = $em->getRepository('BBDurianBundle:User');
        $user = $userRepo->findOneByUsername('tester');
        $user->setTest(true);
        $user->setHiddenTest(true);

        $cash = new Cash($user, 156); // CNY
        $em->persist($cash);
        $em->flush();

        $cashHelper = $this->getContainer()->get('durian.cash_helper');
        $cashHelper->addCashEntry($cash, 1001, 1000);
        $cashHelper->addCashEntry($cash, 1002, -1000);
        $em->flush();
        $emEntry->flush();

        $this->userManager->remove($user);

        $em->flush();
        $em->clear();

        // 資料是否備份
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $removedUser = $emShare->find('BBDurianBundle:RemovedUser', 8);
        $this->assertInstanceOf('BB\DurianBundle\Entity\RemovedUser', $removedUser);

        $removedCash = $emShare->find('BBDurianBundle:RemovedCash', 1);
        $this->assertInstanceOf('BB\DurianBundle\Entity\RemovedCash', $removedCash);
        $this->assertEquals(8, $removedCash->getRemovedUser()->getUserId());

        $removedDetail = $emShare->find('BBDurianBundle:RemovedUserDetail', 8);
        $this->assertInstanceOf('BB\DurianBundle\Entity\RemovedUserDetail', $removedDetail);

        // 資料是否刪除
        $entity = $em->find('BBDurianBundle:Cash', 1);
        $this->assertEquals(null, $entity);
    }

    /**
     * 測試刪除子帳號時會備份其資料
     */
    public function testRemoveSubUser()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $userRepo = $em->getRepository('BBDurianBundle:User');
        $user = $userRepo->findOneByUsername('tester');
        $user->setSub(true);
        $em->flush();

        $this->userManager->remove($user);

        $em->flush();
        $em->clear();

        // 資料是否備份
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $removedUser = $emShare->find('BBDurianBundle:RemovedUser', 8);
        $this->assertInstanceOf('BB\DurianBundle\Entity\RemovedUser', $removedUser);

        $removedCash = $emShare->find('BBDurianBundle:RemovedCash', 1);
        $this->assertNull($removedCash);

        $removedDetail = $emShare->find('BBDurianBundle:RemovedUserDetail', 8);
        $this->assertInstanceOf('BB\DurianBundle\Entity\RemovedUserDetail', $removedDetail);
    }

    /**
     * 測試刪除帳號時存在下層帳號
     */
    public function testRemoveChildrenExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Can not remove user when user still anothers parent',
            150010020
        );

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $userRepo = $em->getRepository('BBDurianBundle:User');
        $user = $userRepo->findOneByUsername('ztester');

        $this->assertEquals(2, $userRepo->countChildOf($user));
        $this->userManager->remove($user);
    }

    /**
     * 測試刪除帳號資料，移除redis內的card資料
     */
    public function testRemoveClearCardDataInRedis()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redisWallet = $this->getContainer()->get('snc_redis.wallet4');
        $cardOp = $this->getContainer()->get('durian.card_operator');

        $userRepo = $em->getRepository('BBDurianBundle:User');
        $user = $userRepo->findOneByUsername('tester');

        $card = $user->getCard();
        $card->enable();
        $options = [
            'operator' => '',
            'opcode' => ''
        ];
        $cardOp->cardOpByRedis($card, 10, $options);

        $this->assertTrue($redisWallet->exists('card_balance_' . $user->getId()));

        $this->userManager->remove($user);

        $this->assertFalse($redisWallet->exists('card_balance_' . $user->getId()));
    }

    /**
     * 測試刪除帳號時mysql中cash的餘額與redis不同步
     */
    public function testRemoveCashUnsynchronisedBalance()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Can not remove user due to unsynchronised cash data',
            150010047
        );

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $doctrine = $this->getContainer()->get('doctrine');

        $userRepo = $em->getRepository('BBDurianBundle:User');
        $user = $userRepo->findOneByUsername('domain20m');

        $currency = 156; // CNY

        $cashInfo = [
            'balance' => 10,
            'pre_sub' => 0,
            'pre_add' => 0
        ];

        $cashOp = $this->getMockBuilder('BB\DurianBundle\Service\OpService')
            ->disableOriginalConstructor()
            ->setMethods(['getRedisCashBalance'])
            ->getMock();
        $cashOp->expects($this->any())
            ->method('getRedisCashBalance')
            ->will($this->returnValue($cashInfo));

        $map = [
            ['durian.op', 1, $cashOp],
            ['doctrine', 1, $doctrine]
        ];

        $container = $this->getMockBuilder('\Symfony\Component\DependencyInjection\ContainerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $container->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($map));

        $cash = new Cash($user, $currency);
        $em->persist($cash);
        $em->flush();

        $this->userManager->setContainer($container);
        $this->userManager->remove($user);
    }

    /**
     * 測試刪除帳號時mysql中cash的預扣與redis不同步
     */
    public function testRemoveCashUnsynchronisedPreSub()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Can not remove user due to unsynchronised cash data',
            150010047
        );

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $doctrine = $this->getContainer()->get('doctrine');

        $userRepo = $em->getRepository('BBDurianBundle:User');
        $user = $userRepo->findOneByUsername('domain20m');

        $currency = 156; // CNY

        $cashInfo = [
            'balance' => 0,
            'pre_sub' => 10,
            'pre_add' => 0
        ];

        $cashOp = $this->getMockBuilder('BB\DurianBundle\Service\OpService')
            ->disableOriginalConstructor()
            ->setMethods(['getRedisCashBalance'])
            ->getMock();
        $cashOp->expects($this->any())
            ->method('getRedisCashBalance')
            ->will($this->returnValue($cashInfo));

        $map = [
            ['durian.op', 1, $cashOp],
            ['doctrine', 1, $doctrine]
        ];

        $container = $this->getMockBuilder('\Symfony\Component\DependencyInjection\ContainerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $container->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($map));

        $cash = new Cash($user, $currency);
        $em->persist($cash);
        $em->flush();

        $this->userManager->setContainer($container);
        $this->userManager->remove($user);
    }

    /**
     * 測試刪除帳號時mysql中cash的預存與redis不同步
     */
    public function testRemoveCashUnsynchronisedPreAdd()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Can not remove user due to unsynchronised cash data',
            150010047
        );

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $doctrine = $this->getContainer()->get('doctrine');

        $userRepo = $em->getRepository('BBDurianBundle:User');
        $user = $userRepo->findOneByUsername('domain20m');

        $currency = 156; // CNY

        $cashInfo = [
            'balance' => 0,
            'pre_sub' => 0,
            'pre_add' => 10
        ];

        $cashOp = $this->getMockBuilder('BB\DurianBundle\Service\OpService')
            ->disableOriginalConstructor()
            ->setMethods(['getRedisCashBalance'])
            ->getMock();
        $cashOp->expects($this->any())
            ->method('getRedisCashBalance')
            ->will($this->returnValue($cashInfo));

        $map = [
            ['durian.op', 1, $cashOp],
            ['doctrine', 1, $doctrine]
        ];

        $container = $this->getMockBuilder('\Symfony\Component\DependencyInjection\ContainerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $container->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($map));

        $cash = new Cash($user, $currency);
        $em->persist($cash);
        $em->flush();

        $this->userManager->setContainer($container);
        $this->userManager->remove($user);
    }

    /**
     * 測試刪除帳號時mysql中cashfake的餘額與redis不同步
     */
    public function testRemoveCashFakeUnsynchronisedBalance()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Can not remove user due to unsynchronised cashfake data',
            150010048
        );

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $doctrine = $this->getContainer()->get('doctrine');

        $userRepo = $em->getRepository('BBDurianBundle:User');
        $user = $userRepo->findOneByUsername('domain20m');

        $currency = 156; // CNY

        $cashfakeInfo = [
            'balance' => 10,
            'pre_sub' => 0,
            'pre_add' => 0
        ];

        $cashFakeOp = $this->getMockBuilder('BB\DurianBundle\CashFake\CashFakeOperator')
            ->disableOriginalConstructor()
            ->setMethods(['getBalanceByRedis'])
            ->getMock();
        $cashFakeOp->expects($this->any())
            ->method('getBalanceByRedis')
            ->will($this->returnValue($cashfakeInfo));

        $map = [
            ['durian.cashfake_op', 1, $cashFakeOp],
            ['doctrine', 1, $doctrine]
        ];

        $container = $this->getMockBuilder('\Symfony\Component\DependencyInjection\ContainerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $container->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($map));

        $cashFake = new CashFake($user, $currency);
        $em->persist($cashFake);
        $em->flush();

        $this->userManager->setContainer($container);
        $this->userManager->remove($user);
    }

    /**
     * 測試刪除帳號時mysql中cashfake的預扣與redis不同步
     */
    public function testRemoveCashFakeUnsynchronisedPreSub()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Can not remove user due to unsynchronised cashfake data',
            150010048
        );

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $doctrine = $this->getContainer()->get('doctrine');

        $userRepo = $em->getRepository('BBDurianBundle:User');
        $user = $userRepo->findOneByUsername('domain20m');

        $currency = 156; // CNY

        $cashfakeInfo = [
            'balance' => 0,
            'pre_sub' => 10,
            'pre_add' => 0
        ];

        $cashFakeOp = $this->getMockBuilder('BB\DurianBundle\CashFake\CashFakeOperator')
            ->disableOriginalConstructor()
            ->setMethods(['getBalanceByRedis'])
            ->getMock();
        $cashFakeOp->expects($this->any())
            ->method('getBalanceByRedis')
            ->will($this->returnValue($cashfakeInfo));

        $map = [
            ['durian.cashfake_op', 1, $cashFakeOp],
            ['doctrine', 1, $doctrine]
        ];

        $container = $this->getMockBuilder('\Symfony\Component\DependencyInjection\ContainerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $container->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($map));

        $cashFake = new CashFake($user, $currency);
        $em->persist($cashFake);
        $em->flush();

        $this->userManager->setContainer($container);
        $this->userManager->remove($user);
    }

    /**
     * 測試刪除帳號時mysql中cashfake的預存與redis不同步
     */
    public function testRemoveCashFakeUnsynchronisedPreAdd()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Can not remove user due to unsynchronised cashfake data',
            150010048
        );

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $doctrine = $this->getContainer()->get('doctrine');

        $userRepo = $em->getRepository('BBDurianBundle:User');
        $user = $userRepo->findOneByUsername('domain20m');

        $currency = 156; // CNY

        $cashfakeInfo = [
            'balance' => 0,
            'pre_sub' => 0,
            'pre_add' => 10
        ];

        $cashFakeOp = $this->getMockBuilder('BB\DurianBundle\CashFake\CashFakeOperator')
            ->disableOriginalConstructor()
            ->setMethods(['getBalanceByRedis'])
            ->getMock();
        $cashFakeOp->expects($this->any())
            ->method('getBalanceByRedis')
            ->will($this->returnValue($cashfakeInfo));

        $map = [
            ['durian.cashfake_op', 1, $cashFakeOp],
            ['doctrine', 1, $doctrine]
        ];

        $container = $this->getMockBuilder('\Symfony\Component\DependencyInjection\ContainerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $container->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($map));

        $cashFake = new CashFake($user, $currency);
        $em->persist($cashFake);
        $em->flush();

        $this->userManager->setContainer($container);
        $this->userManager->remove($user);
    }

    /**
     * 測試刪除帳號時mysql中credit的餘額與redis不同步
     */
    public function testRemoveCreditUnsynchronisedBalance()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Can not remove user due to unsynchronised credit data',
            150010050
        );

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $userRepo = $em->getRepository('BBDurianBundle:User');
        $user = $userRepo->findOneByUsername('domain20m');

        $userManager = $this->getMockBuilder('BB\DurianBundle\User\UserManager')
            ->setMethods(['getEntityManager'])
            ->getMock();
        $userManager->expects($this->any())
            ->method('getEntityManager')
            ->will($this->returnValue($em));

        $creditInfo = [
            'balance' => 10,
            'line' => 0,
            'total_line' => 0
        ];

        $creditOp = $this->getMockBuilder('BB\DurianBundle\Service\OpService')
            ->disableOriginalConstructor()
            ->setMethods(['getBalanceByRedis'])
            ->getMock();
        $creditOp->expects($this->any())
            ->method('getBalanceByRedis')
            ->will($this->returnValue($creditInfo));

        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->disableOriginalConstructor()
            ->getMock();
        $container->expects($this->any())
            ->method('get')
            ->will($this->returnValue($creditOp));

        $credit = new Credit($user, 2);

        $em->persist($credit);
        $em->flush();

        $userManager->setContainer($container);
        $userManager->remove($user);
    }

    /**
     * 測試刪除帳號時mysql中credit的信用額度與redis不同步
     */
    public function testRemoveCreditUnsynchronisedLine()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Can not remove user due to unsynchronised credit data',
            150010050
        );

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $userRepo = $em->getRepository('BBDurianBundle:User');
        $user = $userRepo->findOneByUsername('domain20m');

        $userManager = $this->getMockBuilder('BB\DurianBundle\User\UserManager')
            ->setMethods(['getEntityManager'])
            ->getMock();
        $userManager->expects($this->any())
            ->method('getEntityManager')
            ->will($this->returnValue($em));

        $creditInfo = [
            'balance' => 0,
            'line' => 10,
            'total_line' => 0
        ];

        $creditOp = $this->getMockBuilder('BB\DurianBundle\Service\OpService')
            ->disableOriginalConstructor()
            ->setMethods(['getBalanceByRedis'])
            ->getMock();
        $creditOp->expects($this->any())
            ->method('getBalanceByRedis')
            ->will($this->returnValue($creditInfo));

        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->disableOriginalConstructor()
            ->getMock();
        $container->expects($this->any())
            ->method('get')
            ->will($this->returnValue($creditOp));

        $credit = new Credit($user, 2);

        $em->persist($credit);
        $em->flush();

        $userManager->setContainer($container);
        $userManager->remove($user);
    }

    /**
     * 測試刪除帳號時mysql中credit的下層信用額度總和與redis不同步
     */
    public function testRemoveCreditUnsynchronisedTotalLine()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Can not remove user due to unsynchronised credit data',
            150010050
        );

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $userRepo = $em->getRepository('BBDurianBundle:User');
        $user = $userRepo->findOneByUsername('domain20m');

        $userManager = $this->getMockBuilder('BB\DurianBundle\User\UserManager')
            ->setMethods(['getEntityManager'])
            ->getMock();
        $userManager->expects($this->any())
            ->method('getEntityManager')
            ->will($this->returnValue($em));

        $creditInfo = [
            'balance' => 0,
            'line' => 0,
            'total_line' => 10
        ];

        $creditOp = $this->getMockBuilder('BB\DurianBundle\Service\OpService')
            ->disableOriginalConstructor()
            ->setMethods(['getBalanceByRedis'])
            ->getMock();
        $creditOp->expects($this->any())
            ->method('getBalanceByRedis')
            ->will($this->returnValue($creditInfo));

        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->disableOriginalConstructor()
            ->getMock();
        $container->expects($this->any())
            ->method('get')
            ->will($this->returnValue($creditOp));

        $credit = new Credit($user, 2);

        $em->persist($credit);
        $em->flush();

        $userManager->setContainer($container);
        $userManager->remove($user);
    }

    /**
     * 測試刪除廳主帳號
     */
    public function testRemoveDomain()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $userRepo = $em->getRepository('BBDurianBundle:User');
        $vendor = $em->find('BBDurianBundle:OauthVendor', 1); //weibo

        $user = new User();
        $user->setId(11)
            ->setSub(false)
            ->setDomain(2)
            ->setRole(7)
            ->setUsername('testsub')
            ->setAlias('testsub')
            ->setPassword('123456');

        $detail = new UserDetail($user);
        $email = new UserEmail($user);
        $email->setEmail('');
        $password = new UserPassword($user);
        $password->setHash('');

        $em->persist($user);
        $em->persist($detail);
        $em->persist($email);
        $em->persist($password);
        $em->flush();

        $domainCurrency = new DomainCurrency($user, 156);
        $config = new DomainConfig($user, 'testsub', 'test');
        $totalTest = new DomainTotalTest($user->getId());
        $shareLimit = new ShareLimit($user, 1);
        $payway = new UserPayway($user);
        $oauthUserBinding = new OauthUserBinding('11', $vendor, '123456');

        $em->persist($domainCurrency);
        $em->persist($totalTest);
        $em->persist($shareLimit);
        $em->persist($payway);
        $em->persist($oauthUserBinding);
        $em->flush();

        $emShare->persist($config);
        $emShare->flush();

        $shareLimitNext = new ShareLimitNext($user, 1);
        $em->persist($shareLimitNext);
        $em->flush();

        //刪除廳主
        $this->userManager->remove($user);

        $this->assertEquals(0, $userRepo->countChildOf($user, ['criteria' => ['sub' => 0]]));

        $em->flush();
        $emShare->flush();

        //檢查刪除廳主會順便刪除登入尾碼、幣別、佔成、預改佔成、交易方式、Oauth使用者綁定資料、測試帳號數量的紀錄
        $config = $emShare->getRepository('BBDurianBundle:DomainConfig')
            ->find($user->getId());
        $totalTest = $em->getRepository('BBDurianBundle:DomainTotalTest')
            ->find($user->getId());
        $domainCurrency = $em->getRepository('BBDurianBundle:DomainCurrency')
            ->findBy(['domain' => $user->getId()]);
        $shareLimit = $em->getRepository('BBDurianBundle:ShareLimit')
            ->findBy(['user' => $user->getId()]);
        $shareLimitNext = $em->getRepository('BBDurianBundle:ShareLimitNext')
            ->findBy(['user' => $user->getId()]);
        $userPayway = $em->getRepository('BBDurianBundle:UserPayway')
            ->findBy(['userId' => $user->getId()]);
        $oauthUserBinding = $em->getRepository('BBDurianBundle:OauthUserBinding')
            ->findBy(['userId' => $user->getId()]);

        $this->assertTrue($config->isRemoved());
        $this->assertTrue($totalTest->isRemoved());
        $this->assertTrue($domainCurrency[0]->isRemoved());
        $this->assertEmpty($shareLimit);
        $this->assertEmpty($shareLimitNext);
        $this->assertEmpty($userPayway);
        $this->assertEmpty($oauthUserBinding);
        $this->assertNull($em->find('BBDurianBundle:User', 11));
    }

    /**
     * 測試刪除帳號時cash餘額不為零
     */
    public function testRemoveBalanceIsNotZero()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Can not remove user when user has cash',
            150010019
        );

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $userRepo = $em->getRepository('BBDurianBundle:User');
        $user = $userRepo->findOneByUsername('tester');

        $cash = new Cash($user, 156);
        $cash->setBalance(10);

        $em->persist($cash);
        $em->flush();

        $this->userManager->remove($user);
    }

    /**
     * 測試刪除帳號時cash預扣不為零
     */
    public function testRemovePreSubIsNotZero()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Can not remove user when user has trans cash',
            150010123
        );

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $userRepo = $em->getRepository('BBDurianBundle:User');
        $user = $userRepo->findOneByUsername('tester');

        $cash = new Cash($user, 156);
        $cash->addPreSub(10);

        $em->persist($cash);
        $em->flush();

        $this->userManager->remove($user);
    }

    /**
     * 測試刪除帳號時cash預存不為零
     */
    public function testRemovePreAddIsNotZero()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Can not remove user when user has trans cash',
            150010123
        );

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $userRepo = $em->getRepository('BBDurianBundle:User');
        $user = $userRepo->findOneByUsername('tester');

        $cash = new Cash($user, 156);
        $cash->addPreAdd(10);

        $em->persist($cash);
        $em->flush();

        $this->userManager->remove($user);
    }

    /**
     * 測試刪除帳號資料(假現金餘額為零時)
     */
    public function testRemoveWhenCashFakeIsZero()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redisWallet = $this->getContainer()->get('snc_redis.wallet4');

        $redisWallet->hset('cash_fake_balance_8_156', 'balance', 0);

        $user = $em->find('BBDurianBundle:User', 8);
        $user->getCashFake()->setBalance(0);

        $this->userManager->remove($user);
        $em->flush();
        $em->clear();

        //資料是否備份
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $removedUser = $emShare->find('BBDurianBundle:RemovedUser', 8);
        $this->assertInstanceOf('BB\DurianBundle\Entity\RemovedUser', $removedUser);

        $this->assertFalse($redisWallet->exists('cash_fake_balance_8_156'));
    }

    /**
     * 測試新增刪除子帳號資料size行為是否正確
     */
    public function testRemoveSubUserSizeBehavior()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $parent = $em->find('BBDurianBundle:User', 7);

        $user = new User();
        $user->setId(11)
             ->setParent($parent)
             ->setSub(true)
             ->setDomain(2)
             ->setUsername('testsub')
             ->setAlias('testsub')
             ->setPassword('123456');
        $detail = new UserDetail($user);
        $email = new UserEmail($user);
        $email->setEmail('');
        $password = new UserPassword($user);
        $password->setHash('');
        $parent->addSize();

        $em->persist($user);
        $em->persist($detail);
        $em->persist($email);
        $em->persist($password);
        $em->flush();

        $this->userManager->remove($user);

        $em->flush();

        //是否刪除
        $entity = $em->find('BBDurianBundle:User', 11);
        $this->assertNull($entity);
    }

    /**
     * 測試取在durianBB環境取得最大層級回傳6
     */
    public function testRepoGetMaxLevelInDurianBB()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $userRepo = $em->getRepository('BBDurianBundle:User');

        $mockPltform = $this->getMockBuilder('Doctrine\DBAL\Platforms\SqlitePlatform')
            ->disableOriginalConstructor()
            ->setMethods(['getName'])
            ->getMock();

        $mockPltform->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('mysql'));

        $mockConn = $this->getMockBuilder('Doctrine\DBAL\Connections\MasterSlaveConnection')
            ->disableOriginalConstructor()
            ->setMethods(['getDatabase', 'getDatabasePlatform'])
            ->getMock();

        $mockConn->expects($this->any())
            ->method('getDatabase')
            ->will($this->returnValue('durian_bb'));

        $mockConn->expects($this->any())
            ->method('getDatabasePlatform')
            ->will($this->returnValue($mockPltform));

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getConnection'])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($mockConn));

        $refRepo = new \ReflectionClass($userRepo);

        $property = $refRepo->getProperty('_em');
        $property->setAccessible(true);
        $property->setValue($userRepo, $mockEm);

        $method = $refRepo->getMethod('getMaxLevel');
        $method->setAccessible(true);
        $lvl = $method->invoke($userRepo);

        $this->assertEquals(6, $lvl);
    }

    /**
     * 測試刪除測試會員時,domain_total_test會減1
     */
    public function testRemoveTestUserByRole1()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $userRepo = $em->getRepository('BBDurianBundle:User');
        $user = $userRepo->findOneByUsername('tester');
        $user->setTest(true);

        $totalTest = new DomainTotalTest(2);
        $totalTest->setTotalTest(5);

        $em->persist($totalTest);
        $em->flush();

        $this->userManager->remove($user);

        // 檢查domain_total_test是否減1
        $totalTest = $em->find('BBDurianBundle:DomainTotalTest', 2);
        $this->assertEquals(4, $totalTest->getTotalTest());
    }

    /**
     * 測試刪除測試會員時不進api transfer in out
     */
    public function testRemoveNotInApiTransferInOut()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->find('BBDurianBundle:User', 7);
        $user->setDomain(1);

        $user = $em->find('BBDurianBundle:User', 8);
        $user->setDomain(1);

        $em->flush();

        $this->userManager->remove($user);

        $redis = $this->getContainer()->get('snc_redis.default');
        $queueName = 'cash_fake_api_transfer_in_out_queue';
        $this->assertEquals(0, $redis->llen($queueName));
    }
}
