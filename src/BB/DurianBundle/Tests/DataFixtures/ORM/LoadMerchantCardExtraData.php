<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\MerchantCardExtra;

class LoadMerchantCardExtraData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $merchantCard1 = $manager->find('BBDurianBundle:MerchantCard', 1);

        $mcExtra1 = new MerchantCardExtra($merchantCard1, 'bankLimit', '-1');
        $manager->persist($mcExtra1);

        $merchantCard2 = $manager->find('BBDurianBundle:MerchantCard', 2);

        $mcExtra2 = new MerchantCardExtra($merchantCard2, 'overtime', '3');
        $manager->persist($mcExtra2);

        $mcExtra3 = new MerchantCardExtra($merchantCard2, 'gohometime', '10');
        $manager->persist($mcExtra3);

        $mcExtra4 = new MerchantCardExtra($merchantCard2, 'bankLimit', '5000');
        $manager->persist($mcExtra4);

        $merchantCard3 = $manager->find('BBDurianBundle:MerchantCard', 3);

        $mcExtra5 = new MerchantCardExtra($merchantCard3, 'bankLimit', '90');
        $manager->persist($mcExtra5);

        $merchantCard6 = $manager->find('BBDurianBundle:MerchantCard', 6);
        $mcExtra6 = new MerchantCardExtra($merchantCard6, 'bankLimit', '90');
        $manager->persist($mcExtra6);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantCardData'
        ];
    }
}
