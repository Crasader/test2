<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\StatCashRemit;

class LoadStatCashRemitData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $at = new \DateTime('2013-01-07 12:00:00');

        $stat = new StatCashRemit($at, 6, 156);
        $stat->setDomain(2);
        $stat->setParentId(5);
        $stat->setOfferRemitAmount(13);
        $stat->addOfferRemitCount();
        $stat->setRemitAmount(13);
        $stat->addRemitCount();
        $manager->persist($stat);

        $at = new \DateTime('2013-01-10 12:00:00');

        $stat = new StatCashRemit($at, 7, 901);
        $stat->setDomain(2);
        $stat->setParentId(6);
        $stat->setOfferRemitAmount(50);
        $stat->addOfferRemitCount();
        $stat->setRemitAmount(50);
        $stat->addRemitCount();
        $manager->persist($stat);

        $at = new \DateTime('2013-01-11 12:00:00');

        $stat = new StatCashRemit($at, 8, 156);
        $stat->setDomain(2);
        $stat->setParentId(7);
        $stat->setOfferRemitAmount(9);
        $stat->addOfferRemitCount();
        $stat->setRemitAmount(9);
        $stat->addRemitCount();
        $manager->persist($stat);

        $at = new \DateTime('2014-10-10 12:00:00');

        $stat = new StatCashRemit($at, 8, 156);
        $stat->setDomain(2);
        $stat->setParentId(7);
        $stat->setOfferRemitAmount(5);
        $stat->setOfferRemitCount(5);
        $stat->setRemitAmount(5);
        $stat->setRemitCount(5);
        $manager->persist($stat);

        $at = new \DateTime('2014-10-12 12:00:00');

        $stat = new StatCashRemit($at, 7, 901);
        $stat->setDomain(2);
        $stat->setParentId(6);
        $stat->setOfferCompanyRemitAmount(2);
        $stat->setOfferCompanyRemitCount(2);
        $stat->setRemitAmount(2);
        $stat->setRemitCount(2);
        $manager->persist($stat);

        $manager->flush();
    }
}
