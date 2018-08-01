<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\YTBao;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class YTBaoTest extends DurianTestCase
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

        $yTBao = new YTBao();
        $yTBao->getVerifyData();
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

        $yTBao = new YTBao();
        $yTBao->setPrivateKey('test');
        $yTBao->getVerifyData();
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
            'number' => '900520172770001',
            'amount' => '100',
            'orderId' => '201707250000003580',
            'paymentVendorId' => '9453',
            'notify_url' => 'http://pay.return/',
        ];

        $yTBao = new YTBao();
        $yTBao->setPrivateKey('test');
        $yTBao->setOptions($options);
        $yTBao->getVerifyData();
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
            'number' => '900520172770001',
            'amount' => '9453',
            'orderId' => '201703220000001407',
            'paymentVendorId' => '1103',
            'notify_url' => 'http://pay.return/',
            'verify_url' => '',
        ];

        $yTBao = new YTBao();
        $yTBao->setPrivateKey('test');
        $yTBao->setOptions($options);
        $yTBao->getVerifyData();
    }

    /**
     * 測試支付時沒有返回respCode
     */
    public function testPayReturnWithoutRespCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '900520172770001',
            'amount' => '0.01',
            'orderId' => '201703220000001407',
            'paymentVendorId' => '1103',
            'notify_url' => 'http://pay.return/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"barCode":"weixin://wxpay/bizpayurl?pr=cLys8Hm","merchno":"900520172770001","message":"下单成功",' .
            '"refno":"02170322000081213163","traceno":"201703220000001407"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $yTBao = new YTBao();
        $yTBao->setContainer($this->container);
        $yTBao->setClient($this->client);
        $yTBao->setResponse($response);
        $yTBao->setPrivateKey('test');
        $yTBao->setOptions($options);
        $yTBao->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '交易失败,找不到二维码路由信息',
            180130
        );

        $options = [
            'number' => '900520172770001',
            'amount' => '0.1',
            'orderId' => '201703240000001427',
            'paymentVendorId' => '1103',
            'notify_url' => 'http://pay.return/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"merchno":"900520172770001","message":"交易失败,找不到二维码路由信息","refno":"02170324000081299151",' .
            '"respCode":"0001","traceno":"201703240000001427"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $yTBao = new YTBao();
        $yTBao->setContainer($this->container);
        $yTBao->setClient($this->client);
        $yTBao->setResponse($response);
        $yTBao->setPrivateKey('test');
        $yTBao->setOptions($options);
        $yTBao->getVerifyData();
    }

    /**
     * 測試支付時沒有返回barCode
     */
    public function testPayReturnWithoutBarCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '900520172770001',
            'amount' => '0.01',
            'orderId' => '201703220000001407',
            'paymentVendorId' => '1103',
            'notify_url' => 'http://pay.return/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"merchno":"900520172770001","message":"下单成功","refno":"02170322000081213163","respCode":"00",' .
            '"traceno":"201703220000001407"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $yTBao = new YTBao();
        $yTBao->setContainer($this->container);
        $yTBao->setClient($this->client);
        $yTBao->setResponse($response);
        $yTBao->setPrivateKey('test');
        $yTBao->setOptions($options);
        $yTBao->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'number' => '900520172770001',
            'amount' => '0.01',
            'orderId' => '201703220000001407',
            'paymentVendorId' => '1103',
            'notify_url' => 'http://pay.return/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"barCode":"weixin://wxpay/bizpayurl?pr=cLys8Hm","merchno":"900520172770001","message":"下单成功",' .
            '"refno":"02170322000081213163","respCode":"00","traceno":"201703220000001407"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $yTBao = new YTBao();
        $yTBao->setContainer($this->container);
        $yTBao->setClient($this->client);
        $yTBao->setResponse($response);
        $yTBao->setPrivateKey('test');
        $yTBao->setOptions($options);
        $data = $yTBao->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=cLys8Hm', $yTBao->getQrcode());
    }

    /**
     * 測試手機支付
     */
    public function testPayWithWap()
    {
        $options = [
            'number' => '900140440110001',
            'amount' => '0.01',
            'orderId' => '201709010000006882',
            'paymentVendorId' => '1098',
            'notify_url' => 'http://pay.return/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"barCode":"http://api.ytbao.net/url?r=32170901100015864890","merchno":"900140440110001",' .
            '"message":"下单成功","refno":"32170901100015864890","respCode":"00","traceno":"201709010000006882"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $yTBao = new YTBao();
        $yTBao->setContainer($this->container);
        $yTBao->setClient($this->client);
        $yTBao->setResponse($response);
        $yTBao->setPrivateKey('test');
        $yTBao->setOptions($options);
        $data = $yTBao->getVerifyData();

        $this->assertEquals('http://api.ytbao.net/url?r=32170901100015864890', $data['act_url']);
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

        $yTBao = new YTBao();
        $yTBao->verifyOrderPayment([]);
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

        $yTBao = new YTBao();
        $yTBao->setPrivateKey('test');
        $yTBao->verifyOrderPayment([]);
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
            'amount' => '0.10',
            'merchName' => '贵州峰以容',
            'merchno' => '900520172770001',
            'orderno' => '32170725100013097831',
            'payType' => '2',
            'status' => '1',
            'traceno' => '201707250000003580',
            'transDate' => '2017-07-25',
            'transTime' => '16:56:38',
        ];

        $yTBao = new YTBao();
        $yTBao->setPrivateKey('test');
        $yTBao->setOptions($options);
        $yTBao->verifyOrderPayment([]);
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
            'amount' => '0.10',
            'merchName' => '贵州峰以容',
            'merchno' => '900520172770001',
            'orderno' => '32170725100013097831',
            'payType' => '2',
            'signature' => 'C799B9BE286A738961BB297118B283F0',
            'status' => '1',
            'traceno' => '201707250000003580',
            'transDate' => '2017-07-25',
            'transTime' => '16:56:38',
        ];

        $yTBao = new YTBao();
        $yTBao->setPrivateKey('test');
        $yTBao->setOptions($options);
        $yTBao->verifyOrderPayment([]);
    }

    /**
     * 測試返回時訂單未支付
     */
    public function testReturnWithUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $options = [
            'amount' => '0.10',
            'merchName' => '贵州峰以容',
            'merchno' => '900520172770001',
            'orderno' => '32170725100013097831',
            'payType' => '2',
            'signature' => '42627E2AA6C1DE436EA85CA2F7C62C81',
            'status' => '0',
            'traceno' => '201707250000003580',
            'transDate' => '2017-07-25',
            'transTime' => '16:56:38',
        ];

        $yTBao = new YTBao();
        $yTBao->setPrivateKey('test');
        $yTBao->setOptions($options);
        $yTBao->verifyOrderPayment([]);
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
            'amount' => '0.10',
            'merchName' => '贵州峰以容',
            'merchno' => '900520172770001',
            'orderno' => '32170725100013097831',
            'payType' => '2',
            'signature' => '6E04EE87AD1B4011760604A8DBAD3636',
            'status' => '2',
            'traceno' => '201707250000003580',
            'transDate' => '2017-07-25',
            'transTime' => '16:56:38',
        ];

        $yTBao = new YTBao();
        $yTBao->setPrivateKey('test');
        $yTBao->setOptions($options);
        $yTBao->verifyOrderPayment([]);
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
            'amount' => '0.10',
            'merchName' => '贵州峰以容',
            'merchno' => '900520172770001',
            'orderno' => '32170725100013097831',
            'payType' => '2',
            'signature' => '4284E60F781C7DE4BA1D0021D525C5CB',
            'status' => '1',
            'traceno' => '201707250000003580',
            'transDate' => '2017-07-25',
            'transTime' => '16:56:38',
        ];

        $entry = ['id' => '9453'];

        $yTBao = new YTBao();
        $yTBao->setPrivateKey('test');
        $yTBao->setOptions($options);
        $yTBao->verifyOrderPayment($entry);
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
            'amount' => '0.10',
            'merchName' => '贵州峰以容',
            'merchno' => '900520172770001',
            'orderno' => '32170725100013097831',
            'payType' => '2',
            'signature' => '4284E60F781C7DE4BA1D0021D525C5CB',
            'status' => '1',
            'traceno' => '201707250000003580',
            'transDate' => '2017-07-25',
            'transTime' => '16:56:38',
        ];

        $entry = [
            'id' => '201707250000003580',
            'amount' => '1',
        ];

        $yTBao = new YTBao();
        $yTBao->setPrivateKey('test');
        $yTBao->setOptions($options);
        $yTBao->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $options = [
            'amount' => '0.10',
            'merchName' => '贵州峰以容',
            'merchno' => '900520172770001',
            'orderno' => '32170725100013097831',
            'payType' => '2',
            'signature' => '4284E60F781C7DE4BA1D0021D525C5CB',
            'status' => '1',
            'traceno' => '201707250000003580',
            'transDate' => '2017-07-25',
            'transTime' => '16:56:38',
        ];

        $entry = [
            'id' => '201707250000003580',
            'amount' => '0.10',
        ];

        $yTBao = new YTBao();
        $yTBao->setPrivateKey('test');
        $yTBao->setOptions($options);
        $yTBao->verifyOrderPayment($entry);

        $this->assertEquals('success', $yTBao->getMsg());
    }
}
