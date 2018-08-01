<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\UIPAS;

class UIPASTest extends DurianTestCase
{
    /**
     * 此部分用於需要取得MerchantExtra資料的時候
     *
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * @var \Aw\Nusoap\NusoapClient
     */
    private $client;

    public function setUp()
    {
        parent::setUp();

        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $mockLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Payment')
            ->disableOriginalConstructor()
            ->setMethods(['record'])
            ->getMock();

        $mockLogger->expects($this->any())
            ->method('record')
            ->willReturn(null);

        $container->expects($this->any())
            ->method('get')
            ->willReturn($mockLogger);

        $this->client = $this->getMockBuilder('Aw\Nusoap\NusoapClient')
            ->disableOriginalConstructor()
            ->setMethods(['call'])
            ->getMock();

        $this->container = $container;
    }

    /**
     * 測試加密時沒有帶入privateKey的情況
     */
    public function testEncodeWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $uipas = new UIPAS();
        $uipas->getVerifyData();
    }

    /**
     * 測試加密時未指定支付參數
     */
    public function testEncodeWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $uipas = new UIPAS();
        $uipas->setPrivateKey('5210cafb8194d321f8d6418d1c74f438');

        $sourceData = ['number' => ''];

        $uipas->setOptions($sourceData);
        $uipas->getVerifyData();
    }

    /**
     * 測試加密時沒有帶入merchantId的情況
     */
    public function testEncodeWithoutMerchantId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $uipas = new UIPAS();
        $uipas->setPrivateKey('5210cafb8194d321f8d6418d1c74f438');

        $sourceData = [
            'number' => 'MUPS496114',
            'orderId' => '201409020000000413',
            'amount' => '1000',
            'lang' => 'jp',
            'merchantId' => ''
        ];

        $uipas->setOptions($sourceData);
        $uipas->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testEncode()
    {
        $uipas = new UIPAS();
        $uipas->setPrivateKey('5210cafb8194d321f8d6418d1c74f438');

        $sourceData = [
            'privateKey' => '5210cafb8194d321f8d6418d1c74f438',
            'number' => 'MUPS496114',
            'orderId' => '201409020000000413',
            'amount' => '1000',
            'lang' => 'ja',
            'merchantId' => '50815',
            'postUrl' => 'https://api.uipas.com/cashier/deposit',
            'merchant_extra' => ['account_passward' => '12345678']
        ];

        $payUrl = sprintf(
            '%s/%s/%s/%s/%s/%s/%s',
            $sourceData['postUrl'],
            $sourceData['amount'],
            $sourceData['number'],
            $sourceData['privateKey'],
            $sourceData['orderId'],
            'jp',
            'cb26ed2d6d9e245baf6ef6f060ef23e6'
        );

        $uipas->setOptions($sourceData);
        $encodeData = $uipas->getVerifyData();

        $this->assertEquals($payUrl, $encodeData['act_url']);
    }

    /**
     * 測試加密時使用預設的語系en
     */
    public function testEncodeWithDefaultLang()
    {
        $sourceData = [
            'number' => 'MUPS496114',
            'orderId' => '201409020000000413',
            'amount' => '1000',
            'lang' => 'zh-cn',
            'merchantId' => '50815',
            'postUrl' => 'https://api.uipas.com/cashier/deposit',
            'merchant_extra' => ['account_passward' => '12345678']
        ];

        $payUrl = sprintf(
            '%s/%s/%s/%s/%s/%s/%s',
            $sourceData['postUrl'],
            $sourceData['amount'],
            $sourceData['number'],
            '5210cafb8194d321f8d6418d1c74f438',
            $sourceData['orderId'],
            'en',
            '377a86cdf53cdf2168cb751e3f20ba80'
        );

        $uipas = new UIPAS();
        $uipas->setPrivateKey('5210cafb8194d321f8d6418d1c74f438');
        $uipas->setOptions($sourceData);
        $encodeData = $uipas->getVerifyData();

        $this->assertEquals($payUrl, $encodeData['act_url']);
    }

    /**
     * 測試加密,找不到商家的附加設定值
     */
    public function testEncodeButCannotFindMerchantExtra()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $uipas = new UIPAS();
        $uipas->setPrivateKey('5210cafb8194d321f8d6418d1c74f438');

        $sourceData = [
            'number' => 'MUPS496114',
            'orderId' => '201409020000000413',
            'amount' => '1000',
            'lang' => 'jp',
            'merchantId' => '50815',
            'postUrl' => 'https://api.uipas.com/cashier/deposit',
            'merchant_extra' => []
        ];

        $uipas->setOptions($sourceData);
        $uipas->getVerifyData();
    }

    /**
     * 測試加密沒有帶入postUrl為空值
     */
    public function testEncodeWithoutPostUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $uipas = new UIPAS();
        $uipas->setPrivateKey('5210cafb8194d321f8d6418d1c74f438');

        $sourceData = [
            'privateKey' => '5210cafb8194d321f8d6418d1c74f438',
            'number' => 'MUPS496114',
            'orderId' => '201409020000000413',
            'amount' => '1000',
            'lang' => 'jp',
            'merchantId' => '50815',
            'postUrl' => '',
            'merchant_extra' => ['account_passward' => '12345678']
        ];

        $payUrl = sprintf(
            '%s/%s/%s/%s/%s/%s/%s',
            $sourceData['postUrl'],
            $sourceData['amount'],
            $sourceData['number'],
            $sourceData['privateKey'],
            $sourceData['orderId'],
            'jp',
            'b8126facfabb77667d5322eeffb94009'
        );

        $uipas->setOptions($sourceData);
        $encodeData = $uipas->getVerifyData();

        $this->assertEquals($payUrl, $encodeData['act_url']);
    }

    /**
     * 測試解密驗證支付平台未指定返回參數
     */
    public function testVerifyWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $uipas = new UIPAS();

        $sourceData = [
            'payment_id' => '81',
            'trid' => '1722',
            'refid' => '201409020000000413',
            'amount' => '1000.0000'
        ];

        $uipas->setOptions($sourceData);
        $uipas->verifyOrderPayment([]);
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

        $uipas = new UIPAS();

        $sourceData = [
            'payment_id' => '81',
            'result' => 'failed',
            'trid' => '1722',
            'refid' => '201409020000000413',
            'amount' => '1000.0000'
        ];

        $uipas->setOptions($sourceData);
        $uipas->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $uipas = new UIPAS();

        $sourceData = [
            'payment_id' => '81',
            'result' => 'success',
            'trid' => '1722',
            'refid' => '201409020000000413',
            'amount' => '1000.0000'
        ];

        $entry = ['id' => '2014052200123'];

        $uipas->setOptions($sourceData);
        $uipas->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $uipas = new UIPAS();

        $sourceData = [
            'payment_id' => '81',
            'result' => 'success',
            'trid' => '1722',
            'refid' => '201409020000000413',
            'amount' => '1000.0000'
        ];

        $entry = [
            'id' => '201409020000000413',
            'amount' => '100.0000'
        ];

        $uipas->setOptions($sourceData);
        $uipas->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $uipas = new UIPAS();

        $sourceData = [
            'payment_id' => '81',
            'result' => 'success',
            'trid' => '1722',
            'refid' => '201409020000000413',
            'amount' => '1000.0000'
        ];

        $entry = [
            'id' => '201409020000000413',
            'amount' => '1000.0000'
        ];

        $uipas->setOptions($sourceData);
        $uipas->verifyOrderPayment($entry);

        $this->assertEquals('success', $uipas->getMsg());
    }

    /**
     * 測試訂單查詢未指定查詢參數
     */
    public function testPaymentTrackingWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $uipas = new UIPAS();
        $uipas->setPrivateKey('5210cafb8194d321f8d6418d1c74f438');
        $uipas->paymentTracking();
    }

    /**
     * 測試訂單查詢加密沒代入merchantId
     */
    public function testPaymentTrackingWithoutMerchantId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $sourceData = [
            'number' => '100000000001486',
            'orderId' => '201409020000000413',
            'merchantId' => ''
        ];

        $uipas = new UIPAS();
        $uipas->setPrivateKey('5210cafb8194d321f8d6418d1c74f438');
        $uipas->setOptions($sourceData);
        $uipas->paymentTracking();
    }

    /**
     * 測試訂單查詢加密缺少商家額外的參數設定account_passward
     */
    public function testTrackingWithoutMerchantExtraAccountPassward()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $sourceData = [
            'number' => '100000000001486',
            'orderId' => '201409020000000413',
            'merchantId' => '50815',
            'merchant_extra' => []
        ];

        $uipas = new UIPAS();
        $uipas->setPrivateKey('5210cafb8194d321f8d6418d1c74f438');
        $uipas->setOptions($sourceData);
        $uipas->paymentTracking();
    }

    /**
     * 測試訂單查詢沒代入verifyUrl
     */
    public function testPaymentTrackingWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => '100000000001486',
            'orderId' => '201409020000000413',
            'merchantId' => '50815',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
            'merchant_extra' => ['account_passward' => '12345678']
        ];

        $uipas = new UIPAS();
        $uipas->setPrivateKey('5210cafb8194d321f8d6418d1c74f438');
        $uipas->setOptions($sourceData);
        $uipas->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數transaction status[0]
     */
    public function testPaymentTrackingResultWithoutTransactionCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $data[1] = 'approved';
        $data[2] = '1000.0000';

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($data);

        $sourceData = [
            'number' => 'MUPS496114',
            'orderId' => '201409020000000413',
            'merchantId' => '50815',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.uipas.com',
            'merchant_extra' => ['account_passward' => '12345678']
        ];

        $uipas = new UIPAS();
        $uipas->setClient($this->client);
        $uipas->setContainer($this->container);
        $uipas->setOptions($sourceData);
        $uipas->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數transaction status[1]
     */
    public function testPaymentTrackingResultWithoutTransactionDescription()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $data[0] = '0';
        $data[2] = '1000.0000';

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($data);

        $sourceData = [
            'number' => 'MUPS496114',
            'orderId' => '201409020000000413',
            'merchantId' => '50815',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.uipas.com',
            'merchant_extra' => ['account_passward' => '12345678']
        ];

        $uipas = new UIPAS();
        $uipas->setClient($this->client);
        $uipas->setContainer($this->container);
        $uipas->setOptions($sourceData);
        $uipas->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數transaction status[2]
     */
    public function testPaymentTrackingResultWithoutTransactionAmount()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $data[0] = '0';
        $data[1] = 'approved';

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($data);

        $sourceData = [
            'number' => 'MUPS496114',
            'orderId' => '201409020000000413',
            'merchantId' => '50815',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.uipas.com',
            'merchant_extra' => ['account_passward' => '12345678']
        ];

        $uipas = new UIPAS();
        $uipas->setClient($this->client);
        $uipas->setContainer($this->container);
        $uipas->setOptions($sourceData);
        $uipas->paymentTracking();
    }

    /**
     * 測試訂單查詢結果無效的名稱或密碼
     */
    public function testPaymentTrackingResultInvalidParams()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Invalid Username or Password',
            180147
        );

        $data[0] = '1';
        $data[1] = 'approved';
        $data[2] = '1000.0000';

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($data);

        $sourceData = [
            'number' => 'MUPS496114',
            'orderId' => '201409020000000413',
            'merchantId' => '50815',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.uipas.com',
            'merchant_extra' => ['account_passward' => '12345678']
        ];

        $uipas = new UIPAS();
        $uipas->setClient($this->client);
        $uipas->setContainer($this->container);
        $uipas->setOptions($sourceData);
        $uipas->paymentTracking();
    }

    /**
     * 測試訂單查詢結果Internal Server Error
     */
    public function testPaymentTrackingResultInternalServarError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'System error, please try again later or contact customer service',
            180076
        );

        $data[0] = '4';
        $data[1] = 'approved';
        $data[2] = '1000.0000';

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($data);

        $sourceData = [
            'number' => 'MUPS496114',
            'orderId' => '201409020000000413',
            'merchantId' => '50815',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.uipas.com',
            'merchant_extra' => ['account_passward' => '12345678']
        ];

        $uipas = new UIPAS();
        $uipas->setClient($this->client);
        $uipas->setContainer($this->container);
        $uipas->setOptions($sourceData);
        $uipas->paymentTracking();
    }

    /**
     * 測試訂單查詢結果代入支付平台商號錯誤
     */
    public function testTrackingReturnPaymentGatewayMerchantError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Illegal merchant number',
            180082
        );

        $data[0] = '5';
        $data[1] = 'approved';
        $data[2] = '1000.0000';

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($data);

        $sourceData = [
            'number' => 'MUPS496114',
            'orderId' => '201409020000000413',
            'merchantId' => '50815',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.uipas.com',
            'merchant_extra' => ['account_passward' => '12345678']
        ];

        $uipas = new UIPAS();
        $uipas->setClient($this->client);
        $uipas->setContainer($this->container);
        $uipas->setOptions($sourceData);
        $uipas->paymentTracking();
    }

    /**
     * 測試訂單查詢結果支付失敗
     */
    public function testTrackingReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $data[0] = '0';
        $data[1] = 'unapproved';
        $data[2] = '1000.0000';

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($data);

        $sourceData = [
            'number' => 'MUPS496114',
            'orderId' => '201409020000000413',
            'merchantId' => '50815',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.uipas.com',
            'merchant_extra' => ['account_passward' => '12345678']
        ];

        $uipas = new UIPAS();
        $uipas->setClient($this->client);
        $uipas->setContainer($this->container);
        $uipas->setOptions($sourceData);
        $uipas->paymentTracking();
    }

    /**
     * 測試訂單查詢帶入金額不正確
     */
    public function testPaymentTrackingWithAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $data[0] = '0';
        $data[1] = 'approved';
        $data[2] = '1000.0000';

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($data);

        $sourceData = [
            'number' => 'MUPS496114',
            'orderId' => '201409020000000413',
            'merchantId' => '50815',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.uipas.com',
            'merchant_extra' => ['account_passward' => '12345678'],
            'amount' => '100.00'
        ];

        $uipas = new UIPAS();
        $uipas->setClient($this->client);
        $uipas->setContainer($this->container);
        $uipas->setOptions($sourceData);
        $uipas->paymentTracking();
    }

    /**
     * 測試訂單查詢
     */
    public function testPaymentTracking()
    {
        $data[0] = '0';
        $data[1] = 'approved';
        $data[2] = '1000.0000';

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($data);

        $sourceData = [
            'number' => 'MUPS496114',
            'orderId' => '201409020000000413',
            'merchantId' => '50815',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.uipas.com',
            'merchant_extra' => ['account_passward' => '12345678'],
            'amount' => '1000.00'
        ];

        $uipas = new UIPAS();
        $uipas->setClient($this->client);
        $uipas->setContainer($this->container);
        $uipas->setOptions($sourceData);
        $uipas->paymentTracking();
    }

    /**
     * 測試取得訂單查詢需要的參數時未指定查詢參數
     */
    public function testGetPaymentTrackingDataWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $uipas = new UIPAS();
        $uipas->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒代入number
     */
    public function testGetPaymentTrackingDataWithoutNumber()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $options = ['orderId' => '201409020000000413'];

        $uipas = new UIPAS();
        $uipas->setOptions($options);
        $uipas->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒代入merchantId
     */
    public function testGetPaymentTrackingDataWithoutMerchantId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $options = [
            'number' => '100000000001486',
            'orderId' => '201409020000000413',
            'merchantId' => ''
        ];

        $uipas = new UIPAS();
        $uipas->setOptions($options);
        $uipas->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時缺少商家額外的參數設定account_passward
     */
    public function testGetPaymentTrackingDataWithoutMerchantExtraAccountPassward()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $options = [
            'number' => '100000000001486',
            'orderId' => '201409020000000413',
            'merchantId' => '50815',
            'merchant_extra' => []
        ];

        $uipas = new UIPAS();
        $uipas->setOptions($options);
        $uipas->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒代入verify_url
     */
    public function testGetPaymentTrackingDataWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '100000000001486',
            'orderId' => '201409020000000413',
            'merchantId' => '50815',
            'merchant_extra' => ['account_passward' => '12345678'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $uipas = new UIPAS();
        $uipas->setOptions($options);
        $uipas->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'number' => '100000000001486',
            'orderId' => '201409020000000413',
            'merchantId' => '50815',
            'merchant_extra' => ['account_passward' => '12345678'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.www.uipas.com',
        ];

        $uipas = new UIPAS();
        $uipas->setOptions($options);
        $trackingData = $uipas->getPaymentTrackingData();

        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('/apiv2/index/wsdl', $trackingData['path']);
        $this->assertEquals('CheckTransfer', $trackingData['function']);
        $this->assertEquals('payment.http.www.uipas.com', $trackingData['headers']['Host']);
        $this->assertEquals('100000000001486', $trackingData['arguments']['merchantid']);
        $this->assertEquals('12345678', $trackingData['arguments']['account_passward']);
        $this->assertEquals('201409020000000413', $trackingData['arguments']['refid']);
    }

    /**
     * 測試驗證訂單查詢但缺少回傳參數transaction status[0]
     */
    public function testPaymentTrackingVerifyWithoutTransactionCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = '<SOAP-ENV:Envelope SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" ' .
            'xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/2001' .
            '/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:SOAP-ENC="http://sch' .
            'emas.xmlsoap.org/soap/encoding/"><SOAP-ENV:Body><ns1:CheckTransferResponse xmlns:ns1="urn:ApiWSDL">' .
            '<return xsi:type="SOAP-ENC:Array" SOAP-ENC:arrayType=":[2]">' .
            '</return></ns1:CheckTransferResponse></SOAP-ENV:Body></SOAP-ENV:Envelope>';
        $sourceData = ['content' => $content];

        $uipas = new UIPAS();
        $uipas->setOptions($sourceData);
        $uipas->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但缺少回傳參數transaction status[1]
     */
    public function testPaymentTrackingVerifyWithoutTransactionDescription()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = '<SOAP-ENV:Envelope SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" ' .
            'xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/2001' .
            '/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:SOAP-ENC="http://sch' .
            'emas.xmlsoap.org/soap/encoding/"><SOAP-ENV:Body><ns1:CheckTransferResponse xmlns:ns1="urn:ApiWSDL">' .
            '<return xsi:type="SOAP-ENC:Array" SOAP-ENC:arrayType=":[2]">' .
            '<item xsi:type="xsd:string">0</item>' .
            '</return></ns1:CheckTransferResponse></SOAP-ENV:Body></SOAP-ENV:Envelope>';
        $sourceData = ['content' => $content];

        $uipas = new UIPAS();
        $uipas->setOptions($sourceData);
        $uipas->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但缺少回傳參數transaction status[2]
     */
    public function testPaymentTrackingVerifyWithoutTransactionAmount()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = '<SOAP-ENV:Envelope SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" ' .
            'xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/2001' .
            '/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:SOAP-ENC="http://sch' .
            'emas.xmlsoap.org/soap/encoding/"><SOAP-ENV:Body><ns1:CheckTransferResponse xmlns:ns1="urn:ApiWSDL">' .
            '<return xsi:type="SOAP-ENC:Array" SOAP-ENC:arrayType=":[2]">' .
            '<item xsi:type="xsd:string">0</item>' .
            '<item xsi:type="xsd:string">approved</item>' .
            '</return></ns1:CheckTransferResponse></SOAP-ENV:Body></SOAP-ENV:Envelope>';
        $sourceData = ['content' => $content];

        $uipas = new UIPAS();
        $uipas->setOptions($sourceData);
        $uipas->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但返回無效的名稱或密碼
     */
    public function testPaymentTrackingVerifyButInvalidParams()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Invalid Username or Password',
            180147
        );

        $content = '<SOAP-ENV:Envelope SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" ' .
            'xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/2001' .
            '/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:SOAP-ENC="http://sch' .
            'emas.xmlsoap.org/soap/encoding/"><SOAP-ENV:Body><ns1:CheckTransferResponse xmlns:ns1="urn:ApiWSDL">' .
            '<return xsi:type="SOAP-ENC:Array" SOAP-ENC:arrayType=":[2]">' .
            '<item xsi:type="xsd:string">1</item>' .
            '<item xsi:type="xsd:string">approved</item>' .
            '<item xsi:type="xsd:string">1000.0000</item>' .
            '</return></ns1:CheckTransferResponse></SOAP-ENV:Body></SOAP-ENV:Envelope>';
        $sourceData = ['content' => $content];

        $uipas = new UIPAS();
        $uipas->setOptions($sourceData);
        $uipas->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但返回Internal Server Error
     */
    public function testPaymentTrackingVerifyButInternalServerError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'System error, please try again later or contact customer service',
            180076
        );

        $content = '<SOAP-ENV:Envelope SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" ' .
            'xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/2001' .
            '/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:SOAP-ENC="http://sch' .
            'emas.xmlsoap.org/soap/encoding/"><SOAP-ENV:Body><ns1:CheckTransferResponse xmlns:ns1="urn:ApiWSDL">' .
            '<return xsi:type="SOAP-ENC:Array" SOAP-ENC:arrayType=":[2]">' .
            '<item xsi:type="xsd:string">4</item>' .
            '<item xsi:type="xsd:string">approved</item>' .
            '<item xsi:type="xsd:string">1000.0000</item>' .
            '</return></ns1:CheckTransferResponse></SOAP-ENV:Body></SOAP-ENV:Envelope>';
        $sourceData = ['content' => $content];

        $uipas = new UIPAS();
        $uipas->setOptions($sourceData);
        $uipas->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但返回支付平台商號錯誤
     */
    public function testPaymentTrackingVerifyButPaymentGatewayMerchantError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Illegal merchant number',
            180082
        );

        $content = '<SOAP-ENV:Envelope SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" ' .
            'xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/2001' .
            '/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:SOAP-ENC="http://sch' .
            'emas.xmlsoap.org/soap/encoding/"><SOAP-ENV:Body><ns1:CheckTransferResponse xmlns:ns1="urn:ApiWSDL">' .
            '<return xsi:type="SOAP-ENC:Array" SOAP-ENC:arrayType=":[2]">' .
            '<item xsi:type="xsd:string">5</item>' .
            '<item xsi:type="xsd:string">approved</item>' .
            '<item xsi:type="xsd:string">1000.0000</item>' .
            '</return></ns1:CheckTransferResponse></SOAP-ENV:Body></SOAP-ENV:Envelope>';
        $sourceData = ['content' => $content];

        $uipas = new UIPAS();
        $uipas->setOptions($sourceData);
        $uipas->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但支付失敗
     */
    public function testPaymentTrackingVerifyButPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $content = '<SOAP-ENV:Envelope SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" ' .
            'xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/2001' .
            '/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:SOAP-ENC="http://sch' .
            'emas.xmlsoap.org/soap/encoding/"><SOAP-ENV:Body><ns1:CheckTransferResponse xmlns:ns1="urn:ApiWSDL">' .
            '<return xsi:type="SOAP-ENC:Array" SOAP-ENC:arrayType=":[2]">' .
            '<item xsi:type="xsd:string">0</item>' .
            '<item xsi:type="xsd:string">unapproved</item>' .
            '<item xsi:type="xsd:string">1000.0000</item>' .
            '</return></ns1:CheckTransferResponse></SOAP-ENV:Body></SOAP-ENV:Envelope>';
        $sourceData = ['content' => $content];

        $uipas = new UIPAS();
        $uipas->setOptions($sourceData);
        $uipas->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但金額不正確
     */
    public function testPaymentTrackingVerifyButAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $content = '<SOAP-ENV:Envelope SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" ' .
            'xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/2001' .
            '/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:SOAP-ENC="http://sch' .
            'emas.xmlsoap.org/soap/encoding/"><SOAP-ENV:Body><ns1:CheckTransferResponse xmlns:ns1="urn:ApiWSDL">' .
            '<return xsi:type="SOAP-ENC:Array" SOAP-ENC:arrayType=":[2]">' .
            '<item xsi:type="xsd:string">0</item>' .
            '<item xsi:type="xsd:string">approved</item>' .
            '<item xsi:type="xsd:string">1000.0000</item>' .
            '</return></ns1:CheckTransferResponse></SOAP-ENV:Body></SOAP-ENV:Envelope>';
        $sourceData = [
            'content' => $content,
            'amount' => '100.00'
        ];

        $uipas = new UIPAS();
        $uipas->setOptions($sourceData);
        $uipas->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $content = '<SOAP-ENV:Envelope SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" ' .
            'xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/2001' .
            '/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:SOAP-ENC="http://sch' .
            'emas.xmlsoap.org/soap/encoding/"><SOAP-ENV:Body><ns1:CheckTransferResponse xmlns:ns1="urn:ApiWSDL">' .
            '<return xsi:type="SOAP-ENC:Array" SOAP-ENC:arrayType=":[2]">' .
            '<item xsi:type="xsd:string">0</item>' .
            '<item xsi:type="xsd:string">approved</item>' .
            '<item xsi:type="xsd:string">1000.0000</item>' .
            '</return></ns1:CheckTransferResponse></SOAP-ENV:Body></SOAP-ENV:Envelope>';
        $sourceData = [
            'content' => $content,
            'amount' => '1000.00'
        ];

        $uipas = new UIPAS();
        $uipas->setOptions($sourceData);
        $uipas->paymentTrackingVerify();
    }

    /**
     * 測試轉換訂單查詢支付平台返回的編碼
     */
    public function testProcessTrackingResponseEncoding()
    {
        // 將支付平台的返回做編碼模擬 kue 返回
        $body = '<SOAP-ENV:Envelope SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" ' .
            'xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/2001' .
            '/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:SOAP-ENC="http://sch' .
            'emas.xmlsoap.org/soap/encoding/"><SOAP-ENV:Body><ns1:CheckTransferResponse xmlns:ns1="urn:ApiWSDL">' .
            '<return xsi:type="SOAP-ENC:Array" SOAP-ENC:arrayType=":[2]">' .
            '<item xsi:type="xsd:string">0</item>' .
            '<item xsi:type="xsd:string">approved</item>' .
            '<item xsi:type="xsd:string">1000.0000</item>' .
            '</return></ns1:CheckTransferResponse></SOAP-ENV:Body></SOAP-ENV:Envelope>';
        $encodedBody = base64_encode($body);

        $encodedResponse = [
            'header' => null,
            'body' => $encodedBody
        ];

        $uipas = new UIPAS();
        $trackingResponse = $uipas->processTrackingResponseEncoding($encodedResponse);

        $this->assertEquals($encodedResponse['header'], $trackingResponse['header']);
        $this->assertEquals($body, $trackingResponse['body']);
    }
}
