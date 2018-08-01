<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\QuanTongYunFu;
use Buzz\Message\Response;

class QuanTongYunFuTest extends DurianTestCase
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
     * 測試支付時沒有帶入privateKey的情況
     */
    public function testPayNoPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $quanTongYunFu = new QuanTongYunFu();
        $quanTongYunFu->getVerifyData();
    }

    /**
     * 測試支付時未指定支付參數
     */
    public function testPayNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = ['number' => ''];

        $quanTongYunFu = new QuanTongYunFu();
        $quanTongYunFu->setPrivateKey('1234');
        $quanTongYunFu->setOptions($sourceData);
        $quanTongYunFu->getVerifyData();
    }

    /**
     * 測試支付時帶入不支援的銀行
     */
    public function testPayNotSupportBank()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '63',
            'orderId' => '201805220000013217',
            'amount' => '1',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '999',
        ];

        $quanTongYunFu = new QuanTongYunFu();
        $quanTongYunFu->setPrivateKey('1234');
        $quanTongYunFu->setOptions($sourceData);
        $quanTongYunFu->getVerifyData();
    }

    /**
     * 測試支付時缺少verify_url
     */
    public function testPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => '63',
            'orderId' => '201805220000013217',
            'amount' => '1',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1103',
            'verify_url' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $quanTongYunFu = new QuanTongYunFu();
        $quanTongYunFu->setPrivateKey('test');
        $quanTongYunFu->setOptions($sourceData);
        $quanTongYunFu->getVerifyData();
    }

    /**
     * 測試支付時未返回status
     */
    public function testPayNoReturnStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'type' => 'qqmobile',
            'payImg' => 'http://cashier.hefupal.com/paygate/redirect/ODA1MjQ5OTAyOTk3NDg3MjM1MDcy',
            'Msg' => '订单生成成功',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '63',
            'orderId' => '201805220000013217',
            'amount' => '1',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1103',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $quanTongYunFu = new QuanTongYunFu();
        $quanTongYunFu->setContainer($this->container);
        $quanTongYunFu->setClient($this->client);
        $quanTongYunFu->setResponse($response);
        $quanTongYunFu->setPrivateKey('test');
        $quanTongYunFu->setOptions($sourceData);
        $quanTongYunFu->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '订单生成失败',
            180130
        );

        $result = [
            'type' => 'qqmobile',
            'payImg' => 'http://cashier.hefupal.com/paygate/redirect/ODA1MjQ5OTAyOTk3NDg3MjM1MDcy',
            'status' => '-1',
            'Msg' => '订单生成失败',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '63',
            'orderId' => '201805220000013217',
            'amount' => '1',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1103',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $quanTongYunFu = new QuanTongYunFu();
        $quanTongYunFu->setContainer($this->container);
        $quanTongYunFu->setClient($this->client);
        $quanTongYunFu->setResponse($response);
        $quanTongYunFu->setPrivateKey('test');
        $quanTongYunFu->setOptions($sourceData);
        $quanTongYunFu->getVerifyData();
    }

    /**
     * 測試支付時未返回payImg
     */
    public function testPayNoReturnPayImg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'type' => 'qqmobile',
            'status' => '0',
            'Msg' => '订单生成成功',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '63',
            'orderId' => '201805220000013217',
            'amount' => '1',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1103',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $quanTongYunFu = new QuanTongYunFu();
        $quanTongYunFu->setContainer($this->container);
        $quanTongYunFu->setClient($this->client);
        $quanTongYunFu->setResponse($response);
        $quanTongYunFu->setPrivateKey('test');
        $quanTongYunFu->setOptions($sourceData);
        $quanTongYunFu->getVerifyData();
    }

    /**
     * 測試掃碼支付
     */
    public function testQrcodePay()
    {
        $result = [
            'type' => 'qqmobile',
            'payImg' => 'http://cashier.hefupal.com/paygate/redirect/ODA1MjQ5OTAyOT',
            'status' => '0',
            'Msg' => '订单生成成功',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sourceData = [
            'number' => '63',
            'orderId' => '201805220000013217',
            'amount' => '1',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1103',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $quanTongYunFu = new QuanTongYunFu();
        $quanTongYunFu->setContainer($this->container);
        $quanTongYunFu->setClient($this->client);
        $quanTongYunFu->setResponse($response);
        $quanTongYunFu->setPrivateKey('test');
        $quanTongYunFu->setOptions($sourceData);
        $data = $quanTongYunFu->getVerifyData();

        $this->assertEmpty($data);
        $this->assertSame('http://cashier.hefupal.com/paygate/redirect/ODA1MjQ5OTAyOT', $quanTongYunFu->getQrcode());
    }

    /**
     * 測試支付時PrivateKey長度超過64
     */
    public function testPayWithPrivateKeyLength()
    {
        $sourceData = [
            'number' => '63',
            'orderId' => '201805220000013217',
            'amount' => '1',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '278',
        ];

        $quanTongYunFu = new QuanTongYunFu();
        $quanTongYunFu->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j12345');
        $quanTongYunFu->setOptions($sourceData);
        $encodeData = $quanTongYunFu->getVerifyData();

        $this->assertEquals('Buy', $encodeData['p0_Cmd']);
        $this->assertEquals('63', $encodeData['p1_MerId']);
        $this->assertEquals('201805220000013217', $encodeData['p2_Order']);
        $this->assertEquals('1.00', $encodeData['p3_Amt']);
        $this->assertEquals('CNY', $encodeData['p4_Cur']);
        $this->assertEquals('201805220000013217', $encodeData['p5_Pid']);
        $this->assertEquals('201805220000013217', $encodeData['p6_Pcat']);
        $this->assertEquals('201805220000013217', $encodeData['p7_Pdesc']);
        $this->assertEquals('http://fufutest.000webhostapp.com/pay/', $encodeData['p8_Url']);
        $this->assertEquals('yinlian', $encodeData['pd_FrpId']);
        $this->assertEquals('1', $encodeData['pr_NeedResponse']);
        $this->assertEquals('ef5dbc59dbad433dd9004a53630cc974', $encodeData['hmac']);
    }

    /**
     * 測試加密
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '63',
            'orderId' => '201805220000013217',
            'amount' => '1',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '278',
        ];

        $quanTongYunFu = new QuanTongYunFu();
        $quanTongYunFu->setPrivateKey('1234');
        $quanTongYunFu->setOptions($sourceData);
        $encodeData = $quanTongYunFu->getVerifyData();

        $this->assertEquals('Buy', $encodeData['p0_Cmd']);
        $this->assertEquals('63', $encodeData['p1_MerId']);
        $this->assertEquals('201805220000013217', $encodeData['p2_Order']);
        $this->assertEquals('1.00', $encodeData['p3_Amt']);
        $this->assertEquals('CNY', $encodeData['p4_Cur']);
        $this->assertEquals('201805220000013217', $encodeData['p5_Pid']);
        $this->assertEquals('201805220000013217', $encodeData['p6_Pcat']);
        $this->assertEquals('201805220000013217', $encodeData['p7_Pdesc']);
        $this->assertEquals('http://fufutest.000webhostapp.com/pay/', $encodeData['p8_Url']);
        $this->assertEquals('yinlian', $encodeData['pd_FrpId']);
        $this->assertEquals('1', $encodeData['pr_NeedResponse']);
        $this->assertEquals('ecd4abbe5c739d41f594e5369327ec60', $encodeData['hmac']);
    }

    /**
     * 測試返回時沒有帶入key的情況
     */
    public function testReturnWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $quanTongYunFu = new QuanTongYunFu();
        $quanTongYunFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台未指定返回參數
     */
    public function testReturnWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = ['p1_MerId' => '22'];

        $quanTongYunFu = new QuanTongYunFu();
        $quanTongYunFu->setPrivateKey('1234');
        $quanTongYunFu->setOptions($sourceData);
        $quanTongYunFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時沒有回傳hmac(加密簽名)
     */
    public function testReturnWithoutHmac()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'p1_MerId' => '63',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => 'O20180524111117829963',
            'r3_Amt' => '5.000',
            'r4_Cur' => 'CNY',
            'r5_Pid' => '201805220000013217',
            'r6_Order' => '201805220000013217',
            'r7_Uid' => '',
            'r8_MP' => '201805220000013217',
            'r9_BType' => '2',
            'rb_BankId' => 'qqmobile',
            'ro_BankOrderId' => '',
            'rp_PayDate' => '2018-05-24',
            'rq_CardNo' => '',
            'ru_Trxtime' => '2018-05-24',
        ];

        $quanTongYunFu = new QuanTongYunFu();
        $quanTongYunFu->setPrivateKey('1234');
        $quanTongYunFu->setOptions($sourceData);
        $quanTongYunFu->verifyOrderPayment([]);
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

        $sourceData = [
            'p1_MerId' => '63',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => 'O20180524111117829963',
            'r3_Amt' => '5.000',
            'r4_Cur' => 'CNY',
            'r5_Pid' => '201805220000013217',
            'r6_Order' => '201805220000013217',
            'r7_Uid' => '',
            'r8_MP' => '201805220000013217',
            'r9_BType' => '2',
            'rb_BankId' => 'qqmobile',
            'ro_BankOrderId' => '',
            'rp_PayDate' => '2018-05-24',
            'rq_CardNo' => '',
            'ru_Trxtime' => '2018-05-24',
            'hmac' => '26a1e6f3ee1526b3995bad38ca6fa435',
        ];

        $quanTongYunFu = new QuanTongYunFu();
        $quanTongYunFu->setPrivateKey('1234');
        $quanTongYunFu->setOptions($sourceData);
        $quanTongYunFu->verifyOrderPayment([]);
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

        $sourceData = [
            'p1_MerId' => '63',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '0',
            'r2_TrxId' => 'O20180524111117829963',
            'r3_Amt' => '5.000',
            'r4_Cur' => 'CNY',
            'r5_Pid' => '201805220000013217',
            'r6_Order' => '201805220000013217',
            'r7_Uid' => '',
            'r8_MP' => '201805220000013217',
            'r9_BType' => '2',
            'rb_BankId' => 'qqmobile',
            'ro_BankOrderId' => '',
            'rp_PayDate' => '2018-05-24',
            'rq_CardNo' => '',
            'ru_Trxtime' => '2018-05-24',
            'hmac' => '7b3cb3cb190073fceee4ca4c4236b55b',
        ];

        $quanTongYunFu = new QuanTongYunFu();
        $quanTongYunFu->setPrivateKey('1234');
        $quanTongYunFu->setOptions($sourceData);
        $quanTongYunFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時單號不正確的情況
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'p1_MerId' => '63',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => 'O20180524111117829963',
            'r3_Amt' => '5.000',
            'r4_Cur' => 'CNY',
            'r5_Pid' => '201805220000013217',
            'r6_Order' => '201805220000013217',
            'r7_Uid' => '',
            'r8_MP' => '201805220000013217',
            'r9_BType' => '2',
            'rb_BankId' => 'qqmobile',
            'ro_BankOrderId' => '',
            'rp_PayDate' => '2018-05-24',
            'rq_CardNo' => '',
            'ru_Trxtime' => '2018-05-24',
            'hmac' => '7d5f3ead373a65bf89e1fd41bd95dc94',
        ];

        $entry = ['id' => '201405020016748610'];

        $quanTongYunFu = new QuanTongYunFu();
        $quanTongYunFu->setPrivateKey('1234');
        $quanTongYunFu->setOptions($sourceData);
        $quanTongYunFu->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時金額不正確的情況
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'p1_MerId' => '63',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => 'O20180524111117829963',
            'r3_Amt' => '5.000',
            'r4_Cur' => 'CNY',
            'r5_Pid' => '201805220000013217',
            'r6_Order' => '201805220000013217',
            'r7_Uid' => '',
            'r8_MP' => '201805220000013217',
            'r9_BType' => '2',
            'rb_BankId' => 'qqmobile',
            'ro_BankOrderId' => '',
            'rp_PayDate' => '2018-05-24',
            'rq_CardNo' => '',
            'ru_Trxtime' => '2018-05-24',
            'hmac' => '7d5f3ead373a65bf89e1fd41bd95dc94',
        ];

        $entry = [
            'id' => '201805220000013217',
            'amount' => '9900.0000',
        ];

        $quanTongYunFu = new QuanTongYunFu();
        $quanTongYunFu->setPrivateKey('1234');
        $quanTongYunFu->setOptions($sourceData);
        $quanTongYunFu->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'p1_MerId' => '63',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => 'O20180524111117829963',
            'r3_Amt' => '5.000',
            'r4_Cur' => 'CNY',
            'r5_Pid' => '201805220000013217',
            'r6_Order' => '201805220000013217',
            'r7_Uid' => '',
            'r8_MP' => '201805220000013217',
            'r9_BType' => '2',
            'rb_BankId' => 'qqmobile',
            'ro_BankOrderId' => '',
            'rp_PayDate' => '2018-05-24',
            'rq_CardNo' => '',
            'ru_Trxtime' => '2018-05-24',
            'hmac' => '7d5f3ead373a65bf89e1fd41bd95dc94',
        ];

        $entry = [
            'id' => '201805220000013217',
            'amount' => '5.000',
        ];

        $quanTongYunFu = new QuanTongYunFu();
        $quanTongYunFu->setPrivateKey('1234');
        $quanTongYunFu->setOptions($sourceData);
        $quanTongYunFu->verifyOrderPayment($entry);

        $this->assertEquals('success', $quanTongYunFu->getMsg());
    }
}
