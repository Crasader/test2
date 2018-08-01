<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\Cash;

/**
 * 交易機制現金記錄
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\CashTransRepository")
 * @ORM\Table(
 *      name = "cash_trans",
 *      indexes = {
 *          @ORM\Index(name = "idx_cash_trans_cash_id", columns = {"cash_id"}),
 *          @ORM\Index(name = "idx_cash_trans_ref_id", columns = {"ref_id"}),
 *          @ORM\Index(name = "idx_cash_trans_created_at", columns = {"created_at"}),
 *          @ORM\Index(name = "idx_cash_trans_checked", columns = {"checked"})
 *      }
 * )
 */
class CashTrans
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "bigint")
     */
    private $id;

    /**
     * 對應的現金id
     *
     * @var integer
     *
     * @ORM\Column(name = "cash_id", type = "integer")
     */
    private $cashId;

    /**
     * 使用者Id
     *
     * @var integer
     *
     * @ORM\Column(name = "user_id", type = "integer")
     */
    private $userId;

    /**
     * 幣別
     *
     * @var integer
     *
     * @ORM\Column(type = "smallint", options = {"unsigned" = true})
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
     * 交易金額
     *
     * @var float
     *
     * @ORM\Column(name = "amount", type = "decimal", precision = 16, scale = 4)
     */
    private $amount;

    /**
     * 交易編號
     *
     * @var int
     *
     * @ORM\Column(name = "ref_id", type = "bigint", length = 20, options={"default"=0})
     */
    private $refId;

    /**
     * 建立時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "created_at", type = "datetime")
     */
    private $createdAt;

    /**
     * 狀態是否確認
     *
     * @var boolean
     *
     * @ORM\Column(name = "checked", type = "boolean")
     */
    private $checked;

    /**
     * 確認時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "checked_at", type = "datetime", nullable = true)
     */
    private $checkedAt;

    /**
     * 備註
     *
     * @var string
     *
     * @ORM\Column(name = "memo", type = "string", length = 100, options = {"default" = ""})
     */
    private $memo = '';

    /**
     * @param Cash    $cash   輸入交易帳號
     * @param integer $opcode 交易種類
     * @param float   $amount 交易金額
     * @param string  $memo   交易備註
     */
    public function __construct(Cash $cash, $opcode, $amount, $memo = '')
    {
        $this->checked    = false;
        $this->opcode     = $opcode;
        $this->createdAt  = new \DateTime();
        $this->amount     = $amount;
        $this->memo       = $memo;
        $this->cashId     = $cash->getId();
        $this->userId     = $cash->getUser()->getId();
        $this->currency   = $cash->getCurrency();
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 設定id
     *
     * @param string $id
     * @return CashTrans
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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
     * 取得幣別
     *
     * @return integer
     */
    public function getCurrency()
    {
        return $this->currency;
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
     * 回傳參考編號
     *
     * @return int
     */
    public function getRefId()
    {
        return $this->refId;
    }

    /**
     * 回傳交易時間
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * 設定交易時間
     *
     * @param \DateTime $createAt
     * @return CashTrans
     */
    public function setCreatedAt($createAt)
    {
        $this->createdAt = $createAt;

        return $this;
    }

    /**
     * 設定備註
     *
     * @param string $memo
     * @return CashTrans
     */
    public function setMemo($memo)
    {
        $this->memo = $memo;

        return $this;
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
     * 回傳確認時間
     *
     * @return \DateTime
     */
    public function getCheckedAt()
    {
        return $this->checkedAt;
    }

    /**
     * 是否確認
     *
     * @return bool
     */
    public function isChecked()
    {
        return (bool) $this->checked;
    }

    /**
     * 設定參考編號
     *
     * @param int $refId
     * @return CashTrans
     */
    public function setRefId($refId)
    {
        $this->refId = $refId;

        return $this;
    }
}
