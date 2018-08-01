<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Currency;

/**
 * 比特幣入款記錄
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\BitcoinDepositEntryRepository")
 * @ORM\Table(name = "bitcoin_deposit_entry",
 *      indexes = {
 *          @ORM\Index(name = "idx_bitcoin_deposit_entry_user_id", columns = {"user_id"}),
 *          @ORM\Index(name = "idx_bitcoin_deposit_entry_confirm_at", columns = {"confirm_at"}),
 *          @ORM\Index(name = "idx_bitcoin_deposit_entry_at", columns = {"at"}),
 *          @ORM\Index(name = "idx_bitcoin_deposit_entry_domain_at", columns = {"domain", "at"})
 *      }
 * )
 */
class BitcoinDepositEntry
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type = "bigint", options = {"unsigned" = true})
     */
    private $id;

    /**
     * 對應的比特幣錢包id
     *
     * @var integer
     *
     * @ORM\Column(name = "bitcoin_wallet_id", type = "integer", options = {"unsigned" = true})
     */
    private $bitcoinWalletId;

    /**
     * 對應的比特幣位址id
     *
     * @var integer
     *
     * @ORM\Column(name = "bitcoin_address_id", type = "integer", options = {"unsigned" = true})
     */
    private $bitcoinAddressId;

    /**
     * 比特幣入款位址
     *
     * @var string
     *
     * @ORM\Column(name = "bitcoin_address", type = "string", length = 64)
     */
    private $bitcoinAddress;

    /**
     * 入款使用者ID
     *
     * @var integer
     *
     * @ORM\Column(name = "user_id", type = "bigint", options = {"unsigned" = true})
     */
    private $userId;

    /**
     * 廳
     *
     * @var integer
     *
     * @ORM\Column(type = "bigint", options = {"unsigned" = true})
     */
    private $domain;

    /**
     * 層級Id
     *
     * @var integer
     *
     * @ORM\Column(name = "level_id", type = "integer", options = {"unsigned" = true})
     */
    private $levelId;

    /**
     * 申請入款時間
     *
     * @var integer
     *
     * @ORM\Column(name = "at", type = "bigint", options = {"unsigned" = true})
     */
    private $at;

    /**
     * 確認入款時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "confirm_at", type = "datetime", nullable = true)
     */
    private $confirmAt;

    /**
     * 處理中
     *
     * @var boolean
     *
     * @ORM\Column(name = "process", type = "boolean")
     */
    private $process;

    /**
     * 確認入款
     *
     * @var boolean
     *
     * @ORM\Column(name = "confirm", type = "boolean")
     */
    private $confirm;

    /**
     * 取消入款
     *
     * @var boolean
     *
     * @ORM\Column(name = "cancel", type = "boolean")
     */
    private $cancel;

    /**
     * 對應存款金額交易明細id
     *
     * @var integer
     *
     * @ORM\Column(name = "amount_entry_id", type = "bigint")
     */
    private $amountEntryId;

    /**
     * 入款使用者輸入的存款幣別
     *
     * @var float
     *
     * @ORM\Column(name = "currency", type = "smallint", options = {"unsigned" = true})
     */
    private $currency;

    /**
     * 付款種類的幣別
     *
     * @var integer
     *
     * @ORM\Column(name = "payway_currency", type = "smallint", options = {"unsigned" = true})
     */
    private $paywayCurrency;

    /**
     * 入款使用者輸入的存款金額
     *
     * @var float
     *
     * @ORM\Column(name = "amount", type = "decimal", precision = 16, scale = 4)
     */
    private $amount;

    /**
     * 比特幣支付金額
     *
     * @var float
     *
     * @ORM\Column(name = "bitcoin_amount", type = "decimal", precision = 16, scale = 8)
     */
    private $bitcoinAmount;

    /**
     * 入款匯率
     *
     * @var float
     *
     * @ORM\Column(name = "rate", type = "decimal", precision = 16, scale = 8)
     */
    private $rate;

    /**
     * 轉換成付款種類匯率
     *
     * @var float
     *
     * @ORM\Column(name = "payway_rate", type = "decimal", precision = 16, scale = 8)
     */
    private $paywayRate;

    /**
     * 比特幣入款匯率
     *
     * @var float
     *
     * @ORM\Column(name = "bitcoin_rate", type = "decimal", precision = 16, scale = 8)
     */
    private $bitcoinRate;

    /**
     * 匯差
     *
     * @var float
     *
     * @ORM\Column(name = "rate_difference", type = "decimal", precision = 16, scale = 8)
     */
    private $rateDifference;

    /**
     * 是否為控端
     *
     * @var boolean
     *
     * @ORM\Column(name = "control", type = "boolean")
     *
     */
    private $control;

    /**
     * 操作者
     *
     * @var string
     *
     * @ORM\Column(name = "operator", type = "string", length = 30)
     *
     */
    private $operator;

    /**
     * 備註
     *
     * @var string
     *
     * @ORM\Column(name = "memo", type = "string", length = 500)
     *
     */
    private $memo;

    /**
     * 版本號
     *
     * @var integer
     *
     * @ORM\Column(name = "version", type = "integer")
     * @ORM\Version
     */
    private $version;

    /**
     * 新增一筆入款記錄
     *
     * @param BitcoinWallet $wallet 公司入款帳號
     * @param User $user 入款使用者
     * @param BankInfo $bankInfo 入款使用者所用銀行
     */
    public function __construct($data)
    {
        $at = new \DateTime('now');

        $this->id = $data['id'];
        $this->bitcoinWalletId = $data['bitcoin_wallet_id'];
        $this->bitcoinAddressId = $data['bitcoin_address_id'];
        $this->bitcoinAddress = $data['bitcoin_address'];
        $this->userId = $data['user_id'];
        $this->domain = $data['domain'];
        $this->levelId = $data['level_id'];
        $this->at = $at->format('YmdHis');
        $this->process = 1;
        $this->confirm = 0;
        $this->cancel = 0;
        $this->amountEntryId = 0;
        $this->currency = $data['currency'];
        $this->paywayCurrency = $data['payway_currency'];
        $this->amount = $data['amount'];
        $this->bitcoinAmount = $data['bitcoin_amount'];
        $this->rate = $data['rate'];
        $this->paywayRate = $data['payway_rate'];
        $this->bitcoinRate = $data['bitcoin_rate'];
        $this->rateDifference = $data['rate_difference'];
        $this->control = 0;
        $this->operator = '';
        $this->memo = '';
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 取得對應的比特幣錢包id
     *
     * @return integer
     */
    public function getBitcoinWalletId()
    {
        return $this->bitcoinWalletId;
    }

    /**
     * 取得對應的比特幣位址id
     *
     * @return integer
     */
    public function getBitcoinAddressId()
    {
        return $this->bitcoinAddressId;
    }

    /**
     * 取得比特幣入款位址
     *
     * @return string
     */
    public function getBitcoinAddress()
    {
        return $this->bitcoinAddress;
    }

    /**
     * 取得入款使用者ID
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * 取得廳
     *
     * @return integer
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * 取得層級Id
     *
     * @return integer
     */
    public function getLevelId()
    {
        return $this->levelId;
    }

    /**
     * 設定申請入款時間
     *
     * @param integer $at
     * @return BitcoinDepositEntry
     */
    public function setAt($at)
    {
        $this->at = $at;

        return $this;
    }

    /**
     * 取得申請入款時間
     *
     * @return integer
     */
    public function getAt()
    {
        return new \DateTime($this->at);
    }

    /**
     * 取得確認入款時間
     *
     * @return \DateTime
     */
    public function getConfirmAt()
    {
        return $this->confirmAt;
    }

    /**
     * 回傳明細是否處理中
     *
     * @return boolean
     */
    public function isProcess()
    {
        return (bool) $this->process;
    }

    /**
     * 確認入款
     *
     * @return BitcoinDepositEntry
     */
    public function confirm()
    {
        $this->confirm = true;
        $this->confirmAt = new \DateTime('now');
        $this->process = false;

        return $this;
    }

    /**
     * 回傳處理狀態
     *
     * @return boolean
     */
    public function isConfirm()
    {
        return (bool) $this->confirm;
    }

    /**
     * 取消入款
     *
     * @return BitcoinDepositEntry
     */
    public function cancel()
    {
        $this->cancel = true;
        $this->confirmAt = new \DateTime('now');
        $this->process = false;

        return $this;
    }

    /**
     * 回傳是否取消
     *
     * @return boolean
     */
    public function isCancel()
    {
        return (bool) $this->cancel;
    }

    /**
     * 設定對應存款金額交易明細id
     *
     * @param integer $amountEntryId
     * @return BitcoinDepositEntry
     */
    public function setAmountEntryId($amountEntryId)
    {
        $this->amountEntryId = $amountEntryId;

        return $this;
    }

    /**
     * 取得對應存款金額交易明細id
     *
     * @return integer
     */
    public function getAmountEntryId()
    {
        return $this->amountEntryId;
    }

    /**
     * 回傳入款幣別
     *
     * @return integer
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * 回傳付款種類的幣別
     *
     * @return integer
     */
    public function getPaywayCurrency()
    {
        return $this->paywayCurrency;
    }

    /**
     * 取得入款使用者輸入的存款金額
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * 回傳基本幣別入款金額
     *
     * @return float
     */
    public function getAmountConvBasic()
    {
        return number_format($this->amount * $this->rate, 4, '.', '');
    }

    /**
     * 回傳交易幣別入款金額
     *
     * @return float
     */
    public function getAmountConv()
    {
        return number_format($this->amount * $this->rate / $this->paywayRate, 4, '.', '');
    }

    /**
     * 取得比特幣支付金額
     *
     * @return float
     */
    public function getBitcoinAmount()
    {
        return $this->bitcoinAmount;
    }

    /**
     * 取得入款匯率
     *
     * @return float
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * 取得付款種類匯率
     *
     * @return float
     */
    public function getPaywayRate()
    {
        return $this->paywayRate;
    }

    /**
     * 取得比特幣入款匯率
     *
     * @return float
     */
    public function getBitcoinRate()
    {
        return $this->bitcoinRate;
    }

    /**
     * 取得匯差
     *
     * @return float
     */
    public function getRateDifference()
    {
        return $this->rateDifference;
    }

    /**
     * 控端
     *
     * @return BitcoinDepositEntry
     */
    public function control()
    {
        $this->control = true;

        return $this;
    }

    /**
     * 回傳是否為控端
     *
     * @return boolean
     */
    public function isControl()
    {
        return (bool) $this->control;
    }

    /**
     * 設定操作者
     *
     * @param string $operator
     * @return BitcoinDepositEntry
     */
    public function setOperator($operator)
    {
        $this->operator = $operator;

        return $this;
    }

    /**
     * 取得操作者
     *
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * 設定備註
     *
     * @param string $memo
     * @return BitcoinDepositEntry
     */
    public function setMemo($memo)
    {
        $this->memo = $memo;

        return $this;
    }

    /**
     * 取得備註
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
        $confirmAt = null;
        if (null !== $this->getConfirmAt()) {
            $confirmAt = $this->getConfirmAt()->format(\DateTime::ISO8601);
        }

        $currencyOperator = new Currency();

        return [
            'id' => $this->getId(),
            'bitcoin_wallet_id' => $this->getBitcoinWalletId(),
            'bitcoin_address_id' => $this->getBitcoinAddressId(),
            'bitcoin_address' => $this->getBitcoinAddress(),
            'user_id' => $this->getUserId(),
            'domain' => $this->getDomain(),
            'level_id' => $this->getLevelId(),
            'at' => $this->getAt()->format(\DateTime::ISO8601),
            'confirm_at' => $confirmAt,
            'process' => $this->isProcess(),
            'confirm' => $this->isConfirm(),
            'cancel' => $this->isCancel(),
            'amount_entry_id' => $this->getAmountEntryId(),
            'currency' => $currencyOperator->getMappedCode($this->getCurrency()),
            'payway_currency' => $currencyOperator->getMappedCode($this->getPaywayCurrency()),
            'amount' => $this->getAmount(),
            'amount_conv_basic' => $this->getAmountConvBasic(),
            'amount_conv' => $this->getAmountConv(),
            'bitcoin_amount' => $this->getBitcoinAmount(),
            'rate' => $this->getRate(),
            'payway_rate' => $this->getPaywayRate(),
            'bitcoin_rate' => $this->getBitcoinRate(),
            'rate_difference' => $this->getRateDifference(),
            'control' => $this->isControl(),
            'operator' => $this->getOperator(),
            'memo' => $this->getMemo(),
        ];
    }
}
