<?php

namespace BB\DurianBundle\Tests\Repository;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\CashTrans;

/**
 * 測試CashTransRepository
 */
class CashTransRepositoryTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
        ];

        $this->loadFixtures($classnames);
    }

    /**
     * 測試取得Id最大值
     */
    public function testGetMaxId()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $cash = $em->find('BBDurianBundle:Cash', 1);

        $repo = $em->getRepository('BBDurianBundle:CashTrans');
        $maxId = $repo->getMaxId();

        $entry = new CashTrans($cash, 1001, 1000);
        $entry->setId($maxId + 1);
        $entry->setRefId(1);
        $em->persist($entry);

        $em->flush();

        $this->assertEquals($entry->getId(), $repo->getMaxId());
    }
}
