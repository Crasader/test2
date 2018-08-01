<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Telepay;
use Buzz\Message\Response;

class TelepayTest extends DurianTestCase
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
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $this->container->expects($this->any())
            ->method('get')
            ->willReturn($mockLogger);

        $this->client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
    }

    /**
     * 測試支付未帶入密鑰
     */
    public function testPayWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $telepay = new Telepay();
        $telepay->setOptions([]);
        $telepay->getVerifyData();
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

        $telepay = new Telepay();
        $telepay->setPrivateKey('private key');
        $telepay->setOptions([]);
        $telepay->getVerifyData();
    }

    /**
     * 測試支付設定帶入不支援的銀行
     */
    public function testSetEncodeSourceUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => 'CID01401',
            'orderId' => '201706200000006646',
            'amount' => '1',
            'paymentVendorId' => '999',
            'notify_url' => 'http://two123.comuv.com/pay/return.php',
            'username' => 'php1test',
        ];

        $telepay = new Telepay();
        $telepay->setPrivateKey('private key');
        $telepay->setOptions($sourceData);
        $telepay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $sourceData = [
            'number' => 'CID01401',
            'orderId' => '201706200000006646',
            'amount' => '1',
            'paymentVendorId' => '279',
            'notify_url' => 'http://two123.comuv.com/pay/return.php',
            'username' => 'php1test',
        ];

        $telepay = new Telepay();
        $telepay->setPrivateKey('private key');
        $telepay->setOptions($sourceData);
        $encodeData = $telepay->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['scode']);
        $this->assertEquals($sourceData['orderId'], $encodeData['orderid']);
        $this->assertEquals('unionpay2', $encodeData['paytype']);
        $this->assertEquals($sourceData['amount'], $encodeData['amount']);
        $this->assertEquals($sourceData['username'], $encodeData['productname']);
        $this->assertEquals('CNY', $encodeData['currcode']);
        $this->assertEquals('php1test', $encodeData['userid']);
        $this->assertEquals('', $encodeData['memo']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['callbackurl']);
        $this->assertEquals('3e2b9707948af72281c210bab3f418df', $encodeData['sign']);
    }

    /**
     * 測試手機支付時沒有帶入verify_url的情況
     */
    public function testPhonePayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => 'CID01401',
            'orderId' => '201706200000006646',
            'amount' => '1',
            'paymentVendorId' => '1097',
            'notify_url' => 'http://two123.comuv.com/pay/return.php',
            'username' => 'php1test',
            'verify_url' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"status":"-1","respcode":"13","respmsg":"payment method is not applied or not enabled"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $telepay = new Telepay();
        $telepay->setPrivateKey('private key');
        $telepay->setContainer($this->container);
        $telepay->setClient($this->client);
        $telepay->setResponse($response);
        $telepay->setOptions($sourceData);
        $telepay->getVerifyData();
    }

    /**
     * 測試手機支付沒有返回Status
     */
    public function testPhonePayReturnWithoutStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => 'CID01401',
            'orderId' => '201706200000006646',
            'amount' => '1',
            'paymentVendorId' => '1097',
            'notify_url' => 'http://two123.comuv.com/pay/return.php',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"respcode":"13","respmsg":"payment method is not applied or not enabled"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $telepay = new Telepay();
        $telepay->setPrivateKey('private key');
        $telepay->setContainer($this->container);
        $telepay->setClient($this->client);
        $telepay->setResponse($response);
        $telepay->setOptions($sourceData);
        $telepay->getVerifyData();
    }

    /**
     * 測試手機支付但返回結果失敗
     */
    public function testPhonePayButReturnFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'payment method is not applied or not enabled',
            180130
        );

        $sourceData = [
            'number' => 'CID01401',
            'orderId' => '201706200000006646',
            'amount' => '1',
            'paymentVendorId' => '1097',
            'notify_url' => 'http://two123.comuv.com/pay/return.php',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"status":"-1","respcode":"13","respmsg":"payment method is not applied or not enabled"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $telepay = new Telepay();
        $telepay->setPrivateKey('private key');
        $telepay->setContainer($this->container);
        $telepay->setClient($this->client);
        $telepay->setResponse($response);
        $telepay->setOptions($sourceData);
        $telepay->getVerifyData();
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $sourceData = [
            'number' => 'CID01401',
            'orderId' => '201706200000006646',
            'amount' => '1',
            'paymentVendorId' => '1097',
            'notify_url' => 'http://two123.comuv.com/pay/return.php',
            'username' => 'php1test',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"status":"1","respcode":"000000","respmsg":"success","type":"2"' .
            ',"url":"https://www.ezcips.net/pay/redirectGw.htm?sequenceId=70263"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $telepay = new Telepay();
        $telepay->setPrivateKey('private key');
        $telepay->setContainer($this->container);
        $telepay->setClient($this->client);
        $telepay->setResponse($response);
        $telepay->setOptions($sourceData);
        $encodeData = $telepay->getVerifyData();

        $postUrl = 'https://www.ezcips.net/pay/redirectGw.htm?sequenceId=70263';
        $this->assertEquals($postUrl, $encodeData['post_url']);
    }

    /**
     * 測試返回時未帶入密鑰
     */
    public function testReturnWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $telepay = new Telepay();
        $telepay->setOptions([]);
        $telepay->verifyOrderPayment([]);
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

        $telepay = new Telepay();
        $telepay->setOptions([]);
        $telepay->setPrivateKey('private key');
        $telepay->verifyOrderPayment([]);
    }

    /**
     * 測試返回缺少簽名
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'scode' => 'CID01401',
            'orderno' => '2017062000000002',
            'orderid' => '201706200000006646',
            'paytype' => 'unionpay2',
            'productname' => 'php1test',
            'amount' => '1',
            'currcode' => 'CNY',
            'memo' => '',
            'resptime' => '2017-06-20 10:26:03',
            'status' => '1',
            'respcode' => '00',
            'rmbrate' => '4.5150',
            'callbackurl' => 'http://two123.comuv.com/pay/return.php',
        ];

        $telepay = new Telepay();
        $telepay->setOptions($sourceData);
        $telepay->setPrivateKey('private key');
        $telepay->verifyOrderPayment([]);
    }

    /**
     * 測試返回驗簽失敗
     */
    public function testReturnWithSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $sourceData = [
            'scode' => 'CID01401',
            'orderno' => '2017062000000002',
            'orderid' => '201706200000006646',
            'paytype' => 'unionpay2',
            'productname' => 'php1test',
            'amount' => '1',
            'currcode' => 'CNY',
            'memo' => '',
            'resptime' => '2017-06-20 10:26:03',
            'status' => '1',
            'respcode' => '00',
            'rmbrate' => '4.5150',
            'sign' => '',
            'callbackurl' => 'http://two123.comuv.com/pay/return.php',
        ];

        $telepay = new Telepay();
        $telepay->setOptions($sourceData);
        $telepay->setPrivateKey('private key');
        $telepay->verifyOrderPayment([]);
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

        $sourceData = [
            'scode' => 'CID01401',
            'orderno' => '2017062000000002',
            'orderid' => '201706200000006646',
            'paytype' => 'unionpay2',
            'productname' => 'php1test',
            'amount' => '1',
            'currcode' => 'CNY',
            'memo' => '',
            'resptime' => '2017-06-20 10:26:03',
            'status' => '2',
            'respcode' => '00',
            'rmbrate' => '4.5150',
            'sign' => 'ae27810f83dc7112787af6a1d5407012',
            'callbackurl' => 'http://two123.comuv.com/pay/return.php',
        ];

        $telepay = new Telepay();
        $telepay->setOptions($sourceData);
        $telepay->setPrivateKey('private key');
        $telepay->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'scode' => 'CID01401',
            'orderno' => '2017062000000002',
            'orderid' => '201706200000006646',
            'paytype' => 'unionpay2',
            'productname' => 'php1test',
            'amount' => '1',
            'currcode' => 'CNY',
            'memo' => '',
            'resptime' => '2017-06-20 10:26:03',
            'status' => '1',
            'respcode' => '00',
            'rmbrate' => '4.5150',
            'sign' => '295ca0394e19e2aaf6e3848a57f2b493',
            'callbackurl' => 'http://two123.comuv.com/pay/return.php',
        ];

        $entry = ['id' => '1314520'];

        $telepay = new Telepay();
        $telepay->setOptions($sourceData);
        $telepay->setPrivateKey('private key');
        $telepay->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'scode' => 'CID01401',
            'orderno' => '2017062000000002',
            'orderid' => '201706200000006646',
            'paytype' => 'unionpay2',
            'productname' => 'php1test',
            'amount' => '1',
            'currcode' => 'CNY',
            'memo' => '',
            'resptime' => '2017-06-20 10:26:03',
            'status' => '1',
            'respcode' => '00',
            'rmbrate' => '4.5150',
            'sign' => '295ca0394e19e2aaf6e3848a57f2b493',
            'callbackurl' => 'http://two123.comuv.com/pay/return.php',
        ];

        $entry = [
            'id' => '201706200000006646',
            'amount' => '9487',
        ];

        $telepay = new Telepay();
        $telepay->setOptions($sourceData);
        $telepay->setPrivateKey('private key');
        $telepay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'scode' => 'CID01401',
            'orderno' => '2017062000000002',
            'orderid' => '201706200000006646',
            'paytype' => 'unionpay2',
            'productname' => 'php1test',
            'amount' => '1',
            'currcode' => 'CNY',
            'memo' => '',
            'resptime' => '2017-06-20 10:26:03',
            'status' => '1',
            'respcode' => '00',
            'rmbrate' => '4.5150',
            'sign' => '295ca0394e19e2aaf6e3848a57f2b493',
            'callbackurl' => 'http://two123.comuv.com/pay/return.php',
        ];

        $entry = [
            'id' => '201706200000006646',
            'amount' => '1',
        ];

        $telepay = new Telepay();
        $telepay->setOptions($sourceData);
        $telepay->setPrivateKey('private key');
        $telepay->verifyOrderPayment($entry);

        $this->assertEquals('OK', $telepay->getMsg());
    }

    /**
     * 測試手機支付驗證成功
     */
    public function testReturnSuccessWithPhone()
    {
        $sourceData = [
            'scode' => 'CID01401',
            'orderno' => '2017112300000496',
            'orderid' => '201711230000007786',
            'amount' => '010',
            'currcode' => 'CNY',
            'memo' => '',
            'resptime' => '2017-11-23 15:46:53',
            'status' => '1',
            'respcode' => '00',
            'paytype' => 'wechat_h5',
            'productname' => 'php1test',
            'rmbrate' => '',
            'sign' => '97a1cecac4e96d3705a39987755688be',
        ];

        $entry = [
            'id' => '201711230000007786',
            'amount' => '0.1',
        ];

        $telepay = new Telepay();
        $telepay->setOptions($sourceData);
        $telepay->setPrivateKey('private key');
        $telepay->verifyOrderPayment($entry);

        $this->assertEquals('OK', $telepay->getMsg());
    }

    /**
     * 測試訂單查詢未帶入私鑰
     */
    public function testPaymentTrackingWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $telepay = new Telepay();
        $telepay->paymentTracking();
    }

    /**
     * 測試訂單查詢未指定查詢參數
     */
    public function testPaymentTrackingWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $telepay = new Telepay();
        $telepay->setPrivateKey('private key');
        $telepay->paymentTracking();
    }

    /**
     * 測試訂單查詢沒帶入 reopUrl
     */
    public function testPaymentTrackingWithoutReopUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No reopUrl specified',
            180141
        );

        $sourceData = [
            'number' => 'CID01401',
            'orderId' => '201706200000006646',
            'reopUrl' => '',
        ];

        $telepay = new Telepay();
        $telepay->setPrivateKey('private key');
        $telepay->setOptions($sourceData);
        $telepay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果未指定返回參數
     */
    public function testPaymentTrackingResultWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = [];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'CID01401',
            'orderId' => '201706200000006646',
            'reopUrl' => 'https://payment.skillfully.com.tw/telepay/checkorder.aspx',
        ];

        $telepay = new Telepay();
        $telepay->setPrivateKey('private key');
        $telepay->setContainer($this->container);
        $telepay->setClient($this->client);
        $telepay->setResponse($response);
        $telepay->setOptions($sourceData);
        $telepay->paymentTracking();
    }

    /**
     * 測試訂單查詢未返回簽名
     */
    public function testTrackingReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = [
            'scode' => '',
            'orderid' => '',
            'orderno' => '',
            'paytype' => '',
            'amount' => '',
            'productname' => '',
            'currcode' => '',
            'status' => '',
            'respcode' => '',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'CID01401',
            'orderId' => '201706200000006646',
            'reopUrl' => 'https://payment.skillfully.com.tw/telepay/checkorder.aspx',
        ];

        $telepay = new Telepay();
        $telepay->setPrivateKey('private key');
        $telepay->setContainer($this->container);
        $telepay->setClient($this->client);
        $telepay->setResponse($response);
        $telepay->setOptions($sourceData);
        $telepay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回驗簽失敗
     */
    public function testTrackingReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $result = [
            'scode' => '',
            'orderid' => '',
            'orderno' => '',
            'paytype' => '',
            'amount' => '',
            'productname' => '',
            'currcode' => '',
            'status' => '',
            'respcode' => '',
            'sign' => 'wrong sign',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'CID01401',
            'orderId' => '201706200000006646',
            'reopUrl' => 'https://payment.skillfully.com.tw/telepay/checkorder.aspx',
        ];

        $telepay = new Telepay();
        $telepay->setPrivateKey('private key');
        $telepay->setContainer($this->container);
        $telepay->setClient($this->client);
        $telepay->setResponse($response);
        $telepay->setOptions($sourceData);
        $telepay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回尚未成功支付
     */
    public function testTrackingReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $result = [
            'scode' => '',
            'orderid' => '',
            'orderno' => '',
            'paytype' => '',
            'amount' => '',
            'productname' => '',
            'currcode' => '',
            'status' => '-1',
            'respcode' => '',
            'sign' => '1b053a69747affa9536607aff60d9f08',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'CID01401',
            'orderId' => '201706200000006646',
            'reopUrl' => 'https://payment.skillfully.com.tw/telepay/checkorder.aspx',
        ];

        $telepay = new Telepay();
        $telepay->setPrivateKey('private key');
        $telepay->setContainer($this->container);
        $telepay->setClient($this->client);
        $telepay->setResponse($response);
        $telepay->setOptions($sourceData);
        $telepay->paymentTracking();
    }

    /**
     * 測試訂單查詢單號不正確
     */
    public function testPaymentTrackingWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $result = [
            'scode' => '',
            'orderid' => '',
            'orderno' => '',
            'paytype' => '',
            'amount' => '',
            'productname' => '',
            'currcode' => '',
            'status' => '1',
            'respcode' => '',
            'sign' => '7bc9dabb1f3171d778a3100072a22e75',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'CID01401',
            'orderId' => '201706200000006646',
            'reopUrl' => 'https://payment.skillfully.com.tw/telepay/checkorder.aspx',
        ];

        $telepay = new Telepay();
        $telepay->setPrivateKey('private key');
        $telepay->setContainer($this->container);
        $telepay->setClient($this->client);
        $telepay->setResponse($response);
        $telepay->setOptions($sourceData);
        $telepay->paymentTracking();
    }

    /**
     * 測試訂單查詢帶入金額不正確
     */
    public function testPaymentTrackingWithAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $result = [
            'scode' => '',
            'orderid' => '201706200000006646',
            'orderno' => '',
            'paytype' => '',
            'amount' => '0.01',
            'productname' => '',
            'currcode' => '',
            'status' => '1',
            'respcode' => '',
            'sign' => '3f97622af521ecc94ff02d4a1e1f8b5a',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'CID01401',
            'orderId' => '201706200000006646',
            'amount' => '1',
            'reopUrl' => 'https://payment.skillfully.com.tw/telepay/checkorder.aspx',
        ];

        $telepay = new Telepay();
        $telepay->setPrivateKey('private key');
        $telepay->setContainer($this->container);
        $telepay->setClient($this->client);
        $telepay->setResponse($response);
        $telepay->setOptions($sourceData);
        $telepay->paymentTracking();
    }

    /**
     * 測試訂單查詢
     */
    public function testPaymentTracking()
    {
        $result = [
            'scode' => '',
            'orderid' => '201706200000006646',
            'orderno' => '',
            'paytype' => '',
            'amount' => '1',
            'productname' => '',
            'currcode' => '',
            'status' => '1',
            'respcode' => '',
            'sign' => '1fa83e9a1329ecb978b7233bd460a82d',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => 'CID01401',
            'orderId' => '201706200000006646',
            'amount' => '1',
            'reopUrl' => 'https://payment.skillfully.com.tw/telepay/checkorder.aspx',
        ];

        $telepay = new Telepay();
        $telepay->setPrivateKey('private key');
        $telepay->setContainer($this->container);
        $telepay->setClient($this->client);
        $telepay->setResponse($response);
        $telepay->setOptions($sourceData);
        $telepay->paymentTracking();
    }

    /**
     * 測試訂單查詢未帶入密鑰
     */
    public function testGetPaymentTrackingDataWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $telepay = new Telepay();
        $telepay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時未指定查詢參數
     */
    public function testGetPaymentTrackingDataWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $telepay = new Telepay();
        $telepay->setPrivateKey('private key');
        $telepay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒代入 reopUrl
     */
    public function testGetPaymentTrackingDataWithoutReopUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No reopUrl specified',
            180141
        );

        $options = [
            'number' => 'CID01401',
            'orderId' => '201706200000006646',
            'reopUrl' => '',
        ];

        $telepay = new Telepay();
        $telepay->setPrivateKey('private key');
        $telepay->setOptions($options);
        $telepay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'number' => 'CID01401',
            'orderId' => '201706200000006646',
            'reopUrl' => 'https://payment.skillfully.com.tw/telepay/checkorder.aspx',
        ];

        $this->container->expects($this->any())->method('getParameter')->willReturn('172.26.54.42');

        $telepay = new Telepay();
        $telepay->setPrivateKey('private key');
        $telepay->setContainer($this->container);
        $telepay->setOptions($options);
        $trackingData = $telepay->getPaymentTrackingData();

        $this->assertEquals(['172.26.54.42'], $trackingData['verify_ip']);
        $this->assertEquals('GET', $trackingData['method']);
        $this->assertEquals('172.26.54.42', $trackingData['headers']['Host']);

        $queries = parse_url($trackingData['path'], PHP_URL_QUERY);
        $data = [];
        parse_str($queries, $queries);
        parse_str($queries['data'], $data);

        $this->assertEquals('CID01401', $data['scode']);
        $this->assertEquals('201706200000006646', $data['orderid']);
        $this->assertEquals('11569c36a47f7c1ee73304a956fdfd1a', $data['sign']);
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

        $telepay = new Telepay();
        $telepay->withdrawPayment();
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

        $telepay = new Telepay();
        $telepay->setPrivateKey('jy9CV6uguTE=');

        $telepay->setOptions($sourceData);
        $telepay->withdrawPayment();
    }

    /**
     * 測試出款但返回結果缺少參數
     */
    public function testWithdrawButNoWithdrawReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No withdraw return parameter specified',
            150180209
        );

        $sourceData = [
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '0.01',
            'orderId' => '112332',
            'number' => 'CID01401',
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"prc":"-1", "msg":"DF orderid error","tradeno":""}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $telepay = new Telepay();
        $telepay->setContainer($this->container);
        $telepay->setClient($this->client);
        $telepay->setResponse($response);
        $telepay->setPrivateKey('12345');
        $telepay->setOptions($sourceData);
        $telepay->withdrawPayment();
    }

    /**
     * 測試出款返回異常
     */
    public function testWithdrawFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'DF orderid error',
            180124
        );

        $sourceData = [
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '0.01',
            'orderId' => '112332',
            'number' => 'CID01401',
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"prc":"-1","errcode":"16","msg":"DF orderid error","tradeno":""}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $telepay = new Telepay();
        $telepay->setContainer($this->container);
        $telepay->setClient($this->client);
        $telepay->setResponse($response);
        $telepay->setPrivateKey('12345');
        $telepay->setOptions($sourceData);
        $telepay->withdrawPayment();
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
            'number' => 'CID01401',
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"prc":"1","errcode":"00","msg":"SUCCESSED","tradeno":"DF20171005164832FDI"}';

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

        $telepay = new Telepay();
        $telepay->setContainer($mockContainer);
        $telepay->setClient($this->client);
        $telepay->setResponse($response);
        $telepay->setPrivateKey('12345');
        $telepay->setOptions($sourceData);
        $telepay->withdrawPayment();
    }
}
