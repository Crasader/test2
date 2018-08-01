<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\GeoipRegion;

class LoadGeoipRegionData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $ipTaipei = new GeoipRegion(1, 'TW', 01);
        $ipTaipei->setEnName('Taipei');
        $ipTaipei->setZhCnName('台北');
        $ipTaipei->setZhTwName('台北');
        $manager->persist($ipTaipei);

        $ipLn = new GeoipRegion(2, 'CN', 19);
        $ipLn->setEnName('Laio Ning');
        $ipLn->setZhCnName('遼寧');
        $ipLn->setZhTwName('遼寧');
        $manager->persist($ipLn);

        $ipHb = new GeoipRegion(2, 'CN', 22);
        $ipHb->setEnName('He Bei');
        $ipHb->setZhCnName('河北');
        $ipHb->setZhTwName('河北');
        $manager->persist($ipHb);

        $manager->flush();
    }
}
