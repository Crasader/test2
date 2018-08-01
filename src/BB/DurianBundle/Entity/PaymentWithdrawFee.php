<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\PaymentCharge;

/**
 * 線上出款手續費
 *
 * @ORM\Entity
 * @ORM\Table(name = "payment_withdraw_fee")
 */
class PaymentWithdrawFee
{
    /**
     * 線上支付設定
     *
     * @var PaymentCharge
     *
     * @ORM\Id
     * @ORM\OneToOne(targetEntity = "PaymentCharge")
     * @ORM\JoinColumn(
     *      name = "payment_charge_id",
     *      referencedColumnName = "id",
     *      nullable = false
     * )
     */
    private $paymentCharge;

    /**
     * 重覆出款時數
     *
     * @var integer
     *
     * @ORM\Column(name = "free_period", type = "smallint")
     */
    private $freePeriod = 24;

    /**
     * 免收手續費次數
     *
     * @var integer
     *
     * @ORM\Column(name = "free_count", type = "smallint")
     */
    private $freeCount = 1;

    /**
     * 出款手續費金額上限
     *
     * @var float
     *
     * @ORM\Column(name = "amount_max", type = "decimal", precision = 16, scale = 4)
     */
    private $amountMax = 50;

    /**
     * 出款手續費金額比例
     *
     * @var float
     *
     * @ORM\Column(name = "amount_percent", type = "decimal", precision = 5, scale = 2)
     */
    private $amountPercent = 1;

    /**
     * 出款上限
     *
     * @var float
     *
     * @ORM\Column(name = "withdraw_max", type = "decimal", precision = 16, scale = 4)
     */
    private $withdrawMax = 50000;

    /**
     * 出款下限
     *
     * @var float
     *
     * @ORM\Column(name = "withdraw_min", type = "decimal", precision = 16, scale = 4)
     */
    private $withdrawMin = 100;

    /**
     * 電子錢包重覆出款時數
     *
     * @var integer
     *
     * @ORM\Column(name = "mobile_free_period", type = "smallint")
     */
    private $mobileFreePeriod = 24;

    /**
     * 電子錢包免收手續費次數
     *
     * @var integer
     *
     * @ORM\Column(name = "mobile_free_count", type = "smallint")
     */
    private $mobileFreeCount = 1;

    /**
     * 電子錢包出款手續費金額上限
     *
     * @var float
     *
     * @ORM\Column(name = "mobile_amount_max", type = "decimal", precision = 16, scale = 4)
     */
    private $mobileAmountMax = 50;

    /**
     * 電子錢包出款手續費金額比例
     *
     * @var float
     *
     * @ORM\Column(name = "mobile_amount_percent", type = "decimal", precision = 5, scale = 2)
     */
    private $mobileAmountPercent = 1;

    /**
     * 電子錢包出款上限
     *
     * @var float
     *
     * @ORM\Column(name = "mobile_withdraw_max", type = "decimal", precision = 16, scale = 4)
     */
    private $mobileWithdrawMax = 50000;

    /**
     * 電子錢包出款下限
     *
     * @var float
     *
     * @ORM\Column(name = "mobile_withdraw_min", type = "decimal", precision = 16, scale = 4)
     */
    private $mobileWithdrawMin = 100;

    /**
     * 比特幣重覆出款時數
     *
     * @var integer
     *
     * @ORM\Column(name = "bitcoin_free_period", type = "smallint")
     */
    private $bitcoinFreePeriod = 24;

    /**
     * 比特幣免收手續費次數
     *
     * @var integer
     *
     * @ORM\Column(name = "bitcoin_free_count", type = "smallint")
     */
    private $bitcoinFreeCount = 0;

    /**
     * 比特幣出款手續費金額上限
     *
     * @var float
     *
     * @ORM\Column(name = "bitcoin_amount_max", type = "decimal", precision = 16, scale = 4)
     */
    private $bitcoinAmountMax = 50;

    /**
     * 比特幣出款手續費金額比例
     *
     * @var float
     *
     * @ORM\Column(name = "bitcoin_amount_percent", type = "decimal", precision = 5, scale = 2)
     */
    private $bitcoinAmountPercent = 1;

