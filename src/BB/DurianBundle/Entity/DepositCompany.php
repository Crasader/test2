<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\PaymentCharge;

/**
 * 公司入款
 * repositoryClass = "BB\DurianBundle\Repository\DepositCompanyRepository"
 *
 * @ORM\Entity
 * @ORM\Table(name = "deposit_company")
 */
class DepositCompany extends Deposit
{
    /**
     * 對應的付款設定
     *
     * @var PaymentCharge
     *
     * @ORM\OneToOne(targetEntity = "PaymentCharge", inversedBy = "depositCompany")
     * @ORM\JoinColumn(
     *      name = "payment_charge_id",
     *      referencedColumnName = "id",
     *      nullable = false
     * )
     */
    protected $paymentCharge;

    /**
     * 其他優惠標準(元)
     *
     * @var float
     *
     * @ORM\Column(name = "other_discount_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $otherDiscountAmount;

    /**
     * 其他優惠百分比(%)
     *
     * @var float
     *
     * @ORM\Column(name = "other_discount_percent", type = "decimal", precision = 5, scale = 2)
     */
    private $otherDiscountPercent;

    /**
     * 其他優惠上限
     *
     * @var float
     *
     * @ORM\Column(name = "other_discount_limit", type = "decimal", precision = 16, scale = 4)
     */
    private $otherDiscountLimit;

    /**
     * 單日優惠上限
     *
     * @var float
     *
     * @ORM\Column(name = "daily_discount_limit", type = "decimal", precision = 16, scale = 4)
     */
    private $dailyDiscountLimit;

    /**
     * 大股東最高存款金額
     *
     * @var float
     *
     * @ORM\Column(name = "deposit_sc_max", type = "decimal", precision = 16, scale = 4)
     */
    private $depositScMax;

    /**
     * 大股東最低存款金額
     *
     * @var float
     *
     * @ORM\Column(name = "deposit_sc_min", type = "decimal", precision = 16, scale = 4)
     */
    private $depositScMin = 0;

    /**
     * 股東最高存款金額
     *
     * @var float
     *
     * @ORM\Column(name = "deposit_co_max", type = "decimal", precision = 16, scale = 4)
     */
    private $depositCoMax = 100;

    /**
     * 股東最低存款金額
     *
     * @var float
     *
     * @ORM\Column(name = "deposit_co_min", type = "decimal", precision = 16, scale = 4)
     */
    private $depositCoMin = 0;

    /**
     * 總代理最高存款金額
     *
     * @var float
     *
     * @ORM\Column(name = "deposit_sa_max", type = "decimal", precision = 16, scale = 4)
     */
    private $depositSaMax = 100;

    /**
     * 總代理最低存款金額
     *
     * @var float
     *
     * @ORM\Column(name = "deposit_sa_min", type = "decimal", precision = 16, scale = 4)
     */
    private $depositSaMin = 0;

    /**
     * 代理最高存款金額
     *
     * @var float
     *
     * @ORM\Column(name = "deposit_ag_max", type = "decimal", precision = 16, scale = 4)
     */
    private $depositAgMax = 100;

    /**
     * 代理最低存款金額
     *
     * @var float
     *
     * @ORM\Column(name = "deposit_ag_min", type = "decimal", precision = 16, scale = 4)
     */
    private $depositAgMin = 0;

