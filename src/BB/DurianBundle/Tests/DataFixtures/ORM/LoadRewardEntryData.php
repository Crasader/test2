<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\RewardEntry;

class LoadRewardEntryData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $now = new \DateTime('now');

        $entry1 = new RewardEntry(1, 4);
        $entry1->setId(1);
        $entry1->setUserId(8);
        $entry1->setObtainAt($now);
        $manager->persist($entry1);

        $entry2 = new RewardEntry(1, 6);
        $entry2->setId(2);
        $entry2->setPayoffAt($now);
        $manager->persist($entry2);

        $entry3 = new RewardEntry(2, 10);
        $entry3->setId(3);
        $manager->persist($entry3);

        $manager->flush();
    }
}
