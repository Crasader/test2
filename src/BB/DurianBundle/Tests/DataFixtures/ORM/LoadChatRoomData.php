<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\ChatRoom;

class LoadChatRoomData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $user = $manager->find('BBDurianBundle:User', 8);
        $chatRoom = new ChatRoom($user);
        $chatRoom->setReadable(true);
        $chatRoom->setWritable(true);
        $chatRoom->setBanAt(new \DateTime('99980101000000'));
        $manager->persist($chatRoom);

        $user51 = $manager->find('BBDurianBundle:User', 51);
        $chatRoom2 = new ChatRoom($user51);
        $chatRoom2->setReadable(true);
        $chatRoom2->setWritable(false);
        $manager->persist($chatRoom2);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return ['BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData'];
    }
}
