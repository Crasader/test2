<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\MerchantIpStrategy;

class LoadMerchantIpStrategyData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $merchant = $manager->find('BB\DurianBundle\Entity\Merchant', 1);

        $strategyCity = new MerchantIpStrategy($merchant, 2, 3, 3);
        $manager->persist($strategyCity);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return ['BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantData'];
    }
}
