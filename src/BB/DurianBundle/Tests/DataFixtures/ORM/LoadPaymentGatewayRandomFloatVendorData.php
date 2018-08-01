<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\PaymentGatewayRandomFloatVendor;

class LoadPaymentGatewayRandomFloatVendorData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $paymentGatewayRandomFloatVendor1 = new PaymentGatewayRandomFloatVendor(1, 1);
        $manager->persist($paymentGatewayRandomFloatVendor1);

        $paymentGatewayRandomFloatVendor2 = new PaymentGatewayRandomFloatVendor(1, 5);
        $manager->persist($paymentGatewayRandomFloatVendor2);

        $manager->flush();
    }
}
