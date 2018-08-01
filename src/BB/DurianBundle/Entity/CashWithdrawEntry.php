<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Currency;
use BB\DurianBundle\Entity\Cash;

/**
 * 出款紀錄明細
 * 因需新增欄位, 會造成執行時間過久, 故採用新增一個table的方式
 * 但doctrine限制不同table間index name不能相同, 所以index name加上_2
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\CashWithdrawEntryRepository")
 * @ORM\Table(name = "cash_withdraw_entry",
 *      indexes={
 *          @ORM\Index(name = "idx_cash_withdraw_entry_at_2", columns = {"at"}),
 *          @ORM\Index(name = "idx_cash_withdraw_entry_cash_id_2", columns = {"cash_id"}),
 *          @ORM\Index(name = "idx_cash_withdraw_entry_confirm_at_2", columns = {"confirm_at"}),
 *          @ORM\Index(name = "idx_cash_withdraw_entry_domain_at_2", columns = {"domain", "at"}),
 *          @ORM\Index(name = "idx_cash_withdraw_entry_user_id_at_2", columns = {"user_id", "at"})
 *      }
 * )
 */
class CashWithdrawEntry
{
    /**
     * 未處理
     */
    const UNTREATED = 0;

    /**
     * 確認出款
     */
    const CONFIRM = 1;

    /**
     * 取消出款
     */
    const CANCEL = 2;

    /**
     * 拒絕出款
     */
    const REJECT = 3;

    /**
     * 鎖定
     */
    const LOCK = 4;

    /**
     * 系統鎖定
     */
    const SYSTEM_LOCK = 5;

