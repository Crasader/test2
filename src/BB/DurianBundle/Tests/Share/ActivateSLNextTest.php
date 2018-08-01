<?php

namespace BB\DurianBundle\Tests\Share;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class ActivateSLNextTest extends WebTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var \BB\DurianBundle\Share\ActivateSLNext
     */
    private $activateSLNext;

    public function setUp()
    {
        parent::setUp();

        $classnames = array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareUpdateCronData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareLimitData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareLimitNextData',
        );

        $this->loadFixtures($classnames);

        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->activateSLNext = $this->getContainer()->get('durian.activate_sl_next');
        $this->activateSLNext->disableLog();
    }

    /**
     * 測試傳回需要佔成更新的group
     */
    public function testGetUpdateCronsNeedRun()
    {
        $cronTime = new \DateTime('2011-10-10 12:00:00');
        $updateCrons = $this->activateSLNext->getUpdateCronsNeedRun($cronTime);

        $this->assertEquals(1, $updateCrons[0]->getGroupNum());
        $this->assertEquals(2, $updateCrons[1]->getGroupNum());
        $this->assertEquals(3, $updateCrons[2]->getGroupNum());
        $this->assertEquals(6, $updateCrons[3]->getGroupNum());
    }

    /**
     * 測試執行佔成更新動作
     */
    public function testUpdate()
    {
        /******* Before update *********/

        // user 4
        $sl = $this->em->find('BB\DurianBundle\Entity\User', 4)
                                ->getShareLimit(1);
        $slNext = $this->em->find('BB\DurianBundle\Entity\User', 4)
                                ->getShareLimitNext(1);
        $this->assertNotEquals($slNext->getParentUpper(), $sl->getParentUpper());

        // user 5
        $sl = $this->em->find('BB\DurianBundle\Entity\User', 5)
                                ->getShareLimit(1);
        $slNext = $this->em->find('BB\DurianBundle\Entity\User', 5)
                                ->getShareLimitNext(1);
        $this->assertNotEquals($slNext->getParentUpper(), $sl->getParentUpper());

        // user 6
        $sl = $this->em->find('BB\DurianBundle\Entity\User', 6)
                                ->getShareLimit(1);
        $slNext = $this->em->find('BB\DurianBundle\Entity\User', 6)
                                ->getShareLimitNext(1);
        $this->assertNotEquals($slNext->getParentUpper(), $sl->getParentUpper());
        $this->assertNotEquals($slNext->getUpper(), $sl->getUpper());
        $this->assertNotEquals($slNext->getParentLower(), $sl->getParentLower());

        // user 7
        $sl = $this->em->find('BB\DurianBundle\Entity\User', 7)
                                ->getShareLimit(1);
        $slNext = $this->em->find('BB\DurianBundle\Entity\User', 7)
                                ->getShareLimitNext(1);
        $this->assertNotEquals($slNext->getUpper(), $sl->getUpper());
        $this->assertNotEquals($slNext->getLower(), $sl->getLower());
        $this->assertNotEquals($slNext->getParentUpper(), $sl->getParentUpper());
        $this->assertNotEquals($slNext->getParentLower(), $sl->getParentLower());

        $sl->setMin1(199);
        $sl->setMax1(10);
        $sl->setMax2(10);

        $this->em->persist($sl);
        $this->em->flush();

        $this->assertNotEquals($slNext->getMin1(), $sl->getMin1());
        $this->assertNotEquals($slNext->getMax1(), $sl->getMax1());
        $this->assertNotEquals($slNext->getMax2(), $sl->getMax2());



        $updateCron = $this->em->getRepository('BBDurianBundle:ShareUpdateCron')
            ->findOneBy(array('groupNum' => 1));
        $this->activateSLNext->update($updateCron, new \DateTime('now'));

        $this->em->flush();
        $this->em->clear();


        /******* After update *********/

        // user 4
        $sl = $this->em->find('BB\DurianBundle\Entity\User', 4)
                                ->getShareLimit(1);
        $slNext = $this->em->find('BB\DurianBundle\Entity\User', 4)
                                ->getShareLimitNext(1);
        $this->assertEquals($slNext->getParentUpper(), $sl->getParentUpper());

        // user 5
        $sl = $this->em->find('BB\DurianBundle\Entity\User', 5)
                                ->getShareLimit(1);
        $slNext = $this->em->find('BB\DurianBundle\Entity\User', 5)
                                ->getShareLimitNext(1);
        $this->assertEquals($slNext->getParentUpper(), $sl->getParentUpper());

        // user 6
        $sl = $this->em->find('BB\DurianBundle\Entity\User', 6)
                                ->getShareLimit(1);
        $slNext = $this->em->find('BB\DurianBundle\Entity\User', 6)
                                ->getShareLimitNext(1);
        $this->assertEquals($slNext->getParentUpper(), $sl->getParentUpper());
        $this->assertEquals($slNext->getUpper(), $sl->getUpper());
        $this->assertEquals($slNext->getParentLower(), $sl->getParentLower());

        // user 7
        $sl = $this->em->find('BB\DurianBundle\Entity\User', 7)
                                ->getShareLimit(1);
        $slNext = $this->em->find('BB\DurianBundle\Entity\User', 7)
                                ->getShareLimitNext(1);
        $this->assertEquals($slNext->getUpper(), $sl->getUpper());
        $this->assertEquals($slNext->getLower(), $sl->getLower());
        $this->assertEquals($slNext->getParentUpper(), $sl->getParentUpper());
        $this->assertEquals($slNext->getParentLower(), $sl->getParentLower());

        $this->assertEquals($slNext->getMin1(), $sl->getMin1());
        $this->assertEquals($slNext->getMax1(), $sl->getMax1());
        $this->assertEquals($slNext->getMax2(), $sl->getMax2());

    }

    /**
     * 測試是否在佔成更新
     */
    public function testIsUpdating()
    {
        // share update cron存在沒有跑完的紀錄就是在佔成更新中
        $date = new \DateTime('2011-10-25 03:00:00');
        $this->assertTrue($this->activateSLNext->isUpdating($date));

        $updateCron = $this->em->find('BBDurianBundle:ShareUpdateCron', 11);
        $updateCron->finish();
        $this->em->flush();
        $this->assertFalse($this->activateSLNext->isUpdating($date));


        // 剛好在佔成更新排程規定的時間, 確保沒有零分秒的問題
        $date = new \DateTime('2011-10-24 12:00:00');
        $this->assertTrue($this->activateSLNext->isUpdating($date));

        // 在佔成更新排程規定時間的一分鐘內都是在佔成更新中
        $date = new \DateTime('2011-10-24 12:01:00');
        $this->assertTrue($this->activateSLNext->isUpdating($date));

        $date = new \DateTime('2011-10-24 12:01:01');
        $this->assertFalse($this->activateSLNext->isUpdating($date));

        // 測試傳入其他時區是否正常
        // 等於台灣時間 2011-10-24 12:00:00
        $date = new \DateTime('2011-10-24 00:00:00', new \DateTimeZone('America/New_York'));
        $this->assertTrue($this->activateSLNext->isUpdating($date));

        // 在佔成更新排程規定時間的一分鐘內都是在佔成更新中
        // 等於台灣時間 2011-10-24 12:01:00
        $date = new \DateTime('2011-10-24 00:01:00', new \DateTimeZone('America/New_York'));
        $this->assertTrue($this->activateSLNext->isUpdating($date));

        // 等於台灣時間 2011-10-24 12:01:01
        $date = new \DateTime('2011-10-24 00:01:01', new \DateTimeZone('America/New_York'));
        $this->assertFalse($this->activateSLNext->isUpdating($date));
    }

    /**
     * 測試是否已經佔成更新過了
     */
    public function testHasBeenUpdated()
    {
        // 上次跑完佔成更新是2011-10-10(一) 11:59:00
        $updateCron = $this->em->find('BBDurianBundle:ShareUpdateCron', 1);

        $updateCron->finish();
        $this->em->flush();

        $curDate = new \DateTime('2011-10-10 11:59:59');
        $this->assertTrue($this->activateSLNext->hasBeenUpdated($curDate));

        $curDate = new \DateTime('2011-10-10 12:00:00');
        $this->assertFalse($this->activateSLNext->hasBeenUpdated($curDate));

        // 測試輸入其他時區是否正常
        // 等於台灣時間 2011-10-10 11:59:59
        $curDate = new \DateTime('2011-10-9 23:59:59', new \DateTimeZone('America/New_York'));
        $this->assertTrue($this->activateSLNext->hasBeenUpdated($curDate));

        // 等於台灣時間 2011-10-10 12:00:00
        $curDate = new \DateTime('2011-10-10 00:00:00', new \DateTimeZone('America/New_York'));
        $this->assertFalse($this->activateSLNext->hasBeenUpdated($curDate));

    }

    /**
     * 測試在沒有佔成更新排程的情況下, 是否已經更新過佔成
     */
    public function testHasBeenUpdatedWithoutShareUpdateCron()
    {
        // 把所有的佔成更新排程刪掉
        $updateCrons = $this->em
            ->getRepository('BBDurianBundle:ShareUpdateCron')
            ->findAll();
        foreach ($updateCrons as $updateCron) {
            $this->em->remove($updateCron);
        }

        $this->em->flush();

        $this->setExpectedException(
            'RuntimeException',
            'No share update cron exists',
            150080044
        );

        $curDate = new \DateTime('2011-10-14 23:59:59');
        $this->activateSLNext->hasBeenUpdated($curDate);

    }
}
