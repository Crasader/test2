<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class LoadMerchantCardHasPaymentMethodData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $pm1 = $manager->find('BBDurianBundle:PaymentMethod', 1);
        $pm2 = $manager->find('BBDurianBundle:PaymentMethod', 2);
        $pm3 = $manager->find('BBDurianBundle:PaymentMethod', 3);

        $merchantCard1 = $manager->find('BBDurianBundle:MerchantCard', 1);
        $merchantCard1->addPaymentMethod($pm1);
        $merchantCard1->addPaymentMethod($pm2);
        $merchantCard1->addPaymentMethod($pm3);

        $merchantCard3 = $manager->find('BBDurianBundle:MerchantCard', 3);
        $merchantCard3->addPaymentMethod($pm1);

        $merchantCard6 = $manager->find('BBDurianBundle:MerchantCard', 6);
        $merchantCard6->addPaymentMethod($pm1);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantCardData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentMethodData'
        ];
    }
}
