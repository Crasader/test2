<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YunPay;
use Buzz\Message\Response;

class YunPayTest extends DurianTestCase
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

        $yunPay = new YunPay();
        $yunPay->getVerifyData();
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

        $yunPay = new YunPay();
        $yunPay->setPrivateKey('test');
        $yunPay->getVerifyData();
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
            'number' => '201705230000',
            'orderId' => '201705230000002219',
            'amount' => '100',
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '99',
            'orderCreateDate' => '2017-05-22 21:25:29',
            'username' => 'php1test',
        ];

        $yunPay = new YunPay();
        $yunPay->setPrivateKey('test');
        $yunPay->setOptions($sourceData);
        $yunPay->getVerifyData();
    }

    /**
     * 測試支付缺少商家額外的參數設定platformID
     */
    public function testPayWithoutMerchantExtraPlatformID()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $sourceData = [
            'number' => '201705230000',
            'orderId' => '201705230000002219',
            'amount' => '100',
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1',
            'orderCreateDate' => '2017-05-22 21:25:29',
            'merchant_extra' => [],
            'username' => 'php1test',
        ];

        $yunPay = new YunPay();
        $yunPay->setPrivateKey('test');
        $yunPay->setOptions($sourceData);
        $yunPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '201705230000',
            'orderId' => '201705230000002219',
            'amount' => '100.00',
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1',
            'orderCreateDate' => '2017-05-22 21:25:29',
            'merchant_extra' => ['platformID' => '201705230000'],
            'username' => 'php1test',
        ];

        $yunPay = new YunPay();
        $yunPay->setPrivateKey('test');
        $yunPay->setOptions($sourceData);
        $requestData = $yunPay->getVerifyData();

        $this->assertEquals('201705230000', $requestData['merchNo']);
        $this->assertEquals('201705230000', $requestData['platformID']);
        $this->assertEquals('201705230000002219', $requestData['orderNo']);
        $this->assertEquals('100.00', $requestData['amt']);
        $this->assertEquals('http://154.58.78.54/', $requestData['merchUrl']);
        $this->assertEquals('ICBC', $requestData['bankCode']);
        $this->assertEquals('1', $requestData['choosePayType']);
        $this->assertEquals('4660f8e5ddae7beb3b0e4d143cff7a34', $requestData['signMsg']);
    }

    /**
     * 測試支付銀行為微信二維
     */
    public function testPayWithWeiXinQRCode()
    {
        $sourceData = [
            'number' => '201705230000',
            'orderId' => '201705230000002219',
            'amount' => '100.00',
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1090',
            'orderCreateDate' => '2017-05-22 21:25:29',
            'merchant_extra' => ['platformID' => '201705230000'],
            'username' => 'php1test',
        ];

        $yunPay = new YunPay();
        $yunPay->setPrivateKey('test');
        $yunPay->setOptions($sourceData);
        $requestData = $yunPay->getVerifyData();

        $this->assertEquals('201705230000', $requestData['merchNo']);
        $this->assertEquals('201705230000', $requestData['platformID']);
        $this->assertEquals('201705230000002219', $requestData['orderNo']);
        $this->assertEquals('100.00', $requestData['amt']);
        $this->assertEquals('http://154.58.78.54/', $requestData['merchUrl']);
        $this->assertEquals('', $requestData['bankCode']);
        $this->assertEquals('5', $requestData['choosePayType']);
        $this->assertEquals('4660f8e5ddae7beb3b0e4d143cff7a34', $requestData['signMsg']);
    }

    /**
     * 測試支付時缺少私鑰
     */
    public function testReturnWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $yunPay = new YunPay();
        $yunPay->verifyOrderPayment([]);
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

        $yunPay = new YunPay();
        $yunPay->setPrivateKey('test');
        $yunPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少加密簽名
     */
    public function testReturnWithoutSignMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20170522151438',
            'tradeAmt' => '100.00',
            'merchNo' => 'acctest',
            'merchParam' => '',
            'orderNo' => '201705220000000123',
            'tradeDate' => '20170522',
            'accNo' => '722216',
            'accDate' => '20150316',
            'orderStatus' => '1',
        ];

        $yunPay = new YunPay();
        $yunPay->setPrivateKey('test');
        $yunPay->setOptions($sourceData);
        $yunPay->verifyOrderPayment([]);
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
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20170522151438',
            'tradeAmt' => '100.00',
            'merchNo' => 'acctest',
            'merchParam' => '',
            'orderNo' => '201705220000000123',
            'tradeDate' => '20170522',
            'accNo' => '722216',
            'accDate' => '20150316',
            'orderStatus' => '1',
            'signMsg' => 'acctest',
        ];

        $yunPay = new YunPay();
        $yunPay->setPrivateKey('test');
        $yunPay->setOptions($sourceData);
        $yunPay->verifyOrderPayment([]);
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
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20170522151438',
            'tradeAmt' => '100.00',
            'merchNo' => 'acctest',
            'merchParam' => '',
            'orderNo' => '201705220000000123',
            'tradeDate' => '20170522',
            'accNo' => '722216',
            'accDate' => '20170522',
            'orderStatus' => '0',
            'signMsg' => 'e4456578eaa114cab1c1942ab0758c1d',
        ];

        $yunPay = new YunPay();
        $yunPay->setPrivateKey('test');
        $yunPay->setOptions($sourceData);
        $yunPay->verifyOrderPayment([]);
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
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20170522151438',
            'tradeAmt' => '100.00',
            'merchNo' => 'acctest',
            'merchParam' => '',
            'orderNo' => '201705220000000123',
            'tradeDate' => '20170522',
            'accNo' => '722216',
            'accDate' => '20170522',
            'orderStatus' => '1',
            'signMsg' => 'c71eaf16b683c42ca602448b95f59e21',
        ];

        $entry = ['id' => '201705220000000321'];

        $yunPay = new YunPay();
        $yunPay->setPrivateKey('test');
        $yunPay->setOptions($sourceData);
        $yunPay->verifyOrderPayment($entry);
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
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20170522151438',
            'tradeAmt' => '100.00',
            'merchNo' => 'acctest',
            'merchParam' => '',
            'orderNo' => '201705220000000123',
            'tradeDate' => '20170522',
            'accNo' => '722216',
            'accDate' => '20170522',
            'orderStatus' => '1',
            'signMsg' => 'c71eaf16b683c42ca602448b95f59e21',
        ];

        $entry = [
            'id' => '201705220000000123',
            'amount' => '10.00',
        ];

        $yunPay = new YunPay();
        $yunPay->setPrivateKey('test');
        $yunPay->setOptions($sourceData);
        $yunPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $sourceData = [
            'apiName' => 'PAY_RESULT_NOTIFY',
            'notifyTime' => '20170522151438',
            'tradeAmt' => '100.00',
            'merchNo' => 'acctest',
            'merchParam' => '',
            'orderNo' => '201705220000000123',
            'tradeDate' => '20170522',
            'accNo' => '722216',
            'accDate' => '20170522',
            'orderStatus' => '1',
            'signMsg' => 'c71eaf16b683c42ca602448b95f59e21',
        ];

        $entry = [
            'id' => '201705220000000123',
            'amount' => '100.00',
        ];

        $yunPay = new YunPay();
        $yunPay->setPrivateKey('test');
        $yunPay->setOptions($sourceData);
        $yunPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $yunPay->getMsg());
    }
}
