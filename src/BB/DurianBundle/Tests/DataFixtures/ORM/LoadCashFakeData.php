<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\CashFake;

class LoadCashFakeData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $currency = 156; // CNY

        //ztester
        $user = $manager->find('BBDurianBundle:User', 7);

        $fake1 = new CashFake($user, $currency);
        $manager->persist($fake1);

        //tester
        $user = $manager->find('BBDurianBundle:User', 8);

        $fake2 = new CashFake($user, $currency);
        $manager->persist($fake2);

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
