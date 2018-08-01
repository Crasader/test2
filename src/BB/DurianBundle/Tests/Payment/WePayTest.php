<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\WePay;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class WePayTest extends DurianTestCase
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

        $wePay = new WePay();
        $wePay->getVerifyData();
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

        $wePay = new WePay();
        $wePay->setPrivateKey('test');
        $wePay->getVerifyData();
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

        $options = [
            'number' => 'eslot17',
            'orderId' => '201804090000010782',
            'paymentVendorId' => '9999',
            'amount' => '500',
        ];

        $wePay = new WePay();
        $wePay->setPrivateKey('test');
        $wePay->setOptions($options);
        $wePay->getVerifyData();
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

        $options = [
            'number' => 'eslot17',
            'orderId' => '201804090000010782',
            'paymentVendorId' => '1090',
            'amount' => '500',
            'verify_url' => '',
        ];

        $wePay = new WePay();
        $wePay->setPrivateKey('test');
        $wePay->setOptions($options);
        $wePay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回status
     */
    public function testPayReturnWithoutStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => 'eslot17',
            'orderId' => '201804090000010782',
            'paymentVendorId' => '1090',
            'amount' => '500',
            'verify_url' => 'payment.https.open.goodluckchina.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $wePay = new WePay();
        $wePay->setContainer($this->container);
        $wePay->setClient($this->client);
        $wePay->setResponse($response);
        $wePay->setPrivateKey('test');
        $wePay->setOptions($options);
        $wePay->getVerifyData();
    }

    /**
     * 測試支付時返回錯誤訊息
     */
    public function testPayReturnErrorMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '不在允许的金额列表中[1000,3000,5000,10000]',
            180130
        );

        $options = [
            'number' => 'eslot17',
            'orderId' => '201804090000010782',
            'paymentVendorId' => '1090',
            'amount' => '500',
            'verify_url' => 'payment.https.open.goodluckchina.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            "status" => false,
            "err_code" => "710002",
            "err_msg" => "不在允许的金额列表中[1000,3000,5000,10000]",
            "data" => null,
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $wePay = new WePay();
        $wePay->setContainer($this->container);
        $wePay->setClient($this->client);
        $wePay->setResponse($response);
        $wePay->setPrivateKey('test');
        $wePay->setOptions($options);
        $wePay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => 'eslot17',
            'orderId' => '201804090000010782',
            'paymentVendorId' => '1090',
            'amount' => '500',
            'verify_url' => 'payment.https.open.goodluckchina.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            "status" => false,
            "err_code" => "710002",
            "data" => null,
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $wePay = new WePay();
        $wePay->setContainer($this->container);
        $wePay->setClient($this->client);
        $wePay->setResponse($response);
        $wePay->setPrivateKey('test');
        $wePay->setOptions($options);
        $wePay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回redirectURL
     */
    public function testPayReturnWithoutRedirectURL()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => 'eslot17',
            'orderId' => '201804090000010782',
            'paymentVendorId' => '1090',
            'amount' => '500',
            'verify_url' => 'payment.https.open.goodluckchina.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            "status" => true,
            "err_code" => "",
            "err_msg" => "",
            "data" => [
                "trans_id" => "20180402113920367456",
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $wePay = new WePay();
        $wePay->setContainer($this->container);
        $wePay->setClient($this->client);
        $wePay->setResponse($response);
        $wePay->setPrivateKey('test');
        $wePay->setOptions($options);
        $wePay->getVerifyData();
    }

    /**
     * 測試支付時返回表單內沒有提交網址
     */
    public function testPayReturnFormWithoutAction()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => 'eslot17',
            'orderId' => '201804090000010782',
            'paymentVendorId' => '1090',
            'amount' => '500',
            'verify_url' => 'payment.https.open.goodluckchina.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $form = "<!doctype html><html lang=\"zh-cn\"><head><meta charset=\"utf-8\">" .
            "<meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">" .
            "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\"><title></title>" .
            "<style></style></head><body>" .
            "<form name='SendForm' " .
            "method=\"POST\" target=\"_self\">" .
            "<Input Type='Hidden' Name='EncryptText' value='uaLXL9aN7iJMBQ9mFJer1DvLIj62jUHVL0X0fL" .
            "2zjQTUtYKwtvx3pH69mtE1oC7MpQQ3isc0swo2pBP9ikX xw=='>" .
            "</form><script language='javascript' type='text/JavaScript'>" .
            "SendForm.submit();</script></body></html>\"}}";

        $result = [
            "status" => true,
            "err_code" => "",
            "err_msg" => "",
            "data" => [
                "trans_id" => "20180402113920367456",
                "redirectURL" => $form,
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $wePay = new WePay();
        $wePay->setContainer($this->container);
        $wePay->setClient($this->client);
        $wePay->setResponse($response);
        $wePay->setPrivateKey('test');
        $wePay->setOptions($options);
        $wePay->getVerifyData();
    }

    /**
     * 測試支付時返回表單內沒有type=Hidden的input tag
     */
    public function testPayReturnFormWithoutInput()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => 'eslot17',
            'orderId' => '201804090000010782',
            'paymentVendorId' => '1090',
            'amount' => '500',
            'verify_url' => 'payment.https.open.goodluckchina.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $form = "<!doctype html><html lang=\"zh-cn\"><head><meta charset=\"utf-8\">" .
            "<meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">" .
            "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\"><title></title>" .
            "<style></style></head><body>" .
            "<form name='SendForm' action=\"https://bb.wps8888.com/order/transfer/\" " .
            "method=\"POST\" target=\"_self\">" .
            "</form><script language='javascript' type='text/JavaScript'>" .
            "SendForm.submit();</script></body></html>\"}}";

        $result = [
            "status" => true,
            "err_code" => "",
            "err_msg" => "",
            "data" => [
                "trans_id" => "20180402113920367456",
                "redirectURL" => $form,
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $wePay = new WePay();
        $wePay->setContainer($this->container);
        $wePay->setClient($this->client);
        $wePay->setResponse($response);
        $wePay->setPrivateKey('test');
        $wePay->setOptions($options);
        $wePay->getVerifyData();
    }

    /**
     * 測試支付時返回表單的input tag內沒有Name屬性
     */
    public function testPayReturnFormWithoutName()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => 'eslot17',
            'orderId' => '201804090000010782',
            'paymentVendorId' => '1090',
            'amount' => '500',
            'verify_url' => 'payment.https.open.goodluckchina.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $form = "<!doctype html><html lang=\"zh-cn\"><head><meta charset=\"utf-8\">" .
            "<meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">" .
            "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\"><title></title>" .
            "<style></style></head><body>" .
            "<form name='SendForm' action=\"https://bb.wps8888.com/order/transfer/\" " .
            "method=\"POST\" target=\"_self\">" .
            "<Input Type='Hidden' value='uaLXL9aN7iJMBQ9mFJer1DvLIj62jUHVL0X0fL" .
            "2zjQTUtYKwtvx3pH69mtE1oC7MpQQ3isc0swo2pBP9ikX xw=='>" .
            "</form><script language='javascript' type='text/JavaScript'>" .
            "SendForm.submit();</script></body></html>\"}}";

        $result = [
            "status" => true,
            "err_code" => "",
            "err_msg" => "",
            "data" => [
                "trans_id" => "20180402113920367456",
                "redirectURL" => $form,
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $wePay = new WePay();
        $wePay->setContainer($this->container);
        $wePay->setClient($this->client);
        $wePay->setResponse($response);
        $wePay->setPrivateKey('test');
        $wePay->setOptions($options);
        $wePay->getVerifyData();
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

        $options = [
            'number' => 'eslot17',
            'orderId' => '201804090000010782',
            'paymentVendorId' => '1090',
            'amount' => '500',
            'verify_url' => 'payment.https.open.goodluckchina.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $form = "<!doctype html><html lang=\"zh-cn\"><head><meta charset=\"utf-8\">" .
            "<meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">" .
            "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\"><title></title>" .
            "<style></style></head><body>" .
            "<form name='SendForm' action=\"https://bb.wps8888.com/order/transfer/\" " .
            "method=\"POST\" target=\"_self\">" .
            "<Input Type='Hidden' Name='' value='uaLXL9aN7iJMBQ9mFJer1DvLIj62jUHVL0X0fL" .
            "2zjQTUtYKwtvx3pH69mtE1oC7MpQQ3isc0swo2pBP9ikX xw=='>" .
            "</form><script language='javascript' type='text/JavaScript'>" .
            "SendForm.submit();</script></body></html>\"}}";

        $result = [
            "status" => true,
            "err_code" => "",
            "err_msg" => "",
            "data" => [
                "trans_id" => "20180402113920367456",
                "redirectURL" => $form,
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $wePay = new WePay();
        $wePay->setContainer($this->container);
        $wePay->setClient($this->client);
        $wePay->setResponse($response);
        $wePay->setPrivateKey('test');
        $wePay->setOptions($options);
        $wePay->getVerifyData();
    }

    /**
     * 測試支付時返回表單的input tag內沒有value屬性
     */
    public function testPayReturnFormWithoutValue()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => 'eslot17',
            'orderId' => '201804090000010782',
            'paymentVendorId' => '1090',
            'amount' => '500',
            'verify_url' => 'payment.https.open.goodluckchina.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $form = "<!doctype html><html lang=\"zh-cn\"><head><meta charset=\"utf-8\">" .
            "<meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">" .
            "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\"><title></title>" .
            "<style></style></head><body>" .
            "<form name='SendForm' action=\"https://bb.wps8888.com/order/transfer/\" " .
            "method=\"POST\" target=\"_self\">" .
            "<Input Type='Hidden' Name='EncryptText' >" .
            "</form><script language='javascript' type='text/JavaScript'>" .
            "SendForm.submit();</script></body></html>\"}}";

        $result = [
            "status" => true,
            "err_code" => "",
            "err_msg" => "",
            "data" => [
                "trans_id" => "20180402113920367456",
                "redirectURL" => $form,
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $wePay = new WePay();
        $wePay->setContainer($this->container);
        $wePay->setClient($this->client);
        $wePay->setResponse($response);
        $wePay->setPrivateKey('test');
        $wePay->setOptions($options);
        $wePay->getVerifyData();
    }

    /**
     * 測試支付時返回表單的input tag內value屬性沒有值
     */
    public function testPayReturnFormInputValueWithoutContent()
    {
        $options = [
            'number' => 'eslot17',
            'orderId' => '201804090000010782',
            'paymentVendorId' => '1090',
            'amount' => '500',
            'verify_url' => 'payment.https.open.goodluckchina.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $form = "<!doctype html><html lang=\"zh-cn\"><head><meta charset=\"utf-8\">" .
            "<meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">" .
            "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\"><title></title>" .
            "<style></style></head><body>" .
            "<form name='SendForm' action=\"https://bb.wps8888.com/order/transfer/\" " .
            "method=\"POST\" target=\"_self\">" .
            "<Input Type='Hidden' Name='EncryptText' value=''>" .
            "</form><script language='javascript' type='text/JavaScript'>" .
            "SendForm.submit();</script></body></html>\"}}";

        $result = [
            "status" => true,
            "err_code" => "",
            "err_msg" => "",
            "data" => [
                "trans_id" => "20180402113920367456",
                "redirectURL" => $form,
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $wePay = new WePay();
        $wePay->setContainer($this->container);
        $wePay->setClient($this->client);
        $wePay->setResponse($response);
        $wePay->setPrivateKey('test');
        $wePay->setOptions($options);
        $data = $wePay->getVerifyData();

        $this->assertEquals('https://bb.wps8888.com/order/transfer/', $data['post_url']);
        $this->assertEquals('',$data['params']['EncryptText']);
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $options = [
            'number' => 'eslot17',
            'orderId' => '201804090000010782',
            'paymentVendorId' => '1090',
            'amount' => '500',
            'verify_url' => 'payment.https.open.goodluckchina.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $form = "<!doctype html><html lang=\"zh-cn\"><head><meta charset=\"utf-8\">" .
            "<meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">" .
            "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\"><title></title>" .
            "<style></style></head><body>" .
            "<form name='SendForm' action=\"https://bb.wps8888.com/order/transfer/\" " .
            "method=\"POST\" target=\"_self\">" .
            "<Input Type='Hidden' Name='EncryptText' value='uaLXL9aN7iJMBQ9mFJer1DvLIj62jUHVL0X0fL" .
            "2zjQTUtYKwtvx3pH69mtE1oC7MpQQ3isc0swo2pBP9ikX xw=='>" .
            "</form><script language='javascript' type='text/JavaScript'>" .
            "SendForm.submit();</script></body></html>\"}}";

        $result = [
            "status" => true,
            "err_code" => "",
            "err_msg" => "",
            "data" => [
                "trans_id" => "20180402113920367456",
                "redirectURL" => $form,
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $wePay = new WePay();
        $wePay->setContainer($this->container);
        $wePay->setClient($this->client);
        $wePay->setResponse($response);
        $wePay->setPrivateKey('test');
        $wePay->setOptions($options);
        $data = $wePay->getVerifyData();

        $this->assertEquals('https://bb.wps8888.com/order/transfer/', $data['post_url']);
        $this->assertEquals(
            'uaLXL9aN7iJMBQ9mFJer1DvLIj62jUHVL0X0fL2zjQTUtYKwtvx3pH69mtE1oC7MpQQ3isc0swo2pBP9ikX xw==',
            $data['params']['EncryptText']
        );
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

        $wePay = new WePay();
        $wePay->verifyOrderPayment([]);
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

        $wePay = new WePay();
        $wePay->setPrivateKey('test');
        $wePay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未返回簽名
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'BuCode' => 'eslot17',
            'TransId' => 'eslot17201804090000010782',
            'Amount' => 500,
            'Status' => true,
        ];

        $wePay = new WePay();
        $wePay->setPrivateKey('test');
        $wePay->setOptions($options);
        $wePay->verifyOrderPayment([]);
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

        $options = [
            'BuCode' => 'eslot17',
            'TransId' => 'eslot17201804090000010782',
            'Amount' => 500,
            'Status' => true,
            'Sign' => 'error',
        ];

        $wePay = new WePay();
        $wePay->setPrivateKey('1234');
        $wePay->setOptions($options);
        $wePay->verifyOrderPayment([]);
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

        $options = [
            'BuCode' => 'eslot17',
            'TransId' => 'eslot17201804090000010783',
            'Amount' => 500,
            'Status' => false,
            'Sign' => '6544e6375a2481de1f525f58abfb1f92',
        ];

        $wePay = new WePay();
        $wePay->setPrivateKey('test');
        $wePay->setOptions($options);
        $wePay->verifyOrderPayment([]);
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

        $options = [
            'BuCode' => 'eslot17',
            'TransId' => 'eslot17201804090000010782',
            'Amount' => 500,
            'Status' => true,
            'Sign' => 'e6f39392bd61ae2752957d4be5a1c957',
        ];

        $entry = ['id' => '201707250000003581'];

        $wePay = new WePay();
        $wePay->setPrivateKey('test');
        $wePay->setOptions($options);
        $wePay->verifyOrderPayment($entry);
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

        $options = [
            'BuCode' => 'eslot17',
            'TransId' => 'eslot17201804090000010782',
            'Amount' => 500,
            'Status' => true,
            'Sign' => 'e6f39392bd61ae2752957d4be5a1c957',
        ];

        $entry = [
            'id' => '201804090000010782',
            'amount' => 100,
        ];

        $wePay = new WePay();
        $wePay->setPrivateKey('test');
        $wePay->setOptions($options);
        $wePay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $options = [
            'BuCode' => 'eslot17',
            'TransId' => 'eslot17201804090000010782',
            'Amount' => 500,
            'Status' => true,
            'Sign' => 'e6f39392bd61ae2752957d4be5a1c957',
        ];

        $entry = [
            'id' => '201804090000010782',
            'amount' => 500,
        ];

        $wePay = new WePay();
        $wePay->setPrivateKey('test');
        $wePay->setOptions($options);
        $wePay->verifyOrderPayment($entry);

        $this->assertEquals('{"status":true,"err_msg":"success"}', $wePay->getMsg());
    }
}
