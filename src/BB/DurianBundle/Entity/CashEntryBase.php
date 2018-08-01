<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\CashEntry;

/**
 * 基本現金交易記錄
 *
 * @ORM\MappedSuperclass
 */
abstract class CashEntryBase
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "bigint")
     */
    private $id;

    /**
     * 使用者Id
     *
     * @var integer
     *
     * @ORM\Column(name = "user_id", type = "integer")
     */
    protected $userId;

    /**
     * 幣別
     *
     * @var integer
     *
     * @ORM\Column(type = "smallint", options = {"unsigned" = true})
     */
    protected $currency;

    /**
     * 交易代碼
     *
     * @var integer
     *
     * @ORM\Column(name = "opcode", type = "integer")
     */
    private $opcode;

    /**
     * 建立時間(新)
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "at", type = "bigint")
     */
    private $at;

    /**
     * 建立時間(舊)
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "created_at", type = "datetime")
     */
    private $createdAt;

    /**
     * 交易金額
     *
     * @var float
     *
     * @ORM\Column(name = "amount", type = "decimal", precision = 16, scale = 4)
     */
    private $amount;

    /**
     * 交易餘額
     *
     * @var float
     *
     * @ORM\Column(name = "balance", type = "decimal", precision = 16, scale = 4)
     */
    protected $balance;

    /**
     * 參考編號
     *
     * @var int
     *
     * @ORM\Column(name = "ref_id", type = "bigint", length = 20, options={"default"=0})
     */
    protected $refId;

    /**
     * 備註
     *
     * @var string
     *
     * @ORM\Column(name = "memo", type = "string", length = 100, options = {"default" = ""})
     */
    private $memo = '';

    /**
     * @param integer $opcode 交易種類
     * @param float   $amount 交易金額
     * @param string  $memo   交易備註
     */
    public function __construct($opcode, $amount, $memo)
    {
        $createAt = new \DateTime('now');

        $this->opcode = $opcode;
        $this->at = $createAt->format('YmdHis');
        $this->createdAt = $createAt;
        $this->amount = $amount;
        $this->memo = $memo;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 設定id
     * @param integer $id
     * @return CashEntryBase
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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
     * 回傳交易種類
     *
     * @return integer
     */
    public function getOpcode()
    {
        return $this->opcode;
    }

    /**
     * 回傳交易金額
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * 回傳交易後餘額
     *
     * @return float
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * 回傳參考編號
     *
     * @return int
     */
    public function getRefId()
    {
        return $this->refId;
    }

    /**
     * 設定參考編號
     *
     * @param int $refId
     */
    public function setRefId($refId)
    {
        $this->refId = $refId;
    }

    /**
     * 回傳交易時間(舊)
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * 設定交易時間(舊)
     *
     * @param \DateTime $createdAt
     * @return CashEntryBase
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * 回傳交易時間(新)
     *
     * @return integer
     */
    public function getAt()
    {
        return $this->at;
    }

    /**
     * 設定交易時間(新)
     *
     * @param integer $at
     * @return CashEntryBase
     */
    public function setAt($at)
    {
        $this->at = $at;

        return $this;
    }

    /**
     * 設定備註
     *
     * @param string $memo
     * @return CashEntry
     */
    public function setMemo($memo)
    {
        $this->memo = $memo;

        return $this;
    }

    /**
     * 回傳備註
     *
     * @return string
     */
    public function getMemo()
    {
        return $this->memo;
    }
}
