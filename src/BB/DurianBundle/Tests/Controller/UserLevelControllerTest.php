<?php

namespace BB\DurianBundle\Tests\Controller;

use BB\DurianBundle\Controller\UserLevelController;
use Symfony\Component\HttpFoundation\Request;

class UserLevelControllerTest extends ControllerTest
{
    /**
     * 測試回傳使用者的層級資料時，帶入不合法的使用者參數
     */
    public function testGetWithInvalidUser()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid user_id',
            150640002
        );

        $params = ['user_id' => 35660];

        $request = new Request($params);
        $controller = new UserLevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getAction($request);
    }

    /**
     * 測試回傳使用者的層級資料時，帶入空的使用者
     */
    public function testGetButUserIsEmpty()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No user_id specified',
            150640003
        );

        $params = ['user_id' => []];

        $request = new Request($params);
        $controller = new UserLevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getAction($request);
    }

    /**
     * 測試設定會員層級時，未帶入user_levels
     */
    public function testSetWithoutUserLevels()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid user_levels',
            150640004
        );

        $request = new Request();
        $controller = new UserLevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setAction($request);
    }

    /**
     * 測試設定會員層級時，帶入非陣列的user_levels
     */
    public function testSetWithUserLevelsIsNotArray()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid user_levels',
            150640004
        );

        $params = ['user_levels' => 1];

        $request = new Request([], $params);
        $controller = new UserLevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setAction($request);
    }

    /**
     * 測試設定會員層級時，未帶入user_id
     */
    public function testSetWithoutUserId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No user_id specified',
            150640003
        );

        $params = [
            'user_levels' => [
                ['level_id' => 1]
            ]
        ];

        $request = new Request([], $params);
        $controller = new UserLevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setAction($request);
    }

    /**
     * 測試設定會員層級時，未帶入level_id
     */
    public function testSetWithoutLevelId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No level_id specified',
            150640005
        );

        $params = [
            'user_levels' => [
                ['user_id' => 1]
            ]
        ];

        $request = new Request([], $params);
        $controller = new UserLevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setAction($request);
    }

    /**
     * 測試設定會員層級時，帶入不存在會員層級的會員
     */
    public function testSetButUserLevelNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No UserLevel found',
            150640007
        );

        $params = [
            'user_levels' => [
                [
                    'user_id' => 1,
                    'level_id' => 1
                ]
            ]
        ];

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $request = new Request([], $params);
        $controller = new UserLevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setAction($request);
    }

    /**
     * 測試設定會員層級時，找不到層級
     */
    public function testSetButLevelNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Level found',
            150640006
        );

        $params = [
            'user_levels' => [
                [
                    'user_id' => 1,
                    'level_id' => 1
                ]
            ]
        ];

        $userLevel = $this->getMockBuilder('BB\DurianBundle\Entity\UserLevel')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(0))
            ->method('find')
            ->willReturn($userLevel);

        $request = new Request([], $params);
        $controller = new UserLevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setAction($request);
    }

    /**
     * 測試批次鎖定會員層級時，帶入不合法的userId
     */
    public function testLockWithInvalidUserId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid user_id',
            150640002
        );

        $params = ['user_id' => 35660];

        $request = new Request([], $params);
        $controller = new UserLevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->lockAction($request);
    }

    /**
     * 測試批次鎖定會員層級時，帶入空的userId
     */
    public function testLockButUserIdIsEmpty()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No user_id specified',
            150640003
        );

        $params = ['user_id' => []];

        $request = new Request([], $params);
        $controller = new UserLevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->lockAction($request);
    }

    /**
     * 測試批次鎖定會員層級時，帶入不存在的使用者層級
     */
    public function testLockButUserLevelNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No UserLevel found',
            150640007
        );

        $params = ['user_id' => [1, 2]];

        $userLevel = $this->getMockBuilder('BB\DurianBundle\Entity\UserLevel')
            ->disableOriginalConstructor()
            ->getMock();

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findBy')
            ->willReturn([$userLevel]);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $params);
        $controller = new UserLevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->lockAction($request);
    }

    /**
     * 測試批次解鎖會員層級時，帶入不合法的userId
     */
    public function testUnlockWithInvalidUserId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid user_id',
            150640002
        );

        $params = ['user_id' => 35660];

        $request = new Request([], $params);
        $controller = new UserLevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->unlockAction($request);
    }

    /**
     * 測試批次解鎖會員層級時，帶入空的userId
     */
    public function testUnlockButUserIdIsEmpty()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No user_id specified',
            150640003
        );

        $params = ['user_id' => []];

        $request = new Request([], $params);
        $controller = new UserLevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->unlockAction($request);
    }

    /**
     * 測試批次解鎖會員層級時，帶入不存在的使用者層級
     */
    public function testUnlockButUserLevelNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No UserLevel found',
            150640007
        );

        $params = ['user_id' => [1, 2]];

        $userLevel = $this->getMockBuilder('BB\DurianBundle\Entity\UserLevel')
            ->disableOriginalConstructor()
            ->getMock();

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findBy')
            ->willReturn([$userLevel]);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $params);
        $controller = new UserLevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->unlockAction($request);
    }
}
