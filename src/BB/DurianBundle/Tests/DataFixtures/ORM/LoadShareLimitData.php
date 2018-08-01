<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\ShareLimit;

class LoadShareLimitData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        //company成數設定
        $user = $manager->find('BB\DurianBundle\Entity\User', 2);
        // group 1
        $shareLimit = new ShareLimit($user, 1);
        $manager->persist($shareLimit);
        // group 3
        $shareLimit = new ShareLimit($user, 3);
        $manager->persist($shareLimit);

        //vtester成數設定
        $user = $manager->find('BB\DurianBundle\Entity\User', 3);
        $shareLimit = new ShareLimit($user, 1);
        $shareLimit->setParentUpper(0);
        $manager->persist($shareLimit);

        //wtester成數設定
        $user = $manager->find('BB\DurianBundle\Entity\User', 4);
        $shareLimit = new ShareLimit($user, 1);
        $shareLimit->setUpper(90);
        $shareLimit->setParentUpper(90);
        $shareLimit->setParentLower(10);
        $manager->persist($shareLimit);

        //xtester成數設定
        $user = $manager->find('BB\DurianBundle\Entity\User', 5);
        $shareLimit = new ShareLimit($user, 1);
        $shareLimit->setUpper(70);
        $shareLimit->setParentUpper(20);
        $shareLimit->setParentLower(20);
        $manager->persist($shareLimit);

        //ytester成數設定
        $user = $manager->find('BB\DurianBundle\Entity\User', 6);
        $shareLimit = new ShareLimit($user, 1);
        $shareLimit->setUpper(55);
        $shareLimit->setLower(10);
        $shareLimit->setParentUpper(70);
        $shareLimit->setParentLower(15);
        $manager->persist($shareLimit);

        //ztester成數設定
        $user = $manager->find('BB\DurianBundle\Entity\User', 7);
        $shareLimit = new ShareLimit($user, 1);
        $shareLimit->setUpper(20);
        $shareLimit->setLower(20);
        $shareLimit->setParentUpper(30);
        $shareLimit->setParentLower(30);
        $manager->persist($shareLimit);

        //isolate成數設定
        $user = $manager->find('BB\DurianBundle\Entity\User', 9);
        $shareLimit = new ShareLimit($user, 1);
        $shareLimit->setUpper(100);
        $shareLimit->setLower(0);
        $shareLimit->setParentUpper(20);
        $shareLimit->setParentLower(20);
        $manager->persist($shareLimit);

        //gaga成數設定
        $user = $manager->find('BB\DurianBundle\Entity\User', 10);
        $shareLimit = new ShareLimit($user, 1);
        $shareLimit->setUpper(0);
        $shareLimit->setLower(0);
        $shareLimit->setParentUpper(20);
        $shareLimit->setParentLower(20);
        $manager->persist($shareLimit);

        //vtester2成數設定
        $user = $manager->find('BB\DurianBundle\Entity\User', 50);
        $shareLimit = new ShareLimit($user, 1);
        $shareLimit->setParentUpper(0);
        $manager->persist($shareLimit);

        $manager->flush();
        $manager->clear();

        $repo = $manager->getRepository('BB\DurianBundle\Entity\ShareLimit');

        // 更新 min max
        foreach ($repo->findAll() as $share) {
            $repo->updateMin1($share);
            $repo->updateMax1($share);
            $repo->updateMax2($share);
        }
        $manager->flush();
        $manager->clear();

        // 檢查佔成
        $validator = new \BB\DurianBundle\Share\Validator();
        foreach ($repo->findAll() as $share) {
            $validator->validateLimit($share);
        }
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
        );
    }
}
