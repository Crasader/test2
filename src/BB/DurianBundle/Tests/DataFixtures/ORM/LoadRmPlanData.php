<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\RmPlan;

class LoadRmPlanData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $time = new \DateTime('20140101000000');
        $plan1 = new RmPlan('engineer1', 3, 5, null, $time, '測試1');
        $manager->persist($plan1);

        $plan2 = new RmPlan('engineer2', 3, 5, null, $time, '測試2');
        $plan2->confirm();
        $plan2->queueDone();
        $manager->persist($plan2);

        $plan3 = new RmPlan('engineer3', 3, 5, null, $time, '測試3');
        $plan3->cancel();
        $manager->persist($plan3);

        $plan4 = new RmPlan('engineer4', 3, 5, null, $time, '測試4');
        $plan4->confirm();
        $plan4->finish();
        $manager->persist($plan4);

        $manager->flush();
    }
}
