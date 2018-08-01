<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YZ;
use Buzz\Message\Response;

class YZTest extends DurianTestCase
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

        $yZ = new YZ();
        $yZ->getVerifyData();
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

        $yZ = new YZ();
        $yZ->setPrivateKey('test');
        $yZ->getVerifyData();
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
            'paymentVendorId' => '1092',
            'number' => 'spade',
            'orderId' => '201712220000008212',
            'amount' => '1.01',
            'username' => 'php1test',
            'verify_url' => '',
        ];

        $yZ = new YZ();
        $yZ->setPrivateKey('test');
        $yZ->setOptions($options);
        $yZ->getVerifyData();
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
            'paymentVendorId' => '1092',
            'number' => 'spade',
            'orderId' => '201712220000008212',
            'amount' => '1.01',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"message":"YZError"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $yZ = new YZ();
        $yZ->setContainer($this->container);
        $yZ->setClient($this->client);
        $yZ->setResponse($response);
        $yZ->setPrivateKey('test');
        $yZ->setOptions($options);
        $yZ->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'YZError',
            180130
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1092',
            'number' => 'spade',
            'orderId' => '201712220000008212',
            'amount' => '1.01',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"result":"fail","message":"YZError"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $yZ = new YZ();
        $yZ->setContainer($this->container);
        $yZ->setClient($this->client);
        $yZ->setResponse($response);
        $yZ->setPrivateKey('test');
        $yZ->setOptions($options);
        $yZ->getVerifyData();
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
            'paymentVendorId' => '1092',
            'number' => 'spade',
            'orderId' => '201712220000008212',
            'amount' => '1.01',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"merchant_id":"spade","message":"success","order_no":"201801300000008718",' .
            '"out_trade_no":"22a7fadd27a487a3a5","result":"success","total_fee":100,' .
            '"sign":"C07E5326B0647BE1BFB3A550120B135A"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $yZ = new YZ();
        $yZ->setContainer($this->container);
        $yZ->setClient($this->client);
        $yZ->setResponse($response);
        $yZ->setPrivateKey('test');
        $yZ->setOptions($options);
        $yZ->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1092',
            'number' => 'spade',
            'orderId' => '201712220000008212',
            'amount' => '1.01',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"merchant_id":"spade","message":"success","order_no":"201801300000008718",' .
            '"out_trade_no":"22a7fadd27a487a3a5","result":"success","total_fee":100,"url":' .
            '"http://jh.yizhibank.com/api/pcOrder?code=MjJhN2ZhZGQyN2E0ODdhM2E1JnlmdDIwMTgwMTI5MDAwMDQ=",' .
            '"sign":"C07E5326B0647BE1BFB3A550120B135A"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $yZ = new YZ();
        $yZ->setContainer($this->container);
        $yZ->setClient($this->client);
        $yZ->setResponse($response);
        $yZ->setPrivateKey('test');
        $yZ->setOptions($options);
        $data = $yZ->getVerifyData();

        $qrcode = 'http://jh.yizhibank.com/api/pcOrder?code=MjJhN2ZhZGQyN2E0ODdhM2E1JnlmdDIwMTgwMTI5MDAwMDQ=';
        $this->assertEmpty($data);
        $this->assertEquals($qrcode, $yZ->getQrcode());
    }

    /**
     * 測試銀聯在線
     */
    public function testOnlinePay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '278',
            'number' => 'spade88-3',
            'orderId' => '201803080000004339',
            'amount' => '1',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $res = [
            'merchant_id' => 'spade88-3',
            'message' => 'success',
            'order_no' => '201803080000004339',
            'out_trade_no' => '95ac3e72143c8db8137d',
            'result' => 'success',
            'total_fee' => 100,
            'url' => '<form id="myForm" action="http://jh.yizhibank.com/api/createQuickOrder" method="post">' .
                '<input type="hidden" name="merchantOutOrderNo" value="66cff7af75bcb7676f">' .
                '<input type="hidden" name="merid" value="yft2018022800001">' .
                '<input type="hidden" name="noncestr" value="c781f299e7e7c3aaf610e17a6b819ff8">' .
                '<input type="hidden" name="notifyUrl" value="http://23.235.141.212:8089/receive/notifyurl.php">' .
                '<input type="hidden" name="orderMoney" value="1">' .
                '<input type="hidden" name="orderTime" value="20180308193614">' .
                '<input type="hidden" name="sign" value="8ce749ef8cbe96fb01701fe5ae4c06b8">' .
                '<script>document.getElementById("myForm").submit();</script></form>',
            'sign' => 'ED9217C6167ECA2C9BFB6958689AFBAC',
        ];

        $result = json_encode($res);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $yZ = new YZ();
        $yZ->setContainer($this->container);
        $yZ->setClient($this->client);
        $yZ->setResponse($response);
        $yZ->setPrivateKey('test');
        $yZ->setOptions($options);
        $data = $yZ->getVerifyData();

        $this->assertEquals('http://jh.yizhibank.com/api/createQuickOrder', $data['post_url']);
        $this->assertEquals('66cff7af75bcb7676f', $data['params']['merchantOutOrderNo']);
        $this->assertEquals('yft2018022800001', $data['params']['merid']);
        $this->assertEquals('c781f299e7e7c3aaf610e17a6b819ff8', $data['params']['noncestr']);
        $this->assertEquals('http://23.235.141.212:8089/receive/notifyurl.php', $data['params']['notifyUrl']);
        $this->assertEquals('1', $data['params']['orderMoney']);
        $this->assertEquals('20180308193614', $data['params']['orderTime']);
        $this->assertEquals('8ce749ef8cbe96fb01701fe5ae4c06b8', $data['params']['sign']);
    }

    /**
     * 測試手機支付
     */
    public function testWapPay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1098',
            'number' => 'spade',
            'orderId' => '201712220000008212',
            'amount' => '1.01',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"merchant_id":"spade88","message":"success","order_no":"201802010000008834",' .
            '"out_trade_no":"7eb2e75429a8ab331b","result":"success","total_fee":1000,"url":' .
            '"alipays://platformapi/startApp?appId=10000011&url=http://jh.yizhibank.com/api/' .
            'createOrder?merchantOutOrderNo=7eb2e75429a8ab331b&merid=yft2018012900004&noncestr=' .
            '1a87ec8acbb619257b9cf6078c9b93bb","sign":"83DE8FB12140169949148012E0996663"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $yZ = new YZ();
        $yZ->setContainer($this->container);
        $yZ->setClient($this->client);
        $yZ->setResponse($response);
        $yZ->setPrivateKey('test');
        $yZ->setOptions($options);
        $data = $yZ->getVerifyData();

        $postUrl = 'alipays://platformapi/startApp?appId=10000011&url=http://jh.yizhibank.com/api/' .
            'createOrder?merchantOutOrderNo=7eb2e75429a8ab331b&merid=yft2018012900004&' .
            'noncestr=1a87ec8acbb619257b9cf6078c9b93bb';
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

        $yZ = new YZ();
        $yZ->verifyOrderPayment([]);
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

        $yZ = new YZ();
        $yZ->setPrivateKey('test');
        $yZ->verifyOrderPayment([]);
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
            'nonce_str' => '4b1156cda464e4bf0aca9465506135c1',
            'notify_time' => '20180130150741',
            'order_no' => '201801300000008718',
            'out_trade_no' => '22a7fadd27a487a3a5',
            'service' => 'YZ_Alipay_QR',
            'total_fee' => '100',
        ];

        $yZ = new YZ();
        $yZ->setPrivateKey('test');
        $yZ->setOptions($options);
        $yZ->verifyOrderPayment([]);
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
            'nonce_str' => '4b1156cda464e4bf0aca9465506135c1',
            'notify_time' => '20180130150741',
            'order_no' => '201801300000008718',
            'out_trade_no' => '22a7fadd27a487a3a5',
            'service' => 'YZ_Alipay_QR',
            'total_fee' => '100',
            'sign' => '69FE0E157757FE9EFD8F798F252ECCC1',
        ];

        $yZ = new YZ();
        $yZ->setPrivateKey('test');
        $yZ->setOptions($options);
        $yZ->verifyOrderPayment([]);
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
            'nonce_str' => '4b1156cda464e4bf0aca9465506135c1',
            'notify_time' => '20180130150741',
            'order_no' => '201801300000008718',
            'out_trade_no' => '22a7fadd27a487a3a5',
            'service' => 'YZ_Alipay_QR',
            'total_fee' => '100',
            'sign' => '55F924DFDE2E861B99E64AB43E1B11BA',
        ];

        $yZ = new YZ();
        $yZ->setPrivateKey('test');
        $yZ->setOptions($options);
        $yZ->verifyOrderPayment([]);
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
            'nonce_str' => '4b1156cda464e4bf0aca9465506135c1',
            'notify_time' => '20180130150741',
            'order_no' => '201801300000008718',
            'out_trade_no' => '22a7fadd27a487a3a5',
            'service' => 'YZ_Alipay_QR',
            'total_fee' => '100',
            'sign' => 'FA577C0DE8B981557C20F92BE4FADB3C',
        ];

        $entry = ['id' => '201503220000000555'];

        $yZ = new YZ();
        $yZ->setPrivateKey('test');
        $yZ->setOptions($options);
        $yZ->verifyOrderPayment($entry);
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
            'nonce_str' => '4b1156cda464e4bf0aca9465506135c1',
            'notify_time' => '20180130150741',
            'order_no' => '201801300000008718',
            'out_trade_no' => '22a7fadd27a487a3a5',
            'service' => 'YZ_Alipay_QR',
            'total_fee' => '100',
            'sign' => 'FA577C0DE8B981557C20F92BE4FADB3C',
        ];

        $entry = [
            'id' => '201801300000008718',
            'amount' => '15.00',
        ];

        $yZ = new YZ();
        $yZ->setPrivateKey('test');
        $yZ->setOptions($options);
        $yZ->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $options = [
            'is_paid' => 'true',
            'merchant_id' => 'spade',
            'nonce_str' => '4b1156cda464e4bf0aca9465506135c1',
            'notify_time' => '20180130150741',
            'order_no' => '201801300000008718',
            'out_trade_no' => '22a7fadd27a487a3a5',
            'service' => 'YZ_Alipay_QR',
            'total_fee' => '100',
            'sign' => 'FA577C0DE8B981557C20F92BE4FADB3C',
        ];

        $entry = [
            'id' => '201801300000008718',
            'amount' => '1.00',
        ];

        $yZ = new YZ();
        $yZ->setPrivateKey('test');
        $yZ->setOptions($options);
        $yZ->verifyOrderPayment($entry);

        $this->assertEquals('success', $yZ->getMsg());
    }
}
