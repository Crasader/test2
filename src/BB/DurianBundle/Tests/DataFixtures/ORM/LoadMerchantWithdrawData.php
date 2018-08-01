<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\MerchantWithdraw;

class LoadMerchantWithdrawData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $paymentGateway = $manager->find('BBDurianBundle:PaymentGateway', 1);
        $paymentGateway2 = $manager->find('BBDurianBundle:PaymentGateway', 2);
        $paymentGateway68 = $manager->find('BBDurianBundle:PaymentGateway', 68);

        $privateKey = '1x2x3x4x5x';
        $shopUrl = 'http://ezshop.com/shop';
        $webUrl = 'http://ezshop.com';

        $merchantWithdraw1 = new MerchantWithdraw($paymentGateway, 'EZPAY', '1234567890', 1, 156);
        $merchantWithdraw1->setPrivateKey($privateKey);
        $merchantWithdraw1->setShopUrl($shopUrl);
        $merchantWithdraw1->setWebUrl($webUrl);
        $merchantWithdraw1->approve();
        $merchantWithdraw1->enable();
        $merchantWithdraw1->setMobile(true);
        $manager->persist($merchantWithdraw1);

        $merchantWithdraw2 = new MerchantWithdraw($paymentGateway, 'EZPAY2', 'EZPAY2', 2, 156);
        $merchantWithdraw2->setMobile(true);
        $manager->persist($merchantWithdraw2);

        $merchantWithdraw3 = new MerchantWithdraw($paymentGateway2, 'EZPAY4', '1234567890', 1, 156);
        $merchantWithdraw3->setMobile(true);
        $manager->persist($merchantWithdraw3);

        $merchantWithdraw4 = new MerchantWithdraw($paymentGateway, 'EZPAY2', 'EZPAY2', 2, 156);
        $merchantWithdraw4->setMobile(true);
        $manager->persist($merchantWithdraw4);

        $merchantWithdraw5 = new MerchantWithdraw($paymentGateway68, 'Neteller', 'Neteller', 2, 978);
        $merchantWithdraw5->enable();
        $merchantWithdraw5->approve();
        $merchantWithdraw5->setMobile(true);
        $manager->persist($merchantWithdraw5);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayData',
        ];
    }
}
