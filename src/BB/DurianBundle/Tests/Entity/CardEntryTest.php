<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\Card;
use BB\DurianBundle\Entity\User;

class CardEntryTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasicGetFunction()
    {
        $user = new User();
        $user->setId(99);

        $card = new Card($user);
        $entry = $card->addEntry(9901, 'IRONMAN', 100, '12345'); // 9901 TRADE_IN
        $entry->setId(1);
        $time = new \DateTime('now');
        $entry->setCreatedAt($time);
        $entry->setCardVersion(1);

        $this->assertEquals($user->getId(), $entry->getUserId());
        $this->assertEquals(1, $entry->getId());
        $this->assertEquals($card, $entry->getCard());
        $this->assertEquals(9901, $entry->getOpcode());
        $this->assertEquals('IRONMAN', $entry->getOperator());
        $this->assertEquals($time, $entry->getCreatedAt());
        $this->assertEquals(100, $entry->getAmount());
        $this->assertEquals(100, $entry->getBalance());
        $this->assertEquals('12345', $entry->getRefId());
        $this->assertEquals(1, $entry->getCardVersion());

        $time->add(new \DateInterval('PT1M'));
        $entry->setCreatedAt($time);

        $this->assertEquals($time, $entry->getCreatedAt());

        $entryClone = $card->addEntry(9901, 'IRONMAN', 100, '0'); // 9901 TRADE_IN
        $entryClone->setId(1);
        $entryClone->setCreatedAt($time);

        $cardEntryArray = $entryClone->toArray();

        $this->assertEquals(1, $cardEntryArray['id']);
        $this->assertEquals(0, $cardEntryArray['card_id']);
        $this->assertEquals(99, $cardEntryArray['user_id']);
        $this->assertEquals(9901, $cardEntryArray['opcode']);
        $this->assertEquals(100, $cardEntryArray['amount']);
        $this->assertEquals(200, $cardEntryArray['balance']);
        $this->assertEquals('IRONMAN', $cardEntryArray['operator']);
        $this->assertEquals('', $cardEntryArray['ref_id']);
        $this->assertEquals($time, new \DateTime($cardEntryArray['created_at']));
    }
}