    /**
     * 比特幣出款上限
     *
     * @var float
     *
     * @ORM\Column(name = "bitcoin_withdraw_max", type = "decimal", precision = 16, scale = 4)
     */
    private $bitcoinWithdrawMax = 50000;

    /**
     * 比特幣出款下限
     *
     * @var float
     *
     * @ORM\Column(name = "bitcoin_withdraw_min", type = "decimal", precision = 16, scale = 4)
     */
    private $bitcoinWithdrawMin = 100;

    /**
     * 帳號更換提示
     *
     * @var boolean
     *
     * @ORM\Column(name = "account_replacement_tips", type = "boolean", options = {"default" = false})
     */
    private $accountReplacementTips;

    /**
     * 帳號提示時間間隔
     *
     * @var integer
     *
     * @ORM\Column(name = "account_tips_interval", type = "smallint", options = {"default" = 1})
     */
    private $accountTipsInterval = 1;

    /**
     * @param PaymentCharge $paymentCharge
     */
    public function __construct(PaymentCharge $paymentCharge)
    {
        $this->paymentCharge = $paymentCharge;
        $this->accountReplacementTips = false;
        $this->accountTipsInterval = 1;
    }

    /**
     * 取得線上支付設定
     *
     * @return PaymentCharge
     */
    public function getPaymentCharge()
    {
        return $this->paymentCharge;
    }

    /**
     * 回傳重覆出款時數
     *
     * @return integer
     */
    public function getFreePeriod()
    {
        return $this->freePeriod;
    }

    /**
     * 設定重覆出款時數
     *
     * @param integer $freePeriod
     * @return PaymentWithdrawFee
     */
    public function setFreePeriod($freePeriod)
    {
        $this->freePeriod = $freePeriod;

        return $this;
    }

    /**
     * 取得免收手續費次數
     *
     * @return integer
     */
    public function getFreeCount()
    {
        return $this->freeCount;
    }

    /**
     * 設定免收手續費次數
     *
     * @param integer $freeCount
     * @return PaymentWithdrawFee
     */
    public function setFreeCount($freeCount)
    {
        $this->freeCount = $freeCount;

        return $this;
    }

    /**
     * 取得出款手續費金額上限
     *
     * @return float
     */
    public function getAmountMax()
    {
        return $this->amountMax;
    }

    /**
     * 設定出款手續費金額上限
     *
     * @param integer $amountMax
     * @return PaymentWithdrawFee
     */
    public function setAmountMax($amountMax)
    {
        $this->amountMax = $amountMax;

        return $this;
    }

    /**
     * 取得出款手續費金額比例
     *
     * @return float
     */
    public function getAmountPercent()
    {
        return $this->amountPercent;
    }

    /**
     * 設定出款手續費金額比例
     *
     * @param integer $amountPercent
     * @return PaymentWithdrawFee
     */
    public function setAmountPercent($amountPercent)
    {
        $this->amountPercent = $amountPercent;

        return $this;
    }

    /**
     * 取得出款上限
     *
     * @return float
     */
    public function getWithdrawMax()
    {
        return $this->withdrawMax;
    }

    /**
     * 取得出款下限
     *
     * @return float
     */
    public function getWithdrawMin()
    {
        return $this->withdrawMin;
    }

    /**
     * 設定出款上限
     *
     * @param float $withdrawMax
     * @return PaymentWithdrawFee
     */
    public function setWithdrawMax($withdrawMax)
    {
        $this->withdrawMax = $withdrawMax;

        return $this;
    }

    /**
     * 設定出款下限
     *
     * @param float $withdrawMin
     * @return PaymentWithdrawFee
     */
    public function setWithdrawMin($withdrawMin)
    {
        $this->withdrawMin = $withdrawMin;

        return $this;
    }

    /**
     * 取得電子錢包重覆出款時數
     *
     * @return integer
     */
    public function getMobileFreePeriod()
    {
        return $this->mobileFreePeriod;
    }

