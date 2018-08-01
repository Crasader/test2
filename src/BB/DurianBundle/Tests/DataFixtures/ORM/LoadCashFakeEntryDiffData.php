<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\CashFakeEntryDiff;

class LoadCashFakeEntryDiffData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $cashFakeEntryDiff = new CashFakeEntryDiff();
        $cashFakeEntryDiff->setId(1234);
        $manager->persist($cashFakeEntryDiff);
        $manager->flush();
    }
}
