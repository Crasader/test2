<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Amxmy;
use Buzz\Message\Response;

class AmxmyTest extends DurianTestCase
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
     * 測試支付時缺少私鑰
     */
    public function testPayWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $amxmy = new Amxmy();
        $amxmy->getVerifyData();
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

        $sourceData = ['number' => ''];

        $amxmy = new Amxmy();
        $amxmy->setPrivateKey('test');
        $amxmy->setOptions($sourceData);
        $amxmy->getVerifyData();
    }

    /**
     * 測試支付時代入支付平台不支援的銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '135325',
            'paymentVendorId' => '7',
            'amount' => '2.00',
            'orderId' => '201705090000002599',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'username' => 'php1test',
            'shop_url' => 'pay.abc.com',
            'notify_url' => 'http://two123.comxa.com/',
        ];

        $amxmy = new Amxmy();
        $amxmy->setPrivateKey('test');
        $amxmy->setOptions($sourceData);
        $amxmy->getVerifyData();
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
            'number' => '135325',
            'paymentVendorId' => '1090',
            'amount' => '2.00',
            'orderId' => '201705090000002599',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'username' => 'php1test',
            'shop_url' => 'pay.abc.com',
            'merchant_extra' => ['src_code' => 'AMJRXE1493023548vDtR9'],
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => '',
        ];

        $amxmy = new Amxmy();
        $amxmy->setPrivateKey('test');
        $amxmy->setOptions($sourceData);
        $amxmy->getVerifyData();
    }

    /**
     * 測試支付時返回缺少respcd
     */
    public function testPayReturnWithoutRespcd()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'respmsg' => 'test',
            'data' => [],
        ];

        $response = new Response();
        $response->setContent(json_encode($result, JSON_UNESCAPED_SLASHES));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $sourceData = [
            'number' => '135325',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201705090000002599',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'username' => 'php1test',
            'shop_url' => 'pay.abc.com',
            'merchant_extra' => ['src_code' => 'AMJRXE1493023548vDtR9'],
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $amxmy = new Amxmy();
        $amxmy->setPrivateKey('test');
        $amxmy->setContainer($this->container);
        $amxmy->setClient($this->client);
        $amxmy->setResponse($response);
        $amxmy->setOptions($sourceData);
        $amxmy->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '\u975e\u6cd5\u7684\u4ea4\u6613\u901a\u9053',
            180130
        );

        $result = [
            'respcd' => '0001',
            'respmsg' => '\u975e\u6cd5\u7684\u4ea4\u6613\u901a\u9053',
            'data' => [],
        ];

        $response = new Response();
        $response->setContent(json_encode($result, JSON_UNESCAPED_SLASHES));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $sourceData = [
            'number' => '135325',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201705090000002599',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'username' => 'php1test',
            'shop_url' => 'pay.abc.com',
            'merchant_extra' => ['src_code' => 'AMJRXE1493023548vDtR9'],
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $amxmy = new Amxmy();
        $amxmy->setPrivateKey('test');
        $amxmy->setContainer($this->container);
        $amxmy->setClient($this->client);
        $amxmy->setResponse($response);
        $amxmy->setOptions($sourceData);
        $amxmy->getVerifyData();
    }

    /**
     * 測試支付時返回缺少sign
     */
    public function testPayReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'respcd' => '0000',
            'respmsg' => '',
            'data' => [
                'trade_no' => '201705044162999889',
                'trade_type' => '50104',
                'time_start' => '20170504121515',
                'pay_time' => '',
                'goods_name' => 'php1test',
                'src_code' => 'AMJRXE1493023548vDtR9',
                'out_trade_no' => '201705090000002599',
                'chnl_code' => '105',
                'total_fee' => '100',
                'pay_params' => 'weixin://wxpay/bizpayurl?pr=AmTZzS0',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result, JSON_UNESCAPED_SLASHES));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $sourceData = [
            'number' => '135325',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201705090000002599',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'username' => 'php1test',
            'shop_url' => 'pay.abc.com',
            'merchant_extra' => ['src_code' => 'AMJRXE1493023548vDtR9'],
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $amxmy = new Amxmy();
        $amxmy->setPrivateKey('test');
        $amxmy->setContainer($this->container);
        $amxmy->setClient($this->client);
        $amxmy->setResponse($response);
        $amxmy->setOptions($sourceData);
        $amxmy->getVerifyData();
    }

    /**
     * 測試支付時返回簽名驗證錯誤
     */
    public function testPayReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $result = [
            'respcd' => '0000',
            'respmsg' => '',
            'data' => [
                'trade_no' => '201705044162999889',
                'trade_type' => '50104',
                'time_start' => '20170504121515',
                'pay_time' => '',
                'goods_name' => 'php1test',
                'src_code' => 'AMJRXE1493023548vDtR9',
                'sign' => 'C0FE74CDC3112A6F4430DF11B94FEDC5',
                'out_trade_no' => '201705090000002599',
                'chnl_code' => '105',
                'total_fee' => '100',
                'pay_params' => 'weixin://wxpay/bizpayurl?pr=AmTZzS0',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result, JSON_UNESCAPED_SLASHES));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $sourceData = [
            'number' => '135325',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201705090000002599',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'username' => 'php1test',
            'shop_url' => 'pay.abc.com',
            'merchant_extra' => ['src_code' => 'AMJRXE1493023548vDtR9'],
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $amxmy = new Amxmy();
        $amxmy->setPrivateKey('test');
        $amxmy->setContainer($this->container);
        $amxmy->setClient($this->client);
        $amxmy->setResponse($response);
        $amxmy->setOptions($sourceData);
        $amxmy->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $result = [
            'respcd' => '0000',
            'respmsg' => '',
            'data' => [
                'trade_no' => '201705044162999889',
                'trade_type' => '50104',
                'time_start' => '20170504121515',
                'pay_time' => '',
                'goods_name' => 'php1test',
                'src_code' => 'AMJRXE1493023548vDtR9',
                'sign' => '3D76A9948CD5A3280B6622E39DF4554C',
                'out_trade_no' => '201705090000002599',
                'chnl_code' => '105',
                'total_fee' => '100',
                'pay_params' => 'weixin://wxpay/bizpayurl?pr=AmTZzS0',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result, JSON_UNESCAPED_SLASHES));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $sourceData = [
            'number' => '135325',
            'paymentVendorId' => '1090',
            'amount' => '1.00',
            'orderId' => '201705090000002599',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'username' => 'php1test',
            'shop_url' => 'pay.abc.com',
            'merchant_extra' => ['src_code' => 'AMJRXE1493023548vDtR9'],
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $amxmy = new Amxmy();
        $amxmy->setPrivateKey('test');
        $amxmy->setContainer($this->container);
        $amxmy->setClient($this->client);
        $amxmy->setResponse($response);
        $amxmy->setOptions($sourceData);
        $verifyData = $amxmy->getVerifyData();

        $this->assertEmpty($verifyData);
        $this->assertSame('weixin://wxpay/bizpayurl?pr=AmTZzS0', $amxmy->getQrcode());
    }

    /**
     * 測試支付返回預付單時pay_params格式錯誤
     */
    public function testPayGetEncodeReturnPayParamsWithError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'respcd' => '0000',
            'respmsg' => '',
            'data' => [
                'trade_no' => '201705044162999889',
                'trade_type' => '50104',
                'time_start' => '20170504121515',
                'pay_time' => '',
                'goods_name' => 'php1test',
                'src_code' => 'AMJRXE1493023548vDtR9',
                'sign' => 'BD79772C3C31657C496892A245083F95',
                'out_trade_no' => '201705090000002599',
                'chnl_code' => '105',
                'total_fee' => '100',
                'pay_params' => 'api.amxmy.top/pay/preview',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result, JSON_UNESCAPED_SLASHES));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $sourceData = [
            'number' => '135325',
            'paymentVendorId' => '1',
            'amount' => '1.00',
            'orderId' => '201705090000002599',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'username' => 'php1test',
            'shop_url' => 'pay.abc.com',
            'merchant_extra' => ['src_code' => 'AMJRXE1493023548vDtR9'],
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $amxmy = new Amxmy();
        $amxmy->setPrivateKey('test');
        $amxmy->setContainer($this->container);
        $amxmy->setClient($this->client);
        $amxmy->setResponse($response);
        $amxmy->setOptions($sourceData);
        $verifyData = $amxmy->getVerifyData();
    }

    /**
     * 測試網銀支付無QueryIndex
     */
    public function testBankPayWithoutQueryIndex()
    {
        $result = [
            'respcd' => '0000',
            'respmsg' => '',
            'data' => [
                'trade_no' => '201705044162999889',
                'trade_type' => '50104',
                'time_start' => '20170504121515',
                'pay_time' => '',
                'goods_name' => 'php1test',
                'src_code' => 'AMJRXE1493023548vDtR9',
                'sign' => '793BDAFAB1F6EA10848AF99EAE59D4D7',
                'out_trade_no' => '201705090000002599',
                'chnl_code' => '105',
                'total_fee' => '100',
                'pay_params' => 'http://api.amxmy.top/pay/preview',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result, JSON_UNESCAPED_SLASHES));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $sourceData = [
            'number' => '135325',
            'paymentVendorId' => '1',
            'amount' => '1.00',
            'orderId' => '201705090000002599',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'username' => 'php1test',
            'shop_url' => 'pay.abc.com',
            'merchant_extra' => ['src_code' => 'AMJRXE1493023548vDtR9'],
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $amxmy = new Amxmy();
        $amxmy->setPrivateKey('test');
        $amxmy->setContainer($this->container);
        $amxmy->setClient($this->client);
        $amxmy->setResponse($response);
        $amxmy->setOptions($sourceData);
        $verifyData = $amxmy->getVerifyData();

        $this->assertEquals('http://api.amxmy.top/pay/preview', $verifyData['post_url']);
    }

    /**
     * 測試網銀支付
     */
    public function testBankPay()
    {
        $result = [
            'respcd' => '0000',
            'respmsg' => '',
            'data' => [
                'trade_no' => '201705044162999889',
                'trade_type' => '50104',
                'time_start' => '20170504121515',
                'pay_time' => '',
                'goods_name' => 'php1test',
                'src_code' => 'AMJRXE1493023548vDtR9',
                'sign' => 'C3D7A536E033A4A8441D4EBE707DF86F',
                'out_trade_no' => '201705090000002599',
                'chnl_code' => '105',
                'total_fee' => '100',
                'pay_params' => 'http://api.amxmy.top/pay/preview?order_no=2017518&sign=364FD6E6',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result, JSON_UNESCAPED_SLASHES));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $sourceData = [
            'number' => '135325',
            'paymentVendorId' => '1',
            'amount' => '1.00',
            'orderId' => '201705090000002599',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'username' => 'php1test',
            'shop_url' => 'pay.abc.com',
            'merchant_extra' => ['src_code' => 'AMJRXE1493023548vDtR9'],
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $amxmy = new Amxmy();
        $amxmy->setPrivateKey('test');
        $amxmy->setContainer($this->container);
        $amxmy->setClient($this->client);
        $amxmy->setResponse($response);
        $amxmy->setOptions($sourceData);
        $verifyData = $amxmy->getVerifyData();

        $this->assertEquals('http://api.amxmy.top/pay/preview', $verifyData['post_url']);
        $this->assertEquals('2017518', $verifyData['params']['order_no']);
        $this->assertEquals('364FD6E6', $verifyData['params']['sign']);
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

        $amxmy = new Amxmy();
        $amxmy->verifyOrderPayment([]);
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

        $amxmy = new Amxmy();
        $amxmy->setPrivateKey('test');
        $amxmy->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'trade_no' => '201705094821000510',
            'trade_type' => '60104',
            'time_start' => '20170509101643',
            'total_fee' => '1',
            'goods_name' => 'php1test',
            'goods_detail' => '',
            'fee_type' => 'CNY',
            'orig_trade_no' => '',
            'mchid' => '135325',
            'pay_time' => '20170509101900',
            'out_mchid' => '',
            'cancel' => '1',
            'order_status' => '3',
            'src_code' => 'AMJRXE1493023548vDtR9',
            'time_expire' => '20170509104643',
            'out_trade_no' => '201705090000002599',
            'order_type' => '1',
        ];

        $amxmy = new Amxmy();
        $amxmy->setPrivateKey('test');
        $amxmy->setOptions($sourceData);
        $amxmy->verifyOrderPayment([]);
    }

    /**
     * 測試返回時sign簽名驗證錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $sourceData = [
            'trade_no' => '201705094821000510',
            'trade_type' => '60104',
            'time_start' => '20170509101643',
            'total_fee' => '1',
            'goods_name' => 'php1test',
            'sign' => '84C8F040EC5CC1C07FFF5F59C6E82D17',
            'goods_detail' => '',
            'fee_type' => 'CNY',
            'orig_trade_no' => '',
            'mchid' => '135325',
            'pay_time' => '20170509101900',
            'out_mchid' => '',
            'cancel' => '1',
            'order_status' => '3',
            'src_code' => 'AMJRXE1493023548vDtR9',
            'time_expire' => '20170509104643',
            'out_trade_no' => '201705090000002599',
            'order_type' => '1',
        ];

        $amxmy = new Amxmy();
        $amxmy->setPrivateKey('test');
        $amxmy->setOptions($sourceData);
        $amxmy->verifyOrderPayment([]);
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

        $sourceData = [
            'trade_no' => '201705094821000510',
            'trade_type' => '60104',
            'time_start' => '20170509101643',
            'total_fee' => '1',
            'goods_name' => 'php1test',
            'sign' => '8860CBE96445A444C55334E20EFE135F',
            'goods_detail' => '',
            'fee_type' => 'CNY',
            'orig_trade_no' => '',
            'mchid' => '135325',
            'pay_time' => '20170509101900',
            'out_mchid' => '',
            'cancel' => '1',
            'order_status' => '2',
            'src_code' => 'AMJRXE1493023548vDtR9',
            'time_expire' => '20170509104643',
            'out_trade_no' => '201705090000002599',
            'order_type' => '1',
        ];

        $amxmy = new Amxmy();
        $amxmy->setPrivateKey('test');
        $amxmy->setOptions($sourceData);
        $amxmy->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單編號錯誤
     */
    public function testReturnOrderIdFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'trade_no' => '201705094821000510',
            'trade_type' => '60104',
            'time_start' => '20170509101643',
            'total_fee' => '1',
            'goods_name' => 'php1test',
            'sign' => '6E63B46D705015A7D0AB55015F5D9C7B',
            'goods_detail' => '',
            'fee_type' => 'CNY',
            'orig_trade_no' => '',
            'mchid' => '135325',
            'pay_time' => '20170509101900',
            'out_mchid' => '',
            'cancel' => '1',
            'order_status' => '3',
            'src_code' => 'AMJRXE1493023548vDtR9',
            'time_expire' => '20170509104643',
            'out_trade_no' => '201705090000002599',
            'order_type' => '1',
        ];

        $entry = ['id' => '201702090000001337'];

        $amxmy = new Amxmy();
        $amxmy->setPrivateKey('test');
        $amxmy->setOptions($sourceData);
        $amxmy->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為訂單金額錯誤
     */
    public function testReturnAmountFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'trade_no' => '201705094821000510',
            'trade_type' => '60104',
            'time_start' => '20170509101643',
            'total_fee' => '1',
            'goods_name' => 'php1test',
            'sign' => '6E63B46D705015A7D0AB55015F5D9C7B',
            'goods_detail' => '',
            'fee_type' => 'CNY',
            'orig_trade_no' => '',
            'mchid' => '135325',
            'pay_time' => '20170509101900',
            'out_mchid' => '',
            'cancel' => '1',
            'order_status' => '3',
            'src_code' => 'AMJRXE1493023548vDtR9',
            'time_expire' => '20170509104643',
            'out_trade_no' => '201705090000002599',
            'order_type' => '1',
        ];

        $entry = [
            'id' => '201705090000002599',
            'amount' => '0.02',
        ];

        $amxmy = new Amxmy();
        $amxmy->setPrivateKey('test');
        $amxmy->setOptions($sourceData);
        $amxmy->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'trade_no' => '201705094821000510',
            'trade_type' => '60104',
            'time_start' => '20170509101643',
            'total_fee' => '1',
            'goods_name' => 'php1test',
            'sign' => '6E63B46D705015A7D0AB55015F5D9C7B',
            'goods_detail' => '',
            'fee_type' => 'CNY',
            'orig_trade_no' => '',
            'mchid' => '135325',
            'pay_time' => '20170509101900',
            'out_mchid' => '',
            'cancel' => '1',
            'order_status' => '3',
            'src_code' => 'AMJRXE1493023548vDtR9',
            'time_expire' => '20170509104643',
            'out_trade_no' => '201705090000002599',
            'order_type' => '1',
        ];

        $entry = [
            'id' => '201705090000002599',
            'amount' => '0.01',
        ];

        $amxmy = new Amxmy();
        $amxmy->setPrivateKey('test');
        $amxmy->setOptions($sourceData);
        $amxmy->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $amxmy->getMsg());
    }

    /**
     * 測試訂單查詢缺少私鑰
     */
    public function testTrackingWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $amxmy = new Amxmy();
        $amxmy->paymentTracking();
    }

    /**
     * 測試訂單查詢加密未指定查詢參數
     */
    public function testTrackingWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $amxmy = new Amxmy();
        $amxmy->setPrivateKey('test');
        $amxmy->paymentTracking();
    }

    /**
     * 測試訂單查詢加密沒有帶入verify_url的情況
     */
    public function testTrackingWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => '135325',
            'orderId' => '201705090000002599',
            'merchant_extra' => ['src_code' => 'AMJRXE1493023548vDtR9'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $amxmy = new Amxmy();
        $amxmy->setPrivateKey('test');
        $amxmy->setOptions($sourceData);
        $amxmy->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為缺少回傳參數
     */
    public function testTrackingReturnWithoutParameter()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = [
            'respmsg' => '',
            'data' => [],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $sourceData = [
            'number' => '135325',
            'orderId' => '201705090000002599',
            'merchant_extra' => ['src_code' => 'AMJRXE1493023548vDtR9'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.amxmy.com',
        ];

        $amxmy = new Amxmy();
        $amxmy->setContainer($this->container);
        $amxmy->setClient($this->client);
        $amxmy->setResponse($response);
        $amxmy->setPrivateKey('test');
        $amxmy->setOptions($sourceData);
        $amxmy->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為請求查詢失敗
     */
    public function testTrackingReturnRequestError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '\u521d\u59cb\u5316\u8ba2\u5355\u4fe1\u606f\u5931\u8d25',
            180123
        );

        $result = [
            'respcd' => '0007',
            'respmsg' => '\u521d\u59cb\u5316\u8ba2\u5355\u4fe1\u606f\u5931\u8d25',
            'data' => [],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $sourceData = [
            'number' => '135325',
            'orderId' => '201705090000002599',
            'merchant_extra' => ['src_code' => 'AMJRXE1493023548vDtR9'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.amxmy.com',
        ];

        $amxmy = new Amxmy();
        $amxmy->setContainer($this->container);
        $amxmy->setClient($this->client);
        $amxmy->setResponse($response);
        $amxmy->setPrivateKey('test');
        $amxmy->setOptions($sourceData);
        $amxmy->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為data缺少參數
     */
    public function testTrackingReturnDataWithoutParameter()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = [
            'respcd' => '0000',
            'respmsg' => '',
            'data' => [
                'trade_no' => '201705044216145449',
                'trade_type' => '50104',
                'time_start' => '20170504175402',
                'total_fee' => '100',
                'goods_name' => 'php1test',
                'goods_detail' => '',
                'order_status' => '3',
                'src_code' => 'AMJRXE1493023548vDtR9',
                'fee_type' => 'CNY',
                'orig_trade_no' => '',
                'mchid' => '135325',
                'pay_time' => '',
                'out_mchid' => '',
                'cancel' => '1',
                'out_trade_no' => '201705090000002599',
                'time_expire' => '20170504182402',
                'order_type' => '1',
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $sourceData = [
            'number' => '135325',
            'orderId' => '201705090000002599',
            'merchant_extra' => ['src_code' => 'AMJRXE1493023548vDtR9'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.amxmy.com',
        ];

        $amxmy = new Amxmy();
        $amxmy->setContainer($this->container);
        $amxmy->setClient($this->client);
        $amxmy->setResponse($response);
        $amxmy->setPrivateKey('test');
        $amxmy->setOptions($sourceData);
        $amxmy->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果Sign為空
     */
    public function testTrackingReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = [
            'respcd' => '0000',
            'respmsg' => '',
            'data' => [
                [
                    'trade_no' => '201705044216145449',
                    'trade_type' => '50104',
                    'time_start' => '20170504175402',
                    'total_fee' => '100',
                    'goods_name' => 'php1test',
                    'goods_detail' => '',
                    'order_status' => '3',
                    'src_code' => 'AMJRXE1493023548vDtR9',
                    'fee_type' => 'CNY',
                    'orig_trade_no' => '',
                    'mchid' => '135325',
                    'pay_time' => '',
                    'out_mchid' => '',
                    'cancel' => '1',
                    'out_trade_no' => '201705090000002599',
                    'time_expire' => '20170504182402',
                    'order_type' => '1',
                ],
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $sourceData = [
            'number' => '135325',
            'orderId' => '201705090000002599',
            'merchant_extra' => ['src_code' => 'AMJRXE1493023548vDtR9'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.amxmy.com',
        ];

        $amxmy = new Amxmy();
        $amxmy->setContainer($this->container);
        $amxmy->setClient($this->client);
        $amxmy->setResponse($response);
        $amxmy->setPrivateKey('test');
        $amxmy->setOptions($sourceData);
        $amxmy->paymentTracking();
    }

    /**
     * 測試訂單查詢返回簽名驗證錯誤
     */
    public function testTrackingReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $result = [
            'respcd' => '0000',
            'respmsg' => '',
            'data' => [
                [
                    'trade_no' => '201705044216145449',
                    'trade_type' => '50104',
                    'time_start' => '20170504175402',
                    'total_fee' => '100',
                    'goods_name' => 'php1test',
                    'goods_detail' => '',
                    'order_status' => '3',
                    'src_code' => 'AMJRXE1493023548vDtR9',
                    'fee_type' => 'CNY',
                    'orig_trade_no' => '',
                    'mchid' => '135325',
                    'pay_time' => '',
                    'out_mchid' => '',
                    'cancel' => '1',
                    'out_trade_no' => '201705090000002599',
                    'sign' => 'B459C248D25FFC09189ADED287B1B741',
                    'time_expire' => '20170504182402',
                    'order_type' => '1',
                ],
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $sourceData = [
            'number' => '135325',
            'orderId' => '201705090000002599',
            'merchant_extra' => ['src_code' => 'AMJRXE1493023548vDtR9'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.amxmy.com',
        ];

        $amxmy = new Amxmy();
        $amxmy->setContainer($this->container);
        $amxmy->setClient($this->client);
        $amxmy->setResponse($response);
        $amxmy->setPrivateKey('test');
        $amxmy->setOptions($sourceData);
        $amxmy->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為訂單未支付
     */
    public function testTrackingReturnUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $result = [
            'respcd' => '0000',
            'respmsg' => '',
            'data' => [
                [
                    'trade_no' => '201705044216145449',
                    'trade_type' => '50104',
                    'time_start' => '20170504175402',
                    'total_fee' => '100',
                    'goods_name' => 'php1test',
                    'goods_detail' => '',
                    'order_status' => '1',
                    'src_code' => 'AMJRXE1493023548vDtR9',
                    'fee_type' => 'CNY',
                    'orig_trade_no' => '',
                    'mchid' => '135325',
                    'pay_time' => '',
                    'out_mchid' => '',
                    'cancel' => '1',
                    'out_trade_no' => '201705090000002599',
                    'sign' => '5439EE8B574F83DCCCD28DDF81B0906C',
                    'time_expire' => '20170504182402',
                    'order_type' => '1',
                ],
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $sourceData = [
            'number' => '135325',
            'orderId' => '201705090000002599',
            'merchant_extra' => ['src_code' => 'AMJRXE1493023548vDtR9'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.amxmy.com',
        ];

        $amxmy = new Amxmy();
        $amxmy->setContainer($this->container);
        $amxmy->setClient($this->client);
        $amxmy->setResponse($response);
        $amxmy->setPrivateKey('test');
        $amxmy->setOptions($sourceData);
        $amxmy->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為訂單處理中
     */
    public function testTrackingReturnOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $result = [
            'respcd' => '0000',
            'respmsg' => '',
            'data' => [
                [
                    'trade_no' => '201705044216145449',
                    'trade_type' => '50104',
                    'time_start' => '20170504175402',
                    'total_fee' => '100',
                    'goods_name' => 'php1test',
                    'goods_detail' => '',
                    'order_status' => '2',
                    'src_code' => 'AMJRXE1493023548vDtR9',
                    'fee_type' => 'CNY',
                    'orig_trade_no' => '',
                    'mchid' => '135325',
                    'pay_time' => '',
                    'out_mchid' => '',
                    'cancel' => '1',
                    'out_trade_no' => '201705090000002599',
                    'sign' => 'C583723EF4EF0E64B0ABB0857090F7BA',
                    'time_expire' => '20170504182402',
                    'order_type' => '1',
                ],
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $sourceData = [
            'number' => '135325',
            'orderId' => '201705090000002599',
            'merchant_extra' => ['src_code' => 'AMJRXE1493023548vDtR9'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.amxmy.com',
        ];

        $amxmy = new Amxmy();
        $amxmy->setContainer($this->container);
        $amxmy->setClient($this->client);
        $amxmy->setResponse($response);
        $amxmy->setPrivateKey('test');
        $amxmy->setOptions($sourceData);
        $amxmy->paymentTracking();
    }

    /**
     * 測試訂單查詢返回支付失敗
     */
    public function testTrackingReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $result = [
            'respcd' => '0000',
            'respmsg' => '',
            'data' => [
                [
                    'trade_no' => '201705044216145449',
                    'trade_type' => '50104',
                    'time_start' => '20170504175402',
                    'total_fee' => '100',
                    'goods_name' => 'php1test',
                    'goods_detail' => '',
                    'order_status' => '4',
                    'src_code' => 'AMJRXE1493023548vDtR9',
                    'fee_type' => 'CNY',
                    'orig_trade_no' => '',
                    'mchid' => '135325',
                    'pay_time' => '',
                    'out_mchid' => '',
                    'cancel' => '1',
                    'out_trade_no' => '201705090000002599',
                    'sign' => '24F09886E25A67C1E193B13B7F24091B',
                    'time_expire' => '20170504182402',
                    'order_type' => '1',
                ],
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $sourceData = [
            'number' => '135325',
            'orderId' => '201705090000002599',
            'merchant_extra' => ['src_code' => 'AMJRXE1493023548vDtR9'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.amxmy.com',
        ];

        $amxmy = new Amxmy();
        $amxmy->setContainer($this->container);
        $amxmy->setClient($this->client);
        $amxmy->setResponse($response);
        $amxmy->setPrivateKey('test');
        $amxmy->setOptions($sourceData);
        $amxmy->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為訂單號錯誤
     */
    public function testTrackingReturnWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $result = [
            'respcd' => '0000',
            'respmsg' => '',
            'data' => [
                [
                    'trade_no' => '201705044216145449',
                    'trade_type' => '50104',
                    'time_start' => '20170504175402',
                    'total_fee' => '100',
                    'goods_name' => 'php1test',
                    'goods_detail' => '',
                    'order_status' => '3',
                    'src_code' => 'AMJRXE1493023548vDtR9',
                    'fee_type' => 'CNY',
                    'orig_trade_no' => '',
                    'mchid' => '135325',
                    'pay_time' => '',
                    'out_mchid' => '',
                    'cancel' => '1',
                    'out_trade_no' => '201705090000002599',
                    'sign' => 'BC849F4213E0A2F8C7D3FEBED0B243FD',
                    'time_expire' => '20170504182402',
                    'order_type' => '1',
                ],
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $sourceData = [
            'number' => '135325',
            'orderId' => '201705090000002598',
            'merchant_extra' => ['src_code' => 'AMJRXE1493023548vDtR9'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.amxmy.com',
        ];

        $amxmy = new Amxmy();
        $amxmy->setContainer($this->container);
        $amxmy->setClient($this->client);
        $amxmy->setResponse($response);
        $amxmy->setPrivateKey('test');
        $amxmy->setOptions($sourceData);
        $amxmy->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為訂單金額錯誤
     */
    public function testTrackingReturnWithOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $result = [
            'respcd' => '0000',
            'respmsg' => '',
            'data' => [
                [
                    'trade_no' => '201705044216145449',
                    'trade_type' => '50104',
                    'time_start' => '20170504175402',
                    'total_fee' => '100',
                    'goods_name' => 'php1test',
                    'goods_detail' => '',
                    'order_status' => '3',
                    'src_code' => 'AMJRXE1493023548vDtR9',
                    'fee_type' => 'CNY',
                    'orig_trade_no' => '',
                    'mchid' => '135325',
                    'pay_time' => '',
                    'out_mchid' => '',
                    'cancel' => '1',
                    'out_trade_no' => '201705090000002599',
                    'sign' => 'BC849F4213E0A2F8C7D3FEBED0B243FD',
                    'time_expire' => '20170504182402',
                    'order_type' => '1',
                ],
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $sourceData = [
            'number' => '135325',
            'orderId' => '201705090000002599',
            'amount' => '2',
            'merchant_extra' => ['src_code' => 'AMJRXE1493023548vDtR9'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.amxmy.com',
        ];

        $amxmy = new Amxmy();
        $amxmy->setContainer($this->container);
        $amxmy->setClient($this->client);
        $amxmy->setResponse($response);
        $amxmy->setPrivateKey('test');
        $amxmy->setOptions($sourceData);
        $amxmy->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $result = [
            'respcd' => '0000',
            'respmsg' => '',
            'data' => [
                [
                    'trade_no' => '201705044216145449',
                    'trade_type' => '50104',
                    'time_start' => '20170504175402',
                    'total_fee' => '100',
                    'goods_name' => 'php1test',
                    'goods_detail' => '',
                    'order_status' => '3',
                    'src_code' => 'AMJRXE1493023548vDtR9',
                    'fee_type' => 'CNY',
                    'orig_trade_no' => '',
                    'mchid' => '135325',
                    'pay_time' => '',
                    'out_mchid' => '',
                    'cancel' => '1',
                    'out_trade_no' => '201705090000002599',
                    'sign' => 'BC849F4213E0A2F8C7D3FEBED0B243FD',
                    'time_expire' => '20170504182402',
                    'order_type' => '1',
                ],
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $sourceData = [
            'number' => '135325',
            'orderId' => '201705090000002599',
            'amount' => '1',
            'merchant_extra' => ['src_code' => 'AMJRXE1493023548vDtR9'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.amxmy.com',
        ];

        $amxmy = new Amxmy();
        $amxmy->setContainer($this->container);
        $amxmy->setClient($this->client);
        $amxmy->setResponse($response);
        $amxmy->setPrivateKey('test');
        $amxmy->setOptions($sourceData);
        $amxmy->paymentTracking();
    }

    /**
     * 測試取得訂單查詢需要的參數時缺少私鑰
     */
    public function testGetPaymentTrackingDataWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $amxmy = new Amxmy();
        $amxmy->getPaymentTrackingData();
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

        $amxmy = new Amxmy();
        $amxmy->setPrivateKey('test');
        $amxmy->getPaymentTrackingData();
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

        $sourceData = [
            'number' => '135325',
            'orderId' => '201705090000002599',
            'merchant_extra' => ['src_code' => 'AMJRXE1493023548vDtR9'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $amxmy = new Amxmy();
        $amxmy->setPrivateKey('test');
        $amxmy->setOptions($sourceData);
        $amxmy->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $sourceData = [
            'number' => '135325',
            'orderId' => '201705090000002599',
            'merchant_extra' => ['src_code' => 'AMJRXE1493023548vDtR9'],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.amxmy.com',
        ];

        $amxmy = new Amxmy();
        $amxmy->setPrivateKey('test');
        $amxmy->setOptions($sourceData);
        $trackingData = $amxmy->getPaymentTrackingData();

        $this->assertEquals($sourceData['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('POST', $trackingData['method']);
        $this->assertEquals('/trade/query', $trackingData['path']);
        $this->assertEquals($sourceData['verify_url'], $trackingData['headers']['Host']);
        $this->assertEquals($sourceData['orderId'], $trackingData['form']['out_trade_no']);
        $this->assertEquals($sourceData['merchant_extra']['src_code'], $trackingData['form']['src_code']);
        $this->assertEquals('84D589AF0FD8EDC400DBF38A38DDE021', $trackingData['form']['sign']);
    }

    /**
     * 測試驗證訂單查詢是否成功時缺少私鑰
     */
    public function testPaymentTrackingVerifyWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $amxmy = new Amxmy();
        $amxmy->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時缺少回傳參數
     */
    public function testPaymentTrackingVerifyWithoutParameter()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = [
            'respmsg' => '',
            'data' => [
                [
                    'trade_no' => '201705044216145449',
                    'trade_type' => '50104',
                    'time_start' => '20170504175402',
                    'total_fee' => '100',
                    'goods_name' => 'php1test',
                    'goods_detail' => '',
                    'order_status' => '3',
                    'src_code' => 'AMJRXE1493023548vDtR9',
                    'fee_type' => 'CNY',
                    'orig_trade_no' => '',
                    'mchid' => '135325',
                    'pay_time' => '',
                    'out_mchid' => '',
                    'cancel' => '1',
                    'out_trade_no' => '201705090000002599',
                    'sign' => 'BC849F4213E0A2F8C7D3FEBED0B243FD',
                    'time_expire' => '20170504182402',
                    'order_type' => '1',
                ],
            ],
        ];

        $sourceData = [
            'number' => '135325',
            'orderId' => '201705090000002599',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.amxmy.com',
            'content' => json_encode($result),
        ];

        $amxmy = new Amxmy();
        $amxmy->setPrivateKey('test');
        $amxmy->setOptions($sourceData);
        $amxmy->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時Sign為空
     */
    public function testPaymentTrackingVerifyWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $result = [
            'respcd' => '0000',
            'respmsg' => '',
            'data' => [
                [
                    'trade_no' => '201705044216145449',
                    'trade_type' => '50104',
                    'time_start' => '20170504175402',
                    'total_fee' => '100',
                    'goods_name' => 'php1test',
                    'goods_detail' => '',
                    'order_status' => '3',
                    'src_code' => 'AMJRXE1493023548vDtR9',
                    'fee_type' => 'CNY',
                    'orig_trade_no' => '',
                    'mchid' => '135325',
                    'pay_time' => '',
                    'out_mchid' => '',
                    'cancel' => '1',
                    'out_trade_no' => '201705090000002599',
                    'time_expire' => '20170504182402',
                    'order_type' => '1',
                ],
            ],
        ];

        $sourceData = [
            'number' => '135325',
            'orderId' => '201705090000002599',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.amxmy.com',
            'content' => json_encode($result),
        ];

        $amxmy = new Amxmy();
        $amxmy->setPrivateKey('test');
        $amxmy->setOptions($sourceData);
        $amxmy->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為簽名驗證錯誤
     */
    public function testPaymentTrackingVerifySignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $result = [
            'respcd' => '0000',
            'respmsg' => '',
            'data' => [
                [
                    'trade_no' => '201705044216145449',
                    'trade_type' => '50104',
                    'time_start' => '20170504175402',
                    'total_fee' => '100',
                    'goods_name' => 'php1test',
                    'goods_detail' => '',
                    'order_status' => '3',
                    'src_code' => 'AMJRXE1493023548vDtR9',
                    'fee_type' => 'CNY',
                    'orig_trade_no' => '',
                    'mchid' => '135325',
                    'pay_time' => '',
                    'out_mchid' => '',
                    'cancel' => '1',
                    'out_trade_no' => '201705090000002599',
                    'sign' => 'BC849F4213E0A2F8C7D3FEBED0B243F',
                    'time_expire' => '20170504182402',
                    'order_type' => '1',
                ],
            ],
        ];

        $sourceData = [
            'number' => '135325',
            'orderId' => '201705090000002599',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.amxmy.com',
            'content' => json_encode($result),
        ];

        $amxmy = new Amxmy();
        $amxmy->setPrivateKey('test');
        $amxmy->setOptions($sourceData);
        $amxmy->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為訂單未支付
     */
    public function testPaymentTrackingVerifyUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $result = [
            'respcd' => '0000',
            'respmsg' => '',
            'data' => [
                [
                    'trade_no' => '201705044216145449',
                    'trade_type' => '50104',
                    'time_start' => '20170504175402',
                    'total_fee' => '100',
                    'goods_name' => 'php1test',
                    'goods_detail' => '',
                    'order_status' => '1',
                    'src_code' => 'AMJRXE1493023548vDtR9',
                    'fee_type' => 'CNY',
                    'orig_trade_no' => '',
                    'mchid' => '135325',
                    'pay_time' => '',
                    'out_mchid' => '',
                    'cancel' => '1',
                    'out_trade_no' => '201705090000002599',
                    'sign' => '5439EE8B574F83DCCCD28DDF81B0906C',
                    'time_expire' => '20170504182402',
                    'order_type' => '1',
                ],
            ],
        ];

        $sourceData = [
            'number' => '135325',
            'orderId' => '201705090000002599',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.amxmy.com',
            'content' => json_encode($result),
        ];

        $amxmy = new Amxmy();
        $amxmy->setPrivateKey('test');
        $amxmy->setOptions($sourceData);
        $amxmy->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為訂單處理中
     */
    public function testPaymentTrackingVerifyOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $result = [
            'respcd' => '0000',
            'respmsg' => '',
            'data' => [
                [
                    'trade_no' => '201705044216145449',
                    'trade_type' => '50104',
                    'time_start' => '20170504175402',
                    'total_fee' => '100',
                    'goods_name' => 'php1test',
                    'goods_detail' => '',
                    'order_status' => '2',
                    'src_code' => 'AMJRXE1493023548vDtR9',
                    'fee_type' => 'CNY',
                    'orig_trade_no' => '',
                    'mchid' => '135325',
                    'pay_time' => '',
                    'out_mchid' => '',
                    'cancel' => '1',
                    'out_trade_no' => '201705090000002599',
                    'sign' => 'C583723EF4EF0E64B0ABB0857090F7BA',
                    'time_expire' => '20170504182402',
                    'order_type' => '1',
                ],
            ],
        ];

        $sourceData = [
            'number' => '135325',
            'orderId' => '201705090000002599',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.amxmy.com',
            'content' => json_encode($result),
        ];

        $amxmy = new Amxmy();
        $amxmy->setPrivateKey('test');
        $amxmy->setOptions($sourceData);
        $amxmy->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為支付失敗
     */
    public function testPaymentTrackingVerifyPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $result = [
            'respcd' => '0000',
            'respmsg' => '',
            'data' => [
                [
                    'trade_no' => '201705044216145449',
                    'trade_type' => '50104',
                    'time_start' => '20170504175402',
                    'total_fee' => '100',
                    'goods_name' => 'php1test',
                    'goods_detail' => '',
                    'order_status' => '4',
                    'src_code' => 'AMJRXE1493023548vDtR9',
                    'fee_type' => 'CNY',
                    'orig_trade_no' => '',
                    'mchid' => '135325',
                    'pay_time' => '',
                    'out_mchid' => '',
                    'cancel' => '1',
                    'out_trade_no' => '201705090000002599',
                    'sign' => '24F09886E25A67C1E193B13B7F24091B',
                    'time_expire' => '20170504182402',
                    'order_type' => '1',
                ],
            ],
        ];

        $sourceData = [
            'number' => '135325',
            'orderId' => '201705090000002599',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.amxmy.com',
            'content' => json_encode($result),
        ];

        $amxmy = new Amxmy();
        $amxmy->setPrivateKey('test');
        $amxmy->setOptions($sourceData);
        $amxmy->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為訂單號錯誤
     */
    public function testPaymentTrackingVerifyWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $result = [
            'respcd' => '0000',
            'respmsg' => '',
            'data' => [
                [
                    'trade_no' => '201705044216145449',
                    'trade_type' => '50104',
                    'time_start' => '20170504175402',
                    'total_fee' => '100',
                    'goods_name' => 'php1test',
                    'goods_detail' => '',
                    'order_status' => '3',
                    'src_code' => 'AMJRXE1493023548vDtR9',
                    'fee_type' => 'CNY',
                    'orig_trade_no' => '',
                    'mchid' => '135325',
                    'pay_time' => '',
                    'out_mchid' => '',
                    'cancel' => '1',
                    'out_trade_no' => '201705090000002599',
                    'sign' => 'BC849F4213E0A2F8C7D3FEBED0B243FD',
                    'time_expire' => '20170504182402',
                    'order_type' => '1',
                ],
            ],
        ];

        $sourceData = [
            'number' => '135325',
            'orderId' => '201705090000002598',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.amxmy.com',
            'content' => json_encode($result),
        ];

        $amxmy = new Amxmy();
        $amxmy->setPrivateKey('test');
        $amxmy->setOptions($sourceData);
        $amxmy->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為訂單金額錯誤
     */
    public function testPaymentTrackingVerifyWithOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $result = [
            'respcd' => '0000',
            'respmsg' => '',
            'data' => [
                [
                    'trade_no' => '201705044216145449',
                    'trade_type' => '50104',
                    'time_start' => '20170504175402',
                    'total_fee' => '100',
                    'goods_name' => 'php1test',
                    'goods_detail' => '',
                    'order_status' => '3',
                    'src_code' => 'AMJRXE1493023548vDtR9',
                    'fee_type' => 'CNY',
                    'orig_trade_no' => '',
                    'mchid' => '135325',
                    'pay_time' => '',
                    'out_mchid' => '',
                    'cancel' => '1',
                    'out_trade_no' => '201705090000002599',
                    'sign' => 'BC849F4213E0A2F8C7D3FEBED0B243FD',
                    'time_expire' => '20170504182402',
                    'order_type' => '1',
                ],
            ],
        ];

        $sourceData = [
            'number' => '135325',
            'orderId' => '201705090000002599',
            'amount' => '2',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.amxmy.com',
            'content' => json_encode($result),
        ];

        $amxmy = new Amxmy();
        $amxmy->setPrivateKey('test');
        $amxmy->setOptions($sourceData);
        $amxmy->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢成功
     */
    public function testPaymentTrackingVerifySuccess()
    {
        $result = [
            'respcd' => '0000',
            'respmsg' => '',
            'data' => [
                [
                    'trade_no' => '201705044216145449',
                    'trade_type' => '50104',
                    'time_start' => '20170504175402',
                    'total_fee' => '100',
                    'goods_name' => 'php1test',
                    'goods_detail' => '',
                    'order_status' => '3',
                    'src_code' => 'AMJRXE1493023548vDtR9',
                    'fee_type' => 'CNY',
                    'orig_trade_no' => '',
                    'mchid' => '135325',
                    'pay_time' => '',
                    'out_mchid' => '',
                    'cancel' => '1',
                    'out_trade_no' => '201705090000002599',
                    'sign' => 'BC849F4213E0A2F8C7D3FEBED0B243FD',
                    'time_expire' => '20170504182402',
                    'order_type' => '1',
                ],
            ],
        ];

        $sourceData = [
            'number' => '135325',
            'orderId' => '201705090000002599',
            'amount' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.pay.amxmy.com',
            'content' => json_encode($result),
        ];

        $amxmy = new Amxmy();
        $amxmy->setPrivateKey('test');
        $amxmy->setOptions($sourceData);
        $amxmy->paymentTrackingVerify();
    }
}