    /**
     * @param PaymentCharge $paymentCharge 對應的付款設定
     */
    public function __construct(PaymentCharge $paymentCharge)
    {
        $setting['discount'] = self::FIRST;
        $setting['discount_give_up'] = false;
        $setting['discount_amount'] = 1000;
        $setting['discount_percent'] = 15;
        $setting['discount_factor'] = 1;
        $setting['discount_limit'] = 10000;

        $setting['deposit_max'] = 30000;
        $setting['deposit_min'] = 100;

        $setting['audit_live'] = false;
        $setting['audit_live_amount'] = 0;
        $setting['audit_ball'] = false;
        $setting['audit_ball_amount'] = 0;
        $setting['audit_complex'] = true;
        $setting['audit_complex_amount'] = 10;
        $setting['audit_normal'] = false;
        $setting['audit_3d'] = false;
        $setting['audit_3d_amount'] = 5;
        $setting['audit_battle'] = false;
        $setting['audit_battle_amount'] = 5;
        $setting['audit_virtual'] = false;
        $setting['audit_virtual_amount'] = 5;

        $setting['audit_discount_amount'] = 0;
        $setting['audit_loosen'] = 0;
        $setting['audit_administrative'] = 0;

        parent::__construct($paymentCharge, $setting);

        $this->otherDiscountAmount  = 100;
        $this->otherDiscountPercent = 0;
        $this->otherDiscountLimit   = 0;
        $this->dailyDiscountLimit   = 0;

        $this->depositScMax = 0;
        $this->depositScMin = 0;
        $this->depositCoMax = 0;
        $this->depositCoMin = 0;
        $this->depositSaMax = 0;
        $this->depositSaMin = 0;
        $this->depositAgMax = 0;
        $this->depositAgMin = 0;

        $paymentCharge->addDepositCompany($this);
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
     * 設定其他優惠標準(元)
     *
     * @param float $amount
     * @return DepositCompany
     */
    public function setOtherDiscountAmount($amount)
    {
        $this->otherDiscountAmount = $amount;

        return $this;
    }

    /**
     * 回傳其他優惠標準(元)
     *
     * @return float
     */
    public function getOtherDiscountAmount()
    {
        return $this->otherDiscountAmount;
    }

    /**
     * 設定其他優惠百分比(%)
     *
     * @param float $percent
     * @return DepositCompany
     */
    public function setOtherDiscountPercent($percent)
    {
        $this->otherDiscountPercent = $percent;

        return $this;
    }

    /**
     * 回傳其他優惠百分比(%)
     *
     * @return float
     */
    public function getOtherDiscountPercent()
    {
        return $this->otherDiscountPercent;
    }

    /**
     * 設定其他優惠上限
     *
     * @param float $limit
     * @return DepositCompany
     */
    public function setOtherDiscountLimit($limit)
    {
        $this->otherDiscountLimit = $limit;

        return $this;
    }

    /**
     * 回傳其他優惠上限
     *
     * @return float
     */
    public function getOtherDiscountLimit()
    {
        return $this->otherDiscountLimit;
    }

    /**
     * 設定單日優惠上限
     *
     * @param float $limit
     * @return DepositCompany
     */
    public function setDailyDiscountLimit($limit)
    {
        $this->dailyDiscountLimit = $limit;

        return $this;
    }

    /**
     * 回傳單日優惠上限
     *
     * @return float
     */
    public function getDailyDiscountLimit()
    {
        return $this->dailyDiscountLimit;
    }

    /**
     * 取得大股東最高存款金額
     *
     * @return float
     */
    public function getDepositScMax()
    {
        return $this->depositScMax;
    }

    /**
     * 設定大股東最高存款金額
     *
     * @param float $depositScMax
     * @return DepositCompany
     */
    public function setDepositScMax($depositScMax)
    {
        $this->depositScMax = $depositScMax;

        return $this;
    }

    /**
     * 取得大股東最低存款金額
     *
     * @return float
     */
    public function getDepositScMin()
    {
        return $this->depositScMin;
    }

    /**
     * 設定大股東最低存款金額
     *
     * @param float $depositScMin
     * @return DepositCompony
     */
    public function setDepositScMin($depositScMin)
    {
        $this->depositScMin = $depositScMin;

        return $this;
    }

    /**
     * 取得股東最高存款金額
     *
     * @return float
     */
    public function getDepositCoMax()
    {
        return $this->depositCoMax;
    }

    /**
     * 設定股東最高存款金額
     *
     * @param float $depositCoMax
     * @return DepositCompony
     */
    public function setDepositCoMax($depositCoMax)
    {
        $this->depositCoMax = $depositCoMax;

        return $this;
    }

    /**
     * 取得股東最低存款金額
     *
     * @return float
     */
    public function getDepositCoMin()
    {
        return $this->depositCoMin;
    }

    /**
     * 設定股東最低存款金額
     *
     * @param float $depositCoMin
     * @return DepositCompony
     */
    public function setDepositCoMin($depositCoMin)
    {
        $this->depositCoMin = $depositCoMin;

        return $this;
    }

    /**
     * 取得股東最高存款金額
     *
     * @return float
     */
    public function getDepositSaMax()
    {
        return $this->depositSaMax;
    }

    /**
     * 設定股東最高存款金額
     *
     * @param float $depositSaMax
     * @return DepositCompony
     */
    public function setDepositSaMax($depositSaMax)
    {
        $this->depositSaMax = $depositSaMax;

        return $this;
    }

    /**
     * 取得股東最低存款金額
     *
     * @return float
     */
    public function getDepositSaMin()
    {
        return $this->depositSaMin;
    }

    /**
     * 設定股東最低存款金額
     *
     * @param float $depositSaMin
     * @return DepositCompony
     */
    public function setDepositSaMin($depositSaMin)
    {
        $this->depositSaMin = $depositSaMin;

        return $this;
    }

    /**
     * 取得股東最高存款金額
     *
     * @return float
     */
    public function getDepositAgMax()
    {
        return $this->depositAgMax;
    }

    /**
     * 設定股東最高存款金額
     *
     * @param float $depositAgMax
     * @return DepositCompony
     */
    public function setDepositAgMax($depositAgMax)
    {
        $this->depositAgMax = $depositAgMax;

        return $this;
    }

    /**
     * 取得股東最低存款金額
     *
     * @return float
     */
    public function getDepositAgMin()
    {
        return $this->depositAgMin;
    }

    /**
     * 設定股東最低存款金額
     *
     * @param float $depositAgMin
     * @return DepositCompony
     */
    public function setDepositAgMin($depositAgMin)
    {
        $this->depositAgMin = $depositAgMin;

        return $this;
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
            'other_discount_amount' => $this->getOtherDiscountAmount(),
            'other_discount_percent' => $this->getOtherDiscountPercent(),
            'other_discount_limit' => $this->getOtherDiscountLimit(),
            'daily_discount_limit' => $this->getDailyDiscountLimit(),
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
            'deposit_sc_max' => $this->getDepositScMax(),
            'deposit_sc_min' => $this->getDepositScMin(),
            'deposit_co_max' => $this->getDepositCoMax(),
            'deposit_co_min' => $this->getDepositCoMin(),
            'deposit_sa_max' => $this->getDepositSaMax(),
            'deposit_sa_min' => $this->getDepositSaMin(),
            'deposit_ag_max' => $this->getDepositAgMax(),
            'deposit_ag_min' => $this->getDepositAgMin(),
        ];
    }
}
