<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ChengYouPay;
use Buzz\Message\Response;

class ChengYouPayTest extends DurianTestCase
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
     * 對外返回的參數
     *
     * @var array
     */
    private $verifyResult;

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
            'number' => '10101',
            'orderId' => '201806290000012252',
            'orderCreateDate' => '2018-06-29 15:14:44',
            'paymentVendorId' => '1111',
            'notify_url' => 'http://pay.my/pay/return.php',
            'amount' => '1',
            'verify_ip' => ['172.26.54.41', '172.26.54.42'],
            'verify_url' => 'payment.http.118.31.21.217',
        ];

        $this->verifyResult = [
            'pay_amount' => '3000',
            'pay_orderid' => '201806210000011906',
            'pay_url' => 'https://qr.95516.com/00010000/62622847681799056315106438022626',
            'pay_code' => 'HL0000',
            'pay_msg' => '下单成功',
        ];

        $this->returnResult = [
            'memberid' => '10099',
            'orderid' => '201806290000012252',
            'transaction_id' => '201806290000012252',
            'amount' => '1.0000',
            'datetime' => '2018-06-29 09:19:44',
            'returncode' => '00',
            'sign' => '22C873DC137B4DE7B1C1834EB5178A5B',
            'attach' => '',
        ];
    }

    /**
     * 測試支付時沒有私鑰
     */
    public function testPayWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $chengYouPay = new ChengYouPay();
        $chengYouPay->getVerifyData();
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

        $chengYouPay = new ChengYouPay();
        $chengYouPay->setPrivateKey('test');
        $chengYouPay->setOptions([]);
        $chengYouPay->getVerifyData();
    }

    /**
     * 測試支付時帶入不支援的銀行
     */
    public function testPayWithUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $this->option['paymentVendorId'] = '9999';

        $chengYouPay = new ChengYouPay();
        $chengYouPay->setPrivateKey('test');
        $chengYouPay->setOptions($this->option);
        $chengYouPay->getVerifyData();
    }

    /**
     * 測試對外返回不為json格式
     */
    public function testVerifyResultNotJson()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '充值金额必须是10元的整数倍！如：10元 20元 30元...',
            180130
        );

        $response = new Response();
        $response->setContent('充值金额必须是10元的整数倍！如：10元 20元 30元...');
        $response->addHeader('HTTP/1.1 200 OK');

        $chengYouPay = new ChengYouPay();
        $chengYouPay->setContainer($this->container);
        $chengYouPay->setClient($this->client);
        $chengYouPay->setResponse($response);
        $chengYouPay->setPrivateKey('test');
        $chengYouPay->setOptions($this->option);
        $chengYouPay->getVerifyData();
    }

    /**
     * 測試對外返回沒有pay_code
     */
    public function testVerifyResultWithoutPayCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        unset($this->verifyResult['pay_code']);

        $response = new Response();
        $response->setContent(json_encode($this->verifyResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;');

        $chengYouPay = new ChengYouPay();
        $chengYouPay->setContainer($this->container);
        $chengYouPay->setClient($this->client);
        $chengYouPay->setResponse($response);
        $chengYouPay->setPrivateKey('test');
        $chengYouPay->setOptions($this->option);
        $chengYouPay->getVerifyData();
    }

    /**
     * 測試對外返回不成功
     */
    public function testVerifyResultNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '操作失败',
            180130
        );

        $result = [
            'pay_msg' => '操作失败',
            'pay_code' => 'HL0001',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;');

        $chengYouPay = new ChengYouPay();
        $chengYouPay->setContainer($this->container);
        $chengYouPay->setClient($this->client);
        $chengYouPay->setResponse($response);
        $chengYouPay->setPrivateKey('test');
        $chengYouPay->setOptions($this->option);
        $chengYouPay->getVerifyData();
    }

    /**
     * 測試對外返回不成功且無錯誤訊息
     */
    public function testVerifyResultNotSuccessAndNoErrorMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $result = [
            'pay_code' => 'HL0001',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;');

        $chengYouPay = new ChengYouPay();
        $chengYouPay->setContainer($this->container);
        $chengYouPay->setClient($this->client);
        $chengYouPay->setResponse($response);
        $chengYouPay->setPrivateKey('test');
        $chengYouPay->setOptions($this->option);
        $chengYouPay->getVerifyData();
    }

    /**
     * 測試支付未返回pay_url
     */
    public function testVerifyResultWithoutPayUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        unset($this->verifyResult['pay_url']);

        $response = new Response();
        $response->setContent(json_encode($this->verifyResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;');

        $chengYouPay = new ChengYouPay();
        $chengYouPay->setContainer($this->container);
        $chengYouPay->setClient($this->client);
        $chengYouPay->setResponse($response);
        $chengYouPay->setPrivateKey('test');
        $chengYouPay->setOptions($this->option);
        $chengYouPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $response = new Response();
        $response->setContent(json_encode($this->verifyResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;');

        $chengYouPay = new ChengYouPay();
        $chengYouPay->setContainer($this->container);
        $chengYouPay->setClient($this->client);
        $chengYouPay->setResponse($response);
        $chengYouPay->setPrivateKey('test');
        $chengYouPay->setOptions($this->option);
        $data = $chengYouPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals(
            'https://qr.95516.com/00010000/62622847681799056315106438022626',
            $chengYouPay->getQrcode()
        );
    }

    /**
     * 測試返回時沒有私鑰
     */
    public function testReturnWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $chengYouPay = new ChengYouPay();
        $chengYouPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未指定參數
     */
    public function testReturnWithNoParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $chengYouPay = new ChengYouPay();
        $chengYouPay->setPrivateKey('test');
        $chengYouPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少加密簽名
     */
    public function testReturnWithoutSignature()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['sign']);

        $chengYouPay = new ChengYouPay();
        $chengYouPay->setPrivateKey('test');
        $chengYouPay->setOptions($this->returnResult);
        $chengYouPay->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = 'error';

        $chengYouPay = new ChengYouPay();
        $chengYouPay->setPrivateKey('test');
        $chengYouPay->setOptions($this->returnResult);
        $chengYouPay->verifyOrderPayment([]);
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

        $this->returnResult['returncode'] = '-1';
        $this->returnResult['sign'] = '4CE92ADC785608BC9B1759C4D7580C1F';

        $chengYouPay = new ChengYouPay();
        $chengYouPay->setPrivateKey('test');
        $chengYouPay->setOptions($this->returnResult);
        $chengYouPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時單號不正確
     */
    public function testReturnPaymentOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $entry = ['id' => '201805070000005138'];

        $chengYouPay = new ChengYouPay();
        $chengYouPay->setPrivateKey('test');
        $chengYouPay->setOptions($this->returnResult);
        $chengYouPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時金額不正確
     */
    public function testReturnPaymentOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $entry = [
            'id' => '201806290000012252',
            'amount' => '100',
        ];

        $chengYouPay = new ChengYouPay();
        $chengYouPay->setPrivateKey('test');
        $chengYouPay->setOptions($this->returnResult);
        $chengYouPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時支付成功
     */
    public function testReturnResultSuccess()
    {
        $entry = [
            'id' => '201806290000012252',
            'amount' => '1',
        ];

        $chengYouPay = new ChengYouPay();
        $chengYouPay->setPrivateKey('test');
        $chengYouPay->setOptions($this->returnResult);
        $chengYouPay->verifyOrderPayment($entry);

        $this->assertEquals('OK', $chengYouPay->getMsg());
    }
}
