<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ZsagePay;
use Buzz\Message\Response;

class ZsagePayTest extends DurianTestCase
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
     * 測試支付時缺少私鑰
     */
    public function testPayWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $zsagePay = new ZsagePay();
        $zsagePay->getVerifyData();
    }

    /**
     * 測試支付時未指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $options = ['paymentVendorId' => '1'];

        $zsagePay = new ZsagePay();
        $zsagePay->setPrivateKey('test');
        $zsagePay->setOptions($options);
        $zsagePay->getVerifyData();
    }

    /**
     * 測試支付設定帶入不支援的銀行
     */
    public function testPaySourceNotSupportBank()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $options = [
            'notify_url' => 'http://kai0517.netii.net/',
            'paymentVendorId' => '999',
            'number' => '1000000001',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'orderId' => '201708180000003931',
            'amount' => '1.01',
        ];

        $zsagePay = new ZsagePay();
        $zsagePay->setPrivateKey('test');
        $zsagePay->setOptions($options);
        $zsagePay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://kai0517.netii.net/',
            'paymentVendorId' => '1',
            'number' => '1000000001',
            'orderId' => '201708180000003931',
            'amount' => '1.01',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $zsagePay = new ZsagePay();
        $zsagePay->setPrivateKey('test');
        $zsagePay->setOptions($options);
        $requestData = $zsagePay->getVerifyData();

        $this->assertEquals('1000000001', $requestData['merchantCode']);
        $this->assertEquals('201708180000003931', $requestData['outOrderId']);
        $this->assertEquals(101, $requestData['totalAmount']);
        $this->assertEquals('', $requestData['goodsName']);
        $this->assertEquals('', $requestData['goodsExplain']);
        $this->assertEquals('20170824113232', $requestData['orderCreateTime']);
        $this->assertEquals('', $requestData['lastPayTime']);
        $this->assertEquals('http://kai0517.netii.net/', $requestData['merUrl']);
        $this->assertEquals('http://kai0517.netii.net/', $requestData['noticeUrl']);
        $this->assertEquals('ICBC', $requestData['bankCode']);
        $this->assertEquals('01', $requestData['bankCardType']);
        $this->assertEquals('', $requestData['merchantChannel']);
        $this->assertEquals('', $requestData['ext']);
        $this->assertEquals('ED7F0AF8FB10EBFCDFB914668B2C0C00', $requestData['sign']);
    }

    /**
     * 測試返回時缺少私鑰
     */
    public function testReturnWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $zsagePay = new ZsagePay();
        $zsagePay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未指定返回參數
     */
    public function testReturnWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [];

        $zsagePay = new ZsagePay();
        $zsagePay->setPrivateKey('test');
        $zsagePay->setOptions($options);
        $zsagePay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'ext' => '',
            'merchantCode' => '1000000550',
            'instructCode' => '11001200535',
            'transType' => '00200',
            'outOrderId' => '201708230000004001',
            'transTime' => '20150910121434',
            'totalAmount' => '1',
        ];

        $zsagePay = new ZsagePay();
        $zsagePay->setPrivateKey('test');
        $zsagePay->setOptions($options);
        $zsagePay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時sign錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $options = [
            'ext' => '',
            'merchantCode' => '1000000550',
            'instructCode' => '11001200535',
            'transType' => '00200',
            'outOrderId' => '201708230000004001',
            'transTime' => '20150910121434',
            'totalAmount' => '1',
            'sign' => '705555C41AC3FC6A0C200B8D32FB2E9',
        ];

        $zsagePay = new ZsagePay();
        $zsagePay->setPrivateKey('test');
        $zsagePay->setOptions($options);
        $zsagePay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時訂單號不一樣
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $options = [
            'ext' => '',
            'merchantCode' => '1000000550',
            'instructCode' => '11001200535',
            'transType' => '00200',
            'outOrderId' => '201708230000004001',
            'transTime' => '20150910121434',
            'totalAmount' => '1',
            'sign' => '7849289E2DE398CCAEF520FC829564CB',
        ];

        $entry = ['id' => '201503220000000555'];

        $zsagePay = new ZsagePay();
        $zsagePay->setPrivateKey('test');
        $zsagePay->setOptions($options);
        $zsagePay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時金額不一樣
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $options = [
            'ext' => '',
            'merchantCode' => '1000000550',
            'instructCode' => '11001200535',
            'transType' => '00200',
            'outOrderId' => '201708230000004001',
            'transTime' => '20150910121434',
            'totalAmount' => '1',
            'sign' => '7849289E2DE398CCAEF520FC829564CB',
        ];

        $entry = [
            'id' => '201708230000004001',
            'amount' => '0.1',
        ];

        $zsagePay = new ZsagePay();
        $zsagePay->setPrivateKey('test');
        $zsagePay->setOptions($options);
        $zsagePay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'merchantCode' => '1000000550',
            'instructCode' => '11001200535',
            'transType' => '00200',
            'outOrderId' => '201708230000004001',
            'transTime' => '20150910121434',
            'totalAmount' => '1',
            'sign' => '7849289E2DE398CCAEF520FC829564CB',
        ];

        $entry = [
            'id' => '201708230000004001',
            'amount' => '0.01',
        ];

        $zsagePay = new ZsagePay();
        $zsagePay->setPrivateKey('test');
        $zsagePay->setOptions($options);
        $zsagePay->verifyOrderPayment($entry);

        $this->assertEquals("{'code':'00'}", $zsagePay->getMsg());
    }

    /**
     * 測試訂單查詢缺少私鑰
     */
    public function testTrackingWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $zsagePay = new ZsagePay();
        $zsagePay->paymentTracking();
    }

    /**
     * 測試訂單查詢加密未指定查詢參數
     */
    public function testTrackingWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $zsagePay = new ZsagePay();
        $zsagePay->setPrivateKey('test');
        $zsagePay->paymentTracking();
    }

    /**
     * 測試訂單查詢加密沒有帶入verify_url的情況
     */
    public function testTrackingWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '1000000001',
            'orderId' => '201508060000000201',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $zsagePay = new ZsagePay();
        $zsagePay->setPrivateKey('test');
        $zsagePay->setOptions($options);
        $zsagePay->paymentTracking();
    }

    /**
     * 測試訂單查詢支付平台未返回參數code
     */
    public function testTrackingReturnWithoutCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '1000000001',
            'orderId' => '201508060000000201',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.payment.zsagepay.com',
        ];

        $result = ['msg' => '成功'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $zsagePay = new ZsagePay();
        $zsagePay->setContainer($this->container);
        $zsagePay->setClient($this->client);
        $zsagePay->setResponse($response);
        $zsagePay->setPrivateKey('test');
        $zsagePay->setOptions($options);
        $zsagePay->paymentTracking();
    }

    /**
     * 測試訂單查詢支付平台返回錯誤
     */
    public function testTrackingReturnWithPaymentTrackingFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '验签失败',
            180123
        );

        $options = [
            'number' => '1000000001',
            'orderId' => '201508060000000201',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.payment.zsagepay.com',
        ];

        $result = [
            'code' => '09003',
            'msg' => '验签失败',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $zsagePay = new ZsagePay();
        $zsagePay->setContainer($this->container);
        $zsagePay->setClient($this->client);
        $zsagePay->setResponse($response);
        $zsagePay->setPrivateKey('test');
        $zsagePay->setOptions($options);
        $zsagePay->paymentTracking();
    }

    /**
     * 測試訂單查詢訂單支付平台返回缺少data
     */
    public function testTrackingReturnWithoutData()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '1000000001',
            'orderId' => '201508060000000201',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.payment.zsagepay.com',
        ];

        $result = [
            'code' => '00',
            'msg' => '成功',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $zsagePay = new ZsagePay();
        $zsagePay->setContainer($this->container);
        $zsagePay->setClient($this->client);
        $zsagePay->setResponse($response);
        $zsagePay->setPrivateKey('test');
        $zsagePay->setOptions($options);
        $zsagePay->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付查詢結果未指定返回參數
     */
    public function testTrackingReturnWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '1000000001',
            'orderId' => '201508060000000201',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.payment.zsagepay.com',
        ];

        $result = [
            'code' => '00',
            'data' => [
                'instructCode' => 2017082300014187640,
                'merchantCode' => '1000000001',
                'outOrderId' => '201708230000003997',
                'replyCode' => '00',
                'sign' => 'FCF67BC34404BC142A1DCA924583DC27',
                'transTime' => '20170823121448',
                'transType' => '00200',
            ],
            'msg' => '成功',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $zsagePay = new ZsagePay();
        $zsagePay->setContainer($this->container);
        $zsagePay->setClient($this->client);
        $zsagePay->setResponse($response);
        $zsagePay->setPrivateKey('test');
        $zsagePay->setOptions($options);
        $zsagePay->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付查詢結果驗證沒有sign的情況
     */
    public function testTrackingReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '1000000001',
            'orderId' => '201508060000000201',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.payment.zsagepay.com',
        ];

        $result = [
            'code' => '00',
            'data' => [
                'amount' => 50,
                'ext' => '',
                'instructCode' => 2017082300014187640,
                'merchantCode' => '1000000001',
                'outOrderId' => '201708230000003997',
                'replyCode' => '00',
                'transTime' => '20170823121448',
                'transType' => '00200',
            ],
            'msg' => '成功',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $zsagePay = new ZsagePay();
        $zsagePay->setContainer($this->container);
        $zsagePay->setClient($this->client);
        $zsagePay->setResponse($response);
        $zsagePay->setPrivateKey('test');
        $zsagePay->setOptions($options);
        $zsagePay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果簽名驗證錯誤
     */
    public function testTrackingReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $options = [
            'number' => '1000000001',
            'orderId' => '201508060000000201',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.payment.zsagepay.com',
        ];

        $result = [
            'code' => '00',
            'data' => [
                'amount' => 50,
                'ext' => '',
                'instructCode' => 2017082300014187640,
                'merchantCode' => '1000000001',
                'outOrderId' => '201708230000003997',
                'replyCode' => '00',
                'sign' => 'FCF67BC34404BC142A1DCA924583DC27',
                'transTime' => '20170823121448',
                'transType' => '00200',
            ],
            'msg' => '成功',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $zsagePay = new ZsagePay();
        $zsagePay->setContainer($this->container);
        $zsagePay->setClient($this->client);
        $zsagePay->setResponse($response);
        $zsagePay->setPrivateKey('test');
        $zsagePay->setOptions($options);
        $zsagePay->paymentTracking();
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

        $options = [
            'number' => '1000000001',
            'orderId' => '201508060000000201',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.payment.zsagepay.com',
        ];

        $result = [
            'code' => '00',
            'data' => [
                'amount' => 50,
                'ext' => '',
                'instructCode' => 2017082300014187640,
                'merchantCode' => '1000000001',
                'outOrderId' => '201708230000003997',
                'replyCode' => '01',
                'sign' => 'A61DB57E51FD773C1FB88A38A9710892',
                'transTime' => '20170823121448',
                'transType' => '00200',
            ],
            'msg' => '成功',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $zsagePay = new ZsagePay();
        $zsagePay->setContainer($this->container);
        $zsagePay->setClient($this->client);
        $zsagePay->setResponse($response);
        $zsagePay->setPrivateKey('test');
        $zsagePay->setOptions($options);
        $zsagePay->paymentTracking();
    }

    /**
     * 測試訂單查詢帶入訂單號不正確
     */
    public function testPaymentTrackingWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $options = [
            'number' => '1000000001',
            'orderId' => '201508060000000201',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.payment.zsagepay.com',
        ];

        $result = [
            'code' => '00',
            'data' => [
                'amount' => 50,
                'ext' => '',
                'instructCode' => 2017082300014187640,
                'merchantCode' => '1000000001',
                'outOrderId' => '201708230000003997',
                'replyCode' => '00',
                'sign' => 'BAA520E83EBCD10FDC1620DF96914C9A',
                'transTime' => '20170823121448',
                'transType' => '00200',
            ],
            'msg' => '成功',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $zsagePay = new ZsagePay();
        $zsagePay->setContainer($this->container);
        $zsagePay->setClient($this->client);
        $zsagePay->setResponse($response);
        $zsagePay->setPrivateKey('test');
        $zsagePay->setOptions($options);
        $zsagePay->paymentTracking();
    }

    /**
     * 測試訂單查詢帶入金額不正確
     */
    public function testPaymentTrackingWithAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $options = [
            'number' => '1000000001',
            'amount' => '1',
            'orderId' => '201708230000003997',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.payment.zsagepay.com',
        ];

        $result = [
            'code' => '00',
            'data' => [
                'amount' => 50,
                'ext' => '',
                'instructCode' => 2017082300014187640,
                'merchantCode' => '1000000001',
                'outOrderId' => '201708230000003997',
                'replyCode' => '00',
                'sign' => 'BAA520E83EBCD10FDC1620DF96914C9A',
                'transTime' => '20170823121448',
                'transType' => '00200',
            ],
            'msg' => '成功',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $zsagePay = new ZsagePay();
        $zsagePay->setContainer($this->container);
        $zsagePay->setClient($this->client);
        $zsagePay->setResponse($response);
        $zsagePay->setPrivateKey('test');
        $zsagePay->setOptions($options);
        $zsagePay->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $options = [
            'number' => '1000000001',
            'amount' => '0.5',
            'orderId' => '201708230000003997',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.payment.zsagepay.com',
        ];

        $result = [
            'code' => '00',
            'data' => [
                'amount' => 50,
                'ext' => '',
                'instructCode' => 2017082300014187640,
                'merchantCode' => '1000000001',
                'outOrderId' => '201708230000003997',
                'replyCode' => '00',
                'sign' => 'BAA520E83EBCD10FDC1620DF96914C9A',
                'transTime' => '20170823121448',
                'transType' => '00200',
            ],
            'msg' => '成功',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $zsagePay = new ZsagePay();
        $zsagePay->setContainer($this->container);
        $zsagePay->setClient($this->client);
        $zsagePay->setResponse($response);
        $zsagePay->setPrivateKey('test');
        $zsagePay->setOptions($options);
        $zsagePay->paymentTracking();
    }

    /**
     * 測試取得訂單查詢時需要的參數時沒代入privateKey
     */
    public function testGetPaymentTrackingDataWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $zsagePay = new ZsagePay();
        $zsagePay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢時需要的參數時未指定查詢參數
     */
    public function testGetPaymentTrackingDataWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $zsagePay = new ZsagePay();
        $zsagePay->setPrivateKey('test');
        $zsagePay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢時需要的參數時沒代入verify_url
     */
    public function testGetPaymentTrackingDataWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '1000000001',
            'orderId' => '201508060000000201',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $zsagePay = new ZsagePay();
        $zsagePay->setPrivateKey('test');
        $zsagePay->setOptions($options);
        $zsagePay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢時需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'number' => '1000000001',
            'orderId' => '201508060000000201',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $zsagePay = new ZsagePay();
        $zsagePay->setPrivateKey('test');
        $zsagePay->setOptions($options);
        $trackingData = $zsagePay->getPaymentTrackingData();

        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('/ebank/queryOrder.do', $trackingData['path']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('1000000001', $trackingData['form']['merchantCode']);
        $this->assertEquals('201508060000000201', $trackingData['form']['outOrderId']);
        $this->assertEquals('31225AEBF6D49AD046A19BC801603296', $trackingData['form']['sign']);
        $this->assertEquals('payment.http.test', $trackingData['headers']['Host']);
    }

    /**
     * 測試驗證訂單查詢但沒代入privateKey
     */
    public function testPaymentTrackingVerifyWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $zsagePay = new ZsagePay();
        $zsagePay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但未返回參數code
     */
    public function testPaymentTrackingVerifyButNoCodeReturn()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = ['msg' => '成功'];

        $sourceData = ['content' => json_encode($content)];

        $zsagePay = new ZsagePay();
        $zsagePay->setPrivateKey('test');
        $zsagePay->setOptions($sourceData);
        $zsagePay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但返回錯誤
     */
    public function testPaymentTrackingVerifyButPaymentTrackingFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '验签失败',
            180123
        );

        $content = [
            'code' => '09003',
            'msg' => '验签失败',
        ];

        $sourceData = ['content' => json_encode($content)];

        $zsagePay = new ZsagePay();
        $zsagePay->setPrivateKey('test');
        $zsagePay->setOptions($sourceData);
        $zsagePay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但返回缺少data
     */
    public function testPaymentTrackingVerifyWithoutData()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = [
            'code' => '00',
            'msg' => '成功',
        ];

        $sourceData = ['content' => json_encode($content)];

        $zsagePay = new ZsagePay();
        $zsagePay->setPrivateKey('test');
        $zsagePay->setOptions($sourceData);
        $zsagePay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但未指定返回參數
     */
    public function testPaymentTrackingVerifyWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = [
            'code' => '00',
            'data' => [
                'instructCode' => 2017082300014187640,
                'merchantCode' => '1000000001',
                'outOrderId' => '201708230000003997',
                'replyCode' => '00',
                'sign' => 'FCF67BC34404BC142A1DCA924583DC27',
                'transTime' => '20170823121448',
                'transType' => '00200',
            ],
            'msg' => '成功',
        ];

        $sourceData = ['content' => json_encode($content)];

        $zsagePay = new ZsagePay();
        $zsagePay->setPrivateKey('test');
        $zsagePay->setOptions($sourceData);
        $zsagePay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但缺少回傳參數sign
     */
    public function testPaymentTrackingVerifyWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = [
            'code' => '00',
            'data' => [
                'amount' => 50,
                'ext' => '',
                'instructCode' => 2017082300014187640,
                'merchantCode' => '1000000001',
                'outOrderId' => '201708230000003997',
                'replyCode' => '00',
                'transTime' => '20170823121448',
                'transType' => '00200',
            ],
            'msg' => '成功',
        ];

        $sourceData = ['content' => json_encode($content)];

        $zsagePay = new ZsagePay();
        $zsagePay->setPrivateKey('test');
        $zsagePay->setOptions($sourceData);
        $zsagePay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但簽名驗證錯誤
     */
    public function testPaymentTrackingVerifyButSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $content = [
            'code' => '00',
            'data' => [
                'amount' => 50,
                'ext' => '',
                'instructCode' => 2017082300014187640,
                'merchantCode' => '1000000001',
                'outOrderId' => '201708230000003997',
                'replyCode' => '00',
                'sign' => 'FCF67BC34404BC142A1DCA924583DC27',
                'transTime' => '20170823121448',
                'transType' => '00200',
            ],
            'msg' => '成功',
        ];

        $sourceData = ['content' => json_encode($content)];

        $zsagePay = new ZsagePay();
        $zsagePay->setPrivateKey('test');
        $zsagePay->setOptions($sourceData);
        $zsagePay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但支付失敗
     */
    public function testPaymentTrackingVerifyButPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $content = [
            'code' => '00',
            'data' => [
                'amount' => 50,
                'ext' => '',
                'instructCode' => 2017082300014187640,
                'merchantCode' => '1000000001',
                'outOrderId' => '201708230000003997',
                'replyCode' => '01',
                'sign' => 'A61DB57E51FD773C1FB88A38A9710892',
                'transTime' => '20170823121448',
                'transType' => '00200',
            ],
            'msg' => '成功',
        ];

        $sourceData = ['content' => json_encode($content)];

        $zsagePay = new ZsagePay();
        $zsagePay->setPrivateKey('test');
        $zsagePay->setOptions($sourceData);
        $zsagePay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但訂單號不正確
     */
    public function testPaymentTrackingVerifyButOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $content = [
            'code' => '00',
            'data' => [
                'amount' => 50,
                'ext' => '',
                'instructCode' => 2017082300014187640,
                'merchantCode' => '1000000001',
                'outOrderId' => '201708230000003997',
                'replyCode' => '00',
                'sign' => 'BAA520E83EBCD10FDC1620DF96914C9A',
                'transTime' => '20170823121448',
                'transType' => '00200',
            ],
            'msg' => '成功',
        ];

        $sourceData = [
            'content' => json_encode($content),
            'orderId' => '201708230000004001'
        ];

        $zsagePay = new ZsagePay();
        $zsagePay->setPrivateKey('test');
        $zsagePay->setOptions($sourceData);
        $zsagePay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但金額不正確
     */
    public function testPaymentTrackingVerifyButAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $content = [
            'code' => '00',
            'data' => [
                'amount' => 50,
                'ext' => '',
                'instructCode' => 2017082300014187640,
                'merchantCode' => '1000000001',
                'outOrderId' => '201708230000003997',
                'replyCode' => '00',
                'sign' => 'BAA520E83EBCD10FDC1620DF96914C9A',
                'transTime' => '20170823121448',
                'transType' => '00200',
            ],
            'msg' => '成功',
        ];

        $sourceData = [
            'content' => json_encode($content),
            'orderId' => '201708230000003997',
            'amount' => '0.02',
        ];

        $zsagePay = new ZsagePay();
        $zsagePay->setPrivateKey('test');
        $zsagePay->setOptions($sourceData);
        $zsagePay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $content = [
            'code' => '00',
            'data' => [
                'amount' => 50,
                'ext' => '',
                'instructCode' => 2017082300014187640,
                'merchantCode' => '1000000001',
                'outOrderId' => '201708230000003997',
                'replyCode' => '00',
                'sign' => 'BAA520E83EBCD10FDC1620DF96914C9A',
                'transTime' => '20170823121448',
                'transType' => '00200',
            ],
            'msg' => '成功',
        ];

        $sourceData = [
            'content' => json_encode($content),
            'orderId' => '201708230000003997',
            'amount' => '0.5',
        ];

        $zsagePay = new ZsagePay();
        $zsagePay->setPrivateKey('test');
        $zsagePay->setOptions($sourceData);
        $zsagePay->paymentTrackingVerify();
    }
}
