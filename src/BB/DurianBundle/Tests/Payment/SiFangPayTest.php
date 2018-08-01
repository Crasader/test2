<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\SiFangPay;
use Buzz\Message\Response;

class SiFangPayTest extends DurianTestCase
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
     * 測試支付時沒有私鑰
     */
    public function testPayWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $siFangPay = new SiFangPay();
        $siFangPay->getVerifyData();
    }

    /**
     * 測後支付時未指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $siFangPay = new SiFangPay();
        $siFangPay->setPrivateKey('test');
        $siFangPay->setOptions([]);
        $siFangPay->getVerifyData();
    }

    /**
     * 測試支付時帶入不支援的銀行
     */
    public function testPayWithUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $option = [
            'number' => '9453',
            'orderCreateDate' => '2018-01-23 10:00:00',
            'orderId' => '201801230000009453',
            'notify_url' => 'http://www.seafood.help/',
            'amount' => '1.9453',
            'username' => 'seafood',
            'paymentVendorId' => '9999',
        ];

        $siFangPay = new SiFangPay();
        $siFangPay->setPrivateKey('test');
        $siFangPay->setOptions($option);
        $siFangPay->getVerifyData();
    }

    /**
     * 測試二維支付時缺少verify_url
     */
    public function testPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $option = [
            'number' => '9453',
            'orderCreateDate' => '2018-01-23 10:00:00',
            'orderId' => '201801230000009453',
            'notify_url' => 'http://www.seafood.help/',
            'amount' => '1.9453',
            'username' => 'seafood',
            'paymentVendorId' => '1090',
            'verify_url' => '',
        ];

        $siFangPay = new SiFangPay();
        $siFangPay->setPrivateKey('test');
        $siFangPay->setOptions($option);
        $siFangPay->getVerifyData();
    }

    /**
     * 測試二維支付時缺少respCode
     */
    public function testPayReturnWithoutRespCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'respDesc' => '路由筛选失败',
        ];

        $response = new Response();
        $response->setContent(http_build_query($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $option = [
            'number' => '9453',
            'orderCreateDate' => '2018-01-23 10:00:00',
            'orderId' => '201801230000009453',
            'notify_url' => 'http://www.seafood.help/',
            'amount' => '1.9453',
            'username' => 'seafood',
            'paymentVendorId' => '1090',
            'verify_ip' => ['172.26.54.42', '172.26.54.42'],
            'verify_url' => 'http://www.seafood.help.you/',
        ];

        $siFangPay = new SiFangPay();
        $siFangPay->setPrivateKey('test');
        $siFangPay->setContainer($this->container);
        $siFangPay->setClient($this->client);
        $siFangPay->setResponse($response);
        $siFangPay->setOptions($option);
        $siFangPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回respCode不等於P0000
     */
    public function testPayReturnRespCodeNotEqualP000()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '路由筛选失败',
            180130
        );

        $result = [
            'respCode' => '9999',
            'respDesc' => '路由筛选失败',
        ];

        $response = new Response();
        $response->setContent(http_build_query($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $option = [
            'number' => '9453',
            'orderCreateDate' => '2018-01-23 10:00:00',
            'orderId' => '201801230000009453',
            'notify_url' => 'http://www.seafood.help/',
            'amount' => '1.9453',
            'username' => 'seafood',
            'paymentVendorId' => '1090',
            'verify_ip' => ['172.26.54.42', '172.26.54.42'],
            'verify_url' => 'http://www.seafood.help.you/',
        ];

        $siFangPay = new SiFangPay();
        $siFangPay->setPrivateKey('test');
        $siFangPay->setContainer($this->container);
        $siFangPay->setClient($this->client);
        $siFangPay->setResponse($response);
        $siFangPay->setOptions($option);
        $siFangPay->getVerifyData();
    }

    /**
     * 測試二維支付時缺少codeUrl
     */
    public function testPayReturnWithoutCodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'respCode' => 'P000',
            'respDesc' => '交易处理中',
        ];

        $response = new Response();
        $response->setContent(http_build_query($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $option = [
            'number' => '9453',
            'orderCreateDate' => '2018-01-23 10:00:00',
            'orderId' => '201801230000009453',
            'notify_url' => 'http://www.seafood.help/',
            'amount' => '1.9453',
            'username' => 'seafood',
            'paymentVendorId' => '1090',
            'verify_ip' => ['172.26.54.42', '172.26.54.42'],
            'verify_url' => 'http://www.seafood.help.you/',
        ];

        $siFangPay = new SiFangPay();
        $siFangPay->setPrivateKey('test');
        $siFangPay->setContainer($this->container);
        $siFangPay->setClient($this->client);
        $siFangPay->setResponse($response);
        $siFangPay->setOptions($option);
        $siFangPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testScanPay()
    {
        $result = [
            'respCode' => 'P000',
            'respDesc' => '交易处理中',
            'codeUrl' => 'weixin://wxpay/bizpayurl?pr=3GPPCAF',
        ];

        $response = new Response();
        $response->setContent(http_build_query($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $option = [
            'number' => '9453',
            'orderCreateDate' => '2018-01-23 10:00:00',
            'orderId' => '201801230000009453',
            'notify_url' => 'http://www.seafood.help/',
            'amount' => '1.9453',
            'username' => 'seafood',
            'paymentVendorId' => '1090',
            'verify_ip' => ['172.26.54.42', '172.26.54.42'],
            'verify_url' => 'http://www.seafood.help.you/',
        ];

        $siFangPay = new SiFangPay();
        $siFangPay->setPrivateKey('test');
        $siFangPay->setContainer($this->container);
        $siFangPay->setClient($this->client);
        $siFangPay->setResponse($response);
        $siFangPay->setOptions($option);
        $encodeData = $siFangPay->getVerifyData();

        $this->assertEmpty($encodeData);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=3GPPCAF', $siFangPay->getQrcode());
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $option = [
            'number' => '9453',
            'orderCreateDate' => '2018-01-23 10:00:00',
            'orderId' => '201801230000009453',
            'notify_url' => 'http://www.seafood.help/',
            'amount' => '1.94',
            'username' => 'seafood',
            'paymentVendorId' => '1088',
            'verify_ip' => ['172.26.54.42', '172.26.54.42'],
            'verify_url' => 'http://www.seafood.help.you/',
        ];

        $siFangPay = new SiFangPay();
        $siFangPay->setPrivateKey('test');
        $siFangPay->setOptions($option);
        $encodeData = $siFangPay->getVerifyData();

        $this->assertEquals('V4.0', $encodeData['version']);
        $this->assertEquals('quick', $encodeData['channel']);
        $this->assertEquals('01', $encodeData['transType']);
        $this->assertEquals('9453', $encodeData['merNo']);
        $this->assertEquals('20180123', $encodeData['orderDate']);
        $this->assertEquals('201801230000009453', $encodeData['orderNo']);
        $this->assertEquals('http://www.seafood.help/', $encodeData['returnUrl']);
        $this->assertEquals('http://www.seafood.help/', $encodeData['notifyUrl']);
        $this->assertEquals('194', $encodeData['amount']);
        $this->assertEquals('seafood', $encodeData['goodsInf']);
        $this->assertEquals('1', $encodeData['payType']);
        $this->assertEquals('775A609669B5A5B991E754F26168A8BA', $encodeData['signature']);
    }

    /**
     * 測試返回時沒有私鑰
     */
    public function testReturnWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $siFangPay = new SiFangPay();
        $siFangPay->verifyOrderpayment([]);
    }

    /**
     * 測試反回時未指定參數
     */
    public function testReturnWithNoParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $siFangPay = new SiFangPay();
        $siFangPay->setPrivateKey('test');
        $siFangPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少加密簽名
     */
    public function testReturnWithoutSignature()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $option = [
            'amount' => '194',
            'merNo' => '9453',
            'notifyUrl' => 'http://www.seafood.help/',
            'orderDate' => '20180123',
            'orderNo' => '201801230000009453',
            'payId' => '20180123100030',
            'respCode' => '0000',
            'respDesc' => '交易成功',
            'transType' => '01',
        ];

        $siFangPay = new SiFangPay();
        $siFangPay->setPrivateKey('test');
        $siFangPay->setOptions($option);
        $siFangPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時簽名驗證錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180134
        );

        $option = [
            'amount' => '194',
            'merNo' => '9453',
            'notifyUrl' => 'http://www.seafood.help/',
            'orderDate' => '20180123',
            'orderNo' => '201801230000009453',
            'payId' => '20180123100030',
            'respCode' => '0000',
            'respDesc' => '交易成功',
            'transType' => '01',
            'signature' => '9453',
        ];

        $siFangPay = new SiFangPay();
        $siFangPay->setPrivateKey('test');
        $siFangPay->setOptions($option);
        $siFangPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付失敗
     */
    public function testReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180135
        );

        $option = [
            'amount' => '194',
            'merNo' => '9453',
            'notifyUrl' => 'http://www.seafood.help/',
            'orderDate' => '20180123',
            'orderNo' => '201801230000009453',
            'payId' => '20180123100030',
            'respCode' => '9999',
            'respDesc' => '交易失敗',
            'transType' => '01',
            'signature' => '92EA287BE1DC36FC52DAD7D22C6659DB',
        ];

        $siFangPay = new SiFangPay();
        $siFangPay->setPrivateKey('test');
        $siFangPay->setOptions($option);
        $siFangPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時單號不正確
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $option = [
            'amount' => '194',
            'merNo' => '9453',
            'notifyUrl' => 'http://www.seafood.help/',
            'orderDate' => '20180123',
            'orderNo' => '201801230000009453',
            'payId' => '20180123100030',
            'respCode' => '0000',
            'respDesc' => '交易成功',
            'transType' => '01',
            'signature' => '9499A65BC9543299BAD9967C625ED984',
        ];

        $entry = ['id' => '201801230000009487'];

        $siFangPay = new SiFangPay();
        $siFangPay->setPrivateKey('test');
        $siFangPay->setOptions($option);
        $siFangPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時金額不正確
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $option = [
            'amount' => '194',
            'merNo' => '9453',
            'notifyUrl' => 'http://www.seafood.help/',
            'orderDate' => '20180123',
            'orderNo' => '201801230000009453',
            'payId' => '20180123100030',
            'respCode' => '0000',
            'respDesc' => '交易成功',
            'transType' => '01',
            'signature' => '9499A65BC9543299BAD9967C625ED984',
        ];

        $entry = [
            'id' => '201801230000009453',
            'amount' => '194',
        ];

        $siFangPay = new SiFangPay();
        $siFangPay->setPrivateKey('test');
        $siFangPay->setOptions($option);
        $siFangPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回支付成功
     */
    public function testReturnResultSuccess()
    {
        $option = [
            'amount' => '194',
            'merNo' => '9453',
            'notifyUrl' => 'http://www.seafood.help/',
            'orderDate' => '20180123',
            'orderNo' => '201801230000009453',
            'payId' => '20180123100030',
            'respCode' => '0000',
            'respDesc' => '交易成功',
            'transType' => '01',
            'signature' => '9499A65BC9543299BAD9967C625ED984',
        ];

        $entry = [
            'id' => '201801230000009453',
            'amount' => '1.94',
        ];

        $siFangPay = new SiFangPay();
        $siFangPay->setPrivateKey('test');
        $siFangPay->setOptions($option);
        $siFangPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $siFangPay->getMsg());
    }
}
