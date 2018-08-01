<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\PaymentCharge;

/**
 * 電子錢包
 *
 * @ORM\Entity
 * @ORM\Table(name = "deposit_mobile")
 */
class DepositMobile extends Deposit
{
    /**
     * 對應的付款設定
     *
     * @var PaymentCharge
     *
     * @ORM\OneToOne(targetEntity = "PaymentCharge", inversedBy = "depositMobile")
     * @ORM\JoinColumn(
     *      name = "payment_charge_id",
     *      referencedColumnName = "id",
     *      nullable = false
     * )
     */
    protected $paymentCharge;

    /**
     * @param PaymentCharge $paymentCharge 對應的付款設定
     */
    public function __construct(PaymentCharge $paymentCharge)
    {
        $setting['discount'] = self::FIRST;
        $setting['discount_give_up'] = false;
        $setting['discount_amount'] = 100;
        $setting['discount_percent'] = 0;
        $setting['discount_factor'] = 1;
        $setting['discount_limit'] = 0;

        $setting['deposit_max'] = 1000;
        $setting['deposit_min'] = 10;

        $setting['audit_live'] = true;
        $setting['audit_live_amount'] = 10;
        $setting['audit_ball'] = true;
        $setting['audit_ball_amount'] = 10;
        $setting['audit_complex'] = true;
        $setting['audit_complex_amount'] = 10;
        $setting['audit_normal'] = true;
        $setting['audit_3d'] = false;
        $setting['audit_3d_amount'] = 10;
        $setting['audit_battle'] = false;
        $setting['audit_battle_amount'] = 10;
        $setting['audit_virtual'] = false;
        $setting['audit_virtual_amount'] = 10;

        $setting['audit_discount_amount'] = 0;
        $setting['audit_loosen'] = 10;
        $setting['audit_administrative'] = 0;

        parent::__construct($paymentCharge, $setting);

        $paymentCharge->addDepositMobile($this);
    }

    /**
     * 回傳對應的付款設定
     *
     * @return PaymentCharge
     */
    public function getPaymentCharge()
    {
        return $this->paymentCharge;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'payment_charge_id' => $this->getPaymentCharge()->getId(),
            'discount' => $this->getDiscount(),
            'discount_give_up' => $this->isDiscountGiveUp(),
            'discount_amount' => $this->getDiscountAmount(),
            'discount_percent' => $this->getDiscountPercent(),
            'discount_factor' => $this->getDiscountFactor(),
            'discount_limit' => $this->getDiscountLimit(),
            'deposit_max' => $this->getDepositMax(),
            'deposit_min' => $this->getDepositMin(),
            'audit_live' => $this->isAuditLive(),
            'audit_live_amount' => $this->getAuditLiveAmount(),
            'audit_ball' => $this->isAuditBall(),
            'audit_ball_amount' => $this->getAuditBallAmount(),
            'audit_complex' => $this->isAuditComplex(),
            'audit_complex_amount' => $this->getAuditComplexAmount(),
            'audit_normal' => $this->isAuditNormal(),
            'audit_normal_amount' => $this->getAuditNormalAmount(),
            'audit_3d' => $this->isAudit3D(),
            'audit_3d_amount' => $this->getAudit3DAmount(),
            'audit_battle' => $this->isAuditBattle(),
            'audit_battle_amount' => $this->getAuditBattleAmount(),
            'audit_virtual' => $this->isAuditVirtual(),
            'audit_virtual_amount' => $this->getAuditVirtualAmount(),
            'audit_discount_amount' => $this->getAuditDiscountAmount(),
            'audit_loosen' => $this->getAuditLoosen(),
            'audit_administrative' => $this->getAuditAdministrative(),
        ];
    }
}
