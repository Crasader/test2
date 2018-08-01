<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Ecpss;
use Buzz\Message\Response;

class EcpssTest extends DurianTestCase
{
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
     * 測試加密基本參數設定沒有帶入privateKey的情況
     */
    public function testSetEncodeSourceNoPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $ecpss = new Ecpss();
        $ecpss->getVerifyData();
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

        $ecpss = new Ecpss();
        $ecpss->setPrivateKey('AFBVCsGM');

        $sourceData = ['number' => ''];

        $ecpss->setOptions($sourceData);
        $ecpss->getVerifyData();
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

        $ecpss = new Ecpss();
        $ecpss->setPrivateKey('AFBVCsGM');

        $notifyUrl = 'http://ts-m.vir888.com/app/member/pay_online2/pay_result.php?';
        $notifyUrl .= 'pay_system=18842&hallid=6';

        $sourceData = [
            'number' => '19226',
            'orderId' => '201404240000001231',
            'amount' => '0.01',
            'notify_url' => $notifyUrl,
            'orderCreateDate' => '2014-04-24 18:18:59',
            'paymentVendorId' => '999',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $ecpss->setOptions($sourceData);
        $ecpss->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $sourceData = [
            'number' => '19226',
            'orderId' => '201404240000001231',
            'amount' => '0.01',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'orderCreateDate' => '2014-04-24 18:18:59',
            'paymentVendorId' => '1', //ICBC(工商銀行，返回結果要是這個值)
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $ecpss = new Ecpss();
        $ecpss->setPrivateKey('AFBVCsGM');
        $ecpss->setOptions($sourceData);
        $encodeData = $ecpss->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals($sourceData['number'], $encodeData['MerNo']);
        $this->assertEquals('20140424181859', $encodeData['orderTime']);
        $this->assertEquals($sourceData['orderId'], $encodeData['BillNo']);
        $this->assertSame('0.01', $encodeData['Amount']);
        $this->assertEquals($notifyUrl, $encodeData['ReturnURL']);
        $this->assertEquals($notifyUrl, $encodeData['AdviceURL']);
        $this->assertEquals('ICBC', $encodeData['defaultBankNumber']);
        $this->assertEquals('034F9D94F639BB6F46D4EAFD7E22ED80', $encodeData['SignInfo']);
    }

    /**
     * 測試解密基本參數設定沒有帶入key的情況
     */
    public function testSetDecodeSourceNoKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $ecpss = new Ecpss();

        $ecpss->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證未指定返回參數
     */
    public function testVerifyWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $ecpss = new Ecpss();
        $ecpss->setPrivateKey('AFBVCsGM');

        $sourceData = [
            'BillNo'      => '201404240000001231',
            'Amount'      => '0.01',
            'Result'      => 'Success',
            'MD5info'     => '9BFD9F94DFBD5702E941C3052B7CBF9F',
            'SignMD5info' => 'D41DFA926CB28DAEE576732C9C113D2F'
        ];

        $ecpss->setOptions($sourceData);
        $ecpss->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證缺少SignMD5info
     */
    public function testVerifyWithoutSignMD5info()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $ecpss = new Ecpss();
        $ecpss->setPrivateKey('AFBVCsGM');

        $sourceData = [
            'BillNo' => '201404240000001231',
            'Amount' => '0.01',
            'Succeed' => '88',
            'Result' => 'Success',
            'MD5info' => '9BFD9F94DFBD5702E941C3052B7CBF9F'
        ];

        $ecpss->setOptions($sourceData);
        $ecpss->verifyOrderPayment([]);
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

        $ecpss = new Ecpss();
        $ecpss->setPrivateKey('AFBVCsGM');

        $sourceData = [
            'BillNo'      => '201404240000001231',
            'Amount'      => '0.01',
            'Succeed'     => '88',
            'Result'      => 'Success',
            'MD5info'     => 'D41DFA926CB28DAEE576732C9C113D2F',
            'SignMD5info' => '9BFD9F94DFBD5702E941C3052B7CBF9F'
        ];

        $ecpss->setOptions($sourceData);
        $ecpss->verifyOrderPayment([]);
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

        $ecpss = new Ecpss();
        $ecpss->setPrivateKey('AFBVCsGM');

        $sourceData = [
            'BillNo'      => '201404240000001231',
            'Amount'      => '0.01',
            'Succeed'     => '00',
            'Result'      => 'Failed',
            'MD5info'     => '9BFD9F94DFBD5702E941C3052B7CBF9F',
            'SignMD5info' => 'DD98CD01962794CF0DAADCFFC9A45F4F'
        ];

        $ecpss->setOptions($sourceData);
        $ecpss->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $ecpss = new Ecpss();
        $ecpss->setPrivateKey('AFBVCsGM');

        $sourceData = [
            'BillNo'      => '201404240000001231',
            'Amount'      => '0.01',
            'Succeed'     => '88',
            'Result'      => 'Success',
            'MD5info'     => '9BFD9F94DFBD5702E941C3052B7CBF9F',
            'SignMD5info' => 'D41DFA926CB28DAEE576732C9C113D2F'
        ];

        $entry = ['id' => '20140103000123456'];

        $ecpss->setOptions($sourceData);
        $ecpss->verifyOrderPayment($entry);
    }

