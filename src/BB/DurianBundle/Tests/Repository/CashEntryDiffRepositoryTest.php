<?php

namespace BB\DurianBundle\Tests\Repository;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class CashEntryDiffRepositoryTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashEntryDiffData'
        ];

        $this->loadFixtures($classnames);
    }

    /**
     * 測試計算CashEntryDiff數量
     */
    public function testCountNumOf()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:CashEntryDiff');

        $ret = $repo->countNumOf();

        $this->assertEquals(1, $ret);
    }
}
