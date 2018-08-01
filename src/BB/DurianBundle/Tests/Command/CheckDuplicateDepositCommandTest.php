<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\RemitEntry;

class CheckDuplicateDepositCommandTest extends WebTestCase
{
    /**
     * redis
     */
    private $redis;

    /**
     * italking Queue Key
     * @var string
     */
    private $italkingKey = 'italking_message_queue';

    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemitEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashDepositEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCardDepositEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCardEntryDataForDuplicateDeposit',
        ];
        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashEntryDataForDuplicateDeposit'
        ];
        $this->loadFixtures($classnames, 'entry');

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigData'
        ];
        $this->loadFixtures($classnames, 'share');

        $this->redis = $this->getContainer()->get('snc_redis.default_client');
        $this->redis->flushdb();
    }

    /**
     * 測試沒有代入時間參數
     */
    public function testNoParam()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $remitEntry1 = $em->find('BBDurianBundle:RemitEntry', 1);
        $remitEntry1->setStatus(RemitEntry::CONFIRM);

        $remitEntry2 = $em->find('BBDurianBundle:RemitEntry', 2);
        $remitEntry2->setStatus(RemitEntry::CONFIRM);

        $cashDepositRepository = $em->getRepository('BBDurianBundle:CashDepositEntry');
        $cashDepositEntry1 = $cashDepositRepository->findOneBy(
            ['id' => 201304280000000001]
        );
        $cashDepositEntry1->confirm();
        $cashDepositEntry1->setEntryId(1);

        $cashDepositEntry2 = $cashDepositRepository->findOneBy(
            ['id' => 201305280000000001]
        );
        $cashDepositEntry2->confirm();
        $cashDepositEntry2->setEntryId(2);

        $cardDepositEntryRepository = $em->getRepository('BBDurianBundle:CardDepositEntry');
        $cardDepositEntry = $cardDepositEntryRepository->findOneBy(
            ['id' => 201502010000000001]
        );
        $cardDepositEntry->confirm();
        $cardDepositEntry->setEntryId(3);

        $em->flush();

        $output = $this->runCommand('durian:check-deposit-duplicate');
        $results = explode(PHP_EOL, $output);

        $expMsg = [
            'ztester,domain2@cm,10,2014-10-02 10:23:37,2012030500003548,2',
            'ztester,domain2@cm,10,2014-10-02 10:23:37,201305280000000001,2',
            'tester,domain2@cm,5,2014-10-02 10:23:37,201502010000000001,2',
        ];

        $this->assertEquals($expMsg[0], $results[2]);
        $this->assertEquals($expMsg[1], $results[5]);
        $this->assertEquals($expMsg[2], $results[8]);

        // 檢查 redis
        $this->assertEquals(3, $this->redis->llen($this->italkingKey));

        $msgFormat = "會員帳號: %s, 廳: %s, 金額: %s, 建立時間: %s, 訂單號: %s, 重複筆數: %s, ";
        $msg = "有公司入款重複, 請檢查!!!\n" .
            sprintf($msgFormat, 'ztester', 'domain2@cm', '10', '2014-10-02 10:23:37', '2012030500003548', '2') .
            "請客服立即至GM管理系統/系統客服管理/異常入款批次停權查看,將會員帳號停權後寄發廳主訊息,並請通知研五-電子商務工程師上線檢查.\n" .
            "有線上入款重複, 請檢查!!!\n" .
            sprintf($msgFormat, 'ztester', 'domain2@cm', '10', '2014-10-02 10:23:37', '201305280000000001', '2') .
            "請客服立即至GM管理系統/系統客服管理/異常入款批次停權查看,將會員帳號停權後寄發廳主訊息,並請通知研五-電子商務工程師上線檢查.\n" .
            "有租卡入款重複, 請檢查!!!\n" .
            sprintf($msgFormat, 'tester', 'domain2@cm', '5', '2014-10-02 10:23:37', '201502010000000001', '2') .
            "請客服立即至GM管理系統/系統客服管理/異常入款批次停權查看,並協助通知邦妮,也請通知研五-電子商務工程師上線檢查.\n";
        $queueMsg = json_decode($this->redis->rpop($this->italkingKey), true);

        $this->assertEquals('developer_acc', $queueMsg['type']);
        $this->assertEquals($msg, $queueMsg['message']);

        $gmMsg = "查詢結果: 公司入款/線上支付 有重複入款情形，請依照下列流程處理：\n\n1.請客服至GM管理系統-系統客服管理-異常入款批" .
            "次停權-現金異常入款-錯誤情況選擇：「重複入款」，將會員帳號停權並寄發廳主訊息，並通知邦妮\n\n2.請通知研五-電子商務工程師上線" .
            "檢查\n\n3.若工程師檢查確實異常，後續依照【額度異常】流程處理";
        $queueMsg = json_decode($this->redis->rpop($this->italkingKey), true);

        $this->assertEquals('acc_system', $queueMsg['type']);
        $this->assertEquals($gmMsg, $queueMsg['message']);
    }

    /**
     * 測試代入兩個正常, 查詢有資料且資料沒有重複的時間參數
     */
    public function testHaveDataAndNoDataDuplicate()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $beginTime = date('Y/m/d H:i:s');

        $remitEntry1 = $em->find('BBDurianBundle:RemitEntry', 1);
        $remitEntry1->setStatus(RemitEntry::CONFIRM);

        $cashDepositRepository = $em->getRepository('BBDurianBundle:CashDepositEntry');
        $cashDepositEntry1 = $cashDepositRepository->findOneBy(
            ['id' => 201304280000000001]
        );
        $cashDepositEntry1->confirm();
        $cashDepositEntry1->setEntryId(1);

        $endTime = date('Y/m/d H:i:s');

        $em->flush();

        $params = [
            '--begin' => $beginTime,
            '--end' => $endTime
        ];
        $output = $this->runCommand('durian:check-deposit-duplicate', $params);
        $results = explode(PHP_EOL, $output);

        $this->assertEquals('線上入款:', $results[2]);
        $this->assertStringStartsWith('Execute time', $results[6]);

        // 檢查 redis
        $this->assertEquals(0, $this->redis->llen($this->italkingKey));
    }

    /**
     * 測試代入兩個正常, 查詢有資料且資料重複的時間參數
     */
    public function testHaveDataAndDataDuplicate()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $beginTime = date('Y/m/d H:i:s');

        $remitEntry1 = $em->find('BBDurianBundle:RemitEntry', 1);
        $remitEntry1->setStatus(RemitEntry::CONFIRM);

        $remitEntry2 = $em->find('BBDurianBundle:RemitEntry', 2);
        $remitEntry2->setStatus(RemitEntry::CONFIRM);

        $remitEntry9 = $em->find('BBDurianBundle:RemitEntry', 9);
        $remitEntry9->setStatus(RemitEntry::CONFIRM);

        $cashDepositRepository = $em->getRepository('BBDurianBundle:CashDepositEntry');
        $cashDepositEntry1 = $cashDepositRepository->findOneBy(
            ['id' => 201304280000000001]
        );
        $cashDepositEntry1->confirm();
        $cashDepositEntry1->setEntryId(1);

        $cashDepositEntry2 = $cashDepositRepository->findOneBy(
            ['id' => 201305280000000001]
        );
        $cashDepositEntry2->confirm();
        $cashDepositEntry2->setEntryId(2);

        $endTime = date('Y/m/d H:i:s');

        $em->flush();

        $params = [
            '--begin' => $beginTime,
            '--end' => $endTime
        ];
        $output = $this->runCommand('durian:check-deposit-duplicate', $params);
        $results = explode(PHP_EOL, $output);

        $expMsg = [
            'ztester,domain2@cm,10,2014-10-02 10:23:37,2012030500003548,2',
            'ztester,domain2@cm,999,2014-10-02 10:23:37,2016101215493577,2',
            'ztester,domain2@cm,10,2014-10-02 10:23:37,201305280000000001,2'
        ];

        $this->assertEquals($expMsg[0], $results[2]);
        $this->assertEquals($expMsg[1], $results[3]);
        $this->assertEquals($expMsg[2], $results[6]);

        // 檢查 redis
        $this->assertEquals(2, $this->redis->llen($this->italkingKey));

        $msgFormat = "會員帳號: %s, 廳: %s, 金額: %s, 建立時間: %s, 訂單號: %s, 重複筆數: %s, ";
        $msg = "有公司入款重複, 請檢查!!!\n" .
            sprintf($msgFormat, 'ztester', 'domain2@cm', '10', '2014-10-02 10:23:37', '2012030500003548', '2') .
            "請客服立即至GM管理系統/系統客服管理/異常入款批次停權查看,將會員帳號停權後寄發廳主訊息,並請通知研五-電子商務工程師上線檢查.\n" .
            sprintf($msgFormat, 'ztester', 'domain2@cm', '999', '2014-10-02 10:23:37', '2016101215493577', '2') .
            "請客服立即至GM管理系統/系統客服管理/異常入款批次停權查看,將會員帳號停權後寄發廳主訊息,並請通知研五-電子商務工程師上線檢查.\n" .
            "有線上入款重複, 請檢查!!!\n" .
            sprintf($msgFormat, 'ztester', 'domain2@cm', '10', '2014-10-02 10:23:37', '201305280000000001', '2') .
            "請客服立即至GM管理系統/系統客服管理/異常入款批次停權查看,將會員帳號停權後寄發廳主訊息,並請通知研五-電子商務工程師上線檢查.\n";
        $queueMsg = json_decode($this->redis->rpop($this->italkingKey), true);

        $this->assertEquals('developer_acc', $queueMsg['type']);
        $this->assertEquals($msg, $queueMsg['message']);

        $gmMsg = "查詢結果: 公司入款/線上支付 有重複入款情形，請依照下列流程處理：\n\n1.請客服至GM管理系統-系統客服管理-異常入款批" .
            "次停權-現金異常入款-錯誤情況選擇：「重複入款」，將會員帳號停權並寄發廳主訊息，並通知邦妮\n\n2.請通知研五-電子商務工程師上線" .
            "檢查\n\n3.若工程師檢查確實異常，後續依照【額度異常】流程處理";
        $queueMsg = json_decode($this->redis->rpop($this->italkingKey), true);

        $this->assertEquals('acc_system', $queueMsg['type']);
        $this->assertEquals($gmMsg, $queueMsg['message']);
    }

    /**
     * 測試代入兩個正常, 查詢沒有資料的時間參數
     */
    public function testNoData()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $beginTime = date('Y/m/d H:i:s');

        $remitEntry1 = $em->find('BBDurianBundle:RemitEntry', 3);
        $remitEntry1->setStatus(RemitEntry::CONFIRM);

        $cashDepositRepository = $em->getRepository('BBDurianBundle:CashDepositEntry');
        $cashDepositEntry1 = $cashDepositRepository->findOneBy(
            ['id' => 201304280000000001]
        );
        $cashDepositEntry1->confirm();
        $cashDepositEntry1->setEntryId(1);

        $endTime = date('Y/m/d H:i:s');

        $em->flush();

        $params = [
            '--begin' => $beginTime,
            '--end' => $endTime
        ];
        $output = $this->runCommand('durian:check-deposit-duplicate', $params);
        $results = explode(PHP_EOL, $output);

        $this->assertEquals('線上入款:', $results[2]);
        $this->assertStringStartsWith('Execute time', $results[6]);

        // 檢查 redis
        $this->assertEquals(0, $this->redis->llen($this->italkingKey));
    }

    /**
     * 測試只代入一個開始時間參數
     */
    public function testOnlyBeginTimeParam()
    {
        $params = ['--begin' => '2014/09/23 14:00:00'];
        $output = $this->runCommand('durian:check-deposit-duplicate', $params);

        $this->assertContains('[Exception]', $output);
        $this->assertContains('需同時指定開始及結束時間', $output);
    }

    /**
     * 測試只代入一個結束時間參數
     */
    public function testOnlyEndTimeParam()
    {
        $params = ['--end' => '2014/09/23 14:00:00'];
        $output = $this->runCommand('durian:check-deposit-duplicate', $params);

        $this->assertContains('[Exception]', $output);
        $this->assertContains('需同時指定開始及結束時間', $output);
    }

    /**
     * 代入兩個異常參數(開始時間比結束時間大)
     */
    public function testBeginTimeGreaterThanEndTime()
    {
        $beginTime = '2014/09/24 14:00:00';
        $endTime = '2014/09/23 14:00:00';

        $params = [
            '--begin' => $beginTime,
            '--end' => $endTime
        ];
        $output = $this->runCommand('durian:check-deposit-duplicate', $params);

        $this->assertContains('[Exception]', $output);
        $this->assertContains('無效的開始及結束時間', $output);
    }
}

