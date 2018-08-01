<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ShanRuBao;
use Buzz\Message\Response;

class ShanRuBaoTest extends DurianTestCase
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
            ->willReturn(null);

        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $this->container->expects($this->any())
            ->method('get')
            ->willReturn($mockLogger);

        $this->client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $this->option = [
            'paymentVendorId' => '1090',
            'number' => '852055955130320409',
            'notify_url' => 'http://pay.my/pay/reutrn.php',
            'orderId' => '201805250000005245',
            'amount' => '10',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $this->returnResult = [
            'paysapi_id' => '20180525150933530445',
            'orderid' => '201805250000005245',
            'price' => '10',
            'realprice' => '9.83',
            'orderuid' => '201805250000005245',
            'key' => '9e3a60f324d3826a7284bcad3e130309',
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

        $shanRuBao = new ShanRuBao();
        $shanRuBao->getVerifyData();
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

        $shanRuBao = new ShanRuBao();
        $shanRuBao->setPrivateKey('test');
        $shanRuBao->getVerifyData();
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

        $shanRuBao = new ShanRuBao();
        $shanRuBao->setPrivateKey('test');
        $shanRuBao->setOptions($this->option);
        $shanRuBao->getVerifyData();
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

        $shanRuBao = new ShanRuBao();
        $shanRuBao->setPrivateKey('test');
        $shanRuBao->setOptions($this->option);
        $shanRuBao->getVerifyData();
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
            'msg' => '付款即时到账 未到账可联系我们',
            'data' => [
                [
                    'qrcode' => 'https://pan.baidu.com/share/qrcode?w=210&h=210&url=wxp://s7vrLb5C',
                    'istype' => '2',
                    'realprice' => '9.83',
                ],
            ],
            'url' => 'https://tingliu.000webhostapp.com/pay/return.php',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $shanRuBao = new ShanRuBao();
        $shanRuBao->setContainer($this->container);
        $shanRuBao->setClient($this->client);
        $shanRuBao->setResponse($response);
        $shanRuBao->setPrivateKey('test');
        $shanRuBao->setOptions($this->option);
        $shanRuBao->getVerifyData();
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

        $result = [
            'data' => [
                [
                    'qrcode' => 'https://pan.baidu.com/share/qrcode?w=210&h=210&url=wxp://s7vrLb5C',
                    'istype' => '2',
                    'realprice' => '9.83',
                ],
            ],
            'code' => 1,
            'url' => 'https://tingliu.000webhostapp.com/pay/return.php',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $shanRuBao = new ShanRuBao();
        $shanRuBao->setContainer($this->container);
        $shanRuBao->setClient($this->client);
        $shanRuBao->setResponse($response);
        $shanRuBao->setPrivateKey('test');
        $shanRuBao->setOptions($this->option);
        $shanRuBao->getVerifyData();
    }

    /**
     * 測試支付時返回提交失敗
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '金额错误',
            180130
        );

        $result = [
            'msg' => '金额错误',
            'data' => [
                [
                    'qrcode' => 'https://pan.baidu.com/share/qrcode?w=210&h=210&url=wxp://s7vrLb5C',
                    'istype' => '2',
                    'realprice' => '9.83',
                ],
            ],
            'code' => -1,
            'url' => 'https://tingliu.000webhostapp.com/pay/return.php',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $shanRuBao = new ShanRuBao();
        $shanRuBao->setContainer($this->container);
        $shanRuBao->setClient($this->client);
        $shanRuBao->setResponse($response);
        $shanRuBao->setPrivateKey('test');
        $shanRuBao->setOptions($this->option);
        $shanRuBao->getVerifyData();
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
            'data' => [
                [
                    'istype' => '2',
                    'realprice' => '9.83',
                ],
            ],
            'code' => 1,
            'url' => 'https://tingliu.000webhostapp.com/pay/return.php',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $shanRuBao = new ShanRuBao();
        $shanRuBao->setContainer($this->container);
        $shanRuBao->setClient($this->client);
        $shanRuBao->setResponse($response);
        $shanRuBao->setPrivateKey('test');
        $shanRuBao->setOptions($this->option);
        $shanRuBao->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $result = [
            'msg' => '付款即时到账 未到账可联系我们',
            'data' => [
                [
                    'qrcode' => 'https://pan.baidu.com/share/qrcode?w=210&h=210&url=wxp://s7vrLb5C',
                    'istype' => '2',
                    'realprice' => '9.83',
                ],
            ],
            'code' => 1,
            'url' => 'https://tingliu.000webhostapp.com/pay/return.php',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $shanRuBao = new ShanRuBao();
        $shanRuBao->setContainer($this->container);
        $shanRuBao->setClient($this->client);
        $shanRuBao->setResponse($response);
        $shanRuBao->setPrivateKey('test');
        $shanRuBao->setOptions($this->option);
        $data = $shanRuBao->getVerifyData();

        $this->assertEquals('https://pan.baidu.com/share/qrcode', $data['post_url']);
        $this->assertEquals('210', $data['params']['w']);
        $this->assertEquals('210', $data['params']['h']);
        $this->assertEquals('wxp://s7vrLb5C', $data['params']['url']);
        $this->assertEquals('GET', $shanRuBao->getPayMethod());
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

        $shanRuBao = new ShanRuBao();
        $shanRuBao->verifyOrderPayment([]);
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

        $shanRuBao = new ShanRuBao();
        $shanRuBao->setPrivateKey('test');
        $shanRuBao->verifyOrderPayment([]);
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

        unset($this->returnResult['key']);

        $shanRuBao = new ShanRuBao();
        $shanRuBao->setPrivateKey('test');
        $shanRuBao->setOptions($this->returnResult);
        $shanRuBao->verifyOrderPayment([]);
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

        $this->returnResult['key'] = 'b51244402ede1225b871ec91a97feb9b';

        $shanRuBao = new ShanRuBao();
        $shanRuBao->setPrivateKey('test');
        $shanRuBao->setOptions($this->returnResult);
        $shanRuBao->verifyOrderPayment([]);
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

        $entry = ['id' => '201805250000005246'];

        $shanRuBao = new ShanRuBao();
        $shanRuBao->setPrivateKey('test');
        $shanRuBao->setOptions($this->returnResult);
        $shanRuBao->verifyOrderPayment($entry);
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
            'id' => '201805250000005245',
            'amount' => '100',
        ];

        $shanRuBao = new ShanRuBao();
        $shanRuBao->setPrivateKey('test');
        $shanRuBao->setOptions($this->returnResult);
        $shanRuBao->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $entry = [
            'id' => '201805250000005245',
            'amount' => '10',
        ];

        $shanRuBao = new ShanRuBao();
        $shanRuBao->setPrivateKey('test');
        $shanRuBao->setOptions($this->returnResult);
        $shanRuBao->verifyOrderPayment($entry);

        $this->assertEquals('success', $shanRuBao->getMsg());
    }
}