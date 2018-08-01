<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 記錄使用者資料黑名單
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\BlacklistRepository")
 * @ORM\Table(
 *     name = "blacklist",
 *     indexes = {
 *         @ORM\Index(
 *             name = "idx_blacklist_domain",
 *             columns = {"domain"}),
 *         @ORM\Index(
 *             name = "idx_blacklist_account",
 *             columns = {"account"}),
 *         @ORM\Index(
 *             name = "idx_blacklist_identity_card",
 *             columns = {"identity_card"}),
 *         @ORM\Index(
 *             name = "idx_blacklist_name_real",
 *             columns = {"name_real"}),
 *         @ORM\Index(
 *             name = "idx_blacklist_telephone",
 *             columns = {"telephone"}),
 *         @ORM\Index(
 *             name = "idx_blacklist_email",
 *             columns = {"email"}),
 *         @ORM\Index(
 *             name = "idx_blacklist_ip",
 *             columns = {"ip"}),
 *         @ORM\Index(
 *             name = "idx_blacklist_system_lock",
 *             columns = {"system_lock"}),
 *         @ORM\Index(
 *             name = "idx_blacklist_control_terminal",
 *             columns = {"control_terminal"})
 *     },
 *     uniqueConstraints = {
 *         @ORM\UniqueConstraint(
 *             name = "uni_blacklist_domain_ip",
 *             columns = {"domain", "ip"})
 *     }
 * )
 *
 * @author Ruby 2015.03.20
 */
class Blacklist
{
    /**
     * 編號
     *
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
     * @ORM\Column(name = "domain", type = "integer", options = {"default" = 0})
     */
    private $domain;

    /**
     * 註記是否為全部廳
     *
     * @var boolean
     *
     * @ORM\Column(name = "whole_domain", type = "boolean", options = {"default" = true})
     */
    private $wholeDomain;

    /**
     * 銀行帳號
     *
     * @var string
     *
     * @ORM\Column(name = "account", type = "string", length = 42, nullable = true)
     */
    private $account;

    /**
     * 身分證字號
     *
     * @var string
     *
     * @ORM\Column(name = "identity_card", type = "string", length = 18, nullable = true)
     */
    private $identityCard;

    /**
     * 使用者真實姓名
     *
     * @var string
     *
     * @ORM\Column(name = "name_real", type = "string", length = 100, nullable = true)
     */
    private $nameReal;

    /**
     * 電話
     *
     * @var string
     *
     * @ORM\Column(name = "telephone", type = "string", length = 20, nullable = true)
     */
    private $telephone;

    /**
     * 信箱
     *
     * @var string
     *
     * @ORM\Column(name = "email", type = "string", length = 50, nullable = true)
     */
    private $email;

    /**
     * IP Address
     *
     * @var integer
     *
     * @ORM\Column(name = "ip", type = "integer", options = {"unsigned" = true}, nullable = true)
     */
    private $ip;

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
     * 標記是否為系統封鎖
     *
     * @var boolean
     *
     * @ORM\Column(name = "system_lock", type = "boolean", options = {"default" = false})
     */
    private $systemLock;

    /**
     * 標記是否為控端操作
     *
     * @var boolean
     *
     * @ORM\Column(name = "control_terminal", type = "boolean", options = {"default" = false})
     */
    private $controlTerminal;

