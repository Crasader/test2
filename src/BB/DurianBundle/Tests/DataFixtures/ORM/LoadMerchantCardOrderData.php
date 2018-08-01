<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\MerchantCardOrder;

class LoadMerchantCardOrderData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $mco1 = new MerchantCardOrder(1, 3);
        $manager->persist($mco1);

        $mco2 = new MerchantCardOrder(2, 4);
        $manager->persist($mco2);

        $mco3 = new MerchantCardOrder(3, 10);
        $manager->persist($mco3);

        $mco4 = new MerchantCardOrder(4, 1);
        $manager->persist($mco4);

        $mco5 = new MerchantCardOrder(5, 5);
        $manager->persist($mco5);

        // 給order_id重複測試用
        $mco6 = new MerchantCardOrder(6, 1);
        $manager->persist($mco6);

        $manager->flush();
    }
}
