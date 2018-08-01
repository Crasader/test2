<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\PaymentGatewayCurrency;

class LoadPaymentGatewayCurrencyData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $paymentGateway = $manager->find('BBDurianBundle:PaymentGateway', 1);

        $pgCurrency1 = new PaymentGatewayCurrency($paymentGateway, 156); // CNY
        $manager->persist($pgCurrency1);

        $paymentGateway = $manager->find('BBDurianBundle:PaymentGateway', 2);

        $pgCurrency2 = new PaymentGatewayCurrency($paymentGateway, 156); // CNY
        $manager->persist($pgCurrency2);

        $pgCurrency3 = new PaymentGatewayCurrency($paymentGateway, 840); // USD
        $manager->persist($pgCurrency3);

        // neteller
        $paymentGateway68 = $manager->find('BBDurianBundle:PaymentGateway', 68);

        $pgCurrency5 = new PaymentGatewayCurrency($paymentGateway68, 840); // USD
        $manager->persist($pgCurrency5);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayData'
        );
    }
}
