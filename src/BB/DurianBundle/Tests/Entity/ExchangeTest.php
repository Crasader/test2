<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\Exchange;

class ExchangeTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testNewExchange()
    {
        $currency = 156; // CNY
        $activeAt = new \DateTime('2010-12-06 12:00:00');

        $exchange = new Exchange($currency, 0.1, 0.7, 0.2, $activeAt);

        $this->assertEquals($currency, $exchange->getCurrency());
        $this->assertEquals(0.1, $exchange->getBuy());
        $this->assertEquals(0.7, $exchange->getSell());
        $this->assertEquals(0.2, $exchange->getBasic());
        $this->assertEquals($activeAt, $exchange->getActiveAt());

        $currency = 901; // TWD
        $activeAt = new \DateTime('2012-06-06 12:11:00');

        $exchange->setCurrency($currency);
        $exchange->setBuy(0.9);
        $exchange->setSell(0.8);
        $exchange->setBasic(0.7);
        $exchange->setActiveAt($activeAt);

        $this->assertEquals($currency, $exchange->getCurrency());
        $this->assertEquals($activeAt, $exchange->getActiveAt());

        $array = $exchange->toArray();

        $this->assertEquals(0, $array['id']);
        $this->assertEquals('TWD', $array['currency']);
        $this->assertEquals(0.9, $array['buy']);
        $this->assertEquals(0.8, $array['sell']);
        $this->assertEquals(0.7, $array['basic']);
        $this->assertEquals($activeAt, new \DateTime($array['active_at']));
    }

    /**
     * 測試匯率轉換和換回匯率
     */
    public function testConvertAndReconvert()
    {
        $currency = 156; // CNY

        $activeAt = new \DateTime('2010-12-06 12:00:00');

        $exchange = new Exchange($currency, 0.1, 0.7, 0.2, $activeAt);

        $this->assertEquals(500, $exchange->convertByBasic(100));
        $this->assertEquals(100, $exchange->reconvertByBasic(500));

        $this->assertEquals(1000, $exchange->convertBySell(700));
        $this->assertEquals(50, $exchange->reconvertByBuy(500));
    }
}
