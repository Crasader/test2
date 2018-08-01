<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\PaymentCharge;
use BB\DurianBundle\Entity\DepositMobile;

class DepositMobileTest extends DurianTestCase
{
    /**
     * 測試新增電子錢包設定
     */
    public function testNewDepositMobile()
    {
        $pc = new PaymentCharge(1, 2, 'gaga', false);
        $mobile = new DepositMobile($pc);

        $checkArray = [
            'id' => null,
            'payment_charge_id' => null,
            'discount' => DepositMobile::FIRST,
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
        ];

        $this->assertEquals($pc, $mobile->getPaymentCharge());
        $this->assertEquals($checkArray, $mobile->toArray());

        // set method test
        $mobile->setDiscount(DepositMobile::EACH);
        $this->assertEquals(DepositMobile::EACH, $mobile->getDiscount());

        $mobile->setDiscountGiveUp(true);
        $this->assertTrue($mobile->isDiscountGiveUp());

        $mobile->setDiscountAmount(51);
        $this->assertEquals(51, $mobile->getDiscountAmount());

        $mobile->setDiscountPercent(10);
        $this->assertEquals(10, $mobile->getDiscountPercent());

        $mobile->setDiscountFactor(4);
        $this->assertEquals(4, $mobile->getDiscountFactor());

        $mobile->setDiscountLimit(1000);
        $this->assertEquals(1000, $mobile->getDiscountLimit());

        $mobile->setDepositMax(5000);
        $this->assertEquals(5000, $mobile->getDepositMax());

        $mobile->setDepositMin(10);
        $this->assertEquals(10, $mobile->getDepositMin());

        $mobile->setAuditLive(true);
        $this->assertTrue($mobile->isAuditLive());

        $mobile->setAuditLiveAmount(5);
        $this->assertEquals(5, $mobile->getAuditLiveAmount());

        $mobile->setAuditBall(true);
        $this->assertTrue($mobile->isAuditBall());

        $mobile->setAuditBallAmount(10);
        $this->assertEquals(10, $mobile->getAuditBallAmount());

        $mobile->setAuditComplex(true);
        $this->assertTrue($mobile->isAuditComplex());

        $mobile->setAuditComplexAmount(15);
        $this->assertEquals(15, $mobile->getAuditComplexAmount());

        $mobile->setAuditNormal(true);
        $this->assertTrue($mobile->isAuditNormal());

        $this->assertEquals(100, $mobile->getAuditNormalAmount());

        $mobile->setAudit3D(true);
        $this->assertTrue($mobile->isAudit3D());

        $mobile->setAudit3DAmount(200);
        $this->assertEquals(200, $mobile->getAudit3DAmount());

        $mobile->setAuditBattle(true);
        $this->assertTrue($mobile->isAuditBattle());

        $mobile->setAuditBattleAmount(300);
        $this->assertEquals(300, $mobile->getAuditBattleAmount());

        $mobile->setAuditVirtual(true);
        $this->assertTrue($mobile->isAuditVirtual());

        $mobile->setAuditVirtualAmount(400);
        $this->assertEquals(400, $mobile->getAuditVirtualAmount());

        $mobile->setAuditDiscountAmount(10);
        $this->assertEquals(10, $mobile->getAuditDiscountAmount());

        $mobile->setAuditLoosen(10);
        $this->assertEquals(10, $mobile->getAuditLoosen());

        $mobile->setAuditAdministrative(5);
        $this->assertEquals(5, $mobile->getAuditAdministrative());
    }
}
