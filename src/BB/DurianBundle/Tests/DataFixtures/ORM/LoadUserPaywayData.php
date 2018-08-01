<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\UserPayway;

class LoadUserPaywayData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $user2 = $manager->find('BBDurianBundle:User', 2);
        $payway = new UserPayway($user2);
        $payway->enableCash();
        $payway->enableCredit();
        $manager->persist($payway);

        $user3 = $manager->find('BBDurianBundle:User', 3);
        $payway = new UserPayway($user3);
        $payway->enableCash();
        $payway->enableCredit();
        $manager->persist($payway);

        $user9 = $manager->find('BBDurianBundle:User', 9);
        $payway = new UserPayway($user9);
        $payway->enableCash();
        $payway->enableCashFake();
        $payway->enableCredit();
        $manager->persist($payway);

        $user10 = $manager->find('BBDurianBundle:User', 10);
        $payway = new UserPayway($user10);
        $manager->persist($payway);

        $user2m = $manager->find('BBDurianBundle:User', 20000000);
        $payway = new UserPayway($user2m);
        $manager->persist($payway);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData'
        ];
    }
}
