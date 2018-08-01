<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\RemitAccountVersion;

class RemitAccountVersionTest extends DurianTestCase
{
    /**
     * 測試新增
     */
    public function testNewAndSet()
    {
        $remitAccountVersion = new RemitAccountVersion(2);

        $this->assertNull($remitAccountVersion->getId());
        $this->assertEquals(2, $remitAccountVersion->getDomain());
        $this->assertEquals(0, $remitAccountVersion->getVersion());

        $data = $remitAccountVersion->toArray();
        $this->assertNull($data['id']);
        $this->assertEquals(2, $data['domain']);
        $this->assertEquals(0, $data['version']);
    }
}
