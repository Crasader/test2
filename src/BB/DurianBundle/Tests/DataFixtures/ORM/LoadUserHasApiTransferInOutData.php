<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\UserHasApiTransferInOut;

class LoadUserHasApiTransferInOutData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $data = new UserHasApiTransferInOut(7, false, false);
        $manager->persist($data);

        $data = new UserHasApiTransferInOut(8, true, false);
        $manager->persist($data);

        $manager->flush();
    }
}
