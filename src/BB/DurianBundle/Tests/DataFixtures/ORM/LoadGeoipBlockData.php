<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\GeoipBlock;

class LoadGeoipBlockData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $ipHK = new GeoipBlock(
            3758075904,
            3758079999,
            1,
            3,
            6,
            8
        );
        $manager->persist($ipHK);

        $ipCN = new GeoipBlock(
            3757867008,
            3757875199,
            1,
            2, //CN
            2, //19
            2  //Shenyang
        );
        $manager->persist($ipCN);

        $ipCN2 = new GeoipBlock(
            704905216,
            705167359,
            1,
            2, //CN
            3, //22
            3  //'Beijing'
        );
        $manager->persist($ipCN2);

        $ipCN3 = new GeoipBlock(
            1877705728,
            1877706751,
            1,
            4, // MY
            4, // 68
            4  // 'Changkat'
        );
        $manager->persist($ipCN3);

        $ipJP = new GeoipBlock(
            2113929217,
            2114116095,
            1,
            5, // JP
            5, // 77
            5  // unKnowCity
        );
        $manager->persist($ipJP);

        $ipJP2 = new GeoipBlock(
            3659148580,
            3659151629,
            1,
            5, // JP
            null,
            null
        );
        $manager->persist($ipJP2);

        $manager->flush();
    }
}
