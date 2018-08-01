<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\MovePay;
use Buzz\Message\Response;

class MovePayTest extends DurianTestCase
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
     * 支付時的參數
     *
     * @var array
     */
    private $sourceData;

    /**
     * 對外返回結果
     *
     * @var array
     */
    private $verifyResult;

    /**
     * 返回時的參數
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

        $this->sourceData = [
            'number' => '888110151110001',
            'amount' => '1',
            'orderId' => '201806150000011683',
            'paymentVendorId' => '1111',
            'notify_url' => 'http://youUgly.ugly',
            'verify_url' => 'payment.http.api.togetherpay.cn',
            'verify_ip' => ['172.26.54.41', '172.26.54.41'],
        ];

        $this->verifyResult = [
            'message' => '下单成功',
            'respCode' => '00',
            'merchno' => '888110151110001',
            'refno' => '72180615100000000529',
            'traceno' => '201806150000011683',
            'barCode' => 'https://qr.95516.com/00010000/62842952376679394055637500423827',
            'signature' => '2A73CDDF08687D7546B6202B3CE140BF',
        ];

        $this->returnResult = [
            'amount' => '1.00',
            'channelOrderno' => '2018061519462409691390055296',
            'merchno' => '888110151110001',
            'orderno' => '72180615100000000529',
            'payType' => '32',
            'signature' => '903119587e8cadf7cd418fcfa86df5e5',
            'status' => '1',
            'traceno' => '201806150000011683',
            'transDate' => '2018-06-15',
            'transTime' => '19:46:07',
        ];
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

        $movePay = new MovePay();
        $movePay->getVerifyData();
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

        $movePay = new MovePay();
        $movePay->setPrivateKey('test');
        $movePay->getVerifyData();
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

        $this->sourceData['paymentVendorId'] = '666666';

        $movePay = new MovePay();
        $movePay->setPrivateKey('test');
        $movePay->setOptions($this->sourceData);
        $movePay->getVerifyData();
    }

    /**
     * 測試二維支付時沒有帶入verify_url的情況
     */
    public function testQrcodePayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $this->sourceData['verify_url'] = '';

        $movePay = new MovePay();
        $movePay->setPrivateKey('test');
        $movePay->setOptions($this->sourceData);
        $movePay->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回respCode
     */
    public function testQrcodePayReturnWithoutRespCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->verifyResult['respCode']);

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', json_encode($this->verifyResult)));
        $response->addHeader('HTTP/1.1 200 OK');

        $movePay = new MovePay();
        $movePay->setContainer($this->container);
        $movePay->setClient($this->client);
        $movePay->setResponse($response);
        $movePay->setPrivateKey('test');
        $movePay->setOptions($this->sourceData);
        $movePay->getVerifyData();
    }

    /**
     * 測試二維支付時返回提交失敗
     */
    public function testQrcodePayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '交易失败,商户的手续费未设置',
            180130
        );

        $this->verifyResult['respCode'] = '09';
        $this->verifyResult['message'] = '交易失败,商户的手续费未设置';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', json_encode($this->verifyResult)));
        $response->addHeader('HTTP/1.1 200 OK');

        $movePay = new MovePay();
        $movePay->setContainer($this->container);
        $movePay->setClient($this->client);
        $movePay->setResponse($response);
        $movePay->setPrivateKey('test');
        $movePay->setOptions($this->sourceData);
        $movePay->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回barCode
     */
    public function testQrcodePayReturnWithoutBarCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->verifyResult['barCode']);

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', json_encode($this->verifyResult)));
        $response->addHeader('HTTP/1.1 200 OK');

        $movePay = new MovePay();
        $movePay->setContainer($this->container);
        $movePay->setClient($this->client);
        $movePay->setResponse($response);
        $movePay->setPrivateKey('test');
        $movePay->setOptions($this->sourceData);
        $movePay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', json_encode($this->verifyResult)));
        $response->addHeader('HTTP/1.1 200 OK');

        $movePay = new MovePay();
        $movePay->setContainer($this->container);
        $movePay->setClient($this->client);
        $movePay->setResponse($response);
        $movePay->setPrivateKey('test');
        $movePay->setOptions($this->sourceData);
        $data = $movePay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://qr.95516.com/00010000/62842952376679394055637500423827', $movePay->getQrcode());
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $payUrl = 'https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027&_bid=2183&t=5Va87931f423823844383bbfbb6bfa19';
        $this->sourceData['paymentVendorId'] = '1098';
        $this->verifyResult['barCode'] = $payUrl;

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', json_encode($this->verifyResult)));
        $response->addHeader('HTTP/1.1 200 OK');

        $movePay = new MovePay();
        $movePay->setContainer($this->container);
        $movePay->setClient($this->client);
        $movePay->setResponse($response);
        $movePay->setPrivateKey('test');
        $movePay->setOptions($this->sourceData);
        $data = $movePay->getVerifyData();

        $this->assertEquals('https://myun.tenpay.com/mqq/pay/qrcode.html', $data['post_url']);
        $this->assertEquals('1027', $data['params']['_wv']);
        $this->assertEquals('2183', $data['params']['_bid']);
        $this->assertEquals('5Va87931f423823844383bbfbb6bfa19', $data['params']['t']);
        $this->assertEquals('GET', $movePay->getPayMethod());
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $this->sourceData['paymentVendorId'] = '1';

        $movePay = new MovePay();
        $movePay->setPrivateKey('test');
        $movePay->setOptions($this->sourceData);
        $encodeData = $movePay->getVerifyData();

        $this->assertEquals('888110151110001', $encodeData['merchno']);
        $this->assertEquals('1.00', $encodeData['amount']);
        $this->assertEquals('201806150000011683', $encodeData['traceno']);
        $this->assertEquals('2', $encodeData['channel']);
        $this->assertEquals('3002', $encodeData['bankCode']);
        $this->assertEquals('2', $encodeData['settleType']);
        $this->assertEquals('http://youUgly.ugly', $encodeData['notifyUrl']);
        $this->assertEquals('http://youUgly.ugly', $encodeData['returnUrl']);
        $this->assertEquals('2a0c1d56655fd1ffcbb78ed93c3feaa6', $encodeData['signature']);
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

        $movePay = new MovePay();
        $movePay->verifyOrderPayment([]);
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

        $movePay = new MovePay();
        $movePay->setPrivateKey('test');
        $movePay->verifyOrderPayment([]);
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

        unset($this->returnResult['signature']);

        $movePay = new MovePay();
        $movePay->setPrivateKey('test');
        $movePay->setOptions($this->returnResult);
        $movePay->verifyOrderPayment([]);
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

        $this->returnResult['signature'] = 'error';

        $movePay = new MovePay();
        $movePay->setPrivateKey('test');
        $movePay->setOptions($this->returnResult);
        $movePay->verifyOrderPayment([]);
    }

    /**
     * 測試網銀返回時訂單未支付
     */
    public function testOnlineBankReturnUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $this->returnResult['status'] = '1';
        $this->returnResult['signature'] = '903119587e8cadf7cd418fcfa86df5e5';

        $entry = [
            'payment_method_id' => '1',
        ];

        $movePay = new MovePay();
        $movePay->setPrivateKey('test');
        $movePay->setOptions($this->returnResult);
        $movePay->verifyOrderPayment($entry);
    }

    /**
     * 測試網銀返回時支付失敗
     */
    public function testOnlineBankReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $this->returnResult['status'] = '3';
        $this->returnResult['signature'] = '405369cdbc2a39e01d99bfc72994a2aa';

        $entry = [
            'payment_method_id' => '1',
        ];

        $movePay = new MovePay();
        $movePay->setPrivateKey('test');
        $movePay->setOptions($this->returnResult);
        $movePay->verifyOrderPayment($entry);
    }

    /**
     * 測試二維返回時訂單未支付
     */
    public function testQrcodeReturnUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $this->returnResult['status'] = '0';
        $this->returnResult['signature'] = '287f4077a0a0b8f8cee89478fe1ffb7b';

        $entry = [
            'payment_method_id' => '8',
        ];

        $movePay = new MovePay();
        $movePay->setPrivateKey('test');
        $movePay->setOptions($this->returnResult);
        $movePay->verifyOrderPayment($entry);
    }

    /**
     * 測試二維返回時支付失敗
     */
    public function testQrcodeReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $this->returnResult['status'] = '2';
        $this->returnResult['signature'] = 'd6013fd717bb3c581edbaeb1113f4881';

        $entry = [
            'payment_method_id' => '8',
        ];

        $movePay = new MovePay();
        $movePay->setPrivateKey('test');
        $movePay->setOptions($this->returnResult);
        $movePay->verifyOrderPayment($entry);
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

        $entry = [
            'id' => '9453',
            'payment_method_id' => '8',
        ];

        $movePay = new MovePay();
        $movePay->setPrivateKey('test');
        $movePay->setOptions($this->returnResult);
        $movePay->verifyOrderPayment($entry);
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

        $entry = [
            'id' => '201806150000011683',
            'amount' => '0.1',
            'payment_method_id' => '8',
        ];

        $movePay = new MovePay();
        $movePay->setPrivateKey('test');
        $movePay->setOptions($this->returnResult);
        $movePay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $entry = [
            'id' => '201806150000011683',
            'amount' => '1.00',
            'payment_method_id' => '8',
        ];

        $movePay = new MovePay();
        $movePay->setPrivateKey('test');
        $movePay->setOptions($this->returnResult);
        $movePay->verifyOrderPayment($entry);

        $this->assertEquals('success', $movePay->getMsg());
    }
}
