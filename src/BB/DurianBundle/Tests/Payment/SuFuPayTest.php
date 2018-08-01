<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\SuFuPay;
use Buzz\Message\Response;

class SuFuPayTest extends DurianTestCase
{
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

        $this->option = [
            'number' => '1608412',
            'orderId' => '201805080000012821',
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'amount' => '1',
            'paymentVendorId' => '1',
            'ip' => '111.235.135.54',
            'orderCreateDate' => '2018-05-08 11:45:55',
        ];

        $this->returnResult = [
            'order_no' => '201805080000012821',
            'notify_type' => 'back_notify',
            'merchant_code' => '5473451',
            'trade_time' => '2018-05-10 18:13:48',
            'order_amount' => '50.00',
            'trade_status' => 'success',
            'sign' => '03d803c8a56a76cb49990ab011833fd4',
            'trade_no' => '884510510321963',
            'order_time' => '2018-05-10 18:13:48',
        ];

        $this->sourceData = [
            'account' => '123456789',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '0.01',
            'orderId' => '112332',
            'number' => '10000080001641',
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'ip' => '111.235.135.54',
            'orderCreateDate' => '2018-05-08 11:45:55',
        ];


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
     * 測試支付時沒有私鑰
     */
    public function testPayWithoutKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $suFuPay = new SuFuPay();
        $suFuPay->getVerifyData();
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

