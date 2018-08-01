<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\GlobalIp;

class LoadGlobalIpData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $gIp = new GlobalIp('127.0.0.1');

        $manager->persist($gIp);
        $manager->flush();
    }
}
