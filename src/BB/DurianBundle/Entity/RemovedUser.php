<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use BB\DurianBundle\Currency;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\RemovedCash;
use BB\DurianBundle\Entity\RemovedCashFake;
use BB\DurianBundle\Entity\RemovedCredit;
use BB\DurianBundle\Entity\RemovedCard;

/**
 * 被移除的使用者
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\RemovedUserRepository")
 * @ORM\Table(
 *     name = "removed_user",
 *     indexes = {
 *          @ORM\Index(name = "idx_removed_user_modified_at", columns = {"modified_at"}),
 *     }
 * )
 */
class RemovedUser
{
    /**
     * 使用者ID
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "user_id", type = "integer")
     */
    private $userId;

    /**
     * 父層id
     *
     * @var integer
     *
     * @ORM\Column(name = "parent_id", type = "integer", nullable = true)
     */
    private $parentId;

    /**
     * 帳號
     *
     * @var string
     *
     * @ORM\Column(name = "username", type = "string", length = 30)
     */
    private $username;

    /**
     * 站別
     *
     * @var integer
     *
     * @ORM\Column(name = "domain", type = "integer")
     */
    private $domain;

    /**
     * 暱稱
     *
     * @var string
     *
     * @ORM\Column(name = "alias", type = "string", length = 50)
     */
    private $alias;

    /**
     * 使用者的階層(角色)
     *
     * @var string
     *
     * @ORM\Column(name = "role", type = "smallint", nullable = false)
     */
    private $role;

    /**
     * 子帳號
     *
     * @var bool
     *
     * @ORM\Column(name = "sub", type = "boolean")
     */
    private $sub;

    /**
     * 啟用狀態
     *
     * @var boolean
     *
     * @ORM\Column(name = "enable", type = "boolean")
     */
    private $enable;

    /**
     * 凍結
     *
     * @var boolean
     *
     * @ORM\Column(name = "block", type = "boolean")
     */
    private $block;

    /**
     * 停權
     *
     * @var boolean
     *
     * @ORM\Column(name = "bankrupt", type = "boolean")
     */
    private $bankrupt;

    /**
     * 測試
     *
     * @var boolean
     *
     * @ORM\Column(name = "test", type = "boolean")
     */
    private $test;

    /**
     * 是否為隱藏測試帳號
     *
     * @var boolean
     *
     * @ORM\Column(name = "hidden_test", type = "boolean")
     */
    private $hiddenTest;

    /**
     * 租卡體系
     *
     * @var boolean
     *
     * @ORM\Column(name = "rent", type = "boolean")
     */
    private $rent;

    /**
     * 下層數量
     *
     * @var integer
     *
     * @ORM\Column(name = "size", type = "integer")
     */
    private $size;

    /**
     * 登入錯誤次數
     *
     * $var integer
     *
     * @ORM\Column(name = "err_num", type = "integer")
     */
    private $errNum;

