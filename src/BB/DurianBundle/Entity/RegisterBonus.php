<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 新註冊優惠
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\RegisterBonusRepository")
 * @ORM\Table(name = "register_bonus")
 */
class RegisterBonus
{
    /**
     * 會員id
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "user_id", type = "integer")
     */
    private $userId;

    /**
     * 金額
     *
     * @var integer
     *
     * @ORM\Column(name = "amount", type = "integer")
     */
    private $amount;

    /**
     * 打碼倍數
     *
     * @var integer
     *
     * @ORM\Column(name = "multiply", type = "smallint")
     */
    private $multiply;

    /**
     * 是否寫入退傭費用
     *
     * @var boolean
     *
     * @ORM\Column(name = "refund_commision", type = "boolean")
     */
    private $refundCommision;

    /**
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->userId = $user->getId();
        $this->amount = 0;
        $this->multiply = 0;
        $this->refundCommision = true;
    }

    /**
     * 回傳會員id
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * 回傳優惠金額
     *
     * @return integer
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * 回傳打碼倍倍數
     *
     * @return integer
     */
    public function getMultiply()
    {
        return $this->multiply;
    }

    /**
     * 是否寫入退傭費用
     *
     * @return bool
     */
    public function isRefundCommision()
    {
        return (bool) $this->refundCommision;
    }

    /**
     * 設定優惠金額
     *
     * @param integer $amount
     * @return RegisterBonus
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * 設定打碼倍數
     *
     * @param integer $multiply
     * @return RegisterBonus
     */
    public function setMultiply($multiply)
    {
        $this->multiply = $multiply;

        return $this;
    }

    /**
     * 設定是否寫入退傭費用
     *
     * @param boolean $refundCommision
     * @return RegisterBonus
     */
    public function setRefundCommision($refundCommision)
    {
        $this->refundCommision = (bool) $refundCommision;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'user_id' => $this->getUserId(),
            'amount' => $this->getAmount(),
            'multiply' => $this->getMultiply(),
            'refund_commision' => $this->isRefundCommision()
        ];
    }
}