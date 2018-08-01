<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\UserHasDepositWithdraw;

class LoadUserHasDepositWithdrawData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $at = new \DateTime('2013-01-01 12:00:00');
        $parent = $manager->find('BBDurianBundle:User', 7);
        $parentDepositWithdraw = new UserHasDepositWithdraw($parent, $at, null, false, false);
        $manager->persist($parentDepositWithdraw);

        $user = $manager->find('BBDurianBundle:User', 8);
        $depositWithdraw = new UserHasDepositWithdraw($user, $at, null, false, false);
        $manager->persist($depositWithdraw);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return ['BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData'];
    }
}
