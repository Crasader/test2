<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\HuiLiBaoPay;
use Buzz\Message\Response;

class HuiLiBaoPayTest extends DurianTestCase
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
            'paymentVendorId' => '1111',
            'number' => 'mt1527413139940',
            'notify_url' => 'http://pay.my/pay/reutrn.php',
            'orderId' => '201806210000005682',
            'amount' => '1',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $this->returnResult = [
            'retCode' => '0',
            'userId' => 'mt1527413139940',
            'orderNo' => '201806210000005682',
            'transNo' => '15295616424567492605973',
            'payAmt' => '1.00',
            'goodsDesc' => '201806210000005682',
            'sign' => '646b9d0da85351cc530713d2050d08f4',
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

        $huiLiBaoPay = new HuiLiBaoPay();
        $huiLiBaoPay->getVerifyData();
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

        $huiLiBaoPay = new HuiLiBaoPay();
        $huiLiBaoPay->setPrivateKey('test');
        $huiLiBaoPay->getVerifyData();
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

        $huiLiBaoPay = new HuiLiBaoPay();
        $huiLiBaoPay->setPrivateKey('test');
        $huiLiBaoPay->setOptions($this->option);
        $huiLiBaoPay->getVerifyData();
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

        $huiLiBaoPay = new HuiLiBaoPay();
        $huiLiBaoPay->setPrivateKey('test');
        $huiLiBaoPay->setOptions($this->option);
        $huiLiBaoPay->getVerifyData();
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
            'orderNo' => '201806210000005679',
            'sign' => '42318a68d1bd8be7018289a39f1877af',
            'payUrl' => 'https://qr.95516.com/00010000/62026334965734775092636235222043',
            'transNo' => '15295613697517938722575',
            'userId' => 'mt1527413139940',
            'retMsg' => 'success',
            'tradeType' => '71',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $huiLiBaoPay = new HuiLiBaoPay();
        $huiLiBaoPay->setContainer($this->container);
        $huiLiBaoPay->setClient($this->client);
        $huiLiBaoPay->setResponse($response);
        $huiLiBaoPay->setPrivateKey('test');
        $huiLiBaoPay->setOptions($this->option);
        $huiLiBaoPay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '查无转发通道！',
            180130
        );

        $result = [
            'orderNo' => '',
            'sign' => 'a554990f5039022c57b028bf3f5e8e8b',
            'payUrl' => '',
            'retCode' => 1000,
            'transNo' => '',
            'userId' => '',
            'retMsg' => '查无转发通道！',
            'tradeType' => '',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $huiLiBaoPay = new HuiLiBaoPay();
        $huiLiBaoPay->setContainer($this->container);
        $huiLiBaoPay->setClient($this->client);
        $huiLiBaoPay->setResponse($response);
        $huiLiBaoPay->setPrivateKey('test');
        $huiLiBaoPay->setOptions($this->option);
        $huiLiBaoPay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗且未返回retMsg
     */
    public function testPayReturnNotSuccessAndNoReturnRetMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $result = [
            'orderNo' => '',
            'sign' => 'a554990f5039022c57b028bf3f5e8e8b',
            'payUrl' => '',
            'retCode' => 1000,
            'transNo' => '',
            'userId' => '',
            'tradeType' => '',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $huiLiBaoPay = new HuiLiBaoPay();
        $huiLiBaoPay->setContainer($this->container);
        $huiLiBaoPay->setClient($this->client);
        $huiLiBaoPay->setResponse($response);
        $huiLiBaoPay->setPrivateKey('test');
        $huiLiBaoPay->setOptions($this->option);
        $huiLiBaoPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回payUrl
     */
    public function testPayReturnWithoutPayUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'orderNo' => '201806210000005679',
            'sign' => '42318a68d1bd8be7018289a39f1877af',
            'retCode' => 0,
            'transNo' => '15295613697517938722575',
            'userId' => 'mt1527413139940',
            'retMsg' => 'success',
            'tradeType' => '71',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $huiLiBaoPay = new HuiLiBaoPay();
        $huiLiBaoPay->setContainer($this->container);
        $huiLiBaoPay->setClient($this->client);
        $huiLiBaoPay->setResponse($response);
        $huiLiBaoPay->setPrivateKey('test');
        $huiLiBaoPay->setOptions($this->option);
        $huiLiBaoPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {

        $result = [
            'orderNo' => '201806210000005679',
            'sign' => '42318a68d1bd8be7018289a39f1877af',
            'payUrl' => 'https://qr.95516.com/00010000/62026334965734775092636235222043',
            'retCode' => 0,
            'transNo' => '15295613697517938722575',
            'userId' => 'mt1527413139940',
            'retMsg' => 'success',
            'tradeType' => '71',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $huiLiBaoPay = new HuiLiBaoPay();
        $huiLiBaoPay->setContainer($this->container);
        $huiLiBaoPay->setClient($this->client);
        $huiLiBaoPay->setResponse($response);
        $huiLiBaoPay->setPrivateKey('test');
        $huiLiBaoPay->setOptions($this->option);
        $data = $huiLiBaoPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://qr.95516.com/00010000/62026334965734775092636235222043', $huiLiBaoPay->getQrcode());
    }

    /**
     * 測試手機支付未返回action
     */
    public function testPhonePayContentWihtoutAction()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $this->option['paymentVendorId'] = '1088';

        $result = [
            'orderNo' => '201806210000005677',
            'sign' => 'cf3525df42a81b8bfc8e3020e3414445',
            'payUrl' => '<form name="formdata" method="post">',
            'retCode' => 0,
            'transNo' => '15295605096721860193732',
            'userId' => 'mt1527413139940',
            'retMsg' => 'success',
            'tradeType' => '51',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $huiLiBaoPay = new HuiLiBaoPay();
        $huiLiBaoPay->setContainer($this->container);
        $huiLiBaoPay->setClient($this->client);
        $huiLiBaoPay->setResponse($response);
        $huiLiBaoPay->setPrivateKey('test');
        $huiLiBaoPay->setOptions($this->option);
        $huiLiBaoPay->getVerifyData();
    }

    /**
     * 測試網銀支付未返回input元素
     */
    public function testPayReturnWithoutInput()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $this->option['paymentVendorId'] = '1';

        $result = [
            'orderNo' => '201806040000013560',
            'sign' => 'b7f89066ab18dad12846fb991b6c5409',
            'payUrl' => '<form name="punchout_form" method="post" action="https://c.huiLiBaoPaypay.com/quick/pc/index.do">' .
                '<script type="text/javascript">document.getElementById("sform").submit();</script>',
            'transNo' => '15281007905563353757683',
            'retCode' => 0,
            'userId' => 'mt1524818980855',
            'retMsg' => 'success',
            'tradeType' => '21',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $huiLiBaoPay = new HuiLiBaoPay();
        $huiLiBaoPay->setContainer($this->container);
        $huiLiBaoPay->setClient($this->client);
        $huiLiBaoPay->setResponse($response);
        $huiLiBaoPay->setPrivateKey('test');
        $huiLiBaoPay->setOptions($this->option);
        $huiLiBaoPay->getVerifyData();
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $this->option['paymentVendorId'] = '1088';

        $result = [
            'orderNo' => '201806210000005677',
            'sign' => 'cf3525df42a81b8bfc8e3020e3414445',
            'payUrl' => '<form name="formdata" action="https://c.huiLiBaoPaypay.com/newOnlineBank/paymentUnion.do" method="post">' .
                '<input type="hidden" name="merchantId" value="100395"/>' .
                '<input type="hidden" name="merchantOrderNo" value="15295605096943490273348"/>' .
                '<input type="hidden" name="merchantUserId" value="100395"/>' .
                '<input type="hidden" name="payAmount" value="1"/>' .
                '<input type="hidden" name="notifyUrl" value="http://47.52.78.209/hfbnotifyurl_4.do"/>' .
                '<input type="hidden" name="callBackUrl" value="https://tingliu.000webhostapp.com/pay/return.php"/>' .
                '<input type="hidden" name="description" value="201806210000005677"/>' .
                '<input type="hidden" name="productCode" value="HY_B2CUNIONWAP"/>' .
                '<input type="hidden" name="sign" value="a4b50285c34975f4e9d23dd8e3971827"/>' .
                '</form><script>document.forms["formdata"].submit();</script>',
            'retCode' => 0,
            'transNo' => '15295605096721860193732',
            'userId' => 'mt1527413139940',
            'retMsg' => 'success',
            'tradeType' => '51',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $huiLiBaoPay = new HuiLiBaoPay();
        $huiLiBaoPay->setContainer($this->container);
        $huiLiBaoPay->setClient($this->client);
        $huiLiBaoPay->setResponse($response);
        $huiLiBaoPay->setPrivateKey('test');
        $huiLiBaoPay->setOptions($this->option);
        $data = $huiLiBaoPay->getVerifyData();

        $this->assertEquals('https://c.huiLiBaoPaypay.com/newOnlineBank/paymentUnion.do', $data['post_url']);
        $this->assertEquals('100395', $data['params']['merchantId']);
        $this->assertEquals('15295605096943490273348', $data['params']['merchantOrderNo']);
        $this->assertEquals('100395', $data['params']['merchantUserId']);
        $this->assertEquals('1', $data['params']['payAmount']);
        $this->assertEquals('http://47.52.78.209/hfbnotifyurl_4.do', $data['params']['notifyUrl']);
        $this->assertEquals('https://tingliu.000webhostapp.com/pay/return.php', $data['params']['callBackUrl']);
        $this->assertEquals('201806210000005677', $data['params']['description']);
        $this->assertEquals('HY_B2CUNIONWAP', $data['params']['productCode']);
        $this->assertEquals('a4b50285c34975f4e9d23dd8e3971827', $data['params']['sign']);
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

        $huiLiBaoPay = new HuiLiBaoPay();
        $huiLiBaoPay->verifyOrderPayment([]);
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

        $huiLiBaoPay = new HuiLiBaoPay();
        $huiLiBaoPay->setPrivateKey('test');
        $huiLiBaoPay->verifyOrderPayment([]);
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

        $huiLiBaoPay = new HuiLiBaoPay();
        $huiLiBaoPay->setPrivateKey('test');
        $huiLiBaoPay->setOptions($this->returnResult);
        $huiLiBaoPay->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = '3b14a8266827fa61f3b58230e9584973';

        $huiLiBaoPay = new HuiLiBaoPay();
        $huiLiBaoPay->setPrivateKey('test');
        $huiLiBaoPay->setOptions($this->returnResult);
        $huiLiBaoPay->verifyOrderPayment([]);
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

        $this->returnResult['retCode'] = '1';
        $this->returnResult['sign'] = '8baa704887dbf11b5d3a5f256077f106';

        $huiLiBaoPay = new HuiLiBaoPay();
        $huiLiBaoPay->setPrivateKey('test');
        $huiLiBaoPay->setOptions($this->returnResult);
        $huiLiBaoPay->verifyOrderPayment([]);
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

        $entry = ['id' => '201806210000005681'];

        $huiLiBaoPay = new HuiLiBaoPay();
        $huiLiBaoPay->setPrivateKey('test');
        $huiLiBaoPay->setOptions($this->returnResult);
        $huiLiBaoPay->verifyOrderPayment($entry);
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
            'id' => '201806210000005682',
            'amount' => '100',
        ];

        $huiLiBaoPay = new HuiLiBaoPay();
        $huiLiBaoPay->setPrivateKey('test');
        $huiLiBaoPay->setOptions($this->returnResult);
        $huiLiBaoPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $entry = [
            'id' => '201806210000005682',
            'amount' => '1',
        ];

        $huiLiBaoPay = new HuiLiBaoPay();
        $huiLiBaoPay->setPrivateKey('test');
        $huiLiBaoPay->setOptions($this->returnResult);
        $huiLiBaoPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $huiLiBaoPay->getMsg());
    }
}