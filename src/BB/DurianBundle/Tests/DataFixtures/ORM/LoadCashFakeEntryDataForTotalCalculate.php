<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\CashFakeEntry;

class LoadCashFakeEntryDataForTotalCalculate extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {

        $fake = $manager->find('BB\DurianBundle\Entity\CashFake', 1);

        $entry = new CashFakeEntry($fake, 1006, 10000); // 1006 TRANSFER-4-IN 體育投注額度轉入
        $entry->setId(1);
        $entry->setRefId(0);
        $time = new \DateTime('2012-01-01 12:00:00');
        $entry->setCreatedAt($time);
        $entry->setAt(20120101120000);
        $manager->persist($entry);
        $fake->setBalance(10000);
        $fake->setLastEntryAt(20120101120000);

        $entry = new CashFakeEntry($fake, 1003, -5000); // 1003 TRANSFER
        $entry->setId(2);
        $entry->setRefId(1);
        $manager->persist($entry);
        $fake->setBalance(5000);
        $fake->setLastEntryAt(20120101120000);


        $fake = $manager->find('BB\DurianBundle\Entity\CashFake', 2);

        $entry = new CashFakeEntry($fake, 1003, 5000);
        $entry->setId(3);
        $entry->setRefId(0);
        $manager->persist($entry);
        $fake->setBalance(5000);
        $fake->setLastEntryAt(20120101120000);

        $entry = new CashFakeEntry($fake, 1003, -2500);
        $entry->setId(4);
        $entry->setRefId(0);
        $manager->persist($entry);
        $fake->setBalance(2500);
        $fake->setLastEntryAt(20120101120000);


        $fake = $manager->find('BB\DurianBundle\Entity\CashFake', 3);

        $entry = new CashFakeEntry($fake, 1003, 2500);
        $entry->setId(5);
        $entry->setRefId(0);
        $manager->persist($entry);
        $fake->setBalance(2500);
        $fake->setLastEntryAt(20120101120000);

        $entry = new CashFakeEntry($fake, 1003, -1250);
        $entry->setId(6);
        $entry->setRefId(0);
        $manager->persist($entry);
        $fake->setBalance(1250);
        $fake->setLastEntryAt(20120101120000);


        $fake = $manager->find('BB\DurianBundle\Entity\CashFake', 4);

        $entry = new CashFakeEntry($fake, 1003, 1250);
        $entry->setId(7);
        $entry->setRefId(0);
        $manager->persist($entry);
        $fake->setBalance(1250);
        $fake->setLastEntryAt(20120101120000);

        $entry = new CashFakeEntry($fake, 1003, -625);
        $entry->setId(8);
        $entry->setRefId(0);
        $manager->persist($entry);
        $fake->setBalance(625);
        $fake->setLastEntryAt(20120101120000);


        $fake = $manager->find('BB\DurianBundle\Entity\CashFake', 5);

        $entry = new CashFakeEntry($fake, 1003, 625);
        $entry->setId(9);
        $entry->setRefId(0);
        $manager->persist($entry);
        $fake->setBalance(625);
        $fake->setLastEntryAt(20120101120000);

        $entry = new CashFakeEntry($fake, 1003, -300);
        $entry->setId(10);
        $entry->setRefId(0);
        $manager->persist($entry);
        $fake->setBalance(325);
        $fake->setLastEntryAt(20120101120000);


        $fake = $manager->find('BB\DurianBundle\Entity\CashFake', 6);

        $entry = new CashFakeEntry($fake, 1003, 300);
        $entry->setId(11);
        $entry->setRefId(0);
        $manager->persist($entry);
        $fake->setBalance(300);
        $fake->setLastEntryAt(20120101120000);

        $entry = new CashFakeEntry($fake, 1003, -150);
        $entry->setId(12);
        $entry->setRefId(0);
        $manager->persist($entry);
        $fake->setBalance(150);
        $fake->setLastEntryAt(20120101120000);


        $fake = $manager->find('BB\DurianBundle\Entity\CashFake', 7);

        $entry = new CashFakeEntry($fake, 1003, 150);
        $entry->setId(13);
        $entry->setRefId(0);
        $manager->persist($entry);
        $fake->setBalance(150);
        $fake->setLastEntryAt(20120101120000);


        $fake = $manager->find('BB\DurianBundle\Entity\CashFake', 8);

        $entry = new CashFakeEntry($fake, 1006, 1000); // 1006 TRANSFER-4-IN 體育投注額度轉入
        $entry->setId(14);
        $entry->setRefId(0);
        $manager->persist($entry);
        $fake->setBalance(1000);
        $fake->setLastEntryAt(20120101120000);


        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeDataForTotalCalculate',
        );
    }
}
