<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 現金 opcode 統計
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name="stat_cash_opcode",
 *     indexes = {
 *         @ORM\Index(name = "idx_stat_cash_opcode_at_user_id", columns = {"at", "user_id"}),
 *         @ORM\Index(name = "idx_stat_cash_opcode_domain_at", columns = {"domain", "at"}),
 *         @ORM\Index(name = "idx_stat_cash_opcode_opcode_at", columns = {"opcode", "at"})
 *     }
 * )
 *
 * @author Chuck 2014.09.30
 */
class StatCashOpcode
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
     * 交易代碼
     *
     * @var integer
     *
     * @ORM\Column(name="opcode", type="integer")
     */
    private $opcode;

    /**
     * 總金額
     *
     * @var string
     *
     * @ORM\Column(name="amount", type="decimal", precision = 16, scale = 4)
     */
    private $amount;

    /**
     * 總次數
     *
     * @var integer
     *
     * @ORM\Column(name="count", type="integer")
     */
    private $count;

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
        $this->amount = 0;
        $this->count = 0;
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
     * ps. 僅用在測試
     *
     * @param integer $id
     * @return StatCashOpcode
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
     * @return StatCashOpcode
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
     * @return StatCashOpcode
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
     * @return StatCashOpcode
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
     * 設定交易代碼
     *
     * @param integer $opcode
     * @return StatCashOpcode
     */
    public function setOpcode($opcode)
    {
        $this->opcode = $opcode;

        return $this;
    }

    /**
     * 回傳交易代碼
     *
     * @return integer
     */
    public function getOpcode()
    {
        return $this->opcode;
    }

    /**
     * 設定總金額
     *
     * @param float $amount
     * @return StatCashOpcode
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * 新增總金額
     *
     * @param float $amount
     * @return StatCashOpcode
     */
    public function addAmount($amount)
    {
        $this->amount += $amount;

        return $this;
    }

    /**
     * 回傳總金額
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * 設定總次數
     *
     * @param integer $count
     * @return StatCashOpcode
     */
    public function setCount($count)
    {
        $this->count = $count;

        return $this;
    }

    /**
     * 新增總次數
     *
     * @param integer $count
     * @return StatCashOpcode
     */
    public function addCount($count = 1)
    {
        $this->count += $count;

        return $this;
    }

    /**
     * 回傳總次數
     *
     * @return integer
     */
    public function getCount()
    {
        return $this->count;
    }
}
