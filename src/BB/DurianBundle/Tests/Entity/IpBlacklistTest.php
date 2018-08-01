<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\IpBlacklist;

class IpBlacklistTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $ib = new IpBlacklist(2, '127.0.0.1');

        $this->assertEquals(2, $ib->getDomain());
        $this->assertEquals('127.0.0.1', $ib->getIp());
        $this->assertFalse($ib->isRemoved());
        $this->assertFalse($ib->isCreateUser());
        $this->assertFalse($ib->isLoginError());
        $this->assertNotNull($ib->getCreatedAt());
        $this->assertNotNull($ib->getModifiedAt());
        $this->assertEquals($ib->getOperator(), '');

        //移除IP封鎖列表
        $ib->remove('testOp');

        $ib->setCreateUser(true);
        $ib->setLoginError(true);

        $array = $ib->toArray();

        $createdAt = $ib->getCreatedAt()->format(\DateTime::ISO8601);
        $modifiedAt = $ib->getModifiedAt()->format(\DateTime::ISO8601);

        $this->assertEquals($ib->getId(), $array['id']);
        $this->assertEquals($ib->getDomain(), $array['domain']);
        $this->assertEquals('127.0.0.1', $array['ip']);
        $this->assertTrue($array['removed']);
        $this->assertTrue($array['create_user']);
        $this->assertTrue($array['login_error']);
        $this->assertEquals($createdAt, $array['created_at']);
        $this->assertEquals($modifiedAt, $array['modified_at']);
        $this->assertEquals('testOp', $array['operator']);
    }
}
