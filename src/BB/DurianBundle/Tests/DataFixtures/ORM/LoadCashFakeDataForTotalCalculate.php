<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\CashFake;

class LoadCashFakeDataForTotalCalculate extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $currency = 156; // CNY

        // company
        $user      = $manager->find('BB\DurianBundle\Entity\User', 2);
        $cashFake2 = new CashFake($user, $currency);
        $manager->persist($cashFake2);


        // vtester
        $user      = $manager->find('BB\DurianBundle\Entity\User', 3);
        $cashFake3 = new CashFake($user, $currency);
        $manager->persist($cashFake3);

        // wtester
        $user      = $manager->find('BB\DurianBundle\Entity\User', 4);
        $cashFake4 = new CashFake($user, $currency);
        $manager->persist($cashFake4);


        // wtester
        $user      = $manager->find('BB\DurianBundle\Entity\User', 5);
        $cashFake5 = new CashFake($user, $currency);
        $manager->persist($cashFake5);


        // wtester
        $user      = $manager->find('BB\DurianBundle\Entity\User', 6);
        $cashFake6 = new CashFake($user, $currency);
        $manager->persist($cashFake6);


        // wtester
        $user      = $manager->find('BB\DurianBundle\Entity\User', 7);
        $cashFake7 = new CashFake($user, $currency);
        $manager->persist($cashFake7);


        // wtester
        $user      = $manager->find('BB\DurianBundle\Entity\User', 8);
        $cashFake8 = new CashFake($user, $currency);
        $manager->persist($cashFake8);


        // isolate
        $user      = $manager->find('BB\DurianBundle\Entity\User', 9);
        $cashFake9 = new CashFake($user, $currency);
        $manager->persist($cashFake9);


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
