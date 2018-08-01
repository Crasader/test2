<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Currency;
use BB\DurianBundle\Entity\CashEntry;

/**
 * 金流交易紀錄
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\PaymentDepositWithdrawEntryRepository")
 * @ORM\Table(name = "payment_deposit_withdraw_entry",
 *      indexes = {
 *          @ORM\Index(name = "idx_payment_deposit_withdraw_entry_at", columns = {"at"}),
 *          @ORM\Index(name = "idx_payment_deposit_withdraw_entry_ref_id", columns = {"ref_id"}),
 *          @ORM\Index(name = "idx_payment_deposit_withdraw_entry_merchant_id", columns = {"merchant_id"}),
 *          @ORM\Index(name = "idx_payment_deposit_withdraw_entry_remit_account_id", columns = {"remit_account_id"}),
 *          @ORM\Index(name = "idx_payment_deposit_withdraw_entry_domain_at", columns = {"domain", "at"}),
 *          @ORM\Index(name = "idx_payment_deposit_withdraw_entry_user_id_at", columns = {"user_id", "at"})
 *      }
 * )
 */
class PaymentDepositWithdrawEntry
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type = "bigint")
     */
    private $id;

    /**
     * 建立時間
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type = "bigint")
     */
    private $at;

    /**
     * 商家ID
     *
     * @var integer
     *
     * @ORM\Column(name = "merchant_id", type = "integer", options = {"unsigned" = true, "default" = 0})
     */
    private $merchantId;

    /**
     * 出入款帳號ID
     *
     * @var integer
     *
     * @ORM\Column(name = "remit_account_id", type = "integer", options = {"unsigned" = true, "default" = 0})
     */
    private $remitAccountId;

    /**
     * 登入站別
     *
     * @var integer
     *
     * @ORM\Column(type = "integer")
     */
    private $domain;

    /**
     * 使用者ID
     *
     * @var integer
     *
     * @ORM\Column(name = "user_id", type = "bigint")
     */
    private $userId;

    /**
     * 參考編號
     *
     * @var integer
     *
     * @ORM\Column(name = "ref_id", type = "bigint", options = {"default" = 0})
     */
    private $refId;

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
     * @ORM\Column(type = "integer")
     */
    private $opcode;

    /**
     * 交易金額
     *
     * @var float
     *
     * @ORM\Column(type = "decimal", precision = 16, scale = 4)
     */
    private $amount;

    /**
     * 交易餘額
     *
     * @var float
     *
     * @ORM\Column(type = "decimal", precision = 16, scale = 4)
     */
    private $balance;

    /**
     * 備註
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 100, options = {"default" = ""})
     */
    private $memo;

    /**
     * 操作者名稱
     *
     * @var string
     *
     * @ORM\Column(name = "operator", type = "string", length = 30, options = {"default" = ""})
     */
    private $operator = '';

    /**
     * @param CashEntry $entry 現金交易記錄
     * @param integer $domain 廳
     * @param string $operator 操作者名稱
     */
    public function __construct(CashEntry $entry, $domain, $operator = '')
    {
        if ($entry->getOpcode() >= 9890) {
            throw new \InvalidArgumentException('Invalid opcode', 150040058);
        }

        $this->id = $entry->getId();
        $this->at = $entry->getAt();
        $this->merchantId = 0;
        $this->remitAccountId = 0;
        $this->domain = $domain;
        $this->userId = $entry->getUserId();
        $this->refId = $entry->getRefId();
        $this->currency = $entry->getCurrency();
        $this->opcode = $entry->getOpcode();
        $this->amount = $entry->getAmount();
        $this->balance = $entry->getBalance();
        $this->memo = $entry->getMemo();
        $this->operator = $operator;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 回傳交易時間
     *
     * @return \DateTime
     */
    public function getAt()
    {
        return new \DateTime($this->at);
    }

    /**
     * 設定交易時間
     *
     * @param integer $at
     * @return PaymentDepositWithdrawEntry
     */
    public function setAt($at)
    {
        $this->at = $at;

        return $this;
    }

    /**
     * 回傳商家ID
     *
     * @return integer
     */
    public function getMerchantId()
    {
        return $this->merchantId;
    }

    /**
     * 設定商家ID
     *
     * @param integer $merchantId
     * @return PaymentDepositWithdrawEntry
     */
    public function setMerchantId($merchantId)
    {
        $this->merchantId = $merchantId;

        return $this;
    }

    /**
     * 回傳出入款帳號ID
     *
     * @return integer
     */
    public function getRemitAccountId()
    {
        return $this->remitAccountId;
    }

    /**
     * 設定出入款帳號ID
     *
     * @param integer $remitAccountId
     * @return PaymentDepositWithdrawEntry
     */
    public function setRemitAccountId($remitAccountId)
    {
        $this->remitAccountId = $remitAccountId;

        return $this;
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
     * 回傳使用者ID
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
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
     * 回傳幣別
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
     * 回傳備註
     *
     * @return string
     */
    public function getMemo()
    {
        return $this->memo;
    }

    /**
     * 回傳操作者名稱
     *
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $merchantId = $this->getMerchantId();
        if ($merchantId == 0) {
            $merchantId = '';
        }

        $remitAccountId = $this->getRemitAccountId();
        if ($remitAccountId == 0) {
            $remitAccountId = '';
        }

        $refId = $this->getRefId();
        if ($refId == 0) {
            $refId = '';
        }

        $currencyOperator = new Currency();

        return [
            'id' => $this->getId(),
            'at' => $this->getAt()->format(\DateTime::ISO8601),
            'merchant_id' => $merchantId,
            'remit_account_id' => $remitAccountId,
            'domain' => $this->getDomain(),
            'user_id' => $this->getUserId(),
            'ref_id' => $refId,
            'currency' => $currencyOperator->getMappedCode($this->getCurrency()),
            'opcode' => $this->getOpcode(),
            'amount' => $this->getAmount(),
            'balance' => $this->getBalance(),
            'memo' => $this->getMemo(),
            'operator' => $this->getOperator()
        ];
    }
}
