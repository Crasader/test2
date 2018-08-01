<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\Pay686;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class Pay686Test extends DurianTestCase
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
            'notify_url' => 'http://www.seafood.help/',
            'orderId' => '201805080000046207',
            'amount' => '20',
            'number' => '9527',
            'paymentVendorId' => '1098',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.47.107.21.70',
        ];

        $this->returnResult = [
            'shopCode' => '686cz0605',
            'outOrderNo' => '201805080000046207',
            'goodsClauses' => '201805080000046207',
            'tradeAmount' => '20.00',
            'code' => '0',
            'nonStr' => 'NkhgFal8Mxw6QHok',
            'msg' => 'SUCCESS',
            'sign' => 'adba0afd2ded47d117040e10dba443be',
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

        $pay686 = new Pay686();
        $pay686->getVerifyData();
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

        $pay686 = new Pay686();
        $pay686->setPrivateKey('test');
        $pay686->getVerifyData();
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

        $pay686 = new Pay686();
        $pay686->setPrivateKey('test');
        $pay686->setOptions($this->option);
        $pay686->getVerifyData();
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

        $pay686 = new Pay686();
        $pay686->setPrivateKey('test');
        $pay686->setOptions($this->option);
        $pay686->getVerifyData();
    }

    /**
     * 測試支付時沒有返回payState
     */
    public function testPayReturnWithoutPayState()
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

        $pay686 = new Pay686();
        $pay686->setContainer($this->container);
        $pay686->setClient($this->client);
        $pay686->setResponse($response);
        $pay686->setPrivateKey('test');
        $pay686->setOptions($this->option);
        $pay686->getVerifyData();
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
            'code' => '',
            'payCode' => 'alipay',
            'outOrderNo' => '',
            'goodsClauses' => '201805080000046207',
            'tradeAmount' => null,
            'notifyUrl' => 'http://www.seafood.help/',
            'payState' => 'fail',
            'message' => '订单匹配不足',
            'url' => '',
            'content' => '',
            'payWay' => 'ALIPAY',
            'shopCode' => '686cz0605',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $pay686 = new Pay686();
        $pay686->setContainer($this->container);
        $pay686->setClient($this->client);
        $pay686->setResponse($response);
        $pay686->setPrivateKey('test');
        $pay686->setOptions($this->option);
        $pay686->getVerifyData();
    }

    /**
     * 測試支付時沒有返回url
     */
    public function testPayReturnWithoutUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'code' => '',
            'payCode' => 'alipay',
            'outOrderNo' => '201805080000046207',
            'goodsClauses' => '201805080000046207',
            'tradeAmount' => '20.00',
            'notifyUrl' => 'http://www.seafood.help/',
            'payState' => 'success',
            'message' => '',
            'content' => '',
            'payWay' => 'ALIPAY',
            'shopCode' => '686cz0605',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $pay686 = new Pay686();
        $pay686->setContainer($this->container);
        $pay686->setClient($this->client);
        $pay686->setResponse($response);
        $pay686->setPrivateKey('test');
        $pay686->setOptions($this->option);
        $pay686->getVerifyData();
    }

    /**
     * 測試微信條碼支付時返回缺少跳轉網址
     */
    public function testPayReturnWithoutHref()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '<script  type="text/javascript"></script>';

        $this->option = [
            'notify_url' => 'http://www.seafood.help/',
            'orderId' => '201805180000011770',
            'amount' => '20',
            'number' => '9527',
            'paymentVendorId' => '1115',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.47.107.21.70',
        ];

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $pay686 = new Pay686();
        $pay686->setPrivateKey('test');
        $pay686->setContainer($this->container);
        $pay686->setClient($this->client);
        $pay686->setResponse($response);
        $pay686->setOptions($this->option);
        $pay686->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $this->option['paymentVendorId'] = '1092';

        $result = [
            'code' => '',
            'payCode' => 'alipay',
            'outOrderNo' => '201805080000046207',
            'goodsClauses' => '201805080000046207',
            'tradeAmount' => '20.00',
            'notifyUrl' => 'http://www.seafood.help/',
            'payState' => 'success',
            'message' => '',
            'url' => 'https://qr.alipay.com/upx01452n1rg7yprhlbf208c',
            'content' => '',
            'payWay' => 'ALIPAY',
            'shopCode' => '686cz0605',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $pay686 = new Pay686();
        $pay686->setContainer($this->container);
        $pay686->setClient($this->client);
        $pay686->setResponse($response);
        $pay686->setPrivateKey('test');
        $pay686->setOptions($this->option);
        $data = $pay686->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://qr.alipay.com/upx01452n1rg7yprhlbf208c', $pay686->getQrcode());
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $result = [
            'code' => '',
            'payCode' => 'alipay',
            'outOrderNo' => '201805080000046207',
            'goodsClauses' => '201805080000046207',
            'tradeAmount' => '20.00',
            'notifyUrl' => 'http://www.seafood.help/',
            'payState' => 'success',
            'message' => '',
            'url' => 'https://qr.alipay.com/upx04068kaui6v2hbu5860f3',
            'content' => '',
            'payWay' => 'ALIPAY',
            'shopCode' => '686cz0605',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $pay686 = new Pay686();
        $pay686->setContainer($this->container);
        $pay686->setClient($this->client);
        $pay686->setResponse($response);
        $pay686->setPrivateKey('test');
        $pay686->setOptions($this->option);
        $data = $pay686->getVerifyData();

        $this->assertEquals('https://qr.alipay.com/upx04068kaui6v2hbu5860f3', $data['post_url']);
        $this->assertEmpty($data['params']);
    }

    /**
     * 測試微信條碼
     */
    public function testBarCodePay()
    {
        $result = '<script  type="text/javascript">window.location.href="' .
            'http://47.107.21.70/index.php/686cz/trade/wxtz?notifyUrl=http://pay.simu/pay/return.php&' .
            'sign=e0892173f0c31da6dafe9cf78afbf2e8&outOrderNo=201805180000011770&goodsClauses=201805180000011770&' .
            'tradeAmount=10.00&code=686cz0605&payCode=2"</script>';

        $this->option = [
            'notify_url' => 'http://www.seafood.help/',
            'orderId' => '201805180000011770',
            'amount' => '20',
            'number' => '9527',
            'paymentVendorId' => '1115',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.47.107.21.70',
        ];

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $pay686 = new Pay686();
        $pay686->setPrivateKey('test');
        $pay686->setContainer($this->container);
        $pay686->setClient($this->client);
        $pay686->setResponse($response);
        $pay686->setOptions($this->option);
        $verifyData = $pay686->getVerifyData();

        $this->assertEquals('GET', $pay686->getPayMethod());
        $this->assertEquals('http://pay.simu/pay/return.php', $verifyData['params']['notifyUrl']);
        $this->assertEquals('e0892173f0c31da6dafe9cf78afbf2e8', $verifyData['params']['sign']);
        $this->assertEquals('201805180000011770', $verifyData['params']['outOrderNo']);
        $this->assertEquals('201805180000011770', $verifyData['params']['goodsClauses']);
        $this->assertEquals('10.00', $verifyData['params']['tradeAmount']);
        $this->assertEquals('686cz0605', $verifyData['params']['code']);
        $this->assertEquals('2', $verifyData['params']['payCode']);
        $this->assertEquals('http://47.107.21.70/index.php/686cz/trade/wxtz', $verifyData['post_url']);
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

        $pay686 = new Pay686();
        $pay686->verifyOrderPayment([]);
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

        $pay686 = new Pay686();
        $pay686->setPrivateKey('test');
        $pay686->verifyOrderPayment([]);
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

        unset($this->returnResult['sign']);

        $pay686 = new Pay686();
        $pay686->setPrivateKey('test');
        $pay686->setOptions($this->returnResult);
        $pay686->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = '29d1a570eabaab054a9aaebe246ba69a';

        $pay686 = new Pay686();
        $pay686->setPrivateKey('test');
        $pay686->setOptions($this->returnResult);
        $pay686->verifyOrderPayment([]);
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

        $this->returnResult['code'] = '-1';
        $this->returnResult['sign'] = '212b9e2bcfb09b9ae35c0cf99bd0b428';

        $pay686 = new Pay686();
        $pay686->setPrivateKey('test');
        $pay686->setOptions($this->returnResult);
        $pay686->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果msg為FAIL
     */
    public function testReturnButMsgIsFail()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $this->returnResult['msg'] = 'FAIL';
        $this->returnResult['sign'] = '779c796ad68a28b4ba67c3288eb8b157';

        $pay686 = new Pay686();
        $pay686->setPrivateKey('test');
        $pay686->setOptions($this->returnResult);
        $pay686->verifyOrderPayment([]);
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

        $pay686 = new Pay686();
        $pay686->setPrivateKey('test');
        $pay686->setOptions($this->returnResult);
        $pay686->verifyOrderPayment($entry);
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
            'id' => '201805080000046207',
            'amount' => '123',
        ];

        $pay686 = new Pay686();
        $pay686->setPrivateKey('test');
        $pay686->setOptions($this->returnResult);
        $pay686->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $entry = [
            'id' => '201805080000046207',
            'amount' => '20',
        ];

        $pay686 = new Pay686();
        $pay686->setPrivateKey('test');
        $pay686->setOptions($this->returnResult);
        $pay686->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $pay686->getMsg());
    }
}
