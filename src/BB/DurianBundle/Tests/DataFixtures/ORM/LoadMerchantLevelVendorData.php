<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\MerchantLevelVendor;

class LoadMerchantLevelVendorData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        // 中國銀行
        $vendor1 = $manager->find('BBDurianBundle:PaymentVendor', 1);

        // 移動儲值卡
        $vendor2 = $manager->find('BBDurianBundle:PaymentVendor', 2);

        // 聯通儲值卡
        $vendor3 = $manager->find('BBDurianBundle:PaymentVendor', 3);

        // Neteller
        $vendor292 = $manager->find('BBDurianBundle:PaymentVendor', 292);

        // merchant = 1，paymentMethod = 1
        $criteria = [
            'merchantId' => 1,
            'paymentMethod' => 1
        ];
        $m1ms = $manager->getRepository('BBDurianBundle:MerchantLevelMethod')
            ->findBy($criteria);

        foreach ($m1ms as $m1m) {
            $mlv = new MerchantLevelVendor($m1m->getMerchantId(), $m1m->getLevelId(), $vendor1);
            $manager->persist($mlv);
        }

        // merchant: 2，levelId: 1
        $mlv = new MerchantLevelVendor(2, 1, $vendor1);
        $manager->persist($mlv);

        $mlv = new MerchantLevelVendor(2, 1, $vendor2);
        $manager->persist($mlv);

        $mlv = new MerchantLevelVendor(2, 1, $vendor3);
        $manager->persist($mlv);

        // merchant: 2，levelId: 2
        $mlv = new MerchantLevelVendor(2, 2, $vendor2);
        $manager->persist($mlv);

        // merchant: 6，levelId: 2，Neteller
        $mlv = new MerchantLevelVendor(6, 2, $vendor292);
        $manager->persist($mlv);

        // merchant: 4，levelId: 1
        $mlv = new MerchantLevelVendor(4, 5, $vendor1);
        $manager->persist($mlv);

        // merchant: 7，levelId: 1
        $mlv = new MerchantLevelVendor(7, 1, $vendor1);
        $manager->persist($mlv);

        // merchant: 7，levelId: 2
        $mlv = new MerchantLevelVendor(7, 2, $vendor2);
        $manager->persist($mlv);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantLevelMethodData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentVendorData'
        ];
    }
}
