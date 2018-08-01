<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\CardError;

class LoadCardErrorData extends AbstractFixture
{
    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $date = new \Datetime("2012-12-01 06:00:00");
        $error1 = new CardError();
        $error1->setTotalAmount(1000);
        $error1->setBalance(2000);
        $error1->setCardId(2);
        $error1->setUserId(3);
        $error1->setAt($date);
        $manager->persist($error1);

        $error2 = new CardError();
        $error2->setTotalAmount(2000);
        $error2->setBalance(3000);
        $error2->setCardId(3);
        $error2->setUserId(4);
        $error2->setAt($date);
        $manager->persist($error2);

        $manager->flush();
    }
}
