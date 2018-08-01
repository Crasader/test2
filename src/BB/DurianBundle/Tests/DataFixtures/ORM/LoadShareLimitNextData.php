<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\ShareLimitNext;

class LoadShareLimitNextData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        //company成數設定
        $user = $manager->find('BB\DurianBundle\Entity\User', 2);
        // group1
        $shareLimitNext = new ShareLimitNext($user, 1);
        $manager->persist($shareLimitNext);
        // group3
        $shareLimitNext = new ShareLimitNext($user, 3);
        $manager->persist($shareLimitNext);

        //vtester成數設定
        $user = $manager->find('BB\DurianBundle\Entity\User', 3);
        $shareLimitNext = new ShareLimitNext($user, 1);
        $shareLimitNext->setUpper(90);
        $shareLimitNext->setParentUpper(0);
        $manager->persist($shareLimitNext);

        //wtester成數設定
        $user = $manager->find('BB\DurianBundle\Entity\User', 4);
        $shareLimitNext = new ShareLimitNext($user, 1);
        $shareLimitNext->setUpper(90);
        $shareLimitNext->setParentUpper(0);
        $manager->persist($shareLimitNext);

        //xtester成數設定
        $user = $manager->find('BB\DurianBundle\Entity\User', 5);
        $shareLimitNext = new ShareLimitNext($user, 1);
        $shareLimitNext->setUpper(70);
        $shareLimitNext->setParentUpper(10);
        $manager->persist($shareLimitNext);

        //ytester成數設定
        $user = $manager->find('BB\DurianBundle\Entity\User', 6);
        $shareLimitNext = new ShareLimitNext($user, 1);
        $shareLimitNext->setUpper(50);
        $shareLimitNext->setLower(50);
        $shareLimitNext->setParentUpper(10);
        $manager->persist($shareLimitNext);

        //ztester成數設定
        $user = $manager->find('BB\DurianBundle\Entity\User', 7);
        $shareLimitNext = new ShareLimitNext($user, 1);
        $shareLimitNext->setUpper(30);
        $shareLimitNext->setLower(30);
        $shareLimitNext->setParentUpper(20);
        $shareLimitNext->setParentLower(20);
        $manager->persist($shareLimitNext);

        //isolate成數設定
        $user = $manager->find('BB\DurianBundle\Entity\User', 9);
        $shareLimitNext = new ShareLimitNext($user, 1);
        $shareLimitNext->setUpper(100);
        $shareLimitNext->setLower(0);
        $shareLimitNext->setParentUpper(20);
        $shareLimitNext->setParentLower(20);
        $manager->persist($shareLimitNext);

        //gaga成數設定
        $user = $manager->find('BB\DurianBundle\Entity\User', 10);
        $shareLimitNext = new ShareLimitNext($user, 1);
        $shareLimitNext->setUpper(0);
        $shareLimitNext->setLower(0);
        $shareLimitNext->setParentUpper(20);
        $shareLimitNext->setParentLower(20);
        $manager->persist($shareLimitNext);

        //vtester2成數設定
        $user = $manager->find('BB\DurianBundle\Entity\User', 50);
        $shareLimitNext = new ShareLimitNext($user, 1);
        $shareLimitNext->setUpper(90);
        $shareLimitNext->setParentUpper(0);
        $manager->persist($shareLimitNext);

        $manager->flush();
        $manager->clear();

        $repo = $manager->getRepository('BB\DurianBundle\Entity\ShareLimitNext');

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
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareLimitData'
        );
    }
}
