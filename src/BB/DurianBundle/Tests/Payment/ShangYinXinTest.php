<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ShangYinXin;
use Buzz\Message\Response;

class ShangYinXinTest extends DurianTestCase
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

        $syxPay = new ShangYinXin();
        $syxPay->getVerifyData();
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

        $syxPay = new ShangYinXin();
        $syxPay->setPrivateKey('test');
        $syxPay->getVerifyData();
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
            'number' => '25771756',
            'orderId' => '201511030000001142',
            'amount' => '0.01',
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '999',
            'merchantId' => '12345',
            'domain' => '6',
            'ip' => '127.0.0.1',
        ];

        $syxPay = new ShangYinXin();
        $syxPay->setPrivateKey('test');
        $syxPay->setOptions($options);
        $syxPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'number' => '25771756',
            'orderId' => '201511030000001142',
            'amount' => '0.01',
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1',
            'merchantId' => '12345',
            'domain' => '6',
            'ip' => '127.0.0.1',
        ];

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $options['notify_url'],
            $options['merchantId'],
            $options['domain']
        );

        $syxPay = new ShangYinXin();
        $syxPay->setPrivateKey('test');
        $syxPay->setOptions($options);
        $requestData = $syxPay->getVerifyData();

        $this->assertEquals($options['number'], $requestData['merchantId']);
        $this->assertEquals($options['orderId'], $requestData['outOrderId']);
        $this->assertEquals($options['amount'], $requestData['transAmt']);
        $this->assertEquals($notifyUrl, $requestData['notifyUrl']);
        $this->assertEquals('201511030000001142', $requestData['subject']);
        $this->assertEquals('ICBC', $requestData['defaultBank']);
        $this->assertEquals('eb95da0bd4773bd13bb81a83bcff18a2', $requestData['sign']);
    }

    /**
     * 測試支付銀行為二維時未返回reCode
     */
    public function testPayWithQRCodeNoReturnReCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $syxPay = new ShangYinXin();
        $syxPay->setContainer($this->container);
        $syxPay->setClient($this->client);

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $result .= '<ScanCode>';
        $result .= '    <message>成功</message>';
        $result .= '    <payCode>weixin://wxpay/bizpayurl?pr=ZcQJZ7Y</payCode>';
        $result .= '    <sign>ef0f3268e5b983bd9c828961bc87bdc9</sign>';
        $result .= '</ScanCode>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $syxPay->setResponse($response);

        $options = [
            'number' => '25771756',
            'orderId' => '201511030000001142',
            'amount' => '0.01',
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1090',
            'merchantId' => '12345',
            'domain' => '6',
            'ip' => '127.0.0.1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.allscore.com',
        ];

        $syxPay->setPrivateKey('test');
        $syxPay->setOptions($options);
        $syxPay->getVerifyData();
    }

    /**
     * 測試支付銀行為二維時對外返回結果錯誤
     */
    public function testPayWithQRCodeReturnError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '系统异常:null',
            180130
        );

        $syxPay = new ShangYinXin();
        $syxPay->setContainer($this->container);
        $syxPay->setClient($this->client);

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $result .= '<ScanCode>';
        $result .= '    <merchantId>001016051700644</merchantId>';
        $result .= '    <outOrderId>201611150000006883</outOrderId>';
        $result .= '    <reCode>FAIL</reCode>';
        $result .= '    <message>系统异常:null</message>';
        $result .= '</ScanCode>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $syxPay->setResponse($response);

        $options = [
            'number' => '25771756',
            'orderId' => '201511030000001142',
            'amount' => '0.01',
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1090',
            'merchantId' => '12345',
            'domain' => '6',
            'ip' => '127.0.0.1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.allscore.com',
        ];

        $syxPay->setPrivateKey('test');
        $syxPay->setOptions($options);
        $syxPay->getVerifyData();
    }

    /**
     * 測試支付銀行為二維時未返回payCode
     */
    public function testPayWithQRCodeNoReturnPayCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $syxPay = new ShangYinXin();
        $syxPay->setContainer($this->container);
        $syxPay->setClient($this->client);

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $result .= '<ScanCode>';
        $result .= '    <merchantId>001016051700644</merchantId>';
        $result .= '    <outOrderId>201611150000006883</outOrderId>';
        $result .= '    <transAmt>0.01</transAmt>';
        $result .= '    <payMethod>default_wechat</payMethod>';
        $result .= '    <dateTime>20161115162137</dateTime>';
        $result .= '    <reCode>SUCCESS</reCode>';
        $result .= '    <message>成功</message>';
        $result .= '    <sign>ef0f3268e5b983bd9c828961bc87bdc9</sign>';
        $result .= '</ScanCode>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $syxPay->setResponse($response);

        $options = [
            'number' => '25771756',
            'orderId' => '201511030000001142',
            'amount' => '0.01',
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1090',
            'merchantId' => '12345',
            'domain' => '6',
            'ip' => '127.0.0.1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.allscore.com',
        ];

        $syxPay->setPrivateKey('test');
        $syxPay->setOptions($options);
        $syxPay->getVerifyData();
    }

    /**
     * 測試支付銀行為微信二維
     */
    public function testPayWithWeiXinQRCode()
    {
        $syxPay = new ShangYinXin();
        $syxPay->setContainer($this->container);
        $syxPay->setClient($this->client);

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $result .= '<ScanCode>';
        $result .= '    <merchantId>001016051700644</merchantId>';
        $result .= '    <outOrderId>201611150000006883</outOrderId>';
        $result .= '    <transAmt>0.01</transAmt>';
        $result .= '    <payMethod>default_wechat</payMethod>';
        $result .= '    <dateTime>20161115162137</dateTime>';
        $result .= '    <reCode>SUCCESS</reCode>';
        $result .= '    <message>成功</message>';
        $result .= '    <payCode>weixin://wxpay/bizpayurl?pr=ZcQJZ7Y</payCode>';
        $result .= '    <sign>ef0f3268e5b983bd9c828961bc87bdc9</sign>';
        $result .= '</ScanCode>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $syxPay->setResponse($response);

        $options = [
            'number' => '25771756',
            'orderId' => '201511030000001142',
            'amount' => '0.01',
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1090',
            'merchantId' => '12345',
            'domain' => '6',
            'ip' => '127.0.0.1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.allscore.com',
        ];

        $syxPay->setPrivateKey('test');
        $syxPay->setOptions($options);
        $encodeData = $syxPay->getVerifyData();

        $this->assertEmpty($encodeData);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=ZcQJZ7Y', $syxPay->getQrcode());
    }

    /**
     * 測試支付銀行為支付寶二維
     */
    public function testPayWithAlipay()
    {
        $syxPay = new ShangYinXin();
        $syxPay->setContainer($this->container);
        $syxPay->setClient($this->client);

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $result .= '<ScanCode>';
        $result .= '    <merchantId>001016051700644</merchantId>';
        $result .= '    <outOrderId>201611150000006883</outOrderId>';
        $result .= '    <transAmt>0.01</transAmt>';
        $result .= '    <payMethod>default_wechat</payMethod>';
        $result .= '    <dateTime>20161115162137</dateTime>';
        $result .= '    <reCode>SUCCESS</reCode>';
        $result .= '    <message>成功</message>';
        $result .= '    <payCode>weixin://wxpay/bizpayurl?pr=ZcQJZ7Y</payCode>';
        $result .= '    <sign>ef0f3268e5b983bd9c828961bc87bdc9</sign>';
        $result .= '</ScanCode>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $syxPay->setResponse($response);

        $options = [
            'number' => '25771756',
            'orderId' => '201511030000001142',
            'amount' => '0.01',
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1092',
            'merchantId' => '12345',
            'domain' => '6',
            'ip' => '127.0.0.1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.allscore.com',
        ];

        $syxPay->setPrivateKey('test');
        $syxPay->setOptions($options);
        $encodeData = $syxPay->getVerifyData();

        $this->assertEmpty($encodeData);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=ZcQJZ7Y', $syxPay->getQrcode());
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

        $syxPay = new ShangYinXin();
        $syxPay->verifyOrderPayment([]);
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

        $options = ['outOrderId' => '201511030000001142'];

        $syxPay = new ShangYinXin();
        $syxPay->setPrivateKey('test');
        $syxPay->setOptions($options);
        $syxPay->verifyOrderPayment([]);
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
            'merchantId' => '001015102600376',
            'notifyId' => '639621',
            'notifyTime' => '20151103113858',
            'outOrderId' => '201511030000001144',
            'tradeStatus' => '2',
            'transAmt' => '1',
            'inputCharset' => 'UTF-8',
        ];

        $syxPay = new ShangYinXin();
        $syxPay->setPrivateKey('test');
        $syxPay->setOptions($options);
        $syxPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時取得無效的支付參數
     */
    public function testReturnInvalidPayParameters()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Invalid pay parameters',
            180129
        );

        $result = 'invalid';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $options = [
            'merchantId' => '001015102600376',
            'notifyId' => '639621',
            'notifyTime' => '20151103113858',
            'outOrderId' => '201511030000001144',
            'tradeStatus' => '2',
            'transAmt' => '1',
            'inputCharset' => 'UTF-8',
            'sign' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.allscore.com',
        ];

        $entry = ['merchant_number' => ''];

        $syxPay = new ShangYinXin();
        $syxPay->setContainer($this->container);
        $syxPay->setClient($this->client);
        $syxPay->setResponse($response);
        $syxPay->setPrivateKey('test');
        $syxPay->setOptions($options);
        $syxPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付時對外返回結果錯誤
     */
    public function testPayConnectionPaymentGatewayError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Invalid response',
            180148
        );

        $result = 'false';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $options = [
            'merchantId' => '001015102600376',
            'notifyId' => '639621',
            'notifyTime' => '20151103113858',
            'outOrderId' => '201511030000001144',
            'tradeStatus' => '2',
            'transAmt' => '1',
            'inputCharset' => 'UTF-8',
            'sign' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.allscore.com',
        ];

        $entry = ['merchant_number' => ''];

        $syxPay = new ShangYinXin();
        $syxPay->setContainer($this->container);
        $syxPay->setClient($this->client);
        $syxPay->setResponse($response);
        $syxPay->setPrivateKey('test');
        $syxPay->setOptions($options);
        $syxPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時sign錯誤
     */
    public function testReturnWithWrongSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $result = 'true';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $options = [
            'merchantId' => '001015102600376',
            'notifyId' => '639621',
            'notifyTime' => '20151103113858',
            'outOrderId' => '201511030000001144',
            'tradeStatus' => '2',
            'transAmt' => '1',
            'inputCharset' => 'UTF-8',
            'sign' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.allscore.com',
        ];

        $entry = ['merchant_number' => ''];

        $syxPay = new ShangYinXin();
        $syxPay->setContainer($this->container);
        $syxPay->setClient($this->client);
        $syxPay->setResponse($response);
        $syxPay->setPrivateKey('test');
        $syxPay->setOptions($options);
        $syxPay->verifyOrderPayment($entry);
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

        $result = 'true';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $options = [
            'merchantId' => '001015102600376',
            'notifyId' => '639621',
            'notifyTime' => '20151103113858',
            'outOrderId' => '201511030000001144',
            'tradeStatus' => '1',
            'transAmt' => '1',
            'inputCharset' => 'UTF-8',
            'sign' => 'a317e7841d28064c629a6bbd76a5273e',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.allscore.com',
        ];

        $entry = ['merchant_number' => ''];

        $syxPay = new ShangYinXin();
        $syxPay->setContainer($this->container);
        $syxPay->setClient($this->client);
        $syxPay->setResponse($response);
        $syxPay->setPrivateKey('test');
        $syxPay->setOptions($options);
        $syxPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時訂單號不一樣
     */
    public function testReturnWithErrorOrderId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $result = 'true';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $options = [
            'merchantId' => '001015102600376',
            'notifyId' => '639621',
            'notifyTime' => '20151103113858',
            'outOrderId' => '201511030000001144',
            'tradeStatus' => '2',
            'transAmt' => '1',
            'inputCharset' => 'UTF-8',
            'sign' => 'c26cccf8803419e1f8f20d780a371725',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.allscore.com',
        ];

        $entry = [
            'merchant_number' => '',
            'id' => '201503220000000555'
        ];

        $syxPay = new ShangYinXin();
        $syxPay->setContainer($this->container);
        $syxPay->setClient($this->client);
        $syxPay->setResponse($response);
        $syxPay->setPrivateKey('test');
        $syxPay->setOptions($options);
        $syxPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時金額不一樣
     */
    public function testReturnWithErrorAmount()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $result = 'true';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $options = [
            'merchantId' => '001015102600376',
            'notifyId' => '639621',
            'notifyTime' => '20151103113858',
            'outOrderId' => '201511030000001144',
            'tradeStatus' => '2',
            'transAmt' => '1',
            'inputCharset' => 'UTF-8',
            'sign' => 'c26cccf8803419e1f8f20d780a371725',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.allscore.com',
        ];

        $entry = [
            'merchant_number' => '',
            'id' => '201511030000001144',
            'amount' => '0.1',
        ];

        $syxPay = new ShangYinXin();
        $syxPay->setContainer($this->container);
        $syxPay->setClient($this->client);
        $syxPay->setResponse($response);
        $syxPay->setPrivateKey('test');
        $syxPay->setOptions($options);
        $syxPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $result = 'true';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $options = [
            'merchantId' => '001015102600376',
            'notifyId' => '639621',
            'notifyTime' => '20151103113858',
            'outOrderId' => '201511030000001144',
            'tradeStatus' => '2',
            'transAmt' => '1',
            'inputCharset' => 'UTF-8',
            'sign' => 'c26cccf8803419e1f8f20d780a371725',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.allscore.com',
        ];

        $entry = [
            'merchant_number' => '',
            'id' => '201511030000001144',
            'amount' => '1',
        ];

        $syxPay = new ShangYinXin();
        $syxPay->setContainer($this->container);
        $syxPay->setClient($this->client);
        $syxPay->setResponse($response);
        $syxPay->setPrivateKey('test');
        $syxPay->setOptions($options);
        $syxPay->verifyOrderPayment($entry);
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

        $syxPay = new ShangYinXin();
        $syxPay->paymentTracking();
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

        $syxPay = new ShangYinXin();
        $syxPay->setPrivateKey('test');
        $syxPay->paymentTracking();
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
            'number' => '001015102600376',
            'orderId' => '201511030000001144',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $syxPay = new ShangYinXin();
        $syxPay->setPrivateKey('test');
        $syxPay->setOptions($options);
        $syxPay->paymentTracking();
    }

    /**
     * 測試訂單查詢支付平台連線異常
     */
    public function testTrackingReturnPaymentGatewayConnectionError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment Gateway connection failure',
            180088
        );

        $exception = new \Exception('Timed out', 0);
        $this->client->expects($this->any())
            ->method('send')
            ->willThrowException($exception);

        $options = [
            'number' => '001015102600376',
            'orderId' => '201511030000001144',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '127.0.0.1',
        ];

        $syxPay = new ShangYinXin();
        $syxPay->setContainer($this->container);
        $syxPay->setClient($this->client);
        $syxPay->setPrivateKey('test');
        $syxPay->setOptions($options);
        $syxPay->paymentTracking();
    }

    /**
     * 測試訂單查詢支付平台連線失敗
     */
    public function testTrackingReturnPaymentGatewayConnectionFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment Gateway connection failure',
            180088
        );

        $options = [
            'number' => '001015102600376',
            'orderId' => '201511030000001144',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '127.0.0.1',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 499');
        $response->addHeader('Content-Type:text/html;');

        $syxPay = new ShangYinXin();
        $syxPay->setContainer($this->container);
        $syxPay->setClient($this->client);
        $syxPay->setResponse($response);
        $syxPay->setPrivateKey('test');
        $syxPay->setOptions($options);
        $syxPay->paymentTracking();
    }

    /**
     * 測試訂單查詢支付平台回傳結果為空
     */
    public function testTrackingReturnEmptyPaymentGatewayResponse()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Empty Payment Gateway response',
            180089
        );

        $options = [
            'number' => '001015102600376',
            'orderId' => '201511030000001144',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '127.0.0.1',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $syxPay = new ShangYinXin();
        $syxPay->setContainer($this->container);
        $syxPay->setClient($this->client);
        $syxPay->setResponse($response);
        $syxPay->setPrivateKey('test');
        $syxPay->setOptions($options);
        $syxPay->paymentTracking();
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
            'number' => '001015102600376',
            'orderId' => '201511030000001144',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '127.0.0.1',
        ];

        $result = '<queryResult>' .
            '<srcOutOrderId>201511020000001137</srcOutOrderId>' .
            '<tranAmt>0.01</tranAmt>' .
            '<refunds/>' .
            '<pays>' .
            '</pays>' .
            '</queryResult>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $syxPay = new ShangYinXin();
        $syxPay->setContainer($this->container);
        $syxPay->setClient($this->client);
        $syxPay->setResponse($response);
        $syxPay->setPrivateKey('test');
        $syxPay->setOptions($options);
        $syxPay->paymentTracking();
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
            'number' => '001015102600376',
            'orderId' => '201511030000001144',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '127.0.0.1',
        ];

        $result = '<queryResult>' .
            '<srcOutOrderId>201511020000001137</srcOutOrderId>' .
            '<tranAmt>0.01</tranAmt>' .
            '<refunds/>' .
            '<pays>' .
            '<pay>' .
            '<tranAmt>0.01</tranAmt>' .
            '<srcOutOrderId>201511020000001137</srcOutOrderId>' .
            '<payOrderId>20151102114359134322</payOrderId>' .
            '<payStatus>ORDER_STATUS_PENDING</payStatus>' .
            '</pay>' .
            '</pays>' .
            '</queryResult>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $syxPay = new ShangYinXin();
        $syxPay->setContainer($this->container);
        $syxPay->setClient($this->client);
        $syxPay->setResponse($response);
        $syxPay->setPrivateKey('test');
        $syxPay->setOptions($options);
        $syxPay->paymentTracking();
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
            'number' => '001015102600376',
            'orderId' => '201511030000001144',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '127.0.0.1',
        ];

        $result = '<queryResult>' .
            '<srcOutOrderId>201511020000001137</srcOutOrderId>' .
            '<tranAmt>0.01</tranAmt>' .
            '<refunds/>' .
            '<pays>' .
            '<pay>' .
            '<tranAmt>0.01</tranAmt>' .
            '<srcOutOrderId>201511030000001143</srcOutOrderId>' .
            '<payOrderId>20151102114359134322</payOrderId>' .
            '<payStatus>ORDER_STATUS_SUC</payStatus>' .
            '</pay>' .
            '</pays>' .
            '</queryResult>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $syxPay = new ShangYinXin();
        $syxPay->setContainer($this->container);
        $syxPay->setClient($this->client);
        $syxPay->setResponse($response);
        $syxPay->setPrivateKey('test');
        $syxPay->setOptions($options);
        $syxPay->paymentTracking();
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
            'number' => '001015102600376',
            'amount' => '0.02',
            'orderId' => '201511030000001144',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '127.0.0.1',
        ];

        $result = '<queryResult>' .
            '<srcOutOrderId>201511020000001137</srcOutOrderId>' .
            '<tranAmt>0.01</tranAmt>' .
            '<refunds/>' .
            '<pays>' .
            '<pay>' .
            '<tranAmt>0.1</tranAmt>' .
            '<srcOutOrderId>201511030000001144</srcOutOrderId>' .
            '<payOrderId>20151102114359134322</payOrderId>' .
            '<payStatus>ORDER_STATUS_SUC</payStatus>' .
            '</pay>' .
            '</pays>' .
            '</queryResult>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $syxPay = new ShangYinXin();
        $syxPay->setContainer($this->container);
        $syxPay->setClient($this->client);
        $syxPay->setResponse($response);
        $syxPay->setPrivateKey('test');
        $syxPay->setOptions($options);
        $syxPay->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $options = [
            'number' => '001015102600376',
            'amount' => '0.01',
            'orderId' => '201511030000001144',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '127.0.0.1',
        ];

        $result = '<queryResult>' .
            '<srcOutOrderId>201511020000001137</srcOutOrderId>' .
            '<tranAmt>0.01</tranAmt>' .
            '<refunds/>' .
            '<pays>' .
            '<pay>' .
            '<tranAmt>0.01</tranAmt>' .
            '<srcOutOrderId>201511030000001144</srcOutOrderId>' .
            '<payOrderId>20151102114359134322</payOrderId>' .
            '<payStatus>ORDER_STATUS_SUC</payStatus>' .
            '</pay>' .
            '</pays>' .
            '</queryResult>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $syxPay = new ShangYinXin();
        $syxPay->setContainer($this->container);
        $syxPay->setClient($this->client);
        $syxPay->setResponse($response);
        $syxPay->setPrivateKey('test');
        $syxPay->setOptions($options);
        $syxPay->paymentTracking();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒代入privateKey
     */
    public function testGetPaymentTrackingDataWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $syxPay = new ShangYinXin();
        $syxPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時未指定查詢參數
     */
    public function testGetPaymentTrackingDataWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $syxPay = new ShangYinXin();
        $syxPay->setPrivateKey('test');
        $syxPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒代入verify_url
     */
    public function testGetPaymentTrackingDataWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '001015102600376',
            'orderId' => '201511030000001144',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $syxPay = new ShangYinXin();
        $syxPay->setPrivateKey('test');
        $syxPay->setOptions($options);
        $syxPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'number' => '001015102600376',
            'orderId' => '201511030000001144',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.paymenta.allscore.com',
        ];

        $syxPay = new ShangYinXin();
        $syxPay->setPrivateKey('test');
        $syxPay->setOptions($options);
        $trackingData = $syxPay->getPaymentTrackingData();

        $path = '/olgateway/orderQuery.htm?merchantId=001015102600376' .
            '&outOrderId=201511030000001144&service=orderQuery&inputCharset=utf-8&' .
            'signType=MD5&sign=aedb24fa7ecd6e0176a7798dd54b3950';

        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals($path, $trackingData['path']);
        $this->assertEquals('GET', $trackingData['method']);
        $this->assertEquals('payment.https.paymenta.allscore.com', $trackingData['headers']['Host']);
    }

    /**
     * 測試驗證訂單查詢沒代入privateKey
     */
    public function testPaymentTrackingVerifyWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $syxPay = new ShangYinXin();
        $syxPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢對外支付查詢結果未指定返回參數
     */
    public function testPaymentTrackingVerifyWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = '<queryResult>' .
            '<srcOutOrderId>201511020000001137</srcOutOrderId>' .
            '<tranAmt>0.01</tranAmt>' .
            '<refunds/>' .
            '<pays>' .
            '</pays>' .
            '</queryResult>';
        $sourceData = ['content' => $content];

        $syxPay = new ShangYinXin();
        $syxPay->setPrivateKey('test');
        $syxPay->setOptions($sourceData);
        $syxPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢支付失敗
     */
    public function testPaymentTrackingVerifyPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $content = '<queryResult>' .
            '<srcOutOrderId>201511020000001137</srcOutOrderId>' .
            '<tranAmt>0.01</tranAmt>' .
            '<refunds/>' .
            '<pays>' .
            '<pay>' .
            '<tranAmt>0.01</tranAmt>' .
            '<srcOutOrderId>201511020000001137</srcOutOrderId>' .
            '<payOrderId>20151102114359134322</payOrderId>' .
            '<payStatus>ORDER_STATUS_PENDING</payStatus>' .
            '</pay>' .
            '</pays>' .
            '</queryResult>';
        $sourceData = ['content' => $content];

        $syxPay = new ShangYinXin();
        $syxPay->setPrivateKey('test');
        $syxPay->setOptions($sourceData);
        $syxPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢帶入訂單號不正確
     */
    public function testPaymentTrackingVerifyWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $content = '<queryResult>' .
            '<srcOutOrderId>201511020000001137</srcOutOrderId>' .
            '<tranAmt>0.01</tranAmt>' .
            '<refunds/>' .
            '<pays>' .
            '<pay>' .
            '<tranAmt>0.01</tranAmt>' .
            '<srcOutOrderId>201511030000001143</srcOutOrderId>' .
            '<payOrderId>20151102114359134322</payOrderId>' .
            '<payStatus>ORDER_STATUS_SUC</payStatus>' .
            '</pay>' .
            '</pays>' .
            '</queryResult>';
        $sourceData = [
            'content' => $content,
            'orderId' => '201511030000001144'
        ];

        $syxPay = new ShangYinXin();
        $syxPay->setPrivateKey('test');
        $syxPay->setOptions($sourceData);
        $syxPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢帶入金額不正確
     */
    public function testPaymentTrackingVerifyWithAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $content = '<queryResult>' .
            '<srcOutOrderId>201511020000001137</srcOutOrderId>' .
            '<tranAmt>0.01</tranAmt>' .
            '<refunds/>' .
            '<pays>' .
            '<pay>' .
            '<tranAmt>0.1</tranAmt>' .
            '<srcOutOrderId>201511030000001144</srcOutOrderId>' .
            '<payOrderId>20151102114359134322</payOrderId>' .
            '<payStatus>ORDER_STATUS_SUC</payStatus>' .
            '</pay>' .
            '</pays>' .
            '</queryResult>';
        $sourceData = [
            'content' => $content,
            'orderId' => '201511030000001144',
            'amount' => '0.02'
        ];

        $syxPay = new ShangYinXin();
        $syxPay->setPrivateKey('test');
        $syxPay->setOptions($sourceData);
        $syxPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $content = '<queryResult>' .
            '<srcOutOrderId>201511020000001137</srcOutOrderId>' .
            '<tranAmt>0.01</tranAmt>' .
            '<refunds/>' .
            '<pays>' .
            '<pay>' .
            '<tranAmt>0.01</tranAmt>' .
            '<srcOutOrderId>201511030000001144</srcOutOrderId>' .
            '<payOrderId>20151102114359134322</payOrderId>' .
            '<payStatus>ORDER_STATUS_SUC</payStatus>' .
            '</pay>' .
            '</pays>' .
            '</queryResult>';
        $sourceData = [
            'content' => $content,
            'orderId' => '201511030000001144',
            'amount' => '0.01'
        ];

        $syxPay = new ShangYinXin();
        $syxPay->setPrivateKey('test');
        $syxPay->setOptions($sourceData);
        $syxPay->paymentTrackingVerify();
    }
}
