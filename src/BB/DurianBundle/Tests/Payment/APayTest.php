<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\APay;
use Buzz\Message\Response;

class APayTest extends DurianTestCase
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

        $aPay = new APay();
        $aPay->getVerifyData();
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

        $aPay = new APay();
        $aPay->setPrivateKey('test');
        $aPay->getVerifyData();
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
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '100',
            'number' => '93193f66d1324007902c0da35d4674f4',
            'orderId' => '201803230000011336',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2018-03-23 15:40:00',
        ];

        $aPay = new APay();
        $aPay->setPrivateKey('test');
        $aPay->setOptions($options);
        $aPay->getVerifyData();
    }

    /**
     * 測試二維支付時缺少verify_url
     */
    public function testPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1103',
            'number' => '93193f66d1324007902c0da35d4674f4',
            'orderId' => '201803230000011336',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2018-03-23 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $aPay = new APay();
        $aPay->setPrivateKey('test');
        $aPay->setOptions($options);
        $aPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回缺少errMsg
     */
    public function testPayReturnWithoutErrMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '{"resultCode":"0001","payMessage":""}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1103',
            'number' => '93193f66d1324007902c0da35d4674f4',
            'orderId' => '201803230000011336',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2018-03-23 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $aPay = new APay();
        $aPay->setPrivateKey('test');
        $aPay->setContainer($this->container);
        $aPay->setClient($this->client);
        $aPay->setResponse($response);
        $aPay->setOptions($options);
        $aPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回結果失敗
     */
    public function testPayReturnButFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '商户异常',
            180130
        );

        $result = '{"resultCode":"0001","payMessage":"","errMsg":"商户异常"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1103',
            'number' => '93193f66d1324007902c0da35d4674f4',
            'orderId' => '201803230000011336',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2018-03-23 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $aPay = new APay();
        $aPay->setPrivateKey('test');
        $aPay->setContainer($this->container);
        $aPay->setClient($this->client);
        $aPay->setResponse($response);
        $aPay->setOptions($options);
        $aPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回缺少qrcode
     */
    public function testPayReturnWithoutQrcode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '{"resultCode":"0000","sign":"0A06562B6DB0F2DEC360F0EE79AD71F1","errMsg":""}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1103',
            'number' => '93193f66d1324007902c0da35d4674f4',
            'orderId' => '201803230000011336',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2018-03-23 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $aPay = new APay();
        $aPay->setPrivateKey('test');
        $aPay->setContainer($this->container);
        $aPay->setClient($this->client);
        $aPay->setResponse($response);
        $aPay->setOptions($options);
        $aPay->getVerifyData();
    }

    /**
     * 測試手機支付時返回缺少跳轉網址
     */
    public function testPhonePayReturnWithoutHref()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '{"resultCode":"0000","sign":"0A06562B6DB0F2DEC360F0EE79AD71F1", "payMessage":"<form id=\"' .
            'pay_form\"></form>", "errMsg":""}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1104',
            'number' => '93193f66d1324007902c0da35d4674f4',
            'orderId' => '201803230000011336',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2018-03-23 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $aPay = new APay();
        $aPay->setPrivateKey('test');
        $aPay->setContainer($this->container);
        $aPay->setClient($this->client);
        $aPay->setResponse($response);
        $aPay->setOptions($options);
        $aPay->getVerifyData();
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $result = '{"resultCode":"0000","sign":"0A06562B6DB0F2DEC360F0EE79AD71F1",' .
            '"payMessage":"<html><head></head><body><form id=\"pay_form\" name=\"pay_form\" action=\"' .
            'https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027&_bid=2183&t=6Vd17a5627c4f7958291b6a6e3efeac1\" meth' .
            'od=\"POST\"></form><script language=\"javascript\">window.onload=function(){window.location= \"' .
            'https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027&_bid=2183&t=6Vd17a5627c4f7958291b6a6e3efeac1\";}</s' .
            'cript></body></html>","errMsg":""}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1104',
            'number' => '93193f66d1324007902c0da35d4674f4',
            'orderId' => '201803230000011336',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2018-03-23 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $aPay = new APay();
        $aPay->setPrivateKey('test');
        $aPay->setContainer($this->container);
        $aPay->setClient($this->client);
        $aPay->setResponse($response);
        $aPay->setOptions($options);
        $verifyData = $aPay->getVerifyData();

        $this->assertEquals('GET', $aPay->getPayMethod());
        $this->assertEquals('1027', $verifyData['params']['_wv']);
        $this->assertEquals('2183', $verifyData['params']['_bid']);
        $this->assertEquals('6Vd17a5627c4f7958291b6a6e3efeac1', $verifyData['params']['t']);
        $this->assertEquals('https://myun.tenpay.com/mqq/pay/qrcode.html', $verifyData['post_url']);
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $result = '{"resultCode":"0000","sign":"0A06562B6DB0F2DEC360F0EE79AD71F1",' .
            '"payMessage":"https://qpay.qq.com/qr/69546cd2","errMsg":""}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1103',
            'number' => '93193f66d1324007902c0da35d4674f4',
            'orderId' => '201803230000011336',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2018-03-23 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $aPay = new APay();
        $aPay->setPrivateKey('test');
        $aPay->setContainer($this->container);
        $aPay->setClient($this->client);
        $aPay->setResponse($response);
        $aPay->setOptions($options);
        $verifyData = $aPay->getVerifyData();

        $this->assertEmpty($verifyData);
        $this->assertSame('https://qpay.qq.com/qr/69546cd2', $aPay->getQrcode());
    }

    /**
     * 測試網銀支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'postUrl' => 'https://gateway.rffbe.top',
            'paymentVendorId' => '1102',
            'number' => '93193f66d1324007902c0da35d4674f4',
            'orderId' => '201803230000011336',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2018-03-23 15:40:00',
        ];

        $aPay = new APay();
        $aPay->setPrivateKey('test');
        $aPay->setOptions($options);
        $verifyData = $aPay->getVerifyData();

        $this->assertEquals('93193f66d1324007902c0da35d4674f4', $verifyData['params']['payKey']);
        $this->assertEquals('100.00', $verifyData['params']['orderPrice']);
        $this->assertEquals('201803230000011336', $verifyData['params']['outTradeNo']);
        $this->assertEquals('50000103', $verifyData['params']['productType']);
        $this->assertEquals('20180323154000', $verifyData['params']['orderTime']);
        $this->assertEquals('201803230000011336', $verifyData['params']['productName']);
        $this->assertEquals('127.0.0.1', $verifyData['params']['orderIp']);
        $this->assertEquals('http://two123.comxa.com/', $verifyData['params']['returnUrl']);
        $this->assertEquals('http://two123.comxa.com/', $verifyData['params']['notifyUrl']);
        $this->assertEquals('', $verifyData['params']['remark']);
        $this->assertEquals('83A20413F1582BD835902E0736920F7C', $verifyData['params']['sign']);
    }

    /**
     * 測試快捷支付
     */
    public function testQuickPay()
    {
        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'postUrl' => 'https://gateway.rffbe.top',
            'paymentVendorId' => '278',
            'number' => '93193f66d1324007902c0da35d4674f4',
            'orderId' => '201803230000011336',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2018-03-23 15:40:00',
        ];

        $aPay = new APay();
        $aPay->setPrivateKey('test');
        $aPay->setOptions($options);
        $verifyData = $aPay->getVerifyData();

        $this->assertEquals('93193f66d1324007902c0da35d4674f4', $verifyData['params']['payKey']);
        $this->assertEquals('100.00', $verifyData['params']['orderPrice']);
        $this->assertEquals('201803230000011336', $verifyData['params']['outTradeNo']);
        $this->assertEquals('40000503', $verifyData['params']['productType']);
        $this->assertEquals('20180323154000', $verifyData['params']['orderTime']);
        $this->assertEquals('201803230000011336', $verifyData['params']['productName']);
        $this->assertEquals('127.0.0.1', $verifyData['params']['orderIp']);
        $this->assertEquals('http://two123.comxa.com/', $verifyData['params']['returnUrl']);
        $this->assertEquals('http://two123.comxa.com/', $verifyData['params']['notifyUrl']);
        $this->assertEquals('', $verifyData['params']['remark']);
        $this->assertEquals('687A1091AEBBBF6726520FD6336B39A8', $verifyData['params']['sign']);
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

        $aPay = new APay();
        $aPay->verifyOrderPayment([]);
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

        $aPay = new APay();
        $aPay->setPrivateKey('test');
        $aPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少簽名
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'orderPrice' => '1.00',
            'orderTime' => '20180323160544',
            'outTradeNo' => '201803230000011336',
            'payKey' => '93193f66d1324007902c0da35d4674f4',
            'productName' => '201803230000011336',
            'productType' => '50000103',
            'successTime' => '20180323160641',
            'tradeStatus' => 'SUCCESS',
            'trxNo' => 'P88882018030910001559',
        ];

        $aPay = new APay();
        $aPay->setPrivateKey('test');
        $aPay->setOptions($options);
        $aPay->verifyOrderPayment([]);
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
            'orderPrice' => '1.00',
            'orderTime' => '20180323160544',
            'outTradeNo' => '201803230000011336',
            'payKey' => '93193f66d1324007902c0da35d4674f4',
            'productName' => '201803230000011336',
            'productType' => '50000103',
            'successTime' => '20180323160641',
            'tradeStatus' => 'SUCCESS',
            'trxNo' => 'P88882018030910001559',
            'sign' => '142D4B8DCA6724D19932D9A488440379',
        ];

        $aPay = new APay();
        $aPay->setPrivateKey('test');
        $aPay->setOptions($options);
        $aPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時訂單未支付
     */
    public function testReturnUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $options = [
            'orderPrice' => '1.00',
            'orderTime' => '20180323160544',
            'outTradeNo' => '201803230000011336',
            'payKey' => '93193f66d1324007902c0da35d4674f4',
            'productName' => '201803230000011336',
            'productType' => '50000103',
            'successTime' => '20180323160641',
            'tradeStatus' => 'WAITING_PAYMENT',
            'trxNo' => 'P88882018030910001559',
            'sign' => '99aa90ebb00c2c727fcba8a781119d4b',
        ];

        $aPay = new APay();
        $aPay->setPrivateKey('test');
        $aPay->setOptions($options);
        $aPay->verifyOrderPayment([]);
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

        $options = [
            'orderPrice' => '1.00',
            'orderTime' => '20180323160544',
            'outTradeNo' => '201803230000011336',
            'payKey' => '93193f66d1324007902c0da35d4674f4',
            'productName' => '201803230000011336',
            'productType' => '50000103',
            'successTime' => '20180323160641',
            'tradeStatus' => 'FAILED',
            'trxNo' => 'P88882018030910001559',
            'sign' => '487ff823654bebfbc87df77329e93b63',
        ];

        $aPay = new APay();
        $aPay->setPrivateKey('test');
        $aPay->setOptions($options);
        $aPay->verifyOrderPayment([]);
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
            'orderPrice' => '1.00',
            'orderTime' => '20180323160544',
            'outTradeNo' => '201803230000011336',
            'payKey' => '93193f66d1324007902c0da35d4674f4',
            'productName' => '201803230000011336',
            'productType' => '50000103',
            'successTime' => '20180323160641',
            'tradeStatus' => 'SUCCESS',
            'trxNo' => 'P88882018030910001559',
            'sign' => 'fa74f6be49af153bf9b0252106bd597c',
        ];

        $entry = ['id' => '201503220000000555'];

        $aPay = new APay();
        $aPay->setPrivateKey('test');
        $aPay->setOptions($options);
        $aPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為訂單金額錯誤
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $options = [
            'orderPrice' => '1.00',
            'orderTime' => '20180323160544',
            'outTradeNo' => '201803230000011336',
            'payKey' => '93193f66d1324007902c0da35d4674f4',
            'productName' => '201803230000011336',
            'productType' => '50000103',
            'successTime' => '20180323160641',
            'tradeStatus' => 'SUCCESS',
            'trxNo' => 'P88882018030910001559',
            'sign' => 'fa74f6be49af153bf9b0252106bd597c',
        ];

        $entry = [
            'id' => '201803230000011336',
            'amount' => '15.00',
        ];

        $aPay = new APay();
        $aPay->setPrivateKey('test');
        $aPay->setOptions($options);
        $aPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnSuccess()
    {
        $options = [
            'orderPrice' => '1.00',
            'orderTime' => '20180323160544',
            'outTradeNo' => '201803230000011336',
            'payKey' => '93193f66d1324007902c0da35d4674f4',
            'productName' => '201803230000011336',
            'productType' => '50000103',
            'successTime' => '20180323160641',
            'tradeStatus' => 'SUCCESS',
            'trxNo' => 'P88882018030910001559',
            'sign' => 'fa74f6be49af153bf9b0252106bd597c',
        ];

        $entry = [
            'id' => '201803230000011336',
            'amount' => '1.00',
        ];

        $aPay = new APay();
        $aPay->setPrivateKey('test');
        $aPay->setOptions($options);
        $aPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $aPay->getMsg());
    }
}
