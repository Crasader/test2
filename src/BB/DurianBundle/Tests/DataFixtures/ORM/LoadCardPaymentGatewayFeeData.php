<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\CardPaymentGatewayFee;

class LoadCardPaymentGatewayFeeData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $pgBBpay = $manager->find('BBDurianBundle:PaymentGateway', 1);
        $pgZZpay = $manager->find('BBDurianBundle:PaymentGateway', 2);

        $cardCharge = $manager->find('BBDurianBundle:CardCharge', 1);

        $cpgf1 = new CardPaymentGatewayFee($cardCharge, $pgBBpay);
        $cpgf1->setRate(0.5);
        $manager->persist($cpgf1);

        $cpgf2 = new CardPaymentGatewayFee($cardCharge, $pgZZpay);
        $cpgf2->setRate(0.2);
        $manager->persist($cpgf2);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCardChargeData'
        ];
    }
}
