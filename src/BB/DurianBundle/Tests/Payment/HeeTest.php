<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Hee;
use Buzz\Message\Response;

class HeeTest extends DurianTestCase
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
            ->will($this->returnValue(null));

        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $this->container->expects($this->any())
            ->method('get')
            ->willReturn($mockLogger);

        $this->client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $this->option = [
            'paymentVendorId' => '1097',
            'number' => 'spade88',
            'notify_url' => 'http://pay.my/pay/reutrn.php',
            'orderId' => '201804190000004852',
            'amount' => '1',
            'ip' => '111.235.135.54',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $this->returnResult = [
            'is_paid' => 'true',
            'merchant_id' => 'spade88',
            'nonce_str' => '11451a9726082c3a09670230f6c4614a',
            'notify_time' => '20180419212618',
            'order_no' => '201804190000004852',
            'out_trade_no' => 'a59f55d88777028d265ffb2cd5ff18ef',
            'service' => 'Hee_Weixin_H5',
            'total_fee' => '100',
            'sign' => '2BEE0AC6E354CD50851AF3E208945BB2',
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

        $hee = new Hee();
        $hee->getVerifyData();
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

        $hee = new Hee();
        $hee->setPrivateKey('test');
        $hee->getVerifyData();
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

        $hee = new Hee();
        $hee->setPrivateKey('test');
        $hee->setOptions($this->option);
        $hee->getVerifyData();
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

        $hee = new Hee();
        $hee->setPrivateKey('test');
        $hee->setOptions($this->option);
        $hee->getVerifyData();
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

        $hee = new Hee();
        $hee->setContainer($this->container);
        $hee->setClient($this->client);
        $hee->setResponse($response);
        $hee->setPrivateKey('test');
        $hee->setOptions($this->option);
        $hee->getVerifyData();
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

        $hee = new Hee();
        $hee->setContainer($this->container);
        $hee->setClient($this->client);
        $hee->setResponse($response);
        $hee->setPrivateKey('test');
        $hee->setOptions($this->option);
        $hee->getVerifyData();
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
            'merchant_id' => 'spade88',
            'message' => 'success',
            'order_no' => '201804190000004852',
            'out_trade_no' => '6773f2de7ca19192cc3672c6eec30436',
            'result' => 'success',
            'total_fee' => 100,
            'sign' => '7E63EDB2E3D06776333B8FCE2D88D878',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $hee = new Hee();
        $hee->setContainer($this->container);
        $hee->setClient($this->client);
        $hee->setResponse($response);
        $hee->setPrivateKey('test');
        $hee->setOptions($this->option);
        $hee->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {

        $result = [
            'merchant_id' => 'spade88',
            'message' => 'success',
            'order_no' => '201804190000004852',
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

        $hee = new Hee();
        $hee->setContainer($this->container);
        $hee->setClient($this->client);
        $hee->setResponse($response);
        $hee->setPrivateKey('test');
        $hee->setOptions($this->option);
        $data = $hee->getVerifyData();

        $this->assertEquals('https://pay.heepay.com/Payment/Index.aspx', $data['post_url']);
        $this->assertEquals('1', $data['params']['version']);
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

        $hee = new Hee();
        $hee->verifyOrderPayment([]);
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

        $hee = new Hee();
        $hee->setPrivateKey('test');
        $hee->verifyOrderPayment([]);
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

        $hee = new Hee();
        $hee->setPrivateKey('test');
        $hee->setOptions($this->returnResult);
        $hee->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = 'E7AEB29BE01E8CDE3A3403F5B898E5A2';

        $hee = new Hee();
        $hee->setPrivateKey('test');
        $hee->setOptions($this->returnResult);
        $hee->verifyOrderPayment([]);
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
        $this->returnResult['sign'] = 'C21A02927804439E5612E9F757E50393';

        $hee = new Hee();
        $hee->setPrivateKey('test');
        $hee->setOptions($this->returnResult);
        $hee->verifyOrderPayment([]);
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

        $entry = ['id' => '201804190000004853'];

        $hee = new Hee();
        $hee->setPrivateKey('test');
        $hee->setOptions($this->returnResult);
        $hee->verifyOrderPayment($entry);
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
            'id' => '201804190000004852',
            'amount' => '100',
        ];

        $hee = new Hee();
        $hee->setPrivateKey('test');
        $hee->setOptions($this->returnResult);
        $hee->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $entry = [
            'id' => '201804190000004852',
            'amount' => '1',
        ];

        $hee = new Hee();
        $hee->setPrivateKey('test');
        $hee->setOptions($this->returnResult);
        $hee->verifyOrderPayment($entry);

        $this->assertEquals('success', $hee->getMsg());
    }
}
