<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\ShareLimit;

class LoadScheduleForUpdateData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        //company成數設定
        $user = $manager->find('BB\DurianBundle\Entity\User', 2);
        $shareLimit = new ShareLimit($user, 2);
        $shareLimit->setUpper(100);
        $shareLimit->setLower(0);
        $shareLimit->setParentUpper(100);
        $shareLimit->setParentLower(0);
        $shareLimit->setMin1(20);
        $shareLimit->setMax1(20);
        $shareLimit->setMax2(80);
        $manager->persist($shareLimit);

        //vtester成數設定
        $user = $manager->find('BB\DurianBundle\Entity\User', 3);
        $shareLimit = new ShareLimit($user, 2);
        $shareLimit->setUpper(90);
        $shareLimit->setLower(0);
        $shareLimit->setParentUpper(20);
        $shareLimit->setParentLower(0);
        $shareLimit->setMin1(20);
        $shareLimit->setMax1(30);
        $shareLimit->setMax2(90);
        $manager->persist($shareLimit);

        //wtester成數設定
        $user = $manager->find('BB\DurianBundle\Entity\User', 4);
        $shareLimit = new ShareLimit($user, 2);
        $shareLimit->setUpper(80);
        $shareLimit->setLower(0);
        $shareLimit->setParentUpper(20);
        $shareLimit->setParentLower(0);
        $shareLimit->setMin1(200);
        $shareLimit->setMax1(0);
        $shareLimit->setMax2(0);
        $manager->persist($shareLimit);

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
