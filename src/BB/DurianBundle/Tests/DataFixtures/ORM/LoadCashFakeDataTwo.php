<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\CashFake;

class LoadCashFakeDataTwo extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $currency = 156; // CNY

        // gaga
        $user = $manager->find('BB\DurianBundle\Entity\User', 10);
        $cash = new CashFake($user, $currency);
        $manager->persist($cash);

        // wtester
        $user = $manager->find('BB\DurianBundle\Entity\User', 4);
        $cash = new CashFake($user, $currency);
        $manager->persist($cash);

        // only for test
        $user = $manager->find('BB\DurianBundle\Entity\User', 3);
        $cash = new CashFake($user, $currency);
        $manager->persist($cash);

        $user = $manager->find('BB\DurianBundle\Entity\User', 50);
        $cash = new CashFake($user, $currency);
        $manager->persist($cash);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
        );
    }
}
