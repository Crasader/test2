<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\OutsideEntry;

class LoadOutsideEntryData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $entry = new OutsideEntry();
        $entry->setId(100);
        $entry->setUserId(1);
        $entry->setCurrency(156);
        $entry->setCreatedAt('20170301111111');
        $entry->setOpcode(1001);
        $entry->setAmount(10);
        $entry->setBalance(10);
        $entry->setMemo('test-memo');
        $entry->setRefId(1);
        $entry->setGroup(1);
        $manager->persist($entry);

        $entry2 = new OutsideEntry();
        $entry2->setId(101);
        $entry2->setUserId(1);
        $entry2->setCurrency(156);
        $entry2->setCreatedAt('20170401111111');
        $entry2->setOpcode(1001);
        $entry2->setAmount(10);
        $entry2->setBalance(10);
        $entry2->setMemo('test-memo');
        $entry2->setRefId(2);
        $entry2->setGroup(1);
        $manager->persist($entry2);

        $entry3 = new OutsideEntry();
        $entry3->setId(102);
        $entry3->setUserId(1);
        $entry3->setCurrency(156);
        $entry3->setCreatedAt('20170401111111');
        $entry3->setOpcode(1001);
        $entry3->setAmount(10);
        $entry3->setBalance(10);
        $entry3->setMemo('test-memo');
        $entry3->setRefId(1);
        $entry3->setGroup(1);
        $manager->persist($entry3);

        $entry4 = new OutsideEntry();
        $entry4->setId(103);
        $entry4->setUserId(8);
        $entry4->setCurrency(156);
        $entry4->setCreatedAt('20170301111111');
        $entry4->setOpcode(1010);
        $entry4->setAmount(10);
        $entry4->setBalance(10);
        $entry4->setMemo('test-memo');
        $entry4->setRefId(11);
        $entry4->setGroup(1);
        $manager->persist($entry4);

        $entry5 = new OutsideEntry();
        $entry5->setId(104);
        $entry5->setUserId(8);
        $entry5->setCurrency(156);
        $entry5->setCreatedAt('20170401111111');
        $entry5->setOpcode(1010);
        $entry5->setAmount(-7);
        $entry5->setBalance(3);
        $entry5->setMemo('test-memo');
        $entry5->setRefId(21);
        $entry5->setGroup(1);
        $manager->persist($entry5);

        $manager->flush();
    }
}