    /**
     * 測試金額比對錯誤的情況
     */
    public function testAmountFailure()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $ecpss = new Ecpss();
        $ecpss->setPrivateKey('AFBVCsGM');

        $sourceData = [
            'BillNo'      => '201404240000001231',
            'Amount'      => '0.01',
            'Succeed'     => '88',
            'Result'      => 'Success',
            'MD5info'     => '9BFD9F94DFBD5702E941C3052B7CBF9F',
            'SignMD5info' => 'D41DFA926CB28DAEE576732C9C113D2F'
        ];

        $entry = [
            'id' => '201404240000001231',
            'amount' => '1.0000'
        ];

        $ecpss->setOptions($sourceData);
        $ecpss->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $ecpss = new Ecpss();
        $ecpss->setPrivateKey('AFBVCsGM');

        $sourceData = [
            'BillNo'      => '201404240000001231',
            'Amount'      => '0.01',
            'Succeed'     => '88',
            'Result'      => 'Success',
            'MD5info'     => '9BFD9F94DFBD5702E941C3052B7CBF9F',
            'SignMD5info' => 'D41DFA926CB28DAEE576732C9C113D2F'
        ];

        $entry = [
            'id' => '201404240000001231',
            'amount' => '0.0100'
        ];

        $ecpss->setOptions($sourceData);
        $ecpss->verifyOrderPayment($entry);

