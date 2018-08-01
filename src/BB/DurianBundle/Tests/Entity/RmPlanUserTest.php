<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\RmPlanUser;
use BB\DurianBundle\Entity\User;

class RmPlanUserTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $rpUser = new RmPlanUser(1, 51, 'test', 'abc');

        $this->assertEquals(1, $rpUser->getPlanId());
        $this->assertEquals(51, $rpUser->getUserId());
        $this->assertEquals('test', $rpUser->getUsername());
        $this->assertEquals('abc', $rpUser->getAlias());
        $this->assertNull($rpUser->getModifiedAt());
        $this->assertFalse($rpUser->isRemove());
        $this->assertFalse($rpUser->isCancel());
        $this->assertFalse($rpUser->isRecoverFail());
        $this->assertEmpty($rpUser->getMemo());
        $this->assertEquals(0, $rpUser->getTimeoutCount());

        $array = $rpUser->toArray();

        $this->assertEquals(1, $array['plan_id']);
        $this->assertEquals(51, $array['user_id']);
        $this->assertEquals('test', $array['username']);
        $this->assertEquals('abc', $array['alias']);
        $this->assertNull($array['modified_at']);
        $this->assertFalse($array['remove']);
        $this->assertFalse($array['cancel']);
        $this->assertEmpty($array['memo']);
        $this->assertEquals(0, $array['timeout_count']);

        $rpUser->remove();
        $rpUser->setModifiedAt(new \DateTime('now'));

        $array = $rpUser->toArray();
        $modifiedAt = $rpUser->getModifiedAt()->format(\DateTime::ISO8601);

        $this->assertEquals($modifiedAt, $array['modified_at']);
        $this->assertTrue($array['remove']);

        $rpUser->cancel();
        $rpUser->setModifiedAt(new \DateTime('now'));

        $array = $rpUser->toArray();
        $modifiedAt = $rpUser->getModifiedAt()->format(\DateTime::ISO8601);

        $this->assertEquals($modifiedAt, $array['modified_at']);
        $this->assertTrue($array['cancel']);

        $rpUser->recoverFail();
        $rpUser->setModifiedAt(new \DateTime('now'));

        $array = $rpUser->toArray();
        $modifiedAt = $rpUser->getModifiedAt()->format(\DateTime::ISO8601);

        $this->assertEquals($modifiedAt, $array['modified_at']);
        $this->assertTrue($array['recover_fail']);

        $rpUser->getBalanceFail();
        $rpUser->setModifiedAt(new \DateTime('now'));

        $array = $rpUser->toArray();
        $modifiedAt = $rpUser->getModifiedAt()->format(\DateTime::ISO8601);

        $this->assertEquals($modifiedAt, $array['modified_at']);
        $this->assertTrue($array['get_balance_fail']);

        $rpUser->curlKue();
        $this->assertTrue($rpUser->isCurlKue());

        $rpUser->setCashBalance(1.234);
        $this->assertEquals(1.234, $rpUser->getCashBalance());

        $rpUser->setCashCurrency(156);
        $this->assertEquals(156, $rpUser->getCashCurrency());

        $rpUser->setCashFakeBalance(1.234);
        $this->assertEquals(1.234, $rpUser->getCashFakeBalance());

        $rpUser->setCashFakeCurrency(156);
        $this->assertEquals(156, $rpUser->getCashFakeCurrency());

        $rpUser->setCreditLine(10);
        $this->assertEquals(10, $rpUser->getCreditLine());

        $rpUser->setErrorCode(150010020);
        $this->assertEquals(150010020, $rpUser->getErrorCode());

        $rpUser->setMemo('test');
        $this->assertEquals('test', $rpUser->getMemo());

        $rpUser->addTimeoutCount();
        $this->assertEquals(1, $rpUser->getTimeoutCount());

        $rpUser->addTimeoutCount(4);
        $this->assertEquals(5, $rpUser->getTimeoutCount());

        $rpUser->setLevel(1);
        $this->assertEquals(1, $rpUser->getLevel());

        $rpUser->setLevelAlias('未分層');
        $this->assertEquals('未分層', $rpUser->getLevelAlias());
    }
}
