<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ZBPay2;
use Buzz\Message\Response;

class ZBPay2Test extends DurianTestCase
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
     * 測試支付加密時沒有帶入privateKey的情況
     */
    public function testPayWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $zBPay2 = new ZBPay2();
        $zBPay2->getVerifyData();
    }

    /**
     * 測試支付加密時未指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = ['number' => ''];

        $zBPay2 = new ZBPay2();
        $zBPay2->setPrivateKey('1234');
        $zBPay2->setOptions($sourceData);
        $zBPay2->getVerifyData();
    }

    /**
     * 測試支付加密時帶入支付平台不支援的銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '10044',
            'paymentVendorId' => '999',
            'amount' => '0.01',
            'orderId' => '201711210000007717',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate' => '2017-11-13 15:40:00',
        ];

        $zBPay2 = new ZBPay2();
        $zBPay2->setPrivateKey('1234');
        $zBPay2->setOptions($sourceData);
        $zBPay2->getVerifyData();
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
            'number' => '10044',
            'paymentVendorId' => '1092',
            'amount' => '0.01',
            'orderId' => '201711210000007717',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate' => '2017-11-13 15:40:00',
            'verify_url' => '',
        ];

        $zBPay2 = new ZBPay2();
        $zBPay2->setPrivateKey('test');
        $zBPay2->setOptions($sourceData);
        $zBPay2->getVerifyData();
    }

    /**
     * 測試支付時未返回code
     */
    public function testPayNoReturnWithoutCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '{"qrtype":"qrcode","qrinfo":"weixin://wxpay/bizpayurl?pr=JKhzA2y"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => '10044',
            'paymentVendorId' => '1092',
            'amount' => '0.01',
            'orderId' => '201711210000007717',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate' => '2017-11-13 15:40:00',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $zBPay2 = new ZBPay2();
        $zBPay2->setContainer($this->container);
        $zBPay2->setClient($this->client);
        $zBPay2->setResponse($response);
        $zBPay2->setPrivateKey('test');
        $zBPay2->setOptions($sourceData);
        $zBPay2->getVerifyData();
    }

    /**
     * 測試二維支付提交失敗
     */
    public function testQrcodePayReturnFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '获取二维码失败',
            180130
        );

        $result = '{"code":1,"msg":"获取二维码失败"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => '10044',
            'paymentVendorId' => '1092',
            'amount' => '0.01',
            'orderId' => '201711210000007717',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate' => '2017-11-13 15:40:00',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $zBPay2 = new ZBPay2();
        $zBPay2->setContainer($this->container);
        $zBPay2->setClient($this->client);
        $zBPay2->setResponse($response);
        $zBPay2->setPrivateKey('test');
        $zBPay2->setOptions($sourceData);
        $zBPay2->getVerifyData();
    }

    /**
     * 測試支付時未返回qrinfo
     */
    public function testQrcodePayNoReturnQrinfo()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '{"qrtype":"qrcode","code":0,"msg":""}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => '10044',
            'paymentVendorId' => '1092',
            'amount' => '0.01',
            'orderId' => '201711210000007717',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate' => '2017-11-13 15:40:00',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $zBPay2 = new ZBPay2();
        $zBPay2->setContainer($this->container);
        $zBPay2->setClient($this->client);
        $zBPay2->setResponse($response);
        $zBPay2->setPrivateKey('test');
        $zBPay2->setOptions($sourceData);
        $zBPay2->getVerifyData();
    }

    /**
     * 測試二維支付返回Qrcode
     */
    public function testQrcodePayWtihQrcode()
    {
        $result = '{"qrtype":"qrcode","qrinfo":"weixin://wxpay/bizpayurl?pr=JKhzA2y","code":0,"msg":""}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => '10044',
            'paymentVendorId' => '1092',
            'amount' => '0.01',
            'orderId' => '201711210000007717',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate' => '2017-11-13 15:40:00',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $zBPay2 = new ZBPay2();
        $zBPay2->setContainer($this->container);
        $zBPay2->setClient($this->client);
        $zBPay2->setResponse($response);
        $zBPay2->setPrivateKey('test');
        $zBPay2->setOptions($sourceData);
        $zBPay2->getVerifyData();
        $data = $zBPay2->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=JKhzA2y', $zBPay2->getQrcode());
    }

    /**
     * 測試二維支付返回url
     */
    public function testQrcodePayWtihUrl()
    {
        $result = '{"qrtype":"url","qrinfo":"https://gateway.zbpay365.com/redirect/url?key=' .
            '13F0B29A7C56487D782C5E2A04FF854A","code":0,"msg":""}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => '10044',
            'paymentVendorId' => '1107',
            'amount' => '0.01',
            'orderId' => '201711210000007717',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate' => '2017-11-13 15:40:00',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $zBPay2 = new ZBPay2();
        $zBPay2->setContainer($this->container);
        $zBPay2->setClient($this->client);
        $zBPay2->setResponse($response);
        $zBPay2->setPrivateKey('test');
        $zBPay2->setOptions($sourceData);
        $zBPay2->getVerifyData();
        $data = $zBPay2->getVerifyData();

        $postUrl = 'https://gateway.zbpay365.com/redirect/url?key=13F0B29A7C56487D782C5E2A04FF854A';
        $this->assertEquals($postUrl, $data['post_url']);
        $this->assertEmpty($data['params']);
    }

    /**
     * 測試支付時缺少postUrl
     */
    public function testPayWithoutPostUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = [
            'number' => '10044',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201711210000007717',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate' => '2017-11-13 15:40:00',
            'postUrl' => '',
        ];

        $zBPay2 = new ZBPay2();
        $zBPay2->setPrivateKey('1234');
        $zBPay2->setOptions($sourceData);
        $zBPay2->getVerifyData();
    }

    /**
     * 測試銀聯快捷支付
     */
    public function testUnionPay()
    {
        $sourceData = [
            'number' => '10044',
            'paymentVendorId' => '278',
            'amount' => '0.01',
            'orderId' => '201711210000007717',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate' => '2017-11-13 15:40:00',
            'postUrl' => 'https://gateway.zbpay365.com',
        ];

        $zBPay2 = new ZBPay2();
        $zBPay2->setPrivateKey('1234');
        $zBPay2->setOptions($sourceData);
        $encodeData = $zBPay2->getVerifyData();

        $this->assertEquals('https://gateway.zbpay365.com/FastPay/Index', $encodeData['post_url']);
        $this->assertEquals($sourceData['number'], $encodeData['params']['merchantid']);
        $this->assertEquals('', $encodeData['params']['bankcode']);
        $this->assertEquals('0.01', $encodeData['params']['amount']);
        $this->assertEquals($sourceData['orderId'], $encodeData['params']['orderid']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['params']['notifyurl']);
        $this->assertEquals('20171113154000', $encodeData['params']['request_time']);
        $this->assertEquals('', $encodeData['params']['returnurl']);
        $this->assertEquals('', $encodeData['params']['desc']);
        $this->assertEquals('e4a53bd6783c944ed829681cb209d729', $encodeData['params']['sign']);
        $this->assertArrayNotHasKey('paytype', $encodeData['params']);
        $this->assertArrayNotHasKey('israndom', $encodeData['params']);
        $this->assertArrayNotHasKey('isqrcode', $encodeData['params']);
    }

    /**
     * 測試支付加密
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '10044',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201711210000007717',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate' => '2017-11-13 15:40:00',
            'postUrl' => 'https://gateway.zbpay365.com',
        ];

        $zBPay2 = new ZBPay2();
        $zBPay2->setPrivateKey('1234');
        $zBPay2->setOptions($sourceData);
        $encodeData = $zBPay2->getVerifyData();

        $this->assertEquals('https://gateway.zbpay365.com/GateWay/Pay', $encodeData['post_url']);
        $this->assertEquals($sourceData['number'], $encodeData['params']['merchantid']);
        $this->assertEquals('967', $encodeData['params']['paytype']);
        $this->assertEquals('0.01', $encodeData['params']['amount']);
        $this->assertEquals($sourceData['orderId'], $encodeData['params']['orderid']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['params']['notifyurl']);
        $this->assertEquals('20171113154000', $encodeData['params']['request_time']);
        $this->assertEquals('', $encodeData['params']['returnurl']);
        $this->assertEquals('N', $encodeData['params']['israndom']);
        $this->assertEquals('', $encodeData['params']['desc']);
        $this->assertEquals('006ca9383212864272139bfa92e85a07', $encodeData['params']['sign']);
    }

    /**
     * 測試條碼支付
     */
    public function testCodePay()
    {
        $sourceData = [
            'number' => '10044',
            'paymentVendorId' => '1115',
            'amount' => '0.01',
            'orderId' => '201711210000007717',
            'notify_url' => 'http://pay.my/',
            'orderCreateDate' => '2017-11-13 15:40:00',
            'postUrl' => 'https://gateway.zbpay365.com',
        ];

        $zBPay2 = new ZBPay2();
        $zBPay2->setPrivateKey('1234');
        $zBPay2->setOptions($sourceData);
        $encodeData = $zBPay2->getVerifyData();

        $this->assertEquals('https://gateway.zbpay365.com/WxPay/BarCodePay', $encodeData['post_url']);
        $this->assertEquals('10044', $encodeData['params']['merchantid']);
        $this->assertEquals('0.01', $encodeData['params']['amount']);
        $this->assertEquals('201711210000007717', $encodeData['params']['orderid']);
        $this->assertEquals('http://pay.my/', $encodeData['params']['notifyurl']);
        $this->assertEquals('20171113154000', $encodeData['params']['request_time']);
        $this->assertEquals('', $encodeData['params']['returnurl']);
        $this->assertEquals('', $encodeData['params']['desc']);
        $this->assertEquals('0276d95b3acb53c55a68eb2d855e6e39', $encodeData['params']['sign']);
        $this->assertArrayNotHasKey('paytype', $encodeData['params']);
        $this->assertArrayNotHasKey('israndom', $encodeData['params']);
        $this->assertArrayNotHasKey('isqrcode', $encodeData['params']);
    }

    /**
     * 測試返回時沒有帶入key的情況
     */
    public function testReturnWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $zBPay2 = new ZBPay2();
        $zBPay2->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台未指定返回參數
     */
    public function testReturnWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'orderid' => '201711210000007717',
            'result' => '1',
        ];

        $zBPay2 = new ZBPay2();
        $zBPay2->setPrivateKey('1234');
        $zBPay2->setOptions($sourceData);
        $zBPay2->verifyOrderPayment([]);
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
            'orderid' => '201711210000007722',
            'result' => '1',
            'amount' => '2.00',
            'systemorderid' => 'Q171121164323996568207852',
            'completetime' => '20171121164443',
            'notifytime' => '20171121164443',
            'attach' => '',
            'sourceamount' => '2.00',
        ];

        $zBPay2 = new ZBPay2();
        $zBPay2->setPrivateKey('1234');
        $zBPay2->setOptions($sourceData);
        $zBPay2->verifyOrderPayment([]);
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
            'orderid' => '201711210000007722',
            'result' => '0',
            'amount' => '2.00',
            'systemorderid' => 'Q171121164323996568207852',
            'completetime' => '20171121164443',
            'notifytime' => '20171121164443',
            'sign' => 'b8cf812c67416b2b4251c57389743d00',
            'attach' => '',
            'sourceamount' => '2.00',
        ];

        $zBPay2 = new ZBPay2();
        $zBPay2->setPrivateKey('1234');
        $zBPay2->setOptions($sourceData);
        $zBPay2->verifyOrderPayment([]);
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
            'orderid' => '201711210000007722',
            'result' => '0',
            'amount' => '2.00',
            'systemorderid' => 'Q171121164323996568207852',
            'completetime' => '20171121164443',
            'notifytime' => '20171121164443',
            'sign' => '476579aaea09b162f292940d7dd9534d',
            'attach' => '',
            'sourceamount' => '2.00',
        ];

        $zBPay2 = new ZBPay2();
        $zBPay2->setPrivateKey('1234');
        $zBPay2->setOptions($sourceData);
        $zBPay2->verifyOrderPayment([]);
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
            'orderid' => '201711210000007722',
            'result' => '9',
            'amount' => '2.00',
            'systemorderid' => 'Q171121164323996568207852',
            'completetime' => '20171121164443',
            'notifytime' => '20171121164443',
            'sign' => '11afc514138af1b5b6fcde6d5321cb0f',
            'attach' => '',
            'sourceamount' => '2.00',
        ];

        $zBPay2 = new ZBPay2();
        $zBPay2->setPrivateKey('1234');
        $zBPay2->setOptions($sourceData);
        $zBPay2->verifyOrderPayment([]);
    }

    /**
     * 測試返回時單號不正確的情況
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'orderid' => '201711210000007722',
            'result' => '1',
            'amount' => '2.00',
            'systemorderid' => 'Q171121164323996568207852',
            'completetime' => '20171121164443',
            'notifytime' => '20171121164443',
            'sign' => '7bb1582319cad3b59f75b6c1dd91202d',
            'attach' => '',
            'sourceamount' => '2.00',
        ];


        $entry = ['id' => '201606220000002806'];

        $zBPay2 = new ZBPay2();
        $zBPay2->setPrivateKey('1234');
        $zBPay2->setOptions($sourceData);
        $zBPay2->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時金額不正確的情況
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'orderid' => '201711210000007722',
            'result' => '1',
            'amount' => '2.00',
            'systemorderid' => 'Q171121164323996568207852',
            'completetime' => '20171121164443',
            'notifytime' => '20171121164443',
            'sign' => '7bb1582319cad3b59f75b6c1dd91202d',
            'attach' => '',
            'sourceamount' => '2.00',
        ];


        $entry = [
            'id' => '201711210000007722',
            'amount' => '1.0000',
        ];

        $zBPay2 = new ZBPay2();
        $zBPay2->setPrivateKey('1234');
        $zBPay2->setOptions($sourceData);
        $zBPay2->verifyOrderPayment($entry);
    }

    /**
     * 測試返回支付驗證成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'orderid' => '201711210000007722',
            'result' => '1',
            'amount' => '2.00',
            'systemorderid' => 'Q171121164323996568207852',
            'completetime' => '20171121164443',
            'notifytime' => '20171121164443',
            'sign' => '7bb1582319cad3b59f75b6c1dd91202d',
            'attach' => '',
            'sourceamount' => '2.00',
        ];

        $entry = [
            'id' => '201711210000007722',
            'amount' => '2.00',
        ];

        $zBPay2 = new ZBPay2();
        $zBPay2->setPrivateKey('1234');
        $zBPay2->setOptions($sourceData);
        $zBPay2->verifyOrderPayment($entry);

        $this->assertEquals('success', $zBPay2->getMsg());
    }
}
