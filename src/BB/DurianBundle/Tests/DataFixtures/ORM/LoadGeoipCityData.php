<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\GeoipCity;

class LoadGeoipCityData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $ipEd = new GeoipCity(1, 'TW', '01', 'East Dist.');
        $ipEd->setEnName('East Dist');
        $ipEd->setZhCnName('東區');
        $ipEd->setZhTwName('東區');
        $manager->persist($ipEd);

        $ipSy = new GeoipCity(2, 'CN', '19', 'Shenyang');
        $ipSy->setEnName('Shenyang');
        $ipSy->setZhCnName('瀋陽');
        $ipSy->setZhTwName('瀋陽');
        $manager->persist($ipSy);

        $ipBj = new GeoipCity(3, 'CN', '22', 'Beijing');
        $ipBj->setEnName('Beijing');
        $ipBj->setZhCnName('北京');
        $ipBj->setZhTwName('北京');
        $manager->persist($ipBj);

        $ipCk = new GeoipCity(4, 'MY', '68', 'Changkat');
        $ipCk->setEnName('Changkat');
        $ipCk->setZhCnName('吉隆坡');
        $ipCk->setZhTwName('吉隆坡');
        $manager->persist($ipCk);

        $ipJp = new GeoipCity(5, 'JP', '77', 'unKnowCity');
        $manager->persist($ipJp);

        $manager->flush();
    }
}
