<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\ShanFuTongPay;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class ShanFuTongPayTest extends DurianTestCase
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * @var \Buzz\Client\Curl
     */
    private $client;

    /**
     * 公鑰
     *
     * @var string
     */
    private $publicKey;

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

        $mockLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Payment')
            ->disableOriginalConstructor()
            ->setMethods(['record'])
            ->getMock();

        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();
        $this->container->expects($this->any())
            ->method('get')
            ->willReturn($mockLogger);

        $this->client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $config = [
            'private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        // Create the keypair
        $res = openssl_pkey_new($config);

        // Get public key
        $pubkey = openssl_pkey_get_details($res);

        $this->publicKey = base64_encode($pubkey['key']);

        $this->option = [
            'number' => '9527',
            'orderId' => '201806150000046528',
            'amount' => '10',
            'paymentVendorId' => '1',
            'notify_url' => 'http://www.seafood.help/',
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.xgpay.cc',
        ];

        $this->returnResult = [
            'code' => '0000',
            'msg' => '成功.',
            'order_sn' => '20180615185340219232',
            'down_sn' => '201806150000046528',
            'status' => '2',
            'amount' => '10.00',
            'fee' => '0.07',
            'trans_time' => '20180615185533',
            'sign' => '03de666e760760cff4cd25b382f03d8b',
        ];
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

        $shanFuTongPay = new ShanFuTongPay();
        $shanFuTongPay->getVerifyData();
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

        $shanFuTongPay = new ShanFuTongPay();
        $shanFuTongPay->setPrivateKey('test');
        $shanFuTongPay->getVerifyData();
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

        $this->option['paymentVendorId'] = '999';

        $shanFuTongPay = new ShanFuTongPay();
        $shanFuTongPay->setPrivateKey('test');
        $shanFuTongPay->setOptions($this->option);
        $shanFuTongPay->getVerifyData();
    }

    /**
     * 測試支付時取得RSA公鑰為空字串
     */
    public function testPayGetRsaPublicKeyEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa public key is empty',
            180095
        );

        $this->option['rsa_public_key'] = '';

        $shanFuTongPay = new ShanFuTongPay();
        $shanFuTongPay->setPrivateKey('test');
        $shanFuTongPay->setOptions($this->option);
        $shanFuTongPay->getVerifyData();
    }

    /**
     * 測試支付時取得RSA公鑰失敗
     */
    public function testPayGetRsaPublicKeyError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa public key failure',
            180096
        );

        $this->option['rsa_public_key'] = '123456';

        $shanFuTongPay = new ShanFuTongPay();
        $shanFuTongPay->setPrivateKey('test');
        $shanFuTongPay->setOptions($this->option);
        $shanFuTongPay->getVerifyData();
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

        $this->option['verify_url'] = '';

        $shanFuTongPay = new ShanFuTongPay();
        $shanFuTongPay->setPrivateKey('test');
        $shanFuTongPay->setOptions($this->option);
        $shanFuTongPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回code
     */
    public function testPayReturnWithoutCode()
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
        $response->addHeader('Content-Type:application/json');

        $shanFuTongPay = new ShanFuTongPay();
        $shanFuTongPay->setContainer($this->container);
        $shanFuTongPay->setClient($this->client);
        $shanFuTongPay->setResponse($response);
        $shanFuTongPay->setPrivateKey('test');
        $shanFuTongPay->setOptions($this->option);
        $shanFuTongPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回msg
     */
    public function testPayReturnWithoutMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = ['code' => '0000'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $shanFuTongPay = new ShanFuTongPay();
        $shanFuTongPay->setContainer($this->container);
        $shanFuTongPay->setClient($this->client);
        $shanFuTongPay->setResponse($response);
        $shanFuTongPay->setPrivateKey('test');
        $shanFuTongPay->setOptions($this->option);
        $shanFuTongPay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '小于最小限额.',
            180130
        );

        $result = [
            'code' => '1005',
            'msg' => '小于最小限额.',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $shanFuTongPay = new ShanFuTongPay();
        $shanFuTongPay->setContainer($this->container);
        $shanFuTongPay->setClient($this->client);
        $shanFuTongPay->setResponse($response);
        $shanFuTongPay->setPrivateKey('test');
        $shanFuTongPay->setOptions($this->option);
        $shanFuTongPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回code_url
     */
    public function testPayReturnWithoutCodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'code' => '0000',
            'msg' => '成功.',
            'order_sn' => '20180615185340219232',
            'down_sn' => '201806150000046528',
            'sign' => '8fd024c995eec10053725ee35909229d',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $shanFuTongPay = new ShanFuTongPay();
        $shanFuTongPay->setContainer($this->container);
        $shanFuTongPay->setClient($this->client);
        $shanFuTongPay->setResponse($response);
        $shanFuTongPay->setPrivateKey('test');
        $shanFuTongPay->setOptions($this->option);
        $shanFuTongPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $this->option['paymentVendorId'] = '1111';

        $result = [
            'code' => '0000',
            'msg' => '成功.',
            'order_sn' => '20180615185340219232',
            'down_sn' => '201806150000046528',
            'code_url' => 'https://qr.95516.com/00010000/62029034612121645337325891426240',
            'sign' => '0bfdd8f826d5cb7ec10bad9884c74748',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $shanFuTongPay = new ShanFuTongPay();
        $shanFuTongPay->setContainer($this->container);
        $shanFuTongPay->setClient($this->client);
        $shanFuTongPay->setResponse($response);
        $shanFuTongPay->setPrivateKey('test');
        $shanFuTongPay->setOptions($this->option);
        $data = $shanFuTongPay->getVerifyData();

        $qrcode = 'https://qr.95516.com/00010000/62029034612121645337325891426240';

        $this->assertEmpty($data);
        $this->assertEquals($qrcode, $shanFuTongPay->getQrcode());
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $result = [
            'code' => '0000',
            'msg' => '成功.',
            'order_sn' => '20180615185340219232',
            'down_sn' => '201806150000046528',
            'code_url' => 'http://www.bbp988.com/bj591goto.php?token=ZdP_XJ6BHMM-ESW3GCuCepwxI1kc2tJrUE',
            'sign' => '8fd024c995eec10053725ee35909229d',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $shanFuTongPay = new ShanFuTongPay();
        $shanFuTongPay->setContainer($this->container);
        $shanFuTongPay->setClient($this->client);
        $shanFuTongPay->setResponse($response);
        $shanFuTongPay->setPrivateKey('test');
        $shanFuTongPay->setOptions($this->option);
        $data = $shanFuTongPay->getVerifyData();

        $this->assertEquals('http://www.bbp988.com/bj591goto.php', $data['post_url']);
        $this->assertEquals('ZdP_XJ6BHMM-ESW3GCuCepwxI1kc2tJrUE', $data['params']['token']);
        $this->assertEquals('GET', $shanFuTongPay->getPayMethod());
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

        $shanFuTongPay = new ShanFuTongPay();
        $shanFuTongPay->verifyOrderPayment([]);
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

        $shanFuTongPay = new ShanFuTongPay();
        $shanFuTongPay->setPrivateKey('test');
        $shanFuTongPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未返回code
     */
    public function testReturnWithoutCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['code']);

        $shanFuTongPay = new ShanFuTongPay();
        $shanFuTongPay->setPrivateKey('test');
        $shanFuTongPay->setOptions($this->returnResult);
        $shanFuTongPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未返回msg
     */
    public function testReturnWithoutMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['msg']);

        $shanFuTongPay = new ShanFuTongPay();
        $shanFuTongPay->setPrivateKey('test');
        $shanFuTongPay->setOptions($this->returnResult);
        $shanFuTongPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時返回提交失敗
     */
    public function testReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '业务失败',
            180130
        );

        $this->returnResult['code'] = '1007';
        $this->returnResult['msg'] = '业务失败';

        $shanFuTongPay = new ShanFuTongPay();
        $shanFuTongPay->setPrivateKey('test');
        $shanFuTongPay->setOptions($this->returnResult);
        $shanFuTongPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未返回簽名
     */
    public function testReturnWithoutSignature()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['sign']);

        $shanFuTongPay = new ShanFuTongPay();
        $shanFuTongPay->setPrivateKey('test');
        $shanFuTongPay->setOptions($this->returnResult);
        $shanFuTongPay->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = '24dedd6ba8e302810e3514cc97fd0879';

        $shanFuTongPay = new ShanFuTongPay();
        $shanFuTongPay->setPrivateKey('test');
        $shanFuTongPay->setOptions($this->returnResult);
        $shanFuTongPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未支付
     */
    public function testReturnUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $this->returnResult['status'] = '0';
        $this->returnResult['sign'] = '67d691001be8ea5ef685aca815cc99c1';

        $shanFuTongPay = new ShanFuTongPay();
        $shanFuTongPay->setPrivateKey('test');
        $shanFuTongPay->setOptions($this->returnResult);
        $shanFuTongPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時訂單支付中
     */
    public function testReturnOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $this->returnResult['status'] = '1';
        $this->returnResult['sign'] = '467b8376c611a296d1c0c2e1435811b8';

        $shanFuTongPay = new ShanFuTongPay();
        $shanFuTongPay->setPrivateKey('test');
        $shanFuTongPay->setOptions($this->returnResult);
        $shanFuTongPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付失敗
     */
    public function testReturnWithPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $this->returnResult['status'] = '3';
        $this->returnResult['sign'] = '61a19d91cee35be879cf1b8796afe7eb';

        $shanFuTongPay = new ShanFuTongPay();
        $shanFuTongPay->setPrivateKey('test');
        $shanFuTongPay->setOptions($this->returnResult);
        $shanFuTongPay->verifyOrderPayment([]);
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

        $entry = ['id' => '9453'];

        $shanFuTongPay = new ShanFuTongPay();
        $shanFuTongPay->setPrivateKey('test');
        $shanFuTongPay->setOptions($this->returnResult);
        $shanFuTongPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為金額錯誤
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $entry = [
            'id' => '201806150000046528',
            'amount' => '123',
        ];

        $shanFuTongPay = new ShanFuTongPay();
        $shanFuTongPay->setPrivateKey('test');
        $shanFuTongPay->setOptions($this->returnResult);
        $shanFuTongPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $entry = [
            'id' => '201806150000046528',
            'amount' => '10',
        ];

        $shanFuTongPay = new ShanFuTongPay();
        $shanFuTongPay->setPrivateKey('test');
        $shanFuTongPay->setOptions($this->returnResult);
        $shanFuTongPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $shanFuTongPay->getMsg());
    }

    /**
     * RSA私鑰加密
     *
     * @param array $encodeData 欲加密的陣列
     * @return string
     */
    private function rsaPrivateKeyEncrypt($encodeData)
    {
        $content = trim(base64_decode($this->privateKey));
        $privateKey = openssl_pkey_get_private($content);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        openssl_sign($encodeStr, $sign, $privateKey, OPENSSL_ALGO_SHA1);

        return base64_encode($sign);
    }
}
