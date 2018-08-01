<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\Merchant;
use BB\DurianBundle\Entity\CashDepositEntry;

class LoadMerchantData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $paymentGateway = $manager->find('BBDurianBundle:PaymentGateway', 1);
        $paymentGateway2 = $manager->find('BBDurianBundle:PaymentGateway', 2);
        $paywayCash = CashDepositEntry::PAYWAY_CASH;

        $privateKey = '1x2x3x4x5x';
        $shopUrl    = 'http://ezshop.com/shop';
        $webUrl     = 'http://ezshop.com';

        $merchant = new Merchant($paymentGateway, $paywayCash, 'EZPAY', '1234567890', 1, 156);
        $merchant->setPrivateKey($privateKey);
        $merchant->setShopUrl($shopUrl);
        $merchant->setWebUrl($webUrl);
        $merchant->enable();
        $manager->persist($merchant);

        $merchant = new Merchant($paymentGateway, $paywayCash, 'EZPAY2', 'EZPAY2', 2, 156);
        $merchant->setAmountLimit(15);
        $manager->persist($merchant);

        $merchant = new Merchant($paymentGateway, $paywayCash, 'EZPAY3', 'EZPAY3', 1, 156);
        $merchant->setAmountLimit(50);
        $manager->persist($merchant);

        $merchant = new Merchant($paymentGateway2, $paywayCash, 'EZPAY4', '1234567890', 1, 156);
        $manager->persist($merchant);

        $paymentGateway3 = $manager->find('BBDurianBundle:PaymentGateway', 77);
        $merchant5 = new Merchant($paymentGateway3, $paywayCash, 'CCPAY', '1111166666', 1, 156);
        $merchant5->setId(77);
        $merchant5->approve();
        $manager->persist($merchant5);

        $paymentGateway4 = $manager->find('BBDurianBundle:PaymentGateway', 68);
        $merchant6 = new Merchant($paymentGateway4, $paywayCash, 'Neteller', 'Neteller', 2, 978);
        $merchant6->enable();
        $manager->persist($merchant6);

        $paymentGateway5 = $manager->find('BBDurianBundle:PaymentGateway', 92);
        $merchant7 = new Merchant($paymentGateway5, $paywayCash, 'WeiXin', '987654321', 6, 156);
        $merchant7->enable();
        $manager->persist($merchant7);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayData'
        );
    }
}
