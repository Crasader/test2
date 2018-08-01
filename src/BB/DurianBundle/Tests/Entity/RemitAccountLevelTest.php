<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\RemitAccount;
use BB\DurianBundle\Entity\RemitAccountLevel;

class RemitAccountLevelTest extends DurianTestCase
{
    /**
     * 測試新增修改
     */
    public function testNewAndSet()
    {
        $remitAccount = new RemitAccount(2, 1, 1, '1234567890', 156);
        $remitAccountLevel = new RemitAccountLevel($remitAccount, 0, 2);

        $data = $remitAccountLevel->toArray();

        $this->assertEquals($remitAccount->getId(), $data['remit_account_id']);
        $this->assertEquals(0, $data['level_id']);
        $this->assertEquals(2, $data['order_id']);
        $this->assertNull($data['version']);

        $remitAccountLevel->setOrderId(1314);

        $this->assertEquals(1314, $remitAccountLevel->getOrderId());
    }
}
