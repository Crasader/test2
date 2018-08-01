<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\MerchantExtra;

class LoadMerchantExtraData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $merchant = $manager->find('BBDurianBundle:Merchant', 1);

        $merchantExtra = new MerchantExtra($merchant, 'bankLimit', '-1');
        $manager->persist($merchantExtra);

        $merchant = $manager->find('BBDurianBundle:Merchant', 2);

        $merchantExtra = new MerchantExtra($merchant, 'overtime', '3');
        $manager->persist($merchantExtra);

        $merchantExtra = new MerchantExtra($merchant, 'gohometime', '10');
        $manager->persist($merchantExtra);

        $merchantExtra = new MerchantExtra($merchant, 'bankLimit', '5000');
        $manager->persist($merchantExtra);

        $merchant = $manager->find('BBDurianBundle:Merchant', 3);

        $merchantExtra = new MerchantExtra($merchant, 'bankLimit', '-1');
        $manager->persist($merchantExtra);

        $merchant7 = $manager->find('BBDurianBundle:Merchant', 7);

        $merchantExtra = new MerchantExtra($merchant7, 'bundleID', 'testbundleID');
        $manager->persist($merchantExtra);

        $merchantExtra = new MerchantExtra($merchant7, 'applyID', 'testapplyID');
        $manager->persist($merchantExtra);

        $merchant = $manager->find('BBDurianBundle:Merchant', 5);

        $merchantExtra = new MerchantExtra($merchant, 'verifyKey', 'testVerifyKey');
        $manager->persist($merchantExtra);

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
