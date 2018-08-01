<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\LastLogin;

class LoadLastLoginData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $last = new LastLogin(8, '192.168.1.2');
        $last->setLoginLogId(2);
        $manager->persist($last);

        $last = new LastLogin(10, '192.168.1.1');
        $manager->persist($last);

        $manager->flush();
    }
}
