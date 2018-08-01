<?php

namespace BB\DurianBundle\Tests\Controller;

use BB\DurianBundle\Controller\PaymentGatewayController;
use Symfony\Component\HttpFoundation\Request;

class PaymentGatewayControllerTest extends ControllerTest
{
    /**
     * 測試取得支付平台支援的幣別帶入不存在的Id
     */
    public function testGetPaymentGatewayCurrencyWithInvalidId()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No PaymentGateway found',
            520015
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->will($this->returnValue(null));

        $controller = new PaymentGatewayController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getPaymentGatewayCurrencyAction(1);
    }

    /**
     * 測試修改支付平台 name非UTF8
     */
    public function testEditPaymentGatewayNameNotUtf8()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $operationLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn('something');

        $query = ['name' => mb_convert_encoding('我付通', 'GB2312', 'UTF-8')];

        $request = new Request([], $query);
        $controller = new PaymentGatewayController();
        $container = static::$kernel->getContainer();
        $container->set('durian.operation_logger', $operationLogger);
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改支付平台沒帶入代碼
     */
    public function testEditPaymentGatewayWithoutCode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid PaymentGateway code',
            520004
        );

        $query = ['code' => ''];

        $request = new Request([], $query);
        $controller = new PaymentGatewayController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setAction($request, 1);
    }

    /**
     * 測試修改支付平台沒帶入名稱
     */
    public function testEditPaymentGatewayWithoutName()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid PaymentGateway name',
            520005
        );

        $query = [
            'code' => 'TestPay',
            'name' => '',
        ];

        $request = new Request([], $query);
        $controller = new PaymentGatewayController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setAction($request, 1);
    }

    /**
     * 測試設定支付平台支援的幣別沒有帶入幣別
     */
    public function testSetPaymentGatewayWithoutCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal currency',
            520001
        );

        $query = [];

        $request = new Request([], $query);
        $controller = new PaymentGatewayController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setPaymentGatewayCurrencyAction($request, 1);
    }

    /**
     * 測試設定支付平台支援的幣別帶入幣別為空
     */
    public function testSetPaymentGatewayWithEmptyCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal currency',
            520001
        );

        $query = ['currencies' => []];

        $request = new Request([], $query);
        $controller = new PaymentGatewayController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setPaymentGatewayCurrencyAction($request, 1);
    }

    /**
     * 測試設定支付平台支援的幣別帶入錯誤的ID
     */
    public function testSetPaymentGatewayWithInvalidCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal currency',
            520001
        );

        $query = ['currencies' => ['RMB']];

        $request = new Request([], $query);
        $controller = new PaymentGatewayController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setPaymentGatewayCurrencyAction($request, 1);
    }

    /**
     * 測試修改不存在的支付平台
     */
    public function testEditPaymentGatewayNotExists()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No PaymentGateway found',
            520015
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->will($this->returnValue(null));

        $query = [
            'code' => 'TestPay',
            'name' => 'TestPay',
            'post_url' => 'http://pay.com/pay'
        ];

        $request = new Request([], $query);
        $controller = new PaymentGatewayController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setAction($request, 1);
    }

    /**
     * 測試取得支付平台支援的幣別帶入錯誤的ID
     */
    public function testGetPaymentGatewayWithInvalidCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            520007
        );

        $request = new Request();
        $controller = new PaymentGatewayController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getPaymentGatewayByCurrency($request, 'RMB');
    }

    /**
     * 測試取得不存在的支付平台
     */
    public function testGetPaymentGatewayNotExists()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No PaymentGateway found',
            520015
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->will($this->returnValue(null));

        $controller = new PaymentGatewayController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getAction(1);
    }

    /**
     * 測試新增綁定ip時沒有帶入ip參數
     */
    public function testAddPaymentGatewayBindIpWithoutIp()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid bind ip',
            520003
        );

        $query = [];

        $request = new Request([], $query);
        $controller = new PaymentGatewayController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->addPaymentGatewayBindIpAction($request, 1);
    }

    /**
     * 測試新增綁定ip時帶入的ip不為陣列
     */
    public function testAddPaymentGatewayBindIpIsNotIpArray()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid bind ip',
            520003
        );

        $query = [
            'ips' => '123.1.2.3'
        ];

        $request = new Request([], $query);
        $controller = new PaymentGatewayController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->addPaymentGatewayBindIpAction($request, 1);
    }

    /**
     * 測試新增綁定ip時帶入不完整的ip
     */
    public function testAddPaymentGatewayBindIpWithIpIncomplete()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid bind ip',
            520003
        );

        $query = [
            'ips' => [
                '123.1.2.3',
                '123'
            ]
        ];

        $request = new Request([], $query);
        $controller = new PaymentGatewayController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->addPaymentGatewayBindIpAction($request, 1);
    }

    /**
     * 測試新增綁定ip時帶入錯誤的ip
     */
    public function testAddPaymentGatewayBindIpWithInvalidIp()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid bind ip',
            520003
        );

        $query = [
            'ips' => [
                '123.1.2.33',
                '123.1.2.777'
            ]
        ];

        $request = new Request([], $query);
        $controller = new PaymentGatewayController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->addPaymentGatewayBindIpAction($request, 1);
    }

    /**
     * 測試新增支付平台的綁定ip時支付平台不存在
     */
    public function testAddPaymentGatewayBindIpWithPaymentGatewayNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No PaymentGateway found',
            520015
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->will($this->returnValue(null));

        $query = [
            'ips' => [
                '123.1.2.3',
                '123.1.2.5'
            ]
        ];

        $request = new Request([], $query);
        $controller = new PaymentGatewayController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->addPaymentGatewayBindIpAction($request, 1);
    }

    /**
     * 測試刪除綁定ip時沒有帶入ip參數
     */
    public function testRemovePaymentGatewayBindIpWithoutIp()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid bind ip',
            520003
        );

        $query = [];

        $request = new Request([], $query);
        $controller = new PaymentGatewayController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->removePaymentGatewayBindIpAction($request, 1);
    }

    /**
     * 測試刪除綁定ip時帶入的ip不為陣列
     */
    public function testRemovePaymentGatewayBindIpIsNotIpArray()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid bind ip',
            520003
        );

        $query = ['ips' => '123.1.2.3'];

        $request = new Request([], $query);
        $controller = new PaymentGatewayController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->removePaymentGatewayBindIpAction($request, 1);
    }

    /**
     * 測試刪除綁定ip時帶入不完整的ip
     */
    public function testRemovePaymentGatewayBindIpWithIpIncomplete()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid bind ip',
            520003
        );

        $query = [
            'ips' => [
                '123.123.123.123',
                '123'
            ]
        ];

        $request = new Request([], $query);
        $controller = new PaymentGatewayController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->removePaymentGatewayBindIpAction($request, 1);
    }

    /**
     * 測試刪除綁定ip時帶入錯誤的ip
     */
    public function testRemovePaymentGatewayBindIpWithInvalidIp()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid bind ip',
            520003
        );

        $query = [
            'ips' => [
                '123.123.123.123',
                '123.123.123.777'
            ]
        ];

        $request = new Request([], $query);
        $controller = new PaymentGatewayController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->removePaymentGatewayBindIpAction($request, 1);
    }

    /**
     * 測試刪除錯誤支付平台ID
     */
    public function testRemovePaymentGatewayWithInvalidId()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No PaymentGateway found',
            520015
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->will($this->returnValue(null));

        $controller = new PaymentGatewayController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->removeAction(1);
    }

    /**
     * 測試刪除支付平台的綁定ip時支付平台不存在
     */
    public function testRemovePaymentGatewayBindIpWithPaymentGatewayNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No PaymentGateway found',
            520015
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->will($this->returnValue(null));

        $query = [
            'ips' => [
                '123.1.2.3',
                '123.1.2.5'
            ]
        ];

        $request = new Request([], $query);
        $controller = new PaymentGatewayController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->removePaymentGatewayBindIpAction($request, 1);
    }

    /**
     * 測試查詢支付平台綁定ip序列時支付平台不存在
     */
    public function testGetPaymentGatewayBindIpWhenPaymentGatewayNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No PaymentGateway found',
            520015
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->will($this->returnValue(null));

        $controller = new PaymentGatewayController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getPaymentGatewayBindIpAction(1);
    }

    /**
     * 測試取得支付平台設定的出款銀行帶入不存在的支付平台
     */
    public function testGetBankInfoWithNoPaymentGatewayFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No PaymentGateway found',
            520015
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $controller = new PaymentGatewayController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getBankInfoAction(999);
    }

    /**
     * 測試設定支付平台的出款銀行帶入不存在的支付平台
     */
    public function testSetBankInfoWithNoPaymentGatewayFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No PaymentGateway found',
            520015
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $request = new Request();
        $controller = new PaymentGatewayController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setBankInfoAction($request, 999);
    }

    /**
     * 測試設定支付平台的出款銀行帶入不存在的銀行
     */
    public function testSetBankInfoWithNoBankInfoFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No BankInfo found',
            150520025
        );

        $paymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($paymentGateway);

        $params = ['bank_info' => [999]];

        $request = new Request([], $params);
        $controller = new PaymentGatewayController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setBankInfoAction($request, 1);
    }

    /**
     * 測試設定支付平台的出款銀行時發生重複資料的Exception
     */
    public function testSetBankInfoButDatabaseIsBusy()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Database is busy',
            150520024
        );

        $paymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();
        $bankInfo = $this->getMockBuilder('BB\DurianBundle\Entity\BankInfo')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentGateway->expects($this->any())
            ->method('getBankInfo')
            ->willReturn($bankInfo);
        $bankInfo->expects($this->any())
            ->method('getId')
            ->willReturn([]);

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['find'])
            ->getMock();

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'getRepository', 'beginTransaction', 'flush', 'rollback', 'commit', 'clear'])
            ->getMock();
        $em->expects($this->at(1))
            ->method('find')
            ->willReturn($paymentGateway);
        $em->expects($this->at(2))
            ->method('find')
            ->willReturn($bankInfo);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $pdoExcep = new \PDOException('Duplicate', 1062);
        $pdoExcep->errorInfo[1] = 1062;

        $em->expects($this->once())
            ->method('commit')
            ->willThrowException(new \RuntimeException('Database is busy', 150520024, $pdoExcep));

        $request = new Request();
        $controller = new PaymentGatewayController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setBankInfoAction($request, 1);
    }

    /**
     * 測試取得支付平台欄位說明時支付平台不存在
     */
    public function testGetDescriptionButPaymentGatewayNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No PaymentGateway found',
            520015
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $request = new Request();
        $controller = new PaymentGatewayController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getDescriptionAction($request, 1);
    }

    /**
     * 測試取得支付平台欄位說明不存在
     */
    public function testGetDescriptionWithDescriptionNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No PaymentGatewayDescription found',
            150520026
        );

        $paymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($paymentGateway);

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findBy'])
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findBy')
            ->willReturn(null);
        $em->expects($this->once())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request();
        $controller = new PaymentGatewayController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getDescriptionAction($request, 1);
    }

    /**
     * 測試設定支付平台欄位說明傳入不合法的payment_gateway_description
     */
    public function testSetDescriptionWithInvalidPaymentGatewayDescriptions()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid payment_gateway_descriptions',
            150520027
        );

        $query = ['payment_gateway_descriptions' => 'name'];

        $request = new Request([], $query);
        $controller = new PaymentGatewayController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setDescriptionAction($request, 1);
    }

    /**
     * 測試設定支付平台欄位說明支付平台不存在
     */
    public function testSetDescriptionWithPaymentGatewayNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No PaymentGateway found',
            520015
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $descriptions = [
            [
                'name' => 'number',
                'value' => '111'
            ],
            [
                'name' => 'terminalId',
                'value' => '111'
            ]
        ];

        $query = ['payment_gateway_descriptions' => $descriptions];

        $request = new Request([], $query);
        $controller = new PaymentGatewayController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setDescriptionAction($request, 999);
    }

    /**
     * 測試設定支付平台欄位說明未帶入欄位名稱
     */
    public function testSetDescriptionWithoutName()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No name specified',
            150520028
        );

        $paymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();

        $operation = $this->getMockBuilder('\BB\DurianBundle\Logger\Operation')
           ->disableOriginalConstructor()
           ->getMock();

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($paymentGateway);

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $descriptions = [
            [
                'value' => '111'
            ]
        ];

        $query = ['payment_gateway_descriptions' => $descriptions];

        $request = new Request([], $query);
        $controller = new PaymentGatewayController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $operation);
        $controller->setContainer($container);

        $controller->setDescriptionAction($request, 1);
    }

    /**
     * 測試設定支付平台欄位說明未帶入欄位說明
     */
    public function testSetDescriptionWithoutValue()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No value specified',
            150520029
        );

        $paymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();

        $operation = $this->getMockBuilder('\BB\DurianBundle\Logger\Operation')
           ->disableOriginalConstructor()
           ->getMock();

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($paymentGateway);

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $descriptions = [
            [
                'name' => 'number'
            ]
        ];

        $query = ['payment_gateway_descriptions' => $descriptions];


        $request = new Request([], $query);
        $controller = new PaymentGatewayController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $operation);
        $controller->setContainer($container);

        $controller->setDescriptionAction($request, 1);
    }

    /**
     * 測試設定支付平台欄位說明不存在
     */
    public function testSetDescriptionWithNoPaymentGatewayDescriptionFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No PaymentGatewayDescription found',
            150520026
        );

        $paymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();

        $operation = $this->getMockBuilder('\BB\DurianBundle\Logger\Operation')
           ->disableOriginalConstructor()
           ->getMock();

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('find')
            ->willReturn($paymentGateway);

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $descriptions = [
            [
                'name' => 'test',
                'value' => '111'
            ]
        ];

        $query = ['payment_gateway_descriptions' => $descriptions];

        $request = new Request([], $query);
        $controller = new PaymentGatewayController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $operation);
        $controller->setContainer($container);

        $controller->setDescriptionAction($request, 1);
    }

    /**
     * 測試設定支付平台支援隨機小數的付款廠商，付款廠商不支援
     */
    public function testSetRandomFloatVendorWithPaymentVendorNotSupportByPaymentGateway()
    {
        $this->setExpectedException(
            'RuntimeException',
            'PaymentVendor not support by PaymentGateway',
            520019
        );

        $pgrfv = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGatewayRandomFloatVendor')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentVendor = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentVendor')
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();
        $paymentVendor->expects($this->any())
            ->method('getId')
            ->willReturn(10);
        $paymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->setMethods(['getPaymentVendor'])
            ->getMock();
        $paymentGateway->expects($this->any())
            ->method('getPaymentVendor')
            ->willReturn([$paymentVendor]);

        $operation = $this->getMockBuilder('\BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'getRepository', 'beginTransaction', 'clear'])
            ->getMock();

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('find')
            ->willReturn($paymentGateway);
        $entityRepo->expects($this->once())
            ->method('findBy')
            ->willReturn($pgrfv);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $params = [
            'payment_vendor' => [
                1,
                2,
                4,
            ],
        ];

        $request = new Request([], $params);
        $controller = new PaymentGatewayController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $operation);
        $controller->setContainer($container);

        $controller->setRandomFloatVendorAction($request, 1);
    }

    /**
     * 測試設定支付平台支援隨機小數的付款廠商同分秒問題
     */
    public function testSetRandomFloatVendorWithDatabaseIsBusy()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Database is busy',
            150520024
        );

        $pgrfv = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGatewayRandomFloatVendor')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentVendor = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentVendor')
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();
        $paymentVendor->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $paymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->setMethods(['getPaymentVendor'])
            ->getMock();
        $paymentGateway->expects($this->any())
            ->method('getPaymentVendor')
            ->willReturn([$paymentVendor]);

        $logOp = $this->getMockBuilder('BB\DurianBundle\Entity\LogOperation')
            ->disableOriginalConstructor()
            ->setMethods(['addMessage'])
            ->getMock();

        $operation = $this->getMockBuilder('\BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->setMethods(['create', 'save'])
            ->getMock();
        $operation->expects($this->any())
            ->method('create')
            ->willReturn($logOp);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'getRepository', 'beginTransaction', 'persist', 'flush', 'clear'])
            ->getMock();

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->once())
            ->method('find')
            ->willReturn($paymentGateway);
        $entityRepo->expects($this->once())
            ->method('findBy')
            ->willReturn([$pgrfv]);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $em->expects($this->any())
            ->method('flush')
            ->will($this->throwException(new \RuntimeException('Database is busy', 150520024)));

        $params = ['payment_vendor' => [1]];

        $request = new Request([], $params);
        $controller = new PaymentGatewayController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $operation);
        $controller->setContainer($container);

        $controller->setRandomFloatVendorAction($request, 1);
    }
}
