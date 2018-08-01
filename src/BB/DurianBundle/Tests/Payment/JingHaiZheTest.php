<?php

namespace BB\DurianBundle\Test\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\JingHaiZhe;
use Buzz\Message\Response;

class JingHaiZheTest extends DurianTestCase
{
    /**
     * 私鑰
     *
     * @var string
     */
    private $privateKey;

    /**
     * 公鑰
     *
     * @var string
     */
    private $publicKey;

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * @var \Buzz\Client\Curl
     */
    private $client;

    /**
     * 訂單參數
     *
     * @var array
     */
    private $options;

    /**
     * 返回結果
     *
     * @var array
     */
    private $returnResult;

    public function setUp()
    {
        parent::setUp();

        // Create the keypair
        $res = openssl_pkey_new();

        $privkey = '';
        // Get private key
        openssl_pkey_export($res, $privkey);

        $this->privateKey = base64_encode($privkey);

        // Get public key
        $pubkey = openssl_pkey_get_details($res);

        $this->publicKey = base64_encode($pubkey['key']);

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

        $this->options = [
            'number' => '500008249158',
            'amount' => '1',
            'orderId' => '201805080000011903',
            'paymentVendorId' => '1103',
            'notify_url' => 'http://www.seafood.help/',
            'verify_url' => 'payment.https.pay.jingmugukj.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'orderCreateDate' => '2018-05-08 15:42:13',
            'rsa_private_key' => $this->privateKey,
        ];

        $this->returnResult = [
            'ret' => json_encode([
                'code' => '1000',
                'msg' => 'SUCCESS',
            ]),
            'msg' => json_encode([
                'money' => 100,
                'orderDate' => '2018-05-08 16:38:00',
                'no' => '201805080000011903',
                'merchantNo' => '500008249158',
                'payNo' => '81805081637025973800',
                'remarks' => '201805080000011903',
            ]),
            'sign' => '',
            'rsa_public_key' => $this->publicKey,
        ];

        $encodeStr = $this->returnResult['ret'] . '|' . $this->returnResult['msg'];

        $content = base64_decode($this->privateKey);

        $sign = '';
        openssl_sign($encodeStr, $sign, $content);

        $this->returnResult['sign'] = base64_encode($sign);
    }

