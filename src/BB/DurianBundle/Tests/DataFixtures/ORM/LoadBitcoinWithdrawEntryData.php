<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\BitcoinWithdrawEntry;

class LoadBitcoinWithdrawEntryData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $data1 = [
            'id' => 201712120000000001,
            'user_id' => 6,
            'domain' => 2,
            'level_id' => 1,
            'currency' => 156,
            'amount' => -100,
            'bitcoin_amount' => 12.222221,
            'bitcoin_rate' => 0.12345678,
            'rate_difference' => 0.00123457,
            'deduction' => 0,
            'audit_charge' => 0,
            'audit_fee' => 0,
            'rate' => 1,
            'ip' => '127.0.0.1',
            'withdraw_address' => 'address1',
            'note' => '',
        ];

        $entry1 = new BitcoinWithdrawEntry($data1);
        $entry1->first();
        $entry1->cancel();
        $entry1->setOperator('operatorTest1');
        $entry1->setAmountEntryId(1);
        $manager->persist($entry1);

        $data2 = [
            'id' => 201712120000000002,
            'user_id' => 6,
            'domain' => 2,
            'level_id' => 1,
            'currency' => 156,
            'amount' => -200,
            'bitcoin_amount' => 24.444442,
            'bitcoin_rate' => 0.12345678,
            'rate_difference' => 0.00123457,
            'deduction' => 0,
            'audit_charge' => 0,
            'audit_fee' => 0,
            'rate' => 1,
            'ip' => '127.0.0.1',
            'withdraw_address' => 'address2',
            'note' => '',
        ];
        $entry2 = new BitcoinWithdrawEntry($data2);
        $entry2->setPreviousId(201712120000000001);
        $entry2->detailModified();
        $entry2->confirm();
        $entry2->manual();
        $entry2->setOperator('operatorTest2');
        $entry2->control();
        $entry2->setAmountEntryId(2);
        $manager->persist($entry2);

        $data3 = [
            'id' => 201712120000000003,
            'user_id' => 6,
            'domain' => 2,
            'level_id' => 1,
            'currency' => 156,
            'amount' => -300,
            'bitcoin_amount' => 36.666663,
            'bitcoin_rate' => 0.12345678,
            'rate_difference' => 0.00123457,
            'deduction' => 0,
            'audit_charge' => 0,
            'audit_fee' => 0,
            'rate' => 1,
            'ip' => '127.0.0.1',
            'withdraw_address' => 'address3',
            'note' => '',
        ];
        $entry3 = new BitcoinWithdrawEntry($data3);
        $entry3->setPreviousId(201712120000000002);
        $entry3->detailModified();
        $entry3->locked();
        $entry3->setOperator('operatorTest1');
        $entry3->setAmountEntryId(3);
        $manager->persist($entry3);

        $data4 = [
            'id' => 201712120000000004,
            'user_id' => 6,
            'domain' => 2,
            'level_id' => 1,
            'currency' => 156,
            'amount' => -400,
            'bitcoin_amount' => 48.888884,
            'bitcoin_rate' => 0.12345678,
            'rate_difference' => 0.00123457,
            'deduction' => 0,
            'audit_charge' => 0,
            'audit_fee' => 0,
            'rate' => 1,
            'ip' => '127.0.0.1',
            'withdraw_address' => 'address3',
            'note' => '',
        ];
        $entry4 = new BitcoinWithdrawEntry($data4);
        $entry4->setPreviousId(201712120000000003);
        $entry4->setAmountEntryId(4);
        $manager->persist($entry4);

        $data5 = [
            'id' => 201712120000000005,
            'user_id' => 7,
            'domain' => 2,
            'level_id' => 2,
            'currency' => 840,
            'amount' => -100,
            'bitcoin_amount' => 23.222221,
            'bitcoin_rate' => 0.23456789,
            'rate_difference' => 0.00234568,
            'deduction' => 0,
            'audit_charge' => 0,
            'audit_fee' => 0,
            'rate' => 6.34000000,
            'ip' => '127.0.0.1',
            'withdraw_address' => 'address5',
            'note' => '',
        ];
        $entry5 = new BitcoinWithdrawEntry($data5);
        $entry5->first();
        $entry5->setAmountEntryId(5);
        $manager->persist($entry5);

        $data6 = [
            'id' => 201712120000000006,
            'user_id' => 7,
            'domain' => 2,
            'level_id' => 2,
            'currency' => 840,
            'amount' => -200,
            'bitcoin_amount' => 46.444442,
            'bitcoin_rate' => 0.23456789,
            'rate_difference' => 0.00234568,
            'deduction' => 0,
            'audit_charge' => 0,
            'audit_fee' => 0,
            'rate' => 6.34000000,
            'ip' => '127.0.0.1',
            'withdraw_address' => 'address6',
            'note' => '',
        ];
        $entry6 = new BitcoinWithdrawEntry($data6);
        $entry6->setPreviousId(201712120000000005);
        $entry6->detailModified();
        $entry6->setAmountEntryId(6);
        $manager->persist($entry6);

        $data7 = [
            'id' => 201712120000000007,
            'user_id' => 7,
            'domain' => 2,
            'level_id' => 2,
            'currency' => 840,
            'amount' => -300,
            'bitcoin_amount' => 69.666663,
            'bitcoin_rate' => 0.23456789,
            'rate_difference' => 0.00234568,
            'deduction' => 0,
            'audit_charge' => 0,
            'audit_fee' => 0,
            'rate' => 6.34000000,
            'ip' => '127.0.0.1',
            'withdraw_address' => 'address7',
            'note' => '',
        ];
        $entry7 = new BitcoinWithdrawEntry($data7);
        $entry7->setPreviousId(201712120000000006);
        $entry7->detailModified();
        $entry7->setAmountEntryId(7);
        $manager->persist($entry7);

        $data8 = [
            'id' => 201712120000000008,
            'user_id' => 7,
            'domain' => 2,
            'level_id' => 2,
            'currency' => 840,
            'amount' => -400,
            'bitcoin_amount' => 92.888884,
            'bitcoin_rate' => 0.23456789,
            'rate_difference' => 0.00234568,
            'deduction' => 0,
            'audit_charge' => 0,
            'audit_fee' => 0,
            'rate' => 6.34000000,
            'ip' => '127.0.0.1',
            'withdraw_address' => 'address8',
            'note' => '',
        ];
        $entry8 = new BitcoinWithdrawEntry($data8);
        $entry8->setPreviousId(201712120000000007);
        $entry8->detailModified();
        $entry8->setAmountEntryId(8);
        $manager->persist($entry8);

        $data9 = [
            'id' => 201712120000000009,
            'user_id' => 8,
            'domain' => 2,
            'level_id' => 2,
            'currency' => 840,
            'amount' => -100,
            'bitcoin_amount' => 23.222221,
            'bitcoin_rate' => 0.23456789,
            'rate_difference' => 0.00234568,
            'deduction' => 0,
            'audit_charge' => 0,
            'audit_fee' => 0,
            'rate' => 6.34000000,
            'ip' => '127.0.0.1',
            'withdraw_address' => 'address9',
            'note' => '',
        ];
        $entry9 = new BitcoinWithdrawEntry($data9);
        $entry9->first();
        $entry9->confirm();
        $entry9->setOperator('operatorTest2');
        $entry9->control();
        $entry9->setRefId('refId1');
        $entry9->setAmountEntryId(9);
        $manager->persist($entry9);

        $data10 = [
            'id' => 201712120000000010,
            'user_id' => 5,
            'domain' => 2,
            'level_id' => 1,
            'currency' => 840,
            'amount' => -100,
            'bitcoin_amount' => 23.222221,
            'bitcoin_rate' => 0.23456789,
            'rate_difference' => 0.00234568,
            'deduction' => 0,
            'audit_charge' => 0,
            'audit_fee' => 0,
            'rate' => 6.34000000,
            'ip' => '127.0.0.1',
            'withdraw_address' => 'address10',
            'note' => '',
        ];
        $entry10 = new BitcoinWithdrawEntry($data10);
        $entry10->first();
        $entry10->locked();
        $entry10->setAmountEntryId(10);
        $manager->persist($entry10);

        $manager->flush();
    }
}
