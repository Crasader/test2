<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\LevelCurrency;

class LoadLevelCurrencyData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $level1 = $manager->find('BBDurianBundle:Level', 1);
        $level2 = $manager->find('BBDurianBundle:Level', 2);
        $level4 = $manager->find('BBDurianBundle:Level', 4);
        $level5 = $manager->find('BBDurianBundle:Level', 5);

        $paymentCharge3 = $manager->find('BBDurianBundle:PaymentCharge', 3);
        $paymentCharge5 = $manager->find('BBDurianBundle:PaymentCharge', 5);

        $levelCurrency1 = new LevelCurrency($level2, 156);
        $levelCurrency1->setPaymentCharge($paymentCharge3);
        $levelCurrency1->setUserCount(8);
        $manager->persist($levelCurrency1);

        $levelCurrency2 = new LevelCurrency($level2, 901);
        $levelCurrency2->setPaymentCharge($paymentCharge5);
        $levelCurrency2->setUserCount(4);
        $manager->persist($levelCurrency2);

        $levelCurrency3 = new LevelCurrency($level4, 156);
        $manager->persist($levelCurrency3);

        $levelCurrency4 = new LevelCurrency($level1, 901);
        $levelCurrency4->setUserCount(7);
        $manager->persist($levelCurrency4);

        $levelCurrency5 = new LevelCurrency($level5, 901);
        $levelCurrency5->setUserCount(10);
        $manager->persist($levelCurrency5);

        $levelCurrency6 = new LevelCurrency($level5, 156);
        $manager->persist($levelCurrency6);

        $levelCurrency7 = new LevelCurrency($level1, 156);
        $manager->persist($levelCurrency7);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentChargeData'
        ];
    }
}
