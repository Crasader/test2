<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\UserAncestor;

class LoadUserAncestorData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $user2 = $manager->find('BB\DurianBundle\Entity\User', 2);
        $user3 = $manager->find('BB\DurianBundle\Entity\User', 3);
        $user4 = $manager->find('BB\DurianBundle\Entity\User', 4);
        $user5 = $manager->find('BB\DurianBundle\Entity\User', 5);
        $user6 = $manager->find('BB\DurianBundle\Entity\User', 6);
        $user7 = $manager->find('BB\DurianBundle\Entity\User', 7);
        $user8 = $manager->find('BB\DurianBundle\Entity\User', 8);
        $user9 = $manager->find('BB\DurianBundle\Entity\User', 9);
        $user10 = $manager->find('BB\DurianBundle\Entity\User', 10);
        $user50 = $manager->find('BB\DurianBundle\Entity\User', 50);
        $user51 = $manager->find('BB\DurianBundle\Entity\User', 51);
        $user20m = $manager->find('BB\DurianBundle\Entity\User', 20000000);

        // user2
        $ua = new UserAncestor($user3, $user2, 1);
        $manager->persist($ua);

        $ua = new UserAncestor($user50, $user2, 1);
        $manager->persist($ua);

        $ua = new UserAncestor($user20m, $user2, 1);
        $manager->persist($ua);

        $ua = new UserAncestor($user4, $user2, 2);
        $manager->persist($ua);

        $ua = new UserAncestor($user5, $user2, 3);
        $manager->persist($ua);

        $ua = new UserAncestor($user6, $user2, 4);
        $manager->persist($ua);

        $ua = new UserAncestor($user7, $user2, 5);
        $manager->persist($ua);

        $ua = new UserAncestor($user8, $user2, 6);
        $manager->persist($ua);

        $ua = new UserAncestor($user51, $user2, 6);
        $manager->persist($ua);


        // user3
        $ua = new UserAncestor($user4, $user3, 1);
        $manager->persist($ua);

        $ua = new UserAncestor($user5, $user3, 2);
        $manager->persist($ua);

        $ua = new UserAncestor($user6, $user3, 3);
        $manager->persist($ua);

        $ua = new UserAncestor($user7, $user3, 4);
        $manager->persist($ua);

        $ua = new UserAncestor($user8, $user3, 5);
        $manager->persist($ua);

        $ua = new UserAncestor($user51, $user3, 5);
        $manager->persist($ua);

        // user4
        $ua = new UserAncestor($user5, $user4, 1);
        $manager->persist($ua);

        $ua = new UserAncestor($user6, $user4, 2);
        $manager->persist($ua);

        $ua = new UserAncestor($user7, $user4, 3);
        $manager->persist($ua);

        $ua = new UserAncestor($user8, $user4, 4);
        $manager->persist($ua);

        $ua = new UserAncestor($user51, $user4, 4);
        $manager->persist($ua);

        // user5
        $ua = new UserAncestor($user6, $user5, 1);
        $manager->persist($ua);

        $ua = new UserAncestor($user7, $user5, 2);
        $manager->persist($ua);

        $ua = new UserAncestor($user8, $user5, 3);
        $manager->persist($ua);

        $ua = new UserAncestor($user51, $user5, 3);
        $manager->persist($ua);

        // user6
        $ua = new UserAncestor($user7, $user6, 1);
        $manager->persist($ua);

        $ua = new UserAncestor($user8, $user6, 2);
        $manager->persist($ua);

        $ua = new UserAncestor($user51, $user6, 2);
        $manager->persist($ua);

        // user7
        $ua = new UserAncestor($user8, $user7, 1);
        $manager->persist($ua);

        $ua = new UserAncestor($user51, $user7, 1);
        $manager->persist($ua);

        //$user9
        $ua = new UserAncestor($user10, $user9, 1);
        $manager->persist($ua);

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
