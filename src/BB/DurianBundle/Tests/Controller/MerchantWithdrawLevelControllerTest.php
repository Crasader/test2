<?php

namespace BB\DurianBundle\Tests\Controller;

use BB\DurianBundle\Controller\MerchantWithdrawLevelController;
use Symfony\Component\HttpFoundation\Request;

class MerchantWithdrawLevelControllerTest extends ControllerTest
{
    /**
     * 測試取得出款商家層級設定但出款商家不存在
     */
    public function testGetButMerchantWithdrawNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantWithdraw found',
            150740002
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $controller = new MerchantWithdrawLevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getAction(777, 1);
    }

    /**
     * 測試取得出款商家層級設定但無出款商家層級設定值
     */
    public function testGetButMerchantWithdrawLevelNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantWithdrawLevel found',
            150740003
        );

        $merchantWithdraw = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantWithdraw')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(0))
            ->method('find')
            ->willReturn($merchantWithdraw);

        $controller = new MerchantWithdrawLevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getAction(1, 777);
    }

    /**
     * 測試取得出款商家層級列表但出款商家不存在
     */
    public function testListButMerchantWithdrawNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantWithdraw found',
            150740002
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $controller = new MerchantWithdrawLevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->listAction(777);
    }

    /**
     * 測試依層級回傳出款商家層級設定, 帶入空幣別
     */
    public function testGetByLevelWithEmptyCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            150740004
        );

        $params = ['currency' => ''];

        $request = new Request($params);
        $controller = new MerchantWithdrawLevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getByLevelAction($request, 3);
    }

    /**
     * 測試依層級回傳出款商家層級設定, 帶入錯誤幣別
     */
    public function testGetByLevelWithInvalidCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            150740004
        );

        $params = ['currency' => 'AAA'];

        $request = new Request($params);
        $controller = new MerchantWithdrawLevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getByLevelAction($request, 3);
    }

    /**
     * 測試設定出款商家層級，但沒有帶層級ID
     */
    public function testSetWithoutLevelId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No level_id specified',
            150740005
        );

        $request = new Request();
        $controller = new MerchantWithdrawLevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setAction($request, 1);
    }

    /**
     * 測試設定出款商家層級，但帶的層級ID非陣列
     */
    public function testSetButLevelIdIsNotArray()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid level_id',
            150740009
        );

        $params = ['level_id' => 7];

        $request = new Request([], $params);
        $controller = new MerchantWithdrawLevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setAction($request, 1);
    }

    /**
     * 測試設定出款商家層級，但出款商家不存在
     */
    public function testSetButMerchantWithdrawNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantWithdraw found',
            150740002
        );

        $params = [
            'level_id' => [1, 2, 3]
        ];

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $request = new Request([], $params);
        $controller = new MerchantWithdrawLevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setAction($request, 777);
    }

    /**
     * 測試設定出款商家層級，但層級不存在
     */
    public function testSetButLevelNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Level found',
            150740006
        );

        $params = [
            'level_id' => [1, 2, 3]
        ];

        $merchantWithdraw = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantWithdraw')
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
        $em->expects($this->at(2))
            ->method('find')
            ->willReturn($merchantWithdraw);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $params);
        $controller = new MerchantWithdrawLevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setAction($request, 1);
    }

    /**
     * 測試設定出款商家層級，但層級支援銀行資料被使用中
     */
    public function testSetButLevelBankInfoInUsed()
    {
        $this->setExpectedException(
            'RuntimeException',
            'MerchantWithdrawLevelBankInfo is in used',
            150740007
        );

        $params = [
            'level_id' => [1, 2]
        ];

        $merchantWithdraw = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantWithdraw')
            ->disableOriginalConstructor()
            ->getMock();

        $level = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();

        $mwl = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantWithdrawLevel')
            ->disableOriginalConstructor()
            ->getMock();
        $mwl->expects($this->at(0))
            ->method('getLevelId')
            ->willReturn(1);
        $mwl->expects($this->at(1))
            ->method('getLevelId')
            ->willReturn(2);
        $mwl->expects($this->at(2))
            ->method('getLevelId')
            ->willReturn(3);

        $bankInfo = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantWithdrawLevelBankInfo')
            ->disableOriginalConstructor()
            ->getMock();

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->at(0))
            ->method('findBy')
            ->willReturn([$level, $level]);
        $entityRepo->expects($this->at(1))
            ->method('findBy')
            ->willReturn([$mwl, $mwl, $mwl]);
        $entityRepo->expects($this->at(3))
            ->method('findBy')
            ->willReturn($bankInfo);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(2))
            ->method('find')
            ->willReturn($merchantWithdraw);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $params);
        $controller = new MerchantWithdrawLevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setAction($request, 1);
    }

    /**
     * 測試設定層級內可用出款商家，但不傳出款商家參數
     */
    public function testSetByLevelWithoutMerchantWithdrawId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No merchant_withdraws specified',
            150740011
        );

        $request = new Request();
        $controller = new MerchantWithdrawLevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setByLevelAction($request, 1);
    }


    /**
     * 測試設定層級內可用出款商家，但帶的出款商家參數非陣列
     */
    public function testSetByLevelButMerchantWithdrawIdIsNotArray()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid merchant_withdraws',
            150740010
        );

        $params = ['merchant_withdraws' => 7];

        $request = new Request([], $params);
        $controller = new MerchantWithdrawLevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setByLevelAction($request, 1);
    }

    /**
     * 測試設定層級內可用出款商家，但層級不存在
     */
    public function testSetByLevelButLevelNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Level found',
            150740006
        );

        $params = ['merchant_withdraws' => [1, 2, 3]];

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $request = new Request([], $params);
        $controller = new MerchantWithdrawLevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setByLevelAction($request, 1);
    }

    /**
     * 測試設定層級內可用出款商家，但層級支援銀行資料被使用中
     */
    public function testSetByLevelButLevelBankInfoInUsed()
    {
        $this->setExpectedException(
            'RuntimeException',
            'MerchantWithdrawLevelBankInfo is in used',
            150740007
        );

        $params = ['merchant_withdraws' => [1, 2]];

        $level = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();

        $mwl = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantWithdrawLevel')
            ->disableOriginalConstructor()
            ->getMock();
        $mwl->expects($this->at(0))
            ->method('getmerchantWithdrawId')
            ->willReturn(1);
        $mwl->expects($this->at(1))
            ->method('getmerchantWithdrawId')
            ->willReturn(2);
        $mwl->expects($this->at(2))
            ->method('getmerchantWithdrawId')
            ->willReturn(3);

        $bankInfo = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantWithdrawLevelBankInfo')
            ->disableOriginalConstructor()
            ->getMock();

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->at(0))
            ->method('findBy')
            ->willReturn([$mwl, $mwl, $mwl]);
        $entityRepo->expects($this->at(1))
            ->method('findBy')
            ->willReturn($bankInfo);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(2))
            ->method('find')
            ->willReturn($level);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $params);
        $controller = new MerchantWithdrawLevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setByLevelAction($request, 1);
    }

    /**
     * 測試設定層級內出款商家順序，但不傳出款商家參數
     */
    public function testSetOrderWithoutMerchantWithdraws()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No merchant_withdraws specified',
            150740011
        );

        $request = new Request();
        $controller = new MerchantWithdrawLevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setOrderAction($request, 3);
    }

    /**
     * 測試設定層級內出款商家順序，但帶的出款商家參數非陣列
     */
    public function testSetOrderButMerchantWithdrawsIsNotArray()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid merchant_withdraws',
            150740010
        );

        $params = ['merchant_withdraws' => 123];

        $request = new Request([], $params);
        $controller = new MerchantWithdrawLevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setOrderAction($request, 3);
    }

    /**
     * 測試設定層級內出款商家順序，但層級不存在
     */
    public function testSetOrderButLevelNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Level found',
            150740006
        );

        $params = [
            'merchant_withdraws' => [
                [
                    'merchant_withdraw_id' => 1,
                    'order_id' => 55,
                    'version' => 1
                ]
            ]
        ];

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $request = new Request([], $params);
        $controller = new MerchantWithdrawLevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setOrderAction($request, 777);
    }

    /**
     * 測試設定層級內出款商家順序，但出款商家不存在
     */
    public function testSetOrderButMerchantWithdrawNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantWithdraw found',
            150740002
        );

        $params = [
            'merchant_withdraws' => [
                [
                    'merchant_withdraw_id' => 1,
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
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($level);

        $request = new Request([], $params);
        $controller = new MerchantWithdrawLevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setOrderAction($request, 1);
    }

    /**
     * 測試設定層級內出款商家順序，但出款商家未啟用
     */
    public function testSetOrderWithDisableMerchantWithdraw()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cannot change when MerchantWithdraw disabled',
            150740012
        );

        $params = [
            'merchant_withdraws' => [
                [
                    'merchant_withdraw_id' => 1,
                    'order_id' => 55,
                    'version' => 1
                ]
            ]
        ];

        $level = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();

        $merchantWithdraw = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantWithdraw')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantWithdraw->expects($this->any())
            ->method('isEnabled')
            ->willReturn(false);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($level);
        $em->expects($this->at(3))
            ->method('find')
            ->willReturn($merchantWithdraw);

        $request = new Request([], $params);
        $controller = new MerchantWithdrawLevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setOrderAction($request, 1);
    }

    /**
     * 測試設定層級內出款商家順序, 但無出款商家層級設定值
     */
    public function testSetOrderButMerchantWithdrawLevelNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantWithdrawLevel found',
            150740003
        );

        $params = [
            'merchant_withdraws' => [
                [
                    'merchant_withdraw_id' => 1,
                    'order_id' => 55,
                    'version' => 1
                ]
            ]
        ];

        $level = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();

        $merchantWithdraw = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantWithdraw')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantWithdraw->expects($this->any())
            ->method('isEnabled')
            ->willReturn(true);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($level);
        $em->expects($this->at(3))
            ->method('find')
            ->willReturn($merchantWithdraw);

        $request = new Request([], $params);
        $controller = new MerchantWithdrawLevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setOrderAction($request, 1);
    }

    /**
     * 測試設定層級內出款商家順序, 但層級內出款商家順序已改變
     */
    public function testSetOrderButMerchantWithdrawLevelOrderChanged()
    {
        $this->setExpectedException(
            'RuntimeException',
            'MerchantWithdrawLevel Order has been changed',
            150740013
        );

        $params = [
            'merchant_withdraws' => [
                [
                    'merchant_withdraw_id' => 1,
                    'order_id' => 55,
                    'version' => 1
                ]
            ]
        ];

        $level = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();

        $merchantWithdraw = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantWithdraw')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantWithdraw->expects($this->any())
            ->method('isEnabled')
            ->willReturn(true);

        $mwl = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantWithdrawLevel')
            ->disableOriginalConstructor()
            ->getMock();
        $mwl->expects($this->at(0))
            ->method('getVersion')
            ->willReturn(2);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($level);
        $em->expects($this->at(3))
            ->method('find')
            ->willReturn($merchantWithdraw);
        $em->expects($this->at(4))
            ->method('find')
            ->willReturn($mwl);

        $request = new Request([], $params);
        $controller = new MerchantWithdrawLevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setOrderAction($request, 1);
    }

    /**
     * 測試設定層級內出款商家順序, 但層級內出款商家順序重複
     */
    public function testSetOrderButDuplicateOrderId()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Duplicate orderId',
            150740014
        );

        $params = [
            'merchant_withdraws' => [
                [
                    'merchant_withdraw_id' => 1,
                    'order_id' => 55,
                    'version' => 1
                ]
            ]
        ];

        $mwlRepo = $this->getMockBuilder('BB\DurianBundle\Repository\MerchantWithdrawLevelRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $mwlRepo->expects($this->any())
            ->method('getDuplicatedOrder')
            ->willReturn([1, 2]);

        $level = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();

        $merchantWithdraw = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantWithdraw')
            ->disableOriginalConstructor()
            ->getMock();
        $merchantWithdraw->expects($this->any())
            ->method('isEnabled')
            ->willReturn(true);

        $mwl = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantWithdrawLevel')
            ->disableOriginalConstructor()
            ->getMock();
        $mwl->expects($this->at(0))
            ->method('getVersion')
            ->willReturn(1);
        $mwl->expects($this->at(1))
            ->method('getOrderId')
            ->willReturn(55);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(0))
            ->method('getRepository')
            ->willReturn($mwlRepo);
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($level);
        $em->expects($this->at(3))
            ->method('find')
            ->willReturn($merchantWithdraw);
        $em->expects($this->at(4))
            ->method('find')
            ->willReturn($mwl);

        $request = new Request([], $params);
        $controller = new MerchantWithdrawLevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setOrderAction($request, 1);
    }

    /**
     * 回傳出款商家層級出款銀行但沒有帶入廳及出款商家ID
     */
    public function testGetBankInfoWithoutDomainAndMerchantWithdrawId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No domain or merchant_withdraw_id specified',
            150740001
        );

        $request = new Request();
        $controller = new MerchantWithdrawLevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getBankInfoAction($request);
    }

    /**
     * 測試設定出款商家層級出款銀行但找不到出款商家
     */
    public function testSetBankInfoButMerchantWithdrawNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantWithdraw found',
            150740002
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
        $controller = new MerchantWithdrawLevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setBankInfoAction($request, 999);
    }

    /**
     * 測試移除出款商家層級出款銀行但找不到出款商家
     */
    public function testRemoveBankInfoButMerchantWithdrawNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantWithdraw found',
            150740002
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $operation = $this->getMockBuilder('\BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();

        $request = new Request();
        $controller = new MerchantWithdrawLevelController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $operation);
        $controller->setContainer($container);

        $controller->removeBankInfoAction($request, 999);
    }
}
