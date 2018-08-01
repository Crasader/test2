<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\KuangHuiPay;
use Buzz\Message\Response;

class KuangHuiPayTest extends DurianTestCase
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
            'number' => 'mt1522118664970',
            'orderId' => '201806200000005662',
            'amount' => '1',
            'orderCreateDate' => '2018-06-20 16:26:40',
            'paymentVendorId' => '1103',
            'ip' => '111.235.135.54',
            'verify_url' => 'payment.http.orz.zz',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'notify_url' => 'http://orz.zz/pay/reutrn.php',
        ];

        $this->returnResult = [
            'retCode' => '0',
            'userId' => 'mt1522118664970',
            'orderNo' => '201806200000005662',
            'transNo' => '15294832001591721062742',
            'payAmt' => '1',
            'goodsDesc' => '201806200000005662',
            'sign' => '12ee0c39ad17b8dd91163e0ff72af936',
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

        $kuangHuiPay = new KuangHuiPay();
        $kuangHuiPay->getVerifyData();
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

        $kuangHuiPay = new KuangHuiPay();
        $kuangHuiPay->setPrivateKey('test');
        $kuangHuiPay->getVerifyData();
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

        $this->option['paymentVendorId'] = '9999';

        $kuangHuiPay = new KuangHuiPay();
        $kuangHuiPay->setPrivateKey('test');
        $kuangHuiPay->setOptions($this->option);
        $kuangHuiPay->getVerifyData();
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

        $this->option['verify_url'] = '';

        $kuangHuiPay = new KuangHuiPay();
        $kuangHuiPay->setPrivateKey('test');
        $kuangHuiPay->setOptions($this->option);
        $kuangHuiPay->getVerifyData();
    }

    /**
     * 測試支付時未返回retCode
     */
    public function testPayNoReturnRetCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $kuangHuiPay = new KuangHuiPay();
        $kuangHuiPay->setContainer($this->container);
        $kuangHuiPay->setClient($this->client);
        $kuangHuiPay->setResponse($response);
        $kuangHuiPay->setPrivateKey('test');
        $kuangHuiPay->setOptions($this->option);
        $kuangHuiPay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '验证失败！',
            180130
        );

        $result = [
            'retCode' => '10001',
            'retMsg' => '验证失败！',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $kuangHuiPay = new KuangHuiPay();
        $kuangHuiPay->setContainer($this->container);
        $kuangHuiPay->setClient($this->client);
        $kuangHuiPay->setResponse($response);
        $kuangHuiPay->setPrivateKey('test');
        $kuangHuiPay->setOptions($this->option);
        $kuangHuiPay->getVerifyData();
    }

    /**
     * 測試支付時返回沒有retMsg
     */
    public function testPayReturnWithoutRetMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $result = [
            'retCode' => '10001',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $kuangHuiPay = new KuangHuiPay();
        $kuangHuiPay->setContainer($this->container);
        $kuangHuiPay->setClient($this->client);
        $kuangHuiPay->setResponse($response);
        $kuangHuiPay->setPrivateKey('test');
        $kuangHuiPay->setOptions($this->option);
        $kuangHuiPay->getVerifyData();
    }

    /**
     * 測試支付時未返回payUrl
     */
    public function testPayNoReturnPayUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'retCode' => 0,
            'retMsg' => 'success',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $kuangHuiPay = new KuangHuiPay();
        $kuangHuiPay->setContainer($this->container);
        $kuangHuiPay->setClient($this->client);
        $kuangHuiPay->setResponse($response);
        $kuangHuiPay->setPrivateKey('test');
        $kuangHuiPay->setOptions($this->option);
        $kuangHuiPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $payUrl = 'https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027&_bid=2183&t=6Vf200ed46933af1e172a1adade41c9d';

        $result = [
            'orderNo' => '201806200000005662',
            'sign' => '02a9ae9015999b43f1459ef850a7f8f4',
            'payUrl' => $payUrl,
            'retCode' => 0,
            'transNo' => '15294832001591721062742',
            'userId' => 'mt1522118664970',
            'retMsg' => 'success',
            'tradeType' => '21',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $kuangHuiPay = new KuangHuiPay();
        $kuangHuiPay->setContainer($this->container);
        $kuangHuiPay->setClient($this->client);
        $kuangHuiPay->setResponse($response);
        $kuangHuiPay->setPrivateKey('test');
        $kuangHuiPay->setOptions($this->option);
        $verifyData = $kuangHuiPay->getVerifyData();

        $this->assertEmpty($verifyData);
        $this->assertEquals($payUrl, $kuangHuiPay->getQrcode());
    }

    /**
     * 測試網銀支付未返回action
     */
    public function testBankPayContentWihtoutAction()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'retCode' => 0,
            'retMsg' => 'success',
            'payUrl' => '<form name="formdata">',
        ];

        $this->option['paymentVendorId'] = '1';

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $kuangHuiPay = new KuangHuiPay();
        $kuangHuiPay->setContainer($this->container);
        $kuangHuiPay->setClient($this->client);
        $kuangHuiPay->setResponse($response);
        $kuangHuiPay->setPrivateKey('test');
        $kuangHuiPay->setOptions($this->option);
        $kuangHuiPay->getVerifyData();
    }

    /**
     * 測試網銀支付未返回input元素
     */
    public function testBankPayReturnWithoutInput()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $payUrl = '<form name="formdata" action="https://c.heepay.com/quick/pc/index.do" method="post"></form>' .
            '<script>document.forms["formdata"].submit();</script>';

        $result = [
            'retCode' => 0,
            'retMsg' => 'success',
            'payUrl' => $payUrl,
        ];

        $this->option['paymentVendorId'] = '1';

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $kuangHuiPay = new KuangHuiPay();
        $kuangHuiPay->setContainer($this->container);
        $kuangHuiPay->setClient($this->client);
        $kuangHuiPay->setResponse($response);
        $kuangHuiPay->setPrivateKey('test');
        $kuangHuiPay->setOptions($this->option);
        $kuangHuiPay->getVerifyData();
    }

    /**
     * 測試網銀支付
     */
    public function testBankPay()
    {
        $payUrl = '<form name="formdata" action="https://c.heepay.com/quick/pc/index.do" method="post" ' .
            'accept-charset="utf-8" onsubmit="document.charset=\'utf-8\';">' .
            '<input type="hidden" name="merchantId" value="100395"/>' .
            '<input type="hidden" name="merchantOrderNo" value="15294834948399317424247"/>' .
            '<input type="hidden" name="merchantUserId" value="100395"/>' .
            '<input type="hidden" name="productCode" value="HY_B2CEBANKPC"/>' .
            '<input type="hidden" name="payAmount" value="1"/>' .
            '<input type="hidden" name="requestTime" value="20180620163134"/>' .
            '<input type="hidden" name="version" value="1.0"/>' .
            '<input type="hidden" name="notifyUrl" value="http://47.52.78.209/hfbnotifyurl_3.do"/>' .
            '<input type="hidden" name="callBackUrl" value="https://tingliu.000webhostapp.com/pay/return.php"/>' .
            '<input type="hidden" name="description" value="good"/>' .
            '<input type="hidden" name="clientIp" value="47.75.183.85"/>' .
            '<input type="hidden" name="reqHyTime" value="1529483494892"/>' .
            '<input type="hidden" name="sign" value="aa166fed7f764524bbe42c028ee50360"/>' .
            '<input type="hidden" name="onlineType" value="simple"/>' .
            '<input type="hidden" name="bankId" value="102"/>' .
            '<input type="hidden" name="bankName" value="中国工商银行"/>' .
            '<input type="hidden" name="bankCardType" value="SAVING"/></form>' .
            '<script>document.forms["formdata"].submit();</script>';

        $result = [
            'retCode' => 0,
            'retMsg' => 'success',
            'payUrl' => $payUrl,
        ];

        $this->option['paymentVendorId'] = '1';

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $kuangHuiPay = new KuangHuiPay();
        $kuangHuiPay->setContainer($this->container);
        $kuangHuiPay->setClient($this->client);
        $kuangHuiPay->setResponse($response);
        $kuangHuiPay->setPrivateKey('test');
        $kuangHuiPay->setOptions($this->option);
        $verifyData = $kuangHuiPay->getVerifyData();

        $this->assertEquals('https://c.heepay.com/quick/pc/index.do', $verifyData['post_url']);
        $this->assertEquals('100395', $verifyData['params']['merchantId']);
        $this->assertEquals('15294834948399317424247', $verifyData['params']['merchantOrderNo']);
        $this->assertEquals('100395', $verifyData['params']['merchantUserId']);
        $this->assertEquals('HY_B2CEBANKPC', $verifyData['params']['productCode']);
        $this->assertEquals('1', $verifyData['params']['payAmount']);
        $this->assertEquals('20180620163134', $verifyData['params']['requestTime']);
        $this->assertEquals('1.0', $verifyData['params']['version']);
        $this->assertEquals('http://47.52.78.209/hfbnotifyurl_3.do', $verifyData['params']['notifyUrl']);
        $this->assertEquals('https://tingliu.000webhostapp.com/pay/return.php', $verifyData['params']['callBackUrl']);
        $this->assertEquals('good', $verifyData['params']['description']);
        $this->assertEquals('47.75.183.85', $verifyData['params']['clientIp']);
        $this->assertEquals('1529483494892', $verifyData['params']['reqHyTime']);
        $this->assertEquals('aa166fed7f764524bbe42c028ee50360', $verifyData['params']['sign']);
        $this->assertEquals('simple', $verifyData['params']['onlineType']);
        $this->assertEquals('102', $verifyData['params']['bankId']);
        $this->assertEquals('中国工商银行', $verifyData['params']['bankName']);
        $this->assertEquals('SAVING', $verifyData['params']['bankCardType']);
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

        $kuangHuiPay = new KuangHuiPay();
        $kuangHuiPay->verifyOrderPayment([]);
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

        $sourceData = [];

        $kuangHuiPay = new KuangHuiPay();
        $kuangHuiPay->setPrivateKey('test');
        $kuangHuiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳sign(加密簽名)
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['sign']);

        $kuangHuiPay = new KuangHuiPay();
        $kuangHuiPay->setPrivateKey('test');
        $kuangHuiPay->setOptions($this->returnResult);
        $kuangHuiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時加密簽名驗證錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $this->returnResult['sign'] = '123456789';

        $kuangHuiPay = new KuangHuiPay();
        $kuangHuiPay->setPrivateKey('test');
        $kuangHuiPay->setOptions($this->returnResult);
        $kuangHuiPay->verifyOrderPayment([]);
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
        $this->returnResult['sign'] = '6c36e9d82d1d381287e491e26c751b53';

        $kuangHuiPay = new KuangHuiPay();
        $kuangHuiPay->setPrivateKey('test');
        $kuangHuiPay->setOptions($this->returnResult);
        $kuangHuiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單編號錯誤
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $entry = ['id' => '301806200000005662'];

        $kuangHuiPay = new KuangHuiPay();
        $kuangHuiPay->setPrivateKey('test');
        $kuangHuiPay->setOptions($this->returnResult);
        $kuangHuiPay->verifyOrderPayment($entry);
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
            'id' => '201806200000005662',
            'amount' => '15',
        ];

        $kuangHuiPay = new KuangHuiPay();
        $kuangHuiPay->setPrivateKey('test');
        $kuangHuiPay->setOptions($this->returnResult);
        $kuangHuiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回成功
     */
    public function testReturnSuccess()
    {
        $entry = [
            'id' => '201806200000005662',
            'amount' => '1',
        ];

        $kuangHuiPay = new KuangHuiPay();
        $kuangHuiPay->setPrivateKey('test');
        $kuangHuiPay->setOptions($this->returnResult);
        $kuangHuiPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $kuangHuiPay->getMsg());
    }
}
