<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\HuiFuShihTai;
use Buzz\Message\Response;

class HuiFuShihTaiTest extends DurianTestCase
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

        $huiFuShihTai = new HuiFuShihTai();
        $huiFuShihTai->getVerifyData();
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

        $huiFuShihTai = new HuiFuShihTai();
        $huiFuShihTai->setPrivateKey('test');
        $huiFuShihTai->setOptions($sourceData);
        $huiFuShihTai->getVerifyData();
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
            'number' => 'mt1524818980855',
            'paymentVendorId' => '9999',
            'amount' => '1',
            'orderId' => '201806040000013560',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
        ];

        $huiFuShihTai = new HuiFuShihTai();
        $huiFuShihTai->setPrivateKey('test');
        $huiFuShihTai->setOptions($sourceData);
        $huiFuShihTai->getVerifyData();
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
            'number' => 'mt1524818980855',
            'paymentVendorId' => '1103',
            'amount' => '1',
            'orderId' => '201806040000013560',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => '',
        ];

        $huiFuShihTai = new HuiFuShihTai();
        $huiFuShihTai->setPrivateKey('test');
        $huiFuShihTai->setOptions($sourceData);
        $huiFuShihTai->getVerifyData();
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

        $result = [
            'orderNo' => '201806040000013560',
            'sign' => 'b7f89066ab18dad12846fb991b6c5409',
            'payUrl' => 'https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027&_bid=2183&t=6V9',
            'transNo' => '15281007905563353757683',
            'userId' => 'mt1524818980855',
            'retMsg' => 'success',
            'tradeType' => '21',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'mt1524818980855',
            'paymentVendorId' => '1103',
            'amount' => '1',
            'orderId' => '201806040000013560',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $huiFuShihTai = new HuiFuShihTai();
        $huiFuShihTai->setContainer($this->container);
        $huiFuShihTai->setClient($this->client);
        $huiFuShihTai->setResponse($response);
        $huiFuShihTai->setPrivateKey('test');
        $huiFuShihTai->setOptions($sourceData);
        $huiFuShihTai->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '验证失败！',
            180130
        );

        $result = [
            'orderNo' => '201806040000013560',
            'sign' => 'b7f89066ab18dad12846fb991b6c5409',
            'payUrl' => 'https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027&_bid=2183&t=6V9',
            'transNo' => '15281007905563353757683',
            'retCode' => '10001',
            'userId' => 'mt1524818980855',
            'retMsg' => '验证失败！',
            'tradeType' => '21',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'mt1524818980855',
            'paymentVendorId' => '1103',
            'amount' => '1',
            'orderId' => '201806040000013560',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $huiFuShihTai = new HuiFuShihTai();
        $huiFuShihTai->setContainer($this->container);
        $huiFuShihTai->setClient($this->client);
        $huiFuShihTai->setResponse($response);
        $huiFuShihTai->setPrivateKey('test');
        $huiFuShihTai->setOptions($sourceData);
        $huiFuShihTai->getVerifyData();
    }

    /**
     * 測試支付時返回沒有retMsg
     */
    public function testPayReturnWithoutRetMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $result = [
            'orderNo' => '201806040000013560',
            'sign' => 'b7f89066ab18dad12846fb991b6c5409',
            'payUrl' => 'https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027&_bid=2183&t=6V9',
            'transNo' => '15281007905563353757683',
            'retCode' => '10001',
            'userId' => 'mt1524818980855',
            'tradeType' => '21',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'mt1524818980855',
            'paymentVendorId' => '1103',
            'amount' => '1',
            'orderId' => '201806040000013560',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $huiFuShihTai = new HuiFuShihTai();
        $huiFuShihTai->setContainer($this->container);
        $huiFuShihTai->setClient($this->client);
        $huiFuShihTai->setResponse($response);
        $huiFuShihTai->setPrivateKey('test');
        $huiFuShihTai->setOptions($sourceData);
        $huiFuShihTai->getVerifyData();
    }

    /**
     * 測試支付時未返回payUrl
     */
    public function testPayNoReturnPayUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'orderNo' => '201806040000013560',
            'sign' => 'b7f89066ab18dad12846fb991b6c5409',
            'transNo' => '15281007905563353757683',
            'retCode' => 0,
            'userId' => 'mt1524818980855',
            'retMsg' => 'success',
            'tradeType' => '21',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'mt1524818980855',
            'paymentVendorId' => '1103',
            'amount' => '1',
            'orderId' => '201806040000013560',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $huiFuShihTai = new HuiFuShihTai();
        $huiFuShihTai->setContainer($this->container);
        $huiFuShihTai->setClient($this->client);
        $huiFuShihTai->setResponse($response);
        $huiFuShihTai->setPrivateKey('test');
        $huiFuShihTai->setOptions($sourceData);
        $huiFuShihTai->getVerifyData();
    }

    /**
     * 測試掃碼支付
     */
    public function testQrcodePay()
    {
        $result = [
            'orderNo' => '201806040000013560',
            'sign' => 'b7f89066ab18dad12846fb991b6c5409',
            'payUrl' => 'https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027&_bid=21',
            'transNo' => '15281007905563353757683',
            'retCode' => 0,
            'userId' => 'mt1524818980855',
            'retMsg' => 'success',
            'tradeType' => '21',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'mt1524818980855',
            'paymentVendorId' => '1103',
            'amount' => '1',
            'orderId' => '201806040000013560',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $huiFuShihTai = new HuiFuShihTai();
        $huiFuShihTai->setContainer($this->container);
        $huiFuShihTai->setClient($this->client);
        $huiFuShihTai->setResponse($response);
        $huiFuShihTai->setPrivateKey('test');
        $huiFuShihTai->setOptions($sourceData);
        $data = $huiFuShihTai->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027&_bid=21', $huiFuShihTai->getQrcode());
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

        $result = [
            'orderNo' => '201806040000013560',
            'sign' => 'b7f89066ab18dad12846fb991b6c5409',
            'payUrl' => '<form name="formdata">',
            'transNo' => '15281007905563353757683',
            'retCode' => 0,
            'userId' => 'mt1524818980855',
            'retMsg' => 'success',
            'tradeType' => '21',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'mt1524818980855',
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201806040000013560',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $huiFuShihTai = new HuiFuShihTai();
        $huiFuShihTai->setContainer($this->container);
        $huiFuShihTai->setClient($this->client);
        $huiFuShihTai->setResponse($response);
        $huiFuShihTai->setPrivateKey('test');
        $huiFuShihTai->setOptions($sourceData);
        $data = $huiFuShihTai->getVerifyData();
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

        $content = '<form name="punchout_form" method="post" action="https://c.heepay.com/quick/pc/index.do">' .
            '<script type="text/javascript">document.getElementById("sform").submit();</script>';

        $result = [
            'orderNo' => '201806040000013560',
            'sign' => 'b7f89066ab18dad12846fb991b6c5409',
            'payUrl' => $content,
            'transNo' => '15281007905563353757683',
            'retCode' => 0,
            'userId' => 'mt1524818980855',
            'retMsg' => 'success',
            'tradeType' => '21',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'mt1524818980855',
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201806040000013560',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $huiFuShihTai = new HuiFuShihTai();
        $huiFuShihTai->setContainer($this->container);
        $huiFuShihTai->setClient($this->client);
        $huiFuShihTai->setResponse($response);
        $huiFuShihTai->setPrivateKey('test');
        $huiFuShihTai->setOptions($sourceData);
        $huiFuShihTai->getVerifyData();
    }

    /**
     * 測試網銀支付
     */
    public function testBankPay()
    {
        $content = '<form name="punchout_form" method="post" action="https://c.heepay.com/quick/pc/index.do">' .
            '<input type="hidden" name="biz_content" value="123"/>' .
            '</form>' .
            '<script type="text/javascript">document.getElementById("sform").submit();</script>';

        $result = [
            'orderNo' => '201806040000013560',
            'sign' => 'b7f89066ab18dad12846fb991b6c5409',
            'payUrl' => $content,
            'transNo' => '15281007905563353757683',
            'retCode' => 0,
            'userId' => 'mt1524818980855',
            'retMsg' => 'success',
            'tradeType' => '21',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'mt1524818980855',
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201806040000013560',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $huiFuShihTai = new HuiFuShihTai();
        $huiFuShihTai->setContainer($this->container);
        $huiFuShihTai->setClient($this->client);
        $huiFuShihTai->setResponse($response);
        $huiFuShihTai->setPrivateKey('test');
        $huiFuShihTai->setOptions($sourceData);
        $data = $huiFuShihTai->getVerifyData();

        $this->assertEquals('https://c.heepay.com/quick/pc/index.do', $data['post_url']);
        $this->assertEquals('123', $data['params']['biz_content']);
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

        $huiFuShihTai = new HuiFuShihTai();
        $huiFuShihTai->verifyOrderPayment([]);
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

        $huiFuShihTai = new HuiFuShihTai();
        $huiFuShihTai->setPrivateKey('test');
        $huiFuShihTai->setOptions($sourceData);
        $huiFuShihTai->verifyOrderPayment([]);
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
            'retCode' => '0',
            'userId' => 'mt1524818980855',
            'orderNo' => '201806040000013560',
            'transNo' => '15281007905563353757683',
            'payAmt' => '1.00',
            'goodsDesc' => '201806040000013560',
        ];

        $huiFuShihTai = new HuiFuShihTai();
        $huiFuShihTai->setPrivateKey('test');
        $huiFuShihTai->setOptions($sourceData);
        $huiFuShihTai->verifyOrderPayment([]);
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
            'retCode' => '0',
            'userId' => 'mt1524818980855',
            'orderNo' => '201806040000013560',
            'transNo' => '15281007905563353757683',
            'payAmt' => '1.00',
            'goodsDesc' => '201806040000013560',
            'sign' => '61702b954bb270138e56265b8095f153',
        ];

        $huiFuShihTai = new HuiFuShihTai();
        $huiFuShihTai->setPrivateKey('test');
        $huiFuShihTai->setOptions($sourceData);
        $huiFuShihTai->verifyOrderPayment([]);
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
            'retCode' => '1',
            'userId' => 'mt1524818980855',
            'orderNo' => '201806040000013560',
            'transNo' => '15281007905563353757683',
            'payAmt' => '1.00',
            'goodsDesc' => '201806040000013560',
            'sign' => '45b22bcc1dd275b6554571d47aaa8f09',
        ];

        $huiFuShihTai = new HuiFuShihTai();
        $huiFuShihTai->setPrivateKey('test');
        $huiFuShihTai->setOptions($sourceData);
        $huiFuShihTai->verifyOrderPayment([]);
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
            'retCode' => '0',
            'userId' => 'mt1524818980855',
            'orderNo' => '201806040000013560',
            'transNo' => '15281007905563353757683',
            'payAmt' => '1.00',
            'goodsDesc' => '201806040000013560',
            'sign' => 'ee0f76aec8de7a06b47aa7f25448e6c5',
        ];

        $entry = ['id' => '201704100000002210'];

        $huiFuShihTai = new HuiFuShihTai();
        $huiFuShihTai->setPrivateKey('test');
        $huiFuShihTai->setOptions($sourceData);
        $huiFuShihTai->verifyOrderPayment($entry);
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
            'retCode' => '0',
            'userId' => 'mt1524818980855',
            'orderNo' => '201806040000013560',
            'transNo' => '15281007905563353757683',
            'payAmt' => '1.00',
            'goodsDesc' => '201806040000013560',
            'sign' => 'ee0f76aec8de7a06b47aa7f25448e6c5',
        ];

        $entry = [
            'id' => '201806040000013560',
            'amount' => '100',
        ];

        $huiFuShihTai = new HuiFuShihTai();
        $huiFuShihTai->setPrivateKey('test');
        $huiFuShihTai->setOptions($sourceData);
        $huiFuShihTai->verifyOrderPayment($entry);
    }

    /**
     * 測試返回成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'retCode' => '0',
            'userId' => 'mt1524818980855',
            'orderNo' => '201806040000013560',
            'transNo' => '15281007905563353757683',
            'payAmt' => '1.00',
            'goodsDesc' => '201806040000013560',
            'sign' => 'ee0f76aec8de7a06b47aa7f25448e6c5',
        ];

        $entry = [
            'id' => '201806040000013560',
            'amount' => '1.00',
        ];

        $huiFuShihTai = new HuiFuShihTai();
        $huiFuShihTai->setPrivateKey('test');
        $huiFuShihTai->setOptions($sourceData);
        $huiFuShihTai->verifyOrderPayment($entry);

        $this->assertEquals('success', $huiFuShihTai->getMsg());
    }
}
