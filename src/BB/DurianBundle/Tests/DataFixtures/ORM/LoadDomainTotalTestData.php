<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\DomainTotalTest;

class LoadDomainTotalTestData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $domain2 = new DomainTotalTest(2);
        $manager->persist($domain2);

        $domain9 = new DomainTotalTest(9);
        $manager->persist($domain9);

        $manager->flush();
    }
}
