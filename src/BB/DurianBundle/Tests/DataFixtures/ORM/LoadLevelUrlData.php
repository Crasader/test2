<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\LevelUrl;

class LoadLevelUrlData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $level2 = $manager->find('BBDurianBundle:Level', 2);
        $level3 = $manager->find('BBDurianBundle:Level', 3);

        $levelUrl1 = new LevelUrl($level3, 'acc.com');
        $levelUrl1->enable();
        $manager->persist($levelUrl1);

        $levelUrl2 = new LevelUrl($level3, 'acc.net');
        $manager->persist($levelUrl2);

        $levelUrl3 = new LevelUrl($level3, 'acc.edu');
        $manager->persist($levelUrl3);

        $levelUrl4 = new LevelUrl($level2, 'abc.abc');
        $manager->persist($levelUrl4);

        $levelUrl5 = new LevelUrl($level2, 'cde.cde');
        $levelUrl5->enable();
        $manager->persist($levelUrl5);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelData'
        ];
    }
}
