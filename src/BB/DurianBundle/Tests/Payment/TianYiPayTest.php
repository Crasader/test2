<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\TianYiPay;
use Buzz\Message\Response;

class TianYiPayTest extends DurianTestCase
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
     * 測試支付加密時沒有帶入privateKey的情況
     */
    public function testPayWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $tianYiPay = new TianYiPay();
        $tianYiPay->getVerifyData();
    }

    /**
     * 測試支付加密時未指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = ['number' => ''];

        $tianYiPay = new TianYiPay();
        $tianYiPay->setPrivateKey('1234');
        $tianYiPay->setOptions($sourceData);
        $tianYiPay->getVerifyData();
    }

    /**
     * 測試支付加密時代入支付平台不支援的銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '1234',
            'paymentVendorId' => '999',
            'orderId' => '201801030000008391',
            'amount' => '0.01',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate'=> '2018-04-10 21:25:29',
        ];

        $tianYiPay = new TianYiPay();
        $tianYiPay->setPrivateKey('1234');
        $tianYiPay->setOptions($sourceData);
        $tianYiPay->getVerifyData();
    }

    /**
     * 測試支付時沒有帶入verify_url的情況
     */
    public function testPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '1234',
            'paymentVendorId' => '1',
            'orderId' => '201801030000008391',
            'amount' => '0.01',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate'=> '2018-04-10 21:25:29',
            'verify_url' => '',
        ];

        $tianYiPay = new TianYiPay();
        $tianYiPay->setPrivateKey('test');
        $tianYiPay->setOptions($options);
        $tianYiPay->getVerifyData();
    }

    /**
     * 測試網銀支付時沒有返回status_code
     */
    public function testOnlinePayReturnWithoutStatusCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '1234',
            'paymentVendorId' => '1',
            'orderId' => '201801030000008391',
            'amount' => '0.01',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate'=> '2018-04-10 21:25:29',
            'verify_url' => 'payment.http.cashier.zgmyb.top',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $tianYiPay = new TianYiPay();
        $tianYiPay->setContainer($this->container);
        $tianYiPay->setClient($this->client);
        $tianYiPay->setResponse($response);
        $tianYiPay->setPrivateKey('test');
        $tianYiPay->setOptions($options);
        $tianYiPay->getVerifyData();
    }

    /**
     * 測試網銀支付時沒有返回status_msg
     */
    public function testOnlinePayReturnWithoutStatusMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '1234',
            'paymentVendorId' => '1',
            'orderId' => '201801030000008391',
            'amount' => '0.01',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate'=> '2018-04-10 21:25:29',
            'verify_url' => 'payment.http.cashier.zgmyb.top',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = ['status_code' => 0];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $tianYiPay = new TianYiPay();
        $tianYiPay->setContainer($this->container);
        $tianYiPay->setClient($this->client);
        $tianYiPay->setResponse($response);
        $tianYiPay->setPrivateKey('test');
        $tianYiPay->setOptions($options);
        $tianYiPay->getVerifyData();
    }

    /**
     * 測試網銀支付時返回錯誤訊息
     */
    public function testOnlinePayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'app_id不存在！',
            180130
        );

        $options = [
            'number' => '1234',
            'paymentVendorId' => '1',
            'orderId' => '201801030000008391',
            'amount' => '0.01',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate'=> '2018-04-10 21:25:29',
            'verify_url' => 'payment.http.cashier.zgmyb.top',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'status_code' => -1,
            'status_msg' => 'app_id不存在！',
            'pay_seq' => '',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $tianYiPay = new TianYiPay();
        $tianYiPay->setContainer($this->container);
        $tianYiPay->setClient($this->client);
        $tianYiPay->setResponse($response);
        $tianYiPay->setPrivateKey('test');
        $tianYiPay->setOptions($options);
        $tianYiPay->getVerifyData();
    }

    /**
     * 測試網銀支付時沒有返回pay_url
     */
    public function testOnlinePayReturnWithoutPayUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '1234',
            'paymentVendorId' => '1',
            'orderId' => '201801030000008391',
            'amount' => '0.01',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate'=> '2018-04-10 21:25:29',
            'verify_url' => 'payment.http.cashier.zgmyb.top',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'status_code' => 0,
            'status_msg' => '下单成功',
            'pay_seq' => '37024',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $tianYiPay = new TianYiPay();
        $tianYiPay->setContainer($this->container);
        $tianYiPay->setClient($this->client);
        $tianYiPay->setResponse($response);
        $tianYiPay->setPrivateKey('test');
        $tianYiPay->setOptions($options);
        $tianYiPay->getVerifyData();
    }

    /**
     * 測試網銀支付
     */
    public function testOnlinePay()
    {
        $options = [
            'number' => '1234',
            'paymentVendorId' => '1',
            'orderId' => '201801030000008391',
            'amount' => '0.01',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate'=> '2018-04-10 21:25:29',
            'verify_url' => 'payment.http.cashier.zgmyb.top',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'status_code' => 0,
            'status_msg' => '下单成功',
            'pay_seq' => '37024',
            'pay_url' => 'http://pay.vzhipay.com/Pay/Union/HuiFuBao.aspx?token=38363&bank_code=ICBC',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $tianYiPay = new TianYiPay();
        $tianYiPay->setContainer($this->container);
        $tianYiPay->setClient($this->client);
        $tianYiPay->setResponse($response);
        $tianYiPay->setPrivateKey('test');
        $tianYiPay->setOptions($options);
        $encodeData = $tianYiPay->getVerifyData();

        $this->assertEquals('http://pay.vzhipay.com/Pay/Union/HuiFuBao.aspx', $encodeData['post_url']);
        $this->assertEquals('38363', $encodeData['params']['token']);
        $this->assertEquals('ICBC', $encodeData['params']['bank_code']);
        $this->assertEquals('GET', $tianYiPay->getPayMethod());
    }

    /**
     * 測試手機支付沒有返回status_code
     */
    public function testPhonePayReturnWithoutStatusCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '1234',
            'paymentVendorId' => '1104',
            'orderId' => '201801030000008391',
            'amount' => '0.01',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate'=> '2018-04-10 21:25:29',
            'verify_url' => 'payment.http.cashier.zgmyb.top',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $tianYiPay = new TianYiPay();
        $tianYiPay->setContainer($this->container);
        $tianYiPay->setClient($this->client);
        $tianYiPay->setResponse($response);
        $tianYiPay->setPrivateKey('test');
        $tianYiPay->setOptions($options);
        $tianYiPay->getVerifyData();
    }

    /**
     * 測試手機支付沒有返回status_msg
     */
    public function testPhonePayReturnWithoutStatusMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '1234',
            'paymentVendorId' => '1104',
            'orderId' => '201801030000008391',
            'amount' => '0.01',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate'=> '2018-04-10 21:25:29',
            'verify_url' => 'payment.http.cashier.zgmyb.top',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = ['status_code' => 0];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $tianYiPay = new TianYiPay();
        $tianYiPay->setContainer($this->container);
        $tianYiPay->setClient($this->client);
        $tianYiPay->setResponse($response);
        $tianYiPay->setPrivateKey('test');
        $tianYiPay->setOptions($options);
        $tianYiPay->getVerifyData();
    }

    /**
     * 測試手機支付時返回錯誤訊息
     */
    public function testPhonePayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'app_id不存在！',
            180130
        );

        $options = [
            'number' => '1234',
            'paymentVendorId' => '1104',
            'orderId' => '201801030000008391',
            'amount' => '0.01',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate'=> '2018-04-10 21:25:29',
            'verify_url' => 'payment.http.cashier.zgmyb.top',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'status_code' => -1,
            'status_msg' => 'app_id不存在！',
            'pay_seq' => '',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $tianYiPay = new TianYiPay();
        $tianYiPay->setContainer($this->container);
        $tianYiPay->setClient($this->client);
        $tianYiPay->setResponse($response);
        $tianYiPay->setPrivateKey('test');
        $tianYiPay->setOptions($options);
        $tianYiPay->getVerifyData();
    }

    /**
     * 測試手機支付時沒有返回pay_url
     */
    public function testPhonePayReturnWithoutPayUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '1234',
            'paymentVendorId' => '1104',
            'orderId' => '201801030000008391',
            'amount' => '0.01',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate'=> '2018-04-10 21:25:29',
            'verify_url' => 'payment.http.cashier.zgmyb.top',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'status_code' => 0,
            'status_msg' => '下单成功',
            'pay_seq' => '37466',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $tianYiPay = new TianYiPay();
        $tianYiPay->setContainer($this->container);
        $tianYiPay->setClient($this->client);
        $tianYiPay->setResponse($response);
        $tianYiPay->setPrivateKey('test');
        $tianYiPay->setOptions($options);
        $tianYiPay->getVerifyData();
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $options = [
            'number' => '1234',
            'paymentVendorId' => '1097',
            'orderId' => '201804110000010882',
            'amount' => '0.01',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate'=> '2018-04-10 21:25:29',
            'verify_url' => 'payment.http.cashier.zgmyb.top',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'status_code' => 0,
            'status_msg' => '下单成功',
            'pay_seq' => '37449',
            'pay_url' => 'http://pay.echongbei.com/WxH5Pay.aspx?token=2378320',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $tianYiPay = new TianYiPay();
        $tianYiPay->setContainer($this->container);
        $tianYiPay->setClient($this->client);
        $tianYiPay->setResponse($response);
        $tianYiPay->setPrivateKey('test');
        $tianYiPay->setOptions($options);
        $encodeData = $tianYiPay->getVerifyData();

        $this->assertEquals('http://pay.echongbei.com/WxH5Pay.aspx', $encodeData['post_url']);
        $this->assertEquals('2378320', $encodeData['params']['token']);
        $this->assertEquals('GET', $tianYiPay->getPayMethod());
    }

    /**
     * 測試二維支付沒有返回status_code
     */
    public function testQrcodePayReturnWithoutStatusCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '1234',
            'paymentVendorId' => '1103',
            'orderId' => '201801030000008391',
            'amount' => '0.01',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate'=> '2018-04-10 21:25:29',
            'verify_url' => 'payment.http.cashier.zgmyb.top',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $tianYiPay = new TianYiPay();
        $tianYiPay->setContainer($this->container);
        $tianYiPay->setClient($this->client);
        $tianYiPay->setResponse($response);
        $tianYiPay->setPrivateKey('test');
        $tianYiPay->setOptions($options);
        $tianYiPay->getVerifyData();
    }

    /**
     * 測試二維支付沒有返回status_msg
     */
    public function testQrcodePayReturnWithoutStatusMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '1234',
            'paymentVendorId' => '1103',
            'orderId' => '201801030000008391',
            'amount' => '0.01',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate'=> '2018-04-10 21:25:29',
            'verify_url' => 'payment.http.cashier.zgmyb.top',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = ['status_code' => 0];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $tianYiPay = new TianYiPay();
        $tianYiPay->setContainer($this->container);
        $tianYiPay->setClient($this->client);
        $tianYiPay->setResponse($response);
        $tianYiPay->setPrivateKey('test');
        $tianYiPay->setOptions($options);
        $tianYiPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回錯誤訊息
     */
    public function testQrcodePayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'app_id不存在！',
            180130
        );

        $options = [
            'number' => '1234',
            'paymentVendorId' => '1103',
            'orderId' => '201801030000008391',
            'amount' => '0.01',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate'=> '2018-04-10 21:25:29',
            'verify_url' => 'payment.http.cashier.zgmyb.top',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'status_code' => -1,
            'status_msg' => 'app_id不存在！',
            'pay_seq' => '',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $tianYiPay = new TianYiPay();
        $tianYiPay->setContainer($this->container);
        $tianYiPay->setClient($this->client);
        $tianYiPay->setResponse($response);
        $tianYiPay->setPrivateKey('test');
        $tianYiPay->setOptions($options);
        $tianYiPay->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回pay_url
     */
    public function testQrcodePayReturnWithoutPayUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '1234',
            'paymentVendorId' => '1103',
            'orderId' => '201801030000008391',
            'amount' => '0.01',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate'=> '2018-04-10 21:25:29',
            'verify_url' => 'payment.http.cashier.zgmyb.top',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'status_code' => 0,
            'status_msg' => '下单成功',
            'pay_seq' => '42716',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $tianYiPay = new TianYiPay();
        $tianYiPay->setContainer($this->container);
        $tianYiPay->setClient($this->client);
        $tianYiPay->setResponse($response);
        $tianYiPay->setPrivateKey('test');
        $tianYiPay->setOptions($options);
        $tianYiPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $options = [
            'number' => '1234',
            'paymentVendorId' => '1103',
            'orderId' => '201801030000008391',
            'amount' => '0.01',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate'=> '2018-04-10 21:25:29',
            'verify_url' => 'payment.http.cashier.zgmyb.top',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'status_code' => 0,
            'status_msg' => '下单成功',
            'pay_seq' => '37466',
            'pay_url' => 'https://qpay.qq.com/qr/5efe1cc4',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $tianYiPay = new TianYiPay();
        $tianYiPay->setContainer($this->container);
        $tianYiPay->setClient($this->client);
        $tianYiPay->setResponse($response);
        $tianYiPay->setPrivateKey('test');
        $tianYiPay->setOptions($options);
        $encodeData = $tianYiPay->getVerifyData();

        $this->assertEmpty($encodeData);
        $this->assertEquals('https://qpay.qq.com/qr/5efe1cc4', $tianYiPay->getQrcode());
    }

    /**
     * 測試條碼支付沒有返回status_code
     */
    public function testBarCodePayReturnWithoutStatusCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '1234',
            'paymentVendorId' => '1115',
            'orderId' => '201804120000011000',
            'amount' => '100',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate'=> '2018-04-10 21:25:29',
            'verify_url' => 'payment.http.cashier.zgmyb.top',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $tianYiPay = new TianYiPay();
        $tianYiPay->setContainer($this->container);
        $tianYiPay->setClient($this->client);
        $tianYiPay->setResponse($response);
        $tianYiPay->setPrivateKey('test');
        $tianYiPay->setOptions($options);
        $tianYiPay->getVerifyData();
    }

    /**
     * 測試條碼支付沒有返回status_msg
     */
    public function testBarCodePayReturnWithoutStatusMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '1234',
            'paymentVendorId' => '1115',
            'orderId' => '201804120000011000',
            'amount' => '100',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate'=> '2018-04-10 21:25:29',
            'verify_url' => 'payment.http.cashier.zgmyb.top',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = ['status_code' => 0];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $tianYiPay = new TianYiPay();
        $tianYiPay->setContainer($this->container);
        $tianYiPay->setClient($this->client);
        $tianYiPay->setResponse($response);
        $tianYiPay->setPrivateKey('test');
        $tianYiPay->setOptions($options);
        $tianYiPay->getVerifyData();
    }

    /**
     * 測試條碼支付時返回錯誤訊息
     */
    public function testBarCodePayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'app_id不存在！',
            180130
        );

        $options = [
            'number' => '1234',
            'paymentVendorId' => '1115',
            'orderId' => '201801030000008391',
            'amount' => '100',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate'=> '2018-04-10 21:25:29',
            'verify_url' => 'payment.http.cashier.zgmyb.top',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'status_code' => -1,
            'status_msg' => 'app_id不存在！',
            'pay_seq' => '',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $tianYiPay = new TianYiPay();
        $tianYiPay->setContainer($this->container);
        $tianYiPay->setClient($this->client);
        $tianYiPay->setResponse($response);
        $tianYiPay->setPrivateKey('test');
        $tianYiPay->setOptions($options);
        $tianYiPay->getVerifyData();
    }

    /**
     * 測試條碼支付時沒有返回pay_url
     */
    public function testBarCodePayReturnWithoutPayUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '1234',
            'paymentVendorId' => '1115',
            'orderId' => '201804120000011000',
            'amount' => '100',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate'=> '2018-04-10 21:25:29',
            'verify_url' => 'payment.http.cashier.zgmyb.top',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'status_code' => 0,
            'status_msg' => '下单成功',
            'pay_seq' => '43181',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $tianYiPay = new TianYiPay();
        $tianYiPay->setContainer($this->container);
        $tianYiPay->setClient($this->client);
        $tianYiPay->setResponse($response);
        $tianYiPay->setPrivateKey('test');
        $tianYiPay->setOptions($options);
        $tianYiPay->getVerifyData();
    }

    /**
     * 測試條碼支付
     */
    public function testBarCodePay()
    {
        $options = [
            'number' => '1234',
            'paymentVendorId' => '1115',
            'orderId' => '201804120000011000',
            'amount' => '0.01',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate'=> '2018-04-10 21:25:29',
            'verify_url' => 'payment.http.cashier.zgmyb.top',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $payUrl = 'http://zf.szjhzxxkj.com/ownPay/pay?merchantNo=500007567529&requestNo=43181' .
            '&amount=10000&payMethod=6013&pageUrl=http://candj.huhu.tw/pay/return.php&' .
            'backUrl=http://47.106.69.226/Pay/TenPay/Notify.aspx&payDate=1523524983&' .
            'agencyCode=0&authCode=201804120000011001&remark1=充值订单53552018041200045191号&' .
            'remark2=充值订单53552018041200045191号&remark3=充值订单53552018041200045191号&' .
            'signature=E4FKSB+9abDx+5BvWhZy8NpgHk\/Hct4HYCkTeXBDp7EycRiKYaMeEhsSdCDYGy8gg' .
            'MScJJblndc+pMyT5tJY7Lr4xgTEQCEA3dqjyKdrtjUnhRU4fDipvWJm1k68kw0JNt' .
            'BlNY\/MOoRxxAJGb24+3+M7fn0gD7w8oFVfvvRkh5o=';

        $result = [
            'status_code' => 0,
            'status_msg' => '下单成功',
            'pay_seq' => '37466',
            'pay_url' => $payUrl,
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $tianYiPay = new TianYiPay();
        $tianYiPay->setContainer($this->container);
        $tianYiPay->setClient($this->client);
        $tianYiPay->setResponse($response);
        $tianYiPay->setPrivateKey('test');
        $tianYiPay->setOptions($options);
        $encodeData = $tianYiPay->getVerifyData();

        $sign = 'E4FKSB 9abDx 5BvWhZy8NpgHk\/Hct4HYCkTeXBDp7EycRiKYaMeEhsSdCDYGy8ggMScJJblndc pMyT5tJY7Lr4xg' .
            'TEQCEA3dqjyKdrtjUnhRU4fDipvWJm1k68kw0JNtBlNY\/MOoRxxAJGb24 3 M7fn0gD7w8oFVfvvRkh5o=';

        $this->assertEquals('http://zf.szjhzxxkj.com/ownPay/pay', $encodeData['post_url']);
        $this->assertEquals('201804120000011001', $encodeData['params']['authCode']);
        $this->assertEquals('500007567529', $encodeData['params']['merchantNo']);
        $this->assertEquals('43181', $encodeData['params']['requestNo']);
        $this->assertEquals('10000', $encodeData['params']['amount']);
        $this->assertEquals('6013', $encodeData['params']['payMethod']);
        $this->assertEquals('http://candj.huhu.tw/pay/return.php', $encodeData['params']['pageUrl']);
        $this->assertEquals('http://47.106.69.226/Pay/TenPay/Notify.aspx', $encodeData['params']['backUrl']);
        $this->assertEquals('1523524983', $encodeData['params']['payDate']);
        $this->assertEquals('0', $encodeData['params']['agencyCode']);
        $this->assertEquals('201804120000011001', $encodeData['params']['authCode']);
        $this->assertEquals('充值订单53552018041200045191号', $encodeData['params']['remark1']);
        $this->assertEquals('充值订单53552018041200045191号', $encodeData['params']['remark2']);
        $this->assertEquals('充值订单53552018041200045191号', $encodeData['params']['remark3']);
        $this->assertEquals($sign, $encodeData['params']['signature']);
    }

    /**
     * 測試支付寶支付
     */
    public function testAliPayPay()
    {
        $options = [
            'number' => '1234',
            'paymentVendorId' => '1098',
            'orderId' => '201804120000011000',
            'amount' => '0.01',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate'=> '2018-04-10 21:25:29',
            'verify_url' => 'payment.http.cashier.zgmyb.top',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'status_code' => 0,
            'status_msg' => '下单成功',
            'pay_seq' => '93453',
            'pay_url' => 'http://bpayment.maiduopay.com/Pay/AliPay.aspx?token=14835467',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $tianYiPay = new TianYiPay();
        $tianYiPay->setContainer($this->container);
        $tianYiPay->setClient($this->client);
        $tianYiPay->setResponse($response);
        $tianYiPay->setPrivateKey('test');
        $tianYiPay->setOptions($options);
        $encodeData = $tianYiPay->getVerifyData();

        $this->assertEquals('http://bpayment.maiduopay.com/Pay/AliPay.aspx', $encodeData['post_url']);
        $this->assertEquals('14835467', $encodeData['params']['token']);
        $this->assertEquals('GET', $tianYiPay->getPayMethod());
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

        $tianYiPay = new TianYiPay();
        $tianYiPay->verifyOrderPayment([]);
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

        $tianYiPay = new TianYiPay();
        $tianYiPay->setPrivateKey('1234');
        $tianYiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少加密簽名
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'app_id' => '1641',
            'order_id' => '201804110000010943',
            'pay_seq' => '38363',
            'pay_amt' => '1.00',
            'pay_result' => '20',
            'result_desc' => '支付成功',
            'time_stamp' => '20180411170154',
            'extends' => '',
        ];

        $tianYiPay = new TianYiPay();
        $tianYiPay->setPrivateKey('1234');
        $tianYiPay->setOptions($sourceData);
        $tianYiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時簽名驗證錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $sourceData = [
            'app_id' => '1641',
            'order_id' => '201804110000010943',
            'pay_seq' => '38363',
            'pay_amt' => '1.00',
            'pay_result' => '20',
            'result_desc' => '支付成功',
            'time_stamp' => '20180411170154',
            'extends' => '',
            'sign' => 'hello',
        ];

        $tianYiPay = new TianYiPay();
        $tianYiPay->setPrivateKey('1234');
        $tianYiPay->setOptions($sourceData);
        $tianYiPay->verifyOrderPayment([]);
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

        $sourceData = [
            'app_id' => '1641',
            'order_id' => '201804110000010943',
            'pay_seq' => '38363',
            'pay_amt' => '1.00',
            'pay_result' => '!20',
            'result_desc' => '支付失敗',
            'time_stamp' => '20180411170154',
            'extends' => '',
            'sign' => '57668ceaca37078f10d35a0f98e13719',
        ];

        $tianYiPay = new TianYiPay();
        $tianYiPay->setPrivateKey('1234');
        $tianYiPay->setOptions($sourceData);
        $tianYiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時單號不正確的情況
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'app_id' => '1641',
            'order_id' => '201804110000010943',
            'pay_seq' => '38363',
            'pay_amt' => '1.00',
            'pay_result' => '20',
            'result_desc' => '支付成功',
            'time_stamp' => '20180411170154',
            'extends' => '',
            'sign' => '3e1a5783c9551519f662f0047af8e152',
        ];

        $entry = ['id' => '201606220000002806'];

        $tianYiPay = new TianYiPay();
        $tianYiPay->setPrivateKey('1234');
        $tianYiPay->setOptions($sourceData);
        $tianYiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時金額不正確的情況
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'app_id' => '1641',
            'order_id' => '201804110000010943',
            'pay_seq' => '38363',
            'pay_amt' => '1.00',
            'pay_result' => '20',
            'result_desc' => '支付成功',
            'time_stamp' => '20180411170154',
            'extends' => '',
            'sign' => '3e1a5783c9551519f662f0047af8e152',
        ];

        $entry = [
            'id' => '201804110000010943',
            'amount' => '10',
        ];

        $tianYiPay = new TianYiPay();
        $tianYiPay->setPrivateKey('1234');
        $tianYiPay->setOptions($sourceData);
        $tianYiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回驗證成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'app_id' => '1641',
            'order_id' => '201804110000010943',
            'pay_seq' => '38363',
            'pay_amt' => '1.00',
            'pay_result' => '20',
            'result_desc' => '支付成功',
            'time_stamp' => '20180411170154',
            'extends' => '',
            'sign' => '3e1a5783c9551519f662f0047af8e152',
        ];

        $entry = [
            'id' => '201804110000010943',
            'amount' => '1',
        ];

        $tianYiPay = new TianYiPay();
        $tianYiPay->setPrivateKey('1234');
        $tianYiPay->setOptions($sourceData);
        $tianYiPay->verifyOrderPayment($entry);

        $this->assertEquals('ok', $tianYiPay->getMsg());
    }
}
