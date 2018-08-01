<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\MerchantStat;

class LoadMerchantStatData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $date1 = new \DateTime('2012-01-01T12:00:00+0800');
        $date2 = new \DateTime('2012-01-11T12:00:00+0800');
        $date3 = new \DateTime('now');
        $merchant1 = $manager->find('BBDurianBundle:Merchant', 1);
        $merchant2 = $manager->find('BBDurianBundle:Merchant', 2);

        $stat1 = new MerchantStat($merchant1, $date1, 1);
        $manager->persist($stat1);

        $stat2 = new MerchantStat($merchant1, $date2, 1);
        $manager->persist($stat2);

        $stat3 = new MerchantStat($merchant2, $date3, 2);
        $manager->persist($stat3);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantData'
        );
    }
}
