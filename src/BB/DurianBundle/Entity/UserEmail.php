<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\RemovedUserEmail;

/**
 * 使用者的信箱
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\UserEmailRepository")
 * @ORM\Table(
 *     name = "user_email",
 *     indexes = {@ORM\Index(name = "idx_user_email_email", columns = {"email"})}
 * )
 *
 * @author Squarer 2015.03.18
 */
class UserEmail
{
    /**
     * 信箱對應的使用者
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
     * 信箱
     *
     * @var string
     *
     * @ORM\Column(name = "email", type = "string", length = 254)
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
     * @param User $user 使用者
     */
    public function __construct(User $user)
    {
        $this->confirm = false;
        $this->user = $user;
    }

    /**
     * 設定信箱
     *
     * @param string $email
     * @return UserEmail
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * 回傳信箱
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
     * 設定認證完成
     *
     * @param boolean $bool
     * @return UserEmail
     */
    public function setConfirm($bool)
    {
        $this->confirm = (bool) $bool;

        return $this;
    }

    /**
     * 設定認證完成時間
     *
     * @param \DateTime $date
     * @return UserEmail
     */
    public function setConfirmAt(\DateTime $date)
    {
        $this->confirmAt = $date;

        return $this;
    }

    /**
     * 清除認證完成時間
     *
     * @return UserEmail
     */
    public function removeConfirmAt()
    {
        $this->confirmAt = NULL;

        return $this;
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
     * 回傳信箱所屬的user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * 從刪除使用者信箱備份設定使用者信箱
     *
     * @param RemovedUserEmail $removedEmail 已刪除的信箱
     * @return UserEmail
     */
    public function setFromRemoved(RemovedUserEmail $removedEmail)
    {
        if ($this->getUser()->getId() != $removedEmail->getRemovedUser()->getUserId()) {
            throw new \RuntimeException('UserEmail not belong to this user', 150010132);
        }

        $this->email = $removedEmail->getEmail();
        $this->confirm = $removedEmail->isConfirm();
        $this->confirmAt = $removedEmail->getConfirmAt();

        return $this;
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
            'user_id'     => $this->getUser()->getId(),
            'email'       => $this->getEmail(),
            'confirm'     => $this->isConfirm(),
            'confirm_at'  => $confirmAt
        ];
    }
}
