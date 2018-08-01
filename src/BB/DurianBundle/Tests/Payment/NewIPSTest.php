<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\NewIPS;
use Buzz\Message\Response;

class NewIPSTest extends DurianTestCase
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
    public function testEncodeWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $newIps = new NewIPS();
        $newIps->getVerifyData();
    }

    /**
     * 測試加密基本參數設定未指定支付參數
     */
    public function testEncodeWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $newIps = new NewIPS();
        $newIps->setPrivateKey('GDgLwwdK270Qj1w4xho8lyTpRQZV9Jm5x4NwWOTThUa4');

        $sourceData = ['number' => ''];

        $newIps->setOptions($sourceData);
        $newIps->getVerifyData();
    }

    /**
     * 測試加密時帶入的paymentVendorId沒有對應的情況(Bankco會是空字串)
     */
    public function testEncodeWithoutPaymentVendorId()
    {
        $newIps = new NewIPS();
        $newIps->setPrivateKey('GDgLwwdK270Qj1w4xho8lyTpRQZV9Jm5x4NwWOTThUa4');

        $sourceData = [
            'number' => '000015',
            'orderId' => '201404150000123458',
            'amount' => '0.02',
            'orderCreateDate' => '2014-04-15 21:34:21',
            'notify_url' => 'http://59.126.84.197:3030/return.php?pay_system=32767&hallid=6',
            'username' => 'php1test',
            'paymentVendorId' => '1314',
            'merchantId' => '32767',
            'domain' => '6',
        ];

        $newIps->setOptions($sourceData);
        $encodeData = $newIps->getVerifyData();

        $this->assertEquals('', $encodeData['Bankco']);
    }

    /**
     * 測試加密
     */
    public function testEncode()
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
            'paymentVendorId' => '1',
            'merchantId' => '32767',
            'domain' => '6',
        ];

        $newIps = new NewIPS();
        $newIps->setPrivateKey($privateKey);
        $newIps->setOptions($sourceData);
        $encodeData = $newIps->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system_hallid=%s_%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals($sourceData['number'], $encodeData['Mer_code']);
        $this->assertEquals($sourceData['orderId'], $encodeData['Billno']);
        $this->assertEquals($sourceData['amount'], $encodeData['Amount']);
        $this->assertEquals('20140415', $encodeData['Date']);
        $this->assertEquals('', $encodeData['Merchanturl']);
        $this->assertEquals($notifyUrl, $encodeData['ServerUrl']);
        $this->assertEquals($sourceData['username'], $encodeData['Attach']);
        $this->assertEquals('00004', $encodeData['Bankco']);
        $this->assertEquals('b74303526ef8a0a19ad7ffb4d832d468', $encodeData['SignMD5']);
    }

    /**
     * 測試解密基本參數設定沒有帶入key的情況
     */
    public function testDecodeWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $newIps = new NewIPS();

        $newIps->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時支付平台未指定返回參數
     */
    public function testDecodeWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $newIps = new NewIPS();
        $newIps->setPrivateKey('GDgLwwdK270Qj1w4xho8lyTpRQZV9Jm5x4NwWOTThUa4');

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

        $newIps->setOptions($sourceData);
        $newIps->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時支付平台缺少回傳signature(加密簽名)
     */
    public function testDecodeWithoutSignature()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $newIps = new NewIPS();
        $newIps->setPrivateKey('GDgLwwdK270Qj1w4xho8lyTpRQZV9Jm5x4NwWOTThUa4');

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

        $newIps->setOptions($sourceData);
        $newIps->verifyOrderPayment([]);
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

        $newIps = new NewIPS();
        $newIps->setPrivateKey('GDgLwwdK270Qj1w4xho8lyTpRQZV9Jm5x4NwWOTThUa4');

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

        $newIps->setOptions($sourceData);
        $newIps->verifyOrderPayment([]);
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

        $newIps = new NewIPS();

        $privateKey = 'GDgLwwdK270Qj1w4xho8lyTpRQZV9Jm5x4NwWOTThUa4'.
            'fMhEBK9jOXFrKRT6xhlJuU2FEa89ov0ryyjfJuuPkcGz'.
            'O5CeVx5ZIrkkt1aBlZV36ySvHOMcNv8rncRiy3DQ';

        $newIps->setPrivateKey($privateKey);

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

        $newIps->setOptions($sourceData);
        $newIps->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $newIps = new NewIPS();

        $privateKey = 'GDgLwwdK270Qj1w4xho8lyTpRQZV9Jm5x4NwWOTThUa4'.
            'fMhEBK9jOXFrKRT6xhlJuU2FEa89ov0ryyjfJuuPkcGz'.
            'O5CeVx5ZIrkkt1aBlZV36ySvHOMcNv8rncRiy3DQ';

        $newIps->setPrivateKey($privateKey);

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

        $newIps->setOptions($sourceData);
        $newIps->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $newIps = new NewIPS();

        $privateKey = 'GDgLwwdK270Qj1w4xho8lyTpRQZV9Jm5x4NwWOTThUa4'.
            'fMhEBK9jOXFrKRT6xhlJuU2FEa89ov0ryyjfJuuPkcGz'.
            'O5CeVx5ZIrkkt1aBlZV36ySvHOMcNv8rncRiy3DQ';

        $newIps->setPrivateKey($privateKey);

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

        $newIps->setOptions($sourceData);
        $newIps->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $newIps = new NewIPS();

        $privateKey = 'GDgLwwdK270Qj1w4xho8lyTpRQZV9Jm5x4NwWOTThUa4'.
            'fMhEBK9jOXFrKRT6xhlJuU2FEa89ov0ryyjfJuuPkcGz'.
            'O5CeVx5ZIrkkt1aBlZV36ySvHOMcNv8rncRiy3DQ';

        $newIps->setPrivateKey($privateKey);

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

        $newIps->setOptions($sourceData);
        $newIps->verifyOrderPayment($entry);

        $this->assertEquals('ipscheckok', $newIps->getMsg());
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

        $newIps = new NewIPS();
        $newIps->paymentTracking();
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

        $newIps = new NewIPS();
        $newIps->setPrivateKey('1234');
        $newIps->paymentTracking();
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

        $newIps = new NewIPS();
        $newIps->setPrivateKey('1234');
        $newIps->setOptions($sourceData);
        $newIps->paymentTracking();
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

        $result = '<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '.
            'xmlns:xsd="http://www.w3.org/2001/XMLSchema" '.
            'xmlns="http://Webservice.newIps.com.cn/Sinopay/Standard/">'.
            '</OrderMsg>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newips.com'
        ];

        $newIps = new NewIPS();
        $newIps->setContainer($this->container);
        $newIps->setClient($this->client);
        $newIps->setResponse($response);
        $newIps->setPrivateKey($privateKey);
        $newIps->setOptions($sourceData);
        $newIps->paymentTracking();
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

        $result = '<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '.
            'xmlns:xsd="http://www.w3.org/2001/XMLSchema" '.
            'xmlns="http://Webservice.newIps.com.cn/Sinopay/Standard/">'.
            '<ErrCode>1001</ErrCode>'.
            '</OrderMsg>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newips.com'
        ];

        $newIps = new NewIPS();
        $newIps->setContainer($this->container);
        $newIps->setClient($this->client);
        $newIps->setResponse($response);
        $newIps->setPrivateKey($privateKey);
        $newIps->setOptions($sourceData);
        $newIps->paymentTracking();
    }

    /**
     * 測試訂單查詢結果為支付平台商戶不存在
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

        $result = '<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '.
            'xmlns:xsd="http://www.w3.org/2001/XMLSchema" '.
            'xmlns="http://Webservice.newIps.com.cn/Sinopay/Standard/">'.
            '<ErrCode>1002</ErrCode>'.
            '</OrderMsg>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newips.com'
        ];

        $newIps = new NewIPS();
        $newIps->setContainer($this->container);
        $newIps->setClient($this->client);
        $newIps->setResponse($response);
        $newIps->setPrivateKey($privateKey);
        $newIps->setOptions($sourceData);
        $newIps->paymentTracking();
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

        $result = '<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '.
            'xmlns:xsd="http://www.w3.org/2001/XMLSchema" '.
            'xmlns="http://Webservice.newIps.com.cn/Sinopay/Standard/">'.
            '<ErrCode>1003</ErrCode>'.
            '</OrderMsg>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newips.com'
        ];

        $newIps = new NewIPS();
        $newIps->setContainer($this->container);
        $newIps->setClient($this->client);
        $newIps->setResponse($response);
        $newIps->setPrivateKey($privateKey);
        $newIps->setOptions($sourceData);
        $newIps->paymentTracking();
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

        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = '<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '.
            'xmlns:xsd="http://www.w3.org/2001/XMLSchema" '.
            'xmlns="http://Webservice.newIps.com.cn/Sinopay/Standard/">'.
            '<ErrCode>1004</ErrCode>'.
            '</OrderMsg>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newips.com'
        ];

        $newIps = new NewIPS();
        $newIps->setContainer($this->container);
        $newIps->setClient($this->client);
        $newIps->setResponse($response);
        $newIps->setPrivateKey($privateKey);
        $newIps->setOptions($sourceData);
        $newIps->paymentTracking();
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

        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = '<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '.
            'xmlns:xsd="http://www.w3.org/2001/XMLSchema" '.
            'xmlns="http://Webservice.newIps.com.cn/Sinopay/Standard/">'.
            '<ErrCode>1005</ErrCode>'.
            '</OrderMsg>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newips.com'
        ];

        $newIps = new NewIPS();
        $newIps->setContainer($this->container);
        $newIps->setClient($this->client);
        $newIps->setResponse($response);
        $newIps->setPrivateKey($privateKey);
        $newIps->setOptions($sourceData);
        $newIps->paymentTracking();
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

        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = '<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '.
            'xmlns:xsd="http://www.w3.org/2001/XMLSchema" '.
            'xmlns="http://Webservice.newIps.com.cn/Sinopay/Standard/">'.
            '<ErrCode>1006</ErrCode>'.
            '</OrderMsg>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newips.com'
        ];

        $newIps = new NewIPS();
        $newIps->setContainer($this->container);
        $newIps->setClient($this->client);
        $newIps->setResponse($response);
        $newIps->setPrivateKey($privateKey);
        $newIps->setOptions($sourceData);
        $newIps->paymentTracking();
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

        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = '<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '.
            'xmlns:xsd="http://www.w3.org/2001/XMLSchema" '.
            'xmlns="http://Webservice.newIps.com.cn/Sinopay/Standard/">'.
            '<ErrCode>1007</ErrCode>'.
            '</OrderMsg>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newips.com'
        ];

        $newIps = new NewIPS();
        $newIps->setContainer($this->container);
        $newIps->setClient($this->client);
        $newIps->setResponse($response);
        $newIps->setPrivateKey($privateKey);
        $newIps->setOptions($sourceData);
        $newIps->paymentTracking();
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

        $result = '<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '.
            'xmlns:xsd="http://www.w3.org/2001/XMLSchema" '.
            'xmlns="http://Webservice.newIps.com.cn/Sinopay/Standard/">'.
            '<ErrCode>1008</ErrCode>'.
            '</OrderMsg>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newips.com'
        ];

        $newIps = new NewIPS();
        $newIps->setContainer($this->container);
        $newIps->setClient($this->client);
        $newIps->setResponse($response);
        $newIps->setPrivateKey($privateKey);
        $newIps->setOptions($sourceData);
        $newIps->paymentTracking();
    }

    /**
     * 測試訂單查詢結果id不合法
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

        $result = '<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '.
            'xmlns:xsd="http://www.w3.org/2001/XMLSchema" '.
            'xmlns="http://Webservice.newIps.com.cn/Sinopay/Standard/">'.
            '<ErrCode>1009</ErrCode>'.
            '</OrderMsg>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newips.com'
        ];

        $newIps = new NewIPS();
        $newIps->setContainer($this->container);
        $newIps->setClient($this->client);
        $newIps->setResponse($response);
        $newIps->setPrivateKey($privateKey);
        $newIps->setOptions($sourceData);
        $newIps->paymentTracking();
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

        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = '<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '.
            'xmlns:xsd="http://www.w3.org/2001/XMLSchema" '.
            'xmlns="http://Webservice.newIps.com.cn/Sinopay/Standard/">'.
            '<ErrCode>1010</ErrCode>'.
            '</OrderMsg>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newips.com'
        ];

        $newIps = new NewIPS();
        $newIps->setContainer($this->container);
        $newIps->setClient($this->client);
        $newIps->setResponse($response);
        $newIps->setPrivateKey($privateKey);
        $newIps->setOptions($sourceData);
        $newIps->paymentTracking();
    }

    /**
     * 測試訂單查詢結果為支付平台商號合約過期
     */
    public function testTrackingReturnPaymentGatewayMerchantExpired()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Merchant has been expired',
            180126
        );

        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = '<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '.
            'xmlns:xsd="http://www.w3.org/2001/XMLSchema" '.
            'xmlns="http://Webservice.newIps.com.cn/Sinopay/Standard/">'.
            '<ErrCode>2000</ErrCode>'.
            '</OrderMsg>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newips.com'
        ];

        $newIps = new NewIPS();
        $newIps->setContainer($this->container);
        $newIps->setClient($this->client);
        $newIps->setResponse($response);
        $newIps->setPrivateKey($privateKey);
        $newIps->setOptions($sourceData);
        $newIps->paymentTracking();
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

        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = '<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '.
            'xmlns:xsd="http://www.w3.org/2001/XMLSchema" '.
            'xmlns="http://Webservice.newIps.com.cn/Sinopay/Standard/">'.
            '<ErrCode>9999</ErrCode>'.
            '</OrderMsg>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newips.com'
        ];

        $newIps = new NewIPS();
        $newIps->setContainer($this->container);
        $newIps->setClient($this->client);
        $newIps->setResponse($response);
        $newIps->setPrivateKey($privateKey);
        $newIps->setOptions($sourceData);
        $newIps->paymentTracking();
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

        $result = '<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '.
            'xmlns:xsd="http://www.w3.org/2001/XMLSchema" '.
            'xmlns="http://Webservice.newIps.com.cn/Sinopay/Standard/">'.
            '<ErrCode>0000</ErrCode>'.
            '</OrderMsg>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newips.com'
        ];

        $newIps = new NewIPS();
        $newIps->setContainer($this->container);
        $newIps->setClient($this->client);
        $newIps->setResponse($response);
        $newIps->setPrivateKey($privateKey);
        $newIps->setOptions($sourceData);
        $newIps->paymentTracking();
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

        $result = '<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '.
            'xmlns:xsd="http://www.w3.org/2001/XMLSchema" '.
            'xmlns="http://Webservice.newIps.com.cn/Sinopay/Standard/">'.
            '<ErrCode>0000</ErrCode>'.
            '<OrderRecords>'.
            '<OrderRecord>'.
            '<OrderNo>201404150014262827</OrderNo>'.
            '<IPSOrderNo>NT2014041526107993</IPSOrderNo>'.
            '<Trd_Code>NT</Trd_Code>'.
            '<Cr_Code>RMB</Cr_Code>'.
            '<Amount>100</Amount>'.
            '<MerchantOrderTime>20140415</MerchantOrderTime>'.
            '<IPSOrderTime>20140415171112</IPSOrderTime>'.
            '<Flag>1</Flag>'.
            '</OrderRecord>'.
            '</OrderRecords>'.
            '</OrderMsg>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newips.com'
        ];

        $newIps = new NewIPS();
        $newIps->setContainer($this->container);
        $newIps->setClient($this->client);
        $newIps->setResponse($response);
        $newIps->setPrivateKey($privateKey);
        $newIps->setOptions($sourceData);
        $newIps->paymentTracking();
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

        $result = '<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '.
            'xmlns:xsd="http://www.w3.org/2001/XMLSchema" '.
            'xmlns="http://Webservice.newIps.com.cn/Sinopay/Standard/">'.
            '<ErrCode>0000</ErrCode>'.
            '<OrderRecords>'.
            '<OrderRecord>'.
            '<OrderNo>201404150014262827</OrderNo>'.
            '<IPSOrderNo>NT2014041526107993</IPSOrderNo>'.
            '<Trd_Code>NT</Trd_Code>'.
            '<Cr_Code>CNY</Cr_Code>'.
            '<Amount>100</Amount>'.
            '<MerchantOrderTime>20140415</MerchantOrderTime>'.
            '<IPSOrderTime>20140415171112</IPSOrderTime>'.
            '<Flag>1</Flag>'.
            '<Sign>97df01efaf4997db61640511b4971b77</Sign>'.
            '</OrderRecord>'.
            '</OrderRecords>'.
            '</OrderMsg>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newips.com'
        ];

        $newIps = new NewIPS();
        $newIps->setContainer($this->container);
        $newIps->setClient($this->client);
        $newIps->setResponse($response);
        $newIps->setPrivateKey($privateKey);
        $newIps->setOptions($sourceData);
        $newIps->paymentTracking();
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

        $result = '<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '.
            'xmlns:xsd="http://www.w3.org/2001/XMLSchema" '.
            'xmlns="http://Webservice.newIps.com.cn/Sinopay/Standard/">'.
            '<ErrCode>0000</ErrCode>'.
            '<OrderRecords>'.
            '<OrderRecord>'.
            '<OrderNo>201404150014262827</OrderNo>'.
            '<IPSOrderNo>NT2014041526107993</IPSOrderNo>'.
            '<Trd_Code>NT</Trd_Code>'.
            '<Cr_Code>RMB</Cr_Code>'.
            '<Amount>100</Amount>'.
            '<MerchantOrderTime>20140415</MerchantOrderTime>'.
            '<IPSOrderTime>20140415171112</IPSOrderTime>'.
            '<Flag>1</Flag>'.
            '<Sign>97df01efaf4997db61640511b4971b77</Sign>'.
            '</OrderRecord>'.
            '</OrderRecords>'.
            '</OrderMsg>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '1111130200',
            'orderId' => '201404150014262827',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newips.com',
            'amount' => '10.00'
        ];

        $newIps = new NewIPS();
        $newIps->setContainer($this->container);
        $newIps->setClient($this->client);
        $newIps->setResponse($response);
        $newIps->setPrivateKey($privateKey);
        $newIps->setOptions($sourceData);
        $newIps->paymentTracking();
    }

   /**
    * 測試訂單查詢
    */
    public function testPaymentTracking()
    {
        $privateKey = '8290229213342972300839197914370406523196538757994'.
            '3302098092204933845358213201349019949297614866034'.
            '463359518978774228509354873335';

        $result = '<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '.
            'xmlns:xsd="http://www.w3.org/2001/XMLSchema" '.
            'xmlns="http://Webservice.newIps.com.cn/Sinopay/Standard/">'.
            '<ErrCode>0000</ErrCode>'.
            '<OrderRecords>'.
            '<OrderRecord>'.
            '<OrderNo>201404150014262827</OrderNo>'.
            '<IPSOrderNo>NT2014041526107993</IPSOrderNo>'.
            '<Trd_Code>NT</Trd_Code>'.
            '<Cr_Code>RMB</Cr_Code>'.
            '<Amount>100</Amount>'.
            '<MerchantOrderTime>20140415</MerchantOrderTime>'.
            '<IPSOrderTime>20140415171112</IPSOrderTime>'.
            '<Flag>1</Flag>'.
            '<Sign>97df01efaf4997db61640511b4971b77</Sign>'.
            '</OrderRecord>'.
            '</OrderRecords>'.
            '</OrderMsg>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '1111130200',
            'orderId' => '201404150014262827',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.newips.com',
            'amount' => '100.00'
        ];

        $newIps = new NewIPS();
        $newIps->setContainer($this->container);
        $newIps->setClient($this->client);
        $newIps->setResponse($response);
        $newIps->setPrivateKey($privateKey);
        $newIps->setOptions($sourceData);
        $newIps->paymentTracking();
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

        $newIps = new NewIPS();
        $newIps->getPaymentTrackingData();
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

        $newIps = new NewIPS();
        $newIps->setPrivateKey('test');
        $newIps->getPaymentTrackingData();
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
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $newIps = new NewIPS();
        $newIps->setPrivateKey('test');
        $newIps->setOptions($options);
        $newIps->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $options = [
            'number' => '1111130200',
            'orderId' => '2014052200001',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.webservice.ips.com.cn',
        ];

        $newIps = new NewIPS();
        $newIps->setPrivateKey('test');
        $newIps->setOptions($options);
        $trackingData = $newIps->getPaymentTrackingData();

        $this->assertEquals($options['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('/Sinopay/Standard/IpsCheckTrade.asmx/GetOrderByNo', $trackingData['path']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('payment.http.webservice.ips.com.cn', $trackingData['headers']['Host']);

        $this->assertEquals('1111130200', $trackingData['form']['MerCode']);
        $this->assertEquals('3', $trackingData['form']['Flag']);
        $this->assertEquals('NT', $trackingData['form']['TradeType']);
        $this->assertEquals('2014052200001', $trackingData['form']['StartNo']);
        $this->assertEquals('ffc73779cebe77c9ed8cdf90ff0f6ef5', $trackingData['form']['Sign']);
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

        $newIps = new NewIPS();
        $newIps->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢缺少回傳參數ErrCode
     */
    public function testPaymentTrackingVerifyWithoutErrCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = '<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ' .
            'xmlns:xsd="http://www.w3.org/2001/XMLSchema" ' .
            'xmlns="http://Webservice.newIps.com.cn/Sinopay/Standard/">' .
            '</OrderMsg>';
        $sourceData = ['content' => $content];

        $newIps = new NewIPS();
        $newIps->setPrivateKey('test');
        $newIps->setOptions($sourceData);
        $newIps->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但商戶不存在
     */
    public function testPaymentTrackingVerifyButMerchantNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Merchant is not exist',
            180086
        );

        $content = '<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ' .
            'xmlns:xsd="http://www.w3.org/2001/XMLSchema" ' .
            'xmlns="http://Webservice.newIps.com.cn/Sinopay/Standard/">' .
            '<ErrCode>1001</ErrCode>' .
            '</OrderMsg>';
        $sourceData = ['content' => $content];

        $newIps = new NewIPS();
        $newIps->setPrivateKey('test');
        $newIps->setOptions($sourceData);
        $newIps->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但商戶憑證不存在
     */
    public function testPaymentTrackingVerifyButMerchantCertificateNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Merchant Certificate is not exist',
            180087
        );

        $content = '<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ' .
            'xmlns:xsd="http://www.w3.org/2001/XMLSchema" ' .
            'xmlns="http://Webservice.newIps.com.cn/Sinopay/Standard/">' .
            '<ErrCode>1002</ErrCode>' .
            '</OrderMsg>';
        $sourceData = ['content' => $content];

        $newIps = new NewIPS();
        $newIps->setPrivateKey('test');
        $newIps->setOptions($sourceData);
        $newIps->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但驗證簽名錯誤
     */
    public function testPaymentTrackingVerifyButSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Merchant sign error',
            180127
        );

        $content = '<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ' .
            'xmlns:xsd="http://www.w3.org/2001/XMLSchema" ' .
            'xmlns="http://Webservice.newIps.com.cn/Sinopay/Standard/">' .
            '<ErrCode>1003</ErrCode>' .
            '</OrderMsg>';
        $sourceData = ['content' => $content];

        $newIps = new NewIPS();
        $newIps->setPrivateKey('test');
        $newIps->setOptions($sourceData);
        $newIps->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但時間格式不合法
     */
    public function testPaymentTrackingVerifyButInvalidOrderDate()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Invalid Order date',
            180131
        );

        $content = '<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ' .
            'xmlns:xsd="http://www.w3.org/2001/XMLSchema" ' .
            'xmlns="http://Webservice.newIps.com.cn/Sinopay/Standard/">' .
            '<ErrCode>1004</ErrCode>' .
            '</OrderMsg>';
        $sourceData = ['content' => $content];

        $newIps = new NewIPS();
        $newIps->setPrivateKey('test');
        $newIps->setOptions($sourceData);
        $newIps->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但起始時間大於結束時間
     */
    public function testPaymentTrackingVerifyButBeginTimeLargeThanEngTime()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Begin time large than End time',
            180132
        );

        $content = '<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ' .
            'xmlns:xsd="http://www.w3.org/2001/XMLSchema" ' .
            'xmlns="http://Webservice.newIps.com.cn/Sinopay/Standard/">' .
            '<ErrCode>1005</ErrCode>' .
            '</OrderMsg>';
        $sourceData = ['content' => $content];

        $newIps = new NewIPS();
        $newIps->setPrivateKey('test');
        $newIps->setOptions($sourceData);
        $newIps->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但時間不存在
     */
    public function testPaymentTrackingVerifyButOrderDateNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, No date specified',
            180133
        );

        $content = '<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ' .
            'xmlns:xsd="http://www.w3.org/2001/XMLSchema" ' .
            'xmlns="http://Webservice.newIps.com.cn/Sinopay/Standard/">' .
            '<ErrCode>1006</ErrCode>' .
            '</OrderMsg>';
        $sourceData = ['content' => $content];

        $newIps = new NewIPS();
        $newIps->setPrivateKey('test');
        $newIps->setOptions($sourceData);
        $newIps->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但請求服務失敗
     */
    public function testPaymentTrackingVerifyButConnectionError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Connection error, please try again later or contact customer service',
            180077
        );

        $content = '<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ' .
            'xmlns:xsd="http://www.w3.org/2001/XMLSchema" ' .
            'xmlns="http://Webservice.newIps.com.cn/Sinopay/Standard/">' .
            '<ErrCode>1007</ErrCode>' .
            '</OrderMsg>';
        $sourceData = ['content' => $content];

        $newIps = new NewIPS();
        $newIps->setPrivateKey('test');
        $newIps->setOptions($sourceData);
        $newIps->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢訂單不存在
     */
    public function testPaymentTrackingVerifyOrderNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );

        $content = '<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ' .
            'xmlns:xsd="http://www.w3.org/2001/XMLSchema" ' .
            'xmlns="http://Webservice.newIps.com.cn/Sinopay/Standard/">' .
            '<ErrCode>1008</ErrCode>' .
            '</OrderMsg>';
        $sourceData = ['content' => $content];

        $newIps = new NewIPS();
        $newIps->setPrivateKey('test');
        $newIps->setOptions($sourceData);
        $newIps->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢訂單id不合法
     */
    public function testPaymentTrackingVerifyOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $content = '<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ' .
            'xmlns:xsd="http://www.w3.org/2001/XMLSchema" ' .
            'xmlns="http://Webservice.newIps.com.cn/Sinopay/Standard/">' .
            '<ErrCode>1009</ErrCode>' .
            '</OrderMsg>';
        $sourceData = ['content' => $content];

        $newIps = new NewIPS();
        $newIps->setPrivateKey('test');
        $newIps->setOptions($sourceData);
        $newIps->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但起始訂單號大於結束訂單號
     */
    public function testPaymentTrackingVerifyButStartNoLargeThanEndNo()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, StartNo large than EndNo',
            180134
        );

        $content = '<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ' .
            'xmlns:xsd="http://www.w3.org/2001/XMLSchema" ' .
            'xmlns="http://Webservice.newIps.com.cn/Sinopay/Standard/">' .
            '<ErrCode>1010</ErrCode>' .
            '</OrderMsg>';
        $sourceData = ['content' => $content];

        $newIps = new NewIPS();
        $newIps->setPrivateKey('test');
        $newIps->setOptions($sourceData);
        $newIps->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但商號合約過期
     */
    public function testPaymentTrackingVerifyButMerchantExpired()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Merchant has been expired',
            180126
        );

        $content = '<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ' .
            'xmlns:xsd="http://www.w3.org/2001/XMLSchema" ' .
            'xmlns="http://Webservice.newIps.com.cn/Sinopay/Standard/">' .
            '<ErrCode>2000</ErrCode>' .
            '</OrderMsg>';
        $sourceData = ['content' => $content];

        $newIps = new NewIPS();
        $newIps->setPrivateKey('test');
        $newIps->setOptions($sourceData);
        $newIps->paymentTrackingVerify();
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

        $content = '<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ' .
            'xmlns:xsd="http://www.w3.org/2001/XMLSchema" ' .
            'xmlns="http://Webservice.newIps.com.cn/Sinopay/Standard/">' .
            '<ErrCode>9999</ErrCode>' .
            '</OrderMsg>';
        $sourceData = ['content' => $content];

        $newIps = new NewIPS();
        $newIps->setPrivateKey('test');
        $newIps->setOptions($sourceData);
        $newIps->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢但未指定返回參數
     */
    public function testPaymentTrackingVerifyWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = '<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ' .
            'xmlns:xsd="http://www.w3.org/2001/XMLSchema" ' .
            'xmlns="http://Webservice.newIps.com.cn/Sinopay/Standard/">' .
            '<ErrCode>0000</ErrCode>' .
            '</OrderMsg>';
        $sourceData = ['content' => $content];

        $newIps = new NewIPS();
        $newIps->setPrivateKey('test');
        $newIps->setOptions($sourceData);
        $newIps->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢缺少回傳參數Sign
     */
    public function testPaymentTrackingVerifyWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $content = '<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ' .
            'xmlns:xsd="http://www.w3.org/2001/XMLSchema" ' .
            'xmlns="http://Webservice.newIps.com.cn/Sinopay/Standard/">' .
            '<ErrCode>0000</ErrCode>' .
            '<OrderRecords>' .
            '<OrderRecord>' .
            '<OrderNo>201404150014262827</OrderNo>' .
            '<IPSOrderNo>NT2014041526107993</IPSOrderNo>' .
            '<Trd_Code>NT</Trd_Code>' .
            '<Cr_Code>RMB</Cr_Code>' .
            '<Amount>100</Amount>' .
            '<MerchantOrderTime>20140415</MerchantOrderTime>' .
            '<IPSOrderTime>20140415171112</IPSOrderTime>' .
            '<Flag>1</Flag>' .
            '</OrderRecord>' .
            '</OrderRecords>' .
            '</OrderMsg>';
        $sourceData = ['content' => $content];

        $newIps = new NewIPS();
        $newIps->setPrivateKey('test');
        $newIps->setOptions($sourceData);
        $newIps->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢簽名驗證錯誤
     */
    public function testPaymentTrackingVerifySignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $privateKey = '8290229213342972300839197914370406523196538757994' .
            '3302098092204933845358213201349019949297614866034' .
            '463359518978774228509354873335';

        $content = '<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ' .
            'xmlns:xsd="http://www.w3.org/2001/XMLSchema" ' .
            'xmlns="http://Webservice.newIps.com.cn/Sinopay/Standard/">' .
            '<ErrCode>0000</ErrCode>' .
            '<OrderRecords>' .
            '<OrderRecord>' .
            '<OrderNo>201404150014262827</OrderNo>' .
            '<IPSOrderNo>NT2014041526107993</IPSOrderNo>' .
            '<Trd_Code>NT</Trd_Code>' .
            '<Cr_Code>CNY</Cr_Code>' .
            '<Amount>100</Amount>' .
            '<MerchantOrderTime>20140415</MerchantOrderTime>' .
            '<IPSOrderTime>20140415171112</IPSOrderTime>' .
            '<Flag>1</Flag>' .
            '<Sign>97df01efaf4997db61640511b4971b77</Sign>' .
            '</OrderRecord>' .
            '</OrderRecords>' .
            '</OrderMsg>';
        $sourceData = ['content' => $content];

        $newIps = new NewIPS();
        $newIps->setPrivateKey($privateKey);
        $newIps->setOptions($sourceData);
        $newIps->paymentTrackingVerify();
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

        $privateKey = '8290229213342972300839197914370406523196538757994' .
            '3302098092204933845358213201349019949297614866034' .
            '463359518978774228509354873335';

        $content = '<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ' .
            'xmlns:xsd="http://www.w3.org/2001/XMLSchema" ' .
            'xmlns="http://Webservice.newIps.com.cn/Sinopay/Standard/">' .
            '<ErrCode>0000</ErrCode>' .
            '<OrderRecords>' .
            '<OrderRecord>' .
            '<OrderNo>201404150014262827</OrderNo>' .
            '<IPSOrderNo>NT2014041526107993</IPSOrderNo>' .
            '<Trd_Code>NT</Trd_Code>' .
            '<Cr_Code>RMB</Cr_Code>' .
            '<Amount>100</Amount>' .
            '<MerchantOrderTime>20140415</MerchantOrderTime>' .
            '<IPSOrderTime>20140415171112</IPSOrderTime>' .
            '<Flag>1</Flag>' .
            '<Sign>97df01efaf4997db61640511b4971b77</Sign>' .
            '</OrderRecord>' .
            '</OrderRecords>' .
            '</OrderMsg>';
        $sourceData = [
            'content' => $content,
            'amount' => '10.00'
        ];

        $newIps = new NewIPS();
        $newIps->setPrivateKey($privateKey);
        $newIps->setOptions($sourceData);
        $newIps->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢
     */
    public function testPaymentTrackingVerify()
    {
        $privateKey = '8290229213342972300839197914370406523196538757994' .
            '3302098092204933845358213201349019949297614866034' .
            '463359518978774228509354873335';

        $content = '<OrderMsg xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ' .
            'xmlns:xsd="http://www.w3.org/2001/XMLSchema" ' .
            'xmlns="http://Webservice.newIps.com.cn/Sinopay/Standard/">' .
            '<ErrCode>0000</ErrCode>' .
            '<OrderRecords>' .
            '<OrderRecord>' .
            '<OrderNo>201404150014262827</OrderNo>' .
            '<IPSOrderNo>NT2014041526107993</IPSOrderNo>' .
            '<Trd_Code>NT</Trd_Code>' .
            '<Cr_Code>RMB</Cr_Code>' .
            '<Amount>100</Amount>' .
            '<MerchantOrderTime>20140415</MerchantOrderTime>' .
            '<IPSOrderTime>20140415171112</IPSOrderTime>' .
            '<Flag>1</Flag>' .
            '<Sign>97df01efaf4997db61640511b4971b77</Sign>' .
            '</OrderRecord>' .
            '</OrderRecords>' .
            '</OrderMsg>';
        $sourceData = [
            'content' => $content,
            'amount' => '100.00'
        ];

        $newIps = new NewIPS();
        $newIps->setPrivateKey($privateKey);
        $newIps->setOptions($sourceData);
        $newIps->paymentTrackingVerify();
    }
}
