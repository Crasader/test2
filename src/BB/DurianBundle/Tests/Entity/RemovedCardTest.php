<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\Card;
use BB\DurianBundle\Entity\RemovedUser;
use BB\DurianBundle\Entity\RemovedCard;

class RemovedCardTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testRemovedCardBasic()
    {
        $userId = 3;
        $user = new User();
        $userRefl = new \ReflectionClass($user);
        $userReflProperty = $userRefl->getProperty('id');
        $userReflProperty->setAccessible(true);
        $userReflProperty->setValue($user, $userId);

        $cardId = 2;
        $card = new Card($user);
        $cardRefl = new \ReflectionClass($card);
        $cardReflProperty = $cardRefl->getProperty('id');
        $cardReflProperty->setAccessible(true);
        $cardReflProperty->setValue($card, $cardId);

        $removedUser = new RemovedUser($user);
        $removedCard = new RemovedCard($removedUser, $card);

        $this->assertEquals($cardId, $removedCard->getId());
        $this->assertEquals($removedUser, $removedCard->getRemovedUser());

        $array = $removedCard->toArray();

        $this->assertEquals($cardId, $array['id']);
        $this->assertEquals($userId, $array['user_id']);
    }

    /**
     * 測試租卡指派錯誤
     */
    public function testCardNotBelongToThisUser()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Card not belong to this user',
            150010159
        );

        $user1 = new User();
        $user1->setId(1);
        $card = new Card($user1);

        $user2 = new User();
        $user2->setId(2);
        $removedUser = new RemovedUser($user2);
        $removedCard = new RemovedCard($removedUser, $card);
    }
}
