<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use Buzz\Message\Response;

class RemitAccountFunctionalTest extends WebTestCase
{
    /**
     * @var \Buzz\Client\Curl
     */
    private $mockClient;

    /**
     * 查詢自動認款帳號對外連線 log
     *
     * @var string
     */
    private $logPath;

    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankInfoData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemitAccountData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemitAccountLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemitAccountQrcodeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemitAccountStatData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadAutoRemitData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainAutoRemitData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadAutoRemitHasBankInfoData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemitAccountVersionData',
        ];

        $this->loadFixtures($classnames);
        $this->loadFixtures([], 'share');

        $this->clearPaymentOperationLog();

        $this->mockClient = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $logDir = $this->getContainer()->getParameter('kernel.logs_dir') . DIRECTORY_SEPARATOR . 'test';
        $this->logPath = $logDir . DIRECTORY_SEPARATOR . 'remit_auto_confirm.log';
    }

    /**
     * 測試新增公司出入款帳號
     */
    public function testCreateRemitAccount()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = [
            'domain' => 2,
            'bank_info_id' => 1,
            'account' => '123454321',
            'account_type' => 1,
            'auto_confirm' => 0,
            'currency' => 'CNY',
            'control_tips' => '控端提示',
            'recipient' => '收款人',
            'message' => '<p>會員端提示訊息</p>',
            'enable' => 1,
            'level_id' => [1, 2]
        ];

        $client->request('POST', '/api/remit_account', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $remitAccount = $em->find('BBDurianBundle:RemitAccount', $output['ret']['id']);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($remitAccount->getId(), $output['ret']['id']);
        $this->assertEquals($remitAccount->getDomain(), $output['ret']['domain']);
        $this->assertEquals($remitAccount->getBankInfoId(), $output['ret']['bank_info_id']);
        $this->assertEquals($remitAccount->getAccountType(), $output['ret']['account_type']);
        $this->assertEquals(0, $output['ret']['auto_remit_id']);
        $this->assertEquals($remitAccount->isAutoConfirm(), $output['ret']['auto_confirm']);
        $this->assertEquals($remitAccount->getAccount(), $output['ret']['account']);
        $this->assertEquals('CNY', $output['ret']['currency']);
        $this->assertEquals($remitAccount->getControlTips(), $output['ret']['control_tips']);
        $this->assertEquals($remitAccount->getRecipient(), $output['ret']['recipient']);
        $this->assertEquals('&lt;p&gt;會員端提示訊息&lt;/p&gt;', $output['ret']['message']);
        $this->assertEquals($remitAccount->isEnabled(), $output['ret']['enable']);

        $this->assertEquals(1, $output['ret']['level_id'][0]);
        $this->assertEquals(2, $output['ret']['level_id'][1]);

        // 操作紀錄檢查
        $message = [
            '@domain:2',
            '@bank_info_id:1',
            '@account_type:1',
            '@account:123454321',
            '@currency:CNY',
            '@control_tips:控端提示',
            '@recipient:收款人',
            '@message:<p>會員端提示訊息</p>',
        ];

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remit_account', $logOperation->getTableName());
        $this->assertEquals('@id:10', $logOperation->getMajorKey());
        $this->assertEquals(implode(', ', $message), $logOperation->getMessage());

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('remit_account_level', $logOperation->getTableName());
        $this->assertEquals('@id:10', $logOperation->getMajorKey());
        $this->assertEquals('@level_id:1, 2', $logOperation->getMessage());

        // 檢查金流操作紀錄
        $paymentOperationLogPath = $this->getLogfilePath('payment_operation.log');
        $this->assertFileExists($paymentOperationLogPath);

        $paymentOperationLog = explode(PHP_EOL, file_get_contents($paymentOperationLogPath));
        $this->assertContains('result=ok', $paymentOperationLog[0]);

        // 檢查 log 是否不存在
        $this->assertFileNotExists($this->logPath);
    }

    /**
     * 測試新增自動確認的公司出入款帳號
     */
    public function testCreateRemitAccountWithAutoConfirm()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $logger = $this->getContainer()->get('durian.remit_auto_confirm_logger');

        $client = $this->createClient();

        $doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();

        $doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($em);

        $mockContainer = $this->getMockBuilder(get_class($this->getContainer()))
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $mockContainer->expects($this->exactly(3))
            ->method('get')
            ->will($this->onConsecutiveCalls($doctrine, $doctrine, $logger));

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn('payment.https.s04.tonglueyun.com');

        $response = new Response();
        $response->setContent('{"balance": 4071.77, "success": true}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);

        $parameters = [
            'domain' => 2,
            'bank_info_id' => 1,
            'account' => '123454321',
            'account_type' => 1,
            'auto_remit_id' => 1,
            'auto_confirm' => 1,
            'currency' => 'CNY',
            'control_tips' => '控端提示',
            'recipient' => '收款人',
            'message' => '<p>會員端提示訊息</p>',
            'enable' => 1,
        ];

        $client->request('POST', '/api/remit_account', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $remitAccount = $em->find('BBDurianBundle:RemitAccount', $output['ret']['id']);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($remitAccount->isAutoConfirm(), $output['ret']['auto_confirm']);
        $this->assertEquals(1, $output['ret']['auto_remit_id']);

        // 操作紀錄檢查
        $message = [
            '@domain:2',
            '@bank_info_id:1',
            '@account_type:1',
            '@account:123454321',
            '@currency:CNY',
            '@control_tips:控端提示',
            '@recipient:收款人',
            '@message:<p>會員端提示訊息</p>',
            '@auto_confirm:true',
            '@auto_remit_id:1',
        ];

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remit_account', $logOperation->getTableName());
        $this->assertEquals('@id:10', $logOperation->getMajorKey());
        $this->assertEquals(implode(', ', $message), $logOperation->getMessage());

        // 檢查 log 是否存在
        $this->assertFileExists($this->logPath);

        // 檢查 log 內容
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $logMsg = '/\[\d{4}-\d\d-\d\d \d\d:\d\d:\d\d\] app\.INFO: .*? payment.https.s04.tonglueyun.com ' .
            '"POST \/authority\/system\/api\/query_bankcard\/" ' .
            '"HEADER: " "REQUEST: {"apikey":"\*\*\*\*\*\*",' .
            '"bank_flag":"ICBC","card_number":"123454321"}" ' .
            '"RESPONSE: {"balance": 4071\.77, "success": true}" [] []/';
        $this->assertRegExp($logMsg, $results[0]);
    }

    /**
     * 測試新增BB自動認款的公司出入款帳號
     */
    public function testCreateRemitAccountWithBbAutoConfirm()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $logger = $this->getContainer()->get('durian.remit_auto_confirm_logger');

        $client = $this->createClient();

        $parameters = [
            'domain' => 2,
            'bank_info_id' => 1,
            'account' => '123454321',
            'account_type' => 1,
            'auto_remit_id' => 2,
            'auto_confirm' => 1,
            'currency' => 'CNY',
            'control_tips' => '控端提示',
            'recipient' => '收款人',
            'message' => '<p>會員端提示訊息</p>',
            'enable' => 1,
            'bank_limit' => '100000',
            'crawler_on' => 1,
            'web_bank_account' => 'webAccount',
            'web_bank_password' => 'webPassword',
        ];

        $client->request('POST', '/api/remit_account', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $remitAccount = $em->find('BBDurianBundle:RemitAccount', $output['ret']['id']);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($remitAccount->isAutoConfirm(), $output['ret']['auto_confirm']);
        $this->assertEquals(2, $output['ret']['auto_remit_id']);
        $this->assertEquals(2, $remitAccount->getAutoRemitId());

        // 操作紀錄檢查
        $message = [
            '@domain:2',
            '@bank_info_id:1',
            '@account_type:1',
            '@account:123454321',
            '@currency:CNY',
            '@control_tips:控端提示',
            '@recipient:收款人',
            '@message:<p>會員端提示訊息</p>',
            '@auto_confirm:true',
            '@auto_remit_id:2',
            '@bank_limit:100000',
            '@crawler_on:true',
            '@web_bank_account:webAccount',
            '@web_bank_password:*****',
        ];

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remit_account', $logOperation->getTableName());
        $this->assertEquals('@id:10', $logOperation->getMajorKey());
        $this->assertEquals(implode(', ', $message), $logOperation->getMessage());
    }

    /**
     * 測試新增秒付通自動認款入款帳號時，無自動認款廳主設定
     */
    public function testCreateRemitAccountWithMiaoFuTongWhenWithoutDomainAutoRemit()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $client = $this->createClient();

        $criteria = [
            'domain' => 2,
            'autoRemitId' => 3,
        ];
        $domainAutoRemit = $em->getRepository('BBDurianBundle:DomainAutoRemit')->findOneBy($criteria);
        $this->assertNull($domainAutoRemit);

        $bankInfo = $em->find('BBDurianBundle:BankInfo', 1);
        $autoRemit = $em->find('BBDurianBundle:AutoRemit', 3);
        $autoRemit->addBankInfo($bankInfo);

        $em->flush();

        $sql = 'INSERT INTO user_payway (user_id, cash, cash_fake, credit, outside) VALUES (2, 1, 0, 0, 0);';
        $em->getConnection()->executeUpdate($sql);

        $parameters = [
            'domain' => 2,
            'bank_info_id' => 1,
            'account' => '123454321',
            'account_type' => 1,
            'auto_remit_id' => 3,
            'auto_confirm' => 1,
            'currency' => 'CNY',
            'control_tips' => '控端提示',
            'recipient' => '收款人',
            'message' => '<p>會員端提示訊息</p>',
            'enable' => 1,
            'bank_limit' => '100000',
            'crawler_on' => 1,
            'web_bank_account' => 'webAccount',
            'web_bank_password' => 'webPassword',
        ];

        $client->request('POST', '/api/remit_account', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $remitAccount = $em->find('BBDurianBundle:RemitAccount', $output['ret']['id']);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($remitAccount->isAutoConfirm(), $output['ret']['auto_confirm']);
        $this->assertEquals(3, $output['ret']['auto_remit_id']);
        $this->assertEquals(3, $remitAccount->getAutoRemitId());

        // 操作紀錄檢查
        $message = [
            '@domain:2',
            '@bank_info_id:1',
            '@account_type:1',
            '@account:123454321',
            '@currency:CNY',
            '@control_tips:控端提示',
            '@recipient:收款人',
            '@message:<p>會員端提示訊息</p>',
            '@auto_confirm:true',
            '@auto_remit_id:3',
            '@bank_limit:100000',
            '@crawler_on:true',
            '@web_bank_account:webAccount',
            '@web_bank_password:*****',
        ];

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remit_account', $logOperation->getTableName());
        $this->assertEquals('@id:10', $logOperation->getMajorKey());
        $this->assertEquals(implode(', ', $message), $logOperation->getMessage());
    }

    /**
     * 測試新增公司出入款帳號時為停用狀態
     */
    public function testCreateRemitAccountWithDisable()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = [
            'domain' => 2,
            'bank_info_id' => 1,
            'account' => '12345',
            'account_type' => 1,
            'currency' => 'CNY',
            'control_tips' => '控端提示',
            'enable' => 0
        ];

        $client->request('POST', '/api/remit_account', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(0, $output['ret']['enable']);

        // 操作紀錄檢查
        $message = [
            '@domain:2',
            '@bank_info_id:1',
            '@account_type:1',
            '@account:12345',
            '@currency:CNY',
            '@control_tips:控端提示',
            '@enable:0'
        ];

        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remit_account', $logOperation->getTableName());
        $this->assertEquals('@id:10', $logOperation->getMajorKey());
        $this->assertEquals(implode(', ', $message), $logOperation->getMessage());

        $logOperation = $em->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('remit_account_level', $logOperation->getTableName());
        $this->assertEquals('@id:10', $logOperation->getMajorKey());
        $this->assertEquals('@level_id:', $logOperation->getMessage());
    }

    /**
     * 測試新增公司出入款帳號例外
     */
    public function testCreateRemitAccountException()
    {
        $client = $this->createClient();

        // 測試未帶入廳
        $parameters = [
            'bank_info_id' => 1,
            'account' => '1234567890',
            'account_type' => 0,
            'currency' => 'CNY',
            'control_tips' => '控端提示'
        ];

        $client->request('POST', '/api/remit_account', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550014, $output['code']);
        $this->assertEquals('No domain specified', $output['msg']);

        // 測試未帶入銀行ID
        $parameters = [
            'domain' => 2,
            'account' => '1234567890',
            'account_type' => 0,
            'currency' => 'CNY',
            'control_tips' => '控端提示'
        ];

        $client->request('POST', '/api/remit_account', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550003, $output['code']);
        $this->assertEquals('Invalid bank_info_id', $output['msg']);

        // 測試帶入不存在的銀行ID
        $parameters = [
            'domain' => 2,
            'bank_info_id' => 99,
            'account' => '1234567890',
            'account_type' => 0,
            'currency' => 'CNY',
            'control_tips' => '控端提示'
        ];

        $client->request('POST', '/api/remit_account', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550010, $output['code']);
        $this->assertEquals('No BankInfo found', $output['msg']);

        // 測試未帶入帳號
        $parameters = [
            'domain' => 2,
            'bank_info_id' => 1,
            'account_type' => 0,
            'currency' => 'CNY',
            'control_tips' => '控端提示'
        ];

        $client->request('POST', '/api/remit_account', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550005, $output['code']);
        $this->assertEquals('No account specified', $output['msg']);

        // 測試未帶入帳號類別
        $parameters = [
            'domain' => 2,
            'bank_info_id' => 1,
            'account' => '1234567890',
            'currency' => 'CNY',
            'control_tips' => '控端提示'
        ];

        $client->request('POST', '/api/remit_account', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550006, $output['code']);
        $this->assertEquals('No account type specified', $output['msg']);

        // 測試未帶入幣別
        $parameters = [
            'domain' => 2,
            'bank_info_id' => 1,
            'account' => '1234567890',
            'account_type' => 0,
            'control_tips' => '控端提示'
        ];

        $client->request('POST', '/api/remit_account', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550001, $output['code']);
        $this->assertEquals('Currency not support', $output['msg']);

        // 測試帶入錯誤幣別
        $parameters = [
            'domain' => 2,
            'bank_info_id' => 1,
            'account' => '1234567890',
            'account_type' => 0,
            'currency' => 'AAA',
            'control_tips' => '控端提示'
        ];

        $client->request('POST', '/api/remit_account', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550001, $output['code']);
        $this->assertEquals('Currency not support', $output['msg']);

        // 測試未帶入控端提示
        $parameters = [
            'domain' => 2,
            'bank_info_id' => 1,
            'account' => '1234567890',
            'account_type' => 0,
            'currency' => 'CNY'
        ];

        $client->request('POST', '/api/remit_account', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550007, $output['code']);
        $this->assertEquals('No control tips specified', $output['msg']);

        // 測試新增重複帳號
        $parameters = [
            'domain' => 2,
            'bank_info_id' => 1,
            'account_type' => 1,
            'account' => '1234567890',
            'currency' => 'CNY',
            'control_tips' => '控端提示'
        ];

        $client->request('POST', '/api/remit_account', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550012, $output['code']);
        $this->assertEquals('This account already been used', $output['msg']);
    }

    /**
     * 測試新增自動確認的公司出入款帳號時，但銀行不支援
     */
    public function testCreateRemitAccountWithAutoConfirmButBankInfoIsNotSupported()
    {
        $client = $this->createClient();

        $parameters = [
            'domain' => 2,
            'bank_info_id' => 3,
            'account' => '123454321',
            'account_type' => 1,
            'auto_remit_id' => 1,
            'auto_confirm' => 1,
            'currency' => 'CNY',
            'control_tips' => '控端提示',
            'level_id' => [1],
        ];

        $client->request('POST', '/api/remit_account', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150870015, $output['code']);
        $this->assertEquals('BankInfo is not supported by AutoRemitBankInfo', $output['msg']);
    }

    /**
     * 測試新增自動確認的公司出入款帳號時，但自動認款連線異常
     */
    public function testCreateAutoConfirmRemitAccountButAutoConfirmConnectionError()
    {
        $client = $this->createClient();

        $exception = new \Exception('Auto Confirm connection failure', 150870021);
        $this->mockClient->expects($this->any())
            ->method('send')
            ->willThrowException($exception);

        $mockContainer = $this->getMockBuilder(get_class($this->getContainer()))
            ->setMethods(['getParameter'])
            ->getMock();

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn('payment.https.s04.tonglueyun.com');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setContainer($mockContainer);

        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);

        $parameters = [
            'domain' => 2,
            'bank_info_id' => 1,
            'account' => '123454321',
            'account_type' => 1,
            'auto_remit_id' => 1,
            'auto_confirm' => 1,
            'currency' => 'CNY',
            'control_tips' => '控端提示',
        ];
        $client->request('POST', '/api/remit_account', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150870021, $output['code']);
        $this->assertEquals('Auto Confirm connection failure', $output['msg']);

        // 檢查 log 是否不存在
        $this->assertFileNotExists($this->logPath);
    }

    /**
     * 測試新增自動確認的公司出入款帳號時，但自動認款連線失敗
     */
    public function testCreateAutoConfirmRemitAccountButAutoConfirmConnectionFailure()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $logger = $this->getContainer()->get('durian.remit_auto_confirm_logger');

        $client = $this->createClient();

        $doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();

        $doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($em);

        $mockContainer = $this->getMockBuilder(get_class($this->getContainer()))
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $mockContainer->expects($this->exactly(3))
            ->method('get')
            ->will($this->onConsecutiveCalls($doctrine, $doctrine, $logger));

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn('payment.https.s04.tonglueyun.com');

        $response = new Response();
        $response->addHeader('HTTP/1.1 499');
        $response->addHeader('Content-Type: application/json');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);

        $parameters = [
            'domain' => 2,
            'bank_info_id' => 1,
            'account' => '123454321',
            'account_type' => 1,
            'auto_remit_id' => 1,
            'auto_confirm' => 1,
            'currency' => 'CNY',
            'control_tips' => '控端提示',
        ];
        $client->request('POST', '/api/remit_account', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150870022, $output['code']);
        $this->assertEquals('Auto Confirm connection failure', $output['msg']);

        // 檢查 log 是否存在
        $this->assertFileExists($this->logPath);

        // 檢查 log 內容
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $logMsg = '/\[\d{4}-\d\d-\d\d \d\d:\d\d:\d\d\] app\.INFO: .*? payment.https.s04.tonglueyun.com ' .
            '"POST \/authority\/system\/api\/query_bankcard\/" "HEADER: " "REQUEST: {"apikey":"\*\*\*\*\*\*",' .
            '"bank_flag":"ICBC","card_number":"123454321"}" ' .
            '"RESPONSE: " [] []/';
        $this->assertRegExp($logMsg, $results[0]);
    }

    /**
     * 測試新增自動確認的公司出入款帳號時，但未指定自動認款返回參數
     */
    public function testCreateAutoConfirmRemitAccountButNoAutoConfirmReturnParameterSpecified()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $logger = $this->getContainer()->get('durian.remit_auto_confirm_logger');

        $client = $this->createClient();

        $doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();

        $doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($em);

        $mockContainer = $this->getMockBuilder(get_class($this->getContainer()))
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $mockContainer->expects($this->exactly(3))
            ->method('get')
            ->will($this->onConsecutiveCalls($doctrine, $doctrine, $logger));

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn('payment.https.s04.tonglueyun.com');

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);

        $parameters = [
            'domain' => 2,
            'bank_info_id' => 1,
            'account' => '123454321',
            'account_type' => 1,
            'auto_remit_id' => 1,
            'auto_confirm' => 1,
            'currency' => 'CNY',
            'control_tips' => '控端提示',
        ];
        $client->request('POST', '/api/remit_account', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150870027, $output['code']);
        $this->assertEquals('Please confirm auto_remit_account in the platform.', $output['msg']);

        // 檢查 log 是否存在
        $this->assertFileExists($this->logPath);

        // 檢查 log 內容
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $logMsg = '/\[\d{4}-\d\d-\d\d \d\d:\d\d:\d\d\] app\.INFO: .*? payment.https.s04.tonglueyun.com ' .
            '"POST \/authority\/system\/api\/query_bankcard\/" "HEADER: " "REQUEST: {"apikey":"\*\*\*\*\*\*",' .
            '"bank_flag":"ICBC","card_number":"123454321"}" ' .
            '"RESPONSE: " [] []/';
        $this->assertRegExp($logMsg, $results[0]);
    }

    /**
     * 測試新增自動確認的公司出入款帳號時，但銀行卡不存在
     */
    public function testCreateAutoConfirmRemitAccountButCardIsNotExisting()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $logger = $this->getContainer()->get('durian.remit_auto_confirm_logger');

        $client = $this->createClient();

        $doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();

        $doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($em);

        $mockContainer = $this->getMockBuilder(get_class($this->getContainer()))
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $mockContainer->expects($this->exactly(3))
            ->method('get')
            ->will($this->onConsecutiveCalls($doctrine, $doctrine, $logger));

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn('payment.https.s04.tonglueyun.com');;

        $response = new Response();
        $response->setContent('{"message": "This card is not existing.", "success": false}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);

        $parameters = [
            'domain' => 2,
            'bank_info_id' => 1,
            'account' => '123454321',
            'account_type' => 1,
            'auto_remit_id' => 1,
            'auto_confirm' => 1,
            'currency' => 'CNY',
            'control_tips' => '控端提示',
        ];
        $client->request('POST', '/api/remit_account', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150870024, $output['code']);
        $this->assertRegexp('/This card is not existing./', $output['msg']);

        // 檢查 log 是否存在
        $this->assertFileExists($this->logPath);

        // 檢查 log 內容
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $logMsg = '/This card is not existing/';
        $this->assertRegexp($logMsg, $results[0]);
    }

    /**
     * 測試新增自動確認的公司出入款帳號時，但自動認款失敗
     */
    public function testCreateAutoConfirmRemitAccountButAutoConfirmFailed()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $logger = $this->getContainer()->get('durian.remit_auto_confirm_logger');

        $client = $this->createClient();

        $doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();

        $doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($em);

        $mockContainer = $this->getMockBuilder(get_class($this->getContainer()))
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $mockContainer->expects($this->exactly(3))
            ->method('get')
            ->will($this->onConsecutiveCalls($doctrine, $doctrine, $logger));

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn('payment.https.s04.tonglueyun.com');

        $response = new Response();
        $response->setContent('{"balance": 4071.77, "success": false}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);

        $parameters = [
            'domain' => 2,
            'bank_info_id' => 1,
            'account' => '123454321',
            'account_type' => 1,
            'auto_remit_id' => 1,
            'auto_confirm' => 1,
            'currency' => 'CNY',
            'control_tips' => '控端提示',
        ];
        $client->request('POST', '/api/remit_account', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150870025, $output['code']);
        $this->assertEquals('Auto Confirm failed', $output['msg']);

        // 檢查 log 是否存在
        $this->assertFileExists($this->logPath);

        // 檢查 log 內容
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $logMsg = '/\[\d{4}-\d\d-\d\d \d\d:\d\d:\d\d\] app\.INFO: .*? payment.https.s04.tonglueyun.com ' .
            '"POST \/authority\/system\/api\/query_bankcard\/" "HEADER: " "REQUEST: {"apikey":"\*\*\*\*\*\*",' .
            '"bank_flag":"ICBC","card_number":"123454321"}" ' .
            '"RESPONSE: {"balance": 4071\.77, "success": false}" [] []/';
        $this->assertRegExp($logMsg, $results[0]);
    }

    /**
     * 測試新增自動確認的公司出入款帳號時，但帳號已重複
     */
    public function testCreateRemitAccountWithAutoConfirmButAccountAlreadyBeenUsed()
    {
        $client = $this->createClient();

        $parameters = [
            'domain' => 2,
            'bank_info_id' => 1,
            'account' => '1234567890',
            'account_type' => 1,
            'auto_confirm' => 1,
            'currency' => 'CNY',
            'control_tips' => '控端提示',
            'level_id' => [1],
        ];

        $client->request('POST', '/api/remit_account', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550012, $output['code']);
        $this->assertEquals('This account already been used', $output['msg']);
    }

    /**
     * 測試同分秒新增公司出入款帳號，且該廳尚未建立出入款帳號版本管理
     */
    public function testCreateRemitAccountAtTheSameTimeWithoutRemitAccountVersion()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $bankInfo = $em->find('BBDurianBundle:BankInfo', 1);

        $ravRepo = $this->getMockBuilder('BB\DurianBundle\Repository\RemitAccountVersionRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods([
                'beginTransaction',
                'find',
                'getRepository',
                'persist',
                'remove',
                'flush',
                'rollback',
                'clear'
            ])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($bankInfo);

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($ravRepo);

        $pdoExcep = new \PDOException('Duplicate', 1062);
        $pdoExcep->errorInfo[1] = 1062;
        $exception = new \Exception('An exception occurred while executing', 0, $pdoExcep);

        $mockEm->expects($this->any())
            ->method('flush')
            ->willThrowException($exception);

        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);

        $parameters = [
            'domain' => 2,
            'bank_info_id' => 1,
            'account' => '123454321',
            'account_type' => 1,
            'auto_confirm' => 0,
            'currency' => 'CNY',
            'control_tips' => '控端提示',
            'recipient' => '收款人',
            'message' => '<p>會員端提示訊息</p>',
            'enable' => 1,
        ];

        $client->request('POST', '/api/remit_account', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550015, $output['code']);
        $this->assertEquals('Database is busy', $output['msg']);
    }

    /**
     * 測試同分秒新增公司出入款帳號，且該廳已建立出入款帳號版本管理
     */
    public function testCreateRemitAccountAtTheSameTimeWithExistedRemitAccountVersion()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $bankInfo = $em->find('BBDurianBundle:BankInfo', 1);
        $remitAccountVersion = $em->find('BBDurianBundle:RemitAccountVersion', 1);

        $ravRepo = $this->getMockBuilder('BB\DurianBundle\Repository\RemitAccountVersionRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy','updateRemitAccountVersion'])
            ->getMock();

        $ravRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn($remitAccountVersion);

        $ravRepo->expects($this->any())
            ->method('updateRemitAccountVersion')
            ->willReturn(0);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods([
                'beginTransaction',
                'find',
                'getRepository',
                'persist',
                'remove',
                'flush',
                'rollback',
                'clear'
            ])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($bankInfo);

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($ravRepo);

        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);

        $parameters = [
            'domain' => 1,
            'bank_info_id' => 1,
            'account' => '123454321',
            'account_type' => 1,
            'auto_confirm' => 0,
            'currency' => 'CNY',
            'control_tips' => '控端提示',
            'recipient' => '收款人',
            'message' => '<p>會員端提示訊息</p>',
            'enable' => 1,
        ];

        $client->request('POST', '/api/remit_account', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550015, $output['code']);
        $this->assertEquals('Database is busy', $output['msg']);
    }

    /**
     * 測試新增公司出入款帳號時，帶入不存在的層級Id
     */
    public function testCreateRemitAccountButLevelNotFound()
    {
        $client = $this->createClient();

        $parameters = [
            'domain' => 2,
            'bank_info_id' => 1,
            'account' => '1234567890',
            'account_type' => 0,
            'currency' => 'CNY',
            'control_tips' => '控端提示',
            'level_id' => [999]
        ];

        $client->request('POST', '/api/remit_account', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550004, $output['code']);
        $this->assertEquals('No Level found', $output['msg']);
    }

    /**
     * 測試取得公司出入款帳號
     */
    public function testGetRemitAccount()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/remit_account/1');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertEquals(2, $output['ret']['domain']);
        $this->assertEquals(1, $output['ret']['bank_info_id']);
        $this->assertEquals('1234567890', $output['ret']['account']);
        $this->assertEquals(1, $output['ret']['account_type']);
        $this->assertEquals('CNY', $output['ret']['currency']);
        $this->assertEquals('Control Tips', $output['ret']['control_tips']);
        $this->assertEquals('Recipient', $output['ret']['recipient']);
        $this->assertEquals('Message', $output['ret']['message']);
        $this->assertTrue($output['ret']['enable']);
        $this->assertFalse($output['ret']['deleted']);
    }

    /**
     * 測試取得公司出入款帳號例外
     */
    public function testGetRemitAccountException()
    {
        $client = $this->createClient();

        // 測試帶入不存在的帳號ID
        $client->request('GET', '/api/remit_account/99');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550011, $output['code']);
        $this->assertEquals('No RemitAccount found', $output['msg']);
    }


    /**
     * 測試取得公司自動認款統計資料
     */
    public function testGetRemitAccountStat()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/remit_account/9/stat');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['count']);
        $this->assertEquals(500, $output['ret']['income']);
        $this->assertEquals(100, $output['ret']['payout']);
    }

    /**
     * 測試取得公司自動認款統計資料帶入美東時間
     */
    public function testGetRemitAccountStatWithTheEasternTimeZone()
    {
        $client = $this->createClient();

        $at = new \DateTime('2017-08-30T17:00:00-1200');

        $param = ['at' => $at->format(\DateTime::ISO8601)];

        $client->request('GET', '/api/remit_account/9/stat', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['count']);
        $this->assertEquals(700, $output['ret']['income']);
        $this->assertEquals(100, $output['ret']['payout']);
    }

    /**
     * 測試取得公司自動認款統計資料，資料不存在
     */
    public function testGetRemitAccountStatButNotExist()
    {
        $client = $this->createClient();

        $param = ['at' => (new \DateTime('tomorrow'))->format(\DateTime::ISO8601)];

        $client->request('GET', '/api/remit_account/9/stat', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(0, $output['ret']['count']);
        $this->assertEquals(0, $output['ret']['income']);
        $this->assertEquals(0, $output['ret']['payout']);
    }

    /**
     * 測試公司出入款帳號列表
     */
    public function testRemitAccountList()
    {
        $client = $this->createClient();

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $remitAccount = $em->find('BBDurianBundle:RemitAccount', 2);
        $remitAccount->suspend();
        $em->flush();

        $client->request('GET', '/api/remit_account/list');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(2, $output['ret'][0]['domain']);
        $this->assertEquals(1, $output['ret'][0]['bank_info_id']);
        $this->assertEquals('1234567890', $output['ret'][0]['account']);
        $this->assertEquals(1, $output['ret'][0]['account_type']);
        $this->assertEquals(0, $output['ret'][0]['auto_remit_id']);
        $this->assertFalse($output['ret'][0]['auto_confirm']);
        $this->assertEquals('CNY', $output['ret'][0]['currency']);
        $this->assertEquals('Control Tips', $output['ret'][0]['control_tips']);
        $this->assertEquals('Recipient', $output['ret'][0]['recipient']);
        $this->assertEquals('Message', $output['ret'][0]['message']);
        $this->assertTrue($output['ret'][0]['enable']);
        $this->assertFalse($output['ret'][0]['suspend']);
        $this->assertFalse($output['ret'][0]['deleted']);

        $this->assertEquals(2, $output['ret'][1]['id']);
        $this->assertEquals(2, $output['ret'][1]['domain']);
        $this->assertEquals(2, $output['ret'][1]['bank_info_id']);
        $this->assertEquals('9876543210', $output['ret'][1]['account']);
        $this->assertEquals(0, $output['ret'][1]['account_type']);
        $this->assertEquals(0, $output['ret'][1]['auto_remit_id']);
        $this->assertFalse($output['ret'][1]['auto_confirm']);
        $this->assertEquals('TWD', $output['ret'][1]['currency']);
        $this->assertEquals('控端提示', $output['ret'][1]['control_tips']);
        $this->assertEquals('收款人', $output['ret'][1]['recipient']);
        $this->assertEquals('會員端提示訊息', $output['ret'][1]['message']);
        $this->assertFalse($output['ret'][1]['enable']);
        $this->assertTrue($output['ret'][1]['suspend']);
        $this->assertTrue($output['ret'][1]['deleted']);

        $this->assertEquals(5, $output['ret'][4]['id']);
        $this->assertEquals(2, $output['ret'][4]['domain']);
        $this->assertEquals(1, $output['ret'][4]['bank_info_id']);
        $this->assertEquals('159753456', $output['ret'][4]['account']);
        $this->assertEquals(1, $output['ret'][4]['account_type']);
        $this->assertEquals(1, $output['ret'][4]['auto_remit_id']);
        $this->assertTrue($output['ret'][4]['auto_confirm']);
        $this->assertEquals('CNY', $output['ret'][4]['currency']);
        $this->assertEquals('控端提示', $output['ret'][4]['control_tips']);
        $this->assertEquals('收款人', $output['ret'][4]['recipient']);
        $this->assertEquals('會員端提示訊息', $output['ret'][4]['message']);
        $this->assertTrue($output['ret'][4]['enable']);
        $this->assertFalse($output['ret'][4]['suspend']);
        $this->assertFalse($output['ret'][4]['deleted']);

        $this->assertEquals(9, $output['pagination']['total']);
    }

    /**
     * 測試公司出入款帳號列表帶入參數
     */
    public function testRemitAccountListWithParameter()
    {
        $client = $this->createClient();

        // 測試帶入廳
        $parameters = [
            'domain' => 1
        ];

        $client->request('GET', '/api/remit_account/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEmpty($output['ret']);
        $this->assertEquals(0, $output['pagination']['total']);

        // 測試帶入銀行ID
        $parameters = [
            'bank_info_id' => 2
        ];

        $client->request('GET', '/api/remit_account/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertEquals(2, $output['ret'][0]['bank_info_id']);

        $this->assertEquals(1, $output['pagination']['total']);

        // 測試帶入帳號
        $parameters = [
            'account' => '987%'
        ];

        $client->request('GET', '/api/remit_account/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertEquals('9876543210', $output['ret'][0]['account']);

        $this->assertEquals(1, $output['pagination']['total']);

        // 測試帶入帳號類別
        $parameters = [
            'account_type' => 1
        ];

        $client->request('GET', '/api/remit_account/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(1, $output['ret'][0]['account_type']);

        $this->assertEquals(8, $output['pagination']['total']);

        // 測試帶入幣別
        $parameters = [
            'currency' => 'TWD'
        ];

        $client->request('GET', '/api/remit_account/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertEquals('TWD', $output['ret'][0]['currency']);

        $this->assertEquals(6, $output['pagination']['total']);

        // 測試帶入啟停用
        $parameters = [
            'enable' => 0
        ];

        $client->request('GET', '/api/remit_account/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertFalse($output['ret'][0]['enable']);

        $this->assertEquals(2, $output['pagination']['total']);

        // 測試帶入已刪除
        $parameters = [
            'deleted' => 1
        ];

        $client->request('GET', '/api/remit_account/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertTrue($output['ret'][0]['deleted']);

        $this->assertEquals(1, $output['pagination']['total']);

        // 測試帶入層級Id
        $parameters = ['level_id' => 1];

        $client->request('GET', '/api/remit_account/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(1, $output['pagination']['total']);

        // 測試帶入筆數限制
        $parameters = [
            'first_result' => 0,
            'max_results' => 1,
        ];

        $client->request('GET', '/api/remit_account/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals('1234567890', $output['ret'][0]['account']);

        $this->assertEquals(9, $output['pagination']['total']);
        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(1, $output['pagination']['max_results']);

        // 測試帶入分頁開始
        $parameters = [
            'first_result' => 1,
            'max_results' => 20,
        ];

        $client->request('GET', '/api/remit_account/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertEquals('9876543210', $output['ret'][0]['account']);

        $this->assertEquals(9, $output['pagination']['total']);
        $this->assertEquals(1, $output['pagination']['first_result']);
        $this->assertEquals(20, $output['pagination']['max_results']);
    }

    /**
     * 測試公司出入款帳號列表, 帶入錯誤幣別
     */
    public function testRemitAccountListWithInvalidCurrency()
    {
        $client = $this->createClient();

        $parameters = ['currency' => 'AAA'];

        $client->request('GET', '/api/remit_account/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550001, $output['code']);
        $this->assertEquals('Currency not support', $output['msg']);
    }

    /**
     * 測試公司出入款帳號列表, 帶入空幣別
     */
    public function testRemitAccountListWithEmptyCurrency()
    {
        $client = $this->createClient();

        $parameters = ['currency' => ''];

        $client->request('GET', '/api/remit_account/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550001, $output['code']);
        $this->assertEquals('Currency not support', $output['msg']);
    }

    /**
     * 測試公司出入款帳號列表, 帶入人工確認
     */
    public function testRemitAccountListWithManual()
    {
        $client = $this->createClient();

        $parameters = ['auto_confirm' => 0];

        $client->request('GET', '/api/remit_account/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertFalse($output['ret'][0]['auto_confirm']);
        $this->assertEquals(0, $output['ret'][0]['auto_remit_id']);

        $this->assertEquals(4, $output['ret'][3]['id']);
        $this->assertFalse($output['ret'][3]['auto_confirm']);
        $this->assertEquals(0, $output['ret'][3]['auto_remit_id']);

        $this->assertEquals(5, $output['pagination']['total']);
    }

    /**
     * 測試公司出入款帳號列表, 帶入自動確認
     */
    public function testRemitAccountListWithAutoConfirm()
    {
        $client = $this->createClient();

        $parameters = ['auto_confirm' => 1];

        $client->request('GET', '/api/remit_account/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(5, $output['ret'][0]['id']);
        $this->assertTrue($output['ret'][0]['auto_confirm']);
        $this->assertEquals(1, $output['ret'][0]['auto_remit_id']);

        $this->assertEquals(4, $output['pagination']['total']);
    }

    /**
     * 測試公司出入款帳號列表, 帶入BB自動認款
     */
    public function testRemitAccountListWithBbAutoConfirm()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $remitAccount = $em->find('BBDurianBundle:RemitAccount', 6);
        $remitAccount->setAutoRemitId(2);
        $em->persist($remitAccount);
        $em->flush();

        $client = $this->createClient();

        $parameters = ['auto_remit_id' => 2];

        $client->request('GET', '/api/remit_account/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(6, $output['ret'][0]['id']);
        $this->assertTrue($output['ret'][0]['auto_confirm']);
        $this->assertEquals(2, $output['ret'][0]['auto_remit_id']);

        $this->assertEquals(2, $output['pagination']['total']);
    }

    /**
     * 測試公司出入款帳號列表, 帶入爬蟲停啟用
     */
    public function testRemitAccountListWithCrawlerOn()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $remitAccount = $em->find('BBDurianBundle:RemitAccount', 8);
        $remitAccount->setCrawlerOn(true);
        $em->flush();

        $client = $this->createClient();

        $parameters = ['crawler_on' => 1];

        $client->request('GET', '/api/remit_account/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret'][0]['id']);
        $this->assertTrue($output['ret'][0]['crawler_on']);
        $this->assertEquals(9, $output['ret'][1]['id']);
        $this->assertTrue($output['ret'][1]['crawler_on']);

        $this->assertEquals(2, $output['pagination']['total']);
    }

    /**
     * 測試公司出入款帳號列表, 帶入爬蟲執行狀態
     */
    public function testRemitAccountListWithCrawlerRun()
    {
        $client = $this->createClient();

        $parameters = ['crawler_run' => 1];

        $client->request('GET', '/api/remit_account/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(9, $output['ret'][0]['id']);
        $this->assertTrue($output['ret'][0]['crawler_run']);

        $this->assertEquals(1, $output['pagination']['total']);
    }

    /**
     * 測試公司出入款帳號列表，帶入自動認款平台ID
     */
    public function testRemitAccountListWithAutoRemitId()
    {
        $client = $this->createClient();

        $parameters = ['auto_remit_id' => 0];

        $client->request('GET', '/api/remit_account/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(0, $output['ret'][0]['auto_remit_id']);
        $this->assertEquals(2, $output['ret'][1]['id']);
        $this->assertEquals(0, $output['ret'][1]['auto_remit_id']);
        $this->assertEquals(3, $output['ret'][2]['id']);
        $this->assertEquals(0, $output['ret'][2]['auto_remit_id']);
        $this->assertEquals(4, $output['ret'][3]['id']);
        $this->assertEquals(0, $output['ret'][3]['auto_remit_id']);

        $this->assertEquals(4, $output['pagination']['total']);
    }

    /**
     * 測試公司出入款帳號列表, 帶入爬蟲最後執行時間
     */
    public function testRemitAccountListWithCrawlerUpdate()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $remitAccount8 = $em->find('BBDurianBundle:RemitAccount', 8);
        $time = new \DateTime('2017-09-10 12:59:00');
        $remitAccount8->setCrawlerUpdate($time);

        $remitAccount9 = $em->find('BBDurianBundle:RemitAccount', 9);
        $time = new \DateTime('2017-09-10 13:13:13');
        $remitAccount9->setCrawlerUpdate($time);
        $em->flush();

        $client = $this->createClient();

        $parameters = [
            'crawler_update_start' => '2017-09-10T13:00:00+0800',
            'crawler_update_end' => '2017-09-10T13:30:00+0800',
        ];

        $client->request('GET', '/api/remit_account/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(9, $output['ret'][0]['id']);
        $this->assertEquals('2017-09-10T13:13:13+0800', $output['ret'][0]['crawler_update']);

        $this->assertEquals(1, $output['pagination']['total']);
    }

    /**
     * 測試停啟用公司出入款帳號
     */
    public function testEnableAndDisableRemitAccount()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $remitAccount = $em->find('BBDurianBundle:RemitAccount', 1);
        $remitAccount->suspend();

        $em->flush();
        $em->clear();

        $client->request('PUT', '/api/remit_account/1/disable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $remitAccount = $em->find('BBDurianBundle:RemitAccount', 1);

        $this->assertEquals('ok', $output['result']);
        $this->assertFalse($remitAccount->isEnabled());
        $this->assertFalse($remitAccount->isSuspended());

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('@enable:true=>false, @suspend:true=>false', $logOperation->getMessage());

        $em->clear();

        $client->request('PUT', '/api/remit_account/1/enable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $remitAccount = $em->find('BBDurianBundle:RemitAccount', 1);

        $this->assertEquals('ok', $output['result']);
        $this->assertTrue($remitAccount->isEnabled());

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('@enable:false=>true', $logOperation->getMessage());

        // 檢查 log 是否不存在
        $this->assertFileNotExists($this->logPath);
    }

    /**
     * 測試停啟用公司出入款帳號例外
     */
    public function testEnableAndDisableRemitAccountException()
    {
        $client = $this->createClient();

        // 測試帶入不存在的ID
        $client->request('PUT', '/api/remit_account/99/enable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550011, $output['code']);
        $this->assertEquals('No RemitAccount found', $output['msg']);

        // 測試啟用已刪除帳號
        $client->request('PUT', '/api/remit_account/2/enable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550008, $output['code']);
        $this->assertEquals('Cannot change when RemitAccount deleted', $output['msg']);

        // 測試帶入不存在的ID
        $client->request('PUT', '/api/remit_account/99/disable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550011, $output['code']);
        $this->assertEquals('No RemitAccount found', $output['msg']);

        // 測試停用已刪除帳號
        $client->request('PUT', '/api/remit_account/2/disable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550008, $output['code']);
        $this->assertEquals('Cannot change when RemitAccount deleted', $output['msg']);
    }

    /**
     * 測試啟用自動確認的公司出入款帳號
     */
    public function testEnableAutoConfirmRemitAccount()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $logger = $this->getContainer()->get('durian.remit_auto_confirm_logger');

        $doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();

        $doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($em);

        $mockContainer = $this->getMockBuilder(get_class($this->getContainer()))
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $mockContainer->expects($this->exactly(3))
            ->method('get')
            ->will($this->onConsecutiveCalls($doctrine, $doctrine, $logger));

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn('payment.https.s04.tonglueyun.com');

        // 停用自動確認的公司入款帳號
        $client = $this->createClient();
        $client->request('PUT', '/api/remit_account/6/disable');
        $client->getResponse()->getContent();

        $response = new Response();
        $response->setContent('{"balance": 4071.77, "success": true}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $remitAccount = $em->find('BBDurianBundle:RemitAccount', 6);

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $client = $this->createClient();
        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);
        $client->request('PUT', '/api/remit_account/6/enable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $em->refresh($remitAccount);

        $this->assertEquals('ok', $output['result']);
        $this->assertTrue($remitAccount->isEnabled());

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('@enable:false=>true', $logOperation->getMessage());

        // 檢查 log 是否存在
        $this->assertFileExists($this->logPath);

        // 檢查 log 內容
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $logMsg = '/\[\d{4}-\d\d-\d\d \d\d:\d\d:\d\d\] app\.INFO: .*? payment.https.s04.tonglueyun.com ' .
            '"POST \/authority\/system\/api\/query_bankcard\/" "HEADER: " "REQUEST: {"apikey":"\*\*\*\*\*\*",' .
            '"bank_flag":"ICBC","card_number":"8825252"}" ' .
            '"RESPONSE: {"balance": 4071\.77, "success": true}" [] []/';
        $this->assertRegExp($logMsg, $results[0]);
    }

    /**
     * 測試啟用BB自動確認的公司出入款帳號
     */
    public function testEnableBBAutoConfirmRemitAccount()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        // 停用自動確認的公司入款帳號
        $client = $this->createClient();
        $client->request('PUT', '/api/remit_account/9/disable');
        $client->getResponse()->getContent();

        $remitAccount = $em->find('BBDurianBundle:RemitAccount', 9);
        $remitAccount->setCrawlerOn(false);
        $em->flush();

        $client = $this->createClient();
        $client->request('PUT', '/api/remit_account/9/enable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $remitAccount = $em->find('BBDurianBundle:RemitAccount', 9);
        $em->refresh($remitAccount);

        $this->assertEquals('ok', $output['result']);
        $this->assertTrue($remitAccount->isEnabled());

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('@enable:false=>true, @crawler_on:false=>true', $logOperation->getMessage());
    }

    /**
     * 測試啟用自動認款銀行卡，會修正排序有重複的銀行卡排序
     */
    public function testEnableAutoConfirmRemitAccountWillUpdateDuplicateOrderId()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $logger = $this->getContainer()->get('durian.remit_auto_confirm_logger');

        $doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();

        $doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($em);

        $mockContainer = $this->getMockBuilder(get_class($this->getContainer()))
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $mockContainer->expects($this->exactly(3))
            ->method('get')
            ->will($this->onConsecutiveCalls($doctrine, $doctrine,$logger));

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn('payment.https.s04.tonglueyun.com');

        // 停用自動確認的公司入款帳號
        $client = $this->createClient();
        $client->request('PUT', '/api/remit_account/6/disable');
        $client->getResponse()->getContent();

        $response = new Response();
        $response->setContent('{"balance": 4071.77, "success": true}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $client = $this->createClient();
        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);
        $client->request('PUT', '/api/remit_account/6/enable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $remitAccount = $em->find('BBDurianBundle:RemitAccount', 6);

        $this->assertEquals('ok', $output['result']);
        $this->assertTrue($remitAccount->isEnabled());

        $remitAccountLevel = $em->getRepository('BBDurianBundle:RemitAccountLevel')->findOneBy([
            'levelId' => 2,
            'remitAccountId' => $remitAccount->getId(),
        ]);

        $this->assertNotEmpty($remitAccountLevel);
        $this->assertEquals(2, $remitAccountLevel->getOrderId());

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('@enable:false=>true', $logOperation->getMessage());

        // 檢查 log 是否存在
        $this->assertFileExists($this->logPath);

        // 檢查 log 內容
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $logMsg = '/\[\d{4}-\d\d-\d\d \d\d:\d\d:\d\d\] app\.INFO: .*? payment.https.s04.tonglueyun.com ' .
            '"POST \/authority\/system\/api\/query_bankcard\/" "HEADER: " "REQUEST: {"apikey":"\*\*\*\*\*\*",' .
            '"bank_flag":"ICBC","card_number":"8825252"}" ' .
            '"RESPONSE: {"balance": 4071\.77, "success": true}" [] []/';
        $this->assertRegExp($logMsg, $results[0]);
    }

    /**
     * 測試啟用自動確認的公司出入款帳號，但自動認款連線異常
     */
    public function testEnableAutoConfirmRemitAccountButAutoConfirmConnectionError()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $logger = $this->getContainer()->get('durian.remit_auto_confirm_logger');

        $doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();

        $doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($em);

        $mockContainer = $this->getMockBuilder(get_class($this->getContainer()))
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $mockContainer->expects($this->exactly(3))
            ->method('get')
            ->will($this->onConsecutiveCalls($doctrine, $doctrine, $logger));

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn('payment.https.s04.tonglueyun.com');

        // 停用自動確認的公司入款帳號
        $client = $this->createClient();
        $client->request('PUT', '/api/remit_account/6/disable');
        $client->getResponse()->getContent();

        $exception = new \Exception('Auto Confirm connection failure', 150870021);
        $this->mockClient->expects($this->any())
            ->method('send')
            ->willThrowException($exception);

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setContainer($mockContainer);

        $client = $this->createClient();
        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);
        $client->request('PUT', '/api/remit_account/6/enable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150870021, $output['code']);
        $this->assertEquals('Auto Confirm connection failure', $output['msg']);

        // 檢查帳號是否停用
        $remitAccount = $em->find('BBDurianBundle:RemitAccount', 6);
        $this->assertFalse($remitAccount->isEnabled());

        // 檢查 log 是否不存在
        $this->assertFileNotExists($this->logPath);
    }

    /**
     * 測試啟用自動確認的公司出入款帳號，但自動認款連線失敗
     */
    public function testEnableAutoConfirmRemitAccountButAutoConfirmConnectionFailure()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $logger = $this->getContainer()->get('durian.remit_auto_confirm_logger');

        $doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();

        $doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($em);

        $mockContainer = $this->getMockBuilder(get_class($this->getContainer()))
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $mockContainer->expects($this->exactly(3))
            ->method('get')
            ->will($this->onConsecutiveCalls($doctrine, $doctrine, $logger));

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn('payment.https.s04.tonglueyun.com');

        // 停用自動確認的公司入款帳號
        $client = $this->createClient();
        $client->request('PUT', '/api/remit_account/6/disable');
        $client->getResponse()->getContent();

        $response = new Response();
        $response->addHeader('HTTP/1.1 499');
        $response->addHeader('Content-Type: application/json');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $client = $this->createClient();
        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);
        $client->request('PUT', '/api/remit_account/6/enable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150870022, $output['code']);
        $this->assertEquals('Auto Confirm connection failure', $output['msg']);

        // 檢查帳號是否停用
        $remitAccount = $em->find('BBDurianBundle:RemitAccount', 6);
        $this->assertFalse($remitAccount->isEnabled());

        // 檢查 log 是否存在
        $this->assertFileExists($this->logPath);

        // 檢查 log 內容
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $logMsg = '/\[\d{4}-\d\d-\d\d \d\d:\d\d:\d\d\] app\.INFO: .*? payment.https.s04.tonglueyun.com ' .
            '"POST \/authority\/system\/api\/query_bankcard\/" "HEADER: " "REQUEST: {"apikey":"\*\*\*\*\*\*",' .
            '"bank_flag":"ICBC","card_number":"8825252"}" ' .
            '"RESPONSE: " [] []/';
        $this->assertRegExp($logMsg, $results[0]);
    }

    /**
     * 測試啟用自動確認的公司出入款帳號，但未指定自動認款返回參數
     */
    public function testEnableAutoConfirmRemitAccountButNoAutoConfirmReturnParameterSpecified()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $logger = $this->getContainer()->get('durian.remit_auto_confirm_logger');

        $doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();

        $doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($em);

        $mockContainer = $this->getMockBuilder(get_class($this->getContainer()))
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $mockContainer->expects($this->exactly(3))
            ->method('get')
            ->will($this->onConsecutiveCalls($doctrine, $doctrine, $logger));

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn('payment.https.s04.tonglueyun.com');

        // 停用自動確認的公司入款帳號
        $client = $this->createClient();
        $client->request('PUT', '/api/remit_account/6/disable');
        $client->getResponse()->getContent();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $client = $this->createClient();
        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);
        $client->request('PUT', '/api/remit_account/6/enable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150870027, $output['code']);
        $this->assertEquals('Please confirm auto_remit_account in the platform.', $output['msg']);

        // 檢查帳號是否停用
        $remitAccount = $em->find('BBDurianBundle:RemitAccount', 6);
        $this->assertFalse($remitAccount->isEnabled());

        // 檢查 log 是否存在
        $this->assertFileExists($this->logPath);

        // 檢查 log 內容
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $logMsg = '/\[\d{4}-\d\d-\d\d \d\d:\d\d:\d\d\] app\.INFO: .*? payment.https.s04.tonglueyun.com ' .
            '"POST \/authority\/system\/api\/query_bankcard\/" "HEADER: " "REQUEST: {"apikey":"\*\*\*\*\*\*",' .
            '"bank_flag":"ICBC","card_number":"8825252"}" ' .
            '"RESPONSE: " [] []/';
        $this->assertRegExp($logMsg, $results[0]);
    }

    /**
     * 測試啟用自動確認的公司出入款帳號，但銀行卡不存在
     */
    public function testEnableAutoConfirmRemitAccountButCardIsNotExisting()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $logger = $this->getContainer()->get('durian.remit_auto_confirm_logger');

        $doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();

        $doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($em);

        $mockContainer = $this->getMockBuilder(get_class($this->getContainer()))
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $mockContainer->expects($this->exactly(3))
            ->method('get')
            ->will($this->onConsecutiveCalls($doctrine, $doctrine, $logger));

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn('payment.https.s04.tonglueyun.com');

        // 停用自動確認的公司入款帳號
        $client = $this->createClient();
        $client->request('PUT', '/api/remit_account/6/disable');
        $client->getResponse()->getContent();

        $response = new Response();
        $response->setContent('{"message": "This card is not existing.", "success": false}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $client = $this->createClient();
        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);
        $client->request('PUT', '/api/remit_account/6/enable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150870024, $output['code']);
        $this->assertRegExp('/This card is not existing./', $output['msg']);

        // 檢查帳號是否停用
        $remitAccount = $em->find('BBDurianBundle:RemitAccount', 6);
        $this->assertFalse($remitAccount->isEnabled());

        // 檢查 log 是否存在
        $this->assertFileExists($this->logPath);

        // 檢查 log 內容
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $logMsg = '/This card is not existing/';
        $this->assertRegexp($logMsg, $results[0]);
    }

    /**
     * 測試啟用自動確認的公司出入款帳號，但自動認款失敗
     */
    public function testEnableAutoConfirmRemitAccountButAutoConfirmFailed()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $logger = $this->getContainer()->get('durian.remit_auto_confirm_logger');

        $doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();

        $doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($em);

        $mockContainer = $this->getMockBuilder(get_class($this->getContainer()))
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $mockContainer->expects($this->exactly(3))
            ->method('get')
            ->will($this->onConsecutiveCalls($doctrine, $doctrine, $logger));

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn('payment.https.s04.tonglueyun.com');

        // 停用自動確認的公司入款帳號
        $client = $this->createClient();
        $client->request('PUT', '/api/remit_account/6/disable');
        $client->getResponse()->getContent();

        $response = new Response();
        $response->setContent('{"balance": 4071.77, "success": false}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $client = $this->createClient();
        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);
        $client->request('PUT', '/api/remit_account/6/enable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150870025, $output['code']);
        $this->assertEquals('Auto Confirm failed', $output['msg']);

        // 檢查帳號是否停用
        $remitAccount = $em->find('BBDurianBundle:RemitAccount', 6);
        $this->assertFalse($remitAccount->isEnabled());

        // 檢查 log 是否存在
        $this->assertFileExists($this->logPath);

        // 檢查 log 內容
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $logMsg = '/\[\d{4}-\d\d-\d\d \d\d:\d\d:\d\d\] app\.INFO: .*? payment.https.s04.tonglueyun.com ' .
            '"POST \/authority\/system\/api\/query_bankcard\/" "HEADER: " "REQUEST: {"apikey":"\*\*\*\*\*\*",' .
            '"bank_flag":"ICBC","card_number":"8825252"}" ' .
            '"RESPONSE: {"balance": 4071\.77, "success": false}" [] []/';
        $this->assertRegExp($logMsg, $results[0]);
    }

    /**
     * 測試刪除及回復公司出入款帳號
     */
    public function testDeleteAndRecoverRemitAccount()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $client->request('PUT', '/api/remit_account/2/recover');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $remitAccount = $em->find('BBDurianBundle:RemitAccount', 2);

        $this->assertEquals('ok', $output['result']);
        $this->assertFalse($remitAccount->isDeleted());

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('@deleted:true=>false', $logOperation->getMessage());

        $em->clear();

        $client->request('DELETE', '/api/remit_account/2');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $remitAccount = $em->find('BBDurianBundle:RemitAccount', 2);

        $this->assertEquals('ok', $output['result']);
        $this->assertTrue($remitAccount->isDeleted());

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('@deleted:false=>true', $logOperation->getMessage());

        // 檢查金流操作紀錄
        $paymentOperationLogPath = $this->getLogfilePath('payment_operation.log');
        $this->assertFileExists($paymentOperationLogPath);

        $paymentOperationLog = explode(PHP_EOL, file_get_contents($paymentOperationLogPath));
        $this->assertContains('result=ok', $paymentOperationLog[0]);
    }

    /**
     * 測試刪除及回復公司出入款帳號例外
     */
    public function testDeleteAndRecoverRemitAccountException()
    {
        $client = $this->createClient();

        // 測試帶入不存在的ID
        $client->request('PUT', '/api/remit_account/99/recover');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550011, $output['code']);
        $this->assertEquals('No RemitAccount found', $output['msg']);

        // 測試回復已刪除帳號
        $client->request('PUT', '/api/remit_account/1/recover');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550009, $output['code']);
        $this->assertEquals('Cannot change when RemitAccount enabled', $output['msg']);

        // 測試帶入不存在的ID
        $client->request('DELETE', '/api/remit_account/99');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550011, $output['code']);
        $this->assertEquals('No RemitAccount found', $output['msg']);

        // 測試刪除啟用帳號
        $client->request('DELETE', '/api/remit_account/1');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550009, $output['code']);
        $this->assertEquals('Cannot change when RemitAccount enabled', $output['msg']);
    }

    /**
     * 測試恢復公司入款帳號
     */
    public function testResume()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $remitAccount = $em->find('BBDurianBundle:RemitAccount', 3);
        $remitAccount->suspend();

        $em->flush();

        $client->request('PUT', '/api/remit_account/3/resume');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $em->refresh($remitAccount);

        $this->assertEquals('ok', $output['result']);
        $this->assertFalse($remitAccount->isSuspended());

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('@suspend:true=>false', $logOperation->getMessage());
    }

    /**
     * 測試恢復公司入款帳號帶入不存在的 id
     */
    public function testResumeWithNonexistentId()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/remit_account/99/resume');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550011, $output['code']);
        $this->assertEquals('No RemitAccount found', $output['msg']);
    }

    /**
     * 測試恢復公司入款帳號帶入已被刪除的 id
     */
    public function testResumeWithDeletedId()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/remit_account/2/resume');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150550017, $output['code']);
        $this->assertEquals('Cannot change when RemitAccount deleted', $output['msg']);
    }

    /**
     * 測試恢復公司入款帳號帶入已被停用的 id
     */
    public function testResumeWithDisabledId()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/remit_account/4/resume');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150550018, $output['code']);
        $this->assertEquals('Cannot change when RemitAccount disabled', $output['msg']);
    }

    /**
     * 測試暫停公司入款帳號
     */
    public function testSuspend()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $client->request('PUT', '/api/remit_account/3/suspend');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $remitAccount = $em->find('BBDurianBundle:RemitAccount', 3);

        $this->assertEquals('ok', $output['result']);
        $this->assertTrue($remitAccount->isSuspended());

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('@suspend:false=>true', $logOperation->getMessage());
    }

    /**
     * 測試暫停公司入款帳號帶入不存在的 id
     */
    public function testSuspendWithNonexistentId()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/remit_account/99/suspend');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550011, $output['code']);
        $this->assertEquals('No RemitAccount found', $output['msg']);
    }

    /**
     * 測試暫停公司入款帳號帶入已被刪除的 id
     */
    public function testSuspendWithDeletedId()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/remit_account/2/suspend');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150550017, $output['code']);
        $this->assertEquals('Cannot change when RemitAccount deleted', $output['msg']);
    }

    /**
     * 測試暫停公司入款帳號帶入已被停用的 id
     */
    public function testSuspendWithDisabledId()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/remit_account/4/suspend');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150550018, $output['code']);
        $this->assertEquals('Cannot change when RemitAccount disabled', $output['msg']);
    }

    /**
     * 測試停/啟用爬蟲
     */
    public function testEnableCrawler()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $param = ['crawler_on' => 1];
        $client->request('PUT', '/api/remit_account/1/crawler', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remit_account', $logOperation->getTableName());
        $this->assertEquals('@id:1', $logOperation->getMajorKey());
        $this->assertEquals('@crawler_on:false=>true', $logOperation->getMessage());

        $param['crawler_on'] = 0;
        $client->request('PUT', '/api/remit_account/1/crawler', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('remit_account', $logOperation->getTableName());
        $this->assertEquals('@id:1', $logOperation->getMajorKey());
        $this->assertEquals('@crawler_on:true=>false', $logOperation->getMessage());
    }

    /**
     * 測試啟用爬蟲但入款帳號已刪除
     */
    public function testEnableCrawlerButRemitAccountDeleted()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $remitAccount = $em->find('BBDurianBundle:RemitAccount', 1);
        $remitAccount->delete();
        $em->persist($remitAccount);
        $em->flush();

        $param = ['crawler_on' => 1];
        $client->request('PUT', '/api/remit_account/1/crawler', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550008, $output['code']);
        $this->assertEquals('Cannot change when RemitAccount deleted', $output['msg']);
    }

    /**
     * 測試設定爬蟲執行狀態
     */
    public function testSetCrawlerRun()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $param = ['crawler_run' => 0];
        $client->request('PUT', '/api/remit_account/9/crawler_run', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remit_account', $logOperation->getTableName());
        $this->assertEquals('@id:9', $logOperation->getMajorKey());
        $this->assertEquals('@crawler_run:true=>false', $logOperation->getMessage());

        $param = ['crawler_run' => 1];
        $client->request('PUT', '/api/remit_account/9/crawler_run', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('remit_account', $logOperation->getTableName());
        $this->assertEquals('@id:9', $logOperation->getMajorKey());
        $this->assertEquals('@crawler_run:false=>true', $logOperation->getMessage());
    }

    /**
     * 測試第一次設定爬蟲執行狀態為執行中，爬蟲最後執行時間為null
     */
    public function testFirstTimeSetCrawlerRunWhenCrawlerUpdateIsNull()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $remitAccount = $em->find('BBDurianBundle:RemitAccount', 9);
        $remitAccount->setCrawlerRun(false);
        $remitAccount->setCrawlerUpdate(null);
        $em->flush();

        $param = ['crawler_run' => 1];
        $client->request('PUT', '/api/remit_account/9/crawler_run', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remit_account', $logOperation->getTableName());
        $this->assertEquals('@id:9', $logOperation->getMajorKey());

        $pattern = "/@crawler_run:false=>true, @crawler_update:=>\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/";
        $this->assertRegExp($pattern, $logOperation->getMessage());
    }

    /**
     * 測試設定爬蟲執行狀態為執行中但入款帳號已刪除
     */
    public function testSetCrawlerRunButRemitAccountDeleted()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $remitAccount = $em->find('BBDurianBundle:RemitAccount', 1);
        $remitAccount->delete();
        $em->flush();

        $param = ['crawler_run' => 1];
        $client->request('PUT', '/api/remit_account/1/crawler_run', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550008, $output['code']);
        $this->assertEquals('Cannot change when RemitAccount deleted', $output['msg']);
    }

    /**
     * 測試設定爬蟲狀態為執行中，但公司入款帳號非BB自動認款帳號
     */
    public function testSetCrawlerRunButNotBbAutoConfirm()
    {
        $client = $this->createClient();

        $param = ['crawler_run' => 1];
        $client->request('PUT', '/api/remit_account/1/crawler_run', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150550019, $output['code']);
        $this->assertEquals('RemitAccount is not BB Auto Confirm', $output['msg']);
    }

    /**
     * 測試設定爬蟲狀態為執行中，但爬蟲為停用
     */
    public function testSetCrawlerRunButCrawlerOnIsDisable()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $remitAccount = $em->find('BBDurianBundle:RemitAccount', 9);
        $remitAccount->setCrawlerOn(false);
        $em->flush();

        $param = ['crawler_run' => 1];
        $client->request('PUT', '/api/remit_account/9/crawler_run', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150550020, $output['code']);
        $this->assertEquals('Crawler is not enable', $output['msg']);
    }

    /**
     * 測試設定爬蟲執行狀態為執行中，但原狀態已為執行中
     */
    public function testSetCrawlerRunButCrawlerIsBeingExecuted()
    {
        $client = $this->createClient();

        $param = ['crawler_run' => 1];
        $client->request('PUT', '/api/remit_account/9/crawler_run', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150550021, $output['code']);
        $this->assertEquals('Crawler is being executed', $output['msg']);
    }

    /**
     * 測試解除網銀密碼錯誤
     */
    public function testUnlockPasswordError()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $remitAccount = $em->find('BBDurianBundle:RemitAccount', 1);
        $remitAccount->setPasswordError(true);
        $em->persist($remitAccount);
        $em->flush();

        $client->request('PUT', '/api/remit_account/1/unlock/password_error');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remit_account', $logOperation->getTableName());
        $this->assertEquals('@id:1', $logOperation->getMajorKey());
        $this->assertEquals('@password_error:true=>false', $logOperation->getMessage());
    }

    /**
     * 測試解除網銀密碼錯誤但入款帳號已刪除
     */
    public function testUnlockPasswordErrorButRemitAccountDeleted()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $remitAccount = $em->find('BBDurianBundle:RemitAccount', 1);
        $remitAccount->delete();
        $em->persist($remitAccount);
        $em->flush();

        $client->request('PUT', '/api/remit_account/1/unlock/password_error');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550008, $output['code']);
        $this->assertEquals('Cannot change when RemitAccount deleted', $output['msg']);
    }

    /**
     * 測試修改公司出入款帳號
     */
    public function testEditRemitAccount()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        // 測試修改廳
        $parameters = [
            'domain' => 1
        ];

        $client->request('PUT', '/api/remit_account/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertEquals(1, $output['ret']['domain']);

        // 檢查金流操作紀錄
        $paymentOperationLogPath = $this->getLogfilePath('payment_operation.log');
        $this->assertFileExists($paymentOperationLogPath);

        $paymentOperationLog = explode(PHP_EOL, file_get_contents($paymentOperationLogPath));
        $this->assertContains('result=ok', $paymentOperationLog[0]);

        // 測試修改銀行
        $parameters = [
            'bank_info_id' => 2
        ];

        $client->request('PUT', '/api/remit_account/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertEquals(2, $output['ret']['bank_info_id']);

        // 測試修改帳號
        $parameters = [
            'account' => 'a1234567890'
        ];

        $client->request('PUT', '/api/remit_account/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertEquals('a1234567890', $output['ret']['account']);

        // 測試修改帳號類別
        $parameters = [
            'account_type' => 0
        ];

        $client->request('PUT', '/api/remit_account/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertEquals(0, $output['ret']['account_type']);

        // 測試修改幣別
        $parameters = [
            'currency' => 'USD'
        ];

        $client->request('PUT', '/api/remit_account/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertEquals('USD', $output['ret']['currency']);

        // 測試修改控端提示
        $parameters = [
            'control_tips' => 'New Tips'
        ];

        $client->request('PUT', '/api/remit_account/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertEquals('New Tips', $output['ret']['control_tips']);

        // 測試修改收款人
        $parameters = [
            'recipient' => 'Who'
        ];

        $client->request('PUT', '/api/remit_account/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertEquals('Who', $output['ret']['recipient']);

        // 測試修改會員端提示訊息
        $parameters = [
            'message' => 'message'
        ];

        $client->request('PUT', '/api/remit_account/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertEquals('message', $output['ret']['message']);

        // 操作紀錄檢查
        $logOperation = $em->find('BBDurianBundle:LogOperation', 8);
        $this->assertEquals('remit_account', $logOperation->getTableName());
        $this->assertEquals('@id:1', $logOperation->getMajorKey());
        $this->assertEquals('@message:Message=>message', $logOperation->getMessage());
    }

    /**
     * 測試修改公司出入款帳號例外
     */
    public function testEditRemitAccountException()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 測試帶入空白廳
        $parameters = [
            'domain' => ''
        ];

        $client->request('PUT', '/api/remit_account/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550014, $output['code']);
        $this->assertEquals('No domain specified', $output['msg']);

        // 測試帶入銀行ID
        $parameters = [
            'bank_info_id' => ''
        ];

        $client->request('PUT', '/api/remit_account/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550003, $output['code']);
        $this->assertEquals('Invalid bank_info_id', $output['msg']);

        // 測試帶入不存在的銀行ID
        $parameters = [
            'bank_info_id' => 99
        ];

        $client->request('PUT', '/api/remit_account/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550010, $output['code']);
        $this->assertEquals('No BankInfo found', $output['msg']);

        // 測試帶入空白帳號
        $parameters = [
            'account' => ''
        ];

        $client->request('PUT', '/api/remit_account/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550005, $output['code']);
        $this->assertEquals('No account specified', $output['msg']);

        // 測試帶入空白幣別
        $parameters = [
            'currency' => ''
        ];

        $client->request('PUT', '/api/remit_account/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550002, $output['code']);
        $this->assertEquals('Illegal currency', $output['msg']);

        // 測試帶入空白控端提示
        $parameters = [
            'control_tips' => ''
        ];

        $client->request('PUT', '/api/remit_account/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550007, $output['code']);
        $this->assertEquals('No control tips specified', $output['msg']);

        // 測試修改重複帳號
        $parameters = [
            'bank_info_id' => 1,
            'account_type' => 1,
            'account' => '1234567890'
        ];

        $client->request('PUT', '/api/remit_account/2', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550012, $output['code']);
        $this->assertEquals('This account already been used', $output['msg']);

        // 測試找不到自動認款平台
        $parameters = ['auto_remit_id' => 999];

        $client->request('PUT', '/api/remit_account/6', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150550023, $output['code']);
        $this->assertEquals('No AutoRemit found', $output['msg']);
    }

    /**
     * 測試修改公司出入款帳號為自動確認
     */
    public function testEditRemitAccountWithAutoConfirm()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $logger = $this->getContainer()->get('durian.remit_auto_confirm_logger');

        $client = $this->createClient();

        $doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();

        $doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($em);

        $mockContainer = $this->getMockBuilder(get_class($this->getContainer()))
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $mockContainer->expects($this->exactly(3))
            ->method('get')
            ->will($this->onConsecutiveCalls($doctrine, $doctrine, $logger));

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn('payment.https.s04.tonglueyun.com');

        $response = new Response();
        $response->setContent('{"balance": 4071.77, "success": true}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);

        $parameters = [
            'auto_confirm' => 1,
            'auto_remit_id' => 1,
        ];

        $client->request('PUT', '/api/remit_account/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertTrue($output['ret']['auto_confirm']);
        $this->assertEquals(1, $output['ret']['auto_remit_id']);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remit_account', $logOperation->getTableName());
        $this->assertEquals('@id:1', $logOperation->getMajorKey());
        $this->assertEquals('@auto_remit_id:0=>1, @auto_confirm:false=>true', $logOperation->getMessage());

        // 檢查 log 是否存在
        $this->assertFileExists($this->logPath);

        // 檢查 log 內容
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $logMsg = '/\[\d{4}-\d\d-\d\d \d\d:\d\d:\d\d\] app\.INFO: .*? payment.https.s04.tonglueyun.com ' .
            '"POST \/authority\/system\/api\/query_bankcard\/" "HEADER: " "REQUEST: {"apikey":"\*\*\*\*\*\*",' .
            '"bank_flag":"ICBC","card_number":"1234567890"}" ' .
            '"RESPONSE: {"balance": 4071\.77, "success": true}" [] []/';
        $this->assertRegExp($logMsg, $results[0]);
    }

    /**
     * 測試修改公司出入款帳號為BB自動認款
     */
    public function testEditRemitAccountWithBbAutoConfirm()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $logger = $this->getContainer()->get('durian.remit_auto_confirm_logger');

        $client = $this->createClient();

        $parameters = [
            'auto_confirm' => 1,
            'auto_remit_id' => 2,
        ];

        $client->request('PUT', '/api/remit_account/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertTrue($output['ret']['auto_confirm']);
        $this->assertEquals(2, $output['ret']['auto_remit_id']);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remit_account', $logOperation->getTableName());
        $this->assertEquals('@id:1', $logOperation->getMajorKey());
        $this->assertEquals('@auto_remit_id:0=>2, @auto_confirm:false=>true', $logOperation->getMessage());
    }

    /**
     * 測試修改網銀登入帳號密碼
     */
    public function testEditRemitAccountWithWebBank()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $client = $this->createClient();

        $parameters = ['web_bank_account' => 'webAccount', 'web_bank_password' => 'webPassword'];

        $client->request('PUT', '/api/remit_account/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertEquals('webAccount', $output['ret']['web_bank_account']);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remit_account', $logOperation->getTableName());
        $this->assertEquals('@id:1', $logOperation->getMajorKey());
        $this->assertEquals('@web_bank_account:=>webAccount, @web_bank_password:*****', $logOperation->getMessage());
    }

    /**
     * 測試修改限額
     */
    public function testEditRemitAccountWithBankLimit()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $logger = $this->getContainer()->get('durian.remit_auto_confirm_logger');

        $client = $this->createClient();

        $parameters = ['bank_limit' => 1000.00];

        $client->request('PUT', '/api/remit_account/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertEquals(1000.0000, $output['ret']['bank_limit']);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remit_account', $logOperation->getTableName());
        $this->assertEquals('@id:1', $logOperation->getMajorKey());
        $this->assertEquals('@bank_limit:0=>1000', $logOperation->getMessage());
    }

    /**
     * 測試修改自動確認公司出入款帳號的銀行ID，但銀行不支援自動確認
     */
    public function testEditRemitAccountWithBankInfoButBankInfoNotSupportedByAutoRemitBankInfo()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();

        $doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($em);

        $mockContainer = $this->getMockBuilder(get_class($this->getContainer()))
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $mockContainer->expects($this->exactly(2))
            ->method('get')
            ->will($this->onConsecutiveCalls($doctrine, $doctrine));

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn('payment.https.s04.tonglueyun.com');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setContainer($mockContainer);

        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);

        $parameters = ['bank_info_id' => 2];
        $client->request('PUT', '/api/remit_account/7', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150870015, $output['code']);
        $this->assertEquals('BankInfo is not supported by AutoRemitBankInfo', $output['msg']);
    }

    /**
     * 測試修改公司出入款帳號為自動確認，但銀行不支援自動確認
     */
    public function testEditRemitAccountWithAutoConfirmButBankInfoNotSupportedByAutoRemitBankInfo()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();

        $doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($em);

        $mockContainer = $this->getMockBuilder(get_class($this->getContainer()))
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $mockContainer->expects($this->exactly(2))
            ->method('get')
            ->will($this->onConsecutiveCalls($doctrine, $doctrine));

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn('payment.https.s04.tonglueyun.com');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setContainer($mockContainer);

        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);

        $parameters = [
            'auto_confirm' => 1,
            'auto_remit_id' => 1,
        ];
        $client->request('PUT', '/api/remit_account/4', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150870015, $output['code']);
        $this->assertEquals('BankInfo is not supported by AutoRemitBankInfo', $output['msg']);
    }

    /**
     * 測試修改公司出入款帳號為自動確認，但自動認款連線異常
     */
    public function testEditRemitAccountWithAutoConfirmButAutoConfirmConnectionError()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $exception = new \Exception('Auto Confirm connection failure', 150870021);
        $this->mockClient->expects($this->any())
            ->method('send')
            ->willThrowException($exception);

        $doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();

        $doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($em);

        $mockContainer = $this->getMockBuilder(get_class($this->getContainer()))
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $mockContainer->expects($this->any())
            ->method('get')
            ->willReturn($doctrine);

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn('payment.https.s04.tonglueyun.com');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setContainer($mockContainer);

        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);

        $parameters = [
            'auto_confirm' => 1,
            'auto_remit_id' => 1,
        ];

        $client->request('PUT', '/api/remit_account/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150870021, $output['code']);
        $this->assertEquals('Auto Confirm connection failure', $output['msg']);

        // 檢查 log 是否不存在
        $this->assertFileNotExists($this->logPath);
    }

    /**
     * 測試修改公司出入款帳號為自動確認，但自動認款連線失敗
     */
    public function testEditRemitAccountWithAutoConfirmButAutoConfirmConnectionFailure()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $logger = $this->getContainer()->get('durian.remit_auto_confirm_logger');

        $client = $this->createClient();

        $doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();

        $doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($em);

        $mockContainer = $this->getMockBuilder(get_class($this->getContainer()))
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $mockContainer->expects($this->exactly(3))
            ->method('get')
            ->will($this->onConsecutiveCalls($doctrine, $doctrine, $logger));

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn('payment.https.s04.tonglueyun.com');

        $response = new Response();
        $response->addHeader('HTTP/1.1 499');
        $response->addHeader('Content-Type: application/json');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);

        $parameters = [
            'auto_confirm' => 1,
            'auto_remit_id' => 1,
        ];

        $client->request('PUT', '/api/remit_account/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150870022, $output['code']);
        $this->assertEquals('Auto Confirm connection failure', $output['msg']);

        // 檢查 log 是否存在
        $this->assertFileExists($this->logPath);

        // 檢查 log 內容
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $logMsg = '/\[\d{4}-\d\d-\d\d \d\d:\d\d:\d\d\] app\.INFO: .*? payment.https.s04.tonglueyun.com ' .
            '"POST \/authority\/system\/api\/query_bankcard\/" "HEADER: " "REQUEST: {"apikey":"\*\*\*\*\*\*",' .
            '"bank_flag":"ICBC","card_number":"1234567890"}" ' .
            '"RESPONSE: " [] []/';
        $this->assertRegExp($logMsg, $results[0]);
    }

    /**
     * 測試修改公司出入款帳號為自動確認，但未指定自動認款返回參數
     */
    public function testEditRemitAccountWithAutoConfirmButNoAutoConfirmReturnParameterSpecified()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $logger = $this->getContainer()->get('durian.remit_auto_confirm_logger');

        $client = $this->createClient();

        $doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();

        $doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($em);

        $mockContainer = $this->getMockBuilder(get_class($this->getContainer()))
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $mockContainer->expects($this->exactly(3))
            ->method('get')
            ->will($this->onConsecutiveCalls($doctrine, $doctrine, $logger));

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn('payment.https.s04.tonglueyun.com');

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);

        $parameters = [
            'auto_confirm' => 1,
            'auto_remit_id' => 1,
        ];
        $client->request('PUT', '/api/remit_account/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150870027, $output['code']);
        $this->assertEquals('Please confirm auto_remit_account in the platform.', $output['msg']);

        // 檢查 log 是否存在
        $this->assertFileExists($this->logPath);

        // 檢查 log 內容
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $logMsg = '/\[\d{4}-\d\d-\d\d \d\d:\d\d:\d\d\] app\.INFO: .*? payment.https.s04.tonglueyun.com ' .
            '"POST \/authority\/system\/api\/query_bankcard\/" "HEADER: " "REQUEST: {"apikey":"\*\*\*\*\*\*",' .
            '"bank_flag":"ICBC","card_number":"1234567890"}" ' .
            '"RESPONSE: " [] []/';
        $this->assertRegExp($logMsg, $results[0]);
    }

    /**
     * 測試修改公司出入款帳號為自動確認，但銀行卡不存在
     */
    public function testEditRemitAccountWithAutoConfirmButCardIsNotExisting()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $logger = $this->getContainer()->get('durian.remit_auto_confirm_logger');

        $client = $this->createClient();

        $doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();

        $doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($em);

        $mockContainer = $this->getMockBuilder(get_class($this->getContainer()))
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $mockContainer->expects($this->exactly(3))
            ->method('get')
            ->will($this->onConsecutiveCalls($doctrine, $doctrine, $logger));

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn('payment.https.s04.tonglueyun.com');

        $response = new Response();
        $response->setContent('{"message": "This card is not existing.", "success": false}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);

        $parameters = [
            'auto_confirm' => 1,
            'auto_remit_id' => 1,
        ];
        $client->request('PUT', '/api/remit_account/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150870024, $output['code']);
        $this->assertRegExp('/This card is not existing./', $output['msg']);

        // 檢查 log 是否存在
        $this->assertFileExists($this->logPath);

        // 檢查 log 內容
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $logMsg = '/This card is not existing/';
        $this->assertRegexp($logMsg, $results[0]);
    }

    /**
     * 測試修改公司出入款帳號為自動確認，但自動認款失敗
     */
    public function testEditRemitAccountWithAutoConfirmButAutoConfirmFailed()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $logger = $this->getContainer()->get('durian.remit_auto_confirm_logger');

        $client = $this->createClient();

        $doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();

        $doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($em);

        $mockContainer = $this->getMockBuilder(get_class($this->getContainer()))
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $mockContainer->expects($this->exactly(3))
            ->method('get')
            ->will($this->onConsecutiveCalls($doctrine, $doctrine, $logger));

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn('payment.https.s04.tonglueyun.com');

        $response = new Response();
        $response->setContent('{"balance": 4071.77, "success": false}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);

        $parameters = [
            'auto_confirm' => 1,
            'auto_remit_id' => 1,
        ];
        $client->request('PUT', '/api/remit_account/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150870025, $output['code']);
        $this->assertEquals('Auto Confirm failed', $output['msg']);

        // 檢查 log 是否存在
        $this->assertFileExists($this->logPath);

        // 檢查 log 內容
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $logMsg = '/\[\d{4}-\d\d-\d\d \d\d:\d\d:\d\d\] app\.INFO: .*? payment.https.s04.tonglueyun.com ' .
            '"POST \/authority\/system\/api\/query_bankcard\/" "HEADER: " "REQUEST: {"apikey":"\*\*\*\*\*\*",' .
            '"bank_flag":"ICBC","card_number":"1234567890"}" ' .
            '"RESPONSE: {"balance": 4071\.77, "success": false}" [] []/';
        $this->assertRegExp($logMsg, $results[0]);
    }

    /**
     * 測試修改公司出入款帳號層級
     */
    public function testEditRemitAccountLevel()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        // 測試修改層級
        $parameters = [
            'level_id' => [1, 3]
        ];

        $client->request('PUT', '/api/remit_account/1/level', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]);
        $this->assertEquals(3, $output['ret'][1]);

        // 操作紀錄檢查
        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remit_account_level', $logOperation->getTableName());
        $this->assertEquals('@remit_account_id:1', $logOperation->getMajorKey());
        $this->assertEquals('@level_id:1, 2, 5=>1, 3', $logOperation->getMessage());
    }

    /**
     * 測試修改公司出入款帳號層級時，帶入不存在的層級Id
     */
    public function testEditRemitAccountLevelButLevelNotFound()
    {
        $client = $this->createClient();

        $parameters = [
            'level_id' => [99]
        ];

        $client->request('PUT', '/api/remit_account/1/level', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550004, $output['code']);
        $this->assertEquals('No Level found', $output['msg']);
    }

    /**
     * 測試設定出入款帳號層級，移除時找不到原本的層級
     */
    public function testSetRemitAccountLevelCannotFoundLevelWhenRemove()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $remitAccount = $em->find('BBDurianBundle:RemitAccount', 1);

        $levels = $em->getRepository('BBDurianBundle:RemitAccountLevel')
            ->findBy(['remitAccountId' => 1]);

        $repo= $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy', 'findBy'])
            ->getMock();

        $repo->expects($this->at(0))
            ->method('findBy')
            ->willReturn($levels);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods([
                'beginTransaction',
                'find',
                'getRepository',
                'persist',
                'remove',
                'flush',
                'rollback',
                'clear'
            ])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($remitAccount);

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($repo);

        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);

        $params = [
            'level_id' => [1, 5]
        ];
        $client->request('PUT', '/api/remit_account/1/level', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550013, $output['code']);
        $this->assertEquals('No RemitAccountLevel found', $output['msg']);
    }

    /**
     * 測試同分秒設定出入款帳號層級
     */
    public function testSetRemitAccountLevelWithDuplicatedEntry()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $remitAccount = $em->find('BBDurianBundle:RemitAccount', 1);

        $levels = $em->getRepository('BBDurianBundle:RemitAccountLevel')
            ->findBy(['remitAccountId' => 1]);

        $ralRepo = $this->getMockBuilder('\BB\DurianBundle\Repository\RemitAccountLevelRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy', 'findBy', 'getDefaultOrder'])
            ->getMock();

        $ralRepo->expects($this->at(0))
            ->method('findBy')
            ->willReturn($levels);

        $ralRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn($levels);

        $ralRepo->expects($this->any())
            ->method('getDefaultOrder')
            ->willReturn(1);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods([
                'beginTransaction',
                'find',
                'getRepository',
                'persist',
                'remove',
                'flush',
                'rollback',
                'clear'
            ])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($remitAccount);

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($ralRepo);

        $pdoExcep = new \PDOException('Duplicate', 1062);
        $pdoExcep->errorInfo[1] = 1062;
        $exception = new \Exception('An exception occurred while executing', 0, $pdoExcep);

        $mockEm->expects($this->any())
            ->method('flush')
            ->willThrowException($exception);

        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);

        $params = [
            'level_id' => [2, 10]
        ];

        $client->request('PUT', '/api/remit_account/1/level', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550015, $output['code']);
        $this->assertEquals('Database is busy', $output['msg']);
    }

    /**
     * 測試取得公司出入款帳號層級
     */
    public function testGetRemitAccountLevel()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/remit_account/1/level');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]);
        $this->assertEquals(2, $output['ret'][1]);
        $this->assertEquals(5, $output['ret'][2]);
    }

    /**
     * 測試取得公司出入款帳號層級例外
     */
    public function testGetRemitAccountLevelException()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/remit_account/99/level');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550011, $output['code']);
        $this->assertEquals('No RemitAccount found', $output['msg']);
    }

    /**
     * 測試設定出入款帳號Qrcode未帶入qrcode
     */
    public function testSetRemitAccountQrcodeWithNoQrcodeSpecified()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/remit_account/1/qrcode');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550016, $output['code']);
        $this->assertEquals('No qrcode specified', $output['msg']);
    }

    /**
     * 測試設定出入款帳號Qrcode帶入不存在帳號
     */
    public function testSetRemitAccountQrcodeWithNoRemitAccountfound()
    {
        $client = $this->createClient();

        $qrcode = 'testtesttesttesttesttest';

        $client->request('PUT', '/api/remit_account/100/qrcode', ['qrcode' => $qrcode]);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550011, $output['code']);
        $this->assertEquals('No RemitAccount found', $output['msg']);
    }

    /**
     * 測試設定出入款帳號Qrcode設定不存在
     */
    public function testSetRemitAccountQrcodeWithNewRemitAccountQrcode()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $qrcode = 'testtesttesttesttesttest';

        $client->request('PUT', '/api/remit_account/2/qrcode', ['qrcode' => $qrcode]);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($qrcode, $output['ret']);

        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remit_account_qrcode', $logOperation->getTableName());
        $this->assertEquals('@remit_account_id:2', $logOperation->getMajorKey());
        $this->assertEquals('@qrcode:new', $logOperation->getMessage());
    }

    /**
     * 測試設定出入款帳號Qrcode
     */
    public function testSetRemitAccountQrcode()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $qrcode = 'testtesttesttesttesttest';

        $client->request('PUT', '/api/remit_account/1/qrcode', ['qrcode' => $qrcode]);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($qrcode, $output['ret']);

        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remit_account_qrcode', $logOperation->getTableName());
        $this->assertEquals('@remit_account_id:1', $logOperation->getMajorKey());
        $this->assertEquals('@qrcode:update', $logOperation->getMessage());
    }

    /**
     * 測試取得出入款帳號Qrcode帶入不存在帳號
     */
    public function testGetRemitAccountQrcodeWithoutRemitAccount()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/remit_account/100/qrcode');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(550011, $output['code']);
        $this->assertEquals('No RemitAccount found', $output['msg']);
    }

    /**
     * 測試取得出入款帳號Qrcode不存在
     */
    public function testGetRemitAccountQrcodeWithoutQrcode()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/remit_account/3/qrcode');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('', $output['ret']);
    }

    /**
     * 測試取得出入款帳號Qrcode
     */
    public function testGetRemitAccountQrcode()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/remit_account/1/qrcode');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('testtest', $output['ret']);
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
