<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\DepositBitcoin;

class LoadDepositBitcoinData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        // PaymentCharge1
        $paymentCharge = $manager->find('BBDurianBundle:PaymentCharge', 1);
        $depositBitcoin = new DepositBitcoin($paymentCharge);
        $manager->persist($depositBitcoin);

        // PaymentCharge2
        $paymentCharge = $manager->find('BBDurianBundle:PaymentCharge', 2);
        $depositBitcoin = new DepositBitcoin($paymentCharge);
        $manager->persist($depositBitcoin);

        // PaymentCharge3
        $paymentCharge = $manager->find('BBDurianBundle:PaymentCharge', 3);
        $depositBitcoin = new DepositBitcoin($paymentCharge);
        $manager->persist($depositBitcoin);

        // PaymentCharge4
        $paymentCharge = $manager->find('BBDurianBundle:PaymentCharge', 4);
        $depositBitcoin = new DepositBitcoin($paymentCharge);
        $manager->persist($depositBitcoin);

        // PaymentCharge5
        $paymentCharge = $manager->find('BBDurianBundle:PaymentCharge', 5);
        $depositBitcoin = new DepositBitcoin($paymentCharge);
        $depositBitcoin->setDiscountPercent(101);
        $manager->persist($depositBitcoin);

        // PaymentCharge6
        $paymentCharge = $manager->find('BBDurianBundle:PaymentCharge', 6);
        $depositBitcoin = new DepositBitcoin($paymentCharge);
        $depositBitcoin->setDiscount(DepositBitcoin::EACH);
        $depositBitcoin->setDiscountGiveUp(true);
        $depositBitcoin->setDiscountAmount(100);
        $depositBitcoin->setDiscountPercent(2.12);
        $depositBitcoin->setDiscountFactor(1);
        $depositBitcoin->setDiscountLimit(500);
        $depositBitcoin->setDepositMax(7000);
        $depositBitcoin->setDepositMin(100);
        $depositBitcoin->setAuditLive(true);
        $depositBitcoin->setAuditLiveAmount(10);
        $depositBitcoin->setAuditBall(true);
        $depositBitcoin->setAuditBallAmount(20);
        $depositBitcoin->setAuditComplex(true);
        $depositBitcoin->setAuditComplexAmount(30);
        $depositBitcoin->setAuditNormal(true);
        $depositBitcoin->setAudit3D(true);
        $depositBitcoin->setAudit3DAmount(40);
        $depositBitcoin->setAuditBattle(true);
        $depositBitcoin->setAuditBattleAmount(50);
        $depositBitcoin->setAuditVirtual(true);
        $depositBitcoin->setAuditVirtualAmount(60);
        $depositBitcoin->setAuditDiscountAmount(5);
        $depositBitcoin->setAuditLoosen(40);
        $depositBitcoin->setAuditAdministrative(10);
        $depositBitcoin->setBitcoinFeeMax(1000);
        $depositBitcoin->setBitcoinFeePercent(10);
        $manager->persist($depositBitcoin);

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
