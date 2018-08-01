<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\Card;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\RemovedCard;
use BB\DurianBundle\Entity\RemovedUser;

class CardTest extends DurianTestCase
{
    /**
     * 測試新增租卡及其功能
     */
    public function testNewCard()
    {
        $user1 = new User();
        $user1->setUsername('user1');

        $user2 = new User();
        $user2->setParent($user1);
        $user2->setUsername('user2');

        $user3 = new User();
        $user3->setParent($user2);
        $user3->setUsername('user3');

        $card = new Card($user1);

        $this->assertFalse($card->isEnabled());
        $this->assertEquals($user1, $card->getUser());
        $this->assertEquals(0, $card->getBalance());
        $this->assertEquals(0, $card->getLastBalance());
        $this->assertEquals(0, $card->getPercentage());

        $card->addEntry(9901, 'IRONMAN', 500); // 9901 TRADE_IN
        $this->assertEquals(
            500,
            $card->getBalance(),
            '儲值後租卡點數錯誤'
        );
        $this->assertEquals(
            100,
            $card->getPercentage(),
            '儲值後租卡百分比錯誤'
        );

        $entries = $card->getEntries();
        $this->assertEquals(
            1,
            count($entries),
            '儲值後租卡交易紀錄筆數錯誤'
        );
        $this->assertEquals(
            500,
            $entries[0]->getAmount(),
            '儲值後租卡交易紀錄點數錯誤'
        );

        $this->assertEquals(0, $card->getEnableNum());

        $card->addEnableNum();
        $this->assertEquals(1, $card->getEnableNum());

        $card->minusEnableNum();
        $this->assertEquals(0, $card->getEnableNum());

        $cardArray = $card->toArray();

        $this->assertEquals(0, $cardArray['id']);
        $this->assertEquals(0, $cardArray['user_id']);
        $this->assertFalse($cardArray['enable']);
        $this->assertEquals(0, $cardArray['enable_num']);
        $this->assertEquals(500, $cardArray['balance']);
        $this->assertEquals(500, $cardArray['last_balance']);
        $this->assertEquals(100.0, $cardArray['percentage']);
    }

    /**
     * 測試租卡唯一性
     */
    public function testDuplicateCard()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Duplicate Card entity detected',
            150030002
        );

        $parent = $this->getMockBuilder('BB\DurianBundle\Entity\User')
                ->disableOriginalConstructor()
                ->setMethods(array('getId'))
                ->getMock();

        $user = new User($parent, 'user', 'pass', 'alias');

        $card = new Card($user);
        $card = new Card($user);
    }

    /**
     * 測試租卡開關能否正常設置
     */
    public function testCardEnableAndDisable()
    {
        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
                ->disableOriginalConstructor()
                ->getMock();

        $card = new Card($user);

        $this->assertFalse($card->isEnabled());

        $card->enable();
        $this->assertTrue($card->isEnabled());

        $card->disable();
        $this->assertFalse($card->isEnabled());
    }

    /**
     * 測試交易後資料是否正常
     */
    public function testAddEntry()
    {
        $parent = new User();

        $user = new User();
        $user->setParent($parent);

        $card = new Card($user);

        $date = new \DateTime('NOW');
        $entry = $card->addEntry(9901, 'IRONMAN', 800); // 9901 TRADE_IN
        $entry->setCreatedAt($date);

        $this->assertEquals(9901, $entry->getOpcode());
        $this->assertEquals($card, $entry->getCard());
        $this->assertEquals(800, $entry->getAmount());
        $this->assertEquals(100, $entry->getCard()->getPercentage());
        $this->assertEquals('IRONMAN', $entry->getOperator());
        $this->assertEquals($date, $entry->getCreatedAt());

        $entry = $card->addEntry(20001, 'IRONMAN', -199); // BETTING

        $this->assertEquals(-199, $entry->getAmount());
        $this->assertEquals(75, $card->getPercentage());
    }

    /**
     * 租卡點數一定要是整數
     */
    public function testAmountMustBeIntegerWhenAddEntry()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Card amount must be integer',
            150030003
        );

        $parent = new User();

        $user = new User();
        $user->setParent($parent);

        $card = new Card($user);

        $card->addEntry(9901, 'IRONMAN', '1.1'); // 9901 TRADE_IN
    }

    /**
     * 點數不足會跳出例外警告
     */
    public function testCardBalanceCanNotBeNegative()
    {
        $this->setExpectedException('RuntimeException', 'Not enough balance', 150030020);

        $parent = new User();

        $user = new User();
        $user->setParent($parent);

        $card = new Card($user);
        $card->addEntry(9901, '', 1000); // 9901 TRADE_IN

        $card->addEntry(9902, '', -1001); // 9902 TRADE_OUT
    }

    /**
     * 測試從被移除的租卡設定租卡ID
     */
    public function testSetIdFromRemovedCard()
    {
        $user1 = new User();
        $user1->setId(1);
        $card1 = new Card($user1);
        $reflection = new \ReflectionClass($card1);
        $reflectionProperty = $reflection->getProperty('id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($card1, 2);
        $this->assertEquals(2, $card1->getId());

        $removedUser = new RemovedUser($user1);
        $removedCard = new RemovedCard($removedUser, $card1);

        $user2 = new User();
        $user2->setId(1);

        $card2 = new Card($user2);
        $card2->setId($removedCard);
        $this->assertEquals($card1->getId(), $card2->getId());
    }

    /**
     * 測試從被移除的租卡設定租卡ID，但指派錯誤
     */
    public function testSetIdFromRemovedCardButNotBelongToThisUser()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Removed card not belong to this user',
            150010161
        );

        $user1 = new User();
        $user1->setId(1);
        $card1 = new Card($user1);

        $removedUser = new RemovedUser($user1);
        $removedCard = new RemovedCard($removedUser, $card1);

        $user2 = new User();
        $user2->setId(2);

        $card2 = new Card($user2);
        $card2->setId($removedCard);
    }
}
