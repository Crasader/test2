<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\PaymentCharge;
use BB\DurianBundle\Entity\DepositCompany;

class DepositCompanyTest extends DurianTestCase
{
    /**
     * 測試新增公司入款設定
     *
     */
    public function testNewDepositCompany()
    {
        $pc = new PaymentCharge(1, 2, 'gaga', false);
        $company = new DepositCompany($pc);

        $checkArray = [
            'id' => null,
            'payment_charge_id' => null,
            'discount' => DepositCompany::FIRST,
            'discount_give_up' => false,
            'discount_amount' => 1000,
            'discount_percent' => 15,
            'discount_factor' => 1,
            'discount_limit' => 10000,
            'other_discount_amount' => 100,
            'other_discount_percent' => 0,
            'other_discount_limit' => 0,
            'daily_discount_limit' => 0,
            'deposit_max' => 30000,
            'deposit_min' => 100,
            'audit_live' => false,
            'audit_live_amount' => 0,
            'audit_ball' => false,
            'audit_ball_amount' => 0,
            'audit_complex' => true,
            'audit_complex_amount' => 10,
            'audit_normal' => false,
            'audit_normal_amount' => 100,
            'audit_3d' => false,
            'audit_3d_amount' => 5,
            'audit_battle' => false,
            'audit_battle_amount' => 5,
            'audit_virtual' => false,
            'audit_virtual_amount' => 5,
            'audit_discount_amount' => 0,
            'audit_loosen' => 0,
            'audit_administrative' => 0,
            'deposit_sc_max' => 0,
            'deposit_sc_min' => 0,
            'deposit_co_max' => 0,
            'deposit_co_min' => 0,
            'deposit_sa_max' => 0,
            'deposit_sa_min' => 0,
            'deposit_ag_max' => 0,
            'deposit_ag_min' => 0,
        ];

        $this->assertEquals($pc, $company->getPaymentCharge());
        $this->assertEquals($checkArray, $company->toArray());

        // set method test
        $discount = DepositCompany::FIRST;
        $company->setDiscount($discount);
        $this->assertEquals($discount, $company->getDiscount());

        // set method test
        $discount = DepositCompany::EACH;
        $company->setDiscount($discount);
        $this->assertEquals($discount, $company->getDiscount());

        $company->setDiscountGiveUp(true);
        $this->assertTrue($company->isDiscountGiveUp());

        $amount = 55;
        $company->setDiscountAmount($amount);
        $this->assertEquals($amount, $company->getDiscountAmount());

        $percent = 5.378;
        $company->setDiscountPercent($percent);
        $this->assertEquals($percent, $company->getDiscountPercent());

        $factor = 16;
        $company->setDiscountFactor($factor);
        $this->assertEquals($factor, $company->getDiscountFactor());

        $limit = 555;
        $company->setDiscountLimit($limit);
        $this->assertEquals($limit, $company->getDiscountLimit());

        $otherAmount = 5555;
        $company->setOtherDiscountAmount($otherAmount);
        $this->assertEquals($otherAmount, $company->getOtherDiscountAmount());

        $otherPercent = 5.47895;
        $company->setOtherDiscountPercent($otherPercent);
        $this->assertEquals($otherPercent, $company->getOtherDiscountPercent());

        $otherLimit = 55;
        $company->setOtherDiscountLimit($otherLimit);
        $this->assertEquals($otherLimit, $company->getOtherDiscountLimit());

        $dailyLimit = 55;
        $company->setDailyDiscountLimit($dailyLimit);
        $this->assertEquals($dailyLimit, $company->getDailyDiscountLimit());

        $max = 9999999;
        $company->setDepositMax($max);
        $this->assertEquals($max, $company->getDepositMax());

        $min = 9;
        $company->setDepositMin($min);
        $this->assertEquals($min, $company->getDepositMin());

        $company->setAuditLive(true);
        $this->assertTrue($company->isAuditLive());

        $liveAmount = 7.77;
        $company->setAuditLiveAmount($liveAmount);
        $this->assertEquals($liveAmount, $company->getAuditLiveAmount());

        $company->setAuditBall(true);
        $this->assertTrue($company->isAuditBall());

        $ballAmount = 8.888;
        $company->setAuditBallAmount($ballAmount);
        $this->assertEquals($ballAmount, $company->getAuditBallAmount());

        $company->setAuditComplex(true);
        $this->assertTrue($company->isAuditComplex());

        $complexAmount = 9.999;
        $company->setAuditComplexAmount($complexAmount);
        $this->assertEquals($complexAmount, $company->getAuditComplexAmount());

        $company->setAuditNormal(true);
        $this->assertTrue($company->isAuditNormal());

        $this->assertEquals(100, $company->getAuditNormalAmount());

        $company->setAudit3D(true);
        $this->assertTrue($company->isAudit3D());

        $amount3D = 200;
        $company->setAudit3DAmount($amount3D);
        $this->assertEquals($amount3D, $company->getAudit3DAmount());

        $company->setAuditBattle(true);
        $this->assertTrue($company->isAuditBattle());

        $amountBattle = 300;
        $company->setAuditBattleAmount($amountBattle);
        $this->assertEquals($amountBattle, $company->getAuditBattleAmount());

        $company->setAuditVirtual(true);
        $this->assertTrue($company->isAuditVirtual());

        $amountVirtual= 400;
        $company->setAuditVirtualAmount($amountVirtual);
        $this->assertEquals($amountVirtual, $company->getAuditVirtualAmount());

        $adAmount = 5;
        $company->setAuditDiscountAmount($adAmount);
        $this->assertEquals($adAmount, $company->getAuditDiscountAmount());

        $antAmount = 5;
        $company->setAuditLoosen($antAmount);
        $this->assertEquals($antAmount, $company->getAuditLoosen());

        $anaAmount = 5;
        $company->setAuditAdministrative($anaAmount);
        $this->assertEquals($anaAmount, $company->getAuditAdministrative());

        $depositScMax = 500;
        $company->setDepositScMax($depositScMax);
        $this->assertEquals($depositScMax, $company->getDepositScMax());

        $depositScMin = 2;
        $company->setDepositScMin($depositScMin);
        $this->assertEquals($depositScMin, $company->getDepositScMin());

        $depositCoMax = 400;
        $company->setDepositCoMax($depositCoMax);
        $this->assertEquals($depositCoMax, $company->getDepositCoMax());

        $depositCoMin = 3;
        $company->setDepositCoMin($depositCoMin);
        $this->assertEquals($depositCoMin, $company->getDepositCoMin());

        $depositSaMax = 300;
        $company->setDepositSaMax($depositSaMax);
        $this->assertEquals($depositSaMax, $company->getDepositSaMax());

        $depositSaMin = 4;
        $company->setDepositSaMin($depositSaMin);
        $this->assertEquals($depositSaMin, $company->getDepositSaMin());

        $depositAgMax = 300;
        $company->setDepositAgMax($depositAgMax);
        $this->assertEquals($depositAgMax, $company->getDepositAgMax());

        $depositAgMin = 4;
        $company->setDepositAgMin($depositAgMin);
        $this->assertEquals($depositAgMin, $company->getDepositAgMin());
    }
}
