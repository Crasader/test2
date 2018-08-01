<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\RemitAutoConfirm;

class RemitAutoConfirmTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $remitEntry = $this->getMockBuilder('BB\DurianBundle\Entity\RemitEntry')
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();
        $remitEntry->expects($this->any())
            ->method('getId')
            ->willReturn(5);

        $remitAutoConfirm = new RemitAutoConfirm($remitEntry, '8704746');
        $racArray = $remitAutoConfirm->toArray();

        $this->assertEquals(5, $racArray['remit_entry_id']);
        $this->assertEquals('8704746', $racArray['auto_confirm_id']);
    }

    /**
     * 測試 getter
     */
    public function testGetter()
    {
        $remitEntry = $this->getMockBuilder('BB\DurianBundle\Entity\RemitEntry')
            ->disableOriginalConstructor()
            ->getMock();

        $remitAutoConfirm = new RemitAutoConfirm($remitEntry, 8704746);

        $this->assertEquals($remitEntry, $remitAutoConfirm->getRemitEntry());
        $this->assertEquals('8704746', $remitAutoConfirm->getAutoConfirmId());
    }
}
