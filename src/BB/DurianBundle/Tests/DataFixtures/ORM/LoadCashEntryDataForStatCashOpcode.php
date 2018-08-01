<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\CashEntry;

class LoadCashEntryDataForStatCashOpcode extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $user = $manager->find('BBDurianBundle:User', 7);
        $cash7 = $user->getCash();

        $user = $manager->find('BBDurianBundle:User', 8);
        $cash8 = $user->getCash();

        $user = $manager->find('BBDurianBundle:User', 2);
        $cash2 = $user->getCash();

        $time0110 = new \DateTime('2013-01-10 13:00:00');
        $time0112 = new \DateTime('2013-01-12 14:00:00');

        $entryId = 1;

        $entry = new CashEntry($cash7, 1036, 15);
        $entry->setId($entryId++);
        $entry->setRefId(0);
        $entry->setCreatedAt($time0110);
        $entry->setAt(20130110130000);
        $manager->persist($entry);

        $entry = new CashEntry($cash7, 1036, 10);
        $entry->setId($entryId++);
        $entry->setRefId(0);
        $entry->setCreatedAt($time0110);
        $entry->setAt(20130110130000);
        $manager->persist($entry);

        $entry = new CashEntry($cash8, 1036, 45);
        $entry->setId($entryId++);
        $entry->setRefId(0);
        $entry->setCreatedAt($time0110);
        $entry->setAt(20130110130000);
        $manager->persist($entry);

        $entry = new CashEntry($cash8, 1010, 35);
        $entry->setId($entryId++);
        $entry->setRefId(0);
        $entry->setCreatedAt($time0112);
        $entry->setAt(20130112140000);
        $manager->persist($entry);

        $entry = new CashEntry($cash7, 1036, 10);
        $entry->setId($entryId++);
        $entry->setRefId(0);
        $entry->setCreatedAt($time0112);
        $entry->setAt(20130112140000);
        $manager->persist($entry);

        $entry = new CashEntry($cash8, 1037, 45);
        $entry->setId($entryId++);
        $entry->setRefId(0);
        $entry->setCreatedAt($time0112);
        $entry->setAt(20130112140000);
        $manager->persist($entry);

        $entry = new CashEntry($cash8, 1052, 6);
        $entry->setId($entryId++);
        $entry->setRefId(0);
        $entry->setCreatedAt($time0112);
        $entry->setAt(20130112140000);
        $manager->persist($entry);

        // 不會被列入統計
        $entry = new CashEntry($cash7, 9999, 10);
        $entry->setId($entryId++);
        $entry->setRefId(0);
        $entry->setCreatedAt($time0112);
        $entry->setAt(20130112140000);
        $manager->persist($entry);

        // 廳主
        $entry = new CashEntry($cash2, 1039, 1000);
        $entry->setId($entryId++);
        $entry->setRefId(0);
        $entry->setCreatedAt($time0112);
        $entry->setAt(20130112140000);
        $manager->persist($entry);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
        ];
    }
}