        $this->assertEquals('ok', $ecpss->getMsg());
    }

    /**
     * 測試訂單查詢沒代入privateKey
     */
    public function testPaymentTrackingWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $ecpss = new Ecpss();
        $ecpss->paymentTracking();
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

        $ecpss = new Ecpss();
        $ecpss->setPrivateKey('AFBVCsGM');
        $ecpss->paymentTracking();
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
            'number' => '19226',
            'orderId' => '20140406000123456',
            'orderCreateDate' => '20140424181859',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $ecpss = new Ecpss();
        $ecpss->setPrivateKey('AFBVCsGM');
        $ecpss->setOptions($sourceData);
        $ecpss->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數resultCode
     */
    public function testPaymentTrackingResultWithoutResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<root></root>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '19226',
            'orderId' => '20140406000123456',
            'orderCreateDate' => '20140424181859',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ecpss.com'
        ];

        $ecpss = new Ecpss();
        $ecpss->setContainer($this->container);
        $ecpss->setClient($this->client);
        $ecpss->setResponse($response);
        $ecpss->setPrivateKey('AFBVCsGM');
        $ecpss->setOptions($sourceData);
        $ecpss->paymentTracking();
    }

    /**
     * 測試訂單查詢結果為支付平台IP未綁定
     */
    public function testTrackingReturnPaymentGatewayIPNoBind()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, IP have no binding',
            180125
        );

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<root><resultCode>11</resultCode></root>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '19226',
            'orderId' => '20140406000123456',
            'orderCreateDate' => '20140424181859',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ecpss.com'
        ];

        $ecpss = new Ecpss();
        $ecpss->setContainer($this->container);
        $ecpss->setClient($this->client);
        $ecpss->setResponse($response);
        $ecpss->setPrivateKey('AFBVCsGM');
        $ecpss->setOptions($sourceData);
        $ecpss->paymentTracking();
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

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<root><resultCode>22</resultCode></root>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '19226',
            'orderId' => '20140406000123456',
            'orderCreateDate' => '20140424181859',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ecpss.com'
        ];

        $ecpss = new Ecpss();
        $ecpss->setContainer($this->container);
        $ecpss->setClient($this->client);
        $ecpss->setResponse($response);
        $ecpss->setPrivateKey('AFBVCsGM');
        $ecpss->setOptions($sourceData);
        $ecpss->paymentTracking();
    }

    /**
     * 測試訂單查詢結果交易種類錯誤
     */
    public function testTrackingReturnTransactionKindError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Transaction kind error',
            180085
        );

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<root><resultCode>33</resultCode></root>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '19226',
            'orderId' => '20140406000123456',
            'orderCreateDate' => '20140424181859',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ecpss.com'
        ];

        $ecpss = new Ecpss();
        $ecpss->setContainer($this->container);
        $ecpss->setClient($this->client);
        $ecpss->setResponse($response);
        $ecpss->setPrivateKey('AFBVCsGM');
        $ecpss->setOptions($sourceData);
        $ecpss->paymentTracking();
    }

    /**
     * 測試訂單查詢結果回傳訂單不存在
     */
    public function testTrackingReturnOrderNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<root><resultCode>44</resultCode></root>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '19226',
            'orderId' => '20140406000123456',
            'orderCreateDate' => '20140424181859',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ecpss.com'
        ];

        $ecpss = new Ecpss();
        $ecpss->setContainer($this->container);
        $ecpss->setClient($this->client);
        $ecpss->setResponse($response);
        $ecpss->setPrivateKey('AFBVCsGM');
        $ecpss->setOptions($sourceData);
        $ecpss->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數orderStatus
     */
    public function testPaymentTrackingResultWithoutOrderStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<root><resultCode>00</resultCode></root>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '19226',
            'orderId' => '20140406000123456',
            'orderCreateDate' => '20140424181859',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ecpss.com'
        ];

        $ecpss = new Ecpss();
        $ecpss->setContainer($this->container);
        $ecpss->setClient($this->client);
        $ecpss->setResponse($response);
        $ecpss->setPrivateKey('AFBVCsGM');
        $ecpss->setOptions($sourceData);
        $ecpss->paymentTracking();
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

        $params = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<root>'.
            '<resultCode>00</resultCode>'.
            '<lists><list>'.
            '<orderStatus>0</orderStatus>'.
            '</list></lists>'.
            '</root>';
        $result = urlencode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '19226',
            'orderId' => '20140406000123456',
            'orderCreateDate' => '20140424181859',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ecpss.com'
        ];

        $ecpss = new Ecpss();
        $ecpss->setContainer($this->container);
        $ecpss->setClient($this->client);
        $ecpss->setResponse($response);
        $ecpss->setPrivateKey('AFBVCsGM');
        $ecpss->setOptions($sourceData);
        $ecpss->paymentTracking();
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

        $params = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<root>'.
            '<resultCode>00</resultCode>'.
            '<lists><list>'.
            '<orderStatus>1</orderStatus>'.
            '<orderNumber>201404230000001224</orderNumber>'.
            '<orderAmount>0.01</orderAmount>'.
            '</list></lists>'.
            '</root>';
        $result = urlencode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '19226',
            'orderId' => '201404230000001224',
            'orderCreateDate' => '20140424181859',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ecpss.com',
            'amount' => '1000.00'
        ];

        $ecpss = new Ecpss();
        $ecpss->setContainer($this->container);
        $ecpss->setClient($this->client);
        $ecpss->setResponse($response);
        $ecpss->setPrivateKey('AFBVCsGM');
        $ecpss->setOptions($sourceData);
        $ecpss->paymentTracking();
    }

    /**
     * 測試訂單查詢
     */
    public function testPaymentTracking()
    {
        $params = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<root>'.
            '<resultCode>00</resultCode>'.
            '<lists><list>'.
            '<orderStatus>1</orderStatus>'.
            '<orderNumber>201404230000001224</orderNumber>'.
            '<orderAmount>0.01</orderAmount>'.
            '</list></lists>'.
            '</root>';
        $result = urlencode($params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '19226',
            'orderId' => '201404230000001224',
            'orderCreateDate' => '20140424181859',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ecpss.com',
            'amount' => '0.01'
        ];

        $ecpss = new Ecpss();
        $ecpss->setContainer($this->container);
        $ecpss->setClient($this->client);
        $ecpss->setResponse($response);
        $ecpss->setPrivateKey('AFBVCsGM');
        $ecpss->setOptions($sourceData);
        $ecpss->paymentTracking();
    }
}
