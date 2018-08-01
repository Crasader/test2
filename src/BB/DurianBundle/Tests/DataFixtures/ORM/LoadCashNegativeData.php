<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\CashNegative;

class LoadCashNegativeData extends AbstractFixture
{
    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $neg = new CashNegative(2, 901);
        $neg->setCashId(1);
        $neg->setBalance(-1);
        $neg->setVersion(2);
        $neg->setEntryId(3);
        $neg->setAt(20161117223601);
        $neg->setAmount(-2);
        $neg->setEntryBalance(-1);
        $neg->setOpcode(1023);
        $neg->setRefId(2345);
        $neg->setMemo('測試備註');
        $manager->persist($neg);

        $neg = new CashNegative(3, 901);
        $neg->setCashId(2);
        $neg->setBalance(10);
        $neg->setVersion(1);
        $neg->setEntryId(9);
        $neg->setAt(20161118223601);
        $neg->setAmount(200);
        $neg->setEntryBalance(10);
        $neg->setOpcode(1010);
        $neg->setRefId(23477);
        $neg->setMemo('測試備註2');
        $manager->persist($neg);

        $neg = new CashNegative(8, 156);
        $neg->setCashId(7);
        $neg->setBalance(123);
        $neg->setVersion(2);
        $neg->setEntryId(3);
        $neg->setAt(20170214223601);
        $neg->setAmount(50);
        $neg->setEntryBalance(123);
        $neg->setOpcode(1023);
        $neg->setRefId(1234);
        $neg->setMemo('測試備註3');
        $manager->persist($neg);

        $manager->flush();
    }
}