    /**
     * 建構子
     *
     * @param integer $domain 廳
     */
    public function __construct($domain = 0)
    {
        $now = new \DateTime('now');

        $this->domain = $domain;
        $this->createdAt = $now;
        $this->modifiedAt = $now;
        $this->wholeDomain = true;
        $this->systemLock = false;
        $this->controlTerminal = false;

        if ($domain) {
            $this->wholeDomain = false;
        }
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
     * 回傳廳
     *
     * @return integer
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * 設定廳
     *
     * @param integer $domain 廳
     * @return Blacklist
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * 回傳是否為全廳
     *
     * @return boolean
     */
    public function isWholeDomain()
    {
        return $this->wholeDomain;
    }

    /**
     * 全廳設定
     *
     * @param boolean $wholeDomain 全廳設定
     * @return Blacklist
     */
    public function setWholeDomain($wholeDomain)
    {
        $this->wholeDomain = $wholeDomain;

        return $this;
    }

    /**
     * 取得銀行帳號
     *
     * @return string
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * 設定銀行帳號
     *
     * @param string $account 銀行帳號
     * @return Blacklist
     */
    public function setAccount($account)
    {
        $this->account = $account;

        return $this;
    }

    /**
     * 取得身分證字號
     *
     * @return string
     */
    public function getIdentityCard()
    {
        return $this->identityCard;
    }

    /**
     * 設定身分證字號
     *
     * @param string $identityCard 身分證字號
     * @return Blacklist
     */
    public function setIdentityCard($identityCard)
    {
        $this->identityCard = $identityCard;

        return $this;
    }

    /**
     * 取得真實姓名
     *
     * @return string
     */
    public function getNameReal()
    {
        return $this->nameReal;
    }

    /**
     * 設定真實姓名
     *
     * @param string $nameReal 真實姓名
     * @return Blacklist
     */
    public function setNameReal($nameReal)
    {
        $this->nameReal = $nameReal;

        return $this;
    }

    /**
     * 取得電話
     *
     * @return string
     */
    public function getTelephone()
    {
        return $this->telephone;
    }

    /**
     * 設定電話
     *
     * @param string $telephone 電話
     * @return Blacklist
     */
    public function setTelephone($telephone)
    {
        $this->telephone = $telephone;

        return $this;
    }

    /**
     * 取得信箱
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * 設定信箱
     *
     * @param string $email 信箱
     * @return Blacklist
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * 回傳ip
     *
     * @return string
     */
    public function getIp()
    {
        if (!$this->ip) {
            return null;
        }

        return long2ip($this->ip);
    }

    /**
     * 設定ip
     *
     * @param string $ip IP(ex:128.0.0.1)
     * @return Blacklist
     */
    public function setIp($ip)
    {
        $this->ip = ip2long($ip);

        return $this;
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
     * 設定建立時間
     *
     * @param \DateTime $createdAt 時間
     * @return Blacklist
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

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
     * @param \DateTime $modifiedAt 時間
     * @return Blacklist
     */
    public function setModifiedAt(\DateTime $modifiedAt)
    {
        $this->modifiedAt = $modifiedAt;

        return $this;
    }

    /**
     * 回傳是否為系統封鎖
     *
     * @return boolean
     */
    public function isSystemLock()
    {
        return $this->systemLock;
    }

    /**
     * 系統封鎖設定
     *
     * @param boolean $lock 系統封鎖
     * @return Blacklist
     */
    public function setSystemLock($lock)
    {
        $this->systemLock = $lock;

        return $this;
    }

    /**
     * 回傳是否為控端操作
     *
     * @return boolean
     */
    public function isControlTerminal()
    {
        return $this->controlTerminal;
    }

    /**
     * 控端操作設定
     *
     * @param boolean $control 控端操作
     * @return Blacklist
     */
    public function setControlTerminal($control)
    {
        $this->controlTerminal = $control;

        return $this;
    }

    /**
     * 回傳此物件的陣列型式
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'domain' => $this->getDomain(),
            'whole_domain' => $this->isWholeDomain(),
            'account' => $this->getAccount(),
            'identity_card' => $this->getIdentityCard(),
            'name_real' => $this->getNameReal(),
            'telephone' => $this->getTelephone(),
            'email' => $this->getEmail(),
            'ip' => $this->getIp(),
            'created_at' => $this->getCreatedAt()->format(\DateTime::ISO8601),
            'modified_at' => $this->getModifiedAt()->format(\DateTime::ISO8601),
            'system_lock' => $this->isSystemLock(),
            'control_terminal' => $this->isControlTerminal()
        ];
    }
}
