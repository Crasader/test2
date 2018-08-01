<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Currency;

/**
 * 負數現金
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\CashNegativeRepository")
 * @ORM\Table(name = "cash_negative", indexes = {
 *     @ORM\Index(name = "idx_cash_negative_negative", columns = {"negative"})
 * })
 */
class CashNegative
{
    /**
     * 使用者編號
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "user_id", type = "integer")
     */
    private $userId;

    /**
     * 幣別
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "currency", type = "smallint", options = {"unsigned" = true})
     */
    private $currency;

    /**
     * 現金編號
     *
     * @var integer
     *
     * @ORM\Column(name = "cash_id", type = "integer")
     */
    private $cashId;

    /**
     * 餘額
     *
     * @var float
     *
     * @ORM\Column(name = "balance", type = "decimal", precision = 16, scale = 4)
     */
    private $balance;

    /**
     * 餘額是否為負數
     *
     * @var boolean
     *
     * @ORM\Column(name = "negative", type = "boolean")
     */
    private $negative;

    /**
     * 版本號
     *
     * @var integer
     *
     * @ORM\Column(name = "version", type = "integer", options = {"unsigned" = true})
     */
    private $version;

    /**
     * 導致負數額度的明細編號
     *
     * @var integer
     *
     * @ORM\Column(name = "entry_id", type = "bigint")
     */
    private $entryId;

    /**
     * 建立時間
     *
     * @var integer
     *
     * @ORM\Column(name = "at", type = "bigint")
     */
    private $at;

    /**
     * 交易代碼
     *
     * @var integer
     *
     * @ORM\Column(name = "opcode", type = "integer")
     */
    private $opcode;

    /**
     * 交易金額
     *
     * @var float
     *
     * @ORM\Column(name = "amount", type = "decimal", precision = 16, scale = 4)
     */
    private $amount;

    /**
     * 明細餘額
     *
     * @var float
     *
     * @ORM\Column(name = "entry_balance", type = "decimal", precision = 16, scale = 4)
     */
    private $entryBalance;

    /**
     * 參考編號
     *
     * @var integer
     *
     * @ORM\Column(name = "ref_id", type = "bigint", length = 20, options = {"default" = 0})
     */
    private $refId;

    /**
     * 備註
     *
     * @var string
     *
     * @ORM\Column(name = "memo", type = "string", length = 100, options = {"default" = ""})
     */
    private $memo;

    /**
     * 初始化
     *
     * @param integer $userId
     * @param integer $currency
     */
    public function __construct($userId, $currency)
    {
        $this->userId = $userId;
        $this->currency = $currency;
        $this->refId = 0;
        $this->memo = '';
    }

    /**
     * 回傳使用者編號
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
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
     * 設定現金編號
     *
     * @param integer $cashId
     * @return CashNegative
     */
    public function setCashId($cashId)
    {
        $this->cashId = $cashId;

        return $this;
    }

    /**
     * 回傳現金編號
     *
     * @return integer
     */
    public function getCashId()
    {
        return $this->cashId;
    }

    /**
     * 設定餘額
     *
     * @param float $balance
     * @return CashNegative
     */
    public function setBalance($balance)
    {
        $this->balance = $balance;
        $this->negative = false;

        if ($balance < 0) {
            $this->negative = true;
        }

        return $this;
    }

    /**
     * 回傳餘額
     *
     * @return float
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * 回傳是否為負數
     *
     * @return boolean
     */
    public function isNegative()
    {
        return (boolean) $this->negative;
    }

    /**
     * 設定版本號
     *
     * @param integer $version
     * @return CashNegative
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * 回傳版本號
     *
     * @return integer
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * 設定明細編號
     *
     * @param integer $entryId
     * @return CashNegative
     */
    public function setEntryId($entryId)
    {
        $this->entryId = $entryId;

        return $this;
    }

    /**
     * 回傳明細編號
     *
     * @return integer
     */
    public function getEntryId()
    {
        return $this->entryId;
    }

    /**
     * 設定建立時間
     *
     * @param integer $at
     * @return CashNegative
     */
    public function setAt($at)
    {
        $this->at = $at;

        return $this;
    }

    /**
     * 回傳建立時間
     *
     * @return integer
     */
    public function getAt()
    {
        return $this->at;
    }

    /**
     * 設定交易代碼
     *
     * @param integer $opcode
     * @return CashNegative
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
     * 設定交易金額
     *
     * @param float $amount
     * @return CashNegative
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
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
     * 設定明細餘額
     *
     * @param float $entryBalance
     * @return CashNegative
     */
    public function setEntryBalance($entryBalance)
    {
        $this->entryBalance = $entryBalance;

        return $this;
    }

    /**
     * 回傳明細餘額
     *
     * @return float
     */
    public function getEntryBalance()
    {
        return $this->entryBalance;
    }

    /**
     * 設定參考編號
     *
     * @param integer $refId
     * @return CashNegative
     */
    public function setRefId($refId)
    {
        $this->refId = $refId;

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
     * 設定備註
     *
     * @param string $memo
     * @return CashNegative
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

    /**
     * 回傳陣列形式
     *
     * @return array
     */
    public function toArray()
    {
        $currencyOperator = new Currency;
        $currency = $currencyOperator->getMappedCode($this->getCurrency());
        $createdAt = (new \DateTime($this->getAt()))->format(\DateTime::ISO8601);

        $refId = $this->getRefId();

        if ($refId == 0) {
            $refId = '';
        }

        return [
            'cash' => [
                'id'       => $this->getCashId(),
                'user_id'  => $this->getUserId(),
                'currency' => $currency,
                'balance'  => $this->getBalance()
            ],
            'entry' => [
                'id'         => $this->getEntryId(),
                'cash_id'    => $this->getCashId(),
                'user_id'    => $this->getUserId(),
                'currency'   => $currency,
                'opcode'     => $this->getOpcode(),
                'created_at' => $createdAt,
                'amount'     => $this->getAmount(),
                'balance'    => $this->getEntryBalance(),
                'ref_id'     => $refId,
                'memo'       => $this->getMemo()
            ]
        ];
    }
}
