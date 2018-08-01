<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\PaymentWithdrawFee;

class LoadPaymentWithdrawFeeData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $pcTWD = $manager->find('BBDurianBundle:PaymentCharge', 1);
        $pcCNY = $manager->find('BBDurianBundle:PaymentCharge', 2);
        $pcUSD = $manager->find('BBDurianBundle:PaymentCharge', 3);
        $pcCusTWD = $manager->find('BBDurianBundle:PaymentCharge', 4);
        $pcCusCNY = $manager->find('BBDurianBundle:PaymentCharge', 5);
        $pcCusUSD = $manager->find('BBDurianBundle:PaymentCharge', 6);

        $pwf = new PaymentWithdrawFee($pcTWD);
        $pwf->setFreePeriod(6);
        $pwf->setFreeCount(2);
        $pwf->setAmountMax(12000);
        $pwf->setAmountPercent(5);
        $pwf->setWithdrawMax(18000);
        $pwf->setWithdrawMin(600);
        $manager->persist($pwf);

        $pwf = new PaymentWithdrawFee($pcCNY);
        $pwf->setFreePeriod(3);
        $pwf->setFreeCount(1);
        $pwf->setAmountMax(6000);
        $pwf->setAmountPercent(3);
        $pwf->setWithdrawMax(9000);
        $pwf->setWithdrawMin(200);
        $manager->persist($pwf);

        $pwf = new PaymentWithdrawFee($pcUSD);
        $pwf->setFreePeriod(24);
        $pwf->setFreeCount(1);
        $pwf->setAmountMax(100000);
        $pwf->setAmountPercent(3);
        $pwf->setWithdrawMax(150000);
        $pwf->setWithdrawMin(3300);
        $manager->persist($pwf);

        $pwf = new PaymentWithdrawFee($pcCusTWD);
        $pwf->setFreePeriod(6);
        $pwf->setFreeCount(3);
        $pwf->setAmountMax(24000);
        $pwf->setAmountPercent(1);
        $pwf->setWithdrawMax(36000);
        $pwf->setWithdrawMin(800);
        $manager->persist($pwf);

        $pwf = new PaymentWithdrawFee($pcCusCNY);
        $pwf->setFreePeriod(3);
        $pwf->setFreeCount(2);
        $pwf->setAmountMax(24000);
        $pwf->setAmountPercent(1);
        $pwf->setWithdrawMax(18000);
        $pwf->setWithdrawMin(600);
        $manager->persist($pwf);

        $pwf = new PaymentWithdrawFee($pcCusUSD);
        $pwf->setFreePeriod(24);
        $pwf->setFreeCount(2);
        $pwf->setAmountMax(200000);
        $pwf->setAmountPercent(2.2);
        $pwf->setWithdrawMax(300000);
        $pwf->setWithdrawMin(6600);
        $pwf->setMobileFreePeriod(50);
        $pwf->setMobileFreeCount(30);
        $pwf->setMobileAmountMax(100);
        $pwf->setMobileAmountPercent(0.8);
        $pwf->setMobileWithdrawMax(10000.366);
        $pwf->setMobileWithdrawMin(50.2);
        $manager->persist($pwf);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentChargeData'
        );
    }
}
