<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\LiYingPay;
use Buzz\Message\Response;

class LiYingPayTest extends DurianTestCase
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

        $liYingPay = new LiYingPay();
        $liYingPay->getVerifyData();
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

        $liYingPay = new LiYingPay();
        $liYingPay->setPrivateKey('test');
        $liYingPay->getVerifyData();
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

        $sourceData = [
            'number' => '9527',
            'orderId' => '20171130114612',
            'amount' => '100.00',
            'notify_url' => 'http://seafood.com/',
            'paymentVendorId' => '99',
            'orderCreateDate' => '2017-11-30 11:44:37',
        ];

        $liYingPay = new LiYingPay();
        $liYingPay->setPrivateKey('test');
        $liYingPay->setOptions($sourceData);
        $liYingPay->getVerifyData();
    }

    /**
     * 測試QQH5時沒有帶入verify_url的情況
     */
    public function testQQHFivePayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => '9527',
            'orderId' => '20171130114612',
            'amount' => '100.00',
            'notify_url' => 'http://seafood.com/',
            'paymentVendorId' => '1104',
            'orderCreateDate' => '2017-11-30 11:44:37',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $liYingPay = new LiYingPay();
        $liYingPay->setPrivateKey('test');
        $liYingPay->setOptions($sourceData);
        $liYingPay->getVerifyData();
    }

    /**
     * 測試QQH5加密未返回status
     */
    public function testQQHFiveGetEncodeNoReturnStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [];
        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '20171130114612',
            'amount' => '100.00',
            'notify_url' => 'http://seafood.com/',
            'paymentVendorId' => '1104',
            'orderCreateDate' => '2017-11-30 11:44:37',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.seafood.help.you',
        ];

        $liYingPay = new LiYingPay();
        $liYingPay->setContainer($this->container);
        $liYingPay->setClient($this->client);
        $liYingPay->setResponse($response);
        $liYingPay->setPrivateKey('test');
        $liYingPay->setOptions($sourceData);
        $liYingPay->getVerifyData();
    }

    /**
     * 測試QQH5加密返回status不等於SUCCESS
     */
    public function testQQHFiveGetEncodeReturnStatusNotEqualSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '商戶號不支持',
            180130
        );

        $result = [
            'status' => 'FAIL',
            'message' => '商戶號不支持',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '20171130114612',
            'amount' => '100.00',
            'notify_url' => 'http://seafood.com/',
            'paymentVendorId' => '1104',
            'orderCreateDate' => '2017-11-30 11:44:37',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.seafood.help.you',
        ];

        $liYingPay = new LiYingPay();
        $liYingPay->setContainer($this->container);
        $liYingPay->setClient($this->client);
        $liYingPay->setResponse($response);
        $liYingPay->setPrivateKey('test');
        $liYingPay->setOptions($sourceData);
        $liYingPay->getVerifyData();
    }

    /**
     * 測試QQH5加密未返回result_code
     */
    public function testQQHFiveGetEncodeNoReturnResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'status' => 'SUCCESS',
            'message' => '成功',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '20171130114612',
            'amount' => '100.00',
            'notify_url' => 'http://seafood.com/',
            'paymentVendorId' => '1104',
            'orderCreateDate' => '2017-11-30 11:44:37',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.seafood.help.you',
        ];

        $liYingPay = new LiYingPay();
        $liYingPay->setContainer($this->container);
        $liYingPay->setClient($this->client);
        $liYingPay->setResponse($response);
        $liYingPay->setPrivateKey('test');
        $liYingPay->setOptions($sourceData);
        $liYingPay->getVerifyData();
    }

    /**
     * 測試QQH5加密返回result_code不等於SUCCESS
     */
    public function testQQHFiveGetEncodeReturnResultCodeNotEqualSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '未知錯誤',
            180130
        );

        $result = [
            'status' => 'SUCCESS',
            'message' => '成功',
            'result_code' => 'Fail',
            'err_msg' => '未知錯誤',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '20171130114612',
            'amount' => '100.00',
            'notify_url' => 'http://seafood.com/',
            'paymentVendorId' => '1104',
            'orderCreateDate' => '2017-11-30 11:44:37',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.seafood.help.you',
        ];

        $liYingPay = new LiYingPay();
        $liYingPay->setContainer($this->container);
        $liYingPay->setClient($this->client);
        $liYingPay->setResponse($response);
        $liYingPay->setPrivateKey('test');
        $liYingPay->setOptions($sourceData);
        $liYingPay->getVerifyData();
    }

    /**
     * 測試QQH5加密未返回code_url
     */
    public function testQQHFiveGetEncodeNoReturnCodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'status' => 'SUCCESS',
            'message' => '成功',
            'result_code' => 'SUCCESS',
            'err_msg' => '成功',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '20171130114612',
            'amount' => '100.00',
            'notify_url' => 'http://seafood.com/',
            'paymentVendorId' => '1104',
            'orderCreateDate' => '2017-11-30 11:44:37',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.seafood.help.you',
        ];

        $liYingPay = new LiYingPay();
        $liYingPay->setContainer($this->container);
        $liYingPay->setClient($this->client);
        $liYingPay->setResponse($response);
        $liYingPay->setPrivateKey('test');
        $liYingPay->setOptions($sourceData);
        $liYingPay->getVerifyData();
    }

    /**
     * 測試QQH5支付
     */
    public function testQQHFivePay()
    {
        $result = [
            'status' => 'SUCCESS',
            'message' => '成功',
            'result_code' => 'SUCCESS',
            'err_msg' => '成功',
            'code_url' => 'http://seafood.pay.help.you',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '20171130114612',
            'amount' => '100.00',
            'notify_url' => 'http://seafood.com/',
            'paymentVendorId' => '1104',
            'orderCreateDate' => '2017-11-30 11:44:37',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.seafood.help.you',
        ];

        $liYingPay = new LiYingPay();
        $liYingPay->setContainer($this->container);
        $liYingPay->setClient($this->client);
        $liYingPay->setResponse($response);
        $liYingPay->setPrivateKey('test');
        $liYingPay->setOptions($sourceData);
        $requestData = $liYingPay->getVerifyData();

        $this->assertEquals('http://seafood.pay.help.you', $requestData['act_url']);
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '9527',
            'orderId' => '20171130114612',
            'amount' => '100.00',
            'notify_url' => 'http://seafood.com/',
            'paymentVendorId' => '1090',
            'orderCreateDate' => '2017-11-30 11:44:37',
        ];

        $liYingPay = new LiYingPay();
        $liYingPay->setPrivateKey('test');
        $liYingPay->setOptions($sourceData);
        $requestData = $liYingPay->getVerifyData();

        $this->assertEquals('9527', $requestData['mch_id']);
        $this->assertEquals('01', $requestData['trade_type']);
        $this->assertEquals('20171130114612', $requestData['out_trade_no']);
        $this->assertEquals('', $requestData['body']);
        $this->assertEquals('', $requestData['attach']);
        $this->assertEquals('10000', $requestData['total_fee']);
        $this->assertEquals('', $requestData['bank_id']);
        $this->assertEquals($sourceData['notify_url'], $requestData['notify_url']);
        $this->assertEquals($sourceData['notify_url'], $requestData['return_url']);
        $this->assertEquals('20171130114437', $requestData['time_start']);
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

        $liYingPay = new LiYingPay();
        $liYingPay->verifyOrderPayment([]);
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

        $liYingPay = new LiYingPay();
        $liYingPay->setPrivateKey('test');
        $liYingPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少加密簽名
     */
    public function testReturnWithoutSignMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'mch_id' => '9527',
            'out_trade_no' => '20171130114656',
            'trade_no' => '17113057985648545748',
            'trade_type' => '10',
            'trade_state' => 'SUCCESS',
            'total_fee' => '1000',
            'time_end' => '20171130118656',
            'nonce_str' => '450cc2afa31de1d76e57573c061e4210',
        ];

        $liYingPay = new LiYingPay();
        $liYingPay->setPrivateKey('test');
        $liYingPay->setOptions($sourceData);
        $liYingPay->verifyOrderPayment([]);
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
            'mch_id' => '9527',
            'out_trade_no' => '20171130114656',
            'trade_no' => '17113057985648545748',
            'trade_type' => '10',
            'trade_state' => 'SUCCESS',
            'total_fee' => '1000',
            'time_end' => '20171130118656',
            'nonce_str' => '450cc2afa31de1d76e57573c061e4210',
            'sign' => '55667788',
        ];

        $liYingPay = new LiYingPay();
        $liYingPay->setPrivateKey('test');
        $liYingPay->setOptions($sourceData);
        $liYingPay->verifyOrderPayment([]);
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

        $sourceData = [
            'mch_id' => '9527',
            'out_trade_no' => '20171130114656',
            'trade_no' => '17113057985648545748',
            'trade_type' => '10',
            'trade_state' => 'NOTPAY',
            'total_fee' => '1000',
            'time_end' => '20171130118656',
            'nonce_str' => '450cc2afa31de1d76e57573c061e4210',
            'sign' => 'F7A36962209AC0B0FB0E12587F561E64',
        ];

        $liYingPay = new LiYingPay();
        $liYingPay->setPrivateKey('test');
        $liYingPay->setOptions($sourceData);
        $liYingPay->verifyOrderPayment([]);
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
            'mch_id' => '9527',
            'out_trade_no' => '20171130114656',
            'trade_no' => '17113057985648545748',
            'trade_type' => '10',
            'trade_state' => 'PAYERROR',
            'total_fee' => '1000',
            'time_end' => '20171130118656',
            'nonce_str' => '450cc2afa31de1d76e57573c061e4210',
            'sign' => '5B65D1D2C26E511B0493E70DB760FAEB',
        ];

        $liYingPay = new LiYingPay();
        $liYingPay->setPrivateKey('test');
        $liYingPay->setOptions($sourceData);
        $liYingPay->verifyOrderPayment([]);
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

        $sourceData = [
            'mch_id' => '9527',
            'out_trade_no' => '20171130114656',
            'trade_no' => '17113057985648545748',
            'trade_type' => '10',
            'trade_state' => 'SUCCESS',
            'total_fee' => '1000',
            'time_end' => '20171130118656',
            'nonce_str' => '450cc2afa31de1d76e57573c061e4210',
            'sign' => 'E186888935F71DF1CD10E5BFE7CAEE21',
        ];

        $entry = ['id' => '201705220000000321'];

        $liYingPay = new LiYingPay();
        $liYingPay->setPrivateKey('test');
        $liYingPay->setOptions($sourceData);
        $liYingPay->verifyOrderPayment($entry);
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
            'mch_id' => '9527',
            'out_trade_no' => '20171130114656',
            'trade_no' => '17113057985648545748',
            'trade_type' => '10',
            'trade_state' => 'SUCCESS',
            'total_fee' => '1000',
            'time_end' => '20171130118656',
            'nonce_str' => '450cc2afa31de1d76e57573c061e4210',
            'sign' => 'E186888935F71DF1CD10E5BFE7CAEE21',
        ];

        $entry = [
            'id' => '20171130114656',
            'amount' => '11.00',
        ];

        $liYingPay = new LiYingPay();
        $liYingPay->setPrivateKey('test');
        $liYingPay->setOptions($sourceData);
        $liYingPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $sourceData = [
            'mch_id' => '9527',
            'out_trade_no' => '20171130114656',
            'trade_no' => '17113057985648545748',
            'trade_type' => '10',
            'trade_state' => 'SUCCESS',
            'total_fee' => '1000',
            'time_end' => '20171130118656',
            'nonce_str' => '450cc2afa31de1d76e57573c061e4210',
            'sign' => 'E186888935F71DF1CD10E5BFE7CAEE21',
        ];

        $entry = [
            'id' => '20171130114656',
            'amount' => '10.00',
        ];

        $liYingPay = new LiYingPay();
        $liYingPay->setPrivateKey('test');
        $liYingPay->setOptions($sourceData);
        $liYingPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $liYingPay->getMsg());
    }
}
