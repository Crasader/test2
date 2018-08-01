<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 登入記錄行動裝置資訊
 *
 * @ORM\Table(name = "login_log_mobile")
 * @ORM\Entity
 */
class LoginLogMobile
{
    /**
     * @var integer 使用者登入記錄ID
     *
     * @ORM\Column(name = "login_log_id", type = "bigint", options = {"unsigned" = true})
     * @ORM\Id
     */
    private $loginLogId;

    /**
     * @var string 裝置名稱
     *
     * @ORM\Column(name = "name", type = "string", length = 30, nullable = true)
     */
    private $name;

    /**
     * @var string 廠牌
     *
     * @ORM\Column(name = "brand", type = "string", length = 30, nullable = true)
     */
    private $brand;

    /**
     * @var string 型號
     *
     * @ORM\Column(name = "model", type = "string", length = 30, nullable = true)
     */
    private $model;

    /**
     * @param LoginLog $loginLog 使用者登入記錄
     */
    public function __construct($loginLog)
    {
        $this->loginLogId = $loginLog->getId();
    }

    /**
     * 取得使用者登入記錄ID
     *
     * @return integer
     */
    public function getLoginLogId()
    {
        return $this->loginLogId;
    }

    /**
     * 設定裝置名稱
     *
     * @param string $name 裝置名稱
     * @return LoginLogMobile
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * 取得裝置名稱
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 設定廠牌
     *
     * @param string $brand 廠牌
     * @return LoginLogMobile
     */
    public function setBrand($brand)
    {
        $this->brand = $brand;

        return $this;
    }

    /**
     * 取得廠牌
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
     * @return LoginLogMobile
     */
    public function setModel($model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * 取得型號
     *
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * 回傳資料(sync_login_log_mobile專用)
     *
     * @return array
     */
    public function getInfo()
    {
        return [
            'login_log_id' => $this->getLoginLogId(),
            'name' => $this->getName(),
            'brand' => $this->getBrand(),
            'model' => $this->getModel()
        ];
    }
}
