<?php

namespace BB\DurianBundle\Tests\Repository;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\UserAncestor;
use BB\DurianBundle\Entity\RemovedUser;
use BB\DurianBundle\Entity\RemovedCash;

/**
 * 測試CashRepository
 */
class CashRepositoryTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashTransData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData'
        ];

        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashEntryData'
        ];
        $this->loadFixtures($classnames, 'entry');
        $this->loadFixtures($classnames, 'his');

        $redis = $this->getContainer()->get('snc_redis.sequence');

        $redis->set('cash_seq', 1000);
    }

    /**
     * 測試回傳現金的筆數和交易記錄
     */
    public function testCountAndGetEntries()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $repo = $emEntry->getRepository('BBDurianBundle:Cash');

        // 調整測試資料
        $parameters = [
            'id' => 10,
            'at' => 20120101120000
        ];
        $entry = $emEntry->find('BBDurianBundle:CashEntry', $parameters);
        $entry->setRefId(11509530);
        $emEntry->flush();

        $cash = $em->find('BBDurianBundle:Cash', 1);
        $opcode = [1001, 1002];
        $firstResult = 0;
        $maxResults = 5;
        $startTime = 20120101000000;
        $endTime = 20120102000000;
        $refId = 11509530;
        $orderBy = [
            'createdAt' => 'ASC',
            'cashId' => 'ASC'
        ];

        // 回傳交易紀錄
        $output = $repo->getEntriesBy($cash, $orderBy, $firstResult, $maxResults, $opcode, $startTime, $endTime, $refId);

        $this->assertEquals(1, $output[0]->getCashId());
        $this->assertEquals(901, $output[0]->getCurrency());
        $this->assertEquals(1001, $output[0]->getOpcode());
        $this->assertEquals(11509530, $output[0]->getRefId());

        $this->assertEquals(1, $output[1]->getCashId());
        $this->assertEquals(901, $output[1]->getCurrency());
        $this->assertEquals(1002, $output[1]->getOpcode());
        $this->assertEquals(11509530, $output[1]->getRefId());
    }

    /**
     * 測試根據條件回傳歷史交易紀錄
     */
    public function testGetHisEntriesBy()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emHis = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $repo = $emHis->getRepository('BBDurianBundle:Cash');

        $cash = $em->find('BBDurianBundle:Cash', 1);
        $opcode = [1001, 1002];
        $firstResult = 0;
        $maxResults = 5;
        $startTime = 20120101000000;
        $endTime = 20120102000000;
        $refId = 11509530;
        $orderBy = ['createdAt' => 'ASC'];

        // 回傳交易紀錄
        $output = $repo->getHisEntriesBy($cash, $orderBy, $firstResult, $maxResults, $opcode, $startTime, $endTime, $refId);

        $this->assertEquals(1, $output[0]->getCashId());
        $this->assertEquals(901, $output[0]->getCurrency());
        $this->assertEquals(1001, $output[0]->getOpcode());
        $this->assertEquals(11509530, $output[0]->getRefId());
    }

    /**
     * 測試回傳餘額為負數的現金筆數和明細
     */
    public function testCountAndGetNegativeBalance()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:Cash');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $cashHelper = $this->getContainer()->get('durian.cash_helper');

        // 建立測試資料
        $cash = $em->find('BBDurianBundle:Cash', 1);
        $cashHelper->addCashEntry($cash, 1010, -2000, '', 123456);
        $em->flush();
        $emEntry->flush();

        $firstResult = 0;
        $maxResults = 5;

        $output = $repo->getNegativeBalance($firstResult, $maxResults);
        $balance = $output[0]->getBalance() - $output[0]->getPreSub();

        $this->assertEquals(1, $output[0]->getId());
        $this->assertEquals(901, $output[0]->getCurrency());
        $this->assertEquals(-1000, $balance);
    }

    /**
     * 測試取得時間區間內的明細餘額加總，並將存入、取出分別加總後回傳
     */
    public function testGetTotalAmount()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $repo = $emEntry->getRepository('BBDurianBundle:Cash');

        $cash = $em->find('BBDurianBundle:Cash', 1);
        $opcode = [1001, 1002];
        $startTime = 20120101000000;
        $endTime = 20130102000000;

        $output = $repo->getTotalAmount($cash, $opcode, $startTime, $endTime, 'CashEntry');

        $this->assertEquals(-80, $output['withdraw']);
        $this->assertEquals(1100, $output['deposite']);
        $this->assertEquals(1020, $output['total']);
    }

    /**
     * 測試回復使用者現金資料
     */
    public function testRecoverRemovedCash()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $cash = $em->find('BBDurianBundle:Cash', 7);
        $user = $cash->getUser();
        $removedUser = new RemovedUser($user);
        $removedCash = new RemovedCash($removedUser, $cash);
        $cashArray = $cash->toArray();

        $em->remove($cash);
        $em->flush();
        $em->clear();

        $em->getRepository('BBDurianBundle:Cash')->recoverRemovedCash($removedCash);
        $cashRecover = $em->find('BBDurianBundle:Cash', 7);
        $cashRecoverArray = $cashRecover->toArray();

        $this->assertEquals($cashArray['user_id'], $cashRecoverArray['user_id']);
        $this->assertEquals(0, $cashRecoverArray['balance']);
        $this->assertEquals($cashArray['pre_sub'], $cashRecoverArray['pre_sub']);
        $this->assertEquals($cashArray['pre_add'], $cashRecoverArray['pre_add']);
        $this->assertEquals($cashArray['currency'], $cashRecoverArray['currency']);
    }
}
