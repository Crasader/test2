<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\NganLuong;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use BB\DurianBundle\Payment\PaymentBase;

class NganLuongTest extends DurianTestCase
{
    /**
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

        $mockCde = $this->getMockBuilder('BB\DurianBundle\Entity\CashDepositEntry')
            ->disableOriginalConstructor()
            ->setMethods(['setRefId'])
            ->getMock();
        $mockCde->expects($this->any())
            ->method('setRefId')
            ->willReturn($mockCde);

        $mockRepo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $mockRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn($mockCde);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository', 'flush'])
            ->getMock();
        $mockEm->expects($this->any(0))
            ->method('getRepository')
            ->willReturn($mockRepo);

        $mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();
        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $mockLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Payment')
            ->disableOriginalConstructor()
            ->setMethods(['record'])
            ->getMock();

        $getMap = [
            ['durian.payment_logger', 1, $mockLogger],
            ['doctrine', 1, $mockDoctrine]
        ];

        $container->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

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

        $nganLuong = new NganLuong();
        $nganLuong->getVerifyData();
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

        $nganLuong = new NganLuong();
        $nganLuong->setPrivateKey('AFQjCNF3VmdM55ri0Uvtc0ioOooYJGiUTQ');

        $sourceData = ['number' => ''];

        $nganLuong->setOptions($sourceData);
        $nganLuong->getVerifyData();
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
        $this->client->expects($this->any())
            ->method('call')
            ->willThrowException($exception);

        $nganLuong = new NganLuong();
        $nganLuong->setClient($this->client);
        $nganLuong->setContainer($this->container);
        $nganLuong->setPrivateKey('AFQjCNF3VmdM55ri0Uvtc0ioOooYJGiUTQ');

        $sourceData = [
            'number' => '34923',
            'orderId' => '201409090000000453',
            'amount' => '2000',
            'notify_url' => 'http://neteller.6te.net/neteller.php%3Fpay_system%3D12354%26hallid%3D6',
            'paymentVendorId' => '283',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.nganLuong.com',
            'merchant_extra' => ['receiver' => 'cuocdoilenhdenh_911@gmail.com'],
        ];

        $nganLuong->setOptions($sourceData);
        $nganLuong->getVerifyData();
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

        $nganLuong = new NganLuong();

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn('');

        $nganLuong->setClient($this->client);
        $nganLuong->setContainer($this->container);
        $nganLuong->setPrivateKey('AFQjCNF3VmdM55ri0Uvtc0ioOooYJGiUTQ');

        $sourceData = [
            'number' => '34923',
            'orderId' => '201409090000000453',
            'amount' => '2000',
            'notify_url' => 'http://neteller.6te.net/neteller.php%3Fpay_system%3D12354%26hallid%3D6',
            'paymentVendorId' => '283',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.nganLuong.com',
            'merchant_extra' => ['receiver' => 'cuocdoilenhdenh_911@gmail.com'],
        ];

        $nganLuong->setOptions($sourceData);
        $nganLuong->getVerifyData();
    }

    /**
     * 測試加密時對外返回結果錯誤
     */
    public function testPayConnectionPaymentGatewayError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'error_code: 04, result_description: Checksum code isn\'t right.',
            180130
        );

        $nganLuong = new NganLuong();
        $nganLuong->setPrivateKey('AFQjCNF3VmdM55ri0Uvtc0ioOooYJGiUTQ');

        $paymentInfo = [
            'result_code' => '04',
            'token' => '',
            'link_checkout' => '',
            'time_limit' => '',
            'result_description' => 'Checksum code isn\'t right.'
        ];

        $encoders = [new XmlEncoder('root')];
        $normalizers = [new GetSetMethodNormalizer()];

        $serializer = new Serializer($normalizers, $encoders);

        $xml = $serializer->encode($paymentInfo, 'xml');

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($xml);

        $nganLuong->setClient($this->client);
        $nganLuong->setContainer($this->container);

        $sourceData = [
            'number' => '34923',
            'orderId' => '201409090000000453',
            'amount' => '2000',
            'notify_url' => 'http://neteller.6te.net/neteller.php%3Fpay_system%3D12354%26hallid%3D6',
            'paymentVendorId' => '283',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.nganLuong.com',
            'merchant_extra' => ['receiver' => 'cuocdoilenhdenh_911@gmail.com'],
        ];

        $nganLuong->setOptions($sourceData);
        $nganLuong->getVerifyData();
    }

    /**
     * 測試加密時對外返回缺少狀態碼
     */
    public function testEncodeReplyWithoutResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $nganLuong = new NganLuong();

        $paymentInfo = [
            'token' => '5859286-22044a6f9cb72e5b11a0515fc8b594aa',
            'link_checkout' => 'https://www.nganluong.vn?token=5859286-22044a6f9cb72e5b11a0515fc8b594aa',
            'time_limit' => '06/09/2014, 10:56:16',
            'result_description' => 'Thành công'
        ];

        $encoders = [new XmlEncoder('root')];
        $normalizers = [new GetSetMethodNormalizer()];

        $serializer = new Serializer($normalizers, $encoders);

        $xml = $serializer->encode($paymentInfo, 'xml');

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($xml);

        $nganLuong->setClient($this->client);
        $nganLuong->setContainer($this->container);
        $nganLuong->setPrivateKey('AFQjCNF3VmdM55ri0Uvtc0ioOooYJGiUTQ');

        $sourceData = [
            'number' => '34923',
            'orderId' => '201409090000000453',
            'amount' => '2000',
            'notify_url' => 'http://neteller.6te.net/neteller.php%3Fpay_system%3D12354%26hallid%3D6',
            'paymentVendorId' => '283',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.nganLuong.com',
            'merchant_extra' => ['receiver' => 'cuocdoilenhdenh_911@gmail.com'],
        ];

        $nganLuong->setOptions($sourceData);
        $nganLuong->getVerifyData();
    }

    /**
     * 測試加密時對外返回缺少狀態描述
     */
    public function testEncodeReplyWithoutResultDescription()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $nganLuong = new NganLuong();
        $nganLuong->setPrivateKey('AFQjCNF3VmdM55ri0Uvtc0ioOooYJGiUTQ');

        $paymentInfo = [
            'result_code' => '00',
            'token' => '5859286-22044a6f9cb72e5b11a0515fc8b594aa',
            'link_checkout' => 'https://www.nganluong.vn?token=5859286-22044a6f9cb72e5b11a0515fc8b594aa',
            'time_limit' => '06/09/2014, 10:56:16'
        ];

        $encoders = [new XmlEncoder('root')];
        $normalizers = [new GetSetMethodNormalizer()];

        $serializer = new Serializer($normalizers, $encoders);

        $xml = $serializer->encode($paymentInfo, 'xml');

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($xml);

        $nganLuong->setClient($this->client);
        $nganLuong->setContainer($this->container);

        $sourceData = [
            'number' => '34923',
            'orderId' => '201409090000000453',
            'amount' => '2000',
            'notify_url' => 'http://neteller.6te.net/neteller.php%3Fpay_system%3D12354%26hallid%3D6',
            'paymentVendorId' => '283',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.nganLuong.com',
            'merchant_extra' => ['receiver' => 'cuocdoilenhdenh_911@gmail.com'],
        ];

        $nganLuong->setOptions($sourceData);
        $nganLuong->getVerifyData();
    }

    /**
     * 測試加密時對外返回缺少支付連結
     */
    public function testEncodeReplyWithoutLinkCheckout()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $nganLuong = new NganLuong();
        $nganLuong->setPrivateKey('AFQjCNF3VmdM55ri0Uvtc0ioOooYJGiUTQ');

        $paymentInfo = [
            'result_code' => '00',
            'token' => '5859286-22044a6f9cb72e5b11a0515fc8b594aa',
            'time_limit' => '06/09/2014, 10:56:16',
            'result_description' => 'Thành công'
        ];

        $encoders = [new XmlEncoder('root')];
        $normalizers = [new GetSetMethodNormalizer()];

        $serializer = new Serializer($normalizers, $encoders);

        $xml = $serializer->encode($paymentInfo, 'xml');

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($xml);

        $nganLuong->setClient($this->client);
        $nganLuong->setContainer($this->container);

        $sourceData = [
            'number' => '34923',
            'orderId' => '201409090000000453',
            'amount' => '2000',
            'notify_url' => 'http://neteller.6te.net/neteller.php%3Fpay_system%3D12354%26hallid%3D6',
            'paymentVendorId' => '283',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.nganLuong.com',
            'merchant_extra' => ['receiver' => 'cuocdoilenhdenh_911@gmail.com'],
        ];

        $nganLuong->setOptions($sourceData);
        $nganLuong->getVerifyData();
    }

    /**
     * 測試加密時對外返回缺少token
     */
    public function testEncodeReplyWithoutToken()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $nganLuong = new NganLuong();
        $nganLuong->setPrivateKey('AFQjCNF3VmdM55ri0Uvtc0ioOooYJGiUTQ');

        $paymentInfo = [
            'result_code' => '00',
            'link_checkout' => 'https://www.nganluong.vn?token=5859286-22044a6f9cb72e5b11a0515fc8b594aa',
            'time_limit' => '06/09/2014, 10:56:16',
            'result_description' => 'Thành công'
        ];

        $encoders = [new XmlEncoder('root')];
        $normalizers = [new GetSetMethodNormalizer()];

        $serializer = new Serializer($normalizers, $encoders);

        $xml = $serializer->encode($paymentInfo, 'xml');

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($xml);

        $nganLuong->setClient($this->client);
        $nganLuong->setContainer($this->container);

        $sourceData = [
            'number' => '34923',
            'orderId' => '201409090000000453',
            'amount' => '2000',
            'notify_url' => 'http://neteller.6te.net/neteller.php%3Fpay_system%3D12354%26hallid%3D6',
            'paymentVendorId' => '283',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.nganLuong.com',
            'merchant_extra' => ['receiver' => 'cuocdoilenhdenh_911@gmail.com'],
        ];

        $nganLuong->setOptions($sourceData);
        $nganLuong->getVerifyData();
    }

    /**
     * 測試加密時缺少商家額外資訊
     */
    public function testEncodeWithoutMerchantExtra()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $nganLuong = new NganLuong();
        $nganLuong->setPrivateKey('AFQjCNF3VmdM55ri0Uvtc0ioOooYJGiUTQ');

        $sourceData = [
            'number' => '34923',
            'orderId' => '201409090000000453',
            'amount' => '2000',
            'notify_url' => 'http://neteller.6te.net/neteller.php%3Fpay_system%3D12354%26hallid%3D6',
            'paymentVendorId' => '283',
            'merchant_extra' => [],
        ];

        $nganLuong->setOptions($sourceData);
        $nganLuong->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testEncode()
    {
        $nganLuong = new NganLuong();
        $nganLuong->setPrivateKey('AFQjCNF3VmdM55ri0Uvtc0ioOooYJGiUTQ');

        $paymentInfo = [
            'result_code' => '00',
            'token' => '5859286-22044a6f9cb72e5b11a0515fc8b594aa',
            'link_checkout' => 'https://www.nganluong.vn?token=5859286-22044a6f9cb72e5b11a0515fc8b594aa',
            'time_limit' => '06/09/2014, 10:56:16',
            'result_description' => 'Thành công'
        ];

        $encoders = [new XmlEncoder('root')];
        $normalizers = [new GetSetMethodNormalizer()];

        $serializer = new Serializer($normalizers, $encoders);

        $xml = $serializer->encode($paymentInfo, 'xml');

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($xml);

        $nganLuong->setClient($this->client);
        $nganLuong->setContainer($this->container);

        $sourceData = [
            'number' => '34923',
            'orderId' => '201409090000000453',
            'amount' => '2000',
            'notify_url' => 'http://neteller.6te.net/neteller.php%3Fpay_system%3D12354%26hallid%3D6',
            'paymentVendorId' => '283',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.nganLuong.com',
            'merchant_extra' => ['receiver' => 'cuocdoilenhdenh_911@gmail.com'],
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $nganLuong->setOptions($sourceData);
        $nganLuong->setPayway(PaymentBase::PAYWAY_CASH);
        $encodeData = $nganLuong->getVerifyData();

        $payUrl = 'https://www.nganluong.vn?token=5859286-22044a6f9cb72e5b11a0515fc8b594aa';

        $this->assertEquals($payUrl, $encodeData['act_url']);
    }

    /**
     * 測試加密(租卡)
     */
    public function testEncodeCard()
    {
        $nganLuong = new NganLuong();
        $nganLuong->setPrivateKey('AFQjCNF3VmdM55ri0Uvtc0ioOooYJGiUTQ');

        $paymentInfo = [
            'result_code' => '00',
            'token' => '5859286-22044a6f9cb72e5b11a0515fc8b594aa',
            'link_checkout' => 'https://www.nganluong.vn?token=5859286-22044a6f9cb72e5b11a0515fc8b594aa',
            'time_limit' => '06/09/2014, 10:56:16',
            'result_description' => 'Thành công'
        ];

        $encoders = [new XmlEncoder('root')];
        $normalizers = [new GetSetMethodNormalizer()];

        $serializer = new Serializer($normalizers, $encoders);

        $xml = $serializer->encode($paymentInfo, 'xml');

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($xml);

        $nganLuong->setClient($this->client);
        $nganLuong->setContainer($this->container);

        $sourceData = [
            'number' => '34923',
            'orderId' => '201409090000000453',
            'amount' => '2000',
            'notify_url' => 'http://neteller.6te.net/neteller.php%3Fpay_system%3D12354%26hallid%3D6',
            'paymentVendorId' => '283',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.nganLuong.com',
            'merchant_extra' => ['receiver' => 'cuocdoilenhdenh_911@gmail.com'],
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $nganLuong->setOptions($sourceData);
        $nganLuong->setPayway(PaymentBase::PAYWAY_CARD);
        $encodeData = $nganLuong->getVerifyData();

        $payUrl = 'https://www.nganluong.vn?token=5859286-22044a6f9cb72e5b11a0515fc8b594aa';

        $this->assertEquals($payUrl, $encodeData['act_url']);
    }

    /**
     * 測試解密時帶入key為空字串
     */
    public function testDecodeKeyIsEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $nganLuong = new NganLuong();

        $nganLuong->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台未指定返回參數
     */
    public function testDecodeWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $nganLuong = new NganLuong();
        $nganLuong->setPrivateKey('AFQjCNF3VmdM55ri0Uvtc0ioOooYJGiUTQ');

        $sourceData = [
            'merchant_site_code' => '34923',
            'receiver' => 'cuocdoilenhdenh_911@gmail.com',
            'amount' => '2000',
            'currency_code' => 'VND',
            'ref_id' => '5625061-3fe6c1ed74141e8f82cd74bdfec3ad6d',
            'checksum' => 'df62911d505658e60b6672281ed93d2c'
        ];

        $nganLuong->setOptions($sourceData);
        $nganLuong->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台缺少回傳checksum(加密簽名)
     */
    public function testDecodeWithoutChecksum()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $nganLuong = new NganLuong();
        $nganLuong->setPrivateKey('AFQjCNF3VmdM55ri0Uvtc0ioOooYJGiUTQ');

        $sourceData = [
            'merchant_site_code' => '34923',
            'receiver' => 'cuocdoilenhdenh_911@gmail.com',
            'order_code' => '201409090000000453',
            'amount' => '2000',
            'currency_code' => 'VND',
            'ref_id' => '5625061-3fe6c1ed74141e8f82cd74bdfec3ad6d'
        ];

        $nganLuong->setOptions($sourceData);
        $nganLuong->verifyOrderPayment([]);
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

        $nganLuong = new NganLuong();
        $nganLuong->setPrivateKey('AFQjCNF3VmdM55ri0Uvtc0ioOooYJGiUTQ');

        $sourceData = [
            'merchant_site_code' => '34923',
            'receiver' => 'cuocdoilenhdenh_911@gmail.com',
            'order_code' => '201409090000000453',
            'amount' => '2000',
            'currency_code' => 'vnd',
            'ref_id' => '5859286-22044a6f9cb72e5b11a0515fc8b594aa',
            'checksum' => 'dfddb694ee14f3f0175baaac804955ee'
        ];

        $nganLuong->setOptions($sourceData);
        $nganLuong->verifyOrderPayment([]);
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

        $nganLuong = new NganLuong();
        $nganLuong->setPrivateKey('AFQjCNF3VmdM55ri0Uvtc0ioOooYJGiUTQ');

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn('');

        $nganLuong->setClient($this->client);
        $nganLuong->setContainer($this->container);

        $sourceData = [
            'merchant_site_code' => '34923',
            'receiver' => 'cuocdoilenhdenh_911@gmail.com',
            'order_code' => '201409090000000453',
            'amount' => '2000',
            'currency_code' => 'vnd',
            'ref_id' => '5859286-22044a6f9cb72e5b11a0515fc8b594aa',
            'checksum' => '75baaac804955eedfddb694ee14f3f01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.nganLuong.com'
        ];

        $nganLuong->setOptions($sourceData);
        $nganLuong->verifyOrderPayment([]);
    }

    /**
     * 測試解密時對外返回缺少狀態碼
     */
    public function testDecodeReplyWithoutResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $nganLuong = new NganLuong();
        $nganLuong->setPrivateKey('AFQjCNF3VmdM55ri0Uvtc0ioOooYJGiUTQ');

        $transactionDetail = [
            'result_description' => 'Thành công',
            'transaction_id' => 'NganLuong1234567',
            'amount' => '2000',
            'transaction_status' => '4'
        ];

        $encoders = [new XmlEncoder('root')];
        $normalizers = [new GetSetMethodNormalizer()];

        $serializer = new Serializer($normalizers, $encoders);

        $xml = $serializer->encode($transactionDetail, 'xml');

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($xml);

        $nganLuong->setClient($this->client);
        $nganLuong->setContainer($this->container);

        $sourceData = [
            'merchant_site_code' => '34923',
            'receiver' => 'cuocdoilenhdenh_911@gmail.com',
            'order_code' => '201409090000000453',
            'amount' => '2000',
            'currency_code' => 'vnd',
            'ref_id' => '5859286-22044a6f9cb72e5b11a0515fc8b594aa',
            'checksum' => '75baaac804955eedfddb694ee14f3f01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.nganLuong.com'
        ];

        $nganLuong->setOptions($sourceData);
        $nganLuong->verifyOrderPayment([]);
    }

    /**
     * 測試解密時對外返回缺少狀態描述
     */
    public function testDecodeReplyWithoutResultDescription()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $nganLuong = new NganLuong();
        $nganLuong->setPrivateKey('AFQjCNF3VmdM55ri0Uvtc0ioOooYJGiUTQ');

        $transactionDetail = [
            'result_code' => '00',
            'transaction_id' => 'NganLuong1234567',
            'amount' => '2000',
            'transaction_status' => '4'
        ];

        $encoders = [new XmlEncoder('root')];
        $normalizers = [new GetSetMethodNormalizer()];

        $serializer = new Serializer($normalizers, $encoders);

        $xml = $serializer->encode($transactionDetail, 'xml');

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($xml);

        $nganLuong->setClient($this->client);
        $nganLuong->setContainer($this->container);

        $sourceData = [
            'merchant_site_code' => '34923',
            'receiver' => 'cuocdoilenhdenh_911@gmail.com',
            'order_code' => '201409090000000453',
            'amount' => '2000',
            'currency_code' => 'vnd',
            'ref_id' => '5859286-22044a6f9cb72e5b11a0515fc8b594aa',
            'checksum' => '75baaac804955eedfddb694ee14f3f01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.nganLuong.com'
        ];

        $nganLuong->setOptions($sourceData);
        $nganLuong->verifyOrderPayment([]);
    }

    /**
     * 測試解密時對外返回缺少交易狀態
     */
    public function testDecodeReplyWithoutTransactionStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $nganLuong = new NganLuong();
        $nganLuong->setPrivateKey('AFQjCNF3VmdM55ri0Uvtc0ioOooYJGiUTQ');

        $transactionDetail = [
            'result_code' => '00',
            'result_description' => 'Thành công',
            'transaction_id' => 'NganLuong1234567',
            'amount' => '2000'
        ];

        $encoders = [new XmlEncoder('root')];
        $normalizers = [new GetSetMethodNormalizer()];

        $serializer = new Serializer($normalizers, $encoders);

        $xml = $serializer->encode($transactionDetail, 'xml');

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($xml);

        $nganLuong->setClient($this->client);
        $nganLuong->setContainer($this->container);

        $sourceData = [
            'merchant_site_code' => '34923',
            'receiver' => 'cuocdoilenhdenh_911@gmail.com',
            'order_code' => '201409090000000453',
            'amount' => '2000',
            'currency_code' => 'vnd',
            'ref_id' => '5859286-22044a6f9cb72e5b11a0515fc8b594aa',
            'checksum' => '75baaac804955eedfddb694ee14f3f01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.nganLuong.com'
        ];

        $nganLuong->setOptions($sourceData);
        $nganLuong->verifyOrderPayment([]);
    }

    /**
     * 測試解密時對外返回缺少金額
     */
    public function testDecodeReplyWithoutAmount()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $nganLuong = new NganLuong();
        $nganLuong->setPrivateKey('AFQjCNF3VmdM55ri0Uvtc0ioOooYJGiUTQ');

        $transactionDetail = [
            'result_code' => '00',
            'result_description' => 'Thành công',
            'transaction_id' => 'NganLuong1234567',
            'transaction_status' => '4'
        ];

        $encoders = [new XmlEncoder('root')];
        $normalizers = [new GetSetMethodNormalizer()];

        $serializer = new Serializer($normalizers, $encoders);

        $xml = $serializer->encode($transactionDetail, 'xml');

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($xml);

        $nganLuong->setClient($this->client);
        $nganLuong->setContainer($this->container);

        $sourceData = [
            'merchant_site_code' => '34923',
            'receiver' => 'cuocdoilenhdenh_911@gmail.com',
            'order_code' => '201409090000000453',
            'amount' => '2000',
            'currency_code' => 'vnd',
            'ref_id' => '5859286-22044a6f9cb72e5b11a0515fc8b594aa',
            'checksum' => '75baaac804955eedfddb694ee14f3f01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.nganLuong.com'
        ];

        $nganLuong->setOptions($sourceData);
        $nganLuong->verifyOrderPayment([]);
    }

    /**
     * 測試返回時對外返回結果錯誤
     */
    public function testReturnConnectionPaymentGatewayError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'error_code: 04, result_description: Checksum code isn\'t right.',
            180130
        );

        $nganLuong = new NganLuong();
        $nganLuong->setPrivateKey('AFQjCNF3VmdM55ri0Uvtc0ioOooYJGiUTQ');

        $transactionDetail = [
            'result_code' => '04',
            'result_description' => 'Checksum code isn\'t right.',
            'transaction_id' => 'NganLuong1234567',
            'amount' => '2000',
            'transaction_status' => '4'
        ];

        $encoders = [new XmlEncoder('root')];
        $normalizers = [new GetSetMethodNormalizer()];

        $serializer = new Serializer($normalizers, $encoders);

        $xml = $serializer->encode($transactionDetail, 'xml');

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($xml);

        $nganLuong->setClient($this->client);
        $nganLuong->setContainer($this->container);

        $sourceData = [
            'merchant_site_code' => '34923',
            'receiver' => 'cuocdoilenhdenh_911@gmail.com',
            'order_code' => '201409090000000453',
            'amount' => '2000',
            'currency_code' => 'vnd',
            'ref_id' => '5859286-22044a6f9cb72e5b11a0515fc8b594aa',
            'checksum' => '75baaac804955eedfddb694ee14f3f01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.nganLuong.com'
        ];

        $nganLuong->setOptions($sourceData);
        $nganLuong->verifyOrderPayment([]);
    }

    /**
     * 測試返回時訂單未支付
     */
    public function testReturnUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $nganLuong = new NganLuong();
        $nganLuong->setPrivateKey('AFQjCNF3VmdM55ri0Uvtc0ioOooYJGiUTQ');

        $transactionDetail = [
            'result_code' => '00',
            'result_description' => 'Thành công',
            'transaction_id' => 'NganLuong1234567',
            'amount' => '2000',
            'transaction_status' => '1'
        ];

        $encoders = [new XmlEncoder('root')];
        $normalizers = [new GetSetMethodNormalizer()];

        $serializer = new Serializer($normalizers, $encoders);

        $xml = $serializer->encode($transactionDetail, 'xml');

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($xml);

        $nganLuong->setClient($this->client);
        $nganLuong->setContainer($this->container);

        $sourceData = [
            'merchant_site_code' => '34923',
            'receiver' => 'cuocdoilenhdenh_911@gmail.com',
            'order_code' => '201409090000000453',
            'amount' => '2000',
            'currency_code' => 'vnd',
            'ref_id' => '5859286-22044a6f9cb72e5b11a0515fc8b594aa',
            'checksum' => '75baaac804955eedfddb694ee14f3f01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.nganLuong.com'
        ];

        $nganLuong->setOptions($sourceData);
        $nganLuong->verifyOrderPayment([]);
    }

    /**
     * 測試返回時回傳訂單處理中
     */
    public function testReturnOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $nganLuong = new NganLuong();
        $nganLuong->setPrivateKey('AFQjCNF3VmdM55ri0Uvtc0ioOooYJGiUTQ');

        $transactionDetail = [
            'result_code' => '00',
            'result_description' => 'Thành công',
            'transaction_id' => 'NganLuong1234567',
            'amount' => '2000',
            'transaction_status' => '2'
        ];

        $encoders = [new XmlEncoder('root')];
        $normalizers = [new GetSetMethodNormalizer()];

        $serializer = new Serializer($normalizers, $encoders);

        $xml = $serializer->encode($transactionDetail, 'xml');

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($xml);

        $nganLuong->setClient($this->client);
        $nganLuong->setContainer($this->container);

        $sourceData = [
            'merchant_site_code' => '34923',
            'receiver' => 'cuocdoilenhdenh_911@gmail.com',
            'order_code' => '201409090000000453',
            'amount' => '2000',
            'currency_code' => 'vnd',
            'ref_id' => '5859286-22044a6f9cb72e5b11a0515fc8b594aa',
            'checksum' => '75baaac804955eedfddb694ee14f3f01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.nganLuong.com'
        ];

        $nganLuong->setOptions($sourceData);
        $nganLuong->verifyOrderPayment([]);
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

        $nganLuong = new NganLuong();
        $nganLuong->setPrivateKey('AFQjCNF3VmdM55ri0Uvtc0ioOooYJGiUTQ');

        $transactionDetail = [
            'result_code' => '00',
            'result_description' => 'Thành công',
            'transaction_id' => 'NganLuong1234567',
            'amount' => '2000',
            'transaction_status' => '3'
        ];

        $encoders = [new XmlEncoder('root')];
        $normalizers = [new GetSetMethodNormalizer()];

        $serializer = new Serializer($normalizers, $encoders);

        $xml = $serializer->encode($transactionDetail, 'xml');

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($xml);

        $nganLuong->setClient($this->client);
        $nganLuong->setContainer($this->container);

        $sourceData = [
            'merchant_site_code' => '34923',
            'receiver' => 'cuocdoilenhdenh_911@gmail.com',
            'order_code' => '201409090000000453',
            'amount' => '2000',
            'currency_code' => 'vnd',
            'ref_id' => '5859286-22044a6f9cb72e5b11a0515fc8b594aa',
            'checksum' => '75baaac804955eedfddb694ee14f3f01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.nganLuong.com'
        ];

        $nganLuong->setOptions($sourceData);
        $nganLuong->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $nganLuong = new NganLuong();
        $nganLuong->setPrivateKey('AFQjCNF3VmdM55ri0Uvtc0ioOooYJGiUTQ');

        $transactionDetail = [
            'result_code' => '00',
            'result_description' => 'Thành công',
            'transaction_id' => 'NganLuong1234567',
            'amount' => '2000',
            'transaction_status' => '4'
        ];

        $encoders = [new XmlEncoder('root')];
        $normalizers = [new GetSetMethodNormalizer()];

        $serializer = new Serializer($normalizers, $encoders);

        $xml = $serializer->encode($transactionDetail, 'xml');

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($xml);

        $nganLuong->setClient($this->client);
        $nganLuong->setContainer($this->container);

        $sourceData = [
            'merchant_site_code' => '34923',
            'receiver' => 'cuocdoilenhdenh_911@gmail.com',
            'order_code' => '201409090000000453',
            'amount' => '2000',
            'currency_code' => 'vnd',
            'ref_id' => '5859286-22044a6f9cb72e5b11a0515fc8b594aa',
            'checksum' => '75baaac804955eedfddb694ee14f3f01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.nganLuong.com'
        ];

        $entry = ['id' => '201409030000000123'];

        $nganLuong->setOptions($sourceData);
        $nganLuong->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $nganLuong = new NganLuong();
        $nganLuong->setPrivateKey('AFQjCNF3VmdM55ri0Uvtc0ioOooYJGiUTQ');

        $transactionDetail = [
            'result_code' => '00',
            'result_description' => 'Thành công',
            'transaction_id' => 'NganLuong1234567',
            'amount' => '2000',
            'transaction_status' => '4'
        ];

        $encoders = [new XmlEncoder('root')];
        $normalizers = [new GetSetMethodNormalizer()];

        $serializer = new Serializer($normalizers, $encoders);

        $xml = $serializer->encode($transactionDetail, 'xml');

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($xml);

        $nganLuong->setClient($this->client);
        $nganLuong->setContainer($this->container);

        $sourceData = [
            'merchant_site_code' => '34923',
            'receiver' => 'cuocdoilenhdenh_911@gmail.com',
            'order_code' => '201409090000000453',
            'amount' => '2000',
            'currency_code' => 'vnd',
            'ref_id' => '5859286-22044a6f9cb72e5b11a0515fc8b594aa',
            'checksum' => '75baaac804955eedfddb694ee14f3f01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.nganLuong.com'
        ];

        $entry = [
            'id' => '201409090000000453',
            'amount' => '1.0000'
        ];

        $nganLuong->setOptions($sourceData);
        $nganLuong->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $nganLuong = new NganLuong();
        $nganLuong->setPrivateKey('AFQjCNF3VmdM55ri0Uvtc0ioOooYJGiUTQ');

        $transactionDetail = [
            'result_code' => '00',
            'result_description' => 'Thành công',
            'transaction_id' => 'NganLuong1234567',
            'amount' => '2000',
            'transaction_status' => '4'
        ];

        $encoders = [new XmlEncoder('root')];
        $normalizers = [new GetSetMethodNormalizer()];

        $serializer = new Serializer($normalizers, $encoders);

        $xml = $serializer->encode($transactionDetail, 'xml');

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($xml);

        $nganLuong->setClient($this->client);
        $nganLuong->setContainer($this->container);

        $sourceData = [
            'merchant_site_code' => '34923',
            'receiver' => 'cuocdoilenhdenh_911@gmail.com',
            'order_code' => '201409090000000453',
            'amount' => '2000',
            'currency_code' => 'vnd',
            'ref_id' => '5859286-22044a6f9cb72e5b11a0515fc8b594aa',
            'checksum' => '75baaac804955eedfddb694ee14f3f01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.nganLuong.com'
        ];

        $entry = [
            'id' => '201409090000000453',
            'amount' => '2000.0000'
        ];

        $nganLuong->setOptions($sourceData);
        $nganLuong->verifyOrderPayment($entry);

        $this->assertEquals('Transaction is Success', $nganLuong->getMsg());
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

        $nganLuong = new NganLuong();
        $nganLuong->paymentTracking();
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

        $nganLuong = new NganLuong();
        $nganLuong->setPrivateKey('AFQjCNF3VmdM55ri0Uvtc0ioOooYJGiUTQ');
        $nganLuong->paymentTracking();
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
            'number' => '34923',
            'orderId' => '201409090000000453',
            'amount' => '2000.0000',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => ''
        ];

        $nganLuong = new NganLuong();
        $nganLuong->setPrivateKey('AFQjCNF3VmdM55ri0Uvtc0ioOooYJGiUTQ');
        $nganLuong->setOptions($sourceData);
        $nganLuong->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數result_code
     */
    public function testPaymentTrackingResultWithoutResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $encoders = [new XmlEncoder('root')];
        $normalizers = [new GetSetMethodNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $xml = $serializer->encode([], 'xml');

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($xml);

        $sourceData = [
            'merchantId' => '12345',
            'number' => '34923',
            'orderId' => '201409090000000453',
            'amount' => '2000.0000',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.nganLuong.com',
            'ref_id' => '5859286-22044a6f9cb72e5b11a0515fc8b594aa',
        ];

        $nganLuong = new NganLuong();
        $nganLuong->setClient($this->client);
        $nganLuong->setContainer($this->container);
        $nganLuong->setPrivateKey('AFQjCNF3VmdM55ri0Uvtc0ioOooYJGiUTQ');
        $nganLuong->setOptions($sourceData);
        $nganLuong->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數result_description
     */
    public function testPaymentTrackingResultWithoutResultDescription()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $transactionDetail = ['result_code' => '04'];

        $encoders = [new XmlEncoder('root')];
        $normalizers = [new GetSetMethodNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $xml = $serializer->encode($transactionDetail, 'xml');

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($xml);

        $sourceData = [
            'merchantId' => '12345',
            'number' => '34923',
            'orderId' => '201409090000000453',
            'amount' => '2000.0000',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.nganLuong.com',
            'ref_id' => '5859286-22044a6f9cb72e5b11a0515fc8b594aa',
        ];

        $nganLuong = new NganLuong();
        $nganLuong->setClient($this->client);
        $nganLuong->setContainer($this->container);
        $nganLuong->setPrivateKey('AFQjCNF3VmdM55ri0Uvtc0ioOooYJGiUTQ');
        $nganLuong->setOptions($sourceData);
        $nganLuong->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數transaction_status
     */
    public function testPaymentTrackingResultWithoutTransactionStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $transactionDetail = [
            'result_code' => '04',
            'result_description' => 'Checksum code isn\'t right.'
        ];

        $encoders = [new XmlEncoder('root')];
        $normalizers = [new GetSetMethodNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $xml = $serializer->encode($transactionDetail, 'xml');

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($xml);

        $sourceData = [
            'merchantId' => '12345',
            'number' => '34923',
            'orderId' => '201409090000000453',
            'amount' => '2000.0000',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.nganLuong.com',
            'ref_id' => '5859286-22044a6f9cb72e5b11a0515fc8b594aa',
        ];

        $nganLuong = new NganLuong();
        $nganLuong->setClient($this->client);
        $nganLuong->setContainer($this->container);
        $nganLuong->setPrivateKey('AFQjCNF3VmdM55ri0Uvtc0ioOooYJGiUTQ');
        $nganLuong->setOptions($sourceData);
        $nganLuong->paymentTracking();
    }

    /**
     * 測試訂單查詢結果缺少回傳參數amount
     */
    public function testPaymentTrackingResultWithoutAmount()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $transactionDetail = [
            'result_code' => '04',
            'result_description' => 'Checksum code isn\'t right.',
            'transaction_status' => '00'
        ];

        $encoders = [new XmlEncoder('root')];
        $normalizers = [new GetSetMethodNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $xml = $serializer->encode($transactionDetail, 'xml');

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($xml);

        $sourceData = [
            'merchantId' => '12345',
            'number' => '34923',
            'orderId' => '201409090000000453',
            'amount' => '2000.0000',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.nganLuong.com',
            'ref_id' => '5859286-22044a6f9cb72e5b11a0515fc8b594aa',
        ];

        $nganLuong = new NganLuong();
        $nganLuong->setClient($this->client);
        $nganLuong->setContainer($this->container);
        $nganLuong->setPrivateKey('AFQjCNF3VmdM55ri0Uvtc0ioOooYJGiUTQ');
        $nganLuong->setOptions($sourceData);
        $nganLuong->paymentTracking();
    }

    /**
     * 測試訂單查詢回傳訂單不存在
     */
    public function testTrackingReturnOrderNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );

        $transactionDetail = [
            'result_code' => '06',
            'result_description' => 'Token không tồn tại',
            'transaction_status' => '',
            'amount' => '2000'
        ];

        $encoders = [new XmlEncoder('root')];
        $normalizers = [new GetSetMethodNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $xml = $serializer->encode($transactionDetail, 'xml');

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($xml);

        $sourceData = [
            'merchantId' => '12345',
            'number' => '34923',
            'orderId' => '201409090000000453',
            'amount' => '2000.0000',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.nganLuong.com',
            'ref_id' => '5859286-22044a6f9cb72e5b11a0515fc8b594aa',
        ];

        $nganLuong = new NganLuong();
        $nganLuong->setClient($this->client);
        $nganLuong->setContainer($this->container);
        $nganLuong->setPrivateKey('AFQjCNF3VmdM55ri0Uvtc0ioOooYJGiUTQ');
        $nganLuong->setOptions($sourceData);
        $nganLuong->paymentTracking();
    }

    /**
     * 測試訂單查詢對外返回結果錯誤
     */
    public function testTrackingReturnConnectionPaymentGatewayError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'error_code: 04, result_description: Checksum code isn\'t right.',
            180123
        );

        $transactionDetail = [
            'result_code' => '04',
            'result_description' => 'Checksum code isn\'t right.',
            'transaction_status' => '00',
            'amount' => '2000'
        ];

        $encoders = [new XmlEncoder('root')];
        $normalizers = [new GetSetMethodNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $xml = $serializer->encode($transactionDetail, 'xml');

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($xml);

        $sourceData = [
            'merchantId' => '12345',
            'number' => '34923',
            'orderId' => '201409090000000453',
            'amount' => '2000.0000',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.nganLuong.com',
            'ref_id' => '5859286-22044a6f9cb72e5b11a0515fc8b594aa',
        ];

        $nganLuong = new NganLuong();
        $nganLuong->setClient($this->client);
        $nganLuong->setContainer($this->container);
        $nganLuong->setPrivateKey('AFQjCNF3VmdM55ri0Uvtc0ioOooYJGiUTQ');
        $nganLuong->setOptions($sourceData);
        $nganLuong->paymentTracking();
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

        $transactionDetail = [
            'result_code' => '00',
            'result_description' => 'Thành công',
            'amount' => '2000',
            'transaction_status' => '1'
        ];

        $encoders = [new XmlEncoder('root')];
        $normalizers = [new GetSetMethodNormalizer()];

        $serializer = new Serializer($normalizers, $encoders);

        $xml = $serializer->encode($transactionDetail, 'xml');

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($xml);

        $sourceData = [
            'merchantId' => '12345',
            'number' => '34923',
            'orderId' => '201409090000000453',
            'amount' => '2000.0000',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.nganLuong.com',
            'ref_id' => '5859286-22044a6f9cb72e5b11a0515fc8b594aa',
        ];

        $nganLuong = new NganLuong();
        $nganLuong->setClient($this->client);
        $nganLuong->setContainer($this->container);
        $nganLuong->setPrivateKey('AFQjCNF3VmdM55ri0Uvtc0ioOooYJGiUTQ');
        $nganLuong->setOptions($sourceData);
        $nganLuong->paymentTracking();
    }

    /**
     * 測試訂單查詢結果回傳訂單處理中
     */
    public function testTrackingReturnOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $transactionDetail = [
            'result_code' => '00',
            'result_description' => 'Thành công',
            'amount' => '2000',
            'transaction_status' => '2'
        ];

        $encoders = [new XmlEncoder('root')];
        $normalizers = [new GetSetMethodNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $xml = $serializer->encode($transactionDetail, 'xml');

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($xml);

        $sourceData = [
            'merchantId' => '12345',
            'number' => '34923',
            'orderId' => '201409090000000453',
            'amount' => '2000.0000',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.nganLuong.com',
            'ref_id' => '5859286-22044a6f9cb72e5b11a0515fc8b594aa',
        ];

        $nganLuong = new NganLuong();
        $nganLuong->setClient($this->client);
        $nganLuong->setContainer($this->container);
        $nganLuong->setPrivateKey('AFQjCNF3VmdM55ri0Uvtc0ioOooYJGiUTQ');
        $nganLuong->setOptions($sourceData);
        $nganLuong->paymentTracking();
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

        $transactionDetail = [
            'result_code' => '00',
            'result_description' => 'Thành công',
            'amount' => '2000',
            'transaction_status' => '3'
        ];

        $encoders = [new XmlEncoder('root')];
        $normalizers = [new GetSetMethodNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $xml = $serializer->encode($transactionDetail, 'xml');

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($xml);

        $sourceData = [
            'merchantId' => '12345',
            'number' => '34923',
            'orderId' => '201409090000000453',
            'amount' => '2000.0000',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.nganLuong.com',
            'ref_id' => '5859286-22044a6f9cb72e5b11a0515fc8b594aa',
        ];

        $nganLuong = new NganLuong();
        $nganLuong->setClient($this->client);
        $nganLuong->setContainer($this->container);
        $nganLuong->setPrivateKey('AFQjCNF3VmdM55ri0Uvtc0ioOooYJGiUTQ');
        $nganLuong->setOptions($sourceData);
        $nganLuong->paymentTracking();
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

        $transactionDetail = [
            'result_code' => '00',
            'result_description' => 'Thành công',
            'amount' => '2000',
            'transaction_status' => '4'
        ];

        $encoders = [new XmlEncoder('root')];
        $normalizers = [new GetSetMethodNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $xml = $serializer->encode($transactionDetail, 'xml');

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($xml);

        $sourceData = [
            'merchantId' => '12345',
            'number' => '34923',
            'orderId' => '201409090000000453',
            'amount' => '10.0000',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.nganLuong.com',
            'ref_id' => '5859286-22044a6f9cb72e5b11a0515fc8b594aa',
        ];

        $nganLuong = new NganLuong();
        $nganLuong->setClient($this->client);
        $nganLuong->setContainer($this->container);
        $nganLuong->setPrivateKey('AFQjCNF3VmdM55ri0Uvtc0ioOooYJGiUTQ');
        $nganLuong->setOptions($sourceData);
        $nganLuong->paymentTracking();
    }

    /**
     * 測試訂單查詢
     */
    public function testPaymentTracking()
    {
        $transactionDetail = [
            'result_code' => '00',
            'result_description' => 'Thành công',
            'amount' => '2000',
            'transaction_status' => '4'
        ];

        $encoders = [new XmlEncoder('root')];
        $normalizers = [new GetSetMethodNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $xml = $serializer->encode($transactionDetail, 'xml');

        $this->client->expects($this->any())
            ->method('call')
            ->willReturn($xml);

        $sourceData = [
            'merchantId' => '12345',
            'number' => '34923',
            'orderId' => '201409090000000453',
            'amount' => '2000.0000',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'www.nganLuong.com',
            'ref_id' => '5859286-22044a6f9cb72e5b11a0515fc8b594aa',
        ];

        $nganLuong = new NganLuong();
        $nganLuong->setClient($this->client);
        $nganLuong->setContainer($this->container);
        $nganLuong->setPrivateKey('AFQjCNF3VmdM55ri0Uvtc0ioOooYJGiUTQ');
        $nganLuong->setOptions($sourceData);
        $nganLuong->paymentTracking();
    }
}
