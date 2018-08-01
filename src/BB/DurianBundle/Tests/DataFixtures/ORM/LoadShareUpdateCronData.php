<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\ShareUpdateCron;

class LoadShareUpdateCronData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        // 整合站
        $updateCron1 = new ShareUpdateCron();
        $updateCron1->setGroupNum(1);
        $updateCron1->setPeriod('0 12 * * 1');
        $updateCron1->finish();
        $updateCron1->setUpdateAt(new \DateTime('2011-10-10 11:59:00'));
        $manager->persist($updateCron1);

        $updateCron2 = new ShareUpdateCron();
        $updateCron2->setGroupNum(2);
        $updateCron2->setPeriod('0 12 * * 1');
        $updateCron2->finish();
        $updateCron2->setUpdateAt(new \DateTime('2011-10-10 11:59:00'));
        $manager->persist($updateCron2);

        $updateCron3 = new ShareUpdateCron();
        $updateCron3->setGroupNum(3);
        $updateCron3->setPeriod('0 12 * * 1');
        $updateCron3->finish();
        $updateCron3->setUpdateAt(new \DateTime('2011-10-10 11:59:00'));
        $manager->persist($updateCron3);

        $updateCron4 = new ShareUpdateCron();
        $updateCron4->setGroupNum(6);
        $updateCron4->setPeriod('0 12 * * 1');
        $updateCron4->finish();
        $updateCron4->setUpdateAt(new \DateTime('2011-10-10 11:59:00'));
        $manager->persist($updateCron4);

        // 小球
        $updateCron5 = new ShareUpdateCron();
        $updateCron5->setGroupNum(5);
        $updateCron5->setPeriod('0 0 * * *');
        $updateCron5->finish();
        $updateCron5->setUpdateAt(new \DateTime('2011-10-11 00:00:00'));
        $manager->persist($updateCron5);

        $updateCron6 = new ShareUpdateCron();
        $updateCron6->setGroupNum(7);
        $updateCron6->setPeriod('0 0 * * *');
        $updateCron6->finish();
        $updateCron6->setUpdateAt(new \DateTime('2011-10-11 00:00:00'));
        $manager->persist($updateCron6);

        $updateCron7 = new ShareUpdateCron();
        $updateCron7->setGroupNum(8);
        $updateCron7->setPeriod('0 0 * * *');
        $updateCron7->finish();
        $updateCron7->setUpdateAt(new \DateTime('2011-10-11 00:00:00'));
        $manager->persist($updateCron7);

        $updateCron8 = new ShareUpdateCron();
        $updateCron8->setGroupNum(9);
        $updateCron8->setPeriod('0 0 * * *');
        $updateCron8->finish();
        $updateCron8->setUpdateAt(new \DateTime('2011-10-11 00:00:00'));
        $manager->persist($updateCron8);

        $updateCron9 = new ShareUpdateCron();
        $updateCron9->setGroupNum(10);
        $updateCron9->setPeriod('0 0 * * *');
        $updateCron9->finish();
        $updateCron9->setUpdateAt(new \DateTime('2011-10-11 00:00:00'));
        $manager->persist($updateCron9);

        $updateCron10 = new ShareUpdateCron();
        $updateCron10->setGroupNum(11);
        $updateCron10->setPeriod('0 0 * * *');
        $updateCron10->setUpdateAt(new \DateTime('2011-10-11 00:00:00'));
        $manager->persist($updateCron10);

        $manager->flush();
    }
}
