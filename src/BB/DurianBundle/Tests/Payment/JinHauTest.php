<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\JinHau;
use Buzz\Message\Response;

class JinHauTest extends DurianTestCase
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

        $jinHau = new JinHau();
        $jinHau->getVerifyData();
    }

    /**
     * 測試支付時沒未指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $jinHau = new JinHau();
        $jinHau->setPrivateKey('test');
        $jinHau->getVerifyData();
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
            'number' => '20171109b9',
            'orderId' => '201711150000007600',
            'amount' => '100',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2017-11-15 15:40:00',
        ];

        $jinHau = new JinHau();
        $jinHau->setPrivateKey('test');
        $jinHau->setOptions($options);
        $jinHau->getVerifyData();
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
            'number' => '20171109b9',
            'orderId' => '201711150000007600',
            'amount' => '100',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2017-11-15 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $jinHau = new JinHau();
        $jinHau->setPrivateKey('test');
        $jinHau->setOptions($options);
        $jinHau->getVerifyData();
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
            'number' => '20171109b9',
            'orderId' => '201711150000007600',
            'amount' => '100',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2017-11-15 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $jinHau = new JinHau();
        $jinHau->setPrivateKey('test');
        $jinHau->setContainer($this->container);
        $jinHau->setClient($this->client);
        $jinHau->setResponse($response);
        $jinHau->setOptions($options);
        $jinHau->getVerifyData();
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
            'number' => '20171109b9',
            'orderId' => '201711150000007600',
            'amount' => '100',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2017-11-15 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $jinHau = new JinHau();
        $jinHau->setPrivateKey('test');
        $jinHau->setContainer($this->container);
        $jinHau->setClient($this->client);
        $jinHau->setResponse($response);
        $jinHau->setOptions($options);
        $jinHau->getVerifyData();
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
            'number' => '20171109b9',
            'orderId' => '201711150000007600',
            'amount' => '100',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2017-11-15 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $jinHau = new JinHau();
        $jinHau->setPrivateKey('test');
        $jinHau->setContainer($this->container);
        $jinHau->setClient($this->client);
        $jinHau->setResponse($response);
        $jinHau->setOptions($options);
        $jinHau->getVerifyData();
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
            'number' => '20171109b9',
            'orderId' => '201711150000007600',
            'amount' => '100',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2017-11-15 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $jinHau = new JinHau();
        $jinHau->setPrivateKey('test');
        $jinHau->setContainer($this->container);
        $jinHau->setClient($this->client);
        $jinHau->setResponse($response);
        $jinHau->setOptions($options);
        $verifyData = $jinHau->getVerifyData();

        $this->assertEmpty($verifyData);
        $this->assertSame('https://qpay.qq.com/qr/69546cd2', $jinHau->getQrcode());
    }

    /**
     * 測試網銀支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1',
            'number' => '20171109b9',
            'orderId' => '201711150000007600',
            'amount' => '100',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2017-11-15 15:40:00',
        ];

        $jinHau = new JinHau();
        $jinHau->setPrivateKey('test');
        $jinHau->setOptions($options);
        $verifyData = $jinHau->getVerifyData();

        $this->assertEquals('20171109b9', $verifyData['payKey']);
        $this->assertEquals('100.00', $verifyData['orderPrice']);
        $this->assertEquals('201711150000007600', $verifyData['outTradeNo']);
        $this->assertEquals('50000103', $verifyData['productType']);
        $this->assertEquals('20171115154000', $verifyData['orderTime']);
        $this->assertEquals('php1test', $verifyData['productName']);
        $this->assertEquals('127.0.0.1', $verifyData['orderIp']);
        $this->assertEquals('ICBC', $verifyData['bankCode']);
        $this->assertEquals('PRIVATE_DEBIT_ACCOUNT', $verifyData['bankAccountType']);
        $this->assertEquals('http://two123.comxa.com/', $verifyData['returnUrl']);
        $this->assertEquals('http://two123.comxa.com/', $verifyData['notifyUrl']);
        $this->assertEquals('', $verifyData['remark']);
        $this->assertEquals('', $verifyData['mobile']);
        $this->assertEquals('17114699F11AFADF5C0582134DBFAFA1', $verifyData['sign']);
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

        $jinHau = new JinHau();
        $jinHau->verifyOrderPayment([]);
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

        $jinHau = new JinHau();
        $jinHau->setPrivateKey('test');
        $jinHau->verifyOrderPayment([]);
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
            'orderTime' => '20171116160544',
            'outTradeNo' => '201711160000007667',
            'payKey' => 'e887d52d76fd468b93558f4590b16510',
            'productName' => 'php1test',
            'productType' => '50000103',
            'successTime' => '20171116160641',
            'tradeStatus' => 'SUCCESS',
            'trxNo' => 'PRO77772017111610022894',
        ];

        $jinHau = new JinHau();
        $jinHau->setPrivateKey('test');
        $jinHau->setOptions($options);
        $jinHau->verifyOrderPayment([]);
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
            'orderTime' => '20171116160544',
            'outTradeNo' => '201711160000007667',
            'payKey' => 'e887d52d76fd468b93558f4590b16510',
            'productName' => 'php1test',
            'productType' => '50000103',
            'successTime' => '20171116160641',
            'tradeStatus' => 'SUCCESS',
            'trxNo' => 'PRO77772017111610022894',
            'sign' => '142D4B8DCA6724D19932D9A488440379',
        ];

        $jinHau = new JinHau();
        $jinHau->setPrivateKey('test');
        $jinHau->setOptions($options);
        $jinHau->verifyOrderPayment([]);
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
            'orderTime' => '20171116160544',
            'outTradeNo' => '201711160000007667',
            'payKey' => 'e887d52d76fd468b93558f4590b16510',
            'productName' => 'php1test',
            'productType' => '50000103',
            'successTime' => '20171116160641',
            'tradeStatus' => 'WAITING_PAYMENT',
            'trxNo' => 'PRO77772017111610022894',
            'sign' => '2cb6132adc94a2c21f1dc4e156d48f0e',
        ];

        $jinHau = new JinHau();
        $jinHau->setPrivateKey('test');
        $jinHau->setOptions($options);
        $jinHau->verifyOrderPayment([]);
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
            'orderTime' => '20171116160544',
            'outTradeNo' => '201711160000007667',
            'payKey' => 'e887d52d76fd468b93558f4590b16510',
            'productName' => 'php1test',
            'productType' => '50000103',
            'successTime' => '20171116160641',
            'tradeStatus' => 'FAIL',
            'trxNo' => 'PRO77772017111610022894',
            'sign' => 'cad43c07a4213063ba86a37a79dd011d',
        ];

        $jinHau = new JinHau();
        $jinHau->setPrivateKey('test');
        $jinHau->setOptions($options);
        $jinHau->verifyOrderPayment([]);
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
            'orderTime' => '20171116160544',
            'outTradeNo' => '201711160000007667',
            'payKey' => 'e887d52d76fd468b93558f4590b16510',
            'productName' => 'php1test',
            'productType' => '50000103',
            'successTime' => '20171116160641',
            'tradeStatus' => 'SUCCESS',
            'trxNo' => 'PRO77772017111610022894',
            'sign' => 'a7ae2d3e1fee61ef5a569e057f764dc5',
        ];

        $entry = ['id' => '201503220000000555'];

        $jinHau = new JinHau();
        $jinHau->setPrivateKey('test');
        $jinHau->setOptions($options);
        $jinHau->verifyOrderPayment($entry);
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
            'orderTime' => '20171116160544',
            'outTradeNo' => '201711160000007667',
            'payKey' => 'e887d52d76fd468b93558f4590b16510',
            'productName' => 'php1test',
            'productType' => '50000103',
            'successTime' => '20171116160641',
            'tradeStatus' => 'SUCCESS',
            'trxNo' => 'PRO77772017111610022894',
            'sign' => 'a7ae2d3e1fee61ef5a569e057f764dc5',
        ];

        $entry = [
            'id' => '201711160000007667',
            'amount' => '15.00',
        ];

        $jinHau = new JinHau();
        $jinHau->setPrivateKey('test');
        $jinHau->setOptions($options);
        $jinHau->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnSuccess()
    {
        $options = [
            'orderPrice' => '1.00',
            'orderTime' => '20171116160544',
            'outTradeNo' => '201711160000007667',
            'payKey' => 'e887d52d76fd468b93558f4590b16510',
            'productName' => 'php1test',
            'productType' => '50000103',
            'successTime' => '20171116160641',
            'tradeStatus' => 'SUCCESS',
            'trxNo' => 'PRO77772017111610022894',
            'sign' => 'a7ae2d3e1fee61ef5a569e057f764dc5',
        ];

        $entry = [
            'id' => '201711160000007667',
            'amount' => '1.00',
        ];

        $jinHau = new JinHau();
        $jinHau->setPrivateKey('test');
        $jinHau->setOptions($options);
        $jinHau->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $jinHau->getMsg());
    }
}
