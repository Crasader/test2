<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\User;

/**
 * 速達入款
 *
 * @ORM\Entity
 * @ORM\Table(name = "deposit_suda_entry")
 */
class DepositSudaEntry
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "integer", options = {"unsigned" = true})
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 速達流水號
     *
     * @var integer
     *
     * @ORM\Column(name = "seq_id", type = "integer", options = {"unsigned" = true})
     */
    private $seqId;

    /**
     * 使用者Id
     *
     * @var integer
     *
     * @ORM\Column(name = "user_id", type = "integer")
     */
    private $userId;

    /**
     * 廳
     *
     * @var integer
     *
     * @ORM\Column(name = "domain", type = "integer")
     */
    private $domain;

    /**
     * 速達商號
     *
     * @var integer
     *
     * @ORM\Column(name = "merchant_number", type = "integer", options = {"unsigned" = true})
     */
    private $merchantNumber;

    /**
     * 速達訂單號
     *
     * @var string
     *
     * @ORM\Column(name = "order_id", type = "string", length = 40)
     */
    private $orderId;

    /**
     * 速達銀行代碼
     *
     * @var string
     *
     * @ORM\Column(name = "code", type = "string", length = 5)
     */
    private $code;

    /**
     * 速達商號名稱
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 45)
     */
    private $alias;

    /**
     * 存入金額
     *
     * @var float
     *
     * @ORM\Column(name = "amount", type = "decimal", precision = 16, scale = 4)
     */
    private $amount;

    /**
     * 存款優惠
     *
     * @var float
     *
     * @ORM\Column(name = "offer_deposit", type = "decimal", precision = 16, scale = 4)
     */
    private $offerDeposit;

    /**
     * 其他優惠
     *
     * @var float
     *
     * @ORM\Column(name = "offer_other", type = "decimal", precision = 16, scale = 4)
     */
    private $offerOther;

    /**
     * 銀行代碼
     *
     * @var integer
     *
     * @ORM\Column(name = "bank_info_id", type = "integer")
     */
    private $bankInfoId;

    /**
     * 收款人
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 100)
     */
    private $recipient;

    /**
     * 銀行帳號
     *
     * @var string
     *
     * @ORM\Column(name = "account", type = "string", length = 36)
     */
    private $account;

    /**
     * 手續費
     *
     * @var float
     *
     * @ORM\Column(type = "decimal", precision = 16, scale = 4)
     */
    private $fee;

    /**
     * 入款速達商號 ID
     *
     * @var integer
     *
     * @ORM\Column(name = "merchant_suda_id", type = "smallint", options = {"unsigned" = true})
     */
    private $merchantSudaId;

    /**
     * 提交時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "created_at", type = "datetime")
     */
    private $createdAt;

    /**
     * 確認使用者名稱
     *
     * @var string
     *
     * @ORM\Column(name = "checked_username", type = "string", length = 30)
     */
    private $checkedUsername;

    /**
     * 確認時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "confirm_at", type = "datetime", nullable = true)
     */
    private $confirmAt;

    /**
     * 是否確認
     *
     * @var boolean
     *
     * @ORM\Column(type = "boolean")
     */
    private $confirm;

    /**
     * 是否取消
     *
     * @var boolean
     *
     * @ORM\Column(type = "boolean")
     */
    private $cancel;

    /**
     * 備註
     *
     * @var string
     *
     * @ORM\Column(name = "memo", type = "string", length = 100, options = {"default" = ""})
     */
    private $memo;

    /**
     * @param User $user 對應的使用者
     * @param array $setting 預設設定值
     */
    public function __construct(User $user, $setting)
    {
        $this->seqId           = 0;
        $this->userId          = $user->getId();
        $this->domain          = $user->getDomain();
        $this->merchantNumber  = 0;
        $this->orderId         = '';
        $this->code            = '';
        $this->alias           = '';
        $this->amount          = 0;
        $this->offerDeposit    = 0;
        $this->offerOther      = 0;
        $this->bankInfoId      = 0;
        $this->recipient       = '';
        $this->account         = '';
        $this->fee             = 0;
        $this->merchantSudaId  = 0;
        $this->memo            = '';
        $this->createdAt       = new \DateTime('now');
        $this->checkedUsername = '';
        $this->confirm         = false;
        $this->cancel          = false;

        if (isset($setting['seq_id'])) {
            $this->seqId = $setting['seq_id'];
        }

        if (isset($setting['merchant_number'])) {
            $this->merchantNumber = $setting['merchant_number'];
        }

        if (isset($setting['order_id'])) {
            $this->orderId = $setting['order_id'];
        }

        if (isset($setting['code'])) {
            $this->code = $setting['code'];
        }

        if (isset($setting['alias'])) {
            $this->alias = $setting['alias'];
        }

        if (isset($setting['amount'])) {
            $this->amount = $setting['amount'];
        }

        if (isset($setting['offer_deposit'])) {
            $this->offerDeposit = $setting['offer_deposit'];
        }

        if (isset($setting['offer_other'])) {
            $this->offerOther = $setting['offer_other'];
        }

        if (isset($setting['bank_info_id'])) {
            $this->bankInfoId = $setting['bank_info_id'];
        }

        if (isset($setting['recipient'])) {
            $this->recipient = $setting['recipient'];
        }

        if (isset($setting['account'])) {
            $this->account = $setting['account'];
        }

        if (isset($setting['fee'])) {
            $this->fee = $setting['fee'];
        }

        if (isset($setting['merchant_suda_id'])) {
            $this->merchantSudaId = $setting['merchant_suda_id'];
        }

        if (isset($setting['memo'])) {
            $this->memo = $setting['memo'];
        }
    }

    /**
     * 回傳 id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 設定 id
     *
     * @param integer $id
     * @return DepositSudaEntry
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * 回傳速達流水號
     *
     * @return integer
     */
    public function getSeqId()
    {
        return $this->seqId;
    }

    /**
     * 設定速達流水號
     *
     * @param integer $seqId
     * @return DepositSudaEntry
     */
    public function setSeqId($seqId)
    {
        $this->seqId = $seqId;

        return $this;
    }

    /**
     * 回傳會員Id
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * 設定會員Id
     *
     * @param integer $userId
     * @return DepositSudaEntry
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * 回傳廳
     *
     * @return integer
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * 設定廳
     *
     * @param integer $domain
     * @return DepositSudaEntry
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * 回傳速達商號
     *
     * @return integer
     */
    public function getMerchantNumber()
    {
        return $this->merchantNumber;
    }

    /**
     * 設定速達商號
     *
     * @param integer $merchantNumber
     * @return DepositSudaEntry
     */
    public function setMerchantNumber($merchantNumber)
    {
        $this->merchantNumber = $merchantNumber;

        return $this;
    }

    /**
     * 回傳訂單號
     *
     * @return string
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * 設定訂單號
     *
     * @param string $orderId
     * @return DepositSudaEntry
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;

        return $this;
    }

    /**
     * 回傳速達銀行代碼
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * 設定速達銀行代碼
     *
     * @param string $code
     * @return DepositSudaEntry
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * 回傳速達商號名稱
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * 設定速達商號名稱
     *
     * @param string $alias
     * @return DepositSudaEntry
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * 回傳金額
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * 設定金額
     *
     * @param float $amount
     * @return DepositSudaEntry
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * 回傳存款優惠
     *
     * @return float
     */
    public function getOfferDeposit()
    {
        return $this->offerDeposit;
    }

    /**
     * 設定存款優惠
     *
     * @param float $offerDeposit
     * @return DepositSudaEntry
     */
    public function setOfferDeposit($offerDeposit)
    {
        $this->offerDeposit = $offerDeposit;

        return $this;
    }

    /**
     * 回傳其他優惠
     *
     * @return float
     */
    public function getOfferOther()
    {
        return $this->offerOther;
    }

    /**
     * 設定其他優惠
     *
     * @param float $offerOther
     * @return DepositSudaEntry
     */
    public function setOfferOther($offerOther)
    {
        $this->offerOther = $offerOther;

        return $this;
    }

    /**
     * 回傳銀行代碼
     *
     * @return integer
     */
    public function getBankInfoId()
    {
        return $this->bankInfoId;
    }

    /**
     * 設定銀行代碼
     *
     * @param integer $bankInfoId
     * @return DepositSudaEntry
     */
    public function setBankInfoId($bankInfoId)
    {
        $this->bankInfoId = $bankInfoId;

        return $this;
    }

    /**
     * 回傳收款人
     *
     * @return string
     */
    public function getRecipient()
    {
        return $this->recipient;
    }

    /**
     * 設定收款人
     *
     * @param string $recipient
     * @return DepositSudaEntry
     */
    public function setRecipient($recipient)
    {
        $this->recipient = $recipient;

        return $this;
    }

    /**
     * 回傳銀行帳號
     *
     * @return string
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * 設定銀行帳號
     *
     * @param string $account
     * @return DepositSudaEntry
     */
    public function setAccount($account)
    {
        $this->account = $account;

        return $this;
    }

    /**
     * 回傳手續費
     *
     * @return float
     */
    public function getFee()
    {
        return $this->fee;
    }

    /**
     * 設定手續費
     *
     * @param float $fee
     * @return DepositSudaEntry
     */
    public function setFee($fee)
    {
        $this->fee = $fee;

        return $this;
    }

    /**
     * 回傳入款速達商號ID
     *
     * @return integer
     */
    public function getMerchantSudaId()
    {
        return $this->merchantSudaId;
    }

    /**
     * 設定入款速達商號ID
     *
     * @param integer $merchantSudaId
     * @return DepositSudaEntry
     */
    public function setMerchantSudaId($merchantSudaId)
    {
        $this->merchantSudaId = $merchantSudaId;

        return $this;
    }

    /**
     * 回傳提交時間
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * 設定提交時間
     *
     * @param \DateTime $createdAt
     * @return DepositSudaEntry
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * 回傳操作人
     *
     * @return string
     */
    public function getCheckedUsername()
    {
        return $this->checkedUsername;
    }

    /**
     * 設定操作人
     *
     * @param string $checkedUsername
     * @return DepositSudaEntry
     */
    public function setCheckedUsername($checkedUsername)
    {
        $this->checkedUsername = $checkedUsername;

        return $this;
    }

    /**
     * 回傳確認時間
     *
     * @return \DateTime
     */
    public function getConfirmAt()
    {
        return $this->confirmAt;
    }

    /**
     * 確認入款
     *
     * @return DepositSudaEntry
     */
    public function confirm()
    {
        $this->confirm = true;
        $this->confirmAt = new \DateTime('now');

        return $this;
    }

    /**
     * 取消確認入款
     *
     * @return DepositSudaEntry
     */
    public function unconfirm()
    {
        $this->confirm = false;

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
     * @return DepositSudaEntry
     */
    public function cancel()
    {
        $this->cancel = true;
        $this->confirmAt = new \DateTime('now');

        return $this;
    }

    /**
     * 放棄取消入款
     *
     * @return DepositSudaEntry
     */
    public function uncancel()
    {
        $this->cancel = false;

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
     * @return DepositSudaEntry
     */
    public function setMemo($memo)
    {
        $this->memo = $memo;

        return $this;
    }

    /**
     * 回傳此物件的陣列型式
     *
     * @return array()
     */
    public function toArray()
    {
        $confirmAt = null;
        if ($this->getConfirmAt()) {
            $confirmAt = $this->getConfirmAt()->format(\DateTime::ISO8601);
        }

        return [
            'id'               => $this->getId(),
            'seq_id'           => $this->getSeqId(),
            'user_id'          => $this->getUserId(),
            'domain'           => $this->getDomain(),
            'merchant_number'  => $this->getMerchantNumber(),
            'order_id'         => $this->getOrderId(),
            'code'             => $this->getCode(),
            'alias'            => $this->getAlias(),
            'amount'           => $this->getAmount(),
            'offer_deposit'    => $this->getOfferDeposit(),
            'offer_other'      => $this->getOfferOther(),
            'bank_info_id'     => $this->getBankInfoId(),
            'recipient'        => $this->getRecipient(),
            'account'          => $this->getAccount(),
            'fee'              => $this->getFee(),
            'merchant_suda_id' => $this->getMerchantSudaId(),
            'created_at'       => $this->getCreatedAt()->format(\DateTime::ISO8601),
            'checked_username' => $this->getCheckedUsername(),
            'confirm_at'       => $confirmAt,
            'confirm'          => $this->isConfirm(),
            'cancel'           => $this->isCancel(),
            'memo'             => $this->getMemo()
        ];
    }
}
