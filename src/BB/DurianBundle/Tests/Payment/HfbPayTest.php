<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\HfbPay;
use Buzz\Message\Response;

class HfbPayTest extends DurianTestCase
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
     * 測試出款沒有帶入privateKey
     */
    public function testWithdrawWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $hfbPay = new HfbPay();
        $hfbPay->withdrawPayment();
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

        $hfbPay = new HfbPay();
        $hfbPay->setPrivateKey('jy9CV6uguTE=');
        $hfbPay->setOptions($sourceData);
        $hfbPay->withdrawPayment();
    }

    /**
     * 測試出款沒有帶入withdraw_host
     */
    public function testWithdrawWithoutWithdrawHost()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No withdraw_host specified',
            150180194
        );

        $sourceData = [
            'account' => '123',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '0.01',
            'orderId' => '112332',
            'number' => '10000080001641',
            'orderCreateDate' => '2018-07-18 10:40:05',
            'bank_name' => '中信银行',
            'withdraw_host' => '',
            'shop_url' => 'http://pay.test/pay/',
            'branch' => '北京支行',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $hfbPay = new HfbPay();
        $hfbPay->setPrivateKey('jy9CV6uguTE=');
        $hfbPay->setOptions($sourceData);
        $hfbPay->withdrawPayment();
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
            'account' => '123',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '0.01',
            'orderId' => '112332',
            'number' => '10000080001641',
            'orderCreateDate' => '2018-07-18 10:40:05',
            'bank_name' => '中信银行',
            'withdraw_host' => 'payment.http.withdraw.com',
            'shop_url' => 'http://pay.test/pay/',
            'branch' => '北京支行',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<?xml version="1.0" encoding="UTF-8"?><moboAccount>' .
            '<respData><respCode>20</respCode>' .
            '</respData><signMsg>2691856FDBE3D20BCA5BA962B9E68372</signMsg></moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $hfbPay = new HfbPay();
        $hfbPay->setContainer($this->container);
        $hfbPay->setClient($this->client);
        $hfbPay->setResponse($response);
        $hfbPay->setPrivateKey('jy9CV6uguTE=');
        $hfbPay->setOptions($sourceData);
        $hfbPay->withdrawPayment();
    }

    /**
     * 測試出款返回異常
     */
    public function testWithdrawFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '商户人工结算申请金额超过可结算金额[商户余额不足]',
            180124
        );

        $sourceData = [
            'account' => '123',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '0.01',
            'orderId' => '112332',
            'number' => '10000080001641',
            'orderCreateDate' => '2018-07-18 10:40:05',
            'bank_name' => '中信银行',
            'withdraw_host' => 'payment.http.withdraw.com',
            'shop_url' => 'http://pay.test/pay/',
            'branch' => '北京支行',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<?xml version="1.0" encoding="UTF-8"?><moboAccount>' .
            '<respData><respCode>276</respCode>' .
            '<respDesc>商户人工结算申请金额超过可结算金额[商户余额不足]</respDesc>' .
            '</respData><signMsg>2691856FDBE3D20BCA5BA962B9E68372</signMsg></moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $hfbPay = new HfbPay();
        $hfbPay->setContainer($this->container);
        $hfbPay->setClient($this->client);
        $hfbPay->setResponse($response);
        $hfbPay->setPrivateKey('jy9CV6uguTE=');
        $hfbPay->setOptions($sourceData);
        $hfbPay->withdrawPayment();
    }

    /**
     * 測試出款成功但沒有返回訂單號
     */
    public function testWithdrawSuccessWithoutBatchNo()
    {
        $sourceData = [
            'account' => '123',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '0.01',
            'orderId' => '112332',
            'number' => '10000080001641',
            'orderCreateDate' => '2018-07-18 10:40:05',
            'bank_name' => '中信银行',
            'withdraw_host' => 'payment.http.withdraw.com',
            'shop_url' => 'http://pay.test/pay/',
            'branch' => '北京支行',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<?xml version="1.0" encoding="UTF-8"?><moboAccount>' .
            '<respData><respCode>00</respCode>' .
            '<respDesc>交易成功</respDesc><accDate>20180110</accDate>' .
            '</respData><signMsg>8ADFDAFDB14AD630BCC5897D38038F97</signMsg></moboAccount>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $hfbPay = new HfbPay();
        $hfbPay->setContainer($this->container);
        $hfbPay->setClient($this->client);
        $hfbPay->setResponse($response);
        $hfbPay->setPrivateKey('jy9CV6uguTE=');
        $hfbPay->setOptions($sourceData);
        $hfbPay->withdrawPayment();
    }

    /**
     * 測試出款返回成功
     */
    public function testWithdrawSuccess()
    {
        $sourceData = [
            'account' => '123',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '0.01',
            'orderId' => '112332',
            'number' => '10000080001641',
            'orderCreateDate' => '2018-07-18 10:40:05',
            'bank_name' => '中信银行',
            'withdraw_host' => 'payment.http.withdraw.com',
            'shop_url' => 'http://pay.test/pay/',
            'branch' => '北京支行',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $result = '<?xml version="1.0" encoding="UTF-8"?><moboAccount>' .
            '<respData><respCode>00</respCode>' .
            '<respDesc>交易成功</respDesc><batchNo>705111</batchNo><accDate>20180110</accDate>' .
            '</respData><signMsg>8ADFDAFDB14AD630BCC5897D38038F97</signMsg></moboAccount>';

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
        $mockEm->expects($this->any())
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
            ->willReturnMap($getMap);

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/xml;charset=UTF-8');

        $hfbPay = new HfbPay();
        $hfbPay->setContainer($mockContainer);
        $hfbPay->setClient($this->client);
        $hfbPay->setResponse($response);
        $hfbPay->setPrivateKey('jy9CV6uguTE=');
        $hfbPay->setOptions($sourceData);
        $hfbPay->withdrawPayment();
    }
}
