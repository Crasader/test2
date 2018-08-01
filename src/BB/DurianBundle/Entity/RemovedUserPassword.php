<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\UserPassword;

/**
 * 移除的使用者密碼
 *
 * @ORM\Entity
 * @ORM\Table(name = "removed_user_password")
 *
 * @author Cullen 2015.11.18
 */
class RemovedUserPassword
{
    /**
     * 對應被移除的RemovedUser
     *
     * @var RemovedUser
     *
     * @ORM\Id
     * @ORM\OneToOne(targetEntity = "RemovedUser")
     * @ORM\JoinColumn(name = "user_id",
     *     referencedColumnName = "user_id",
     *     nullable = false)
     */
    private $removedUser;

    /**
     * 登入密碼
     *
     * @var string
     *
     * @ORM\Column(name = "hash", type = "string", length = 100)
     */
    private $hash;

    /**
     * 密碼有效時間
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
     * @param RemovedUser  $removedUser  對應被刪除的使用者
     * @param UserPassword $userPassword 要刪除的使用者密碼
     */
    public function __construct(RemovedUser $removedUser, UserPassword $userPassword)
    {
        if ($removedUser->getUserId() != $userPassword->getUser()->getId()) {
            throw new \RuntimeException('UserPassword not belong to this user', 150010138);
        }

        $now = new \DateTime('now');

        $this->removedUser = $removedUser;
        $this->hash = $userPassword->getHash();
        $this->expireAt = $userPassword->getExpireAt();

        // 若expire_at欄位為0000-00-00 00:00:00, format會變成-0001-11-3, 改塞$now
        if ($this->expireAt->format('Y-m-d') == '-0001-11-30') {
            $this->expireAt = $now;
        }

        $this->modifiedAt = $now;
        $this->reset = $userPassword->isReset();
        $this->oncePassword = $userPassword->getOncePassword();
        $this->used = $userPassword->isUsed();
        $this->onceExpireAt = $userPassword->getOnceExpireAt();
        $this->errNum = $userPassword->getErrNum();
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
     * 回傳登入密碼
     *
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
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
     * 回傳臨時密碼逾期時間
     *
     * @return \DateTime
     */
    public function getOnceExpireAt()
    {
        return $this->onceExpireAt;
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
     * @return array
     */
    public function toArray()
    {
        $onceExpireAt = null;
        if (!is_null($this->getOnceExpireAt())) {
            $onceExpireAt = $this->getOnceExpireAt()->format(\DateTime::ISO8601);
        }

        return [
            'user_id' => $this->getRemovedUser()->getUserId(),
            'hash' => $this->getHash(),
            'expire_at' => $this->getExpireAt()->format(\DateTime::ISO8601),
            'modified_at' => $this->getModifiedAt()->format(\DateTime::ISO8601),
            'reset' => $this->isReset(),
            'once_password' => $this->getOncePassword(),
            'used' => $this->isUsed(),
            'once_expire_at' => $onceExpireAt,
            'err_num' => $this->getErrNum()
        ];
    }
}
