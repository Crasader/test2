<?php

namespace BB\DurianBundle\Tests\Controller;

use BB\DurianBundle\Controller\MerchantLevelController;
use Symfony\Component\HttpFoundation\Request;

class MerchantLevelControllerTest extends ControllerTest
{
    /**
     * 測試取得商家層級設定但商家不存在
     */
    public function testGetButMerchantNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Merchant found',
            670001
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $controller = new MerchantLevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getAction(777, 1);
    }

    /**
     * 測試取得商家層級設定但無商家層級設定值
     */
    public function testGetButMerchantLevelNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantLevel found',
            670003
        );

        $merchant = $this->getMockBuilder('BB\DurianBundle\Entity\Merchant')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(0))
            ->method('find')
            ->willReturn($merchant);

        $controller = new MerchantLevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getAction(1, 777);
    }

    /**
     * 測試取得商家層級列表但商家不存在
     */
    public function testListButMerchantNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Merchant found',
            670001
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $controller = new MerchantLevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->listAction(777);
    }

    /**
     * 測試由層級取得商家層級設定，帶入空幣別
     */
    public function testGetByLevelWithEmptyCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            670004
        );

        $params = ['currency' => ''];

        $request = new Request($params);
        $controller = new MerchantLevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getByLevelAction($request, 3);
    }

    /**
     * 測試由層級取得商家層級設定，帶入錯誤幣別
     */
    public function testGetByLevelWithInvalidCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            670004
        );

        $params = ['currency' => 'AAA'];

        $request = new Request($params);
        $controller = new MerchantLevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getByLevelAction($request, 3);
    }

    /**
     * 測試由層級取得商家帶入不合法的付款方式
     */
    public function testGetByLevelWithInvalidPayway()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid payway',
            670005
        );

        $params = ['payway' => 8888];

        $request = new Request($params);
        $controller = new MerchantLevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getByLevelAction($request, 3);
    }

    /**
     * 測試設定商家層級，但沒有帶層級ID
     */
    public function testSetWithoutLevelId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No level_id specified',
            670006
        );

        $request = new Request();
        $controller = new MerchantLevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setAction($request, 1);
    }

    /**
     * 測試設定商家層級，但帶的層級ID非陣列
     */
    public function testSetButLevelIdIsInteger()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No level_id specified',
            670006
        );

        $params = ['level_id' => 7];

        $request = new Request([], $params);
        $controller = new MerchantLevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setAction($request, 1);
    }

    /**
     * 測試設定商家層級，但帶的層級ID是空陣列
     */
    public function testSetButLevelIdIsEmptyArray()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No level_id specified',
            670006
        );

        $params = ['level_id' => []];

        $request = new Request([], $params);
        $controller = new MerchantLevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setAction($request, 1);
    }

    /**
     * 測試設定商家層級，但商家不存在
     */
    public function testSetButMerchantNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Merchant found',
            670001
        );

        $params = [
            'level_id' => [1, 2, 3]
        ];

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $request = new Request([], $params);
        $controller = new MerchantLevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setAction($request, 777);
    }

    /**
     * 測試設定商家層級，但層級不存在
     */
    public function testSetButLevelNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Level found',
            670002
        );

        $params = [
            'level_id' => [1, 2, 3]
        ];

        $merchant = $this->getMockBuilder('BB\DurianBundle\Entity\Merchant')
            ->disableOriginalConstructor()
            ->getMock();

        $level = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findBy')
            ->willReturn([$level, $level]);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(3))
            ->method('find')
            ->willReturn($merchant);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $params);
        $controller = new MerchantLevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setAction($request, 1);
    }

    /**
     * 測試設定層級內可用商家，但不傳商家參數
     */
    public function testSetByLevelWithoutMerchants()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No merchants specified',
            670008
        );

        $request = new Request();
        $controller = new MerchantLevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setByLevelAction($request, 1);
    }

    /**
     * 測試設定層級內可用商家，但層級不存在
     */
    public function testSetByLevelButLevelNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Level found',
            670002
        );

        $params = ['merchants' => [1, 2, 3]];

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $request = new Request([], $params);
        $controller = new MerchantLevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setByLevelAction($request, 1);
    }

    /**
     * 測試設定層級內商家順序，但不傳商家參數
     */
    public function testSetOrderWithoutMerchants()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid merchants',
            670009
        );

        $request = new Request();
        $controller = new MerchantLevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setOrderAction($request, 3);
    }

    /**
     * 測試設定層級內商家順序，但傳入空的商家參數
     */
    public function testSetOrderWithEmptyMerchants()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No merchants specified',
            670008
        );

        $params = ['merchants' => []];

        $request = new Request([], $params);
        $controller = new MerchantLevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setOrderAction($request, 3);
    }

    /**
     * 測試設定層級內商家順序，但層級不存在
     */
    public function testSetOrderButLevelNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Level found',
            670002
        );

        $params = [
            'merchants' => [
                [
                    'merchant_id' => 1,
                    'order_id' => 55,
                    'version' => 1
                ]
            ]
        ];

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $request = new Request([], $params);
        $controller = new MerchantLevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setOrderAction($request, 777);
    }

    /**
     * 測試設定層級內商家順序，但商家不存在
     */
    public function testSetOrderButMerchantNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Merchant found',
            670001
        );

        $params = [
            'merchants' => [
                [
                    'merchant_id' => 1,
                    'order_id' => 55,
                    'version' => 1
                ]
            ]
        ];

        $level = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(2))
            ->method('find')
            ->willReturn($level);

        $request = new Request([], $params);
        $controller = new MerchantLevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setOrderAction($request, 1);
    }

    /**
     * 測試設定層級內商家順序，但商家未啟用
     */
    public function testSetOrderWithDisableMerchant()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cannot change when merchant disabled',
            670010
        );

        $params = [
            'merchants' => [
                [
                    'merchant_id' => 1,
                    'order_id' => 55,
                    'version' => 1
                ]
            ]
        ];

        $level = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();

        $merchant = $this->getMockBuilder('BB\DurianBundle\Entity\Merchant')
            ->disableOriginalConstructor()
            ->getMock();
        $merchant->expects($this->any())
            ->method('isEnabled')
            ->willReturn(false);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(2))
            ->method('find')
            ->willReturn($level);
        $em->expects($this->at(3))
            ->method('find')
            ->willReturn($merchant);

        $request = new Request([], $params);
        $controller = new MerchantLevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setOrderAction($request, 1);
    }

    /**
     * 測試設定商家層級付款方式，但找不到商家
     */
    public function testSetMerchantLevelMethodButMerchantNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Merchant found',
            670001
        );

        $conn = $this->getMockBuilder('\Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getConnection')
            ->willReturn($conn);

        $request = new Request();
        $controller = new MerchantLevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setMerchantLevelMethodAction($request, 999);
    }

    /**
     * 測試刪除商家層級付款方式，但找不到商家
     */
    public function testRemoveMerchantLevelMethodButMerchantNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Merchant found',
            670001
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $operation = $this->getMockBuilder('\BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();

        $request = new Request();
        $controller = new MerchantLevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $operation);
        $controller->setContainer($container);

        $controller->removeMerchantLevelMethodAction($request, 99);
    }

    /**
     * 測試設定商家層級付款廠商，但找不到商家
     */
    public function testSetMerchantLevelVendorButMerchantNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Merchant found',
            670001
        );

        $conn = $this->getMockBuilder('\Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getConnection')
            ->willReturn($conn);

        $request = new Request();
        $controller = new MerchantLevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setMerchantLevelVendorAction($request, 999);
    }

    /**
     * 測試刪除商家層級付款廠商，但找不到商家
     */
    public function testRemoveMerchantLevelVendorButMerchantNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Merchant found',
            670001
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $operation = $this->getMockBuilder('\BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();

        $request = new Request();
        $controller = new MerchantLevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $operation);
        $controller->setContainer($container);

        $controller->removeMerchantLevelVendorAction($request, 99);
    }

    /**
     * 測試回傳商家層級設定的付款方式，但沒有帶入廳及商家ID
     */
    public function testGetMerchantLevelMethodWithoutDomainAndMerchantId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No domain or merchant_id specified',
            670018
        );

        $request = new Request();
        $controller = new MerchantLevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getMerchantLevelMethodAction($request);
    }

    /**
     * 測試回傳商家層級設定的付款廠商，但沒有帶入廳及商家ID
     */
    public function testGetMerchantLevelVendorWithoutDomainAndMerchantId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No domain or merchant_id specified',
            670018
        );

        $request = new Request();
        $controller = new MerchantLevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getMerchantLevelVendorAction($request);
    }
}
