<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\IPS;
use Buzz\Message\Response;

class IPSTest extends DurianTestCase
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

        $ips = new IPS();
        $ips->getVerifyData();
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

        $ips = new IPS();

        $privateKey = 'GDgLwwdK270Qj1w4xho8lyTpRQZV9Jm5x4NwWOTThUa4'.
            'fMhEBK9jOXFrKRT6xhlJuU2FEa89ov0ryyjfJuuPkcGz'.
            'O5CeVx5ZIrkkt1aBlZV36ySvHOMcNv8rncRiy3DQ';

        $ips->setPrivateKey($privateKey);

        $sourceData = ['number' => ''];

        $ips->setOptions($sourceData);
        $ips->getVerifyData();
    }

    /**
     * 測試加密時帶入的paymentVendorId沒有對應的情況(Bankco會是空字串)
     */
    public function testSetEncodeSourceNoPaymentVendorId()
    {
        $ips = new IPS();

        $privateKey = 'GDgLwwdK270Qj1w4xho8lyTpRQZV9Jm5x4NwWOTThUa4'.
            'fMhEBK9jOXFrKRT6xhlJuU2FEa89ov0ryyjfJuuPkcGz'.
            'O5CeVx5ZIrkkt1aBlZV36ySvHOMcNv8rncRiy3DQ';

        $ips->setPrivateKey($privateKey);

        $sourceData = [
            'number' => '000015',
            'orderId' => '201404150000123458',
            'amount' => '0.02',
            'orderCreateDate' => '2014-04-15 21:34:21',
            'notify_url' => 'http://59.126.84.197:3030/return.php?pay_system=32767&hallid=6',
            'username' => 'php1test',
            'paymentVendorId' => '1314',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $ips->setOptions($sourceData);
        $encodeData = $ips->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['Mer_code']);
        $this->assertEquals($sourceData['orderId'], $encodeData['Billno']);
        $this->assertEquals($sourceData['amount'], $encodeData['Amount']);
        $this->assertEquals('20140415', $encodeData['Date']);
        $this->assertEquals($sourceData['username'], $encodeData['Attach']);
        $this->assertSame('', $encodeData['Bankco']);
        $this->assertEquals('c116c3bd090551e62b72931bff80e719', $encodeData['SignMD5']);
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $privateKey = 'GDgLwwdK270Qj1w4xho8lyTpRQZV9Jm5x4NwWOTThUa4'.
            'fMhEBK9jOXFrKRT6xhlJuU2FEa89ov0ryyjfJuuPkcGz'.
            'O5CeVx5ZIrkkt1aBlZV36ySvHOMcNv8rncRiy3DQ';

        $sourceData = [
            'number' => '000015',
            'orderId' => '201404150000123456',
            'amount' => '0.02',
            'orderCreateDate' => '2014-04-15 21:34:21',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'username' => 'php1test',
            'paymentVendorId' => '1', //1 => 00004
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $ips = new IPS();
        $ips->setPrivateKey($privateKey);
        $ips->setOptions($sourceData);
        $encodeData = $ips->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals($sourceData['number'], $encodeData['Mer_code']);
        $this->assertEquals($sourceData['orderId'], $encodeData['Billno']);
        $this->assertEquals($sourceData['amount'], $encodeData['Amount']);
        $this->assertEquals('20140415', $encodeData['Date']);
        $this->assertEquals($notifyUrl, $encodeData['ServerUrl']);
        $this->assertEquals($sourceData['username'], $encodeData['Attach']);
        $this->assertEquals('00004', $encodeData['Bankco']);
        $this->assertEquals('b74303526ef8a0a19ad7ffb4d832d468', $encodeData['SignMD5']);
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

        $ips = new IPS();

        $ips->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證未指定返回參數
     */
    public function testVerifyWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $ips = new IPS();

        $privateKey = 'GDgLwwdK270Qj1w4xho8lyTpRQZV9Jm5x4NwWOTThUa4'.
            'fMhEBK9jOXFrKRT6xhlJuU2FEa89ov0ryyjfJuuPkcGz'.
            'O5CeVx5ZIrkkt1aBlZV36ySvHOMcNv8rncRiy3DQ';

        $ips->setPrivateKey($privateKey);

        $sourceData = [
            'billno'        => '201404150000123456',
            'mercode'       => '000015',
            'amount'        => '0.02',
            'date'          => '20140415',
            'msg'           => 'sucess',
            'ipsbillno'     => 'NT2014041570874082',
            'retencodetype' => '17',
            'attach'        => 'abcd',
            'Currency_type' => 'RMB',
            'signature'     => 'C116C3BD090551E62B72931BFF80E719',
            'ipsbanktime'   => '20140415194821',
            'bankbillno'    => '301100135869'
        ];

        $ips->setOptions($sourceData);
        $ips->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證缺少signature
     */
    public function testVerifyWithoutSignature()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $ips = new IPS();

        $privateKey = 'GDgLwwdK270Qj1w4xho8lyTpRQZV9Jm5x4NwWOTThUa4'.
            'fMhEBK9jOXFrKRT6xhlJuU2FEa89ov0ryyjfJuuPkcGz'.
            'O5CeVx5ZIrkkt1aBlZV36ySvHOMcNv8rncRiy3DQ';

        $ips->setPrivateKey($privateKey);

        $sourceData = [
            'billno'        => '201404150000123456',
            'mercode'       => '000015',
            'amount'        => '0.02',
            'date'          => '20140415',
            'succ'          => 'Y',
            'msg'           => 'sucess',
            'ipsbillno'     => 'NT2014041570874082',
            'retencodetype' => '17',
            'attach'        => 'abcd',
            'Currency_type' => 'RMB',
            'ipsbanktime'   => '20140415194821',
            'bankbillno'    => '301100135869'
        ];

        $ips->setOptions($sourceData);
        $ips->verifyOrderPayment([]);
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

        $ips = new IPS();

        $privateKey = 'GDgLwwdK270Qj1w4xho8lyTpRQZV9Jm5x4NwWOTThUa4'.
            'fMhEBK9jOXFrKRT6xhlJuU2FEa89ov0ryyjfJuuPkcGz'.
            'O5CeVx5ZIrkkt1aBlZV36ySvHOMcNv8rncRiy3DQ';

        $ips->setPrivateKey($privateKey);

        $sourceData = [
            'billno'        => '201404150000123456',
            'mercode'       => '000015',
            'amount'        => '0.02',
            'date'          => '20140415',
            'succ'          => 'Y',
            'msg'           => 'sucess',
            'ipsbillno'     => 'NT2014041570874082',
            'retencodetype' => '17',
            'attach'        => 'abcd',
            'Currency_type' => 'RMB',
            'signature'     => 'C116C3BD090551E62B72931BFF80E719',
            'ipsbanktime'   => '20140415194821',
            'bankbillno'    => '301100135869'
        ];

        $ips->setOptions($sourceData);
        $ips->verifyOrderPayment([]);
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

        $ips = new IPS();

        $privateKey = 'GDgLwwdK270Qj1w4xho8lyTpRQZV9Jm5x4NwWOTThUa4'.
            'fMhEBK9jOXFrKRT6xhlJuU2FEa89ov0ryyjfJuuPkcGz'.
            'O5CeVx5ZIrkkt1aBlZV36ySvHOMcNv8rncRiy3DQ';

        $ips->setPrivateKey($privateKey);

        $sourceData = [
            'billno'        => '201404150000123457',
            'mercode'       => '000015',
            'amount'        => '0.02',
            'date'          => '20140415',
            'succ'          => 'N',
            'msg'           => 'fail',
            'ipsbillno'     => 'NT2014041570874086',
            'retencodetype' => '17',
            'attach'        => 'abcd',
            'Currency_type' => 'RMB',
            'signature'     => 'f8f0cf36f447d9bbaaabd32486dddfd3',
            'ipsbanktime'   => '20140415195037',
            'bankbillno'    => '301100135871'
        ];

        $ips->setOptions($sourceData);
        $ips->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $ips = new IPS();

        $privateKey = 'GDgLwwdK270Qj1w4xho8lyTpRQZV9Jm5x4NwWOTThUa4'.
            'fMhEBK9jOXFrKRT6xhlJuU2FEa89ov0ryyjfJuuPkcGz'.
            'O5CeVx5ZIrkkt1aBlZV36ySvHOMcNv8rncRiy3DQ';

        $ips->setPrivateKey($privateKey);

        $sourceData = [
            'billno'        => '201404150000123456',
            'mercode'       => '000015',
            'amount'        => '0.02',
            'date'          => '20140415',
            'succ'          => 'Y',
            'msg'           => 'sucess',
            'ipsbillno'     => 'NT2014041570874082',
            'retencodetype' => '17',
            'attach'        => 'abcd',
            'Currency_type' => 'RMB',
            'signature'     => '4a3b9c8627fe7125bfd69784705d4a3c',
            'ipsbanktime'   => '20140415194821',
            'bankbillno'    => '301100135869'
        ];

        $entry = ['id' => '20140102030405006'];

        $ips->setOptions($sourceData);
        $ips->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $ips = new IPS();

        $privateKey = 'GDgLwwdK270Qj1w4xho8lyTpRQZV9Jm5x4NwWOTThUa4'.
            'fMhEBK9jOXFrKRT6xhlJuU2FEa89ov0ryyjfJuuPkcGz'.
            'O5CeVx5ZIrkkt1aBlZV36ySvHOMcNv8rncRiy3DQ';

        $ips->setPrivateKey($privateKey);

        $sourceData = [
            'billno'        => '201404150000123456',
            'mercode'       => '000015',
            'amount'        => '0.02',
            'date'          => '20140415',
            'succ'          => 'Y',
            'msg'           => 'sucess',
            'ipsbillno'     => 'NT2014041570874082',
            'retencodetype' => '17',
            'attach'        => 'abcd',
            'Currency_type' => 'RMB',
            'signature'     => '4a3b9c8627fe7125bfd69784705d4a3c',
            'ipsbanktime'   => '20140415194821',
            'bankbillno'    => '301100135869'
        ];

        $entry = [
            'id' => '201404150000123456',
            'amount' => '0.1000'
        ];

        $ips->setOptions($sourceData);
        $ips->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $ips = new IPS();

        $privateKey = 'GDgLwwdK270Qj1w4xho8lyTpRQZV9Jm5x4NwWOTThUa4'.
            'fMhEBK9jOXFrKRT6xhlJuU2FEa89ov0ryyjfJuuPkcGz'.
            'O5CeVx5ZIrkkt1aBlZV36ySvHOMcNv8rncRiy3DQ';

        $ips->setPrivateKey($privateKey);

        $sourceData = [
            'billno'        => '201404150000123456',
            'mercode'       => '000015',
            'amount'        => '0.02',
            'date'          => '20140415',
            'succ'          => 'Y',
            'msg'           => 'sucess',
            'ipsbillno'     => 'NT2014041570874082',
            'retencodetype' => '17',
            'attach'        => 'abcd',
            'Currency_type' => 'RMB',
            'signature'     => '4a3b9c8627fe7125bfd69784705d4a3c',
            'ipsbanktime'   => '20140415194821',
            'bankbillno'    => '301100135869'
        ];

        $entry = [
            'id' => '201404150000123456',
            'amount' => '0.0200'
        ];

        $ips->setOptions($sourceData);
        $ips->verifyOrderPayment($entry);

        $this->assertEquals('ipscheckok', $ips->getMsg());
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

        $ips = new IPS();
        $ips->paymentTracking();
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

        $ips = new IPS();
        $ips->setPrivateKey('1234');
        $ips->paymentTracking();
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
            'number' => '015187',
            'orderId' => '201404150014262827',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $ips = new IPS();
        $ips->setPrivateKey('1234');
        $ips->setOptions($sourceData);
        $ips->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數ErrCode
     */
    public function testPaymentTrackingResultWithoutErrCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = <<<EOT
<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://Webservice.ips.com.cn/Sinopay/Standard/">
</OrderMsg>
EOT;

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '015187',
            'orderId' => '201404150014262827',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ips.com'
        ];

        $ips = new IPS();
        $ips->setContainer($this->container);
        $ips->setClient($this->client);
        $ips->setResponse($response);
        $ips->setPrivateKey($privateKey);
        $ips->setOptions($sourceData);
        $ips->paymentTracking();
    }

    /**
     * 測試訂單查詢結果為支付平台商戶不存在
     */
    public function testTrackingReturnPaymentGatewayMerchantNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Merchant is not exist',
            180086
        );

        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = <<<EOT
