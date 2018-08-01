<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\MaintainWhitelist;

class LoadMaintainWhitelistData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $whitelist = new MaintainWhitelist('10.240.22.122');
        $manager->persist($whitelist);

        $whitelist = new MaintainWhitelist('10.240.22.123');
        $manager->persist($whitelist);

        $manager->flush();
    }
}
