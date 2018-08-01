<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\PaymentCharge;
use BB\DurianBundle\Entity\CashDepositEntry;

class LoadPaymentChargeData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $paywayCash = CashDepositEntry::PAYWAY_CASH;

        // 1
        $paymentCharge = new PaymentCharge($paywayCash, 2, '台幣', true);
        $paymentCharge->setCode('TWD');
        $paymentCharge->setRank(1);
        $manager->persist($paymentCharge);

        // 2
        $paymentCharge = new PaymentCharge($paywayCash, 2, '人民幣', true);
        $paymentCharge->setCode('CNY');
        $paymentCharge->setRank(2);
        $manager->persist($paymentCharge);

        // 3
        $paymentCharge = new PaymentCharge($paywayCash, 2, '美金', true);
        $paymentCharge->setCode('USD');
        $paymentCharge->setRank(3);
        $manager->persist($paymentCharge);

        // 4
        $paymentCharge = new PaymentCharge($paywayCash, 2, '台幣-自訂', false);
        $paymentCharge->setCode('TWD-C');
        $paymentCharge->setRank(1);
        $manager->persist($paymentCharge);

        // 5
        $paymentCharge = new PaymentCharge($paywayCash, 2, '人民幣-自訂', false);
        $paymentCharge->setCode('CNY-C');
        $paymentCharge->setRank(2);
        $manager->persist($paymentCharge);

        // 6
        $paymentCharge = new PaymentCharge($paywayCash, 2, '美金-自訂', false);
        $paymentCharge->setCode('USD-C');
        $paymentCharge->setRank(3);
        $manager->persist($paymentCharge);

        $manager->flush();
    }
}
