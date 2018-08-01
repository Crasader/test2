<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\NowtoPay;
use Buzz\Message\Response;

class NowtoPayTest extends DurianTestCase
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

        $nowtoPay = new NowtoPay();
        $nowtoPay->getVerifyData();
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

        $nowtoPay = new NowtoPay();
        $nowtoPay->setPrivateKey('test');
        $nowtoPay->setOptions($sourceData);
        $nowtoPay->getVerifyData();
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
            'number' => '17040',
            'paymentVendorId' => '7',
            'amount' => '2.00',
            'orderId' => '201703210000001931',
            'notify_url' => 'http://two123.comxa.com/',
        ];

        $nowtoPay = new NowtoPay();
        $nowtoPay->setPrivateKey('test');
        $nowtoPay->setOptions($sourceData);
        $nowtoPay->getVerifyData();
    }

    /**
     * 測試支付時postUrl為空
     */
    public function testPayWithoutPostUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = [
            'number' => '17040',
            'paymentVendorId' => '1090',
            'amount' => '2.00',
            'orderId' => '201703210000001931',
            'notify_url' => 'http://two123.comxa.com/',
            'postUrl' => '',
        ];

        $nowtoPay = new NowtoPay();
        $nowtoPay->setPrivateKey('test');
        $nowtoPay->setOptions($sourceData);
        $nowtoPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '17040',
            'paymentVendorId' => '1090',
            'amount' => '2.00',
            'orderId' => '201703210000001931',
            'notify_url' => 'http://two123.comxa.com/',
            'postUrl' => 'https://gateway.nowtopay.com/NowtoPay.html',
        ];

        $nowtoPay = new NowtoPay();
        $nowtoPay->setPrivateKey('test');
        $nowtoPay->setOptions($sourceData);
        $encodeData = $nowtoPay->getVerifyData();

        $actUrl = 'https://gateway.nowtopay.com/NowtoPay.html?partner=17040&banktype=MSWEIXIN&paymoney=2.00&ordernumb' .
            'er=201703210000001931&callbackurl=http://two123.comxa.com/&hrefbackurl=&attach=&isshow=1&sign=bf4f1a3230' .
            '12f2f4c7d861c81e4c8baa';

        $this->assertEquals($sourceData['number'], $encodeData['partner']);
        $this->assertEquals('MSWEIXIN', $encodeData['banktype']);
        $this->assertSame($sourceData['amount'], $encodeData['paymoney']);
        $this->assertEquals($sourceData['orderId'], $encodeData['ordernumber']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['callbackurl']);
        $this->assertEquals('', $encodeData['hrefbackurl']);
        $this->assertEquals('', $encodeData['attach']);
        $this->assertEquals('1', $encodeData['isshow']);
        $this->assertEquals('bf4f1a323012f2f4c7d861c81e4c8baa', $encodeData['sign']);
        $this->assertEquals($actUrl, $encodeData['act_url']);
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

        $nowtoPay = new NowtoPay();
        $nowtoPay->verifyOrderPayment([]);
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

        $nowtoPay = new NowtoPay();
        $nowtoPay->setPrivateKey('test');
        $nowtoPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'partner' => '17040',
            'ordernumber' => '201703090000001810',
            'orderstatus' => '1',
            'paymoney' => '2.0000',
            'sysnumber' => 'AK17040170321164718232',
            'attach' => '',
        ];

        $nowtoPay = new NowtoPay();
        $nowtoPay->setOptions($sourceData);
        $nowtoPay->setPrivateKey('test');
        $nowtoPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時sign簽名驗證錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $sourceData = [
            'partner' => '17040',
            'ordernumber' => '201703090000001810',
            'orderstatus' => '1',
            'paymoney' => '2.0000',
            'sysnumber' => 'AK17040170321164718232',
            'attach' => '',
            'sign' => 'cec460574962122c03973b04609b3cf5',
        ];

        $nowtoPay = new NowtoPay();
        $nowtoPay->setOptions($sourceData);
        $nowtoPay->setPrivateKey('test');
        $nowtoPay->verifyOrderPayment([]);
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
            'partner' => '17040',
            'ordernumber' => '201703090000001810',
            'orderstatus' => '0',
            'paymoney' => '2.0000',
            'sysnumber' => 'AK17040170321164718232',
            'attach' => '',
            'sign' => '18a1e22ddb368c5a82ae759cebfb0806',
        ];

        $nowtoPay = new NowtoPay();
        $nowtoPay->setOptions($sourceData);
        $nowtoPay->setPrivateKey('test');
        $nowtoPay->verifyOrderPayment([]);
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
            'partner' => '17040',
            'ordernumber' => '201703090000001810',
            'orderstatus' => '1',
            'paymoney' => '2.0000',
            'sysnumber' => 'AK17040170321164718232',
            'attach' => '',
            'sign' => '706b257dcf07eee219e1ccd9c9452caf',
        ];

        $entry = ['id' => '201703090000001811'];

        $nowtoPay = new NowtoPay();
        $nowtoPay->setOptions($sourceData);
        $nowtoPay->setPrivateKey('test');
        $nowtoPay->verifyOrderPayment($entry);
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
            'partner' => '17040',
            'ordernumber' => '201703090000001810',
            'orderstatus' => '1',
            'paymoney' => '2.0000',
            'sysnumber' => 'AK17040170321164718232',
            'attach' => '',
            'sign' => '706b257dcf07eee219e1ccd9c9452caf',
        ];

        $entry = [
            'id' => '201703090000001810',
            'amount' => '0.01',
        ];

        $nowtoPay = new NowtoPay();
        $nowtoPay->setOptions($sourceData);
        $nowtoPay->setPrivateKey('test');
        $nowtoPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'partner' => '17040',
            'ordernumber' => '201703090000001810',
            'orderstatus' => '1',
            'paymoney' => '2.0000',
            'sysnumber' => 'AK17040170321164718232',
            'attach' => '',
            'sign' => '706b257dcf07eee219e1ccd9c9452caf',
        ];

        $entry = [
            'id' => '201703090000001810',
            'amount' => '2',
        ];

        $nowtoPay = new NowtoPay();
        $nowtoPay->setOptions($sourceData);
        $nowtoPay->setPrivateKey('test');
        $nowtoPay->verifyOrderPayment($entry);

        $this->assertEquals('ok', $nowtoPay->getMsg());
    }
}
