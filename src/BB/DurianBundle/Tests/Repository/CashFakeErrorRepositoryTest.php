<?php

namespace BB\DurianBundle\Tests\Repository;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class CashFakeErrorRepositoryTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeErrorData'
        ];

        $this->loadFixtures($classnames, 'share');
    }

    /**
     * 測試計算CashFakeError數量
     */
    public function testCountNumOf()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $repo = $em->getRepository('BBDurianBundle:CashFakeError');

        $output = $repo->countNumOf();

        $this->assertEquals(2, $output);
    }
}