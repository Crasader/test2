<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\Cash;
use BB\DurianBundle\Entity\RemovedUser;
use BB\DurianBundle\Currency;

/**
 * 移除的現金
 *
 * @ORM\Entity
 * @ORM\Table(name = "removed_cash")
 */
class RemovedCash
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
     * @ORM\ManyToOne(targetEntity = "RemovedUser", inversedBy = "removedCash")
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
     * 現金餘額
     *
     * @var float
     *
     * @ORM\Column(name = "balance", type = "decimal", precision = 16, scale = 4)
     */
    protected $balance;

    /**
     * @param RemovedUser $removedUser 對應被刪除的使用者
     * @param Cash $cash 要刪除的現金
     */
    public function __construct(RemovedUser $removedUser, Cash $cash)
    {
        if ($removedUser->getUserId() != $cash->getUser()->getId()) {
            throw new \RuntimeException('Cash not belong to this user', 150010133);
        }

        $this->id          = $cash->getId();
        $this->removedUser = $removedUser;
        $this->currency    = $cash->getCurrency();
        $this->balance     = $cash->getBalance();

        $removedUser->addRemovedCash($this);
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
     * 回傳餘額
     *
     * @return float
     */
    public function getBalance()
    {
        return $this->balance;
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
            'balance'  => $this->getBalance(),
            'currency' => $currencyOperator->getMappedCode($this->getCurrency())
        ];
    }
}
