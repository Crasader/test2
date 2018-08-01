<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\PaymentCharge;
use BB\DurianBundle\Entity\PaymentWithdrawFee;

/**
 * 取款審核
 *
 * @ORM\Entity
 * @ORM\Table(name = "payment_withdraw_verify")
 */
class PaymentWithdrawVerify
{
    /**
     * 線上支付設定
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\OneToOne(targetEntity = "PaymentCharge")
     * @ORM\JoinColumn(
     *     name = "payment_charge_id",
     *     referencedColumnName = "id",
     *     nullable = false
     * )
     */
    private $paymentCharge;

    /**
     * 是否需要審核
     *
     * @var boolean
     *
     * @ORM\Column(name = "need_verify",type = "boolean")
     */
    private $needVerify = true;

    /**
     * 審核所需時數
     *
     * @var integer
     *
     * @ORM\Column(name = "verify_time", type = "smallint", options = {"unsigned" = true})
     */
    private $verifyTime = 24;

    /**
     * 審核金額，超過才需要審核
     *
     * @var float
     *
     * @ORM\Column(name = "verify_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $verifyAmount = 5000;

    /**
     * @param PaymentCharge $paymentCharge
     */
    public function __construct(PaymentCharge $paymentCharge)
    {
        $this->paymentCharge = $paymentCharge;
    }

    /**
     * 取得paymentCharge
     *
     * @return paymentCharge
     */
    public function getPaymentCharge()
    {
        return $this->paymentCharge;
    }

    /**
     * 取得是否需要審核
     *
     * @return boolean
     */
    public function isNeedVerify()
    {
        return $this->needVerify;
    }

    /**
     * 設定是否需要審核
     *
     * @param boolean $needVerify
     *
     * @return PaymentWithdrawVerify
     */
    public function setNeedVerify($needVerify)
    {
        $this->needVerify = $needVerify;

        return $this;
    }

    /**
     * 取得審核需要的時間
     *
     * @return integer
     */
    public function getVerifyTime()
    {
        return $this->verifyTime;
    }

    /**
     * 設定審核需要的時間
     *
     * @param integer $verifyTime
     * @return PaymentWithdrawVerify
     */
    public function setVerifyTime($verifyTime)
    {
        $this->verifyTime = $verifyTime;

        return $this;
    }

    /**
     * 取得審核金額
     *
     * @return integer
     */
    public function getVerifyAmount()
    {
        return $this->verifyAmount;
    }

    /**
     * 設定審核金額
     *
     * @param integer $verifyAmount
     * @return PaymentWithdrawFee
     */
    public function setVerifyAmount($verifyAmount)
    {
        $this->verifyAmount = $verifyAmount;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array(
            'payment_charge_id' => $this->getPaymentCharge()->getId(),
            'need_verify'       => $this->isNeedVerify(),
            'verify_time'       => $this->getVerifyTime(),
            'verify_amount'     => $this->getVerifyAmount()
        );
    }
}
