<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\PaymentCharge;
use BB\DurianBundle\Entity\DepositOnline;

class DepositOnlineTest extends DurianTestCase
{
    /**
     * 測試新增線上存款設定
     */
    public function testNewDepositOnline()
    {
        $pc = new PaymentCharge(1, 2, 'gaga', false);
        $online = new DepositOnline($pc);

        $checkArray = array(
            'id' => null,
            'payment_charge_id' => null,
            'discount' => DepositOnline::FIRST,
            'discount_give_up' => false,
            'discount_amount' => 100,
            'discount_percent' => 0,
            'discount_factor' => 1,
            'discount_limit' => 0,
            'deposit_max' => 1000,
            'deposit_min' => 10,
            'audit_live' => true,
            'audit_live_amount' => 10,
            'audit_ball' => true,
            'audit_ball_amount' => 10,
            'audit_complex' => true,
            'audit_complex_amount' => 10,
            'audit_normal' => true,
            'audit_normal_amount' => 100,
            'audit_3d' => false,
            'audit_3d_amount' => 10,
            'audit_battle' => false,
            'audit_battle_amount' => 10,
            'audit_virtual' => false,
            'audit_virtual_amount' => 10,
            'audit_discount_amount' => 0,
            'audit_loosen' => 10,
            'audit_administrative' => 0,
        );

        $this->assertEquals($pc, $online->getPaymentCharge());
        $this->assertEquals($checkArray, $online->toArray());


        // set method test
        $discount = DepositOnline::EACH;
        $online->setDiscount($discount);
        $this->assertEquals($discount, $online->getDiscount());

        $online->setDiscountGiveUp(true);
        $this->assertTrue($online->isDiscountGiveUp());

        $amount = 51;
        $online->setDiscountAmount($amount);
        $this->assertEquals($amount, $online->getDiscountAmount());

        $percent = 10;
        $online->setDiscountPercent($percent);
        $this->assertEquals($percent, $online->getDiscountPercent());

        $factor = 4;
        $online->setDiscountFactor($factor);
        $this->assertEquals($factor, $online->getDiscountFactor());

        $limit = 1000;
        $online->setDiscountLimit($limit);
        $this->assertEquals($limit, $online->getDiscountLimit());

        $max = 5000;
        $online->setDepositMax($max);
        $this->assertEquals($max, $online->getDepositMax());

        $min = 10;
        $online->setDepositMin($min);
        $this->assertEquals($min, $online->getDepositMin());

        $online->setAuditLive(true);
        $this->assertTrue($online->isAuditLive());

        $liveAmount = 5;
        $online->setAuditLiveAmount($liveAmount);
        $this->assertEquals($liveAmount, $online->getAuditLiveAmount());

        $online->setAuditBall(true);
        $this->assertTrue($online->isAuditBall());

        $ballAmount = 10;
        $online->setAuditBallAmount($ballAmount);
        $this->assertEquals($ballAmount, $online->getAuditBallAmount());

        $online->setAuditComplex(true);
        $this->assertTrue($online->isAuditComplex());

        $complexAmount = 15;
        $online->setAuditComplexAmount($complexAmount);
        $this->assertEquals($complexAmount, $online->getAuditComplexAmount());

        $online->setAuditNormal(true);
        $this->assertTrue($online->isAuditNormal());

        $this->assertEquals(100, $online->getAuditNormalAmount());

        $online->setAudit3D(true);
        $this->assertTrue($online->isAudit3D());

        $amount3D = 200;
        $online->setAudit3DAmount($amount3D);
        $this->assertEquals($amount3D, $online->getAudit3DAmount());

        $online->setAuditBattle(true);
        $this->assertTrue($online->isAuditBattle());

        $amountBattle = 300;
        $online->setAuditBattleAmount($amountBattle);
        $this->assertEquals($amountBattle, $online->getAuditBattleAmount());

        $online->setAuditVirtual(true);
        $this->assertTrue($online->isAuditVirtual());

        $amountVirtual= 400;
        $online->setAuditVirtualAmount($amountVirtual);
        $this->assertEquals($amountVirtual, $online->getAuditVirtualAmount());

        $adAmount = 10;
        $online->setAuditDiscountAmount($adAmount);
        $this->assertEquals($adAmount, $online->getAuditDiscountAmount());

        $antAmount = 10;
        $online->setAuditLoosen($antAmount);
        $this->assertEquals($antAmount, $online->getAuditLoosen());

        $anaAmount = 5;
        $online->setAuditAdministrative($anaAmount);
        $this->assertEquals($anaAmount, $online->getAuditAdministrative());
    }
}
