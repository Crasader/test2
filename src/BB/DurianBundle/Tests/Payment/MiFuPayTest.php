<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\MiFuPay;
use Buzz\Message\Response;

class MiFuPayTest extends DurianTestCase
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
            'paymentVendorId' => '1',
            'number' => '1001',
            'notify_url' => 'http://pay.my/pay/reutrn.php',
            'orderId' => '201806150000005583',
            'amount' => '1',
            'orderCreateDate' => '2017-06-15 12:26:41',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $this->returnResult = [
            'code' => '1000',
            'billno' => '201806150000005583',
            'merchant' => '1001',
            'amount' => '1',
            'bank' => 'ICBC',
            'status' => '110',
            'sign_type' => 'MD5',
            'pay_time' => '20180615214146',
            'msg' => '',
            'sign' => 'f3c0f0e0b4c14b43ba2e225b3ad998a9',
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

        $miFuPay = new MiFuPay();
        $miFuPay->getVerifyData();
    }

    /**
     * 測試支付時沒指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $miFuPay = new MiFuPay();
        $miFuPay->setPrivateKey('test');
        $miFuPay->getVerifyData();
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

        $miFuPay = new MiFuPay();
        $miFuPay->setPrivateKey('test');
        $miFuPay->setOptions($this->option);
        $miFuPay->getVerifyData();
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

        $this->option['paymentVendorId'] = '1098';
        $this->option['verify_url'] = '';

        $miFuPay = new MiFuPay();
        $miFuPay->setPrivateKey('test');
        $miFuPay->setOptions($this->option);
        $miFuPay->getVerifyData();
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

        $this->option['paymentVendorId'] = '1098';

        $result = [
            'tradeNo' => '201807160000005884',
            'amount' => '1.00',
            'type' => 'H5_ALIPAY',
            'qrCode' => 'https://qr.alipay.com/bax041072jkzvgnxfd2d6054',
            'sign' => '98bd6139c44b5f7f7034d447201a5852',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $miFuPay = new MiFuPay();
        $miFuPay->setContainer($this->container);
        $miFuPay->setClient($this->client);
        $miFuPay->setResponse($response);
        $miFuPay->setPrivateKey('test');
        $miFuPay->setOptions($this->option);
        $miFuPay->getVerifyData();
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

        $this->option['paymentVendorId'] = '1098';

        $result = [
            'code' => '0',
            'data' => 'Error',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $miFuPay = new MiFuPay();
        $miFuPay->setContainer($this->container);
        $miFuPay->setClient($this->client);
        $miFuPay->setResponse($response);
        $miFuPay->setPrivateKey('test');
        $miFuPay->setOptions($this->option);
        $miFuPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回qrCode
     */
    public function testPayReturnWithoutQrCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $this->option['paymentVendorId'] = '1098';

        $result = [
            'code' => '1000',
            'tradeNo' => '201807160000005884',
            'amount' => '1.00',
            'type' => 'H5_ALIPAY',
            'sign' => '98bd6139c44b5f7f7034d447201a5852',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $miFuPay = new MiFuPay();
        $miFuPay->setContainer($this->container);
        $miFuPay->setClient($this->client);
        $miFuPay->setResponse($response);
        $miFuPay->setPrivateKey('test');
        $miFuPay->setOptions($this->option);
        $miFuPay->getVerifyData();
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $this->option['paymentVendorId'] = '1098';

        $result = [
            'code' => '1000',
            'tradeNo' => '201807160000005884',
            'amount' => '1.00',
            'type' => 'H5_ALIPAY',
            'qrCode' => 'https://qr.alipay.com/bax041072jkzvgnxfd2d6054',
            'sign' => '98bd6139c44b5f7f7034d447201a5852',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $miFuPay = new MiFuPay();
        $miFuPay->setContainer($this->container);
        $miFuPay->setClient($this->client);
        $miFuPay->setResponse($response);
        $miFuPay->setPrivateKey('test');
        $miFuPay->setOptions($this->option);
        $data = $miFuPay->getVerifyData();

        $this->assertEquals('https://qr.alipay.com/bax041072jkzvgnxfd2d6054', $data['post_url']);
        $this->assertEmpty($data['params']);
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $miFuPay = new MiFuPay();
        $miFuPay->setPrivateKey('test');
        $miFuPay->setOptions($this->option);
        $data = $miFuPay->getVerifyData();

        $this->assertEquals('1001', $data['merchant']);
        $this->assertEquals('201806150000005583', $data['billno']);
        $this->assertEquals('1.00', $data['amount']);
        $this->assertEquals('http://pay.my/pay/reutrn.php', $data['notify_url']);
        $this->assertEquals('http://pay.my/pay/reutrn.php', $data['return_url']);
        $this->assertEquals('MD5', $data['sign_type']);
        $this->assertEquals('ICBC', $data['bank']);
        $this->assertEquals('20170615122641', $data['pay_time']);
        $this->assertEquals('2d51270e8147a0724d17e2afc13a3e5e', $data['sign']);
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

        $miFuPay = new MiFuPay();
        $miFuPay->verifyOrderPayment([]);
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

        $miFuPay = new MiFuPay();
        $miFuPay->setPrivateKey('test');
        $miFuPay->verifyOrderPayment([]);
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

        unset($this->returnResult['sign']);

        $miFuPay = new MiFuPay();
        $miFuPay->setPrivateKey('test');
        $miFuPay->setOptions($this->returnResult);
        $miFuPay->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = 'd0f511534bee2da2fcbaeb2fa0f2fc90';

        $miFuPay = new MiFuPay();
        $miFuPay->setPrivateKey('test');
        $miFuPay->setOptions($this->returnResult);
        $miFuPay->verifyOrderPayment([]);
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

        $this->returnResult['status'] = '210';
        $this->returnResult['sign'] = 'b93f71a1d893e5027e8dd7232afaf47f';

        $miFuPay = new MiFuPay();
        $miFuPay->setPrivateKey('test');
        $miFuPay->setOptions($this->returnResult);
        $miFuPay->verifyOrderPayment([]);
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

        $entry = ['id' => '201806150000005582'];

        $miFuPay = new MiFuPay();
        $miFuPay->setPrivateKey('test');
        $miFuPay->setOptions($this->returnResult);
        $miFuPay->verifyOrderPayment($entry);
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

        $entry = [
            'id' => '201806150000005583',
            'amount' => '100',
        ];

        $miFuPay = new MiFuPay();
        $miFuPay->setPrivateKey('test');
        $miFuPay->setOptions($this->returnResult);
        $miFuPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $entry = [
            'id' => '201806150000005583',
            'amount' => '1',
        ];

        $miFuPa = new MiFuPay();
        $miFuPa->setPrivateKey('test');
        $miFuPa->setOptions($this->returnResult);
        $miFuPa->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $miFuPa->getMsg());
    }
}