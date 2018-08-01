<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\User;

/**
 * 使用者支援的交易方式
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\UserPaywayRepository")
 * @ORM\Table(name = "user_payway")
 */
class UserPayway
{
    /**
     * 使用者編號
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "user_id", type = "integer")
     */
    private $userId;

    /**
     * Cash 是否啟用
     *
     * @var boolean
     *
     * @ORM\Column(type = "boolean")
     */
    private $cash;

    /**
     * CashFake 是否啟用
     *
     * @var boolean
     *
     * @ORM\Column(name = "cash_fake", type = "boolean")
     */
    private $cashFake;

    /**
     * Credit 是否啟用
     *
     * @var boolean
     *
     * @ORM\Column(type = "boolean")
     */
    private $credit;

    /**
     * 外接額度 是否啟用
     *
     * @var boolean
     *
     * @ORM\Column(type = "boolean")
     */
    private $outside;

    /**
     * 建構子
     *
     * @param User $user 使用者
     */
    public function __construct(User $user)
    {
        $this->userId = $user->getId();
        $this->cash = false;
        $this->cashFake = false;
        $this->credit = false;
        $this->outside = false;
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
     * 回傳 Cash 是否啟用
     *
     * @return boolean
     */
    public function isCashEnabled()
    {
        return (bool) $this->cash;
    }

    /**
     * 啟用 Cash
     *
     * @return UserPayway
     */
    public function enableCash()
    {
        $this->cash = true;

        return $this;
    }

    /**
     * 停用 Cash
     *
     * @return UserPayway
     */
    public function disableCash()
    {
        $this->cash = false;

        return $this;
    }

    /**
     * 回傳 CashFake 是否啟用
     *
     * @return boolean
     */
    public function isCashFakeEnabled()
    {
        return (bool) $this->cashFake;
    }

    /**
     * 啟用 CashFake
     *
     * @return UserPayway
     */
    public function enableCashFake()
    {
        $this->cashFake = true;

        return $this;
    }

    /**
     * 停用 CashFake
     *
     * @return UserPayway
     */
    public function disableCashFake()
    {
        $this->cashFake = false;

        return $this;
    }

    /**
     * 回傳 Credit 是否啟用
     *
     * @return boolean
     */
    public function isCreditEnabled()
    {
        return (bool) $this->credit;
    }

    /**
     * 啟用 Credit
     *
     * @return UserPayway
     */
    public function enableCredit()
    {
        $this->credit = true;

        return $this;
    }

    /**
     * 停用 Credit
     *
     * @return UserPayway
     */
    public function disableCredit()
    {
        $this->credit = false;

        return $this;
    }

    /**
     * 回傳 外接額度 是否啟用
     *
     * @return boolean
     */
    public function isOutsideEnabled()
    {
        return (bool) $this->outside;
    }

    /**
     * 啟用 外接額度
     *
     * @return UserPayway
     */
    public function enableOutside()
    {
        $this->outside = true;

        return $this;
    }

    /**
     * 停用 外接額度
     *
     * @return UserPayway
     */
    public function disableOutside()
    {
        $this->outside = false;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'user_id'   => $this->getUserId(),
            'cash'      => $this->isCashEnabled(),
            'cash_fake' => $this->isCashFakeEnabled(),
            'credit'    => $this->isCreditEnabled(),
            'outside'   => $this->isOutsideEnabled()
        ];
    }
}
