<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\User;

/**
 * 使用者信箱認證
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\EmailVerifyCodeRepository")
 * @ORM\Table(
 *      name = "email_verify_code",
 *      indexes = {
 *          @ORM\Index(name = "idx_email_verify_code_code", columns = {"code"})
 *      }
 * )
 *
 * @author Ruby 2015.03.27
 */
class EmailVerifyCode
{
    /**
     * 信箱對應的使用者
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "user_id", type = "integer")
     */
    private $userId;

    /**
     * 認證金鑰
     *
     * @var string
     *
     * @ORM\Column(name = "code", type = "string", length = 64)
     */
    private $code;

    /**
     * 認證時效
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "expire_at", type = "datetime")
     */
    private $expireAt;

    /**
     * 新增email認證
     *
     * @param integer   $userId   使用者id
     * @param string    $code     認證金鑰
     * @param \DateTime $expireAt 認證時效
     */
    public function __construct($userId, $code, $expireAt)
    {
        $this->userId = $userId;
        $this->code = $code;
        $this->expireAt = $expireAt;
    }

    /**
     * 回傳所屬的使用者id
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * 設定認證金鑰
     *
     * @param string $code 認證金鑰
     * @return EmailVerifyCode
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * 回傳認證金鑰
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * 設定認證時效
     *
     * @param \DateTime $expireAt 時效
     * @return EmailVerifyCode
     */
    public function setExpireAt(\DateTime $expireAt)
    {
        $this->expireAt = $expireAt;

        return $this;
    }

    /**
     * 回傳認證時效
     *
     * @return \DateTime
     */
    public function getExpireAt()
    {
        return $this->expireAt;
    }
}
