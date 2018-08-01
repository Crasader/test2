<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\YiLeXiangAliPay;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class YiLeXiangAliPayTest extends DurianTestCase
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

        $yiLeXiangAliPay = new YiLeXiangAliPay();
        $yiLeXiangAliPay->getVerifyData();
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

        $yiLeXiangAliPay = new YiLeXiangAliPay();
        $yiLeXiangAliPay->setPrivateKey('test');
        $yiLeXiangAliPay->getVerifyData();
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

        $yiLeXiangAliPay = new YiLeXiangAliPay();
        $yiLeXiangAliPay->setPrivateKey('test');
        $yiLeXiangAliPay->setOptions($options);
        $yiLeXiangAliPay->getVerifyData();
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
            'number' => 'gl00024587',
            'amount' => '2',
            'orderId' => '201803090000010499',
            'paymentVendorId' => '1092',
            'merchant_extra' => ['appid' => '1fbcf2d407ed45b1937ee12de4f5323c'],
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => '',
        ];

        $yiLeXiangAliPay = new YiLeXiangAliPay();
        $yiLeXiangAliPay->setPrivateKey('test');
        $yiLeXiangAliPay->setOptions($options);
        $yiLeXiangAliPay->getVerifyData();
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
            'number' => 'gl00024587',
            'amount' => '2',
            'orderId' => '201803090000010499',
            'paymentVendorId' => '1092',
            'merchant_extra' => ['appid' => '1fbcf2d407ed45b1937ee12de4f5323c'],
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.https.open.goodluckchina.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"msg":"\u64cd\u4f5c\u6210\u529f","orderNo":"20180309163615109874633",' .
            '"orderId":"d555f7c361434ecc96e6655d3c1fe680","pay_url":"http://olewx.goodluckchina.net/' .
            'op/toOauth.html?model=00&custNo=gl00024587&first=y&orderId=d555f7c361434ecc96e6655d3c1fe680"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yiLeXiangAliPay = new YiLeXiangAliPay();
        $yiLeXiangAliPay->setContainer($this->container);
        $yiLeXiangAliPay->setClient($this->client);
        $yiLeXiangAliPay->setResponse($response);
        $yiLeXiangAliPay->setPrivateKey('test');
        $yiLeXiangAliPay->setOptions($options);
        $yiLeXiangAliPay->getVerifyData();
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
            'number' => 'gl00024587',
            'amount' => '2',
            'orderId' => '201803090000010499',
            'paymentVendorId' => '1092',
            'merchant_extra' => ['appid' => '1fbcf2d407ed45b1937ee12de4f5323c'],
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.https.open.goodluckchina.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"orderNo":"20180308140817109783542","msg":"签名验证失败","code":"50037"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yiLeXiangAliPay = new YiLeXiangAliPay();
        $yiLeXiangAliPay->setContainer($this->container);
        $yiLeXiangAliPay->setClient($this->client);
        $yiLeXiangAliPay->setResponse($response);
        $yiLeXiangAliPay->setPrivateKey('test');
        $yiLeXiangAliPay->setOptions($options);
        $yiLeXiangAliPay->getVerifyData();
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
            'number' => 'gl00024587',
            'amount' => '2',
            'orderId' => '201803090000010499',
            'paymentVendorId' => '1092',
            'merchant_extra' => ['appid' => '1fbcf2d407ed45b1937ee12de4f5323c'],
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.https.open.goodluckchina.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"msg":"\u64cd\u4f5c\u6210\u529f","code":1,"orderNo":"20180309163615109874633",' .
            '"orderId":"d555f7c361434ecc96e6655d3c1fe680"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yiLeXiangAliPay = new YiLeXiangAliPay();
        $yiLeXiangAliPay->setContainer($this->container);
        $yiLeXiangAliPay->setClient($this->client);
        $yiLeXiangAliPay->setResponse($response);
        $yiLeXiangAliPay->setPrivateKey('test');
        $yiLeXiangAliPay->setOptions($options);
        $yiLeXiangAliPay->getVerifyData();
    }

    /**
     * 測試支付寶二維
     */
    public function testScanAliPay()
    {
        $options = [
            'number' => 'gl00024587',
            'amount' => '2',
            'orderId' => '201803090000010499',
            'paymentVendorId' => '1092',
            'merchant_extra' => ['appid' => '1fbcf2d407ed45b1937ee12de4f5323c'],
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.https.open.goodluckchina.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"msg":"\u64cd\u4f5c\u6210\u529f","code":1,"orderNo":"20180309163615109874633",' .
            '"orderId":"d555f7c361434ecc96e6655d3c1fe680","pay_url":"http://olewx.goodluckchina.net/' .
            'op/toOauth.html?model=00&custNo=gl00024587&first=y&orderId=d555f7c361434ecc96e6655d3c1fe680"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yiLeXiangAliPay = new YiLeXiangAliPay();
        $yiLeXiangAliPay->setContainer($this->container);
        $yiLeXiangAliPay->setClient($this->client);
        $yiLeXiangAliPay->setResponse($response);
        $yiLeXiangAliPay->setPrivateKey('test');
        $yiLeXiangAliPay->setOptions($options);
        $data = $yiLeXiangAliPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('http://olewx.goodluckchina.net/op/toOauth.html?model=00&custNo=gl00024587&first=' .
            'y&orderId=d555f7c361434ecc96e6655d3c1fe680', $yiLeXiangAliPay->getQrcode());
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

        $yiLeXiangAliPay = new YiLeXiangAliPay();
        $yiLeXiangAliPay->verifyOrderPayment([]);
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

        $yiLeXiangAliPay = new YiLeXiangAliPay();
        $yiLeXiangAliPay->setPrivateKey('test');
        $yiLeXiangAliPay->verifyOrderPayment([]);
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
            'attach' => '201803090000010499',
            'cust_no' => 'gl00024587',
            'money' => '2',
            'order_id' => 'd555f7c361434ecc96e6655d3c1fe680',
            'pay_channel' => '01',
            'pay_status' => 'success',
            'pay_time' => '',
            'plat_order_no' => '',
            'return_code' => 'SUCCESS',
            'return_msg' => '支付成功',
            'trade_no' => '20180309163615109874633',
        ];

        $yiLeXiangAliPay = new YiLeXiangAliPay();
        $yiLeXiangAliPay->setPrivateKey('test');
        $yiLeXiangAliPay->setOptions($options);
        $yiLeXiangAliPay->verifyOrderPayment([]);
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
            'attach' => '201803090000010499',
            'cust_no' => 'gl00024587',
            'money' => '2',
            'order_id' => 'd555f7c361434ecc96e6655d3c1fe680',
            'pay_channel' => '01',
            'pay_status' => 'success',
            'pay_time' => '',
            'plat_order_no' => '',
            'return_code' => 'SUCCESS',
            'return_msg' => '支付成功',
            'trade_no' => '20180309163615109874633',
            'sign' => '5f6e326a6421162199c5294f680612c6',
        ];

        $yiLeXiangAliPay = new YiLeXiangAliPay();
        $yiLeXiangAliPay->setPrivateKey('1234');
        $yiLeXiangAliPay->setOptions($options);
        $yiLeXiangAliPay->verifyOrderPayment([]);
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
            'attach' => '201803090000010499',
            'cust_no' => 'gl00024587',
            'money' => '2',
            'order_id' => 'd555f7c361434ecc96e6655d3c1fe680',
            'pay_channel' => '01',
            'pay_status' => '',
            'pay_time' => '',
            'plat_order_no' => '',
            'return_code' => 'SUCCESS',
            'return_msg' => '支付成功',
            'trade_no' => '20180309163615109874633',
            'sign' => 'fbae7a5bc6899ad3be4f1cff787162d2',
        ];

        $yiLeXiangAliPay = new YiLeXiangAliPay();
        $yiLeXiangAliPay->setPrivateKey('test');
        $yiLeXiangAliPay->setOptions($options);
        $yiLeXiangAliPay->verifyOrderPayment([]);
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
            'attach' => '201803090000010499',
            'cust_no' => 'gl00024587',
            'money' => '2',
            'order_id' => 'd555f7c361434ecc96e6655d3c1fe680',
            'pay_channel' => '01',
            'pay_status' => 'success',
            'pay_time' => '',
            'plat_order_no' => '',
            'return_code' => 'SUCCESS',
            'return_msg' => '支付成功',
            'trade_no' => '20180309163615109874633',
            'sign' => 'e981d6e1bd0d77c29bfd0cfeff9688ce',
        ];

        $entry = ['id' => '201707250000003581'];

        $yiLeXiangAliPay = new YiLeXiangAliPay();
        $yiLeXiangAliPay->setPrivateKey('test');
        $yiLeXiangAliPay->setOptions($options);
        $yiLeXiangAliPay->verifyOrderPayment($entry);
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
            'attach' => '201803090000010499',
            'cust_no' => 'gl00024587',
            'money' => '2',
            'order_id' => 'd555f7c361434ecc96e6655d3c1fe680',
            'pay_channel' => '01',
            'pay_status' => 'success',
            'pay_time' => '',
            'plat_order_no' => '',
            'return_code' => 'SUCCESS',
            'return_msg' => '支付成功',
            'trade_no' => '20180309163615109874633',
            'sign' => 'e981d6e1bd0d77c29bfd0cfeff9688ce',
        ];

        $entry = [
            'id' => '201803090000010499',
            'amount' => '1.00',
        ];

        $yiLeXiangAliPay = new YiLeXiangAliPay();
        $yiLeXiangAliPay->setPrivateKey('test');
        $yiLeXiangAliPay->setOptions($options);
        $yiLeXiangAliPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $options = [
            'attach' => '201803090000010499',
            'cust_no' => 'gl00024587',
            'money' => '2',
            'order_id' => 'd555f7c361434ecc96e6655d3c1fe680',
            'pay_channel' => '01',
            'pay_status' => 'success',
            'pay_time' => '',
            'plat_order_no' => '',
            'return_code' => 'SUCCESS',
            'return_msg' => '支付成功',
            'trade_no' => '20180309163615109874633',
            'sign' => 'e981d6e1bd0d77c29bfd0cfeff9688ce',
        ];

        $entry = [
            'id' => '201803090000010499',
            'amount' => '2',
        ];

        $yiLeXiangAliPay = new YiLeXiangAliPay();
        $yiLeXiangAliPay->setPrivateKey('test');
        $yiLeXiangAliPay->setOptions($options);
        $yiLeXiangAliPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $yiLeXiangAliPay->getMsg());
    }
}
