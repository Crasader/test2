<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\YunHuiTong;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class YunHuiTongTest extends DurianTestCase
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

        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();
        $this->container->expects($this->any())
            ->method('get')
            ->willReturn($mockLogger);

        $this->client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $this->option = [
            'number' => '1218020716087336',
            'orderId' => '201807200000012869',
            'paymentVendorId' => '1111',
            'amount' => '1',
            'ip' => '10.123.123.123',
            'notify_url' => 'http://www.seafood.help/',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.120.78.75.130',
        ];

        $aesParams = [
            'amount' => '10.00',
            'code' => '200',
            'bizMsg' => '操作成功',
            'bizCode' => '1',
            'serverRequestNo' => '714362476929232896',
            'requestNo' => '201807200000012869',
            'merchantNo' => '1218020716087336',
            'status' => 'SUCCESS',
        ];

        $this->returnResult = [
            'order_number' => '201807200000012869',
            'bizType' => 'PAY',
            'data' => $this->aesEncrypt($aesParams),
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

        $yunHuiTong = new YunHuiTong();
        $yunHuiTong->getVerifyData();
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

        $yunHuiTong = new YunHuiTong();
        $yunHuiTong->setPrivateKey('test1234567890test');
        $yunHuiTong->getVerifyData();
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

        $this->option['paymentVendorId'] = '9999';

        $yunHuiTong = new YunHuiTong();
        $yunHuiTong->setPrivateKey('test1234567890test');
        $yunHuiTong->setOptions($this->option);
        $yunHuiTong->getVerifyData();
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

        $yunHuiTong = new YunHuiTong();
        $yunHuiTong->setPrivateKey('test1234567890test');
        $yunHuiTong->setOptions($this->option);
        $yunHuiTong->getVerifyData();
    }

    /**
     * 測試支付時沒有返回bizCode
     */
    public function testPayReturnWithoutBizCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [];

        $response = new Response();
        $response->setContent($this->aesEncrypt($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $yunHuiTong = new YunHuiTong();
        $yunHuiTong->setContainer($this->container);
        $yunHuiTong->setClient($this->client);
        $yunHuiTong->setResponse($response);
        $yunHuiTong->setPrivateKey('test1234567890test');
        $yunHuiTong->setOptions($this->option);
        $yunHuiTong->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '交易路由失败',
            180130
        );

        $result = [
            'code' => '200',
            'bizMsg' => '交易路由失败',
            'bizCode' => 'PB000002',
        ];

        $response = new Response();
        $response->setContent($this->aesEncrypt($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $yunHuiTong = new YunHuiTong();
        $yunHuiTong->setContainer($this->container);
        $yunHuiTong->setClient($this->client);
        $yunHuiTong->setResponse($response);
        $yunHuiTong->setPrivateKey('test1234567890test');
        $yunHuiTong->setOptions($this->option);
        $yunHuiTong->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗且無錯誤訊息
     */
    public function testPayReturnNotSuccessAndNoMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $result = [
            'code' => '200',
            'bizCode' => 'PB000002',
        ];

        $response = new Response();
        $response->setContent($this->aesEncrypt($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $yunHuiTong = new YunHuiTong();
        $yunHuiTong->setContainer($this->container);
        $yunHuiTong->setClient($this->client);
        $yunHuiTong->setResponse($response);
        $yunHuiTong->setPrivateKey('test1234567890test');
        $yunHuiTong->setOptions($this->option);
        $yunHuiTong->getVerifyData();
    }

    /**
     * 測試支付時沒有返回codeUrl
     */
    public function testPayReturnWithoutCodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'amount' => '11',
            'code' => '200',
            'bizMsg' => '操作成功',
            'bizCode' => '1',
            'requsetNo' => '201807230000012878',
            'status' => 'PROCESS',
        ];

        $response = new Response();
        $response->setContent($this->aesEncrypt($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $yunHuiTong = new YunHuiTong();
        $yunHuiTong->setContainer($this->container);
        $yunHuiTong->setClient($this->client);
        $yunHuiTong->setResponse($response);
        $yunHuiTong->setPrivateKey('test1234567890test');
        $yunHuiTong->setOptions($this->option);
        $yunHuiTong->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $result = [
            'amount' => '11',
            'code' => '200',
            'bizMsg' => '操作成功',
            'bizCode' => '1',
            'requsetNo' => '201807230000012878',
            'payurl' => 'https://qr.95516.com/00010000/62252493337689950597974747422472',
            'status' => 'PROCESS',
        ];

        $response = new Response();
        $response->setContent($this->aesEncrypt($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $yunHuiTong = new YunHuiTong();
        $yunHuiTong->setContainer($this->container);
        $yunHuiTong->setClient($this->client);
        $yunHuiTong->setResponse($response);
        $yunHuiTong->setPrivateKey('test1234567890test');
        $yunHuiTong->setOptions($this->option);
        $data = $yunHuiTong->getVerifyData();

        $qrcode = 'https://qr.95516.com/00010000/62252493337689950597974747422472';

        $this->assertEmpty($data);
        $this->assertEquals($qrcode, $yunHuiTong->getQrcode());
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $this->option['paymentVendorId'] = '1098';

        $result = [
            'amount' => '11',
            'code' => '200',
            'bizMsg' => '操作成功',
            'bizCode' => '1',
            'requsetNo' => '201807230000012878',
            'payurl' => 'https://qr.alipay.com/bax00463jg2ycaijsc1t40f1',
            'status' => 'PROCESS',
        ];

        $response = new Response();
        $response->setContent($this->aesEncrypt($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $yunHuiTong = new YunHuiTong();
        $yunHuiTong->setContainer($this->container);
        $yunHuiTong->setClient($this->client);
        $yunHuiTong->setResponse($response);
        $yunHuiTong->setPrivateKey('test1234567890test');
        $yunHuiTong->setOptions($this->option);
        $data = $yunHuiTong->getVerifyData();

        $this->assertEquals('https://qr.alipay.com/bax00463jg2ycaijsc1t40f1', $data['post_url']);
        $this->assertEmpty($data['params']);
        $this->assertEquals('GET', $yunHuiTong->getPayMethod());
    }

    /**
     * 測試銀聯在線(快捷)對外返回payurl沒有form表單
     */
    public function testYLWapReturnWithoutForm()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $this->option['paymentVendorId'] = '278';

        $html = "<html>" .
            "<head>" .
            "<meta http-equiv='Content-Type' content='text/html;charset=utf-8'/>" .
            "</head>" .
            "<body>" .
            "</body>" .
            "<script type='text/javascript'>document.pay_form.submit() </script>" .
            "</html>";

        $result = [
            'amount' => '11',
            'code' => '200',
            'bizMsg' => '操作成功',
            'bizCode' => '1',
            'requsetNo' => '201807230000012878',
            'payurl' => urlencode($html),
            'status' => 'PROCESS',
        ];

        $response = new Response();
        $response->setContent($this->aesEncrypt($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $yunHuiTong = new YunHuiTong();
        $yunHuiTong->setContainer($this->container);
        $yunHuiTong->setClient($this->client);
        $yunHuiTong->setResponse($response);
        $yunHuiTong->setPrivateKey('test1234567890test');
        $yunHuiTong->setOptions($this->option);
        $yunHuiTong->getVerifyData();
    }

    /**
     * 測試銀聯在線(快捷)時返回payurl沒有提交網址
     */
    public function testPayReturnWithoutAction()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $this->option['paymentVendorId'] = '278';

        $html = "<html>" .
            "<head>" .
            "<meta http-equiv='Content-Type' content='text/html;charset=utf-8'/>" .
            "</head>" .
            "<body>" .
            "<form id ='pay_form' name='pay_form' action='' method='post' accept-charset='UTF-8'>" .
            "</form>" .
            "</body>" .
            "<script type='text/javascript'>document.pay_form.submit() </script>" .
            "</html>";

        $result = [
            'amount' => '11',
            'code' => '200',
            'bizMsg' => '操作成功',
            'bizCode' => '1',
            'requsetNo' => '201807230000012878',
            'payurl' => urlencode($html),
            'status' => 'PROCESS',
        ];

        $response = new Response();
        $response->setContent($this->aesEncrypt($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $yunHuiTong = new YunHuiTong();
        $yunHuiTong->setContainer($this->container);
        $yunHuiTong->setClient($this->client);
        $yunHuiTong->setResponse($response);
        $yunHuiTong->setPrivateKey('test1234567890test');
        $yunHuiTong->setOptions($this->option);
        $yunHuiTong->getVerifyData();
    }

    /**
     * 測試網銀支付
     */
    public function testPay()
    {
        $this->option['paymentVendorId'] = '278';

        $html = "<html>" .
            "<head>" .
            "<meta http-equiv='Content-Type' content='text/html;charset=utf-8'/>" .
            "</head>" .
            "<body>" .
            "<form id ='pay_form' name='pay_form' action='https://gateway.95516.com/gateway/api/frontTransReq.do' " .
            "method='post' accept-charset='UTF-8'>" .
            "<input type='hidden' name = 'bizType' id='bizType' value='000201'/>" .
            "<input type='hidden' name = 'backUrl' id='backUrl' " .
            "value='ZX|http://144.255.17.145:8288/request/upacInternetBankNotify'/>" .
            "<input type='hidden' name = 'orderId' id='orderId'" .
            "value='UPAC201807200930855000000000000000033024'/>" .
            "<input type='hidden' name = 'signature' id='signature' " .
            "value='kyENk4lyEyV9gvTvtfTx7DmJ+PMpCA0pYqKwb2590jEQNGYOwksqfMdRSMtpZ5W28l8k/FYhIDvFhiwJvSCiVPTBBhJ" .
            "I8Wl7mPFrk0ijhZzRbsJJb3DPCLxAu5Ao26f4JWWsnfekXPP1O0TkaS6nJBD6GU/rXkyIFG5PbKHaIepjVOS7yWM2Ef7L7ebuM" .
            "fuqajrPo3CN4sB+aiA3H/bOHxQFGRqVycoEtpG3iZgsChj/Wf6+4EVfSY9l73Nor4KIV0yxA9VONcjyeYjf58eUiHE2E/c/ach" .
            "9MJWwBaQn3WSyMmZznfc6GNwuk5YYXvm0gFennAVbqXv2CsPPmr1nTA=='/>" .
            "<input type='hidden' name = 'txnSubType' id='txnSubType' value='01'/>" .
            "<input type='hidden' name = 'merName' id='merName' value='北京东方迅成商贸有限公司'/>" .
            "<input type='hidden' name = 'txnType' id='txnType' value='01'/>" .
            "<input type='hidden' name = 'channelType' id='channelType' value='07'/>" .
            "<input type='hidden' name = 'frontUrl' id='frontUrl' " .
            "value='https://cashier.sandpay.com.cn/gateway/api/order/notice/allChannelPay'/>" .
            "<input type='hidden' name = 'certId' id='certId' value='69525006230'/>" .
            "<input type='hidden' name = 'encoding' id='encoding' value='UTF-8'/>" .
            "<input type='hidden' name = 'acqInsCode' id='acqInsCode' value='48273320'/>" .
            "<input type='hidden' name = 'version' id='version' value='5.0.0'/>" .
            "<input type='hidden' name = 'merAbbr' id='merAbbr' value='北京东方迅成商贸'/>" .
            "<input type='hidden' name = 'accessType' id='accessType' value='1'/>" .
            "<input type='hidden' name = 'reqReserved' id='reqReserved' value='00000930'/>" .
            "<input type='hidden' name = 'txnTime' id='txnTime' value='20180720121811'/>" .
            "<input type='hidden' name = 'merId' id='merId' value='000000014929997'/>" .
            "<input type='hidden' name = 'merCatCode' id='merCatCode' value='8999'/>" .
            "<input type='hidden' name = 'currencyCode' id='currencyCode' value='156'/>" .
            "<input type='hidden' name = 'signMethod' id='signMethod' value='01'/>" .
            "<input type='hidden' name = 'txnAmt' id='txnAmt' value='1100'/>" .
            "</form>" .
            "</body>" .
            "<script type='text/javascript'>document.pay_form.submit() </script>" .
            "</html>";

        $result = [
            'amount' => '11',
            'code' => '200',
            'bizMsg' => '操作成功',
            'bizCode' => '1',
            'requsetNo' => '201807230000012878',
            'payurl' => urlencode($html),
            'status' => 'PROCESS',
        ];

        $response = new Response();
        $response->setContent($this->aesEncrypt($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $yunHuiTong = new YunHuiTong();
        $yunHuiTong->setContainer($this->container);
        $yunHuiTong->setClient($this->client);
        $yunHuiTong->setResponse($response);
        $yunHuiTong->setPrivateKey('test1234567890test');
        $yunHuiTong->setOptions($this->option);
        $data = $yunHuiTong->getVerifyData();

        $sign = 'kyENk4lyEyV9gvTvtfTx7DmJ+PMpCA0pYqKwb2590jEQNGYOwksqfMdRSMtpZ5W28l8k/FYhIDvFhiwJvSCiVPTBBhJI8Wl7mP' .
            'Frk0ijhZzRbsJJb3DPCLxAu5Ao26f4JWWsnfekXPP1O0TkaS6nJBD6GU/rXkyIFG5PbKHaIepjVOS7yWM2Ef7L7ebuMfuqajrPo3CN' .
            '4sB+aiA3H/bOHxQFGRqVycoEtpG3iZgsChj/Wf6+4EVfSY9l73Nor4KIV0yxA9VONcjyeYjf58eUiHE2E/c/ach9MJWwBaQn3WSyMm' .
            'Zznfc6GNwuk5YYXvm0gFennAVbqXv2CsPPmr1nTA==';

        $frontUrl = 'https://cashier.sandpay.com.cn/gateway/api/order/notice/allChannelPay';

        $this->assertEquals('https://gateway.95516.com/gateway/api/frontTransReq.do', $data['post_url']);
        $this->assertEquals('000201', $data['params']['bizType']);
        $this->assertEquals(
            'ZX|http://144.255.17.145:8288/request/upacInternetBankNotify',
            $data['params']['backUrl']
        );
        $this->assertEquals('UPAC201807200930855000000000000000033024', $data['params']['orderId']);
        $this->assertEquals($sign, $data['params']['signature']);
        $this->assertEquals('01', $data['params']['txnSubType']);
        $this->assertEquals('北京东方迅成商贸有限公司', $data['params']['merName']);
        $this->assertEquals('01', $data['params']['txnType']);
        $this->assertEquals('07', $data['params']['channelType']);
        $this->assertEquals($frontUrl, $data['params']['frontUrl']);
        $this->assertEquals('69525006230', $data['params']['certId']);
        $this->assertEquals('UTF-8', $data['params']['encoding']);
        $this->assertEquals('48273320', $data['params']['acqInsCode']);
        $this->assertEquals('5.0.0', $data['params']['version']);
        $this->assertEquals('北京东方迅成商贸', $data['params']['merAbbr']);
        $this->assertEquals('1', $data['params']['accessType']);
        $this->assertEquals('00000930', $data['params']['reqReserved']);
        $this->assertEquals('20180720121811', $data['params']['txnTime']);
        $this->assertEquals('000000014929997', $data['params']['merId']);
        $this->assertEquals('8999', $data['params']['merCatCode']);
        $this->assertEquals('156', $data['params']['currencyCode']);
        $this->assertEquals('01', $data['params']['signMethod']);
        $this->assertEquals('1100', $data['params']['txnAmt']);
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

        $yunHuiTong = new YunHuiTong();
        $yunHuiTong->verifyOrderPayment([]);
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

        $yunHuiTong = new YunHuiTong();
        $yunHuiTong->setPrivateKey('test');
        $yunHuiTong->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付失敗
     */
    public function testReturnWithPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $aesParams = [
            'amount' => '10.00',
            'code' => '200',
            'bizMsg' => '操作成功',
            'bizCode' => '1',
            'serverRequestNo' => '714362476929232896',
            'requestNo' => '201807200000012869',
            'merchantNo' => '1218020716087336',
            'status' => 'FAILURE',
        ];

        $this->returnResult['data'] = $this->aesEncrypt($aesParams);

        $yunHuiTong = new YunHuiTong();
        $yunHuiTong->setPrivateKey('test1234567890test');
        $yunHuiTong->setOptions($this->returnResult);
        $yunHuiTong->verifyOrderPayment([]);
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

        $entry = ['id' => '9453'];

        $yunHuiTong = new YunHuiTong();
        $yunHuiTong->setPrivateKey('test1234567890test');
        $yunHuiTong->setOptions($this->returnResult);
        $yunHuiTong->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為金額錯誤
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $entry = [
            'id' => '201807200000012869',
            'amount' => '123',
        ];

        $yunHuiTong = new YunHuiTong();
        $yunHuiTong->setPrivateKey('test1234567890test');
        $yunHuiTong->setOptions($this->returnResult);
        $yunHuiTong->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $entry = [
            'id' => '201807200000012869',
            'amount' => '10',
        ];

        $yunHuiTong = new YunHuiTong();
        $yunHuiTong->setPrivateKey('test1234567890test');
        $yunHuiTong->setOptions($this->returnResult);
        $yunHuiTong->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $yunHuiTong->getMsg());
    }

    /**
     * AES加密
     *
     * @param array $data 待加密資料
     * @return string
     */
    private function aesEncrypt($data)
    {
        // 加密只使用密鑰前16位
        $encodeStr = openssl_encrypt(json_encode($data), 'aes-128-ecb', 'test1234567890te', OPENSSL_RAW_DATA);

        return bin2hex($encodeStr);
    }
}
