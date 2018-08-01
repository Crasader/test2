<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\DinWeiXinH5;
use Buzz\Message\Response;

class DinWeiXinH5Test extends DurianTestCase
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
            ->will($this->returnValue(null));

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

        $dinWeiXinH5 = new DinWeiXinH5();
        $dinWeiXinH5->getVerifyData();
    }

    /**
     * 測試支付時沒指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $dinWeiXinH5 = new DinWeiXinH5();
        $dinWeiXinH5->setPrivateKey('test');
        $dinWeiXinH5->getVerifyData();
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
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1097',
            'number' => 'spade',
            'orderId' => '201712220000008212',
            'amount' => '1.01',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
            'verify_url' => '',
        ];

        $dinWeiXinH5 = new DinWeiXinH5();
        $dinWeiXinH5->setPrivateKey('test');
        $dinWeiXinH5->setOptions($options);
        $dinWeiXinH5->getVerifyData();
    }

    /**
     * 測試支付時沒有返回result
     */
    public function testPayReturnWithoutResult()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1097',
            'number' => 'spade',
            'orderId' => '201712220000008212',
            'amount' => '1.01',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"message":"AmountError"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $dinWeiXinH5 = new DinWeiXinH5();
        $dinWeiXinH5->setContainer($this->container);
        $dinWeiXinH5->setClient($this->client);
        $dinWeiXinH5->setResponse($response);
        $dinWeiXinH5->setPrivateKey('test');
        $dinWeiXinH5->setOptions($options);
        $dinWeiXinH5->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'AmountError',
            180130
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1097',
            'number' => 'spade',
            'orderId' => '201712220000008212',
            'amount' => '1.01',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"result":"fail","message":"AmountError"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $dinWeiXinH5 = new DinWeiXinH5();
        $dinWeiXinH5->setContainer($this->container);
        $dinWeiXinH5->setClient($this->client);
        $dinWeiXinH5->setResponse($response);
        $dinWeiXinH5->setPrivateKey('test');
        $dinWeiXinH5->setOptions($options);
        $dinWeiXinH5->getVerifyData();
    }

    /**
     * 測試支付時沒有返回url
     */
    public function testPayReturnWithoutUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1097',
            'number' => 'spade',
            'orderId' => '201712220000008212',
            'amount' => '1.01',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"merchant_id":"spade","message":"success","order_no":"201712220000008212",' .
            '"out_trade_no":"f58890136239fc6f011f2ff594512d31","result":"success","total_fee":100,' .
            '"sign":"D40EB0676FE5E92A18F752251E011C3D"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $dinWeiXinH5 = new DinWeiXinH5();
        $dinWeiXinH5->setContainer($this->container);
        $dinWeiXinH5->setClient($this->client);
        $dinWeiXinH5->setResponse($response);
        $dinWeiXinH5->setPrivateKey('test');
        $dinWeiXinH5->setOptions($options);
        $dinWeiXinH5->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1097',
            'number' => 'spade',
            'orderId' => '201712220000008212',
            'amount' => '1.01',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"merchant_id":"spade","message":"success","order_no":"201712220000008212",' .
            '"out_trade_no":"f58890136239fc6f011f2ff594512d31","result":"success","total_fee":100,' .
            '"url":"http://wx.zhgamy.com/wxjump/?p=https://wx.tenpay.com/cgi-bin/mmpayweb-bin/checkmweb?' .
            'prepay_id=wx20171222103817cee0bf331f0936090112&package=3107081468",' .
            '"sign":"D40EB0676FE5E92A18F752251E011C3D"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $dinWeiXinH5 = new DinWeiXinH5();
        $dinWeiXinH5->setContainer($this->container);
        $dinWeiXinH5->setClient($this->client);
        $dinWeiXinH5->setResponse($response);
        $dinWeiXinH5->setPrivateKey('test');
        $dinWeiXinH5->setOptions($options);
        $data = $dinWeiXinH5->getVerifyData();

        $postUrl = 'http://wx.zhgamy.com/wxjump/?p=https://wx.tenpay.com/cgi-bin/mmpayweb-bin/' .
            'checkmweb?prepay_id=wx20171222103817cee0bf331f0936090112&package=3107081468';
        $this->assertEquals($postUrl, $data['post_url']);
        $this->assertEmpty($data['params']);
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

        $dinWeiXinH5 = new DinWeiXinH5();
        $dinWeiXinH5->verifyOrderPayment([]);
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

        $dinWeiXinH5 = new DinWeiXinH5();
        $dinWeiXinH5->setPrivateKey('test');
        $dinWeiXinH5->verifyOrderPayment([]);
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
            'is_paid' => 'true',
            'merchant_id' => 'spade',
            'nonce_str' => '4d8ddc5de0fe53e77c7d9995ef9437f9',
            'notify_time' => '20171222104037',
            'order_no' => '201712220000008212',
            'out_trade_no' => 'f58890136239fc6f011f2ff594512d31',
            'service' => 'Weixin_h5',
            'total_fee' => '100',
        ];

        $dinWeiXinH5 = new DinWeiXinH5();
        $dinWeiXinH5->setPrivateKey('test');
        $dinWeiXinH5->setOptions($options);
        $dinWeiXinH5->verifyOrderPayment([]);
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
            'is_paid' => 'true',
            'merchant_id' => 'spade',
            'nonce_str' => '4d8ddc5de0fe53e77c7d9995ef9437f9',
            'notify_time' => '20171222104037',
            'order_no' => '201712220000008212',
            'out_trade_no' => 'f58890136239fc6f011f2ff594512d31',
            'service' => 'Weixin_h5',
            'total_fee' => '100',
            'sign' => 'B915C78C6467060C3DFF45A4186A3F9A',
        ];

        $dinWeiXinH5 = new DinWeiXinH5();
        $dinWeiXinH5->setPrivateKey('test');
        $dinWeiXinH5->setOptions($options);
        $dinWeiXinH5->verifyOrderPayment([]);
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

        $options = [
            'is_paid' => 'false',
            'merchant_id' => 'spade',
            'nonce_str' => '4d8ddc5de0fe53e77c7d9995ef9437f9',
            'notify_time' => '20171222104037',
            'order_no' => '201712220000008212',
            'out_trade_no' => 'f58890136239fc6f011f2ff594512d31',
            'service' => 'Weixin_h5',
            'total_fee' => '100',
            'sign' => '2C5B7D7D1B18CE67312A4246304CFAB8',
        ];

        $dinWeiXinH5 = new DinWeiXinH5();
        $dinWeiXinH5->setPrivateKey('test');
        $dinWeiXinH5->setOptions($options);
        $dinWeiXinH5->verifyOrderPayment([]);
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
            'is_paid' => 'true',
            'merchant_id' => 'spade',
            'nonce_str' => '4d8ddc5de0fe53e77c7d9995ef9437f9',
            'notify_time' => '20171222104037',
            'order_no' => '201712220000008212',
            'out_trade_no' => 'f58890136239fc6f011f2ff594512d31',
            'service' => 'Weixin_h5',
            'total_fee' => '100',
            'sign' => 'F2CD4350BD0C52E59A73FAF5847D3491',
        ];

        $entry = ['id' => '201503220000000555'];

        $dinWeiXinH5 = new DinWeiXinH5();
        $dinWeiXinH5->setPrivateKey('test');
        $dinWeiXinH5->setOptions($options);
        $dinWeiXinH5->verifyOrderPayment($entry);
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
            'is_paid' => 'true',
            'merchant_id' => 'spade',
            'nonce_str' => '4d8ddc5de0fe53e77c7d9995ef9437f9',
            'notify_time' => '20171222104037',
            'order_no' => '201712220000008212',
            'out_trade_no' => 'f58890136239fc6f011f2ff594512d31',
            'service' => 'Weixin_h5',
            'total_fee' => '100',
            'sign' => 'F2CD4350BD0C52E59A73FAF5847D3491',
        ];

        $entry = [
            'id' => '201712220000008212',
            'amount' => '15.00',
        ];

        $dinWeiXinH5 = new DinWeiXinH5();
        $dinWeiXinH5->setPrivateKey('test');
        $dinWeiXinH5->setOptions($options);
        $dinWeiXinH5->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $options = [
            'is_paid' => 'true',
            'merchant_id' => 'spade',
            'nonce_str' => '4d8ddc5de0fe53e77c7d9995ef9437f9',
            'notify_time' => '20171222104037',
            'order_no' => '201712220000008212',
            'out_trade_no' => 'f58890136239fc6f011f2ff594512d31',
            'service' => 'Weixin_h5',
            'total_fee' => '100',
            'sign' => 'F2CD4350BD0C52E59A73FAF5847D3491',
        ];

        $entry = [
            'id' => '201712220000008212',
            'amount' => '1.00',
        ];

        $dinWeiXinH5 = new DinWeiXinH5();
        $dinWeiXinH5->setPrivateKey('test');
        $dinWeiXinH5->setOptions($options);
        $dinWeiXinH5->verifyOrderPayment($entry);

        $this->assertEquals('success', $dinWeiXinH5->getMsg());
    }
}
