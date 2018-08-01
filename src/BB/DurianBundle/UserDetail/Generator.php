<?php

namespace BB\DurianBundle\UserDetail;

use Symfony\Component\DependencyInjection\ContainerAware;
use BB\DurianBundle\Entity\UserDetail;
use BB\DurianBundle\Entity\User;

class Generator extends ContainerAware
{
    /**
     * 新增使用者詳細資料
     *
     * @param User $user
     * @param array $detailData
     *
     * @return UserDetail $detail
     */
    public function create($user, $detailData)
    {
        if ($user->isTest()) {
            $detailData['name_real'] = 'Test User';
        }

        if (count($detailData) == 0) {
            $detail = new UserDetail($user);

            return $detail;
        }

        $validator = $this->container->get('durian.validator');
        $parameterHandler = $this->container->get('durian.parameter_handler');
        $userDetailValidator = $this->container->get('durian.user_detail_validator');

        $detail = new UserDetail($user);

        // 電話號碼允許格式 ex:+11111111111或11111111111
        if (isset($detailData['telephone'])) {
            $validator->validateTelephone($detailData['telephone']);
            $userDetailValidator->validateTelephoneLength($detailData['telephone']);
        }

        // 最多只可帶入一個有值的證件欄位
        $credentialCount = 0;
        if (isset($detailData['passport']) && $detailData['passport'] != '') {
            $credentialCount++;
        }

        if (isset($detailData['identity_card']) && $detailData['identity_card'] != '') {
            $credentialCount++;
        }

        if (isset($detailData['driver_license']) && $detailData['driver_license'] != '') {
            $credentialCount++;
        }

        if (isset($detailData['insurance_card']) && $detailData['insurance_card'] != '') {
            $credentialCount++;
        }

        if (isset($detailData['health_card']) && $detailData['health_card'] != '') {
            $credentialCount++;
        }

        if ($credentialCount > 1) {
            throw new \RuntimeException('Cannot specify more than one credential fields', 150090011);
        }

        if (isset($detailData['nickname'])) {
            $validator->validateEncode($detailData['nickname']);
            $detailData['nickname'] = $parameterHandler->filterSpecialChar($detailData['nickname']);
            $userDetailValidator->validateNicknameLength($detailData['nickname']);

            $detail->setNickname($detailData['nickname']);
        }

        if (isset($detailData['name_real'])) {
            $validator->validateEncode($detailData['name_real']);
            $detailData['name_real'] = $parameterHandler->filterSpecialChar($detailData['name_real']);
            $userDetailValidator->validateNameRealLength($detailData['name_real']);

            $specialCharacter = ['\0', '\t', '\n', '\r', '\x0B'];

            // 指定特殊字元會被移除，所以移除後若是與原字串不同，就代表字串帶有特殊字元
            if (str_replace($specialCharacter, '', $detailData['name_real']) != $detailData['name_real']) {
                throw new \InvalidArgumentException('Invalid name_real', 150090042);
            }

            $detail->setNameReal($detailData['name_real']);
        }

        if (isset($detailData['name_chinese'])) {
            $validator->validateEncode($detailData['name_chinese']);
            $detailData['name_chinese'] = $parameterHandler->filterSpecialChar($detailData['name_chinese']);
            $userDetailValidator->validateNameChineseLength($detailData['name_chinese']);

            $detail->setNameChinese($detailData['name_chinese']);
        }

        if (isset($detailData['name_english'])) {
            $validator->validateEncode($detailData['name_english']);
            $detailData['name_english'] = $parameterHandler->filterSpecialChar($detailData['name_english']);
            $userDetailValidator->validateNameEnglishLength($detailData['name_english']);

            $detail->setNameEnglish($detailData['name_english']);
        }

        if (isset($detailData['country'])) {
            $validator->validateEncode($detailData['country']);
            $detailData['country'] = $parameterHandler->filterSpecialChar($detailData['country']);
            $userDetailValidator->validateCountryLength($detailData['country']);

            $detail->setCountry($detailData['country']);
        }

        if (isset($detailData['passport'])) {
            $validator->validateEncode($detailData['passport']);
            $detailData['passport'] = $parameterHandler->filterSpecialChar($detailData['passport']);
            $userDetailValidator->validatePassportLength($detailData['passport']);

            $detail->setPassport($detailData['passport']);
        }

        if (isset($detailData['identity_card'])) {
            $validator->validateEncode($detailData['identity_card']);
            $detailData['identity_card'] = $parameterHandler->filterSpecialChar($detailData['identity_card']);
            $userDetailValidator->validateIdentityCardLength($detailData['identity_card']);

            $detail->setIdentityCard($detailData['identity_card']);
        }

        if (isset($detailData['driver_license'])) {
            $validator->validateEncode($detailData['driver_license']);
            $detailData['driver_license'] = $parameterHandler->filterSpecialChar($detailData['driver_license']);
            $userDetailValidator->validateDriverLicenseLength($detailData['driver_license']);

            $detail->setDriverLicense($detailData['driver_license']);
        }

        if (isset($detailData['insurance_card'])) {
            $validator->validateEncode($detailData['insurance_card']);
            $detailData['insurance_card'] = $parameterHandler->filterSpecialChar($detailData['insurance_card']);
            $userDetailValidator->validateInsuranceCardLength($detailData['insurance_card']);

            $detail->setInsuranceCard($detailData['insurance_card']);
        }

        if (isset($detailData['health_card'])) {
            $validator->validateEncode($detailData['health_card']);
            $detailData['health_card'] = $parameterHandler->filterSpecialChar($detailData['health_card']);
            $userDetailValidator->validateHealthCardLength($detailData['health_card']);

            $detail->setHealthCard($detailData['health_card']);
        }

        if (isset($detailData['telephone'])) {
            $detail->setTelephone($detailData['telephone']);
        }

        if (isset($detailData['qq_num'])) {
            $validator->validateEncode($detailData['qq_num']);
            $detailData['qq_num'] = $parameterHandler->filterSpecialChar($detailData['qq_num']);
            $userDetailValidator->validateQQNumLength($detailData['qq_num']);

            $detail->setQQNum($detailData['qq_num']);
        }

        if (isset($detailData['note'])) {
            $validator->validateEncode($detailData['note']);
            $detailData['note'] = $parameterHandler->filterSpecialChar($detailData['note']);
            $userDetailValidator->validateNoteLength($detailData['note']);

            $detail->setNote($detailData['note']);
        }

        if (isset($detailData['password'])) {
            $validator->validateEncode($detailData['password']);
            $detailData['password'] = $parameterHandler->filterSpecialChar($detailData['password']);
            $userDetailValidator->validatePasswordLength($detailData['password']);

            $detail->setPassword($detailData['password']);
        }

        if (isset($detailData['birthday'])) {
            if (!$validator->validateDate($detailData['birthday'])) {
                throw new \InvalidArgumentException('Invalid birthday given', 150090025);
            }

            $birth = new \DateTime($detailData['birthday']);
            $detail->setBirthday($birth);
        }

        if (isset($detailData['wechat'])) {
            $validator->validateEncode($detailData['wechat']);
            $detailData['wechat'] = $parameterHandler->filterSpecialChar($detailData['wechat']);
            $userDetailValidator->validateWechatLength($detailData['wechat']);

            $detail->setWechat($detailData['wechat']);
        }

        return $detail;
    }
}
