<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YangPuTao;
use Buzz\Message\Response;

class YangPuTaoTest extends DurianTestCase
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

        $yangPuTao = new YangPuTao();
        $yangPuTao->getVerifyData();
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

        $yangPuTao = new YangPuTao();
        $yangPuTao->setPrivateKey('test');
        $yangPuTao->setOptions($sourceData);
        $yangPuTao->getVerifyData();
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
            'number' => '10180029730927',
            'paymentVendorId' => '9999',
            'amount' => '1.00',
            'orderId' => '201805080000012774',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'ip' => '111.235.135.54',
            'orderCreateDate' => '2018-05-08 11:45:55',
        ];

        $yangPuTao = new YangPuTao();
        $yangPuTao->setPrivateKey('test');
        $yangPuTao->setOptions($sourceData);
        $yangPuTao->getVerifyData();
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
            'number' => '10180029730927',
            'paymentVendorId' => '1103',
            'amount' => '1.00',
            'orderId' => '201805080000012774',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => '',
            'ip' => '111.235.135.54',
            'orderCreateDate' => '2018-05-08 11:45:55',
        ];

        $yangPuTao = new YangPuTao();
        $yangPuTao->setPrivateKey('test');
        $yangPuTao->setOptions($sourceData);
        $yangPuTao->getVerifyData();
    }

    /**
     * 測試支付時未返回resultCode
     */
    public function testPayNoReturnResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'code' => '0',
            'msg' => '提交成功',
            'success' => 'true',
            'bizSeqNo' => '180508172253H508713735373435',
            'transactionTime' => '20180508172253',
            'data' =>  [
                'orderSn' => '201805080000012774',
                'url' => 'https://qpay.qq.com/qr/591a3bb5',
                'remark' => '2018050817225188946',
                'totalAmount' => '1',
                'sign' => '1E6673A653DF5D7ECDB2101182D1F816',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/x-www-form-urlencoded;charset=UTF-8');

        $sourceData = [
            'number' => '10180029730927',
            'paymentVendorId' => '1103',
            'amount' => '1.00',
            'orderId' => '201805080000012774',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'ip' => '111.235.135.54',
            'orderCreateDate' => '2018-05-08 11:45:55',
        ];

        $yangPuTao = new YangPuTao();
        $yangPuTao->setContainer($this->container);
        $yangPuTao->setClient($this->client);
        $yangPuTao->setResponse($response);
        $yangPuTao->setPrivateKey('test');
        $yangPuTao->setOptions($sourceData);
        $yangPuTao->getVerifyData();
    }

    /**
     * 測試支付失敗
     */
    public function testPayFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $result = [
            'code' => '0',
            'msg' => '提交成功',
            'success' => 'true',
            'bizSeqNo' => '180508172253H508713735373435',
            'transactionTime' => '20180508172253',
            'data' =>  [
                'msg' => '未知错误',
                'resultCode' => 'FAIL',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/x-www-form-urlencoded;charset=UTF-8');

        $sourceData = [
            'number' => '10180029730927',
            'paymentVendorId' => '1103',
            'amount' => '1.00',
            'orderId' => '201805080000012774',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'ip' => '111.235.135.54',
            'orderCreateDate' => '2018-05-08 11:45:55',
        ];

        $yangPuTao = new YangPuTao();
        $yangPuTao->setContainer($this->container);
        $yangPuTao->setClient($this->client);
        $yangPuTao->setResponse($response);
        $yangPuTao->setPrivateKey('test');
        $yangPuTao->setOptions($sourceData);
        $yangPuTao->getVerifyData();
    }

    /**
     * 測試支付時未返回url
     */
    public function testPayNoReturnUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'code' => '0',
            'msg' => '提交成功',
            'success' => 'true',
            'bizSeqNo' => '180508172253H508713735373435',
            'transactionTime' => '20180508172253',
            'data' =>  [
                'orderSn' => '201805080000012774',
                'remark' => '2018050817225188946',
                'totalAmount' => '1',
                'resultCode' => 'SUCCESS',
                'sign' => '1E6673A653DF5D7ECDB2101182D1F816',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/x-www-form-urlencoded;charset=UTF-8');

        $sourceData = [
            'number' => '10180029730927',
            'paymentVendorId' => '1103',
            'amount' => '1.00',
            'orderId' => '201805080000012774',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'ip' => '111.235.135.54',
            'orderCreateDate' => '2018-05-08 11:45:55',
        ];

        $yangPuTao = new YangPuTao();
        $yangPuTao->setContainer($this->container);
        $yangPuTao->setClient($this->client);
        $yangPuTao->setResponse($response);
        $yangPuTao->setPrivateKey('test');
        $yangPuTao->setOptions($sourceData);
        $yangPuTao->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $result = [
            'code' => '0',
            'msg' => '提交成功',
            'success' => 'true',
            'bizSeqNo' => '180508172253H508713735373435',
            'transactionTime' => '20180508172253',
            'data' =>  [
                'orderSn' => '201805080000012774',
                'url' => 'https://qpay.qq.com/qr/591a3bb5',
                'remark' => '2018050817225188946',
                'totalAmount' => '1',
                'resultCode' => 'SUCCESS',
                'sign' => '1E6673A653DF5D7ECDB2101182D1F816',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/x-www-form-urlencoded;charset=UTF-8');

        $sourceData = [
            'number' => '10180029730927',
            'paymentVendorId' => '1103',
            'amount' => '1.00',
            'orderId' => '201805080000012774',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'ip' => '111.235.135.54',
            'orderCreateDate' => '2018-05-08 11:45:55',
        ];

        $yangPuTao = new YangPuTao();
        $yangPuTao->setContainer($this->container);
        $yangPuTao->setClient($this->client);
        $yangPuTao->setResponse($response);
        $yangPuTao->setPrivateKey('test');
        $yangPuTao->setOptions($sourceData);
        $yangPuTao->getVerifyData();
        $data = $yangPuTao->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://qpay.qq.com/qr/591a3bb5', $yangPuTao->getQrcode());
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

        $yangPuTao = new YangPuTao();
        $yangPuTao->verifyOrderPayment([]);
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

        $yangPuTao = new YangPuTao();
        $yangPuTao->setPrivateKey('test');
        $yangPuTao->setOptions($sourceData);
        $yangPuTao->verifyOrderPayment([]);
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
            'orderSn' => '201805080000012774',
            'remark' => '2018050817225188946',
            'totalAmount' => '1.00',
            'transTime' => '1525771373',
        ];

        $yangPuTao = new YangPuTao();
        $yangPuTao->setPrivateKey('test');
        $yangPuTao->setOptions($sourceData);
        $yangPuTao->verifyOrderPayment([]);
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
            'orderSn' => '201805080000012774',
            'remark' => '2018050817225188946',
            'totalAmount' => '1.00',
            'transTime' => '1525771373',
            'sign' => '89A1F5152B8759C04CA7ECBF684E7059',
        ];

        $yangPuTao = new YangPuTao();
        $yangPuTao->setPrivateKey('test');
        $yangPuTao->setOptions($sourceData);
        $yangPuTao->verifyOrderPayment([]);
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
            'orderSn' => '201805080000012774',
            'remark' => '2018050817225188946',
            'totalAmount' => '1.00',
            'transTime' => '1525771373',
            'sign' => '324F46B1E878016D365BCD041019DB6E',
        ];

        $entry = ['id' => '201704100000002210'];

        $yangPuTao = new YangPuTao();
        $yangPuTao->setPrivateKey('test');
        $yangPuTao->setOptions($sourceData);
        $yangPuTao->verifyOrderPayment($entry);
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
            'orderSn' => '201805080000012774',
            'remark' => '2018050817225188946',
            'totalAmount' => '1.00',
            'transTime' => '1525771373',
            'sign' => '324F46B1E878016D365BCD041019DB6E',
        ];

        $entry = [
            'id' => '201805080000012774',
            'amount' => '100',
        ];

        $yangPuTao = new YangPuTao();
        $yangPuTao->setPrivateKey('test');
        $yangPuTao->setOptions($sourceData);
        $yangPuTao->verifyOrderPayment($entry);
    }

    /**
     * 測試返回成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'orderSn' => '201805080000012774',
            'remark' => '2018050817225188946',
            'totalAmount' => '1.00',
            'transTime' => '1525771373',
            'sign' => '324F46B1E878016D365BCD041019DB6E',
        ];

        $entry = [
            'id' => '201805080000012774',
            'amount' => '1.00',
        ];

        $yangPuTao = new YangPuTao();
        $yangPuTao->setPrivateKey('test');
        $yangPuTao->setOptions($sourceData);
        $yangPuTao->verifyOrderPayment($entry);

        $this->assertEquals('0000', $yangPuTao->getMsg());
    }
}
