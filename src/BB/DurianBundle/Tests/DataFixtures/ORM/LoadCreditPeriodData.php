<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\CreditPeriod;

class LoadCreditPeriodData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {

        //credit 5
        $credit = $manager->find('BB\DurianBundle\Entity\Credit', 5);

        $date = new \DateTime('2011-07-20');
        $period = new CreditPeriod($credit, $date);
        $period->addAmount(700);
        $manager->persist($period);

        //credit 6
        $credit = $manager->find('BB\DurianBundle\Entity\Credit', 6);

        $period = new CreditPeriod($credit, $date);
        $period->addAmount(200);
        $manager->persist($period);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCreditData'
        );
    }
}
