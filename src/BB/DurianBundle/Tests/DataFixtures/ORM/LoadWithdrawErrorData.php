<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\WithdrawError;

class LoadWithdrawErrorData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $withdrawError = new WithdrawError(8, 380021, 'No BankCurrency found');
        $manager->persist($withdrawError);

        $manager->flush();
    }
}
