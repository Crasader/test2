<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 記錄IP封鎖列表
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\IpBlacklistRepository")
 * @ORM\Table(
 *     name = "ip_blacklist",
 *     uniqueConstraints = {
 *         @ORM\UniqueConstraint(
 *             name = "uni_ip_blacklist_domain_ip_created_date_create_user_login_error",
 *             columns = {"domain", "ip", "created_date", "create_user", "login_error"})
 *     }
 * )
 *
 * @author petty 2014.10.06
 */
class IpBlacklist
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
     * 廳
     *
     * @var integer
     *
     * @ORM\Column(name = "domain", type = "integer")
     */
    private $domain;

    /**
     * IP Address
     *
     * @var integer
     *
     * @ORM\Column(type = "integer", options = {"unsigned" = true})
     */
    private $ip;

    /**
     * 因新增使用者問題產生
     *
     * @var boolean
     *
     * @ORM\Column(name = "create_user", type = "boolean")
     */
    private $createUser;

    /**
     * 因登入問題產生
     *
     * @var boolean
     *
     * @ORM\Column(name = "login_error", type = "boolean")
     */
    private $loginError;

    /**
     * 建立時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "created_at", type = "datetime")
     */
    private $createdAt;

    /**
     * 修改時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "modified_at", type = "datetime")
     */
    private $modifiedAt;

    /**
     * 刪除設定
     *
     * @var boolean
     *
     * @ORM\Column(type = "boolean")
     */
    private $removed;

    /**
     * 操作者
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 30)
     */
    private $operator;

    /**
     * 建立日期
     *
     * @var integer
     *
     * @ORM\Column(name = "created_date", type = "integer", nullable = true)
     */
    private $createdDate;

    /**
     * @param integer $domain
     * @param string  $ip IP (ex:128.0.0.1)
     */
    public function __construct($domain, $ip)
    {
        $now = new \DateTime('now');

        $this->createdAt   = $now;
        $this->modifiedAt  = $now;

        $this->domain      = $domain;
        $this->ip          = ip2long($ip);
        $this->createUser  = false;
        $this->loginError  = false;
        $this->removed     = false;
        $this->operator    = '';
        $this->createdDate = $now->format('Ymd');
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 回傳廳主ID
     *
     * @return integer
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * 回傳ip
     *
     * @return string
     */
    public function getIp()
    {
        return long2ip($this->ip);
    }

    /**
     * 設定因新增使用者問題產生
     *
     * @param boolean $createUser
     * @return IpBlacklist
     */
    public function setCreateUser($createUser)
    {
        $this->createUser = (bool) $createUser;

        return $this;
    }

    /**
     * 回傳是否因新增使用者問題產生
     *
     * @return boolean
     */
    public function isCreateUser()
    {
        return $this->createUser;
    }

    /**
     * 設定因登入問題產生
     *
     * @param boolean $loginError
     * @return IpBlacklist
     */
    public function setLoginError($loginError)
    {
        $this->loginError = (bool) $loginError;

        return $this;
    }

    /**
     * 回傳是否因登入問題產生
     *
     * @return boolean
     */
    public function isLoginError()
    {
        return $this->loginError;
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
     * 回傳修改時間
     *
     * @return \DateTime
     */
    public function getModifiedAt()
    {
        return $this->modifiedAt;
    }

    /**
     * 刪除IP封鎖列表
     *
     * @param string $operator 操作者
     * @return IpBlacklist
     */
    public function remove($operator = '')
    {
        $this->removed = true;
        $this->operator = $operator;
        $this->modifiedAt = new \DateTime('now');

        return $this;
    }

    /**
     * 回傳是否刪除
     *
     * @return boolean
     */
    public function isRemoved()
    {
        return (bool) $this->removed;
    }

    /**
     * 回傳操作者
     *
     * @return integer
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id'          => $this->getId(),
            'domain'      => $this->getDomain(),
            'ip'          => $this->getIp(),
            'create_user' => $this->isCreateUser(),
            'login_error' => $this->isLoginError(),
            'removed'     => $this->isRemoved(),
            'created_at'  => $this->getCreatedAt()->format(\DateTime::ISO8601),
            'modified_at' => $this->getModifiedAt()->format(\DateTime::ISO8601),
            'operator'    => $this->getOperator()
        ];
    }
}
