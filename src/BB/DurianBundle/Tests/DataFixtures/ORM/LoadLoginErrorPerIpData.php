<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\LoginErrorPerIp;

class LoadLoginErrorPerIpData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $time = new \DateTime('now');

        $stat = new LoginErrorPerIp('111.235.135.3', $time, 2);
        $stat->addCount();
        $manager->persist($stat);

        $stat = new LoginErrorPerIp('123.123.123.123', $time, 2);
        $stat->addCount();
        $manager->persist($stat);

        $stat = new LoginErrorPerIp('111.235.135.3', $time->sub(new \DateInterval('P2D')), 2);
        $stat->addCount(2);
        $manager->persist($stat);

        $time = new \DateTime('2014-05-07T22:13:35+0800');

        $stat = new LoginErrorPerIp('111.235.135.3', $time, 2);
        $stat->addCount(2);
        $manager->persist($stat);

        $manager->flush();
    }
}
