<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\RmPlanUserExtraBalance;

class LoadRmPlanUserExtraBalanceData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $rpuBalance = new RmPlanUserExtraBalance(1, 'ab', 0);
        $manager->persist($rpuBalance);

        $rpuBalance = new RmPlanUserExtraBalance(1, 'sabah', 1);
        $manager->persist($rpuBalance);

        $manager->flush();
    }
}
