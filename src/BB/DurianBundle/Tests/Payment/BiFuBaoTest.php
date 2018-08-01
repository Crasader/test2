<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\BiFuBao;
use Buzz\Message\Response;

class BiFuBaoTest extends DurianTestCase
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
     * 測試支付時缺少私鑰
     */
    public function testPayWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $biFuBao = new BiFuBao();
        $biFuBao->getVerifyData();
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

        $sourceData = ['number' => ''];

        $biFuBao = new BiFuBao();
        $biFuBao->setPrivateKey('test');
        $biFuBao->setOptions($sourceData);
        $biFuBao->getVerifyData();
    }

    /**
     * 測試支付時代入支付平台不支援的銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => 'SP20180531144915',
            'paymentVendorId' => '9999',
            'amount' => '0.01',
            'orderId' => '201806280000014748',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'postUrl' => 'http://s3.av8dpay.com/bifubao-gateway/front-pay/',
            'orderCreateDate' => '2018-06-28 11:45:55',
        ];

        $biFuBao = new BiFuBao();
        $biFuBao->setPrivateKey('test');
        $biFuBao->setOptions($sourceData);
        $biFuBao->getVerifyData();
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

        $sourceData = [
            'number' => 'SP20180531144915',
            'paymentVendorId' => '1103',
            'amount' => '0.01',
            'orderId' => '201806280000014748',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => '',
            'postUrl' => 'http://s3.av8dpay.com/bifubao-gateway/front-pay/',
            'orderCreateDate' => '2018-06-28 11:45:55',
        ];

        $biFuBao = new BiFuBao();
        $biFuBao->setPrivateKey('test');
        $biFuBao->setOptions($sourceData);
        $biFuBao->getVerifyData();
    }

    /**
     * 測試支付時未返回RET_CODE
     */
    public function testPayNoReturnRetCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'QR_CODE' => 'https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027&_bid=2183&t=5V57838efbbeac272888',
            'SIGNED_MSG' => 'ee984ce987edbef9bf38f140b2ef3a9c',
            'SYS_ORDER' => 'PA20180628204724TWOTOWER3159',
            'TRAN_AMT' => '100',
            'TRAN_CODE' => '201806280000014751',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/x-www-form-urlencoded;charset=UTF-8');

        $sourceData = [
            'number' => 'SP20180531144915',
            'paymentVendorId' => '1103',
            'amount' => '0.01',
            'orderId' => '201806280000014748',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'http://s3.av8dpay.com/bifubao-gateway/front-pay/',
            'orderCreateDate' => '2018-06-28 11:45:55',
        ];

        $biFuBao = new BiFuBao();
        $biFuBao->setContainer($this->container);
        $biFuBao->setClient($this->client);
        $biFuBao->setResponse($response);
        $biFuBao->setPrivateKey('test');
        $biFuBao->setOptions($sourceData);
        $biFuBao->getVerifyData();
    }

    /**
     * 測試支付失敗
     */
    public function testPayFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '商戶渠道關閉',
            180130
        );

        $result = [
            'RET_CODE' => 'BANK_ROUTE_CLOSE',
            'RET_MSG' => '商戶渠道關閉',
            'SIGNED_MSG' => '43ebd01964309178336f450d02f314af',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/x-www-form-urlencoded;charset=UTF-8');

        $sourceData = [
            'number' => 'SP20180531144915',
            'paymentVendorId' => '1103',
            'amount' => '0.01',
            'orderId' => '201806280000014748',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'http://s3.av8dpay.com/bifubao-gateway/front-pay/',
            'orderCreateDate' => '2018-06-28 11:45:55',
        ];

        $biFuBao = new BiFuBao();
        $biFuBao->setContainer($this->container);
        $biFuBao->setClient($this->client);
        $biFuBao->setResponse($response);
        $biFuBao->setPrivateKey('test');
        $biFuBao->setOptions($sourceData);
        $biFuBao->getVerifyData();
    }

    /**
     * 測試支付失敗沒返回RET_MSG
     */
    public function testPayFailedWithoutRetMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $result = [
            'RET_CODE' => 'BANK_ROUTE_CLOSE',
            'SIGNED_MSG' => '43ebd01964309178336f450d02f314af',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/x-www-form-urlencoded;charset=UTF-8');

        $sourceData = [
            'number' => 'SP20180531144915',
            'paymentVendorId' => '1103',
            'amount' => '0.01',
            'orderId' => '201806280000014748',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'http://s3.av8dpay.com/bifubao-gateway/front-pay/',
            'orderCreateDate' => '2018-06-28 11:45:55',
        ];

        $biFuBao = new BiFuBao();
        $biFuBao->setContainer($this->container);
        $biFuBao->setClient($this->client);
        $biFuBao->setResponse($response);
        $biFuBao->setPrivateKey('test');
        $biFuBao->setOptions($sourceData);
        $biFuBao->getVerifyData();
    }

    /**
     * 測試支付時未返回QR_CODE
     */
    public function testPayNoReturnQrCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'RET_CODE' => 'SUCCESS',
            'SIGNED_MSG' => 'ee984ce987edbef9bf38f140b2ef3a9c',
            'SYS_ORDER' => 'PA20180628204724TWOTOWER3159',
            'TRAN_AMT' => '100',
            'TRAN_CODE' => '201806280000014751',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/x-www-form-urlencoded;charset=UTF-8');

        $sourceData = [
            'number' => 'SP20180531144915',
            'paymentVendorId' => '1103',
            'amount' => '0.01',
            'orderId' => '201806280000014748',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'http://s3.av8dpay.com/bifubao-gateway/front-pay/',
            'orderCreateDate' => '2018-06-28 11:45:55',
        ];

        $biFuBao = new BiFuBao();
        $biFuBao->setContainer($this->container);
        $biFuBao->setClient($this->client);
        $biFuBao->setResponse($response);
        $biFuBao->setPrivateKey('test');
        $biFuBao->setOptions($sourceData);
        $biFuBao->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $result = [
            'QR_CODE' => 'https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027&_bid=2183',
            'RET_CODE' => 'SUCCESS',
            'SIGNED_MSG' => 'ee984ce987edbef9bf38f140b2ef3a9c',
            'SYS_ORDER' => 'PA20180628204724TWOTOWER3159',
            'TRAN_AMT' => '100',
            'TRAN_CODE' => '201806280000014751',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/x-www-form-urlencoded;charset=UTF-8');

        $sourceData = [
            'number' => 'SP20180531144915',
            'paymentVendorId' => '1103',
            'amount' => '0.01',
            'orderId' => '201806280000014748',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'http://s3.av8dpay.com/bifubao-gateway/front-pay/',
            'orderCreateDate' => '2018-06-28 11:45:55',
        ];

        $biFuBao = new BiFuBao();
        $biFuBao->setContainer($this->container);
        $biFuBao->setClient($this->client);
        $biFuBao->setResponse($response);
        $biFuBao->setPrivateKey('test');
        $biFuBao->setOptions($sourceData);
        $biFuBao->getVerifyData();
        $data = $biFuBao->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027&_bid=2183', $biFuBao->getQrcode());
    }

    /**
     * 測試網銀支付
     */
    public function testBankPay()
    {
        $options = [
            'number' => 'SP20180531144915',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201806280000014748',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'http://s3.av8dpay.com/bifubao-gateway/front-pay/',
            'orderCreateDate' => '2018-06-28 11:45:55',
        ];

        $biFuBao = new BiFuBao();
        $biFuBao->setPrivateKey('test');
        $biFuBao->setOptions($options);
        $data = $biFuBao->getVerifyData();

        $this->assertEquals('http://s3.av8dpay.com/bifubao-gateway/front-pay/ebank-pay.htm', $data['post_url']);
        $this->assertEquals('SP20180531144915', $data['params']['MERCHANT_ID']);
        $this->assertEquals('201806280000014748', $data['params']['TRAN_CODE']);
        $this->assertEquals('1', $data['params']['TRAN_AMT']);
        $this->assertEquals('201806280000014748', $data['params']['REMARK']);
        $this->assertEquals('http://fufutest.000webhostapp.com/pay', $data['params']['NO_URL']);
        $this->assertEquals('http://fufutest.000webhostapp.com/pay', $data['params']['RET_URL']);
        $this->assertEquals('20180628114555', $data['params']['SUBMIT_TIME']);
        $this->assertEquals('1001', $data['params']['BANK_ID']);
        $this->assertEquals('1', $data['params']['VERSION']);
        $this->assertEquals('7dece9048109c42a3efa001b12b2d852', $data['params']['SIGNED_MSG']);
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $options = [
            'number' => 'SP20180531144915',
            'paymentVendorId' => '1098',
            'amount' => '0.01',
            'orderId' => '201806280000014748',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'postUrl' => 'http://s3.av8dpay.com/bifubao-gateway/front-pay/',
            'orderCreateDate' => '2018-06-28 11:45:55',
        ];

        $biFuBao = new BiFuBao();
        $biFuBao->setPrivateKey('test');
        $biFuBao->setOptions($options);
        $data = $biFuBao->getVerifyData();

        $this->assertEquals('http://s3.av8dpay.com/bifubao-gateway/front-pay/h5-pay.htm', $data['post_url']);
        $this->assertEquals('SP20180531144915', $data['params']['MERCHANT_ID']);
        $this->assertEquals('201806280000014748', $data['params']['TRAN_CODE']);
        $this->assertEquals('1', $data['params']['TRAN_AMT']);
        $this->assertEquals('201806280000014748', $data['params']['REMARK']);
        $this->assertEquals('http://fufutest.000webhostapp.com/pay', $data['params']['NO_URL']);
        $this->assertEquals('http://fufutest.000webhostapp.com/pay', $data['params']['RET_URL']);
        $this->assertEquals('20180628114555', $data['params']['SUBMIT_TIME']);
        $this->assertEquals('12', $data['params']['TYPE']);
        $this->assertEquals('1', $data['params']['VERSION']);
        $this->assertEquals('4cce88c2292d961b6d14eb2af6b4913b', $data['params']['SIGNED_MSG']);
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

        $biFuBao = new BiFuBao();
        $biFuBao->verifyOrderPayment([]);
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

        $sourceData = [];

        $biFuBao = new BiFuBao();
        $biFuBao->setPrivateKey('test');
        $biFuBao->setOptions($sourceData);
        $biFuBao->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳SIGNED_MSG(加密簽名)
     */
    public function testReturnWithoutSignedMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'MERCHANT_ID' => 'SP20180531144915',
            'PAY_TIME' => '20180628202430',
            'REMARK' => '201806280000014748',
            'STATUS' => '1',
            'SYS_CODE' => 'PA20180628202347SUNYINS2658',
            'TRAN_AMT' => '1',
            'TRAN_CODE' => '201806280000014748',
            'TYPE' => '4',
        ];

        $biFuBao = new BiFuBao();
        $biFuBao->setPrivateKey('test');
        $biFuBao->setOptions($sourceData);
        $biFuBao->verifyOrderPayment([]);
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

        $sourceData = [
            'MERCHANT_ID' => 'SP20180531144915',
            'PAY_TIME' => '20180628202430',
            'REMARK' => '201806280000014748',
            'SIGNED_MSG' => '0f3cdfce3c80be1470461a3c508a9e43',
            'STATUS' => '1',
            'SYS_CODE' => 'PA20180628202347SUNYINS2658',
            'TRAN_AMT' => '1',
            'TRAN_CODE' => '201806280000014748',
            'TYPE' => '4',
        ];

        $biFuBao = new BiFuBao();
        $biFuBao->setPrivateKey('test');
        $biFuBao->setOptions($sourceData);
        $biFuBao->verifyOrderPayment([]);
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

        $sourceData = [
            'MERCHANT_ID' => 'SP20180531144915',
            'PAY_TIME' => '20180628202430',
            'REMARK' => '201806280000014748',
            'SIGNED_MSG' => '2c3cdde9ef74fc195729c08acd904bb1',
            'STATUS' => '2',
            'SYS_CODE' => 'PA20180628202347SUNYINS2658',
            'TRAN_AMT' => '1',
            'TRAN_CODE' => '201806280000014748',
            'TYPE' => '4',
        ];
        $biFuBao = new BiFuBao();
        $biFuBao->setPrivateKey('test');
        $biFuBao->setOptions($sourceData);
        $biFuBao->verifyOrderPayment([]);
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

        $sourceData = [
            'MERCHANT_ID' => 'SP20180531144915',
            'PAY_TIME' => '20180628202430',
            'REMARK' => '201806280000014748',
            'SIGNED_MSG' => 'ec196d36bd1c38318710c8a84089d883',
            'STATUS' => '1',
            'SYS_CODE' => 'PA20180628202347SUNYINS2658',
            'TRAN_AMT' => '1',
            'TRAN_CODE' => '201806280000014748',
            'TYPE' => '4',
        ];

        $entry = ['id' => '201704100000002210'];

        $biFuBao = new BiFuBao();
        $biFuBao->setPrivateKey('test');
        $biFuBao->setOptions($sourceData);
        $biFuBao->verifyOrderPayment($entry);
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

        $sourceData = [
            'MERCHANT_ID' => 'SP20180531144915',
            'PAY_TIME' => '20180628202430',
            'REMARK' => '201806280000014748',
            'SIGNED_MSG' => 'ec196d36bd1c38318710c8a84089d883',
            'STATUS' => '1',
            'SYS_CODE' => 'PA20180628202347SUNYINS2658',
            'TRAN_AMT' => '1',
            'TRAN_CODE' => '201806280000014748',
            'TYPE' => '4',
        ];

        $entry = [
            'id' => '201806280000014748',
            'amount' => '100',
        ];

        $biFuBao = new BiFuBao();
        $biFuBao->setPrivateKey('test');
        $biFuBao->setOptions($sourceData);
        $biFuBao->verifyOrderPayment($entry);
    }

    /**
     * 測試返回成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'MERCHANT_ID' => 'SP20180531144915',
            'PAY_TIME' => '20180628202430',
            'REMARK' => '201806280000014748',
            'SIGNED_MSG' => 'ec196d36bd1c38318710c8a84089d883',
            'STATUS' => '1',
            'SYS_CODE' => 'PA20180628202347SUNYINS2658',
            'TRAN_AMT' => '1',
            'TRAN_CODE' => '201806280000014748',
            'TYPE' => '4',
        ];

        $entry = [
            'id' => '201806280000014748',
            'amount' => '0.01',
        ];

        $biFuBao = new BiFuBao();
        $biFuBao->setPrivateKey('test');
        $biFuBao->setOptions($sourceData);
        $biFuBao->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $biFuBao->getMsg());
    }
}