    /**
     * 測試支付時未指定支付參數
     */
    public function testPayWithoutPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $jingHaiZhe = new JingHaiZhe();
        $jingHaiZhe->getVerifyData();
    }

    /**
     * 測試支付時帶入支付平台不支援的銀行
     */
    public function testPayWithoutSupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $this->options['paymentVendorId'] = '9999';

        $jingHaiZhe = new JingHaiZhe();
        $jingHaiZhe->setOptions($this->options);
        $jingHaiZhe->getVerifyData();
    }

    /**
     * 測試加密產生簽名失敗
     */
    public function testGetEncodeGenerateSignatureFailure()
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

        // Create the keypair
        $res = openssl_pkey_new($config);

        $privateKey = '';
        // Get private key
        openssl_pkey_export($res, $privateKey);

        $this->options['rsa_private_key'] = base64_encode($privateKey);

        $jingHaiZhe = new JingHaiZhe();
        $jingHaiZhe->setOptions($this->options);
        $jingHaiZhe->getVerifyData();
    }

    /**
     * 測試加密時取得RSA私鑰為空
     */
    public function testReturnGetRsaPrivateKeyEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa private key is empty',
            180092
        );

        $this->options['rsa_private_key'] = '';

        $jingHaiZhe = new JingHaiZhe();
        $jingHaiZhe->setOptions($this->options);
        $jingHaiZhe->getVerifyData();
    }

    /**
     * 測試加密時取得RSA私鑰失敗
     */
    public function testReturnGetRsaPrivateKeyError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa private key failure',
            180093
        );

        $this->options['rsa_private_key'] = '12345';

        $jingHaiZhe = new JingHaiZhe();
        $jingHaiZhe->setOptions($this->options);
        $jingHaiZhe->getVerifyData();
    }

    /**
     * 測試二維支付取得Qrcode不成功
     */
    public function testQrcodePayGetQrcodeNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '参数异常，商户号数据异常',
            180130
        );

        $result = [
            'msg' => '参数异常，商户号数据异常',
            'code' => '2010',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $jingHaiZhe = new JingHaiZhe();
        $jingHaiZhe->setContainer($this->container);
        $jingHaiZhe->setClient($this->client);
        $jingHaiZhe->setResponse($response);
        $jingHaiZhe->setOptions($this->options);
        $jingHaiZhe->getVerifyData();
    }

    /**
     * 測試二維支付對外返回沒有qrcode
     */
    public function testQrcodePayWithoutQrcode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'backOrderId' => '81805081517061559774',
            'sign' => 'adpr+hdSZ9QOqOihOvYTU3h3vX84ATNn/e9PXk9oe15HLiwZvmQUtKeQ' .
                'Oc5xr/sUgwCbNwU8wMKwAQAOfmnGcqGSK9DhdAvrxoxsWenGp1W2rI1Kn/VXir' .
                '8TYz/Wv5VKYhA3FmCfV91/v8y3tYRHtWU0YjsQyV16IrRu7HL+zi8=',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $jingHaiZhe = new JingHaiZhe();
        $jingHaiZhe->setContainer($this->container);
        $jingHaiZhe->setClient($this->client);
        $jingHaiZhe->setResponse($response);
        $jingHaiZhe->setOptions($this->options);
        $jingHaiZhe->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $result = [
            'backQrCodeUrl' => 'https://qpay.qq.com/qr/57dd4225',
            'backOrderId' => '81805081517061559774',
            'sign' => 'adpr+hdSZ9QOqOihOvYTU3h3vX84ATNn/e9PXk9oe15HLiwZvmQUtKeQ' .
                'Oc5xr/sUgwCbNwU8wMKwAQAOfmnGcqGSK9DhdAvrxoxsWenGp1W2rI1Kn/VXir' .
                '8TYz/Wv5VKYhA3FmCfV91/v8y3tYRHtWU0YjsQyV16IrRu7HL+zi8=',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $jingHaiZhe = new JingHaiZhe();
        $jingHaiZhe->setContainer($this->container);
        $jingHaiZhe->setClient($this->client);
        $jingHaiZhe->setResponse($response);
        $jingHaiZhe->setOptions($this->options);
        $data = $jingHaiZhe->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://qpay.qq.com/qr/57dd4225', $jingHaiZhe->getQrcode());
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $result = [
            'backQrCodeUrl' => 'https://myun.tenpay.com/mqq/pay/qrcode.html?' .
                '_wv=1027&_bid=2183&t=5V28a0accb8f0c651cc0b38af71fb6a8',
            'backOrderId' => '81805090911078292607',
            'sign' => 'WeCYeF3QJpgq7ggcjSCf9yTf4HkovKA26W4oVr9CBRzQuVCfWZoBZ27l' .
                '1WNEZIvEkXmOiuC9Wx7gT57jeoKO8gAjm16DhEyKlPisKAKPYn945OsN 7BeLR' .
                'l td5D40sk1w06GI7jJs0xChqtOG5xvGuQMJ0AeUL2nD2zFBvDOlY=',
        ];

        $this->options['paymentVendorId'] = '1104';

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $jingHaiZhe = new JingHaiZhe();
        $jingHaiZhe->setContainer($this->container);
        $jingHaiZhe->setClient($this->client);
        $jingHaiZhe->setResponse($response);
        $jingHaiZhe->setOptions($this->options);
        $data = $jingHaiZhe->getVerifyData();

        $this->assertEquals('https://myun.tenpay.com/mqq/pay/qrcode.html', $data['post_url']);
        $this->assertEquals('1027', $data['params']['_wv']);
        $this->assertEquals('2183', $data['params']['_bid']);
        $this->assertEquals('5V28a0accb8f0c651cc0b38af71fb6a8', $data['params']['t']);
        $this->assertEquals('GET', $jingHaiZhe->getPayMethod());
    }

    /**
     * 測試京東手機支付
     */
    public function testJDWapPay()
    {
        $this->options['paymentVendorId'] = '1108';

        $jingHaiZhe = new JingHaiZhe();
        $jingHaiZhe->setOptions($this->options);
        $data = $jingHaiZhe->getVerifyData();

        $sign = '';

        $encodeStr = '500008249158|201805080000011903|100|http://www.seafood.help/|' .
            'http://www.seafood.help/|1525765333||201805080000011903|' .
            '201805080000011903|201805080000011903';

        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $this->assertEquals('500008249158', $data['merchantNo']);
        $this->assertEquals('201805080000011903', $data['requestNo']);
        $this->assertEquals(100, $data['amount']);
        $this->assertEquals('6018', $data['payMethod']);
        $this->assertEquals('http://www.seafood.help/', $data['pageUrl']);
        $this->assertEquals('http://www.seafood.help/', $data['backUrl']);
        $this->assertEquals(1525765333, $data['payDate']);
        $this->assertEquals('', $data['agencyCode']);
        $this->assertEquals('201805080000011903', $data['remark1']);
        $this->assertEquals('201805080000011903', $data['remark2']);
        $this->assertEquals('201805080000011903', $data['remark3']);
        $this->assertEquals(base64_encode($sign), $data['signature']);
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

        $jingHaiZhe = new JingHaiZhe();
        $jingHaiZhe->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少必要返回參數
     */
    public function testReturnWithoutRequireParameters()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $this->returnResult['ret'] = '{}';

        $jingHaiZhe = new JingHaiZhe();
        $jingHaiZhe->setOptions($this->returnResult);
        $jingHaiZhe->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台缺少回傳sign(加密簽名)
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['sign']);

        $jingHaiZhe = new JingHaiZhe();
        $jingHaiZhe->setOptions($this->returnResult);
        $jingHaiZhe->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = 'fail';

        $jingHaiZhe = new JingHaiZhe();
        $jingHaiZhe->setOptions($this->returnResult);
        $jingHaiZhe->verifyOrderPayment([]);
    }

    /**
     * 測試返回時取得RSA公鑰為空
     */
    public function testReturnGetRsaPublicKeyEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa public key is empty',
            180095
        );

        $this->returnResult['rsa_public_key'] = '';

        $jingHaiZhe = new JingHaiZhe();
        $jingHaiZhe->setOptions($this->returnResult);
        $jingHaiZhe->verifyOrderPayment([]);
    }

    /**
     * 測試返回時取得RSA公鑰失敗
     */
    public function testReturnGetRsaPublicKeyError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa public key failure',
            180096
        );

        $this->returnResult['rsa_public_key'] = '123';

        $jingHaiZhe = new JingHaiZhe();
        $jingHaiZhe->setOptions($this->returnResult);
        $jingHaiZhe->verifyOrderPayment([]);
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

        $content = base64_decode($this->privateKey);

        $encodeStr = '{"code":"9003","msg":"pay failure"}|{"money":100,"orderDate":"2018-05-08 16:38:00",' .
            '"no":"201805080000011903","merchantNo":"500008249158","payNo":"81805081637025973800",' .
            '"remarks":"201805080000011903"}';

        $sign = '';
        openssl_sign($encodeStr, $sign, $content);

        $this->returnResult['ret'] = json_encode([
            'code' => '9003',
            'msg' => 'pay failure',
        ]);
        $this->returnResult['sign'] = base64_encode($sign);

        $jingHaiZhe = new JingHaiZhe();
        $jingHaiZhe->setOptions($this->returnResult);
        $jingHaiZhe->verifyOrderPayment([]);
    }

    /**
     * 測試返回單號不正確的情況
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $entry = ['id' => '2014052200123'];

        $jingHaiZhe = new JingHaiZhe();
        $jingHaiZhe->setOptions($this->returnResult);
        $jingHaiZhe->verifyOrderPayment($entry);
    }

    /**
     * 測試返回金額不正確的情況
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $entry = [
            'id' => '201805080000011903',
            'amount' => '50',
        ];

        $jingHaiZhe = new JingHaiZhe();
        $jingHaiZhe->setOptions($this->returnResult);
        $jingHaiZhe->verifyOrderPayment($entry);
    }

    /**
     * 測試返回支付驗證成功
     */
    public function testPaySuccess()
    {
        $entry = [
            'id' => '201805080000011903',
            'amount' => '1',
        ];

        $jingHaiZhe = new JingHaiZhe();
        $jingHaiZhe->setOptions($this->returnResult);
        $jingHaiZhe->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $jingHaiZhe->getMsg());
    }
}
