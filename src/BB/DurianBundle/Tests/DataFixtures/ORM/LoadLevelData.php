<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\Level;

class LoadLevelData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $level1 = new Level(3, '未分層', 0, 1);
        $level1->setCreatedAtStart(new \DateTime('2000-09-21 16:15:12'));
        $level1->setCreatedAtEnd(new \DateTime('2030-12-31 23:59:59'));
        $level1->setUserCount(7);
        $manager->persist($level1);

        $level2 = new Level(3, '第一層', 0, 2);
        $level2->setCreatedAtStart(new \DateTime('2005-09-21 16:15:12'));
        $level2->setCreatedAtEnd(new \DateTime('2035-12-31 23:59:59'));
        $level2->setUserCount(100);
        $manager->persist($level2);

        $level3 = new Level(10, '未分層', 0, 1);
        $level3->setCreatedAtStart(new \DateTime('2010-09-21 16:15:12'));
        $level3->setCreatedAtEnd(new \DateTime('2040-12-31 23:59:59'));
        $manager->persist($level3);

        $level4 = new Level(10, '第一層', 1, 2);
        $level4->setCreatedAtStart(new \DateTime('2008-09-22 09:13:45'));
        $level4->setCreatedAtEnd(new \DateTime('2058-09-22 09:13:45'));
        $manager->persist($level4);

        $level5 = new Level(3, '第三層', 1, 3);
        $level5->setCreatedAtStart(new \DateTime('2013-1-1 11:11:11'));
        $level5->setCreatedAtEnd(new \DateTime('now'));
        $level5->setDepositCount(3);
        $level5->setDepositMax(71);
        $level5->setWithdrawCount(1);
        $level5->setWithdrawTotal(50);
        $manager->persist($level5);

        $level6 = new Level(10, '第二層', 0, 3);
        $level6->setCreatedAtStart(new \DateTime('2001-09-23 08:42:45'));
        $level6->setCreatedAtEnd(new \DateTime('2030-09-23 08:42:45'));
        $manager->persist($level6);

        // 租卡
        $level7 = new Level(3, '第四層', 0, 4);
        $level7->setCreatedAtStart(new \DateTime('2001-09-23 08:42:45'));
        $level7->setCreatedAtEnd(new \DateTime('2030-09-23 08:42:45'));
        $manager->persist($level7);

        $level8 = new Level(3, '第五層', 0, 5);
        $level8->setCreatedAtStart(new \DateTime('2001-09-23 08:42:45'));
        $level8->setCreatedAtEnd(new \DateTime('2030-09-23 08:42:45'));
        $manager->persist($level8);

        $manager->flush();
    }
}
