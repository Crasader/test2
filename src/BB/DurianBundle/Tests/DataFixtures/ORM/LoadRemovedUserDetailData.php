<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\RemovedUserDetail;

class LoadRemovedUserDetailData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $removedUser = $manager->find('BBDurianBundle:RemovedUser', 50);
        $userDetail = $manager->find('BBDurianBundle:UserDetail', 50);

        $entry = new RemovedUserDetail($removedUser, $userDetail);

        $manager->persist($entry);
        $manager->flush($entry);
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemovedUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserDetailData'
        ];
    }
}
