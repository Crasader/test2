<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\MerchantLevelMethod;

class LoadMerchantLevelMethodData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        // 人民幣借記卡
        $method1 = $manager->find('BBDurianBundle:PaymentMethod', 1);

        // 信用卡支付
        $method2 = $manager->find('BBDurianBundle:PaymentMethod', 2);

        // 電話支付
        $method3 = $manager->find('BBDurianBundle:PaymentMethod', 3);

        $mlm = new MerchantLevelMethod(1, 1, $method1);
        $manager->persist($mlm);

        $mlm = new MerchantLevelMethod(1, 2, $method1);
        $manager->persist($mlm);

        $mlm = new MerchantLevelMethod(1, 3, $method1);
        $manager->persist($mlm);

        $mlm = new MerchantLevelMethod(1, 3, $method3);
        $manager->persist($mlm);

        $mlm = new MerchantLevelMethod(1, 4, $method1);
        $manager->persist($mlm);

        $mlm = new MerchantLevelMethod(2, 1, $method1);
        $manager->persist($mlm);

        $mlm = new MerchantLevelMethod(2, 2, $method1);
        $manager->persist($mlm);

        $mlm = new MerchantLevelMethod(2, 3, $method1);
        $manager->persist($mlm);

        $mlm = new MerchantLevelMethod(2, 1, $method2);
        $manager->persist($mlm);

        $mlm = new MerchantLevelMethod(2, 2, $method2);
        $manager->persist($mlm);

        $mlm = new MerchantLevelMethod(7, 8, $method1);
        $manager->persist($mlm);

        $mlm = new MerchantLevelMethod(7, 1, $method1);
        $manager->persist($mlm);

        $mlm = new MerchantLevelMethod(4, 5, $method1);
        $manager->persist($mlm);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentMethodData'
        ];
    }
}
