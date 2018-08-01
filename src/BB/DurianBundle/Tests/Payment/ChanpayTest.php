<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Chanpay;
use Buzz\Message\Response;

class ChanpayTest extends DurianTestCase
{
    /**
     * 此部分用於需要取得Container的時候
     *
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * @var \Buzz\Client\Curl
     */
    private $client;

    /**
     * 私鑰
     *
     * @var string
     */
    private $privateKey;

    /**
     * 公鑰
     *
     * @var string
     */
    private $publicKey;

    /**
     * 此部分用於需要取得Container的時候
     */
    public function setUp()
    {
        parent::setUp();

        $config = [
            'private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        // Create the keypair
        $res = openssl_pkey_new($config);

        $privkey = '';
        // Get private key
        openssl_pkey_export($res, $privkey);
        $this->privateKey = $privkey;

        // Get public key
        $pubkey = openssl_pkey_get_details($res);
        $this->publicKey = $pubkey['key'];

        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $mockLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Payment')
            ->disableOriginalConstructor()
            ->setMethods(['record'])
            ->getMock();

        $mockLogger->expects($this->any())
            ->method('record')
            ->willReturn(null);

        $getMap = [
            ['durian.payment_logger', 1, $mockLogger]
        ];

        $this->container->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        $this->client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
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

        $chanpay = new Chanpay();
        $chanpay->setOptions($sourceData);
        $chanpay->withdrawPayment();
    }

    /**
     * 測試出款加密產生簽名失敗
     */
    public function testWithdrawGenerateSignatureFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Generate signature failure',
            180144
        );

        $config = [
            'private_key_bits' => 384,
            'private_key_type' => OPENSSL_KEYTYPE_DH,
        ];

        // Create the keypair
        $res = openssl_pkey_new($config);

        $privkey = '';
        // Get private key
        openssl_pkey_export($res, $privkey);

        $sourceData = [
            'number' => '200001300073',
            'orderCreateDate' => '20171128175000',
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '0.01',
            'orderId' => '112360',
            'withdraw_host' => 'payment.http.withdraw.com',
            'rsa_public_key' => base64_encode($this->publicKey),
            'rsa_private_key' => base64_encode($privkey),
        ];

