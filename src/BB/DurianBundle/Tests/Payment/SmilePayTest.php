<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\SmilePay;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class SmilePayTest extends DurianTestCase
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

        $smilePay = new SmilePay();
        $smilePay->getVerifyData();
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

        $smilePay = new SmilePay();
        $smilePay->setPrivateKey('test');
        $smilePay->getVerifyData();
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
            'number' => '100310089076',
            'amount' => '10',
            'orderId' => '201712280000006078',
            'paymentVendorId' => '9453',
            'username' => 'php1test',
            'notify_url' => 'http://pay.in-action.tw/',
        ];

        $smilePay = new SmilePay();
        $smilePay->setPrivateKey('test');
        $smilePay->setOptions($options);
        $smilePay->getVerifyData();
    }

    /**
     * 測試支付時沒有帶入MerchantExtra的情況
     */
    public function testPayWithoutMerchantExtra()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $options = [
            'number' => '100310089076',
            'amount' => '9453',
            'orderId' => '201712280000006078',
            'paymentVendorId' => '1103',
            'username' => 'php1test',
            'notify_url' => 'http://pay.in-action.tw/',
            'merchant_extra' => [],
        ];

        $smilePay = new SmilePay();
        $smilePay->setPrivateKey('test');
        $smilePay->setOptions($options);
        $smilePay->getVerifyData();
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
            'number' => '100310089076',
            'amount' => '9453',
            'orderId' => '201712280000006078',
            'paymentVendorId' => '1103',
            'username' => 'php1test',
            'notify_url' => 'http://pay.in-action.tw/',
            'merchant_extra' => ['HashIV' => 'test'],
            'verify_url' => '',
        ];

        $smilePay = new SmilePay();
        $smilePay->setPrivateKey('test');
        $smilePay->setOptions($options);
        $smilePay->getVerifyData();
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

        $options = [
            'number' => '100310089076',
            'amount' => '0.1',
            'orderId' => '201712280000006078',
            'paymentVendorId' => '1103',
            'username' => 'php1test',
            'notify_url' => 'http://pay.in-action.tw/',
            'merchant_extra' => ['HashIV' => 'test'],
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = 'Code:90,Message:支付接口已关闭';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $smilePay = new SmilePay();
        $smilePay->setContainer($this->container);
        $smilePay->setClient($this->client);
        $smilePay->setResponse($response);
        $smilePay->setPrivateKey('test');
        $smilePay->setOptions($options);
        $smilePay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $options = [
            'number' => '100310089076',
            'amount' => '0.1',
            'orderId' => '201712280000006078',
            'paymentVendorId' => '1103',
            'username' => 'php1test',
            'notify_url' => 'http://pay.in-action.tw/',
            'merchant_extra' => ['HashIV' => 'test'],
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = 'http://sp17.smilepay.vip/temp/MD1712280956128.png';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $smilePay = new SmilePay();
        $smilePay->setContainer($this->container);
        $smilePay->setClient($this->client);
        $smilePay->setResponse($response);
        $smilePay->setPrivateKey('test');
        $smilePay->setOptions($options);
        $data = $smilePay->getVerifyData();

        $codeUrl = '<img src="http://sp17.smilepay.vip/temp/MD1712280956128.png"/>';

        $this->assertEmpty($data);
        $this->assertEquals($codeUrl, $smilePay->getHtml());
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $options = [
            'number' => '100310089076',
            'amount' => '0.1',
            'orderId' => '201712280000006078',
            'paymentVendorId' => '1104',
            'username' => 'php1test',
            'notify_url' => 'http://pay.in-action.tw/',
            'merchant_extra' => ['HashIV' => 'test'],
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = 'http://116.62.140.1:81/payapi/api/trade/qqh5?code=MEXDTI3NHn9yFaCyTFm';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $smilePay = new SmilePay();
        $smilePay->setContainer($this->container);
        $smilePay->setClient($this->client);
        $smilePay->setResponse($response);
        $smilePay->setPrivateKey('test');
        $smilePay->setOptions($options);
        $data = $smilePay->getVerifyData();

        $this->assertEquals('http://116.62.140.1:81/payapi/api/trade/qqh5?code=MEXDTI3NHn9yFaCyTFm', $data['post_url']);
        $this->assertEquals([], $data['params']);
    }

    /**
     * 測試網銀支付
     */
    public function testBankPay()
    {
        $options = [
            'number' => '100310089076',
            'amount' => '10',
            'orderId' => '201712280000006078',
            'paymentVendorId' => '1',
            'username' => 'php1test',
            'notify_url' => 'http://pay.in-action.tw/',
            'merchant_extra' => ['HashIV' => 'test'],
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $smilePay = new SmilePay();
        $smilePay->setPrivateKey('test');
        $smilePay->setOptions($options);
        $data = $smilePay->getVerifyData();

        $this->assertEquals($options['number'], $data['merchantId']);
        $this->assertEquals('Bank', $data['payMode']);
        $this->assertEquals($options['orderId'], $data['orderNo']);
        $this->assertSame('10.00', $data['orderAmount']);
        $this->assertSame($options['username'], $data['goods']);
        $this->assertEquals($options['notify_url'], $data['notifyUrl']);
        $this->assertEquals($options['notify_url'], $data['returnUrl']);
        $this->assertEquals('ICBC', $data['bank']);
        $this->assertEquals('', $data['memo']);
        $this->assertEquals('SHA2', $data['encodeType']);
        $this->assertEquals('BED678576062EEB49C30CC2A970FA6A444B8C0644A7EF01429623354E5E891C7', $data['signSHA2']);
    }

    /**
     * 測試銀聯在線
     */
    public function testYLPay()
    {
        $options = [
            'number' => '100310686803',
            'amount' => '200',
            'orderId' => '201805100000012013',
            'paymentVendorId' => '278',
            'username' => 'php1test',
            'notify_url' => 'http://pay.in-action.tw/',
            'merchant_extra' => ['HashIV' => 'test'],
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $smilePay = new SmilePay();
        $smilePay->setPrivateKey('test');
        $smilePay->setOptions($options);
        $data = $smilePay->getVerifyData();

        $this->assertEquals('100310686803', $data['merchantId']);
        $this->assertEquals('BankEX', $data['payMode']);
        $this->assertEquals('201805100000012013', $data['orderNo']);
        $this->assertSame('200.00', $data['orderAmount']);
        $this->assertSame('php1test', $data['goods']);
        $this->assertEquals('http://pay.in-action.tw/', $data['notifyUrl']);
        $this->assertEquals('http://pay.in-action.tw/', $data['returnUrl']);
        $this->assertEquals('', $data['bank']);
        $this->assertEquals('', $data['memo']);
        $this->assertEquals('SHA2', $data['encodeType']);
        $this->assertEquals('2AB91DF09A505D5F5D5A0EEB801F54D9AEDE87E991F47ACD01E54A51365E184B', $data['signSHA2']);
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

        $smilePay = new SmilePay();
        $smilePay->setPrivateKey('test');
        $smilePay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時沒有帶入MerchantExtra的情況
     */
    public function testReturnWithoutMerchantExtra()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $options = [
            'encodeType' => 'SHA2',
            'merchantId' => '100310089076',
            'orderAmount' => '10.00',
            'orderNo' => '201712280000006078',
            'payMode' => 'Bank',
            'tradeNo' => 'MD171228104720499832',
            'success' => 'Y',
            'signSHA2' => '7088A2034AAF40713D005D0AC5D6B7CEDA40DABE773B048B0802A2D1B68CD555',
            'merchant_extra' => [],
        ];

        $smilePay = new SmilePay();
        $smilePay->setPrivateKey('test');
        $smilePay->setOptions($options);
        $smilePay->verifyOrderPayment([]);
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
            'encodeType' => 'SHA2',
            'merchantId' => '100310089076',
            'orderAmount' => '10.00',
            'orderNo' => '201712280000006078',
            'payMode' => 'Bank',
            'tradeNo' => 'MD171228104720499832',
            'success' => 'Y',
            'merchant_extra' => ['HashIV' => 'test'],
        ];

        $smilePay = new SmilePay();
        $smilePay->setPrivateKey('test');
        $smilePay->setOptions($options);
        $smilePay->verifyOrderPayment([]);
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
            'encodeType' => 'SHA2',
            'merchantId' => '100310089076',
            'orderAmount' => '10.00',
            'orderNo' => '201712280000006078',
            'payMode' => 'Bank',
            'tradeNo' => 'MD171228104720499832',
            'success' => 'Y',
            'signSHA2' => '7088A2034AAF40713D005D0AC5D6B7CEDA40DABE773B048B0802A2D1B68CD555',
            'merchant_extra' => ['HashIV' => 'test'],
        ];

        $smilePay = new SmilePay();
        $smilePay->setPrivateKey('test');
        $smilePay->setOptions($options);
        $smilePay->verifyOrderPayment([]);
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
            'encodeType' => 'SHA2',
            'merchantId' => '100310089076',
            'orderAmount' => '10.00',
            'orderNo' => '201712280000006078',
            'payMode' => 'Bank',
            'tradeNo' => 'MD171228104720499832',
            'success' => 'F',
            'signSHA2' => '3DAA8033D383BB8D6EF731F75CB6266BE8E987531E1F70E8AE7D89B2D70ABF47',
            'merchant_extra' => ['HashIV' => 'test'],
        ];

        $smilePay = new SmilePay();
        $smilePay->setPrivateKey('test');
        $smilePay->setOptions($options);
        $smilePay->verifyOrderPayment([]);
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
            'encodeType' => 'SHA2',
            'merchantId' => '100310089076',
            'orderAmount' => '10.00',
            'orderNo' => '201712280000006078',
            'payMode' => 'Bank',
            'tradeNo' => 'MD171228104720499832',
            'success' => 'Y',
            'signSHA2' => '3DAA8033D383BB8D6EF731F75CB6266BE8E987531E1F70E8AE7D89B2D70ABF47',
            'merchant_extra' => ['HashIV' => 'test'],
        ];

        $entry = ['id' => '9453'];

        $smilePay = new SmilePay();
        $smilePay->setPrivateKey('test');
        $smilePay->setOptions($options);
        $smilePay->verifyOrderPayment($entry);
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
            'encodeType' => 'SHA2',
            'merchantId' => '100310089076',
            'orderAmount' => '10.00',
            'orderNo' => '201712280000006078',
            'payMode' => 'Bank',
            'tradeNo' => 'MD171228104720499832',
            'success' => 'Y',
            'signSHA2' => '3DAA8033D383BB8D6EF731F75CB6266BE8E987531E1F70E8AE7D89B2D70ABF47',
            'merchant_extra' => ['HashIV' => 'test'],
        ];

        $entry = [
            'id' => '201712280000006078',
            'amount' => '1',
        ];

        $smilePay = new SmilePay();
        $smilePay->setPrivateKey('test');
        $smilePay->setOptions($options);
        $smilePay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $options = [
            'encodeType' => 'SHA2',
            'merchantId' => '100310089076',
            'orderAmount' => '10.00',
            'orderNo' => '201712280000006078',
            'payMode' => 'Bank',
            'tradeNo' => 'MD171228104720499832',
            'success' => 'Y',
            'signSHA2' => '3DAA8033D383BB8D6EF731F75CB6266BE8E987531E1F70E8AE7D89B2D70ABF47',
            'merchant_extra' => ['HashIV' => 'test'],
        ];

        $entry = [
            'id' => '201712280000006078',
            'amount' => '10',
        ];

        $smilePay = new SmilePay();
        $smilePay->setPrivateKey('test');
        $smilePay->setOptions($options);
        $smilePay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $smilePay->getMsg());
    }
}
