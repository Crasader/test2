<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\NewJFT;
use Buzz\Message\Response;

class NewJFTTest extends DurianTestCase
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

        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $mockLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Payment')
            ->disableOriginalConstructor()
            ->setMethods(['record'])
            ->getMock();

        $mockLogger->expects($this->any())
            ->method('record')
            ->willReturn(null);

        $this->container->expects($this->any())
            ->method('get')
            ->willReturn($mockLogger);

        $this->client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
    }

    /**
     * 測試支付加密時沒有帶入privateKey的情況
     */
    public function testPayEncodeWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $newJFT = new NewJFT();
        $newJFT->getVerifyData();
    }

    /**
     * 測試支付加密時未指定支付參數
     */
    public function testPayEncodeWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $newJFT = new NewJFT();
        $newJFT->setPrivateKey('8ADC8348E3AF63AD8FA17975C8620EA6');

        $sourceData = ['number' => ''];

        $newJFT->setOptions($sourceData);
        $newJFT->getVerifyData();
    }

    /**
     * 測試支付加密時沒有帶入postUrl的情況
     */
    public function testPayEncodeWithoutPostUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $newJFT = new NewJFT();
        $newJFT->setPrivateKey('8ADC8348E3AF63AD8FA17975C8620EA6');

        $sourceData = [
            'number' => '5438',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201406110000000001',
            'notify_url' => 'http://118.232.50.208/return/return.php?pay_system=12345&hallid=6',
            'postUrl' => '',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $newJFT->setOptions($sourceData);
        $newJFT->getVerifyData();
    }

    /**
     * 測試支付加密時代入支付平台不支援的銀行
     */
    public function testPayEncodeUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $newJFT = new NewJFT();
        $newJFT->setPrivateKey('8ADC8348E3AF63AD8FA17975C8620EA6');

        $sourceData = [
            'number' => '5438',
            'paymentVendorId' => '999',
            'amount' => '0.01',
            'orderId' => '201406110000000001',
            'notify_url' => 'http://118.232.50.208/return/return.php?pay_system=12345&hallid=6',
            'postUrl' => 'http://do.jftpay.com/chargebank.aspx',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $newJFT->setOptions($sourceData);
        $newJFT->getVerifyData();
    }

    /**
     * 測試支付加密
     */
    public function testPayEncode()
    {
        $sourceData = [
            'number' => '5438',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201406110000000001',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'postUrl' => 'http://do.jftpay.com/chargebank.aspx',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $newJFT = new NewJFT();
        $newJFT->setPrivateKey('8ADC8348E3AF63AD8FA17975C8620EA6');
        $newJFT->setOptions($sourceData);
        $encodeData = $newJFT->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system_hallid=%s_%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals($sourceData['number'], $encodeData['parter']);
        $this->assertEquals('967', $encodeData['type']);
        $this->assertSame('0.01', $encodeData['value']);
        $this->assertEquals($sourceData['orderId'], $encodeData['orderid']);
        $this->assertEquals($notifyUrl, $encodeData['callbackurl']);
        $this->assertEquals('eb5b047f468cea29d3db36e6e51102c1', $encodeData['sign']);

        //檢查要提交的網址是否正確
        $data = [];
        $data['parter'] = $encodeData['parter'];
        $data['type'] = $encodeData['type'];
        $data['value'] = $encodeData['value'];
        $data['orderid'] = $encodeData['orderid'];
        $data['callbackurl'] = $encodeData['callbackurl'];
        $data['sign'] = $encodeData['sign'];

        $this->assertEquals($sourceData['postUrl'] . '?' . http_build_query($data), $encodeData['act_url']);
    }

    /**
     * 測試解密基本參數設定沒有帶入key的情況
     */
    public function testSetDecodeSourceWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $newJFT = new NewJFT();
        $newJFT->setPrivateKey('');

        $newJFT->setContainer($this->container);

        $sourceData = ['aorderid' => '2014061109475775325'];

        $newJFT->setOptions($sourceData);
        $newJFT->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台缺少回傳aorderid(新聚付通的訂單號)
     */
    public function testVerifyWithoutAorderid()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $newJFT = new NewJFT();
        $newJFT->setPrivateKey('8ADC8348E3AF63AD8FA17975C8620EA6');

        $newJFT->setContainer($this->container);

        $sourceData = [
            'pay_system' => '12345',
            'hallid'     => '6',
            'orderid'    => '201406110000000001',
            'opstate'    => '0',
            'ovalue'     => '0.01',
            'sign'       => '25534b37bcf0d702234f8f7e4896193f',
            'reply'      => '1',
            'atime'      => '2014-6-11 9:49:09',
            'textmsg'    => ''
        ];

        $newJFT->setOptions($sourceData);
        $newJFT->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台未指定返回參數
     */
    public function testVerifyWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $result = 'true';

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader('Content-Type:application/json;charset=UTF-8');

        $newJFT = new NewJFT();
        $newJFT->setContainer($this->container);
        $newJFT->setClient($this->client);
        $newJFT->setResponse($respone);
        $newJFT->setPrivateKey('8ADC8348E3AF63AD8FA17975C8620EA6');

        $sourceData = [
            'pay_system' => '12345',
            'hallid' => '6',
            'orderid' => '201406110000000001',
            'opstate' => '0',
            'sign' => '25534b37bcf0d702234f8f7e4896193f',
            'reply' => '1',
            'aorderid' => '2014061109475775325',
            'atime' => '2014-6-11 9:49:09',
            'textmsg' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.2.3.4'
        ];

        $newJFT->setOptions($sourceData);
        $newJFT->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台缺少回傳sign(加密簽名)
     */
    public function testVerifyWithouttSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $result = 'true';

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader('Content-Type:application/json;charset=UTF-8');

        $newJFT = new NewJFT();
        $newJFT->setContainer($this->container);
        $newJFT->setClient($this->client);
        $newJFT->setResponse($respone);
        $newJFT->setPrivateKey('8ADC8348E3AF63AD8FA17975C8620EA6');

        $sourceData = [
            'pay_system' => '12345',
            'hallid' => '6',
            'orderid' => '201406110000000001',
            'opstate' => '0',
            'ovalue' => '0.01',
            'reply' => '1',
            'aorderid' => '2014061109475775325',
            'atime' => '2014-6-11 9:49:09',
            'textmsg' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.2.3.4'
        ];

        $newJFT->setOptions($sourceData);
        $newJFT->verifyOrderPayment([]);
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

        $result = 'true';

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader('Content-Type:application/json;charset=UTF-8');

        $newJFT = new NewJFT();
        $newJFT->setContainer($this->container);
        $newJFT->setClient($this->client);
        $newJFT->setResponse($respone);
        $newJFT->setPrivateKey('8ADC8348E3AF63AD8FA17975C8620EA6');

        $sourceData = [
            'pay_system' => '12345',
            'hallid' => '6',
            'orderid' => '201406110000000001',
            'opstate' => '0',
            'ovalue' => '0.01',
            'sign' => '02234f8f7e4896193f25534b37bcf0d7',
            'reply' => '1',
            'aorderid' => '2014061109475775325',
            'atime' => '2014-6-11 9:49:09',
            'textmsg' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.2.3.4'
        ];

        $newJFT->setOptions($sourceData);
        $newJFT->verifyOrderPayment([]);
    }

    /**
     * 測試返回時取得無效的支付參數
     */
    public function testReturnInvalidPayParameters()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Invalid pay parameters',
            180129
        );

        $result = 'true';

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader('Content-Type:application/json;charset=UTF-8');

        $newJFT = new NewJFT();
        $newJFT->setContainer($this->container);
        $newJFT->setClient($this->client);
        $newJFT->setResponse($respone);
        $newJFT->setPrivateKey('8ADC8348E3AF63AD8FA17975C8620EA6');

        $sourceData = [
            'pay_system' => '12345',
            'hallid' => '6',
            'orderid' => '201406110000000001',
            'opstate' => '-1',
            'ovalue' => '0.01',
            'sign' => '51483c663afc297320704db3e4d67ca8',
            'reply' => '1',
            'aorderid' => '2014061109475775325',
            'atime' => '2014-6-11 9:49:09',
            'textmsg' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.2.3.4'
        ];

        $newJFT->setOptions($sourceData);
        $newJFT->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為支付平台驗證簽名錯誤
     */
    public function testReturnPaymentGatewaySignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Merchant sign error',
            180127
        );

        $result = 'true';

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader('Content-Type:application/json;charset=UTF-8');

        $newJFT = new NewJFT();
        $newJFT->setContainer($this->container);
        $newJFT->setClient($this->client);
        $newJFT->setResponse($respone);
        $newJFT->setPrivateKey('8ADC8348E3AF63AD8FA17975C8620EA6');

        $sourceData = [
            'pay_system' => '12345',
            'hallid' => '6',
            'orderid' => '201406110000000001',
            'opstate' => '-2',
            'ovalue' => '0.01',
            'sign' => 'b09da7e764a50a463e06a9ec8b63ff58',
            'reply' => '1',
            'aorderid' => '2014061109475775325',
            'atime' => '2014-6-11 9:49:09',
            'textmsg' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.2.3.4'
        ];

        $newJFT->setOptions($sourceData);
        $newJFT->verifyOrderPayment([]);
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

        $result = 'true';

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader('Content-Type:application/json;charset=UTF-8');

        $newJFT = new NewJFT();
        $newJFT->setContainer($this->container);
        $newJFT->setClient($this->client);
        $newJFT->setResponse($respone);
        $newJFT->setPrivateKey('8ADC8348E3AF63AD8FA17975C8620EA6');

        $sourceData = [
            'pay_system' => '12345',
            'hallid' => '6',
            'orderid' => '201406110000000001',
            'opstate' => '-5',
            'ovalue' => '0.01',
            'sign' => 'eace6c019afb35014e9173d1f9ac2ef7',
            'reply' => '1',
            'aorderid' => '2014061109475775325',
            'atime' => '2014-6-11 9:49:09',
            'textmsg' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.2.3.4'
        ];

        $newJFT->setOptions($sourceData);
        $newJFT->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $result = 'true';

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader('Content-Type:application/json;charset=UTF-8');

        $newJFT = new NewJFT();
        $newJFT->setContainer($this->container);
        $newJFT->setClient($this->client);
        $newJFT->setResponse($respone);
        $newJFT->setPrivateKey('8ADC8348E3AF63AD8FA17975C8620EA6');

        $sourceData = [
            'pay_system' => '12345',
            'hallid' => '6',
            'orderid' => '201406110000000001',
            'opstate' => '0',
            'ovalue' => '0.01',
            'sign' => '25534b37bcf0d702234f8f7e4896193f',
            'reply' => '1',
            'aorderid' => '2014061109475775325',
            'atime' => '2014-6-11 9:49:09',
            'textmsg' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.2.3.4'
        ];

        $entry = ['id' => '2014052200123'];

        $newJFT->setOptions($sourceData);
        $newJFT->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $result = 'true';

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader('Content-Type:application/json;charset=UTF-8');

        $newJFT = new NewJFT();
        $newJFT->setContainer($this->container);
        $newJFT->setClient($this->client);
        $newJFT->setResponse($respone);
        $newJFT->setPrivateKey('8ADC8348E3AF63AD8FA17975C8620EA6');

        $sourceData = [
            'pay_system' => '12345',
            'hallid' => '6',
            'orderid' => '201406110000000001',
            'opstate' => '0',
            'ovalue' => '0.01',
            'sign' => '25534b37bcf0d702234f8f7e4896193f',
            'reply' => '1',
            'aorderid' => '2014061109475775325',
            'atime' => '2014-6-11 9:49:09',
            'textmsg' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.2.3.4'
        ];

        $entry = [
            'id' => '201406110000000001',
            'amount' => '1.0000'
        ];

        $newJFT->setOptions($sourceData);
        $newJFT->verifyOrderPayment($entry);
    }

    /**
     * 測試支付時對外返回結果錯誤
     */
    public function testPayConnectionPaymentGatewayError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $result = 'false';

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader('Content-Type:application/json;charset=UTF-8');

        $newJFT = new NewJFT();
        $newJFT->setContainer($this->container);
        $newJFT->setClient($this->client);
        $newJFT->setResponse($respone);
        $newJFT->setPrivateKey('8ADC8348E3AF63AD8FA17975C8620EA6');

        $sourceData = [
            'pay_system' => '12345',
            'hallid' => '6',
            'orderid' => '201406110000000001',
            'opstate' => '0',
            'ovalue' => '0.01',
            'sign' => '25534b37bcf0d702234f8f7e4896193f',
            'reply' => '1',
            'aorderid' => '2014061109475775325',
            'atime' => '2014-6-11 9:49:09',
            'textmsg' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.2.3.4'
        ];

        $entry = [
            'id' => '201406110000000001',
            'amount' => '0.0100'
        ];

        $newJFT->setOptions($sourceData);
        $newJFT->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時支付平台回傳結果為空
     */
    public function testReturnEmptyPaymentGatewayResponse()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Empty Payment Gateway response',
            180089
        );

        $respone = new Response();
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader('Content-Type:application/json;charset=UTF-8');

        $newJFT = new NewJFT();
        $newJFT->setContainer($this->container);
        $newJFT->setClient($this->client);
        $newJFT->setResponse($respone);
        $newJFT->setPrivateKey('8ADC8348E3AF63AD8FA17975C8620EA6');

        $sourceData = [
            'pay_system' => '12345',
            'hallid' => '6',
            'orderid' => '201406110000000001',
            'opstate' => '0',
            'ovalue' => '0.01',
            'sign' => '25534b37bcf0d702234f8f7e4896193f',
            'reply' => '1',
            'aorderid' => '2014061109475775325',
            'atime' => '2014-6-11 9:49:09',
            'textmsg' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.2.3.4'
        ];

        $entry = [
            'id' => '201406110000000001',
            'amount' => '0.0100'
        ];

        $newJFT->setOptions($sourceData);
        $newJFT->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時支付平台連線異常
     */
    public function testReturnPaymentGatewayConnectionError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment Gateway connection failure',
            180088
        );

        $exception = new \Exception('Payment Gateway connection failure', 180088);
        $this->client->expects($this->any())
            ->method('send')
            ->willThrowException($exception);

        $newJFT = new NewJFT();
        $newJFT->setContainer($this->container);
        $newJFT->setClient($this->client);
        $newJFT->setPrivateKey('8ADC8348E3AF63AD8FA17975C8620EA6');

        $sourceData = [
            'pay_system' => '12345',
            'hallid' => '6',
            'orderid' => '201406110000000001',
            'opstate' => '0',
            'ovalue' => '0.01',
            'sign' => '25534b37bcf0d702234f8f7e4896193f',
            'reply' => '1',
            'aorderid' => '2014061109475775325',
            'atime' => '2014-6-11 9:49:09',
            'textmsg' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.2.3.4'
        ];

        $entry = [
            'id' => '201406110000000001',
            'amount' => '0.0100'
        ];

        $newJFT->setOptions($sourceData);
        $newJFT->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時支付平台連線失敗
     */
    public function testReturnPaymentGatewayConnectionFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment Gateway connection failure',
            180088
        );

        $result = 'true';

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 499');
        $respone->addHeader('Content-Type:application/json;charset=UTF-8');

        $newJFT = new NewJFT();
        $newJFT->setContainer($this->container);
        $newJFT->setClient($this->client);
        $newJFT->setResponse($respone);
        $newJFT->setPrivateKey('8ADC8348E3AF63AD8FA17975C8620EA6');

        $sourceData = [
            'pay_system' => '12345',
            'hallid' => '6',
            'orderid' => '201406110000000001',
            'opstate' => '0',
            'ovalue' => '0.01',
            'sign' => '25534b37bcf0d702234f8f7e4896193f',
            'reply' => '1',
            'aorderid' => '2014061109475775325',
            'atime' => '2014-6-11 9:49:09',
            'textmsg' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.2.3.4'
        ];

        $entry = [
            'id' => '201406110000000001',
            'amount' => '0.0100'
        ];

        $newJFT->setOptions($sourceData);
        $newJFT->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $result = 'true';

        $respone = new Response();
        $respone->setContent($result);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader('Content-Type:application/json;charset=UTF-8');

        $newJFT = new NewJFT();
        $newJFT->setContainer($this->container);
        $newJFT->setClient($this->client);
        $newJFT->setResponse($respone);
        $newJFT->setPrivateKey('8ADC8348E3AF63AD8FA17975C8620EA6');

        $sourceData = [
            'pay_system' => '12345',
            'hallid' => '6',
            'orderid' => '201406110000000001',
            'opstate' => '0',
            'ovalue' => '0.01',
            'sign' => '25534b37bcf0d702234f8f7e4896193f',
            'reply' => '1',
            'aorderid' => '2014061109475775325',
            'atime' => '2014-6-11 9:49:09',
            'textmsg' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.2.3.4'
        ];

        $entry = [
            'id' => '201406110000000001',
            'amount' => '0.0100'
        ];

        $newJFT->setOptions($sourceData);
        $newJFT->verifyOrderPayment($entry);

        $this->assertEquals('opstate=0', $newJFT->getMsg());
    }
}
