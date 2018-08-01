<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YiTong;
use Buzz\Message\Response;

class YiTongTest extends DurianTestCase
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

        $this->client = $this->getMockBuilder('Buzz\Client\Curl')
            ->getMock();
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

        $yiTong = new YiTong();
        $yiTong->getVerifyData();
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

        $yiTong = new YiTong();
        $yiTong->setPrivateKey('test');
        $yiTong->getVerifyData();
    }

    /**
     * 測試支付時帶入不支援的銀行
     */
    public function testPayWithUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $option = [
            'number' => '9453',
            'orderId' => '201802070000009453',
            'orderCreateDate' => '2018-02-07 16:00:00',
            'amount' => '1.9453',
            'notify_url' => 'http://www.seafood.help/',
            'username' => 'seafood',
            'paymentVendorId' => '9999',
        ];

        $yiTong = new YiTong();
        $yiTong->setPrivateKey('test');
        $yiTong->setOptions($option);
        $yiTong->getVerifyData();
    }

    /**
     * 測試二維支付時缺少verify_url
     */
    public function testPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $option = [
            'number' => '9453',
            'orderId' => '201802070000009453',
            'orderCreateDate' => '2018-02-07 16:00:00',
            'amount' => '1.9453',
            'notify_url' => 'http://www.seafood.help/',
            'username' => 'seafood',
            'paymentVendorId' => '1090',
            'verify_url' => '',
        ];

        $yiTong = new YiTong();
        $yiTong->setPrivateKey('test');
        $yiTong->setOptions($option);
        $yiTong->getVerifyData();
    }

    /**
     * 測試二維支付時缺少code
     */
    public function testPayReturnWithoutCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = ['msg' => '您没有开通该支付方式'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $option = [
            'number' => '9453',
            'orderId' => '201802070000009453',
            'orderCreateDate' => '2018-02-07 16:00:00',
            'amount' => '1.9453',
            'notify_url' => 'http://www.seafood.help/',
            'username' => 'seafood',
            'ip' => '127.0.0.1',
            'paymentVendorId' => '1090',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'http://www.seafood.help.you/',
        ];

        $yiTong = new YiTong();
        $yiTong->setPrivateKey('test');
        $yiTong->setContainer($this->container);
        $yiTong->setClient($this->client);
        $yiTong->setResponse($response);
        $yiTong->setOptions($option);
        $yiTong->getVerifyData();
    }

    /**
     * 測試二維支付時返回錯誤訊息
     */
    public function testPayReturnError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '您没有开通该支付方式',
            180130
        );

        $result = [
            'code' => '205',
            'msg' => '您没有开通该支付方式',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $option = [
            'number' => '9453',
            'orderId' => '201802070000009453',
            'orderCreateDate' => '2018-02-07 16:00:00',
            'amount' => '1.9453',
            'notify_url' => 'http://www.seafood.help/',
            'username' => 'seafood',
            'ip' => '127.0.0.1',
            'paymentVendorId' => '1090',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'http://www.seafood.help.you/',
        ];

        $yiTong = new YiTong();
        $yiTong->setPrivateKey('test');
        $yiTong->setContainer($this->container);
        $yiTong->setClient($this->client);
        $yiTong->setResponse($response);
        $yiTong->setOptions($option);
        $yiTong->getVerifyData();
    }

    /**
     * 測試二維支付時缺少data
     */
    public function testPayReturnWithoutData()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'code' => '100',
            'msg' => '',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $option = [
            'number' => '9453',
            'orderId' => '201802070000009453',
            'orderCreateDate' => '2018-02-07 16:00:00',
            'amount' => '1.9453',
            'notify_url' => 'http://www.seafood.help/',
            'username' => 'seafood',
            'ip' => '127.0.0.1',
            'paymentVendorId' => '1090',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'http://www.seafood.help.you/',
        ];

        $yiTong = new YiTong();
        $yiTong->setPrivateKey('test');
        $yiTong->setContainer($this->container);
        $yiTong->setClient($this->client);
        $yiTong->setResponse($response);
        $yiTong->setOptions($option);
        $yiTong->getVerifyData();
    }

    /**
     * 測試二維支付時缺少payCode
     */
    public function testPayReturnWithoutPayCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $data = [];

        $result = [
            'code' => '100',
            'msg' => '请求成功',
            'data' => $data,
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $option = [
            'number' => '9453',
            'orderId' => '201802070000009453',
            'orderCreateDate' => '2018-02-07 16:00:00',
            'amount' => '1.9453',
            'notify_url' => 'http://www.seafood.help/',
            'username' => 'seafood',
            'ip' => '127.0.0.1',
            'paymentVendorId' => '1090',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'http://www.seafood.help.you/',
        ];

        $yiTong = new YiTong();
        $yiTong->setPrivateKey('test');
        $yiTong->setContainer($this->container);
        $yiTong->setClient($this->client);
        $yiTong->setResponse($response);
        $yiTong->setOptions($option);
        $yiTong->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testScanPay()
    {
        $data = [
            'payCode' => 'weixin://wxpay/bizpayurl?pr=5p9IBEE',
            'orderNo' => '201802070000009453',
            'merchParam' => '',
        ];

        $result = [
            'code' => '100',
            'msg' => '请求成功',
            'data' => $data,
            'sign' => 'dc3d444bbf9eefaec7daf475dbc387ae',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $option = [
            'number' => '9453',
            'orderId' => '201802070000009453',
            'orderCreateDate' => '2018-02-07 16:00:00',
            'amount' => '1.9453',
            'notify_url' => 'http://www.seafood.help/',
            'username' => 'seafood',
            'ip' => '127.0.0.1',
            'paymentVendorId' => '1090',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'http://www.seafood.help.you/',
        ];

        $yiTong = new YiTong();
        $yiTong->setPrivateKey('test');
        $yiTong->setContainer($this->container);
        $yiTong->setClient($this->client);
        $yiTong->setResponse($response);
        $yiTong->setOptions($option);
        $encodeData = $yiTong->getVerifyData();

        $this->assertEmpty($encodeData);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=5p9IBEE', $yiTong->getQrcode());
    }

    /**
     * 測試返回時沒有私鑰
     */
    public function testReturnWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $yiTong = new YiTong();
        $yiTong->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未指定參數
     */
    public function testReturnWithNoParameterSpecefied()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $yiTong = new YiTong();
        $yiTong->setPrivateKey('test');
        $yiTong->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少加密簽名
     */
    public function testReturnWithoutSignature()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $option = [
            'API' => 'PAYMENT',
            'merchNo' => 'FT1001001',
            'orderNo' => '201803130000002135',
            'Amt' => '1',
            'status' => '1',
            'merchParam' => '',
            'notify_time' => '2018-03-13 14:42:50',
        ];

        $yiTong = new YiTong();
        $yiTong->setPrivateKey('test');
        $yiTong->setOptions($option);
        $yiTong->verifyOrderPayment([]);
    }

    /**
     * 測試返回時簽名驗證錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180134
        );

        $option = [
            'API' => 'PAYMENT',
            'merchNo' => 'FT1001001',
            'orderNo' => '201803130000002135',
            'Amt' => '1',
            'status' => '1',
            'merchParam' => '',
            'notify_time' => '2018-03-13 14:42:50',
            'sign' => '',
        ];

        $yiTong = new YiTong();
        $yiTong->setPrivateKey('test');
        $yiTong->setOptions($option);
        $yiTong->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付失敗
     */
    public function testReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180135
        );

        $option = [
            'API' => 'PAYMENT',
            'merchNo' => 'FT1001001',
            'orderNo' => '201803130000002135',
            'Amt' => '1',
            'status' => '0',
            'merchParam' => '',
            'notify_time' => '2018-03-13 14:42:50',
            'sign' => '187a29ba2307d1ef30d4cbb12219313c',
        ];

        $yiTong = new YiTong();
        $yiTong->setPrivateKey('test');
        $yiTong->setOptions($option);
        $yiTong->verifyOrderPayment([]);
    }

    /**
     * 測試返回時訂單號不正確
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $option = [
            'API' => 'PAYMENT',
            'merchNo' => 'FT1001001',
            'orderNo' => '201803130000002135',
            'Amt' => '1',
            'status' => '1',
            'merchParam' => '',
            'notify_time' => '2018-03-13 14:42:50',
            'sign' => '75d65f66bc541c9260071e455e52066b',
        ];

        $entry = ['id' => '201803130000002136'];

        $yiTong = new YiTong();
        $yiTong->setPrivateKey('test');
        $yiTong->setOptions($option);
        $yiTong->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時金額不正確
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $option = [
            'API' => 'PAYMENT',
            'merchNo' => 'FT1001001',
            'orderNo' => '201803130000002135',
            'Amt' => '1',
            'status' => '1',
            'merchParam' => '',
            'notify_time' => '2018-03-13 14:42:50',
            'sign' => '75d65f66bc541c9260071e455e52066b',
        ];

        $entry = [
            'id' => '201803130000002135',
            'amount' => '1',
        ];

        $yiTong = new YiTong();
        $yiTong->setPrivateKey('test');
        $yiTong->setOptions($option);
        $yiTong->verifyOrderPayment($entry);
    }

    /**
     * 測試返回支付成功
     */
    public function testReturnResultSuccess()
    {
        $option = [
            'API' => 'PAYMENT',
            'merchNo' => 'FT1001001',
            'orderNo' => '201803130000002135',
            'Amt' => '1',
            'status' => '1',
            'merchParam' => '',
            'notify_time' => '2018-03-13 14:42:50',
            'sign' => '75d65f66bc541c9260071e455e52066b',
        ];

        $entry = [
            'id' => '201803130000002135',
            'amount' => '0.01',
        ];

        $yiTong = new YiTong();
        $yiTong->setPrivateKey('test');
        $yiTong->setOptions($option);
        $yiTong->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $yiTong->getMsg());
    }
}
