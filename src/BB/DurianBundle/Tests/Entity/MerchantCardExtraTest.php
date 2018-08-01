<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\MerchantCardExtra;

/**
 * 測試 MerchantCardExtra
 */
class MerchantCardExtraTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $id = 99;
        $name = 'name';
        $value = 'value';

        $mb = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCard');
        $mb->disableOriginalConstructor();
        $mb->setMethods(['getId']);

        $merchantCard = $mb->getMock();
        $merchantCard->expects($this->any())->method('getId')->willReturn($id);

        $extra = new MerchantCardExtra($merchantCard, $name, $value);

        $this->assertEquals($merchantCard, $extra->getMerchantCard());
        $this->assertEquals($name, $extra->getName());
        $this->assertEquals($value, $extra->getValue());

        $array = $extra->toArray();
        $this->assertEquals($id, $array['merchant_card_id']);
        $this->assertEquals($name, $array['name']);
        $this->assertEquals($value, $array['value']);

        $extra->setValue(689);
        $this->assertEquals(689, $extra->getValue());
    }
}
