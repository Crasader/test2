<?php

namespace BB\DurianBundle\Tests\Repository;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class CashFakeEntryDiffRepositoryTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeEntryDiffData'
        ];

        $this->loadFixtures($classnames);
    }

    /**
     * 測試計算CashFakeEntryDiff數量
     */
    public function testCountNumOf()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:CashFakeEntryDiff');

        $ret = $repo->countNumOf();

        $this->assertEquals(1, $ret);
    }
}
