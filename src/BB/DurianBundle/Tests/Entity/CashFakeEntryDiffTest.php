<?php
namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\CashFakeEntryDiff;

class CashFakeEntryDiffTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $entry = new CashFakeEntryDiff();

        $entry->setId(0);
        $this->assertEquals(0, $entry->getId());
        $this->assertTrue($entry->getCheckTime() instanceof \DateTime);

        $array = $entry->toArray();
        $checkTime = $entry->getCheckTime()->format(\DateTime::ISO8601);

        $this->assertEquals(0, $array['id']);
        $this->assertEquals($checkTime, $array['check_time']);
    }
}
