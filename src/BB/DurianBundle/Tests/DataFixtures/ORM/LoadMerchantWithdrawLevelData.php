<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\MerchantWithdrawLevel;

class LoadMerchantWithdrawLevelData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $mwl1 = new MerchantWithdrawLevel(1, 1, 1);
        $manager->persist($mwl1);

        $mwl2 = new MerchantWithdrawLevel(2, 1, 2);
        $manager->persist($mwl2);

        $mwl3 = new MerchantWithdrawLevel(2, 2, 1);
        $manager->persist($mwl3);

        $mwl4 = new MerchantWithdrawLevel(5, 2, 1);
        $manager->persist($mwl4);

        $manager->flush();
    }
}
