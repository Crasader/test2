<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\BaoQing;
use Buzz\Message\Response;

class BaoQingTest extends DurianTestCase
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
            'number' => 'ZB1527508605',
            'orderId' => '201806220000012002',
            'amount' => '1',
            'notify_url' => 'http://retunr.php',
            'paymentVendorId' => '1092',
            'ip' => '192.168.101.1',
            'verify_url' => 'payment.http.www.gzbaoqing.com',
            'verify_ip' => ['172.26.54.41', '172.26.54.42'],
        ];

        $this->verifySuccessResult = [
            'resqn' => 'QD_201806220000012002',
            'payinfo' => 'weixin://wxpay/bizpayurl?pr=z3DZmbq',
            'trxid' => '111894690000277437',
            'body' => '201806220000012002',
            'trxstatus' => '0000',
            'sign' => 'f55a92eb2c1bbe4ffc059748748494bb',
        ];

        $this->verifyFailResult = [
            'status' => '40011',
            'msg' => '回调地址为空',
        ];

        $this->returnResult = [
            'status' => '200',
            'account' => 'ZB1527508605',
            'resqn' => 'QD201806220000012002',
            'trade_no' => '4200000110201806222027045583',
            'pay_amount' => '100',
            'mer_sign' => '29e0325cf0602dae15c2828c43f18471',
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

        $baoQing = new BaoQing();
        $baoQing->getVerifyData();
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

        $baoQing = new BaoQing();
        $baoQing->setPrivateKey('test');
        $baoQing->setOptions([]);
        $baoQing->getVerifyData();
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

        $baoQing = new BaoQing();
        $baoQing->setPrivateKey('test');
        $baoQing->setOptions($this->sourceData);
        $baoQing->getVerifyData();
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

        $baoQing = new BaoQing();
        $baoQing->setPrivateKey('test');
        $baoQing->setOptions($this->sourceData);
        $baoQing->getVerifyData();
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

        $baoQing = new BaoQing();
        $baoQing->setContainer($this->container);
        $baoQing->setClient($this->client);
        $baoQing->setResponse($response);
        $baoQing->setPrivateKey('test');
        $baoQing->setOptions($this->sourceData);
        $baoQing->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '回调地址为空',
            180130
        );

        $response = new Response();
        $response->setContent(json_encode($this->verifyFailResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $baoQing = new BaoQing();
        $baoQing->setContainer($this->container);
        $baoQing->setClient($this->client);
        $baoQing->setResponse($response);
        $baoQing->setPrivateKey('test');
        $baoQing->setOptions($this->sourceData);
        $baoQing->getVerifyData();
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

        $baoQing = new BaoQing();
        $baoQing->setContainer($this->container);
        $baoQing->setClient($this->client);
        $baoQing->setResponse($response);
        $baoQing->setPrivateKey('test');
        $baoQing->setOptions($this->sourceData);
        $baoQing->getVerifyData();
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

        $baoQing = new BaoQing();
        $baoQing->setContainer($this->container);
        $baoQing->setClient($this->client);
        $baoQing->setResponse($response);
        $baoQing->setPrivateKey('test');
        $baoQing->setOptions($this->sourceData);
        $baoQing->getVerifyData();
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

        $baoQing = new BaoQing();
        $baoQing->setContainer($this->container);
        $baoQing->setClient($this->client);
        $baoQing->setResponse($response);
        $baoQing->setPrivateKey('test');
        $baoQing->setOptions($this->sourceData);
        $baoQing->getVerifyData();
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

        $baoQing = new BaoQing();
        $baoQing->setContainer($this->container);
        $baoQing->setClient($this->client);
        $baoQing->setResponse($response);
        $baoQing->setPrivateKey('test');
        $baoQing->setOptions($this->sourceData);
        $data = $baoQing->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=z3DZmbq', $baoQing->getQrcode());
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $this->sourceData['paymentVendorId'] = '1098';

        $baoQing = new BaoQing();
        $baoQing->setPrivateKey('test');
        $baoQing->setOptions($this->sourceData);
        $data = $baoQing->getVerifyData();

        $this->assertEquals('ZB1527508605', $data['account']);
        $this->assertEquals('QD201806220000012002', $data['resqn']);
        $this->assertEquals('201806220000012002', $data['body']);
        $this->assertEquals('1', $data['pay_amount']);
        $this->assertEquals('http://retunr.php', $data['notify_url']);
        $this->assertEquals('1', $data['pay_type']);
        $this->assertEquals('192.168.101.1', $data['pay_ip']);
        $this->assertEquals('1', $data['is_key']);
        $this->assertEquals('d5b87b795510f536f87c48ac08e185b5', $data['sign']);
        $this->assertEquals('5', $data['pay_way']);
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

        $baoQing = new BaoQing();
        $baoQing->verifyOrderPayment([]);
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

        $baoQing = new BaoQing();
        $baoQing->setPrivateKey('test');
        $baoQing->setOptions([]);
        $baoQing->verifyOrderPayment([]);
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

        $baoQing = new BaoQing();
        $baoQing->setPrivateKey('test');
        $baoQing->setOptions($this->returnResult);
        $baoQing->verifyOrderPayment([]);
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

        $baoQing = new BaoQing();
        $baoQing->setPrivateKey('test');
        $baoQing->setOptions($this->returnResult);
        $baoQing->verifyOrderPayment([]);
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
        $this->returnResult['mer_sign'] = 'd01a2c5d0ef4d5ddf02dfd8dd063e428';

        $baoQing = new BaoQing();
        $baoQing->setPrivateKey('test');
        $baoQing->setOptions($this->returnResult);
        $baoQing->verifyOrderPayment([]);
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

        $baoQing = new BaoQing();
        $baoQing->setPrivateKey('test');
        $baoQing->setOptions($this->returnResult);
        $baoQing->verifyOrderPayment($entry);
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
            'id' => '201806220000012002',
            'amount' => '1000',
        ];

        $baoQing = new BaoQing();
        $baoQing->setPrivateKey('test');
        $baoQing->setOptions($this->returnResult);
        $baoQing->verifyOrderPayment($entry);
    }

    /**
     * 測試返回成功
     */
    public function testReturnSuccess()
    {
        $entry = [
            'id' => '201806220000012002',
            'amount' => '1.00',
        ];

        $baoQing = new BaoQing();
        $baoQing->setPrivateKey('test');
        $baoQing->setOptions($this->returnResult);
        $baoQing->verifyOrderPayment($entry);

        $this->assertEquals('success', $baoQing->getMsg());
    }
}
