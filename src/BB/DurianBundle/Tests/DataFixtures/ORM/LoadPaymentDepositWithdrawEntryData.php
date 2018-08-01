<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\PaymentDepositWithdrawEntry;

class LoadPaymentDepositWithdrawEntryData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $entryRepo = $manager->getRepository('BBDurianBundle:CashEntry');

        $entry1 = $entryRepo->findOneBy(['id'=> 1]);
        $pEntry1 = new PaymentDepositWithdrawEntry($entry1, 2, 'company');
        $manager->persist($pEntry1);

        $entry2 = $entryRepo->findOneBy(['id'=> 2]);
        $pEntry2 = new PaymentDepositWithdrawEntry($entry2, 2, 'company2');
        $manager->persist($pEntry2);

        $entry3 = $entryRepo->findOneBy(['id'=> 3]);
        $pEntry3 = new PaymentDepositWithdrawEntry($entry3, 2);
        $manager->persist($pEntry3);

        $entry4 = $entryRepo->findOneBy(['id'=> 4]);
        $pEntry4 = new PaymentDepositWithdrawEntry($entry4, 2);
        $manager->persist($pEntry4);

        $entry5 = $entryRepo->findOneBy(['id'=> 5]);
        $pEntry5 = new PaymentDepositWithdrawEntry($entry5, 2);
        $manager->persist($pEntry5);

        $entry6 = $entryRepo->findOneBy(['id'=> 6]);
        $pEntry6 = new PaymentDepositWithdrawEntry($entry6, 2);
        $manager->persist($pEntry6);

        $entry7 = $entryRepo->findOneBy(['id'=> 7]);
        $pEntry7 = new PaymentDepositWithdrawEntry($entry7, 2);
        $manager->persist($pEntry7);

        $entry8 = $entryRepo->findOneBy(['id'=> 8]);
        $pEntry8 = new PaymentDepositWithdrawEntry($entry8, 9);
        $manager->persist($pEntry8);

        $entry9 = $entryRepo->findOneBy(['id'=> 9]);
        $pEntry9 = new PaymentDepositWithdrawEntry($entry9, 2, 'company');
        $manager->persist($pEntry9);

        $entry10 = $entryRepo->findOneBy(['id'=> 10]);
        $pEntry10 = new PaymentDepositWithdrawEntry($entry10, 2, 'company');
        $manager->persist($pEntry10);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return ['BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashEntryData'];
    }
}
