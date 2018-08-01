<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YunFuxPay;
use Buzz\Message\Response;

class YunFuxPayTest extends DurianTestCase
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

        $yunFuXPay = new YunFuxPay();
        $yunFuXPay->getVerifyData();
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

        $yunFuXPay = new YunFuxPay();
        $yunFuXPay->setPrivateKey('test');
        $yunFuXPay->getVerifyData();
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
            'number' => '2017031920431360256',
            'orderId' => '201707190000003432',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://test.com/return.php',
            'paymentVendorId' => '9999',
        ];

        $yunFuXPay = new YunFuxPay();
        $yunFuXPay->setPrivateKey('test');
        $yunFuXPay->setOptions($options);
        $yunFuXPay->getVerifyData();
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
            'number' => '2017031920431360256',
            'orderId' => '201707190000003432',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://test.com/return.php',
            'verify_url' => '',
            'paymentVendorId' => '1090',
        ];

        $yunFuXPay = new YunFuxPay();
        $yunFuXPay->setPrivateKey('test');
        $yunFuXPay->setOptions($options);
        $yunFuXPay->getVerifyData();
    }

    /**
     * 測試支付對外返回不是xml格式
     */
    public function testPayReturnNotXml()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Invalid XML format',
            180121
        );

        $options = [
            'number' => '2017031920431360256',
            'orderId' => '201707190000003432',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://test.com/return.php',
            'verify_url' => 'payment.https.pay.test.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1090',
        ];

        $result = '不是XML';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('Content-Type:application/xml;charset=UTF-8;');

        $yunFuXPay = new YunFuxPay();
        $yunFuXPay->setContainer($this->container);
        $yunFuXPay->setClient($this->client);
        $yunFuXPay->setResponse($response);
        $yunFuXPay->setPrivateKey('test');
        $yunFuXPay->setOptions($options);
        $yunFuXPay->getVerifyData();
    }

    /**
     * 測試支付對外返回沒有result_code的情況
     */
    public function testPayReturnWithoutResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '2017031920431360256',
            'orderId' => '201707190000003432',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://test.com/return.php',
            'verify_url' => 'payment.https.pay.test.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1090',
        ];

        $result = '<xml></xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('Content-Type:application/xml;charset=UTF-8;');

        $yunFuXPay = new YunFuxPay();
        $yunFuXPay->setContainer($this->container);
        $yunFuXPay->setClient($this->client);
        $yunFuXPay->setResponse($response);
        $yunFuXPay->setPrivateKey('test');
        $yunFuXPay->setOptions($options);
        $yunFuXPay->getVerifyData();
    }

    /**
     * 測試支付對外返回時result_code不為0
     */
    public function testPayReturnResultCodeNotZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '加签失败',
            180130
        );

        $options = [
            'number' => '2017031920431360256',
            'orderId' => '201707190000003432',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://test.com/return.php',
            'verify_url' => 'payment.https.pay.test.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1090',
        ];

        $result = '<xml>' .
            '<result_code><![CDATA[10302]]></result_code>' .
            '<err_msg><![CDATA[加签失败]]></err_msg>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('Content-Type:application/xml;charset=UTF-8;');

        $yunFuXPay = new YunFuxPay();
        $yunFuXPay->setContainer($this->container);
        $yunFuXPay->setClient($this->client);
        $yunFuXPay->setResponse($response);
        $yunFuXPay->setPrivateKey('test');
        $yunFuXPay->setOptions($options);
        $yunFuXPay->getVerifyData();
    }

    /**
     * 測試支付對外返回沒有status的情況
     */
    public function testPayReturnWithoutStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '2017031920431360256',
            'orderId' => '201707190000003432',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://test.com/return.php',
            'verify_url' => 'payment.https.pay.test.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1090',
        ];

        $result = '<xml>' .
            '<appid><![CDATA[wx0ef853786635dfe7]]></appid>' .
            '<charset><![CDATA[UTF-8]]></charset>' .
            '<code_img_url><![CDATA[weixin://wxpay/bizpayurl?pr=LA8BTwt]]></code_img_url>' .
            '<code_url><![CDATA[weixin://wxpay/bizpayurl?pr=LA8BTwt]]></code_url>' .
            '<mch_id><![CDATA[2017031920431360256]]></mch_id>' .
            '<nonce_str><![CDATA[f73346b38e477b6aba00d86c66f7a1a0]]></nonce_str>' .
            '<result_code><![CDATA[0]]></result_code>' .
            '<sign><![CDATA[25455D83E8AD89821E4204BFB5F2D171]]></sign>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<uuid><![CDATA[21cba4e2d2890fc51d251160ec9108f72]]></uuid>' .
            '<version><![CDATA[2.0]]></version>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('Content-Type:application/xml;charset=UTF-8;');

        $yunFuXPay = new YunFuxPay();
        $yunFuXPay->setContainer($this->container);
        $yunFuXPay->setClient($this->client);
        $yunFuXPay->setResponse($response);
        $yunFuXPay->setPrivateKey('test');
        $yunFuXPay->setOptions($options);
        $yunFuXPay->getVerifyData();
    }

    /**
     * 測試支付對外返回時status不為0
     */
    public function testPayReturnStatusNotZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '103560018443',
            'orderId' => '201705190000006351',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://test.com/return.php',
            'verify_url' => 'payment.https.pay.test.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1103',
        ];

        $result = '<xml>' .
            '<appid><![CDATA[wx0ef853786635dfe7]]></appid>' .
            '<charset><![CDATA[UTF-8]]></charset>' .
            '<code_img_url><![CDATA[weixin://wxpay/bizpayurl?pr=LA8BTwt]]></code_img_url>' .
            '<code_url><![CDATA[weixin://wxpay/bizpayurl?pr=LA8BTwt]]></code_url>' .
            '<mch_id><![CDATA[2017031920431360256]]></mch_id>' .
            '<nonce_str><![CDATA[f73346b38e477b6aba00d86c66f7a1a0]]></nonce_str>' .
            '<result_code><![CDATA[0]]></result_code>' .
            '<sign><![CDATA[25455D83E8AD89821E4204BFB5F2D171]]></sign>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status><![CDATA[1]]></status>' .
            '<uuid><![CDATA[21cba4e2d2890fc51d251160ec9108f72]]></uuid>' .
            '<version><![CDATA[2.0]]></version>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('Content-Type:application/xml;charset=UTF-8;');

        $yunFuXPay = new YunFuxPay();
        $yunFuXPay->setContainer($this->container);
        $yunFuXPay->setClient($this->client);
        $yunFuXPay->setResponse($response);
        $yunFuXPay->setPrivateKey('test');
        $yunFuXPay->setOptions($options);
        $yunFuXPay->getVerifyData();
    }

    /**
     * 測試二維支付返回沒有code_url
     */
    public function testQRcodePayReturnWithoutCodeUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '2017031920431360256',
            'orderId' => '201707190000003432',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://test.com/return.php',
            'verify_url' => 'payment.https.pay.test.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1090',
        ];

        $result = '<xml>' .
            '<appid><![CDATA[wx0ef853786635dfe7]]></appid>' .
            '<charset><![CDATA[UTF-8]]></charset>' .
            '<code_img_url><![CDATA[weixin://wxpay/bizpayurl?pr=LA8BTwt]]></code_img_url>' .
            '<mch_id><![CDATA[2017031920431360256]]></mch_id>' .
            '<nonce_str><![CDATA[f73346b38e477b6aba00d86c66f7a1a0]]></nonce_str>' .
            '<result_code><![CDATA[0]]></result_code>' .
            '<sign><![CDATA[25455D83E8AD89821E4204BFB5F2D171]]></sign>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status><![CDATA[0]]></status>' .
            '<uuid><![CDATA[21cba4e2d2890fc51d251160ec9108f72]]></uuid>' .
            '<version><![CDATA[2.0]]></version>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('Content-Type:application/xml;charset=UTF-8;');

        $yunFuXPay = new YunFuxPay();
        $yunFuXPay->setContainer($this->container);
        $yunFuXPay->setClient($this->client);
        $yunFuXPay->setResponse($response);
        $yunFuXPay->setPrivateKey('test');
        $yunFuXPay->setOptions($options);
        $yunFuXPay->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQRcodePay()
    {
        $options = [
            'number' => '2017031920431360256',
            'orderId' => '201707190000003432',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://test.com/return.php',
            'verify_url' => 'payment.https.pay.test.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1090',
        ];

        $result = '<xml>' .
            '<appid><![CDATA[wx0ef853786635dfe7]]></appid>' .
            '<charset><![CDATA[UTF-8]]></charset>' .
            '<code_img_url><![CDATA[weixin://wxpay/bizpayurl?pr=LA8BTwt]]></code_img_url>' .
            '<code_url><![CDATA[weixin://wxpay/bizpayurl?pr=LA8BTwt]]></code_url>' .
            '<mch_id><![CDATA[2017031920431360256]]></mch_id>' .
            '<nonce_str><![CDATA[f73346b38e477b6aba00d86c66f7a1a0]]></nonce_str>' .
            '<result_code><![CDATA[0]]></result_code>' .
            '<sign><![CDATA[25455D83E8AD89821E4204BFB5F2D171]]></sign>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status><![CDATA[0]]></status>' .
            '<uuid><![CDATA[21cba4e2d2890fc51d251160ec9108f72]]></uuid>' .
            '<version><![CDATA[2.0]]></version>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('Content-Type:application/xml;charset=UTF-8;');

        $yunFuXPay = new YunFuxPay();
        $yunFuXPay->setContainer($this->container);
        $yunFuXPay->setClient($this->client);
        $yunFuXPay->setResponse($response);
        $yunFuXPay->setPrivateKey('test');
        $yunFuXPay->setOptions($options);
        $data = $yunFuXPay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=LA8BTwt', $yunFuXPay->getQrcode());
    }

    /**
     * 測試網銀返回缺少url
     */
    public function testBankPayReturnWithoutUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'number' => '2017031920431360256',
            'orderId' => '201707190000003432',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://test.com/return.php',
            'verify_url' => 'payment.https.pay.test.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1102',
        ];

        $result = '<xml>' .
            '<status><![CDATA[0]]></status>' .
            '<result_code><![CDATA[0]]></result_code>' .
            '<charset><![CDATA[UTF-8]]></charset>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<version><![CDATA[2.0]]></version>' .
            '<mch_id><![CDATA[2017031920431360256]]></mch_id>' .
            '<sign><![CDATA[142D9BA80C03D64E03FDE916D10DDFC4]]></sign>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('Content-Type:application/xml;charset=UTF-8;');

        $yunFuXPay = new YunFuxPay();
        $yunFuXPay->setContainer($this->container);
        $yunFuXPay->setClient($this->client);
        $yunFuXPay->setResponse($response);
        $yunFuXPay->setPrivateKey('test');
        $yunFuXPay->setOptions($options);
        $yunFuXPay->getVerifyData();
    }

    /**
     * 測試網銀
     */
    public function testBankPay()
    {
        $options = [
            'number' => '2017031920431360256',
            'orderId' => '201707190000003432',
            'username' => 'php1test',
            'amount' => '100',
            'ip' => '127.0.0.1',
            'notify_url' => 'http://test.com/return.php',
            'verify_url' => 'payment.https.pay.test.cn',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'paymentVendorId' => '1102',
        ];

        $result = '<xml>' .
            '<status><![CDATA[0]]></status>' .
            '<result_code><![CDATA[0]]></result_code>' .
            '<url><![CDATA[http://newpay.yunfux.cn/pay/api/pay/wywap?uuid=AF73D0ACDB52A5333527AEE4B2530961]]></url>' .
            '<charset><![CDATA[UTF-8]]></charset>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<version><![CDATA[2.0]]></version>' .
            '<mch_id><![CDATA[2017031920431360256]]></mch_id>' .
            '<sign><![CDATA[142D9BA80C03D64E03FDE916D10DDFC4]]></sign>' .
            '</xml>';

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent($result);
        $response->addHeader('Content-Type:application/xml;charset=UTF-8;');

        $yunFuXPay = new YunFuxPay();
        $yunFuXPay->setContainer($this->container);
        $yunFuXPay->setClient($this->client);
        $yunFuXPay->setResponse($response);
        $yunFuXPay->setPrivateKey('test');
        $yunFuXPay->setOptions($options);
        $data = $yunFuXPay->getVerifyData();

        $url = 'http://newpay.yunfux.cn/pay/api/pay/wywap?uuid=AF73D0ACDB52A5333527AEE4B2530961';
        $this->assertEquals($url, $data['act_url']);
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

        $yunFuXPay = new YunFuxPay();
        $yunFuXPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少content
     */
    public function testReturnWithoutContent()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $yunFuXPay = new YunFuxPay();
        $yunFuXPay->setPrivateKey('test');
        $yunFuXPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回不是xml格式
     */
    public function testReturnNotXml()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Invalid XML format',
            180121
        );

        $xml = '不是xml';

        $options = ['content' => $xml];

        $yunFuXPay = new YunFuxPay();
        $yunFuXPay->setPrivateKey('test');
        $yunFuXPay->setOptions($options);
        $yunFuXPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時xml缺少status
     */
    public function testReturnWithoutStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $xml = '<xml></xml>';

        $options = ['content' => $xml];

        $yunFuXPay = new YunFuxPay();
        $yunFuXPay->setPrivateKey('test');
        $yunFuXPay->setOptions($options);
        $yunFuXPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時status不是且有message
     */
    public function testReturnStatusNotZeroAndHasMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '支付失敗',
            180130
        );

        $xml = '<xml>' .
            '<bank_type><![CDATA[CFT]]></bank_type>' .
            '<charset><![CDATA[UTF-8]]></charset>' .
            '<fee_type><![CDATA[CNY]]></fee_type>' .
            '<is_subscribe><![CDATA[N]]></is_subscribe>' .
            '<mch_id><![CDATA[2017031920431360256]]></mch_id>' .
            '<nonce_str><![CDATA[1500425002684]]></nonce_str>' .
            '<openid><![CDATA[olE5xwHvAwQ9g2m3JuTdPMwMtzWc]]></openid>' .
            '<out_trade_no><![CDATA[201707190000003432]]></out_trade_no>' .
            '<out_transaction_id><![CDATA[4000222001201707191508151673]]></out_transaction_id>' .
            '<pay_result><![CDATA[0]]></pay_result>' .
            '<result_code><![CDATA[0]]></result_code>' .
            '<sign><![CDATA[2817D167E8B59A24C8D2781D63CB410C]]></sign>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<message><![CDATA[支付失敗]]></message>' .
            '<status><![CDATA[1]]></status>' .
            '<time_end><![CDATA[20170719084322]]></time_end>' .
            '<total_fee><![CDATA[10]]></total_fee>' .
            '<trade_type><![CDATA[pay.weixin.native]]></trade_type>' .
            '<transaction_id><![CDATA[150530046896201707192211024464]]></transaction_id>' .
            '<version><![CDATA[2.0]]></version>' .
            '</xml>';

        $options = ['content' => $xml];

        $yunFuXPay = new YunFuxPay();
        $yunFuXPay->setPrivateKey('test');
        $yunFuXPay->setOptions($options);
        $yunFuXPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時xml缺少result_code
     */
    public function testReturnWithoutResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $xml = '<xml>' .
            '<bank_type><![CDATA[CFT]]></bank_type>' .
            '<charset><![CDATA[UTF-8]]></charset>' .
            '<fee_type><![CDATA[CNY]]></fee_type>' .
            '<is_subscribe><![CDATA[N]]></is_subscribe>' .
            '<mch_id><![CDATA[2017031920431360256]]></mch_id>' .
            '<nonce_str><![CDATA[1500425002684]]></nonce_str>' .
            '<openid><![CDATA[olE5xwHvAwQ9g2m3JuTdPMwMtzWc]]></openid>' .
            '<out_trade_no><![CDATA[201707190000003432]]></out_trade_no>' .
            '<out_transaction_id><![CDATA[4000222001201707191508151673]]></out_transaction_id>' .
            '<pay_result><![CDATA[0]]></pay_result>' .
            '<sign><![CDATA[2817D167E8B59A24C8D2781D63CB410C]]></sign>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status><![CDATA[0]]></status>' .
            '<time_end><![CDATA[20170719084322]]></time_end>' .
            '<total_fee><![CDATA[10]]></total_fee>' .
            '<trade_type><![CDATA[pay.weixin.native]]></trade_type>' .
            '<transaction_id><![CDATA[150530046896201707192211024464]]></transaction_id>' .
            '<version><![CDATA[2.0]]></version>' .
            '</xml>';

        $options = ['content' => $xml];

        $yunFuXPay = new YunFuxPay();
        $yunFuXPay->setPrivateKey('test');
        $yunFuXPay->setOptions($options);
        $yunFuXPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時result_code不是0
     */
    public function testReturnResultCodeNotZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $xml = '<xml>' .
            '<bank_type><![CDATA[CFT]]></bank_type>' .
            '<charset><![CDATA[UTF-8]]></charset>' .
            '<fee_type><![CDATA[CNY]]></fee_type>' .
            '<is_subscribe><![CDATA[N]]></is_subscribe>' .
            '<mch_id><![CDATA[2017031920431360256]]></mch_id>' .
            '<nonce_str><![CDATA[1500425002684]]></nonce_str>' .
            '<openid><![CDATA[olE5xwHvAwQ9g2m3JuTdPMwMtzWc]]></openid>' .
            '<out_trade_no><![CDATA[201707190000003432]]></out_trade_no>' .
            '<out_transaction_id><![CDATA[4000222001201707191508151673]]></out_transaction_id>' .
            '<pay_result><![CDATA[0]]></pay_result>' .
            '<result_code><![CDATA[1]]></result_code>' .
            '<sign><![CDATA[2817D167E8B59A24C8D2781D63CB410C]]></sign>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status><![CDATA[0]]></status>' .
            '<time_end><![CDATA[20170719084322]]></time_end>' .
            '<total_fee><![CDATA[10]]></total_fee>' .
            '<trade_type><![CDATA[pay.weixin.native]]></trade_type>' .
            '<transaction_id><![CDATA[150530046896201707192211024464]]></transaction_id>' .
            '<version><![CDATA[2.0]]></version>' .
            '</xml>';

        $options = ['content' => $xml];

        $yunFuXPay = new YunFuxPay();
        $yunFuXPay->setPrivateKey('test');
        $yunFuXPay->setOptions($options);
        $yunFuXPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時xml未指定返回參數
     */
    public function testReturnWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $xml = '<xml>' .
            '<bank_type><![CDATA[CFT]]></bank_type>' .
            '<charset><![CDATA[UTF-8]]></charset>' .
            '<fee_type><![CDATA[CNY]]></fee_type>' .
            '<is_subscribe><![CDATA[N]]></is_subscribe>' .
            '<nonce_str><![CDATA[1500425002684]]></nonce_str>' .
            '<openid><![CDATA[olE5xwHvAwQ9g2m3JuTdPMwMtzWc]]></openid>' .
            '<out_transaction_id><![CDATA[4000222001201707191508151673]]></out_transaction_id>' .
            '<pay_result><![CDATA[0]]></pay_result>' .
            '<result_code><![CDATA[0]]></result_code>' .
            '<sign><![CDATA[2817D167E8B59A24C8D2781D63CB410C]]></sign>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status><![CDATA[0]]></status>' .
            '<time_end><![CDATA[20170719084322]]></time_end>' .
            '<total_fee><![CDATA[10]]></total_fee>' .
            '<trade_type><![CDATA[pay.weixin.native]]></trade_type>' .
            '<transaction_id><![CDATA[150530046896201707192211024464]]></transaction_id>' .
            '<version><![CDATA[2.0]]></version>' .
            '</xml>';

        $options = ['content' => $xml];

        $yunFuXPay = new YunFuxPay();
        $yunFuXPay->setPrivateKey('test');
        $yunFuXPay->setOptions($options);
        $yunFuXPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時xml缺少sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $xml = '<xml>' .
            '<bank_type><![CDATA[CFT]]></bank_type>' .
            '<charset><![CDATA[UTF-8]]></charset>' .
            '<fee_type><![CDATA[CNY]]></fee_type>' .
            '<is_subscribe><![CDATA[N]]></is_subscribe>' .
            '<mch_id><![CDATA[2017031920431360256]]></mch_id>' .
            '<nonce_str><![CDATA[1500425002684]]></nonce_str>' .
            '<openid><![CDATA[olE5xwHvAwQ9g2m3JuTdPMwMtzWc]]></openid>' .
            '<out_trade_no><![CDATA[201707190000003432]]></out_trade_no>' .
            '<out_transaction_id><![CDATA[4000222001201707191508151673]]></out_transaction_id>' .
            '<pay_result><![CDATA[0]]></pay_result>' .
            '<result_code><![CDATA[0]]></result_code>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status><![CDATA[0]]></status>' .
            '<time_end><![CDATA[20170719084322]]></time_end>' .
            '<total_fee><![CDATA[10]]></total_fee>' .
            '<trade_type><![CDATA[pay.weixin.native]]></trade_type>' .
            '<transaction_id><![CDATA[150530046896201707192211024464]]></transaction_id>' .
            '<version><![CDATA[2.0]]></version>' .
            '</xml>';

        $options = ['content' => $xml];

        $yunFuXPay = new YunFuxPay();
        $yunFuXPay->setPrivateKey('test');
        $yunFuXPay->setOptions($options);
        $yunFuXPay->verifyOrderPayment([]);
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

        $xml = '<xml>' .
            '<bank_type><![CDATA[CFT]]></bank_type>' .
            '<charset><![CDATA[UTF-8]]></charset>' .
            '<fee_type><![CDATA[CNY]]></fee_type>' .
            '<is_subscribe><![CDATA[N]]></is_subscribe>' .
            '<mch_id><![CDATA[2017031920431360256]]></mch_id>' .
            '<nonce_str><![CDATA[1500425002684]]></nonce_str>' .
            '<openid><![CDATA[olE5xwHvAwQ9g2m3JuTdPMwMtzWc]]></openid>' .
            '<out_trade_no><![CDATA[201707190000003432]]></out_trade_no>' .
            '<out_transaction_id><![CDATA[4000222001201707191508151673]]></out_transaction_id>' .
            '<pay_result><![CDATA[0]]></pay_result>' .
            '<result_code><![CDATA[0]]></result_code>' .
            '<sign><![CDATA[2817D167E8B59A24C8D2781D63CB410C]]></sign>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status><![CDATA[0]]></status>' .
            '<time_end><![CDATA[20170719084322]]></time_end>' .
            '<total_fee><![CDATA[10]]></total_fee>' .
            '<trade_type><![CDATA[pay.weixin.native]]></trade_type>' .
            '<transaction_id><![CDATA[150530046896201707192211024464]]></transaction_id>' .
            '<version><![CDATA[2.0]]></version>' .
            '</xml>';

        $options = ['content' => $xml];

        $yunFuXPay = new YunFuxPay();
        $yunFuXPay->setPrivateKey('test');
        $yunFuXPay->setOptions($options);
        $yunFuXPay->verifyOrderPayment([]);
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

        $xml = '<xml>' .
            '<bank_type><![CDATA[CFT]]></bank_type>' .
            '<charset><![CDATA[UTF-8]]></charset>' .
            '<fee_type><![CDATA[CNY]]></fee_type>' .
            '<is_subscribe><![CDATA[N]]></is_subscribe>' .
            '<mch_id><![CDATA[2017031920431360256]]></mch_id>' .
            '<nonce_str><![CDATA[1500425002684]]></nonce_str>' .
            '<openid><![CDATA[olE5xwHvAwQ9g2m3JuTdPMwMtzWc]]></openid>' .
            '<out_trade_no><![CDATA[201707190000003432]]></out_trade_no>' .
            '<out_transaction_id><![CDATA[4000222001201707191508151673]]></out_transaction_id>' .
            '<pay_result><![CDATA[0]]></pay_result>' .
            '<result_code><![CDATA[0]]></result_code>' .
            '<sign><![CDATA[4FA48D73D1295E4647986A70E8A9A95B]]></sign>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status><![CDATA[0]]></status>' .
            '<time_end><![CDATA[20170719084322]]></time_end>' .
            '<total_fee><![CDATA[10]]></total_fee>' .
            '<trade_type><![CDATA[pay.weixin.native]]></trade_type>' .
            '<transaction_id><![CDATA[150530046896201707192211024464]]></transaction_id>' .
            '<version><![CDATA[2.0]]></version>' .
            '</xml>';

        $options = ['content' => $xml];

        $entry = ['id' => '201707190000003431'];

        $yunFuXPay = new YunFuxPay();
        $yunFuXPay->setPrivateKey('test');
        $yunFuXPay->setOptions($options);
        $yunFuXPay->verifyOrderPayment($entry);
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

        $xml = '<xml>' .
            '<bank_type><![CDATA[CFT]]></bank_type>' .
            '<charset><![CDATA[UTF-8]]></charset>' .
            '<fee_type><![CDATA[CNY]]></fee_type>' .
            '<is_subscribe><![CDATA[N]]></is_subscribe>' .
            '<mch_id><![CDATA[2017031920431360256]]></mch_id>' .
            '<nonce_str><![CDATA[1500425002684]]></nonce_str>' .
            '<openid><![CDATA[olE5xwHvAwQ9g2m3JuTdPMwMtzWc]]></openid>' .
            '<out_trade_no><![CDATA[201707190000003432]]></out_trade_no>' .
            '<out_transaction_id><![CDATA[4000222001201707191508151673]]></out_transaction_id>' .
            '<pay_result><![CDATA[0]]></pay_result>' .
            '<result_code><![CDATA[0]]></result_code>' .
            '<sign><![CDATA[4FA48D73D1295E4647986A70E8A9A95B]]></sign>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status><![CDATA[0]]></status>' .
            '<time_end><![CDATA[20170719084322]]></time_end>' .
            '<total_fee><![CDATA[10]]></total_fee>' .
            '<trade_type><![CDATA[pay.weixin.native]]></trade_type>' .
            '<transaction_id><![CDATA[150530046896201707192211024464]]></transaction_id>' .
            '<version><![CDATA[2.0]]></version>' .
            '</xml>';

        $options = ['content' => $xml];

        $entry = [
            'id' => '201707190000003432',
            'amount' => '15.00',
        ];

        $yunFuXPay = new YunFuxPay();
        $yunFuXPay->setPrivateKey('test');
        $yunFuXPay->setOptions($options);
        $yunFuXPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $xml = '<xml>' .
            '<bank_type><![CDATA[CFT]]></bank_type>' .
            '<charset><![CDATA[UTF-8]]></charset>' .
            '<fee_type><![CDATA[CNY]]></fee_type>' .
            '<is_subscribe><![CDATA[N]]></is_subscribe>' .
            '<mch_id><![CDATA[2017031920431360256]]></mch_id>' .
            '<nonce_str><![CDATA[1500425002684]]></nonce_str>' .
            '<openid><![CDATA[olE5xwHvAwQ9g2m3JuTdPMwMtzWc]]></openid>' .
            '<out_trade_no><![CDATA[201707190000003432]]></out_trade_no>' .
            '<out_transaction_id><![CDATA[4000222001201707191508151673]]></out_transaction_id>' .
            '<pay_result><![CDATA[0]]></pay_result>' .
            '<result_code><![CDATA[0]]></result_code>' .
            '<sign><![CDATA[4FA48D73D1295E4647986A70E8A9A95B]]></sign>' .
            '<sign_type><![CDATA[MD5]]></sign_type>' .
            '<status><![CDATA[0]]></status>' .
            '<time_end><![CDATA[20170719084322]]></time_end>' .
            '<total_fee><![CDATA[10]]></total_fee>' .
            '<trade_type><![CDATA[pay.weixin.native]]></trade_type>' .
            '<transaction_id><![CDATA[150530046896201707192211024464]]></transaction_id>' .
            '<version><![CDATA[2.0]]></version>' .
            '</xml>';

        $options = ['content' => $xml];

        $entry = [
            'id' => '201707190000003432',
            'amount' => '0.1',
        ];

        $yunFuXPay = new YunFuxPay();
        $yunFuXPay->setPrivateKey('test');
        $yunFuXPay->setOptions($options);
        $yunFuXPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $yunFuXPay->getMsg());
    }
}
