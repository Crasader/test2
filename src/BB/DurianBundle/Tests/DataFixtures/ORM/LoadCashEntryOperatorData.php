<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\CashEntryOperator;

class LoadCashEntryOperatorData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        // cash entry 1
        $operator = new CashEntryOperator(1, 'company');
        $manager->persist($operator);

        $operator = new CashEntryOperator(2, 'company2');
        $manager->persist($operator);

        $operator = new CashEntryOperator(9, 'company');
        $manager->persist($operator);

        $operator = new CashEntryOperator(10, 'company');
        $manager->persist($operator);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashEntryData'
        );
    }
}
