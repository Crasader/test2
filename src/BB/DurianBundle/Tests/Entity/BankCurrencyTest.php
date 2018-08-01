<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\BankInfo;
use BB\DurianBundle\Entity\BankCurrency;

class BankCurrencyTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testNewBankCurrency()
    {
        $bankname = '中國銀行';
        $currency = 156; // CNY
        $bankInfo = new BankInfo($bankname);
        $bankCurrency = new BankCurrency($bankInfo, $currency);

        $this->assertEquals($currency, $bankCurrency->getCurrency());

        $arrayData = $bankCurrency->toArray();

        $this->assertNull($arrayData['id']);
        $this->assertNull($arrayData['bank_info_id']);
        $this->assertEquals('CNY', $arrayData['currency']);
    }
}
