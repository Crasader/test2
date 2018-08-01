<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\DepositCompany;

class LoadDepositCompanyData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        // PaymentCharge1
        $paymentCharge  = $manager->find('BBDurianBundle:PaymentCharge', 1);
        $depositCompany = new DepositCompany($paymentCharge);
        $manager->persist($depositCompany);

        // PaymentCharge2
        $paymentCharge  = $manager->find('BBDurianBundle:PaymentCharge', 2);
        $depositCompany = new DepositCompany($paymentCharge);
        $manager->persist($depositCompany);

        // PaymentCharge3
        $paymentCharge  = $manager->find('BBDurianBundle:PaymentCharge', 3);
        $depositCompany = new DepositCompany($paymentCharge);
        $manager->persist($depositCompany);

        // PaymentCharge4
        $paymentCharge  = $manager->find('BBDurianBundle:PaymentCharge', 4);
        $depositCompany = new DepositCompany($paymentCharge);
        $manager->persist($depositCompany);

        // PaymentCharge5
        $paymentCharge  = $manager->find('BBDurianBundle:PaymentCharge', 5);
        $depositCompany = new DepositCompany($paymentCharge);
        $manager->persist($depositCompany);

        // PaymentCharge6
        $paymentCharge  = $manager->find('BBDurianBundle:PaymentCharge', 6);
        $depositCompany = new DepositCompany($paymentCharge);
        $depositCompany->setDiscount(DepositCompany::EACH);
        $depositCompany->setDiscountGiveUp(true);
        $depositCompany->setDiscountAmount(51);
        $depositCompany->setDiscountPercent(10);
        $depositCompany->setDiscountFactor(4);
        $depositCompany->setDiscountLimit(1000);
        $depositCompany->setDepositMax(5000);
        $depositCompany->setDepositMin(10);
        $depositCompany->setAuditLive(true);
        $depositCompany->setAuditLiveAmount(5);
        $depositCompany->setAuditBall(true);
        $depositCompany->setAuditBallAmount(10);
        $depositCompany->setAuditComplex(true);
        $depositCompany->setAuditComplexAmount(15);
        $depositCompany->setAuditNormal(true);
        $depositCompany->setAudit3D(true);
        $depositCompany->setAudit3DAmount(20);
        $depositCompany->setAuditBattle(true);
        $depositCompany->setAuditBattleAmount(25);
        $depositCompany->setAuditVirtual(true);
        $depositCompany->setAuditVirtualAmount(30);
        $depositCompany->setAuditDiscountAmount(10);
        $depositCompany->setAuditLoosen(10);
        $depositCompany->setAuditAdministrative(5);
        $depositCompany->setOtherDiscountAmount(50);
        $depositCompany->setOtherDiscountPercent(5);
        $depositCompany->setOtherDiscountLimit(100);
        $depositCompany->setDailyDiscountLimit(500);
        $depositCompany->setDepositScMax(1000);
        $depositCompany->setDepositScMin(5);
        $depositCompany->setDepositCoMax(900);
        $depositCompany->setDepositCoMin(6);
        $depositCompany->setDepositSaMax(800);
        $depositCompany->setDepositSaMin(7);
        $depositCompany->setDepositAgMax(700);
        $depositCompany->setDepositAgMin(8);

        $manager->persist($depositCompany);

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
