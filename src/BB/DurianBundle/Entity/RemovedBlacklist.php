<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 記錄被移除的黑名單
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\RemovedBlacklistRepository")
 * @ORM\Table(
 *     name = "removed_blacklist",
 *     indexes = {
 *         @ORM\Index(
 *             name = "idx_removed_blacklist_modified_at",
 *             columns = {"modified_at"}),
 *         @ORM\Index(
 *             name = "idx_removed_blacklist_system_lock",
 *             columns = {"system_lock"}),
 *         @ORM\Index(
 *             name = "idx_removed_blacklist_control_terminal",
 *             columns = {"control_terminal"})
 *     }
 * )
 */
class RemovedBlacklist
{
    /**
     * 編號
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "blacklist_id", type = "integer")
     */
    private $blacklistId;

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
     * @param Blacklist $blacklist 要刪除的黑名單
     */
    public function __construct(Blacklist $blacklist)
    {
        $this->blacklistId = $blacklist->getId();
        $this->domain = $blacklist->getDomain();
        $this->wholeDomain = $blacklist->isWholeDomain();
        $this->account = $blacklist->getAccount();
        $this->identityCard = $blacklist->getIdentityCard();
        $this->nameReal = $blacklist->getNameReal();
        $this->telephone = $blacklist->getTelephone();
        $this->email = $blacklist->getEmail();
        $ip = $blacklist->getIp();

        if ($ip) {
            $ip = ip2long($blacklist->getIp());
        }

        $this->ip = $ip;
        $this->createdAt = $blacklist->getCreatedAt();
        $this->modifiedAt = new \DateTime('now');
        $this->systemLock = $blacklist->isSystemLock();
        $this->controlTerminal = $blacklist->isControlTerminal();
    }

    /**
     * 回傳id
     *
     * @return integer
     */
    public function getBlacklistId()
    {
        return $this->blacklistId;
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
     * 回傳是否為全廳
     *
     * @return boolean
     */
    public function isWholeDomain()
    {
        return $this->wholeDomain;
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
     * 取得身分證字號
     *
     * @return string
     */
    public function getIdentityCard()
    {
        return $this->identityCard;
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
     * 取得電話
     *
     * @return string
     */
    public function getTelephone()
    {
        return $this->telephone;
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
     * 設定修改時間
     *
     * @param \DateTime $modifiedAt 時間
     * @return RemovedBlacklist
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
     * 回傳是否為控端操作
     *
     * @return boolean
     */
    public function isControlTerminal()
    {
        return $this->controlTerminal;
    }

    /**
     * 回傳此物件的陣列型式
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->getBlacklistId(),
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
