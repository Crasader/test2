<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YiChiFu;
use Buzz\Message\Response;

class YiChiFuTest extends DurianTestCase
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
    private $option;

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

        // 轉換格式
        $search = [
            '-----BEGIN PUBLIC KEY-----',
            '-----END PUBLIC KEY-----',
            "\n",
        ];

        $content = str_replace($search, '', $pubkey['key']);

        $this->publicKey = base64_encode($content);

        $this->option = [
            'number' => '1111130200',
            'notify_url' => 'http://59.126.84.197:3030/return.php',
            'orderId' => '2018032811111',
            'orderCreateDate' => '2018-03-28 09:30:11',
            'amount' => '0.01',
            'paymentVendorId' => '1103',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'http://seafood.help.you',
            'postUrl' => 'yiqpay.com',
            'ip' => '111.235.135.54',
        ];

        $this->returnResult = [
            'pay_system' => '49745',
            'trade_no' => '1003450919',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '1111130200',
            'order_no' => '201803270000001913',
            'trade_status' => 'SUCCESS',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'HFG000005221224275',
            'order_time' => '2018-03-27 08:59:10',
            'notify_id' => '6449d835356847458ab8c21f3381be10',
            'trade_time' => '2018-03-27 08:59:13',
            'rsa_public_key' => $this->publicKey,
        ];

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

        $yiChiFu = new YiChiFu();
        $yiChiFu->setOptions([]);
        $yiChiFu->getVerifyData();
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

        $this->option['paymentVendorId'] = '99999';

        $yiChiFu = new YiChiFu();
        $yiChiFu->setOptions($this->option);
        $yiChiFu->getVerifyData();
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

        $this->option['rsa_private_key'] = base64_encode($privkey);

        $yiChiFu = new YiChiFu();
        $yiChiFu->setOptions($this->option);
        $yiChiFu->getVerifyData();
    }

    /**
     * 測試加密未返回response
     */
    public function testGetEncodeNoReturnResponse()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Empty Payment Gateway response',
            180089
        );

        $result = '<?xml version="1.0" encoding="UTF-8" ?><dinpay></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $yiChiFu = new YiChiFu();
        $yiChiFu->setContainer($this->container);
        $yiChiFu->setClient($this->client);
        $yiChiFu->setResponse($response);
        $yiChiFu->setOptions($this->option);
        $yiChiFu->getVerifyData();
    }

    /**
     * 測試加密未返回resp_code
     */
    public function testGetEncodeNoReturnRespCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<resp_desc>成功</resp_desc>' .
            '<sign_type>RSA-S</sign_type>' .
            '<sign>ZWENv7QbH6sIC4DhJ50wbG2ovyiuRGPAoPrnp ke9mGC2IDVVU/d HUmpFwWcWxwQLB sy1GgpvUL5fGsTrbSkxXsijQz6q0' .
            'Q81vTlKt0cBvw7PSeuhxHpxy1zeO jaNqTDesY6HPGCK6TPXv/roY3XKpG4 YOCzfGp XtbPzc=</sign>' .
            '<qrcode>weixin://wxpay/bizpayurl?pr=MtTpobS</qrcode>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $yiChiFu = new YiChiFu();
        $yiChiFu->setContainer($this->container);
        $yiChiFu->setClient($this->client);
        $yiChiFu->setResponse($response);
        $yiChiFu->setOptions($this->option);
        $yiChiFu->getVerifyData();
    }

    /**
     * 測試加密返回resp_code不為SUCCESS
     */
    public function testGetEncodeReturnRespCodeNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '商家订单号太长',
            180130
        );

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<resp_code>OREDER_NO_IS_TOO_LONG</resp_code>' .
            '<resp_desc>商家订单号太长</resp_desc>' .
            '<sign_type>RSA-S</sign_type>' .
            '<sign>ZWENv7QbH6sIC4DhJ50wbG2ovyiuRGPAoPrnp ke9mGC2IDVVU/d HUmpFwWcWxwQLB sy1GgpvUL5fGsTrbSkxXsijQz6q0' .
            'Q81vTlKt0cBvw7PSeuhxHpxy1zeO jaNqTDesY6HPGCK6TPXv/roY3XKpG4 YOCzfGp XtbPzc=</sign>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $yiChiFu = new YiChiFu();
        $yiChiFu->setContainer($this->container);
        $yiChiFu->setClient($this->client);
        $yiChiFu->setResponse($response);
        $yiChiFu->setOptions($this->option);
        $yiChiFu->getVerifyData();
    }

    /**
     * 測試加密未返回result_code
     */
    public function testGetEncodeNoReturnResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>成功</resp_desc>' .
            '<sign_type>RSA-S</sign_type>' .
            '<sign>ZWENv7QbH6sIC4DhJ50wbG2ovyiuRGPAoPrnp ke9mGC2IDVVU/d HUmpFwWcWxwQLB sy1GgpvUL5fGsTrbSkxXsijQz6q0' .
            'Q81vTlKt0cBvw7PSeuhxHpxy1zeO jaNqTDesY6HPGCK6TPXv/roY3XKpG4 YOCzfGp XtbPzc=</sign>' .
            '<qrcode>weixin://wxpay/bizpayurl?pr=MtTpobS</qrcode>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $yiChiFu = new YiChiFu();
        $yiChiFu->setContainer($this->container);
        $yiChiFu->setClient($this->client);
        $yiChiFu->setResponse($response);
        $yiChiFu->setOptions($this->option);
        $yiChiFu->getVerifyData();
    }

    /**
     * 測試加密返回result_code不等於0
     */
    public function testGetEncodeReturnResultCodeNotEqualToZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '获取二维码失败',
            180130
        );

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>1</result_code>' .
            '<result_desc>获取二维码失败</result_desc>' .
            '<sign_type>RSA-S</sign_type>' .
            '<sign>ZWENv7QbH6sIC4DhJ50wbG2ovyiuRGPAoPrnp ke9mGC2IDVVU/d HUmpFwWcWxwQLB sy1GgpvUL5fGsTrbSkxXsijQz6q0' .
            'Q81vTlKt0cBvw7PSeuhxHpxy1zeO jaNqTDesY6HPGCK6TPXv/roY3XKpG4 YOCzfGp XtbPzc=</sign>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $yiChiFu = new YiChiFu();
        $yiChiFu->setContainer($this->container);
        $yiChiFu->setClient($this->client);
        $yiChiFu->setResponse($response);
        $yiChiFu->setOptions($this->option);
        $yiChiFu->getVerifyData();
    }

    /**
     * 測試加密返回result_code不等於0，且沒有返回result_desc
     */
    public function testGetEncodeReturnResultCodeNotEqualToZeroAndNoResultDesc()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>1</result_code>' .
            '<sign_type>RSA-S</sign_type>' .
            '<sign>ZWENv7QbH6sIC4DhJ50wbG2ovyiuRGPAoPrnp ke9mGC2IDVVU/d HUmpFwWcWxwQLB sy1GgpvUL5fGsTrbSkxXsijQz6q0' .
            'Q81vTlKt0cBvw7PSeuhxHpxy1zeO jaNqTDesY6HPGCK6TPXv/roY3XKpG4 YOCzfGp XtbPzc=</sign>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $yiChiFu = new YiChiFu();
        $yiChiFu->setContainer($this->container);
        $yiChiFu->setClient($this->client);
        $yiChiFu->setResponse($response);
        $yiChiFu->setOptions($this->option);
        $yiChiFu->getVerifyData();
    }

    /**
     * 測試加密未返回qrcode
     */
    public function testGetEncodeNoReturnQrcode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>成功</resp_desc>' .
            '<result_code>0</result_code>' .
            '<result_desc>获取二维码成功</result_desc>' .
            '<sign_type>RSA-S</sign_type>' .
            '<sign>ZWENv7QbH6sIC4DhJ50wbG2ovyiuRGPAoPrnp ke9mGC2IDVVU/d HUmpFwWcWxwQLB sy1GgpvUL5fGsTrbSkxXsijQz6q0' .
            'Q81vTlKt0cBvw7PSeuhxHpxy1zeO jaNqTDesY6HPGCK6TPXv/roY3XKpG4 YOCzfGp XtbPzc=</sign>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $yiChiFu = new YiChiFu();
        $yiChiFu->setContainer($this->container);
        $yiChiFu->setClient($this->client);
        $yiChiFu->setResponse($response);
        $yiChiFu->setOptions($this->option);
        $yiChiFu->getVerifyData();
    }

    /**
     * 測試網銀支付
     */
    public function testPayWithOnlineBank()
    {
        $encodeStr = 'bank_code=ICBC&client_ip=111.235.135.54&input_charset=UTF-8&interface_version=V3.0' .
            '&merchant_code=1111130200&notify_url=http://59.126.84.197:3030/return.php' .
            '&order_amount=0.01&order_no=2018032811111&order_time=2018-03-28 09:30:11&' .
            'product_name=2018032811111&redo_flag=1&service_type=direct_pay';

        $this->option['paymentVendorId'] = '1';

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $yiChiFu = new YiChiFu();
        $yiChiFu->setOptions($this->option);
        $requestData = $yiChiFu->getVerifyData();

        $this->assertEquals('ICBC', $requestData['params']['bank_code']);
        $this->assertEquals('V3.0', $requestData['params']['interface_version']);
        $this->assertEquals('direct_pay', $requestData['params']['service_type']);
        $this->assertEquals('https://pay.yiqpay.com/gateway?input_charset=UTF-8', $requestData['post_url']);
        $this->assertEquals(base64_encode($sign), $requestData['params']['sign']);
        $this->assertEquals('0.01', $requestData['params']['order_amount']);
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>成功</resp_desc>' .
            '<result_code>0</result_code>' .
            '<result_desc>获取二维码成功</result_desc>' .
            '<sign_type>RSA-S</sign_type>' .
            '<sign>ZWENv7QbH6sIC4DhJ50wbG2ovyiuRGPAoPrnp ke9mGC2IDVVU/d HUmpFwWcWxwQLB sy1GgpvUL5fGsTrbSkxXsijQz6q0' .
            'Q81vTlKt0cBvw7PSeuhxHpxy1zeO jaNqTDesY6HPGCK6TPXv/roY3XKpG4 YOCzfGp XtbPzc=</sign>' .
            '<qrcode>qqpay://qqpay/bizpayurl?pr=MtTpobS</qrcode>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $yiChiFu = new YiChiFu();
        $yiChiFu->setContainer($this->container);
        $yiChiFu->setClient($this->client);
        $yiChiFu->setResponse($response);
        $yiChiFu->setOptions($this->option);
        $encodeData = $yiChiFu->getVerifyData();

        $this->assertEmpty($encodeData);
        $this->assertEquals('qqpay://qqpay/bizpayurl?pr=MtTpobS', $yiChiFu->getQrcode());
    }

    /**
     * 測試解密驗證支付平台未指定返回參數
     */
    public function testVerifyWithoutTradeStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['trade_status']);

        $yiChiFu = new YiChiFu();
        $yiChiFu->setOptions($this->returnResult);
        $yiChiFu->verifyOrderPayment([]);
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

        $yiChiFu = new YiChiFu();
        $yiChiFu->setOptions($this->returnResult);
        $yiChiFu->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = 'See You Again';

        $yiChiFu = new YiChiFu();
        $yiChiFu->setOptions($this->returnResult);
        $yiChiFu->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = 'test';
        $this->returnResult['rsa_public_key'] = '';

        $yiChiFu = new YiChiFu();
        $yiChiFu->setOptions($this->returnResult);
        $yiChiFu->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = 'test';
        $this->returnResult['rsa_public_key'] = '123';

        $yiChiFu = new YiChiFu();
        $yiChiFu->setOptions($this->returnResult);
        $yiChiFu->verifyOrderPayment([]);
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

        $encodeStr = 'bank_seq_no=HFG000005221224275&interface_version=V3.0&merchant_code=1111130200&' .
            'notify_id=6449d835356847458ab8c21f3381be10&notify_type=offline_notify&order_amount=0.01&' .
            'order_no=201803270000001913&order_time=2018-03-27+08%3A59%3A10&trade_no=1003450919&trade_status=UNPAY&' .
            'trade_time=2018-03-27+08%3A59%3A13';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $this->returnResult['sign'] = base64_encode($sign);
        $this->returnResult['trade_status'] = 'UNPAY';

        $yiChiFu = new YiChiFu();
        $yiChiFu->setOptions($this->returnResult);
        $yiChiFu->verifyOrderPayment([]);
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

        $encodeStr = 'bank_seq_no=HFG000005221224275&interface_version=V3.0&merchant_code=1111130200&' .
            'notify_id=6449d835356847458ab8c21f3381be10&notify_type=offline_notify&order_amount=0.01&' .
            'order_no=201803270000001913&order_time=2018-03-27+08%3A59%3A10&trade_no=1003450919&trade_status=SUCCESS&' .
            'trade_time=2018-03-27+08%3A59%3A13';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $this->returnResult['sign'] = base64_encode($sign);

        $entry = ['id' => '2014052200123'];

        $yiChiFu = new YiChiFu();
        $yiChiFu->setOptions($this->returnResult);
        $yiChiFu->verifyOrderPayment($entry);
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

        $encodeStr = 'bank_seq_no=HFG000005221224275&interface_version=V3.0&merchant_code=1111130200&' .
            'notify_id=6449d835356847458ab8c21f3381be10&notify_type=offline_notify&order_amount=0.01&' .
            'order_no=201803270000001913&order_time=2018-03-27+08%3A59%3A10&trade_no=1003450919&trade_status=SUCCESS&' .
            'trade_time=2018-03-27+08%3A59%3A13';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $this->returnResult['sign'] = base64_encode($sign);

        $entry = [
            'id' => '201803270000001913',
            'amount' => '1.0000',
        ];

        $yiChiFu = new YiChiFu();
        $yiChiFu->setOptions($this->returnResult);
        $yiChiFu->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功(異步返回)
     */
    public function testPaySuccess()
    {
        $encodeStr = 'bank_seq_no=HFG000005221224275&interface_version=V3.0&merchant_code=1111130200&' .
            'notify_id=6449d835356847458ab8c21f3381be10&notify_type=offline_notify&order_amount=0.01&' .
            'order_no=201803270000001913&order_time=2018-03-27+08%3A59%3A10&trade_no=1003450919&trade_status=SUCCESS&' .
            'trade_time=2018-03-27+08%3A59%3A13';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $this->returnResult['sign'] = base64_encode($sign);

        $entry = [
            'id' => '201803270000001913',
            'amount' => '0.0100',
        ];

        $yiChiFu = new YiChiFu();
        $yiChiFu->setOptions($this->returnResult);
        $yiChiFu->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $yiChiFu->getMsg());
    }

    /**
     * 測試訂單查詢未指定查詢參數
     */
    public function testPaymentTrackingWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $yiChiFu = new YiChiFu();
        $yiChiFu->paymentTracking();
    }

    /**
     * 測試訂單查詢產生簽名失敗
     */
    public function testPaymentTrackingGenerateSignatureFailure()
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

        $this->option['rsa_private_key'] = base64_encode($privkey);

        $yiChiFu = new YiChiFu();
        $yiChiFu->setOptions($this->option);
        $yiChiFu->paymentTracking();
    }

    /**
     * 測試訂單查詢沒帶入verify_url
     */
    public function testPaymentTrackingWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $this->option['verify_url'] = '';

        $yiChiFu = new YiChiFu();
        $yiChiFu->setOptions($this->option);
        $yiChiFu->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數is_success
     */
    public function testPaymentTrackingResultWithoutIsSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response></response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $yiChiFu = new YiChiFu();
        $yiChiFu->setContainer($this->container);
        $yiChiFu->setClient($this->client);
        $yiChiFu->setResponse($response);
        $yiChiFu->setOptions($this->option);
        $yiChiFu->paymentTracking();
    }

    /**
     * 測試訂單查詢失敗
     */
    public function testTrackingReturnPaymentTrackingFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment tracking failed',
            180081
        );

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>F</is_success>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $yiChiFu = new YiChiFu();
        $yiChiFu->setContainer($this->container);
        $yiChiFu->setClient($this->client);
        $yiChiFu->setResponse($response);
        $yiChiFu->setOptions($this->option);
        $yiChiFu->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數trade_status
     */
    public function testPaymentTrackingResultWithoutTradeStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $yiChiFu = new YiChiFu();
        $yiChiFu->setContainer($this->container);
        $yiChiFu->setClient($this->client);
        $yiChiFu->setResponse($response);
        $yiChiFu->setOptions($this->option);
        $yiChiFu->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數sign
     */
    public function testTrackingResultWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<trade><trade_status>UNPAY</trade_status></trade>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $yiChiFu = new YiChiFu();
        $yiChiFu->setContainer($this->container);
        $yiChiFu->setClient($this->client);
        $yiChiFu->setResponse($response);
        $yiChiFu->setOptions($this->option);
        $yiChiFu->paymentTracking();
    }

    /**
     * 測試訂單查詢結果簽名驗證錯誤
     */
    public function testTrackingReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign></sign>' .
            '<trade><trade_status>UNPAY</trade_status></trade>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $yiChiFu = new YiChiFu();
        $yiChiFu->setContainer($this->container);
        $yiChiFu->setClient($this->client);
        $yiChiFu->setResponse($response);
        $yiChiFu->setOptions($this->option);
        $yiChiFu->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單未支付
     */
    public function testTrackingReturnUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $encodeStr = 'merchant_code=1111130200&order_amount=0.01&order_no=2018032700001' .
            '&order_time=2018-03-27 15:09:53&trade_no=1136435210&trade_status=UNPAY&trade_time=2018-03-27 15:10:02';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>1111130200</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>2018032700001</order_no>' .
            '<order_time>2018-03-27 15:09:53</order_time>' .
            '<trade_no>1136435210</trade_no>' .
            '<trade_status>UNPAY</trade_status>' .
            '<trade_time>2018-03-27 15:10:02</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $yiChiFu = new YiChiFu();
        $yiChiFu->setContainer($this->container);
        $yiChiFu->setClient($this->client);
        $yiChiFu->setResponse($response);
        $yiChiFu->setOptions($this->option);
        $yiChiFu->paymentTracking();
    }

    /**
     * 測試訂單查詢結果支付失敗
     */
    public function testTrackingReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $encodeStr = 'merchant_code=1111130200&order_amount=0.01&order_no=2018032700001' .
            '&order_time=2018-03-27 15:09:53&trade_no=1136435210&trade_status=FAILED&trade_time=2018-03-27 15:10:02';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>1111130200</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>2018032700001</order_no>' .
            '<order_time>2018-03-27 15:09:53</order_time>' .
            '<trade_no>1136435210</trade_no>' .
            '<trade_status>FAILED</trade_status>' .
            '<trade_time>2018-03-27 15:10:02</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $yiChiFu = new YiChiFu();
        $yiChiFu->setContainer($this->container);
        $yiChiFu->setClient($this->client);
        $yiChiFu->setResponse($response);
        $yiChiFu->setOptions($this->option);
        $yiChiFu->paymentTracking();
    }

    /**
     * 測試訂單查詢結果為訂單單號錯誤
     */
    public function testTrackingReturnWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $encodeStr = 'merchant_code=1111130200&order_amount=0.01&order_no=2018032700001' .
            '&order_time=2018-03-27 15:09:53&trade_no=1136435210&trade_status=SUCCESS&trade_time=2018-03-27 15:10:02';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>1111130200</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>2018032700001</order_no>' .
            '<order_time>2018-03-27 15:09:53</order_time>' .
            '<trade_no>1136435210</trade_no>' .
            '<trade_status>SUCCESS</trade_status>' .
            '<trade_time>2018-03-27 15:10:02</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $yiChiFu = new YiChiFu();
        $yiChiFu->setContainer($this->container);
        $yiChiFu->setClient($this->client);
        $yiChiFu->setResponse($response);
        $yiChiFu->setOptions($this->option);
        $yiChiFu->paymentTracking();
    }

    /**
     * 測試訂單查詢結果支付失敗
     */
    public function testTrackingReturnWithPayAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $encodeStr = 'merchant_code=1111130200&order_amount=0.05&order_no=2018032811111' .
            '&order_time=2018-03-27 15:09:53&trade_no=1136435210&trade_status=SUCCESS&trade_time=2018-03-27 15:10:02';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>1111130200</merchant_code>' .
            '<order_amount>0.05</order_amount>' .
            '<order_no>2018032811111</order_no>' .
            '<order_time>2018-03-27 15:09:53</order_time>' .
            '<trade_no>1136435210</trade_no>' .
            '<trade_status>SUCCESS</trade_status>' .
            '<trade_time>2018-03-27 15:10:02</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $yiChiFu = new YiChiFu();
        $yiChiFu->setContainer($this->container);
        $yiChiFu->setClient($this->client);
        $yiChiFu->setResponse($response);
        $yiChiFu->setOptions($this->option);
        $yiChiFu->paymentTracking();
    }

    /**
     * 測試訂單查詢
     */
    public function testPaymentTracking()
    {
        $encodeStr = 'merchant_code=1111130200&order_amount=0.01&order_no=2018032811111' .
            '&order_time=2018-03-27 09:30:11&trade_no=1003450919&trade_status=SUCCESS&trade_time=2018-03-27 09:31:31';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign_type>RSA-S</sign_type>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>1111130200</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>2018032811111</order_no>' .
            '<order_time>2018-03-27 09:30:11</order_time>' .
            '<trade_no>1003450919</trade_no>' .
            '<trade_status>SUCCESS</trade_status>' .
            '<trade_time>2018-03-27 09:31:31</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $yiChiFu = new YiChiFu();
        $yiChiFu->setContainer($this->container);
        $yiChiFu->setClient($this->client);
        $yiChiFu->setResponse($response);
        $yiChiFu->setOptions($this->option);
        $yiChiFu->paymentTracking();
    }

    /**
     * 測試取得訂單查詢需要的參數時未指定查詢參數
     */
    public function testGetPaymentTrackingDataWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $yiChiFu = new YiChiFu();
        $yiChiFu->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒代入verify_url
     */
    public function testGetPaymentTrackingDataWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $this->option['verify_url'] = '';

        $yiChiFu = new YiChiFu();
        $yiChiFu->setOptions($this->option);
        $yiChiFu->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $yiChiFu = new YiChiFu();
        $yiChiFu->setOptions($this->option);
        $trackingData = $yiChiFu->getPaymentTrackingData();

        $this->assertEquals('single_trade_query', $trackingData['form']['service_type']);
        $this->assertEquals('1111130200', $trackingData['form']['merchant_code']);
        $this->assertEquals('V3.1', $trackingData['form']['interface_version']);
        $this->assertEquals('RSA-S', $trackingData['form']['sign_type']);
        $this->assertEquals('2018032811111', $trackingData['form']['order_no']);
        $this->assertEquals(['172.26.54.42', '172.26.54.41'], $trackingData['verify_ip']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('/query', $trackingData['path']);
        $this->assertEquals('http://seafood.help.you', $trackingData['headers']['Host']);
    }

    /**
     * 測試驗證訂單查詢是否成功時缺少回傳參數
     */
    public function testPaymentTrackingVerifyWithoutParameter()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response></response></dinpay>';

        $this->option['content'] = $result;

        $yiChiFu = new YiChiFu();
        $yiChiFu->setOptions($this->option);
        $yiChiFu->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢失敗
     */
    public function testPaymentTrackingVerifyFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment tracking failed',
            180081
        );

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>F</is_success>' .
            '</response></dinpay>';

        $this->option['content'] = $result;

        $yiChiFu = new YiChiFu();
        $yiChiFu->setOptions($this->option);
        $yiChiFu->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果缺少回傳參數trade_status
     */
    public function testPaymentTrackingVerifyWithoutTradeStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '</response></dinpay>';

        $this->option['content'] = $result;

        $yiChiFu = new YiChiFu();
        $yiChiFu->setOptions($this->option);
        $yiChiFu->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果缺少回傳參數sign
     */
    public function testPaymentTrackingVerifyWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<trade><trade_status>UNPAY</trade_status></trade>' .
            '</response></dinpay>';

        $this->option['content'] = $result;

        $yiChiFu = new YiChiFu();
        $yiChiFu->setOptions($this->option);
        $yiChiFu->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果簽名驗證錯誤
     */
    public function testPaymentTrackingVerifySignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign></sign>' .
            '<trade><trade_status>UNPAY</trade_status></trade>' .
            '</response></dinpay>';

        $this->option['content'] = $result;

        $yiChiFu = new YiChiFu();
        $yiChiFu->setOptions($this->option);
        $yiChiFu->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果訂單未支付
     */
    public function testPaymentTrackingVerifyUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $encodeStr = 'merchant_code=1111130200&order_amount=0.01&order_no=2018032700001' .
            '&order_time=2018-03-27 15:09:53&trade_no=1136435210&trade_status=UNPAY&trade_time=2018-03-27 15:10:02';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>1111130200</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>2018032700001</order_no>' .
            '<order_time>2018-03-27 15:09:53</order_time>' .
            '<trade_no>1136435210</trade_no>' .
            '<trade_status>UNPAY</trade_status>' .
            '<trade_time>2018-03-27 15:10:02</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $this->option['content'] = $result;

        $yiChiFu = new YiChiFu();
        $yiChiFu->setOptions($this->option);
        $yiChiFu->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果支付失敗
     */
    public function testPaymentTrackingVerifyPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $encodeStr = 'merchant_code=1111130200&order_amount=0.01&order_no=2018032700001' .
            '&order_time=2018-03-27 15:09:53&trade_no=1136435210&trade_status=FAILED&trade_time=2018-03-27 15:10:02';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>1111130200</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>2018032700001</order_no>' .
            '<order_time>2018-03-27 15:09:53</order_time>' .
            '<trade_no>1136435210</trade_no>' .
            '<trade_status>FAILED</trade_status>' .
            '<trade_time>2018-03-27 15:10:02</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $this->option['content'] = $result;

        $yiChiFu = new YiChiFu();
        $yiChiFu->setOptions($this->option);
        $yiChiFu->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為訂單單號錯誤
     */
    public function testPaymentTrackingVerifyWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $encodeStr = 'merchant_code=1111130200&order_amount=0.01&order_no=2018032700001' .
            '&order_time=2018-03-27 15:09:53&trade_no=1136435210&trade_status=SUCCESS&trade_time=2018-03-27 15:10:02';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>1111130200</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>2018032700001</order_no>' .
            '<order_time>2018-03-27 15:09:53</order_time>' .
            '<trade_no>1136435210</trade_no>' .
            '<trade_status>SUCCESS</trade_status>' .
            '<trade_time>2018-03-27 15:10:02</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $this->option['content'] = $result;

        $yiChiFu = new YiChiFu();
        $yiChiFu->setOptions($this->option);
        $yiChiFu->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為支付金額錯誤
     */
    public function testPaymentTrackingVerifyWithPayAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $encodeStr = 'merchant_code=1111130200&order_amount=0.05&order_no=2018032811111' .
            '&order_time=2018-03-27 15:09:53&trade_no=1136435210&trade_status=SUCCESS&trade_time=2018-03-27 15:10:02';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>1111130200</merchant_code>' .
            '<order_amount>0.05</order_amount>' .
            '<order_no>2018032811111</order_no>' .
            '<order_time>2018-03-27 15:09:53</order_time>' .
            '<trade_no>1136435210</trade_no>' .
            '<trade_status>SUCCESS</trade_status>' .
            '<trade_time>2018-03-27 15:10:02</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $this->option['content'] = $result;

        $yiChiFu = new YiChiFu();
        $yiChiFu->setOptions($this->option);
        $yiChiFu->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $encodeStr = 'merchant_code=1111130200&order_amount=0.01&order_no=2018032811111' .
            '&order_time=2018-03-22 09:30:11&trade_no=1003450919&trade_status=SUCCESS&trade_time=2018-03-22 09:31:31';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign_type>RSA-S</sign_type>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>1111130200</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>2018032811111</order_no>' .
            '<order_time>2018-03-22 09:30:11</order_time>' .
            '<trade_no>1003450919</trade_no>' .
            '<trade_status>SUCCESS</trade_status>' .
            '<trade_time>2018-03-22 09:31:31</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $this->option['content'] = $result;

        $yiChiFu = new YiChiFu();
        $yiChiFu->setOptions($this->option);
        $yiChiFu->paymentTrackingVerify();
    }
}