    /**
     * 處理中
     */
    const PROCESSING = 6;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "bigint")
     */
    private $id;

    /**
     * 出款時間(新)
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type = "bigint", options = {"unsigned" = true})
     */
    private $at;

    /**
     * 對應的現金帳號id
     *
     * @var integer
     *
     * @ORM\Column(name = "cash_id", type = "integer")
     */
    private $cashId;

    /**
     * 確認時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "confirm_at", type = "datetime", nullable = true)
     */
    private $confirmAt;

    /**
     * 登入站別
     *
     * @var integer
     *
     * @ORM\Column(name = "domain", type = "integer")
     */
    private $domain;

    /**
     * 使用者Id
     *
     * @var integer
     *
     * @ORM\Column(name = "user_id", type = "integer")
     */
    private $userId;

    /**
     * 首次出款
     *
     * @var boolean
     *
     * @ORM\Column(name = "first", type = "boolean")
     */
    private $first;

    /**
     * 與上筆同使用者出款明細詳細資料真實姓名是否相符
     *
     * @var boolean
     *
     * @ORM\Column(name = "detail_modified", type = "boolean")
     */
    private $detailModified;

    /**
     * 是否自動出款
     *
     * @var boolean
     *
     * @ORM\Column(name = "auto_withdraw", type = "boolean")
     */
    private $autoWithdraw;

    /**
     * 幣別
     *
     * @var integer
     *
     * @ORM\Column(type = "smallint", options = {"unsigned" = true})
     */
    private $currency;

    /**
     * 狀態
     *
     * @var integer
     *
     * @ORM\Column(name = "status", type = "smallint")
     */
    private $status;

    /**
     * 會員層級
     *
     * @var integer
     *
     * @ORM\Column(name = "level_id", type = "integer", options = {"unsigned" = true})
     */
    private $levelId;

    /**
     * 出款商家Id
     *
     * @var integer
     *
     * @ORM\Column(name = "merchant_withdraw_id", type = "integer", options = {"unsigned" = true, "default" = 0})
     */
    private $merchantWithdrawId;

    /**
     * 上一筆出款明細ID
     *
     * @var integer
     *
     * @ORM\Column(name = "previous_id", type = "bigint")
     */
    private $previousId;

    /**
     * 參考編號(對應交易明細)
     *
     * @var integer
     *
     * @ORM\Column(name = "entry_id", type = "bigint", nullable = true)
     */
    private $entryId;

    /**
     * 出款金額
     *
     * @var float
     *
     * @ORM\Column(name = "amount", type = "decimal", precision = 16, scale = 4)
     */
    private $amount;

    /**
     * 手續費
     *
     * @var float
     *
     * @ORM\Column(name = "fee", type = "decimal", precision = 16, scale = 4)
     */
    private $fee;

    /**
     * 常態稽核手續費
     *
     * @var float
     *
     * @ORM\Column(name = "aduit_fee", type = "decimal", precision = 16, scale = 4)
     */
    private $aduitFee;

    /**
     * 常態稽核行政費用
     *
     * @var float
     *
     * @ORM\Column(name ="aduit_charge", type = "decimal", precision = 16, scale = 4)
     */
    private $aduitCharge;

    /**
     * 優惠扣除
     *
     * @var float
     *
     * @ORM\Column(name = "deduction", type = "decimal", precision = 16, scale = 4)
     */
    private $deduction;

    /**
     * 實際出款金額
     *
     * @var float
     *
     * @ORM\Column(name = "real_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $realAmount;

    /**
     * 支付平台手續費
     *
     * @var float
     *
     * @ORM\Column(name = "payment_gateway_fee", type = "decimal", precision = 16, scale = 4)
     */
    private $paymentGatewayFee;

    /**
     * 自動出款金額
     *
     * @var float
     *
     * @ORM\Column(name = "auto_withdraw_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $autoWithdrawAmount;

    /**
     * 匯率
     *
     * @var float
     *
     * @ORM\Column(name = "rate", type = "decimal", precision = 16, scale = 8)
     */
    private $rate;

    /**
     * 出款時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "created_at", type = "datetime")
     */
    private $createdAt;

    /**
     * 聯絡電話
     *
     * @var string
     *
     * @ORM\Column(name = "telephone", type = "string", length = 20)
     */
    private $telephone;

    /**
     * 會員ip
     *
     * @var string
     *
     * @ORM\Column(name = "ip", type = "string", length = 25)
     */
    private $ip;

    /**
     * 確認使用者名稱
     *
     * @var string
     *
     * @ORM\Column(name = "checked_username", type = "string", length = 30, nullable = true)
     */
    private $checkedUsername;

    /**
     * 銀行帳號
     *
     * @var string
     *
     * @ORM\Column(name = "account", type = "string", length = 42)
     */
    private $account;

    /**
     * 支行
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 64)
     */
    private $branch;

    /**
     * 使用者真實姓名
     *
     * @var string
     *
     * @ORM\Column(name = "name_real", type = "string", length = 100)
     */
    private $nameReal;

    /**
     * 帳戶持卡人
     *
     * @var string
     *
     * @ORM\Column(name = "account_holder", type = "string", length = 100)
     */
    private $accountHolder;

    /**
     * 銀行開戶省份
     *
     * @var string
     *
     * @ORM\Column(name = "province", type = "string", length = 100)
     */
    private $province;

    /**
     * 銀行開戶城市
     *
     * @var string
     *
     * @ORM\Column(name = "city", type = "string", length = 100)
     */
    private $city;

    /**
     * 參考編號(對應支付平台)
     *
     * @var string
     *
     * @ORM\Column(name = "ref_id", type = "string", length = 100)
     */
    private $refId;

    /**
     * 銀行名稱
     *
     * @var string
     *
     * @ORM\Column(name = "bank_name", type = "string", length = 255)
     */
    private $bankName;

    /**
     * 備註
     *
     * @var string
     *
     * @ORM\Column(name = "memo", type = "string", length = 500)
     */
    private $memo;

    /**
     * note(紀錄user detail當下的note)
     *
     * @var String
     * @ORM\Column(name = "note", type = "string", length = 150, options = {"default" = ""})
     */
    private $note = '';

    /**
     * 版本號
     *
     * @var integer
     *
     * @ORM\Column(type = "integer", options = {"unsigned" = true})
     * @ORM\Version
     */
    private $version;

    /**
     * 新增一筆現金的出款紀錄
     *
     * @param Cash    $cash 對應的現金
     * @param float   $amount 出款金額
     * @param float   $fee 手續費
     * @param float   $deduction 優惠扣除
     * @param float   $aduitCharge 常態稽核行政費用
     * @param float   $aduitFee 常態稽核手續費
     * @param float $paymentGatewayFee 支付平台手續費
     * @param string  $ip 登入ip
     */
    public function __construct(
        Cash $cash,
        $amount,
        $fee,
        $deduction,
        $aduitCharge,
        $aduitFee,
        $paymentGatewayFee,
        $ip
    ) {
        $createAt = new \DateTime('now');

        $this->cashId = $cash->getId();
        $this->at = $createAt->format('YmdHis');
        $this->userId = $cash->getUser()->getId();
        $this->currency = $cash->getCurrency();
        $this->amount = $amount;
        $this->fee = $fee;
        $this->deduction = $deduction;
        $this->aduitCharge = $aduitCharge;
        $this->aduitFee = $aduitFee;
        $this->realAmount = $amount - $fee - $deduction - $aduitCharge - $aduitFee;
        $this->paymentGatewayFee = $paymentGatewayFee;
        $this->autoWithdrawAmount = $this->realAmount - $this->paymentGatewayFee;
        $this->ip = $ip;
        $this->createdAt = $createAt;
        $this->memo = '';
        $this->status = self::UNTREATED;
        $this->first = false;
        $this->detailModified = false;
        $this->previousId = 0;
        $this->nameReal = '';
        $this->telephone = '';
        $this->bankName = '';
        $this->account = '';
        $this->branch = '';
        $this->accountHolder = '';
        $this->province = '';
        $this->city = '';
        $this->autoWithdraw = false;
        $this->levelId = 0;
        $this->merchantWithdrawId = 0;
        $this->refId = '';
        $this->note = '';
    }

    /**
     * 設定id
     *
     * @param integer $id
     * @return CashWithdrawEntry
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * 回傳id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 回傳at
     *
     * @return \DateTime
     */
    public function getAt()
    {
        return new \DateTime($this->at);
    }

    /**
     * 回傳對應的現金id
     *
     * @return integer
     */
    public function getCashId()
    {
        return $this->cashId;
    }

    /**
     * 設定確認時間
     *
     * @param \DateTime
     * @return CashWithdrawEntry
     */
    public function setConfirmAt(\Datetime $confirmAt)
    {
        $this->confirmAt = $confirmAt;

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
     * 設定廳主id
     *
     * @param integer $domain
     * @return CashWithdrawEntry
     */
    public function setDomain($domain)
    {
        if ($this->domain != null) {
            return $this;
        }

        $this->domain = $domain;

        return $this;
    }

    /**
     * 回傳廳主id
     *
     * @return integer
     */
    public function getDomain()
    {
        return $this->domain;
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
     * 首次出款
     *
     * @return CashWithdrawEntry
     */
    public function first()
    {
        $this->first = true;

        return $this;
    }

    /**
     * 是否為首次出款
     *
     * @return boolean
     */
    public function isFirst()
    {
        return (bool) $this->first;
    }

    /**
     * 設定為上筆同使用者出款明細詳細資料真實姓名被修改過
     *
     * @return CashWithdrawEntry
     */
    public function detailModified()
    {
        $this->detailModified = true;

        return $this;
    }

    /**
     * 上筆同使用者出款明細詳細資料真實姓名被修改過
     *
     * @return boolean
     */
    public function isDetailModified()
    {
        return $this->detailModified;
    }

    /**
     * 設定自動出款
     *
     * @param boolean $autoWithdraw
     * @return CashWithdrawEntry
     */
    public function setAutoWithdraw($autoWithdraw)
    {
        $this->autoWithdraw = (bool) $autoWithdraw;

        return $this;
    }

    /**
     * 是否為自動出款
     *
     * @return boolean
     */
    public function isAutoWithdraw()
    {
        return (bool) $this->autoWithdraw;
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
     * 設定狀態
     *
     * @param integer $status
     * @return CashWithdrawEntry
     */
    public function setStatus($status)
    {
        $this->status = $status;
        $this->confirmAt = new \DateTime('now');

        return $this;
    }

    /**
     * 回傳狀態
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * 設定會員層級
     *
     * @param integer $levelId
     * @return CashWithdrawEntry
     */
    public function setLevelId($levelId)
    {
        if (!$this->levelId) {
            $this->levelId = $levelId;
        }

        return $this;
    }

    /**
     * 回傳會員層級
     *
     * @return integer
     */
    public function getLevelId()
    {
        return $this->levelId;
    }

    /**
     * 設定出款商家Id
     *
     * @param integer $merchantWithdrawId
     * @return CashWithdrawEntry
     */
    public function setMerchantWithdrawId($merchantWithdrawId)
    {
        $this->merchantWithdrawId = $merchantWithdrawId;

        return $this;
    }

    /**
     * 回傳出款商家Id
     *
     * @return integer
     */
    public function getMerchantWithdrawId()
    {
        if (!$this->merchantWithdrawId) {
            return null;
        }

        return $this->merchantWithdrawId;
    }

    /**
     * 設定上一筆出款明細ID
     *
     * @param integer $entryId
     * @return CashWithdrawEntry
     */
    public function setPreviousId($entryId)
    {
        if ($this->previousId != 0) {
            return $this;
        }

        $this->previousId = $entryId;

        return $this;
    }

    /**
     * 回傳上一筆出款明細ID
     *
     * @return integer
     */
    public function getPreviousId()
    {
        return $this->previousId;
    }

    /**
     * 設定參考編號(對應交易明細)
     *
     * @param integer $entryId
     * @return CashWithdrawEntry
     */
    public function setEntryId($entryId)
    {
        $this->entryId = $entryId;

        return $this;
    }

    /**
     * 回傳參考編號(對應交易明細)
     *
     * @return string
     */
    public function getEntryId()
    {
        return $this->entryId;
    }

    /**
     * 回傳出款金額
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
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
     * 回傳常態稽核手續費
     *
     * @return float
     */
    public function getAduitFee()
    {
        return $this->aduitFee;
    }

    /**
     * 回傳常態稽核行政費用
     *
     * @return float
     */
    public function getAduitCharge()
    {
        return $this->aduitCharge;
    }

    /**
     * 回傳優惠扣除
     *
     * @return float
     */
    public function getDeduction()
    {
        return $this->deduction;
    }

    /**
     * 設定實際出款金額
     *
     * @param float $realAmount
     * @return CashWithdrawEntry
     */
    public function setRealAmount($realAmount)
    {
        $this->realAmount = $realAmount;

        return $this;
    }

    /**
     * 回傳實際出款金額
     *
     * @return float
     */
    public function getRealAmount()
    {
        return $this->realAmount;
    }

    /**
     * 設定支付平台手續費
     *
     * @param float $paymentGatewayFee
     * @return CashWithdrawEntry
     */
    public function setPaymentGatewayFee($paymentGatewayFee)
    {
        $this->paymentGatewayFee = $paymentGatewayFee;

        return $this;
    }

    /**
     * 回傳支付平台手續費
     *
     * @return float
     */
    public function getPaymentGatewayFee()
    {
        return $this->paymentGatewayFee;
    }

    /**
     * 設定自動出款金額
     *
     * @param float $autoWithdrawAmount
     * @return CashWithdrawEntry
     */
    public function setAutoWithdrawAmount($autoWithdrawAmount)
    {
        $this->autoWithdrawAmount = $autoWithdrawAmount;

        return $this;
    }

    /**
     * 回傳自動出款金額
     *
     * @return float
     */
    public function getAutoWithdrawAmount()
    {
        return $this->autoWithdrawAmount;
    }

    /**
     * 設定匯率
     *
     * @param float $rate
     * @return CashWithdrawEntry
     */
    public function setRate($rate)
    {
        $this->rate = $rate;

        return $this;
    }

    /**
     * 回傳匯率
     *
     * @return float
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * 設定出款時間
     *
     * @param \DateTime $createdAt
     * @return CashWithdrawEntry
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
        $this->at = $createdAt->format('YmdHis');

        return $this;
    }

    /**
     * 回傳出款時間
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * 設定電話號碼
     *
     * @param string $telephone
     * @return CashWithdrawEntry
     */
    public function setTelephone($telephone)
    {
        if ($this->telephone != '') {
            return $this;
        }

        $this->telephone = $telephone;

        return $this;
    }

    /**
     * 取得電話號碼
     *
     * @return string
     */
    public function getTelephone()
    {
        return $this->telephone;
    }

    /**
     * 回傳ip
     *
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * 設定確認使用者名稱
     *
     * @param string $username
     * @return CashWithdrawEntry
     */
    public function setCheckedUsername($username)
    {
        $this->checkedUsername = $username;

        return $this;
    }

    /**
     * 回傳確認使用者名稱
     *
     * @return string
     */
    public function getCheckedUsername()
    {
        return $this->checkedUsername;
    }

    /**
     * 設定銀行帳號
     *
     * @param string $account
     * @return CashWithdrawEntry
     */
    public function setAccount($account)
    {
        if ($this->account == '') {
            $this->account = $account;
        }

        return $this;
    }

    /**
     * 取得銀行帳號
     *
     * @return string
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * 設定銀行支行
     *
     * @param string $branch
     * @return CashWithdrawEntry
     */
    public function setBranch($branch)
    {
        $this->branch = $branch;

        return $this;
    }

    /**
     * 取得銀行支行
     *
     * @return string
     */
    public function getBranch()
    {
        return $this->branch;
    }

    /**
     * 設定真實姓名
     *
     * @param string $name
     * @return CashWithdrawEntry
     */
    public function setNameReal($name)
    {
        if ($this->nameReal != '') {
            return $this;
        }

        $this->nameReal = $name;

        return $this;
    }

    /**
     * 取得真實姓名
     *
     * @return string
     */
    public function getNameReal()
    {
        return $this->nameReal;
    }

    /**
     * 設定帳號持卡人
     *
     * @param string $accountHolder
     * @return CashWithdrawEntry
     */
    public function setAccountHolder($accountHolder)
    {
        $this->accountHolder = $accountHolder;

        return $this;
    }

    /**
     * 取得帳號持卡人
     *
     * @return string
     */
    public function getAccountHolder()
    {
        return $this->accountHolder;
    }

    /**
     * 設定開戶省份
     *
     * @param string $province
     * @return CashWithdrawEntry
     */
    public function setProvince($province)
    {
        if ($this->province == '') {
            $this->province = $province;
        }

        return $this;
    }

    /**
     * 取得開戶省份
     *
     * @return string
     */
    public function getProvince()
    {
        return $this->province;
    }

    /**
     * 設定開戶城市
     *
     * @param string $city
     * @return CashWithdrawEntry
     */
    public function setCity($city)
    {
        if ($this->city == '') {
            $this->city = $city;
        }

        return $this;
    }

    /**
     * 取得開戶城市
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * 設定參考編號(對應支付平台)
     *
     * @param string $refId
     * @return CashDepositEntryNew
     */
    public function setRefId($refId)
    {
        $this->refId = $refId;

        return $this;
    }

    /**
     * 取得參考編號(對應支付平台)
     *
     * @return string
     */
    public function getRefId()
    {
        return $this->refId;
    }

    /**
     * 設定銀行名稱
     *
     * @param string $bankName
     * @return CashWithdrawEntry
     */
    public function setBankName($bankName)
    {
        if ($this->bankName == '') {
            $this->bankName = $bankName;
        }

        return $this;
    }

    /**
     * 取得銀行名稱
     *
     * @return string
     */
    public function getBankName()
    {
        return $this->bankName;
    }

    /**
     * 設定備註
     *
     * @param string $memo
     * @return CashWithdrawEntry
     */
    public function setMemo($memo)
    {
        $this->memo = $memo;

        return $this;
    }

    /**
     * 回傳備註
     *
     * @return strimg
     */
    public function getMemo()
    {
        return $this->memo;
    }

    /**
     * 設定note
     *
     * @param string $note
     * @return CashWithdrawEntry
     */
    public function setNote($note)
    {
        $this->note = $note;

        return $this;
    }

    /**
     * 回傳note
     *
     * @return strimg
     */
    public function getNote()
    {
        return $this->note;
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
        $aduitChargeConv = $this->getAduitCharge() * $this->rate; // 轉匯後常態稽核行政費用
        $aduitFeeConv = $this->getAduitFee() * $this->rate; // 轉匯後常態稽核手續費
        $realAmountConv = $this->getRealAmount() * $this->rate; // 轉匯後真實出款金額
        $paymentGatewayFeeConv = $this->getPaymentGatewayFee() * $this->rate; // 轉匯後支付平台手續費
        $autoWithdrawAmountConv = $this->getAutoWithdrawAmount() * $this->rate; // 轉匯後自動出款金額

        return [
            'id' => $this->getId(),
            'cash_id' => $this->getCashId(),
            'user_id' => $this->getUserId(),
            'currency' => $currencyOperator->getMappedCode($this->getCurrency()),
            'domain' => $this->getDomain(),
            'amount' => $this->getAmount(),
            'fee' => $this->getFee(),
            'deduction' => $this->getDeduction(),
            'aduit_charge' => $this->getAduitCharge(),
            'aduit_fee' => $this->getAduitFee(),
            'real_amount' => $this->getRealAmount(),
            'payment_gateway_fee' => $this->getPaymentGatewayFee(),
            'auto_withdraw_amount' => $this->getAutoWithdrawAmount(),
            'first' => $this->isFirst(),
            'detail_modified' => $this->isDetailModified(),
            'ip' => $this->getIp(),
            'memo' => $this->getMemo(),
            'level_id' => $this->getLevelId(),
            'name_real' => $this->getNameReal(),
            'telephone' => $this->getTelephone(),
            'bank_name' => $this->getBankName(),
            'account' => $this->getAccount(),
            'branch' => $this->getBranch(),
            'account_holder' => $this->getAccountHolder(),
            'province' => $this->getProvince(),
            'city' => $this->getCity(),
            'status' => $this->getStatus(),
            'confirm_at' => $confirmAt,
            'checked_username' => $this->getCheckedUsername(),
            'entry_id' => $this->getEntryId(),
            'previous_id' => $this->getPreviousId(),
            'at' => $this->getCreatedAt()->format(\DateTime::ISO8601),
            'created_at' => $this->getCreatedAt()->format(\DateTime::ISO8601),
            'rate' => $this->getRate(), // 當時匯率資料
            'amount_conv' => number_format($this->amount * $this->rate, 4, '.', ''), // 轉匯後出款金額
            'fee_conv' => number_format($this->fee * $this->rate, 4, '.', ''), // 轉匯後手續費
            'deduction_conv' => number_format($this->deduction * $this->rate, 4, '.', ''), // 轉匯後優惠扣除
            'aduit_charge_conv' => number_format($aduitChargeConv, 4, '.', ''), // 轉匯後常態稽核行政費用
            'aduit_fee_conv' => number_format($aduitFeeConv, 4, '.', ''), // 轉匯後常態稽核手續費
            'real_amount_conv' => number_format($realAmountConv, 4, '.', ''), // 轉匯後真實出款金額
            'payment_gateway_fee_conv' => number_format($paymentGatewayFeeConv, 4, '.', ''), // 轉匯後支付平台手續費
            'auto_withdraw_amount_conv' => number_format($autoWithdrawAmountConv, 4, '.', ''), // 轉匯後自動出款金額
            'auto_withdraw' => $this->isAutoWithdraw(),
            'merchant_withdraw_id' => $this->getMerchantWithdrawId(),
            'ref_id' => $this->getRefId(),
            'note' => $this->getNote(),
        ];
    }
}