    /**
     * 設定電子錢包重覆出款時數
     *
     * @param integer $mobileFreePeriod
     * @return PaymentWithdrawFee
     */
    public function setMobileFreePeriod($mobileFreePeriod)
    {
        $this->mobileFreePeriod = $mobileFreePeriod;

        return $this;
    }

    /**
     * 取得電子錢包免收手續費次數
     *
     * @return integer
     */
    public function getMobileFreeCount()
    {
        return $this->mobileFreeCount;
    }

    /**
     * 設定電子錢包免收手續費次數
     *
     * @param integer $mobileFreeCount
     * @return PaymentWithdrawFee
     */
    public function setMobileFreeCount($mobileFreeCount)
    {
        $this->mobileFreeCount = $mobileFreeCount;

        return $this;
    }

    /**
     * 取得電子錢包出款手續費金額上限
     *
     * @return float
     */
    public function getMobileAmountMax()
    {
        return $this->mobileAmountMax;
    }

    /**
     * 設定電子錢包出款手續費金額上限
     *
     * @param integer $mobileAmountMax
     * @return PaymentWithdrawFee
     */
    public function setMobileAmountMax($mobileAmountMax)
    {
        $this->mobileAmountMax = $mobileAmountMax;

        return $this;
    }

    /**
     * 取得電子錢包出款手續費金額比例
     *
     * @return float
     */
    public function getMobileAmountPercent()
    {
        return $this->mobileAmountPercent;
    }

    /**
     * 設定電子錢包出款手續費金額比例
     *
     * @param float $mobileAmountPercent
     * @return PaymentWithdrawFee
     */
    public function setMobileAmountPercent($mobileAmountPercent)
    {
        $this->mobileAmountPercent = $mobileAmountPercent;

        return $this;
    }

    /**
     * 取得電子錢包出款上限
     *
     * @return float
     */
    public function getMobileWithdrawMax()
    {
        return $this->mobileWithdrawMax;
    }

    /**
     * 取得電子錢包出款下限
     *
     * @return float
     */
    public function getMobileWithdrawMin()
    {
        return $this->mobileWithdrawMin;
    }

    /**
     * 設定電子錢包出款上限
     *
     * @param float $mobileWithdrawMax
     * @return PaymentWithdrawFee
     */
    public function setMobileWithdrawMax($mobileWithdrawMax)
    {
        $this->mobileWithdrawMax = $mobileWithdrawMax;

        return $this;
    }

    /**
     * 設定電子錢包出款下限
     *
     * @param float $mobileWithdrawMin
     * @return PaymentWithdrawFee
     */
    public function setMobileWithdrawMin($mobileWithdrawMin)
    {
        $this->mobileWithdrawMin = $mobileWithdrawMin;

        return $this;
    }

    /**
     * 取得比特幣重覆出款時數
     *
     * @return integer
     */
    public function getBitcoinFreePeriod()
    {
        return $this->bitcoinFreePeriod;
    }

    /**
     * 設定比特幣重覆出款時數
     *
     * @param integer $bitcoinFreePeriod
     * @return PaymentWithdrawFee
     */
    public function setBitcoinFreePeriod($bitcoinFreePeriod)
    {
        $this->bitcoinFreePeriod = $bitcoinFreePeriod;

        return $this;
    }

    /**
     * 取得比特幣免收手續費次數
     *
     * @return integer
     */
    public function getBitcoinFreeCount()
    {
        return $this->bitcoinFreeCount;
    }

    /**
     * 設定比特幣免收手續費次數
     *
     * @param integer $bitcoinFreeCount
     * @return PaymentWithdrawFee
     */
    public function setBitcoinFreeCount($bitcoinFreeCount)
    {
        $this->bitcoinFreeCount = $bitcoinFreeCount;

        return $this;
    }

    /**
     * 取得比特幣出款手續費金額上限
     *
     * @return float
     */
    public function getBitcoinAmountMax()
    {
        return $this->bitcoinAmountMax;
    }

    /**
     * 設定比特幣出款手續費金額上限
     *
     * @param integer $bitcoinAmountMax
     * @return PaymentWithdrawFee
     */
    public function setBitcoinAmountMax($bitcoinAmountMax)
    {
        $this->bitcoinAmountMax = $bitcoinAmountMax;

        return $this;
    }

