<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\ShareUpdateCron;

class LoadShareUpdateCronForControllerData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $updateCron = new ShareUpdateCron();
        $updateCron->setPeriod('0 0 * * *')
             ->setUpdateAt(new \DateTime('today'))
             ->finish()
             ->setGroupNum(1);

        $manager->persist($updateCron);

        $updateCron = new ShareUpdateCron();
        $updateCron->setPeriod('0 0 * * *')
             ->setUpdateAt(new \DateTime('today'))
             ->finish()
             ->setGroupNum(3);

        $manager->persist($updateCron);
        $manager->flush();
    }
}