<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://Webservice.ips.com.cn/Sinopay/Standard/">
<ErrCode>1001</ErrCode>
</OrderMsg>
EOT;

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '015187',
            'orderId' => '201404150014262827',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ips.com'
        ];

        $ips = new IPS();
        $ips->setContainer($this->container);
        $ips->setClient($this->client);
        $ips->setResponse($response);
        $ips->setPrivateKey($privateKey);
        $ips->setOptions($sourceData);
        $ips->paymentTracking();
    }

    /**
     * 測試訂單查詢結果為支付平台商戶憑證不存在
     */
    public function testTrackingReturnPaymentGatewayMerchantCertificateNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Merchant Certificate is not exist',
            180087
        );

        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = <<<EOT
<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://Webservice.ips.com.cn/Sinopay/Standard/">
<ErrCode>1002</ErrCode>
</OrderMsg>
EOT;

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '015187',
            'orderId' => '201404150014262827',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ips.com'
        ];

        $ips = new IPS();
        $ips->setContainer($this->container);
        $ips->setClient($this->client);
        $ips->setResponse($response);
        $ips->setPrivateKey($privateKey);
        $ips->setOptions($sourceData);
        $ips->paymentTracking();
    }

    /**
     * 測試訂單查詢結果支付平台驗證簽名錯誤
     */
    public function testTrackingReturnPaymentGatewaySignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Merchant sign error',
            180127
        );

        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = <<<EOT
