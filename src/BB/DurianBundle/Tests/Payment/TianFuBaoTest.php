<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\TianFuBao;
use Buzz\Message\Response;

class TianFuBaoTest extends DurianTestCase
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
     * 測試支付加密時沒有帶入privateKey的情況
     */
    public function testPayWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $tianFuBao = new TianFuBao();
        $tianFuBao->getVerifyData();
    }

    /**
     * 測試支付加密時未指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = ['number' => ''];

        $tianFuBao = new TianFuBao();
        $tianFuBao->setPrivateKey('1234');
        $tianFuBao->setOptions($sourceData);
        $tianFuBao->getVerifyData();
    }

    /**
     * 測試支付加密時代入支付平台不支援的銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '1234',
            'paymentVendorId' => '999',
            'amount' => '0.01',
            'orderId' => '201801220000008639',
            'notify_url' => 'http://two123.comuv.com',
            'username' => 'php1test',
        ];

        $tianFuBao = new TianFuBao();
        $tianFuBao->setPrivateKey('1234');
        $tianFuBao->setOptions($sourceData);
        $tianFuBao->getVerifyData();
    }

    /**
     * 測試支付加密
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '1234',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201801220000008639',
            'notify_url' => 'http://two123.comuv.com',
            'username' => 'php1test',
        ];

        $tianFuBao = new TianFuBao();
        $tianFuBao->setPrivateKey('1234');
        $tianFuBao->setOptions($sourceData);
        $encodeData = $tianFuBao->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['spid']);
        $this->assertEquals($sourceData['orderId'], $encodeData['sp_userid']);
        $this->assertEquals($sourceData['orderId'], $encodeData['spbillno']);
        $this->assertEquals('1', $encodeData['money']);
        $this->assertEquals('1', $encodeData['cur_type']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['return_url']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['notify_url']);
        $this->assertEquals('', $encodeData['errpage_url']);
        $this->assertEquals($sourceData['username'], $encodeData['memo']);
        $this->assertEquals('', $encodeData['expire_time']);
        $this->assertEquals('', $encodeData['attach']);
        $this->assertEquals('1001', $encodeData['bank_segment']);
        $this->assertEquals('1', $encodeData['user_type']);
        $this->assertEquals('1', $encodeData['channel']);
        $this->assertEquals('MD5', $encodeData['encode_type']);
        $this->assertEquals('', $encodeData['risk_ctrl']);
        $this->assertEquals('d4eb0916aea068852de57b75de0356a6', $encodeData['sign']);
    }

    /**
     * 測試返回時基本參數設定沒有帶入key的情況
     */
    public function testReturnWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $tianFuBao = new TianFuBao();
        $tianFuBao->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台未指定返回參數
     */
    public function testReturnWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'mId' => '50009',
            'orderNumber' => '201801090000008482',
        ];

        $tianFuBao = new TianFuBao();
        $tianFuBao->setPrivateKey('1234');
        $tianFuBao->setOptions($sourceData);
        $tianFuBao->verifyOrderPayment([]);
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

        $sourceData = [
            'cur_type' => '1',
            'encode_type' => 'MD5',
            'listid' => '8021800355082180122207773145',
            'money' => '1',
            'pay_type' => '2',
            'result' => '1',
            'spbillno' => '201801220000008639',
            'spid' => '1800355082',
            'user_type' => '1',
            'retcode' => '00',
            'retmsg' => '支付成功',
        ];

        $tianFuBao = new TianFuBao();
        $tianFuBao->setPrivateKey('1234');
        $tianFuBao->setOptions($sourceData);
        $tianFuBao->verifyOrderPayment([]);
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
            'cur_type' => '1',
            'encode_type' => 'MD5',
            'listid' => '8021800355082180122207773145',
            'money' => '1',
            'pay_type' => '2',
            'result' => '1',
            'spbillno' => '201801220000008639',
            'spid' => '1800355082',
            'user_type' => '1',
            'sign' => '004ab3ec1c3590a0578f33d9f177670e',
            'retcode' => '00',
            'retmsg' => '支付成功',
        ];

        $tianFuBao = new TianFuBao();
        $tianFuBao->setPrivateKey('1234');
        $tianFuBao->setOptions($sourceData);
        $tianFuBao->verifyOrderPayment([]);
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
            'cur_type' => '1',
            'encode_type' => 'MD5',
            'listid' => '8021800355082180122207773145',
            'money' => '1',
            'pay_type' => '2',
            'result' => '9',
            'spbillno' => '201801220000008639',
            'spid' => '1800355082',
            'user_type' => '1',
            'sign' => 'bf48ebb36a15d22102a672414d14e0fb',
            'retcode' => '00',
            'retmsg' => '支付失敗',
        ];

        $tianFuBao = new TianFuBao();
        $tianFuBao->setPrivateKey('1234');
        $tianFuBao->setOptions($sourceData);
        $tianFuBao->verifyOrderPayment([]);
    }

    /**
     * 測試返回支付單號不正確的情況
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'cur_type' => '1',
            'encode_type' => 'MD5',
            'listid' => '8021800355082180122207773145',
            'money' => '1',
            'pay_type' => '2',
            'result' => '1',
            'spbillno' => '201801220000008639',
            'spid' => '1800355082',
            'user_type' => '1',
            'sign' => 'f765be12012d1666ba9c0cb6fd9a88ff',
            'retcode' => '00',
            'retmsg' => '支付成功',
        ];

        $entry = ['id' => '201606220000002806'];

        $tianFuBao = new TianFuBao();
        $tianFuBao->setPrivateKey('1234');
        $tianFuBao->setOptions($sourceData);
        $tianFuBao->verifyOrderPayment($entry);
    }

    /**
     * 測試返回支付金額不正確的情況
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'cur_type' => '1',
            'encode_type' => 'MD5',
            'listid' => '8021800355082180122207773145',
            'money' => '1',
            'pay_type' => '2',
            'result' => '1',
            'spbillno' => '201801220000008639',
            'spid' => '1800355082',
            'user_type' => '1',
            'sign' => 'f765be12012d1666ba9c0cb6fd9a88ff',
            'retcode' => '00',
            'retmsg' => '支付成功',
        ];

        $entry = [
            'id' => '201801220000008639',
            'amount' => '1.0000',
        ];

        $tianFuBao = new TianFuBao();
        $tianFuBao->setPrivateKey('1234');
        $tianFuBao->setOptions($sourceData);
        $tianFuBao->verifyOrderPayment($entry);
    }

    /**
     * 測試返回驗證成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'cur_type' => '1',
            'encode_type' => 'MD5',
            'listid' => '8021800355082180122207773145',
            'money' => '1',
            'pay_type' => '2',
            'result' => '1',
            'spbillno' => '201801220000008639',
            'spid' => '1800355082',
            'user_type' => '1',
            'sign' => 'f765be12012d1666ba9c0cb6fd9a88ff',
            'retcode' => '00',
            'retmsg' => '支付成功',
        ];

        $entry = [
            'id' => '201801220000008639',
            'amount' => '0.01',
        ];

        $tianFuBao = new TianFuBao();
        $tianFuBao->setPrivateKey('1234');
        $tianFuBao->setOptions($sourceData);
        $tianFuBao->verifyOrderPayment($entry);

        $this->assertEquals('<retcode>00</retcode>', $tianFuBao->getMsg());
    }

    /**
     * 測試出款沒有帶入privateKey
     */
    public function testWithdrawWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $tianFuBao = new TianFuBao();
        $tianFuBao->withdrawPayment();
    }

    /**
     * 測試出款未指定出款參數
     */
    public function testWithdrawNoWithdrawParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No withdraw parameter specified',
            150180196
        );

        $sourceData = ['account' => ''];

        $tianFuBao = new TianFuBao();
        $tianFuBao->setPrivateKey('12345');
        $tianFuBao->setOptions($sourceData);
        $tianFuBao->withdrawPayment();
    }

    /**
     * 測試出款但返回結果缺少參數
     */
    public function testWithdrawButNoWithdrawReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No withdraw return parameter specified',
            150180209
        );

        $sourceData = [
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '0.01',
            'orderId' => '112332',
            'number' => '10000080001641',
            'orderCreateDate' => '2018-01-10 10:40:05',
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<?xml version="1.0" encoding="UTF-8" ?><root>' .
            '<retmsg>商户签名校验失败</retmsg><tid>api_pay_single</tid></root>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $tianFuBao = new TianFuBao();
        $tianFuBao->setContainer($this->container);
        $tianFuBao->setClient($this->client);
        $tianFuBao->setResponse($response);
        $tianFuBao->setPrivateKey('12345');
        $tianFuBao->setOptions($sourceData);
        $tianFuBao->withdrawPayment();
    }

    /**
     * 測試出款但餘額不足
     */
    public function testWithdrawButInsufficientBalance()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Insufficient balance',
            150180197
        );

        $sourceData = [
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '0.01',
            'orderId' => '112332',
            'number' => '10000080001641',
            'orderCreateDate' => '2018-01-10 10:40:05',
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<?xml version="1.0" encoding="UTF-8" ?><root><retcode>207538</retcode>' .
            '<retmsg>余额不足</retmsg><tid>api_pay_single</tid></root>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $tianFuBao = new TianFuBao();
        $tianFuBao->setContainer($this->container);
        $tianFuBao->setClient($this->client);
        $tianFuBao->setResponse($response);
        $tianFuBao->setPrivateKey('12345');
        $tianFuBao->setOptions($sourceData);
        $tianFuBao->withdrawPayment();
    }

    /**
     * 測試出款返回異常
     */
    public function testWithdrawFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '商户签名校验失败',
            180124
        );

        $sourceData = [
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '0.01',
            'orderId' => '112332',
            'number' => '10000080001641',
            'orderCreateDate' => '2018-01-10 10:40:05',
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<?xml version="1.0" encoding="UTF-8" ?><root><retcode>207040</retcode>' .
            '<retmsg>商户签名校验失败</retmsg><tid>api_pay_single</tid></root>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $tianFuBao = new TianFuBao();
        $tianFuBao->setContainer($this->container);
        $tianFuBao->setClient($this->client);
        $tianFuBao->setResponse($response);
        $tianFuBao->setPrivateKey('12345');
        $tianFuBao->setOptions($sourceData);
        $tianFuBao->withdrawPayment();
    }

    /**
     * 測試出款返回成功
     */
    public function testWithdrawSuccess()
    {
        $sourceData = [
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '0.01',
            'orderId' => '112332',
            'number' => '10000080001641',
            'orderCreateDate' => '2018-01-10 10:40:05',
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<?xml version="1.0" encoding="UTF-8" ?><root><cipher_data>' .
            'pkgug2jRaKnTPixC1W52m5</cipher_data><retcode>00</retcode><retmsg>操作成功</retmsg></root>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $tianFuBao = new TianFuBao();
        $tianFuBao->setContainer($this->container);
        $tianFuBao->setClient($this->client);
        $tianFuBao->setResponse($response);
        $tianFuBao->setPrivateKey('12345');
        $tianFuBao->setOptions($sourceData);
        $tianFuBao->withdrawPayment();
    }
}
