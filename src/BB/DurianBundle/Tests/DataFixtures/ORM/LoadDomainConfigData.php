<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\DomainConfig;

class LoadDomainConfigData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $config = new DomainConfig(2, 'domain2', 'cm');
        $manager->persist($config);

        $config = new DomainConfig(9, '蓝田', 'ag');
        $manager->persist($config);

        $config = new DomainConfig(3, 'domain3', 'th');
        $manager->persist($config);

        $manager->flush();
    }
}
