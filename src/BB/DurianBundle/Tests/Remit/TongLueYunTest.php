<?php

namespace BB\DurianBundle\Tests\Remit;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Remit\TongLueYun;
use Buzz\Message\Response;

class TongLueYunTest extends WebTestCase
{
    /**
     * Container 的 mock
     *
     * @var Symfony\Component\DependencyInjection\Container
     */
    private $mockContainer;

    /**
     * @var \Buzz\Client\Curl
     */
    private $mockClient;

    /**
     * Doctrine 的 mock
     *
     * @var Doctrine\Bundle\DoctrineBundle\Registry
     */
    private $mockDoctrine;

    /**
     * em 的 mock
     *
     * @var Doctrine\ORM\EntityManager
     */
    private $mockEm;

    /**
     * 自動確認對外連線 log
     *
     * @var string
     */
    private $logPath;

    public function setUp()
    {
        parent::setUp();

        $this->mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find'])
            ->getMock();

        $this->mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();
        $this->mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($this->mockEm);

        $logger = $this->getContainer()->get('durian.remit_auto_confirm_logger');

        $this->mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get', 'getParameter'])
            ->getMock();
        $this->mockContainer->expects($this->any())
            ->method('get')
            ->willReturn($logger);
        $this->mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn('127.0.0.1');

        $this->mockClient = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $logDir = $this->getContainer()->getParameter('kernel.logs_dir') . DIRECTORY_SEPARATOR . 'test';
        $this->logPath = $logDir . DIRECTORY_SEPARATOR . 'remit_auto_confirm.log';
    }