        $suFuPay = new SuFuPay();
        $suFuPay->setPrivateKey('test');
        $suFuPay->setOptions([]);
        $suFuPay->getVerifyData();
    }

    /**
     * 測試支付時帶入不支援的銀行
     */
    public function testPayWithUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $this->option['paymentVendorId'] = '9999';

        $suFuPay = new SuFuPay();
        $suFuPay->setPrivateKey('test');
        $suFuPay->setOptions($this->option);
        $suFuPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $suFuPay = new SuFuPay();
        $suFuPay->setPrivateKey('2xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
        $suFuPay->setOptions($this->option);
        $encodeData = $suFuPay->getVerifyData();

        $this->assertEquals('http://fufutest.000webhostapp.com/pay/', $encodeData['notify_url']);
        $this->assertEquals('', $encodeData['return_url']);
        $this->assertEquals('1', $encodeData['pay_type']);
        $this->assertEquals('ICBC', $encodeData['bank_code']);
        $this->assertEquals('1608412', $encodeData['merchant_code']);
        $this->assertEquals('201805080000012821', $encodeData['order_no']);
        $this->assertEquals('1.00', $encodeData['order_amount']);
        $this->assertEquals('2018-05-08 11:45:55', $encodeData['order_time']);
        $this->assertEquals('http://fufutest.000webhostapp.com/pay/', $encodeData['req_referer']);
        $this->assertEquals('111.235.135.54', $encodeData['customer_ip']);
        $this->assertEquals('', $encodeData['return_params']);
        $this->assertEquals('c81d9c684b92d85cd1dbc7983772b728', $encodeData['sign']);
    }

    /**
     * 測試二維支付
     */
    public function testScanPay()
    {
        $this->option['paymentVendorId'] = '1103';

        $suFuPay = new SuFuPay();
        $suFuPay->setPrivateKey('2xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
        $suFuPay->setOptions($this->option);
        $encodeData = $suFuPay->getVerifyData();

        $this->assertEquals('http://fufutest.000webhostapp.com/pay/', $encodeData['notify_url']);
        $this->assertEquals('', $encodeData['return_url']);
        $this->assertEquals('5', $encodeData['pay_type']);
        $this->assertArrayNotHasKey('bank_code', $encodeData);
        $this->assertEquals('1608412', $encodeData['merchant_code']);
        $this->assertEquals('201805080000012821', $encodeData['order_no']);
        $this->assertEquals('1.00', $encodeData['order_amount']);
        $this->assertEquals('2018-05-08 11:45:55', $encodeData['order_time']);
        $this->assertEquals('http://fufutest.000webhostapp.com/pay/', $encodeData['req_referer']);
        $this->assertEquals('111.235.135.54', $encodeData['customer_ip']);
        $this->assertEquals('', $encodeData['return_params']);
        $this->assertEquals('b9c18b2ccfd168e711fb272a5cab16e7', $encodeData['sign']);
    }

    /**
     * 測試返回時未指定參數
     */
    public function testReturnWithNoParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $suFuPay = new SuFuPay();
        $suFuPay->setPrivateKey('test');
        $suFuPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少加密簽名
     */
    public function testReturnWithoutSignature()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['sign']);

        $suFuPay = new SuFuPay();
        $suFuPay->setPrivateKey('test');
        $suFuPay->setOptions($this->returnResult);
        $suFuPay->verifyOrderPayment([]);
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

        $suFuPay = new SuFuPay();
        $suFuPay->setPrivateKey('test');
        $suFuPay->setOptions($this->returnResult);
        $suFuPay->verifyOrderPayment([]);
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

        $this->returnResult['trade_status'] = 'failed';
        $this->returnResult['sign'] = '0cf8375690c1a3b98f759a9d11b653fd';

        $suFuPay = new SuFuPay();
        $suFuPay->setPrivateKey('test');
        $suFuPay->setOptions($this->returnResult);
        $suFuPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時單號不正確
     */
    public function testReturnPaymentOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $entry = ['id' => '201802210000000000'];

        $suFuPay = new SuFuPay();
        $suFuPay->setPrivateKey('2xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
        $suFuPay->setOptions($this->returnResult);
        $suFuPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時金額不正確
     */
    public function testReturnPaymentOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $entry = [
            'id' => '201805080000012821',
            'amount' => '999',
        ];

        $suFuPay = new SuFuPay();
        $suFuPay->setPrivateKey('2xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
        $suFuPay->setOptions($this->returnResult);
        $suFuPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時支付成功
     */
    public function testReturnResultSuccess()
    {
        $entry = [
            'id' => '201805080000012821',
            'amount' => '50.00',
        ];

        $suFuPay = new SuFuPay();
        $suFuPay->setPrivateKey('2xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
        $suFuPay->setOptions($this->returnResult);
        $suFuPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $suFuPay->getMsg());
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

        $suFuPay = new SuFuPay();
        $suFuPay->withdrawPayment();
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

        $suFuPay = new SuFuPay();
        $suFuPay->setPrivateKey('jy9CV6uguTE=');

        $suFuPay->setOptions($sourceData);
        $suFuPay->withdrawPayment();
    }

    /**
     * 測試出款帶入未支援的出款銀行
     */
    public function testWithdrawBankInfoNotSupported()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'BankInfo is not supported by PaymentGateway',
            150180195
        );

        $this->sourceData['bank_info_id'] = '999';

        $suFuPay = new SuFuPay();
        $suFuPay->setPrivateKey('12345');
        $suFuPay->setOptions($this->sourceData);
        $suFuPay->withdrawPayment();
    }

    /**
     * 測試出款沒有帶入Withdraw_host
     */
    public function testWithdrawWithoutWithdrawHost()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No withdraw_host specified',
            150180194
        );

        $this->sourceData['withdraw_host'] = '';

        $suFuPay = new SuFuPay();
        $suFuPay->setPrivateKey('12345');
        $suFuPay->setOptions($this->sourceData);
        $suFuPay->withdrawPayment();
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

        $result = '{"transid":"100000006"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $suFuPay = new SuFuPay();
        $suFuPay->setContainer($this->container);
        $suFuPay->setClient($this->client);
        $suFuPay->setResponse($response);
        $suFuPay->setPrivateKey('12345');
        $suFuPay->setOptions($this->sourceData);
        $suFuPay->withdrawPayment();
    }

    /**
     * 測試出款返回異常
     */
    public function testWithdrawFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Withdraw error',
            180124
        );

        $result = '{"transid":"100000006","bank_status":"0"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $suFuPay = new SuFuPay();
        $suFuPay->setContainer($this->container);
        $suFuPay->setClient($this->client);
        $suFuPay->setResponse($response);
        $suFuPay->setPrivateKey('12345');
        $suFuPay->setOptions($this->sourceData);
        $suFuPay->withdrawPayment();
    }

    /**
     * 測試出款返回成功
     */
    public function testWithdrawSuccess()
    {
        $result = '{"transid":"100000006","bank_status":"1","sign":"36679704253a71e95d051868e6726838",' .
            '"is_success":"true","order_id":"664120515553208","errror_msg":""}';

        $mockCwe = $this->getMockBuilder('BB\DurianBundle\Entity\CashWithdrawEntry')
            ->disableOriginalConstructor()
            ->setMethods(['setRefId'])
            ->getMock();
        $mockCwe->expects($this->any())
            ->method('setRefId')
            ->willReturn($mockCwe);

        $mockRepo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $mockRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn($mockCwe);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository', 'flush'])
            ->getMock();
        $mockEm->expects($this->at(0))
            ->method('getRepository')
            ->willReturn($mockRepo);

        $mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();
        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $mockLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Payment')
            ->disableOriginalConstructor()
            ->setMethods(['record'])
            ->getMock();

        $getMap = [
            ['durian.payment_logger', 1, $mockLogger],
            ['doctrine', 1, $mockDoctrine],
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $suFuPay = new SuFuPay();
        $suFuPay->setContainer($mockContainer);
        $suFuPay->setClient($this->client);
        $suFuPay->setResponse($response);
        $suFuPay->setPrivateKey('12345');
        $suFuPay->setOptions($this->sourceData);
        $suFuPay->withdrawPayment();
    }
}
