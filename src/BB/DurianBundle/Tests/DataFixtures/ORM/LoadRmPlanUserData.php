<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\RmPlanUser;

class LoadRmPlanUserData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $rpUser = new RmPlanUser(1, 51, 'test1', 'test1');
        $manager->persist($rpUser);

        $rpUser = new RmPlanUser(2, 51, 'test2', 'test2');
        $manager->persist($rpUser);

        $manager->flush();
    }
}
