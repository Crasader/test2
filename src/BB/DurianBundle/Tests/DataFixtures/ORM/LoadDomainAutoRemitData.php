<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\DomainAutoRemit;

class LoadDomainAutoRemitData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $autoRemit = $manager->find('BBDurianBundle:AutoRemit', 2);

        $domainAutoRemit = new DomainAutoRemit(3, $autoRemit);
        $manager->persist($domainAutoRemit);

        $autoRemit = $manager->find('BBDurianBundle:AutoRemit', 1);

        $domainAutoRemit = new DomainAutoRemit(2, $autoRemit);
        $domainAutoRemit->setApiKey('zxcasdqwe123');
        $manager->persist($domainAutoRemit);

        $autoRemit = $manager->find('BBDurianBundle:AutoRemit', 2);

        $domainAutoRemit = new DomainAutoRemit(2, $autoRemit);
        $manager->persist($domainAutoRemit);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadAutoRemitData',
        ];
    }
}
