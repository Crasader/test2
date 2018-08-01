<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\StatDomainCashOpcode;

class LoadStatDomainCashOpcodeData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $at = new \DateTime('2014-05-07 12:00:00');

        // withdraw_manual
        $stat1 = new StatDomainCashOpcode($at, 8, 156, 1013);
        $stat1->setDomain(2);
        $stat1->setAmount(100);
        $stat1->addCount(3);
        $manager->persist($stat1);

        $stat2 = new StatDomainCashOpcode($at, 51, 156, 1014);
        $stat2->setDomain(2);
        $stat2->setAmount(2000);
        $stat2->addCount(1);
        $manager->persist($stat2);

        // deposit_manual
        $stat3 = new StatDomainCashOpcode($at, 8, 156, 1010);
        $stat3->setDomain(2);
        $stat3->setAmount(3000);
        $stat3->addCount(1);
        $manager->persist($stat3);

        // offer
        $stat4 = new StatDomainCashOpcode($at, 8, 156, 1011);
        $stat4->setDomain(2);
        $stat4->setAmount(4000);
        $stat4->addCount(5);
        $manager->persist($stat4);

        // rebate
        $stat5 = new StatDomainCashOpcode($at, 8, 156, 1024);
        $stat5->setDomain(2);
        $stat5->setAmount(5000);
        $stat5->addCount(32);
        $manager->persist($stat5);

        // deposit_company
        $stat6 = new StatDomainCashOpcode($at, 8, 156, 1036);
        $stat6->setDomain(2);
        $stat6->setAmount(1234);
        $stat6->addCount(5);
        $manager->persist($stat6);

        // deposit_online
        $stat7 = new StatDomainCashOpcode($at, 8, 156, 1040);
        $stat7->setDomain(2);
        $stat7->setAmount(2234);
        $stat7->addCount(3);
        $manager->persist($stat7);

        $manager->flush();
    }
}