<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://Webservice.ips.com.cn/Sinopay/Standard/">
<ErrCode>1003</ErrCode>
</OrderMsg>
EOT;

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '015187',
            'orderId' => '201404150014262827',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ips.com'
        ];

        $ips = new IPS();
        $ips->setContainer($this->container);
        $ips->setClient($this->client);
        $ips->setResponse($response);
        $ips->setPrivateKey($privateKey);
        $ips->setOptions($sourceData);
        $ips->paymentTracking();
    }

    /**
     * 測試訂單查詢結果支付平台時間格式不合法
     */
    public function testTrackingReturnPaymentGatewayInvalidOrderDate()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Invalid Order date',
            180131
        );

        $result = <<<EOT
<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://Webservice.ips.com.cn/Sinopay/Standard/">
<ErrCode>1004</ErrCode>
</OrderMsg>
EOT;

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '015187',
            'orderId' => '201404150014262827',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ips.com'
        ];

        $ips = new IPS();
        $ips->setContainer($this->container);
        $ips->setClient($this->client);
        $ips->setResponse($response);
        $ips->setPrivateKey('8290229213342972300839197914370406523196538757994');
        $ips->setOptions($sourceData);
        $ips->paymentTracking();
    }

    /**
     * 測試訂單查詢結果支付平台起始時間大於結束時間
     */
    public function testTrackingReturnPaymentGatewayBeginTimeLargeThanEngTime()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Begin time large than End time',
            180132
        );

        $result = <<<EOT
<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://Webservice.ips.com.cn/Sinopay/Standard/">
<ErrCode>1005</ErrCode>
</OrderMsg>
EOT;

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '015187',
            'orderId' => '201404150014262827',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ips.com'
        ];

        $ips = new IPS();
        $ips->setContainer($this->container);
        $ips->setClient($this->client);
        $ips->setResponse($response);
        $ips->setPrivateKey('8290229213342972300839197914370406523196538757994');
        $ips->setOptions($sourceData);
        $ips->paymentTracking();
    }

    /**
     * 測試訂單查詢結果支付平台時間不存在
     */
    public function testTrackingReturnPaymentGatewayOrderDateNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, No date specified',
            180133
        );

        $result = <<<EOT
<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://Webservice.ips.com.cn/Sinopay/Standard/">
<ErrCode>1006</ErrCode>
</OrderMsg>
EOT;

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '015187',
            'orderId' => '201404150014262827',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ips.com'
        ];

        $ips = new IPS();
        $ips->setContainer($this->container);
        $ips->setClient($this->client);
        $ips->setResponse($response);
        $ips->setPrivateKey('8290229213342972300839197914370406523196538757994');
        $ips->setOptions($sourceData);
        $ips->paymentTracking();
    }

    /**
     * 測試訂單查詢結果為請求服務失敗
     */
    public function testTrackingReturnConnectionError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Connection error, please try again later or contact customer service',
            180077
        );

        $result = <<<EOT
<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://Webservice.ips.com.cn/Sinopay/Standard/">
<ErrCode>1007</ErrCode>
</OrderMsg>
EOT;

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '015187',
            'orderId' => '201404150014262827',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ips.com'
        ];

        $ips = new IPS();
        $ips->setContainer($this->container);
        $ips->setClient($this->client);
        $ips->setResponse($response);
        $ips->setPrivateKey('8290229213342972300839197914370406523196538757994');
        $ips->setOptions($sourceData);
        $ips->paymentTracking();
    }

    /**
     * 測試訂單查詢結果回傳訂單不存在
     */
    public function testTrackingReturnOrderNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );

        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = <<<EOT
<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://Webservice.ips.com.cn/Sinopay/Standard/">
<ErrCode>1008</ErrCode>
</OrderMsg>
EOT;

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '015187',
            'orderId' => '201404150014262827',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ips.com'
        ];

        $ips = new IPS();
        $ips->setContainer($this->container);
        $ips->setClient($this->client);
        $ips->setResponse($response);
        $ips->setPrivateKey($privateKey);
        $ips->setOptions($sourceData);
        $ips->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單id不合法
     */
    public function testPaymentTrackingResultOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = <<<EOT
<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://Webservice.ips.com.cn/Sinopay/Standard/">
<ErrCode>1009</ErrCode>
</OrderMsg>
EOT;

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '015187',
            'orderId' => '201404150014262827',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ips.com'
        ];

        $ips = new IPS();
        $ips->setContainer($this->container);
        $ips->setClient($this->client);
        $ips->setResponse($response);
        $ips->setPrivateKey($privateKey);
        $ips->setOptions($sourceData);
        $ips->paymentTracking();
    }

    /**
     * 測試訂單查詢結果支付平台起始訂單號大於結束訂單號
     */
    public function testTrackingReturnPaymentGatewayStartNoLargeThanEndNo()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, StartNo large than EndNo',
            180134
        );

        $result = <<<EOT
<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://Webservice.ips.com.cn/Sinopay/Standard/">
<ErrCode>1010</ErrCode>
</OrderMsg>
EOT;

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '015187',
            'orderId' => '201404150014262827',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ips.com'
        ];

        $ips = new IPS();
        $ips->setContainer($this->container);
        $ips->setClient($this->client);
        $ips->setResponse($response);
        $ips->setPrivateKey('8290229213342972300839197914370406523196538757994');
        $ips->setOptions($sourceData);
        $ips->paymentTracking();
    }

    /**
     * 測試訂單查詢結果為支付平台商號合約過期
     */
    public function testTracingReturnPaymentGatewayMerchantExpired()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Merchant has been expired',
            180126
        );

        $result = <<<EOT
