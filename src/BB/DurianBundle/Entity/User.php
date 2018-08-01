<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use BB\DurianBundle\Currency;
use BB\DurianBundle\Entity\Cash;
use BB\DurianBundle\Entity\CashFake;
use BB\DurianBundle\Entity\Card;
use BB\DurianBundle\Entity\Credit;
use BB\DurianBundle\Entity\ShareLimit;
use BB\DurianBundle\Entity\ShareLimitNext;
use BB\DurianBundle\Entity\RemovedUser;

/**
 * 使用者
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\UserRepository")
 * @ORM\Table(
 *     name = "user",
 *     uniqueConstraints = {@ORM\UniqueConstraint(
 *         name = "uni_username_domain",
 *         columns = {"username", "domain"})
 *     },
 *     indexes = {
 *          @ORM\Index(name = "idx_user_domain_modified_at", columns = {"domain", "modified_at"}),
 *          @ORM\Index(name = "idx_user_created_at", columns = {"created_at"}),
 *          @ORM\Index(name = "idx_user_domain_role", columns = {"domain", "role"}),
 *          @ORM\Index(name = "idx_user_domain_enable_role", columns = {"domain", "enable", "role"}),
 *          @ORM\Index(name = "idx_user_domain_role_last_login", columns = {"domain", "role", "last_login"})
 *     }
 * )
 */
class User
{
    /**
     * 帳號最長字數
     */
    const MAX_USERNAME_LENGTH = 20;

    /**
     * 帳號最短字數
     */
    const MIN_USERNAME_LENGTH = 4;

    /**
     * 密碼最長字數
     */
    const MAX_PASSWORD_LENGTH = 12;

    /**
     * 密碼最短字數
     */
    const MIN_PASSWORD_LENGTH = 6;

    /**
     * 暱稱最長字數
     */
    const MAX_ALIAS_LENGTH    = 30;

