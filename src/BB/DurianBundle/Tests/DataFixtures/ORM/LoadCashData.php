<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\Cash;

class LoadCashData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $currency = 901; // TWD

        // company
        $user  = $manager->find('BBDurianBundle:User', 2);
        $cash1 = new Cash($user, $currency);
        $manager->persist($cash1);
        $manager->flush();
        $cash1->setBalance(1000);
        $cash1->setLastEntryAt(20120101120000);

        // vtester
        $user  = $manager->find('BBDurianBundle:User', 3);
        $cash2 = new Cash($user, $currency);
        $manager->persist($cash2);
        $manager->flush();
        $cash2->setBalance(1000);
        $cash2->setLastEntryAt(20120101120000);

        // wtester
        $user  = $manager->find('BBDurianBundle:User', 4);
        $cash3 = new Cash($user, $currency);
        $manager->persist($cash3);
        $manager->flush();
        $cash3->setBalance(1000);
        $cash3->setLastEntryAt(20120101120000);

        // xtester
        $user  = $manager->find('BBDurianBundle:User', 5);
        $cash4 = new Cash($user, $currency);
        $manager->persist($cash4);
        $manager->flush();
        $cash4->setBalance(1000);
        $cash4->setLastEntryAt(20120101120000);

        // ytester
        $user  = $manager->find('BBDurianBundle:User', 6);
        $cash5 = new Cash($user, $currency);
        $manager->persist($cash5);
        $manager->flush();
        $cash5->setBalance(1000);
        $cash5->setLastEntryAt(20120101120000);

        // ztester
        $user  = $manager->find('BBDurianBundle:User', 7);
        $cash6 = new Cash($user, $currency);
        $manager->persist($cash6);
        $manager->flush();
        $cash6->setBalance(1000);
        $cash6->setLastEntryAt(20120101120000);

        // tester
        $user  = $manager->find('BBDurianBundle:User', 8);
        $cash7 = new Cash($user, $currency);
        $manager->persist($cash7);
        $manager->flush();
        $cash7->setBalance(1000);
        $cash7->setLastEntryAt(20120101120000);

        // isolate
        $user  = $manager->find('BBDurianBundle:User', 9);
        $cash8 = new Cash($user, $currency);
        $manager->persist($cash8);
        $manager->flush();
        $cash8->setBalance(1000);
        $cash8->setLastEntryAt(20120101120000);

        $user  = $manager->find('BBDurianBundle:User', 51);
        $cash9 = new Cash($user, $currency);
        $manager->persist($cash9);

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
