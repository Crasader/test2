<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\User;

class LoadUserData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {

        //2 company
        $user1 = new User();
        $user1->setId(2);
        $user1->setUsername('company');
        $user1->setAlias('company');
        $user1->setPassword('123456');
        $user1->setDomain(2);
        $user1->setRole(8);
        $manager->persist($user1);

        //3 vtester
        $user2 = new User();
        $user2->setId(3);
        $user2->setUsername('vtester');
        $user2->setParent($user1);
        $user1->addSize();
        $user2->setAlias('vtester');
        $user2->setPassword('123456');
        $user2->setDomain(2);
        $user2->setRole(7);
        $manager->persist($user2);

        //4 wtester
        $user3 = new User();
        $user3->setId(4);
        $user3->setUsername('wtester');
        $user3->setParent($user2);
        $user2->addSize();
        $user3->setAlias('wtester');
        $user3->setPassword('123456');
        $user3->setDomain(2);
        $user3->setRole(5);
        $manager->persist($user3);

        //5 xtester
        $user4 = new User();
        $user4->setId(5);
        $user4->setUsername('xtester');
        $user4->setParent($user3);
        $user3->addSize();
        $user4->setAlias('xtester');
        $user4->setPassword('123456');
        $user4->setDomain(2);
        $user4->setRole(4);
        $manager->persist($user4);

        //6 ytester
        $user5 = new User();
        $user5->setId(6);
        $user5->setUsername('ytester');
        $user5->setParent($user4);
        $user4->addSize();
        $user5->setAlias('ytester');
        $user5->setPassword('123456');
        $user5->setDomain(2);
        $user5->setRole(3);
        $user5->setHiddenTest(true);
        $manager->persist($user5);

        //7 ztester
        $user6 = new User();
        $user6->setId(7);
        $user6->setUsername('ztester');
        $user6->setParent($user5);
        $user5->addSize();
        $user6->setAlias('ztester');
        $user6->setPassword('123456');
        $user6->setCurrency(901); // TWD
        $user6->setDomain(2);
        $user6->setCreatedAt(new \DateTime('2013-1-1 11:11:11'));
        $user6->setRole(2);
        $manager->persist($user6);

        //8 tester
        $user7 = new User();
        $user7->setId(8);
        $user7->setUsername('tester');
        $user7->setParent($user6);
        $user6->addSize();
        $user7->setAlias('tester');
        $user7->setPassword('123456');
        $user7->setDomain(2);
        $user7->setRole(1);
        $manager->persist($user7);

        //9 isolate user
        $user8 = new User();
        $user8->setId(9);
        $user8->setUsername('isolate');
        $user8->setAlias('isolate');
        $user8->setPassword('123456');
        $user8->setDomain(9);
        $user8->setRole(8);
        $manager->persist($user8);

        //10 gaga
        $user9 = new User();
        $user9->setId(10);
        $user9->setUsername('gaga');
        $user9->setParent($user8);
        $user8->addSize();
        $user9->setAlias('gaga');
        $user9->setPassword('gagagaga');
        $user9->setDomain(9);
        $user9->setCreatedAt(new \DateTime('2011-1-1 11:11:11'));
        $user9->setModifiedAt(new \DateTime('2011-1-1 11:12:11'));
        $user9->setPasswordExpireAt(new \DateTime('2011-12-1 11:11:11'));
        $user9->setRole(7);
        $manager->persist($user9);

        //50 vtest2
        $user50 = new User();
        $user50->setId(50);
        $user50->setUsername('vtester2');
        $user50->setParent($user1);
        $user1->addSize();
        $user50->setAlias('vtester2');
        $user50->setPassword('123456');
        $user50->setDomain(2);
        $user50->setRole(7);
        $manager->persist($user50);

        //20000000 domain20m
        $user20m = new User();
        $user20m->setId(20000000);
        $user20m->setUsername('domain20m');
        $user20m->setParent($user1);
        $user1->addSize();
        $user20m->setAlias('domain20m');
        $user20m->setPassword('123');
        $user20m->setDomain(2);
        $user20m->setRole(7);
        $manager->persist($user20m);

        //oauth user
        $user51 = new User();
        $user51->setId(51);
        $user51->setUsername('oauthuser');
        $user51->setPassword('');
        $user51->setParent($user6);
        $user6->addSize();
        $user51->setAlias('oauthuser');
        $user51->setDomain(2);
        $user51->setRole(1);
        $manager->persist($user51);

        $manager->flush();
    }
}
