<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class LoadPaymentGatewayHasPaymentMethodData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $paymentGateway = $manager->find('BBDurianBundle:PaymentGateway', 1);

        $pm1 = $manager->find('BBDurianBundle:PaymentMethod', 1);
        $paymentGateway->addPaymentMethod($pm1);

        $pm2 = $manager->find('BBDurianBundle:PaymentMethod', 2);
        $paymentGateway->addPaymentMethod($pm2);

        $pm3 = $manager->find('BBDurianBundle:PaymentMethod', 3);
        $paymentGateway->addPaymentMethod($pm3);

        $pm5 = $manager->find('BBDurianBundle:PaymentMethod', 5);
        $paymentGateway->addPaymentMethod($pm5);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentMethodData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayData'
        );
    }
}
