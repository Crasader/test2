<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\TianFuPay;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class TianFuPayTest extends DurianTestCase
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
    private $privateKey;

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

        $config = [
            'private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        // Create the keypair
        $res = openssl_pkey_new($config);

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

        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();
        $this->container->expects($this->any())
            ->method('get')
            ->willReturn($mockLogger);

        $this->client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $this->option = [
            'number' => '9527',
            'notify_url' => 'http://www.seafood.help/',
            'orderId' => '201805310000046378',
            'amount' => '10',
            'orderCreateDate' => '2018-05-31 14:05:23',
            'paymentVendorId' => '1092',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.tf767.com',
            'rsa_private_key' => $this->privateKey,
        ];

        $this->returnResult = [
            'inputCharset' => 'UTF-8',
            'payDatetime' => '20180531140713',
            'orderNo' => '201805310000046378',
            'payResult' => '1',
            'orderAmount' => '1000',
            'signType' => '1',
            'returnDatetime' => '20180531140713',
            'partnerId' => '1811413706050316',
            'orderDatetime' => '20180531140523',
            'extraCommonParam' => '',
            'tradeSeq' => 'TF87918053114350140526',
            'rsa_public_key' => $this->publicKey,
        ];

        $encodeData = [];

        foreach ($this->returnResult as $key => $value) {
            if (!in_array($key, ['signType', 'rsa_public_key']) && $value != '') {
                $encodeData[$key] = $value;
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $this->returnResult['signMsg'] = base64_encode($sign);
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

        $tianFuPay = new TianFuPay();
        $tianFuPay->getVerifyData();
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

        $tianFuPay = new TianFuPay();
        $tianFuPay->setOptions($this->option);
        $tianFuPay->getVerifyData();
    }

    /**
     * 測試支付時取得RSA私鑰為空字串
     */
    public function testPayGetRsaPrivateKeyEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa private key is empty',
            180092
        );

        $this->option['rsa_private_key'] = '';

        $tianFuPay = new TianFuPay();
        $tianFuPay->setOptions($this->option);
        $tianFuPay->getVerifyData();
    }

    /**
     * 測試支付時取得RSA私鑰失敗
     */
    public function testPayGetRsaPrivateKeyError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa private key failure',
            180093
        );

        $this->option['rsa_private_key'] = '123456';

        $tianFuPay = new TianFuPay();
        $tianFuPay->setOptions($this->option);
        $tianFuPay->getVerifyData();
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

        // Create the keypair
        $res = openssl_pkey_new($config);

        $privkey = '';

        // Get private key
        openssl_pkey_export($res, $privkey);

        $this->option['rsa_private_key'] = base64_encode($privkey);

        $tianFuPay = new TianFuPay();
        $tianFuPay->setOptions($this->option);
        $tianFuPay->getVerifyData();
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

        $tianFuPay = new TianFuPay();
        $tianFuPay->setOptions($this->option);
        $tianFuPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回errCode
     */
    public function testPayReturnWithoutErrCode()
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

        $tianFuPay = new TianFuPay();
        $tianFuPay->setContainer($this->container);
        $tianFuPay->setClient($this->client);
        $tianFuPay->setResponse($response);
        $tianFuPay->setOptions($this->option);
        $tianFuPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回errMsg
     */
    public function testPayReturnWithoutErrMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = ['errCode' => '0000'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $tianFuPay = new TianFuPay();
        $tianFuPay->setContainer($this->container);
        $tianFuPay->setClient($this->client);
        $tianFuPay->setResponse($response);
        $tianFuPay->setOptions($this->option);
        $tianFuPay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '金额不能小于5元',
            180130
        );

        $result = [
            'orderNo' => '201805310000046378',
            'errMsg' => '金额不能小于5元',
            'errCode' => '{PE10014}',
            'qrCode' => '',
            'retHtml' => '',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $tianFuPay = new TianFuPay();
        $tianFuPay->setContainer($this->container);
        $tianFuPay->setClient($this->client);
        $tianFuPay->setResponse($response);
        $tianFuPay->setOptions($this->option);
        $tianFuPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回qrCode
     */
    public function testPayReturnWithoutQrCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $signMsg = 'YngA83t1Io35RtuOUUfKsRE741m8Il+10ZzL8FtsMA6cRcacD2INBGxU2ak1X65PTVngAgMZK/678PxDdxMSACPHlD+Bla' .
            'XH8rIcC2nFQ33E84t7JgY+t8/6ElBAVinTNjcaxQzUO7Q00IyUsEC1RXZp2rsWhmP2DjeSNcRsmvk=';

        $result = [
            'orderNo' => '201805310000046378',
            'signMsg' => $signMsg,
            'signType' => '1',
            'errMsg' => '',
            'errCode' => '0000',
            'retHtml' => '',
            'tradeSeq' => 'TF87918053114350140526',
            'extraCommonParam' => '',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $tianFuPay = new TianFuPay();
        $tianFuPay->setContainer($this->container);
        $tianFuPay->setClient($this->client);
        $tianFuPay->setResponse($response);
        $tianFuPay->setOptions($this->option);
        $tianFuPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回retHtml
     */
    public function testPayReturnWithoutRetHtml()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $signMsg = 'YngA83t1Io35RtuOUUfKsRE741m8Il+10ZzL8FtsMA6cRcacD2INBGxU2ak1X65PTVngAgMZK/678PxDdxMSACPHlD+Bla' .
            'XH8rIcC2nFQ33E84t7JgY+t8/6ElBAVinTNjcaxQzUO7Q00IyUsEC1RXZp2rsWhmP2DjeSNcRsmvk=';

        $result = [
            'orderNo' => '201805310000046378',
            'signMsg' => $signMsg,
            'signType' => '1',
            'errMsg' => '',
            'errCode' => '0000',
            'qrCode' => 'HTTPS://QR.ALIPAY.COM/FKX039119RLZHIIMZ1HTEE?t=1527415207687',
            'tradeSeq' => 'TF87918053114350140526',
            'extraCommonParam' => '',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $tianFuPay = new TianFuPay();
        $tianFuPay->setContainer($this->container);
        $tianFuPay->setClient($this->client);
        $tianFuPay->setResponse($response);
        $tianFuPay->setOptions($this->option);
        $tianFuPay->getVerifyData();
    }

    /**
     * 測試支付寶手機支付沒有返回open
     */
    public function testAliPayPhonePayWithoutOpen()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $this->option['paymentVendorId'] = '1098';

        $signMsg = 'VB9C3fjVe7kFSKcEbIR6bLrx+iaja7AsvYn9fpf6lIExNeH3B++/c1RZpOQJ03UcFCmhdqicjV3VDV4sp/' .
            'GBWHajUaKKgHALNTAGGOYDxRsaNpHVJNKuglBVtacPpMbEprhDR8wI4ZHFFTBNP0MZR9vrC6Xk4TWcsSjT40KSeUc=';

        $retHtml = '<html><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><body></body>' .
            '<script></script></html>';

        $result = [
            'orderNo' => '201805310000046378',
            'signMsg' => $signMsg,
            'signType' => '1',
            'errMsg' => '',
            'errCode' => '0000',
            'qrCode' => '',
            'retHtml' => $retHtml,
            'tradeSeq' => 'TF53618053138533135833',
            'extraCommonParam' => '',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $tianFuPay = new TianFuPay();
        $tianFuPay->setContainer($this->container);
        $tianFuPay->setClient($this->client);
        $tianFuPay->setResponse($response);
        $tianFuPay->setOptions($this->option);
        $tianFuPay->getVerifyData();
    }

    /**
     * 測試京東手機支付沒有返回action
     */
    public function testJDPhonePayWithoutAction()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $this->option['paymentVendorId'] = '1108';

        $signMsg = 'oINcnTWB0Ea1mcx1iR0OcN8xIGwnRMke4It2cx2e5ZhU+D7v9K3fM2pzIZcDUOrdv8ZJpw5xk7gRDodS7Y' .
            'nTSvtR+2zX4J+8qMIC7nfl8OVkUP5jAgcIBdhrOIW7JS5ksoB/bMLQ0uXj1axLnqh1yIm/2nAsqmqbtUKev8Y23ts=';

        $retHtml = "<form name='fm' method='post'>" .
            "<input type='hidden' name='orderSource' value='1' />" .
            "<input type='hidden' name='orderNo' value='100000100021474635' />" .
            "<input type='hidden' name='payerIp' value='103.70.77.158' />" .
            "<input type='hidden' name='sign' value='57749e203246bf27064a5af2d15f0a15' />" .
            "<input type='hidden' name='version' value='V2.0' />" .
            "<input type='hidden' name='productName' value='100000100021474635' />" .
            "<input type='hidden' name='payChannelType' value='1' />" .
            "<input type='hidden' name='orderAmount' value='500.0' />" .
            "<input type='hidden' name='orderTime' value='20180531135558' />" .
            "<input type='hidden' name='payChannelCode' value='CR_WAP' />" .
            "<input type='hidden' name='service' value='bankPay' />" .
            "<input type='hidden' name='pageUrl' value='http://139.129.206.79:29606/PayApi" .
            "/payCall/standardPayCall/4~000001536474120001~5' />" .
            "<input type='hidden' name='curCode' value='CNY' />" .
            "<input type='hidden' name='merchantNo' value='CZ0002910' />" .
            "<input type='hidden' name='bgUrl' value='http://139.129.206.79:29606/PayApi" .
            "/payCall/standardPayCall/3~000001536474120001~5' />" .
            "</form><script language='JavaScript' > document.fm.submit();</script> ";

        $result = [
            'orderNo' => '201805310000046378',
            'signMsg' => $signMsg,
            'signType' => '1',
            'errMsg' => '',
            'errCode' => '0000',
            'qrCode' => '',
            'retHtml' => $retHtml,
            'tradeSeq' => '72418053124862135559',
            'extraCommonParam' => '',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $tianFuPay = new TianFuPay();
        $tianFuPay->setContainer($this->container);
        $tianFuPay->setClient($this->client);
        $tianFuPay->setResponse($response);
        $tianFuPay->setOptions($this->option);
        $tianFuPay->getVerifyData();
    }

    /**
     * 測試京東手機支付沒有返回input
     */
    public function testJDPhonePayWithoutInput()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $this->option['paymentVendorId'] = '1108';

        $signMsg = 'oINcnTWB0Ea1mcx1iR0OcN8xIGwnRMke4It2cx2e5ZhU+D7v9K3fM2pzIZcDUOrdv8ZJpw5xk7gRDodS7Y' .
            'nTSvtR+2zX4J+8qMIC7nfl8OVkUP5jAgcIBdhrOIW7JS5ksoB/bMLQ0uXj1axLnqh1yIm/2nAsqmqbtUKev8Y23ts=';

        $retHtml = "<form name='fm' method='post' action='http://zftapi.shijihuitong.com/PayApi/bankPay'>" .
            "</form><script language='JavaScript' > document.fm.submit();</script> ";

        $result = [
            'orderNo' => '201805310000046378',
            'signMsg' => $signMsg,
            'signType' => '1',
            'errMsg' => '',
            'errCode' => '0000',
            'qrCode' => '',
            'retHtml' => $retHtml,
            'tradeSeq' => '72418053124862135559',
            'extraCommonParam' => '',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $tianFuPay = new TianFuPay();
        $tianFuPay->setContainer($this->container);
        $tianFuPay->setClient($this->client);
        $tianFuPay->setResponse($response);
        $tianFuPay->setOptions($this->option);
        $tianFuPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $signMsg = 'YngA83t1Io35RtuOUUfKsRE741m8Il+10ZzL8FtsMA6cRcacD2INBGxU2ak1X65PTVngAgMZK/678PxDdxMSACPHlD+Bla' .
            'XH8rIcC2nFQ33E84t7JgY+t8/6ElBAVinTNjcaxQzUO7Q00IyUsEC1RXZp2rsWhmP2DjeSNcRsmvk=';

        $result = [
            'orderNo' => '201805310000046378',
            'signMsg' => $signMsg,
            'signType' => '1',
            'errMsg' => '',
            'errCode' => '0000',
            'qrCode' => 'HTTPS://QR.ALIPAY.COM/FKX039119RLZHIIMZ1HTEE?t=1527415207687',
            'retHtml' => '',
            'tradeSeq' => 'TF87918053114350140526',
            'extraCommonParam' => '',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $tianFuPay = new TianFuPay();
        $tianFuPay->setContainer($this->container);
        $tianFuPay->setClient($this->client);
        $tianFuPay->setResponse($response);
        $tianFuPay->setOptions($this->option);
        $data = $tianFuPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('HTTPS://QR.ALIPAY.COM/FKX039119RLZHIIMZ1HTEE?t=1527415207687', $tianFuPay->getQrcode());
    }

    /**
     * 測試支付寶手機支付
     */
    public function testAliPayPhonePay()
    {
        $this->option['paymentVendorId'] = '1098';

        $signMsg = 'VB9C3fjVe7kFSKcEbIR6bLrx+iaja7AsvYn9fpf6lIExNeH3B++/c1RZpOQJ03UcFCmhdqicjV3VDV4sp/' .
            'GBWHajUaKKgHALNTAGGOYDxRsaNpHVJNKuglBVtacPpMbEprhDR8wI4ZHFFTBNP0MZR9vrC6Xk4TWcsSjT40KSeUc=';

        $retHtml = '<html><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><body></body>' .
            '<script>window.open(\'HTTPS://QR.ALIPAY.COM/FKX00613RNTBP6WA98JEC5?t=1527415206308\',\'_self\');' .
            '</script></html>';

        $result = [
            'orderNo' => '201805310000046378',
            'signMsg' => $signMsg,
            'signType' => '1',
            'errMsg' => '',
            'errCode' => '0000',
            'qrCode' => '',
            'retHtml' => $retHtml,
            'tradeSeq' => 'TF53618053138533135833',
            'extraCommonParam' => '',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $tianFuPay = new TianFuPay();
        $tianFuPay->setContainer($this->container);
        $tianFuPay->setClient($this->client);
        $tianFuPay->setResponse($response);
        $tianFuPay->setOptions($this->option);
        $data = $tianFuPay->getVerifyData();

        $this->assertEquals('HTTPS://QR.ALIPAY.COM/FKX00613RNTBP6WA98JEC5', $data['post_url']);
        $this->assertEquals('1527415206308', $data['params']['t']);
        $this->assertEquals('GET', $tianFuPay->getPayMethod());
    }

    /**
     * 測試京東手機支付
     */
    public function testJDPhonePay()
    {
        $this->option['paymentVendorId'] = '1108';

        $signMsg = 'oINcnTWB0Ea1mcx1iR0OcN8xIGwnRMke4It2cx2e5ZhU+D7v9K3fM2pzIZcDUOrdv8ZJpw5xk7gRDodS7Y' .
            'nTSvtR+2zX4J+8qMIC7nfl8OVkUP5jAgcIBdhrOIW7JS5ksoB/bMLQ0uXj1axLnqh1yIm/2nAsqmqbtUKev8Y23ts=';

        $retHtml = "<form name='fm' method='post' action='http://zftapi.shijihuitong.com/PayApi/bankPay'>" .
            "<input type='hidden' name='orderSource' value='1' />" .
            "<input type='hidden' name='orderNo' value='100000100021474635' />" .
            "<input type='hidden' name='payerIp' value='103.70.77.158' />" .
            "<input type='hidden' name='sign' value='57749e203246bf27064a5af2d15f0a15' />" .
            "<input type='hidden' name='version' value='V2.0' />" .
            "<input type='hidden' name='productName' value='100000100021474635' />" .
            "<input type='hidden' name='payChannelType' value='1' />" .
            "<input type='hidden' name='orderAmount' value='500.0' />" .
            "<input type='hidden' name='orderTime' value='20180531135558' />" .
            "<input type='hidden' name='payChannelCode' value='CR_WAP' />" .
            "<input type='hidden' name='service' value='bankPay' />" .
            "<input type='hidden' name='pageUrl' value='http://139.129.206.79:29606/PayApi" .
            "/payCall/standardPayCall/4~000001536474120001~5' />" .
            "<input type='hidden' name='curCode' value='CNY' />" .
            "<input type='hidden' name='merchantNo' value='CZ0002910' />" .
            "<input type='hidden' name='bgUrl' value='http://139.129.206.79:29606/PayApi" .
            "/payCall/standardPayCall/3~000001536474120001~5' />" .
            "</form><script language='JavaScript' > document.fm.submit();</script> ";

        $result = [
            'orderNo' => '201805310000046378',
            'signMsg' => $signMsg,
            'signType' => '1',
            'errMsg' => '',
            'errCode' => '0000',
            'qrCode' => '',
            'retHtml' => $retHtml,
            'tradeSeq' => '72418053124862135559',
            'extraCommonParam' => '',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $tianFuPay = new TianFuPay();
        $tianFuPay->setContainer($this->container);
        $tianFuPay->setClient($this->client);
        $tianFuPay->setResponse($response);
        $tianFuPay->setOptions($this->option);
        $data = $tianFuPay->getVerifyData();

        $pageUrl = 'http://139.129.206.79:29606/PayApi/payCall/standardPayCall/4~000001536474120001~5';
        $bgUrl = 'http://139.129.206.79:29606/PayApi/payCall/standardPayCall/3~000001536474120001~5';

        $this->assertEquals('http://zftapi.shijihuitong.com/PayApi/bankPay', $data['post_url']);
        $this->assertEquals('1', $data['params']['orderSource']);
        $this->assertEquals('100000100021474635', $data['params']['orderNo']);
        $this->assertEquals('103.70.77.158', $data['params']['payerIp']);
        $this->assertEquals('57749e203246bf27064a5af2d15f0a15', $data['params']['sign']);
        $this->assertEquals('V2.0', $data['params']['version']);
        $this->assertEquals('100000100021474635', $data['params']['productName']);
        $this->assertEquals('1', $data['params']['payChannelType']);
        $this->assertEquals('500.0', $data['params']['orderAmount']);
        $this->assertEquals('20180531135558', $data['params']['orderTime']);
        $this->assertEquals('CR_WAP', $data['params']['payChannelCode']);
        $this->assertEquals('bankPay', $data['params']['service']);
        $this->assertEquals($pageUrl, $data['params']['pageUrl']);
        $this->assertEquals('CNY', $data['params']['curCode']);
        $this->assertEquals('CZ0002910', $data['params']['merchantNo']);
        $this->assertEquals($bgUrl, $data['params']['bgUrl']);
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

        $tianFuPay = new TianFuPay();
        $tianFuPay->verifyOrderPayment([]);
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

        unset($this->returnResult['signMsg']);

        $tianFuPay = new TianFuPay();
        $tianFuPay->setOptions($this->returnResult);
        $tianFuPay->verifyOrderPayment([]);
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

        $tianFuPay = new TianFuPay();
        $tianFuPay->setOptions($this->returnResult);
        $tianFuPay->verifyOrderPayment([]);
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

        $this->returnResult['rsa_public_key'] = '123456';

        $tianFuPay = new TianFuPay();
        $tianFuPay->setOptions($this->returnResult);
        $tianFuPay->verifyOrderPayment([]);
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

        $this->returnResult['signMsg'] = 'dGYR2LiTLa+A6rX+PZ07C0c2PiYbdF/1g+YttHpdV2TE9NE8UUM';

        $tianFuPay = new TianFuPay();
        $tianFuPay->setOptions($this->returnResult);
        $tianFuPay->verifyOrderPayment([]);
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

        $this->returnResult['payResult'] = '2';

        $encodeData = [];

        foreach ($this->returnResult as $key => $value) {
            if (!in_array($key, ['signType', 'rsa_public_key', 'signMsg']) && $value != '') {
                $encodeData[$key] = $value;
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey));

        $this->returnResult['signMsg'] = base64_encode($sign);

        $tianFuPay = new TianFuPay();
        $tianFuPay->setOptions($this->returnResult);
        $tianFuPay->verifyOrderPayment([]);
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

        $tianFuPay = new TianFuPay();
        $tianFuPay->setOptions($this->returnResult);
        $tianFuPay->verifyOrderPayment($entry);
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
            'id' => '201805310000046378',
            'amount' => '123',
        ];

        $tianFuPay = new TianFuPay();
        $tianFuPay->setOptions($this->returnResult);
        $tianFuPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $entry = [
            'id' => '201805310000046378',
            'amount' => '10',
        ];

        $tianFuPay = new TianFuPay();
        $tianFuPay->setOptions($this->returnResult);
        $tianFuPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $tianFuPay->getMsg());
    }
}
