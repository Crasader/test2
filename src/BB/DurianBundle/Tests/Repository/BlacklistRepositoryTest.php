<?php

namespace BB\DurianBundle\Tests\Repository;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class BlacklistRepositoryTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = ['BB\DurianBundle\Tests\DataFixtures\ORM\LoadBlacklistData'];

        $this->loadFixtures($classnames, 'share');
    }

    /**
     * 測試依條件回傳黑名單
     */
    public function testgetBlacklistSingleBy()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $repo = $emShare->getRepository('BBDurianBundle:Blacklist');

        $criteria['identity_card'] = '55665566';

        $result = $repo->getBlacklistSingleBy($criteria);

        $this->assertEquals(2, $result->getId());
        $this->assertEquals('55665566', $result->getidentityCard());

        $criteria = [];
        $criteria['telephone'] = '0911123456';

        $result = $repo->getBlacklistSingleBy($criteria);

        $this->assertEquals(4, $result->getId());
        $this->assertEquals('0911123456', $result->getTelephone());

        $criteria = [];
        $criteria['system_lock'] = true;

        $result = $repo->getBlacklistSingleBy($criteria);

        $this->assertEquals(6, $result->getId());
        $this->assertTrue($result->isSystemLock());
    }
}
