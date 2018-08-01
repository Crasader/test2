<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\JinYang;
use Buzz\Message\Response;

class JinYangTest extends DurianTestCase
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
            ->will($this->returnValue(null));

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

        $jinYang = new JinYang();
        $jinYang->getVerifyData();
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

        $jinYang = new JinYang();
        $jinYang->setPrivateKey('test');
        $jinYang->getVerifyData();
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

        $sourceData = [
            'number' => '22225',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '999',
            'orderId' => '201712040000002745',
            'amount' => '0.01',
        ];

        $jinYang = new JinYang();
        $jinYang->setPrivateKey('test');
        $jinYang->setOptions($sourceData);
        $jinYang->getVerifyData();
    }

    /**
     * 測試二維支付時沒有帶入verify_url的情況
     */
    public function testQrCodePayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => '22225',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1090',
            'orderId' => '201712040000002745',
            'amount' => '0.01',
            'verify_url' => '',
        ];

        $jinYang = new JinYang();
        $jinYang->setPrivateKey('test');
        $jinYang->setOptions($sourceData);
        $jinYang->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回retCode
     */
    public function testQrCodePayReturnWithoutRetCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => '22225',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1090',
            'orderId' => '201712040000002745',
            'amount' => '0.01',
            'verify_url' => 'payment.http.pay.095pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"data":null,"rspMsg":"不能支付一元！"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $jinYang = new JinYang();
        $jinYang->setContainer($this->container);
        $jinYang->setClient($this->client);
        $jinYang->setResponse($response);
        $jinYang->setPrivateKey('test');
        $jinYang->setOptions($sourceData);
        $jinYang->getVerifyData();
    }

    /**
     * 測試二維支付時返回提交失敗
     */
    public function testQRcodePayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '不能支付一元！',
            180130
        );

        $sourceData = [
            'number' => '22225',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1090',
            'orderId' => '201712040000002745',
            'amount' => '0.01',
            'verify_url' => 'payment.http.pay.095pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"data":null,"rspCode":1019,"rspMsg":"不能支付一元！"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $jinYang = new JinYang();
        $jinYang->setContainer($this->container);
        $jinYang->setClient($this->client);
        $jinYang->setResponse($response);
        $jinYang->setPrivateKey('test');
        $jinYang->setOptions($sourceData);
        $jinYang->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回r6_qrcode
     */
    public function testQrCodePayReturnWithoutQrcode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => '22225',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1090',
            'orderId' => '201712040000002745',
            'amount' => '0.01',
            'verify_url' => 'payment.http.pay.095pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"data":{"r1_mchtid":22225,"r2_systemorderno":"jy2293177","r3_orderno":"201712040000002768",' .
            '"r4_amount":3.0,"r5_version":"v2.8","r7_paytype":"WEIXIN",' .
            '"sign":"c1114ee52d748968d01d55738539d337"},"rspCode":1,"rspMsg":"下单成功!"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $jinYang = new JinYang();
        $jinYang->setContainer($this->container);
        $jinYang->setClient($this->client);
        $jinYang->setResponse($response);
        $jinYang->setPrivateKey('test');
        $jinYang->setOptions($sourceData);
        $jinYang->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrCodePay()
    {
        $sourceData = [
            'number' => '22225',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1090',
            'orderId' => '201712040000002745',
            'amount' => '0.01',
            'verify_url' => 'payment.http.pay.095pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"data":{"r1_mchtid":22225,"r2_systemorderno":"jy2293177","r3_orderno":"201712040000002768",' .
            '"r4_amount":3.0,"r5_version":"v2.8","r6_qrcode":"http://pay.095pay.com/zfapi/order/getqrcode?' .
            'orderid=2293177&sign=BD99BA03425F5BF7883B6154027F7D2A","r7_paytype":"WEIXIN",' .
            '"sign":"c1114ee52d748968d01d55738539d337"},"rspCode":1,"rspMsg":"下单成功!"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $jinYang = new JinYang();
        $jinYang->setContainer($this->container);
        $jinYang->setClient($this->client);
        $jinYang->setResponse($response);
        $jinYang->setPrivateKey('test');
        $jinYang->setOptions($sourceData);
        $data = $jinYang->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals(
            '<img src="http://pay.095pay.com/zfapi/order/getqrcode?orderid=2293177&' .
            'sign=BD99BA03425F5BF7883B6154027F7D2A"/>',
            $jinYang->getHtml()
        );
    }

    /**
     * 測試網銀
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '22225',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '1',
            'orderId' => '201712040000002745',
            'amount' => '0.01',
            'verify_url' => 'payment.http.pay.095pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $jinYang = new JinYang();
        $jinYang->setPrivateKey('test');
        $jinYang->setOptions($sourceData);
        $data = $jinYang->getVerifyData();

        $this->assertEquals('22225', $data['p1_mchtid']);
        $this->assertEquals('ICBC', $data['p2_paytype']);
        $this->assertEquals('0.01', $data['p3_paymoney']);
        $this->assertEquals('201712040000002745', $data['p4_orderno']);
        $this->assertEquals('http://pay.my/pay/return.php', $data['p5_callbackurl']);
        $this->assertEquals('', $data['p6_notifyurl']);
        $this->assertEquals('v2.8', $data['p7_version']);
        $this->assertEquals('1', $data['p8_signtype']);
        $this->assertEquals('', $data['p9_attach']);
        $this->assertEquals('', $data['p10_appname']);
        $this->assertEquals('1', $data['p11_isshow']);
        $this->assertEquals('', $data['p12_orderip']);
        $this->assertArrayNotHasKey('p13_memberid', $data);
        $this->assertEquals('f1f845b3e8066ae7d069589140c2cace', $data['sign']);
    }

    /**
     * 測試銀聯在線
     */
    public function testQuickPay()
    {
        $sourceData = [
            'number' => '22225',
            'notify_url' => 'http://pay.my/pay/return.php',
            'paymentVendorId' => '278',
            'orderId' => '201712040000002745',
            'amount' => '0.01',
            'username' => 'Matthew',
            'verify_url' => 'payment.http.pay.095pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $jinYang = new JinYang();
        $jinYang->setPrivateKey('test');
        $jinYang->setOptions($sourceData);
        $data = $jinYang->getVerifyData();

        $this->assertEquals('22225', $data['p1_mchtid']);
        $this->assertEquals('FASTPAY', $data['p2_paytype']);
        $this->assertEquals('0.01', $data['p3_paymoney']);
        $this->assertEquals('201712040000002745', $data['p4_orderno']);
        $this->assertEquals('http://pay.my/pay/return.php', $data['p5_callbackurl']);
        $this->assertEquals('', $data['p6_notifyurl']);
        $this->assertEquals('v2.8', $data['p7_version']);
        $this->assertEquals('1', $data['p8_signtype']);
        $this->assertEquals('', $data['p9_attach']);
        $this->assertEquals('', $data['p10_appname']);
        $this->assertEquals('1', $data['p11_isshow']);
        $this->assertEquals('', $data['p12_orderip']);
        $this->assertEquals('Matthew', $data['p13_memberid']);
        $this->assertEquals('be7e70fc117d59c068291c0e1ef891df', $data['sign']);
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

        $jinYang = new JinYang();
        $jinYang->verifyOrderPayment([]);
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

        $jinYang = new JinYang();
        $jinYang->setPrivateKey('test');
        $jinYang->verifyOrderPayment([]);
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
            'partner' => '22225',
            'ordernumber' => '201712040000002745',
            'orderstatus' => '1',
            'paymoney' => '0.0100',
            'sysnumber' => '20171204145816384516165',
            'attach' => '',
        ];

        $jinYang = new JinYang();
        $jinYang->setPrivateKey('test');
        $jinYang->setOptions($options);
        $jinYang->verifyOrderPayment([]);
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
            'partner' => '22225',
            'ordernumber' => '201712040000002745',
            'orderstatus' => '1',
            'paymoney' => '0.0100',
            'sysnumber' => '20171204145816384516165',
            'attach' => '',
            'sign' => 'c845130ec5e08f7a1345822e07cb1ef0',
        ];

        $jinYang = new JinYang();
        $jinYang->setPrivateKey('test');
        $jinYang->setOptions($options);
        $jinYang->verifyOrderPayment([]);
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
            'partner' => '22225',
            'ordernumber' => '201712040000002745',
            'orderstatus' => '2',
            'paymoney' => '0.0100',
            'sysnumber' => '20171204145816384516165',
            'attach' => '',
            'sign' => 'fe5ada19e9cd9651e6b7759ba383bc58',
        ];

        $jinYang = new JinYang();
        $jinYang->setPrivateKey('test');
        $jinYang->setOptions($options);
        $jinYang->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單單號錯誤
     */
    public function testReturnOrderIdFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $options = [
            'partner' => '22225',
            'ordernumber' => '201712040000002745',
            'orderstatus' => '1',
            'paymoney' => '0.0100',
            'sysnumber' => '20171204145816384516165',
            'attach' => '',
            'sign' => '3aab3da7164a82d3deab089e9fd921be',
        ];

        $entry = [
            'id' => '201712040000002746',
            'amount' => '0.01',
        ];

        $jinYang = new JinYang();
        $jinYang->setPrivateKey('test');
        $jinYang->setOptions($options);
        $jinYang->verifyOrderPayment($entry);
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
            'partner' => '22225',
            'ordernumber' => '201712040000002745',
            'orderstatus' => '1',
            'paymoney' => '0.0100',
            'sysnumber' => '20171204145816384516165',
            'attach' => '',
            'sign' => '3aab3da7164a82d3deab089e9fd921be',
        ];

        $entry = [
            'id' => '201712040000002745',
            'amount' => '10',
        ];

        $jinYang = new JinYang();
        $jinYang->setPrivateKey('test');
        $jinYang->setOptions($options);
        $jinYang->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'partner' => '22225',
            'ordernumber' => '201712040000002745',
            'orderstatus' => '1',
            'paymoney' => '0.0100',
            'sysnumber' => '20171204145816384516165',
            'attach' => '',
            'sign' => '3aab3da7164a82d3deab089e9fd921be',
        ];

        $entry = [
            'id' => '201712040000002745',
            'amount' => '0.01',
        ];

        $jinYang = new JinYang();
        $jinYang->setPrivateKey('test');
        $jinYang->setOptions($options);
        $jinYang->verifyOrderPayment($entry);

        $this->assertEquals('ok', $jinYang->getMsg());
    }
}
