<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\IFeePay;
use Buzz\Message\Response;

class IFeePayTest extends DurianTestCase
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

        $iFeePay = new IFeePay();
        $iFeePay->getVerifyData();
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

        $iFeePay = new IFeePay();
        $iFeePay->setPrivateKey('test');
        $iFeePay->setOptions($sourceData);
        $iFeePay->getVerifyData();
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
            'number' => '144710001674',
            'paymentVendorId' => '9999',
            'username' => 'php1test',
            'amount' => '0.01',
            'orderId' => '201801030000003494',
            'notify_url' => 'http://pay.my/pay/return.php',
        ];

        $iFeePay = new IFeePay();
        $iFeePay->setPrivateKey('test');
        $iFeePay->setOptions($sourceData);
        $iFeePay->getVerifyData();
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
            'number' => '144710001674',
            'paymentVendorId' => '1103',
            'username' => 'php1test',
            'amount' => '0.01',
            'orderId' => '201801030000003494',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $iFeePay = new IFeePay();
        $iFeePay->setPrivateKey('test');
        $iFeePay->setOptions($sourceData);
        $iFeePay->getVerifyData();
    }

    /**
     * 測試支付時返回缺少result_code
     */
    public function testPayReturnWithoutResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => '144710001674',
            'paymentVendorId' => '1103',
            'username' => 'php1test',
            'amount' => '0.01',
            'orderId' => '201801030000003494',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.pay.ifeepay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'bank_code' => 'QQSCAN',
            'code_url' => 'https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027&_bid=2183&t=6V9718630cae4',
            'order_no' => '201801030000003490',
            'result_msg' => '二维码请求成功',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $iFeePay = new IFeePay();
        $iFeePay->setPrivateKey('test');
        $iFeePay->setContainer($this->container);
        $iFeePay->setClient($this->client);
        $iFeePay->setResponse($response);
        $iFeePay->setOptions($sourceData);
        $iFeePay->getVerifyData();
    }

    /**
     * 測試支付時返回缺少result_msg
     */
    public function testPayReturnWithoutResultMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => '144710001674',
            'paymentVendorId' => '1103',
            'username' => 'php1test',
            'amount' => '0.01',
            'orderId' => '201801030000003494',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.pay.ifeepay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'bank_code' => 'QQSCAN',
            'code_url' => 'https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027&_bid=2183&t=6V9718630cae4',
            'order_no' => '201801030000003490',
            'result_code' => '00',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $iFeePay = new IFeePay();
        $iFeePay->setPrivateKey('test');
        $iFeePay->setContainer($this->container);
        $iFeePay->setClient($this->client);
        $iFeePay->setResponse($response);
        $iFeePay->setOptions($sourceData);
        $iFeePay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '没有路由可用',
            180130
        );

        $sourceData = [
            'number' => '144710001674',
            'paymentVendorId' => '1103',
            'username' => 'php1test',
            'amount' => '0.01',
            'orderId' => '201801030000003494',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.pay.ifeepay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'result_code' => 'BNK001',
            'result_msg' => '没有路由可用',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $iFeePay = new IFeePay();
        $iFeePay->setPrivateKey('test');
        $iFeePay->setContainer($this->container);
        $iFeePay->setClient($this->client);
        $iFeePay->setResponse($response);
        $iFeePay->setOptions($sourceData);
        $iFeePay->getVerifyData();
    }

    /**
     * 測試支付時返回缺少code_url
     */
    public function testPayReturnWithoutCodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => '144710001674',
            'paymentVendorId' => '1103',
            'username' => 'php1test',
            'amount' => '0.01',
            'orderId' => '201801030000003494',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.pay.ifeepay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'bank_code' => 'QQSCAN',
            'order_no' => '201801030000003490',
            'result_code' => '00',
            'result_msg' => '二维码请求成功',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $iFeePay = new IFeePay();
        $iFeePay->setPrivateKey('test');
        $iFeePay->setContainer($this->container);
        $iFeePay->setClient($this->client);
        $iFeePay->setResponse($response);
        $iFeePay->setOptions($sourceData);
        $iFeePay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $result = [
            'bank_code' => 'QQSCAN',
            'code_url' => 'https://myun.tenpay.com/mqq/pay/qrcode.html',
            'order_no' => '201801030000003490',
            'result_code' => '00',
            'result_msg' => '二维码请求成功',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $sourceData = [
            'number' => '144710001674',
            'paymentVendorId' => '1103',
            'username' => 'php1test',
            'amount' => '0.01',
            'orderId' => '201801030000003494',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.pay.ifeepay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $iFeePay = new IFeePay();
        $iFeePay->setPrivateKey('test');
        $iFeePay->setContainer($this->container);
        $iFeePay->setClient($this->client);
        $iFeePay->setResponse($response);
        $iFeePay->setOptions($sourceData);
        $verifyData = $iFeePay->getVerifyData();

        $this->assertEmpty($verifyData);
        $this->assertSame('https://myun.tenpay.com/mqq/pay/qrcode.html', $iFeePay->getQrcode());
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $sourceData = [
            'number' => '144710001674',
            'paymentVendorId' => '1104',
            'username' => 'php1test',
            'amount' => '0.01',
            'orderId' => '201801030000003494',
            'notify_url' => 'http://pay.my/pay/return.php',
        ];

        $iFeePay = new IFeePay();
        $iFeePay->setPrivateKey('test');
        $iFeePay->setOptions($sourceData);
        $verifyData = $iFeePay->getVerifyData();

        $this->assertEquals('v1', $verifyData['version']);
        $this->assertEquals('144710001674', $verifyData['merchant_no']);
        $this->assertEquals('201801030000003494', $verifyData['order_no']);
        $this->assertEquals('php1test', $verifyData['goods_name']);
        $this->assertEquals('0.01', $verifyData['order_amount']);
        $this->assertEquals('http://pay.my/pay/return.php', $verifyData['backend_url']);
        $this->assertEquals('', $verifyData['frontend_url']);
        $this->assertEquals('', $verifyData['reserve']);
        $this->assertEquals('12', $verifyData['pay_mode']);
        $this->assertEquals('QQWAP', $verifyData['bank_code']);
        $this->assertEquals('0', $verifyData['card_type']);
        $this->assertEquals('71674a25310718a5320311a247de7615', $verifyData['sign']);
    }

    /**
     * 測試網銀支付
     */
    public function testBankPay()
    {
        $sourceData = [
            'number' => '144710001674',
            'paymentVendorId' => '1',
            'username' => 'php1test',
            'amount' => '0.01',
            'orderId' => '201801030000003494',
            'notify_url' => 'http://pay.my/pay/return.php',
        ];

        $iFeePay = new IFeePay();
        $iFeePay->setPrivateKey('test');
        $iFeePay->setOptions($sourceData);
        $verifyData = $iFeePay->getVerifyData();

        $this->assertEquals('v1', $verifyData['version']);
        $this->assertEquals('144710001674', $verifyData['merchant_no']);
        $this->assertEquals('201801030000003494', $verifyData['order_no']);
        $this->assertEquals('php1test', $verifyData['goods_name']);
        $this->assertEquals('0.01', $verifyData['order_amount']);
        $this->assertEquals('http://pay.my/pay/return.php', $verifyData['backend_url']);
        $this->assertEquals('', $verifyData['frontend_url']);
        $this->assertEquals('', $verifyData['reserve']);
        $this->assertEquals('01', $verifyData['pay_mode']);
        $this->assertEquals('ICBC', $verifyData['bank_code']);
        $this->assertEquals('0', $verifyData['card_type']);
        $this->assertEquals('d177c22b73b0d3d718ad4ca009774a58', $verifyData['sign']);
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

        $iFeePay = new IFeePay();
        $iFeePay->verifyOrderPayment([]);
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

        $iFeePay = new IFeePay();
        $iFeePay->setPrivateKey('test');
        $iFeePay->verifyOrderPayment([]);
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

        $sourceData = [
            'merchant_no' => '144710001674',
            'order_no' => '201801030000003494',
            'order_amount' => '0.01',
            'original_amount' => '0.01',
            'upstream_settle' => '0',
            'result' => 'S',
            'pay_time' => '20180103134936',
            'trace_id' => '1195625',
            'reserve' => '',
        ];

        $iFeePay = new IFeePay();
        $iFeePay->setPrivateKey('test');
        $iFeePay->setOptions($sourceData);
        $iFeePay->verifyOrderPayment([]);
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

        $sourceData = [
            'merchant_no' => '144710001674',
            'order_no' => '201801030000003494',
            'order_amount' => '0.01',
            'original_amount' => '0.01',
            'upstream_settle' => '0',
            'result' => 'S',
            'pay_time' => '20180103134936',
            'trace_id' => '1195625',
            'reserve' => '',
            'sign' => 'e8484c85fb00de20221e408f57dca147',
        ];

        $iFeePay = new IFeePay();
        $iFeePay->setPrivateKey('test');
        $iFeePay->setOptions($sourceData);
        $iFeePay->verifyOrderPayment([]);
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
            'merchant_no' => '144710001674',
            'order_no' => '201801030000003494',
            'order_amount' => '0.01',
            'original_amount' => '0.01',
            'upstream_settle' => '0',
            'result' => 'F',
            'pay_time' => '20180103134936',
            'trace_id' => '1195625',
            'reserve' => '',
            'sign' => '20ed1eab491690cd88073634df71f9ee',
        ];

        $iFeePay = new IFeePay();
        $iFeePay->setPrivateKey('test');
        $iFeePay->setOptions($sourceData);
        $iFeePay->verifyOrderPayment([]);
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
            'merchant_no' => '144710001674',
            'order_no' => '201801030000003494',
            'order_amount' => '0.01',
            'original_amount' => '0.01',
            'upstream_settle' => '0',
            'result' => 'S',
            'pay_time' => '20180103134936',
            'trace_id' => '1195625',
            'reserve' => '',
            'sign' => '8f4a9f0166bd57e9bc240a37362192a1',
        ];

        $entry = [
            'id' => '201801030000003495',
            'amount' => '0.01',
        ];

        $iFeePay = new IFeePay();
        $iFeePay->setPrivateKey('test');
        $iFeePay->setOptions($sourceData);
        $iFeePay->verifyOrderPayment($entry);
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

        $sourceData = [
            'merchant_no' => '144710001674',
            'order_no' => '201801030000003494',
            'order_amount' => '0.01',
            'original_amount' => '0.01',
            'upstream_settle' => '0',
            'result' => 'S',
            'pay_time' => '20180103134936',
            'trace_id' => '1195625',
            'reserve' => '',
            'sign' => '8f4a9f0166bd57e9bc240a37362192a1',
        ];

        $entry = [
            'id' => '201801030000003494',
            'amount' => '1',
        ];

        $iFeePay = new IFeePay();
        $iFeePay->setPrivateKey('test');
        $iFeePay->setOptions($sourceData);
        $iFeePay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'merchant_no' => '144710001674',
            'order_no' => '201801030000003494',
            'order_amount' => '0.01',
            'original_amount' => '0.01',
            'upstream_settle' => '0',
            'result' => 'S',
            'pay_time' => '20180103134936',
            'trace_id' => '1195625',
            'reserve' => '',
            'sign' => '8f4a9f0166bd57e9bc240a37362192a1',
        ];

        $entry = [
            'id' => '201801030000003494',
            'amount' => '0.01',
        ];

        $iFeePay = new IFeePay();
        $iFeePay->setPrivateKey('test');
        $iFeePay->setOptions($sourceData);
        $iFeePay->verifyOrderPayment($entry);

        $this->assertEquals('success', $iFeePay->getMsg());
    }

    /**
     * 測試出款沒有帶入privateKey
     */
    public function testWithdrawWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $iFeePay = new IFeePay();
        $iFeePay->withdrawPayment();
    }

    /**
     * 測試出款未指定出款參數
     */
    public function testWithdrawNoWithdrawParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No withdraw parameter specified',
            150180196
        );

        $sourceData = ['account' => ''];

        $iFeePay = new IFeePay();
        $iFeePay->setPrivateKey('jy9CV6uguTE=');

        $iFeePay->setOptions($sourceData);
        $iFeePay->withdrawPayment();
    }

    /**
     * 測試出款缺少商家附加設定值
     */
    public function testWithdrawWithoutMerchantExtra()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $sourceData = [
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '0.01',
            'orderId' => '112332',
            'number' => '10000080001641',
            'withdraw_host' => 'payment.http.withdraw.com',
            'merchant_extra' => [],
        ];

        $iFeePay = new IFeePay();
        $iFeePay->setPrivateKey('jy9CV6uguTE=');
        $iFeePay->setOptions($sourceData);
        $iFeePay->withdrawPayment();
    }

    /**
     * 測試出款但餘額不足
     */
    public function testWithdrawButInsufficientBalance()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Insufficient balance',
            150180197
        );

        $sourceData = [
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '0.01',
            'orderId' => '112332',
            'number' => '10000080001641',
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => ['pay_pwd' => '1234'],
        ];

        $result = '{"result_code":"TRS001","result_msg":"\u5546\u6237\u94b1\u5305\u4f59\u989d\u4e0d\u8db3"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $iFeePay = new IFeePay();
        $iFeePay->setContainer($this->container);
        $iFeePay->setClient($this->client);
        $iFeePay->setResponse($response);
        $iFeePay->setPrivateKey('12345');
        $iFeePay->setOptions($sourceData);
        $iFeePay->withdrawPayment();
    }

    /**
     * 測試出款返回異常
     */
    public function testWithdrawFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '订单已存在',
            180124
        );

        $sourceData = [
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '0.01',
            'orderId' => '112332',
            'number' => '10000080001641',
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => ['pay_pwd' => '1234'],
        ];

        $result = '{"result_code":"ORD031","result_msg":"\u8ba2\u5355\u5df2\u5b58\u5728"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $iFeePay = new IFeePay();
        $iFeePay->setContainer($this->container);
        $iFeePay->setClient($this->client);
        $iFeePay->setResponse($response);
        $iFeePay->setPrivateKey('12345');
        $iFeePay->setOptions($sourceData);
        $iFeePay->withdrawPayment();
    }

    /**
     * 測試出款返回成功
     */
    public function testWithdrawSuccess()
    {
        $sourceData = [
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '0.01',
            'orderId' => '112332',
            'number' => '10000080001641',
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => ['pay_pwd' => '1234'],
        ];

        $result = '{"result_code":"000000","result_msg":"\u4ee3\u4ed8\u7533\u8bf7\u6210\u529f' .
            '\uff0c\u8bf7\u8010\u5fc3\u7b49\u5f85\u51fa\u6b3e\u7ed3\u679c","merchant_no":"144710001674",' .
            '"order_no":"20180116122038755","mer_order_no":"112384","result":"H"' .
            ',"sign":"ef0ab4d129b9e7c50492377ce75922a2"}';

        $mockCwe = $this->getMockBuilder('BB\DurianBundle\Entity\CashWithdrawEntry')
            ->disableOriginalConstructor()
            ->setMethods(['setRefId'])
            ->getMock();
        $mockCwe->expects($this->any())
            ->method('setRefId')
            ->willReturn($mockCwe);

        $mockRepo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $mockRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn($mockCwe);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository', 'flush'])
            ->getMock();
        $mockEm->expects($this->at(0))
            ->method('getRepository')
            ->willReturn($mockRepo);

        $mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();
        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $mockLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Payment')
            ->disableOriginalConstructor()
            ->setMethods(['record'])
            ->getMock();

        $getMap = [
            ['durian.payment_logger', 1, $mockLogger],
            ['doctrine', 1, $mockDoctrine],
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $iFeePay = new IFeePay();
        $iFeePay->setContainer($mockContainer);
        $iFeePay->setClient($this->client);
        $iFeePay->setResponse($response);
        $iFeePay->setPrivateKey('12345');
        $iFeePay->setOptions($sourceData);
        $iFeePay->withdrawPayment();
    }
}
