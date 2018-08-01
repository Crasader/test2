<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\CashEntry;

class LoadCashEntryDataForRemit extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $user = $manager->find('BBDurianBundle:User', 8);
        $cash = $user->getCash();
        $remitEntry = $manager->find('BBDurianBundle:RemitEntry', 5);

        $entry = new CashEntry($cash, 1036, 100); // 1036 DEPOSIT-COMPANY-IN 公司入款
        $entry->setId(1);
        $entry->setRefId($remitEntry->getOrderNumber());
        $manager->persist($entry);

        $entry->setCreatedAt($remitEntry->getConfirmAt());
        $entry->setAt($remitEntry->getConfirmAt()->format('YmdHis'));

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemitEntryData'
        ];
    }
}
