<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 手勢登入綁定
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\SlideBindingRepository")
 * @ORM\Table(
 *     name = "slide_binding",
 *     uniqueConstraints = {
 *         @ORM\UniqueConstraint(
 *             name = "uni_slide_binding_user_device",
 *             columns = {"user_id", "device_id"})
 *     }
 * )
 */
class SlideBinding
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "bigint", options = {"unsigned" = true})
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 手勢登入綁定的使用者ID
     *
     * @var integer
     *
     * @ORM\Column(name = "user_id", type = "integer")
     */
    private $userId;

    /**
     * 手勢登入綁定的裝置
     *
     * @var SlideDevice
     *
     * @ORM\ManyToOne(targetEntity = "SlideDevice", inversedBy = "bindings")
     * @ORM\JoinColumn(
     *     name = "device_id",
     *     referencedColumnName = "id",
     *     nullable = false,
     *     onDelete = "CASCADE"
     * )
     */
    private $device;

    /**
     * 綁定名稱
     *
     * @var string
     *
     * @ORM\Column(name = "name", type = "string", length = 100, nullable = true)
     */
    private $name;

    /**
     * 綁定標記
     *
     * @var string
     *
     * @ORM\Column(name = "binding_token", type = "string", length = 100)
     */
    private $bindingToken;

    /**
     * 建立時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "created_at", type = "datetime")
     */
    private $createdAt;

    /**
     * 登入錯誤次數
     *
     * @var integer
     *
     * @ORM\Column(name = "err_num", type = "integer")
     */
    private $errNum;

    /**
     * @param integer $userId 使用者ID
     * @param SlideDevice $device 手勢登入裝置
     */
    public function __construct($userId, $device)
    {
        $this->userId = $userId;
        $this->device = $device;
        $this->createdAt = new \DateTime();
        $this->errNum = 0;

        $device->addBinding($this);
    }

    /**
     * 回傳id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 回傳綁定的使用者ID
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * 回傳綁定的裝置
     *
     * @return SlideDevice
     */
    public function getDevice()
    {
        return $this->device;
    }

    /**
     * 設定裝置名稱
     *
     * @param string $name 裝置名稱
     * @return SlideBinding
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * 回傳裝置名稱
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 設定綁定標記
     *
     * @param string $bindingToken 綁定標記
     * @return SlideBinding
     */
    public function setBindingToken($bindingToken)
    {
        $this->bindingToken = $bindingToken;

        return $this;
    }

    /**
     * 回傳綁定標記
     *
     * @return string
     */
    public function getBindingToken()
    {
        return $this->bindingToken;
    }

    /**
     * 回傳建立時間
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * 增加登入錯誤次數
     *
     * @return SlideBinding
     */
    public function addErrNum()
    {
        $this->errNum++;

        return $this;
    }

    /**
     * 歸零登入錯誤次數
     *
     * @return SlideBinding
     */
    public function zeroErrNum()
    {
        if ($this->errNum != 0) {
            $this->errNum = 0;
        }

        return $this;
    }

    /**
     * 設定登入錯誤次數
     *
     * @param integer $errNum 登入錯誤次數
     * @return SlideBinding
     */
    public function setErrNum($errNum)
    {
        $this->errNum = $errNum;

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
}
