<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\CashEntryDiff;

class LoadCashEntryDiffData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $cashEntryDiff = new CashEntryDiff();
        $cashEntryDiff->setId(1234);
        $manager->persist($cashEntryDiff);
        $manager->flush();
    }
}
