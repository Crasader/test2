<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\DepositTracking;

/**
 * 測試入款查詢
 */
class DepositTrackingTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $depositTracking = new DepositTracking(201501160000000001, 1, 55688);

        $this->assertEquals(201501160000000001, $depositTracking->getEntryId());
        $this->assertEquals(1, $depositTracking->getPaymentGatewayId());
        $this->assertEquals(55688, $depositTracking->getMerchantId());
        $this->assertEquals(0, $depositTracking->getRetry());

        $depositTracking->addRetry();
        $this->assertEquals(1, $depositTracking->getRetry());
    }
}
