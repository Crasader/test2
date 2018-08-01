<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\RmPlanLevel;

class LoadRmPlanLevelData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $rpLevel = new RmPlanLevel(1, 1, '未分層');
        $manager->persist($rpLevel);

        $rpLevel = new RmPlanLevel(1, 2, '第一層');
        $manager->persist($rpLevel);

        $manager->flush();
    }
}
