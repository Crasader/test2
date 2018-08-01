<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\UserHasApiTransferInOut;

/**
 * 測試使用者api轉入轉出記錄
 */
class UserHasApiTransferInOutTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $record = new UserHasApiTransferInOut(8, false, false);
        $this->assertEquals(8, $record->getUserId());
        $this->assertFalse($record->isApiTransferIn());
        $this->assertFalse($record->isApiTransferOut());

        $record->setApiTransferIn(true);
        $record->setApiTransferOut(true);

        $this->assertTrue($record->isApiTransferIn());
        $this->assertTrue($record->isApiTransferOut());

        $data = $record->toArray();
        $this->assertEquals(8, $data['user_id']);
        $this->assertTrue($data['api_transfer_in']);
        $this->assertTrue($data['api_transfer_out']);
    }
}
