<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\UserCreatedPerIp;

class LoadUserCreatedPerIpData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $now = new \DateTime('2013-09-30 13:00:00');
        $repo = $manager->getRepository('BBDurianBundle:UserCreatedPerIp');

        $stat = new UserCreatedPerIp('127.0.0.1', $now, 99);
        $manager->persist($stat);
        $manager->flush();
        $repo->increaseCount($stat->getId());

        $stat = new UserCreatedPerIp('127.0.0.1', $now, 2);
        $manager->persist($stat);
        $manager->flush();
        $repo->increaseCount($stat->getId());

        $stat = new UserCreatedPerIp('0.0.0.0', $now, 2);
        $manager->persist($stat);
        $manager->flush();
        $repo->increaseCount($stat->getId(), 2);

        $time = new \DateTime('now');
        $stat = new UserCreatedPerIp('127.0.111.1', $time, 2);
        $manager->persist($stat);
        $manager->flush();
        $repo->increaseCount($stat->getId());

        $time = new \DateTime('2014-05-07T22:13:35+0800');
        $stat = new UserCreatedPerIp('127.0.111.1', $time, 2);
        $manager->persist($stat);
        $manager->flush();
        $repo->increaseCount($stat->getId());
    }
}
