<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\Blacklist;

class BlacklistTest extends DurianTestCase
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
        $blacklist->setModifiedAt($at);
        $blacklist->setSystemLock(false);
        $blacklist->setControlTerminal(true);

        $this->assertEquals(456, $blacklist->getDomain());
        $this->assertFalse($blacklist->IsWholeDomain());
        $this->assertEquals('0x9d3016517d294a06a2193e8cae2e108dt56f4j3D', $blacklist->getAccount());
        $this->assertEquals('87654321', $blacklist->getIdentityCard());
        $this->assertEquals('三一三', $blacklist->getNameReal());
        $this->assertEquals('1020304050', $blacklist->getTelephone());
        $this->assertEquals('pop@112.com', $blacklist->getEmail());
        $this->assertEquals('128.0.0.1', $blacklist->getIp());
        $this->assertEquals($at, $blacklist->getCreatedAt());
        $this->assertEquals($at, $blacklist->getModifiedAt());
        $this->assertFalse($blacklist->isSystemLock());
        $this->assertTrue($blacklist->isControlTerminal());
    }
}
