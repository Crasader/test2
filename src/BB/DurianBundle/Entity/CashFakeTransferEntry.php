<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Currency;
use BB\DurianBundle\Entity\CashFakeEntry;

/**
 * 假現金轉帳交易記錄
 *
 * @ORM\Entity
 * @ORM\Table(name = "cash_fake_transfer_entry",
 *     indexes = {
 *         @ORM\Index(name = "idx_cash_fake_transfer_entry_at", columns = {"at"}),
 *         @ORM\Index(name = "idx_cash_fake_transfer_entry_ref_id", columns = {"ref_id"}),
 *         @ORM\Index(name = "idx_cash_fake_transfer_entry_user_id_at", columns = {"user_id", "at"}),
 *         @ORM\Index(name = "idx_cash_fake_transfer_entry_domain_at", columns = {"domain", "at"})
 *     }
 * )
 */
class CashFakeTransferEntry
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "bigint")
     */
    private $id;

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
     * 使用者Id
     *
     * @var integer
     *
     * @ORM\Column(name = "user_id", type = "integer")
     */
    private $userId;

    /**
     * 登入站別
     *
     * @var integer
     *
     * @ORM\Column(type = "integer")
     */
    private $domain;

    /**
     * 幣別
     *
     * @var integer
     *
     * @ORM\Column(type = "smallint")
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
    private $balance;

    /**
     * 參考編號
     *
     * @var int
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
    private $memo = '';

    /**
     * @param CashFakeEntry $entry 假現金交易記錄
     * @param integer $domain 使用者所在的廳
     */
    public function __construct(CashFakeEntry $entry, $domain)
    {
        if ($entry->getOpcode() >= 9890) {
            throw new \InvalidArgumentException('Invalid opcode', 150050046);
        }

        $this->id = $entry->getId();
        $this->at = $entry->getAt();
        $this->userId = $entry->getUserId();
        $this->domain = $domain;
        $this->currency = $entry->getCurrency();
        $this->opcode = $entry->getOpcode();
        $this->createdAt = $entry->getCreatedAt();
        $this->amount = $entry->getAmount();
        $this->balance = $entry->getBalance();
        $this->refId = $entry->getRefId();
        $this->memo = $entry->getMemo();
    }

    /**
     * 回傳 Id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
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
     * @param integer $at 交易時間
     * @return CashFakeTransferEntry
     */
    public function setAt($at)
    {
        $this->at = $at;

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
     * 回傳登入站別
     *
     * @return integer
     */
    public function getDomain()
    {
        return $this->domain;
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
     * 回傳交易種類
     *
     * @return integer
     */
    public function getOpcode()
    {
        return $this->opcode;
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
     * @param \DateTime $createdAt 交易時間(舊)
     * @return CashFakeTransferEntry
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

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
     * 回傳備註
     *
     * @return string
     */
    public function getMemo()
    {
        return $this->memo;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $refId = $this->getRefId();
        if ($refId == 0) {
            $refId = '';
        }

        $currencyOperator = new Currency();

        return [
            'id' => $this->getId(),
            'user_id' => $this->getUserId(),
            'domain' => $this->getDomain(),
            'currency' => $currencyOperator->getMappedCode($this->getCurrency()),
            'opcode' => $this->getOpcode(),
            'created_at' => $this->getCreatedAt()->format(\DateTime::ISO8601),
            'amount' => $this->getAmount(),
            'balance' => $this->getBalance(),
            'ref_id' => $refId,
            'memo' => $this->getMemo()
        ];
    }
}
