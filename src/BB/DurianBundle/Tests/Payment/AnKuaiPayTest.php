<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\AnKuaiPay;
use Buzz\Message\Response;

class AnKuaiPayTest extends DurianTestCase
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

        $anKuaiPay = new AnKuaiPay();
        $anKuaiPay->getVerifyData();
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

        $sourceData = ['number' => ''];

        $anKuaiPay = new AnKuaiPay();
        $anKuaiPay->setPrivateKey('test');
        $anKuaiPay->setOptions($sourceData);
        $anKuaiPay->getVerifyData();
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

        $sourceData = [
            'number' => '17040',
            'paymentVendorId' => '7',
            'amount' => '2.00',
            'orderId' => '201703210000001931',
            'notify_url' => 'http://two123.comxa.com/',
        ];

        $anKuaiPay = new AnKuaiPay();
        $anKuaiPay->setPrivateKey('test');
        $anKuaiPay->setOptions($sourceData);
        $anKuaiPay->getVerifyData();
    }

    /**
     * 測試支付時postUrl為空
     */
    public function testPayWithoutPostUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = [
            'number' => '17040',
            'paymentVendorId' => '1',
            'amount' => '2.00',
            'orderId' => '201703210000001931',
            'notify_url' => 'http://two123.comxa.com/',
            'postUrl' => '',
        ];

        $anKuaiPay = new AnKuaiPay();
        $anKuaiPay->setPrivateKey('test');
        $anKuaiPay->setOptions($sourceData);
        $anKuaiPay->getVerifyData();
    }

    /**
     * 測試支付(工商銀行網銀)
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '17040',
            'paymentVendorId' => '1',
            'amount' => '2.00',
            'orderId' => '201703210000001931',
            'notify_url' => 'http://two123.comxa.com/',
            'postUrl' => 'http://pay.ankuaipay.cn/PayBank.aspx',
        ];

        $anKuaiPay = new AnKuaiPay();
        $anKuaiPay->setPrivateKey('test');
        $anKuaiPay->setOptions($sourceData);
        $encodeData = $anKuaiPay->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['partner']);
        $this->assertEquals('ICBC', $encodeData['banktype']);
        $this->assertSame($sourceData['amount'], $encodeData['paymoney']);
        $this->assertEquals($sourceData['orderId'], $encodeData['ordernumber']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['callbackurl']);
        $this->assertEquals('', $encodeData['hrefbackurl']);
        $this->assertEquals('', $encodeData['attach']);
        $this->assertEquals('1', $encodeData['isshow']);
        $this->assertEquals('4489b6a4ea8731f303928163189c3d4d', $encodeData['sign']);

        // 檢查要提交的網址是否正確
        $data = [];
        $data['partner'] = $encodeData['partner'];
        $data['banktype'] = $encodeData['banktype'];
        $data['paymoney'] = $encodeData['paymoney'];
        $data['ordernumber'] = $encodeData['ordernumber'];
        $data['callbackurl'] = $encodeData['callbackurl'];
        $data['hrefbackurl'] = $encodeData['hrefbackurl'];
        $data['attach'] = $encodeData['attach'];
        $data['isshow'] = $encodeData['isshow'];
        $data['sign'] = $encodeData['sign'];

        $this->assertEquals($sourceData['postUrl'] . '?' .urldecode(http_build_query($data)), $encodeData['act_url']);
    }

    /**
     * 測試二維支付時缺少verify_url
     */
    public function testQrcodePayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => '17040',
            'paymentVendorId' => '1092',
            'amount' => '2',
            'orderId' => '201703210000001931',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => '',
        ];

        $anKuaiPay = new AnKuaiPay();
        $anKuaiPay->setPrivateKey('test');
        $anKuaiPay->setOptions($sourceData);
        $anKuaiPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回結果失敗
     */
    public function testQrcodePayReturnFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '低于单笔最低限额',
            180130
        );

        $result = '低于单笔最低限额';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '17040',
            'paymentVendorId' => '1092',
            'amount' => '2',
            'orderId' => '201703210000001931',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $anKuaiPay = new AnKuaiPay();
        $anKuaiPay->setPrivateKey('test');
        $anKuaiPay->setContainer($this->container);
        $anKuaiPay->setClient($this->client);
        $anKuaiPay->setResponse($response);
        $anKuaiPay->setOptions($sourceData);
        $anKuaiPay->getVerifyData();
    }

    /**
     * 測試支付寶二維支付時返回缺少參數
     */
    public function testAliPayReturnWithoutData()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = 'http://pay.ankuaipay/MakeQRCode.aspx?data=';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/html');

        $sourceData = [
            'number' => '17040',
            'paymentVendorId' => '1092',
            'amount' => '2',
            'orderId' => '201703210000001931',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $anKuaiPay = new AnKuaiPay();
        $anKuaiPay->setPrivateKey('test');
        $anKuaiPay->setContainer($this->container);
        $anKuaiPay->setClient($this->client);
        $anKuaiPay->setResponse($response);
        $anKuaiPay->setOptions($sourceData);
        $anKuaiPay->getVerifyData();
    }

    /**
     * 測試支付寶二維支付
     */
    public function testAlipay()
    {
        $sourceData = [
            'number' => '17040',
            'paymentVendorId' => '1092',
            'amount' => '2',
            'orderId' => '201703210000001931',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = 'http://pay.ankuaipay.cn/MakeQRCode.aspx?data=https://qr.alipay.com/bax09020bi2l4q2h4bnd0020';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $anKuaiPay = new AnKuaiPay();
        $anKuaiPay->setPrivateKey('test');
        $anKuaiPay->setContainer($this->container);
        $anKuaiPay->setClient($this->client);
        $anKuaiPay->setResponse($response);
        $anKuaiPay->setOptions($sourceData);
        $requestData = $anKuaiPay->getVerifyData();

        $this->assertEmpty($requestData);
        $this->assertEquals('https://qr.alipay.com/bax09020bi2l4q2h4bnd0020', $anKuaiPay->getQrcode());
    }

    /**
     * 測試QQ二維支付
     */
    public function testQQ()
    {
        $sourceData = [
            'number' => '17040',
            'paymentVendorId' => '1103',
            'amount' => '2',
            'orderId' => '201703210000001931',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = 'https://pay.swiftpass.cn/pay/qrcode?uuid=https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027' .
            '&_bid=2183&t=6Va699a8dc4633bcc095e1c7c0178101';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/html;');

        $anKuaiPay = new AnKuaiPay();
        $anKuaiPay->setPrivateKey('test');
        $anKuaiPay->setContainer($this->container);
        $anKuaiPay->setClient($this->client);
        $anKuaiPay->setResponse($response);
        $anKuaiPay->setOptions($sourceData);
        $requestData = $anKuaiPay->getVerifyData();

        $this->assertEmpty($requestData);
        $this->assertEquals(
            '<img src="https://pay.swiftpass.cn/pay/qrcode?uuid=https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027' .
            '&_bid=2183&t=6Va699a8dc4633bcc095e1c7c0178101"/>',
            $anKuaiPay->getHtml()
        );
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

        $anKuaiPay = new AnKuaiPay();
        $anKuaiPay->verifyOrderPayment([]);
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

        $anKuaiPay = new AnKuaiPay();
        $anKuaiPay->setPrivateKey('test');
        $anKuaiPay->verifyOrderPayment([]);
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

        $sourceData = [
            'partner' => '17040',
            'ordernumber' => '201703090000001810',
            'orderstatus' => '1',
            'paymoney' => '2.0000',
            'sysnumber' => 'AK17040170321164718232',
            'attach' => '',
        ];

        $anKuaiPay = new AnKuaiPay();
        $anKuaiPay->setOptions($sourceData);
        $anKuaiPay->setPrivateKey('test');
        $anKuaiPay->verifyOrderPayment([]);
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

        $sourceData = [
            'partner' => '17040',
            'ordernumber' => '201703090000001810',
            'orderstatus' => '1',
            'paymoney' => '2.0000',
            'sysnumber' => 'AK17040170321164718232',
            'attach' => '',
            'sign' => 'cec460574962122c03973b04609b3cf5',
        ];

        $anKuaiPay = new AnKuaiPay();
        $anKuaiPay->setOptions($sourceData);
        $anKuaiPay->setPrivateKey('test');
        $anKuaiPay->verifyOrderPayment([]);
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

        $sourceData = [
            'partner' => '17040',
            'ordernumber' => '201703090000001810',
            'orderstatus' => '0',
            'paymoney' => '2.0000',
            'sysnumber' => 'AK17040170321164718232',
            'attach' => '',
            'sign' => '18a1e22ddb368c5a82ae759cebfb0806',
        ];

        $anKuaiPay = new AnKuaiPay();
        $anKuaiPay->setOptions($sourceData);
        $anKuaiPay->setPrivateKey('test');
        $anKuaiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單編號錯誤
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'partner' => '17040',
            'ordernumber' => '201703090000001810',
            'orderstatus' => '1',
            'paymoney' => '2.0000',
            'sysnumber' => 'AK17040170321164718232',
            'attach' => '',
            'sign' => '706b257dcf07eee219e1ccd9c9452caf',
        ];

        $entry = ['id' => '201703090000001811'];

        $anKuaiPay = new AnKuaiPay();
        $anKuaiPay->setOptions($sourceData);
        $anKuaiPay->setPrivateKey('test');
        $anKuaiPay->verifyOrderPayment($entry);
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

        $sourceData = [
            'partner' => '17040',
            'ordernumber' => '201703090000001810',
            'orderstatus' => '1',
            'paymoney' => '2.0000',
            'sysnumber' => 'AK17040170321164718232',
            'attach' => '',
            'sign' => '706b257dcf07eee219e1ccd9c9452caf',
        ];

        $entry = [
            'id' => '201703090000001810',
            'amount' => '0.01',
        ];

        $anKuaiPay = new AnKuaiPay();
        $anKuaiPay->setOptions($sourceData);
        $anKuaiPay->setPrivateKey('test');
        $anKuaiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'partner' => '17040',
            'ordernumber' => '201703090000001810',
            'orderstatus' => '1',
            'paymoney' => '2.0000',
            'sysnumber' => 'AK17040170321164718232',
            'attach' => '',
            'sign' => '706b257dcf07eee219e1ccd9c9452caf',
        ];

        $entry = [
            'id' => '201703090000001810',
            'amount' => '2',
        ];

        $anKuaiPay = new AnKuaiPay();
        $anKuaiPay->setOptions($sourceData);
        $anKuaiPay->setPrivateKey('test');
        $anKuaiPay->verifyOrderPayment($entry);

        $this->assertEquals('ok', $anKuaiPay->getMsg());
    }
}
