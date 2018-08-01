<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\LevelTransfer;

class LoadLevelTransferData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $levelTransfer1 = new LevelTransfer(3, 2, 5);
        $manager->persist($levelTransfer1);

        $levelTransfer2 = new LevelTransfer(10, 6, 4);
        $manager->persist($levelTransfer2);

        $manager->flush();
    }
}
