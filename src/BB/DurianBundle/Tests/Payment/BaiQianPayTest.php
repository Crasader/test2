<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\BaiQianPay;
use Buzz\Message\Response;

class BaiQianPayTest extends DurianTestCase
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

        $baiQianPay = new BaiQianPay();
        $baiQianPay->getVerifyData();
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

        $sourceData = ['X1_Amount' => ''];

        $baiQianPay = new BaiQianPay();
        $baiQianPay->setPrivateKey('test');
        $baiQianPay->setOptions($sourceData);
        $baiQianPay->getVerifyData();
    }

    /**
     * 測試支付時帶入支付平台不支援的銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '2017052444010020',
            'paymentVendorId' => '9999',
            'amount' => '0.01',
            'orderId' => '201710160000001536',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.api.baiqianpay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $baiQianPay = new BaiQianPay();
        $baiQianPay->setPrivateKey('test');
        $baiQianPay->setOptions($sourceData);
        $baiQianPay->getVerifyData();
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

        $options = [
            'number' => '2017052444010020',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201710160000001536',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $baiQianPay = new BaiQianPay();
        $baiQianPay->setPrivateKey('test');
        $baiQianPay->setOptions($options);
        $baiQianPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回缺少msg
     */
    public function testPayReturnWithoutMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '{"status":88,"money":"1.00","order_id":"201804020000004673",' .
            '"imgUrl":"https://qr.95516.com/00010000/62232648030224944851723654227542",' .
            '"para":{"X1_Amount":"1.00","X2_BillNo":"201804020000004673","X3_MerNo":"1626399298",' .
            '"X4_ReturnURL":"https://tingliu.000webhostapp.com/pay/return.php","X5_NotifyURL":"",' .
            '"X6_MD5info":"8378FE9633F1FD3C377797D6FECB02C6","X7_PaymentType":"BSM","X8_MerRemark":"",' .
            '"isApp":"","ip":"111.235.135.54"}}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'number' => '2017052444010020',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201710160000001536',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.bq.baiqianpay.com',
        ];

        $baiQianPay = new BaiQianPay();
        $baiQianPay->setPrivateKey('test');
        $baiQianPay->setContainer($this->container);
        $baiQianPay->setClient($this->client);
        $baiQianPay->setResponse($response);
        $baiQianPay->setOptions($options);
        $baiQianPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回缺少status
     */
    public function testPayReturnWithoutStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '{"money":"1.00","order_id":"201804020000004673",' .
            '"imgUrl":"https://qr.95516.com/00010000/62232648030224944851723654227542",' .
            '"para":{"X1_Amount":"1.00","X2_BillNo":"201804020000004673","X3_MerNo":"1626399298",' .
            '"X4_ReturnURL":"https://tingliu.000webhostapp.com/pay/return.php","X5_NotifyURL":"",' .
            '"X6_MD5info":"8378FE9633F1FD3C377797D6FECB02C6","X7_PaymentType":"BSM","X8_MerRemark":"",' .
            '"isApp":"","ip":"111.235.135.54"},"msg":"success"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'number' => '2017052444010020',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201710160000001536',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.bq.baiqianpay.com',
        ];

        $baiQianPay = new BaiQianPay();
        $baiQianPay->setPrivateKey('test');
        $baiQianPay->setContainer($this->container);
        $baiQianPay->setClient($this->client);
        $baiQianPay->setResponse($response);
        $baiQianPay->setOptions($options);
        $baiQianPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回結果失敗
     */
    public function testPayReturnButFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '该渠道已关闭',
            180130
        );

        $result = '{"status":-1,"msg":"该渠道已关闭"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'number' => '2017052444010020',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201710160000001536',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.bq.baiqianpay.com',
        ];

        $baiQianPay = new BaiQianPay();
        $baiQianPay->setPrivateKey('test');
        $baiQianPay->setContainer($this->container);
        $baiQianPay->setClient($this->client);
        $baiQianPay->setResponse($response);
        $baiQianPay->setOptions($options);
        $baiQianPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回缺少imgUrl
     */
    public function testPayReturnWithoutImgUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '{"status":88,"money":"1.00","order_id":"201804020000004673",' .
            '"para":{"X1_Amount":"1.00","X2_BillNo":"201804020000004673","X3_MerNo":"1626399298",' .
            '"X4_ReturnURL":"https://tingliu.000webhostapp.com/pay/return.php","X5_NotifyURL":"",' .
            '"X6_MD5info":"8378FE9633F1FD3C377797D6FECB02C6","X7_PaymentType":"BSM","X8_MerRemark":"",' .
            '"isApp":"","ip":"111.235.135.54"},"msg":"success"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'number' => '2017052444010020',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201710160000001536',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.bq.baiqianpay.com',
        ];

        $baiQianPay = new BaiQianPay();
        $baiQianPay->setPrivateKey('test');
        $baiQianPay->setContainer($this->container);
        $baiQianPay->setClient($this->client);
        $baiQianPay->setResponse($response);
        $baiQianPay->setOptions($options);
        $baiQianPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $result = '{"status":88,"money":"1.00","order_id":"201804020000004673",' .
            '"imgUrl":"weixin://wxpay/bizpayurl?pr=2686C9Q",' .
            '"para":{"X1_Amount":"1.00","X2_BillNo":"201804020000004673","X3_MerNo":"1626399298",' .
            '"X4_ReturnURL":"https://tingliu.000webhostapp.com/pay/return.php","X5_NotifyURL":"",' .
            '"X6_MD5info":"8378FE9633F1FD3C377797D6FECB02C6","X7_PaymentType":"BSM","X8_MerRemark":"",' .
            '"isApp":"","ip":"111.235.135.54"},"msg":"success"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'number' => '2017052444010020',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201710160000001536',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.bq.baiqianpay.com',
        ];

        $baiQianPay = new BaiQianPay();
        $baiQianPay->setPrivateKey('test');
        $baiQianPay->setContainer($this->container);
        $baiQianPay->setClient($this->client);
        $baiQianPay->setResponse($response);
        $baiQianPay->setOptions($options);
        $verifyData = $baiQianPay->getVerifyData();

        $this->assertEmpty($verifyData);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=2686C9Q', $baiQianPay->getQrcode());
    }

    /**
     * 測試支付
     */
    public function testBankPay()
    {
        $sourceData = [
            'number' => '2017052444010020',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201710160000001536',
            'notify_url' => 'http://pay.my/pay/return.php',
        ];

        $baiQianPay = new BaiQianPay();
        $baiQianPay->setPrivateKey('test');
        $baiQianPay->setOptions($sourceData);
        $verifyData = $baiQianPay->getVerifyData();

        $this->assertEquals('0.01', $verifyData['X1_Amount']);
        $this->assertEquals('201710160000001536', $verifyData['X2_BillNo']);
        $this->assertEquals('2017052444010020', $verifyData['X3_MerNo']);
        $this->assertEquals('http://pay.my/pay/return.php', $verifyData['X4_ReturnURL']);
        $this->assertEquals('9DB0B7C693018D6FD0B1F37427900C1D', $verifyData['X6_MD5info']);
        $this->assertEquals('ICBC', $verifyData['X7_PaymentType']);
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

        $baiQianPay = new BaiQianPay();
        $baiQianPay->verifyOrderPayment([]);
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

        $baiQianPay = new BaiQianPay();
        $baiQianPay->setPrivateKey('test');
        $baiQianPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳MD5info
     */
    public function testReturnWithoutMD5info()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'Amount' => '0.01',
            'BillNo' => '201710160000001536',
            'MerNo' => '2017052444010020',
            'Succeed' => '88',
        ];

        $baiQianPay = new BaiQianPay();
        $baiQianPay->setPrivateKey('test');
        $baiQianPay->setOptions($sourceData);
        $baiQianPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時MD5info簽名驗證錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $sourceData = [
            'Amount' => '0.01',
            'BillNo' => '201710160000001536',
            'MerNo' => '2017052444010020',
            'MD5info' => '1234',
            'Succeed' => '88',
        ];

        $baiQianPay = new BaiQianPay();
        $baiQianPay->setPrivateKey('test');
        $baiQianPay->setOptions($sourceData);
        $baiQianPay->verifyOrderPayment([]);
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
            'MerNo' => '2017090633010146',
            'BillNo' => '201710050000001416',
            'Amount' => '0.01',
            'Succeed' => '-1',
            'MD5info' => '29D554597979F6F36101B014D7C269E7',
        ];

        $baiQianPay = new BaiQianPay();
        $baiQianPay->setPrivateKey('test');
        $baiQianPay->setOptions($sourceData);
        $baiQianPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單編號錯誤
     */
    public function testReturnOrderIdFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'MerNo' => '2017090633010146',
            'BillNo' => '201710050000001416',
            'Amount' => '0.01',
            'Succeed' => '88',
            'MD5info' => '82A3532CAA1688CF9CFD6870FE799C34',
        ];

        $entry = [
            'id' => '201710050000001417',
            'amount' => '0.01',
        ];

        $baiQianPay = new BaiQianPay();
        $baiQianPay->setPrivateKey('test');
        $baiQianPay->setOptions($sourceData);
        $baiQianPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為訂單金額錯誤
     */
    public function testReturnAmountFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'MerNo' => '2017090633010146',
            'BillNo' => '201710050000001416',
            'Amount' => '0.01',
            'Succeed' => '88',
            'MD5info' => '82A3532CAA1688CF9CFD6870FE799C34',
        ];

        $entry = [
            'id' => '201710050000001416',
            'amount' => '1.00',
        ];

        $baiQianPay = new BaiQianPay();
        $baiQianPay->setPrivateKey('test');
        $baiQianPay->setOptions($sourceData);
        $baiQianPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'MerNo' => '2017090633010146',
            'BillNo' => '201710050000001416',
            'Amount' => '0.01',
            'Succeed' => '88',
            'MD5info' => '82A3532CAA1688CF9CFD6870FE799C34',
        ];

        $entry = [
            'id' => '201710050000001416',
            'amount' => '0.01',
        ];

        $baiQianPay = new BaiQianPay();
        $baiQianPay->setPrivateKey('test');
        $baiQianPay->setOptions($sourceData);
        $baiQianPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $baiQianPay->getMsg());
    }
}