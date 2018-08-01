<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\ShouFu;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class ShouFuTest extends DurianTestCase
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
            'number' => '9527',
            'amount' => '1',
            'orderId' => '201804120000046024',
            'paymentVendorId' => '1103',
            'notify_url' => 'http://www.seafood.help/',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.payapi.3vpay.net',
        ];

        $this->returnResult = [
            'sign' => 'A3E680898782705C66F56C7E05D592E7',
            'transDate' => '2018-04-12',
            'transAmount' => '0.01',
            'transTime' => '20:31:53',
            'merchantNumber' => '211110154110001',
            'payWay' => 'qq',
            'transNo' => '201804120000046024',
            'transStatus' => '1',
            'systemno' => '18392432',
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

        $shouFu = new ShouFu();
        $shouFu->getVerifyData();
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

        $shouFu = new ShouFu();
        $shouFu->setPrivateKey('test');
        $shouFu->getVerifyData();
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

        $shouFu = new ShouFu();
        $shouFu->setPrivateKey('test');
        $shouFu->setOptions($this->option);
        $shouFu->getVerifyData();
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

        $shouFu = new ShouFu();
        $shouFu->setPrivateKey('test');
        $shouFu->setOptions($this->option);
        $shouFu->getVerifyData();
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
        $response->addHeader('Content-Type:application/json;charset=GBK');

        $shouFu = new ShouFu();
        $shouFu->setContainer($this->container);
        $shouFu->setClient($this->client);
        $shouFu->setResponse($response);
        $shouFu->setPrivateKey('test');
        $shouFu->setOptions($this->option);
        $shouFu->getVerifyData();
    }

    /**
     * 測試支付時沒有返回respInfo
     */
    public function testPayReturnWithoutRespInfo()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = ['respCode' => '0000'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=GBK');

        $shouFu = new ShouFu();
        $shouFu->setContainer($this->container);
        $shouFu->setClient($this->client);
        $shouFu->setResponse($response);
        $shouFu->setPrivateKey('test');
        $shouFu->setOptions($this->option);
        $shouFu->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '系统异常,其他错误,签名有误',
            180130
        );

        $result = [
            'merchantNumber' => '9527',
            'respCode' => 'A0',
            'respInfo' => '系统异常,其他错误,签名有误',
            'transNo' => '201804120000046024',
        ];

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', json_encode($result, JSON_UNESCAPED_UNICODE)));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=GBK');

        $shouFu = new ShouFu();
        $shouFu->setContainer($this->container);
        $shouFu->setClient($this->client);
        $shouFu->setResponse($response);
        $shouFu->setPrivateKey('test');
        $shouFu->setOptions($this->option);
        $shouFu->getVerifyData();
    }

    /**
     * 測試支付時沒有返回qrcodeUrl
     */
    public function testPayReturnWithoutQrcodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'merchantNumber' => '9527',
            'respCode' => '0000',
            'respInfo' => '成功',
            'systemno' => '18392432',
            'transNo' => '201804120000046024',
        ];

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', json_encode($result, JSON_UNESCAPED_UNICODE)));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=GBK');

        $shouFu = new ShouFu();
        $shouFu->setContainer($this->container);
        $shouFu->setClient($this->client);
        $shouFu->setResponse($response);
        $shouFu->setPrivateKey('test');
        $shouFu->setOptions($this->option);
        $shouFu->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $result = [
            'merchantNumber' => '9527',
            'qrcodeUrl' => 'https://qpay.qq.com/qr/5f60f901',
            'respCode' => '0000',
            'respInfo' => '成功',
            'systemno' => '18392432',
            'transNo' => '201804120000046024',
        ];

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', json_encode($result, JSON_UNESCAPED_UNICODE)));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=GBK');

        $shouFu = new ShouFu();
        $shouFu->setContainer($this->container);
        $shouFu->setClient($this->client);
        $shouFu->setResponse($response);
        $shouFu->setPrivateKey('test');
        $shouFu->setOptions($this->option);
        $data = $shouFu->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://qpay.qq.com/qr/5f60f901', $shouFu->getQrcode());
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $this->option['paymentVendorId'] = '1098';

        $result = [
            'merchantNumber' => '9527',
            'qrcodeUrl' => 'http://api.y8pay.com/jk/pay/wapsyt.html?refno=10041416537',
            'respCode' => '0000',
            'respInfo' => '成功',
            'systemno' => '20020449',
            'transNo' => '201804120000046024',
        ];

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', json_encode($result, JSON_UNESCAPED_UNICODE)));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=GBK');

        $shouFu = new ShouFu();
        $shouFu->setContainer($this->container);
        $shouFu->setClient($this->client);
        $shouFu->setResponse($response);
        $shouFu->setPrivateKey('test');
        $shouFu->setOptions($this->option);
        $data = $shouFu->getVerifyData();

        $this->assertEquals('http://api.y8pay.com/jk/pay/wapsyt.html', $data['post_url']);
        $this->assertEquals('10041416537', $data['params']['refno']);
        $this->assertEquals('GET', $shouFu->getPayMethod());
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

        $shouFu = new ShouFu();
        $shouFu->verifyOrderPayment([]);
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

        $shouFu = new ShouFu();
        $shouFu->setPrivateKey('test');
        $shouFu->verifyOrderPayment([]);
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

        $shouFu = new ShouFu();
        $shouFu->setPrivateKey('test');
        $shouFu->setOptions($this->returnResult);
        $shouFu->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = '0552391A3C718AFDE30FBF01C8F9B197';

        $shouFu = new ShouFu();
        $shouFu->setPrivateKey('test');
        $shouFu->setOptions($this->returnResult);
        $shouFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時訂單未支付
     */
    public function testReturnUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $this->returnResult['transStatus'] = '0';
        $this->returnResult['sign'] = 'A46A45051B05FFA0435F8E473E3256E5';

        $shouFu = new ShouFu();
        $shouFu->setPrivateKey('test');
        $shouFu->setOptions($this->returnResult);
        $shouFu->verifyOrderPayment([]);
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

        $this->returnResult['transStatus'] = 'A0';
        $this->returnResult['sign'] = 'F0199A9ED0731BEF6A5C48DD80A21F1A';

        $shouFu = new ShouFu();
        $shouFu->setPrivateKey('test');
        $shouFu->setOptions($this->returnResult);
        $shouFu->verifyOrderPayment([]);
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

        $shouFu = new ShouFu();
        $shouFu->setPrivateKey('test');
        $shouFu->setOptions($this->returnResult);
        $shouFu->verifyOrderPayment($entry);
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
            'id' => '201804120000046024',
            'amount' => '123',
        ];

        $shouFu = new ShouFu();
        $shouFu->setPrivateKey('test');
        $shouFu->setOptions($this->returnResult);
        $shouFu->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $entry = [
            'id' => '201804120000046024',
            'amount' => '0.01',
        ];

        $shouFu = new ShouFu();
        $shouFu->setPrivateKey('test');
        $shouFu->setOptions($this->returnResult);
        $shouFu->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $shouFu->getMsg());
    }
}
