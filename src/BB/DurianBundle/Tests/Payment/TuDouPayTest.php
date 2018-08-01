<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\TuDouPay;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class TuDouPayTest extends DurianTestCase
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

        $tuDouPay = new TuDouPay();
        $tuDouPay->getVerifyData();
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

        $tuDouPay = new TuDouPay();
        $tuDouPay->setPrivateKey('test');
        $tuDouPay->getVerifyData();
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
            'number' => '900100004',
            'amount' => '100',
            'orderId' => '201805020000012604',
            'paymentVendorId' => '9453',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'ip' => '111.235.135.54',
        ];

        $tuDouPay = new TuDouPay();
        $tuDouPay->setPrivateKey('test');
        $tuDouPay->setOptions($options);
        $tuDouPay->getVerifyData();
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
            'number' => '900100004',
            'amount' => '9453',
            'orderId' => '201805020000012604',
            'paymentVendorId' => '1103',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => '',
            'ip' => '111.235.135.54',
        ];

        $tuDouPay = new TuDouPay();
        $tuDouPay->setPrivateKey('test');
        $tuDouPay->setOptions($options);
        $tuDouPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回status
     */
    public function testPayReturnWithoutStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '900100004',
            'amount' => '0.01',
            'orderId' => '201805020000012604',
            'paymentVendorId' => '1103',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'ip' => '111.235.135.54',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $tuDouPay = new TuDouPay();
        $tuDouPay->setContainer($this->container);
        $tuDouPay->setClient($this->client);
        $tuDouPay->setResponse($response);
        $tuDouPay->setPrivateKey('test');
        $tuDouPay->setOptions($options);
        $tuDouPay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $options = [
            'number' => '900100004',
            'amount' => '0.1',
            'orderId' => '201703240000001427',
            'paymentVendorId' => '1103',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'ip' => '111.235.135.54',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'status' => 'FAILED',
            'message' => '交易处理中:58:交易失败,业务暂未开通',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $tuDouPay = new TuDouPay();
        $tuDouPay->setContainer($this->container);
        $tuDouPay->setClient($this->client);
        $tuDouPay->setResponse($response);
        $tuDouPay->setPrivateKey('test');
        $tuDouPay->setOptions($options);
        $tuDouPay->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回qrCode
     */
    public function testScanPayReturnWithoutQrCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '900100004',
            'amount' => '0.01',
            'orderId' => '201805020000012604',
            'paymentVendorId' => '1103',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'ip' => '111.235.135.54',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'merchantCode' => '900100004',
            'interfaceVersion' => '1.0',
            'sign' => '871A539B0B3CEAAEC26AE4D480E1AFF7',
            'status' => 'SUCCESS',
            'message' => 'null',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $tuDouPay = new TuDouPay();
        $tuDouPay->setContainer($this->container);
        $tuDouPay->setClient($this->client);
        $tuDouPay->setResponse($response);
        $tuDouPay->setPrivateKey('test');
        $tuDouPay->setOptions($options);
        $tuDouPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testScanPay()
    {
        $options = [
            'number' => '900100004',
            'amount' => '0.01',
            'orderId' => '201805020000012604',
            'paymentVendorId' => '1103',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'ip' => '111.235.135.54',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'merchantCode' => '900100004',
            'interfaceVersion' => '1.0',
            'sign' => '871A539B0B3CEAAEC26AE4D480E1AFF7',
            'status' => 'SUCCESS',
            'message' => 'null',
            'qrCode' => 'https://qr.95516.com/00010000/62027533387488456892269917213370',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $tuDouPay = new TuDouPay();
        $tuDouPay->setContainer($this->container);
        $tuDouPay->setClient($this->client);
        $tuDouPay->setResponse($response);
        $tuDouPay->setPrivateKey('test');
        $tuDouPay->setOptions($options);
        $data = $tuDouPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://qr.95516.com/00010000/62027533387488456892269917213370', $tuDouPay->getQrcode());
    }

    /**
     * 測試手機支付時沒有返回payUrl
     */
    public function testPhonePayReturnWithoutPayUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '900100004',
            'amount' => '0.01',
            'orderId' => '201805020000012604',
            'paymentVendorId' => '1104',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'ip' => '111.235.135.54',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'merchantCode' => '900100004',
            'interfaceVersion' => '1.0',
            'sign' => '871A539B0B3CEAAEC26AE4D480E1AFF7',
            'status' => 'SUCCESS',
            'message' => 'null',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $tuDouPay = new TuDouPay();
        $tuDouPay->setContainer($this->container);
        $tuDouPay->setClient($this->client);
        $tuDouPay->setResponse($response);
        $tuDouPay->setPrivateKey('test');
        $tuDouPay->setOptions($options);
        $tuDouPay->getVerifyData();
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $options = [
            'number' => '900100004',
            'amount' => '0.01',
            'orderId' => '201805020000012604',
            'paymentVendorId' => '1104',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'ip' => '111.235.135.54',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'merchantCode' => '900100004',
            'interfaceVersion' => '1.0',
            'sign' => '871A539B0B3CEAAEC26AE4D480E1AFF7',
            'status' => 'SUCCESS',
            'message' => 'null',
            'payUrl' => 'https://qpay.qq.com/qr/57c2d674',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $tuDouPay = new TuDouPay();
        $tuDouPay->setContainer($this->container);
        $tuDouPay->setClient($this->client);
        $tuDouPay->setResponse($response);
        $tuDouPay->setPrivateKey('test');
        $tuDouPay->setOptions($options);
        $data = $tuDouPay->getVerifyData();

        $this->assertEquals('https://qpay.qq.com/qr/57c2d674', $data['post_url']);
        $this->assertEmpty($data['params']);
        $this->assertEquals('GET', $tuDouPay->getPayMethod());
    }

    /**
     * 測試網銀支付未返回Action
     */
    public function testBankPayWithoutAction()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '900100004',
            'amount' => '0.01',
            'orderId' => '201805020000012604',
            'paymentVendorId' => '1',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'ip' => '111.235.135.54',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '123';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $tuDouPay = new TuDouPay();
        $tuDouPay->setContainer($this->container);
        $tuDouPay->setClient($this->client);
        $tuDouPay->setResponse($response);
        $tuDouPay->setPrivateKey('test');
        $tuDouPay->setOptions($options);
        $tuDouPay->getVerifyData();
    }

    /**
     * 測試網銀支付未返回input元素
     */
    public function testPayReturnWithoutInput()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '900100004',
            'amount' => '0.01',
            'orderId' => '201805020000012604',
            'paymentVendorId' => '1',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'ip' => '111.235.135.54',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = "<form name='PayForm' action='http://wxianj.com/redirect.php' method='post></form>";

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $tuDouPay = new TuDouPay();
        $tuDouPay->setContainer($this->container);
        $tuDouPay->setClient($this->client);
        $tuDouPay->setResponse($response);
        $tuDouPay->setPrivateKey('test');
        $tuDouPay->setOptions($options);
        $tuDouPay->getVerifyData();
    }

    /**
     * 測試網銀支付
     */
    public function testBankPay()
    {
        $options = [
            'number' => '900100004',
            'amount' => '0.01',
            'orderId' => '201805020000012604',
            'paymentVendorId' => '1',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'ip' => '111.235.135.54',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = "<form name='PayForm' action='http://wxianj.com/redirect.php' method='post>" .
            "<input type='hidden' name='bank_account_type' value='personal' />" .
            "<input type='hidden' name='bank_type' value='1021000' />" .
            "<input type='hidden' name='business_code' value='3010002' />" .
            "<input type='hidden' name='charset' value='UTF-8' />" .
            "<input type='hidden' name='method' value='ysepay.online.directpay.createbyuser' />" .
            "</Form>";

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $tuDouPay = new TuDouPay();
        $tuDouPay->setContainer($this->container);
        $tuDouPay->setClient($this->client);
        $tuDouPay->setResponse($response);
        $tuDouPay->setPrivateKey('test');
        $tuDouPay->setOptions($options);
        $data = $tuDouPay->getVerifyData();

        $this->assertEquals('http://wxianj.com/redirect.php', $data['post_url']);
        $this->assertEquals('personal', $data['params']['bank_account_type']);
        $this->assertEquals('1021000', $data['params']['bank_type']);
        $this->assertEquals('3010002', $data['params']['business_code']);
        $this->assertEquals('UTF-8', $data['params']['charset']);
        $this->assertEquals('ysepay.online.directpay.createbyuser', $data['params']['method']);
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

        $tuDouPay = new TuDouPay();
        $tuDouPay->verifyOrderPayment([]);
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

        $tuDouPay = new TuDouPay();
        $tuDouPay->setPrivateKey('test');
        $tuDouPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未返回簽名
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'merchantCode' => '900100004',
            'interfaceVersion' => '1.0',
            'orderId' => '201805020000012604',
            'sysOrderId' => '176634457694932992',
            'status' => 'SUCCESS',
            'amount' => '1.00000000',
        ];

        $tuDouPay = new TuDouPay();
        $tuDouPay->setPrivateKey('test');
        $tuDouPay->setOptions($options);
        $tuDouPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時簽名驗證錯誤
     */
    public function testReturnSignVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $options = [
            'merchantCode' => '900100004',
            'interfaceVersion' => '1.0',
            'orderId' => '201805020000012604',
            'sysOrderId' => '176634457694932992',
            'status' => 'SUCCESS',
            'amount' => '1.00000000',
            'sign' => 'D57386B397EB36243A094CFFB83544B3',
        ];

        $tuDouPay = new TuDouPay();
        $tuDouPay->setPrivateKey('test');
        $tuDouPay->setOptions($options);
        $tuDouPay->verifyOrderPayment([]);
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
            'merchantCode' => '900100004',
            'interfaceVersion' => '1.0',
            'orderId' => '201805020000012604',
            'sysOrderId' => '176634457694932992',
            'status' => 'FAILED',
            'amount' => '1.00000000',
            'sign' => 'B7E145E92CDDCB01DCAD2E7953FE62A7',
        ];

        $tuDouPay = new TuDouPay();
        $tuDouPay->setPrivateKey('test');
        $tuDouPay->setOptions($options);
        $tuDouPay->verifyOrderPayment([]);
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
            'merchantCode' => '900100004',
            'interfaceVersion' => '1.0',
            'orderId' => '201805020000012604',
            'sysOrderId' => '176634457694932992',
            'status' => 'SUCCESS',
            'amount' => '1.00000000',
            'sign' => '2294C4E80D481CE60B61695D203DCB2E',
        ];

        $entry = ['id' => '9453'];

        $tuDouPay = new TuDouPay();
        $tuDouPay->setPrivateKey('test');
        $tuDouPay->setOptions($options);
        $tuDouPay->verifyOrderPayment($entry);
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
            'merchantCode' => '900100004',
            'interfaceVersion' => '1.0',
            'orderId' => '201805020000012604',
            'sysOrderId' => '176634457694932992',
            'status' => 'SUCCESS',
            'amount' => '1.00000000',
            'sign' => '2294C4E80D481CE60B61695D203DCB2E',
        ];

        $entry = [
            'id' => '201805020000012604',
            'amount' => '100',
        ];

        $tuDouPay = new TuDouPay();
        $tuDouPay->setPrivateKey('test');
        $tuDouPay->setOptions($options);
        $tuDouPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $options = [
            'merchantCode' => '900100004',
            'interfaceVersion' => '1.0',
            'orderId' => '201805020000012604',
            'sysOrderId' => '176634457694932992',
            'status' => 'SUCCESS',
            'amount' => '1.00000000',
            'sign' => '2294C4E80D481CE60B61695D203DCB2E',
        ];

        $entry = [
            'id' => '201805020000012604',
            'amount' => '1',
        ];

        $tuDouPay = new TuDouPay();
        $tuDouPay->setPrivateKey('test');
        $tuDouPay->setOptions($options);
        $tuDouPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $tuDouPay->getMsg());
    }
}
