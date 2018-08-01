<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\CashTrans;

class LoadCashTransData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        // cashTrans1
        $cash = $manager->find('BBDurianBundle:Cash', 1);

        $cashTrans = new CashTrans($cash, 30001, 101);
        $cashTrans->setId(1)
            ->setRefId(951)
            ->setCreatedAt(new \DateTime('2013-01-05 12:00:00'));
        $manager->persist($cashTrans);

        // cashTrans2
        $cash = $manager->find('BBDurianBundle:Cash', 2);

        $cashTrans = new CashTrans($cash, 30002, 777);
        $cashTrans->setId(2)
            ->setRefId(1236)
            ->setCreatedAt(new \DateTime('2013-01-08 14:25:00'));
        $manager->persist($cashTrans);

        // cashTrans3
        $cash = $manager->find('BBDurianBundle:Cash', 2);

        $cashTrans = new CashTrans($cash, 30002, 212);
        $cashTrans->setId(3)
            ->setRefId(9527)
            ->setCreatedAt(new \DateTime('2013-02-02 02:22:00'));
        $manager->persist($cashTrans);
        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return ['BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData'];
    }
}
