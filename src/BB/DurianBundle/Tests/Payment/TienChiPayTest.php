<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\TienChiPay;
use Buzz\Message\Response;

class TienChiPayTest extends DurianTestCase
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * @var \Buzz\Client\Curl
     */
    private $client;

    /**
     * @var array
     */
    private $option;

    /**
     * @var array
     */
    private $result;

    /**
     * @var array
     */
    private $returnResult;

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

        $this->option = [
            'number' => '9453',
            'orderCreateDate' => '2018-02-14 01:53:17',
            'orderId' => '201802140000009453',
            'notify_url' => 'http://www.seafood.help/',
            'amount' => '1.9453',
            'username' => 'seafood',
            'paymentVendorId' => '1102',
            'ip' => '127.0.0.1',
            'verify_url' => 'http://seafood.com.tw',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $this->returnResult = [
            'merchant_id' => '100002',
            'order_id' => '201802120000004434',
            'trans_amt' => '100',
            'send_time' => '20180212234037',
            'resp_desc' => '交易成功',
            'resp_code' => '0000',
            'sign' => '5e941d35adb7bf290a8d6880453bc163',
        ];

        $this->result = [
            'ret_code' => '200',
            'ret_msg' => '成功',
            'result' => [
                'pay_link' => 'http://payment.pz-hero.com/pay?to=apple',
            ],
        ];
    }

    /**
     * 測試支付時沒有私鑰
     */
    public function testPayWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $tienChiPay = new TienChiPay();
        $tienChiPay->getVerifyData();
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

        $tienChiPay = new TienChiPay();
        $tienChiPay->setPrivateKey('test');
        $tienChiPay->setOptions([]);
        $tienChiPay->getVerifyData();
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

        $this->option['paymentVendorId'] = '9999';

        $tienChiPay = new TienChiPay();
        $tienChiPay->setPrivateKey('test');
        $tienChiPay->setOptions($this->option);
        $tienChiPay->getVerifyData();
    }

    /**
     * 測試支付缺少verify_url
     */
    public function testPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $this->option['verify_url'] = '';

        $tienChiPay = new TienChiPay();
        $tienChiPay->setPrivateKey('test');
        $tienChiPay->setOptions($this->option);
        $tienChiPay->getVerifyData();
    }

    /**
     * 測試支付時返回缺少ret_code
     */
    public function testPayReturnWithoutRetCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        unset($this->result['ret_code']);

        $response = new Response();
        $response->setContent(json_encode($this->result));
        $response->addHeader('HTTP/1.1 200 OK');

        $tienChiPay = new TienChiPay();
        $tienChiPay->setPrivateKey('test');
        $tienChiPay->setOptions($this->option);
        $tienChiPay->setContainer($this->container);
        $tienChiPay->setClient($this->client);
        $tienChiPay->setResponse($response);
        $tienChiPay->getVerifyData();
    }

    /**
     * 測試支付時返回ret_code不等於200
     */
    public function testPayReturnRetCodeNotEqualTwoHundred()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '交易失敗',
            180130
        );

        $this->result['ret_code'] = '999';
        $this->result['ret_msg'] = '交易失敗';

        $response = new Response();
        $response->setContent(json_encode($this->result));
        $response->addHeader('HTTP/1.1 200 OK');

        $tienChiPay = new TienChiPay();
        $tienChiPay->setPrivateKey('test');
        $tienChiPay->setOptions($this->option);
        $tienChiPay->setContainer($this->container);
        $tienChiPay->setClient($this->client);
        $tienChiPay->setResponse($response);
        $tienChiPay->getVerifyData();
    }

    /**
     * 測試支付時未返回pay_link
     */
    public function testPayReturnWithoutPayLink()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        unset($this->result['result']['pay_link']);

        $response = new Response();
        $response->setContent(json_encode($this->result));
        $response->addHeader('HTTP/1.1 200 OK');

        $tienChiPay = new TienChiPay();
        $tienChiPay->setPrivateKey('test');
        $tienChiPay->setOptions($this->option);
        $tienChiPay->setContainer($this->container);
        $tienChiPay->setClient($this->client);
        $tienChiPay->setResponse($response);
        $tienChiPay->getVerifyData();
    }

    /**
     * 測試支付時返回pay_link格式不符
     */
    public function testPayReturnPayLinkWrongFormat()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $this->result['result']['pay_link'] = 'http://payment.pz-hero.com';

        $response = new Response();
        $response->setContent(json_encode($this->result));
        $response->addHeader('HTTP/1.1 200 OK');

        $tienChiPay = new TienChiPay();
        $tienChiPay->setPrivateKey('test');
        $tienChiPay->setOptions($this->option);
        $tienChiPay->setContainer($this->container);
        $tienChiPay->setClient($this->client);
        $tienChiPay->setResponse($response);
        $tienChiPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $response = new Response();
        $response->setContent(json_encode($this->result));
        $response->addHeader('HTTP/1.1 200 OK');

        $tienChiPay = new TienChiPay();
        $tienChiPay->setPrivateKey('test');
        $tienChiPay->setOptions($this->option);
        $tienChiPay->setContainer($this->container);
        $tienChiPay->setClient($this->client);
        $tienChiPay->setResponse($response);
        $encodeData = $tienChiPay->getVerifyData();

        $this->assertEquals('http://payment.pz-hero.com/pay', $encodeData['post_url']);
        $this->assertEquals('apple', $encodeData['params']['to']);
    }

    /**
     * 測試二維支付
     */
    public function testQRcodePay()
    {
        $url = 'https://pay.wen25.com/?to=aHR0cDovL3A2NzJjOGU1Yy5ia3QuY' .
            '2xvdWRkbi5jb20vaW5kZXguaHRtbD9wYXlfdHlwZT13ZWNoYXQmb3JkZXJ' .
            'faWQ9MjAxODA1MDkxMTQyMjI3NTI3NTQwOQ==';

        $this->result['result']['pay_link'] = $url;

        $response = new Response();
        $response->setContent(json_encode($this->result));
        $response->addHeader('HTTP/1.1 200 OK');

        $this->option['paymentVendorId'] = '1090';

        $tienChiPay = new TienChiPay();
        $tienChiPay->setPrivateKey('test');
        $tienChiPay->setOptions($this->option);
        $tienChiPay->setContainer($this->container);
        $tienChiPay->setClient($this->client);
        $tienChiPay->setResponse($response);
        $encodeData = $tienChiPay->getVerifyData();

        $this->assertEmpty($encodeData);
        $this->assertEquals($url, $tienChiPay->getQrcode());
    }

    /**
     * 測試QQWAP支付
     */
    public function testQQWapPay()
    {
        $this->result['result']['pay_link'] = 'https://qpay.qq.com/qr/535b3451';

        $response = new Response();
        $response->setContent(json_encode($this->result));
        $response->addHeader('HTTP/1.1 200 OK');

        $this->option['paymentVendorId'] = '1104';

        $tienChiPay = new TienChiPay();
        $tienChiPay->setPrivateKey('test');
        $tienChiPay->setOptions($this->option);
        $tienChiPay->setContainer($this->container);
        $tienChiPay->setClient($this->client);
        $tienChiPay->setResponse($response);
        $encodeData = $tienChiPay->getVerifyData();

        $this->assertEquals('https://qpay.qq.com/qr/535b3451', $encodeData['post_url']);
        $this->assertEmpty($encodeData['params']);
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

        $tienChiPay = new TienChiPay();
        $tienChiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未指定參數
     */
    public function testReturnWithNoParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $tienChiPay = new TienChiPay();
        $tienChiPay->setPrivateKey('test');
        $tienChiPay->verifyOrderPayment([]);
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

        unset($this->returnResult['sign']);

        $tienChiPay = new TienChiPay();
        $tienChiPay->setPrivateKey('test');
        $tienChiPay->setOptions($this->returnResult);
        $tienChiPay->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = '9453';

        $tienChiPay = new TienChiPay();
        $tienChiPay->setPrivateKey('test');
        $tienChiPay->setOptions($this->returnResult);
        $tienChiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付失敗
     */
    public function testReturnUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $this->returnResult['resp_code'] = '0001';
        $this->returnResult['sign'] = '1a7f94e869bdf7ecece3fbd5dc3ff63e';

        $tienChiPay = new TienChiPay();
        $tienChiPay->setPrivateKey('test');
        $tienChiPay->setOptions($this->returnResult);
        $tienChiPay->verifyOrderPayment([]);
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

        $this->returnResult['resp_code'] = '9453';
        $this->returnResult['sign'] = '5787c19181ef934b19f7635d61ce8faf';

        $tienChiPay = new TienChiPay();
        $tienChiPay->setPrivateKey('test');
        $tienChiPay->setOptions($this->returnResult);
        $tienChiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時單號不正確
     */
    public function testReturnPaymentOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $entry = ['id' => '201802140000009487'];

        $tienChiPay = new TienChiPay();
        $tienChiPay->setPrivateKey('test');
        $tienChiPay->setOptions($this->returnResult);
        $tienChiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時金額不正確
     */
    public function testReturnPaymentOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $entry = [
            'id' => '201802120000004434',
            'amount' => '999',
        ];

        $tienChiPay = new TienChiPay();
        $tienChiPay->setPrivateKey('test');
        $tienChiPay->setOptions($this->returnResult);
        $tienChiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時回調參數增加goods_desc
     */
    public function testReturnResultWithGoodsDesc()
    {
        $this->returnResult['goods_desc'] = '商品备注';
        $this->returnResult['sign'] = 'a8e0f1536b992dea87da8462506ac482';

        $entry = [
            'id' => '201802120000004434',
            'amount' => '1',
        ];

        $tienChiPay = new TienChiPay();
        $tienChiPay->setPrivateKey('test');
        $tienChiPay->setOptions($this->returnResult);
        $tienChiPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $tienChiPay->getMsg());
    }

    /**
     * 測試返回時支付成功
     */
    public function testReturnResultSuccess()
    {
        $entry = [
            'id' => '201802120000004434',
            'amount' => '1',
        ];

        $tienChiPay = new TienChiPay();
        $tienChiPay->setPrivateKey('test');
        $tienChiPay->setOptions($this->returnResult);
        $tienChiPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $tienChiPay->getMsg());
    }
}
