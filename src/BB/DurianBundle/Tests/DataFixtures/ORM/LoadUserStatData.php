<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\UserStat;

class LoadUserStatData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        // ytester
        $user6  = $manager->find('BBDurianBundle:User', 6);
        $userStat1 = new UserStat($user6);
        $userStat1->setManualCount(1);
        $userStat1->setManualTotal(50);
        $userStat1->setManualMax(50);
        $userStat1->setWithdrawCount(1);
        $userStat1->setWithdrawTotal(50);
        $userStat1->setWithdrawMax(50);
        $manager->persist($userStat1);

        // ztester
        $user7  = $manager->find('BBDurianBundle:User', 7);
        $userStat2 = new UserStat($user7);
        $userStat2->setDepositCount(3);
        $userStat2->setDepositTotal(600);
        $userStat2->setDepositMax(300);
        $userStat2->setRemitCount(3);
        $userStat2->setRemitTotal(600);
        $userStat2->setRemitMax(300);
        $userStat2->setManualCount(3);
        $userStat2->setManualTotal(600);
        $userStat2->setManualMax(300);
        $userStat2->setWithdrawCount(4);
        $userStat2->setWithdrawTotal(423);
        $userStat2->setWithdrawMax(185);
        $manager->persist($userStat2);

        // tester
        $user8  = $manager->find('BBDurianBundle:User', 8);
        $userStat3 = new UserStat($user8);
        $userStat3->setWithdrawCount(3);
        $userStat3->setWithdrawTotal(255);
        $userStat3->setWithdrawMax(135);
        $manager->persist($userStat3);

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
