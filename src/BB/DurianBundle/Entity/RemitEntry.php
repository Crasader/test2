<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\RemitAccount;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\BankInfo;

/**
 * 公司帳號入款記錄
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\RemitEntryRepository")
 * @ORM\Table(
 *      name = "remit_entry",
 *      indexes = {
 *          @ORM\Index(name = "idx_remit_entry_remit_account_id_created_at", columns = {"remit_account_id", "created_at"}),
 *          @ORM\Index(name = "idx_remit_entry_remit_account_id_confirm_at", columns = {"remit_account_id", "confirm_at"}),
 *          @ORM\Index(name = "idx_remit_entry_created_at", columns = {"created_at"}),
 *          @ORM\Index(name = "idx_remit_entry_confirm_at", columns = {"confirm_at"}),
 *          @ORM\Index(name = "idx_remit_entry_order_number", columns = {"order_number"}),
 *          @ORM\Index(name = "idx_remit_entry_user_id", columns = {"user_id"}),
 *          @ORM\Index(name = "idx_remit_entry_domain_created_at", columns = {"domain", "created_at"}),
 *          @ORM\Index(name = "idx_remit_entry_domain_confirm_at", columns = {"domain", "confirm_at"}),
 *      }
 * )
 */
class RemitEntry
{
    /**
     * 未處理
     */
    const UNCONFIRM = 0;

    /**
     * 確認入款
     */
    const CONFIRM = 1;

    /**
     * 取消入款
     */
    const CANCEL = 2;

