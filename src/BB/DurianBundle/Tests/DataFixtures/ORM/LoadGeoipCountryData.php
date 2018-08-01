<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\GeoipCountry;

class LoadGeoipCountryData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $ipTw = new GeoipCountry('TW');
        $ipTw->setEnName('Taiwan');
        $ipTw->setZhCnName('台灣');
        $ipTw->setZhTwName('中華民國');
        $manager->persist($ipTw);

        $ipCn = new GeoipCountry('CN');
        $ipCn->setEnName('China');
        $ipCn->setZhCnName('中國');
        $ipCn->setZhTwName('中華人民共和國');
        $manager->persist($ipCn);

        $ipHk = new GeoipCountry('HK');
        $ipHk->setEnName('Hong Kong');
        $ipHk->setZhCnName('香港');
        $ipHk->setZhTwName('香港');
        $manager->persist($ipHk);

        $ipMy = new GeoipCountry('MY');
        $ipMy->setEnName('Malaysia');
        $ipMy->setZhCnName('马来西亚');
        $ipMy->setZhTwName('馬來西亞');
        $manager->persist($ipMy);

        $ipJp = new GeoipCountry('JP');
        $manager->persist($ipJp);

        $manager->flush();
    }
}
