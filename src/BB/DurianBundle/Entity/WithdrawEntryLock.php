<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Currency;

/**
 * 出款鎖定
 *
 * @ORM\Entity
 * @ORM\Table(name = "withdraw_entry_lock")
 */
class WithdrawEntryLock
{
    /**
     * 明細對應id
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "entry_id", type = "bigint")
     */
    private $entryId;

    /**
     * 明細對應userId
     *
     * @var integer
     *
     * @ORM\Column(name = "user_id", type = "integer")
     */
    private $userId;

    /**
     * 明細對應幣別
     *
     * @var integer
     *
     * @ORM\Column(name = "currency", type = "smallint", options = {"unsigned" = true})
     */
    private $currency;

    /**
     * 操作者名稱
     *
     * @var string
     *
     * @ORM\Column(name = "operator", type = "string", length = 30)
     */
    private $operator;

    /**
     * 鎖定
     *
     * @var boolean
     *
     * @ORM\Column(name = "locked", type = "boolean")
     */
    private $locked;

    /**
     * @param CashWithdrawEntry $entry
     * @param string $operator
     */
    public function __construct(CashWithdrawEntry $entry, $operator)
    {
        $this->entryId = $entry->getId();
        $this->userId = $entry->getUserId();
        $this->currency = $entry->getCurrency();
        $this->operator = $operator;
        $this->locked = 0;
    }

    /**
     * 回傳操作者名稱
     *
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * 審核鎖定
     *
     * @return WithdrawEntryLock
     */
    public function locked()
    {
        $this->locked = true;

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
     * @return array
     */
    public function toArray()
    {
        $currencyOperator = new Currency();

        return [
            'entry_id' => $this->entryId,
            'user_id' => $this->userId,
            'currency' => $currencyOperator->getMappedCode($this->currency),
            'operator' => $this->operator,
            'locked' => $this->isLocked(),
        ];
    }
}
