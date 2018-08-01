<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\DomainCurrency;

class LoadDomainCurrencyData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $domain = $manager->find('BBDurianBundle:User', 2);

        // CNY
        $domainCurrency1 = new DomainCurrency($domain, 156);
        $manager->persist($domainCurrency1);
        $domainCurrency1->presetOn();

        // HKD
        $domainCurrency2 = new DomainCurrency($domain, 344);
        $manager->persist($domainCurrency2);

        // TWD
        $domainCurrency3 = new DomainCurrency($domain, 901);
        $manager->persist($domainCurrency3);

        // USD
        $domainCurrency4 = new DomainCurrency($domain, 840);
        $manager->persist($domainCurrency4);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData'
        );
    }
}
