<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\PayEase;
use Buzz\Message\Response;

class PayEaseTest extends DurianTestCase
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
     * 測試加密基本參數設定沒有帶入privateKey的情況
     */
    public function testSetEncodeSourceNoPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $payEase = new PayEase();
        $payEase->getVerifyData();
    }

    /**
     * 測試加密基本參數設定未指定支付參數
     */
    public function testSetEncodeSourceNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $payEase = new PayEase();
        $payEase->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');

        $sourceData = ['number' => ''];

        $payEase->setOptions($sourceData);
        $payEase->getVerifyData();
    }

    /**
     * 測試加密時PrivateKey長度超過64
     */
    public function testGetEncodeDataWithPrivateKeyLength()
    {
        $payEase = new PayEase();
        $payEase->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j12345');

        $sourceData = [
            'number' => '10012150139',
            'orderCreateDate' => '20140424',
            'orderId' => '201405120018316114',
            'amount' => '1',
            'notify_url' => 'http://pay.rasfasl.com/pay/pay_response.php?pay_system=48542&hallid=206',
            'username' => 'php1test',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $payEase->setOptions($sourceData);
        $payEase->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $sourceData = [
            'number' => '10012150139',
            'orderCreateDate' => '20140424',
            'orderId' => '201405120018316114',
            'amount' => '1',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'username' => 'php1test',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $payEase = new PayEase();
        $payEase->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');
        $payEase->setOptions($sourceData);
        $encodeData = $payEase->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals($sourceData['number'], $encodeData['v_mid']);
        $this->assertEquals(
            $sourceData['orderCreateDate'].'-'.$sourceData['number'].'-'.$sourceData['orderId'],
            $encodeData['v_oid']
        );
        $this->assertEquals($sourceData['amount'], $encodeData['v_amount']);
        $this->assertEquals($sourceData['orderCreateDate'], $encodeData['v_ymd']);
        $this->assertEquals($notifyUrl, $encodeData['v_url']);
        $this->assertEquals($sourceData['username'], $encodeData['v_ordername']);
        $this->assertEquals('7ee1a6916aab179b41df26ecc4c6c788', $encodeData['v_md5info']);
    }

    /**
     * 測試解密基本參數設定沒有帶入key的情況
     */
    public function testSetDecodeSourceNoKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $payEase = new PayEase();

        $payEase->verifyOrderPayment([]);
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

        $payEase = new PayEase();
        $payEase->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');

        $sourceData = [
            'v_oid'       => '20051215-888-000616',
            'v_pstatus'   => '1',
            'v_moneytype' => '0',
            'v_count'     => '1',
            'v_md5money'  => 'e9101cd8e9af19090b48eec7bdce8b38'
        ];

        $payEase->setOptions($sourceData);
        $payEase->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台沒有回傳v_md5money(加密簽名)
     */
    public function testVerifyWithoutMd5money()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $payEase = new PayEase();
        $payEase->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');

        $sourceData = [
            'v_oid'       => '20051215-888-000616',
            'v_pstatus'   => '1',
            'v_amount'    => '1.00',
            'v_moneytype' => '0',
            'v_count'     => '1'
        ];

        $payEase->setOptions($sourceData);
        $payEase->verifyOrderPayment([]);
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

        $payEase = new PayEase();
        $payEase->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');

        $sourceData = [
            'v_oid'       => '20051215-888-000616',
            'v_pstatus'   => '1',
            'v_amount'    => '1.00',
            'v_moneytype' => '0',
            'v_count'     => '1',
            'v_md5money'  => 'x'
        ];

        $payEase->setOptions($sourceData);
        $payEase->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台沒有回傳v_pstatus(狀態)
     */
    public function testVerifyWithoutPstatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $payEase = new PayEase();
        $payEase->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');

        $sourceData = [
            'v_oid'       => '20051215-888-000616',
            'v_amount'    => '1.00',
            'v_moneytype' => '0',
            'v_count'     => '1',
            'v_md5money'  => 'e9101cd8e9af19090b48eec7bdce8b38'
        ];

        $payEase->setOptions($sourceData);
        $payEase->verifyOrderPayment([]);
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

        $payEase = new PayEase();
        $payEase->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');

        $sourceData = [
            'v_oid'       => '20051215-888-000616',
            'v_pstatus'   => '9',
            'v_amount'    => '1.00',
            'v_moneytype' => '0',
            'v_count'     => '1',
            'v_md5money'  => 'e9101cd8e9af19090b48eec7bdce8b38'
        ];

        $payEase->setOptions($sourceData);
        $payEase->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $payEase = new PayEase();
        $payEase->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');

        $sourceData = [
            'v_oid'       => '20051215-888-000616',
            'v_pstatus'   => '1',
            'v_amount'    => '1.00',
            'v_moneytype' => '0',
            'v_count'     => '1',
            'v_md5money'  => 'e9101cd8e9af19090b48eec7bdce8b38'
        ];

        $entry = ['id' => '19990720'];

        $payEase->setOptions($sourceData);
        $payEase->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $payEase = new PayEase();
        $payEase->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');

        $sourceData = [
            'v_oid'       => '20051215-888-000616',
            'v_pstatus'   => '1',
            'v_amount'    => '1.00',
            'v_moneytype' => '0',
            'v_count'     => '1',
            'v_md5money'  => 'e9101cd8e9af19090b48eec7bdce8b38'
        ];

        $entry = [
            'id' => '000616',
            'amount' => '9900.0000'
        ];

        $payEase->setOptions($sourceData);
        $payEase->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功，支付狀態為1
     */
    public function testPaySuccessPstatus1()
    {
        $payEase = new PayEase();
        $payEase->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');

        $sourceData = [
            'v_oid'       => '20051215-888-000616',
            'v_pstatus'   => '1',
            'v_amount'    => '1.00',
            'v_moneytype' => '0',
            'v_count'     => '1',
            'v_md5money'  => 'e9101cd8e9af19090b48eec7bdce8b38'
        ];

        $entry = [
            'id' => '000616',
            'amount' => '1.00'
        ];

        $payEase->setOptions($sourceData);
        $payEase->verifyOrderPayment($entry);

        $this->assertEquals('sent', $payEase->getMsg());
    }

    /**
     * 測試支付驗證成功，支付狀態為20
     */
    public function testPaySuccessPstatus20()
    {
        $payEase = new PayEase();
        $payEase->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');

        $sourceData = [
            'v_oid'       => '20051215-888-000616',
            'v_pstatus'   => '20',
            'v_amount'    => '1.00',
            'v_moneytype' => '0',
            'v_count'     => '1',
            'v_md5money'  => 'e9101cd8e9af19090b48eec7bdce8b38'
        ];

        $entry = [
            'id' => '000616',
            'amount' => '1.00'
        ];

        $payEase->setOptions($sourceData);
        $payEase->verifyOrderPayment($entry);

        $this->assertEquals('sent', $payEase->getMsg());
    }

    /**
     * 測試訂單查詢沒代入privateKey
     */
    public function testPaymentTrackingWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $payEase = new PayEase();
        $payEase->paymentTracking();
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

        $payEase = new PayEase();
        $payEase->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');
        $payEase->paymentTracking();
    }

    /**
     * 測試訂單查詢沒代入orderCreateDate
     */
    public function testPaymentTrackingWithoutOrderCreateDate()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $sourceData = [
            'number' => '888',
            'orderId' => '201404150014262827',
            'orderCreateDate' => '',
        ];

        $payEase = new PayEase();
        $payEase->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');
        $payEase->setOptions($sourceData);
        $payEase->paymentTracking();
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
            'number' => '888',
            'orderId' => '201404150014262827',
            'orderCreateDate' => '20140424',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $payEase = new PayEase();
        $payEase->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');
        $payEase->setOptions($sourceData);
        $payEase->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數messagebody
     */
    public function testPaymentTrackingResultWithoutMessagebody()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $params = '<?xml version="1.0" encoding="gb2312"?>'.
            '<ordermessage></ordermessage>';
        $result = iconv("utf-8", "gb2312", $params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '888',
            'orderId' => '201404150014262827',
            'orderCreateDate' => '20140424',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.payeasy.com'
        ];

        $payEase = new PayEase();
        $payEase->setContainer($this->container);
        $payEase->setClient($this->client);
        $payEase->setResponse($response);
        $payEase->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');
        $payEase->setOptions($sourceData);
        $payEase->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數pstatus
     */
    public function testPaymentTrackingResultWithoutPstatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $params = '<?xml version="1.0" encoding="gb2312"?>'.
            '<ordermessage>'.
            '<messagebody>'.
            '<order></order>'.
            '</messagebody>'.
            '</ordermessage>';
        $result = iconv("utf-8", "gb2312", $params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '888',
            'orderId' => '201404150014262827',
            'orderCreateDate' => '20140424',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.payeasy.com'
        ];

        $payEase = new PayEase();
        $payEase->setContainer($this->container);
        $payEase->setClient($this->client);
        $payEase->setResponse($response);
        $payEase->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');
        $payEase->setOptions($sourceData);
        $payEase->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單未支付
     */
    public function testTrackingReturnUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $params = '<?xml version="1.0" encoding="gb2312"?>'.
            '<ordermessage>'.
            '<messagebody>'.
            '<order>'.
            '<pstatus>2</pstatus>'.
            '</order>'.
            '</messagebody>'.
            '</ordermessage>';
        $result = iconv("utf-8", "gb2312", $params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '888',
            'orderId' => '201404150014262827',
            'orderCreateDate' => '20140424',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.payeasy.com'
        ];

        $payEase = new PayEase();
        $payEase->setContainer($this->container);
        $payEase->setClient($this->client);
        $payEase->setResponse($response);
        $payEase->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');
        $payEase->setOptions($sourceData);
        $payEase->paymentTracking();
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

        $params = '<?xml version="1.0" encoding="gb2312"?>'.
            '<ordermessage>'.
            '<messagebody>'.
            '<order>'.
            '<pstatus>999</pstatus>'.
            '</order>'.
            '</messagebody>'.
            '</ordermessage>';
        $result = iconv("utf-8", "gb2312", $params);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '888',
            'orderId' => '201404150014262827',
            'orderCreateDate' => '20140424',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.payeasy.com'
        ];

        $payEase = new PayEase();
        $payEase->setContainer($this->container);
        $payEase->setClient($this->client);
        $payEase->setResponse($response);
        $payEase->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');
        $payEase->setOptions($sourceData);
        $payEase->paymentTracking();
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

        $params = '<?xml version="1.0" encoding="gb2312"?>'.
            '<ordermessage>'.
            '<messagehead>'.
            '<status>0</status>'.
            '<statusdesc>Success</statusdesc>'.
            '<mid>888</mid>'.
            '<oid>20051215-888-000616</oid>'.
            '</messagehead>'.
            '<messagebody>'.
            '<order>'.
            '<orderindex>1</orderindex>'.
            '<oid>20051215-888-000616</oid>'.
            '<pmode>招商银行SZ</pmode>'.
            '<pstatus>1</pstatus>'.
            '<pstring>支付成功</pstring>'.
            '<amount>0.01</amount>'.
            '<moneytype>0</moneytype>'.
            '<isvirement>0</isvirement>'.
            '<sign>bf1b047972c55b5c233892b7e6acb21e</sign>'.
            '</order>'.
            '</messagebody>'.
            '</ordermessage>';
        $result = urlencode(iconv("utf-8", "gb2312", $params));

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '888',
            'orderId' => '000616',
            'orderCreateDate' => '20140424',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.payeasy.com',
            'amount' => '1000.00'
        ];

        $payEase = new PayEase();
        $payEase->setContainer($this->container);
        $payEase->setClient($this->client);
        $payEase->setResponse($response);
        $payEase->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');
        $payEase->setOptions($sourceData);
        $payEase->paymentTracking();
    }

   /**
    * 測試訂單查詢
    */
    public function testPaymentTracking()
    {
        $params = '<?xml version="1.0" encoding="gb2312"?>'.
            '<ordermessage>'.
            '<messagehead>'.
            '<status>0</status>'.
            '<statusdesc>Success</statusdesc>'.
            '<mid>888</mid>'.
            '<oid>20051215-888-000616</oid>'.
            '</messagehead>'.
            '<messagebody>'.
            '<order>'.
            '<orderindex>1</orderindex>'.
            '<oid>20051215-888-000616</oid>'.
            '<pmode>招商银行SZ</pmode>'.
            '<pstatus>1</pstatus>'.
            '<pstring>支付成功</pstring>'.
            '<amount>0.01</amount>'.
            '<moneytype>0</moneytype>'.
            '<isvirement>0</isvirement>'.
            '<sign>bf1b047972c55b5c233892b7e6acb21e</sign>'.
            '</order>'.
            '</messagebody>'.
            '</ordermessage>';
        $result = urlencode(iconv("utf-8", "gb2312", $params));

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '888',
            'orderId' => '000616',
            'orderCreateDate' => '20140424',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.payeasy.com',
            'amount' => '0.01'
        ];

        $payEase = new PayEase();
        $payEase->setContainer($this->container);
        $payEase->setClient($this->client);
        $payEase->setResponse($response);
        $payEase->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');
        $payEase->setOptions($sourceData);
        $payEase->paymentTracking();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒代入privateKey
     */
    public function testGetPaymentTrackingDataWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $payEase = new PayEase();
        $payEase->getPaymentTrackingData();
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

        $payEase = new PayEase();
        $payEase->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');
        $payEase->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒代入orderCreateDate
     */
    public function testGetPaymentTrackingDataWithoutOrderCreateDate()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $options = [
            'number' => '888',
            'orderId' => '201404150014262827',
            'orderCreateDate' => '',
        ];

        $payEase = new PayEase();
        $payEase->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');
        $payEase->setOptions($options);
        $payEase->getPaymentTrackingData();
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
            'number' => '888',
            'orderId' => '201404150014262827',
            'orderCreateDate' => '20140424',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $payEase = new PayEase();
        $payEase->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');
        $payEase->setOptions($options);
        $payEase->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'number' => '19822546',
            'orderId' => '201506100000002073',
            'orderCreateDate' => '20140424',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.beijing.com.cn',
        ];

        $payEase = new PayEase();
        $payEase->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');
        $payEase->setOptions($options);
        $trackingData = $payEase->getPaymentTrackingData();

        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('/merchant/order/order_ack_oid_list.jsp', $trackingData['path']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('payment.http.pay.beijing.com.cn', $trackingData['headers']['Host']);

        $this->assertEquals('19822546', $trackingData['form']['v_mid']);
        $this->assertEquals('20140424-19822546-201506100000002073', $trackingData['form']['v_oid']);
        $this->assertEquals('011f4b9931006b85eb33071b5af4442b', $trackingData['form']['v_mac']);
    }

    /**
     * 測試驗證訂單查詢沒代入privateKey
     */
    public function testPaymentTrackingVerifyWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $payEase = new PayEase();
        $payEase->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢缺少回傳參數messagebody
     */
    public function testPaymentTrackingVerifyWithoutMessagebody()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = '<?xml version="1.0" encoding="utf-8"?>' .
            '<ordermessage></ordermessage>';
        $sourceData = ['content' => $content];

        $payEase = new PayEase();
        $payEase->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');
        $payEase->setOptions($sourceData);
        $payEase->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢缺少回傳參數pstatus
     */
    public function testPaymentTrackingVerifyWithoutPstatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = '<?xml version="1.0" encoding="utf-8"?>' .
            '<ordermessage><messagebody><order></order>' .
            '</messagebody></ordermessage>';
        $sourceData = ['content' => $content];

        $payEase = new PayEase();
        $payEase->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');
        $payEase->setOptions($sourceData);
        $payEase->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢訂單未支付
     */
    public function testPaymentTrackingVerifyUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $content = '<?xml version="1.0" encoding="utf-8"?>' .
            '<ordermessage><messagebody><order><pstatus>2</pstatus></order>' .
            '</messagebody></ordermessage>';
        $sourceData = ['content' => $content];

        $payEase = new PayEase();
        $payEase->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');
        $payEase->setOptions($sourceData);
        $payEase->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢支付失敗
     */
    public function testPaymentTrackingVerifyPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $content = '<?xml version="1.0" encoding="utf-8"?>' .
            '<ordermessage><messagebody><order><pstatus>999</pstatus></order>' .
            '</messagebody></ordermessage>';
        $sourceData = ['content' => $content];

        $payEase = new PayEase();
        $payEase->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');
        $payEase->setOptions($sourceData);
        $payEase->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢帶入金額不正確
     */
    public function testPaymentTrackingVerifyWithAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $content = '<?xml version="1.0" encoding="utf-8"?>' .
            '<ordermessage>' .
            '<messagehead>' .
            '<status>0</status>' .
            '<statusdesc>Success</statusdesc>' .
            '<mid>888</mid>' .
            '<oid>20051215-888-000616</oid>' .
            '</messagehead>' .
            '<messagebody>' .
            '<order>' .
            '<orderindex>1</orderindex>' .
            '<oid>20051215-888-000616</oid>' .
            '<pmode>招商银行SZ</pmode>' .
            '<pstatus>1</pstatus>' .
            '<pstring>支付成功</pstring>' .
            '<amount>0.01</amount>' .
            '<moneytype>0</moneytype>' .
            '<isvirement>0</isvirement>' .
            '<sign>bf1b047972c55b5c233892b7e6acb21e</sign>' .
            '</order>' .
            '</messagebody>' .
            '</ordermessage>';
        $sourceData = [
            'content' => $content,
            'amount' => '1000.00'
        ];

        $payEase = new PayEase();
        $payEase->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');
        $payEase->setOptions($sourceData);
        $payEase->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $content = '<?xml version="1.0" encoding="utf-8"?>' .
            '<ordermessage>' .
            '<messagehead>' .
            '<status>0</status>' .
            '<statusdesc>Success</statusdesc>' .
            '<mid>888</mid>' .
            '<oid>20051215-888-000616</oid>' .
            '</messagehead>' .
            '<messagebody>' .
            '<order>' .
            '<orderindex>1</orderindex>' .
            '<oid>20051215-888-000616</oid>' .
            '<pmode>招商银行SZ</pmode>' .
            '<pstatus>1</pstatus>' .
            '<pstring>支付成功</pstring>' .
            '<amount>0.01</amount>' .
            '<moneytype>0</moneytype>' .
            '<isvirement>0</isvirement>' .
            '<sign>bf1b047972c55b5c233892b7e6acb21e</sign>' .
            '</order>' .
            '</messagebody>' .
            '</ordermessage>';
        $sourceData = [
            'content' => $content,
            'amount' => '0.01'
        ];

        $payEase = new PayEase();
        $payEase->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');
        $payEase->setOptions($sourceData);
        $payEase->paymentTrackingVerify();
    }
}
