<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\TaiHePay;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class TaiHePayTest extends DurianTestCase
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
     * 提交給支付平台時需要的參數
     *
     * @var array
     */
    private $option;

    /**
     * 支付成功時通知的參數
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

        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();
        $this->container->expects($this->any())
            ->method('get')
            ->willReturn($mockLogger);

        $this->client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $this->option = [
            'amount' => '100',
            'orderCreateDate' => '2018-07-11 16:21:46',
            'notify_url' => 'http://www.seafood.help/',
            'number' => '9527',
            'orderId' => '201807110000046602',
            'paymentVendorId' => '1092',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.47.101.43.243',
        ];

        $this->returnResult = [
            'payId' => '1016961128674222080',
            'prdOrdNo' => '201807110000046602',
            'transType' => '008',
            'orderAmount' => '10000',
            'signType' => 'MD5',
            'merId' => '9527',
            'versionId' => '1.0',
            'payTime' => '20180711162356',
            'orderStatus' => '01',
            'synNotifyUrl' => 'http://www.seafood.help/',
            'signData' => '5730EFBED263FE2C5FDE739C6D89F4DE',
            'asynNotifyUrl' => 'http://www.seafood.help/',
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

        $taiHePay = new TaiHePay();
        $taiHePay->getVerifyData();
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

        $taiHePay = new TaiHePay();
        $taiHePay->setPrivateKey('test');
        $taiHePay->getVerifyData();
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

        $this->option['paymentVendorId'] = '999';

        $taiHePay = new TaiHePay();
        $taiHePay->setPrivateKey('test');
        $taiHePay->setOptions($this->option);
        $taiHePay->getVerifyData();
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

        $this->option['verify_url'] = '';

        $taiHePay = new TaiHePay();
        $taiHePay->setPrivateKey('test');
        $taiHePay->setOptions($this->option);
        $taiHePay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回retCode
     */
    public function testPayReturnWithoutRetCode()
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
        $response->addHeader('Content-Type:application/json');

        $taiHePay = new TaiHePay();
        $taiHePay->setContainer($this->container);
        $taiHePay->setClient($this->client);
        $taiHePay->setResponse($response);
        $taiHePay->setPrivateKey('test');
        $taiHePay->setOptions($this->option);
        $taiHePay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗且有retMsg
     */
    public function testPayReturnNotSuccessWithRetMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '9527支付平台MD5验签失败…',
            180130
        );

        $result = [
            'retCode' => '1518024',
            'retMsg' => '9527支付平台MD5验签失败…',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $taiHePay = new TaiHePay();
        $taiHePay->setContainer($this->container);
        $taiHePay->setClient($this->client);
        $taiHePay->setResponse($response);
        $taiHePay->setPrivateKey('test');
        $taiHePay->setOptions($this->option);
        $taiHePay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $result = ['retCode' => '1518024'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $taiHePay = new TaiHePay();
        $taiHePay->setContainer($this->container);
        $taiHePay->setClient($this->client);
        $taiHePay->setResponse($response);
        $taiHePay->setPrivateKey('test');
        $taiHePay->setOptions($this->option);
        $taiHePay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回qrcode
     */
    public function testPayReturnWithoutQrcode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'platmerord' => '1016947528173875200',
            'retCode' => '1',
            'signType' => 'MD5',
            'retMsg' => '下单成功',
            'signData' => 'F4A54A63E4A1B024E2CB1C72D7FAD77A',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $taiHePay = new TaiHePay();
        $taiHePay->setContainer($this->container);
        $taiHePay->setClient($this->client);
        $taiHePay->setResponse($response);
        $taiHePay->setPrivateKey('test');
        $taiHePay->setOptions($this->option);
        $taiHePay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $this->option['paymentVendorId'] = '1098';

        $taiHePay = new TaiHePay();
        $taiHePay->setPrivateKey('test');
        $taiHePay->setOptions($this->option);
        $data = $taiHePay->getVerifyData();

        $this->assertEquals('1.0', $data['versionId']);
        $this->assertEquals('10000', $data['orderAmount']);
        $this->assertEquals('20180711162146', $data['orderDate']);
        $this->assertEquals('RMB', $data['currency']);
        $this->assertEquals('008', $data['transType']);
        $this->assertEquals('http://www.seafood.help/', $data['asynNotifyUrl']);
        $this->assertEquals('http://www.seafood.help/', $data['synNotifyUrl']);
        $this->assertEquals('MD5', $data['signType']);
        $this->assertEquals('9527', $data['merId']);
        $this->assertEquals('201807110000046602', $data['prdOrdNo']);
        $this->assertEquals('00028', $data['payMode']);
        $this->assertEquals('D00', $data['receivableType']);
        $this->assertEquals('6D152B2E43892B2A0326787CE0108EB3', $data['signData']);
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $result = [
            'platmerord' => '1016947528173875200',
            'retCode' => '1',
            'signType' => 'MD5',
            'retMsg' => '下单成功',
            'qrcode' => 'HTTPS://QR.ALIPAY.COM/FKX084950X9QWRTV5LQEA9?t=1531294159729',
            'signData' => 'F4A54A63E4A1B024E2CB1C72D7FAD77A',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $taiHePay = new TaiHePay();
        $taiHePay->setContainer($this->container);
        $taiHePay->setClient($this->client);
        $taiHePay->setResponse($response);
        $taiHePay->setPrivateKey('test');
        $taiHePay->setOptions($this->option);
        $data = $taiHePay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertSame('HTTPS://QR.ALIPAY.COM/FKX084950X9QWRTV5LQEA9?t=1531294159729', $taiHePay->getQrcode());
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

        $taiHePay = new TaiHePay();
        $taiHePay->verifyOrderPayment([]);
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

        $taiHePay = new TaiHePay();
        $taiHePay->setPrivateKey('test');
        $taiHePay->verifyOrderPayment([]);
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

        unset($this->returnResult['signData']);

        $taiHePay = new TaiHePay();
        $taiHePay->setPrivateKey('test');
        $taiHePay->setOptions($this->returnResult);
        $taiHePay->verifyOrderPayment([]);
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

        $this->returnResult['signData'] = '077760732B0CF5C0655611D18CB6C3CA';

        $taiHePay = new TaiHePay();
        $taiHePay->setPrivateKey('test');
        $taiHePay->setOptions($this->returnResult);
        $taiHePay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時訂單未支付
     */
    public function testReturnUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $this->returnResult['orderStatus'] = '00';
        $this->returnResult['signData'] = 'B8FB0C2D237E99C778017CC6C49DB85A';

        $taiHePay = new TaiHePay();
        $taiHePay->setPrivateKey('test');
        $taiHePay->setOptions($this->returnResult);
        $taiHePay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時訂單支付中
     */
    public function testReturnOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $this->returnResult['orderStatus'] = '02';
        $this->returnResult['signData'] = 'E31B42D5FA9EF99516B93329387BE7DE';

        $taiHePay = new TaiHePay();
        $taiHePay->setPrivateKey('test');
        $taiHePay->setOptions($this->returnResult);
        $taiHePay->verifyOrderPayment([]);
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

        $this->returnResult['orderStatus'] = '03';
        $this->returnResult['signData'] = '54AC61D82A1994E0F4D80F47E935EDEC';

        $taiHePay = new TaiHePay();
        $taiHePay->setPrivateKey('test');
        $taiHePay->setOptions($this->returnResult);
        $taiHePay->verifyOrderPayment([]);
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

        $entry = ['id' => '9453'];

        $taiHePay = new TaiHePay();
        $taiHePay->setPrivateKey('test');
        $taiHePay->setOptions($this->returnResult);
        $taiHePay->verifyOrderPayment($entry);
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
            'id' => '201807110000046602',
            'amount' => '10',
        ];

        $taiHePay = new TaiHePay();
        $taiHePay->setPrivateKey('test');
        $taiHePay->setOptions($this->returnResult);
        $taiHePay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $entry = [
            'id' => '201807110000046602',
            'amount' => '100',
        ];

        $taiHePay = new TaiHePay();
        $taiHePay->setPrivateKey('test');
        $taiHePay->setOptions($this->returnResult);
        $taiHePay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $taiHePay->getMsg());
    }
}
