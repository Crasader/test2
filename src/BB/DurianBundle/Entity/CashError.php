<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 現金額度不符
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\CashErrorRepository")
 * @ORM\Table(name = "cash_error")
 */
class CashError
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 現金id
     *
     * @var integer
     *
     * @ORM\Column(name = "cash_id", type = "integer")
     */
    private $cashId;

    /**
     * 使用者Id
     *
     * @var integer
     *
     * @ORM\Column(name = "user_id", type = "integer")
     */
    private $userId;

    /**
     * 幣別
     *
     * @var integer
     *
     * @ORM\Column(type = "smallint", options = {"unsigned" = true})
     */
    private $currency;

    /**
     * 交易餘額
     *
     * @var float
     *
     * @ORM\Column(name = "balance", type = "decimal", precision = 16, scale = 4)
     */
    protected $balance;

    /**
     * 額度總和
     *
     * @var float
     *
     * @ORM\Column(name = "total_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $totalAmount;

    /**
     * 更新額度不符名單的背景程式的上次執行時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "at", type = "datetime")
     */
    private $at;

    /**
     * 設定id
     *
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * 取得id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 設定現金id
     *
     * @param int $id
     */
    public function setCashId($id)
    {
        $this->cashId = $id;
    }

    /**
     * 取得cash id
     *
     * @return int
     */
    public function getCashId()
    {
        return $this->cashId;
    }

    /**
     * 取得userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * 設定userId
     *
     * @param integer $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * 取得幣別
     *
     * @return integer
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * 設定幣別
     *
     * @param integer $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * 設定餘額
     *
     * @param float $balance
     */
    public function setBalance($balance)
    {
        $this->balance = $balance;
    }

    /**
     * 設定明細額度總和
     *
     * @param float $amount
     */
    public function setTotalAmount($amount)
    {
        $this->totalAmount = $amount;
    }

    /**
     * 取得明細額度總和
     *
     * @return float
     */
    public function getTotalAmount()
    {
        return $this->totalAmount;
    }

    /**
     * 取得餘額
     *
     * @return float
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * 設定額度不符背景程式開始執行的時間
     *
     * @param \DateTime $date
     */
    public function setAt($date)
    {
        $this->at = $date;
    }
}