    /**
     * 取得比特幣出款手續費金額比例
     *
     * @return float
     */
    public function getBitcoinAmountPercent()
    {
        return $this->bitcoinAmountPercent;
    }

    /**
     * 設定比特幣出款手續費金額比例
     *
     * @param float $bitcoinAmountPercent
     * @return PaymentWithdrawFee
     */
    public function setBitcoinAmountPercent($bitcoinAmountPercent)
    {
        $this->bitcoinAmountPercent = $bitcoinAmountPercent;

        return $this;
    }

    /**
     * 取得比特幣出款上限
     *
     * @return float
     */
    public function getBitcoinWithdrawMax()
    {
        return $this->bitcoinWithdrawMax;
    }

    /**
     * 取得比特幣出款下限
     *
     * @return float
     */
    public function getBitcoinWithdrawMin()
    {
        return $this->bitcoinWithdrawMin;
    }

    /**
     * 設定比特幣出款上限
     *
     * @param float $bitcoinWithdrawMax
     * @return PaymentWithdrawFee
     */
    public function setBitcoinWithdrawMax($bitcoinWithdrawMax)
    {
        $this->bitcoinWithdrawMax = $bitcoinWithdrawMax;

        return $this;
    }

    /**
     * 設定比特幣出款下限
     *
     * @param float $bitcoinWithdrawMin
     * @return PaymentWithdrawFee
     */
    public function setBitcoinWithdrawMin($bitcoinWithdrawMin)
    {
        $this->bitcoinWithdrawMin = $bitcoinWithdrawMin;

        return $this;
    }

    /**
     * 設定出款銀行帳號更換提示
     *
     * @return PaymentWithdrawFee
     */
    public function setAccountReplacementTips($accountReplacementTips)
    {
        $this->accountReplacementTips = $accountReplacementTips;

        return $this;
    }

    /**
     * 回傳是否開啟出款銀行帳號更換提示
     *
     * @return boolean
     */
    public function isAccountReplacementTips()
    {
        return (bool) $this->accountReplacementTips;
    }

    /**
     * 回傳帳號提示時間間隔
     *
     * @return integer
     */
    public function getAccountTipsInterval()
    {
        return $this->accountTipsInterval;
    }

    /**
     * 設定帳號提示時間間隔
     *
     * @param integer $accountTipsInterval
     * @return PaymentWithdrawFee
     */
    public function setAccountTipsInterval($accountTipsInterval)
    {
        $this->accountTipsInterval = $accountTipsInterval;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'payment_charge_id' => $this->getPaymentCharge()->getId(),
            'free_period' => $this->getFreePeriod(),
            'free_count' => $this->getFreeCount(),
            'amount_max' => $this->getAmountMax(),
            'amount_percent' => $this->getAmountPercent(),
            'withdraw_max' => $this->getWithdrawMax(),
            'withdraw_min' => $this->getWithdrawMin(),
            'mobile_free_period' => $this->getMobileFreePeriod(),
            'mobile_free_count' => $this->getMobileFreeCount(),
            'mobile_amount_max' => $this->getMobileAmountMax(),
            'mobile_amount_percent' => $this->getMobileAmountPercent(),
            'mobile_withdraw_max' => $this->getMobileWithdrawMax(),
            'mobile_withdraw_min' => $this->getMobileWithdrawMin(),
            'bitcoin_free_period' => $this->getBitcoinFreePeriod(),
            'bitcoin_free_count' => $this->getBitcoinFreeCount(),
            'bitcoin_amount_max' => $this->getBitcoinAmountMax(),
            'bitcoin_amount_percent' => $this->getBitcoinAmountPercent(),
            'bitcoin_withdraw_max' => $this->getBitcoinWithdrawMax(),
            'bitcoin_withdraw_min' => $this->getBitcoinWithdrawMin(),
            'account_replacement_tips' => $this->isAccountReplacementTips(),
            'account_tips_interval' => $this->getAccountTipsInterval(),
        ];
    }
}
