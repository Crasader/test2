<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\RemovedUserEmail;

class LoadRemovedUserEmailData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     *
     * sinhao 2015.07.14
     */
    public function load(ObjectManager $manager)
    {
        $removedUser = $manager->find('BBDurianBundle:RemovedUser', 50);
        $userEmail = $manager->find('BBDurianBundle:UserEmail', 50);

        $removedUserEmail = new RemovedUserEmail($removedUser, $userEmail);

        $manager->persist($removedUserEmail);
        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemovedUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserEmailData'
        ];
    }
}
