<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\WangLong;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class WangLongTest extends DurianTestCase
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
            'number' => '9527',
            'orderId' => '201804300000046024',
            'orderCreateDate' => '2018-04-30 15:40:05',
            'amount' => '1',
            'ip' => '10.123.123.123',
            'notify_url' => 'http://www.seafood.help/',
            'paymentVendorId' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.47.93.11.210',
            'merchant_extra' => [
                'OnlineBankSettleCycle' => 'D0',
                'QuickPaySettleCycle' => 'T1',
                'QrcodeSettleCycle' => 'D0',
                'PhonePaySettleCycle' => 'D1',
            ],
        ];

        $this->returnResult = [
            'sign' => '32e7c41375d9b015bd155a2ca8e23c9a',
            'tranNo' => '20180430134048010110009808',
            'orderAmt' => '5.00',
            'outOrderNo' => '201804300000046095',
            'tranTime' => '20180430134048',
            'orderTime' => '20180430134044',
            'sysMerchNo' => '152018041400045',
            'tranAmt' => '5.00',
            'signType' => 'MD5',
            'tranResult' => 'SUCCESS',
            'inputCharset' => 'UTF-8',
            'tranCode' => '0101',
            'tranFeeAmt' => '0.04',
            'tranAttr' => 'DEBIT',
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

        $wangLong = new WangLong();
        $wangLong->getVerifyData();
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

        $wangLong = new WangLong();
        $wangLong->setPrivateKey('test');
        $wangLong->getVerifyData();
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

        $wangLong = new WangLong();
        $wangLong->setPrivateKey('test');
        $wangLong->setOptions($this->option);
        $wangLong->getVerifyData();
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

        $wangLong = new WangLong();
        $wangLong->setPrivateKey('test');
        $wangLong->setOptions($this->option);
        $wangLong->getVerifyData();
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

        $result = [];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $wangLong = new WangLong();
        $wangLong->setContainer($this->container);
        $wangLong->setClient($this->client);
        $wangLong->setResponse($response);
        $wangLong->setPrivateKey('test');
        $wangLong->setOptions($this->option);
        $wangLong->getVerifyData();
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

        $result = ['retCode' => '0000'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $wangLong = new WangLong();
        $wangLong->setContainer($this->container);
        $wangLong->setClient($this->client);
        $wangLong->setResponse($response);
        $wangLong->setPrivateKey('test');
        $wangLong->setOptions($this->option);
        $wangLong->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '非法参数',
            180130
        );

        $result = [
            'retCode' => '1001',
            'retMsg' => '非法参数',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $wangLong = new WangLong();
        $wangLong->setContainer($this->container);
        $wangLong->setClient($this->client);
        $wangLong->setResponse($response);
        $wangLong->setPrivateKey('test');
        $wangLong->setOptions($this->option);
        $wangLong->getVerifyData();
    }

    /**
     * 測試QQ二維支付時沒有返回codeUrl
     */
    public function testQQScanPayReturnWithoutCodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $this->option['paymentVendorId'] = '1103';

        $result = [
            'retCode' => '0000',
            'retMsg' => '操作完成',
            'sysMerchNo' => '152018041400045',
            'sign' => '7963119ebf5c9565afb71d43875b5464',
            'tranNo' => '20180430150316090110009843',
            'tranAmt' => '10.00',
            'outOrderNo' => '201804300000046024',
            'orderStatus' => '01',
            'tranReqNo' => '2000020095',
            'tranDesc' => 'OK',
            'tranAttr' => 'NATIVE',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $wangLong = new WangLong();
        $wangLong->setContainer($this->container);
        $wangLong->setClient($this->client);
        $wangLong->setResponse($response);
        $wangLong->setPrivateKey('test');
        $wangLong->setOptions($this->option);
        $wangLong->getVerifyData();
    }

    /**
     * 測試條碼支付時沒有返回jumpUrl
     */
    public function testBarCodePayReturnWithoutJumpUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $this->option['paymentVendorId'] = '1115';

        $result = [
            'retCode' => '0000',
            'retMsg' => '操作完成',
            'sysMerchNo' => '152018041400045',
            'sign' => '441e8c41e9654c1e105d91376fe0913b',
            'tranNo' => '20180430150630090110009847',
            'tranAmt' => '10.00',
            'outOrderNo' => '201804300000046113',
            'orderStatus' => '01',
            'tranReqNo' => '2000020099',
            'tranDesc' => 'OK',
            'tranAttr' => 'H5',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $wangLong = new WangLong();
        $wangLong->setContainer($this->container);
        $wangLong->setClient($this->client);
        $wangLong->setResponse($response);
        $wangLong->setPrivateKey('test');
        $wangLong->setOptions($this->option);
        $wangLong->getVerifyData();
    }

    /**
     * 測試網銀支付時沒有返回autoSubmitForm
     */
    public function testPayReturnWithoutAutoSubmitForm()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'retCode' => '0000',
            'retMsg' => '操作完成',
            'sysMerchNo' => '152018041400045',
            'sign' => 'c63f883c3cbccc7e83721c30176c2080',
            'tranNo' => '20180430134048010110009808',
            'outOrderNo' => '201804300000046095',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $wangLong = new WangLong();
        $wangLong->setContainer($this->container);
        $wangLong->setClient($this->client);
        $wangLong->setResponse($response);
        $wangLong->setPrivateKey('test');
        $wangLong->setOptions($this->option);
        $wangLong->getVerifyData();
    }

    /**
     * 測試網銀支付時返回autoSubmitForm沒有提交網址
     */
    public function testPayReturnWithoutAction()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $autoSubmitForm = '<body><form id = "sform" action="" method="post">' .
            '<input type="hidden" name="bankCode" id="bankCode" value="102"/>' .
            '<input type="hidden" name="amount" id="amount" value="500"/>' .
            '</form></body>' .
            "<script type=\"text/javascript\">document.getElementById(\"sform\").submit();\n</script>";

        $result = [
            'retCode' => '0000',
            'retMsg' => '操作完成',
            'sysMerchNo' => '152018041400045',
            'sign' => 'c63f883c3cbccc7e83721c30176c2080',
            'autoSubmitForm' => $autoSubmitForm,
            'tranNo' => '20180430134048010110009808',
            'outOrderNo' => '201804300000046095',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $wangLong = new WangLong();
        $wangLong->setContainer($this->container);
        $wangLong->setClient($this->client);
        $wangLong->setResponse($response);
        $wangLong->setPrivateKey('test');
        $wangLong->setOptions($this->option);
        $wangLong->getVerifyData();
    }

    /**
     * 測試QQ二維支付
     */
    public function testQQScanPay()
    {
        $this->option['paymentVendorId'] = '1103';

        $result = [
            'retCode' => '0000',
            'retMsg' => '操作完成',
            'sysMerchNo' => '152018041400045',
            'sign' => '7963119ebf5c9565afb71d43875b5464',
            'tranNo' => '20180430150316090110009843',
            'tranAmt' => '10.00',
            'outOrderNo' => '201804300000046024',
            'orderStatus' => '01',
            'tranReqNo' => '2000020095',
            'tranDesc' => 'OK',
            'codeUrl' => 'https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027&_bid=2183&t=6Vf7b953afba2d95cb3',
            'tranAttr' => 'NATIVE',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $wangLong = new WangLong();
        $wangLong->setContainer($this->container);
        $wangLong->setClient($this->client);
        $wangLong->setResponse($response);
        $wangLong->setPrivateKey('test');
        $wangLong->setOptions($this->option);
        $data = $wangLong->getVerifyData();

        $qrcode = 'https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027&_bid=2183&t=6Vf7b953afba2d95cb3';

        $this->assertEmpty($data);
        $this->assertEquals($qrcode, $wangLong->getQrcode());
    }

    /**
     * 測試條碼支付
     */
    public function testBarCodePay()
    {
        $this->option['paymentVendorId'] = '1115';

        $result = [
            'retCode' => '0000',
            'retMsg' => '操作完成',
            'sysMerchNo' => '152018041400045',
            'sign' => '441e8c41e9654c1e105d91376fe0913b',
            'tranNo' => '20180430150630090110009847',
            'tranAmt' => '10.00',
            'outOrderNo' => '201804300000046113',
            'orderStatus' => '01',
            'tranReqNo' => '2000020099',
            'tranDesc' => 'OK',
            'jumpUrl' => 'http://pay.cocopay.cc/tools/shortUrl.do?key=9c86d043696e1788a84ab2b8ec90a1ab',
            'tranAttr' => 'H5',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $wangLong = new WangLong();
        $wangLong->setContainer($this->container);
        $wangLong->setClient($this->client);
        $wangLong->setResponse($response);
        $wangLong->setPrivateKey('test');
        $wangLong->setOptions($this->option);
        $data = $wangLong->getVerifyData();

        $this->assertEquals('http://pay.cocopay.cc/tools/shortUrl.do', $data['post_url']);
        $this->assertEquals('9c86d043696e1788a84ab2b8ec90a1ab', $data['params']['key']);
        $this->assertEquals('GET', $wangLong->getPayMethod());
    }

    /**
     * 測試網銀支付
     */
    public function testPay()
    {
        $autoSubmitForm = '<body><form id = "sform" action="http://www.dulpay.com/api/pay/net" method="post">' .
            '<input type="hidden" name="bankCode" id="bankCode" value="102"/>' .
            '<input type="hidden" name="amount" id="amount" value="500"/>' .
            '<input type="hidden" name="callBackUrl" id="callBackUrl" ' .
            'value="http://47.93.11.210/noti/payment/chn/front/01/DUL_CHN/DUL_CHN_ME0000000184"/>' .
            '<input type="hidden" name="orderNo" id="orderNo" value="2000020060"/>' .
            '<input type="hidden" name="cardType" id="cardType" value="1"/>' .
            '<input type="hidden" name="sign" id="sign" value="231d3b0b772caa1701f20243f88d5856"/>' .
            '<input type="hidden" name="appNo" id="appNo" value="d6d16396bb8112a46d6ef6e97dbe47ff"/>' .
            '<input type="hidden" name="payType" id="payType" value="gateway"/>' .
            '<input type="hidden" name="notifyUrl" id="notifyUrl" ' .
            'value="http://47.93.11.210/noti/payment/chn/back/01/DUL_CHN/DUL_CHN_ME0000000184"/>' .
            '<input type="hidden" name="goodsTitle" id="goodsTitle" value="201804300000046095"/>' .
            '<input type="hidden" name="currency" id="currency" value="CNY"/>' .
            '<input type="hidden" name="merchantNo" id="merchantNo" value="ME0000000184"/>' .
            '<input type="hidden" name="timestamp" id="timestamp" value="20180430134048317"/>' .
            '</form></body>' .
            "<script type=\"text/javascript\">document.getElementById(\"sform\").submit();\n</script>";

        $result = [
            'retCode' => '0000',
            'retMsg' => '操作完成',
            'sysMerchNo' => '152018041400045',
            'sign' => 'c63f883c3cbccc7e83721c30176c2080',
            'autoSubmitForm' => $autoSubmitForm,
            'tranNo' => '20180430134048010110009808',
            'outOrderNo' => '201804300000046095',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $wangLong = new WangLong();
        $wangLong->setContainer($this->container);
        $wangLong->setClient($this->client);
        $wangLong->setResponse($response);
        $wangLong->setPrivateKey('test');
        $wangLong->setOptions($this->option);
        $data = $wangLong->getVerifyData();

        $callBackUrl = 'http://47.93.11.210/noti/payment/chn/front/01/DUL_CHN/DUL_CHN_ME0000000184';
        $notifyUrl = 'http://47.93.11.210/noti/payment/chn/back/01/DUL_CHN/DUL_CHN_ME0000000184';

        $this->assertEquals('http://www.dulpay.com/api/pay/net', $data['post_url']);
        $this->assertEquals('102', $data['params']['bankCode']);
        $this->assertEquals('500', $data['params']['amount']);
        $this->assertEquals($callBackUrl, $data['params']['callBackUrl']);
        $this->assertEquals('2000020060', $data['params']['orderNo']);
        $this->assertEquals('1', $data['params']['cardType']);
        $this->assertEquals('231d3b0b772caa1701f20243f88d5856', $data['params']['sign']);
        $this->assertEquals('d6d16396bb8112a46d6ef6e97dbe47ff', $data['params']['appNo']);
        $this->assertEquals('gateway', $data['params']['payType']);
        $this->assertEquals($notifyUrl, $data['params']['notifyUrl']);
        $this->assertEquals('201804300000046095', $data['params']['goodsTitle']);
        $this->assertEquals('CNY', $data['params']['currency']);
        $this->assertEquals('ME0000000184', $data['params']['merchantNo']);
        $this->assertEquals('20180430134048317', $data['params']['timestamp']);
    }

    /**
     * 測試快捷支付對外返回沒有form表單
     */
    public function testQuickPayReturnWithoutForm()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $this->option['paymentVendorId'] = '278';

        $result = [
            'retCode' => '0000',
            'retMsg' => '操作完成',
            'sign' => 'b73730243ff3320b22ec04cbdd8872eb',
            'tranNo' => '201806211221370111000000026305',
            'sysMerchNo' => '152018041400045',
            'outOrderNo' => '201806210000011932',
            'autoSubmitForm' => '',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $wangLong = new WangLong();
        $wangLong->setContainer($this->container);
        $wangLong->setClient($this->client);
        $wangLong->setResponse($response);
        $wangLong->setPrivateKey('test');
        $wangLong->setOptions($this->option);
        $wangLong->getVerifyData();
    }

    /**
     * 測試快捷支付時返回autoSubmitForm沒有提交網址
     */
    public function testQuickPayReturnWithoutAction()
    {
        $this->option['paymentVendorId'] = '278';

        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $autoSubmitForm =
            '<body>' .
                '<form id = "sform" action="" method="post">' .
                    '<input type="hidden" name="extend" id="extend" value=\'\'/>' .
                    '<input type="hidden" name="charset" id="charset" value=\'UTF-8\'/>' .
                '</form>' .
            '</body>' .
            '<script type="text/javascript">document.getElementById("sform").submit(); </script>';

        $result = [
            'retCode' => '0000',
            'retMsg' => '操作完成',
            'sysMerchNo' => '152018041400045',
            'sign' => 'c63f883c3cbccc7e83721c30176c2080',
            'autoSubmitForm' => $autoSubmitForm,
            'tranNo' => '20180430134048010110009808',
            'outOrderNo' => '201804300000046095',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $wangLong = new WangLong();
        $wangLong->setContainer($this->container);
        $wangLong->setClient($this->client);
        $wangLong->setResponse($response);
        $wangLong->setPrivateKey('test');
        $wangLong->setOptions($this->option);
        $wangLong->getVerifyData();
    }

    /**
     * 測試快捷支付
     */
    public function testQuickPay()
    {
        $this->option['paymentVendorId'] = '278';

        $formData = '{"head":' .
            '{"accessType":"1","channelType":"07","method":"sandPay.fastPay.quickPay.index"' .
            ',"mid":"19708422","productId":"00000016","reqTime":"20180621122137","version":"1.0"}' .
            ',"body":' .
            '{"userId":"0000011932","clearCycle":"0","extend":"","currencyCode":"156"' .
            ',"frontUrl":"http://47.93.11.210/noti/payment/chn/front/02/SAND_CHN/SAND_CHN_19708422/' .
            '15295548971136556305"' .
            ',"notifyUrl":"http://47.93.11.210/noti/payment/chn/back/02/SAND_CHN/SAND_CHN_19708422"' .
            ',"orderCode":"15295548971136556305","orderTime":"20180621122137","totalAmount":"000000001200"' .
            ',"body":"201806210000011932","subject":"201806210000011932"}}';

        $sign = 'SETS3s1dJ70bYF5apeY/l8aXRMNmUUqdZ6JItTsvdWuf2gdxgflZLVBIVdB4xqQZ4VdgcFs7zl0snQM0U/Wag3gaum0iJ' .
            'ugE8gcoAThYo5yJymFecGjIJKHE++kcW6yK2gw7F7HAJnVaE7bwBmBv6qcOwHdQMZYCIsIwxbUFiQEalDRN5htenYQhX6FUo0' .
            '0dFkD2KiELNmbActIa/cShFTrjR5u1zSF7ouMW+Oz4QGHllcNu+5Yar/vGTj1zwybpTlPnl8RWG3ozXGtjflILSTFlUy/TDEY' .
            'BAsiHKOtJPRERo8n6wfbwEgb49ee8BeiNBp0ntLo7E4mvSaxy3HyKSg==';

        $autoSubmitForm =
            '<body>' .
                '<form id = "sform" action="https://cashier.sandpay.com.cn/fastPay/quickPay/index" method="post">' .
                    '<input type="hidden" name="extend" id="extend" value=\'\'/>' .
                    '<input type="hidden" name="charset" id="charset" value=\'UTF-8\'/>' .
                    "<input type=\"hidden\" name=\"data\" id=\"data\" value='$formData'/>" .
                    "<input type=\"hidden\" name=\"sign\" id=\"sign\" value='$sign'/>" .
                    '<input type="hidden" name="signType" id="signType" value=\'01\'/>' .
                '</form>' .
            '</body>' .
            '<script type="text/javascript">document.getElementById("sform").submit(); </script>';

        $result = [
            'retCode' => '0000',
            'retMsg' => '操作完成',
            'sign' => 'b73730243ff3320b22ec04cbdd8872eb',
            'tranNo' => '201806211221370111000000026305',
            'sysMerchNo' => '152018041400045',
            'outOrderNo' => '201806210000011932',
            'autoSubmitForm' => $autoSubmitForm,
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $wangLong = new WangLong();
        $wangLong->setContainer($this->container);
        $wangLong->setClient($this->client);
        $wangLong->setResponse($response);
        $wangLong->setPrivateKey('test');
        $wangLong->setOptions($this->option);
        $data = $wangLong->getVerifyData();

        $this->assertEquals('https://cashier.sandpay.com.cn/fastPay/quickPay/index', $data['post_url']);
        $this->assertEquals('', $data['params']['extend']);
        $this->assertEquals('UTF-8', $data['params']['charset']);
        $this->assertEquals($formData, $data['params']['data']);
        $this->assertEquals($sign, $data['params']['sign']);
        $this->assertEquals('01', $data['params']['signType']);
    }

    /**
     * 測試支付寶手機支付
     */
    public function testPhonePay()
    {
        $this->option['paymentVendorId'] = '1098';

        $result = [
            'retCode' => '0000',
            'retMsg' => '操作完成',
            'sign' => 'c825f330c47d30a7907b9fe835b8a6aa',
            'tranNo' => '201806211207530110000000026146',
            'tranAmt' => '12.00',
            'sysMerchNo' => '152018041400045',
            'outOrderNo' => '201806210000011931',
            'orderStatus' => '01',
            'tranDesc' => 'OK',
            'jumpUrl' => 'http://bak.yqjjmy.com/pay/wykj.php?ids=1301867&sign=da6ef83e22b553e98a3feb7a3e02515b',
            'tranAttr' => 'H5',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json');

        $wangLong = new WangLong();
        $wangLong->setContainer($this->container);
        $wangLong->setClient($this->client);
        $wangLong->setResponse($response);
        $wangLong->setPrivateKey('test');
        $wangLong->setOptions($this->option);
        $data = $wangLong->getVerifyData();

        $this->assertEquals('http://bak.yqjjmy.com/pay/wykj.php', $data['post_url']);
        $this->assertEquals('1301867', $data['params']['ids']);
        $this->assertEquals('da6ef83e22b553e98a3feb7a3e02515b', $data['params']['sign']);
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

        $wangLong = new WangLong();
        $wangLong->verifyOrderPayment([]);
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

        $wangLong = new WangLong();
        $wangLong->setPrivateKey('test');
        $wangLong->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未返回簽名
     */
    public function testReturnWithoutSignature()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['sign']);

        $wangLong = new WangLong();
        $wangLong->setPrivateKey('test');
        $wangLong->setOptions($this->returnResult);
        $wangLong->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = '72115d871b377d6fb195d04bf3483d91';

        $wangLong = new WangLong();
        $wangLong->setPrivateKey('test');
        $wangLong->setOptions($this->returnResult);
        $wangLong->verifyOrderPayment([]);
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

        $this->returnResult['tranResult'] = 'FAIL';
        $this->returnResult['sign'] = '3cbc646c476b2d879c810ea5d40d6ac9';

        $wangLong = new WangLong();
        $wangLong->setPrivateKey('test');
        $wangLong->setOptions($this->returnResult);
        $wangLong->verifyOrderPayment([]);
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

        $wangLong = new WangLong();
        $wangLong->setPrivateKey('test');
        $wangLong->setOptions($this->returnResult);
        $wangLong->verifyOrderPayment($entry);
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
            'id' => '201804300000046095',
            'amount' => '123',
        ];

        $wangLong = new WangLong();
        $wangLong->setPrivateKey('test');
        $wangLong->setOptions($this->returnResult);
        $wangLong->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $entry = [
            'id' => '201804300000046095',
            'amount' => '5',
        ];

        $wangLong = new WangLong();
        $wangLong->setPrivateKey('test');
        $wangLong->setOptions($this->returnResult);
        $wangLong->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $wangLong->getMsg());
    }
}
