<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\RmPlanQueue;

class LoadRmPlanQueueData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $plan = $manager->find('BBDurianBundle:RmPlan', 1);
        $manager->persist(new RmPlanQueue($plan));

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRmPlanData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRmPlanLevelData',
        ];
    }
}
