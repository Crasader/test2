<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\CashDepositEntry;

class LoadCashDepositEntryData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $cash = $manager->find('BBDurianBundle:Cash', 7);
        $merchant = $manager->find('BBDurianBundle:Merchant', 1);
        $paymentVendor = $manager->find('BBDurianBundle:PaymentVendor', 1);

        $data = array(
            'amount'            => 100,
            'offer'             => 10,
            'fee'               => -1,
            'payway_rate'       => 0.2,
            'rate'              => 0.2,
            'payway'            => CashDepositEntry::PAYWAY_CASH,
            'payway_currency'   => 156,
            'abandon_offer'     => false,
            'web_shop'          => true,
            'currency'          => 901,
            'level_id'          => 2,
            'telephone'         => '123456789',
            'postcode'          => 400,
            'address'           => '地球',
            'email'             => 'earth@gmail.com'
        );

        $entry = new CashDepositEntry($cash, $merchant, $paymentVendor, $data);
        $entry->setId(201304280000000001);
        $entry->setAt('20130428120000');

        $manager->persist($entry);

        $data = array(
            'amount'            => 1000,
            'offer'             => 20,
            'fee'               => -2,
            'payway_rate'       => 0.2,
            'rate'              => 0.2,
            'payway'            => CashDepositEntry::PAYWAY_CASH,
            'payway_currency'   => 156,
            'abandon_offer'     => false,
            'web_shop'          => true,
            'currency'          => 901,
            'level_id'          => 2,
            'telephone'         => '789',
            'postcode'          => 401,
            'address'           => '宇宙',
            'email'             => 'universe@gmail.com'
        );

        $entry = new CashDepositEntry($cash, $merchant, $paymentVendor, $data);
        $entry->setId(201305280000000001);
        $entry->setAt('20130528120000');
        $entry->setManual(true);

        $manager->persist($entry);
        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentVendorData'
        ];
    }
}
