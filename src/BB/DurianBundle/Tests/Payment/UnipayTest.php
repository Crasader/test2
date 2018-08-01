<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Unipay;
use Buzz\Message\Response;

class UnipayTest extends DurianTestCase
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
     * @var resource 私鑰
     */
    private $privateKey;

    /**
     * @var resource 公鑰
     */
    private $publicKey;

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
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $this->container->expects($this->any())
            ->method('get')
            ->willReturn($mockLogger);

        $this->client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
    }

    /**
     * 測試加密基本參數設定未指定返回參數
     */
    public function testSetEncodeSourceNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $unipay = new Unipay();

        $sourceData = ['number' => ''];

        $unipay->setOptions($sourceData);
        $unipay->getVerifyData();
    }

    /**
     * 測試加密基本參數設定帶入不支援的銀行
     */
    public function testSetEncodeSourceNotSupportBank()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $unipay = new Unipay();

        $sourceData = [
            'number' => 'CAL',
            'orderId' => '201404210015169731',
            'amount' => '100',
            'paymentVendorId' => '999',
            'notify_url' => 'http://ts-m.vir888.com/',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $unipay->setOptions($sourceData);
        $unipay->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $this->createKey();

        $sourceData = [
            'number' => 'CAL',
            'orderId' => '201404210015169731',
            'amount' => '100',
            'paymentVendorId' => '1', // gonghang(工商銀行，返回結果要是這個值)
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'merchantId' => '12345',
            'domain' => '6',
            'rsa_private_key' => base64_encode($this->privateKey),
            'rsa_public_key' => base64_encode($this->publicKey),
        ];

        $unipay = new Unipay();
        $unipay->setOptions($sourceData);
        $encodeData = $unipay->getVerifyData();

        $notifyUrl = sprintf(
            '%s?out_trade_no=%s',
            $sourceData['notify_url'],
            $sourceData['orderId']
        );

        $decrypted = $this->decrypt(base64_decode($encodeData['info']));
        parse_str($decrypted, $info);

        $this->assertEquals($sourceData['number'], $encodeData['partner']);
        $this->assertEquals($sourceData['orderId'], $info['order_number']);

        $this->assertEquals(sprintf('%.2f', $sourceData['amount']), $info['total_fee']);
        $this->assertEquals('gonghang', $info['pay_id']);
        $this->assertEquals($notifyUrl, $info['return_url']);
        $this->assertEquals($notifyUrl, $info['notify_url']);
        $this->assertEquals('01', $info['card_type']);
        $this->assertEquals('', $info['version']);
        $this->assertEquals('', $info['base64_memo']);
    }

    /**
     * 測試解密驗證未指定返回參數 info
     */
    public function testVerifyWithoutInfo()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $unipay = new Unipay();

        $unipay->setOptions([]);
        $unipay->verifyOrderPayment([]);
    }

    /**
     * 測試返回解密後缺少參數
     */
    public function testReturnWithoutParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $this->createKey();

        $unipay = new Unipay();

        $unipay->setOptions([
            'info' => base64_encode($this->encrypt([])),
            'rsa_private_key' => base64_encode($this->privateKey),
            'rsa_public_key' => base64_encode($this->publicKey),
        ]);
        $unipay->verifyOrderPayment([]);
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

        $this->createKey();

        $unipay = new Unipay();

        $sourceData = [
            'partner' => 'CAL',
            'out_trade_no' => '201404210015169731',
            'pay_no' => '1877187',
            'amount' => '100.0',
            'mdr_fee' => '0.0',
            'pay_result' => '0',
            'sett_date' => '',
        ];

        $info = base64_encode($this->encrypt($sourceData));

        $unipay->setOptions([
            'info' => $info,
            'rsa_private_key' => base64_encode($this->privateKey),
            'rsa_public_key' => base64_encode($this->publicKey),
        ]);
        $unipay->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $this->createKey();

        $unipay = new Unipay();

        $sourceData = [
            'partner' => 'CAL',
            'out_trade_no' => '201404210015169731',
            'pay_no' => '1877187',
            'amount' => '100.0',
            'mdr_fee' => '0.0',
            'pay_result' => '1',
            'sett_date' => '',
        ];

        $entry = ['id' => '201402049487426'];

        $info = base64_encode($this->encrypt($sourceData));

        $unipay->setOptions([
            'info' => $info,
            'rsa_private_key' => base64_encode($this->privateKey),
            'rsa_public_key' => base64_encode($this->publicKey),
        ]);
        $unipay->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $this->createKey();

        $unipay = new Unipay();

        $sourceData = [
            'partner' => 'CAL',
            'out_trade_no' => '201404210015169731',
            'pay_no' => '1877187',
            'amount' => '100.0',
            'mdr_fee' => '0.0',
            'pay_result' => '1',
            'sett_date' => '',
        ];

        $entry = [
            'id' => '201404210015169731',
            'amount' => '9487',
        ];

        $info = base64_encode($this->encrypt($sourceData));

        $unipay->setOptions([
            'info' => $info,
            'rsa_private_key' => base64_encode($this->privateKey),
            'rsa_public_key' => base64_encode($this->publicKey),
        ]);
        $unipay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $this->createKey();

        $unipay = new Unipay();

        $sourceData = [
            'partner' => 'CAL',
            'out_trade_no' => '201404210015169731',
            'pay_no' => '1877187',
            'amount' => '100.0',
            'mdr_fee' => '0.0',
            'pay_result' => '1',
            'sett_date' => '',
        ];

        $entry = [
            'id' => '201404210015169731',
            'amount' => '100.0',
        ];

        $info = base64_encode($this->encrypt($sourceData));

        $unipay->setOptions([
            'info' => $info,
            'rsa_private_key' => base64_encode($this->privateKey),
            'rsa_public_key' => base64_encode($this->publicKey),
        ]);
        $unipay->verifyOrderPayment($entry);

        $this->assertEquals('success', $unipay->getMsg());
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

        $unipay = new Unipay();
        $unipay->paymentTracking();
    }

    /**
     * 測試訂單查詢沒代入 reopUrl
     */
    public function testPaymentTrackingWithoutReopUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No reopUrl specified',
            180141
        );

        $this->createKey();

        $sourceData = [
            'number' => 'CAL',
            'orderId' => '201404150014262828',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'reopUrl' => '',
            'rsa_private_key' => base64_encode($this->privateKey),
            'rsa_public_key' => base64_encode($this->publicKey),
        ];

        $unipay = new Unipay();
        $unipay->setOptions($sourceData);
        $unipay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數
     */
    public function testPaymentTrackingResultWithoutParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '<root></root>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $this->createKey();

        $sourceData = [
            'number' => 'CAL',
            'orderId' => '201404150014262828',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.unipay.com',
            'reopUrl' => 'https://www.unipaygo.com/index.php/payin/proxy',
            'rsa_private_key' => base64_encode($this->privateKey),
            'rsa_public_key' => base64_encode($this->publicKey),
        ];

        $unipay = new Unipay();
        $unipay->setContainer($this->container);
        $unipay->setClient($this->client);
        $unipay->setResponse($response);
        $unipay->setOptions($sourceData);
        $unipay->paymentTracking();
    }

    /**
     * 測試訂單查詢失敗
     */
    public function testTrackingFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'NO Trade number',
            180123
        );

        $result = '<root>' .
            '<resp_code>81</resp_code>' .
            '<resp_desc>NO Trade number</resp_desc>' .
            '<partner>cal</partner>' .
            '<info></info>' .
            '</root>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $this->createKey();

        $sourceData = [
            'number' => 'CAL',
            'orderId' => '201404150014262828',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.unipay.com',
            'reopUrl' => 'https://www.unipaygo.com/index.php/payin/proxy',
            'rsa_private_key' => base64_encode($this->privateKey),
            'rsa_public_key' => base64_encode($this->publicKey),
        ];

        $unipay = new Unipay();
        $unipay->setContainer($this->container);
        $unipay->setClient($this->client);
        $unipay->setResponse($response);
        $unipay->setOptions($sourceData);
        $unipay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果未指定返回參數
     */
    public function testPaymentTrackingResultWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $this->createKey();

        $info = base64_encode($this->encrypt([]));

        $result = '<root>' .
            '<resp_code>00</resp_code>' .
            '<resp_desc>Success</resp_desc>' .
            '<partner>cal</partner>' .
            "<info>$info</info>" .
            '</root>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'CAL',
            'orderId' => '201404150014262828',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.unipay.com',
            'reopUrl' => 'https://www.unipaygo.com/index.php/payin/proxy',
            'rsa_private_key' => base64_encode($this->privateKey),
            'rsa_public_key' => base64_encode($this->publicKey),
        ];

        $unipay = new Unipay();
        $unipay->setContainer($this->container);
        $unipay->setClient($this->client);
        $unipay->setResponse($response);
        $unipay->setOptions($sourceData);
        $unipay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回尚未成功支付
     */
    public function testTrackingReturnUnpaid()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $this->createKey();

        $info = base64_encode($this->encrypt([
            'out_trade_no' => '201404150014262828',
            'amount' => '123',
            'curr_code' => 'CNY',
            'pay_result' => '0',
            'sett_date' => '20170505',
        ]));

        $result = '<root>' .
            '<resp_code>00</resp_code>' .
            '<resp_desc>Success</resp_desc>' .
            '<partner>cal</partner>' .
            "<info>$info</info>" .
            '</root>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'CAL',
            'orderId' => '201404150014262828',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.unipay.com',
            'reopUrl' => 'https://www.unipaygo.com/index.php/payin/proxy',
            'rsa_private_key' => base64_encode($this->privateKey),
            'rsa_public_key' => base64_encode($this->publicKey),
        ];

        $unipay = new Unipay();
        $unipay->setContainer($this->container);
        $unipay->setClient($this->client);
        $unipay->setResponse($response);
        $unipay->setOptions($sourceData);
        $unipay->paymentTracking();
    }

    /**
     * 測試訂單查詢單號不正確
     */
    public function testPaymentTrackingWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $this->createKey();

        $info = base64_encode($this->encrypt([
            'out_trade_no' => '2012378192378',
            'amount' => '123',
            'curr_code' => 'CNY',
            'pay_result' => '1',
            'sett_date' => '20170505',
        ]));

        $result = '<root>' .
            '<resp_code>00</resp_code>' .
            '<resp_desc>Success</resp_desc>' .
            '<partner>cal</partner>' .
            "<info>$info</info>" .
            '</root>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'CAL',
            'orderId' => '201404150014262828',
            'amount' => '123',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.unipay.com',
            'reopUrl' => 'https://www.unipaygo.com/index.php/payin/proxy',
            'rsa_private_key' => base64_encode($this->privateKey),
            'rsa_public_key' => base64_encode($this->publicKey),
        ];

        $unipay = new Unipay();
        $unipay->setContainer($this->container);
        $unipay->setClient($this->client);
        $unipay->setResponse($response);
        $unipay->setOptions($sourceData);
        $unipay->paymentTracking();
    }

    /**
     * 測試訂單查詢帶入金額不正確
     */
    public function testPaymentTrackingWithAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $this->createKey();

        $info = base64_encode($this->encrypt([
            'out_trade_no' => '201404150014262828',
            'amount' => '123',
            'curr_code' => 'CNY',
            'pay_result' => '1',
            'sett_date' => '20170505',
        ]));

        $result = '<root>' .
            '<resp_code>00</resp_code>' .
            '<resp_desc>Success</resp_desc>' .
            '<partner>cal</partner>' .
            "<info>$info</info>" .
            '</root>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'CAL',
            'orderId' => '201404150014262828',
            'amount' => '1234',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.unipay.com',
            'reopUrl' => 'https://www.unipaygo.com/index.php/payin/proxy',
            'rsa_private_key' => base64_encode($this->privateKey),
            'rsa_public_key' => base64_encode($this->publicKey),
        ];

        $unipay = new Unipay();
        $unipay->setContainer($this->container);
        $unipay->setClient($this->client);
        $unipay->setResponse($response);
        $unipay->setOptions($sourceData);
        $unipay->paymentTracking();
    }

    /**
     * 測試訂單查詢
     */
    public function testPaymentTracking()
    {
        $this->createKey();

        $info = base64_encode($this->encrypt([
            'out_trade_no' => '201404150014262828',
            'amount' => '1234',
            'curr_code' => 'CNY',
            'pay_result' => '1',
            'sett_date' => '20170505',
        ]));

        $result = '<root>' .
            '<resp_code>00</resp_code>' .
            '<resp_desc>Success</resp_desc>' .
            '<partner>cal</partner>' .
            "<info>$info</info>" .
            '</root>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'CAL',
            'orderId' => '201404150014262828',
            'amount' => '1234',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.unipay.com',
            'reopUrl' => 'https://www.unipaygo.com/index.php/payin/proxy',
            'rsa_private_key' => base64_encode($this->privateKey),
            'rsa_public_key' => base64_encode($this->publicKey),
        ];

        $unipay = new Unipay();
        $unipay->setContainer($this->container);
        $unipay->setClient($this->client);
        $unipay->setResponse($response);
        $unipay->setOptions($sourceData);
        $unipay->paymentTracking();
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

        $unipay = new Unipay();
        $unipay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒代入 reopUrl
     */
    public function testGetPaymentTrackingDataWithoutReopUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No reopUrl specified',
            180141
        );

        $this->createKey();

        $options = [
            'number' => 'CAL',
            'orderId' => '201404150014262828',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'reopUrl' => '',
            'rsa_private_key' => base64_encode($this->privateKey),
            'rsa_public_key' => base64_encode($this->publicKey),
        ];

        $unipay = new Unipay();
        $unipay->setOptions($options);
        $unipay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $this->createKey();

        $options = [
            'number' => 'CAL',
            'orderId' => '201404150014262828',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.www.unipaygo.com',
            'reopUrl' => 'https://www.unipaygo.com/index.php/payin/proxy',
            'rsa_private_key' => base64_encode($this->privateKey),
            'rsa_public_key' => base64_encode($this->publicKey),
        ];

        $this->container->expects($this->any())->method('getParameter')->willReturn('172.26.54.42');

        $unipay = new Unipay();
        $unipay->setContainer($this->container);
        $unipay->setOptions($options);
        $trackingData = $unipay->getPaymentTrackingData();

        $this->assertEquals(['172.26.54.42'], $trackingData['verify_ip']);
        $this->assertEquals('GET', $trackingData['method']);
        $this->assertEquals('172.26.54.42', $trackingData['headers']['Host']);

        $queries = parse_url($trackingData['path'], PHP_URL_QUERY);
        parse_str($queries, $queries);
        parse_str($queries['data'], $data);

        $this->assertEquals('CAL', $data['partner']);
        $this->assertEquals('out_trade_no=' . $options['orderId'], $this->decrypt(base64_decode($data['info'])));
    }

    /**
     * 加密訂單內容
     *
     * 以 214 為單位切割字串，並依照 RSA OAEP 作加密
     *
     * @param array $data 待加密內容
     * @return string 加密後的內容
     */
    private function encrypt(array $data)
    {
        $encrypted = '';

        foreach (str_split(http_build_query($data), 214) as $chunk) {
            $sign = '';
            openssl_public_encrypt($chunk, $sign, $this->publicKey, OPENSSL_PKCS1_OAEP_PADDING);

            $encrypted .= $sign;
        }

        return $encrypted;
    }

    /**
     * 解密訂單內容
     *
     * 以 256 為單位切割字串，並依照 RSA OAEP 作解密
     *
     * @param string $encrypted 已加密的內容
     * @return string 解密後的內容
     */
    private function decrypt(string $encrypted)
    {
        $decrypted = '';

        foreach (str_split($encrypted, 256) as $chunk) {
            $raw = '';
            openssl_private_decrypt($chunk, $raw, $this->privateKey, OPENSSL_PKCS1_OAEP_PADDING);

            $decrypted .= $raw;
        }

        return $decrypted;
    }

    /**
     * 產生公私鑰
     *
     * @return
     */
    private function createKey()
    {
        if (!empty($this->privateKey) && !empty($this->publicKey)) {
            return;
        }

        // 因中國銀聯是以 256 長度切割字串，鑰匙長度須為 2048
        $resource = openssl_pkey_new(['private_key_bits' => 2048]);

        $privateKey = '';
        openssl_pkey_export($resource, $privateKey);
        $publicKey = openssl_pkey_get_details($resource)['key'];

        $this->privateKey = $privateKey;
        $this->publicKey = $publicKey;
    }
}
