<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\BaiSheng;
use Buzz\Message\Response;

class BaiShengTest extends DurianTestCase
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

        $baiSheng = new BaiSheng();
        $baiSheng->getVerifyData();
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

        $baiSheng = new BaiSheng();
        $baiSheng->setPrivateKey('test');
        $baiSheng->getVerifyData();
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
            'number' => '201708181614086782',
            'orderId' => '201709180000004711',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
        ];

        $baiSheng = new BaiSheng();
        $baiSheng->setPrivateKey('test');
        $baiSheng->setOptions($options);
        $baiSheng->getVerifyData();
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
            'paymentVendorId' => '1103',
            'number' => '201708181614086782',
            'orderId' => '201709180000004711',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => '',
        ];

        $baiSheng = new BaiSheng();
        $baiSheng->setPrivateKey('test');
        $baiSheng->setOptions($options);
        $baiSheng->getVerifyData();
    }

    /**
     * 測試支付時沒有返回code
     */
    public function testPayReturnWithoutCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1103',
            'number' => '201708181614086782',
            'orderId' => '201709180000004711',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"PaymentNo":"1803041022299563","QrCodeUrl":"https:\/\/qpay.qq.com\/qr\/635dbe0e",' .
            '"Timestamp":"2018-03-07 10:10:10"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $baiSheng = new BaiSheng();
        $baiSheng->setContainer($this->container);
        $baiSheng->setClient($this->client);
        $baiSheng->setResponse($response);
        $baiSheng->setPrivateKey('test');
        $baiSheng->setOptions($options);
        $baiSheng->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '签名验证不通过',
            180130
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1103',
            'number' => '201708181614086782',
            'orderId' => '201709180000004711',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"Code":"403","Message":"\u7b7e\u540d\u9a8c\u8bc1\u4e0d\u901a\u8fc7",' .
            '"Timestamp":"2018-03-07 09:02:01"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $baiSheng = new BaiSheng();
        $baiSheng->setContainer($this->container);
        $baiSheng->setClient($this->client);
        $baiSheng->setResponse($response);
        $baiSheng->setPrivateKey('test');
        $baiSheng->setOptions($options);
        $baiSheng->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗沒有Message
     */
    public function testPayReturnNotSuccessWithoutMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Pay error',
            180130
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1103',
            'number' => '201708181614086782',
            'orderId' => '201709180000004711',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"Code":"403","Timestamp":"2018-03-07 09:02:01"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $baiSheng = new BaiSheng();
        $baiSheng->setContainer($this->container);
        $baiSheng->setClient($this->client);
        $baiSheng->setResponse($response);
        $baiSheng->setPrivateKey('test');
        $baiSheng->setOptions($options);
        $baiSheng->getVerifyData();
    }

    /**
     * 測試支付時沒有返回QrCode
     */
    public function testPayReturnWithoutQrCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1103',
            'number' => '201708181614086782',
            'orderId' => '201709180000004711',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"PaymentNo":"1803041022299563","Code":"200","Timestamp":"2018-03-07 10:10:10"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $baiSheng = new BaiSheng();
        $baiSheng->setContainer($this->container);
        $baiSheng->setClient($this->client);
        $baiSheng->setResponse($response);
        $baiSheng->setPrivateKey('test');
        $baiSheng->setOptions($options);
        $baiSheng->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1103',
            'number' => '201708181614086782',
            'orderId' => '201709180000004711',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '{"PaymentNo":"1803041022299563","QrCodeUrl":"https:\/\/qpay.qq.com\/qr\/635dbe0e",' .
            '"Code":"200","Timestamp":"2018-03-07 10:10:10"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $baiSheng = new BaiSheng();
        $baiSheng->setContainer($this->container);
        $baiSheng->setClient($this->client);
        $baiSheng->setResponse($response);
        $baiSheng->setPrivateKey('test');
        $baiSheng->setOptions($options);
        $data = $baiSheng->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('https://qpay.qq.com/qr/635dbe0e', $baiSheng->getQrcode());
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $options = [
            'notify_url' => 'http://pay.in-action.tw/',
            'paymentVendorId' => '1104',
            'number' => '201708181614086782',
            'orderId' => '201709180000004711',
            'amount' => '1.01',
            'username' => 'php1test',
            'orderCreateDate' => '2017-08-24 11:32:32',
        ];

        $baiSheng = new BaiSheng();
        $baiSheng->setPrivateKey('test');
        $baiSheng->setOptions($options);
        $data = $baiSheng->getVerifyData();

        $this->assertEquals($options['number'], $data['MerchantId']);
        $this->assertEquals('d26a5e371c82a13204424c6e62239026', $data['Sign']);
        $this->assertEquals($options['orderCreateDate'], $data['Timestamp']);
        $this->assertEquals('QQ_WAP_PAY', $data['PaymentTypeCode']);
        $this->assertEquals($options['orderId'], $data['OutPaymentNo']);
        $this->assertEquals(round($options['amount']) * 100, $data['PaymentAmount']);
        $this->assertEquals($options['notify_url'], $data['NotifyUrl']);
        $this->assertEquals($options['username'], $data['PassbackParams']);
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

        $baiSheng = new BaiSheng();
        $baiSheng->verifyOrderPayment([]);
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

        $baiSheng = new BaiSheng();
        $baiSheng->setPrivateKey('test');
        $baiSheng->verifyOrderPayment([]);
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
            'Code' => '200',
            'MerchantId' => '18022614003101',
            'OutPaymentNo' => '201803070000009944',
            'PassbackParams' => 'php1test',
            'PaymentAmount' => '200',
            'PaymentFee' => '3',
            'PaymentNo' => '1803041022299563',
            'PaymentState' => 'S',
        ];

        $baiSheng = new BaiSheng();
        $baiSheng->setPrivateKey('test');
        $baiSheng->setOptions($options);
        $baiSheng->verifyOrderPayment([]);
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
            'Code' => '200',
            'MerchantId' => '18022614003101',
            'OutPaymentNo' => '201803070000009944',
            'PassbackParams' => 'php1test',
            'PaymentAmount' => '200',
            'PaymentFee' => '3',
            'PaymentNo' => '1803041022299563',
            'PaymentState' => 'S',
            'Sign' => '791EDBCD5446497F4DBF7F73E64B4365',
        ];

        $baiSheng = new BaiSheng();
        $baiSheng->setPrivateKey('test');
        $baiSheng->setOptions($options);
        $baiSheng->verifyOrderPayment([]);
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
            'Code' => '200',
            'MerchantId' => '18022614003101',
            'OutPaymentNo' => '201803070000009944',
            'PassbackParams' => 'php1test',
            'PaymentAmount' => '200',
            'PaymentFee' => '3',
            'PaymentNo' => '1803041022299563',
            'PaymentState' => '',
            'Sign' => '7D41F0F6E26AE46F24FC7624329C3062',
        ];

        $baiSheng = new BaiSheng();
        $baiSheng->setPrivateKey('test');
        $baiSheng->setOptions($options);
        $baiSheng->verifyOrderPayment([]);
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
            'Code' => '200',
            'MerchantId' => '18022614003101',
            'OutPaymentNo' => '201803070000009944',
            'PassbackParams' => 'php1test',
            'PaymentAmount' => '200',
            'PaymentFee' => '3',
            'PaymentNo' => '1803041022299563',
            'PaymentState' => 'S',
            'Sign' => 'A155C296D771B98835949AAFB79BEF89',
        ];

        $entry = ['id' => '201503220000000555'];

        $baiSheng = new BaiSheng();
        $baiSheng->setPrivateKey('test');
        $baiSheng->setOptions($options);
        $baiSheng->verifyOrderPayment($entry);
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
            'Code' => '200',
            'MerchantId' => '18022614003101',
            'OutPaymentNo' => '201803070000009944',
            'PassbackParams' => 'php1test',
            'PaymentAmount' => '200',
            'PaymentFee' => '3',
            'PaymentNo' => '1803041022299563',
            'PaymentState' => 'S',
            'Sign' => 'A155C296D771B98835949AAFB79BEF89',
        ];

        $entry = [
            'id' => '201803070000009944',
            'amount' => '15.00',
        ];

        $baiSheng = new BaiSheng();
        $baiSheng->setPrivateKey('test');
        $baiSheng->setOptions($options);
        $baiSheng->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $options = [
            'Code' => '200',
            'MerchantId' => '18022614003101',
            'OutPaymentNo' => '201803070000009944',
            'PassbackParams' => 'php1test',
            'PaymentAmount' => '200',
            'PaymentFee' => '3',
            'PaymentNo' => '1803041022299563',
            'PaymentState' => 'S',
            'Sign' => 'A155C296D771B98835949AAFB79BEF89',
        ];

        $entry = [
            'id' => '201803070000009944',
            'amount' => '2',
        ];

        $baiSheng = new BaiSheng();
        $baiSheng->setPrivateKey('test');
        $baiSheng->setOptions($options);
        $baiSheng->verifyOrderPayment($entry);

        $this->assertEquals('success', $baiSheng->getMsg());
    }
}
