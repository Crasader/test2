<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\DepositTracking;

class LoadDepositTrackingData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $entry1 = new DepositTracking(201304280000000001, 2, 1);
        $manager->persist($entry1);

        $entry2 = new DepositTracking(201305280000000001, 1, 1);
        $entry2->addRetry();
        $entry2->addRetry();
        $manager->persist($entry2);

        $manager->flush();
    }
}
