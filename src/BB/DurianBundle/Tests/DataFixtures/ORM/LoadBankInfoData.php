<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\BankInfo;

class LoadBankInfoData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $bankInfo = new BankInfo('中國銀行');
        $manager->persist($bankInfo);

        $bankInfo = new BankInfo('台灣銀行');
        $manager->persist($bankInfo);

        $bankInfo = new BankInfo('美國銀行');
        $bankInfo->setBankUrl('https://www.bankofamerica.com/');
        $manager->persist($bankInfo);

        $bankInfo = new BankInfo('日本銀行');
        $bankInfo->disable();
        $manager->persist($bankInfo);

        $manager->flush();

        // 自動出款銀行
        $sql = 'INSERT INTO bank_info (id, bankname, abbr, bank_url, virtual, withdraw, enable, auto_withdraw)' .
            ' VALUES (?, ?, ?, ?, ?, ?, ?, ?)';

        $params = [
            292,
            'Neteller',
            '',
            '',
            false,
            false,
            true,
            true,
        ];

        $manager->getConnection()->executeUpdate($sql, $params);
    }
}
