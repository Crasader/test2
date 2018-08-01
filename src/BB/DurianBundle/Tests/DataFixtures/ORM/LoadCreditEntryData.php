<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\CreditEntry;

class LoadCreditEntryData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $now = new \DateTime();
        // credit 1
        $credit = $manager->find('BBDurianBundle:Credit', 1);

        $userId = $credit->getUser()->getId();
        $groupNum = $credit->getGroupNum();

        $entry = new CreditEntry($userId, $groupNum, 50020, -100, 900, $now);
        $entry->setCreditId(1);
        $entry->setLine($credit->getLine());
        $entry->setTotalLine($credit->getTotalLine());
        $entry->setRefId(1234567);

        $manager->persist($entry);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCreditData'
        );
    }
}
