<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YinLiBao;
use Buzz\Message\Response;

class YinLiBaoTest extends DurianTestCase
{
    /**
     * 私鑰
     *
     * @var string
     */
    private $privateKey;

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * @var \Buzz\Client\Curl
     */
    private $client;

    /**
     * 提交給支付平台時需要的參數
     *
     * @var array
     */
    private $option;

    /**
     * 支付成功時通知的參數
     *
     * @var array
     */
    private $returnResult;

    public function setUp()
    {
        parent::setUp();

        $res = openssl_pkey_new();

        $privatekey = '';
        openssl_pkey_export($res, $privatekey);
        $this->privateKey = base64_encode($privatekey);

        $publicKey = openssl_pkey_get_details($res);
        $publicKey = base64_encode($publicKey['key']);

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

        $this->option = [
            'number' => '1528537196',
            'orderId' => '201806260000012042',
            'amount' => '1',
            'orderCreateDate' => '2018-06-26 09:30:40',
            'paymentVendorId' => '1111',
            'ip' => '111.235.135.54',
            'verify_url' => 'payment.http.orz.zz',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'notify_url' => 'http://orz.zz/pay/reutrn.php',
        ];

        $this->returnResult = [
            'result_code' => 'SUCCESS',
            'mch_id' => '1528537196',
            'trade_type' => 'UPSCAN',
            'nonce' => 'b832465ea71a495c95ff934abcfb2177',
            'timestamp' => '1529984032',
            'out_trade_no' => '201806260000012042',
            'total_fee' => '100',
            'trade_no' => '0301180626000561063080537',
            'platform_trade_no' => 'TA20180626144204143740',
            'pay_time' => '20180626144220',
        ];

        $encodeData = $this->returnResult;
        ksort($encodeData);
        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        openssl_sign(md5($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_SHA256);

        $this->returnResult['sign'] = base64_encode($sign);
        $this->returnResult['rsa_public_key'] = $publicKey;
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

        $yinLiBao = new YinLiBao();
        $yinLiBao->getVerifyData();
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

        $yinLiBao = new YinLiBao();
        $yinLiBao->setPrivateKey('test');
        $yinLiBao->getVerifyData();
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

        $this->option['paymentVendorId'] = '9999';

        $yinLiBao = new YinLiBao();
        $yinLiBao->setPrivateKey('test');
        $yinLiBao->setOptions($this->option);
        $yinLiBao->getVerifyData();
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

        $this->option['verify_url'] = '';

        $yinLiBao = new YinLiBao();
        $yinLiBao->setPrivateKey('test');
        $yinLiBao->setOptions($this->option);
        $yinLiBao->getVerifyData();
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

        $yinLiBao = new YinLiBao();
        $yinLiBao->setContainer($this->container);
        $yinLiBao->setClient($this->client);
        $yinLiBao->setPrivateKey('test');
        $yinLiBao->setOptions($this->option);
        $yinLiBao->getVerifyData();
    }

    /**
     * 測試支付時返回結果為空
     */
    public function testPayReturnEmptyContent()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Empty Payment Gateway response',
            180089
        );

        $response = new Response();
        $response->addHeader('HTTP/1.1 200');
        $response->addHeader('Content-Type:application/json;');

