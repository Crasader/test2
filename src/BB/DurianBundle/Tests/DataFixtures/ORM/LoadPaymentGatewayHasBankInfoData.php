<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class LoadPaymentGatewayHasBankInfoData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $paymentGateway = $manager->find('BBDurianBundle:PaymentGateway', 1);

        $bankInfo = $manager->find('BBDurianBundle:BankInfo', 1);
        $paymentGateway->addBankInfo($bankInfo);

        $bankInfo = $manager->find('BBDurianBundle:BankInfo', 2);
        $paymentGateway->addBankInfo($bankInfo);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankInfoData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayData'
        ];
    }
}
