<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\QuanYinPay;
use Buzz\Message\Response;

class QuanYinPayTest extends DurianTestCase
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
            ->will($this->returnValue(null));

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

        $quanYinPay = new QuanYinPay();
        $quanYinPay->getVerifyData();
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

        $quanYinPay = new QuanYinPay();
        $quanYinPay->setPrivateKey('test');
        $quanYinPay->getVerifyData();
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
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '9999',
            'number' => 'd14a5dec33694eb3a0132f6d24de2aeb',
            'orderId' => '201709190000004732',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
        ];

        $quanYinPay = new QuanYinPay();
        $quanYinPay->setPrivateKey('test');
        $quanYinPay->setOptions($options);
        $quanYinPay->getVerifyData();
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
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => 'd14a5dec33694eb3a0132f6d24de2aeb',
            'orderId' => '201709190000004732',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => '',
        ];

        $quanYinPay = new QuanYinPay();
        $quanYinPay->setPrivateKey('test');
        $quanYinPay->setOptions($options);
        $quanYinPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回result
     */
    public function testPayReturnWithoutResult()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => 'd14a5dec33694eb3a0132f6d24de2aeb',
            'orderId' => '201709190000004732',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = ['msg' => '请求融智付异常。通道已禁用!'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $quanYinPay = new QuanYinPay();
        $quanYinPay->setContainer($this->container);
        $quanYinPay->setClient($this->client);
        $quanYinPay->setResponse($response);
        $quanYinPay->setPrivateKey('test');
        $quanYinPay->setOptions($options);
        $quanYinPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回msg
     */
    public function testPayReturnWithoutMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => 'd14a5dec33694eb3a0132f6d24de2aeb',
            'orderId' => '201709190000004732',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = ['result' => 'false'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $quanYinPay = new QuanYinPay();
        $quanYinPay->setContainer($this->container);
        $quanYinPay->setClient($this->client);
        $quanYinPay->setResponse($response);
        $quanYinPay->setPrivateKey('test');
        $quanYinPay->setOptions($options);
        $quanYinPay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '请求融智付异常。通道已禁用!',
            180130
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => 'd14a5dec33694eb3a0132f6d24de2aeb',
            'orderId' => '201709190000004732',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'result' => 'false',
            'msg' => '请求融智付异常。通道已禁用!',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $quanYinPay = new QuanYinPay();
        $quanYinPay->setContainer($this->container);
        $quanYinPay->setClient($this->client);
        $quanYinPay->setResponse($response);
        $quanYinPay->setPrivateKey('test');
        $quanYinPay->setOptions($options);
        $quanYinPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回code_url
     */
    public function testPayReturnWithoutCodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1090',
            'number' => 'd14a5dec33694eb3a0132f6d24de2aeb',
            'orderId' => '201709190000004732',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'result' => 'success',
            'msg' => '成功',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $quanYinPay = new QuanYinPay();
        $quanYinPay->setContainer($this->container);
        $quanYinPay->setClient($this->client);
        $quanYinPay->setResponse($response);
        $quanYinPay->setPrivateKey('test');
        $quanYinPay->setOptions($options);
        $quanYinPay->getVerifyData();
    }

    /**
     * 測試銀聯二維
     */
    public function testQrcodePay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1111',
            'number' => 'd14a5dec33694eb3a0132f6d24de2aeb',
            'orderId' => '201709190000004732',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'result' => 'success',
            'msg' => '成功',
            'code_url' => 'https://qr.95516.com/00010000/62822564271870960737971829416087',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $quanYinPay = new QuanYinPay();
        $quanYinPay->setContainer($this->container);
        $quanYinPay->setClient($this->client);
        $quanYinPay->setResponse($response);
        $quanYinPay->setPrivateKey('test');
        $quanYinPay->setOptions($options);
        $data = $quanYinPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://qr.95516.com/00010000/62822564271870960737971829416087', $quanYinPay->getQrcode());
    }

    /**
     * 測試網銀
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1',
            'number' => 'd14a5dec33694eb3a0132f6d24de2aeb',
            'orderId' => '201709190000004732',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $codeUrl = 'http://156.235.194.133:8050/rb-pay-web-gateway/jsp/bankPay.jsp?addr=https://pay.yflpay.com/b2cpay' .
            '&version=1.0.1&merchantId=5508800002&orderNo=66662018061213413635&bankId=ICBC&orderAmount=10000&orderDat' .
            'etime=20180612101627&pageUrl=http://156.235.194.133:8050/rb-pay-web-gateway/scanPayNotify/result/YFLPAY/' .
            '66662018061213413635&notifyUrl=http://156.235.194.133:8050/rb-pay-web-gateway/scanPayNotify/notifyExt/YF' .
            'LPAY/66662018061213413635&payType=wgpay&memo=php1test&signType=MD5&sign=3fccfc775431cb6d06cba97f45b6b69c';

        $result = [
            'result' => 'success',
            'msg' => '成功',
            'code_url' => $codeUrl,
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $quanYinPay = new QuanYinPay();
        $quanYinPay->setContainer($this->container);
        $quanYinPay->setClient($this->client);
        $quanYinPay->setResponse($response);
        $quanYinPay->setPrivateKey('test');
        $quanYinPay->setOptions($options);
        $data = $quanYinPay->getVerifyData();

        $postUrl = 'http://156.235.194.133:8050/rb-pay-web-gateway/jsp/bankPay.jsp';
        $pageUrl = 'http://156.235.194.133:8050/rb-pay-web-gateway/scanPayNotify/result/YFLPAY/66662018061213413635';
        $ntUrl = 'http://156.235.194.133:8050/rb-pay-web-gateway/scanPayNotify/notifyExt/YFLPAY/66662018061213413635';

        $this->assertEquals('GET', $quanYinPay->getPayMethod());
        $this->assertEquals('https://pay.yflpay.com/b2cpay', $data['params']['addr']);
        $this->assertEquals('1.0.1', $data['params']['version']);
        $this->assertEquals('5508800002', $data['params']['merchantId']);
        $this->assertEquals('66662018061213413635', $data['params']['orderNo']);
        $this->assertEquals('ICBC', $data['params']['bankId']);
        $this->assertEquals('10000', $data['params']['orderAmount']);
        $this->assertEquals('20180612101627', $data['params']['orderDatetime']);
        $this->assertEquals($pageUrl, $data['params']['pageUrl']);
        $this->assertEquals($ntUrl, $data['params']['notifyUrl']);
        $this->assertEquals('wgpay', $data['params']['payType']);
        $this->assertEquals('php1test', $data['params']['memo']);
        $this->assertEquals('MD5', $data['params']['signType']);
        $this->assertEquals('3fccfc775431cb6d06cba97f45b6b69c', $data['params']['sign']);
        $this->assertEquals($postUrl, $data['post_url']);
    }

    /**
     * 測試網銀收銀檯
     */
    public function testBankPay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1102',
            'number' => 'd14a5dec33694eb3a0132f6d24de2aeb',
            'orderId' => '201709190000004732',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $codeUrl = 'http://156.235.194.133:8050/rb-pay-web-gateway/jsp/yflBankPay.jsp?orderId=a8308e841e274e81a493a28' .
            '572a32f4f&recordId=50d4f8df470d4366b856f268527ae7dd&66662018061113405000';

        $result = [
            'result' => 'success',
            'msg' => '成功',
            'code_url' => $codeUrl,
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $quanYinPay = new QuanYinPay();
        $quanYinPay->setContainer($this->container);
        $quanYinPay->setClient($this->client);
        $quanYinPay->setResponse($response);
        $quanYinPay->setPrivateKey('test');
        $quanYinPay->setOptions($options);
        $data = $quanYinPay->getVerifyData();

        $postUrl = 'http://156.235.194.133:8050/rb-pay-web-gateway/jsp/yflBankPay.jsp';

        $this->assertEquals('GET', $quanYinPay->getPayMethod());
        $this->assertEquals('a8308e841e274e81a493a28572a32f4f', $data['params']['orderId']);
        $this->assertEquals('50d4f8df470d4366b856f268527ae7dd', $data['params']['recordId']);
        $this->assertEmpty($data['params']['66662018061113405000']);
        $this->assertEquals($postUrl, $data['post_url']);
    }

    /**
     * 測試銀聯在線手機支付
     */
    public function testPhonePay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1088',
            'number' => 'd14a5dec33694eb3a0132f6d24de2aeb',
            'orderId' => '201709190000004732',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $codeUrl = 'http://156.235.194.133:8050/rb-pay-web-gateway/jsp/bankPay.jsp?addr=http://pay.yflpay.com/kjpay&v' .
            'ersion=1.0.2&merchantId=5508800004&orderNo=66662018061213414529&orderAmount=10000&orderDatetime=20180612' .
            '113454&pageUrl=http://156.235.194.133:8050/rb-pay-web-gateway/scanPayNotify/result/YFLPAY/66662018061213' .
            '414529&notifyUrl=http://156.235.194.133:8050/rb-pay-web-gateway/scanPayNotify/notifyExt/YFLPAY/666620180' .
            '61213414529&payType=kjpay&signType=MD5&sign=8b68f3fa165235cbf4aa66b279887723';

        $result = [
            'result' => 'success',
            'msg' => '成功',
            'code_url' => $codeUrl,
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $quanYinPay = new QuanYinPay();
        $quanYinPay->setContainer($this->container);
        $quanYinPay->setClient($this->client);
        $quanYinPay->setResponse($response);
        $quanYinPay->setPrivateKey('test');
        $quanYinPay->setOptions($options);
        $data = $quanYinPay->getVerifyData();

        $postUrl = 'http://156.235.194.133:8050/rb-pay-web-gateway/jsp/bankPay.jsp';
        $pageUrl = 'http://156.235.194.133:8050/rb-pay-web-gateway/scanPayNotify/result/YFLPAY/66662018061213414529';
        $ntUrl = 'http://156.235.194.133:8050/rb-pay-web-gateway/scanPayNotify/notifyExt/YFLPAY/66662018061213414529';

        $this->assertEquals('GET', $quanYinPay->getPayMethod());
        $this->assertEquals('http://pay.yflpay.com/kjpay', $data['params']['addr']);
        $this->assertEquals('1.0.2', $data['params']['version']);
        $this->assertEquals('5508800004', $data['params']['merchantId']);
        $this->assertEquals('66662018061213414529', $data['params']['orderNo']);
        $this->assertEquals('10000', $data['params']['orderAmount']);
        $this->assertEquals('20180612113454', $data['params']['orderDatetime']);
        $this->assertEquals($pageUrl, $data['params']['pageUrl']);
        $this->assertEquals($ntUrl, $data['params']['notifyUrl']);
        $this->assertEquals('kjpay', $data['params']['payType']);
        $this->assertEquals('MD5', $data['params']['signType']);
        $this->assertEquals('8b68f3fa165235cbf4aa66b279887723', $data['params']['sign']);
        $this->assertEquals($postUrl, $data['post_url']);
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

        $quanYinPay = new QuanYinPay();
        $quanYinPay->verifyOrderPayment([]);
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

        $quanYinPay = new QuanYinPay();
        $quanYinPay->setPrivateKey('test');
        $quanYinPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時tradeStatus不正確
     */
    public function testReturnTradeStatusNotCorrect()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $options = [
            'orderDate' => '20170919',
            'orderNo' => '201709190000004732',
            'orderPrice' => '0.100000',
            'orderTime' => '20170919091926',
            'payKey' => 'd14a5dec33694eb3a0132f6d24de2aeb',
            'payPayCode' => 'ZITOPAY_WX_SCAN',
            'payWayCode' => 'ZITOPAY',
            'productName' => 'php1test',
            'tradeStatus' => 'FALSE',
            'trxNo' => '77772017091910006767',
            'sign' => 'C78036A828CB37575FE9028C2E0ACE3C',
        ];

        $quanYinPay = new QuanYinPay();
        $quanYinPay->setPrivateKey('test');
        $quanYinPay->setOptions($options);
        $quanYinPay->verifyOrderPayment([]);
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

        $options = [
            'orderDate' => '20170919',
            'orderNo' => '201709190000004732',
            'orderPrice' => '0.100000',
            'orderTime' => '20170919091926',
            'payKey' => 'd14a5dec33694eb3a0132f6d24de2aeb',
            'payPayCode' => 'ZITOPAY_WX_SCAN',
            'payWayCode' => 'ZITOPAY',
            'productName' => 'php1test',
            'tradeStatus' => 'SUCCESS',
            'trxNo' => '77772017091910006767',
        ];

        $quanYinPay = new QuanYinPay();
        $quanYinPay->setPrivateKey('test');
        $quanYinPay->setOptions($options);
        $quanYinPay->verifyOrderPayment([]);
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
            'orderDate' => '20170919',
            'orderNo' => '201709190000004732',
            'orderPrice' => '0.100000',
            'orderTime' => '20170919091926',
            'payKey' => 'd14a5dec33694eb3a0132f6d24de2aeb',
            'payPayCode' => 'ZITOPAY_WX_SCAN',
            'payWayCode' => 'ZITOPAY',
            'productName' => 'php1test',
            'tradeStatus' => 'SUCCESS',
            'trxNo' => '77772017091910006767',
            'sign' => 'C78036A828CB37575FE9028C2E0ACE3C',
        ];

        $quanYinPay = new QuanYinPay();
        $quanYinPay->setPrivateKey('test');
        $quanYinPay->setOptions($options);
        $quanYinPay->verifyOrderPayment([]);
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
            'orderDate' => '20170919',
            'orderNo' => '201709190000004732',
            'orderPrice' => '0.100000',
            'orderTime' => '20170919091926',
            'payKey' => 'd14a5dec33694eb3a0132f6d24de2aeb',
            'payPayCode' => 'ZITOPAY_WX_SCAN',
            'payWayCode' => 'ZITOPAY',
            'productName' => 'php1test',
            'tradeStatus' => 'SUCCESS',
            'trxNo' => '77772017091910006767',
            'sign' => '8AD2FF935B84AEF46D559FA355D56EEB',
        ];

        $entry = ['id' => '201503220000000555'];

        $quanYinPay = new QuanYinPay();
        $quanYinPay->setPrivateKey('test');
        $quanYinPay->setOptions($options);
        $quanYinPay->verifyOrderPayment($entry);
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
            'orderDate' => '20170919',
            'orderNo' => '201709190000004732',
            'orderPrice' => '0.100000',
            'orderTime' => '20170919091926',
            'payKey' => 'd14a5dec33694eb3a0132f6d24de2aeb',
            'payPayCode' => 'ZITOPAY_WX_SCAN',
            'payWayCode' => 'ZITOPAY',
            'productName' => 'php1test',
            'tradeStatus' => 'SUCCESS',
            'trxNo' => '77772017091910006767',
            'sign' => '8AD2FF935B84AEF46D559FA355D56EEB',
        ];

        $entry = [
            'id' => '201709190000004732',
            'amount' => '15.00',
        ];

        $quanYinPay = new QuanYinPay();
        $quanYinPay->setPrivateKey('test');
        $quanYinPay->setOptions($options);
        $quanYinPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $options = [
            'orderDate' => '20170919',
            'orderNo' => '201709190000004732',
            'orderPrice' => '0.100000',
            'orderTime' => '20170919091926',
            'payKey' => 'd14a5dec33694eb3a0132f6d24de2aeb',
            'payPayCode' => 'ZITOPAY_WX_SCAN',
            'payWayCode' => 'ZITOPAY',
            'productName' => 'php1test',
            'tradeStatus' => 'SUCCESS',
            'trxNo' => '77772017091910006767',
            'sign' => '8AD2FF935B84AEF46D559FA355D56EEB',
        ];

        $entry = [
            'id' => '201709190000004732',
            'amount' => '0.1',
        ];

        $quanYinPay = new QuanYinPay();
        $quanYinPay->setPrivateKey('test');
        $quanYinPay->setOptions($options);
        $quanYinPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $quanYinPay->getMsg());
    }
}