        $chanpay = new Chanpay();
        $chanpay->setOptions($sourceData);
        $chanpay->withdrawPayment();
    }

    /**
     * 測試出款返回缺少AcceptStatus
     */
    public function testWithdrawWithoutAcceptStatus()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No withdraw return parameter specified',
            150180209
        );

        $sourceData = [
            'number' => '200001300073',
            'orderCreateDate' => '20171128175000',
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '0.01',
            'orderId' => '112360',
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'rsa_public_key' => base64_encode($this->publicKey),
            'rsa_private_key' => base64_encode($this->privateKey),
        ];

        $result = '{"InputCharset":"UTF-8","PartnerId":"200001300073","RetCode":"REQUIRED_FIELD_NOT_EXIST"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/xml;charset=UTF-8');

        $chanpay = new Chanpay();
        $chanpay->setContainer($this->container);
        $chanpay->setClient($this->client);
        $chanpay->setResponse($response);
        $chanpay->setOptions($sourceData);
        $chanpay->withdrawPayment();
    }

    /**
     * 測試出款返回結果失敗
     */
    public function testWithdrawButFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '错误代码[88888888]',
            180124
        );

        $sourceData = [
            'number' => '200001300073',
            'orderCreateDate' => '20171128175000',
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '0.01',
            'orderId' => '112360',
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'rsa_public_key' => base64_encode($this->publicKey),
            'rsa_private_key' => base64_encode($this->privateKey),
        ];

        $result = '{"AcceptStatus":"F","InputCharset":"UTF-8","PlatformErrorMessage":"错误代码[88888888]"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/xml;charset=UTF-8');

        $chanpay = new Chanpay();
        $chanpay->setContainer($this->container);
        $chanpay->setClient($this->client);
        $chanpay->setResponse($response);
        $chanpay->setOptions($sourceData);
        $chanpay->withdrawPayment();
    }

    /**
     * 測試出款返回錯誤
     */
    public function testWithdrawButError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Withdraw error',
            180124
        );

        $sourceData = [
            'number' => '200001300073',
            'orderCreateDate' => '20171128175000',
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '0.01',
            'orderId' => '112360',
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'rsa_public_key' => base64_encode($this->publicKey),
            'rsa_private_key' => base64_encode($this->privateKey),
        ];

        $result = '{"AcceptStatus":"F","InputCharset":"UTF-8","PartnerId":"200001300073",' .
            '"RetCode":"REQUIRED_FIELD_NOT_EXIST"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/xml;charset=UTF-8');

        $chanpay = new Chanpay();
        $chanpay->setContainer($this->container);
        $chanpay->setClient($this->client);
        $chanpay->setResponse($response);
        $chanpay->setOptions($sourceData);
        $chanpay->withdrawPayment();
    }

    /**
     * 測試出款返回缺少OriginalRetCode
     */
    public function testWithdrawWithoutOriginalRetCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No withdraw return parameter specified',
            150180209
        );

        $sourceData = [
            'number' => '200001300073',
            'orderCreateDate' => '20171128175000',
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '0.01',
            'orderId' => '112360',
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'rsa_public_key' => base64_encode($this->publicKey),
            'rsa_private_key' => base64_encode($this->privateKey),
        ];

        $result = '{"AcceptStatus":"S","AcctName":"P11166565","AcctNo":"P47566009"' .
            ',"AppRetMsg":"交易成功"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/xml;charset=UTF-8');

        $chanpay = new Chanpay();
        $chanpay->setContainer($this->container);
        $chanpay->setClient($this->client);
        $chanpay->setResponse($response);
        $chanpay->setOptions($sourceData);
        $chanpay->withdrawPayment();
    }

    /**
     * 測試出款提交未成功
     */
    public function testWithdrawNotSuccess()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '失败',
            180124
        );

        $sourceData = [
            'number' => '200001300073',
            'orderCreateDate' => '20171128175000',
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '0.01',
            'orderId' => '112360',
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'rsa_public_key' => base64_encode($this->publicKey),
            'rsa_private_key' => base64_encode($this->privateKey),
        ];

        $result = '{"AcceptStatus":"S","FlowNo":"11BS1TGOJ6E6538A","InputCharset":"UTF-8",' .
           '"OutTradeNo":"112360","PartnerId":"200001300073","OriginalErrorMessage":"失败",' .
           '"OriginalRetCode":"1000","TradeTime":"175512","TransCode":"T10100"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/xml;charset=UTF-8');

        $chanpay = new Chanpay();
        $chanpay->setContainer($this->container);
        $chanpay->setClient($this->client);
        $chanpay->setResponse($response);
        $chanpay->setOptions($sourceData);
        $chanpay->withdrawPayment();
    }

    /**
     * 測試出款返回錯誤
     */
    public function testWithdrawSuccess()
    {
        $sourceData = [
            'number' => '200001300073',
            'orderCreateDate' => '20171128175000',
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '0.01',
            'orderId' => '112360',
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'rsa_public_key' => base64_encode($this->publicKey),
            'rsa_private_key' => base64_encode($this->privateKey),
        ];

        $result = '{"AcceptStatus":"S","AcctName":"P11166565","AcctNo":"P47566009","AppRetMsg":' .
            '"交易成功","AppRetcode":"00019999","CorpName":"厦门市诚用明握网络科技有限公司","Fee":"2.00",' .
            '"FlowNo":"11C615DJNRE63B76","InputCharset":"UTF-8","OriginalErrorMessage":"成功[0000000]",' .
            '"OriginalRetCode":"000000","OutTradeNo":"112372A1512528841","PartnerId":"200001300073",' .
            '"PlatformErrorMessage":"交易受理成功","PlatformRetCode":"0000","Sign":"dQZPv5D3dnbFzrj9I",' .
            '"SignType":"RSA","TimeStamp":"20171206105211","TradeDate":"20171206","TradeTime":"105405",' .
            '"TransAmt":"1.00","TransCode":"T10000"}';

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
        $response->addHeader('Content-Type:text/xml;charset=UTF-8');

        $chanpay = new Chanpay();
        $chanpay->setContainer($mockContainer);
        $chanpay->setClient($this->client);
        $chanpay->setResponse($response);
        $chanpay->setOptions($sourceData);
        $chanpay->withdrawPayment();
    }
}
