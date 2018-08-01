<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\MerchantCardStat;

class LoadMerchantCardStatData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $date1 = new \DateTime('2012-01-01T12:00:00+0800');
        $date2 = new \DateTime('2012-01-11T12:00:00+0800');
        $date3 = new \DateTime('now');
        $merchantCard1 = $manager->find('BBDurianBundle:MerchantCard', 1);
        $merchantCard2 = $manager->find('BBDurianBundle:MerchantCard', 2);

        $stat1 = new MerchantCardStat($merchantCard1, $date1, 1);
        $manager->persist($stat1);

        $stat2 = new MerchantCardStat($merchantCard1, $date2, 1);
        $manager->persist($stat2);

        $stat3 = new MerchantCardStat($merchantCard2, $date3, 2);
        $manager->persist($stat3);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantCardData'
        ];
    }
}
