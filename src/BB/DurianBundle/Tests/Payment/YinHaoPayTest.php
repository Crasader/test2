<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YinHaoPay;
use Buzz\Message\Response;

class YinHaoPayTest extends DurianTestCase
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

        $this->option = [
            'paymentVendorId' => '1',
            'number' => 'A1201810004',
            'notify_url' => 'http://pay.my/pay/reutrn.php',
            'orderId' => '201806050000005338',
            'amount' => '1',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $this->returnResult = [
            'sign' => '8FB2B1EAE776999247A415FBCBB42959',
            'respCode' => '60006',
            'sysPayOrderNo' => '10181528181609567795',
            'assPayOrderNo' => '201806050000005338',
            'assCode' => 'A1201810004',
            'assPayMoney' => '100',
            'respMsg' => '系统支付成功',
            'assPayMessage' => '10181528181609567795',
            'succTime' => '20180605145442',
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

        $yinHaoPay = new YinHaoPay();
        $yinHaoPay->getVerifyData();
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

        $yinHaoPay = new YinHaoPay();
        $yinHaoPay->setPrivateKey('test');
        $yinHaoPay->getVerifyData();
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

        $yinHaoPay = new YinHaoPay();
        $yinHaoPay->setPrivateKey('test');
        $yinHaoPay->setOptions($this->option);
        $yinHaoPay->getVerifyData();
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

        $yinHaoPay = new YinHaoPay();
        $yinHaoPay->setPrivateKey('test');
        $yinHaoPay->setOptions($this->option);
        $yinHaoPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回code
     */
    public function testPayReturnWithoutCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'success' => 'true',
            'message' => '[101]生成网关直连支付支付链接成功',
            'payUrl' => 'http://pay.ufxyz.com/w8/allscore_30511528181609582523.html',
            'assPayOrderNo' => '201806050000005338',
            'sysPayOrderNo' => '10181528181609567795',
            'alertOrderId' => '10181528181609567795',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yinHaoPay = new YinHaoPay();
        $yinHaoPay->setContainer($this->container);
        $yinHaoPay->setClient($this->client);
        $yinHaoPay->setResponse($response);
        $yinHaoPay->setPrivateKey('test');
        $yinHaoPay->setOptions($this->option);
        $yinHaoPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回message
     */
    public function testPayReturnWithoutMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'success' => 'true',
            'code' => '10000',
            'payUrl' => 'http://pay.ufxyz.com/w8/allscore_30511528181609582523.html',
            'assPayOrderNo' => '201806050000005338',
            'sysPayOrderNo' => '10181528181609567795',
            'alertOrderId' => '10181528181609567795',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yinHaoPay = new YinHaoPay();
        $yinHaoPay->setContainer($this->container);
        $yinHaoPay->setClient($this->client);
        $yinHaoPay->setResponse($response);
        $yinHaoPay->setPrivateKey('test');
        $yinHaoPay->setOptions($this->option);
        $yinHaoPay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '[assPayMoney]支付金额小于100分',
            180130
        );

        $result = [
            'success' => 'false',
            'message' => '[assPayMoney]支付金额小于100分',
            'code' => '20001',
            'payUrl' => '',
            'assPayOrderNo' => '0000',
            'sysPayOrderNo' => '0000',
            'alertOrderId' => '0000',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yinHaoPay = new YinHaoPay();
        $yinHaoPay->setContainer($this->container);
        $yinHaoPay->setClient($this->client);
        $yinHaoPay->setResponse($response);
        $yinHaoPay->setPrivateKey('test');
        $yinHaoPay->setOptions($this->option);
        $yinHaoPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回payUrl
     */
    public function testPayReturnWithoutPayUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'success' => 'true',
            'message' => '[101]生成网关直连支付支付链接成功',
            'code' => '10000',
            'assPayOrderNo' => '201806050000005338',
            'sysPayOrderNo' => '10181528181609567795',
            'alertOrderId' => '10181528181609567795',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yinHaoPay = new YinHaoPay();
        $yinHaoPay->setContainer($this->container);
        $yinHaoPay->setClient($this->client);
        $yinHaoPay->setResponse($response);
        $yinHaoPay->setPrivateKey('test');
        $yinHaoPay->setOptions($this->option);
        $yinHaoPay->getVerifyData();
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $this->option['paymentVendorId'] = '1088';

        $result = [
            'success' => 'true',
            'message' => '[101]生成银联H5快捷支付支付链接成功',
            'code' => '10000',
            'payUrl' => 'http://pay.ufxyz.com/w8/allscore_30511528181609582523.html',
            'assPayOrderNo' => '201806050000005338',
            'sysPayOrderNo' => '10181528181609567795',
            'alertOrderId' => '10181528181609567795',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yinHaoPay = new YinHaoPay();
        $yinHaoPay->setContainer($this->container);
        $yinHaoPay->setClient($this->client);
        $yinHaoPay->setResponse($response);
        $yinHaoPay->setPrivateKey('test');
        $yinHaoPay->setOptions($this->option);
        $data = $yinHaoPay->getVerifyData();

        $this->assertEquals('http://pay.ufxyz.com/w8/allscore_30511528181609582523.html', $data['post_url']);
        $this->assertEmpty($data['params']);
        $this->assertEquals('GET', $yinHaoPay->getPayMethod());
    }

    /**
     * 測試支付寶二維支付
     */
    public function testaAliQrcodePay()
    {
        $this->option['paymentVendorId'] = '1092';

        $result = [
            'success' => 'true',
            'message' => '[101]生成支付宝扫码支付支付链接成功',
            'code' => '10000',
            'payUrl' => 'https://qr.alipay.com/bax04739nxfhlpvauh8r200b',
            'assPayOrderNo' => '201806140000005505',
            'sysPayOrderNo' => '10181528959143790683',
            'alertOrderId' => '10181528959143790683',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yinHaoPay = new YinHaoPay();
        $yinHaoPay->setContainer($this->container);
        $yinHaoPay->setClient($this->client);
        $yinHaoPay->setResponse($response);
        $yinHaoPay->setPrivateKey('test');
        $yinHaoPay->setOptions($this->option);
        $data = $yinHaoPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertSame('https://qr.alipay.com/bax04739nxfhlpvauh8r200b', $yinHaoPay->getQrcode());
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $result = [
            'success' => 'true',
            'message' => '[101]生成网关直连支付支付链接成功',
            'code' => '10000',
            'payUrl' => 'http://pay.ufxyz.com/w8/allscore_30511528181609582523.html',
            'assPayOrderNo' => '201806050000005338',
            'sysPayOrderNo' => '10181528181609567795',
            'alertOrderId' => '10181528181609567795',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yinHaoPay = new YinHaoPay();
        $yinHaoPay->setContainer($this->container);
        $yinHaoPay->setClient($this->client);
        $yinHaoPay->setResponse($response);
        $yinHaoPay->setPrivateKey('test');
        $yinHaoPay->setOptions($this->option);
        $data = $yinHaoPay->getVerifyData();

        $this->assertEquals('http://pay.ufxyz.com/w8/allscore_30511528181609582523.html', $data['post_url']);
        $this->assertEmpty($data['params']);
        $this->assertEquals('GET', $yinHaoPay->getPayMethod());
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

        $yinHaoPay = new YinHaoPay();
        $yinHaoPay->verifyOrderPayment([]);
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

        $yinHaoPay = new YinHaoPay();
        $yinHaoPay->setPrivateKey('test');
        $yinHaoPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少簽名
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['sign']);

        $yinHaoPay = new YinHaoPay();
        $yinHaoPay->setPrivateKey('test');
        $yinHaoPay->setOptions($this->returnResult);
        $yinHaoPay->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = '337733969B10450FD1AF127C89BC2540';

        $yinHaoPay = new YinHaoPay();
        $yinHaoPay->setPrivateKey('test');
        $yinHaoPay->setOptions($this->returnResult);
        $yinHaoPay->verifyOrderPayment([]);
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

        $this->returnResult['respCode'] = '60009';
        $this->returnResult['sign'] = 'C5F0C7A065D9EBA157D17C6361AB2D17';

        $yinHaoPay = new YinHaoPay();
        $yinHaoPay->setPrivateKey('test');
        $yinHaoPay->setOptions($this->returnResult);
        $yinHaoPay->verifyOrderPayment([]);
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

        $entry = ['id' => '201806050000005339'];

        $yinHaoPay = new YinHaoPay();
        $yinHaoPay->setPrivateKey('test');
        $yinHaoPay->setOptions($this->returnResult);
        $yinHaoPay->verifyOrderPayment($entry);
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
            'id' => '201806050000005338',
            'amount' => '100',
        ];

        $yinHaoPay = new YinHaoPay();
        $yinHaoPay->setPrivateKey('test');
        $yinHaoPay->setOptions($this->returnResult);
        $yinHaoPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $entry = [
            'id' => '201806050000005338',
            'amount' => '1',
        ];

        $yinHaoPay = new YinHaoPay();
        $yinHaoPay->setPrivateKey('test');
        $yinHaoPay->setOptions($this->returnResult);
        $yinHaoPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $yinHaoPay->getMsg());
    }
}