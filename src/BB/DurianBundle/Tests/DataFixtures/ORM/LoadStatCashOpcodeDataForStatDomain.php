<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\StatCashOpcode;

class LoadStatCashOpcodeDataForStatDomain extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $at = new \DateTime('2014-05-07 12:00:00');

        $stat = new StatCashOpcode($at, 8, 156);
        $stat->setDomain(2);
        $stat->setParentId(7);
        $stat->setOpcode(1013);
        $stat->setAmount(1000);
        $stat->setCount(5);
        $manager->persist($stat);

        $stat = new StatCashOpcode($at, 8, 156);
        $stat->setDomain(2);
        $stat->setParentId(7);
        $stat->setOpcode(1014);
        $stat->setAmount(2000);
        $stat->setCount(5);
        $manager->persist($stat);

        $stat = new StatCashOpcode($at, 8, 156);
        $stat->setDomain(2);
        $stat->setParentId(7);
        $stat->setOpcode(1010);
        $stat->setAmount(3000);
        $stat->setCount(5);
        $manager->persist($stat);

        $stat = new StatCashOpcode($at, 8, 156);
        $stat->setDomain(2);
        $stat->setParentId(7);
        $stat->setOpcode(1011);
        $stat->setAmount(4000);
        $stat->setCount(2);
        $manager->persist($stat);

        $stat = new StatCashOpcode($at, 8, 156);
        $stat->setDomain(2);
        $stat->setParentId(7);
        $stat->setOpcode(1024);
        $stat->setAmount(5000);
        $stat->setCount(1);
        $manager->persist($stat);

        $manager->flush();
    }
}
