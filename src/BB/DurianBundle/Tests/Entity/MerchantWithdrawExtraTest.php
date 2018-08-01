<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\MerchantWithdrawExtra;

/**
 * 測試 MerchantWithdrawExtra
 */
class MerchantWithdrawExtraTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $id = 99;

        $merchantWithdraw = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantWithdraw')
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();

        $merchantWithdraw->expects($this->any())
            ->method('getId')
            ->willReturn($id);

        $extra = new MerchantWithdrawExtra($merchantWithdraw, 'name', 'value');
        $extra->setValue('123');

        $array = $extra->toArray();
        $this->assertEquals($id, $array['merchant_withdraw_id']);
        $this->assertEquals('name', $array['name']);
        $this->assertEquals('123', $array['value']);
    }

    /**
     * 測試getter
     */
    public function testGetter()
    {
        $merchantWithdraw = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantWithdraw')
            ->disableOriginalConstructor()
            ->getMock();

        $extra = new MerchantWithdrawExtra($merchantWithdraw, 'name', 'value');

        $this->assertEquals($merchantWithdraw, $extra->getMerchantWithdraw());
        $this->assertEquals('name', $extra->getName());
        $this->assertEquals('value', $extra->getValue());
    }
}
