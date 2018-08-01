<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\HaoPay3C;
use Buzz\Message\Response;

class HaoPay3CTest extends DurianTestCase
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
     * 測試支付加密時沒有帶入privateKey的情況
     */
    public function testPayWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $haoPay3C = new HaoPay3C();
        $haoPay3C->getVerifyData();
    }

    /**
     * 測試支付加密時未指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = ['number' => ''];

        $haoPay3C = new HaoPay3C();
        $haoPay3C->setPrivateKey('1234');
        $haoPay3C->setOptions($sourceData);
        $haoPay3C->getVerifyData();
    }

    /**
     * 測試支付加密時代入支付平台不支援的銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '1234',
            'paymentVendorId' => '999',
            'amount' => '0.01',
            'orderId' => '201801220000008639',
            'notify_url' => 'http://two123.comuv.com',
        ];

        $haoPay3C = new HaoPay3C();
        $haoPay3C->setPrivateKey('1234');
        $haoPay3C->setOptions($sourceData);
        $haoPay3C->getVerifyData();
    }

    /**
     * 測試支付，但沒有帶入verify_url的情況
     */
    public function testPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1090',
            'number' => '19822546',
            'orderId' => '201506100000002073',
            'amount' => '100',
            'verify_url' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $haoPay3C = new HaoPay3C();
        $haoPay3C->setPrivateKey('1234');
        $haoPay3C->setOptions($options);
        $haoPay3C->getVerifyData();
    }

    /**
     * 測試支付，但缺少返回參數Message
     */
    public function testPayButReturnWithoutMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1090',
            'number' => '19822546',
            'orderId' => '201506100000002073',
            'amount' => '100',
            'verify_url' => 'payment.http.api.pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"orderNum":"201801220000008601","pl_orderNum":"","pl_url":"","Code":"SPIerr2"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $haoPay3C = new HaoPay3C();
        $haoPay3C->setContainer($this->container);
        $haoPay3C->setClient($this->client);
        $haoPay3C->setResponse($response);
        $haoPay3C->setPrivateKey('test');
        $haoPay3C->setOptions($options);
        $haoPay3C->getVerifyData();
    }

    /**
     * 測試支付，但回傳結果失敗
     */
    public function testPayButReturnFail()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '金流代碼{service}沒有允許的payid',
            180130
        );

        $options = [
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1090',
            'number' => '19822546',
            'orderId' => '201506100000002073',
            'amount' => '100',
            'verify_url' => 'payment.http.api.pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"orderNum":"201801220000008601","pl_orderNum":"","pl_url":"",' .
            '"Code":"SPIerr2","Message":"金流代碼{service}沒有允許的payid"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $haoPay3C = new HaoPay3C();
        $haoPay3C->setContainer($this->container);
        $haoPay3C->setClient($this->client);
        $haoPay3C->setResponse($response);
        $haoPay3C->setPrivateKey('test');
        $haoPay3C->setOptions($options);
        $haoPay3C->getVerifyData();
    }

    /**
     * 測試支付，但回傳結果沒有pl_url
     */
    public function testPayButReturnWithoutP1Url()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1090',
            'number' => '19822546',
            'orderId' => '201506100000002073',
            'amount' => '100',
            'verify_url' => 'payment.http.api.pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"orderNum":"201801220000008600","pl_orderNum":"5A654FB1AEBC7AA65C77",' .
            '"Code":"0000","Message":"success"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $haoPay3C = new HaoPay3C();
        $haoPay3C->setContainer($this->container);
        $haoPay3C->setClient($this->client);
        $haoPay3C->setResponse($response);
        $haoPay3C->setPrivateKey('test');
        $haoPay3C->setOptions($options);
        $haoPay3C->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '1090',
            'number' => '19822546',
            'orderId' => '201506100000002073',
            'amount' => '100',
            'verify_url' => 'payment.http.api.pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"orderNum":"201801190000008586","pl_orderNum":"5A65490BD4F847799262",' .
            '"pl_url":"https://pay.haopays.com/Bifubao2/pay/5A65490BD4F847799262/' .
            '1516587275","Code":"0000","Message":"success"}';

        $url = 'https://pay.haopays.com/Bifubao2/pay/5A65490BD4F847799262/1516587275';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $haoPay3C = new HaoPay3C();
        $haoPay3C->setContainer($this->container);
        $haoPay3C->setClient($this->client);
        $haoPay3C->setResponse($response);
        $haoPay3C->setPrivateKey('test');
        $haoPay3C->setOptions($options);
        $requestData = $haoPay3C->getVerifyData();

        $this->assertEquals($url, $requestData['post_url']);
        $this->assertEmpty($requestData['params']);
    }

    /**
     * 測試返回時基本參數設定沒有帶入key的情況
     */
    public function testReturnWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $haoPay3C = new HaoPay3C();
        $haoPay3C->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台未指定返回參數
     */
    public function testReturnWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'mId' => '50009',
            'orderNumber' => '201801090000008482',
        ];

        $haoPay3C = new HaoPay3C();
        $haoPay3C->setPrivateKey('1234');
        $haoPay3C->setOptions($sourceData);
        $haoPay3C->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳sign(加密簽名)
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'orderNum' => '201801220000008620',
            'pl_orderNum' => '5A6591C0B88957FCAD58',
            'pl_payState' => '4',
            'pl_payMessage' => '支付成功',
            'pl_transMoney' => '100',
        ];

        $haoPay3C = new HaoPay3C();
        $haoPay3C->setPrivateKey('1234');
        $haoPay3C->setOptions($sourceData);
        $haoPay3C->verifyOrderPayment([]);
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
            'orderNum' => '201801220000008620',
            'pl_orderNum' => '5A6591C0B88957FCAD58',
            'pl_payState' => '4',
            'pl_payMessage' => '支付成功',
            'pl_transMoney' => '100',
            'Sign' => 'd9f859f7d7133542f3167640d9f9ecc1',
        ];

        $haoPay3C = new HaoPay3C();
        $haoPay3C->setPrivateKey('1234');
        $haoPay3C->setOptions($sourceData);
        $haoPay3C->verifyOrderPayment([]);
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

        $sourceData = [
            'orderNum' => '201801220000008620',
            'pl_orderNum' => '5A6591C0B88957FCAD58',
            'pl_payState' => '0',
            'pl_payMessage' => '支付失敗',
            'pl_transMoney' => '100',
            'Sign' => '582a8c3044f58c4a30d9e476f12615fd',
        ];

        $haoPay3C = new HaoPay3C();
        $haoPay3C->setPrivateKey('1234');
        $haoPay3C->setOptions($sourceData);
        $haoPay3C->verifyOrderPayment([]);
    }

    /**
     * 測試返回支付單號不正確的情況
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'orderNum' => '201801220000008620',
            'pl_orderNum' => '5A6591C0B88957FCAD58',
            'pl_payState' => '4',
            'pl_payMessage' => '支付成功',
            'pl_transMoney' => '100',
            'Sign' => '7f483442aa32aa23fffa572609480893',
        ];

        $entry = ['id' => '201606220000002806'];

        $haoPay3C = new HaoPay3C();
        $haoPay3C->setPrivateKey('1234');
        $haoPay3C->setOptions($sourceData);
        $haoPay3C->verifyOrderPayment($entry);
    }

    /**
     * 測試返回支付金額不正確的情況
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'orderNum' => '201801220000008620',
            'pl_orderNum' => '5A6591C0B88957FCAD58',
            'pl_payState' => '4',
            'pl_payMessage' => '支付成功',
            'pl_transMoney' => '100',
            'Sign' => '7f483442aa32aa23fffa572609480893',
        ];

        $entry = [
            'id' => '201801220000008620',
            'amount' => '1.1',
        ];

        $haoPay3C = new HaoPay3C();
        $haoPay3C->setPrivateKey('1234');
        $haoPay3C->setOptions($sourceData);
        $haoPay3C->verifyOrderPayment($entry);
    }

    /**
     * 測試返回驗證成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'orderNum' => '201801220000008620',
            'pl_orderNum' => '5A6591C0B88957FCAD58',
            'pl_payState' => '4',
            'pl_payMessage' => '支付成功',
            'pl_transMoney' => '100',
            'Sign' => '7f483442aa32aa23fffa572609480893',
        ];

        $entry = [
            'id' => '201801220000008620',
            'amount' => '1.00',
        ];

        $haoPay3C = new HaoPay3C();
        $haoPay3C->setPrivateKey('1234');
        $haoPay3C->setOptions($sourceData);
        $haoPay3C->verifyOrderPayment($entry);

        $this->assertEquals('{"success":"true"}', $haoPay3C->getMsg());
    }
}
