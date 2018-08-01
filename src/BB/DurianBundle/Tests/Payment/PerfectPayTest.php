<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\PerfectPay;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class PerfectPayTest extends DurianTestCase
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

        $perfectPay = new PerfectPay();
        $perfectPay->getVerifyData();
    }

    /**
     * 測試支付時沒有指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\Durianbundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $perfectPay = new PerfectPay();
        $perfectPay->setPrivateKey('test');
        $perfectPay->getVerifyData();
    }

    /**
     * 測試支付時帶入不支援銀行
     */
    public function testPayWithNoSupportBank()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '9527',
            'orderId' => '201710310000009453',
            'amount' => '1.00',
            'username' => 'seafood',
            'notify_url' => 'http://orz.com.tw/',
            'paymentVendorId' => '999',
            'ip' => '127.0.0.1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'http://seafood.eat.good',
        ];

        $perfectPay = new PerfectPay();
        $perfectPay->setPrivateKey('test');
        $perfectPay->setOptions($sourceData);
        $perfectPay->getVerifyData();
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
            'number' => '9527',
            'orderId' => '201710310000009453',
            'amount' => '1.00',
            'username' => 'seafood',
            'notify_url' => 'http://orz.com.tw/',
            'paymentVendorId' => '1090',
            'ip' => '127.0.0.1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $perfectPay = new PerfectPay();
        $perfectPay->setPrivateKey('test');
        $perfectPay->setOptions($sourceData);
        $perfectPay->getVerifyData();
    }

    /**
     * 測試支付時返回缺少r1_Code
     */
    public function testPayReturnWithoutR1Code()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'r0_Cmd' => 'Buy',
            'p1_MerId' => '9527',
            'r2_TrxId' => 'ORDE2017071011261700004895BP',
            'r3_PayInfo' => 'weixin://wxpay/bizpayurl?pr=nfHFGJ7',
            'r4_Amt' => '0.01',
            'r7_Desc' => '',
            'hmac' => 'ILikeSeafood',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201710310000009453',
            'amount' => '1.00',
            'username' => 'seafood',
            'notify_url' => 'http://orz.com.tw/',
            'paymentVendorId' => '1090',
            'ip' => '127.0.0.1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'http://seafood.help.you',
        ];

        $perfectPay = new PerfectPay();
        $perfectPay->setPrivateKey('test');
        $perfectPay->setContainer($this->container);
        $perfectPay->setClient($this->client);
        $perfectPay->setResponse($response);
        $perfectPay->setOptions($sourceData);
        $perfectPay->getVerifyData();
    }

    /**
     * 測試支付時返回r1_Code不等於1
     */
    public function testPayReturnR1CodeNotEqualOne()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $result = [
            'r0_Cmd' => 'Buy',
            'p1_MerId' => '9527',
            'r1_Code' => '99',
            'r2_TrxId' => 'ORDE2017071011261700004895BP',
            'r3_PayInfo' => 'weixin://wxpay/bizpayurl?pr=nfHFGJ7',
            'r4_Amt' => '0.01',
            'r7_Desc' => '',
            'hmac' => 'ILikeSeafood',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201710310000009453',
            'amount' => '1.00',
            'username' => 'seafood',
            'notify_url' => 'http://orz.com.tw/',
            'paymentVendorId' => '1090',
            'ip' => '127.0.0.1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'http://seafood.help.you',
        ];

        $perfectPay = new PerfectPay();
        $perfectPay->setPrivateKey('test');
        $perfectPay->setContainer($this->container);
        $perfectPay->setClient($this->client);
        $perfectPay->setResponse($response);
        $perfectPay->setOptions($sourceData);
        $perfectPay->getVerifyData();
    }

    /**
     * 測試支付時返回缺少參數r3_PayInfo
     */
    public function testPayReturnWithoutR3PayInfo()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'r0_Cmd' => 'Buy',
            'p1_MerId' => '9527',
            'r1_Code' => '1',
            'r2_TrxId' => 'ORDE2017071011261700004895BP',
            'r4_Amt' => '0.01',
            'r7_Desc' => '',
            'hmac' => 'ILikeSeafood',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201710310000009453',
            'amount' => '1.00',
            'username' => 'seafood',
            'notify_url' => 'http://orz.com.tw/',
            'paymentVendorId' => '1090',
            'ip' => '127.0.0.1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'http://seafood.help.you',
        ];

        $perfectPay = new PerfectPay();
        $perfectPay->setPrivateKey('test');
        $perfectPay->setContainer($this->container);
        $perfectPay->setClient($this->client);
        $perfectPay->setResponse($response);
        $perfectPay->setOptions($sourceData);
        $perfectPay->getVerifyData();
    }

    /**
     * 測試支付成功
     */
    public function testPaySuccess()
    {
        $result = [
            'r0_Cmd' => 'Buy',
            'p1_MerId' => '9527',
            'r1_Code' => '1',
            'r2_TrxId' => 'ORDE2017071011261700004895BP',
            'r3_PayInfo' => 'weixin://wxpay/bizpayurl?pr=nfHFGJ7',
            'r4_Amt' => '0.01',
            'r7_Desc' => '',
            'hmac' => 'ILikeSeafood',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201710310000009453',
            'amount' => '1.00',
            'username' => 'seafood',
            'notify_url' => 'http://orz.com.tw/',
            'paymentVendorId' => '1090',
            'ip' => '127.0.0.1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'http://seafood.help.you',
        ];

        $perfectPay = new PerfectPay();
        $perfectPay->setPrivateKey('test');
        $perfectPay->setContainer($this->container);
        $perfectPay->setClient($this->client);
        $perfectPay->setResponse($response);
        $perfectPay->setOptions($sourceData);
        $encodeData = $perfectPay->getVerifyData();

        $this->assertEmpty($encodeData);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=nfHFGJ7', $perfectPay->getQrcode());
    }

    /**
     * 測試加密時PrivateKey長度超過64
     */
    public function testGetEncodeDataWithPrivateKeyLengthOverSixtyFour()
    {
        $result = [
            'r0_Cmd' => 'Buy',
            'p1_MerId' => '9527',
            'r1_Code' => '1',
            'r2_TrxId' => 'ORDE2017071011261700004895BP',
            'r3_PayInfo' => 'weixin://wxpay/bizpayurl?pr=nfHFGJ7',
            'r4_Amt' => '0.01',
            'r7_Desc' => '',
            'hmac' => 'ILikeSeafood',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '201710310000009453',
            'amount' => '1.00',
            'username' => 'seafood',
            'notify_url' => 'http://orz.com.tw/',
            'paymentVendorId' => '1090',
            'ip' => '127.0.0.1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'http://seafood.help.you',
        ];

        $perfectPay = new PerfectPay();
        $perfectPay->setPrivateKey('sjkdflksdjflksdjfklsjdlfkjsdlkfjklsdfjddddddlksdjflksdjflkjsdlkfj');
        $perfectPay->setContainer($this->container);
        $perfectPay->setClient($this->client);
        $perfectPay->setResponse($response);
        $perfectPay->setOptions($sourceData);
        $perfectPay->getVerifyData();
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

        $perfectPay = new PerfectPay();
        $perfectPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未指定返回參數
     */
    public function testReturnWithNoParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $perfectPay = new PerfectPay();
        $perfectPay->setPrivateKey('test');
        $perfectPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少hmac
     */
    public function testReturnWithoutHmac()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'p1_MerId' => 'CHANG1507795279453',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => 'ORDE2017103194531100008523AW',
            'r3_Amt' => '0.01',
            'r4_Cur' => 'CNY',
            'r5_Pid' => 'seafood',
            'r6_Order' => '201710310000009453',
            'r8_MP' => '',
            'r9_BType' => '2',
            'ro_BankOrderId' => '',
            'rp_PayDate' => '',
        ];

        $perfectPay = new PerfectPay();
        $perfectPay->setPrivateKey('test');
        $perfectPay->setOptions($sourceData);
        $perfectPay->verifyOrderPayment([]);
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
            'p1_MerId' => 'CHANG1507795279453',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => 'ORDE2017103194531100008523AW',
            'r3_Amt' => '0.01',
            'r4_Cur' => 'CNY',
            'r5_Pid' => 'seafood',
            'r6_Order' => '201710310000009453',
            'r8_MP' => '',
            'r9_BType' => '2',
            'ro_BankOrderId' => '',
            'rp_PayDate' => '',
            'hmac' => 'ThisIsASeafoodWorld',
        ];

        $perfectPay = new PerfectPay();
        $perfectPay->setPrivateKey('test');
        $perfectPay->setOptions($sourceData);
        $perfectPay->verifyOrderPayment([]);
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
            'p1_MerId' => 'CHANG1507795279453',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '999',
            'r2_TrxId' => 'ORDE2017103194531100008523AW',
            'r3_Amt' => '0.01',
            'r4_Cur' => 'CNY',
            'r5_Pid' => 'seafood',
            'r6_Order' => '201710310000009453',
            'r8_MP' => '',
            'r9_BType' => '2',
            'ro_BankOrderId' => '',
            'rp_PayDate' => '',
            'hmac' => 'aa562e0bb9f2d80e310d6c715bc21f74',
        ];

        $perfectPay = new PerfectPay();
        $perfectPay->setPrivateKey('test');
        $perfectPay->setOptions($sourceData);
        $perfectPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果訂單號錯誤
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'p1_MerId' => 'CHANG1507795279453',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => 'ORDE2017103194531100008523AW',
            'r3_Amt' => '0.01',
            'r4_Cur' => 'CNY',
            'r5_Pid' => 'seafood',
            'r6_Order' => '201710310000009453',
            'r8_MP' => '',
            'r9_BType' => '2',
            'ro_BankOrderId' => '',
            'rp_PayDate' => '',
            'hmac' => '06da54f26374189493102a9bd7a5aac4',
        ];

        $entry = ['id' => '201710310000009454'];

        $perfectPay = new PerfectPay();
        $perfectPay->setPrivateKey('test');
        $perfectPay->setOptions($sourceData);
        $perfectPay->verifyOrderPayment($entry);
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
            'p1_MerId' => 'CHANG1507795279453',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => 'ORDE2017103194531100008523AW',
            'r3_Amt' => '0.01',
            'r4_Cur' => 'CNY',
            'r5_Pid' => 'seafood',
            'r6_Order' => '201710310000009453',
            'r8_MP' => '',
            'r9_BType' => '2',
            'ro_BankOrderId' => '',
            'rp_PayDate' => '',
            'hmac' => '06da54f26374189493102a9bd7a5aac4',
        ];

        $entry = [
            'id' => '201710310000009453',
            'amount' => '2.0000',
        ];

        $perfectPay = new PerfectPay();
        $perfectPay->setPrivateKey('test');
        $perfectPay->setOptions($sourceData);
        $perfectPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付認證成功
     */
    public function testReturnOrder()
    {
        $sourceData = [
            'p1_MerId' => 'CHANG1507795279453',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => 'ORDE2017103194531100008523AW',
            'r3_Amt' => '0.01',
            'r4_Cur' => 'CNY',
            'r5_Pid' => 'seafood',
            'r6_Order' => '201710310000009453',
            'r8_MP' => '',
            'r9_BType' => '2',
            'ro_BankOrderId' => '',
            'rp_PayDate' => '',
            'hmac' => '06da54f26374189493102a9bd7a5aac4',
        ];

        $entry = [
            'id' => '201710310000009453',
            'amount' => '0.01',
        ];

        $perfectPay = new PerfectPay();
        $perfectPay->setPrivateKey('test');
        $perfectPay->setOptions($sourceData);
        $perfectPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $perfectPay->getMsg());
    }
}
