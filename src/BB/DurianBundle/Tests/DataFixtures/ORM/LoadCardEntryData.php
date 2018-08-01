<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\CardEntry;

class LoadCardEntryData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $card = $manager->find('BB\DurianBundle\Entity\Card', 7);

        $entry = new CardEntry($card, 9901, 3000, 3000, 'company', 13579); // 9901 TRADE_IN
        $entry->setId(1);
        $time = new \DateTime('2012-01-01 12:00:00');
        $entry->setCreatedAt($time);
        $manager->persist($entry);

        $entry = new CardEntry($card, 9902, 500, 3500, 'company'); // 9902 TRADE_OUT
        $entry->setId(2);
        $time = new \DateTime('2012-01-02 12:00:00');
        $entry->setCreatedAt($time);
        $manager->persist($entry);

        $entry = new CardEntry($card, 20001, -100, 3400, 'isolate'); // 20001 下注
        $entry->setId(3);
        $time = new \DateTime('2012-01-03 12:00:00');
        $entry->setCreatedAt($time);
        $manager->persist($entry);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCardData'
        );
    }
}
