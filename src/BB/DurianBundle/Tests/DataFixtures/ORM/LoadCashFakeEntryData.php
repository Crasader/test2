<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\CashFakeEntry;

class LoadCashFakeEntryData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {

        $fake = $manager->find('BB\DurianBundle\Entity\CashFake', 1);

        $entry = new CashFakeEntry($fake, 1006, 1000); // 1006 TRANSFER-4-IN 體育投注額度轉入
        $entry->setId(1);
        $entry->setRefId(5150840307);
        $manager->persist($entry);
        // 沒有用addCashFakeEntry要手動設
        $fake->setBalance(1000);
        $time = new \DateTime('2013-01-01 12:00:00');
        $entry->setCreatedAt($time);
        $entry->setAt(20130101120000);

        $entry = new CashFakeEntry($fake, 1003, -500); // 1003 TRANSFER
        $entry->setId(2);
        $entry->setRefId(5150840544);
        $manager->persist($entry);
        $fake->setBalance(500);
        $entry->setCreatedAt($time);
        $entry->setAt(20130101120000);

        $fake = $manager->find('BB\DurianBundle\Entity\CashFake', 2);

        $entry = new CashFakeEntry($fake, 1003, 500); // 1003 TRANSFER
        $entry->setId(3);
        $entry->setRefId(1899192866);
        $manager->persist($entry);
        $fake->setBalance(500);
        $entry->setCreatedAt($time);
        $entry->setAt(20130101120000);

        $entry = new CashFakeEntry($fake, 1001, 100); // 1001 DEPOSIT
        $entry->setId(4);
        $entry->setRefId(0);
        $manager->persist($entry);
        $entry->setCreatedAt($time);
        $entry->setAt(20130101120000);

        $entry = new CashFakeEntry($fake, 1002, 80); // 1002 WITHDRAWAL
        $entry->setId(5);
        $entry->setRefId(0);
        $entry->setMemo('123');
        $manager->persist($entry);
        $entry->setCreatedAt($time);
        $entry->setAt(20130101120000);

        $entry = new CashFakeEntry($fake, 1001, 500);
        $entry->setId(6);
        $entry->setRefId(1);
        $manager->persist($entry);
        $fake->setBalance(500);
        $time = new \DateTime('2014-06-16 12:00:00');
        $entry->setCreatedAt($time);
        $entry->setAt(20140616120000);

        $entry = new CashFakeEntry($fake, 1001, 1000);
        $entry->setId(7);
        $entry->setRefId(2);
        $entry->setMemo('123');
        $manager->persist($entry);
        $entry->setCreatedAt($time);
        $entry->setAt(20140616120000);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeData',
        );
    }
}
