<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\StatDomainCashOpcodeHK;

class LoadStatDomainCashOpcodeHKData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $at = new \DateTime('2014-05-07 00:00:00');

        // withdraw_manual
        $stat1 = new StatDomainCashOpcodeHK($at, 8, 156, 1013);
        $stat1->setDomain(2);
        $stat1->setAmount(100);
        $stat1->addCount(3);
        $manager->persist($stat1);

        $stat2 = new StatDomainCashOpcodeHK($at, 51, 156, 1014);
        $stat2->setDomain(2);
        $stat2->setAmount(2000);
        $stat2->addCount(1);
        $manager->persist($stat2);

        // deposit_manual
        $stat3 = new StatDomainCashOpcodeHK($at, 8, 156, 1010);
        $stat3->setDomain(2);
        $stat3->setAmount(3000);
        $stat3->addCount(1);
        $manager->persist($stat3);

        // offer
        $stat4 = new StatDomainCashOpcodeHK($at, 8, 156, 1011);
        $stat4->setDomain(2);
        $stat4->setAmount(4000);
        $stat4->addCount(5);
        $manager->persist($stat4);

        // rebate
        $stat5 = new StatDomainCashOpcodeHK($at, 8, 156, 1024);
        $stat5->setDomain(2);
        $stat5->setAmount(5000);
        $stat5->addCount(32);
        $manager->persist($stat5);

        // company
        $stat6 = new StatDomainCashOpcodeHK($at, 8, 156, 1036);
        $stat6->setDomain(2);
        $stat6->setAmount(1234);
        $stat6->addCount(2);
        $manager->persist($stat6);

        // online
        $stat6 = new StatDomainCashOpcodeHK($at, 8, 156, 1040);
        $stat6->setDomain(2);
        $stat6->setAmount(2234);
        $stat6->addCount(1);
        $manager->persist($stat6);

        $manager->flush();
    }
}
