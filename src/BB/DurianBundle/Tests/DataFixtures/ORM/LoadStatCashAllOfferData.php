<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\StatCashAllOffer;

class LoadStatCashAllOfferData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $at = new \DateTime('2013-01-07 12:00:00');

        $stat = new StatCashAllOffer($at, 6, 156);
        $stat->setDomain(2);
        $stat->setParentId(5);
        $stat->setOfferRebateRemitAmount(26);
        $stat->addOfferRebateRemitCount(2);
        $manager->persist($stat);

        $stat = new StatCashAllOffer($at, 7, 901);
        $stat->setDomain(2);
        $stat->setParentId(6);
        $stat->setOfferRebateRemitAmount(30);
        $stat->addOfferRebateRemitCount();
        $manager->persist($stat);

        $stat = new StatCashAllOffer($at, 8, 156);
        $stat->setDomain(2);
        $stat->setParentId(7);
        $stat->setOfferRebateRemitAmount(3);
        $stat->addOfferRebateRemitCount();
        $manager->persist($stat);

        $at = new \DateTime('2013-01-10 12:00:00');

        $stat = new StatCashAllOffer($at, 7, 901);
        $stat->setDomain(2);
        $stat->setParentId(6);
        $stat->setOfferRebateRemitAmount(90);
        $stat->addOfferRebateRemitCount(3);
        $manager->persist($stat);

        $stat = new StatCashAllOffer($at, 8, 156);
        $stat->setDomain(2);
        $stat->setParentId(7);
        $stat->setOfferRebateRemitAmount(8);
        $stat->addOfferRebateRemitCount(2);
        $manager->persist($stat);

        $at = new \DateTime('2013-01-11 12:00:00');

        $stat = new StatCashAllOffer($at, 6, 156);
        $stat->setDomain(2);
        $stat->setParentId(5);
        $stat->setOfferRebateRemitAmount(18);
        $stat->addOfferRebateRemitCount(3);
        $manager->persist($stat);

        $manager->flush();
    }
}
