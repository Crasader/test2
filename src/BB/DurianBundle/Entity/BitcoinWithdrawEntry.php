<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Currency;

/**
 * 比特幣出款記錄
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\BitcoinWithdrawEntryRepository")
 * @ORM\Table(name = "bitcoin_withdraw_entry",
 *      indexes={
 *          @ORM\Index(name = "idx_bitcoin_withdraw_entry_at", columns = {"at"}),
 *          @ORM\Index(name = "idx_bitcoin_withdraw_entry_user_id", columns = {"user_id"}),
 *          @ORM\Index(name = "idx_bitcoin_withdraw_entry_confirm_at", columns = {"confirm_at"}),
 *          @ORM\Index(name = "idx_bitcoin_withdraw_entry_domain_at", columns = {"domain", "at"}),
 *          @ORM\Index(name = "idx_bitcoin_withdraw_entry_user_id_at", columns = {"user_id", "at"})
 *      }
 * )
 */
class BitcoinWithdrawEntry
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type = "bigint", options = {"unsigned" = true})
     */
    private $id;

    /**
     * 出款使用者ID
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
     * 申請出款時間
     *
     * @var integer
     *
     * @ORM\Column(name = "at", type = "bigint", options = {"unsigned" = true})
     */
    private $at;

    /**
     * 確認出款時間
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
     * 確認出款
     *
     * @var boolean
     *
     * @ORM\Column(name = "confirm", type = "boolean")
     */
    private $confirm;

    /**
     * 取消出款
     *
     * @var boolean
     *
     * @ORM\Column(name = "cancel", type = "boolean")
     */
    private $cancel;

    /**
     * 審核鎖定
     *
     * @var boolean
     *
     * @ORM\Column(name = "locked", type = "boolean")
     */
    private $locked;

    /**
     * 人工確認
     *
     * @var boolean
     *
     * @ORM\Column(name = "manual", type = "boolean")
     */
    private $manual;

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
     * 對應出款金額交易明細id
     *
     * @var integer
     *
     * @ORM\Column(name = "amount_entry_id", type = "bigint")
     */
    private $amountEntryId;

    /**
     * 上一筆出款明細ID
     *
     * @var integer
     *
     * @ORM\Column(name = "previous_id", type = "bigint")
     */
    private $previousId;

    /**
     * 使用者輸入的出款幣別
     *
     * @var float
     *
     * @ORM\Column(name = "currency", type = "smallint", options = {"unsigned" = true})
     */
    private $currency;

    /**
     * 出款金額
     *
     * @var float
     *
     * @ORM\Column(name = "amount", type = "decimal", precision = 16, scale = 4)
     */
    private $amount;

    /**
     * 比特幣出款金額
     *
     * @var float
     *
     * @ORM\Column(name = "bitcoin_amount", type = "decimal", precision = 16, scale = 8)
     */
    private $bitcoinAmount;

    /**
     * 出款匯率
     *
     * @var float
     *
     * @ORM\Column(name = "rate", type = "decimal", precision = 16, scale = 8)
     */
    private $rate;

    /**
     * 比特幣出款匯率
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
     * 常態稽核手續費
     *
     * @var float
     *
     * @ORM\Column(name = "audit_fee", type = "decimal", precision = 16, scale = 4)
     */
    private $auditFee;

    /**
     * 常態稽核行政費用
     *
     * @var float
     *
     * @ORM\Column(name ="audit_charge", type = "decimal", precision = 16, scale = 4)
     */
    private $auditCharge;

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
     * 會員申請出款使用的ip
     *
     * @var integer
     *
     * @ORM\Column(name = "ip", type = "integer", options = {"unsigned" = true})
     *
     */
    private $ip;

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
     * 比特幣出款位址
     *
     * @var string
     *
     * @ORM\Column(name = "withdraw_address", type = "string", length = 64)
     *
     */
    private $withdrawAddress;

    /**
     * 參考編號
     *
     * @var string
     *
     * @ORM\Column(name = "ref_id", type = "string", length = 100)
     */
    private $refId;

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
     * @ORM\Column(name = "version", type = "integer")
     * @ORM\Version
     */
    private $version;

    /**
     * 新增一筆出款記錄
     *
     * @param array $data 預設設定值
     */
    public function __construct($data)
    {
        $at = new \DateTime('now');

        $this->id = $data['id'];
        $this->userId = $data['user_id'];
        $this->domain = $data['domain'];
        $this->levelId = $data['level_id'];
        $this->at = $at->format('YmdHis');
        $this->process = 1;
        $this->confirm = 0;
        $this->cancel = 0;
        $this->locked = 0;
        $this->manual = 0;
        $this->first = 0;
        $this->detailModified = 0;
        $this->amountEntryId = 0;
        $this->previousId = 0;
        $this->currency = $data['currency'];
        $this->amount = $data['amount'];
        $this->bitcoinAmount = $data['bitcoin_amount'];
        $this->rate = $data['rate'];
        $this->bitcoinRate = $data['bitcoin_rate'];
        $this->rateDifference = $data['rate_difference'];
        $this->auditFee = $data['audit_fee'];
        $this->auditCharge = $data['audit_charge'];
        $this->deduction = $data['deduction'];
        $this->realAmount = $this->amount - $this->deduction - $this->auditCharge - $this->auditFee;
        $this->ip = ip2long($data['ip']);
        $this->control = 0;
        $this->operator = '';
        $this->withdrawAddress = $data['withdraw_address'];
        $this->refId = '';
        $this->memo = '';
        $this->note = $data['note'];
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
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
     * @return BitcoinWithdrawEntry
     */
    public function confirm()
    {
        $this->confirm = true;
        $this->confirmAt = new \DateTime('now');
        $this->locked = false;
        $this->process = false;

        return $this;
    }

    /**
     * 回傳是否已確認入款
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
     * @return BitcoinWithdrawEntry
     */
    public function cancel()
    {
        $this->cancel = true;
        $this->confirmAt = new \DateTime('now');
        $this->locked = false;
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
     * 審核鎖定
     *
     * @return BitcoinWithdrawEntry
     */
    public function locked()
    {
        $this->locked = true;

        return $this;
    }

    /**
     * 解除審核鎖定
     *
     * @return BitcoinWithdrawEntry
     */
    public function unlocked()
    {
        $this->locked = false;

        return $this;
    }

    /**
     * 回傳是否鎖定
     *
     * @return boolean
     */
    public function isLocked()
    {
        return (bool) $this->locked;
    }

    /**
     * 人工確認
     *
     * @return BitcoinWithdrawEntry
     */
    public function manual()
    {
        $this->manual = true;

        return $this;
    }

    /**
     * 回傳是否人工確認
     *
     * @return boolean
     */
    public function isManual()
    {
        return (bool) $this->manual;
    }

    /**
     * 首次出款
     *
     * @return BitcoinWithdrawEntry
     */
    public function first()
    {
        $this->first = true;

        return $this;
    }

    /**
     * 回傳是否首次出款
     *
     * @return boolean
     */
    public function isFirst()
    {
        return (bool) $this->first;
    }

    /**
     * 設定為上筆同使用者出款明細詳細資料真實姓名被修改過(出款位址)
     *
     * @return BitcoinWithdrawEntry
     */
    public function detailModified()
    {
        $this->detailModified = true;

        return $this;
    }

    /**
     * 回傳上筆同使用者出款明細詳細資料真實姓名被修改過
     *
     * @return boolean
     */
    public function isDetailModified()
    {
        return (bool) $this->detailModified;
    }

    /**
     * 設定對應存款金額交易明細id
     *
     * @param integer $amountEntryId
     * @return BitcoinWithdrawEntry
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
     * 設定上一筆出款明細ID
     *
     * @param integer $previousId
     * @return BitcoinWithdrawEntry
     */
    public function setPreviousId($previousId)
    {
        $this->previousId = $previousId;

        return $this;
    }

    /**
     * 取得上一筆出款明細ID
     *
     * @return integer
     */
    public function getPreviousId()
    {
        return $this->previousId;
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
     * 取得入款使用者輸入的存款金額
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
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
     * 回傳常態稽核手續費
     *
     * @return float
     */
    public function getAuditFee()
    {
        return $this->auditFee;
    }

    /**
     * 回傳常態稽核行政費用
     *
     * @return float
     */
    public function getAuditCharge()
    {
        return $this->auditCharge;
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
     * 回傳實際出款金額
     *
     * @return float
     */
    public function getRealAmount()
    {
        return $this->realAmount;
    }

    /**
     * 回傳會員申請出款使用的ip
     *
     * @return string
     */
    public function getIp()
    {
        return long2ip($this->ip);
    }

    /**
     * 控端
     *
     * @return BitcoinWithdrawEntry
     */
    public function control()
    {
        $this->control = true;

        return $this;
    }

    /**
     * 重置是否為控端
     *
     * @return BitcoinWithdrawEntry
     */
    public function resetControl()
    {
        $this->control = false;

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
     * @return BitcoinWithdrawEntry
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
     * 取得比特幣出款位址
     *
     * @return string
     */
    public function getWithdrawAddress()
    {
        return $this->withdrawAddress;
    }

    /**
     * 設定參考編號
     *
     * @param string $refId
     * @return BitcoinWithdrawEntry
     */
    public function setRefId($refId)
    {
        $this->refId = $refId;

        return $this;
    }

    /**
     * 取得參考編號
     *
     * @return string
     */
    public function getRefId()
    {
        return $this->refId;
    }

    /**
     * 設定備註
     *
     * @param string $memo
     * @return BitcoinWithdrawEntry
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
        $auditChargeConv = $this->getAuditCharge() * $this->rate; // 轉匯後常態稽核行政費用
        $auditFeeConv = $this->getAuditFee() * $this->rate; // 轉匯後常態稽核手續費
        $realAmountConv = $this->getRealAmount() * $this->rate; // 轉匯後真實出款金額

        return [
            'id' => $this->getId(),
            'user_id' => $this->getUserId(),
            'domain' => $this->getDomain(),
            'level_id' => $this->getLevelId(),
            'at' => $this->getAt()->format(\DateTime::ISO8601),
            'confirm_at' => $confirmAt,
            'process' => $this->isProcess(),
            'confirm' => $this->isConfirm(),
            'cancel' => $this->isCancel(),
            'locked' => $this->isLocked(),
            'manual' => $this->isManual(),
            'first' => $this->isFirst(),
            'detailModified' => $this->isDetailModified(),
            'amount_entry_id' => $this->getAmountEntryId(),
            'previous_id' => $this->getPreviousId(),
            'currency' => $currencyOperator->getMappedCode($this->getCurrency()),
            'amount' => $this->getAmount(),
            'bitcoin_amount' => $this->getBitcoinAmount(),
            'rate' => $this->getRate(),
            'bitcoin_rate' => $this->getBitcoinRate(),
            'rate_difference' => $this->getRateDifference(),
            'amount_conv' => number_format($this->amount * $this->rate, 4, '.', ''), // 轉匯後出款金額
            'deduction_conv' => number_format($this->deduction * $this->rate, 4, '.', ''), // 轉匯後優惠扣除
            'audit_charge_conv' => number_format($auditChargeConv, 4, '.', ''), // 轉匯後常態稽核行政費用
            'audit_fee_conv' => number_format($auditFeeConv, 4, '.', ''), // 轉匯後常態稽核手續費
            'real_amount_conv' => number_format($realAmountConv, 4, '.', ''), // 轉匯後真實出款金額
            'deduction' => $this->getDeduction(),
            'audit_charge' => $this->getAuditCharge(),
            'audit_fee' => $this->getAuditFee(),
            'real_amount' => $this->getRealAmount(),
            'ip' => $this->getIp(),
            'control' => $this->isControl(),
            'operator' => $this->getOperator(),
            'withdraw_address' => $this->getWithdrawAddress(),
            'ref_id' => $this->getRefId(),
            'memo' => $this->getMemo(),
            'note' => $this->getNote(),
        ];
    }
}
