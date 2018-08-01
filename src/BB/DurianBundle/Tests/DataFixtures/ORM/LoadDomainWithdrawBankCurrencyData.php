<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\DomainWithdrawBankCurrency;

class LoadDomainWithdrawBankCurrencyData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        // tester
        $domain = $manager->find('BBDurianBundle:User', 2);

        // 台灣銀行-TWD
        $bankCurrency1 = $manager->find('BBDurianBundle:BankCurrency', 1);
        $domainWithdrawBankCurrency1 = new DomainWithdrawBankCurrency($domain, $bankCurrency1);
        $manager->persist($domainWithdrawBankCurrency1);

        // 台灣銀行-CNY
        $bankCurrency2 = $manager->find('BBDurianBundle:BankCurrency', 2);
        $domainWithdrawBankCurrency2 = new DomainWithdrawBankCurrency($domain, $bankCurrency2);
        $manager->persist($domainWithdrawBankCurrency2);

        // Neteller-EUR
        $bankCurrency3 = $manager->find('BBDurianBundle:BankCurrency', 4);
        $domainWithdrawBankCurrency3 = new DomainWithdrawBankCurrency($domain, $bankCurrency3);
        $manager->persist($domainWithdrawBankCurrency3);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankCurrencyData',
        ];
    }
}
