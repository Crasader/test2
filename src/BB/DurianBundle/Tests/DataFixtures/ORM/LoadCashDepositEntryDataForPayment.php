<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\Merchant;
use BB\DurianBundle\Entity\CashDepositEntry;
use BB\DurianBundle\Entity\MerchantKey;
use BB\DurianBundle\Entity\MerchantExtra;

class LoadCashDepositEntryDataForPayment extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $payway = CashDepositEntry::PAYWAY_CASH;
        $baofooGateway = $manager->find('BBDurianBundle:PaymentGateway', 67);
        $baofooMerchant = new Merchant($baofooGateway, $payway, 'baofooII_1', '9855667', 1, 901);
        $baofooMerchant->setPrivateKey('1x2x3x4x5x');
        $baofooMerchant->setShopUrl('http://ezshop.com/shop');
        $baofooMerchant->setWebUrl('http://ezshop.com');
        $baofooMerchant->enable();
        $manager->persist($baofooMerchant);
        $manager->flush();

        $merchantExtra = new MerchantExtra($baofooMerchant, 'seller_email', 'baofoo@mail.cn');
        $manager->persist($merchantExtra);

        $publicKey = new MerchantKey($baofooMerchant, 'public', 'testtest');
        $manager->persist($publicKey);
        $privateKey = new MerchantKey($baofooMerchant, 'private', str_repeat('1234', 1024));
        $manager->persist($privateKey);

        $cash = $manager->find('BBDurianBundle:Cash', 7);
        $paymentVendor = $manager->find('BBDurianBundle:PaymentVendor', 1);
        $data = [
            'amount' => 1000,
            'offer' => 10,
            'fee' => -1,
            'payway_rate' => 0.2,
            'rate' => 0.2,
            'payway' => $payway,
            'payway_currency' => 156,
            'abandon_offer' => false,
            'web_shop' => true,
            'currency' => 156,
            'level_id' => 2,
            'telephone' => '23325252',
            'postcode' => 20800,
            'address' => '海洋',
            'email' => 'ocean@gmail.com'
        ];

        $entry = new CashDepositEntry($cash, $baofooMerchant, $paymentVendor, $data);
        $entry->setId(201401280000000001);
        $entry->setAt(20140128120000);
        $manager->persist($entry);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentVendorData',
        );
    }
}
