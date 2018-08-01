<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\KeFuTong;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class KeFuTongTest extends DurianTestCase
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
     * 支付時的參數
     *
     * @var array
     */
    private $sourceData;

    /**
     * 對外返回時的參數
     *
     * @var array
     */
    private $verifyResult;

    /**
     * 返回時的參數
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

        $this->sourceData = [
            'paymentVendorId' => '1090',
            'orderId' => '201806250000012106',
            'number' => '899100000010689',
            'orderCreateDate' => '2018-06-26 15:03:03',
            'amount' => '1',
            'notify_url' => 'http://handsome.php',
            'verify_url' => 'payment.http.39.107.212.245',
            'verify_ip' => ['172.26.54.41', '172.26.54.42'],
        ];

        $codeUrl =  'weixin://wxpay/bizpayurl?appid=wxc8bda2993cd5ef62&mch_id=1273028201&' .
            'nonce_str=1142393973&product_id=42262455717816445120&time_stamp=1529898159&' .
            'sign=24602FE77176EC379FBF0B9D6B7A4DC5';

        $this->verifyResult = [
            'sign' => 'DD42FFD079C0D806E65DBD9824E6DA95',
            'platOrderId' => '201806250000012106',
            'tradeState' => 'WAIT_BUYER_PAY',
            'retCode' => '0000',
            'codeUrl' => $codeUrl,
            'funCode' => '2005',
            'merId' => '899100000010689',
            'outTradeNo' => '89910000001068920180625114325063163',
        ];

        $this->returnResult = [
            'funCode' => '2018',
            'orderAmt' => '1000',
            'outTradeNo' => '89910000001068920180625114325063163',
            'platMerId' => '899100000010689',
            'platOrderId' => '201806250000012106',
            'platTermId' => null,
            'sign' => '48e5080608b56624db8756aa08e7cd64',
            'tradeState' => 'TRADE_SUCCESS',
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
            '180142'
        );

        $keFuTong = new KeFuTong();
        $keFuTong->getVerifyData();
    }

    /**
     * 測試支付時未指定支付參數
     */
    public function testPayWithoutPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $keFuTong = new KeFuTong();
        $keFuTong->setPrivateKey('test');
        $keFuTong->setOptions([]);
        $keFuTong->getVerifyData();
    }

    /**
     * 測試支付時帶入支付平台不支援的銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $this->sourceData['paymentVendorId'] = '9999';

        $keFuTong = new KeFuTong();
        $keFuTong->setPrivateKey('test');
        $keFuTong->setOptions($this->sourceData);
        $keFuTong->getVerifyData();
    }

    /**
     * 測試支付時缺少verify_url
     */
    public function testPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $this->sourceData['verify_url'] = '';

        $keFuTong = new KeFuTong();
        $keFuTong->setPrivateKey('test');
        $keFuTong->setOptions($this->sourceData);
        $keFuTong->getVerifyData();
    }

    /**
     * 測試支付時未返回retCode
     */
    public function testPayNoReturnRetCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        unset($this->verifyResult['retCode']);

        $response = new Response();
        $response->setContent(json_encode($this->verifyResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/xml; charset=UTF-8');

        $keFuTong = new KeFuTong();
        $keFuTong->setContainer($this->container);
        $keFuTong->setClient($this->client);
        $keFuTong->setResponse($response);
        $keFuTong->setPrivateKey('test');
        $keFuTong->setOptions($this->sourceData);
        $keFuTong->getVerifyData();
    }

    /**
     * 測試支付時返回不成功
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '该笔订单请求失败',
            180130
        );

        $this->verifyResult['retCode'] = '1116';
        $this->verifyResult['retMsg'] = '该笔订单请求失败';

        $response = new Response();
        $response->setContent(json_encode($this->verifyResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/xml; charset=UTF-8');

        $keFuTong = new KeFuTong();
        $keFuTong->setContainer($this->container);
        $keFuTong->setClient($this->client);
        $keFuTong->setResponse($response);
        $keFuTong->setPrivateKey('test');
        $keFuTong->setOptions($this->sourceData);
        $keFuTong->getVerifyData();
    }

    /**
     * 測試支付時返回不成功且無錯誤訊息
     */
    public function testPayReturnNotSuccessAndNoErrorMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $this->verifyResult['retCode'] = '1116';
        unset($this->verifyResult['retMsg']);

        $response = new Response();
        $response->setContent(json_encode($this->verifyResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/xml; charset=UTF-8');

        $keFuTong = new KeFuTong();
        $keFuTong->setContainer($this->container);
        $keFuTong->setClient($this->client);
        $keFuTong->setResponse($response);
        $keFuTong->setPrivateKey('test');
        $keFuTong->setOptions($this->sourceData);
        $keFuTong->getVerifyData();
    }

    /**
     * 測試支付時未返回codeUrl
     */
    public function testPayNoReturnCodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        unset($this->verifyResult['codeUrl']);

        $response = new Response();
        $response->setContent(json_encode($this->verifyResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/xml; charset=UTF-8');

        $keFuTong = new KeFuTong();
        $keFuTong->setContainer($this->container);
        $keFuTong->setClient($this->client);
        $keFuTong->setResponse($response);
        $keFuTong->setPrivateKey('test');
        $keFuTong->setOptions($this->sourceData);
        $keFuTong->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $response = new Response();
        $response->setContent(json_encode($this->verifyResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/xml; charset=UTF-8');

        $keFuTong = new KeFuTong();
        $keFuTong->setContainer($this->container);
        $keFuTong->setClient($this->client);
        $keFuTong->setResponse($response);
        $keFuTong->setPrivateKey('test');
        $keFuTong->setOptions($this->sourceData);
        $keFuTong->getVerifyData();
        $data = $keFuTong->getVerifyData();

        $codeUrl =  'weixin://wxpay/bizpayurl?appid=wxc8bda2993cd5ef62&mch_id=1273028201&' .
            'nonce_str=1142393973&product_id=42262455717816445120&time_stamp=1529898159&' .
            'sign=24602FE77176EC379FBF0B9D6B7A4DC5';

        $this->assertEmpty($data);
        $this->assertEquals($codeUrl, $keFuTong->getQrcode());
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

        $keFuTong = new KeFuTong();
        $keFuTong->verifyOrderPayment([]);
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

        $keFuTong = new KeFuTong();
        $keFuTong->setPrivateKey('test');
        $keFuTong->verifyOrderPayment([]);
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

        unset($this->returnResult['sign']);

        $keFuTong = new KeFuTong();
        $keFuTong->setPrivateKey('test');
        $keFuTong->setOptions($this->returnResult);
        $keFuTong->verifyOrderPayment([]);
    }

    /**
     * 測試返回時加密簽名驗證錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $this->returnResult['sign'] = 'error';

        $keFuTong = new KeFuTong();
        $keFuTong->setPrivateKey('test');
        $keFuTong->setOptions($this->returnResult);
        $keFuTong->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為支付失敗
     */
    public function testReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $this->returnResult['tradeState'] = 'Failure';
        $this->returnResult['sign'] = '25521a52f34e86513e9f3fd1073f8c0a';

        $keFuTong = new KeFuTong();
        $keFuTong->setPrivateKey('test');
        $keFuTong->setOptions($this->returnResult);
        $keFuTong->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單編號錯誤
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $entry = ['id' => '201704100000002210'];

        $keFuTong = new KeFuTong();
        $keFuTong->setPrivateKey('test');
        $keFuTong->setOptions($this->returnResult);
        $keFuTong->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為訂單金額錯誤
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $entry = [
            'id' => '201806250000012106',
            'amount' => '1.01',
        ];

        $keFuTong = new KeFuTong();
        $keFuTong->setPrivateKey('test');
        $keFuTong->setOptions($this->returnResult);
        $keFuTong->verifyOrderPayment($entry);
    }

    /**
     * 測試返回成功
     */
    public function testReturnSuccess()
    {
        $entry = [
            'id' => '201806250000012106',
            'amount' => '10',
        ];

        $keFuTong = new KeFuTong();
        $keFuTong->setPrivateKey('test');
        $keFuTong->setOptions($this->returnResult);
        $keFuTong->verifyOrderPayment($entry);

        $this->assertEquals('{retCode:"success"}', $keFuTong->getMsg());
    }
}
