<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\HeXinPay;
use Buzz\Message\Response;

class HeXinPayTest extends DurianTestCase
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

        $heXinPay = new HeXinPay();
        $heXinPay->getVerifyData();
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

        $heXinPay = new HeXinPay();
        $heXinPay->setPrivateKey('test');
        $heXinPay->setOptions($sourceData);
        $heXinPay->getVerifyData();
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
            'number' => '901504943502480648',
            'paymentVendorId' => '9999',
            'amount' => '1',
            'orderId' => '201710240000001777',
            'orderCreateDate' => '2017-10-24 15:00:20',
            'username' => 'php1test',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.pay.zhongweipay.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $heXinPay = new HeXinPay();
        $heXinPay->setPrivateKey('test');
        $heXinPay->setOptions($sourceData);
        $heXinPay->getVerifyData();
    }

    /**
     * 測試二維支付時缺少verify_url
     */
    public function testQrcodePayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => '901504943502480648',
            'paymentVendorId' => '1090',
            'amount' => '1',
            'orderId' => '201710240000001777',
            'orderCreateDate' => '2017-10-24 15:00:20',
            'username' => 'php1test',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $heXinPay = new HeXinPay();
        $heXinPay->setPrivateKey('test');
        $heXinPay->setOptions($sourceData);
        $heXinPay->getVerifyData();
    }

    /**
     * 測試二維支付加密未返回res_code
     */
    public function testQrcodeGetEncodeNoReturnResCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => '901504943502480648',
            'paymentVendorId' => '1090',
            'amount' => '1',
            'orderId' => '201710240000001777',
            'orderCreateDate' => '2017-10-24 15:00:20',
            'username' => 'php1test',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.pay.zhongweipay.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"res_msg":"交易处理中","nonce_str":"9qEF1MqsqYytj3hJ",' .
            '"order_sn":"201710240000001778","money":"1","codeUrl":"weixin://wxpay/bizpayurl?pr=FndniGh",' .
            '"ex_field":"","signature":"694289141c72ccfcff8388f6ed10c76d"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $heXinPay = new HeXinPay();
        $heXinPay->setPrivateKey('test');
        $heXinPay->setContainer($this->container);
        $heXinPay->setClient($this->client);
        $heXinPay->setResponse($response);
        $heXinPay->setOptions($sourceData);
        $heXinPay->getVerifyData();
    }

    /**
     * 測試二維支付加密未返回res_msg
     */
    public function testQrcodeGetEncodeNoReturnResMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => '901504943502480648',
            'paymentVendorId' => '1090',
            'amount' => '1',
            'orderId' => '201710240000001777',
            'orderCreateDate' => '2017-10-24 15:00:20',
            'username' => 'php1test',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.pay.zhongweipay.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"res_code":"P000","nonce_str":"9qEF1MqsqYytj3hJ",' .
            '"order_sn":"201710240000001778","money":"1","codeUrl":"weixin://wxpay/bizpayurl?pr=FndniGh",' .
            '"ex_field":"","signature":"694289141c72ccfcff8388f6ed10c76d"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $heXinPay = new HeXinPay();
        $heXinPay->setPrivateKey('test');
        $heXinPay->setContainer($this->container);
        $heXinPay->setClient($this->client);
        $heXinPay->setResponse($response);
        $heXinPay->setOptions($sourceData);
        $heXinPay->getVerifyData();
    }

    /**
     * 測試二維支付加密返回res_code不為P000
     */
    public function testQrcodeGetEncodeReturnWithFailedResCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '交易达到限额',
            180130
        );

        $sourceData = [
            'number' => '901504943502480648',
            'paymentVendorId' => '1103',
            'amount' => '1',
            'orderId' => '201710240000001777',
            'orderCreateDate' => '2017-10-24 15:00:20',
            'username' => 'php1test',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.pay.zhongweipay.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"res_code":"-1","res_msg":"交易达到限额","nonce_str":"9qEF1MqsqYytj3hJ",' .
            '"order_sn":"201710240000001778","money":"1","codeUrl":"weixin://wxpay/bizpayurl?pr=FndniGh",' .
            '"ex_field":"","signature":"694289141c72ccfcff8388f6ed10c76d"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $heXinPay = new HeXinPay();
        $heXinPay->setPrivateKey('test');
        $heXinPay->setContainer($this->container);
        $heXinPay->setClient($this->client);
        $heXinPay->setResponse($response);
        $heXinPay->setOptions($sourceData);
        $heXinPay->getVerifyData();
    }

    /**
     * 測試二維加密未返回codeUrl
     */
    public function testQrcodeGetEncodeNoReturnCodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => '901504943502480648',
            'paymentVendorId' => '1090',
            'amount' => '1',
            'orderId' => '201710240000001777',
            'orderCreateDate' => '2017-10-24 15:00:20',
            'username' => 'php1test',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.pay.zhongweipay.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"res_code":"P000","res_msg":"交易处理中","nonce_str":"9qEF1MqsqYytj3hJ",' .
            '"order_sn":"201710240000001778","money":"1","ex_field":"",' .
            '"signature":"694289141c72ccfcff8388f6ed10c76d"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $heXinPay = new HeXinPay();
        $heXinPay->setPrivateKey('test');
        $heXinPay->setContainer($this->container);
        $heXinPay->setClient($this->client);
        $heXinPay->setResponse($response);
        $heXinPay->setOptions($sourceData);
        $heXinPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $sourceData = [
            'number' => '901504943502480648',
            'paymentVendorId' => '1090',
            'amount' => '1',
            'orderId' => '201710240000001777',
            'orderCreateDate' => '2017-10-24 15:00:20',
            'username' => 'php1test',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.pay.zhongweipay.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"res_code":"P000","res_msg":"交易处理中","nonce_str":"9qEF1MqsqYytj3hJ",' .
            '"order_sn":"201710240000001778","money":"1","codeUrl":"weixin://wxpay/bizpayurl?pr=FndniGh",' .
            '"ex_field":"","signature":"694289141c72ccfcff8388f6ed10c76d"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $heXinPay = new HeXinPay();
        $heXinPay->setPrivateKey('test');
        $heXinPay->setContainer($this->container);
        $heXinPay->setClient($this->client);
        $heXinPay->setResponse($response);
        $heXinPay->setOptions($sourceData);
        $data = $heXinPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=FndniGh', $heXinPay->getQrcode());
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $sourceData = [
            'number' => '901504943502480648',
            'paymentVendorId' => '1104',
            'amount' => '1',
            'orderId' => '201710240000001777',
            'orderCreateDate' => '2017-10-24 15:00:20',
            'username' => 'php1test',
            'notify_url' => 'http://pay.my/pay/return.php',
        ];

        $heXinPay = new HeXinPay();
        $heXinPay->setPrivateKey('test');
        $heXinPay->setOptions($sourceData);
        $verifyData = $heXinPay->getVerifyData();

        $this->assertEquals('901504943502480648', $verifyData['account_no']);
        $this->assertEquals('v1.0', $verifyData['version']);
        $this->assertEquals('00000003', $verifyData['method']);
        $this->assertEquals('09', $verifyData['productId']);
        $this->assertEquals('php1test', $verifyData['nonce_str']);
        $this->assertEquals('qqwapxf', $verifyData['pay_tool']);
        $this->assertEquals('201710240000001777', $verifyData['order_sn']);
        $this->assertEquals('1', $verifyData['money']);
        $this->assertEquals('php1test', $verifyData['body']);
        $this->assertEquals('http://pay.my/pay/return.php', $verifyData['notify']);
        $this->assertEquals('b56f457ee0c1a0ac9ad7d034109eb501', $verifyData['signature']);
    }

    /**
     * 測試網銀支付時缺少verify_url
     */
    public function testBankPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => '901504943502480648',
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201710240000001777',
            'orderCreateDate' => '2017-10-24 15:00:20',
            'username' => 'php1test',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => '',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $heXinPay = new HeXinPay();
        $heXinPay->setPrivateKey('test');
        $heXinPay->setOptions($sourceData);
        $heXinPay->getVerifyData();
    }

    /**
     * 測試網銀支付加密未返回res_code
     */
    public function testBankGetEncodeNoReturnResCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => '901504943502480648',
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201710240000001777',
            'orderCreateDate' => '2017-10-24 15:00:20',
            'username' => 'php1test',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.pay.zhongweipay.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"res_msg":"请求成功","nonce_str":"aK3jm0oYieSIPEjb",' .
            '"payUrl":"http://api.tfb8.com/cgi-bin/v2.0/api_cardpay_apply.cgi?' .
            'cipher_data=PngZKFV9cKVG2cL3bwHRosjoJM5B2bjm62CI/b6E/dTAJE9wvLqpDaUJnqhx4rylqWU5b' .
            'ZRs9a0gbaGXSub12qsDsOYVOoNt3Px1o0dtgrhMrw/ejvoxKaW5x4GM3ka6pqabl+ExFMfYQkk1VUMb67P' .
            'zhgXigpiApOblH37iPI87KEAEqMa/arO6zL2/b9mcsRqDek/0SFVD/wZBxknJ0lPigkFwQOHi38Atyt129y' .
            'ovRmvrRIHAP/UAWXRD56gMKWwMxu1aVczwglPF4tk/th6oE3cl2zNbts32QpXdgTamSYD4LMYapxcn01F3V' .
            'wRoYZdhXLGJ9xn+9dx50eeatItQEIDR7x1nfW847Qy9XsnyPOFn93xXfESXd9yqqO3bEA4JSlgW7KjnFVPE' .
            '93ahvbBP9kKzFDuu9n9OUG3C5sXf3t8pcOVN/b/TnyTbIPRVBnO/PtWbrjFpICSn3aMsDfUpVMwEtj/Mjt7' .
            'Wb4okbK0+qavb6xrWSyvyHUAt5Pf7","order_sn":"201710240000001777","ex_field":"",' .
            '"signature":"d71aa2d4b16b80d6ecdcb16486261edd"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $heXinPay = new HeXinPay();
        $heXinPay->setPrivateKey('test');
        $heXinPay->setContainer($this->container);
        $heXinPay->setClient($this->client);
        $heXinPay->setResponse($response);
        $heXinPay->setOptions($sourceData);
        $heXinPay->getVerifyData();
    }

    /**
     * 測試網銀支付加密未返回res_msg
     */
    public function testBankGetEncodeNoReturnResMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => '901504943502480648',
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201710240000001777',
            'orderCreateDate' => '2017-10-24 15:00:20',
            'username' => 'php1test',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.pay.zhongweipay.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"res_code":"P000","nonce_str":"aK3jm0oYieSIPEjb",' .
            '"payUrl":"http://api.tfb8.com/cgi-bin/v2.0/api_cardpay_apply.cgi?' .
            'cipher_data=PngZKFV9cKVG2cL3bwHRosjoJM5B2bjm62CI/b6E/dTAJE9wvLqpDaUJnqhx4rylqWU5b' .
            'ZRs9a0gbaGXSub12qsDsOYVOoNt3Px1o0dtgrhMrw/ejvoxKaW5x4GM3ka6pqabl+ExFMfYQkk1VUMb67P' .
            'zhgXigpiApOblH37iPI87KEAEqMa/arO6zL2/b9mcsRqDek/0SFVD/wZBxknJ0lPigkFwQOHi38Atyt129y' .
            'ovRmvrRIHAP/UAWXRD56gMKWwMxu1aVczwglPF4tk/th6oE3cl2zNbts32QpXdgTamSYD4LMYapxcn01F3V' .
            'wRoYZdhXLGJ9xn+9dx50eeatItQEIDR7x1nfW847Qy9XsnyPOFn93xXfESXd9yqqO3bEA4JSlgW7KjnFVPE' .
            '93ahvbBP9kKzFDuu9n9OUG3C5sXf3t8pcOVN/b/TnyTbIPRVBnO/PtWbrjFpICSn3aMsDfUpVMwEtj/Mjt7' .
            'Wb4okbK0+qavb6xrWSyvyHUAt5Pf7","order_sn":"201710240000001777","ex_field":"",' .
            '"signature":"d71aa2d4b16b80d6ecdcb16486261edd"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $heXinPay = new HeXinPay();
        $heXinPay->setPrivateKey('test');
        $heXinPay->setContainer($this->container);
        $heXinPay->setClient($this->client);
        $heXinPay->setResponse($response);
        $heXinPay->setOptions($sourceData);
        $heXinPay->getVerifyData();
    }

    /**
     * 測試網銀支付加密返回res_code不為P000
     */
    public function testBankGetEncodeReturnWithFailedResCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '交易达到限额',
            180130
        );

        $sourceData = [
            'number' => '901504943502480648',
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201710240000001777',
            'orderCreateDate' => '2017-10-24 15:00:20',
            'username' => 'php1test',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.pay.zhongweipay.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"res_code":"-1","res_msg":"交易达到限额","nonce_str":"aK3jm0oYieSIPEjb",' .
            '"payUrl":"http://api.tfb8.com/cgi-bin/v2.0/api_cardpay_apply.cgi?' .
            'cipher_data=PngZKFV9cKVG2cL3bwHRosjoJM5B2bjm62CI/b6E/dTAJE9wvLqpDaUJnqhx4rylqWU5b' .
            'ZRs9a0gbaGXSub12qsDsOYVOoNt3Px1o0dtgrhMrw/ejvoxKaW5x4GM3ka6pqabl+ExFMfYQkk1VUMb67P' .
            'zhgXigpiApOblH37iPI87KEAEqMa/arO6zL2/b9mcsRqDek/0SFVD/wZBxknJ0lPigkFwQOHi38Atyt129y' .
            'ovRmvrRIHAP/UAWXRD56gMKWwMxu1aVczwglPF4tk/th6oE3cl2zNbts32QpXdgTamSYD4LMYapxcn01F3V' .
            'wRoYZdhXLGJ9xn+9dx50eeatItQEIDR7x1nfW847Qy9XsnyPOFn93xXfESXd9yqqO3bEA4JSlgW7KjnFVPE' .
            '93ahvbBP9kKzFDuu9n9OUG3C5sXf3t8pcOVN/b/TnyTbIPRVBnO/PtWbrjFpICSn3aMsDfUpVMwEtj/Mjt7' .
            'Wb4okbK0+qavb6xrWSyvyHUAt5Pf7","order_sn":"201710240000001777","ex_field":"",' .
            '"signature":"d71aa2d4b16b80d6ecdcb16486261edd"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $heXinPay = new HeXinPay();
        $heXinPay->setPrivateKey('test');
        $heXinPay->setContainer($this->container);
        $heXinPay->setClient($this->client);
        $heXinPay->setResponse($response);
        $heXinPay->setOptions($sourceData);
        $heXinPay->getVerifyData();
    }

    /**
     * 測試網銀支付加密未返回payUrl
     */
    public function testBankGetEncodeNoReturnPayUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => '901504943502480648',
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201710240000001777',
            'orderCreateDate' => '2017-10-24 15:00:20',
            'username' => 'php1test',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.pay.zhongweipay.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"res_code":"P000","res_msg":"请求成功","nonce_str":"aK3jm0oYieSIPEjb",' .
            '"order_sn":"201710240000001777","ex_field":"","signature":"d71aa2d4b16b80d6ecdcb16486261edd"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $heXinPay = new HeXinPay();
        $heXinPay->setPrivateKey('test');
        $heXinPay->setContainer($this->container);
        $heXinPay->setClient($this->client);
        $heXinPay->setResponse($response);
        $heXinPay->setOptions($sourceData);
        $heXinPay->getVerifyData();
    }

    /**
     * 測試網銀支付時返回缺少cipher_data
     */
    public function testBankPayReturnWithoutCipherData()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'number' => '901504943502480648',
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201710240000001777',
            'orderCreateDate' => '2017-10-24 15:00:20',
            'username' => 'php1test',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.pay.zhongweipay.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"res_code":"P000","res_msg":"请求成功","nonce_str":"aK3jm0oYieSIPEjb",' .
            '"payUrl":"http://api.tfb8.com/cgi-bin/v2.0/api_cardpay_apply.cgi?' .
            'data=PngZKFV9cKVG2cL3bwHRosjoJM5B2bjm62CI/b6E/dTAJE9wvLqpDaUJnqhx4rylqWU5b' .
            'ZRs9a0gbaGXSub12qsDsOYVOoNt3Px1o0dtgrhMrw/ejvoxKaW5x4GM3ka6pqabl+ExFMfYQkk1VUMb67P' .
            'zhgXigpiApOblH37iPI87KEAEqMa/arO6zL2/b9mcsRqDek/0SFVD/wZBxknJ0lPigkFwQOHi38Atyt129y' .
            'ovRmvrRIHAP/UAWXRD56gMKWwMxu1aVczwglPF4tk/th6oE3cl2zNbts32QpXdgTamSYD4LMYapxcn01F3V' .
            'wRoYZdhXLGJ9xn+9dx50eeatItQEIDR7x1nfW847Qy9XsnyPOFn93xXfESXd9yqqO3bEA4JSlgW7KjnFVPE' .
            '93ahvbBP9kKzFDuu9n9OUG3C5sXf3t8pcOVN/b/TnyTbIPRVBnO/PtWbrjFpICSn3aMsDfUpVMwEtj/Mjt7' .
            'Wb4okbK0+qavb6xrWSyvyHUAt5Pf7","order_sn":"201710240000001777","ex_field":"",' .
            '"signature":"d71aa2d4b16b80d6ecdcb16486261edd"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $heXinPay = new HeXinPay();
        $heXinPay->setPrivateKey('test');
        $heXinPay->setContainer($this->container);
        $heXinPay->setClient($this->client);
        $heXinPay->setResponse($response);
        $heXinPay->setOptions($sourceData);
        $heXinPay->getVerifyData();
    }

    /**
     * 測試網銀支付
     */
    public function testBankPay()
    {
        $sourceData = [
            'number' => '901504943502480648',
            'paymentVendorId' => '1',
            'amount' => '1',
            'orderId' => '201710240000001777',
            'orderCreateDate' => '2017-10-24 15:00:20',
            'username' => 'php1test',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.http.pay.zhongweipay.net',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"res_code":"P000","res_msg":"请求成功","nonce_str":"aK3jm0oYieSIPEjb",' .
            '"payUrl":"http://api.tfb8.com/cgi-bin/v2.0/api_cardpay_apply.cgi?' .
            'cipher_data=PngZKFV9cKVG2cL3bwHRosjoJM5B2bjm62CI/b6E/dTAJE9wvLqpDaUJnqhx4rylqWU5b' .
            'ZRs9a0gbaGXSub12qsDsOYVOoNt3Px1o0dtgrhMrw/ejvoxKaW5x4GM3ka6pqabl+ExFMfYQkk1VUMb67P' .
            'zhgXigpiApOblH37iPI87KEAEqMa/arO6zL2/b9mcsRqDek/0SFVD/wZBxknJ0lPigkFwQOHi38Atyt129y' .
            'ovRmvrRIHAP/UAWXRD56gMKWwMxu1aVczwglPF4tk/th6oE3cl2zNbts32QpXdgTamSYD4LMYapxcn01F3V' .
            'wRoYZdhXLGJ9xn+9dx50eeatItQEIDR7x1nfW847Qy9XsnyPOFn93xXfESXd9yqqO3bEA4JSlgW7KjnFVPE' .
            '93ahvbBP9kKzFDuu9n9OUG3C5sXf3t8pcOVN/b/TnyTbIPRVBnO/PtWbrjFpICSn3aMsDfUpVMwEtj/Mjt7' .
            'Wb4okbK0+qavb6xrWSyvyHUAt5Pf7","order_sn":"201710240000001777","ex_field":"",' .
            '"signature":"d71aa2d4b16b80d6ecdcb16486261edd"}';

        $url = 'PngZKFV9cKVG2cL3bwHRosjoJM5B2bjm62CI/b6E/dTAJE9wvLqpDaUJnqhx4rylqWU5b' .
            'ZRs9a0gbaGXSub12qsDsOYVOoNt3Px1o0dtgrhMrw/ejvoxKaW5x4GM3ka6pqabl+ExFMfYQkk1VUMb67P' .
            'zhgXigpiApOblH37iPI87KEAEqMa/arO6zL2/b9mcsRqDek/0SFVD/wZBxknJ0lPigkFwQOHi38Atyt129y' .
            'ovRmvrRIHAP/UAWXRD56gMKWwMxu1aVczwglPF4tk/th6oE3cl2zNbts32QpXdgTamSYD4LMYapxcn01F3V' .
            'wRoYZdhXLGJ9xn+9dx50eeatItQEIDR7x1nfW847Qy9XsnyPOFn93xXfESXd9yqqO3bEA4JSlgW7KjnFVPE' .
            '93ahvbBP9kKzFDuu9n9OUG3C5sXf3t8pcOVN/b/TnyTbIPRVBnO/PtWbrjFpICSn3aMsDfUpVMwEtj/Mjt7' .
            'Wb4okbK0+qavb6xrWSyvyHUAt5Pf7';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $heXinPay = new HeXinPay();
        $heXinPay->setPrivateKey('test');
        $heXinPay->setContainer($this->container);
        $heXinPay->setClient($this->client);
        $heXinPay->setResponse($response);
        $heXinPay->setOptions($sourceData);
        $data = $heXinPay->getVerifyData();

        $this->assertEquals('http://api.tfb8.com/cgi-bin/v2.0/api_cardpay_apply.cgi', $data['post_url']);
        $this->assertEquals(urldecode($url), $data['params']['cipher_data']);
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

        $heXinPay = new HeXinPay();
        $heXinPay->verifyOrderPayment([]);
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

        $heXinPay = new HeXinPay();
        $heXinPay->setPrivateKey('test');
        $heXinPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳signature
     */
    public function testReturnWithoutSignature()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'res_code' => '0000',
            'res_msg' => 'success',
            'status' => '1',
            'order_sn' => '201710240000001778',
            'money' => '1.00',
            'nonce_str' => '6ZwhQIk4aQb7Of2',
        ];

        $heXinPay = new HeXinPay();
        $heXinPay->setPrivateKey('test');
        $heXinPay->setOptions($sourceData);
        $heXinPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時signature簽名驗證錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $sourceData = [
            'res_code' => '0000',
            'res_msg' => 'success',
            'status' => '1',
            'order_sn' => '201710240000001778',
            'money' => '1.00',
            'nonce_str' => '6ZwhQIk4aQb7Of2',
            'signature' => '982d663da94c5fcf697c1321c7eabae6',
        ];

        $heXinPay = new HeXinPay();
        $heXinPay->setPrivateKey('test');
        $heXinPay->setOptions($sourceData);
        $heXinPay->verifyOrderPayment([]);
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
            'res_code' => '0000',
            'res_msg' => 'success',
            'status' => '0',
            'order_sn' => '201710240000001778',
            'money' => '1.00',
            'nonce_str' => '6ZwhQIk4aQb7Of2',
            'signature' => '87f761615399ecb269807d019d604379',
        ];

        $heXinPay = new HeXinPay();
        $heXinPay->setPrivateKey('test');
        $heXinPay->setOptions($sourceData);
        $heXinPay->verifyOrderPayment([]);
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
            'res_code' => '0000',
            'res_msg' => 'success',
            'status' => '1',
            'order_sn' => '201710240000001778',
            'money' => '1.00',
            'nonce_str' => '6ZwhQIk4aQb7Of2',
            'signature' => '7291e6a2e3764c10166bad5b7b22d2c4',
        ];

        $entry = [
            'id' => '201710240000001779',
            'amount' => '1.00',
        ];

        $heXinPay = new HeXinPay();
        $heXinPay->setPrivateKey('test');
        $heXinPay->setOptions($sourceData);
        $heXinPay->verifyOrderPayment($entry);
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
            'res_code' => '0000',
            'res_msg' => 'success',
            'status' => '1',
            'order_sn' => '201710240000001778',
            'money' => '1.00',
            'nonce_str' => '6ZwhQIk4aQb7Of2',
            'signature' => '7291e6a2e3764c10166bad5b7b22d2c4',
        ];

        $entry = [
            'id' => '201710240000001778',
            'amount' => '0.01',
        ];

        $heXinPay = new HeXinPay();
        $heXinPay->setPrivateKey('test');
        $heXinPay->setOptions($sourceData);
        $heXinPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'res_code' => '0000',
            'res_msg' => 'success',
            'status' => '1',
            'order_sn' => '201710240000001778',
            'money' => '1.00',
            'nonce_str' => '6ZwhQIk4aQb7Of2',
            'signature' => '7291e6a2e3764c10166bad5b7b22d2c4',
        ];

        $entry = [
            'id' => '201710240000001778',
            'amount' => '1.00',
        ];

        $heXinPay = new HeXinPay();
        $heXinPay->setPrivateKey('test');
        $heXinPay->setOptions($sourceData);
        $heXinPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $heXinPay->getMsg());
    }
}