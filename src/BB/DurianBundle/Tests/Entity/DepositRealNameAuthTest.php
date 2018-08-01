<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\DepositRealNameAuth;

class DepositRealNameAuthTest extends DurianTestCase
{
    /**
     * 測試基本功能
     */
    public function testBasic()
    {
        $encryptText = 'abcdefghijklmnopqrstuvwxyz123456';

        $depositRealNameAuth = new DepositRealNameAuth($encryptText);

        $this->assertNull($depositRealNameAuth->getId());
        $this->assertEquals($encryptText, $depositRealNameAuth->getEncryptText());
    }
}
