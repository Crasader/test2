<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\HY;
use Buzz\Message\Response;

class HYTest extends DurianTestCase
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
            ->willReturn($this->returnValue(null));

        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $this->container->expects($this->any())
            ->method('get')
            ->willReturn($mockLogger);

        $this->client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $this->option = [
            'paymentVendorId' => '1092',
            'number' => 'spade99',
            'notify_url' => 'http://pay.my/pay/reutrn.php',
            'orderId' => '201804300000005017',
            'amount' => '1',
            'ip' => '111.235.135.54',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $this->returnResult = [
            'is_paid' => 'true',
            'merchant_id' => 'spade99',
            'nonce_str' => '6fac7866fd21dbad2dfddd3dbfa2d687',
            'notify_time' => '2018-04-30 20:33:13',
            'order_fee' => '100',
            'order_no' => '201804300000005017',
            'out_trade_no' => '7570840eecde68776fe90162a8c91b1e',
            'service' => 'Hyzfu_Weixin_QR',
            'total_fee' => '96',
            'sign' => '225D9765E7CD83E866A36B616C55A4E8',
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

        $hy = new HY();
        $hy->getVerifyData();
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

        $hy = new HY();
        $hy->setPrivateKey('test');
        $hy->getVerifyData();
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

        $hy = new HY();
        $hy->setPrivateKey('test');
        $hy->setOptions($this->option);
        $hy->getVerifyData();
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

        $hy = new HY();
        $hy->setPrivateKey('test');
        $hy->setOptions($this->option);
        $hy->getVerifyData();
    }

    /**
     * 測試支付時沒有返回result
     */
    public function testPayReturnWithoutResult()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'message' => 'AmountError',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $hy = new HY();
        $hy->setContainer($this->container);
        $hy->setClient($this->client);
        $hy->setResponse($response);
        $hy->setPrivateKey('test');
        $hy->setOptions($this->option);
        $hy->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'AmountError',
            180130
        );

        $result = [
            'result' => 'fail',
            'message' => 'AmountError',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $hy = new HY();
        $hy->setContainer($this->container);
        $hy->setClient($this->client);
        $hy->setResponse($response);
        $hy->setPrivateKey('test');
        $hy->setOptions($this->option);
        $hy->getVerifyData();
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
            'merchant_id' => 'spade99',
            'message' => 'success',
            'order_no' => '201804300000005017',
            'out_trade_no' => '6773f2de7ca19192cc3672c6eec30436',
            'result' => 'success',
            'total_fee' => 100,
            'sign' => '7E63EDB2E3D06776333B8FCE2D88D878',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $hy = new HY();
        $hy->setContainer($this->container);
        $hy->setClient($this->client);
        $hy->setResponse($response);
        $hy->setPrivateKey('test');
        $hy->setOptions($this->option);
        $hy->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {

        $result = [
            'merchant_id' => 'spade99',
            'message' => 'success',
            'order_no' => '201804300000005017',
            'out_trade_no' => '6773f2de7ca19192cc3672c6eec30436',
            'result' => 'success',
            'total_fee' => 100,
            'url' => 'https://pay.heepay.com/Payment/Index.aspx?version=1',
            'sign' => '7E63EDB2E3D06776333B8FCE2D88D878',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $hy = new HY();
        $hy->setContainer($this->container);
        $hy->setClient($this->client);
        $hy->setResponse($response);
        $hy->setPrivateKey('test');
        $hy->setOptions($this->option);
        $data = $hy->getVerifyData();

        $this->assertEquals('https://pay.heepay.com/Payment/Index.aspx', $data['post_url']);
        $this->assertEquals('1', $data['params']['version']);
        $this->assertEquals('GET', $hy->getPayMethod());
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

        $hy = new HY();
        $hy->verifyOrderPayment([]);
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

        $hy = new HY();
        $hy->setPrivateKey('test');
        $hy->verifyOrderPayment([]);
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

        $hy = new HY();
        $hy->setPrivateKey('test');
        $hy->setOptions($this->returnResult);
        $hy->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = '9733BA4F8743EF5AA831A50CC248C82B';

        $hy = new HY();
        $hy->setPrivateKey('test');
        $hy->setOptions($this->returnResult);
        $hy->verifyOrderPayment([]);
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

        $this->returnResult['is_paid'] = 'false';
        $this->returnResult['sign'] = '052AECAA9F37892FF6F86A1BCAF0FBD0';

        $hy = new HY();
        $hy->setPrivateKey('test');
        $hy->setOptions($this->returnResult);
        $hy->verifyOrderPayment([]);
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

        $entry = ['id' => '201804300000005018'];

        $hy = new HY();
        $hy->setPrivateKey('test');
        $hy->setOptions($this->returnResult);
        $hy->verifyOrderPayment($entry);
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
            'id' => '201804300000005017',
            'amount' => '100',
        ];

        $hy = new HY();
        $hy->setPrivateKey('test');
        $hy->setOptions($this->returnResult);
        $hy->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $entry = [
            'id' => '201804300000005017',
            'amount' => '1',
        ];

        $hy = new HY();
        $hy->setPrivateKey('test');
        $hy->setOptions($this->returnResult);
        $hy->verifyOrderPayment($entry);

        $this->assertEquals('success', $hy->getMsg());
    }
}
