<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\CkePay;
use Buzz\Message\Response;

class CkePayTest extends DurianTestCase
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

        $ckePay = new CkePay();
        $ckePay->getVerifyData();
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

        $ckePay = new CkePay();
        $ckePay->setPrivateKey('test');
        $ckePay->setOptions($sourceData);
        $ckePay->getVerifyData();
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
            'number' => '795024838160001',
            'paymentVendorId' => '9999',
            'amount' => '1.00',
            'orderId' => '201805150000012891',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
        ];

        $ckePay = new CkePay();
        $ckePay->setPrivateKey('test');
        $ckePay->setOptions($sourceData);
        $ckePay->getVerifyData();
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
            'number' => '795024838160001',
            'paymentVendorId' => '1111',
            'amount' => '1.00',
            'orderId' => '201805150000012891',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => '',
        ];

        $ckePay = new CkePay();
        $ckePay->setPrivateKey('test');
        $ckePay->setOptions($sourceData);
        $ckePay->getVerifyData();
    }

    /**
     * 測試支付時未返回ret_code
     */
    public function testPayNoReturnRetCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'img_url' => 'https://api.yunzhikj.cn/pay/qrcode?code=https://qr.95516.com/00010000/622186655616',
            'ret_msg' => 'https://qr.95516.com/00010000/62218665561638921293902872223251',
            'sign' => '166786BC94CD5C83F2F2C048172ED15D',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/x-www-form-urlencoded;charset=UTF-8');

        $sourceData = [
            'number' => '795024838160001',
            'paymentVendorId' => '1111',
            'amount' => '1.00',
            'orderId' => '201805150000012891',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $ckePay = new CkePay();
        $ckePay->setContainer($this->container);
        $ckePay->setClient($this->client);
        $ckePay->setResponse($response);
        $ckePay->setPrivateKey('test');
        $ckePay->setOptions($sourceData);
        $ckePay->getVerifyData();
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

        $result = [
            'img_url' => 'https://api.yunzhikj.cn/pay/qrcode?code=https://qr.95516.com/00010000/622186655616',
            'ret_code' => '02',
            'ret_msg' => 'https://qr.95516.com/00010000/62218665561638921293902872223251',
            'sign' => '166786BC94CD5C83F2F2C048172ED15D',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/x-www-form-urlencoded;charset=UTF-8');

        $sourceData = [
            'number' => '795024838160001',
            'paymentVendorId' => '1111',
            'amount' => '1.00',
            'orderId' => '201805150000012891',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $ckePay = new CkePay();
        $ckePay->setContainer($this->container);
        $ckePay->setClient($this->client);
        $ckePay->setResponse($response);
        $ckePay->setPrivateKey('test');
        $ckePay->setOptions($sourceData);
        $ckePay->getVerifyData();
    }

    /**
     * 測試支付時未返回img_url
     */
    public function testPayNoReturnImgUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'ret_code' => '00',
            'ret_msg' => 'https://qr.95516.com/00010000/62218665561638921293902872223251',
            'sign' => '166786BC94CD5C83F2F2C048172ED15D',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/x-www-form-urlencoded;charset=UTF-8');

        $sourceData = [
            'number' => '795024838160001',
            'paymentVendorId' => '1111',
            'amount' => '1.00',
            'orderId' => '201805150000012891',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $ckePay = new CkePay();
        $ckePay->setContainer($this->container);
        $ckePay->setClient($this->client);
        $ckePay->setResponse($response);
        $ckePay->setPrivateKey('test');
        $ckePay->setOptions($sourceData);
        $ckePay->getVerifyData();
    }

    /**
     * 測試銀聯掃碼支付
     */
    public function testYLQrcodePay()
    {
        $result = [
            'img_url' => 'https://api.yunzhikj.cn/pay/qrcode?code=https://qr.95516.com/00010000/622186655616',
            'ret_code' => '00',
            'ret_msg' => 'https://qr.95516.com/00010000/62218665561638921293902872223251',
            'sign' => '166786BC94CD5C83F2F2C048172ED15D',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/x-www-form-urlencoded;charset=UTF-8');

        $sourceData = [
            'number' => '795024838160001',
            'paymentVendorId' => '1111',
            'amount' => '1.00',
            'orderId' => '201805150000012891',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $ckePay = new CkePay();
        $ckePay->setContainer($this->container);
        $ckePay->setClient($this->client);
        $ckePay->setResponse($response);
        $ckePay->setPrivateKey('test');
        $ckePay->setOptions($sourceData);
        $ckePay->getVerifyData();
        $data = $ckePay->getVerifyData();

        $this->assertEquals('https://api.yunzhikj.cn/pay/qrcode', $data['post_url']);
        $this->assertEquals('https://qr.95516.com/00010000/622186655616', $data['params']['code']);
        $this->assertEquals('GET', $ckePay->getPayMethod());
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '795024838160001',
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201805150000012891',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $ckePay = new CkePay();
        $ckePay->setPrivateKey('test');
        $ckePay->setOptions($sourceData);
        $verifyData = $ckePay->getVerifyData();

        $this->assertEquals('7950248', $verifyData['store_id']);
        $this->assertEquals('795024838160001', $verifyData['mch_id']);
        $this->assertEquals('37', $verifyData['pay_type']);
        $this->assertEquals('201805150000012891', $verifyData['out_trade_no']);
        $this->assertEquals('1', $verifyData['trans_amt']);
        $this->assertEquals('ICBC', $verifyData['bank_english_code']);
        $this->assertEquals('0', $verifyData['card_type']);
        $this->assertEquals('http://fufutest.000webhostapp.com/pay', $verifyData['notify_url']);
        $this->assertEquals('201805150000012891', $verifyData['body']);
        $this->assertEquals('BDF33ACD785859AF5E9AA07EE922DC02', $verifyData['sign']);
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

        $ckePay = new CkePay();
        $ckePay->verifyOrderPayment([]);
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

        $ckePay = new CkePay();
        $ckePay->setPrivateKey('test');
        $ckePay->setOptions($sourceData);
        $ckePay->verifyOrderPayment([]);
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
            'body' => '201805150000012891',
            'mch_id' => '795024838160001',
            'order_no' => '20180515115915842472258',
            'out_trade_no' => '201805150000012891',
            'pay_type' => '37',
            'ret_code' => '00',
            'store_id' => '7950248',
            'trans_amt' => '1.00',
        ];

        $ckePay = new CkePay();
        $ckePay->setPrivateKey('test');
        $ckePay->setOptions($sourceData);
        $ckePay->verifyOrderPayment([]);
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
            'body' => '201805150000012891',
            'mch_id' => '795024838160001',
            'order_no' => '20180515115915842472258',
            'out_trade_no' => '201805150000012891',
            'pay_type' => '37',
            'ret_code' => '00',
            'sign' => 'F77411D7C8D8C3375DA4514E69136468',
            'store_id' => '7950248',
            'trans_amt' => '1.00',
        ];

        $ckePay = new CkePay();
        $ckePay->setPrivateKey('test');
        $ckePay->setOptions($sourceData);
        $ckePay->verifyOrderPayment([]);
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
            'body' => '201805150000012891',
            'mch_id' => '795024838160001',
            'order_no' => '20180515115915842472258',
            'out_trade_no' => '201805150000012891',
            'pay_type' => '37',
            'ret_code' => '02',
            'sign' => '4B72D33BDDA5FBEF03A588D9EF24D0C1',
            'store_id' => '7950248',
            'trans_amt' => '1.00',
        ];

        $ckePay = new CkePay();
        $ckePay->setPrivateKey('test');
        $ckePay->setOptions($sourceData);
        $ckePay->verifyOrderPayment([]);
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
            'body' => '201805150000012891',
            'mch_id' => '795024838160001',
            'order_no' => '20180515115915842472258',
            'out_trade_no' => '201805150000012891',
            'pay_type' => '37',
            'ret_code' => '00',
            'sign' => 'A9ADEC697F688DF0A33DF5FE6A6CDF3B',
            'store_id' => '7950248',
            'trans_amt' => '1.00',
        ];

        $entry = ['id' => '201704100000002210'];

        $ckePay = new CkePay();
        $ckePay->setPrivateKey('test');
        $ckePay->setOptions($sourceData);
        $ckePay->verifyOrderPayment($entry);
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
            'body' => '201805150000012891',
            'mch_id' => '795024838160001',
            'order_no' => '20180515115915842472258',
            'out_trade_no' => '201805150000012891',
            'pay_type' => '37',
            'ret_code' => '00',
            'sign' => 'A9ADEC697F688DF0A33DF5FE6A6CDF3B',
            'store_id' => '7950248',
            'trans_amt' => '1.00',
        ];

        $entry = [
            'id' => '201805150000012891',
            'amount' => '100',
        ];

        $ckePay = new CkePay();
        $ckePay->setPrivateKey('test');
        $ckePay->setOptions($sourceData);
        $ckePay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'body' => '201805150000012891',
            'mch_id' => '795024838160001',
            'order_no' => '20180515115915842472258',
            'out_trade_no' => '201805150000012891',
            'pay_type' => '37',
            'ret_code' => '00',
            'sign' => 'A9ADEC697F688DF0A33DF5FE6A6CDF3B',
            'store_id' => '7950248',
            'trans_amt' => '1.00',
        ];

        $entry = [
            'id' => '201805150000012891',
            'amount' => '1.00',
        ];

        $ckePay = new CkePay();
        $ckePay->setPrivateKey('test');
        $ckePay->setOptions($sourceData);
        $ckePay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $ckePay->getMsg());
    }
}
