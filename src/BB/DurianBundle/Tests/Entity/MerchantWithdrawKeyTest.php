<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\MerchantWithdrawKey;

/**
 * 測試 MerchantWithdrawKey
 */
class MerchantWithdrawKeyTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $merchantWithdraw = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantWithdraw')
            ->disableOriginalConstructor()
            ->getMock();

        $entry = new MerchantWithdrawKey($merchantWithdraw, 'public', 'testtest');
        $this->assertEquals($merchantWithdraw, $entry->getMerchantWithdraw());
        $this->assertNull($entry->getId());
        $this->assertEquals('public', $entry->getKeyType());
        $this->assertEquals('testtest', $entry->getFileContent());

        $entry->setFileContent('123456789');
        $this->assertEquals('123456789', $entry->getFileContent());
    }
}
