<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\User;

/**
 * 紀錄廳的相關設定
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\DomainConfigRepository")
 * @ORM\Table(
 *      name = "domain_config",
 *      uniqueConstraints = {@ORM\UniqueConstraint(name = "unique_code", columns = {"login_code"})}
 * )
 *
 * @author petty 2014.10.06
 */
class DomainConfig
{
    /**
     * 同IP一天最多可新增使用者次數
     */
    const MAX_CREATE_USER_TIMES = 10;

    /**
     * 30天內被封鎖過新增使用者的IP,一天最多可新增使用者的次數
     */
    const LIMITED_CREATED_USER_TIMES = 3;

    /**
     * 封鎖IP新增使用者天數
     */
    const CREATE_USER_LIMIT_DAYS = 1;

    /**
     * 同IP最多可試密碼錯誤次數
     */
    const MAX_ERROR_PWD_TIMES = 30;

    /**
     * 封鎖IP登入天數
     */
    const LOGIN_LIMIT_DAYS = 1;

    /**
     * 同廳下的會員最多可使用的測試帳號數量(排除隱藏測試帳號)
     */
    const MAX_TOTAL_TEST = 100;

    /**
     * 長度限制下限
     */
    const LOGIN_CODE_LENGTH_MIN = 2;

    /**
     * 長度限制上限
     */
    const LOGIN_CODE_LENGTH_MAX = 3;

    /**
     * 廳名最長字數
     */
    const MAX_NAME_LENGTH = 30;

    /**
     * 廳名最短字數
     */
    const MIN_NAME_LENGTH = 1;

    /**
     * 廳(same data type with User::domain)
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type = "integer")
     */
    private $domain;

    /**
     * 廳名
     *
     * @var string
     *
     * @ORM\Column(name = "name", type = "string", length = 50)
     */
    private $name;

    /**
     * 啟用
     *
     * @var boolean
     *
     * @ORM\Column(name = "enable", type = "boolean")
     */
    private $enable;

    /**
     * 刪除
     *
     * @var boolean
     *
     * @ORM\Column(name = "removed", type = "boolean", options = {"default" = false})
     */
    private $removed;

    /**
     * 阻擋新增使用者
     *
     * @var boolean
     *
     * @ORM\Column(name = "block_create_user", type = "boolean")
     */
    private $blockCreateUser;

    /**
     * 阻擋登入
     *
     * @var boolean
     *
     * @ORM\Column(name = "block_login", type = "boolean")
     */
    private $blockLogin;

    /**
     * 阻擋測試帳號
     *
     * @var boolean
     *
     * @ORM\Column(name = "block_test_user", type = "boolean")
     */
    private $blockTestUser;

    /**
     * 代碼
     *
     * @var string
     *
     * @ORM\Column(name = "login_code", type = "string", length=50)
     */
    private $loginCode;

    /**
     * 驗證OTP
     *
     * @var boolean
     *
     * @ORM\Column(name = "verify_otp", type = "boolean", options = {"default" = false})
     */
    private $verifyOtp;

    /**
     * 控端免轉錢包開關(當此開關開啟時，廳主才可設定錢包狀態)
     *
     * @var boolean
     *
     * @ORM\Column(name = "free_transfer_wallet", type = "boolean", options = {"default" = false})
     */
    private $freeTransferWallet;


    /**
     * 錢包狀態(0:多錢包、1:免轉錢包、2:會員自選)
     *
     * @var integer
     *
     * @ORM\Column(name = "wallet_status", type = "smallint")
     */
    private $walletStatus;

