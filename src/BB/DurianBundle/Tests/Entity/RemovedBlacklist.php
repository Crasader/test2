<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\Blacklist;
use BB\DurianBundle\Entity\RemovedBlacklist;

class RemovedBlacklistTest extends DurianTestCase
{
    /**
     * 測試基本功能
     */
    public function testBasic()
    {
        $at = new \DateTime('2015-04-29 10:10:18');
        $blacklist = new Blacklist();
        $blacklist->setDomain(456);
        $blacklist->setWholeDomain(false);
        $blacklist->setAccount('0x9d3016517d294a06a2193e8cae2e108dt56f4j3D');
        $blacklist->setIdentityCard('87654321');
        $blacklist->setNameReal('三一三');
        $blacklist->setTelephone('1020304050');
        $blacklist->setEmail('pop@112.com');
        $blacklist->setIp('128.0.0.1');
        $blacklist->setCreatedAt($at);
        $blacklist->setSystemLock(false);
        $blacklist->setControlTerminal(true);

        $rmBlacklist = new RemovedBlacklist($blacklist);
        $rmBlacklist->setModifiedAt($at);

        $this->assertEquals(456, $rmBlacklist->getDomain());
        $this->assertFalse($rmBlacklist->IsWholeDomain());
        $this->assertEquals('0x9d3016517d294a06a2193e8cae2e108dt56f4j3D', $rmBlacklist->getAccount());
        $this->assertEquals('87654321', $rmBlacklist->getIdentityCard());
        $this->assertEquals('三一三', $rmBlacklist->getNameReal());
        $this->assertEquals('1020304050', $rmBlacklist->getTelephone());
        $this->assertEquals('pop@112.com', $rmBlacklist->getEmail());
        $this->assertEquals('128.0.0.1', $rmBlacklist->getIp());
        $this->assertEquals($at, $rmBlacklist->getCreatedAt());
        $this->assertEquals($at, $rmBlacklist->getModifiedAt());
        $this->assertFalse($rmBlacklist->isSystemLock());
        $this->assertTrue($rmBlacklist->isControlTerminal());
    }
}
