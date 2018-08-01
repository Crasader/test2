<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ChengHsinPay;
use Buzz\Message\Response;

class ChengHsinPayTest extends DurianTestCase
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
            ->will($this->returnValue(null));

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

        $chengHsinPay = new ChengHsinPay();
        $chengHsinPay->getVerifyData();
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

        $chengHsinPay = new ChengHsinPay();
        $chengHsinPay->setPrivateKey('test');
        $chengHsinPay->getVerifyData();
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
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '9999',
            'number' => '00000000518773',
            'orderId' => '201709210000004808',
            'amount' => '1.01',
            'username' => 'php1test',
            'merchant_extra' => ['receivableType' => 'D00'],
            'orderCreateDate' => '2017-08-24 11:32:32',
        ];

        $chengHsinPay = new ChengHsinPay();
        $chengHsinPay->setPrivateKey('test');
        $chengHsinPay->setOptions($options);
        $chengHsinPay->getVerifyData();
    }

    /**
     * 測試支付缺少商家額外的參數設定receivableType
     */
    public function testPayWithoutMerchantExtraReceivableType()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1',
            'number' => '00000000518773',
            'orderId' => '201709210000004808',
            'amount' => '1.01',
            'username' => 'php1test',
            'merchant_extra' => [],
            'orderCreateDate' => '2017-08-24 11:32:32',
        ];

        $chengHsinPay = new ChengHsinPay();
        $chengHsinPay->setPrivateKey('test');
        $chengHsinPay->setOptions($options);
        $chengHsinPay->getVerifyData();
    }

    /**
     * 測試二維支付時沒有帶入verify_url的情況
     */
    public function testQrCodePayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '00000000518773',
            'orderId' => '201709210000004808',
            'amount' => '1.01',
            'username' => 'php1test',
            'merchant_extra' => ['receivableType' => 'D00'],
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => '',
        ];

        $chengHsinPay = new ChengHsinPay();
        $chengHsinPay->setPrivateKey('test');
        $chengHsinPay->setOptions($options);
        $chengHsinPay->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回retCode
     */
    public function testQrCodePayReturnWithoutRetCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '00000000518773',
            'orderId' => '201709210000004808',
            'amount' => '1.01',
            'username' => 'php1test',
            'merchant_extra' => ['receivableType' => 'D00'],
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = ['retMsg' => '00000000518773商户未配置该支付方式……'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $chengHsinPay = new ChengHsinPay();
        $chengHsinPay->setContainer($this->container);
        $chengHsinPay->setClient($this->client);
        $chengHsinPay->setResponse($response);
        $chengHsinPay->setPrivateKey('test');
        $chengHsinPay->setOptions($options);
        $chengHsinPay->getVerifyData();
    }

    /**
     * 測試二維支付時返回提交失敗
     */
    public function testQRcodePayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '00000000518773商户未配置该支付方式……',
            180130
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '00000000518773',
            'orderId' => '201709210000004808',
            'amount' => '1.01',
            'username' => 'php1test',
            'merchant_extra' => ['receivableType' => 'D00'],
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'retCode' => '1518025',
            'retMsg' => '00000000518773商户未配置该支付方式……',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $chengHsinPay = new ChengHsinPay();
        $chengHsinPay->setContainer($this->container);
        $chengHsinPay->setClient($this->client);
        $chengHsinPay->setResponse($response);
        $chengHsinPay->setPrivateKey('test');
        $chengHsinPay->setOptions($options);
        $chengHsinPay->getVerifyData();
    }

    /**
     * 測試二維支付時沒有返回qrcode
     */
    public function testQrCodePayReturnWithoutQrcode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1103',
            'number' => '00000000518773',
            'orderId' => '201709210000004808',
            'amount' => '1.01',
            'username' => 'php1test',
            'merchant_extra' => ['receivableType' => 'D00'],
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'platmerord' => '911038426894102528',
            'retCode' => '1',
            'signType' => 'MD5',
            'retMsg' => '下单成功',
            'signData' => '37672C3885B587787BFBC132150A8C3D',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $chengHsinPay = new ChengHsinPay();
        $chengHsinPay->setContainer($this->container);
        $chengHsinPay->setClient($this->client);
        $chengHsinPay->setResponse($response);
        $chengHsinPay->setPrivateKey('test');
        $chengHsinPay->setOptions($options);
        $chengHsinPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrCodePay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => '00000000518773',
            'orderId' => '201709210000004808',
            'amount' => '1.01',
            'username' => 'php1test',
            'merchant_extra' => ['receivableType' => 'D00'],
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'platmerord' => '911036398058926080',
            'retCode' => '1',
            'signType' => 'MD5',
            'retMsg' => '下单成功',
            'qrcode' => 'weixin://wxpay/bizpayurl?pr=W571jlx',
            'signData' => '4682F5C41D8353FC59DD5153DEF9D9EE',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $chengHsinPay = new ChengHsinPay();
        $chengHsinPay->setContainer($this->container);
        $chengHsinPay->setClient($this->client);
        $chengHsinPay->setResponse($response);
        $chengHsinPay->setPrivateKey('test');
        $chengHsinPay->setOptions($options);
        $data = $chengHsinPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=W571jlx', $chengHsinPay->getQrcode());
    }

    /**
     * 測試網銀
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1',
            'number' => '00000000518773',
            'orderId' => '201709210000004808',
            'amount' => '1.01',
            'username' => 'php1test',
            'merchant_extra' => ['receivableType' => 'D00'],
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $chengHsinPay = new ChengHsinPay();
        $chengHsinPay->setPrivateKey('test');
        $chengHsinPay->setOptions($options);
        $data = $chengHsinPay->getVerifyData();

        $this->assertEquals('1.0', $data['versionId']);
        $this->assertEquals($options['amount'] * 100, $data['orderAmount']);
        $this->assertEquals('20170824113232', $data['orderDate']);
        $this->assertEquals('RMB', $data['currency']);
        $this->assertEquals('0', $data['accountType']);
        $this->assertEquals('0008', $data['transType']);
        $this->assertEquals($options['notify_url'], $data['asynNotifyUrl']);
        $this->assertEquals($options['notify_url'], $data['synNotifyUrl']);
        $this->assertEquals('MD5', $data['signType']);
        $this->assertEquals($options['number'], $data['merId']);
        $this->assertEquals($options['orderId'], $data['prdOrdNo']);
        $this->assertEquals('00020', $data['payMode']);
        $this->assertEquals('102', $data['tranChannel']);
        $this->assertEquals($options['merchant_extra']['receivableType'], $data['receivableType']);
        $this->assertEquals('', $data['prdAmt']);
        $this->assertEquals('', $data['prdDisUrl']);
        $this->assertEquals($options['username'], $data['prdName']);
        $this->assertEquals('', $data['prdShortName']);
        $this->assertEquals($options['username'], $data['prdDesc']);
        $this->assertEquals('1', $data['pnum']);
        $this->assertEquals('', $data['merParam']);
        $this->assertEquals('ff2fc88f96ce004100eb24378b89dcc3', $data['signData']);
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

        $chengHsinPay = new ChengHsinPay();
        $chengHsinPay->verifyOrderPayment([]);
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

        $chengHsinPay = new ChengHsinPay();
        $chengHsinPay->setPrivateKey('test');
        $chengHsinPay->verifyOrderPayment([]);
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

        $options = [
            'payId' => '910800945087049728',
            'prdOrdNo' => '201709210000004808',
            'transType' => '008',
            'orderAmount' => '101',
            'signType' => 'MD5',
            'merId' => '00000000518773',
            'versionId' => '1.0',
            'payTime' => '20170921174107',
            'orderStatus' => '01',
            'synNotifyUrl' => 'http://pay.in-action.tw/pay/return.php',
            'asynNotifyUrl' => 'http://pay.in-action.tw/pay/return.php',
        ];

        $chengHsinPay = new ChengHsinPay();
        $chengHsinPay->setPrivateKey('test');
        $chengHsinPay->setOptions($options);
        $chengHsinPay->verifyOrderPayment([]);
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
            'payId' => '910800945087049728',
            'prdOrdNo' => '201709210000004808',
            'transType' => '008',
            'orderAmount' => '101',
            'signType' => 'MD5',
            'merId' => '00000000518773',
            'versionId' => '1.0',
            'payTime' => '20170921174107',
            'orderStatus' => '01',
            'synNotifyUrl' => 'http://pay.in-action.tw/pay/return.php',
            'signData' => '999782BD9FEE7AE3854C2750D4B40880',
            'asynNotifyUrl' => 'http://pay.in-action.tw/pay/return.php',
        ];

        $chengHsinPay = new ChengHsinPay();
        $chengHsinPay->setPrivateKey('test');
        $chengHsinPay->setOptions($options);
        $chengHsinPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單處理中
     */
    public function testReturnOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $options = [
            'payId' => '910800945087049728',
            'prdOrdNo' => '201709210000004808',
            'transType' => '008',
            'orderAmount' => '101',
            'signType' => 'MD5',
            'merId' => '00000000518773',
            'versionId' => '1.0',
            'payTime' => '20170921174107',
            'orderStatus' => '02',
            'synNotifyUrl' => 'http://pay.in-action.tw/pay/return.php',
            'signData' => '663EDC4B655AEDB811993BBA6FE72A1D',
            'asynNotifyUrl' => 'http://pay.in-action.tw/pay/return.php',
        ];

        $chengHsinPay = new ChengHsinPay();
        $chengHsinPay->setPrivateKey('test');
        $chengHsinPay->setOptions($options);
        $chengHsinPay->verifyOrderPayment([]);
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

        $options = [
            'payId' => '910800945087049728',
            'prdOrdNo' => '201709210000004808',
            'transType' => '008',
            'orderAmount' => '101',
            'signType' => 'MD5',
            'merId' => '00000000518773',
            'versionId' => '1.0',
            'payTime' => '20170921174107',
            'orderStatus' => '00',
            'synNotifyUrl' => 'http://pay.in-action.tw/pay/return.php',
            'signData' => '421FEEA17220401B4BD77EC50C1F70F0',
            'asynNotifyUrl' => 'http://pay.in-action.tw/pay/return.php',
        ];

        $chengHsinPay = new ChengHsinPay();
        $chengHsinPay->setPrivateKey('test');
        $chengHsinPay->setOptions($options);
        $chengHsinPay->verifyOrderPayment([]);
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
            'payId' => '910800945087049728',
            'prdOrdNo' => '201709210000004808',
            'transType' => '008',
            'orderAmount' => '101',
            'signType' => 'MD5',
            'merId' => '00000000518773',
            'versionId' => '1.0',
            'payTime' => '20170921174107',
            'orderStatus' => '01',
            'synNotifyUrl' => 'http://pay.in-action.tw/pay/return.php',
            'signData' => 'F5A8D3BAA4EF824EFB5EC7EE16346012',
            'asynNotifyUrl' => 'http://pay.in-action.tw/pay/return.php',
        ];

        $entry = ['id' => '201503220000000555'];

        $chengHsinPay = new ChengHsinPay();
        $chengHsinPay->setPrivateKey('test');
        $chengHsinPay->setOptions($options);
        $chengHsinPay->verifyOrderPayment($entry);
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

        $options = [
            'payId' => '910800945087049728',
            'prdOrdNo' => '201709210000004808',
            'transType' => '008',
            'orderAmount' => '101',
            'signType' => 'MD5',
            'merId' => '00000000518773',
            'versionId' => '1.0',
            'payTime' => '20170921174107',
            'orderStatus' => '01',
            'synNotifyUrl' => 'http://pay.in-action.tw/pay/return.php',
            'signData' => 'F5A8D3BAA4EF824EFB5EC7EE16346012',
            'asynNotifyUrl' => 'http://pay.in-action.tw/pay/return.php',
        ];

        $entry = [
            'id' => '201709210000004808',
            'amount' => '15.00',
        ];

        $chengHsinPay = new ChengHsinPay();
        $chengHsinPay->setPrivateKey('test');
        $chengHsinPay->setOptions($options);
        $chengHsinPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $options = [
            'payId' => '910800945087049728',
            'prdOrdNo' => '201709210000004808',
            'transType' => '008',
            'orderAmount' => '101',
            'signType' => 'MD5',
            'merId' => '00000000518773',
            'versionId' => '1.0',
            'payTime' => '20170921174107',
            'orderStatus' => '01',
            'synNotifyUrl' => 'http://pay.in-action.tw/pay/return.php',
            'signData' => 'F5A8D3BAA4EF824EFB5EC7EE16346012',
            'asynNotifyUrl' => 'http://pay.in-action.tw/pay/return.php',
        ];

        $entry = [
            'id' => '201709210000004808',
            'amount' => '1.01',
        ];

        $chengHsinPay = new ChengHsinPay();
        $chengHsinPay->setPrivateKey('test');
        $chengHsinPay->setOptions($options);
        $chengHsinPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $chengHsinPay->getMsg());
    }
}
