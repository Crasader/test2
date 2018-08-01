<?php

namespace BB\DurianBundle\Tests\Repository;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\CashFakeTrans;

/**
 * 測試CashFakeTransRepository
 */
class CashFakeTransRepositoryTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeData',
        ];

        $this->loadFixtures($classnames);
    }

    /**
     * 測試取得Id最大值
     */
    public function testGetMaxId()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');

        $cashfake = $em->find('BBDurianBundle:CashFake', 1);

        $repo = $em->getRepository('BBDurianBundle:CashFakeTrans');
        $maxId = $repo->getMaxId();

        $entry = new CashFakeTrans($cashfake, 1001, 1000);
        $entry->setId($maxId + 1);
        $entry->setRefId(1);
        $em->persist($entry);

        $em->flush();

        $this->assertEquals($entry->getId(), $repo->getMaxId());
    }
}
