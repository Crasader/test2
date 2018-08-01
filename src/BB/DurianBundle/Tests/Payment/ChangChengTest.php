<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\ChangCheng;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class ChangChengTest extends DurianTestCase
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

        $changCheng = new ChangCheng();
        $changCheng->getVerifyData();
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

        $changCheng = new ChangCheng();
        $changCheng->setPrivateKey('test');
        $changCheng->getVerifyData();
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
            'number' => '211330315200001',
            'amount' => '100',
            'orderId' => '201709050000006913',
            'paymentVendorId' => '999',
            'notify_url' => 'http://two123.comxa.com/',
        ];

        $changCheng = new ChangCheng();
        $changCheng->setPrivateKey('test');
        $changCheng->setOptions($options);
        $changCheng->getVerifyData();
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
            'number' => '211330315200001',
            'amount' => '1',
            'orderId' => '201703220000001407',
            'paymentVendorId' => '1103',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => '',
        ];

        $changCheng = new ChangCheng();
        $changCheng->setPrivateKey('test');
        $changCheng->setOptions($options);
        $changCheng->getVerifyData();
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
            'number' => '211330315200001',
            'amount' => '0.01',
            'orderId' => '201703220000001407',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.a.cc8pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"barCode":"weixin://wxpay/bizpayurl?pr=swRRgsD","merchno":"211330315200001",' .
            '"message":"下单成功","refno":"10000006348674", "traceno":"201709050000006913"';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $changCheng = new ChangCheng();
        $changCheng->setContainer($this->container);
        $changCheng->setClient($this->client);
        $changCheng->setResponse($response);
        $changCheng->setPrivateKey('test');
        $changCheng->setOptions($options);
        $changCheng->getVerifyData();
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
            'number' => '211330315200001',
            'amount' => '0.1',
            'orderId' => '201709050000006920',
            'paymentVendorId' => '1097',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.a.cc8pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"merchno":"211330315200001","message":"交易失败,找不到二维码路由信息",' .
            '"respCode":"58","traceno":"201709050000006920"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $changCheng = new ChangCheng();
        $changCheng->setContainer($this->container);
        $changCheng->setClient($this->client);
        $changCheng->setResponse($response);
        $changCheng->setPrivateKey('test');
        $changCheng->setOptions($options);
        $changCheng->getVerifyData();
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
            'number' => '211330315200001',
            'amount' => '0.01',
            'orderId' => '201709050000006913',
            'paymentVendorId' => '1103',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.a.cc8pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"merchno":"211330315200001","message":"下单成功","refno":"10000006348674",' .
            '"respCode":"00","traceno":"201709050000006913"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $changCheng = new ChangCheng();
        $changCheng->setContainer($this->container);
        $changCheng->setClient($this->client);
        $changCheng->setResponse($response);
        $changCheng->setPrivateKey('test');
        $changCheng->setOptions($options);
        $changCheng->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'number' => '211330315200001',
            'amount' => '0.01',
            'orderId' => '201709050000006913',
            'paymentVendorId' => '1103',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.a.cc8pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"barCode":"weixin://wxpay/bizpayurl?pr=swRRgsD","merchno":"211330315200001",' .
            '"message":"下单成功","refno":"10000006348674","respCode":"00","traceno":"201709050000006913"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $changCheng = new ChangCheng();
        $changCheng->setContainer($this->container);
        $changCheng->setClient($this->client);
        $changCheng->setResponse($response);
        $changCheng->setPrivateKey('test');
        $changCheng->setOptions($options);
        $data = $changCheng->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=swRRgsD', $changCheng->getQrcode());
    }

    /**
     * 測試手機支付
     */
    public function testPayWithWap()
    {
        $options = [
            'number' => '211330315200001',
            'amount' => '0.01',
            'orderId' => '201709050000006917',
            'paymentVendorId' => '1098',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.a.cc8pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"barCode":"http://a.cc8pay.com/api/wap/?url=https://qpay.qq.com/qr/5715e44e",' .
            '"merchno":"211330315200001","message":"交易成功","refno":"10000006351465","respCode":"00",' .
            '"traceno":"201709050000006917"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $changCheng = new ChangCheng();
        $changCheng->setContainer($this->container);
        $changCheng->setClient($this->client);
        $changCheng->setResponse($response);
        $changCheng->setPrivateKey('test');
        $changCheng->setOptions($options);
        $data = $changCheng->getVerifyData();

        $this->assertEquals('http://a.cc8pay.com/api/wap/?url=https://qpay.qq.com/qr/5715e44e', $data['act_url']);
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

        $changCheng = new ChangCheng();
        $changCheng->verifyOrderPayment([]);
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

        $changCheng = new ChangCheng();
        $changCheng->setPrivateKey('test');
        $changCheng->verifyOrderPayment([]);
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
            'merchno' => '211330315200001',
            'status' => '1',
            'traceno' => '201709050000006913',
            'orderno' => '10000006348674',
            'merchName' => 'CC965A',
            'channelOrderno' => '',
            'amount' => '0.01',
            'transDate' => '2017-09-05',
            'channelTraceno' => '',
            'transTime' => '17:14:10',
            'payType' => '2',
            'openId' => 'weixin://wxpay/bizpayurl?pr=swRRgsD',
        ];

        $changCheng = new ChangCheng();
        $changCheng->setPrivateKey('test');
        $changCheng->setOptions($options);
        $changCheng->verifyOrderPayment([]);
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
            'merchno' => '211330315200001',
            'status' => '1',
            'traceno' => '201709050000006913',
            'orderno' => '10000006348674',
            'merchName' => 'CC965A',
            'channelOrderno' => '',
            'amount' => '0.01',
            'transDate' => '2017-09-05',
            'channelTraceno' => '',
            'transTime' => '17:14:10',
            'payType' => '2',
            'signature' => 'E9BF0EDFEDACD9D04194F12076738332',
            'openId' => 'weixin://wxpay/bizpayurl?pr=swRRgsD',
        ];

        $changCheng = new ChangCheng();
        $changCheng->setPrivateKey('test');
        $changCheng->setOptions($options);
        $changCheng->verifyOrderPayment([]);
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
            'merchno' => '211330315200001',
            'status' => '0',
            'traceno' => '201709050000006913',
            'orderno' => '10000006348674',
            'merchName' => 'CC965A',
            'channelOrderno' => '',
            'amount' => '0.01',
            'transDate' => '2017-09-05',
            'channelTraceno' => '',
            'transTime' => '17:14:10',
            'payType' => '2',
            'signature' => '2a311dfbe46cc15b872cfa3dea4a888c',
            'openId' => 'weixin://wxpay/bizpayurl?pr=swRRgsD',
        ];

        $changCheng = new ChangCheng();
        $changCheng->setPrivateKey('test');
        $changCheng->setOptions($options);
        $changCheng->verifyOrderPayment([]);
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
            'merchno' => '211330315200001',
            'status' => '9',
            'traceno' => '201709050000006913',
            'orderno' => '10000006348674',
            'merchName' => 'CC965A',
            'channelOrderno' => '',
            'amount' => '0.01',
            'transDate' => '2017-09-05',
            'channelTraceno' => '',
            'transTime' => '17:14:10',
            'payType' => '2',
            'signature' => '01b6fe346e60021835ca305bfe6d0396',
            'openId' => 'weixin://wxpay/bizpayurl?pr=swRRgsD',
        ];

        $changCheng = new ChangCheng();
        $changCheng->setPrivateKey('test');
        $changCheng->setOptions($options);
        $changCheng->verifyOrderPayment([]);
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
            'merchno' => '211330315200001',
            'status' => '1',
            'traceno' => '201709050000006913',
            'orderno' => '10000006348674',
            'merchName' => 'CC965A',
            'channelOrderno' => '',
            'amount' => '0.01',
            'transDate' => '2017-09-05',
            'channelTraceno' => '',
            'transTime' => '17:14:10',
            'payType' => '2',
            'signature' => 'f92560806ef87632d4c0e08f1cc04056',
            'openId' => 'weixin://wxpay/bizpayurl?pr=swRRgsD',
        ];

        $entry = ['id' => '201707250000003581'];

        $changCheng = new ChangCheng();
        $changCheng->setPrivateKey('test');
        $changCheng->setOptions($options);
        $changCheng->verifyOrderPayment($entry);
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
            'merchno' => '211330315200001',
            'status' => '1',
            'traceno' => '201709050000006913',
            'orderno' => '10000006348674',
            'merchName' => 'CC965A',
            'channelOrderno' => '',
            'amount' => '0.01',
            'transDate' => '2017-09-05',
            'channelTraceno' => '',
            'transTime' => '17:14:10',
            'payType' => '2',
            'signature' => 'f92560806ef87632d4c0e08f1cc04056',
            'openId' => 'weixin://wxpay/bizpayurl?pr=swRRgsD',
        ];

        $entry = [
            'id' => '201709050000006913',
            'amount' => '1',
        ];

        $changCheng = new ChangCheng();
        $changCheng->setPrivateKey('test');
        $changCheng->setOptions($options);
        $changCheng->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $options = [
            'merchno' => '211330315200001',
            'status' => '1',
            'traceno' => '201709050000006913',
            'orderno' => '10000006348674',
            'merchName' => 'CC965A',
            'channelOrderno' => '',
            'amount' => '0.01',
            'transDate' => '2017-09-05',
            'channelTraceno' => '',
            'transTime' => '17:14:10',
            'payType' => '2',
            'signature' => 'f92560806ef87632d4c0e08f1cc04056',
            'openId' => 'weixin://wxpay/bizpayurl?pr=swRRgsD',
        ];

        $entry = [
            'id' => '201709050000006913',
            'amount' => '0.01',
        ];

        $changCheng = new ChangCheng();
        $changCheng->setPrivateKey('test');
        $changCheng->setOptions($options);
        $changCheng->verifyOrderPayment($entry);

        $this->assertEquals('success', $changCheng->getMsg());
    }
}
