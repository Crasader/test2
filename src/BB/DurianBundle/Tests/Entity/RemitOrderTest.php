<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\RemitOrder;

class RemitOrderTest extends DurianTestCase
{
    /**
     * 測試新增一筆訂單號
     */
    public function testNewRemitOrder()
    {
        $now = new \DateTime('now');
        $remitOrder = new RemitOrder($now);

        $orderNumber = $remitOrder->getOrderNumber();
        $this->assertEquals(16, mb_strlen($orderNumber, 'utf-8'));

        $date = str_split($orderNumber, 8);
        $this->assertEquals($now->format('Ymd'), $date[0]);

        $this->assertFalse($remitOrder->isUsed());

        $remitOrder->setUsed(true);
        $this->assertTrue($remitOrder->isUsed());

        $remitOrder->setUsed(false);
        $this->assertFalse($remitOrder->isUsed());

        $remitOrderArray = $remitOrder->toArray();

        $orderNumber = $remitOrderArray['order_number'];
        $this->assertEquals(16, mb_strlen($orderNumber, 'utf-8'));
        $this->assertFalse($remitOrderArray['used']);
    }
}
