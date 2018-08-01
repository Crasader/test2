<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\IpBlacklist;

class LoadIpBlacklistData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $ipBlack = new IpBlacklist(2, '126.0.0.1');
        $ipBlack->setCreateUser(true);
        $manager->persist($ipBlack);

        $ipBlack = new IpBlacklist(2, '128.0.0.1');
        $ipBlack->setCreateUser(true);
        $manager->persist($ipBlack);

        $ipBlack = new IpBlacklist(2, '111.235.135.3');
        $ipBlack->setLoginError(true);
        $manager->persist($ipBlack);

        $ipBlack = new IpBlacklist(2, '123.123.123.123');
        $ipBlack->setCreateUser(true);
        $ipBlack->remove('tester');
        $manager->persist($ipBlack);

        $ipBlack = new IpBlacklist(2, '127.0.111.1');
        $ipBlack->setCreateUser(true);
        $manager->persist($ipBlack);

        $ipBlack = new IpBlacklist(999, '127.0.111.1');
        $ipBlack->setCreateUser(true);
        $manager->persist($ipBlack);

        $ipBlack = new IpBlacklist(2, '218.26.54.4');
        $ipBlack->setLoginError(true);
        $manager->persist($ipBlack);

        $manager->flush();
    }
}
