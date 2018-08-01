<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\Petition;

class PetitionTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $pe = new Petition(8, 2, 1, '達文西', "李四", 'admin');
        $userId = $pe->getUserId();

        $this->assertEquals(8, $userId);
        $this->assertEquals(2, $pe->getDomain());
        $this->assertEquals(1, $pe->getRole());
        $this->assertEquals("達文西", $pe->getValue());
        $this->assertEquals("admin", $pe->getOperator());
        $this->assertTrue($pe->isUntreated());
        $this->assertFalse($pe->isConfirm());
        $this->assertFalse($pe->isCancel());
        $this->assertNotNull($pe->getCreatedAt());
        $this->assertEquals("李四", $pe->getOldValue());

        $pe->confirm();

        $array = $pe->toArray();

        $value = $pe->getValue();
        $operator = $pe->getOperator();
        $createdAt = $pe->getCreatedAt()->format(\DateTime::ISO8601);
        $activeAt = $pe->getActiveAt()->format(\DateTime::ISO8601);

        $this->assertEquals($userId, $array['user_id']);
        $this->assertEquals($value, $array['value']);
        $this->assertEquals($operator, $array['operator']);
        $this->assertEquals($createdAt, $array['created_at']);
        $this->assertEquals($activeAt, $array['active_at']);
        $this->assertFalse($array['untreated']);
        $this->assertTrue($array['confirm']);
        $this->assertFalse($array['cancel']);

        $pe->cancel();

        $array = $pe->toArray();

        $this->assertFalse($array['untreated']);
        $this->assertTrue($array['cancel']);
    }
}
