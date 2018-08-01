<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\SlideDevice;

class LoadSlideDeviceData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $device1 = new SlideDevice('mitsuha', password_hash('123456789', PASSWORD_BCRYPT));
        $device1->setOs('Android');
        $device1->setBrand('GiONEE');
        $device1->setModel('F103');
        $device2 = new SlideDevice('taki', password_hash('987654321', PASSWORD_BCRYPT));
        $device2->setErrNum(2);
        $device3 = new SlideDevice('okutera', password_hash('7895123', PASSWORD_BCRYPT));
        $device4 = new SlideDevice('sayaka', password_hash('7896321', PASSWORD_BCRYPT));
        $device4->disable();

        $manager->persist($device1);
        $manager->persist($device2);
        $manager->persist($device3);
        $manager->persist($device4);

        $manager->flush();
    }
}
