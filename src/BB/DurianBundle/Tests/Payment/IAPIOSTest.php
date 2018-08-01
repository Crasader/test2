<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\IAPIOS;
use Buzz\Message\Response;

class IAPIOSTest extends DurianTestCase
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
     * 測試加密
     */
    public function testPay()
    {
        $options = ['orderId' => '20160518000000123'];

        $IAPIOS = new IAPIOS();
        $IAPIOS->setOptions($options);
        $requestData = $IAPIOS->getVerifyData();

        $this->assertEquals('20160518000000123', $requestData['cash_deposit_entry_id']);
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

        $IAPIOS = new IAPIOS();
        $IAPIOS->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少支付狀態
     */
    public function testReturnWithoutStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'cash_deposit_entry_id' => '20160518000000123',
            'receipt' => 'testReceipt',
            'amount' => '100.00',
            'verify_url' => 'www.iapios.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $res = [
            'environment' => 'Sandbox',
            'receipt' => 'test123'
        ];

        $result = json_encode($res);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $IAPIOS = new IAPIOS();
        $IAPIOS->setContainer($this->container);
        $IAPIOS->setClient($this->client);
        $IAPIOS->setResponse($response);
        $IAPIOS->setOptions($options);
        $IAPIOS->verifyOrderPayment([]);
    }

    /**
     * 測試返回App Store無法讀取提供的JSON數據
     */
    public function testReturnWithTheAppStoreCouldNotReadTheJSONObjectYouProvided()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'The App Store could not read the JSON object you provided',
            150180165
        );

        $options = [
            'cash_deposit_entry_id' => '20160518000000123',
            'receipt' => 'testReceipt',
            'amount' => '100.00',
            'verify_url' => 'www.iapios.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $res = [
            'status' => 21000,
            'environment' => 'Sandbox',
            'receipt' => 'test123',
        ];

        $result = json_encode($res);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $IAPIOS = new IAPIOS();
        $IAPIOS->setContainer($this->container);
        $IAPIOS->setClient($this->client);
        $IAPIOS->setResponse($response);
        $IAPIOS->setOptions($options);
        $IAPIOS->verifyOrderPayment([]);
    }

    /**
     * 測試返回Receipt-Data的數據有異常或是遺失
     */
    public function testReturnWithTheDataInTheReceiptDataPropertyWasMalformedOrMissing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'The data in the receipt-data property was malformed or missing',
            150180166
        );

        $options = [
            'cash_deposit_entry_id' => '20160518000000123',
            'receipt' => 'testReceipt',
            'amount' => '100.00',
            'verify_url' => 'www.iapios.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $res = [
            'status' => 21002,
            'environment' => 'Sandbox',
            'receipt' => 'test123',
        ];

        $result = json_encode($res);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $IAPIOS = new IAPIOS();
        $IAPIOS->setContainer($this->container);
        $IAPIOS->setClient($this->client);
        $IAPIOS->setResponse($response);
        $IAPIOS->setOptions($options);
        $IAPIOS->verifyOrderPayment([]);
    }

    /**
     * 測試返回Receipt無法通過驗證
     */
    public function testReturnWithTheReceiptCouldNotBeAuthenticated()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'The receipt could not be authenticated',
            150180167
        );

        $options = [
            'cash_deposit_entry_id' => '20160518000000123',
            'receipt' => 'testReceipt',
            'amount' => '100.00',
            'verify_url' => 'www.iapios.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $res = [
            'status' => 21003,
            'environment' => 'Sandbox',
            'receipt' => 'test123',
        ];

        $result = json_encode($res);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $IAPIOS = new IAPIOS();
        $IAPIOS->setContainer($this->container);
        $IAPIOS->setClient($this->client);
        $IAPIOS->setResponse($response);
        $IAPIOS->setOptions($options);
        $IAPIOS->verifyOrderPayment([]);
    }

    /**
     * 測試返回提供的shared secret不合法
     */
    public function testReturnWithTheSharedSecretYouProvidedDoesNotMatchTheSharedSecretOnFileForYourAccount()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'The shared secret you provided does not match the shared secret on file for your account',
            150180168
        );

        $options = [
            'cash_deposit_entry_id' => '20160518000000123',
            'receipt' => 'testReceipt',
            'amount' => '100.00',
            'verify_url' => 'www.iapios.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $res = [
            'status' => 21004,
            'environment' => 'Sandbox',
            'receipt' => 'test123',
        ];

        $result = json_encode($res);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $IAPIOS = new IAPIOS();
        $IAPIOS->setContainer($this->container);
        $IAPIOS->setClient($this->client);
        $IAPIOS->setResponse($response);
        $IAPIOS->setOptions($options);
        $IAPIOS->verifyOrderPayment([]);
    }

    /**
     * 測試返回Receipt伺服器目前無法使用
     */
    public function testReturnWithTheReceiptServerIsNotCurrentlyAvailable()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'The receipt server is not currently available',
            150180169
        );

        $options = [
            'cash_deposit_entry_id' => '20160518000000123',
            'receipt' => 'testReceipt',
            'amount' => '100.00',
            'verify_url' => 'www.iapios.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $res = [
            'status' => 21005,
            'environment' => 'Sandbox',
            'receipt' => 'test123',
        ];

        $result = json_encode($res);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $IAPIOS = new IAPIOS();
        $IAPIOS->setContainer($this->container);
        $IAPIOS->setClient($this->client);
        $IAPIOS->setResponse($response);
        $IAPIOS->setOptions($options);
        $IAPIOS->verifyOrderPayment([]);
    }

    /**
     * 測試返回Receipt為合法收據但已經過期
     */
    public function testReturnWithThisReceiptIsValidButTheSubscriptionHasExpired()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'This receipt is valid but the subscription has expired',
            150180170
        );

        $options = [
            'cash_deposit_entry_id' => '20160518000000123',
            'receipt' => 'testReceipt',
            'amount' => '100.00',
            'verify_url' => 'www.iapios.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $res = [
            'status' => 21006,
            'environment' => 'Sandbox',
            'receipt' => 'test123',
        ];

        $result = json_encode($res);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $IAPIOS = new IAPIOS();
        $IAPIOS->setContainer($this->container);
        $IAPIOS->setClient($this->client);
        $IAPIOS->setResponse($response);
        $IAPIOS->setOptions($options);
        $IAPIOS->verifyOrderPayment([]);
    }

    /**
     * 測試返回Receipt為測試環境數據但卻發送到正式環境驗證
     */
    public function testReturnWithThisReceiptIsFromTheTestEnvironment()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'This receipt is from the test environment, but it was sent to the production environment for verification',
            150180171
        );

        $options = [
            'cash_deposit_entry_id' => '20160518000000123',
            'receipt' => 'testReceipt',
            'amount' => '100.00',
            'verify_url' => 'www.iapios.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $res = [
            'status' => 21007,
            'environment' => 'Sandbox',
            'receipt' => 'test123',
        ];

        $result = json_encode($res);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $IAPIOS = new IAPIOS();
        $IAPIOS->setContainer($this->container);
        $IAPIOS->setClient($this->client);
        $IAPIOS->setResponse($response);
        $IAPIOS->setOptions($options);
        $IAPIOS->verifyOrderPayment([]);
    }

    /**
     * 測試返回Receipt為正式環境數據但卻發送到測試環境驗證
     */
    public function testReturnWithThisReceiptIsFromTheProductionEnvironment()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'This receipt is from the production environment, but it was sent to the test environment for verification',
            150180172
        );

        $options = [
            'cash_deposit_entry_id' => '20160518000000123',
            'receipt' => 'testReceipt',
            'amount' => '100.00',
            'verify_url' => 'www.iapios.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $res = [
            'status' => 21008,
            'environment' => 'Sandbox',
            'receipt' => 'test123',
        ];

        $result = json_encode($res);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $IAPIOS = new IAPIOS();
        $IAPIOS->setContainer($this->container);
        $IAPIOS->setClient($this->client);
        $IAPIOS->setResponse($response);
        $IAPIOS->setOptions($options);
        $IAPIOS->verifyOrderPayment([]);
    }

    /**
     * 測試返回支付失敗
     */
    public function testReturnWithPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $options = [
            'cash_deposit_entry_id' => '20160518000000123',
            'receipt' => 'testReceipt',
            'amount' => '100.00',
            'verify_url' => 'www.iapios.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $res = [
            'status' => 123,
            'environment' => 'Sandbox',
            'receipt' => 'test123',
        ];

        $result = json_encode($res);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $IAPIOS = new IAPIOS();
        $IAPIOS->setContainer($this->container);
        $IAPIOS->setClient($this->client);
        $IAPIOS->setResponse($response);
        $IAPIOS->setOptions($options);
        $IAPIOS->verifyOrderPayment([]);
    }

    /**
     * 測試返回訂單號錯誤
     */
    public function testReturnWithOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $options = [
            'cash_deposit_entry_id' => '20160518000000123',
            'receipt' => 'testReceipt',
            'amount' => '100.00',
            'verify_url' => 'www.iapios.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $res = [
            'status' => 0,
            'environment' => 'Sandbox',
            'receipt' => 'test123',
        ];

        $result = json_encode($res);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $entry = ['id' => '20160518000000456'];

        $IAPIOS = new IAPIOS();
        $IAPIOS->setContainer($this->container);
        $IAPIOS->setClient($this->client);
        $IAPIOS->setResponse($response);
        $IAPIOS->setOptions($options);
        $IAPIOS->verifyOrderPayment($entry);
    }

    /**
     * 測試返回金額錯誤
     */
    public function testReturnWithOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $options = [
            'cash_deposit_entry_id' => '20160518000000123',
            'receipt' => 'testReceipt',
            'amount' => '100.00',
            'verify_url' => 'www.iapios.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $res = [
            'status' => 0,
            'environment' => 'Sandbox',
            'receipt' => 'test123',
        ];

        $result = json_encode($res);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $entry = [
            'id' => '20160518000000123',
            'amount' => '10.00',
        ];

        $IAPIOS = new IAPIOS();
        $IAPIOS->setContainer($this->container);
        $IAPIOS->setClient($this->client);
        $IAPIOS->setResponse($response);
        $IAPIOS->setOptions($options);
        $IAPIOS->verifyOrderPayment($entry);
    }

    /**
     * 測試返回驗證
     */
    public function testReturn()
    {
        $options = [
            'cash_deposit_entry_id' => '20160518000000123',
            'receipt' => 'testReceipt',
            'amount' => '100.00',
            'verify_url' => 'www.iapios.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $res = [
            'status' => 0,
            'environment' => 'Sandbox',
            'receipt' => 'test123',
        ];

        $result = json_encode($res);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('content-type:text/html;charset=UTF-8;');

        $entry = [
            'id' => '20160518000000123',
            'amount' => '100.00',
        ];

        $IAPIOS = new IAPIOS();
        $IAPIOS->setContainer($this->container);
        $IAPIOS->setClient($this->client);
        $IAPIOS->setResponse($response);
        $IAPIOS->setOptions($options);
        $IAPIOS->verifyOrderPayment($entry);

        $this->assertEquals('success', $IAPIOS->getMsg());
    }
}
