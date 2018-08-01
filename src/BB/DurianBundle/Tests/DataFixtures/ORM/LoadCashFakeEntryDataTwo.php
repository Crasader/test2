<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\CashFakeEntry;

class LoadCashFakeEntryDataTwo extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {

        $fake = $manager->find('BB\DurianBundle\Entity\CashFake', 3);

        $entry = new CashFakeEntry($fake, 1001, 199); // 1001 DEPOSIT
        $entry->setId(6);
        $entry->setRefId(0);
        $manager->persist($entry);
        $fake->setBalance(199);

        $entry = new CashFakeEntry($fake, 1001, 299); // 1002 WITHDRAWAL
        $entry->setId(7);
        $entry->setRefId(0);
        $manager->persist($entry);
        $fake->setBalance(498);


        $fake = $manager->find('BB\DurianBundle\Entity\CashFake', 4);

        $entry = new CashFakeEntry($fake, 1001, 199); // 1001 DEPOSIT
        $entry->setId(8);
        $entry->setRefId(0);
        $manager->persist($entry);
        $fake->setBalance(199);

        $entry = new CashFakeEntry($fake, 1001, 299); // 1002 WITHDRAWAL
        $entry->setId(9);
        $entry->setRefId(0);
        $manager->persist($entry);
        $fake->setBalance(498);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeDataTwo',
        );
    }
}
