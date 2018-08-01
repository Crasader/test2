<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\SBH;
use Buzz\Message\Response;

class SBHTest extends DurianTestCase
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
            ->willReturn(null);

        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $this->container->expects($this->any())
            ->method('get')
            ->willReturn($mockLogger);

        $this->client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $this->option = [
            'paymentVendorId' => '1103',
            'number' => 't20180012',
            'notify_url' => 'http://pay.my/pay/reutrn.php',
            'orderId' => '201806270000005767',
            'amount' => '1',
            'ip' => '111.235.135.54',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $this->returnResult = [
            'is_paid' => 'true',
            'merchant_id' => 't20180012',
            'nonce_str' => '1b14a9febe6fddd78d125eb80ecf3d16',
            'notify_time' => '2018-06-27 11:01:42',
            'order_no' => '201806270000005767',
            'out_trade_no' => 'b02d55f1062021d838f058fba801323d',
            'service' => 'SBH_QQ_QR',
            'total_fee' => '1000',
            'sign' => 'F6083D4869E0AC900649998CF34AAC80',
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

        $sBH = new SBH();
        $sBH->getVerifyData();
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

        $sBH = new SBH();
        $sBH->setPrivateKey('test');
        $sBH->getVerifyData();
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

        $sBH = new SBH();
        $sBH->setPrivateKey('test');
        $sBH->setOptions($this->option);
        $sBH->getVerifyData();
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

        $sBH = new SBH();
        $sBH->setPrivateKey('test');
        $sBH->setOptions($this->option);
        $sBH->getVerifyData();
    }

    /**
     * 測試支付時沒有返回result
     */
    public function testPayReturnWithoutResult()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'message' => 'AmountError',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sBH = new SBH();
        $sBH->setContainer($this->container);
        $sBH->setClient($this->client);
        $sBH->setResponse($response);
        $sBH->setPrivateKey('test');
        $sBH->setOptions($this->option);
        $sBH->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'AmountError',
            180130
        );

        $result = [
            'result' => 'fail',
            'message' => 'AmountError',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sBH = new SBH();
        $sBH->setContainer($this->container);
        $sBH->setClient($this->client);
        $sBH->setResponse($response);
        $sBH->setPrivateKey('test');
        $sBH->setOptions($this->option);
        $sBH->getVerifyData();
    }

    /**
     * 測試支付時沒有返回url
     */
    public function testPayReturnWithoutUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'merchant_id' => 't20180012',
            'message' => 'success',
            'order_no' => '201806270000005767',
            'out_trade_no' => 'b02d55f1062021d838f058fba801323d',
            'result' => 'success',
            'total_fee' => 100,
            'sign' => '9A954534A2041D866F130C844ABBDD11',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sBH = new SBH();
        $sBH->setContainer($this->container);
        $sBH->setClient($this->client);
        $sBH->setResponse($response);
        $sBH->setPrivateKey('test');
        $sBH->setOptions($this->option);
        $sBH->getVerifyData();
    }

    /**
     * 測試手機支付時缺少action
     */
    public function testPhonePayWithoutAction()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $this->option['paymentVendorId'] = '1104';

        $result = [
            'merchant_id' => 't20180012',
            'message' => 'success',
            'order_no' => '201806270000005761',
            'out_trade_no' => '77f236a77a557a79ea0e8159d9266f1b',
            'result' => 'success',
            'total_fee' => 100,
            'url' => '<form id="form_table" method="post">' .
                '<input type="hidden" name="merchNo" value="M00000068">' .
                '<input type="hidden" name="notifyUrl" value="http://23.235.141.212:8102/receive/notifyurl.php">' .
                '<input type="hidden" name="orderNo" value="77f236a77a557a79ea0e8159d9266f1b">' .
                '<input type="hidden" name="pageUrl" value="http://23.235.141.212:8102/receive/returnurl.php">' .
                '<input type="hidden" name="productName" value="Test_商品">' .
                '<input type="hidden" name="transAmount" value="1000">' .
                '<input type="hidden" name="sign" value="6d9c8e145c3b690b5d02e7d785032812">' .
                '</form><script>document.getElementById("form_table").submit()</script>',
            'sign' => 'B9EEA2AA28651E38B868A270D1E0406F',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $sBH = new SBH();
        $sBH->setContainer($this->container);
        $sBH->setClient($this->client);
        $sBH->setResponse($response);
        $sBH->setPrivateKey('test');
        $sBH->setOptions($this->option);
        $sBH->getVerifyData();
    }

    /**
     * 測試手機支付時返回表單的input tag內Name屬性沒有值
     */
    public function testPhonePayReturnFormInputNameWithoutValue()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $this->option['paymentVendorId'] = '1104';

        $result = [
            'merchant_id' => 't20180012',
            'message' => 'success',
            'order_no' => '201806270000005761',
            'out_trade_no' => '77f236a77a557a79ea0e8159d9266f1b',
            'result' => 'success',
            'total_fee' => 100,
            'url' => '<form id="form" method="post" action="http://47.94.208.216:8080/app/doQQH5NPay.do">' .
                '<input type="hidden" name="" value="1.0">' .
                '<input type="hidden" name="" value="11009">' .
                '<script>document.getElementById("form").submit();</script></form>',
            'sign' => '44130B7D08E643F6699DD73596FEBB91',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $sBH = new SBH();
        $sBH->setContainer($this->container);
        $sBH->setClient($this->client);
        $sBH->setResponse($response);
        $sBH->setPrivateKey('test');
        $sBH->setOptions($this->option);
        $sBH->getVerifyData();
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $this->option['paymentVendorId'] = '1104';

        $result = [
            'merchant_id' => 't20180012',
            'message' => 'success',
            'order_no' => '201806270000005761',
            'out_trade_no' => '77f236a77a557a79ea0e8159d9266f1b',
            'result' => 'success',
            'total_fee":100',
            'url' => '<form id="form_table" action="http://47.94.208.216:8080/app/doQQH5NPay.do" method="post">' .
                '<input type="hidden" name="merchNo" value="M00000068">' .
                '<input type="hidden" name="notifyUrl" value="http://23.235.141.212:8102/receive/notifyurl.php">' .
                '<input type="hidden" name="orderNo" value="77f236a77a557a79ea0e8159d9266f1b">' .
                '<input type="hidden" name="pageUrl" value="http://23.235.141.212:8102/receive/returnurl.php">' .
                '<input type="hidden" name="productName" value="Test_商品">' .
                '<input type="hidden" name="transAmount" value="1000">' .
                '<input type="hidden" name="sign" value="6d9c8e145c3b690b5d02e7d785032812">' .
                '</form><script>document.getElementById("form_table").submit()</script>',
            'sign' => 'B9EEA2AA28651E38B868A270D1E0406F',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sBH = new SBH();
        $sBH->setContainer($this->container);
        $sBH->setClient($this->client);
        $sBH->setResponse($response);
        $sBH->setPrivateKey('test');
        $sBH->setOptions($this->option);
        $data = $sBH->getVerifyData();

        $this->assertEquals('http://47.94.208.216:8080/app/doQQH5NPay.do', $data['post_url']);
        $this->assertEquals('M00000068', $data['params']['merchNo']);
        $this->assertEquals('http://23.235.141.212:8102/receive/notifyurl.php', $data['params']['notifyUrl']);
        $this->assertEquals('77f236a77a557a79ea0e8159d9266f1b', $data['params']['orderNo']);
        $this->assertEquals('http://23.235.141.212:8102/receive/returnurl.php', $data['params']['pageUrl']);
        $this->assertEquals('Test_商品', $data['params']['productName']);
        $this->assertEquals('1000', $data['params']['transAmount']);
        $this->assertEquals('6d9c8e145c3b690b5d02e7d785032812', $data['params']['sign']);
    }

    /**
     * 測試支付
     */
    public function testPay()
    {

        $result = [
            'merchant_id' => 't20180012',
            'message' => 'success',
            'order_no' => '201806270000005767',
            'out_trade_no' => 'b02d55f1062021d838f058fba801323d',
            'result' => 'success',
            'total_fee' => 100,
            'url' => 'http://pay.cocopay.cc/util/generateQrCodeForKey.do?url=e9ffabef406f7a6bf642a8ca7325abde&type=SCAN',
            'sign' => '9A954534A2041D866F130C844ABBDD11',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $sBH = new SBH();
        $sBH->setContainer($this->container);
        $sBH->setClient($this->client);
        $sBH->setResponse($response);
        $sBH->setPrivateKey('test');
        $sBH->setOptions($this->option);
        $data = $sBH->getVerifyData();

        $this->assertEquals('http://pay.cocopay.cc/util/generateQrCodeForKey.do', $data['post_url']);
        $this->assertEquals('e9ffabef406f7a6bf642a8ca7325abde', $data['params']['url']);
        $this->assertEquals('SCAN', $data['params']['type']);
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

        $sBH = new SBH();
        $sBH->verifyOrderPayment([]);
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

        $sBH = new SBH();
        $sBH->setPrivateKey('test');
        $sBH->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少簽名
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['sign']);

        $sBH = new SBH();
        $sBH->setPrivateKey('test');
        $sBH->setOptions($this->returnResult);
        $sBH->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = '11368DC7DF60F9BA2474DA60FD159152';

        $sBH = new SBH();
        $sBH->setPrivateKey('test');
        $sBH->setOptions($this->returnResult);
        $sBH->verifyOrderPayment([]);
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

        $this->returnResult['is_paid'] = 'false';
        $this->returnResult['sign'] = '2921FD288D2F25F8DF8B815D205E3FD0';

        $sBH = new SBH();
        $sBH->setPrivateKey('test');
        $sBH->setOptions($this->returnResult);
        $sBH->verifyOrderPayment([]);
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

        $entry = ['id' => '201806270000005768'];

        $sBH = new SBH();
        $sBH->setPrivateKey('test');
        $sBH->setOptions($this->returnResult);
        $sBH->verifyOrderPayment($entry);
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
            'id' => '201806270000005767',
            'amount' => '10000',
        ];

        $sBH = new SBH();
        $sBH->setPrivateKey('test');
        $sBH->setOptions($this->returnResult);
        $sBH->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $entry = [
            'id' => '201806270000005767',
            'amount' => '10',
        ];

        $sBH = new SBH();
        $sBH->setPrivateKey('test');
        $sBH->setOptions($this->returnResult);
        $sBH->verifyOrderPayment($entry);

        $this->assertEquals('success', $sBH->getMsg());
    }
}