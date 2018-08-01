<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\StatCashOpcodeHK;

class LoadStatCashOpcodeHKData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $at = new \DateTime('2014-10-10 01:00:00');

        $stat = new StatCashOpcodeHK($at, 8, 156);
        $stat->setDomain(2);
        $stat->setParentId(7);
        $stat->setOpcode(1001);
        $stat->setAmount(1000);
        $stat->setCount(5);
        $manager->persist($stat);

        $stat = new StatCashOpcodeHK($at, 8, 156);
        $stat->setDomain(2);
        $stat->setParentId(7);
        $stat->setOpcode(1011);
        $stat->setAmount(50);
        $stat->setCount(5);
        $manager->persist($stat);

        $stat = new StatCashOpcodeHK($at, 7, 156);
        $stat->setDomain(2);
        $stat->setParentId(6);
        $stat->setOpcode(1001);
        $stat->setAmount(1000);
        $stat->setCount(2);
        $manager->persist($stat);

        $stat = new StatCashOpcodeHK($at, 7, 156);
        $stat->setDomain(2);
        $stat->setParentId(6);
        $stat->setOpcode(1011);
        $stat->setAmount(100);
        $stat->setCount(5);
        $manager->persist($stat);

        $manager->flush();
    }
}
