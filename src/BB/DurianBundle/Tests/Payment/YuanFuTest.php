<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YuanFu;
use Buzz\Message\Response;

class YuanFuTest extends DurianTestCase
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
     * 對外返回失敗結果
     *
     * @var array
     */
    private $verifyFailResult;

    /**
     * 對外返回成功結果
     *
     * @var array
     */
    private $verifySuccessResult;

    /**
     * 返回時的參數
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

        $this->sourceData = [
            'number' => '10006',
            'orderId' => '201806270000012159',
            'orderCreateDate' => '2018-06-27 16:30:21',
            'paymentVendorId' => '1111',
            'notify_url' => 'http://return.php',
            'amount' => '1',
            'verify_url' => 'payment.http.p.yuanfu123.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $this->verifyFailResult = [
            'status' => 'error',
            'msg' => '支付通道不存在',
            'data' => [
                'pay_bankcode' => '216',
            ],
        ];

        $this->verifySuccessResult = [
            'successno' => 100001,
            'msg' => '获取数据成功',
            'data' => [
                'pay_orderid' => '20180627150239102985',
                'pay_url' => 'http://jk.00597d.com:9999/pay_qr_Yzfbsm_id_20180627150239102985.html',
            ],
        ];

        $this->returnResult = [
            'memberid' => '10006',
            'orderid' => '201806270000012159',
            'transaction_id' => '20180627144635989855',
            'amount' => '1.000',
            'datetime' => '20180627144803',
            'returncode' => '00',
            'sign' => 'EC3CC798FE6E72D59B11EEBF3165B366',
            'attach' => '',
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

        $yuanFu = new YuanFu();
        $yuanFu->getVerifyData();
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

        $yuanFu = new YuanFu();
        $yuanFu->setPrivateKey('test');
        $yuanFu->getVerifyData();
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

        $this->sourceData['paymentVendorId'] = '66666';

        $yuanFu = new YuanFu();
        $yuanFu->setPrivateKey('test');
        $yuanFu->setOptions($this->sourceData);
        $yuanFu->getVerifyData();
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

        $yuanFu = new YuanFu();
        $yuanFu->setPrivateKey('test');
        $yuanFu->setOptions($this->sourceData);
        $yuanFu->getVerifyData();
    }

    /**
     * 測試支付未返回successno及status
     */
    public function testPayNoReturnStatusParamter()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        unset($this->verifyFailResult['status']);

        $response = new Response();
        $response->setContent(json_encode($this->verifyFailResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yuanFu = new YuanFu();
        $yuanFu->setPrivateKey('test');
        $yuanFu->setContainer($this->container);
        $yuanFu->setClient($this->client);
        $yuanFu->setResponse($response);
        $yuanFu->setOptions($this->sourceData);
        $yuanFu->getVerifyData();
    }

    /**
     * 測試支付未返回msg
     */
    public function testPayNoReturnMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        unset($this->verifyFailResult['msg']);

        $response = new Response();
        $response->setContent(json_encode($this->verifyFailResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yuanFu = new YuanFu();
        $yuanFu->setPrivateKey('test');
        $yuanFu->setContainer($this->container);
        $yuanFu->setClient($this->client);
        $yuanFu->setResponse($response);
        $yuanFu->setOptions($this->sourceData);
        $yuanFu->getVerifyData();
    }

    /**
     * 測試支付返回不成功
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '支付通道不存在',
            180130
        );

        $response = new Response();
        $response->setContent(json_encode($this->verifyFailResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yuanFu = new YuanFu();
        $yuanFu->setPrivateKey('test');
        $yuanFu->setContainer($this->container);
        $yuanFu->setClient($this->client);
        $yuanFu->setResponse($response);
        $yuanFu->setOptions($this->sourceData);
        $yuanFu->getVerifyData();
    }

    /**
     * 測試支付未返回pay_url
     */
    public function testPayNoReturnPayUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        unset($this->verifySuccessResult['data']['pay_url']);

        $response = new Response();
        $response->setContent(json_encode($this->verifySuccessResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yuanFu = new YuanFu();
        $yuanFu->setPrivateKey('test');
        $yuanFu->setContainer($this->container);
        $yuanFu->setClient($this->client);
        $yuanFu->setResponse($response);
        $yuanFu->setOptions($this->sourceData);
        $yuanFu->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $response = new Response();
        $response->setContent(json_encode($this->verifySuccessResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yuanFu = new YuanFu();
        $yuanFu->setPrivateKey('test');
        $yuanFu->setContainer($this->container);
        $yuanFu->setClient($this->client);
        $yuanFu->setResponse($response);
        $yuanFu->setOptions($this->sourceData);
        $data = $yuanFu->getVerifyData();

        $this->assertEmpty($data['params']);
        $this->assertEquals('http://jk.00597d.com:9999/pay_qr_Yzfbsm_id_20180627150239102985.html', $data['post_url']);
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

        $yuanFu = new YuanFu();
        $yuanFu->verifyOrderPayment([]);
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

        $yuanFu = new YuanFu();
        $yuanFu->setPrivateKey('test');
        $yuanFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['sign']);

        $yuanFu = new YuanFu();
        $yuanFu->setPrivateKey('test');
        $yuanFu->setOptions($this->returnResult);
        $yuanFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時sign簽名驗證錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $this->returnResult['sign'] = 'error';

        $yuanFu = new YuanFu();
        $yuanFu->setPrivateKey('test');
        $yuanFu->setOptions($this->returnResult);
        $yuanFu->verifyOrderPayment([]);
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

        $this->returnResult['returncode'] = '-1';
        $this->returnResult['sign'] = '277BC398A46B4C42DA98A929AB3946DB';

        $yuanFu = new YuanFu();
        $yuanFu->setPrivateKey('test');
        $yuanFu->setOptions($this->returnResult);
        $yuanFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單編號錯誤
     */
    public function testReturnOrderIdFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $entry = [
            'id' => '201711230000002582',
        ];

        $yuanFu = new YuanFu();
        $yuanFu->setPrivateKey('test');
        $yuanFu->setOptions($this->returnResult);
        $yuanFu->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為訂單金額錯誤
     */
    public function testReturnAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $entry = [
            'id' => '201806270000012159',
            'amount' => '300',
        ];

        $yuanFu = new YuanFu();
        $yuanFu->setPrivateKey('test');
        $yuanFu->setOptions($this->returnResult);
        $yuanFu->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnSuccess()
    {
        $entry = [
            'id' => '201806270000012159',
            'amount' => '1',
        ];

        $yuanFu = new YuanFu();
        $yuanFu->setPrivateKey('test');
        $yuanFu->setOptions($this->returnResult);
        $yuanFu->verifyOrderPayment($entry);

        $this->assertEquals('OK', $yuanFu->getMsg());
    }
}
