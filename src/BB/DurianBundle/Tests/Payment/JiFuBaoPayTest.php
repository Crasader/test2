<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\JiFuBaoPay;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class JiFuBaoPayTest extends DurianTestCase
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
            'paymentVendorId' => '1098',
            'number' => '9527',
            'notify_url' => 'http://www.seafood.help/',
            'orderId' => '201806230000046079',
            'amount' => '0.01',
            'orderCreateDate' => '2018-06-23 14:24:34',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.jfbaopay.com',
        ];

        $this->returnResult = [
            'transTypeNo' => 'C2000102',
            'respCode' => '00',
            'respMsg' => '支付成功',
            'reqReserved' => '',
            'queryId' => '20180623142435075678',
            'signature' => 'C5520CFE6F32ECC3552CF794B7D0A590',
            'orderId' => '201806230000046079',
            'merchantNum' => '9527',
            'txnTime' => '201806174142435',
            'txnAmt' => '1',
        ];
    }

    /**
     * 測試支付時缺少私鑰
     */
    public function testPayWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $jiFuBaoPay = new JiFuBaoPay();
        $jiFuBaoPay->getVerifyData();
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

        $jiFuBaoPay = new JiFuBaoPay();
        $jiFuBaoPay->setPrivateKey('test');
        $jiFuBaoPay->getVerifyData();
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

        $jiFuBaoPay = new JiFuBaoPay();
        $jiFuBaoPay->setPrivateKey('test');
        $jiFuBaoPay->setOptions($this->option);
        $jiFuBaoPay->getVerifyData();
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

        $jiFuBaoPay = new JiFuBaoPay();
        $jiFuBaoPay->setPrivateKey('test');
        $jiFuBaoPay->setOptions($this->option);
        $jiFuBaoPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回respCode
     */
    public function testPayReturnWithoutRespCode()
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

        $jiFuBaoPay = new JiFuBaoPay();
        $jiFuBaoPay->setContainer($this->container);
        $jiFuBaoPay->setClient($this->client);
        $jiFuBaoPay->setResponse($response);
        $jiFuBaoPay->setPrivateKey('test');
        $jiFuBaoPay->setOptions($this->option);
        $jiFuBaoPay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗且有respMsg
     */
    public function testPayReturnNotSuccessWithRespMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '订单验签失败',
            180130
        );

        $result = [
            'transTypeNo' => 'C2000101',
            'respCode' => '88',
            'backUrl' => 'http://www.seafood.help/',
            'respMsg' => '订单验签失败',
            'reqReserved' => null,
            'orderId' => '201806230000046079',
            'merchantNum' => '9527',
            'txnAmt' => '1',
            'txnTime' => '20180623142434',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $jiFuBaoPay = new JiFuBaoPay();
        $jiFuBaoPay->setContainer($this->container);
        $jiFuBaoPay->setClient($this->client);
        $jiFuBaoPay->setResponse($response);
        $jiFuBaoPay->setPrivateKey('test');
        $jiFuBaoPay->setOptions($this->option);
        $jiFuBaoPay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $result = [
            'transTypeNo' => 'C2000101',
            'respCode' => '88',
            'backUrl' => 'http://www.seafood.help/',
            'reqReserved' => null,
            'orderId' => '201806230000046079',
            'merchantNum' => '9527',
            'txnAmt' => '1',
            'txnTime' => '20180623142434',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $jiFuBaoPay = new JiFuBaoPay();
        $jiFuBaoPay->setContainer($this->container);
        $jiFuBaoPay->setClient($this->client);
        $jiFuBaoPay->setResponse($response);
        $jiFuBaoPay->setPrivateKey('test');
        $jiFuBaoPay->setOptions($this->option);
        $jiFuBaoPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回payInfo
     */
    public function testPayReturnWithoutPayInfo()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'transTypeNo' => 'C2000102',
            'respCode' => '66',
            'backUrl' => 'http://www.seafood.help/',
            'respMsg' => null,
            'reqReserved' => null,
            'queryId' => '201806230000046079',
            'signature' => '63F2AA5B2A0CC3E443A4161F6C3316B5',
            'orderId' => '201806230000046079',
            'merchantNum' => '9527',
            'txnAmt' => '1',
            'txnTime' => '20180623142434',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $jiFuBaoPay = new JiFuBaoPay();
        $jiFuBaoPay->setContainer($this->container);
        $jiFuBaoPay->setClient($this->client);
        $jiFuBaoPay->setResponse($response);
        $jiFuBaoPay->setPrivateKey('test');
        $jiFuBaoPay->setOptions($this->option);
        $jiFuBaoPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $this->option['paymentVendorId'] = '1092';

        $result = [
            'transTypeNo' => 'T0000102',
            'respCode' => '66',
            'backUrl' => 'http://www.seafood.help/',
            'payInfo' => 'https://qr.alipay.com/bax07737l7zz5p0hhz8o60ea',
            'respMsg' => '下单成功',
            'reqReserved' => null,
            'queryId' => '201807270000012999',
            'signature' => 'C10ECA718BB6AD088AF2D73AD8DD94F0',
            'orderId' => '201807270000012999',
            'merchantNum' => '201806130024118',
            'txnAmt' => '10000',
            'txnTime' => '20180727173923',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $jiFuBaoPay = new JiFuBaoPay();
        $jiFuBaoPay->setContainer($this->container);
        $jiFuBaoPay->setClient($this->client);
        $jiFuBaoPay->setResponse($response);
        $jiFuBaoPay->setPrivateKey('test');
        $jiFuBaoPay->setOptions($this->option);
        $data = $jiFuBaoPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://qr.alipay.com/bax07737l7zz5p0hhz8o60ea', $jiFuBaoPay->getQrcode());
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $result = [
            'transTypeNo' => 'C2000102',
            'respCode' => '66',
            'backUrl' => 'http://www.seafood.help/',
            'payInfo' => 'http://106.15.159.189/smartchannel/alipay/topup?order_code=9jclkk4dogi7njxqs5hh',
            'respMsg' => null,
            'reqReserved' => null,
            'queryId' => '201806230000046079',
            'signature' => '63F2AA5B2A0CC3E443A4161F6C3316B5',
            'orderId' => '201806230000046079',
            'merchantNum' => '9527',
            'txnAmt' => '1',
            'txnTime' => '20180623142434',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $jiFuBaoPay = new JiFuBaoPay();
        $jiFuBaoPay->setContainer($this->container);
        $jiFuBaoPay->setClient($this->client);
        $jiFuBaoPay->setResponse($response);
        $jiFuBaoPay->setPrivateKey('test');
        $jiFuBaoPay->setOptions($this->option);
        $data = $jiFuBaoPay->getVerifyData();

        $this->assertEquals('http://106.15.159.189/smartchannel/alipay/topup', $data['post_url']);
        $this->assertEquals('9jclkk4dogi7njxqs5hh', $data['params']['order_code']);
        $this->assertEquals('GET', $jiFuBaoPay->getPayMethod());
    }

    /**
     * 測試返回時缺少私鑰
     */
    public function testReturnWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $jiFuBaoPay = new JiFuBaoPay();
        $jiFuBaoPay->verifyOrderPayment([]);
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

        $jiFuBaoPay = new JiFuBaoPay();
        $jiFuBaoPay->setPrivateKey('test');
        $jiFuBaoPay->verifyOrderPayment([]);
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

        unset($this->returnResult['signature']);

        $jiFuBaoPay = new JiFuBaoPay();
        $jiFuBaoPay->setPrivateKey('test');
        $jiFuBaoPay->setOptions($this->returnResult);
        $jiFuBaoPay->verifyOrderPayment([]);
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

        $this->returnResult['signature'] = 'F893D0C73636C6827A30BD25D8D1D4D4';

        $jiFuBaoPay = new JiFuBaoPay();
        $jiFuBaoPay->setPrivateKey('test');
        $jiFuBaoPay->setOptions($this->returnResult);
        $jiFuBaoPay->verifyOrderPayment([]);
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

        $this->returnResult['respCode'] = '11';
        $this->returnResult['signature'] = 'C5520CFE6F32ECC3552CF794B7D0A590';

        $jiFuBaoPay = new JiFuBaoPay();
        $jiFuBaoPay->setPrivateKey('test');
        $jiFuBaoPay->setOptions($this->returnResult);
        $jiFuBaoPay->verifyOrderPayment([]);
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

        $jiFuBaoPay = new JiFuBaoPay();
        $jiFuBaoPay->setPrivateKey('test');
        $jiFuBaoPay->setOptions($this->returnResult);
        $jiFuBaoPay->verifyOrderPayment($entry);
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
            'id' => '201806230000046079',
            'amount' => '123',
        ];

        $jiFuBaoPay = new JiFuBaoPay();
        $jiFuBaoPay->setPrivateKey('test');
        $jiFuBaoPay->setOptions($this->returnResult);
        $jiFuBaoPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $entry = [
            'id' => '201806230000046079',
            'amount' => '0.01',
        ];

        $jiFuBaoPay = new JiFuBaoPay();
        $jiFuBaoPay->setPrivateKey('test');
        $jiFuBaoPay->setOptions($this->returnResult);
        $jiFuBaoPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $jiFuBaoPay->getMsg());
    }
}
