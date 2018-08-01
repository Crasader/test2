<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\MerchantWithdrawIpStrategy;

class LoadMerchantWithdrawIpStrategyData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $merchantWithdraw = $manager->find('BBDurianBundle:MerchantWithdraw', 2);

        $strategyCity = new MerchantWithdrawIpStrategy($merchantWithdraw, 2, 3, 3);
        $manager->persist($strategyCity);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return ['BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantWithdrawData'];
    }
}
