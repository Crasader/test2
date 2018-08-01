<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\RegisterBonus;

class LoadRegisterBonusData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $user4 = $manager->find('BBDurianBundle:User', 4);

        // bonus id = 4
        $bonus = new RegisterBonus($user4);
        $manager->persist($bonus);

        $user8 = $manager->find('BBDurianBundle:User', 8);

        // bonus id = 8
        $bonus = new RegisterBonus($user8);
        $manager->persist($bonus);

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
