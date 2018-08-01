<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\User;

/**
 * 銀行帳號資料
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\BankRepository")
 * @ORM\Table(name = "bank",
 *     uniqueConstraints = {@ORM\UniqueConstraint(
 *         name = "uni_user_id_code_account",
 *         columns = {"user_id", "code", "account"})
 *     },
 *     indexes={@ORM\Index(
 *         name = "idx_bank_account",
 *         columns = {"account"})
 *     }
 * )
 */
class Bank
{
    /**
     * 常用
     */
    const IN_USE = 1;

    /**
     * 歷史
     */
    const USED = 2;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 對應的使用者
     *
     * @var User
     *
     * @ORM\ManyToOne(targetEntity = "User")
     * @ORM\JoinColumn(name = "user_id",
     *     referencedColumnName = "id",
     *     nullable = false)
     */
    private $user;

    /**
     * 銀行帳號狀態
     *
     * @var integer
     *
     * @ORM\Column(name = "status", type = "smallint")
     */
    private $status;

    /**
     * 銀行代碼
     *
     * @var integer
     *
     * @ORM\Column(name = "code", type = "smallint", nullable = true)
     */
    private $code;

    /**
     * 銀行帳號
     *
     * @var string
     *
     * @ORM\Column(name = "account", type = "string", length = 42)
     */
    private $account;

    /**
     * 銀行開戶省份
     *
     * @var string
     *
     * @ORM\Column(name = "province", type = "string", length = 100)
     */
    private $province;

    /**
     * 銀行開戶城市
     *
     * @var string
     *
     * @ORM\Column(name = "city", type = "string", length = 100)
     */
    private $city;

    /**
     * 是否為電子錢包帳戶
     *
     * @var boolean
     *
     * @ORM\Column(name = "mobile", type = "boolean")
     */
    private $mobile;

    /**
     * 支行
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 64)
     */
    private $branch;

    /**
     * 帳戶持卡人
     *
     * @var string
     *
     * @ORM\Column(name = "account_holder", type = "string", length = 100)
     */
    private $accountHolder;

    /**
     * 新增銀行帳號資訊
     *
     * @param User $user 使用者
     */
    public function __construct(User $user)
    {
        $this->user   = $user;
        $this->status = self::IN_USE;
        $this->city = '';
        $this->province = '';
        $this->mobile = false;
        $this->branch = '';
        $this->accountHolder = '';
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 回傳對應的使用者
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * 設定銀行資訊狀態
     *
     * @param integer $status
     * @return Bank
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * 取得銀行資訊狀態
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * 設定銀行帳號
     *
     * @param integer $code
     * @return Bank
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * 取得銀行代碼
     *
     * @return integer
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * 設定銀行帳號
     *
     * @param string $account
     * @return Bank
     */
    public function setAccount($account)
    {
        $this->account = $account;

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
     * 設定開戶省份
     *
     * @param string $province
     * @return Bank
     */
    public function setProvince($province)
    {
        $this->province = $province;

        return $this;
    }

    /**
     * 取得開戶省份
     *
     * @return string
     */
    public function getProvince()
    {
        return $this->province;
    }

    /**
     * 設定開戶城市
     *
     * @param string $city
     * @return Bank
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * 取得開戶城市
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * 設定是否為電子錢包帳戶
     *
     * @param boolean $mobile
     * @return Bank
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;

        return $this;
    }

    /**
     * 回傳是否為電子錢包帳戶
     *
     * @return boolean
     */
    public function isMobile()
    {
        return $this->mobile;
    }

    /**
     * 設定支行
     *
     * @param string $branch
     * @return Bank
     */
    public function setBranch($branch)
    {
        $this->branch = $branch;

        return $this;
    }

    /**
     * 取得支行
     *
     * @return string
     */
    public function getBranch()
    {
        return $this->branch;
    }

    /**
     * 設定銀行帳戶持卡人
     *
     * @param string $accountHolder
     * @return BankHolder
     */
    public function setAccountHolder($accountHolder)
    {
        $this->accountHolder = $accountHolder;

        return $this;
    }

    /**
     * 取得銀行帳戶持卡人
     *
     * @return string
     */
    public function getAccountHolder()
    {
        return $this->accountHolder;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'code' => $this->getCode(),
            'account' => $this->getAccount(),
            'status' => $this->getStatus(),
            'province' => $this->getProvince(),
            'city' => $this->getCity(),
            'mobile' => $this->isMobile(),
            'branch' => $this->getBranch(),
            'account_holder' => $this->getAccountHolder(),
        ];
    }
}
