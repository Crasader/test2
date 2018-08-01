<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 現金交易記錄操作者
 *
 * @ORM\Entity
 * @ORM\Table(name = "cash_fake_entry_operator")
 */
class CashFakeEntryOperator
{
    /**
     * 明細對應id
     *
     * @var integer
     * @ORM\Id
     * @ORM\Column(name = "entry_id", type = "bigint")
     */
    private $entryId;

    /**
     * 操作者名稱
     *
     * @var string
     *
     * @ORM\Column(name = "username", type = "string", length = 30, options = {"default" = ""})
     */
    private $username = '';

    /**
     * 錢的流向是否為轉出,
     * true代表你轉給對方 (amount < 0)
     * false代表對方轉給你 (amount > 0)
     *
     * @var bool
     *
     * @ORM\Column(name="transfer_out", type="boolean", nullable = true)
     */
    private $transferOut;

    /**
     * 記錄對方把錢轉給你, 或把錢轉給對方時, 對方的名字
     *
     * @var string
     *
     * @ORM\Column(name="whom", type="string", length=20)
     */
    private $whom;

    /**
     * whom存的使用者的階層
     *
     * @var integer
     *
     * @ORM\Column(name="level", type="smallint", nullable = true)
     */
    private $level;

    /**
     *
     * @param int $entryId
     * @param string $username
     */
    public function __construct($entryId, $username)
    {
        $this->entryId  = $entryId;
        $this->username = $username;
    }

    /**
     * 回傳交易明細id
     *
     * @return integer
     */
    public function getEntryId()
    {
        return $this->entryId;
    }

    /**
     * 回傳使用者名稱
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     *
     * @param bool $isOut 設定錢的流向, true代表你轉給對方 (amount < 0),
     *                               false代表對方轉給你 (amount > 0)
     *
     * @return CashFakeEntryOperator
     */
    public function setTransferOut($isOut)
    {
        $this->transferOut = $isOut;

        return $this;
    }

    /**
     * 傳回錢的流向
     *
     * @return bool
     */
    public function getTransferOut()
    {
        return $this->transferOut;
    }

    /**
     * 設定對方把錢轉給你, 或把錢轉給對方時, 對方的名字
     *
     * @param string $username
     * @return CashFakeEntryOperator
     */
    public function setWhom($username)
    {
        $this->whom = $username;

        return $this;
    }

    /**
     * 傳回對方把錢轉給你, 或把錢轉給對方時, 對方的名字
     *
     * @return string
     */
    public function getWhom()
    {
        return $this->whom;
    }

    /**
     * 設定whom存的使用者的階層
     *
     * @param int $level
     * @return CashFakeEntryOperator
     */
    public function setLevel($level)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * 傳回whom存的使用者的階層
     *
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array(
            'entry_id'     => $this->entryId,
            'username'     => $this->username,
            'transfer_out' => $this->transferOut,
            'whom'         => $this->whom,
            'level'        => $this->level
        );
    }
}
