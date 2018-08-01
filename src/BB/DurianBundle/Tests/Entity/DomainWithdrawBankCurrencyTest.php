<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\BankInfo;
use BB\DurianBundle\Entity\BankCurrency;
use BB\DurianBundle\Entity\DomainWithdrawBankCurrency;

class DomainWithdrawBankCurrencyTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testNewDomainWithdrawBankCurrency()
    {
        $bankname = '中國銀行';
        $currency = 156; // CNY

        $user = new User();
        $user->setId(1);

        $bankInfo = new BankInfo($bankname);
        $bankCurrency = new BankCurrency($bankInfo, $currency);
        $domainBank = new DomainWithdrawBankCurrency($user, $bankCurrency);

        $array = $domainBank->toArray();
        $this->assertNull($array['id']);
        $this->assertEquals(1, $array['domain']);
        $this->assertNull($array['bank_currency_id']);
    }
}
