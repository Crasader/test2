<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\MerchantWithdrawStat;

class LoadMerchantWithdrawStatData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $date1 = new \DateTime('2016-01-01T12:00:00+0800');
        $date2 = new \DateTime('2016-01-25T12:00:00+0800');
        $merchantWithdraw2 = $manager->find('BBDurianBundle:MerchantWithdraw', 2);
        $merchantWithdraw3 = $manager->find('BBDurianBundle:MerchantWithdraw', 3);

        $stat1 = new MerchantWithdrawStat($merchantWithdraw2, $date1, 2);
        $manager->persist($stat1);

        $stat2 = new MerchantWithdrawStat($merchantWithdraw3, $date2, 2);
        $manager->persist($stat2);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantWithdrawData',
        ];
    }
}
