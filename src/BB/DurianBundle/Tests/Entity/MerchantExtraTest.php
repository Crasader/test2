<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\MerchantExtra;

/**
 * 測試 MerchantExtra
 */
class MerchantExtraTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $id = 99;
        $name = 'name';
        $value = 'value';

        $merchant = $this->getMockBuilder('BB\DurianBundle\Entity\Merchant')
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();

        $merchant->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id));

        $entry = new MerchantExtra($merchant, $name, $value);
        $entry->setValue($value);

        $this->assertEquals($merchant, $entry->getMerchant());

        $array = $entry->toArray();
        $this->assertEquals($id, $array['merchant_id']);
        $this->assertEquals($name, $array['name']);
        $this->assertEquals($value, $array['value']);
    }
}
