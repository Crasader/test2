<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 自動認款的匯款記錄
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\AutoConfirmEntryRepository")
 * @ORM\Table(
 *      name = "auto_confirm_entry",
 *      indexes = {
 *          @ORM\Index(name = "idx_auto_confirm_entry_created_at", columns = {"created_at"}),
 *          @ORM\Index(name = "idx_auto_confirm_entry_confirm_at", columns = {"confirm_at"}),
 *          @ORM\Index(name = "idx_auto_confirm_entry_ref_id", columns = {"ref_id"})
 *      }
 * )
 */
class AutoConfirmEntry
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
     * 匯款記錄建立時間
     *
     * @var integer
     *
     * @ORM\Column(name = "created_at", type = "bigint", options = {"unsigned" = true})
     */
    private $createdAt;

    /**
     * 確認入款時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "confirm_at", type = "datetime", nullable = true)
     */
    private $confirmAt;

    /**
     * 外部訂單ID
     *
     * @var string
     *
     * @ORM\Column(name = "ref_id", type = "string", length = 64, nullable = true)
     */
    private $refId;

    /**
     * 確認入款
     *
     * @var boolean
     *
     * @ORM\Column(name = "confirm", type = "boolean")
     */
    private $confirm;

    /**
     * 是否為人工匹配
     *
     * @var boolean
     *
     * @ORM\Column(name = "manual", type = "boolean")
     */
    private $manual;

    /**
     * 出入款帳號id
     *
     * @var integer
     *
     * @ORM\Column(name = "remit_account_id", type = "integer", options = {"unsigned" = true})
     */
    private $remitAccountId;

    /**
     * 公司入款記錄id, 只有狀態為confirm的才會設值，否則為0
     *
     * @var integer
     *
     * @ORM\Column(name = "remit_entry_id", type = "integer", options = {"unsigned" = true})
     */
    private $remitEntryId;

    /**
     * 匯款金額
     *
     * @var float
     *
     * @ORM\Column(name = "amount", type = "decimal", precision = 16, scale = 4)
     */
    private $amount;

    /**
     * 匯款手續費
     *
     * @var float
     *
     * @ORM\Column(name = "fee", type = "decimal", precision = 16, scale = 4)
     */
    private $fee;

    /**
     * 匯款餘額
     *
     * @var float
     *
     * @ORM\Column(name = "balance", type = "decimal", precision = 16, scale = 4)
     */
    private $balance;

    /**
     * 匯款時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "trade_at", type = "datetime")
     */
    private $tradeAt;

    /**
     * 匯款方式
     *
     * @var string
     *
     * @ORM\Column(name = "method", type = "string", length = 30)
     */
    private $method;

    /**
     * 匯款人姓名
     *
     * @var string
     *
     * @ORM\Column(name = "name", type = "string", length = 32)
     */
    private $name;

    /**
     * 匯款卡號
     *
     * @var string
     *
     * @ORM\Column(name = "account", type = "string", length = 64)
     */
    private $account;

    /**
     * 匯款附言
     *
     * @var string
     *
     * @ORM\Column(name = "trade_memo", type = "string", length = 100)
     */
    private $tradeMemo;

    /**
     * 收支信息
     *
     * @var string
     *
     * @ORM\Column(name = "message", type = "string", length = 100)
     */
    private $message;

    /**
     * 備註
     *
     * @var string
     *
     * @ORM\Column(name = "memo", type = "string", length = 100)
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
     * 新增一筆匯款記錄
     *
     * @param RemitAccount $account 公司入款帳號
     * @param array $data 匯款記錄資訊
     */
    public function __construct(RemitAccount $account, $data)
    {
        $now = new \DateTime();

        $this->remitAccountId = $account->getId();
        $this->remitEntryId = 0;
        $this->createdAt = $now->format('YmdHis');
        $this->confirm = false;
        $this->manual = false;
        $this->memo = '';

        $this->method = $data['method'];
        $this->name = $data['name'];
        $this->account = $data['account'];
        $this->amount = $data['amount'];
        $this->balance = $data['balance'];
        $this->fee = $data['fee'];
        $this->tradeMemo = $data['memo'];
        $this->message = $data['message'];
        $this->tradeAt = new \DateTime($data['time']);
    }

    /**
     * 取得id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 取得建立時間
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return new \DateTime($this->createdAt);
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
     * 取得外部訂單ID
     *
     * @return string
     */
    public function getRefId()
    {
        return $this->refId;
    }

    /**
     * 設定外部訂單ID
     *
     * @param string $refId
     * @return AutoConfirmEntry
     */
    public function setRefId($refId)
    {
        $this->refId = $refId;

        return $this;
    }

    /**
     * 是否已確認入款
     *
     * @return boolean
     */
    public function isConfirm()
    {
        return $this->confirm;
    }

    /**
     * 確認入款
     *
     * @return AutoConfirmEntry
     */
    public function confirm()
    {
        $this->confirm = true;
        $this->confirmAt = new \DateTime();

        return $this;
    }

    /**
     * 是否為人工匹配
     *
     * @return boolean
     */
    public function isManual()
    {
        return $this->manual;
    }

    /**
     * 設定為入工匹配
     *
     * @param boolean $bool
     * @return AutoConfirmEntry
     */
    public function setManual($bool)
    {
        $this->manual = $bool;

        return $this;
    }

    /**
     * 取得入款帳戶Id
     *
     * @return integer
     */
    public function getRemitAccountId()
    {
        return $this->remitAccountId;
    }

    /**
     * 設定入款帳戶Id
     *
     * @param integer $remitAccountId
     * @return AutoConfirmEntry
     */
    public function setRemitAccountId($remitAccountId)
    {
        $this->remitAccountId = $remitAccountId;

        return $this;
    }

    /**
     * 取得公司入款明細Id
     *
     * @return integer|null
     */
    public function getRemitEntryId()
    {
        if (!$this->remitEntryId) {
            return null;
        }

        return $this->remitEntryId;
    }

    /**
     * 設定公司入款明細Id
     *
     * @param integer $remitEntryId
     * @return AutoConfirmEntry
     */
    public function setRemitEntryId($remitEntryId)
    {
        $this->remitEntryId = $remitEntryId;

        return $this;
    }

    /**
     * 取得金額
     *
     * @return string
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * 設定金額
     *
     * @param string $amount
     * @return AutoConfirmEntry
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * 取得手續費
     *
     * @return string
     */
    public function getFee()
    {
        return $this->fee;
    }

    /**
     * 設定手續費
     *
     * @param string $fee
     * @return AutoConfirmEntry
     */
    public function setFee($fee)
    {
        $this->fee = $fee;

        return $this;
    }

    /**
     * 取得餘額
     *
     * @return string
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * 設定餘額
     *
     * @param string $balance
     * @return AutoConfirmEntry
     */
    public function setBalance($balance)
    {
        $this->balance = $balance;

        return $this;
    }

    /**
     * 取得匯款時間
     *
     * @return \DateTime
     */
    public function getTradeAt()
    {
        return $this->tradeAt;
    }

    /**
     * 設定匯款時間
     *
     * @param \DateTime $tradeAt
     * @return AutoConfirmEntry
     */
    public function setTradeAt($tradeAt)
    {
        $this->tradeAt = $tradeAt;

        return $this;
    }

    /**
     * 取得匯款方式
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * 設定匯款方式
     *
     * @param string $method
     * @return AutoConfirmEntry
     */
    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * 取得匯款人姓名
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 設定匯款人姓名
     *
     * @param string $name
     * @return AutoConfirmEntry
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * 取得匯款卡號
     *
     * @return string
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * 設定匯款卡號
     *
     * @param string $account
     * @return AutoConfirmEntry
     */
    public function setAccount($account)
    {
        $this->account = $account;

        return $this;
    }

    /**
     * 取得匯款附言
     *
     * @return string
     */
    public function getTradeMemo()
    {
        return $this->tradeMemo;
    }

    /**
     * 設定匯款附言
     *
     * @param string $tradeMemo
     * @return AutoConfirmEntry
     */
    public function setTradeMemo($tradeMemo)
    {
        $this->tradeMemo = $tradeMemo;

        return $this;
    }

    /**
     * 取得收支信息
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * 設定收支信息
     *
     * @param string $message
     * @return AutoConfirmEntry
     */
    public function setMessage($message)
    {
        $this->message = $message;

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
     * 設定備註
     *
     * @param string $memo
     * @return AutoConfirmEntry
     */
    public function setMemo($memo)
    {
        $this->memo = $memo;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $confirmAt = null;

        if (!is_null($this->getConfirmAt())) {
            $confirmAt = $this->getConfirmAt()->format(\DateTime::ISO8601);
        }

        return [
            'id' => $this->getId(),
            'created_at' => $this->getCreatedAt()->format(\DateTime::ISO8601),
            'confirm_at' => $confirmAt,
            'ref_id' => $this->getRefId(),
            'confirm' => $this->isConfirm(),
            'manual' => $this->isManual(),
            'remit_account_id' => $this->getRemitAccountId(),
            'remit_entry_id' => $this->getRemitEntryId(),
            'trade_at' => $this->getTradeAt()->format(\DateTime::ISO8601),
            'amount' => $this->getAmount(),
            'fee' => $this->getFee(),
            'balance' => $this->getBalance(),
            'method' => $this->getMethod(),
            'name' => $this->getName(),
            'account' => $this->getAccount(),
            'trade_memo' => $this->getTradeMemo(),
            'message' => $this->getMessage(),
            'memo' => $this->getMemo(),
        ];
    }
}
