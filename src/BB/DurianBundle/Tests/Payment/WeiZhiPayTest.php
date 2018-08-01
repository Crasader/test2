<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\WeiZhiPay;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class WeiZhiPayTest extends DurianTestCase
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

        $weiZhiPay = new WeiZhiPay();
        $weiZhiPay->getVerifyData();
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

        $weiZhiPay = new WeiZhiPay();
        $weiZhiPay->setPrivateKey('test');
        $weiZhiPay->getVerifyData();
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
            'amount' => '10',
            'orderId' => '201710270000005352',
            'paymentVendorId' => '9453',
            'notify_url' => 'http://pay.in-action.tw/',
            'postUrl' => 'http://pay1.weizhipay.com/pay/',
        ];

        $weiZhiPay = new WeiZhiPay();
        $weiZhiPay->setPrivateKey('test');
        $weiZhiPay->setOptions($options);
        $weiZhiPay->getVerifyData();
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
            'amount' => '9453',
            'orderId' => '201710270000005352',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://pay.in-action.tw/',
            'postUrl' => 'http://pay1.weizhipay.com/pay/',
            'verify_url' => '',
        ];

        $weiZhiPay = new WeiZhiPay();
        $weiZhiPay->setPrivateKey('test');
        $weiZhiPay->setOptions($options);
        $weiZhiPay->getVerifyData();
    }

    /**
     * 測試微信支付
     */
    public function testWxPay()
    {
        $options = [
            'number' => '100992',
            'amount' => '0.1',
            'orderId' => '201703240000001427',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://pay.in-action.tw/',
            'postUrl' => 'http://pay1.weizhipay.com/pay/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = 'BD01AC72396747D9A2CC1A8D99F97D9827DF24D4F77';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $weiZhiPay = new WeiZhiPay();
        $weiZhiPay->setContainer($this->container);
        $weiZhiPay->setClient($this->client);
        $weiZhiPay->setResponse($response);
        $weiZhiPay->setPrivateKey('test');
        $weiZhiPay->setOptions($options);
        $data = $weiZhiPay->getVerifyData();

        $url = 'http://pay1.weizhipay.com/pay/WeChat.aspx?Code=BD01AC72396747D9A2CC1A8D99F97D9827DF24D4F77&SuccessUrl=';

        $this->assertEquals($url, $data['act_url']);
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

        $weiZhiPay = new WeiZhiPay();
        $weiZhiPay->setPrivateKey('test');
        $weiZhiPay->verifyOrderPayment([]);
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
            'tradeNo' => '1000050301201710270210050281797',
            'desc' => '',
            'time' => '2017-10-27 17:01:01',
            'userid' => '201710270000005352',
            'amount' => '0.10',
            'status' => '交易成功',
            'type' => '微信',
        ];

        $weiZhiPay = new WeiZhiPay();
        $weiZhiPay->setPrivateKey('test');
        $weiZhiPay->setOptions($options);
        $weiZhiPay->verifyOrderPayment([]);
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
            'tradeNo' => '1000050301201710270210050281797',
            'desc' => '',
            'time' => '2017-10-27 17:01:01',
            'userid' => '201710270000005352',
            'amount' => '0.10',
            'status' => '交易成功',
            'type' => '微信',
            'sig' => '85756FBCD538F5D051F798368FDDB98F',
        ];

        $weiZhiPay = new WeiZhiPay();
        $weiZhiPay->setPrivateKey('test');
        $weiZhiPay->setOptions($options);
        $weiZhiPay->verifyOrderPayment([]);
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
            'tradeNo' => '1000050301201710270210050281797',
            'desc' => '',
            'time' => '2017-10-27 17:01:01',
            'userid' => '201710270000005352',
            'amount' => '0.10',
            'status' => '交易失敗',
            'type' => '微信',
            'sig' => '4774B3D2A390488662CA06D3FF5246BF',
        ];

        $weiZhiPay = new WeiZhiPay();
        $weiZhiPay->setPrivateKey('test');
        $weiZhiPay->setOptions($options);
        $weiZhiPay->verifyOrderPayment([]);
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
            'tradeNo' => '1000050301201710270210050281797',
            'desc' => '',
            'time' => '2017-10-27 17:01:01',
            'userid' => '201710270000005352',
            'amount' => '0.10',
            'status' => '交易成功',
            'type' => '微信',
            'sig' => '6230F973ACB15CCDD876DB0A65C580E2',
        ];

        $entry = ['id' => '9453'];

        $weiZhiPay = new WeiZhiPay();
        $weiZhiPay->setPrivateKey('test');
        $weiZhiPay->setOptions($options);
        $weiZhiPay->verifyOrderPayment($entry);
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
            'tradeNo' => '1000050301201710270210050281797',
            'desc' => '',
            'time' => '2017-10-27 17:01:01',
            'userid' => '201710270000005352',
            'amount' => '0.10',
            'status' => '交易成功',
            'type' => '微信',
            'sig' => '6230F973ACB15CCDD876DB0A65C580E2',
        ];


        $entry = [
            'id' => '201710270000005352',
            'amount' => '1',
        ];

        $weiZhiPay = new WeiZhiPay();
        $weiZhiPay->setPrivateKey('test');
        $weiZhiPay->setOptions($options);
        $weiZhiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $options = [
            'tradeNo' => '1000050301201710270210050281797',
            'desc' => '',
            'time' => '2017-10-27 17:01:01',
            'userid' => '201710270000005352',
            'amount' => '0.10',
            'status' => '交易成功',
            'type' => '微信',
            'sig' => '6230F973ACB15CCDD876DB0A65C580E2',
        ];

        $entry = [
            'id' => '201710270000005352',
            'amount' => '0.1',
        ];

        $weiZhiPay = new WeiZhiPay();
        $weiZhiPay->setPrivateKey('test');
        $weiZhiPay->setOptions($options);
        $weiZhiPay->verifyOrderPayment($entry);

        $this->assertEquals('ok', $weiZhiPay->getMsg());
    }
}
