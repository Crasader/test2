<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\StatCashDepositWithdraw;

class LoadStatCashDepositWithdrawData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $at = new \DateTime('2013-01-08 12:00:00');

        $stat = new StatCashDepositWithdraw($at, 7, 901);
        $stat->setDomain(2);
        $stat->setParentId(6);
        $stat->setDepositWithdrawAmount(5);
        $stat->addDepositWithdrawCount(1);
        $stat->setDepositAmount(5);
        $stat->addDepositCount();
        $manager->persist($stat);

        $stat = new StatCashDepositWithdraw($at, 8, 156);
        $stat->setDomain(2);
        $stat->setParentId(7);
        $stat->setDepositWithdrawAmount(50);
        $stat->addDepositWithdrawCount(1);
        $stat->setDepositAmount(50);
        $stat->addDepositCount();
        $manager->persist($stat);

        $at = new \DateTime('2013-01-09 12:00:00');

        $stat = new StatCashDepositWithdraw($at, 7, 901);
        $stat->setDomain(2);
        $stat->setParentId(6);
        $stat->setDepositWithdrawAmount(30);
        $stat->addDepositWithdrawCount(2);
        $stat->setDepositAmount(10);
        $stat->addDepositCount();
        $stat->setWithdrawAmount(20);
        $stat->addWithdrawCount();
        $manager->persist($stat);

        $stat = new StatCashDepositWithdraw($at, 8, 156);
        $stat->setDomain(2);
        $stat->setParentId(7);
        $stat->setDepositWithdrawAmount(3);
        $stat->addDepositWithdrawCount(2);
        $stat->setDepositAmount(1);
        $stat->addDepositCount();
        $stat->setWithdrawAmount(2);
        $stat->addWithdrawCount();
        $manager->persist($stat);

        $at = new \DateTime('2013-01-10 12:00:00');

        $stat = new StatCashDepositWithdraw($at, 7, 901);
        $stat->setDomain(2);
        $stat->setParentId(6);
        $stat->setDepositWithdrawAmount(30);
        $stat->addDepositWithdrawCount(2);
        $stat->setDepositAmount(10);
        $stat->addDepositCount();
        $stat->setWithdrawAmount(20);
        $stat->addWithdrawCount();
        $manager->persist($stat);

        $stat = new StatCashDepositWithdraw($at, 8, 156);
        $stat->setDomain(2);
        $stat->setParentId(7);
        $stat->setDepositWithdrawAmount(3);
        $stat->addDepositWithdrawCount(2);
        $stat->setDepositAmount(1);
        $stat->addDepositCount();
        $stat->setWithdrawAmount(2);
        $stat->addWithdrawCount();
        $manager->persist($stat);

        $at = new \DateTime('2013-01-11 12:00:00');

        // 用來測試排序功能
        $stat = new StatCashDepositWithdraw($at, 6, 156);
        $stat->setDomain(2);
        $stat->setParentId(5);
        $stat->setDepositWithdrawAmount(13);
        $stat->addDepositWithdrawCount(2);
        $stat->setDepositAmount(6);
        $stat->addDepositCount();
        $stat->setWithdrawAmount(7);
        $stat->addWithdrawCount();
        $manager->persist($stat);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
        ];
    }
}