    /**
     * 測試檢查自動認款apikey時遮罩 apikey
     */
    public function testMaskApiKeyWhenCheckApiKey()
    {
        $logger = $this->getContainer()->get('durian.remit_auto_confirm_logger');

        $mockContainer = $this->getMockBuilder(get_class($this->getContainer()))
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $mockContainer->expects($this->any())
            ->method('get')
            ->with('durian.remit_auto_confirm_logger')
            ->will($this->returnValue($logger));

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn('payment.https.s04.tonglueyun.com');

        $response = new Response();
        $response->setContent('{"success": true}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $tongLueYun = new TongLueYun();
        $tongLueYun->setContainer($this->mockContainer);
        $tongLueYun->setClient($this->mockClient);
        $tongLueYun->setResponse($response);
        $tongLueYun->setContainer($mockContainer);

        $tongLueYun->checkApiKey('hailostony');

        // 檢查 log 是否存在
        $this->assertFileExists($this->logPath);

        // 檢查 log 內容
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $logMsg = 'payment.https.s04.tonglueyun.com "POST /authority/system/api/list_order/" ' .
            '"HEADER: " "REQUEST: {"apikey":"******"}" "RESPONSE: {"success": true}"';
        $this->assertContains($logMsg, $results[0]);
    }

    /**
     * 測試查詢自動認款帳號時遮罩 apikey
     */
    public function testMaskApiKeyWhenCheckAutoRemitAccount()
    {
        $logger = $this->getContainer()->get('durian.remit_auto_confirm_logger');

        $mockContainer = $this->getMockBuilder(get_class($this->getContainer()))
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $mockContainer->expects($this->exactly(2))
            ->method('get')
            ->will($this->onConsecutiveCalls($this->mockDoctrine, $logger));

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn('payment.https.s04.tonglueyun.com');

        $mockDomainAutoRemit = $this->getMockBuilder('BB\DurianBundle\Entity\DomainAutoRemit')
            ->disableOriginalConstructor()
            ->getMock();
        $mockDomainAutoRemit->expects($this->any())
            ->method('getApiKey')
            ->willReturn('123qweasdzxc');

        $mockBankInfo = $this->getMockBuilder('BB\DurianBundle\Entity\BankInfo')
            ->disableOriginalConstructor()
            ->getMock();
        $mockBankInfo->expects($this->any())
            ->method('getId')
            ->willReturn('1');

        $mockAutoRemit = $this->getMockBuilder('BB\DurianBundle\Entity\AutoRemit')
            ->disableOriginalConstructor()
            ->getMock();
        $mockAutoRemit->expects($this->any())
            ->method('getBankInfo')
            ->willReturn([$mockBankInfo]);

        $mockRemitAccount = $this->getMockBuilder('BB\DurianBundle\Entity\RemitAccount')
            ->disableOriginalConstructor()
            ->setMethods(['getBankInfoId', 'getAccount', 'getAutoRemitId', 'getDomain'])
            ->getMock();
        $mockRemitAccount->expects($this->any())
            ->method('getBankInfoId')
            ->willReturn(1);
        $mockRemitAccount->expects($this->any())
            ->method('getAccount')
            ->willReturn('123456');
        $mockRemitAccount->expects($this->any())
            ->method('getAutoRemitId')
            ->willReturn(1);
        $mockRemitAccount->expects($this->any())
            ->method('getDomain')
            ->willReturn(2);

        $this->mockEm->expects($this->at(0))
            ->method('find')
            ->willReturn($mockAutoRemit);
        $this->mockEm->expects($this->at(1))
            ->method('find')
            ->willReturn($mockDomainAutoRemit);

        $response = new Response();
        $response->setContent('{"balance": 4071.77, "success": true}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $tongLueYun = new TongLueYun();
        $tongLueYun->setContainer($this->mockContainer);
        $tongLueYun->setRemitAccount($mockRemitAccount);
        $tongLueYun->setClient($this->mockClient);
        $tongLueYun->setResponse($response);
        $tongLueYun->setContainer($mockContainer);

        $tongLueYun->checkAutoRemitAccount();

        // 檢查 log 是否存在
        $this->assertFileExists($this->logPath);

        // 檢查 log 內容
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $logMsg = 'payment.https.s04.tonglueyun.com "POST /authority/system/api/query_bankcard/" ' .
            '"HEADER: " "REQUEST: {"apikey":"******","bank_flag":"ICBC","card_number":"123456"}" ' .
            '"RESPONSE: {"balance": 4071.77, "success": true}"';
        $this->assertContains($logMsg, $results[0]);
    }

    /**
     * 測試提交自動認款訂單時遮罩 apikey
     */
    public function testMaskApiKeyWhenSubmitAutoRemitEntry()
    {
        $logger = $this->getContainer()->get('durian.remit_auto_confirm_logger');

        $mockContainer = $this->getMockBuilder(get_class($this->getContainer()))
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $mockContainer->expects($this->exactly(2))
            ->method('get')
            ->will($this->onConsecutiveCalls($this->mockDoctrine, $logger));

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn('payment.https.s04.tonglueyun.com');

        $mockDomainAutoRemit = $this->getMockBuilder('BB\DurianBundle\Entity\DomainAutoRemit')
            ->disableOriginalConstructor()
            ->getMock();
        $mockDomainAutoRemit->expects($this->any())
            ->method('getApiKey')
            ->willReturn('123qweasdzxc');

        $this->mockEm->expects($this->at(0))
            ->method('find')
            ->willReturn($mockDomainAutoRemit);

        $mockRemitAccount = $this->getMockBuilder('BB\DurianBundle\Entity\RemitAccount')
            ->disableOriginalConstructor()
            ->setMethods(['getBankInfoId', 'getAccount'])
            ->getMock();
        $mockRemitAccount->expects($this->any())
            ->method('getBankInfoId')
            ->willReturn(1);
        $mockRemitAccount->expects($this->any())
            ->method('getAccount')
            ->willReturn('8825252');

        $response = new Response();
        $response->setContent('{"id": 7025875, "success": true}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $tongLueYun = new TongLueYun();
        $tongLueYun->setContainer($this->mockContainer);
        $tongLueYun->setRemitAccount($mockRemitAccount);
        $tongLueYun->setClient($this->mockClient);
        $tongLueYun->setResponse($response);
        $tongLueYun->setContainer($mockContainer);

        $payData = [
            'pay_card_number' => '987654321',
            'pay_username' => '戰鬥民族',
            'amount' => '123',
        ];
        $tongLueYun->submitAutoRemitEntry('123456', $payData);

        // 檢查 log 是否存在
        $this->assertFileExists($this->logPath);

        // 檢查 log 內容
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $logMsg = '/\[\d{4}-\d\d-\d\d \d\d:\d\d:\d\d\] app\.INFO: .*? payment.https.s04.tonglueyun.com ' .
            '"POST \/authority\/system\/api\/place_order\/" ' .
            '"HEADER: " "REQUEST: {"apikey":"\*\*\*\*\*\*","order_id":"123456",' .
            '"bank_flag":"ICBC","card_login_name":"","card_number":"8825252","pay_card_number":"987654321",' .
            '"pay_username":"\\\u6230\\\u9b25\\\u6c11\\\u65cf","amount":"123","create_time":\d+,"comment":"123456"}" ' .
            '"RESPONSE: {"id": 7025875, "success": true}" [] []/';
        $this->assertRegExp($logMsg, $results[0]);
    }

    /**
     * 測試取消自動認款訂單時遮罩 apikey
     */
    public function testMaskApiKeyWhenCancelAutoRemitEntry()
    {
        $logger = $this->getContainer()->get('durian.remit_auto_confirm_logger');

        $mockContainer = $this->getMockBuilder(get_class($this->getContainer()))
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $mockContainer->expects($this->exactly(2))
            ->method('get')
            ->will($this->onConsecutiveCalls($this->mockDoctrine, $logger));

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn('payment.https.s04.tonglueyun.com');

        $mockDomainAutoRemit = $this->getMockBuilder('BB\DurianBundle\Entity\DomainAutoRemit')
            ->disableOriginalConstructor()
            ->getMock();
        $mockDomainAutoRemit->expects($this->any())
            ->method('getApiKey')
            ->willReturn('123qweasdzxc');

        $mockRemitAccount = $this->getMockBuilder('BB\DurianBundle\Entity\RemitAccount')
            ->disableOriginalConstructor()
            ->getMock();

        $mockRemitAutoConfirm = $this->getMockBuilder('BB\DurianBundle\Entity\RemitAutoConfirm')
            ->disableOriginalConstructor()
            ->setMethods(['getAutoConfirmId'])
            ->getMock();
        $mockRemitAutoConfirm->expects($this->any())
            ->method('getAutoConfirmId')
            ->willReturn(8704746);

        $this->mockEm->expects($this->at(0))
            ->method('find')
            ->willReturn($mockDomainAutoRemit);
        $this->mockEm->expects($this->at(1))
            ->method('find')
            ->willReturn($mockRemitAutoConfirm);

        $mockRemitEntry = $this->getMockBuilder('BB\DurianBundle\Entity\RemitEntry')
            ->disableOriginalConstructor()
            ->getMock();

        $response = new Response();
        $response->setContent('{"success": true}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $tongLueYun = new TongLueYun();
        $tongLueYun->setContainer($this->mockContainer);
        $tongLueYun->setRemitAccount($mockRemitAccount);
        $tongLueYun->setClient($this->mockClient);
        $tongLueYun->setResponse($response);
        $tongLueYun->setContainer($mockContainer);

        $tongLueYun->cancelAutoRemitEntry($mockRemitEntry);

        // 檢查 log 是否存在
        $this->assertFileExists($this->logPath);

        // 檢查 log 內容
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $logMsg = 'payment.https.s04.tonglueyun.com "POST /authority/system/api/revoke_order/" ' .
            '"HEADER: " "REQUEST: {"apikey":"******","id":8704746}" "RESPONSE: {"success": true}"';
        $this->assertContains($logMsg, $results[0]);
    }

    /**
     * 清除產生的 log 檔案
     */
    public function tearDown()
    {
        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }

        parent::tearDown();
    }
}
