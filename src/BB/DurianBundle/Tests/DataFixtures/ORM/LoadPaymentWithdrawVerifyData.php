<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\PaymentWithdrawVerify;

class LoadPaymentWithdrawVerifyData extends AbstractFixture implements DependentFixtureInterface
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

        $pwv = new PaymentWithdrawVerify($pcTWD);
        $pwv->setNeedVerify(false);
        $pwv->setVerifyTime(0);
        $pwv->setVerifyAmount(0);
        $manager->persist($pwv);

        $pwv = new PaymentWithdrawVerify($pcCNY);
        $pwv->setNeedVerify(true);
        $pwv->setVerifyTime(12);
        $pwv->setVerifyAmount(6000);
        $manager->persist($pwv);

        $pwv = new PaymentWithdrawVerify($pcUSD);
        $pwv->setNeedVerify(true);
        $pwv->setVerifyTime(2);
        $pwv->setVerifyAmount(100000);
        $manager->persist($pwv);

        $pwv = new PaymentWithdrawVerify($pcCusTWD, false, 0, 0);
        $pwv->setNeedVerify(false);
        $pwv->setVerifyTime(0);
        $pwv->setVerifyAmount(0);
        $manager->persist($pwv);

        $pwv = new PaymentWithdrawVerify($pcCusCNY);
        $pwv->setNeedVerify(true);
        $pwv->setVerifyTime(24);
        $pwv->setVerifyAmount(12000);
        $manager->persist($pwv);

        $pwv = new PaymentWithdrawVerify($pcCusUSD);
        $pwv->setNeedVerify(true);
        $pwv->setVerifyTime(2);
        $pwv->setVerifyAmount(200000);
        $manager->persist($pwv);

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
