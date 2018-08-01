<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\TongLianAlipay;
use Buzz\Message\Response;

class TongLianAlipayTest extends DurianTestCase
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

        $config = [
            'private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

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
            'number' => '1527688127',
            'orderId' => '201807040000012397',
            'amount' => '10',
            'orderCreateDate' => '2018-07-04 09:20:40',
            'paymentVendorId' => '1098',
            'ip' => '192.168.101.1',
            'notify_url' => 'http://orz.zz/pay/reutrn.php',
            'verify_url' => 'payment.http.orz.zz',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $this->returnResult = [
            'mch_id' => '1527688127',
            'nonce' => '282ebcd74f324dfa843ff8f3aa8d0a7b',
            'out_trade_no' => '201807040000012397',
            'pay_time' => '20180704092052',
            'platform_trade_no' => 'TB20180704091448667866',
            'result_code' => 'SUCCESS',
            'timestamp' => '1530667252',
            'total_fee' => '1000',
            'trade_no' => '20180704091450979897',
            'trade_type' => 'ALIH5',
        ];

        $this->returnResult['sign'] = $this->rsaPrivateKeyEncrypt($this->returnResult);
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

        $tongLianAlipay = new TongLianAlipay();
        $tongLianAlipay->getVerifyData();
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

        $tongLianAlipay = new TongLianAlipay();
        $tongLianAlipay->setPrivateKey('test');
        $tongLianAlipay->getVerifyData();
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

        $tongLianAlipay = new TongLianAlipay();
        $tongLianAlipay->setPrivateKey('test');
        $tongLianAlipay->setOptions($this->option);
        $tongLianAlipay->getVerifyData();
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

        $tongLianAlipay = new TongLianAlipay();
        $tongLianAlipay->setPrivateKey('test');
        $tongLianAlipay->setOptions($this->option);
        $tongLianAlipay->getVerifyData();
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

        $tongLianAlipay = new TongLianAlipay();
        $tongLianAlipay->setContainer($this->container);
        $tongLianAlipay->setClient($this->client);
        $tongLianAlipay->setPrivateKey('test');
        $tongLianAlipay->setOptions($this->option);
        $tongLianAlipay->getVerifyData();
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
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $tongLianAlipay = new TongLianAlipay();
        $tongLianAlipay->setContainer($this->container);
        $tongLianAlipay->setClient($this->client);
        $tongLianAlipay->setResponse($response);
        $tongLianAlipay->setPrivateKey('test');
        $tongLianAlipay->setOptions($this->option);
        $tongLianAlipay->getVerifyData();
    }

    /**
     * 測試支付時返回statusCode不為200
     */
    public function testPayReturnStatusCodeNot200()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '支付金额最低限额3元',
            180130
        );

        $result = [
            'code' => 'WAF/BAD_REQUEST',
            'message' => '支付金额最低限额3元',
            'host_id' => 'api.jgrjnw.cn',
            'request_id' => '57989cf9-9577-4f54-b2a4-41a862e4e307',
            'server_time' => '2018-07-04T00:39:41.440 0000',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 500 Internal Server Error');
        $response->addHeader('Content-Type:application/json');

        $tongLianAlipay = new TongLianAlipay();
        $tongLianAlipay->setContainer($this->container);
        $tongLianAlipay->setClient($this->client);
        $tongLianAlipay->setResponse($response);
        $tongLianAlipay->setPrivateKey('test');
        $tongLianAlipay->setOptions($this->option);
        $tongLianAlipay->getVerifyData();
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
            'code' => 'WAF/BAD_REQUEST',
            'host_id' => 'api.jgrjnw.cn',
            'request_id' => '57989cf9-9577-4f54-b2a4-41a862e4e307',
            'server_time' => '2018-07-04T00:39:41.440 0000',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 500 Internal Server Error');
        $response->addHeader('Content-Type:application/json');

        $tongLianAlipay = new TongLianAlipay();
        $tongLianAlipay->setContainer($this->container);
        $tongLianAlipay->setClient($this->client);
        $tongLianAlipay->setResponse($response);
        $tongLianAlipay->setPrivateKey('test');
        $tongLianAlipay->setOptions($this->option);
        $tongLianAlipay->getVerifyData();
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

        $result = [
            'mch_id' => '1527688127',
            'nonce' => 'd7d9403e539941ae81ef25f46f0b971f',
            'trade_type' => 'ALIH5',
            'sign' => 'sign',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $tongLianAlipay = new TongLianAlipay();
        $tongLianAlipay->setContainer($this->container);
        $tongLianAlipay->setClient($this->client);
        $tongLianAlipay->setResponse($response);
        $tongLianAlipay->setPrivateKey('test');
        $tongLianAlipay->setOptions($this->option);
        $tongLianAlipay->getVerifyData();
    }

    /**
     * 測試支付寶二維支付
     */
    public function testAliQrcodePay()
    {
        $result = [
            'pay_url' => 'https://api.jgrjnw.cn/pay/form?prepay_id=1b9a8d6ae1ed4c50b37fdc1774ccfeed',
            'mch_id' => '1527688127',
            'nonce' => 'd7d9403e539941ae81ef25f46f0b971f',
            'trade_type' => 'ALIH5',
            'sign' => 'sign',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $tongLianAlipay = new TongLianAlipay();
        $tongLianAlipay->setContainer($this->container);
        $tongLianAlipay->setClient($this->client);
        $tongLianAlipay->setResponse($response);
        $tongLianAlipay->setPrivateKey('test');
        $tongLianAlipay->setOptions($this->option);
        $verifyData = $tongLianAlipay->getVerifyData();

        $this->assertEquals('https://api.jgrjnw.cn/pay/form', $verifyData['post_url']);
        $this->assertEquals('1b9a8d6ae1ed4c50b37fdc1774ccfeed', $verifyData['params']['prepay_id']);
        $this->assertEquals('GET', $tongLianAlipay->getPayMethod());
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

        $tongLianAlipay = new TongLianAlipay();
        $tongLianAlipay->setPrivateKey('test');
        $tongLianAlipay->verifyOrderPayment([]);
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

        $tongLianAlipay = new TongLianAlipay();
        $tongLianAlipay->setPrivateKey('test');
        $tongLianAlipay->setOptions($this->returnResult);
        $tongLianAlipay->verifyOrderPayment([]);
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

        $tongLianAlipay = new TongLianAlipay();
        $tongLianAlipay->setPrivateKey('test');
        $tongLianAlipay->setOptions($this->returnResult);
        $tongLianAlipay->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = $this->rsaPrivateKeyEncrypt($encodeData);

        $tongLianAlipay = new TongLianAlipay();
        $tongLianAlipay->setPrivateKey('test');
        $tongLianAlipay->setOptions($this->returnResult);
        $tongLianAlipay->verifyOrderPayment([]);
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

        $tongLianAlipay = new TongLianAlipay();
        $tongLianAlipay->setPrivateKey('test');
        $tongLianAlipay->setOptions($this->returnResult);
        $tongLianAlipay->verifyOrderPayment($entry);
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
            'id' => '201807040000012397',
            'amount' => '15',
        ];

        $tongLianAlipay = new TongLianAlipay();
        $tongLianAlipay->setPrivateKey('test');
        $tongLianAlipay->setOptions($this->returnResult);
        $tongLianAlipay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回成功
     */
    public function testReturnSuccess()
    {
        $entry = [
            'id' => '201807040000012397',
            'amount' => '10',
        ];

        $tongLianAlipay = new TongLianAlipay();
        $tongLianAlipay->setPrivateKey('test');
        $tongLianAlipay->setOptions($this->returnResult);
        $tongLianAlipay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $tongLianAlipay->getMsg());
    }

    /**
     * RSA加密
     *
     * @param array $encode 待加密陣列
     * @return string
     */
    private function rsaPrivateKeyEncrypt($encodeData)
    {
        $content = base64_decode($this->privateKey);
        $privateKey = openssl_pkey_get_private($content);

        ksort($encodeData);
        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        openssl_sign(md5($encodeStr), $sign, $privateKey, OPENSSL_ALGO_SHA256);

        return base64_encode($sign);
    }
}
