<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\CGPay;
use Buzz\Message\Response;

class CGPayTest extends DurianTestCase
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
     * 訂單參數
     *
     * @var array
     */
    private $options;

    /**
     * 返回結果
     *
     * @var array
     */
    private $returnResult;

    /**
     * 出款參數
     *
     * @var array
     */
    private $withdrawOptions;

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

        $this->options = [
            'number' => '0dc21e2ba3aa2f3c8282d7c484e5f1ce',
            'amount' => '1',
            'orderId' => '201804160000011161',
            'paymentVendorId' => '1117',
            'notify_url' => 'http://www.seafood.help/',
            'verify_url' => 'payment.http.public.meowpay.io',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'orderCreateDate' => '2018-04-17 15:45:55',
            'ip' => '192.168.1.1',
            'userId' => '1234',
        ];

        $this->returnResult = [
            'MerchantId' => '0dc21e2ba3aa2f3c8282d7c484e5f1ce',
            'Sign' => '205DE27EEF4EA51EC9957FAE0C08999F',
            'OrderId' => '76a9fb77d8ce72ed486446df2350ad33',
            'MerchantOrderId' => '201804160000011161',
            'Attach' => '201804160000011161',
            'Symbol' => 'CGP',
            'PayAmount' => '10000000',
            'PayTimeSpan' => '1523911268',
            'EventId' => '',
        ];

        $this->withdrawOptions = [
            'number' => '0dc21e2ba3aa2f3c8282d7c484e5f1ce',
            'orderId' => '10000000000009',
            'account' => '0x9d3016517d294a06a2193e8cae2e108dt56f4j3D',
            'amount' => '1',
            'bank_info_id' => '429',
            'withdraw_host' => 'payment.http.public.meowpay.io',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];
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

        $cGPay = new CGPay();
        $cGPay->getVerifyData();
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

        $cGPay = new CGPay();
        $cGPay->setPrivateKey('test');
        $cGPay->getVerifyData();
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

        $this->options['paymentVendorId'] = '9999';

        $cGPay = new CGPay();
        $cGPay->setPrivateKey('1234');
        $cGPay->setOptions($this->options);
        $cGPay->getVerifyData();
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

        $this->options['verify_url'] = '';

        $cGPay = new CGPay();
        $cGPay->setPrivateKey('test');
        $cGPay->setOptions($this->options);
        $cGPay->getVerifyData();
    }

    /**
     * 測試支付沒有返回ReturnCode
     */
    public function testPayReturnWithoutReturnCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $cGPay = new CGPay();
        $cGPay->setContainer($this->container);
        $cGPay->setClient($this->client);
        $cGPay->setResponse($response);
        $cGPay->setPrivateKey('test');
        $cGPay->setOptions($this->options);
        $cGPay->getVerifyData();
    }

    /**
     * 測試支付沒有返回RetrunMessage
     */
    public function testPayReturnWithoutRetrunMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = ['ReturnCode' => '400'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $cGPay = new CGPay();
        $cGPay->setContainer($this->container);
        $cGPay->setClient($this->client);
        $cGPay->setResponse($response);
        $cGPay->setPrivateKey('test');
        $cGPay->setOptions($this->options);
        $cGPay->getVerifyData();
    }

    /**
     * 測試支付時返回錯誤訊息
     */
    public function testPayReturnNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Error MerchantId',
            180130
        );

        $result = [
            'OrderId' => 'null',
            'Qrcode' => 'null',
            'ReturnCode' => '400',
            'RetrunMessage' => 'Error MerchantId',
            'Sign' => 'null',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $cGPay = new CGPay();
        $cGPay->setContainer($this->container);
        $cGPay->setClient($this->client);
        $cGPay->setResponse($response);
        $cGPay->setPrivateKey('test');
        $cGPay->setOptions($this->options);
        $cGPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回Qrcode
     */
    public function testPayReturnWithoutQrcode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'OrderId' => 'c04a56c97d5ca17d80ce54ea15059a5f',
            'ReturnCode' => '0',
            'RetrunMessage' => '成功',
            'Sign' => 'A5D4E299CA8A5AD4C65069ADAF30E2AF',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $cGPay = new CGPay();
        $cGPay->setContainer($this->container);
        $cGPay->setClient($this->client);
        $cGPay->setResponse($response);
        $cGPay->setPrivateKey('test');
        $cGPay->setOptions($this->options);
        $cGPay->getVerifyData();
    }

    /**
     * 測試支付時沒有返回Sign
     */
    public function testPayReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $result = [
            'OrderId' => 'c04a56c97d5ca17d80ce54ea15059a5f',
            'Qrcode' => 'http://public.meowpay.io/qr/30F971E570B1ADBD212CD767B2203E1F.jpg',
            'ReturnCode' => '0',
            'RetrunMessage' => '成功',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $cGPay = new CGPay();
        $cGPay->setContainer($this->container);
        $cGPay->setClient($this->client);
        $cGPay->setResponse($response);
        $cGPay->setPrivateKey('test');
        $cGPay->setOptions($this->options);
        $cGPay->getVerifyData();
    }

    /**
     * 測試支付時沒有驗證簽名錯誤
     */
    public function testPayReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $result = [
            'OrderId' => 'c04a56c97d5ca17d80ce54ea15059a5f',
            'Qrcode' => 'http://public.meowpay.io/qr/30F971E570B1ADBD212CD767B2203E1F.jpg',
            'ReturnCode' => '0',
            'RetrunMessage' => '成功',
            'Sign' => 'test',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $cGPay = new CGPay();
        $cGPay->setContainer($this->container);
        $cGPay->setClient($this->client);
        $cGPay->setResponse($response);
        $cGPay->setPrivateKey('test');
        $cGPay->setOptions($this->options);
        $cGPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $result = [
            'OrderId' => 'c04a56c97d5ca17d80ce54ea15059a5f',
            'Qrcode' => 'http://public.meowpay.io/qr/30F971E570B1ADBD212CD767B2203E1F.jpg',
            'ReturnCode' => '0',
            'RetrunMessage' => '成功',
            'Sign' => '034653D3C26A29CA3EB74D6AF97EA2EF',
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $cGPay = new CGPay();
        $cGPay->setContainer($this->container);
        $cGPay->setClient($this->client);
        $cGPay->setResponse($response);
        $cGPay->setPrivateKey('test');
        $cGPay->setOptions($this->options);
        $encodeData = $cGPay->getVerifyData();

        $this->assertEmpty($encodeData['params']);
        $this->assertEquals('http://public.meowpay.io/qr/30F971E570B1ADBD212CD767B2203E1F.jpg', $encodeData['post_url']);
        $this->assertEquals('GET', $cGPay->getPayMethod());
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

        $cGPay = new CGPay();
        $cGPay->verifyOrderPayment([]);
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

        $cGPay = new CGPay();
        $cGPay->setPrivateKey('1234');
        $cGPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少加密簽名
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['Sign']);

        $cGPay = new CGPay();
        $cGPay->setPrivateKey('1234');
        $cGPay->setOptions($this->returnResult);
        $cGPay->verifyOrderPayment([]);
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

        $cGPay = new CGPay();
        $cGPay->setPrivateKey('1234');
        $cGPay->setOptions($this->returnResult);
        $cGPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時單號不正確的情況
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $this->returnResult['Sign'] = '2C875C4B801C61DE7C511B2A7C906FF3';

        $entry = ['id' => '201606220000002806'];

        $cGPay = new CGPay();
        $cGPay->setPrivateKey('1234');
        $cGPay->setOptions($this->returnResult);
        $cGPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時金額不正確的情況
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $this->returnResult['Sign'] = '2C875C4B801C61DE7C511B2A7C906FF3';

        $entry = [
            'id' => '201804160000011161',
            'amount' => '10',
        ];

        $cGPay = new CGPay();
        $cGPay->setPrivateKey('1234');
        $cGPay->setOptions($this->returnResult);
        $cGPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回驗證成功
     */
    public function testReturnSuccess()
    {
        $this->returnResult['Sign'] = '2C875C4B801C61DE7C511B2A7C906FF3';

        $entry = [
            'id' => '201804160000011161',
            'amount' => '1',
        ];

        $cGPay = new CGPay();
        $cGPay->setPrivateKey('1234');
        $cGPay->setOptions($this->returnResult);
        $cGPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $cGPay->getMsg());
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

        $cGPay = new CGPay();
        $cGPay->withdrawPayment();
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

        $cGPay = new CGPay();
        $cGPay->setPrivateKey('3c673fb7d5');

        $cGPay->setOptions([]);
        $cGPay->withdrawPayment();
    }

    /**
     * 測試出款金額不為整數
     */
    public function testWithdrawAmountNotBeInteger()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Amount must be an integer',
            150180193
        );

        $this->withdrawOptions['amount'] = '1.23';

        $cGPay = new CGPay();
        $cGPay->setPrivateKey('12345');
        $cGPay->setOptions($this->withdrawOptions);
        $cGPay->withdrawPayment();
    }

    /**
     * 測試出款但返回結果缺少ReturnCode
     */
    public function testWithdrawButNoReturnReturnCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No withdraw return parameter specified',
            150180209
        );

        $result = [];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $cGPay = new CGPay();
        $cGPay->setContainer($this->container);
        $cGPay->setClient($this->client);
        $cGPay->setResponse($response);
        $cGPay->setPrivateKey('12345');
        $cGPay->setOptions($this->withdrawOptions);
        $cGPay->withdrawPayment();
    }

    /**
     * 測試出款但返回結果缺少RetrunMessage
     */
    public function testWithdrawButNoReturnRetrunMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No withdraw return parameter specified',
            150180209
        );

        $result = ['ReturnCode' => '0'];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $cGPay = new CGPay();
        $cGPay->setContainer($this->container);
        $cGPay->setClient($this->client);
        $cGPay->setResponse($response);
        $cGPay->setPrivateKey('12345');
        $cGPay->setOptions($this->withdrawOptions);
        $cGPay->withdrawPayment();
    }

    /**
     * 測試出款返回異常
     */
    public function testWithdrawFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Error Amount 只能為10000000 的倍數',
            180124
        );

        $result = [
            'WithdrawId' => null,
            'ReturnCode' => '400',
            'RetrunMessage' => 'Error Amount 只能為10000000 的倍數',
            'Sign' => null,
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $cGPay = new CGPay();
        $cGPay->setContainer($this->container);
        $cGPay->setClient($this->client);
        $cGPay->setResponse($response);
        $cGPay->setPrivateKey('12345');
        $cGPay->setOptions($this->withdrawOptions);
        $cGPay->withdrawPayment();
    }

    /**
     * 測試出款返回成功
     */
    public function testWithdrawSuccess()
    {
        $result = [
            'WithdrawId' => 'tpwnhzs6xmt3d',
            'ReturnCode' => '0',
            'RetrunMessage' => '成功',
            'Sign' => '5CB7C94E5014BC6F9A5577FAC64817D5',
        ];

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
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $cGPay = new CGPay();
        $cGPay->setContainer($mockContainer);
        $cGPay->setClient($this->client);
        $cGPay->setResponse($response);
        $cGPay->setPrivateKey('12345');
        $cGPay->setOptions($this->withdrawOptions);
        $cGPay->withdrawPayment();
    }
}
