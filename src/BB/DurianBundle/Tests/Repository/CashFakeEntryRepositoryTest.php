<?php

namespace BB\DurianBundle\Tests\Repository;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\CashFakeEntry;
use BB\DurianBundle\Entity\CashFakeTrans;

/**
 * 測試CashFakeEntryRepository
 */
class CashFakeEntryRepositoryTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeTransferEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData'
        ];

        $this->loadFixtures($classnames);
    }

    /**
     * 測試取得Id最大值
     */
    public function testGetMaxId()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $cashFake = $em->find('BBDurianBundle:CashFake', 1);

        $repo = $em->getRepository('BBDurianBundle:CashFakeEntry');
        $maxId = $repo->getMaxId();

        $entry = new CashFakeEntry($cashFake, 1001, 1000);
        $entry->setId($maxId + 1);
        $entry->setRefId(238030097);
        $time = new \DateTime('2013-01-01 12:00:00');
        $entry->setCreatedAt($time);
        $entry->setAt(20130101120000);
        $em->persist($entry);

        $em->flush();

        $this->assertEquals($entry->getId(), $repo->getMaxId());
    }

    /**
     * 測試修改明細備註
     */
    public function testSetEntryMemo()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:CashFakeEntry');

        $id = 1;
        $at = '20130101120000';
        $memo = "Setting entry's memo";

        // 建立CashFakeTrans測試資料
        $entry = $em->find('BBDurianBundle:CashFake', 1);
        $time = new \DateTime('2013-01-01 12:00:00');

        $cashFakeTrans = new CashFakeTrans($entry, 123, 0);
        $cashFakeTrans->setId(1);
        $cashFakeTrans->setCreatedAt($time);
        $cashFakeTrans->setRefId(5150840307);
        $em->persist($cashFakeTrans);
        $em->flush();

        $em->clear();

        $output = $repo->setEntryMemo($id, $at, $memo);

        $parameter = [
            'id' => 1,
            'at' => '20130101120000'
        ];

        $entry = $em->find('BBDurianBundle:CashFakeEntry', $parameter);
        $cashFakeEntry = $entry->toArray();

        $this->assertEquals($memo, $cashFakeEntry['memo']);

        $entry = $em->find('BBDurianBundle:CashFakeTransferEntry', $parameter);

        $this->assertEquals($memo, $entry->getMemo());

        $entry = $em->find('BBDurianBundle:CashFakeTrans', 1);

        $this->assertEquals($memo, $entry->getMemo());
    }

    /**
     * 測試修改歷史資料庫明細備註
     */
    public function testSetHisEntryMemo()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:CashFakeEntry');

        $id = 1;
        $at = '20130101120000';
        $memo = "Setting history entry's memo";

        $output = $repo->setHisEntryMemo($id, $at, $memo);

        $parameter = [
            'id' => 1,
            'at' => '20130101120000'
        ];

        $entry = $em->find('BBDurianBundle:CashFakeEntry', $parameter);

        $cashFakeEntry = $entry->toArray();

        $this->assertEquals($memo, $cashFakeEntry['memo']);
    }

    /**
     * 測試取得時間區間內同refId下，交易明細筆數低於2筆
     */
    public function testGetCountEntriesBelowTwo()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:CashFakeEntry');

        // 調整資料
        $parameter = [
            'id' => 1,
            'at' => '20130101120000'
        ];

        $entry = $em->find('BBDurianBundle:CashFakeEntry', $parameter);
        $entry->setRefId(123);
        $em->persist($entry);

        $parameter = [
            'id' => 2,
            'at' => '20130101120000'
        ];

        $entry = $em->find('BBDurianBundle:CashFakeEntry', $parameter);
        $entry->setRefId(456);
        $em->persist($entry);

        $em->flush();

        $opcode = [1003, 1006];
        $refId = [123, 456];
        $time = [
            'start' => '20130101120000',
            'end' => '20130101120010'
        ];

        $output = $repo->getCountEntriesBelowTwo($opcode, $refId, $time);

        $this->assertEquals(123, $output[0]['ref_id']);
        $this->assertEquals(1, $output[0]['entry_total']);
        $this->assertEquals(456, $output[1]['ref_id']);
        $this->assertEquals(1, $output[1]['entry_total']);
    }

    /**
     * 測試取得ref_id區間內的假現金明細總合和其總筆數
     */
    public function testSumAndCountEntryAmountWithRefId()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:CashFakeEntry');

        // 新增測試資料
        $cashFake = $em->find('BBDurianBundle:CashFake', 1);

        $entry = new CashFakeEntry($cashFake, 40000, -500);
        $entry->setId(6);
        $entry->setRefId(123);
        $em->persist($entry);
        $entry->setAt('20150226135000');

        $entry = new CashFakeEntry($cashFake, 40001, 500);
        $entry->setId(7);
        $entry->setRefId(123);
        $em->persist($entry);
        $entry->setAt('20150226135000');

        $cashFake = $em->find('BBDurianBundle:CashFake', 2);

        $entry = new CashFakeEntry($cashFake, 40000, -500);
        $entry->setId(8);
        $entry->setRefId(456);
        $em->persist($entry);
        $entry->setAt('20150226135000');

        $entry = new CashFakeEntry($cashFake, 40001, 1000);
        $entry->setId(9);
        $entry->setRefId(456);
        $em->persist($entry);
        $entry->setAt('20150226135000');

        $em->flush();

        $parameter = [
            'opcode' => [40000, 40001],
            'ref_id_begin' => 123,
            'ref_id_end' => 456,
            'start_time' => '20150226000000',
            'end_time' => '20150227000000'
        ];

        // 假現金明細總合
        $output = $repo->sumEntryAmountWithRefId($parameter, 0, 5);

        $this->assertEquals(123, $output[0]['ref_id']);
        $this->assertEquals(0, $output[0]['amount']);
        $this->assertEquals(40001, $output[0]['opcode']);
        $this->assertEquals('', $output[0]['memo']);
        $this->assertEquals(7, $output[0]['user_id']);

        $this->assertEquals(456, $output[1]['ref_id']);
        $this->assertEquals(500, $output[1]['amount']);
        $this->assertEquals(40001, $output[1]['opcode']);
        $this->assertEquals('', $output[1]['memo']);
        $this->assertEquals(8, $output[1]['user_id']);

        // 假現金明細總合的總筆數
        $output = $repo->countSumEntryAmountWithRefId($parameter);

        $this->assertEquals(2, $output);
    }

    /**
     * 測試取得ref_id區間內的假現金明細和其總筆數
     */
    public function testGetAndCountEntryWithRefId()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:CashFakeEntry');

        $parameter = [
            'opcode' => [1001],
            'ref_id_begin' => 0,
            'ref_id_end' => 5,
            'start_time' => '20140616000000',
            'end_time' => '20140617000000'
        ];

        // 假現金明細
        $output = $repo->getEntryWithRefId($parameter, 0, 2);

        $this->assertEquals(1, $output[0]['ref_id']);
        $this->assertEquals(500, $output[0]['amount']);
        $this->assertEquals(1001, $output[0]['opcode']);
        $this->assertEquals(8, $output[0]['user_id']);
        $this->assertEmpty($output[0]['memo']);

        $this->assertEquals(2, $output[1]['ref_id']);
        $this->assertEquals(1000, $output[1]['amount']);
        $this->assertEquals(1001, $output[1]['opcode']);
        $this->assertEquals(8, $output[1]['user_id']);
        $this->assertEquals('123', $output[1]['memo']);

        // 假現金明細的總筆數
        $output = $repo->getCountEntryWithRefId($parameter);

        $this->assertEquals(2, $output);
    }

    /**
     * 測試取得假現金明細在時間區間內的ref_id和其總筆數
     */
    public function testGetAndCountCashFakeEntryRefId()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:CashFakeEntry');

        $parameter = [
            'opcode' => [1001, 1003, 1006],
            'start' => '20120101000000',
            'end' => '20130102000000'
        ];

        $output = $repo->getCashFakeEntryRefId($parameter, 0, 5);

        $this->assertEquals(5150840307, $output[0]['refId']);
        $this->assertEquals(5150840544, $output[1]['refId']);
        $this->assertEquals(1899192866, $output[2]['refId']);
        $this->assertEquals(0, $output[3]['refId']);

        // ref_id總筆數
        $output = $repo->getCountCashFakeEntryRefId($parameter);

        $this->assertEquals(4, $output);
    }

    /**
     * 測試取得時間區間內的假現金明細和其總筆數
     */
    public function testGetAndCountEntryWithTime()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:CashFakeEntry');

        $parameter = [
            'opcode' => [1001, 1002],
            'start_time' => '20120101000000',
            'end_time' => '20130102000000',
            'domain' => 2,
            'user_id' => 8
        ];

        $output = $repo->getEntryWithTime($parameter, 0, 2);

        $this->assertEquals(0, $output[0]['ref_id']);
        $this->assertEquals(100, $output[0]['amount']);
        $this->assertEquals(1001, $output[0]['opcode']);
        $this->assertEquals(8, $output[0]['user_id']);

        $this->assertEquals(0, $output[1]['ref_id']);
        $this->assertEquals(80, $output[1]['amount']);
        $this->assertEquals(1002, $output[1]['opcode']);
        $this->assertEquals(8, $output[1]['user_id']);

        // 假現金明細的總筆數
        $output = $repo->getCountEntryWithTime($parameter);

        $this->assertEquals(2, $output);
    }
}
