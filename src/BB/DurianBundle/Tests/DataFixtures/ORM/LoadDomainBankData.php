<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\DomainBank;

class LoadDomainBankData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        // tester
        $domain = $manager->find('BB\DurianBundle\Entity\User', 2);

        // 台灣銀行-CNY
        $bankCurrency = $manager->find('BB\DurianBundle\Entity\BankCurrency', 2);
        $domainBank   = new DomainBank($domain, $bankCurrency);
        $manager->persist($domainBank);

        // 台灣銀行-USD
        $bankCurrency = $manager->find('BB\DurianBundle\Entity\BankCurrency', 3);
        $domainBank   = new DomainBank($domain, $bankCurrency);
        $manager->persist($domainBank);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankCurrencyData',
        );
    }
}
