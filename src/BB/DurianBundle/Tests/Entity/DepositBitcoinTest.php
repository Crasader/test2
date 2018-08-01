<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\PaymentCharge;
use BB\DurianBundle\Entity\DepositBitcoin;

class DepositBitcoinTest extends DurianTestCase
{
    /**
     * 測試新增比特幣入款設定
     *
     */
    public function testNewDepositCompany()
    {
        $pc = new PaymentCharge(1, 2, 'gaga', false);
        $bitcoin = new DepositBitcoin($pc);

        $checkArray = [
            'id' => null,
            'payment_charge_id' => null,
            'discount' => DepositBitcoin::FIRST,
            'discount_give_up' => false,
            'discount_amount' => 0,
            'discount_percent' => 0,
            'discount_factor' => 1,
            'discount_limit' => 0,
            'bitcoin_fee_max' => 100,
            'bitcoin_fee_percent' => 1,
            'deposit_max' => 30000,
            'deposit_min' => 100,
            'audit_live' => false,
            'audit_live_amount' => 0,
            'audit_ball' => false,
            'audit_ball_amount' => 0,
            'audit_complex' => false,
            'audit_complex_amount' => 0,
            'audit_normal' => false,
            'audit_normal_amount' => 100,
            'audit_3d' => false,
            'audit_3d_amount' => 0,
            'audit_battle' => false,
            'audit_battle_amount' => 0,
            'audit_virtual' => false,
            'audit_virtual_amount' => 0,
            'audit_discount_amount' => 0,
            'audit_loosen' => 0,
            'audit_administrative' => 0,
        ];

        $this->assertEquals($pc, $bitcoin->getPaymentCharge());
        $this->assertEquals($checkArray, $bitcoin->toArray());

        $bitcoin->setBitcoinFeeMax(500);
        $this->assertEquals(500, $bitcoin->getBitcoinFeeMax());

        $bitcoin->setBitcoinFeePercent(2);
        $this->assertEquals(2, $bitcoin->getBitcoinFeePercent());

        // set method test
        $bitcoin->setDiscount(DepositBitcoin::FIRST);
        $this->assertEquals(DepositBitcoin::FIRST, $bitcoin->getDiscount());

        // set method test
        $bitcoin->setDiscount(DepositBitcoin::EACH);
        $this->assertEquals(DepositBitcoin::EACH, $bitcoin->getDiscount());

        $bitcoin->setDiscountGiveUp(true);
        $this->assertTrue($bitcoin->isDiscountGiveUp());

        $bitcoin->setDiscountAmount(51);
        $this->assertEquals(51, $bitcoin->getDiscountAmount());

        $bitcoin->setDiscountPercent(10);
        $this->assertEquals(10, $bitcoin->getDiscountPercent());

        $bitcoin->setDiscountFactor(4);
        $this->assertEquals(4, $bitcoin->getDiscountFactor());

        $bitcoin->setDiscountLimit(1000);
        $this->assertEquals(1000, $bitcoin->getDiscountLimit());

        $bitcoin->setDepositMax(5000);
        $this->assertEquals(5000, $bitcoin->getDepositMax());

        $bitcoin->setDepositMin(10);
        $this->assertEquals(10, $bitcoin->getDepositMin());

        $bitcoin->setAuditLive(true);
        $this->assertTrue($bitcoin->isAuditLive());

        $bitcoin->setAuditLiveAmount(5);
        $this->assertEquals(5, $bitcoin->getAuditLiveAmount());

        $bitcoin->setAuditBall(true);
        $this->assertTrue($bitcoin->isAuditBall());

        $bitcoin->setAuditBallAmount(10);
        $this->assertEquals(10, $bitcoin->getAuditBallAmount());

        $bitcoin->setAuditComplex(true);
        $this->assertTrue($bitcoin->isAuditComplex());

        $bitcoin->setAuditComplexAmount(15);
        $this->assertEquals(15, $bitcoin->getAuditComplexAmount());

        $bitcoin->setAuditNormal(true);
        $this->assertTrue($bitcoin->isAuditNormal());

        $this->assertEquals(100, $bitcoin->getAuditNormalAmount());

        $bitcoin->setAudit3D(true);
        $this->assertTrue($bitcoin->isAudit3D());

        $bitcoin->setAudit3DAmount(200);
        $this->assertEquals(200, $bitcoin->getAudit3DAmount());

        $bitcoin->setAuditBattle(true);
        $this->assertTrue($bitcoin->isAuditBattle());

        $bitcoin->setAuditBattleAmount(300);
        $this->assertEquals(300, $bitcoin->getAuditBattleAmount());

        $bitcoin->setAuditVirtual(true);
        $this->assertTrue($bitcoin->isAuditVirtual());

        $bitcoin->setAuditVirtualAmount(400);
        $this->assertEquals(400, $bitcoin->getAuditVirtualAmount());

        $bitcoin->setAuditDiscountAmount(10);
        $this->assertEquals(10, $bitcoin->getAuditDiscountAmount());

        $bitcoin->setAuditLoosen(10);
        $this->assertEquals(10, $bitcoin->getAuditLoosen());

        $bitcoin->setAuditAdministrative(5);
        $this->assertEquals(5, $bitcoin->getAuditAdministrative());
    }
}
