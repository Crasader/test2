<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\MerchantSuda;

/**
 * 測試 MerchantSuda
 */
class MerchantSudaTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $domain = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();

        $param = [
            'login_alias' => 'test01',
            'alias' => 'test',
            'private_key1' => '123456',
            'private_key2' => '234567',
            'type' => 1
        ];

        $merchantSuda = new MerchantSuda($domain, $param);
        $now = new \DateTime('now');

        $ret = $merchantSuda->toArray();
        $this->assertEquals('test01', $ret['login_alias']);
        $this->assertEquals('test', $ret['alias']);
        $this->assertEquals('123456', $ret['private_key1']);
        $this->assertEquals('234567', $ret['private_key2']);
        $this->assertEquals(1, $ret['type']);
        $this->assertFalse($ret['enable']);
        $this->assertFalse($ret['removed']);
        $this->assertEquals($now->format(\DateTime::ISO8601), $ret['created_at']);

        $merchantSuda->setId(123);
        $this->assertEquals(123, $merchantSuda->getId());

        $merchantSuda->setDomain(213);
        $this->assertEquals(213, $merchantSuda->getDomain());

        $merchantSuda->setLoginAlias('login_alias');
        $this->assertEquals('login_alias', $merchantSuda->getLoginAlias());

        $merchantSuda->setAlias('test123');
        $this->assertEquals('test123', $merchantSuda->getAlias());

        $merchantSuda->setPrivateKey1('new_key_01');
        $this->assertEquals('new_key_01', $merchantSuda->getPrivateKey1());

        $merchantSuda->setPrivateKey2('new_key_02');
        $this->assertEquals('new_key_02', $merchantSuda->getPrivateKey2());

        $merchantSuda->setType(2);
        $this->assertEquals(2, $merchantSuda->getType());

        $merchantSuda->enable();
        $this->assertTrue($merchantSuda->isEnabled());

        $merchantSuda->disable();
        $this->assertFalse($merchantSuda->isEnabled());

        $merchantSuda->remove();
        $this->assertTrue($merchantSuda->isRemoved());

        $merchantSuda->recover();
        $this->assertFalse($merchantSuda->isRemoved());

        $merchantSuda->setCreatedAt('2013-01-11');
        $this->assertEquals('2013-01-11', $merchantSuda->getCreatedAt());;
    }
}
