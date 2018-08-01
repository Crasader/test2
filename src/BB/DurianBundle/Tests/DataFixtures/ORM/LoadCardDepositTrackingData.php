<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\CardDepositTracking;

class LoadCardDepositTrackingData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $tracking1 = new CardDepositTracking(201502010000000001, 1, 3);
        $manager->persist($tracking1);

        $tracking2 = new CardDepositTracking(201502010000000002, 2, 3);
        $manager->persist($tracking2);

        $tracking3 = new CardDepositTracking(201501080000000001, 1, 6);
        $tracking3->addRetry();
        $tracking3->addRetry();
        $manager->persist($tracking3);

        $manager->flush();
    }
}
