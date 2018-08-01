<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 異常入款提醒email
 *
 * @ORM\Entity
 * @ORM\Table(name = "abnormal_deposit_notify_email")
 */
class AbnormalDepositNotifyEmail
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type = "integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 信箱
     *
     * @var string
     *
     * @ORM\Column(name = "email", type = "string", length = 254)
     */
    private $email;

    /**
     * AbnormalDepositNotifyEmail constructor
     *
     * @param string $email 信箱
     */
    public function __construct($email)
    {
        $this->email = $email;
    }

    /*
     * 回傳id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 設定信箱
     *
     * @param string $email 信箱
     * @return AbnormalDepositNotifyEmail
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
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'email' => $this->getEmail(),
        ];
    }
}
