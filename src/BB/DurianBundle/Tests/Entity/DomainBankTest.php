<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\BankInfo;
use BB\DurianBundle\Entity\BankCurrency;
use BB\DurianBundle\Entity\DomainBank;

class DomainBankTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testNewBankCurrency()
    {
        $bankname = '中國銀行';
        $currency = 156; // CNY

        $user = new User();
        $bankInfo = new BankInfo($bankname);
        $bankCurrency = new BankCurrency($bankInfo, $currency);
        $domainBank = new DomainBank($user, $bankCurrency);

        $this->assertNull($domainBank->getId());
        $this->assertNull($domainBank->getDomain());
        $this->assertNull($domainBank->getBankCurrencyId());
    }
}
