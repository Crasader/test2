<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\CashFake;
use BB\DurianBundle\Entity\RemovedUser;
use BB\DurianBundle\Currency;

/**
 * 移除的假現金
 *
 * @ORM\Entity
 * @ORM\Table(name = "removed_cash_fake")
 */
class RemovedCashFake
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "integer")
     */
    private $id;

    /**
     * 對應被移除的RemovedUser
     *
     * @var RemovedUser
     *
     * @ORM\ManyToOne(targetEntity = "RemovedUser", inversedBy = "removedCashFake")
     * @ORM\JoinColumn(name = "user_id",
     *     referencedColumnName = "user_id",
     *     nullable = false)
     */
    private $removedUser;

    /**
     * 幣別
     *
     * @var integer
     *
     * @ORM\Column(type = "smallint", options = {"unsigned" = true})
     */
    private $currency;

    /**
     * @param RemovedUser $removedUser 對應被刪除的使用者
     * @param CashFake $cashFake 要刪除的假現金
     */
    public function __construct(RemovedUser $removedUser, CashFake $cashFake)
    {
        if ($removedUser->getUserId() != $cashFake->getUser()->getId()) {
            throw new \RuntimeException('CashFake not belong to this user', 150010157);
        }

        $this->id          = $cashFake->getId();
        $this->removedUser = $removedUser;
        $this->currency    = $cashFake->getCurrency();

        $removedUser->addRemovedCashFake($this);
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 回傳對應的刪除使用者
     *
     * @return RemovedUser
     */
    public function getRemovedUser()
    {
        return $this->removedUser;
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
        $currencyOperator = new Currency();

        return [
            'id'       => $this->getId(),
            'user_id'  => $this->getRemovedUser()->getUserId(),
            'currency' => $currencyOperator->getMappedCode($this->getCurrency())
        ];
    }
}
