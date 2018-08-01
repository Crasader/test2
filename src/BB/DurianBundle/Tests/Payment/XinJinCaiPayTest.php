<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\XinJinCaiPay;
use Buzz\Message\Response;

class XinJinCaiPayTest extends DurianTestCase
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
            'number' => '8046',
            'orderId' => '201807040000012194',
            'amount' => '1',
            'paymentVendorId' => '1111',
            'verify_url' => 'payment.http.orz.zz',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'rsa_private_key' => $this->privateKey,
            'notify_url' => 'http://orz.zz/pay/reutrn.php',
        ];

        $this->returnResult = [
            'status' => '1',
            'company_oid' => '8046',
            'order_id' => '201807040000012194',
            'order_abc' => '782019906379210752',
            'amount' => '100',
        ];

        $this->returnResult['sign'] = $this->rsaPrivateKeyEncrypt($this->returnResult);
        $this->returnResult['rsa_public_key'] = $publicKey;
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

        $xinJinCaiPay = new XinJinCaiPay();
        $xinJinCaiPay->getVerifyData();
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

        $xinJinCaiPay = new XinJinCaiPay();
        $xinJinCaiPay->setOptions($this->option);
        $xinJinCaiPay->getVerifyData();
    }

    /**
     * 測試支付時取得商家私鑰為空字串
     */
    public function testPayGetRsaPrivateKeyEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa private key is empty',
            180092
        );

        $this->option['rsa_private_key'] = '';

        $xinJinCaiPay = new XinJinCaiPay();
        $xinJinCaiPay->setOptions($this->option);
        $xinJinCaiPay->getVerifyData();
    }

    /**
     * 測試支付時取得商家私鑰失敗
     */
    public function testPayGetRsaPrivateKeyError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa private key failure',
            180093
        );

        $this->option['rsa_private_key'] = '123456789';

        $xinJinCaiPay = new XinJinCaiPay();
        $xinJinCaiPay->setOptions($this->option);
        $xinJinCaiPay->getVerifyData();
    }

    /**
     * 測試支付時產生簽名失敗
     */
    public function testPayGenerateSignatureFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Generate signature failure',
            180144
        );

        $config = [
            'private_key_bits' => 384,
            'private_key_type' => OPENSSL_KEYTYPE_DH,
        ];

        $res = openssl_pkey_new($config);

        $privatekey = '';
        openssl_pkey_export($res, $privatekey);
        $this->privateKey = base64_encode($privatekey);

        $this->option['rsa_private_key'] = base64_encode($privatekey);

        $xinJinCaiPay = new XinJinCaiPay();
        $xinJinCaiPay->setOptions($this->option);
        $xinJinCaiPay->getVerifyData();
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

        $xinJinCaiPay = new XinJinCaiPay();
        $xinJinCaiPay->setOptions($this->option);
        $xinJinCaiPay->getVerifyData();
    }

    /**
     * 測試支付時返回缺少status
     */
    public function testPayReturnWithoutStatus()
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

        $xinJinCaiPay = new XinJinCaiPay();
        $xinJinCaiPay->setContainer($this->container);
        $xinJinCaiPay->setClient($this->client);
        $xinJinCaiPay->setResponse($response);
        $xinJinCaiPay->setOptions($this->option);
        $xinJinCaiPay->getVerifyData();
    }

    /**
     * 測試支付時返回status非1且非2
     */
    public function testPayReturnStatusNotOneAndNotTwo()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '验签失败',
            180130
        );

        $result = [
            'status' => '208003',
            'message' => '验签失败',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $xinJinCaiPay = new XinJinCaiPay();
        $xinJinCaiPay->setContainer($this->container);
        $xinJinCaiPay->setClient($this->client);
        $xinJinCaiPay->setResponse($response);
        $xinJinCaiPay->setOptions($this->option);
        $xinJinCaiPay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $result = ['status' => '404'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $xinJinCaiPay = new XinJinCaiPay();
        $xinJinCaiPay->setContainer($this->container);
        $xinJinCaiPay->setClient($this->client);
        $xinJinCaiPay->setResponse($response);
        $xinJinCaiPay->setOptions($this->option);
        $xinJinCaiPay->getVerifyData();
    }

    /**
     * 測試支付時返回缺少content
     */
    public function testPayReturnWithoutContent()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = ['status' => '2'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $xinJinCaiPay = new XinJinCaiPay();
        $xinJinCaiPay->setContainer($this->container);
        $xinJinCaiPay->setClient($this->client);
        $xinJinCaiPay->setResponse($response);
        $xinJinCaiPay->setOptions($this->option);
        $xinJinCaiPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $qrcode = 'https://qr.95516.com/00010000/62822320309845063437399960815583';
        $result = [
            'amount' => '10000',
            'company_oid' => '8046',
            'content' => $qrcode,
            'message' => '下单成功。',
            'order_abc' => '781620832069115904',
            'order_id' => '201807040000012194',
            'order_name' => '201807040000012194',
            'pay_type' => '5',
            'status' => '2',
            'sign' => 'hPkbedTTsyLtqrrIE18H8eQO4Pf/qpfnPeqVCoBsjWYIQYO/SzjaBN7efffG77Kv7i8hipET9havxmwI1v7sGrIy9GD7IK' .
                'gEJbpPSUdXTBodlTwqzE7RlljDA2kOF6ZLToSrszthHSk6pEHs5FV9MIjqf6tgo7RPr75at3k=',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $xinJinCaiPay = new XinJinCaiPay();
        $xinJinCaiPay->setContainer($this->container);
        $xinJinCaiPay->setClient($this->client);
        $xinJinCaiPay->setResponse($response);
        $xinJinCaiPay->setOptions($this->option);
        $verifyData = $xinJinCaiPay->getVerifyData();

        $this->assertEmpty($verifyData);
        $this->assertEquals($qrcode, $xinJinCaiPay->getQrcode());
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $result = [
            'amount' => '10000',
            'company_oid' => '8046',
            'content' => 'weixin://wxpay/bizpayurl?pr=tFcz77r',
            'message' => '下单成功。',
            'order_abc' => '781590362044260352',
            'order_id' => '201807040000012170',
            'order_name' => '201807040000012170',
            'pay_type' => '38',
            'status' => '2',
            'sign' => 'QPQ3tJ4TUE/kzHVDitFYSE0IHdTpq3ge3w3CI17mwgpNUw2DL6bhfCuW3uhjeASMUccxm9XUGVmiMX9m9gvs7KazUctztP' .
                'dwEQ5QDKMdb6UAI1QNQXRFXhIlmUpBPvVNXGxG2IyBIUPbF2a7kMc925h0Hy8Hw9ZwQT0YwNUA=',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $this->option['paymentVendorId'] = '1097';

        $xinJinCaiPay = new XinJinCaiPay();
        $xinJinCaiPay->setContainer($this->container);
        $xinJinCaiPay->setClient($this->client);
        $xinJinCaiPay->setResponse($response);
        $xinJinCaiPay->setOptions($this->option);
        $verifyData = $xinJinCaiPay->getVerifyData();

        $this->assertEquals('GET', $xinJinCaiPay->getPayMethod());
        $this->assertEquals('weixin://wxpay/bizpayurl', $verifyData['post_url']);
        $this->assertEquals('tFcz77r', $verifyData['params']['pr']);
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

        $xinJinCaiPay = new XinJinCaiPay();
        $xinJinCaiPay->verifyOrderPayment([]);
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

        $xinJinCaiPay = new XinJinCaiPay();
        $xinJinCaiPay->setOptions($this->returnResult);
        $xinJinCaiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時取得商家公鑰為空字串
     */
    public function testReturnGetRsaPublicKeyEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa public key is empty',
            180095
        );

        $this->returnResult['rsa_public_key'] = '';

        $xinJinCaiPay = new XinJinCaiPay();
        $xinJinCaiPay->setOptions($this->returnResult);
        $xinJinCaiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時取得商家公鑰失敗
     */
    public function testReturnGetRsaPublicKeyError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa public key failure',
            180096
        );

        $this->returnResult['rsa_public_key'] = '123456789';

        $xinJinCaiPay = new XinJinCaiPay();
        $xinJinCaiPay->setOptions($this->returnResult);
        $xinJinCaiPay->verifyOrderPayment([]);
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

        $xinJinCaiPay = new XinJinCaiPay();
        $xinJinCaiPay->setOptions($this->returnResult);
        $xinJinCaiPay->verifyOrderPayment([]);
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

        $this->returnResult['status'] = '0';

        $encodeData = $this->returnResult;
        unset($encodeData['rsa_public_key']);
        unset($encodeData['sign']);

        $this->returnResult['sign'] = $this->rsaPrivateKeyEncrypt($encodeData);

        $xinJinCaiPay = new XinJinCaiPay();
        $xinJinCaiPay->setOptions($this->returnResult);
        $xinJinCaiPay->verifyOrderPayment([]);
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

        $entry = ['id' => '301807040000012194'];

        $xinJinCaiPay = new XinJinCaiPay();
        $xinJinCaiPay->setOptions($this->returnResult);
        $xinJinCaiPay->verifyOrderPayment($entry);
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
            'id' => '201807040000012194',
            'amount' => '15',
        ];

        $xinJinCaiPay = new XinJinCaiPay();
        $xinJinCaiPay->setOptions($this->returnResult);
        $xinJinCaiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回成功
     */
    public function testReturnSuccess()
    {
        $entry = [
            'id' => '201807040000012194',
            'amount' => '1',
        ];

        $xinJinCaiPay = new XinJinCaiPay();
        $xinJinCaiPay->setOptions($this->returnResult);
        $xinJinCaiPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $xinJinCaiPay->getMsg());
    }

    /**
     * RSA私鑰加密
     *
     * @param array $encodeData 加密參數
     * @return string
     */
    private function rsaPrivateKeyEncrypt($encodeData)
    {
        ksort($encodeData);
        $encodeStr = json_encode($encodeData, JSON_UNESCAPED_UNICODE);

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey), OPENSSL_ALGO_SHA1);

        return base64_encode($sign);
    }
}
