<?php

namespace BB\DurianBundle\Tests\Repository;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class UserDetailRepositoryTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserDetailData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserEmailData'
        ];

        $this->loadFixtures($classnames);
    }

    /**
     * 測試根據模糊搜尋條件回傳使用者詳細資料
     */
    public function testFindByFuzzyParameter()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:UserDetail');

        $parent = null;
        $depth = null;
        $userCriteria = [];
        $detailCriteria = [];
        $account = '6221386170003601228';
        $orderBy = ['user' => 'desc'];
        $firstResult = 0;
        $maxResults = 1;
        $fields = ['email', 'name_real', 'name_chinese', 'birthday'];

        $ret = $repo->findByFuzzyParameter(
            $parent,
            $depth,
            $userCriteria,
            $detailCriteria,
            $account,
            $orderBy,
            $firstResult,
            $maxResults,
            $fields
        );

        $this->assertEquals(8, $ret[0]['user_id']);
        $this->assertEquals('Davinci@chinatown.com', $ret[0]['email']);
        $this->assertEquals('達文西', $ret[0]['name_real']);
        $this->assertEquals('甲級情報員', $ret[0]['name_chinese']);
        $this->assertEquals('2000-10-10', $ret[0]['birthday']);
    }

    /**
     * 測試根據模糊搜尋條件回傳使用者詳細資料，depth帶入負數產生例外
     */
    public function testFindByFuzzyParameterWithNegativeDepthException()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'If you want to get child from any depth, Set parameter depth = null, thx.',
            150090014
        );

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:UserDetail');

        $parent = null;
        $depth = -2;

        $ret = $repo->findByFuzzyParameter(
            $parent,
            $depth
        );
    }

    /**
     * 測試根據模糊搜尋條件回傳使用者詳細資料數量
     */
    public function testCountByFuzzyParameter()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:UserDetail');

        $parent = null;
        $depth = null;
        $userCriteria = [];
        $detailCriteria = [];
        $account = '6221386170003601228';

        $ret = $repo->countByFuzzyParameter(
            $parent,
            $depth,
            $userCriteria,
            $detailCriteria,
            $account
        );

        $this->assertEquals(1, $ret);
    }

    /**
     * 測試根據模糊搜尋條件回傳使用者詳細資料當生日為 0000-00-00 00:00:00
     */
    public function testFindByFuzzyParameterWhenBirthdayIsZero()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:UserDetail');

        // 因無法藉由 entity set birthday 為 0000-00-00 00:00:00, 這邊直接下語法修改資料
        $sql = "UPDATE `user_detail` SET birthday = '0000-00-00 00:00:00' WHERE user_id = 8";
        $em->getConnection()->executeUpdate($sql);

        $parent = null;
        $depth = null;
        $userCriteria = [];
        $detailCriteria = [];
        $account = '6221386170003601228';
        $orderBy = ['user' => 'desc'];
        $firstResult = 0;
        $maxResults = 1;
        $fields = ['birthday'];

        $ret = $repo->findByFuzzyParameter(
            $parent,
            $depth,
            $userCriteria,
            $detailCriteria,
            $account,
            $orderBy,
            $firstResult,
            $maxResults,
            $fields
        );

        $this->assertEquals(8, $ret[0]['user_id']);
        $this->assertNull($ret[0]['birthday']);
    }

    /**
     * 測試根據條件回傳所需要的UserDetail欄位資訊當生日為 0000-00-00 00:00:00
     */
    public function testGetSingleUserDetailByWhenBirthdayIsZero()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:UserDetail');

        // 因無法藉由 entity set birthday 為 0000-00-00 00:00:00, 這邊直接下語法修改資料
        $sql = "UPDATE `user_detail` SET birthday = '0000-00-00 00:00:00' WHERE user_id = 8";
        $em->getConnection()->executeUpdate($sql);

        $fields = ['email', 'birthday'];

        $ret = $repo->getSingleUserDetailBy(8, $fields);

        $this->assertEquals(8, $ret['user_id']);
        $this->assertNull($ret['birthday']);
        $this->assertEquals('Davinci@chinatown.com', $ret['email']);

        // 測試錯誤userid回傳狀況
        $ret = $repo->getSingleUserDetailBy(3345678, []);

        $this->assertNull($ret);
    }

    /**
     * 測試取得真實姓名不等於'測試帳號'的測試帳號與數量
     */
    public function testGetTestUserIdWithErrorNameReal()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:UserDetail');

        $detail = $em->find('BBDurianBundle:UserDetail', 8);
        $detail->setNameReal('測試帳號');
        $em->persist($detail);
        $em->flush();

        $idSet = [8, 10];
        $firstResult = 0;
        $maxResults = 1;

        // 測試取得帳號
        $ret = $repo->getTestUserIdWithErrorNameReal($idSet, $firstResult, $maxResults);

        $this->assertEquals('10', $ret[0]['user_id']);
        $this->assertEquals('叮叮說你好', $ret[0]['name_real']);

        // 測試取得數量
        $ret = $repo->countTestUserIdWithErrorNameReal($idSet);

        $this->assertEquals(1, $ret);
    }

    /**
     * 測試藉由使用者id取得詳細資料時，生日為0000-00-00
     */
    public function testGetUserDetailByUserIdWhenBirthdayIsZero()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:UserDetail');

        $detail = $em->find('BBDurianBundle:UserDetail', 8);
        $birthday = new \Datetime('0000-00-00 00:00:00');
        $detail->setBirthday($birthday);
        $em->flush();

        $ret = $repo->getUserDetailByUserId(8);

        $this->assertNull($ret[0]['birthday']);
    }

    /**
     * 測試藉由使用者id取得詳細資料時，生日為null
     */
    public function testGetUserDetailByUserIdWhenBirthdayIsNull()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:UserDetail');

        $detail = $em->find('BBDurianBundle:UserDetail', 8);
        $detail->setBirthday(null);
        $em->flush();

        $ret = $repo->getUserDetailByUserId(8);

        $this->assertNull($ret[0]['birthday']);
    }
}
