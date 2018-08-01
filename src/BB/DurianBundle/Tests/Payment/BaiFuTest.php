<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\BaiFu;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class BaiFuTest extends DurianTestCase
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

        $baiFu = new BaiFu();
        $baiFu->getVerifyData();
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

        $baiFu = new BaiFu();
        $baiFu->setPrivateKey('test');
        $baiFu->getVerifyData();
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
            'number' => 'DR18030915224012',
            'amount' => '1',
            'orderId' => '201803190000010323',
            'paymentVendorId' => '9453',
            'notify_url' => 'http://payment/return.php',
            'ip' => '192.168.101.1',
        ];

        $baiFu = new BaiFu();
        $baiFu->setPrivateKey('test');
        $baiFu->setOptions($options);
        $baiFu->getVerifyData();
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
            'number' => 'DR18030915224012',
            'amount' => '1',
            'orderId' => '201803190000010323',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://payment/return.php',
            'ip' => '192.168.101.1',
            'verify_url' => '',
        ];

        $baiFu = new BaiFu();
        $baiFu->setPrivateKey('test');
        $baiFu->setOptions($options);
        $baiFu->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回resultCode
     */
    public function testQrcodePayReturnWithoutResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => 'DR18030915224012',
            'amount' => '1',
            'orderId' => '201803190000010323',
            'paymentVendorId' => '1103',
            'notify_url' => 'http://payment/return.php',
            'ip' => '192.168.101.1',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $baiFu = new BaiFu();
        $baiFu->setContainer($this->container);
        $baiFu->setClient($this->client);
        $baiFu->setResponse($response);
        $baiFu->setPrivateKey('test');
        $baiFu->setOptions($options);
        $baiFu->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回resultMsg
     */
    public function testQrcodePayReturnWithoutResultMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => 'DR18030915224012',
            'amount' => '1',
            'orderId' => '201803190000010323',
            'paymentVendorId' => '1103',
            'notify_url' => 'http://payment/return.php',
            'ip' => '192.168.101.1',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = ['resultCode' => '99'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $baiFu = new BaiFu();
        $baiFu->setContainer($this->container);
        $baiFu->setClient($this->client);
        $baiFu->setResponse($response);
        $baiFu->setPrivateKey('test');
        $baiFu->setOptions($options);
        $baiFu->getVerifyData();
    }

    /**
     * 測試二維支付時返回提交失敗
     */
    public function testQrcodePayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Request Param is Null',
            180130
        );

        $options = [
            'number' => 'DR18030915224012',
            'amount' => '1',
            'orderId' => '201803190000010323',
            'paymentVendorId' => '1103',
            'notify_url' => 'http://payment/return.php',
            'ip' => '192.168.101.1',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'resultCode' => '99',
            'resultMsg' => 'Request Param is Null',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $baiFu = new BaiFu();
        $baiFu->setContainer($this->container);
        $baiFu->setClient($this->client);
        $baiFu->setResponse($response);
        $baiFu->setPrivateKey('test');
        $baiFu->setOptions($options);
        $baiFu->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回CodeUrl
     */
    public function testQrcodePayReturnWithoutCodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => 'DR18030915224012',
            'amount' => '1',
            'orderId' => '201803190000010323',
            'paymentVendorId' => '1103',
            'notify_url' => 'http://payment/return.php',
            'ip' => '192.168.101.1',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'merchantNo' => 'DR180309114511502',
            'orderNum' => '201803190000010311',
            'resultCode' => '00',
            'resultMsg' => '提交成功',
            'sign' => '8986E95C3BAD95A496BAB2C6EB299E5A',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $baiFu = new BaiFu();
        $baiFu->setContainer($this->container);
        $baiFu->setClient($this->client);
        $baiFu->setResponse($response);
        $baiFu->setPrivateKey('test');
        $baiFu->setOptions($options);
        $baiFu->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $options = [
            'number' => 'DR18030915224012',
            'amount' => '1',
            'orderId' => '201803190000010323',
            'paymentVendorId' => '1103',
            'notify_url' => 'http://payment/return.php',
            'ip' => '192.168.101.1',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'CodeUrl' => 'https://qpay.qq.com/qr/6cfca9fd',
            'merchantNo' => 'DR180309114511502',
            'orderNum' => '201803190000010311',
            'resultCode' => '00',
            'resultMsg' => '提交成功',
            'sign' => '8986E95C3BAD95A496BAB2C6EB299E5A',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $baiFu = new BaiFu();
        $baiFu->setContainer($this->container);
        $baiFu->setClient($this->client);
        $baiFu->setResponse($response);
        $baiFu->setPrivateKey('test');
        $baiFu->setOptions($options);
        $data = $baiFu->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://qpay.qq.com/qr/6cfca9fd', $baiFu->getQrcode());
    }

    /**
     * 測試手機支付時沒有返回resultCode
     */
    public function testPhonePayReturnWithoutResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => 'DR18030915224012',
            'amount' => '1',
            'orderId' => '201803190000010323',
            'paymentVendorId' => '1097',
            'notify_url' => 'http://payment/return.php',
            'ip' => '192.168.101.1',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $baiFu = new BaiFu();
        $baiFu->setContainer($this->container);
        $baiFu->setClient($this->client);
        $baiFu->setResponse($response);
        $baiFu->setPrivateKey('test');
        $baiFu->setOptions($options);
        $baiFu->getVerifyData();
    }

    /**
     * 測試手機支付時沒有返回resultMsg
     */
    public function testPhonePayReturnWithoutResultMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => 'DR18030915224012',
            'amount' => '1',
            'orderId' => '201803190000010323',
            'paymentVendorId' => '1097',
            'notify_url' => 'http://payment/return.php',
            'ip' => '192.168.101.1',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = ['resultCode' => '99'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $baiFu = new BaiFu();
        $baiFu->setContainer($this->container);
        $baiFu->setClient($this->client);
        $baiFu->setResponse($response);
        $baiFu->setPrivateKey('test');
        $baiFu->setOptions($options);
        $baiFu->getVerifyData();
    }

    /**
     * 測試手機支付時返回提交失敗
     */
    public function testPhonePayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'WX_WAP不能低于:1.01元',
            180130
        );

        $options = [
            'number' => 'DR18030915224012',
            'amount' => '1',
            'orderId' => '201803190000010323',
            'paymentVendorId' => '1097',
            'notify_url' => 'http://payment/return.php',
            'ip' => '192.168.101.1',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'resultCode' => '99',
            'resultMsg' => 'WX_WAP不能低于:1.01元',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $baiFu = new BaiFu();
        $baiFu->setContainer($this->container);
        $baiFu->setClient($this->client);
        $baiFu->setResponse($response);
        $baiFu->setPrivateKey('test');
        $baiFu->setOptions($options);
        $baiFu->getVerifyData();
    }

    /**
     * 測試手機支付時沒有返回CodeUrl
     */
    public function testPhonePayReturnWithoutCodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => 'DR18030915224012',
            'amount' => '1',
            'orderId' => '201803190000010323',
            'paymentVendorId' => '1097',
            'notify_url' => 'http://payment/return.php',
            'ip' => '192.168.101.1',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'merchantNo' => 'DR180309114511502',
            'orderNum' => '201803190000010304',
            'resultCode' => '00',
            'resultMsg' => '提交成功',
            'sign' => '21AA6A1A51E8E5A77817F28947B19C7B',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $baiFu = new BaiFu();
        $baiFu->setContainer($this->container);
        $baiFu->setClient($this->client);
        $baiFu->setResponse($response);
        $baiFu->setPrivateKey('test');
        $baiFu->setOptions($options);
        $baiFu->getVerifyData();
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $options = [
            'number' => 'DR18030915224012',
            'amount' => '1',
            'orderId' => '201803190000010323',
            'paymentVendorId' => '1097',
            'notify_url' => 'http://payment/return.php',
            'ip' => '192.168.101.1',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'CodeUrl' => 'https://h5pay.jd.com/code?c=5wrgtpppf0udwr',
            'merchantNo' => 'DR180309114511502',
            'orderNum' => '201803190000010304',
            'resultCode' => '00',
            'resultMsg' => '提交成功',
            'sign' => '21AA6A1A51E8E5A77817F28947B19C7B',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $baiFu = new BaiFu();
        $baiFu->setContainer($this->container);
        $baiFu->setClient($this->client);
        $baiFu->setResponse($response);
        $baiFu->setPrivateKey('test');
        $baiFu->setOptions($options);
        $data = $baiFu->getVerifyData();

        $this->assertEquals('https://h5pay.jd.com/code', $data['post_url']);
        $this->assertEquals('5wrgtpppf0udwr', $data['params']['c']);
        $this->assertEquals('GET', $baiFu->getPayMethod());
    }

    /**
     * 測試條碼支付
     */
    public function testCodePay()
    {
        $options = [
            'number' => 'DR18030915224012',
            'amount' => '1',
            'orderId' => '201803190000010323',
            'paymentVendorId' => '1115',
            'notify_url' => 'http://payment/return.php',
            'ip' => '192.168.101.1',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'CodeUrl' => 'http://scan.948pay.com:8188/scanPage/20180411/dry5D80358CF.jsp',
            'merchantNo' => 'DR180309152240127',
            'orderNum' => '201804110000004771',
            'resultCode' => "00",
            'resultMsg' => '提交成功',
            'sign' => '41FE484E4C5960D22DD468D6C1E76F5A',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $baiFu = new BaiFu();
        $baiFu->setContainer($this->container);
        $baiFu->setClient($this->client);
        $baiFu->setResponse($response);
        $baiFu->setPrivateKey('test');
        $baiFu->setOptions($options);
        $data = $baiFu->getVerifyData();

        $this->assertEmpty($data['params']);
        $this->assertEquals('http://scan.948pay.com:8188/scanPage/20180411/dry5D80358CF.jsp', $data['post_url']);
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

        $baiFu = new BaiFu();
        $baiFu->verifyOrderPayment([]);
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

        $baiFu = new BaiFu();
        $baiFu->setPrivateKey('test');
        $baiFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未返回簽名
     */
    public function testReturnWithoutSignature()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'goodsName' => '201803190000010323',
            'merchantNo' => 'DR180309114511502',
            'netwayCode' => 'QQ',
            'orderNum' => '201803190000010323',
            'payAmount' => '100',
            'payDate' => '2018-03-19 16:02:45',
            'resultCode' => '00',
        ];

        $baiFu = new BaiFu();
        $baiFu->setPrivateKey('test');
        $baiFu->setOptions($options);
        $baiFu->verifyOrderPayment([]);
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

        $options = [
            'goodsName' => '201803190000010323',
            'merchantNo' => 'DR180309114511502',
            'netwayCode' => 'QQ',
            'orderNum' => '201803190000010323',
            'payAmount' => '100',
            'payDate' => '2018-03-19 16:02:45',
            'resultCode' => '00',
            'sign' => 'test',
        ];

        $baiFu = new BaiFu();
        $baiFu->setPrivateKey('test');
        $baiFu->setOptions($options);
        $baiFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付失敗
     */
    public function testReturnWithPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $options = [
            'goodsName' => '201803190000010323',
            'merchantNo' => 'DR180309114511502',
            'netwayCode' => 'QQ',
            'orderNum' => '201803190000010323',
            'payAmount' => '100',
            'payDate' => '2018-03-19 16:02:45',
            'resultCode' => '99',
            'sign' => 'B3755FB4E648595C456156D716DCE2F0',
        ];

        $baiFu = new BaiFu();
        $baiFu->setPrivateKey('test');
        $baiFu->setOptions($options);
        $baiFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單號錯誤
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $options = [
            'goodsName' => '201803190000010323',
            'merchantNo' => 'DR180309114511502',
            'netwayCode' => 'QQ',
            'orderNum' => '201803190000010323',
            'payAmount' => '100',
            'payDate' => '2018-03-19 16:02:45',
            'resultCode' => '00',
            'sign' => '190A2B1888DCE71A6774ACA834FB242A',
        ];

        $entry = ['id' => '666666'];

        $baiFu = new BaiFu();
        $baiFu->setPrivateKey('test');
        $baiFu->setOptions($options);
        $baiFu->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為金額錯誤
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $options = [
            'goodsName' => '201803190000010323',
            'merchantNo' => 'DR180309114511502',
            'netwayCode' => 'QQ',
            'orderNum' => '201803190000010323',
            'payAmount' => '100',
            'payDate' => '2018-03-19 16:02:45',
            'resultCode' => '00',
            'sign' => '190A2B1888DCE71A6774ACA834FB242A',
        ];

        $entry = [
            'id' => '201803190000010323',
            'amount' => '777',
        ];

        $baiFu = new BaiFu();
        $baiFu->setPrivateKey('test');
        $baiFu->setOptions($options);
        $baiFu->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $options = [
            'goodsName' => '201803190000010323',
            'merchantNo' => 'DR180309114511502',
            'netwayCode' => 'QQ',
            'orderNum' => '201803190000010323',
            'payAmount' => '100',
            'payDate' => '2018-03-19 16:02:45',
            'resultCode' => '00',
            'sign' => '190A2B1888DCE71A6774ACA834FB242A',
        ];

        $entry = [
            'id' => '201803190000010323',
            'amount' => '1',
        ];

        $baiFu = new BaiFu();
        $baiFu->setPrivateKey('test');
        $baiFu->setOptions($options);
        $baiFu->verifyOrderPayment($entry);

        $this->assertEquals('000000', $baiFu->getMsg());
    }
}
