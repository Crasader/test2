<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YiHsunJie;
use Buzz\Message\Response;

class YiHsunJieTest extends DurianTestCase
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
            'paymentVendorId' => '1111',
            'number' => 'mt1529150654816',
            'notify_url' => 'http://pay.my/pay/reutrn.php',
            'orderId' => '201806230000005707',
            'amount' => '1',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $this->returnResult = [
            'retCode' => '0',
            'userId' => 'mt1529150654816',
            'orderNo' => '201806230000005707',
            'transNo' => '15297228939502535371891',
            'payAmt' => '1.00',
            'goodsDesc' => '201806230000005707',
            'sign' => '15c6800277e7d4e57f3a275d2bb3e29c',
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

        $yiHsunJie = new YiHsunJie();
        $yiHsunJie->getVerifyData();
    }

    /**
     * 測試支付時沒指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $yiHsunJie = new YiHsunJie();
        $yiHsunJie->setPrivateKey('test');
        $yiHsunJie->getVerifyData();
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

        $yiHsunJie = new YiHsunJie();
        $yiHsunJie->setPrivateKey('test');
        $yiHsunJie->setOptions($this->option);
        $yiHsunJie->getVerifyData();
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

        $yiHsunJie = new YiHsunJie();
        $yiHsunJie->setPrivateKey('test');
        $yiHsunJie->setOptions($this->option);
        $yiHsunJie->getVerifyData();
    }

    /**
     * 測試支付時沒有返回retCode
     */
    public function testPayReturnWithoutRetCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'orderNo' => '201806230000005702',
            'sign' => '86cc820b087c52b5f13d54c7e4b21cbe',
            'payUrl' => 'https://qr.95516.com/00010000/62422961177289261805819032028872',
            'transNo' => '15297218934519891859423',
            'userId' => 'mt1529150654816',
            'retMsg' => 'success',
            'tradeType' => '7',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yiHsunJie = new YiHsunJie();
        $yiHsunJie->setContainer($this->container);
        $yiHsunJie->setClient($this->client);
        $yiHsunJie->setResponse($response);
        $yiHsunJie->setPrivateKey('test');
        $yiHsunJie->setOptions($this->option);
        $yiHsunJie->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '查无转发通道！',
            180130
        );

        $result = [
            'orderNo' => '',
            'sign' => 'a554990f5039022c57b028bf3f5e8e8b',
            'payUrl' => '',
            'retCode' => 1000,
            'transNo' => '',
            'userId' => '',
            'retMsg' => '查无转发通道！',
            'tradeType' => '',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yiHsunJie = new YiHsunJie();
        $yiHsunJie->setContainer($this->container);
        $yiHsunJie->setClient($this->client);
        $yiHsunJie->setResponse($response);
        $yiHsunJie->setPrivateKey('test');
        $yiHsunJie->setOptions($this->option);
        $yiHsunJie->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗且未返回retMsg
     */
    public function testPayReturnNotSuccessAndNoReturnRetMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $result = [
            'orderNo' => '',
            'sign' => 'a554990f5039022c57b028bf3f5e8e8b',
            'payUrl' => '',
            'retCode' => 1000,
            'transNo' => '',
            'userId' => '',
            'tradeType' => '',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yiHsunJie = new YiHsunJie();
        $yiHsunJie->setContainer($this->container);
        $yiHsunJie->setClient($this->client);
        $yiHsunJie->setResponse($response);
        $yiHsunJie->setPrivateKey('test');
        $yiHsunJie->setOptions($this->option);
        $yiHsunJie->getVerifyData();
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
            'orderNo' => '201806230000005702',
            'sign' => '86cc820b087c52b5f13d54c7e4b21cbe',
            'retCode' => 0,
            'transNo' => '15297218934519891859423',
            'userId' => 'mt1529150654816',
            'retMsg' => 'success',
            'tradeType' => '7',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yiHsunJie = new YiHsunJie();
        $yiHsunJie->setContainer($this->container);
        $yiHsunJie->setClient($this->client);
        $yiHsunJie->setResponse($response);
        $yiHsunJie->setPrivateKey('test');
        $yiHsunJie->setOptions($this->option);
        $yiHsunJie->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $result = [
            'orderNo' => '201806230000005702',
            'sign' => '86cc820b087c52b5f13d54c7e4b21cbe',
            'payUrl' => 'https://qr.95516.com/00010000/62422961177289261805819032028872',
            'retCode' => 0,
            'transNo' => '15297218934519891859423',
            'userId' => 'mt1529150654816',
            'retMsg' => 'success',
            'tradeType' => '7',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yiHsunJie = new YiHsunJie();
        $yiHsunJie->setContainer($this->container);
        $yiHsunJie->setClient($this->client);
        $yiHsunJie->setResponse($response);
        $yiHsunJie->setPrivateKey('test');
        $yiHsunJie->setOptions($this->option);
        $data = $yiHsunJie->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://qr.95516.com/00010000/62422961177289261805819032028872', $yiHsunJie->getQrcode());
    }

    /**
     * 測試手機支付未返回action
     */
    public function testPhonePayContentWihtoutAction()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $this->option['paymentVendorId'] = '1088';

        $result = [
            'orderNo' => '201806230000005704',
            'sign' => 'dfdbcc8200b48764aad426d56e587c26',
            'payUrl' => '<form name="formdata" method="post">',
            'retCode' => 0,
            'transNo' => '15297225302373565748621',
            'userId' => 'mt1529150654816',
            'retMsg' => 'success',
            'tradeType' => '5',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yiHsunJie = new YiHsunJie();
        $yiHsunJie->setContainer($this->container);
        $yiHsunJie->setClient($this->client);
        $yiHsunJie->setResponse($response);
        $yiHsunJie->setPrivateKey('test');
        $yiHsunJie->setOptions($this->option);
        $yiHsunJie->getVerifyData();
    }

    /**
     * 測試網銀支付未返回input元素
     */
    public function testPayReturnWithoutInput()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $this->option['paymentVendorId'] = '1';

        $result = [
            'orderNo' => '201806230000005699',
            'sign' => '98513d606aa1102cfed0464e6244e7a5',
            'payUrl' => '<form name="punchout_form" method="post" action="https://c.heepay.com/quick/pc/index.do">' .
                '<script type="text/javascript">document.getElementById("sform").submit();</script>',
            'transNo' => '15281007905563353757683',
            'retCode' => 0,
            'userId' => 'mt1529150654816',
            'retMsg' => 'success',
            'tradeType' => '41',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yiHsunJie = new YiHsunJie();
        $yiHsunJie->setContainer($this->container);
        $yiHsunJie->setClient($this->client);
        $yiHsunJie->setResponse($response);
        $yiHsunJie->setPrivateKey('test');
        $yiHsunJie->setOptions($this->option);
        $yiHsunJie->getVerifyData();
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $this->option['paymentVendorId'] = '1088';

        $result = [
            'orderNo' => '201806230000005704',
            'sign' => 'dfdbcc8200b48764aad426d56e587c26',
            'payUrl' => '<form name="formdata" action="https://c.heepay.com/newOnlineBank/paymentUnion.do" method="post">' .
                '<input type="hidden" name="merchantId" value="100741"/>' .
                '<input type="hidden" name="merchantOrderNo" value="15297225302373565748621"/>' .
                '<input type="hidden" name="merchantUserId" value="易讯捷"/>' .
                '<input type="hidden" name="payAmount" value="1"/>' .
                '<input type="hidden" name="notifyUrl" value="http://47.75.187.6/hfbnotifyurl_4.do"/>' .
                '<input type="hidden" name="callBackUrl" value="https://tingliu.000webhostapp.com/pay/return.php"/>' .
                '<input type="hidden" name="description" value="201806230000005704"/>' .
                '<input type="hidden" name="productCode" value="HY_B2CUNIONWAP"/>' .
                '<input type="hidden" name="sign" value="324492899733fd5a9290b4c81f540777"/>' .
                '</form><script>document.forms["formdata"].submit();</script>',
            'retCode' => 0,
            'transNo' => '15297225302373565748621',
            'userId' => 'mt1529150654816',
            'retMsg' => 'success',
            'tradeType' => '5',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yiHsunJie = new YiHsunJie();
        $yiHsunJie->setContainer($this->container);
        $yiHsunJie->setClient($this->client);
        $yiHsunJie->setResponse($response);
        $yiHsunJie->setPrivateKey('test');
        $yiHsunJie->setOptions($this->option);
        $data = $yiHsunJie->getVerifyData();

        $this->assertEquals('https://c.heepay.com/newOnlineBank/paymentUnion.do', $data['post_url']);
        $this->assertEquals('100741', $data['params']['merchantId']);
        $this->assertEquals('15297225302373565748621', $data['params']['merchantOrderNo']);
        $this->assertEquals('易讯捷', $data['params']['merchantUserId']);
        $this->assertEquals('1', $data['params']['payAmount']);
        $this->assertEquals('http://47.75.187.6/hfbnotifyurl_4.do', $data['params']['notifyUrl']);
        $this->assertEquals('https://tingliu.000webhostapp.com/pay/return.php', $data['params']['callBackUrl']);
        $this->assertEquals('201806230000005704', $data['params']['description']);
        $this->assertEquals('HY_B2CUNIONWAP', $data['params']['productCode']);
        $this->assertEquals('324492899733fd5a9290b4c81f540777', $data['params']['sign']);
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

        $yiHsunJie = new YiHsunJie();
        $yiHsunJie->verifyOrderPayment([]);
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

        $yiHsunJie = new YiHsunJie();
        $yiHsunJie->setPrivateKey('test');
        $yiHsunJie->verifyOrderPayment([]);
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

        $yiHsunJie = new YiHsunJie();
        $yiHsunJie->setPrivateKey('test');
        $yiHsunJie->setOptions($this->returnResult);
        $yiHsunJie->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = 'e9976eabd0c6403d69b46b397e981766';

        $yiHsunJie = new YiHsunJie();
        $yiHsunJie->setPrivateKey('test');
        $yiHsunJie->setOptions($this->returnResult);
        $yiHsunJie->verifyOrderPayment([]);
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

        $this->returnResult['retCode'] = '1';
        $this->returnResult['sign'] = 'c8d77ee7a044e37ecb8af5cca9e49f6e';

        $yiHsunJie = new YiHsunJie();
        $yiHsunJie->setPrivateKey('test');
        $yiHsunJie->setOptions($this->returnResult);
        $yiHsunJie->verifyOrderPayment([]);
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

        $entry = ['id' => '201806230000005708'];

        $yiHsunJie = new YiHsunJie();
        $yiHsunJie->setPrivateKey('test');
        $yiHsunJie->setOptions($this->returnResult);
        $yiHsunJie->verifyOrderPayment($entry);
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
            'id' => '201806230000005707',
            'amount' => '100',
        ];

        $yiHsunJie = new YiHsunJie();
        $yiHsunJie->setPrivateKey('test');
        $yiHsunJie->setOptions($this->returnResult);
        $yiHsunJie->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $entry = [
            'id' => '201806230000005707',
            'amount' => '1',
        ];

        $yiHsunJie = new YiHsunJie();
        $yiHsunJie->setPrivateKey('test');
        $yiHsunJie->setOptions($this->returnResult);
        $yiHsunJie->verifyOrderPayment($entry);

        $this->assertEquals('success', $yiHsunJie->getMsg());
    }
}