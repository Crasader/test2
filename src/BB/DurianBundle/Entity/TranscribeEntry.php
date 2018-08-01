<?php
namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\RemitAccount;

/**
 * 人工抄錄的入款資訊
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\TranscribeEntryRepository")
 * @ORM\Table(
 *      name = "transcribe_entry",
 *      uniqueConstraints = {
 *          @ORM\UniqueConstraint(
 *              name = "uni_transcribe_entry_remit_account_id_rank", columns = {"remit_account_id", "rank"}
 *          )
 *      },
 *      indexes = {
 *          @ORM\Index(name = "idx_transcribe_entry_booked_at", columns = {"booked_at"}),
 *          @ORM\Index(name = "idx_transcribe_entry_first_transcribe_at", columns = {"first_transcribe_at"}),
 *          @ORM\Index(name = "idx_transcribe_entry_confirm_at", columns = {"confirm_at"})
 *      }
 * )
 */
class TranscribeEntry
{
    /**
     * rank最大值
     *
     * mysql smallint上限為 32767
     */
    const MAX_RANK = 32767;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "integer", options = {"unsigned" = true})
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 出入款帳號id
     *
     * @var integer
     *
     * @ORM\Column(name = "remit_account_id", type = "integer", options = {"unsigned" = true})
     */
    private $remitAccountId;

    /**
     * 金額，正為入款，負為出款
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
     * 存/出款方式
     *
     * @var integer
     *
     * @ORM\Column(name = "method", type = "smallint", options = {"unsigned" = true})
     */
    private $method;

    /**
     * 真實姓名
     *
     * @var string
     *
     * @ORM\Column(name = "name_real", type = "string", length = 32)
     */
    private $nameReal;

    /**
     * 交易地點
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 32)
     */
    private $location;

    /**
     * 空資料
     *
     * @var boolean
     *
     * @ORM\Column(name = "blank", type = "boolean")
     */
    private $blank;

    /**
     * 已確認
     *
     * @var boolean
     *
     * @ORM\Column(name = "confirm", type = "boolean")
     */
    private $confirm;

    /**
     * 出款
     *
     * @var boolean
     *
     * @ORM\Column(name = "withdraw", type = "boolean")
     */
    private $withdraw;

    /**
     * 已刪除
     *
     * @var boolean
     *
     * @ORM\Column(name = "deleted", type = "boolean")
     */
    private $deleted;

    /**
     * 抄錄人員
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 30, nullable = true)
     *
     */
    private $creator;

    /**
     * 登記日期
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "booked_at", type = "datetime")
     */
    private $bookedAt;

    /**
     * 首次抄錄時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "first_transcribe_at", type = "datetime", nullable = true)
     */
    private $firstTranscribeAt;

    /**
     * 抄錄時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "transcribe_at", type = "datetime", nullable = true)
     */
    private $transcribeAt;

    /**
     * 出款到哪個出入款帳號id
     *
     * @var integer
     *
     * @ORM\Column(name = "recipient_account_id", type = "smallint", options = {"unsigned" = true}, nullable = true)
     */
    private $recipientAccountId;

    /**
     * 備註
     *
     * @var string
     *
     * @ORM\Column(name = "memo", type = "string", length = 100, options = {"default" = ""})
     */
    private $memo;

    /**
     * 交易異動
     *
     * @var string
     *
     * @ORM\Column(name = "trade_memo", type = "string", length = 100, options = {"default" = ""})
     */
    private $tradeMemo;

    /**
     * 排序順序
     *
     * @var integer
     *
     * @ORM\Column(name = "rank", type = "smallint")
     */
    private $rank;

    /**
     * 公司帳號入款記錄id, 只有狀態為confirm的才會設值，否則皆為null
     *
     * @var integer
     *
     * @ORM\Column(name = "remit_entry_id", type = "integer", options = {"unsigned" = true}, nullable = true)
     */
    private $remitEntryId;

    /**
     * 使用者帳號
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 30, nullable = true)
     */
    private $username;

    /**
     * 認領時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "confirm_at", type = "datetime", nullable = true)
     */
    private $confirmAt;

    /**
     * 是否為強制認領
     *
     * @var boolean
     *
     * @ORM\Column(name = "force_confirm", type = "boolean", options = {"default" = false})
     */
    private $forceConfirm;

    /**
     * 強制認領的操作者
     *
     * @var string
     *
     * @ORM\Column(name = "force_operator", type = "string", length = 30, nullable = true)
     */
    private $forceOperator;

    /**
     * 更新時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "update_at", type = "datetime")
     */
    private $updateAt;

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
     * 新增一筆抄錄明細
     *
     * @param RemitAccount $account 公司入款帳號
     * @param integer $rank 排序順序
     */
    public function __construct(RemitAccount $account, $rank)
    {
        $now = new \DateTime();

        $this->remitAccountId = $account->getId();
        $this->bookedAt = $now;
        $this->updateAt = $now;

        $this->amount = 0;
        $this->fee = 0;
        $this->method = 0;
        $this->rank = $rank;
        $this->creator = '';
        $this->nameReal = '';
        $this->location = '';
        $this->memo = '';
        $this->tradeMemo = '';

        $this->blank = true;
        $this->confirm = false;
        $this->withdraw = false;
        $this->deleted = false;
        $this->forceConfirm = false;
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
     * 設定id (for 轉資料)
     *
     * @param integer $id
     * @return TranscribeEntry
     */
    public function setId($id)
    {
        $this->id = $id;

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
     * @param integer $accountId
     * @return TranscribeEntry
     */
    public function setRemitAccountId($accountId)
    {
        $this->remitAccountId = $accountId;

        return $this;
    }

    /**
     * 取得金額
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
     * @return TranscribeEntry
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * 取得手續費
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
     * @return TranscribeEntry
     */
    public function setFee($fee)
    {
        $this->fee = $fee;

        return $this;
    }

    /**
     * 取得入款方式
     *
     * @return integer
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * 設定入款方式
     *
     * @param integer $method
     * @return TranscribeEntry
     */
    public function setMethod($method)
    {
        $this->method = $method;

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
     * 設定真實姓名
     *
     * @param string $nameReal
     * @return TranscribeEntry
     */
    public function setNameReal($nameReal)
    {
        $this->nameReal = $nameReal;

        return $this;
    }

    /**
     * 取得交易地點
     *
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * 設定交易地點
     *
     * @param string $location
     * @return TranscribeEntry
     */
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * 不是空資料
     *
     * @return TranscribeEntry
     */
    public function unBlank()
    {
        $this->blank = false;

        return $this;
    }

    /**
     * 回傳是否為空資料
     *
     * @return boolean
     */
    public function isBlank()
    {
        return $this->blank;
    }

    /**
     * 已確認
     *
     * @return TranscribeEntry
     */
    public function confirm()
    {
        $this->confirm = true;

        return $this;
    }

    /**
     * 未確認
     *
     * @return TranscribeEntry
     */
    public function unConfirm()
    {
        $this->confirm = false;

        return $this;
    }

    /**
     * 回傳是否為已確認
     *
     * @return boolean
     */
    public function isConfirm()
    {
        return $this->confirm;
    }

    /**
     * 出款
     *
     * @return TranscribeEntry
     */
    public function withdraw()
    {
        $this->withdraw = true;

        return $this;
    }

    /**
     * 回傳是否為出款
     *
     * @return boolean
     */
    public function isWithdraw()
    {
        return $this->withdraw;
    }

    /**
     * 已刪除
     *
     * @return TranscribeEntry
     */
    public function deleted()
    {
        $this->deleted = true;

        return $this;
    }

    /**
     * 回傳是否為已刪除
     *
     * @return boolean
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * 取得抄錄人員
     *
     * @return string
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * 設定抄錄人員
     *
     * @param string $creator
     * @return TranscribeEntry
     */
    public function setCreator($creator)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * 取得登記時間
     *
     * @return \DateTime
     */
    public function getBookedAt()
    {
        return $this->bookedAt;
    }

    /**
     * 設定登記時間
     *
     * @param \DateTime $bookedAt
     * @return TranscribeEntry
     */
    public function setBookedAt($bookedAt)
    {
        $this->bookedAt = $bookedAt;

        return $this;
    }

    /**
     * 取得首次抄錄時間
     *
     * @return \DateTime
     */
    public function getFirstTranscribeAt()
    {
        return $this->firstTranscribeAt;
    }

    /**
     * 設定首次抄錄時間
     *
     * @param \DateTime $firstTranscribeAt
     * @return TranscribeEntry
     */
    public function setFirstTranscribeAt($firstTranscribeAt)
    {
        $this->firstTranscribeAt = $firstTranscribeAt;

        return $this;
    }

    /**
     * 取得首次抄錄時間
     *
     * @return \DateTime
     */
    public function getTranscribeAt()
    {
        return $this->transcribeAt;
    }

    /**
     * 設定首次抄錄時間
     *
     * @param \DateTime $transcribeAt
     * @return TranscribeEntry
     */
    public function setTranscribeAt($transcribeAt)
    {
        $this->transcribeAt = $transcribeAt;

        return $this;
    }

    /**
     * 取得目標出款帳戶Id
     *
     * @return integer
     */
    public function getRecipientAccountId()
    {
        return $this->recipientAccountId;
    }

    /**
     * 設定目標出款帳戶
     *
     * @param integer $recipientAccoundId
     * @return TranscribeEntry
     */
    public function setRecipientAccountId($recipientAccoundId)
    {
        $this->recipientAccountId = $recipientAccoundId;

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
     * @return TranscribeEntry
     */
    public function setMemo($memo)
    {
        $this->memo = $memo;

        return $this;
    }

    /**
     * 取得交易異動備註
     *
     * @return string
     */
    public function getTradeMemo()
    {
        return $this->tradeMemo;
    }

    /**
     * 設定交易異動備註
     *
     * @param string $tradeMemo
     * @return TranscribeEntry
     */
    public function setTradeMemo($tradeMemo)
    {
        $this->tradeMemo = $tradeMemo;

        return $this;
    }

    /**
     * 取得排序
     *
     * @return integer
     */
    public function getRank()
    {
        return $this->rank;
    }

    /**
     * 設定排序
     *
     * @param integer $rank
     * @return TranscribeEntry
     */
    public function setRank($rank)
    {
        $this->rank = $rank;

        return $this;
    }

    /**
     * 取得版號
     *
     * @return integer
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * 回傳人工抄錄明細Id
     *
     * @return integer
     */
    public function getRemitEntryId()
    {
        return $this->remitEntryId;
    }

    /**
     * 設定人工抄錄明細id
     *
     * @param integer $remitEntryId
     * @return TranscribeEntry
     */
    public function setRemitEntryId($remitEntryId)
    {
        $this->remitEntryId = $remitEntryId;

        return $this;
    }

    /**
     * 設定使用者帳號
     *
     * @param string $username
     * @return TranscribeEntry
     */
    public function setUsername($username)
    {
        $this->username= $username;

        return $this;
    }

    /**
     * 取得使用者帳號
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * 強制認領
     *
     * @return TranscribeEntry
     */
    public function forceConfirm()
    {
        $now = new \DateTime();
        $this->confirm = true;
        $this->forceConfirm = true;
        $this->setConfirmAt($now);
        $this->updateAt = $now;

        return $this;
    }

    /**
     * 是否為強制認領
     *
     * @return boolean
     */
    public function isForceConfirm()
    {
        return (bool) $this->forceConfirm;
    }

    /**
     * 設定強制認領操作者
     *
     * @param string $operator
     * @return TranscribeEntry
     */
    public function setForceOperator($operator)
    {
        $this->forceOperator= $operator;

        return $this;
    }

    /**
     * 取得強制認領操作者
     *
     * @return string
     */
    public function getForceOperator()
    {
        return $this->forceOperator;
    }

    /**
     * 設定認領時間
     *
     * @param \DateTime $at
     * @return TranscribeEntry
     */
    public function setConfirmAt($at)
    {
        $this->confirmAt= $at;

        return $this;
    }

    /**
     * 取得認領時間
     *
     * @return \DateTime
     */
    public function getConfirmAt()
    {
        return $this->confirmAt;
    }

    /**
     * 設定更新時間
     *
     * @param \DateTime $at
     * @return TranscribeEntry
     */
    public function setUpdateAt($at)
    {
        $this->updateAt = $at;

        return $this;
    }

    /**
     * 取得更新時間
     *
     * @return \DateTime
     */
    public function getUpdateAt()
    {
        return $this->updateAt;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $transcribeAt = null;
        $firstTranscribeAt = null;
        $confirmAt = null;

        if ($this->getFirstTranscribeAt()) {
            $firstTranscribeAt = $this->getFirstTranscribeAt()->format(\DateTime::ISO8601);
        }

        if ($this->getTranscribeAt()) {
            $transcribeAt = $this->getTranscribeAt()->format(\DateTime::ISO8601);
        }

        if ($this->getConfirmAt()) {
            $confirmAt = $this->getConfirmAt()->format(\DateTime::ISO8601);
        }

        return [
            'id' => $this->getId(),
            'remit_account_id' => $this->getRemitAccountId(),
            'amount' => $this->getAmount(),
            'fee' => $this->getFee(),
            'method' => $this->getMethod(),
            'name_real' => $this->getNameReal(),
            'location' => $this->getLocation(),
            'blank' => $this->isBlank(),
            'confirm' => $this->isConfirm(),
            'withdraw' => $this->isWithdraw(),
            'deleted' => $this->isDeleted(),
            'creator' => $this->getCreator(),
            'booked_at' => $this->getBookedAt()->format(\DateTime::ISO8601),
            'first_transcribe_at' => $firstTranscribeAt,
            'transcribe_at' => $transcribeAt,
            'recipient_account_id' => $this->getRecipientAccountId(),
            'memo' => $this->getMemo(),
            'trade_memo' => $this->getTradeMemo(),
            'rank' => $this->getRank(),
            'remit_entry_id' => $this->getRemitEntryId(),
            'username' => $this->getUsername(),
            'confirm_at' => $confirmAt,
            'force_confirm' => $this->isForceConfirm(),
            'force_operator' => $this->getForceOperator(),
            'update_at' => $this->getUpdateAt()->format(\DateTime::ISO8601),
            'version' => $this->getVersion()
        ];
    }
}
