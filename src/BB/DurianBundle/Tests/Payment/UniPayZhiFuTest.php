<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\UniPayZhiFu;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class UniPayZhiFuTest extends DurianTestCase
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
     * RSA私鑰
     *
     * @var string
     */
    private $privateKey;

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

        $config = [
            'private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        // Create the keypair
        $res = openssl_pkey_new($config);

        $privkey = '';
        openssl_pkey_export($res, $privkey);
        $this->privateKey = base64_encode($privkey);

        $pubkey = openssl_pkey_get_details($res);
        $publicKey = base64_encode($pubkey['key']);

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
            'orderId' => '201807050000012439',
            'orderCreateDate' => '2018-07-05 14:52:01',
            'number' => '4301180400234',
            'ip' => '192.168.1.1',
            'paymentVendorId' => '1111',
            'amount' => '1',
            'notify_url' => 'http://return.php',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.api.fzmanba.com',
            'merchant_extra' => [
                'org' => '7259',
                'AliScanProdId' => 'Z8',
                'AliScanSettlePeriod' => 'D0',
                'AliPhoneProdId' => 'Z7',
                'AliPhoneSettlePeriod' => 'D0',
                'UnionScanProdId' => 'Y2',
                'UnionScanSettlePeriod' => 'T1',
                'UnionPhoneProdId' => 'Y3',
                'UnionPhoneSettlePeriod' => 'T1',
            ],
            'rsa_private_key' => $this->privateKey,
        ];

        $return = [
            'mchId' => '4301180400234',
            'orderId' => '201807050000012439',
            'payTime' => '20180705145122',
            'settlementPeriod' => 'T1',
            'advanceFee' => 0,
            'prodId' => 'Y2',
            'feeType' => 'CNY',
            'rootOrderId' => '20180705182743965701',
            'tradeState' => 'SUCCESS',
            'totalAmount' => 100,
            'actualPayAmount' => 100,
            'orgNo' => '43011337',
            'payChannel' => 'UNION_PAY',
            'settlementAmount' => 99,
            'tradeId' => 100000000000042110,
            'tradeType' => 'ORDER_CODE',
        ];

        $aes = $this->aesEncrypt($return);
        $this->returnResult = [
            'encryptData' => $aes,
            'sign' => $this->rsaEncrypt($aes),
            'rsa_public_key' => $publicKey,
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

        $uniPayZhiFu = new UniPayZhiFu();
        $uniPayZhiFu->getVerifyData();
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

        $uniPayZhiFu = new UniPayZhiFu();
        $uniPayZhiFu->setPrivateKey('test');
        $uniPayZhiFu->getVerifyData();
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

        $this->option['paymentVendorId'] = '6666';

        $uniPayZhiFu = new UniPayZhiFu();
        $uniPayZhiFu->setPrivateKey('test');
        $uniPayZhiFu->setOptions($this->option);
        $uniPayZhiFu->getVerifyData();
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

        $uniPayZhiFu = new UniPayZhiFu();
        $uniPayZhiFu->setPrivateKey('test');
        $uniPayZhiFu->setOptions($this->option);
        $uniPayZhiFu->getVerifyData();
    }

    /**
     * 測試取得RSA私鑰為空
     */
    public function testGetRsaPrivateKeyEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa private key is empty',
            180092
        );

        $this->option['rsa_private_key'] = '';

        $uniPayZhiFu = new UniPayZhiFu();
        $uniPayZhiFu->setPrivateKey('test');
        $uniPayZhiFu->setOptions($this->option);
        $uniPayZhiFu->getVerifyData();
    }

    /**
     * 測試取得RSA私鑰失敗
     */
    public function testGetRsaPrivateKeyFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa private key failure',
            180093
        );

        $this->option['rsa_private_key'] = '123456';

        $uniPayZhiFu = new UniPayZhiFu();
        $uniPayZhiFu->setPrivateKey('test');
        $uniPayZhiFu->setOptions($this->option);
        $uniPayZhiFu->getVerifyData();
    }

    /**
     * 測試產生RSA加密簽名失敗
     */
    public function testGenerateSignatureFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Generate signature failure',
            180144
        );

        $config = [
            'private_key_bits' => 384,
            'private_key_type' => OPENSSL_KEYTYPE_DH,
        ];

        // Create the keypair
        $res = openssl_pkey_new($config);

        $privkey = '';
        openssl_pkey_export($res, $privkey);
        $this->option['rsa_private_key'] = base64_encode($privkey);

        $uniPayZhiFu = new UniPayZhiFu();
        $uniPayZhiFu->setPrivateKey('test');
        $uniPayZhiFu->setOptions($this->option);
        $uniPayZhiFu->getVerifyData();
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

        $uniPayZhiFu = new UniPayZhiFu();
        $uniPayZhiFu->setPrivateKey('test');
        $uniPayZhiFu->setOptions($this->option);
        $uniPayZhiFu->getVerifyData();
    }

    /**
     * 測試支付時沒有返回rstCode
     */
    public function testPayReturnWithoutRstCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'traceId' => '',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $uniPayZhiFu = new UniPayZhiFu();
        $uniPayZhiFu->setContainer($this->container);
        $uniPayZhiFu->setClient($this->client);
        $uniPayZhiFu->setResponse($response);
        $uniPayZhiFu->setPrivateKey('test');
        $uniPayZhiFu->setOptions($this->option);
        $uniPayZhiFu->getVerifyData();
    }

    /**
     * 測試支付時返回rstCode不等於000000
     */
    public function testPayReturnRstCodeNotEqual000000()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '无效的timestamp',
            180130
        );

        $result = [
            'traceId' => '201807030000012369',
            'rstCode' => '400903',
            'rstMsg' => '无效的timestamp',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $uniPayZhiFu = new UniPayZhiFu();
        $uniPayZhiFu->setContainer($this->container);
        $uniPayZhiFu->setClient($this->client);
        $uniPayZhiFu->setResponse($response);
        $uniPayZhiFu->setPrivateKey('test');
        $uniPayZhiFu->setOptions($this->option);
        $uniPayZhiFu->getVerifyData();
    }

    /**
     * 測試支付時返回rstCode不等於000000且無rstMsg
     */
    public function testPayReturnRstCodeNotEqual000000AndNoRstMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $result = [
            'traceId' => '201807030000012369',
            'rstCode' => '400903',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $uniPayZhiFu = new UniPayZhiFu();
        $uniPayZhiFu->setContainer($this->container);
        $uniPayZhiFu->setClient($this->client);
        $uniPayZhiFu->setResponse($response);
        $uniPayZhiFu->setPrivateKey('test');
        $uniPayZhiFu->setOptions($this->option);
        $uniPayZhiFu->getVerifyData();
    }

    /**
     * 測試支付時沒有返回encryptData
     */
    public function testPayReturnWithoutEncryptData()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'traceId' => '201807030000012369',
            'rstCode' => '000000',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $uniPayZhiFu = new UniPayZhiFu();
        $uniPayZhiFu->setContainer($this->container);
        $uniPayZhiFu->setClient($this->client);
        $uniPayZhiFu->setResponse($response);
        $uniPayZhiFu->setPrivateKey('test');
        $uniPayZhiFu->setOptions($this->option);
        $uniPayZhiFu->getVerifyData();
    }

    /**
     * 測試支付時沒有返回resultCode
     */
    public function testPayReturnWithoutResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'traceId' => '201807030000012369',
            'rstCode' => '000000',
            'encryptData' => $this->aesEncrypt([]),
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $uniPayZhiFu = new UniPayZhiFu();
        $uniPayZhiFu->setContainer($this->container);
        $uniPayZhiFu->setClient($this->client);
        $uniPayZhiFu->setResponse($response);
        $uniPayZhiFu->setPrivateKey('test');
        $uniPayZhiFu->setOptions($this->option);
        $uniPayZhiFu->getVerifyData();
    }

    /**
     * 測試支付時返回提resultCode不等於SUCCESS
     */
    public function testPayReturnNotEqualSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '不成功',
            180130
        );

        $encrypt = [
            'errorCode' => '9999',
            'errorCodeDes' => '不成功',
            'resultCode' => 'NOTSUCCESS',
        ];

        $result = [
            'traceId' => '201807030000012369',
            'rstCode' => '000000',
            'encryptData' => $this->aesEncrypt($encrypt),
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $uniPayZhiFu = new UniPayZhiFu();
        $uniPayZhiFu->setContainer($this->container);
        $uniPayZhiFu->setClient($this->client);
        $uniPayZhiFu->setResponse($response);
        $uniPayZhiFu->setPrivateKey('test');
        $uniPayZhiFu->setOptions($this->option);
        $uniPayZhiFu->getVerifyData();
    }

    /**
     * 測試支付時返回提resultCode不等於SUCCESS且無errorCodeDes
     */
    public function testPayReturnNotEqualSuccessAndNoErrorCodeDes()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $encrypt = [
            'errorCode' => '9999',
            'resultCode' => 'NOTSUCCESS',
        ];

        $result = [
            'traceId' => '201807030000012369',
            'rstCode' => '000000',
            'encryptData' => $this->aesEncrypt($encrypt),
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $uniPayZhiFu = new UniPayZhiFu();
        $uniPayZhiFu->setContainer($this->container);
        $uniPayZhiFu->setClient($this->client);
        $uniPayZhiFu->setResponse($response);
        $uniPayZhiFu->setPrivateKey('test');
        $uniPayZhiFu->setOptions($this->option);
        $uniPayZhiFu->getVerifyData();
    }

    /**
     * 測試支付時沒有返回tradeState
     */
    public function testPayReturnWithoutTradeState()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $encrypt = [
            'resultCode' => 'SUCCESS',
        ];

        $result = [
            'traceId' => '201807030000012369',
            'rstCode' => '000000',
            'encryptData' => $this->aesEncrypt($encrypt),
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $uniPayZhiFu = new UniPayZhiFu();
        $uniPayZhiFu->setContainer($this->container);
        $uniPayZhiFu->setClient($this->client);
        $uniPayZhiFu->setResponse($response);
        $uniPayZhiFu->setPrivateKey('test');
        $uniPayZhiFu->setOptions($this->option);
        $uniPayZhiFu->getVerifyData();
    }

    /**
     * 測試支付時返回tradeState不等於WAIT_PAY
     */
    public function testPayReturnTradeStateNotEqualWaitPay()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '交易失敗',
            180130
        );

        $encrypt = [
            'actualPayAmount' => 100,
            'errorCode' => '9999',
            'errorCodeDes' => '交易失敗',
            'feeType' => 'CNY',
            'orderId' => '201807040000012410',
            'orgNo' => '43011337',
            'payChannel' => 'TEN_PAY',
            'resultCode' => 'SUCCESS',
            'totalAmount' => 100,
            'tradeId' => 100000000000041800,
            'tradeState' => 'FAIL',
            'tradeType' => 'ORDER_CODE',
        ];

        $result = [
            'traceId' => '201807040000012410',
            'rstCode' => '000000',
            'encryptData' => $this->aesEncrypt($encrypt),
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $uniPayZhiFu = new UniPayZhiFu();
        $uniPayZhiFu->setContainer($this->container);
        $uniPayZhiFu->setClient($this->client);
        $uniPayZhiFu->setResponse($response);
        $uniPayZhiFu->setPrivateKey('test');
        $uniPayZhiFu->setOptions($this->option);
        $uniPayZhiFu->getVerifyData();
    }

    /**
     * 測試支付時返回tradeState不等於WAIT_PAY且無errorCodeDes
     */
    public function testPayReturnTradeStateNotEqualWaitPayAndNoErrorCodeDes()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $encrypt = [
            'actualPayAmount' => 100,
            'errorCode' => '9999',
            'feeType' => 'CNY',
            'orderId' => '201807040000012410',
            'orgNo' => '43011337',
            'payChannel' => 'TEN_PAY',
            'resultCode' => 'SUCCESS',
            'totalAmount' => 100,
            'tradeId' => 100000000000041800,
            'tradeState' => 'FAIL',
            'tradeType' => 'ORDER_CODE',
        ];

        $result = [
            'traceId' => '201807040000012410',
            'rstCode' => '000000',
            'encryptData' => $this->aesEncrypt($encrypt),
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $uniPayZhiFu = new UniPayZhiFu();
        $uniPayZhiFu->setContainer($this->container);
        $uniPayZhiFu->setClient($this->client);
        $uniPayZhiFu->setResponse($response);
        $uniPayZhiFu->setPrivateKey('test');
        $uniPayZhiFu->setOptions($this->option);
        $uniPayZhiFu->getVerifyData();
    }

    /**
     * 測試支付時沒有返回codeUrl
     */
    public function testPayReturnWithoutCodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $encrypt = [
            'actualPayAmount' => 100,
            'feeType' => 'CNY',
            'orderId' => '201807040000012410',
            'orgNo' => '43011337',
            'payChannel' => 'TEN_PAY',
            'resultCode' => 'SUCCESS',
            'totalAmount' => 100,
            'tradeId' => 100000000000041800,
            'tradeState' => 'WAIT_PAY',
            'tradeType' => 'ORDER_CODE',
        ];

        $result = [
            'traceId' => '201807040000012410',
            'rstCode' => '000000',
            'encryptData' => $this->aesEncrypt($encrypt),
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $uniPayZhiFu = new UniPayZhiFu();
        $uniPayZhiFu->setContainer($this->container);
        $uniPayZhiFu->setClient($this->client);
        $uniPayZhiFu->setResponse($response);
        $uniPayZhiFu->setPrivateKey('test');
        $uniPayZhiFu->setOptions($this->option);
        $uniPayZhiFu->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $encrypt = [
            'actualPayAmount' => 100,
            'codeUrl' => 'https://qr.95516.com/48020000/41021807049545615165705599',
            'feeType' => 'CNY',
            'orderId' => '201807040000012410',
            'orgNo' => '43011337',
            'payChannel' => 'TEN_PAY',
            'resultCode' => 'SUCCESS',
            'totalAmount' => 100,
            'tradeId' => 100000000000041800,
            'tradeState' => 'WAIT_PAY',
            'tradeType' => 'ORDER_CODE',
        ];

        $result = [
            'traceId' => '201807040000012410',
            'rstCode' => '000000',
            'encryptData' => $this->aesEncrypt($encrypt),
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $uniPayZhiFu = new UniPayZhiFu();
        $uniPayZhiFu->setContainer($this->container);
        $uniPayZhiFu->setClient($this->client);
        $uniPayZhiFu->setResponse($response);
        $uniPayZhiFu->setPrivateKey('test');
        $uniPayZhiFu->setOptions($this->option);
        $data = $uniPayZhiFu->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://qr.95516.com/48020000/41021807049545615165705599', $uniPayZhiFu->getQrcode());
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $this->option['paymentVendorId'] = '1098';

        $payUrl = 'https://i.ziyezi.com/dist/h5pay/index.html?c=ALIPAY&k=6413ba373c808b43d3f979d286362c52&t=' .
            '1530694292697&s=dfca2228e595d1c8e53c345a57c3069e';

        $encrypt = [
            'actualPayAmount' => 100,
            'codeUrl' => 'https://qr.95516.com/48020000/41021807049545615165705599',
            'feeType' => 'CNY',
            'orderId' => '201807040000012409',
            'orgNo' => '43011337',
            'payChannel' => 'ALIPAY',
            'resultCode' => 'SUCCESS',
            'totalAmount' => 500,
            'tradeId' => 100000000000041799,
            'tradeState' => 'WAIT_PAY',
            'tradeType' => 'H5_WAP',
            'tradeUrl' => $payUrl,
        ];

        $result = [
            'traceId' => '201807040000012410',
            'rstCode' => '000000',
            'encryptData' => $this->aesEncrypt($encrypt),
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $uniPayZhiFu = new UniPayZhiFu();
        $uniPayZhiFu->setContainer($this->container);
        $uniPayZhiFu->setClient($this->client);
        $uniPayZhiFu->setResponse($response);
        $uniPayZhiFu->setPrivateKey('test');
        $uniPayZhiFu->setOptions($this->option);
        $data = $uniPayZhiFu->getVerifyData();

        $this->assertEquals('https://i.ziyezi.com/dist/h5pay/index.html', $data['post_url']);
        $this->assertEquals('ALIPAY', $data['params']['c']);
        $this->assertEquals('6413ba373c808b43d3f979d286362c52', $data['params']['k']);
        $this->assertEquals('1530694292697', $data['params']['t']);
        $this->assertEquals('dfca2228e595d1c8e53c345a57c3069e', $data['params']['s']);
        $this->assertEquals('GET', $uniPayZhiFu->getPayMethod());
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

        $uniPayZhiFu = new UniPayZhiFu();
        $uniPayZhiFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未指定encryptData參數
     */
    public function testReturnWithoutEncryptData()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['encryptData']);

        $uniPayZhiFu = new UniPayZhiFu();
        $uniPayZhiFu->setPrivateKey('test');
        $uniPayZhiFu->setOptions($this->returnResult);
        $uniPayZhiFu->verifyOrderPayment([]);
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

        unset($this->returnResult['sign']);

        $uniPayZhiFu = new UniPayZhiFu();
        $uniPayZhiFu->setPrivateKey('test');
        $uniPayZhiFu->setOptions($this->returnResult);
        $uniPayZhiFu->verifyOrderPayment([]);
    }

    /**
     * 測試取得RSA公鑰為空
     */
    public function testGetRsaPublicKeyEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa public key is empty',
            180095
        );

        $this->returnResult['rsa_public_key'] = '';

        $uniPayZhiFu = new UniPayZhiFu();
        $uniPayZhiFu->setPrivateKey('test');
        $uniPayZhiFu->setOptions($this->returnResult);
        $uniPayZhiFu->verifyOrderPayment([]);
    }

    /**
     * 測試取得RSA公鑰失敗
     */
    public function testGetRsaPublicKeyFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa public key failure',
            180096
        );

        $this->returnResult['rsa_public_key'] = '123456';

        $uniPayZhiFu = new UniPayZhiFu();
        $uniPayZhiFu->setPrivateKey('test');
        $uniPayZhiFu->setOptions($this->returnResult);
        $uniPayZhiFu->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = 'error';

        $uniPayZhiFu = new UniPayZhiFu();
        $uniPayZhiFu->setPrivateKey('test');
        $uniPayZhiFu->setOptions($this->returnResult);
        $uniPayZhiFu->verifyOrderPayment([]);
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

        $return = [
            'orderId' => '201807050000012439',
            'payTime' => '20180705145122',
            'settlementPeriod' => 'T1',
            'tradeState' => 'SUCCESS',
            'totalAmount' => 100,
            'actualPayAmount' => 100,
            'orgNo' => '43011337',
            'payChannel' => 'UNION_PAY',
            'settlementAmount' => 99,
            'tradeId' => 100000000000042110,
            'tradeType' => 'ORDER_CODE',
        ];

        $aes = $this->aesEncrypt($return);
        $this->returnResult['encryptData'] = $aes;
        $this->returnResult['sign'] = $this->rsaEncrypt($aes);

        $uniPayZhiFu = new UniPayZhiFu();
        $uniPayZhiFu->setPrivateKey('test');
        $uniPayZhiFu->setOptions($this->returnResult);
        $uniPayZhiFu->verifyOrderPayment([]);
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

        $data = hex2bin($this->returnResult['encryptData']);
        $return = openssl_decrypt($data, 'aes-128-ecb', 'test', OPENSSL_RAW_DATA);
        $return = json_decode($return, true);
        $return['tradeState'] = 'FAIL';
        $aes = $this->aesEncrypt($return);
        $this->returnResult['encryptData'] = $aes;
        $this->returnResult['sign'] = $this->rsaEncrypt($aes);

        $uniPayZhiFu = new UniPayZhiFu();
        $uniPayZhiFu->setPrivateKey('test');
        $uniPayZhiFu->setOptions($this->returnResult);
        $uniPayZhiFu->verifyOrderPayment([]);
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

        $uniPayZhiFu = new UniPayZhiFu();
        $uniPayZhiFu->setPrivateKey('test');
        $uniPayZhiFu->setOptions($this->returnResult);
        $uniPayZhiFu->verifyOrderPayment($entry);
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
            'id' => '201807050000012439',
            'amount' => '123',
        ];

        $uniPayZhiFu = new UniPayZhiFu();
        $uniPayZhiFu->setPrivateKey('test');
        $uniPayZhiFu->setOptions($this->returnResult);
        $uniPayZhiFu->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $entry = [
            'id' => '201807050000012439',
            'amount' => '1',
        ];

        $uniPayZhiFu = new UniPayZhiFu();
        $uniPayZhiFu->setPrivateKey('test');
        $uniPayZhiFu->setOptions($this->returnResult);
        $uniPayZhiFu->verifyOrderPayment($entry);

        $this->assertEquals('success', $uniPayZhiFu->getMsg());
    }

    /**
     * AES加密
     *
     * @param array $data 待加密資料
     * @return string
     */
    private function aesEncrypt($data)
    {
        $encodeStr = openssl_encrypt(json_encode($data), 'aes-128-ecb', 'test', OPENSSL_RAW_DATA);

        return bin2hex($encodeStr);
    }

    /**
     * RSA加密
     *
     * @param string $data 待加密資料
     * @return string
     */
    private function rsaEncrypt($data)
    {
        $privateKey = trim(base64_decode($this->privateKey));

        openssl_sign($data, $sign, $privateKey);

        return base64_encode($sign);
    }
}
