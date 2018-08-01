<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\StatCashOffer;

class LoadStatCashOfferData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $at = new \DateTime('2013-01-10 12:00:00');

        $stat = new StatCashOffer($at, 7, 901);
        $stat->setDomain(2);
        $stat->setParentId(6);
        $stat->setOfferDepositAmount(30);
        $stat->addOfferDepositCount();
        $stat->setOfferAmount(30);
        $stat->addOfferCount();
        $manager->persist($stat);

        $stat = new StatCashOffer($at, 8, 156);
        $stat->setDomain(2);
        $stat->setParentId(7);
        $stat->setOfferDepositAmount(3);
        $stat->addOfferDepositCount();
        $stat->setOfferAmount(3);
        $stat->addOfferCount();
        $manager->persist($stat);

        $at = new \DateTime('2013-01-11 12:00:00');

        $stat = new StatCashOffer($at, 6, 156);
        $stat->setDomain(2);
        $stat->setParentId(5);
        $stat->setOfferDepositAmount(8);
        $stat->addOfferDepositCount();
        $stat->setOfferAmount(8);
        $stat->addOfferCount();
        $manager->persist($stat);

        $at = new \DateTime('2014-10-10 12:00:00');

        $stat = new StatCashOffer($at, 8, 156);
        $stat->setDomain(2);
        $stat->setParentId(7);
        $stat->setOfferDepositAmount(50);
        $stat->setOfferDepositCount(5);
        $stat->setOfferAmount(50);
        $stat->setOfferCount(5);
        $manager->persist($stat);

        $at = new \DateTime('2014-10-12 12:00:00');

        $stat = new StatCashOffer($at, 8, 156);
        $stat->setDomain(2);
        $stat->setParentId(7);
        $stat->setOfferOnlineDepositAmount(100);
        $stat->setOfferOnlineDepositCount(1);
        $stat->setOfferActiveAmount(10);
        $stat->setOfferActiveCount(1);
        $stat->setOfferAmount(110);
        $stat->setOfferCount(2);
        $manager->persist($stat);

        $stat = new StatCashOffer($at, 7, 901);
        $stat->setDomain(2);
        $stat->setParentId(6);
        $stat->setOfferCompanyDepositAmount(20);
        $stat->setOfferCompanyDepositCount(2);
        $stat->setOfferAmount(20);
        $stat->setOfferCount(2);
        $manager->persist($stat);

        $at = new \DateTime('2014-10-13 12:00:00');

        $stat = new StatCashOffer($at, 8, 156);
        $stat->setDomain(2);
        $stat->setParentId(7);
        $stat->setOfferActiveAmount(100);
        $stat->setOfferActiveCount(1);
        $stat->setOfferAmount(100);
        $stat->setOfferCount(1);
        $manager->persist($stat);

        $manager->flush();
    }
}
