<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Kubxent;
use Buzz\Message\Response;

class KubxentTest extends DurianTestCase
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
     * 支付時的參數
     *
     * @var array
     */
    private $sourceData;

    /**
     * 對外提交成功的參數
     *
     * @var array
     */
    private $verifySuccessResult;

    /**
     * 對外提交失敗的參數
     *
     * @var array
     */
    private $verifyFailResult;

    /**
     * 返回時的參數
     *
     * @var array
     */
    private $returnReslt;

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

        $this->sourceData = [
            'number' => '04558605',
            'orderId' => '201806230000012008',
            'amount' => '1',
            'notify_url' => 'http://retunr.php',
            'paymentVendorId' => '1090',
            'verify_url' => 'payment.http.kubxent.com',
            'verify_ip' => ['172.26.54.41', '172.26.54.42'],
        ];

        $this->verifySuccessResult = [
            'resqn' => 'QD_201806230000012008',
            'payinfo' => 'weixin://wxpay/bizpayurl?pr=iMGpT7E',
            'trxid' => '',
            'body' => '充值',
            'trxstatus' => '0000',
            'sign' => 'cf1e64a43191612dae55494561ba60a7',
        ];

        $this->verifyFailResult = [
            'status' => '40013',
            'msg' => '验签失败',
        ];

        $this->returnResult = [
            'status' => '200',
            'account' => '04558605',
            'resqn' => 'QD_201806230000012008',
            'trade_no' => '4200000115201806233475513488',
            'pay_amount' => '100',
            'mer_sign' => 'cab380df5ff7db81573844b929820914',
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

        $kubxent = new Kubxent();
        $kubxent->getVerifyData();
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

        $kubxent = new Kubxent();
        $kubxent->setPrivateKey('test');
        $kubxent->setOptions([]);
        $kubxent->getVerifyData();
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

        $this->sourceData['paymentVendorId'] = '66666';

        $kubxent = new Kubxent();
        $kubxent->setPrivateKey('test');
        $kubxent->setOptions($this->sourceData);
        $kubxent->getVerifyData();
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

        $this->sourceData['verify_url'] = '';

        $kubxent = new Kubxent();
        $kubxent->setPrivateKey('test');
        $kubxent->setOptions($this->sourceData);
        $kubxent->getVerifyData();
    }

    /**
     * 測試支付時未返回trxstatus及status
     */
    public function testPayNoReturnTrxstatusAndStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $response = new Response();
        $response->setContent(json_encode([]));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $kubxent = new Kubxent();
        $kubxent->setContainer($this->container);
        $kubxent->setClient($this->client);
        $kubxent->setResponse($response);
        $kubxent->setPrivateKey('test');
        $kubxent->setOptions($this->sourceData);
        $kubxent->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '验签失败',
            180130
        );

        $response = new Response();
        $response->setContent(json_encode($this->verifyFailResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $kubxent = new Kubxent();
        $kubxent->setContainer($this->container);
        $kubxent->setClient($this->client);
        $kubxent->setResponse($response);
        $kubxent->setPrivateKey('test');
        $kubxent->setOptions($this->sourceData);
        $kubxent->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗且無提示訊息
     */
    public function testPayReturnNotSuccessAndNoMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        unset($this->verifyFailResult['msg']);

        $response = new Response();
        $response->setContent(json_encode($this->verifyFailResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $kubxent = new Kubxent();
        $kubxent->setContainer($this->container);
        $kubxent->setClient($this->client);
        $kubxent->setResponse($response);
        $kubxent->setPrivateKey('test');
        $kubxent->setOptions($this->sourceData);
        $kubxent->getVerifyData();
    }

    /**
     * 測試支付時返回trxstatus不等於0000
     */
    public function testPayReturnTrxstatusNotEqual0000()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $this->verifySuccessResult['trxstatus'] = '1234';

        $response = new Response();
        $response->setContent(json_encode($this->verifySuccessResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $kubxent = new Kubxent();
        $kubxent->setContainer($this->container);
        $kubxent->setClient($this->client);
        $kubxent->setResponse($response);
        $kubxent->setPrivateKey('test');
        $kubxent->setOptions($this->sourceData);
        $kubxent->getVerifyData();
    }

    /**
     * 測試支付時未返回payinfo
     */
    public function testPayNoReturnPayInfo()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        unset($this->verifySuccessResult['payinfo']);

        $response = new Response();
        $response->setContent(json_encode($this->verifySuccessResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $kubxent = new Kubxent();
        $kubxent->setContainer($this->container);
        $kubxent->setClient($this->client);
        $kubxent->setResponse($response);
        $kubxent->setPrivateKey('test');
        $kubxent->setOptions($this->sourceData);
        $kubxent->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $response = new Response();
        $response->setContent(json_encode($this->verifySuccessResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $kubxent = new Kubxent();
        $kubxent->setContainer($this->container);
        $kubxent->setClient($this->client);
        $kubxent->setResponse($response);
        $kubxent->setPrivateKey('test');
        $kubxent->setOptions($this->sourceData);
        $data = $kubxent->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=iMGpT7E', $kubxent->getQrcode());
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

        $kubxent = new Kubxent();
        $kubxent->verifyOrderPayment([]);
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

        $kubxent = new Kubxent();
        $kubxent->setPrivateKey('test');
        $kubxent->setOptions([]);
        $kubxent->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳mer_sign(加密簽名)
     */
    public function testReturnWithoutMerSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['mer_sign']);

        $kubxent = new Kubxent();
        $kubxent->setPrivateKey('test');
        $kubxent->setOptions($this->returnResult);
        $kubxent->verifyOrderPayment([]);
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

        $this->returnResult['mer_sign'] = 'error';

        $kubxent = new Kubxent();
        $kubxent->setPrivateKey('test');
        $kubxent->setOptions($this->returnResult);
        $kubxent->verifyOrderPayment([]);
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

        $this->returnResult['status'] = '-1';
        $this->returnResult['mer_sign'] = '7d15ae67005572edac8eb5663444fc58';

        $kubxent = new Kubxent();
        $kubxent->setPrivateKey('test');
        $kubxent->setOptions($this->returnResult);
        $kubxent->verifyOrderPayment([]);
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

        $entry = ['id' => '201704100000002210'];

        $kubxent = new Kubxent();
        $kubxent->setPrivateKey('test');
        $kubxent->setOptions($this->returnResult);
        $kubxent->verifyOrderPayment($entry);
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
            'id' => '201806230000012008',
            'amount' => '1000',
        ];

        $kubxent = new Kubxent();
        $kubxent->setPrivateKey('test');
        $kubxent->setOptions($this->returnResult);
        $kubxent->verifyOrderPayment($entry);
    }

    /**
     * 測試返回成功
     */
    public function testReturnSuccess()
    {
        $entry = [
            'id' => '201806230000012008',
            'amount' => '1.00',
        ];

        $kubxent = new Kubxent();
        $kubxent->setPrivateKey('test');
        $kubxent->setOptions($this->returnResult);
        $kubxent->verifyOrderPayment($entry);

        $this->assertEquals('success', $kubxent->getMsg());
    }
}
