<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Consumer\Poper;
use BB\DurianBundle\Entity\RemitEntry;
use BB\DurianBundle\Entity\UserRemitDiscount;
use BB\DurianBundle\Entity\AutoConfirmEntry;

class AutoConfirmFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemitAccountData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemitEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadAutoConfirmEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelCurrencyData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentChargeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDepositCompanyData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserStatData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemitAccountStatData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainAutoRemitData',
        ];
        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadExchangeData',
        ];
        $this->loadFixtures($classnames, 'share');

        $this->loadFixtures([], 'entry');

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $redis->flushdb();

        $redis = $this->getContainer()->get('snc_redis.sequence');
        $redis->set('cash_seq', 1);
    }

    /**
     * 測試驗證身份
     */
    public function testCheckStatus()
    {
        $params = [
            'login_code' => 'cm',
            'account' => '5432112345',
        ];

        $client = $this->createClient();
        $client->request('GET', '/api/auto_confirm/check_status', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('2017-09-10T13:13:13+0800', $output['ret']['crawler_update']);
    }

    /**
     * 測試驗證身份且取得帳號密碼
     */
    public function testCheckStatusWithBankData()
    {
        $params = [
            'login_code' => 'cm',
            'account' => '5432112345',
            'get_bank_data' => '1',
        ];

        $client = $this->createClient();
        $client->request('GET', '/api/auto_confirm/check_status', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('2017-09-10T13:13:13+0800', $output['ret']['crawler_update']);
        $this->assertEquals('webAccount', $output['ret']['web_bank_account']);
        $this->assertEquals('2bh65gtJBGwNjl7CsDPCIA==', $output['ret']['web_bank_password']);
    }

    /**
     * 測試取得一筆匯款記錄
     */
    public function testGetEntry()
    {
        $client = $this->createClient();
        $client->request('GET', 'api/auto_confirm/entry/1');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertNull($output['ret']['confirm_at']);
        $this->assertFalse($output['ret']['confirm']);
        $this->assertFalse($output['ret']['manual']);
        $this->assertEquals('8', $output['ret']['remit_account_id']);
        $this->assertNull($output['ret']['remit_entry_id']);
        $this->assertEquals('-1', $output['ret']['amount']);
        $this->assertEquals('0', $output['ret']['fee']);
        $this->assertEquals('10', $output['ret']['balance']);
        $this->assertEquals('電子匯入', $output['ret']['method']);
        $this->assertEquals('1234554321', $output['ret']['account']);
        $this->assertEquals('姓名一', $output['ret']['name']);
        $this->assertEquals('2017052400193240', $output['ret']['trade_memo']);
        $this->assertEquals('山西分行轉', $output['ret']['message']);
        $this->assertEquals('備註一', $output['ret']['memo']);
        $this->assertEquals('2017-01-01T01:00:00+0800', $output['ret']['trade_at']);
    }

    /**
     * 測試新增匯款資料
     */
    public function testCreate()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();
        $redis = $this->getContainer()->get('snc_redis.default_client');

        // 先取出原本的額度
        $cashOrigin = $em->find('BBDurianBundle:Cash', 7);
        $balanceOrigin = $cashOrigin->getBalance();

        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 10);
        $remitEntry2 = $em->find('BBDurianBundle:RemitEntry', 11);
        $remitEntry3 = $em->find('BBDurianBundle:RemitEntry', 12);

        $data1 = [
            'amount' => '1000.00',
            'fee' => '0.00',
            'balance' => '1100.00',
            'account' => '1234554321',
            'name' => '張三',
            'time' => '',
            'memo' => '2017052400193239',
            'message' => '山東分行轉',
            'method' => '電子匯入',
        ];

        $data2 = [
            'amount' => '1500.00',
            'fee' => '0.00',
            'balance' => '1535.00',
            'account' => '',
            'name' => '李四',
            'time' => '',
            'memo' => '',
            'message' => '廣東分行轉',
            'method' => '電子匯入',
        ];

        $data3 = [
            'amount' => '20.00',
            'fee' => '0.00',
            'balance' => '1535.00',
            'account' => '1234554321',
            'name' => '',
            'time' => '',
            'memo' => '',
            'message' => '廣東分行轉',
            'method' => '電子匯入',
        ];

        $data = [];
        array_push($data, $data1, $data2, $data3);
        $data = json_encode($data);

        $params = [
            'login_code' => 'cm',
            'data' => $data,
            'balance' => '35.00',
        ];

        $client->request('POST', 'api/remit_account/5432112345/auto_confirm_entry', $params);

        // 跑背景程式讓queue被消化
        $cashPoper = new Poper();
        $cashPoper->runPop($this->getContainer(), 'cash');

        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cashOrigin);
        $em->refresh($remitEntry);
        $em->refresh($remitEntry2);
        $em->refresh($remitEntry3);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals('3', $output['ret'][0]['id']);
        $this->assertTrue($output['ret'][0]['confirm']);
        $this->assertFalse($output['ret'][0]['manual']);
        $this->assertEquals('9', $output['ret'][0]['remit_account_id']);
        $this->assertEquals('10', $output['ret'][0]['remit_entry_id']);
        $this->assertEquals($data1['amount'], $output['ret'][0]['amount']);
        $this->assertEquals($data1['fee'], $output['ret'][0]['fee']);
        $this->assertEquals($data1['balance'], $output['ret'][0]['balance']);
        $this->assertEquals($data1['method'], $output['ret'][0]['method']);
        $this->assertEquals($data1['account'], $output['ret'][0]['account']);
        $this->assertEquals($data1['name'], $output['ret'][0]['name']);
        $this->assertEquals($data1['memo'], $output['ret'][0]['trade_memo']);
        $this->assertEquals($data1['message'], $output['ret'][0]['message']);

        $this->assertEquals('4', $output['ret'][1]['id']);
        $this->assertTrue($output['ret'][1]['confirm']);
        $this->assertFalse($output['ret'][1]['manual']);
        $this->assertEquals('9', $output['ret'][1]['remit_account_id']);
        $this->assertEquals('11', $output['ret'][1]['remit_entry_id']);
        $this->assertEquals($data2['amount'], $output['ret'][1]['amount']);
        $this->assertEquals($data2['fee'], $output['ret'][1]['fee']);
        $this->assertEquals($data2['balance'], $output['ret'][1]['balance']);
        $this->assertEquals($data2['method'], $output['ret'][1]['method']);
        $this->assertEquals($data2['account'], $output['ret'][1]['account']);
        $this->assertEquals($data2['name'], $output['ret'][1]['name']);
        $this->assertEquals($data2['memo'], $output['ret'][1]['trade_memo']);
        $this->assertEquals($data2['message'], $output['ret'][1]['message']);

        $this->assertEquals('5', $output['ret'][2]['id']);
        $this->assertTrue($output['ret'][2]['confirm']);
        $this->assertFalse($output['ret'][2]['manual']);
        $this->assertEquals('9', $output['ret'][2]['remit_account_id']);
        $this->assertEquals('12', $output['ret'][2]['remit_entry_id']);
        $this->assertEquals($data3['amount'], $output['ret'][2]['amount']);
        $this->assertEquals($data3['fee'], $output['ret'][2]['fee']);
        $this->assertEquals($data3['balance'], $output['ret'][2]['balance']);
        $this->assertEquals($data3['method'], $output['ret'][2]['method']);
        $this->assertEquals($data3['account'], $output['ret'][2]['account']);
        $this->assertEquals($data3['name'], $output['ret'][2]['name']);
        $this->assertEquals($data3['memo'], $output['ret'][2]['trade_memo']);
        $this->assertEquals($data3['message'], $output['ret'][2]['message']);

        $this->assertEquals(1, $remitEntry->getStatus());
        $this->assertEquals('BB', $remitEntry->getOperator());
        $this->assertEquals('2', $remitEntry->getAmountEntryId());

        // 金額是否有增加(confirm之後是照實際其他優惠欄位的值來設定其他優惠明細的優惠金額)
        $user = $em->find('BBDurianBundle:User', $remitEntry->getUserId());
        $cash = $user->getCash();
        $amount = $balanceOrigin
            + $remitEntry->getAmount()
            + $remitEntry->getDiscount()
            + $remitEntry->getActualOtherDiscount()
            + $remitEntry2->getAmount()
            + $remitEntry2->getDiscount()
            + $remitEntry2->getActualOtherDiscount()
            + $remitEntry3->getAmount()
            + $remitEntry3->getDiscount()
            + $remitEntry3->getActualOtherDiscount();
        $this->assertEquals($amount, $cash->getBalance());

        // 檢查存款現金明細
        $cashEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $remitEntry->getAmountEntryId()]);
        $this->assertEquals($remitEntry->getAmount(), $cashEntry->getAmount());
        $this->assertEquals($remitEntry->getOrderNumber(), $cashEntry->getRefId());
        $this->assertEquals('1036', $cashEntry->getOpcode());
        $this->assertEquals('操作者： BB', $cashEntry->getMemo());

        $pdwEntry = $em->getRepository('BBDurianBundle:PaymentDepositWithdrawEntry')
            ->findOneBy(['id' => $remitEntry->getAmountEntryId()]);
        $this->assertEquals($remitEntry->getRemitAccountId(), $pdwEntry->getRemitAccountId());

        // 檢查其他優惠現金明細
        $discountEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $remitEntry->getOtherDiscountEntryId()]);
        $this->assertEquals($remitEntry->getActualOtherDiscount(), $discountEntry->getAmount());
        $this->assertEquals($remitEntry->getOrderNumber(), $discountEntry->getRefId());
        $this->assertEquals('1038', $discountEntry->getOpcode());
        $this->assertEquals('操作者： BB', $discountEntry->getMemo());

        // confirm之後匯款明細的其他優惠欄位不會被更改
        $this->assertEquals(1, $remitEntry->getOtherDiscount());

        // 檢查使用者出入款統計資料
        $userStat = $em->find('BBDurianBundle:UserStat', $remitEntry->getUserId());
        $this->assertEquals(3, $userStat->getRemitCount());
        $this->assertEquals(2520, $userStat->getRemitTotal());
        $this->assertEquals(1500, $userStat->getRemitMax());
        $this->assertEquals(1000, $userStat->getFirstDepositAmount());

        // 檢查LogOperation
        $logOp1 = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remit_entry', $logOp1->getTableName());
        $this->assertEquals('@id:10', $logOp1->getMajorKey());
        $this->assertEquals('@discount:60=>150', $logOp1->getMessage());

        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $msg = '@remit_count:0=>1, @remit_total:0=>1000, @remit_max:0=>1000.0000';
        $msg .= ', @first_deposit_at:' . $userStat->getFirstDepositAt()->format(\DateTime::ISO8601);
        $msg .= ', @first_deposit_amount:1000.0000, @modified_at:';
        $this->assertEquals('user_stat', $logOp2->getTableName());
        $this->assertEquals('@user_id:8', $logOp2->getMajorKey());
        $this->assertContains($msg, $logOp2->getMessage());

        $logOp3 = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertEquals('remit_entry', $logOp3->getTableName());
        $this->assertEquals('@id:10', $logOp3->getMajorKey());
        $message = [
            '@status:' . RemitEntry::UNCONFIRM . '=>' . RemitEntry::CONFIRM,
            '@operator:=>' . $remitEntry->getOperator(),
            '@duration:' . $remitEntry->getDuration(),
            '@amount_entry_id:' . $remitEntry->getAmountEntryId(),
            '@discount_entry_id:' . $remitEntry->getDiscountEntryId(),
            '@other_discount_entry_id:' . $remitEntry->getOtherDiscountEntryId(),
        ];
        $this->assertEquals(implode(', ', $message), $logOp3->getMessage());

        $logOp4 = $emShare->find('BBDurianBundle:LogOperation', 4);
        $this->assertEquals('auto_confirm_entry', $logOp4->getTableName());
        $this->assertEquals('@id:3', $logOp4->getMajorKey());
        $this->assertEquals('@remit_entry_id:10, @confirm:true', $logOp4->getMessage());

        $logOp5 = $emShare->find('BBDurianBundle:LogOperation', 5);
        $msg = '@remit_count:1=>2, @remit_total:1000=>2500, @remit_max:1000.0000=>1500.0000';
        $msg .= ', @modified_at:';
        $this->assertEquals('user_stat', $logOp5->getTableName());
        $this->assertEquals('@user_id:8', $logOp5->getMajorKey());
        $this->assertContains($msg, $logOp5->getMessage());

        $logOp6 = $emShare->find('BBDurianBundle:LogOperation', 6);
        $this->assertEquals('remit_entry', $logOp6->getTableName());
        $this->assertEquals('@id:11', $logOp6->getMajorKey());
        $message = [
            '@status:' . RemitEntry::UNCONFIRM . '=>' . RemitEntry::CONFIRM,
            '@operator:=>' . $remitEntry2->getOperator(),
            '@duration:' . $remitEntry2->getDuration(),
            '@amount_entry_id:' . $remitEntry2->getAmountEntryId(),
        ];
        $this->assertEquals(implode(', ', $message), $logOp6->getMessage());

        $logOp7 = $emShare->find('BBDurianBundle:LogOperation', 7);
        $this->assertEquals('auto_confirm_entry', $logOp7->getTableName());
        $this->assertEquals('@id:4', $logOp7->getMajorKey());
        $this->assertEquals('@remit_entry_id:11, @confirm:true', $logOp7->getMessage());

        $logOp8 = $emShare->find('BBDurianBundle:LogOperation', 8);
        $msg = '@remit_count:2=>3, @remit_total:2500=>2520, @modified_at:';
        $this->assertEquals('user_stat', $logOp8->getTableName());
        $this->assertEquals('@user_id:8', $logOp8->getMajorKey());
        $this->assertContains($msg, $logOp8->getMessage());

        $logOp9 = $emShare->find('BBDurianBundle:LogOperation', 9);
        $this->assertEquals('remit_entry', $logOp9->getTableName());
        $this->assertEquals('@id:12', $logOp9->getMajorKey());
        $message = [
            '@status:' . RemitEntry::UNCONFIRM . '=>' . RemitEntry::CONFIRM,
            '@operator:=>' . $remitEntry3->getOperator(),
            '@duration:' . $remitEntry3->getDuration(),
            '@amount_entry_id:' . $remitEntry3->getAmountEntryId(),
        ];
        $this->assertEquals(implode(', ', $message), $logOp9->getMessage());

        $logOp10 = $emShare->find('BBDurianBundle:LogOperation', 10);
        $this->assertEquals('auto_confirm_entry', $logOp10->getTableName());
        $this->assertEquals('@id:5', $logOp10->getMajorKey());
        $this->assertEquals('@remit_entry_id:12, @confirm:true', $logOp10->getMessage());

        // 檢查每日優惠資料
        $dailyDiscount = $em->find('BBDurianBundle:UserRemitDiscount', 1);
        $this->assertEquals(1, $dailyDiscount->getDiscount());

        // 檢查統計入款金額queue
        $statDeposit = json_decode($redis->rpop('stat_domain_deposit_queue'), true);
        $this->assertEquals(2, $statDeposit['domain']);
        $this->assertEquals('20.0000', $statDeposit['amount']);

        $statDeposit = json_decode($redis->rpop('stat_domain_deposit_queue'), true);
        $this->assertEquals(2, $statDeposit['domain']);
        $this->assertEquals('1500.0000', $statDeposit['amount']);

        $statDeposit = json_decode($redis->rpop('stat_domain_deposit_queue'), true);
        $this->assertEquals(2, $statDeposit['domain']);
        $this->assertEquals('1000.0000', $statDeposit['amount']);

        // 檢查通知稽核 queue
        $auditParams = json_decode($redis->rpop('audit_queue'), true);
        $this->assertEquals(10, $auditParams['remit_entry_id']);
        $this->assertEquals(8, $auditParams['user_id']);
        $this->assertEquals(2151, $auditParams['balance']);
        $this->assertEquals(1000, $auditParams['amount']);
        $this->assertEquals(150, $auditParams['offer']);
        $this->assertEquals('0', $auditParams['fee']);
        $this->assertEquals('N', $auditParams['abandonsp']);
        $this->assertEquals($remitEntry->getConfirmAt()->format('Y-m-d H:i:s'), $auditParams['deposit_time']);
        $this->assertEquals(1, $auditParams['auto_confirm']);

        $auditParams2 = json_decode($redis->rpop('audit_queue'), true);
        $this->assertEquals(11, $auditParams2['remit_entry_id']);
        $this->assertEquals(8, $auditParams2['user_id']);
        $this->assertEquals(3651, $auditParams2['balance']);
        $this->assertEquals(1500, $auditParams2['amount']);
        $this->assertEquals(0, $auditParams2['offer']);
        $this->assertEquals('0', $auditParams2['fee']);
        $this->assertEquals('N', $auditParams2['abandonsp']);
        $this->assertEquals($remitEntry2->getConfirmAt()->format('Y-m-d H:i:s'), $auditParams2['deposit_time']);
        $this->assertEquals(1, $auditParams2['auto_confirm']);

        $auditParams3 = json_decode($redis->rpop('audit_queue'), true);
        $this->assertEquals(12, $auditParams3['remit_entry_id']);
        $this->assertEquals(8, $auditParams3['user_id']);
        $this->assertEquals(3671, $auditParams3['balance']);
        $this->assertEquals(20, $auditParams3['amount']);
        $this->assertEquals(0, $auditParams3['offer']);
        $this->assertEquals('0', $auditParams3['fee']);
        $this->assertEquals('N', $auditParams3['abandonsp']);
        $this->assertEquals($remitEntry3->getConfirmAt()->format('Y-m-d H:i:s'), $auditParams3['deposit_time']);
        $this->assertEquals(1, $auditParams3['auto_confirm']);
    }

    /**
     * 測試新增匯款資料，支付寶成功匹配
     */
    public function testCreateMatchByAliPay()
    {
        $client = $this->createClient();

        $data1 = [
            'amount' => '1500.00',
            'fee' => '0.00',
            'balance' => '1535.00',
            'account' => '',
            'name' => '支付宝（中国）网络技术有限公司客户备付金',
            'time' => '',
            'memo' => '李四支付宝转账',
            'message' => '',
            'method' => '转账',
        ];

        $data = [];
        array_push($data, $data1);
        $data = json_encode($data);

        $params = [
            'login_code' => 'cm',
            'data' => $data,
            'balance' => '35.00',
        ];

        $client->request('POST', 'api/remit_account/5432112345/auto_confirm_entry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals('3', $output['ret'][0]['id']);
        $this->assertTrue($output['ret'][0]['confirm']);
        $this->assertFalse($output['ret'][0]['manual']);
        $this->assertEquals('9', $output['ret'][0]['remit_account_id']);
        $this->assertEquals('11', $output['ret'][0]['remit_entry_id']);
        $this->assertEquals($data1['amount'], $output['ret'][0]['amount']);
        $this->assertEquals($data1['fee'], $output['ret'][0]['fee']);
        $this->assertEquals($data1['balance'], $output['ret'][0]['balance']);
        $this->assertEquals($data1['method'], $output['ret'][0]['method']);
        $this->assertEquals($data1['account'], $output['ret'][0]['account']);
        $this->assertEquals($data1['name'], $output['ret'][0]['name']);
        $this->assertEquals($data1['memo'], $output['ret'][0]['trade_memo']);
        $this->assertEquals($data1['message'], $output['ret'][0]['message']);
    }

    /**
     * 測試新增匯款資料，支付寶成功匹配，姓名為支付宝转账
     */
    public function testCreateMatchByAliPayWithSameNameReal()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 11);
        $remitEntry->setNameReal('支付宝转账');
        $em->flush();

        $client = $this->createClient();

        $data1 = [
            'amount' => '1500.00',
            'fee' => '0.00',
            'balance' => '1535.00',
            'account' => '',
            'name' => '支付宝（中国）网络技术有限公司客户备付金',
            'time' => '',
            'memo' => '支付宝转账支付宝转账',
            'message' => '廣東分行轉',
            'method' => '转账',
        ];

        $data = [];
        array_push($data, $data1);
        $data = json_encode($data);

        $params = [
            'login_code' => 'cm',
            'data' => $data,
            'balance' => '35.00',
        ];

        $client->request('POST', 'api/remit_account/5432112345/auto_confirm_entry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals('3', $output['ret'][0]['id']);
        $this->assertTrue($output['ret'][0]['confirm']);
        $this->assertFalse($output['ret'][0]['manual']);
        $this->assertEquals('9', $output['ret'][0]['remit_account_id']);
        $this->assertEquals('11', $output['ret'][0]['remit_entry_id']);
        $this->assertEquals($data1['amount'], $output['ret'][0]['amount']);
        $this->assertEquals($data1['fee'], $output['ret'][0]['fee']);
        $this->assertEquals($data1['balance'], $output['ret'][0]['balance']);
        $this->assertEquals($data1['method'], $output['ret'][0]['method']);
        $this->assertEquals($data1['account'], $output['ret'][0]['account']);
        $this->assertEquals($data1['name'], $output['ret'][0]['name']);
        $this->assertEquals($data1['memo'], $output['ret'][0]['trade_memo']);
        $this->assertEquals($data1['message'], $output['ret'][0]['message']);
    }

    /**
     * 測試新增匯款資料，未匹配
     */
    public function testCreateNotMatch()
    {
        $client = $this->createClient();

        $data1 = [
            'amount' => '100.00',
            'fee' => '0.00',
            'balance' => '1100.00',
            'account' => '1234554321',
            'name' => '張三',
            'time' => '',
            'memo' => '2017052400193239',
            'message' => '山東分行轉',
            'method' => '電子匯入',
        ];

        $data2 = [
            'amount' => '150.00',
            'fee' => '0.00',
            'balance' => '1535.00',
            'account' => '',
            'name' => '李四',
            'time' => '',
            'memo' => '',
            'message' => '廣東分行轉',
            'method' => '電子匯入',
        ];

        $data3 = [
            'amount' => '2.00',
            'fee' => '0.00',
            'balance' => '1535.00',
            'account' => '1234554321',
            'name' => '',
            'time' => '',
            'memo' => '',
            'message' => '廣東分行轉',
            'method' => '電子匯入',
        ];

        $data = [];
        array_push($data, $data1, $data2, $data3);
        $data = json_encode($data);

        $params = [
            'login_code' => 'cm',
            'data' => $data,
            'balance' => '35.00',
        ];

        $client->request('POST', 'api/remit_account/5432112345/auto_confirm_entry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals('3', $output['ret'][0]['id']);
        $this->assertFalse($output['ret'][0]['confirm']);
        $this->assertFalse($output['ret'][0]['manual']);
        $this->assertEquals('9', $output['ret'][0]['remit_account_id']);
        $this->assertNull($output['ret'][0]['remit_entry_id']);
        $this->assertEquals($data1['amount'], $output['ret'][0]['amount']);
        $this->assertEquals($data1['fee'], $output['ret'][0]['fee']);
        $this->assertEquals($data1['balance'], $output['ret'][0]['balance']);
        $this->assertEquals($data1['method'], $output['ret'][0]['method']);
        $this->assertEquals($data1['account'], $output['ret'][0]['account']);
        $this->assertEquals($data1['name'], $output['ret'][0]['name']);
        $this->assertEquals($data1['memo'], $output['ret'][0]['trade_memo']);
        $this->assertEquals($data1['message'], $output['ret'][0]['message']);

        $this->assertEquals('4', $output['ret'][1]['id']);
        $this->assertFalse($output['ret'][1]['confirm']);
        $this->assertFalse($output['ret'][1]['manual']);
        $this->assertEquals('9', $output['ret'][1]['remit_account_id']);
        $this->assertNull($output['ret'][1]['remit_entry_id']);
        $this->assertEquals($data2['amount'], $output['ret'][1]['amount']);
        $this->assertEquals($data2['fee'], $output['ret'][1]['fee']);
        $this->assertEquals($data2['balance'], $output['ret'][1]['balance']);
        $this->assertEquals($data2['method'], $output['ret'][1]['method']);
        $this->assertEquals($data2['account'], $output['ret'][1]['account']);
        $this->assertEquals($data2['name'], $output['ret'][1]['name']);
        $this->assertEquals($data2['memo'], $output['ret'][1]['trade_memo']);
        $this->assertEquals($data2['message'], $output['ret'][1]['message']);

        $this->assertEquals('5', $output['ret'][2]['id']);
        $this->assertFalse($output['ret'][2]['confirm']);
        $this->assertFalse($output['ret'][2]['manual']);
        $this->assertEquals('9', $output['ret'][2]['remit_account_id']);
        $this->assertNull($output['ret'][2]['remit_entry_id']);
        $this->assertEquals($data3['amount'], $output['ret'][2]['amount']);
        $this->assertEquals($data3['fee'], $output['ret'][2]['fee']);
        $this->assertEquals($data3['balance'], $output['ret'][2]['balance']);
        $this->assertEquals($data3['method'], $output['ret'][2]['method']);
        $this->assertEquals($data3['account'], $output['ret'][2]['account']);
        $this->assertEquals($data3['name'], $output['ret'][2]['name']);
        $this->assertEquals($data3['memo'], $output['ret'][2]['trade_memo']);
        $this->assertEquals($data3['message'], $output['ret'][2]['message']);
    }

    /**
     * 測試新增匯款資料，交易姓名為空字串
     */
    public function testCreateWithEmptyName()
    {
        $client = $this->createClient();

        $data2 = [
            'amount' => '1500.00',
            'fee' => '0.00',
            'balance' => '1535.00',
            'account' => '',
            'name' => '',
            'time' => '',
            'memo' => '',
            'message' => '廣東分行轉',
            'method' => '電子匯入',
        ];

        $data = [];
        array_push($data, $data2);
        $data = json_encode($data);

        $params = [
            'login_code' => 'cm',
            'data' => $data,
            'balance' => '35.00',
        ];

        $client->request('POST', 'api/remit_account/5432112345/auto_confirm_entry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals('3', $output['ret'][0]['id']);
        $this->assertFalse($output['ret'][0]['confirm']);
        $this->assertFalse($output['ret'][0]['manual']);
        $this->assertEquals('9', $output['ret'][0]['remit_account_id']);
        $this->assertNull($output['ret'][0]['remit_entry_id']);
        $this->assertEquals($data2['amount'], $output['ret'][0]['amount']);
        $this->assertEquals($data2['fee'], $output['ret'][0]['fee']);
        $this->assertEquals($data2['balance'], $output['ret'][0]['balance']);
        $this->assertEquals($data2['method'], $output['ret'][0]['method']);
        $this->assertEquals($data2['account'], $output['ret'][0]['account']);
        $this->assertEquals($data2['name'], $output['ret'][0]['name']);
        $this->assertEquals($data2['memo'], $output['ret'][0]['trade_memo']);
        $this->assertEquals($data2['message'], $output['ret'][0]['message']);
    }

    /**
     * 測試新增匯款資料，交易帳號為空
     */
    public function testCreateWithEmptyAccount()
    {
        $client = $this->createClient();

        $data3 = [
            'amount' => '20.00',
            'fee' => '0.00',
            'balance' => '1535.00',
            'account' => '',
            'name' => '帳號空字串',
            'time' => '',
            'memo' => '',
            'message' => '廣東分行轉',
            'method' => '電子匯入',
        ];

        $data = [];
        array_push($data, $data3);
        $data = json_encode($data);

        $params = [
            'login_code' => 'cm',
            'data' => $data,
            'balance' => '35.00',
        ];

        $client->request('POST', 'api/remit_account/5432112345/auto_confirm_entry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals('3', $output['ret'][0]['id']);
        $this->assertFalse($output['ret'][0]['confirm']);
        $this->assertFalse($output['ret'][0]['manual']);
        $this->assertEquals('9', $output['ret'][0]['remit_account_id']);
        $this->assertNull($output['ret'][0]['remit_entry_id']);
        $this->assertEquals($data3['amount'], $output['ret'][0]['amount']);
        $this->assertEquals($data3['fee'], $output['ret'][0]['fee']);
        $this->assertEquals($data3['balance'], $output['ret'][0]['balance']);
        $this->assertEquals($data3['method'], $output['ret'][0]['method']);
        $this->assertEquals($data3['account'], $output['ret'][0]['account']);
        $this->assertEquals($data3['name'], $output['ret'][0]['name']);
        $this->assertEquals($data3['memo'], $output['ret'][0]['trade_memo']);
        $this->assertEquals($data3['message'], $output['ret'][0]['message']);
    }

    /**
     * 測試新增匯款資料，支付寶匹配失敗，明細姓名不正確
     */
    public function testCreateNotMatchByAliPayButWrongName()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 11);
        $remitEntry->setMethod(8);
        $em->flush();

        $client = $this->createClient();

        $data1 = [
            'amount' => '1500.00',
            'fee' => '0.00',
            'balance' => '1535.00',
            'account' => '',
            'name' => '支付宝宝不是支付宝',
            'time' => '',
            'memo' => '李四支付宝转账',
            'message' => '',
            'method' => '转账',
        ];

        $data = [];
        array_push($data, $data1);
        $data = json_encode($data);

        $params = [
            'login_code' => 'cm',
            'data' => $data,
            'balance' => '35.00',
        ];

        $client->request('POST', 'api/remit_account/5432112345/auto_confirm_entry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals('3', $output['ret'][0]['id']);
        $this->assertFalse($output['ret'][0]['confirm']);
        $this->assertFalse($output['ret'][0]['manual']);
        $this->assertEquals('9', $output['ret'][0]['remit_account_id']);
        $this->assertNull($output['ret'][0]['remit_entry_id']);
        $this->assertEquals($data1['amount'], $output['ret'][0]['amount']);
        $this->assertEquals($data1['fee'], $output['ret'][0]['fee']);
        $this->assertEquals($data1['balance'], $output['ret'][0]['balance']);
        $this->assertEquals($data1['method'], $output['ret'][0]['method']);
        $this->assertEquals($data1['account'], $output['ret'][0]['account']);
        $this->assertEquals($data1['name'], $output['ret'][0]['name']);
        $this->assertEquals($data1['memo'], $output['ret'][0]['trade_memo']);
        $this->assertEquals($data1['message'], $output['ret'][0]['message']);
    }

    /**
     * 測試新增匯款資料，但金額超過50萬
     */
    public function testCreateWithAbnormalAmount()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 10);
        $remitEntry->setAmount(25000000);
        $em->persist($remitEntry);
        $em->flush();

        // 先取出原本的額度
        $cashOrigin = $em->find('BBDurianBundle:Cash', 7);
        $balanceOrigin = $cashOrigin->getBalance();

        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 10);

        $data1 = [
            'amount' => '25000000.00',
            'fee' => '0.00',
            'balance' => '25000000.00',
            'account' => '1234554321',
            'name' => '張三',
            'time' => '',
            'memo' => '2017052400193239',
            'message' => '山東分行轉',
            'method' => '電子匯入',
        ];

        $data = [];
        array_push($data, $data1);
        $data = json_encode($data);

        $params = [
            'login_code' => 'cm',
            'data' => $data,
            'balance' => '25000000.00',
        ];

        $client->request('POST', 'api/remit_account/5432112345/auto_confirm_entry', $params);

        // 跑背景程式讓queue被消化
        $cashPoper = new Poper();
        $cashPoper->runPop($this->getContainer(), 'cash');

        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cashOrigin);
        $em->refresh($remitEntry);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(1, $remitEntry->getStatus());
        $this->assertEquals('BB', $remitEntry->getOperator());
        $this->assertEquals('2', $remitEntry->getAmountEntryId());
        $this->assertEquals('3', $remitEntry->getDiscountEntryId());
        $this->assertEquals('4', $remitEntry->getOtherDiscountEntryId());

        // 金額是否有增加(confirm之後是照實際其他優惠欄位的值來設定其他優惠明細的優惠金額)
        $user = $em->find('BBDurianBundle:User', $remitEntry->getUserId());
        $cash = $user->getCash();
        $amount = $balanceOrigin
            + $remitEntry->getAmount()
            + $remitEntry->getDiscount()
            + $remitEntry->getActualOtherDiscount();
        $this->assertEquals($amount, $cash->getBalance());

        // 檢查存款優惠明細
        $cashEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $remitEntry->getAmountEntryId()]);
        $this->assertEquals(7, $cashEntry->getCashId());
        $this->assertEquals(2, $cashEntry->getId());
        $this->assertEquals(1036, $cashEntry->getOpcode());
        $this->assertEquals('25000000', $cashEntry->getAmount());
        $this->assertEquals(25001000, $cashEntry->getBalance());
        $this->assertEquals('操作者： BB', $cashEntry->getMemo());

        $pdwEntry = $em->getRepository('BBDurianBundle:PaymentDepositWithdrawEntry')
            ->findOneBy(['id' => $remitEntry->getAmountEntryId()]);
        $this->assertEquals($remitEntry->getRemitAccountId(), $pdwEntry->getRemitAccountId());

        // 檢查其他優惠明細(confirm後其他優惠明細記錄的優惠金額是實際其他優惠)
        $discountEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $remitEntry->getOtherDiscountEntryId()]);
        $this->assertEquals($remitEntry->getActualOtherDiscount(), $discountEntry->getAmount());
        $this->assertEquals($remitEntry->getOrderNumber(), $discountEntry->getRefId());
        $this->assertEquals('1038', $discountEntry->getOpcode());
        $this->assertEquals('操作者： BB', $discountEntry->getMemo());

        // 檢查使用者出入款統計資料
        $userStat = $em->find('BBDurianBundle:UserStat', $remitEntry->getUserId());
        $this->assertEquals(1, $userStat->getRemitCount());
        $this->assertEquals(25000000, $userStat->getRemitTotal());
        $this->assertEquals(25000000, $userStat->getRemitMax());
        $this->assertEquals(25000000, $userStat->getFirstDepositAmount());

        // 檢查LogOperation
        $logOp1 = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remit_account', $logOp1->getTableName());
        $this->assertEquals('@id:9', $logOp1->getMajorKey());
        $this->assertEquals('@suspend:false=>true', $logOp1->getMessage());

        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('remit_entry', $logOp2->getTableName());
        $this->assertEquals('@id:10', $logOp2->getMajorKey());
        $this->assertEquals('@discount:60=>10000', $logOp2->getMessage());

        $logOp3 = $emShare->find('BBDurianBundle:LogOperation', 3);
        $msg = '@remit_count:0=>1, @remit_total:0=>25000000, @remit_max:0=>25000000.0000';
        $msg .= ', @first_deposit_at:' . $userStat->getFirstDepositAt()->format(\DateTime::ISO8601);
        $msg .= ', @first_deposit_amount:25000000.0000, @modified_at:';
        $this->assertEquals('user_stat', $logOp3->getTableName());
        $this->assertEquals('@user_id:8', $logOp3->getMajorKey());
        $this->assertContains($msg, $logOp3->getMessage());

        $logOp4 = $emShare->find('BBDurianBundle:LogOperation', 4);
        $this->assertEquals('remit_entry', $logOp4->getTableName());
        $this->assertEquals('@id:10', $logOp4->getMajorKey());
        $message = [
            '@status:' . RemitEntry::UNCONFIRM . '=>' . RemitEntry::CONFIRM,
            '@operator:=>' . $remitEntry->getOperator(),
            '@duration:' . $remitEntry->getDuration(),
            '@amount_entry_id:' . $remitEntry->getAmountEntryId(),
            '@discount_entry_id:' . $remitEntry->getDiscountEntryId(),
            '@other_discount_entry_id:' . $remitEntry->getOtherDiscountEntryId(),
        ];
        $this->assertEquals(implode(', ', $message), $logOp4->getMessage());

        $logOp5 = $emShare->find('BBDurianBundle:LogOperation', 5);
        $this->assertEquals('auto_confirm_entry', $logOp5->getTableName());
        $this->assertEquals('@id:3', $logOp5->getMajorKey());
        $this->assertEquals('@remit_entry_id:10, @confirm:true', $logOp5->getMessage());

        // 檢查異常入款提醒 queue
        $abnormalDepositNotify = json_decode($redis->rpop('abnormal_deposit_notify_queue'), true);
        $this->assertEquals(2, $abnormalDepositNotify['domain']);
        $this->assertEquals('tester', $abnormalDepositNotify['user_name']);
        $this->assertEquals(1036, $abnormalDepositNotify['opcode']);
        $this->assertEquals('BB', $abnormalDepositNotify['operator']);
        $this->assertEquals('25000000.0000', $abnormalDepositNotify['amount']);
    }

    /**
     * 測試新增匯款資料，但非首次存款
     */
    public function testCreateWithNotFirstDeposit()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();
        $redis = $this->getContainer()->get('snc_redis.default_client');

        // 先取出原本的額度
        $cashOrigin = $em->find('BBDurianBundle:Cash', 7);
        $balanceOrigin = $cashOrigin->getBalance();

        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 10);

        $sql = 'UPDATE deposit_company SET discount = 2 WHERE id = 5';
        $em->getConnection()->executeUpdate($sql);

        $time = (new \DateTime())->format('His') > date('120000') ?
            date('Y-m-d 12:00:00') :
            date('Y-m-d 12:00:00', strtotime('-1 day'));
        $time = new \DateTime($time);
        $user = $em->find('BBDurianBundle:User', 8);
        $userRemitDiscount = new UserRemitDiscount($user, $time);
        $userRemitDiscount->addDiscount(0.01);

        $em->persist($userRemitDiscount);
        $em->flush();

        $data1 = [
            'amount' => '1000.00',
            'fee' => '0.00',
            'balance' => '1100.00',
            'account' => '1234554321',
            'name' => '張三',
            'time' => '',
            'memo' => '2017052400193239',
            'message' => '山東分行轉',
            'method' => '電子匯入',
        ];

        $data = [];
        array_push($data, $data1);
        $data = json_encode($data);

        $params = [
            'login_code' => 'cm',
            'data' => $data,
            'balance' => '35.00',
        ];

        $client->request('POST', 'api/remit_account/5432112345/auto_confirm_entry', $params);

        // 跑背景程式讓queue被消化
        $cashPoper = new Poper();
        $cashPoper->runPop($this->getContainer(), 'cash');

        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cashOrigin);
        $em->refresh($remitEntry);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 金額是否有增加(confirm之後是照實際其他優惠欄位的值來設定其他優惠明細的優惠金額)
        $user = $em->find('BBDurianBundle:User', $remitEntry->getUserId());
        $cash = $user->getCash();
        $amount = $balanceOrigin
            + $remitEntry->getAmount()
            + $remitEntry->getDiscount()
            + $remitEntry->getActualOtherDiscount();
        $this->assertEquals($amount, $cash->getBalance());

        // 檢查存款優惠明細
        $cashEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $remitEntry->getAmountEntryId()]);
        $this->assertEquals('1000', $cashEntry->getAmount());
        $this->assertEquals(2000, $cashEntry->getBalance());
        $this->assertEquals(1036, $cashEntry->getOpcode());
        $this->assertEquals('操作者： BB', $cashEntry->getMemo());

        $pdwEntry = $em->getRepository('BBDurianBundle:PaymentDepositWithdrawEntry')
            ->findOneBy(['id' => $remitEntry->getAmountEntryId()]);
        $this->assertEquals($remitEntry->getRemitAccountId(), $pdwEntry->getRemitAccountId());

        // 檢查其他優惠明細(confirm後其他優惠明細記錄的優惠金額是實際其他優惠)
        $discountEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $remitEntry->getDiscountEntryId()]);
        $this->assertEquals($remitEntry->getDiscount(), $discountEntry->getAmount());
        $this->assertEquals($remitEntry->getOrderNumber(), $discountEntry->getRefId());
        $this->assertEquals('1037', $discountEntry->getOpcode());
        $this->assertEquals('操作者： BB', $discountEntry->getMemo());

        // confirm之後匯款明細的其他優惠欄位不會被更改
        $this->assertEquals(1, $remitEntry->getOtherDiscount());

        // 檢查使用者出入款統計資料
        $userStat = $em->find('BBDurianBundle:UserStat', $remitEntry->getUserId());
        $this->assertEquals(1, $userStat->getRemitCount());
        $this->assertEquals(1000, $userStat->getRemitTotal());
        $this->assertEquals(1000, $userStat->getRemitMax());
        $this->assertEquals(1000, $userStat->getFirstDepositAmount());

        // 檢查LogOperation
        $logOp1 = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remit_entry', $logOp1->getTableName());
        $this->assertEquals('@id:10', $logOp1->getMajorKey());
        $this->assertEquals('@discount:60=>150', $logOp1->getMessage());

        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $msg = '@remit_count:0=>1, @remit_total:0=>1000, @remit_max:0=>1000.0000';
        $msg .= ', @first_deposit_at:' . $userStat->getFirstDepositAt()->format(\DateTime::ISO8601);
        $msg .= ', @first_deposit_amount:1000.0000, @modified_at:';
        $this->assertEquals('user_stat', $logOp2->getTableName());
        $this->assertEquals('@user_id:8', $logOp2->getMajorKey());
        $this->assertContains($msg, $logOp2->getMessage());

        $logOp3 = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertEquals('remit_entry', $logOp3->getTableName());
        $this->assertEquals('@id:10', $logOp3->getMajorKey());
        $message = [
            '@status:' . RemitEntry::UNCONFIRM . '=>' . RemitEntry::CONFIRM,
            '@operator:=>' . $remitEntry->getOperator(),
            '@duration:' . $remitEntry->getDuration(),
            '@amount_entry_id:' . $remitEntry->getAmountEntryId(),
            '@discount_entry_id:' . $remitEntry->getDiscountEntryId(),
            '@other_discount_entry_id:' . $remitEntry->getOtherDiscountEntryId(),
        ];
        $this->assertEquals(implode(', ', $message), $logOp3->getMessage());

        $logOp4 = $emShare->find('BBDurianBundle:LogOperation', 4);
        $this->assertEquals('auto_confirm_entry', $logOp4->getTableName());
        $this->assertEquals('@id:3', $logOp4->getMajorKey());
        $this->assertEquals('@remit_entry_id:10, @confirm:true', $logOp4->getMessage());

        // 檢查每日優惠資料
        $dailyDiscount = $em->find('BBDurianBundle:UserRemitDiscount', 1);
        $this->assertEquals(0.01, $dailyDiscount->getDiscount());

        // 檢查統計入款金額queue
        $statDeposit = json_decode($redis->rpop('stat_domain_deposit_queue'), true);
        $this->assertEquals(2, $statDeposit['domain']);
        $this->assertEquals('1000.0000', $statDeposit['amount']);

        // 檢查通知稽核 queue
        $auditParams = json_decode($redis->rpop('audit_queue'), true);
        $this->assertEquals(10, $auditParams['remit_entry_id']);
        $this->assertEquals(8, $auditParams['user_id']);
        $this->assertEquals(2151, $auditParams['balance']);
        $this->assertEquals(1000, $auditParams['amount']);
        $this->assertEquals(150, $auditParams['offer']);
        $this->assertEquals('0', $auditParams['fee']);
        $this->assertEquals('N', $auditParams['abandonsp']);
        $this->assertEquals($remitEntry->getConfirmAt()->format('Y-m-d H:i:s'), $auditParams['deposit_time']);
        $this->assertEquals(1, $auditParams['auto_confirm']);
    }

    /**
     * 測試新增匯款資料，單日 + 本次 < 單日可領
     */
    public function testCreateWithDailyAndOtherLessThanDailyLimit()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();
        $redis = $this->getContainer()->get('snc_redis.default_client');

        // 先取出原本的額度
        $cashOrigin = $em->find('BBDurianBundle:Cash', 7);
        $balanceOrigin = $cashOrigin->getBalance();

        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 10);

        $sql = 'UPDATE deposit_company SET daily_discount_limit = 100 WHERE id = 5';
        $em->getConnection()->executeUpdate($sql);

        $time = (new \DateTime())->format('His') > date('120000') ?
            date('Y-m-d 12:00:00') :
            date('Y-m-d 12:00:00', strtotime('-1 day'));
        $time = new \DateTime($time);
        $user = $em->find('BBDurianBundle:User', 8);
        $userRemitDiscount = new UserRemitDiscount($user, $time);
        $userRemitDiscount->addDiscount(10);

        $em->persist($userRemitDiscount);
        $em->flush();

        $data1 = [
            'amount' => '1000.00',
            'fee' => '0.00',
            'balance' => '1100.00',
            'account' => '1234554321',
            'name' => '張三',
            'time' => '',
            'memo' => '2017052400193239',
            'message' => '山東分行轉',
            'method' => '電子匯入',
        ];

        $data = [];
        array_push($data, $data1);
        $data = json_encode($data);

        $params = [
            'login_code' => 'cm',
            'data' => $data,
            'balance' => '35.00',
        ];

        $client->request('POST', 'api/remit_account/5432112345/auto_confirm_entry', $params);

        // 跑背景程式讓queue被消化
        $cashPoper = new Poper();
        $cashPoper->runPop($this->getContainer(), 'cash');

        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cashOrigin);
        $em->refresh($remitEntry);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 金額是否有增加(confirm之後是照實際其他優惠欄位的值來設定其他優惠明細的優惠金額)
        $user = $em->find('BBDurianBundle:User', $remitEntry->getUserId());
        $cash = $user->getCash();
        $amount = $balanceOrigin
            + $remitEntry->getAmount()
            + $remitEntry->getDiscount()
            + $remitEntry->getActualOtherDiscount();
        $this->assertEquals($amount, $cash->getBalance());

        // 檢查存款優惠明細
        $cashEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $remitEntry->getAmountEntryId()]);
        $this->assertEquals(7, $cashEntry->getCashId());
        $this->assertEquals(2, $cashEntry->getId());
        $this->assertEquals(1036, $cashEntry->getOpcode());
        $this->assertEquals('1000', $cashEntry->getAmount());
        $this->assertEquals(2000, $cashEntry->getBalance());
        $this->assertEquals('操作者： BB', $cashEntry->getMemo());

        $pdwEntry = $em->getRepository('BBDurianBundle:PaymentDepositWithdrawEntry')
            ->findOneBy(['id' => $remitEntry->getAmountEntryId()]);
        $this->assertEquals($remitEntry->getRemitAccountId(), $pdwEntry->getRemitAccountId());

        // 檢查其他優惠明細(confirm後其他優惠明細記錄的優惠金額是實際其他優惠)
        $discountEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $remitEntry->getOtherDiscountEntryId()]);
        $this->assertEquals($remitEntry->getActualOtherDiscount(), $discountEntry->getAmount());
        $this->assertEquals($remitEntry->getOrderNumber(), $discountEntry->getRefId());
        $this->assertEquals('1038', $discountEntry->getOpcode());
        $this->assertEquals('操作者： BB', $discountEntry->getMemo());

        // confirm之後匯款明細的其他優惠欄位不會被更改
        $this->assertEquals(1, $remitEntry->getOtherDiscount());

        // 檢查使用者出入款統計資料
        $userStat = $em->find('BBDurianBundle:UserStat', $remitEntry->getUserId());
        $this->assertEquals(1, $userStat->getRemitCount());
        $this->assertEquals(1000, $userStat->getRemitTotal());
        $this->assertEquals(1000, $userStat->getRemitMax());
        $this->assertEquals(1000, $userStat->getFirstDepositAmount());

        // 檢查LogOperation
        $logOp1 = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remit_entry', $logOp1->getTableName());
        $this->assertEquals('@id:10', $logOp1->getMajorKey());
        $this->assertEquals('@discount:60=>150', $logOp1->getMessage());

        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $msg = '@remit_count:0=>1, @remit_total:0=>1000, @remit_max:0=>1000.0000';
        $msg .= ', @first_deposit_at:' . $userStat->getFirstDepositAt()->format(\DateTime::ISO8601);
        $msg .= ', @first_deposit_amount:1000.0000, @modified_at:';
        $this->assertEquals('user_stat', $logOp2->getTableName());
        $this->assertEquals('@user_id:8', $logOp2->getMajorKey());
        $this->assertContains($msg, $logOp2->getMessage());

        $logOp3 = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertEquals('remit_entry', $logOp3->getTableName());
        $this->assertEquals('@id:10', $logOp3->getMajorKey());
        $message = [
            '@status:' . RemitEntry::UNCONFIRM . '=>' . RemitEntry::CONFIRM,
            '@operator:=>' . $remitEntry->getOperator(),
            '@duration:' . $remitEntry->getDuration(),
            '@amount_entry_id:' . $remitEntry->getAmountEntryId(),
            '@discount_entry_id:' . $remitEntry->getDiscountEntryId(),
            '@other_discount_entry_id:' . $remitEntry->getOtherDiscountEntryId(),
        ];
        $this->assertEquals(implode(', ', $message), $logOp3->getMessage());

        $logOp4 = $emShare->find('BBDurianBundle:LogOperation', 4);
        $this->assertEquals('auto_confirm_entry', $logOp4->getTableName());
        $this->assertEquals('@id:3', $logOp4->getMajorKey());
        $this->assertEquals('@remit_entry_id:10, @confirm:true', $logOp4->getMessage());

        // 檢查每日優惠資料
        $dailyDiscount = $em->find('BBDurianBundle:UserRemitDiscount', 1);
        $this->assertEquals(10, $dailyDiscount->getDiscount());

        // 檢查統計入款金額queue
        $statDeposit = json_decode($redis->rpop('stat_domain_deposit_queue'), true);
        $this->assertEquals(2, $statDeposit['domain']);
        $this->assertEquals('1000.0000', $statDeposit['amount']);

        // 檢查通知稽核 queue
        $auditParams = json_decode($redis->rpop('audit_queue'), true);
        $this->assertEquals(10, $auditParams['remit_entry_id']);
        $this->assertEquals(8, $auditParams['user_id']);
        $this->assertEquals(2151, $auditParams['balance']);
        $this->assertEquals(1000, $auditParams['amount']);
        $this->assertEquals(150, $auditParams['offer']);
        $this->assertEquals('0', $auditParams['fee']);
        $this->assertEquals('N', $auditParams['abandonsp']);
        $this->assertEquals($remitEntry->getConfirmAt()->format('Y-m-d H:i:s'), $auditParams['deposit_time']);
        $this->assertEquals(1, $auditParams['auto_confirm']);
    }

    /**
     * 測試新增匯款資料，其他 = 單日 - 單日已領
     */
    public function testCreateWithLimitMinusDaily()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();
        $redis = $this->getContainer()->get('snc_redis.default_client');

        // 先取出原本的額度
        $cashOrigin = $em->find('BBDurianBundle:Cash', 7);
        $balanceOrigin = $cashOrigin->getBalance();

        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 10);

        $sql = 'UPDATE deposit_company SET daily_discount_limit = 100 WHERE id = 5';
        $em->getConnection()->executeUpdate($sql);

        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 10);
        $remitEntry->setOtherDiscount(60);
        $em->persist($remitEntry);

        $time = (new \DateTime())->format('His') > date('120000') ?
            date('Y-m-d 12:00:00') :
            date('Y-m-d 12:00:00', strtotime('-1 day'));
        $time = new \DateTime($time);
        $user = $em->find('BBDurianBundle:User', 8);
        $userRemitDiscount = new UserRemitDiscount($user, $time);
        $userRemitDiscount->addDiscount(50);

        $em->persist($userRemitDiscount);
        $em->flush();

        $data1 = [
            'amount' => '1000.00',
            'fee' => '0.00',
            'balance' => '1100.00',
            'account' => '1234554321',
            'name' => '張三',
            'time' => '',
            'memo' => '2017052400193239',
            'message' => '山東分行轉',
            'method' => '電子匯入',
        ];

        $data = [];
        array_push($data, $data1);
        $data = json_encode($data);

        $params = [
            'login_code' => 'cm',
            'data' => $data,
            'balance' => '35.00',
        ];

        $client->request('POST', 'api/remit_account/5432112345/auto_confirm_entry', $params);

        // 跑背景程式讓queue被消化
        $cashPoper = new Poper();
        $cashPoper->runPop($this->getContainer(), 'cash');

        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cashOrigin);
        $em->refresh($remitEntry);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 金額是否有增加(confirm之後是照實際其他優惠欄位的值來設定其他優惠明細的優惠金額)
        $user = $em->find('BBDurianBundle:User', $remitEntry->getUserId());
        $cash = $user->getCash();
        $amount = $balanceOrigin
            + $remitEntry->getAmount()
            + $remitEntry->getDiscount()
            + $remitEntry->getActualOtherDiscount();
        $this->assertEquals($amount, $cash->getBalance());

        // 檢查存款優惠明細
        $cashEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $remitEntry->getAmountEntryId()]);
        $this->assertEquals(7, $cashEntry->getCashId());
        $this->assertEquals(2, $cashEntry->getId());
        $this->assertEquals(1036, $cashEntry->getOpcode());
        $this->assertEquals('1000', $cashEntry->getAmount());
        $this->assertEquals('2000', $cashEntry->getBalance());
        $this->assertEquals('操作者： BB', $cashEntry->getMemo());

        $pdwEntry = $em->getRepository('BBDurianBundle:PaymentDepositWithdrawEntry')
            ->findOneBy(['id' => $remitEntry->getAmountEntryId()]);
        $this->assertEquals($remitEntry->getRemitAccountId(), $pdwEntry->getRemitAccountId());

        // 檢查其他優惠明細(confirm後其他優惠明細記錄的優惠金額是實際其他優惠)
        $discountEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $remitEntry->getOtherDiscountEntryId()]);
        $this->assertEquals($remitEntry->getActualOtherDiscount(), $discountEntry->getAmount());
        $this->assertEquals($remitEntry->getOrderNumber(), $discountEntry->getRefId());
        $this->assertEquals('1038', $discountEntry->getOpcode());
        $this->assertEquals('操作者： BB', $discountEntry->getMemo());

        // confirm之後匯款明細的其他優惠欄位不會被更改
        $this->assertEquals(60, $remitEntry->getOtherDiscount());

        // 檢查使用者出入款統計資料
        $userStat = $em->find('BBDurianBundle:UserStat', $remitEntry->getUserId());
        $this->assertEquals(1, $userStat->getRemitCount());
        $this->assertEquals(1000, $userStat->getRemitTotal());
        $this->assertEquals(1000, $userStat->getRemitMax());
        $this->assertEquals(1000, $userStat->getFirstDepositAmount());

        // 檢查LogOperation
        $logOp1 = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remit_entry', $logOp1->getTableName());
        $this->assertEquals('@id:10', $logOp1->getMajorKey());
        $this->assertEquals('@discount:60=>150, @actual_other_discount:1=>50', $logOp1->getMessage());

        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $msg = '@remit_count:0=>1, @remit_total:0=>1000, @remit_max:0=>1000.0000';
        $msg .= ', @first_deposit_at:' . $userStat->getFirstDepositAt()->format(\DateTime::ISO8601);
        $msg .= ', @first_deposit_amount:1000.0000, @modified_at:';
        $this->assertEquals('user_stat', $logOp2->getTableName());
        $this->assertEquals('@user_id:8', $logOp2->getMajorKey());
        $this->assertContains($msg, $logOp2->getMessage());

        $logOp3 = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertEquals('remit_entry', $logOp3->getTableName());
        $this->assertEquals('@id:10', $logOp3->getMajorKey());
        $message = [
            '@status:' . RemitEntry::UNCONFIRM . '=>' . RemitEntry::CONFIRM,
            '@operator:=>' . $remitEntry->getOperator(),
            '@duration:' . $remitEntry->getDuration(),
            '@amount_entry_id:' . $remitEntry->getAmountEntryId(),
            '@discount_entry_id:' . $remitEntry->getDiscountEntryId(),
            '@other_discount_entry_id:' . $remitEntry->getOtherDiscountEntryId(),
        ];
        $this->assertEquals(implode(', ', $message), $logOp3->getMessage());

        $logOp4 = $emShare->find('BBDurianBundle:LogOperation', 4);
        $this->assertEquals('auto_confirm_entry', $logOp4->getTableName());
        $this->assertEquals('@id:3', $logOp4->getMajorKey());
        $this->assertEquals('@remit_entry_id:10, @confirm:true', $logOp4->getMessage());

        // 檢查每日優惠資料
        $dailyDiscount = $em->find('BBDurianBundle:UserRemitDiscount', 1);
        $this->assertEquals(50, $dailyDiscount->getDiscount());

        // 檢查統計入款金額queue
        $statDeposit = json_decode($redis->rpop('stat_domain_deposit_queue'), true);
        $this->assertEquals(2, $statDeposit['domain']);
        $this->assertEquals('1000.0000', $statDeposit['amount']);

        // 檢查通知稽核 queue
        $auditParams = json_decode($redis->rpop('audit_queue'), true);
        $this->assertEquals(10, $auditParams['remit_entry_id']);
        $this->assertEquals(8, $auditParams['user_id']);
        $this->assertEquals(2200, $auditParams['balance']);
        $this->assertEquals(1000, $auditParams['amount']);
        $this->assertEquals(150, $auditParams['offer']);
        $this->assertEquals('0', $auditParams['fee']);
        $this->assertEquals('N', $auditParams['abandonsp']);
        $this->assertEquals($remitEntry->getConfirmAt()->format('Y-m-d H:i:s'), $auditParams['deposit_time']);
        $this->assertEquals(1, $auditParams['auto_confirm']);
    }

    /**
     * 測試新增匯款資料，但放棄優惠
     */
    public function testCreateWithAbandonDiscount()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 10);
        $remitEntry->abandonDiscount();
        $em->persist($remitEntry);

        $time = (new \DateTime())->format('His') > date('120000') ?
            date('Y-m-d 12:00:00') :
            date('Y-m-d 12:00:00', strtotime('-1 day'));
        $time = new \DateTime($time);
        $user = $em->find('BBDurianBundle:User', 8);
        $userRemitDiscount = new UserRemitDiscount($user, $time);
        $userRemitDiscount->addDiscount(0.01);

        $em->persist($userRemitDiscount);
        $em->flush();

        $data1 = [
            'amount' => '1000.00',
            'fee' => '0.00',
            'balance' => '1100.00',
            'account' => '1234554321',
            'name' => '張三',
            'time' => '',
            'memo' => '2017052400193239',
            'message' => '山東分行轉',
            'method' => '電子匯入',
        ];

        $data = [];
        array_push($data, $data1);
        $data = json_encode($data);

        $params = [
            'login_code' => 'cm',
            'data' => $data,
            'balance' => '35.00',
        ];

        $client->request('POST', 'api/remit_account/5432112345/auto_confirm_entry', $params);

        //跑背景程式讓queue被消化
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($remitEntry);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 檢查通知稽核 queue
        $auditParams = json_decode($redis->rpop('audit_queue'), true);

        $this->assertEquals(10, $auditParams['remit_entry_id']);
        $this->assertEquals(8, $auditParams['user_id']);
        $this->assertEquals(2061, $auditParams['balance']);
        $this->assertEquals(1000, $auditParams['amount']);
        $this->assertEquals(60, $auditParams['offer']);
        $this->assertEquals(0, $auditParams['fee']);
        $this->assertEquals('Y', $auditParams['abandonsp']);
        $this->assertEquals($remitEntry->getConfirmAt()->format('Y-m-d H:i:s'), $auditParams['deposit_time']);
        $this->assertEquals(1, $auditParams['auto_confirm']);
    }

    /**
     * 測試新增匯款資料，但沒有出入款統計資料
     */
    public function testCreateWithNoUserStat()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();
        $redis = $this->getContainer()->get('snc_redis.default_client');

        // 先取出原本的額度
        $cashOrigin = $em->find('BBDurianBundle:Cash', 7);
        $balanceOrigin = $cashOrigin->getBalance();

        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 10);

        $userStat = $em->find('BBDurianBundle:UserStat', 8);
        $em->remove($userStat);
        $em->flush();

        $data1 = [
            'amount' => '1000.00',
            'fee' => '0.00',
            'balance' => '1100.00',
            'account' => '1234554321',
            'name' => '張三',
            'time' => '',
            'memo' => '2017052400193239',
            'message' => '山東分行轉',
            'method' => '電子匯入',
        ];

        $data = [];
        array_push($data, $data1);
        $data = json_encode($data);

        $params = [
            'login_code' => 'cm',
            'data' => $data,
            'balance' => '35.00',
        ];

        $client = $this->createClient();
        $client->request('POST', 'api/remit_account/5432112345/auto_confirm_entry', $params);

        // 跑背景程式讓queue被消化
        $cashPoper = new Poper();
        $cashPoper->runPop($this->getContainer(), 'cash');

        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cashOrigin);
        $em->refresh($remitEntry);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 金額是否有增加(confirm之後是照實際其他優惠欄位的值來設定其他優惠明細的優惠金額)
        $user = $em->find('BBDurianBundle:User', $remitEntry->getUserId());
        $cash = $user->getCash();
        $amount = $balanceOrigin
            + $remitEntry->getAmount()
            + $remitEntry->getDiscount()
            + $remitEntry->getActualOtherDiscount();
        $this->assertEquals($amount, $cash->getBalance());

        // 檢查存款優惠明細
        $cashEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $remitEntry->getAmountEntryId()]);
        $this->assertEquals(7, $cashEntry->getCashId());
        $this->assertEquals(2, $cashEntry->getId());
        $this->assertEquals(1036, $cashEntry->getOpcode());
        $this->assertEquals('1000', $cashEntry->getAmount());
        $this->assertEquals('2000', $cashEntry->getBalance());
        $this->assertEquals('操作者： BB', $cashEntry->getMemo());

        $pdwEntry = $em->getRepository('BBDurianBundle:PaymentDepositWithdrawEntry')
            ->findOneBy(['id' => $remitEntry->getAmountEntryId()]);
        $this->assertEquals($remitEntry->getRemitAccountId(), $pdwEntry->getRemitAccountId());

        // 檢查其他優惠明細(confirm後其他優惠明細記錄的優惠金額是實際其他優惠)
        $discountEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $remitEntry->getOtherDiscountEntryId()]);
        $this->assertEquals($remitEntry->getActualOtherDiscount(), $discountEntry->getAmount());
        $this->assertEquals($remitEntry->getOrderNumber(), $discountEntry->getRefId());
        $this->assertEquals('1038', $discountEntry->getOpcode());
        $this->assertEquals('操作者： BB', $discountEntry->getMemo());

        // confirm之後匯款明細的其他優惠欄位不會被更改
        $this->assertEquals(1, $remitEntry->getOtherDiscount());

        // 檢查使用者出入款統計資料
        $userStat = $em->find('BBDurianBundle:UserStat', $remitEntry->getUserId());
        $this->assertEquals(1, $userStat->getRemitCount());
        $this->assertEquals(1000, $userStat->getRemitTotal());
        $this->assertEquals(1000, $userStat->getRemitMax());
        $this->assertEquals(1000, $userStat->getFirstDepositAmount());

        // 檢查LogOperation
        $logOp1 = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remit_entry', $logOp1->getTableName());
        $this->assertEquals('@id:10', $logOp1->getMajorKey());
        $this->assertEquals('@discount:60=>150', $logOp1->getMessage());

        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $msg = '@remit_count:0=>1, @remit_total:0=>1000, @remit_max:0=>1000.0000';
        $msg .= ', @first_deposit_at:' . $userStat->getFirstDepositAt()->format(\DateTime::ISO8601);
        $msg .= ', @first_deposit_amount:1000.0000, @modified_at:';
        $this->assertEquals('user_stat', $logOp2->getTableName());
        $this->assertEquals('@user_id:8', $logOp2->getMajorKey());
        $this->assertContains($msg, $logOp2->getMessage());

        $logOp3 = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertEquals('remit_entry', $logOp3->getTableName());
        $this->assertEquals('@id:10', $logOp3->getMajorKey());
        $message = [
            '@status:' . RemitEntry::UNCONFIRM . '=>' . RemitEntry::CONFIRM,
            '@operator:=>' . $remitEntry->getOperator(),
            '@duration:' . $remitEntry->getDuration(),
            '@amount_entry_id:' . $remitEntry->getAmountEntryId(),
            '@discount_entry_id:' . $remitEntry->getDiscountEntryId(),
            '@other_discount_entry_id:' . $remitEntry->getOtherDiscountEntryId(),
        ];
        $this->assertEquals(implode(', ', $message), $logOp3->getMessage());

        $logOp4 = $emShare->find('BBDurianBundle:LogOperation', 4);
        $this->assertEquals('auto_confirm_entry', $logOp4->getTableName());
        $this->assertEquals('@id:3', $logOp4->getMajorKey());
        $this->assertEquals('@remit_entry_id:10, @confirm:true', $logOp4->getMessage());

        // 檢查每日優惠資料
        $dailyDiscount = $em->find('BBDurianBundle:UserRemitDiscount', 1);
        $this->assertEquals(1, $dailyDiscount->getDiscount());

        // 檢查統計入款金額queue
        $statDeposit = json_decode($redis->rpop('stat_domain_deposit_queue'), true);
        $this->assertEquals(2, $statDeposit['domain']);
        $this->assertEquals('1000.0000', $statDeposit['amount']);

        // 檢查通知稽核 queue
        $auditParams = json_decode($redis->rpop('audit_queue'), true);
        $this->assertEquals(10, $auditParams['remit_entry_id']);
        $this->assertEquals(8, $auditParams['user_id']);
        $this->assertEquals(2151, $auditParams['balance']);
        $this->assertEquals(1000, $auditParams['amount']);
        $this->assertEquals(150, $auditParams['offer']);
        $this->assertEquals('0', $auditParams['fee']);
        $this->assertEquals('N', $auditParams['abandonsp']);
        $this->assertEquals($remitEntry->getConfirmAt()->format('Y-m-d H:i:s'), $auditParams['deposit_time']);
        $this->assertEquals(1, $auditParams['auto_confirm']);
    }

    /**
     * 測試新增匯款資料，但沒有payment_charge
     */
    public function testCreateWithNoPaymentCharge()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();
        $redis = $this->getContainer()->get('snc_redis.default_client');

        // 先取出原本的額度
        $cashOrigin = $em->find('BBDurianBundle:Cash', 7);
        $balanceOrigin = $cashOrigin->getBalance();

        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 10);

        $paymentCharge = $em->find('BBDurianBundle:PaymentCharge', 5);
        $paymentCharge->setCode('TWD');
        $em->persist($paymentCharge);
        $em->flush();

        $sql = 'UPDATE level_currency SET payment_charge_id = null WHERE level_id = 2 AND currency = 901';
        $em->getConnection()->executeUpdate($sql);

        $sql = 'UPDATE payment_charge SET preset = 1, code = \'TWD\' WHERE id = 5';
        $em->getConnection()->executeUpdate($sql);

        $data1 = [
            'amount' => '1000.00',
            'fee' => '0.00',
            'balance' => '1100.00',
            'account' => '1234554321',
            'name' => '張三',
            'time' => '',
            'memo' => '2017052400193239',
            'message' => '山東分行轉',
            'method' => '電子匯入',
        ];

        $data = [];
        array_push($data, $data1);
        $data = json_encode($data);

        $params = [
            'login_code' => 'cm',
            'data' => $data,
            'balance' => '35.00',
        ];

        $client->request('POST', 'api/remit_account/5432112345/auto_confirm_entry', $params);

        // 跑背景程式讓queue被消化
        $cashPoper = new Poper();
        $cashPoper->runPop($this->getContainer(), 'cash');

        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cashOrigin);
        $em->refresh($remitEntry);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 金額是否有增加(confirm之後是照實際其他優惠欄位的值來設定其他優惠明細的優惠金額)
        $user = $em->find('BBDurianBundle:User', $remitEntry->getUserId());
        $cash = $user->getCash();
        $amount = $balanceOrigin
            + $remitEntry->getAmount()
            + $remitEntry->getDiscount()
            + $remitEntry->getActualOtherDiscount();
        $this->assertEquals($amount, $cash->getBalance());

        // 檢查存款優惠明細
        $cashEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $remitEntry->getAmountEntryId()]);
        $this->assertEquals(7, $cashEntry->getCashId());
        $this->assertEquals(2, $cashEntry->getId());
        $this->assertEquals(1036, $cashEntry->getOpcode());
        $this->assertEquals('1000', $cashEntry->getAmount());
        $this->assertEquals(2000, $cashEntry->getBalance());
        $this->assertEquals('操作者： BB', $cashEntry->getMemo());

        $pdwEntry = $em->getRepository('BBDurianBundle:PaymentDepositWithdrawEntry')
            ->findOneBy(['id' => $remitEntry->getAmountEntryId()]);
        $this->assertEquals($remitEntry->getRemitAccountId(), $pdwEntry->getRemitAccountId());

        // 檢查其他優惠明細(confirm後其他優惠明細記錄的優惠金額是實際其他優惠)
        $discountEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $remitEntry->getOtherDiscountEntryId()]);
        $this->assertEquals($remitEntry->getActualOtherDiscount(), $discountEntry->getAmount());
        $this->assertEquals($remitEntry->getOrderNumber(), $discountEntry->getRefId());
        $this->assertEquals('1038', $discountEntry->getOpcode());
        $this->assertEquals('操作者： BB', $discountEntry->getMemo());

        // confirm之後匯款明細的其他優惠欄位不會被更改
        $this->assertEquals(1, $remitEntry->getOtherDiscount());

        // 檢查使用者出入款統計資料
        $userStat = $em->find('BBDurianBundle:UserStat', $remitEntry->getUserId());
        $this->assertEquals(1, $userStat->getRemitCount());
        $this->assertEquals(1000, $userStat->getRemitTotal());
        $this->assertEquals(1000, $userStat->getRemitMax());
        $this->assertEquals(1000, $userStat->getFirstDepositAmount());

        // 檢查LogOperation
        $logOp1 = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remit_entry', $logOp1->getTableName());
        $this->assertEquals('@id:10', $logOp1->getMajorKey());
        $this->assertEquals('@discount:60=>150', $logOp1->getMessage());

        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $msg = '@remit_count:0=>1, @remit_total:0=>1000, @remit_max:0=>1000.0000';
        $msg .= ', @first_deposit_at:' . $userStat->getFirstDepositAt()->format(\DateTime::ISO8601);
        $msg .= ', @first_deposit_amount:1000.0000, @modified_at:';
        $this->assertEquals('user_stat', $logOp2->getTableName());
        $this->assertEquals('@user_id:8', $logOp2->getMajorKey());
        $this->assertContains($msg, $logOp2->getMessage());

        $logOp3 = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertEquals('remit_entry', $logOp3->getTableName());
        $this->assertEquals('@id:10', $logOp3->getMajorKey());
        $message = [
            '@status:' . RemitEntry::UNCONFIRM . '=>' . RemitEntry::CONFIRM,
            '@operator:=>' . $remitEntry->getOperator(),
            '@duration:' . $remitEntry->getDuration(),
            '@amount_entry_id:' . $remitEntry->getAmountEntryId(),
            '@discount_entry_id:' . $remitEntry->getDiscountEntryId(),
            '@other_discount_entry_id:' . $remitEntry->getOtherDiscountEntryId(),
        ];
        $this->assertEquals(implode(', ', $message), $logOp3->getMessage());

        $logOp4 = $emShare->find('BBDurianBundle:LogOperation', 4);
        $this->assertEquals('auto_confirm_entry', $logOp4->getTableName());
        $this->assertEquals('@id:3', $logOp4->getMajorKey());
        $this->assertEquals('@remit_entry_id:10, @confirm:true', $logOp4->getMessage());

        // 檢查每日優惠資料
        $dailyDiscount = $em->find('BBDurianBundle:UserRemitDiscount', 1);
        $this->assertEquals(1, $dailyDiscount->getDiscount());

        // 檢查統計入款金額queue
        $statDeposit = json_decode($redis->rpop('stat_domain_deposit_queue'), true);
        $this->assertEquals(2, $statDeposit['domain']);
        $this->assertEquals('1000.0000', $statDeposit['amount']);

        // 檢查通知稽核 queue
        $auditParams = json_decode($redis->rpop('audit_queue'), true);
        $this->assertEquals(10, $auditParams['remit_entry_id']);
        $this->assertEquals(8, $auditParams['user_id']);
        $this->assertEquals(2151, $auditParams['balance']);
        $this->assertEquals(1000, $auditParams['amount']);
        $this->assertEquals(150, $auditParams['offer']);
        $this->assertEquals('0', $auditParams['fee']);
        $this->assertEquals('N', $auditParams['abandonsp']);
        $this->assertEquals($remitEntry->getConfirmAt()->format('Y-m-d H:i:s'), $auditParams['deposit_time']);
        $this->assertEquals(1, $auditParams['auto_confirm']);

        $reg = '/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+0800/';

        $this->assertEquals($data1['amount'], $output['ret'][0]['amount']);
        $this->assertEquals($data1['fee'], $output['ret'][0]['fee']);
        $this->assertEquals($data1['balance'], $output['ret'][0]['balance']);
        $this->assertEquals($data1['name'], $output['ret'][0]['name']);
        $this->assertEquals($data1['memo'], $output['ret'][0]['trade_memo']);
        $this->assertEquals($data1['message'], $output['ret'][0]['message']);
        $this->assertEquals('', $output['ret'][0]['memo']);
        $this->assertEquals($data1['method'], $output['ret'][0]['method']);
        $this->assertEquals('3', $output['ret'][0]['id']);
        $this->assertRegExp($reg, $output['ret'][0]['confirm_at']);
        $this->assertRegExp($reg, $remitEntry->getConfirmAt()->format(\DateTime::ISO8601));
        $this->assertTrue($output['ret'][0]['confirm']);
        $this->assertFalse($output['ret'][0]['manual']);
        $this->assertEquals(9, $output['ret'][0]['remit_account_id']);
        $this->assertEquals(10, $output['ret'][0]['remit_entry_id']);
        $this->assertRegExp($reg, $output['ret'][0]['created_at']);
        $this->assertRegExp($reg, $output['ret'][0]['trade_at']);
    }

    /**
     * 測試新增匯款資料時，限額為零不停用
     */
    public function testCreateWithZeroBankLimit()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $remitAccount = $client->getContainer()->get('doctrine.orm.entity_manager')
            ->find('BBDurianBundle:RemitAccount', 9);

        $remitAccount->setBankLimit('0.0000');

        $data1 = [
            'amount' => '1000.00',
            'fee' => '0.00',
            'balance' => '1100.00',
            'account' => '1234554321',
            'name' => '張三',
            'time' => '',
            'memo' => '2017052400193239',
            'message' => '山東分行轉',
            'method' => '電子匯入',
        ];

        $data = [];
        array_push($data, $data1);
        $data = json_encode($data);

        $params = [
            'login_code' => 'cm',
            'data' => $data,
            'balance' => '35.00',
        ];

        $client->request('POST', 'api/remit_account/5432112345/auto_confirm_entry', $params);

        $remitAccount = $em->find('BBDurianBundle:RemitAccount', 9);

        $this->assertTrue($remitAccount->isEnabled());
    }

    /**
     * 測試新增匯款資料時，新增自動認款統計資料超過限額
     */
    public function testCreateRemitAccountStatOverBankLimit()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $remitAccount = $em->find('BBDurianBundle:RemitAccount', 9);
        $remitAccount->setBankLimit(1000);
        $em->persist($remitAccount);
        $em->flush();

        $data1 = [
            'amount' => '1000.00',
            'fee' => '0.00',
            'balance' => '1100.00',
            'account' => '1234554321',
            'name' => '張三',
            'time' => '',
            'memo' => '2017052400193239',
            'message' => '山東分行轉',
            'method' => '電子匯入',
        ];

        $data2 = [
            'amount' => '-1500.00',
            'fee' => '0.00',
            'balance' => '1535.00',
            'account' => '1234554321',
            'name' => '李四',
            'time' => '',
            'memo' => '',
            'message' => '廣東分行轉',
            'method' => '電子匯入',
        ];

        $data = [];
        array_push($data, $data1, $data2);
        $data = json_encode($data);

        $params = [
            'login_code' => 'cm',
            'data' => $data,
            'balance' => '35.00',
        ];

        $client->request('POST', 'api/remit_account/5432112345/auto_confirm_entry', $params);

        $ras = $em->find('BBDurianBundle:RemitAccountStat', 3);
        $rasArray = $ras->toArray();

        $this->assertEquals(2, $rasArray['count']);
        $this->assertEquals(1500, $rasArray['income']);
        $this->assertEquals(1600, $rasArray['payout']);

        $em->refresh($remitAccount);

        $this->assertTrue($remitAccount->isEnabled());
        $this->assertTrue($remitAccount->isSuspended());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remit_account', $logOp->getTableName());
        $this->assertEquals('@id:9', $logOp->getMajorKey());
        $this->assertEquals('@suspend:false=>true', $logOp->getMessage());
    }

    /**
     * 測試新增單一筆匯款資料
     */
    public function testCreateSingle()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();
        $redis = $this->getContainer()->get('snc_redis.default_client');

        // 先取出原本的額度
        $cashOrigin = $em->find('BBDurianBundle:Cash', 7);
        $balanceOrigin = $cashOrigin->getBalance();

        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 10);

        $sql = 'UPDATE remit_entry SET auto_remit_id = 3 WHERE id = 10';
        $em->getConnection()->executeUpdate($sql);

        $time = new \DateTime('now');
        $time = $time->sub(new \DateInterval('PT20M'));
        $time = $time->format(\DateTime::ISO8601);

        $params = [
            'login_code' => 'cm',
            'amount' => '1000.00',
            'fee' => '0.00',
            'balance' => '1100.00',
            'account' => '1234554321',
            'name' => '張三',
            'time' => $time,
            'memo' => '2017052400193239',
            'message' => '山東分行轉',
            'method' => '電子匯入',
            'ref_id' => 'ThisIsRefId',
        ];

        $client->request('POST', 'api/remit_account/5432112345/single_auto_confirm_entry', $params);

        // 跑背景程式讓queue被消化
        $cashPoper = new Poper();
        $cashPoper->runPop($this->getContainer(), 'cash');

        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cashOrigin);
        $em->refresh($remitEntry);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals('3', $output['ret']['id']);
        $this->assertTrue($output['ret']['confirm']);
        $this->assertFalse($output['ret']['manual']);
        $this->assertEquals('9', $output['ret']['remit_account_id']);
        $this->assertEquals('10', $output['ret']['remit_entry_id']);
        $this->assertEquals($params['amount'], $output['ret']['amount']);
        $this->assertEquals($params['fee'], $output['ret']['fee']);
        $this->assertEquals($params['balance'], $output['ret']['balance']);
        $this->assertEquals($params['method'], $output['ret']['method']);
        $this->assertEquals($params['account'], $output['ret']['account']);
        $this->assertEquals($params['name'], $output['ret']['name']);
        $this->assertEquals($params['memo'], $output['ret']['trade_memo']);
        $this->assertEquals($params['message'], $output['ret']['message']);

        $this->assertEquals(1, $remitEntry->getStatus());
        $this->assertEquals('秒付通', $remitEntry->getOperator());
        $this->assertEquals('2', $remitEntry->getAmountEntryId());

        // 金額是否有增加(confirm之後是照實際其他優惠欄位的值來設定其他優惠明細的優惠金額)
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
        $this->assertEquals('操作者： 秒付通', $cashEntry->getMemo());

        $pdwEntry = $em->getRepository('BBDurianBundle:PaymentDepositWithdrawEntry')
            ->findOneBy(['id' => $remitEntry->getAmountEntryId()]);
        $this->assertEquals($remitEntry->getRemitAccountId(), $pdwEntry->getRemitAccountId());

        // 檢查其他優惠現金明細
        $discountEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $remitEntry->getOtherDiscountEntryId()]);
        $this->assertEquals($remitEntry->getActualOtherDiscount(), $discountEntry->getAmount());
        $this->assertEquals($remitEntry->getOrderNumber(), $discountEntry->getRefId());
        $this->assertEquals('1038', $discountEntry->getOpcode());
        $this->assertEquals('操作者： 秒付通', $discountEntry->getMemo());

        // confirm之後匯款明細的其他優惠欄位不會被更改
        $this->assertEquals(1, $remitEntry->getOtherDiscount());

        // 檢查使用者出入款統計資料
        $userStat = $em->find('BBDurianBundle:UserStat', $remitEntry->getUserId());
        $this->assertEquals(1, $userStat->getRemitCount());
        $this->assertEquals(1000, $userStat->getRemitTotal());
        $this->assertEquals(1000, $userStat->getRemitMax());
        $this->assertEquals(1000, $userStat->getFirstDepositAmount());

        // 檢查LogOperation
        $logOp1 = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remit_entry', $logOp1->getTableName());
        $this->assertEquals('@id:10', $logOp1->getMajorKey());
        $this->assertEquals('@discount:60=>150', $logOp1->getMessage());

        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $msg = '@remit_count:0=>1, @remit_total:0=>1000, @remit_max:0=>1000.0000';
        $msg .= ', @first_deposit_at:' . $userStat->getFirstDepositAt()->format(\DateTime::ISO8601);
        $msg .= ', @first_deposit_amount:1000.0000, @modified_at:';
        $this->assertEquals('user_stat', $logOp2->getTableName());
        $this->assertEquals('@user_id:8', $logOp2->getMajorKey());
        $this->assertContains($msg, $logOp2->getMessage());

        $logOp3 = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertEquals('remit_entry', $logOp3->getTableName());
        $this->assertEquals('@id:10', $logOp3->getMajorKey());
        $message = [
            '@status:' . RemitEntry::UNCONFIRM . '=>' . RemitEntry::CONFIRM,
            '@operator:=>' . $remitEntry->getOperator(),
            '@duration:' . $remitEntry->getDuration(),
            '@amount_entry_id:' . $remitEntry->getAmountEntryId(),
            '@discount_entry_id:' . $remitEntry->getDiscountEntryId(),
            '@other_discount_entry_id:' . $remitEntry->getOtherDiscountEntryId(),
        ];
        $this->assertEquals(implode(', ', $message), $logOp3->getMessage());

        $logOp4 = $emShare->find('BBDurianBundle:LogOperation', 4);
        $this->assertEquals('auto_confirm_entry', $logOp4->getTableName());
        $this->assertEquals('@id:3', $logOp4->getMajorKey());
        $this->assertEquals('@remit_entry_id:10, @confirm:true', $logOp4->getMessage());

        // 檢查每日優惠資料
        $dailyDiscount = $em->find('BBDurianBundle:UserRemitDiscount', 1);
        $this->assertEquals(1, $dailyDiscount->getDiscount());

        // 檢查統計入款金額queue
        $statDeposit = json_decode($redis->rpop('stat_domain_deposit_queue'), true);
        $this->assertEquals(2, $statDeposit['domain']);
        $this->assertEquals('1000.0000', $statDeposit['amount']);

        // 檢查通知稽核 queue
        $auditParams = json_decode($redis->rpop('audit_queue'), true);
        $this->assertEquals(10, $auditParams['remit_entry_id']);
        $this->assertEquals(8, $auditParams['user_id']);
        $this->assertEquals(2151, $auditParams['balance']);
        $this->assertEquals(1000, $auditParams['amount']);
        $this->assertEquals(150, $auditParams['offer']);
        $this->assertEquals('0', $auditParams['fee']);
        $this->assertEquals('N', $auditParams['abandonsp']);
        $this->assertEquals($remitEntry->getConfirmAt()->format('Y-m-d H:i:s'), $auditParams['deposit_time']);
        $this->assertEquals(1, $auditParams['auto_confirm']);
    }

    /**
     * 測試新增單一筆匯款資料，支付寶成功匹配
     */
    public function testCreateSingleMatchByAliPay()
    {
        $client = $this->createClient();

        $time = new \DateTime('now');
        $time = $time->sub(new \DateInterval('PT20M'));
        $time = $time->format(\DateTime::ISO8601);

        $params = [
            'login_code' => 'cm',
            'amount' => '1500.00',
            'fee' => '0.00',
            'balance' => '1535.00',
            'account' => '',
            'name' => '支付宝（中国）网络技术有限公司客户备付金',
            'time' => $time,
            'memo' => '李四支付宝转账',
            'message' => '',
            'method' => '转账',
            'ref_id' => 'ThisIsRefId',
        ];

        $client->request('POST', 'api/remit_account/5432112345/single_auto_confirm_entry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals('3', $output['ret']['id']);
        $this->assertTrue($output['ret']['confirm']);
        $this->assertFalse($output['ret']['manual']);
        $this->assertEquals('9', $output['ret']['remit_account_id']);
        $this->assertEquals('11', $output['ret']['remit_entry_id']);
        $this->assertEquals($params['amount'], $output['ret']['amount']);
        $this->assertEquals($params['fee'], $output['ret']['fee']);
        $this->assertEquals($params['balance'], $output['ret']['balance']);
        $this->assertEquals($params['method'], $output['ret']['method']);
        $this->assertEquals($params['account'], $output['ret']['account']);
        $this->assertEquals($params['name'], $output['ret']['name']);
        $this->assertEquals($params['memo'], $output['ret']['trade_memo']);
        $this->assertEquals($params['message'], $output['ret']['message']);
    }

    /**
     * 測試新增單一筆匯款資料，支付寶成功匹配，姓名為支付宝转账
     */
    public function testCreateSingleMatchByAliPayWithSameNameReal()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $remitEntry = $em->find('BBDurianBundle:RemitEntry', 11);
        $remitEntry->setMethod(8);
        $remitEntry->setNameReal('支付宝转账');
        $em->flush();

        $client = $this->createClient();

        $time = new \DateTime('now');
        $time = $time->sub(new \DateInterval('PT20M'));
        $time = $time->format(\DateTime::ISO8601);

        $params = [
            'login_code' => 'cm',
            'amount' => '1500.00',
            'fee' => '0.00',
            'balance' => '1535.00',
            'account' => '',
            'name' => '支付宝（中国）网络技术有限公司客户备付金',
            'time' => $time,
            'memo' => '支付宝转账支付宝转账',
            'message' => '',
            'method' => '转账',
            'ref_id' => 'ThisIsRefId',
        ];

        $client->request('POST', 'api/remit_account/5432112345/single_auto_confirm_entry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals('3', $output['ret']['id']);
        $this->assertTrue($output['ret']['confirm']);
        $this->assertFalse($output['ret']['manual']);
        $this->assertEquals('9', $output['ret']['remit_account_id']);
        $this->assertEquals('11', $output['ret']['remit_entry_id']);
        $this->assertEquals($params['amount'], $output['ret']['amount']);
        $this->assertEquals($params['fee'], $output['ret']['fee']);
        $this->assertEquals($params['balance'], $output['ret']['balance']);
        $this->assertEquals($params['method'], $output['ret']['method']);
        $this->assertEquals($params['account'], $output['ret']['account']);
        $this->assertEquals($params['name'], $output['ret']['name']);
        $this->assertEquals($params['memo'], $output['ret']['trade_memo']);
        $this->assertEquals($params['message'], $output['ret']['message']);
    }

    /**
     * 測試新增單一筆匯款資料，未匹配
     */
    public function testCreateSingleNotMatch()
    {
        $client = $this->createClient();

        $time = new \DateTime('now');
        $time = $time->sub(new \DateInterval('PT20M'));
        $time = $time->format(\DateTime::ISO8601);

        $params = [
            'login_code' => 'cm',
            'amount' => '991.00',
            'fee' => '0.00',
            'balance' => '1100.00',
            'account' => '1234554321',
            'name' => '張三',
            'time' => $time,
            'memo' => '2017052400193239',
            'message' => '山東分行轉',
            'method' => '電子匯入',
            'ref_id' => 'ThisIsRefId',
        ];

        $client->request('POST', 'api/remit_account/5432112345/single_auto_confirm_entry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals('3', $output['ret']['id']);
        $this->assertFalse($output['ret']['confirm']);
        $this->assertFalse($output['ret']['manual']);
        $this->assertEquals('9', $output['ret']['remit_account_id']);
        $this->assertNull($output['ret']['remit_entry_id']);
        $this->assertEquals($params['amount'], $output['ret']['amount']);
        $this->assertEquals($params['fee'], $output['ret']['fee']);
        $this->assertEquals($params['balance'], $output['ret']['balance']);
        $this->assertEquals($params['method'], $output['ret']['method']);
        $this->assertEquals($params['account'], $output['ret']['account']);
        $this->assertEquals($params['name'], $output['ret']['name']);
        $this->assertEquals($params['memo'], $output['ret']['trade_memo']);
        $this->assertEquals($params['message'], $output['ret']['message']);
    }

    /**
     * 測試新增單一筆匯款資料，支付寶匹配，明細姓名不正確
     */
    public function testCreateSingleNotMatchByAliPayButWrongName()
    {
        $client = $this->createClient();

        $time = new \DateTime('now');
        $time = $time->sub(new \DateInterval('PT20M'));
        $time = $time->format(\DateTime::ISO8601);

        $params = [
            'login_code' => 'cm',
            'amount' => '1500.00',
            'fee' => '0.00',
            'balance' => '1535.00',
            'account' => '',
            'name' => '支付宝宝不是支付宝',
            'time' => $time,
            'memo' => '李四支付宝转账',
            'message' => '',
            'method' => '转账',
            'ref_id' => 'ThisIsRefId',
        ];

        $client->request('POST', 'api/remit_account/5432112345/single_auto_confirm_entry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals('3', $output['ret']['id']);
        $this->assertFalse($output['ret']['confirm']);
        $this->assertFalse($output['ret']['manual']);
        $this->assertEquals('9', $output['ret']['remit_account_id']);
        $this->assertNull($output['ret']['remit_entry_id']);
        $this->assertEquals($params['amount'], $output['ret']['amount']);
        $this->assertEquals($params['fee'], $output['ret']['fee']);
        $this->assertEquals($params['balance'], $output['ret']['balance']);
        $this->assertEquals($params['method'], $output['ret']['method']);
        $this->assertEquals($params['account'], $output['ret']['account']);
        $this->assertEquals($params['name'], $output['ret']['name']);
        $this->assertEquals($params['memo'], $output['ret']['trade_memo']);
        $this->assertEquals($params['message'], $output['ret']['message']);
    }

    /**
     * 測試新增單一筆匯款資料，交易姓名為空字串
     */
    public function testCreateSingleWithEmptyName()
    {
        $client = $this->createClient();

        $time = new \DateTime('now');
        $time = $time->sub(new \DateInterval('PT20M'));
        $time = $time->format(\DateTime::ISO8601);

        $params = [
            'login_code' => 'cm',
            'amount' => '1000.00',
            'fee' => '0.00',
            'balance' => '1100.00',
            'account' => '999999999',
            'name' => '',
            'time' => $time,
            'memo' => '',
            'message' => '山東分行轉',
            'method' => '電子匯入',
            'ref_id' => 'ThisIsRefId',
        ];

        $client->request('POST', 'api/remit_account/5432112345/single_auto_confirm_entry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals('3', $output['ret']['id']);
        $this->assertFalse($output['ret']['confirm']);
        $this->assertFalse($output['ret']['manual']);
        $this->assertEquals('9', $output['ret']['remit_account_id']);
        $this->assertNull($output['ret']['remit_entry_id']);
        $this->assertEquals($params['amount'], $output['ret']['amount']);
        $this->assertEquals($params['fee'], $output['ret']['fee']);
        $this->assertEquals($params['balance'], $output['ret']['balance']);
        $this->assertEquals($params['method'], $output['ret']['method']);
        $this->assertEquals($params['account'], $output['ret']['account']);
        $this->assertEquals($params['name'], $output['ret']['name']);
        $this->assertEquals($params['memo'], $output['ret']['trade_memo']);
        $this->assertEquals($params['message'], $output['ret']['message']);
    }

    /**
     * 測試新增單一筆匯款資料，交易帳號為空字串
     */
    public function testCreateSingleWithEmptyAccount()
    {
        $client = $this->createClient();

        $time = new \DateTime('now');
        $time = $time->sub(new \DateInterval('PT20M'));
        $time = $time->format(\DateTime::ISO8601);

        $params = [
            'login_code' => 'cm',
            'amount' => '1000.00',
            'fee' => '0.00',
            'balance' => '1100.00',
            'account' => '',
            'name' => '張三一',
            'time' => $time,
            'memo' => '',
            'message' => '山東分行轉',
            'method' => '電子匯入',
            'ref_id' => 'ThisIsRefId',
        ];

        $client->request('POST', 'api/remit_account/5432112345/single_auto_confirm_entry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals('3', $output['ret']['id']);
        $this->assertFalse($output['ret']['confirm']);
        $this->assertFalse($output['ret']['manual']);
        $this->assertEquals('9', $output['ret']['remit_account_id']);
        $this->assertNull($output['ret']['remit_entry_id']);
        $this->assertEquals($params['amount'], $output['ret']['amount']);
        $this->assertEquals($params['fee'], $output['ret']['fee']);
        $this->assertEquals($params['balance'], $output['ret']['balance']);
        $this->assertEquals($params['method'], $output['ret']['method']);
        $this->assertEquals($params['account'], $output['ret']['account']);
        $this->assertEquals($params['name'], $output['ret']['name']);
        $this->assertEquals($params['memo'], $output['ret']['trade_memo']);
        $this->assertEquals($params['message'], $output['ret']['message']);
    }

    /**
     * 測試新增單一筆匯款資料，新增自動認款統計資料超過限額
     */
    public function testCreateSingleRemitAccountStatOverBankLimit()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $remitAccount = $em->find('BBDurianBundle:RemitAccount', 9);
        $remitAccount->setBankLimit(900);
        $em->persist($remitAccount);
        $em->flush();

        $time = new \DateTime('now');
        $time = $time->sub(new \DateInterval('PT20M'));
        $time = $time->format(\DateTime::ISO8601);

        $params = [
            'login_code' => 'cm',
            'amount' => '1000.00',
            'fee' => '0.00',
            'balance' => '1100.00',
            'account' => '1234554321',
            'name' => '張三',
            'time' => $time,
            'memo' => '2017052400193239',
            'message' => '山東分行轉',
            'method' => '電子匯入',
            'ref_id' => 'ThisIsRefId',
        ];

        $client->request('POST', 'api/remit_account/5432112345/single_auto_confirm_entry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $em->refresh($remitAccount);

        $this->assertEquals('ok', $output['result']);

        $this->assertTrue($remitAccount->isEnabled());
        $this->assertTrue($remitAccount->isSuspended());

        $ras = $em->find('BBDurianBundle:RemitAccountStat', 3);
        $rasArray = $ras->toArray();

        $this->assertEquals(2, $rasArray['count']);
        $this->assertEquals(1500, $rasArray['income']);

        // 檢查LogOperation
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 5);
        $this->assertEquals('remit_account', $logOp->getTableName());
        $this->assertEquals('@id:9', $logOp->getMajorKey());
        $this->assertEquals('@suspend:false=>true', $logOp->getMessage());
    }

    /**
     * 測試人工匯配訂單
     */
    public function testManualmatchAutoConfirmEntry()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $sql = 'UPDATE remit_entry SET remit_account_id = 8 WHERE id = 10';
        $em->getConnection()->executeUpdate($sql);

        $param = [
            'operator' => 'handsdo',
            'order_number' => '2017052400193239',
        ];

        $client->request('PUT', 'api/auto_confirm/2/manual', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
    }

    /**
     * 測試匯款記錄列表
     */
    public function testListEntry()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $ace = $em->find('BBDurianBundle:AutoConfirmEntry', 1);

        $params = [
            'first_result' => '0',
            'max_results' => '3',
            'sort' => 'id',
            'order' => 'asc',
            'confirm' => 0,
            'manual' => 0,
            'remit_account_id' => 8,
            'remit_entry_id' => 0,
            'account' => '1234554321',
            'name' => '姓名一',
            'trade_memo' => '2017052400193240',
            'message' => '山西分行轉',
            'memo' => '備註一',
            'method' => '電子匯入',
            'created_start' => (new \DateTime('today'))->format(\DateTime::ISO8601),
            'created_end' => (new \DateTime('tomorrow'))->format(\DateTime::ISO8601),
            'confirm_start' => '',
            'confirm_end' => '',
            'trade_start' => '2017-01-01T00:00:00+0800',
            'trade_end' => '2017-01-01T06:00:00+0800',
            'amount_min' => '-3',
            'amount_max' => '3',
            'fee_min' => '0',
            'fee_max' => '3',
            'balance_min' => '0',
            'balance_max' => '10',
            'sub_total' => 1,
            'total' => 1,
        ];

        $client = $this->createClient();
        $client->request('GET', '/api/auto_confirm/entry/list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals($ace->getCreatedAt()->format(\DateTime::ISO8601), $output['ret'][0]['created_at']);
        $this->assertNull($output['ret'][0]['confirm_at']);
        $this->assertFalse($output['ret'][0]['confirm']);
        $this->assertFalse($output['ret'][0]['manual']);
        $this->assertEquals(8, $output['ret'][0]['remit_account_id']);
        $this->assertEquals(0, $output['ret'][0]['remit_entry_id']);
        $this->assertEquals('2017-01-01T01:00:00+0800', $output['ret'][0]['trade_at']);
        $this->assertEquals('-1', $output['ret'][0]['amount']);
        $this->assertEquals('0', $output['ret'][0]['fee']);
        $this->assertEquals('10', $output['ret'][0]['balance']);
        $this->assertEquals('1234554321', $output['ret'][0]['account']);
        $this->assertEquals('姓名一', $output['ret'][0]['name']);
        $this->assertEquals('2017052400193240', $output['ret'][0]['trade_memo']);
        $this->assertEquals('山西分行轉', $output['ret'][0]['message']);
        $this->assertEquals('備註一', $output['ret'][0]['memo']);

        $this->assertEquals(-1, $output['sub_total']['amount']);
        $this->assertEquals('-1', $output['total']['amount']);

        $this->assertEquals('0', $output['pagination']['first_result']);
        $this->assertEquals('3', $output['pagination']['max_results']);
        $this->assertEquals('1', $output['pagination']['total']);
    }

    /**
     * 測試匯款記錄列表不帶排序欄位
     */
    public function testListEntryWithoutSortAndOrder()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $remitAccount = $em->find('BBDurianBundle:RemitAccount', 8);

        $data3 = [
            'method' => '',
            'name' => '張三三',
            'account' => '55688',
            'amount' => '-75',
            'balance' => '925',
            'fee' => '0',
            'memo' => '',
            'message' => '手續費',
            'time' => '2017-01-02 02:01:00',
        ];
        $ace3 = new AutoConfirmEntry($remitAccount, $data3);
        $em->persist($ace3);

        $data4 = [
            'method' => '',
            'name' => '張三三',
            'account' => '55688',
            'amount' => '-5000',
            'balance' => '6000',
            'fee' => '0',
            'memo' => '',
            'message' => '轉出',
            'time' => '2017-01-02 02:01:00',
        ];
        $ace4 = new AutoConfirmEntry($remitAccount, $data4);
        $em->persist($ace4);

        $data5 = [
            'method' => '',
            'name' => '張三一',
            'account' => '55688',
            'amount' => '1000',
            'balance' => '1000',
            'fee' => '0',
            'memo' => '',
            'message' => '轉入',
            'time' => '2017-01-02 02:00:00',
        ];
        $ace5 = new AutoConfirmEntry($remitAccount, $data5);
        $em->persist($ace5);

        $em->flush();

        $params = [
            'confirm' => 0,
            'remit_account_id' => 8,
            'trade_start' => '2017-01-02T00:00:00+0800',
            'trade_end' => '2017-01-02T06:00:00+0800',
        ];

        $client = $this->createClient();
        $client->request('GET', '/api/auto_confirm/entry/list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(5, $output['ret'][0]['id']);
        $this->assertEquals(4, $output['ret'][1]['id']);
        $this->assertEquals(3, $output['ret'][2]['id']);
    }

    /**
     * 測試修改匯款明細
     */
    public function testSetAutoConfirmEntry()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $param = ['memo' => 'thisisnewmemo'];
        $client->request('PUT', 'api/auto_confirm/entry/1', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertEquals('thisisnewmemo', $output['ret']['memo']);

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('auto_confirm_entry', $logOperation->getTableName());
        $this->assertEquals('@id:1', $logOperation->getMajorKey());
        $this->assertEquals('@memo:備註一=>thisisnewmemo', $logOperation->getMessage());
    }

    /**
     * 測試修改匯款明細帶入空字串
     */
    public function testSetAutoConfirmEntryWithEmptyString()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $param = ['memo' => ''];
        $client->request('PUT', 'api/auto_confirm/entry/1', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertEquals('', $output['ret']['memo']);

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('auto_confirm_entry', $logOperation->getTableName());
        $this->assertEquals('@id:1', $logOperation->getMajorKey());
        $this->assertEquals('@memo:備註一=>', $logOperation->getMessage());
    }

    /**
     * 測試鎖定網銀密碼錯誤
     */
    public function testLockPasswordError()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $param = ['login_code' => 'cm'];
        $client->request('PUT', 'api/remit_account/5432112345/lock/password_error', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);

        $this->assertEquals('remit_account', $logOperation->getTableName());
        $this->assertEquals('@id:9', $logOperation->getMajorKey());
        $this->assertEquals('@password_error:false=>true, @enable:true=>false', $logOperation->getMessage());
    }
}
