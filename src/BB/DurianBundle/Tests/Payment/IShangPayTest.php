<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\IShangPay;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class IShangPayTest extends DurianTestCase
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

        $iShangPay = new IShangPay();
        $iShangPay->getVerifyData();
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

        $iShangPay = new IShangPay();
        $iShangPay->setPrivateKey('test');
        $iShangPay->getVerifyData();
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
            'number' => '866251',
            'amount' => '10',
            'orderId' => '201712290000006104',
            'paymentVendorId' => '9453',
            'username' => 'php1test',
            'ip' => '10.123.123.123',
            'notify_url' => 'http://pay.in-action.tw/',
        ];

        $iShangPay = new IShangPay();
        $iShangPay->setPrivateKey('test');
        $iShangPay->setOptions($options);
        $iShangPay->getVerifyData();
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
            'number' => '866251',
            'amount' => '9453',
            'orderId' => '201712290000006104',
            'paymentVendorId' => '1097',
            'username' => 'php1test',
            'ip' => '10.123.123.123',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => '',
        ];

        $iShangPay = new IShangPay();
        $iShangPay->setPrivateKey('test');
        $iShangPay->setOptions($options);
        $iShangPay->getVerifyData();
    }

    /**
     * 測試支付時返回缺少resultcode
     */
    public function testPayReturnWithoutResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '866251',
            'amount' => '9453',
            'orderId' => '201712290000006104',
            'paymentVendorId' => '1097',
            'username' => 'php1test',
            'ip' => '10.123.123.123',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = ['pay_info' => 'test'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $iShangPay = new IShangPay();
        $iShangPay->setPrivateKey('test');
        $iShangPay->setContainer($this->container);
        $iShangPay->setClient($this->client);
        $iShangPay->setResponse($response);
        $iShangPay->setOptions($options);
        $iShangPay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '支付金额不能小于1元',
            180130
        );

        $options = [
            'number' => '866251',
            'amount' => '9453',
            'orderId' => '201712290000006104',
            'paymentVendorId' => '1097',
            'username' => 'php1test',
            'ip' => '10.123.123.123',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'resultcode' => 'fail',
            'pay_info' => '支付金额不能小于1元',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $iShangPay = new IShangPay();
        $iShangPay->setContainer($this->container);
        $iShangPay->setClient($this->client);
        $iShangPay->setResponse($response);
        $iShangPay->setPrivateKey('test');
        $iShangPay->setOptions($options);
        $iShangPay->getVerifyData();
    }

    /**
     * 測試支付返回pay_info格式錯誤
     */
    public function testPayGetEncodeReturnPayInfoWithError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '866251',
            'amount' => '9453',
            'orderId' => '201712290000006104',
            'paymentVendorId' => '1097',
            'username' => 'php1test',
            'ip' => '10.123.123.123',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'resultcode' => 'success',
            'pay_info' => 'statecheck.swiftpass.cn/pay/wappay?token_id=11af5c03&service=pay.weixin.wappayv2',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $iShangPay = new IShangPay();
        $iShangPay->setContainer($this->container);
        $iShangPay->setClient($this->client);
        $iShangPay->setResponse($response);
        $iShangPay->setPrivateKey('test');
        $iShangPay->setOptions($options);
        $iShangPay->getVerifyData();
    }

    /**
     * 測試支付時pay_info沒有query
     */
    public function testPayReturnPayInfoWithoutQuery()
    {
        $options = [
            'number' => '866251',
            'amount' => '9453',
            'orderId' => '201712290000006104',
            'paymentVendorId' => '1097',
            'username' => 'php1test',
            'ip' => '10.123.123.123',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'resultcode' => 'success',
            'pay_info' => 'https://statecheck.swiftpass.cn/pay/wappay',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $iShangPay = new IShangPay();
        $iShangPay->setContainer($this->container);
        $iShangPay->setClient($this->client);
        $iShangPay->setResponse($response);
        $iShangPay->setPrivateKey('test');
        $iShangPay->setOptions($options);
        $data = $iShangPay->getVerifyData();

        $this->assertEquals('https://statecheck.swiftpass.cn/pay/wappay', $data['post_url']);
        $this->assertEmpty($data['params']);
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'number' => '866251',
            'amount' => '9453',
            'orderId' => '201712290000006104',
            'paymentVendorId' => '1097',
            'username' => 'php1test',
            'ip' => '10.123.123.123',
            'notify_url' => 'http://pay.in-action.tw/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'resultcode' => 'success',
            'pay_info' => 'https://statecheck.swiftpass.cn/pay/wappay?token_id=11ae3&service=pay.weixin.wappayv2',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $iShangPay = new IShangPay();
        $iShangPay->setContainer($this->container);
        $iShangPay->setClient($this->client);
        $iShangPay->setResponse($response);
        $iShangPay->setPrivateKey('test');
        $iShangPay->setOptions($options);
        $data = $iShangPay->getVerifyData();

        $this->assertEquals('https://statecheck.swiftpass.cn/pay/wappay', $data['post_url']);
        $this->assertEquals('11ae3', $data['params']['token_id']);
        $this->assertEquals('pay.weixin.wappayv2', $data['params']['service']);
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

        $iShangPay = new IShangPay();
        $iShangPay->setPrivateKey('test');
        $iShangPay->verifyOrderPayment([]);
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
            'orderid' => '201712290000006104',
            'transactionid' => '4200000027201712293618243167',
            'result' => '1',
            'fee' => '100',
            'orderfrom' => 'wap',
            'paymode' => 'wxpay',
            'ordertime' => '2017-12-29 12:04:29',
            'cpparam' => '',
        ];

        $iShangPay = new IShangPay();
        $iShangPay->setPrivateKey('test');
        $iShangPay->setOptions($options);
        $iShangPay->verifyOrderPayment([]);
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
            'orderid' => '201712290000006104',
            'transactionid' => '4200000027201712293618243167',
            'result' => '1',
            'fee' => '100',
            'orderfrom' => 'wap',
            'paymode' => 'wxpay',
            'ordertime' => '2017-12-29 12:04:29',
            'cpparam' => '',
            'sign' => '4117a16d931c27cfc06df976a15b27b4',
        ];

        $iShangPay = new IShangPay();
        $iShangPay->setPrivateKey('test');
        $iShangPay->setOptions($options);
        $iShangPay->verifyOrderPayment([]);
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
            'orderid' => '201712290000006104',
            'transactionid' => '4200000027201712293618243167',
            'result' => '0',
            'fee' => '100',
            'orderfrom' => 'wap',
            'paymode' => 'wxpay',
            'ordertime' => '2017-12-29 12:04:29',
            'cpparam' => '',
            'sign' => '3584783a8f994c3e747815da5c47a84e',
        ];

        $iShangPay = new IShangPay();
        $iShangPay->setPrivateKey('test');
        $iShangPay->setOptions($options);
        $iShangPay->verifyOrderPayment([]);
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
            'orderid' => '201712290000006104',
            'transactionid' => '4200000027201712293618243167',
            'result' => '1',
            'fee' => '100',
            'orderfrom' => 'wap',
            'paymode' => 'wxpay',
            'ordertime' => '2017-12-29 12:04:29',
            'cpparam' => '',
            'sign' => '055cf8997e6cf6f856f7e8b7fe804379',
        ];

        $entry = ['id' => '9453'];

        $iShangPay = new IShangPay();
        $iShangPay->setPrivateKey('test');
        $iShangPay->setOptions($options);
        $iShangPay->verifyOrderPayment($entry);
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
            'orderid' => '201712290000006104',
            'transactionid' => '4200000027201712293618243167',
            'result' => '1',
            'fee' => '100',
            'orderfrom' => 'wap',
            'paymode' => 'wxpay',
            'ordertime' => '2017-12-29 12:04:29',
            'cpparam' => '',
            'sign' => '055cf8997e6cf6f856f7e8b7fe804379',
        ];

        $entry = [
            'id' => '201712290000006104',
            'amount' => '10',
        ];

        $iShangPay = new IShangPay();
        $iShangPay->setPrivateKey('test');
        $iShangPay->setOptions($options);
        $iShangPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $options = [
            'orderid' => '201712290000006104',
            'transactionid' => '4200000027201712293618243167',
            'result' => '1',
            'fee' => '100',
            'orderfrom' => 'wap',
            'paymode' => 'wxpay',
            'ordertime' => '2017-12-29 12:04:29',
            'cpparam' => '',
            'sign' => '055cf8997e6cf6f856f7e8b7fe804379',
        ];

        $entry = [
            'id' => '201712290000006104',
            'amount' => '1',
        ];

        $iShangPay = new IShangPay();
        $iShangPay->setPrivateKey('test');
        $iShangPay->setOptions($options);
        $iShangPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $iShangPay->getMsg());
    }
}
