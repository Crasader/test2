<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Baokimpay;
use Buzz\Message\Response;

class BaokimpayTest extends DurianTestCase
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * @var \Aw\Nusoap\NusoapClient
     */
    private $nusoapClient;

    /**
     * @var \Buzz\Client\Curl
     */
    private $curlClient;

    public function setUp()
    {
        parent::setUp();

        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get', 'getParameter'])
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

        $this->nusoapClient = $this->getMockBuilder('Aw\Nusoap\NusoapClient')
            ->disableOriginalConstructor()
            ->setMethods(['call'])
            ->getMock();

        $this->curlClient = $this->getMockBuilder('Buzz\Client\Curl')
            ->disableOriginalConstructor()
            ->setMethods(['send'])
            ->getMock();

        $this->container = $container;
    }

    /**
     * 測試取得驗證資料,基本參數設定沒有帶入privateKey的情況
     */
    public function testGetVerifyDataNoKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $baokimpay = new Baokimpay();
        $baokimpay->getVerifyData();
    }

    /**
     * 測試取得驗證資料,基本參數設定未指定支付參數
     */
    public function testGetVerifyDataNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $baokimpay = new Baokimpay();
        $baokimpay->setPrivateKey('de0887e31acb2dad');

        $sourceData = ['number' => ''];

        $baokimpay->setOptions($sourceData);
        $baokimpay->getVerifyData();
    }

    /**
     * 測試取得驗證資料,基本參數設定沒有帶入merchantId的情況
     */
    public function testGetVerifyDataNoMerchantId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $baokimpay = new Baokimpay();
        $baokimpay->setPrivateKey('de0887e31acb2dad');

        $sourceData = [
            'number' => '46981',
            'orderId' => '20140610000123',
            'amount' => '10',
            'username' => 'baokimtest',
            'paymentVendorId' => '246',
            'notify_url' => 'http://121.235.11.30/baokim/return.php?pay_system=46981&hallid=222',
            'domain' => '222',
            'merchantId' => '',
        ];

        $baokimpay->setOptions($sourceData);
        $baokimpay->getVerifyData();
    }

    /**
     * 測試取得驗證資料,基本參數設定沒有帶入postUrl的情況
     */
    public function testGetVerifyDataNoPostUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $baokimpay = new Baokimpay();
        $baokimpay->setPrivateKey('de0887e31acb2dad');

        $sourceData = [
            'number' => '46981',
            'orderId' => '20140610000123',
            'amount' => '10',
            'username' => 'baokimtest',
            'paymentVendorId' => '24600',
            'domain' => '222',
            'notify_url' => 'http://121.235.11.30/baokim/return.php?pay_system=46981&hallid=222',
            'merchantId' => '8056',
            'postUrl' => '',
        ];

        $baokimpay->setOptions($sourceData);
        $baokimpay->getVerifyData();
    }

    /**
     * 測試取得驗證資料,基本參數設定帶入不支援的銀行
     */
    public function testGetVerifyDataNotSupportBank()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $baokimpay = new Baokimpay();
        $baokimpay->setPrivateKey('de0887e31acb2dad');

        $sourceData = [
            'number' => '46981',
            'orderId' => '20140610000123',
            'amount' => '10',
            'username' => 'baokimtest',
            'paymentVendorId' => '24600',
            'domain' => '222',
            'notify_url' => 'http://121.235.11.30/baokim/return.php?pay_system=46981&hallid=222',
            'merchantId' => '8056',
            'postUrl' => 'http://',
        ];

        $baokimpay->setOptions($sourceData);
        $baokimpay->getVerifyData();
    }

    /**
     * 測試支付時支付平台連線失敗
     */
    public function testPayPaymentGatewayConnectionFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment Gateway connection failure',
            180088
        );

        $exception = new \Exception('Payment Gateway connection failure', 180088);
        $this->nusoapClient->expects($this->any())
            ->method('call')
            ->willThrowException($exception);

        $baokimpay = new Baokimpay();
        $baokimpay->setContainer($this->container);
        $baokimpay->setClient($this->nusoapClient);
        $baokimpay->setPrivateKey('de0887e31acb2dad');

        $merchantExtra = [
            'api_username' => '101001',
            'api_password' => '101001',
            'bk_seller_email' => '101001'
        ];

        $sourceData = [
            'number' => '46981',
            'orderId' => '20140610000123',
            'amount' => '10',
            'username' => 'baokimtest',
            'paymentVendorId' => '246',
            'domain' => '222',
            'notify_url' => 'http://121.235.11.30/baokim/return.php?pay_system=46981&hallid=222',
            'merchantId' => '8056',
            'postUrl' => 'http://',
            'merchant_extra' => $merchantExtra,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $baokimpay->setOptions($sourceData);
        $baokimpay->getVerifyData();
    }

    /**
     * 測試加密時支付平台回傳結果為空
     */
    public function testPayEmptyPaymentGatewayResponse()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Empty Payment Gateway response',
            180089
        );

        $this->nusoapClient->expects($this->any())
            ->method('call')
            ->willReturn('');

        $baokimpay = new Baokimpay();
        $baokimpay->setContainer($this->container);
        $baokimpay->setClient($this->nusoapClient);
        $baokimpay->setPrivateKey('de0887e31acb2dad');

        $merchantExtra = [
            'api_username' => '101001',
            'api_password' => '101001',
            'bk_seller_email' => '101001'
        ];

        $sourceData = [
            'number' => '46981',
            'orderId' => '20140610000123',
            'amount' => '10',
            'username' => 'baokimtest',
            'paymentVendorId' => '246',
            'domain' => '222',
            'notify_url' => 'http://121.235.11.30/baokim/return.php?pay_system=46981&hallid=222',
            'merchantId' => '8056',
            'postUrl' => 'http://',
            'merchant_extra' => $merchantExtra,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $baokimpay->setOptions($sourceData);
        $baokimpay->getVerifyData();
    }

    /**
     * 測試支付時對外返回結果錯誤
     */
    public function testPayConnectionPaymentGatewayError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'merchant_site not found for merchant_id=7970',
            180130
        );

        $returnUrl = [
            'error_code'    => '8',
            'error_message' => 'merchant_site not found for merchant_id=7970',
            'url_redirect'  => ''
        ];

        $this->nusoapClient->expects($this->any())
            ->method('call')
            ->willReturn($returnUrl);

        $baokimpay = new Baokimpay();
        $baokimpay->setContainer($this->container);
        $baokimpay->setClient($this->nusoapClient);
        $baokimpay->setPrivateKey('de0887e31acb2dad');

        $merchantExtra = [
            'api_username' => '101001',
            'api_password' => '101001',
            'bk_seller_email' => '101001'
        ];

        $sourceData = [
            'number' => '46981',
            'orderId' => '20140610000123',
            'amount' => '10',
            'username' => 'baokimtest',
            'paymentVendorId' => '246',
            'domain' => '222',
            'notify_url' => 'http://121.235.11.30/baokim/return.php?pay_system=46981&hallid=222',
            'merchantId' => '8056',
            'postUrl' => 'http://',
            'merchant_extra' => $merchantExtra,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $baokimpay->setOptions($sourceData);
        $baokimpay->getVerifyData();
    }

    /**
     * 測試取得驗證資料時取得Url時 回傳缺少參數 error_code
     */
    public function testGetVerifyDataResponseWithoutErrorCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $returnUrl = [
            'error_message' => '',
            'url_redirect'  => ''
        ];

        $this->nusoapClient->expects($this->any())
            ->method('call')
            ->willReturn($returnUrl);

        $baokimpay = new Baokimpay();
        $baokimpay->setContainer($this->container);
        $baokimpay->setClient($this->nusoapClient);
        $baokimpay->setPrivateKey('de0887e31acb2dad');

        $merchantExtra = [
            'api_username' => '101001',
            'api_password' => '101001',
            'bk_seller_email' => '101001'
        ];

        $sourceData = [
            'number' => '46981',
            'orderId' => '20140610000123',
            'amount' => '10',
            'username' => 'baokimtest',
            'paymentVendorId' => '246',
            'domain' => '222',
            'notify_url' => 'http://121.235.11.30/baokim/return.php?pay_system=46981&hallid=222',
            'merchantId' => '8056',
            'postUrl' => 'http://',
            'merchant_extra' => $merchantExtra,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $baokimpay->setOptions($sourceData);
        $baokimpay->getVerifyData();
    }

    /**
     * 測試取得驗證資料時取得Url時 回傳缺少參數 error_message
     */
    public function testGetVerifyDataResponseWithoutErrorMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $returnUrl = [
            'error_code'   => '8',
            'url_redirect' => ''
        ];

        $this->nusoapClient->expects($this->any())
            ->method('call')
            ->willReturn($returnUrl);

        $baokimpay = new Baokimpay();
        $baokimpay->setContainer($this->container);
        $baokimpay->setClient($this->nusoapClient);
        $baokimpay->setPrivateKey('de0887e31acb2dad');

        $merchantExtra = [
            'api_username' => '101001',
            'api_password' => '101001',
            'bk_seller_email' => '101001'
        ];

        $sourceData = [
            'number' => '46981',
            'orderId' => '20140610000123',
            'amount' => '10',
            'username' => 'baokimtest',
            'paymentVendorId' => '246',
            'domain' => '222',
            'notify_url' => 'http://121.235.11.30/baokim/return.php?pay_system=46981&hallid=222',
            'merchantId' => '8056',
            'postUrl' => 'http://',
            'merchant_extra' => $merchantExtra,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $baokimpay->setOptions($sourceData);
        $baokimpay->getVerifyData();
    }

    /**
     * 測試取得驗證資料時取得Url時 回傳缺少參數 url_redirect
     */
    public function testGetVerifyDataResponseWithoutUrlRedirect()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $returnUrl = [
            'error_code'    => '8',
            'error_message' => 'merchant_site not found for merchant_id=7970'
        ];

        $this->nusoapClient->expects($this->any())
            ->method('call')
            ->willReturn($returnUrl);

        $baokimpay = new Baokimpay();
        $baokimpay->setContainer($this->container);
        $baokimpay->setClient($this->nusoapClient);
        $baokimpay->setPrivateKey('de0887e31acb2dad');

        $merchantExtra = [
            'api_username' => '101001',
            'api_password' => '101001',
            'bk_seller_email' => '101001'
        ];

        $sourceData = [
            'number' => '46981',
            'orderId' => '20140610000123',
            'amount' => '10',
            'username' => 'baokimtest',
            'paymentVendorId' => '246',
            'domain' => '222',
            'notify_url' => 'http://121.235.11.30/baokim/return.php?pay_system=46981&hallid=222',
            'merchantId' => '8056',
            'postUrl' => 'http://',
            'merchant_extra' => $merchantExtra,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $baokimpay->setOptions($sourceData);
        $baokimpay->getVerifyData();
    }

    /**
     * 測試取得驗證資料成功
     */
    public function testGetVerifyDataSuccess()
    {
        $returnUrl = [
            'error_code'    => '0',
            'error_message' => '',
            'url_redirect'  => 'http://sandbox.baokim.vn//paymentpro/payment_pro_2?'.
                'merchant_id=577&order_id=201301231112111910_1P415_6&session_id'.
                '=07ba1282e8e5203c3769406e30b9081f&checksum=f087333431239315942681215e025a4d'
        ];

        $this->nusoapClient->expects($this->any())
            ->method('call')
            ->willReturn($returnUrl);

        $baokimpay = new Baokimpay();
        $baokimpay->setContainer($this->container);
        $baokimpay->setClient($this->nusoapClient);
        $baokimpay->setPrivateKey('de0887e31acb2dad');

        $merchantExtra = [
            'api_username' => '101001',
            'api_password' => '101001',
            'bk_seller_email' => '101001'
        ];

        $sourceData = [
            'number' => '46981',
            'orderId' => '20140610000123',
            'amount' => '10',
            'username' => 'baokimtest',
            'paymentVendorId' => '246',
            'domain' => '222',
            'notify_url' => 'http://121.235.11.30/baokim/return.php',
            'merchantId' => '8056',
            'postUrl' => 'http://',
            'merchant_extra' => $merchantExtra,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $baokimpay->setOptions($sourceData);
        $verifyData = $baokimpay->getVerifyData();

        $orderId = $sourceData['orderId'].
            "_".$sourceData['merchantId'].
            "_".$verifyData['shipping_address'];

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals($sourceData['number'], $verifyData['merchant_id']);
        $this->assertEquals($orderId, $verifyData['order_id']);
        $this->assertEquals('10', $verifyData['total_amount']);
        $this->assertEquals($sourceData['username'], $verifyData['payer_name']);
        $this->assertEquals('baokimtest', $verifyData['order_description']);
        $this->assertEquals('baokimtest', $verifyData['payer_message']);
        $this->assertEquals('86', $verifyData['bank_payment_method_id']);
        $this->assertEquals($notifyUrl, $verifyData['url_return']);
        $this->assertEquals('222', $verifyData['shipping_address']);
    }

    /**
     * 測試取得驗證資料,找不到商家的ApiUsername附加設定值
     */
    public function testGetVerifyDataButNoApiUsernameSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $baokimpay = new Baokimpay();
        $baokimpay->setPrivateKey('de0887e31acb2dad');

        $sourceData = [
            'number' => '46981',
            'orderId' => '20140610000123',
            'amount' => '10',
            'username' => 'baokimtest',
            'paymentVendorId' => '246',
            'domain' => '222',
            'notify_url' => 'http://121.235.11.30/baokim/return.php?pay_system=46981&hallid=222',
            'merchantId' => '8056',
            'postUrl' => 'http://',
            'merchant_extra' => [],
        ];

        $baokimpay->setOptions($sourceData);
        $baokimpay->getVerifyData();
    }

    /**
     * 測試取得驗證資料,找不到商家的ApiPassword附加設定值
     */
    public function testGetVerifyDataButNoApiPasswordSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $baokimpay = new Baokimpay();
        $baokimpay->setPrivateKey('de0887e31acb2dad');

        $sourceData = [
            'number' => '46981',
            'orderId' => '20140610000123',
            'amount' => '10',
            'username' => 'baokimtest',
            'paymentVendorId' => '246',
            'domain' => '222',
            'notify_url' => 'http://121.235.11.30/baokim/return.php?pay_system=46981&hallid=222',
            'merchantId' => '8056',
            'postUrl' => 'http://',
            'merchant_extra' => ['api_username' => '101001'],
        ];

        $baokimpay->setOptions($sourceData);
        $baokimpay->getVerifyData();
    }

    /**
     * 測試取得驗證資料,找不到商家的BkSellerEmail附加設定值
     */
    public function testGetVerifyDataButNoBkSellerEmailSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $baokimpay = new Baokimpay();
        $baokimpay->setPrivateKey('de0887e31acb2dad');

        $merchantExtra = [
            'api_username' => '101001',
            'api_password' => '101001'
        ];

        $sourceData = [
            'number' => '46981',
            'orderId' => '20140610000123',
            'amount' => '10',
            'username' => 'baokimtest',
            'paymentVendorId' => '246',
            'domain' => '222',
            'notify_url' => 'http://121.235.11.30/baokim/return.php?pay_system=46981&hallid=222',
            'merchantId' => '8056',
            'postUrl' => 'http://',
            'merchant_extra' => $merchantExtra,
        ];

        $baokimpay->setOptions($sourceData);
        $baokimpay->getVerifyData();
    }

    /**
     * 測試驗證支付是否成功,但未帶入key的情況
     */
    public function testVerifyPaymentNoKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $baokimpay = new Baokimpay();

        $baokimpay->verifyOrderPayment([]);
    }

    /**
     * 測試驗證支付是否成功,但未帶入pay_system的情況
     */
    public function testVerifyPaymentNoPaySystem()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $baokimpay = new Baokimpay();
        $baokimpay->setPrivateKey('de0887e31acb2dad');

        $baokimpay->verifyOrderPayment([]);
    }

    /**
     * 測試驗證支付是否成功,但未帶入hallid的情況
     */
    public function testVerifyPaymentNoHallid()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $baokimpay = new Baokimpay();
        $baokimpay->setPrivateKey('de0887e31acb2dad');

        $sourceData = ['pay_system' => '46981'];

        $baokimpay->setOptions($sourceData);
        $baokimpay->verifyOrderPayment([]);
    }

    /**
     * 測試驗證支付是否成功,但未帶入BPN驗證所需的相關參數的情況
     */
    public function testVerifyPaymentNoBPN()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $baokimpay = new Baokimpay();
        $baokimpay->setPrivateKey('de0887e31acb2dad');

        $sourceData = [
            'pay_system' => '46981',
            'hallid'     => '222'
        ];

        $baokimpay->setOptions($sourceData);
        $baokimpay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台連線失敗
     */
    public function testReturnPaymentGatewayConnectionFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment Gateway connection failure',
            180088
        );

        $exception = new \Exception('Payment Gateway connection failure', 180088);

        $this->curlClient->expects($this->any())
            ->method('send')
            ->willThrowException($exception);

        $baokimpay = new Baokimpay();
        $baokimpay->setContainer($this->container);
        $baokimpay->setClient($this->curlClient);
        $baokimpay->setPrivateKey('de0887e31acb2dad');

        $sourceData = [
            'pay_system' => '46981',
            'hallid' => '222',
            'created_on' => '1287729470',
            'customer_address' => 'Dia Chi Khach Hang',
            'customer_email' => 'khoinm@baokim.vn',
            'customer_name' => 'Nguyen Minh Khoi',
            'customer_phone' => '84987654321',
            'fee_amount' => '1000',
            'merchant_address' => 'Dia Chi Cong Ty',
            'merchant_email' => 'hangntt@baokim.vn',
            'merchant_id' => '8',
            'merchant_name' => 'Nguyen Thi Thu Hang',
            'merchant_phone' => '84981234567',
            'net_amount' => '99000',
            'order_amount' => '10',
            'order_id' => '100139',
            'payment_type' => '2',
            'total_amount' => '100000.00',
            'transaction_id' => '2506B4F7E6E6C',
            'transaction_status' => '4',
            'usd_vnd_exchange_rate' => '1',
            'verify_sign' => '2IsQX54QVnYrU2wpsaWJCusC1veXr0vu2auZ451trdoA6',
            'bpn_id' => '1254883544',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.baokimpay.com'
        ];

        $baokimpay->setOptions($sourceData);
        $baokimpay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時驗證BPN到支付平台連線失敗
     */
    public function testReturnPaymentGatewayConnectionFailureWithVerifyBPN()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment Gateway connection failure',
            180088
        );

        $this->curlClient->expects($this->any())
            ->method('send')
            ->willReturn('');

        $result = 'VERIFIED,1,100139';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 499 OK');
        $response->addHeader("Content-Type:application/json;charset=UTF-8");

        $baokimpay = new Baokimpay();
        $baokimpay->setContainer($this->container);
        $baokimpay->setClient($this->curlClient);
        $baokimpay->setResponse($response);
        $baokimpay->setPrivateKey('de0887e31acb2dad');

        $sourceData = [
            'pay_system' => '46981',
            'hallid' => '222',
            'created_on' => '1287729470',
            'customer_address' => 'Dia Chi Khach Hang',
            'customer_email' => 'khoinm@baokim.vn',
            'customer_name' => 'Nguyen Minh Khoi',
            'customer_phone' => '84987654321',
            'fee_amount' => '1000',
            'merchant_address' => 'Dia Chi Cong Ty',
            'merchant_email' => 'hangntt@baokim.vn',
            'merchant_id' => '8',
            'merchant_name' => 'Nguyen Thi Thu Hang',
            'merchant_phone' => '84981234567',
            'net_amount' => '99000',
            'order_amount' => '10',
            'order_id' => '100139',
            'payment_type' => '2',
            'total_amount' => '100000.00',
            'transaction_id' => '2506B4F7E6E6C',
            'transaction_status' => '4',
            'usd_vnd_exchange_rate' => '1',
            'verify_sign' => '2IsQX54QVnYrU2wpsaWJCusC1veXr0vu2auZ451trdoA6',
            'bpn_id' => '1254883544',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.baokimpay.com'
        ];

        $baokimpay->setOptions($sourceData);
        $baokimpay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台回傳結果為空
     */
    public function testReturnEmptyPaymentGatewayResponse()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Empty Payment Gateway response',
            180089
        );

        $this->curlClient->expects($this->any())
            ->method('send')
            ->willReturn('');

        $result = '';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader("Content-Type:application/json;charset=UTF-8");

        $baokimpay = new Baokimpay();
        $baokimpay->setContainer($this->container);
        $baokimpay->setClient($this->curlClient);
        $baokimpay->setResponse($response);
        $baokimpay->setPrivateKey('de0887e31acb2dad');

        $sourceData = [
            'pay_system' => '46981',
            'hallid' => '222',
            'created_on' => '1287729470',
            'customer_address' => 'Dia Chi Khach Hang',
            'customer_email' => 'khoinm@baokim.vn',
            'customer_name' => 'Nguyen Minh Khoi',
            'customer_phone' => '84987654321',
            'fee_amount' => '1000',
            'merchant_address' => 'Dia Chi Cong Ty',
            'merchant_email' => 'hangntt@baokim.vn',
            'merchant_id' => '8',
            'merchant_name' => 'Nguyen Thi Thu Hang',
            'merchant_phone' => '84981234567',
            'net_amount' => '99000',
            'order_amount' => '10',
            'order_id' => '100139',
            'payment_type' => '2',
            'total_amount' => '100000.00',
            'transaction_id' => '2506B4F7E6E6C',
            'transaction_status' => '4',
            'usd_vnd_exchange_rate' => '1',
            'verify_sign' => '2IsQX54QVnYrU2wpsaWJCusC1veXr0vu2auZ451trdoA6',
            'bpn_id' => '1254883544',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.baokimpay.com'
        ];

        $baokimpay->setOptions($sourceData);
        $baokimpay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付失敗(BPN回傳INVALID)
     */
    public function testReturnPaymentFailureWithInvalid()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $this->curlClient->expects($this->any())
            ->method('send')
            ->willReturn('');

        $result = 'INVALID';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader("Content-Type:application/json;charset=UTF-8");

        $baokimpay = new Baokimpay();
        $baokimpay->setContainer($this->container);
        $baokimpay->setClient($this->curlClient);
        $baokimpay->setResponse($response);
        $baokimpay->setPrivateKey('de0887e31acb2dad');

        $sourceData = [
            'pay_system' => '46981',
            'hallid' => '222',
            'created_on' => '1287729470',
            'customer_address' => 'Dia Chi Khach Hang',
            'customer_email' => 'khoinm@baokim.vn',
            'customer_name' => 'Nguyen Minh Khoi',
            'customer_phone' => '84987654321',
            'fee_amount' => '1000',
            'merchant_address' => 'Dia Chi Cong Ty',
            'merchant_email' => 'hangntt@baokim.vn',
            'merchant_id' => '8',
            'merchant_name' => 'Nguyen Thi Thu Hang',
            'merchant_phone' => '84981234567',
            'net_amount' => '99000',
            'order_amount' => '10',
            'order_id' => '100139',
            'payment_type' => '2',
            'total_amount' => '100000.00',
            'transaction_id' => '2506B4F7E6E6C',
            'transaction_status' => '4',
            'usd_vnd_exchange_rate' => '1',
            'verify_sign' => '2IsQX54QVnYrU2wpsaWJCusC1veXr0vu2auZ451trdoA6',
            'bpn_id' => '1254883544',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.baokimpay.com'
        ];

        $baokimpay->setOptions($sourceData);
        $baokimpay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付失敗(BPN不是回傳VERIFIED)
     */
    public function testReturnPaymentFailureWithVerified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $this->curlClient->expects($this->any())
            ->method('send')
            ->willReturn('');

        $result = 'VERIFIED,1,100139';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader("Content-Type:application/json;charset=UTF-8");

        $baokimpay = new Baokimpay();
        $baokimpay->setContainer($this->container);
        $baokimpay->setClient($this->curlClient);
        $baokimpay->setResponse($response);
        $baokimpay->setPrivateKey('de0887e31acb2dad');

        $sourceData = [
            'pay_system' => '46981',
            'hallid' => '222',
            'created_on' => '1287729470',
            'customer_address' => 'Dia Chi Khach Hang',
            'customer_email' => 'khoinm@baokim.vn',
            'customer_name' => 'Nguyen Minh Khoi',
            'customer_phone' => '84987654321',
            'fee_amount' => '1000',
            'merchant_address' => 'Dia Chi Cong Ty',
            'merchant_email' => 'hangntt@baokim.vn',
            'merchant_id' => '8',
            'merchant_name' => 'Nguyen Thi Thu Hang',
            'merchant_phone' => '84981234567',
            'net_amount' => '99000',
            'order_amount' => '10',
            'order_id' => '100139',
            'payment_type' => '2',
            'total_amount' => '100000.00',
            'transaction_id' => '2506B4F7E6E6C',
            'transaction_status' => '4',
            'usd_vnd_exchange_rate' => '1',
            'verify_sign' => '2IsQX54QVnYrU2wpsaWJCusC1veXr0vu2auZ451trdoA6',
            'bpn_id' => '1254883544',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.baokimpay.com'
        ];

        $baokimpay->setOptions($sourceData);
        $baokimpay->verifyOrderPayment([]);
    }

    /**
     * 測試支付驗證卻回傳錯誤的orderId
     */
    public function testPayWithErrorOrderId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $this->curlClient->expects($this->any())
            ->method('send')
            ->willReturn('');

        $result = 'VERIFIED,4,100139';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader("Content-Type:application/json;charset=UTF-8");

        $baokimpay = new Baokimpay();
        $baokimpay->setContainer($this->container);
        $baokimpay->setClient($this->curlClient);
        $baokimpay->setResponse($response);
        $baokimpay->setPrivateKey('de0887e31acb2dad');

        $sourceData = [
            'pay_system' => '46981',
            'hallid' => '222',
            'created_on' => '1287729470',
            'customer_address' => 'Dia Chi Khach Hang',
            'customer_email' => 'khoinm@baokim.vn',
            'customer_name' => 'Nguyen Minh Khoi',
            'customer_phone' => '84987654321',
            'fee_amount' => '1000',
            'merchant_address' => 'Dia Chi Cong Ty',
            'merchant_email' => 'hangntt@baokim.vn',
            'merchant_id' => '8',
            'merchant_name' => 'Nguyen Thi Thu Hang',
            'merchant_phone' => '84981234567',
            'net_amount' => '99000',
            'order_amount' => '10',
            'order_id' => '100139',
            'payment_type' => '2',
            'total_amount' => '100000.00',
            'transaction_id' => '2506B4F7E6E6C',
            'transaction_status' => '4',
            'usd_vnd_exchange_rate' => '1',
            'verify_sign' => '2IsQX54QVnYrU2wpsaWJCusC1veXr0vu2auZ451trdoA6',
            'bpn_id' => '1254883544',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.baokimpay.com'
        ];

        $entry = ['id' => '99999'];

        $baokimpay->setOptions($sourceData);
        $baokimpay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證卻回傳錯誤的amount
     */
    public function testPayWithErrorAmount()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $this->curlClient->expects($this->any())
            ->method('send')
            ->willReturn('');

        $result = 'VERIFIED,4,100139';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader("Content-Type:application/json;charset=UTF-8");

        $baokimpay = new Baokimpay();
        $baokimpay->setContainer($this->container);
        $baokimpay->setClient($this->curlClient);
        $baokimpay->setResponse($response);
        $baokimpay->setPrivateKey('de0887e31acb2dad');

        $sourceData = [
            'pay_system' => '46981',
            'hallid' => '222',
            'created_on' => '1287729470',
            'customer_address' => 'Dia Chi Khach Hang',
            'customer_email' => 'khoinm@baokim.vn',
            'customer_name' => 'Nguyen Minh Khoi',
            'customer_phone' => '84987654321',
            'fee_amount' => '1000',
            'merchant_address' => 'Dia Chi Cong Ty',
            'merchant_email' => 'hangntt@baokim.vn',
            'merchant_id' => '8',
            'merchant_name' => 'Nguyen Thi Thu Hang',
            'merchant_phone' => '84981234567',
            'net_amount' => '99000',
            'order_amount' => '10',
            'order_id' => '100139',
            'payment_type' => '2',
            'total_amount' => '100000.00',
            'transaction_id' => '2506B4F7E6E6C',
            'transaction_status' => '4',
            'usd_vnd_exchange_rate' => '1',
            'verify_sign' => '2IsQX54QVnYrU2wpsaWJCusC1veXr0vu2auZ451trdoA6',
            'bpn_id' => '1254883544',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.baokimpay.com'
        ];

        $entry = [
            'id' => '100139',
            'amount' => '90000.00'
        ];

        $baokimpay->setOptions($sourceData);
        $baokimpay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $this->curlClient->expects($this->any())
            ->method('send')
            ->willReturn('');

        $result = 'VERIFIED,4,100139';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader("Content-Type:application/json;charset=UTF-8");

        $baokimpay = new Baokimpay();
        $baokimpay->setContainer($this->container);
        $baokimpay->setClient($this->curlClient);
        $baokimpay->setResponse($response);
        $baokimpay->setPrivateKey('de0887e31acb2dad');

        $sourceData = [
            'pay_system' => '46981',
            'hallid' => '222',
            'created_on' => '1287729470',
            'customer_address' => 'Dia Chi Khach Hang',
            'customer_email' => 'khoinm@baokim.vn',
            'customer_name' => 'Nguyen Minh Khoi',
            'customer_phone' => '84987654321',
            'fee_amount' => '1000',
            'merchant_address' => 'Dia Chi Cong Ty',
            'merchant_email' => 'hangntt@baokim.vn',
            'merchant_id' => '8',
            'merchant_name' => 'Nguyen Thi Thu Hang',
            'merchant_phone' => '84981234567',
            'net_amount' => '99000',
            'order_amount' => '10',
            'order_id' => '100139',
            'payment_type' => '2',
            'total_amount' => '100000.00',
            'transaction_id' => '2506B4F7E6E6C',
            'transaction_status' => '4',
            'usd_vnd_exchange_rate' => '1',
            'verify_sign' => '2IsQX54QVnYrU2wpsaWJCusC1veXr0vu2auZ451trdoA6',
            'bpn_id' => '1254883544',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.baokimpay.com'
        ];

        $entry = [
            'id' => '100139',
            'amount' => '100000.00'
        ];

        $baokimpay->setOptions($sourceData);
        $baokimpay->verifyOrderPayment($entry);

        $this->assertEquals('success', $baokimpay->getMsg());
    }
}
