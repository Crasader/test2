<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\PengChengPay;
use Buzz\Message\Response;

class PengChengPayTest extends DurianTestCase
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

        $pengChengPay = new PengChengPay();
        $pengChengPay->getVerifyData();
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

        $sourceData = ['number' => ''];

        $pengChengPay = new PengChengPay();
        $pengChengPay->setPrivateKey('test');
        $pengChengPay->setOptions($sourceData);
        $pengChengPay->getVerifyData();
    }

    /**
     * 測試支付時代入支付平台不支援的銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '10408',
            'paymentVendorId' => '9999',
            'amount' => '1',
            'orderId' => '201806130000014170',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
        ];

        $pengChengPay = new PengChengPay();
        $pengChengPay->setPrivateKey('test');
        $pengChengPay->setOptions($sourceData);
        $pengChengPay->getVerifyData();
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

        $sourceData = [
            'number' => '10408',
            'paymentVendorId' => '1103',
            'amount' => '1',
            'orderId' => '201806130000014170',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => '',
        ];

        $pengChengPay = new PengChengPay();
        $pengChengPay->setPrivateKey('test');
        $pengChengPay->setOptions($sourceData);
        $pengChengPay->getVerifyData();
    }

    /**
     * 測試支付時未返回resultCode
     */
    public function testPayNoReturnResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'resultMsg' => '成功',
            'codeImageUrl' => 'https://qpay.qq.com/qr/571ace83',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '10408',
            'paymentVendorId' => '1103',
            'amount' => '1',
            'orderId' => '201806130000014170',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $pengChengPay = new PengChengPay();
        $pengChengPay->setContainer($this->container);
        $pengChengPay->setClient($this->client);
        $pengChengPay->setResponse($response);
        $pengChengPay->setPrivateKey('test');
        $pengChengPay->setOptions($sourceData);
        $pengChengPay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '没有可用通道',
            180130
        );

        $result = [
            'resultMsg' => '没有可用通道',
            'resultCode' => '0020',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '10408',
            'paymentVendorId' => '1103',
            'amount' => '1',
            'orderId' => '201806130000014170',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $pengChengPay = new PengChengPay();
        $pengChengPay->setContainer($this->container);
        $pengChengPay->setClient($this->client);
        $pengChengPay->setResponse($response);
        $pengChengPay->setPrivateKey('test');
        $pengChengPay->setOptions($sourceData);
        $pengChengPay->getVerifyData();
    }

    /**
     * 測試支付時返回沒有resultMsg
     */
    public function testPayReturnWithoutResultMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $result = [
            'resultCode' => '0020',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '10408',
            'paymentVendorId' => '1103',
            'amount' => '1',
            'orderId' => '201806130000014170',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $pengChengPay = new PengChengPay();
        $pengChengPay->setContainer($this->container);
        $pengChengPay->setClient($this->client);
        $pengChengPay->setResponse($response);
        $pengChengPay->setPrivateKey('test');
        $pengChengPay->setOptions($sourceData);
        $pengChengPay->getVerifyData();
    }

    /**
     * 測試網銀支付時未返回codeImageUrl
     */
    public function testBankPayNoReturnCodeImageUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'resultMsg' => '成功',
            'resultCode' => '0000',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '10408',
            'paymentVendorId' => '1103',
            'amount' => '1',
            'orderId' => '201806130000014170',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $pengChengPay = new PengChengPay();
        $pengChengPay->setContainer($this->container);
        $pengChengPay->setClient($this->client);
        $pengChengPay->setResponse($response);
        $pengChengPay->setPrivateKey('test');
        $pengChengPay->setOptions($sourceData);
        $pengChengPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $result = [
            'resultMsg' => '成功',
            'codeImageUrl' => 'https://qpay.qq.com/qr/571ace83',
            'resultCode' => '0000',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '10408',
            'paymentVendorId' => '1103',
            'amount' => '1',
            'orderId' => '201806130000014170',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $pengChengPay = new PengChengPay();
        $pengChengPay->setContainer($this->container);
        $pengChengPay->setClient($this->client);
        $pengChengPay->setResponse($response);
        $pengChengPay->setPrivateKey('test');
        $pengChengPay->setOptions($sourceData);
        $data = $pengChengPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://qpay.qq.com/qr/571ace83', $pengChengPay->getQrcode());
    }

    /**
     * 測試銀聯在線支付
     */
    public function testQuickPay()
    {
        $sourceData = [
            'number' => '10408',
            'paymentVendorId' => '278',
            'amount' => '1',
            'orderId' => '201806130000014170',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $pengChengPay = new PengChengPay();
        $pengChengPay->setPrivateKey('test');
        $pengChengPay->setOptions($sourceData);
        $data = $pengChengPay->getVerifyData();

        $result = [
            'mchNo' => '10408',
            'orderID' => '201806130000014170',
            'money' => '1.00',
            'body' => '201806130000014170',
            'payType' => 'qpay',
            'notifyUrl' => 'aHR0cDovL2Z1ZnV0ZXN0LjAwMHdlYmhvc3RhcHAuY29tL3BheQ==',
            'callbackurl' => 'aHR0cDovL2Z1ZnV0ZXN0LjAwMHdlYmhvc3RhcHAuY29tL3BheQ==',
            'sign' => '289135b8659a024489eccb05d4bfde09',
        ];

        $this->assertEquals(json_encode($result), $data['requestBody']);
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

        $pengChengPay = new PengChengPay();
        $pengChengPay->verifyOrderPayment([]);
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

        $sourceData = [];

        $pengChengPay = new PengChengPay();
        $pengChengPay->setPrivateKey('test');
        $pengChengPay->setOptions($sourceData);
        $pengChengPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳sign(加密簽名)
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
           'orderID' => '201806130000014170',
           'money' => '1.00',
           'transID' => '010000174201806142108348300575',
           'status' => 'TRADE_FINISHED',
           'notifyUrl' => 'http://fufutest.000webhostapp.com/pay/pay_response.php',
           'count' => '8000',
        ];

        $pengChengPay = new PengChengPay();
        $pengChengPay->setPrivateKey('test');
        $pengChengPay->setOptions($sourceData);
        $pengChengPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時加密簽名驗證錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $sourceData = [
           'orderID' => '201806130000014170',
           'money' => '1.00',
           'transID' => '010000174201806142108348300575',
           'status' => 'TRADE_FINISHED',
           'notifyUrl' => 'http://fufutest.000webhostapp.com/pay/pay_response.php',
           'count' => '8000',
           'sign' => '4ec2367ab83cc79cf0f1dddffe27704d',
        ];

        $pengChengPay = new PengChengPay();
        $pengChengPay->setPrivateKey('test');
        $pengChengPay->setOptions($sourceData);
        $pengChengPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為支付失敗
     */
    public function testReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $sourceData = [
           'orderID' => '201806130000014170',
           'money' => '1.00',
           'transID' => '010000174201806142108348300575',
           'status' => 'TRADE_FAILURE',
           'notifyUrl' => 'http://fufutest.000webhostapp.com/pay/pay_response.php',
           'count' => '8000',
           'sign' => '5b1fe70157564575327c69ebb56e4575',
        ];

        $pengChengPay = new PengChengPay();
        $pengChengPay->setPrivateKey('test');
        $pengChengPay->setOptions($sourceData);
        $pengChengPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單編號錯誤
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
           'orderID' => '201806130000014170',
           'money' => '1.00',
           'transID' => '010000174201806142108348300575',
           'status' => 'TRADE_FINISHED',
           'notifyUrl' => 'http://fufutest.000webhostapp.com/pay/pay_response.php',
           'count' => '8000',
           'sign' => 'c00b710180b904a5cd2bfd3115f42ba9',
        ];

        $entry = ['id' => '201704100000002210'];

        $pengChengPay = new PengChengPay();
        $pengChengPay->setPrivateKey('test');
        $pengChengPay->setOptions($sourceData);
        $pengChengPay->verifyOrderPayment($entry);
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

        $sourceData = [
           'orderID' => '201806130000014170',
           'money' => '1.00',
           'transID' => '010000174201806142108348300575',
           'status' => 'TRADE_FINISHED',
           'notifyUrl' => 'http://fufutest.000webhostapp.com/pay/pay_response.php',
           'count' => '8000',
           'sign' => 'c00b710180b904a5cd2bfd3115f42ba9',
        ];

        $entry = [
            'id' => '201806130000014170',
            'amount' => '100',
        ];

        $pengChengPay = new PengChengPay();
        $pengChengPay->setPrivateKey('test');
        $pengChengPay->setOptions($sourceData);
        $pengChengPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
           'orderID' => '201806130000014170',
           'money' => '1.00',
           'transID' => '010000174201806142108348300575',
           'status' => 'TRADE_FINISHED',
           'notifyUrl' => 'http://fufutest.000webhostapp.com/pay/pay_response.php',
           'count' => '8000',
           'sign' => 'c00b710180b904a5cd2bfd3115f42ba9',
        ];

        $entry = [
            'id' => '201806130000014170',
            'amount' => '1',
        ];

        $pengChengPay = new PengChengPay();
        $pengChengPay->setPrivateKey('test');
        $pengChengPay->setOptions($sourceData);
        $pengChengPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $pengChengPay->getMsg());
    }
}
