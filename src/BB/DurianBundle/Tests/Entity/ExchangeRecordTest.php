<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\Exchange;
use BB\DurianBundle\Entity\ExchangeRecord;

class ExchangeRecordTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testNewExchangeRecord()
    {
        $currency = 156; // CNY
        $activeAt = new \DateTime('2010-12-06 12:00:00');

        $exchange = new Exchange($currency, 0.1, 0.7, 0.2, $activeAt);
        $exchangeRecord = new ExchangeRecord($exchange, 'test');

        $this->assertEquals($currency, $exchangeRecord->getCurrency());
        $this->assertEquals($activeAt, $exchangeRecord->getActiveAt());
        $this->assertEquals('test', $exchangeRecord->getMemo());
        $this->assertTrue($exchangeRecord->getModifiedAt() instanceof \DateTime);

        $array = $exchangeRecord->toArray();

        $this->assertNull($array['id']);
        $this->assertNull($array['exchange_id']);
        $this->assertEquals('CNY', $array['currency']);
        $this->assertEquals(0.1, $array['buy']);
        $this->assertEquals(0.7, $array['sell']);
        $this->assertEquals(0.2, $array['basic']);
        $this->assertEquals($activeAt, new \DateTime($array['active_at']));
        $this->assertEquals($exchangeRecord->getModifiedAt()->format(\DateTime::ISO8601), $array['modified_at']);
    }
}
