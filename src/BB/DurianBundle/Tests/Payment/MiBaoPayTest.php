<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\MiBaoPay;
use Buzz\Message\Response;

class MiBaoPayTest extends DurianTestCase
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

        $miBaoPay = new MiBaoPay();
        $miBaoPay->getVerifyData();
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

        $miBaoPay = new MiBaoPay();
        $miBaoPay->setPrivateKey('test');
        $miBaoPay->getVerifyData();
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
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '999',
            'number' => '800096',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'orderId' => '201710250000005263',
            'amount' => '1.01',
        ];

        $miBaoPay = new MiBaoPay();
        $miBaoPay->setPrivateKey('test');
        $miBaoPay->setOptions($options);
        $miBaoPay->getVerifyData();
    }

    /**
     * 測試二維支付時沒有帶入ip的情況
     */
    public function testQRcodePayWithoutIp()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '800096',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'orderId' => '201710250000005263',
            'amount' => '1.01',
        ];

        $miBaoPay = new MiBaoPay();
        $miBaoPay->setPrivateKey('test');
        $miBaoPay->setOptions($options);
        $miBaoPay->getVerifyData();
    }

    /**
     * 測試二維支付時沒有帶入verify_url的情況
     */
    public function testQRcodePayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '800096',
            'ip' => '10.251.123.123',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'orderId' => '201710250000005263',
            'amount' => '1.01',
            'verify_url' => '',
        ];

        $miBaoPay = new MiBaoPay();
        $miBaoPay->setPrivateKey('test');
        $miBaoPay->setOptions($options);
        $miBaoPay->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回code
     */
    public function testQRcodePayReturnWithoutCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '800096',
            'ip' => '10.251.123.123',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'orderId' => '201710250000005263',
            'amount' => '1.01',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'data' => [
                'merchantCode' => '800096',
                'orderId' => '2017082300014187640',
                'outOrderId' => '201708230000003997',
                'sign' => '01A5FA38ACC437602F3AB8F35C13958C',
                'url' => 'weixin://wxpay/bizpayurl?pr=eOVHVz1',
            ],
            'msg' => '',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $miBaoPay = new MiBaoPay();
        $miBaoPay->setContainer($this->container);
        $miBaoPay->setClient($this->client);
        $miBaoPay->setResponse($response);
        $miBaoPay->setPrivateKey('test');
        $miBaoPay->setOptions($options);
        $miBaoPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回提交失敗
     */
    public function testQRcodePayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '交易失败',
            180130
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '800096',
            'ip' => '10.251.123.123',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'orderId' => '201710250000005263',
            'amount' => '1.01',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'code' => '05016',
            'msg' => '交易失败',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $miBaoPay = new MiBaoPay();
        $miBaoPay->setContainer($this->container);
        $miBaoPay->setClient($this->client);
        $miBaoPay->setResponse($response);
        $miBaoPay->setPrivateKey('test');
        $miBaoPay->setOptions($options);
        $miBaoPay->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回url
     */
    public function testQRcodePayReturnWithoutUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '800096',
            'ip' => '10.251.123.123',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'orderId' => '201710250000005263',
            'amount' => '1.01',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'code' => '00',
            'data' => [
                'merchantCode' => '800096',
                'orderId' => '2017082300014187640',
                'outOrderId' => '201708230000003997',
                'sign' => '1F47FC99CD203A2E14925624CC5E5D22',
            ],
            'msg' => '',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $miBaoPay = new MiBaoPay();
        $miBaoPay->setContainer($this->container);
        $miBaoPay->setClient($this->client);
        $miBaoPay->setResponse($response);
        $miBaoPay->setPrivateKey('test');
        $miBaoPay->setOptions($options);
        $miBaoPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQRcodePay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '800096',
            'ip' => '10.251.123.123',
            'orderId' => '201710250000005263',
            'amount' => '1.01',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'code' => '00',
            'data' => [
                'merchantCode' => '800096',
                'orderId' => '2017082300014187640',
                'outOrderId' => '201708230000003997',
                'sign' => 'CA198E1900B2EDDA1C5110060D8B3C6D',
                'url' => 'weixin://wxpay/bizpayurl?pr=eOVHVz1',
            ],
            'msg' => '',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $miBaoPay = new MiBaoPay();
        $miBaoPay->setContainer($this->container);
        $miBaoPay->setClient($this->client);
        $miBaoPay->setResponse($response);
        $miBaoPay->setPrivateKey('test');
        $miBaoPay->setOptions($options);
        $data = $miBaoPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=eOVHVz1', $miBaoPay->getQrcode());
    }

    /**
     * 測試QQ手機支付支付
     */
    public function testQQWapPay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1104',
            'number' => '800096',
            'orderId' => '201710250000005263',
            'amount' => '1.01',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'https://api.mibaozf.com',
        ];

        $miBaoPay = new MiBaoPay();
        $miBaoPay->setPrivateKey('test');
        $miBaoPay->setOptions($options);
        $requestData = $miBaoPay->getVerifyData();

        $this->assertEquals('https://api.mibaozf.com/wap/pay', $requestData['post_url']);
        $this->assertEquals($options['number'], $requestData['params']['merchantCode']);
        $this->assertEquals($options['orderId'], $requestData['params']['outOrderId']);
        $this->assertEquals($options['amount'] * 100, $requestData['params']['totalAmount']);
        $this->assertEquals('', $requestData['params']['goodsName']);
        $this->assertEquals('', $requestData['params']['goodsExplain']);
        $this->assertEquals('20170824113232', $requestData['params']['orderCreateTime']);
        $this->assertEquals($options['notify_url'], $requestData['params']['merUrl']);
        $this->assertEquals($options['notify_url'], $requestData['params']['noticeUrl']);
        $this->assertEquals('19', $requestData['params']['payType']);
        $this->assertEquals('1100', $requestData['params']['arrivalType']);
        $this->assertEquals('', $requestData['params']['ext']);
        $this->assertEquals('dc897a36b064077ec294cbb20a1cd8fe', $requestData['params']['sign']);
    }

    /**
     * 測試網銀支付
     */
    public function testBankPay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1',
            'number' => '800096',
            'orderId' => '201710250000005263',
            'amount' => '1.01',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'https://api.mibaozf.com',
        ];

        $miBaoPay = new MiBaoPay();
        $miBaoPay->setPrivateKey('test');
        $miBaoPay->setOptions($options);
        $requestData = $miBaoPay->getVerifyData();

        $this->assertEquals('https://api.mibaozf.com/ebank/pay', $requestData['post_url']);
        $this->assertEquals($options['number'], $requestData['params']['merchantCode']);
        $this->assertEquals($options['orderId'], $requestData['params']['outOrderId']);
        $this->assertEquals($options['amount'] * 100, $requestData['params']['totalAmount']);
        $this->assertEquals('', $requestData['params']['goodsName']);
        $this->assertEquals('', $requestData['params']['goodsExplain']);
        $this->assertEquals('20170824113232', $requestData['params']['orderCreateTime']);
        $this->assertEquals($options['notify_url'], $requestData['params']['merUrl']);
        $this->assertEquals($options['notify_url'], $requestData['params']['noticeUrl']);
        $this->assertEquals('ICBC', $requestData['params']['bankCode']);
        $this->assertEquals('800', $requestData['params']['bankCardType']);
        $this->assertEquals('', $requestData['params']['ext']);
        $this->assertEquals('2e06491f98fb3433309cbb08157fa86d', $requestData['params']['sign']);
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

        $miBaoPay = new MiBaoPay();
        $miBaoPay->verifyOrderPayment([]);
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

        $options = [];

        $miBaoPay = new MiBaoPay();
        $miBaoPay->setPrivateKey('test');
        $miBaoPay->setOptions($options);
        $miBaoPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'ext' => '',
            'merchantCode' => '800096',
            'instructCode' => '91800096211120171025114306283217',
            'outOrderId' => '201710250000005263',
            'transTime' => '20171025114343',
            'totalAmount' => '200',
        ];

        $miBaoPay = new MiBaoPay();
        $miBaoPay->setPrivateKey('test');
        $miBaoPay->setOptions($options);
        $miBaoPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時sign錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $options = [
            'ext' => '',
            'merchantCode' => '800096',
            'instructCode' => '91800096211120171025114306283217',
            'outOrderId' => '201710250000005263',
            'transTime' => '20171025114343',
            'totalAmount' => '200',
            'sign' => '81b16808d38c55c7f4b0e54ad637e35b',
        ];

        $miBaoPay = new MiBaoPay();
        $miBaoPay->setPrivateKey('test');
        $miBaoPay->setOptions($options);
        $miBaoPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時訂單號錯誤
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $options = [
            'ext' => '',
            'merchantCode' => '800096',
            'instructCode' => '91800096211120171025114306283217',
            'outOrderId' => '201710250000005263',
            'transTime' => '20171025114343',
            'totalAmount' => '200',
            'sign' => 'b3fddc9c15edf47af3f83170e7fd7f05',
        ];

        $entry = ['id' => '201503220000000555'];

        $miBaoPay = new MiBaoPay();
        $miBaoPay->setPrivateKey('test');
        $miBaoPay->setOptions($options);
        $miBaoPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時金額不一樣
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $options = [
            'ext' => '',
            'merchantCode' => '800096',
            'instructCode' => '91800096211120171025114306283217',
            'outOrderId' => '201710250000005263',
            'transTime' => '20171025114343',
            'totalAmount' => '200',
            'sign' => 'b3fddc9c15edf47af3f83170e7fd7f05',
        ];

        $entry = [
            'id' => '201710250000005263',
            'amount' => '0.1',
        ];

        $miBaoPay = new MiBaoPay();
        $miBaoPay->setPrivateKey('test');
        $miBaoPay->setOptions($options);
        $miBaoPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'ext' => '',
            'merchantCode' => '800096',
            'instructCode' => '91800096211120171025114306283217',
            'outOrderId' => '201710250000005263',
            'transTime' => '20171025114343',
            'totalAmount' => '200',
            'sign' => 'b3fddc9c15edf47af3f83170e7fd7f05',
        ];
        $entry = [
            'id' => '201710250000005263',
            'amount' => '2.00',
        ];

        $miBaoPay = new MiBaoPay();
        $miBaoPay->setPrivateKey('test');
        $miBaoPay->setOptions($options);
        $miBaoPay->verifyOrderPayment($entry);

        $this->assertEquals("{'code':'00'}", $miBaoPay->getMsg());
    }
}
