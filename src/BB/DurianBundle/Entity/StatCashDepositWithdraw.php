<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 現金出入款統計
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\StatCashDepositWithdrawRepository")
 * @ORM\Table(
 *     name="stat_cash_deposit_withdraw",
 *     indexes = {
 *         @ORM\Index(name = "idx_stat_cash_deposit_withdraw_at_user_id", columns = {"at", "user_id"}),
 *         @ORM\Index(name = "idx_stat_cash_deposit_withdraw_domain_at", columns = {"domain", "at"})
 *     }
 * )
 *
 * @author Sweet 2014.10.30
 */
class StatCashDepositWithdraw
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer", options={"unsigned" = true})
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 統計日期
     *
     * @var \DateTime
     *
     * @ORM\Column(name="at", type="datetime")
     */
    private $at;

    /**
     * 使用者編號
     *
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer")
     */
    private $userId;

    /**
     * 幣別
     *
     * @var integer
     *
     * @ORM\Column(name="currency", type="smallint", options = {"unsigned" = true})
     */
    private $currency;

    /**
     * 廳
     *
     * @var integer
     *
     * @ORM\Column(name="domain", type="integer")
     */
    private $domain;

    /**
     * 上層ID
     *
     * @var integer
     *
     * @ORM\Column(name="parent_id", type="integer")
     */
    private $parentId;

    /**
     * 入款總金額
     *
     * @var float
     *
     * @ORM\Column(name="deposit_amount", type="decimal", precision = 16, scale = 4)
     */
    private $depositAmount;

    /**
     * 入款總次數
     *
     * @var integer
     *
     * @ORM\Column(name="deposit_count", type="integer")
     */
    private $depositCount;

    /**
     * 出款總金額
     *
     * @var float
     *
     * @ORM\Column(name="withdraw_amount", type="decimal", precision = 16, scale = 4)
     */
    private $withdrawAmount;

    /**
     * 出款總次數
     *
     * @var integer
     *
     * @ORM\Column(name="withdraw_count", type="integer")
     */
    private $withdrawCount;

    /**
     * 出入款總金額
     *
     * @var float
     *
     * @ORM\Column(name="deposit_withdraw_amount", type="decimal", precision = 16, scale = 4)
     */
    private $depositWithdrawAmount;

    /**
     * 出入款總次數
     *
     * @var integer
     *
     * @ORM\Column(name="deposit_withdraw_count", type="integer")
     */
    private $depositWithdrawCount;

    /**
     * 建構子
     *
     * @param \DateTime $at       統計日期
     * @param integer   $userId   使用者編號
     * @param integer   $currency 幣別
     */
    public function __construct($at, $userId, $currency)
    {
        $this->at = $at;
        $this->userId = $userId;
        $this->currency = $currency;
        $this->depositAmount = 0;
        $this->depositCount = 0;
        $this->withdrawAmount = 0;
        $this->withdrawCount = 0;
        $this->depositWithdrawAmount = 0;
        $this->depositWithdrawCount = 0;
    }

    /**
     * 回傳編號
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 設定編號
     * 僅用在測試
     *
     * @param integer $id
     * @return StatCashDepositWithdraw
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * 取得統計日期
     *
     * @return \DateTime
     */
    public function getAt()
    {
        return $this->at;
    }

    /**
     * 取得使用者編號
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * 設定幣別
     *
     * @param integer $currency
     * @return StatCashDepositWithdraw
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * 回傳幣別
     *
     * @return integer
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * 設定廳主
     *
     * @param integer $domain
     * @return StatCashDepositWithdraw
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * 回傳廳主
     *
     * @return integer
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * 設定上層ID
     *
     * @param integer $parentId
     * @return StatCashDepositWithdraw
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;

        return $this;
    }

    /**
     * 回傳上層ID
     *
     * @return integer
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * 設定入款總金額
     *
     * @param float $depositAmount
     * @return StatCashDepositWithdraw
     */
    public function setDepositAmount($depositAmount)
    {
        $this->depositAmount = $depositAmount;

        return $this;
    }

    /**
     * 新增入款總金額
     *
     * @param float $depositAmount
     * @return StatCashDepositWithdraw
     */
    public function addDepositAmount($depositAmount)
    {
        $this->depositAmount += $depositAmount;

        return $this;
    }

    /**
     * 回傳入款總金額
     *
     * @return float
     */
    public function getDepositAmount()
    {
        return $this->depositAmount;
    }

    /**
     * 設定入款總次數
     *
     * @param integer $depositCount
     * @return StatCashDepositWithdraw
     */
    public function setDepositCount($depositCount)
    {
        $this->depositCount = $depositCount;

        return $this;
    }

    /**
     * 新增入款總次數
     *
     * @param integer $depositCount
     * @return StatCashDepositWithdraw
     */
    public function addDepositCount($depositCount = 1)
    {
        $this->depositCount += $depositCount;

        return $this;
    }

    /**
     * 回傳入款總次數
     *
     * @return integer
     */
    public function getDepositCount()
    {
        return $this->depositCount;
    }

    /**
     * 設定出款總金額
     *
     * @param float $withdrawAmount
     * @return StatCashDepositWithdraw
     */
    public function setWithdrawAmount($withdrawAmount)
    {
        $this->withdrawAmount = $withdrawAmount;

        return $this;
    }

    /**
     * 新增出款總金額
     *
     * @param float $withdrawAmount
     * @return StatCashDepositWithdraw
     */
    public function addWithdrawAmount($withdrawAmount)
    {
        $this->withdrawAmount += $withdrawAmount;

        return $this;
    }

    /**
     * 回傳出款總金額
     *
     * @return float
     */
    public function getWithdrawAmount()
    {
        return $this->withdrawAmount;
    }

    /**
     * 設定出款總次數
     *
     * @param integer $withdrawCount
     * @return StatCashDepositWithdraw
     */
    public function setWithdrawCount($withdrawCount)
    {
        $this->withdrawCount = $withdrawCount;

        return $this;
    }

    /**
     * 新增出款總次數
     *
     * @param integer $withdrawCount
     * @return StatCashDepositWithdraw
     */
    public function addWithdrawCount($withdrawCount = 1)
    {
        $this->withdrawCount += $withdrawCount;

        return $this;
    }

    /**
     * 回傳出款總次數
     *
     * @return integer
     */
    public function getWithdrawCount()
    {
        return $this->withdrawCount;
    }

    /**
     * 設定出入款總金額
     *
     * @param float $depositWithdrawAmount
     * @return StatCashDepositWithdraw
     */
    public function setDepositWithdrawAmount($depositWithdrawAmount)
    {
        $this->depositWithdrawAmount = $depositWithdrawAmount;

        return $this;
    }

    /**
     * 新增出入款總金額
     *
     * @param float $depositWithdrawAmount
     * @return StatCashDepositWithdraw
     */
    public function addDepositWithdrawAmount($depositWithdrawAmount)
    {
        $this->depositWithdrawAmount += $depositWithdrawAmount;

        return $this;
    }

    /**
     * 回傳出入款總金額
     *
     * @return float
     */
    public function getDepositWithdrawAmount()
    {
        return $this->depositWithdrawAmount;
    }

    /**
     * 設定出入款總次數
     *
     * @param integer $depositWithdrawCount
     * @return StatCashDepositWithdraw
     */
    public function setDepositWithdrawCount($depositWithdrawCount)
    {
        $this->depositWithdrawCount = $depositWithdrawCount;

        return $this;
    }

    /**
     * 新增出入款總次數
     *
     * @param integer $depositWithdrawCount
     * @return StatCashDepositWithdraw
     */
    public function addDepositWithdrawCount($depositWithdrawCount = 1)
    {
        $this->depositWithdrawCount += $depositWithdrawCount;

        return $this;
    }

    /**
     * 回傳出入款總次數
     *
     * @return integer
     */
    public function getDepositWithdrawCount()
    {
        return $this->depositWithdrawCount;
    }
}
