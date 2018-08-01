<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\MerchantWithdrawLevelBankInfo;

class LoadMerchantWithdrawLevelBankInfoData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $bankInfo1 = $manager->find('BBDurianBundle:BankInfo', 1);
        $bankInfo2 = $manager->find('BBDurianBundle:BankInfo', 2);

        // merchantWithdraw: 1，levelId: 1
        $mwlbi1 = new MerchantWithdrawLevelBankInfo(1, 1, $bankInfo1);
        $manager->persist($mwlbi1);

        // merchantWithdraw: 2，levelId: 1
        $mwlbi2 = new MerchantWithdrawLevelBankInfo(2, 1, $bankInfo2);
        $manager->persist($mwlbi2);

        // merchantWithdraw: 2，levelId: 2
        $mwlbi3 = new MerchantWithdrawLevelBankInfo(2, 2, $bankInfo2);
        $manager->persist($mwlbi3);

        // merchantWithdraw: 5，levelId: 2 Neteller
        $bankInfo292 = $manager->find('BBDurianBundle:BankInfo', 292);
        $mwlbi4 = new MerchantWithdrawLevelBankInfo(5, 2, $bankInfo292);
        $manager->persist($mwlbi4);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankInfoData',
        ];
    }
}
