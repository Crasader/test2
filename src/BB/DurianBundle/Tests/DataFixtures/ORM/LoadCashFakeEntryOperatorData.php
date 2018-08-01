<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\CashFakeEntryOperator;

class LoadCashFakeEntryOperatorData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        // cash entry 1
        $operator = new CashFakeEntryOperator(1, 'company');
        $operator->setTransferOut(true);
        $operator->setLevel(2);
        $operator->setWhom('lala');
        $manager->persist($operator);

        $operator = new CashFakeEntryOperator(2, 'company');
        $operator->setTransferOut(true);
        $operator->setLevel(2);
        $operator->setWhom('lala');
        $manager->persist($operator);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeEntryDataForTotalCalculate'
        );
    }
}
