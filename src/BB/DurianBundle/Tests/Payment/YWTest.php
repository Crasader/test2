<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YW;
use Buzz\Message\Response;

class YWTest extends DurianTestCase
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
            'number' => 'spade99',
            'notify_url' => 'http://pay.my/pay/reutrn.php',
            'orderId' => '201804250000004920',
            'amount' => '5',
            'ip' => '111.235.135.54',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $this->returnResult = [
            'is_paid' => 'true',
            'merchant_id' => 'spade99',
            'nonce_str' => '6fb62acc7a717ecb9b9825278ca7a3c4',
            'notify_time' => '20180425143529',
            'order_no' => '201804250000004920',
            'out_trade_no' => '66614c9876762e5095800a34df40f7',
            'service' => 'YW_Weixin_H5',
            'total_fee' => '500',
            'sign' => 'AFBEF8B1D2A32C9FFD9CB1C8536D06AA',

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

        $yW = new YW();
        $yW->getVerifyData();
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

        $yW = new YW();
        $yW->setPrivateKey('test');
        $yW->getVerifyData();
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

        $yW = new YW();
        $yW->setPrivateKey('test');
        $yW->setOptions($this->option);
        $yW->getVerifyData();
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

        $yW = new YW();
        $yW->setPrivateKey('test');
        $yW->setOptions($this->option);
        $yW->getVerifyData();
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

        $yW = new YW();
        $yW->setContainer($this->container);
        $yW->setClient($this->client);
        $yW->setResponse($response);
        $yW->setPrivateKey('test');
        $yW->setOptions($this->option);
        $yW->getVerifyData();
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

        $yW = new YW();
        $yW->setContainer($this->container);
        $yW->setClient($this->client);
        $yW->setResponse($response);
        $yW->setPrivateKey('test');
        $yW->setOptions($this->option);
        $yW->getVerifyData();
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
            'merchant_id' => 'spade99',
            'message' => 'success',
            'order_no' => '201804250000004920',
            'out_trade_no' => '66614c9876762e5095800a34df40f7',
            'result' => 'success',
            'total_fee' => '500',
            'sign' => '44130B7D08E643F6699DD73596FEBB91',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yW = new YW();
        $yW->setContainer($this->container);
        $yW->setClient($this->client);
        $yW->setResponse($response);
        $yW->setPrivateKey('test');
        $yW->setOptions($this->option);
        $yW->getVerifyData();
    }

    /**
     * 測試支付時缺少action
     */
    public function testPayWithoutAction()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'merchant_id' => 'spade99',
            'message' => 'success',
            'order_no' => '201804250000004920',
            'out_trade_no' => '66614c9876762e5095800a34df40f7',
            'result' => 'success',
            'total_fee' => '500',
            'url' => '<form id="form" method="post"">' .
                '<input type="hidden" name="version" value="1.0">' .
                '<input type="hidden" name="customerid" value="11009">' .
                '<input type="hidden" name="sdorderno" value="66614c9876762e5095800a34df40f7">' .
                '<input type="hidden" name="total_fee" value="5.00">' .
                '<input type="hidden" name="paytype" value="wxh5">' .
                '<input type="hidden" name="notifyurl" value="http://23.235.141.212:8101/receive/notifyurl.php">' .
                '<input type="hidden" name="returnurl" value="http://23.235.141.212:8101/receive/returnurl.php">' .
                '<input type="hidden" name="remark" value="">' .
                '<input type="hidden" name="bankcode" value="">' .
                '<input type="hidden" name="sign" value="498eb17bdc50a707312578d698c551d2">' .
                '<input type="hidden" name="get_code" value="">' .
                '<script>document.getElementById("form").submit();</script></form>',
            'sign' => '44130B7D08E643F6699DD73596FEBB91',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $yW = new YW();
        $yW->setContainer($this->container);
        $yW->setClient($this->client);
        $yW->setResponse($response);
        $yW->setPrivateKey('test');
        $yW->setOptions($this->option);
        $yW->getVerifyData();
    }

    /**
     * 測試支付時返回表單的input tag內Name屬性沒有值
     */
    public function testPayReturnFormInputNameWithoutValue()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'merchant_id' => 'spade99',
            'message' => 'success',
            'order_no' => '201804250000004920',
            'out_trade_no' => '66614c9876762e5095800a34df40f7',
            'result' => 'success',
            'total_fee' => '500',
            'url' => '<form id="form" method="post" action="http://pay.yunweipay.com/apisubmit">' .
                '<input type="hidden" name="" value="1.0">' .
                '<input type="hidden" name="" value="11009">' .
                '<script>document.getElementById("form").submit();</script></form>',
            'sign' => '44130B7D08E643F6699DD73596FEBB91',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $yW = new YW();
        $yW->setContainer($this->container);
        $yW->setClient($this->client);
        $yW->setResponse($response);
        $yW->setPrivateKey('test');
        $yW->setOptions($this->option);
        $yW->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {

        $result = [
            'merchant_id' => 'spade99',
            'message' => 'success',
            'order_no' => '201804250000004920',
            'out_trade_no' => '66614c9876762e5095800a34df40f7',
            'result' => 'success',
            'total_fee' => '500',
            'url' => '<form id="form" method="post" action="http://pay.yunweipay.com/apisubmit">' .
                '<input type="hidden" name="version" value="1.0">' .
                '<input type="hidden" name="customerid" value="11009">' .
                '<input type="hidden" name="sdorderno" value="66614c9876762e5095800a34df40f7">' .
                '<input type="hidden" name="total_fee" value="5.00">' .
                '<input type="hidden" name="paytype" value="wxh5">' .
                '<input type="hidden" name="notifyurl" value="http://23.235.141.212:8101/receive/notifyurl.php">' .
                '<input type="hidden" name="returnurl" value="http://23.235.141.212:8101/receive/returnurl.php">' .
                '<input type="hidden" name="remark" value="">' .
                '<input type="hidden" name="bankcode" value="">' .
                '<input type="hidden" name="sign" value="498eb17bdc50a707312578d698c551d2">' .
                '<input type="hidden" name="get_code" value="">' .
                '<script>document.getElementById("form").submit();</script></form>',
            'sign' => '44130B7D08E643F6699DD73596FEBB91',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $yW = new YW();
        $yW->setContainer($this->container);
        $yW->setClient($this->client);
        $yW->setResponse($response);
        $yW->setPrivateKey('test');
        $yW->setOptions($this->option);
        $data = $yW->getVerifyData();

        $this->assertEquals('http://pay.yunweipay.com/apisubmit', $data['post_url']);
        $this->assertEquals('1.0', $data['params']['version']);
        $this->assertEquals('11009', $data['params']['customerid']);
        $this->assertEquals('66614c9876762e5095800a34df40f7', $data['params']['sdorderno']);
        $this->assertEquals('5.00', $data['params']['total_fee']);
        $this->assertEquals('wxh5', $data['params']['paytype']);
        $this->assertEquals('http://23.235.141.212:8101/receive/notifyurl.php', $data['params']['notifyurl']);
        $this->assertEquals('http://23.235.141.212:8101/receive/returnurl.php', $data['params']['returnurl']);
        $this->assertEquals('', $data['params']['remark']);
        $this->assertEquals('', $data['params']['bankcode']);
        $this->assertEquals('498eb17bdc50a707312578d698c551d2', $data['params']['sign']);
        $this->assertEquals('', $data['params']['get_code']);
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

        $yW = new YW();
        $yW->verifyOrderPayment([]);
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

        $yW = new YW();
        $yW->setPrivateKey('test');
        $yW->verifyOrderPayment([]);
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

        $yW = new YW();
        $yW->setPrivateKey('test');
        $yW->setOptions($this->returnResult);
        $yW->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = 'B5D1C3C932A2BFB540BC83944B32FD1A';

        $yW = new YW();
        $yW->setPrivateKey('test');
        $yW->setOptions($this->returnResult);
        $yW->verifyOrderPayment([]);
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
        $this->returnResult['sign'] = '468692AF5AA252B9E3F92633CBDD7CCE';

        $yW = new YW();
        $yW->setPrivateKey('test');
        $yW->setOptions($this->returnResult);
        $yW->verifyOrderPayment([]);
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

        $entry = ['id' => '201804250000004921'];

        $yW = new YW();
        $yW->setPrivateKey('test');
        $yW->setOptions($this->returnResult);
        $yW->verifyOrderPayment($entry);
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
            'id' => '201804250000004920',
            'amount' => '500',
        ];

        $yW = new YW();
        $yW->setPrivateKey('test');
        $yW->setOptions($this->returnResult);
        $yW->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $entry = [
            'id' => '201804250000004920',
            'amount' => '5',
        ];

        $yW = new YW();
        $yW->setPrivateKey('test');
        $yW->setOptions($this->returnResult);
        $yW->verifyOrderPayment($entry);

        $this->assertEquals('success', $yW->getMsg());
    }
}