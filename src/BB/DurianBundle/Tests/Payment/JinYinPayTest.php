<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\JinYinPay;
use Buzz\Message\Response;

class JinYinPayTest extends DurianTestCase
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

        $mockLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Payment')
            ->disableOriginalConstructor()
            ->setMethods(['record'])
            ->getMock();

        $mockLogger->expects($this->any())
            ->method('record')
            ->will($this->returnValue(null));

        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $this->container->expects($this->any())
            ->method('get')
            ->willReturn($mockLogger);

        $this->client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $this->option = [
            'paymentVendorId' => '1090',
            'number' => '100519145',
            'notify_url' => 'http://pay.my/pay/reutrn.php',
            'orderId' => '201806110000005456',
            'amount' => '1',
            'orderCreateDate' => '2018-06-11 12:00:00',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $this->returnResult = [
            'signData' => '5E336F82387D4C698A861E34474DF979',
            'versionId' => '1.0',
            'orderAmount' => '100',
            'transType' => '008',
            'asynNotifyUrl' => 'https://tingliu.000webhostapp.com/pay/return.php',
            'payTime' => '20180611155640',
            'synNotifyUrl' => 'https://tingliu.000webhostapp.com/pay/return.php',
            'orderStatus' => '01',
            'signType' => 'MD5',
            'merId' => '100519145',
            'payId' => '1006082590207184896',
            'prdOrdNo' => '201806110000005456',
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

        $jinYinPay = new JinYinPay();
        $jinYinPay->getVerifyData();
    }

    /**
     * 測試支付時沒指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $jinYinPay = new JinYinPay();
        $jinYinPay->setPrivateKey('test');
        $jinYinPay->getVerifyData();
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

        $this->option['paymentVendorId'] = '999';

        $jinYinPay = new JinYinPay();
        $jinYinPay->setPrivateKey('test');
        $jinYinPay->setOptions($this->option);
        $jinYinPay->getVerifyData();
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

        $jinYinPay = new JinYinPay();
        $jinYinPay->setPrivateKey('test');
        $jinYinPay->setOptions($this->option);
        $jinYinPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回retCode
     */
    public function testPayReturnWithoutRetCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'signData' => 'C71FC1A263AB4945E882FCC570AD1DC8',
            'code' => '1',
            'qrcode' => 'weixin://wxpay/bizpayurl?appid=wxc8bda2993cd5ef62&mch_id=12730',
            'signType' => 'MD5',
            'platmerord' => '1004695441595502592',
            'serviceName' => '支付申请-扫码支付订单建立',
            'retMsg' => '下单成功',
            'desc' => '下单成功',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $jinYinPay = new JinYinPay();
        $jinYinPay->setContainer($this->container);
        $jinYinPay->setClient($this->client);
        $jinYinPay->setResponse($response);
        $jinYinPay->setPrivateKey('test');
        $jinYinPay->setOptions($this->option);
        $jinYinPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回retMsg
     */
    public function testPayReturnWithoutRetMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'signData' => 'C71FC1A263AB4945E882FCC570AD1DC8',
            'code' => '1',
            'qrcode' => 'weixin://wxpay/bizpayurl?appid=wxc8bda2993cd5ef62&mch_id=12730',
            'signType' => 'MD5',
            'platmerord' => '1004695441595502592',
            'retCode' => '1',
            'serviceName' => '支付申请-扫码支付订单建立',
            'desc' => '下单成功',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $jinYinPay = new JinYinPay();
        $jinYinPay->setContainer($this->container);
        $jinYinPay->setClient($this->client);
        $jinYinPay->setResponse($response);
        $jinYinPay->setPrivateKey('test');
        $jinYinPay->setOptions($this->option);
        $jinYinPay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '抱歉上游库存不足',
            180130
        );

        $result = [
            'code' => '1',
            'retCode' => '1526005',
            'serviceName' => '支付申请-扫码支付订单建立',
            'retMsg' => '抱歉上游库存不足',
            'desc' => '',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $jinYinPay = new JinYinPay();
        $jinYinPay->setContainer($this->container);
        $jinYinPay->setClient($this->client);
        $jinYinPay->setResponse($response);
        $jinYinPay->setPrivateKey('test');
        $jinYinPay->setOptions($this->option);
        $jinYinPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回qrcode
     */
    public function testPayReturnWithoutQrcode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'signData' => 'C71FC1A263AB4945E882FCC570AD1DC8',
            'code' => '1',
            'signType' => 'MD5',
            'platmerord' => '1004695441595502592',
            'retCode' => '1',
            'serviceName' => '支付申请-扫码支付订单建立',
            'retMsg' => '下单成功',
            'desc' => '下单成功',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $jinYinPay = new JinYinPay();
        $jinYinPay->setContainer($this->container);
        $jinYinPay->setClient($this->client);
        $jinYinPay->setResponse($response);
        $jinYinPay->setPrivateKey('test');
        $jinYinPay->setOptions($this->option);
        $jinYinPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $result = [
            'signData' => 'C71FC1A263AB4945E882FCC570AD1DC8',
            'code' => '1',
            'qrcode' => 'weixin://wxpay/bizpayurl?appid=wxc8bda2993cd5ef62&mch_id=12730',
            'signType' => 'MD5',
            'platmerord' => '1004695441595502592',
            'retCode' => '1',
            'serviceName' => '支付申请-扫码支付订单建立',
            'retMsg' => '下单成功',
            'desc' => '下单成功',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $jinYinPay = new JinYinPay();
        $jinYinPay->setContainer($this->container);
        $jinYinPay->setClient($this->client);
        $jinYinPay->setResponse($response);
        $jinYinPay->setPrivateKey('test');
        $jinYinPay->setOptions($this->option);
        $data = $jinYinPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertSame('weixin://wxpay/bizpayurl?appid=wxc8bda2993cd5ef62&mch_id=12730', $jinYinPay->getQrcode());
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $this->option['paymentVendorId'] = '1098';

        $jinYinPay = new JinYinPay();
        $jinYinPay->setPrivateKey('test');
        $jinYinPay->setOptions($this->option);
        $data = $jinYinPay->getVerifyData();

        $this->assertEquals('1.0', $data['versionId']);
        $this->assertEquals('100', $data['orderAmount']);
        $this->assertEquals('20180611120000', $data['orderDate']);
        $this->assertEquals('RMB', $data['currency']);
        $this->assertEquals('0', $data['accountType']);
        $this->assertEquals('008', $data['transType']);
        $this->assertEquals('http://pay.my/pay/reutrn.php', $data['asynNotifyUrl']);
        $this->assertEquals('http://pay.my/pay/reutrn.php', $data['synNotifyUrl']);
        $this->assertEquals('MD5', $data['signType']);
        $this->assertEquals('100519145', $data['merId']);
        $this->assertEquals('201806110000005456', $data['prdOrdNo']);
        $this->assertEquals('00026', $data['payMode']);
        $this->assertEquals('D00', $data['receivableType']);
        $this->assertEquals('100', $data['prdAmt']);
        $this->assertEquals('201806110000005456', $data['prdName']);
        $this->assertEquals('103', $data['tranChannel']);
        $this->assertEquals('6D00273EAABBE8F18063AAA820C38DC4', $data['signData']);
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $this->option['paymentVendorId'] = '1';

        $jinYinPay = new JinYinPay();
        $jinYinPay->setPrivateKey('test');
        $jinYinPay->setOptions($this->option);
        $data = $jinYinPay->getVerifyData();

        $this->assertEquals('1.0', $data['versionId']);
        $this->assertEquals('100', $data['orderAmount']);
        $this->assertEquals('20180611120000', $data['orderDate']);
        $this->assertEquals('RMB', $data['currency']);
        $this->assertEquals('0', $data['accountType']);
        $this->assertEquals('008', $data['transType']);
        $this->assertEquals('http://pay.my/pay/reutrn.php', $data['asynNotifyUrl']);
        $this->assertEquals('http://pay.my/pay/reutrn.php', $data['synNotifyUrl']);
        $this->assertEquals('MD5', $data['signType']);
        $this->assertEquals('100519145', $data['merId']);
        $this->assertEquals('201806110000005456', $data['prdOrdNo']);
        $this->assertEquals('00020', $data['payMode']);
        $this->assertEquals('D00', $data['receivableType']);
        $this->assertEquals('100', $data['prdAmt']);
        $this->assertEquals('201806110000005456', $data['prdName']);
        $this->assertEquals('102', $data['tranChannel']);
        $this->assertEquals('FB1C2B15DA93EB3F190525C362FC4DFF', $data['signData']);
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

        $jinYinPay = new JinYinPay();
        $jinYinPay->verifyOrderPayment([]);
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

        $jinYinPay = new JinYinPay();
        $jinYinPay->setPrivateKey('test');
        $jinYinPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少簽名
     */
    public function testReturnWithoutSignData()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['signData']);

        $jinYinPay = new JinYinPay();
        $jinYinPay->setPrivateKey('test');
        $jinYinPay->setOptions($this->returnResult);
        $jinYinPay->verifyOrderPayment([]);
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

        $this->returnResult['signData'] = '5759E2C3A9EE775923B75C5FA44C2E6F';

        $jinYinPay = new JinYinPay();
        $jinYinPay->setPrivateKey('test');
        $jinYinPay->setOptions($this->returnResult);
        $jinYinPay->verifyOrderPayment([]);
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

        $this->returnResult['orderStatus'] = '02';
        $this->returnResult['signData'] = 'CA9414617033E41EAFB19E115CA9D4FD';

        $jinYinPay = new JinYinPay();
        $jinYinPay->setPrivateKey('test');
        $jinYinPay->setOptions($this->returnResult);
        $jinYinPay->verifyOrderPayment([]);
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

        $entry = ['id' => '201806110000005457'];

        $jinYinPay = new JinYinPay();
        $jinYinPay->setPrivateKey('test');
        $jinYinPay->setOptions($this->returnResult);
        $jinYinPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為訂單金額錯誤
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $entry = [
            'id' => '201806110000005456',
            'amount' => '100',
        ];

        $jinYinPay = new JinYinPay();
        $jinYinPay->setPrivateKey('test');
        $jinYinPay->setOptions($this->returnResult);
        $jinYinPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $entry = [
            'id' => '201806110000005456',
            'amount' => '1',
        ];

        $jinYinPay = new JinYinPay();
        $jinYinPay->setPrivateKey('test');
        $jinYinPay->setOptions($this->returnResult);
        $jinYinPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $jinYinPay->getMsg());
    }
}