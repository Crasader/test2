<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\UserDetail;
use BB\DurianBundle\Entity\RemovedUser;

/**
 * 移除的詳細資料
 *
 * @ORM\Entity
 * @ORM\Table(name = "removed_user_detail")
 */
class RemovedUserDetail
{
    /**
     * 對應被移除的RemovedUser
     *
     * @var RemovedUser
     *
     * @ORM\Id
     * @ORM\OneToOne(targetEntity = "RemovedUser")
     * @ORM\JoinColumn(name = "user_id",
     *     referencedColumnName = "user_id",
     *     nullable = false)
     */
    private $removedUser;

    /**
     * 使用者遊戲暱稱
     *
     * @var string
     *
     * @ORM\Column(name = "nickname", type = "string", length = 36)
     */
    private $nickname;

    /**
     * 使用者真實姓名
     *
     * @var string
     *
     * @ORM\Column(name = "name_real", type = "string", length = 100)
     */
    private $nameReal;

    /**
     * 使用者中文姓名
     *
     * @var string
     *
     * @ORM\Column(name = "name_chinese", type = "string", length = 36)
     */
    private $nameChinese;

    /**
     * 使用者英文姓名
     *
     * @var string
     *
     * @ORM\Column(name = "name_english", type = "string", length = 36)
     */
    private $nameEnglish;

    /**
     * 國籍
     *
     * @var string
     *
     * @ORM\Column(name = "country", type = "string", length = 30)
     */
    private $country;

    /**
     * 護照號碼
     *
     * @var string
     *
     * @ORM\Column(name = "passport", type = "string", length = 18)
     */
    private $passport;

    /**
     * 身分證字號
     *
     * @var string
     *
     * @ORM\Column(name = "identity_card", type = "string", length = 18)
     */
    private $identityCard;

    /**
     * 駕照號碼
     *
     * @var string
     *
     * @ORM\Column(name = "driver_license", type = "string", length = 18)
     */
    private $driverLicense;

    /**
     * 保險證字號
     *
     * @var string
     *
     * @ORM\Column(name = "insurance_card", type = "string", length = 18)
     */
    private $insuranceCard;

    /**
     * 健保卡號碼
     *
     * @var string
     *
     * @ORM\Column(name = "health_card", type = "string", length = 18)
     */
    private $healthCard;

    /**
     * 線上取款密碼
     *
     * @var string
     *
     * @ORM\Column(name = "password", type = "string", length = 10)
     */
    private $password;

    /**
     * 生日
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "birthday", type = "datetime", nullable = true)
     */
    private $birthday;

    /**
     * 聯絡電話
     *
     * @var string
     *
     * @ORM\Column(name = "telephone", type = "string", length = 20)
     *
     */
    private $telephone;

    /**
     * QQ帳號
     *
     * @var string
     *
     * @ORM\Column(name = "qq_num", type = "string", length = 16)
     */
    private $qqNum;

    /**
     * 備註
     *
     * @var String
     * @ORM\Column(name = "note", type = "string", length = 150)
     */
    private $note;

    /**
     * 微信帳號
     *
     * @var string
     *
     * @ORM\Column(name = "wechat", type = "string", length = 32, options = {"default" = ""})
     */
    private $wechat;

    /**
     * @param RemovedUser $removedUser 對應被刪除的使用者
     * @param UserDetail  $userDetail  要刪除的詳細資料
     */
    public function __construct(RemovedUser $removedUser, UserDetail $userDetail)
    {
        if ($removedUser->getUserId() != $userDetail->getUser()->getId()) {
            throw new \RuntimeException('UserDetail not belong to this user', 150010134);
        }

        $this->removedUser   = $removedUser;
        $this->nickname      = $userDetail->getNickname();
        $this->nameReal      = $userDetail->getNameReal();
        $this->nameChinese   = $userDetail->getNameChinese();
        $this->nameEnglish   = $userDetail->getNameEnglish();
        $this->country       = $userDetail->getCountry();
        $this->passport      = $userDetail->getPassport();
        $this->identityCard  = $userDetail->getIdentityCard();
        $this->driverLicense = $userDetail->getDriverLicense();
        $this->insuranceCard = $userDetail->getInsuranceCard();
        $this->healthCard    = $userDetail->getHealthCard();
        $this->birthday      = $userDetail->getBirthday();
        $this->password      = $userDetail->getPassword();
        $this->telephone     = $userDetail->getTelephone();
        $this->qqNum         = $userDetail->getQQNum();
        $this->note          = $userDetail->getNote();
        $this->wechat        = $userDetail->getWechat();
    }

    /**
     * 回傳對應的刪除使用者
     *
     * @return RemovedUser
     */
    public function getRemovedUser()
    {
        return $this->removedUser;
    }

    /**
     * 取得遊戲暱稱
     *
     * @return string
     */
    public function getNickname()
    {
        return $this->nickname;
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
     * 取得中文姓名
     *
     * @return string
     */
    public function getNameChinese()
    {
        return $this->nameChinese;
    }

    /**
     * 取得英文姓名
     *
     * @return string
     */
    public function getNameEnglish()
    {
        return $this->nameEnglish;
    }

    /**
     * 取得國籍
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * 取得護照號碼
     *
     * @return string
     */
    public function getPassport()
    {
        return $this->passport;
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
     * 取得駕照號碼
     *
     * @return string
     */
    public function getDriverLicense()
    {
        return $this->driverLicense;
    }

    /**
     * 取得保險證字號
     *
     * @return string
     */
    public function getInsuranceCard()
    {
        return $this->insuranceCard;
    }

    /**
     * 取得健保卡號碼
     *
     * @return string
     */
    public function getHealthCard()
    {
        return $this->healthCard;
    }

    /**
     * 取得線上取款密碼
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * 取得生日
     *
     * @return \DateTime
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * 取得電話號碼
     *
     * @return string
     */
    public function getTelephone()
    {
        return $this->telephone;
    }

    /**
     * 取得QQ帳號
     *
     * @return string
     */
    public function getQQNum()
    {
        return $this->qqNum;
    }

    /**
     * 取得備註內容
     *
     * @return String
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * 取得微信帳號
     *
     * @return string
     */
    public function getWechat()
    {
        return $this->wechat;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $birthday = null;

        if (null !== $this->getBirthday()) {
            $birthday = $this->getBirthday()->format('Y-m-d');
        }

        return array(
            'user_id'        => $this->getRemovedUser()->getUserId(),
            'nickname'       => $this->getNickname(),
            'name_real'      => $this->getNameReal(),
            'name_chinese'   => $this->getNameChinese(),
            'name_english'   => $this->getNameEnglish(),
            'country'        => $this->getCountry(),
            'passport'       => $this->getPassport(),
            'identity_card'  => $this->getIdentityCard(),
            'driver_license' => $this->getDriverLicense(),
            'insurance_card' => $this->getInsuranceCard(),
            'health_card'    => $this->getHealthCard(),
            'password'       => $this->getPassword(),
            'birthday'       => $birthday,
            'telephone'      => $this->getTelephone(),
            'qq_num'         => $this->getQQNum(),
            'note'           => $this->getNote(),
            'wechat'         => $this->getWechat(),
        );
    }
}