        $yinLiBao = new YinLiBao();
        $yinLiBao->setContainer($this->container);
        $yinLiBao->setClient($this->client);
        $yinLiBao->setResponse($response);
        $yinLiBao->setPrivateKey('test');
        $yinLiBao->setOptions($this->option);
        $yinLiBao->getVerifyData();
    }

    /**
     * 測試支付時返回statusCode不為200
     */
    public function testPayReturnStatusCodeNot200()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '支付失败（交易金额不得低于单笔下限10.00元）',
            180130
        );

        $result = [
            'code' => 'WAF/SERVICE_UNAVAILABLE',
            'message' => '支付失败（交易金额不得低于单笔下限10.00元）',
            'host_id' => 'api.365df.cn',
            'request_id' => 'b714506b-ad6e-408a-838f-f80d8ea65fa3',
            'server_time' => '2018-06-28T14:06:41.895 0000',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 500');
        $response->addHeader('Content-Type:application/json;');

        $yinLiBao = new YinLiBao();
        $yinLiBao->setContainer($this->container);
        $yinLiBao->setClient($this->client);
        $yinLiBao->setResponse($response);
        $yinLiBao->setPrivateKey('test');
        $yinLiBao->setOptions($this->option);
        $yinLiBao->getVerifyData();
    }

    /**
     * 測試支付時返回statusCode不為200且無錯誤訊息
     */
    public function testPayReturnStatusCodeNot200AndNoMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $result = [
            'code' => 'WAF/SERVICE_UNAVAILABLE',
            'host_id' => 'api.365df.cn',
            'request_id' => 'b714506b-ad6e-408a-838f-f80d8ea65fa3',
            'server_time' => '2018-06-28T14:06:41.895 0000',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 500');
        $response->addHeader('Content-Type:application/json;');

        $yinLiBao = new YinLiBao();
        $yinLiBao->setContainer($this->container);
        $yinLiBao->setClient($this->client);
        $yinLiBao->setResponse($response);
        $yinLiBao->setPrivateKey('test');
        $yinLiBao->setOptions($this->option);
        $yinLiBao->getVerifyData();
    }

    /**
     * 測試支付時未返回pay_url
     */
    public function testPayNoReturnPayUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yinLiBao = new YinLiBao();
        $yinLiBao->setContainer($this->container);
        $yinLiBao->setClient($this->client);
        $yinLiBao->setResponse($response);
        $yinLiBao->setPrivateKey('test');
        $yinLiBao->setOptions($this->option);
        $yinLiBao->getVerifyData();
    }

    /**
     * 測試銀聯二維支付
     */
    public function testQrcodePay()
    {
        $result = [
            'mch_id' => '1528537196',
            'trade_type' => 'UPSCAN',
            'nonce' => 'ef4b546fb3224eb4aa996e125d311d86',
            'pay_url' => 'https://api.365df.cn/qr/fbd208b98d9a4d69bd24812fb2c729fe',
            'sign' => 'liz0wbwAQPEMwbUrXSaz4jfjCILwCraxWdIMBcHoCi1dqj28dYxjWm5G7wdE6icdFkkaB3rbGpeonvVxofA0wJQHcHRpfb' .
                'tR/64BnuAKZrvM40hhUrvdgrEflnRswkPWa7lGRPYdP8nyqTGeoiQpE67tf6m/3ZcIv622uUDzwLbx/XmQTVrhRcCaxDvujgL4Zf' .
                'pRCR9bFeW/1Urs2/8YJgmWAgL1zxbpRaLGysNGyBz13hjn4azFReu2sS5HT4FEFd0rCBn0IdOVl7BB4EYLIPS7VJu3FLfs656QfU' .
                'uB0uV6oNSKMEKjveCLD10G5hmpI5NCsBsRuwWa3O7Q==',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yinLiBao = new YinLiBao();
        $yinLiBao->setContainer($this->container);
        $yinLiBao->setClient($this->client);
        $yinLiBao->setResponse($response);
        $yinLiBao->setPrivateKey('test');
        $yinLiBao->setOptions($this->option);
        $verifyData = $yinLiBao->getVerifyData();

        $html = '<img src="https://api.365df.cn/qr/fbd208b98d9a4d69bd24812fb2c729fe"/>';

        $this->assertEmpty($verifyData);
        $this->assertEquals($html, $yinLiBao->getHtml());
    }

    /**
     * 測試網銀收銀台支付
     */
    public function testPay()
    {
        $result = [
            'mch_id' => '1528537196',
            'trade_type' => 'GATEWAY',
            'nonce' => 'b50e58906186401eb3be57bfb4873f98',
            'pay_url' => 'https://api.365df.cn/pay/gateway?prepay_id=322d1c45bb0247b1815b2b7dec13c698',
            'sign' => 'UHB4lzcFQ9aKVldPN4bcfSwbRZer12/Nh8JpLb6ioPnv9MUXaebcuuE0dGnfTSzp4C9FgRVgwzCO5SvArz7v13zJD7dtnA' .
                'Uwc92SdsrVuta0RRJ8NktetzlNTCmOowfu9tcUQn6lmTLPQHlOfsOIKO/R5CVeDgJ7LxV1HQCzxDsVYcSXozjKFLWab3tBEC5TfR' .
                'Np8EgQ/SaJQbU/ZnuhjBnfj1iaNacYfdQYq6Z2f4LzyTZbpMd9ZuZWNg0oDqYTy015lyi23ksZuDxPDf1Z/RnsVdfAxDPHdwnCy8' .
                'gb7VOyqJeYrl9hvpDrAmqWpFOCeF8LHiOUm5hbWstA==',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $this->option['paymentVendorId'] = '1102';

        $yinLiBao = new YinLiBao();
        $yinLiBao->setContainer($this->container);
        $yinLiBao->setClient($this->client);
        $yinLiBao->setResponse($response);
        $yinLiBao->setPrivateKey('test');
        $yinLiBao->setOptions($this->option);
        $verifyData = $yinLiBao->getVerifyData();

        $this->assertEquals('GET', $yinLiBao->getPayMethod());
        $this->assertEquals('https://api.365df.cn/pay/gateway', $verifyData['post_url']);
        $this->assertEquals('322d1c45bb0247b1815b2b7dec13c698', $verifyData['params']['prepay_id']);
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

        $yinLiBao = new YinLiBao();
        $yinLiBao->verifyOrderPayment([]);
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

        $yinLiBao = new YinLiBao();
        $yinLiBao->setPrivateKey('test');
        $yinLiBao->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少簽名
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['sign']);

        $yinLiBao = new YinLiBao();
        $yinLiBao->setPrivateKey('test');
        $yinLiBao->setOptions($this->returnResult);
        $yinLiBao->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = '123456789';

        $yinLiBao = new YinLiBao();
        $yinLiBao->setPrivateKey('test');
        $yinLiBao->setOptions($this->returnResult);
        $yinLiBao->verifyOrderPayment([]);
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

        $this->returnResult['result_code'] = 'FAIL';

        $encodeData = $this->returnResult;
        unset($encodeData['rsa_public_key']);
        unset($encodeData['sign']);
        ksort($encodeData);
        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        openssl_sign(md5($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_SHA256);

        $this->returnResult['sign'] = base64_encode($sign);

        $yinLiBao = new YinLiBao();
        $yinLiBao->setPrivateKey('test');
        $yinLiBao->setOptions($this->returnResult);
        $yinLiBao->verifyOrderPayment([]);
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

        $entry = ['id' => '301806260000012042'];

        $yinLiBao = new YinLiBao();
        $yinLiBao->setPrivateKey('test');
        $yinLiBao->setOptions($this->returnResult);
        $yinLiBao->verifyOrderPayment($entry);
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

        $entry = [
            'id' => '201806260000012042',
            'amount' => '15',
        ];

        $yinLiBao = new YinLiBao();
        $yinLiBao->setPrivateKey('test');
        $yinLiBao->setOptions($this->returnResult);
        $yinLiBao->verifyOrderPayment($entry);
    }

    /**
     * 測試返回成功
     */
    public function testReturnSuccess()
    {
        $entry = [
            'id' => '201806260000012042',
            'amount' => '1',
        ];

        $yinLiBao = new YinLiBao();
        $yinLiBao->setPrivateKey('test');
        $yinLiBao->setOptions($this->returnResult);
        $yinLiBao->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $yinLiBao->getMsg());
    }
}
