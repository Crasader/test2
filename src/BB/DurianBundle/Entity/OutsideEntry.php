<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Currency;

/**
 * 外接額度交易明細
 *
 * @ORM\Entity(repositoryClass="BB\DurianBundle\Repository\OutsideEntryRepository")
 * @ORM\Table(
 *      name = "outside_entry",
 *      indexes = {
 *          @ORM\Index(name = "idx_outside_entry_created_at", columns = {"created_at"}),
 *          @ORM\Index(name = "idx_outside_entry_user_id_created_at", columns = {"user_id", "created_at"}),
 *          @ORM\Index(name = "idx_outside_entry_ref_id", columns = {"ref_id"})
 *      }
 * )
 */
class OutsideEntry
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "bigint")
     */
    private $id;

    /**
     * bbin使用者編號
     *
     * @var integer
     *
     * @ORM\Column(name = "user_id", type = "bigint", options = {"unsigned" = true})
     */
    private $userId;

    /**
     * 幣別
     *
     * @var integer
     *
     * @ORM\Column(name = "currency", type = "smallint", options = {"unsigned" = true})
     */
    private $currency;

    /**
     * 交易代碼
     *
     * @var integer
     *
     * @ORM\Column(name = "opcode", type = "integer")
     */
    private $opcode;

    /**
     * 交易量
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
     * @var integer
     *
     * @ORM\Column(name = "ref_id", type = "bigint", length = 20, options = {"default" = "0"})
     */
    protected $refId;

    /**
     * 備註
     *
     * @var string
     *
     * @ORM\Column(name = "memo", type = "string", length = 100, options = {"default" = ""})
     */
    private $memo;

    /**
     * 建立時間
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "created_at", type = "bigint")
     */
    private $createdAt;

    /**
     * 外接額度群組編號
     *
     * @var integer
     *
     * @ORM\Column(name = "group_num", type = "integer")
     */
    protected $group;

    /**
     * 建構子
     */
    public function __construct()
    {
        $this->memo = '';
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
     *
     * @param integer $id
     *
     * @return OutsideEntry
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * 回傳對應的使用者編號
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * 設定對應的使用者編號
     *
     * @param integer $userId
     *
     * @return OutsideEntry
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

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
     * 設定幣別
     *
     * @param integer $currency
     *
     * @return OutsideEntry
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
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
     * 設定交易種類
     *
     * @param integer $opcode
     *
     * @return OutsideEntry
     */
    public function setOpcode($opcode)
    {
        $this->opcode = $opcode;

        return $this;
    }

    /**
     * 回傳交易量
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * 設定交易量
     *
     * @param float $amount
     *
     * @return OutsideEntry
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
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
     * 設定餘額
     *
     * @param float $balance
     *
     * @return OutsideEntry
     */
    public function setBalance($balance)
    {
        $this->balance = $balance;

        return $this;
    }

    /**
     * 回傳參考編號
     *
     * @return integer
     */
    public function getRefId()
    {
        return $this->refId;
    }

    /**
     * 設定參考編號
     *
     * @param integer $refId
     *
     * @return OutsideEntry
     */
    public function setRefId($refId)
    {
        $this->refId = $refId;

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

    /**
     * 設定備註
     *
     * @param string $memo
     *
     * @return OutsideEntry
     */
    public function setMemo($memo)
    {
        $this->memo = $memo;

        return $this;
    }

    /**
     * 回傳建立時間
     *
     * @return integer
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * 設定建立時間
     *
     * @param integer $createdAt
     *
     * @return OutsideEntry
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * 回傳群組編號
     *
     * @return integer
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * 設定群組編號
     *
     * @param integer $group
     *
     * @return OutsideEntry
     */
    public function setGroup($group)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * 回傳此物件的陣列型式
     *
     * @return array
     */
    public function toArray()
    {
        $refId = $this->getRefId();
        if ($refId == 0) {
            $refId = '';
        }

        $createdAt = new \DateTime($this->getCreatedAt());
        $currencyService = new Currency();

        return [
            'id'               => $this->getId(),
            'user_id'          => $this->getUserId(),
            'currency'         => $currencyService->getMappedCode($this->getCurrency()),
            'opcode'           => $this->getOpcode(),
            'created_at'       => $createdAt->format(\DateTime::ISO8601),
            'amount'           => $this->getAmount(),
            'balance'          => $this->getBalance(),
            'memo'             => $this->getMemo(),
            'ref_id'           => $refId,
            'group'            => $this->getGroup()
        ];
    }
}
