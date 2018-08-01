<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\MerchantCard;

class LoadMerchantCardData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $pg = $manager->find('BBDurianBundle:PaymentGateway', 1);
        $pg2 = $manager->find('BBDurianBundle:PaymentGateway', 2);
        $pg67 = $manager->find('BBDurianBundle:PaymentGateway', 67);

        $privateKey = '1x2x3x4x5x';
        $shopUrl = 'http://ezshop.com/shop';
        $webUrl = 'http://ezshop.com';

        $merchantCard1 = new MerchantCard($pg, 'EZPAY', '5566001', 2, 156);
        $merchantCard1->setPrivateKey($privateKey);
        $merchantCard1->setShopUrl($shopUrl);
        $merchantCard1->setWebUrl($webUrl);
        $manager->persist($merchantCard1);

        $merchantCard2 = new MerchantCard($pg, 'EZPAY1', '5566002', 2, 156);
        $merchantCard2->approve();
        $manager->persist($merchantCard2);

        $merchantCard3 = new MerchantCard($pg, 'EZPAY3', '5566003', 2, 156);
        $merchantCard3->approve();
        $merchantCard3->enable();
        $manager->persist($merchantCard3);

        $merchantCard4 = new MerchantCard($pg2, 'EZPAY3', '5566003', 2, 156);
        $merchantCard4->approve();
        $manager->persist($merchantCard4);

        $merchantCard5 = new MerchantCard($pg2, 'EZPAY4', '5566004', 2, 840);
        $merchantCard5->approve();
        $manager->persist($merchantCard5);

        $merchantCard6 = new MerchantCard($pg67, 'baofooII_1', '9855667', 2, 156);
        $merchantCard6->setPrivateKey('1x2x3x4x5x');
        $merchantCard6->setShopUrl($shopUrl);
        $merchantCard6->setWebUrl($webUrl);
        $merchantCard6->approve();
        $merchantCard6->enable();
        $manager->persist($merchantCard6);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayData'
        ];
    }
}
