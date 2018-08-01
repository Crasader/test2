<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\User;

class LoadUserDataForCustomizeController extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        //1 orderByTester
        $user1 = new User();
        $user1->setId(1);
        $user1->setUsername('orderByTester');
        $user1->setAlias('orderByTester');
        $user1->setPassword('3345678');
        $user1->setDomain(1);
        $user1->setRole(7);
        $manager->persist($user1);

        //75 company
        $user1 = new User();
        $user1->setId(75);
        $user1->setUsername('company');
        $user1->setAlias('company');
        $user1->setPassword('123456');
        $user1->setDomain(75);
        $user1->setRole(7);
        $manager->persist($user1);

        //84 vtester
        $user2 = new User();
        $user2->setId(84);
        $user2->setUsername('vtester');
        $user2->setAlias('vtester');
        $user2->setPassword('123456');
        $user2->setDomain(84);
        $user2->setRole(7);
        $manager->persist($user2);

        //164 wtester
        $user3 = new User();
        $user3->setId(164);
        $user3->setUsername('wtester');
        $user3->setAlias('wtester');
        $user3->setPassword('123456');
        $user3->setDomain(164);
        $user3->setRole(7);
        $manager->persist($user3);

        //858278 wtester
        $user11 = new User();
        $user11->setId(858278);
        $user11->setUsername('yu123');
        $user11->setParent($user1);
        $user1->addSize();
        $user11->setAlias('yu123');
        $user11->setPassword('123456');
        $user11->setDomain(75);
        $user11->setRole(7);
        $user11->setSub(true);
        $manager->persist($user11);

        $user100 = new User();
        $user100->setId(100);
        $user100->setUsername('test100');
        $user100->setAlias('test100');
        $user100->setPassword('123456');
        $user100->setParent($user1);
        $user1->addSize();
        $user100->setDomain(75);
        $user100->setRole(5);
        $manager->persist($user100);

        $user101 = new User();
        $user101->setId(101);
        $user101->setUsername('test101');
        $user101->setAlias('test101');
        $user101->setPassword('123456');
        $user101->setParent($user1);
        $user1->addSize();
        $user101->setDomain(75);
        $user101->setRole(5);
        $user101->setTest(true);
        $manager->persist($user101);

        $user102 = new User();
        $user102->setId(102);
        $user102->setUsername('test102');
        $user102->setAlias('test102');
        $user102->setPassword('123456');
        $user102->setParent($user2);
        $user2->addSize();
        $user102->setDomain(84);
        $user102->setRole(5);
        $user102->setHiddenTest(true);
        $manager->persist($user102);

        $manager->flush();
    }
}
