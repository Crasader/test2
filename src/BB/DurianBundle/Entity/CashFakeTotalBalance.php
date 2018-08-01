<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Currency;

/**
 * 下層快開額度餘額紀錄
 *
 * @ORM\Entity
 * @ORM\Table(name = "cash_fake_total_balance")
 */
class CashFakeTotalBalance
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 對應的上層id
     *
     * @var integer
     *
     * @ORM\Column(name = "parent_id", type = "integer")
     */
    private $parentId;

    /**
     * 幣別
     *
     * @var integer
     *
     * @ORM\Column(type = "smallint", options = {"unsigned" = true})
     */
    private $currency;

    /**
     * 下層啟用使用者的總餘額
     *
     * @var float
     *
     * @ORM\Column(name = "enable_balance", type = "decimal", precision = 16, scale = 4)
     */
    private $enableBalance;

    /**
     * 下層停用使用者的總餘額
     *
     * @var float
     *
     * @ORM\Column(name = "disable_balance", type = "decimal", precision = 16, scale = 4)
     */
    private $disableBalance;

    /**
     * 更新時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "at", type = "datetime", nullable = true)
     */
    private $at;

    /**
     * @param integer  $parentId 上層id
     * @param integer  $currency 幣別
     */
    public function __construct($parentId, $currency)
    {
        $this->parentId       = $parentId;
        $this->currency       = $currency;
        $this->enableBalance  = 0;
        $this->disableBalance = 0;
    }

    /**
     * @return integer
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * @param float $balance
     * @return CashFakeTotalBalance
     */
    public function setEnableBalance($balance)
    {
        $this->enableBalance = $balance;

        return $this;
    }

    /**
     * @return float
     */
    public function getEnableBalance()
    {
        return $this->enableBalance;
    }

    /**
     * @param float $balance
     * @return CashFakeTotalBalance
     */
    public function setDisableBalance($balance)
    {
        $this->disableBalance = $balance;

        return $this;
    }

    /**
     * @return float
     */
    public function getDisableBalance()
    {
        return $this->disableBalance;
    }

    /**
     * @param \DateTime $at
     * @return CashFakeTotalBalance
     */
    public function setAt($at)
    {
        $this->at = $at;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getAt()
    {
        return $this->at;
    }

    /**
     * 回傳幣別
     *
     * @return integer
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $at = null;
        if (!is_null($this->at)) {
            $at = $this->at->format(\DateTime::ISO8601);
        }

        $currencyOperator = new Currency();

        return array(
            'id' => $this->id,
            'at' => $at,
            'parent_id'       => $this->parentId,
            'currency'        => $currencyOperator->getMappedCode($this->getCurrency()),
            'enable_balance'  => $this->enableBalance,
            'disable_balance' => $this->disableBalance,
        );
    }
}
