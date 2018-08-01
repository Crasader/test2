<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\DomainConfig;

class LoadDomainConfigDataForCustomizeController extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $domain = $manager->find('BBDurianBundle:User', 75);
        $config = new DomainConfig($domain, 'domain75', 'com');
        $manager->persist($config);

        $domain = $manager->find('BBDurianBundle:User', 84);
        $config = new DomainConfig($domain, 'domain84', 'vt');
        $manager->persist($config);

        $domain = $manager->find('BBDurianBundle:User', 164);
        $config = new DomainConfig($domain, 'domain164', 'ls');
        $manager->persist($config);

        $domain = $manager->find('BBDurianBundle:User', 1);
        $config = new DomainConfig($domain, 'domain1', 'jo');
        $manager->persist($config);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserDataForCustomizeController'
        ];
    }
}