<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://Webservice.ips.com.cn/Sinopay/Standard/">
<ErrCode>2000</ErrCode>
</OrderMsg>
EOT;

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '015187',
            'orderId' => '201404150014262827',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ips.com'
        ];

        $ips = new IPS();
        $ips->setContainer($this->container);
        $ips->setClient($this->client);
        $ips->setResponse($response);
        $ips->setPrivateKey('8290229213342972300839197914370406523196538757994');
        $ips->setOptions($sourceData);
        $ips->paymentTracking();
    }

    /**
     * 測試訂單查詢失敗
     */
    public function testTrackingReturnPaymentTrackingFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment tracking failed',
            180081
        );

        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = <<<EOT
<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://Webservice.ips.com.cn/Sinopay/Standard/">
<ErrCode>9999</ErrCode>
</OrderMsg>
EOT;

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '015187',
            'orderId' => '201404150014262827',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ips.com'
        ];

        $ips = new IPS();
        $ips->setContainer($this->container);
        $ips->setClient($this->client);
        $ips->setResponse($response);
        $ips->setPrivateKey($privateKey);
        $ips->setOptions($sourceData);
        $ips->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數OrderRecords
     */
    public function testPaymentTrackingResultWithoutOrderRecords()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = <<<EOT
<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://Webservice.ips.com.cn/Sinopay/Standard/">
<ErrCode>0000</ErrCode>
</OrderMsg>
EOT;

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '015187',
            'orderId' => '201404150014262827',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ips.com'
        ];

        $ips = new IPS();
        $ips->setContainer($this->container);
        $ips->setClient($this->client);
        $ips->setResponse($response);
        $ips->setPrivateKey($privateKey);
        $ips->setOptions($sourceData);
        $ips->paymentTracking();
    }

    /**
     * 測試訂單查詢結果未指定返回參數
     */
    public function testPaymentTrackingResultWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = <<<EOT
<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://Webservice.ips.com.cn/Sinopay/Standard/">
<ErrCode>0000</ErrCode>
<OrderRecords>
<OrderRecord>
</OrderRecord>
</OrderRecords>
</OrderMsg>
EOT;

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '015187',
            'orderId' => '201404150014262827',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ips.com'
        ];

        $ips = new IPS();
        $ips->setContainer($this->container);
        $ips->setClient($this->client);
        $ips->setResponse($response);
        $ips->setPrivateKey($privateKey);
        $ips->setOptions($sourceData);
        $ips->paymentTracking();
    }

    /**
     * 測試訂單查詢結果Flag不為1支付失敗
     */
    public function testTrackingReturnFlagError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = <<<EOT
<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://Webservice.ips.com.cn/Sinopay/Standard/">
<ErrCode>0000</ErrCode>
<OrderRecords>
<OrderRecord>
<OrderNo>201404150014262827</OrderNo>
<IPSOrderNo>NT2014041526107993</IPSOrderNo>
<Trd_Code>NT</Trd_Code>
<Cr_Code>RMB</Cr_Code>
<Amount>500</Amount>
<MerchantOrderTime>20140415</MerchantOrderTime>
<IPSOrderTime>20140415171112</IPSOrderTime>
<Flag>2</Flag>
</OrderRecord>
</OrderRecords>
</OrderMsg>
EOT;

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '015187',
            'orderId' => '201404150014262827',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ips.com'
        ];

        $ips = new IPS();
        $ips->setContainer($this->container);
        $ips->setClient($this->client);
        $ips->setResponse($response);
        $ips->setPrivateKey($privateKey);
        $ips->setOptions($sourceData);
        $ips->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數Sign
     */
    public function testPaymentTrackingResultWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = <<<EOT
<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://Webservice.ips.com.cn/Sinopay/Standard/">
<ErrCode>0000</ErrCode>
<OrderRecords>
<OrderRecord>
<OrderNo>201404150014262827</OrderNo>
<IPSOrderNo>NT2014041526107993</IPSOrderNo>
<Trd_Code>NT</Trd_Code>
<Cr_Code>RMB</Cr_Code>
<Amount>100</Amount>
<MerchantOrderTime>20140415</MerchantOrderTime>
<IPSOrderTime>20140415171112</IPSOrderTime>
<Flag>1</Flag>
</OrderRecord>
</OrderRecords>
</OrderMsg>
EOT;

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '015187',
            'orderId' => '201404150014262827',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ips.com'
        ];

        $ips = new IPS();
        $ips->setContainer($this->container);
        $ips->setClient($this->client);
        $ips->setResponse($response);
        $ips->setPrivateKey($privateKey);
        $ips->setOptions($sourceData);
        $ips->paymentTracking();
    }

    /**
     * 測試訂單查詢結果簽名驗證錯誤
     */
    public function testTrackingReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = <<<EOT
<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://Webservice.ips.com.cn/Sinopay/Standard/">
<ErrCode>0000</ErrCode>
<OrderRecords>
<OrderRecord>
<OrderNo>201404150014262827</OrderNo>
<IPSOrderNo>NT2014041526107993</IPSOrderNo>
<Trd_Code>NT</Trd_Code>
<Cr_Code>RMB</Cr_Code>
<Amount>500</Amount>
<MerchantOrderTime>20140415</MerchantOrderTime>
<IPSOrderTime>20140415171112</IPSOrderTime>
<Flag>1</Flag>
<Sign>97df01efaf4997db61640511b4971b77</Sign>
</OrderRecord>
</OrderRecords>
</OrderMsg>
EOT;

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '015187',
            'orderId' => '201404150014262827',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ips.com'
        ];

        $ips = new IPS();
        $ips->setContainer($this->container);
        $ips->setClient($this->client);
        $ips->setResponse($response);
        $ips->setPrivateKey($privateKey);
        $ips->setOptions($sourceData);
        $ips->paymentTracking();
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

        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = <<<EOT
