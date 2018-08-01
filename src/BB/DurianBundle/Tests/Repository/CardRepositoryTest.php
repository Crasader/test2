<?php

namespace BB\DurianBundle\Tests\Repository;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class CardRepositoryTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCardEntryData'
        ];

        $this->loadFixtures($classnames);
    }

    /**
     * 測試取得租卡交易紀錄
     */
    public function testGetEntriesBy()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:Card');

        $card = $em->find('BBDurianBundle:Card', 7);
        $orderBy = ['opcode' => 'DESC'];
        $opcode = [9902, 20001];
        $firstResult = 0;
        $maxResults = 1;
        $startTime = '2012-01-01 12:00:00';
        $endTime = '2012-01-03 12:00:00';

        $entries = $repo->getEntriesBy(
            $card,
            $orderBy,
            $firstResult,
            $maxResults,
            $opcode,
            $startTime,
            $endTime
        );

        $entry = $entries[0]->toArray();

        $this->assertEquals(3, $entry['id']);
        $this->assertEquals(7, $entry['card_id']);
        $this->assertEquals(20001, $entry['opcode']);

        // 測試帶入一個opcode
        $entries = $repo->getEntriesBy(
            $card,
            $orderBy,
            $firstResult,
            $maxResults,
            9902,
            $startTime,
            $endTime
        );

        $entry = $entries[0]->toArray();

        $this->assertEquals(2, $entry['id']);
        $this->assertEquals(7, $entry['card_id']);
        $this->assertEquals(9902, $entry['opcode']);
    }

    /**
     * 測試取得租卡交易紀錄筆數
     */
    public function testCountEntriesOf()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:Card');

        $card = $em->find('BBDurianBundle:Card', 7);
        $opcode = [9902, 20001];
        $startTime = '2012-01-01 12:00:00';
        $endTime = '2012-01-03 12:00:00';

        $ret = $repo->countEntriesOf(
            $card,
            $opcode,
            $startTime,
            $endTime
        );

        $this->assertEquals(2, $ret);

        // 測試帶入一個opcode
        $ret = $repo->countEntriesOf(
            $card,
            9902,
            $startTime,
            $endTime
        );

        $this->assertEquals(1, $ret);
    }

    /**
     * 測試刪除交易紀錄
     */
    public function testRemoveEntryOf()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:Card');

        $card = $em->find('BBDurianBundle:Card', 7);
        $repo->removeEntryOf($card);

        $ret = $em->getRepository('BBDurianBundle:CardEntry')->findAll();

        $this->assertEmpty($ret);
    }
}
