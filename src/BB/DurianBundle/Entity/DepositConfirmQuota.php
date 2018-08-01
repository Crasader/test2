<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 人工入款最大金額
 *
 * @ORM\Entity
 * @ORM\Table(name = "deposit_confirm_quota")
 */
class DepositConfirmQuota
{
    /**
     * 使用者id
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
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->userId = $user->getId();
        $this->amount = 0;
    }

    /**
     * 回傳使用者id
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * 回傳最大金額
     *
     * @return integer
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * 設定最大金額
     *
     * @param integer $amount
     * @return DepositConfirmQuota
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'user_id' => $this->getUserId(),
            'amount' => $this->getAmount()
        ];
    }
}