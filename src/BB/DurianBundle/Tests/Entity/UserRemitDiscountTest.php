<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\UserRemitDiscount;

class UserRemitDiscountTest extends DurianTestCase
{
    /**
     * 測試新增會員匯款優惠設定
     */
    public function testNewUserRemitDiscount()
    {
        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->any())
            ->method('getId')
            ->will($this->returnValue('1'));
        $time = new \DateTime(date('2012-01-03'));

        $remitDiscount = new UserRemitDiscount($user, $time);
        $remitDiscount->addDiscount(10);

        $this->assertEquals('2012-01-02T12:00:00+0800', $remitDiscount->getPeriodAt()->format(\DateTime::ISO8601));
        $this->assertEquals(1, $remitDiscount->getUserId());
        $this->assertEquals(10, $remitDiscount->getDiscount());

        $ret = $remitDiscount->toArray();
        $this->assertEquals('2012-01-02T12:00:00+0800', $ret['period_at']);
        $this->assertEquals(1, $ret['user_id']);
        $this->assertEquals(10, $ret['discount']);
    }
}
