<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\SlideBinding;

class LoadSlideBindingData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $device1 = $manager->find('BBDurianBundle:SlideDevice', 1);
        $device2 = $manager->find('BBDurianBundle:SlideDevice', 2);
        $device3 = $manager->find('BBDurianBundle:SlideDevice', 3);
        $device4 = $manager->find('BBDurianBundle:SlideDevice', 4);

        $binding1 = new SlideBinding(5, $device4);
        $binding1->setBindingToken('kiminonawa');
        $manager->persist($binding1);

        $binding2 = new SlideBinding(6, $device2);
        $binding2->setBindingToken('katawaredoki');
        $binding2->setErrNum(2);
        $manager->persist($binding2);

        $binding3 = new SlideBinding(8, $device1);
        $binding3->setBindingToken('kiminonawa');
        $binding3->setName('三葉');
        $manager->persist($binding3);

        $binding4 = new SlideBinding(8, $device3);
        $binding4->setBindingToken('kiminonawa');
        $binding4->setName('奧寺');
        $binding4->setErrNum(3);
        $manager->persist($binding4);

        $binding5 = new SlideBinding(8, $device4);
        $binding5->setBindingToken('kiminonawa');
        $manager->persist($binding5);

        $binding6 = new SlideBinding(9, $device1);
        $binding6->setBindingToken('kiminonawa');
        $manager->persist($binding6);

        $binding7 = new SlideBinding(50, $device1);
        $binding7->setBindingToken('kiminonawa');
        $manager->persist($binding7);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return ['BB\DurianBundle\Tests\DataFixtures\ORM\LoadSlideDeviceData'];
    }
}
