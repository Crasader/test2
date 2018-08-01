<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\DepositOnline;

class LoadDepositOnlineData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        // PaymentCharge1
        $paymentCharge = $manager->find('BBDurianBundle:PaymentCharge', 1);
        $depositOnline = new DepositOnline($paymentCharge);
        $manager->persist($depositOnline);

        // PaymentCharge2
        $paymentCharge = $manager->find('BBDurianBundle:PaymentCharge', 2);
        $depositOnline = new DepositOnline($paymentCharge);
        $manager->persist($depositOnline);

        // PaymentCharge3
        $paymentCharge = $manager->find('BBDurianBundle:PaymentCharge', 3);
        $depositOnline = new DepositOnline($paymentCharge);
        $manager->persist($depositOnline);

        // PaymentCharge4
        $paymentCharge = $manager->find('BBDurianBundle:PaymentCharge', 4);
        $depositOnline = new DepositOnline($paymentCharge);
        $manager->persist($depositOnline);

        // PaymentCharge5
        $paymentCharge = $manager->find('BBDurianBundle:PaymentCharge', 5);
        $depositOnline = new DepositOnline($paymentCharge);
        $depositOnline->setDiscountPercent(101);
        $manager->persist($depositOnline);

        // PaymentCharge6
        $paymentCharge = $manager->find('BBDurianBundle:PaymentCharge', 6);
        $depositOnline = new DepositOnline($paymentCharge);
        $depositOnline->setDiscount(DepositOnline::EACH);
        $depositOnline->setDiscountGiveUp(true);
        $depositOnline->setDiscountAmount(100);
        $depositOnline->setDiscountPercent(2.12);
        $depositOnline->setDiscountFactor(1);
        $depositOnline->setDiscountLimit(500);
        $depositOnline->setDepositMax(7000);
        $depositOnline->setDepositMin(100);
        $depositOnline->setAuditLive(true);
        $depositOnline->setAuditLiveAmount(10);
        $depositOnline->setAuditBall(true);
        $depositOnline->setAuditBallAmount(20);
        $depositOnline->setAuditComplex(true);
        $depositOnline->setAuditComplexAmount(30);
        $depositOnline->setAuditNormal(true);
        $depositOnline->setAudit3D(true);
        $depositOnline->setAudit3DAmount(40);
        $depositOnline->setAuditBattle(true);
        $depositOnline->setAuditBattleAmount(50);
        $depositOnline->setAuditVirtual(true);
        $depositOnline->setAuditVirtualAmount(60);
        $depositOnline->setAuditDiscountAmount(5);
        $depositOnline->setAuditLoosen(40);
        $depositOnline->setAuditAdministrative(10);
        $manager->persist($depositOnline);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentChargeData',
        );
    }
}
