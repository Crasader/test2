<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ChengHueiPay;
use Buzz\Message\Response;

class ChengHueiPayTest extends DurianTestCase
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
     * 測試加密基本參數設定沒有帶入privateKey的情況
     */
    public function testSetEncodeSourceNoPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $chengHueiPay = new ChengHueiPay();
        $chengHueiPay->getVerifyData();
    }

    /**
     * 測試加密基本參數設定未指定支付參數
     */
    public function testSetEncodeSourceNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $chengHueiPay = new ChengHueiPay();
        $chengHueiPay->setPrivateKey('test');
        $chengHueiPay->getVerifyData();
    }

    /**
     * 測試加密基本參數設定帶入不支援的銀行
     */
    public function testSetEncodeSourceUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'paymentVendorId' => '999',
            'number' => '500011',
            'orderId' => '201801300000003951',
            'username' => 'php1test',
            'amount' => '10',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
        ];

        $chengHueiPay = new ChengHueiPay();
        $chengHueiPay->setPrivateKey('test');
        $chengHueiPay->setOptions($sourceData);
        $chengHueiPay->getVerifyData();
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

        $sourceData = [
            'paymentVendorId' => '1097',
            'number' => '500011',
            'orderId' => '201801300000003951',
            'username' => 'php1test',
            'amount' => '10',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => '',
        ];

        $chengHueiPay = new ChengHueiPay();
        $chengHueiPay->setPrivateKey('test');
        $chengHueiPay->setOptions($sourceData);
        $chengHueiPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回returnCode
     */
    public function testPayReturnWithoutReturnCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'paymentVendorId' => '1097',
            'number' => '500011',
            'orderId' => '201801300000003951',
            'username' => 'php1test',
            'amount' => '10',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.https.api.zcc3.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'errCode' => '2800009',
            'errCodeDes' => '没有找到匹配的商户支付渠道',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $chengHueiPay = new ChengHueiPay();
        $chengHueiPay->setContainer($this->container);
        $chengHueiPay->setClient($this->client);
        $chengHueiPay->setResponse($response);
        $chengHueiPay->setPrivateKey('test');
        $chengHueiPay->setOptions($sourceData);
        $chengHueiPay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗且errCodeDes為NULL
     */
    public function testPayReturnNotSuccessAndErrCodeDesIsNULL()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'paymentVendorId' => '1097',
            'number' => '500011',
            'orderId' => '201801300000003951',
            'username' => 'php1test',
            'amount' => '10',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.https.api.zcc3.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'returnCode' => 'FAIL',
            'errCode' => '2800009',
            'errCodeDes' => null,
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $chengHueiPay = new ChengHueiPay();
        $chengHueiPay->setContainer($this->container);
        $chengHueiPay->setClient($this->client);
        $chengHueiPay->setResponse($response);
        $chengHueiPay->setPrivateKey('test');
        $chengHueiPay->setOptions($sourceData);
        $chengHueiPay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '没有找到匹配的商户支付渠道',
            180130
        );

        $sourceData = [
            'paymentVendorId' => '1097',
            'number' => '500011',
            'orderId' => '201801300000003951',
            'username' => 'php1test',
            'amount' => '10',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.https.api.zcc3.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'returnCode' => 'FAIL',
            'errCode' => '2800009',
            'errCodeDes' => '没有找到匹配的商户支付渠道',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $chengHueiPay = new ChengHueiPay();
        $chengHueiPay->setContainer($this->container);
        $chengHueiPay->setClient($this->client);
        $chengHueiPay->setResponse($response);
        $chengHueiPay->setPrivateKey('test');
        $chengHueiPay->setOptions($sourceData);
        $chengHueiPay->getVerifyData();
    }

    /**
     * 測試手機支付時沒有返回redirectUrl
     */
    public function testPhonePayReturnWithoutRedirectUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'paymentVendorId' => '1097',
            'number' => '500011',
            'orderId' => '201801300000003951',
            'username' => 'php1test',
            'amount' => '10',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.https.api.zcc3.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'returnCode' => 'SUCCESS',
            'errCode' => '',
            'errCodeDes' => '',
            'codeUrl' => '',
            'appPayServices' => '',
            'appPayTokenId' => '',
            'fail' => 'false',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $chengHueiPay = new ChengHueiPay();
        $chengHueiPay->setContainer($this->container);
        $chengHueiPay->setClient($this->client);
        $chengHueiPay->setResponse($response);
        $chengHueiPay->setPrivateKey('test');
        $chengHueiPay->setOptions($sourceData);
        $chengHueiPay->getVerifyData();
    }

    /**
     * 測試銀聯在線手機支付
     */
    public function testUnionPhonePay()
    {
        $sourceData = [
            'paymentVendorId' => '1088',
            'number' => '500011',
            'orderId' => '201801300000003951',
            'username' => 'php1test',
            'amount' => '10',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.https.api.zcc3.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'returnCode' => 'SUCCESS',
            'errCode' => '',
            'errCodeDes' => '',
            'codeUrl' => '',
            'redirectUrl' => 'http://api.xinpays.vip/pay/dopay/gateway?payId=2610793048983552',
            'appPayServices' => '',
            'appPayTokenId' => '',
            'fail' => 'false',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $chengHueiPay = new ChengHueiPay();
        $chengHueiPay->setContainer($this->container);
        $chengHueiPay->setClient($this->client);
        $chengHueiPay->setResponse($response);
        $chengHueiPay->setPrivateKey('test');
        $chengHueiPay->setOptions($sourceData);
        $data = $chengHueiPay->getVerifyData();

        $this->assertEquals('2610793048983552', $data['params']['payId']);
        $this->assertEquals('http://api.xinpays.vip/pay/dopay/gateway', $data['post_url']);
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $sourceData = [
            'paymentVendorId' => '1097',
            'number' => '500011',
            'orderId' => '201801300000003951',
            'username' => 'php1test',
            'amount' => '10',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.https.api.zcc3.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'returnCode' => 'SUCCESS',
            'errCode' => '',
            'errCodeDes' => '',
            'codeUrl' => '',
            'redirectUrl' => 'https://api.xinpays.vip/pay/dopay/wx/h5/page?payId=2548195767011328',
            'appPayServices' => '',
            'appPayTokenId' => '',
            'fail' => 'false',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $chengHueiPay = new ChengHueiPay();
        $chengHueiPay->setContainer($this->container);
        $chengHueiPay->setClient($this->client);
        $chengHueiPay->setResponse($response);
        $chengHueiPay->setPrivateKey('test');
        $chengHueiPay->setOptions($sourceData);
        $data = $chengHueiPay->getVerifyData();

        $this->assertEmpty($data['params']);
        $this->assertEquals('https://api.xinpays.vip/pay/dopay/wx/h5/page?payId=2548195767011328', $data['post_url']);
    }

    /**
     * 測試二維支付時沒有返回codeUrl
     */
    public function testQrCodePayReturnWithoutCodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $sourceData = [
            'paymentVendorId' => '1103',
            'number' => '500011',
            'orderId' => '201801300000003951',
            'username' => 'php1test',
            'amount' => '10',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.https.api.zcc3.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'returnCode' => 'SUCCESS',
            'errCode' => '',
            'errCodeDes' => '',
            'redirectUrl' => '',
            'appPayServices' => '',
            'appPayTokenId' => '',
            'fail' => 'false',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $chengHueiPay = new ChengHueiPay();
        $chengHueiPay->setContainer($this->container);
        $chengHueiPay->setClient($this->client);
        $chengHueiPay->setResponse($response);
        $chengHueiPay->setPrivateKey('test');
        $chengHueiPay->setOptions($sourceData);
        $chengHueiPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrCodePay()
    {
        $sourceData = [
            'paymentVendorId' => '1103',
            'number' => '500011',
            'orderId' => '201801300000003951',
            'username' => 'php1test',
            'amount' => '10',
            'ip' => '111.235.135.54',
            'notify_url' => 'http://pay.my/pay/return.php',
            'verify_url' => 'payment.https.api.zcc3.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = [
            'returnCode' => 'SUCCESS',
            'errCode' => '',
            'errCodeDes' => '',
            'codeUrl' => 'https://qpay.qq.com/qr/68147565',
            'redirectUrl' => '',
            'appPayServices' => '',
            'appPayTokenId' => '',
            'fail' => 'false',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $chengHueiPay = new ChengHueiPay();
        $chengHueiPay->setContainer($this->container);
        $chengHueiPay->setClient($this->client);
        $chengHueiPay->setResponse($response);
        $chengHueiPay->setPrivateKey('test');
        $chengHueiPay->setOptions($sourceData);
        $data = $chengHueiPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://qpay.qq.com/qr/68147565', $chengHueiPay->getQrcode());
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

        $chengHueiPay = new ChengHueiPay();
        $chengHueiPay->verifyOrderPayment([]);
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

        $chengHueiPay = new ChengHueiPay();
        $chengHueiPay->setPrivateKey('test');
        $chengHueiPay->verifyOrderPayment([]);
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
            'result_code' => 'SUCCESS',
            'nonce_str' => '7caf5e22ea3eb8175ab518429c8589a4',
            'err_code' => '',
            'err_msg' => '',
            'merch_no' => '900021',
            'trade_type' => 'NATIVE',
            'trade_state' => 'SUCCESS',
            'transaction_id' => '20180130113428995520',
            'out_trade_no' => '201801300000003951',
            'total_fee' => '1000',
            'fee_type' => 'CNY',
            'time_end' => '20180130113615',
        ];

        $chengHueiPay = new ChengHueiPay();
        $chengHueiPay->setPrivateKey('test');
        $chengHueiPay->setOptions($options);
        $chengHueiPay->verifyOrderPayment([]);
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
            'result_code' => 'SUCCESS',
            'nonce_str' => '7caf5e22ea3eb8175ab518429c8589a4',
            'err_code' => '',
            'err_msg' => '',
            'merch_no' => '900021',
            'sign' => 'E7A5906AD32B87EEAF74DFC4544FBCD8',
            'trade_type' => 'NATIVE',
            'trade_state' => 'SUCCESS',
            'transaction_id' => '20180130113428995520',
            'out_trade_no' => '201801300000003951',
            'total_fee' => '1000',
            'fee_type' => 'CNY',
            'time_end' => '20180130113615',
        ];

        $chengHueiPay = new ChengHueiPay();
        $chengHueiPay->setPrivateKey('test');
        $chengHueiPay->setOptions($options);
        $chengHueiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時result_code不等於SUCCESS
     */
    public function testReturnResultCodeNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $options = [
            'result_code' => 'FAIL',
            'nonce_str' => '7caf5e22ea3eb8175ab518429c8589a4',
            'err_code' => '',
            'err_msg' => '',
            'merch_no' => '900021',
            'sign' => '891E5DBEDFABE3E1F7B3EAF3F24D329B',
            'trade_type' => 'NATIVE',
            'trade_state' => 'FAIL',
            'transaction_id' => '20180130113428995520',
            'out_trade_no' => '201801300000003951',
            'total_fee' => '1000',
            'fee_type' => 'CNY',
            'time_end' => '20180130113615',
        ];

        $chengHueiPay = new ChengHueiPay();
        $chengHueiPay->setPrivateKey('test');
        $chengHueiPay->setOptions($options);
        $chengHueiPay->verifyOrderPayment([]);
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
            'result_code' => 'SUCCESS',
            'nonce_str' => '7caf5e22ea3eb8175ab518429c8589a4',
            'err_code' => '',
            'err_msg' => '',
            'merch_no' => '900021',
            'sign' => 'F34B1E0F64FB54221B13F5B16298D7E1',
            'trade_type' => 'NATIVE',
            'trade_state' => 'FAIL',
            'transaction_id' => '20180130113428995520',
            'out_trade_no' => '201801300000003951',
            'total_fee' => '1000',
            'fee_type' => 'CNY',
            'time_end' => '20180130113615',
        ];

        $chengHueiPay = new ChengHueiPay();
        $chengHueiPay->setPrivateKey('test');
        $chengHueiPay->setOptions($options);
        $chengHueiPay->verifyOrderPayment([]);
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
            'result_code' => 'SUCCESS',
            'nonce_str' => '7caf5e22ea3eb8175ab518429c8589a4',
            'err_code' => '',
            'err_msg' => '',
            'merch_no' => '900021',
            'sign' => '5601ECED5C985C2DD855B60E3268FAD6',
            'trade_type' => 'NATIVE',
            'trade_state' => 'SUCCESS',
            'transaction_id' => '20180130113428995520',
            'out_trade_no' => '201801300000003951',
            'total_fee' => '1000',
            'fee_type' => 'CNY',
            'time_end' => '20180130113615',
        ];

        $entry = [
            'id' => '201801300000003952',
            'amount' => '10',
        ];

        $chengHueiPay = new ChengHueiPay();
        $chengHueiPay->setPrivateKey('test');
        $chengHueiPay->setOptions($options);
        $chengHueiPay->verifyOrderPayment($entry);
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
            'result_code' => 'SUCCESS',
            'nonce_str' => '7caf5e22ea3eb8175ab518429c8589a4',
            'err_code' => '',
            'err_msg' => '',
            'merch_no' => '900021',
            'sign' => '5601ECED5C985C2DD855B60E3268FAD6',
            'trade_type' => 'NATIVE',
            'trade_state' => 'SUCCESS',
            'transaction_id' => '20180130113428995520',
            'out_trade_no' => '201801300000003951',
            'total_fee' => '1000',
            'fee_type' => 'CNY',
            'time_end' => '20180130113615',
        ];

        $entry = [
            'id' => '201801300000003951',
            'amount' => '1',
        ];

        $chengHueiPay = new ChengHueiPay();
        $chengHueiPay->setPrivateKey('test');
        $chengHueiPay->setOptions($options);
        $chengHueiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'result_code' => 'SUCCESS',
            'nonce_str' => '7caf5e22ea3eb8175ab518429c8589a4',
            'err_code' => '',
            'err_msg' => '',
            'merch_no' => '900021',
            'sign' => '5601ECED5C985C2DD855B60E3268FAD6',
            'trade_type' => 'NATIVE',
            'trade_state' => 'SUCCESS',
            'transaction_id' => '20180130113428995520',
            'out_trade_no' => '201801300000003951',
            'total_fee' => '1000',
            'fee_type' => 'CNY',
            'time_end' => '20180130113615',
        ];

        $entry = [
            'id' => '201801300000003951',
            'amount' => '10',
        ];

        $chengHueiPay = new ChengHueiPay();
        $chengHueiPay->setPrivateKey('test');
        $chengHueiPay->setOptions($options);
        $chengHueiPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $chengHueiPay->getMsg());
    }
}
