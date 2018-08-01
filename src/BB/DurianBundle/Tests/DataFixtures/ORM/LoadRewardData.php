<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\Reward;

class LoadRewardData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $time = new \DateTime('2015-01-01 00:00:00');
        $start = $time->format(\DateTime::ISO8601);
        $end = $time->add(new \DateInterval('P4D'))->format(\DateTime::ISO8601);

        $reward1 = new Reward('test', 2, 10, 2, 1, 6, $start, $end);
        $reward1->addObtainQuantity();
        $reward1->addObtainAmount(4);
        $reward1->addObtainQuantity();
        $reward1->addObtainAmount(6);
        $reward1->setEntryCreated();
        $manager->persist($reward1);

        $now = new \DateTime('now');
        $start = $now->sub(new \DateInterval('P1D'))->format(\DateTime::ISO8601);
        $end = $now->add(new \DateInterval('P5D'))->format(\DateTime::ISO8601);

        $reward2 = new Reward('test2', 2, 10, 1, 1, 10, $start, $end);
        $reward2->addObtainQuantity();
        $reward2->addObtainAmount(10);
        $reward2->setEntryCreated();
        $manager->persist($reward2);

        $now = new \DateTime('now');
        $start = $now->sub(new \DateInterval('P1D'))->format(\DateTime::ISO8601);
        $end = $now->add(new \DateInterval('P2D'))->format(\DateTime::ISO8601);

        $reward3 = new Reward('test3', 9, 100, 5, 1, 50, $start, $end);
        $manager->persist($reward3);

        $manager->flush();
    }
}
