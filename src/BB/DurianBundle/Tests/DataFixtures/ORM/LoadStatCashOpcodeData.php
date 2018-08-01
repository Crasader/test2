<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\StatCashOpcode;

class LoadStatCashOpcodeData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $at = new \DateTime('2014-10-10 12:00:00');

        $stat = new StatCashOpcode($at, 8, 156);
        $stat->setDomain(2);
        $stat->setParentId(7);
        $stat->setOpcode(1001);
        $stat->setAmount(1000);
        $stat->setCount(5);
        $manager->persist($stat);

        $stat = new StatCashOpcode($at, 8, 156);
        $stat->setDomain(2);
        $stat->setParentId(7);
        $stat->setOpcode(1011);
        $stat->setAmount(50);
        $stat->setCount(5);
        $manager->persist($stat);

        $stat = new StatCashOpcode($at, 8, 156);
        $stat->setDomain(2);
        $stat->setParentId(7);
        $stat->setOpcode(1012);
        $stat->setAmount(5);
        $stat->setCount(5);
        $manager->persist($stat);

        $stat = new StatCashOpcode($at, 8, 156);
        $stat->setDomain(2);
        $stat->setParentId(7);
        $stat->setOpcode(1002);
        $stat->setAmount(-400);
        $stat->setCount(2);
        $manager->persist($stat);

        $stat = new StatCashOpcode($at, 8, 156);
        $stat->setDomain(2);
        $stat->setParentId(7);
        $stat->setOpcode(1005);
        $stat->setAmount(200);
        $stat->setCount(1);
        $manager->persist($stat);

        $stat = new StatCashOpcode($at, 7, 901);
        $stat->setDomain(2);
        $stat->setParentId(6);
        $stat->setOpcode(1010);
        $stat->setAmount(2000);
        $stat->setCount(2);
        $manager->persist($stat);

        $stat = new StatCashOpcode($at, 7, 901);
        $stat->setDomain(2);
        $stat->setParentId(6);
        $stat->setOpcode(1024);
        $stat->setAmount(100);
        $stat->setCount(5);
        $manager->persist($stat);

        $stat = new StatCashOpcode($at, 7, 901);
        $stat->setDomain(2);
        $stat->setParentId(6);
        $stat->setOpcode(1029);
        $stat->setAmount(-20);
        $stat->setCount(1);
        $manager->persist($stat);

        $stat = new StatCashOpcode($at, 7, 901);
        $stat->setDomain(2);
        $stat->setParentId(6);
        $stat->setOpcode(1054);
        $stat->setAmount(20);
        $stat->setCount(1);
        $manager->persist($stat);

        $at = new \DateTime('2014-10-12 12:00:00');

        $stat = new StatCashOpcode($at, 8, 156);
        $stat->setDomain(2);
        $stat->setParentId(7);
        $stat->setOpcode(1039);
        $stat->setAmount(1000);
        $stat->setCount(1);
        $manager->persist($stat);

        $stat = new StatCashOpcode($at, 8, 156);
        $stat->setDomain(2);
        $stat->setParentId(7);
        $stat->setOpcode(1041);
        $stat->setAmount(10);
        $stat->setCount(1);
        $manager->persist($stat);

        $stat = new StatCashOpcode($at, 8, 156);
        $stat->setDomain(2);
        $stat->setParentId(7);
        $stat->setOpcode(1053);
        $stat->setAmount(100);
        $stat->setCount(3);
        $manager->persist($stat);

        $stat = new StatCashOpcode($at, 7, 901);
        $stat->setDomain(2);
        $stat->setParentId(6);
        $stat->setOpcode(1036);
        $stat->setAmount(2000);
        $stat->setCount(2);
        $manager->persist($stat);

        $stat = new StatCashOpcode($at, 7, 901);
        $stat->setDomain(2);
        $stat->setParentId(6);
        $stat->setOpcode(1037);
        $stat->setAmount(20);
        $stat->setCount(2);
        $manager->persist($stat);

        $stat = new StatCashOpcode($at, 7, 901);
        $stat->setDomain(2);
        $stat->setParentId(6);
        $stat->setOpcode(1038);
        $stat->setAmount(2);
        $stat->setCount(2);
        $manager->persist($stat);

        $stat = new StatCashOpcode($at, 7, 901);
        $stat->setDomain(2);
        $stat->setParentId(6);
        $stat->setOpcode(1014);
        $stat->setAmount(-1000);
        $stat->setCount(1);
        $manager->persist($stat);

        $at = new \DateTime('2014-10-13 12:00:00');

        $stat = new StatCashOpcode($at, 8, 156);
        $stat->setDomain(2);
        $stat->setParentId(7);
        $stat->setOpcode(1048);
        $stat->setAmount(20);
        $stat->setCount(1);
        $manager->persist($stat);

        $stat = new StatCashOpcode($at, 8, 156);
        $stat->setDomain(2);
        $stat->setParentId(7);
        $stat->setOpcode(1053);
        $stat->setAmount(100);
        $stat->setCount(1);
        $manager->persist($stat);

        $stat = new StatCashOpcode($at, 8, 156);
        $stat->setDomain(2);
        $stat->setParentId(7);
        $stat->setOpcode(1095);
        $stat->setAmount(200);
        $stat->setCount(1);
        $manager->persist($stat);

        $manager->flush();
    }
}
