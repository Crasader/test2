<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ExPay;
use Buzz\Message\Response;

class ExPayTest extends DurianTestCase
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

        $exPay = new ExPay();
        $exPay->getVerifyData();
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

        $exPay = new ExPay();
        $exPay->setPrivateKey('test');
        $exPay->getVerifyData();
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
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '100',
            'number' => '157eab4515dc4401b3a3468bff99398e',
            'orderId' => '201803300000011724',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2018-03-30 15:40:00',
        ];

        $exPay = new ExPay();
        $exPay->setPrivateKey('test');
        $exPay->setOptions($options);
        $exPay->getVerifyData();
    }

    /**
     * 測試支付時缺少verify_url
     */
    public function testPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1111',
            'number' => '157eab4515dc4401b3a3468bff99398e',
            'orderId' => '201803300000011724',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2018-03-30 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $exPay = new ExPay();
        $exPay->setPrivateKey('test');
        $exPay->setOptions($options);
        $exPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回缺少resultCode
     */
    public function testPayReturnWithoutResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '{"payMessage":""}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1111',
            'number' => '157eab4515dc4401b3a3468bff99398e',
            'orderId' => '201803300000011724',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2018-03-30 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $exPay = new ExPay();
        $exPay->setPrivateKey('test');
        $exPay->setContainer($this->container);
        $exPay->setClient($this->client);
        $exPay->setResponse($response);
        $exPay->setOptions($options);
        $exPay->getVerifyData();
    }

    /**
     * 測試支付時返回結果失敗
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
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1111',
            'number' => '157eab4515dc4401b3a3468bff99398e',
            'orderId' => '201803300000011724',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2018-03-30 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $exPay = new ExPay();
        $exPay->setPrivateKey('test');
        $exPay->setContainer($this->container);
        $exPay->setClient($this->client);
        $exPay->setResponse($response);
        $exPay->setOptions($options);
        $exPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回缺少payMessage
     */
    public function testPayReturnWithoutPayMessage()
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
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1111',
            'number' => '157eab4515dc4401b3a3468bff99398e',
            'orderId' => '201803300000011724',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2018-03-30 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $exPay = new ExPay();
        $exPay->setPrivateKey('test');
        $exPay->setContainer($this->container);
        $exPay->setClient($this->client);
        $exPay->setResponse($response);
        $exPay->setOptions($options);
        $exPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $result = '{"respType":"BASE64","returnMsg":"請求成功","resultCode":"0000","errMsg":"",' .
            '"sign":"6DA1104EF6FDE83A75C2EFA49B8B8202","payUrl":"","payMessage":"data:image/png' .
            ';base64,iVBORw0KGgoAAAANSUhEUgAAAGQAAABkAQAAAABYmaj5AAABGklEQVR42uXUMZKEIBAF0CbRK0"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1111',
            'number' => '157eab4515dc4401b3a3468bff99398e',
            'orderId' => '201803300000011724',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2018-03-30 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $exPay = new ExPay();
        $exPay->setPrivateKey('test');
        $exPay->setContainer($this->container);
        $exPay->setClient($this->client);
        $exPay->setResponse($response);
        $exPay->setOptions($options);
        $verifyData = $exPay->getVerifyData();

        $this->assertEmpty($verifyData);

        $getHtml = '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAGQAAABkAQAAAABYmaj5A' .
            'AABGklEQVR42uXUMZKEIBAF0CbRK0"/>';

        $this->assertEquals($getHtml, $exPay->getHtml());
    }

    /**
     * 測試支付時payMessage缺少url
     */
    public function testPayReturnPayMessageWithoutUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $res = [
            'respType' => 'HTML',
            'returnMsg' => '請求成功',
            'resultCode' => '0000',
            'errMsg' => '',
            'sign' => '474CD48F5EEAFD3660B2E1FB754D7CB8',
            'payUrl' => '',
            'payMessage' => "<script>location.href=''</script>",
        ];

        $result = json_encode($res);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1098',
            'number' => '157eab4515dc4401b3a3468bff99398e',
            'orderId' => '201803300000011724',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2018-03-30 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $exPay = new ExPay();
        $exPay->setPrivateKey('test');
        $exPay->setContainer($this->container);
        $exPay->setClient($this->client);
        $exPay->setResponse($response);
        $exPay->setOptions($options);
        $exPay->getVerifyData();
    }

    /**
     * 測試支付寶手機支付
     */
    public function testAliPhonePay()
    {
        $res = [
            'respType' => 'HTML',
            'returnMsg' => '請求成功',
            'resultCode' => '0000',
            'errMsg' => '',
            'sign' => '474CD48F5EEAFD3660B2E1FB754D7CB8',
            'payUrl' => '',
            'payMessage' => "<script>location.href='https://qr.alipay.com/bax085657xk8qrhhhkkk00c8'</script>",
        ];

        $result = json_encode($res);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1098',
            'number' => '157eab4515dc4401b3a3468bff99398e',
            'orderId' => '201803300000011724',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2018-03-30 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $exPay = new ExPay();
        $exPay->setPrivateKey('test');
        $exPay->setContainer($this->container);
        $exPay->setClient($this->client);
        $exPay->setResponse($response);
        $exPay->setOptions($options);
        $verifyData = $exPay->getVerifyData();

        $this->assertEquals('https://qr.alipay.com/bax085657xk8qrhhhkkk00c8', $verifyData['post_url']);
        $this->assertEmpty($verifyData['params']);
        $this->assertEquals('GET', $exPay->getPayMethod());
    }

    /**
     * 測試網銀支付
     */
    public function testPay()
    {
        $payMessage = "<script>location.href='https://cashier.etonepay.com/NetPay/BankSelect.action?" .
            "attach=&backURL=http://gateway.gzwwjy.com/cnpPayNotify/notify/YITONGJF8_NETPAY_T0&bankI" .
            "d=888880160132903&bussId=ONL0001&currencyType=156&entryType=&merOrderNum=20180403103712" .
            "31&merURL=http://fufutest.000webhostapp.com/pay/pay_response.php&merchantId=88820171211" .
            "0114&orderInfo=&reserver1=&reserver2=&reserver3=&reserver4=&signValue=4c582fc0d64d52eb0" .
            "8204f0c09e16e0b&stlmId=&sysTraceNum=2018040310371231&tranAmt=1000&tranDateTime=20180403" .
            "093858&transCode=8888&userId=&userIp=&version=1.0.0'</script>";
        $res = [
            'respType' => 'HTML',
            'returnMsg' => '請求成功',
            'resultCode' => '0000',
            'errMsg' => '',
            'sign' => '474CD48F5EEAFD3660B2E1FB754D7CB8',
            'payUrl' => '',
            'payMessage' => $payMessage,
        ];

        $result = json_encode($res);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1',
            'number' => '157eab4515dc4401b3a3468bff99398e',
            'orderId' => '201803300000011724',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2018-03-30 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $exPay = new ExPay();
        $exPay->setPrivateKey('test');
        $exPay->setContainer($this->container);
        $exPay->setClient($this->client);
        $exPay->setResponse($response);
        $exPay->setOptions($options);
        $verifyData = $exPay->getVerifyData();

        $this->assertEquals('https://cashier.etonepay.com/NetPay/BankSelect.action', $verifyData['post_url']);
        $this->assertEquals('', $verifyData['params']['attach']);

        $backURL = 'http://gateway.gzwwjy.com/cnpPayNotify/notify/YITONGJF8_NETPAY_T0';

        $this->assertEquals($backURL, $verifyData['params']['backURL']);
        $this->assertEquals('888880160132903', $verifyData['params']['bankId']);
        $this->assertEquals('ONL0001', $verifyData['params']['bussId']);
        $this->assertEquals('156', $verifyData['params']['currencyType']);
        $this->assertEquals('', $verifyData['params']['entryType']);
        $this->assertEquals('2018040310371231', $verifyData['params']['merOrderNum']);
        $this->assertEquals('http://fufutest.000webhostapp.com/pay/pay_response.php', $verifyData['params']['merURL']);
        $this->assertEquals('888201712110114', $verifyData['params']['merchantId']);
        $this->assertEquals('', $verifyData['params']['orderInfo']);
        $this->assertEquals('', $verifyData['params']['reserver1']);
        $this->assertEquals('', $verifyData['params']['reserver2']);
        $this->assertEquals('', $verifyData['params']['reserver3']);
        $this->assertEquals('', $verifyData['params']['reserver4']);
        $this->assertEquals('4c582fc0d64d52eb08204f0c09e16e0b', $verifyData['params']['signValue']);
        $this->assertEquals('', $verifyData['params']['stlmId']);
        $this->assertEquals('2018040310371231', $verifyData['params']['sysTraceNum']);
        $this->assertEquals('1000', $verifyData['params']['tranAmt']);
        $this->assertEquals('20180403093858', $verifyData['params']['tranDateTime']);
        $this->assertEquals('8888', $verifyData['params']['transCode']);
        $this->assertEquals('', $verifyData['params']['userId']);
        $this->assertEquals('', $verifyData['params']['userIp']);
        $this->assertEquals('1.0.0', $verifyData['params']['version']);
        $this->assertEquals('GET', $exPay->getPayMethod());
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

        $exPay = new EXPay();
        $exPay->verifyOrderPayment([]);
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

        $exPay = new ExPay();
        $exPay->setPrivateKey('test');
        $exPay->verifyOrderPayment([]);
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
            'orderTime' => '20180330060322',
            'outTradeNo' => '201803300000011724',
            'payKey' => '157eab4515dc4401b3a3468bff99398e',
            'productName' => '201803300000011724',
            'productType' => '50000103',
            'successTime' => '20180402213203',
            'tradeStatus' => 'SUCCESS',
            'trxNo' => '77772018040210365645',
        ];

        $exPay = new ExPay();
        $exPay->setPrivateKey('test');
        $exPay->setOptions($options);
        $exPay->verifyOrderPayment([]);
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
            'orderTime' => '20180330060322',
            'outTradeNo' => '201803300000011724',
            'payKey' => '157eab4515dc4401b3a3468bff99398e',
            'productName' => '201803300000011724',
            'productType' => '50000103',
            'successTime' => '20180402213203',
            'tradeStatus' => 'SUCCESS',
            'trxNo' => '77772018040210365645',
            'sign' => '142D4B8DCA6724D19932D9A488440379',
        ];

        $exPay = new ExPay();
        $exPay->setPrivateKey('test');
        $exPay->setOptions($options);
        $exPay->verifyOrderPayment([]);
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
            'orderTime' => '20180330060322',
            'outTradeNo' => '201803300000011724',
            'payKey' => '157eab4515dc4401b3a3468bff99398e',
            'productName' => '201803300000011724',
            'productType' => '50000103',
            'successTime' => '20180402213203',
            'tradeStatus' => 'WAITING_PAYMENT',
            'trxNo' => '77772018040210365645',
            'sign' => '868e19acb6e0df8bc9f2bf912c652dfc',
        ];

        $exPay = new ExPay();
        $exPay->setPrivateKey('test');
        $exPay->setOptions($options);
        $exPay->verifyOrderPayment([]);
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
            'orderTime' => '20180330060322',
            'outTradeNo' => '201803300000011724',
            'payKey' => '157eab4515dc4401b3a3468bff99398e',
            'productName' => '201803300000011724',
            'productType' => '50000103',
            'successTime' => '20180402213203',
            'tradeStatus' => 'FAIL',
            'trxNo' => '77772018040210365645',
            'sign' => '6a3902fdd95c35c9aba9ffd87b9ebba3',
        ];

        $exPay = new ExPay();
        $exPay->setPrivateKey('test');
        $exPay->setOptions($options);
        $exPay->verifyOrderPayment([]);
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
            'orderTime' => '20180330060322',
            'outTradeNo' => '201803300000011724',
            'payKey' => '157eab4515dc4401b3a3468bff99398e',
            'productName' => '201803300000011724',
            'productType' => '50000103',
            'successTime' => '20180402213203',
            'tradeStatus' => 'SUCCESS',
            'trxNo' => '77772018040210365645',
            'sign' => 'c4bc6337369e300c7895266275381fca',
        ];

        $entry = ['id' => '201503220000000555'];

        $exPay = new ExPay();
        $exPay->setPrivateKey('test');
        $exPay->setOptions($options);
        $exPay->verifyOrderPayment($entry);
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
            'orderTime' => '20180330060322',
            'outTradeNo' => '201803300000011724',
            'payKey' => '157eab4515dc4401b3a3468bff99398e',
            'productName' => '201803300000011724',
            'productType' => '50000103',
            'successTime' => '20180402213203',
            'tradeStatus' => 'SUCCESS',
            'trxNo' => '77772018040210365645',
            'sign' => 'c4bc6337369e300c7895266275381fca',
        ];

        $entry = [
            'id' => '201803300000011724',
            'amount' => '15.00',
        ];

        $exPay = new ExPay();
        $exPay->setPrivateKey('test');
        $exPay->setOptions($options);
        $exPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnSuccess()
    {
        $options = [
            'orderPrice' => '1.00',
            'orderTime' => '20180330060322',
            'outTradeNo' => '201803300000011724',
            'payKey' => '157eab4515dc4401b3a3468bff99398e',
            'productName' => '201803300000011724',
            'productType' => '50000103',
            'successTime' => '20180402213203',
            'tradeStatus' => 'SUCCESS',
            'trxNo' => '77772018040210365645',
            'sign' => 'c4bc6337369e300c7895266275381fca',
        ];

        $entry = [
            'id' => '201803300000011724',
            'amount' => '1.00',
        ];

        $exPay = new ExPay();
        $exPay->setPrivateKey('test');
        $exPay->setOptions($options);
        $exPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $exPay->getMsg());
    }
}
