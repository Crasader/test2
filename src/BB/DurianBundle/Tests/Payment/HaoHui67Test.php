<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\HaoHui67;
use Buzz\Message\Response;

class HaoHui67Test extends DurianTestCase
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
            'number' => 'esball67',
            'paymentVendorId' => '1098',
            'amount' => '1',
            'orderId' => '201807040000005826',
            'notify_url' => 'http://pay.my/pay/reutrn.php',
            'ip' => '111.235.135.54',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => ['Token' => '123456'],
        ];

        $this->returnResult = [
            'account' => 'esball67',
            'nonceStr' => '5c7a0872487f89a274e3f64d19da8fe6aa29582b47670421329ce8f2947927b4',
            'orderNo' => '201807040000005826',
            'payMoney' => '1.00',
            'payStatus' => 'success',
            'uuid' => '5b3c67785dff9',
            'sign' => 'b2977bb9429127a390c1bd4c699a3def6c960d6fa315367e52f99a77ccfe28e0',
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

        $haoHui67 = new HaoHui67();
        $haoHui67->getVerifyData();
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

        $haoHui67 = new HaoHui67();
        $haoHui67->setPrivateKey('test');
        $haoHui67->getVerifyData();
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

        $haoHui67 = new HaoHui67();
        $haoHui67->setPrivateKey('test');
        $haoHui67->setOptions($this->option);
        $haoHui67->getVerifyData();
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

        $haoHui67 = new HaoHui67();
        $haoHui67->setPrivateKey('test');
        $haoHui67->setOptions($this->option);
        $haoHui67->getVerifyData();
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
            'uuid' => '5b3c67785dff9',
            'redirectURL' => 'https://www.honor6767.com/pay/route/version/h5/e64b7507-a807-47f3-8dfe-818b88eced70',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $haoHui67 = new HaoHui67();
        $haoHui67->setContainer($this->container);
        $haoHui67->setClient($this->client);
        $haoHui67->setResponse($response);
        $haoHui67->setPrivateKey('test');
        $haoHui67->setOptions($this->option);
        $haoHui67->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '未開放',
            180130
        );

        $result = [
            'retCode' => '-101',
            'retMsg' => '未開放',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $haoHui67 = new HaoHui67();
        $haoHui67->setContainer($this->container);
        $haoHui67->setClient($this->client);
        $haoHui67->setResponse($response);
        $haoHui67->setPrivateKey('test');
        $haoHui67->setOptions($this->option);
        $haoHui67->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗且未回傳retMsg
     */
    public function testPayReturnNotSuccessAndNoReturRetMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $result = ['retCode' => '-101'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $haoHui67 = new HaoHui67();
        $haoHui67->setContainer($this->container);
        $haoHui67->setClient($this->client);
        $haoHui67->setResponse($response);
        $haoHui67->setPrivateKey('test');
        $haoHui67->setOptions($this->option);
        $haoHui67->getVerifyData();
    }

    /**
     * 測試支付時沒有返回redirectURL
     */
    public function testPayReturnWithoutRedirectUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'retCode' => '0',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $haoHui67 = new HaoHui67();
        $haoHui67->setContainer($this->container);
        $haoHui67->setClient($this->client);
        $haoHui67->setResponse($response);
        $haoHui67->setPrivateKey('test');
        $haoHui67->setOptions($this->option);
        $haoHui67->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {

        $result = [
            'retCode' => '0',
            'uuid' => '5b3c67785dff9',
            'redirectURL' => 'https://www.honor6767.com/pay/route/version/h5/e64b7507',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $haoHui67 = new HaoHui67();
        $haoHui67->setContainer($this->container);
        $haoHui67->setClient($this->client);
        $haoHui67->setResponse($response);
        $haoHui67->setPrivateKey('test');
        $haoHui67->setOptions($this->option);
        $data = $haoHui67->getVerifyData();

        $this->assertEquals('https://www.honor6767.com/pay/route/version/h5/e64b7507', $data['post_url']);
        $this->assertEmpty($data['params']);
        $this->assertEquals('GET', $haoHui67->getPayMethod());
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

        $haoHui67 = new HaoHui67();
        $haoHui67->verifyOrderPayment([]);
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

        $haoHui67 = new HaoHui67();
        $haoHui67->setPrivateKey('test');
        $haoHui67->verifyOrderPayment([]);
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

        $haoHui67 = new HaoHui67();
        $haoHui67->setPrivateKey('test');
        $haoHui67->setOptions($this->returnResult);
        $haoHui67->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = '0BA6E31DBDEEF4740293F4122FD18DA263829A6FEDE798E8F195D091A6DE4E2F';

        $haoHui67 = new HaoHui67();
        $haoHui67->setPrivateKey('test');
        $haoHui67->setOptions($this->returnResult);
        $haoHui67->verifyOrderPayment([]);
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

        $this->returnResult['payStatus'] = 'timeout';
        $this->returnResult['sign'] = 'b0b6cb807647c2098673ce260f71da415b6c6dbc4250796497a54401780aec79';

        $haoHui67 = new HaoHui67();
        $haoHui67->setPrivateKey('test');
        $haoHui67->setOptions($this->returnResult);
        $haoHui67->verifyOrderPayment([]);
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

        $entry = ['id' => '201807040000005825'];

        $haoHui67 = new HaoHui67();
        $haoHui67->setPrivateKey('test');
        $haoHui67->setOptions($this->returnResult);
        $haoHui67->verifyOrderPayment($entry);
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
            'id' => '201807040000005826',
            'amount' => '100',
        ];

        $haoHui67 = new HaoHui67();
        $haoHui67->setPrivateKey('test');
        $haoHui67->setOptions($this->returnResult);
        $haoHui67->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $entry = [
            'id' => '201807040000005826',
            'amount' => '1',
        ];

        $haoHui67 = new HaoHui67();
        $haoHui67->setPrivateKey('test');
        $haoHui67->setOptions($this->returnResult);
        $haoHui67->verifyOrderPayment($entry);

        $this->assertEquals('888888', $haoHui67->getMsg());
    }
}