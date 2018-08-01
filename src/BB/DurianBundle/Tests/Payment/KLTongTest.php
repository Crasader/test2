<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\KLTong;
use Buzz\Message\Response;

class KLTongTest extends DurianTestCase
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

        $kLTong = new KLTong();
        $kLTong->getVerifyData();
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

        $kLTong = new KLTong();
        $kLTong->setPrivateKey('24ff81f25b24e89604ca6fa74c7a9d2f');

        $sourceData = ['number' => ''];

        $kLTong->setOptions($sourceData);
        $kLTong->getVerifyData();
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

        $kLTong = new KLTong();
        $kLTong->setPrivateKey('24ff81f25b24e89604ca6fa74c7a9d2f');

        $sourceData = [
            'number' => '802527',
            'orderId' => '201404050012804726',
            'paymentVendorId' => '999',
            'amount' => '55',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $kLTong->setOptions($sourceData);
        $kLTong->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $sourceData = [
            'number' => '802527',
            'orderId' => '201404050012804726',
            'paymentVendorId' => '3', //'3' => 'ABC'
            'amount' => '55',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $kLTong = new KLTong();
        $kLTong->setPrivateKey('24ff81f25b24e89604ca6fa74c7a9d2f');
        $kLTong->setOptions($sourceData);
        $encodeData = $kLTong->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals($sourceData['number'], $encodeData['MerchantID']);
        $this->assertEquals($sourceData['orderId'], $encodeData['MerOrderNo']);
        $this->assertEquals('ABC', $encodeData['BankID']);
        $this->assertEquals($sourceData['amount'], $encodeData['Money']);
        $this->assertEquals($notifyUrl, $encodeData['NoticeURL']);
        $this->assertEquals('', $encodeData['NoticePage']);
        $this->assertEquals('a25a65065447f1388cab08637455affd', $encodeData['sign']);
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

        $kLTong = new KLTong();

        $kLTong->verifyOrderPayment([]);
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

        $kLTong = new KLTong();
        $kLTong->setPrivateKey('24ff81f25b24e89604ca6fa74c7a9d2f');

         $sourceData = [
            'PayOrderNo' => '140407000713-802451-354e',
            'MerchantID' => '802527',
            'MerOrderNo' => '201404070013026716',
            'CardNo'     => 'CCB',
            'CardType'   => '15',
            'FactMoney'  => '500.00',
            'PayResult'  => 'true',
            'CustomizeA' => '',
            'CustomizeB' => '',
            'CustomizeC' => '',
            'PayTime'    => '2014-04-07+00%3A08%3A24.21',
            'sign'       => 'B08CD6B6J38DLKF6FE9090A52FB6ED5'
        ];

        $kLTong->setOptions($sourceData);
        $kLTong->verifyOrderPayment([]);
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

        $kLTong = new KLTong();
        $kLTong->setPrivateKey('24ff81f25b24e89604ca6fa74c7a9d2f');

        $sourceData = [
            'PayOrderNo' => '140407000713-802451-354e',
            'MerchantID' => '802527',
            'MerOrderNo' => '201404070013026716',
            'CardNo'     => 'CCB',
            'CardType'   => '15',
            'FactMoney'  => '500.00',
            'PayResult'  => 'true',
            'CustomizeA' => '',
            'CustomizeB' => '',
            'CustomizeC' => '',
            'sign'       => 'B08CD6B62077F257EFE9090A52FB6ED5'
        ];

        $kLTong->setOptions($sourceData);
        $kLTong->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時沒有支付平台缺少回傳參數(測試sign:加密簽名)
     */
    public function testVerifyWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $kLTong = new KLTong();
        $kLTong->setPrivateKey('24ff81f25b24e89604ca6fa74c7a9d2f');

        $sourceData = [
            'PayOrderNo' => '140407000713-802451-354e',
            'MerchantID' => '802527',
            'MerOrderNo' => '201404070013026716',
            'CardNo'     => 'CCB',
            'CardType'   => '15',
            'FactMoney'  => '500.00',
            'PayResult'  => 'true',
            'CustomizeA' => '',
            'CustomizeB' => '',
            'CustomizeC' => '',
            'PayTime'    => '2014-04-07+00%3A08%3A24.21'
        ];

        $kLTong->setOptions($sourceData);
        $kLTong->verifyOrderPayment([]);
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

        $kLTong = new KLTong();
        $kLTong->setPrivateKey('24ff81f25b24e89604ca6fa74c7a9d2f');

         $sourceData = [
            'PayOrderNo' => '140407000713-802451-354e',
            'MerchantID' => '802527',
            'MerOrderNo' => '201404070013026716',
            'CardNo'     => 'CCB',
            'CardType'   => '15',
            'FactMoney'  => '500.00',
            'PayResult'  => 'fail',
            'CustomizeA' => '',
            'CustomizeB' => '',
            'CustomizeC' => '',
            'PayTime'    => '2014-04-07+00%3A08%3A24.21',
            'sign'       => '1988518FA3C48846D78E52015A8AAA04'
        ];

        $kLTong->setOptions($sourceData);
        $kLTong->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $kLTong = new KLTong();
        $kLTong->setPrivateKey('24ff81f25b24e89604ca6fa74c7a9d2f');

         $sourceData = [
            'PayOrderNo' => '140407000713-802451-354e',
            'MerchantID' => '802527',
            'MerOrderNo' => '201404070013026716',
            'CardNo'     => 'CCB',
            'CardType'   => '15',
            'FactMoney'  => '500.00',
            'PayResult'  => 'true',
            'CustomizeA' => '',
            'CustomizeB' => '',
            'CustomizeC' => '',
            'PayTime'    => '2014-04-07+00%3A08%3A24.21',
            'sign'       => 'E8D26377F6E5616A1E4029D168C17CB2'
        ];

        $entry = ['id' => '20140113143143'];

        $kLTong->setOptions($sourceData);
        $kLTong->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $kLTong = new KLTong();
        $kLTong->setPrivateKey('24ff81f25b24e89604ca6fa74c7a9d2f');

         $sourceData = [
            'PayOrderNo' => '140407000713-802451-354e',
            'MerchantID' => '802527',
            'MerOrderNo' => '201404070013026716',
            'CardNo'     => 'CCB',
            'CardType'   => '15',
            'FactMoney'  => '500.00',
            'PayResult'  => 'true',
            'CustomizeA' => '',
            'CustomizeB' => '',
            'CustomizeC' => '',
            'PayTime'    => '2014-04-07+00%3A08%3A24.21',
            'sign'       => 'E8D26377F6E5616A1E4029D168C17CB2'
        ];

        $entry = [
            'id' => '201404070013026716',
            'amount' => '115.00'
        ];

        $kLTong->setOptions($sourceData);
        $kLTong->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $kLTong = new KLTong();
        $kLTong->setPrivateKey('24ff81f25b24e89604ca6fa74c7a9d2f');

        $sourceData = [
            'PayOrderNo' => '140407000713-802451-354e',
            'MerchantID' => '802527',
            'MerOrderNo' => '201404070013026716',
            'CardNo'     => 'CCB',
            'CardType'   => '15',
            'FactMoney'  => '500.00',
            'PayResult'  => 'true',
            'CustomizeA' => '',
            'CustomizeB' => '',
            'CustomizeC' => '',
            'PayTime'    => '2014-04-07+00%3A08%3A24.21',
            'sign'       => 'E8D26377F6E5616A1E4029D168C17CB2'
        ];

        $entry = [
            'id' => '201404070013026716',
            'amount' => '500.00'
        ];

        $kLTong->setOptions($sourceData);
        $kLTong->verifyOrderPayment($entry);

        $this->assertEquals('OK', $kLTong->getMsg());
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

        $kLTong = new KLTong();
        $kLTong->paymentTracking();
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
            'number' => '802527',
            'orderId' => '201404050012804726',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $kLTong = new KLTong();
        $kLTong->setPrivateKey('24ff81f25b24e89604ca6fa74c7a9d2f');
        $kLTong->setOptions($sourceData);
        $kLTong->paymentTracking();
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

        $result = 'OK,http://user@:80';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '802527',
            'orderId' => '201404050012804726',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.kltong.com'
        ];

        $kLTong = new KLTong();
        $kLTong->setContainer($this->container);
        $kLTong->setClient($this->client);
        $kLTong->setResponse($response);
        $kLTong->setPrivateKey('b33818e9b439dd682ca1d88df8b7b219');
        $kLTong->setOptions($sourceData);
        $kLTong->paymentTracking();
    }

    /**
     * 測試訂單查詢對外返回結果錯誤
     */
    public function testTrackingReturnConnectionPaymentGatewayError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'OK,订单正在处理中,请稍候!',
            180123
        );

        $result = 'OK,订单正在处理中,请稍候!';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=GBK');

        $sourceData = [
            'number' => '802527',
            'orderId' => '201404050012804726',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.kltong.com'
        ];

        $kLTong = new KLTong();
        $kLTong->setContainer($this->container);
        $kLTong->setClient($this->client);
        $kLTong->setResponse($response);
        $kLTong->setPrivateKey('b33818e9b439dd682ca1d88df8b7b219');
        $kLTong->setOptions($sourceData);
        $kLTong->paymentTracking();
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

        $result = 'OK,http://118.232.50.208/return.php?pay_system=12345';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '802527',
            'orderId' => '201404050012804726',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.kltong.com'
        ];

        $kLTong = new KLTong();
        $kLTong->setContainer($this->container);
        $kLTong->setClient($this->client);
        $kLTong->setResponse($response);
        $kLTong->setPrivateKey('b33818e9b439dd682ca1d88df8b7b219');
        $kLTong->setOptions($sourceData);
        $kLTong->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數sign
     */
    public function testPaymentTrackingResultWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = 'OK,http://118.232.50.208/return.php?'.
            'pay_system=12345&PayOrderNo=140825135503-805545-9638&'.
            'MerchantID=802530&MerOrderNo=201408250000000049&CardNo=ICBC&'.
            'CardType=15&FactMoney=0.10&PayResult=true&CustomizeA=&'.
            'CustomizeB=&CustomizeC=&PayTime=2014-08-25%2013%3A42%3A31.847';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '802527',
            'orderId' => '201404050012804726',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.kltong.com'
        ];

        $kLTong = new KLTong();
        $kLTong->setContainer($this->container);
        $kLTong->setClient($this->client);
        $kLTong->setResponse($response);
        $kLTong->setPrivateKey('b33818e9b439dd682ca1d88df8b7b219');
        $kLTong->setOptions($sourceData);
        $kLTong->paymentTracking();
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

        $result = 'OK,http://118.232.50.208/return.php?'.
            'pay_system=12345&PayOrderNo=140825135503-805545-9638&'.
            'MerchantID=802530&MerOrderNo=201408250000000049&CardNo=ICBC&'.
            'CardType=15&FactMoney=0.10&PayResult=true&CustomizeA=&'.
            'CustomizeB=&CustomizeC=&PayTime=2014-08-25%2013%3A42%3A31.847&'.
            'sign=F63F0B7FCF1641987676C850A475A70A';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '802527',
            'orderId' => '201404050012804726',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.kltong.com'
        ];

        $kLTong = new KLTong();
        $kLTong->setContainer($this->container);
        $kLTong->setClient($this->client);
        $kLTong->setResponse($response);
        $kLTong->setPrivateKey('b33818e9b439dd682ca1d88df8b7b219');
        $kLTong->setOptions($sourceData);
        $kLTong->paymentTracking();
    }

    /**
     * 測試訂單查詢結果回傳訂單處理中
     */
    public function testTrackingReturnOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $result = 'OK,http://118.232.50.208/return.php?'.
            'pay_system=12345&PayOrderNo=140825135503-805545-9638&'.
            'MerchantID=802530&MerOrderNo=201408250000000049&CardNo=ICBC&'.
            'CardType=15&FactMoney=0.10&PayResult=treat&CustomizeA=&'.
            'CustomizeB=&CustomizeC=&PayTime=2014-08-25%2013%3A42%3A31.847&'.
            'sign=F63F0B7FCF1641987676C850A475A70A';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '802527',
            'orderId' => '201404050012804726',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.kltong.com'
        ];

        $kLTong = new KLTong();
        $kLTong->setContainer($this->container);
        $kLTong->setClient($this->client);
        $kLTong->setResponse($response);
        $kLTong->setPrivateKey('b33818e9b439dd682ca1d88df8b7b219');
        $kLTong->setOptions($sourceData);
        $kLTong->paymentTracking();
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

        $result = 'OK,http://118.232.50.208/return.php?'.
            'pay_system=12345&PayOrderNo=140825135503-805545-9638&'.
            'MerchantID=802530&MerOrderNo=201408250000000049&CardNo=ICBC&'.
            'CardType=15&FactMoney=0.10&PayResult=false&CustomizeA=&'.
            'CustomizeB=&CustomizeC=&PayTime=2014-08-25%2013%3A42%3A31.847&'.
            'sign=B1ED42FCA7539460FCC04EFF582BB86B';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '802527',
            'orderId' => '201404050012804726',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.kltong.com'
        ];

        $kLTong = new KLTong();
        $kLTong->setContainer($this->container);
        $kLTong->setClient($this->client);
        $kLTong->setResponse($response);
        $kLTong->setPrivateKey('24ff81f25b24e89604ca6fa74c7a9d2f');
        $kLTong->setOptions($sourceData);
        $kLTong->paymentTracking();
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

        $result = 'OK,http://118.232.50.208/return.php?'.
            'pay_system=12345&PayOrderNo=140825135503-805545-9638&'.
            'MerchantID=802530&MerOrderNo=201408250000000049&CardNo=ICBC&'.
            'CardType=15&FactMoney=0.10&PayResult=true&CustomizeA=&'.
            'CustomizeB=&CustomizeC=&PayTime=2014-08-25%2013%3A42%3A31.847&'.
            'sign=50A9555C541A67F5FF655D6514F151BD';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '802527',
            'orderId' => '201408250000000049',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.kltong.com',
            'amount' => '1000.00'
        ];

        $kLTong = new KLTong();
        $kLTong->setContainer($this->container);
        $kLTong->setClient($this->client);
        $kLTong->setResponse($response);
        $kLTong->setPrivateKey('b33818e9b439dd682ca1d88df8b7b219');
        $kLTong->setOptions($sourceData);
        $kLTong->paymentTracking();
    }

    /**
     * 測試訂單查詢
     */
    public function testPaymentTracking()
    {
        $result = 'OK,http://118.232.50.208/return.php?'.
            'pay_system=12345&PayOrderNo=140825135503-805545-9638&'.
            'MerchantID=802530&MerOrderNo=201408250000000049&CardNo=ICBC&'.
            'CardType=15&FactMoney=0.10&PayResult=true&CustomizeA=&'.
            'CustomizeB=&CustomizeC=&PayTime=2014-08-25%2013%3A42%3A31.847&'.
            'sign=50A9555C541A67F5FF655D6514F151BD';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '802527',
            'orderId' => '201408250000000049',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.kltong.com',
            'amount' => '0.1'
        ];

        $kLTong = new KLTong();
        $kLTong->setContainer($this->container);
        $kLTong->setClient($this->client);
        $kLTong->setResponse($response);
        $kLTong->setPrivateKey('b33818e9b439dd682ca1d88df8b7b219');
        $kLTong->setOptions($sourceData);
        $kLTong->paymentTracking();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒代入privateKey
     */
    public function testGetPaymentTrackingDataWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $kLTong = new KLTong();
        $kLTong->getPaymentTrackingData();
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

        $kLTong = new KLTong();
        $kLTong->setPrivateKey('b33818e9b439dd682ca1d88df8b7b219');
        $kLTong->getPaymentTrackingData();
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

        $options = [
            'number' => '802527',
            'orderId' => '201408250000000049',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $kLTong = new KLTong();
        $kLTong->setPrivateKey('b33818e9b439dd682ca1d88df8b7b219');
        $kLTong->setOptions($options);
        $kLTong->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'number' => '802527',
            'orderId' => '201408250000000049',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.315d.com:9180',
        ];

        $kLTong = new KLTong();
        $kLTong->setPrivateKey('b33818e9b439dd682ca1d88df8b7b219');
        $kLTong->setOptions($options);
        $trackingData = $kLTong->getPaymentTrackingData();

        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('/busics/MerQuery', $trackingData['path']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('payment.http.www.315d.com:9180', $trackingData['headers']['Host']);

        $this->assertEquals('802527', $trackingData['form']['MerchantID']);
        $this->assertEquals('201408250000000049', $trackingData['form']['MerOrderID']);
        $this->assertEquals('50d3239794bec1e6f662b697c6e76b13', $trackingData['form']['sign']);
    }

    /**
     * 測試驗證訂單查詢沒代入privateKey
     */
    public function testPaymentTrackingVerifyWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $kLTong = new KLTong();
        $kLTong->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但未指定返回參數
     */
    public function testPaymentTrackingVerifyWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = 'OK,http://118.232.50.208/return.php?pay_system=12345';

        $sourceData = ['content' => urlencode($content)];

        $kLTong = new KLTong();
        $kLTong->setPrivateKey('b33818e9b439dd682ca1d88df8b7b219');
        $kLTong->setOptions($sourceData);
        $kLTong->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢缺少參數sign(加密簽名)
     */
    public function testPaymentTrackingVerifyWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = 'OK,http://118.232.50.208/return.php?pay_system=12345' .
            '&hallid=6?PayOrderNo=140825135503-805545-9638&MerchantID=802530' .
            '&MerOrderNo=201408250000000049&CardNo=ICBC&CardType=15' .
            '&FactMoney=0.10&PayResult=true&CustomizeA=&CustomizeB=' .
            '&CustomizeC=&PayTime=2014-08-25%2013%3A42%3A31.847&ErrorMsg=';

        $sourceData = ['content' => urlencode($content)];

        $kLTong = new KLTong();
        $kLTong->setPrivateKey('b33818e9b439dd682ca1d88df8b7b219');
        $kLTong->setOptions($sourceData);
        $kLTong->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢簽名驗證錯誤
     */
    public function testPaymentTrackingVerifySignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $content = 'OK,http://118.232.50.208/return.php?' .
            'pay_system=12345&hallid=6?PayOrderNo=140825135503-805545-9638' .
            '&MerchantID=802530&MerOrderNo=201408250000000049&CardNo=ICBC' .
            '&CardType=15&FactMoney=0.10&PayResult=true&CustomizeA=' .
            '&CustomizeB=&CustomizeC=&PayTime=2014-08-25%2013%3A42%3A31.847' .
            '&ErrorMsg=&sign=F63F0B7FCF1641987676C850A475A70A';

        $sourceData = ['content' => urlencode($content)];

        $kLTong = new KLTong();
        $kLTong->setPrivateKey('b33818e9b439dd682ca1d88df8b7b219');
        $kLTong->setOptions($sourceData);
        $kLTong->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢回傳訂單處理中
     */
    public function testPaymentTrackingVerifyOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $content = 'OK,http://118.232.50.208/return.php?' .
            'pay_system=12345&hallid=6?PayOrderNo=140825135503-805545-9638' .
            '&MerchantID=802530&MerOrderNo=201408250000000049&CardNo=ICBC' .
            '&CardType=15&FactMoney=0.10&PayResult=treat&CustomizeA=' .
            '&CustomizeB=&CustomizeC=&PayTime=2014-08-25%2013%3A42%3A31.847' .
            '&ErrorMsg=&sign=F63F0B7FCF1641987676C850A475A70A';

        $sourceData = ['content' => urlencode($content)];

        $kLTong = new KLTong();
        $kLTong->setPrivateKey('b33818e9b439dd682ca1d88df8b7b219');
        $kLTong->setOptions($sourceData);
        $kLTong->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢支付失敗
     */
    public function testPaymentTrackingVerifyPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $content = 'OK,http://118.232.50.208/return.php?' .
            'pay_system=12345&hallid=6?PayOrderNo=140825135503-805545-9638' .
            '&MerchantID=802530&MerOrderNo=201408250000000049&CardNo=ICBC' .
            '&CardType=15&FactMoney=0.10&PayResult=false&CustomizeA=' .
            '&CustomizeB=&CustomizeC=&PayTime=2014-08-25%2013%3A42%3A31.847' .
            '&ErrorMsg=&sign=3B1444CD2BCB7D74FAA16085B369AFD2';

        $sourceData = ['content' => urlencode($content)];

        $kLTong = new KLTong();
        $kLTong->setPrivateKey('b33818e9b439dd682ca1d88df8b7b219');
        $kLTong->setOptions($sourceData);
        $kLTong->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但金額不正確
     */
    public function testPaymentTrackingVerifyButAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $content = 'OK,http://118.232.50.208/return.php?pay_system=12345' .
            '&hallid=6?PayOrderNo=140825135503-805545-9638&MerchantID=802530' .
            '&MerOrderNo=201408250000000049&CardNo=ICBC&CardType=15' .
            '&FactMoney=0.10&PayResult=true&CustomizeA=&CustomizeB=' .
            '&CustomizeC=&PayTime=2014-08-25%2013%3A42%3A31.847&ErrorMsg=' .
            '&sign=50A9555C541A67F5FF655D6514F151BD';

        $sourceData = [
            'content' => urlencode($content),
            'amount' => 500
        ];

        $kLTong = new KLTong();
        $kLTong->setPrivateKey('b33818e9b439dd682ca1d88df8b7b219');
        $kLTong->setOptions($sourceData);
        $kLTong->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $content = 'OK,http://118.232.50.208/return.php?pay_system=12345' .
            '&hallid=6?PayOrderNo=140825135503-805545-9638&MerchantID=802530' .
            '&MerOrderNo=201408250000000049&CardNo=ICBC&CardType=15' .
            '&FactMoney=0.10&PayResult=true&CustomizeA=&CustomizeB=' .
            '&CustomizeC=&PayTime=2014-08-25%2013%3A42%3A31.847&ErrorMsg=' .
            '&sign=50A9555C541A67F5FF655D6514F151BD';

        $sourceData = [
            'content' => urlencode($content),
            'amount' => 0.1
        ];

        $kLTong = new KLTong();
        $kLTong->setPrivateKey('b33818e9b439dd682ca1d88df8b7b219');
        $kLTong->setOptions($sourceData);
        $kLTong->paymentTrackingVerify();
    }
}
