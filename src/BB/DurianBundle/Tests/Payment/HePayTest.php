<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\HePay;
use Buzz\Message\Response;

class HePayTest extends DurianTestCase
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
    }

    /**
     * 測試加密基本參數設定未指定支付參數
     */
    public function testSetEncodeSourceNoNumber()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = ['number' => ''];

        $hePay = new HePay();
        $hePay->setOptions($sourceData);
        $hePay->getVerifyData();
    }

    /**
     * 測試加密基本參數設定帶入不支援的銀行
     */
    public function testSetEncodeSourceUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '126072',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderId' => '201712270000003338',
            'orderCreateDate' => '2017-12-27 10:28:22',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '999',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'ip' => '111.235.135.54',
        ];

        $hePay = new HePay();
        $hePay->setOptions($sourceData);
        $hePay->getVerifyData();
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

        $privkey = '';
        // Get private key
        openssl_pkey_export($res, $privkey);

        $sourceData = [
            'number' => '126072',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderId' => '201712270000003338',
            'orderCreateDate' => '2017-12-27 10:28:22',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1102',
            'rsa_private_key' => base64_encode($privkey),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'ip' => '111.235.135.54',
        ];

        $hePay = new HePay();
        $hePay->setOptions($sourceData);
        $hePay->getVerifyData();
    }

    /**
     * 測試加密未返回state
     */
    public function testGetEncodeNoReturnState()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => '126072',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderId' => '201712270000003338',
            'orderCreateDate' => '2017-12-27 10:28:22',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1102',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.xueyuplus.com',
            'ip' => '111.235.135.54',
        ];

        $result = '{"sign":"I2Az1eDrGdIg OIooYFt nniOHg3kntVEjdcJ0DByh2sx1UIsPFfYsD9ZPbhrQHcTiffjLXj3jDX' .
            'BmDvv8LjRgahgL qSWEXLYdmwN TZKAAQg22Cd48Uu0R4tZAAG4fQ83RgvZfItYyTndzdgL8i80eE5TlFqHRaRvv8N' .
            'L\/T50=","pay_url":"http:\/\/pay1.xgawehs.top\/S270420171227105452033525.html",' .
            '"return_msg":"提交成功","return_code":"SUCCESS"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $hePay = new HePay();
        $hePay->setContainer($this->container);
        $hePay->setClient($this->client);
        $hePay->setResponse($response);
        $hePay->setOptions($sourceData);
        $hePay->getVerifyData();
    }

    /**
     * 測試加密未返回return_code
     */
    public function testGetEncodeNoReturnReturnCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => '126072',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderId' => '201712270000003338',
            'orderCreateDate' => '2017-12-27 10:28:22',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1102',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.xueyuplus.com',
            'ip' => '111.235.135.54',
        ];

        $result = '{"sign":"I2Az1eDrGdIg OIooYFt nniOHg3kntVEjdcJ0DByh2sx1UIsPFfYsD9ZPbhrQHcTiffjLXj3jDX' .
            'BmDvv8LjRgahgL qSWEXLYdmwN TZKAAQg22Cd48Uu0R4tZAAG4fQ83RgvZfItYyTndzdgL8i80eE5TlFqHRaRvv8N' .
            'L\/T50=","pay_url":"http:\/\/pay1.xgawehs.top\/S270420171227105452033525.html",' .
            '"return_msg":"提交成功","state":"0"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $hePay = new HePay();
        $hePay->setContainer($this->container);
        $hePay->setClient($this->client);
        $hePay->setResponse($response);
        $hePay->setOptions($sourceData);
        $hePay->getVerifyData();
    }

    /**
     * 測試加密返回state不為0，且沒有返回return_msg
     */
    public function testGetEncodeReturnStateNotEqualToZeroAndNoReturnMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => '126072',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderId' => '201712270000003338',
            'orderCreateDate' => '2017-12-27 10:28:22',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1102',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.xueyuplus.com',
            'ip' => '111.235.135.54',
        ];

        $result = '{"sign":"","state":"1","return_code":"ERROR"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $hePay = new HePay();
        $hePay->setContainer($this->container);
        $hePay->setClient($this->client);
        $hePay->setResponse($response);
        $hePay->setOptions($sourceData);
        $hePay->getVerifyData();
    }

    /**
     * 測試加密返回state不為0
     */
    public function testGetEncodeReturnStateNotZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '通道可用额度10.0-3000.0',
            180130
        );

        $sourceData = [
            'number' => '126072',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderId' => '201712270000003338',
            'orderCreateDate' => '2017-12-27 10:28:22',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1102',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.xueyuplus.com',
            'ip' => '111.235.135.54',
        ];

        $result = '{"sign":"","return_msg":"通道可用额度10.0-3000.0","state":"1","return_code":"ERROR"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $hePay = new HePay();
        $hePay->setContainer($this->container);
        $hePay->setClient($this->client);
        $hePay->setResponse($response);
        $hePay->setOptions($sourceData);
        $hePay->getVerifyData();
    }

    /**
     * 測試加密未返回pay_url
     */
    public function testGetEncodeNoReturnPayUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => '126072',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderId' => '201712270000003338',
            'orderCreateDate' => '2017-12-27 10:28:22',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1102',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.xueyuplus.com',
            'ip' => '111.235.135.54',
        ];

        $result = '{"sign":"I2Az1eDrGdIg OIooYFt nniOHg3kntVEjdcJ0DByh2sx1UIsPFfYsD9ZPbhrQHcTiffjLXj3jDX' .
            'BmDvv8LjRgahgL qSWEXLYdmwN TZKAAQg22Cd48Uu0R4tZAAG4fQ83RgvZfItYyTndzdgL8i80eE5TlFqHRaRvv8N' .
            'L\/T50=","return_msg":"提交成功","state":"0","return_code":"SUCCESS"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $hePay = new HePay();
        $hePay->setContainer($this->container);
        $hePay->setClient($this->client);
        $hePay->setResponse($response);
        $hePay->setOptions($sourceData);
        $hePay->getVerifyData();
    }

    /**
     * 測試加密(掃碼)
     */
    public function testGetEncodeData()
    {
        $sourceData = [
            'number' => '126072',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderId' => '201712270000003338',
            'orderCreateDate' => '2017-12-27 10:28:22',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1103',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.xueyuplus.com',
            'ip' => '111.235.135.54',
        ];

        $result = '{"sign":"I2Az1eDrGdIg OIooYFt nniOHg3kntVEjdcJ0DByh2sx1UIsPFfYsD9ZPbhrQHcTiffjLXj3jDX' .
            'BmDvv8LjRgahgL qSWEXLYdmwN TZKAAQg22Cd48Uu0R4tZAAG4fQ83RgvZfItYyTndzdgL8i80eE5TlFqHRaRvv8N' .
            'L\/T50=","pay_url":"https:\/\/qpay.qq.com\/qr\/6cb85a86",' .
            '"return_msg":"提交成功","state":"0","return_code":"SUCCESS"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $hePay = new HePay();
        $hePay->setContainer($this->container);
        $hePay->setClient($this->client);
        $hePay->setResponse($response);
        $hePay->setOptions($sourceData);
        $encodeData = $hePay->getVerifyData();

        $this->assertEmpty($encodeData);
        $this->assertEquals('https://qpay.qq.com/qr/6cb85a86', $hePay->getQrcode());
    }

    /**
     * 測試手機支付返回時pay_url格式錯誤
     */
    public function testPhonePayGetEncodeReturnPayUrlWithError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => '126072',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderId' => '201712270000003338',
            'orderCreateDate' => '2017-12-27 10:28:22',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1097',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.xueyuplus.com',
            'ip' => '111.235.135.54',
        ];

        $result = '{"sign":"I2Az1eDrGdIg OIooYFt nniOHg3kntVEjdcJ0DByh2sx1UIsPFfYsD9ZPbhrQHcTiffjLXj3jDX' .
            'BmDvv8LjRgahgL qSWEXLYdmwN TZKAAQg22Cd48Uu0R4tZAAG4fQ83RgvZfItYyTndzdgL8i80eE5TlFqHRaRvv8N' .
            'L\/T50=","pay_url":"www.joinpay.com\/trade\/uniPayApi.action?p5_ProductName=28365385201712271' .
            '01246&q9_TransactionModel=MODEL2&hmac=059c784d6c06efb114aa954bd6a1f8d8&p1_MerchantNo=888100700008832' .
            '&p9_NotifyUrl=http:\/\/47.92.123.131\/weixin\/notifyWap\/2836538520171227101246&q1_FrpCode=WEIXIN_H5' .
            '&p4_Cur=1&p3_Amount=10.00&p0_Version=1.0&p2_OrderNo=2836538520171227101246",' .
            '"return_msg":"提交成功","state":"0","return_code":"SUCCESS"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $hePay = new HePay();
        $hePay->setContainer($this->container);
        $hePay->setClient($this->client);
        $hePay->setResponse($response);
        $hePay->setOptions($sourceData);
        $hePay->getVerifyData();
    }

    /**
     * 測試手機支付
     */
    public function testPayWithPhone()
    {
        $sourceData = [
            'number' => '126072',
            'notify_url' => 'http://pay.my/pay/return.php',
            'orderId' => '201712270000003338',
            'orderCreateDate' => '2017-12-27 10:28:22',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1097',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.xueyuplus.com',
            'ip' => '111.235.135.54',
        ];

        $result = '{"sign":"I2Az1eDrGdIg OIooYFt nniOHg3kntVEjdcJ0DByh2sx1UIsPFfYsD9ZPbhrQHcTiffjLXj3jDX' .
            'BmDvv8LjRgahgL qSWEXLYdmwN TZKAAQg22Cd48Uu0R4tZAAG4fQ83RgvZfItYyTndzdgL8i80eE5TlFqHRaRvv8N' .
            'L\/T50=","pay_url":"https:\/\/www.joinpay.com\/trade\/uniPayApi.action?p5_ProductName=28365385201712271' .
            '01246&q1_FrpCode=WEIXIN_H5&p3_Amount=10.00&p0_Version=1.0&p2_OrderNo=2836538520171227101246",' .
            '"return_msg":"提交成功","state":"0","return_code":"SUCCESS"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $hePay = new HePay();
        $hePay->setContainer($this->container);
        $hePay->setClient($this->client);
        $hePay->setResponse($response);
        $hePay->setOptions($sourceData);
        $requestData = $hePay->getVerifyData();

        $this->assertEquals('https://www.joinpay.com/trade/uniPayApi.action', $requestData['post_url']);
        $this->assertEquals('2836538520171227101246', $requestData['params']['p5_ProductName']);
        $this->assertEquals('WEIXIN_H5', $requestData['params']['q1_FrpCode']);
        $this->assertEquals('10.00', $requestData['params']['p3_Amount']);
        $this->assertEquals('1.0', $requestData['params']['p0_Version']);
        $this->assertEquals('2836538520171227101246', $requestData['params']['p2_OrderNo']);
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

        $hePay = new HePay();
        $hePay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台缺少回傳sign(加密簽名)
     */
    public function testVerifyWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'opay_status' => '0',
            'order_type' => '2704',
            'out_trade_no' => '201712270000003338',
            'pay_status' => '1',
            'seller_id' => '126072',
            'total_fee' => '1',
        ];

        $hePay = new HePay();
        $hePay->setOptions($sourceData);
        $hePay->verifyOrderPayment([]);
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

        $sourceData = [
            'opay_status' => '0',
            'order_type' => '2704',
            'out_trade_no' => '201712270000003338',
            'pay_status' => '1',
            'seller_id' => '126072',
            'total_fee' => '1',
            'sign' => '123123',
            'rsa_public_key' => '',
        ];

        $hePay = new HePay();
        $hePay->setOptions($sourceData);
        $hePay->verifyOrderPayment([]);
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

        $sourceData = [
            'opay_status' => '0',
            'order_type' => '2704',
            'out_trade_no' => '201712270000003338',
            'pay_status' => '1',
            'seller_id' => '126072',
            'total_fee' => '1',
            'sign' => '123123',
            'rsa_public_key' => '456456',
        ];

        $hePay = new HePay();
        $hePay->setOptions($sourceData);
        $hePay->verifyOrderPayment([]);
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
            'opay_status' => '0',
            'order_type' => '2704',
            'out_trade_no' => '201712270000003338',
            'pay_status' => '1',
            'seller_id' => '126072',
            'total_fee' => '1',
            'sign' => '123123',
            'rsa_public_key' => $this->publicKey,
        ];

        $hePay = new HePay();
        $hePay->setOptions($sourceData);
        $hePay->verifyOrderPayment([]);
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

        $encodeStr = 'opay_status=0&order_type=2704&out_trade_no=201712270000003338&' .
            'pay_status=0&seller_id=126072&total_fee=1';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $sourceData = [
            'opay_status' => '0',
            'order_type' => '2704',
            'out_trade_no' => '201712270000003338',
            'pay_status' => '0',
            'seller_id' => '126072',
            'total_fee' => '1',
            'sign' => base64_encode($sign),
            'rsa_public_key' => $this->publicKey,
        ];

        $hePay = new HePay();
        $hePay->setOptions($sourceData);
        $hePay->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $encodeStr = 'opay_status=0&order_type=2704&out_trade_no=201712270000003338&' .
            'pay_status=1&seller_id=126072&total_fee=1';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $sourceData = [
            'opay_status' => '0',
            'order_type' => '2704',
            'out_trade_no' => '201712270000003338',
            'pay_status' => '1',
            'seller_id' => '126072',
            'total_fee' => '1',
            'sign' => base64_encode($sign),
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201712270000003331',
            'amount' => '0.01',
        ];

        $hePay = new HePay();
        $hePay->setOptions($sourceData);
        $hePay->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $encodeStr = 'opay_status=0&order_type=2704&out_trade_no=201712270000003338&' .
            'pay_status=1&seller_id=126072&total_fee=1';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $sourceData = [
            'opay_status' => '0',
            'order_type' => '2704',
            'out_trade_no' => '201712270000003338',
            'pay_status' => '1',
            'seller_id' => '126072',
            'total_fee' => '1',
            'sign' => base64_encode($sign),
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201712270000003338',
            'amount' => '1',
        ];

        $hePay = new HePay();
        $hePay->setOptions($sourceData);
        $hePay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $encodeStr = 'opay_status=0&order_type=2704&out_trade_no=201712270000003338&' .
            'pay_status=1&seller_id=126072&total_fee=1';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $sourceData = [
            'opay_status' => '0',
            'order_type' => '2704',
            'out_trade_no' => '201712270000003338',
            'pay_status' => '1',
            'seller_id' => '126072',
            'total_fee' => '1',
            'sign' => base64_encode($sign),
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201712270000003338',
            'amount' => '0.01',
        ];

        $hePay = new HePay();
        $hePay->setOptions($sourceData);
        $hePay->verifyOrderPayment($entry);

        $this->assertEquals('success', $hePay->getMsg());
    }
}
