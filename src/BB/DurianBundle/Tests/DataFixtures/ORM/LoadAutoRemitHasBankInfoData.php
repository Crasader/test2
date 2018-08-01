<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class LoadAutoRemitHasBankInfoData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $autoRemit1 = $manager->find('BBDurianBundle:AutoRemit', 1);

        $bankInfo1 = $manager->find('BBDurianBundle:BankInfo', 1);
        $autoRemit1->addBankInfo($bankInfo1);

        $autoRemit2 = $manager->find('BBDurianBundle:AutoRemit', 2);

        $bankInfo1 = $manager->find('BBDurianBundle:BankInfo', 1);
        $autoRemit2->addBankInfo($bankInfo1);

        $bankInfo2 = $manager->find('BBDurianBundle:BankInfo', 2);
        $autoRemit2->addBankInfo($bankInfo2);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadAutoRemitData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankInfoData',
        ];
    }
}
