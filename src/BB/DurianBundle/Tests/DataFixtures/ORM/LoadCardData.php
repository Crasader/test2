<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\Card;

class LoadCardData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {

        //company
        $user = $manager->find('BB\DurianBundle\Entity\User', 2);

        $card = new Card($user);
        $manager->persist($card);

        //vtester
        $user = $manager->find('BB\DurianBundle\Entity\User', 3);

        $card = new Card($user);
        $manager->persist($card);

        //wtester
        $user = $manager->find('BB\DurianBundle\Entity\User', 4);

        $card = new Card($user);
        $manager->persist($card);

        //xtester
        $user = $manager->find('BB\DurianBundle\Entity\User', 5);

        $card = new Card($user);
        $manager->persist($card);

        //ytester
        $user = $manager->find('BB\DurianBundle\Entity\User', 6);

        $card = new Card($user);
        $manager->persist($card);

        //ztester
        $user = $manager->find('BB\DurianBundle\Entity\User', 7);

        $card = new Card($user);
        $manager->persist($card);

        //tester
        $user = $manager->find('BB\DurianBundle\Entity\User', 8);

        $card = new Card($user);
        $manager->persist($card);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData'
        );
    }
}
