<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\PaymentGatewayFee;

class LoadPaymentGatewayFeeData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $pgBBpay = $manager->find('BBDurianBundle:PaymentGateway', 1);
        $pgZZpay = $manager->find('BBDurianBundle:PaymentGateway', 2);

        $pcTWD = $manager->find('BBDurianBundle:PaymentCharge', 1);
        $pcCNY = $manager->find('BBDurianBundle:PaymentCharge', 2);
        $pcUSD = $manager->find('BBDurianBundle:PaymentCharge', 3);
        $pcTWDCus = $manager->find('BBDurianBundle:PaymentCharge', 4);
        $pcCNYCus = $manager->find('BBDurianBundle:PaymentCharge', 5);
        $pcUSDCus = $manager->find('BBDurianBundle:PaymentCharge', 6);

        $pgf = new PaymentGatewayFee($pcTWD, $pgBBpay);
        $pgf->setRate(0.5);
        $manager->persist($pgf);

        $pgf = new PaymentGatewayFee($pcCNY, $pgBBpay);
        $pgf->setRate(2);
        $manager->persist($pgf);

        $pgf = new PaymentGatewayFee($pcUSD, $pgBBpay);
        $pgf->setRate(4.25);
        $manager->persist($pgf);

        $pgf = new PaymentGatewayFee($pcTWD, $pgZZpay);
        $pgf->setRate(0.2);
        $manager->persist($pgf);

        $pgf = new PaymentGatewayFee($pcCNY, $pgZZpay);
        $pgf->setRate(2.285);
        $manager->persist($pgf);

        $pgf = new PaymentGatewayFee($pcUSD, $pgZZpay);
        $pgf->setRate(3);
        $manager->persist($pgf);

        $pgf = new PaymentGatewayFee($pcTWDCus, $pgBBpay);
        $pgf->setRate(0.76);
        $manager->persist($pgf);

        $pgf = new PaymentGatewayFee($pcCNYCus, $pgBBpay);
        $pgf->setRate(2.5);
        $manager->persist($pgf);

        $pgf = new PaymentGatewayFee($pcUSDCus, $pgBBpay);
        $pgf->setRate(445);
        $pgf->setWithdrawRate(123);
        $manager->persist($pgf);

        $pgf = new PaymentGatewayFee($pcTWDCus, $pgZZpay);
        $pgf->setRate(0.28);
        $manager->persist($pgf);

        $pgf = new PaymentGatewayFee($pcCNYCus, $pgZZpay);
        $pgf->setRate(2.291);
        $manager->persist($pgf);

        $pgf = new PaymentGatewayFee($pcUSDCus, $pgZZpay);
        $pgf->setRate(3.6);
        $pgf->setWithdrawRate(456);
        $manager->persist($pgf);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentChargeData'
        );
    }
}
