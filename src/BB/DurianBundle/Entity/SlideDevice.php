<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * 手勢登入裝置
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name = "slide_device",
 *     uniqueConstraints = {
 *         @ORM\UniqueConstraint(
 *             name = "uni_slide_device_app",
 *             columns = {"app_id"})
 *     }
 * )
 */
class SlideDevice
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
     * 裝置識別ID
     *
     * @var string
     *
     * @ORM\Column(name = "app_id", type = "string", length = 100)
     */
    private $appId;

    /**
     * 手勢密碼
     *
     * @var string
     *
     * @ORM\Column(name = "hash", type = "string", length = 100)
     */
    private $hash;

    /**
     * 驗證錯誤次數
     *
     * @var integer
     *
     * @ORM\Column(name = "err_num", type = "integer")
     */
    private $errNum;

    /**
     * 是否啟用手勢登入
     *
     * @var boolean
     *
     * @ORM\Column(name = "enabled", type = "boolean")
     */
    private $enabled;

    /**
     * 作業系統
     *
     * @var string
     *
     * @ORM\Column(name = "os", type = "string", length = 30, nullable = true)
     */
    private $os;

    /**
     * 廠牌
     *
     * @var string
     *
     * @ORM\Column(name = "brand", type = "string", length = 30, nullable = true)
     */
    private $brand;

    /**
     * 型號
     *
     * @var string
     *
     * @ORM\Column(name = "model", type = "string", length = 30, nullable = true)
     */
    private $model;

    /**
     * 手勢登入綁定
     *
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity = "SlideBinding", mappedBy = "device")
     */
    private $bindings;

    /**
     * @param string $appId 裝置識別ID
     * @param string $hash 手勢密碼
     */
    public function __construct($appId, $hash)
    {
        $this->appId = $appId;
        $this->hash = $hash;
        $this->errNum = 0;
        $this->enabled = true;
        $this->bindings = new ArrayCollection;
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
     * 回傳裝置識別ID
     *
     * @return string
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * 設定手勢密碼
     *
     * @param string $hash 手勢密碼
     * @return SlideDevice
     */
    public function setHash($hash)
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * 回傳手勢密碼
     *
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * 裝置綁定一個新的手勢登入
     *
     * @param SlideBinding $binding 手勢登入綁定
     * @return SlideDevice
     */
    public function addBinding(SlideBinding $binding)
    {
        $this->bindings[] = $binding;

        return $this;
    }

    /**
     * 回傳裝置綁定的帳號數量
     *
     * @return integer
     */
    public function countBindings()
    {
        return count($this->bindings);
    }

    /**
     * 回傳裝置所有的手勢登入綁定
     *
     * @return ArrayCollection
     */
    public function getBindings()
    {
        return $this->bindings;
    }

    /**
     * 增加驗證錯誤次數
     *
     * @return SlideDevice
     */
    public function addErrNum()
    {
        $this->errNum++;

        return $this;
    }

    /**
     * 歸零驗證錯誤次數
     *
     * @return SlideDevice
     */
    public function zeroErrNum()
    {
        if ($this->errNum != 0) {
            $this->errNum = 0;
        }

        return $this;
    }

    /**
     * 設定驗證錯誤次數
     *
     * @param integer $errNum 驗證錯誤次數
     * @return SlideDevice
     */
    public function setErrNum($errNum)
    {
        $this->errNum = $errNum;

        return $this;
    }

    /**
     * 回傳驗證錯誤次數
     *
     * @return integer
     */
    public function getErrNum()
    {
        return $this->errNum;
    }

    /**
     * 停用手勢登入
     *
     * @return SlideDevice
     */
    public function disable()
    {
        $this->enabled = false;

        return $this;
    }

    /**
     * 啟用手勢登入
     *
     * @return SlideDevice
     */
    public function enable()
    {
        $this->enabled = true;

        return $this;
    }

    /**
     * 回傳是否啟用手勢登入
     *
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * 設定作業系統
     *
     * @param string $os 作業系統
     * @return SlideDevice
     */
    public function setOs($os)
    {
        $this->os = $os;

        return $this;
    }

    /**
     * 回傳作業系統
     *
     * @return string
     */
    public function getOs()
    {
        return $this->os;
    }

    /**
     * 設定廠牌
     *
     * @param string $brand 廠牌
     * @return SlideDevice
     */
    public function setBrand($brand)
    {
        $this->brand = $brand;

        return $this;
    }

    /**
     * 回傳廠牌
     *
     * @return string
     */
    public function getBrand()
    {
        return $this->brand;
    }

    /**
     * 設定型號
     *
     * @param string $model 型號
     * @return SlideDevice
     */
    public function setModel($model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * 回傳型號
     *
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }
}
