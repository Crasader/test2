<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Entity\RemitLevelOrder;
use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\RemitEntry;
use BB\DurianBundle\Consumer\Poper;
use Buzz\Message\Response;

class RemitFunctionalTest extends WebTestCase
{
    /**
     * @var \Buzz\Client\Curl
     */
    private $mockClient;

    /**
     * 自動確認對外連線 log
     *
     * @var string
     */
    private $logPath;

    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashDataTwo',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankInfoData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemitAccountData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemitAccountLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemitEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentChargeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDepositCompanyData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadTranscribeEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserRemitDiscountData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemitAutoConfirmData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserPaywayData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemitAccountStatData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemitLevelOrderData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadAutoRemitData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainAutoRemitData',
        ];

        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashEntryDataForRemit'
        ];

        $this->loadFixtures($classnames, 'entry');

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadExchangeData'
        ];

        $this->loadFixtures($classnames, 'share');

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $redis->flushdb();

        $redis = $this->getContainer()->get('snc_redis.sequence');

        $redis->set('cash_seq', 1);

        $this->mockClient = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $logDir = $this->getContainer()->getParameter('kernel.logs_dir') . DIRECTORY_SEPARATOR . 'test';
        $this->logPath = $logDir . DIRECTORY_SEPARATOR . 'remit_auto_confirm.log';
    }

    /**
     * 測試取得訂單號
     */
    public function testGetOrderNumber()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/remit/entry/order_number');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(16, strlen($output['ret']));
    }

    /**
     * 測試新增入款記錄
     */
    public function testRemit()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $client->request('GET', '/api/remit/entry/order_number');
        $orderNumberOutPut = json_decode($client->getResponse()->getContent(), true);
        $orderNumber = $orderNumberOutPut['ret'];

        $parameters = [
            'order_number' => $orderNumber,
            'ancestor_id' => 3,
            'account_id' => 1,
            'bank_info_id' => 2,
            'amount' => 51,
            'method' => 4,
            'name_real' => '戰鬥民族',
            'other_discount' => '100',
            'branch' => '天下第一舞蹈會',
            'cellphone' => '100212551121',
            'trade_number' => '0001564516464',
            'payer_card' => '154121645113265',
            'transfer_code' => '100100',
            'atm_terminal_code' => '1021',
            'memo' => '撿到一百塊',
            'identity_card' => '410305197611070144',
            'deposit_at' => '2013-10-10T12:00:00+0800',
        ];
        $client->request('POST', '/api/user/8/remit', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $entryId = $output['ret']['id'];
        $remitEntry = $em->find('BBDurianBundle:RemitEntry', $entryId);

        $this->assertEquals($remitEntry->getRemitAccountId(), $output['ret']['remit_account_id']);
        $this->assertEquals($remitEntry->getDomain(), $output['ret']['domain']);
        $this->assertEquals($remitEntry->getOrderNumber(), $output['ret']['order_number']);
        $this->assertEquals($remitEntry->getOldOrderNumber(), $output['ret']['old_order_number']);
        $this->assertEquals($remitEntry->getUserId(), $output['ret']['user_id']);
        $this->assertEquals($remitEntry->getLevelId(), $output['ret']['level_id']);
        $this->assertEquals($remitEntry->getAncestorId(), $output['ret']['ancestor_id']);
        $this->assertEquals($remitEntry->getBankInfoId(), $output['ret']['bank_info_id']);
        $this->assertEquals($remitEntry->getNameReal(), $output['ret']['name_real']);
        $this->assertEquals($remitEntry->getMethod(), $output['ret']['method']);
        $this->assertEquals($remitEntry->getBranch(), $output['ret']['branch']);
        $this->assertEquals($remitEntry->getAmount(), $output['ret']['amount']);
        $this->assertEquals($remitEntry->getDiscount(), $output['ret']['discount']);
        $this->assertEquals($remitEntry->getOtherDiscount(), $output['ret']['other_discount']);
        $this->assertEquals($remitEntry->getAutoRemitId(), $output['ret']['auto_remit_id']);
        $this->assertFalse($output['ret']['abandon_discount']);
        $this->assertFalse($output['ret']['auto_confirm']);
        $this->assertEquals($remitEntry->getCellphone(), $output['ret']['cellphone']);
        $this->assertEquals($remitEntry->getTradeNumber(), $output['ret']['trade_number']);
        $this->assertEquals($remitEntry->getPayerCard(), $output['ret']['payer_card']);
        $this->assertEquals($remitEntry->getTransferCode(), $output['ret']['transfer_code']);
        $this->assertEquals($remitEntry->getAtmTerminalCode(), $output['ret']['atm_terminal_code']);
        $this->assertEquals($remitEntry->getMemo(), $output['ret']['memo']);
        $this->assertEquals($remitEntry->getIdentityCard(), $output['ret']['identity_card']);
        $this->assertEquals($remitEntry->getStatus(), $output['ret']['status']);
        $this->assertEquals($remitEntry->getOperator(), $output['ret']['operator']);
        $this->assertEquals($remitEntry->getRate(), $output['ret']['rate']);
        $this->assertEquals($remitEntry->getCreatedAt()->format(\DateTime::ISO8601), $output['ret']['created_at']);
        $this->assertEquals($remitEntry->getDepositAt()->format(\DateTime::ISO8601), $output['ret']['deposit_at']);
        $this->assertNull($output['ret']['confirm_at']);
        $this->assertEquals($remitEntry->getDuration(), $output['ret']['duration']);

        // 檢查優惠金額
        $paymentCharge = $em->find('BBDurianBundle:PaymentCharge', 6);
        $depositCompany = $paymentCharge->getDepositCompany();
        // 因為入款金額未到達存款優惠標準，故無存款優惠
        $this->assertEquals(0, $output['ret']['discount']);
        // 因為入款金額 * 其他優惠比例 > 其他優惠上限，故額度只到其他優惠上限
        $this->assertEquals($depositCompany->getOtherDiscountLimit(), $output['ret']['other_discount']);

        // 檢查訂單號已被使用
        $remitOrder = $em->find('BBDurianBundle:RemitOrder', $orderNumber);
        $this->assertTrue($remitOrder->isUsed());

        // 檢查LogOperation
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remit_entry', $logOp->getTableName());
        $this->assertEquals("@id:$entryId", $logOp->getMajorKey());

        $message = [
            '@remit_account_id:'.$output['ret']['remit_account_id'],
            '@domain:'.$output['ret']['domain'],
            '@order_number:'.$output['ret']['order_number'],
            '@ancestor_id:'.$output['ret']['ancestor_id'],
            '@user_id:'.$output['ret']['user_id'],
            "@level_id:".$output['ret']['level_id'],
            '@bank_info_id:'.$output['ret']['bank_info_id'],
            "@name_real:".$output['ret']['name_real'],
            "@method:".$output['ret']['method'],
            "@amount:".$output['ret']['amount'],
            '@discount:'.$output['ret']['discount'],
            '@other_discount:'.$output['ret']['other_discount'],
            '@abandon_discount:false',
            '@auto_confirm:false',
            "@deposit_at:".$remitEntry->getDepositAt()->format('YmdHis'),
            "@branch:".$output['ret']['branch'],
            "@cellphone:".$output['ret']['cellphone'],
            "@trade_number:".$output['ret']['trade_number'],
            "@payer_card:".$output['ret']['payer_card'],
            "@transfer_code:".$output['ret']['transfer_code'],
            "@atm_terminal_code:".$output['ret']['atm_terminal_code'],
            "@memo:".$output['ret']['memo'],
            "@identity_card:".$output['ret']['identity_card'],
        ];
        $this->assertEquals(implode(', ', $message), $logOp->getMessage());
    }

    /**
     * 測試新增自動入款記錄
     */
    public function testAutoConfirmRemit()
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

        $client->request('GET', '/api/remit/entry/order_number');
        $numberOutPut = json_decode($client->getResponse()->getContent(), true);
        $orderNumber = $numberOutPut['ret'];

        $response = new Response();
        $response->setContent('{"id": 7025875, "success": true}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $remitAccount = $em->find('BBDurianBundle:RemitAccount', 7);

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $client = $this->createClient();
        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);

        $parameters = [
            'order_number' => $orderNumber,
            'ancestor_id' => 3,
            'bank_info_id' => 1,
            'amount' => 999,
            'method' => 1,
            'name_real' => '戰鬥民族',
            'branch' => '山東分會',
            'cellphone' => '100212551121',
            'memo' => '撿到一百塊',
            'identity_card' => '410305197611070144',
            'auto_confirm' => 1,
        ];
        $client->request('POST', '/api/user/8/remit', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $entryId = $output['ret']['id'];
        $remitEntry = $em->find('BBDurianBundle:RemitEntry', $entryId);

        // Account6 是自動入款的銀行帳號，但有未確認的明細，所以會取到 Account7
        $this->assertEquals(7, $remitEntry->getRemitAccountId());
        $this->assertEquals($remitEntry->getRemitAccountId(), $output['ret']['remit_account_id']);
        $this->assertEquals($remitEntry->getDomain(), $output['ret']['domain']);
        $this->assertEquals($remitEntry->getOrderNumber(), $output['ret']['order_number']);
        $this->assertEquals($remitEntry->getOldOrderNumber(), $output['ret']['old_order_number']);
        $this->assertEquals($remitEntry->getUserId(), $output['ret']['user_id']);
        $this->assertEquals($remitEntry->getLevelId(), $output['ret']['level_id']);
        $this->assertEquals($remitEntry->getAncestorId(), $output['ret']['ancestor_id']);
        $this->assertEquals($remitEntry->getBankInfoId(), $output['ret']['bank_info_id']);
        $this->assertEquals($remitEntry->getNameReal(), $output['ret']['name_real']);
        $this->assertEquals($remitEntry->getMethod(), $output['ret']['method']);
        $this->assertEquals($remitEntry->getBranch(), $output['ret']['branch']);
        $this->assertEquals($remitEntry->getAmount(), $output['ret']['amount']);
        $this->assertEquals($remitEntry->getDiscount(), $output['ret']['discount']);
        $this->assertEquals($remitEntry->getOtherDiscount(), $output['ret']['other_discount']);
        $this->assertEquals($remitEntry->getAutoRemitId(), $output['ret']['auto_remit_id']);
        $this->assertFalse($output['ret']['abandon_discount']);
        $this->assertTrue($output['ret']['auto_confirm']);
        $this->assertEquals($remitEntry->getCellphone(), $output['ret']['cellphone']);
        $this->assertEquals($remitEntry->getTradeNumber(), $output['ret']['trade_number']);
        $this->assertEquals($remitEntry->getPayerCard(), $output['ret']['payer_card']);
        $this->assertEquals($remitEntry->getTransferCode(), $output['ret']['transfer_code']);
        $this->assertEquals($remitEntry->getAtmTerminalCode(), $output['ret']['atm_terminal_code']);
        $this->assertEquals($remitEntry->getMemo(), $output['ret']['memo']);
        $this->assertEquals($remitEntry->getIdentityCard(), $output['ret']['identity_card']);
        $this->assertEquals($remitEntry->getStatus(), $output['ret']['status']);
        $this->assertEquals($remitEntry->getOperator(), $output['ret']['operator']);
        $this->assertEquals($remitEntry->getRate(), $output['ret']['rate']);
        $this->assertEquals($remitEntry->getCreatedAt()->format(\DateTime::ISO8601), $output['ret']['created_at']);
        $this->assertNull($remitEntry->getDepositAt());
        $this->assertNull($output['ret']['confirm_at']);
        $this->assertEquals($remitEntry->getDuration(), $output['ret']['duration']);

        // 檢查訂單號已被使用
        $remitOrder = $em->find('BBDurianBundle:RemitOrder', $orderNumber);
        $this->assertTrue($remitOrder->isUsed());

        // 檢查是否新增自動認款相關資料
        $remitAutoConfirm = $em->find('BBDurianBundle:RemitAutoConfirm', $entryId);

        $this->assertEquals($remitEntry, $remitAutoConfirm->getRemitEntry());
        $this->assertEquals('7025875', $remitAutoConfirm->getAutoConfirmId());

        // 檢查LogOperation
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remit_entry', $logOp->getTableName());
        $this->assertEquals("@id:$entryId", $logOp->getMajorKey());

        $message = [
            '@remit_account_id:'.$output['ret']['remit_account_id'],
            '@domain:'.$output['ret']['domain'],
            '@order_number:'.$output['ret']['order_number'],
            '@ancestor_id:'.$output['ret']['ancestor_id'],
            '@user_id:'.$output['ret']['user_id'],
            "@level_id:".$output['ret']['level_id'],
            '@bank_info_id:'.$output['ret']['bank_info_id'],
            "@name_real:".$output['ret']['name_real'],
            "@method:".$output['ret']['method'],
            "@amount:".$output['ret']['amount'],
            '@discount:'.$output['ret']['discount'],
            '@other_discount:'.$output['ret']['other_discount'],
            '@abandon_discount:false',
            '@auto_confirm:true',
            "@branch:".$output['ret']['branch'],
            "@cellphone:".$output['ret']['cellphone'],
            "@memo:".$output['ret']['memo'],
            "@identity_card:".$output['ret']['identity_card'],
            "@rate:".$output['ret']['rate'],
        ];
        $this->assertEquals(implode(', ', $message), $logOp->getMessage());

        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('remit_auto_confirm', $logOp2->getTableName());
        $this->assertEquals("@remit_entry_id:$entryId", $logOp2->getMajorKey());
        $this->assertEquals('@auto_confirm_id:7025875', $logOp2->getMessage());

        // 檢查 log 是否存在
        $this->assertFileExists($this->logPath);

        // 檢查 log 內容
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $logMsg = '/\[\d{4}-\d\d-\d\d \d\d:\d\d:\d\d\] app\.INFO: .*? payment.https.s04.tonglueyun.com ' .
            '"POST \/authority\/system\/api\/place_order\/" '.
            '"HEADER: " ' .
            '"REQUEST: {"apikey":"\*\*\*\*\*\*","order_id":"' .
            $orderNumber . '","bank_flag":"ICBC","card_login_name":"","card_number":"3939889","pay_card_number":"",' .
            '"pay_username":"\\\u6230\\\u9b25\\\u6c11\\\u65cf","amount":999,' .
            '"create_time":\d+,"comment":"' . $orderNumber . '"}" "RESPONSE: {"id": 7025875, "success": true}" [] []/';
        $this->assertRegExp($logMsg, $results[0]);
    }

    /**
     * 測試新增自動入款紀錄不會分配暫停的銀行卡
     */
    public function testAutoConfirmRemitWillNeverChooseSuspendedRemitAccount()
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

        // 調整銀行卡以利後續測試
        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 9);
        $remitAutoConfirm = $em->getRepository('BBDurianBundle:RemitAutoConfirm')->findOneBy(['remitEntry' => 9]);

        $remitAccountLevel6 = $em->getRepository('BBDurianBundle:RemitAccountLevel')->findOneBy([
            'levelId' => 2,
            'remitAccountId' => 6,
        ]);

        $remitAccountLevel6->setOrderId(2);

        $remitAccount6 = $em->find('BBDurianBundle:RemitAccount', 6);
        $remitAccount6->suspend();

        $em->remove($remitEntry);
        $em->remove($remitAutoConfirm);
        $em->flush();

        $client->request('GET', '/api/remit/entry/order_number');
        $numberOutPut = json_decode($client->getResponse()->getContent(), true);
        $orderNumber = $numberOutPut['ret'];

        $response = new Response();
        $response->setContent('{"id": 7025875, "success": true}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $client = $this->createClient();
        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);

        $parameters = [
            'order_number' => $orderNumber,
            'ancestor_id' => 3,
            'bank_info_id' => 1,
            'amount' => 999,
            'method' => 1,
            'name_real' => '戰鬥民族',
            'branch' => '山東分會',
            'cellphone' => '100212551121',
            'memo' => '撿到一百塊',
            'identity_card' => '410305197611070144',
            'auto_confirm' => 1,
        ];
        $client->request('POST', '/api/user/8/remit', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $entryId = $output['ret']['id'];
        $remitEntry = $em->find('BBDurianBundle:RemitEntry', $entryId);

        // Account6 超過限額被暫停，所以會取到 Account7
        $this->assertEquals(7, $remitEntry->getRemitAccountId());
        $this->assertEquals($remitEntry->getRemitAccountId(), $output['ret']['remit_account_id']);
        $this->assertEquals($remitEntry->getOrderNumber(), $output['ret']['order_number']);
        $this->assertEquals($remitEntry->getOldOrderNumber(), $output['ret']['old_order_number']);
        $this->assertEquals($remitEntry->getUserId(), $output['ret']['user_id']);
        $this->assertEquals($remitEntry->getLevelId(), $output['ret']['level_id']);
        $this->assertEquals($remitEntry->getAncestorId(), $output['ret']['ancestor_id']);
        $this->assertEquals($remitEntry->getBankInfoId(), $output['ret']['bank_info_id']);
        $this->assertEquals($remitEntry->getNameReal(), $output['ret']['name_real']);
        $this->assertEquals($remitEntry->getMethod(), $output['ret']['method']);
        $this->assertEquals($remitEntry->getBranch(), $output['ret']['branch']);
        $this->assertEquals($remitEntry->getAmount(), $output['ret']['amount']);
        $this->assertEquals($remitEntry->getDiscount(), $output['ret']['discount']);
        $this->assertEquals($remitEntry->getOtherDiscount(), $output['ret']['other_discount']);
        $this->assertFalse($output['ret']['abandon_discount']);
        $this->assertTrue($output['ret']['auto_confirm']);
        $this->assertEquals($remitEntry->getCellphone(), $output['ret']['cellphone']);
        $this->assertEquals($remitEntry->getTradeNumber(), $output['ret']['trade_number']);
        $this->assertEquals($remitEntry->getPayerCard(), $output['ret']['payer_card']);
        $this->assertEquals($remitEntry->getTransferCode(), $output['ret']['transfer_code']);
        $this->assertEquals($remitEntry->getAtmTerminalCode(), $output['ret']['atm_terminal_code']);
        $this->assertEquals($remitEntry->getMemo(), $output['ret']['memo']);
        $this->assertEquals($remitEntry->getIdentityCard(), $output['ret']['identity_card']);
        $this->assertEquals($remitEntry->getStatus(), $output['ret']['status']);
        $this->assertEquals($remitEntry->getOperator(), $output['ret']['operator']);
        $this->assertEquals($remitEntry->getRate(), $output['ret']['rate']);
        $this->assertEquals($remitEntry->getCreatedAt()->format(\DateTime::ISO8601), $output['ret']['created_at']);
        $this->assertNull($remitEntry->getDepositAt());
        $this->assertNull($output['ret']['confirm_at']);
        $this->assertEquals($remitEntry->getDuration(), $output['ret']['duration']);

        // 檢查訂單號已被使用
        $remitOrder = $em->find('BBDurianBundle:RemitOrder', $orderNumber);
        $this->assertTrue($remitOrder->isUsed());

        // 檢查是否新增自動認款相關資料
        $remitAutoConfirm = $em->find('BBDurianBundle:RemitAutoConfirm', $entryId);

        $this->assertEquals($remitEntry, $remitAutoConfirm->getRemitEntry());
        $this->assertEquals('7025875', $remitAutoConfirm->getAutoConfirmId());

        // 檢查LogOperation
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remit_entry', $logOp->getTableName());
        $this->assertEquals("@id:$entryId", $logOp->getMajorKey());

        $message = [
            '@remit_account_id:' . $output['ret']['remit_account_id'],
            '@domain:' . $output['ret']['domain'],
            '@order_number:' . $output['ret']['order_number'],
            '@ancestor_id:' . $output['ret']['ancestor_id'],
            '@user_id:' . $output['ret']['user_id'],
            '@level_id:' . $output['ret']['level_id'],
            '@bank_info_id:' . $output['ret']['bank_info_id'],
            '@name_real:' . $output['ret']['name_real'],
            '@method:' . $output['ret']['method'],
            '@amount:' . $output['ret']['amount'],
            '@discount:' . $output['ret']['discount'],
            '@other_discount:' . $output['ret']['other_discount'],
            '@abandon_discount:false',
            '@auto_confirm:true',
            '@branch:' . $output['ret']['branch'],
            '@cellphone:' . $output['ret']['cellphone'],
            '@memo:' . $output['ret']['memo'],
            '@identity_card:' . $output['ret']['identity_card'],
            '@rate:' . $output['ret']['rate'],
        ];
        $this->assertEquals(implode(', ', $message), $logOp->getMessage());

        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('remit_auto_confirm', $logOp2->getTableName());
        $this->assertEquals("@remit_entry_id:$entryId", $logOp2->getMajorKey());
        $this->assertEquals('@auto_confirm_id:7025875', $logOp2->getMessage());

        // 檢查 log 是否存在
        $this->assertFileExists($this->logPath);

        // 檢查 log 內容
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $logMsg = '/\[\d{4}-\d\d-\d\d \d\d:\d\d:\d\d\] app\.INFO: .*? payment.https.s04.tonglueyun.com ' .
            '"POST \/authority\/system\/api\/place_order\/" ' .
            '"HEADER: " ' .
            '"REQUEST: {"apikey":"\*\*\*\*\*\*","order_id":"' .
            $orderNumber . '","bank_flag":"ICBC","card_login_name":"","card_number":"3939889","pay_card_number":"",' .
            '"pay_username":"\\\u6230\\\u9b25\\\u6c11\\\u65cf","amount":999,' .
            '"create_time":\d+,"comment":"' . $orderNumber . '"}" "RESPONSE: {"id": 7025875, "success": true}" [] []/';
        $this->assertRegExp($logMsg, $results[0]);
    }

    /**
     * 測試新增自動入款記錄依照使用次數分配銀行卡
     */
    public function testAutoConfirmRemitWithRemitLevelOrder()
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

        $client->request('GET', '/api/remit/entry/order_number');
        $numberOutPut = json_decode($client->getResponse()->getContent(), true);
        $orderNumber = $numberOutPut['ret'];

        $response = new Response();
        $response->setContent('{"id": 7025875, "success": true}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $client = $this->createClient();
        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);

        // 調整銀行卡以利後續測試
        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 9);
        $remitAutoConfirm = $em->getRepository('BBDurianBundle:RemitAutoConfirm')->findOneBy(['remitEntry' => 9]);

        $remitAccountLevel6 = $em->getRepository('BBDurianBundle:RemitAccountLevel')->findOneBy([
            'levelId' => 2,
            'remitAccountId' => 6,
        ]);

        $remitAccountLevel6->setOrderId(2);

        $remitAccount8 = $em->find('BBDurianBundle:RemitAccount', 8);

        $remitAccount8->setAutoConfirm(true);

        $em->remove($remitEntry);
        $em->remove($remitAutoConfirm);
        $em->flush();

        $parameters = [
            'order_number' => $orderNumber,
            'ancestor_id' => 3,
            'bank_info_id' => 2,
            'amount' => 999,
            'method' => 1,
            'name_real' => '戰鬥民族',
            'branch' => '山東分會',
            'cellphone' => '100212551121',
            'memo' => '撿到一百塊',
            'identity_card' => '410305197611070144',
            'auto_confirm' => 1,
        ];
        $client->request('POST', '/api/user/8/remit', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $entryId = $output['ret']['id'];
        $remitEntry = $em->find('BBDurianBundle:RemitEntry', $entryId);

        // Account 6 8 的使用次數相同，但 Account 8 排序較前，因此優先取得
        $this->assertEquals(8, $remitEntry->getRemitAccountId());
        $this->assertEquals($remitEntry->getRemitAccountId(), $output['ret']['remit_account_id']);
        $this->assertEquals($remitEntry->getDomain(), $output['ret']['domain']);
        $this->assertEquals($remitEntry->getOrderNumber(), $output['ret']['order_number']);
        $this->assertEquals($remitEntry->getOldOrderNumber(), $output['ret']['old_order_number']);
        $this->assertEquals($remitEntry->getUserId(), $output['ret']['user_id']);
        $this->assertEquals($remitEntry->getLevelId(), $output['ret']['level_id']);
        $this->assertEquals($remitEntry->getAncestorId(), $output['ret']['ancestor_id']);
        $this->assertEquals($remitEntry->getBankInfoId(), $output['ret']['bank_info_id']);
        $this->assertEquals($remitEntry->getNameReal(), $output['ret']['name_real']);
        $this->assertEquals($remitEntry->getMethod(), $output['ret']['method']);
        $this->assertEquals($remitEntry->getBranch(), $output['ret']['branch']);
        $this->assertEquals($remitEntry->getAmount(), $output['ret']['amount']);
        $this->assertEquals($remitEntry->getDiscount(), $output['ret']['discount']);
        $this->assertEquals($remitEntry->getOtherDiscount(), $output['ret']['other_discount']);
        $this->assertEquals($remitEntry->getAutoRemitId(), $output['ret']['auto_remit_id']);
        $this->assertFalse($output['ret']['abandon_discount']);
        $this->assertTrue($output['ret']['auto_confirm']);
        $this->assertEquals($remitEntry->getCellphone(), $output['ret']['cellphone']);
        $this->assertEquals($remitEntry->getTradeNumber(), $output['ret']['trade_number']);
        $this->assertEquals($remitEntry->getPayerCard(), $output['ret']['payer_card']);
        $this->assertEquals($remitEntry->getTransferCode(), $output['ret']['transfer_code']);
        $this->assertEquals($remitEntry->getAtmTerminalCode(), $output['ret']['atm_terminal_code']);
        $this->assertEquals($remitEntry->getMemo(), $output['ret']['memo']);
        $this->assertEquals($remitEntry->getIdentityCard(), $output['ret']['identity_card']);
        $this->assertEquals($remitEntry->getStatus(), $output['ret']['status']);
        $this->assertEquals($remitEntry->getOperator(), $output['ret']['operator']);
        $this->assertEquals($remitEntry->getRate(), $output['ret']['rate']);
        $this->assertEquals($remitEntry->getCreatedAt()->format(\DateTime::ISO8601), $output['ret']['created_at']);
        $this->assertNull($remitEntry->getDepositAt());
        $this->assertNull($output['ret']['confirm_at']);
        $this->assertEquals($remitEntry->getDuration(), $output['ret']['duration']);

        // 檢查訂單號已被使用
        $remitOrder = $em->find('BBDurianBundle:RemitOrder', $orderNumber);
        $this->assertTrue($remitOrder->isUsed());

        // 檢查是否新增自動認款相關資料
        $remitAutoConfirm = $em->find('BBDurianBundle:RemitAutoConfirm', $entryId);

        $this->assertEquals($remitEntry, $remitAutoConfirm->getRemitEntry());
        $this->assertEquals('7025875', $remitAutoConfirm->getAutoConfirmId());

        // 檢查LogOperation
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remit_entry', $logOp->getTableName());
        $this->assertEquals("@id:$entryId", $logOp->getMajorKey());

        $message = [
            '@remit_account_id:' . $output['ret']['remit_account_id'],
            '@domain:' . $output['ret']['domain'],
            '@order_number:' . $output['ret']['order_number'],
            '@ancestor_id:' . $output['ret']['ancestor_id'],
            '@user_id:' . $output['ret']['user_id'],
            '@level_id:' . $output['ret']['level_id'],
            '@bank_info_id:' . $output['ret']['bank_info_id'],
            '@name_real:' . $output['ret']['name_real'],
            '@method:' . $output['ret']['method'],
            '@amount:' . $output['ret']['amount'],
            '@discount:' . $output['ret']['discount'],
            '@other_discount:' . $output['ret']['other_discount'],
            '@abandon_discount:false',
            '@auto_confirm:true',
            '@branch:' . $output['ret']['branch'],
            '@cellphone:' . $output['ret']['cellphone'],
            '@memo:' . $output['ret']['memo'],
            '@identity_card:' . $output['ret']['identity_card'],
            '@rate:' . $output['ret']['rate'],
        ];
        $this->assertEquals(implode(', ', $message), $logOp->getMessage());

        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('remit_auto_confirm', $logOp2->getTableName());
        $this->assertEquals("@remit_entry_id:$entryId", $logOp2->getMajorKey());
        $this->assertEquals('@auto_confirm_id:7025875', $logOp2->getMessage());

        // 檢查 log 是否存在
        $this->assertFileExists($this->logPath);

        // 檢查 log 內容
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $logMsg = '/\[\d{4}-\d\d-\d\d \d\d:\d\d:\d\d\] app\.INFO: .*? payment.https.s04.tonglueyun.com ' .
            '"POST \/authority\/system\/api\/place_order\/" ' .
            '"HEADER: " ' .
            '"REQUEST: {"apikey":"\*\*\*\*\*\*","order_id":"' .
            $orderNumber . '","bank_flag":"CCB","card_login_name":"","card_number":"94879487","pay_card_number":"",' .
            '"pay_username":"\\\u6230\\\u9b25\\\u6c11\\\u65cf","amount":999,' .
            '"create_time":\d+,"comment":"' . $orderNumber . '"}" "RESPONSE: {"id": 7025875, "success": true}" [] []/';
        $this->assertRegExp($logMsg, $results[0]);
    }

    /**
     * 測試新增自動入款記錄不依照使用次數分配銀行卡
     */
    public function testAutoConfirmRemitWithoutRemitLevelOrder()
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

        $client->request('GET', '/api/remit/entry/order_number');
        $numberOutPut = json_decode($client->getResponse()->getContent(), true);
        $orderNumber = $numberOutPut['ret'];

        $response = new Response();
        $response->setContent('{"id": 7025875, "success": true}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $client = $this->createClient();
        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);

        // 調整銀行卡以利後續測試分配邏輯
        $remitEntries = $em->getRepository('BBDurianBundle:RemitEntry')->findBy(['remitAccountId' => 6]);
        $remitAutoConfirm = $em->getRepository('BBDurianBundle:RemitAutoConfirm')->findOneBy(['remitEntry' => 9]);

        $em->remove($remitAutoConfirm);
        foreach ($remitEntries as $remitEntry) {
            $em->remove($remitEntry);
        }

        $remitAccount8 = $em->find('BBDurianBundle:RemitAccount', 8);
        $remitAccount8->setAutoConfirm(true);

        // 提高 Account6 排序
        $remitAccountLevel6 = $em->find('BBDurianBundle:RemitAccountLevel', [
            'remitAccountId' => 6,
            'levelId' => 2
        ]);
        $remitAccountLevel6->setOrderId(2);

        $remitAccount6 = $em->find('BBDurianBundle:RemitAccount', 6);
        $remitAccount6->setBankInfoId(4);
        $remitAccount6->setAutoConfirm(true);
        $remitAccount6->setAutoRemitId(1);

        // 關閉「依照使用次數分配銀行卡」的設定
        $config = $em->getRepository('BBDurianBundle:RemitLevelOrder')->findOneBy(['levelId' => 2]);
        $config->setByCount(false);

        $em->flush();

        $parameters = [
            'order_number' => $orderNumber,
            'ancestor_id' => 3,
            'bank_info_id' => 2,
            'amount' => 999,
            'method' => 1,
            'name_real' => '戰鬥民族',
            'branch' => '山東分會',
            'cellphone' => '100212551121',
            'memo' => '撿到一百塊',
            'identity_card' => '410305197611070144',
            'auto_confirm' => 1,
        ];
        $client->request('POST', '/api/user/8/remit', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $entryId = $output['ret']['id'];
        $remitEntry = $em->find('BBDurianBundle:RemitEntry', $entryId);

        // 關閉銀行卡次數統計時，會依照銀行卡排序，優先分配銀行卡排序較前的
        $this->assertEquals(7, $remitEntry->getRemitAccountId());
        $this->assertEquals($remitEntry->getRemitAccountId(), $output['ret']['remit_account_id']);
        $this->assertEquals($remitEntry->getDomain(), $output['ret']['domain']);
        $this->assertEquals($remitEntry->getOrderNumber(), $output['ret']['order_number']);
        $this->assertEquals($remitEntry->getOldOrderNumber(), $output['ret']['old_order_number']);
        $this->assertEquals($remitEntry->getUserId(), $output['ret']['user_id']);
        $this->assertEquals($remitEntry->getLevelId(), $output['ret']['level_id']);
        $this->assertEquals($remitEntry->getAncestorId(), $output['ret']['ancestor_id']);
        $this->assertEquals($remitEntry->getBankInfoId(), $output['ret']['bank_info_id']);
        $this->assertEquals($remitEntry->getNameReal(), $output['ret']['name_real']);
        $this->assertEquals($remitEntry->getMethod(), $output['ret']['method']);
        $this->assertEquals($remitEntry->getBranch(), $output['ret']['branch']);
        $this->assertEquals($remitEntry->getAmount(), $output['ret']['amount']);
        $this->assertEquals($remitEntry->getDiscount(), $output['ret']['discount']);
        $this->assertEquals($remitEntry->getOtherDiscount(), $output['ret']['other_discount']);
        $this->assertEquals($remitEntry->getAutoRemitId(), $output['ret']['auto_remit_id']);
        $this->assertFalse($output['ret']['abandon_discount']);
        $this->assertTrue($output['ret']['auto_confirm']);
        $this->assertEquals($remitEntry->getCellphone(), $output['ret']['cellphone']);
        $this->assertEquals($remitEntry->getTradeNumber(), $output['ret']['trade_number']);
        $this->assertEquals($remitEntry->getPayerCard(), $output['ret']['payer_card']);
        $this->assertEquals($remitEntry->getTransferCode(), $output['ret']['transfer_code']);
        $this->assertEquals($remitEntry->getAtmTerminalCode(), $output['ret']['atm_terminal_code']);
        $this->assertEquals($remitEntry->getMemo(), $output['ret']['memo']);
        $this->assertEquals($remitEntry->getIdentityCard(), $output['ret']['identity_card']);
        $this->assertEquals($remitEntry->getStatus(), $output['ret']['status']);
        $this->assertEquals($remitEntry->getOperator(), $output['ret']['operator']);
        $this->assertEquals($remitEntry->getRate(), $output['ret']['rate']);
        $this->assertEquals($remitEntry->getCreatedAt()->format(\DateTime::ISO8601), $output['ret']['created_at']);
        $this->assertNull($remitEntry->getDepositAt());
        $this->assertNull($output['ret']['confirm_at']);
        $this->assertEquals($remitEntry->getDuration(), $output['ret']['duration']);

        // 檢查訂單號已被使用
        $remitOrder = $em->find('BBDurianBundle:RemitOrder', $orderNumber);
        $this->assertTrue($remitOrder->isUsed());

        // 檢查是否新增自動認款相關資料
        $remitAutoConfirm = $em->find('BBDurianBundle:RemitAutoConfirm', $entryId);

        $this->assertEquals($remitEntry, $remitAutoConfirm->getRemitEntry());
        $this->assertEquals('7025875', $remitAutoConfirm->getAutoConfirmId());

        // 檢查LogOperation
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remit_entry', $logOp->getTableName());
        $this->assertEquals("@id:$entryId", $logOp->getMajorKey());

        $message = [
            '@remit_account_id:' . $output['ret']['remit_account_id'],
            '@domain:' . $output['ret']['domain'],
            '@order_number:' . $output['ret']['order_number'],
            '@ancestor_id:' . $output['ret']['ancestor_id'],
            '@user_id:' . $output['ret']['user_id'],
            '@level_id:' . $output['ret']['level_id'],
            '@bank_info_id:' . $output['ret']['bank_info_id'],
            '@name_real:' . $output['ret']['name_real'],
            '@method:' . $output['ret']['method'],
            '@amount:' . $output['ret']['amount'],
            '@discount:' . $output['ret']['discount'],
            '@other_discount:' . $output['ret']['other_discount'],
            '@abandon_discount:false',
            '@auto_confirm:true',
            '@branch:' . $output['ret']['branch'],
            '@cellphone:' . $output['ret']['cellphone'],
            '@memo:' . $output['ret']['memo'],
            '@identity_card:' . $output['ret']['identity_card'],
            '@rate:' . $output['ret']['rate'],
        ];
        $this->assertEquals(implode(', ', $message), $logOp->getMessage());

        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('remit_auto_confirm', $logOp2->getTableName());
        $this->assertEquals("@remit_entry_id:$entryId", $logOp2->getMajorKey());
        $this->assertEquals('@auto_confirm_id:7025875', $logOp2->getMessage());

        // 檢查 log 是否存在
        $this->assertFileExists($this->logPath);

        // 檢查 log 內容
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $logMsg = '/\[\d{4}-\d\d-\d\d \d\d:\d\d:\d\d\] app\.INFO: .*? payment.https.s04.tonglueyun.com ' .
            '"POST \/authority\/system\/api\/place_order\/" "HEADER: " ' .
            '"REQUEST: {"apikey":"\*\*\*\*\*\*","order_id":"' .
            $orderNumber . '","bank_flag":"ICBC","card_login_name":"","card_number":"3939889","pay_card_number":"",' .
            '"pay_username":"\\\u6230\\\u9b25\\\u6c11\\\u65cf","amount":999,' .
            '"create_time":\d+,"comment":"' . $orderNumber . '"}" "RESPONSE: {"id": 7025875, "success": true}" [] []/';
        $this->assertRegExp($logMsg, $results[0]);
    }

    /**
     * 測試新增自動入款記錄無可用的入款銀行
     */
    public function testAutoConfirmWithNoAccount()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $repository = $em->getRepository('BBDurianBundle:RemitAccount');

        // 關掉自動入款帳號
        $remitAccounts = $repository->findBy(['autoConfirm' => true]);

        foreach ($remitAccounts as $remitAccount) {
            $remitAccount->setAutoConfirm(false);
        }

        $em->flush();

        $client->request('GET', '/api/remit/entry/order_number');
        $numberOutPut = json_decode($client->getResponse()->getContent(), true);

        $parameters = [
            'order_number' => $numberOutPut['ret'],
            'ancestor_id' => 3,
            'bank_info_id' => 1,
            'amount' => 999,
            'method' => 1,
            'name_real' => '戰鬥民族',
            'branch' => '山東分會',
            'cellphone' => '100212551121',
            'memo' => '撿到一百塊',
            'identity_card' => '410305197611070144',
            'auto_confirm' => 1,
        ];

        $client->request('POST', '/api/user/8/remit', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150300077, $output['code']);
        $this->assertEquals('No auto RemitAccount found', $output['msg']);
    }

    /**
     * 測試新增自動入款記錄無符合自動認款條件的入款銀行
     */
    public function testAutoConfirmWithNoAccountAvailable()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $repository = $em->getRepository('BBDurianBundle:RemitAccount');

        // 關掉自動入款帳號
        $remitAccounts = $repository->findBy(['id' => [7, 8]]);

        foreach ($remitAccounts as $remitAccount) {
            $remitAccount->setAutoConfirm(false);
        }

        $em->flush();

        $client->request('GET', '/api/remit/entry/order_number');
        $numberOutPut = json_decode($client->getResponse()->getContent(), true);

        $parameters = [
            'order_number' => $numberOutPut['ret'],
            'ancestor_id' => 3,
            'bank_info_id' => 1,
            'amount' => 999,
            'method' => 1,
            'name_real' => '戰鬥民族',
            'branch' => '山東分會',
            'cellphone' => '100212551121',
            'memo' => '撿到一百塊',
            'identity_card' => '410305197611070144',
            'auto_confirm' => 1,
        ];

        $client->request('POST', '/api/user/8/remit', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150300078, $output['code']);
        $this->assertEquals('No auto RemitAccount available', $output['msg']);
    }

    /**
     * 測試新增自動入款記錄但自動認款連線異常
     */
    public function testAutoConfirmRemitButAutoConfirmConnectionError()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $client->request('GET', '/api/remit/entry/order_number');
        $numberOutPut = json_decode($client->getResponse()->getContent(), true);

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

        $client = $this->createClient();
        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);

        $parameters = [
            'order_number' => $numberOutPut['ret'],
            'ancestor_id' => 3,
            'bank_info_id' => 1,
            'amount' => 999,
            'method' => 1,
            'name_real' => '戰鬥民族',
            'branch' => '山東分會',
            'cellphone' => '100212551121',
            'memo' => '撿到一百塊',
            'identity_card' => '410305197611070144',
            'auto_confirm' => 1,
        ];

        $client->request('POST', '/api/user/8/remit', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150870021, $output['code']);
        $this->assertEquals('Auto Confirm connection failure', $output['msg']);

        // 檢查 log 是否不存在
        $this->assertFileNotExists($this->logPath);
    }

    /**
     * 測試新增自動入款記錄但自動認款連線失敗
     */
    public function testAutoConfirmRemitButAutoConfirmConnectionFailure()
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

        $client->request('GET', '/api/remit/entry/order_number');
        $numberOutPut = json_decode($client->getResponse()->getContent(), true);

        $response = new Response();
        $response->addHeader('HTTP/1.1 499');
        $response->addHeader('Content-Type: application/json');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $client = $this->createClient();
        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);

        $parameters = [
            'order_number' => $numberOutPut['ret'],
            'ancestor_id' => 3,
            'bank_info_id' => 1,
            'amount' => 999,
            'method' => 1,
            'name_real' => '戰鬥民族',
            'branch' => '山東分會',
            'cellphone' => '100212551121',
            'memo' => '撿到一百塊',
            'identity_card' => '410305197611070144',
            'auto_confirm' => 1,
        ];

        $client->request('POST', '/api/user/8/remit', $parameters);
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
            '"POST \/authority\/system\/api\/place_order\/" ' .
            '"HEADER: " ' .
            '"REQUEST: {"apikey":"\*\*\*\*\*\*","order_id":"' .
            $numberOutPut['ret'] . '","bank_flag":"ICBC","card_login_name":"","card_number":"3939889",' .
            '"pay_card_number":"","pay_username":"\\\u6230\\\u9b25\\\u6c11\\\u65cf","amount":999,"create_time":\d+,' .
            '"comment":"' . $numberOutPut['ret'] . '"}" "RESPONSE: " [] []/';
        $this->assertRegExp($logMsg, $results[0]);
    }

    /**
     * 測試新增自動入款記錄但未指定自動認款返回參數
     */
    public function testAutoConfirmRemitButNoAutoConfirmReturnParameterSpecified()
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

        $client->request('GET', '/api/remit/entry/order_number');
        $numberOutPut = json_decode($client->getResponse()->getContent(), true);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $client = $this->createClient();
        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);

        $parameters = [
            'order_number' => $numberOutPut['ret'],
            'ancestor_id' => 3,
            'bank_info_id' => 1,
            'amount' => 999,
            'method' => 1,
            'name_real' => '戰鬥民族',
            'branch' => '山東分會',
            'cellphone' => '100212551121',
            'memo' => '撿到一百塊',
            'identity_card' => '410305197611070144',
            'auto_confirm' => 1,
        ];

        $client->request('POST', '/api/user/8/remit', $parameters);
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
            '"POST \/authority\/system\/api\/place_order\/" "HEADER: " '.
            '"REQUEST: {"apikey":"\*\*\*\*\*\*","order_id":"' .
            $numberOutPut['ret'] . '","bank_flag":"ICBC","card_login_name":"","card_number":"3939889",' .
            '"pay_card_number":"","pay_username":"\\\u6230\\\u9b25\\\u6c11\\\u65cf","amount":999,"create_time":\d+,' .
            '"comment":"' . $numberOutPut['ret'] . '"}" "RESPONSE: " [] []/';
        $this->assertRegExp($logMsg, $results[0]);
    }

    /**
     * 測試新增自動入款記錄但沒有銀行卡的權限
     */
    public function testAutoConfirmRemitWithoutPermissionForBankCard()
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

        $client->request('GET', '/api/remit/entry/order_number');
        $numberOutPut = json_decode($client->getResponse()->getContent(), true);

        $response = new Response();
        $response->setContent('{"message": " You don\'t have permission for this bankcard..", "success": false}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $client = $this->createClient();
        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);

        $parameters = [
            'order_number' => $numberOutPut['ret'],
            'ancestor_id' => 3,
            'bank_info_id' => 1,
            'amount' => 999,
            'method' => 1,
            'name_real' => '戰鬥民族',
            'branch' => '山東分會',
            'cellphone' => '100212551121',
            'memo' => '撿到一百塊',
            'identity_card' => '410305197611070144',
            'auto_confirm' => 1,
        ];

        $client->request('POST', '/api/user/8/remit', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150870024, $output['code']);
        $this->assertRegExp('/You don\'t have permission for this bankcard../', $output['msg']);

        // 檢查 log 是否存在
        $this->assertFileExists($this->logPath);

        // 檢查 log 內容
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $logMsg = "/You don't have permission for this bankcard/";
        $this->assertRegexp($logMsg, $results[0]);
    }

    /**
     * 測試新增自動入款記錄但自動認款失敗
     */
    public function testAutoConfirmRemitButAutoConfirmFailed()
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

        $client->request('GET', '/api/remit/entry/order_number');
        $numberOutPut = json_decode($client->getResponse()->getContent(), true);

        $response = new Response();
        $response->setContent('{"id": 7025875, "success": false}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $client = $this->createClient();
        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);

        $parameters = [
            'order_number' => $numberOutPut['ret'],
            'ancestor_id' => 3,
            'bank_info_id' => 1,
            'amount' => 999,
            'method' => 1,
            'name_real' => '戰鬥民族',
            'branch' => '山東分會',
            'cellphone' => '100212551121',
            'memo' => '撿到一百塊',
            'identity_card' => '410305197611070144',
            'auto_confirm' => 1,
        ];

        $client->request('POST', '/api/user/8/remit', $parameters);
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
            '"POST \/authority\/system\/api\/place_order\/" "HEADER: " ' .
            '"REQUEST: {"apikey":"\*\*\*\*\*\*","order_id":"' .
            $numberOutPut['ret'] . '","bank_flag":"ICBC","card_login_name":"","card_number":"3939889",' .
            '"pay_card_number":"","pay_username":"\\\u6230\\\u9b25\\\u6c11\\\u65cf","amount":999,"create_time":\d+,' .
            '"comment":"' . $numberOutPut['ret'] . '"}" "RESPONSE: {"id": 7025875, "success": false}" [] []/';
        $this->assertRegExp($logMsg, $results[0]);
    }

    /**
     * 測試新增自動入款記錄但自動認款 id 未返回
     */
    public function testAutoConfirmRemitButAutoConfirmIdNotReturn()
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

        $client->request('GET', '/api/remit/entry/order_number');
        $numberOutPut = json_decode($client->getResponse()->getContent(), true);

        $response = new Response();
        $response->setContent('{"success": true}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $client = $this->createClient();
        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);

        $parameters = [
            'order_number' => $numberOutPut['ret'],
            'ancestor_id' => 3,
            'bank_info_id' => 1,
            'amount' => 999,
            'method' => 1,
            'name_real' => '戰鬥民族',
            'branch' => '山東分會',
            'cellphone' => '100212551121',
            'memo' => '撿到一百塊',
            'identity_card' => '410305197611070144',
            'auto_confirm' => 1,
        ];

        $client->request('POST', '/api/user/8/remit', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150870018, $output['code']);
        $this->assertEquals('No auto confirm return parameter specified', $output['msg']);

        // 檢查 log 是否存在
        $this->assertFileExists($this->logPath);

        // 檢查 log 內容
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $logMsg = '/\[\d{4}-\d\d-\d\d \d\d:\d\d:\d\d\] app\.INFO: .*? payment.https.s04.tonglueyun.com ' .
            '"POST \/authority\/system\/api\/place_order\/" ' .
            '"HEADER: " "REQUEST: {"apikey":"\*\*\*\*\*\*","order_id":"' .
            $numberOutPut['ret'] . '","bank_flag":"ICBC","card_login_name":"","card_number":"3939889",' .
            '"pay_card_number":"","pay_username":"\\\u6230\\\u9b25\\\u6c11\\\u65cf","amount":999,"create_time":\d+,' .
            '"comment":"' . $numberOutPut['ret'] . '"}" "RESPONSE: {"success": true}" [] []/';
        $this->assertRegExp($logMsg, $results[0]);
    }

    /**
     * 測試新增非人民幣帳號入款記錄
     */
    public function testRemitWhenCurrencyNotCNY()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $client->request('GET', '/api/remit/entry/order_number');
        $orderNumberOutPut = json_decode($client->getResponse()->getContent(), true);
        $orderNumber = $orderNumberOutPut['ret'];

        $account = $em->find('BBDurianBundle:RemitAccount', 2);
        $account->enable();
        $em->flush();

        $parameters = [
            'order_number' => $orderNumber,
            'ancestor_id' => 3,
            'account_id' => 2, // 新台幣
            'bank_info_id' => 2,
            'amount' => 100,
            'method' => 4,
            'name_real' => '戰鬥民族',
            'branch' => '天下第一舞蹈會',
            'deposit_at' => '2013-10-10T12:00:00+0800',
        ];

        $client->request('POST', '/api/user/8/remit', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $entryId = $output['ret']['id'];
        $remitEntry = $em->find('BBDurianBundle:RemitEntry', $entryId);
        $amount = $remitEntry->getAmount();
        $rate = $remitEntry->getRate();

        $this->assertEquals($amount, $output['ret']['amount']);
        $this->assertEquals($rate, $output['ret']['rate']);
        $this->assertEquals(($amount * $rate), $remitEntry->getAmountConvBasic());
    }

    /**
     * 測試新增入款記錄代入放棄優惠(abandon_discount=1)
     */
    public function testRemitAbandonDiscount()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $client->request('GET', '/api/remit/entry/order_number');
        $orderNumberOutPut = json_decode($client->getResponse()->getContent(), true);
        $orderNumber = $orderNumberOutPut['ret'];

        $parameters = [
            'order_number' => $orderNumber,
            'ancestor_id' => 3,
            'account_id' => 1,
            'bank_info_id' => 2,
            'amount' => 51,
            'method' => 4,
            'name_real' => '戰鬥民族',
            'deposit_at' => '2013-10-10T12:00:00+0800',
            'abandon_discount' => 1,
        ];
        $client->request('POST', '/api/user/8/remit', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $entryId = $output['ret']['id'];
        $remitEntry = $em->find('BBDurianBundle:RemitEntry', $entryId);

        $this->assertTrue($output['ret']['abandon_discount']);

        // 檢查LogOperation
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remit_entry', $logOp->getTableName());
        $this->assertEquals("@id:$entryId", $logOp->getMajorKey());

        $message = [
            '@remit_account_id:'.$output['ret']['remit_account_id'],
            '@domain:'.$output['ret']['domain'],
            '@order_number:'.$output['ret']['order_number'],
            '@ancestor_id:'.$output['ret']['ancestor_id'],
            '@user_id:'.$output['ret']['user_id'],
            "@level_id:".$output['ret']['level_id'],
            '@bank_info_id:'.$output['ret']['bank_info_id'],
            "@name_real:".$output['ret']['name_real'],
            "@method:".$output['ret']['method'],
            "@amount:".$output['ret']['amount'],
            '@discount:'.$output['ret']['discount'],
            '@other_discount:'.$output['ret']['other_discount'],
            '@abandon_discount:true',
            '@auto_confirm:false',
            "@deposit_at:".$remitEntry->getDepositAt()->format('YmdHis')
        ];

        $this->assertEquals(implode(', ', $message), $logOp->getMessage());
    }

    /**
     * 測試新增入款記錄代入不放棄優惠(abandon_discount=0)
     */
    public function testRemitNoAbandonDiscount()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $client->request('GET', '/api/remit/entry/order_number');
        $orderNumberOutPut = json_decode($client->getResponse()->getContent(), true);
        $orderNumber = $orderNumberOutPut['ret'];

        $parameters = [
            'order_number' => $orderNumber,
            'ancestor_id' => 3,
            'account_id' => 1,
            'bank_info_id' => 2,
            'amount' => 51,
            'method' => 4,
            'name_real' => '戰鬥民族',
            'deposit_at' => '2013-10-10T12:00:00+0800',
            'abandon_discount' => 0,
        ];
        $client->request('POST', '/api/user/8/remit', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $entryId = $output['ret']['id'];
        $remitEntry = $em->find('BBDurianBundle:RemitEntry', $entryId);

        $this->assertFalse($output['ret']['abandon_discount']);

        // 檢查LogOperation
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remit_entry', $logOp->getTableName());
        $this->assertEquals("@id:$entryId", $logOp->getMajorKey());

        $message = [
            '@remit_account_id:'.$output['ret']['remit_account_id'],
            '@domain:'.$output['ret']['domain'],
            '@order_number:'.$output['ret']['order_number'],
            '@ancestor_id:'.$output['ret']['ancestor_id'],
            '@user_id:'.$output['ret']['user_id'],
            "@level_id:".$output['ret']['level_id'],
            '@bank_info_id:'.$output['ret']['bank_info_id'],
            "@name_real:".$output['ret']['name_real'],
            "@method:".$output['ret']['method'],
            "@amount:".$output['ret']['amount'],
            '@discount:'.$output['ret']['discount'],
            '@other_discount:'.$output['ret']['other_discount'],
            '@abandon_discount:false',
            '@auto_confirm:false',
            "@deposit_at:".$remitEntry->getDepositAt()->format('YmdHis'),
        ];

        $this->assertEquals(implode(', ', $message), $logOp->getMessage());
    }

    /**
     * 測試新增入款記錄，真實姓名會過濾特殊字元
     */
    public function testRemitNameRealContainsSpecialCharacter()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $client->request('GET', '/api/remit/entry/order_number');
        $orderNumberOutPut = json_decode($client->getResponse()->getContent(), true);
        $orderNumber = $orderNumberOutPut['ret'];

        $parameters = [
            'order_number' => $orderNumber,
            'ancestor_id' => 3,
            'account_id' => 1,
            'bank_info_id' => 2,
            'amount' => 51,
            'method' => 4,
            'name_real' => '戰鬥民族',
            'other_discount' => '100',
            'branch' => '天下第一舞蹈會',
            'cellphone' => '100212551121',
            'trade_number' => '0001564516464',
            'payer_card' => '154121645113265',
            'transfer_code' => '100100',
            'atm_terminal_code' => '1021',
            'memo' => '撿到一百塊',
            'identity_card' => '410305197611070144',
            'deposit_at' => '2013-10-10T12:00:00+0800',
        ];
        $client->request('POST', '/api/user/8/remit', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $entryId = $output['ret']['id'];
        $remitEntry = $em->find('BBDurianBundle:RemitEntry', $entryId);

        $this->assertEquals('戰鬥民族', $output['ret']['name_real']);
        $this->assertEquals('戰鬥民族',$remitEntry->getNameReal());
    }

    /**
     * 測試新增非人民幣帳號入款但匯率不存在
     */
    public function testRemitWhenCurrencyNotCNYAndExchangeNotExist()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();
        $accountId = 2;

        $client->request('GET', '/api/remit/entry/order_number');
        $orderNumberOutPut = json_decode($client->getResponse()->getContent(), true);
        $orderNumber = $orderNumberOutPut['ret'];

        $account = $em->find('BBDurianBundle:RemitAccount', $accountId);
        $account->enable();
        $account->setCurrency(392);
        $em->flush();

        $parameters = [
            'order_number' => $orderNumber,
            'ancestor_id' => 3,
            'account_id' => $accountId, // 日幣
            'bank_info_id' => 2,
            'amount' => 100,
            'method' => 4,
            'name_real' => '戰鬥民族',
            'branch' => '天下第一舞蹈會',
            'deposit_at' => '2013-10-10T12:00:00+0800',
        ];

        $client->request('POST', '/api/user/8/remit', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300055, $output['code']);
        $this->assertEquals('No such exchange', $output['msg']);

        // 噴錯不會寫LogOperation
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertNull($logOp);

        // 噴錯訂單號不會被設成使用
        $remitOrder = $em->find('BBDurianBundle:RemitOrder', $orderNumber);
        $this->assertFalse($remitOrder->isUsed());
    }

    /**
     * 測試新增入款記錄公司帳號不存在
     */
    public function testRemitWhenRemitAccountNotExist()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/remit/entry/order_number');
        $orderNumberOutPut = json_decode($client->getResponse()->getContent(), true);
        $orderNumber = $orderNumberOutPut['ret'];

        $parameters = [
            'order_number' => $orderNumber,
            'ancestor_id' => 3,
            'account_id' => 999,
            'bank_info_id' => 2,
            'amount' => 51,
            'method' => 4,
            'name_real' => '戰鬥民族',
            'branch' => '天下第一舞蹈會',
            'deposit_at' => '2013-10-10T12:00:00+0800',
        ];

        $client->request('POST', '/api/user/8/remit', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300002, $output['code']);
        $this->assertEquals('No RemitAccount found', $output['msg']);
    }

    /**
     * 測試新增入款記錄公司帳號未被啟用
     */
    public function testRemitWhenRemitAccountNotEnable()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/remit/entry/order_number');
        $orderNumberOutPut = json_decode($client->getResponse()->getContent(), true);
        $orderNumber = $orderNumberOutPut['ret'];

        $parameters = [
            'order_number' => $orderNumber,
            'ancestor_id' => 3,
            'account_id' => 2,
            'bank_info_id' => 2,
            'amount' => 51,
            'method' => 4,
            'name_real' => '戰鬥民族',
            'branch' => '天下第一舞蹈會',
            'deposit_at' => '2013-10-10T12:00:00+0800',
        ];

        $client->request('POST', '/api/user/8/remit', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300022, $output['code']);
        $this->assertEquals('RemitAccount is disabled', $output['msg']);
    }

    /**
     * 測試新增入款記錄公司帳號已被暫停
     */
    public function testRemitWhenRemitAccountIsSuspended()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $remitAccount = $em->find('BBDurianBundle:RemitAccount', 2);

        $remitAccount->enable();
        $remitAccount->suspend();

        $em->flush();

        $client = $this->createClient();
        $client->request('GET', '/api/remit/entry/order_number');
        $orderNumberOutPut = json_decode($client->getResponse()->getContent(), true);
        $orderNumber = $orderNumberOutPut['ret'];

        $parameters = [
            'order_number' => $orderNumber,
            'ancestor_id' => 3,
            'account_id' => 2,
            'bank_info_id' => 2,
            'amount' => 51,
            'method' => 4,
            'name_real' => '戰鬥民族',
            'branch' => '天下第一舞蹈會',
            'deposit_at' => '2013-10-10T12:00:00+0800',
        ];

        $client->request('POST', '/api/user/8/remit', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150300082, $output['code']);
        $this->assertEquals('RemitAccount is suspended', $output['msg']);
    }

    /**
     * 測試新增入款記錄入款會員不存在
     */
    public function testRemitWhenUserNotExist()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/remit/entry/order_number');
        $orderNumberOutPut = json_decode($client->getResponse()->getContent(), true);
        $orderNumber = $orderNumberOutPut['ret'];

        $parameters = [
            'order_number' => $orderNumber,
            'ancestor_id' => 3,
            'account_id' => 1,
            'bank_info_id' => 2,
            'amount' => 51,
            'method' => 4,
            'name_real' => '戰鬥民族',
            'branch' => '天下第一舞蹈會',
            'deposit_at' => '2013-10-10T12:00:00+0800',
        ];

        $client->request('POST', '/api/user/777/remit', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300054, $output['code']);
        $this->assertEquals('No such user', $output['msg']);
    }

    /**
     * 測試新增入款記錄入款會員的銀行系統不支援
     */
    public function testRemitWhenBankInfoNotExist()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/remit/entry/order_number');
        $orderNumberOutPut = json_decode($client->getResponse()->getContent(), true);
        $orderNumber = $orderNumberOutPut['ret'];

        $parameters = [
            'order_number' => $orderNumber,
            'ancestor_id' => 3,
            'account_id' => 1,
            'bank_info_id' => 666,
            'amount' => 51,
            'method' => 4,
            'name_real' => '戰鬥民族',
            'branch' => '天下第一舞蹈會',
            'deposit_at' => '2013-10-10T12:00:00+0800',
        ];

        $client->request('POST', '/api/user/8/remit', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300052, $output['code']);
        $this->assertEquals('No BankInfo found', $output['msg']);
    }

    /**
     * 測試新增入款記錄入款會員的上層不存在
     */
    public function testRemitWhenAncestorNotExist()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/remit/entry/order_number');
        $orderNumberOutPut = json_decode($client->getResponse()->getContent(), true);
        $orderNumber = $orderNumberOutPut['ret'];

        $parameters = [
            'order_number' => $orderNumber,
            'ancestor_id' => 888,
            'account_id' => 1,
            'bank_info_id' => 2,
            'amount' => 51,
            'method' => 4,
            'name_real' => '戰鬥民族',
            'branch' => '天下第一舞蹈會',
            'deposit_at' => '2013-10-10T12:00:00+0800',
        ];

        $client->request('POST', '/api/user/8/remit', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300051, $output['code']);
        $this->assertEquals('No ancestor found', $output['msg']);
    }

    /**
     * 測試新增入款記錄未代入訂單號
     */
    public function testRemitWithoutOrderNumber()
    {
        $client = $this->createClient();
        $parameters = [
            'ancestor_id' => 3,
            'account_id' => 1,
            'bank_info_id' => 2,
            'amount' => 51,
            'method' => 4,
            'name_real' => '戰鬥民族',
            'branch' => '天下第一舞蹈會',
            'deposit_at' => '2013-10-10T12:00:00+0800',
        ];

        $client->request('POST', '/api/user/8/remit', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300015, $output['code']);
        $this->assertEquals('No RemitOrder found', $output['msg']);
    }

    /**
     * 測試新增入款記錄的訂單號不存在
     */
    public function testRemitWithOrderNumberNotExist()
    {
        $client = $this->createClient();
        $parameters = [
            'order_number' => 2012123455667788,
            'ancestor_id' => 3,
            'account_id' => 1,
            'bank_info_id' => 2,
            'amount' => 51,
            'method' => 4,
            'name_real' => '戰鬥民族',
            'branch' => '天下第一舞蹈會',
            'deposit_at' => '2013-10-10T12:00:00+0800',
        ];

        $client->request('POST', '/api/user/8/remit', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300015, $output['code']);
        $this->assertEquals('No RemitOrder found', $output['msg']);
    }

    /**
     * 測試新增入款記錄的訂單號不可用
     */
    public function testRemitWithOrderNumberHasBeenUsed()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $client->request('GET', '/api/remit/entry/order_number');
        $orderNumberOutPut = json_decode($client->getResponse()->getContent(), true);
        $orderNumber = $orderNumberOutPut['ret'];

        $remitOrder = $em->find('BBDurianBundle:RemitOrder', $orderNumber);
        $remitOrder->setUsed(true);
        $em->flush();

        $parameters = [
            'order_number' => $orderNumber,
            'ancestor_id' => 3,
            'account_id' => 1,
            'bank_info_id' => 2,
            'amount' => 51,
            'method' => 4,
            'name_real' => '戰鬥民族',
            'branch' => '天下第一舞蹈會',
            'deposit_at' => '2013-10-10T12:00:00+0800',
        ];

        $client->request('POST', '/api/user/8/remit', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300024, $output['code']);
        $this->assertEquals('RemitOrder has been used', $output['msg']);
    }

    /**
     * 測試新增入款記錄未代入存款人姓名
     */
    public function testRemitWithoutNameReal()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/remit/entry/order_number');
        $orderNumberOutPut = json_decode($client->getResponse()->getContent(), true);
        $orderNumber = $orderNumberOutPut['ret'];

        $parameters = [
            'order_number' => $orderNumber,
            'ancestor_id' => 3,
            'account_id' => 1,
            'bank_info_id' => 2,
            'amount' => 51,
            'method' => 4,
            'branch' => '天下第一舞蹈會',
            'deposit_at' => '2013-10-10T12:00:00+0800',
        ];

        $client->request('POST', '/api/user/8/remit', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300006, $output['code']);
        $this->assertEquals('No name_real specified', $output['msg']);
    }

    /**
     * 測試新增入款記錄未代入存款方式
     */
    public function testRemitWithoutMethod()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/remit/entry/order_number');
        $orderNumberOutPut = json_decode($client->getResponse()->getContent(), true);
        $orderNumber = $orderNumberOutPut['ret'];

        $parameters = [
            'order_number' => $orderNumber,
            'ancestor_id' => 3,
            'account_id' => 1,
            'bank_info_id' => 2,
            'amount' => 51,
            'name_real' => '戰鬥民族',
            'branch' => '天下第一舞蹈會',
            'deposit_at' => '2013-10-10T12:00:00+0800',
        ];

        $client->request('POST', '/api/user/8/remit', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300007, $output['code']);
        $this->assertEquals('Invalid method', $output['msg']);
    }

    /**
     * 測試新增入款記錄代入的存款方式不支援
     */
    public function testRemitWithMethodUnsupported()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/remit/entry/order_number');
        $orderNumberOutPut = json_decode($client->getResponse()->getContent(), true);
        $orderNumber = $orderNumberOutPut['ret'];

        $parameters = [
            'order_number' => $orderNumber,
            'ancestor_id' => 3,
            'account_id' => 1,
            'bank_info_id' => 2,
            'amount' => 51,
            'method' => 777,
            'name_real' => '戰鬥民族',
            'branch' => '天下第一舞蹈會',
            'deposit_at' => '2013-10-10T12:00:00+0800',
        ];

        $client->request('POST', '/api/user/8/remit', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300007, $output['code']);
        $this->assertEquals('Invalid method', $output['msg']);
    }

    /**
     * 測試新增入款記錄未代入存款金額
     */
    public function testRemitWithoutAmount()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/remit/entry/order_number');
        $orderNumberOutPut = json_decode($client->getResponse()->getContent(), true);
        $orderNumber = $orderNumberOutPut['ret'];

        $parameters = [
            'order_number' => $orderNumber,
            'ancestor_id' => 3,
            'account_id' => 1,
            'bank_info_id' => 2,
            'method' => 4,
            'name_real' => '戰鬥民族',
            'branch' => '天下第一舞蹈會',
            'deposit_at' => '2013-10-10T12:00:00+0800',
        ];

        $client->request('POST', '/api/user/8/remit', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300008, $output['code']);
        $this->assertEquals('Invalid amount', $output['msg']);
    }

    /**
     * 測試新增入款記錄代入負數存款金額
     */
    public function testRemitWithAmountNegative()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/remit/entry/order_number');
        $orderNumberOutPut = json_decode($client->getResponse()->getContent(), true);
        $orderNumber = $orderNumberOutPut['ret'];

        $parameters = [
            'order_number' => $orderNumber,
            'ancestor_id' => 3,
            'account_id' => 1,
            'bank_info_id' => 2,
            'amount' => -1,
            'method' => 4,
            'name_real' => '戰鬥民族',
            'branch' => '天下第一舞蹈會',
            'deposit_at' => '2013-10-10T12:00:00+0800',
        ];

        $client->request('POST', '/api/user/8/remit', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300008, $output['code']);
        $this->assertEquals('Invalid amount', $output['msg']);
    }

    /**
     * 測試新增入款記錄代入存款金額為0
     */
    public function testRemitWithZeroAmount()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/remit/entry/order_number');
        $orderNumberOutPut = json_decode($client->getResponse()->getContent(), true);
        $orderNumber = $orderNumberOutPut['ret'];

        $parameters = [
            'order_number' => $orderNumber,
            'ancestor_id' => 3,
            'account_id' => 1,
            'bank_info_id' => 2,
            'amount' => 0,
            'method' => 4,
            'name_real' => '戰鬥民族',
            'branch' => '天下第一舞蹈會',
            'deposit_at' => '2013-10-10T12:00:00+0800',
        ];

        $client->request('POST', '/api/user/8/remit', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300008, $output['code']);
        $this->assertEquals('Invalid amount', $output['msg']);
    }

    /**
     * 測試新增入款記錄未代入存款時間
     */
    public function testRemitWithoutDepositAt()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/remit/entry/order_number');
        $orderNumberOutPut = json_decode($client->getResponse()->getContent(), true);
        $orderNumber = $orderNumberOutPut['ret'];

        $parameters = [
            'order_number' => $orderNumber,
            'ancestor_id' => 3,
            'account_id' => 1,
            'bank_info_id' => 2,
            'amount' => 51,
            'method' => 4,
            'name_real' => '戰鬥民族',
            'branch' => '天下第一舞蹈會',
        ];

        $client->request('POST', '/api/user/8/remit', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300009, $output['code']);
        $this->assertEquals('Invalid deposit_at', $output['msg']);
    }

    /**
     * 測試新增入款記錄代入不合法存款時間
     */
    public function testRemitWithInvalidDepositAt()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/remit/entry/order_number');
        $orderNumberOutput = json_decode($client->getResponse()->getContent(), true);
        $orderNumber = $orderNumberOutput['ret'];

        $parameters = [
            'order_number' => $orderNumber,
            'ancestor_id' => 3,
            'account_id' => 1,
            'bank_info_id' => 2,
            'amount' => 51,
            'method' => 4,
            'name_real' => '戰鬥民族',
            'branch' => '天下第一舞蹈會',
            'deposit_at' => '2015-08-11 065:55:00'
        ];

        $client->request('POST', '/api/user/8/remit', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300009, $output['code']);
        $this->assertEquals('Invalid deposit_at', $output['msg']);
    }

    /**
     * 測試新增入款記錄優惠金額不合法(代入空值)
     */
    public function testRemitDiscountInvalid()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/remit/entry/order_number');
        $orderNumberOutPut = json_decode($client->getResponse()->getContent(), true);
        $orderNumber = $orderNumberOutPut['ret'];

        $parameters = [
            'order_number' => $orderNumber,
            'ancestor_id' => 3,
            'account_id' => 1,
            'bank_info_id' => 2,
            'amount' => 51,
            'method' => 4,
            'name_real' => '戰鬥民族',
            'discount' => '',
            'branch' => '天下第一舞蹈會',
            'deposit_at' => '2013-10-10T12:00:00+0800',
        ];

        $client->request('POST', '/api/user/8/remit', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300026, $output['code']);
        $this->assertEquals('Invalid discount', $output['msg']);
    }

    /**
     * 測試新增入款記錄其他優惠金額不合法(代入非浮點數)
     */
    public function testRemitOtherDiscountInvalid()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/remit/entry/order_number');
        $orderNumberOutPut = json_decode($client->getResponse()->getContent(), true);
        $orderNumber = $orderNumberOutPut['ret'];

        $parameters = [
            'order_number' => $orderNumber,
            'ancestor_id' => 3,
            'account_id' => 1,
            'bank_info_id' => 2,
            'amount' => 51,
            'method' => 4,
            'name_real' => '戰鬥民族',
            'other_discount' => '0x265FBC',
            'branch' => '天下第一舞蹈會',
            'deposit_at' => '2013-10-10T12:00:00+0800',
        ];

        $client->request('POST', '/api/user/8/remit', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300027, $output['code']);
        $this->assertEquals('Invalid other_discount', $output['msg']);
    }

    /**
     * 測試新增入款記錄存款金額超過Cash上限
     */
    public function testRemitAmountExceedMaxValue()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/remit/entry/order_number');
        $orderNumberOutPut = json_decode($client->getResponse()->getContent(), true);
        $orderNumber = $orderNumberOutPut['ret'];

        $parameters = [
            'order_number' => $orderNumber,
            'ancestor_id' => 3,
            'account_id' => 1,
            'bank_info_id' => 2,
            'amount' => 10000000001,
            'method' => 4,
            'name_real' => '戰鬥民族',
            'discount' => '456',
            'other_discount' => '123',
            'branch' => '天下第一舞蹈會',
            'deposit_at' => '2013-10-10T12:00:00+0800',
        ];

        $client->request('POST', '/api/user/8/remit', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300050, $output['code']);
        $this->assertEquals('Amount exceed the MAX value', $output['msg']);
    }

    /**
     * 測試新增入款記錄存款優惠超過Cash上限
     */
    public function testRemitDiscountExceedMaxValue()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/remit/entry/order_number');
        $orderNumberOutPut = json_decode($client->getResponse()->getContent(), true);
        $orderNumber = $orderNumberOutPut['ret'];

        $parameters = [
            'order_number' => $orderNumber,
            'ancestor_id' => 3,
            'account_id' => 1,
            'bank_info_id' => 2,
            'amount' => 1,
            'method' => 4,
            'name_real' => '戰鬥民族',
            'discount' => '10000000001',
            'other_discount' => '123',
            'branch' => '天下第一舞蹈會',
            'deposit_at' => '2013-10-10T12:00:00+0800',
        ];

        $client->request('POST', '/api/user/8/remit', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300041, $output['code']);
        $this->assertEquals('Discount exceed the MAX value', $output['msg']);
    }

    /**
     * 測試新增入款記錄其他優惠超過Cash上限
     */
    public function testRemitOtherDiscountExceedMaxValue()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/remit/entry/order_number');
        $orderNumberOutPut = json_decode($client->getResponse()->getContent(), true);
        $orderNumber = $orderNumberOutPut['ret'];

        $parameters = [
            'order_number' => $orderNumber,
            'ancestor_id' => 3,
            'account_id' => 1,
            'bank_info_id' => 2,
            'amount' => 1,
            'method' => 4,
            'name_real' => '戰鬥民族',
            'discount' => '456',
            'other_discount' => '10000000001',
            'branch' => '天下第一舞蹈會',
            'deposit_at' => '2013-10-10T12:00:00+0800',
        ];

        $client->request('POST', '/api/user/8/remit', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300042, $output['code']);
        $this->assertEquals('Other discount exceed the MAX value', $output['msg']);
    }

    /**
     * 測試新增入款記錄找不到會員層級
     */
    public function testRemitButUserLevelNotFound()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/remit/entry/order_number');
        $orderNumberOutPut = json_decode($client->getResponse()->getContent(), true);
        $orderNumber = $orderNumberOutPut['ret'];

        $parameters = [
            'order_number' => $orderNumber,
            'ancestor_id' => 3,
            'account_id' => 1,
            'bank_info_id' => 2,
            'amount' => 51,
            'method' => 4,
            'name_real' => '戰鬥民族',
            'branch' => '天下第一舞蹈會',
            'deposit_at' => '2013-10-10T12:00:00+0800',
        ];

        $client->request('POST', '/api/user/2/remit', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300063, $output['code']);
        $this->assertEquals('No UserLevel found', $output['msg']);
    }

    /**
     * 測試取得入款記錄
     */
    public function testGetRemitEntry()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $client->request('GET', '/api/remit/entry/1');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $entryId = $output['ret']['id'];
        $remitEntry = $em->find('BBDurianBundle:RemitEntry', $entryId);

        $this->assertEquals($remitEntry->getRemitAccountId(), $output['ret']['remit_account_id']);
        $this->assertEquals($remitEntry->getOrderNumber(), $output['ret']['order_number']);
        $this->assertEquals($remitEntry->getOldOrderNumber(), $output['ret']['old_order_number']);
        $this->assertEquals($remitEntry->getUserId(), $output['ret']['user_id']);
        $this->assertEquals($remitEntry->getLevelId(), $output['ret']['level_id']);
        $this->assertEquals($remitEntry->getAncestorId(), $output['ret']['ancestor_id']);
        $this->assertEquals($remitEntry->getBankInfoId(), $output['ret']['bank_info_id']);
        $this->assertEquals($remitEntry->getNameReal(), $output['ret']['name_real']);
        $this->assertEquals($remitEntry->getMethod(), $output['ret']['method']);
        $this->assertEquals($remitEntry->getBranch(), $output['ret']['branch']);
        $this->assertEquals($remitEntry->getAmount(), $output['ret']['amount']);
        $this->assertEquals($remitEntry->getDiscount(), $output['ret']['discount']);
        $this->assertEquals($remitEntry->getOtherDiscount(), $output['ret']['other_discount']);
        $this->assertEquals($remitEntry->getActualOtherDiscount(), $output['ret']['actual_other_discount']);
        $this->assertEquals($remitEntry->getAutoRemitId(), $output['ret']['auto_remit_id']);
        $this->assertFalse($output['ret']['abandon_discount']);
        $this->assertFalse($output['ret']['auto_confirm']);
        $this->assertEquals($remitEntry->getStatus(), $output['ret']['status']);
        $this->assertEquals($remitEntry->getOperator(), $output['ret']['operator']);
        $this->assertEquals($remitEntry->getCreatedAt()->format(\DateTime::ISO8601), $output['ret']['created_at']);
        $this->assertEquals($remitEntry->getDepositAt()->format(\DateTime::ISO8601), $output['ret']['deposit_at']);
        $this->assertNull($output['ret']['confirm_at']);
        $this->assertEquals($remitEntry->getDuration(), $output['ret']['duration']);
    }

    /**
     * 測試取得入款記錄不存在
     */
    public function testGetRemitEntryNotExist()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/remit/entry/5555');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300012, $output['code']);
        $this->assertEquals('No RemitEntry found', $output['msg']);
    }

    /**
     * 測試變更入款記錄
     */
    public function testSetRemitEntry()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $discount = 100;
        $otherDiscount = 200;
        $actualOtherDiscount = 300;
        $parameters = [
            'discount' => $discount,
            'other_discount' => $otherDiscount,
            'actual_other_discount' => $actualOtherDiscount,
        ];

        $client->request('PUT', '/api/remit/entry/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $entryId = $output['ret']['id'];
        $remitEntry = $em->find('BBDurianBundle:RemitEntry', $entryId);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($remitEntry->getRemitAccountId(), $output['ret']['remit_account_id']);
        $this->assertEquals($remitEntry->getOrderNumber(), $output['ret']['order_number']);
        $this->assertEquals($remitEntry->getOldOrderNumber(), $output['ret']['old_order_number']);
        $this->assertEquals($remitEntry->getUserId(), $output['ret']['user_id']);
        $this->assertEquals($remitEntry->getLevelId(), $output['ret']['level_id']);
        $this->assertEquals($remitEntry->getAncestorId(), $output['ret']['ancestor_id']);
        $this->assertEquals($remitEntry->getBankInfoId(), $output['ret']['bank_info_id']);
        $this->assertEquals($remitEntry->getNameReal(), $output['ret']['name_real']);
        $this->assertEquals($remitEntry->getMethod(), $output['ret']['method']);
        $this->assertEquals($remitEntry->getBranch(), $output['ret']['branch']);
        $this->assertEquals($remitEntry->getAmount(), $output['ret']['amount']);
        $this->assertEquals($remitEntry->getDiscount(), $output['ret']['discount']);
        $this->assertEquals($remitEntry->getOtherDiscount(), $output['ret']['other_discount']);
        $this->assertEquals($remitEntry->getActualOtherDiscount(), $output['ret']['actual_other_discount']);
        $this->assertEquals($remitEntry->getStatus(), $output['ret']['status']);
        $this->assertEquals($remitEntry->getOperator(), $output['ret']['operator']);
        $this->assertEquals($remitEntry->getCreatedAt()->format(\DateTime::ISO8601), $output['ret']['created_at']);
        $this->assertEquals($remitEntry->getDepositAt()->format(\DateTime::ISO8601), $output['ret']['deposit_at']);
        $this->assertEquals($remitEntry->getDuration(), $output['ret']['duration']);

        // 檢查LogOperation
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remit_entry', $logOp->getTableName());
        $this->assertEquals("@id:$entryId", $logOp->getMajorKey());

        $message = [
            "@discount:0=>$discount",
            "@other_discount:0=>$otherDiscount",
            "@actual_other_discount:5=>$actualOtherDiscount",
        ];
        $this->assertEquals(implode(', ', $message), $logOp->getMessage());
    }

    /**
     * 測試變更入款記錄狀態
     */
    public function testSetStatusOfRemitEntry()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 1);

        $this->assertEquals($remitEntry->getStatus(), RemitEntry::UNCONFIRM);
        $this->assertNull($remitEntry->getConfirmAt());

        // 建立時間三天內才可修改狀態
        $now = new \DateTime('now');
        $fiveMinAgo = $now->sub(new \DateInterval('PT5M'))->format('YmdHis');
        $remitEntry->setCreatedAt($fiveMinAgo);
        $em->flush();

        $parameters = ['status' => RemitEntry::CANCEL];

        $client->request('PUT', '/api/remit/entry/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $entryId = $output['ret']['id'];
        $em->refresh($remitEntry);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($remitEntry->getStatus(), $output['ret']['status']);
        $this->assertEquals($remitEntry->getConfirmAt()->format(\DateTime::ISO8601), $output['ret']['confirm_at']);

        // 檢查LogOperation
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remit_entry', $logOp->getTableName());
        $this->assertEquals("@id:$entryId", $logOp->getMajorKey());

        $message = ["@status:" . RemitEntry::UNCONFIRM . "=>" . RemitEntry::CANCEL];
        $this->assertEquals(implode(', ', $message), $logOp->getMessage());
    }

    /**
     * 測試設定未處理的自動認款公司入款的狀態為取消
     */
    public function testSetUnconfirmAutoConfirmRemitEntryStatusWithCancel()
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
        $response->setContent('{"success": true}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);

        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 9);

        $this->assertEquals($remitEntry->getStatus(), RemitEntry::UNCONFIRM);
        $this->assertNull($remitEntry->getConfirmAt());

        // 建立時間三天內才可修改狀態
        $now = new \DateTime('now');
        $fiveMinAgo = $now->sub(new \DateInterval('PT5M'))->format('YmdHis');
        $remitEntry->setCreatedAt($fiveMinAgo);
        $em->flush();

        $parameters = ['status' => RemitEntry::CANCEL];
        $client->request('PUT', '/api/remit/entry/9', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $em->refresh($remitEntry);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(9, $output['ret']['id']);
        $this->assertEquals(RemitEntry::CANCEL, $output['ret']['status']);
        $this->assertEquals($remitEntry->getConfirmAt()->format(\DateTime::ISO8601), $output['ret']['confirm_at']);

        // 檢查 DB 資料
        $this->assertEquals(RemitEntry::CANCEL, $remitEntry->getStatus());
        $this->assertNotNull($remitEntry->getConfirmAt());

        // 檢查 LogOperation
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remit_entry', $logOp->getTableName());
        $this->assertEquals('@id:9', $logOp->getMajorKey());

        $message = ['@status:' . RemitEntry::UNCONFIRM . '=>' . RemitEntry::CANCEL];
        $this->assertEquals(implode(', ', $message), $logOp->getMessage());

        // 檢查 log 是否存在
        $this->assertFileExists($this->logPath);

        // 檢查 log 內容
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $logMsg = 'payment.https.s04.tonglueyun.com "POST /authority/system/api/revoke_order/" ' .
            '"HEADER: " "REQUEST: {"apikey":"******","id":"8706073"}" "RESPONSE: {"success": true}';
        $this->assertContains($logMsg, $results[0]);
    }

    /**
     * 測試設定取消的自動認款公司入款的狀態為未處理
     */
    public function testSetCancelAutoConfirmRemitEntryStatusWithUnconfirm()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $response = new Response();
        $response->setContent('{"success": true}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $mockContainer = $this->getMockBuilder(get_class($this->getContainer()))
            ->setMethods(['getParameter'])
            ->getMock();

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn('payment.https.s04.tonglueyun.com');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);

        // 建立時間三天內才可修改狀態
        $now = new \DateTime('now');
        $fiveMinAgo = $now->sub(new \DateInterval('PT5M'))->format('YmdHis');

        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 9);
        $remitEntry->setCreatedAt($fiveMinAgo);
        $em->flush();

        // 先將狀態設定為取消
        $parameters = ['status' => RemitEntry::CANCEL];
        $client->request('PUT', '/api/remit/entry/9', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 設定取消的訂單狀態為未處理
        $parameters = ['status' => RemitEntry::UNCONFIRM];
        $client->request('PUT', '/api/remit/entry/9', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $em->refresh($remitEntry);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(9, $output['ret']['id']);
        $this->assertEquals(RemitEntry::UNCONFIRM, $output['ret']['status']);
        $this->assertEquals($remitEntry->getConfirmAt()->format(\DateTime::ISO8601), $output['ret']['confirm_at']);

        // 檢查 DB 資料
        $this->assertEquals(RemitEntry::UNCONFIRM, $remitEntry->getStatus());
        $this->assertNotNull($remitEntry->getConfirmAt());

        // 檢查 LogOperation
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('remit_entry', $logOp->getTableName());
        $this->assertEquals('@id:9', $logOp->getMajorKey());

        $message = ['@status:' . RemitEntry::CANCEL . '=>' . RemitEntry::UNCONFIRM];
        $this->assertEquals(implode(', ', $message), $logOp->getMessage());

        // 檢查 log 是否存在
        $this->assertFileExists($this->logPath);

        // 檢查設定為未處理時是否有 log
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $this->assertEquals(2, count($results));
    }

    /**
     * 測試設定未處理的自動認款公司入款的狀態為取消但公司入款帳號不存在
     */
    public function testSetUnconfirmAutoConfirmRemitEntryStatusWithCancelButRemitAccountNotFound()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        // 將公司入款帳號設定為不存在的帳號
        $sql = 'UPDATE remit_entry SET remit_account_id = 999 WHERE id = 9';
        $em->getConnection()->executeUpdate($sql);

        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 9);

        // 建立時間三天內才可修改狀態
        $now = new \DateTime('now');
        $fiveMinAgo = $now->sub(new \DateInterval('PT5M'))->format('YmdHis');
        $remitEntry->setCreatedAt($fiveMinAgo);
        $em->flush();

        $parameters = ['status' => RemitEntry::CANCEL];
        $client->request('PUT', '/api/remit/entry/9', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300002, $output['code']);
        $this->assertEquals('No RemitAccount found', $output['msg']);

        // 檢查狀態是否為未處理
        $em->refresh($remitEntry);
        $this->assertEquals(RemitEntry::UNCONFIRM, $remitEntry->getStatus());
    }

    /**
     * 測試設定未處理的自動認款公司入款的狀態為取消但自動認款不支援此廳
     */
    public function testSetUnconfirmAutoConfirmRemitEntryStatusWithCancelButDomainIsNotSupportedByAutoConfirm()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        // 刪除廳的自動認款相關設定
        $domainAutoRemit = $em->find('BBDurianBundle:DomainAutoRemit', ['domain' => 2, 'autoRemitId' => 1]);
        $em->remove($domainAutoRemit);

        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 9);

        // 建立時間三天內才可修改狀態
        $now = new \DateTime('now');
        $fiveMinAgo = $now->sub(new \DateInterval('PT5M'))->format('YmdHis');
        $remitEntry->setCreatedAt($fiveMinAgo);
        $em->flush();

        $parameters = ['status' => RemitEntry::CANCEL];
        $client->request('PUT', '/api/remit/entry/9', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150870019, $output['code']);
        $this->assertEquals('Domain is not supported by AutoConfirm', $output['msg']);

        // 檢查狀態是否為未處理
        $em->refresh($remitEntry);
        $this->assertEquals(RemitEntry::UNCONFIRM, $remitEntry->getStatus());
    }

    /**
     * 測試設定未處理的自動認款公司入款的狀態為取消但自動認款公司入款相關資料不存在
     */
    public function testSetUnconfirmAutoConfirmRemitEntryStatusWithCancelButRemitAutoConfirmNotFound()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 8);

        // 建立時間三天內才可修改狀態
        $now = new \DateTime('now');
        $fiveMinAgo = $now->sub(new \DateInterval('PT5M'))->format('YmdHis');
        $remitEntry->setCreatedAt($fiveMinAgo);
        $em->flush();

        $parameters = ['status' => RemitEntry::CANCEL];
        $client->request('PUT', '/api/remit/entry/8', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150870020, $output['code']);
        $this->assertEquals('No RemitAutoConfirm found', $output['msg']);

        // 檢查狀態是否為未處理
        $em->refresh($remitEntry);
        $this->assertEquals(RemitEntry::UNCONFIRM, $remitEntry->getStatus());
    }

    /**
     * 測試設定未處理的BB自動認款公司入款的狀態為取消
     */
    public function testSetUnconfirmBbAutoConfirmRemitEntryStatusWithCancel()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 8);

        // 建立時間三天內才可修改狀態
        $now = new \DateTime('now');
        $fiveMinAgo = $now->sub(new \DateInterval('PT5M'))->format('YmdHis');
        $remitEntry->setCreatedAt($fiveMinAgo);
        $em->flush();

        $sql = 'UPDATE remit_entry SET auto_remit_id = 2 WHERE id = 8';
        $em->getConnection()->executeUpdate($sql);

        $sql = 'UPDATE remit_account SET auto_remit_id = 2 WHERE id = 5';
        $em->getConnection()->executeUpdate($sql);

        $parameters = ['status' => RemitEntry::CANCEL];
        $client->request('PUT', '/api/remit/entry/8', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['id']);
        $this->assertEquals(2, $output['ret']['status']);

        // 檢查 DB 資料
        $em->refresh($remitEntry);
        $this->assertEquals(RemitEntry::CANCEL, $remitEntry->getStatus());

        // 檢查 LogOperation
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remit_entry', $logOp->getTableName());
        $this->assertEquals('@id:8', $logOp->getMajorKey());
        $this->assertEquals('@status:0=>2', $logOp->getMessage());
    }

    /**
     * 測試同略雲自動認款帳號改為公司入款後設定自動認款訂單入款狀態為取消
     */
    public function testSetRemitEntryWithCancelAfterSetTongLueYunRemitAccountDisableAutoConfirm()
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
        $response->setContent('{"success": true}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $client = $this->createClient();
        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);

        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 9);

        $remitAcoount = $em->find('BBDurianBundle:RemitAccount', $remitEntry->getRemitAccountId());
        $remitAcoount->setAutoConfirm(false);
        $remitAcoount->setAutoRemitId(0);

        // 建立時間三天內才可修改狀態
        $now = new \DateTime('now');
        $fiveMinAgo = $now->sub(new \DateInterval('PT5M'))->format('YmdHis');
        $remitEntry->setCreatedAt($fiveMinAgo);
        $em->flush();

        $parameters = ['status' => RemitEntry::CANCEL];
        $client->request('PUT', '/api/remit/entry/9', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 檢查 log 是否存在
        $this->assertFileExists($this->logPath);

        // 檢查 log 內容
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $logMsg = 'payment.https.s04.tonglueyun.com "POST /authority/system/api/revoke_order/" ' .
            '"HEADER: " "REQUEST: {"apikey":"******","id":"8706073"}" "RESPONSE: {"success": true}"';
        $this->assertContains($logMsg, $results[0]);

        // 檢查狀態是否為未處理
        $em->refresh($remitEntry);
        $this->assertEquals(RemitEntry::CANCEL, $remitEntry->getStatus());
    }

    /**
     * 測試BB自動認款帳號改為公司入款後設定自動認款訂單入款狀態為取消
     */
    public function testSetRemitEntryWithCancelAfterSetBBRemitAccountDisableAutoConfirm()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 8);

        $remitAccount = $em->find('BBDurianBundle:RemitAccount', $remitEntry->getRemitAccountId());
        $remitAccount->setAutoConfirm(false);
        $remitAccount->setAutoRemitId(0);

        // 建立時間三天內才可修改狀態
        $now = new \DateTime('now');
        $fiveMinAgo = $now->sub(new \DateInterval('PT5M'))->format('YmdHis');
        $remitEntry->setCreatedAt($fiveMinAgo);
        $em->flush();

        $sql = 'UPDATE remit_entry SET auto_remit_id = 2 WHERE id = 8';
        $em->getConnection()->executeUpdate($sql);

        $sql = 'UPDATE remit_account SET auto_remit_id = 2 WHERE id = 5';
        $em->getConnection()->executeUpdate($sql);

        $parameters = ['status' => RemitEntry::CANCEL];
        $client->request('PUT', '/api/remit/entry/8', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['id']);
        $this->assertEquals(2, $output['ret']['status']);

        // 檢查 DB 資料
        $em->refresh($remitEntry);
        $this->assertEquals(RemitEntry::CANCEL, $remitEntry->getStatus());

        // 檢查 LogOperation
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remit_entry', $logOp->getTableName());
        $this->assertEquals('@id:8', $logOp->getMajorKey());
        $this->assertEquals('@status:0=>2', $logOp->getMessage());
    }

    /**
     * 測試設定未處理的自動認款公司入款的狀態為取消但自動認款連線異常
     */
    public function testSetUnconfirmAutoConfirmRemitEntryStatusWithCancelButAutoConfirmConnectionError()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
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

        $client = $this->createClient();
        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);

        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 9);

        // 建立時間三天內才可修改狀態
        $now = new \DateTime('now');
        $fiveMinAgo = $now->sub(new \DateInterval('PT5M'))->format('YmdHis');
        $remitEntry->setCreatedAt($fiveMinAgo);
        $em->flush();

        $parameters = ['status' => RemitEntry::CANCEL];
        $client->request('PUT', '/api/remit/entry/9', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150870021, $output['code']);
        $this->assertEquals('Auto Confirm connection failure', $output['msg']);

        // 檢查 log 是否不存在
        $this->assertFileNotExists($this->logPath);

        // 檢查狀態是否為未處理
        $em->refresh($remitEntry);
        $this->assertEquals(RemitEntry::UNCONFIRM, $remitEntry->getStatus());
    }

    /**
     * 測試設定未處理的自動認款公司入款的狀態為取消但自動認款連線失敗
     */
    public function testSetUnconfirmAutoConfirmRemitEntryStatusWithCancelButAutoConfirmConnectionFailure()
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
        $response->addHeader('HTTP/1.1 499');
        $response->addHeader('Content-Type: application/json');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $client = $this->createClient();
        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);

        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 9);

        // 建立時間三天內才可修改狀態
        $now = new \DateTime('now');
        $fiveMinAgo = $now->sub(new \DateInterval('PT5M'))->format('YmdHis');
        $remitEntry->setCreatedAt($fiveMinAgo);
        $em->flush();

        $parameters = ['status' => RemitEntry::CANCEL];
        $client->request('PUT', '/api/remit/entry/9', $parameters);

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

        $logMsg = 'payment.https.s04.tonglueyun.com "POST /authority/system/api/revoke_order/" ' .
            '"HEADER: " "REQUEST: {"apikey":"******","id":"8706073"}" "RESPONSE: "';
        $this->assertContains($logMsg, $results[0]);

        // 檢查狀態是否為未處理
        $em->refresh($remitEntry);
        $this->assertEquals(RemitEntry::UNCONFIRM, $remitEntry->getStatus());
    }

    /**
     * 測試設定未處理的自動認款公司入款的狀態為取消但未指定自動認款返回參數
     */
    public function testSetUnconfirmAutoConfirmRemitEntryStatusWithCancelButNoAutoConfirmReturnParameterSpecified()
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
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $client = $this->createClient();
        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);

        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 9);

        // 建立時間三天內才可修改狀態
        $now = new \DateTime('now');
        $fiveMinAgo = $now->sub(new \DateInterval('PT5M'))->format('YmdHis');
        $remitEntry->setCreatedAt($fiveMinAgo);
        $em->flush();

        $parameters = ['status' => RemitEntry::CANCEL];
        $client->request('PUT', '/api/remit/entry/9', $parameters);

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

        $logMsg = 'payment.https.s04.tonglueyun.com "POST /authority/system/api/revoke_order/" ' .
            '"HEADER: " "REQUEST: {"apikey":"******","id":"8706073"}" "RESPONSE: "';
        $this->assertContains($logMsg, $results[0]);

        // 檢查狀態是否為未處理
        $em->refresh($remitEntry);
        $this->assertEquals(RemitEntry::UNCONFIRM, $remitEntry->getStatus());
    }

    /**
     * 測試設定未處理的自動認款公司入款的狀態為取消但訂單不存在
     */
    public function testSetUnconfirmAutoConfirmRemitEntryStatusWithCancelButOrderIdNotExist()
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
        $response->setContent('{"message": "order id doesn\'t exist.", "success": false}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $client = $this->createClient();
        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);

        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 9);

        // 建立時間三天內才可修改狀態
        $now = new \DateTime('now');
        $fiveMinAgo = $now->sub(new \DateInterval('PT5M'))->format('YmdHis');
        $remitEntry->setCreatedAt($fiveMinAgo);
        $em->flush();

        $parameters = ['status' => RemitEntry::CANCEL];
        $client->request('PUT', '/api/remit/entry/9', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150870024, $output['code']);
        $this->assertRegExp('/order id doesn\'t exist./', $output['msg']);

        // 檢查 log 是否存在
        $this->assertFileExists($this->logPath);

        // 檢查 log 內容
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);

        $logMsg = "/order id doesn't exist./";
        $this->assertRegExp($logMsg, $results[0]);

        // 檢查狀態是否為未處理
        $em->refresh($remitEntry);
        $this->assertEquals(RemitEntry::UNCONFIRM, $remitEntry->getStatus());
    }

    /**
     * 測試設定未處理的自動認款公司入款的狀態為取消但自動認款失敗
     */
    public function testSetUnconfirmAutoConfirmRemitEntryStatusWithCancelButAutoConfirmFailed()
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
        $response->setContent('{"success": false}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $client = $this->createClient();
        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);

        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 9);

        // 建立時間三天內才可修改狀態
        $now = new \DateTime('now');
        $fiveMinAgo = $now->sub(new \DateInterval('PT5M'))->format('YmdHis');
        $remitEntry->setCreatedAt($fiveMinAgo);
        $em->flush();

        $parameters = ['status' => RemitEntry::CANCEL];
        $client->request('PUT', '/api/remit/entry/9', $parameters);

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

        $logMsg = 'payment.https.s04.tonglueyun.com "POST /authority/system/api/revoke_order/" ' .
            '"HEADER: " "REQUEST: {"apikey":"******","id":"8706073"}" "RESPONSE: {"success": false}"';
        $this->assertContains($logMsg, $results[0]);

        // 檢查狀態是否為未處理
        $em->refresh($remitEntry);
        $this->assertEquals(RemitEntry::UNCONFIRM, $remitEntry->getStatus());
    }

    /**
     * 測試變更建立時間早於三天以前的入款記錄狀態
     */
    public function testSetStatusOfRemitEntryCreatedAtBeforeThreeDaysAgo()
    {
        $client = $this->createClient();

        $parameters = ['status' => RemitEntry::CANCEL];

        $client->request('PUT', '/api/remit/entry/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300048, $output['code']);
        $this->assertEquals('Can not modify status of expired entry', $output['msg']);
    }

    /**
     * 測試變更已確認的入款記錄狀態
     */
    public function testSetRemitEntryAlreadyConfirmed()
    {
        $client = $this->createClient();
        $parameters = ['status' => RemitEntry::CANCEL];
        $client->request('PUT', '/api/remit/entry/5', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300013, $output['code']);
        $this->assertEquals('Can not modify confirmed entry', $output['msg']);

        // 噴錯不會寫LogOperation
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $logOp = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertNull($logOp);
    }

    /**
     * 測試變更入款記錄狀態代入狀態不合法
     */
    public function testSetRemitEntryWithInvalidStatus()
    {
        $client = $this->createClient();

        $parameters = ['status' => 789,];
        $client->request('PUT', '/api/remit/entry/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300010, $output['code']);
        $this->assertEquals('Invalid status', $output['msg']);
    }

    /**
     * 測試用變更入款記錄API來確認入款
     */
    public function testSetRemitEntryWithConfirmStatus()
    {
        $client = $this->createClient();

        $parameters = ['status' => RemitEntry::CONFIRM];
        $client->request('PUT', '/api/remit/entry/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300010, $output['code']);
        $this->assertEquals('Invalid status', $output['msg']);
    }

    /**
     * 測試變更入款記錄未修改狀態則不會檢查狀態的正確性
     */
    public function testSetRemitEntryWithoutStatus()
    {
        $client = $this->createClient();
        $parameters = [
            'operator' => 'woosa',
            'discount' => 10,
            'other_discount' => 5,
            'actual_other_discount' => 15,
        ];
        $client->request('PUT', '/api/remit/entry/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(10, $output['ret']['discount']);
        $this->assertEquals(5, $output['ret']['other_discount']);
        $this->assertEquals(15, $output['ret']['actual_other_discount']);
        $this->assertEquals(RemitEntry::UNCONFIRM, $output['ret']['status']);
        // operator不會被修改
        $this->assertEquals('', $output['ret']['operator']);

        // 檢查LogOperation
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $logOp = $em->find('BBDurianBundle:LogOperation', 1);

        $message = [
            "@discount:0=>10",
            "@other_discount:0=>5",
            "@actual_other_discount:5=>15",
        ];
        $this->assertEquals(implode(', ', $message), $logOp->getMessage());
    }

    /**
     * 測試變更不存在的入款記錄狀態
     */
    public function testSetRemitEntryNotExist()
    {
        $client = $this->createClient();

        $parameters = ['status' => RemitEntry::CANCEL];
        $client->request('PUT', '/api/remit/entry/777', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300012, $output['code']);
        $this->assertEquals('No RemitEntry found', $output['msg']);
    }

    /**
     * 測試修改公司入款記錄代入錯誤的存款優惠
     */
    public function testSetRemitEntryWithInvalidDiscount()
    {
        $client = $this->createClient();
        $parameters = ['discount' => '123doremi'];
        $client->request('PUT', '/api/remit/entry/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300026, $output['code']);
        $this->assertEquals('Invalid discount', $output['msg']);
    }

    /**
     * 測試修改公司入款記錄代入錯誤的其他優惠
     */
    public function testSetRemitEntryWithInvalidOtherDiscount()
    {
        $client = $this->createClient();
        $parameters = ['other_discount' => 'doremi456'];
        $client->request('PUT', '/api/remit/entry/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300027, $output['code']);
        $this->assertEquals('Invalid other_discount', $output['msg']);
    }

    /**
     * 測試修改公司入款記錄代入錯誤的實際優惠
     */
    public function testSetRemitEntryWithInvalidActualOtherDiscount()
    {
        $client = $this->createClient();
        $parameters = ['actual_other_discount' => 'doremi456'];
        $client->request('PUT', '/api/remit/entry/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300028, $output['code']);
        $this->assertEquals('Invalid actual_other_discount', $output['msg']);
    }

    /**
     * 測試照入款記錄出款給存款會員
     */
    public function testConfirmRemit()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();
        $redis = $this->getContainer()->get('snc_redis.default_client');

        // 先取出原本的額度
        $cashOrigin = $em->find('BBDurianBundle:Cash', 1);
        $balanceOrigin = $cashOrigin->getBalance();

        $remitEntry1 = $em->find('BBDurianBundle:RemitEntry', 1);
        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 2);

        // 建立時間三天內才可修改狀態
        $now = new \DateTime('now');
        $fiveMinAgo = $now->sub(new \DateInterval('PT5M'))->format('YmdHis');
        $remitEntry1->setCreatedAt($fiveMinAgo);
        $remitEntry->setCreatedAt($fiveMinAgo);
        $em->flush();

        $params = [
            'operator' => 'odin',
            'is_discount' => 1,
            'transcribe_entry_id' => 1,
            'amount' => 1000,
            'fee' => 30
        ];
        $client->request('POST', '/api/remit/entry/2/confirm', $params);

        //跑背景程式讓queue被消化
        $cashPoper = new Poper();
        $cashPoper->runPop($this->getContainer(), 'cash');

        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cashOrigin);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $entryId = $output['ret']['id'];
        $em->refresh($remitEntry);

        $this->assertEquals($remitEntry->getStatus(), $output['ret']['status']);
        $this->assertEquals($remitEntry->getOperator(), $output['ret']['operator']);
        $this->assertEquals($remitEntry->getConfirmAt()->format(\DateTime::ISO8601), $output['ret']['confirm_at']);
        $this->assertEquals($remitEntry->getAmountEntryId(), $output['ret']['amount_entry_id']);
        $this->assertEquals($remitEntry->getDiscountEntryId(), $output['ret']['discount_entry_id']);
        $this->assertEquals($remitEntry->getOtherDiscountEntryId(), $output['ret']['other_discount_entry_id']);

        //檢查回傳的操作明細
        $this->assertEquals(4, $output['ret']['amount_entry']['id']);
        $this->assertEquals(1, $output['ret']['amount_entry']['cash_id']);
        $this->assertEquals(1038, $output['ret']['amount_entry']['opcode']);
        $this->assertEquals(5, $output['ret']['amount_entry']['amount']);
        $this->assertEquals(215, $output['ret']['amount_entry']['balance']);
        $this->assertEquals('操作者： odin', $output['ret']['amount_entry']['memo']);
        $this->assertEquals($remitEntry->getRemitAccountId(), $output['ret']['amount_entry']['tag']);
        $this->assertEquals($remitEntry->getRemitAccountId(), $output['ret']['amount_entry']['remit_account_id']);

        // 檢查公司入款帳號統計資料
        $remitAccountStat = $em->getRepository('BBDurianBundle:RemitAccountStat')->findOneBy([
            'remitAccount' => 1,
            'at' => date('YmdHis', mktime(0, 0, 0))
        ]);

        $this->assertNotNull($remitAccountStat);
        $this->assertEquals(1, $remitAccountStat->getCount());
        $this->assertEquals(100, $remitAccountStat->getIncome());

        // 金額是否有增加(confirm之後是照實際其他優惠欄位的值來設定其他優惠明細的優惠金額)
        $user = $em->find('BBDurianBundle:User', $remitEntry->getUserId());
        $cash = $user->getCash();
        $amount = $balanceOrigin
                + $remitEntry->getAmount()
                + $remitEntry->getDiscount()
                + $remitEntry->getActualOtherDiscount();
        $this->assertEquals($amount, $cash->getBalance());

        // 檢查存款現金明細
        $daParam = ['id' => $remitEntry->getAmountEntryId()];
        $daEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')->findOneBy($daParam);
        $this->assertEquals($remitEntry->getAmount(), $daEntry->getAmount());
        $this->assertEquals($remitEntry->getOrderNumber(), $daEntry->getRefId());
        $this->assertEquals('1036', $daEntry->getOpcode());
        $this->assertEquals('操作者： odin', $daEntry->getMemo());

        $pdwEntry = $em->getRepository('BBDurianBundle:PaymentDepositWithdrawEntry')
            ->findOneBy(['id' => $remitEntry->getAmountEntryId()]);
        $this->assertEquals($remitEntry->getRemitAccountId(), $pdwEntry->getRemitAccountId());

        // 檢查存款優惠現金明細
        $ddParam = ['id' => $remitEntry->getDiscountEntryId()];
        $ddEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')->findOneBy($ddParam);
        $this->assertEquals($remitEntry->getDiscount(), $ddEntry->getAmount());
        $this->assertEquals($remitEntry->getOrderNumber(), $ddEntry->getRefId());
        $this->assertEquals('1037', $ddEntry->getOpcode());
        $this->assertEquals('操作者： odin', $ddEntry->getMemo());

        $ddfParam = ['id' => $remitEntry->getAmountEntryId()];
        $pdwEntry = $em->getRepository('BBDurianBundle:PaymentDepositWithdrawEntry')->findOneBy($ddfParam);
        $this->assertEquals($remitEntry->getRemitAccountId(), $pdwEntry->getRemitAccountId());

        // 檢查其他優惠現金明細(confirm後其他優惠明細記錄的優惠金額是實際其他優惠)
        $odParam = ['id' => $remitEntry->getOtherDiscountEntryId()];
        $odEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')->findOneBy($odParam);

        $this->assertEquals($remitEntry->getActualOtherDiscount(), $odEntry->getAmount());
        $this->assertEquals($remitEntry->getOrderNumber(), $odEntry->getRefId());
        $this->assertEquals('1038', $odEntry->getOpcode());
        $this->assertEquals('操作者： odin', $odEntry->getMemo());

        $odfParam = ['id' => $remitEntry->getAmountEntryId()];
        $pdwEntry = $em->getRepository('BBDurianBundle:PaymentDepositWithdrawEntry')->findOneBy($odfParam);
        $this->assertEquals($remitEntry->getRemitAccountId(), $pdwEntry->getRemitAccountId());

        // 確認人工抄錄明細狀態異動
        $tce = $em->find('BBDurianBundle:TranscribeEntry', 1);
        $this->assertTrue($tce->isConfirm());
        $this->assertEquals($output['ret']['id'], $tce->getRemitEntryId());
        $this->assertEquals($output['ret']['username'], $tce->getUsername());
        $this->assertEquals($output['ret']['confirm_at'], $tce->getConfirmAt()->format(\DateTime::ISO8601));

        //confirm之後匯款明細的其他優惠欄位不會被更改
        $this->assertEquals(1, $remitEntry->getOtherDiscount());

        // 檢查使用者出入款統計資料
        $userId = $remitEntry->getUserId();
        $userStat = $em->find('BBDurianBundle:UserStat', $userId);
        $this->assertEquals(1, $userStat->getRemitCount());
        $this->assertEquals(100, $userStat->getRemitTotal());
        $this->assertEquals(100, $userStat->getRemitMax());
        $this->assertEquals(100, $userStat->getFirstDepositAmount());

        // 檢查銀行卡次數
        $remitAccountStat = $em->getRepository('BBDurianBundle:RemitAccountStat')->findOneBy([
            'remitAccount' => $remitEntry->getRemitAccountId(),
        ]);
        $this->assertEquals(1, $remitAccountStat->getCount());

        // 檢查LogOperation
        $logOp1 = $emShare->find('BBDurianBundle:LogOperation', 1);
        $msg = '@remit_count:0=>1, @remit_total:0=>100, @remit_max:0=>100.0000';
        $msg .= ', @first_deposit_at:' . $userStat->getFirstDepositAt()->format(\DateTime::ISO8601);
        $msg .= ', @first_deposit_amount:100.0000, @modified_at:';
        $this->assertEquals('user_stat', $logOp1->getTableName());
        $this->assertEquals('@user_id:8', $logOp1->getMajorKey());
        $this->assertContains($msg, $logOp1->getMessage());

        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('remit_entry', $logOp2->getTableName());
        $this->assertEquals("@id:$entryId", $logOp2->getMajorKey());
        $message = [
            '@status:'.RemitEntry::UNCONFIRM.'=>'.RemitEntry::CONFIRM,
            "@operator:incredibleS=>".$remitEntry->getOperator(),
            "@duration:".$remitEntry->getDuration(),
            "@amount_entry_id:".$daEntry->getId(),
            "@discount_entry_id:".$ddEntry->getId(),
            "@other_discount_entry_id:".$odEntry->getId(),
        ];
        $this->assertEquals(implode(', ', $message), $logOp2->getMessage());

        // 檢查每日優惠資料(第一次確認入款, 找不到資料會自動新增)
        $dailyDiscount = $em->find('BBDurianBundle:UserRemitDiscount', 3);
        $this->assertEquals(5, $dailyDiscount->getDiscount());

        // 檢查每日優惠資料(再次確認入款, 資料已存在並有正確加總)
        $params = ['operator' => 'admin'];
        $client->request('POST', '/api/remit/entry/1/confirm', $params);

        $em->refresh($dailyDiscount);
        $this->assertEquals(10, $dailyDiscount->getDiscount());

        // 檢查統計入款金額queue
        $statDeposit = json_decode($redis->rpop('stat_domain_deposit_queue'), true);

        $this->assertEquals(2, $statDeposit['domain']);
        $this->assertEquals(10, $statDeposit['amount']);
    }

    /**
     * 測試照入款記錄出款給存款會員，帶入自動認款參數
     */
    public function testConfirmRemitWithAutoConfirm()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();
        $redis = $this->getContainer()->get('snc_redis.default_client');

        // 先取出原本的額度
        $cashOrigin = $em->find('BBDurianBundle:Cash', 1);
        $balanceOrigin = $cashOrigin->getBalance();

        $now = new \DateTime('now');
        $fiveMinAgo = $now->sub(new \DateInterval('PT5M'))->format('YmdHis');

        // 建立時間三天內才可修改狀態
        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 9);
        $remitEntry->setCreatedAt($fiveMinAgo);
        $remitEntry->setDiscount(10);
        $em->flush();

        $params = [
            'operator' => 0,
            'auto_confirm' => 1,
        ];
        $client->request('POST', '/api/remit/entry/9/confirm', $params);

        // 跑背景程式讓 queue 被消化
        $cashPoper = new Poper();
        $cashPoper->runPop($this->getContainer(), 'cash');

        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cashOrigin);
        $em->refresh($remitEntry);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $entryId = $output['ret']['id'];

        $this->assertEquals($remitEntry->getStatus(), $output['ret']['status']);
        $this->assertEquals($remitEntry->getOperator(), $output['ret']['operator']);
        $this->assertEquals($remitEntry->getConfirmAt()->format(\DateTime::ISO8601), $output['ret']['confirm_at']);
        $this->assertEquals($remitEntry->getAmountEntryId(), $output['ret']['amount_entry_id']);
        $this->assertEquals($remitEntry->getDiscountEntryId(), $output['ret']['discount_entry_id']);
        $this->assertEquals($remitEntry->getOtherDiscountEntryId(), $output['ret']['other_discount_entry_id']);

        // 檢查回傳的操作明細
        $this->assertEquals(3, $output['ret']['amount_entry']['id']);
        $this->assertEquals(1, $output['ret']['amount_entry']['cash_id']);
        $this->assertEquals(1037, $output['ret']['amount_entry']['opcode']);
        $this->assertEquals(10, $output['ret']['amount_entry']['amount']);
        $this->assertEquals(1109, $output['ret']['amount_entry']['balance']);
        $this->assertEquals('操作者： 0', $output['ret']['amount_entry']['memo']);
        $this->assertEquals($remitEntry->getRemitAccountId(), $output['ret']['amount_entry']['tag']);
        $this->assertEquals($remitEntry->getRemitAccountId(), $output['ret']['amount_entry']['remit_account_id']);

        // 檢查公司入款帳號統計資料
        $remitAccountStat = $em->getRepository('BBDurianBundle:RemitAccountStat')->findOneBy([
            'remitAccount' => 6,
            'at' => date('YmdHis', mktime(0, 0, 0))
        ]);

        $this->assertNotNull($remitAccountStat);
        $this->assertEquals(2, $remitAccountStat->getCount());
        $this->assertEquals(999, $remitAccountStat->getIncome());

        // 金額是否有增加(confirm 之後是照實際其他優惠欄位的值來設定其他優惠明細的優惠金額)
        $user = $em->find('BBDurianBundle:User', $remitEntry->getUserId());
        $cash = $user->getCash();
        $amount = $balanceOrigin
            + $remitEntry->getAmount()
            + $remitEntry->getDiscount()
            + $remitEntry->getActualOtherDiscount();
        $this->assertEquals($amount, $cash->getBalance());

        // 檢查存款現金明細
        $cashEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $remitEntry->getAmountEntryId()]);
        $this->assertEquals($remitEntry->getAmount(), $cashEntry->getAmount());
        $this->assertEquals($remitEntry->getOrderNumber(), $cashEntry->getRefId());
        $this->assertEquals('1036', $cashEntry->getOpcode());
        $this->assertEquals('操作者： 0', $cashEntry->getMemo());

        $pdwEntry = $em->getRepository('BBDurianBundle:PaymentDepositWithdrawEntry')
            ->findOneBy(['id' => $remitEntry->getAmountEntryId()]);
        $this->assertEquals($remitEntry->getRemitAccountId(), $pdwEntry->getRemitAccountId());

        // 檢查存款優惠現金明細
        $discountCashEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $remitEntry->getDiscountEntryId()]);
        $this->assertEquals($remitEntry->getDiscount(), $discountCashEntry->getAmount());
        $this->assertEquals($remitEntry->getOrderNumber(), $discountCashEntry->getRefId());
        $this->assertEquals('1037', $discountCashEntry->getOpcode());
        $this->assertEquals('操作者： 0', $discountCashEntry->getMemo());

        // confirm 之後匯款明細的其他優惠欄位不會被更改
        $this->assertEquals(0, $remitEntry->getOtherDiscount());

        // 檢查使用者出入款統計資料
        $userStat = $em->find('BBDurianBundle:UserStat', $remitEntry->getUserId());
        $this->assertEquals(1, $userStat->getRemitCount());
        $this->assertEquals(999, $userStat->getRemitTotal());
        $this->assertEquals(999, $userStat->getRemitMax());
        $this->assertEquals(999, $userStat->getFirstDepositAmount());

        // 檢查 LogOperation
        $logOp1 = $emShare->find('BBDurianBundle:LogOperation', 1);
        $msg = '@remit_count:0=>1, @remit_total:0=>999, @remit_max:0=>999.0000';
        $msg .= ', @first_deposit_at:' . $userStat->getFirstDepositAt()->format(\DateTime::ISO8601);
        $msg .= ', @first_deposit_amount:999.0000, @modified_at:';
        $this->assertEquals('user_stat', $logOp1->getTableName());
        $this->assertEquals('@user_id:8', $logOp1->getMajorKey());
        $this->assertContains($msg, $logOp1->getMessage());

        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('remit_entry', $logOp2->getTableName());
        $this->assertEquals("@id:$entryId", $logOp2->getMajorKey());
        $message = [
            '@status:' . RemitEntry::UNCONFIRM . '=>' . RemitEntry::CONFIRM,
            '@operator:=>' . $remitEntry->getOperator(),
            '@duration:' . $remitEntry->getDuration(),
            '@amount_entry_id:' . $cashEntry->getId(),
            '@discount_entry_id:' . $discountCashEntry->getId(),
        ];
        $this->assertEquals(implode(', ', $message), $logOp2->getMessage());

        // 檢查通知稽核 queue
        $auditParams = json_decode($redis->rpop('audit_queue'), true);

        $this->assertEquals(9, $auditParams['remit_entry_id']);
        $this->assertEquals(8, $auditParams['user_id']);
        $this->assertEquals(1109, $auditParams['balance']);
        $this->assertEquals(999, $auditParams['amount']);
        $this->assertEquals(10, $auditParams['offer']);
        $this->assertEquals(0, $auditParams['fee']);
        $this->assertEquals('N', $auditParams['abandonsp']);
        $this->assertEquals($remitEntry->getConfirmAt()->format('Y-m-d H:i:s'), $auditParams['deposit_time']);
        $this->assertEquals(1, $auditParams['auto_confirm']);
    }

    /**
     * 測試公司入款限額 0 為無限制
     */
    public function testConfirmRemitWillNeverSuspendWhenBankLimitIsZero()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();
        $redis = $this->getContainer()->get('snc_redis.default_client');

        // 直接用 $client 的 em 調整 RemitAccount 限額，避免 sqlite affinity 把限額調整成 '0'
        $remitAccount = $client->getContainer()->get('doctrine.orm.entity_manager')
            ->find('BBDurianBundle:RemitAccount', 1);
        $remitAccount->setBankLimit('0.0000');

        // 確認入款帳號狀態以利後續測試
        $this->assertTrue($remitAccount->isEnabled());
        $this->assertFalse($remitAccount->isSuspended());

        // 先取出原本的額度
        $cashOrigin = $em->find('BBDurianBundle:Cash', 1);
        $balanceOrigin = $cashOrigin->getBalance();

        $remitEntry1 = $em->find('BBDurianBundle:RemitEntry', 1);
        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 2);

        // 建立時間三天內才可修改狀態
        $now = new \DateTime('now');
        $fiveMinAgo = $now->sub(new \DateInterval('PT5M'))->format('YmdHis');
        $remitEntry1->setCreatedAt($fiveMinAgo);
        $remitEntry->setCreatedAt($fiveMinAgo);
        $em->flush();

        $params = [
            'operator' => 'odin',
            'is_discount' => 1,
            'transcribe_entry_id' => 1,
            'amount' => 1000,
            'fee' => 30
        ];
        $client->request('POST', '/api/remit/entry/2/confirm', $params);

        // 跑背景程式讓queue被消化
        $cashPoper = new Poper();
        $cashPoper->runPop($this->getContainer(), 'cash');

        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cashOrigin);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $entryId = $output['ret']['id'];
        $em->refresh($remitEntry);

        $this->assertEquals($remitEntry->getStatus(), $output['ret']['status']);
        $this->assertEquals($remitEntry->getOperator(), $output['ret']['operator']);
        $this->assertEquals($remitEntry->getConfirmAt()->format(\DateTime::ISO8601), $output['ret']['confirm_at']);
        $this->assertEquals($remitEntry->getAmountEntryId(), $output['ret']['amount_entry_id']);
        $this->assertEquals($remitEntry->getDiscountEntryId(), $output['ret']['discount_entry_id']);
        $this->assertEquals($remitEntry->getOtherDiscountEntryId(), $output['ret']['other_discount_entry_id']);

        // 檢查回傳的操作明細
        $this->assertEquals(4, $output['ret']['amount_entry']['id']);
        $this->assertEquals(1, $output['ret']['amount_entry']['cash_id']);
        $this->assertEquals(1038, $output['ret']['amount_entry']['opcode']);
        $this->assertEquals(5, $output['ret']['amount_entry']['amount']);
        $this->assertEquals(215, $output['ret']['amount_entry']['balance']);
        $this->assertEquals('操作者： odin', $output['ret']['amount_entry']['memo']);
        $this->assertEquals($remitEntry->getRemitAccountId(), $output['ret']['amount_entry']['tag']);
        $this->assertEquals($remitEntry->getRemitAccountId(), $output['ret']['amount_entry']['remit_account_id']);

        // 檢查公司入款帳號統計資料
        $remitAccountStat = $em->getRepository('BBDurianBundle:RemitAccountStat')->findOneBy([
            'remitAccount' => 1,
            'at' => date('YmdHis', mktime(0, 0, 0))
        ]);

        $this->assertNotNull($remitAccountStat);
        $this->assertEquals(1, $remitAccountStat->getCount());
        $this->assertEquals(100, $remitAccountStat->getIncome());

        // 入款帳號必須被暫停
        $remitAccount = $em->find('BBDurianBundle:RemitAccount', 1);

        $this->assertTrue($remitAccount->isEnabled());
        $this->assertFalse($remitAccount->isSuspended());

        $logOp = $emShare->getRepository('BBDurianBundle:LogOperation')->findOneBy([
            'tableName' => 'remit_account',
            'majorKey' => '@id:1',
            'message' => '@suspend:false=>true',
        ]);

        $this->assertNull($logOp);

        // 金額是否有增加(confirm之後是照實際其他優惠欄位的值來設定其他優惠明細的優惠金額)
        $user = $em->find('BBDurianBundle:User', $remitEntry->getUserId());
        $cash = $user->getCash();
        $amount = $balanceOrigin
                + $remitEntry->getAmount()
                + $remitEntry->getDiscount()
                + $remitEntry->getActualOtherDiscount();
        $this->assertEquals($amount, $cash->getBalance());

        // 檢查存款現金明細
        $daParam = ['id' => $remitEntry->getAmountEntryId()];
        $daEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')->findOneBy($daParam);
        $this->assertEquals($remitEntry->getAmount(), $daEntry->getAmount());
        $this->assertEquals($remitEntry->getOrderNumber(), $daEntry->getRefId());
        $this->assertEquals('1036', $daEntry->getOpcode());
        $this->assertEquals('操作者： odin', $daEntry->getMemo());

        $pdwEntry = $em->getRepository('BBDurianBundle:PaymentDepositWithdrawEntry')
            ->findOneBy(['id' => $remitEntry->getAmountEntryId()]);
        $this->assertEquals($remitEntry->getRemitAccountId(), $pdwEntry->getRemitAccountId());

        // 檢查存款優惠現金明細
        $ddParam = ['id' => $remitEntry->getDiscountEntryId()];
        $ddEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')->findOneBy($ddParam);
        $this->assertEquals($remitEntry->getDiscount(), $ddEntry->getAmount());
        $this->assertEquals($remitEntry->getOrderNumber(), $ddEntry->getRefId());
        $this->assertEquals('1037', $ddEntry->getOpcode());
        $this->assertEquals('操作者： odin', $ddEntry->getMemo());

        $ddfParam = ['id' => $remitEntry->getAmountEntryId()];
        $pdwEntry = $em->getRepository('BBDurianBundle:PaymentDepositWithdrawEntry')->findOneBy($ddfParam);
        $this->assertEquals($remitEntry->getRemitAccountId(), $pdwEntry->getRemitAccountId());

        // 檢查其他優惠現金明細(confirm後其他優惠明細記錄的優惠金額是實際其他優惠)
        $odParam = ['id' => $remitEntry->getOtherDiscountEntryId()];
        $odEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')->findOneBy($odParam);

        $this->assertEquals($remitEntry->getActualOtherDiscount(), $odEntry->getAmount());
        $this->assertEquals($remitEntry->getOrderNumber(), $odEntry->getRefId());
        $this->assertEquals('1038', $odEntry->getOpcode());
        $this->assertEquals('操作者： odin', $odEntry->getMemo());

        $odfParam = ['id' => $remitEntry->getAmountEntryId()];
        $pdwEntry = $em->getRepository('BBDurianBundle:PaymentDepositWithdrawEntry')->findOneBy($odfParam);
        $this->assertEquals($remitEntry->getRemitAccountId(), $pdwEntry->getRemitAccountId());

        // 確認人工抄錄明細狀態異動
        $tce = $em->find('BBDurianBundle:TranscribeEntry', 1);
        $this->assertTrue($tce->isConfirm());
        $this->assertEquals($output['ret']['id'], $tce->getRemitEntryId());
        $this->assertEquals($output['ret']['username'], $tce->getUsername());
        $this->assertEquals($output['ret']['confirm_at'], $tce->getConfirmAt()->format(\DateTime::ISO8601));

        //confirm之後匯款明細的其他優惠欄位不會被更改
        $this->assertEquals(1, $remitEntry->getOtherDiscount());

        // 檢查使用者出入款統計資料
        $userId = $remitEntry->getUserId();
        $userStat = $em->find('BBDurianBundle:UserStat', $userId);
        $this->assertEquals(1, $userStat->getRemitCount());
        $this->assertEquals(100, $userStat->getRemitTotal());
        $this->assertEquals(100, $userStat->getRemitMax());
        $this->assertEquals(100, $userStat->getFirstDepositAmount());

        // 檢查銀行卡次數
        $remitAccountStat = $em->getRepository('BBDurianBundle:RemitAccountStat')->findOneBy([
            'remitAccount' => $remitEntry->getRemitAccountId(),
        ]);
        $this->assertEquals(1, $remitAccountStat->getCount());

        // 檢查LogOperation
        $logOp1 = $emShare->find('BBDurianBundle:LogOperation', 1);
        $msg = '@remit_count:0=>1, @remit_total:0=>100, @remit_max:0=>100.0000';
        $msg .= ', @first_deposit_at:' . $userStat->getFirstDepositAt()->format(\DateTime::ISO8601);
        $msg .= ', @first_deposit_amount:100.0000, @modified_at:';
        $this->assertEquals('user_stat', $logOp1->getTableName());
        $this->assertEquals('@user_id:8', $logOp1->getMajorKey());
        $this->assertContains($msg, $logOp1->getMessage());

        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('remit_entry', $logOp2->getTableName());
        $this->assertEquals("@id:$entryId", $logOp2->getMajorKey());
        $message = [
            '@status:' . RemitEntry::UNCONFIRM . '=>' . RemitEntry::CONFIRM,
            '@operator:incredibleS=>' . $remitEntry->getOperator(),
            '@duration:' . $remitEntry->getDuration(),
            '@amount_entry_id:' . $daEntry->getId(),
            '@discount_entry_id:' . $ddEntry->getId(),
            '@other_discount_entry_id:' . $odEntry->getId(),
        ];
        $this->assertEquals(implode(', ', $message), $logOp2->getMessage());

        // 檢查每日優惠資料(第一次確認入款, 找不到資料會自動新增)
        $dailyDiscount = $em->find('BBDurianBundle:UserRemitDiscount', 3);
        $this->assertEquals(5, $dailyDiscount->getDiscount());

        // 檢查每日優惠資料(再次確認入款, 資料已存在並有正確加總)
        $params = ['operator' => 'admin'];
        $client->request('POST', '/api/remit/entry/1/confirm', $params);

        $em->refresh($dailyDiscount);
        $this->assertEquals(10, $dailyDiscount->getDiscount());

        // 檢查統計入款金額queue
        $statDeposit = json_decode($redis->rpop('stat_domain_deposit_queue'), true);

        $this->assertEquals(2, $statDeposit['domain']);
        $this->assertEquals(10, $statDeposit['amount']);
    }

    /**
     * 測試公司入款超過限額會暫停入款帳號
     */
    public function testConfirmRemitWillSuspendWhenBankLimitReached()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();
        $redis = $this->getContainer()->get('snc_redis.default_client');

        // 調整入款帳號限額
        $remitAccount = $em->find('BBDurianBundle:RemitAccount', 1);
        $remitAccount->setBankLimit(99);

        // 確認入款帳號狀態以利後續測試
        $this->assertTrue($remitAccount->isEnabled());
        $this->assertFalse($remitAccount->isSuspended());

        // 先取出原本的額度
        $cashOrigin = $em->find('BBDurianBundle:Cash', 1);
        $balanceOrigin = $cashOrigin->getBalance();

        $remitEntry1 = $em->find('BBDurianBundle:RemitEntry', 1);
        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 2);

        // 建立時間三天內才可修改狀態
        $now = new \DateTime('now');
        $fiveMinAgo = $now->sub(new \DateInterval('PT5M'))->format('YmdHis');
        $remitEntry1->setCreatedAt($fiveMinAgo);
        $remitEntry->setCreatedAt($fiveMinAgo);
        $em->flush();

        $params = [
            'operator' => 'odin',
            'is_discount' => 1,
            'transcribe_entry_id' => 1,
            'amount' => 1000,
            'fee' => 30
        ];
        $client->request('POST', '/api/remit/entry/2/confirm', $params);

        // 跑背景程式讓queue被消化
        $cashPoper = new Poper();
        $cashPoper->runPop($this->getContainer(), 'cash');

        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cashOrigin);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $entryId = $output['ret']['id'];
        $em->refresh($remitEntry);

        $this->assertEquals($remitEntry->getStatus(), $output['ret']['status']);
        $this->assertEquals($remitEntry->getOperator(), $output['ret']['operator']);
        $this->assertEquals($remitEntry->getConfirmAt()->format(\DateTime::ISO8601), $output['ret']['confirm_at']);
        $this->assertEquals($remitEntry->getAmountEntryId(), $output['ret']['amount_entry_id']);
        $this->assertEquals($remitEntry->getDiscountEntryId(), $output['ret']['discount_entry_id']);
        $this->assertEquals($remitEntry->getOtherDiscountEntryId(), $output['ret']['other_discount_entry_id']);

        // 檢查回傳的操作明細
        $this->assertEquals(4, $output['ret']['amount_entry']['id']);
        $this->assertEquals(1, $output['ret']['amount_entry']['cash_id']);
        $this->assertEquals(1038, $output['ret']['amount_entry']['opcode']);
        $this->assertEquals(5, $output['ret']['amount_entry']['amount']);
        $this->assertEquals(215, $output['ret']['amount_entry']['balance']);
        $this->assertEquals('操作者： odin', $output['ret']['amount_entry']['memo']);
        $this->assertEquals($remitEntry->getRemitAccountId(), $output['ret']['amount_entry']['tag']);
        $this->assertEquals($remitEntry->getRemitAccountId(), $output['ret']['amount_entry']['remit_account_id']);

        // 檢查公司入款帳號統計資料
        $remitAccountStat = $em->getRepository('BBDurianBundle:RemitAccountStat')->findOneBy([
            'remitAccount' => 1,
            'at' => date('YmdHis', mktime(0, 0, 0))
        ]);

        $this->assertNotNull($remitAccountStat);
        $this->assertEquals(1, $remitAccountStat->getCount());
        $this->assertEquals(100, $remitAccountStat->getIncome());

        // 入款帳號必須被暫停
        $em->refresh($remitAccount);

        $this->assertTrue($remitAccount->isEnabled());
        $this->assertTrue($remitAccount->isSuspended());

        $logOp = $emShare->getRepository('BBDurianBundle:LogOperation')->findOneBy([
            'tableName' => 'remit_account',
            'majorKey' => '@id:1',
            'message' => '@suspend:false=>true',
        ]);

        $this->assertNotNull($logOp);

        // 金額是否有增加(confirm之後是照實際其他優惠欄位的值來設定其他優惠明細的優惠金額)
        $user = $em->find('BBDurianBundle:User', $remitEntry->getUserId());
        $cash = $user->getCash();
        $amount = $balanceOrigin
                + $remitEntry->getAmount()
                + $remitEntry->getDiscount()
                + $remitEntry->getActualOtherDiscount();
        $this->assertEquals($amount, $cash->getBalance());

        // 檢查存款現金明細
        $daParam = ['id' => $remitEntry->getAmountEntryId()];
        $daEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')->findOneBy($daParam);
        $this->assertEquals($remitEntry->getAmount(), $daEntry->getAmount());
        $this->assertEquals($remitEntry->getOrderNumber(), $daEntry->getRefId());
        $this->assertEquals('1036', $daEntry->getOpcode());
        $this->assertEquals('操作者： odin', $daEntry->getMemo());

        $pdwEntry = $em->getRepository('BBDurianBundle:PaymentDepositWithdrawEntry')
            ->findOneBy(['id' => $remitEntry->getAmountEntryId()]);
        $this->assertEquals($remitEntry->getRemitAccountId(), $pdwEntry->getRemitAccountId());

        // 檢查存款優惠現金明細
        $ddParam = ['id' => $remitEntry->getDiscountEntryId()];
        $ddEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')->findOneBy($ddParam);
        $this->assertEquals($remitEntry->getDiscount(), $ddEntry->getAmount());
        $this->assertEquals($remitEntry->getOrderNumber(), $ddEntry->getRefId());
        $this->assertEquals('1037', $ddEntry->getOpcode());
        $this->assertEquals('操作者： odin', $ddEntry->getMemo());

        $ddfParam = ['id' => $remitEntry->getAmountEntryId()];
        $pdwEntry = $em->getRepository('BBDurianBundle:PaymentDepositWithdrawEntry')->findOneBy($ddfParam);
        $this->assertEquals($remitEntry->getRemitAccountId(), $pdwEntry->getRemitAccountId());

        // 檢查其他優惠現金明細(confirm後其他優惠明細記錄的優惠金額是實際其他優惠)
        $odParam = ['id' => $remitEntry->getOtherDiscountEntryId()];
        $odEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')->findOneBy($odParam);

        $this->assertEquals($remitEntry->getActualOtherDiscount(), $odEntry->getAmount());
        $this->assertEquals($remitEntry->getOrderNumber(), $odEntry->getRefId());
        $this->assertEquals('1038', $odEntry->getOpcode());
        $this->assertEquals('操作者： odin', $odEntry->getMemo());

        $odfParam = ['id' => $remitEntry->getAmountEntryId()];
        $pdwEntry = $em->getRepository('BBDurianBundle:PaymentDepositWithdrawEntry')->findOneBy($odfParam);
        $this->assertEquals($remitEntry->getRemitAccountId(), $pdwEntry->getRemitAccountId());

        // 確認人工抄錄明細狀態異動
        $tce = $em->find('BBDurianBundle:TranscribeEntry', 1);
        $this->assertTrue($tce->isConfirm());
        $this->assertEquals($output['ret']['id'], $tce->getRemitEntryId());
        $this->assertEquals($output['ret']['username'], $tce->getUsername());
        $this->assertEquals($output['ret']['confirm_at'], $tce->getConfirmAt()->format(\DateTime::ISO8601));

        //confirm之後匯款明細的其他優惠欄位不會被更改
        $this->assertEquals(1, $remitEntry->getOtherDiscount());

        // 檢查使用者出入款統計資料
        $userId = $remitEntry->getUserId();
        $userStat = $em->find('BBDurianBundle:UserStat', $userId);
        $this->assertEquals(1, $userStat->getRemitCount());
        $this->assertEquals(100, $userStat->getRemitTotal());
        $this->assertEquals(100, $userStat->getRemitMax());
        $this->assertEquals(100, $userStat->getFirstDepositAmount());

        // 檢查銀行卡次數
        $remitAccountStat = $em->getRepository('BBDurianBundle:RemitAccountStat')->findOneBy([
            'remitAccount' => $remitEntry->getRemitAccountId(),
        ]);
        $this->assertEquals(1, $remitAccountStat->getCount());

        // 檢查LogOperation
        $logOp1 = $emShare->find('BBDurianBundle:LogOperation', 1);
        $msg = '@remit_count:0=>1, @remit_total:0=>100, @remit_max:0=>100.0000';
        $msg .= ', @first_deposit_at:' . $userStat->getFirstDepositAt()->format(\DateTime::ISO8601);
        $msg .= ', @first_deposit_amount:100.0000, @modified_at:';
        $this->assertEquals('user_stat', $logOp1->getTableName());
        $this->assertEquals('@user_id:8', $logOp1->getMajorKey());
        $this->assertContains($msg, $logOp1->getMessage());

        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertEquals('remit_entry', $logOp2->getTableName());
        $this->assertEquals("@id:$entryId", $logOp2->getMajorKey());
        $message = [
            '@status:' . RemitEntry::UNCONFIRM.'=>'.RemitEntry::CONFIRM,
            '@operator:incredibleS=>' . $remitEntry->getOperator(),
            '@duration:' . $remitEntry->getDuration(),
            '@amount_entry_id:' . $daEntry->getId(),
            '@discount_entry_id:' . $ddEntry->getId(),
            '@other_discount_entry_id:' . $odEntry->getId(),
        ];
        $this->assertEquals(implode(', ', $message), $logOp2->getMessage());

        // 檢查每日優惠資料(第一次確認入款, 找不到資料會自動新增)
        $dailyDiscount = $em->find('BBDurianBundle:UserRemitDiscount', 3);
        $this->assertEquals(5, $dailyDiscount->getDiscount());

        // 檢查每日優惠資料(再次確認入款, 資料已存在並有正確加總)
        $params = ['operator' => 'admin'];
        $client->request('POST', '/api/remit/entry/1/confirm', $params);

        $em->refresh($dailyDiscount);
        $this->assertEquals(10, $dailyDiscount->getDiscount());

        // 檢查統計入款金額queue
        $statDeposit = json_decode($redis->rpop('stat_domain_deposit_queue'), true);

        $this->assertEquals(2, $statDeposit['domain']);
        $this->assertEquals(10, $statDeposit['amount']);
    }

    /**
     * 測試急速到帳超過限額會暫停入款帳號
     */
    public function testConfirmRemitWithAutoConfirmWillSuspendWhenBankLimitReached()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();
        $redis = $this->getContainer()->get('snc_redis.default_client');

        // 調整入款帳號限額
        $remitAccount = $em->find('BBDurianBundle:RemitAccount', 6);
        $remitAccount->setBankLimit(99);

        // 確認入款帳號狀態以利後續測試
        $this->assertTrue($remitAccount->isEnabled());
        $this->assertFalse($remitAccount->isSuspended());

        // 先取出原本的額度
        $cashOrigin = $em->find('BBDurianBundle:Cash', 1);
        $balanceOrigin = $cashOrigin->getBalance();

        $now = new \DateTime('now');
        $fiveMinAgo = $now->sub(new \DateInterval('PT5M'))->format('YmdHis');

        // 建立時間三天內才可修改狀態
        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 9);
        $remitEntry->setCreatedAt($fiveMinAgo);
        $remitEntry->setDiscount(10);
        $em->flush();

        $params = [
            'operator' => 0,
            'auto_confirm' => 1,
        ];
        $client->request('POST', '/api/remit/entry/9/confirm', $params);

        // 跑背景程式讓 queue 被消化
        $cashPoper = new Poper();
        $cashPoper->runPop($this->getContainer(), 'cash');

        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cashOrigin);
        $em->refresh($remitEntry);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $entryId = $output['ret']['id'];

        $this->assertEquals($remitEntry->getStatus(), $output['ret']['status']);
        $this->assertEquals($remitEntry->getOperator(), $output['ret']['operator']);
        $this->assertEquals($remitEntry->getConfirmAt()->format(\DateTime::ISO8601), $output['ret']['confirm_at']);
        $this->assertEquals($remitEntry->getAmountEntryId(), $output['ret']['amount_entry_id']);
        $this->assertEquals($remitEntry->getDiscountEntryId(), $output['ret']['discount_entry_id']);
        $this->assertEquals($remitEntry->getOtherDiscountEntryId(), $output['ret']['other_discount_entry_id']);

        // 檢查回傳的操作明細
        $this->assertEquals(3, $output['ret']['amount_entry']['id']);
        $this->assertEquals(1, $output['ret']['amount_entry']['cash_id']);
        $this->assertEquals(1037, $output['ret']['amount_entry']['opcode']);
        $this->assertEquals(10, $output['ret']['amount_entry']['amount']);
        $this->assertEquals(1109, $output['ret']['amount_entry']['balance']);
        $this->assertEquals('操作者： 0', $output['ret']['amount_entry']['memo']);
        $this->assertEquals($remitEntry->getRemitAccountId(), $output['ret']['amount_entry']['tag']);
        $this->assertEquals($remitEntry->getRemitAccountId(), $output['ret']['amount_entry']['remit_account_id']);

        // 檢查公司入款帳號統計資料
        $remitAccountStat = $em->getRepository('BBDurianBundle:RemitAccountStat')->findOneBy([
            'remitAccount' => 6,
            'at' => date('YmdHis', mktime(0, 0, 0))
        ]);

        $this->assertNotNull($remitAccountStat);
        $this->assertEquals(2, $remitAccountStat->getCount());
        $this->assertEquals(999, $remitAccountStat->getIncome());

        // 入款帳號必須被暫停
        $em->refresh($remitAccount);

        $this->assertTrue($remitAccount->isEnabled());
        $this->assertTrue($remitAccount->isSuspended());

        $logOp = $emShare->getRepository('BBDurianBundle:LogOperation')->findOneBy([
            'tableName' => 'remit_account',
            'majorKey' => '@id:6',
            'message' => '@suspend:false=>true',
        ]);

        $this->assertNotNull($logOp);

        // 金額是否有增加(confirm 之後是照實際其他優惠欄位的值來設定其他優惠明細的優惠金額)
        $user = $em->find('BBDurianBundle:User', $remitEntry->getUserId());
        $cash = $user->getCash();
        $amount = $balanceOrigin
            + $remitEntry->getAmount()
            + $remitEntry->getDiscount()
            + $remitEntry->getActualOtherDiscount();
        $this->assertEquals($amount, $cash->getBalance());

        // 檢查存款現金明細
        $cashEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $remitEntry->getAmountEntryId()]);
        $this->assertEquals($remitEntry->getAmount(), $cashEntry->getAmount());
        $this->assertEquals($remitEntry->getOrderNumber(), $cashEntry->getRefId());
        $this->assertEquals('1036', $cashEntry->getOpcode());
        $this->assertEquals('操作者： 0', $cashEntry->getMemo());

        $pdwEntry = $em->getRepository('BBDurianBundle:PaymentDepositWithdrawEntry')
            ->findOneBy(['id' => $remitEntry->getAmountEntryId()]);
        $this->assertEquals($remitEntry->getRemitAccountId(), $pdwEntry->getRemitAccountId());

        // 檢查存款優惠現金明細
        $discountCashEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $remitEntry->getDiscountEntryId()]);
        $this->assertEquals($remitEntry->getDiscount(), $discountCashEntry->getAmount());
        $this->assertEquals($remitEntry->getOrderNumber(), $discountCashEntry->getRefId());
        $this->assertEquals('1037', $discountCashEntry->getOpcode());
        $this->assertEquals('操作者： 0', $discountCashEntry->getMemo());

        // confirm 之後匯款明細的其他優惠欄位不會被更改
        $this->assertEquals(0, $remitEntry->getOtherDiscount());

        // 檢查使用者出入款統計資料
        $userStat = $em->find('BBDurianBundle:UserStat', $remitEntry->getUserId());
        $this->assertEquals(1, $userStat->getRemitCount());
        $this->assertEquals(999, $userStat->getRemitTotal());
        $this->assertEquals(999, $userStat->getRemitMax());
        $this->assertEquals(999, $userStat->getFirstDepositAmount());

        // 檢查 LogOperation
        $logOp1 = $emShare->find('BBDurianBundle:LogOperation', 1);
        $msg = '@remit_count:0=>1, @remit_total:0=>999, @remit_max:0=>999.0000';
        $msg .= ', @first_deposit_at:' . $userStat->getFirstDepositAt()->format(\DateTime::ISO8601);
        $msg .= ', @first_deposit_amount:999.0000, @modified_at:';
        $this->assertEquals('user_stat', $logOp1->getTableName());
        $this->assertEquals('@user_id:8', $logOp1->getMajorKey());
        $this->assertContains($msg, $logOp1->getMessage());

        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertEquals('remit_entry', $logOp2->getTableName());
        $this->assertEquals("@id:$entryId", $logOp2->getMajorKey());
        $message = [
            '@status:' . RemitEntry::UNCONFIRM . '=>' . RemitEntry::CONFIRM,
            '@operator:=>' . $remitEntry->getOperator(),
            '@duration:' . $remitEntry->getDuration(),
            '@amount_entry_id:' . $cashEntry->getId(),
            '@discount_entry_id:' . $discountCashEntry->getId(),
        ];
        $this->assertEquals(implode(', ', $message), $logOp2->getMessage());

        // 檢查通知稽核 queue
        $auditParams = json_decode($redis->rpop('audit_queue'), true);

        $this->assertEquals(9, $auditParams['remit_entry_id']);
        $this->assertEquals(8, $auditParams['user_id']);
        $this->assertEquals(1109, $auditParams['balance']);
        $this->assertEquals(999, $auditParams['amount']);
        $this->assertEquals(10, $auditParams['offer']);
        $this->assertEquals(0, $auditParams['fee']);
        $this->assertEquals('N', $auditParams['abandonsp']);
        $this->assertEquals($remitEntry->getConfirmAt()->format('Y-m-d H:i:s'), $auditParams['deposit_time']);
        $this->assertEquals(1, $auditParams['auto_confirm']);
    }

    /**
     * 測試照入款記錄出款給存款會員，帶入自動認款參數但會員放棄優惠
     */
    public function testConfirmRemitWithAutoConfirmButAbandonDiscount()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $now = new \DateTime('now');
        $fiveMinAgo = $now->sub(new \DateInterval('PT5M'))->format('YmdHis');

        // 建立時間三天內才可修改狀態，且設定為放棄優惠
        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 9);
        $remitEntry->setCreatedAt($fiveMinAgo);
        $remitEntry->abandonDiscount();
        $em->flush();

        $params = [
            'operator' => 0,
            'auto_confirm' => 1,
        ];
        $client->request('POST', '/api/remit/entry/9/confirm', $params);

        // 跑背景程式讓 queue 被消化
        $cashPoper = new Poper();
        $cashPoper->runPop($this->getContainer(), 'cash');

        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($remitEntry);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals($remitEntry->getStatus(), $output['ret']['status']);
        $this->assertEquals($remitEntry->getOperator(), $output['ret']['operator']);
        $this->assertEquals($remitEntry->getConfirmAt()->format(\DateTime::ISO8601), $output['ret']['confirm_at']);
        $this->assertEquals($remitEntry->getAmountEntryId(), $output['ret']['amount_entry_id']);
        $this->assertEquals($remitEntry->getDiscountEntryId(), $output['ret']['discount_entry_id']);
        $this->assertEquals($remitEntry->getOtherDiscountEntryId(), $output['ret']['other_discount_entry_id']);

        // 檢查回傳的操作明細
        $this->assertEquals(2, $output['ret']['amount_entry']['id']);
        $this->assertEquals(1, $output['ret']['amount_entry']['cash_id']);
        $this->assertEquals(1036, $output['ret']['amount_entry']['opcode']);
        $this->assertEquals(999, $output['ret']['amount_entry']['amount']);
        $this->assertEquals(1099, $output['ret']['amount_entry']['balance']);
        $this->assertEquals('操作者： 0', $output['ret']['amount_entry']['memo']);
        $this->assertEquals($remitEntry->getRemitAccountId(), $output['ret']['amount_entry']['tag']);
        $this->assertEquals($remitEntry->getRemitAccountId(), $output['ret']['amount_entry']['remit_account_id']);

        // 檢查公司入款帳號統計資料
        $remitAccountStat = $em->getRepository('BBDurianBundle:RemitAccountStat')->findOneBy([
            'remitAccount' => 6,
            'at' => date('YmdHis', mktime(0, 0, 0))
        ]);

        $this->assertNotNull($remitAccountStat);
        $this->assertEquals(2, $remitAccountStat->getCount());
        $this->assertEquals(999, $remitAccountStat->getIncome());

        // 檢查通知稽核 queue
        $auditParams = json_decode($redis->rpop('audit_queue'), true);

        $this->assertEquals(9, $auditParams['remit_entry_id']);
        $this->assertEquals(8, $auditParams['user_id']);
        $this->assertEquals(1099, $auditParams['balance']);
        $this->assertEquals(999, $auditParams['amount']);
        $this->assertEquals(0, $auditParams['offer']);
        $this->assertEquals(0, $auditParams['fee']);
        $this->assertEquals('Y', $auditParams['abandonsp']);
        $this->assertEquals($remitEntry->getConfirmAt()->format('Y-m-d H:i:s'), $auditParams['deposit_time']);
        $this->assertEquals(1, $auditParams['auto_confirm']);
    }

    /**
     * 測試照入款記錄出款給存款會員, 入款金額超過50萬, 需寄發異常入款提醒
     */
    public function testConfirmRemitWithAbnormalAmount()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();
        $redis = $this->getContainer()->get('snc_redis.default_client');

        // 先取出原本的額度
        $cashOrigin = $em->find('BBDurianBundle:Cash', 1);
        $balanceOrigin = $cashOrigin->getBalance();

        $account = $em->find('BBDurianBundle:RemitAccount', 2);
        $user = $em->find('BBDurianBundle:User', 8);
        $bankInfo = $em->find('BBDurianBundle:BankInfo', 2);

        $depositAt = new \DateTime('now');

        $remitEntry = new RemitEntry($account, $user, $bankInfo);
        $remitEntry->setOrderNumber(2012031700004121);
        $remitEntry->setOldOrderNumber('q5e456z2x1');
        $remitEntry->setDepositAt($depositAt);
        $remitEntry->setAmount(2500000);
        $remitEntry->setRate(0.2);
        $em->persist($remitEntry);
        $em->flush();

        $params = [
            'operator' => 'odin',
            'amount' => 2500000,
            'fee' => 30
        ];
        $client->request('POST', '/api/remit/entry/13/confirm', $params);

        // 跑背景程式讓queue被消化
        $cashPoper = new Poper();
        $cashPoper->runPop($this->getContainer(), 'cash');

        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cashOrigin);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $entryId = $output['ret']['id'];
        $em->refresh($remitEntry);

        $this->assertEquals($remitEntry->getStatus(), $output['ret']['status']);
        $this->assertEquals($remitEntry->getOperator(), $output['ret']['operator']);
        $this->assertEquals($remitEntry->getConfirmAt()->format(\DateTime::ISO8601), $output['ret']['confirm_at']);
        $this->assertEquals($remitEntry->getAmountEntryId(), $output['ret']['amount_entry_id']);
        $this->assertEquals($remitEntry->getDiscountEntryId(), $output['ret']['discount_entry_id']);
        $this->assertEquals($remitEntry->getOtherDiscountEntryId(), $output['ret']['other_discount_entry_id']);

        // 檢查回傳的操作明細
        $this->assertEquals(2, $output['ret']['amount_entry']['id']);
        $this->assertEquals(1, $output['ret']['amount_entry']['cash_id']);
        $this->assertEquals(1036, $output['ret']['amount_entry']['opcode']);
        $this->assertEquals(2500000, $output['ret']['amount_entry']['amount']);
        $this->assertEquals(2500100, $output['ret']['amount_entry']['balance']);
        $this->assertEquals('操作者： odin', $output['ret']['amount_entry']['memo']);
        $this->assertEquals($remitEntry->getRemitAccountId(), $output['ret']['amount_entry']['tag']);
        $this->assertEquals($remitEntry->getRemitAccountId(), $output['ret']['amount_entry']['remit_account_id']);

        // 檢查公司入款帳號統計資料
        $remitAccountStat = $em->getRepository('BBDurianBundle:RemitAccountStat')->findOneBy([
            'remitAccount' => 2,
            'at' => date('YmdHis', mktime(0, 0, 0))
        ]);

        $this->assertNotNull($remitAccountStat);
        $this->assertEquals(1, $remitAccountStat->getCount());
        $this->assertEquals(2500000, $remitAccountStat->getIncome());

        // 金額是否有增加(confirm之後是照實際其他優惠欄位的值來設定其他優惠明細的優惠金額)
        $em->refresh($user);
        $cash = $user->getCash();
        $amount = $balanceOrigin
            + $remitEntry->getAmount()
            + $remitEntry->getDiscount()
            + $remitEntry->getActualOtherDiscount();
        $this->assertEquals($amount, $cash->getBalance());

        // 檢查存款現金明細
        $daParam = ['id' => $remitEntry->getAmountEntryId()];
        $daEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')->findOneBy($daParam);
        $this->assertEquals($remitEntry->getAmount(), $daEntry->getAmount());
        $this->assertEquals($remitEntry->getOrderNumber(), $daEntry->getRefId());
        $this->assertEquals('1036', $daEntry->getOpcode());
        $this->assertEquals('操作者： odin', $daEntry->getMemo());

        $pdwEntry = $em->getRepository('BBDurianBundle:PaymentDepositWithdrawEntry')
            ->findOneBy(['id' => $remitEntry->getAmountEntryId()]);
        $this->assertEquals($remitEntry->getRemitAccountId(), $pdwEntry->getRemitAccountId());

        // 檢查使用者出入款統計資料
        $userId = $remitEntry->getUserId();
        $userStat = $em->find('BBDurianBundle:UserStat', $userId);
        $this->assertEquals(1, $userStat->getRemitCount());
        $this->assertEquals(500000, $userStat->getRemitTotal());
        $this->assertEquals(500000, $userStat->getRemitMax());
        $this->assertEquals(500000, $userStat->getFirstDepositAmount());

        // 檢查LogOperation
        $logOp1 = $emShare->find('BBDurianBundle:LogOperation', 1);
        $msg = '@remit_count:0=>1, @remit_total:0=>500000, @remit_max:0=>500000.0000';
        $msg .= ', @first_deposit_at:' . $userStat->getFirstDepositAt()->format(\DateTime::ISO8601);
        $msg .= ', @first_deposit_amount:500000.0000, @modified_at:';
        $this->assertEquals('user_stat', $logOp1->getTableName());
        $this->assertEquals('@user_id:8', $logOp1->getMajorKey());
        $this->assertContains($msg, $logOp1->getMessage());

        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('remit_entry', $logOp2->getTableName());
        $this->assertEquals("@id:$entryId", $logOp2->getMajorKey());
        $message = [
            '@status:'.RemitEntry::UNCONFIRM.'=>'.RemitEntry::CONFIRM,
            "@operator:=>".$remitEntry->getOperator(),
            "@duration:".$remitEntry->getDuration(),
            "@amount_entry_id:".$daEntry->getId(),
        ];
        $this->assertEquals(implode(', ', $message), $logOp2->getMessage());

        // 檢查異常入款提醒queue
        $abnormalDepositNotify = json_decode($redis->rpop('abnormal_deposit_notify_queue'), true);

        $this->assertEquals(2, $abnormalDepositNotify['domain']);
        $this->assertEquals('tester', $abnormalDepositNotify['user_name']);
        $this->assertEquals(1036, $abnormalDepositNotify['opcode']);
        $this->assertEquals('odin', $abnormalDepositNotify['operator']);
        $this->assertEquals(500000, $abnormalDepositNotify['amount']);
    }

    /**
     * 測試入款記錄同分秒重覆出款給存款會員
     */
    public function testConfirmRemitEntryWithDuplicatedEntry()
    {
        $client = $this->createClient();

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->find('BBDurianBundle:User', 8);
        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 1);

        // 建立時間三天內才可修改狀態
        $now = new \DateTime('now');
        $fiveMinAgo = $now->sub(new \DateInterval('PT5M'))->format('YmdHis');
        $remitEntry->setCreatedAt($fiveMinAgo);
        $em->flush();
        $em->clear();

        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 1);

        $repo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();

        $repo->expects($this->any())
            ->method('findOneBy')
            ->will($this->returnValue($remitEntry));

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['beginTransaction', 'find', 'getRepository', 'persist', 'flush', 'rollback', 'clear'])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('find')
            ->will($this->returnValue($user));

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($repo);

        $pdoExcep = new \PDOException('Duplicate', 23000);
        $pdoExcep->errorInfo[1] = 1062;
        $exception = new \Exception("Duplicate entry", 300056, $pdoExcep);

        $mockEm->expects($this->any())
            ->method('flush')
            ->will($this->throwException($exception));

        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);

        $params = [
            'operator' => 'odin',
            'is_discount' => 1,
            'transcribe_entry_id' => 1,
            'amount' => 1000,
            'fee' => 30
        ];

        $client->request('POST', '/api/remit/entry/2/confirm', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300056, $output['code']);
        $this->assertEquals('Database is busy', $output['msg']);
    }

    /**
     * 測試照建立時間早於三天以前的入款記錄出款給存款會員
     */
    public function testConfirmRemitEntryCreatedAtBeforeThreeDaysAgo()
    {
        $client = $this->createClient();

        $params = ['operator' => 'odin'];
        $client->request('POST', '/api/remit/entry/1/confirm', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300048, $output['code']);
        $this->assertEquals('Can not modify status of expired entry', $output['msg']);
    }

    /**
     * 測試照入款記錄出款給存款會員但不予優惠
     */
    public function testConfirmRemitWithoutOffer()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $client = $this->createClient();

        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 2);

        // 建立時間三天內才可修改狀態
        $now = new \DateTime('now');
        $fiveMinAgo = $now->sub(new \DateInterval('PT5M'))->format('YmdHis');
        $remitEntry->setCreatedAt($fiveMinAgo);
        $em->flush();

        // 先取出原本的額度
        $cashOrigin = $em->find('BBDurianBundle:Cash', 1);
        $balanceOrigin = $cashOrigin->getBalance();

        $params = [
            'operator' => 'admin',
            'is_discount' => 0
        ];
        $client->request('POST', '/api/remit/entry/2/confirm', $params);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cashOrigin);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $em->refresh($remitEntry);

        $this->assertEquals($remitEntry->getAmountEntryId(), $output['ret']['amount_entry_id']);
        $this->assertEquals($remitEntry->getDiscountEntryId(), $output['ret']['discount_entry_id']);
        $this->assertEquals($remitEntry->getOtherDiscountEntryId(), $output['ret']['other_discount_entry_id']);

        //檢查回傳的操作明細
        $this->assertEquals(3, $output['ret']['amount_entry']['id']);
        $this->assertEquals(1, $output['ret']['amount_entry']['cash_id']);
        $this->assertEquals(1038, $output['ret']['amount_entry']['opcode']);
        $this->assertEquals(5, $output['ret']['amount_entry']['amount']);
        $this->assertEquals(205, $output['ret']['amount_entry']['balance']);
        $this->assertEquals('操作者： admin', $output['ret']['amount_entry']['memo']);

        // 檢查公司入款帳號統計資料
        $remitAccountStat = $em->getRepository('BBDurianBundle:RemitAccountStat')->findOneBy([
            'remitAccount' => 1,
            'at' => date('YmdHis', mktime(0, 0, 0))
        ]);

        $this->assertNotNull($remitAccountStat);
        $this->assertEquals(1, $remitAccountStat->getCount());
        $this->assertEquals(100, $remitAccountStat->getIncome());

        // 金額是否有增加
        $user = $em->find('BBDurianBundle:User', $remitEntry->getUserId());
        $cash = $user->getCash();
        $amount = $balanceOrigin + $remitEntry->getAmount() + $remitEntry->getActualOtherDiscount();
        $this->assertEquals($amount, $cash->getBalance());

        // 驗證存款優惠現金明細不會寫入
        $ddEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $remitEntry->getDiscountEntryId()]);
        $this->assertNull($ddEntry);

        // 驗證其他優惠依然會寫入現金明細
        $odEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $remitEntry->getOtherDiscountEntryId()]);
        $this->assertEquals($remitEntry->getActualOtherDiscount(), $odEntry->getAmount());
        $this->assertEquals($remitEntry->getOrderNumber(), $odEntry->getRefId());
        $this->assertEquals('1038', $odEntry->getOpcode());
        $this->assertEquals('操作者： admin', $odEntry->getMemo());
    }

    /**
     * 測試照入款記錄出款給存款會員但不予其他優惠
     */
    public function testConfirmRemitWithoutOtherOffer()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $client = $this->createClient();

        $rEntry = $em->find('BBDurianBundle:RemitEntry', 2);
        $rEntry->setActualOtherDiscount(0);

        // 建立時間三天內才可修改狀態
        $now = new \DateTime('now');
        $fiveMinAgo = $now->sub(new \DateInterval('PT5M'))->format('YmdHis');
        $rEntry->setCreatedAt($fiveMinAgo);

        $em->flush($rEntry);
        $em->clear();

        // 先取出原本的額度
        $cashOrigin = $em->find('BBDurianBundle:Cash', 1);
        $balanceOrigin = $cashOrigin->getBalance();

        $params = [
            'operator' => 'odin',
            'is_discount' => 1
        ];
        $client->request('POST', '/api/remit/entry/2/confirm', $params);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cashOrigin);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $entryId = $output['ret']['id'];
        $remitEntry = $em->find('BBDurianBundle:RemitEntry', $entryId);

        $this->assertEquals($remitEntry->getAmountEntryId(), $output['ret']['amount_entry_id']);
        $this->assertEquals($remitEntry->getDiscountEntryId(), $output['ret']['discount_entry_id']);
        $this->assertEquals($remitEntry->getOtherDiscountEntryId(), $output['ret']['other_discount_entry_id']);

        //檢查回傳的操作明細
        $this->assertEquals(3, $output['ret']['amount_entry']['id']);
        $this->assertEquals(1, $output['ret']['amount_entry']['cash_id']);
        $this->assertEquals(1037, $output['ret']['amount_entry']['opcode']);
        $this->assertEquals(10, $output['ret']['amount_entry']['amount']);
        $this->assertEquals(210, $output['ret']['amount_entry']['balance']);
        $this->assertEquals('操作者： odin', $output['ret']['amount_entry']['memo']);

        // 檢查公司入款帳號統計資料
        $remitAccountStat = $em->getRepository('BBDurianBundle:RemitAccountStat')->findOneBy([
            'remitAccount' => 1,
            'at' => date('YmdHis', mktime(0, 0, 0))
        ]);

        $this->assertNotNull($remitAccountStat);
        $this->assertEquals(1, $remitAccountStat->getCount());
        $this->assertEquals(100, $remitAccountStat->getIncome());

        // 金額是否有增加
        $user = $em->find('BBDurianBundle:User', $remitEntry->getUserId());
        $cash = $user->getCash();
        $amount = $balanceOrigin + $remitEntry->getAmount();
        $amount += $remitEntry->getDiscount() + $remitEntry->getActualOtherDiscount();
        $this->assertEquals($amount, $cash->getBalance());

        // 檢查存款現金明細
        $daEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $remitEntry->getAmountEntryId()]);
        $this->assertEquals($remitEntry->getAmount(), $daEntry->getAmount());
        $this->assertEquals($remitEntry->getOrderNumber(), $daEntry->getRefId());
        $this->assertEquals('1036', $daEntry->getOpcode());
        $this->assertEquals('操作者： odin', $daEntry->getMemo());

        // 檢查存款優惠現金明細
        $ddEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $remitEntry->getDiscountEntryId()]);
        $this->assertEquals($remitEntry->getDiscount(), $ddEntry->getAmount());
        $this->assertEquals($remitEntry->getOrderNumber(), $ddEntry->getRefId());
        $this->assertEquals('1037', $ddEntry->getOpcode());
        $this->assertEquals('操作者： odin', $ddEntry->getMemo());

        // 檢查其他優惠現金明細不會被寫入
        $odEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $remitEntry->getOtherDiscountEntryId()]);
        $this->assertNull($odEntry);
    }

    /**
     * 測試照入款記錄出款給存款會員但不予其他優惠及入款優惠
     */
    public function testConfirmRemitWithoutOtherOfferAndOffer()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $client = $this->createClient();

        $rEntry = $em->find('BBDurianBundle:RemitEntry', 2);
        $rEntry->setActualOtherDiscount(0);

        // 建立時間三天內才可修改狀態
        $now = new \DateTime('now');
        $fiveMinAgo = $now->sub(new \DateInterval('PT5M'))->format('YmdHis');
        $rEntry->setCreatedAt($fiveMinAgo);

        $em->flush($rEntry);
        $em->clear();

        // 先取出原本的額度
        $cashOrigin = $em->find('BBDurianBundle:Cash', 1);
        $balanceOrigin = $cashOrigin->getBalance();

        $params = [
            'operator' => 'odin',
            'is_discount' => 0
        ];
        $client->request('POST', '/api/remit/entry/2/confirm', $params);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cashOrigin);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $remitEntry = $em->find('BBDurianBundle:RemitEntry', $output['ret']['id']);

        $this->assertEquals($remitEntry->getAmountEntryId(), $output['ret']['amount_entry_id']);
        $this->assertEquals($remitEntry->getDiscountEntryId(), $output['ret']['discount_entry_id']);
        $this->assertEquals($remitEntry->getOtherDiscountEntryId(), $output['ret']['other_discount_entry_id']);

        //檢查回傳的操作明細
        $this->assertEquals(2, $output['ret']['amount_entry']['id']);
        $this->assertEquals(1, $output['ret']['amount_entry']['cash_id']);
        $this->assertEquals(1036, $output['ret']['amount_entry']['opcode']);
        $this->assertEquals(100, $output['ret']['amount_entry']['amount']);
        $this->assertEquals(200, $output['ret']['amount_entry']['balance']);
        $this->assertEquals('操作者： odin', $output['ret']['amount_entry']['memo']);

        // 檢查公司入款帳號統計資料
        $remitAccountStat = $em->getRepository('BBDurianBundle:RemitAccountStat')->findOneBy([
            'remitAccount' => 1,
            'at' => date('YmdHis', mktime(0, 0, 0))
        ]);

        $this->assertNotNull($remitAccountStat);
        $this->assertEquals(1, $remitAccountStat->getCount());
        $this->assertEquals(100, $remitAccountStat->getIncome());

        // 金額是否有增加
        $user = $em->find('BBDurianBundle:User', $remitEntry->getUserId());
        $cash = $user->getCash();
        $amount = $balanceOrigin + $remitEntry->getAmount() + $remitEntry->getActualOtherDiscount();
        $this->assertEquals($amount, $cash->getBalance());

        // 驗證存款優惠現金明細不會寫入
        $ddEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $remitEntry->getDiscountEntryId()]);
        $this->assertNull($ddEntry);

        // 驗證其他優惠明細不會寫入
        $odEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $remitEntry->getOtherDiscountEntryId()]);
        $this->assertNull($odEntry);
    }

    /**
     * 測試入款但記錄不存在
     */
    public function testConfirmRemitButRemitEntryNotExist()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $params = ['operator' => 'odin'];
        $client->request('POST', '/api/remit/entry/999/confirm', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300012, $output['code']);
        $this->assertEquals('No RemitEntry found', $output['msg']);

        // 噴錯不會寫LogOperation
        $logOp = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertNull($logOp);
    }

    /**
     * 測試入款但記錄不是未處理的狀態
     */
    public function testConfirmRemitButRemitEntryNotUnconfirm()
    {
        $client = $this->createClient();
        $params = ['operator' => 'odin'];
        $client->request('POST', '/api/remit/entry/3/confirm', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300011, $output['code']);
        $this->assertEquals('RemitEntry not unconfirm', $output['msg']);
    }

    /**
     * 測試入款但未代入操作者
     */
    public function testConfirmRemitWithoutOperator()
    {
        $client = $this->createClient();
        $client->request('POST', '/api/remit/entry/2/confirm');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300033, $output['code']);
        $this->assertEquals('Invalid operator specified', $output['msg']);
    }

    /**
     * 測試入款但代入操作者不合法
     */
    public function testConfirmRemitWithInvalidOperator()
    {
        $client = $this->createClient();

        $params = ['operator' => ''];
        $client->request('POST', '/api/remit/entry/2/confirm', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300033, $output['code']);
        $this->assertEquals('Invalid operator specified', $output['msg']);
    }

    /**
     * 測試入款但使用者無現金
     */
    public function testConfirmRemitButUserHasNoCash()
    {
        $client = $this->createClient();

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 6);

        // 建立時間三天內才可修改狀態
        $now = new \DateTime('now');
        $fiveMinAgo = $now->sub(new \DateInterval('PT5M'))->format('YmdHis');
        $remitEntry->setCreatedAt($fiveMinAgo);
        $em->flush();

        $params = ['operator' => 'odin'];
        $client->request('POST', '/api/remit/entry/6/confirm', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300053, $output['code']);
        $this->assertEquals('No cash found', $output['msg']);
    }

    /**
     * 測試重覆確認入款記錄
     */
    public function testConfirmRemitTwice()
    {
        $client = $this->createClient();

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 2);

        // 建立時間三天內才可修改狀態
        $now = new \DateTime('now');
        $fiveMinAgo = $now->sub(new \DateInterval('PT5M'))->format('YmdHis');
        $remitEntry->setCreatedAt($fiveMinAgo);
        $em->flush();

        $params = ['operator' => 'odin'];

        //第一次會成功
        $client->request('POST', '/api/remit/entry/2/confirm', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        //第二次會發生例外
        $client->request('POST', '/api/remit/entry/2/confirm', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300011, $output['code']);
        $this->assertEquals('RemitEntry not unconfirm', $output['msg']);
    }

    /**
     * 測試確認入款記錄時人工抄錄明細金額已改變
     */
    public function testConfirmRemitWithTranscribeEntryAmountHasBeenChanged()
    {
        $client = $this->createClient();

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 1);

        // 建立時間三天內才可修改狀態
        $now = new \DateTime('now');
        $fiveMinAgo = $now->sub(new \DateInterval('PT5M'))->format('YmdHis');
        $remitEntry->setCreatedAt($fiveMinAgo);
        $em->flush();

        $params = [
            'operator' => 'odin',
            'transcribe_entry_id' => 5,
            'amount' => 2000,
            'fee' => 30
        ];
        $client->request('POST', '/api/remit/entry/1/confirm', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300059, $output['code']);
        $this->assertEquals('TranscribeEntry amount has been changed', $output['msg']);
    }

    /**
     * 測試確認入款記錄時人工抄錄明細手續費已改變
     */
    public function testConfirmRemitWithTranscribeEntryFeeHasBeenChanged()
    {
        $client = $this->createClient();

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 1);

        // 建立時間三天內才可修改狀態
        $now = new \DateTime('now');
        $fiveMinAgo = $now->sub(new \DateInterval('PT5M'))->format('YmdHis');
        $remitEntry->setCreatedAt($fiveMinAgo);
        $em->flush();

        $params = [
            'operator' => 'odin',
            'transcribe_entry_id' => 5,
            'amount' => 1030,
            'fee' => 20
        ];
        $client->request('POST', '/api/remit/entry/1/confirm', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300060, $output['code']);
        $this->assertEquals('TranscribeEntry fee has been changed', $output['msg']);
    }

    /**
     * 測試確認入款記錄時人工抄錄明細為空資料
     */
    public function testConfirmRemitWithBlankTranscribeEntry()
    {
        $client = $this->createClient();

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 1);

        // 建立時間三天內才可修改狀態
        $now = new \DateTime('now');
        $fiveMinAgo = $now->sub(new \DateInterval('PT5M'))->format('YmdHis');
        $remitEntry->setCreatedAt($fiveMinAgo);
        $em->flush();

        $params = [
            'operator' => 'odin',
            'transcribe_entry_id' => 4,
            'amount' => 0,
            'fee' => 0
        ];
        $client->request('POST', '/api/remit/entry/1/confirm', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300039, $output['code']);
        $this->assertEquals('Cannot confirm, the transcribe entry is blank', $output['msg']);
    }

    /**
     * 測試確認入款記錄時人工抄錄明細為出款
     */
    public function testConfirmRemitWithTranscribeEntryIsWithdraw()
    {
        $client = $this->createClient();

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 1);

        // 建立時間三天內才可修改狀態
        $now = new \DateTime('now');
        $fiveMinAgo = $now->sub(new \DateInterval('PT5M'))->format('YmdHis');
        $remitEntry->setCreatedAt($fiveMinAgo);
        $em->flush();

        $params = [
            'operator' => 'odin',
            'transcribe_entry_id' => 3,
            'amount' => -1040,
            'fee' => 0
        ];
        $client->request('POST', '/api/remit/entry/1/confirm', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300038, $output['code']);
        $this->assertEquals('Cannot confirm, the transcribe entry is withdrawn', $output['msg']);
    }

    /**
     * 測試確認入款記錄時人工抄錄明細為已確認
     */
    public function testConfirmRemitWithTranscribeEntryIsConfirm()
    {
        $client = $this->createClient();

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 1);

        // 建立時間三天內才可修改狀態
        $now = new \DateTime('now');
        $fiveMinAgo = $now->sub(new \DateInterval('PT5M'))->format('YmdHis');
        $remitEntry->setCreatedAt($fiveMinAgo);
        $em->flush();

        $params = [
            'operator' => 'odin',
            'transcribe_entry_id' => 2,
            'amount' => 1030,
            'fee' => 30
        ];
        $client->request('POST', '/api/remit/entry/1/confirm', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300037, $output['code']);
        $this->assertEquals('Cannot confirm, the transcribe entry is already confirmed', $output['msg']);
    }

    /**
     * 測試確認入款記錄時人工抄錄明細為已刪除
     */
    public function testConfirmRemitWithTranscribeEntryIsDeleted()
    {
        $client = $this->createClient();

        // 先把人工抄錄明細改成已刪除
        $client->request('DELETE', '/api/transcribe/entry/2');

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 1);

        // 建立時間三天內才可修改狀態
        $now = new \DateTime('now');
        $fiveMinAgo = $now->sub(new \DateInterval('PT5M'))->format('YmdHis');
        $remitEntry->setCreatedAt($fiveMinAgo);
        $em->flush();

        $params = [
            'operator' => 'odin',
            'transcribe_entry_id' => 2,
            'amount' => 1030,
            'fee' => 30
        ];
        $client->request('POST', '/api/remit/entry/1/confirm', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300040, $output['code']);
        $this->assertEquals('Cannot confirm, the transcribe entry is already deleted', $output['msg']);
    }

    /**
     * 測試確認入款記錄時代入不合法的人工抄錄明細
     */
    public function testConfirmRemitWithInvalidTranscribeEntry()
    {
        $client = $this->createClient();

        // 先把人工抄錄明細改成已刪除
        $client->request('DELETE', '/api/transcribe/entry/2');

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 1);

        // 建立時間三天內才可修改狀態
        $now = new \DateTime('now');
        $fiveMinAgo = $now->sub(new \DateInterval('PT5M'))->format('YmdHis');
        $remitEntry->setCreatedAt($fiveMinAgo);
        $em->flush();

        $params = [
            'operator' => 'odin',
            'transcribe_entry_id' => 1.55698,
            'amount' => 1000,
            'fee' => 10
        ];
        $client->request('POST', '/api/remit/entry/1/confirm', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300019, $output['code']);
        $this->assertEquals('Invalid transcribe entry id specified', $output['msg']);
    }

    /**
     * 測試確認入款記錄時代入人工抄錄明細金額不合法
     */
    public function testConfirmRemitWithInvalidTranscribeEntryAmount()
    {
        $client = $this->createClient();

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 1);

        // 建立時間三天內才可修改狀態
        $now = new \DateTime('now');
        $fiveMinAgo = $now->sub(new \DateInterval('PT5M'))->format('YmdHis');
        $remitEntry->setCreatedAt($fiveMinAgo);
        $em->flush();

        $params = [
            'operator' => 'odin',
            'transcribe_entry_id' => 22098,
            'fee' => 10
        ];
        $client->request('POST', '/api/remit/entry/1/confirm', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300057, $output['code']);
        $this->assertEquals('Invalid amount specified', $output['msg']);
    }

    /**
     * 測試確認入款記錄時代入人工抄錄明細手續費不合法
     */
    public function testConfirmRemitWithInvalidTranscribeEntryFee()
    {
        $client = $this->createClient();

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 1);

        // 建立時間三天內才可修改狀態
        $now = new \DateTime('now');
        $fiveMinAgo = $now->sub(new \DateInterval('PT5M'))->format('YmdHis');
        $remitEntry->setCreatedAt($fiveMinAgo);
        $em->flush();

        $params = [
            'operator' => 'odin',
            'transcribe_entry_id' => 22098,
            'amount' => 1000
        ];
        $client->request('POST', '/api/remit/entry/1/confirm', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300058, $output['code']);
        $this->assertEquals('Invalid fee specified', $output['msg']);
    }

    /**
     * 測試確認入款記錄時代入不存在的人工抄錄明細
     */
    public function testConfirmRemitWithTranscribeEntryNotExist()
    {
        $client = $this->createClient();

        // 先把人工抄錄明細改成已刪除
        $client->request('DELETE', '/api/transcribe/entry/2');

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 1);

        // 建立時間三天內才可修改狀態
        $now = new \DateTime('now');
        $fiveMinAgo = $now->sub(new \DateInterval('PT5M'))->format('YmdHis');
        $remitEntry->setCreatedAt($fiveMinAgo);
        $em->flush();

        $params = [
            'operator' => 'odin',
            'transcribe_entry_id' => 22098,
            'amount' => 1000,
            'fee' => 10
        ];
        $client->request('POST', '/api/remit/entry/1/confirm', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300016, $output['code']);
        $this->assertEquals('No TranscribeEntry found', $output['msg']);
    }

    /**
     * 測試入款記錄列表
     */
    public function testListRemitEntry()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = [
            'domain' => '2',
            'currency' => 'CNY',
            'ancestor_id' => 3,
            'enable' => 1,
            'bank_info_id' => 1,
            'remit_account_id' => 1,
            'status' => RemitEntry::UNCONFIRM,
            'username' => 'tester',
            'order_number' => 2012010100002459,
            'old_order_number' => 'a1b2c3d4e5',
            'amount_min' => 5,
            'amount_max' => 15,
            'duration_min' => 0,
            'duration_max' => 3600,
            'first_result' => 0,
            'max_results' => 1,
            'sort' => 'id',
            'order' => 'desc',
            'sub_total' => 1,
            'total' => 1
        ];

        $client->request('GET', '/api/remit/entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $param = ['id' => $output['ret'][0]['id']];
        $remitEntry = $em->find('BBDurianBundle:RemitEntry', $param);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($remitEntry->getRemitAccountId(), $output['ret'][0]['remit_account_id']);
        $this->assertEquals($remitEntry->getOrderNumber(), $output['ret'][0]['order_number']);
        $this->assertEquals($remitEntry->getOldOrderNumber(), $output['ret'][0]['old_order_number']);
        $this->assertEquals($remitEntry->getUserId(), $output['ret'][0]['user_id']);
        $this->assertEquals($remitEntry->getLevelId(), $output['ret'][0]['level_id']);
        $this->assertEquals($remitEntry->getAncestorId(), $output['ret'][0]['ancestor_id']);
        $this->assertEquals($remitEntry->getBankInfoId(), $output['ret'][0]['bank_info_id']);
        $this->assertEquals($remitEntry->getNameReal(), $output['ret'][0]['name_real']);
        $this->assertEquals($remitEntry->getMethod(), $output['ret'][0]['method']);
        $this->assertEquals($remitEntry->getBranch(), $output['ret'][0]['branch']);
        $this->assertEquals($remitEntry->getAmount(), $output['ret'][0]['amount']);
        $this->assertEquals($remitEntry->getDiscount(), $output['ret'][0]['discount']);
        $this->assertEquals($remitEntry->getOtherDiscount(), $output['ret'][0]['other_discount']);
        $this->assertEquals($remitEntry->getActualOtherDiscount(), $output['ret'][0]['actual_other_discount']);
        $this->assertEquals($remitEntry->getStatus(), $output['ret'][0]['status']);
        $this->assertEquals($remitEntry->getOperator(), $output['ret'][0]['operator']);
        $this->assertEquals($remitEntry->getCreatedAt()->format(\DateTime::ISO8601), $output['ret'][0]['created_at']);
        $this->assertEquals($remitEntry->getDepositAt()->format(\DateTime::ISO8601), $output['ret'][0]['deposit_at']);
        $this->assertEquals($remitEntry->getDuration(), $output['ret'][0]['duration']);
        $this->assertNull($output['ret'][0]['confirm_at']);

        $this->assertEquals(1, $output['pagination']['total']);
        $this->assertFalse(isset($output['ret'][1]));

        $this->assertEquals(10, $output['sub_total']['amount']);
        $this->assertEquals(0, $output['sub_total']['discount']);
        $this->assertEquals(0, $output['sub_total']['other_discount']);
        $this->assertEquals(5, $output['sub_total']['actual_other_discount']);
        $this->assertEquals(10, $output['sub_total']['total_amount']);
        $this->assertEquals(15, $output['sub_total']['actual_total_amount']);

        $this->assertEquals(10, $output['total']['amount']);
        $this->assertEquals(0, $output['total']['discount']);
        $this->assertEquals(0, $output['total']['other_discount']);
        $this->assertEquals(5, $output['total']['actual_other_discount']);
        $this->assertEquals(10, $output['total']['total_amount']);
        $this->assertEquals(15, $output['total']['actual_total_amount']);
    }

    /**
     * 測試依Domain查詢入款記錄列表
     */
    public function testListRemitEntryByDomain()
    {
        $client = $this->createClient();
        $parameters = [
            'domain' => '9',
            'sort' => 'id',
            'order' => 'asc'
        ];
        $client->request('GET', '/api/remit/entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(5, $output['ret'][0]['id']);
        $this->assertEquals(2012112500080045, $output['ret'][0]['order_number']);
        $this->assertEquals('qwe456z2x1', $output['ret'][0]['old_order_number']);
        $this->assertEquals(RemitEntry::CONFIRM, $output['ret'][0]['status']);
        $this->assertEquals(6, $output['ret'][1]['id']);
        $this->assertEquals(RemitEntry::UNCONFIRM, $output['ret'][1]['status']);
        $this->assertFalse(isset($output['ret'][2]));
    }

    /**
     * 測試依幣別查詢入款記錄列表
     */
    public function testListRemitEntryByCurrency()
    {
        $client = $this->createClient();

        $parameters = [
            'currency' => 'CNY',
            'sort' => 'id',
            'order' => 'desc',
        ];
        $client->request('GET', '/api/remit/entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret'][0]['id']);
        $this->assertEquals(7, $output['ret'][1]['id']);
        $this->assertEquals(3, $output['ret'][2]['id']);
        $this->assertEquals(2, $output['ret'][3]['id']);
        $this->assertEquals(1, $output['ret'][4]['id']);
        $this->assertFalse(isset($output['ret'][5]));
    }

    /**
     * 測試依上層查詢入款記錄列表
     */
    public function testListRemitEntryByAncestor()
    {
        $client = $this->createClient();

        $parameters = ['ancestor_id' => '3'];
        $client->request('GET', '/api/remit/entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertFalse(isset($output['ret'][1]));
    }

    /**
     * 測試依公司帳號停起用查詢入款記錄列表
     */
    public function testListRemitEntryByEnable()
    {
        $client = $this->createClient();

        $parameters = ['enable' => 0];
        $client->request('GET', '/api/remit/entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(4, $output['ret'][0]['id']);
        $this->assertEquals(7, $output['ret'][1]['id']);
        $this->assertFalse(isset($output['ret'][2]));
    }

    /**
     * 測試依公司帳號為刪除狀態查詢入款記錄列表
     */
    public function testListRemitEntryByDeleted()
    {
        $client = $this->createClient();

        $parameters = ['deleted' => 1];
        $client->request('GET', '/api/remit/entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(4, $output['ret'][0]['id']);
        $this->assertFalse(isset($output['ret'][1]));
    }

    /**
     * 測試依公司帳號所屬銀行查詢入款記錄列表
     */
    public function testListRemitEntryByBankInfo()
    {
        $client = $this->createClient();

        $parameters = [
            'bank_info_id' => 1,
            'first_result' => 1,
            'max_results' => 2,
            'sort' => 'id',
            'order' => 'desc',
        ];
        $client->request('GET', '/api/remit/entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(11, $output['ret'][0]['id']);
        $this->assertEquals(10, $output['ret'][1]['id']);
        $this->assertFalse(isset($output['ret'][2]));
    }

    /**
     * 測試依公司帳號查詢入款記錄列表
     */
    public function testListRemitEntryByRemitAccount()
    {
        $client = $this->createClient();

        $parameters = ['remit_account_id' => 2];
        $client->request('GET', '/api/remit/entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(4, $output['ret'][0]['id']);
        $this->assertFalse(isset($output['ret'][1]));
    }

    /**
     * 測試依多個公司帳號查詢入款記錄列表
     */
    public function testListRemitEntryByMultipleRemitAccount()
    {
        $client = $this->createClient();

        $multipleAccountId = [1, 2];
        $parameters = ['remit_account_id' => $multipleAccountId];
        $client->request('GET', '/api/remit/entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(1, $output['ret'][0]['remit_account_id']);
        $this->assertEquals(4, $output['ret'][3]['id']);
        $this->assertEquals(2, $output['ret'][3]['remit_account_id']);

        $this->assertEquals(4, $output['pagination']['total']);
    }

    /**
     * 測試依出入款帳號帳號類別查詢出款記錄列表
     */
    public function testListRemitEntryByRemitAccountType()
    {
        $client = $this->createClient();

        $parameters = ['account_type' => 0];
        $client->request('GET', '/api/remit/entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(4, $output['ret'][0]['id']);
        $this->assertFalse(isset($output['ret'][1]));
    }

    /**
     * 測試帶入不存在的入款帳號
     */
    public function testListRemitEntryWhenRemitAccountNotExist()
    {
        $client = $this->createClient();
        $parameters = ['remit_account_id' => '123'];
        $client->request('GET', '/api/remit/entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEmpty($output['ret']);
    }

    /**
     * 測試依入款狀態查詢入款記錄列表
     */
    public function testListRemitEntryByStatus()
    {
        $client = $this->createClient();

        $parameters = ['status' => RemitEntry::UNCONFIRM];
        $client->request('GET', '/api/remit/entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(2, $output['ret'][1]['id']);
        $this->assertEquals(4, $output['ret'][2]['id']);
        $this->assertEquals(6, $output['ret'][3]['id']);
        $this->assertEquals(7, $output['ret'][4]['id']);
        $this->assertEquals(8, $output['ret'][5]['id']);
        $this->assertEquals(9, $output['ret'][6]['id']);
        $this->assertEquals(10, $output['ret'][7]['id']);
        $this->assertEquals(11, $output['ret'][8]['id']);
        $this->assertEquals(12, $output['ret'][9]['id']);
        $this->assertFalse(isset($output['ret'][10]));
    }

    /**
     * 測試依起始時間查詢紀錄列表
     */
    public function testListRemitEntryByConfirmStart()
    {
        $client = $this->createClient();

        $parameters = ['confirm_start' => '2012-03-05T08:00:00+0800'];
        $client->request('GET', '/api/remit/entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertEquals(3, $output['ret'][1]['id']);
        $this->assertEquals(5, $output['ret'][2]['id']);
        $this->assertCount(3, $output['ret']);
    }

    /**
     * 測試依結束時間查詢紀錄列表
     */
    public function testListRemitEntryByConfirmEnd()
    {
        $client = $this->createClient();

        $parameters = ['confirm_end' => '2012-03-05T08:00:00+0800'];
        $client->request('GET', '/api/remit/entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertCount(1, $output['ret']);
    }

    /**
     * 測試依入款人帳號查詢入款記錄列表
     */
    public function testListRemitEntryByUsername()
    {
        $client = $this->createClient();

        $parameters = ['username' => 'gaga'];
        $client->request('GET', '/api/remit/entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(5, $output['ret'][0]['id']);
        $this->assertEquals(6, $output['ret'][1]['id']);
        $this->assertFalse(isset($output['ret'][2]));
    }

    /**
     * 測試依入款人帳號查詢入款記錄列表，但帳號含有空白
     */
    public function testListRemitEntryByUsernameAndUsernameContainsBlanks()
    {
        $client = $this->createClient();

        $parameters = ['username' => ' gaga '];
        $client->request('GET', '/api/remit/entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(5, $output['ret'][0]['id']);
        $this->assertEquals(6, $output['ret'][1]['id']);
        $this->assertFalse(isset($output['ret'][2]));
    }

    /**
     * 測試依訂單號查詢入款記錄列表
     */
    public function testListRemitEntryByOrderNumber()
    {
        $client = $this->createClient();

        $parameters = ['order_number' => 2012031700036153];
        $client->request('GET', '/api/remit/entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret'][0]['id']);
        $this->assertFalse(isset($output['ret'][1]));
    }

    /**
     * 測試依舊訂單號查詢入款記錄列表
     */
    public function testListRemitEntryByOldOrderNumber()
    {
        $client = $this->createClient();

        $parameters = ['old_order_number' => 'k1l2m3n4o5'];
        $client->request('GET', '/api/remit/entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret'][0]['id']);
        $this->assertFalse(isset($output['ret'][1]));
    }

    /**
     * 測試依存款金額交易明細id查詢入款記錄列表
     */
    public function testListRemitEntryByAmountEntryId()
    {
        $client = $this->createClient();

        $parameters = ['amount_entry_id' => '1'];
        $client->request('GET', '/api/remit/entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(5, $output['ret'][0]['id']);
        $this->assertCount(1, $output['ret']);
    }

    /**
     * 測試依金額區間查詢入款記錄列表
     */
    public function testListRemitEntryByAmountMax()
    {
        $client = $this->createClient();

        $parameters = [
            'amount_min' => '5',
            'amount_max' => '50',
        ];
        $client->request('GET', '/api/remit/entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(12, $output['ret'][1]['id']);
        $this->assertFalse(isset($output['ret'][2]));
    }

    /**
     * 測試依操作時間查詢入款記錄列表
     */
    public function testListRemitEntryByDurationMinMax()
    {
        $client = $this->createClient();
        $parameters = [
            'duration_min' => '270',
            'duration_max' => '630',
        ];
        $client->request('GET', '/api/remit/entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(5, $output['ret'][0]['id']);
        $this->assertFalse(isset($output['ret'][1]));
    }

    /**
     * 測試依入款使用者層級Id查詢入款記錄列表
     */
    public function testListRemitEntryByLevelId()
    {
        $client = $this->createClient();

        $parameters = [
            'level_id' => [0]
        ];
        $client->request('GET', '/api/remit/entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(2, $output['ret'][1]['id']);
        $this->assertEquals(3, $output['ret'][2]['id']);
        $this->assertEquals(4, $output['ret'][3]['id']);
        $this->assertEquals(5, $output['ret'][4]['id']);
        $this->assertEquals(6, $output['ret'][5]['id']);
        $this->assertEquals(7, $output['ret'][6]['id']);
        $this->assertEquals(8, $output['ret'][7]['id']);
        $this->assertEquals(9, $output['ret'][8]['id']);
        $this->assertEquals(10, $output['ret'][9]['id']);
        $this->assertEquals(11, $output['ret'][10]['id']);
        $this->assertEquals(12, $output['ret'][11]['id']);
        $this->assertFalse(isset($output['ret'][12]));
    }

    /**
     * 測試依建立入款記錄起始時間查詢入款記錄列表
     */
    public function testListRemitEntryByCreatedStart()
    {
        $client = $this->createClient();

        $parameters = ['created_start' => '2014-05-07T22:13:35+0800'];
        $client->request('GET', '/api/remit/entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(10, $output['ret'][0]['id']);
        $this->assertEquals(11, $output['ret'][1]['id']);
        $this->assertEquals(12, $output['ret'][2]['id']);
        $this->assertEquals(6, $output['ret'][3]['id']);
        $this->assertEquals(5, $output['ret'][4]['id']);
        $this->assertEquals(2, $output['ret'][5]['id']);
        $this->assertEquals(3, $output['ret'][6]['id']);
        $this->assertEquals(4, $output['ret'][7]['id']);
        $this->assertEquals(7, $output['ret'][8]['id']);
        $this->assertEquals(8, $output['ret'][9]['id']);
        $this->assertEquals(9, $output['ret'][10]['id']);
        $this->assertCount(11, $output['ret']);
    }

    /**
     * 測試依建立入款記錄結束時間查詢入款記錄列表
     */
    public function testListRemitEntryByCreatedEnd()
    {
        $client = $this->createClient();

        $parameters = ['created_end' => '2014-05-07T22:13:35+0800'];
        $client->request('GET', '/api/remit/entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertCount(1, $output['ret']);
    }

    /**
     * 測試依自動認款查詢入款記錄列表
     */
    public function testListRemitEntryByAutoConfirm()
    {
        $client = $this->createClient();

        $parameters = ['auto_confirm' => '1'];
        $client->request('GET', '/api/remit/entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret'][0]['id']);
        $this->assertCount(5, $output['ret']);
    }

    /**
     * 測試依自動認款平台ID查詢入款記錄列表
     */
    public function testListRemitEntryByAutoRemitId()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = ['auto_remit_id' => 999];
        $client->request('GET', '/api/remit/entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(0, $output['pagination']['total']);
    }

    /**
     * 測試入款記錄列表, 帶入錯誤幣別
     */
    public function testListRemitEntryWithInvalidCurrency()
    {
        $client = $this->createClient();

        $parameters = ['currency' => 'AAA'];
        $client->request('GET', '/api/remit/entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300049, $output['code']);
        $this->assertEquals('Currency not support', $output['msg']);
    }

    /**
     * 測試入款記錄列表, 帶入空幣別
     */
    public function testListRemitEntryWithEmptyCurrency()
    {
        $client = $this->createClient();

        $parameters = ['currency' => ''];
        $client->request('GET', '/api/remit/entry/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300049, $output['code']);
        $this->assertEquals('Currency not support', $output['msg']);
    }

    /**
     * 測試取得會員匯款優惠金額
     */
    public function testGetUserRemitDiscount()
    {
        $client = $this->createClient();
        $parameters = [
            'start' => '2012-03-03T23:23:23-0400',
            'end' => '2012-03-05T23:23:23-0400'
        ];
        $client->request('GET', '/api/user/8/remit/discount', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(10, $output['ret']);

        // 跨天
        $parameters = [
            'start' => '2012-03-07T23:23:23-0400',
            'end' => '2012-03-09T23:23:23-0400'
        ];
        $client->request('GET', '/api/user/8/remit/discount', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(30, $output['ret']);

        // 總和
        $parameters = [
            'start' => '2012-03-01T23:23:23-0400',
            'end' => '2012-03-10T23:23:23-0400'
        ];
        $client->request('GET', '/api/user/8/remit/discount', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(40, $output['ret']);
    }

    /**
     * 測試取得會員匯款優惠金額尚無優惠資料
     */
    public function testGetUserRemitDiscountNoData()
    {
        $client = $this->createClient();
        $parameters = [
            'start' => '2012-01-01T11:00:00+0800',
            'end' => '2012-01-10T11:00:00+0800'
        ];
        $client->request('GET', '/api/user/3/remit/discount', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('0.0000', $output['ret']);
    }

    /**
     * 測試取得會員匯款優惠金額缺少開始時間
     */
    public function testGetUserRemitDiscountNoStartAt()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/user/3/remit/discount');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300061, $output['code']);
        $this->assertEquals('No start_at specified', $output['msg']);
    }

    /**
     * 測試取得會員匯款優惠金額缺少結束時間
     */
    public function testGetUserRemitDiscountNoEndAt()
    {
        $client = $this->createClient();
        $parameters = ['start' => '2012-01-01T11:00:00+0800'];
        $client->request('GET', '/api/user/3/remit/discount', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300062, $output['code']);
        $this->assertEquals('No end_at specified', $output['msg']);
    }

    /**
     * 測試取得會員匯款優惠金額使用者不存在
     */
    public function testGetUserRemitDiscountNoUserExist()
    {
        $client = $this->createClient();
        $parameters = [
            'start' => '2012-01-01T11:00:00+0800',
            'end' => '2012-01-10T11:00:00+0800'
        ];
        $client->request('GET', '/api/user/999/remit/discount', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(300054, $output['code']);
        $this->assertEquals('No such user', $output['msg']);
    }

    /**
     * 測試取得該廳「依照使用次數分配銀行卡」的設定
     */
    public function testGetAutoRemitRemitLevelOrder()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/remit/domain/3/remit_level_order', [
            'level_ids' => [1]
        ]);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $remitLevelOrder = $output['ret'][0];

        $this->assertEquals(2, $remitLevelOrder['id']);
        $this->assertEquals(3, $remitLevelOrder['domain']);
        $this->assertEquals(1, $remitLevelOrder['level_id']);
        $this->assertFalse($remitLevelOrder['by_count']);
    }

    /**
     * 測試啟用該廳指定層級「依照使用次數分配銀行卡」的設定
     */
    public function testEnableAutoRemitRemitLevelOrder()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $client->request('PUT', '/api/remit/domain/3/remit_level_order', ['level_ids' => [1, 5]]);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $configs = $em->getRepository('BBDurianBundle:RemitLevelOrder')->findBy(['levelId' => [1, 5]]);

        foreach ($configs as $config) {
            $this->assertTrue($config->getByCount());
        }

        // 檢查操作紀錄
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remit_level_order', $logOperation->getTableName());
        $this->assertEquals('@domain:3', $logOperation->getMajorKey());
        $this->assertEquals('@by_count:true, @level_ids:1,5', $logOperation->getMessage());
    }

    /**
     * 測試關閉該廳指定層級「依照使用次數分配銀行卡」的設定
     */
    public function testDisableAutoRemitRemitLevelOrder()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $client->request('PUT', '/api/remit/domain/3/remit_level_order', [
            'by_count' => false,
            'level_ids' => [1, 5],
        ]);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $configs = $em->getRepository('BBDurianBundle:RemitLevelOrder')->findBy(['levelId' => [1, 5]]);

        foreach ($configs as $config) {
            $this->assertFalse($config->getByCount());
        }

        // 檢查操作紀錄
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remit_level_order', $logOperation->getTableName());
        $this->assertEquals('@domain:3', $logOperation->getMajorKey());
        $this->assertEquals('@by_count:false, @level_ids:1,5', $logOperation->getMessage());
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
