<?php

namespace BB\DurianBundle\Tests\Exchange;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class ExchangeTest extends WebTestCase
{
    public function setUp()
    {
        $classes = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadExchangeData'
        ];
        $this->loadFixtures($classes, 'share');
    }

    /**
     * 測試幣別進行匯率轉換
     */
    public function testExchangeCreditByCurrency()
    {
        $exchange = $this->getContainer()->get('durian.exchange');

        $creditData = [
            'line' => 5,
            'balance' => 100
        ];

        $creditData = $exchange->exchangeCreditByCurrency($creditData, 901, new \DateTime('2010-12-15 12:00:00'));
        $this->assertEquals(22, $creditData['line']);
        $this->assertEquals('448.43', $creditData['balance']);
    }

    /**
     * 測試匯率轉換時傳入不存在的幣別
     */
    public function testExchangeWithNonExistCurrency()
    {
        $this->setExpectedException('RuntimeException', 'No such exchange', 470010);

        $exchange = $this->getContainer()->get('durian.exchange');

        $creditData = [
            'line' => 5,
            'balance' => 100
        ];

        $exchange->exchangeCreditByCurrency($creditData, 9999);
    }
}