    /**
     * 支援的存款方式
     *
     * @var array
     */
    public static $methods = [
        1, // 網銀轉帳
        2, // ATM自動櫃員機
        3, // ATM現金入款
        4, // 銀行櫃檯
        5, // 語音轉帳(KRW)
        6, // 支票存款(THB)
        7, // 信用卡(THB)
        8, // 手機銀行轉帳
        9, // 微信支付
        10, // 支付寶
        11, // QQ錢包
        12, // 財付通
    ];

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "integer", options = {"unsigned" = true})
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 公司帳號ID
     *
     * @var integer
     *
     * @ORM\Column(name = "remit_account_id", type = "integer", options = {"unsigned" = true})
     */
    private $remitAccountId;

    /**
     * 廳
     *
     * @var integer
     *
     * @ORM\Column(type = "integer")
     */
    private $domain;

    /**
     * 入款使用者ID
     *
     * @var integer
     *
     * @ORM\Column(name = "user_id", type = "bigint", options = {"unsigned" = true})
     */
    private $userId;

    /**
     * 操作者變更狀態的時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "confirm_at", type = "datetime", nullable = true)
     */
    private $confirmAt;

    /**
     * 入款記錄建立時間(使用者申請提交時建立)
     *
     * @var integer
     *
     * @ORM\Column(name = "created_at", type = "bigint", options = {"unsigned" = true})
     */
    private $createdAt;

    /**
     * 訂單號
     *
     * @var integer
     *
     * @ORM\Column(name = "order_number", type = "bigint")
     */
    private $orderNumber;

    /**
     * 放棄優惠
     *
     * @var boolean
     *
     * @ORM\Column(name = "abandon_discount", type = "boolean")
     */
    private $abandonDiscount;

    /**
     * 是否為自動確認
     *
     * @var boolean
     *
     * @ORM\Column(name = "auto_confirm", type = "boolean")
     */
    private $autoConfirm;

    /**
     * 自動認款平台ID
     *
     * @var integer
     *
     * @ORM\Column(name = "auto_remit_id", type = "smallint", options = {"unsigned" = true})
     */
    private $autoRemitId;

    /**
     * 入款使用者輸入的存款方式
     *
     * @var integer
     *
     * @ORM\Column(type = "smallint", options = {"unsigned" = true})
     */
    private $method;

    /**
     * 處理狀態
     *
     * @var integer
     *
     * @ORM\Column(type = "smallint", options = {"unsigned" = true})
     */
    private $status;

    /**
     * 層級Id
     *
     * @var integer
     *
     * @ORM\Column(name = "level_id", type = "integer", options = {"unsigned" = true})
     */
    private $levelId;

    /**
     * 操作所需時間(confirm_at - deposit_at)取秒數紀錄
     *
     * @var integer
     *
     * @ORM\Column(type = "integer", options = {"unsigned" = true})
     */
    private $duration;

    /**
     * 上層ID
     *
     * @var integer
     *
     * @ORM\Column(name = "ancestor_id", type = "integer")
     */
    private $ancestorId;

    /**
     * 入款使用者的銀行
     *
     * @var integer
     *
     * @ORM\Column(name = "bank_info_id", type = "integer")
     */
    private $bankInfoId;

    /**
     * 對應存款金額交易明細id
     *
     * @var int
     *
     * @ORM\Column(name = "amount_entry_id", type = "bigint")
     */
    private $amountEntryId;

    /**
     * 對應存款優惠交易明細id
     *
     * @var int
     *
     * @ORM\Column(name = "discount_entry_id", type = "bigint")
     */
    private $discountEntryId;

    /**
     * 對應其他優惠交易明細id
     *
     * @var int
     *
     * @ORM\Column(name = "other_discount_entry_id", type = "bigint")
     */
    private $otherDiscountEntryId;

    /**
     * 入款使用者輸入的存款金額
     *
     * @var float
     *
     * @ORM\Column(type = "decimal", precision = 16, scale = 4)
     */
    private $amount;

    /**
     * 存款優惠
     *
     * @var float
     *
     * @ORM\Column(type = "decimal", precision = 16, scale = 4)
     */
    private $discount;

    /**
     * 其他優惠
     *
     * @var float
     *
     * @ORM\Column(name = "other_discount", type = "decimal", precision = 16, scale = 4)
     */
    private $otherDiscount;

    /**
     * 實際其他優惠
     *
     * @var float
     *
     * @ORM\Column(name = "actual_other_discount", type = "decimal", precision = 16, scale = 4)
     */
    private $actualOtherDiscount;

    /**
     * 入款匯率
     *
     * @var float
     *
     * @ORM\Column(type = "decimal", precision = 16, scale = 8)
     */
    private $rate;

    /**
     * 入款使用者輸入的存款時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "deposit_at", type = "datetime", nullable = true)
     */
    private $depositAt;

    /**
     * 交易流水號
     *
     * @var string
     *
     * @ORM\Column(name = "trade_number", type = "string", length = 18)
     *
     */
    private $tradeNumber;

    /**
     * 轉帳交易代碼
     *
     * @var string
     *
     * @ORM\Column(name = "transfer_code", type = "string", length = 18)
     *
     */
    private $transferCode;

    /**
     * ATM編碼
     *
     * @var string
     *
     * @ORM\Column(name = "atm_terminal_code", type = "string", length = 18)
     *
     */
    private $atmTerminalCode;

    /**
     * 身分證號碼
     *
     * @var string
     *
     * @ORM\Column(name = "identity_card", type = "string", length = 18)
     *
     */
    private $identityCard;

    /**
     * 舊訂單號
     *
     * @var string
     *
     * @ORM\Column(name = "old_order_number", type = "string", length = 20)
     */
    private $oldOrderNumber;

    /**
     * 手機號碼
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 20)
     *
     */
    private $cellphone;

    /**
     * 使用者帳號
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 30)
     */
    private $username;

    /**
     * 付款人卡號
     *
     * @var string
     *
     * @ORM\Column(name = "payer_card", type = "string", length = 30)
     *
     */
    private $payerCard;

    /**
     * 操作者
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 30)
     *
     */
    private $operator;

    /**
     * 入款使用者輸入的存款人姓名
     *
     * @var string
     *
     * @ORM\Column(name = "name_real", type = "string", length = 32)
     */
    private $nameReal;

    /**
     * 入款使用者輸入的分行
     * 部分存款方式會記錄分行資料
     * ex.ATM相關方式(2.3) & 銀行櫃檯(4)
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 64, nullable = true)
     */
    private $branch;

    /**
     * 備註
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 255)
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
     * @param RemitAccount $account 公司入款帳號
     * @param User $user 入款使用者
     * @param BankInfo $bankInfo 入款使用者所用銀行
     */
    public function __construct(RemitAccount $account, User $user, BankInfo $bankInfo)
    {
        $createAt = new \DateTime('now');

        $this->remitAccountId = $account->getId();
        $this->domain = $account->getDomain();
        $this->userId = $user->getId();
        $this->createdAt = $createAt->format('YmdHis');
        $this->orderNumber = 0;
        $this->abandonDiscount = false;
        $this->autoConfirm = $account->isAutoConfirm();
        $this->autoRemitId = $account->getAutoRemitId();
        $this->method = 0;
        $this->status = self::UNCONFIRM;
        $this->levelId = 0;
        $this->duration = 0;
        $this->ancestorId = 0;
        $this->bankInfoId = $bankInfo->getId();
        $this->amountEntryId = 0;
        $this->discountEntryId = 0;
        $this->otherDiscountEntryId = 0;
        $this->amount = 0;
        $this->discount = 0;
        $this->otherDiscount = 0;
        $this->actualOtherDiscount = 0;
        $this->rate = 1;
        $this->tradeNumber = '';
        $this->transferCode = '';
        $this->atmTerminalCode = '';
        $this->identityCard = '';
        $this->oldOrderNumber = '';
        $this->cellphone = '';
        $this->username = $user->getUsername();
        $this->payerCard = '';
        $this->operator = '';
        $this->nameReal = '';
        $this->branch = '';
        $this->memo = '';
    }

    /**
     * 設定ID
     *
     * @param integer $id
     * @return RemitEntry
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * 回傳ID
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 回傳公司帳號ID
     *
     * @return integer
     */
    public function getRemitAccountId()
    {
        return $this->remitAccountId;
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
     * 回傳入款使用者ID
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * 設定變更狀態的時間
     *
     * @param \DateTime $confirmAt
     * @return RemitEntry
     */
    public function setConfirmAt($confirmAt)
    {
        $this->confirmAt = $confirmAt;

        return $this;
    }

    /**
     * 回傳變更狀態的時間
     *
     * @return \DateTime
     */
    public function getConfirmAt()
    {
        return $this->confirmAt;
    }

    /**
     * 設定變更狀態的時間
     *
     * @param integer $createdAt
     * @return RemitEntry
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * 回傳入款記錄建立(提交)時間
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return new \DateTime($this->createdAt);
    }

    /**
     * 設定訂單號
     *
     * @param integer $orderNumber
     * @return RemitEntry
     */
    public function setOrderNumber($orderNumber)
    {
        $this->orderNumber = $orderNumber;

        return $this;
    }

    /**
     * 回傳訂單號
     *
     * @return integer
     */
    public function getOrderNumber()
    {
        return $this->orderNumber;
    }

    /**
     * 放棄優惠
     *
     * @return RemitEntry
     */
    public function abandonDiscount()
    {
        $this->abandonDiscount = true;

        return $this;
    }

    /**
     * 回傳是否放棄優惠
     *
     * @return boolean
     */
    public function isAbandonDiscount()
    {
        return $this->abandonDiscount;
    }

    /**
     * 是否為自動確認
     *
     * @return boolean
     */
    public function isAutoConfirm()
    {
        return $this->autoConfirm;
    }

    /**
     * 回傳自動認款平台ID
     *
     * @return integer
     */
    public function getAutoRemitId()
    {
        return $this->autoRemitId;
    }

    /**
     * 設定存款方式
     *
     * @param integer $method
     * @return RemitEntry
     */
    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * 回傳存款方式
     *
     * @return integer
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * 設定處理狀態
     *
     * @param integer $status 狀態
     * @return RemitEntry
     */
    public function setStatus($status)
    {
        $this->status = $status;
        $this->confirmAt = new \DateTime('now');

        return $this;
    }

    /**
     * 回傳處理狀態
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * 設定層級Id
     *
     * @param integer $levelId
     * @return RemitEntry
     */
    public function setLevelId($levelId)
    {
        $this->levelId = $levelId;

        return $this;
    }

    /**
     * 回傳層級Id
     *
     * @return integer
     */
    public function getLevelId()
    {
        return $this->levelId;
    }

    /**
     * 設定操作所需時間
     *
     * @param integer $duration
     * @return RemitEntry
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * 回傳操作所需時間
     *
     * @return integer
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * 設定入款使用者的上層ID
     *
     * @param integer $ancestorId
     * @return RemitEntry
     */
    public function setAncestorId($ancestorId)
    {
        $this->ancestorId = $ancestorId;

        return $this;
    }

    /**
     * 回傳入款使用者的上層ID
     *
     * @return integer
     */
    public function getAncestorId()
    {
        return $this->ancestorId;
    }

    /**
     * 回傳入款使用者的銀行
     *
     * @return integer
     */
    public function getBankInfoId()
    {
        return $this->bankInfoId;
    }

    /**
     * 設定存款金額交易明細id
     *
     * @param integer $entryId
     * @return RemitEntry
     */
    public function setAmountEntryId($entryId)
    {
        $this->amountEntryId = $entryId;

        return $this;
    }

    /**
     * 回傳存款金額交易明細id
     *
     * @return integer
     */
    public function getAmountEntryId()
    {
        return $this->amountEntryId;
    }

    /**
     * 設定存款優惠交易明細id
     *
     * @param integer $entryId
     * @return RemitEntry
     */
    public function setDiscountEntryId($entryId)
    {
        $this->discountEntryId = $entryId;

        return $this;
    }

    /**
     * 回傳存款優惠交易明細id
     *
     * @return integer
     */
    public function getDiscountEntryId()
    {
        return $this->discountEntryId;
    }

    /**
     * 設定其他優惠交易明細id
     *
     * @param integer $entryId
     * @return RemitEntry
     */
    public function setOtherDiscountEntryId($entryId)
    {
        $this->otherDiscountEntryId = $entryId;

        return $this;
    }

    /**
     * 回傳其他優惠交易明細id
     *
     * @return integer
     */
    public function getOtherDiscountEntryId()
    {
        return $this->otherDiscountEntryId;
    }

    /**
     * 設定存款金額
     *
     * @param float $amount
     * @return RemitEntry
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * 回傳存款金額
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
     * 設定存款優惠
     *
     * @param float $amount
     * @return RemitEntry
     */
    public function setDiscount($amount)
    {
        $this->discount = $amount;

        return $this;
    }

    /**
     * 回傳存款優惠
     *
     * @return float
     */
    public function getDiscount()
    {
        return $this->discount;
    }

    /**
     * 設定其他優惠
     *
     * @param float $amount
     * @return RemitEntry
     */
    public function setOtherDiscount($amount)
    {
        $this->otherDiscount = $amount;

        return $this;
    }

    /**
     * 回傳其他優惠
     *
     * @return float
     */
    public function getOtherDiscount()
    {
        return $this->otherDiscount;
    }

    /**
     * 設定實際其他優惠
     *
     * @param float $amount
     * @return RemitEntry
     */
    public function setActualOtherDiscount($amount)
    {
        $this->actualOtherDiscount = $amount;

        return $this;
    }

    /**
     * 回傳實際其他優惠
     *
     * @return float
     */
    public function getActualOtherDiscount()
    {
        return $this->actualOtherDiscount;
    }

    /**
     * 設定入款匯率
     *
     * @param float $rate
     * @return RemitEntry
     */
    public function setRate($rate)
    {
        $this->rate = $rate;

        return $this;
    }

    /**
     * 回傳入款匯率
     *
     * @return float
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * 設定入款時間
     *
     * @param \DateTime $depositAt
     * @return RemitEntry
     */
    public function setDepositAt($depositAt)
    {
        $this->depositAt = $depositAt;

        return $this;
    }

    /**
     * 回傳入款時間
     *
     * @return \DateTime
     */
    public function getDepositAt()
    {
        return $this->depositAt;
    }

    /**
     * 設定匯款名細記錄的交易流水號
     *
     * @param string $tradeNumber 交易流水號
     * @return RemitEntry
     */
    public function setTradeNumber($tradeNumber)
    {
        $this->tradeNumber = $tradeNumber;

        return $this;
    }

    /**
     * 回傳匯款名細記錄的交易流水號
     *
     * @return string
     */
    public function getTradeNumber()
    {
        return $this->tradeNumber;
    }

    /**
     * 設定匯款名細記錄的轉帳交易代碼
     *
     * @param string $transferCode 轉帳交易代碼
     * @return RemitEntry
     */
    public function setTransferCode($transferCode)
    {
        $this->transferCode = $transferCode;

        return $this;
    }

    /**
     * 設定匯款名細記錄的轉帳交易代碼
     *
     * @return string
     */
    public function getTransferCode()
    {
        return $this->transferCode;
    }

    /**
     * 設定匯款名細記錄的備註
     *
     * @param string $atmTerminalCode ATM編碼
     * @return RemitEntry
     */
    public function setAtmTerminalCode($atmTerminalCode)
    {
        $this->atmTerminalCode = $atmTerminalCode;

        return $this;
    }

    /**
     * 回傳匯款名細記錄的ATM編碼
     *
     * @return string
     */
    public function getAtmTerminalCode()
    {
        return $this->atmTerminalCode;
    }

    /**
     * 設定匯款名細記錄的身分證號碼
     *
     * @param string $identityCard 身分證號碼
     * @return RemitEntry
     */
    public function setIdentityCard($identityCard)
    {
        $this->identityCard = $identityCard;

        return $this;
    }

    /**
     * 回傳匯款名細記錄的身分證號碼
     *
     * @return string
     */
    public function getIdentityCard()
    {
        return $this->identityCard;
    }

    /**
     * 設定舊訂單號
     *
     * @param string $oldOrderNumber
     * @return RemitEntry
     */
    public function setOldOrderNumber($oldOrderNumber)
    {
        $this->oldOrderNumber = $oldOrderNumber;

        return $this;
    }

    /**
     * 回傳舊訂單號
     *
     * @return string
     */
    public function getOldOrderNumber()
    {
        return $this->oldOrderNumber;
    }

    /**
     * 設定匯款名細記錄的手機號碼
     *
     * @param string $cellphone 手機號碼
     * @return RemitEntry
     */
    public function setCellphone($cellphone)
    {
        $this->cellphone = $cellphone;

        return $this;
    }

    /**
     * 回傳匯款名細記錄的手機號碼
     *
     * @return string
     */
    public function getCellphone()
    {
        return $this->cellphone;
    }

    /**
     * 回傳使用者帳號
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * 設定匯款名細記錄的付款人卡號
     *
     * @param string $payerCard 付款人卡號
     * @return RemitEntry
     */
    public function setPayerCard($payerCard)
    {
        $this->payerCard = $payerCard;

        return $this;
    }

    /**
     * 回傳匯款名細記錄的付款人卡號
     *
     * @return string
     */
    public function getPayerCard()
    {
        return $this->payerCard;
    }

    /**
     * 設定變動處理狀態的操作者
     *
     * @param string $operator 操作者
     * @return RemitEntry
     */
    public function setOperator($operator)
    {
        $this->operator = $operator;

        return $this;
    }

    /**
     * 回傳變動處理狀態的操作者
     *
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * 設定存款人姓名
     *
     * @param string $nameReal
     * @return RemitEntry
     */
    public function setNameReal($nameReal)
    {
        $this->nameReal = $nameReal;

        return $this;
    }

    /**
     * 回傳存款人姓名
     *
     * @return string
     */
    public function getNameReal()
    {
        return $this->nameReal;
    }

    /**
     * 設定存款分行
     *
     * @param string $branch
     * @return RemitEntry
     */
    public function setBranch($branch)
    {
        $this->branch = $branch;

        return $this;
    }

    /**
     * 回傳存款分行
     *
     * @return string
     */
    public function getBranch()
    {
        return $this->branch;
    }

    /**
     * 設定匯款名細記錄的備註
     *
     * @param string $memo 備註
     * @return RemitEntry
     */
    public function setMemo($memo)
    {
        $this->memo = $memo;

        return $this;
    }

    /**
     * 回傳匯款名細記錄的備註
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
        $depositAt = null;
        $confirmAt = null;

        if ($this->getDepositAt()) {
            $depositAt = $this->getDepositAt()->format(\DateTime::ISO8601);
        }

        if ($this->getConfirmAt()) {
            $confirmAt = $this->getConfirmAt()->format(\DateTime::ISO8601);
        }

        return [
            'id' => $this->getId(),
            'remit_account_id' => $this->getRemitAccountId(),
            'domain' => $this->getDomain(),
            'user_id' => $this->getUserId(),
            'confirm_at' => $confirmAt,
            'created_at' => $this->getCreatedAt()->format(\DateTime::ISO8601),
            'order_number' => $this->getOrderNumber(),
            'abandon_discount' => $this->isAbandonDiscount(),
            'auto_confirm' => $this->isAutoConfirm(),
            'auto_remit_id' => $this->getAutoRemitId(),
            'method' => $this->getMethod(),
            'status' => $this->getStatus(),
            'level_id' => $this->getLevelId(),
            'duration' => $this->getDuration(),
            'ancestor_id' => $this->getAncestorId(),
            'bank_info_id' => $this->getBankInfoId(),
            'amount_entry_id' => $this->getAmountEntryId(),
            'discount_entry_id' => $this->getDiscountEntryId(),
            'other_discount_entry_id' => $this->getOtherDiscountEntryId(),
            'amount' => $this->getAmount(),
            'discount' => $this->getDiscount(),
            'other_discount' => $this->getOtherDiscount(),
            'actual_other_discount' => $this->getActualOtherDiscount(),
            'rate' => $this->getRate(),
            'deposit_at' => $depositAt,
            'trade_number' => $this->getTradeNumber(),
            'transfer_code' => $this->getTransferCode(),
            'atm_terminal_code' => $this->getAtmTerminalCode(),
            'identity_card' => $this->getIdentityCard(),
            'old_order_number' => $this->getOldOrderNumber(),
            'cellphone' => $this->getCellphone(),
            'username' => $this->getUsername(),
            'payer_card' => $this->getPayerCard(),
            'operator' => $this->getOperator(),
            'name_real' => $this->getNameReal(),
            'branch' => $this->getBranch(),
            'memo' => $this->getMemo(),
        ];
    }
}
