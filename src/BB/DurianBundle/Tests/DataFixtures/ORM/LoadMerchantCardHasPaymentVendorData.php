<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class LoadMerchantCardHasPaymentVendorData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $pv1 = $manager->find('BBDurianBundle:PaymentVendor', 1);
        $pv4 = $manager->find('BBDurianBundle:PaymentVendor', 4);

        $merchantCard1 = $manager->find('BBDurianBundle:MerchantCard', 1);
        $merchantCard1->addPaymentVendor($pv1);
        $merchantCard1->addPaymentVendor($pv4);

        $merchantCard3 = $manager->find('BBDurianBundle:MerchantCard', 3);
        $merchantCard3->addPaymentVendor($pv1);

        $merchantCard6 = $manager->find('BBDurianBundle:MerchantCard', 6);
        $merchantCard6->addPaymentVendor($pv1);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantCardData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentVendorData'
        ];
    }
}
