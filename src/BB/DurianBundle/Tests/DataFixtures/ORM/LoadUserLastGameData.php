<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\UserLastGame;

class LoadUserLastGameData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $time = new \DateTime('2018-02-14 11:11:11');
        $user = $manager->find('BBDurianBundle:User', 7);
        $userLastGame = new UserLastGame($user);
        $userLastGame->enable();
        $userLastGame->setModifiedAt($time);
        $userLastGame->setLastGameCode(1);
        $manager->persist($userLastGame);

        $user = $manager->find('BBDurianBundle:User', 10);
        $userLastGame = new UserLastGame($user);
        $userLastGame->setLastGameCode(1);
        $manager->persist($userLastGame);

        $user = $manager->find('BBDurianBundle:User', 2);
        $userLastGame = new UserLastGame($user);
        $userLastGame->setLastGameCode(1);
        $manager->persist($userLastGame);

        $time = new \DateTime('2018-02-14 11:11:11');
        $user = $manager->find('BBDurianBundle:User', 6);
        $userLastGame = new UserLastGame($user);
        $userLastGame->setModifiedAt($time);
        $userLastGame->setLastGameCode(1);
        $manager->persist($userLastGame);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return ['BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData'];
    }
}
