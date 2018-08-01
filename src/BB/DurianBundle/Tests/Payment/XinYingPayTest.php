<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\XinYingPay;
use Buzz\Message\Response;

class XinYingPayTest extends DurianTestCase
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

        $xinYingPay = new XinYingPay();
        $xinYingPay->getVerifyData();
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

        $xinYingPay = new XinYingPay();
        $xinYingPay->setPrivateKey('test');
        $xinYingPay->getVerifyData();
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

        $sourceData = [
            'number' => '27641',
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '999',
            'orderId' => '201609290000004496',
            'amount' => '0.01',
        ];

        $xinYingPay = new XinYingPay();
        $xinYingPay->setPrivateKey('test');
        $xinYingPay->setOptions($sourceData);
        $xinYingPay->getVerifyData();
    }

    /**
     * 測試支付沒有帶入postUrl的情況
     */
    public function testPayWithoutPostUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = [
            'number' => '27641',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201609220000004434',
            'notify_url' => 'http://two123.comxa.com/',
            'postUrl' => '',
        ];

        $xinYingPay = new XinYingPay();
        $xinYingPay->setPrivateKey('1234');
        $xinYingPay->setOptions($sourceData);
        $xinYingPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '27641',
            'paymentVendorId' => '1',
            'amount' => '1.00',
            'orderId' => '201608160000003698',
            'notify_url' => 'http://two123.comxa.com/',
            'postUrl' => 'http://api.52hpay.com:8888/PayGateWay.aspx',
        ];

        $xinYingPay = new XinYingPay();
        $xinYingPay->setPrivateKey('test');
        $xinYingPay->setOptions($sourceData);
        $encodeData = $xinYingPay->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['partner']);
        $this->assertEquals('ICBC', $encodeData['banktype']);
        $this->assertEquals($sourceData['amount'], $encodeData['paymoney']);
        $this->assertEquals($sourceData['orderId'], $encodeData['ordernumber']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['callbackurl']);
        $this->assertEquals('', $encodeData['hrefbackurl']);
        $this->assertEquals('', $encodeData['attach']);
        $this->assertEquals('4d40067d6b314600dda3fb8cf395b399', $encodeData['sign']);

        // 檢查要提交的網址是否正確
        $data = [];
        $data['partner'] = $encodeData['partner'];
        $data['banktype'] = $encodeData['banktype'];
        $data['paymoney'] = $encodeData['paymoney'];
        $data['ordernumber'] = $encodeData['ordernumber'];
        $data['callbackurl'] = $encodeData['callbackurl'];
        $data['hrefbackurl'] = $encodeData['hrefbackurl'];
        $data['attach'] = $encodeData['attach'];
        $data['sign'] = $encodeData['sign'];

        $this->assertEquals($sourceData['postUrl'] . '?' . http_build_query($data), $encodeData['act_url']);
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

        $xinYingPay = new XinYingPay();
        $xinYingPay->setPrivateKey('test');
        $xinYingPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'partner' => '27641',
            'ordernumber' => '201609290000004496',
            'orderstatus' => '1',
            'paymoney' => '0.10',
            'sysnumber' => 'J1697216092913397188210000',
            'attach' => '',
        ];

        $xinYingPay = new XinYingPay();
        $xinYingPay->setPrivateKey('test');
        $xinYingPay->setOptions($sourceData);
        $xinYingPay->verifyOrderPayment([]);
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

        $sourceData = [
            'partner' => '27641',
            'ordernumber' => '201609290000004496',
            'orderstatus' => '1',
            'paymoney' => '0.10',
            'sysnumber' => 'J1697216092913397188210000',
            'attach' => '',
            'sign' => '6aed90cc1da387bf5443123',
        ];

        $xinYingPay = new XinYingPay();
        $xinYingPay->setPrivateKey('test');
        $xinYingPay->setOptions($sourceData);
        $xinYingPay->verifyOrderPayment([]);
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

        $sourceData = [
            'partner' => '27641',
            'ordernumber' => '201609290000004496',
            'orderstatus' => '999',
            'paymoney' => '0.10',
            'sysnumber' => 'J1697216092913397188210000',
            'attach' => '',
            'sign' => 'f380c2e42b542e246444421526fdeca4',
        ];

        $xinYingPay = new XinYingPay();
        $xinYingPay->setPrivateKey('test');
        $xinYingPay->setOptions($sourceData);
        $xinYingPay->verifyOrderPayment([]);
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

        $sourceData = [
            'partner' => '27641',
            'ordernumber' => '201609290000004496',
            'orderstatus' => '1',
            'paymoney' => '0.10',
            'sysnumber' => 'J1697216092913397188210000',
            'attach' => '',
            'sign' => 'f957ccedf2ff06d65016cec863408ae3',
        ];

        $entry = [
            'id' => '201609290000004496',
            'amount' => '15.00',
        ];

        $xinYingPay = new XinYingPay();
        $xinYingPay->setPrivateKey('test');
        $xinYingPay->setOptions($sourceData);
        $xinYingPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為訂單單號錯誤
     */
    public function testReturnOrderIdFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'partner' => '27641',
            'ordernumber' => '201609290000004496',
            'orderstatus' => '1',
            'paymoney' => '0.10',
            'sysnumber' => 'J1697216092913397188210000',
            'attach' => '',
            'sign' => 'f957ccedf2ff06d65016cec863408ae3',
        ];

        $entry = ['id' => '201609290000004499'];

        $xinYingPay = new XinYingPay();
        $xinYingPay->setPrivateKey('test');
        $xinYingPay->setOptions($sourceData);
        $xinYingPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $sourceData = [
            'partner' => '27641',
            'ordernumber' => '201609290000004496',
            'orderstatus' => '1',
            'paymoney' => '0.10',
            'sysnumber' => 'J1697216092913397188210000',
            'attach' => '',
            'sign' => 'f957ccedf2ff06d65016cec863408ae3',
        ];

        $entry = [
            'id' => '201609290000004496',
            'amount' => '0.1',
        ];

        $xinYingPay = new XinYingPay();
        $xinYingPay->setPrivateKey('test');
        $xinYingPay->setOptions($sourceData);
        $xinYingPay->verifyOrderPayment($entry);

        $this->assertEquals('ok', $xinYingPay->getMsg());
    }

    /**
     * 測試訂單查詢缺少私鑰
     */
    public function testTrackingWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $xinYingPay = new XinYingPay();
        $xinYingPay->paymentTracking();
    }

    /**
     * 測試訂單查詢加密未指定查詢參數
     */
    public function testTrackingWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $xinYingPay = new XinYingPay();
        $xinYingPay->setPrivateKey('test');
        $xinYingPay->paymentTracking();
    }

    /**
     * 測試訂單查詢加密沒有帶入verify_url的情況
     */
    public function testTrackingWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => '27641',
            'orderId' => '201702100000001344',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $xinYingPay = new XinYingPay();
        $xinYingPay->setPrivateKey('test');
        $xinYingPay->setOptions($sourceData);
        $xinYingPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果Sign為空
     */
    public function testTrackingReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $params = [
            'partner' => '27641',
            'ordernumber' => '201702100000001344',
            'orderstatus' => '1',
            'paymoney' => '0.01',
            'ordermoney' => '0.01',
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '27641',
            'orderId' => '201702100000001344',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $xinYingPay = new XinYingPay();
        $xinYingPay->setContainer($this->container);
        $xinYingPay->setClient($this->client);
        $xinYingPay->setResponse($response);
        $xinYingPay->setPrivateKey('test');
        $xinYingPay->setOptions($sourceData);
        $xinYingPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回簽名驗證錯誤
     */
    public function testTrackingReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $params = [
            'partner' => '27641',
            'ordernumber' => '201702100000001344',
            'orderstatus' => '1',
            'paymoney' => '0.01',
            'ordermoney' => '0.01',
            'sign' => 'etertetertertertertert',
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '27641',
            'orderId' => '201702100000001344',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $xinYingPay = new XinYingPay();
        $xinYingPay->setContainer($this->container);
        $xinYingPay->setClient($this->client);
        $xinYingPay->setResponse($response);
        $xinYingPay->setPrivateKey('test');
        $xinYingPay->setOptions($sourceData);
        $xinYingPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回支付失敗
     */
    public function testTrackingReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $params = [
            'partner' => '27641',
            'ordernumber' => '201702100000001344',
            'orderstatus' => '99',
            'paymoney' => '0.01',
            'ordermoney' => '0.01',
            'sign' => '6d7913ce0a9ec111c99cf76dbe684aa6',
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '27641',
            'orderId' => '201702100000001344',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $xinYingPay = new XinYingPay();
        $xinYingPay->setContainer($this->container);
        $xinYingPay->setClient($this->client);
        $xinYingPay->setResponse($response);
        $xinYingPay->setPrivateKey('test');
        $xinYingPay->setOptions($sourceData);
        $xinYingPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為訂單號錯誤
     */
    public function testTrackingReturnWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $params = [
            'partner' => '27641',
            'ordernumber' => '201702100000001344',
            'orderstatus' => '1',
            'paymoney' => '0.01',
            'ordermoney' => '0.01',
            'sign' => 'cf9a834583efe0ebe03d058b9a86bf56',
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '27641',
            'orderId' => '201702100000001343',
            'amount' => '0.01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $xinYingPay = new XinYingPay();
        $xinYingPay->setContainer($this->container);
        $xinYingPay->setClient($this->client);
        $xinYingPay->setResponse($response);
        $xinYingPay->setPrivateKey('test');
        $xinYingPay->setOptions($sourceData);
        $xinYingPay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回結果為訂單金額錯誤
     */
    public function testTrackingReturnWithOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $params = [
            'partner' => '27641',
            'ordernumber' => '201702100000001344',
            'orderstatus' => '1',
            'paymoney' => '0.01',
            'ordermoney' => '0.01',
            'sign' => 'cf9a834583efe0ebe03d058b9a86bf56',
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '27641',
            'orderId' => '201702100000001344',
            'amount' => '0.02',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $xinYingPay = new XinYingPay();
        $xinYingPay->setContainer($this->container);
        $xinYingPay->setClient($this->client);
        $xinYingPay->setResponse($response);
        $xinYingPay->setPrivateKey('test');
        $xinYingPay->setOptions($sourceData);
        $xinYingPay->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $params = [
            'partner' => '27641',
            'ordernumber' => '201702100000001344',
            'orderstatus' => '1',
            'paymoney' => '0.01',
            'ordermoney' => '0.01',
            'sign' => 'cf9a834583efe0ebe03d058b9a86bf56',
        ];
        $result = json_encode($params);

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '27641',
            'orderId' => '201702100000001344',
            'amount' => '0.01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $xinYingPay = new XinYingPay();
        $xinYingPay->setContainer($this->container);
        $xinYingPay->setClient($this->client);
        $xinYingPay->setResponse($response);
        $xinYingPay->setPrivateKey('test');
        $xinYingPay->setOptions($sourceData);
        $xinYingPay->paymentTracking();
    }

    /**
     * 測試取得訂單查詢需要的參數時缺少私鑰
     */
    public function testGetPaymentTrackingDataWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $xinYingPay = new XinYingPay();
        $xinYingPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時未指定查詢參數
     */
    public function testGetPaymentTrackingDataWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $xinYingPay = new XinYingPay();
        $xinYingPay->setPrivateKey('test');
        $xinYingPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數時沒代入verify_url
     */
    public function testGetPaymentTrackingDataWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $sourceData = [
            'number' => '27641',
            'orderId' => '201702100000001344',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '',
        ];

        $xinYingPay = new XinYingPay();
        $xinYingPay->setPrivateKey('test');
        $xinYingPay->setOptions($sourceData);
        $xinYingPay->getPaymentTrackingData();
    }

    /**
     * 測試取得訂單查詢需要的參數
     */
    public function testGetPaymentTrackingData()
    {
        $sourceData = [
            'number' => '27641',
            'orderId' => '201702100000001344',
            'amount' => '0.01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
        ];

        $xinYingPay = new XinYingPay();
        $xinYingPay->setPrivateKey('test');
        $xinYingPay->setOptions($sourceData);
        $trackingData = $xinYingPay->getPaymentTrackingData();

        $path = '/OrderSelect.aspx?partner=27641&ordernumber=201702100000001344' .
            '&sign=bd02ab9631215486086637c7749650f1';
        $this->assertEquals($sourceData['verify_ip'], $trackingData['verify_ip']);
        $this->assertEquals('GET', $trackingData['method']);
        $this->assertEquals($path, $trackingData['path']);
        $this->assertEquals($sourceData['verify_url'], $trackingData['headers']['Host']);
    }

    /**
     * 測試驗證訂單查詢是否成功時缺少私鑰
     */
    public function testPaymentTrackingVerifyWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $xinYingPay = new XinYingPay();
        $xinYingPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時缺少回傳參數
     */
    public function testPaymentTrackingVerifyWithoutParameter()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $params = [
            'partner' => '27641',
            'ordernumber' => '201702100000001344',
            'orderstatus' => '1',
            'paymoney' => '0.01',
            'ordermoney' => '0.01',
        ];
        $result = json_encode($params);

        $sourceData = [
            'number' => '27641',
            'orderId' => '201702100000001344',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $xinYingPay = new XinYingPay();
        $xinYingPay->setPrivateKey('test');
        $xinYingPay->setOptions($sourceData);
        $xinYingPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時Sign為空
     */
    public function testPaymentTrackingVerifyWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $params = [
            'partner' => '27641',
            'ordernumber' => '201702100000001344',
            'orderstatus' => '1',
            'paymoney' => '0.01',
            'ordermoney' => '0.01',
        ];
        $result = json_encode($params);

        $sourceData = [
            'number' => '27641',
            'orderId' => '201702100000001344',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $xinYingPay = new XinYingPay();
        $xinYingPay->setPrivateKey('test');
        $xinYingPay->setOptions($sourceData);
        $xinYingPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為簽名驗證錯誤
     */
    public function testPaymentTrackingVerifySignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $params = [
            'partner' => '27641',
            'ordernumber' => '201702100000001344',
            'orderstatus' => '1',
            'paymoney' => '0.01',
            'ordermoney' => '0.01',
            'sign' => 'sdfdsfsdfdsfdsf',
        ];
        $result = json_encode($params);

        $sourceData = [
            'number' => '27641',
            'orderId' => '201702100000001344',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $xinYingPay = new XinYingPay();
        $xinYingPay->setPrivateKey('test');
        $xinYingPay->setOptions($sourceData);
        $xinYingPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為未支付
     */
    public function testPaymentTrackingVerifyUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $params = [
            'partner' => '27641',
            'ordernumber' => '201702100000001344',
            'orderstatus' => '0',
            'paymoney' => '0.01',
            'ordermoney' => '0.01',
            'sign' => '917145db202c34c195bf5cbae1aea777',
        ];
        $result = json_encode($params);

        $sourceData = [
            'number' => '27641',
            'orderId' => '201702100000001344',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $xinYingPay = new XinYingPay();
        $xinYingPay->setPrivateKey('test');
        $xinYingPay->setOptions($sourceData);
        $xinYingPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為支付失敗
     */
    public function testPaymentTrackingVerifyPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $params = [
            'partner' => '27641',
            'ordernumber' => '201702100000001344',
            'orderstatus' => '99',
            'paymoney' => '0.01',
            'ordermoney' => '0.01',
            'sign' => '6d7913ce0a9ec111c99cf76dbe684aa6',
        ];
        $result = json_encode($params);

        $sourceData = [
            'number' => '27641',
            'orderId' => '201702100000001344',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $xinYingPay = new XinYingPay();
        $xinYingPay->setPrivateKey('test');
        $xinYingPay->setOptions($sourceData);
        $xinYingPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢是否成功時返回結果為支付金額錯誤
     */
    public function testPaymentTrackingVerifyWithPayAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $params = [
            'partner' => '27641',
            'ordernumber' => '201702100000001344',
            'orderstatus' => '1',
            'paymoney' => '0.01',
            'ordermoney' => '0.01',
            'sign' => 'cf9a834583efe0ebe03d058b9a86bf56',
        ];
        $result = json_encode($params);

        $sourceData = [
            'number' => '27641',
            'orderId' => '201702100000001344',
            'amount' => '0.02',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $xinYingPay = new XinYingPay();
        $xinYingPay->setPrivateKey('test');
        $xinYingPay->setOptions($sourceData);
        $xinYingPay->paymentTrackingVerify();
    }

    /**
     * 測試驗證訂單查詢成功
     */
    public function testPaymentTrackingVerifySuccess()
    {
        $params = [
            'partner' => '27641',
            'ordernumber' => '201702100000001344',
            'orderstatus' => '1',
            'paymoney' => '0.01',
            'ordermoney' => '0.01',
            'sign' => 'cf9a834583efe0ebe03d058b9a86bf56',
        ];
        $result = json_encode($params);

        $sourceData = [
            'number' => '27641',
            'orderId' => '201702100000001344',
            'amount' => '0.01',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.http.test',
            'content' => $result,
        ];

        $xinYingPay = new XinYingPay();
        $xinYingPay->setPrivateKey('test');
        $xinYingPay->setOptions($sourceData);
        $xinYingPay->paymentTrackingVerify();
    }
}
