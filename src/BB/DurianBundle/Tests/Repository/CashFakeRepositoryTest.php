<?php

namespace BB\DurianBundle\Tests\Repository;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\CashFake;
use BB\DurianBundle\Entity\CashFakeEntry;
use BB\DurianBundle\Entity\CashFakeTotalBalance;
use BB\DurianBundle\Entity\CashFakeTrans;
use BB\DurianBundle\Entity\UserAncestor;
use BB\DurianBundle\Entity\RemovedUser;
use BB\DurianBundle\Entity\RemovedCashFake;

/**
 * 測試CashFakeRepository
 */
class CashFakeRepositoryTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeEntryOperatorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeTransferEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData'
        ];

        $this->loadFixtures($classnames);
    }

    /**
     * 測試回傳假現金有幾筆交易記錄
     */
    public function testCountNumOf()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $repo = $em->getRepository('BBDurianBundle:CashFake');

        // 調整測試資料
        $parameters = [
            'id' => 2,
            'at' => 20130101120000
        ];
        $entry = $em->find('BBDurianBundle:CashFakeEntry', $parameters);
        $entry->setRefId(5150840307);
        $em->flush();

        $cashFake = $em->find('BBDurianBundle:CashFake', 1);
        $opcode = [1003, 1006];
        $startTime = 20130101000000;
        $endTime = 20130102000000;
        $refId = 5150840307;

        $output = $repo->countNumOf($cashFake, $opcode, $startTime, $endTime, $refId);

        $this->assertEquals(2, $output);

        // 若 $opcode 不是陣列型態
        $opcode = 1003;

        $output = $repo->countNumOf($cashFake, $opcode, $startTime, $endTime, $refId);

        $this->assertEquals(1, $output);
    }

    /**
     * 測試根據條件回傳交易紀錄
     */
    public function testGetEntriesBy()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $repo = $em->getRepository('BBDurianBundle:CashFake');

        $cashFake = $em->find('BBDurianBundle:CashFake', 2);
        $orderBy = ['createdAt' => 'ASC'];
        $opcode = [1001, 1002];
        $startTime = 20130101000000;
        $endTime = 20130102000000;
        $refId = 0;

        $output = $repo->getEntriesBy($cashFake, $orderBy, 0, 5, $opcode, $startTime, $endTime, $refId);

        $this->assertEquals(2, count($output));
        $this->assertEquals(4, $output[0]->getId());
        $this->assertEquals(1001, $output[0]->getOpcode());
        $this->assertEquals(0, $output[0]->getRefId());
        $this->assertEquals(5, $output[1]->getId());
        $this->assertEquals(1002, $output[1]->getOpcode());
        $this->assertEquals(0, $output[1]->getRefId());

        // 若 $opcode 不是陣列型態
        $opcode = 1001;

        $output = $repo->getEntriesBy($cashFake, $orderBy, 0, 5, $opcode, $startTime, $endTime, $refId);

        $this->assertEquals(1, count($output));
        $this->assertEquals(4, $output[0]->getId());
        $this->assertEquals(1001, $output[0]->getOpcode());
        $this->assertEquals(0, $output[0]->getRefId());
    }

    /**
     * 測試加總明細總額
     */
    public function testSumEntryAmountOf()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $repo = $em->getRepository('BBDurianBundle:CashFake');

        // 調整測試資料
        $parameters = [
            'id' => 2,
            'at' => 20130101120000
        ];
        $entry = $em->find('BBDurianBundle:CashFakeEntry', $parameters);
        $entry->setRefId(5150840307);
        $em->flush();

        $opcode = [1003, 1006];
        $startTime = 20130101000000;
        $endTime = 20130102000000;
        $refId = [5150840307];

        $output = $repo->sumEntryAmountOf($opcode, $startTime, $endTime, $refId);

        $this->assertEquals(5150840307, $output[0]['ref_id']);
        $this->assertEquals(500, $output[0]['total_amount']);
    }

    /**
     * 測試藉由明細回傳操作者
     */
    public function testGetEntryOperatorByEntries()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $repo = $em->getRepository('BBDurianBundle:CashFake');

        $parameters = [
            'id' => 1,
            'at' => 20130101120000
        ];
        $entry = $em->find('BBDurianBundle:CashFakeEntry', $parameters);

        $output = $repo->getEntryOperatorByEntries([$entry]);

        $this->assertEquals(1, $output[1]->getEntryId());
        $this->assertEquals('company', $output[1]->getUsername());
        $this->assertEquals('lala', $output[1]->getWhom());
    }

    /**
     * 測試回傳所有下層餘額總和 (only for 假現金)
     */
    public function testGetTotalBalanceBelow()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $repo = $em->getRepository('BBDurianBundle:CashFake');

        $user = $em->find('BBDurianBundle:User', 7);
        $parameters = [
            'sub' => 0,
            'block' => 0,
            'enable' => 1
        ];

        $output = $repo->getTotalBalanceBelow($user, $parameters, 1);

        $this->assertEquals(500, $output);
    }

    /**
     * 測試回傳假現金或其下層有幾筆轉帳交易記錄明細和筆數 (僅限9890以下的opcode)
     */
    public function testCountAndGetTransferEntriesOf()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $repo = $em->getRepository('BBDurianBundle:CashFake');

        $user = $em->find('BBDurianBundle:User', 8);
        $parameters = [
            'depth' => 0,
            'opcode' => [1001, 1002],
            'start_time' => 20130101000000,
            'end_time' => 20130102000000,
            'ref_id' => 0,
            'currency' => 156
        ];

        // 回傳交易記錄筆數
        $output = $repo->countTransferEntriesOf($user, $parameters);

        $this->assertEquals(2, $output);

        $parameters = [
            'depth' => 0,
            'order_by' => ['createdAt' => 'ASC'],
            'first_result' => 0,
            'max_results' => 5,
            'opcode' => [1001, 1002],
            'start_time' => 20130101000000,
            'end_time' => 20130102000000,
            'ref_id' => 0,
            'currency' => 156
        ];

        // 回傳交易記錄明細
        $output = $repo->getTransferEntriesOf($user, $parameters);

        $this->assertEquals(4, $output[0]->getId());
        $this->assertEquals(8, $output[0]->getUserid());
        $this->assertEquals(2, $output[0]->getDomain());
        $this->assertEquals(1001, $output[0]->getOpcode());
        $this->assertEquals(156, $output[0]->getCurrency());

        $this->assertEquals(5, $output[1]->getId());
        $this->assertEquals(8, $output[1]->getUserid());
        $this->assertEquals(2, $output[1]->getDomain());
        $this->assertEquals(1002, $output[1]->getOpcode());
        $this->assertEquals(156, $output[1]->getCurrency());

        $user = $em->find('BBDurianBundle:User', 7);
        $parameters = [
            'depth' => 1,
            'opcode' => 1002,
            'start_time' => 20130101000000,
            'end_time' => 20130102000000,
            'ref_id' => 0,
            'currency' => 156
        ];

        // 回傳交易記錄筆數，當 depth = 1
        $output = $repo->countTransferEntriesOf($user, $parameters);

        $this->assertEquals(1, $output);

        $parameters = [
            'depth' => 1,
            'order_by' => ['createdAt' => 'ASC'],
            'first_result' => 0,
            'max_results' => 5,
            'opcode' => 1002,
            'start_time' => 20130101000000,
            'end_time' => 20130102000000,
            'ref_id' => 0,
            'currency' => 156
        ];

        // 回傳交易記錄明細，當 depth = 1
        $output = $repo->getTransferEntriesOf($user, $parameters);

        $this->assertEquals(5, $output[0]->getId());
        $this->assertEquals(8, $output[0]->getUserid());
        $this->assertEquals(2, $output[0]->getDomain());
        $this->assertEquals(1002, $output[0]->getOpcode());
        $this->assertEquals(156, $output[0]->getCurrency());

        $user = $em->find('BBDurianBundle:User', 2);
        $parameters = [
            'depth' => 5,
            'opcode' => 1002,
            'start_time' => 20130101000000,
            'end_time' => 20130102000000,
            'ref_id' => 0,
            'currency' => 156
        ];

        // 回傳交易記錄筆數，當 depth = 5
        $output = $repo->countTransferEntriesOf($user, $parameters);

        $this->assertEquals(1, $output);

        $parameters = [
            'depth' => 5,
            'order_by' => ['createdAt' => 'ASC'],
            'first_result' => 0,
            'max_results' => 5,
            'opcode' => 1002,
            'start_time' => 20130101000000,
            'end_time' => 20130102000000,
            'ref_id' => 0,
            'currency' => 156
        ];

        // 回傳交易記錄明細，當 depth = 5
        $output = $repo->getTransferEntriesOf($user, $parameters);

        $this->assertEquals(5, $output[0]->getId());
        $this->assertEquals(8, $output[0]->getUserid());
        $this->assertEquals(2, $output[0]->getDomain());
        $this->assertEquals(1002, $output[0]->getOpcode());
        $this->assertEquals(156, $output[0]->getCurrency());
    }

    /**
     * 測試計算餘額為負數快開額度明細和筆數
     */
    public function testCountAndGetNegativeBalance()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $repo = $em->getRepository('BBDurianBundle:CashFake');

        // 調整測試資料
        $cashFake = $em->find('BBDurianBundle:CashFake', 1);
        $cashFake->setBalance(-100);
        $cashFake->setNegative(true);
        $em->flush();

        $output = $repo->countNegativeBalance(0, 5);

        $this->assertEquals(1, $output);

        // 回傳快開額度明細
        $output = $repo->getNegativeBalance(0, 5);
        $balance = $output[0]->getBalance() - $output[0]->getPreSub();

        $this->assertEquals(1, $output[0]->getId());
        $this->assertEquals(-100, $balance);
        $this->assertEquals(156, $output[0]->getCurrency());
    }

    /**
     * 測試回傳下層有的幣別
     */
    public function testGetCurrencyBelow()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $repo = $em->getRepository('BBDurianBundle:CashFake');

        $output = $repo->getCurrencyBelow(7);

        $this->assertEquals(156, $output[0]);
    }

    /**
     * 回傳會員總額中的幣別
     */
    public function testGetTotalBalanceCurrency()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $repo = $em->getRepository('BBDurianBundle:CashFake');

        // 建置測試資料
        $balance = new CashFakeTotalBalance(7, 156);
        $em->persist($balance);
        $em->flush();

        $output = $repo->getTotalBalanceCurrency(7, [156]);

        $this->assertEquals(156, $output[0]);

        // 回傳空值，若沒帶入幣別
        $output = $repo->getTotalBalanceCurrency(7, []);

        $this->assertEmpty($output);
    }

    /**
     * 測試取得時間區間內的明細餘額加總，並將存入、取出分別加總後回傳
     */
    public function testGetTotalAmount()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $repo = $em->getRepository('BBDurianBundle:CashFake');

        $cashFake = $em->find('BBDurianBundle:CashFake', 1);
        $opcode = [1003, 1006];
        $startTime = 20150101000000;
        $endTime = 20150102000000;

        // 回傳空值
        $output = $repo->getTotalAmount($cashFake, $opcode, $startTime, $endTime, 'CashFakeEntry');

        $this->assertEmpty($output);

        $startTime = 20130101000000;
        $endTime = 20130102000000;

        // 回傳明細
        $output = $repo->getTotalAmount($cashFake, $opcode, $startTime, $endTime, 'CashFakeEntry');

        $this->assertEquals(-500, $output['withdraw']);
        $this->assertEquals(1000, $output['deposite']);
        $this->assertEquals(500, $output['total']);
    }

    /**
     * 測試刪除預扣存交易紀錄
     */
    public function testRemoveTransEntryOf()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $repo = $em->getRepository('BBDurianBundle:CashFake');

        // 建立測試資料
        $cashFake = $em->find('BBDurianBundle:CashFake', 1);
        $cashFakeTrans = new CashFakeTrans($cashFake, 101, 100, 'testing removeTransEntryOf');
        $cashFakeTrans->setId(1);
        $cashFakeTrans->setRefId(123);
        $em->persist($cashFakeTrans);
        $em->flush();
        $em->clear();

        $output = $repo->removeTransEntryOf($cashFake);

        // 檢查紀錄是否刪除
        $cashFakeTrans = $em->find('BBDurianBundle:CashFakeTrans', 1);

        $this->assertNull($cashFakeTrans);
    }

    /**
     * 測試取得最近一筆導致快開額度為負的交易明細
     */
    public function testGetNegativeEntry()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $repo = $em->getRepository('BBDurianBundle:CashFake');

        $cashFake = $em->find('BBDurianBundle:CashFake', 1);
        $startTime = 20150303000000;
        $endTime = 20150305000000;

        // 回傳 null
        $output = $repo->getNegativeEntry($cashFake);

        $this->assertNull($output);

        // 建置測試資料
        $entry = new CashFakeEntry($cashFake, 1010, -501);
        $entry->setId(99);
        $entry->setRefId(515123447);
        $em->persist($entry);
        $entry->setAt(20150304000000);
        $em->flush();

        $output = $repo->getNegativeEntry($cashFake, $startTime, $endTime);

        // 回傳的明細與測試資料相同
        $this->assertEquals($entry, $output);
    }

    /**
     * 測試取得時間區間內未commit的transaction資料和筆數
     */
    public function testCountAndGetCashFakeUncommit()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $repo = $em->getRepository('BBDurianBundle:CashFake');

        $at = new \Datetime('2015-03-04 00:00:00');
        $time = clone $at;
        $time->sub(new \DateInterval('P1D'));

        // 建立測試資料
        $cashFake = $em->find('BBDurianBundle:CashFake', 1);
        $cashFakeTrans = new CashFakeTrans($cashFake, 1010, 100, 'testing getCashFakeUncommit');
        $cashFakeTrans->setId(1);
        $cashFakeTrans->setRefId(123);
        $cashFakeTrans->isChecked(false);
        $cashFakeTrans->setCreatedAt($time);
        $em->persist($cashFakeTrans);
        $em->flush();

        $output = $repo->countCashFakeUncommit($at);

        $this->assertEquals(1, $output);

        // 回傳明細
        $output = $repo->getCashFakeUncommit($at, 0, 5);

        $this->assertEquals(1, $output[0]['id']);
        $this->assertEquals(100, $output[0]['amount']);
        $this->assertEquals(1, $output[0]['cash_fake_id']);
        $this->assertEquals(1010, $output[0]['opcode']);
        $this->assertEquals(123, $output[0]['ref_id']);
    }

    /**
     * 測試取得使用者資料，回傳的資料會符合userInfo[$userId]['username']的格式
     */
    public function testGetUserInfoById()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $repo = $em->getRepository('BBDurianBundle:CashFake');

        $output = $repo->getUserInfoById(7);

        $this->assertEquals(7, $output[7]['user_id']);
        $this->assertEquals('ztester', $output[7]['username']);
        $this->assertEquals(2, $output[7]['domain']);
        $this->assertEquals('company', $output[7]['domain_alias']);
    }

    /**
     * 回傳會員快開總餘額記錄
     */
    public function testGetCashFakeTotalBalance()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $repo = $em->getRepository('BBDurianBundle:CashFake');

        // 建置測試資料
        $balance = new CashFakeTotalBalance(7, 156);
        $em->persist($balance);
        $time = new \Datetime('2015-03-04 00:00:00');
        $balance->setAt($time);
        $em->flush();

        $output = $repo->getCashFakeTotalBalance(true);

        $this->assertEquals(7, $output[0]->getParentId());
        $this->assertEquals(156, $output[0]->getCurrency());
        $this->assertEquals($time, $output[0]->getAt());
    }

    /**
     * 測試回復使用者假現金資料
     */
    public function testRecoverRemovedCashFake()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $cashFake = $em->find('BBDurianBundle:CashFake', 2);
        $user = $cashFake->getUser();
        $removedUser = new RemovedUser($user);
        $removedCashFake = new RemovedCashFake($removedUser, $cashFake);
        $cashFakeArray = $cashFake->toArray();

        $em->remove($cashFake);
        $em->flush();
        $em->clear();

        $em->getRepository('BBDurianBundle:CashFake')->recoverRemovedCashFake($removedCashFake);
        $cashFakeRecover = $em->find('BBDurianBundle:CashFake', 2);
        $cashFakeRecoverArray = $cashFakeRecover->toArray();

        $this->assertEquals($cashFakeArray['user_id'], $cashFakeRecoverArray['user_id']);
        $this->assertEquals(0, $cashFakeRecoverArray['balance']);
        $this->assertEquals($cashFakeArray['pre_sub'], $cashFakeRecoverArray['pre_sub']);
        $this->assertEquals($cashFakeArray['pre_add'], $cashFakeRecoverArray['pre_add']);
        $this->assertEquals($cashFakeArray['currency'], $cashFakeRecoverArray['currency']);
        $this->assertEquals($cashFakeArray['enable'], $cashFakeRecoverArray['enable']);
    }
}
