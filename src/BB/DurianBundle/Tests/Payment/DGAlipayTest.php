<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\DGAlipay;
use Buzz\Message\Response;

class DGAlipayTest extends DurianTestCase
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * @var \Buzz\Client\Curl
     */
    private $client;

    /**
     * 提交給支付平台時需要的參數
     *
     * @var array
     */
    private $option;

    /**
     * 支付成功時通知的參數
     *
     * @var array
     */
    private $returnResult;

    public function setUp()
    {
        parent::setUp();

        $mockLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Payment')
            ->disableOriginalConstructor()
            ->setMethods(['record'])
            ->getMock();

        $mockLogger->expects($this->any())
            ->method('record')
            ->willReturn($this->returnValue(null));

        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $this->container->expects($this->any())
            ->method('get')
            ->willReturn($mockLogger);

        $this->client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $this->option = [
            'number' => '65b055f38b28abb579cf3db4',
            'amount' => '1',
            'paymentVendorId' => '1092',
            'notify_url' => 'http://pay.my/pay/reutrn.php',
            'orderId' => '201805210000005152',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $this->returnResult = [
            'key' => 'e52383e1e1ef94c1b094afc35631a4ee',
            'orderid' => '201805210000005152',
            'api_id' => '5124596226b0c6e8d9e0dc56',
            'price' => '1',
            'realprice' => '0.99',
            'orderuid' => '201805210000005152',
            'sign' => 'b85f790cc0a922fd6aae6eecd8c7136f',
        ];
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

        $dGAlipay = new DGAlipay();
        $dGAlipay->getVerifyData();
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

        $dGAlipay = new DGAlipay();
        $dGAlipay->setPrivateKey('test');
        $dGAlipay->getVerifyData();
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

        $this->option['paymentVendorId'] = '999';

        $dGAlipay = new DGAlipay();
        $dGAlipay->setPrivateKey('test');
        $dGAlipay->setOptions($this->option);
        $dGAlipay->getVerifyData();
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

        $this->option['verify_url'] = '';

        $dGAlipay = new DGAlipay();
        $dGAlipay->setPrivateKey('test');
        $dGAlipay->setOptions($this->option);
        $dGAlipay->getVerifyData();
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

        $result = [
            'msg' => 'AmountError',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $dGAlipay = new DGAlipay();
        $dGAlipay->setContainer($this->container);
        $dGAlipay->setClient($this->client);
        $dGAlipay->setResponse($response);
        $dGAlipay->setPrivateKey('test');
        $dGAlipay->setOptions($this->option);
        $dGAlipay->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '订单号重复',
            180130
        );

        $result = [
            'code' => '-3',
            'msg' => '订单号重复',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $dGAlipay = new DGAlipay();
        $dGAlipay->setContainer($this->container);
        $dGAlipay->setClient($this->client);
        $dGAlipay->setResponse($response);
        $dGAlipay->setPrivateKey('test');
        $dGAlipay->setOptions($this->option);
        $dGAlipay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回qrcode
     */
    public function testPayReturnWithoutQrcode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'msg' => '付款即时到账 未到账可联系我们',
            'code' => 1,
            'url' => 'https://tingliu.000webhostapp.com/pay/return.php',
            "data" => [
                'orderid' => '201805210000005152',
                'istype' => 1,
                'realprice' => 0.99,
                'price_istype"' => 2,
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $dGAlipay = new DGAlipay();
        $dGAlipay->setContainer($this->container);
        $dGAlipay->setClient($this->client);
        $dGAlipay->setResponse($response);
        $dGAlipay->setPrivateKey('test');
        $dGAlipay->setOptions($this->option);
        $dGAlipay->getVerifyData();
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $this->option['paymentVendorId'] = '1098';

        $result = [
            'msg' => '付款即时到账 未到账可联系我们',
            'code' => 1,
            'url' => 'https://tingliu.000webhostapp.com/pay/return.php',
            "data" => [
                'orderid' => '201805210000005152',
                'qrcode' => 'HTTPS://QR.ALIPAY.COM/FKX06037YDD0BKYA6BK59F',
                'istype' => 1,
                'realprice' => 0.99,
                'price_istype"' => 2,
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $dGAlipay = new DGAlipay();
        $dGAlipay->setContainer($this->container);
        $dGAlipay->setClient($this->client);
        $dGAlipay->setResponse($response);
        $dGAlipay->setPrivateKey('test');
        $dGAlipay->setOptions($this->option);
        $data = $dGAlipay->getVerifyData();

        $this->assertEquals('HTTPS://QR.ALIPAY.COM/FKX06037YDD0BKYA6BK59F', $data['post_url']);
        $this->assertEmpty($data['params']);
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $result = [
            'msg' => '付款即时到账 未到账可联系我们',
            'code' => 1,
            'url' => 'https://tingliu.000webhostapp.com/pay/return.php',
            "data" => [
                'orderid' => '201805210000005152',
                'qrcode' => 'HTTPS://QR.ALIPAY.COM/FKX06037YDD0BKYA6BK59F',
                'istype' => 1,
                'realprice' => 0.99,
                'price_istype"' => 2,
            ],
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $dGAlipay = new DGAlipay();
        $dGAlipay->setContainer($this->container);
        $dGAlipay->setClient($this->client);
        $dGAlipay->setResponse($response);
        $dGAlipay->setPrivateKey('test');
        $dGAlipay->setOptions($this->option);
        $data = $dGAlipay->getVerifyData();

        $this->assertEmpty($data);
        $this->assertEquals('HTTPS://QR.ALIPAY.COM/FKX06037YDD0BKYA6BK59F', $dGAlipay->getQrcode());
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

        $dGAlipay = new DGAlipay();
        $dGAlipay->verifyOrderPayment([]);
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

        $dGAlipay = new DGAlipay();
        $dGAlipay->setPrivateKey('test');
        $dGAlipay->verifyOrderPayment([]);
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

        unset($this->returnResult['sign']);

        $dGAlipay = new DGAlipay();
        $dGAlipay->setPrivateKey('test');
        $dGAlipay->setOptions($this->returnResult);
        $dGAlipay->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = 'ade0fc9436bd2e6efb48405b3f9ef81a';

        $dGAlipay = new DGAlipay();
        $dGAlipay->setPrivateKey('test');
        $dGAlipay->setOptions($this->returnResult);
        $dGAlipay->verifyOrderPayment([]);
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

        $entry = ['id' => '201805210000005153'];

        $dGAlipay = new DGAlipay();
        $dGAlipay->setPrivateKey('test');
        $dGAlipay->setOptions($this->returnResult);
        $dGAlipay->verifyOrderPayment($entry);
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

        $entry = [
            'id' => '201805210000005152',
            'amount' => '100',
        ];

        $dGAlipay = new DGAlipay();
        $dGAlipay->setPrivateKey('test');
        $dGAlipay->setOptions($this->returnResult);
        $dGAlipay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $entry = [
            'id' => '201805210000005152',
            'amount' => '1',
        ];

        $dGAlipay = new DGAlipay();
        $dGAlipay->setPrivateKey('test');
        $dGAlipay->setOptions($this->returnResult);
        $dGAlipay->verifyOrderPayment($entry);

        $this->assertEquals('success', $dGAlipay->getMsg());
    }
}