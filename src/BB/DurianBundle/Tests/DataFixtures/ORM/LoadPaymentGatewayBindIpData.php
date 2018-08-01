<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\PaymentGatewayBindIp;

class LoadPaymentGatewayBindIpData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $paymentGateway = $manager->find('BBDurianBundle:PaymentGateway', 67);

        $pgbi = new PaymentGatewayBindIp($paymentGateway, '123.123.123.123');
        $manager->persist($pgbi);

        $pgbi = new PaymentGatewayBindIp($paymentGateway, '123.123.123.125');
        $manager->persist($pgbi);

        $paymentGateway = $manager->find('BBDurianBundle:PaymentGateway', 2);
        $pgbi = new PaymentGatewayBindIp($paymentGateway, '1.2.3.4');
        $manager->persist($pgbi);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayData'
        ];
    }
}