    /**
     * 暱稱最短字數
     */
    const MIN_ALIAS_LENGTH    = 1;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "integer")
     */
    private $id;

    /**
     * 上層帳號
     *
     * @var User
     *
     * @ORM\ManyToOne(targetEntity = "User")
     * @ORM\JoinColumn(name = "parent_id",
     *     referencedColumnName = "id")
     */
    private $parent;

    /**
     * 帳號
     *
     * @var string
     *
     * @ORM\Column(name = "username", type = "string", length = 30)
     */
    private $username;

    /**
     * 登入站別
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
     * @var integer
     *
     * @ORM\Column(name = "role", type = "smallint", nullable = false)
     */
    private $role;

    /**
     * 是否子帳號
     *
     * @var bool
     *
     * @ORM\Column(name = "sub", type = "boolean")
     */
    private $sub;

    /**
     * 啟用
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
     * 停權
     *
     * @var boolean
     *
     * @ORM\Column(name = "bankrupt", type = "boolean")
     */
    private $bankrupt;

    /**
     * 租卡體系
     *
     * @var boolean
     *
     * @ORM\Column(name = "rent", type = "boolean")
     */
    private $rent;

    /**
     * 下層數量(包含停用)
     *
     * @var integer
     *
     * @ORM\Column(name = "size", type = "integer")
     */
    private $size;

    /**
     * 記錄習慣使用的幣別
     *
     * @var integer
     *
     * @ORM\Column(type = "smallint", options = {"unsigned" = true})
     */
    private $currency;

    /**
     * 登入錯誤次數
     *
     * $var integer
     *
     * @ORM\Column(name = "err_num", type = "integer")
     */
    private $errNum;

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
     * 登入密碼
     *
     * @var string
     *
     * @ORM\Column(name = "password", type = "string", length = 50)
     */
    private $password;

    /**
     * 密碼有效時間。過期需要更換密碼
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
     * 記錄最後一次的銀行出款資訊
     *
     * @var integer
     *
     * @ORM\Column(name = "last_bank", type = "integer", nullable = true)
     */
    private $lastBank;

    /**
     * 信用額度清單
     *
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity = "Credit", mappedBy = "user",
     * indexBy = "groupNum")
     */
    private $credits;

    /**
     * 現金
     *
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity = "Cash", mappedBy = "user")
     */
    private $cash;

    /**
     * 假現金
     *
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity = "CashFake", mappedBy = "user")
     */
    private $cashFake;

    /**
     * 租卡
     *
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity = "Card", mappedBy = "user")
     */
    private $cards;

    /**
     * 通用佔成
     *
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity = "ShareLimit", mappedBy = "user",
     * indexBy = "groupNum")
     */
    private $shareLimits;

    /**
     * 預改通用佔成
     *
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity = "ShareLimitNext", mappedBy = "user",
     * indexBy="groupNum")
     */
    private $shareLimitNexts;

    /**
     * 減少下層數量註記
     *
     * @var integer
     */
    private $subSizeFlag = 0;

    public function __construct()
    {
        $now = new \DateTime('now');

        $this->sub        = false;
        $this->block      = false;
        $this->enable     = true;
        $this->bankrupt   = false;
        $this->test       = false;
        $this->rent       = false;
        $this->hiddenTest = false;
        $this->size       = 0;
        $this->errNum     = 0;
        $this->role       = 0;

        $this->createdAt  = clone $now;
        $this->modifiedAt = clone $now;

        $expireAt = clone $now;
        $expireAt->add(new \DateInterval('P30D'));
        $this->passwordExpireAt = $expireAt;
        $this->passwordReset = false;

        $this->currency = 156; // CNY

        $this->credits         = new ArrayCollection;
        $this->cash            = new ArrayCollection;
        $this->cashFake        = new ArrayCollection;
        $this->cards           = new ArrayCollection;
        $this->shareLimits     = new ArrayCollection;
        $this->shareLimitNexts = new ArrayCollection;
    }

    /**
     * 轉字串
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getId();
    }

    /**
     * 設定id
     *
     * @param integer $id
     * @return User
     */
    public function setId($id)
    {
        if (null == $this->getId()) {
            $this->id = $id;
        }

        return $this;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 設定上層帳號
     *
     * @param  User $parent
     * @return User
     */
    public function setParent(User $parent)
    {
        if ($parent->isSub()) {
            throw new \InvalidArgumentException('Sub user can not be parent', 150010001);
        }

        if (!$parent->isEnabled()) {
            throw new \InvalidArgumentException('User disabled can not be parent', 150010002);
        }

        if ($parent->getAllParents()->contains($this)) {
            throw new \InvalidArgumentException('Inheritance loop detected', 150010003);
        }

        $this->parent = $parent;

        return $this;
    }

    /**
     * 回傳減少下層數量註記
     *
     * @return integer
     */
    public function getSubSizeFlag()
    {
        return $this->subSizeFlag;
    }

    /**
     * 回傳父層(上一層)帳號
     *
     * @return null|User
     */
    public function getParent()
    {
        if ($this->parent == null) {
            return null;
        }

        return $this->parent;
    }

    /**
     * 回傳所有父層
     *
     * @return ArrayCollection
     */
    public function getAllParents()
    {
        $result = new ArrayCollection;

        $user   = $this;

        while ($user->hasParent()) {
            $user     = $user->getParent();
            $result[] = $user;
        }

        return $result;
    }

    /**
     * 回傳所有父層的Id
     *
     * @return array
     */
    public function getAllParentsId()
    {
        $ids = array();

        foreach ($this->getAllParents() as $p) {
            $ids[] = $p->getId();
        }

        return $ids;
    }

    /**
     * 回傳是否有上層。無上層回傳false
     *
     * @return bool
     */
    public function hasParent()
    {
        if ($this->getParent()) {
            return true;
        }

        return false;
    }

    /**
     * 回傳登入帳號
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * 設定帳號
     *
     * @param  string $username
     * @return User
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * 回傳doamin
     *
     * @return integer
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * 設定domain
     *
     * @param integer $domain
     * @return User
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * 設定暱稱
     *
     * @param  string $alias
     * @return User
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;

        return $this;
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
     * 設定是否子帳號
     *
     * @param bool $bool
     * @return User
     */
    public function setSub($bool)
    {
        $this->sub = $bool;

        if (!$bool) {
            return $this;
        }

        if ($this->parent && $this->subSizeFlag == 0) {
            $this->parent->subSize();
        }

        $this->subSizeFlag = 1;

        return $this;
    }

    /**
     * 是否子帳號
     *
     * @return bool
     */
    public function isSub()
    {
        return (bool) $this->sub;
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
     * 設定測試帳號
     *
     * @param boolean $bool
     * @return User
     */
    public function setTest($bool)
    {
        $this->test = (bool) $bool;

        return $this;
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
     * 設定隱藏測試帳號
     *
     * @param boolean $bool
     * @return User
     */
    public function setHiddenTest($bool)
    {
        $this->hiddenTest = (bool) $bool;

        return $this;
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
     * 設定租卡體系
     *
     * @param boolean $bool
     * @return User
     */
    public function setRent($bool)
    {
        $this->rent = (bool) $bool;

        return $this;
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
     * 增加下層數量
     *
     * @return User
     */
    public function addSize()
    {
        $this->size++;

        return $this;
    }

    /**
     * 減少下層數量
     *
     * @return User
     */
    public function subSize()
    {
        $this->size--;

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

    /**
     * 增加登入錯誤次數
     *
     * @return User
     */
    public function addErrNum()
    {
        $this->errNum++;

        return $this;
    }

    /**
     * 歸零登入錯誤次數
     *
     * @return User
     */
    public function zeroErrNum()
    {
        $this->errNum = 0;

        return $this;
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
     * 設定習慣使用的幣別
     *
     * @param integer $currency
     * @return User
     */
    public function setCurrency($currency)
    {
        if (!$currency) {
            throw new \InvalidArgumentException('Currency can not be null', 150010041);
        }

        $this->currency = $currency;

        return $this;
    }

    /**
     * 回傳帳號建立時間
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * 設定帳號建立時間
     *
     * @param  \DateTime $date
     * @return User
     */
    public function setCreatedAt(\DateTime $date)
    {
        $this->createdAt = $date;

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
     * @return User
     */
    public function setModifiedAt(\DateTime $date)
    {
        $this->modifiedAt = $date;

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
     * 設定最後登入時間
     *
     * @param \DateTime $date
     * @return User
     */
    public function setLastLogin(\DateTime $date)
    {
        $this->lastLogin = $date;

        return $this;
    }

    /**
     * 停用使用者
     *
     * @return User
     */
    public function disable()
    {
        $this->enable = false;

        return $this;
    }

    /**
     * 啟用使用者
     *
     * @return User
     */
    public function enable()
    {
        // 有上層且為停用
        if ($this->hasParent() && !$this->parent->isEnabled()) {
            throw new \RuntimeException('Can not enable when parent is disable', 150010046);
        }

        $this->enable = true;

        return $this;
    }

    /**
     * 回傳是否啟用
     *
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->enable;
    }

    /**
     * 凍結帳號
     *
     * @return User
     */
    public function block()
    {
        $this->block = true;

        return $this;
    }

    /**
     * 解凍帳號
     *
     * @return User
     */
    public function unblock()
    {
        $this->block = false;

        return $this;
    }

    /**
     * 回傳是否凍結
     *
     * @return boolean
     */
    public function isBlock()
    {
        return $this->block;
    }

    /**
     * 設定是否停權
     *
     * @param boolean $bool
     * @return User
     */
    public function setBankrupt($bool)
    {
        $this->bankrupt = (bool) $bool;

        return $this;
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
     * 設定密碼
     *
     * @param string $value
     * @return User
     */
    public function setPassword($value)
    {
        $this->password = $value;
        $nowtime        = new \DateTime('now');
        $newexptime     = $nowtime->add(new \DateInterval("P30D"));
        $this->setPasswordExpireAt($newexptime);

        return $this;
    }

    /**
     * 回傳登入密碼
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * 設定密碼逾期時間
     *
     * @param \DateTime $date
     * @return User
     */
    public function setPasswordExpireAt(\DateTime $date)
    {
        $this->passwordExpireAt = $date;

        return $this;
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
     * 是否須重設密碼
     *
     * @return bool
     */
    public function isPasswordReset()
    {
        return (bool) $this->passwordReset;
    }

    /**
     * 設定重設密碼開關
     *
     * @param boolean $bool
     * @return User
     */
    public function setPasswordReset($bool)
    {
        $this->passwordReset = (bool) $bool;

        return $this;
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
     * 設定最後出款銀行資訊
     *
     * @param integer $bankId
     * @return User
     */
    public function setLastBank($bankId)
    {
        $this->lastBank = $bankId;

        return $this;
    }

    /**
     * 回傳使用者階層/角色
     *
     * @return integer
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * 設定使用者階層(角色)
     *
     * @param int $role
     * @return User
     */
    public function setRole($role)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * 回傳全部信用額度
     *
     * @return Credit[]
     */
    public function getCredits()
    {
        return $this->credits;
    }

    /**
     * 根據群組編號回傳Credit
     * 無此編號回傳null
     *
     * @param integer $groupNum 群組編號
     * @return Credit
     */
    public function getCredit($groupNum)
    {
        return $this->credits[$groupNum];
    }

    /**
     * 加入信用額度
     *
     * @param  Credit $credit
     * @return User
     */
    public function addCredit(Credit $credit)
    {
        if (isset($this->credits[$credit->getGroupNum()])) {
            throw new \RuntimeException('Duplicate Credit', 150010006);
        }

        $this->credits[$credit->getGroupNum()] = $credit;

        return $this;
    }

    /**
     * 回傳現金，無現金將回傳null
     *
     * @return Cash
     */
    public function getCash()
    {
        return $this->cash[0];
    }

    /**
     * 加入現金
     *
     * @param  Cash $cash
     * @return User
     */
    public function addCash(Cash $cash)
    {
        if (null !== $this->getCash()) {
            throw new \RuntimeException('Cash entity for the user already exists', 150010007);
        }

        $this->cash[] = $cash;

        return $this;
    }

    /**
     * 回傳假現金，無假現金將回傳null
     *
     * @return CashFake
     */
    public function getCashFake()
    {
        return $this->cashFake[0];
    }

    /**
     * 加入假現金
     *
     * @param CashFake $fake
     * @return User
     */
    public function addCashFake(CashFake $fake)
    {
        if (null !== $this->getCashFake()) {
            throw new \RuntimeException('CashFake for the user already exists', 150010008);
        }

        $this->cashFake[] = $fake;

        return $this;
    }

    /**
     * 回傳租卡
     *
     * @return Card
     */
    public function getCard()
    {
        return $this->cards[0];
    }

    /**
     * 加入租卡
     *
     * @param  Card $card
     * @return User
     */
    public function addCard(Card $card)
    {
        if (null !== $this->getCard()) {
            throw new \RuntimeException('Card entity for the user already exists', 150010009);
        }

        $this->cards->add($card);

        return $this;
    }

    /**
     * 新增一個ShareLimit到這個使用者
     *
     * @param ShareLimit $shareLimit
     * @return User
     */
    public function addShareLimit(ShareLimit $shareLimit)
    {
        $groupNum = $shareLimit->getGroupNum();

        if (isset($this->shareLimits[$groupNum])) {
            throw new \RuntimeException('Duplicate ShareLimit', 150010010);
        }

        $this->shareLimits[$groupNum] = $shareLimit;

        return $this;
    }

    /**
     * 回傳帳號所有的佔成限制
     *
     * @return ShareLimit[]
     */
    public function getShareLimits()
    {
        return $this->shareLimits;
    }

    /**
     * 依照群組編號回傳ShareLimit，如無則回傳null
     *
     * @param integer $groupNum 群組編號
     * @return ShareLimit
     */
    public function getShareLimit($groupNum)
    {
        return $this->shareLimits[$groupNum];
    }

    /**
     * 新增一個ShareLimitNext到這個使用者
     *
     * @param ShareLimitNext $shareLimitNext
     * @return User
     */
    public function addShareLimitNext(ShareLimitNext $shareLimitNext)
    {
        if (isset($this->shareLimitNexts[$shareLimitNext->getGroupNum()])) {
            throw new \RuntimeException('Duplicate ShareLimitNext', 150010011);
        }

        $this->shareLimitNexts[$shareLimitNext->getGroupNum()] = $shareLimitNext;

        return $this;
    }

    /**
     * 回傳帳號所有的佔成限制
     *
     * @return ShareLimitNext[]
     */
    public function getShareLimitNexts()
    {
        return $this->shareLimitNexts;
    }

    /**
     * 依照群組編號回傳ShareLimitNext，如無則回傳null
     *
     * @param integer $groupNum
     * @return ShareLimitNext
     */
    public function getShareLimitNext($groupNum)
    {
        return $this->shareLimitNexts[$groupNum];
    }

    /**
     * 回傳是否是父層
     *
     * @param User $ancestor
     * @return bool
     */
    public function isAncestor($ancestor)
    {
        return $this->getAllParents()->contains($ancestor);
    }

    /**
     * 從刪除使用者備份設定使用者資料
     *
     * @param RemovedUser $removedUser
     * @return User
     */
    public function setFromRemoved(RemovedUser $removedUser)
    {
        $this->id = $removedUser->getUserId();
        $this->parentId = $removedUser->getParentId();

        $passwordExpireAt = $removedUser->getPasswordExpireAt();
        if ($passwordExpireAt->format('Y-m-d') == '-0001-11-30') {
            $createAt = new \DateTime($removedUser->getCreatedAt()->format('Y-m-d H:i:s'));
            $passwordExpireAt = $createAt->add(new \DateInterval('P30D'));
        }

        $this->username         = $removedUser->getUsername();
        $this->alias            = $removedUser->getAlias();
        $this->password         = $removedUser->getPassword();
        $this->passwordExpireAt = $passwordExpireAt;
        $this->passwordReset    = $removedUser->isPasswordReset();

        $this->domain           = $removedUser->getDomain();
        $this->sub              = $removedUser->isSub();
        $this->block            = $removedUser->isBlock();
        $this->enable           = $removedUser->isEnable();
        $this->bankrupt         = $removedUser->isBankrupt();
        $this->test             = $removedUser->isTest();
        $this->rent             = $removedUser->isRent();
        $this->size             = $removedUser->getSize();
        $this->errNum           = $removedUser->getErrNum();
        $this->hiddenTest       = $removedUser->isHiddenTest();

        $this->createdAt        = $removedUser->getCreatedAt();
        $this->modifiedAt       = new \DateTime('now');
        $this->lastLogin        = $removedUser->getLastLogin();
        $this->lastBank         = $removedUser->getLastBank();
        $this->currency         = $removedUser->getCurrency();
        $this->role             = $removedUser->getRole();

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

        return [
            'id'                 => $this->getId(),
            'username'           => $this->getUsername(),
            'domain'             => $this->getDomain(),
            'alias'              => $this->getAlias(),
            'sub'                => $this->isSub(),
            'enable'             => $this->isEnabled(),
            'block'              => $this->isBlock(),
            'bankrupt'           => $this->isBankrupt(),
            'test'               => $this->isTest(),
            'hidden_test'        => $this->isHiddenTest(),
            'size'               => $this->getSize(),
            'err_num'            => $this->getErrNum(),
            'currency'           => $currencyOperator->getMappedCode($this->getCurrency()),
            'created_at'         => $this->getCreatedAt()->format(\DateTime::ISO8601),
            'modified_at'        => $this->getModifiedAt()->format(\DateTime::ISO8601),
            'last_login'         => $lastLogin,
            'password_expire_at' => $this->getPasswordExpireAt()->format(\DateTime::ISO8601),
            'password_reset'     => $this->isPasswordReset(),
            'last_bank'          => $this->getLastBank(),
            'role'               => $this->getRole(),
        ];
    }
}
