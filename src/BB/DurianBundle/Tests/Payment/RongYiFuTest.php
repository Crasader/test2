<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\RongYiFu;
use Buzz\Message\Response;

class RongYiFuTest extends DurianTestCase
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
     * 測試支付加密時沒有帶入privateKey的情況
     */
    public function testPayWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $rongYiFu = new RongYiFu();
        $rongYiFu->getVerifyData();
    }

    /**
     * 測試支付加密時未指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = ['number' => ''];

        $rongYiFu = new RongYiFu();
        $rongYiFu->setPrivateKey('test');
        $rongYiFu->setOptions($sourceData);
        $rongYiFu->getVerifyData();
    }

    /**
     * 測試支付時加密時帶入支付平台不支援的銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '1001757',
            'paymentVendorId' => '999',
            'amount' => '0.01',
            'orderId' => '201612140000000663',
            'notify_url' => 'http://two123.comxa.com/',
        ];

        $rongYiFu = new RongYiFu();
        $rongYiFu->setPrivateKey('test');
        $rongYiFu->setOptions($sourceData);
        $rongYiFu->getVerifyData();
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

        $sourceData = [
            'number' => '1001757',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201612140000000663',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => '',
        ];

        $rongYiFu = new RongYiFu();
        $rongYiFu->setPrivateKey('test');
        $rongYiFu->setOptions($sourceData);
        $rongYiFu->getVerifyData();
    }

    /**
     * 測試微信支付時返回qrcode格式錯誤
     */
    public function testPayReturnWithWrongWeixinQrcode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '接口维护中，我们会尽快处理好，感谢理解',
            180130
        );

        $result = '接口维护中，我们会尽快处理好，感谢理解';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html;');

        $sourceData = [
            'number' => '1001757',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201612140000000663',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.api.r1pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $rongYiFu = new RongYiFu();
        $rongYiFu->setContainer($this->container);
        $rongYiFu->setClient($this->client);
        $rongYiFu->setResponse($response);
        $rongYiFu->setPrivateKey('test');
        $rongYiFu->setOptions($sourceData);
        $rongYiFu->getVerifyData();
    }

    /**
     * 測試支付寶支付時返回qrcode格式錯誤
     */
    public function testPayReturnWithWrongAlipayQrcode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '接口维护，我们将很快恢复，请稍后再试ERROR',
            180130
        );

        $result = '接口维护，我们将很快恢复，请稍后再试ERROR';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html;');

        $sourceData = [
            'number' => '1001757',
            'paymentVendorId' => '1092',
            'amount' => '0.01',
            'orderId' => '201612140000000663',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.api.r1pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $rongYiFu = new RongYiFu();
        $rongYiFu->setContainer($this->container);
        $rongYiFu->setClient($this->client);
        $rongYiFu->setResponse($response);
        $rongYiFu->setPrivateKey('test');
        $rongYiFu->setOptions($sourceData);
        $rongYiFu->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $result = 'weixin://wxpay/bizpayurl?pr=P3iDZBg';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html;');

        $sourceData = [
            'number' => '1001757',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201612140000000663',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.api.r1pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $rongYiFu = new RongYiFu();
        $rongYiFu->setContainer($this->container);
        $rongYiFu->setClient($this->client);
        $rongYiFu->setResponse($response);
        $rongYiFu->setPrivateKey('test');
        $rongYiFu->setOptions($sourceData);
        $data = $rongYiFu->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=P3iDZBg', $rongYiFu->getQrcode());
    }

    /**
     * 測試返回時沒有帶入key的情況
     */
    public function testReturnWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $rongYiFu = new RongYiFu();
        $rongYiFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台未指定返回參數
     */
    public function testReturnWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = ['result' => ''];

        $rongYiFu = new RongYiFu();
        $rongYiFu->setPrivateKey('test');
        $rongYiFu->setOptions($sourceData);
        $rongYiFu->verifyOrderPayment([]);
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
            'userid' => '1001757',
            'orderid' => '201612140000000663',
            'btype' => '83',
            'result' => '2000',
            'value' => '0.01',
            'realvalue' => '0.01',
        ];

        $rongYiFu = new RongYiFu();
        $rongYiFu->setPrivateKey('test');
        $rongYiFu->setOptions($sourceData);
        $rongYiFu->verifyOrderPayment([]);
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
            'userid' => '1001757',
            'orderid' => '201612140000000663',
            'btype' => '83',
            'result' => '2000',
            'value' => '0.01',
            'realvalue' => '0.01',
            'sign' => '000002a6c0d59149b81ce68ae5b99999',
        ];

        $rongYiFu = new RongYiFu();
        $rongYiFu->setPrivateKey('test');
        $rongYiFu->setOptions($sourceData);
        $rongYiFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付失敗
     */
    public function testReturnPaymentFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $sourceData = [
            'userid' => '1001757',
            'orderid' => '201612140000000663',
            'btype' => '83',
            'result' => '2001',
            'value' => '0.01',
            'realvalue' => '0.01',
            'sign' => 'e7563a18b3cf2911985d01fb52f5cd0b',
        ];

        $rongYiFu = new RongYiFu();
        $rongYiFu->setPrivateKey('test');
        $rongYiFu->setOptions($sourceData);
        $rongYiFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時單號不正確的情況
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'userid' => '1001757',
            'orderid' => '201612140000099999',
            'btype' => '83',
            'result' => '2000',
            'value' => '0.01',
            'realvalue' => '0.01',
            'sign' => '85a3742d3d38875d08c31736e5781b59',
        ];

        $entry = ['id' => '201612140000000663'];

        $rongYiFu = new RongYiFu();
        $rongYiFu->setPrivateKey('test');
        $rongYiFu->setOptions($sourceData);
        $rongYiFu->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時單號不正確的情況
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'userid' => '1001757',
            'orderid' => '201612140000000663',
            'btype' => '83',
            'result' => '2000',
            'value' => '1.00',
            'realvalue' => '1.00',
            'sign' => 'fa7a7ddbcb952de568f3ce57adf3cf66',
        ];

        $entry = [
            'id' => '201612140000000663',
            'amount' => '0.01',
        ];

        $rongYiFu = new RongYiFu();
        $rongYiFu->setPrivateKey('test');
        $rongYiFu->setOptions($sourceData);
        $rongYiFu->verifyOrderPayment($entry);
    }

    /**
     * 測試返回成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'userid' => '1001757',
            'orderid' => '201612140000000663',
            'btype' => '83',
            'result' => '2000',
            'value' => '0.01',
            'realvalue' => '0.01',
            'sign' => 'a8b122a6c0d59149b81ce68ae5b7a9de',
        ];

        $entry = [
            'id' => '201612140000000663',
            'amount' => '0.01',
        ];

        $rongYiFu = new RongYiFu();
        $rongYiFu->setPrivateKey('test');
        $rongYiFu->setOptions($sourceData);
        $rongYiFu->verifyOrderPayment($entry);

        $this->assertEquals('ok', $rongYiFu->getMsg());
    }

    /**
     * 測試訂單查詢缺少私鑰
     */
    public function testTrackingWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $rongYiFu = new RongYiFu();
        $rongYiFu->paymentTracking();
    }

    /**
     * 測試訂單查詢加密未指定查詢參數
     */
    public function testTrackingWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $rongYiFu = new RongYiFu();
        $rongYiFu->setPrivateKey('test');
        $rongYiFu->paymentTracking();
    }

    /**
     * 測試訂單查詢加密沒有帶入verify_url的情況
     */
    public function testTrackingWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => '1001757',
            'orderId' => '201612140000000663',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $rongYiFu = new RongYiFu();
        $rongYiFu->setPrivateKey('test');
        $rongYiFu->setOptions($sourceData);
        $rongYiFu->paymentTracking();
    }

    /**
     * 測試訂單查詢對外支付查詢結果未指定返回參數
     */
    public function testTrackingReturnNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = 'checkcode=3002&message=订单未完成';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html;');

        $sourceData = [
            'number' => '1001757',
            'orderId' => '201612140000000663',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.r1pay.com',
        ];

        $rongYiFu = new RongYiFu();
        $rongYiFu->setContainer($this->container);
        $rongYiFu->setClient($this->client);
        $rongYiFu->setResponse($response);
        $rongYiFu->setPrivateKey('test');
        $rongYiFu->setOptions($sourceData);
        $rongYiFu->paymentTracking();
    }

    /**
     * 測試訂單查詢結果回傳訂單不存在
     */
    public function testTrackingReturnOrderNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );

        $result = 'checkcode=0&realmoney=0&message=没有订单记录';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html;');

        $sourceData = [
            'number' => '1001757',
            'orderId' => '201612140000000663',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.r1pay.com',
        ];

        $rongYiFu = new RongYiFu();
        $rongYiFu->setContainer($this->container);
        $rongYiFu->setClient($this->client);
        $rongYiFu->setResponse($response);
        $rongYiFu->setPrivateKey('test');
        $rongYiFu->setOptions($sourceData);
        $rongYiFu->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單處理中
     */
    public function testTrackingReturnPaymentTrackingFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $result = 'checkcode=3002&realmoney=0&message=订单未完成';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html;');

        $sourceData = [
            'number' => '1001757',
            'orderId' => '201612140000000663',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.r1pay.com',
        ];

        $rongYiFu = new RongYiFu();
        $rongYiFu->setContainer($this->container);
        $rongYiFu->setClient($this->client);
        $rongYiFu->setResponse($response);
        $rongYiFu->setPrivateKey('test');
        $rongYiFu->setOptions($sourceData);
        $rongYiFu->paymentTracking();
    }

    /**
     * 測試訂單查詢結果簽名驗證錯誤
     */
    public function testTrackingReturnMerchantSignError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Merchant sign error',
            180127
        );

        $result = 'checkcode=3003&realmoney=0&message=信息签名有误';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html;');

        $sourceData = [
            'number' => '1001757',
            'orderId' => '201612140000000663',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.r1pay.com',
        ];

        $rongYiFu = new RongYiFu();
        $rongYiFu->setContainer($this->container);
        $rongYiFu->setClient($this->client);
        $rongYiFu->setResponse($response);
        $rongYiFu->setPrivateKey('test');
        $rongYiFu->setOptions($sourceData);
        $rongYiFu->paymentTracking();
    }

   /**
     * 測試訂單查詢結果結果失敗
     */
    public function testTrackingReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $result = 'checkcode=3001&realmoney=0&message=失败';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html;');

        $sourceData = [
            'number' => '1001757',
            'orderId' => '201612140000000663',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.r1pay.com',
        ];

        $rongYiFu = new RongYiFu();
        $rongYiFu->setContainer($this->container);
        $rongYiFu->setClient($this->client);
        $rongYiFu->setResponse($response);
        $rongYiFu->setPrivateKey('test');
        $rongYiFu->setOptions($sourceData);
        $rongYiFu->paymentTracking();
    }

   /**
     * 測試訂單查詢結果金額錯誤
     */
    public function testTrackingReturnWithAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $result = 'checkcode=3000&realmoney=1.00&message=成功';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html;');

        $sourceData = [
            'number' => '1001757',
            'orderId' => '201612140000000663',
            'amount' => '0.01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.r1pay.com',
        ];

        $rongYiFu = new RongYiFu();
        $rongYiFu->setContainer($this->container);
        $rongYiFu->setClient($this->client);
        $rongYiFu->setResponse($response);
        $rongYiFu->setPrivateKey('test');
        $rongYiFu->setOptions($sourceData);
        $rongYiFu->paymentTracking();
    }

   /**
     * 測試訂單查詢結果結果成功
     */
    public function testTrackingSuccess()
    {
        $result = 'checkcode=3000&realmoney=0.01&message=成功';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html;');

        $sourceData = [
            'number' => '1001757',
            'orderId' => '201612140000000663',
            'amount' => '0.01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.api.r1pay.com',
        ];

        $rongYiFu = new RongYiFu();
        $rongYiFu->setContainer($this->container);
        $rongYiFu->setClient($this->client);
        $rongYiFu->setResponse($response);
        $rongYiFu->setPrivateKey('test');
        $rongYiFu->setOptions($sourceData);
        $rongYiFu->paymentTracking();
    }
}
