<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YunSheng;
use Buzz\Message\Response;

class YunShengTest extends DurianTestCase
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

        $yunSheng = new YunSheng();
        $yunSheng->getVerifyData();
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

        $yunSheng = new YunSheng();
        $yunSheng->setPrivateKey('test');
        $yunSheng->getVerifyData();
    }

    /**
     * 測試支付加密時代入支付平台不支援的銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

         $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '9999',
            'number' => '1234',
            'orderId' => '201712220000008212',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2018-02-02 12:00:00',
        ];

        $yunSheng = new YunSheng();
        $yunSheng->setPrivateKey('test');
        $yunSheng->setOptions($options);
        $yunSheng->getVerifyData();
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
            'number' => '1234',
            'orderId' => '201712220000008212',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2018-02-02 12:00:00',
            'verify_url' => '',
        ];

        $yunSheng = new YunSheng();
        $yunSheng->setPrivateKey('test');
        $yunSheng->setOptions($options);
        $yunSheng->getVerifyData();
    }

    /**
     * 測試支付時沒有返回refMsg
     */
    public function testPayReturnWithoutRefMsgt()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1092',
            'number' => '1234',
            'orderId' => '201712220000008212',
            'amount' => '1.01',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'orderCreateDate' => '2018-02-02 12:00:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"status":"02","merId":"0000020","refCode":"0204","businessType":"1100",' .
            '"transChanlName":"0004"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $yunSheng = new YunSheng();
        $yunSheng->setContainer($this->container);
        $yunSheng->setClient($this->client);
        $yunSheng->setResponse($response);
        $yunSheng->setPrivateKey('test');
        $yunSheng->setOptions($options);
        $yunSheng->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '系统异常。',
            180130
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1092',
            'number' => '1234',
            'orderId' => '201712220000008212',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2018-02-02 12:00:00',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"status":"02","merId":"0000020","refCode":"0204","businessType":"1100",' .
            '"transChanlName":"0004","refMsg":"\u7cfb\u7edf\u5f02\u5e38\u3002"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $yunSheng = new YunSheng();
        $yunSheng->setContainer($this->container);
        $yunSheng->setClient($this->client);
        $yunSheng->setResponse($response);
        $yunSheng->setPrivateKey('test');
        $yunSheng->setOptions($options);
        $yunSheng->getVerifyData();
    }

    /**
     * 測試支付時沒有返回codeImgUrl
     */
    public function testPayReturnWithoutCodeImgUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1092',
            'number' => '1234',
            'orderId' => '201712220000008212',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2018-02-02 12:00:00',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"ksPayOrderId":"0000020062458901517401498137","status":"00",' .
            '"refMsg":"系統異常","signData":"50356EDE13F67D84C56B913596848BB2"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $yunSheng = new YunSheng();
        $yunSheng->setContainer($this->container);
        $yunSheng->setClient($this->client);
        $yunSheng->setResponse($response);
        $yunSheng->setPrivateKey('test');
        $yunSheng->setOptions($options);
        $yunSheng->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '1234',
            'orderId' => '201712220000008212',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2018-02-02 12:00:00',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"ksPayOrderId":"0000020062458901517401498137","status":"00",' .
            '"codeImgUrl":"http://39.108.211.66/qrcode/?cipher_data=c3RhdHVzQHN1Y2Nlc3Mmcm",' .
            '"codeUrl":"http://39.108.211.66/qrcode/?cipher_data=c3RhdHVzQHN1Y2Nlc3Mmcm",' .
            '"merId":"0000020","refCode":"01","businessType":"1100","transChanlName":"0002",' .
            '"refMsg":"系統異常","signData":"50356EDE13F67D84C56B913596848BB2"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $yunSheng = new YunSheng();
        $yunSheng->setContainer($this->container);
        $yunSheng->setClient($this->client);
        $yunSheng->setResponse($response);
        $yunSheng->setPrivateKey('test');
        $yunSheng->setOptions($options);
        $data = $yunSheng->getVerifyData();

        $url = 'http://39.108.211.66/qrcode/?cipher_data=c3RhdHVzQHN1Y2Nlc3Mmcm';
        $this->assertEquals($url, $data['post_url']);
        $this->assertEmpty($data['params']);
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1',
            'number' => '1234',
            'orderId' => '201712220000008212',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2018-02-02 12:00:00',
        ];

        $yunSheng = new YunSheng();
        $yunSheng->setPrivateKey('test');
        $yunSheng->setOptions($options);
        $requestData = $yunSheng->getVerifyData();

        $this->assertEquals('001', $requestData['versionId']);
        $this->assertEquals('1100', $requestData['businessType']);
        $this->assertEquals('', $requestData['insCode']);
        $this->assertEquals('1001', $requestData['transChanlName']);
        $this->assertEquals('', $requestData['openBankName']);
        $this->assertEquals($options['number'], $requestData['merId']);
        $this->assertEquals($options['orderId'], $requestData['orderId']);
        $this->assertEquals('20180202120000', $requestData['transDate']);
        $this->assertEquals('1.01', $requestData['transAmount']);
        $this->assertEquals('156', $requestData['transCurrency']);
        $this->assertEquals($options['notify_url'], $requestData['pageNotifyUrl']);
        $this->assertEquals($options['notify_url'], $requestData['backNotifyUrl']);
        $this->assertEquals('php1test', $requestData['orderDesc']);
        $this->assertEquals('', $requestData['dev']);
        $this->assertEquals('MD5', $requestData['signType']);
        $this->assertEquals('4A96A090F73656ABFD23878F4DFB0E70', $requestData['signData']);
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

        $yunSheng = new YunSheng();
        $yunSheng->verifyOrderPayment([]);
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

        $entry = ['payment_vendor_id' => '1'];

        $yunSheng = new YunSheng();
        $yunSheng->setPrivateKey('test');
        $yunSheng->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時缺少簽名
     */
    public function testReturnWithoutSignData()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'transDate' => '20180131160214',
            'transAmount' => '1.00',
            'ksPayOrderId' => '0000020710235581517403755721',
            'orderDesc' => 'php1test',
            'refcode' => '00',
            'versionId' => '001',
            'businessType' => '1100',
            'transChanlName' => '0002',
            'refMsg' => '%BD%BB%D2%D7%B3%C9%B9%A6',
            'orderId' => '201801310000008817',
        ];

        $entry = ['payment_vendor_id' => '1090'];

        $yunSheng = new YunSheng();
        $yunSheng->setPrivateKey('test');
        $yunSheng->setOptions($options);
        $yunSheng->verifyOrderPayment($entry);
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
            'transDate' => '20180131160214',
            'transAmount' => '1.00',
            'ksPayOrderId' => '0000020710235581517403755721',
            'orderDesc' => 'php1test',
            'refcode' => '00',
            'versionId' => '001',
            'businessType' => '1100',
            'transChanlName' => '0002',
            'refMsg' => '%BD%BB%D2%D7%B3%C9%B9%A6',
            'orderId' => '201801310000008817',
            'signData' => 'D2A04379480EA034238DC108D37FCFA0',
        ];

        $entry = ['payment_vendor_id' => '1090'];

        $yunSheng = new YunSheng();
        $yunSheng->setPrivateKey('test');
        $yunSheng->setOptions($options);
        $yunSheng->verifyOrderPayment($entry);
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
            'transDate' => '20180131160214',
            'transAmount' => '1.00',
            'ksPayOrderId' => '0000020710235581517403755721',
            'orderDesc' => 'php1test',
            'refcode' => '99',
            'versionId' => '001',
            'businessType' => '1100',
            'transChanlName' => '0002',
            'refMsg' => '%BD%BB%D2%D7%B3%C9%B9%A6',
            'orderId' => '201801310000008817',
            'signData' => '1C46CEE7F6EEC5D16C9664B2D66D617C',
        ];

        $entry = ['payment_vendor_id' => '1090'];

        $yunSheng = new YunSheng();
        $yunSheng->setPrivateKey('test');
        $yunSheng->setOptions($options);
        $yunSheng->verifyOrderPayment($entry);
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
            'transDate' => '20180131160214',
            'transAmount' => '1.00',
            'ksPayOrderId' => '0000020710235581517403755721',
            'orderDesc' => 'php1test',
            'refcode' => '00',
            'versionId' => '001',
            'businessType' => '1100',
            'transChanlName' => '0002',
            'refMsg' => '%BD%BB%D2%D7%B3%C9%B9%A6',
            'orderId' => '201801310000008817',
            'signData' => '54AB91247BD4AC1A756ADF5595EB6CF1',
        ];

        $entry = [
            'payment_vendor_id' => '1090',
            'id' => '201503220000000555',
        ];

        $yunSheng = new YunSheng();
        $yunSheng->setPrivateKey('test');
        $yunSheng->setOptions($options);
        $yunSheng->verifyOrderPayment($entry);
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
            'transDate' => '20180131160214',
            'transAmount' => '1.00',
            'ksPayOrderId' => '0000020710235581517403755721',
            'orderDesc' => 'php1test',
            'refcode' => '00',
            'versionId' => '001',
            'businessType' => '1100',
            'transChanlName' => '0002',
            'refMsg' => '%BD%BB%D2%D7%B3%C9%B9%A6',
            'orderId' => '201801310000008817',
            'signData' => '54AB91247BD4AC1A756ADF5595EB6CF1',
        ];

        $entry = [
            'payment_vendor_id' => '1090',
            'id' => '201801310000008817',
            'amount' => '15.00',
        ];

        $yunSheng = new YunSheng();
        $yunSheng->setPrivateKey('test');
        $yunSheng->setOptions($options);
        $yunSheng->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $options = [
            'versionId' => '001',
            'businessType' => '1200',
            'insCode' => '',
            'merId' => '0000020',
            'transDate' => 'Thu Feb 01 08:50:07 EST 2018',
            'transAmount' => '0.01',
            'transCurrency' => '156',
            'transChanlName' => '1100',
            'openBankName' => '',
            'orderId' => '201802010000008841',
            'ksPayOrderId' => '0000020545007511517493007471',
            'payStatus' => '00',
            'payMsg' => '%BD%BB%D2%D7%B3%C9%B9%A6',
            'pageNotifyUrl' => 'http://candj.huhu.tw/pay/pay_response.php',
            'backNotifyUrl' => 'http://candj.huhu.tw/pay/pay_response.php',
            'orderDesc' => 'php1test',
            'dev' => '',
            'signData' => 'AE9A594F7067B0D49564E0222724CE00',
        ];

        $entry = [
            'payment_vendor_id' => '1',
            'id' => '201802010000008841',
            'amount' => '0.01',
        ];

        $yunSheng = new YunSheng();
        $yunSheng->setPrivateKey('test');
        $yunSheng->setOptions($options);
        $yunSheng->verifyOrderPayment($entry);

        $this->assertEquals('ok', $yunSheng->getMsg());
    }

    /**
     * 測試二維支付驗證成功
     */
    public function testQrcodeReturnOrder()
    {
        $options = [
            'transDate' => '20180131160214',
            'transAmount' => '1.00',
            'ksPayOrderId' => '0000020710235581517403755721',
            'orderDesc' => 'php1test',
            'refcode' => '00',
            'versionId' => '001',
            'businessType' => '1100',
            'transChanlName' => '0002',
            'refMsg' => '%BD%BB%D2%D7%B3%C9%B9%A6',
            'orderId' => '201801310000008817',
            'signData' => '54AB91247BD4AC1A756ADF5595EB6CF1',
        ];

        $entry = [
            'payment_vendor_id' => '1090',
            'id' => '201801310000008817',
            'amount' => '1.00',
        ];

        $yunSheng = new YunSheng();
        $yunSheng->setPrivateKey('test');
        $yunSheng->setOptions($options);
        $yunSheng->verifyOrderPayment($entry);

        $this->assertEquals('ok', $yunSheng->getMsg());
    }
}
