<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\Pay3K2;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class Pay3K2Test extends DurianTestCase
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
            'orderId' => '201803300000045931',
            'orderCreateDate' => '2018-04-02 10:25:13',
            'amount' => '1',
            'paymentVendorId' => '1098',
            'notify_url' => 'http://www.seafood.help/',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.payapi.3vpay.net',
        ];

        $this->returnResult = [
            'channelOrderId' => '201803300000045931',
            'orderId' => '1803301908266010594',
            'timeStamp' => '1522404086',
            'totalFee' => '100',
            'attach' => 'null',
            'sign' => '50176E2C003A01DAA3B7CAFA90DFE75B',
            'transactionId' => '41946773891055616',
            'return_code' => '0000',
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

        $pay3K2 = new Pay3K2();
        $pay3K2->getVerifyData();
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

        $pay3K2 = new Pay3K2();
        $pay3K2->setPrivateKey('test');
        $pay3K2->getVerifyData();
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

        $pay3K2 = new Pay3K2();
        $pay3K2->setPrivateKey('test');
        $pay3K2->setOptions($this->option);
        $pay3K2->getVerifyData();
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

        $pay3K2 = new Pay3K2();
        $pay3K2->setPrivateKey('test');
        $pay3K2->setOptions($this->option);
        $pay3K2->getVerifyData();
    }

    /**
     * 測試支付時沒有返回return_code
     */
    public function testPayReturnWithoutReturnCode()
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
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $pay3K2 = new Pay3K2();
        $pay3K2->setContainer($this->container);
        $pay3K2->setClient($this->client);
        $pay3K2->setResponse($response);
        $pay3K2->setPrivateKey('test');
        $pay3K2->setOptions($this->option);
        $pay3K2->getVerifyData();
    }

    /**
     * 測試支付時沒有返回return_msg
     */
    public function testPayReturnWithoutReturnMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = ['return_code' => '0001'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $pay3K2 = new Pay3K2();
        $pay3K2->setContainer($this->container);
        $pay3K2->setClient($this->client);
        $pay3K2->setResponse($response);
        $pay3K2->setPrivateKey('test');
        $pay3K2->setOptions($this->option);
        $pay3K2->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '商户已关闭',
            180130
        );

        $result = [
            'return_msg' => '商户已关闭',
            'return_code' => '1002',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $pay3K2 = new Pay3K2();
        $pay3K2->setContainer($this->container);
        $pay3K2->setClient($this->client);
        $pay3K2->setResponse($response);
        $pay3K2->setPrivateKey('test');
        $pay3K2->setOptions($this->option);
        $pay3K2->getVerifyData();
    }

    /**
     * 測試支付時沒有返回pay_info
     */
    public function testPayReturnWithoutPayInfo()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'return_msg' => '下单成功',
            'return_code' => '0000',
            'payParam' => [
                'orderId' => '180131160446310012',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $pay3K2 = new Pay3K2();
        $pay3K2->setContainer($this->container);
        $pay3K2->setClient($this->client);
        $pay3K2->setResponse($response);
        $pay3K2->setPrivateKey('test');
        $pay3K2->setOptions($this->option);
        $pay3K2->getVerifyData();
    }

    /**
     * 測試支付對外返回缺少scheme
     */
    public function testPayReturnWithoutScheme()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'return_msg' => '下单成功',
            'return_code' => '0000',
            'payParam' => [
                'pay_info' => '://api.yoyupay.com/goto/order/411616/14422?url=HTTPS://QR.ALIPAY.COM/FKX0O78AE',
                'orderId' => '180131160446310012',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $pay3K2 = new Pay3K2();
        $pay3K2->setContainer($this->container);
        $pay3K2->setClient($this->client);
        $pay3K2->setResponse($response);
        $pay3K2->setPrivateKey('test');
        $pay3K2->setOptions($this->option);
        $pay3K2->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $result = [
            'return_msg' => '下单成功',
            'return_code' => '0000',
            'payParam' => [
                'pay_info' => 'http://api.yoyupay.com/goto/order/411616/14422?url=HTTPS://QR.ALIPAY.COM/FKX0O78AE',
                'orderId' => '180131160446310012',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $pay3K2 = new Pay3K2();
        $pay3K2->setContainer($this->container);
        $pay3K2->setClient($this->client);
        $pay3K2->setResponse($response);
        $pay3K2->setPrivateKey('test');
        $pay3K2->setOptions($this->option);
        $data = $pay3K2->getVerifyData();

        $this->assertEquals('http://api.yoyupay.com/goto/order/411616/14422', $data['post_url']);
        $this->assertEquals('HTTPS://QR.ALIPAY.COM/FKX0O78AE', $data['params']['url']);
        $this->assertEquals('GET', $pay3K2->getPayMethod());
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

        $pay3K2 = new Pay3K2();
        $pay3K2->setPrivateKey('test');
        $pay3K2->verifyOrderPayment([]);
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

        $pay3K2 = new Pay3K2();
        $pay3K2->setPrivateKey('test');
        $pay3K2->setOptions($this->returnResult);
        $pay3K2->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = '50176E2C999A01DAA3B7CAFA90DFE75B';

        $pay3K2 = new Pay3K2();
        $pay3K2->setPrivateKey('test');
        $pay3K2->setOptions($this->returnResult);
        $pay3K2->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未返回return_code
     */
    public function testReturnWithoutReturnCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['return_code']);

        $pay3K2 = new Pay3K2();
        $pay3K2->setPrivateKey('test');
        $pay3K2->setOptions($this->returnResult);
        $pay3K2->verifyOrderPayment([]);
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

        $this->returnResult['return_code'] = '0004';

        $pay3K2 = new Pay3K2();
        $pay3K2->setPrivateKey('test');
        $pay3K2->setOptions($this->returnResult);
        $pay3K2->verifyOrderPayment([]);
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

        $pay3K2 = new Pay3K2();
        $pay3K2->setPrivateKey('test');
        $pay3K2->setOptions($this->returnResult);
        $pay3K2->verifyOrderPayment($entry);
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
            'id' => '201803300000045931',
            'amount' => '123',
        ];

        $pay3K2 = new Pay3K2();
        $pay3K2->setPrivateKey('test');
        $pay3K2->setOptions($this->returnResult);
        $pay3K2->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $entry = [
            'id' => '201803300000045931',
            'amount' => '1',
        ];

        $pay3K2 = new Pay3K2();
        $pay3K2->setPrivateKey('test');
        $pay3K2->setOptions($this->returnResult);
        $pay3K2->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $pay3K2->getMsg());
    }
}
