<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\YingYu;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class YingYuTest extends DurianTestCase
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
     * 支付時的參數
     *
     * @var array
     */
    private $sourceData;

    /**
     * 對外返回時的參數
     *
     * @var array
     */
    private $verifyResult;

    /**
     * 返回時的參數
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

        $this->sourceData = [
            'orderId' => '201806200000011853',
            'number' => '171100020224',
            'amount' => '1',
            'notify_url' => 'http://return.php',
            'paymentVendorId' => '1103',
            'verify_url' => 'payment.https.api.yingyupay.com',
            'verify_ip' => ['172.26.54.41', '172.26.54.42'],
        ];

        $this->verifyResult = [
            'sign' => '9FA0DD7EB26F95E87410C0EBA8616900',
            'orderNo' => 'p2018062011393217631181',
            'resCode' => '00',
            'resultCode' => '00',
            'nonceStr' => '5SevoJmj8tcR5iWprAA72EZN0TYMKzvd',
            'paySeq' => '10012018062011393129332402',
            'resultDesc' => '成功',
            'payUrl' => 'https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027&_bid=2183&t=5V5d92adbc88839daa1fc09034104087',
            'resDesc' => '成功',
        ];

        $this->returnResult = [
            'createTime' => '20180620113931',
            'status' => '02',
            'nonceStr' => 'TkG3yMzLXqePpVnjO1l1brA6lm68TgQX',
            'resultDesc' => '成功',
            'outTradeNo' => '201806200000011853',
            'sign' => 'a25ba4d03950d7a42e1061e7e8fd7b6a',
            'productDesc' => '201806200000011853',
            'orderNo' => 'p2018062011393217631181',
            'branchId' => '171100020224',
            'resultCode' => '00',
            'resCode' => '00',
            'payType' => '50',
            'resDesc' => '成功',
            'orderAmt' => 100,
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
            '180142'
        );

        $yingYu = new YingYu();
        $yingYu->getVerifyData();
    }

    /**
     * 測試支付時未指定支付參數
     */
    public function testPayWithoutPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $yingYu = new YingYu();
        $yingYu->setPrivateKey('test');
        $yingYu->setOptions([]);
        $yingYu->getVerifyData();
    }

    /**
     * 測試支付時帶入支付平台不支援的銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $this->sourceData['paymentVendorId'] = '66666';

        $yingYu = new YingYu();
        $yingYu->setPrivateKey('test');
        $yingYu->setOptions($this->sourceData);
        $yingYu->getVerifyData();
    }

    /**
     * 測試支付時缺少verify_url
     */
    public function testPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $this->sourceData['verify_url'] = '';

        $yingYu = new YingYu();
        $yingYu->setPrivateKey('test');
        $yingYu->setOptions($this->sourceData);
        $yingYu->getVerifyData();
    }

    /**
     * 測試支付時未返回resultCode
     */
    public function testPayNoReturnResultCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        unset($this->verifyResult['resultCode']);

        $response = new Response();
        $response->setContent(json_encode($this->verifyResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $yingYu = new YingYu();
        $yingYu->setContainer($this->container);
        $yingYu->setClient($this->client);
        $yingYu->setResponse($response);
        $yingYu->setPrivateKey('test');
        $yingYu->setOptions($this->sourceData);
        $yingYu->getVerifyData();
    }

    /**
     * 測試支付時返回resultCode不等於00
     */
    public function testPayReturnResultCodeNotEqualZeroZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '支付平台連線異常',
            180130
        );

        $this->verifyResult['resultCode'] = '99';
        $this->verifyResult['resultDesc'] = '支付平台連線異常';

        $response = new Response();
        $response->setContent(json_encode($this->verifyResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $yingYu = new YingYu();
        $yingYu->setContainer($this->container);
        $yingYu->setClient($this->client);
        $yingYu->setResponse($response);
        $yingYu->setPrivateKey('test');
        $yingYu->setOptions($this->sourceData);
        $yingYu->getVerifyData();
    }


    /**
     * 測試支付時未返回resCode
     */
    public function testPayNoReturnResCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        unset($this->verifyResult['resCode']);

        $response = new Response();
        $response->setContent(json_encode($this->verifyResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $yingYu = new YingYu();
        $yingYu->setContainer($this->container);
        $yingYu->setClient($this->client);
        $yingYu->setResponse($response);
        $yingYu->setPrivateKey('test');
        $yingYu->setOptions($this->sourceData);
        $yingYu->getVerifyData();
    }

    /**
     * 測試支付時返回resCode不等於00
     */
    public function testPayReturnResCodeNotEqualZeroZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'BA该渠道正在维护，暂停使用[银行通道维护，请稍后重试！',
            180130
        );

        $this->verifyResult['resCode'] = '99';
        $this->verifyResult['resDesc'] = 'BA该渠道正在维护，暂停使用[银行通道维护，请稍后重试！';

        $response = new Response();
        $response->setContent(json_encode($this->verifyResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $yingYu = new YingYu();
        $yingYu->setContainer($this->container);
        $yingYu->setClient($this->client);
        $yingYu->setResponse($response);
        $yingYu->setPrivateKey('test');
        $yingYu->setOptions($this->sourceData);
        $yingYu->getVerifyData();
    }

    /**
     * 測試支付時未返回payUrl
     */
    public function testPayNoReturnPayUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        unset($this->verifyResult['payUrl']);

        $response = new Response();
        $response->setContent(json_encode($this->verifyResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $yingYu = new YingYu();
        $yingYu->setContainer($this->container);
        $yingYu->setClient($this->client);
        $yingYu->setResponse($response);
        $yingYu->setPrivateKey('test');
        $yingYu->setOptions($this->sourceData);
        $yingYu->getVerifyData();
    }

    /**
     * 測試網銀支付
     */
    public function testOnlinePay()
    {
        $response = new Response();
        $response->setContent(json_encode($this->verifyResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $this->sourceData['paymentVendorId'] = '1';

        $yingYu = new YingYu();
        $yingYu->setContainer($this->container);
        $yingYu->setClient($this->client);
        $yingYu->setResponse($response);
        $yingYu->setPrivateKey('test');
        $yingYu->setOptions($this->sourceData);
        $yingYu->getVerifyData();
        $data = $yingYu->getVerifyData();

        $this->assertEquals('https://myun.tenpay.com/mqq/pay/qrcode.html', $data['post_url']);
        $this->assertEquals('1027', $data['params']['_wv']);
        $this->assertEquals('2183', $data['params']['_bid']);
        $this->assertEquals('5V5d92adbc88839daa1fc09034104087', $data['params']['t']);
        $this->assertEquals('GET', $yingYu->getPayMethod());
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $response = new Response();
        $response->setContent(json_encode($this->verifyResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $yingYu = new YingYu();
        $yingYu->setContainer($this->container);
        $yingYu->setClient($this->client);
        $yingYu->setResponse($response);
        $yingYu->setPrivateKey('test');
        $yingYu->setOptions($this->sourceData);
        $yingYu->getVerifyData();
        $data = $yingYu->getVerifyData();

        $url = 'https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027&_bid=2183&t=5V5d92adbc88839daa1fc09034104087';

        $this->assertEmpty($data);
        $this->assertEquals($url, $yingYu->getQrcode());
    }

    /**
     * 測試手機支付
     */
    public function testPayWithWap()
    {
        $response = new Response();
        $response->setContent(json_encode($this->verifyResult));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json;charset=UTF-8');

        $this->sourceData['paymentVendorId'] = '1098';

        $yingYu = new YingYu();
        $yingYu->setContainer($this->container);
        $yingYu->setClient($this->client);
        $yingYu->setResponse($response);
        $yingYu->setPrivateKey('test');
        $yingYu->setOptions($this->sourceData);
        $yingYu->getVerifyData();
        $data = $yingYu->getVerifyData();

        $this->assertEquals('https://myun.tenpay.com/mqq/pay/qrcode.html', $data['post_url']);
        $this->assertEquals('1027', $data['params']['_wv']);
        $this->assertEquals('2183', $data['params']['_bid']);
        $this->assertEquals('5V5d92adbc88839daa1fc09034104087', $data['params']['t']);
        $this->assertEquals('GET', $yingYu->getPayMethod());
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

        $yingYu = new YingYu();
        $yingYu->verifyOrderPayment([]);
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

        $yingYu = new YingYu();
        $yingYu->setPrivateKey('test');
        $yingYu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳sign(加密簽名)
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['sign']);

        $yingYu = new YingYu();
        $yingYu->setPrivateKey('test');
        $yingYu->setOptions($this->returnResult);
        $yingYu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時加密簽名驗證錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $this->returnResult['sign'] = 'This sign will verfiy fail';

        $yingYu = new YingYu();
        $yingYu->setPrivateKey('test');
        $yingYu->setOptions($this->returnResult);
        $yingYu->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為resultCode不等於00
     */
    public function testReturnResultCodeNotEqualZreoZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '支付平台連線錯誤',
            180130
        );

        $this->returnResult['resultCode'] = '99';
        $this->returnResult['resultDesc'] = '支付平台連線錯誤';
        $this->returnResult['sign'] = '0b018a99ff2d849a7d19c1ebbc071b8c';

        $yingYu = new YingYu();
        $yingYu->setPrivateKey('test');
        $yingYu->setOptions($this->returnResult);
        $yingYu->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為resCode不等於00
     */
    public function testReturnResCodeNotEqualZreoZero()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '交易失敗',
            180130
        );

        $this->returnResult['resCode'] = 'gg';
        $this->returnResult['resDesc'] = '交易失敗';
        $this->returnResult['sign'] = '47ba6050f8b461ab4b1eea3c18afbe81';

        $yingYu = new YingYu();
        $yingYu->setPrivateKey('test');
        $yingYu->setOptions($this->returnResult);
        $yingYu->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單未支付
     */
    public function testReturnUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $this->returnResult['status'] = '00';
        $this->returnResult['sign'] = 'dd40903630d014901a79a52fa6dffa44';

        $yingYu = new YingYu();
        $yingYu->setPrivateKey('test');
        $yingYu->setOptions($this->returnResult);
        $yingYu->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單支付中
     */
    public function testReturnOrderProcessing()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order Processing',
            180059
        );

        $this->returnResult['status'] = '01';
        $this->returnResult['sign'] = 'e274aa46e57fda04d9a9c784e7bbbd66';

        $yingYu = new YingYu();
        $yingYu->setPrivateKey('test');
        $yingYu->setOptions($this->returnResult);
        $yingYu->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為支付失敗
     */
    public function testReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $this->returnResult['status'] = '03';
        $this->returnResult['sign'] = '30d5c25c775ca7f48eac3616920222a6';

        $yingYu = new YingYu();
        $yingYu->setPrivateKey('test');
        $yingYu->setOptions($this->returnResult);
        $yingYu->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單編號錯誤
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $entry = ['id' => '201704100000002210'];

        $yingYu = new YingYu();
        $yingYu->setPrivateKey('test');
        $yingYu->setOptions($this->returnResult);
        $yingYu->verifyOrderPayment($entry);
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
            'id' => '201806200000011853',
            'amount' => '20',
        ];

        $yingYu = new YingYu();
        $yingYu->setPrivateKey('test');
        $yingYu->setOptions($this->returnResult);
        $yingYu->verifyOrderPayment($entry);
    }

    /**
     * 測試返回成功
     */
    public function testReturnSuccess()
    {
        $entry = [
            'id' => '201806200000011853',
            'amount' => '1',
        ];

        $yingYu = new YingYu();
        $yingYu->setPrivateKey('test');
        $yingYu->setOptions($this->returnResult);
        $yingYu->verifyOrderPayment($entry);

        $this->assertEquals('{"resCode":"00","resDesc":"SUCCESS"}', $yingYu->getMsg());
    }
}
