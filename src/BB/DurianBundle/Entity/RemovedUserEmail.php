<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\UserEmail;

/**
 * 移除的使用者信箱
 *
 * @ORM\Entity
 * @ORM\Table(name = "removed_user_email")
 *
 * @author sin-hao 2015.07.13
 */
class RemovedUserEmail
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
     * 信箱
     *
     * @var string
     *
     * @ORM\Column(name = "email", type = "string", length = 254, nullable = true)
     */
    private $email;

    /**
     * 是否認證完成
     *
     * @var boolean
     *
     * @ORM\Column(name = "confirm", type = "boolean")
     */
    private $confirm;

    /**
     * 認證完成時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "confirm_at", type = "datetime", nullable = true)
     */
    private $confirmAt;

    /**
     * @param RemovedUser $removedUser 對應被刪除的使用者
     * @param UserEmail  $userEmail  要刪除的使用者信箱
     */
    public function __construct(RemovedUser $removedUser, UserEmail $userEmail)
    {
        if ($removedUser->getUserId() != $userEmail->getUser()->getId()) {
            throw new \RuntimeException('UserEmail not belong to this user', 150010132);
        }

        $this->removedUser = $removedUser;
        $this->email = $userEmail->getEmail();
        $this->confirm = $userEmail->isConfirm();
        $this->confirmAt = $userEmail->getConfirmAt();
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
     * 取得email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * 是否認證完成
     *
     * @return bool
     */
    public function isConfirm()
    {
        return (bool) $this->confirm;
    }

    /**
     * 回傳認證完成時間
     *
     * @return \DateTime
     */
    public function getConfirmAt()
    {
        return $this->confirmAt;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $confirmAt = null;
        if ($this->getConfirmAt()) {
            $confirmAt = $this->getConfirmAt()->format(\DateTime::ISO8601);
        }

        return [
            'user_id' => $this->getRemovedUser()->getUserId(),
            'email' => $this->getEmail(),
            'confirm' => $this->isConfirm(),
            'confirm_at' => $confirmAt
        ];
    }
}
