<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\DomainAutoRemit;
use BB\DurianBundle\Entity\AutoRemit;

class DomainAutoRemitTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $autoRemit = new AutoRemit('BB', 'BB自動認款');

        $domainAutoRemit = new DomainAutoRemit(1, $autoRemit);
        $domainAutoRemitArray = $domainAutoRemit->toArray();

        $this->assertEquals('1', $domainAutoRemitArray['domain']);
        $this->assertNull($domainAutoRemitArray['auto_remit_id']);
        $this->assertTrue($domainAutoRemitArray['enable']);
    }

    /**
     * 測試getter & setter
     */
    public function testGetterAndSetter()
    {
        $autoRemit = new AutoRemit('BB', 'BB自動認款');

        $domainAutoRemit = new DomainAutoRemit(1, $autoRemit);
        $this->assertEquals('1', $domainAutoRemit->getDomain());
        $this->assertNull($domainAutoRemit->getAutoRemitId());
        $this->assertTrue($domainAutoRemit->getEnable());
        $this->assertEquals('', $domainAutoRemit->getApiKey());

        $domainAutoRemit->setDomain(2);
        $domainAutoRemit->setApiKey('thisisapikey');
        $domainAutoRemit->setEnable(false);
        $this->assertEquals(2, $domainAutoRemit->getDomain());
        $this->assertEquals('thisisapikey', $domainAutoRemit->getApiKey());
        $this->assertFalse($domainAutoRemit->getEnable());
    }
}
