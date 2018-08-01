<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\WithdrawError;

/**
 * 測試第三方出款錯誤訊息
 */
class WithdrawErrorTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $withdrawError = new WithdrawError(6, 9527, 'thankYou9527');

        $this->assertEquals(6, $withdrawError->getEntryId());
        $this->assertEquals(9527, $withdrawError->getErrorCode());
        $this->assertEquals('thankYou9527', $withdrawError->getErrorMessage());

        $withdrawError->setErrorCode(9453);
        $this->assertEquals(9453, $withdrawError->getErrorCode());

        $withdrawError->setErrorMessage('thankYou9453');
        $this->assertEquals('thankYou9453', $withdrawError->getErrorMessage());

        $withdrawError->setAt(new \DateTime());
        $this->assertEquals(new \DateTime(), $withdrawError->getAt());
    }
}
