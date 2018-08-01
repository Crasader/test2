<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\CardDepositEntry;

class LoadCardDepositEntryData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $card = $manager->find('BBDurianBundle:Card', 1);
        $merchantCard = $manager->find('BBDurianBundle:MerchantCard', 3);
        $vendor = $manager->find('BBDurianBundle:PaymentVendor', 1);

        $data1 = [
            'amount' => 100,
            'fee' => -5,
            'web_shop' => false,
            'currency' => 156,
            'rate' => 1,
            'payway_currency' => 156,
            'payway_rate' => 0.05,
            'postcode' => 123,
            'telephone' => '8825252',
            'address' => '地球',
            'email' => 'earth@gmail.com',
            'feeConvBasic' => -5,
            'amountConvBasic' => 100,
            'feeConv' => -5,
            'amountConv' => 100
        ];

        $entry1 = new CardDepositEntry($card, $merchantCard, $vendor, $data1);
        $entry1->setId(201502010000000001);
        $entry1->setAt('20150201120000');
        $manager->persist($entry1);


        $data2 = [
            'amount' => 100,
            'fee' => -5,
            'web_shop' => false,
            'currency' => 156,
            'rate' => 1,
            'payway_currency' => 156,
            'payway_rate' => 0.05,
            'postcode' => 123,
            'telephone' => '8825252',
            'address' => '地球',
            'email' => 'earth@gmail.com',
            'feeConvBasic' => -5,
            'amountConvBasic' => 100,
            'feeConv' => -100,
            'amountConv' => 2000
        ];

        $entry2 = new CardDepositEntry($card, $merchantCard, $vendor, $data2);
        $entry2->setId(201502010000000002);
        $entry2->setAt('20150201150000');
        $manager->persist($entry2);


        $data3 = [
            'amount' => 1000,
            'fee' => -5,
            'web_shop' => true,
            'currency' => 156,
            'rate' => 1,
            'payway_currency' => 156,
            'payway_rate' => 0.05,
            'postcode' => 789,
            'telephone' => '3345678',
            'address' => '火星',
            'email' => 'mars@gmail.com',
            'feeConvBasic' => -5,
            'amountConvBasic' => 1000,
            'feeConv' => -5,
            'amountConv' => 1000
        ];

        $entry3 = new CardDepositEntry($card, $merchantCard, $vendor, $data3);
        $entry3->setId(201501050000000001);
        $entry3->setAt('20150105100000');
        $entry3->confirm();
        $entry3->setManual(true);
        $manager->persist($entry3);

        $card->setBalance(1000);


        $merchantCard6 = $manager->find('BBDurianBundle:MerchantCard', 6);
        $data4 = [
            'amount' => 1000,
            'fee' => -1,
            'web_shop' => true,
            'currency' => 156,
            'rate' => 1,
            'payway_currency' => 156,
            'payway_rate' => 0.05,
            'postcode' => 20800,
            'telephone' => '23325252',
            'address' => '海洋',
            'email' => 'ocean@gmail.com',
            'feeConvBasic' => -1,
            'amountConvBasic' => 1000,
            'feeConv' => -1,
            'amountConv' => 1000
        ];

        $entry4 = new CardDepositEntry($card, $merchantCard6, $vendor, $data4);
        $entry4->setId(201501080000000001);
        $entry4->setAt(20150108120000);
        $manager->persist($entry4);


        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCardData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantCardData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentVendorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayData'
        ];
    }
}
