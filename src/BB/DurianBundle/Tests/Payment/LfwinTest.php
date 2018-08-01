<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\PaymentBase;
use BB\DurianBundle\Payment\Lfwin;
use Buzz\Message\Response;

class LfwinTest extends DurianTestCase
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

        $mockCde = $this->getMockBuilder('BB\DurianBundle\Entity\CashDepositEntry')
            ->disableOriginalConstructor()
            ->setMethods(['setRefId'])
            ->getMock();
        $mockCde->expects($this->any())
            ->method('setRefId')
            ->willReturn($mockCde);

        $mockRepo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $mockRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn($mockCde);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository', 'flush'])
            ->getMock();
        $mockEm->expects($this->any(0))
            ->method('getRepository')
            ->willReturn($mockRepo);

        $mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();
        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

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

        $getMap = [
            ['durian.payment_logger', 1, $mockLogger],
            ['doctrine', 1, $mockDoctrine]
        ];

        $this->container->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        $this->client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
    }

    /**
     * 測試支付沒有帶入privateKey的情況
     */
    public function testPayWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $lfwin = new Lfwin();
        $lfwin->getVerifyData();
    }

    /**
     * 測試支付未指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $options = ['number' => ''];

        $lfwin = new Lfwin();
        $lfwin->setPrivateKey('abcdefg');
        $lfwin->setOptions($options);
        $lfwin->getVerifyData();
    }

    /**
     * 測試支付未带入支援银行
     */
    public function testPayButPaymentVendorIsNotSupportedByPaymentGateway()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'amount' => '100',
            'paymentVendorId' => '1',
        ];

        $lfwin = new Lfwin();
        $lfwin->setPrivateKey('abcdefg');
        $lfwin->setOptions($options);
        $lfwin->getVerifyData();
    }

    /**
     *  測試支付缺少商家額外的參數設定ClerkID
     */
    public function testPayWithoutMerchantExtraClerkID()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'amount' => '100',
            'paymentVendorId' => '1090',
            'merchant_extra' => []
        ];

        $lfwin = new Lfwin();
        $lfwin->setPrivateKey('test');
        $lfwin->setOptions($options);
        $lfwin->getVerifyData();
    }

    /**
     * 測試支付DES加密失敗
     */
    public function testPayButDESEncryptFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'DES encrypt failed',
            150180177
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'amount' => '100',
            'paymentVendorId' => '1090',
            'merchant_extra' => ['ClerkID' => '1']
        ];

        $lfwin = new Lfwin();
        $lfwin->setPrivateKey('1234567');
        $lfwin->setOptions($options);
        $lfwin->getVerifyData();
    }

    /**
     * 測試支付未代入verifyUrl
     */
    public function testPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'amount' => '100',
            'paymentVendorId' => '1090',
            'merchant_extra' => ['ClerkID' => '1'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $lfwin = new Lfwin();
        $lfwin->setPrivateKey('12345678');
        $lfwin->setOptions($options);
        $lfwin->getVerifyData();
    }

    /**
     * 測試支付返回DES解密失敗
     */
    public function testPayReturnDESDecryptFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'DES decrypt failed',
            150180175
        );

        $lfwin = new Lfwin();
        $lfwin->setContainer($this->container);
        $lfwin->setClient($this->client);

        $result = '';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $lfwin->setResponse($response);

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'amount' => '100',
            'paymentVendorId' => '1090',
            'merchant_extra' => ['ClerkID' => '1'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.lfwin.com',
        ];

        $lfwin->setPrivateKey('12345678');
        $lfwin->setOptions($options);
        $lfwin->getVerifyData();
    }

    /**
     * 測試支付沒有返回status
     */
    public function testPayReturnWithoutStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $lfwin = new Lfwin();
        $lfwin->setContainer($this->container);
        $lfwin->setClient($this->client);

        $result = 'qpjAZJQcFjc=';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $lfwin->setResponse($response);

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'amount' => '100',
            'paymentVendorId' => '1090',
            'merchant_extra' => ['ClerkID' => '1'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.lfwin.com',
        ];

        $lfwin->setPrivateKey('12345678');
        $lfwin->setOptions($options);
        $lfwin->getVerifyData();
    }

    /**
     * 測試支付返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '支付类型参数不正确！',
            180130
        );

        $lfwin = new Lfwin();
        $lfwin->setContainer($this->container);
        $lfwin->setClient($this->client);

        $result = '9Yg+SKNxOFO+hcrxUO/cIGYCptL5o4gOf7/1cfI1/GULFGeIVLVNIsU1uwtiCzay1C/V3/skJcR8Bcway' .
            'vGktzc13b1OlbJ+EGehU1KQxqAa8kn2YGdcWS4J5u8GZx/G';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $lfwin->setResponse($response);

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'amount' => '100',
            'paymentVendorId' => '1090',
            'merchant_extra' => ['ClerkID' => '1'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.lfwin.com',
        ];

        $lfwin->setPrivateKey('12345678');
        $lfwin->setOptions($options);
        $lfwin->getVerifyData();
    }

    /**
     * 測試支付沒有返回qrcode
     */
    public function testPayReturnWithoutQrcode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $lfwin = new Lfwin();
        $lfwin->setContainer($this->container);
        $lfwin->setClient($this->client);

        $result = '9Yg+SKNxOFMIe1t5RpTMpRm5dbHkPl5tkIvxVjrs0AMo3QPoe0Xz10fF9ZYxJ9i6UxpgH29Y7X4UbxxXJ' .
            '3M8iiht05B9YLKIwIIEa/Xkd21TcTnuAms4ChlCXSOsNFhY';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $lfwin->setResponse($response);

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'amount' => '100',
            'paymentVendorId' => '1090',
            'merchant_extra' => ['ClerkID' => '1'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.lfwin.com',
        ];

        $lfwin->setPrivateKey('12345678');
        $lfwin->setOptions($options);
        $lfwin->getVerifyData();
    }

    /**
     * 測試支付沒有返回orderid
     */
    public function testPayReturnWithoutOrderid()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $lfwin = new Lfwin();
        $lfwin->setContainer($this->container);
        $lfwin->setClient($this->client);

        $result = '9Yg+SKNxOFMIe1t5RpTMpRm5dbHkPl5tkIvxVjrs0AMo3QPoe0Xz10fF9ZYxJ9i6UxpgH29Y7X4UbxxXJ' .
            '3M8iiht05B9YLKIwIIEa/Xkd21TcTnuAms4Ch86rT7C/1Lq+yHDYtYW8rUl2OL3ZrGESjSouQb0PrWLUrG/JGhF' .
            'Swuc79/lrZ9dfv/ehl1AOqqY';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $lfwin->setResponse($response);

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'amount' => '100',
            'paymentVendorId' => '1090',
            'merchant_extra' => ['ClerkID' => '1'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.lfwin.com',
        ];

        $lfwin->setPrivateKey('12345678');
        $lfwin->setOptions($options);
        $lfwin->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $lfwin = new Lfwin();
        $lfwin->setContainer($this->container);
        $lfwin->setClient($this->client);

        $result = '9Yg+SKNxOFMIe1t5RpTMpRm5dbHkPl5tkIvxVjrs0AMo3QPoe0Xz10fF9ZYxJ9i6UxpgH29Y7X4UbxxXJ' .
            '3M8iiht05B9YLKIwIIEa/Xkd21TcTnuAms4Ch86rT7C/1Lq+yHDYtYW8rUl2OL3ZrGESjSouQb0PrWLUrG/JGhF' .
            'Swuc79/lrZ9dfjXwQYqRcZ71UvZyySzJ8UN9CFUR3D8K+iktv7n0clUQTJYESDA3c8eIjPighJ1B2w==';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $lfwin->setResponse($response);

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'amount' => '100',
            'paymentVendorId' => '1090',
            'merchant_extra' => ['ClerkID' => '1'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.lfwin.com',
        ];

        $lfwin->setPrivateKey('12345678');
        $lfwin->setOptions($options);
        $lfwin->setPayway(PaymentBase::PAYWAY_CASH);
        $encodeData = $lfwin->getVerifyData();

        $this->assertEmpty($encodeData);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=klLGk0o', $lfwin->getQrcode());
    }

    /**
     * 測試解密驗證時支付平台未指定返回參數
     */
    public function testVerifyWithoutResult()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $lfwin = new Lfwin();
        $lfwin->setOptions([]);
        $lfwin->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時支付失敗
     */
    public function testVerifyButPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $lfwin = new Lfwin();

        $sourceData = [
            'paystatus' => '0',
            'mch_orderid' => '201503160000002219',
            'paymoney' => '0.01',
            'orderid' => '123',
        ];

        $lfwin->setOptions($sourceData);
        $lfwin->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時單號不正確的情況
     */
    public function testVerifyButOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $lfwin = new Lfwin();

        $sourceData = [
            'paystatus' => '1',
            'mch_orderid' => '201503160000002219',
            'paymoney' => '0.01',
            'orderid' => '123',
        ];

        $entry = ['id' => '20140320000000012'];

        $lfwin->setOptions($sourceData);
        $lfwin->verifyOrderPayment($entry);
    }

    /**
     * 測試解密驗證時金額不正確的情況
     */
    public function testVerifyButOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $lfwin = new Lfwin();

        $sourceData = [
            'paystatus' => '1',
            'mch_orderid' => '201503160000002219',
            'paymoney' => '0.01',
            'orderid' => '123',
        ];

        $entry = [
            'id' => '201503160000002219',
            'amount' => '115.00',
        ];

        $lfwin->setOptions($sourceData);
        $lfwin->verifyOrderPayment($entry);
    }

    /**
     * 測試解密驗證時支付平台單號不正確的情況
     */
    public function testVerifyButRefIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Ref Id error',
            150180176
        );

        $lfwin = new Lfwin();

        $sourceData = [
            'paystatus' => '1',
            'mch_orderid' => '201503160000002219',
            'paymoney' => '0.01',
            'orderid' => '123',
        ];

        $entry = [
            'id' => '201503160000002219',
            'amount' => '0.01',
            'ref_id' => '456',
        ];

        $lfwin->setOptions($sourceData);
        $lfwin->verifyOrderPayment($entry);
    }

    /**
     * 測試解密驗證
     */
    public function testVerify()
    {
        $lfwin = new Lfwin();

        $sourceData = [
            'paystatus' => '1',
            'mch_orderid' => '201503160000002219',
            'paymoney' => '0.01',
            'orderid' => '123',
        ];

        $entry = [
            'id' => '201503160000002219',
            'amount' => '0.01',
            'ref_id' => '123',
        ];

        $lfwin->setOptions($sourceData);
        $lfwin->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $lfwin->getMsg());
    }

    /**
     * 測試訂單查詢沒有帶入privateKey的情況
     */
    public function testPaymentTrackingWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $lfwin = new Lfwin();
        $lfwin->paymentTracking();
    }

    /**
     * 測試訂單查詢未指定支付參數
     */
    public function testPaymentTrackingWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $lfwin = new Lfwin();
        $lfwin->setPrivateKey('abcdefg');
        $lfwin->setOptions([]);
        $lfwin->paymentTracking();
    }

    /**
     * 測試訂單查詢未代入verifyUrl
     */
    public function testPaymentTrackingWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '20130809',
            'ref_id' => '123',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $lfwin = new Lfwin();
        $lfwin->setPrivateKey('12345678');
        $lfwin->setOptions($options);
        $lfwin->paymentTracking();
    }

    /**
     * 測試訂單查詢返回DES解密失敗
     */
    public function testPaymentTrackingReturnDESDecryptFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'DES decrypt failed',
            150180175
        );

        $lfwin = new Lfwin();
        $lfwin->setContainer($this->container);
        $lfwin->setClient($this->client);

        $result = '';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $lfwin->setResponse($response);

        $options = [
            'number' => '20130809',
            'ref_id' => '123',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.lfwin.com',
        ];

        $lfwin->setPrivateKey('12345678');
        $lfwin->setOptions($options);
        $lfwin->paymentTracking();
    }

    /**
     * 測試訂單查詢沒有返回status
     */
    public function testPaymentTrackingReturnWithoutStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $lfwin = new Lfwin();
        $lfwin->setContainer($this->container);
        $lfwin->setClient($this->client);

        $result = 'qpjAZJQcFjc=';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $lfwin->setResponse($response);

        $options = [
            'number' => '20130809',
            'ref_id' => '123',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.lfwin.com',
        ];

        $lfwin->setPrivateKey('12345678');
        $lfwin->setOptions($options);
        $lfwin->paymentTracking();
    }

    /**
     * 測試訂單查詢返回提交失敗
     */
    public function testPaymentTrackingReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment tracking failed',
            180081
        );

        $lfwin = new Lfwin();
        $lfwin->setContainer($this->container);
        $lfwin->setClient($this->client);

        $result = '9Yg+SKNxOFO+hcrxUO/cIGYCptL5o4gOf7/1cfI1/GULFGeIVLVNIsU1uwtiCzay1C/V3/skJcR8Bcway' .
            'vGktzc13b1OlbJ+EGehU1KQxqAa8kn2YGdcWS4J5u8GZx/G';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $lfwin->setResponse($response);

        $options = [
            'number' => '20130809',
            'ref_id' => '123',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.lfwin.com',
        ];

        $lfwin->setPrivateKey('12345678');
        $lfwin->setOptions($options);
        $lfwin->paymentTracking();
    }

    /**
     * 測試訂單查詢沒有返回指定參數
     */
    public function testPaymentTrackingWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $lfwin = new Lfwin();
        $lfwin->setContainer($this->container);
        $lfwin->setClient($this->client);

        $result = '9Yg+SKNxOFMIe1t5RpTMpRm5dbHkPl5tkIvxVjrs0AMo3QPoe0Xz10fF9ZYxJ9i6UxpgH29Y7X4UbxxXJ' .
            '3M8iiht05B9YLKIwIIEa/Xkd21TcTnuAms4Ch86rT7C/1Lq+yHDYtYW8rUl2OL3ZrGESjSouQb0PrWLUrG/JGhF' .
            'Swuc79/lrZ9dfv/ehl1AOqqY';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $lfwin->setResponse($response);

        $options = [
            'number' => '20130809',
            'ref_id' => '123',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.lfwin.com',
        ];

        $lfwin->setPrivateKey('12345678');
        $lfwin->setOptions($options);
        $lfwin->paymentTracking();
    }

    /**
     * 測試訂單查詢結果支付失敗
     */
    public function testPaymentTrackingReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $lfwin = new Lfwin();
        $lfwin->setContainer($this->container);
        $lfwin->setClient($this->client);

        $result = '9Yg+SKNxOFMIe1t5RpTMpRm5dbHkPl5tkIvxVjrs0AMo3QPoe0Xz10fF9ZYxJ9i6UxpgH29Y7X4UbxxXJ' .
            '3M8iiht05B9YLKIwIIEa/Xkd21TcTnuAms4Cu0xP+O6eFMtieIKaQ0Nvz5oXMbttaUN3DDO9TWJ5TrUc0QuyGOp' .
            'qcxpPrauSGfUoeAIE8NBcvyur9+w/eGOx4k+KRnAb2jiBj03T1W9dcCS';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $lfwin->setResponse($response);

        $options = [
            'number' => '20130809',
            'ref_id' => '123',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.lfwin.com',
        ];

        $lfwin->setPrivateKey('12345678');
        $lfwin->setOptions($options);
        $lfwin->paymentTracking();
    }

    /**
     * 測試訂單查詢結果單號不正確的情況
     */
    public function testPaymentTrackingReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $lfwin = new Lfwin();
        $lfwin->setContainer($this->container);
        $lfwin->setClient($this->client);

        $result = '9Yg+SKNxOFMIe1t5RpTMpRm5dbHkPl5tkIvxVjrs0AMo3QPoe0Xz10fF9ZYxJ9i6UxpgH29Y7X4UbxxXJ' .
            '3M8iiht05B9YLKIwIIEa/Xkd21TcTnuAms4Cu0xP+O6eFMtieIKaQ0Nvz58+wBsFGb33oGf73Syd0KZs3qXy2Gm' .
            'HmixIopYahw4PHRxXHQ7vqlKteuye9h1FDLO35r237nF6B1jwXzDWfAf';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $lfwin->setResponse($response);

        $options = [
            'number' => '20130809',
            'ref_id' => '123',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.lfwin.com',
            'orderId' => '201503160000002210',
        ];

        $lfwin->setPrivateKey('12345678');
        $lfwin->setOptions($options);
        $lfwin->paymentTracking();
    }

    /**
     * 測試訂單查詢結果金額不正確的情況
     */
    public function testPaymentTrackingReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $lfwin = new Lfwin();
        $lfwin->setContainer($this->container);
        $lfwin->setClient($this->client);

        $result = '9Yg+SKNxOFMIe1t5RpTMpRm5dbHkPl5tkIvxVjrs0AMo3QPoe0Xz10fF9ZYxJ9i6UxpgH29Y7X4UbxxXJ' .
            '3M8iiht05B9YLKIwIIEa/Xkd21TcTnuAms4Cu0xP+O6eFMtieIKaQ0Nvz58+wBsFGb33oGf73Syd0KZs3qXy2Gm' .
            'HmixIopYahw4PHRxXHQ7vqlKteuye9h1FDLO35r237nF6B1jwXzDWfAf';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $lfwin->setResponse($response);

        $options = [
            'number' => '20130809',
            'ref_id' => '123',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.lfwin.com',
            'orderId' => '201503160000002219',
            'amount' => '100'
        ];

        $lfwin->setPrivateKey('12345678');
        $lfwin->setOptions($options);
        $lfwin->paymentTracking();
    }

    /**
     * 測試訂單查詢
     */
    public function testPaymentTracking()
    {
        $lfwin = new Lfwin();
        $lfwin->setContainer($this->container);
        $lfwin->setClient($this->client);

        $result = '9Yg+SKNxOFMIe1t5RpTMpRm5dbHkPl5tkIvxVjrs0AMo3QPoe0Xz10fF9ZYxJ9i6UxpgH29Y7X4UbxxXJ' .
            '3M8iiht05B9YLKIwIIEa/Xkd21TcTnuAms4Cu0xP+O6eFMtieIKaQ0Nvz58+wBsFGb33oGf73Syd0KZs3qXy2Gm' .
            'HmixIopYahw4PHRxXHQ7vqlKteuye9h1FDLO35r237nF6B1jwXzDWfAf';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $lfwin->setResponse($response);

        $options = [
            'number' => '20130809',
            'ref_id' => '123',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.lfwin.com',
            'orderId' => '201503160000002219',
            'amount' => '0.01'
        ];

        $lfwin->setPrivateKey('12345678');
        $lfwin->setOptions($options);
        $lfwin->paymentTracking();
    }
}
