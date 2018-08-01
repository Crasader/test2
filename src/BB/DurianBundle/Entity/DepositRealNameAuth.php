<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 線上支付實名認證
 *
 * @ORM\Entity
 * @ORM\Table(name = "deposit_real_name_auth")
 */
class DepositRealNameAuth
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
     * 加密字串
     *
     * @var string
     *
     * @ORM\Column(name = "encrypt_text", type = "string", length = 32)
     */
    private $encryptText;

    /**
     * DepositRealNameAuth constructor
     *
     * @param string $encryptText 加密字串
     */
    public function __construct($encryptText)
    {
        $this->encryptText = $encryptText;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 回傳加密字串
     *
     * @return string
     */
    public function getEncryptText()
    {
        return $this->encryptText;
    }
}
