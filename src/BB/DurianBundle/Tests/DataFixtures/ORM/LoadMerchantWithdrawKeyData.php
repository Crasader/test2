<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\MerchantWithdrawKey;

class LoadMerchantWithdrawKeyData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $merchantWithdraw2 = $manager->find('BBDurianBundle:MerchantWithdraw', 2);
        $merchantWithdrawKey1 = new MerchantWithdrawKey($merchantWithdraw2, 'private', str_repeat('1234', 1024));
        $manager->persist($merchantWithdrawKey1);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantWithdrawData',
        ];
    }
}
