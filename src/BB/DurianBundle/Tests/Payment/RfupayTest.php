<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Rfupay;
use Buzz\Message\Response;

class RfupayTest extends DurianTestCase
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
            ->setMethods(['get', 'getParameter'])
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

        $rfupay = new Rfupay();
        $rfupay->getVerifyData();
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

        $rfupay = new Rfupay();
        $rfupay->setPrivateKey('test');
        $rfupay->getVerifyData();
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
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100',
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '99',
            'username' => 'php1test',
        ];

        $rfupay = new Rfupay();
        $rfupay->setPrivateKey('test');
        $rfupay->setOptions($options);
        $rfupay->getVerifyData();
    }

    /**
     * 測試支付缺少商家額外的參數設定
     */
    public function testPayWithoutMerchantExtra()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $options = [
            'number' => 'acctest',
            'orderId' => '201503220000000123',
            'orderCreateDate' => '2015-03-22 21:25:29',
            'amount' => '100',
            'notify_url' => 'http://www.mobao.cn/return.php',
            'paymentVendorId' => '1',
            'merchantId' => '35660',
            'domain' => '6',
            'merchant_extra' => [],
            'username' => 'php1test',
        ];

        $rfupay = new Rfupay();
        $rfupay->setPrivateKey('test');
        $rfupay->setOptions($options);
        $rfupay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'number' => 'acctest',
            'orderId' => '201503220000000123',
            'orderCreateDate' => '2015-03-22 21:25:29',
            'amount' => '100',
            'notify_url' => 'http://pay.xxx.xxx/app/return.php',
            'paymentVendorId' => '1',
            'merchantId' => '35660',
            'domain' => '6',
            'merchant_extra' => [
                'accountId' => 'testAccountId',
                'goods' => 'testGoods',
                'refCode' => 'testRefCode',
            ],
            'username' => 'php1test',
            'support' => true,
        ];

        $rfupay = new Rfupay();
        $rfupay->setPrivateKey('test');
        $rfupay->setOptions($options);
        $requestData = $rfupay->getVerifyData();

        $this->assertEquals('acctest', $requestData['partyId']);
        $this->assertEquals('testAccountId', $requestData['accountId']);
        $this->assertEmpty($requestData['appType']);
        $this->assertEquals('testGoods201503220000000123', $requestData['orderNo']);
        $this->assertEquals('100.00', $requestData['orderAmount']);
        $this->assertEquals('testGoods', $requestData['goods']);
        $this->assertEquals('http://pay.xxx.xxx/app/return.php', $requestData['returnUrl']);
        $this->assertEquals('01', $requestData['cardType']);
        $this->assertEquals('00004', $requestData['bank']);
        $this->assertEquals('Md5', $requestData['encodeType']);
        $this->assertEquals('testRefCode', $requestData['refCode']);
        $this->assertEquals('824628b4fb28e22563772359106553c0', $requestData['signMD5']);
    }

    /**
     * 測試支付銀行為銀聯在線
     */
    public function testPayWithMWeb()
    {
        $options = [
            'number' => 'acctest',
            'orderId' => '201503220000000123',
            'orderCreateDate' => '2015-03-22 21:25:29',
            'amount' => '100',
            'notify_url' => 'http://pay.xxx.xxx/app/return.php',
            'paymentVendorId' => '1088',
            'merchantId' => '35660',
            'domain' => '6',
            'merchant_extra' => [
                'accountId' => 'testAccountId',
                'goods' => 'testGoods',
                'refCode' => 'testRefCode',
            ],
            'username' => 'php1test',
        ];

        $rfupay = new Rfupay();
        $rfupay->setPrivateKey('test');
        $rfupay->setOptions($options);
        $requestData = $rfupay->getVerifyData();

        $this->assertEquals('acctest', $requestData['partyId']);
        $this->assertEquals('testAccountId', $requestData['accountId']);
        $this->assertEquals('MWEB',$requestData['appType']);
        $this->assertEquals('testGoods201503220000000123', $requestData['orderNo']);
        $this->assertEquals('100.00', $requestData['orderAmount']);
        $this->assertEquals('testGoods', $requestData['goods']);
        $this->assertEquals('http://pay.xxx.xxx/app/return.php', $requestData['returnUrl']);
        $this->assertEquals('01', $requestData['cardType']);
        $this->assertEquals('MWEB', $requestData['bank']);
        $this->assertEquals('Md5', $requestData['encodeType']);
        $this->assertEquals('testRefCode', $requestData['refCode']);
        $this->assertEquals('bcd5c52048c0cece3567c18e4fede6dd', $requestData['signMD5']);
    }

    /**
     * 測試支付銀行為微信二維
     */
    public function testPayWithWeiXinQRCode()
    {
        $options = [
            'number' => 'acctest',
            'orderId' => '201503220000000123',
            'orderCreateDate' => '2015-03-22 21:25:29',
            'amount' => '100',
            'notify_url' => 'http://pay.xxx.xxx/app/return.php',
            'paymentVendorId' => '1090',
            'merchantId' => '35660',
            'domain' => '6',
            'merchant_extra' => [
                'accountId' => 'testAccountId',
                'goods' => 'testGoods',
                'refCode' => 'testRefCode',
            ],
            'username' => 'php1test',
            'support' => true,
        ];

        $rfupay = new Rfupay();
        $rfupay->setPrivateKey('test');
        $rfupay->setOptions($options);
        $requestData = $rfupay->getVerifyData();

        $this->assertEquals('acctest', $requestData['partyId']);
        $this->assertEquals('testAccountId', $requestData['accountId']);
        $this->assertEquals('WECHAT',$requestData['appType']);
        $this->assertEquals('testGoods201503220000000123', $requestData['orderNo']);
        $this->assertEquals('100.00', $requestData['orderAmount']);
        $this->assertEquals('testGoods', $requestData['goods']);
        $this->assertEquals('http://pay.xxx.xxx/app/return.php', $requestData['returnUrl']);
        $this->assertEquals('01', $requestData['cardType']);
        $this->assertEquals('wechat', $requestData['bank']);
        $this->assertEquals('Md5', $requestData['encodeType']);
        $this->assertEquals('testRefCode', $requestData['refCode']);
        $this->assertEquals('8b97cdd0c2b4023b2b8ca152f2b9c957', $requestData['signMD5']);
    }

    /**
     * 測試支付銀行為支付寶二維
     */
    public function testPayWithAlipay()
    {
        $options = [
            'number' => 'acctest',
            'orderId' => '201503220000000123',
            'orderCreateDate' => '2015-03-22 21:25:29',
            'amount' => '100',
            'notify_url' => 'http://pay.xxx.xxx/app/return.php',
            'paymentVendorId' => '1092',
            'merchantId' => '35660',
            'domain' => '6',
            'merchant_extra' => [
                'accountId' => 'testAccountId',
                'goods' => 'testGoods',
                'refCode' => 'testRefCode',
            ],
            'username' => 'php1test',
        ];

        $rfupay = new Rfupay();
        $rfupay->setPrivateKey('test');
        $rfupay->setOptions($options);
        $requestData = $rfupay->getVerifyData();

        $this->assertEquals('acctest', $requestData['partyId']);
        $this->assertEquals('testAccountId', $requestData['accountId']);
        $this->assertEquals('ALIPAY',$requestData['appType']);
        $this->assertEquals('testGoods201503220000000123', $requestData['orderNo']);
        $this->assertEquals('100.00', $requestData['orderAmount']);
        $this->assertEquals('testGoods', $requestData['goods']);
        $this->assertEquals('http://pay.xxx.xxx/app/return.php', $requestData['returnUrl']);
        $this->assertEquals('01', $requestData['cardType']);
        $this->assertEquals('alipay', $requestData['bank']);
        $this->assertEquals('Md5', $requestData['encodeType']);
        $this->assertEquals('testRefCode', $requestData['refCode']);
        $this->assertEquals('9c695d80c78c064196d8c5400f1d1531', $requestData['signMD5']);
    }

    /**
     * 測試支付銀行為QQ二維
     */
    public function testPayWithQQ()
    {
        $options = [
            'number' => 'acctest',
            'orderId' => '201503220000000123',
            'amount' => '100',
            'notify_url' => 'http://pay.xxx.xxx/app/return.php',
            'paymentVendorId' => '1103',
            'merchant_extra' => [
                'accountId' => 'testAccountId',
                'goods' => 'testGoods',
                'refCode' => 'testRefCode',
            ],
        ];

        $rfupay = new Rfupay();
        $rfupay->setPrivateKey('test');
        $rfupay->setOptions($options);
        $requestData = $rfupay->getVerifyData();

        $this->assertEquals('acctest', $requestData['partyId']);
        $this->assertEquals('testAccountId', $requestData['accountId']);
        $this->assertEquals('QPAY',$requestData['appType']);
        $this->assertEquals('testGoods201503220000000123', $requestData['orderNo']);
        $this->assertEquals('100.00', $requestData['orderAmount']);
        $this->assertEquals('testGoods', $requestData['goods']);
        $this->assertEquals('http://pay.xxx.xxx/app/return.php', $requestData['returnUrl']);
        $this->assertEquals('01', $requestData['cardType']);
        $this->assertEquals('', $requestData['bank']);
        $this->assertEquals('Md5', $requestData['encodeType']);
        $this->assertEquals('testRefCode', $requestData['refCode']);
        $this->assertEquals('44aafcb612a0983e3cb670522d9513a1', $requestData['signMD5']);
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

        $rfupay = new Rfupay();
        $rfupay->verifyOrderPayment([]);
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

        $rfupay = new Rfupay();
        $rfupay->setPrivateKey('test');
        $rfupay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少加密簽名
     */
    public function testReturnWithoutSignMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'orderNo' => 'W79201608040000005976',
            'appType' => 'WECHAT',
            'orderAmount' => '0.01',
            'succ' => 'Y',
            'encodeType' => 'Md5',
        ];

        $rfupay = new Rfupay();
        $rfupay->setPrivateKey('test');
        $rfupay->setOptions($options);
        $rfupay->verifyOrderPayment([]);
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
            'orderNo' => 'W79201608040000005976',
            'appType' => 'WECHAT',
            'orderAmount' => '0.01',
            'succ' => 'Y',
            'encodeType' => 'Md5',
            'signMD5' => 'test',
        ];

        $rfupay = new Rfupay();
        $rfupay->setPrivateKey('test');
        $rfupay->setOptions($options);
        $rfupay->verifyOrderPayment([]);
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
            'orderNo' => 'W79201608040000005976',
            'appType' => 'WECHAT',
            'orderAmount' => '0.01',
            'succ' => 'N',
            'encodeType' => 'Md5',
            'signMD5' => 'ea9457911c436a01777af52bdeb6c565',
        ];

        $rfupay = new Rfupay();
        $rfupay->setPrivateKey('test');
        $rfupay->setOptions($options);
        $rfupay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時沒有goods(商戶首碼)
     */
    public function testReturnWithoutGoods()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'orderNo' => 'W79201608040000005976',
            'appType' => 'WECHAT',
            'orderAmount' => '0.01',
            'succ' => 'Y',
            'encodeType' => 'Md5',
            'signMD5' => '5cf6ceb9697022110ab7b29095b1f7da',
        ];

        $rfupay = new Rfupay();
        $rfupay->setPrivateKey('test');
        $rfupay->setOptions($options);
        $rfupay->verifyOrderPayment([]);
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
            'orderNo' => 'W79201608040000005976',
            'appType' => 'WECHAT',
            'orderAmount' => '0.01',
            'succ' => 'Y',
            'encodeType' => 'Md5',
            'signMD5' => '5cf6ceb9697022110ab7b29095b1f7da',
            'goods' => 'W79',
        ];

        $entry = ['id' => '201608040000005975'];

        $rfupay = new Rfupay();
        $rfupay->setPrivateKey('test');
        $rfupay->setOptions($options);
        $rfupay->verifyOrderPayment($entry);
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
            'orderNo' => 'W79201608040000005976',
            'appType' => 'WECHAT',
            'orderAmount' => '0.01',
            'succ' => 'Y',
            'encodeType' => 'Md5',
            'signMD5' => '5cf6ceb9697022110ab7b29095b1f7da',
            'goods' => 'W79',
        ];

        $entry = [
            'id' => '201608040000005976',
            'amount' => '0.02',
        ];

        $rfupay = new Rfupay();
        $rfupay->setPrivateKey('test');
        $rfupay->setOptions($options);
        $rfupay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'orderNo' => 'W79201608040000005976',
            'appType' => 'WECHAT',
            'orderAmount' => '0.01',
            'succ' => 'Y',
            'encodeType' => 'Md5',
            'signMD5' => '5cf6ceb9697022110ab7b29095b1f7da',
            'goods' => 'W79',
        ];

        $entry = [
            'id' => '201608040000005976',
            'amount' => '0.01',
        ];

        $rfupay = new Rfupay();
        $rfupay->setPrivateKey('test');
        $rfupay->setOptions($options);
        $rfupay->verifyOrderPayment($entry);

        $this->assertEquals('checkok', $rfupay->getMsg());
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

        $rfupay = new Rfupay();
        $rfupay->paymentTracking();
    }

    /**
     * 測試訂單查詢未指定查詢參數
     */
    public function testTrackingWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $rfupay = new Rfupay();
        $rfupay->setPrivateKey('test');
        $rfupay->paymentTracking();
    }

    /**
     * 測試訂單查詢缺少商家額外的參數設定
     */
    public function testTrackingWithoutMerchantExtra()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100',
            'merchant_extra' => [],
        ];

        $rfupay = new Rfupay();
        $rfupay->setPrivateKey('test');
        $rfupay->setOptions($options);
        $rfupay->paymentTracking();
    }

    /**
     * 測試訂單查詢沒有帶入reopUrl的情況
     */
    public function testTrackingWithoutReopUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No reopUrl specified',
            180141
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100',
            'merchant_extra' => [
                'accountId' => 'testAccountId',
                'goods' => 'testGoods',
            ],
            'reopUrl' => '',
        ];

        $rfupay = new Rfupay();
        $rfupay->setPrivateKey('test');
        $rfupay->setOptions($options);
        $rfupay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回不合法的情況
     */
    public function testTrackingReturnWithInvalidResponse()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Invalid response',
            180148
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100',
            'merchant_extra' => [
                'accountId' => 'testAccountId',
                'goods' => 'testGoods',
            ],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'reopUrl' => 'https://portal.rfupayadv.com/Main/api_enquiry/orderEnquiry',
        ];

        $this->container->expects($this->any())->method('getParameter')->willReturn('172.26.54.42');

        $result = 'test';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $rfupay = new Rfupay();
        $rfupay->setContainer($this->container);
        $rfupay->setClient($this->client);
        $rfupay->setResponse($response);
        $rfupay->setPrivateKey('test');
        $rfupay->setOptions($options);
        $rfupay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回沒有MtapayResp的情況
     */
    public function testTrackingReturnWithoutMtapayResp()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100',
            'merchant_extra' => [
                'accountId' => 'testAccountId',
                'goods' => 'testGoods',
            ],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'reopUrl' => 'https://portal.rfupayadv.com/Main/api_enquiry/orderEnquiry',
        ];

        $this->container->expects($this->any())->method('getParameter')->willReturn('172.26.54.42');

        $result = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<documents></documents>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $rfupay = new Rfupay();
        $rfupay->setContainer($this->container);
        $rfupay->setClient($this->client);
        $rfupay->setResponse($response);
        $rfupay->setPrivateKey('test');
        $rfupay->setOptions($options);
        $rfupay->paymentTracking();
    }

    /**
     * 測試訂單查詢返回沒有必要參數
     */
    public function testTrackingReturnWithoutSignMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100',
            'merchant_extra' => [
                'accountId' => 'testAccountId',
                'goods' => 'testGoods',
            ],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'reopUrl' => 'https://portal.rfupayadv.com/Main/api_enquiry/orderEnquiry',
        ];

        $this->container->expects($this->any())->method('getParameter')->willReturn('172.26.54.42');

        $result = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<documents><MtapayResp></MtapayResp></documents>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $rfupay = new Rfupay();
        $rfupay->setContainer($this->container);
        $rfupay->setClient($this->client);
        $rfupay->setResponse($response);
        $rfupay->setPrivateKey('test');
        $rfupay->setOptions($options);
        $rfupay->paymentTracking();
    }

    /**
     * 測試訂單查詢時提交的參數錯誤
     */
    public function testTrackingReturnSubmitTheParameterError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Submit the parameter error',
            180075
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100',
            'merchant_extra' => [
                'accountId' => 'testAccountId',
                'goods' => 'testGoods',
            ],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'reopUrl' => 'https://portal.rfupayadv.com/Main/api_enquiry/orderEnquiry',
        ];

        $this->container->expects($this->any())->method('getParameter')->willReturn('172.26.54.42');

        $result = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<documents><MtapayResp>' .
            '<orderNo>W79201608040000005972</orderNo>' .
            '<mtaTransId></mtaTransId>' .
            '<result>0500</result>' .
            '<respCode></respCode>' .
            '</MtapayResp></documents>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $rfupay = new Rfupay();
        $rfupay->setContainer($this->container);
        $rfupay->setClient($this->client);
        $rfupay->setResponse($response);
        $rfupay->setPrivateKey('test');
        $rfupay->setOptions($options);
        $rfupay->paymentTracking();
    }

    /**
     * 測試訂單查詢支付平台驗證簽名錯誤
     */
    public function testTrackingReturnPaymentGatewaySignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Merchant sign error',
            180127
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100',
            'merchant_extra' => [
                'accountId' => 'testAccountId',
                'goods' => 'testGoods',
            ],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'reopUrl' => 'https://portal.rfupayadv.com/Main/api_enquiry/orderEnquiry',
        ];

        $this->container->expects($this->any())->method('getParameter')->willReturn('172.26.54.42');

        $result = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<documents><MtapayResp>' .
            '<orderNo>W79201608040000005972</orderNo>' .
            '<mtaTransId></mtaTransId>' .
            '<result>1020</result>' .
            '<respCode></respCode>' .
            '</MtapayResp></documents>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $rfupay = new Rfupay();
        $rfupay->setContainer($this->container);
        $rfupay->setClient($this->client);
        $rfupay->setResponse($response);
        $rfupay->setPrivateKey('test');
        $rfupay->setOptions($options);
        $rfupay->paymentTracking();
    }

    /**
     * 測試訂單查詢回傳訂單不存在
     */
    public function testTrackingReturnOrderNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100',
            'merchant_extra' => [
                'accountId' => 'testAccountId',
                'goods' => 'testGoods',
            ],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'reopUrl' => 'https://portal.rfupayadv.com/Main/api_enquiry/orderEnquiry',
        ];

        $this->container->expects($this->any())->method('getParameter')->willReturn('172.26.54.42');

        $result = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<documents><MtapayResp>' .
            '<orderNo>W79201608040000005972</orderNo>' .
            '<mtaTransId></mtaTransId>' .
            '<result>1010</result>' .
            '<respCode></respCode>' .
            '</MtapayResp></documents>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $rfupay = new Rfupay();
        $rfupay->setContainer($this->container);
        $rfupay->setClient($this->client);
        $rfupay->setResponse($response);
        $rfupay->setPrivateKey('test');
        $rfupay->setOptions($options);
        $rfupay->paymentTracking();
    }

    /**
     * 測試訂單查詢失敗
     */
    public function testTrackingReturnPaymentTrackingFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment tracking failed',
            180081
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100',
            'merchant_extra' => [
                'accountId' => 'testAccountId',
                'goods' => 'testGoods',
            ],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'reopUrl' => 'https://portal.rfupayadv.com/Main/api_enquiry/orderEnquiry',
        ];

        $this->container->expects($this->any())->method('getParameter')->willReturn('172.26.54.42');

        $result = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<documents><MtapayResp>' .
            '<orderNo>W79201608040000005972</orderNo>' .
            '<mtaTransId></mtaTransId>' .
            '<result>9999</result>' .
            '<respCode></respCode>' .
            '</MtapayResp></documents>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $rfupay = new Rfupay();
        $rfupay->setContainer($this->container);
        $rfupay->setClient($this->client);
        $rfupay->setResponse($response);
        $rfupay->setPrivateKey('test');
        $rfupay->setOptions($options);
        $rfupay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果沒有回傳signMD5
     */
    public function testTrackingReturnWithoutSignMD5()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100',
            'merchant_extra' => [
                'accountId' => 'testAccountId',
                'goods' => 'testGoods',
            ],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'reopUrl' => 'https://portal.rfupayadv.com/Main/api_enquiry/orderEnquiry',
        ];

        $this->container->expects($this->any())->method('getParameter')->willReturn('172.26.54.42');

        $result = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<documents><MtapayResp>' .
            '<orderNo>W79201608040000005972</orderNo>' .
            '<mtaTransId></mtaTransId>' .
            '<result>0000</result>' .
            '<respCode></respCode>' .
            '</MtapayResp></documents>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $rfupay = new Rfupay();
        $rfupay->setContainer($this->container);
        $rfupay->setClient($this->client);
        $rfupay->setResponse($response);
        $rfupay->setPrivateKey('test');
        $rfupay->setOptions($options);
        $rfupay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果解密驗證錯誤
     */
    public function testTrackingReturnDecryptValidationError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100',
            'merchant_extra' => [
                'accountId' => 'testAccountId',
                'goods' => 'testGoods',
            ],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'reopUrl' => 'https://portal.rfupayadv.com/Main/api_enquiry/orderEnquiry',
        ];

        $this->container->expects($this->any())->method('getParameter')->willReturn('172.26.54.42');

        $result = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<documents><MtapayResp>' .
            '<orderNo>W79201608040000005972</orderNo>' .
            '<mtaTransId></mtaTransId>' .
            '<result>0000</result>' .
            '<respCode></respCode>' .
            '<signMD5>test</signMD5>' .
            '</MtapayResp></documents>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $rfupay = new Rfupay();
        $rfupay->setContainer($this->container);
        $rfupay->setClient($this->client);
        $rfupay->setResponse($response);
        $rfupay->setPrivateKey('test');
        $rfupay->setOptions($options);
        $rfupay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單狀態為未支付
     */
    public function testTrackingReturnOrderPaymentUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100',
            'merchant_extra' => [
                'accountId' => 'testAccountId',
                'goods' => 'testGoods',
            ],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'reopUrl' => 'https://portal.rfupayadv.com/Main/api_enquiry/orderEnquiry',
        ];

        $this->container->expects($this->any())->method('getParameter')->willReturn('172.26.54.42');

        $result = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<documents><MtapayResp>' .
            '<orderNo>W79201608040000005972</orderNo>' .
            '<mtaTransId></mtaTransId>' .
            '<result>0000</result>' .
            '<respCode>0</respCode>' .
            '<signMD5>5cc2141eed3e01a2ebca4e094f9a7d59</signMD5>' .
            '</MtapayResp></documents>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $rfupay = new Rfupay();
        $rfupay->setContainer($this->container);
        $rfupay->setClient($this->client);
        $rfupay->setResponse($response);
        $rfupay->setPrivateKey('test');
        $rfupay->setOptions($options);
        $rfupay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單狀態不為1則代表支付失敗
     */
    public function testTrackingReturnOrderPaymentfailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100',
            'merchant_extra' => [
                'accountId' => 'testAccountId',
                'goods' => 'testGoods',
            ],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'reopUrl' => 'https://portal.rfupayadv.com/Main/api_enquiry/orderEnquiry',
        ];

        $this->container->expects($this->any())->method('getParameter')->willReturn('172.26.54.42');

        $result = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<documents><MtapayResp>' .
            '<orderNo>W79201608040000005972</orderNo>' .
            '<mtaTransId></mtaTransId>' .
            '<result>0000</result>' .
            '<respCode>2</respCode>' .
            '<signMD5>206b1a9f9dc2167bac7710fa21f77c3d</signMD5>' .
            '</MtapayResp></documents>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $rfupay = new Rfupay();
        $rfupay->setContainer($this->container);
        $rfupay->setClient($this->client);
        $rfupay->setResponse($response);
        $rfupay->setPrivateKey('test');
        $rfupay->setOptions($options);
        $rfupay->paymentTracking();
    }

    /**
     * 測試訂單查詢結果沒有回傳transAmt
     */
    public function testTrackingReturnWithoutTransAmt()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100',
            'merchant_extra' => [
                'accountId' => 'testAccountId',
                'goods' => 'testGoods',
            ],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'reopUrl' => 'https://portal.rfupayadv.com/Main/api_enquiry/orderEnquiry',
        ];

        $this->container->expects($this->any())->method('getParameter')->willReturn('172.26.54.42');

        $result = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<documents><MtapayResp>' .
            '<orderNo>W79201608040000005972</orderNo>' .
            '<mtaTransId></mtaTransId>' .
            '<result>0000</result>' .
            '<respCode>1</respCode>' .
            '<signMD5>f1311d3764d285d2182eb016062dbf44</signMD5>' .
            '</MtapayResp></documents>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $rfupay = new Rfupay();
        $rfupay->setContainer($this->container);
        $rfupay->setClient($this->client);
        $rfupay->setResponse($response);
        $rfupay->setPrivateKey('test');
        $rfupay->setOptions($options);
        $rfupay->paymentTracking();
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100',
            'merchant_extra' => [
                'accountId' => 'testAccountId',
                'goods' => 'testGoods',
            ],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'reopUrl' => 'https://portal.rfupayadv.com/Main/api_enquiry/orderEnquiry',
        ];

        $this->container->expects($this->any())->method('getParameter')->willReturn('172.26.54.42');

        $result = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<documents><MtapayResp>' .
            '<orderNo>W79201608040000005972</orderNo>' .
            '<mtaTransId></mtaTransId>' .
            '<transAmt>10</transAmt>' .
            '<result>0000</result>' .
            '<respCode>1</respCode>' .
            '<signMD5>f1311d3764d285d2182eb016062dbf44</signMD5>' .
            '</MtapayResp></documents>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $rfupay = new Rfupay();
        $rfupay->setContainer($this->container);
        $rfupay->setClient($this->client);
        $rfupay->setResponse($response);
        $rfupay->setPrivateKey('test');
        $rfupay->setOptions($options);
        $rfupay->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100',
            'merchant_extra' => [
                'accountId' => 'testAccountId',
                'goods' => 'testGoods',
            ],
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'reopUrl' => 'https://portal.rfupayadv.com/Main/api_enquiry/orderEnquiry',
        ];

        $this->container->expects($this->any())->method('getParameter')->willReturn('172.26.54.42');

        $result = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<documents><MtapayResp>' .
            '<orderNo>W79201608040000005972</orderNo>' .
            '<mtaTransId></mtaTransId>' .
            '<transAmt>100</transAmt>' .
            '<result>0000</result>' .
            '<respCode>1</respCode>' .
            '<signMD5>f1311d3764d285d2182eb016062dbf44</signMD5>' .
            '</MtapayResp></documents>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $rfupay = new Rfupay();
        $rfupay->setContainer($this->container);
        $rfupay->setClient($this->client);
        $rfupay->setResponse($response);
        $rfupay->setPrivateKey('test');
        $rfupay->setOptions($options);
        $rfupay->paymentTracking();
    }
}
