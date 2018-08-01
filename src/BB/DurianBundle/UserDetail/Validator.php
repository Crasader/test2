<?php
namespace BB\DurianBundle\UserDetail;

use Symfony\Component\DependencyInjection\Container;
use Doctrine\Common\Util\Inflector;

class Validator
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @param Container $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * 取得 userDetail 該欄位定義的最大長度
     *
     * @param string $fieldName 資料庫欄位名稱
     * @return integer
     */
    public function getMaxLength($fieldName)
    {
        $em = $this->container->get('doctrine.orm.entity_manager');
        $metadata = $em->getClassMetadata('BBDurianBundle:UserDetail');

        $fieldName = Inflector::camelize($fieldName);
        $fieldData = $metadata->getFieldMapping($fieldName);

        return $fieldData['length'];
    }

    /**
     * 驗證暱稱的長度
     *
     * @param string $nickname
     */
    public function validateNicknameLength($nickname)
    {
        $maxLength = $this->getMaxLength('nickname');

        if (mb_strlen($nickname, 'UTF-8') > $maxLength) {
            throw new \InvalidArgumentException('Invalid nickname length given', 150090026);
        }
    }

    /**
     * 驗證真實姓名的長度
     *
     * @param string $nameReal
     */
    public function validateNameRealLength($nameReal)
    {
        $maxLength = $this->getMaxLength('name_real');

        if (mb_strlen($nameReal, 'UTF-8') > $maxLength) {
            throw new \InvalidArgumentException('Invalid name_real length given', 150090027);
        }
    }

    /**
     * 驗證中文姓名的長度
     *
     * @param string $nameChinese
     */
    public function validateNameChineseLength($nameChinese)
    {
        $maxLength = $this->getMaxLength('name_chinese');

        if (mb_strlen($nameChinese, 'UTF-8') > $maxLength) {
            throw new \InvalidArgumentException('Invalid name_chinese length given', 150090028);
        }
    }

    /**
     * 驗證英文姓名的長度
     *
     * @param string $nameEnglish
     */
    public function validateNameEnglishLength($nameEnglish)
    {
        $maxLength = $this->getMaxLength('name_english');

        if (mb_strlen($nameEnglish, 'UTF-8') > $maxLength) {
            throw new \InvalidArgumentException('Invalid name_english length given', 150090029);
        }
    }

    /**
     * 驗證國籍的長度
     *
     * @param string $country
     */
    public function validateCountryLength($country)
    {
        $maxLength = $this->getMaxLength('country');

        if (mb_strlen($country, 'UTF-8') > $maxLength) {
            throw new \InvalidArgumentException('Invalid country length given', 150090030);
        }
    }

    /**
     * 驗證護照字號的長度
     *
     * @param string $passport
     */
    public function validatePassportLength($passport)
    {
        $maxLength = $this->getMaxLength('passport');

        if (mb_strlen($passport, 'UTF-8') > $maxLength) {
            throw new \InvalidArgumentException('Invalid passport length given', 150090031);
        }
    }

    /**
     * 驗證身分證字號的長度
     *
     * @param string $identityCard
     */
    public function validateIdentityCardLength($identityCard)
    {
        $maxLength = $this->getMaxLength('identity_card');

        if (mb_strlen($identityCard, 'UTF-8') > $maxLength) {
            throw new \InvalidArgumentException('Invalid identity_card length given', 150090032);
        }
    }

    /**
     * 驗證駕照號碼的長度
     *
     * @param string $driverLicense
     */
    public function validateDriverLicenseLength($driverLicense)
    {
        $maxLength = $this->getMaxLength('driver_license');

        if (mb_strlen($driverLicense, 'UTF-8') > $maxLength) {
            throw new \InvalidArgumentException('Invalid driver_license length given', 150090033);
        }
    }

    /**
     * 驗證保險證字號的長度
     *
     * @param string $insuranceCard
     */
    public function validateInsuranceCardLength($insuranceCard)
    {
        $maxLength = $this->getMaxLength('insurance_card');

        if (mb_strlen($insuranceCard, 'UTF-8') > $maxLength) {
            throw new \InvalidArgumentException('Invalid insurance_card length given', 150090034);
        }
    }

    /**
     * 驗證健保卡號碼的長度
     *
     * @param string $healthCard
     */
    public function validateHealthCardLength($healthCard)
    {
        $maxLength = $this->getMaxLength('health_card');

        if (mb_strlen($healthCard, 'UTF-8') > $maxLength) {
            throw new \InvalidArgumentException('Invalid health_card length given', 150090035);
        }
    }

    /**
     * 驗證電話號碼的長度
     *
     * @param string $telephone
     */
    public function validateTelephoneLength($telephone)
    {
        $maxLength = $this->getMaxLength('telephone');

        if (mb_strlen($telephone, 'UTF-8') > $maxLength) {
            throw new \InvalidArgumentException('Invalid telephone length given', 150090036);
        }
    }

    /**
     * 驗證QQ號碼的長度
     *
     * @param string $qqNum
     */
    public function validateQQNumLength($qqNum)
    {
        $maxLength = $this->getMaxLength('qq_num');

        if (mb_strlen($qqNum, 'UTF-8') > $maxLength) {
            throw new \InvalidArgumentException('Invalid qq_num length given', 150090037);
        }
    }

    /**
     * 驗證密碼的長度
     *
     * @param string $password
     */
    public function validatePasswordLength($password)
    {
        $maxLength = $this->getMaxLength('password');

        if (mb_strlen($password, 'UTF-8') > $maxLength) {
            throw new \InvalidArgumentException('Invalid password length given', 150090038);
        }
    }

    /**
     * 驗證備註的長度
     *
     * @param string $note
     */
    public function validateNoteLength($note)
    {
        $maxLength = $this->getMaxLength('note');

        if (mb_strlen($note, 'UTF-8') > $maxLength) {
            throw new \InvalidArgumentException('Invalid note length given', 150090039);
        }
    }

    /**
     * 驗證微信帳號的長度
     *
     * @param string $wechat
     */
    public function validateWechatLength($wechat)
    {
        $maxLength = $this->getMaxLength('wechat');

        if (mb_strlen($wechat, 'UTF-8') > $maxLength) {
            throw new \InvalidArgumentException('Invalid wechat length given', 150090043);
        }
    }
}
