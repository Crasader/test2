<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\MerchantKey;

/**
 * 測試 MerchantKey
 */
class MerchantKeyTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $merchant = $this->getMockBuilder('BB\DurianBundle\Entity\Merchant')
            ->disableOriginalConstructor()
            ->getMock();

        $entry = new MerchantKey($merchant, 'public', 'testtest');
        $this->assertEquals($merchant, $entry->getMerchant());
        $this->assertEquals(0, $entry->getId());
        $this->assertEquals('public', $entry->getKeyType());
        $this->assertEquals('testtest', $entry->getFileContent());

        $entry->setFileContent('123456789');
        $this->assertEquals('123456789', $entry->getFileContent());
    }
}