    /**
     * 記錄習慣使用的幣別
     *
     * @var integer
     *
     * @ORM\Column(name = "currency", type = "smallint", options = {"unsigned" = true})
     */
    private $currency;

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
     * 最後登入時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "last_login", type = "datetime", nullable = true)
     */
    private $lastLogin;

    /**
     * 記錄最後一次的銀行出款資訊
     *
     * @var integer
     *
     * @ORM\Column(name = "last_bank", type = "integer", nullable = true)
     */
    private $lastBank;

    /**
     * 密碼
     *
     * @var string
     *
     * @ORM\Column(name = "password", type = "string", length = 50)
     */
    private $password;

    /**
     * 密碼有效時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "password_expire_at", type = "datetime")
     */
    private $passwordExpireAt;

    /**
     * 重設密碼設定
     *
     * @var boolean
     *
     * @ORM\Column(name = "password_reset", type = "boolean")
     */
    private $passwordReset;

    /**
     * 被移除的現金
     *
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity = "RemovedCash", mappedBy = "removedUser")
     */
    private $removedCash;

    /**
     * 被移除的假現金
     *
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity = "RemovedCashFake", mappedBy = "removedUser")
     */
    private $removedCashFake;

    /**
     * 被移除的信用額度清單
     *
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity = "RemovedCredit", mappedBy = "removedUser",
     * indexBy = "groupNum")
     */
    private $removedCredits;

    /**
     * 被移除的租卡
     *
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity = "RemovedCard", mappedBy = "removedUser")
     */
    private $removedCards;

    /**
     * @param User $user    要刪除的使用者
     */
    public function __construct(User $user)
    {
        if ($user->hasParent()) {
            $this->parentId = $user->getParent()->getId();
        }

        // 因有資料密碼逾期時間欄位為 0000-00-00 00:00:00 的資料, format 之後會變成 -0001-11-30
        // 在 new RemovedUser 時會造成無法刪除帳號, 此情況則直接回傳原預設值
        $passwordExpireAt = $user->getPasswordExpireAt();
        if ($passwordExpireAt->format('Y-m-d') == '-0001-11-30') {
            $createAt = new \DateTime($user->getCreatedAt()->format('Y-m-d H:i:s'));
            $passwordExpireAt = $createAt->add(new \DateInterval('P30D'));
        }

        $this->userId           = $user->getId();
        $this->username         = $user->getUsername();
        $this->alias            = $user->getAlias();
        $this->password         = $user->getPassword();
        $this->passwordExpireAt = $passwordExpireAt;
        $this->passwordReset    = $user->isPasswordReset();

        $this->domain           = $user->getDomain();
        $this->sub              = $user->isSub();
        $this->block            = $user->isBlock();
        $this->enable           = $user->isEnabled();
        $this->bankrupt         = $user->isBankrupt();
        $this->test             = $user->isTest();
        $this->rent             = $user->isRent();
        $this->size             = $user->getSize();
        $this->errNum           = $user->getErrNum();
        $this->hiddenTest       = $user->isHiddenTest();

        $this->createdAt        = $user->getCreatedAt();
        $this->modifiedAt       = new \DateTime('now');
        $this->lastLogin        = $user->getLastLogin();
        $this->lastBank         = $user->getLastBank();
        $this->currency         = $user->getCurrency();
        $this->role             = $user->getRole();

        $this->removedCash      = new ArrayCollection;
        $this->removedCashFake  = new ArrayCollection;
        $this->removedCredits   = new ArrayCollection;
        $this->removedCards     = new ArrayCollection;
    }

    /**
     * 回傳使用者ID
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * 回傳父層id
     *
     * @return integer
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * 回傳帳號
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * 回傳domain
     *
     * @return integer
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * 回傳暱稱
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * 是否為子帳號
     *
     * @return bool
     */
    public function isSub()
    {
        return (bool) $this->sub;
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
     * @param \DateTime $createAt
     * @return RemovedUser
     */
    public function setCreatedAt($createAt)
    {
        $this->createdAt = $createAt;

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
     * @param \DateTime
     * @return RemovedUser
     */
    public function setModifiedAt($modifiedAt)
    {
        $this->modifiedAt = $modifiedAt;

        return $this;
    }

    /**
     * 回傳最後登入時間
     *
     * @return \DateTime
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    /**
     * 回傳最後出款銀行資訊
     *
     * @return integer
     */
    public function getLastBank()
    {
        return $this->lastBank;
    }

    /**
     * 回傳習慣使用的幣別
     *
     * @return integer
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * 設定使用者階層(角色)
     *
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * 是否啟用
     *
     * @return boolean
     */
    public function isEnable()
    {
        return (bool) $this->enable;
    }

    /**
     * 是否凍結
     *
     * @return boolean
     */
    public function isBlock()
    {
        return (bool) $this->block;
    }

    /**
     * 是否停權
     *
     * @return boolean
     */
    public function isBankrupt()
    {
        return (bool) $this->bankrupt;
    }

    /**
     * 是否測試帳號
     *
     * @return bool
     */
    public function isTest()
    {
        return (bool) $this->test;
    }

    /**
     * 是否為隱藏測試帳號
     *
     * @return bool
     */
    public function isHiddenTest()
    {
        return (bool) $this->hiddenTest;
    }

    /**
     * 是否為租卡體系
     *
     * @return bool
     */
    public function isRent()
    {
        return (bool) $this->rent;
    }

    /**
     * 回傳下層數量
     *
     * @return integer
     */
    public function getSize()
    {
        return $this->size;
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
     * 回傳密碼
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * 回傳密碼逾期時間
     *
     * @return \DateTime
     */
    public function getPasswordExpireAt()
    {
        return $this->passwordExpireAt;
    }

    /**
     * 回傳重設密碼開關
     *
     * @return bool
     */
    public function isPasswordReset()
    {
        return (bool) $this->passwordReset;
    }

    /**
     * 添加被移除的現金
     *
     * @param RemovedCash $removedCash
     * @return RemovedUser
     */
    public function addRemovedCash(RemovedCash $removedCash)
    {
        $this->removedCash[] = $removedCash;

        return $this;
    }

    /**
     * 回傳被移除的現金
     *
     * @return RemovedCash
     */
    public function getRemovedCash()
    {
        return $this->removedCash[0];
    }

    /**
     * 添加被移除的假現金
     *
     * @param RemovedCashFake $removedCashFake
     * @return RemovedUser
     */
    public function addRemovedCashFake(RemovedCashFake $removedCashFake)
    {
        $this->removedCashFake[] = $removedCashFake;

        return $this;
    }

    /**
     * 回傳被移除的假現金
     *
     * @return RemovedCashFake
     */
    public function getRemovedCashFake()
    {
        return $this->removedCashFake[0];
    }

    /**
     * 加入被移除的信用額度
     *
     * @param  RemovedCredit $removedCredit
     * @return RemovedUser
     */
    public function addRemovedCredit(RemovedCredit $removedCredit)
    {
        $this->removedCredits[$removedCredit->getGroupNum()] = $removedCredit;

        return $this;
    }

    /**
     * 回傳全部被移除的信用額度
     *
     * @return RemovedCredit[]
     */
    public function getRemovedCredits()
    {
        return $this->removedCredits;
    }

    /**
     * 回傳被移除的租卡
     *
     * @return Card
     */
    public function getRemovedCard()
    {
        return $this->removedCards[0];
    }

    /**
     * 加入被移除的租卡
     *
     * @param  RemovedCard $removedCard
     * @return RemovedUser
     */
    public function addRemovedCard(RemovedCard $removedCard)
    {
        $this->removedCards->add($removedCard);

        return $this;
    }

    /**
     * 設定使用者階層(角色)
     *
     * @param int $role
     * @return RemovedUser
     */
    public function setRole($role)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $lastLogin = null;
        if (null !== $this->getLastLogin()) {
            $lastLogin = $this->getLastLogin()->format(\DateTime::ISO8601);
        }

        $currencyOperator = new Currency();

        return array(
            'userId'             => $this->getUserId(),
            'username'           => $this->getUsername(),
            'domain'             => $this->getDomain(),
            'alias'              => $this->getAlias(),
            'sub'                => $this->isSub(),
            'enable'             => $this->isEnable(),
            'block'              => $this->isBlock(),
            'bankrupt'           => $this->isBankrupt(),
            'password_expire_at' => $this->getPasswordExpireAt()->format(\DateTime::ISO8601),
            'password_reset'     => $this->isPasswordReset(),
            'test'               => $this->isTest(),
            'hidden_test'        => $this->isHiddenTest(),
            'size'               => $this->getSize(),
            'err_num'            => $this->getErrNum(),
            'currency'           => $currencyOperator->getMappedCode($this->getCurrency()),
            'created_at'         => $this->getCreatedAt()->format(\DateTime::ISO8601),
            'modified_at'        => $this->getModifiedAt()->format(\DateTime::ISO8601),
            'last_login'         => $lastLogin,
            'last_bank'          => $this->getLastBank(),
            'role'               => $this->getRole(),
        );
    }
}
