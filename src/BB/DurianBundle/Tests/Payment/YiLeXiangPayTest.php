<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\YiLeXiangPay;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class YiLeXiangPayTest extends DurianTestCase
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

        $yiLeXiangPay = new YiLeXiangPay();
        $yiLeXiangPay->getVerifyData();
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

        $yiLeXiangPay = new YiLeXiangPay();
        $yiLeXiangPay->setPrivateKey('test');
        $yiLeXiangPay->getVerifyData();
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
            'number' => 'gl00024678',
            'amount' => '100',
            'orderId' => '201803070000010317',
            'paymentVendorId' => '9999',
            'notify_url' => 'http://two123.comxa.com/',
        ];

        $yiLeXiangPay = new YiLeXiangPay();
        $yiLeXiangPay->setPrivateKey('test');
        $yiLeXiangPay->setOptions($options);
        $yiLeXiangPay->getVerifyData();
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
            'number' => 'gl00024678',
            'amount' => '100',
            'orderId' => '201803070000010317',
            'paymentVendorId' => '1',
            'merchant_extra' => ['appId' => '7aee88cd4b654e2692664851b9603acf'],
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => '',
        ];

        $yiLeXiangPay = new YiLeXiangPay();
        $yiLeXiangPay->setPrivateKey('test');
        $yiLeXiangPay->setOptions($options);
        $yiLeXiangPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回code
     */
    public function testPayReturnWithoutCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => 'gl00024678',
            'amount' => '0.01',
            'orderId' => '201803070000010317',
            'paymentVendorId' => '1',
            'merchant_extra' => ['appId' => '7aee88cd4b654e2692664851b9603acf'],
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.https.open.goodluckchina.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"orderNo":"20180308140817109783542", ' .
            '"orderId":"e3acf9ad523f458e896e3aa32d9c677d","pay_url":"' .
            'http://www.lingfengzhuangshi.cn/chinaGPayGateway.html?orderNo' .
            '=20180308140817109783542"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yiLeXiangPay = new YiLeXiangPay();
        $yiLeXiangPay->setContainer($this->container);
        $yiLeXiangPay->setClient($this->client);
        $yiLeXiangPay->setResponse($response);
        $yiLeXiangPay->setPrivateKey('test');
        $yiLeXiangPay->setOptions($options);
        $yiLeXiangPay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '签名验证失败',
            180130
        );

        $options = [
            'number' => 'gl00024678',
            'amount' => '0.01',
            'orderId' => '201803070000010317',
            'paymentVendorId' => '1',
            'merchant_extra' => ['appId' => '7aee88cd4b654e2692664851b9603acf'],
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.https.open.goodluckchina.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"orderNo":"20180308140817109783542","msg":"签名验证失败","code":"50037"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yiLeXiangPay = new YiLeXiangPay();
        $yiLeXiangPay->setContainer($this->container);
        $yiLeXiangPay->setClient($this->client);
        $yiLeXiangPay->setResponse($response);
        $yiLeXiangPay->setPrivateKey('test');
        $yiLeXiangPay->setOptions($options);
        $yiLeXiangPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回pay_url
     */
    public function testPayReturnWithoutPayUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => 'gl00024678',
            'amount' => '0.01',
            'orderId' => '201803070000010317',
            'paymentVendorId' => '1',
            'merchant_extra' => ['appId' => '7aee88cd4b654e2692664851b9603acf'],
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.https.open.goodluckchina.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"code":1,"orderNo":"20180308140817109783542", ' .
            '"orderId":"e3acf9ad523f458e896e3aa32d9c677d"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yiLeXiangPay = new YiLeXiangPay();
        $yiLeXiangPay->setContainer($this->container);
        $yiLeXiangPay->setClient($this->client);
        $yiLeXiangPay->setResponse($response);
        $yiLeXiangPay->setPrivateKey('test');
        $yiLeXiangPay->setOptions($options);
        $yiLeXiangPay->getVerifyData();
    }

    /**
     * 測試網銀支付
     */
    public function testBankPay()
    {
        $options = [
            'number' => 'gl00024678',
            'amount' => '0.01',
            'orderId' => '201803070000010317',
            'paymentVendorId' => '1',
            'merchant_extra' => ['appId' => '7aee88cd4b654e2692664851b9603acf'],
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.https.open.goodluckchina.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"code":1,"orderNo":"20180308140817109783542", ' .
            '"orderId":"e3acf9ad523f458e896e3aa32d9c677d","pay_url":"' .
            'http://www.lingfengzhuangshi.cn/chinaGPayGateway.html?orderNo' .
            '=20180308140817109783542"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yiLeXiangPay = new YiLeXiangPay();
        $yiLeXiangPay->setContainer($this->container);
        $yiLeXiangPay->setClient($this->client);
        $yiLeXiangPay->setResponse($response);
        $yiLeXiangPay->setPrivateKey('test');
        $yiLeXiangPay->setOptions($options);
        $data = $yiLeXiangPay->getVerifyData();

        $this->assertEmpty($data['params']);
        $this->assertEquals(
            'http://www.lingfengzhuangshi.cn/chinaGPayGateway.html?orderNo=20180308140817109783542',
            $data['post_url']
        );
    }

    /**
     * 測試快捷支付
     */
    public function testQuickPay()
    {
        $options = [
            'number' => 'gl00024678',
            'amount' => '0.01',
            'orderId' => '201803070000010317',
            'paymentVendorId' => '278',
            'merchant_extra' => ['appId' => '7aee88cd4b654e2692664851b9603acf'],
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.https.open.goodluckchina.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"code":1,"orderNo":"20180308140817109783542", ' .
            '"orderId":"e3acf9ad523f458e896e3aa32d9c677d","pay_url":"' .
            'http://www.lingfengzhuangshi.cn/chinaGPayGateway.html?orderNo' .
            '=20180308140817109783542"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yiLeXiangPay = new YiLeXiangPay();
        $yiLeXiangPay->setContainer($this->container);
        $yiLeXiangPay->setClient($this->client);
        $yiLeXiangPay->setResponse($response);
        $yiLeXiangPay->setPrivateKey('test');
        $yiLeXiangPay->setOptions($options);
        $data = $yiLeXiangPay->getVerifyData();

        $this->assertEmpty($data['params']);
        $this->assertEquals(
            'http://www.lingfengzhuangshi.cn/chinaGPayGateway.html?orderNo=20180308140817109783542',
            $data['post_url']
        );
    }

    /**
     * 測試二維支付
     */
    public function testScanPay()
    {
        $options = [
            'number' => 'gl00024678',
            'amount' => '0.01',
            'orderId' => '201803070000010317',
            'paymentVendorId' => '1103',
            'merchant_extra' => ['appId' => '7aee88cd4b654e2692664851b9603acf'],
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.https.open.goodluckchina.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"code":1,"orderNo":"20180308140817109783542", ' .
            '"orderId":"e3acf9ad523f458e896e3aa32d9c677d","pay_url":"' .
            'https://qpay.qq.com/qr/6d6575a3"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yiLeXiangPay = new YiLeXiangPay();
        $yiLeXiangPay->setContainer($this->container);
        $yiLeXiangPay->setClient($this->client);
        $yiLeXiangPay->setResponse($response);
        $yiLeXiangPay->setPrivateKey('test');
        $yiLeXiangPay->setOptions($options);
        $data = $yiLeXiangPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://qpay.qq.com/qr/6d6575a3', $yiLeXiangPay->getQrcode());
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

        $yiLeXiangPay = new YiLeXiangPay();
        $yiLeXiangPay->verifyOrderPayment([]);
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

        $yiLeXiangPay = new YiLeXiangPay();
        $yiLeXiangPay->setPrivateKey('test');
        $yiLeXiangPay->verifyOrderPayment([]);
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
            'attach' => '201803080000010433',
            'cust_no' => 'gl00024678',
            'money' => '0.01',
            'order_id' => '8007b3ba574840458ec35b7f4145a5a5',
            'pay_channel' => '04',
            'pay_status' => 'success',
            'pay_time' => '',
            'plat_order_no' => '',
            'return_code' => 'SUCCESS',
            'return_msg' => '支付成功',
            'trade_no' => '20180308113259109772223',
        ];

        $yiLeXiangPay = new YiLeXiangPay();
        $yiLeXiangPay->setPrivateKey('test');
        $yiLeXiangPay->setOptions($options);
        $yiLeXiangPay->verifyOrderPayment([]);
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
            'attach' => '201803080000010433',
            'cust_no' => 'gl00024678',
            'money' => '0.01',
            'order_id' => '8007b3ba574840458ec35b7f4145a5a5',
            'pay_channel' => '04',
            'pay_status' => 'success',
            'pay_time' => '',
            'plat_order_no' => '',
            'return_code' => 'SUCCESS',
            'return_msg' => '支付成功',
            'trade_no' => '20180308113259109772223',
            'sign' => '5f6e326a6421162199c5294f680612c6',
        ];

        $yiLeXiangPay = new YiLeXiangPay();
        $yiLeXiangPay->setPrivateKey('1234');
        $yiLeXiangPay->setOptions($options);
        $yiLeXiangPay->verifyOrderPayment([]);
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
            'attach' => '201803080000010433',
            'cust_no' => 'gl00024678',
            'money' => '0.01',
            'order_id' => '8007b3ba574840458ec35b7f4145a5a5',
            'pay_channel' => '04',
            'pay_status' => '',
            'pay_time' => '',
            'plat_order_no' => '',
            'return_code' => 'SUCCESS',
            'return_msg' => '支付成功',
            'trade_no' => '20180308113259109772223',
            'sign' => 'd61e637dc74880fe701f587a0253e493',
        ];

        $yiLeXiangPay = new YiLeXiangPay();
        $yiLeXiangPay->setPrivateKey('test');
        $yiLeXiangPay->setOptions($options);
        $yiLeXiangPay->verifyOrderPayment([]);
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
            'attach' => '201803080000010433',
            'cust_no' => 'gl00024678',
            'money' => '0.01',
            'order_id' => '8007b3ba574840458ec35b7f4145a5a5',
            'pay_channel' => '04',
            'pay_status' => 'success',
            'pay_time' => '',
            'plat_order_no' => '',
            'return_code' => 'SUCCESS',
            'return_msg' => '支付成功',
            'trade_no' => '20180308113259109772223',
            'sign' => 'c35ca5e0101f149d3b885e81a5ac89ce',
        ];

        $entry = ['id' => '201707250000003581'];

        $yiLeXiangPay = new YiLeXiangPay();
        $yiLeXiangPay->setPrivateKey('test');
        $yiLeXiangPay->setOptions($options);
        $yiLeXiangPay->verifyOrderPayment($entry);
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
            'attach' => '201803080000010433',
            'cust_no' => 'gl00024678',
            'money' => '0.01',
            'order_id' => '8007b3ba574840458ec35b7f4145a5a5',
            'pay_channel' => '04',
            'pay_status' => 'success',
            'pay_time' => '',
            'plat_order_no' => '',
            'return_code' => 'SUCCESS',
            'return_msg' => '支付成功',
            'trade_no' => '20180308113259109772223',
            'sign' => 'c35ca5e0101f149d3b885e81a5ac89ce',
        ];

        $entry = [
            'id' => '201803080000010433',
            'amount' => '1.00',
        ];

        $yiLeXiangPay = new YiLeXiangPay();
        $yiLeXiangPay->setPrivateKey('test');
        $yiLeXiangPay->setOptions($options);
        $yiLeXiangPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $options = [
            'attach' => '201803080000010433',
            'cust_no' => 'gl00024678',
            'money' => '0.01',
            'order_id' => '8007b3ba574840458ec35b7f4145a5a5',
            'pay_channel' => '04',
            'pay_status' => 'success',
            'pay_time' => '',
            'plat_order_no' => '',
            'return_code' => 'SUCCESS',
            'return_msg' => '支付成功',
            'trade_no' => '20180308113259109772223',
            'sign' => 'c35ca5e0101f149d3b885e81a5ac89ce',
        ];

        $entry = [
            'id' => '201803080000010433',
            'amount' => '0.01',
        ];

        $yiLeXiangPay = new YiLeXiangPay();
        $yiLeXiangPay->setPrivateKey('test');
        $yiLeXiangPay->setOptions($options);
        $yiLeXiangPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $yiLeXiangPay->getMsg());
    }
}
