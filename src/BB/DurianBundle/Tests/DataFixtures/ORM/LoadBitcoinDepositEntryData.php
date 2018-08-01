<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\BitcoinDepositEntry;

class LoadBitcoinDepositEntryData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $data1 = [
            'id' => 201801110000000001,
            'bitcoin_wallet_id' => 4,
            'bitcoin_address_id' => 1,
            'bitcoin_address' => 'address2',
            'user_id' => 2,
            'domain' => 2,
            'level_id' => 2,
            'currency' => 901,
            'payway_currency' => 901,
            'amount' => 100,
            'bitcoin_amount' => 12.469135,
            'bitcoin_rate' => 0.12345678,
            'rate_difference' => 0.00123457,
            'rate' => 0.223,
            'payway_rate' => 0.223,
        ];

        $entry1 = new BitcoinDepositEntry($data1);
        $entry1->confirm();
        $entry1->setOperator('test');
        $entry1->setAmountEntryId(2);
        $manager->persist($entry1);

        $data2 = [
            'id' => 201801110000000002,
            'bitcoin_wallet_id' => 4,
            'bitcoin_address_id' => 6,
            'bitcoin_address' => 'address8',
            'user_id' => 8,
            'domain' => 2,
            'level_id' => 2,
            'currency' => 156,
            'payway_currency' => 901,
            'amount' => 1000,
            'bitcoin_amount' => 236.91357,
            'bitcoin_rate' => 0.23456789,
            'rate_difference' => 0.00234568,
            'rate' => 1,
            'payway_rate' => 0.223,
        ];

        $entry2 = new BitcoinDepositEntry($data2);
        $manager->persist($entry2);

        $data3 = [
            'id' => 201801120000000003,
            'bitcoin_wallet_id' => 4,
            'bitcoin_address_id' => 6,
            'bitcoin_address' => 'address8',
            'user_id' => 8,
            'domain' => 2,
            'level_id' => 2,
            'currency' => 840,
            'payway_currency' => 840,
            'amount' => 500,
            'bitcoin_amount' => 68.54096,
            'bitcoin_rate' => 0.13572468,
            'rate_difference' => 0.00135724,
            'rate' => 6.34000000,
            'payway_rate' => 6.34000000,
        ];

        $entry3 = new BitcoinDepositEntry($data3);
        $entry3->cancel();
        $entry3->setOperator('operatorTest');
        $entry3->control();
        $manager->persist($entry3);

        $manager->flush();
    }
}
