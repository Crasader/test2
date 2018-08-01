<?php

namespace BB\DurianBundle\Tests\Repository;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class CardErrorRepositoryTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCardErrorData'
        ];

        $this->loadFixtures($classnames, 'share');
    }

    /**
     * 測試計算CardError數量
     */
    public function testCountNumOf()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $repo = $em->getRepository('BBDurianBundle:CardError');

        $ret = $repo->countNumOf();

        $this->assertEquals(2, $ret);
    }
}
