<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\TianXiaPay;
use Buzz\Message\Response;

class TianXiaPayTest extends DurianTestCase
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
            'orderId' => '201804100000045959',
            'orderCreateDate' => '2018-04-10 17:14:13',
            'amount' => '1',
            'notify_url' => 'http://www.seafood.help/',
            'ip' => '123.123.123.123',
            'paymentVendorId' => '1111',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.gate.vip8shop.com',
        ];

        $this->returnResult = [
            'service' => 'TRADE.NOTIFY',
            'merId' => '9527',
            'tradeNo' => '201804110000045979',
            'tradeDate' => '20180411',
            'opeNo' => '1088353',
            'opeDate' => '20180411',
            'amount' => '0.10',
            'status' => '1',
            'extra' => '',
            'payTime' => '20180411110153',
            'sign' => '1742536BD21D8A2D2B19F05B411E3152',
            'notifyType' => '1',
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

        $tianXiaPay = new TianXiaPay();
        $tianXiaPay->getVerifyData();
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

        $tianXiaPay = new TianXiaPay();
        $tianXiaPay->setPrivateKey('test');
        $tianXiaPay->getVerifyData();
    }

    /**
     * 測試支付時代入支付平台不支援的銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $this->option['paymentVendorId'] = '999';

        $tianXiaPay = new TianXiaPay();
        $tianXiaPay->setPrivateKey('test');
        $tianXiaPay->setOptions($this->option);
        $tianXiaPay->getVerifyData();
    }

    /**
     * 測試支付時缺少verify_url
     */
    public function testPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $this->option['verify_url'] = '';

        $tianXiaPay = new TianXiaPay();
        $tianXiaPay->setPrivateKey('test');
        $tianXiaPay->setOptions($this->option);
        $tianXiaPay->getVerifyData();
    }

    /**
     * 測試支付時返回缺少detail
     */
    public function testPayReturnWithoutDetail()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '<?xml version="1.0" encoding="utf-8"?>' .
            '<message><sign>49883D6E21BDB0CA9C26C0DD73D8583C</sign>' .
            '</message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $tianXiaPay = new TianXiaPay();
        $tianXiaPay->setPrivateKey('test');
        $tianXiaPay->setContainer($this->container);
        $tianXiaPay->setClient($this->client);
        $tianXiaPay->setResponse($response);
        $tianXiaPay->setOptions($this->option);
        $tianXiaPay->getVerifyData();
    }

    /**
     * 測試支付時返回缺少code
     */
    public function testPayReturnWithoutCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '<?xml version="1.0" encoding="utf-8"?>' .
            '<message><detail><desc>下单成功</desc>' .
            '<qrCode>aHR0cHM6Ly9xci45NTUxNi5jb20vMDAwMTAwMDAvNjIyNDE5NDQ1NTgyNDAwMDk5MzY4ODc4NjU4MjU1NDg=</qrCode>' .
            '</detail><sign>49883D6E21BDB0CA9C26C0DD73D8583C</sign>' .
            '</message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $tianXiaPay = new TianXiaPay();
        $tianXiaPay->setPrivateKey('test');
        $tianXiaPay->setContainer($this->container);
        $tianXiaPay->setClient($this->client);
        $tianXiaPay->setResponse($response);
        $tianXiaPay->setOptions($this->option);
        $tianXiaPay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '交易失败（签名错误）',
            180130
        );

        $result = '<?xml version="1.0" encoding="utf-8"?>' .
            '<message><detail><code>05</code><desc>交易失败（签名错误）</desc>' .
            '<qrCode>aHR0cHM6Ly9xci45NTUxNi5jb20vMDAwMTAwMDAvNjIyNDE5NDQ1NTgyNDAwMDk5MzY4ODc4NjU4MjU1NDg=</qrCode>' .
            '</detail><sign>49883D6E21BDB0CA9C26C0DD73D8583C</sign>' .
            '</message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $tianXiaPay = new TianXiaPay();
        $tianXiaPay->setPrivateKey('test');
        $tianXiaPay->setContainer($this->container);
        $tianXiaPay->setClient($this->client);
        $tianXiaPay->setResponse($response);
        $tianXiaPay->setOptions($this->option);
        $tianXiaPay->getVerifyData();
    }

    /**
     * 測試支付時返回缺少qrCode
     */
    public function testPayReturnWithoutQRCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '<?xml version="1.0" encoding="utf-8"?>' .
            '<message><detail><code>00</code><desc>下单成功</desc>' .
            '</detail><sign>49883D6E21BDB0CA9C26C0DD73D8583C</sign>' .
            '</message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $tianXiaPay = new TianXiaPay();
        $tianXiaPay->setPrivateKey('test');
        $tianXiaPay->setContainer($this->container);
        $tianXiaPay->setClient($this->client);
        $tianXiaPay->setResponse($response);
        $tianXiaPay->setOptions($this->option);
        $tianXiaPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $result = '<?xml version="1.0" encoding="utf-8"?>' .
            '<message><detail><code>00</code><desc>下单成功</desc>' .
            '<qrCode>aHR0cHM6Ly9xci45NTUxNi5jb20vMDAwMTAwMDAvNjIyNDE5NDQ1NTgyNDAwMDk5MzY4ODc4NjU4MjU1NDg=</qrCode>' .
            '</detail><sign>49883D6E21BDB0CA9C26C0DD73D8583C</sign>' .
            '</message>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $tianXiaPay = new TianXiaPay();
        $tianXiaPay->setPrivateKey('test');
        $tianXiaPay->setContainer($this->container);
        $tianXiaPay->setClient($this->client);
        $tianXiaPay->setResponse($response);
        $tianXiaPay->setOptions($this->option);
        $verifyData = $tianXiaPay->getVerifyData();

        $this->assertEmpty($verifyData);
        $this->assertSame('https://qr.95516.com/00010000/62241944558240009936887865825548', $tianXiaPay->getQrcode());
    }

    /**
     * 測試網銀支付
     */
    public function testBankPay()
    {
        $this->option['paymentVendorId'] = '1';

        $tianXiaPay = new TianXiaPay();
        $tianXiaPay->setPrivateKey('test');
        $tianXiaPay->setOptions($this->option);
        $verifyData = $tianXiaPay->getVerifyData();

        $this->assertEquals('TRADE.B2C', $verifyData['service']);
        $this->assertEquals('1.0.0.0', $verifyData['version']);
        $this->assertEquals('9527', $verifyData['merId']);
        $this->assertEquals('201804100000045959', $verifyData['tradeNo']);
        $this->assertEquals('20180410', $verifyData['tradeDate']);
        $this->assertEquals('1.00', $verifyData['amount']);
        $this->assertEquals('http://www.seafood.help/', $verifyData['notifyUrl']);
        $this->assertEquals('', $verifyData['extra']);
        $this->assertEquals('201804100000045959', $verifyData['summary']);
        $this->assertEquals('', $verifyData['expireTime']);
        $this->assertEquals('123.123.123.123', $verifyData['clientIp']);
        $this->assertEquals('ICBC', $verifyData['bankId']);
        $this->assertEquals('e2f8dadf804f6d6545d3840ec443318f', $verifyData['sign']);
        $this->assertArrayNotHasKey('typeId', $verifyData);
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $this->option['paymentVendorId'] = '1104';

        $tianXiaPay = new TianXiaPay();
        $tianXiaPay->setPrivateKey('test');
        $tianXiaPay->setOptions($this->option);
        $verifyData = $tianXiaPay->getVerifyData();

        $this->assertEquals('TRADE.H5PAY', $verifyData['service']);
        $this->assertEquals('1.0.0.0', $verifyData['version']);
        $this->assertEquals('9527', $verifyData['merId']);
        $this->assertEquals('201804100000045959', $verifyData['tradeNo']);
        $this->assertEquals('20180410', $verifyData['tradeDate']);
        $this->assertEquals('1.00', $verifyData['amount']);
        $this->assertEquals('http://www.seafood.help/', $verifyData['notifyUrl']);
        $this->assertEquals('', $verifyData['extra']);
        $this->assertEquals('201804100000045959', $verifyData['summary']);
        $this->assertEquals('', $verifyData['expireTime']);
        $this->assertEquals('123.123.123.123', $verifyData['clientIp']);
        $this->assertEquals('3', $verifyData['typeId']);
        $this->assertEquals('c55b5145775f9f1d2de92ccf445ea209', $verifyData['sign']);
        $this->assertArrayNotHasKey('bankId', $verifyData);
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

        $tianXiaPay = new TianXiaPay();
        $tianXiaPay->verifyOrderPayment([]);
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

        $tianXiaPay = new TianXiaPay();
        $tianXiaPay->setPrivateKey('test');
        $tianXiaPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['sign']);

        $tianXiaPay = new TianXiaPay();
        $tianXiaPay->setPrivateKey('test');
        $tianXiaPay->setOptions($this->returnResult);
        $tianXiaPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時sign簽名驗證錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $this->returnResult['sign'] = 'E24CCC6FBEB36590155AA01275688549';

        $tianXiaPay = new TianXiaPay();
        $tianXiaPay->setPrivateKey('test');
        $tianXiaPay->setOptions($this->returnResult);
        $tianXiaPay->verifyOrderPayment([]);
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

        $this->returnResult['status'] = '2';
        $this->returnResult['sign'] = 'D2DDF3FE396ADC65711348D22FED7E53';

        $tianXiaPay = new TianXiaPay();
        $tianXiaPay->setPrivateKey('test');
        $tianXiaPay->setOptions($this->returnResult);
        $tianXiaPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單編號錯誤
     */
    public function testReturnOrderIdFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $entry = ['id' => '201709140000007051'];

        $tianXiaPay = new TianXiaPay();
        $tianXiaPay->setPrivateKey('test');
        $tianXiaPay->setOptions($this->returnResult);
        $tianXiaPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為訂單金額錯誤
     */
    public function testReturnAmountFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $entry = [
            'id' => '201804110000045979',
            'amount' => '0.02',
        ];

        $tianXiaPay = new TianXiaPay();
        $tianXiaPay->setPrivateKey('test');
        $tianXiaPay->setOptions($this->returnResult);
        $tianXiaPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $entry = [
            'id' => '201804110000045979',
            'amount' => '0.1',
        ];

        $tianXiaPay = new TianXiaPay();
        $tianXiaPay->setPrivateKey('test');
        $tianXiaPay->setOptions($this->returnResult);
        $tianXiaPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $tianXiaPay->getMsg());
    }
}
