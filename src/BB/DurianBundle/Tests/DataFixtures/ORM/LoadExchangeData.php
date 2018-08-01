<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\Exchange;

class LoadExchangeData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $activeAt = new \DateTime('2010-12-01 12:00:00');
        $exchange = new Exchange(156, 0.90000000, 1.10000000, 1.00000000, $activeAt);
        $manager->persist($exchange);

        $activeAt = new \DateTime('2010-12-01 12:00:00');
        $exchange = new Exchange(344, 0.94000000, 0.96000000, 0.95000000, $activeAt);
        $manager->persist($exchange);

        $activeAt = new \DateTime('2010-12-15 12:00:00');
        $exchange = new Exchange(344, 0.95000000, 0.97000000, 0.96000000, $activeAt);
        $manager->persist($exchange);

        $activeAt = new \DateTime('2010-12-01 12:00:00');
        $exchange = new Exchange(901, 0.19000000, 0.21000000, 0.20000000, $activeAt);
        $manager->persist($exchange);

        $activeAt = new \DateTime('2010-12-15 12:00:00');
        $exchange = new Exchange(901, 0.22200000, 0.22400000, 0.22300000, $activeAt);
        $manager->persist($exchange);

        $activeAt = new \DateTime('2099-12-01 12:00:00');
        $exchange = new Exchange(156, 0.99000000, 1.11000000, 1.33000000, $activeAt);
        $manager->persist($exchange);

        $manager->flush();
    }
}
