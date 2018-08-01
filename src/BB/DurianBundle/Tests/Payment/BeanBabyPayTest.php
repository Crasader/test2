<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\BeanBabyPay;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class BeanBabyPayTest extends DurianTestCase
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

        $beanBabyPay = new BeanBabyPay();
        $beanBabyPay->getVerifyData();
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

        $beanBabyPay = new BeanBabyPay();
        $beanBabyPay->setPrivateKey('test');
        $beanBabyPay->getVerifyData();
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
            'number' => '9527',
            'orderId' => '20180206114612',
            'amount' => '1.00',
            'notify_url' => 'http://seafood.com/',
            'paymentVendorId' => '99',
            'username' => 'Seafood',
            'ip' => '127.0.0.1',
        ];

        $beanBabyPay = new BeanBabyPay();
        $beanBabyPay->setPrivateKey('test');
        $beanBabyPay->setOptions($sourceData);
        $beanBabyPay->getVerifyData();
    }

    /**
     * 測試QQH5時沒有帶入verify_url的情況
     */
    public function testQQHFivePayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => '9527',
            'orderId' => '20180206114612',
            'amount' => '1.00',
            'notify_url' => 'http://seafood.com/',
            'paymentVendorId' => '1104',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
            'username' => 'Seafood',
            'ip' => '127.0.0.1',
        ];

        $beanBabyPay = new BeanBabyPay();
        $beanBabyPay->setPrivateKey('test');
        $beanBabyPay->setOptions($sourceData);
        $beanBabyPay->getVerifyData();
    }

    /**
     * 測試加密未返回code
     */
    public function testGetEncodeNoReturnStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = ['msg' => '未成功'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '20180206114612',
            'amount' => '1.00',
            'notify_url' => 'http://seafood.com/',
            'paymentVendorId' => '1104',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.seafood.help.you',
            'username' => 'Seafood',
            'ip' => '127.0.0.1',
        ];

        $beanBabyPay = new BeanBabyPay();
        $beanBabyPay->setContainer($this->container);
        $beanBabyPay->setClient($this->client);
        $beanBabyPay->setResponse($response);
        $beanBabyPay->setPrivateKey('test');
        $beanBabyPay->setOptions($sourceData);
        $beanBabyPay->getVerifyData();
    }

    /**
     * 測試加密返回code不等於0000
     */
    public function testGetEncodeReturnCodeError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '商戶號不支持',
            180130
        );

        $result = [
            'code' => 'FAIL',
            'msg' => '商戶號不支持',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '20180206114612',
            'amount' => '1.00',
            'notify_url' => 'http://seafood.com/',
            'paymentVendorId' => '1104',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.seafood.help.you',
            'username' => 'Seafood',
            'ip' => '127.0.0.1',
        ];

        $beanBabyPay = new BeanBabyPay();
        $beanBabyPay->setContainer($this->container);
        $beanBabyPay->setClient($this->client);
        $beanBabyPay->setResponse($response);
        $beanBabyPay->setPrivateKey('test');
        $beanBabyPay->setOptions($sourceData);
        $beanBabyPay->getVerifyData();
    }

    /**
     * 測試加密未返回token_id
     */
    public function testGetEncodeNoReturnResultTokenId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'code' => '0000',
            'msg' => '交易成功',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '20180206114612',
            'amount' => '1.00',
            'notify_url' => 'http://seafood.com/',
            'paymentVendorId' => '1104',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.seafood.help.you',
            'username' => 'Seafood',
            'ip' => '127.0.0.1',
        ];

        $beanBabyPay = new BeanBabyPay();
        $beanBabyPay->setContainer($this->container);
        $beanBabyPay->setClient($this->client);
        $beanBabyPay->setResponse($response);
        $beanBabyPay->setPrivateKey('test');
        $beanBabyPay->setOptions($sourceData);
        $beanBabyPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $result = [
            'code' => '0000',
            'msg' => '成功',
            'data' => ['token_id' => 'http://seafood.pay.help.you'],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '20180206114612',
            'amount' => '1.00',
            'notify_url' => 'http://seafood.com/',
            'paymentVendorId' => '1104',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.seafood.help.you',
            'username' => 'Seafood',
            'ip' => '127.0.0.1',
        ];

        $beanBabyPay = new BeanBabyPay();
        $beanBabyPay->setContainer($this->container);
        $beanBabyPay->setClient($this->client);
        $beanBabyPay->setResponse($response);
        $beanBabyPay->setPrivateKey('test');
        $beanBabyPay->setOptions($sourceData);
        $requestData = $beanBabyPay->getVerifyData();

        $this->assertEquals('http://seafood.pay.help.you', $requestData['post_url']);
        $this->assertEmpty($requestData['params']);
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

        $beanBabyPay = new BeanBabyPay();
        $beanBabyPay->verifyOrderPayment([]);
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

        $beanBabyPay = new BeanBabyPay();
        $beanBabyPay->setPrivateKey('test');
        $beanBabyPay->verifyOrderPayment([]);
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
            'error' => '1',
            'message' => '支付成功',
            'parter' => '9527',
            'ptoid' => '20180206114612',
            'oid' => '0000000123456',
            'attach' => 'Seafood',
            'money' => '1.00',
            'stime' => '1517881874',
        ];

        $beanBabyPay = new BeanBabyPay();
        $beanBabyPay->setPrivateKey('test');
        $beanBabyPay->setOptions($sourceData);
        $beanBabyPay->verifyOrderPayment([]);
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
            'error' => '1',
            'message' => '支付成功',
            'parter' => '9527',
            'ptoid' => '20180206114612',
            'oid' => '0000000123456',
            'attach' => 'Seafood',
            'money' => '1.00',
            'stime' => '1517881874',
            'sign' => 'HappySeafoodDay'
        ];

        $beanBabyPay = new BeanBabyPay();
        $beanBabyPay->setPrivateKey('test');
        $beanBabyPay->setOptions($sourceData);
        $beanBabyPay->verifyOrderPayment([]);
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
            'error' => '99',
            'message' => '支付成功',
            'parter' => '9527',
            'ptoid' => '20180206114612',
            'oid' => '0000000123456',
            'attach' => 'Seafood',
            'money' => '1.00',
            'stime' => '1517881874',
            'sign' => '2131f1cce763af5bf09f66a2d2d8f3be'
        ];

        $beanBabyPay = new BeanBabyPay();
        $beanBabyPay->setPrivateKey('test');
        $beanBabyPay->setOptions($sourceData);
        $beanBabyPay->verifyOrderPayment([]);
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
            'error' => '1',
            'message' => '支付成功',
            'parter' => '9527',
            'ptoid' => '20180206114612',
            'oid' => '0000000123456',
            'attach' => 'Seafood',
            'money' => '1.00',
            'stime' => '1517881874',
            'sign' => '9ae9c55f2b5cf79924e62efc1cc50d05'
        ];

        $entry = ['id' => '201705220000000321'];

        $beanBabyPay = new BeanBabyPay();
        $beanBabyPay->setPrivateKey('test');
        $beanBabyPay->setOptions($sourceData);
        $beanBabyPay->verifyOrderPayment($entry);
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
            'error' => '1',
            'message' => '支付成功',
            'parter' => '9527',
            'ptoid' => '20180206114612',
            'oid' => '0000000123456',
            'attach' => 'Seafood',
            'money' => '1.00',
            'stime' => '1517881874',
            'sign' => '9ae9c55f2b5cf79924e62efc1cc50d05'
        ];

        $entry = [
            'id' => '20180206114612',
            'amount' => '11.00',
        ];

        $beanBabyPay = new BeanBabyPay();
        $beanBabyPay->setPrivateKey('test');
        $beanBabyPay->setOptions($sourceData);
        $beanBabyPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $sourceData = [
            'error' => '1',
            'message' => '支付成功',
            'parter' => '9527',
            'ptoid' => '20180206114612',
            'oid' => '0000000123456',
            'attach' => 'Seafood',
            'money' => '1.00',
            'stime' => '1517881874',
            'sign' => '9ae9c55f2b5cf79924e62efc1cc50d05'
        ];

        $entry = [
            'id' => '20180206114612',
            'amount' => '1.00',
        ];

        $beanBabyPay = new BeanBabyPay();
        $beanBabyPay->setPrivateKey('test');
        $beanBabyPay->setOptions($sourceData);
        $beanBabyPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $beanBabyPay->getMsg());
    }
}
