<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\DepositMobile;

class LoadDepositMobileData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        // PaymentCharge1
        $paymentCharge = $manager->find('BBDurianBundle:PaymentCharge', 1);
        $depositMobile = new DepositMobile($paymentCharge);
        $manager->persist($depositMobile);

        // PaymentCharge2
        $paymentCharge = $manager->find('BBDurianBundle:PaymentCharge', 2);
        $depositMobile = new DepositMobile($paymentCharge);
        $manager->persist($depositMobile);

        // PaymentCharge3
        $paymentCharge = $manager->find('BBDurianBundle:PaymentCharge', 3);
        $depositMobile = new DepositMobile($paymentCharge);
        $manager->persist($depositMobile);

        // PaymentCharge4
        $paymentCharge = $manager->find('BBDurianBundle:PaymentCharge', 4);
        $depositMobile = new DepositMobile($paymentCharge);
        $manager->persist($depositMobile);

        // PaymentCharge5
        $paymentCharge = $manager->find('BBDurianBundle:PaymentCharge', 5);
        $depositMobile = new DepositMobile($paymentCharge);
        $depositMobile->setDiscountPercent(101);
        $manager->persist($depositMobile);

        // PaymentCharge6
        $paymentCharge = $manager->find('BBDurianBundle:PaymentCharge', 6);
        $depositMobile = new DepositMobile($paymentCharge);
        $depositMobile->setDiscount(DepositMobile::EACH);
        $depositMobile->setDiscountGiveUp(true);
        $depositMobile->setDiscountAmount(100);
        $depositMobile->setDiscountPercent(2.12);
        $depositMobile->setDiscountFactor(1);
        $depositMobile->setDiscountLimit(500);
        $depositMobile->setDepositMax(7000);
        $depositMobile->setDepositMin(100);
        $depositMobile->setAuditLive(true);
        $depositMobile->setAuditLiveAmount(10);
        $depositMobile->setAuditBall(true);
        $depositMobile->setAuditBallAmount(20);
        $depositMobile->setAuditComplex(true);
        $depositMobile->setAuditComplexAmount(30);
        $depositMobile->setAuditNormal(true);
        $depositMobile->setAudit3D(true);
        $depositMobile->setAudit3DAmount(40);
        $depositMobile->setAuditBattle(true);
        $depositMobile->setAuditBattleAmount(50);
        $depositMobile->setAuditVirtual(true);
        $depositMobile->setAuditVirtualAmount(60);
        $depositMobile->setAuditDiscountAmount(5);
        $depositMobile->setAuditLoosen(40);
        $depositMobile->setAuditAdministrative(10);
        $manager->persist($depositMobile);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentChargeData',
        ];
    }
}
