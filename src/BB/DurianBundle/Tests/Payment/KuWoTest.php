<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\KuWo;
use Buzz\Message\Response;

class KuWoTest extends DurianTestCase
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

        $kuWo = new KuWo();
        $kuWo->getVerifyData();
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

        $kuWo = new KuWo();
        $kuWo->setPrivateKey('test');
        $kuWo->getVerifyData();
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
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '100',
            'number' => '16965',
            'orderId' => '201709150000007037',
            'amount' => '100',
        ];

        $kuWo = new KuWo();
        $kuWo->setPrivateKey('test');
        $kuWo->setOptions($options);
        $kuWo->getVerifyData();
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

        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1103',
            'number' => '11111111',
            'orderId' => '201803230000011336',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2018-03-23 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $kuWo = new KuWo();
        $kuWo->setPrivateKey('test');
        $kuWo->setOptions($options);
        $kuWo->getVerifyData();
    }

    /**
     * 測試支付時返回缺少code
     */
    public function testPayReturnWithoutCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '{"trxMerchantNo":"11111111"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1103',
            'number' => '11111111',
            'orderId' => '201803230000011336',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2018-03-23 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $kuWo = new KuWo();
        $kuWo->setPrivateKey('test');
        $kuWo->setContainer($this->container);
        $kuWo->setClient($this->client);
        $kuWo->setResponse($response);
        $kuWo->setOptions($options);
        $kuWo->getVerifyData();
    }

    /**
     * 測試支付時返回code失敗代碼
     */
    public function testPayReturnCodeFail()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '订单不存在',
            180130
        );

        $result = '{"code":"10007", "message":"订单不存在"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1103',
            'number' => '11111111',
            'orderId' => '201803230000011336',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2018-03-23 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $kuWo = new KuWo();
        $kuWo->setPrivateKey('test');
        $kuWo->setContainer($this->container);
        $kuWo->setClient($this->client);
        $kuWo->setResponse($response);
        $kuWo->setOptions($options);
        $kuWo->getVerifyData();
    }

    /**
     * 測試支付時返回code失敗代碼時沒有message
     */
    public function testPayReturnCodeFailWithoutMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $result = '{"code":"10007"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1103',
            'number' => '11111111',
            'orderId' => '201803230000011336',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2018-03-23 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $kuWo = new KuWo();
        $kuWo->setPrivateKey('test');
        $kuWo->setContainer($this->container);
        $kuWo->setClient($this->client);
        $kuWo->setResponse($response);
        $kuWo->setOptions($options);
        $kuWo->getVerifyData();
    }

    /**
     * 測試支付時返回缺少payUrl
     */
    public function testPayReturnWithoutPayUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '{"code":"00000"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1103',
            'number' => '11111111',
            'orderId' => '201803230000011336',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2018-03-23 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $kuWo = new KuWo();
        $kuWo->setPrivateKey('test');
        $kuWo->setContainer($this->container);
        $kuWo->setClient($this->client);
        $kuWo->setResponse($response);
        $kuWo->setOptions($options);
        $kuWo->getVerifyData();
    }

    /**
     * 測試支付時返回payUrl缺少path
     */
    public function testPayReturnPayUrlWithoutPath()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '{"code":"00000", "message":"接口收单成功",' .
            '"payUrl":"http://saas.yeeyk.com"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1097',
            'number' => '11111111',
            'orderId' => '201803230000011336',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2018-03-23 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $kuWo = new KuWo();
        $kuWo->setPrivateKey('test');
        $kuWo->setContainer($this->container);
        $kuWo->setClient($this->client);
        $kuWo->setResponse($response);
        $kuWo->setOptions($options);
        $kuWo->getVerifyData();
    }

    /**
     * 測試手機支付
     */
    public function testWAPPay()
    {
        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1097',
            'number' => '11111111',
            'orderId' => '201803230000011336',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2018-03-23 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $result = '{"code":"00000", "message":"接口收单成功",' .
            '"payUrl":"http://saas.yeeyk.com/saas-trx-gateway/order/acceptOrder"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $kuWo = new KuWo();
        $kuWo->setPrivateKey('test');
        $kuWo->setContainer($this->container);
        $kuWo->setClient($this->client);
        $kuWo->setResponse($response);
        $kuWo->setOptions($options);
        $requestData = $kuWo->getVerifyData();

        $this->assertEquals('http://saas.yeeyk.com/saas-trx-gateway/order/acceptOrder', $requestData['post_url']);
        $this->assertEquals([], $requestData['params']);
        $this->assertEquals('GET', $kuWo->getPayMethod());
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1103',
            'number' => '11111111',
            'orderId' => '201803230000011336',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2018-03-23 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $result = '{"code":"00000", "message":"接口收单成功",' .
            '"payUrl":"http://fpay.yeeyk.com?payNo=20180321FP"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $kuWo = new KuWo();
        $kuWo->setPrivateKey('test');
        $kuWo->setContainer($this->container);
        $kuWo->setClient($this->client);
        $kuWo->setResponse($response);
        $kuWo->setOptions($options);
        $requestData = $kuWo->getVerifyData();

        $this->assertEquals('http://fpay.yeeyk.com?payNo=20180321FP', $kuWo->getQrcode());
        $this->assertEquals([], $requestData);
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

        $kuWo = new KuWo();
        $kuWo->verifyOrderPayment([]);
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

        $kuWo = new KuWo();
        $kuWo->setPrivateKey('test');
        $kuWo->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少簽名
     */
    public function testReturnWithoutHmac()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'result' => 'SUCCESS',
            'trxMerchantOrderno' => '201803150000007731',
            'amount' => '0.01',
            'trxMerchantNo' => '80066000242',
            'memberGoods' => '201803150000007731',
            'reCode' => '1',
            'productNo' => 'WXWAP-JS',
        ];

        $kuWo = new KuWo();
        $kuWo->setPrivateKey('test');
        $kuWo->setOptions($options);
        $kuWo->verifyOrderPayment([]);
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
            'result' => 'SUCCESS',
            'trxMerchantOrderno' => '201803150000007731',
            'amount' => '0.01',
            'trxMerchantNo' => '80066000242',
            'memberGoods' => '201803150000007731',
            'reCode' => '1',
            'productNo' => 'WXWAP-JS',
            'hmac' => '12345',
        ];

        $kuWo = new KuWo();
        $kuWo->setPrivateKey('test');
        $kuWo->setOptions($options);
        $kuWo->verifyOrderPayment([]);
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

        $options = [
            'result' => 'FAIL',
            'trxMerchantOrderno' => '201803150000007731',
            'amount' => '0.01',
            'trxMerchantNo' => '80066000242',
            'memberGoods' => '201803150000007731',
            'reCode' => '0',
            'productNo' => 'WXWAP-JS',
            'hmac' => 'dcd155de8500c4995b0423191798f6d9',
        ];

        $kuWo = new KuWo();
        $kuWo->setPrivateKey('test');
        $kuWo->setOptions($options);
        $kuWo->verifyOrderPayment([]);
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
            'result' => 'SUCCESS',
            'trxMerchantOrderno' => '201803150000007731',
            'amount' => '0.01',
            'trxMerchantNo' => '80066000242',
            'memberGoods' => '201803150000007731',
            'reCode' => '1',
            'productNo' => 'WXWAP-JS',
            'hmac' => '1304bcd82c31b15f9e7641f443e6e918',
        ];

        $entry = ['id' => '12345'];

        $kuWo = new KuWo();
        $kuWo->setPrivateKey('test');
        $kuWo->setOptions($options);
        $kuWo->verifyOrderPayment($entry);
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

        $options = [
            'result' => 'SUCCESS',
            'trxMerchantOrderno' => '201803150000007731',
            'amount' => '0.01',
            'trxMerchantNo' => '80066000242',
            'memberGoods' => '201803150000007731',
            'reCode' => '1',
            'productNo' => 'WXWAP-JS',
            'hmac' => '1304bcd82c31b15f9e7641f443e6e918',
        ];

        $entry = [
            'id' => '201803150000007731',
            'amount' => '15.00',
        ];

        $kuWo = new KuWo();
        $kuWo->setPrivateKey('test');
        $kuWo->setOptions($options);
        $kuWo->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnSuccess()
    {
        $options = [
            'result' => 'SUCCESS',
            'trxMerchantOrderno' => '201803150000007731',
            'amount' => '0.01',
            'trxMerchantNo' => '80066000242',
            'memberGoods' => '201803150000007731',
            'reCode' => '1',
            'productNo' => 'WXWAP-JS',
            'hmac' => '1304bcd82c31b15f9e7641f443e6e918',
        ];

        $entry = [
            'id' => '201803150000007731',
            'amount' => '0.01',
        ];

        $kuWo = new KuWo();
        $kuWo->setPrivateKey('test');
        $kuWo->setOptions($options);
        $kuWo->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $kuWo->getMsg());
    }
}
