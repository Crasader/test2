<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ChangPay;
use Buzz\Message\Response;

class ChangPayTest extends DurianTestCase
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

        $changPay = new ChangPay();
        $changPay->getVerifyData();
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

        $changPay = new ChangPay();
        $changPay->setPrivateKey('test');
        $changPay->setOptions($sourceData);
        $changPay->getVerifyData();
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
            'number' => 'ybwtcrxvhj',
            'paymentVendorId' => '9999',
            'amount' => '1',
            'orderId' => '201806120000014058',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'orderCreateDate' => '2018-06-12 11:45:55',
            'ip' => '111.235.135.54',
        ];

        $changPay = new ChangPay();
        $changPay->setPrivateKey('test');
        $changPay->setOptions($sourceData);
        $changPay->getVerifyData();
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
            'number' => 'ybwtcrxvhj',
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201806120000014058',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => '',
            'orderCreateDate' => '2018-06-12 11:45:55',
            'ip' => '111.235.135.54',
        ];

        $changPay = new ChangPay();
        $changPay->setPrivateKey('test');
        $changPay->setOptions($sourceData);
        $changPay->getVerifyData();
    }

    /**
     * 測試支付時未返回code
     */
    public function testPayNoReturnCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'data' => [
                'mch_id' => 'ybwtcrxvhj',
                'op_channel_id' => '1193',
                'channel_id' => '645',
                'api_id' => '128',
                'pay_type' => '1',
                'mch_order' => '201806120000014058',
                'created_at' => '1528774637',
                'pay_info' => 'https://n-sdk.retenai.com/api/transfer.api?amt=1000&mch_amt=1000&mch_id=ybwtcrxvhj' .
                    '&mch_order=201806120000014058&service=1&sign_type=md5&sign=3a486335ff2db6eeee4fa7d0ee8ee666',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'ybwtcrxvhj',
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201806120000014058',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'orderCreateDate' => '2018-06-12 11:45:55',
            'ip' => '111.235.135.54',
        ];

        $changPay = new ChangPay();
        $changPay->setContainer($this->container);
        $changPay->setClient($this->client);
        $changPay->setResponse($response);
        $changPay->setPrivateKey('test');
        $changPay->setOptions($sourceData);
        $changPay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '系统下单异常或支付通道异常',
            180130
        );

        $result = [
            'code' => 114,
            'msg' => '系统下单异常或支付通道异常',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'ybwtcrxvhj',
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201806120000014058',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'orderCreateDate' => '2018-06-12 11:45:55',
            'ip' => '111.235.135.54',
        ];

        $changPay = new ChangPay();
        $changPay->setContainer($this->container);
        $changPay->setClient($this->client);
        $changPay->setResponse($response);
        $changPay->setPrivateKey('test');
        $changPay->setOptions($sourceData);
        $changPay->getVerifyData();
    }

    /**
     * 測試支付時返回沒有msg
     */
    public function testPayReturnWithoutMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $result = [
            'code' => 114,
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'ybwtcrxvhj',
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201806120000014058',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'orderCreateDate' => '2018-06-12 11:45:55',
            'ip' => '111.235.135.54',
        ];

        $changPay = new ChangPay();
        $changPay->setContainer($this->container);
        $changPay->setClient($this->client);
        $changPay->setResponse($response);
        $changPay->setPrivateKey('test');
        $changPay->setOptions($sourceData);
        $changPay->getVerifyData();
    }

    /**
     * 測試網銀支付時未返回pay_info
     */
    public function testBankPayNoReturnPayInfo()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'code' => 1,
            'data' => [
                'mch_id' => 'ybwtcrxvhj',
                'op_channel_id' => '1193',
                'channel_id' => '645',
                'api_id' => '128',
                'pay_type' => '1',
                'mch_order' => '201806120000014058',
                'created_at' => '1528774637',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'ybwtcrxvhj',
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201806120000014058',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'orderCreateDate' => '2018-06-12 11:45:55',
            'ip' => '111.235.135.54',
        ];

        $changPay = new ChangPay();
        $changPay->setContainer($this->container);
        $changPay->setClient($this->client);
        $changPay->setResponse($response);
        $changPay->setPrivateKey('test');
        $changPay->setOptions($sourceData);
        $changPay->getVerifyData();
    }

    /**
     * 測試二維支付時未返回code_url
     */
    public function testScanPayNoReturnCodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'code' => 1,
            'data' => [
                'mch_id' => 'ybwtcrxvhj',
                'op_channel_id' => '1193',
                'channel_id' => '645',
                'api_id' => '128',
                'pay_type' => '1',
                'mch_order' => '201806120000014058',
                'created_at' => '1528774637',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'ybwtcrxvhj',
            'paymentVendorId' => '1103',
            'amount' => '1',
            'orderId' => '201806120000014058',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'orderCreateDate' => '2018-06-12 11:45:55',
            'ip' => '111.235.135.54',
        ];

        $changPay = new ChangPay();
        $changPay->setContainer($this->container);
        $changPay->setClient($this->client);
        $changPay->setResponse($response);
        $changPay->setPrivateKey('test');
        $changPay->setOptions($sourceData);
        $changPay->getVerifyData();
    }

    /**
     * 測試京東手機支付時未返回pay_url
     */
    public function testJingDongPayNoReturnPayUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'code' => 1,
            'data' => [
                'mch_id' => 'ybwtcrxvhj',
                'op_channel_id' => '1193',
                'channel_id' => '645',
                'api_id' => '128',
                'pay_type' => '1',
                'mch_order' => '201806120000014058',
                'created_at' => '1528774637',
                'code_url' => 'https://n-sdk.retenai.com/api/transfer.api?amt=1000&mch_amt=1000&mch_id=ybwtcrxvhj' .
                    '&mch_order=201806120000014058&service=1&sign_type=md5&sign=3a486335ff2db6eeee4fa7d0ee8ee666',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'ybwtcrxvhj',
            'paymentVendorId' => '1108',
            'amount' => '1',
            'orderId' => '201806120000014058',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'orderCreateDate' => '2018-06-12 11:45:55',
            'ip' => '111.235.135.54',
        ];

        $changPay = new ChangPay();
        $changPay->setContainer($this->container);
        $changPay->setClient($this->client);
        $changPay->setResponse($response);
        $changPay->setPrivateKey('test');
        $changPay->setOptions($sourceData);
        $changPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $result = [
            'code' => 1,
            'data' => [
                'mch_id' => 'ybwtcrxvhj',
                'op_channel_id' => '1193',
                'channel_id' => '645',
                'api_id' => '128',
                'pay_type' => '1',
                'mch_order' => '201806120000014058',
                'created_at' => '1528774637',
                'code_url' => 'weixin://wxpay/bizpayurl?pr=9EmHpwG',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'ybwtcrxvhj',
            'paymentVendorId' => '1090',
            'amount' => '1',
            'orderId' => '201806120000014058',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'orderCreateDate' => '2018-06-12 11:45:55',
            'ip' => '111.235.135.54',
        ];

        $changPay = new ChangPay();
        $changPay->setContainer($this->container);
        $changPay->setClient($this->client);
        $changPay->setResponse($response);
        $changPay->setPrivateKey('test');
        $changPay->setOptions($sourceData);
        $data = $changPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=9EmHpwG', $changPay->getQrcode());
    }

    /**
     * 測試網銀支付
     */
    public function testBankPay()
    {
        $result = [
            'code' => 1,
            'data' => [
                'mch_id' => 'ybwtcrxvhj',
                'op_channel_id' => '1193',
                'channel_id' => '645',
                'api_id' => '128',
                'pay_type' => '1',
                'mch_order' => '201806120000014058',
                'created_at' => '1528774637',
                'pay_info' => 'https://n-sdk.retenai.com/api/transfer.api?amt=1000&mch_amt=1000&mch_id=ybwtcrxvhj' .
                    '&mch_order=201806120000014058&service=1&sign_type=md5&sign=3a486335ff2db6eeee4fa7d0ee8ee666',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'ybwtcrxvhj',
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201806120000014058',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'orderCreateDate' => '2018-06-12 11:45:55',
            'ip' => '111.235.135.54',
        ];

        $changPay = new ChangPay();
        $changPay->setContainer($this->container);
        $changPay->setClient($this->client);
        $changPay->setResponse($response);
        $changPay->setPrivateKey('test');
        $changPay->setOptions($sourceData);
        $data = $changPay->getVerifyData();

        $this->assertEquals('https://n-sdk.retenai.com/api/transfer.api', $data['post_url']);
        $this->assertEquals('1000', $data['params']['amt']);
        $this->assertEquals('1000', $data['params']['mch_amt']);
        $this->assertEquals('ybwtcrxvhj', $data['params']['mch_id']);
        $this->assertEquals('201806120000014058', $data['params']['mch_order']);
        $this->assertEquals('1', $data['params']['service']);
        $this->assertEquals('md5', $data['params']['sign_type']);
        $this->assertEquals('3a486335ff2db6eeee4fa7d0ee8ee666', $data['params']['sign']);
        $this->assertEquals('GET', $changPay->getPayMethod());
    }

    /**
     * 測試京東手機支付
     */
    public function testJingDongPhonePay()
    {
        $result = [
            'code' => 1,
            'data' => [
                'mch_id' => 'ybwtcrxvhj',
                'op_channel_id' => '1193',
                'channel_id' => '645',
                'api_id' => '128',
                'pay_type' => '1',
                'mch_order' => '201806120000014058',
                'created_at' => '1528774637',
                'pay_url' => 'http://desk.ling-pay.com/h5pay/PrePayServlet?payType=JD&orderNum=4820180612110235976',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'ybwtcrxvhj',
            'paymentVendorId' => '1108',
            'amount' => '1',
            'orderId' => '201806120000014058',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'orderCreateDate' => '2018-06-12 11:45:55',
            'ip' => '111.235.135.54',
        ];

        $changPay = new ChangPay();
        $changPay->setContainer($this->container);
        $changPay->setClient($this->client);
        $changPay->setResponse($response);
        $changPay->setPrivateKey('test');
        $changPay->setOptions($sourceData);
        $data = $changPay->getVerifyData();

        $this->assertEquals('http://desk.ling-pay.com/h5pay/PrePayServlet', $data['post_url']);
        $this->assertEquals('JD', $data['params']['payType']);
        $this->assertEquals('4820180612110235976', $data['params']['orderNum']);
        $this->assertEquals('GET', $changPay->getPayMethod());
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

        $changPay = new ChangPay();
        $changPay->verifyOrderPayment([]);
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

        $changPay = new ChangPay();
        $changPay->setPrivateKey('test');
        $changPay->setOptions($sourceData);
        $changPay->verifyOrderPayment([]);
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
            'amt' => '1000',
            'amt_type' => 'cny',
            'created_at' => '1528774723',
            'mch_amt' => '1000',
            'mch_id' => 'ybwtcrxvhj',
            'mch_order' => '201806120000014058',
            'service' => '1',
            'sign_type' => 'md5',
            'status' => '2',
            'success_at' => '1528774721',
        ];

        $changPay = new ChangPay();
        $changPay->setPrivateKey('test');
        $changPay->setOptions($sourceData);
        $changPay->verifyOrderPayment([]);
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
            'amt' => '1000',
            'amt_type' => 'cny',
            'created_at' => '1528774723',
            'mch_amt' => '1000',
            'mch_id' => 'ybwtcrxvhj',
            'mch_order' => '201806120000014058',
            'service' => '1',
            'sign_type' => 'md5',
            'status' => '2',
            'success_at' => '1528774721',
            'sign' => '5cd5162c42dff8ea5741663dd9e2ff95',
        ];

        $changPay = new ChangPay();
        $changPay->setPrivateKey('test');
        $changPay->setOptions($sourceData);
        $changPay->verifyOrderPayment([]);
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
            'amt' => '1000',
            'amt_type' => 'cny',
            'created_at' => '1528774723',
            'mch_amt' => '1000',
            'mch_id' => 'ybwtcrxvhj',
            'mch_order' => '201806120000014058',
            'service' => '1',
            'sign_type' => 'md5',
            'status' => '1',
            'success_at' => '1528774721',
            'sign' => 'c9f0337a6b3a5f998cfd325afe4c4e9b',
        ];

        $changPay = new ChangPay();
        $changPay->setPrivateKey('test');
        $changPay->setOptions($sourceData);
        $changPay->verifyOrderPayment([]);
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
            'amt' => '1000',
            'amt_type' => 'cny',
            'created_at' => '1528774723',
            'mch_amt' => '1000',
            'mch_id' => 'ybwtcrxvhj',
            'mch_order' => '201806120000014058',
            'service' => '1',
            'sign_type' => 'md5',
            'status' => '2',
            'success_at' => '1528774721',
            'sign' => 'bf0b112da784dcdedced75de3dba57fa',
        ];

        $entry = ['id' => '201704100000002210'];

        $changPay = new ChangPay();
        $changPay->setPrivateKey('test');
        $changPay->setOptions($sourceData);
        $changPay->verifyOrderPayment($entry);
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
            'amt' => '1000',
            'amt_type' => 'cny',
            'created_at' => '1528774723',
            'mch_amt' => '1000',
            'mch_id' => 'ybwtcrxvhj',
            'mch_order' => '201806120000014058',
            'service' => '1',
            'sign_type' => 'md5',
            'status' => '2',
            'success_at' => '1528774721',
            'sign' => 'bf0b112da784dcdedced75de3dba57fa',
        ];

        $entry = [
            'id' => '201806120000014058',
            'amount' => '100',
        ];

        $changPay = new ChangPay();
        $changPay->setPrivateKey('test');
        $changPay->setOptions($sourceData);
        $changPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'amt' => '1000',
            'amt_type' => 'cny',
            'created_at' => '1528774723',
            'mch_amt' => '1000',
            'mch_id' => 'ybwtcrxvhj',
            'mch_order' => '201806120000014058',
            'service' => '1',
            'sign_type' => 'md5',
            'status' => '2',
            'success_at' => '1528774721',
            'sign' => 'bf0b112da784dcdedced75de3dba57fa',
        ];

        $entry = [
            'id' => '201806120000014058',
            'amount' => '1',
        ];

        $changPay = new ChangPay();
        $changPay->setPrivateKey('test');
        $changPay->setOptions($sourceData);
        $changPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $changPay->getMsg());
    }
}
