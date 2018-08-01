<?php
namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Operator;
use BB\DurianBundle\Entity\PaymentGateway;
use BB\DurianBundle\Entity\Merchant;
use BB\DurianBundle\Entity\MerchantKey;
use BB\DurianBundle\Entity\MerchantCard;
use BB\DurianBundle\Payment\CBPay;
use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;
use BB\DurianBundle\Entity\MerchantWithdraw;
use BB\DurianBundle\Entity\DomainConfig;

class OperatorTest extends DurianTestCase
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * @var \Doctrine\Bundle\DoctrineBundle\Registry
     */
    private $mockDoctrine;

    /**
     * @var \Doctrine\ORM\EntityRepository
     */
    private $mockEntityRepository;

    public function setUp()
    {
        $this->mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository', 'persist'])
            ->getMock();

        $mockEmShare = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository', 'find', 'persist'])
            ->getMock();

        $repoMethods = [
            'findOneBy',
            'findBy',
            'getCurrentVersion',
            'getBlockByIpAddress',
            'getIpStrategy',
            'getMerchantIdByOrderId',
            'getMerchantCountByIds'
        ];

        $this->mockEntityRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods($repoMethods)
            ->getMock();

        $getMap = [
            ['default', $mockEm],
            ['share', $mockEmShare]
        ];
        $this->mockDoctrine->expects($this->any())
            ->method('getManager')
            ->will($this->returnValueMap($getMap));

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->mockEntityRepository);

        $domainConfig = new DomainConfig(1, 'test1234', 'dt');
        $mockEmShare->expects($this->any())
            ->method('find')
            ->willReturn($domainConfig);

        $mockEmShare->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->mockEntityRepository);

        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $mockItalkingOperator = $this->getMockBuilder('BB\DurianBundle\Message\ITalkingOperator')
            ->disableOriginalConstructor()
            ->setMethods(['pushMessageToQueue'])
            ->getMock();

        $mockItalkingOperator->expects($this->any())
            ->method('pushMessageToQueue')
            ->willReturn(null);

        $mockOperationLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->setMethods(['create', 'addMessage'])
            ->getMock();
        $mockOperationLogger->expects($this->any())
            ->method('create')
            ->willReturn($mockOperationLogger);

        $getMap = [
            ['doctrine.orm.entity_manager', 1, $mockEm],
            ['durian.italking_operator', 1, $mockItalkingOperator],
            ['durian.operation_logger', 1, $mockOperationLogger]
        ];

        $this->container->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));
        $this->container->expects($this->any())
            ->method('getParameter')
            ->willReturn('[172.26.54.42, 172.26.54.41]');
    }

    /**
     * 測試處理商號達到限制停用沒有商號設定
     */
    public function testSuspendMerchantNoMerchantExtraExist()
    {
        $mockPaymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();

        $merchent1 = new Merchant($mockPaymentGateway, 1, 'alias1', '12345', '6', '156');
        $merchent1->setId(1);

        $operator = new Operator();
        $operator->setDoctrine($this->mockDoctrine);
        $result = $operator->suspendMerchant($merchent1);

        // 測試回傳結果為空
        $this->assertNull($result);
    }

    /**
     * 測試處理商號達到限制停用
     */
    public function testSuspendMerchant()
    {
        $mockPaymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();

        $mockMerchantExtra = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantExtra')
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMock();
        $mockMerchantExtra->expects($this->any())
            ->method('getValue')
            ->willReturn(50);

        $mockMerchantStat = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantStat')
            ->disableOriginalConstructor()
            ->setMethods(['getTotal'])
            ->getMock();
        $mockMerchantStat->expects($this->any())
            ->method('getTotal')
            ->willReturn(1000);

        $mockMerchantLevel = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantLevel')
            ->disableOriginalConstructor()
            ->setMethods(['getLevelId'])
            ->getMock();
        $mockMerchantLevel->expects($this->any())
            ->method('getLevelId')
            ->willReturn(1);

        $this->mockEntityRepository->expects($this->at(0))
            ->method('findOneBy')
            ->willReturn($mockMerchantExtra);
        $this->mockEntityRepository->expects($this->at(1))
            ->method('findOneBy')
            ->willReturn($mockMerchantStat);

        $mockMerchantLevels = [
            $mockMerchantLevel,
            $mockMerchantLevel
        ];
        $this->mockEntityRepository->expects($this->any())
            ->method('findBy')
            ->willReturn($mockMerchantLevels);

        // domain = 6，esball
        $merchent1 = new Merchant($mockPaymentGateway, 1, 'alias1', '12345', '6', '156');
        $merchent1->setId(1);
        $merchent1->enable();

        $operator = new Operator();
        $operator->setContainer($this->container);
        $operator->setDoctrine($this->mockDoctrine);
        $result = $operator->suspendMerchant($merchent1);

        // 測試回傳結果為空
        $this->assertNull($result);

        // domain = 98， 博九
        $merchent1 = new Merchant($mockPaymentGateway, 1, 'alias1', '12345', '98', '156');
        $merchent1->setId(2);

        $operator = new Operator();
        $operator->setContainer($this->container);
        $operator->setDoctrine($this->mockDoctrine);
        $result = $operator->suspendMerchant($merchent1);

        // 測試回傳結果為空
        $this->assertNull($result);
    }

    /**
     * 測試移除被IP限制的商家
     */
    public function testIpBlockFilter()
    {
        $mockPaymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockEntityRepository->expects($this->any())
            ->method('getIpStrategy')
            ->willReturn([1]);

        $merchent1 = new Merchant($mockPaymentGateway, 1, 'alias1', '12345', '6', '156');
        $merchent1->setId(1);

        $merchent2 = new Merchant($mockPaymentGateway, 1, 'alias2', '54321', '6', '156');
        $merchent2->setId(2);

        $merchents = [
            $merchent1,
            $merchent2
        ];

        $ip = '127.0.0.1';
        $ph = new Operator();
        $ph->setDoctrine($this->mockDoctrine);
        $result = $ph->ipBlockFilter($ip, $merchents);

        //測試回傳結果為merchant2
        $this->assertEquals($merchent2, $result[1]);
    }

    /**
     * 測試根據層級的排序設定取得商家但層級不存在
     */
    public function testGetMerchantByOrderStrategyButLevelNotFound()
    {
        $this->setExpectedException('RuntimeException', 'No Level found', 180136);

        $mockEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $mockDoctrine = $this->getMockBuilder('\Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();
        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $mockPaymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();

        $merchent1 = new Merchant($mockPaymentGateway, 1, 'alias1', '12345', '6', '156');
        $merchent1->setId(1);

        $merchent2 = new Merchant($mockPaymentGateway, 1, 'alias2', '54321', '6', '156');
        $merchent2->setId(2);

        $merchents = [
            $merchent1,
            $merchent2
        ];

        $operator = new Operator();
        $operator->setDoctrine($mockDoctrine);
        $operator->getMerchantByOrderStrategy($merchents, 1);
    }

    /**
     * 測試根據層級的排序設定取得商家且照商家層級設定排序
     */
    public function testGetMerchantByOrderStrategyWithStrategyIsOrderId()
    {
        $mocklevel = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();
        $mocklevel->expects($this->any())
            ->method('getOrderStrategy')
            ->willReturn(0);

        $mockEntityRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['getMerchantCountByIds', 'getMinOrderMerchant', 'findOneBy'])
            ->getMock();

        $merchantIds = [
            'merchant_id' => 2,
            'deposit_count' => 0
        ];
        $mockEntityRepository->expects($this->any())
            ->method('getMerchantCountByIds')
            ->willReturn($merchantIds);

        $merchantIds = [
            'merchant_id' => 1,
            'order_id' => 0
        ];
        $mockEntityRepository->expects($this->any())
            ->method('getMinOrderMerchant')
            ->willReturn($merchantIds);

        $mockEntityRepository->expects($this->any())
            ->method('findOneBy')
            ->willReturn('');

        $mockEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($mocklevel);
        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockEntityRepository);

        $mockDoctrine = $this->getMockBuilder('\Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();
        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $mockPaymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();

        $merchent1 = new Merchant($mockPaymentGateway, 1, 'alias1', '12345', '6', '156');
        $merchent1->setId(1);

        $merchent2 = new Merchant($mockPaymentGateway, 1, 'alias2', '54321', '6', '156');
        $merchent2->setId(2);

        $merchents = [
            $merchent1,
            $merchent2
        ];

        $operator = new Operator();
        $operator->setDoctrine($mockDoctrine);
        $result = $operator->getMerchantByOrderStrategy($merchents, 1);

        // 測試回傳結果是否為merchant1
        $this->assertEquals($merchent1, $result);
    }

    /**
     * 測試根據層級的排序設定取得商家且照商家交易次數排序
     */
    public function testGetMerchantByOrderStrategyWithStrategyIsCount()
    {
        $mocklevel = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();
        $mocklevel->expects($this->any())
            ->method('getOrderStrategy')
            ->willReturn(1);

        $mockEntityRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['getMerchantCountByIds', 'getMinOrderMerchant', 'findOneBy'])
            ->getMock();

        $merchantIds = [
            'merchant_id' => 2,
            'deposit_count' => 0
        ];
        $mockEntityRepository->expects($this->any())
            ->method('getMerchantCountByIds')
            ->willReturn($merchantIds);

        $merchantIds = [
            'merchant_id' => 1,
            'order_id' => 0
        ];
        $mockEntityRepository->expects($this->any())
            ->method('getMinOrderMerchant')
            ->willReturn($merchantIds);

        $mockEntityRepository->expects($this->any())
            ->method('findOneBy')
            ->willReturn('');

        $mockEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($mocklevel);
        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockEntityRepository);

        $mockDoctrine = $this->getMockBuilder('\Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();
        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $mockPaymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();

        $merchent1 = new Merchant($mockPaymentGateway, 1, 'alias1', '12345', '6', '156');
        $merchent1->setId(1);

        $merchent2 = new Merchant($mockPaymentGateway, 1, 'alias2', '54321', '6', '156');
        $merchent2->setId(2);

        $merchents = [
            $merchent1,
            $merchent2
        ];

        $operator = new Operator();
        $operator->setDoctrine($mockDoctrine);
        $result = $operator->getMerchantByOrderStrategy($merchents, 1);

        // 測試回傳結果是否為merchant2
        $this->assertEquals($merchent2, $result);
    }

    /**
     * 測試訂單查詢成功
     */
    public function testPaymentTrackingSuccess()
    {
        $paymentGateway = new PaymentGateway('CBPay', '網銀在線', 'http://cbpay.com', 1);
        $paymentGateway->setAutoReop(true);
        $merchant = new Merchant($paymentGateway, 1, 'CBPayTest', '12345', '6', '156');
        $merchantPublicKey = new MerchantKey($merchant, 'public', 'testPublicKey');

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'getRepository'])
            ->getMock();

        $mockEntityRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findBy', 'findOneBy'])
            ->getMock();

        $mockEntityRepository->expects($this->any())
            ->method('findBy')
            ->willReturn([]);

        $mockEntityRepository->expects($this->at(2))
            ->method('findOneBy')
            ->willReturn($merchantPublicKey);

        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($merchant);

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockEntityRepository);

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CashDepositEntry')
            ->disableOriginalConstructor()
            ->setMethods(['getAt'])
            ->getMock();

        $mockEntry->expects($this->any())
            ->method('getAt')
            ->willReturn(new \DateTime('20150101000000'));

        $mockCBPay = $this->getMockBuilder('BB\DurianBundle\Payment\CBPay')
            ->disableOriginalConstructor()
            ->getMock();

        $mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();

        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $mockOperator = $this->getMockBuilder('BB\DurianBundle\Payment\Operator')
            ->disableOriginalConstructor()
            ->setMethods(['getAvaliablePaymentGateway'])
            ->getMock();

        $mockOperator->expects($this->any())
            ->method('getAvaliablePaymentGateway')
            ->willReturn($mockCBPay);

        $mockOperator->setContainer($this->container);
        $mockOperator->setDoctrine($mockDoctrine);
        $mockOperator->paymentTracking($mockEntry);
    }

    /**
     * 測試訂單查詢結果為不支援訂單查詢功能
     */
    public function testPaymentTrackingDoesNotSupport()
    {
        $this->setExpectedException(
            'RuntimeException',
            'PaymentGateway does not support order tracking',
            180074
        );

        $paymentGateway = new PaymentGateway('CBPay', '網銀在線', 'http://cbpay.com', 1);
        $merchant = new Merchant($paymentGateway, 1, 'CBPayTest', '12345', '6', '156');

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find'])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($merchant);

        $mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();

        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CashDepositEntry')
            ->disableOriginalConstructor()
            ->getMock();

        $operator = new Operator();
        $operator->setDoctrine($mockDoctrine);
        $operator->paymentTracking($mockEntry);
    }

    /**
     * 測試訂單查詢結果支付失敗
     */
    public function testTrackingReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $paymentGateway = new PaymentGateway('CBPay', '網銀在線', 'http://cbpay.com', 1);
        $paymentGateway->setAutoReop(true);
        $merchant = new Merchant($paymentGateway, 1, 'CBPayTest', '12345', '6', '156');

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'getRepository'])
            ->getMock();

        $mockEntityRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findBy', 'findOneBy'])
            ->getMock();

        $mockEntityRepository->expects($this->any())
            ->method('findBy')
            ->willReturn([]);

        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($merchant);

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockEntityRepository);

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CashDepositEntry')
            ->disableOriginalConstructor()
            ->setMethods(['getAt'])
            ->getMock();

        $mockEntry->expects($this->any())
            ->method('getAt')
            ->willReturn(new \DateTime('20150101000000'));

        $mockCBPay = $this->getMockBuilder('BB\DurianBundle\Payment\CBPay')
            ->disableOriginalConstructor()
            ->setMethods(['paymentTracking'])
            ->getMock();

        $exception = new PaymentConnectionException('Payment failure', 180035, $mockEntry->getId());

        $mockCBPay->expects($this->any())
            ->method('paymentTracking')
            ->will($this->throwException($exception));

        $mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();

        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $mockOperator = $this->getMockBuilder('BB\DurianBundle\Payment\Operator')
            ->disableOriginalConstructor()
            ->setMethods(['getAvaliablePaymentGateway'])
            ->getMock();

        $mockOperator->expects($this->any())
            ->method('getAvaliablePaymentGateway')
            ->willReturn($mockCBPay);

        $mockOperator->setContainer($this->container);
        $mockOperator->setDoctrine($mockDoctrine);
        $mockOperator->paymentTracking($mockEntry);
    }

    /**
     * 測試批次訂單查詢結果為不支援批次訂單查詢功能
     */
    public function testBatchTrackingDoesNotSupport()
    {
        $this->setExpectedException(
            'RuntimeException',
            'PaymentGateway does not support batch order tracking',
            150180174
        );

        $paymentGateway = new PaymentGateway('CBPay', '網銀在線', 'http://cbpay.com', 1);
        $merchant = new Merchant($paymentGateway, 1, 'CBPayTest', '12345', '6', '156');

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find'])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($merchant);

        $mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();

        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $entries = [
            ['entry_id' => 20160204170000],
            ['entry_id' => 20160204180000],
        ];

        $operator = new Operator();
        $operator->setDoctrine($mockDoctrine);
        $operator->batchTracking(1, $entries);
    }

    /**
     * 測試批次訂單查詢
     */
    public function testBatchTracking()
    {
        $mockPaymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();

        $mockPaymentGateway->expects($this->any())
            ->method('getId')
            ->willReturn(8);

        $mockMerchant = $this->getMockBuilder('BB\DurianBundle\Entity\Merchant')
            ->disableOriginalConstructor()
            ->setMethods(['getPaymentGateway'])
            ->getMock();

        $mockMerchant->expects($this->any())
            ->method('getPaymentGateway')
            ->willReturn($mockPaymentGateway);

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CashDepositEntry')
            ->disableOriginalConstructor()
            ->setMethods(['getAt'])
            ->getMock();

        $mockEntry->expects($this->any())
            ->method('getAt')
            ->willReturn(new \DateTime('20150101000000'));

        $mockEntityRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();

        $mockEntityRepository->expects($this->any())
            ->method('findOneBy')
            ->willReturn($mockEntry);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'getRepository'])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($mockMerchant);

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockEntityRepository);

        $mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();

        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $mockIPS = $this->getMockBuilder('BB\DurianBundle\Payment\IPS')
            ->disableOriginalConstructor()
            ->getMock();

        $mockOperator = $this->getMockBuilder('BB\DurianBundle\Payment\Operator')
            ->disableOriginalConstructor()
            ->setMethods(['getAvaliablePaymentGateway'])
            ->getMock();

        $mockOperator->expects($this->any())
            ->method('getAvaliablePaymentGateway')
            ->willReturn($mockIPS);

        $entries = [
            ['entry_id' => 20160204170000],
            ['entry_id' => 20160204180000],
        ];

        $mockOperator->setContainer($this->container);
        $mockOperator->setDoctrine($mockDoctrine);
        $mockOperator->batchTracking(1, $entries);
    }

    /**
     * 測試租卡批次訂單查詢結果為不支援批次訂單查詢功能
     */
    public function testCardBatchTrackingDoesNotSupport()
    {
        $this->setExpectedException(
            'RuntimeException',
            'PaymentGateway does not support batch order tracking',
            150180174
        );

        $paymentGateway = new PaymentGateway('CBPay', '網銀在線', 'http://cbpay.com', 1);
        $merchantCard = new MerchantCard($paymentGateway, 'CBPayTest', '12345', '6', '156');

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find'])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($merchantCard);

        $mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();

        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $entries = [
            ['entry_id' => 20160204170000],
            ['entry_id' => 20160204180000],
        ];

        $operator = new Operator();
        $operator->setDoctrine($mockDoctrine);
        $operator->cardBatchTracking(1, $entries);
    }

    /**
     * 測試租卡批次訂單查詢
     */
    public function testCardBatchTracking()
    {
        $mockPaymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();

        $mockPaymentGateway->expects($this->any())
            ->method('getId')
            ->willReturn(8);

        $mockMerchant = $this->getMockBuilder('BB\DurianBundle\Entity\Merchant')
            ->disableOriginalConstructor()
            ->setMethods(['getPaymentGateway'])
            ->getMock();

        $mockMerchant->expects($this->any())
            ->method('getPaymentGateway')
            ->willReturn($mockPaymentGateway);

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CashDepositEntry')
            ->disableOriginalConstructor()
            ->setMethods(['getAt'])
            ->getMock();

        $mockEntry->expects($this->any())
            ->method('getAt')
            ->willReturn(new \DateTime('20150101000000'));

        $mockEntityRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();

        $mockEntityRepository->expects($this->any())
            ->method('findOneBy')
            ->willReturn($mockEntry);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'getRepository'])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($mockMerchant);

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockEntityRepository);

        $mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();

        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $mockIPS = $this->getMockBuilder('BB\DurianBundle\Payment\IPS')
            ->disableOriginalConstructor()
            ->getMock();

        $mockOperator = $this->getMockBuilder('BB\DurianBundle\Payment\Operator')
            ->disableOriginalConstructor()
            ->setMethods(['getAvaliablePaymentGateway'])
            ->getMock();

        $mockOperator->expects($this->any())
            ->method('getAvaliablePaymentGateway')
            ->willReturn($mockIPS);

        $entries = [
            ['entry_id' => 20160204170000],
            ['entry_id' => 20160204180000],
        ];

        $mockOperator->setContainer($this->container);
        $mockOperator->setDoctrine($mockDoctrine);
        $mockOperator->cardBatchTracking(1, $entries);
    }

    /**
     * 測試根據層級的排序設定取得商家但找不到商家
     */
    public function testGetMerchantByOrderStrategyButMerchantNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Merchant found',
            180006
        );

        $mocklevel = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();
        $mocklevel->expects($this->any())
            ->method('getOrderStrategy')
            ->willReturn(0);

        $mockEntityRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['getMerchantCountByIds', 'getMinOrderMerchant', 'findOneBy'])
            ->getMock();

        $merchantIds = [
            'merchant_id' => 2,
            'deposit_count' => 0
        ];
        $mockEntityRepository->expects($this->any())
            ->method('getMerchantCountByIds')
            ->willReturn($merchantIds);

        $mockEntityRepository->expects($this->any())
            ->method('getMinOrderMerchant')
            ->willReturn([]);

        $mockEntityRepository->expects($this->any())
            ->method('findOneBy')
            ->willReturn('');

        $mockEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($mocklevel);
        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockEntityRepository);

        $mockDoctrine = $this->getMockBuilder('\Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();
        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $mockPaymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();

        $merchent1 = new Merchant($mockPaymentGateway, 1, 'alias1', '12345', '6', '156');
        $merchent1->setId(1);

        $merchent2 = new Merchant($mockPaymentGateway, 1, 'alias2', '54321', '6', '156');
        $merchent2->setId(2);

        $merchents = [
            $merchent1,
            $merchent2
        ];

        $operator = new Operator();
        $operator->setDoctrine($mockDoctrine);
        $operator->getMerchantByOrderStrategy($merchents, 1);
    }

    /**
     * 測試租卡訂單查詢成功
     */
    public function testCardTrackingSuccess()
    {
        $paymentGateway = new PaymentGateway('CBPay', '網銀在線', 'http://cbpay.com', 1);
        $paymentGateway->setAutoReop(true);
        $merchantCard = new MerchantCard($paymentGateway, 'CBPayTest', '12345', '6', '156');

        $mockRsaKey = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCardKey')
            ->disableOriginalConstructor()
            ->getMock();

        $mockMerchantExtra = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCardExtra')
            ->disableOriginalConstructor()
            ->getMock();

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'getRepository'])
            ->getMock();

        $mockEntityRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findBy', 'findOneBy'])
            ->getMock();

        $mockEntityRepository->expects($this->any())
            ->method('findBy')
            ->willReturn([$mockMerchantExtra, $mockMerchantExtra]);

        $mockEntityRepository->expects($this->any())
            ->method('findOneBy')
            ->willReturn($mockRsaKey);

        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($merchantCard);

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockEntityRepository);

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CardDepositEntry')
            ->disableOriginalConstructor()
            ->setMethods(['getAt'])
            ->getMock();

        $mockEntry->expects($this->any())
            ->method('getAt')
            ->willReturn(new \DateTime('20150101000000'));

        $mockCBPay = $this->getMockBuilder('BB\DurianBundle\Payment\CBPay')
            ->disableOriginalConstructor()
            ->getMock();

        $mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();

        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $mockOperator = $this->getMockBuilder('BB\DurianBundle\Payment\Operator')
            ->disableOriginalConstructor()
            ->setMethods(['getAvaliablePaymentGateway'])
            ->getMock();

        $mockOperator->expects($this->any())
            ->method('getAvaliablePaymentGateway')
            ->willReturn($mockCBPay);

        $mockOperator->setContainer($this->container);
        $mockOperator->setDoctrine($mockDoctrine);
        $mockOperator->cardTracking($mockEntry);
    }

    /**
     * 測試租卡訂單查詢結果為不支援訂單查詢功能
     */
    public function testCardTrackingDoesNotSupport()
    {
        $this->setExpectedException(
            'RuntimeException',
            'PaymentGateway does not support order tracking',
            150720024
        );

        $paymentGateway = new PaymentGateway('CBPay', '網銀在線', 'http://cbpay.com', 1);
        $merchantCard = new MerchantCard($paymentGateway, 'CBPayTest', '12345', '6', '156');

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'getRepository'])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($merchantCard);

        $mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();

        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CardDepositEntry')
            ->disableOriginalConstructor()
            ->getMock();

        $operator = new Operator();
        $operator->setDoctrine($mockDoctrine);
        $operator->cardTracking($mockEntry);
    }

    /**
     * 測試管端訂單查詢結果支付失敗
     */
    public function testCardTrackingReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $paymentGateway = new PaymentGateway('CBPay', '網銀在線', 'http://cbpay.com', 1);
        $paymentGateway->setAutoReop(true);
        $merchantCard = new MerchantCard($paymentGateway, 'CBPayTest', '12345', '6', '156');

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'getRepository'])
            ->getMock();

        $mockEntityRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findBy', 'findOneBy'])
            ->getMock();

        $mockEntityRepository->expects($this->any())
            ->method('findBy')
            ->willReturn([]);

        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($merchantCard);

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockEntityRepository);

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CardDepositEntry')
            ->disableOriginalConstructor()
            ->setMethods(['getAt'])
            ->getMock();

        $mockEntry->expects($this->any())
            ->method('getAt')
            ->willReturn(new \DateTime('20150101000000'));

        $mockCBPay = $this->getMockBuilder('BB\DurianBundle\Payment\CBPay')
            ->disableOriginalConstructor()
            ->setMethods(['paymentTracking'])
            ->getMock();

        $exception = new PaymentConnectionException('Payment failure', 180035, $mockEntry->getId());

        $mockCBPay->expects($this->any())
            ->method('paymentTracking')
            ->will($this->throwException($exception));

        $mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();

        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $mockOperator = $this->getMockBuilder('BB\DurianBundle\Payment\Operator')
            ->disableOriginalConstructor()
            ->setMethods(['getAvaliablePaymentGateway'])
            ->getMock();

        $mockOperator->expects($this->any())
            ->method('getAvaliablePaymentGateway')
            ->willReturn($mockCBPay);

        $mockOperator->setContainer($this->container);
        $mockOperator->setDoctrine($mockDoctrine);
        $mockOperator->cardTracking($mockEntry);
    }

    /**
     * 測試根據層級的排序設定取得出款商家但層級不存在
     */
    public function testGetMerchantWithdrawByOrderStrategyButLevelNotFound()
    {
        $this->setExpectedException('RuntimeException', 'No Level found', 180136);

        $mockEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $mockDoctrine = $this->getMockBuilder('\Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();
        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $mockPaymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();

        $merchentWithdraw1 = new MerchantWithdraw($mockPaymentGateway, 'alias1', '12345', '6', '156');
        $merchentWithdraw1->setId(1);

        $merchentWithdraw2 = new MerchantWithdraw($mockPaymentGateway, 'alias2', '54321', '6', '156');
        $merchentWithdraw2->setId(2);

        $merchentWithdraws = [
            $merchentWithdraw1,
            $merchentWithdraw2
        ];

        $operator = new Operator();
        $operator->setDoctrine($mockDoctrine);
        $operator->getMerchantWithdrawByOrderStrategy($merchentWithdraws, 1);
    }

    /**
     * 測試根據層級的排序設定取得出款商家但找不到出款商家
     */
    public function testGetMerchantWithdrawByOrderStrategyButMerchantWithdrawNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantWithdraw found',
            150180158
        );

        $mockLevel = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();
        $mockLevel->expects($this->any())
            ->method('getOrderStrategy')
            ->willReturn(0);

        $mockEntityRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['getMerchantWithdrawCountByIds', 'getMinOrderMerchantWithdraw'])
            ->getMock();

        $merchantWithdrawIds = [
            'merchant_withdraw_id' => 2,
            'deposit_count' => 0
        ];
        $mockEntityRepository->expects($this->any())
            ->method('getMerchantWithdrawCountByIds')
            ->willReturn($merchantWithdrawIds);

        $mockEntityRepository->expects($this->any())
            ->method('getMinOrderMerchantWithdraw')
            ->willReturn([]);

        $mockEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($mockLevel);
        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockEntityRepository);

        $mockDoctrine = $this->getMockBuilder('\Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();
        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $mockPaymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();

        $merchentWithdraw1 = new MerchantWithdraw($mockPaymentGateway, 'alias1', '12345', '6', '156');
        $merchentWithdraw1->setId(1);

        $merchentWithdraw2 = new MerchantWithdraw($mockPaymentGateway, 'alias2', '54321', '6', '156');
        $merchentWithdraw2->setId(2);

        $merchentWithdraws = [
            $merchentWithdraw1,
            $merchentWithdraw2
        ];

        $operator = new Operator();
        $operator->setDoctrine($mockDoctrine);
        $operator->getMerchantWithdrawByOrderStrategy($merchentWithdraws, 1);
    }

    /**
     * 測試取得單筆查詢時需要的參數成功
     */
    public function testGetPaymentTrackingDataSuccess()
    {
        $paymentGateway = new PaymentGateway('CBPay', '網銀在線', 'http://cbpay.com', 1);
        $paymentGateway->setAutoReop(true);
        $merchant = new Merchant($paymentGateway, 1, 'CBPayTest', '12345', '6', '156');
        $merchantPublicKey = new MerchantKey($merchant, 'public', 'testPublicKey');

        $mockEntityRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findBy', 'findOneBy'])
            ->getMock();
        $mockEntityRepository->expects($this->any())
            ->method('findBy')
            ->willReturn([]);
        $mockEntityRepository->expects($this->at(2))
            ->method('findOneBy')
            ->willReturn($merchantPublicKey);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'getRepository'])
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($merchant);
        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockEntityRepository);

        $mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();
        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $mockCBPay = $this->getMockBuilder('BB\DurianBundle\Payment\CBPay')
            ->disableOriginalConstructor()
            ->getMock();

        $mockOperator = $this->getMockBuilder('BB\DurianBundle\Payment\Operator')
            ->disableOriginalConstructor()
            ->setMethods(['getAvaliablePaymentGateway'])
            ->getMock();
        $mockOperator->expects($this->any())
            ->method('getAvaliablePaymentGateway')
            ->willReturn($mockCBPay);

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CashDepositEntry')
            ->disableOriginalConstructor()
            ->setMethods(['getAt'])
            ->getMock();
        $mockEntry->expects($this->any())
            ->method('getAt')
            ->willReturn(new \DateTime('20150101000000'));

        $mockOperator->setContainer($this->container);
        $mockOperator->setDoctrine($mockDoctrine);
        $mockOperator->getPaymentTrackingData($mockEntry);
    }

    /**
     * 測試取得單筆查詢時需要的參數但不支援訂單查詢功能
     */
    public function testGetPaymentTrackingDataButNotSupportTracking()
    {
        $this->setExpectedException(
            'RuntimeException',
            'PaymentGateway does not support order tracking',
            180074
        );

        $paymentGateway = new PaymentGateway('CBPay', '網銀在線', 'http://cbpay.com', 1);
        $merchant = new Merchant($paymentGateway, 1, 'CBPayTest', '12345', '6', '156');

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find'])
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($merchant);

        $mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();
        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CashDepositEntry')
            ->disableOriginalConstructor()
            ->getMock();

        $operator = new Operator();
        $operator->setContainer($this->container);
        $operator->setDoctrine($mockDoctrine);
        $operator->getPaymentTrackingData($mockEntry);
    }

    /**
     * 測試取得單筆查詢時需要的參數但未帶入verify_ip
     */
    public function testGetPaymentTrackingDataButNoVerifyIpSpecified()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No verify_ip specified',
            150180178
        );

        $paymentGateway = new PaymentGateway('CBPay', '網銀在線', 'http://cbpay.com', 1);
        $paymentGateway->setLabel('CBPay');
        $paymentGateway->setAutoReop(true);
        $merchant = new Merchant($paymentGateway, 1, 'CBPayTest', '12345', '6', '156');

        $mockEntityRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findBy', 'findOneBy'])
            ->getMock();
        $mockEntityRepository->expects($this->any())
            ->method('findBy')
            ->willReturn([]);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'getRepository'])
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($merchant);
        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockEntityRepository);

        $mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();
        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CashDepositEntry')
            ->disableOriginalConstructor()
            ->getMock();

        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['getParameter'])
            ->getMock();

        $operator = new Operator();
        $operator->setContainer($container);
        $operator->setDoctrine($mockDoctrine);
        $operator->getPaymentTrackingData($mockEntry);
    }

    /**
     * 測試入款查詢解密驗證成功
     */
    public function testDepositExamineVerifySuccess()
    {
        $paymentGateway = new PaymentGateway('CBPay', '網銀在線', 'http://cbpay.com', 1);
        $merchant = new Merchant($paymentGateway, 1, 'CBPayTest', '12345', '6', '156');
        $merchantPublicKey = new MerchantKey($merchant, 'public', 'testPublicKey');

        $mockEntityRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findBy', 'findOneBy'])
            ->getMock();
        $mockEntityRepository->expects($this->any())
            ->method('findBy')
            ->willReturn([]);
        $mockEntityRepository->expects($this->at(2))
            ->method('findOneBy')
            ->willReturn($merchantPublicKey);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'getRepository'])
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($merchant);
        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockEntityRepository);

        $mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();
        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $mockCBPay = $this->getMockBuilder('BB\DurianBundle\Payment\CBPay')
            ->disableOriginalConstructor()
            ->getMock();

        $mockOperator = $this->getMockBuilder('BB\DurianBundle\Payment\Operator')
            ->disableOriginalConstructor()
            ->setMethods(['getAvaliablePaymentGateway'])
            ->getMock();
        $mockOperator->expects($this->any())
            ->method('getAvaliablePaymentGateway')
            ->willReturn($mockCBPay);

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CashDepositEntry')
            ->disableOriginalConstructor()
            ->setMethods(['getAt'])
            ->getMock();
        $mockEntry->expects($this->any())
            ->method('getAt')
            ->willReturn(new \DateTime('20150101000000'));

        $mockOperator->setDoctrine($mockDoctrine);
        $mockOperator->depositExamineVerify($mockEntry, []);
    }

    /**
     * 測試入款查詢解密驗證但商家不存在
     */
    public function testDepositExamineVerifyButMerchantNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Merchant found',
            180006
        );

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();
        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CashDepositEntry')
            ->disableOriginalConstructor()
            ->getMock();

        $operator = new Operator();
        $operator->setDoctrine($mockDoctrine);
        $operator->depositExamineVerify($mockEntry, []);
    }

    /**
     * 測試入款查詢解密驗證成功但支付平台不支援查詢解密驗證
     */
    public function testDepositExamineVerifyButPaymentGatewayNotSupportTrackingVerify()
    {
        $this->setExpectedException(
            'RuntimeException',
            'PaymentGateway does not support tracking verify',
            150180164
        );

        $paymentGateway = new PaymentGateway('CBPay', '網銀在線', 'http://cbpay.com', 1);
        $merchant = new Merchant($paymentGateway, 1, 'CBPayTest', '12345', '6', '156');

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find'])
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($merchant);

        $mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();
        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $mockUCFPay = $this->getMockBuilder('BB\DurianBundle\Payment\UCFPay')
            ->disableOriginalConstructor()
            ->getMock();

        $mockOperator = $this->getMockBuilder('BB\DurianBundle\Payment\Operator')
            ->disableOriginalConstructor()
            ->setMethods(['getAvaliablePaymentGateway'])
            ->getMock();
        $mockOperator->expects($this->any())
            ->method('getAvaliablePaymentGateway')
            ->willReturn($mockUCFPay);

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CashDepositEntry')
            ->disableOriginalConstructor()
            ->getMock();

        $mockOperator->setDoctrine($mockDoctrine);
        $mockOperator->depositExamineVerify($mockEntry, []);
    }

    /**
     * 測試入款查詢解密驗證但支付失敗
     */
    public function testDepositExamineVerifyButPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $paymentGateway = new PaymentGateway('CBPay', '網銀在線', 'http://cbpay.com', 1);
        $merchant = new Merchant($paymentGateway, 1, 'CBPayTest', '12345', '6', '156');
        $merchantPublicKey = new MerchantKey($merchant, 'public', 'testPublicKey');

        $mockEntityRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findBy', 'findOneBy'])
            ->getMock();
        $mockEntityRepository->expects($this->any())
            ->method('findBy')
            ->willReturn([]);
        $mockEntityRepository->expects($this->at(2))
            ->method('findOneBy')
            ->willReturn($merchantPublicKey);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'getRepository'])
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($merchant);
        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockEntityRepository);

        $mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();
        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CashDepositEntry')
            ->disableOriginalConstructor()
            ->setMethods(['getAt'])
            ->getMock();
        $mockEntry->expects($this->any())
            ->method('getAt')
            ->willReturn(new \DateTime('20150101000000'));

        $exception = new PaymentConnectionException('Payment failure', 180035, $mockEntry->getId());
        $mockCBPay = $this->getMockBuilder('BB\DurianBundle\Payment\CBPay')
            ->disableOriginalConstructor()
            ->setMethods(['paymentTrackingVerify'])
            ->getMock();
        $mockCBPay->expects($this->any())
            ->method('paymentTrackingVerify')
            ->willThrowException($exception);

        $mockOperator = $this->getMockBuilder('BB\DurianBundle\Payment\Operator')
            ->disableOriginalConstructor()
            ->setMethods(['getAvaliablePaymentGateway'])
            ->getMock();
        $mockOperator->expects($this->any())
            ->method('getAvaliablePaymentGateway')
            ->willReturn($mockCBPay);

        $mockOperator->setDoctrine($mockDoctrine);
        $mockOperator->depositExamineVerify($mockEntry, []);
    }

    /**
     * 測試轉換訂單查詢支付平台返回的編碼成功
     */
    public function testProcessTrackingResponseEncodingSuccess()
    {
        $cbPay = new CBPay();

        $mockPaymentGateway = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentGateway')
            ->disableOriginalConstructor()
            ->getMock();

        $mockMerchant = $this->getMockBuilder('BB\DurianBundle\Entity\Merchant')
            ->disableOriginalConstructor()
            ->getMock();
        $mockMerchant->expects($this->any())
            ->method('getPaymentGateway')
            ->willReturn($mockPaymentGateway);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($mockMerchant);

        $mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();
        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CashDepositEntry')
            ->disableOriginalConstructor()
            ->getMock();

        $mockOperator = $this->getMockBuilder('BB\DurianBundle\Payment\Operator')
            ->disableOriginalConstructor()
            ->setMethods(['getAvaliablePaymentGateway'])
            ->getMock();
        $mockOperator->expects($this->any())
            ->method('getAvaliablePaymentGateway')
            ->willReturn($cbPay);

        // 將支付平台的返回做編碼
        $body = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
            "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html xmlns="http://www.w3.org/1999/xhtml">
            <body>
            <form name="PAResForm" action="" method="post">
            <input type=hidden name="v_oid" value="20130428-12345-201304280000000001">
            <input type=hidden name="v_pmode" value="民生银行">
            <input type=hidden name="v_pstatus" value="20">
            <input type=hidden name="v_pstring" value="支付完成 ">
            <input type=hidden name="v_amount" value="100.00" pattern="######0.00">
            <input type=hidden name="v_moneytype" value="CNY">
            <input type=hidden name="v_md5str" value="FA649F788FD7C16212B52BAB39389C4C">
            <input type=hidden name="v_md5info" value="9f66fa78ad41d7cd3f7e6b9ce272ca0d">
            <input type=hidden name="remark1" value="">
            <input type=hidden name="remark2" value="">
            <input type="hidden" name="v_ver" value="4.0"/>
            </form>
            </body>
            </html>';
        $encodedBody = base64_encode(iconv('UTF-8', 'GB2312', $body));

        $encodedResponse = [
            'header' => [
                'server' => 'nginx',
                'content-type' => 'text/html; charset=GB2312'
            ],
            'body' => $encodedBody
        ];
        $mockOperator->setDoctrine($mockDoctrine);
        $trackingResponse = $mockOperator->processTrackingResponseEncoding($mockEntry, $encodedResponse);

        $this->assertEquals($encodedResponse['header'], $trackingResponse['header']);
        $this->assertEquals($body, $trackingResponse['body']);
    }

    /**
     * 測試取得實名認證結果但找不到商家
     */
    public function testRealNameAuthButNoMerchantFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Merchant found',
            180006
        );

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();
        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CashDepositEntry')
            ->disableOriginalConstructor()
            ->getMock();

        $operator = new Operator();
        $operator->setDoctrine($mockDoctrine);
        $operator->realNameAuth($mockEntry, []);
    }

    /**
     * 測試取得實名認證結果但商家實名認證開關不存在
     */
    public function testRealNameAuthButNoMerchantExtraFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Merchant have no need to authenticate',
            150180184
        );

        $paymentGateway = new PaymentGateway('BeeCloud', 'BeeCloud', '', 1);
        $merchant = new Merchant($paymentGateway, 1, 'BeeCloudTest', '12345', '6', '156');

        $mockEntityRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'getRepository'])
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($merchant);
        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockEntityRepository);

        $mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();
        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CashDepositEntry')
            ->disableOriginalConstructor()
            ->getMock();

        $operator = new Operator();
        $operator->setDoctrine($mockDoctrine);
        $operator->realNameAuth($mockEntry, []);
    }

    /**
     * 測試取得實名認證結果但商家不需要實名認證
     */
    public function testRealNameAuthButMerchantHaveNoNeedToAuthenticate()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Merchant have no need to authenticate',
            150180184
        );

        $paymentGateway = new PaymentGateway('BeeCloud', 'BeeCloud', '', 1);
        $merchant = new Merchant($paymentGateway, 1, 'BeeCloudTest', '12345', '6', '156');

        $mockMerchantExtra = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantExtra')
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMock();
        $mockMerchantExtra->expects($this->any())
            ->method('getValue')
            ->willReturn(0);

        $mockEntityRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $mockEntityRepository->expects($this->any())
            ->method('findOneBy')
            ->willReturn($mockMerchantExtra);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'getRepository'])
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($merchant);
        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockEntityRepository);

        $mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();
        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CashDepositEntry')
            ->disableOriginalConstructor()
            ->getMock();

        $operator = new Operator();
        $operator->setDoctrine($mockDoctrine);
        $operator->realNameAuth($mockEntry, []);
    }

    /**
     * 測試取得實名認證結果但支付平台不支援實名認證
     */
    public function testRealNameAuthButPaymentGatewayDoesNotSupportRealNameAuthentication()
    {
        $this->setExpectedException(
            'RuntimeException',
            'PaymentGateway does not support real name authentication',
            150180185
        );

        $paymentGateway = new PaymentGateway('CBPay', 'CBPay', '', 1);
        $paymentGateway->setLabel('CBPay');

        $merchant = new Merchant($paymentGateway, 1, 'CBPayTest', '12345', '6', '156');

        $mockMerchantExtra = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantExtra')
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMock();
        $mockMerchantExtra->expects($this->any())
            ->method('getValue')
            ->willReturn(1);

        $mockEntityRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $mockEntityRepository->expects($this->any())
            ->method('findOneBy')
            ->willReturn($mockMerchantExtra);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'getRepository'])
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($merchant);
        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockEntityRepository);

        $mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();
        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CashDepositEntry')
            ->disableOriginalConstructor()
            ->getMock();

        $operator = new Operator();
        $operator->setDoctrine($mockDoctrine);
        $operator->realNameAuth($mockEntry, []);
    }

    /**
     * 測試取得實名認證結果成功
     */
    public function testRealNameAuthSuccess()
    {
        $paymentGateway = new PaymentGateway('BeeCloud', 'BeeCloud', '', 1);
        $paymentGateway->setLabel('BeeCloud');

        $merchant = new Merchant($paymentGateway, 1, 'BeeCloudTest', '12345', '6', '156');

        $mockMerchantExtra = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantExtra')
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMock();
        $mockMerchantExtra->expects($this->any())
            ->method('getValue')
            ->willReturn(1);

        $mockEntityRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $mockEntityRepository->expects($this->any())
            ->method('findOneBy')
            ->willReturn($mockMerchantExtra);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'getRepository'])
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($merchant);
        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockEntityRepository);

        $mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();
        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CashDepositEntry')
            ->disableOriginalConstructor()
            ->getMock();

        $mockBeeCloud = $this->getMockBuilder('BB\DurianBundle\Payment\BeeCloud')
            ->disableOriginalConstructor()
            ->getMock();

        $mockOperator = $this->getMockBuilder('BB\DurianBundle\Payment\Operator')
            ->disableOriginalConstructor()
            ->setMethods(['getAvaliablePaymentGateway'])
            ->getMock();
        $mockOperator->expects($this->any())
            ->method('getAvaliablePaymentGateway')
            ->willReturn($mockBeeCloud);

        $mockOperator->setContainer($this->container);
        $mockOperator->setDoctrine($mockDoctrine);
        $mockOperator->realNameAuth($mockEntry, []);
    }

     /**
     * 測試取得租卡入款實名認證結果但找不到租卡商家
     */
    public function testCardAuthButNoMerchantCardFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No MerchantCard found',
            150180188
        );

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();
        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CardDepositEntry')
            ->disableOriginalConstructor()
            ->getMock();

        $operator = new Operator();
        $operator->setDoctrine($mockDoctrine);
        $operator->cardRealNameAuth($mockEntry, []);
    }

    /**
     * 測試取得租卡入款實名認證結果但租卡商家實名認證開關不存在
     */
    public function testCardRealNameAuthButNoMerchantCardExtraFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'MerchantCard have no need to authenticate',
            150180189
        );

        $paymentGateway = new PaymentGateway('BeeCloud', 'BeeCloud', '', 1);
        $merchantCard = new MerchantCard($paymentGateway, 'BeeCloudTest', '12345', '6', '156');

        $mockEntityRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'getRepository'])
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($merchantCard);
        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockEntityRepository);

        $mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();
        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CardDepositEntry')
            ->disableOriginalConstructor()
            ->getMock();

        $operator = new Operator();
        $operator->setDoctrine($mockDoctrine);
        $operator->cardRealNameAuth($mockEntry, []);
    }

    /**
     * 測試取得租卡入款實名認證結果但租卡商家不需要實名認證
     */
    public function testCardRealNameAuthButMerchantCardHaveNoNeedToAuthenticate()
    {
        $this->setExpectedException(
            'RuntimeException',
            'MerchantCard have no need to authenticate',
            150180189
        );

        $paymentGateway = new PaymentGateway('BeeCloud', 'BeeCloud', '', 1);
        $merchantCard = new MerchantCard($paymentGateway, 'BeeCloudTest', '12345', '6', '156');

        $mockMerchantCardExtra = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCardExtra')
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMock();
        $mockMerchantCardExtra->expects($this->any())
            ->method('getValue')
            ->willReturn(0);

        $mockEntityRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $mockEntityRepository->expects($this->any())
            ->method('findOneBy')
            ->willReturn($mockMerchantCardExtra);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'getRepository'])
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($merchantCard);
        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockEntityRepository);

        $mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();
        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CardDepositEntry')
            ->disableOriginalConstructor()
            ->getMock();

        $operator = new Operator();
        $operator->setDoctrine($mockDoctrine);
        $operator->cardRealNameAuth($mockEntry, []);
    }

    /**
     * 測試取得租卡入款實名認證結果但支付平台不支援實名認證
     */
    public function testCardRealNameAuthButPaymentGatewayDoesNotSupportRealNameAuthentication()
    {
        $this->setExpectedException(
            'RuntimeException',
            'PaymentGateway does not support real name authentication',
            150180185
        );

        $paymentGateway = new PaymentGateway('CBPay', 'CBPay', '', 1);
        $paymentGateway->setLabel('CBPay');

        $merchantCard = new MerchantCard($paymentGateway, 'CBPayTest', '12345', '6', '156');

        $mockMerchantCardExtra = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCardExtra')
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMock();
        $mockMerchantCardExtra->expects($this->any())
            ->method('getValue')
            ->willReturn(1);

        $mockEntityRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $mockEntityRepository->expects($this->any())
            ->method('findOneBy')
            ->willReturn($mockMerchantCardExtra);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'getRepository'])
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($merchantCard);
        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockEntityRepository);

        $mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();
        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CardDepositEntry')
            ->disableOriginalConstructor()
            ->getMock();

        $operator = new Operator();
        $operator->setDoctrine($mockDoctrine);
        $operator->cardRealNameAuth($mockEntry, []);
    }

    /**
     * 測試取得租卡入款實名認證結果成功
     */
    public function testCardRealNameAuthSuccess()
    {
        $paymentGateway = new PaymentGateway('BeeCloud', 'BeeCloud', '', 1);
        $paymentGateway->setLabel('BeeCloud');

        $merchantCard = new MerchantCard($paymentGateway, 'BeeCloudTest', '12345', '6', '156');

        $mockMerchantCardExtra = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCardExtra')
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMock();
        $mockMerchantCardExtra->expects($this->any())
            ->method('getValue')
            ->willReturn(1);

        $mockEntityRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $mockEntityRepository->expects($this->any())
            ->method('findOneBy')
            ->willReturn($mockMerchantCardExtra);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'getRepository'])
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($merchantCard);
        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockEntityRepository);

        $mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();
        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $mockEntry = $this->getMockBuilder('BB\DurianBundle\Entity\CardDepositEntry')
            ->disableOriginalConstructor()
            ->getMock();

        $mockBeeCloud = $this->getMockBuilder('BB\DurianBundle\Payment\BeeCloud')
            ->disableOriginalConstructor()
            ->getMock();

        $mockOperator = $this->getMockBuilder('BB\DurianBundle\Payment\Operator')
            ->disableOriginalConstructor()
            ->setMethods(['getAvaliablePaymentGateway'])
            ->getMock();
        $mockOperator->expects($this->any())
            ->method('getAvaliablePaymentGateway')
            ->willReturn($mockBeeCloud);

        $mockOperator->setContainer($this->container);
        $mockOperator->setDoctrine($mockDoctrine);
        $mockOperator->cardRealNameAuth($mockEntry, []);
    }

    /**
     * 測試重整公鑰失敗
     */
    public function testRefreshPublicKey()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Get public key failure',
            150180210
        );

        $publicKey = 'abc';

        $operator = new Operator();
        $operator->refreshRsaKey(base64_encode($publicKey), '');
    }

    /**
     * 測試重整私鑰失敗
     */
    public function testRefreshPrivateKey()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Get private key failure',
            150180211
        );

        $privateKey = 'def';

        $operator = new Operator();
        $operator->refreshRsaKey('', base64_encode($privateKey));
    }
}
