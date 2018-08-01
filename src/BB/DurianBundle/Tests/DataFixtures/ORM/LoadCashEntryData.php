<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\CashEntry;

class LoadCashEntryData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        // cash 1
        $cash = $manager->find('BB\DurianBundle\Entity\Cash', 1);

        $entry = new CashEntry($cash, 1001, 1000); // 1001 DEPOSIT
        $entry->setId(1);
        $entry->setRefId(238030097);
        $manager->persist($entry);
        $time = new \DateTime('2013-01-01 12:00:00');
        $entry->setCreatedAt($time);
        $entry->setAt(20130101120000);

        // 直接新增，cash不用setBalance，transferEntry也不用同步新增
        $entry = new CashEntry($cash, 1001, 100); // 1001 DEPOSIT
        $entry->setId(9);
        $entry->setRefId(11509530);
        $time = new \DateTime('2012-01-01 12:00:00');
        $entry->setCreatedAt($time);
        $entry->setAt(20120101120000);
        $manager->persist($entry);

        // 直接新增，cash不用setBalance，transferEntry也不用同步新增
        $entry = new CashEntry($cash, 1002, -80); // 1002 WITHDRAWAL
        $entry->setId(10);
        $entry->setRefId(5150840319);
        $entry->setMemo('123');
        $time = new \DateTime('2012-01-01 12:00:00');
        $entry->setCreatedAt($time);
        $entry->setAt(20120101120000);
        $manager->persist($entry);

        // cash 2
        $cash = $manager->find('BB\DurianBundle\Entity\Cash', 2);

        $entry = new CashEntry($cash, 1001, 1000); // 1001 DEPOSIT
        $entry->setId(2);
        $entry->setRefId(1899192299);
        $manager->persist($entry);
        $entry->setCreatedAt($time);
        $entry->setAt(20120101120000);

        // cash 3
        $cash = $manager->find('BB\DurianBundle\Entity\Cash', 3);

        $entry = new CashEntry($cash, 1001, 1000); // 1001 DEPOSIT
        $entry->setId(3);
        $entry->setRefId(0);
        $manager->persist($entry);
        $entry->setCreatedAt($time);
        $entry->setAt(20120101120000);

        // cash 4
        $cash = $manager->find('BB\DurianBundle\Entity\Cash', 4);

        $entry = new CashEntry($cash, 1001, 1000); // 1001 DEPOSIT
        $entry->setId(4);
        $entry->setRefId(0);
        $manager->persist($entry);
        $entry->setCreatedAt($time);
        $entry->setAt(20120101120000);

        // cash 5
        $cash = $manager->find('BB\DurianBundle\Entity\Cash', 5);

        $entry = new CashEntry($cash, 1001, 1000); // 1001 DEPOSIT
        $entry->setId(5);
        $entry->setRefId(0);
        $manager->persist($entry);
        $entry->setCreatedAt($time);
        $entry->setAt(20120101120000);

        // cash 6
        $cash = $manager->find('BB\DurianBundle\Entity\Cash', 6);

        $entry = new CashEntry($cash, 1001, 1000); // 1001 DEPOSIT
        $entry->setId(6);
        $entry->setRefId(0);
        $manager->persist($entry);
        $entry->setCreatedAt($time);
        $entry->setAt(20120101120000);

        // cash 7
        $cash = $manager->find('BB\DurianBundle\Entity\Cash', 7);

        $entry = new CashEntry($cash, 1001, 1000); // 1001 DEPOSIT
        $entry->setId(7);
        $entry->setRefId(0);
        $manager->persist($entry);
        $entry->setCreatedAt($time);
        $entry->setAt(20120101120000);

        // cash 8
        $cash = $manager->find('BB\DurianBundle\Entity\Cash', 8);

        $entry = new CashEntry($cash, 1001, 1000); // 1001 DEPOSIT
        $entry->setId(8);
        $entry->setRefId(0);
        $manager->persist($entry);
        $entry->setCreatedAt($time);
        $entry->setAt(20120101120000);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData'
        );
    }
}
