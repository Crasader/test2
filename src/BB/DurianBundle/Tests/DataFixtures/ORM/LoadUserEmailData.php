<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\UserEmail;

class LoadUserEmailData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $time = new \DateTime('2015-03-25 17:31:13');
        $user = $manager->find('BBDurianBundle:User', 8);
        $userEmail = new UserEmail($user);
        $userEmail->setEmail('Davinci@chinatown.com');
        $manager->persist($userEmail);

        $user = $manager->find('BBDurianBundle:User', 10);
        $time = new \DateTime('2015-03-27 09:12:53');
        $userEmail = new UserEmail($user);
        $userEmail->setEmail('Davinci@chinatown.com');
        $userEmail->setConfirm(true);
        $userEmail->setConfirmAt($time);
        $manager->persist($userEmail);

        $user = $manager->find('BBDurianBundle:User', 2);
        $userEmail = new UserEmail($user);
        $userEmail->setEmail('');
        $manager->persist($userEmail);

        $user = $manager->find('BBDurianBundle:User', 3);
        $userEmail = new UserEmail($user);
        $userEmail->setEmail('');
        $manager->persist($userEmail);

        $user = $manager->find('BBDurianBundle:User', 4);
        $userEmail = new UserEmail($user);
        $userEmail->setEmail('');
        $manager->persist($userEmail);

        $user = $manager->find('BBDurianBundle:User', 5);
        $userEmail = new UserEmail($user);
        $userEmail->setEmail('');
        $manager->persist($userEmail);

        $user = $manager->find('BBDurianBundle:User', 6);
        $userEmail = new UserEmail($user);
        $userEmail->setEmail('Davinci@chinatown.com');
        $manager->persist($userEmail);

        $user = $manager->find('BBDurianBundle:User', 7);
        $userEmail = new UserEmail($user);
        $userEmail->setEmail('');
        $manager->persist($userEmail);

        $user = $manager->find('BBDurianBundle:User', 9);
        $userEmail = new UserEmail($user);
        $userEmail->setEmail('');
        $manager->persist($userEmail);

        $user = $manager->find('BBDurianBundle:User', 50);
        $userEmail = new UserEmail($user);
        $userEmail->setEmail('');
        $manager->persist($userEmail);

        $user = $manager->find('BBDurianBundle:User', 51);
        $userEmail = new UserEmail($user);
        $userEmail->setEmail('');
        $manager->persist($userEmail);

        $user = $manager->find('BBDurianBundle:User', 20000000);
        $userEmail = new UserEmail($user);
        $userEmail->setEmail('');
        $manager->persist($userEmail);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return ['BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData'];
    }
}