<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://Webservice.ips.com.cn/Sinopay/Standard/">
<ErrCode>0000</ErrCode>
<OrderRecords>
<OrderRecord>
<OrderNo>201404150014262827</OrderNo>
<IPSOrderNo>NT2014041526107993</IPSOrderNo>
<Trd_Code>NT</Trd_Code>
<Cr_Code>RMB</Cr_Code>
<Amount>100</Amount>
<MerchantOrderTime>20140415</MerchantOrderTime>
<IPSOrderTime>20140415171112</IPSOrderTime>
<Flag>1</Flag>
<Sign>97df01efaf4997db61640511b4971b77</Sign>
</OrderRecord>
</OrderRecords>
</OrderMsg>
EOT;

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '015187',
            'orderId' => '201404150014262827',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ips.com',
            'amount' => '500.00'
        ];

        $ips = new IPS();
        $ips->setContainer($this->container);
        $ips->setClient($this->client);
        $ips->setResponse($response);
        $ips->setPrivateKey($privateKey);
        $ips->setOptions($sourceData);
        $ips->paymentTracking();
    }

   /**
    * 測試訂單查詢
    */
    public function testPaymentTracking()
    {
        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = <<<EOT
<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://Webservice.ips.com.cn/Sinopay/Standard/">
<ErrCode>0000</ErrCode>
<OrderRecords>
<OrderRecord>
<OrderNo>201404150014262827</OrderNo>
<IPSOrderNo>NT2014041526107993</IPSOrderNo>
<Trd_Code>NT</Trd_Code>
<Cr_Code>RMB</Cr_Code>
<Amount>100</Amount>
<MerchantOrderTime>20140415</MerchantOrderTime>
<IPSOrderTime>20140415171112</IPSOrderTime>
<Flag>1</Flag>
<Sign>97df01efaf4997db61640511b4971b77</Sign>
</OrderRecord>
</OrderRecords>
</OrderMsg>
EOT;

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '015187',
            'orderId' => '201404150014262827',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ips.com',
            'amount' => '100.00'
        ];

        $ips = new IPS();
        $ips->setContainer($this->container);
        $ips->setClient($this->client);
        $ips->setResponse($response);
        $ips->setPrivateKey($privateKey);
        $ips->setOptions($sourceData);
        $ips->paymentTracking();
    }

    /**
     * 測試批次訂單查詢沒代入privateKey
     */
    public function testBatchTrackingWithoutPrivateKey()
    {
        $ips = new IPS();
        $output = $ips->batchTracking();

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(180142, $output['code']);
        $this->assertEquals('No privateKey specified', $output['msg']);
    }

    /**
     * 測試批次訂單查詢未指定查詢參數
     */
    public function testBatchTrackingWithNoTrackingParameterSpecified()
    {
        $ips = new IPS();
        $ips->setPrivateKey('1234');
        $output = $ips->batchTracking();

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(180138, $output['code']);
        $this->assertEquals('No tracking parameter specified', $output['msg']);
    }

    /**
     * 測試批次訂單查詢沒代入verifyUrl
     */
    public function testBatchTrackingWithoutVerifyUrl()
    {
        $sourceData = [
            'number' => '015187',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $ips = new IPS();
        $ips->setPrivateKey('1234');
        $ips->setOptions($sourceData);
        $output = $ips->batchTracking();

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(180140, $output['code']);
        $this->assertEquals('No verify_url specified', $output['msg']);
    }

    /**
     * 測試批次訂單查詢結果缺少回傳參數ErrCode
     */
    public function testBatchTrackingResultWithoutErrCode()
    {
        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = <<<EOT
<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://Webservice.ips.com.cn/Sinopay/Standard/">
</OrderMsg>
EOT;

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $entries = [
            ['entry_id' => 20160204170000],
            ['entry_id' => 20160204180000],
        ];

        $sourceData = [
            'number' => '015187',
            'entries' => $entries,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ips.com'
        ];

        $ips = new IPS();
        $ips->setContainer($this->container);
        $ips->setClient($this->client);
        $ips->setResponse($response);
        $ips->setPrivateKey($privateKey);
        $ips->setOptions($sourceData);
        $output = $ips->batchTracking();

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(180139, $output['code']);
        $this->assertEquals('No tracking return parameter specified', $output['msg']);
    }

    /**
     * 測試批次訂單查詢結果為支付平台商戶不存在
     */
    public function testBatchTrackingReturnPaymentGatewayMerchantNotExist()
    {
        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = <<<EOT
<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://Webservice.ips.com.cn/Sinopay/Standard/">
<ErrCode>1001</ErrCode>
</OrderMsg>
EOT;

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $entries = [
            ['entry_id' => 20160204170000],
            ['entry_id' => 20160204180000],
        ];

        $sourceData = [
            'number' => '015187',
            'entries' => $entries,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ips.com'
        ];

        $ips = new IPS();
        $ips->setContainer($this->container);
        $ips->setClient($this->client);
        $ips->setResponse($response);
        $ips->setPrivateKey($privateKey);
        $ips->setOptions($sourceData);
        $output = $ips->batchTracking();

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(180086, $output['code']);
        $this->assertEquals('PaymentGateway error, Merchant is not exist', $output['msg']);
    }

    /**
     * 測試批次訂單查詢結果為支付平台商戶憑證不存在
     */
    public function testBatchTrackingReturnPaymentGatewayMerchantCertificateNotExist()
    {
        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = <<<EOT
<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://Webservice.ips.com.cn/Sinopay/Standard/">
<ErrCode>1002</ErrCode>
</OrderMsg>
EOT;

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $entries = [
            ['entry_id' => 20160204170000],
            ['entry_id' => 20160204180000],
        ];

        $sourceData = [
            'number' => '015187',
            'entries' => $entries,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ips.com'
        ];

        $ips = new IPS();
        $ips->setContainer($this->container);
        $ips->setClient($this->client);
        $ips->setResponse($response);
        $ips->setPrivateKey($privateKey);
        $ips->setOptions($sourceData);
        $output = $ips->batchTracking();

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(180087, $output['code']);
        $this->assertEquals('PaymentGateway error, Merchant Certificate is not exist', $output['msg']);
    }

    /**
     * 測試批次訂單查詢結果為支付平台驗證簽名錯誤
     */
    public function testBatchTrackingReturnPaymentGatewaySignatureVerificationFailed()
    {
        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = <<<EOT
<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://Webservice.ips.com.cn/Sinopay/Standard/">
<ErrCode>1003</ErrCode>
</OrderMsg>
EOT;

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $entries = [
            ['entry_id' => 20160204170000],
            ['entry_id' => 20160204180000],
        ];

        $sourceData = [
            'number' => '015187',
            'entries' => $entries,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ips.com'
        ];

        $ips = new IPS();
        $ips->setContainer($this->container);
        $ips->setClient($this->client);
        $ips->setResponse($response);
        $ips->setPrivateKey($privateKey);
        $ips->setOptions($sourceData);
        $output = $ips->batchTracking();

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(180127, $output['code']);
        $this->assertEquals('PaymentGateway error, Merchant sign error', $output['msg']);
    }

    /**
     * 測試批次訂單查詢結果為支付平台時間格式不合法
     */
    public function testBatchTrackingReturnPaymentGatewayInvalidOrderDate()
    {
        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = <<<EOT
<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://Webservice.ips.com.cn/Sinopay/Standard/">
<ErrCode>1004</ErrCode>
</OrderMsg>
EOT;

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $entries = [
            ['entry_id' => 20160204170000],
            ['entry_id' => 20160204180000],
        ];

        $sourceData = [
            'number' => '015187',
            'entries' => $entries,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ips.com'
        ];

        $ips = new IPS();
        $ips->setContainer($this->container);
        $ips->setClient($this->client);
        $ips->setResponse($response);
        $ips->setPrivateKey($privateKey);
        $ips->setOptions($sourceData);
        $output = $ips->batchTracking();

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(180131, $output['code']);
        $this->assertEquals('PaymentGateway error, Invalid Order date', $output['msg']);
    }

    /**
     * 測試批次訂單查詢結果為支付平台起始時間大於結束時間
     */
    public function testBatchTrackingReturnPaymentGatewayBeginTimeLargeThanEngTime()
    {
        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = <<<EOT
<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://Webservice.ips.com.cn/Sinopay/Standard/">
<ErrCode>1005</ErrCode>
</OrderMsg>
EOT;

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $entries = [
            ['entry_id' => 20160204170000],
            ['entry_id' => 20160204180000],
        ];

        $sourceData = [
            'number' => '015187',
            'entries' => $entries,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ips.com'
        ];

        $ips = new IPS();
        $ips->setContainer($this->container);
        $ips->setClient($this->client);
        $ips->setResponse($response);
        $ips->setPrivateKey($privateKey);
        $ips->setOptions($sourceData);
        $output = $ips->batchTracking();

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(180132, $output['code']);
        $this->assertEquals('PaymentGateway error, Begin time large than End time', $output['msg']);
    }

    /**
     * 測試批次訂單查詢結果為支付平台時間不存在
     */
    public function testBatchTrackingReturnPaymentGatewayOrderDateNotExist()
    {
        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = <<<EOT
<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://Webservice.ips.com.cn/Sinopay/Standard/">
<ErrCode>1006</ErrCode>
</OrderMsg>
EOT;

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $entries = [
            ['entry_id' => 20160204170000],
            ['entry_id' => 20160204180000],
        ];

        $sourceData = [
            'number' => '015187',
            'entries' => $entries,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ips.com'
        ];

        $ips = new IPS();
        $ips->setContainer($this->container);
        $ips->setClient($this->client);
        $ips->setResponse($response);
        $ips->setPrivateKey($privateKey);
        $ips->setOptions($sourceData);
        $output = $ips->batchTracking();

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(180133, $output['code']);
        $this->assertEquals('PaymentGateway error, No date specified', $output['msg']);
    }

    /**
     * 測試批次訂單查詢結果為請求服務失敗
     */
    public function testBatchTrackingReturnConnectionError()
    {
        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = <<<EOT
<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://Webservice.ips.com.cn/Sinopay/Standard/">
<ErrCode>1007</ErrCode>
</OrderMsg>
EOT;

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $entries = [
            ['entry_id' => 20160204170000],
            ['entry_id' => 20160204180000],
        ];

        $sourceData = [
            'number' => '015187',
            'entries' => $entries,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ips.com'
        ];

        $ips = new IPS();
        $ips->setContainer($this->container);
        $ips->setClient($this->client);
        $ips->setResponse($response);
        $ips->setPrivateKey($privateKey);
        $ips->setOptions($sourceData);
        $output = $ips->batchTracking();

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(180077, $output['code']);
        $this->assertEquals('Connection error, please try again later or contact customer service', $output['msg']);
    }

    /**
     * 測試批次訂單查詢結果回傳訂單不存在
     */
    public function testBatchTrackingReturnOrderNotExist()
    {
        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = <<<EOT
<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://Webservice.ips.com.cn/Sinopay/Standard/">
<ErrCode>1008</ErrCode>
</OrderMsg>
EOT;

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $entries = [
            ['entry_id' => 20160204170000],
            ['entry_id' => 20160204180000],
        ];

        $sourceData = [
            'number' => '015187',
            'entries' => $entries,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ips.com'
        ];

        $ips = new IPS();
        $ips->setContainer($this->container);
        $ips->setClient($this->client);
        $ips->setResponse($response);
        $ips->setPrivateKey($privateKey);
        $ips->setOptions($sourceData);
        $output = $ips->batchTracking();

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(180060, $output['code']);
        $this->assertEquals('Order does not exist', $output['msg']);
    }

    /**
     * 測試批次訂單查詢結果訂單id不合法
     */
    public function testBatchTrackingReturnOrderIdError()
    {
        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = <<<EOT
<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://Webservice.ips.com.cn/Sinopay/Standard/">
<ErrCode>1009</ErrCode>
</OrderMsg>
EOT;

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $entries = [
            ['entry_id' => 20160204170000],
            ['entry_id' => 20160204180000],
        ];

        $sourceData = [
            'number' => '015187',
            'entries' => $entries,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ips.com'
        ];

        $ips = new IPS();
        $ips->setContainer($this->container);
        $ips->setClient($this->client);
        $ips->setResponse($response);
        $ips->setPrivateKey($privateKey);
        $ips->setOptions($sourceData);
        $output = $ips->batchTracking();

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(180061, $output['code']);
        $this->assertEquals('Order Id error', $output['msg']);
    }

    /**
     * 測試批次訂單查詢結果支付平台起始訂單號大於結束訂單號
     */
    public function testBatchTrackingReturnPaymentGatewayStartNoLargeThanEndNo()
    {
        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = <<<EOT
<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://Webservice.ips.com.cn/Sinopay/Standard/">
<ErrCode>1010</ErrCode>
</OrderMsg>
EOT;

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $entries = [
            ['entry_id' => 20160204170000],
            ['entry_id' => 20160204180000],
        ];

        $sourceData = [
            'number' => '015187',
            'entries' => $entries,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ips.com'
        ];

        $ips = new IPS();
        $ips->setContainer($this->container);
        $ips->setClient($this->client);
        $ips->setResponse($response);
        $ips->setPrivateKey($privateKey);
        $ips->setOptions($sourceData);
        $output = $ips->batchTracking();

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(180134, $output['code']);
        $this->assertEquals('PaymentGateway error, StartNo large than EndNo', $output['msg']);
    }

    /**
     * 測試批次訂單查詢結果支付平台商號合約過期
     */
    public function testBatchTrackingReturnPaymentGatewayMerchantExpired()
    {
        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = <<<EOT
<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://Webservice.ips.com.cn/Sinopay/Standard/">
<ErrCode>2000</ErrCode>
</OrderMsg>
EOT;

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $entries = [
            ['entry_id' => 20160204170000],
            ['entry_id' => 20160204180000],
        ];

        $sourceData = [
            'number' => '015187',
            'entries' => $entries,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ips.com'
        ];

        $ips = new IPS();
        $ips->setContainer($this->container);
        $ips->setClient($this->client);
        $ips->setResponse($response);
        $ips->setPrivateKey($privateKey);
        $ips->setOptions($sourceData);
        $output = $ips->batchTracking();

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(180126, $output['code']);
        $this->assertEquals('PaymentGateway error, Merchant has been expired', $output['msg']);
    }

    /**
     * 測試批次訂單查詢失敗
     */
    public function testBatchTrackingReturnPaymentTrackingFailure()
    {
        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = <<<EOT
<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://Webservice.ips.com.cn/Sinopay/Standard/">
<ErrCode>9999</ErrCode>
</OrderMsg>
EOT;

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $entries = [
            ['entry_id' => 20160204170000],
            ['entry_id' => 20160204180000],
        ];

        $sourceData = [
            'number' => '015187',
            'entries' => $entries,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ips.com'
        ];

        $ips = new IPS();
        $ips->setContainer($this->container);
        $ips->setClient($this->client);
        $ips->setResponse($response);
        $ips->setPrivateKey($privateKey);
        $ips->setOptions($sourceData);
        $output = $ips->batchTracking();

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(180081, $output['code']);
        $this->assertEquals('Payment tracking failed', $output['msg']);
    }

    /**
     * 測試批次訂單查詢結果缺少回傳參數Total
     */
    public function testBatchTrackingReturnWithoutTotal()
    {
        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = <<<EOT
<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://Webservice.ips.com.cn/Sinopay/Standard/">
<ErrCode>0000</ErrCode>
</OrderMsg>
EOT;

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $entries = [
            ['entry_id' => 20160204170000],
            ['entry_id' => 20160204180000],
        ];

        $sourceData = [
            'number' => '015187',
            'entries' => $entries,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ips.com'
        ];

        $ips = new IPS();
        $ips->setContainer($this->container);
        $ips->setClient($this->client);
        $ips->setResponse($response);
        $ips->setPrivateKey($privateKey);
        $ips->setOptions($sourceData);
        $output = $ips->batchTracking();

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(180139, $output['code']);
        $this->assertEquals('No tracking return parameter specified', $output['msg']);
    }

    /**
     * 測試批次訂單查詢結果回傳分頁檔大於一頁
     */
    public function testBatchTrackingReturnEntriesExceedRestriction()
    {
        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = <<<EOT
<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://Webservice.ips.com.cn/Sinopay/Standard/">
<Total>101</Total>
<ErrCode>0000</ErrCode>
</OrderMsg>
EOT;

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $entries = [
            ['entry_id' => 20160204170000],
            ['entry_id' => 20160204180000],
        ];

        $sourceData = [
            'number' => '015187',
            'entries' => $entries,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ips.com'
        ];

        $ips = new IPS();
        $ips->setContainer($this->container);
        $ips->setClient($this->client);
        $ips->setResponse($response);
        $ips->setPrivateKey($privateKey);
        $ips->setOptions($sourceData);
        $output = $ips->batchTracking();

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150180173, $output['code']);
        $this->assertEquals('The number of return entries exceed the restriction', $output['msg']);
    }

    /**
     * 測試批次訂單查詢結果缺少回傳參數OrderRecords
     */
    public function testBatchTrackingReturnWithoutOrderRecords()
    {
        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = <<<EOT
<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://Webservice.ips.com.cn/Sinopay/Standard/">
<Total>100</Total>
<ErrCode>0000</ErrCode>
</OrderMsg>
EOT;

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $entries = [
            ['entry_id' => 20160204170000],
            ['entry_id' => 20160204180000],
        ];

        $sourceData = [
            'number' => '015187',
            'entries' => $entries,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ips.com'
        ];

        $ips = new IPS();
        $ips->setContainer($this->container);
        $ips->setClient($this->client);
        $ips->setResponse($response);
        $ips->setPrivateKey($privateKey);
        $ips->setOptions($sourceData);
        $output = $ips->batchTracking();

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(180139, $output['code']);
        $this->assertEquals('No tracking return parameter specified', $output['msg']);
    }

    /**
     * 測試批次訂單查詢結果缺少回傳參數Count
     */
    public function testBatchTrackingReturnWithoutCount()
    {
        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = <<<EOT
<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://Webservice.ips.com.cn/Sinopay/Standard/">
<Total>100</Total>
<ErrCode>0000</ErrCode>
<OrderRecords>
<OrderRecord>
</OrderRecord>
</OrderRecords>
</OrderMsg>
EOT;

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $entries = [
            ['entry_id' => 20160204170000],
            ['entry_id' => 20160204180000],
        ];

        $sourceData = [
            'number' => '015187',
            'entries' => $entries,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ips.com'
        ];

        $ips = new IPS();
        $ips->setContainer($this->container);
        $ips->setClient($this->client);
        $ips->setResponse($response);
        $ips->setPrivateKey($privateKey);
        $ips->setOptions($sourceData);
        $output = $ips->batchTracking();

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(180139, $output['code']);
        $this->assertEquals('No tracking return parameter specified', $output['msg']);
    }

    /**
     * 測試批次訂單查詢回傳一筆
     */
    public function testBatchTrackingReturnOneEntry()
    {
        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = <<<EOT
<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://Webservice.ips.com.cn/Sinopay/Standard/">
<Count>1</Count>
<Total>1</Total>
<ErrCode>0000</ErrCode>
<OrderRecords>
<OrderRecord>
<OrderNo>201404150014262827</OrderNo>
<IPSOrderNo>NT2014041526107993</IPSOrderNo>
<Trd_Code>NT</Trd_Code>
<Cr_Code>RMB</Cr_Code>
<Amount>100</Amount>
<MerchantOrderTime>20140415</MerchantOrderTime>
<IPSOrderTime>20140415171112</IPSOrderTime>
<Flag>1</Flag>
<Sign>97df01efaf4997db61640511b4971b77</Sign>
</OrderRecord>
</OrderRecords>
</OrderMsg>
EOT;

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $entries = [
            [
                'entry_id' => 201404150014262827,
                'amount' => '100.00',
            ],
        ];

        $sourceData = [
            'number' => '015187',
            'entries' => $entries,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ips.com'
        ];

        $ips = new IPS();
        $ips->setContainer($this->container);
        $ips->setClient($this->client);
        $ips->setResponse($response);
        $ips->setPrivateKey($privateKey);
        $ips->setOptions($sourceData);
        $output = $ips->batchTracking();

        $this->assertEquals('ok', $output['201404150014262827']['result']);
    }

    /**
     * 測試批次訂單查詢回傳多筆
     */
    public function testBatchTrackingReturnEntries()
    {
        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = <<<EOT
<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://Webservice.ips.com.cn/Sinopay/Standard/">
<Count>5</Count>
<Total>5</Total>
<ErrCode>0000</ErrCode>
<OrderRecords>
    <OrderRecord>
        <OrderNo>201404150014262827</OrderNo>
        <IPSOrderNo>NT2014041526107993</IPSOrderNo>
        <Trd_Code>NT</Trd_Code>
        <Cr_Code>RMB</Cr_Code>
        <Amount>100</Amount>
        <MerchantOrderTime>20140415</MerchantOrderTime>
        <IPSOrderTime>20140415171112</IPSOrderTime>
        <Flag>1</Flag>
        <Sign>97df01efaf4997db61640511b4971b77</Sign>
    </OrderRecord>
    <OrderRecord>
        <OrderNo>201602040000000552</OrderNo>
        <IPSOrderNo>NT2016020573216755</IPSOrderNo>
        <Trd_Code>NT</Trd_Code>
        <Cr_Code>RMB</Cr_Code>
        <Amount>0.02</Amount>
        <MerchantOrderTime>20160204</MerchantOrderTime>
        <IPSOrderTime>20160205013541</IPSOrderTime>
        <Flag>2</Flag>
        <Attach>hikaru</Attach>
        <Sign>c87524aaffd330fe29ca4ac64dc91829</Sign>
    </OrderRecord>
    <OrderRecord>
        <OrderNo>201602040000000553</OrderNo>
        <IPSOrderNo>NT2016020573216755</IPSOrderNo>
        <Trd_Code>NT</Trd_Code>
        <Cr_Code>RMB</Cr_Code>
        <Amount>0.02</Amount>
        <MerchantOrderTime>20160204</MerchantOrderTime>
        <IPSOrderTime>20160205013541</IPSOrderTime>
        <Flag>1</Flag>
        <Attach>hikaru</Attach>
    </OrderRecord>
    <OrderRecord>
        <OrderNo>201602040000000554</OrderNo>
        <IPSOrderNo>NT2016020573216755</IPSOrderNo>
        <Trd_Code>NT</Trd_Code>
        <Cr_Code>RMB</Cr_Code>
        <Amount>0.02</Amount>
        <MerchantOrderTime>20160204</MerchantOrderTime>
        <IPSOrderTime>20160205013541</IPSOrderTime>
        <Flag>1</Flag>
        <Attach>hikaru</Attach>
        <Sign>c87524aaffd330fe29ca4ac64dc91829</Sign>
    </OrderRecord>
    <OrderRecord>
        <OrderNo>201602040000000555</OrderNo>
        <IPSOrderNo>NT2016020573216755</IPSOrderNo>
        <Trd_Code>NT</Trd_Code>
        <Cr_Code>RMB</Cr_Code>
        <Amount>0.02</Amount>
        <MerchantOrderTime>20160204</MerchantOrderTime>
        <IPSOrderTime>20160205013541</IPSOrderTime>
        <Flag>1</Flag>
        <Attach>hikaru</Attach>
        <Sign>39d54afbf19786553bb950da6a0b9cfb</Sign>
    </OrderRecord>
</OrderRecords>
</OrderMsg>
EOT;

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $entries = [
            [
                'entry_id' => 201404150014262827,
                'amount' => '100.00',
            ],
            [
                'entry_id' => 201602040000000552,
                'amount' => '0.02',
            ],
            [
                'entry_id' => 201602040000000553,
                'amount' => '0.02',
            ],
            [
                'entry_id' => 201602040000000554,
                'amount' => '0.02',
            ],
            [
                'entry_id' => 201602040000000555,
                'amount' => '0.01',
            ],
            [
                'entry_id' => 201602040000000556,
                'amount' => '0.02',
            ]
        ];

        $sourceData = [
            'number' => '015187',
            'entries' => $entries,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.ips.com'
        ];

        $ips = new IPS();
        $ips->setContainer($this->container);
        $ips->setClient($this->client);
        $ips->setResponse($response);
        $ips->setPrivateKey($privateKey);
        $ips->setOptions($sourceData);
        $output = $ips->batchTracking();

        $this->assertEquals('ok', $output['201404150014262827']['result']);
        $this->assertEquals('error', $output['201602040000000552']['result']);
        $this->assertEquals('180035', $output['201602040000000552']['code']);
        $this->assertEquals('Payment failure', $output['201602040000000552']['msg']);
        $this->assertEquals('error', $output['201602040000000553']['result']);
        $this->assertEquals('180139', $output['201602040000000553']['code']);
        $this->assertEquals('No tracking return parameter specified', $output['201602040000000553']['msg']);
        $this->assertEquals('error', $output['201602040000000554']['result']);
        $this->assertEquals('180034', $output['201602040000000554']['code']);
        $this->assertEquals('Signature verification failed', $output['201602040000000554']['msg']);
        $this->assertEquals('error', $output['201602040000000555']['result']);
        $this->assertEquals('180058', $output['201602040000000555']['code']);
        $this->assertEquals('Order Amount error', $output['201602040000000555']['msg']);
        $this->assertEquals('error', $output['201602040000000556']['result']);
        $this->assertEquals('180060', $output['201602040000000556']['code']);
        $this->assertEquals('Order does not exist', $output['201602040000000556']['msg']);
    }
}
