<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\CashEntry;

class LoadCashEntryDataForDuplicateDeposit extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $cash6 = $manager->find('BBDurianBundle:Cash', 6);

        $entryId = 1;

        $createAt = new \DateTime('2014-10-02 10:23:37');

        // 公司入款
        // 一個CashEntry的RefId屬於remitEntry1
        $entry = new CashEntry($cash6, 1036, 5); // 1036 DEPOSIT
        $entry->setId($entryId++);
        $entry->setRefId(2012010100002459);
        $entry->setCreatedAt($createAt);
        $entry->setAt(20141002102337);
        $manager->persist($entry);

        // 兩個CashEntry的RefId屬於remitEntry2
        for ($i = 0; $i < 2; $i++) {
            $entry = new CashEntry($cash6, 1036, 10); // 1036 DEPOSIT
            $entry->setId($entryId++);
            $entry->setRefId(2012030500003548);
            $entry->setCreatedAt($createAt);
            $entry->setAt(20141002102337);
            $manager->persist($entry);
        }

        // 兩個CashEntry的RefId屬於remitEntry9
        for ($i = 0; $i < 2; $i++) {
            $entry = new CashEntry($cash6, 1036, 999); // 1036 DEPOSIT
            $entry->setId($entryId++);
            $entry->setRefId(2016101215493577);
            $entry->setCreatedAt($createAt);
            $entry->setAt(20141002102337);
            $manager->persist($entry);
        }

        // 線上入款
        // 一個CashEntry的RefId屬於cashDepositEntry1
        $entry = new CashEntry($cash6, 1039, 5); // 1039 DEPOSIT
        $entry->setId($entryId++);
        $entry->setRefId(201304280000000001);
        $entry->setCreatedAt($createAt);
        $entry->setAt(20141002102337);
        $manager->persist($entry);

        // 兩個CashEntry的RefId屬於cashDepositEntry1
        for ($i = 0; $i < 2; $i++) {
            $entry = new CashEntry($cash6, 1039, 10); // 1039 DEPOSIT
            $entry->setId($entryId++);
            $entry->setRefId(201305280000000001);
            $entry->setCreatedAt($createAt);
            $entry->setAt(20141002102337);
            $manager->persist($entry);
        }

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemitEntryData'
        ];
    }
}
