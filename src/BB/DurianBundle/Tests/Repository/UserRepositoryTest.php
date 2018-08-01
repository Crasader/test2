<?php

namespace BB\DurianBundle\Tests\Repository;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\UserAncestor;
use BB\DurianBundle\Entity\UserHasDepositWithdraw;

class UserRepositoryTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserPaywayData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserDetailData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserEmailData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankInfoData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankCurrencyData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainBankData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserPaywayData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserHasDepositWithdrawData'
        ];
        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemovedUserData'
        ];
        $this->loadFixtures($classnames, 'share');
    }

    /**
     * 測試體系查詢專用的模糊搜尋
     */
    public function testByFuzzyName()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:User');

        $output = $repo->findByFuzzyName('tester', 2, 0, 0, 20);
        $total = $repo->countByFuzzyName('tester', 2, 0);

        $this->assertEquals('tester', $output[0]->getUsername());
        $this->assertEquals(1, $total);
    }

    /**
     * 測試回傳下層帳號
     */
    public function testChildUser()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:User');

        $now = new \Datetime('now');
        $parent = $em->find('BBDurianBundle:User', 2);
        $params = [
            'depth' => 6,
            'criteria' => ['bankrupt' => 0],
            'order_by' => ['username' => 'ASC'],
            'first_result' => 0,
            'max_results' => 20
        ];
        $searchSet = $this->makeSearch(['username'], ['company']);
        $searchPeriod = [
            'startAt' => $parent->getCreatedAt(),
            'endAt' => $now,
            'modifiedStartAt' => $parent->getCreatedAt(),
            'modifiedEndAt' => $now
        ];

        // 不帶入parent
        $total = $repo->countChildOf(null, $params, $searchSet, $searchPeriod);
        $output = $repo->findChildBy(null, $params, $searchSet, $searchPeriod);

        $this->assertEquals('company', $output[0]->getUsername());
        $this->assertEquals(1, $total);

        // table為User
        $searchSet = $this->makeSearch(['username'], ['%tester']);
        $total = $repo->countChildOf($parent, $params, $searchSet, $searchPeriod);
        $output = $repo->findChildBy($parent, $params, $searchSet, $searchPeriod);

        $this->assertEquals(8, $output[0]->getId());
        $this->assertEquals(7, $output[0]->getParent()->getId());
        $this->assertEquals('tester', $output[0]->getUsername());
        $this->assertEquals(1, $total);

        // table為UserDetail
        $searchSet = $this->makeSearch(['passport'], ['PA123456']);
        $total = $repo->countChildOf($parent, $params, $searchSet, $searchPeriod);
        $output = $repo->findChildBy($parent, $params, $searchSet, $searchPeriod);

        $this->assertEquals(1, $total);
        $this->assertEquals('tester', $output[0]->getUsername());

        // table為UserEmail
        $searchSet = $this->makeSearch(['email'], ['Davinci@chinatown.com']);
        $total = $repo->countChildOf($parent, $params, $searchSet, $searchPeriod);
        $output = $repo->findChildBy($parent, $params, $searchSet, $searchPeriod);

        $this->assertEquals(1, $total);
        $this->assertEquals('tester', $output[0]->getUsername());

        // table為Bank
        $searchSet = $this->makeSearch(['account'], ['4']);
        $total = $repo->countChildOf($parent, $params, $searchSet, $searchPeriod);
        $output = $repo->findChildBy($parent, $params, $searchSet, $searchPeriod);

        $this->assertEquals(1, $total);
        $this->assertEquals('tester', $output[0]->getUsername());

        // table為UserHasDepositWithdraw
        $searchSet = $this->makeSearch(['deposit'], ['0']);
        $total = $repo->countChildOf($parent, $params, $searchSet, $searchPeriod);
        $output = $repo->findChildBy($parent, $params, $searchSet, $searchPeriod);

        $this->assertEquals(1, $total);
        $this->assertEquals('tester', $output[0]->getUsername());
    }

    /**
     * 測試帶入無效depth，回傳例外訊息
     */
    public function testChildUserByMinusDepth()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:User');

        $now = new \Datetime('now');
        $parent = $em->find('BBDurianBundle:User', 2);
        $params = [
            'depth' => -1,
            'criteria' => ['bankrupt' => 0],
            'order_by' => ['username' => 'ASC'],
            'first_result' => 0,
            'max_results' => 20
        ];
        $searchSet = $this->makeSearch(['username'], ['company']);
        $searchPeriod = [
            'startAt' => $parent->getCreatedAt(),
            'endAt' => $now,
            'modifiedStartAt' => $parent->getCreatedAt(),
            'modifiedEndAt' => $now
        ];

        // 預期的錯誤訊息
        $this->setExpectedException(
            'InvalidArgumentException',
            'If you want to get child from any depth, Set parameter depth = null, thx.',
            150010028
        );

        $repo->findChildBy($parent, $params, $searchSet, $searchPeriod);
    }

    /**
     * 測試回傳所有子帳號陣列
     */
    public function testChildUserInArray()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:User');

        $now = new \Datetime('now');
        $parent = $em->find('BBDurianBundle:User', 2);
        $params = [
            'depth' => null,
            'criteria' => [
                'sub'   => 0,
                'block' => 0,
                'bankrupt' => 0
            ],
            'order_by' => ['id' => 'ASC'],
            'first_result' => 0,
            'max_results' => 20
        ];
        $searchSet = $this->makeSearch(['username'], ['company']);
        $searchPeriod = [
            'startAt' => $parent->getCreatedAt(),
            'endAt' => $now,
            'modifiedStartAt' => $parent->getCreatedAt(),
            'modifiedEndAt' => $now
        ];

        // 不帶入parent
        $output = $repo->findChildArrayBy(null, $params, $searchSet, $searchPeriod);

        $this->assertEquals('company', $output[0]['username']);

        // table為User，回傳子帳號陣列
        $searchSet = $this->makeSearch(['username'], ['%tester']);
        $output = $repo->findChildArrayBy($parent, $params, $searchSet, $searchPeriod);

        $this->assertEquals(3, $output[0]['id']);
        $this->assertEquals('vtester', $output[0]['username']);
        $this->assertEquals(4, $output[1]['id']);
        $this->assertEquals('wtester', $output[1]['username']);
        $this->assertEquals(5, $output[2]['id']);
        $this->assertEquals('xtester', $output[2]['username']);
        $this->assertEquals(6, $output[3]['id']);
        $this->assertEquals('ytester', $output[3]['username']);

        // 下層搜尋
        $depthOne = $params;
        $depthOne['depth'] = 1;
        $outputByDepth = $repo->findChildArrayBy($parent, $depthOne, $searchSet, $searchPeriod);

        $this->assertEquals(3, $outputByDepth[0]['id']);
        $this->assertEquals('vtester', $outputByDepth[0]['username']);

        // 檢驗user的父帳號id
        $fields = ['parentId', 'username'];
        $outputId = $repo->findChildArrayBy($parent, $params, $searchSet, $searchPeriod, $fields);

        $this->assertEquals(3, $outputId[0]['id']);
        $this->assertEquals(2, $outputId[0]['parent_id']);
        $this->assertEquals(4, $outputId[1]['id']);
        $this->assertEquals(3, $outputId[1]['parent_id']);
        $this->assertEquals(5, $outputId[2]['id']);
        $this->assertEquals(4, $outputId[2]['parent_id']);

        // table為UserDetail
        $searchSet = $this->makeSearch(['passport'], ['PA123456']);
        $output = $repo->findChildArrayBy($parent, $params, $searchSet, $searchPeriod);

        $this->assertEquals('tester', $output[0]['username']);

        // table為Bank
        $searchSet = $this->makeSearch(['account'], ['4']);
        $output = $repo->findChildArrayBy($parent, $params, $searchSet, $searchPeriod);

        $this->assertEquals('tester', $output[0]['username']);

        // table為UserHasDepositWithdraw
        $searchSet = $this->makeSearch(['deposit'], ['0']);
        $output = $repo->findChildArrayBy($parent, $params, $searchSet, $searchPeriod);

        $this->assertEquals('tester', $output[0]['username']);

        // depth為負數
        $params['depth'] = -1;

        $this->setExpectedException(
            'InvalidArgumentException',
            'If you want to get child from any depth, Set parameter depth = null, thx.',
            150010028
        );

        $repo->findChildArrayBy($parent, $params, $searchSet, $searchPeriod);
    }

    /**
     * 測試停啟用使用者
     */
    public function testEnableAndDisableUser()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:User');

        // 停用使用者
        $parent = $em->find('BBDurianBundle:User', 2);
        $lastModifiedAt = $parent->getModifiedAt()->getTimeStamp();
        $parent->disable();
        $repo->disableAllChild($parent);

        // 取得所有下層
        $params = [
            'depth' => null,
            'criteria' => [],
            'order_by' => ['id' => 'ASC']
        ];
        $subUsers = $repo->findChildBy($parent, $params);

        //測試下層是否一起停用
        foreach ($subUsers as $user) {
            $this->assertFalse($user->isEnabled());
            $this->assertGreaterThanOrEqual($lastModifiedAt, $user->getModifiedAt()->getTimeStamp());
        }

        //測試非下層的是否有被停用
        $user = $repo->findBy(['id' => 9]);
        $this->assertTrue($user[0]->isEnabled());
        $user = $repo->findBy(['id' => 10]);
        $this->assertTrue($user[0]->isEnabled());

        // 啟用使用者
        $parent->enable();
        $repo->enableAllSub($parent);

        // 確認有無啟用成功
        $this->assertFalse($subUsers[0]->isEnabled());
        $this->assertFalse($subUsers[5]->isEnabled());
    }

    /**
     * 測試更改Ancestor
     */
    public function testChangeAncestor()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:User');

        $newParent = $em->find('BBDurianBundle:User', 2);
        $oldParent = $em->find('BBDurianBundle:User', 9);
        $user = $em->find('BBDurianBundle:User', 10);

        // 轉移前
        $uaRepo = $em->getRepository('BBDurianBundle:UserAncestor');
        $uas = $uaRepo->findBy(['user' => $user->getId()]);

        $this->assertEquals($oldParent, $user->getParent());
        $this->assertEquals(9, $uas[0]->getAncestor()->getId());
        $this->assertEquals(1, $uas[0]->getDepth());

        // 轉移
        $user->setParent($newParent);
        $repo->changeAncestor($newParent, $oldParent, $user);

        // 測試轉移後UserAncestor 是否正確
        $uas = $uaRepo->findBy(['user' => $user->getId()]);

        $this->assertNotNull($uas);
        $this->assertEquals(1, count($uas));
        $this->assertEquals(2, $uas[0]->getAncestor()->getId());
        $this->assertEquals(1, $uas[0]->getDepth());
    }

    /**
     * 刪除使用者的Ancestor資料
     */
    public function testRemoveAncestorBy()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:User');

        $user = $em->find('BBDurianBundle:User', 10);
        $uaRepo = $em->getRepository('BBDurianBundle:UserAncestor');

        // 測試刪除使用者的Ancestor資料
        $repo->removeAncestorBy($user);
        $uas = $uaRepo->findBy(['user' => $user->getId()]);

        $this->assertEmpty($uas);
    }

    /**
     * 測試使用id陣列回傳使用者 (object)
     */
    public function testGetMultiUserByIds()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:User');

        // 取得id陣列
        $user = $em->find('BBDurianBundle:User', 7);
        $ids = $user->getAllParentsId();

        $this->assertEquals(5, count($ids));
        $this->assertEquals([6, 5, 4, 3, 2], $ids);

        // 測試不帶入id，回傳空值
        $this->assertEquals([], $repo->getMultiUserByIds([]));

        // 測試回傳多位使用者
        foreach ($repo->getMultiUserByIds($ids) as $user) {
            $outputUsersId[] = $user->getId();
        }

        $this->assertEmpty(array_diff($outputUsersId, $ids));
    }

    /**
     * 測試使用id陣列回傳使用者 (array)
     */
    public function testGetUserArrayByIds()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:User');

        // 取得id陣列
        $user = $em->find('BBDurianBundle:User', 7);
        $ids = $user->getAllParentsId();

        $this->assertEquals(5, count($ids));
        $this->assertEquals([6, 5, 4, 3, 2], $ids);

        // 測試回傳使用者陣列
        foreach ($repo->getUserArrayByIds($ids) as $user) {
            $output[] = $user['id'];
        }

        $this->assertEmpty(array_diff($output, $ids));
    }

    /**
     * 測試所有子層註記或取消為測試帳號
     */
    public function testSetAllChildTestOnAndTestOff()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:User');

        // 測試資料
        $parent = $em->find('BBDurianBundle:User', 2);
        $parent->setTest(true);

        // 註記為測試
        $repo->setTestUserOnAllChild($parent);

        // 取得所有下層
        $params = [
            'depth' => null,
            'criteria' => [],
            'order_by' => ['id' => 'ASC']
        ];
        $subUsers = $repo->findChildBy($parent, $params);

        // 測試下層
        foreach ($subUsers as $user) {
            $this->assertTrue($user->isTest());
        }

        // 測試非下層的是否有被註記為測試帳號
        $user = $em->find('BBDurianBundle:User', 9);
        $this->assertFalse($user->isTest());

        $em->clear();

        // 所有子層取消測試的註記
        $parent = $em->find('BBDurianBundle:User', 2);

        $repo->setTestUserOffAllChild($parent);

        // 檢查下層
        $subUsers = $repo->findChildBy($parent, $params);
        foreach ($subUsers as $user) {
            $this->assertFalse($user->isTest());
        }
    }

    /**
     * 測試取得所有測試帳號的Id，並將id以陣列方式回傳
     */
    public function testGetAllChildTestUserIdArray()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:User');

        // 設定為測試帳號
        $parent = $em->find('BBDurianBundle:User', 2);
        $parent->setTest(true);

        $user = new User();
        $user->setId(11);
        $user->setParent($parent);
        $user->setUsername('testOff');
        $user->setAlias('testOff');
        $user->setPassword('');
        $user->setDomain(2);
        $user->setTest(true);
        $em->persist($user);

        $ua = new UserAncestor($user, $parent, 1);

        $em->persist($ua);
        $em->flush();

        $output = $repo->getAllChildTestUserIdArray($parent->getId());

        $this->assertEquals(11, $output[0]['id']);
    }

    /**
     * 測試一次取得User的所有上層
     */
    public function testGetAllParentsAtOnce()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:User');

        // 不代入user，回傳空陣列
        $ids = [];
        $output = $repo->getAllParentsAtOnce([]);

        foreach ($output as $user) {
            $ids[] = $user->getId();
        }

        $this->assertEmpty($ids);

        // 帶入user
        $user = $em->find('BBDurianBundle:User', 5);
        $parentIds = $user->getAllParentsId();

        $output = $repo->getAllParentsAtOnce([$user]);

        foreach ($output as $user) {
            $ids[] = $user->getId();
        }

        $this->assertEmpty(array_diff($parentIds, $ids));
    }

    /**
     * 測試取得所有廳主的Id，並將id做為key以陣列方式回傳
     */
    public function testGetAllDomainId()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:User');

        $user = new User();
        $user->setId(11);
        $user->setUsername('testOff');
        $user->setAlias('testOff');
        $user->setPassword('');
        $user->setDomain(2);
        $em->persist($user);
        $em->flush();

        $output = $repo->getDomainIdArrayAsKey(1);

        // user的id為key
        $this->assertTrue($user->isEnabled());
        $this->assertFalse($user->hasParent());
        $this->assertNotNull($output[11]);

    }

    /**
     * 測試取得使用者的階層
     */
    public function testGetLevelBy()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:User');

        // 不代入user
        $output = $repo->getLevel(null);

        $this->assertNull($output);

        // 回傳最大層數
        $user = $em->find('BBDurianBundle:User', 2);
        $output = $repo->getLevel($user);

        $this->assertFalse($user->hasParent());
        $this->assertEquals(7, $output);

        // 會員level為1
        $user = $em->find('BBDurianBundle:User', 8);
        $output = $repo->getLevel($user);

        $this->assertEquals(1, $output);
    }

    /**
     * 測試取得指定廳某時點修改會員資料
     */
    public function testGetModifiedUserByDomain()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:User');

        $beginAt = '2010-01-01 00:00:00';
        $limit = ['first' => 0, 'max' => 20];

        // 取得會員資料
        $output = $repo->getModifiedUserByDomain(2, $beginAt, $limit);

        $user = $output[12]->toArray();
        $userDetail = $output[13]->toArray();
        $userEmail = $output[14]->toArray();

        // 比對$output[8](user), $output[9](userDetail)
        $this->assertEquals($user['id'], $userDetail['user_id']);
        $this->assertEquals('ytester', $user['username']);
        $this->assertEquals('ytester', $user['alias']);
        $this->assertEquals('Davinci@chinatown.com', $userEmail['email']);
        $this->assertEquals('33456785', $userDetail['telephone']);

        // 計算筆數
        $total = $repo->countModifiedUserByDomain(2, $beginAt);

        $this->assertEquals(10, $total);
    }

    /**
     * 測試回傳時間區間內新增會員資料
     */
    public function testGetMemberDetailBy()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:User');

        $startAt = new \DateTime('1911-11-11 12:34:56');
        $startAt->format(\DateTime::ISO8601);
        $endAt = new \DateTime('now');
        $endAt->format(\DateTime::ISO8601);

        $output = $repo->getMemberDetail(2, $startAt, $endAt, 0, 20);

        $this->assertEquals('tester', $output[0]['username']);
        $this->assertEquals('達文西', $output[0]['nameReal']);
        $this->assertEquals('3345678', $output[0]['telephone']);
        $this->assertEquals('PA123456', $output[0]['passport']);
        $this->assertEquals('Davinci@chinatown.com', $output[0]['email']);
        $this->assertEquals('485163154787', $output[0]['qqNum']);
        $this->assertEquals('Republic of China', $output[0]['country']);
        $this->assertEquals('Hello Durian', $output[0]['note']);

        // 計算筆數
        $total = $repo->countMemberDetail(2, $startAt, $endAt);

        $this->assertEquals(2, $total);
        $this->assertEquals(2, count($output));
    }

    /**
     * 測試由會員ID回傳所有上層username
     *
     * @dataProvider userProvider
     */
    public function testGetAllParentUsernameById($uid, $parentsName)
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:User');

        $user = $em->find('BBDurianBundle:User', $uid);
        $parents=[];

        foreach ($user->getAllParents() as $key) {
            $parents[] = $key->getUsername();
        }

        // 檢查上層
        foreach ($parentsName as $key) {
            $this->assertTrue(in_array($key, $parents));
        }

        $output = $repo->getMemberAllParentUsername([$uid]);

        $this->assertEquals($parentsName[3], $output[$uid]['sc']);
        $this->assertEquals($parentsName[2], $output[$uid]['co']);
        $this->assertEquals($parentsName[1], $output[$uid]['sa']);
        $this->assertEquals($parentsName[0], $output[$uid]['ag']);
    }

    /**
     * 會員編號和所有上層username
     *
     * @return array
     */
    public function userProvider()
    {
        return [
            ['uid' => 6, ['xtester', 'wtester', 'vtester', 'company']],
            ['uid' => 7, ['ytester', 'xtester', 'wtester', 'vtester']],
            ['uid' => 8, ['ztester', 'ytester', 'xtester', 'wtester']]
        ];

    }

    /**
     * 測試由會員ID回傳所有上層username
     */
    public function testGetAllParentUsernameByNull()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:User');

        // 不代入id
        $output = $repo->getMemberAllParentUsername([]);

        $this->assertEquals(0, count($output));
    }

    /**
     * 測試回傳使用者資料，並整理成 userInfo[userId] 的格式
     */
    public function testGetUserInfoById()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:User');

        $output = $repo->getUserInfoById([2]);

        $this->assertEquals(1, count($output));
        $this->assertEquals(2, $output[2]["user_id"]);
        $this->assertEquals('company', $output[2]["username"]);
        $this->assertEquals(2, $output[2]["domain"]);
        $this->assertEquals('company', $output[2]["domain_alias"]);
    }

    /**
     * 測試計算使用者啟用的下層數量
     */
    public function testCountEnabledChildOfUser()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:User');

        $user = $em->find('BBDurianBundle:User', 2);

        $output = $repo->countEnabledChildOfUser($user);

        $this->assertEquals(9, $output);

        // 停用一個下層使用者
        $childUser = $em->find('BBDurianBundle:User', 50);
        $childUser->disable();
        $em->flush();

        $output = $repo->countEnabledChildOfUser($user);

        $this->assertEquals(8, $output);
    }

    /**
     * 測試根據廳與使用者帳號，回傳使用者id
     */
    public function testGetUserIdsByUsername()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:User');

        $user = $em->find('BBDurianBundle:User', 2);

        $output = $repo->getUserIdsByUsername($user->getDomain(), [$user->getUsername()]);

        $this->assertEquals(2, $user->getDomain());
        $this->assertEquals('company', $user->getUsername());
        $this->assertEquals($user->getId(), $output[0]['id']);
        $this->assertEquals($user->getUsername(), $output[0]['username']);
    }

    /**
     * 測試取得Id最大值
     */
    public function testGetMaxId()
    {
        $em = $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:User');

        $maxId = $repo->getMaxId();
        $this->assertEquals(20000000, $maxId);
    }

    /**
     * 測試根據條件回傳指定廳會員
     */
    public function testGetUserByDomain()
    {
        $em = $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:User');

        $user = $em->find('BBDurianBundle:User', 8);
        $date = new \Datetime('2015-01-02 00:00:00');
        $date->format(\DateTime::ISO8601);

        $user->setCreatedAt($date);
        $em->persist($user);

        $depositWithdraw = $em->find('BBDurianBundle:UserHasDepositWithdraw', 8);
        $depositWithdraw->setDeposit(true);
        $depositWithdraw->setWithdraw(false);
        $em->flush();

        $startAt = new \DateTime('2015-01-01 00:00:00');
        $startAt->format(\DateTime::ISO8601);
        $endAt = new \DateTime('2015-01-03 00:00:00');
        $endAt->format(\DateTime::ISO8601);

        $usernames = ['tester'];

        $orderBy = ['user' => 'ASC'];

        $criteria = [
            'domain' => 3,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'usernames' => $usernames,
            'order_by' => $orderBy,
            'deposit' => 1,
            'withdraw' => 0
        ];

        $limit = [
            'first_result' => 0,
            'max_results' => 20
        ];

        $output = $repo->getUserByDomain($criteria, $orderBy, $limit);

        $this->assertEquals(8, $output[0]['id']);

        // 測試資料筆數
        $total = $repo->countUserByDomain($criteria, $orderBy, $limit);

        $this->assertEquals(1, $total);
    }

    /**
     * 產生搜尋資訊
     *
     * @param array $searchField 搜尋欄位
     * @param array $searchValue 搜尋資料
     */
    private function makeSearch(array $searchFields, array $searchValues)
    {
        $currencyOperator = $this->getContainer()->get('durian.currency');

        $searchSet = [];
        $userFields = [
            'username',
            'alias',
            'currency',
            'err_num',
            'last_bank'
        ];
        $bankFields = [
            'account'
        ];
        $detailFields = [
            'name_real',
            'name_english',
            'passport',
            'identity_card',
            'driver_license',
            'insurance_card',
            'health_card',
            'telephone',
            'qq_num'
        ];
        $emailFields = ['email'];
        $depositWithdrawFields = [
            'deposit',
            'withdraw'
        ];

        foreach ($searchFields as $index => $searchField) {
            if (in_array($searchField, $userFields)) {
                $table = 'User';
            }

            if (in_array($searchField, $bankFields)) {
                $table = 'Bank';
            }

            if (in_array($searchField, $detailFields)) {
                $table = 'UserDetail';
            }

            if (in_array($searchField, $emailFields)) {
                $table = 'UserEmail';
            }

            if (in_array($searchField, $depositWithdrawFields)) {
                $table = 'UserHasDepositWithdraw';
            }

            if ($searchField == 'currency') {
                $searchValues[$index] = $currencyOperator->getMappedNum($searchValues[$index]);
            }

            $searchSet[$table][] = [
                'field' => \Doctrine\Common\Util\Inflector::camelize($searchField),
                'value' => $searchValues[$index],
            ];
        }

        return $searchSet;
    }

    /**
     * 測試計算廳指定時間後未登入總會員數
     */
    public function testCountNotLogin()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:User');

        $user = $em->find('BBDurianBundle:User', 8);

        $user->setLastLogin(new \DateTime('2013-1-1 11:11:11'));
        $em->flush();

        $output = $repo->countNotLogin(2, '2011-12-1 11:10:11');

        $this->assertEquals(1, $output);
    }
}
