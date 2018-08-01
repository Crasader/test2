<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\Promotion;

class PromotionTest extends DurianTestCase
{
    /**
     * 測試基本功能
     */
    public function testBasic()
    {
        $user = new User();
        $promotion = new Promotion($user);
        $promotion->setUrl('hp://isnotenough.com');
        $promotion->setOthers('hllp://goingtodead.tt');

        $result = $promotion->toArray();
        $this->assertEquals($user->getId(), $result['user_id']);
        $this->assertEquals('hp://isnotenough.com', $result['url']);
        $this->assertEquals('hllp://goingtodead.tt', $result['others']);
    }
}
