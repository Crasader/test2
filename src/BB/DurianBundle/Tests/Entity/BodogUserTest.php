<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\BodogUser;

/**
 * 測試博狗使用者
 */
class BodogUserTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $entry = new BodogUser();

        $entry->setId(1);
        $entry->setExternalId(2);
        $entry->setCurrency(156);

        $this->assertEquals(1, $entry->getId());
        $this->assertNotEmpty($entry->getCreatedAt());
        $this->assertEquals(2, $entry->getExternalId());
        $this->assertEquals(156, $entry->getCurrency());

        $array = $entry->toArray();
        $this->assertEquals(1, $array['id']);
        $this->assertEquals(2, $array['external_id']);
        $this->assertNotEmpty($array['created_at']);
        $this->assertEquals('CNY', $array['currency']);
    }
}
