<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\CardEntry;

class LoadCardEntryDataForDuplicateDeposit extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $card7 = $manager->find('BBDurianBundle:Card', 7);

        $createAt = new \DateTime('2014-10-02 10:23:37');

        // 租卡入款，兩筆CardEntry的RefId屬於CardDepositEntry
        $entry1 = new CardEntry($card7, 9901, 5, 10, 'test', 201502010000000001);
        $entry1->setId(1);
        $entry1->setCreatedAt($createAt);
        $manager->persist($entry1);

        $entry2 = new CardEntry($card7, 9901, 5, 10, 'test', 201502010000000001);
        $entry2->setId(2);
        $entry2->setCreatedAt($createAt);
        $manager->persist($entry2);

        $manager->flush();
    }

    /**
     * @inheritDoc
     */
    public function getDependencies()
    {
        return ['BB\DurianBundle\Tests\DataFixtures\ORM\LoadCardData'];
    }
}
