<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ZhiTongBao;
use Buzz\Message\Response;

class ZhitongBaoTest extends DurianTestCase
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

        $zhiTongBao = new ZhiTongBao();

        $sourceData = ['number' => ''];

        $zhiTongBao->setOptions($sourceData);
        $zhiTongBao->getVerifyData();
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

        $zhiTongBao = new ZhiTongBao();

        $sourceData = [
            'number' => '9527',
            'notify_url' => 'http://yes9527.com.tw/return.php',
            'orderId' => '2017091211111',
            'orderCreateDate' => '2017-09-12 09:30:11',
            'amount' => '0.01',
            'username' => 'powei',
            'paymentVendorId' => '99999',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.api.ZhiTongBao.com',
            'ip' => '111.235.135.54',
        ];

        $zhiTongBao->setOptions($sourceData);
        $zhiTongBao->getVerifyData();
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

        $zhiTongBao = new ZhiTongBao();

        $sourceData = [
            'number' => '9527',
            'notify_url' => 'http://yes9527.com.tw',
            'orderId' => '2017091211111',
            'orderCreateDate' => '2017-09-12 09:30:11',
            'amount' => '0.01',
            'username' => 'po-wei',
            'paymentVendorId' => '1092',
            'rsa_private_key' => base64_encode($privkey),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.api.ZhiTongBao.com',
            'ip' => '111.235.135.54',
        ];

        $zhiTongBao->setOptions($sourceData);
        $zhiTongBao->getVerifyData();
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
            '<sign>POWEIISAGOODPEOPLEVERYNICE</sign>' .
            '<qrcode>weixin://wxpay/bizpayurl?pr=MtTpobS</qrcode>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'notify_url' => 'http://yes9527.com.tw',
            'orderId' => '2017091211111',
            'orderCreateDate' => '2017-09-12 09:30:11',
            'amount' => '0.01',
            'username' => 'po-wei',
            'paymentVendorId' => '1092',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.api.ZhiTongBao.com',
            'ip' => '111.235.135.54',
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setResponse($response);
        $zhiTongBao->setContainer($this->container);
        $zhiTongBao->setClient($this->client);
        $zhiTongBao->setOptions($sourceData);
        $zhiTongBao->getVerifyData();
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
            '<sign>POWEIISAGOODPEOPLEVERYNICE</sign>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'notify_url' => 'http://yes9527.com.tw',
            'orderId' => '2017091211111444444444',
            'orderCreateDate' => '2017-09-12 09:30:11',
            'amount' => '0.01',
            'username' => 'POWEI',
            'paymentVendorId' => '1092',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.api.ZhiTongBao.com',
            'ip' => '111.235.135.54',
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setResponse($response);
        $zhiTongBao->setContainer($this->container);
        $zhiTongBao->setClient($this->client);
        $zhiTongBao->setOptions($sourceData);
        $zhiTongBao->getVerifyData();
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
            '<sign>POWEIISAGOODPEOPLEVERYNICE</sign>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'notify_url' => 'http://yes9527.com.tw',
            'orderId' => '2017091211111',
            'orderCreateDate' => '2017-09-12 09:30:11',
            'amount' => '0.01',
            'username' => 'PO-WEI',
            'paymentVendorId' => '1092',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.api.ZhiTongBao.com',
            'ip' => '111.235.135.54',
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setResponse($response);
        $zhiTongBao->setContainer($this->container);
        $zhiTongBao->setClient($this->client);
        $zhiTongBao->setOptions($sourceData);
        $zhiTongBao->getVerifyData();
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
            '<sign>POWEIISAGOODPEOPLEVERYNICE</sign>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'notify_url' => 'http://yes9527.com.tw',
            'orderId' => '2017091211111',
            'orderCreateDate' => '2017-09-12 09:30:11',
            'amount' => '0.01',
            'username' => 'po-wei',
            'paymentVendorId' => '1092',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.api.ZhiTongBao.com',
            'ip' => '111.235.135.54',
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setResponse($response);
        $zhiTongBao->setContainer($this->container);
        $zhiTongBao->setClient($this->client);
        $zhiTongBao->setOptions($sourceData);
        $zhiTongBao->getVerifyData();
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
            '<sign>POWEIISAGOODPEOPLEVERYNICE</sign>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'notify_url' => 'http://yes9527.com.tw',
            'orderId' => '2017091211111',
            'orderCreateDate' => '2017-09-12 09:30:11',
            'amount' => '0.01',
            'username' => 'po-wei',
            'paymentVendorId' => '1092',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.api.ZhiTongBao.com',
            'ip' => '111.235.135.54',
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setResponse($response);
        $zhiTongBao->setContainer($this->container);
        $zhiTongBao->setClient($this->client);
        $zhiTongBao->setOptions($sourceData);
        $zhiTongBao->getVerifyData();
    }

    /**
     * 測試二維支付加密未返回qrcode
     */
    public function testQRcodePayGetEncodeNoReturnQrcode()
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
            '<sign>POWEIISAGOODPEOPLEVERYNICE</sign>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'notify_url' => 'http://yes9527.com.tw',
            'orderId' => '2017091211111',
            'orderCreateDate' => '2017-09-12 09:30:11',
            'amount' => '0.01',
            'username' => 'po-wei',
            'paymentVendorId' => '1092',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.api.ZhiTongBao.com',
            'ip' => '111.235.135.54',
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setResponse($response);
        $zhiTongBao->setContainer($this->container);
        $zhiTongBao->setClient($this->client);
        $zhiTongBao->setOptions($sourceData);
        $zhiTongBao->getVerifyData();
    }

    /**
     * 測試微信手機支付加密返回payURL缺少scheme
     */
    public function testWxPhonePayGetEncodeReturnPayURLWithoutScheme()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>100100011172</merchant_code>' .
            '<order_no>201710260000005304</order_no>' .
            '<order_time>2017-10-26 10:14:43</order_time>' .
            '<payURL>api.ulopay.com/pay/location</payURL>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>0</result_code>' .
            '<sign>KJaAnBCvLn2u39F5rba59BacQWO4WH8VEO37DFYuc/pI2tqA=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '<trade_no>1014063571</trade_no>' .
            '<trade_time>2017-10-26 10:14:44</trade_time>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'notify_url' => 'http://yes9527.com.tw',
            'orderId' => '2017091211111',
            'orderCreateDate' => '2017-09-12 09:30:11',
            'amount' => '0.01',
            'username' => 'po-wei',
            'paymentVendorId' => '1097',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.api.ZhiTongBao.com',
            'ip' => '111.235.135.54',
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setResponse($response);
        $zhiTongBao->setContainer($this->container);
        $zhiTongBao->setClient($this->client);
        $zhiTongBao->setOptions($sourceData);
        $zhiTongBao->getVerifyData();
    }

    /**
     * 測試微信手機支付
     */
    public function testWxPhonePay()
    {
        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>100100011172</merchant_code>' .
            '<order_no>201710260000005304</order_no>' .
            '<order_time>2017-10-26 10:14:43</order_time>' .
            '<payURL>https://api.ulopay.com/pay/location?url=aHR0cDovL3FxLmx1ZHN0dWRQ1YTVlZTk5</payURL>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>0</result_code>' .
            '<sign>KJaAnBCvLn2u39F5rba59BacQWO4WH8VEO37DFYuc/pI2tqA=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '<trade_no>1014063571</trade_no>' .
            '<trade_time>2017-10-26 10:14:44</trade_time>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'notify_url' => 'http://yes9527.com.tw',
            'orderId' => '2017091211111',
            'orderCreateDate' => '2017-09-12 09:30:11',
            'amount' => '0.01',
            'username' => 'po-wei',
            'paymentVendorId' => '1097',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.api.ZhiTongBao.com',
            'ip' => '111.235.135.54',
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setResponse($response);
        $zhiTongBao->setContainer($this->container);
        $zhiTongBao->setClient($this->client);
        $zhiTongBao->setOptions($sourceData);
        $data = $zhiTongBao->getVerifyData();

        $this->assertEquals('https://api.ulopay.com/pay/location', $data['post_url']);
        $this->assertEquals('aHR0cDovL3FxLmx1ZHN0dWRQ1YTVlZTk5', $data['params']['url']);
        $this->assertEquals('GET', $zhiTongBao->getPayMethod());
    }

    /**
     * 測試支付寶手機支付
     */
    public function testAliPayPhonePay()
    {
        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>100100011172</merchant_code>' .
            '<order_no>201710260000005304</order_no>' .
            '<order_time>2017-10-26 10:14:43</order_time>' .
            '<payURL>https://api.ulopay.com:3310/pay/location/ABSDFSDFSDF</payURL>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>0</result_code>' .
            '<sign>KJaAnBCvLn2u39F5rba59BacQWO4WH8VEO37DFYuc/pI2tqA=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '<trade_no>1014063571</trade_no>' .
            '<trade_time>2017-10-26 10:14:44</trade_time>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'notify_url' => 'http://yes9527.com.tw',
            'orderId' => '2017091211111',
            'orderCreateDate' => '2017-09-12 09:30:11',
            'amount' => '0.01',
            'username' => 'po-wei',
            'paymentVendorId' => '1097',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.api.ZhiTongBao.com',
            'ip' => '111.235.135.54',
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setResponse($response);
        $zhiTongBao->setContainer($this->container);
        $zhiTongBao->setClient($this->client);
        $zhiTongBao->setOptions($sourceData);
        $data = $zhiTongBao->getVerifyData();

        $this->assertEquals('https://api.ulopay.com:3310/pay/location/ABSDFSDFSDF', $data['post_url']);
        $this->assertEmpty($data['params']);
        $this->assertEquals('GET', $zhiTongBao->getPayMethod());
    }

    /**
     * 測試QQ手機支付
     */
    public function testQQPhonePay()
    {
        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<dinpay><response>' .
            '<interface_version>V3.1</interface_version>' .
            '<merchant_code>100100011172</merchant_code>' .
            '<order_no>201710260000005304</order_no>' .
            '<order_time>2017-10-26 10:14:43</order_time>' .
            '<payURL>https://qpay.qq.com/qr/6e6e82ef</payURL>' .
            '<resp_code>SUCCESS</resp_code>' .
            '<resp_desc>通讯成功</resp_desc>' .
            '<result_code>0</result_code>' .
            '<sign>KJaAnBCvLn2u39F5rba59BacQWO4WH8VEO37DFYuc/pI2tqA=</sign>' .
            '<sign_type>RSA-S</sign_type>' .
            '<trade_no>1014063571</trade_no>' .
            '<trade_time>2017-10-26 10:14:44</trade_time>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'notify_url' => 'http://yes9527.com.tw',
            'orderId' => '2017091211111',
            'orderCreateDate' => '2017-09-12 09:30:11',
            'amount' => '0.01',
            'username' => 'po-wei',
            'paymentVendorId' => '1104',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.api.ZhiTongBao.com',
            'ip' => '111.235.135.54',
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setResponse($response);
        $zhiTongBao->setContainer($this->container);
        $zhiTongBao->setClient($this->client);
        $zhiTongBao->setOptions($sourceData);
        $data = $zhiTongBao->getVerifyData();

        $this->assertEquals('https://qpay.qq.com/qr/6e6e82ef', $data['post_url']);
        $this->assertEmpty($data['params']);
        $this->assertEquals('GET', $zhiTongBao->getPayMethod());
    }

    /**
     * 測試銀聯H5手機支付
     */
    public function testUnionPayH5PhonePay()
    {
        $encodeStr = 'client_ip=111.235.135.54&input_charset=UTF-8&interface_version=V3.0' .
            '&merchant_code=100100001020&notify_url=http://yes9527.com.tw&order_amount=1.00&' .
            'order_no=2017091211111&order_time=2018-02-08 09:04:11&product_name=wade&service_type=h5_union_d';

        $sourceData = [
            'number' => '100100001020',
            'notify_url' => 'http://yes9527.com.tw',
            'orderId' => '2017091211111',
            'orderCreateDate' => '2018-02-08 09:04:11',
            'amount' => '1',
            'username' => 'wade',
            'paymentVendorId' => '1088',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'ztbaopay.com',
            'ip' => '111.235.135.54',
        ];

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setOptions($sourceData);
        $data = $zhiTongBao->getVerifyData();

        $this->assertEquals('https://pay.ztbaopay.com/gateway?input_charset=UTF-8', $data['post_url']);
        $this->assertEquals('100100001020', $data['params']['merchant_code']);
        $this->assertEquals('h5_union_d', $data['params']['service_type']);
        $this->assertEquals('http://yes9527.com.tw', $data['params']['notify_url']);
        $this->assertEquals('V3.0', $data['params']['interface_version']);
        $this->assertEquals('UTF-8', $data['params']['input_charset']);
        $this->assertEquals('111.235.135.54', $data['params']['client_ip']);
        $this->assertEquals('RSA-S', $data['params']['sign_type']);
        $this->assertEquals(base64_encode($sign), $data['params']['sign']);
        $this->assertEquals('2017091211111', $data['params']['order_no']);
        $this->assertEquals('2018-02-08 09:04:11', $data['params']['order_time']);
        $this->assertEquals('1.00', $data['params']['order_amount']);
        $this->assertEquals('wade', $data['params']['product_name']);
    }

    /**
     * 測試加密(二維)
     */
    public function testGetEncodeData()
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
            '<qrcode>weixin://wxpay/bizpayurl?pr=MtTpobS</qrcode>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'notify_url' => 'http://yes9527.com.tw',
            'orderId' => '2017091211111',
            'orderCreateDate' => '2017-09-12 09:30:11',
            'amount' => '0.01',
            'username' => 'po-wei',
            'paymentVendorId' => '1092',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.api.ZhiTongBao.com',
            'ip' => '111.235.135.54',
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setResponse($response);
        $zhiTongBao->setContainer($this->container);
        $zhiTongBao->setClient($this->client);
        $zhiTongBao->setOptions($sourceData);
        $encodeData = $zhiTongBao->getVerifyData();

        $this->assertEmpty($encodeData);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=MtTpobS', $zhiTongBao->getQrcode());
    }

    /**
     * 測試加密(網銀)
     */
    public function testPayWithOnlineBank()
    {
        $encodeStr = 'bank_code=ICBC&client_ip=111.235.135.54&input_charset=UTF-8&interface_version=V3.0' .
            '&merchant_code=9527&notify_url=http://yes9527.com.tw/return.php&order_amount=0.01&order_no=2017091211111' .
            '&order_time=2017-09-12 09:30:11&product_name=php1test&service_type=direct_pay';

        $sourceData = [
            'number' => '9527',
            'notify_url' => 'http://yes9527.com.tw/return.php',
            'orderId' => '2017091211111',
            'orderCreateDate' => '2017-09-12 09:30:11',
            'amount' => '0.01',
            'username' => 'php1test',
            'paymentVendorId' => '1',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'payment.https.api.ZhiTongBao.com',
            'ip' => '111.235.135.54',
        ];

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setOptions($sourceData);
        $requestData = $zhiTongBao->getVerifyData();

        $postUrl = 'https://pay.' . $sourceData['postUrl'] . '/gateway?input_charset=UTF-8';
        $this->assertEquals('ICBC', $requestData['params']['bank_code']);
        $this->assertEquals('V3.0', $requestData['params']['interface_version']);
        $this->assertEquals('direct_pay', $requestData['params']['service_type']);
        $this->assertEquals('9527', $requestData['params']['merchant_code']);
        $this->assertEquals('http://yes9527.com.tw/return.php', $requestData['params']['notify_url']);
        $this->assertEquals('UTF-8', $requestData['params']['input_charset']);
        $this->assertEquals('RSA-S', $requestData['params']['sign_type']);
        $this->assertEquals('2017091211111', $requestData['params']['order_no']);
        $this->assertEquals('2017-09-12 09:30:11', $requestData['params']['order_time']);
        $this->assertEquals('0.01', $requestData['params']['order_amount']);
        $this->assertEquals('php1test', $requestData['params']['product_name']);
        $this->assertEquals($postUrl, $requestData['post_url']);
        $this->assertEquals(base64_encode($sign), $requestData['params']['sign']);
        $this->assertEquals($sourceData['amount'], $requestData['params']['order_amount']);
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

        $zhiTongBao = new ZhiTongBao();

        $sourceData = [
            'pay_system' => '49745',
            'trade_no' => '1003450919',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '9527',
            'order_no' => '201704190000001913',
            'sign' => '080a1529519594d50db187f2a36b1649',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'HFG000005221224275',
            'order_time' => '2017-09-12 08:59:10',
            'notify_id' => '6449d835356847458ab8c21f3381be10',
            'trade_time' => '2017-09-12 09:12:10'
        ];

        $zhiTongBao->setOptions($sourceData);
        $zhiTongBao->verifyOrderPayment([]);
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
            'pay_system' => '49745',
            'trade_no' => '1003450919',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '9527',
            'order_no' => '2017091200001',
            'trade_status' => 'SUCCESS',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'HFG000005221224275',
            'order_time' => '2017-09-12 09:30:11',
            'notify_id' => '6449d835356847458ab8c21f3381be10',
            'trade_time' => '2017-09-12 09:31:31'
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setOptions($sourceData);
        $zhiTongBao->verifyOrderPayment([]);
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

        $options = [
            'trade_no' => '1136435210',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '2000600129',
            'order_no' => '201709120000002345',
            'trade_status' => 'UNPAY',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'HFG000007999858313',
            'order_time' => '2017-09-12 15:09:53',
            'notify_id' => 'aa1f5f6df8d24fafaa6c14faee93cd11',
            'trade_time' => '2017-09-12 16:09:53',
            'sign' => 'test',
            'rsa_public_key' => '',
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setOptions($options);
        $zhiTongBao->verifyOrderPayment([]);
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

        $options = [
            'trade_no' => '1136435210',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '2000600129',
            'order_no' => '201709120000002345',
            'trade_status' => 'UNPAY',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'HFG000007999858313',
            'order_time' => '2017-09-12 15:09:53',
            'notify_id' => 'aa1f5f6df8d24fafaa6c14faee93cd11',
            'trade_time' => '2017-09-12 16:09:53',
            'sign' => 'test',
            'rsa_public_key' => '123',
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setOptions($options);
        $zhiTongBao->verifyOrderPayment([]);
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
            'pay_system' => '49745',
            'trade_no' => '1003450919',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '9527',
            'order_no' => '2017091200001',
            'trade_status' => 'SUCCESS',
            'sign' => 'poweiisagoodguy',
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => 'HFG000005221224275',
            'order_time' => '2017-09-12 09:30:11',
            'notify_id' => '6449d835356847458ab8c21f3381be10',
            'trade_time' => '2017-09-12 09:31:31',
            'rsa_public_key' => $this->publicKey,
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setOptions($sourceData);
        $zhiTongBao->verifyOrderPayment([]);
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

        $encodeStr = 'bank_seq_no=7551102936201603310273064491&interface_version=V3.0&merchant_code=9527&' .
            'notify_id=5b9b67ecf6be4346ab738dddf9127c62&notify_type=offline_notify&order_amount=0.01&' .
            'order_no=201709120000004830&order_time=2017-09-12+16%3A35%3A06&trade_no=1125060594&trade_status=UNPAY&' .
            'trade_time=2017-09-12+16%3A14%3A02';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $sourceData = [
            'pay_system' => '49745',
            'trade_no' => '1125060594',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '9527',
            'order_no' => '201709120000004830',
            'trade_status' => 'UNPAY',
            'sign' => base64_encode($sign),
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => '7551102936201603310273064491',
            'order_time' => '2017-09-12 16:35:06',
            'notify_id' => '5b9b67ecf6be4346ab738dddf9127c62',
            'trade_time' => '2017-09-12 16:14:02',
            'rsa_public_key' => $this->publicKey,
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setOptions($sourceData);
        $zhiTongBao->verifyOrderPayment([]);
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

        $encodeStr = 'bank_seq_no=7551102936201603310273064491&interface_version=V3.0&merchant_code=1111110166&' .
            'notify_id=5b9b67ecf6be4346ab738dddf9127c62&notify_type=offline_notify&order_amount=0.01&' .
            'order_no=201709120000004830&order_time=2017-09-12+16%3A35%3A06&trade_no=1125060594&trade_status=SUCCESS&' .
            'trade_time=2017-09-12+16%3A14%3A02';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $sourceData = [
            'pay_system' => '49745',
            'trade_no' => '1125060594',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '1111110166',
            'order_no' => '201709120000004830',
            'trade_status' => 'SUCCESS',
            'sign' => base64_encode($sign),
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => '7551102936201603310273064491',
            'order_time' => '2017-09-12 16:35:06',
            'notify_id' => '5b9b67ecf6be4346ab738dddf9127c62',
            'trade_time' => '2017-09-12 16:14:02',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = ['id' => '2014052200123'];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setOptions($sourceData);
        $zhiTongBao->verifyOrderPayment($entry);
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

        $encodeStr = 'bank_seq_no=7551102936201603310273064491&interface_version=V3.0&merchant_code=1111110166&' .
            'notify_id=5b9b67ecf6be4346ab738dddf9127c62&notify_type=offline_notify&order_amount=0.01&' .
            'order_no=201709120000004830&order_time=2017-09-12+16%3A35%3A06&trade_no=1125060594&trade_status=SUCCESS&' .
            'trade_time=2017-09-12+16%3A14%3A02';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $sourceData = [
            'pay_system' => '49745',
            'trade_no' => '1125060594',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '1111110166',
            'order_no' => '201709120000004830',
            'trade_status' => 'SUCCESS',
            'sign' => base64_encode($sign),
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => '7551102936201603310273064491',
            'order_time' => '2017-09-12 16:35:06',
            'notify_id' => '5b9b67ecf6be4346ab738dddf9127c62',
            'trade_time' => '2017-09-12 16:14:02',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201709120000004830',
            'amount' => '1.0000'
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setOptions($sourceData);
        $zhiTongBao->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功(異步返回)
     */
    public function testPaySuccess()
    {
        $encodeStr = 'bank_seq_no=7551102936201603310273064491&interface_version=V3.0&merchant_code=1111110166&' .
            'notify_id=5b9b67ecf6be4346ab738dddf9127c62&notify_type=offline_notify&order_amount=0.01&' .
            'order_no=201709120000004830&order_time=2017-09-12+16%3A35%3A06&trade_no=1125060594&trade_status=SUCCESS&' .
            'trade_time=2017-09-12+16%3A14%3A02';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $sourceData = [
            'pay_system' => '49745',
            'trade_no' => '1125060594',
            'sign_type' => 'RSA-S',
            'notify_type' => 'offline_notify',
            'merchant_code' => '1111110166',
            'order_no' => '201709120000004830',
            'trade_status' => 'SUCCESS',
            'sign' => base64_encode($sign),
            'order_amount' => '0.01',
            'interface_version' => 'V3.0',
            'bank_seq_no' => '7551102936201603310273064491',
            'order_time' => '2017-09-12 16:35:06',
            'notify_id' => '5b9b67ecf6be4346ab738dddf9127c62',
            'trade_time' => '2017-09-12 16:14:02',
            'rsa_public_key' => $this->publicKey,
        ];

        $entry = [
            'id' => '201709120000004830',
            'amount' => '0.0100'
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setOptions($sourceData);
        $zhiTongBao->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $zhiTongBao->getMsg());
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

        $zhitongbao = new ZhiTongBao();
        $zhitongbao->paymentTracking();
    }

    /**
     * 測試訂單查詢沒代入verifyUrl
     */
    public function testPaymentTrackingWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => '9527',
            'orderId' => '2017091200001',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setOptions($sourceData);
        $zhiTongBao->paymentTracking();
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

        $privateKey = '';

        // Get private key
        openssl_pkey_export($res, $privateKey);

        $zhiTongBao = new ZhiTongBao();

        $sourceData = [
            'number' => '9527',
            'orderId' => '2017091200001',
            'rsa_private_key' => base64_encode($privateKey),
        ];

        $zhiTongBao->setOptions($sourceData);
        $zhiTongBao->paymentTracking();
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

        $sourceData = [
            'number' => '9527',
            'orderId' => '2017091200001',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ZhiTongBao.com'
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setContainer($this->container);
        $zhiTongBao->setClient($this->client);
        $zhiTongBao->setResponse($response);
        $zhiTongBao->setOptions($sourceData);
        $zhiTongBao->paymentTracking();
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

        $sourceData = [
            'number' => '9527',
            'orderId' => '2017091200001',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ZhiTongBao.com'
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setContainer($this->container);
        $zhiTongBao->setClient($this->client);
        $zhiTongBao->setResponse($response);
        $zhiTongBao->setOptions($sourceData);
        $zhiTongBao->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數trade
     */
    public function testPaymentTrackingResultWithoutTrade()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response><is_success>T</is_success></response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '2017091200001',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ZhiTongBao.com',
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setContainer($this->container);
        $zhiTongBao->setClient($this->client);
        $zhiTongBao->setResponse($response);
        $zhiTongBao->setOptions($sourceData);
        $zhiTongBao->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付查詢結果未指定返回參數
     */
    public function testTrackingReturnWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<trade><merchant_code>9527</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>2017091200001</order_no>' .
            '<order_time>2017-05-10 08:38:54</order_time>' .
            '<trade_time>2017-05-10 08:38:07</trade_time>' .
            '<trade_status>UNPAY</trade_status>' .
            '</trade></response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '2017091200001',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.zhitongbao.com'
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setContainer($this->container);
        $zhiTongBao->setClient($this->client);
        $zhiTongBao->setResponse($response);
        $zhiTongBao->setOptions($sourceData);
        $zhiTongBao->paymentTracking();
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
            '<trade><merchant_code>2000600129</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201709120000002345</order_no>' .
            '<order_time>2017-05-10 08:38:54</order_time>' .
            '<trade_no>S1001240244</trade_no>' .
            '<trade_status>UNPAY</trade_status>' .
            '<trade_time>2017-05-10 08:38:07</trade_time>' .
            '</trade></response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '2000600129',
            'orderId' => '201709120000002345',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.zhitongbao.com'
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setContainer($this->container);
        $zhiTongBao->setClient($this->client);
        $zhiTongBao->setResponse($response);
        $zhiTongBao->setOptions($sourceData);
        $zhiTongBao->paymentTracking();
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
            '<trade><merchant_code>2000600129</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201709120000002345</order_no>' .
            '<order_time>2017-05-10 08:38:54</order_time>' .
            '<trade_no>S1001240244</trade_no>' .
            '<trade_status>UNPAY</trade_status>' .
            '<trade_time>2017-05-10 08:38:07</trade_time>' .
            '</trade></response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '2000600129',
            'orderId' => '201709120000002345',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.zhitongbao.com'
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setContainer($this->container);
        $zhiTongBao->setClient($this->client);
        $zhiTongBao->setResponse($response);
        $zhiTongBao->setOptions($sourceData);
        $zhiTongBao->paymentTracking();
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

        $encodeStr = 'merchant_code=9527&order_amount=0.01&order_no=2017091200001' .
            '&order_time=2017-09-12 15:09:53&trade_no=1136435210&trade_status=UNPAY&trade_time=2017-09-12 16:09:53';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>9527</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>2017091200001</order_no>' .
            '<order_time>2017-09-12 15:09:53</order_time>' .
            '<trade_no>1136435210</trade_no>' .
            '<trade_status>UNPAY</trade_status>' .
            '<trade_time>2017-09-12 16:09:53</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '2017091200001',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.zhitongbao.com'
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setContainer($this->container);
        $zhiTongBao->setClient($this->client);
        $zhiTongBao->setResponse($response);
        $zhiTongBao->setOptions($sourceData);
        $zhiTongBao->paymentTracking();
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

        $encodeStr = 'merchant_code=9527&order_amount=0.01&order_no=2017091200001' .
            '&order_time=2017-09-12 15:09:53&trade_no=1136435210&trade_status=FAILED&trade_time=2017-09-12 16:09:53';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>9527</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>2017091200001</order_no>' .
            '<order_time>2017-09-12 15:09:53</order_time>' .
            '<trade_no>1136435210</trade_no>' .
            '<trade_status>FAILED</trade_status>' .
            '<trade_time>2017-09-12 16:09:53</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '2017091200001',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.zhitongbao.com'
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setContainer($this->container);
        $zhiTongBao->setClient($this->client);
        $zhiTongBao->setResponse($response);
        $zhiTongBao->setOptions($sourceData);
        $zhiTongBao->paymentTracking();
    }

    /**
     * 測試訂單查詢
     */
    public function testPaymentTracking()
    {
        $encodeStr = 'merchant_code=9527&order_amount=0.01&order_no=2017091200001' .
            '&order_time=2014-05-22 09:30:11&trade_no=1003450919&trade_status=SUCCESS&trade_time=2014-05-22 09:31:31';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign_type>RSA-S</sign_type>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>9527</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>2017091200001</order_no>' .
            '<order_time>2014-05-22 09:30:11</order_time>' .
            '<trade_no>1003450919</trade_no>' .
            '<trade_status>SUCCESS</trade_status>' .
            '<trade_time>2014-05-22 09:31:31</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $sourceData = [
            'number' => '9527',
            'orderId' => '2017091200001',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.zhitongbao.com',
            'amount' => '0.01'
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setContainer($this->container);
        $zhiTongBao->setClient($this->client);
        $zhiTongBao->setResponse($response);
        $zhiTongBao->setOptions($sourceData);
        $zhiTongBao->paymentTracking();
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

        $zhitongbao = new ZhiTongBao();
        $zhitongbao->getPaymentTrackingData();
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

        $sourceData = [
            'number' => '9527',
            'orderId' => '2017091200001',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setOptions($sourceData);
        $zhiTongBao->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $sourceData = [
            'number' => '9527',
            'orderId' => '2017091200001',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ZhiTongBao.com'
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setOptions($sourceData);
        $trackingData = $zhiTongBao->getPaymentTrackingData();

        $this->assertEquals($sourceData['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('/query', $trackingData['path']);
        $this->assertEquals($sourceData['verify_url'], $trackingData['headers']['Host']);
    }

    /**
     * 測試驗證訂單查詢是否成功時缺少回傳參
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

        $sourceData = [
            'number' => '9527',
            'orderId' => '2017091200001',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ZhiTongBao.com',
            'content' => $result
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setOptions($sourceData);
        $zhiTongBao->paymentTrackingVerify();
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

        $sourceData = [
            'number' => '9527',
            'orderId' => '2017091200001',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ZhiTongBao.com',
            'content' => $result
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setOptions($sourceData);
        $zhiTongBao->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢結果缺少回傳參數trade
     */
    public function testPaymentTrackingVerifyWithoutTrade()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response><is_success>T</is_success></response></dinpay>';

        $sourceData = [
            'number' => '2000600129',
            'orderId' => '201709120000002345',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ZhiTongBao.com',
            'content' => $result ,
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setOptions($sourceData);
        $zhiTongBao->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢對外支付查詢結果未指定返回參數
     */
    public function testPaymentTrackingVerifyWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<trade><merchant_code>9527</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>2017091200001</order_no>' .
            '<order_time>2017-05-10 08:38:54</order_time>' .
            '<trade_time>2017-05-10 08:38:07</trade_time>' .
            '<trade_status>UNPAY</trade_status>' .
            '</trade></response></dinpay>';

        $sourceData = [
            'number' => '9527',
            'orderId' => '2017091200001',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ZhiTongBao.com',
            'content' => $result
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setOptions($sourceData);
        $zhiTongBao->paymentTrackingVerify();
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
            '<trade><merchant_code>2000600129</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201709120000002345</order_no>' .
            '<order_time>2017-05-10 08:38:54</order_time>' .
            '<trade_no>S1001240244</trade_no>' .
            '<trade_status>UNPAY</trade_status>' .
            '<trade_time>2017-05-10 08:38:07</trade_time>' .
            '</trade></response></dinpay>';

        $sourceData = [
            'number' => '2000600129',
            'orderId' => '201709120000002345',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ZhiTongBao.com',
            'content' => $result
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setOptions($sourceData);
        $zhiTongBao->paymentTrackingVerify();
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
            '<trade><merchant_code>2000600129</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>201709120000002345</order_no>' .
            '<order_time>2017-05-10 08:38:54</order_time>' .
            '<trade_no>S1001240244</trade_no>' .
            '<trade_status>UNPAY</trade_status>' .
            '<trade_time>2017-05-10 08:38:07</trade_time>' .
            '</trade></response></dinpay>';

        $sourceData = [
            'number' => '2000600129',
            'orderId' => '201709120000002345',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ZhiTongBao.com',
            'content' => $result
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setOptions($sourceData);
        $zhiTongBao->paymentTrackingVerify();
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

        $encodeStr = 'merchant_code=9527&order_amount=0.01&order_no=2017091200001' .
            '&order_time=2017-09-12 15:09:53&trade_no=1136435210&trade_status=SUCCESS&trade_time=2017-09-12 16:09:53';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>9527</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>2017091200001</order_no>' .
            '<order_time>2017-09-12 15:09:53</order_time>' .
            '<trade_no>1136435210</trade_no>' .
            '<trade_status>SUCCESS</trade_status>' .
            '<trade_time>2017-09-12 16:09:53</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $sourceData = [
            'number' => '9527',
            'orderId' => '20170912000016',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ZhiTongBao.com',
            'content' => $result
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setOptions($sourceData);
        $zhiTongBao->paymentTrackingVerify();
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

        $encodeStr = 'merchant_code=9527&order_amount=0.01&order_no=2017091200001' .
            '&order_time=2017-09-12 15:09:53&trade_no=1136435210&trade_status=SUCCESS&trade_time=2017-09-12 16:09:53';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>9527</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>2017091200001</order_no>' .
            '<order_time>2017-09-12 15:09:53</order_time>' .
            '<trade_no>1136435210</trade_no>' .
            '<trade_status>SUCCESS</trade_status>' .
            '<trade_time>2017-09-12 16:09:53</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $sourceData = [
            'number' => '9527',
            'orderId' => '2017091200001',
            'amount' => '0.02',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ZhiTongBao.com',
            'content' => $result
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setOptions($sourceData);
        $zhiTongBao->paymentTrackingVerify();
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

        $encodeStr = 'merchant_code=9527&order_amount=0.01&order_no=2017091200001' .
            '&order_time=2017-09-12 15:09:53&trade_no=1136435210&trade_status=UNPAY&trade_time=2017-09-12 16:09:53';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>9527</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>2017091200001</order_no>' .
            '<order_time>2017-09-12 15:09:53</order_time>' .
            '<trade_no>1136435210</trade_no>' .
            '<trade_status>UNPAY</trade_status>' .
            '<trade_time>2017-09-12 16:09:53</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $sourceData = [
            'number' => '9527',
            'orderId' => '2017091200001',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ZhiTongBao.com',
            'content' => $result
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setOptions($sourceData);
        $zhiTongBao->paymentTrackingVerify();
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

        $encodeStr = 'merchant_code=9527&order_amount=0.01&order_no=2017091200001' .
            '&order_time=2017-09-12 15:09:53&trade_no=1136435210&trade_status=FAILED&trade_time=2017-09-12 16:09:53';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>9527</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>2017091200001</order_no>' .
            '<order_time>2017-09-12 15:09:53</order_time>' .
            '<trade_no>1136435210</trade_no>' .
            '<trade_status>FAILED</trade_status>' .
            '<trade_time>2017-09-12 16:09:53</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $sourceData = [
            'number' => '9527',
            'orderId' => '2017091200001',
            'amount' => '0.01',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ZhiTongBao.com',
            'content' => $result
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setOptions($sourceData);
        $zhiTongBao->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $encodeStr = 'merchant_code=9527&order_amount=0.01&order_no=2017091200001' .
            '&order_time=2014-05-22 09:30:11&trade_no=1003450919&trade_status=SUCCESS&trade_time=2014-05-22 09:31:31';

        $sign = '';
        openssl_sign(urldecode($encodeStr), $sign, base64_decode($this->privateKey), OPENSSL_ALGO_MD5);

        $result = '<?xml version="1.0" encoding="UTF-8" ?>' .
            '<dinpay><response>' .
            '<is_success>T</is_success>' .
            '<sign_type>RSA-S</sign_type>' .
            '<sign>' . base64_encode($sign) . '</sign>' .
            '<trade>' .
            '<merchant_code>9527</merchant_code>' .
            '<order_amount>0.01</order_amount>' .
            '<order_no>2017091200001</order_no>' .
            '<order_time>2014-05-22 09:30:11</order_time>' .
            '<trade_no>1003450919</trade_no>' .
            '<trade_status>SUCCESS</trade_status>' .
            '<trade_time>2014-05-22 09:31:31</trade_time>' .
            '</trade>' .
            '</response></dinpay>';

        $sourceData = [
            'number' => '9527',
            'orderId' => '2017091200001',
            'rsa_private_key' => $this->privateKey,
            'rsa_public_key' => $this->publicKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ZhiTongBao.com',
            'amount' => '0.01',
            'content' => $result
        ];

        $zhiTongBao = new ZhiTongBao();
        $zhiTongBao->setOptions($sourceData);
        $zhiTongBao->paymentTrackingVerify();
    }
}
