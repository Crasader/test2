<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\CardDepositTracking;

/**
 * 測試租卡入款查詢
 */
class CardDepositTrackingTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $cdTracking = new CardDepositTracking(201501160000000001, 1, 5566);

        $this->assertEquals(201501160000000001, $cdTracking->getEntryId());
        $this->assertEquals(1, $cdTracking->getPaymentGatewayId());
        $this->assertEquals(5566, $cdTracking->getMerchantCardId());
        $this->assertEquals(0, $cdTracking->getRetry());

        $cdTracking->addRetry();
        $this->assertEquals(1, $cdTracking->getRetry());
    }
}