    /**
     * @param User|integer $domain 廳物件|廳編號
     * @param string $name 廳名
     * @param string $loginCode
     */
    public function __construct($domain, $name, $loginCode)
    {
        $this->domain = $domain;

        if ($domain instanceof User) {
            $this->domain = $domain->getId();
        }

        $this->name               = $name;
        $this->enable             = true;
        $this->removed            = false;
        $this->blockCreateUser    = false;
        $this->blockLogin         = false;
        $this->blockTestUser      = false;
        $this->loginCode          = $loginCode;
        $this->verifyOtp          = false;
        $this->freeTransferWallet = false;
        $this->walletStatus       = 0;
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
     * 是否阻擋ip新增使用者
     *
     * @return bool
     */
    public function isBlockCreateUser()
    {
        return (bool) $this->blockCreateUser;
    }

    /**
     * 回傳廳名
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 設定廳名
     *
     * @param  string $name 廳名
     * @return DomainConfig
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * 刪除廳主
     *
     * @return DomainConfig
     */
    public function remove()
    {
        $this->removed = true;

        return $this;
    }

    /**
     * 回傳是否刪除
     *
     * @return boolean
     */
    public function isRemoved()
    {
        return $this->removed;
    }

    /**
     * 停用廳主
     *
     * @return DomainConfig
     */
    public function disable()
    {
        $this->enable = false;

        return $this;
    }

    /**
     * 啟用廳主
     *
     * @return DomainConfig
     */
    public function enable()
    {
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
     * 設定阻擋新增使用者
     *
     * @param boolean $blockCreateUser
     * @return DomainConfig
     */
    public function setBlockCreateUser($blockCreateUser)
    {
        $this->blockCreateUser = (bool) $blockCreateUser;

        return $this;
    }

    /**
     * 是否阻擋ip登入
     *
     * @return bool
     */
    public function isBlockLogin()
    {
        return $this->blockLogin;
    }

    /**
     * 設定阻擋登入
     *
     * @param boolean $blockLogin
     * @return DomainConfig
     */
    public function setBlockLogin($blockLogin)
    {
        $this->blockLogin = (bool) $blockLogin;

        return $this;
    }

    /**
     * 是否阻擋測試帳號
     *
     * @return boolean
     */
    public function isBlockTestUser()
    {
        return $this->blockTestUser;
    }

    /**
     * 設定阻擋測試帳號
     *
     * @param boolean $blockTestUser
     * @return DomainConfig
     */
    public function setBlockTestUser($blockTestUser)
    {
        $this->blockTestUser = (bool) $blockTestUser;

        return $this;
    }

    /**
     * 取得廳的代碼
     *
     * @return string
     */
    public function getLoginCode()
    {
        return $this->loginCode;
    }

    /**
     * 設定廳的代碼
     *
     * @param string $loginCode
     * @return DomainConfig
     */
    public function setLoginCode($loginCode)
    {
        $this->loginCode = $loginCode;

        return $this;
    }

    /**
     * 是否驗證OTP
     *
     * @return bool
     */
    public function isVerifyOtp()
    {
        return $this->verifyOtp;
    }

    /**
     * 設定驗證OTP
     *
     * @param boolean $verifyOtp
     * @return DomainConfig
     */
    public function setVerifyOtp($verifyOtp)
    {
        $this->verifyOtp = (bool) $verifyOtp;

        return $this;
    }

    /**
     * 控端啟用廳免轉錢包
     *
     * @return DomainConfig
     */
    public function enableFreeTransferWallet()
    {
        $this->freeTransferWallet = true;

        return $this;
    }

    /**
     * 控端停用廳免轉錢包
     *
     * @return DomainConfig
     */
    public function disableFreeTransferWallet()
    {
        $this->freeTransferWallet = false;

        return $this;
    }

    /**
     * 回傳是否啟用免轉錢包狀態
     *
     * @return boolean
     */
    public function isFreeTransferWallet()
    {
        return $this->freeTransferWallet;
    }

    /**
     * 設定廳開放錢包狀態
     *
     * @param integer $walletStatus
     * @return DomainConfig
     */
    public function setWalletStatus($walletStatus)
    {
        $this->walletStatus = $walletStatus;

        return $this;
    }

    /**
     * 回傳錢包狀態
     *
     * @return integer
     */
    public function getWalletStatus()
    {
        return $this->walletStatus;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'domain'               => $this->getDomain(),
            'name'                 => $this->getName(),
            'enable'               => $this->isEnabled(),
            'removed'              => $this->isRemoved(),
            'block_create_user'    => $this->isBlockCreateUser(),
            'block_login'          => $this->isBlockLogin(),
            'block_test_user'      => $this->isBlockTestUser(),
            'login_code'           => $this->getLoginCode(),
            'verify_otp'           => $this->isVerifyOtp(),
            'free_transfer_wallet' => $this->isFreeTransferWallet(),
            'wallet_status'        => $this->getWalletStatus()
        ];
    }
}
