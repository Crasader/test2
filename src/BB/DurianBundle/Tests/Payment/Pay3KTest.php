<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\Pay3K;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class Pay3KTest extends DurianTestCase
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

        $pay3K = new Pay3K();
        $pay3K->getVerifyData();
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

        $pay3K = new Pay3K();
        $pay3K->setPrivateKey('test');
        $pay3K->getVerifyData();
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

        $options = [
            'number' => '100397',
            'amount' => '10',
            'orderId' => '201710200000005227',
            'paymentVendorId' => '9453',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'notify_url' => 'http://pay.in-action.tw/',
        ];

        $pay3K = new Pay3K();
        $pay3K->setPrivateKey('test');
        $pay3K->setOptions($options);
        $pay3K->getVerifyData();
    }

    /**
     * 測試支付時沒有帶入MerchantExtra的情況
     */
    public function testPayWithoutMerchantExtra()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $options = [
            'number' => '100397',
            'amount' => '9453',
            'orderId' => '201710200000005227',
            'paymentVendorId' => '1097',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'notify_url' => 'http://pay.in-action.tw/',
            'merchant_extra' => [],
        ];

        $pay3K = new Pay3K();
        $pay3K->setPrivateKey('test');
        $pay3K->setOptions($options);
        $pay3K->getVerifyData();
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

        $options = [
            'number' => '100397',
            'amount' => '9453',
            'orderId' => '201710200000005227',
            'paymentVendorId' => '1097',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'notify_url' => 'http://pay.in-action.tw/',
            'merchant_extra' => ['appId' => '1002'],
            'verify_url' => '',
        ];

        $pay3K = new Pay3K();
        $pay3K->setPrivateKey('test');
        $pay3K->setOptions($options);
        $pay3K->getVerifyData();
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

        $options = [
            'number' => '100397',
            'amount' => '0.01',
            'orderId' => '201710200000005227',
            'paymentVendorId' => '1097',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'notify_url' => 'http://pay.in-action.tw/',
            'merchant_extra' => ['appId' => '1002'],
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $pay3K = new Pay3K();
        $pay3K->setContainer($this->container);
        $pay3K->setClient($this->client);
        $pay3K->setResponse($response);
        $pay3K->setPrivateKey('test');
        $pay3K->setOptions($options);
        $pay3K->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '签名校验失败，请查询key值或者sign是否正确',
            180130
        );

        $options = [
            'number' => '100397',
            'amount' => '0.1',
            'orderId' => '201703240000001427',
            'paymentVendorId' => '1097',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'notify_url' => 'http://pay.in-action.tw/',
            'merchant_extra' => ['appId' => '1002'],
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'return_msg' => '签名校验失败，请查询key值或者sign是否正确',
            'return_code' => -1002,
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $pay3K = new Pay3K();
        $pay3K->setContainer($this->container);
        $pay3K->setClient($this->client);
        $pay3K->setResponse($response);
        $pay3K->setPrivateKey('test');
        $pay3K->setOptions($options);
        $pay3K->getVerifyData();
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

        $options = [
            'number' => '100397',
            'amount' => '0.1',
            'orderId' => '201703240000001427',
            'paymentVendorId' => '1097',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'notify_url' => 'http://pay.in-action.tw/',
            'merchant_extra' => ['appId' => '1002'],
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'return_msg' => '预下单成功',
            'return_code' => 0,
            'orderId' => '1003971002201710206206557039',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $pay3K = new Pay3K();
        $pay3K->setContainer($this->container);
        $pay3K->setClient($this->client);
        $pay3K->setResponse($response);
        $pay3K->setPrivateKey('test');
        $pay3K->setOptions($options);
        $pay3K->getVerifyData();
    }

    /**
     * 測試支付對外返回缺少query
     */
    public function testPayReturnWithoutQuery()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '100397',
            'amount' => '0.1',
            'orderId' => '201703240000001427',
            'paymentVendorId' => '1097',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'notify_url' => 'http://pay.in-action.tw/',
            'merchant_extra' => ['appId' => '1002'],
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'payParam' => [
                'pay_info' => 'http://qq.ludstudio.com/api/jumptoweixin',
            ],
            'return_msg' => '预下单成功',
            'return_code' => 0,
            'orderId' => '1003971002201710206206557039',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $pay3K = new Pay3K();
        $pay3K->setContainer($this->container);
        $pay3K->setClient($this->client);
        $pay3K->setResponse($response);
        $pay3K->setPrivateKey('test');
        $pay3K->setOptions($options);
        $pay3K->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'number' => '100397',
            'amount' => '0.1',
            'orderId' => '201703240000001427',
            'paymentVendorId' => '1097',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'notify_url' => 'http://pay.in-action.tw/',
            'merchant_extra' => ['appId' => '1002'],
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'payParam' => [
                'pay_info' => 'http://qq.ludstudio.com/api/jumptoweixin?wid=1092&qid=59e9c68f3fe72cc608fa1dc1',
            ],
            'return_msg' => '预下单成功',
            'return_code' => 0,
            'orderId' => '1003971002201710206206557039',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $pay3K = new Pay3K();
        $pay3K->setContainer($this->container);
        $pay3K->setClient($this->client);
        $pay3K->setResponse($response);
        $pay3K->setPrivateKey('test');
        $pay3K->setOptions($options);
        $data = $pay3K->getVerifyData();

        $this->assertEquals('http://qq.ludstudio.com/api/jumptoweixin', $data['post_url']);
        $this->assertEquals('1092', $data['params']['wid']);
        $this->assertEquals('59e9c68f3fe72cc608fa1dc1', $data['params']['qid']);
        $this->assertEquals('GET', $pay3K->getPayMethod());
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

        $pay3K = new Pay3K();
        $pay3K->setPrivateKey('test');
        $pay3K->verifyOrderPayment([]);
    }

    /**
     * 測試返回時沒有帶入MerchantExtra的情況
     */
    public function testReturnWithoutMerchantExtra()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $options = [
            'totalFee' => '10',
            'return_code' => '0',
            'channelOrderId' => '201710200000005227',
            'orderId' => '1003971002201710205072225024',
            'timeStamp' => '20171020190700',
            'transactionId' => '4200000012201710209253241984',
            'merchant_extra' => [],
        ];

        $pay3K = new Pay3K();
        $pay3K->setPrivateKey('test');
        $pay3K->setOptions($options);
        $pay3K->verifyOrderPayment([]);
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

        $options = [
            'totalFee' => '10',
            'return_code' => '0',
            'channelOrderId' => '201710200000005227',
            'orderId' => '1003971002201710205072225024',
            'timeStamp' => '20171020190700',
            'transactionId' => '4200000012201710209253241984',
            'merchant_extra' => ['verifyKey' => 'test'],
        ];

        $pay3K = new Pay3K();
        $pay3K->setPrivateKey('test');
        $pay3K->setOptions($options);
        $pay3K->verifyOrderPayment([]);
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

        $options = [
            'totalFee' => '10',
            'return_code' => '0',
            'channelOrderId' => '201710200000005227',
            'orderId' => '1003971002201710205072225024',
            'timeStamp' => '20171020190700',
            'transactionId' => '4200000012201710209253241984',
            'sign' => '3302a94fe1725f3eff73842f305b1c7d',
            'merchant_extra' => ['verifyKey' => 'test'],
        ];

        $pay3K = new Pay3K();
        $pay3K->setPrivateKey('test');
        $pay3K->setOptions($options);
        $pay3K->verifyOrderPayment([]);
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

        $options = [
            'totalFee' => '10',
            'return_code' => '1',
            'channelOrderId' => '201710200000005227',
            'orderId' => '1003971002201710205072225024',
            'timeStamp' => '20171020190700',
            'transactionId' => '4200000012201710209253241984',
            'sign' => '5ac9d0350d424d96c278abe44c933e82',
            'merchant_extra' => ['verifyKey' => 'test'],
        ];

        $pay3K = new Pay3K();
        $pay3K->setPrivateKey('test');
        $pay3K->setOptions($options);
        $pay3K->verifyOrderPayment([]);
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

        $options = [
            'totalFee' => '10',
            'return_code' => '0',
            'channelOrderId' => '201710200000005227',
            'orderId' => '1003971002201710205072225024',
            'timeStamp' => '20171020190700',
            'transactionId' => '4200000012201710209253241984',
            'sign' => '5ac9d0350d424d96c278abe44c933e82',
            'merchant_extra' => ['verifyKey' => 'test'],
        ];

        $entry = ['id' => '9453'];

        $pay3K = new Pay3K();
        $pay3K->setPrivateKey('test');
        $pay3K->setOptions($options);
        $pay3K->verifyOrderPayment($entry);
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

        $options = [
            'totalFee' => '10',
            'return_code' => '0',
            'channelOrderId' => '201710200000005227',
            'orderId' => '1003971002201710205072225024',
            'timeStamp' => '20171020190700',
            'transactionId' => '4200000012201710209253241984',
            'sign' => '5ac9d0350d424d96c278abe44c933e82',
            'merchant_extra' => ['verifyKey' => 'test'],
        ];


        $entry = [
            'id' => '201710200000005227',
            'amount' => '1',
        ];

        $pay3K = new Pay3K();
        $pay3K->setPrivateKey('test');
        $pay3K->setOptions($options);
        $pay3K->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $options = [
            'totalFee' => '10',
            'return_code' => '0',
            'channelOrderId' => '201710200000005227',
            'orderId' => '1003971002201710205072225024',
            'timeStamp' => '20171020190700',
            'transactionId' => '4200000012201710209253241984',
            'sign' => '5ac9d0350d424d96c278abe44c933e82',
            'merchant_extra' => ['verifyKey' => 'test'],
        ];

        $entry = [
            'id' => '201710200000005227',
            'amount' => '0.1',
        ];

        $pay3K = new Pay3K();
        $pay3K->setPrivateKey('test');
        $pay3K->setOptions($options);
        $pay3K->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $pay3K->getMsg());
    }
}
