<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\HengXingShanPay;
use Buzz\Message\Response;

class HengXingShanPayTest extends DurianTestCase
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

        $hengXingShanPay = new HengXingShanPay();
        $hengXingShanPay->getVerifyData();
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

        $hengXingShanPay = new HengXingShanPay();
        $hengXingShanPay->setPrivateKey('test');
        $hengXingShanPay->getVerifyData();
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
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '100',
            'number' => '10000092',
            'orderId' => '201803230000011336',
            'amount' => '100',
            'orderCreateDate' => '2018-03-23 15:40:00',
        ];

        $hengXingShanPay = new HengXingShanPay();
        $hengXingShanPay->setPrivateKey('test');
        $hengXingShanPay->setOptions($options);
        $hengXingShanPay->getVerifyData();
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

        $options = [
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '278',
            'number' => '10000092',
            'orderId' => '201803230000011336',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2018-03-23 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $hengXingShanPay = new HengXingShanPay();
        $hengXingShanPay->setPrivateKey('test');
        $hengXingShanPay->setOptions($options);
        $hengXingShanPay->getVerifyData();
    }

    /**
     * 測試支付時返回缺少status
     */
    public function testPayReturnWithoutStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '{"merchantId":10000092,"orderId":"201803280000011530","payOrderId":"102018032810068141",' .
            'codeImgUrl":"http://paygw.oracn.net/union/qrCode?param=d4jrAwOmg+WUJF44BHLWB/4vie3WcCNQ11y9pn1' .
            'rD+ktP6qC6vmjTadP+YY1YZOVmgNkuU0vluDf7aLS2lQ1pI5yPdX5/A+gwS6aujYV7HY=","tranTime":"20180328114' .
            '954","tranAmt":"30.00","signType":"MD5","signData":"B7BED8BB2B33A476921AA475901FF182"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1111',
            'number' => '10000092',
            'orderId' => '201803230000011336',
            'amount' => '100',
            'orderCreateDate' => '2018-03-23 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $hengXingShanPay = new HengXingShanPay();
        $hengXingShanPay->setPrivateKey('test');
        $hengXingShanPay->setContainer($this->container);
        $hengXingShanPay->setClient($this->client);
        $hengXingShanPay->setResponse($response);
        $hengXingShanPay->setOptions($options);
        $hengXingShanPay->getVerifyData();
    }

    /**
     * 測試支付時返回結果失敗
     */
    public function testPayReturnButFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '最低交易额是10.00元',
            180130
        );

        $result = '{"status":"01","msg":"最低交易额是10.00元"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '278',
            'number' => '10000092',
            'orderId' => '201803230000011336',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'orderCreateDate' => '2018-03-23 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $hengXingShanPay = new HengXingShanPay();
        $hengXingShanPay->setPrivateKey('test');
        $hengXingShanPay->setContainer($this->container);
        $hengXingShanPay->setClient($this->client);
        $hengXingShanPay->setResponse($response);
        $hengXingShanPay->setOptions($options);
        $hengXingShanPay->getVerifyData();
    }

    /**
     * 測試支付時返回缺少codeUrl
     */
    public function testPayReturnWithoutCodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = '{"status":"00","merchantId":"10000092","orderId":"201803280000011530"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1111',
            'number' => '10000092',
            'orderId' => '201803230000011336',
            'amount' => '100',
            'orderCreateDate' => '2018-03-23 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $hengXingShanPay = new HengXingShanPay();
        $hengXingShanPay->setPrivateKey('test');
        $hengXingShanPay->setContainer($this->container);
        $hengXingShanPay->setClient($this->client);
        $hengXingShanPay->setResponse($response);
        $hengXingShanPay->setOptions($options);
        $hengXingShanPay->getVerifyData();
    }

    /**
     * 測試銀聯二維支付
     */
    public function testQrcodePay()
    {
        $result = '{"status":"00","merchantId":10000092,"orderId":"201803280000011530","payOrderId":"102018032810' .
            '068141","codeUrl":"https://qr.95516.com/00010001/620410245347","codeImgUrl":"http://paygw.oracn.net/' .
            'union/qrCode?param=d4jrAwOmg+WUJF44BHLWB/4vie3WcCNQ11y9pn1rD+ktP6qC6vmjTadP+YY1YZOVmgNkuU0vluDf7aLS2l' .
            'Q1pI5yPdX5/A+gwS6aujYV7HY=","tranTime":"20180328114954","tranAmt":"30.00","signType":"MD5","signData"' .
            ':"B7BED8BB2B33A476921AA475901FF182"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1111',
            'number' => '10000092',
            'orderId' => '201803280000011530',
            'amount' => '30.00',
            'orderCreateDate' => '2018-03-23 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $hengXingShanPay = new HengXingShanPay();
        $hengXingShanPay->setPrivateKey('test');
        $hengXingShanPay->setContainer($this->container);
        $hengXingShanPay->setClient($this->client);
        $hengXingShanPay->setResponse($response);
        $hengXingShanPay->setOptions($options);
        $verifyData = $hengXingShanPay->getVerifyData();

        $this->assertEmpty($verifyData);
        $this->assertEquals('https://qr.95516.com/00010001/620410245347', $hengXingShanPay->getQrcode());
    }

    /**
     * 測試銀聯在線支付時返回缺少H5Url
     */
    public function testPayReturnWithoutH5Url()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = ' {"status":"00","merchantId":10000092,"orderId":"201803280000011532","payOrderId":' .
            '"102018032810069243","tranTime":"20180328115145","tranAmt":"30.00","signType":"MD5",' .
            '"signData":"0E77C0D13F909C0E1B29D6A15FFF345C"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '278',
            'number' => '10000092',
            'orderId' => '201803230000011336',
            'amount' => '100',
            'orderCreateDate' => '2018-03-23 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $hengXingShanPay = new HengXingShanPay();
        $hengXingShanPay->setPrivateKey('test');
        $hengXingShanPay->setContainer($this->container);
        $hengXingShanPay->setClient($this->client);
        $hengXingShanPay->setResponse($response);
        $hengXingShanPay->setOptions($options);
        $hengXingShanPay->getVerifyData();
    }

    /**
     * 測試銀聯在線支付
     */
    public function testQuickPay()
    {
        $result = ' {"status":"00","merchantId":10000092,"orderId":"201803280000011532","payOrderId":' .
            '"102018032810069243","H5Url":"http://extman.bairnet.com/an_pay/consume.action?order_no=an2018' .
            '03281151493330659543939","tranTime":"20180328115145","tranAmt":"30.00","signType":"MD5",' .
            '"signData":"0E77C0D13F909C0E1B29D6A15FFF345C"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $options = [
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '278',
            'number' => '10000092',
            'orderId' => '201803280000011530',
            'amount' => '30.00',
            'orderCreateDate' => '2018-03-23 15:40:00',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test.com',
        ];

        $hengXingShanPay = new HengXingShanPay();
        $hengXingShanPay->setPrivateKey('test');
        $hengXingShanPay->setContainer($this->container);
        $hengXingShanPay->setClient($this->client);
        $hengXingShanPay->setResponse($response);
        $hengXingShanPay->setOptions($options);
        $verifyData = $hengXingShanPay->getVerifyData();

        $this->assertEquals('http://extman.bairnet.com/an_pay/consume.action', $verifyData['post_url']);
        $this->assertEquals('an201803281151493330659543939', $verifyData['params']['order_no']);
        $this->assertEquals('GET', $hengXingShanPay->getPayMethod());
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

        $hengXingShanPay = new HengXingShanPay();
        $hengXingShanPay->verifyOrderPayment([]);
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

        $hengXingShanPay = new HengXingShanPay();
        $hengXingShanPay->setPrivateKey('test');
        $hengXingShanPay->verifyOrderPayment([]);
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

        $options = [
            'charset' => 'UTF-8',
            'version' => '1.0',
            'merchantId' => '10000092',
            'orderId' => '201803280000011538',
            'payOrderId' => '102018032810093341',
            'payType' => 'DQP',
            'tranAmt' => '10.00',
            'orderSts' => 'PD',
            'tranTime' => '20180328134413',
            'remark' => '',
            'orderDesc' => '',
            'extRemark' => '',
            'signType' => 'MD5',
        ];

        $hengXingShanPay = new HengXingShanPay();
        $hengXingShanPay->setPrivateKey('test');
        $hengXingShanPay->setOptions($options);
        $hengXingShanPay->verifyOrderPayment([]);
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
            'charset' => 'UTF-8',
            'version' => '1.0',
            'merchantId' => '10000092',
            'orderId' => '201803280000011538',
            'payOrderId' => '102018032810093341',
            'payType' => 'DQP',
            'tranAmt' => '10.00',
            'orderSts' => 'PD',
            'tranTime' => '20180328134413',
            'remark' => '',
            'orderDesc' => '',
            'extRemark' => '',
            'signType' => 'MD5',
            'signData' => '123',
        ];

        $hengXingShanPay = new HengXingShanPay();
        $hengXingShanPay->setPrivateKey('test');
        $hengXingShanPay->setOptions($options);
        $hengXingShanPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付失敗
     */
    public function testReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $options = [
            'charset' => 'UTF-8',
            'version' => '1.0',
            'merchantId' => '10000092',
            'orderId' => '201803280000011538',
            'payOrderId' => '102018032810093341',
            'payType' => 'DQP',
            'tranAmt' => '10.00',
            'orderSts' => 'NE',
            'tranTime' => '20180328134413',
            'remark' => '',
            'orderDesc' => '',
            'extRemark' => '',
            'signType' => 'MD5',
            'signData' => 'D4C9D9FC8CD63197732EB9E3A45ABFDC',
        ];

        $hengXingShanPay = new HengXingShanPay();
        $hengXingShanPay->setPrivateKey('test');
        $hengXingShanPay->setOptions($options);
        $hengXingShanPay->verifyOrderPayment([]);
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
            'charset' => 'UTF-8',
            'version' => '1.0',
            'merchantId' => '10000092',
            'orderId' => '201803280000011538',
            'payOrderId' => '102018032810093341',
            'payType' => 'DQP',
            'tranAmt' => '10.00',
            'orderSts' => 'PD',
            'tranTime' => '20180328134413',
            'remark' => '',
            'orderDesc' => '',
            'extRemark' => '',
            'signType' => 'MD5',
            'signData' => '9478E616572123CF63E7F4B1088C5B06',
        ];

        $entry = ['id' => '201503220000000555'];

        $hengXingShanPay = new HengXingShanPay();
        $hengXingShanPay->setPrivateKey('test');
        $hengXingShanPay->setOptions($options);
        $hengXingShanPay->verifyOrderPayment($entry);
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

        $options = [
            'charset' => 'UTF-8',
            'version' => '1.0',
            'merchantId' => '10000092',
            'orderId' => '201803280000011538',
            'payOrderId' => '102018032810093341',
            'payType' => 'DQP',
            'tranAmt' => '10.00',
            'orderSts' => 'PD',
            'tranTime' => '20180328134413',
            'remark' => '',
            'orderDesc' => '',
            'extRemark' => '',
            'signType' => 'MD5',
            'signData' => '9478E616572123CF63E7F4B1088C5B06',
        ];

        $entry = [
            'id' => '201803280000011538',
            'amount' => '15.00',
        ];

        $hengXingShanPay = new HengXingShanPay();
        $hengXingShanPay->setPrivateKey('test');
        $hengXingShanPay->setOptions($options);
        $hengXingShanPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnSuccess()
    {
        $options = [
            'charset' => 'UTF-8',
            'version' => '1.0',
            'merchantId' => '10000092',
            'orderId' => '201803280000011538',
            'payOrderId' => '102018032810093341',
            'payType' => 'DQP',
            'tranAmt' => '10.00',
            'orderSts' => 'PD',
            'tranTime' => '20180328134413',
            'remark' => '',
            'orderDesc' => '',
            'extRemark' => '',
            'signType' => 'MD5',
            'signData' => '9478E616572123CF63E7F4B1088C5B06',
        ];

        $entry = [
            'id' => '201803280000011538',
            'amount' => '10.00',
        ];

        $hengXingShanPay = new HengXingShanPay();
        $hengXingShanPay->setPrivateKey('test');
        $hengXingShanPay->setOptions($options);
        $hengXingShanPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $hengXingShanPay->getMsg());
    }
}
