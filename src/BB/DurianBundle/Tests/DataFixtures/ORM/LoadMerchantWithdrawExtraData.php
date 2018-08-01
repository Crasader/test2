<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\MerchantWithdrawExtra;

class LoadMerchantWithdrawExtraData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $merchantWithdraw1 = $manager->find('BBDurianBundle:MerchantWithdraw', 1);
        $merchantWithdraw2 = $manager->find('BBDurianBundle:MerchantWithdraw', 2);
        $merchantWithdraw3 = $manager->find('BBDurianBundle:MerchantWithdraw', 3);

        $merchantWithdrawExtra1 = new MerchantWithdrawExtra($merchantWithdraw1, 'bankLimit', '-1');
        $manager->persist($merchantWithdrawExtra1);

        $merchantWithdrawExtra2 = new MerchantWithdrawExtra($merchantWithdraw2, 'overtime', '3');
        $manager->persist($merchantWithdrawExtra2);

        $merchantWithdrawExtra3 = new MerchantWithdrawExtra($merchantWithdraw2, 'gohometime', '10');
        $manager->persist($merchantWithdrawExtra3);

        $merchantWithdrawExtra4 = new MerchantWithdrawExtra($merchantWithdraw2, 'bankLimit', '5000');
        $manager->persist($merchantWithdrawExtra4);

        $merchantWithdrawExtra5 = new MerchantWithdrawExtra($merchantWithdraw3, 'bankLimit', '-1');
        $manager->persist($merchantWithdrawExtra5);

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
