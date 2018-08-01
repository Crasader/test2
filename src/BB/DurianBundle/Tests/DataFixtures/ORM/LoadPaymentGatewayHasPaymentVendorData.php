<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class LoadPaymentGatewayHasPaymentVendorData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $paymentGateway = $manager->find('BBDurianBundle:PaymentGateway', 1);

        $pv1 = $manager->find('BBDurianBundle:PaymentVendor', 1);
        $paymentGateway->addPaymentVendor($pv1);

        $pv2 = $manager->find('BBDurianBundle:PaymentVendor', 2);
        $paymentGateway->addPaymentVendor($pv2);

        $pv4 = $manager->find('BBDurianBundle:PaymentVendor', 4);
        $paymentGateway->addPaymentVendor($pv4);

        $pv5 = $manager->find('BBDurianBundle:PaymentVendor', 5);
        $paymentGateway->addPaymentVendor($pv5);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentVendorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayData'
        );
    }
}
