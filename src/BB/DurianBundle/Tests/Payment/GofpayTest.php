<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Gofpay;
use Buzz\Message\Response;

class GofpayTest extends DurianTestCase
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
            ->will($this->returnValue(null));

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

        $gofpay = new Gofpay();
        $gofpay->getVerifyData();
    }

    /**
     * 測試加密基本參數設定未指定支付參數
     */
    public function testSetEncodeSourceNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $gofpay = new Gofpay();
        $gofpay->setPrivateKey('5bcb841b696618a89a8b9a1faab9f102');

        $sourceData = ['number' => ''];

        $gofpay->setOptions($sourceData);
        $gofpay->getVerifyData();
    }

    /**
     * 測試加密基本參數設定沒有帶入verifyUrl的情況
     */
    public function testSetEncodeSourceNoVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $gofpay = new Gofpay();
        $gofpay->setPrivateKey('5bcb841b696618a89a8b9a1faab9f102');

        $sourceData = [
            'number' => '411419157065431',
            'orderId' => '201403200000000123',
            'amount' => '1234.56',
            'notify_url' => 'http://pay.1199-eb.net/pay/RequestReturn.php',
            'orderCreateDate' => '2014-04-05 00:06:15',
            'ip' => '111.235.135.3',
            'username' => 'acctest',
            'paymentVendorId' => '1',
            'verify_url' => '',
            'merchantId' => '50123',
            'domain' => '6',
        ];

        $gofpay->setOptions($sourceData);
        $gofpay->getVerifyData();
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

        $gofpay = new Gofpay();
        $gofpay->setPrivateKey('5bcb841b696618a89a8b9a1faab9f102');

        $sourceData = [
            'number' => '411419157065431',
            'orderId' => '201403200000000123',
            'amount' => '1234.56',
            'notify_url' => 'http://pay.1199-eb.net/pay/RequestReturn.php',
            'orderCreateDate' => '2014-04-05 00:06:15',
            'ip' => '111.235.135.3',
            'username' => 'acctest',
            'paymentVendorId' => '999',
            'verify_url' => 'www.gopay.com.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchantId' => '50123',
            'domain' => '6',
        ];

        $gofpay->setOptions($sourceData);
        $gofpay->getVerifyData();
    }

    /**
     * 測試加密參數設定成功
     */
    public function testSetEncodeSuccess()
    {
        $respone = new Response();
        $respone->setContent(20111202115229);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader('Content-Type:application/json;charset=UTF-8');

        $gofpay = new Gofpay();
        $gofpay->setContainer($this->container);
        $gofpay->setClient($this->client);
        $gofpay->setResponse($respone);
        $gofpay->setPrivateKey('5bcb841b696618a89a8b9a1faab9f102');

        $sourceData = [
            'number' => '411419157065431',
            'orderId' => '201403200000000123',
            'amount' => '1234.56',
            'notify_url' => 'http://pay.1199-eb.net/pay/RequestReturn.php',
            'orderCreateDate' => '2014-04-05 00:06:15',
            'ip' => '111.235.135.3',
            'username' => 'acctest',
            'paymentVendorId' => '1',
            'verify_url' => 'www.gopay.com.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => ['virCardNoIn' => '101001'],
            'merchantId' => '50123',
            'domain' => '6',
        ];

        $gofpay->setOptions($sourceData);
        $verifyData = $gofpay->getVerifyData();

        $this->assertEquals($sourceData['number'], $verifyData['merchantID']);
        $this->assertEquals($sourceData['orderId'], $verifyData['merOrderNum']);
        $this->assertSame('1234.56', $verifyData['tranAmt']);
        $this->assertEquals($sourceData['username'], $verifyData['goodsName']);
        $this->assertEquals('ICBC', $verifyData['bankCode']);
        $this->assertEquals('18a5c242e3c5d62a514b082eebb73865', $verifyData['signValue']);
    }

    /**
     * 測試加密參數設定,找不到商家的virCardNoIn附加設定值
     */
    public function testSetEncodeButNoVirCardNoInSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $mockLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Payment')
            ->disableOriginalConstructor()
            ->getMock();

        $container->expects($this->any())
            ->method('get')
            ->willReturn($mockLogger);

        $respone = new Response();
        $respone->setContent(20111202115229);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader('Content-Type:application/json;charset=UTF-8');

        $gofpay = new Gofpay();
        $gofpay->setContainer($container);
        $gofpay->setClient($this->client);
        $gofpay->setResponse($respone);
        $gofpay->setPrivateKey('5bcb841b696618a89a8b9a1faab9f102');

        $sourceData = [
            'number' => '411419157065431',
            'orderId' => '201403200000000123',
            'amount' => '1234.56',
            'notify_url' => 'http://pay.1199-eb.net/pay/RequestReturn.php',
            'orderCreateDate' => '2014-04-05 00:06:15',
            'ip' => '111.235.135.3',
            'username' => 'acctest',
            'paymentVendorId' => '1',
            'verify_url' => 'www.gopay.com.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => [],
            'merchantId' => '50123',
            'domain' => '6',
        ];

        $gofpay->setOptions($sourceData);
        $gofpay->getVerifyData();
    }

    /**
     * 測試加密時支付平台連線異常
     */
    public function testPayPaymentGatewayConnectionError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment Gateway connection failure',
            180088
        );

        $exception = new \Exception('Payment Gateway connection failure', 180088);
        $this->client->expects($this->any())
            ->method('send')
            ->willThrowException($exception);

        $gofpay = new Gofpay();
        $gofpay->setContainer($this->container);
        $gofpay->setClient($this->client);
        $gofpay->setPrivateKey('5bcb841b696618a89a8b9a1faab9f102');

        $sourceData = [
            'number' => '411419157065431',
            'orderId' => '201403200000000123',
            'amount' => '1234.56',
            'notify_url' => 'http://pay.1199-eb.net/pay/RequestReturn.php',
            'orderCreateDate' => '2014-04-05 00:06:15',
            'ip' => '111.235.135.3',
            'username' => 'acctest',
            'paymentVendorId' => '1',
            'verify_url' => 'www.gopay.com.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchantId' => '50123',
            'domain' => '6',
        ];

        $gofpay->setOptions($sourceData);
        $gofpay->getVerifyData();
    }

    /**
     * 測試支付時支付平台連線失敗
     */
    public function testPayReturnPaymentGatewayConnectionFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment Gateway connection failure',
            180088
        );

        $respone = new Response();
        $respone->setContent(20111202115229);
        $respone->addHeader('HTTP/1.1 499');
        $respone->addHeader('Content-Type:application/json;charset=UTF-8');

        $gofpay = new Gofpay();
        $gofpay->setContainer($this->container);
        $gofpay->setClient($this->client);
        $gofpay->setResponse($respone);
        $gofpay->setPrivateKey('5bcb841b696618a89a8b9a1faab9f102');

        $sourceData = [
            'number' => '411419157065431',
            'orderId' => '201403200000000123',
            'amount' => '1234.56',
            'notify_url' => 'http://pay.1199-eb.net/pay/RequestReturn.php',
            'orderCreateDate' => '2014-04-05 00:06:15',
            'ip' => '111.235.135.3',
            'username' => 'acctest',
            'paymentVendorId' => '1',
            'verify_url' => 'www.gopay.com.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchantId' => '50123',
            'domain' => '6',
        ];

        $gofpay->setOptions($sourceData);
        $gofpay->getVerifyData();
    }

    /**
     * 測試加密時支付平台回傳結果為空
     */
    public function testPayEmptyPaymentGatewayResponse()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Empty Payment Gateway response',
            180089
        );

        $respone = new Response();
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader('Content-Type:application/json;charset=UTF-8');

        $gofpay = new Gofpay();
        $gofpay->setContainer($this->container);
        $gofpay->setClient($this->client);
        $gofpay->setResponse($respone);
        $gofpay->setPrivateKey('5bcb841b696618a89a8b9a1faab9f102');

        $sourceData = [
            'number' => '411419157065431',
            'orderId' => '201403200000000123',
            'amount' => '1234.56',
            'notify_url' => 'http://pay.1199-eb.net/pay/RequestReturn.php',
            'orderCreateDate' => '2014-04-05 00:06:15',
            'ip' => '111.235.135.3',
            'username' => 'acctest',
            'paymentVendorId' => '1',
            'verify_url' => 'www.gopay.com.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchantId' => '50123',
            'domain' => '6',
        ];

        $gofpay->setOptions($sourceData);
        $gofpay->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $sourceData = [
            'number' => '411419157065431',
            'orderId' => '201403200000000123',
            'amount' => '1234.56',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'orderCreateDate' => '2014-04-05 00:06:15',
            'ip' => '111.235.135.3',
            'username' => 'acctest',
            'paymentVendorId' => '1',
            'verify_url' => 'www.gopay.com.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => ['virCardNoIn' => '101001'],
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $respone = new Response();
        $respone->setContent(20111202115229);
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->addHeader('Content-Type:application/json;charset=UTF-8');

        $gofpay = new Gofpay();
        $gofpay->setContainer($this->container);
        $gofpay->setClient($this->client);
        $gofpay->setResponse($respone);
        $gofpay->setPrivateKey('5bcb841b696618a89a8b9a1faab9f102');
        $gofpay->setOptions($sourceData);
        $verifyData = $gofpay->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals($sourceData['number'], $verifyData['merchantID']);
        $this->assertEquals($sourceData['orderId'], $verifyData['merOrderNum']);
        $this->assertSame('1234.56', $verifyData['tranAmt']);
        $this->assertSame($notifyUrl, $verifyData['backgroundMerUrl']);
        $this->assertEquals($sourceData['username'], $verifyData['goodsName']);
        $this->assertEquals('ICBC', $verifyData['bankCode']);
        $this->assertEquals('51df3c8da6734f349b428110793cdac7', $verifyData['signValue']);
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

        $gofpay = new Gofpay();

        $gofpay->verifyOrderPayment([]);
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

        $gofpay = new Gofpay();
        $gofpay->setPrivateKey('56453hjkjkh567fgsd');

        $sourceData = [
            'version'          => '2.0',
            'charset'          => '2',
            'language'         => '1',
            'signType'         => '1',
            'merchantID'       => '0000039205',
            'merOrderNum'      => '201405280020678196',
            'tranAmt'          => '120',
            'feeAmt'           => '0.48',
            'tiliuAmt'         => '',
            'frontMerUrl'      => '',
            'hallid'           => '264',
            'backgroundMerUrl' => 'http://pay.szzpx.com/pay/pay_response.php?pay_system=14903',
            'tranDateTime'     => '20140527121636',
            'tranIP'           => '110.85.34.59',
            'respCode'         => '9999',
            'msgExt'           => '',
            'orderId'          => '2014052839972673',
            'gopayOutOrderId'  => '2014052839972673',
            'bankCode'         => 'CCB',
            'tranFinishTime'   => '20140528001908',
            'goodsName'        => 'aaqins',
            'goodsDetail'      => '',
            'buyerName'        => 'aaqins',
            'buyerContact'     => '',
            'merRemark1'       => '',
            'merRemark2'       => '',
            'signValue'        => '98723d26b0c616c79a5f56a1f157c0c2'
        ];

        $gofpay->setOptions($sourceData);
        $gofpay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時沒有支付平台缺少回傳參數(測試signValue:加密簽名)
     */
    public function testVerifyWithoutSignValue()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $gofpay = new Gofpay();
        $gofpay->setPrivateKey('56453hjkjkh567fgsd');

        $sourceData = [
            'version'          => '2.0',
            'charset'          => '2',
            'language'         => '1',
            'signType'         => '1',
            'tranCode'         => '8888',
            'merchantID'       => '0000039205',
            'merOrderNum'      => '201405280020678196',
            'tranAmt'          => '120',
            'feeAmt'           => '0.48',
            'tiliuAmt'         => '',
            'frontMerUrl'      => '',
            'hallid'           => '264',
            'backgroundMerUrl' => 'http://pay.szzpx.com/pay/pay_response.php?pay_system=14903',
            'tranDateTime'     => '20140527121636',
            'tranIP'           => '110.85.34.59',
            'respCode'         => '9999',
            'msgExt'           => '',
            'orderId'          => '2014052839972673',
            'gopayOutOrderId'  => '2014052839972673',
            'bankCode'         => 'CCB',
            'tranFinishTime'   => '20140528001908',
            'goodsName'        => 'aaqins',
            'goodsDetail'      => '',
            'buyerName'        => 'aaqins',
            'buyerContact'     => '',
            'merRemark1'       => '',
            'merRemark2'       => ''
        ];

        $gofpay->setOptions($sourceData);
        $gofpay->verifyOrderPayment([]);
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

        $gofpay = new Gofpay();
        $gofpay->setPrivateKey('56453hjkjkh567fgsd');

        $sourceData = [
            'version'          => '2.0',
            'charset'          => '2',
            'language'         => '1',
            'signType'         => '1',
            'tranCode'         => '8888',
            'merchantID'       => '0000039205',
            'merOrderNum'      => '201405280020678000',
            'tranAmt'          => '120',
            'feeAmt'           => '0.48',
            'tiliuAmt'         => '',
            'frontMerUrl'      => '',
            'hallid'           => '264',
            'backgroundMerUrl' => 'http://pay.szzpx.com/pay/pay_response.php?pay_system=14903',
            'tranDateTime'     => '20140527121636',
            'tranIP'           => '110.85.34.59',
            'respCode'         => '9999',
            'msgExt'           => '',
            'orderId'          => '2014052839972673',
            'gopayOutOrderId'  => '2014052839972673',
            'bankCode'         => 'CCB',
            'tranFinishTime'   => '20140528001908',
            'goodsName'        => 'aaqins',
            'goodsDetail'      => '',
            'buyerName'        => 'aaqins',
            'buyerContact'     => '',
            'merRemark1'       => '',
            'merRemark2'       => '',
            'signValue'        => '98723d26b0c616c79a5f56a1f157c0c2'
        ];

        $gofpay->setOptions($sourceData);
        $gofpay->verifyOrderPayment([]);
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

        $gofpay = new Gofpay();
        $gofpay->setPrivateKey('56453hjkjkh567fgsd');

        $sourceData = [
            'version'          => '2.0',
            'charset'          => '2',
            'language'         => '1',
            'signType'         => '1',
            'tranCode'         => '8888',
            'merchantID'       => '0000039205',
            'merOrderNum'      => '201405280020678196',
            'tranAmt'          => '120',
            'feeAmt'           => '0.48',
            'tiliuAmt'         => '',
            'frontMerUrl'      => '',
            'hallid'           => '264',
            'backgroundMerUrl' => 'http://pay.szzpx.com/pay/pay_response.php?pay_system=14903',
            'tranDateTime'     => '20140527121636',
            'tranIP'           => '110.85.34.59',
            'respCode'         => '9999',
            'msgExt'           => '',
            'orderId'          => '2014052839972673',
            'gopayOutOrderId'  => '2014052839972673',
            'bankCode'         => 'CCB',
            'tranFinishTime'   => '20140528001908',
            'goodsName'        => 'aaqins',
            'goodsDetail'      => '',
            'buyerName'        => 'aaqins',
            'buyerContact'     => '',
            'merRemark1'       => '',
            'merRemark2'       => '',
            'signValue'        => '0c9399351307bc957297d4232da377e5'
        ];

        $gofpay->setOptions($sourceData);
        $gofpay->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $gofpay = new Gofpay();
        $gofpay->setPrivateKey('56453hjkjkh567fgsd');

        $sourceData = [
            'version'          => '2.0',
            'charset'          => '2',
            'language'         => '1',
            'signType'         => '1',
            'tranCode'         => '8888',
            'merchantID'       => '0000039205',
            'merOrderNum'      => '201405280020678196',
            'tranAmt'          => '120',
            'feeAmt'           => '0.48',
            'tiliuAmt'         => '',
            'frontMerUrl'      => '',
            'hallid'           => '264',
            'backgroundMerUrl' => 'http://pay.szzpx.com/pay/pay_response.php?pay_system=14903',
            'tranDateTime'     => '20140527121636',
            'tranIP'           => '110.85.34.59',
            'respCode'         => '0000',
            'msgExt'           => '',
            'orderId'          => '2014052839972673',
            'gopayOutOrderId'  => '2014052839972673',
            'bankCode'         => 'CCB',
            'tranFinishTime'   => '20140528001908',
            'goodsName'        => 'aaqins',
            'goodsDetail'      => '',
            'buyerName'        => 'aaqins',
            'buyerContact'     => '',
            'merRemark1'       => '',
            'merRemark2'       => '',
            'signValue'        => '38664c6a6119267e43bd20fe32f06583'
        ];

        $entry = ['id' => '20140320000000012'];

        $gofpay->setOptions($sourceData);
        $gofpay->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $gofpay = new Gofpay();
        $gofpay->setPrivateKey('56453hjkjkh567fgsd');

        $sourceData = [
            'version'          => '2.0',
            'charset'          => '2',
            'language'         => '1',
            'signType'         => '1',
            'tranCode'         => '8888',
            'merchantID'       => '0000039205',
            'merOrderNum'      => '201405280020678196',
            'tranAmt'          => '120',
            'feeAmt'           => '0.48',
            'tiliuAmt'         => '',
            'frontMerUrl'      => '',
            'hallid'           => '264',
            'backgroundMerUrl' => 'http://pay.szzpx.com/pay/pay_response.php?pay_system=14903',
            'tranDateTime'     => '20140527121636',
            'tranIP'           => '110.85.34.59',
            'respCode'         => '0000',
            'msgExt'           => '',
            'orderId'          => '2014052839972673',
            'gopayOutOrderId'  => '2014052839972673',
            'bankCode'         => 'CCB',
            'tranFinishTime'   => '20140528001908',
            'goodsName'        => 'aaqins',
            'goodsDetail'      => '',
            'buyerName'        => 'aaqins',
            'buyerContact'     => '',
            'merRemark1'       => '',
            'merRemark2'       => '',
            'signValue'        => '38664c6a6119267e43bd20fe32f06583'
        ];

        $entry = [
            'id' => '201405280020678196',
            'amount' => '1234.56'
        ];

        $gofpay->setOptions($sourceData);
        $gofpay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $gofpay = new Gofpay();
        $gofpay->setPrivateKey('56453hjkjkh567fgsd');

        $sourceData = [
            'version'          => '2.0',
            'charset'          => '2',
            'language'         => '1',
            'signType'         => '1',
            'tranCode'         => '8888',
            'merchantID'       => '0000039205',
            'merOrderNum'      => '201405280020678196',
            'tranAmt'          => '120',
            'feeAmt'           => '0.48',
            'tiliuAmt'         => '',
            'frontMerUrl'      => '',
            'hallid'           => '264',
            'backgroundMerUrl' => 'http://pay.szzpx.com/pay/pay_response.php?pay_system=14903',
            'tranDateTime'     => '20140527121636',
            'tranIP'           => '110.85.34.59',
            'respCode'         => '0000',
            'msgExt'           => '',
            'orderId'          => '2014052839972673',
            'gopayOutOrderId'  => '2014052839972673',
            'bankCode'         => 'CCB',
            'tranFinishTime'   => '20140528001908',
            'goodsName'        => 'aaqins',
            'goodsDetail'      => '',
            'buyerName'        => 'aaqins',
            'buyerContact'     => '',
            'merRemark1'       => '',
            'merRemark2'       => '',
            'signValue'        => '38664c6a6119267e43bd20fe32f06583'
        ];

        $entry = [
            'id' => '201405280020678196',
            'amount' => '120'
        ];

        $gofpay->setOptions($sourceData);
        $gofpay->verifyOrderPayment($entry);

        $this->assertEquals('RespCode=0000|JumpURL=', $gofpay->getMsg());
    }
}
