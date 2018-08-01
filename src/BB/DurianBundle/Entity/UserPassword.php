<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\RemovedUserPassword;

/**
 * 使用者的密碼
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\UserPasswordRepository")
 * @ORM\Table(name = "user_password")
 *
 * @author George 2014.11.18
 */
class UserPassword
{
    /**
     * 密碼對應的使用者
     *
     * @var User
     *
     * @ORM\Id
     * @ORM\OneToOne(targetEntity = "User")
     * @ORM\JoinColumn(name = "user_id",
     *     referencedColumnName = "id",
     *     nullable = false)
     */
    private $user;

    /**
     * 登入密碼
     *
     * @var string
     *
     * @ORM\Column(name = "hash", type = "string", length = 100)
     */
    private $hash;

    /**
     * 密碼有效時間。過期需要更換密碼
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "expire_at", type = "datetime")
     */
    private $expireAt;

    /**
     * 修改時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "modified_at", type = "datetime")
     */
    private $modifiedAt;

    /**
     * 重設密碼設定
     *
     * @var boolean
     *
     * @ORM\Column(name = "reset", type = "boolean")
     */
    private $reset;

    /**
     * 臨時密碼
     *
     * @var string
     *
     * @ORM\Column(name = "once_password", type = "string", length = 100, nullable = true)
     */
    private $oncePassword;

    /**
     * 臨時密碼是否使用過
     *
     * @var boolean
     *
     * @ORM\Column(name = "used", type = "boolean")
     */
    private $used;

    /**
     * 臨時密碼逾期時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "once_expire_at", type = "datetime", nullable = true)
     */
    private $onceExpireAt;

    /**
     * 登入錯誤次數
     *
     * @var integer
     *
     * @ORM\Column(name = "err_num", type = "integer")
     */
    private $errNum;

    /**
     * @param User $user 密碼的擁有者
     */
    public function __construct(User $user)
    {
        $now = new \DateTime('now');

        $expireAt = clone $now;
        $expireAt->add(new \DateInterval('P30D'));

        $this->modifiedAt = $now;
        $this->expireAt = $expireAt;
        $this->reset = false;
        $this->errNum = 0;
        $this->user = $user;
        $this->used = false;
    }

    /**
     * 設定密碼
     *
     * @param string $value
     * @return UserPassword
     */
    public function setHash($value)
    {
        $now = new \DateTime('now');

        $expireAt = clone $now;
        $expireAt->add(new \DateInterval('P30D'));

        $this->hash = $value;
        $this->modifiedAt = $now;
        $this->expireAt = $expireAt;

        return $this;
    }

    /**
     * 回傳登入密碼
     *
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * 設定密碼逾期時間
     *
     * @param \DateTime $date
     * @return UserPassword
     */
    public function setExpireAt(\DateTime $date)
    {
        $this->expireAt = $date;

        return $this;
    }

    /**
     * 回傳密碼逾期時間
     *
     * @return \DateTime
     */
    public function getExpireAt()
    {
        return $this->expireAt;
    }

    /**
     * 設定密碼修改時間
     *
     * @param \DateTime $date
     * @return UserPassword
     */
    public function setModifiedAt(\DateTime $date)
    {
        $this->modifiedAt = $date;

        return $this;
    }

    /**
     * 回傳修改時間
     *
     * @return \DateTime
     */
    public function getModifiedAt()
    {
        return $this->modifiedAt;
    }

    /**
     * 是否須重設密碼
     *
     * @return bool
     */
    public function isReset()
    {
        return (bool) $this->reset;
    }

    /**
     * 設定重設密碼開關
     *
     * @param boolean $bool
     * @return UserPassword
     */
    public function setReset($bool)
    {
        $this->reset = (bool) $bool;

        return $this;
    }

    /**
     * 回傳登入錯誤次數
     *
     * @return integer
     */
    public function getErrNum()
    {
        return $this->errNum;
    }

    /**
     * 增加登入錯誤次數
     *
     * @return UserPassword
     */
    public function addErrNum()
    {
        $this->errNum++;

        return $this;
    }

    /**
     * 歸零登入錯誤次數
     *
     * @return UserPassword
     */
    public function zeroErrNum()
    {
        $this->errNum = 0;

        return $this;
    }

    /**
     * 設定登入錯誤次數
     *
     * @param integer $errNum 登入錯誤次數
     * @return UserPassword
     */
    public function setErrNum($errNum)
    {
        $this->errNum = $errNum;

        return $this;
    }

    /**
     * 回傳密碼所屬的user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * 設定臨時密碼
     *
     * @param string $oncePassword 臨時密碼
     * @return UserPassword
     */
    public function setOncePassword($oncePassword)
    {
        $this->oncePassword = $oncePassword;

        return $this;
    }

    /**
     * 回傳臨時密碼
     *
     * @return string
     */
    public function getOncePassword()
    {
        return $this->oncePassword;
    }

    /**
     * 臨時密碼是否使用過
     *
     * @return bool
     */
    public function isUsed()
    {
        return (bool) $this->used;
    }

    /**
     * 設定臨時密碼是否使用過
     *
     * @param boolean $bool 使用設定
     * @return UserPassword
     */
    public function setUsed($bool)
    {
        $this->used = (bool) $bool;

        return $this;
    }

    /**
     * 設定臨時密碼逾期時間
     *
     * @param \DateTime $date 時間
     * @return UserPassword
     */
    public function setOnceExpireAt(\DateTime $date)
    {
        $this->onceExpireAt = $date;

        return $this;
    }

    /**
     * 回傳臨時密碼逾期時間
     *
     * @return \DateTime
     */
    public function getOnceExpireAt()
    {
        return $this->onceExpireAt;
    }

    /**
     * 從刪除使用者密碼備份設定使用者密碼
     *
     * @param RemovedUserPassword $removedPassword 已刪除的密碼
     * @return UserPassword
     */
    public function setFromRemoved(RemovedUserPassword $removedPassword)
    {
        if ($this->getUser()->getId() != $removedPassword->getRemovedUser()->getUserId()) {
            throw new \RuntimeException('UserPassword not belong to this user', 150010138);
        }

        $now = new \DateTime('now');

        $this->hash = $removedPassword->getHash();
        $this->expireAt = $removedPassword->getExpireAt();

        // 若expire_at欄位為0000-00-00 00:00:00, format會變成-0001-11-3, 改塞$now
        if ($this->expireAt->format('Y-m-d') == '-0001-11-30') {
            $this->expireAt = $now;
        }

        $this->modifiedAt = $now;
        $this->reset = $removedPassword->isReset();
        $this->oncePassword = $removedPassword->getOncePassword();
        $this->used = $removedPassword->isUsed();
        $this->onceExpireAt = $removedPassword->getOnceExpireAt();
        $this->errNum = $removedPassword->getErrNum();

        return $this;
    }
}
