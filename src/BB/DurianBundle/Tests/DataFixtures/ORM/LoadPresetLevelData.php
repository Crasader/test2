<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\PresetLevel;

class LoadPresetLevelData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        // vtester
        $user3 = $manager->find('BBDurianBundle:User', 3);
        $level1 = $manager->find('BBDurianBundle:Level', 1);
        $presetLevel1 = new PresetLevel($user3, $level1);
        $manager->persist($presetLevel1);

        // xtester
        $user5 = $manager->find('BBDurianBundle:User', 5);
        $level5 = $manager->find('BBDurianBundle:Level', 5);
        $presetLevel2 = new PresetLevel($user5, $level5);
        $manager->persist($presetLevel2);

        // gaga
        $user10 = $manager->find('BBDurianBundle:User', 10);
        $level4 = $manager->find('BBDurianBundle:Level', 4);
        $presetLevel3 = new PresetLevel($user10, $level4);
        $manager->persist($presetLevel3);

        // company
        $user2 = $manager->find('BBDurianBundle:User', 2);
        $level7 = $manager->find('BBDurianBundle:Level', 7);
        $presetLevel4 = new PresetLevel($user2, $level7);
        $manager->persist($presetLevel4);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelData'
        ];
    }
}
