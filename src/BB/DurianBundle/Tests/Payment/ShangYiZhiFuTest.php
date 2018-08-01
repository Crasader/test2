<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ShangYiZhiFu;
use Buzz\Message\Response;

class ShangYiZhiFuTest extends DurianTestCase
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
     * 測試支付時沒有帶入privateKey的情況
     */
    public function testPayEncodeWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $shangYiZhiFu = new ShangYiZhiFu();
        $shangYiZhiFu->getVerifyData();
    }

    /**
     * 測試支付時未指定支付參數
     */
    public function testPayEncodeWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = ['number' => ''];

        $shangYiZhiFu = new ShangYiZhiFu();
        $shangYiZhiFu->setPrivateKey('1234');
        $shangYiZhiFu->setOptions($sourceData);
        $shangYiZhiFu->getVerifyData();
    }

    /**
     * 測試支付時沒有帶入postUrl的情況
     */
    public function testPayEncodeWithoutPostUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = [
            'number' => '1024',
            'paymentVendorId' => '1',
            'amount' => '1.10',
            'orderId' => '201801030000007722',
            'notify_url' => 'http://two123.comxa.com/',
            'postUrl' => '',
        ];

        $shangYiZhiFu = new ShangYiZhiFu();
        $shangYiZhiFu->setPrivateKey('1234');
        $shangYiZhiFu->setOptions($sourceData);
        $shangYiZhiFu->getVerifyData();
    }

    /**
     * 測試支付時代入支付平台不支援的銀行
     */
    public function testPayEncodeUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '1024',
            'paymentVendorId' => '999',
            'amount' => '1.10',
            'orderId' => '201801030000007722',
            'notify_url' => 'http://two123.comxa.com/',
            'postUrl' => 'https://gateway.shangyizhifu.com/chargebank.aspx',
        ];

        $shangYiZhiFu = new ShangYiZhiFu();
        $shangYiZhiFu->setPrivateKey('1234');
        $shangYiZhiFu->setOptions($sourceData);
        $shangYiZhiFu->getVerifyData();
    }

    /**
     * 測試手機支付提交地址未返回
     */
    public function testNoWapPayUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Empty Payment Gateway response',
            180089
        );

        $result = '';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '1024',
            'paymentVendorId' => '1097',
            'amount' => '1.10',
            'orderId' => '201801030000007722',
            'notify_url' => 'http://two123.comxa.com/',
            'postUrl' => 'https://gateway.shangyizhifu.com/chargebank.aspx',
            'verify_url' => 'payment.https.gateway.shangyizhifu.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $shangYiZhiFu = new ShangYiZhiFu();
        $shangYiZhiFu->setContainer($this->container);
        $shangYiZhiFu->setClient($this->client);
        $shangYiZhiFu->setResponse($response);
        $shangYiZhiFu->setPrivateKey('test');
        $shangYiZhiFu->setOptions($sourceData);
        $encodeData = $shangYiZhiFu->getVerifyData();
    }

    /**
     * 測試手機支付提交地址格式不正確
     */
    public function testWapPayPayUrlFormatError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = 'https://api.ulopay.com';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $sourceData = [
            'number' => '1024',
            'paymentVendorId' => '1097',
            'amount' => '1.10',
            'orderId' => '201801030000007722',
            'notify_url' => 'http://two123.comxa.com/',
            'postUrl' => 'https://gateway.shangyizhifu.com/chargebank.aspx',
            'verify_url' => 'payment.https.gateway.shangyizhifu.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $shangYiZhiFu = new ShangYiZhiFu();
        $shangYiZhiFu->setContainer($this->container);
        $shangYiZhiFu->setClient($this->client);
        $shangYiZhiFu->setResponse($response);
        $shangYiZhiFu->setPrivateKey('test');
        $shangYiZhiFu->setOptions($sourceData);
        $encodeData = $shangYiZhiFu->getVerifyData();
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $sourceData = [
            'number' => '1024',
            'paymentVendorId' => '1097',
            'amount' => '1.10',
            'orderId' => '201801030000007722',
            'notify_url' => 'http://two123.comxa.com/',
            'postUrl' => 'https://gateway.shangyizhifu.com/chargebank.aspx',
            'verify_url' => 'payment.https.gateway.shangyizhifu.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = 'https://api.ulopay.com/pay/jspay?ret=1&prepay_id=7848394169c272332bcc15a95bdeb5aa';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');

        $shangYiZhiFu = new ShangYiZhiFu();
        $shangYiZhiFu->setContainer($this->container);
        $shangYiZhiFu->setClient($this->client);
        $shangYiZhiFu->setResponse($response);
        $shangYiZhiFu->setPrivateKey('test');
        $shangYiZhiFu->setOptions($sourceData);
        $encodeData = $shangYiZhiFu->getVerifyData();

        $this->assertEquals(1, $encodeData['params']['ret']);
        $this->assertEquals('7848394169c272332bcc15a95bdeb5aa', $encodeData['params']['prepay_id']);
        // 檢查要提交的網址是否正確
        $this->assertEquals('https://api.ulopay.com/pay/jspay', $encodeData['post_url']);
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '1024',
            'paymentVendorId' => '1103',
            'amount' => '1.10',
            'orderId' => '201801030000007722',
            'notify_url' => 'http://candj.huhu.tw/',
            'postUrl' => 'https://gateway.shangyizhifu.com/chargebank.aspx',
        ];

        $shangYiZhiFu = new ShangYiZhiFu();
        $shangYiZhiFu->setPrivateKey('test');
        $shangYiZhiFu->setOptions($sourceData);
        $data = $shangYiZhiFu->getVerifyData();

        // 檢查要提交的網址是否正確
        $queryData = [];
        $queryData['parter'] = $sourceData['number'];
        $queryData['type'] = '993';
        $queryData['value'] = $sourceData['amount'];
        $queryData['orderid'] = $sourceData['orderId'];
        $queryData['callbackurl'] = $sourceData['notify_url'];
        $queryData['hrefbackurl'] = '';
        $queryData['payerIp'] = '';
        $queryData['attach'] = '';
        $queryData['sign'] = 'abcfffcd6d1445069e6ff4eadb062d65';
        $queryData['agent'] = '';

        $this->assertEquals($sourceData['postUrl'] . '?' . http_build_query($queryData), $data['post_url']);
    }

    /**
     * 測試解密基本參數設定沒有帶入key的情況
     */
    public function testSetDecodeSourceWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $shangYiZhiFu = new ShangYiZhiFu();
        $shangYiZhiFu->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台未指定返回參數
     */
    public function testVerifyWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'orderid' => '201609220000004434',
            'opstate' => '0',
        ];

        $shangYiZhiFu = new ShangYiZhiFu();
        $shangYiZhiFu->setPrivateKey('1234');
        $shangYiZhiFu->setOptions($sourceData);
        $shangYiZhiFu->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台缺少回傳sign(加密簽名)
     */
    public function testVerifyWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'orderid' => '201801030000007722',
            'opstate' => '0',
            'ovalue' => '1.10',
            'sysorderid' => 'B5224690624018404326',
            'systime' => '2018/01/03 12:08:14',
            'attach' => '',
            'msg' => '',
        ];

        $shangYiZhiFu = new ShangYiZhiFu();
        $shangYiZhiFu->setPrivateKey('test');
        $shangYiZhiFu->setOptions($sourceData);
        $shangYiZhiFu->verifyOrderPayment([]);
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
            'orderid' => '201801030000007722',
            'opstate' => '0',
            'ovalue' => '1.10',
            'sysorderid' => 'B5224690624018404326',
            'systime' => '2018/01/03 12:08:14',
            'attach' => '',
            'msg' => '',
            'sign' => '1234',
        ];

        $shangYiZhiFu = new ShangYiZhiFu();
        $shangYiZhiFu->setPrivateKey('test');
        $shangYiZhiFu->setOptions($sourceData);
        $shangYiZhiFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時取得無效的支付參數
     */
    public function testReturnInvalidPayParameters()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Invalid pay parameters',
            180129
        );

        $sourceData = [
            'orderid' => '201801030000007722',
            'opstate' => '-1',
            'ovalue' => '1.10',
            'sysorderid' => 'B5224690624018404326',
            'systime' => '2018/01/03 12:08:14',
            'attach' => '',
            'msg' => '',
            'sign' => 'cd9c385842bf162fe699c16e4a6155e3',
        ];

        $shangYiZhiFu = new ShangYiZhiFu();
        $shangYiZhiFu->setPrivateKey('1234');
        $shangYiZhiFu->setOptions($sourceData);
        $shangYiZhiFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為支付平台驗證簽名錯誤
     */
    public function testReturnPaymentGatewaySignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Merchant sign error',
            180127
        );

        $sourceData = [
            'orderid' => '201801030000007722',
            'opstate' => '-2',
            'ovalue' => '1.10',
            'sysorderid' => 'B5224690624018404326',
            'systime' => '2018/01/03 12:08:14',
            'attach' => '',
            'msg' => '',
            'sign' => 'f3485c8bae3406c3e82747a586d8f0da',
        ];

        $shangYiZhiFu = new ShangYiZhiFu();
        $shangYiZhiFu->setPrivateKey('1234');
        $shangYiZhiFu->setOptions($sourceData);
        $shangYiZhiFu->verifyOrderPayment([]);
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
            'orderid' => '201801030000007722',
            'opstate' => '-8',
            'ovalue' => '1.10',
            'sysorderid' => 'B5224690624018404326',
            'systime' => '2018/01/03 12:08:14',
            'attach' => '',
            'msg' => '',
            'sign' => '993e73551f9e637b63fc16ddea09d2cf',
        ];

        $shangYiZhiFu = new ShangYiZhiFu();
        $shangYiZhiFu->setPrivateKey('test');
        $shangYiZhiFu->setOptions($sourceData);
        $shangYiZhiFu->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'orderid' => '201801030000007722',
            'opstate' => '0',
            'ovalue' => '1.10',
            'sysorderid' => 'B5224690624018404326',
            'systime' => '2018/01/03 12:08:14',
            'attach' => '',
            'msg' => '',
            'sign' => 'c02fb0ed4e6b3667321e7a4450894810',
        ];

        $entry = ['id' => '201801030000000000'];

        $shangYiZhiFu = new ShangYiZhiFu();
        $shangYiZhiFu->setPrivateKey('test');
        $shangYiZhiFu->setOptions($sourceData);
        $shangYiZhiFu->verifyOrderPayment($entry);
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

        $sourceData = [
            'orderid' => '201801030000007722',
            'opstate' => '0',
            'ovalue' => '1.10',
            'sysorderid' => 'B5224690624018404326',
            'systime' => '2018/01/03 12:08:14',
            'attach' => '',
            'msg' => '',
            'sign' => 'c02fb0ed4e6b3667321e7a4450894810',
        ];

        $entry = [
            'id' => '201801030000007722',
            'amount' => '1.00',
        ];

        $shangYiZhiFu = new ShangYiZhiFu();
        $shangYiZhiFu->setPrivateKey('test');
        $shangYiZhiFu->setOptions($sourceData);
        $shangYiZhiFu->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'orderid' => '201801030000007722',
            'opstate' => '0',
            'ovalue' => '1.10',
            'sysorderid' => 'B5224690624018404326',
            'systime' => '2018/01/03 12:08:14',
            'attach' => '',
            'msg' => '',
            'sign' => 'c02fb0ed4e6b3667321e7a4450894810',
        ];

        $entry = [
            'id' => '201801030000007722',
            'amount' => '1.10',
        ];

        $shangYiZhiFu = new ShangYiZhiFu();
        $shangYiZhiFu->setPrivateKey('test');
        $shangYiZhiFu->setOptions($sourceData);
        $shangYiZhiFu->verifyOrderPayment($entry);

        $this->assertEquals('opstate=0', $shangYiZhiFu->getMsg());
    }
}
