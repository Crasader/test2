<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 現金交易記錄操作者
 *
 * @ORM\Entity
 * @ORM\Table(name = "cash_entry_operator")
 */
class CashEntryOperator
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
     * @return array
     */
    public function toArray()
    {
        return array(
            'entry_id' => $this->entryId,
            'username' => $this->username,
        );
    }
}
