<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\User;

/**
 * 使用者存提款紀錄
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\UserHasDepositWithdrawRepository")
 * @ORM\Table(
 *     name = "user_has_deposit_withdraw",
 *     indexes = {
 *         @ORM\Index(name = "idx_user_has_deposit_withdraw_first_deposit_at", columns = {"first_deposit_at"})
 *     }
 * )
 */
class UserHasDepositWithdraw
{
    /**
     * 存提款紀錄對應的使用者ID
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="user_id", type="integer")
     */
    private $userId;

    /**
     * 入款統計日期
     *
     * @var \DateTime
     *
     * @ORM\Column(name="deposit_at", type="datetime", nullable = true)
     */
    private $depositAt;

    /**
     * 出款統計日期
     *
     * @var \DateTime
     *
     * @ORM\Column(name="withdraw_at", type="datetime", nullable = true)
     */
    private $withdrawAt;

    /**
     * 是否入款
     *
     * #var boolean
     *
     * @ORM\Column(name="deposit", type="boolean")
     */
    private $deposit;

    /**
     * 是否出款
     *
     * #var boolean
     *
     * @ORM\Column(name="withdraw", type="boolean")
     */
    private $withdraw;

    /**
     * 首次入款時間
     *
     * @var integer
     *
     * @ORM\Column(name = "first_deposit_at", type = "bigint", options = {"unsigned" = true, "default" = 0})
     */
    private $firstDepositAt;

    /**
     * 建構子
     *
     * @param User      $user       使用者
     * @param \DateTime $depositAt  入款紀錄日期
     * @param \DateTime $withdrawAt 出款紀錄日期
     * @param boolean   $deposit    是否入款
     * @param boolean   $withdraw   是否出款
     */
    public function __construct(User $user, $depositAt, $withdrawAt, $deposit, $withdraw)
    {
        $this->userId = $user->getId();
        $this->depositAt = $depositAt;
        $this->withdrawAt = $withdrawAt;
        $this->deposit = $deposit;
        $this->withdraw = $withdraw;
        $this->firstDepositAt = 0;
    }

    /**
     * 取得使用者ID
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * 取得入款統計日期
     *
     * @return \DateTime
     */
    public function getDepositAt()
    {
        return $this->depositAt;
    }

    /**
     * 設定入款統計日期
     *
     * @param \DateTime $at
     * @return UserHasDepositWithdraw
     */
    public function setDepositAt($at)
    {
        $this->depositAt = $at;

        return $this;
    }

    /**
     * 取得出款統計日期
     *
     * @return \DateTime
     */
    public function getWithdrawAt()
    {
        return $this->withdrawAt;
    }

    /**
     * 設定出款統計日期
     *
     * @param \DateTime $at
     * @return UserHasDepositWithdraw
     */
    public function setWithdrawAt($at)
    {
        $this->withdrawAt = $at;

        return $this;
    }

    /**
     * 取得入款
     *
     * @return boolean
     */
    public function isDeposited()
    {
        return $this->deposit;
    }

    /**
     * 設定入款
     *
     * @param boolean $deposit
     * @return UserHasDepositWithdraw
     */
    public function setDeposit($deposit)
    {
        $this->deposit = $deposit;

        return $this;
    }

    /**
     * 取得出款
     *
     * @return boolean
     */
    public function isWithdrew()
    {
        return $this->withdraw;
    }

    /**
     * 設定出款
     *
     * @param boolean $withdraw
     * @return UserHasDepositWithdraw
     */
    public function setWithdraw($withdraw)
    {
        $this->withdraw = $withdraw;

        return $this;
    }

    /**
     * 取得首次入款時間
     *
     * @return null|\DateTime
     */
    public function getFirstDepositAt()
    {
        if (!$this->firstDepositAt) {
            return null;
        }

        return new \DateTime($this->firstDepositAt);
    }

    /**
     * 設定首次入款時間
     *
     * @param integer $firstDepositAt
     * @return UserHasDepositWithdraw
     */
    public function setFirstDepositAt($firstDepositAt)
    {
        $this->firstDepositAt = $firstDepositAt;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $depositAt = null;
        $withdrawAt = null;

        if (null !== $this->getDepositAt()) {
            $depositAt = $this->getDepositAt()->format(\DateTime::ISO8601);
        }

        if (null !== $this->getWithdrawAt()) {
            $withdrawAt = $this->getWithdrawAt()->format(\DateTime::ISO8601);
        }

        $firstDepositAt = $this->getFirstDepositAt();

        // 如果首存時間非null則調整回傳時間格式
        if ($firstDepositAt) {
            $firstDepositAt = $firstDepositAt->format(\DateTime::ISO8601);
        }

        return [
            'user_id' => $this->getUserId(),
            'deposit_at' => $depositAt,
            'withdraw_at' => $withdrawAt,
            'deposit' => $this->isDeposited(),
            'withdraw' => $this->isWithdrew(),
            'first_deposit_at' => $firstDepositAt,
        ];
    }
}
