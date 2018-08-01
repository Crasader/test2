<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\UserLevel;

class LoadUserLevelData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        // xtester
        $user5 = $manager->find('BBDurianBundle:User', 5);
        $upl1 = new UserLevel($user5, 1);
        $upl1->locked();
        $manager->persist($upl1);

        // ytester
        $user6 = $manager->find('BBDurianBundle:User', 6);
        $upl2 = new UserLevel($user6, 1);
        $manager->persist($upl2);

        // ztester
        $user7 = $manager->find('BBDurianBundle:User', 7);
        $upl3 = new UserLevel($user7, 2);
        $manager->persist($upl3);

        // tester
        $user8 = $manager->find('BBDurianBundle:User', 8);
        $upl4 = new UserLevel($user8, 2);
        $manager->persist($upl4);

        // wtester
        $user4 = $manager->find('BBDurianBundle:User', 4);
        $upl5 = new UserLevel($user4, 2);
        $manager->persist($upl5);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData'
        ];
    }
}
