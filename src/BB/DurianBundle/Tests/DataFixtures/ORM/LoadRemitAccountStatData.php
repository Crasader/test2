<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\RemitAccountStat;

class LoadRemitAccountStatData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $remitAccount = $manager->find('BBDurianBundle:RemitAccount', 6);
        $stat = new RemitAccountStat($remitAccount, new \DateTime());
        $stat->setCount(1);

        $manager->persist($stat);

        $remitAccount = $manager->find('BBDurianBundle:RemitAccount', 7);
        $stat = new RemitAccountStat($remitAccount, new \DateTime());
        $stat->setCount(2);

        $manager->persist($stat);

        $remitAccount = $manager->find('BBDurianBundle:RemitAccount', 8);
        $stat = new RemitAccountStat($remitAccount, new \DateTime());
        $stat->setCount(1);

        $remitAccount = $manager->find('BBDurianBundle:RemitAccount', 9);
        $stat = new RemitAccountStat($remitAccount, new \DateTime());
        $stat->setCount(1);
        $stat->setIncome(500);
        $stat->setPayout(100);

        $manager->persist($stat);

        $remitAccount = $manager->find('BBDurianBundle:RemitAccount', 9);
        $stat = new RemitAccountStat($remitAccount, new \DateTime('2017-08-31T21:00:00+0800'));
        $stat->setCount(1);
        $stat->setIncome(700);
        $stat->setPayout(100);

        $manager->persist($stat);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemitAccountData',
        ];
    }
}
