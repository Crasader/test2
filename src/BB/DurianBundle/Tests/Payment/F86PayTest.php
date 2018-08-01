<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\F86Pay;
use Buzz\Message\Response;

class F86PayTest extends DurianTestCase
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

        $f86Pay = new F86Pay();
        $f86Pay->getVerifyData();
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

        $f86Pay = new F86Pay();
        $f86Pay->setPrivateKey('test');
        $f86Pay->getVerifyData();
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
            'number' => 'GSTZ4015FNXV',
            'amount' => '100.00',
            'notify_url' => 'http://pay.test/',
            'orderId' => '201712040000005945',
            'username' => 'test',
            'paymentVendorId' => '9999',
            'merchant_extra' => [],
        ];

        $f86Pay = new F86Pay();
        $f86Pay->setPrivateKey('test');
        $f86Pay->setOptions($options);
        $f86Pay->getVerifyData();
    }

    /**
     * 測試支付時未指定商家附加設定值
     */
    public function testPayWithNoMerchantExtraValueSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $options = [
            'number' => 'GSTZ4015FNXV',
            'amount' => '100.00',
            'notify_url' => 'http://pay.test/',
            'orderId' => '201712040000005945',
            'username' => 'test',
            'paymentVendorId' => '1090',
            'merchant_extra' => [],
        ];

        $f86Pay = new F86Pay();
        $f86Pay->setPrivateKey('test');
        $f86Pay->setOptions($options);
        $f86Pay->getVerifyData();
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

        $merchantExtra = [
            'merchantCerNo' => '12345789',
        ];

        $options = [
            'number' => 'GSTZ4015FNXV',
            'amount' => '100.00',
            'notify_url' => 'http://pay.test/',
            'orderId' => '201712040000005945',
            'username' => 'test',
            'paymentVendorId' => '1090',
            'merchant_extra' => $merchantExtra,
            'verify_url' => '',
            'postUrl' => 'http://gw.f86pay.com/',
        ];

        $f86Pay = new F86Pay();
        $f86Pay->setPrivateKey('test');
        $f86Pay->setOptions($options);
        $f86Pay->getVerifyData();
    }

    /**
     * 測試支付時支付平台連線異常
     */
    public function testPayReturnPaymentGatewayConnectionError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment Gateway connection failure',
            180088
        );

        $exception = new \Exception('Timed out', 0);
        $this->client->expects($this->any())
            ->method('send')
            ->willThrowException($exception);

        $merchantExtra = [
            'merchantCerNo' => '12345789',
        ];

        $options = [
            'number' => 'GSTZ4015FNXV',
            'amount' => '100.00',
            'notify_url' => 'http://pay.test/',
            'orderId' => '201712040000005945',
            'username' => 'test',
            'paymentVendorId' => '1090',
            'merchant_extra' => $merchantExtra,
            'verify_url' => 'payment.http.duolabao',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'http://gw.f86pay.com/',
        ];

        $f86Pay = new F86Pay();
        $f86Pay->setContainer($this->container);
        $f86Pay->setClient($this->client);
        $f86Pay->setPrivateKey('test');
        $f86Pay->setOptions($options);
        $f86Pay->getVerifyData();
    }

    /**
     * 測試支付時返回statusCode不為200
     */
    public function testPayReturnStatusCodeNot200()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment Gateway connection failure',
            180088
        );

        $merchantExtra = [
            'merchantCerNo' => '12345789',
        ];

        $options = [
            'number' => 'GSTZ4015FNXV',
            'amount' => '100.00',
            'notify_url' => 'http://pay.test/',
            'orderId' => '201712040000005945',
            'username' => 'test',
            'paymentVendorId' => '1090',
            'merchant_extra' => $merchantExtra,
            'verify_url' => 'payment.http.duolabao',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'http://gw.f86pay.com/',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 500 ');
        $response->addHeader('Content-Type:text/html;');

        $f86Pay = new F86Pay();
        $f86Pay->setContainer($this->container);
        $f86Pay->setClient($this->client);
        $f86Pay->setResponse($response);
        $f86Pay->setPrivateKey('test');
        $f86Pay->setOptions($options);
        $f86Pay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回x-oapi-error-code
     */
    public function testPayReturnWithoutResult()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $merchantExtra = [
            'merchantCerNo' => '12345789',
        ];

        $options = [
            'number' => 'GSTZ4015FNXV',
            'amount' => '100.00',
            'notify_url' => 'http://pay.test/',
            'orderId' => '201712040000005945',
            'username' => 'test',
            'paymentVendorId' => '1090',
            'merchant_extra' => $merchantExtra,
            'verify_url' => 'payment.http.duolabao',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'http://gw.f86pay.com/',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $f86Pay = new F86Pay();
        $f86Pay->setContainer($this->container);
        $f86Pay->setClient($this->client);
        $f86Pay->setResponse($response);
        $f86Pay->setPrivateKey('test');
        $f86Pay->setOptions($options);
        $f86Pay->getVerifyData();
    }

    /**
     * 測試支付時返回提交錯誤且有返回錯誤訊息
     */
    public function testPayReturnNotSuccessHasMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '该商户没有授权的支付渠道, 商户编号: RPWY89000SXZN',
            180130
        );

        $merchantExtra = [
            'merchantCerNo' => '12345789',
        ];

        $options = [
            'number' => 'GSTZ4015FNXV',
            'amount' => '100.00',
            'notify_url' => 'http://pay.test/',
            'orderId' => '201712040000005945',
            'username' => 'test',
            'paymentVendorId' => '1090',
            'merchant_extra' => $merchantExtra,
            'verify_url' => 'payment.http.duolabao',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'http://gw.f86pay.com/',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');
        $response->addHeader('x-oapi-error-code:ERROR_BIZ_ERROR');
        $msg = 'x-oapi-msg:%E8%AF%A5%E5%95%86%E6%88%B7%E6%B2%A1%E6%9C%89%E6%8E%88%E6%9D%83%E7%9A%84%E6%94%AF%E4%BB%98'
        . '%E6%B8%A0%E9%81%93%2C%20%E5%95%86%E6%88%B7%E7%BC%96%E5%8F%B7%3A%20RPWY89000SXZN';
        $response->addHeader($msg);

        $f86Pay = new F86Pay();
        $f86Pay->setContainer($this->container);
        $f86Pay->setClient($this->client);
        $f86Pay->setResponse($response);
        $f86Pay->setPrivateKey('test');
        $f86Pay->setOptions($options);
        $f86Pay->getVerifyData();
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

        $merchantExtra = [
            'merchantCerNo' => '12345789',
        ];

        $options = [
            'number' => 'GSTZ4015FNXV',
            'amount' => '100.00',
            'notify_url' => 'http://pay.test/',
            'orderId' => '201712040000005945',
            'username' => 'test',
            'paymentVendorId' => '1090',
            'merchant_extra' => $merchantExtra,
            'verify_url' => 'payment.http.duolabao',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'http://gw.f86pay.com/',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');
        $response->addHeader('x-oapi-error-code:ERROR_BIZ_ERROR');

        $f86Pay = new F86Pay();
        $f86Pay->setContainer($this->container);
        $f86Pay->setClient($this->client);
        $f86Pay->setResponse($response);
        $f86Pay->setPrivateKey('test');
        $f86Pay->setOptions($options);
        $f86Pay->getVerifyData();
    }

    /**
     * 測試支付時返回提交成功但body為空
     */
    public function testPayReturnSuccessButEmptyBody()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Empty Payment Gateway response',
            180089
        );

        $merchantExtra = [
            'merchantCerNo' => '12345789',
        ];

        $options = [
            'number' => 'GSTZ4015FNXV',
            'amount' => '100.00',
            'notify_url' => 'http://pay.test/',
            'orderId' => '201712040000005945',
            'username' => 'test',
            'paymentVendorId' => '1090',
            'merchant_extra' => $merchantExtra,
            'verify_url' => 'payment.http.duolabao',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'http://gw.f86pay.com/',
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');
        $response->addHeader('x-oapi-error-code:SUCCEED');

        $f86Pay = new F86Pay();
        $f86Pay->setContainer($this->container);
        $f86Pay->setClient($this->client);
        $f86Pay->setResponse($response);
        $f86Pay->setPrivateKey('test');
        $f86Pay->setOptions($options);
        $f86Pay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回paymentInfo
     */
    public function testPayReturnWithoutUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $merchantExtra = [
            'merchantCerNo' => '12345789',
        ];

        $options = [
            'number' => 'GSTZ4015FNXV',
            'amount' => '100.00',
            'notify_url' => 'http://pay.test/',
            'orderId' => '201712040000005945',
            'username' => 'test',
            'paymentVendorId' => '1090',
            'merchant_extra' => $merchantExtra,
            'verify_url' => 'payment.http.duolabao',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'http://gw.f86pay.com/',
        ];

        $result = '{"merchantNo":"GSTZ4015FNXV"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');
        $response->addHeader('x-oapi-error-code:SUCCEED');

        $f86Pay = new F86Pay();
        $f86Pay->setContainer($this->container);
        $f86Pay->setClient($this->client);
        $f86Pay->setResponse($response);
        $f86Pay->setPrivateKey('test');
        $f86Pay->setOptions($options);
        $f86Pay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $merchantExtra = [
            'merchantCerNo' => '12345789',
        ];

        $options = [
            'number' => 'GSTZ4015FNXV',
            'amount' => '100.00',
            'notify_url' => 'http://pay.test/',
            'orderId' => '201712040000005945',
            'username' => 'test',
            'paymentVendorId' => '1090',
            'merchant_extra' => $merchantExtra,
            'verify_url' => 'payment.http.duolabao',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'http://gw.f86pay.com/',
        ];

        $result = '{"paymentInfo":"http://gw.scennet.com/pay/router.do?trade_no=201712056new0AYE"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');
        $response->addHeader('x-oapi-error-code:SUCCEED');

        $f86Pay = new F86Pay();
        $f86Pay->setContainer($this->container);
        $f86Pay->setClient($this->client);
        $f86Pay->setResponse($response);
        $f86Pay->setPrivateKey('test');
        $f86Pay->setOptions($options);
        $data = $f86Pay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('http://gw.scennet.com/pay/router.do?trade_no=201712056new0AYE', $f86Pay->getQrcode());
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $merchantExtra = [
            'merchantCerNo' => '12345789',
        ];

        $options = [
            'number' => 'GSTZ4015FNXV',
            'amount' => '100.00',
            'notify_url' => 'http://pay.test/',
            'orderId' => '201712040000005945',
            'username' => 'test',
            'paymentVendorId' => '1104',
            'merchant_extra' => $merchantExtra,
            'verify_url' => 'payment.http.duolabao',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'http://gw.f86pay.com/',
        ];

        $result = '{"paymentInfo":"http://gw.scennet.com/pay/router.do?trade_no=201712056new0AYE"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');
        $response->addHeader('x-oapi-error-code:SUCCEED');

        $f86Pay = new F86Pay();
        $f86Pay->setContainer($this->container);
        $f86Pay->setClient($this->client);
        $f86Pay->setResponse($response);
        $f86Pay->setPrivateKey('test');
        $f86Pay->setOptions($options);
        $data = $f86Pay->getVerifyData();

        $this->assertEquals('http://gw.scennet.com/pay/router.do?trade_no=201712056new0AYE', $data['act_url']);
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

        $f86Pay = new F86Pay();
        $f86Pay->verifyOrderPayment([]);
    }

    /**
     * 測試返回缺少簽名
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $body = [
            'settleFee' => '0',
            'settlePeriod' => 'T1',
            'payType' => 'WECHAT_QRCODE_PAY',
            'attachment' => 'eyJyMTBfU5DT0RFIn0=',
            'outTradeNo' => '201712040000005945',
            'currency' => 'CNY',
            'payedAmount' => '100',
            'amount' => '100',
            'payedTime' => '2017-12-04 12:51:43',
            'tradeNo' => '201712046mcb0BTR',
            'settleType' => 'SELF',
            'merchantNo' => 'GSTZ4015FNXV',
            'status' => 'SETTLED',
        ];

        $options = [
            'notify_url' => 'http://pay.test/pay/return.php',
            'headers' => [
                'x-oapi-sm' => 'MD5',
            ],
            'body' => json_encode($body),
        ];

        $f86Pay = new F86Pay();
        $f86Pay->setPrivateKey('test');
        $f86Pay->setOptions($options);
        $f86Pay->verifyOrderPayment([]);
    }

    /**
     * 測試返回缺少body
     */
    public function testReturnWithoutBody()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'notify_url' => 'http://pay.test/pay/return.php',
            'headers' => [
                'x-oapi-sm' => 'MD5',
                'x-oapi-sign' => 'MD5',
            ],
        ];

        $f86Pay = new F86Pay();
        $f86Pay->setPrivateKey('test');
        $f86Pay->setOptions($options);
        $f86Pay->verifyOrderPayment([]);
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

        $body = [
            'settleFee' => '0',
            'settlePeriod' => 'T1',
            'payType' => 'WECHAT_QRCODE_PAY',
            'attachment' => 'eyJyMTBfU5DT0RFIn0=',
            'outTradeNo' => '201712040000005945',
            'currency' => 'CNY',
            'payedAmount' => '100',
            'amount' => '100',
            'payedTime' => '2017-12-04 12:51:43',
            'tradeNo' => '201712046mcb0BTR',
            'settleType' => 'SELF',
            'merchantNo' => 'GSTZ4015FNXV',
            'status' => 'SETTLED',
        ];

        $options = [
            'notify_url' => 'http://pay.test/pay/return.php',
            'headers' => [
                'x-oapi-sm' => 'MD5',
                'x-oapi-sign' => 'MD5',
            ],
            'body' => json_encode($body),
        ];

        $f86Pay = new F86Pay();
        $f86Pay->setPrivateKey('test');
        $f86Pay->setOptions($options);
        $f86Pay->verifyOrderPayment([]);
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

        $body = [
            'settleFee' => '0',
            'settlePeriod' => 'T1',
            'payType' => 'WECHAT_QRCODE_PAY',
            'attachment' => 'eyJyMTBfU5DT0RFIn0=',
            'outTradeNo' => '201712040000005945',
            'currency' => 'CNY',
            'payedAmount' => '100',
            'payedTime' => '2017-12-04 12:51:43',
            'tradeNo' => '201712046mcb0BTR',
            'settleType' => 'SELF',
            'merchantNo' => 'GSTZ4015FNXV',
            'status' => 'SETTLED',
        ];

        $options = [
            'notify_url' => 'http://pay.test/pay/return.php',
            'headers' => [
                'x-oapi-sm' => 'MD5',
                'x-oapi-sign' => '3822C08824E3F6632C393BB81D88DAAF',
            ],
            'body' => json_encode($body),
        ];

        $f86Pay = new F86Pay();
        $f86Pay->setPrivateKey('test');
        $f86Pay->setOptions($options);
        $f86Pay->verifyOrderPayment([]);
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

        $body = [
            'settleFee' => '0',
            'settlePeriod' => 'T1',
            'payType' => 'WECHAT_QRCODE_PAY',
            'attachment' => 'eyJyMTBfU5DT0RFIn0=',
            'outTradeNo' => '201712040000005945',
            'currency' => 'CNY',
            'payedAmount' => '100',
            'amount' => '100',
            'payedTime' => '2017-12-04 12:51:43',
            'tradeNo' => '201712046mcb0BTR',
            'settleType' => 'SELF',
            'merchantNo' => 'GSTZ4015FNXV',
            'status' => 'PAYED_FAILED',
        ];

        $options = [
            'notify_url' => 'http://pay.test/pay/return.php',
            'headers' => [
                'x-oapi-sm' => 'MD5',
                'x-oapi-sign' => '3FFA23A001601EA157F9E10053BC6EE4',
            ],
            'body' => json_encode($body),
        ];

        $f86Pay = new F86Pay();
        $f86Pay->setPrivateKey('test');
        $f86Pay->setOptions($options);
        $f86Pay->verifyOrderPayment([]);
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

        $body = [
            'settleFee' => '0',
            'settlePeriod' => 'T1',
            'payType' => 'WECHAT_QRCODE_PAY',
            'attachment' => 'eyJyMTBfU5DT0RFIn0=',
            'outTradeNo' => '201712040000005945',
            'currency' => 'CNY',
            'payedAmount' => '100',
            'amount' => '100',
            'payedTime' => '2017-12-04 12:51:43',
            'tradeNo' => '201712046mcb0BTR',
            'settleType' => 'SELF',
            'merchantNo' => 'GSTZ4015FNXV',
            'status' => 'SETTLED',
        ];

        $options = [
            'notify_url' => 'http://pay.test/pay/return.php',
            'headers' => [
                'x-oapi-sm' => 'MD5',
                'x-oapi-sign' => 'C5702AA7C1206BB0A6EC4C4FF5E940D1',
            ],
            'body' => json_encode($body),
        ];

        $entry = ['id' => '201608150000004475'];

        $f86Pay = new F86Pay();
        $f86Pay->setPrivateKey('test');
        $f86Pay->setOptions($options);
        $f86Pay->verifyOrderPayment($entry);
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

        $body = [
            'settleFee' => '0',
            'settlePeriod' => 'T1',
            'payType' => 'WECHAT_QRCODE_PAY',
            'attachment' => 'eyJyMTBfU5DT0RFIn0=',
            'outTradeNo' => '201712040000005945',
            'currency' => 'CNY',
            'payedAmount' => '100',
            'amount' => '100',
            'payedTime' => '2017-12-04 12:51:43',
            'tradeNo' => '201712046mcb0BTR',
            'settleType' => 'SELF',
            'merchantNo' => 'GSTZ4015FNXV',
            'status' => 'SETTLED',
        ];

        $options = [
            'notify_url' => 'http://pay.test/pay/return.php',
            'headers' => [
                'x-oapi-sm' => 'MD5',
                'x-oapi-sign' => 'C5702AA7C1206BB0A6EC4C4FF5E940D1',
            ],
            'body' => json_encode($body),
        ];

        $entry = [
            'id' => '201712040000005945',
            'amount' => '10',
        ];

        $f86Pay = new F86Pay();
        $f86Pay->setPrivateKey('test');
        $f86Pay->setOptions($options);
        $f86Pay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $body = [
            'settleFee' => '0',
            'settlePeriod' => 'T1',
            'payType' => 'WECHAT_QRCODE_PAY',
            'attachment' => 'eyJyMTBfU5DT0RFIn0=',
            'outTradeNo' => '201712040000005945',
            'currency' => 'CNY',
            'payedAmount' => '100',
            'amount' => '100',
            'payedTime' => '2017-12-04 12:51:43',
            'tradeNo' => '201712046mcb0BTR',
            'settleType' => 'SELF',
            'merchantNo' => 'GSTZ4015FNXV',
            'status' => 'SETTLED',
        ];

        $options = [
            'notify_url' => 'http://pay.test/pay/return.php',
            'headers' => [
                'x-oapi-sm' => 'MD5',
                'x-oapi-sign' => 'C5702AA7C1206BB0A6EC4C4FF5E940D1',
            ],
            'body' => json_encode($body),
        ];

        $entry = [
            'id' => '201712040000005945',
            'amount' => '1',
        ];

        $f86Pay = new F86Pay();
        $f86Pay->setPrivateKey('test');
        $f86Pay->setOptions($options);
        $f86Pay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCEED', $f86Pay->getMsg());
    }
}
