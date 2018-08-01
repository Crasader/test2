<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Neteller;
use Buzz\Message\Response;

class NetellerTest extends DurianTestCase
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * @var \Buzz\Client\Curl
     */
    private $client;

    public function setUp()
    {
        parent::setUp();

        $mockLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Payment')
            ->disableOriginalConstructor()
            ->setMethods(['record'])
            ->getMock();

        $mockLogger->expects($this->any())
            ->method('record')
            ->willReturn(null);

        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $this->container->expects($this->any())
            ->method('get')
            ->willReturn($mockLogger);

        $this->client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
    }

    /**
     * 測試加密缺少回傳shop_url
     */
    public function testGetEncodeDataWithoutShopUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No shop_url specified',
            180157
        );

        $neteller = new Neteller();

        $sourceData = ['shop_url' => ''];

        $neteller->setOptions($sourceData);
        $neteller->getVerifyData([]);
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $sourceData = [
            'shop_url' => 'http://localhost/pay/',
            'paymentGatewayId' => '80',
            'orderId' => '201512241611123456'
        ];

        $neteller = new Neteller();
        $neteller->setOptions($sourceData);
        $encodeData = $neteller->getVerifyData([]);

        $actUrl = sprintf(
            '%sreturn.php?payment_id=%s&ref_id=%s',
            'http://localhost/pay/',
            '80',
            '201512241611123456'
        );

        $this->assertEquals($actUrl, $encodeData['act_url']);
    }

    /**
     * 測試解密驗證缺少回傳email或account_id
     */
    public function testVerifyWithoutEmailAndAccountId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $neteller = new Neteller();

        $sourceData = [
            'email_account_id' => '',
            'email' => '',
            'account_id' => ''
        ];

        $neteller->setOptions($sourceData);
        $neteller->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證缺少回傳secure_id
     */
    public function testVerifyWithoutSecureId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $neteller = new Neteller();

        $sourceData = [
            'email_account_id' => '453501020503',
            'email' => 'netellertest_EUR@neteller.com',
            'account_id' => '453501020503',
            'secure_id' => ''
        ];

        $neteller->setOptions($sourceData);
        $neteller->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台缺少回傳VerifyUrl
     */
    public function testVerifyWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $neteller = new Neteller();

        $sourceData = [
            'email_account_id' => '453501020503',
            'email' => 'netellertest_EUR@neteller.com',
            'account_id' => '453501020503',
            'secure_id' => '908379',
            'verify_url' => '',
            'verify_ip' => '1.2.3.4'
        ];

        $neteller->setOptions($sourceData);
        $neteller->verifyOrderPayment([]);
    }

    /**
     * 測試返回時帶入幣別不合法
     */
    public function testReturnIllegalOrderCurrency()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Illegal Order currency',
            180083
        );

        $neteller = new Neteller();

        $sourceData = [
            'email_account_id' => '453501020503',
            'email' => 'netellertest_EUR@neteller.com',
            'account_id' => '453501020503',
            'secure_id' => '908379',
            'verify_url' => 'www.neteller.com',
            'verify_ip' => '1.2.3.4'
        ];

        $entry = ['currency' => 'CNY'];

        $neteller->setOptions($sourceData);
        $neteller->verifyOrderPayment($entry);
    }

    /**
     * 測試解密驗證缺少回傳商號額外設定
     */
    public function testVerifyWithoutMerchantExtra()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $neteller = new Neteller();

        $sourceData = [
            'email_account_id' => '453501020503',
            'email' => 'netellertest_EUR@neteller.com',
            'account_id' => '453501020503',
            'secure_id' => '908379',
            'verify_url' => 'www.neteller.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => []
        ];

        $entry = ['currency' => 'EUR'];

        $neteller->setOptions($sourceData);
        $neteller->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時支付平台回傳結果為空
     */
    public function testReturnEmptyPaymentGatewayResponse()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Empty Payment Gateway response',
            180089
        );

        $respone = new Response();
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader("Content-Type:application/json;charset=UTF-8");

        $neteller = new Neteller();
        $neteller->setContainer($this->container);
        $neteller->setClient($this->client);
        $neteller->setResponse($respone);

        $merchantExtra = [
            'client_id' => '',
            'client_secret' => ''
        ];

        $sourceData = [
            'email_account_id' => '453501020503',
            'email' => 'netellertest_EUR@neteller.com',
            'account_id' => '453501020503',
            'secure_id' => '908379',
            'verify_url' => 'www.neteller.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => $merchantExtra
        ];

        $entry = ['currency' => 'EUR'];

        $neteller->setOptions($sourceData);
        $neteller->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時支付平台連線異常
     */
    public function testReturnPaymentGatewayConnectionError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment Gateway connection failure',
            180088
        );

        $exception = new \Exception('Payment Gateway connection failure', 180088);
        $this->client->expects($this->any())
            ->method('send')
            ->willThrowException($exception);

        $neteller = new Neteller();
        $neteller->setContainer($this->container);
        $neteller->setClient($this->client);

        $merchantExtra = [
            'client_id' => '',
            'client_secret' => ''
        ];

        $sourceData = [
            'email_account_id' => '453501020503',
            'email' => 'netellertest_EUR@neteller.com',
            'account_id' => '453501020503',
            'secure_id' => '908379',
            'verify_url' => 'www.neteller.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => $merchantExtra
        ];

        $entry = ['currency' => 'EUR'];

        $neteller->setOptions($sourceData);
        $neteller->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時對外返回結果錯誤
     */
    public function testReturnConnectionPaymentGatewayError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'unsupported_grant_type',
            180130
        );

        $res = [
            'error' => 'unsupported_grant_type'
        ];
        $result = json_encode($res);

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader("Content-Type:application/json;charset=UTF-8");

        $neteller = new Neteller();
        $neteller->setContainer($this->container);
        $neteller->setClient($this->client);
        $neteller->setResponse($respone);

        $merchantExtra = [
            'client_id' => '',
            'client_secret' => ''
        ];

        $sourceData = [
            'email_account_id' => '453501020503',
            'email' => 'netellertest_EUR@neteller.com',
            'account_id' => '453501020503',
            'secure_id' => '908379',
            'verify_url' => 'www.neteller.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => $merchantExtra
        ];

        $entry = [
            'currency' => 'EUR',
            'id' => '201502241611123456',
            'amount' => '1.00'
        ];

        $neteller->setOptions($sourceData);
        $neteller->verifyOrderPayment($entry);
    }

    /**
     * 測試解密驗證缺少回傳Token
     */
    public function testVerifyWithoutToken()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $res = [
            'tokenType' => 'Bearer',
            'expiresIn' => 300
        ];
        $result = json_encode($res);

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader("Content-Type:application/json;charset=UTF-8");

        $neteller = new Neteller();
        $neteller->setContainer($this->container);
        $neteller->setClient($this->client);
        $neteller->setResponse($respone);

        $merchantExtra = [
            'client_id' => '',
            'client_secret' => ''
        ];

        $sourceData = [
            'email_account_id' => '453501020503',
            'email' => 'netellertest_EUR@neteller.com',
            'account_id' => '453501020503',
            'secure_id' => '908379',
            'verify_url' => 'www.neteller.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => $merchantExtra
        ];

        $entry = [
            'currency' => 'EUR',
            'id' => '201502241611123456',
            'amount' => '1.00'
        ];

        $neteller->setOptions($sourceData);
        $neteller->verifyOrderPayment($entry);
    }

    /**
     * 測試解密驗證缺少回傳TokenType
     */
    public function testVerifyWithoutTokenType()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $res = [
            'accessToken' => '0.AQAAAUW-9Tu4AAAEk-BNppPGFNwoWODBcOznHwA.skB0dDtyMrW4xCZJw__FGNtL-08',
            'expiresIn' => 300
        ];
        $result = json_encode($res);

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader("Content-Type:application/json;charset=UTF-8");

        $neteller = new Neteller();
        $neteller->setContainer($this->container);
        $neteller->setClient($this->client);
        $neteller->setResponse($respone);

        $merchantExtra = [
            'client_id' => '',
            'client_secret' => ''
        ];

        $sourceData = [
            'email_account_id' => '453501020503',
            'email' => 'netellertest_EUR@neteller.com',
            'account_id' => '453501020503',
            'secure_id' => '908379',
            'verify_url' => 'www.neteller.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => $merchantExtra
        ];

        $entry = [
            'currency' => 'EUR',
            'id' => '201502241611123456',
            'amount' => '1.00'
        ];

        $neteller->setOptions($sourceData);
        $neteller->verifyOrderPayment($entry);
    }

    /**
     * 測試解密驗證缺少回傳支付狀態
     */
    public function testVerifyWithoutStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $res = [
            'accessToken' => '0.AQAAAUW-9Tu4AAAEk-BNppPGFNwoWODBcOznHwA.skB0dDtyMrW4xCZJw__FGNtL-08',
            'tokenType' => 'Bearer',
            'expiresIn' => 300,
            'accountProfile' => [
                'accountId' => '453501020503'
            ],
            'transaction' => [
                'merchantRefId' => '20140901103803',
                'amount' => 5000,
                'currency' => 'EUR'
            ]
        ];
        $result = json_encode($res);

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader("Content-Type:application/json;charset=UTF-8");

        $neteller = new Neteller();
        $neteller->setContainer($this->container);
        $neteller->setClient($this->client);
        $neteller->setResponse($respone);

        $merchantExtra = [
            'client_id' => '',
            'client_secret' => ''
        ];

        $sourceData = [
            'email_account_id' => '453501020503',
            'email' => 'netellertest_EUR@neteller.com',
            'account_id' => '453501020503',
            'secure_id' => '908379',
            'verify_url' => 'www.neteller.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => $merchantExtra
        ];

        $entry = [
            'currency' => 'EUR',
            'id' => '201502241611123456',
            'amount' => '1.00'
        ];

        $neteller->setOptions($sourceData);
        $neteller->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時回傳訂單處理中
     */
    public function testReturnOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $res = [
            'accessToken' => '0.AQAAAUW-9Tu4AAAEk-BNppPGFNwoWODBcOznHwA.skB0dDtyMrW4xCZJw__FGNtL-08',
            'tokenType' => 'Bearer',
            'expiresIn' => 300,
            'accountProfile' => [
                'accountId' => '453501020503'
            ],
            'transaction' => [
                'merchantRefId' => '20140901103803',
                'amount' => 5000,
                'currency' => 'EUR',
                'status' => 'pending'
            ]
        ];
        $result = json_encode($res);

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader("Content-Type:application/json;charset=UTF-8");

        $neteller = new Neteller();
        $neteller->setContainer($this->container);
        $neteller->setClient($this->client);
        $neteller->setResponse($respone);

        $merchantExtra = [
            'client_id' => '',
            'client_secret' => ''
        ];

        $sourceData = [
            'email_account_id' => '453501020503',
            'email' => 'netellertest_EUR@neteller.com',
            'account_id' => '453501020503',
            'secure_id' => '908379',
            'verify_url' => 'www.neteller.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => $merchantExtra
        ];

        $entry = [
            'currency' => 'EUR',
            'id' => '201502241611123456',
            'amount' => '1.00'
        ];

        $neteller->setOptions($sourceData);
        $neteller->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時訂單已取消
     */
    public function testReturnOrderHasBeenCancelled()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order has been cancelled',
            180063
        );

        $res = [
            'accessToken' => '0.AQAAAUW-9Tu4AAAEk-BNppPGFNwoWODBcOznHwA.skB0dDtyMrW4xCZJw__FGNtL-08',
            'tokenType' => 'Bearer',
            'expiresIn' => 300,
            'accountProfile' => [
                'accountId' => '453501020503'
            ],
            'transaction' => [
                'merchantRefId' => '20140901103803',
                'amount' => 5000,
                'currency' => 'EUR',
                'status' => 'cancelled'
            ]
        ];
        $result = json_encode($res);

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader("Content-Type:application/json;charset=UTF-8");

        $neteller = new Neteller();
        $neteller->setContainer($this->container);
        $neteller->setClient($this->client);
        $neteller->setResponse($respone);

        $merchantExtra = [
            'client_id' => '',
            'client_secret' => ''
        ];

        $sourceData = [
            'email_account_id' => '453501020503',
            'email' => 'netellertest_EUR@neteller.com',
            'account_id' => '453501020503',
            'secure_id' => '908379',
            'verify_url' => 'www.neteller.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => $merchantExtra
        ];

        $entry = [
            'currency' => 'EUR',
            'id' => '201502241611123456',
            'amount' => '1.00'
        ];

        $neteller->setOptions($sourceData);
        $neteller->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時支付失敗
     */
    public function testReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $res = [
            'accessToken' => '0.AQAAAUW-9Tu4AAAEk-BNppPGFNwoWODBcOznHwA.skB0dDtyMrW4xCZJw__FGNtL-08',
            'tokenType' => 'Bearer',
            'expiresIn' => 300,
            'accountProfile' => [
                'accountId' => '453501020503'
            ],
            'transaction' => [
                'merchantRefId' => '20140901103803',
                'amount' => 5000,
                'currency' => 'EUR',
                'status' => 'accept'
            ]
        ];
        $result = json_encode($res);

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader("Content-Type:application/json;charset=UTF-8");

        $neteller = new Neteller();
        $neteller->setContainer($this->container);
        $neteller->setClient($this->client);
        $neteller->setResponse($respone);

        $merchantExtra = [
            'client_id' => '',
            'client_secret' => ''
        ];

        $sourceData = [
            'email_account_id' => '453501020503',
            'email' => 'netellertest_EUR@neteller.com',
            'account_id' => '453501020503',
            'secure_id' => '908379',
            'verify_url' => 'www.neteller.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => $merchantExtra
        ];

        $entry = [
            'currency' => 'EUR',
            'id' => '201502241611123456',
            'amount' => '1.00'
        ];

        $neteller->setOptions($sourceData);
        $neteller->verifyOrderPayment($entry);
    }

    /**
     * 測試解密驗證缺少回傳Neteller訂單號
     */
    public function testReturnWithoutTransactionId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $res = [
            'accessToken' => '0.AQAAAUW-9Tu4AAAEk-BNppPGFNwoWODBcOznHwA.skB0dDtyMrW4xCZJw__FGNtL-08',
            'tokenType' => 'Bearer',
            'expiresIn' => 300,
            'accountProfile' => [
                'accountId' => '453501020503'
            ],
            'transaction' => [
                'merchantRefId' => '20140901103803',
                'amount' => 5000,
                'currency' => 'EUR',
                'status' => 'accepted'
            ]
        ];
        $result = json_encode($res);

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader("Content-Type:application/json;charset=UTF-8");

        $neteller = new Neteller();
        $neteller->setContainer($this->container);
        $neteller->setClient($this->client);
        $neteller->setResponse($respone);

        $merchantExtra = [
            'client_id' => '',
            'client_secret' => ''
        ];

        $sourceData = [
            'email_account_id' => '453501020503',
            'email' => 'netellertest_EUR@neteller.com',
            'account_id' => '453501020503',
            'secure_id' => '908379',
            'verify_url' => 'www.neteller.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => $merchantExtra
        ];

        $entry = [
            'currency' => 'EUR',
            'id' => '201502241611123456',
            'amount' => '1.00'
        ];

        $neteller->setOptions($sourceData);
        $neteller->verifyOrderPayment($entry);
    }

    /**
     * 測試解密驗證支付成功
     */
    public function testVerifySuccess()
    {
        $res = [
            'accessToken' => '0.AQAAAUW-9Tu4AAAEk-BNppPGFNwoWODBcOznHwA.skB0dDtyMrW4xCZJw__FGNtL-08',
            'tokenType' => 'Bearer',
            'expiresIn' => 300,
            'accountProfile' => [
                'accountId' => '453501020503'
            ],
            'transaction' => [
                'merchantRefId' => '20140901103803',
                'amount' => 5000,
                'currency' => 'EUR',
                'status' => 'accepted',
                'id' => '100458204510460'
            ]
        ];
        $result = json_encode($res);
        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader("Content-Type:application/json;charset=UTF-8");

        $mockCde = $this->getMockBuilder('BB\DurianBundle\Entity\CashDepositEntry')
            ->disableOriginalConstructor()
            ->setMethods(['setRefId'])
            ->getMock();
        $mockCde->expects($this->any())
            ->method('setRefId')
            ->willReturn($mockCde);

        $mockRepo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $mockRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn($mockCde);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository', 'flush'])
            ->getMock();
        $mockEm->expects($this->at(0))
            ->method('getRepository')
            ->willReturn($mockRepo);

        $mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();
        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $mockLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Payment')
            ->disableOriginalConstructor()
            ->setMethods(['record'])
            ->getMock();

        $getMap = [
            ['durian.payment_logger', 1, $mockLogger],
            ['doctrine', 1, $mockDoctrine]
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        $neteller = new Neteller();
        $neteller->setContainer($mockContainer);
        $neteller->setClient($this->client);
        $neteller->setResponse($respone);

        $merchantExtra = [
            'client_id' => '',
            'client_secret' => ''
        ];

        $sourceData = [
            'email_account_id' => '453501020503',
            'email' => 'netellertest_EUR@neteller.com',
            'account_id' => '453501020503',
            'secure_id' => '908379',
            'verify_url' => 'www.neteller.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => $merchantExtra
        ];

        $entry = [
            'currency' => 'EUR',
            'id' => '201502241611123456',
            'amount' => '1.00'
        ];

        $neteller->setOptions($sourceData);
        $neteller->verifyOrderPayment($entry);

        $this->assertEquals('success', $neteller->getMsg());
    }

    /**
     * 測試出款缺少回傳VerifyUrl
     */
    public function testWithdrawWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $neteller = new Neteller();

        $options = [
            'verify_ip' => '',
            'verify_url' => ''
        ];
        $neteller->setOptions($options);

        $neteller->withdrawPayment([]);
    }

    /**
     * 測試出款返回時帶入幣別不合法
     */
    public function testWithdrawReturnIllegalOrderCurrency()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Illegal Order currency',
            180083
        );

        $neteller = new Neteller();

        $options = [
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.neteller.com'
        ];
        $neteller->setOptions($options);

        $entry = [
            'account' => '',
            'id' => '',
            'auto_withdraw_amount' => '',
            'currency' => 'CNY'
        ];
        $neteller->withdrawPayment($entry);
    }

    /**
     * 測試出款缺少回傳商號額外設定
     */
    public function testWithdrawWithoutMerchantExtra()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $neteller = new Neteller();

        $options = [
            'merchant_extra' => [],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.neteller.com'
        ];
        $neteller->setOptions($options);

        $entry = [
            'account' => '',
            'id' => '',
            'auto_withdraw_amount' => '',
            'currency' => 'EUR'
        ];
        $neteller->withdrawPayment($entry);
    }

    /**
     * 測試出款返回時支付平台回傳結果為空
     */
    public function testWithdrawReturnEmptyPaymentGatewayResponse()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Empty Payment Gateway response',
            180089
        );

        $respone = new Response();
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader("Content-Type:application/json;charset=UTF-8");

        $neteller = new Neteller();
        $neteller->setContainer($this->container);
        $neteller->setClient($this->client);
        $neteller->setResponse($respone);

        $merchantExtra = [
            'client_id' => '',
            'client_secret' => ''
        ];

        $options = [
            'merchant_extra' => $merchantExtra,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.neteller.com'
        ];
        $neteller->setOptions($options);

        $entry = [
            'account' => '',
            'id' => '',
            'auto_withdraw_amount' => '',
            'currency' => 'EUR'
        ];
        $neteller->withdrawPayment($entry);
    }

    /**
     * 測試出款對外返回結果錯誤
     */
    public function testWithdrawConnectionPaymentGatewayError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'unsupported_grant_type',
            180124
        );

        $res = ['error' => 'unsupported_grant_type'];
        $result = json_encode($res);

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader("Content-Type:application/json;charset=UTF-8");

        $neteller = new Neteller();
        $neteller->setContainer($this->container);
        $neteller->setClient($this->client);
        $neteller->setResponse($respone);

        $merchantExtra = [
            'client_id' => '',
            'client_secret' => ''
        ];

        $options = [
            'merchant_extra' => $merchantExtra,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.neteller.com'
        ];
        $neteller->setOptions($options);

        $entry = [
            'account' => '',
            'id' => '',
            'auto_withdraw_amount' => '',
            'currency' => 'EUR'
        ];
        $neteller->withdrawPayment($entry);
    }

    /**
     * 測試出款缺少回傳Token
     */
    public function testWithdrawWithoutToken()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $res = [
            'tokenType' => 'Bearer',
            'expiresIn' => 300
        ];
        $result = json_encode($res);

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader("Content-Type:application/json;charset=UTF-8");

        $neteller = new Neteller();
        $neteller->setContainer($this->container);
        $neteller->setClient($this->client);
        $neteller->setResponse($respone);

        $merchantExtra = [
            'client_id' => '',
            'client_secret' => ''
        ];

        $options = [
            'merchant_extra' => $merchantExtra,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.neteller.com'
        ];
        $neteller->setOptions($options);

        $entry = [
            'account' => '',
            'id' => '',
            'auto_withdraw_amount' => '',
            'currency' => 'EUR'
        ];
        $neteller->withdrawPayment($entry);
    }

    /**
     * 測試出款缺少回傳TokenType
     */
    public function testWithdrawWithoutTokenType()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $res = [
            'accessToken' => '0.AQAAAUW-9Tu4AAAEk-BNppPGFNwoWODBcOznHwA.skB0dDtyMrW4xCZJw__FGNtL-08',
            'expiresIn' => 300
        ];
        $result = json_encode($res);

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader("Content-Type:application/json;charset=UTF-8");

        $neteller = new Neteller();
        $neteller->setContainer($this->container);
        $neteller->setClient($this->client);
        $neteller->setResponse($respone);

        $merchantExtra = [
            'client_id' => '',
            'client_secret' => ''
        ];

        $options = [
            'merchant_extra' => $merchantExtra,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.neteller.com'
        ];
        $neteller->setOptions($options);

        $entry = [
            'account' => '',
            'id' => '',
            'auto_withdraw_amount' => '',
            'currency' => 'EUR'
        ];
        $neteller->withdrawPayment($entry);
    }

    /**
     * 測試出款缺少回傳支付狀態
     */
    public function testWithdrawWithoutStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $res = [
            'accessToken' => '0.AQAAAUW-9Tu4AAAEk-BNppPGFNwoWODBcOznHwA.skB0dDtyMrW4xCZJw__FGNtL-08',
            'tokenType' => 'Bearer',
            'expiresIn' => 300,
            'payeeProfile' => ['accountId' => '453501020503'],
            'transaction' => [
                'merchantRefId' => '20140901103803',
                'amount' => 5000,
                'currency' => 'EUR'
            ]
        ];
        $result = json_encode($res);

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader("Content-Type:application/json;charset=UTF-8");

        $neteller = new Neteller();
        $neteller->setContainer($this->container);
        $neteller->setClient($this->client);
        $neteller->setResponse($respone);

        $merchantExtra = [
            'client_id' => '',
            'client_secret' => ''
        ];

        $options = [
            'merchant_extra' => $merchantExtra,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.neteller.com'
        ];
        $neteller->setOptions($options);

        $entry = [
            'account' => '',
            'id' => '',
            'auto_withdraw_amount' => '',
            'currency' => 'EUR'
        ];
        $neteller->withdrawPayment($entry);
    }

    /**
     * 測試出款結果回傳訂單處理中
     */
    public function testWithdrawReturnOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $res = [
            'accessToken' => '0.AQAAAUW-9Tu4AAAEk-BNppPGFNwoWODBcOznHwA.skB0dDtyMrW4xCZJw__FGNtL-08',
            'tokenType' => 'Bearer',
            'expiresIn' => 300,
            'payeeProfile' => ['accountId' => '453501020503'],
            'transaction' => [
                'merchantRefId' => '20140901103803',
                'amount' => 5000,
                'currency' => 'EUR',
                'status' => 'pending'
            ]
        ];
        $result = json_encode($res);

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader("Content-Type:application/json;charset=UTF-8");

        $neteller = new Neteller();
        $neteller->setContainer($this->container);
        $neteller->setClient($this->client);
        $neteller->setResponse($respone);

        $merchantExtra = [
            'client_id' => '',
            'client_secret' => ''
        ];

        $options = [
            'merchant_extra' => $merchantExtra,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.neteller.com'
        ];
        $neteller->setOptions($options);

        $entry = [
            'account' => '',
            'id' => '',
            'auto_withdraw_amount' => '',
            'currency' => 'EUR'
        ];
        $neteller->withdrawPayment($entry);
    }

    /**
     * 測試出款返回時訂單已取消
     */
    public function testWithdrawReturnOrderHasBeenCancelled()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order has been cancelled',
            180063
        );

        $res = [
            'accessToken' => '0.AQAAAUW-9Tu4AAAEk-BNppPGFNwoWODBcOznHwA.skB0dDtyMrW4xCZJw__FGNtL-08',
            'tokenType' => 'Bearer',
            'expiresIn' => 300,
            'payeeProfile' => ['accountId' => '453501020503'],
            'transaction' => [
                'merchantRefId' => '20140901103803',
                'amount' => 5000,
                'currency' => 'EUR',
                'status' => 'cancelled'
            ]
        ];
        $result = json_encode($res);

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader("Content-Type:application/json;charset=UTF-8");

        $neteller = new Neteller();
        $neteller->setContainer($this->container);
        $neteller->setClient($this->client);
        $neteller->setResponse($respone);

        $merchantExtra = [
            'client_id' => '',
            'client_secret' => ''
        ];

        $options = [
            'merchant_extra' => $merchantExtra,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.neteller.com'
        ];
        $neteller->setOptions($options);

        $entry = [
            'account' => '',
            'id' => '',
            'auto_withdraw_amount' => '',
            'currency' => 'EUR'
        ];
        $neteller->withdrawPayment($entry);
    }

    /**
     * 測試出款返回支付失敗
     */
    public function testWithdrawReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $res = [
            'accessToken' => '0.AQAAAUW-9Tu4AAAEk-BNppPGFNwoWODBcOznHwA.skB0dDtyMrW4xCZJw__FGNtL-08',
            'tokenType' => 'Bearer',
            'expiresIn' => 300,
            'payeeProfile' => ['accountId' => '453501020503'],
            'transaction' => [
                'merchantRefId' => '20140901103803',
                'amount' => 5000,
                'currency' => 'EUR',
                'status' => 'accept'
            ]
        ];
        $result = json_encode($res);

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader("Content-Type:application/json;charset=UTF-8");

        $neteller = new Neteller();
        $neteller->setContainer($this->container);
        $neteller->setClient($this->client);
        $neteller->setResponse($respone);

        $merchantExtra = [
            'client_id' => '',
            'client_secret' => ''
        ];

        $options = [
            'merchant_extra' => $merchantExtra,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.neteller.com'
        ];
        $neteller->setOptions($options);

        $entry = [
            'account' => '',
            'id' => '',
            'auto_withdraw_amount' => '',
            'currency' => 'EUR'
        ];
        $neteller->withdrawPayment($entry);
    }

    /**
     * 測試出款返回缺少回傳Neteller訂單號
     */
    public function testWithdrawReturnWithoutTransactionId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $res = [
            'accessToken' => '0.AQAAAUW-9Tu4AAAEk-BNppPGFNwoWODBcOznHwA.skB0dDtyMrW4xCZJw__FGNtL-08',
            'tokenType' => 'Bearer',
            'expiresIn' => 300,
            'payeeProfile' => ['accountId' => '453501020503'],
            'transaction' => [
                'merchantRefId' => '20140901103803',
                'amount' => 5000,
                'currency' => 'EUR',
                'status' => 'accepted'
            ]
        ];
        $result = json_encode($res);

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader("Content-Type:application/json;charset=UTF-8");

        $neteller = new Neteller();
        $neteller->setContainer($this->container);
        $neteller->setClient($this->client);
        $neteller->setResponse($respone);

        $merchantExtra = [
            'client_id' => '',
            'client_secret' => ''
        ];

        $options = [
            'merchant_extra' => $merchantExtra,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.neteller.com'
        ];
        $neteller->setOptions($options);

        $entry = [
            'account' => '',
            'id' => '',
            'auto_withdraw_amount' => '',
            'currency' => 'EUR'
        ];
        $neteller->withdrawPayment($entry);
    }

    /**
     * 測試出款支付成功
     */
    public function testWithdrawSuccess()
    {
        $res = [
            'accessToken' => '0.AQAAAUW-9Tu4AAAEk-BNppPGFNwoWODBcOznHwA.skB0dDtyMrW4xCZJw__FGNtL-08',
            'tokenType' => 'Bearer',
            'expiresIn' => 300,
            'payeeProfile' => ['accountId' => '453501020503'],
            'transaction' => [
                'merchantRefId' => '20140901103803',
                'amount' => 5000,
                'currency' => 'EUR',
                'status' => 'accepted',
                'id' => '100458204510460'
            ]
        ];
        $result = json_encode($res);
        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader("Content-Type:application/json;charset=UTF-8");

        $mockCwe = $this->getMockBuilder('BB\DurianBundle\Entity\CashWithdrawEntry')
            ->disableOriginalConstructor()
            ->setMethods(['setRefId'])
            ->getMock();
        $mockCwe->expects($this->any())
            ->method('setRefId')
            ->willReturn($mockCwe);

        $mockRepo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $mockRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn($mockCwe);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository', 'flush'])
            ->getMock();
        $mockEm->expects($this->at(0))
            ->method('getRepository')
            ->willReturn($mockRepo);

        $mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();
        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $mockLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Payment')
            ->disableOriginalConstructor()
            ->setMethods(['record'])
            ->getMock();

        $getMap = [
            ['durian.payment_logger', 1, $mockLogger],
            ['doctrine', 1, $mockDoctrine]
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        $neteller = new Neteller();
        $neteller->setContainer($mockContainer);
        $neteller->setClient($this->client);
        $neteller->setResponse($respone);

        $merchantExtra = [
            'client_id' => '',
            'client_secret' => ''
        ];

        $options = [
            'merchant_extra' => $merchantExtra,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.neteller.com'
        ];
        $neteller->setOptions($options);

        $entry = [
            'account' => '',
            'id' => '',
            'auto_withdraw_amount' => '',
            'currency' => 'EUR'
        ];
        $neteller->withdrawPayment($entry);
    }

    /**
     * 測試取得額外的支付欄位
     */
    public function testGetExtraParams()
    {
        $neteller = new Neteller();

        $extraParams = [
            'email_account_id',
            'secure_id'
        ];

        $this->assertEquals($extraParams, $neteller->getExtraParams());
    }
}
