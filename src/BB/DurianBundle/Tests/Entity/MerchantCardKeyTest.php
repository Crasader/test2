<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\MerchantCardKey;

/**
 * 測試 MerchantCardKey
 */
class MerchantCardKeyTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $mb = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantCard');
        $mb->disableOriginalConstructor();
        $merchantCard = $mb->getMock();

        $entry = new MerchantCardKey($merchantCard, 'public', 'testtest');
        $this->assertEquals($merchantCard, $entry->getMerchantCard());
        $this->assertEquals(0, $entry->getId());
        $this->assertEquals('public', $entry->getKeyType());
        $this->assertEquals('testtest', $entry->getFileContent());

        $entry->setFileContent('123456789');
        $this->assertEquals('123456789', $entry->getFileContent());
    }
}
