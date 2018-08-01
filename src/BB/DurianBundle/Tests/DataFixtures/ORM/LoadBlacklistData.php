<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\Blacklist;

class LoadBlacklistData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $blacklist = new Blacklist();
        $blacklist->setAccount('blackbank123');
        $blacklist->setControlTerminal(true);
        $manager->persist($blacklist);

        $blacklist = new Blacklist();
        $blacklist->setIdentityCard('55665566');
        $blacklist->setControlTerminal(true);
        $manager->persist($blacklist);

        $blacklist = new Blacklist(2);
        $blacklist->setNameReal('控端指定廳人工新增黑名單');
        $blacklist->setControlTerminal(true);
        $manager->persist($blacklist);

        $blacklist = new Blacklist();
        $blacklist->setTelephone('0911123456');
        $blacklist->setControlTerminal(true);
        $manager->persist($blacklist);

        $blacklist = new Blacklist();
        $blacklist->setEmail('blackemail@tmail.com');
        $blacklist->setControlTerminal(true);
        $manager->persist($blacklist);

        $blacklist = new Blacklist();
        $blacklist->setIp('115.195.41.247');
        $blacklist->setSystemLock(true);
        $blacklist->setControlTerminal(true);
        $manager->persist($blacklist);

        $blacklist = new Blacklist(2);
        $blacklist->setNameReal('廳主端人工新增黑名單');
        $manager->persist($blacklist);

        $blacklist = new Blacklist(9);
        $blacklist->setTelephone('3345678');
        $manager->persist($blacklist);
        $manager->flush();
    }
}
