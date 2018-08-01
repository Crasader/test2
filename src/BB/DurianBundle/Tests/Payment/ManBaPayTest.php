<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\ManBaPay;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class ManBaPayTest extends DurianTestCase
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
            'number' => '9527',
            'orderId' => '201805070000046184',
            'amount' => '1',
            'ip' => '10.123.123.123',
            'notify_url' => 'http://www.seafood.help/',
            'paymentVendorId' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.api.fzmanba.com',
            'merchant_extra' => [
                'merAccount' => '7259',
            ]
        ];

        $return = [
            'sign' => '6b7992c0e842b8297c7503aac0317cc54167e762',
            'amount' => '1.000000',
            'merAccount' => '7259',
            'mbOrderId' => '1000119901525345599507i10nx9iuu3',
            'orderStatus' => 'SUCCESS',
            'orderId' => '201805070000046184',
        ];

        ksort($return);

        $this->returnResult = [
            'merAccount' => '9527',
            'data' => base64_encode(openssl_encrypt(json_encode($return), 'aes-256-ecb', 'test', OPENSSL_RAW_DATA)),
            'orderId' => '201805070000046184',
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

        $manBaPay = new ManBaPay();
        $manBaPay->getVerifyData();
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

        $manBaPay = new ManBaPay();
        $manBaPay->setPrivateKey('test');
        $manBaPay->getVerifyData();
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

        $manBaPay = new ManBaPay();
        $manBaPay->setPrivateKey('test');
        $manBaPay->setOptions($this->option);
        $manBaPay->getVerifyData();
    }

    /**
     * 測試支付時沒有帶入MerchantExtra的情況
     */
    public function testPayWithoutMerchantExtra()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $this->option['merchant_extra'] = [];

        $manBaPay = new ManBaPay();
        $manBaPay->setPrivateKey('test');
        $manBaPay->setOptions($this->option);
        $manBaPay->getVerifyData();
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

        $manBaPay = new ManBaPay();
        $manBaPay->setPrivateKey('test');
        $manBaPay->setOptions($this->option);
        $manBaPay->getVerifyData();
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

        $result = [];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $manBaPay = new ManBaPay();
        $manBaPay->setContainer($this->container);
        $manBaPay->setClient($this->client);
        $manBaPay->setResponse($response);
        $manBaPay->setPrivateKey('test');
        $manBaPay->setOptions($this->option);
        $manBaPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回msg
     */
    public function testPayReturnWithoutMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = ['code' => '000000'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $manBaPay = new ManBaPay();
        $manBaPay->setContainer($this->container);
        $manBaPay->setClient($this->client);
        $manBaPay->setResponse($response);
        $manBaPay->setPrivateKey('test');
        $manBaPay->setOptions($this->option);
        $manBaPay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '支付金额为空或不正确',
            180130
        );

        $result = [
            'code' => '101000',
            'msg' => '支付金额为空或不正确',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $manBaPay = new ManBaPay();
        $manBaPay->setContainer($this->container);
        $manBaPay->setClient($this->client);
        $manBaPay->setResponse($response);
        $manBaPay->setPrivateKey('test');
        $manBaPay->setOptions($this->option);
        $manBaPay->getVerifyData();
    }

    /**
     * 測試QQ二維支付時沒有返回qrCode
     */
    public function testQQScanPayReturnWithoutQrCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $this->option['paymentVendorId'] = '1103';

        $result = [
            'data' => [
                'merAccount' => '7259',
                'mbOrderId' => '1000119901525681180703y3z7x93e53',
                'orderId' => '201805070000046184',
            ],
            'code' => '000000',
            'msg' => '成功',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $manBaPay = new ManBaPay();
        $manBaPay->setContainer($this->container);
        $manBaPay->setClient($this->client);
        $manBaPay->setResponse($response);
        $manBaPay->setPrivateKey('test');
        $manBaPay->setOptions($this->option);
        $manBaPay->getVerifyData();
    }

    /**
     * 測試手機支付時沒有返回payUrl
     */
    public function testPhonePayReturnWithoutPayUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $this->option['paymentVendorId'] = '1098';

        $result = [
            'data' => [
                'qrCode' => '',
                'merAccount' => '7259',
                'mbOrderId' => '1000119901525681511719khb6od71yy',
                'orderId' => '201805070000046184',
            ],
            'code' => '000000',
            'msg' => '成功',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $manBaPay = new ManBaPay();
        $manBaPay->setContainer($this->container);
        $manBaPay->setClient($this->client);
        $manBaPay->setResponse($response);
        $manBaPay->setPrivateKey('test');
        $manBaPay->setOptions($this->option);
        $manBaPay->getVerifyData();
    }

    /**
     * 測試網銀支付時沒有返回payUrl
     */
    public function testPayReturnWithoutPayUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'data' => [
                'qrCode' => '',
                'merAccount' => '7259',
                'mbOrderId' => '1000119901525681714242ffx874160j',
                'orderId' => '201805070000046184',
            ],
            'code' => '000000',
            'msg' => '成功',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $manBaPay = new ManBaPay();
        $manBaPay->setContainer($this->container);
        $manBaPay->setClient($this->client);
        $manBaPay->setResponse($response);
        $manBaPay->setPrivateKey('test');
        $manBaPay->setOptions($this->option);
        $manBaPay->getVerifyData();
    }

    /**
     * 測試QQ二維支付
     */
    public function testQQScanPay()
    {
        $this->option['paymentVendorId'] = '1103';

        $result = [
            'data' => [
                'qrCode' => 'https://qpay.qq.com/qr/5c955c55',
                'merAccount' => '7259',
                'mbOrderId' => '1000119901525681180703y3z7x93e53',
                'orderId' => '201805070000046184',
            ],
            'code' => '000000',
            'msg' => '成功',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $manBaPay = new ManBaPay();
        $manBaPay->setContainer($this->container);
        $manBaPay->setClient($this->client);
        $manBaPay->setResponse($response);
        $manBaPay->setPrivateKey('test');
        $manBaPay->setOptions($this->option);
        $data = $manBaPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://qpay.qq.com/qr/5c955c55', $manBaPay->getQrcode());
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $this->option['paymentVendorId'] = '1098';

        $payUrl = 'http://qr.sytpay.cn/api/v1/create.php?payType=syt&orderAmount=1.00&partner=120180507026462541&' .
            'orderId=02180507100000083622&payMethod=24&sign=2BBC2676946A35AE107AB0DE9B51300F&signType=MD5' .
            '&notifyUrl=http://120.77.34.148:8087/ysfQrcodeNotify.do&version=1.0';

        $result = [
            'data' => [
                'qrCode' => '',
                'merAccount' => '7259',
                'mbOrderId' => '1000119901525681511719khb6od71yy',
                'payUrl' => $payUrl,
                'orderId' => '201805070000046184',
            ],
            'code' => '000000',
            'msg' => '成功',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $manBaPay = new ManBaPay();
        $manBaPay->setContainer($this->container);
        $manBaPay->setClient($this->client);
        $manBaPay->setResponse($response);
        $manBaPay->setPrivateKey('test');
        $manBaPay->setOptions($this->option);
        $data = $manBaPay->getVerifyData();

        $this->assertEquals('http://qr.sytpay.cn/api/v1/create.php', $data['post_url']);
        $this->assertEquals('syt', $data['params']['payType']);
        $this->assertEquals('1.00', $data['params']['orderAmount']);
        $this->assertEquals('120180507026462541', $data['params']['partner']);
        $this->assertEquals('02180507100000083622', $data['params']['orderId']);
        $this->assertEquals('24', $data['params']['payMethod']);
        $this->assertEquals('2BBC2676946A35AE107AB0DE9B51300F', $data['params']['sign']);
        $this->assertEquals('MD5', $data['params']['signType']);
        $this->assertEquals('http://120.77.34.148:8087/ysfQrcodeNotify.do', $data['params']['notifyUrl']);
        $this->assertEquals('1.0', $data['params']['version']);
        $this->assertEquals('GET', $manBaPay->getPayMethod());
    }

    /**
     * 測試網銀支付
     */
    public function testPay()
    {
        $payUrl = 'https://api.fzmanba.com/paygateway/mbgateway/banks?merAccount=7259' .
            '&orderId=1000119901525681714242ffx874160j';

        $result = [
            'data' => [
                'qrCode' => '',
                'merAccount' => '7259',
                'mbOrderId' => '1000119901525681714242ffx874160j',
                'payUrl' => $payUrl,
                'orderId' => '201805070000046184',
            ],
            'code' => '000000',
            'msg' => '成功',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $manBaPay = new ManBaPay();
        $manBaPay->setContainer($this->container);
        $manBaPay->setClient($this->client);
        $manBaPay->setResponse($response);
        $manBaPay->setPrivateKey('test');
        $manBaPay->setOptions($this->option);
        $data = $manBaPay->getVerifyData();

        $this->assertEquals('https://api.fzmanba.com/paygateway/mbgateway/banks', $data['post_url']);
        $this->assertEquals('7259', $data['params']['merAccount']);
        $this->assertEquals('1000119901525681714242ffx874160j', $data['params']['orderId']);
        $this->assertEquals('GET', $manBaPay->getPayMethod());
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

        $manBaPay = new ManBaPay();
        $manBaPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未指定data參數
     */
    public function testReturnWithoutData()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['data']);

        $manBaPay = new ManBaPay();
        $manBaPay->setPrivateKey('test');
        $manBaPay->setOptions($this->returnResult);
        $manBaPay->verifyOrderPayment([]);
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

        $this->returnResult['data'] = '';

        $manBaPay = new ManBaPay();
        $manBaPay->setPrivateKey('test');
        $manBaPay->setOptions($this->returnResult);
        $manBaPay->verifyOrderPayment([]);
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

        $return = [
            'amount' => '1.000000',
            'merAccount' => '7259',
            'mbOrderId' => '1000119901525345599507i10nx9iuu3',
            'orderStatus' => 'SUCCESS',
            'orderId' => '201805070000046184',
        ];

        ksort($return);

        $this->returnResult = [
            'merAccount' => '9527',
            'data' => base64_encode(openssl_encrypt(json_encode($return), 'aes-256-ecb', 'test', OPENSSL_RAW_DATA)),
            'orderId' => '201805070000046184',
        ];

        $manBaPay = new ManBaPay();
        $manBaPay->setPrivateKey('test');
        $manBaPay->setOptions($this->returnResult);
        $manBaPay->verifyOrderPayment([]);
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

        $return = [
            'sign' => '11ddf8aff2181570f3fa518da542b8322f55d148',
            'amount' => '1.000000',
            'merAccount' => '7259',
            'mbOrderId' => '1000119901525345599507i10nx9iuu3',
            'orderStatus' => 'SUCCESS',
            'orderId' => '201805070000046184',
        ];

        ksort($return);

        $this->returnResult = [
            'merAccount' => '9527',
            'data' => base64_encode(openssl_encrypt(json_encode($return), 'aes-256-ecb', 'test', OPENSSL_RAW_DATA)),
            'orderId' => '201805070000046184',
        ];

        $manBaPay = new ManBaPay();
        $manBaPay->setPrivateKey('test');
        $manBaPay->setOptions($this->returnResult);
        $manBaPay->verifyOrderPayment([]);
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

        $return = [
            'sign' => '11ddf8aff2181570f3fa518da542b8322f55d148',
            'amount' => '1.000000',
            'merAccount' => '7259',
            'mbOrderId' => '1000119901525345599507i10nx9iuu3',
            'orderStatus' => 'FAILED',
            'orderId' => '201805070000046184',
        ];

        ksort($return);

        $this->returnResult = [
            'merAccount' => '9527',
            'data' => base64_encode(openssl_encrypt(json_encode($return), 'aes-256-ecb', 'test', OPENSSL_RAW_DATA)),
            'orderId' => '201805070000046184',
        ];

        $manBaPay = new ManBaPay();
        $manBaPay->setPrivateKey('test');
        $manBaPay->setOptions($this->returnResult);
        $manBaPay->verifyOrderPayment([]);
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

        $manBaPay = new ManBaPay();
        $manBaPay->setPrivateKey('test');
        $manBaPay->setOptions($this->returnResult);
        $manBaPay->verifyOrderPayment($entry);
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
            'id' => '201805070000046184',
            'amount' => '123',
        ];

        $manBaPay = new ManBaPay();
        $manBaPay->setPrivateKey('test');
        $manBaPay->setOptions($this->returnResult);
        $manBaPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $entry = [
            'id' => '201805070000046184',
            'amount' => '1',
        ];

        $manBaPay = new ManBaPay();
        $manBaPay->setPrivateKey('test');
        $manBaPay->setOptions($this->returnResult);
        $manBaPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $manBaPay->getMsg());
    }
}
