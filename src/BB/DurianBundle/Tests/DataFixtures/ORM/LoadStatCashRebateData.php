<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\StatCashRebate;

class LoadStatCashRebateData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $at = new \DateTime('2013-01-07 12:00:00');

        $stat = new StatCashRebate($at, 6, 156);
        $stat->setDomain(2);
        $stat->setParentId(5);
        $stat->setRebateKenoAmount(13);
        $stat->addRebateKenoCount();
        $stat->setRebateAmount(13);
        $stat->addRebateCount();
        $manager->persist($stat);

        $stat = new StatCashRebate($at, 7, 901);
        $stat->setDomain(2);
        $stat->setParentId(6);
        $stat->setRebateBallAmount(30);
        $stat->setRebateBallCount();
        $stat->setRebateAmount(30);
        $stat->addRebateCount();
        $manager->persist($stat);

        $stat = new StatCashRebate($at, 8, 156);
        $stat->setDomain(2);
        $stat->setParentId(7);
        $stat->setRebateKenoAmount(3);
        $stat->addRebateKenoCount();
        $stat->setRebateAmount(3);
        $stat->addRebateCount();
        $manager->persist($stat);

        $at = new \DateTime('2013-01-10 12:00:00');

        $stat = new StatCashRebate($at, 7, 901);
        $stat->setDomain(2);
        $stat->setParentId(6);
        $stat->setRebateKenoAmount(10);
        $stat->addRebateKenoCount();
        $stat->setRebateAmount(10);
        $stat->addRebateCount();
        $manager->persist($stat);

        $stat = new StatCashRebate($at, 8, 156);
        $stat->setDomain(2);
        $stat->setParentId(7);
        $stat->setRebateBallAmount(5);
        $stat->setRebateBallCount();
        $stat->setRebateAmount(5);
        $stat->addRebateCount();
        $manager->persist($stat);

        $at = new \DateTime('2013-01-11 12:00:00');

        $stat = new StatCashRebate($at, 6, 156);
        $stat->setDomain(2);
        $stat->setParentId(5);
        $stat->setRebateBallAmount(1);
        $stat->setRebateBallCount();
        $stat->setRebateAmount(1);
        $stat->addRebateCount();
        $manager->persist($stat);

        $at = new \DateTime('2014-10-10 12:00:00');

        $stat = new StatCashRebate($at, 7, 901);
        $stat->setDomain(2);
        $stat->setParentId(6);
        $stat->setRebateBallAmount(80);
        $stat->setRebateBallCount(4);
        $stat->setRebateOfferAmount(20);
        $stat->setRebateOfferCount(1);
        $stat->setRebateAmount(100);
        $stat->setRebateCount(5);
        $manager->persist($stat);

        $at = new \DateTime('2014-10-13 12:00:00');

        $stat = new StatCashRebate($at, 8, 156);
        $stat->setDomain(2);
        $stat->setParentId(7);
        $stat->setRebateLotteryAmount(20);
        $stat->setRebateLotteryCount(1);
        $stat->setRebateAmount(20);
        $stat->setRebateCount(1);
        $manager->persist($stat);

        $manager->flush();
    }
}
