<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\PaymentCharge;

/**
 * 基本入款設定
 *
 * @ORM\MappedSuperclass
 */
abstract class Deposit
{
    /**
     * 首次存款優惠
     */
    const FIRST = 1;

    /**
     * 每次存款優惠
     */
    const EACH = 2;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type = "integer", options = {"unsigned" = true})
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 存款優惠種類
     *
     * @var integer
     *
     * @ORM\Column(type = "smallint", options = {"unsigned" = true})
     */
    private $discount;

    /**
     * 放棄存款優惠
     *
     * @var boolean
     *
     * @ORM\Column(name = "discount_give_up", type = "boolean")
     */
    private $discountGiveUp;

    /**
     * 優惠標準(元)
     *
     * @var float
     *
     * @ORM\Column(name = "discount_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $discountAmount;

    /**
     * 優惠百分比(%)
     *
     * @var float
     *
     * @ORM\Column(name = "discount_percent", type = "decimal", precision = 5, scale = 2)
     */
    private $discountPercent;

    /**
     * 優惠係數
     *
     * @var integer
     *
     * @ORM\Column(name = "discount_factor", type = "smallint", options = {"unsigned" = true})
     */
    private $discountFactor;

    /**
     * 優惠上限金額
     *
     * @var float
     *
     * @ORM\Column(name = "discount_limit", type = "decimal", precision = 16, scale = 4)
     */
    private $discountLimit;

    /**
     * 最高存款金額
     *
     * @var float
     *
     * @ORM\Column(name = "deposit_max", type = "decimal", precision = 16, scale = 4)
     */
    private $depositMax;

    /**
     * 最低存款金額
     *
     * @var float
     *
     * @ORM\Column(name = "deposit_min", type = "decimal", precision = 16, scale = 4)
     */
    private $depositMin;

    /**
     * 真人額度稽核開關
     *
     * @var boolean
     *
     * @ORM\Column(name = "audit_live", type = "boolean")
     */
    private $auditLive;

    /**
     * 真人額度稽核
     *
     * @var float
     *
     * @ORM\Column(name = "audit_live_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $auditLiveAmount;

    /**
     * 球類額度稽核開關
     *
     * @var boolean
     *
     * @ORM\Column(name = "audit_ball", type = "boolean")
     */
    private $auditBall;

    /**
     * 球類額度稽核
     *
     * @var float
     *
     * @ORM\Column(name = "audit_ball_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $auditBallAmount;

    /**
     * 綜合額度稽核開關
     *
     * @var boolean
     *
     * @ORM\Column(name = "audit_complex", type = "boolean")
     */
    private $auditComplex;

    /**
     * 綜合額度稽核
     *
     * @var float
     *
     * @ORM\Column(name = "audit_complex_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $auditComplexAmount;

    /**
     * 常態性稽核開關
     *
     * @var boolean
     *
     * @ORM\Column(name = "audit_normal", type = "boolean")
     */
    private $auditNormal;

    /**
     * 常態性稽核(%)
     *
     * @var float
     *
     * @ORM\Column(name = "audit_normal_amount", type = "decimal", precision = 5, scale = 2)
     */
    private $auditNormalAmount;

    /**
     * 3D廳額度稽核開關
     *
     * @var boolean
     *
     * @ORM\Column(name = "audit_3d", type = "boolean")
     */
    private $audit3D;

    /**
     * 3D廳額度稽核
     *
     * @var float
     *
     * @ORM\Column(name = "audit_3d_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $audit3DAmount;

    /**
     * 對戰額度稽核開關
     *
     * @var boolean
     *
     * @ORM\Column(name = "audit_battle", type = "boolean")
     */
    private $auditBattle;

    /**
     * 對戰額度稽核
     *
     * @var float
     *
     * @ORM\Column(name = "audit_battle_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $auditBattleAmount;

    /**
     * 虛擬賽事額度稽核開關
     *
     * @var boolean
     *
     * @ORM\Column(name = "audit_virtual", type = "boolean")
     */
    private $auditVirtual;

    /**
     * 虛擬賽事額度稽核
     *
     * @var float
     *
     * @ORM\Column(name = "audit_virtual_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $auditVirtualAmount;

    /**
     * 優惠餘額稽核(%)
     *
     * @var float
     *
     * @ORM\Column(name = "audit_discount_amount", type = "decimal", precision = 5, scale = 2)
     */
    private $auditDiscountAmount;

    /**
     * 常態性稽核放寬額度
     *
     * @var float
     *
     * @ORM\Column(name = "audit_loosen", type = "decimal", precision = 16, scale = 4)
     */
    private $auditLoosen;

    /**
     * 常態性稽核行政費率(%)
     *
     * @var float
     *
     * @ORM\Column(name = "audit_administrative", type = "decimal", precision = 5, scale = 2)
     */
    private $auditAdministrative;

    /**
     * Optimistic locking
     * 供樂觀鎖驗證
     *
     * @var integer
     *
     * @ORM\Column(name = "version", type = "integer")
     * @ORM\Version
     */
    private $version;

    /**
     * @param PaymentCharge $paymentCharge 對應的線上付款設定
     * @param array $setting 預設設定值
     */
    public function __construct(PaymentCharge $paymentCharge, $setting)
    {
        $this->paymentCharge = $paymentCharge;
        $this->discount = $setting['discount'];
        $this->discountGiveUp = $setting['discount_give_up'];
        $this->discountAmount = $setting['discount_amount'];
        $this->discountPercent = $setting['discount_percent'];
        $this->discountFactor = $setting['discount_factor'];
        $this->discountLimit = $setting['discount_limit'];

        $this->depositMax = $setting['deposit_max'];
        $this->depositMin = $setting['deposit_min'];

        $this->auditLive = $setting['audit_live'];
        $this->auditLiveAmount = $setting['audit_live_amount'];
        $this->auditBall = $setting['audit_ball'];
        $this->auditBallAmount = $setting['audit_ball_amount'];
        $this->auditComplex = $setting['audit_complex'];
        $this->auditComplexAmount = $setting['audit_complex_amount'];
        $this->auditNormal = $setting['audit_normal'];
        $this->auditNormalAmount = 100;
        $this->audit3D = $setting['audit_3d'];
        $this->audit3DAmount = $setting['audit_3d_amount'];
        $this->auditBattle = $setting['audit_battle'];
        $this->auditBattleAmount = $setting['audit_battle_amount'];
        $this->auditVirtual = $setting['audit_virtual'];
        $this->auditVirtualAmount = $setting['audit_virtual_amount'];

        $this->auditDiscountAmount = $setting['audit_discount_amount'];
        $this->auditLoosen = $setting['audit_loosen'];
        $this->auditAdministrative = $setting['audit_administrative'];
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 設定存款優惠種類
     *
     * @param integer $discount
     * @return Deposit
     */
    public function setDiscount($discount)
    {
        if ($discount == self::FIRST) {
            $this->discount = self::FIRST;
        }

        if ($discount == self::EACH) {
            $this->discount = self::EACH;
        }

        return $this;
    }

    /**
     * 回傳存款優惠種類
     *
     * @return integer
     */
    public function getDiscount()
    {
        return $this->discount;
    }

    /**
     * 設定是否放棄存款優惠
     *
     * @param bool $bool
     * @return Deposit
     */
    public function setDiscountGiveUp($bool)
    {
        $this->discountGiveUp = (bool) $bool;

        return $this;
    }

    /**
     * 回傳是否放棄存款優惠
     *
     * @return boolean
     */
    public function isDiscountGiveUp()
    {
        return (bool) $this->discountGiveUp;
    }

    /**
     * 設定優惠標準(元)
     *
     * @param float $amount
     * @return Deposit
     */
    public function setDiscountAmount($amount)
    {
        $this->discountAmount = $amount;

        return $this;
    }

    /**
     * 回傳優惠標準(元)
     *
     * @return float
     */
    public function getDiscountAmount()
    {
        return $this->discountAmount;
    }

    /**
     * 設定優惠百分比(%)
     *
     * @param float $percent
     * @return Deposit
     */
    public function setDiscountPercent($percent)
    {
        $this->discountPercent = $percent;

        return $this;
    }

    /**
     * 回傳優惠百分比(%)
     *
     * @return float
     */
    public function getDiscountPercent()
    {
        return $this->discountPercent;
    }

    /**
     * 設定優惠係數
     *
     * @param integer $factor
     * @return Deposit
     */
    public function setDiscountFactor($factor)
    {
        $this->discountFactor = $factor;

        return $this;
    }

    /**
     * 回傳優惠係數
     *
     * @return integer
     */
    public function getDiscountFactor()
    {
        return $this->discountFactor;
    }

    /**
     * 設定優惠上限金額
     *
     * @param float $limit
     * @return Deposit
     */
    public function setDiscountLimit($limit)
    {
        $this->discountLimit = $limit;

        return $this;
    }

    /**
     * 回傳優惠上限金額
     *
     * @return float
     */
    public function getDiscountLimit()
    {
        return $this->discountLimit;
    }

    /**
     * 設定最高存款金額
     *
     * @param float $max
     * @return Deposit
     */
    public function setDepositMax($max)
    {
        $this->depositMax = $max;

        return $this;
    }

    /**
     * 回傳最高存款金額
     *
     * @return float
     */
    public function getDepositMax()
    {
        return $this->depositMax;
    }

    /**
     * 設定最低存款金額
     *
     * @param float $min
     * @return Deposit
     */
    public function setDepositMin($min)
    {
        $this->depositMin = $min;

        return $this;
    }

    /**
     * 回傳最低存款金額
     *
     * @return float
     */
    public function getDepositMin()
    {
        return $this->depositMin;
    }

    /**
     * 設定真人額度稽核
     *
     * @param bool $bool
     * @return Deposit
     */
    public function setAuditLive($bool)
    {
        $this->auditLive = (bool) $bool;

        return $this;
    }

    /**
     * 回傳真人額度稽核是否開啟
     *
     * @return boolean
     */
    public function isAuditLive()
    {
        return (bool) $this->auditLive;
    }

    /**
     * 設定真人額度稽核
     *
     * @param float $amount
     * @return Deposit
     */
    public function setAuditLiveAmount($amount)
    {
        $this->auditLiveAmount = $amount;

        return $this;
    }

    /**
     * 回傳真人額度稽核
     *
     * @return float
     */
    public function getAuditLiveAmount()
    {
        return $this->auditLiveAmount;
    }

    /**
     * 設定球類額度稽核
     *
     * @param bool $bool
     * @return Deposit
     */
    public function setAuditBall($bool)
    {
        $this->auditBall = (bool) $bool;

        return $this;
    }

    /**
     * 回傳球類額度稽核是否開啟
     *
     * @return boolean
     */
    public function isAuditBall()
    {
        return (bool) $this->auditBall;
    }

    /**
     * 設定球類額度稽核
     *
     * @param float $amount
     * @return Deposit
     */
    public function setAuditBallAmount($amount)
    {
        $this->auditBallAmount = $amount;

        return $this;
    }

    /**
     * 回傳球類額度稽核
     *
     * @return float
     */
    public function getAuditBallAmount()
    {
        return $this->auditBallAmount;
    }

    /**
     * 設定綜合額度稽核
     *
     * @param bool $bool
     * @return Deposit
     */
    public function setAuditComplex($bool)
    {
        $this->auditComplex = (bool) $bool;

        return $this;
    }

    /**
     * 回傳綜合額度稽核是否開啟
     *
     * @return boolean
     */
    public function isAuditComplex()
    {
        return (bool) $this->auditComplex;
    }

    /**
     * 設定綜合額度稽核
     *
     * @param float $amount
     * @return Deposit
     */
    public function setAuditComplexAmount($amount)
    {
        $this->auditComplexAmount = $amount;

        return $this;
    }

    /**
     * 回傳綜合額度稽核
     *
     * @return float
     */
    public function getAuditComplexAmount()
    {
        return $this->auditComplexAmount;
    }

    /**
     * 設定常態性稽核
     *
     * @param bool $bool
     * @return Deposit
     */
    public function setAuditNormal($bool)
    {
        $this->auditNormal = (bool) $bool;

        return $this;
    }

    /**
     * 回傳常態性稽核是否開啟
     *
     * @return boolean
     */
    public function isAuditNormal()
    {
        return (bool) $this->auditNormal;
    }

    /**
     * 回傳常態性稽核(%)
     *
     * @return float
     */
    public function getAuditNormalAmount()
    {
        return $this->auditNormalAmount;
    }

    /**
     * 設定3D廳稽核
     *
     * @param bool $bool
     * @return Deposit
     */
    public function setAudit3D($bool)
    {
        $this->audit3D = (bool) $bool;

        return $this;
    }

    /**
     * 回傳3D廳稽核是否開啟
     *
     * @return boolean
     */
    public function isAudit3D()
    {
        return (bool) $this->audit3D;
    }

    /**
     * 設定3D廳稽核(%)
     *
     * @param float $amount
     * @return Deposit
     */
    public function setAudit3DAmount($amount)
    {
        $this->audit3DAmount = $amount;

        return $this;
    }

    /**
     * 回傳3D廳稽核(%)
     *
     * @return float
     */
    public function getAudit3DAmount()
    {
        return $this->audit3DAmount;
    }

    /**
     * 設定對戰稽核
     *
     * @param bool $bool
     * @return Deposit
     */
    public function setAuditBattle($bool)
    {
        $this->auditBattle = (bool) $bool;

        return $this;
    }

    /**
     * 回傳對戰稽核是否開啟
     *
     * @return boolean
     */
    public function isAuditBattle()
    {
        return (bool) $this->auditBattle;
    }

    /**
     * 設定對戰稽核(%)
     *
     * @param float $amount
     * @return Deposit
     */
    public function setAuditBattleAmount($amount)
    {
        $this->auditBattleAmount = $amount;

        return $this;
    }

    /**
     * 回傳對戰稽核(%)
     *
     * @return float
     */
    public function getAuditBattleAmount()
    {
        return $this->auditBattleAmount;
    }

    /**
     * 設定虛擬賽事稽核
     *
     * @param bool $bool
     * @return Deposit
     */
    public function setAuditVirtual($bool)
    {
        $this->auditVirtual = (bool) $bool;

        return $this;
    }

    /**
     * 回傳虛擬賽事稽核是否開啟
     *
     * @return boolean
     */
    public function isAuditVirtual()
    {
        return (bool) $this->auditVirtual;
    }

    /**
     * 設定虛擬賽事稽核(%)
     *
     * @param float $amount
     * @return Deposit
     */
    public function setAuditVirtualAmount($amount)
    {
        $this->auditVirtualAmount = $amount;

        return $this;
    }

    /**
     * 回傳虛擬賽事稽核(%)
     *
     * @return float
     */
    public function getAuditVirtualAmount()
    {
        return $this->auditVirtualAmount;
    }

    /**
     * 設定優惠餘額稽核(%)
     *
     * @param float $amount
     * @return Deposit
     */
    public function setAuditDiscountAmount($amount)
    {
        $this->auditDiscountAmount = $amount;

        return $this;
    }

    /**
     * 回傳優惠餘額稽核(%)
     *
     * @return float
     */
    public function getAuditDiscountAmount()
    {
        return $this->auditDiscountAmount;
    }

    /**
     * 設定常態性稽核放寬額度
     *
     * @param float $amount
     * @return Deposit
     */
    public function setAuditLoosen($amount)
    {
        $this->auditLoosen = $amount;

        return $this;
    }

    /**
     * 回傳常態性稽核放寬額度
     *
     * @return float
     */
    public function getAuditLoosen()
    {
        return $this->auditLoosen;
    }

    /**
     * 設定常態性稽核行政費率(%)
     *
     * @param float $amount
     * @return Deposit
     */
    public function setAuditAdministrative($amount)
    {
        $this->auditAdministrative = $amount;

        return $this;
    }

    /**
     * 回傳常態性稽核行政費率(%)
     *
     * @return float
     */
    public function getAuditAdministrative()
    {
        return $this->auditAdministrative;
    }
}
