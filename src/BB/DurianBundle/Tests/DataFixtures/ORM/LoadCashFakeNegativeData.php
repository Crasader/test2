<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\CashFakeNegative;

class LoadCashFakeNegativeData extends AbstractFixture
{
    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $neg = new CashFakeNegative(7, 156);
        $neg->setCashFakeId(1);
        $neg->setBalance(-1);
        $neg->setVersion(0);
        $neg->setEntryId(3);
        $neg->setAt(20161117223601);
        $neg->setAmount(-2);
        $neg->setEntryBalance(-1);
        $neg->setOpcode(1023);
        $neg->setRefId(2345);
        $neg->setMemo('測試備註');
        $manager->persist($neg);

        $neg = new CashFakeNegative(8, 156);
        $neg->setCashFakeId(2);
        $neg->setBalance(10);
        $neg->setVersion(0);
        $neg->setEntryId(9);
        $neg->setAt(20161118223601);
        $neg->setAmount(200);
        $neg->setEntryBalance(10);
        $neg->setOpcode(1010);
        $neg->setRefId(23477);
        $neg->setMemo('測試備註2');
        $manager->persist($neg);

        $manager->flush();
    }
}
