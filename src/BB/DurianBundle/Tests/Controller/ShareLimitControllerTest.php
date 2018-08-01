<?php

namespace BB\DurianBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Controller\ShareLimitController;

class ShareLimitControllerTest extends ControllerTest
{
    /**
     * 測試單獨新增預改佔成會回傳錯誤
     */
    public function testNewShareLimitNextWillGetError()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No sharelimit specified',
            150080030
        );

        $parameters = [
            'sharelimit_next' => [
                'upper'        => 10,
                'lower'        => 10,
                'parent_upper' => 10,
                'parent_lower' => 10
            ]
        ];

        $request = new Request([], $parameters);
        $controller = new ShareLimitController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, 1, 1);
    }

    /**
     * 測試新增佔成傳入空陣列
     */
    public function testNewShareLimitWithEmptyShareLimit()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No sharelimit specified',
            150080030
        );

        $parameters = ['sharelimit' => []];

        $request = new Request([], $parameters);
        $controller = new ShareLimitController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, 1, 1);
    }

    /**
     * 測試檢查佔成,沒傳group_num
     */
    public function testValidateShareLimitWithoutGroupNum()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No group_num specified',
            150080036
        );

        $parameters = [
            'parent_id'    => 7,
            'next'         => 0,
            'upper'        => 0,
            'lower'        => 0,
            'parent_upper' => 20,
            'parent_lower' => 20
        ];

        $query = new Request($parameters);
        $controller = new ShareLimitController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->validateAction($query);
    }

    /**
     * 測試檢查佔成, 同時傳parentId, userId
     */
    public function testValidateShareLimitWithParentIdAndUserId()
    {
        $this->setExpectedException(
            'RuntimeException',
            'User id and parent id can not be assigned simultaneously',
            150080027
        );

        $parameters = [
            'parent_id'    => 9,
            'user_id'      => 10,
            'next'         => 0,
            'group_num'    => 1,
            'upper'        => 90,
            'lower'        => 10,
            'parent_upper' => 95,
            'parent_lower' => 5
        ];

        $query = new Request($parameters);
        $controller = new ShareLimitController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->validateAction($query);
    }

    /**
     * 測試檢查佔成, 不傳parentId, userId
     */
    public function testValidateShareLimitWithoutParentIdAndUserId()
    {
        $this->setExpectedException(
            'RuntimeException',
            'User id or parent id not found',
            150080026
        );

        $parameters = [
            'next'         => 0,
            'group_num'    => 1,
            'upper'        => 90,
            'lower'        => 10,
            'parent_upper' => 95,
            'parent_lower' => 5
        ];

        $query = new Request($parameters);
        $controller = new ShareLimitController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->validateAction($query);
    }

    /**
     * 測試取得佔成範圍(不傳group number)
     */
    public function testGetOptionByParentIdWithoutGroupNumber()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No group_num specified',
            150080036
        );

        $parameters = ['parent_id' => 6];

        $query = new Request($parameters);
        $controller = new ShareLimitController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getOptionAction($query);
    }

    /**
     * 測試取得佔成分配, 但傳的時間參數錯誤
     */
    public function testGetDivisionWithWrongTimeStamp()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Wrong type of timestamp',
            150080033
        );

        $parameters = ['timestamp' => 123];

        $query = new Request($parameters);
        $controller = new ShareLimitController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getDivisionAction($query, 1, 1);
    }

    /**
     * 測試取得佔成分配, 但傳的時間為空
     */
    public function testGetDivisionWithEmptyTimeStamp()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Must send timestamp',
            150080032
        );

        $parameters = ['timestamp' => ''];

        $query = new Request($parameters);
        $controller = new ShareLimitController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getDivisionAction($query, 1, 1);
    }

    /**
     * 測試取得佔成分配, group number 不合法
     */
    public function testGetDivisionWithInvalidGroupNumber()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid group number',
            150080031
        );

        $parameters = ['timestamp' => '2015-01-01 00:00:00'];

        $shareUpdateCron = $this->getMockBuilder('BB\DurianBundle\Entity\ShareUpdateCron')
            ->setMethods(['findBy'])
            ->disableOriginalConstructor()
            ->getMock();
        $shareUpdateCron->expects($this->any())
            ->method('findBy')
            ->will($this->returnValue(null));

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->setMethods(['getRepository', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($shareUpdateCron));

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);

        $query = new Request($parameters);
        $controller = new ShareLimitController();
        $controller->setContainer($container);

        $controller->getDivisionAction($query, 1, 1);
    }

    /**
     * 測試取得多個佔成分配, 但傳的時間參數錯誤
     */
    public function testGetMultiDivisionWithWrongTimeStamp()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Wrong type of timestamp',
            150080033
        );

        $parameters = ['timestamp' => 123];

        $query = new Request($parameters);
        $controller = new ShareLimitController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getMultiDivisionAction($query, 1);
    }

    /**
     * 測試取得多個佔成分配, 但傳的時間為空
     */
    public function testGetMutliDivisionWithEmptyTimeStamp()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Must send timestamp',
            150080032
        );

        $parameters = ['timestamp' => ''];

        $query = new Request($parameters);
        $controller = new ShareLimitController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getMultiDivisionAction($query, 1);
    }
}
