<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\MerchantCardKey;

class LoadMerchantCardKeyData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $merchantCard1 = $manager->find('BBDurianBundle:MerchantCard', 1);
        $mcKey1 = new MerchantCardKey($merchantCard1, 'public', 'testtest');
        $manager->persist($mcKey1);

        $merchantCard2 = $manager->find('BBDurianBundle:MerchantCard', 2);
        $mcKey2 = new MerchantCardKey($merchantCard2, 'private', str_repeat('1234', 1024));
        $manager->persist($mcKey2);

        $merchantCard4 = $manager->find('BBDurianBundle:MerchantCard', 4);
        $mcKey3 = new MerchantCardKey($merchantCard4, 'public', 'testtest');
        $manager->persist($mcKey3);

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
