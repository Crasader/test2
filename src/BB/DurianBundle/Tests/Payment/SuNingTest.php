<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\SuNing;
use Buzz\Message\Response;

class SuNingTest extends DurianTestCase
{
    /**
     * 私鑰
     *
     * @var string
     */
    private $privateKey;

    /**
     * 公鑰
     *
     * @var string
     */
    private $publicKey;

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * @var \Buzz\Client\Curl
     */
    private $client;

    /**
     * 出款請求時的參數
     *
     * @var array
     */
    private $withdrawParams;

    /**
     * 出款對外返回的參數
     *
     * @var array
     */
    private $withdrawVerifyResult;

    public function setUp()
    {
        parent::setUp();

        $config = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        // Create the key pair
        $res = openssl_pkey_new($config);

        // Get private key
        $pkey = '';
        openssl_pkey_export($res, $pkey);
        $this->privateKey = base64_encode($pkey);

        // Get public key
        $pubKey = openssl_pkey_get_details($res);
        $this->publicKey = base64_encode($pubKey['key']);

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

        $this->withdrawParams = [
            'number' => '7021000000',
            'orderId' => '10000',
            'orderCreateDate' => '2018-06-11 16:15:14',
            'account' => '65867561',
            'nameReal' => '王小明',
            'bank_name' => '中信銀行',
            'bank_info_id' => '11',
            'amount' => '1',
            'shop_url' => 'http://pay.wang999.com',
            'withdraw_host' => 'payment.https.wag.yifubao.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => [
                'publicKeyIndex' => '00001',
                'productCode' => '123456',
                'goodsType' => '123456',
            ],
            'rsa_private_key' => $this->privateKey,
        ];

        $this->withdrawVerifyResult = [
            '' => '',
        ];
    }

    /**
     * 測試出款未指定出款參數
     */
    public function testWithdrawNoWithdrawParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No withdraw parameter specified',
            150180196
        );

        $suNing = new SuNing();
        $suNing->setOptions([]);
        $suNing->withdrawPayment();
    }

    /**
     * 測試出款沒有帶入Withdraw_host
     */
    public function testWithdrawWithoutWithdrawHost()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No withdraw_host specified',
            150180194
        );

        $this->withdrawParams['withdraw_host'] = '';

        $suNing = new SuNing();
        $suNing->setOptions($this->withdrawParams);
        $suNing->withdrawPayment();
    }

    /**
     * 測試出款帶入未支援的出款銀行
     */
    public function testWithdrawBankInfoNotSupported()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'BankInfo is not supported by PaymentGateway',
            150180195
        );

        $this->withdrawParams['bank_info_id'] = '66666';

        $suNing = new SuNing();
        $suNing->setOptions($this->withdrawParams);
        $suNing->withdrawPayment();
    }

    /**
     * 測試出款缺少商家附加設定值
     */
    public function testWithdrawWithoutMerchantExtra()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $this->withdrawParams['merchant_extra'] = [];

        $suNing = new SuNing();
        $suNing->setOptions($this->withdrawParams);
        $suNing->withdrawPayment();
    }

    /**
     * 測試出款加密失敗
     */
    public function testWithdrawGenerateSignatureFailure()
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

        // Create the key pair
        $res = openssl_pkey_new($config);

        // Get private key
        $pkey = '';
        openssl_pkey_export($res, $pkey);

        $this->withdrawParams['rsa_private_key'] = base64_encode($pkey);

        $suNing = new SuNing();
        $suNing->setOptions($this->withdrawParams);
        $suNing->withdrawPayment();
    }

    /**
     * 測試出款但返回結果缺少參數
     */
    public function testWithdrawButNoWithdrawReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No withdraw return parameter specified',
            150180209
        );

        $response = new Response();
        $response->setContent(json_encode([]));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $suNing = new SuNing();
        $suNing->setContainer($this->container);
        $suNing->setClient($this->client);
        $suNing->setResponse($response);
        $suNing->setOptions($this->withdrawParams);
        $suNing->withdrawPayment();
    }

    /**
     * 測試出款異常返回結果缺少參數
     */
    public function testWithdrawFailedNoWithdrawReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No withdraw return parameter specified',
            150180209
        );

        $key = sprintf(
            '%s_%s',
            $this->withdrawParams['orderId'],
            $this->withdrawParams['number']
        );

        $result = [
            $key => [],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $suNing = new SuNing();
        $suNing->setContainer($this->container);
        $suNing->setClient($this->client);
        $suNing->setResponse($response);
        $suNing->setOptions($this->withdrawParams);
        $suNing->withdrawPayment();
    }

    /**
     * 測試出款返回異常
     */
    public function testWithdrawFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '该批次已存在',
            180124
        );

        $key = sprintf(
            '%s_%s',
            $this->withdrawParams['orderId'],
            $this->withdrawParams['number']
        );

        $result = [
            $key => [
                'responseCode' => '2004',
                'responseMsg' => '该批次已存在',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $suNing = new SuNing();
        $suNing->setContainer($this->container);
        $suNing->setClient($this->client);
        $suNing->setResponse($response);
        $suNing->setOptions($this->withdrawParams);
        $suNing->withdrawPayment();
    }

    /**
     * 測試出款返回異常且無錯誤訊息
     */
    public function testWithdrawFailedButNoResponseMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Withdraw error',
            180124
        );

        $key = sprintf(
            '%s_%s',
            $this->withdrawParams['orderId'],
            $this->withdrawParams['number']
        );

        $result = [
            $key => [
                'responseCode' => '2004',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $suNing = new SuNing();
        $suNing->setContainer($this->container);
        $suNing->setClient($this->client);
        $suNing->setResponse($response);
        $suNing->setOptions($this->withdrawParams);
        $suNing->withdrawPayment();
    }

    /**
     * 測試出款返回成功
     */
    public function testWithdrawSuccess()
    {
        $result = [
            'responseCode' => '0000'
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $suNing = new SuNing();
        $suNing->setContainer($this->container);
        $suNing->setClient($this->client);
        $suNing->setResponse($response);
        $suNing->setOptions($this->withdrawParams);
        $suNing->withdrawPayment();
    }
}
