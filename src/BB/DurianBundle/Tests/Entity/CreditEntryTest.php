<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\CreditEntry;

/**
 * 測試 CreditEntry
 */
class CreditEntryTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $now = new \DateTime();
        $userId =1;
        $groupNum = 2;
        $opcode = 3;
        $amount = 50;
        $balance = 100;
        $periodAt = $now;
        $refId = 0;
        $memo = 'This is a Memo';
        $line = 1000;
        $creditId = 999;
        $at = 10;
        $totalLine = 2000;
        $creditVersion = 1;

        $entry = new CreditEntry($userId, $groupNum, $opcode, $amount, $balance, $periodAt);

        $this->assertEquals(0, $entry->getId());
        $this->assertEquals($now, $entry->getPeriodAt());

        $entry->setCreditId($creditId);
        $entry->setAt($at);

        $entry->setRefId($refId);
        $this->assertEquals($refId, $entry->getRefId());

        $entry->setCreditVersion($creditVersion);
        $this->assertEquals($creditVersion, $entry->getCreditVersion());

        $entry->setMemo($memo);
        $entry->setLine($line);
        $entry->setTotalLine($totalLine);

        $array = $entry->toArray();

        $this->assertEquals(0, $array['id']);
        $this->assertEquals($creditId, $array['credit_id']);
        $this->assertEquals($userId, $array['user_id']);
        $this->assertEquals($groupNum, $array['group']);
        $this->assertEquals($opcode, $array['opcode']);
        $this->assertEquals($at, $array['at']);
        $this->assertEquals($amount, $array['amount']);
        $this->assertEquals($memo, $array['memo']);
        $this->assertEquals('', $array['ref_id']);
        $this->assertEquals($balance, $array['balance']);
        $this->assertEquals($line, $array['line']);
        $this->assertEquals($totalLine, $array['total_line']);
        $this->assertEquals($periodAt, new \DateTime($array['period_at']));
    }
}
