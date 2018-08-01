<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\StatDomainDepositAmount;

class LoadStatDomainDepositAmountData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $stat = new StatDomainDepositAmount(6, 20160808);
        $stat->setAmount(500000.0000);
        $manager->persist($stat);

        $manager->flush();
    }
}
