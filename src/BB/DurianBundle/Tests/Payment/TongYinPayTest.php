<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\TongYinPay;
use Buzz\Message\Response;

class TongYinPayTest extends DurianTestCase
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

        $tongYinPay = new TongYinPay();
        $tongYinPay->getVerifyData();
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

        $tongYinPay = new TongYinPay();
        $tongYinPay->setPrivateKey('test');
        $tongYinPay->setOptions($sourceData);
        $tongYinPay->getVerifyData();
    }

    /**
     * 測試支付時業務交互數據參數未指定支付參數
     */
    public function testPayBusinessDataWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = [
            'orderId' => '201806280000014704',
            'orderCreateDate' => '2018-06-28 11:45:55',
        ];

        $tongYinPay = new TongYinPay();
        $tongYinPay->setPrivateKey('test');
        $tongYinPay->setOptions($sourceData);
        $tongYinPay->getVerifyData();
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
            'number' => '472018181821056191',
            'paymentVendorId' => '9999',
            'amount' => '1',
            'orderId' => '201806280000014704',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'orderCreateDate' => '2018-06-28 11:45:55',
        ];

        $tongYinPay = new TongYinPay();
        $tongYinPay->setPrivateKey('test');
        $tongYinPay->setOptions($sourceData);
        $tongYinPay->getVerifyData();
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
            'number' => '472018181821056191',
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201806280000014704',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => '',
            'orderCreateDate' => '2018-06-28 11:45:55',
            'merchant_extra' => ['orgId' => '4520184823160472'],
        ];

        $tongYinPay = new TongYinPay();
        $tongYinPay->setPrivateKey('test');
        $tongYinPay->setOptions($sourceData);
        $tongYinPay->getVerifyData();
    }

    /**
     * 測試支付時未返回respCode
     */
    public function testPayNoReturnRespCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'msg' => '获取成功',
            'requestId' => '201806280000014704',
            'respMsg' => '通讯成功',
            'result' => '{"ishtml":"0","url":"http://47.91.212.244:18888/open-gateway/redirect/go?_t=1527579643514"}',
            'status' => '3',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '472018181821056191',
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201806280000014704',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'orderCreateDate' => '2018-06-28 11:45:55',
            'merchant_extra' => ['orgId' => '4520184823160472'],
        ];

        $tongYinPay = new TongYinPay();
        $tongYinPay->setContainer($this->container);
        $tongYinPay->setClient($this->client);
        $tongYinPay->setResponse($response);
        $tongYinPay->setPrivateKey('test');
        $tongYinPay->setOptions($sourceData);
        $tongYinPay->getVerifyData();
    }

    /**
     * 測試支付時請求失敗
     */
    public function testPayRequestFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '请求时间戳非法',
            180130
        );

        $result = [
            'requestId' => '201806280000014704',
            'respCode' => 'X6',
            'respMsg' => '请求时间戳非法',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '472018181821056191',
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201806280000014704',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'orderCreateDate' => '2018-06-28 11:45:55',
            'merchant_extra' => ['orgId' => '4520184823160472'],
        ];

        $tongYinPay = new TongYinPay();
        $tongYinPay->setContainer($this->container);
        $tongYinPay->setClient($this->client);
        $tongYinPay->setResponse($response);
        $tongYinPay->setPrivateKey('test');
        $tongYinPay->setOptions($sourceData);
        $tongYinPay->getVerifyData();
    }

    /**
     * 測試支付時未返回key
     */
    public function testPayNoReturnKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'msg' => '获取成功',
            'requestId' => '201806280000014704',
            'respCode' => '00',
            'respMsg' => '通讯成功',
            'result' => '{"ishtml":"0","url":"http://47.91.212.244:18888/open-gateway/redirect/go?_t=1527579643514"}',
            'status' => '3',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '472018181821056191',
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201806280000014704',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'orderCreateDate' => '2018-06-28 11:45:55',
            'merchant_extra' => ['orgId' => '4520184823160472'],
        ];

        $tongYinPay = new TongYinPay();
        $tongYinPay->setContainer($this->container);
        $tongYinPay->setClient($this->client);
        $tongYinPay->setResponse($response);
        $tongYinPay->setPrivateKey('test');
        $tongYinPay->setOptions($sourceData);
        $tongYinPay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '调用支付失败，请联系管理员',
            180130
        );

        $result = [
            'key' => '400003',
            'msg' => '调用支付失败，请联系管理员',
            'requestId' => '201806280000014704',
            'respCode' => '00',
            'respMsg' => '通讯成功',
            'status' => '2',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '472018181821056191',
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201806280000014704',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'orderCreateDate' => '2018-06-28 11:45:55',
            'merchant_extra' => ['orgId' => '4520184823160472'],
        ];

        $tongYinPay = new TongYinPay();
        $tongYinPay->setContainer($this->container);
        $tongYinPay->setClient($this->client);
        $tongYinPay->setResponse($response);
        $tongYinPay->setPrivateKey('test');
        $tongYinPay->setOptions($sourceData);
        $tongYinPay->getVerifyData();
    }

    /**
     * 測試支付時未返回result
     */
    public function testPayNoReturnResult()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'key' => '05',
            'msg' => '获取成功',
            'requestId' => '201806280000014704',
            'respCode' => '00',
            'respMsg' => '通讯成功',
            'status' => '3',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '472018181821056191',
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201806280000014704',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'orderCreateDate' => '2018-06-28 11:45:55',
            'merchant_extra' => ['orgId' => '4520184823160472'],
        ];

        $tongYinPay = new TongYinPay();
        $tongYinPay->setContainer($this->container);
        $tongYinPay->setClient($this->client);
        $tongYinPay->setResponse($response);
        $tongYinPay->setPrivateKey('test');
        $tongYinPay->setOptions($sourceData);
        $tongYinPay->getVerifyData();
    }

    /**
     * 測試支付時返回result没有url
     */
    public function testPayReturnResultWithoutUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'key' => '05',
            'msg' => '获取成功',
            'requestId' => '201806280000014704',
            'respCode' => '00',
            'respMsg' => '通讯成功',
            'result' => '{"ishtml":"0","url":""}',
            'status' => '3',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '472018181821056191',
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201806280000014704',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'orderCreateDate' => '2018-06-28 11:45:55',
            'merchant_extra' => ['orgId' => '4520184823160472'],
        ];

        $tongYinPay = new TongYinPay();
        $tongYinPay->setContainer($this->container);
        $tongYinPay->setClient($this->client);
        $tongYinPay->setResponse($response);
        $tongYinPay->setPrivateKey('test');
        $tongYinPay->setOptions($sourceData);
        $tongYinPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $result = [
            'key' => '05',
            'msg' => '获取成功',
            'requestId' => '201806280000014704',
            'respCode' => '00',
            'respMsg' => '通讯成功',
            'result' => '{"ishtml":"0","url":"https://qr.95516.com/00010000/62812229561697038782099459014961"}',
            'status' => '3',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '472018181821056191',
            'paymentVendorId' => '1092',
            'amount' => '1',
            'orderId' => '201806280000014704',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'orderCreateDate' => '2018-06-28 11:45:55',
            'merchant_extra' => ['orgId' => '4520184823160472'],
        ];

        $tongYinPay = new TongYinPay();
        $tongYinPay->setContainer($this->container);
        $tongYinPay->setClient($this->client);
        $tongYinPay->setResponse($response);
        $tongYinPay->setPrivateKey('test');
        $tongYinPay->setOptions($sourceData);
        $data = $tongYinPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://qr.95516.com/00010000/62812229561697038782099459014961', $tongYinPay->getQrcode());
    }

    /**
     * 測試網銀支付
     */
    public function testBankPay()
    {
        $result = [
            'key' => '05',
            'msg' => '获取成功',
            'requestId' => '201806280000014704',
            'respCode' => '00',
            'respMsg' => '通讯成功',
            'result' => '{"ishtml":"0","url":"http://47.91.212.244:18888/open-gateway/redirect/go?_t=1527579643514"}',
            'status' => '3',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '472018181821056191',
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201806280000014704',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'orderCreateDate' => '2018-06-28 11:45:55',
            'merchant_extra' => ['orgId' => '4520184823160472'],
        ];

        $tongYinPay = new TongYinPay();
        $tongYinPay->setContainer($this->container);
        $tongYinPay->setClient($this->client);
        $tongYinPay->setResponse($response);
        $tongYinPay->setPrivateKey('test');
        $tongYinPay->setOptions($sourceData);
        $data = $tongYinPay->getVerifyData();

        $this->assertEquals('http://47.91.212.244:18888/open-gateway/redirect/go', $data['post_url']);
        $this->assertEquals('1527579643514', $data['params']['_t']);
        $this->assertEquals('GET', $tongYinPay->getPayMethod());
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

        $tongYinPay = new TongYinPay();
        $tongYinPay->verifyOrderPayment([]);
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

        $tongYinPay = new TongYinPay();
        $tongYinPay->setPrivateKey('test');
        $tongYinPay->setOptions($sourceData);
        $tongYinPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳sign_data
     */
    public function testReturnWithoutSignData()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'amount' => '100',
            'goods_info' => '201806280000014704',
            'merno' => '472018181821056191',
            'order_id' => '201806280000014704',
            'orgid' => '4520184823160472',
            'plat_order_id' => '2018052915404382538385',
            'timestamp' => '20180529154207',
            'trade_date' => '2018-05-29 15:42:07',
            'trade_status' => '0',
        ];

        $tongYinPay = new TongYinPay();
        $tongYinPay->setPrivateKey('test');
        $tongYinPay->setOptions($sourceData);
        $tongYinPay->verifyOrderPayment([]);
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
            'amount' => '100',
            'goods_info' => '201806280000014704',
            'merno' => '472018181821056191',
            'order_id' => '201806280000014704',
            'orgid' => '4520184823160472',
            'plat_order_id' => '2018052915404382538385',
            'sign_data' => 'ac230c421937d7d00a95253420a04309',
            'timestamp' => '20180529154207',
            'trade_date' => '2018-05-29 15:42:07',
            'trade_status' => '0',
        ];

        $tongYinPay = new TongYinPay();
        $tongYinPay->setPrivateKey('test');
        $tongYinPay->setOptions($sourceData);
        $tongYinPay->verifyOrderPayment([]);
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
            'amount' => '100',
            'goods_info' => '201806280000014704',
            'merno' => '472018181821056191',
            'order_id' => '201806280000014704',
            'orgid' => '4520184823160472',
            'plat_order_id' => '2018052915404382538385',
            'sign_data' => 'df711759c8039a6671555b26a047c31e',
            'timestamp' => '20180529154207',
            'trade_date' => '2018-05-29 15:42:07',
            'trade_status' => '1',
        ];

        $tongYinPay = new TongYinPay();
        $tongYinPay->setPrivateKey('test');
        $tongYinPay->setOptions($sourceData);
        $tongYinPay->verifyOrderPayment([]);
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
            'amount' => '100',
            'goods_info' => '201806280000014704',
            'merno' => '472018181821056191',
            'order_id' => '201806280000014704',
            'orgid' => '4520184823160472',
            'plat_order_id' => '2018052915404382538385',
            'sign_data' => 'de8971b7af1b548aa1eabf693a58b704',
            'timestamp' => '20180529154207',
            'trade_date' => '2018-05-29 15:42:07',
            'trade_status' => '0',
        ];

        $entry = ['id' => '201704100000002210'];

        $tongYinPay = new TongYinPay();
        $tongYinPay->setPrivateKey('test');
        $tongYinPay->setOptions($sourceData);
        $tongYinPay->verifyOrderPayment($entry);
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
            'amount' => '100',
            'goods_info' => '201806280000014704',
            'merno' => '472018181821056191',
            'order_id' => '201806280000014704',
            'orgid' => '4520184823160472',
            'plat_order_id' => '2018052915404382538385',
            'sign_data' => 'de8971b7af1b548aa1eabf693a58b704',
            'timestamp' => '20180529154207',
            'trade_date' => '2018-05-29 15:42:07',
            'trade_status' => '0',
        ];

        $entry = [
            'id' => '201806280000014704',
            'amount' => '100000',
        ];

        $tongYinPay = new TongYinPay();
        $tongYinPay->setPrivateKey('test');
        $tongYinPay->setOptions($sourceData);
        $tongYinPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'amount' => '100',
            'goods_info' => '201806280000014704',
            'merno' => '472018181821056191',
            'order_id' => '201806280000014704',
            'orgid' => '4520184823160472',
            'plat_order_id' => '2018052915404382538385',
            'sign_data' => 'de8971b7af1b548aa1eabf693a58b704',
            'timestamp' => '20180529154207',
            'trade_date' => '2018-05-29 15:42:07',
            'trade_status' => '0',
        ];

        $entry = [
            'id' => '201806280000014704',
            'amount' => '1',
        ];

        $tongYinPay = new TongYinPay();
        $tongYinPay->setPrivateKey('test');
        $tongYinPay->setOptions($sourceData);
        $tongYinPay->verifyOrderPayment($entry);

        $this->assertEquals('{"responseCode":"0000"}', $tongYinPay->getMsg());
    }
}
