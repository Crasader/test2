<?php

namespace BB\DurianBundle\Tests\Repository;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\CardEntry;

/**
 * 測試CardEntryRepository
 */
class CardEntryRepositoryTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCardData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCardEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData'
        ];

        $this->loadFixtures($classnames);
    }

    /**
     * 測試取得Id最大值
     */
    public function testGetMaxId()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $card = $em->find('BBDurianBundle:Card', 7);

        $repo = $em->getRepository('BBDurianBundle:CardEntry');
        $maxId = $repo->getMaxId();

        $entry = new CardEntry($card, 9901, 3000, 3000, 'company');
        $entry->setId($maxId + 1);
        $time = new \DateTime('2012-01-01 12:00:00');
        $entry->setCreatedAt($time);
        $em->persist($entry);

        $em->flush();

        $this->assertEquals($entry->getId(), $repo->getMaxId());
    }

    /**
     * 測試用上層使用者取得租卡交易紀錄
     */
    public function testGetEntriesByParent()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:CardEntry');

        $criteria = [
            'parent_id' => 2,
            'depth' => 6,
            'opcode' => [20001,9901],
            'start_time' => '2012-01-01 12:00:00',
            'end_time' => '2012-01-03 12:00:00'
        ];

        $orderBy = ['opcode' => 'DESC'];

        $limit = [
            'first_result' => 0,
            'max_results' => 2
        ];

        $entries = $repo->getEntriesByParent($criteria, $orderBy, $limit);

        $this->assertEquals(3, $entries[0]->getId());
        $this->assertEquals(7, $entries[0]->getCard()->getId());
        $this->assertEquals(20001, $entries[0]->getOpcode());
        $this->assertEquals(1, $entries[1]->getId());
        $this->assertEquals(7, $entries[1]->getCard()->getId());
        $this->assertEquals(9901, $entries[1]->getOpcode());
        $this->assertEquals(2, count($entries));
    }
}
