<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\CashWithdrawEntry;
use BB\DurianBundle\Consumer\Poper;
use Buzz\Message\Response;

class WithdrawFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankCurrencyData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashWithdrawEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserDetailData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantWithdrawData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantWithdrawLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantWithdrawLevelBankInfoData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelCurrencyData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadWithdrawErrorData',
        ];

        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashEntryData'
        ];

        $this->loadFixtures($classnames, 'entry');

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadExchangeData'
        ];

        $this->loadFixtures($classnames, 'share');

        $redis = $this->getContainer()->get('snc_redis.sequence');

        $redis->set('cash_withdraw_seq', 10);
        $redis->set('cash_seq', 1000);

        $this->clearSensitiveLog();
        $sensitiveData = 'entrance=6&operator=test&client_ip=127.0.0.1';
        $sensitiveData .= '&run_php=WithdrawFunctionsTest.php&operator_id=&vendor=acc';

        $this->sensitiveData = $sensitiveData;
        $this->headerParam = ['HTTP_SENSITIVE_DATA' => $sensitiveData];
    }

    /**
     * 測試現金出款
     */
    public function testWithdraw()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $client = $this->createClient();

        //暫時改為直營網domain以測試AccountLog isDetailModified及previousId
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setDomain(6);
        $em->flush();

        $parameters = [
            'bank_id'   => 4,
            'amount'    => -50,
            'fee'       => -1,
            'deduction' => -1,
            'payment_gateway_fee' => -1,
            'memo'      => 'test',
            'ip'        => '127.0.0.1'
        ];

        $client->request('POST', '/api/user/8/cash/withdraw', $parameters, [], $this->headerParam);

        //跑背景程式讓queue被消化
        $cashPoper = new Poper();
        $cashPoper->runPop($this->getContainer(), 'cash');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $em->refresh($user);
        $entryId = $ret['ret']['withdraw_entry']['id'];
        $entry = $em->getRepository('BBDurianBundle:CashWithdrawEntry')->findOneBy(['id' => $entryId]);

        $this->assertEquals($entry->getCashId(), $ret['ret']['withdraw_entry']['cash_id']);
        $this->assertEquals($entry->getUserId(), $ret['ret']['withdraw_entry']['user_id']);
        $this->assertEquals('TWD', $ret['ret']['withdraw_entry']['currency']);
        $this->assertEquals($entry->getDomain(), $ret['ret']['withdraw_entry']['domain']);
        $this->assertEquals($entry->getAmount(), $ret['ret']['withdraw_entry']['amount']);
        $this->assertEquals($entry->getFee(), $ret['ret']['withdraw_entry']['fee']);
        $this->assertEquals($entry->getDeduction(), $ret['ret']['withdraw_entry']['deduction']);
        $this->assertEquals(-1, $ret['ret']['withdraw_entry']['fee']);
        $this->assertEquals($entry->getRealAmount(), $ret['ret']['withdraw_entry']['real_amount']);
        $this->assertEquals($entry->isFirst(), $ret['ret']['withdraw_entry']['first']);
        $this->assertEquals($entry->getIp(), $ret['ret']['withdraw_entry']['ip']);
        $this->assertEquals($entry->getMemo(), $ret['ret']['withdraw_entry']['memo']);
        $this->assertEquals($entry->getStatus(), $ret['ret']['withdraw_entry']['status']);
        $this->assertEquals($entry->getConfirmAt(), $ret['ret']['withdraw_entry']['confirm_at']);
        $this->assertEquals($entry->getLevelId(), $ret['ret']['withdraw_entry']['level_id']);
        $this->assertEquals($entry->getNameReal(), $ret['ret']['withdraw_entry']['name_real']);
        $this->assertEquals($entry->getTelephone(), $ret['ret']['withdraw_entry']['telephone']);
        $this->assertEquals($entry->getBankName(), $ret['ret']['withdraw_entry']['bank_name']);
        $this->assertEquals($entry->getAccount(), $ret['ret']['withdraw_entry']['account']);
        $this->assertEquals(8, $ret['ret']['withdraw_entry']['previous_id']);
        $this->assertEquals(4, $user->getLastBank());
        $this->assertFalse($ret['ret']['withdraw_entry']['detail_modified']);

        $previousId = $ret['ret']['withdraw_entry']['id'];

        $centry = $emEntry->getRepository('BBDurianBundle:CashEntry')
                ->findOneBy(['id' => $ret['ret']['entry']['id']]);

        $this->assertEquals($centry->getOpcode(), $ret['ret']['entry']['opcode']);
        $this->assertEquals($centry->getMemo(), $ret['ret']['entry']['memo']);
        $this->assertEquals($centry->getAmount(), $ret['ret']['entry']['amount']);
        $this->assertEquals($entry->getId(), $ret['ret']['entry']['ref_id']);

        $param = $em->find('BBDurianBundle:AccountLog', 1);

        $this->assertEquals(8, $param->getPreviousId());
        $this->assertFalse($param->isDetailModified());
        $this->assertEquals(6, $param->getDomain());

        /*
         * 測試再次出款時，首次出款欄位(first_withdraw)就不為true
         * 並測試修改使用者的真實姓名與原本不同且為直營網時是否此筆出款明細
         * 及AccountLog isDetailModified是否為true
         */
        $detail = $em->find('BBDurianBundle:UserDetail', 8);
        $detail->setNameReal('李奧納多');
        $em->flush();

        $parameters = [
            'bank_id'   => 4,
            'amount'    => -500,
            'fee'       => -1,
            'deduction' => -1,
            'memo'      => 'test',
            'ip'        => '127.0.0.1'
        ];

        $client->request('POST', '/api/user/8/cash/withdraw', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertFalse($ret['ret']['withdraw_entry']['first']);
        $this->assertTrue($ret['ret']['withdraw_entry']['detail_modified']);
        $this->assertEquals($previousId, $ret['ret']['withdraw_entry']['previous_id']);
        $this->assertEquals('李奧納多', $ret['ret']['withdraw_entry']['name_real']);

        $param = $em->find('BBDurianBundle:AccountLog', 2);

        $this->assertEquals($previousId, $param->getPreviousId());
        $this->assertTrue($param->isDetailModified());

        // 敏感資訊log
        $logPath = $this->getLogfilePath('sensitive.log');
        $this->assertFileExists($logPath);
        $results = explode(PHP_EOL, file_get_contents($logPath));
        $line = "sensitive-data:$this->sensitiveData";
        $this->assertTrue(strpos($results[0], $line) !== false);
    }

    /**
     * 測試出款時設定為自動出款
     */
    public function testAutoWithdraw()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $client = $this->createClient();

        $memo = '';
        for ($i = 0; $i < 100; $i++) {
            $memo .= 'a';
        }

        $parameters = [
            'bank_id'   => 5,
            'amount'    => -50,
            'fee'       => -1,
            'deduction' => -1,
            'payment_gateway_fee' => -1,
            'memo'      => $memo . '012',
            'ip'        => '127.0.0.1'
        ];

        $client->request('POST', '/api/user/8/cash/withdraw', $parameters, [], $this->headerParam);

        //跑背景程式讓queue被消化
        $cashPoper = new Poper();
        $cashPoper->runPop($this->getContainer(), 'cash');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals($memo, $ret['ret']['entry']['memo']);

        $entryId = $ret['ret']['withdraw_entry']['id'];
        $entry = $em->getRepository('BBDurianBundle:CashWithdrawEntry')->findOneBy(['id' => $entryId]);

        $this->assertEquals($entry->getCashId(), $ret['ret']['withdraw_entry']['cash_id']);
        $this->assertEquals($entry->getUserId(), $ret['ret']['withdraw_entry']['user_id']);
        $this->assertEquals('TWD', $ret['ret']['withdraw_entry']['currency']);
        $this->assertEquals($entry->getDomain(), $ret['ret']['withdraw_entry']['domain']);
        $this->assertEquals($entry->getAmount(), $ret['ret']['withdraw_entry']['amount']);
        $this->assertEquals($entry->getFee(), $ret['ret']['withdraw_entry']['fee']);
        $this->assertEquals($entry->getDeduction(), $ret['ret']['withdraw_entry']['deduction']);
        $this->assertEquals(-1, $ret['ret']['withdraw_entry']['fee']);
        $this->assertEquals($entry->getRealAmount(), $ret['ret']['withdraw_entry']['real_amount']);
        $this->assertEquals($entry->isFirst(), $ret['ret']['withdraw_entry']['first']);
        $this->assertEquals($entry->getIp(), $ret['ret']['withdraw_entry']['ip']);
        $this->assertEquals($entry->getMemo(), $ret['ret']['withdraw_entry']['memo']);
        $this->assertEquals($entry->getStatus(), $ret['ret']['withdraw_entry']['status']);
        $this->assertEquals($entry->getConfirmAt(), $ret['ret']['withdraw_entry']['confirm_at']);
        $this->assertEquals($entry->getLevelId(), $ret['ret']['withdraw_entry']['level_id']);
        $this->assertEquals($entry->getNameReal(), $ret['ret']['withdraw_entry']['name_real']);
        $this->assertEquals($entry->getTelephone(), $ret['ret']['withdraw_entry']['telephone']);
        $this->assertEquals($entry->getBankName(), $ret['ret']['withdraw_entry']['bank_name']);
        $this->assertEquals($entry->getAccount(), $ret['ret']['withdraw_entry']['account']);
        $this->assertFalse($ret['ret']['withdraw_entry']['detail_modified']);
        $this->assertTrue($ret['ret']['withdraw_entry']['auto_withdraw']);
        $this->assertNull($ret['ret']['withdraw_entry']['merchant_withdraw_id']);

        $centry = $emEntry->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $ret['ret']['entry']['id']]);

        $this->assertEquals($centry->getOpcode(), $ret['ret']['entry']['opcode']);
        $this->assertEquals($centry->getMemo(), $ret['ret']['entry']['memo']);
        $this->assertEquals($centry->getAmount(), $ret['ret']['entry']['amount']);
        $this->assertEquals($centry->getRefId(), $ret['ret']['entry']['ref_id']);
    }

    /**
     * 測試現金出款,出現flush錯誤,執行RollBack CashTrans
     */
    public function testWithdrawButRollBack()
    {
        $em    = $this->getContainer()->get('doctrine.orm.entity_manager');
        $user8 = $em->find('BBDurianBundle:User', 8);
        $userDetail = $em->find('BBDurianBundle:UserDetail', 8);
        $bank4 = $em->find('BBDurianBundle:Bank', 4);
        $bankCurrency = $em->find('BBDurianBundle:BankCurrency', 1);
        $bankInfo = $em->find('BBDurianBundle:BankInfo', 1);
        $userLevel = $em->find('BBDurianBundle:UserLevel', 8);

        $idGenerator = $this->getContainer()->get('durian.cash_entry_id_generator');
        $cashEntryId = $idGenerator->generate();

        // mock entity manager
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods([
                'beginTransaction',
                'find',
                'persist',
                'flush',
                'rollback',
                'getRepository',
                'clear'
            ])
            ->getMock();

        $mockEm->expects($this->at(1))
            ->method('find')
            ->will($this->returnValue($user8));

        $mockEm->expects($this->at(2))
            ->method('find')
            ->will($this->returnValue($bank4));

        $mockEm->expects($this->at(3))
            ->method('find')
            ->will($this->returnValue($bankCurrency));

        $mockEm->expects($this->at(4))
            ->method('find')
            ->will($this->returnValue($bankInfo));

        $mockEm->expects($this->at(5))
            ->method('find')
            ->willReturn($userLevel);

        $mockEm->expects($this->at(6))
            ->method('find')
            ->willReturn(null);

        $entityRepo= $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods([
                'findOneByUser',
                'totalWithdrawEntry',
                'getPreviousWithdrawEntry'
            ])
            ->getMock();

        $entityRepo->expects($this->any())
            ->method('findOneByUser')
            ->will($this->returnValue($userDetail));

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($entityRepo));

        $mockEm->expects($this->at(12))
            ->method('flush')
            ->will($this->throwException(new \Exception('SQLSTATE[28000] [1045]')));

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);

        $parameters = [
            'bank_id'   => 4,
            'amount'    => -500,
            'fee'       => -1,
            'deduction' => -1,
            'memo'      => 'test',
            'ip'        => '127.0.0.1'
        ];
        $client->request('POST', '/api/user/8/cash/withdraw', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $redisWallet = $this->getContainer()->get('snc_redis.wallet4');

        // 檢查pre_sub資料是否有rollback
        $this->assertEquals(0, $redisWallet->hget('cash_balance_8_901', 'pre_sub'));

        // 檢查餘額資料是否有rollback
        $this->assertEquals(10000000, $redisWallet->hget('cash_balance_8_901', 'balance'));

        // 檢查cash_sync_queue內容
        $syncMsg = $redis->lpop('cash_sync_queue');
        $msg = [
            'HEAD' => 'CASHSYNCHRONIZE',
            'KEY' => 'cash_balance_8_901',
            'ERRCOUNT' => 0,
            'id' => '7',
            'user_id' => '8',
            'balance' => 1000,
            'pre_sub' => 0,
            'pre_add' => 0,
            'version' => 4,
            'currency' => '901'
        ];
        $this->assertEquals(json_encode($msg), $syncMsg);

        // 檢查cash_queue是否有資料
        $queueMsg = $redis->lpop('cash_queue');
        $this->assertInternalType('string', $queueMsg);

        // 檢查key是否有刪除
        $tRedisWallet = $this->getContainer()->get('snc_redis.wallet1');
        $this->assertNull($tRedisWallet->get("en_cashtrans_id_$cashEntryId"));

        // 檢查輸出結果
        $this->assertEquals('error', $ret['result']);
        $this->assertEquals('SQLSTATE[28000] [1045]', $ret['msg']);
    }

    /**
     * 測試出款但操作者與帶入的為不同廳
     */
    public function testWithdrawWithOperationIsNotInSameDomain()
    {
        $client = $this->createClient();

        $sensitiveData = 'entrance=2&operator=test&client_ip=127.0.0.1';
        $sensitiveData .= '&run_php=WithdrawFunctionsTest.php&operator_id=9&vendor=acc';

        $headers = ['HTTP_SENSITIVE_DATA' => $sensitiveData];
        $client->request('POST', '/api/user/8/cash/withdraw', [], [], $headers);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150240007', $output['code']);
        $this->assertEquals('The request not allowed when operator is not in same domain', $output['msg']);
    }

    /**
     * 測試現金出款memo非UTF8
     */
    public function testWithdrawMemoNotUtf8()
    {
        $client = $this->createClient();

        $parameters = [
            'bank_id' => 4,
            'amount'  => -50,
            'memo'    => mb_convert_encoding('龜龍鱉', 'GB2312', 'UTF-8'),
            'ip'      => '127.0.0.1'
        ];

        $client->request('POST', '/api/user/8/cash/withdraw', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150610002, $output['code']);
        $this->assertEquals('String must use utf-8 encoding', $output['msg']);
    }

    /**
     * 測試現金出款, 但手續費超過小數4位
     */
    public function testWithdrawWithInvalidFee()
    {
        $client = $this->createClient();

        $parameters = [
            'bank_id'   => 4,
            'amount'    => -50,
            'fee'       => -1.01111,
            'deduction' => -1,
            'memo'      => 'test',
            'ip'        => '127.0.0.1'
        ];

        $client->request('POST', '/api/user/8/cash/withdraw', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150610003, $ret['code']);
        $this->assertEquals('The decimal digit of amount exceeds limitation', $ret['msg']);
    }

    /**
     * 測試出款,找不到user的cash資料
     */
    public function testWithdrawWithNoCashUser()
    {
        $client = $this->createClient();

        $client->request('POST', '/api/user/10/cash/withdraw', [], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(380023, $output['code']);
        $this->assertEquals('No cash found', $output['msg']);
    }

    /**
     * 測試出款,輸入錯誤的bank
     */
    public function testWithdrawWithErrorBank()
    {
        $client = $this->createClient();

        $parameters = ['bank_id' => 9999];

        $client->request('POST', '/api/user/8/cash/withdraw', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(380022, $output['code']);
        $this->assertEquals('No Bank found', $output['msg']);
    }

    /**
     * 測試出款,輸入User不相同
     */
    public function testWithdrawUserNotMatch()
    {
        $client = $this->createClient();

        $parameters = ['bank_id' => 3];

        $client->request('POST', '/api/user/2/cash/withdraw', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(380005, $output['code']);
        $this->assertEquals('User not match', $output['msg']);
    }

    /**
     * 測試現金出款找不到BankCurrency
     */
    public function testWithdrawWithoutBankCurrency()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $bankCurrency = $em->find('BBDurianBundle:BankCurrency', 2);
        $em->remove($bankCurrency);
        $em->flush();

        $parameters = ['bank_id' => 3];

        $client->request('POST', '/api/user/8/cash/withdraw', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(380021, $output['code']);
        $this->assertEquals('No BankCurrency found', $output['msg']);
    }

    /**
     * 測試出款,但會員層級不存在
     */
    public function testWithdrawWithoutUserLevel()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 將userLevel資料刪除
        $userLevel = $em->find('BBDurianBundle:UserLevel', 8);
        $em->remove($userLevel);
        $em->flush();

        $parameters = ['bank_id' => 4];

        $client->request('POST', '/api/user/8/cash/withdraw', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(380019, $output['code']);
        $this->assertEquals('No UserLevel found', $output['msg']);
    }

    /**
     * 測試現金出款找不到UserDetail
     */
    public function testWithdrawWithoutUserDetail()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $userDetail = $em->find('BBDurianBundle:UserDetail', 8);
        $em->remove($userDetail);
        $em->flush();

        $parameters = ['bank_id' => 4];

        $client->request('POST', '/api/user/8/cash/withdraw', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(380024, $output['code']);
        $this->assertEquals('No detail data found', $output['msg']);
    }

    /**
     * 測試現金出款真實姓名為空
     */
    public function testWithdrawWithNameRealNull()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $userDetail = $em->find('BBDurianBundle:UserDetail', 8);
        $userDetail->setNameReal('');
        $em->flush();

        $parameters = ['bank_id' => 4];

        $client->request('POST', '/api/user/8/cash/withdraw', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(380020, $output['code']);
        $this->assertEquals('Name real is null', $output['msg']);
    }

    /**
     * 測試現金出款UserDetail 電話為空字串
     */
    public function testWithdrawUserDetailTelephoneWithEmptyString()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        //把userDetail電話改為空值，是否能正常出款
        $userDetail = $em->getRepository('BBDurianBundle:UserDetail')
                         ->findOneBy(['user' => 8]);
        $userDetail->setTelephone('');

        $em->flush();

        $parameters = [
            'bank_id'   => 4,
            'amount'    => -50,
            'fee'       => -1,
            'deduction' => -1,
            'memo'      => 'test',
            'ip'        => '127.0.0.1'
        ];

        $client->request('POST', '/api/user/8/cash/withdraw', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
    }

    /**
     * 測試現金出款為首次出款
     */
    public function testWithdrawWithFirstWithdraw()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        //暫時改為直營網domain以測試AccountLog isDetailModified及previousId
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setDomain(6);
        $em->flush();

        //清除該使用者的出款紀錄
        $qb = $em->createQueryBuilder();
        $qb->delete('BBDurianBundle:CashWithdrawEntry', 'cwe');
        $qb->where('cwe.userId = 8');
        $qb->getQuery()->execute();

        $parameters = [
            'bank_id'   => 4,
            'amount'    => -50,
            'fee'       => -1,
            'deduction' => -1,
            'memo'      => 'test',
            'ip'        => '127.0.0.1'
        ];

        $client->request('POST', '/api/user/8/cash/withdraw', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $entryId = $ret['ret']['withdraw_entry']['id'];
        $entry = $em->getRepository('BBDurianBundle:CashWithdrawEntry')->findOneBy(['id' => $entryId]);

        $this->assertEquals($entry->getCashId(), $ret['ret']['withdraw_entry']['cash_id']);
        $this->assertEquals($entry->getUserId(), $ret['ret']['withdraw_entry']['user_id']);
        $this->assertEquals($entry->getDomain(), $ret['ret']['withdraw_entry']['domain']);
        $this->assertEquals($entry->getAmount(), $ret['ret']['withdraw_entry']['amount']);
        $this->assertTrue($ret['ret']['withdraw_entry']['first']);

        $param = $em->find('BBDurianBundle:AccountLog', 1);

        $this->assertEquals('首次出款', $param->getRemark());
    }

    /**
     * 測試現金出款為直營網且為自動入款
     */
    public function testWithdrawWithDirectDomainAndAutoWithdraw()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $client = $this->createClient();

        // 改為直營網domain
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setDomain(6);

        // 將銀行調整為自動出款銀行
        $bankInfo = $em->find('BBDurianBundle:BankInfo', 2);
        $bankInfo->setAutoWithdraw(true);
        $em->flush();

        $parameters = [
            'bank_id' => 4,
            'amount' => -50,
            'fee' => -1,
            'deduction' => -1,
            'payment_gateway_fee' => -1,
            'memo' => 'test',
            'ip'=> '127.0.0.1'
        ];

        $client->request('POST', '/api/user/8/cash/withdraw', $parameters, [], $this->headerParam);

        // 跑背景程式讓queue被消化
        $cashPoper = new Poper();
        $cashPoper->runPop($this->getContainer(), 'cash');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $em->refresh($user);
        $entryId = $ret['ret']['withdraw_entry']['id'];
        $entry = $em->getRepository('BBDurianBundle:CashWithdrawEntry')->findOneBy(['id' => $entryId]);

        $this->assertEquals($entry->getCashId(), $ret['ret']['withdraw_entry']['cash_id']);
        $this->assertEquals($entry->getUserId(), $ret['ret']['withdraw_entry']['user_id']);
        $this->assertEquals('TWD', $ret['ret']['withdraw_entry']['currency']);
        $this->assertEquals($entry->getDomain(), $ret['ret']['withdraw_entry']['domain']);
        $this->assertEquals($entry->getAmount(), $ret['ret']['withdraw_entry']['amount']);
        $this->assertEquals($entry->getFee(), $ret['ret']['withdraw_entry']['fee']);
        $this->assertEquals($entry->getDeduction(), $ret['ret']['withdraw_entry']['deduction']);
        $this->assertEquals(-1, $ret['ret']['withdraw_entry']['fee']);
        $this->assertEquals($entry->getRealAmount(), $ret['ret']['withdraw_entry']['real_amount']);
        $this->assertEquals($entry->isFirst(), $ret['ret']['withdraw_entry']['first']);
        $this->assertEquals($entry->getIp(), $ret['ret']['withdraw_entry']['ip']);
        $this->assertEquals($entry->getMemo(), $ret['ret']['withdraw_entry']['memo']);
        $this->assertEquals($entry->getStatus(), $ret['ret']['withdraw_entry']['status']);
        $this->assertEquals($entry->getConfirmAt(), $ret['ret']['withdraw_entry']['confirm_at']);
        $this->assertEquals($entry->getLevelId(), $ret['ret']['withdraw_entry']['level_id']);
        $this->assertEquals($entry->getNameReal(), $ret['ret']['withdraw_entry']['name_real']);
        $this->assertEquals($entry->getTelephone(), $ret['ret']['withdraw_entry']['telephone']);
        $this->assertEquals($entry->getBankName(), $ret['ret']['withdraw_entry']['bank_name']);
        $this->assertEquals($entry->getAccount(), $ret['ret']['withdraw_entry']['account']);
        $this->assertEquals(8, $ret['ret']['withdraw_entry']['previous_id']);
        $this->assertNull($user->getLastBank());
        $this->assertFalse($ret['ret']['withdraw_entry']['detail_modified']);

        $centry = $emEntry->getRepository('BBDurianBundle:CashEntry')->findOneBy(['id' => $ret['ret']['entry']['id']]);
        $this->assertEquals($centry->getOpcode(), $ret['ret']['entry']['opcode']);
        $this->assertEquals($centry->getMemo(), $ret['ret']['entry']['memo']);
        $this->assertEquals($centry->getAmount(), $ret['ret']['entry']['amount']);
        $this->assertEquals($entry->getId(), $ret['ret']['entry']['ref_id']);

        $param = $em->find('BBDurianBundle:AccountLog', 1);

        $this->assertNull($param);
    }

    /**
     * 測試現金確認出款但操作者不相符
     */
    public function testConfirmWithdrawByInvalidOperator()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $entry = $em->getRepository('BBDurianBundle:CashWithdrawEntry')
            ->findOneBy(['id' => 8]);
        $entry->setStatus(CashWithdrawEntry::UNTREATED);
        $em->flush();

        // 鎖定出款資料
        $client->request('PUT', '/api/cash/withdraw/8/lock', ['operator' => 'test_username'], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(CashWithdrawEntry::LOCK, $output['ret'][0]['status']);

        // 測試確認操作者不相符
        $parameters = [
            'checked_username' => 'test_123',
            'status' => 1
        ];

        $client->request('PUT', '/api/cash/withdraw/8', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(380015, $ret['code']);
        $this->assertEquals('Invalid operator', $ret['msg']);
    }

    /**
     * 測試現金確認出款
     */
    public function testConfirmWithdrawByConfirm()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default');
        $client = $this->createClient();

        // 鎖定出款資料
        $client->request('PUT', '/api/cash/withdraw/8/lock', ['operator' => 'test_username'], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(CashWithdrawEntry::LOCK, $output['ret'][0]['status']);

        // 測試確認成功
        $parameters = [
            'checked_username' => 'test_username',
            'status'           => 1
        ];

        $client->request('PUT', '/api/cash/withdraw/8', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals(CashWithdrawEntry::CONFIRM, $ret['ret']['withdraw_entry'][0]['status']);

        // 驗證出款鎖定明細已刪除
        $welEntry = $em->getRepository('BBDurianBundle:WithdrawEntryLock')
            ->findOneBy(['entryId' => $ret['ret']['withdraw_entry'][0]['id']]);
        $this->assertNull($welEntry);

        // 檢查使用者出入款統計資料
        $userStat = $em->find('BBDurianBundle:UserStat', 8);
        $this->assertEquals(1, $userStat->getWithdrawCount());
        $this->assertEquals(185, $userStat->getWithdrawTotal());
        $this->assertEquals(185, $userStat->getWithdrawMax());

        //驗證operationLog
        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $msg2 = '@withdraw_count:0=>1, @withdraw_total:0=>185, @withdraw_max:0=>185.0000, @modified_at:';
        $this->assertEquals('user_stat', $logOp2->getTableName());
        $this->assertEquals('@user_id:8', $logOp2->getMajorKey());
        $this->assertContains($msg2, $logOp2->getMessage());

        $operationlog = $emShare->find('BBDurianBundle:LogOperation', 3);
        $idLog = sprintf('@id:%s', $ret['ret']['withdraw_entry'][0]['id']);
        $msg = sprintf('@status:%s=>%s', CashWithdrawEntry::LOCK, CashWithdrawEntry::CONFIRM);

        $this->assertEquals($operationlog->getTableName(), 'cash_withdraw_entry');
        $this->assertEquals($operationlog->getMajorKey(), $idLog);
        $this->assertEquals($operationlog->getMessage(), $msg);

        $queueName = 'cash_deposit_withdraw_queue';
        $this->assertEquals(1, $redis->llen($queueName));

        $queue = json_decode($redis->rpop($queueName), true);

        $this->assertEquals(0, $queue['ERRCOUNT']);
        $this->assertEquals(8, $queue['user_id']);
        $this->assertFalse($queue['deposit']);
        $this->assertTrue($queue['withdraw']);
        $this->assertNotNull($queue['withdraw_at']);
    }

    /**
     * 測試現金確認自動出款
     */
    public function testConfirmWithdrawByConfirmAutoWithdraw()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $sql = "Update cash SET currency = '978' WHERE id = 7";
        $em->getConnection()->executeUpdate($sql);

        $parameters = [
            'bank_id'   => 5,
            'amount'    => -50,
            'fee'       => -1,
            'deduction' => -1,
            'memo'      => 'test',
            'ip'        => '127.0.0.1'
        ];

        $client->request('POST', '/api/user/8/cash/withdraw', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertTrue($ret['ret']['withdraw_entry']['auto_withdraw']);

        // 鎖定出款資料
        $client->request('PUT', '/api/cash/withdraw/11/lock', ['operator' => 'test_username'], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(CashWithdrawEntry::LOCK, $output['ret'][0]['status']);

        $client = $this->createClient();

        $mockHelper = $this->getMockBuilder('BB\DurianBundle\Withdraw\Helper')
            ->disableOriginalConstructor()
            ->setMethods(['checkAutoWithdraw'])
            ->getMock();

        $client->getContainer()->set('durian.withdraw_helper', $mockHelper);

        $parameters = [
            'checked_username' => 'test_username',
            'status' => 1,
            'merchant_withdraw_id' => 5,
        ];

        $client->request('PUT', '/api/cash/withdraw/11', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals(CashWithdrawEntry::SYSTEM_LOCK, $ret['ret']['withdraw_entry'][0]['status']);
        $this->assertEquals(2, $ret['ret']['withdraw_entry'][0]['domain']);
        $this->assertEquals(5, $ret['ret']['withdraw_entry'][0]['merchant_withdraw_id']);
    }

    /**
     * 測試直營網現金確認自動出款
     */
    public function testConfirmWithdrawDirectDomainByConfirmAutoWithdraw()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        // 改為直營網domain
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setDomain(6);
        $em->flush();

        $parameters = [
            'bank_id' => 5,
            'amount' => -50,
            'fee' => -1,
            'deduction' => -1,
            'memo' => 'test',
            'ip' => '127.0.0.1'
        ];

        $client->request('POST', '/api/user/8/cash/withdraw', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertTrue($ret['ret']['withdraw_entry']['auto_withdraw']);

        // 鎖定出款資料
        $client->request('PUT', '/api/cash/withdraw/11/lock', ['operator' => 'test_username'], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(CashWithdrawEntry::LOCK, $output['ret'][0]['status']);

        $client = $this->createClient();

        $mockHelper = $this->getMockBuilder('BB\DurianBundle\Withdraw\Helper')
            ->disableOriginalConstructor()
            ->setMethods(['checkAutoWithdraw'])
            ->getMock();

        $client->getContainer()->set('durian.withdraw_helper', $mockHelper);

        $parameters = [
            'checked_username' => 'test_username',
            'status' => 1,
            'merchant_withdraw_id' => 5
        ];

        $client->request('PUT', '/api/cash/withdraw/11', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals(CashWithdrawEntry::SYSTEM_LOCK, $ret['ret']['withdraw_entry'][0]['status']);
        $this->assertEquals(6, $ret['ret']['withdraw_entry'][0]['domain']);
        $this->assertEquals(5, $ret['ret']['withdraw_entry'][0]['merchant_withdraw_id']);
    }

    /**
     * 測試用levelId確認自動出款
     */
    public function testConfirmAutoWithdrawByLevelId()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $sql = "Update cash SET currency = '978' WHERE id = 7";
        $em->getConnection()->executeUpdate($sql);

        $parameters = [
            'bank_id' => 5,
            'amount' => -50,
            'fee' => -1,
            'deduction' => -1,
            'memo' => 'test',
            'ip' => '127.0.0.1'
        ];

        $client->request('POST', '/api/user/8/cash/withdraw', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertTrue($ret['ret']['withdraw_entry']['auto_withdraw']);

        // 鎖定出款資料
        $client->request('PUT', '/api/cash/withdraw/11/lock', ['operator' => 'test_username'], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(CashWithdrawEntry::LOCK, $output['ret'][0]['status']);

        $client = $this->createClient();

        $mockHelper = $this->getMockBuilder('BB\DurianBundle\Withdraw\Helper')
            ->disableOriginalConstructor()
            ->setMethods(['checkAutoWithdraw'])
            ->getMock();

        $client->getContainer()->set('durian.withdraw_helper', $mockHelper);

        $parameters = [
            'checked_username' => 'test_username',
            'status' => 1,
            'merchant_withdraw_id' => 5
        ];

        $client->request('PUT', '/api/cash/withdraw/11', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals(CashWithdrawEntry::SYSTEM_LOCK, $ret['ret']['withdraw_entry'][0]['status']);
        $this->assertEquals(2, $ret['ret']['withdraw_entry'][0]['domain']);
    }

    /**
     * 測試確認出款沒有帶入 status
     */
    public function testWithdrawConfirmWithoutStatus()
    {
        $client = $this->createClient();

        // 鎖定出款資料
        $client->request('PUT', '/api/cash/withdraw/8/lock', ['operator' => 'test_username'], [], $this->headerParam);

        $client->request('PUT', '/api/cash/withdraw/8');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(380002, $output['code']);
        $this->assertEquals('No status specified', $output['msg']);
    }

    /**
     * 測試確認出款沒有帶入 checked_username
     */
    public function testWithdrawConfirmWithoutCheckedUsername()
    {
        $client = $this->createClient();

        // 鎖定出款資料
        $client->request('PUT', '/api/cash/withdraw/8/lock', ['operator' => 'test_username'], [], $this->headerParam);

        $client->request('PUT', '/api/cash/withdraw/8', ['status' => 2]);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(380003, $output['code']);
        $this->assertEquals('No checked_username specified', $output['msg']);
    }

    /**
     * 測試確認出款帶入不存在的 id
     */
    public function testWithdrawConfirmWithEntryIdNotExists()
    {
        $client = $this->createClient();
        $client->request('PUT', '/api/cash/withdraw/999');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(380001, $output['code']);
        $this->assertEquals('No such withdraw entry', $output['msg']);
    }

    /**
     * 測試確認出款帶入已確認 id
     */
    public function testWithdrawConfirmWithStatusConfirm()
    {
        $client = $this->createClient();

        $parameters = [
            'checked_username' => 'test_username',
            'status' => 1
        ];

        $client->request('PUT', '/api/cash/withdraw/1', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals('Withdraw status not lock', $ret['msg']);
        $this->assertEquals(380014, $ret['code']);
    }

    /**
     * 測試現金確認自動出款找不到銀行幣別
     */
    public function testConfirmWithdrawByConfirmAutoWithdrawWithoutBankCurrency()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $sql = "Update cash SET currency = '978' WHERE id = 7";
        $em->getConnection()->executeUpdate($sql);

        $parameters = [
            'bank_id'   => 5,
            'amount'    => -50,
            'fee'       => -1,
            'deduction' => -1,
            'memo'      => 'test',
            'ip'        => '127.0.0.1'
        ];

        $client->request('POST', '/api/user/8/cash/withdraw', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertTrue($ret['ret']['withdraw_entry']['auto_withdraw']);

        // 鎖定出款資料
        $client->request('PUT', '/api/cash/withdraw/11/lock', ['operator' => 'test_username'], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(CashWithdrawEntry::LOCK, $output['ret'][0]['status']);
    }

    /**
     * 測試現金確認自動出款未指定商號
     */
    public function testConfirmWithdrawByConfirmAutoWithdrawWithNoMerchantWithdrawIdSpecified()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = [
            'bank_id' => 5,
            'amount' => -50,
            'fee' => -1,
            'deduction' => -1,
            'memo' => 'test',
            'ip' => '127.0.0.1'
        ];

        $client->request('POST', '/api/user/8/cash/withdraw', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertTrue($ret['ret']['withdraw_entry']['auto_withdraw']);

        // 鎖定出款資料
        $client->request('PUT', '/api/cash/withdraw/11/lock', ['operator' => 'test_username'], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(CashWithdrawEntry::LOCK, $output['ret'][0]['status']);
    }

    /**
     * 測試現金確認自動出款找不到商號
     */
    public function testConfirmWithdrawByConfirmAutoWithdrawWithoutMerchant()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $sql = "Update cash SET currency = '978' WHERE id = 7";
        $em->getConnection()->executeUpdate($sql);

        $parameters = [
            'bank_id'   => 5,
            'amount'    => -50,
            'fee'       => -1,
            'deduction' => -1,
            'memo'      => 'test',
            'ip'        => '127.0.0.1'
        ];

        $client->request('POST', '/api/user/8/cash/withdraw', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertTrue($ret['ret']['withdraw_entry']['auto_withdraw']);

        // 鎖定出款資料
        $client->request('PUT', '/api/cash/withdraw/11/lock', ['operator' => 'test_username'], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(CashWithdrawEntry::LOCK, $output['ret'][0]['status']);
    }

    /**
     * 測試現金確認自動出款商家但找不到銀行資料
     */
    public function testConfirmWithdrawByConfirmAutoWithdrawButNoBankFound()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $sql = "Update cash SET currency = '978' WHERE id = 7";
        $em->getConnection()->executeUpdate($sql);

        $parameters = [
            'bank_id' => 5,
            'amount' => -50,
            'fee' => -1,
            'deduction' => -1,
            'memo' => 'test',
            'ip' => '127.0.0.1'
        ];

        $client->request('POST', '/api/user/8/cash/withdraw', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertTrue($ret['ret']['withdraw_entry']['auto_withdraw']);

        // 鎖定出款資料
        $client->request('PUT', '/api/cash/withdraw/11/lock', ['operator' => 'test_username'], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(CashWithdrawEntry::LOCK, $output['ret'][0]['status']);
    }

    /**
     * 測試現金確認自動出款商家未核准
     */
    public function testConfirmWithdrawByConfirmAutoWithdrawWithMerchantIsNotApproved()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $sql = "Update cash SET currency = '978' WHERE id = 7";
        $em->getConnection()->executeUpdate($sql);

        $parameters = [
            'bank_id' => 5,
            'amount' => -50,
            'fee' => -1,
            'deduction' => -1,
            'memo' => 'test',
            'ip' => '127.0.0.1'
        ];

        $client->request('POST', '/api/user/8/cash/withdraw', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertTrue($ret['ret']['withdraw_entry']['auto_withdraw']);

        // 鎖定出款資料
        $client->request('PUT', '/api/cash/withdraw/11/lock', ['operator' => 'test_username'], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(CashWithdrawEntry::LOCK, $output['ret'][0]['status']);
    }

    /**
     * 測試現金確認自動出款商家已停用
     */
    public function testConfirmWithdrawByConfirmAutoWithdrawWithMerchantIsDisabled()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $sql = "Update cash SET currency = '978' WHERE id = 7";
        $em->getConnection()->executeUpdate($sql);

        $merchantWithdraw = $em->find('BBDurianBundle:MerchantWithdraw', 5);
        $merchantWithdraw->disable();
        $em->flush();

        $parameters = [
            'bank_id' => 5,
            'amount' => -50,
            'fee' => -1,
            'deduction' => -1,
            'memo' => 'test',
            'ip' => '127.0.0.1'
        ];

        $client->request('POST', '/api/user/8/cash/withdraw', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertTrue($ret['ret']['withdraw_entry']['auto_withdraw']);

        // 鎖定出款資料
        $client->request('PUT', '/api/cash/withdraw/11/lock', ['operator' => 'test_username'], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(CashWithdrawEntry::LOCK, $output['ret'][0]['status']);
    }

    /**
     * 測試現金確認自動出款商家已暫停
     */
    public function testConfirmWithdrawByConfirmAutoWithdrawWithMerchantIsSuspended()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $sql = "Update cash SET currency = '978' WHERE id = 7";
        $em->getConnection()->executeUpdate($sql);

        $merchantWithdraw = $em->find('BBDurianBundle:MerchantWithdraw', 5);
        $merchantWithdraw->suspend();
        $em->flush();

        $parameters = [
            'bank_id' => 5,
            'amount' => -50,
            'fee' => -1,
            'deduction' => -1,
            'memo' => 'test',
            'ip' => '127.0.0.1'
        ];

        $client->request('POST', '/api/user/8/cash/withdraw', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertTrue($ret['ret']['withdraw_entry']['auto_withdraw']);

        // 鎖定出款資料
        $client->request('PUT', '/api/cash/withdraw/11/lock', ['operator' => 'test_username'], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(CashWithdrawEntry::LOCK, $output['ret'][0]['status']);
    }

    /**
     * 測試現金確認自動出款商家已刪除
     */
    public function testConfirmWithdrawByConfirmAutoWithdrawWithMerchantIsRemoved()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $sql = "Update cash SET currency = '978' WHERE id = 7";
        $em->getConnection()->executeUpdate($sql);

        $merchantWithdraw = $em->find('BBDurianBundle:MerchantWithdraw', 5);
        $merchantWithdraw->remove();
        $em->flush();

        $parameters = [
            'bank_id' => 5,
            'amount' => -50,
            'fee' => -1,
            'deduction' => -1,
            'memo' => 'test',
            'ip' => '127.0.0.1'
        ];

        $client->request('POST', '/api/user/8/cash/withdraw', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertTrue($ret['ret']['withdraw_entry']['auto_withdraw']);

        // 鎖定出款資料
        $client->request('PUT', '/api/cash/withdraw/11/lock', ['operator' => 'test_username'], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(CashWithdrawEntry::LOCK, $output['ret'][0]['status']);
    }

    /**
     * 測試現金取消出款
     */
    public function testConfirmWithdrawByCancel()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $cash = $em->find('BBDurianBundle:Cash', 7);
        $this->assertEquals(1000, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());

        $em->clear();

        $parameters = [
            'bank_id'   => 4,
            'amount'    => -50,
            'fee'       => -1,
            'deduction' => -1,
            'memo'      => 'test',
            'ip'        => '127.0.0.1'
        ];

        $client->request('POST', '/api/user/8/cash/withdraw', $parameters, [], $this->headerParam);

        $parameters = [
            'bank_id'   => 4,
            'amount'    => -30,
            'fee'       => -1,
            'deduction' => -1,
            'memo'      => 'test',
            'ip'        => '127.0.0.1'
        ];

        $client->request('POST', '/api/user/8/cash/withdraw', $parameters, [], $this->headerParam);

        //跑背景程式讓queue被消化
        $cashPoper = new Poper();
        $cashPoper->runPop($this->getContainer(), 'cash');

        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $user = $em->find('BBDurianBundle:User', 8);
        $cash = $user->getCash();

        $this->assertEquals(920, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());

        // 鎖定出款資料
        $client->request('PUT', '/api/cash/withdraw/11/lock', ['operator' => 'test_username'], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(CashWithdrawEntry::LOCK, $output['ret'][0]['status']);
        $this->assertEquals(CashWithdrawEntry::LOCK, $output['ret'][1]['status']);

        $parameters = [
            'checked_username' => 'test_username',
            'status'           => 2
        ];

        $client->request('PUT', '/api/cash/withdraw/11', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        //跑背景程式讓queue被消化
        $cashPoper = new Poper();
        $cashPoper->runPop($this->getContainer(), 'cash');

        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->clear();

        $this->assertEquals('ok', $ret['result']);

        $cash = $em->find('BBDurianBundle:Cash', 7);
        $centry = $emEntry->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $ret['ret']['entry'][0]['id']]);

        $this->assertEquals(CashWithdrawEntry::CANCEL, $ret['ret']['withdraw_entry'][0]['status']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals($centry->getAmount(), $ret['ret']['entry'][0]['amount']);
        $this->assertEquals($centry->getBalance(), $ret['ret']['entry'][0]['balance']);
        $this->assertEquals($centry->getOpcode(), $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(11, $ret['ret']['entry'][0]['ref_id']);
        $this->assertEquals(1000, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());

        // 驗證出款鎖定明細已刪除
        $welEntry = $em->getRepository('BBDurianBundle:WithdrawEntryLock')
            ->findOneBy(['entryId' => $ret['ret']['withdraw_entry'][0]['id']]);
        $this->assertNull($welEntry);

        //驗證operationLog且兩筆都有記錄
        $operationlog = $emShare->find('BBDurianBundle:LogOperation', 4);
        $idLog = sprintf('@id:%s', $ret['ret']['withdraw_entry'][0]['id']);
        $msg = sprintf('@status:%s=>%s', CashWithdrawEntry::LOCK, CashWithdrawEntry::CANCEL);

        $this->assertEquals($operationlog->getTableName(), 'cash_withdraw_entry');
        $this->assertEquals($operationlog->getMajorKey(), $idLog);
        $this->assertEquals($operationlog->getMessage(), $msg);
    }

    /**
     * 測試現金取消出款會連動取消該會員該筆明細之後所有鎖定及未處理明細
     */
    public function testCancelWithdrawByCancelNextUntreatedAndLockEntries()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();
        $ceRepo = $emEntry->getRepository('BBDurianBundle:CashEntry');
        $welRepo = $em->getRepository('BBDurianBundle:WithdrawEntryLock');

        $cash = $em->find('BBDurianBundle:Cash', 7);
        $this->assertEquals(1000, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());
        $em->clear();

        // 鎖定第一筆出款資料
        $client->request('PUT', '/api/cash/withdraw/8/lock', ['operator' => 'test_username'], [], $this->headerParam);

        // 申請第二筆出款
        $params = [
            'bank_id' => 4,
            'amount' => -30,
            'fee' => -1,
            'deduction' => -1,
            'memo' => 'test',
            'ip' => '127.0.0.1'
        ];
        $client->request('POST', '/api/user/8/cash/withdraw', $params, [], $this->headerParam);

        // 跑背景程式讓queue被消化
        $cashPoper = new Poper();
        $cashPoper->runPop($this->getContainer(), 'cash');

        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->clear();

        // 不同操作者鎖定第二筆出款資料
        $client->request('PUT', '/api/cash/withdraw/11/lock', ['operator' => 'test123'], [], $this->headerParam);

        // 申請第三筆出款
        $params = [
            'bank_id' => 4,
            'amount' => -30,
            'fee' => -1,
            'deduction' => -1,
            'memo' => 'test',
            'ip' => '127.0.0.1'
        ];
        $client->request('POST', '/api/user/8/cash/withdraw', $params, [], $this->headerParam);

        // 跑背景程式讓queue被消化
        $cashPoper = new Poper();
        $cashPoper->runPop($this->getContainer(), 'cash');

        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->clear();

        // 第二筆資料取消出款
        $params = [
            'checked_username' => 'test123',
            'status' => CashWithdrawEntry::CANCEL
        ];
        $client->request('PUT', '/api/cash/withdraw/11', $params);

        // 跑背景程式讓queue被消化
        $cashPoper = new Poper();
        $cashPoper->runPop($this->getContainer(), 'cash');

        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->clear();

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $cash = $em->find('BBDurianBundle:Cash', 7);
        $centry = $ceRepo->findOneBy(['id' => $ret['ret']['entry'][0]['id']]);

        $this->assertEquals(11, $ret['ret']['withdraw_entry'][0]['id']);
        $this->assertEquals(CashWithdrawEntry::CANCEL, $ret['ret']['withdraw_entry'][0]['status']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals($centry->getAmount(), $ret['ret']['entry'][0]['amount']);
        $this->assertEquals($centry->getBalance(), $ret['ret']['entry'][0]['balance']);
        $this->assertEquals($centry->getOpcode(), $ret['ret']['entry'][0]['opcode']);
        $this->assertEquals(11, $ret['ret']['entry'][0]['ref_id']);

        $centry = $ceRepo->findOneBy(['id' => $ret['ret']['entry'][1]['id']]);

        $this->assertEquals(12, $ret['ret']['withdraw_entry'][1]['id']);
        $this->assertEquals(CashWithdrawEntry::CANCEL, $ret['ret']['withdraw_entry'][1]['status']);
        $this->assertEquals($cash->getBalance(), $ret['ret']['cash']['balance']);
        $this->assertEquals($cash->getPreSub(), $ret['ret']['cash']['pre_sub']);
        $this->assertEquals($cash->getPreAdd(), $ret['ret']['cash']['pre_add']);
        $this->assertEquals($centry->getAmount(), $ret['ret']['entry'][1]['amount']);
        $this->assertEquals($centry->getBalance(), $ret['ret']['entry'][1]['balance']);
        $this->assertEquals($centry->getOpcode(), $ret['ret']['entry'][1]['opcode']);
        $this->assertEquals(12, $ret['ret']['entry'][1]['ref_id']);

        // 驗證出款鎖定明細已刪除
        $welEntry = $welRepo->findOneBy(['entryId' => $ret['ret']['withdraw_entry'][0]['id']]);
        $this->assertNull($welEntry);

        // 檢查redis內餘額
        $redisWallet = $this->getContainer()->get('snc_redis.wallet4');
        $cashKey = 'cash_balance_8_901';
        $this->assertEquals($cash->getBalance() * 10000, $redisWallet->hget($cashKey, 'balance'));
        $this->assertEquals($cash->getPreAdd() * 10000, $redisWallet->hget($cashKey, 'pre_add'));
        $this->assertEquals($cash->getPreSub() * 10000, $redisWallet->hget($cashKey, 'pre_sub'));

        // 檢查第一筆出款鎖定明細未被刪除
        $welArray = $welRepo->findOneBy(['entryId' => 8])->toArray();
        $this->assertEquals(8, $welArray['entry_id']);
        $this->assertEquals('test_username', $welArray['operator']);

        // 檢查第一筆出款明細還是刪除狀態
        $entry = $em->getRepository('BBDurianBundle:CashWithdrawEntry')
            ->findOneBy(['id' => 8]);
        $this->assertEquals(CashWithdrawEntry::LOCK, $entry->getStatus());

        // 驗證operationLog且兩筆都有記錄
        $operationlog = $emShare->find('BBDurianBundle:LogOperation', 3);
        $idLog = sprintf('@id:%s', $ret['ret']['withdraw_entry'][0]['id']);
        $msg = sprintf('@status:%s=>%s', CashWithdrawEntry::LOCK, CashWithdrawEntry::CANCEL);

        $this->assertEquals($operationlog->getTableName(), 'cash_withdraw_entry');
        $this->assertEquals($operationlog->getMajorKey(), $idLog);
        $this->assertEquals($operationlog->getMessage(), $msg);

        $operationlog = $emShare->find('BBDurianBundle:LogOperation', 4);
        $idLog = sprintf('@id:%s', $ret['ret']['withdraw_entry'][1]['id']);
        $msg = sprintf('@status:%s=>%s', CashWithdrawEntry::UNTREATED, CashWithdrawEntry::CANCEL);

        $this->assertEquals($operationlog->getTableName(), 'cash_withdraw_entry');
        $this->assertEquals($operationlog->getMajorKey(), $idLog);
        $this->assertEquals($operationlog->getMessage(), $msg);
    }

    /**
     * 測試現金拒絕出款
     */
    public function testConfirmWithdrawByReject()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        // 鎖定出款資料
        $client->request('PUT', '/api/cash/withdraw/8/lock', ['operator' => 'test_username'], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(CashWithdrawEntry::LOCK, $output['ret'][0]['status']);

        $parameters = [
            'checked_username' => 'test_username',
            'status'           => 3
        ];

        $client->request('PUT', '/api/cash/withdraw/8', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(CashWithdrawEntry::REJECT, $ret['ret']['withdraw_entry'][0]['status']);

        // 驗證出款操作者明細已刪除
        $welEntry = $em->getRepository('BBDurianBundle:WithdrawEntryLock')
            ->findOneBy(['entryId' => $ret['ret']['withdraw_entry'][0]['id']]);
        $this->assertNull($welEntry);

        //驗證operationLog
        $operationlog = $emShare->find('BBDurianBundle:LogOperation', 2);
        $idLog = sprintf('@id:%s', $ret['ret']['withdraw_entry'][0]['id']);
        $msg = sprintf('@status:%s=>%s', CashWithdrawEntry::LOCK, CashWithdrawEntry::REJECT);

        $this->assertEquals($operationlog->getTableName(), 'cash_withdraw_entry');
        $this->assertEquals($operationlog->getMajorKey(), $idLog);
        $this->assertEquals($operationlog->getMessage(), $msg);

        // 確認紀錄敏感資訊log
        $logPath = $this->getLogfilePath('sensitive.log');
        $this->assertFileExists($logPath);
        $results = explode(PHP_EOL, file_get_contents($logPath));
        $line = "sensitive-data:$this->sensitiveData";
        $this->assertTrue(strpos($results[0], $line) !== false);
    }

    /**
     * 測試現金直營網確認出款連線到帳務系統發生例外
     */
    public function testConfirmWithdrawByDirectDomainsConnectOccurException()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 暫時改為直營網domain以測試Acc狀態
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setDomain(6);
        $em->flush();

        // 鎖定出款資料
        $client->request('PUT', '/api/cash/withdraw/8/lock', ['operator' => 'test_username'], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(CashWithdrawEntry::LOCK, $output['ret'][0]['status']);

        $helper = $this->getContainer()->get('durian.withdraw_helper');

        $mockClient = $this->getMockBuilder('Buzz\Client\Curl')
            ->setMethods(['send'])
            ->getMock();
        $msg = 'Operation timed out after 5000 milliseconds with 0 bytes received';
        $mockClient->expects($this->once())
            ->method('send')
            ->willThrowException(new \Exception($msg, 28));

        $client = $this->createClient();
        $helper->setClient($mockClient);
        $client->getContainer()->set('durian.withdraw_helper', $helper);

        $container = $this->getContainer();
        $logsDir = $container->getParameter('kernel.logs_dir') . '/test';

        $dirPath = $logsDir . DIRECTORY_SEPARATOR . 'account';
        $logPath = $dirPath . DIRECTORY_SEPARATOR . 'checkStatus.log';

        if (file_exists($logPath)) {
            unlink($logPath);
        }

        $parameters = [
            'checked_username' => 'test_username',
            'status' => CashWithdrawEntry::CONFIRM
        ];

        $client->request('PUT', '/api/cash/withdraw/8', $parameters);

        $result = $client->getResponse()->getContent();
        $ret = json_decode($result, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals('Connect to account failure', $ret['msg']);
        $this->assertEquals(380028, $ret['code']);

        // 檢查log是否存在
        $this->assertFileExists($logPath);

        // 檢查log內容
        $contents = file_get_contents($logPath);
        $results = explode(PHP_EOL, $contents);

        $logMsg = 'LOGGER.DEBUG: GET /app/tellership/auto_check_tellership.php?uitype=auto&from_id=8';
        $this->assertContains($logMsg, $results[0]);
        $this->assertContains('Host:', $results[1]);
        $this->assertContains($msg, $results[2]);

        unlink($logPath);
    }

    /**
     * 測試現金直營網確認出款連線到帳務系統連線狀態非200
     */
    public function testConfirmWithdrawByDirectDomainsConnectStatusNot200()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 暫時改為直營網domain以測試Acc狀態
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setDomain(6);
        $em->flush();

        // 鎖定出款資料
        $client->request('PUT', '/api/cash/withdraw/8/lock', ['operator' => 'test_username'], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(CashWithdrawEntry::LOCK, $output['ret'][0]['status']);

        $helper = $this->getContainer()->get('durian.withdraw_helper');
        $mockClient = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $param = [
            8 => ['username' => 'testconfirm']
        ];

        $response = new Response();
        $response->setContent(json_encode($param));
        $response->addHeader('HTTP/1.1 499');

        $helper->setClient($mockClient);
        $helper->setResponse($response);

        $client = $this->createClient();
        $client->getContainer()->set('durian.withdraw_helper', $helper);

        $parameters = [
            'checked_username' => 'test_username',
            'status' => CashWithdrawEntry::CONFIRM
        ];

        $client->request('PUT', '/api/cash/withdraw/8', $parameters);

        $result = $client->getResponse()->getContent();
        $ret = json_decode($result, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals('Connect to account failure', $ret['msg']);
        $this->assertEquals(380028, $ret['code']);
    }

    /**
     * 測試現金直營網確認出款沒有回傳狀態
     */
    public function testConfirmWithdrawByDirectDomainsWithoutStatus()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        //暫時改為直營網domain以測試Acc狀態
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setDomain(6);
        $em->flush();

        // 鎖定出款資料
        $client->request('PUT', '/api/cash/withdraw/8/lock', ['operator' => 'test_username'], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(CashWithdrawEntry::LOCK, $output['ret'][0]['status']);

        $helper = $this->getContainer()->get('durian.withdraw_helper');
        $mockClient = $this->getMockBuilder('Buzz\Client\Curl')
                       ->getMock();

        $result = [
            8 => [
                'username' => 'testconfirm'
            ]
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $helper->setClient($mockClient);
        $helper->setResponse($response);

        $client = $this->createClient();
        $client->getContainer()->set('durian.withdraw_helper', $helper);

        $parameters = [
            'checked_username' => 'test_username',
            'status'           => CashWithdrawEntry::CONFIRM
        ];

        $client->request('PUT', '/api/cash/withdraw/8', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals('Can not confirm the status of account', $ret['msg']);
        $this->assertEquals(380007, $ret['code']);
    }

    /**
     * 測試現金直營網確認出款回傳狀態尚未完成
     */
    public function testConfirmWithdrawByDirectDomainsWithStatusZero()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        //暫時改為直營網domain以測試Acc狀態
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setDomain(6);
        $em->flush();

        // 鎖定出款資料
        $client->request('PUT', '/api/cash/withdraw/8/lock', ['operator' => 'test_username'], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(CashWithdrawEntry::LOCK, $output['ret'][0]['status']);

        $helper = $this->getContainer()->get('durian.withdraw_helper');
        $mockClient = $this->getMockBuilder('Buzz\Client\Curl')
                       ->getMock();

        $result = [
            8 => [
                'status' => 0,
                'username' => 'testconfirm'
            ]
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $helper->setClient($mockClient);
        $helper->setResponse($response);

        $client = $this->createClient();
        $client->getContainer()->set('durian.withdraw_helper', $helper);

        $parameters = [
            'checked_username' => 'test_username',
            'status'           => CashWithdrawEntry::CONFIRM
        ];

        $client->request('PUT', '/api/cash/withdraw/8', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals('Can not confirm when account status is paying', $ret['msg']);
        $this->assertEquals(380008, $ret['code']);
    }

    /**
     * 測試現金取消出款直營網回傳狀態為確認出款
     */
    public function testCancelWithdrawByDirectDomainsWithAccStatusConfirm()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        //暫時改為直營網domain以測試Acc狀態
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setDomain(6);
        $em->flush();

        // 鎖定出款資料
        $client->request('PUT', '/api/cash/withdraw/8/lock', ['operator' => 'test_username'], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(CashWithdrawEntry::LOCK, $output['ret'][0]['status']);

        $helper = $this->getContainer()->get('durian.withdraw_helper');
        $mockClient = $this->getMockBuilder('Buzz\Client\Curl')
                       ->getMock();

        $result = [
            8 => [
                'status' => 1,
                'username' => 'testconfirm'
            ]
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $helper->setClient($mockClient);
        $helper->setResponse($response);

        $client = $this->createClient();
        $client->getContainer()->set('durian.withdraw_helper', $helper);

        $parameters = [
            'checked_username' => 'test_username',
            'status'           => CashWithdrawEntry::CANCEL
        ];

        $client->request('PUT', '/api/cash/withdraw/8', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals('Can not reject or cancel when account status is pay_finish', $ret['msg']);
        $this->assertEquals(380009, $ret['code']);
    }

    /**
     * 測試現金直營網確認出款回傳狀態取消出款
     */
    public function testConfirmWithdrawByDirectDomainsWithAccStatusCancel()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        //暫時改為直營網domain以測試Acc狀態
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setDomain(6);
        $em->flush();

        // 鎖定出款資料
        $client->request('PUT', '/api/cash/withdraw/8/lock', ['operator' => 'test_username'], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(CashWithdrawEntry::LOCK, $output['ret'][0]['status']);

        $helper = $this->getContainer()->get('durian.withdraw_helper');
        $mockClient = $this->getMockBuilder('Buzz\Client\Curl')
                       ->getMock();

        $result = [
            8 => [
                'status' => 2,
                'username' => 'testconfirm'
            ]
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $helper->setClient($mockClient);
        $helper->setResponse($response);

        $client = $this->createClient();
        $client->getContainer()->set('durian.withdraw_helper', $helper);

        $parameters = [
            'checked_username' => 'test_username',
            'status'           => CashWithdrawEntry::CONFIRM
        ];

        $client->request('PUT', '/api/cash/withdraw/8', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals('Can not confirm or system lock cancelled account', $ret['msg']);
        $this->assertEquals(380010, $ret['code']);
    }

    /**
     * 測試現金直營網確認出款
     */
    public function testConfirmWithdrawByDirectDomains()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        //暫時改為直營網domain以測試Acc狀態
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setDomain(6);
        $em->flush();

        $parameters = [
            'bank_id'   => 4,
            'amount'    => -50,
            'fee'       => -1,
            'deduction' => -1,
            'memo'      => 'test',
            'ip'        => '127.0.0.1'
        ];

        $client->request('POST', '/api/user/8/cash/withdraw', $parameters, [], $this->headerParam);

        //跑背景程式讓queue被消化
        $cashPoper = new Poper();
        $cashPoper->runPop($this->getContainer(), 'cash');

        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $cash = $user->getCash();

        $this->assertEquals(950, $cash->getBalance());
        $this->assertEquals(0, $cash->getPreSub());
        $this->assertEquals(0, $cash->getPreAdd());

        // 鎖定出款資料
        $client->request('PUT', '/api/cash/withdraw/11/lock', ['operator' => 'test_username'], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(CashWithdrawEntry::LOCK, $output['ret'][0]['status']);

        $helper = $this->getContainer()->get('durian.withdraw_helper');
        $mockClient = $this->getMockBuilder('Buzz\Client\Curl')
                       ->getMock();

        $result = [
            11 => [
                'status' => 1,
                'username' => 'testconfirm'
            ]
        ];

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->addHeader('HTTP/1.1 200 OK');

        $helper->setClient($mockClient);
        $helper->setResponse($response);

        $client = $this->createClient();
        $client->getContainer()->set('durian.withdraw_helper', $helper);

        $parameters = [
            'checked_username' => 'test_username',
            'status'           => CashWithdrawEntry::CONFIRM
        ];

        $client->request('PUT', '/api/cash/withdraw/11', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals(CashWithdrawEntry::CONFIRM, $ret['ret']['withdraw_entry'][0]['status']);
        $this->assertEquals($user->getDomain(), $ret['ret']['withdraw_entry'][0]['domain']);

        // 驗證出款操作者明細已刪除
        $welEntry = $em->getRepository('BBDurianBundle:WithdrawEntryLock')
            ->findOneBy(['entryId' => $ret['ret']['withdraw_entry'][0]['id']]);
        $this->assertNull($welEntry);

        // 檢查使用者出入款統計資料
        $userStat = $em->find('BBDurianBundle:UserStat', 8);
        $this->assertEquals(1, $userStat->getWithdrawCount());
        $this->assertEquals(10.704, $userStat->getWithdrawTotal());
        $this->assertEquals(10.704, $userStat->getWithdrawMax());

        //驗證operationLog
        $logOp3 = $emShare->find('BBDurianBundle:LogOperation', 3);
        $msg3 = '@withdraw_count:0=>1, @withdraw_total:0=>10.704, @withdraw_max:0=>10.7040, @modified_at:';
        $this->assertEquals('user_stat', $logOp3->getTableName());
        $this->assertEquals('@user_id:8', $logOp3->getMajorKey());
        $this->assertContains($msg3, $logOp3->getMessage());

        $operationlog = $emShare->find('BBDurianBundle:LogOperation', 4);
        $idLog = sprintf('@id:%s', $ret['ret']['withdraw_entry'][0]['id']);
        $msg = sprintf('@status:%s=>%s', CashWithdrawEntry::LOCK, CashWithdrawEntry::CONFIRM);

        $this->assertEquals($operationlog->getTableName(), 'cash_withdraw_entry');
        $this->assertEquals($operationlog->getMajorKey(), $idLog);
        $this->assertEquals($operationlog->getMessage(), $msg);
    }

    /**
     * 測試現金出款設定備註
     */
    public function testWithdrawSetMemo()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $client = $this->createClient();
        $client->request('PUT', '/api/cash/withdraw/8/memo', ['memo' => 'testmemo'], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        // 操作紀錄檢查
        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals("cash_withdraw_entry", $logOperation->getTableName());
        $this->assertEquals("@id:8", $logOperation->getMajorKey());
        $this->assertEquals("@memo:test=>testmemo", $logOperation->getMessage());

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals('testmemo', $ret['ret']['withdraw_entry']['memo']);

        // 確認紀錄敏感資訊log
        $logPath = $this->getLogfilePath('sensitive.log');
        $this->assertFileExists($logPath);
        $results = explode(PHP_EOL, file_get_contents($logPath));
        $line = "sensitive-data:$this->sensitiveData";
        $this->assertTrue(strpos($results[0], $line) !== false);
    }

    /**
     * 測試現金出款設定備註帶入不存在的 id
     */
    public function testWithdrawSetMemoWithEmptyMemo()
    {
        $client = $this->createClient();
        $client->request('PUT', '/api/cash/withdraw/1/memo', ['memo' => ''], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals('', $ret['ret']['withdraw_entry']['memo']);
    }

    /**
     * 測試現金出款設定備註帶入不存在的 id
     */
    public function testWithdrawSetMemoWithEntryIdNotExists()
    {
        $client = $this->createClient();
        $client->request('PUT', '/api/cash/withdraw/999/memo', ['memo' => ''], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(380001, $ret['code']);
        $this->assertEquals('No such withdraw entry', $ret['msg']);
    }

    /**
     * 測試設定現金出款明細備註輸入非UTF8
     */
    public function testWithdrawSetMemoInputNotUtf8()
    {
        $client = $this->createClient();

        $parameter = ['memo' => mb_convert_encoding('e龜龍鱉', 'GB2312', 'UTF-8')];

        $client->request('PUT', '/api/cash/withdraw/1/memo', $parameter, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150610002, $output['code']);
        $this->assertEquals('String must use utf-8 encoding', $output['msg']);
    }

    /**
     * 測試設定現金出款明細備註但操作者與帶入的為不同廳
     */
    public function testWithdrawSetMemoWithOperationIsNotInSameDomain()
    {
        $client = $this->createClient();

        $sensitiveData = 'entrance=2&operator=test&client_ip=127.0.0.1';
        $sensitiveData .= '&run_php=WithdrawFunctionsTest.php&operator_id=9&vendor=acc';

        $headers = ['HTTP_SENSITIVE_DATA' => $sensitiveData];
        $client->request('PUT', '/api/cash/withdraw/8/memo', ['memo' => 'testmemo'], [], $headers);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150240007', $output['code']);
        $this->assertEquals('The request not allowed when operator is not in same domain', $output['msg']);
    }

    /**
     * 測試鎖定現金出款找不到出款明細
     */
    public function testWithdrawLockWithoutWithdrawEntry()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/cash/withdraw/999/lock', [], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(380001, $output['code']);
        $this->assertEquals('No such withdraw entry', $output['msg']);
    }

    /**
     * 測試鎖定現金出款沒帶入操作者
     */
    public function testWithdrawLockWithoutOperator()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $entry = $em->getRepository('BBDurianBundle:CashWithdrawEntry')
            ->findOneBy(['id' => 8]);
        $entry->setStatus(CashWithdrawEntry::UNTREATED);
        $em->flush();

        $client->request('PUT', '/api/cash/withdraw/8/lock', [], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(380013, $output['code']);
        $this->assertEquals('No operator specified', $output['msg']);
    }

    /**
     * 測試鎖定現金出款但操作者與帶入的為不同廳
     */
    public function testWithdrawLockWithOperationIsNotInSameDomain()
    {
        $client = $this->createClient();

        $sensitiveData = 'entrance=2&operator=test&client_ip=127.0.0.1';
        $sensitiveData .= '&run_php=WithdrawFunctionsTest.php&operator_id=9&vendor=acc';

        $headers = ['HTTP_SENSITIVE_DATA' => $sensitiveData];
        $client->request('PUT', '/api/cash/withdraw/7/lock', ['operator' => 'test123'], [], $headers);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150240007', $output['code']);
        $this->assertEquals('The request not allowed when operator is not in same domain', $output['msg']);
    }

    /**
     * 測試鎖定現金出款資料
     */
    public function testWithdrawLock()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $entry = $em->getRepository('BBDurianBundle:CashWithdrawEntry')
            ->findOneBy(['id' => 7]);
        $entry->setStatus(CashWithdrawEntry::UNTREATED);
        $em->flush();

        $client->request('PUT', '/api/cash/withdraw/7/lock', ['operator' => 'test123'], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(7, $output['ret'][0]['id']);
        $this->assertEquals(CashWithdrawEntry::LOCK, $output['ret'][0]['status']);
        $this->assertEquals(8, $output['ret'][1]['id']);
        $this->assertEquals(CashWithdrawEntry::LOCK, $output['ret'][1]['status']);

        // 驗證出款操作者明細寫入
        $welEntry = $em->getRepository('BBDurianBundle:WithdrawEntryLock')
            ->findOneBy(['entryId' => $output['ret'][0]['id']]);
        $this->assertEquals($welEntry->getOperator(), 'test123');

        $welEntry = $em->getRepository('BBDurianBundle:WithdrawEntryLock')
            ->findOneBy(['entryId' => $output['ret'][1]['id']]);
        $this->assertEquals($welEntry->getOperator(), 'test123');

        // 驗證operationLog
        $operationlog = $emShare->find('BBDurianBundle:LogOperation', 1);
        $idLog = sprintf('@id:%s', $output['ret'][0]['id']);
        $msg = sprintf('@status:%s=>%s', CashWithdrawEntry::UNTREATED, CashWithdrawEntry::LOCK);

        $this->assertEquals($operationlog->getTableName(), 'cash_withdraw_entry');
        $this->assertEquals($operationlog->getMajorKey(), $idLog);
        $this->assertEquals($operationlog->getMessage(), $msg);

        $operationlog = $emShare->find('BBDurianBundle:LogOperation', 2);
        $idLog = sprintf('@id:%s', $output['ret'][1]['id']);
        $msg = sprintf('@status:%s=>%s', CashWithdrawEntry::UNTREATED, CashWithdrawEntry::LOCK);

        $this->assertEquals($operationlog->getTableName(), 'cash_withdraw_entry');
        $this->assertEquals($operationlog->getMajorKey(), $idLog);
        $this->assertEquals($operationlog->getMessage(), $msg);

        // 測試第二次設定處理時出現狀態已被設定
        $client->request('PUT', '/api/cash/withdraw/7/lock', ['operator' => 'test321'], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(380004, $output['code']);
        $this->assertEquals('Withdraw already check status', $output['msg']);

        // 確認紀錄敏感資訊log
        $logPath = $this->getLogfilePath('sensitive.log');
        $this->assertFileExists($logPath);
        $results = explode(PHP_EOL, file_get_contents($logPath));
        $line = "sensitive-data:$this->sensitiveData";
        $this->assertTrue(strpos($results[0], $line) !== false);
    }

    /**
     * 測試取消鎖定現金出款找不到出款明細
     */
    public function testWithdrawUnlockWithoutWithdrawEntry()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/cash/withdraw/999/unlock', [], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(380001, $output['code']);
        $this->assertEquals('No such withdraw entry', $output['msg']);
    }

    /**
     * 測試取消鎖定現金出款沒帶入操作者
     */
    public function testWithdrawUnlockWithoutOperator()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $client->request('PUT', '/api/cash/withdraw/8/lock', ['operator' => 'test123'], [], $this->headerParam);

        // 執行取消處理出款資料
        $client->request('PUT', '/api/cash/withdraw/8/unlock');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(380013, $output['code']);
        $this->assertEquals('No operator specified', $output['msg']);
    }

    /**
     * 測試取消處理現金出款操作者不相符
     */
    public function testWithdrawUnlockWithInvalidOperator()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 鎖定出款資料
        $client->request('PUT', '/api/cash/withdraw/8/lock', ['operator' => 'test123'], [], $this->headerParam);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret'][0]['id']);
        $this->assertEquals(CashWithdrawEntry::LOCK, $output['ret'][0]['status']);

        // 執行取消鎖定出款資料
        $client->request('PUT', '/api/cash/withdraw/8/unlock', ['operator' => 'test321'], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(380015, $output['code']);
        $this->assertEquals('Invalid operator', $output['msg']);
    }

    /**
     * 測試取消鎖定現金出款但操作者與帶入的為不同廳
     */
    public function testWithdrawUnlockWithOperationIsNotInSameDomain()
    {
        $client = $this->createClient();

        $sensitiveData = 'entrance=2&operator=test&client_ip=127.0.0.1';
        $sensitiveData .= '&run_php=WithdrawFunctionsTest.php&operator_id=9&vendor=acc';

        // 鎖定出款資料
        $client->request('PUT', '/api/cash/withdraw/8/lock', ['operator' => 'test123'], [], $this->headerParam);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret'][0]['id']);
        $this->assertEquals(CashWithdrawEntry::LOCK, $output['ret'][0]['status']);

        $headers = ['HTTP_SENSITIVE_DATA' => $sensitiveData];
        $client->request('PUT', '/api/cash/withdraw/8/unlock', ['operator' => 'test123'], [], $headers);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150240007', $output['code']);
        $this->assertEquals('The request not allowed when operator is not in same domain', $output['msg']);
    }

    /**
     * 測試強制解除鎖定時不會檢查操作者
     */
    public function testWithdrawUnlockWithForce()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 鎖定出款資料
        $client->request('PUT', '/api/cash/withdraw/8/lock', ['operator' => 'test123'], [], $this->headerParam);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret'][0]['id']);
        $this->assertEquals(CashWithdrawEntry::LOCK, $output['ret'][0]['status']);

        $params = ['force' => 1];

        // 執行取消處理出款資料
        $client->request('PUT', '/api/cash/withdraw/8/unlock', $params, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['id']);
        $this->assertEquals(CashWithdrawEntry::UNTREATED, $output['ret']['status']);
    }

    /**
     * 測試取消鎖定現金出款資料
     */
    public function testWithdrawUnlock()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        // 鎖定出款資料
        $client->request('PUT', '/api/cash/withdraw/8/lock', ['operator' => 'test123'], [], $this->headerParam);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret'][0]['id']);
        $this->assertEquals(CashWithdrawEntry::LOCK, $output['ret'][0]['status']);

        // 驗證出款操作者明細寫入
        $welEntry = $em->getRepository('BBDurianBundle:WithdrawEntryLock')
            ->findOneBy(['entryId' => $output['ret'][0]['id']]);
        $this->assertEquals($welEntry->getOperator(), 'test123');

        // 執行取消處理出款資料
        $client->request('PUT', '/api/cash/withdraw/8/unlock', ['operator' => 'test123'], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['id']);
        $this->assertEquals(CashWithdrawEntry::UNTREATED, $output['ret']['status']);

        // 驗證出款操作者明細已刪除
        $welEntry = $em->getRepository('BBDurianBundle:WithdrawEntryLock')
            ->findOneBy(['entryId' => $output['ret']['id']]);
        $this->assertNull($welEntry);

        // 驗證operationLog
        $operationlog = $emShare->find('BBDurianBundle:LogOperation', 2);
        $idLog = sprintf('@id:%s', $output['ret']['id']);
        $msg = sprintf('@status:%s=>%s', CashWithdrawEntry::LOCK, CashWithdrawEntry::UNTREATED);

        $this->assertEquals($operationlog->getTableName(), 'cash_withdraw_entry');
        $this->assertEquals($operationlog->getMajorKey(), $idLog);
        $this->assertEquals($operationlog->getMessage(), $msg);

        // 測試第二次設定取消處理時出現出款狀態非處理中
        $client->request('PUT', '/api/cash/withdraw/8/unlock', ['operator' => 'test321'], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(380014, $output['code']);
        $this->assertEquals('Withdraw status not lock', $output['msg']);

        // 確認紀錄敏感資訊log
        $logPath = $this->getLogfilePath('sensitive.log');
        $this->assertFileExists($logPath);
        $results = explode(PHP_EOL, file_get_contents($logPath));
        $line = "sensitive-data:$this->sensitiveData";
        $this->assertTrue(strpos($results[0], $line) !== false);
    }

    /**
     * 測試不同操作者鎖定出款單
     */
    public function testWithdrawLockWithDifferentOperator()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 鎖定出款資料
        $client->request('PUT', '/api/cash/withdraw/8/lock', ['operator' => 'test123'], [], $this->headerParam);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret'][0]['id']);
        $this->assertEquals(CashWithdrawEntry::LOCK, $output['ret'][0]['status']);

        // 新增一筆出款單
        $parameters = [
            'bank_id' => 4,
            'amount' => -50,
            'fee' => -1,
            'deduction' => -1,
            'memo' => 'test',
            'ip' => '127.0.0.1'
        ];

        $client->request('POST', '/api/user/8/cash/withdraw', $parameters, [], $this->headerParam);

        // 鎖定出款資料
        $client->request('PUT', '/api/cash/withdraw/11/lock', ['operator' => '123test'], [], $this->headerParam);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(11, $output['ret'][0]['id']);
        $this->assertEquals(CashWithdrawEntry::LOCK, $output['ret'][0]['status']);

        // 檢查不同出款鎖定資料
        $welEntry = $em->getRepository('BBDurianBundle:WithdrawEntryLock')
            ->findOneBy(['entryId' => 8]);
        $this->assertEquals($welEntry->getOperator(), 'test123');

        $welEntry = $em->getRepository('BBDurianBundle:WithdrawEntryLock')
            ->findOneBy(['entryId' => 11]);
        $this->assertEquals($welEntry->getOperator(), '123test');

        // 取消鎖定其中一筆出款資料
        $client->request('PUT', '/api/cash/withdraw/8/unlock', ['operator' => 'test123'], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['id']);
        $this->assertEquals(CashWithdrawEntry::UNTREATED, $output['ret']['status']);

        // 檢查另一筆出款鎖定資料存在
        $welEntry = $em->getRepository('BBDurianBundle:WithdrawEntryLock')
            ->findOneBy(['entryId' => 11]);
        $this->assertEquals($welEntry->getOperator(), '123test');
    }

    /**
     * 測試使用id回傳現金出款紀錄
     */
    public function testGetWithdrawEntry()
    {
        $client = $this->createClient();

        $parameters = ['sub_ret' => 1];

        $client->request('GET', '/api/cash/withdraw/8', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(-400, $ret['ret']['amount']);
        $this->assertEquals(-10, $ret['ret']['fee']);
        $this->assertEquals(-20, $ret['ret']['deduction']);
        $this->assertEquals(-370, $ret['ret']['real_amount']);
        $this->assertEquals('test', $ret['ret']['memo']);
        $this->assertEquals(0, $ret['ret']['previous_id']);
        $this->assertFalse($ret['ret']['detail_modified']);
        $this->assertEquals([7, 6, 5, 4, 3, 2], $ret['sub_ret']['user']['all_parents']);

        // 確認紀錄敏感資訊log
        $logPath = $this->getLogfilePath('sensitive.log');
        $this->assertFileExists($logPath);
        $results = explode(PHP_EOL, file_get_contents($logPath));
        $line = "sensitive-data:$this->sensitiveData";
        $this->assertTrue(strpos($results[0], $line) !== false);
    }

    /**
     * 測試使用id回傳現金出款紀錄已鎖定的資料會回傳鎖定操作者資料
     */
    public function testGetWithdrawEntryWithWithdrawEntryLock()
    {
        $client = $this->createClient();

        // 鎖定出款資料
        $client->request('PUT', '/api/cash/withdraw/8/lock', ['operator' => 'test123'], [], $this->headerParam);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret'][0]['id']);
        $this->assertEquals(CashWithdrawEntry::LOCK, $output['ret'][0]['status']);

        $parameters = ['sub_ret' => 1];

        $client->request('GET', '/api/cash/withdraw/8', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(-400, $ret['ret']['amount']);
        $this->assertEquals(-10, $ret['ret']['fee']);
        $this->assertEquals(-20, $ret['ret']['deduction']);
        $this->assertEquals(-370, $ret['ret']['real_amount']);
        $this->assertEquals('test', $ret['ret']['memo']);
        $this->assertEquals(0, $ret['ret']['previous_id']);
        $this->assertFalse($ret['ret']['detail_modified']);
        $this->assertEquals([7, 6, 5, 4, 3, 2], $ret['sub_ret']['user']['all_parents']);
        $this->assertEquals('8', $ret['sub_ret']['withdraw_entry_lock']['entry_id']);
        $this->assertEquals('8', $ret['sub_ret']['withdraw_entry_lock']['user_id']);
        $this->assertEquals('TWD', $ret['sub_ret']['withdraw_entry_lock']['currency']);
        $this->assertEquals('test123', $ret['sub_ret']['withdraw_entry_lock']['operator']);
    }

    /**
     * 測試使用id回傳現金出款紀錄,帶入錯誤的withdraw entry id
     */
    public function testGetWithdrawEntryWithErrorEntryId()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/cash/withdraw/199', [], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(380001, $output['code']);
        $this->assertEquals('No such withdraw entry', $output['msg']);
    }

    /**
     * 測試使用id回傳現金出款紀錄但操作者與帶入的為不同廳
     */
    public function testGetWithdrawEntryWithOperationIsNotInSameDomain()
    {
        $client = $this->createClient();

        $sensitiveData = 'entrance=2&operator=test&client_ip=127.0.0.1';
        $sensitiveData .= '&run_php=WithdrawFunctionsTest.php&operator_id=9&vendor=acc';

        $headers = ['HTTP_SENSITIVE_DATA' => $sensitiveData];
        $client->request('GET', '/api/cash/withdraw/8', [], [], $headers);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150240007', $output['code']);
        $this->assertEquals('The request not allowed when operator is not in same domain', $output['msg']);
    }

    /**
     * 測試使用id回傳現金出款紀錄,找不到user的cash資料
     */
    public function testGetWithdrawEntryWithNoCashUser()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $cash6 = $em->find('BBDurianBundle:Cash', 6);
        $em->remove($cash6);
        $em->flush();

        $parameters = ['sub_ret' => true];
        $client->request('GET', '/api/cash/withdraw/1', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(380023, $output['code']);
        $this->assertEquals('No cash found', $output['msg']);
    }

    /**
     * 測試回傳現金出款紀錄
     */
    public function testGetWithdrawEntries()
    {
        $client = $this->createClient();

        $parameters = ['sub_ret' => 1];

        $client->request('GET', '/api/user/8/cash/withdraw', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(-100, $ret['ret'][0]['amount']);
        $this->assertEquals(-10, $ret['ret'][0]['fee']);
        $this->assertEquals(-20, $ret['ret'][0]['deduction']);
        $this->assertEquals(-70, $ret['ret'][0]['real_amount']);
        $this->assertEquals('', $ret['ret'][0]['memo']);
        $this->assertEquals(-50, $ret['ret'][0]['amount_conv']);
        $this->assertEquals(-5, $ret['ret'][0]['fee_conv']);
        $this->assertEquals(-10, $ret['ret'][0]['deduction_conv']);
        $this->assertEquals(-35, $ret['ret'][0]['real_amount_conv']);
        $this->assertEquals('2012-07-19T05:00:00+0800', $ret['ret'][0]['confirm_at']);
        $this->assertEquals('2010-07-19T05:00:00+0800', $ret['ret'][0]['at']);
        $this->assertEquals('2010-07-19T05:00:00+0800', $ret['ret'][0]['created_at']);
        $this->assertEquals(-200, $ret['ret'][1]['amount']);
        $this->assertEquals(-10, $ret['ret'][1]['fee']);
        $this->assertEquals(-20, $ret['ret'][1]['deduction']);
        $this->assertEquals(-170, $ret['ret'][1]['real_amount']);
        $this->assertEquals(-100, $ret['ret'][1]['amount_conv']);
        $this->assertEquals(-5, $ret['ret'][1]['fee_conv']);
        $this->assertEquals(-10, $ret['ret'][1]['deduction_conv']);
        $this->assertEquals(-85, $ret['ret'][1]['real_amount_conv']);
        $this->assertEquals('2012-07-20T05:00:00+0800', $ret['ret'][1]['confirm_at']);
        $this->assertEquals('2010-07-20T05:00:00+0800', $ret['ret'][1]['at']);
        $this->assertEquals('2010-07-20T05:00:00+0800', $ret['ret'][1]['created_at']);

        $this->assertEquals(-400, $ret['ret'][3]['amount']);
        $this->assertEquals(-10, $ret['ret'][3]['fee']);
        $this->assertEquals(-20, $ret['ret'][3]['deduction']);
        $this->assertEquals(-370, $ret['ret'][3]['real_amount']);
        $this->assertEquals('test', $ret['ret'][3]['memo']);
        $this->assertEquals([7, 6, 5, 4, 3, 2], $ret['sub_ret']['user']['all_parents']);
        $this->assertEquals(4, $ret['pagination']['total']);

        $parameters = ['status' => 1];

        $client->request('GET', '/api/user/8/cash/withdraw', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(3, $ret['pagination']['total']);

        $parameters = [
            'status' => [0, 2],
            'order' => 'asc',
            'sort' => 'id'
        ];

        $client->request('GET', '/api/user/8/cash/withdraw', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(-400, $ret['ret'][0]['amount']);
        $this->assertEquals(-10, $ret['ret'][0]['fee']);
        $this->assertEquals(-20, $ret['ret'][0]['deduction']);
        $this->assertEquals(0, $ret['ret'][0]['status']);
        $this->assertEquals(-370, $ret['ret'][0]['real_amount']);
        $this->assertEquals('test', $ret['ret'][0]['memo']);
        $this->assertEquals(-200, $ret['ret'][0]['amount_conv']);
        $this->assertEquals(-5, $ret['ret'][0]['fee_conv']);
        $this->assertEquals(-10, $ret['ret'][0]['deduction_conv']);
        $this->assertEquals(-185, $ret['ret'][0]['real_amount_conv']);
        $this->assertEquals(0, $ret['ret'][0]['previous_id']);
        $this->assertFalse($ret['ret'][0]['detail_modified']);

        $this->assertEquals(1, $ret['pagination']['total']);

        $parameters = ['sub_total' => 1];

        $client->request('GET', '/api/user/8/cash/withdraw', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals(4, $ret['pagination']['total']);
        $this->assertEquals(-1000, $ret['sub_total']['amount']);
        $this->assertEquals(-40, $ret['sub_total']['fee']);
        $this->assertEquals(0, $ret['sub_total']['aduit_charge']);
        $this->assertEquals(-80, $ret['sub_total']['deduction']);
        $this->assertEquals(-880, $ret['sub_total']['real_amount']);
        $this->assertEquals(-1000, $ret['total']['amount']);
        $this->assertEquals(-40, $ret['total']['fee']);
        $this->assertEquals(0, $ret['total']['aduit_charge']);
        $this->assertEquals(-80, $ret['total']['deduction']);
        $this->assertEquals(-880, $ret['total']['real_amount']);

        $parameters = [
            'sub_total' => 1,
            'first_result' => 0,
            'max_results' => 3
        ];

        $client->request('GET', '/api/user/8/cash/withdraw', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(-600, $ret['sub_total']['amount']);
        $this->assertEquals(-30, $ret['sub_total']['fee']);
        $this->assertEquals(0, $ret['sub_total']['aduit_charge']);
        $this->assertEquals(-60, $ret['sub_total']['deduction']);
        $this->assertEquals(-510, $ret['sub_total']['real_amount']);
        $this->assertEquals(-1000, $ret['total']['amount']);
        $this->assertEquals(-40, $ret['total']['fee']);
        $this->assertEquals(0, $ret['total']['aduit_charge']);
        $this->assertEquals(-80, $ret['total']['deduction']);
        $this->assertEquals(-880, $ret['total']['real_amount']);
    }

    /**
     * 測試回傳現金出款紀錄帶入是否為自動出款條件
     */
    public function testGetWithdrawEntriesByAutoWithdraw()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $entry = $em->getRepository('BBDurianBundle:CashWithdrawEntry')->findOneBy(['id' => 2]);
        $entry->setAutoWithdraw(true);
        $em->flush();

        $parameters = [
            'start' => '2010-07-15T00:00:00+0800',
            'end' => '2010-07-25T00:00:00+0800',
            'auto_withdraw' => '1'
        ];

        $client->request('GET', '/api/user/7/cash/withdraw', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret'][0]['id']);
        $this->assertEquals(7, $ret['ret'][0]['user_id']);
        $this->assertEquals('2012-07-20T05:00:00+0800', $ret['ret'][0]['confirm_at']);

        $this->assertEquals(1, count($ret['ret']));
    }

    /**
     * 測試回傳現金出款紀錄帶入出款商家ID條件
     */
    public function testGetWithdrawEntriesByMerchantWithdrawId()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $entry = $em->getRepository('BBDurianBundle:CashWithdrawEntry')->findOneBy(['id' => 2]);
        $entry->setMerchantWithdrawId(123);
        $em->flush();

        $parameters = [
            'start' => '2010-07-15T00:00:00+0800',
            'end' => '2010-07-25T00:00:00+0800',
            'merchant_withdraw_id' => '123'
        ];

        $client->request('GET', '/api/user/7/cash/withdraw', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret'][0]['id']);
        $this->assertEquals(7, $ret['ret'][0]['user_id']);
        $this->assertEquals('2012-07-20T05:00:00+0800', $ret['ret'][0]['confirm_at']);

        $this->assertEquals(1, count($ret['ret']));
    }

    /**
     * 測試取得現金出款明細依照時間排序
     */
    public function testGetWithdrawEntriesOrderByDate()
    {
        $client = $this->createClient();

        // 測試取得取得現金出款明細依照 at 欄位排序
        $parameters = [
            'start' => '2010-07-20T00:00:00+0800',
            'end' => '2010-07-21T12:00:00+0800',
            'sort' => 'at',
            'order' => 'desc'
        ];

        $client->request('GET', '/api/user/8/cash/withdraw', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret'][0]['id']);
        $this->assertEquals(8, $ret['ret'][0]['user_id']);
        $this->assertEquals('2012-07-21T05:00:00+0800', $ret['ret'][0]['confirm_at']);
        $this->assertEquals(6, $ret['ret'][1]['id']);
        $this->assertEquals(8, $ret['ret'][1]['user_id']);
        $this->assertEquals('2012-07-20T05:00:00+0800', $ret['ret'][1]['confirm_at']);

        $this->assertEquals(2, count($ret['ret']));

        // 測試取得有確認出款明細依照 created_at 欄位排序
        $parameters = [
            'start' => '2010-07-20T00:00:00+0800',
            'end' => '2010-07-21T12:00:00+0800',
            'sort' => 'created_at',
            'order' => 'asc'
        ];

        $client->request('GET', '/api/user/8/cash/withdraw', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals(6, $ret['ret'][0]['id']);
        $this->assertEquals(8, $ret['ret'][0]['user_id']);
        $this->assertEquals('2012-07-20T05:00:00+0800', $ret['ret'][0]['confirm_at']);
        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret'][1]['id']);
        $this->assertEquals(8, $ret['ret'][1]['user_id']);
        $this->assertEquals('2012-07-21T05:00:00+0800', $ret['ret'][1]['confirm_at']);

        $this->assertEquals(2, count($ret['ret']));
    }

    /**
     * 測試取得現金出款記錄,找不到user的cash資訊
     */
    public function testGetWithdrawEntriesWithNoCashUser()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/10/cash/withdraw', [], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(380023, $output['code']);
        $this->assertEquals('No cash found', $output['msg']);
    }

    /**
     * 測試取得現金出款記錄但操作者與帶入的為不同廳
     */
    public function testGetWithdrawEntriesWithOperationIsNotInSameDomain()
    {
        $client = $this->createClient();

        $sensitiveData = 'entrance=2&operator=test&client_ip=127.0.0.1';
        $sensitiveData .= '&run_php=WithdrawFunctionsTest.php&operator_id=9&vendor=acc';

        $headers = ['HTTP_SENSITIVE_DATA' => $sensitiveData];
        $client->request('GET', '/api/user/8/cash/withdraw', [], [], $headers);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150240007', $output['code']);
        $this->assertEquals('The request not allowed when operator is not in same domain', $output['msg']);
    }

    /**
     * 測試回傳現金出款紀錄紀錄
     */
    public function testGetWithdrawEntriesList()
    {
        $client = $this->createClient();

        $parameters = ['domain' => 2];

        $client->request('GET', '/api/cash/withdraw/list', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals('TWD', $ret['ret'][7]['currency']);
        $this->assertEquals(-400, $ret['ret'][7]['amount']);
        $this->assertEquals(-10, $ret['ret'][7]['fee']);
        $this->assertEquals(-20, $ret['ret'][7]['deduction']);
        $this->assertEquals(-370, $ret['ret'][7]['real_amount']);
        $this->assertEquals('test', $ret['ret'][7]['memo']);
        $this->assertEquals(0, $ret['ret'][7]['previous_id']);
        $this->assertFalse($ret['ret'][7]['detail_modified']);
        $this->assertEquals(380021, $ret['ret'][7]['error_code']);
        $this->assertEquals('无此银行币别资讯', $ret['ret'][7]['error_message']);
        $this->assertEquals(9, $ret['pagination']['total']);

        //測試搜尋幣別帶入小計資料
        $parameters = [
            'domain'    => 2,
            'currency'  => 'TWD',
            'sub_ret'   => 1,
            'sub_total' => 1
        ];

        $client->request('GET', '/api/cash/withdraw/list', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals('TWD', $ret['ret'][7]['currency']);
        $this->assertEquals(-400, $ret['ret'][7]['amount']);
        $this->assertEquals(-10, $ret['ret'][7]['fee']);
        $this->assertEquals(-20, $ret['ret'][7]['deduction']);
        $this->assertEquals(-370, $ret['ret'][7]['real_amount']);
        $this->assertEquals('test', $ret['ret'][7]['memo']);
        $this->assertEquals(-2100.0000, $ret['sub_total']['amount']);
        $this->assertEquals(-80.0000, $ret['sub_total']['fee']);
        $this->assertEquals(0, $ret['sub_total']['aduit_fee']);
        $this->assertEquals(0, $ret['sub_total']['aduit_charge']);
        $this->assertEquals(-160.0000, $ret['sub_total']['deduction']);
        $this->assertEquals(-1860.0000, $ret['sub_total']['real_amount']);
        $this->assertEquals(-1860.0000, $ret['sub_total']['auto_withdraw_amount']);
        $this->assertEquals(0, $ret['sub_total']['payment_gateway_fee']);
        $this->assertEquals(-2100.0000, $ret['total']['amount']);
        $this->assertEquals(-80.0000, $ret['total']['fee']);
        $this->assertEquals(0, $ret['total']['aduit_fee']);
        $this->assertEquals(0, $ret['total']['aduit_charge']);
        $this->assertEquals(-160.0000, $ret['total']['deduction']);
        $this->assertEquals(-1860.0000, $ret['total']['real_amount']);
        $this->assertEquals(-1860.0000, $ret['total']['auto_withdraw_amount']);
        $this->assertEquals(0, $ret['total']['payment_gateway_fee']);
        $this->assertEquals(3, $ret['total']['user_count']);
        $this->assertEquals(2, $ret['total']['deduction_user_count']);
        $this->assertEquals(9, $ret['pagination']['total']);

        $parameters = [
            'domain'    => 2,
            'parent_id' => 6
        ];

        $client->request('GET', '/api/cash/withdraw/list', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals('TWD', $ret['ret'][7]['currency']);
        $this->assertEquals(-400, $ret['ret'][7]['amount']);
        $this->assertEquals(-10, $ret['ret'][7]['fee']);
        $this->assertEquals(-20, $ret['ret'][7]['deduction']);
        $this->assertEquals(-370, $ret['ret'][7]['real_amount']);
        $this->assertEquals('test', $ret['ret'][7]['memo']);
        $this->assertEquals(8, $ret['pagination']['total']);

        $parameters = [
            'domain'    => 2,
            'parent_id' => 6,
            'status'    => 1
        ];

        $client->request('GET', '/api/cash/withdraw/list', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals('TWD', $ret['ret'][6]['currency']);
        $this->assertEquals(-400, $ret['ret'][6]['amount']);
        $this->assertEquals(-10, $ret['ret'][6]['fee']);
        $this->assertEquals(-20, $ret['ret'][6]['deduction']);
        $this->assertEquals(-370, $ret['ret'][6]['real_amount']);
        $this->assertEquals(1, $ret['ret'][6]['status']);
        $this->assertEquals('', $ret['ret'][6]['memo']);
        $this->assertEquals(7, $ret['pagination']['total']);

        $parameters = [
            'domain'    => 2,
            'parent_id' => 6,
            'status'    => [0, 2],
            'memo'      =>'test'
        ];

        $client->request('GET', '/api/cash/withdraw/list', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(-400, $ret['ret'][0]['amount']);
        $this->assertEquals(-10, $ret['ret'][0]['fee']);
        $this->assertEquals(-20, $ret['ret'][0]['deduction']);
        $this->assertEquals(-370, $ret['ret'][0]['real_amount']);
        $this->assertEquals(0, $ret['ret'][0]['status']);
        $this->assertEquals('test', $ret['ret'][0]['memo']);
        $this->assertEquals(1, $ret['pagination']['total']);

        $parameters = [
            'domain' => 2,
            'level_id' => [1, 2],
            'sort'   => ['id'],
            'order'  => ['asc']
        ];

        $client->request('GET', '/api/cash/withdraw/list', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals('TWD', $ret['ret'][2]['currency']);
        $this->assertEquals(-300, $ret['ret'][2]['amount']);
        $this->assertEquals(-10, $ret['ret'][7]['fee']);
        $this->assertEquals(-20, $ret['ret'][7]['deduction']);
        $this->assertEquals(-370, $ret['ret'][7]['real_amount']);
        $this->assertEquals(2, $ret['ret'][7]['level_id']);
        $this->assertEquals('test', $ret['ret'][7]['memo']);
        $this->assertEquals(9, $ret['pagination']['total']);

        $parameters = [
            'domain' => 2,
            'level_id' => 1
        ];

        $client->request('GET', '/api/cash/withdraw/list', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals('TWD', $ret['ret'][1]['currency']);
        $this->assertEquals(-100, $ret['ret'][1]['amount']);
        $this->assertEquals(-10, $ret['ret'][6]['fee']);
        $this->assertEquals(-20, $ret['ret'][6]['deduction']);
        $this->assertEquals(-370, $ret['ret'][6]['real_amount']);
        $this->assertEquals(1, $ret['ret'][6]['level_id']);
        $this->assertEquals(8, $ret['pagination']['total']);

        $parameters = [
            'domain'    => 2,
            'parent_id' => 11
        ];

        $client->request('GET', '/api/cash/withdraw/list', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals('No such user', $ret['msg']);
        $this->assertEquals(380026, $ret['code']);

        // 測試傳回在某個時間區間內有欄位有確認的出款明細的列表
        $parameters = [
            'domain' => 2,
            'parent_id' => 6,
            'confirm_at_start' => '2012-07-20T00:00:00+0800',
            'confirm_at_end' => '2012-07-21T12:00:00+0800',
            'sub_ret' => 1
        ];

        $client->request('GET', '/api/cash/withdraw/list', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(-200, $ret['ret'][0]['amount']);
        $this->assertEquals(-10, $ret['ret'][0]['fee']);
        $this->assertEquals(-20, $ret['ret'][0]['deduction']);
        $this->assertEquals(-170, $ret['ret'][0]['real_amount']);
        $this->assertEquals('2012-07-20T05:00:00+0800', $ret['ret'][0]['confirm_at']);
        $this->assertEquals('TWD', $ret['ret'][3]['currency']);
        $this->assertEquals(-300, $ret['ret'][3]['amount']);
        $this->assertEquals(-10, $ret['ret'][3]['fee']);
        $this->assertEquals(-20, $ret['ret'][3]['deduction']);
        $this->assertEquals(-270, $ret['ret'][3]['real_amount']);
        $this->assertEquals('2012-07-21T05:00:00+0800', $ret['ret'][3]['confirm_at']);
        $this->assertEquals([6, 5, 4, 3, 2], $ret['sub_ret']['user'][0]['all_parents']);

        $this->assertEquals(4, $ret['pagination']['total']);

        $parameters = [
            'domain'    => 2,
            'sub_total' => 1
        ];

        $client->request('GET', '/api/cash/withdraw/list', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals(9, $ret['pagination']['total']);
        $this->assertEquals(-1030, $ret['sub_total']['amount']);
        $this->assertEquals(-39, $ret['sub_total']['fee']);
        $this->assertEquals(0, $ret['sub_total']['aduit_fee']);
        $this->assertEquals(0, $ret['sub_total']['aduit_charge']);
        $this->assertEquals(-78, $ret['sub_total']['deduction']);
        $this->assertEquals(-913, $ret['sub_total']['real_amount']);
        $this->assertEquals(-913, $ret['sub_total']['auto_withdraw_amount']);
        $this->assertEquals(0, $ret['sub_total']['payment_gateway_fee']);
        $this->assertEquals(3, $ret['total']['user_count']);
        $this->assertEquals(2, $ret['total']['deduction_user_count']);
        $this->assertEquals(-1030, $ret['total']['amount']);
        $this->assertEquals(-39, $ret['total']['fee']);
        $this->assertEquals(0, $ret['total']['aduit_fee']);
        $this->assertEquals(0, $ret['total']['aduit_charge']);
        $this->assertEquals(-78, $ret['total']['deduction']);
        $this->assertEquals(-913, $ret['total']['real_amount']);
        $this->assertEquals(-913, $ret['total']['auto_withdraw_amount']);
        $this->assertEquals(0, $ret['total']['payment_gateway_fee']);

        $parameters = [
            'domain'       => 2,
            'sub_total'    => 1,
            'first_result' => 0,
            'max_results'  => 3,
            'sort'         => ['id'],
            'order'        => ['asc']
        ];

        $client->request('GET', '/api/cash/withdraw/list', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals(9, $ret['pagination']['total']);
        $this->assertEquals(-280.00, $ret['sub_total']['amount']);
        $this->assertEquals(-14.00, $ret['sub_total']['fee']);
        $this->assertEquals(0, $ret['sub_total']['aduit_fee']);
        $this->assertEquals(0, $ret['sub_total']['aduit_charge']);
        $this->assertEquals(-28, $ret['sub_total']['deduction']);
        $this->assertEquals(-238, $ret['sub_total']['real_amount']);
        $this->assertEquals(-238, $ret['sub_total']['auto_withdraw_amount']);
        $this->assertEquals(0, $ret['sub_total']['payment_gateway_fee']);
        $this->assertEquals(-1030, $ret['total']['amount']);
        $this->assertEquals(-39, $ret['total']['fee']);
        $this->assertEquals(0, $ret['total']['aduit_fee']);
        $this->assertEquals(0, $ret['total']['aduit_charge']);
        $this->assertEquals(-78, $ret['total']['deduction']);
        $this->assertEquals(-913, $ret['total']['real_amount']);
        $this->assertEquals(-913, $ret['total']['auto_withdraw_amount']);
        $this->assertEquals(0, $ret['total']['payment_gateway_fee']);
        $this->assertEquals(3, $ret['total']['user_count']);
        $this->assertEquals(2, $ret['total']['deduction_user_count']);

        //測試帶入參數exclude_zero排除欄位值為0
        $parameters = [
            'domain'       => 2,
            'exclude_zero' => ['fee']
        ];

        $client->request('GET', '/api/cash/withdraw/list', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(8, $ret['pagination']['total']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(-100, $ret['ret'][0]['amount']);
        $this->assertEquals(-10, $ret['ret'][0]['fee']);
        $this->assertEquals(-20, $ret['ret'][0]['deduction']);
        $this->assertEquals(-70, $ret['ret'][0]['real_amount']);
        $this->assertEquals(0, $ret['ret'][0]['aduit_fee']);
        $this->assertEquals(0, $ret['ret'][0]['aduit_charge']);
        $this->assertEquals(1, $ret['ret'][0]['level_id']);
        $this->assertEquals(-400, $ret['ret'][7]['amount']);
        $this->assertEquals(-10, $ret['ret'][7]['fee']);
        $this->assertEquals(-20, $ret['ret'][7]['deduction']);
        $this->assertEquals(-370, $ret['ret'][7]['real_amount']);
        $this->assertEquals(0, $ret['ret'][7]['aduit_fee']);
        $this->assertEquals(0, $ret['ret'][7]['aduit_charge']);
        $this->assertEquals(2, $ret['ret'][7]['level_id']);

        $parameters = [
            'domain'       => 2,
            'exclude_zero' => ['aduit_fee']
        ];

        $client->request('GET', '/api/cash/withdraw/list', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(0, $ret['pagination']['total']);

        // 敏感資訊log
        $logPath = $this->getLogfilePath('sensitive.log');
        $this->assertFileExists($logPath);
        $results = explode(PHP_EOL, file_get_contents($logPath));
        $line = "sensitive-data:$this->sensitiveData";
        $this->assertTrue(strpos($results[0], $line) !== false);
    }

    /**
     * 測試回傳現金出款紀錄紀錄帶入狀態為0的情況
     */
    public function testGetWithdrawEntriesListWithStatus()
    {
        $client = $this->createClient();

        $parameters = [
            'domain' => 2,
            'status' => 0
        ];

        $client->request('GET', '/api/cash/withdraw/list', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(-400, $ret['ret'][0]['amount']);
        $this->assertEquals(-10, $ret['ret'][0]['fee']);
        $this->assertEquals(-20, $ret['ret'][0]['deduction']);
        $this->assertEquals(-370, $ret['ret'][0]['real_amount']);
        $this->assertEquals(0, $ret['ret'][0]['status']);
        $this->assertEquals('test', $ret['ret'][0]['memo']);
        $this->assertEquals(1, $ret['pagination']['total']);
    }

    /**
     * 測試回傳現金出款紀錄紀錄帶入金額區間
     */
    public function testGetWithdrawEntriesListWithAmount()
    {
        $client = $this->createClient();

        $parameters = [
            'domain' => 2,
            'amount_min' => '-500',
            'amount_max' => '-350',
            'sub_total' => 1
        ];

        $client->request('GET', '/api/cash/withdraw/list', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(4, $ret['ret'][0]['id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(-400, $ret['ret'][0]['amount']);
        $this->assertEquals(-10, $ret['ret'][0]['fee']);
        $this->assertEquals(-20, $ret['ret'][0]['deduction']);
        $this->assertEquals(-370, $ret['ret'][0]['real_amount']);
        $this->assertEquals(1, $ret['ret'][0]['status']);

        $this->assertEquals(8, $ret['ret'][1]['id']);
        $this->assertEquals('TWD', $ret['ret'][1]['currency']);
        $this->assertEquals(-400, $ret['ret'][1]['amount']);
        $this->assertEquals(-10, $ret['ret'][1]['fee']);
        $this->assertEquals(-20, $ret['ret'][1]['deduction']);
        $this->assertEquals(-370, $ret['ret'][1]['real_amount']);
        $this->assertEquals(0, $ret['ret'][1]['status']);
        $this->assertEquals('test', $ret['ret'][1]['memo']);

        $this->assertEquals(2, $ret['pagination']['total']);
        $this->assertEquals(-400.00, $ret['sub_total']['amount']);
        $this->assertEquals(-10.00, $ret['sub_total']['fee']);
        $this->assertEquals(0, $ret['sub_total']['aduit_fee']);
        $this->assertEquals(0, $ret['sub_total']['aduit_charge']);
        $this->assertEquals(-20, $ret['sub_total']['deduction']);
        $this->assertEquals(-370, $ret['sub_total']['real_amount']);
        $this->assertEquals(-370, $ret['sub_total']['auto_withdraw_amount']);
        $this->assertEquals(0, $ret['sub_total']['payment_gateway_fee']);
        $this->assertEquals(-10, $ret['total']['fee']);
        $this->assertEquals(0, $ret['total']['aduit_fee']);
        $this->assertEquals(0, $ret['total']['aduit_charge']);
        $this->assertEquals(-20, $ret['total']['deduction']);
        $this->assertEquals(-370, $ret['total']['real_amount']);
        $this->assertEquals(-370, $ret['total']['auto_withdraw_amount']);
        $this->assertEquals(0, $ret['total']['payment_gateway_fee']);
        $this->assertEquals(2, $ret['total']['user_count']);
        $this->assertEquals(2, $ret['total']['deduction_user_count']);
    }

    /**
     * 測試以createdAt及updateAt取下層出款明細列表
     */
    public function testGetWithdrawEntriesListByCreatedAtAndUpdateAt()
    {
        $client = $this->createClient();

        $parameters = [
            'domain' => 2,
            'created_at_start' => '2010-07-20T06:00:00+0800',
            'created_at_end' => '2010-07-21T12:00:00+0800',
            'confirm_at_start' => '2012-07-20T00:00:00+0800',
            'confirm_at_end' => '2012-07-21T12:00:00+0800'
        ];

        $client->request('GET', '/api/cash/withdraw/list', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(-300, $ret['ret'][0]['amount']);
        $this->assertEquals(-10, $ret['ret'][0]['fee']);
        $this->assertEquals(-20, $ret['ret'][0]['deduction']);
        $this->assertEquals(-270, $ret['ret'][0]['real_amount']);
        $this->assertEquals('2010-07-21T05:00:00+0800', $ret['ret'][0]['at']);
        $this->assertEquals('2012-07-21T05:00:00+0800', $ret['ret'][0]['confirm_at']);
        $this->assertEquals('TWD', $ret['ret'][1]['currency']);
        $this->assertEquals(-300, $ret['ret'][1]['amount']);
        $this->assertEquals(-10, $ret['ret'][1]['fee']);
        $this->assertEquals(-20, $ret['ret'][1]['deduction']);
        $this->assertEquals(-270, $ret['ret'][1]['real_amount']);
        $this->assertEquals('2010-07-21T05:00:00+0800', $ret['ret'][1]['at']);
        $this->assertEquals('2012-07-21T05:00:00+0800', $ret['ret'][1]['confirm_at']);

        $this->assertEquals(2, count($ret['ret']));
    }

    /**
     * 測試以domain及username取下層出款明細列表
     */
    public function testGetWithdrawEntriesListByDomainAndUsername()
    {
        $client = $this->createClient();

        $parameters = [
            'domain' => 2,
            'username' => 'tester'
        ];

        $client->request('GET', '/api/cash/withdraw/list', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(5, $output['ret'][0]['id']);
        $this->assertEquals(6, $output['ret'][1]['id']);
        $this->assertEquals(7, $output['ret'][2]['id']);
        $this->assertEquals(8, $output['ret'][3]['id']);
    }

    /**
     * 測試以domain及username取下層出款明細列表，但username含有空白
     */
    public function testGetWithdrawEntriesListByDomainAndUsernameAndUsernameContainsBlanks()
    {
        $client = $this->createClient();

        $parameters = [
            'domain' => 2,
            'username' => ' tester '
        ];

        $client->request('GET', '/api/cash/withdraw/list', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(5, $output['ret'][0]['id']);
        $this->assertEquals(6, $output['ret'][1]['id']);
        $this->assertEquals(7, $output['ret'][2]['id']);
        $this->assertEquals(8, $output['ret'][3]['id']);
    }

    /**
     * 測試取下層出款明細列表，但資料庫現金資料未同步
     */
    public function testGetWithdrawEntriesListWithUnsynchronisedCashData()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $opService = $this->getContainer()->get('durian.op');

        // 新增一筆出款，但先不跑背景程式讓 queue 被消化
        $parameters = [
            'bank_id' => 5,
            'amount' => -50,
            'fee' => -1,
            'deduction' => -1,
            'payment_gateway_fee' => -1,
            'memo' => 'test',
            'ip' => '127.0.0.1',
        ];
        $client->request('POST', '/api/user/8/cash/withdraw', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(950, $output['ret']['cash']['balance']);

        $cashEntryId = $output['ret']['entry']['id'];
        $cashEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $cashEntryId]);
        $this->assertNull($cashEntry);

        // 檢查 mysql 中的 cash 資料
        $criteria = [
            'user' => 8,
            'currency' => 901,
        ];
        $cash = $em->getRepository('BBDurianBundle:Cash')->findOneBy($criteria);
        $cashArray = $cash->toArray();

        $this->assertEquals(1000, $cashArray['balance']);
        $this->assertEquals(0, $cashArray['pre_sub']);
        $this->assertEquals(0, $cashArray['pre_add']);

        // 檢查 redis 中的 cash 資料
        $redisCashInfo = $opService->getRedisCashBalance($cash);
        $this->assertEquals(950, $redisCashInfo['balance']);
        $this->assertEquals(0, $redisCashInfo['pre_sub']);
        $this->assertEquals(0, $redisCashInfo['pre_add']);

        // 取得出款明細列表
        $parameters = [
            'domain' => 2,
            'username' => 'tester',
            'sub_ret' => 1,
        ];
        $client->request('GET', '/api/cash/withdraw/list', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(11, $output['ret'][4]['id']);
        $this->assertEquals(-50, $output['ret'][4]['amount']);

        $this->assertEquals(950, $output['sub_ret']['cash'][0]['balance']);
        $this->assertEquals(0, $output['sub_ret']['cash'][0]['pre_sub']);
        $this->assertEquals(0, $output['sub_ret']['cash'][0]['pre_add']);

        // 跑背景程式讓 queue 被消化
        $cashPoper = new Poper();
        $cashPoper->runPop($this->getContainer(), 'cash');

        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $cashEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $cashEntryId]);
        $this->assertNotNull($cashEntry);

        // 檢查 mysql 中的 cash 資料
        $em->refresh($cash);
        $cashArray = $cash->toArray();

        $this->assertEquals(950, $cashArray['balance']);
        $this->assertEquals(0, $cashArray['pre_sub']);
        $this->assertEquals(0, $cashArray['pre_add']);

        // 檢查 redis 中的 cash 資料
        $redisCashInfo = $opService->getRedisCashBalance($cash);
        $this->assertEquals(950, $redisCashInfo['balance']);
        $this->assertEquals(0, $redisCashInfo['pre_sub']);
        $this->assertEquals(0, $redisCashInfo['pre_add']);

        // 取得出款明細列表
        $client->request('GET', '/api/cash/withdraw/list', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 檢查餘額資料是否與 queue 未消化前相同
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(950, $output['sub_ret']['cash'][0]['balance']);
        $this->assertEquals(0, $output['sub_ret']['cash'][0]['pre_sub']);
        $this->assertEquals(0, $output['sub_ret']['cash'][0]['pre_add']);
    }

    /**
     * 測試取得下層出款明細列表,沒有帶入domain
     */
    public function testGetWithdrawEntriesListWithoutDomain()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/cash/withdraw/list', [], [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(380018, $output['code']);
        $this->assertEquals('No domain specified', $output['msg']);
    }

    /**
     * 測試取得下層出款明細列表但操作者與帶入的為不同廳
     */
    public function testGetWithdrawEntriesListWithOperationIsNotInSameDomain()
    {
        $client = $this->createClient();

        $sensitiveData = 'entrance=2&operator=test&client_ip=127.0.0.1';
        $sensitiveData .= '&run_php=WithdrawFunctionsTest.php&operator_id=9&vendor=acc';

        $headers = ['HTTP_SENSITIVE_DATA' => $sensitiveData];
        $client->request('GET', '/api/cash/withdraw/list', ['domain' => 2], [], $headers);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150240007', $output['code']);
        $this->assertEquals('The request not allowed when operator is not in same domain', $output['msg']);
    }

    /**
     * 測試取下層出款明細列表代入不存在的username
     */
    public function testGetWithdrawEntriesListByDomainAndNonExistUsername()
    {
        $client = $this->createClient();

        $parameters = [
            'domain' => 2,
            'username' => 'tester_hrhrhr'
        ];

        $client->request('GET', '/api/cash/withdraw/list', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(380026, $output['code']);
        $this->assertEquals('No such user', $output['msg']);
    }

    /**
     * 測試回傳現金出款紀錄依照時間排序
     */
    public function testGetWithdrawEntriesListOrderByDate()
    {
        $client = $this->createClient();

        // 測試取得有確認出款明細依照 at 欄位排序
        $parameters = [
            'domain' => 2,
            'confirm_at_start' => '2012-07-20T00:00:00+0800',
            'confirm_at_end' => '2012-07-21T12:00:00+0800',
            'sort' => 'at',
            'order' => 'desc'
        ];

        $client->request('GET', '/api/cash/withdraw/list', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret'][0]['id']);
        $this->assertEquals(8, $ret['ret'][0]['user_id']);
        $this->assertEquals('2012-07-21T05:00:00+0800', $ret['ret'][0]['confirm_at']);
        $this->assertEquals(2, $ret['ret'][3]['id']);
        $this->assertEquals(7, $ret['ret'][3]['user_id']);
        $this->assertEquals('2012-07-20T05:00:00+0800', $ret['ret'][3]['confirm_at']);

        $this->assertEquals(4, count($ret['ret']));

        // 測試取得有確認出款明細依照 created_at 欄位排序
        $parameters = [
            'domain' => 2,
            'confirm_at_start' => '2012-07-20T00:00:00+0800',
            'confirm_at_end' => '2012-07-21T12:00:00+0800',
            'sort' => 'created_at',
            'order' => 'asc'
        ];

        $client->request('GET', '/api/cash/withdraw/list', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret'][0]['id']);
        $this->assertEquals(7, $ret['ret'][0]['user_id']);
        $this->assertEquals('2012-07-20T05:00:00+0800', $ret['ret'][0]['confirm_at']);
        $this->assertEquals(7, $ret['ret'][3]['id']);
        $this->assertEquals(8, $ret['ret'][3]['user_id']);
        $this->assertEquals('2012-07-21T05:00:00+0800', $ret['ret'][3]['confirm_at']);

        $this->assertEquals(4, count($ret['ret']));
    }

    /**
     * 測試回傳現金出款紀錄帶入錯誤幣別
     */
    public function testGetWithdrawEntriesListWithInvalidCurrency()
    {
        $client = $this->createClient();

        $parameters = [
            'domain'   => '2',
            'currency' => 'AAAA'
        ];

        $client->request('GET', '/api/cash/withdraw/list', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(380017, $ret['code']);
        $this->assertEquals('Currency not support', $ret['msg']);
    }

    /**
     * 測試回傳現金出款紀錄帶入空幣別
     */
    public function testGetWithdrawEntriesListWithEmptyCurrency()
    {
        $client = $this->createClient();

        $parameters = [
            'domain'   => '2',
            'currency' => ''
        ];

        $client->request('GET', '/api/cash/withdraw/list', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(380017, $ret['code']);
        $this->assertEquals('Currency not support', $ret['msg']);
    }

    /**
     * 測試回傳現金出款紀錄已鎖定的資料會回傳鎖定操作者資料
     */
    public function testGetWithdrawEntriesListWithWithdrawEntryLock()
    {
        $client = $this->createClient();

        // 鎖定出款資料
        $client->request('PUT', '/api/cash/withdraw/8/lock', ['operator' => 'test123'], [], $this->headerParam);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret'][0]['id']);
        $this->assertEquals(CashWithdrawEntry::LOCK, $output['ret'][0]['status']);

        $parameters = [
            'created_at_start' => '2010-07-07T00:00:00+0800',
            'created_at_end' => '2010-07-25T00:00:00+0800',
            'domain' => '2',
            'sub_ret' => 1
        ];

        $client->request('GET', '/api/cash/withdraw/list', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret'][0]['id']);
        $this->assertEquals(8, $output['ret'][0]['id']);
        $this->assertEquals(-100, $ret['ret'][0]['amount']);
        $this->assertEquals(-10, $ret['ret'][0]['fee']);
        $this->assertEquals(-20, $ret['ret'][0]['deduction']);
        $this->assertEquals(-70, $ret['ret'][0]['real_amount']);
        $this->assertEquals(0, $ret['ret'][0]['aduit_fee']);
        $this->assertEquals(0, $ret['ret'][0]['aduit_charge']);
        $this->assertEquals(1, $ret['ret'][0]['level_id']);
        $this->assertEquals(8, $ret['sub_ret']['withdraw_entry_lock'][0]['entry_id']);
        $this->assertEquals(8, $ret['sub_ret']['withdraw_entry_lock'][0]['user_id']);
        $this->assertEquals('TWD', $ret['sub_ret']['withdraw_entry_lock'][0]['currency']);
        $this->assertEquals('test123', $ret['sub_ret']['withdraw_entry_lock'][0]['operator']);
    }

    /**
     * 測試回傳現金出款紀錄帶入是否為自動出款條件
     */
    public function testGetWithdrawEntriesListByAutoWithdraw()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $entry = $em->getRepository('BBDurianBundle:CashWithdrawEntry')->findOneBy(['id' => 2]);
        $entry->setAutoWithdraw(true);
        $em->flush();

        $parameters = [
            'created_at_start' => '2010-07-07T00:00:00+0800',
            'created_at_end' => '2010-07-25T00:00:00+0800',
            'domain' => '2',
            'auto_withdraw' => '1'
        ];

        $client->request('GET', '/api/cash/withdraw/list', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret'][0]['id']);
        $this->assertEquals(2, $ret['ret'][0]['domain']);

    }

    /**
     * 測試回傳現金出款紀錄帶入出款商家ID條件
     */
    public function testGetWithdrawEntriesListByMerchantWithdrawId()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $entry = $em->getRepository('BBDurianBundle:CashWithdrawEntry')->findOneBy(['id' => 2]);
        $entry->setMerchantWithdrawId(123);
        $em->flush();

        $parameters = [
            'created_at_start' => '2010-07-07T00:00:00+0800',
            'created_at_end' => '2010-07-25T00:00:00+0800',
            'domain' => '2',
            'merchant_withdraw_id' => '123'
        ];

        $client->request('GET', '/api/cash/withdraw/list', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret'][0]['id']);
        $this->assertEquals(2, $ret['ret'][0]['domain']);

    }

    /**
     * 測試傳回在時間區間內有確認出款明細的使用者
     */
    public function testGetWithdrawConfirmedList()
    {
        $client = $this->createClient();

        // 測試傳回在時間區間內有確認出款明細的使用者, 及相關資料
        $parameters = [
            'confirm_at_start' => '2012-07-20T00:00:00+0800',
            'confirm_at_end' => '2012-07-21T12:00:00+0800',
            'status' => ''
        ];

        $client->request('GET', '/api/cash/withdraw/confirmed_list', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret'][0]['user_id']);
        $this->assertEquals('2012-07-20T05:00:00+0800', $ret['ret'][0]['confirm_at']);
        $this->assertEquals(8, $ret['ret'][3]['user_id']);
        $this->assertEquals('2012-07-21T05:00:00+0800', $ret['ret'][3]['confirm_at']);

        $this->assertEquals(4, count($ret['ret']));

        // 測試傳回在created_at時間區間內有確認出款明細的資料
        $parameters = [
            'created_at_start' => '2010-07-20T00:00:00+0800',
            'created_at_end'   => '2010-07-21T12:00:00+0800'
        ];

        $client->request('GET', '/api/cash/withdraw/confirmed_list', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret'][0]['user_id']);
        $this->assertEquals('2010-07-20T05:00:00+0800', $ret['ret'][0]['at']);
        $this->assertEquals('2010-07-20T05:00:00+0800', $ret['ret'][0]['created_at']);
        $this->assertEquals(8, $ret['ret'][3]['user_id']);
        $this->assertEquals('2010-07-21T05:00:00+0800', $ret['ret'][3]['at']);
        $this->assertEquals('2010-07-21T05:00:00+0800', $ret['ret'][3]['created_at']);

        $this->assertEquals(4, count($ret['ret']));

        // 同時帶入created_at & confirm_at
        $parameters = [
            'created_at_start' => '2010-07-20T00:00:00+0800',
            'created_at_end' => '2010-07-21T12:00:00+0800',
            'confirm_at_start' => '2012-07-20T00:00:00+0800',
            'confirm_at_end' => '2012-07-21T12:00:00+0800'
        ];

        $client->request('GET', '/api/cash/withdraw/confirmed_list', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret'][0]['user_id']);
        $this->assertEquals(8, $ret['ret'][3]['user_id']);
        $this->assertEquals(4, count($ret['ret']));

        // 測試傳回在某個時間之後有確認出款明細的使用者, 及相關資料
        $parameters = ['confirm_at_start' => '2012-07-20T00:00:00+0800'];

        $client->request('GET', '/api/cash/withdraw/confirmed_list', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret'][0]['user_id']);
        $this->assertEquals('2012-07-20T05:00:00+0800', $ret['ret'][0]['confirm_at']);
        $this->assertEquals(7, $ret['ret'][4]['user_id']);
        $this->assertEquals('2012-07-22T05:00:00+0800', $ret['ret'][4]['confirm_at']);

        $this->assertEquals(6, count($ret['ret']));

        // 測試分頁功能
        $parameters = [
            'confirm_at_start' => '2012-07-20T00:00:00+0800',
            'max_results' => 4,
            'first_result' => 1
        ];

        $client->request('GET', '/api/cash/withdraw/confirmed_list', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret'][1]['user_id']);
        $this->assertEquals('2012-07-21T05:00:00+0800', $ret['ret'][1]['confirm_at']);
        $this->assertEquals(8, $ret['ret'][2]['user_id']);
        $this->assertEquals('2012-07-21T05:00:00+0800', $ret['ret'][2]['confirm_at']);

        $this->assertEquals(1, $ret['pagination']['first_result']);
        $this->assertEquals(4, $ret['pagination']['max_results']);
        $this->assertEquals(6, $ret['pagination']['total']);

        // 測試排序
        $parameters = [
            'confirm_at_start' => '2012-07-20T00:00:00+0800',
            'max_results' => 4,
            'first_result' => 1,
            'sort' => ['confirm_at'],
            'order' => ['desc']
        ];

        $client->request('GET', '/api/cash/withdraw/confirmed_list', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret'][0]['user_id']);
        $this->assertEquals('2012-07-22T05:00:00+0800', $ret['ret'][0]['confirm_at']);
        $this->assertEquals(8, $ret['ret'][3]['user_id']);
        $this->assertEquals('2012-07-20T05:00:00+0800', $ret['ret'][3]['confirm_at']);

        $this->assertEquals(1, $ret['pagination']['first_result']);
        $this->assertEquals(4, $ret['pagination']['max_results']);
        $this->assertEquals(6, $ret['pagination']['total']);

        // 測試sub_ret
        $parameters = [
            'confirm_at_start' => '2012-07-20T00:00:00+0800',
            'max_results' => 4,
            'first_result' => 1,
            'sub_ret' => 1
        ];

        $client->request('GET', '/api/cash/withdraw/confirmed_list', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret'][1]['user_id']);
        $this->assertEquals('2012-07-21T05:00:00+0800', $ret['ret'][1]['confirm_at']);
        $this->assertEquals(8, $ret['ret'][2]['user_id']);
        $this->assertEquals('2012-07-21T05:00:00+0800', $ret['ret'][2]['confirm_at']);

        // sub_ret user
        $this->assertEquals(8, $ret['sub_ret']['user'][0]['id']);
        $this->assertEquals('tester', $ret['sub_ret']['user'][0]['username']);
        $this->assertEquals(7, $ret['sub_ret']['user'][1]['id']);
        $this->assertEquals('ztester', $ret['sub_ret']['user'][1]['username']);

        // sub_ret cash
        $this->assertEquals(7, $ret['sub_ret']['cash'][0]['id']);
        $this->assertEquals(8, $ret['sub_ret']['cash'][0]['user_id']);
        $this->assertEquals(6, $ret['sub_ret']['cash'][1]['id']);
        $this->assertEquals(7, $ret['sub_ret']['cash'][1]['user_id']);

        $this->assertEquals(1, $ret['pagination']['first_result']);
        $this->assertEquals(4, $ret['pagination']['max_results']);
        $this->assertEquals(6, $ret['pagination']['total']);

        // 測試指定狀態
        $parameters = [
            'confirm_at_start' => '2012-06-18T00:00:00+0800',
            'status' => 2
        ];

        $client->request('GET', '/api/cash/withdraw/confirmed_list', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(9, $ret['ret'][0]['user_id']);
        $this->assertEquals('2012-06-19T05:00:00+0800', $ret['ret'][0]['confirm_at']);

        $this->assertEquals(1, $ret['pagination']['total']);

        $parameters = [
            'confirm_at_start' => '2012-06-18T00:00:00+0800',
            'status' => '這不是數字!!'
        ];

        $client->request('GET', '/api/cash/withdraw/confirmed_list', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(380006, $ret['code']);
        $this->assertEquals('Status must be numeric', $ret['msg']);
    }

    /**
     * 測試取得有確認出款明細的使用者依照時間排序
     */
    public function testGetWithdrawConfirmedListOrderByDate()
    {
        $client = $this->createClient();

        // 測試取得有確認出款明細依照 created_at 欄位排序
        $parameters = [
            'created_at_start' => '2010-07-20T00:00:00+0800',
            'created_at_end' => '2010-07-21T12:00:00+0800',
            'sort' => 'at',
            'order' => 'desc'
        ];

        $client->request('GET', '/api/cash/withdraw/confirmed_list', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(8, $ret['ret'][0]['user_id']);
        $this->assertEquals('2012-07-21T05:00:00+0800', $ret['ret'][0]['confirm_at']);
        $this->assertEquals(7, $ret['ret'][3]['user_id']);
        $this->assertEquals('2012-07-20T05:00:00+0800', $ret['ret'][3]['confirm_at']);

        $this->assertEquals(4, count($ret['ret']));

        // 測試取得有確認出款明細依照 created_at 欄位排序
        $parameters = [
            'created_at_start' => '2010-07-20T00:00:00+0800',
            'created_at_end' => '2010-07-21T12:00:00+0800',
            'sort' => 'created_at',
            'order' => 'asc'
        ];

        $client->request('GET', '/api/cash/withdraw/confirmed_list', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret'][0]['user_id']);
        $this->assertEquals('2012-07-20T05:00:00+0800', $ret['ret'][0]['confirm_at']);
        $this->assertEquals(8, $ret['ret'][3]['user_id']);
        $this->assertEquals('2012-07-21T05:00:00+0800', $ret['ret'][3]['confirm_at']);

        $this->assertEquals(4, count($ret['ret']));
    }

    /**
     * 測試取得有確認出款明細的使用者帶入是否為自動出款條件
     */
    public function testGetWithdrawConfirmedListByAutoWithdraw()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $entry = $em->getRepository('BBDurianBundle:CashWithdrawEntry')->findOneBy(['id' => 2]);
        $entry->setAutoWithdraw(true);
        $em->flush();

        $parameters = [
            'created_at_start' => '2010-07-20T00:00:00+0800',
            'created_at_end' => '2010-07-21T12:00:00+0800',
            'auto_withdraw' => '1'
        ];

        $client->request('GET', '/api/cash/withdraw/confirmed_list', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret'][0]['id']);
        $this->assertEquals(7, $ret['ret'][0]['user_id']);
        $this->assertEquals('2012-07-20T05:00:00+0800', $ret['ret'][0]['confirm_at']);

        $this->assertEquals(1, count($ret['ret']));
    }

    /**
     * 測試取得有確認出款明細的使用者帶入出款商家ID條件
     */
    public function testGetWithdrawConfirmedListByMerchantWithdrawId()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $entry = $em->getRepository('BBDurianBundle:CashWithdrawEntry')->findOneBy(['id' => 2]);
        $entry->setMerchantWithdrawId(123);
        $em->flush();

        $parameters = [
            'created_at_start' => '2010-07-20T00:00:00+0800',
            'created_at_end' => '2010-07-21T12:00:00+0800',
            'merchant_withdraw_id' => '123'
        ];

        $client->request('GET', '/api/cash/withdraw/confirmed_list', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret'][0]['id']);
        $this->assertEquals(7, $ret['ret'][0]['user_id']);
        $this->assertEquals('2012-07-20T05:00:00+0800', $ret['ret'][0]['confirm_at']);

        $this->assertEquals(1, count($ret['ret']));
    }

    /**
     * 測試取得有確認的出款明細但不傳時間參數
     */
    public function testGetWithdrawConfirmedListWithOutTimeCondition()
    {
        $client = $this->createClient();

        // 都不傳
        $client->request('GET', '/api/cash/withdraw/confirmed_list');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(380011, $ret['code']);
        $this->assertEquals('Must send time parameters', $ret['msg']);

        // 傳空字串
        $parameters = [
            'created_at_start' => '',
            'created_at_end'   => '',
            'confirm_at_start' => ' ',
            'confirm_at_end'   => ' '
        ];

        $client->request('GET', '/api/cash/withdraw/confirmed_list', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(380011, $ret['code']);
        $this->assertEquals('Must send time parameters', $ret['msg']);
    }

    /**
     * 測試回傳現金出款統計紀錄
     */
    public function testGetWithdrawReport()
    {
        $client = $this->createClient();

        // 測試把userIds當中值為空的索引刪掉
        $parameters = [
            'users' => [7, 8, 999999, 0],
            'confirm_at_start' => '2012-07-20T00:00:00+0800',
            'confirm_at_end' => '2012-07-21T12:00:00+0800'
        ];

        $client->request('GET', '/api/cash/withdraw/report', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret']['withdraw'][0]['user_id']);
        $this->assertEquals(6, $ret['ret']['withdraw'][0]['cash_id']);
        $this->assertEquals(-203, $ret['ret']['withdraw'][0]['basic_sum']);
        $this->assertEquals(-440, $ret['ret']['withdraw'][0]['user_original_sum']);
        $this->assertEquals(2, $ret['ret']['withdraw'][0]['count']);
        $this->assertEquals('TWD', $ret['ret']['withdraw'][0]['currency']);
        $this->assertEquals(6, $ret['ret']['withdraw'][0]['parent_id']);
        $this->assertEquals(2, $ret['ret']['withdraw'][0]['domain']);

        $this->assertEquals(8, $ret['ret']['withdraw'][1]['user_id']);
        $this->assertEquals(7, $ret['ret']['withdraw'][1]['cash_id']);
        $this->assertEquals(-220, $ret['ret']['withdraw'][1]['basic_sum']);
        $this->assertEquals(-440, $ret['ret']['withdraw'][1]['user_original_sum']);
        $this->assertEquals('TWD', $ret['ret']['withdraw'][1]['currency']);
        $this->assertEquals(7, $ret['ret']['withdraw'][1]['parent_id']);
        $this->assertEquals(2, $ret['ret']['withdraw'][1]['domain']);
        $this->assertEquals(2, $ret['ret']['withdraw'][1]['count']);
        $this->assertEquals(2, count($ret['ret']['withdraw']));

        // 測試未帶入任何參數
        $client->request('GET', '/api/cash/withdraw/report');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertNull($ret['ret']['withdraw']);

        // confirm_at時間區間內沒有確認的明細
        $parameters = [
            'users' => [7, 8, 999999],
            'confirm_at_start' => '2012-01-20T00:00:00+0800',
            'confirm_at_end' => '2012-01-21T12:00:00+0800'
        ];

        $client->request('GET', '/api/cash/withdraw/report', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertNull($ret['ret']['withdraw']);

        // 看at時間區間
        $parameters = [
            'users' => [7, 8, 999999],
            'created_at_start' => '2010-07-20T00:00:00+0800',
            'created_at_end' => '2010-07-21T12:00:00+0800'
        ];

        $client->request('GET', '/api/cash/withdraw/report', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret']['withdraw'][0]['user_id']);
        $this->assertEquals(6, $ret['ret']['withdraw'][0]['cash_id']);
        $this->assertEquals(-203, $ret['ret']['withdraw'][0]['basic_sum']);
        $this->assertEquals(-440, $ret['ret']['withdraw'][0]['user_original_sum']);
        $this->assertEquals(2, $ret['ret']['withdraw'][0]['count']);
        $this->assertEquals('TWD', $ret['ret']['withdraw'][0]['currency']);
        $this->assertEquals(6, $ret['ret']['withdraw'][0]['parent_id']);
        $this->assertEquals(2, $ret['ret']['withdraw'][0]['domain']);

        $this->assertEquals(8, $ret['ret']['withdraw'][1]['user_id']);
        $this->assertEquals(7, $ret['ret']['withdraw'][1]['cash_id']);
        $this->assertEquals(-220, $ret['ret']['withdraw'][1]['basic_sum']);
        $this->assertEquals(-440, $ret['ret']['withdraw'][1]['user_original_sum']);
        $this->assertEquals('TWD', $ret['ret']['withdraw'][1]['currency']);
        $this->assertEquals(7, $ret['ret']['withdraw'][1]['parent_id']);
        $this->assertEquals(2, $ret['ret']['withdraw'][1]['domain']);
        $this->assertEquals(2, $ret['ret']['withdraw'][1]['count']);
        $this->assertEquals(2, count($ret['ret']['withdraw']));
    }

    /**
     * 測試回傳現金出款統計紀錄
     */
    public function testGetWithdrawReportWithEmptyUsers()
    {
        $client = $this->createClient();

        // 測試把userIds當中值為空的索引刪掉
        $parameters = [
            'users' => [''],
            'start' => '2012-07-20T00:00:00+0800',
            'end' => '2012-07-21T12:00:00+0800',
            'use_update_at' => 1
        ];

        $client->request('GET', '/api/cash/withdraw/report', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(0, count($ret['ret']['withdraw']));
    }

    /**
     * 測試回傳現金出款統計紀錄帶入是否為自動出款條件
     */
    public function testGetWithdrawReportByAutoWithdraw()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $entry = $em->getRepository('BBDurianBundle:CashWithdrawEntry')->findOneBy(['id' => 2]);
        $entry->setAutoWithdraw(true);
        $em->flush();

        $parameters = [
            'users' => [7],
            'created_at_start' => '2010-07-20T00:00:00+0800',
            'created_at_end' => '2016-07-21T12:00:00+0800',
            'auto_withdraw' => '1'
        ];

        $client->request('GET', '/api/cash/withdraw/report', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret']['withdraw'][0]['user_id']);
        $this->assertEquals(6, $ret['ret']['withdraw'][0]['cash_id']);
        $this->assertEquals(-68.00, $ret['ret']['withdraw'][0]['basic_sum']);
        $this->assertEquals(-170.00, $ret['ret']['withdraw'][0]['user_original_sum']);
        $this->assertEquals(1, $ret['ret']['withdraw'][0]['count']);
        $this->assertEquals('TWD', $ret['ret']['withdraw'][0]['currency']);
        $this->assertEquals(6, $ret['ret']['withdraw'][0]['parent_id']);
        $this->assertEquals(2, $ret['ret']['withdraw'][0]['domain']);
    }

    /**
     * 測試回傳現金出款統計紀錄帶入出款商家ID條件
     */
    public function testGetWithdrawReportByMerchantWithdrawId()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $entry = $em->getRepository('BBDurianBundle:CashWithdrawEntry')->findOneBy(['id' => 2]);
        $entry->setMerchantWithdrawId(123);
        $em->flush();

        $parameters = [
            'users' => [7],
            'created_at_start' => '2010-07-20T00:00:00+0800',
            'created_at_end' => '2016-07-21T12:00:00+0800',
            'merchant_withdraw_id' => '123'
        ];

        $client->request('GET', '/api/cash/withdraw/report', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret']['withdraw'][0]['user_id']);
        $this->assertEquals(6, $ret['ret']['withdraw'][0]['cash_id']);
        $this->assertEquals(-68.00, $ret['ret']['withdraw'][0]['basic_sum']);
        $this->assertEquals(-170.00, $ret['ret']['withdraw'][0]['user_original_sum']);
        $this->assertEquals(1, $ret['ret']['withdraw'][0]['count']);
        $this->assertEquals('TWD', $ret['ret']['withdraw'][0]['currency']);
        $this->assertEquals(6, $ret['ret']['withdraw'][0]['parent_id']);
        $this->assertEquals(2, $ret['ret']['withdraw'][0]['domain']);
    }

    /**
     * 測試Account確認出款未帶入操作者姓名
     */
    public function testWithdrawAccountConfirmWithoutCheckedUsername()
    {
        $client = $this->createClient();
        $client->request('PUT', '/api/withdraw/1/account_confirm');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(380003, $output['code']);
        $this->assertEquals('No checked_username specified', $output['msg']);
    }

    /**
     * 測試Account確認出款未帶入出款商家ID
     */
    public function testWithdrawAccountConfirmWithoutMerchantWithdrawId()
    {
        $client = $this->createClient();

        $parameters = ['checked_username' => 'rabbit'];

        $client->request('PUT', '/api/withdraw/1/account_confirm', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150380030, $output['code']);
        $this->assertEquals('No merchant_withdraw_id specified', $output['msg']);
    }

    /**
     * 測試Account確認出款但出款明細不存在
     */
    public function testWithdrawAccountConfirmButEntryNotExists()
    {
        $client = $this->createClient();

        $parameters = [
            'checked_username' => 'rabbit',
            'merchant_withdraw_id' => 1,
        ];

        $client->request('PUT', '/api/withdraw/999/account_confirm', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(380001, $output['code']);
        $this->assertEquals('No such withdraw entry', $output['msg']);
    }

    /**
     * 測試Account確認出款但出款明細不是未處理狀態
     */
    public function testWithdrawAccountConfirmButStatusNotUntreated()
    {
        $client = $this->createClient();

        $parameters = [
            'checked_username' => 'rabbit',
            'merchant_withdraw_id' => 1,
        ];

        $client->request('PUT', '/api/withdraw/1/account_confirm', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150380037, $output['code']);
        $this->assertEquals('Withdraw status not untreated', $output['msg']);
    }

    /**
     * 測試Account確認出款但該廳不支援
     */
    public function testWithdrawAccountConfirmButDomainNotSupported()
    {
        $client = $this->createClient();

        $parameters = [
            'checked_username' => 'rabbit',
            'merchant_withdraw_id' => 1,
        ];

        $client->request('PUT', '/api/withdraw/8/account_confirm', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150380038, $output['code']);
        $this->assertEquals('Domain is not supported by WithdrawAccountConfirm', $output['msg']);
    }

    /**
     * 測試Account確認出款但不支援電子錢包出款明細
     */
    public function testWithdrawAccountConfirmButNotSupportedByAutoWithdrawEntry()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $sql = 'UPDATE cash_withdraw_entry SET domain = 6, auto_withdraw = 1 WHERE id = 8';
        $em->getConnection()->executeUpdate($sql);

        $parameters = [
            'checked_username' => 'rabbit',
            'merchant_withdraw_id' => 1,
        ];

        $client->request('PUT', '/api/withdraw/8/account_confirm', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150380039, $output['code']);
        $this->assertEquals('Entry of mobile is not supported by WithdrawAccountConfirm', $output['msg']);
    }

    /**
     * 測試Account確認出款但找不到現金資料
     */
    public function testWithdrawAccountConfirmButNoCashFound()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $sql = 'UPDATE cash_withdraw_entry SET domain = 6 WHERE id = 8';
        $em->getConnection()->executeUpdate($sql);

        $cash = $em->find('BBDurianBundle:Cash', 7);
        $em->remove($cash);
        $em->flush();

        $parameters = [
            'checked_username' => 'rabbit',
            'merchant_withdraw_id' => 1,
        ];

        $client->request('PUT', '/api/withdraw/8/account_confirm', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(380023, $output['code']);
        $this->assertEquals('No cash found', $output['msg']);
    }

    /**
     * 測試Account確認出款但找不到出款商家
     */
    public function testWithdrawAccountConfirmButNoMerchantWithdrawFound()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $sql = 'UPDATE cash_withdraw_entry SET domain = 6 WHERE id = 8';
        $em->getConnection()->executeUpdate($sql);

        $parameters = [
            'checked_username' => 'rabbit',
            'merchant_withdraw_id' => 999,
        ];

        $client->request('PUT', '/api/withdraw/8/account_confirm', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150380029, $output['code']);
        $this->assertEquals('No MerchantWithdraw found', $output['msg']);
    }

    /**
     * 測試Account確認出款但找不到層級幣別
     */
    public function testWithdrawAccountConfirmButNoLevelCurrencyFound()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $sql = 'UPDATE cash_withdraw_entry SET domain = 6, currency = 978 WHERE id = 8';
        $em->getConnection()->executeUpdate($sql);

        $parameters = [
            'checked_username' => 'rabbit',
            'merchant_withdraw_id' => 1,
        ];

        $client->request('PUT', '/api/withdraw/8/account_confirm', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150380035, $output['code']);
        $this->assertEquals('No LevelCurrency found', $output['msg']);
    }

    /**
     * 測試Account確認出款但找不到線上付款收費相關設定
     */
    public function testWithdrawAccountConfirmButNoPaymentChargeFound()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $sql = 'UPDATE cash_withdraw_entry SET domain = 6 WHERE id = 8';
        $em->getConnection()->executeUpdate($sql);

        $sql = 'UPDATE level_currency SET payment_charge_id = NULL WHERE level_id = 2 and currency = 901';
        $em->getConnection()->executeUpdate($sql);

        $paymentCharge = $em->find('BBDurianBundle:PaymentCharge', 1);
        $em->remove($paymentCharge);
        $em->flush();

        $parameters = [
            'checked_username' => 'rabbit',
            'merchant_withdraw_id' => 1,
        ];

        $client->request('PUT', '/api/withdraw/8/account_confirm', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150380036, $output['code']);
        $this->assertEquals('No PaymentCharge found', $output['msg']);
    }

    /**
     * 測試Account確認出款且未設定支付平台手續費
     */
    public function testWithdrawAccountConfirmWithoutPaymentGatewayFee()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default');

        $sql = 'UPDATE cash_withdraw_entry SET domain = 6 WHERE id = 8';
        $em->getConnection()->executeUpdate($sql);

        $parameters = [
            'checked_username' => 'rabbit',
            'merchant_withdraw_id' => 1,
        ];

        $client->request('PUT', '/api/withdraw/8/account_confirm', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(CashWithdrawEntry::CONFIRM, $output['ret']['status']);
        $this->assertEquals(6, $output['ret']['domain']);
        $this->assertEquals(1, $output['ret']['merchant_withdraw_id']);

        // 檢查使用者出入款統計資料
        $userStat = $em->find('BBDurianBundle:UserStat', 8);
        $this->assertEquals(1, $userStat->getWithdrawCount());
        $this->assertEquals(185, $userStat->getWithdrawTotal());
        $this->assertEquals(185, $userStat->getWithdrawMax());

        // 驗證operationLog
        $logOp1 = $emShare->find('BBDurianBundle:LogOperation', 1);
        $msg1 = '@withdraw_count:0=>1, @withdraw_total:0=>185, @withdraw_max:0=>185.0000, @modified_at:';
        $this->assertEquals('user_stat', $logOp1->getTableName());
        $this->assertEquals('@user_id:8', $logOp1->getMajorKey());
        $this->assertContains($msg1, $logOp1->getMessage());

        $message = [
            '@status:0=>1',
            '@merchant_withdraw_id:1',
            '@checked_username:rabbit',
        ];

        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('cash_withdraw_entry', $logOp2->getTableName());
        $this->assertEquals('@id:8', $logOp2->getMajorKey());
        $this->assertEquals(implode(', ', $message), $logOp2->getMessage());

        $queueName = 'cash_deposit_withdraw_queue';
        $this->assertEquals(1, $redis->llen($queueName));

        $queue = json_decode($redis->rpop($queueName), true);

        $this->assertEquals(0, $queue['ERRCOUNT']);
        $this->assertEquals(8, $queue['user_id']);
        $this->assertFalse($queue['deposit']);
        $this->assertTrue($queue['withdraw']);
        $this->assertNotNull($queue['withdraw_at']);
    }

    /**
     * 測試Account確認出款
     */
    public function testWithdrawAccountConfirm()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default');

        $sql = 'UPDATE cash_withdraw_entry SET domain = 6 WHERE id = 8';
        $em->getConnection()->executeUpdate($sql);

        // 新增支付平台線上付款費率
        $sql = 'INSERT INTO payment_gateway_fee (payment_charge_id, payment_gateway_id, ' .
            'rate, withdraw_rate) VALUES (5, 1, 0, 10)';
        $em->getConnection()->executeUpdate($sql);

        $parameters = [
            'checked_username' => 'rabbit',
            'merchant_withdraw_id' => 1,
        ];

        $client->request('PUT', '/api/withdraw/8/account_confirm', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(CashWithdrawEntry::CONFIRM, $output['ret']['status']);
        $this->assertEquals(6, $output['ret']['domain']);
        $this->assertEquals(1, $output['ret']['merchant_withdraw_id']);

        // 檢查使用者出入款統計資料
        $userStat = $em->find('BBDurianBundle:UserStat', 8);
        $this->assertEquals(1, $userStat->getWithdrawCount());
        $this->assertEquals(165, $userStat->getWithdrawTotal());
        $this->assertEquals(165, $userStat->getWithdrawMax());

        // 驗證operationLog
        $logOp1 = $emShare->find('BBDurianBundle:LogOperation', 1);
        $msg1 = '@withdraw_count:0=>1, @withdraw_total:0=>165, @withdraw_max:0=>165.0000, @modified_at:';
        $this->assertEquals('user_stat', $logOp1->getTableName());
        $this->assertEquals('@user_id:8', $logOp1->getMajorKey());
        $this->assertContains($msg1, $logOp1->getMessage());

        $message = [
            '@payment_gateway_fee:0=>-40',
            '@auto_withdraw_amount:-370=>-330',
            '@status:0=>1',
            '@merchant_withdraw_id:1',
            '@checked_username:rabbit',
        ];

        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('cash_withdraw_entry', $logOp2->getTableName());
        $this->assertEquals('@id:8', $logOp2->getMajorKey());
        $this->assertEquals(implode(', ', $message), $logOp2->getMessage());

        $queueName = 'cash_deposit_withdraw_queue';
        $this->assertEquals(1, $redis->llen($queueName));

        $queue = json_decode($redis->rpop($queueName), true);

        $this->assertEquals(0, $queue['ERRCOUNT']);
        $this->assertEquals(8, $queue['user_id']);
        $this->assertFalse($queue['deposit']);
        $this->assertTrue($queue['withdraw']);
        $this->assertNotNull($queue['withdraw_at']);
    }

    /**
     * 測試取得出款查詢結果但找不到出款明細
     */
    public function testGetWithdrawTrackingWithoutWithdrawEntry()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/withdraw/999/tracking');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(380001, $output['code']);
        $this->assertEquals('No such withdraw entry', $output['msg']);
    }

    /**
     * 測試取得出款查詢結果但找不到商家
     */
    public function testGetWithdrawTrackingButNoMerchantWithdarwFound()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $entry = $em->getRepository('BBDurianBundle:CashWithdrawEntry')->findOneBy(['id' => 8]);
        $entry->setMerchantWithdrawId(0);
        $em->flush();

        $client->request('GET', '/api/withdraw/8/tracking');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150380029, $output['code']);
        $this->assertEquals('No MerchantWithdraw found', $output['msg']);
    }

    /**
     * 測試取得出款查詢結果但支付平台不支援查詢
     */
    public function testGetWithdrawTrackingButPaymentGatewayNotSupport()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/withdraw/8/tracking');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150380040, $output['code']);
        $this->assertEquals('PaymentGateway does not support withdraw tracking', $output['msg']);
    }

    /**
     * 測試取得出款查詢結果
     */
    public function testGetWithdrawTracking()
    {
        $mockHelper = $this->getMockBuilder('BB\DurianBundle\Withdraw')
            ->disableOriginalConstructor()
            ->setMethods(['withdrawTracking'])
            ->getMock();

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 67);
        $paymentGateway->setWithdrawTracking(true);
        $em->flush();

        $client = $this->createClient();
        $client->getContainer()->set('durian.withdraw_helper', $mockHelper);

        $client->request('GET', '/api/withdraw/8/tracking');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
    }
}
