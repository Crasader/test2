<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\User;

/**
 * 使用者最近登入遊戲(並記錄免轉錢包是否啟用)
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\UserLastGameRepository")
 * @ORM\Table(name = "user_last_game")
 */
class UserLastGame
{
    /**
     * 使用者編號
     *
     * @var User
     *
     * @ORM\Id
     * @ORM\OneToOne(targetEntity = "User")
     * @ORM\JoinColumn(name = "user_id", referencedColumnName = "id")
     */
    private $user;

    /**
     * 免轉錢包停啟用(廳主設定會員自選時才有效)
     *
     * @var boolean
     *
     * @ORM\Column(name = "enable", type = "boolean")
     */
    private $enable;

    /**
     * 最近登入的遊戲代碼
     *
     * @var integer
     *
     * @ORM\Column(name = "last_game_code", type = "smallint", options = {"unsigned" = true, "default" = 1})
     */
    private $lastGameCode;

    /**
     * 修改時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "modified_at", nullable = true, type = "datetime")
     */
    private $modifiedAt;

    /**
     * 版本號
     *
     * @var integer
     *
     * @ORM\Column(name = "version", type = "integer", options = {"unsigned" = true})
     * @ORM\Version
     */
    private $version;

    /**
     * 建構子
     *
     * @param User $user 使用者
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->enable = true;
        $this->lastGameCode = 1;
    }

    /**
     * 回傳對應使用者
     *
     * @return integer
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * 回傳免轉錢包是否啟用
     *
     * @return boolean
     */
    public function isEnabled()
    {
        return (bool) $this->enable;
    }

    /**
     * 啟用免轉錢包
     *
     * @return UserLastGame
     */
    public function enable()
    {
        $this->enable = true;

        return $this;
    }

    /**
     * 停用免轉錢包
     *
     * @return UserLastGame
     */
    public function disable()
    {
        $this->enable = false;

        return $this;
    }

    /**
     * 回傳最近登入遊戲
     *
     * @return Integer
     */
    public function getLastGameCode()
    {
        return $this->lastGameCode;
    }

    /**
     * 設定最後登入遊戲
     *
     * @param Integer $code 最後登入遊戲game code
     * @return UserLastGame
     */
    public function setLastGameCode($code)
    {
        $this->lastGameCode = $code;

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
     * 設定修改時間
     *
     * @param  \DateTime $date
     * @return UserLastGame
     */
    public function setModifiedAt(\DateTime $date)
    {
        $this->modifiedAt = $date;

        return $this;
    }

    /**
     * 回傳版本號
     *
     * @return Integer
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $ret = [
            'user_id' => $this->getUser()->getId(),
            'enable' => $this->isEnabled(),
            'last_game_code' => $this->getLastGameCode(),
            'modified_at' => null
        ];

        if ($this->getModifiedAt()) {
            $ret['modified_at'] = $this->getModifiedAt()->format(\DateTime::ISO8601);
        }

        return $ret;
    }
}
