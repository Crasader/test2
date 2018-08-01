<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\JiuTongPay;
use Buzz\Message\Response;

class JiuTongPayTest extends DurianTestCase
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
     * 私鑰
     *
     * @var string
     */
    private $rsaPrivateKey;

    /**
     * 公鑰
     *
     * @var string
     */
    private $rsaPublicKey;

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

        $config = [
            'private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        // Create the keypair
        $res = openssl_pkey_new($config);

        $privkey = '';

        // Get private key
        openssl_pkey_export($res, $privkey);
        $this->rsaPrivateKey = base64_encode($privkey);

        // Get public key
        $pubkey = openssl_pkey_get_details($res);
        $this->rsaPublicKey = base64_encode($pubkey['key']);
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

        $jiuTongPay = new JiuTongPay();
        $jiuTongPay->getVerifyData();
    }

    /**
     * 測試加密基本參數設定未指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $jiuTongPay = new JiuTongPay();

        $jiuTongPay->setPrivateKey('1234');
        $jiuTongPay->getVerifyData();
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
            'orderId' => '201709070000000831',
            'number' => '123456',
            'paymentVendorId' => '9999',
            'amount' => '100',
            'notify_url' => 'http://payment/return.php',
        ];

        $jiuTongPay = new JiuTongPay();
        $jiuTongPay->setPrivateKey('1234');
        $jiuTongPay->setOptions($sourceData);
        $jiuTongPay->getVerifyData();
    }

    /**
     * 測試支付時取得RSA公鑰為空
     */
    public function testPayGetRsaPublicKeyEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa public key is empty',
            180095
        );

        $sourceData = [
            'orderId' => '201803070000000831',
            'number' => '123456',
            'paymentVendorId' => '1',
            'amount' => '100',
            'notify_url' => 'http://payment/return.php',
            'orderCreateDate' => '2018-03-07 10:30:20',
            'rsa_public_key' => '',
        ];

        $jiuTongPay = new JiuTongPay();
        $jiuTongPay->setPrivateKey('1234');
        $jiuTongPay->setOptions($sourceData);
        $jiuTongPay->getVerifyData();
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

        $sourceData = [
            'orderId' => '201803070000000831',
            'number' => '123456',
            'paymentVendorId' => '1',
            'amount' => '100',
            'notify_url' => 'http://payment/return.php',
            'orderCreateDate' => '2018-03-08 10:30:20',
            'rsa_public_key' => 'public_key_test',
        ];

        $jiuTongPay = new JiuTongPay();
        $jiuTongPay->setPrivateKey('1234');
        $jiuTongPay->setOptions($sourceData);
        $jiuTongPay->getVerifyData();
    }

    /**
     * 測試支付時未返回stateCode
     */
    public function testPayNoReturnStateCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'merNo' => 'JTZF800210',
            'msg' => '提交成功',
            'orderNum' => '201803150000010912',
            'qrcodeUrl' => 'http://api.jiutongpay.com/api/eBankPay.action?pr=S5V6QpL',
            'sign' => '7C4DDF508171A03DB78C283A6BAA58A6',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'JTZF800210',
            'paymentVendorId' => '1',
            'amount' => '1.00',
            'orderId' => '201803150000010912',
            'notify_url' => 'http://yes9527.com',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'rsa_public_key' => $this->rsaPublicKey,
        ];

        $jiuTongPay = new JiuTongPay();
        $jiuTongPay->setContainer($this->container);
        $jiuTongPay->setClient($this->client);
        $jiuTongPay->setResponse($response);
        $jiuTongPay->setPrivateKey('test');
        $jiuTongPay->setOptions($sourceData);
        $jiuTongPay->getVerifyData();
    }

    /**
     * 測試支付失敗
     */
    public function testPayFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '金額無效',
            180130
        );

        $result = [
            'stateCode' => '99',
            'msg' => '金額無效',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'JTZF800210',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201707190000009453',
            'notify_url' => 'http://yes9527.com',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'rsa_public_key' => $this->rsaPublicKey,
        ];

        $jiuTongPay = new JiuTongPay();
        $jiuTongPay->setContainer($this->container);
        $jiuTongPay->setClient($this->client);
        $jiuTongPay->setResponse($response);
        $jiuTongPay->setPrivateKey('test');
        $jiuTongPay->setOptions($sourceData);
        $jiuTongPay->getVerifyData();
    }

    /**
     * 測試支付時未返回qrcodeUrl
     */
    public function testPayNoReturnQrcodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'merNo' => 'JTZF800210',
            'msg' => '提交成功',
            'orderNum' => '201803150000010912',
            'sign' => '7C4DDF508171A03DB78C283A6BAA58A6',
            'stateCode' => '00',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'JTZF800210',
            'paymentVendorId' => '1',
            'amount' => '1.00',
            'orderId' => '201707190000009453',
            'notify_url' => 'http://yes9527.com',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'rsa_public_key' => $this->rsaPublicKey,
        ];

        $jiuTongPay = new JiuTongPay();
        $jiuTongPay->setContainer($this->container);
        $jiuTongPay->setClient($this->client);
        $jiuTongPay->setResponse($response);
        $jiuTongPay->setPrivateKey('test');
        $jiuTongPay->setOptions($sourceData);
        $jiuTongPay->getVerifyData();
    }

    /**
     * 測試微信WAP支付對外返回提交網址格式錯誤
     */
    public function testWxWapPayReturnQueryFormatError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'merNo' => 'JTZF800210',
            'msg' => '提交成功',
            'orderNum' => '201803150000010912',
            'qrcodeUrl' => '://api.jiutongpay.com/api/eBankPay.action?token=S5V6QpL',
            'sign' => '7C4DDF508171A03DB78C283A6BAA58A6',
            'stateCode' => '00',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'JTZF800210',
            'paymentVendorId' => '1',
            'amount' => '1.00',
            'orderId' => '201707190000009453',
            'notify_url' => 'http://yes9527.com',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'rsa_public_key' => $this->rsaPublicKey,
        ];

        $jiuTongPay = new JiuTongPay();
        $jiuTongPay->setContainer($this->container);
        $jiuTongPay->setClient($this->client);
        $jiuTongPay->setResponse($response);
        $jiuTongPay->setPrivateKey('test');
        $jiuTongPay->setOptions($sourceData);
        $jiuTongPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testBankPay()
    {
        $result = [
            'merNo' => 'JTZF800210',
            'msg' => '提交成功',
            'orderNum' => '201707190000009453',
            'qrcodeUrl' => 'http://api.jiutongpay.com/api/eBankPay.action?token=S5V6QpL',
            'sign' => '08EC37F28D0AE78D1217DA752531B0B3',
            'stateCode' => '00',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'JTZF800210',
            'paymentVendorId' => '1',
            'amount' => '1.00',
            'orderId' => '201707190000009453',
            'notify_url' => 'http://yes9527.com',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'rsa_public_key' => $this->rsaPublicKey,
        ];

        $chingYiPay = new JiuTongPay();
        $chingYiPay->setContainer($this->container);
        $chingYiPay->setClient($this->client);
        $chingYiPay->setResponse($response);
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->getVerifyData();
        $data = $chingYiPay->getVerifyData();

        $this->assertEquals('http://api.jiutongpay.com/api/eBankPay.action', $data['post_url']);
        $this->assertEquals('S5V6QpL', $data['params']['token']);
    }

    /**
     * 測試支付沒有Query
     */
    public function testBankPayWithoutQuery()
    {
        $result = [
            'merNo' => 'JTZF800210',
            'msg' => '提交成功',
            'orderNum' => '201707190000009453',
            'qrcodeUrl' => 'http://api.jiutongpay.com/api/eBankPay.action',
            'sign' => '08EC37F28D0AE78D1217DA752531B0B3',
            'stateCode' => '00',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'JTZF800210',
            'paymentVendorId' => '1',
            'amount' => '1.00',
            'orderId' => '201707190000009453',
            'notify_url' => 'http://yes9527.com',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'rsa_public_key' => $this->rsaPublicKey,
        ];

        $chingYiPay = new JiuTongPay();
        $chingYiPay->setContainer($this->container);
        $chingYiPay->setClient($this->client);
        $chingYiPay->setResponse($response);
        $chingYiPay->setPrivateKey('test');
        $chingYiPay->setOptions($sourceData);
        $chingYiPay->getVerifyData();
        $data = $chingYiPay->getVerifyData();

        $this->assertEquals('http://api.jiutongpay.com/api/eBankPay.action', $data['post_url']);
        $this->assertEmpty($data['params']);
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

        $jiuTongPay = new JiuTongPay();
        $jiuTongPay->verifyOrderPayment([]);
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

        $jiuTongPay = new JiuTongPay();
        $jiuTongPay->setPrivateKey('1234');
        $jiuTongPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時取得商家私鑰為空字串
     */
    public function testReturnGetRsaPrivateKeyEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa private key is empty',
            180092
        );

        $sourceData = [
            'data' => 'U7IfV1i1PUMmfw8ovIJpO37bMAyOBD1hhPzGUc6YDwYS3tGOK7B',
            'merchNo' => 'JTZF800210',
            'orderNum' => '201709070000000831',
            'rsa_private_key' => '',
        ];

        $jiuTongPay = new JiuTongPay();
        $jiuTongPay->setPrivateKey('1234');
        $jiuTongPay->setOptions($sourceData);
        $jiuTongPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時取得商家私鑰失敗
     */
    public function testReturnGetRsaPrivateKeyError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa private key failure',
            180093
        );

        $sourceData = [
            'data' => 'U7IfV1i1PUMmfw8ovIJpO37bMAyOBD1hhPzGUc6YDwYS3tGOK7B',
            'merchNo' => 'JTZF800210',
            'orderNum' => '201709070000000831',
            'rsa_private_key' => '123456',
        ];

        $jiuTongPay = new JiuTongPay();
        $jiuTongPay->setPrivateKey('1234');
        $jiuTongPay->setOptions($sourceData);
        $jiuTongPay->verifyOrderPayment([]);
    }

    /**
     * 測試未返回sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'data' => 'U7IfV1i1PUMmfw8ovIJpO37bMAyOBD1hhPzGUc6YDwYS3tGOK7B',
            'merchNo' => 'JTZF800210',
            'orderNum' => '201709070000000831',
            'rsa_private_key' => $this->rsaPrivateKey,
        ];

        $jiuTongPay = new JiuTongPay();
        $jiuTongPay->setPrivateKey('1234');
        $jiuTongPay->setOptions($sourceData);
        $jiuTongPay->verifyOrderPayment([]);
    }

    /**
     * 測試缺少返回解密参数
     */
    public function testReturnWithoutDecodeParams()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $encodeData = [
            'amount' => '100',
            'goodsName' => 'php1test',
            'merNo' => 'JTZF800210',
            'netway' => 'E_BANK_ICBC',
            'payResult' => '99',
            'payDate' => '2017-07-19 14:25:38',
            'sign' => '92895C0B41B7BCC9E4736323E4B2D5E5',
        ];

        $data = $this->getData($encodeData);

        $sourceData = [
            'data' => $data,
            'merchNo' => 'JTZF800210',
            'orderNum' => '201709070000000831',
            'rsa_private_key' => $this->rsaPrivateKey,
        ];

        $jiuTongPay = new JiuTongPay();
        $jiuTongPay->setPrivateKey('1234');
        $jiuTongPay->setOptions($sourceData);
        $jiuTongPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時簽名錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $encodeData = [
            'amount' => '100',
            'goodsName' => 'php1test',
            'merNo' => 'JTZF800210',
            'netway' => 'E_BANK_ICBC',
            'orderNum' => '201707190000009453',
            'payResult' => '00',
            'payDate' => '2017-07-19 14:25:38',
            'sign' => '987456321',
        ];

        $data = $this->getData($encodeData);

        $sourceData = [
            'data' => $data,
            'merchNo' => 'JTZF800210',
            'orderNum' => '201709070000000831',
            'rsa_private_key' => $this->rsaPrivateKey,
        ];

        $jiuTongPay = new JiuTongPay();
        $jiuTongPay->setPrivateKey('1234');
        $jiuTongPay->setOptions($sourceData);
        $jiuTongPay->verifyOrderPayment([]);
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

        $encodeData = [
            'amount' => '100',
            'goodsName' => 'php1test',
            'merNo' => 'JTZF800210',
            'netway' => 'E_BANK_ICBC',
            'orderNum' => '201707190000009453',
            'payResult' => '99',
            'payDate' => '2017-07-19 14:25:38',
            'sign' => '92895C0B41B7BCC9E4736323E4B2D5E5',
        ];

        $data = $this->getData($encodeData);

        $sourceData = [
            'data' => $data,
            'merchNo' => 'JTZF800210',
            'orderNum' => '201709070000000831',
            'rsa_private_key' => $this->rsaPrivateKey,
        ];

        $jiuTongPay = new JiuTongPay();
        $jiuTongPay->setPrivateKey('1234');
        $jiuTongPay->setOptions($sourceData);
        $jiuTongPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回訂單號錯誤
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $encodeData = [
            'amount' => '100',
            'goodsName' => 'php1test',
            'merNo' => 'JTZF800210',
            'netway' => 'E_BANK_ICBC',
            'orderNum' => '201707190000009453',
            'payResult' => '00',
            'payDate' => '2017-07-19 14:25:38',
            'sign' => 'EEC3A16305E6E839590B1726E650B7F2',
        ];

        $data = $this->getData($encodeData);

        $sourceData = [
            'data' => $data,
            'merchNo' => 'JTZF800210',
            'orderNum' => '201709070000000831',
            'rsa_private_key' => $this->rsaPrivateKey,
        ];

        $entry = [
            'id' => '201709070000000832',
            'amount' => '100',
        ];

        $jiuTongPay = new JiuTongPay();
        $jiuTongPay->setPrivateKey('1234');
        $jiuTongPay->setOptions($sourceData);
        $jiuTongPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回金額錯誤
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $encodeData = [
            'amount' => '100',
            'goodsName' => 'php1test',
            'merNo' => 'JTZF800210',
            'netway' => 'E_BANK_ICBC',
            'orderNum' => '201707190000009453',
            'payResult' => '00',
            'payDate' => '2017-07-19 14:25:38',
            'sign' => 'EEC3A16305E6E839590B1726E650B7F2',
        ];

        $data = $this->getData($encodeData);

        $sourceData = [
            'data' => $data,
            'merchNo' => 'JTZF800210',
            'orderNum' => '201709070000000831',
            'rsa_private_key' => $this->rsaPrivateKey,
        ];

        $entry = [
            'id' => '201707190000009453',
            'amount' => '1000',
        ];

        $jiuTongPay = new JiuTongPay();
        $jiuTongPay->setPrivateKey('1234');
        $jiuTongPay->setOptions($sourceData);
        $jiuTongPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testPaySuccess()
    {
        $encodeData = [
            'amount' => '100',
            'goodsName' => 'php1test',
            'merNo' => 'JTZF800210',
            'netway' => 'E_BANK_ICBC',
            'orderNum' => '201707190000009453',
            'payResult' => '00',
            'payDate' => '2017-07-19 14:25:38',
            'sign' => 'EEC3A16305E6E839590B1726E650B7F2',
        ];

        $data = $this->getData($encodeData);

        $sourceData = [
            'data' => $data,
            'merchNo' => 'JTZF800210',
            'orderNum' => '201709070000000831',
            'rsa_private_key' => $this->rsaPrivateKey,
        ];

        $entry = [
            'id' => '201707190000009453',
            'amount' => '1',
        ];

        $jiuTongPay = new JiuTongPay();
        $jiuTongPay->setPrivateKey('1234');
        $jiuTongPay->setOptions($sourceData);
        $jiuTongPay->verifyOrderPayment($entry);

        $this->assertEquals('0', $jiuTongPay->getMsg());
    }

    /**
     * 測試出款沒有帶入privateKey
     */
    public function testWithdrawWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $jiuTongPay = new JiuTongPay();
        $jiuTongPay->withdrawPayment();
    }

    /**
     * 測試出款未指定出款參數
     */
    public function testWithdrawNoWithdrawParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No withdraw parameter specified',
            150180196
        );

        $sourceData = ['account' => ''];

        $jiuTongPay = new JiuTongPay();
        $jiuTongPay->setPrivateKey('1234');
        $jiuTongPay->setOptions($sourceData);
        $jiuTongPay->withdrawPayment();
    }

    /**
     * 測試出款但返回結果缺少參數
     */
    public function testWithdrawButNoWithdrawReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No withdraw return parameter specified',
            150180209
        );

        $sourceData = [
            'number' => 'JTZF800009',
            'orderId' => '10000000000006',
            'amount' => '1',
            'bank_info_id' => '11',
            'nameReal' => '吴坚',
            'account' => '123456789123456789',
            'shop_url' => 'http://pay.wang999.com/pay/',
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'rsa_public_key' => $this->rsaPublicKey,
        ];

        $result = '{"stateCode":"99"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json; charset=utf-8');

        $jiuTongPay = new JiuTongPay();
        $jiuTongPay->setContainer($this->container);
        $jiuTongPay->setClient($this->client);
        $jiuTongPay->setResponse($response);
        $jiuTongPay->setPrivateKey('1234');
        $jiuTongPay->setOptions($sourceData);
        $jiuTongPay->withdrawPayment();
    }

    /**
     * 測試出款但返回結果錯誤(卡號錯誤)
     */
    public function testWithdrawButError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '银行卡号错误',
            180124
        );

        $sourceData = [
            'number' => 'JTZF800009',
            'orderId' => '10000000000006',
            'amount' => '1',
            'bank_info_id' => '11',
            'nameReal' => '吴坚',
            'account' => '123456789123456789',
            'shop_url' => 'http://pay.wang999.com/pay/',
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'rsa_public_key' => $this->rsaPublicKey,
        ];

        $result = '{"stateCode":"99","msg":"银行卡号错误"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json; charset=utf-8');

        $jiuTongPay = new JiuTongPay();
        $jiuTongPay->setContainer($this->container);
        $jiuTongPay->setClient($this->client);
        $jiuTongPay->setResponse($response);
        $jiuTongPay->setPrivateKey('1234');
        $jiuTongPay->setOptions($sourceData);
        $jiuTongPay->withdrawPayment();
    }

    /**
     * 測試出款請求成功
     */
    public function testWithdrawSuccess()
    {
        $sourceData = [
            'number' => 'JTZF800009',
            'orderId' => '10000000000006',
            'amount' => '1',
            'bank_info_id' => '11',
            'nameReal' => '吴坚',
            'account' => '123456789123456789',
            'shop_url' => 'http://pay.wang999.com/pay/',
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'rsa_public_key' => $this->rsaPublicKey,
        ];

        $result = '{"amount":"100","merNo":"JTZF800009","msg":"提交成功","orderNum":"10000000000006",' .
            '"sign":"66ACC665AB21346BA9395795803F02A6","stateCode":"00"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json; charset=utf-8');

        $jiuTongPay = new JiuTongPay();
        $jiuTongPay->setContainer($this->container);
        $jiuTongPay->setClient($this->client);
        $jiuTongPay->setResponse($response);
        $jiuTongPay->setPrivateKey('1234');
        $jiuTongPay->setOptions($sourceData);
        $jiuTongPay->withdrawPayment();
    }

    /**
     * 組成支付平台回傳的data
     *
     * @param array $encodeParams
     * @return string
     */
    private function getData($encodeParams)
    {
        $publicKey = base64_decode($this->rsaPublicKey);

        $encodeData = [];

        // 組織加密簽名，排除sign(加密簽名)
        foreach ($encodeParams as $key => $value) {
            if ($key != 'sign') {
                $encodeData[$key] = $value;
            }
        }

        ksort($encodeData);

        $encodeData['sign'] = $encodeParams['sign'];

        $encodeStr = json_encode($encodeData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $encParam = '';
        foreach (str_split($encodeStr, 117) as $str) {
            $data = '';
            openssl_public_encrypt($str, $data, $publicKey);
            $encParam .= $data;
        }

        return base64_encode($encParam);
    }
}
