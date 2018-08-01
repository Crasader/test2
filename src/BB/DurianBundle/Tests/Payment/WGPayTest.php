<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\WGPay;
use Buzz\Message\Response;

class WGPayTest extends DurianTestCase
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
     * 訂單參數
     *
     * @var array
     */
    private $options;

    /**
     * 返回結果
     *
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

        $this->options = [
            'number' => '2100002',
            'amount' => '1',
            'orderId' => '201804130000011103',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://www.seafood.help/',
            'verify_url' => 'payment.http.cashier.zgmyb.top',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $this->returnResult = [
            'amt' => '1.00',
            'merCode' => '2100002',
            'payAmt' => '0.97',
            'payStatus' => 'S',
            'product' => 'Wechat',
            'rmk' => '201804130000011103',
            'sign' => '880CDD0C47ACBEF7E4F33B1EFCBF510D',
            'tradeNo' => '201804130000011103',
            'tradeTime' => '2018-04-1317:57:17',
        ];
    }

    /**
     * 測試支付加密時沒有帶入privateKey的情況
     */
    public function testPayWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $wGPay = new WGPay();
        $wGPay->getVerifyData();
    }

    /**
     * 測試支付加密時未指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $wGPay = new WGPay();
        $wGPay->setPrivateKey('test');
        $wGPay->setOptions([]);
        $wGPay->getVerifyData();
    }

    /**
     * 測試支付加密時代入支付平台不支援的銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $this->options['paymentVendorId'] = '999';

        $wGPay = new WGPay();
        $wGPay->setPrivateKey('1234');
        $wGPay->setOptions($this->options);
        $wGPay->getVerifyData();
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

        $this->options['verify_url'] = '';

        $wGPay = new WGPay();
        $wGPay->setPrivateKey('test');
        $wGPay->setOptions($this->options);
        $wGPay->getVerifyData();
    }

    /**
     * 測試二維支付沒有返回code
     */
    public function testQrcodePayReturnWithoutCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $wGPay = new WGPay();
        $wGPay->setContainer($this->container);
        $wGPay->setClient($this->client);
        $wGPay->setResponse($response);
        $wGPay->setPrivateKey('test');
        $wGPay->setOptions($this->options);
        $wGPay->getVerifyData();
    }

    /**
     * 測試二維支付沒有返回Msg
     */
    public function testQrcodePayReturnWithoutMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = ['code' => '8889'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $wGPay = new WGPay();
        $wGPay->setContainer($this->container);
        $wGPay->setClient($this->client);
        $wGPay->setResponse($response);
        $wGPay->setPrivateKey('test');
        $wGPay->setOptions($this->options);
        $wGPay->getVerifyData();
    }

    /**
     * 測試支付時返回錯誤訊息
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '签名有误',
            180130
        );

        $result = [
            'code' => '8889',
            'msg' => '签名有误',
            'merCode' => '2100002',
            'tradeNo' => '201804130000011064',
            'rmk' => '201804130000011064',
            'sign' => '37E1D9989F09DE1C40F11906BCF11C73',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $wGPay = new WGPay();
        $wGPay->setContainer($this->container);
        $wGPay->setClient($this->client);
        $wGPay->setResponse($response);
        $wGPay->setPrivateKey('test');
        $wGPay->setOptions($this->options);
        $wGPay->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回pay_url
     */
    public function testQrcodePayReturnWithoutPayUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'code' => '0000',
            'msg' => '下单成功',
            'merCode' => '2100002',
            'tradeNo' => '201804160000011146',
            'amt' => '1.00',
            'payAmt' => '0.99',
            'rmk' => '201804160000011146',
            'sign' => 'BBA5F7825658274B3BF9C12548D0BAC5',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $wGPay = new WGPay();
        $wGPay->setContainer($this->container);
        $wGPay->setClient($this->client);
        $wGPay->setResponse($response);
        $wGPay->setPrivateKey('test');
        $wGPay->setOptions($this->options);
        $wGPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $result = [
            'code' => '0000',
            'msg' => '下单成功',
            'merCode' => '2100002',
            'tradeNo' => '201804160000011146',
            'amt' => '1.00',
            'payAmt' => '0.99',
            'rmk' => '201804160000011146',
            'payUrl' => 'http://payapi.459r.com/orderPay.jhtml?no=65520180416160145704278353',
            'sign' => 'BBA5F7825658274B3BF9C12548D0BAC5',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $wGPay = new WGPay();
        $wGPay->setContainer($this->container);
        $wGPay->setClient($this->client);
        $wGPay->setResponse($response);
        $wGPay->setPrivateKey('test');
        $wGPay->setOptions($this->options);
        $encodeData = $wGPay->getVerifyData();

        $this->assertEquals('65520180416160145704278353', $encodeData['params']['no']);
        $this->assertEquals('http://payapi.459r.com/orderPay.jhtml', $encodeData['post_url']);
        $this->assertEquals('GET', $wGPay->getPayMethod());
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $this->options['paymentVendorId'] = '1097';

        $result = [
            'code' => '0000',
            'msg' => '下单成功',
            'merCode' => '2100002',
            'tradeNo' => '201804160000011146',
            'amt' => '1.00',
            'payAmt' => '0.98',
            'rmk' => '201804250000011387',
            'payUrl' => 'http://payapi.459r.com/orderPay.jhtml?no=65520180425155907622422603',
            'sign' => '33148FC1041E607A8C273CB4071A8F39',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $wGPay = new WGPay();
        $wGPay->setContainer($this->container);
        $wGPay->setClient($this->client);
        $wGPay->setResponse($response);
        $wGPay->setPrivateKey('test');
        $wGPay->setOptions($this->options);
        $encodeData = $wGPay->getVerifyData();

        $this->assertEquals('65520180425155907622422603', $encodeData['params']['no']);
        $this->assertEquals('http://payapi.459r.com/orderPay.jhtml', $encodeData['post_url']);
        $this->assertEquals('GET', $wGPay->getPayMethod());
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

        $wGPay = new WGPay();
        $wGPay->verifyOrderPayment([]);
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

        $wGPay = new WGPay();
        $wGPay->setPrivateKey('1234');
        $wGPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少加密簽名
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['sign']);

        $wGPay = new WGPay();
        $wGPay->setPrivateKey('1234');
        $wGPay->setOptions($this->returnResult);
        $wGPay->verifyOrderPayment([]);
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

        $wGPay = new WGPay();
        $wGPay->setPrivateKey('1234');
        $wGPay->setOptions($this->returnResult);
        $wGPay->verifyOrderPayment([]);
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

        $this->returnResult['payStatus'] = 'NotS';
        $this->returnResult['sign'] = 'F04AEF6278DEA748EDBC3A5E18E52F92';

        $wGPay = new WGPay();
        $wGPay->setPrivateKey('1234');
        $wGPay->setOptions($this->returnResult);
        $wGPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時單號不正確的情況
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $this->returnResult['sign'] = 'BF6554723853F142E51D956525CFA920';

        $entry = ['id' => '201606220000002806'];

        $wGPay = new WGPay();
        $wGPay->setPrivateKey('1234');
        $wGPay->setOptions($this->returnResult);
        $wGPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時金額不正確的情況
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $this->returnResult['sign'] = 'BF6554723853F142E51D956525CFA920';

        $entry = [
            'id' => '201804130000011103',
            'amount' => '10',
        ];

        $wGPay = new WGPay();
        $wGPay->setPrivateKey('1234');
        $wGPay->setOptions($this->returnResult);
        $wGPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回驗證成功
     */
    public function testReturnSuccess()
    {
        $this->returnResult['sign'] = 'BF6554723853F142E51D956525CFA920';

        $entry = [
            'id' => '201804130000011103',
            'amount' => '1',
        ];

        $wGPay = new WGPay();
        $wGPay->setPrivateKey('1234');
        $wGPay->setOptions($this->returnResult);
        $wGPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $wGPay->getMsg());
    }
}
