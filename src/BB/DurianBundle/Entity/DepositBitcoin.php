<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\PaymentCharge;

/**
 * 比特幣入款設定
 *
 * @ORM\Entity
 * @ORM\Table(name = "deposit_bitcoin")
 */
class DepositBitcoin extends Deposit
{
    /**
     * 對應的付款設定
     *
     * @var PaymentCharge
     *
     * @ORM\OneToOne(targetEntity = "PaymentCharge", inversedBy = "depositBitcoin")
     * @ORM\JoinColumn(
     *      name = "payment_charge_id",
     *      referencedColumnName = "id",
     *      nullable = false
     * )
     */
    protected $paymentCharge;

    /**
     * 比特幣入款手續費金額上限
     *
     * @var float
     *
     * @ORM\Column(name = "bitcoin_fee_max", type = "decimal", precision = 16, scale = 4)
     */
    private $bitcoinFeeMax = 100;

    /**
     * 比特幣入款手續費金額比例
     *
     * @var float
     *
     * @ORM\Column(name = "bitcoin_fee_percent", type = "decimal", precision = 5, scale = 2)
     */
    private $bitcoinFeePercent = 1;

    /**
     * @param PaymentCharge $paymentCharge 對應的付款設定
     */
    public function __construct(PaymentCharge $paymentCharge)
    {
        $setting['discount'] = self::FIRST;
        $setting['discount_give_up'] = false;
        $setting['discount_amount'] = 0;
        $setting['discount_percent'] = 0;
        $setting['discount_factor'] = 1;
        $setting['discount_limit'] = 0;

        $setting['deposit_max'] = 30000;
        $setting['deposit_min'] = 100;

        $setting['audit_live'] = false;
        $setting['audit_live_amount'] = 0;
        $setting['audit_ball'] = false;
        $setting['audit_ball_amount'] = 0;
        $setting['audit_complex'] = false;
        $setting['audit_complex_amount'] = 0;
        $setting['audit_normal'] = false;
        $setting['audit_3d'] = false;
        $setting['audit_3d_amount'] = 0;
        $setting['audit_battle'] = false;
        $setting['audit_battle_amount'] = 0;
        $setting['audit_virtual'] = false;
        $setting['audit_virtual_amount'] = 0;

        $setting['audit_discount_amount'] = 0;
        $setting['audit_loosen'] = 0;
        $setting['audit_administrative'] = 0;

        parent::__construct($paymentCharge, $setting);

        $this->bitcoinFeeMax = 100;
        $this->bitcoinFeePercent = 1;

        $paymentCharge->addDepositBitcoin($this);
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
     * 設定比特幣出款手續費最大值
     *
     * @param float $fee
     * @return DepositBitcoin
     */
    public function setBitcoinFeeMax($fee)
    {
        $this->bitcoinFeeMax = $fee;

        return $this;
    }

    /**
     * 回傳比特幣出款手續費最大值
     *
     * @return float
     */
    public function getBitcoinFeeMax()
    {
        return $this->bitcoinFeeMax;
    }

    /**
     * 設定比特幣出款手續費百分比(%)
     *
     * @param float $percent
     * @return DepositBitcoin
     */
    public function setBitcoinFeePercent($percent)
    {
        $this->bitcoinFeePercent = $percent;

        return $this;
    }

    /**
     * 回傳比特幣出款手續費百分比(%)
     *
     * @return float
     */
    public function getBitcoinFeePercent()
    {
        return $this->bitcoinFeePercent;
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
            'bitcoin_fee_max' => $this->getBitcoinFeeMax(),
            'bitcoin_fee_percent' => $this->getBitcoinFeePercent(),
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
