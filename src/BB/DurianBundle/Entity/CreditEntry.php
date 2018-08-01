<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 信用額度交易記錄
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\CreditEntryRepository")
 * @ORM\Table(
 *      name = "credit_entry",
 *      indexes = {
 *          @ORM\Index(name = "idx_credit_entry_credit_id", columns = {"credit_id"}),
 *          @ORM\Index(name = "idx_credit_entry_user_id_group_num", columns = {"user_id", "group_num"}),
 *          @ORM\Index(name = "idx_credit_entry_period_at", columns = {"period_at"}),
 *          @ORM\Index(name = "idx_credit_entry_at", columns = {"at"}),
 *          @ORM\Index(name = "idx_credit_entry_ref_id", columns = {"ref_id"}),
 *          @ORM\Index(name = "idx_credit_entry_opcode", columns = {"opcode"})
 *      }
 * )
 */
class CreditEntry
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "bigint")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name = "credit_id", type = "integer")
     */
    private $creditId;

    /**
     * 使用者編號
     *
     * @var integer
     *
     * @ORM\Column(name = "user_id", type = "integer")
     */
    private $userId;

    /**
     * 群組編號
     *
     * @var integer
     *
     * @ORM\Column(name = "group_num", type = "integer")
     */
    private $groupNum;

    /**
     * 交易代碼
     *
     * @var integer
     *
     * @ORM\Column(name = "opcode", type = "integer")
     */
    private $opcode;

    /**
     * 明細所發生的時間，亦即產生時間
     *
     * @var integer
     *
     * @ORM\Column(name = "at", type = "bigint")
     */
    private $at;

    /**
     * 這筆明細下在哪一個時間區間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "period_at", type = "datetime")
     */
    private $periodAt;

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
    protected $balance;

    /**
     * 信用額度上限
     *
     * @var integer
     *
     * @ORM\Column(name = "line", type = "bigint")
     */
    private $line;

    /**
     * 下層信用額度總和
     *
     * @var integer
     *
     * @ORM\Column(name = "total_line", type = "bigint")
     */
    private $totalLine;

    /**
     * 參考編號
     *
     * @var int
     *
     * @ORM\Column(name = "ref_id", type = "bigint", length = 20, options={"default"=0})
     */
    protected $refId;

    /**
     * 備註
     *
     * @var string
     *
     * @ORM\Column(name = "memo", type = "string", length = 100, options = {"default" = ""})
     */
    private $memo = '';

    /**
     * @var integer
     *
     * @ORM\Column(name = "credit_version", type = "integer", options = {"unsigned" = true, "default" = 0})
     */
    private $creditVersion;

    /**
     * 建構子
     *
     * @param integer   $userId
     * @param integer   $groupNum
     * @param integer   $opcode
     * @param float     $amount
     * @param float     $balance
     * @param \DateTime $periodAt
     */
    public function __construct($userId, $groupNum, $opcode, $amount, $balance, $periodAt)
    {
        $this->userId = $userId;
        $this->groupNum = $groupNum;
        $this->opcode = $opcode;
        $this->amount = $amount;
        $this->balance = $balance;
        $this->periodAt = $periodAt;
        $this->at = (new \DateTime)->format('YmdHis');
        $this->refId = '';
        $this->memo = '';
        $this->creditVersion = 0;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 設定 CreditId (會移除 CreditId，目前先保留)
     *
     * @param integer $creditId
     * @return CreditEntry
     */
    public function setCreditId($creditId)
    {
        $this->creditId = $creditId;

        return $this;
    }

    /**
     * @return integer
     */
    public function getCreditId()
    {
        return $this->creditId;
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
     * 回傳群組編號
     *
     * @return integer
     */
    public function getGroupNum()
    {
        return $this->groupNum;
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
     * 設定參考編號
     *
     * @param integer $refId
     * @reutnr CreditEntry
     */
    public function setRefId($refId)
    {
        $this->refId = $refId;

        return $this;
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
     * 設定交易時間
     *
     * @param integer $at
     * @return CreditEntry
     */
    public function setAt($at)
    {
        $this->at = $at;

        return $this;
    }

    /**
     * 回傳交易時間
     *
     * @return \Integer
     */
    public function getAt()
    {
        $at = $this->at;

        return $at;
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
     * @return CreditEntry
     */
    public function setMemo($memo)
    {
        $this->memo = $memo;

        return $this;
    }

    /**
     * 設定 Line
     *
     * @param integer $line
     * @return CreditEntry
     */
    public function setLine($line)
    {
        $this->line = $line;

        return $this;
    }

    /**
     *
     * @return integer
     */
    public function getLine()
    {
        return $this->line;
    }

    /**
     * 設定 TotalLine
     *
     * @param integer $totalLine
     * @return CreditEntry
     */
    public function setTotalLine($totalLine)
    {
        $this->totalLine = $totalLine;

        return $this;
    }

    /**
     * @return integer
     */
    public function getTotalLine()
    {
        return $this->totalLine;
    }

    /**
     * @return \DateTime
     */
    public function getPeriodAt()
    {
        return $this->periodAt;
    }

    /**
     * 回傳信用額度版本號
     *
     * @return integer
     */
    public function getCreditVersion()
    {
        return $this->creditVersion;
    }

    /**
     * 設定信用額度版本號
     *
     * @param integer $creditVersion
     * @return CreditEntry
     */
    public function setCreditVersion($creditVersion)
    {
        $this->creditVersion = $creditVersion;

        return $this;
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

        return array(
            'id'             => $this->getId(),
            'credit_id'  => $this->getCreditId(),
            'user_id'    => $this->getUserId(),
            'group'      => $this->getGroupNum(),
            'opcode'     => $this->getOpcode(),
            'at'         => $this->getAt(),
            'amount'     => $this->getAmount(),
            'memo'       => $this->getMemo(),
            'ref_id'     => $refId,
            'balance'    => $this->getBalance(),
            'line'       => $this->getLine(),
            'total_line' => $this->getTotalLine(),
            'period_at'  => $this->getPeriodAt()->format(\DateTime::ISO8601)
        );
    }
}
