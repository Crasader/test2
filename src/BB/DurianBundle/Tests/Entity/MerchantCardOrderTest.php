<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\MerchantCardOrder;

class MerchantCardOrderTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $mao = new MerchantCardOrder(1, 1);
        $this->assertEquals(1, $mao->getMerchantCardId());
        $this->assertEquals(1, $mao->getOrderId());
        $this->assertEquals(0, $mao->getVersion());

        $mao->setOrderId(2);

        $array = $mao->toArray();
        $this->assertEquals(1, $array['merchant_card_id']);
        $this->assertEquals(2, $array['order_id']);
        $this->assertEquals(0, $array['version']);
    }
}
