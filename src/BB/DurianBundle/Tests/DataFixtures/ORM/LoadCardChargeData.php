<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\CardCharge;

class LoadCardChargeData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $cardCharge = new CardCharge(2);
        $cardCharge->setOrderStrategy(CardCharge::STRATEGY_ORDER);
        $cardCharge->setDepositScMax(99);
        $cardCharge->setDepositScMin(11);
        $cardCharge->setDepositCoMax(98);
        $cardCharge->setDepositCoMin(12);
        $cardCharge->setDepositSaMax(97);
        $cardCharge->setDepositSaMin(13);
        $cardCharge->setDepositAgMax(96);
        $cardCharge->setDepositAgMin(14);
        $manager->persist($cardCharge);

        $manager->flush();
    }
}
