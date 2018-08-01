<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\TungSao;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class TungSaoTest extends DurianTestCase
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

        $tungSao = new TungSao();
        $tungSao->getVerifyData();
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

        $tungSao = new TungSao();
        $tungSao->setPrivateKey('test');
        $tungSao->getVerifyData();
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
            'number' => '325110155420001',
            'amount' => '100',
            'orderId' => '201709050000006913',
            'paymentVendorId' => '999',
            'notify_url' => 'http://two123.comxa.com/',
        ];

        $tungSao = new TungSao();
        $tungSao->setPrivateKey('test');
        $tungSao->setOptions($options);
        $tungSao->getVerifyData();
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
            'number' => '325110155420001',
            'amount' => '1',
            'orderId' => '201712070000007888',
            'paymentVendorId' => '1103',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => '',
        ];

        $tungSao = new TungSao();
        $tungSao->setPrivateKey('test');
        $tungSao->setOptions($options);
        $tungSao->getVerifyData();
    }

    /**
     * 測試支付時沒有返回respCode
     */
    public function testPayReturnWithoutRespCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '325110155420001',
            'amount' => '0.01',
            'orderId' => '201712070000007888',
            'paymentVendorId' => '1090',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"barCode":"weixin://wxpay/bizpayurl?pr=swRRgsD","merchno":"325110155420001",' .
            '"message":"下单成功","refno":"10000006348674", "traceno":"201709050000006913"';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $tungSao = new TungSao();
        $tungSao->setContainer($this->container);
        $tungSao->setClient($this->client);
        $tungSao->setResponse($response);
        $tungSao->setPrivateKey('test');
        $tungSao->setOptions($options);
        $tungSao->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '交易失败,找不到二维码路由信息',
            180130
        );

        $options = [
            'number' => '325110155420001',
            'amount' => '0.1',
            'orderId' => '201709050000006920',
            'paymentVendorId' => '1097',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"merchno":"325110155420001","message":"交易失败,找不到二维码路由信息",' .
            '"respCode":"58","traceno":"201709050000006920"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $tungSao = new TungSao();
        $tungSao->setContainer($this->container);
        $tungSao->setClient($this->client);
        $tungSao->setResponse($response);
        $tungSao->setPrivateKey('test');
        $tungSao->setOptions($options);
        $tungSao->getVerifyData();
    }

    /**
     * 測試支付時沒有返回barCode
     */
    public function testPayReturnWithoutBarCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '325110155420001',
            'amount' => '0.01',
            'orderId' => '201709050000006913',
            'paymentVendorId' => '1103',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"merchno":"325110155420001","message":"下单成功","refno":"571902","respCode":"00",' .
            '"traceno":"201712070000007888"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $tungSao = new TungSao();
        $tungSao->setContainer($this->container);
        $tungSao->setClient($this->client);
        $tungSao->setResponse($response);
        $tungSao->setPrivateKey('test');
        $tungSao->setOptions($options);
        $tungSao->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'number' => '325110155420001',
            'amount' => '0.01',
            'orderId' => '201709050000006913',
            'paymentVendorId' => '1103',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"barCode":"http://api.y8pay.com/jk/pay/syt.html?refno=10009258074",' .
            '"merchno":"325110155420001","message":"下单成功","refno":"571902","respCode":"00",' .
            '"traceno":"201712070000007888"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $tungSao = new TungSao();
        $tungSao->setContainer($this->container);
        $tungSao->setClient($this->client);
        $tungSao->setResponse($response);
        $tungSao->setPrivateKey('test');
        $tungSao->setOptions($options);
        $data = $tungSao->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('http://api.y8pay.com/jk/pay/syt.html?refno=10009258074', $tungSao->getQrcode());
    }

    /**
     * 測試手機支付
     */
    public function testPayWithWap()
    {
        $options = [
            'number' => '325110155420001',
            'amount' => '0.01',
            'orderId' => '201712070000007899',
            'paymentVendorId' => '1104',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.pay.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"barCode":"http://api.y8pay.com/jk/pay/wapsyt.html?refno=10009263971",' .
            '"merchno":"325110155420001","message":"交易成功","refno":"574222","respCode":"00",' .
            '"traceno":"201712070000007899"}';

        $response = new Response();
        $response->setContent(iconv('UTF-8', 'GBK', $result));
        $response->addHeader('HTTP/1.1 200 OK');

        $tungSao = new TungSao();
        $tungSao->setContainer($this->container);
        $tungSao->setClient($this->client);
        $tungSao->setResponse($response);
        $tungSao->setPrivateKey('test');
        $tungSao->setOptions($options);
        $data = $tungSao->getVerifyData();

        $this->assertEquals('http://api.y8pay.com/jk/pay/wapsyt.html?refno=10009263971', $data['act_url']);
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

        $tungSao = new TungSao();
        $tungSao->verifyOrderPayment([]);
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

        $tungSao = new TungSao();
        $tungSao->setPrivateKey('test');
        $tungSao->verifyOrderPayment([]);
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

        $options = [
            'merchno' => '325110155420001',
            'status' => '1',
            'traceno' => '201712070000007888',
            'orderno' => '571902',
            'merchName' => 'gf20A390',
            'channelOrderno' => '',
            'amount' => '0.01',
            'transDate' => '2017-12-07',
            'channelTraceno' => '',
            'transTime' => '14:16:09',
            'payType' => '4',
            'openId' => 'http://api.y8pay.com/jk/pay/syt.html?refno=10009258074',
        ];

        $tungSao = new TungSao();
        $tungSao->setPrivateKey('test');
        $tungSao->setOptions($options);
        $tungSao->verifyOrderPayment([]);
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
            'merchno' => '325110155420001',
            'status' => '1',
            'traceno' => '201712070000007888',
            'orderno' => '571902',
            'merchName' => 'gf20A390',
            'channelOrderno' => '',
            'amount' => '0.01',
            'transDate' => '2017-12-07',
            'channelTraceno' => '',
            'transTime' => '14:16:09',
            'payType' => '4',
            'signature' => 'AA061BFF829EC6284180D25693741443',
            'openId' => 'http://api.y8pay.com/jk/pay/syt.html?refno=10009258074',
        ];

        $tungSao = new TungSao();
        $tungSao->setPrivateKey('test');
        $tungSao->setOptions($options);
        $tungSao->verifyOrderPayment([]);
    }

    /**
     * 測試返回時訂單未支付
     */
    public function testReturnWithUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $options = [
            'merchno' => '325110155420001',
            'status' => '0',
            'traceno' => '201712070000007888',
            'orderno' => '571902',
            'merchName' => 'gf20A390',
            'channelOrderno' => '',
            'amount' => '0.01',
            'transDate' => '2017-12-07',
            'channelTraceno' => '',
            'transTime' => '14:16:09',
            'payType' => '4',
            'signature' => '4e9bd748567c77f077ac30df2ce564e9',
            'openId' => 'http://api.y8pay.com/jk/pay/syt.html?refno=10009258074',
        ];

        $tungSao = new TungSao();
        $tungSao->setPrivateKey('test');
        $tungSao->setOptions($options);
        $tungSao->verifyOrderPayment([]);
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
            'merchno' => '325110155420001',
            'status' => '2',
            'traceno' => '201712070000007888',
            'orderno' => '571902',
            'merchName' => 'gf20A390',
            'channelOrderno' => '',
            'amount' => '0.01',
            'transDate' => '2017-12-07',
            'channelTraceno' => '',
            'transTime' => '14:16:09',
            'payType' => '4',
            'signature' => '84d67e63a280e8ecf5a64cc1229eff2b',
            'openId' => 'http://api.y8pay.com/jk/pay/syt.html?refno=10009258074',
        ];

        $tungSao = new TungSao();
        $tungSao->setPrivateKey('test');
        $tungSao->setOptions($options);
        $tungSao->verifyOrderPayment([]);
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
            'merchno' => '325110155420001',
            'status' => '1',
            'traceno' => '201712070000007888',
            'orderno' => '571902',
            'merchName' => 'gf20A390',
            'channelOrderno' => '',
            'amount' => '0.01',
            'transDate' => '2017-12-07',
            'channelTraceno' => '',
            'transTime' => '14:16:09',
            'payType' => '4',
            'signature' => 'ecae72b7297292531faea0f1d6c00bc6',
            'openId' => 'http://api.y8pay.com/jk/pay/syt.html?refno=10009258074',
        ];

        $entry = ['id' => '201707250000003581'];

        $tungSao = new TungSao();
        $tungSao->setPrivateKey('test');
        $tungSao->setOptions($options);
        $tungSao->verifyOrderPayment($entry);
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
            'merchno' => '325110155420001',
            'status' => '1',
            'traceno' => '201712070000007888',
            'orderno' => '571902',
            'merchName' => 'gf20A390',
            'channelOrderno' => '',
            'amount' => '0.01',
            'transDate' => '2017-12-07',
            'channelTraceno' => '',
            'transTime' => '14:16:09',
            'payType' => '4',
            'signature' => 'ecae72b7297292531faea0f1d6c00bc6',
            'openId' => 'http://api.y8pay.com/jk/pay/syt.html?refno=10009258074',
        ];

        $entry = [
            'id' => '201712070000007888',
            'amount' => '1',
        ];

        $tungSao = new TungSao();
        $tungSao->setPrivateKey('test');
        $tungSao->setOptions($options);
        $tungSao->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $options = [
            'merchno' => '325110155420001',
            'status' => '1',
            'traceno' => '201712070000007888',
            'orderno' => '571902',
            'merchName' => 'gf20A390',
            'channelOrderno' => '',
            'amount' => '0.01',
            'transDate' => '2017-12-07',
            'channelTraceno' => '',
            'transTime' => '14:16:09',
            'payType' => '4',
            'signature' => 'ecae72b7297292531faea0f1d6c00bc6',
            'openId' => 'http://api.y8pay.com/jk/pay/syt.html?refno=10009258074',
        ];

        $entry = [
            'id' => '201712070000007888',
            'amount' => '0.01',
        ];

        $tungSao = new TungSao();
        $tungSao->setPrivateKey('test');
        $tungSao->setOptions($options);
        $tungSao->verifyOrderPayment($entry);

        $this->assertEquals('success', $tungSao->getMsg());
    }
}
