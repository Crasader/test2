<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\BankCurrency;

class LoadBankCurrencyData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        // 台灣銀行
        $bankInfo = $manager->find('BBDurianBundle:BankInfo', 2);

        $bankCurrency = new BankCurrency($bankInfo, 901); // TWD
        $manager->persist($bankCurrency);

        $bankCurrency = new BankCurrency($bankInfo, 156); // CNY
        $manager->persist($bankCurrency);

        $bankCurrency = new BankCurrency($bankInfo, 840); // USD
        $manager->persist($bankCurrency);

        // Neteller
        $bankInfo = $manager->find('BBDurianBundle:BankInfo', 292);
        $bankCurrency = new BankCurrency($bankInfo, 978); // EUR
        $manager->persist($bankCurrency);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankInfoData'
        );
    }
}
