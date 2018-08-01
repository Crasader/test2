<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\PaymentGatewayDescription;

class LoadPaymentGatewayDescriptionData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $paymentGateway1 = $manager->find('BBDurianBundle:PaymentGateway', 1);
        $paymentGateway2 = $manager->find('BBDurianBundle:PaymentGateway', 2);

        $paymentGatewayDescription1 = new PaymentGatewayDescription($paymentGateway1, 'number', '987654321');
        $manager->persist($paymentGatewayDescription1);

        $paymentGatewayDescription2 = new PaymentGatewayDescription($paymentGateway1, 'private_key', 'testtest');
        $manager->persist($paymentGatewayDescription2);

        $paymentGatewayDescription3 = new PaymentGatewayDescription($paymentGateway1, 'terminalId', '77777777');
        $manager->persist($paymentGatewayDescription3);

        $paymentGatewayDescription4 = new PaymentGatewayDescription($paymentGateway2, 'number', '123456789');
        $manager->persist($paymentGatewayDescription4);

        $paymentGatewayDescription5 = new PaymentGatewayDescription($paymentGateway2, 'private_key', 'abcdefg');
        $manager->persist($paymentGatewayDescription5);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayData',
        ];
    }
}
