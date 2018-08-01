<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\MerchantLevel;

class LoadMerchantLevelData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $ml1 = new MerchantLevel(1, 1, 1);
        $manager->persist($ml1);

        $ml2 = new MerchantLevel(1, 2, 1);
        $manager->persist($ml2);

        $ml3 = new MerchantLevel(1, 3, 1);
        $manager->persist($ml3);

        $ml4 = new MerchantLevel(1, 4, 1);
        $manager->persist($ml4);

        $ml5 = new MerchantLevel(2, 3, 2);
        $manager->persist($ml5);

        $ml6 = new MerchantLevel(3, 4, 2);
        $manager->persist($ml6);

        $ml7 = new MerchantLevel(6, 1, 2);
        $manager->persist($ml7);

        $ml8 = new MerchantLevel(6, 2, 1);
        $manager->persist($ml8);

        $ml9 = new MerchantLevel(4, 5, 1);
        $manager->persist($ml9);

        $ml10 = new MerchantLevel(7, 1, 5);
        $manager->persist($ml10);

        $manager->flush();
    }
}
