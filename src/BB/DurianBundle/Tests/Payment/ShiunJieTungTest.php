<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ShiunJieTung;
use Buzz\Message\Response;

class ShiunJieTungTest extends DurianTestCase
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

        $shiunJieTung = new ShiunJieTung();
        $shiunJieTung->getVerifyData();
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

        $shiunJieTung = new ShiunJieTung();
        $shiunJieTung->setPrivateKey('test');
        $shiunJieTung->getVerifyData();
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
            'number' => '777140159310001',
            'amount' => '0.01',
            'orderId' => '201710300000001921',
            'paymentVendorId' => '999',
            'notify_url' => 'http://pay.my/pay/pay.php',
            'username' => 'php1test',
        ];

        $shiunJieTung = new ShiunJieTung();
        $shiunJieTung->setPrivateKey('test');
        $shiunJieTung->setOptions($sourceData);
        $shiunJieTung->getVerifyData();
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

        $sourceData = [
            'number' => '777140159310001',
            'amount' => '0.01',
            'orderId' => '201710300000001921',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://pay.my/pay/pay.php',
            'username' => 'php1test',
            'verify_url' => '',
        ];

        $shiunJieTung = new ShiunJieTung();
        $shiunJieTung->setPrivateKey('test');
        $shiunJieTung->setOptions($sourceData);
        $shiunJieTung->getVerifyData();
    }

    /**
     * 測試支付時沒有返回respCode
     */
    public function testPayReturnWithoutRespCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => '777140159310001',
            'amount' => '0.01',
            'orderId' => '201710300000001921',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://pay.my/pay/pay.php',
            'username' => 'php1test',
            'verify_url' => 'payment.http.a.bldpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"barCode":"weixin://wxpay/bizpayurl?pr=NKikuWe","merchno":"777140159310001",' .
            '"message":"下单成功","refno":"251802497099","traceno":"201710300000001921"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $shiunJieTung = new ShiunJieTung();
        $shiunJieTung->setContainer($this->container);
        $shiunJieTung->setClient($this->client);
        $shiunJieTung->setResponse($response);
        $shiunJieTung->setPrivateKey('test');
        $shiunJieTung->setOptions($sourceData);
        $shiunJieTung->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '交易失败,找不到二维码路由信息',
            180130
        );

        $sourceData = [
            'number' => '777140159310001',
            'amount' => '0.01',
            'orderId' => '201710300000001921',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://pay.my/pay/pay.php',
            'username' => 'php1test',
            'verify_url' => 'payment.http.a.bldpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"merchno":"777140159310001","message":"交易失败,找不到二维码路由信息",' .
            '"respCode":"58","traceno":"201710300000001921"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $shiunJieTung = new ShiunJieTung();
        $shiunJieTung->setContainer($this->container);
        $shiunJieTung->setClient($this->client);
        $shiunJieTung->setResponse($response);
        $shiunJieTung->setPrivateKey('test');
        $shiunJieTung->setOptions($sourceData);
        $shiunJieTung->getVerifyData();
    }

    /**
     * 測試支付時沒有返回barCode
     */
    public function testPayReturnWithoutBarCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => '777140159310001',
            'amount' => '0.01',
            'orderId' => '201710300000001921',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://pay.my/pay/pay.php',
            'username' => 'php1test',
            'verify_url' => 'payment.http.a.bldpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"merchno":"777140159310001","message":"下单成功",' .
            '"refno":"251802497099","respCode":"00","traceno":"201710300000001921"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $shiunJieTung = new ShiunJieTung();
        $shiunJieTung->setContainer($this->container);
        $shiunJieTung->setClient($this->client);
        $shiunJieTung->setResponse($response);
        $shiunJieTung->setPrivateKey('test');
        $shiunJieTung->setOptions($sourceData);
        $shiunJieTung->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '777140159310001',
            'amount' => '0.01',
            'orderId' => '201710300000001921',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://pay.my/pay/pay.php',
            'username' => 'php1test',
            'verify_url' => 'payment.http.a.bldpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"barCode":"weixin://wxpay/bizpayurl?pr=NKikuWe","merchno":"777140159310001",' .
            '"message":"下单成功","refno":"251802497099","respCode":"00","traceno":"201710300000001921"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $shiunJieTung = new ShiunJieTung();
        $shiunJieTung->setContainer($this->container);
        $shiunJieTung->setClient($this->client);
        $shiunJieTung->setResponse($response);
        $shiunJieTung->setPrivateKey('test');
        $shiunJieTung->setOptions($sourceData);
        $data = $shiunJieTung->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=NKikuWe', $shiunJieTung->getQrcode());
    }

    /**
     * 測試手機支付
     */
    public function testCellPhonePay()
    {
        $sourceData = [
            'number' => '211440354110008',
            'amount' => '1.54',
            'orderId' => '201712270000007519',
            'paymentVendorId' => '1097',
            'notify_url' => 'http://pay.simu/pay/pay.php',
            'username' => 'php1test',
            'verify_url' => 'payment.http.a.bldpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"barCode":"http://a.bldpay.com:8209/payapi/wap/?url=https:' .
            '//epay.211pay.com/portal?body=shopping&charset=UTF-8&defaultbank=' .
            'WXPAY&merchant_ID=100000000011627&mobile=3&notify_url=http://a.bl' .
            'dpay.com:8209/payapi/ScfQrcodeNotify&order_no=251804744846&paymen' .
            't_type=1&paymethod=directPay&return_url=http://a.bldpay.com:8209/' .
            'payapi/qrcodeReturn?&seller_email=b930hhs@163.com&service=online_' .
            'pay&sign=68107af36301f11cfd1dc971794ff22c&sign_type=MD5&title=sha' .
            'ngpin&total_fee=1.54","merchno":"211440354110008","message":"交易' .
            '成功","refno":"251804744846","respCode":"00","traceno":"201712270000007519"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $shiunJieTung = new ShiunJieTung();
        $shiunJieTung->setContainer($this->container);
        $shiunJieTung->setClient($this->client);
        $shiunJieTung->setResponse($response);
        $shiunJieTung->setPrivateKey('test');
        $shiunJieTung->setOptions($sourceData);
        $data = $shiunJieTung->getVerifyData();

        $postUrl = 'http://a.bldpay.com:8209/payapi/wap/?url=https://epay.211pay.com/' .
            'portal?body=shopping&charset=UTF-8&defaultbank=WXPAY&merchant_ID=' .
            '100000000011627&mobile=3&notify_url=http://a.bldpay.com:8209/payapi/' .
            'ScfQrcodeNotify&order_no=251804744846&payment_type=1&paymethod=direct' .
            'Pay&return_url=http://a.bldpay.com:8209/payapi/qrcodeReturn?&seller_email=b930hhs' .
            '@163.com&service=online_pay&sign=68107af36301f11cfd1dc971794ff22c&sign_type=' .
            'MD5&title=shangpin&total_fee=1.54';

        $this->assertEmpty($data['params']);
        $this->assertEquals($postUrl, $data['post_url']);
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

        $shiunJieTung = new ShiunJieTung();
        $shiunJieTung->verifyOrderPayment([]);
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

        $shiunJieTung = new ShiunJieTung();
        $shiunJieTung->setPrivateKey('test');
        $shiunJieTung->verifyOrderPayment([]);
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

        $sourceData = [
            'merchno' => '777140159310001',
            'status' => '1',
            'traceno' => '201710300000001921',
            'orderno' => '251802497099',
            'merchName' => '恒发源通-116688',
            'channelOrderno' => '',
            'amount' => '1.00',
            'transDate' => '2017-10-30',
            'channelTraceno' => '',
            'transTime' => '15:10:45',
            'payType' => '2',
            'openId' => 'weixin://wxpay/bizpayurl?pr=NKikuWe',
        ];

        $shiunJieTung = new ShiunJieTung();
        $shiunJieTung->setPrivateKey('test');
        $shiunJieTung->setOptions($sourceData);
        $shiunJieTung->verifyOrderPayment([]);
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
            'merchno' => '777140159310001',
            'status' => '1',
            'traceno' => '201710300000001921',
            'orderno' => '251802497099',
            'merchName' => '恒发源通-116688',
            'channelOrderno' => '',
            'amount' => '1.00',
            'transDate' => '2017-10-30',
            'channelTraceno' => '',
            'transTime' => '15:10:45',
            'payType' => '2',
            'signature' => '67B30EE01A8AFB2D718AAFE4956ABFE8',
            'openId' => 'weixin://wxpay/bizpayurl?pr=NKikuWe',
        ];

        $shiunJieTung = new ShiunJieTung();
        $shiunJieTung->setPrivateKey('test');
        $shiunJieTung->setOptions($sourceData);
        $shiunJieTung->verifyOrderPayment([]);
    }

    /**
     * 測試返回時訂單未支付
     */
    public function testReturnWithUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $sourceData = [
            'merchno' => '777140159310001',
            'status' => '0',
            'traceno' => '201710300000001921',
            'orderno' => '251802497099',
            'merchName' => '恒发源通-116688',
            'channelOrderno' => '',
            'amount' => '1.00',
            'transDate' => '2017-10-30',
            'channelTraceno' => '',
            'transTime' => '15:10:45',
            'payType' => '2',
            'signature' => 'd4e12dc8832ae9266024cf4acb97370f',
            'openId' => 'weixin://wxpay/bizpayurl?pr=NKikuWe',
        ];

        $shiunJieTung = new ShiunJieTung();
        $shiunJieTung->setPrivateKey('test');
        $shiunJieTung->setOptions($sourceData);
        $shiunJieTung->verifyOrderPayment([]);
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

        $sourceData = [
            'merchno' => '777140159310001',
            'status' => '9',
            'traceno' => '201710300000001921',
            'orderno' => '251802497099',
            'merchName' => '恒发源通-116688',
            'channelOrderno' => '',
            'amount' => '1.00',
            'transDate' => '2017-10-30',
            'channelTraceno' => '',
            'transTime' => '15:10:45',
            'payType' => '2',
            'signature' => 'aa59783e26cd77466e74b0cf081090b3',
            'openId' => 'weixin://wxpay/bizpayurl?pr=NKikuWe',
        ];

        $shiunJieTung = new ShiunJieTung();
        $shiunJieTung->setPrivateKey('test');
        $shiunJieTung->setOptions($sourceData);
        $shiunJieTung->verifyOrderPayment([]);
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
            'merchno' => '777140159310001',
            'status' => '1',
            'traceno' => '201710300000001921',
            'orderno' => '251802497099',
            'merchName' => '恒发源通-116688',
            'channelOrderno' => '',
            'amount' => '1.00',
            'transDate' => '2017-10-30',
            'channelTraceno' => '',
            'transTime' => '15:10:45',
            'payType' => '2',
            'signature' => '9eaf0c0e213397fcc1d78d0792c7b762',
            'openId' => 'weixin://wxpay/bizpayurl?pr=NKikuWe',
        ];

        $entry = [
            'id' => '201710300000001920',
            'amount' => '1',
        ];

        $shiunJieTung = new ShiunJieTung();
        $shiunJieTung->setPrivateKey('test');
        $shiunJieTung->setOptions($sourceData);
        $shiunJieTung->verifyOrderPayment($entry);
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

        $sourceData = [
            'merchno' => '777140159310001',
            'status' => '1',
            'traceno' => '201710300000001921',
            'orderno' => '251802497099',
            'merchName' => '恒发源通-116688',
            'channelOrderno' => '',
            'amount' => '1.00',
            'transDate' => '2017-10-30',
            'channelTraceno' => '',
            'transTime' => '15:10:45',
            'payType' => '2',
            'signature' => '9eaf0c0e213397fcc1d78d0792c7b762',
            'openId' => 'weixin://wxpay/bizpayurl?pr=NKikuWe',
        ];

        $entry = [
            'id' => '201710300000001921',
            'amount' => '10',
        ];

        $shiunJieTung = new ShiunJieTung();
        $shiunJieTung->setPrivateKey('test');
        $shiunJieTung->setOptions($sourceData);
        $shiunJieTung->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $sourceData = [
            'merchno' => '777140159310001',
            'status' => '1',
            'traceno' => '201710300000001921',
            'orderno' => '251802497099',
            'merchName' => '恒发源通-116688',
            'channelOrderno' => '',
            'amount' => '1.00',
            'transDate' => '2017-10-30',
            'channelTraceno' => '',
            'transTime' => '15:10:45',
            'payType' => '2',
            'signature' => '9eaf0c0e213397fcc1d78d0792c7b762',
            'openId' => 'weixin://wxpay/bizpayurl?pr=NKikuWe',
        ];

        $entry = [
            'id' => '201710300000001921',
            'amount' => '1',
        ];

        $shiunJieTung = new ShiunJieTung();
        $shiunJieTung->setPrivateKey('test');
        $shiunJieTung->setOptions($sourceData);
        $shiunJieTung->verifyOrderPayment($entry);

        $this->assertEquals('success', $shiunJieTung->getMsg());
    }
}
