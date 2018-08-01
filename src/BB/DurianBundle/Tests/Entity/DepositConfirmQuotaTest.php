<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\DepositConfirmQuota;

class DepositConfirmQuotaTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $user = new User();
        $depositConfirmQuota = new DepositConfirmQuota($user);

        $this->assertEquals($depositConfirmQuota->getUserid(), $user->getId());
        $this->assertEquals(0, $depositConfirmQuota->getAmount());

        // 設定金額
        $depositConfirmQuota->setAmount(1000);

        $array = $depositConfirmQuota->toArray();

        $this->assertEquals(1000, $array['amount']);
    }
}