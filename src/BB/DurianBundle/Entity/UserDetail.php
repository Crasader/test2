<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\RemovedUserDetail;

/**
 * 使用者詳細資料
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\UserDetailRepository")
 * @ORM\Table(name = "user_detail",
 *     indexes = {
 *         @ORM\Index(
 *             name = "idx_user_detail_name_real_2",
 *             columns = {"name_real"}),
 *         @ORM\Index(
 *             name = "idx_user_detail_name_chinese_2",
 *             columns = {"name_chinese"}),
 *         @ORM\Index(
 *             name = "idx_user_detail_name_english_2",
 *             columns = {"name_english"}),
 *         @ORM\Index(
 *             name = "idx_user_detail_passport_2",
 *             columns = {"passport"}),
 *         @ORM\Index(
 *             name = "idx_user_detail_identity_card_2",
 *             columns = {"identity_card"}),
 *         @ORM\Index(
 *             name = "idx_user_detail_qq_num_2",
 *             columns = {"qq_num"}),
 *         @ORM\Index(
 *             name = "idx_user_detail_telephone_2",
 *             columns = {"telephone"}),
 *         @ORM\Index(
 *             name = "idx_user_detail_wechat",
 *             columns = {"wechat"})
 *     }
 * )
 */
class UserDetail
{
    /**
     * 對應的使用者
     *
     * @var User
     *
     * @ORM\Id
     * @ORM\OneToOne(targetEntity = "User")
     * @ORM\JoinColumn(name = "user_id",
     *     referencedColumnName = "id", nullable = false)
     */
    private $user;

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
     * 線上取款密碼
     *
     * @var string
     *
     * @ORM\Column(name = "password", type = "string", length = 10)
     */
    private $password;

    /**
     * 微信帳號
     *
     * @var string
     *
     * @ORM\Column(name = "wechat", type = "string", length = 32, options = {"default" = ""})
     */
    private $wechat;

    /**
     * @param User $user 對應的使用者
     */
    public function __construct(User $user)
    {
        $this->user = $user;

        $this->nickname = '';
        $this->nameReal = '';
        $this->nameChinese = '';
        $this->nameEnglish = '';
        $this->country = '';
        $this->passport = '';
        $this->identityCard = '';
        $this->driverLicense = '';
        $this->insuranceCard = '';
        $this->healthCard = '';
        $this->birthday = null;
        $this->telephone = '';
        $this->qqNum = '';
        $this->note = '';
        $this->password = '';
        $this->wechat = '';
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
     * 設定遊戲暱稱
     *
     * @param string $nickname
     * @return UserDetail
     */
    public function setNickname($nickname)
    {
        $this->nickname = $nickname;

        return $this;
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
     * 設定真實姓名
     *
     * @param string $name
     * @return UserDetail
     */
    public function setNameReal($name)
    {
        $this->nameReal = $name;

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
     * 設定中文姓名
     *
     * @param string $name
     * @return UserDetail
     */
    public function setNameChinese($name)
    {
        $this->nameChinese = $name;

        return $this;
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
     * 設定英文姓名
     *
     * @param string $name
     * @return UserDetail
     */
    public function setNameEnglish($name)
    {
        $this->nameEnglish = $name;

        return $this;
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
     * 設定國籍
     *
     * @param string $country
     * @return UserDetail
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
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
     * 設定護照號碼
     *
     * @param string $passport
     * @return UserDetail
     */
    public function setPassport($passport)
    {
        $this->passport = $passport;

        return $this;
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
     * 設定身分證字號
     *
     * @param string $identityCard
     * @return UserDetail
     */
    public function setIdentityCard($identityCard)
    {
        $this->identityCard = $identityCard;

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
     * 設定駕照號碼
     *
     * @param string $driverLicense
     * @return UserDetail
     */
    public function setDriverLicense($driverLicense)
    {
        $this->driverLicense = $driverLicense;

        return $this;
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
     * 設定保險證字號
     *
     * @param string $insuranceCard
     * @return UserDetail
     */
    public function setInsuranceCard($insuranceCard)
    {
        $this->insuranceCard = $insuranceCard;

        return $this;
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
     * 設定健保卡號碼
     *
     * @param string $healthCard
     * @return UserDetail
     */
    public function setHealthCard($healthCard)
    {
        $this->healthCard = $healthCard;

        return $this;
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
     * 設定線上取款密碼
     *
     * @param string $password
     * @return UserDetail
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
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
     * 設定生日
     *
     * @param \DateTime $date
     * @return UserDetail
     */
    public function setBirthday($date)
    {
        $this->birthday = $date;

        return $this;
    }

    /**
     * 取得生日
     *
     * @return \DateTime | null
     */
    public function getBirthday()
    {
        // 因正式站有生日欄位為 0000-00-00 00:00:00 的資料, format 之後會變成 -0001-11-30
        // 在 new RemovedUserDetail 時會造成無法刪除帳號
        // 故在此直接驗證格式為此情況則直接回傳 null
        if ($this->birthday && $this->birthday->format('Y-m-d') == '-0001-11-30') {
            return null;
        }

        return $this->birthday;
    }

    /**
     * 設定電話號碼
     *
     * @param string $telephone
     * @return UserDetail
     */
    public function setTelephone($telephone)
    {
        $this->telephone = $telephone;

        return $this;
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
     * 設定QQ帳號
     *
     * @param string $number
     * @return UserDetail
     */
    public function setQQNum($number)
    {
        $this->qqNum = $number;

        return $this;
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
     * 設定備註內容
     *
     * @param String $note
     * @return UserDetail
     */
    public function setNote($note)
    {
        $this->note = $note;

        return $this;
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
     * 設定微信帳號
     *
     * @param string $wechat
     * @return UserDetail
     */
    public function setWechat($wechat)
    {
        $this->wechat = $wechat;

        return $this;
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
     * 從刪除使用者詳細資料備份設定使用者詳細資料
     *
     * @param RemovedUserDetail $removedUD 已刪除的詳細資料
     * @return UserDetail
     */
    public function setFromRemoved(RemovedUserDetail $removedUD)
    {
        if ($this->getUser()->getId() != $removedUD->getRemovedUser()->getUserId()) {
            throw new \RuntimeException('UserDetail not belong to this user', 150010134);
        }

        $this->nickname      = $removedUD->getNickname();
        $this->nameReal      = $removedUD->getNameReal();
        $this->nameChinese   = $removedUD->getNameChinese();
        $this->nameEnglish   = $removedUD->getNameEnglish();
        $this->country       = $removedUD->getCountry();
        $this->passport      = $removedUD->getPassport();
        $this->identityCard  = $removedUD->getIdentityCard();
        $this->driverLicense = $removedUD->getDriverLicense();
        $this->insuranceCard = $removedUD->getInsuranceCard();
        $this->healthCard    = $removedUD->getHealthCard();
        $this->birthday      = $removedUD->getBirthday();
        $this->password      = $removedUD->getPassword();
        $this->telephone     = $removedUD->getTelephone();
        $this->qqNum         = $removedUD->getQQNum();
        $this->note          = $removedUD->getNote();
        $this->wechat        = $removedUD->getWechat();

        return $this;
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
            'user_id'        => $this->getUser()->getId(),
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
