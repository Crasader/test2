<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\MerchantKey;

class LoadMerchantKeyData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $merchant = $manager->find('BBDurianBundle:Merchant', 1);
        $merchantKey = new MerchantKey($merchant, 'public', 'testtest');
        $manager->persist($merchantKey);

        $merchant = $manager->find('BBDurianBundle:Merchant', 2);
        $merchantKey = new MerchantKey($merchant, 'private', str_repeat('1234', 1024));
        $manager->persist($merchantKey);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantData'
        );
    }
}
