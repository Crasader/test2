<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\GaoHuiTong;
use BB\DurianBundle\Tests\Functional\WebTestCase;
use Buzz\Message\Response;

class GaoHuiTongTest extends WebTestCase
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

        $res = openssl_pkey_new();

        $privateKey = '';
        openssl_pkey_export($res, $privateKey);
        $this->privateKey = base64_encode($privateKey);

        $publicKey = openssl_pkey_get_details($res);

        $this->publicKey = base64_encode($publicKey['key']);

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
     * 測試加密基本參數設定無指定支付參數
     */
    public function testSetEncodeSourceNoNumber()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = ['number' => ''];

        $gaoHuiTong = new GaoHuiTong();
        $gaoHuiTong->setOptions($sourceData);
        $gaoHuiTong->getVerifyData();
    }

    /**
     * 測試支付時帶入不支援銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '94539487',
            'notify_url' => 'http://ii.love.cap/return.php',
            'orderId' => '2018012912345678',
            'orderCreateDate' => '2018-01-29 09:11:22',
            'amount' => '0.1',
            'paymentVendorId' => '9911',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'http://service.gaohuitong.com/PayApi/bankPay',
            'ip' => '111.235.135.54',
        ];

        $gaoHuiTong = new GaoHuiTong();
        $gaoHuiTong->setOptions($sourceData);
        $gaoHuiTong->getVerifyData();
    }

    /**
     * 測試加密簽名參數失敗
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
        $res = openssl_pkey_new($config);

        $privateKey = '';
        openssl_pkey_export($res, $privateKey);
        $privateKey = base64_encode($privateKey);

        $sourceData = [
            'number' => '94539487',
            'notify_url' => 'http://ii.love.cap/return.php',
            'orderId' => '2018012912345678',
            'orderCreateDate' => '2018-01-29 09:11:22',
            'amount' => '0.1',
            'paymentVendorId' => '1',
            'rsa_private_key' => $privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'http://tnndbear.net',
            'ip' => '111.235.135.54',
        ];

        $gaoHuiTong = new GaoHuiTong();
        $gaoHuiTong->setOptions($sourceData);
        $gaoHuiTong->getVerifyData();
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

        $sourceData = [
            'number' => '94539487',
            'notify_url' => 'http://ii.love.cap/return.php',
            'orderId' => '2018012912345678',
            'orderCreateDate' => '2018-01-29 09:11:22',
            'amount' => '0.1',
            'paymentVendorId' => '1111',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => '',
            'postUrl' => 'http://service.gaohuitong.com/PayApi/bankPay',
            'ip' => '111.235.135.54',
        ];

        $gaoHuiTong = new GaoHuiTong();
        $gaoHuiTong->setOptions($sourceData);
        $gaoHuiTong->getVerifyData();
    }

    /**
     * 測試支付對外返回缺少dealCode
     */
    public function testPayParameterWithoutdealCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '94539487',
            'notify_url' => 'http://ii.love.cap/return.php',
            'orderId' => '2018012912345678',
            'orderCreateDate' => '2018-01-29 09:11:22',
            'amount' => '0.1',
            'paymentVendorId' => '1111',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'http://tnndbear.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'http://service.gaohuitong.com/PayApi/bankPay',
            'ip' => '111.235.135.54',
        ];

        $content = '{"dealMsg":"sign\u9a8c\u7b7e\u5931\u8d25","merchantNo":"CX0001974"}';
        $response = new Response();
        $response->setContent($content);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $gaoHuiTong = new GaoHuiTong();
        $gaoHuiTong->setContainer($this->container);
        $gaoHuiTong->setClient($this->client);
        $gaoHuiTong->setOptions($options);
        $gaoHuiTong->setResponse($response);
        $gaoHuiTong->getVerifyData();
    }

    /**
     * 測試支付對外返回缺少codeUrl
     */
    public function testPayParameterWithoutCodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '94539487',
            'notify_url' => 'http://ii.love.cap/return.php',
            'orderId' => '2018012912345678',
            'orderCreateDate' => '2018-01-29 09:11:22',
            'amount' => '0.1',
            'paymentVendorId' => '1111',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'http://tnndbear.net1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'http://service.gaohuitong.com/PayApi/bankPay',
            'ip' => '111.235.135.54',
        ];

        $content = '{"dealMsg":"\u4ea' .
            '4\u6613\u6210\u529f","sign":"prCPGhO9E+4Nl\/TZSeeQz+mNj9twxJUzg8AAfiGrXUyvYTSmRosGryUrCnlCu3jIq7gEwTx2K' .
            'lUG7reuCAVEh5beyIUt4LplyOeaZTMO1CQ9phYmdpUto+wjV8zzKr3Qbfu92nPnVK\/vt4F1oq+7x5a5kJpb+RcLIFP5w\/HfNL4=",' .
            '"dealCode":"10000","merchantNo":"CX0001974"}';

        $response = new Response();
        $response->setContent(json_encode($content));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $gaoHuiTong = new GaoHuiTong();
        $gaoHuiTong->setContainer($this->container);
        $gaoHuiTong->setClient($this->client);
        $gaoHuiTong->setOptions($options);
        $gaoHuiTong->setResponse($response);
        $gaoHuiTong->getVerifyData();
    }

    /**
     * 測試支付對外返回失敗
     */
    public function testPayParameterFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'sign验签失败',
            180130
        );

        $options = [
            'number' => '94539487',
            'notify_url' => 'http://ii.love.cap/return.php',
            'orderId' => '2018012912345678',
            'orderCreateDate' => '2018-01-29 09:11:22',
            'amount' => '0.1',
            'paymentVendorId' => '1111',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'http://tnndbear.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'http://service.gaohuitong.com/PayApi/bankPay',
            'ip' => '111.235.135.54',
        ];

        $content = '{"dealMsg":"sign\u9a8c\u7b7e\u5931\u8d25","dealCode":"90004","merchantNo":"CX0001974"}';
        $response = new Response();
        $response->setContent($content);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $gaoHuiTong = new GaoHuiTong();
        $gaoHuiTong->setContainer($this->container);
        $gaoHuiTong->setClient($this->client);
        $gaoHuiTong->setOptions($options);
        $gaoHuiTong->setResponse($response);
        $gaoHuiTong->getVerifyData();
    }

    /**
     * 測試支付對外返回失敗沒返回dealMsg
     */
    public function testPayParameterFailureWithoutDealMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '94539487',
            'notify_url' => 'http://ii.love.cap/return.php',
            'orderId' => '2018012912345678',
            'orderCreateDate' => '2018-01-29 09:11:22',
            'amount' => '0.1',
            'paymentVendorId' => '1111',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'http://tnndbear.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'http://service.gaohuitong.com/PayApi/bankPay',
            'ip' => '111.235.135.54',
        ];

        $content = '{"dealCode":"90004","merchantNo":"CX0001974"}';
        $response = new Response();
        $response->setContent($content);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $gaoHuiTong = new GaoHuiTong();
        $gaoHuiTong->setContainer($this->container);
        $gaoHuiTong->setClient($this->client);
        $gaoHuiTong->setOptions($options);
        $gaoHuiTong->setResponse($response);
        $gaoHuiTong->getVerifyData();
    }

    /**
     * 測試銀聯二維支付
     */
    public function testUnipayQrcodePay()
    {
        $options = [
            'number' => '94539487',
            'notify_url' => 'http://ii.love.cap/return.php',
            'orderId' => '201801300000000761',
            'orderCreateDate' => '2018-01-29 09:11:22',
            'amount' => '0.01',
            'paymentVendorId' => '1111',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'http://tnndbear.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'http://service.gaohuitong.com/PayApi/bankPay',
            'ip' => '111.235.135.54',
        ];

        $content = '{"codeUrl":"https:\/\/qr.95516.com\/00010000\/6222866377888541353060912' .
            '4827688","dealMsg":"\u4ea4\u6613\u6210\u529f","sign":"U\/RyZx6MaoLWkIjka4SSe3oRkQfWL7mrI7G6nAamsuG' .
            'PE8GUOKYf3aZEl9NZ8lz\/BZ3bm4JwVKztOTmoTUPhrxi2XqJS5e+kVJ0uqZSQ2a\/NG+3u7jggScjksTJpIovMSSEx0f3' .
            'ViVffXxAcftMpxZYL9KR0CBSKmehetB1J7k=","dealCode":"10000","merchantNo":"CX0001974"}';

        $response = new Response();
        $response->setContent($content);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $gaoHuiTong = new GaoHuiTong();
        $gaoHuiTong->setContainer($this->container);
        $gaoHuiTong->setClient($this->client);
        $gaoHuiTong->setOptions($options);
        $gaoHuiTong->setResponse($response);
        $data = $gaoHuiTong->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://qr.95516.com/00010000/62228663778885413530609124827688', $gaoHuiTong->getQrcode());
    }

    /**
     * 測試網銀支付
     */
    public function testBankPay()
    {
        $options = [
            'number' => '94539487',
            'notify_url' => 'http://ii.love.cap/return.php',
            'orderId' => '2018012912345678',
            'orderCreateDate' => '2018-01-29 09:11:22',
            'amount' => '0.1',
            'username' => 'phptest1',
            'paymentVendorId' => '1',
            'rsa_private_key' => $this->privateKey,
            'verify_url' => 'http://tnndbear.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'http://service.gaohuitong.com/PayApi/bankPay',
            'ip' => '111.235.135.54',
        ];

        $gaoHuiTong = new GaoHuiTong();
        $gaoHuiTong->setOptions($options);
        $params = $gaoHuiTong->getVerifyData();

        $this->assertEquals($options['number'], $params['merchantNo']);
        $this->assertEquals('V2.0', $params['version']);
        $this->assertEquals('bankPay', $params['service']);
        $this->assertEquals('CNY', $params['curCode']);
        $this->assertEquals($options['orderId'], $params['orderNo']);
        $this->assertEquals($options['amount'] * 100, $params['orderAmount']);
        $this->assertEquals('ICBC', $params['payChannelCode']);
        $this->assertEquals($options['notify_url'], $params['bgUrl']);

        $encodeStr = 'bgUrl=http://ii.love.cap/return.php&curCode=CNY&merchantNo=94539487&orderAmount=10&orderNo=2' .
            '018012912345678&orderSource=1&orderTime=20180129091122&payChannelCode=ICBC&payChannelType=1&service=b' .
            'ankPay&signType=2&version=V2.0';
        openssl_sign($encodeStr, $veirfySign, $gaoHuiTong->getRsaPrivateKey());
        $this->assertEquals(urlencode(urlencode(base64_encode($veirfySign))), $params['sign']);
    }

    /**
     * 測試解密驗證時支付平台未指定返回參數
     */
    public function testVerifyWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $gaoHuiTong = new GaoHuiTong();
        $gaoHuiTong->setPrivateKey('56453hjkjkh567fgsd');

        $returnData = [
            'orderNo' => '201802030000000819',
            'dealMsg' => '交易成功',
            'fee' => 1,
            'version' => 'V2.0',
            'productName' => '借记卡扫码支付',
            'cxOrderNo' => '600000100027352913',
            'orderAmount' => 1,
            'orderTime' => '20180206162208',
            'dealTime' => '20180207145121',
            'payChannelCode' => 'CX_DC',
            'dealCode' => '10000',
            'curCode' => 'CNY',
            'merchantNo' => 'CX0001974',
            'rsa_public_key' => $this->publicKey,
        ];

        $gaoHuiTong->setOptions($returnData);
        $gaoHuiTong->verifyOrderPayment([]);
    }

    /**
     * 測試支付異步返回通知缺少sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $returnData = [
            'orderNo' => '201802030000000819',
            'dealMsg' => '交易成功',
            'fee' => 1,
            'version' => 'V2.0',
            'productName' => '借记卡扫码支付',
            'cxOrderNo' => '600000100027352913',
            'orderAmount' => 1,
            'orderTime' => '20180206162208',
            'dealTime' => '20180207145121',
            'payChannelCode' => 'CX_DC',
            'dealCode' => '10000',
            'curCode' => 'CNY',
            'merchantNo' => 'CX0001974',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'orderId' => '201801300000000761',
            'amount' => '0.01',
        ];

        $gaoHuiTong = new GaoHuiTong();
        $gaoHuiTong->setOptions($returnData);
        $gaoHuiTong->verifyOrderPayment($entry);
    }

    /**
     * 測試支付異步返回通知sign錯誤
     */
    public function testReturnSignError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $returnData = [
            'orderNo' => '201802030000000819',
            'dealMsg' => '交易成功',
            'fee' => 1,
            'sign' => '1234',
            'version' => 'V2.0',
            'productName' => '借记卡扫码支付',
            'cxOrderNo' => '600000100027352913',
            'orderAmount' => 1,
            'orderTime' => '20180206162208',
            'dealTime' => '20180207145121',
            'payChannelCode' => 'CX_DC',
            'dealCode' => '10000',
            'curCode' => 'CNY',
            'merchantNo' => 'CX0001974',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'orderId' => '201801300000000761',
            'amount' => '0.01',
        ];

        $gaoHuiTong = new GaoHuiTong();
        $gaoHuiTong->setOptions($returnData);
        $gaoHuiTong->verifyOrderPayment($entry);
    }

    /**
     * 測試支付異步返回通知失敗
     */
    public function testReturnFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $encodeStr = 'curCode=CNY&cxOrderNo=600000100027352913&dealCode=1&dealMsg=交易成功&dealTime=201802071451' .
            '21&fee=1&merchantNo=CX0001974&orderAmount=1&orderNo=201802030000000819&orderTime=20180206162208&payCha' .
            'nnelCode=CX_DC&productName=借记卡扫码支付&version=V2.0';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $returnData = [
            'sign' => urlencode(base64_encode($sign)),
            'orderNo' => '201802030000000819',
            'dealMsg' => '交易成功',
            'fee' => 1,
            'version' => 'V2.0',
            'productName' => '借记卡扫码支付',
            'cxOrderNo' => '600000100027352913',
            'orderAmount' => 1,
            'orderTime' => '20180206162208',
            'dealTime' => '20180207145121',
            'payChannelCode' => 'CX_DC',
            'dealCode' => '1',
            'curCode' => 'CNY',
            'merchantNo' => 'CX0001974',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201802030000000819',
            'amount' => '0.01',
        ];

        $gaoHuiTong = new GaoHuiTong();
        $gaoHuiTong->setOptions($returnData);
        $gaoHuiTong->verifyOrderPayment($entry);
    }

    /**
     * 測試支付異步返回通知訂單號錯誤
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $encodeStr = 'curCode=CNY&cxOrderNo=600000100027352913&dealCode=10000&dealMsg=交易成功&dealTime=201802071451' .
            '21&fee=1&merchantNo=CX0001974&orderAmount=1&orderNo=201802030000000819&orderTime=20180206162208&payCha' .
            'nnelCode=CX_DC&productName=借记卡扫码支付&version=V2.0';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $returnData = [
            'sign' => urlencode(base64_encode($sign)),
            'orderNo' => '201802030000000819',
            'dealMsg' => '交易成功',
            'fee' => 1,
            'version' => 'V2.0',
            'productName' => '借记卡扫码支付',
            'cxOrderNo' => '600000100027352913',
            'orderAmount' => 1,
            'orderTime' => '20180206162208',
            'dealTime' => '20180207145121',
            'payChannelCode' => 'CX_DC',
            'dealCode' => '10000',
            'curCode' => 'CNY',
            'merchantNo' => 'CX0001974',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201801300000000762',
            'amount' => '0.01',
        ];

        $gaoHuiTong = new GaoHuiTong();
        $gaoHuiTong->setOptions($returnData);
        $gaoHuiTong->verifyOrderPayment($entry);
    }

    /**
     * 測試支付異步返回通知金額錯誤
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $encodeStr = 'curCode=CNY&cxOrderNo=600000100027352913&dealCode=10000&dealMsg=交易成功&dealTime=201802071451' .
            '21&fee=1&merchantNo=CX0001974&orderAmount=1&orderNo=201802030000000819&orderTime=20180206162208&payCha' .
            'nnelCode=CX_DC&productName=借记卡扫码支付&version=V2.0';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $returnData = [
            'sign' => urlencode(base64_encode($sign)),
            'orderNo' => '201802030000000819',
            'dealMsg' => '交易成功',
            'fee' => 1,
            'version' => 'V2.0',
            'productName' => '借记卡扫码支付',
            'cxOrderNo' => '600000100027352913',
            'orderAmount' => 1,
            'orderTime' => '20180206162208',
            'dealTime' => '20180207145121',
            'payChannelCode' => 'CX_DC',
            'dealCode' => '10000',
            'curCode' => 'CNY',
            'merchantNo' => 'CX0001974',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201802030000000819',
            'amount' => '1.01',
        ];

        $gaoHuiTong = new GaoHuiTong();
        $gaoHuiTong->setOptions($returnData);
        $gaoHuiTong->verifyOrderPayment($entry);
    }

    /**
     * 測試支付異步返回通知驗簽時公鑰為空
     */
    public function testReturnGetRsaPublicKeyEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa public key is empty',
            180095
        );

        $encodeStr = 'curCode=CNY&cxOrderNo=600000100027352913&dealCode=10000&dealMsg=交易成功&dealTime=201802071451' .
            '21&fee=1&merchantNo=CX0001974&orderAmount=1&orderNo=201802030000000819&orderTime=20180206162208&payCha' .
            'nnelCode=CX_DC&productName=借记卡扫码支付&version=V2.0';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $returnData = [
            'sign' => urlencode(base64_encode($sign)),
            'orderNo' => '201802030000000819',
            'dealMsg' => '交易成功',
            'fee' => 1,
            'version' => 'V2.0',
            'productName' => '借记卡扫码支付',
            'cxOrderNo' => '600000100027352913',
            'orderAmount' => 1,
            'orderTime' => '20180206162208',
            'dealTime' => '20180207145121',
            'payChannelCode' => 'CX_DC',
            'dealCode' => '10000',
            'curCode' => 'CNY',
            'merchantNo' => 'CX0001974',
            'rsa_public_key' => '',
        ];

        $entry = [
            'id' => '201801300000000761',
            'amount' => '1',
        ];

        $gaoHuiTong = new GaoHuiTong();
        $gaoHuiTong->setOptions($returnData);
        $gaoHuiTong->verifyOrderPayment($entry);
    }

    /**
     * 測試支付異步返回通知驗簽時取得公鑰失敗
     */
    public function testReturnGetRsaPublicKeyFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa public key failure',
            180096
        );

        $encodeStr = 'curCode=CNY&cxOrderNo=600000100027352913&dealCode=10000&dealMsg=交易成功&dealTime=201802071451' .
            '21&fee=1&merchantNo=CX0001974&orderAmount=1&orderNo=201802030000000819&orderTime=20180206162208&payCha' .
            'nnelCode=CX_DC&productName=借记卡扫码支付&version=V2.0';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $returnData = [
            'sign' => urlencode(base64_encode($sign)),
            'orderNo' => '201802030000000819',
            'dealMsg' => '交易成功',
            'fee' => 1,
            'version' => 'V2.0',
            'productName' => '借记卡扫码支付',
            'cxOrderNo' => '600000100027352913',
            'orderAmount' => 1,
            'orderTime' => '20180206162208',
            'dealTime' => '20180207145121',
            'payChannelCode' => 'CX_DC',
            'dealCode' => '10000',
            'curCode' => 'CNY',
            'merchantNo' => 'CX0001974',
            'rsa_public_key' => 'gggg',
        ];

        $entry = [
            'id' => '201802030000000819',
            'amount' => '1',
        ];

        $gaoHuiTong = new GaoHuiTong();
        $gaoHuiTong->setOptions($returnData);
        $gaoHuiTong->verifyOrderPayment($entry);
    }

    /**
     * 測試支付異步返回成功
     */
    public function testReturnSuccess()
    {
        $encodeStr = 'curCode=CNY&cxOrderNo=600000100027352913&dealCode=10000&dealMsg=交易成功&dealTime=201802071451' .
            '21&fee=1&merchantNo=CX0001974&orderAmount=1&orderNo=201802030000000819&orderTime=20180206162208&payCha' .
            'nnelCode=CX_DC&productName=借记卡扫码支付&version=V2.0';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $returnData = [
            'sign' => urlencode(base64_encode($sign)),
            'orderNo' => '201802030000000819',
            'dealMsg' => '交易成功',
            'fee' => 1,
            'version' => 'V2.0',
            'productName' => '借记卡扫码支付',
            'cxOrderNo' => '600000100027352913',
            'orderAmount' => 1,
            'orderTime' => '20180206162208',
            'dealTime' => '20180207145121',
            'payChannelCode' => 'CX_DC',
            'dealCode' => '10000',
            'curCode' => 'CNY',
            'merchantNo' => 'CX0001974',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201802030000000819',
            'amount' => '0.01',
        ];

        $gaoHuiTong = new GaoHuiTong();
        $gaoHuiTong->setOptions($returnData);
        $gaoHuiTong->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $gaoHuiTong->getMsg());
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

        $gaoHuiTong = new GaoHuiTong();

        $gaoHuiTong->setOptions($sourceData);
        $gaoHuiTong->withdrawPayment();
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
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '0.01',
            'orderId' => '112332',
            'number' => '10000080001641',
            'orderCreateDate' => '2018-01-10 10:40:05',
            'bank_name' => '工商銀行',
            'withdraw_host' => 'payment.http.withdraw.com',
            'shop_url' => 'http://pay.test/pay/',
            'branch' => '北京支行',
            'province' => '天津省',
            'city' => '很有市',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'rsa_private_key' => $this->privateKey,
        ];

        $result = '{"dealMsg":"\u6210\u529f","sign":"MnDEWQp"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $gaoHuiTong = new GaoHuiTong();
        $gaoHuiTong->setContainer($this->container);
        $gaoHuiTong->setClient($this->client);
        $gaoHuiTong->setResponse($response);

        $gaoHuiTong->setOptions($sourceData);
        $gaoHuiTong->withdrawPayment();
    }

    /**
     * 測試出款但餘額不足
     */
    public function testWithdrawButInsufficientBalance()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Insufficient balance',
            150180197
        );

        $sourceData = [
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '0.01',
            'orderId' => '112332',
            'number' => '10000080001641',
            'orderCreateDate' => '2018-01-10 10:40:05',
            'bank_name' => '工商銀行',
            'withdraw_host' => 'payment.http.withdraw.com',
            'shop_url' => 'http://pay.test/pay/',
            'branch' => '北京支行',
            'province' => '天津省',
            'city' => '很有市',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'rsa_private_key' => $this->privateKey,
        ];

        $result = '{"dealMsg":"\u5546\u6237\u4f59\u989d\u4e0d\u8db3","dealCode":20003}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $gaoHuiTong = new GaoHuiTong();
        $gaoHuiTong->setContainer($this->container);
        $gaoHuiTong->setClient($this->client);
        $gaoHuiTong->setResponse($response);

        $gaoHuiTong->setOptions($sourceData);
        $gaoHuiTong->withdrawPayment();
    }

    /**
     * 測試出款加密簽名參數失敗
     */
    public function testGetWithdrawEncodeGenerateSignatureFailure()
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

        $privateKey = '';
        openssl_pkey_export($res, $privateKey);
        $privateKey = base64_encode($privateKey);

        $sourceData = [
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '0.01',
            'orderId' => '112332',
            'number' => '10000080001641',
            'orderCreateDate' => '2018-01-10 10:40:05',
            'bank_name' => '工商銀行',
            'withdraw_host' => 'payment.http.withdraw.com',
            'shop_url' => 'http://pay.test/pay/',
            'branch' => '北京支行',
            'province' => '天津省',
            'city' => '很有市',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'rsa_private_key' => $privateKey,
        ];

        $gaoHuiTong = new GaoHuiTong();
        $gaoHuiTong->setOptions($sourceData);
        $gaoHuiTong->withdrawPayment();
    }

    /**
     * 測試出款返回異常
     */
    public function testWithdrawFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'sign验签失败',
            180124
        );

        $sourceData = [
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '0.01',
            'orderId' => '112332',
            'number' => '10000080001641',
            'orderCreateDate' => '2018-01-10 10:40:05',
            'bank_name' => '工商銀行',
            'withdraw_host' => 'payment.http.withdraw.com',
            'shop_url' => 'http://pay.test/pay/',
            'branch' => '北京支行',
            'province' => '天津省',
            'city' => '很有市',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'rsa_private_key' => $this->privateKey,
        ];

        $result = '{"dealMsg":"sign\u9a8c\u7b7e\u5931\u8d25","dealCode":90004}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $gaoHuiTong = new GaoHuiTong();
        $gaoHuiTong->setContainer($this->container);
        $gaoHuiTong->setClient($this->client);
        $gaoHuiTong->setResponse($response);

        $gaoHuiTong->setOptions($sourceData);
        $gaoHuiTong->withdrawPayment();
    }

    /**
     * 測試出款返回成功
     */
    public function testWithdrawSuccess()
    {
        $sourceData = [
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '0.01',
            'orderId' => '112332',
            'number' => '10000080001641',
            'orderCreateDate' => '2018-01-10 10:40:05',
            'bank_name' => '工商銀行',
            'withdraw_host' => 'payment.http.withdraw.com',
            'shop_url' => 'http://pay.test/pay/',
            'branch' => '北京支行',
            'province' => '天津省',
            'city' => '很有市',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'rsa_private_key' => $this->privateKey,
        ];

        $result = '{"dealMsg":"\u6210\u529f","sign":"MnDEWQpBOpiME8iUOnD8kNuuQlKnWNgs8=","dealCode":"10000"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $gaoHuiTong = new GaoHuiTong();
        $gaoHuiTong->setContainer($this->container);
        $gaoHuiTong->setClient($this->client);
        $gaoHuiTong->setResponse($response);
        $gaoHuiTong->setOptions($sourceData);
        $gaoHuiTong->withdrawPayment();
    }
}
