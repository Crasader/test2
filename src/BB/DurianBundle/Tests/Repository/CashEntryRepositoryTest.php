<?php

namespace BB\DurianBundle\Tests\Repository;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\CashEntry;

/**
 * 測試CashEntryRepository
 */
class CashEntryRepositoryTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData'
        ];

        $this->loadFixtures($classnames);
        $this->loadFixtures([], 'entry');
    }

    /**
     * 測試取得Id最大值
     */
    public function testGetMaxId()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $cash = $em->find('BBDurianBundle:Cash', 1);
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');

        $repo = $emEntry->getRepository('BBDurianBundle:CashEntry');
        $maxId = $repo->getMaxId();

        $entry = new CashEntry($cash, 1001, 1000);
        $entry->setId($maxId + 1);
        $entry->setRefId(238030097);
        $time = new \DateTime('2013-01-01 12:00:00');
        $entry->setCreatedAt($time);
        $entry->setAt(20130101120000);
        $emEntry->persist($entry);

        $emEntry->flush();

        $this->assertEquals($entry->getId(), $repo->getMaxId());
    }
}
