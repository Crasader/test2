<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\DomainCurrency;

class DomainCurrencyTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testNewDomainCurrency()
    {
        $id = 9527;
        $currency = 156;

        $user = new User();
        $user->setId($id);

        $domainCurrency = new DomainCurrency($user, $currency);

        $this->assertFalse($domainCurrency->isRemoved());
        $this->assertFalse($domainCurrency->isPreset());
        $this->assertEquals($id, $domainCurrency->getDomain());
        $this->assertEquals($currency, $domainCurrency->getCurrency());

        $domainCurrency->remove();

        $dcArray = $domainCurrency->toArray();
        $this->assertTrue($dcArray['removed']);
        $this->assertEquals('CNY', $dcArray['currency']);
        $this->assertFalse($dcArray['is_virtual']);

        $domainCurrency->presetOn();
        $this->assertTrue($domainCurrency->isPreset());

        $domainCurrency->presetOff();
        $this->assertFalse($domainCurrency->isPreset());
    }
}
