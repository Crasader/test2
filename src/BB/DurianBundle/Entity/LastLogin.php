<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 使用者最後登錄紀錄
 *
 * @ORM\Entity
 * @ORM\Table(
 *      name = "last_login",
 *      indexes = {
 *          @ORM\Index(name = "idx_last_login_ip", columns = {"ip"})
 *      }
 * )
 *
 * @author Michael 2016.6.1
 */
class LastLogin
{
    /**
     * 對應的使用者編號
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "user_id", type = "bigint", options = {"unsigned" = true})
     */
    private $userId;

    /**
     * 最後成功登入紀錄 id
     *
     * @var integer
     *
     * @ORM\Column(name = "login_log_id", type = "bigint", options = {"unsigned" = true, "default" = 0})
     */
    private $loginLogId;

    /**
     * 登入ip
     *
     * @var integer
     *
     * @ORM\Column(name = "ip", type = "integer", options = {"unsigned" = true})
     */
    private $ip;

    /**
     * 登入錯誤次數
     *
     * $var integer
     *
     * @ORM\Column(name = "err_num", type = "integer")
     */
    private $errNum;

    /**
     * 最後登入時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "at", type = "datetime", nullable = true)
     */
    private $at;

    /**
     * @param integer $userId 使用者編號
     * @param string $ip 登入ip
     */
    public function __construct($userId, $ip)
    {
        $this->userId = $userId;
        $this->ip = ip2long($ip);
        $this->errNum = 0;
        $this->loginLogId = 0;
    }

    /**
     * 回傳使用者編號
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * 回傳最後登入 id
     *
     * @return integer
     */
    public function getLoginLogId()
    {
        return $this->loginLogId;
    }

    /**
     * 設定最後登入 id
     *
     * @param integer $id 登入紀錄 id
     * @return LastLogin
     */
    public function setLoginLogId($id)
    {
        $this->loginLogId = $id;

        return $this;
    }

    /**
     * 設定登入ip
     *
     * @param string $ip 登入ip
     * @return LastLogin
     */
    public function setIp($ip)
    {
        $this->ip = ip2long($ip);

        return $this;
    }

    /**
     * 回傳登入ip
     *
     * @return string
     */
    public function getIp()
    {
        return long2ip($this->ip);
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
     * 增加登入錯誤次數
     *
     * @return LastLogin
     */
    public function addErrNum()
    {
        $this->errNum++;

        return $this;
    }

    /**
     * 歸零登入錯誤次數
     *
     * @return LastLogin
     */
    public function zeroErrNum()
    {
        $this->errNum = 0;

        return $this;
    }

    /**
     * 回傳最後登入時間
     *
     * @return \DateTime
     */
    public function getAt()
    {
        return $this->at;
    }

    /**
     * 設定最後登入時間
     *
     * @param \DateTime $date 登入時間
     * @return LastLogin
     */
    public function setAt(\DateTime $date)
    {
        $this->at = $date;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $at = null;
        if (null !== $this->getAt()) {
            $at = $this->getAt()->format(\DateTime::ISO8601);
        }

        return [
            'user_id' => $this->getUserId(),
            'login_log_id' => $this->getLoginLogId(),
            'ip' => $this->getIp(),
            'err_num' => $this->getErrNum(),
            'at' => $at
        ];
    }
}
