<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\CashFakeError;

class LoadCashFakeErrorData extends AbstractFixture
{
    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $date = new \Datetime("2012-12-01 06:00:00");
        $error1 = new CashFakeError();
        $error1->setTotalAmount(1000);
        $error1->setBalance(2000);
        $error1->setCashFakeId(2);
        $error1->setUserId(8);
        $error1->setCurrency(156);
        $error1->setTotalAmount(1000);
        $error1->setAt($date);
        $manager->persist($error1);

        $error2 = new CashFakeError();
        $error2->setTotalAmount(2000);
        $error2->setBalance(3000);
        $error2->setCashFakeId(3);
        $error2->setUserId(8);
        $error2->setCurrency(156);
        $error2->setAt($date);
        $manager->persist($error2);

        $manager->flush();
    }
}
