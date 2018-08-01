<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\LefuPay;
use Buzz\Message\Response;

class LefuPayTest extends DurianTestCase
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

        $lefupay = new LefuPay();
        $lefupay->getVerifyData();
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

        $lefupay = new Lefupay();
        $lefupay->setPrivateKey('test');
        $lefupay->getVerifyData();
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
            'number' => '2D8162A16682973574EB57507393B51D',
            'orderId' => '201805310000011613',
            'amount' => '1',
            'paymentVendorId' => '9999',
            'notify_url' => 'http://orz.zz/',
        ];

        $lefupay = new Lefupay();
        $lefupay->setPrivateKey('test');
        $lefupay->setOptions($options);
        $lefupay->getVerifyData();
    }

    /**
     * 測試微信二維支付時缺少verify_url
     */
    public function testQrcodePayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '2D8162A16682973574EB57507393B51D',
            'orderId' => '201805310000011613',
            'amount' => '1',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://orz.zz/',
            'verify_url' => '',
        ];

        $lefupay = new Lefupay();
        $lefupay->setPrivateKey('test');
        $lefupay->setOptions($options);
        $lefupay->getVerifyData();
    }

    /**
     * 測試微信二維支付時返回缺少success
     */
    public function testQrcodePayReturnWithoutSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [];

        $options = [
            'number' => '2D8162A16682973574EB57507393B51D',
            'orderId' => '201805310000011613',
            'amount' => '1',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://orz.zz/',
            'verify_url' => 'http://orz.zz/',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $lefupay = new Lefupay();
        $lefupay->setPrivateKey('test');
        $lefupay->setContainer($this->container);
        $lefupay->setClient($this->client);
        $lefupay->setResponse($response);
        $lefupay->setOptions($options);
        $lefupay->getVerifyData();
    }

    /**
     * 測試微信二維支付時返回提交失敗
     */
    public function testQrcodePayReturnFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '下单失败',
            180130
        );

        $result = [
            'success' => false,
            'msg' => '下单失败',
        ];

        $options = [
            'number' => '2D8162A16682973574EB57507393B51D',
            'orderId' => '201805310000011613',
            'amount' => '1',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://orz.zz/',
            'verify_url' => 'http://orz.zz/',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $lefupay = new Lefupay();
        $lefupay->setPrivateKey('test');
        $lefupay->setContainer($this->container);
        $lefupay->setClient($this->client);
        $lefupay->setResponse($response);
        $lefupay->setOptions($options);
        $lefupay->getVerifyData();
    }

    /**
     * 測試微信二維支付時返回缺少data
     */
    public function testQrcodePayReturnWithoutData()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'success' => true,
            'msg' => '下单成功',
        ];

        $options = [
            'number' => '2D8162A16682973574EB57507393B51D',
            'orderId' => '201805310000011613',
            'amount' => '1',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://orz.zz/',
            'verify_url' => 'http://orz.zz/',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $lefupay = new Lefupay();
        $lefupay->setPrivateKey('test');
        $lefupay->setContainer($this->container);
        $lefupay->setClient($this->client);
        $lefupay->setResponse($response);
        $lefupay->setOptions($options);
        $lefupay->getVerifyData();
    }
    /**
     * 測試微信二維
     */
    public function testQrcodePay()
    {
        $result = [
            'success' => true,
            'msg' => '下单成功',
            'data' => 'http://pay.leefupay.com/pay/order/pay/page?orderNumber=201805310000011613',
        ];

        $options = [
            'number' => '2D8162A16682973574EB57507393B51D',
            'orderId' => '201805310000011613',
            'amount' => '1',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://orz.zz/',
            'verify_url' => 'http://orz.zz/',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $lefupay = new Lefupay();
        $lefupay->setPrivateKey('test');
        $lefupay->setContainer($this->container);
        $lefupay->setClient($this->client);
        $lefupay->setResponse($response);
        $lefupay->setOptions($options);
        $verifyData = $lefupay->getVerifyData();

        $this->assertEquals('GET', $lefupay->getPayMethod());
        $this->assertEquals('201805310000011613', $verifyData['params']['orderNumber']);
        $this->assertEquals('http://pay.leefupay.com/pay/order/pay/page', $verifyData['post_url']);
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

        $lefupay = new Lefupay();
        $lefupay->verifyOrderPayment([]);
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

        $lefupay = new Lefupay();
        $lefupay->setPrivateKey('test');
        $lefupay->verifyOrderPayment([]);
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

        $options = [
            'success' => true,
            'orderNumber' => '201805310000011613',
            'money' => '1',
            'payDate' => '1527838707000',
            'remark' => '',
        ];

        $lefupay = new Lefupay();
        $lefupay->setPrivateKey('test');
        $lefupay->setOptions($options);
        $lefupay->verifyOrderPayment([]);
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
            'success' => true,
            'orderNumber' => '201805310000011613',
            'money' => '1',
            'payDate' => '1527838707000',
            'remark' => '',
            'signature' => '123456789',
        ];

        $lefupay = new Lefupay();
        $lefupay->setPrivateKey('test');
        $lefupay->setOptions($options);
        $lefupay->verifyOrderPayment([]);
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

        $options = [
            'success' => false,
            'orderNumber' => '201805310000011613',
            'money' => '1',
            'payDate' => '1527838707000',
            'remark' => '',
            'signature' => '06038902A72E5A9EF42A0E71EF63326B',
        ];

        $lefupay = new Lefupay();
        $lefupay->setPrivateKey('test');
        $lefupay->setOptions($options);
        $lefupay->verifyOrderPayment([]);
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
            'success' => true,
            'orderNumber' => '201805310000011613',
            'money' => '1',
            'payDate' => '1527838707000',
            'remark' => '',
            'signature' => 'E24335B9380803C6803B8C1C7EFFC24C',
        ];

        $entry = ['id' => '301805240000011487'];

        $lefupay = new Lefupay();
        $lefupay->setPrivateKey('test');
        $lefupay->setOptions($options);
        $lefupay->verifyOrderPayment($entry);
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
            'success' => true,
            'orderNumber' => '201805310000011613',
            'money' => '1',
            'payDate' => '1527838707000',
            'remark' => '',
            'signature' => 'E24335B9380803C6803B8C1C7EFFC24C',
        ];

        $entry = [
            'id' => '201805310000011613',
            'amount' => '15',
        ];

        $lefupay = new Lefupay();
        $lefupay->setPrivateKey('test');
        $lefupay->setOptions($options);
        $lefupay->verifyOrderPayment($entry);
    }
    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $options = [
            'success' => true,
            'orderNumber' => '201805310000011613',
            'money' => '1',
            'payDate' => '1527838707000',
            'remark' => '',
            'signature' => 'E24335B9380803C6803B8C1C7EFFC24C',
        ];

        $entry = [
            'id' => '201805310000011613',
            'amount' => '1',
        ];

        $lefupay = new Lefupay();
        $lefupay->setPrivateKey('test');
        $lefupay->setOptions($options);
        $lefupay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $lefupay->getMsg());
    }
}
