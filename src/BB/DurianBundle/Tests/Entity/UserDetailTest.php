<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\UserDetail;
use BB\DurianBundle\Entity\RemovedUser;
use BB\DurianBundle\Entity\RemovedUserDetail;

class UserDetailTest extends DurianTestCase
{
    /**
     * 測試new UserDetail產生的物件設定值和預設值都正確
     */
    public function testNewUserDetail()
    {
        $user = new User();

        $userDetail = new UserDetail($user);

        // 基本資料檢查
        $this->assertEquals('', $userDetail->getBirthday());
        $this->assertEquals('', $userDetail->getNickName());
        $this->assertEquals('', $userDetail->getNameReal());
        $this->assertEquals('', $userDetail->getNameChinese());
        $this->assertEquals('', $userDetail->getNameEnglish());
        $this->assertEquals('', $userDetail->getCountry());
        $this->assertEquals('', $userDetail->getPassport());
        $this->assertEquals('', $userDetail->getIdentityCard());
        $this->assertEquals('', $userDetail->getDriverLicense());
        $this->assertEquals('', $userDetail->getInsuranceCard());
        $this->assertEquals('', $userDetail->getHealthCard());
        $this->assertEquals('', $userDetail->getPassword());
        $this->assertEquals('', $userDetail->getTelephone());
        $this->assertEquals('', $userDetail->getQQNum());
        $this->assertEquals('', $userDetail->getNote());
        $this->assertEquals('', $userDetail->getWechat());

        $this->assertEquals($user, $userDetail->getUser());

        // set method
        $qq = '485163154787';
        $userDetail->setQQNum($qq);

        $tel = '3345678';
        $userDetail->setTelephone($tel);

        $birth = new \DateTime('0000-00-00 00:00:00');
        $userDetail->setBirthday($birth);
        $this->assertNull($userDetail->getBirthday());

        $birth = new \DateTime('now');
        $userDetail->setBirthday($birth);
        $this->assertEquals($birth, $userDetail->getBirthday());

        $pass = '9527';
        $userDetail->setPassword($pass);

        $passport = 'PA123456';
        $userDetail->setPassport($passport);

        $identityCard = 'IC123456';
        $userDetail->setIdentityCard($identityCard);

        $driverLicense = 'DL123456';
        $userDetail->setDriverLicense($driverLicense);

        $insuranceCard = 'INC123456';
        $userDetail->setInsuranceCard($insuranceCard);

        $healthCard = 'HC123456';
        $userDetail->setHealthCard($healthCard);

        $country = 'Republic of China';
        $userDetail->setCountry($country);

        $name = 'Da Vinci';
        $userDetail->setNameEnglish($name);

        $name = '甲級情報員';
        $userDetail->setNameChinese($name);

        $name = '達文西';
        $userDetail->setNameReal($name);

        $name = 'MJ149';
        $userDetail->setNickName($name);

        $note = 'Hello Durian';
        $userDetail->setNote($note);

        $wechat = 'wechat123';
        $userDetail->setWechat($wechat);

        $array = $userDetail->toArray();

        $this->assertEquals(0, $array['user_id']);
        $this->assertEquals('MJ149', $array['nickname']);
        $this->assertEquals('達文西', $array['name_real']);
        $this->assertEquals('甲級情報員', $array['name_chinese']);
        $this->assertEquals('Da Vinci', $array['name_english']);
        $this->assertEquals($country, $array['country']);
        $this->assertEquals($passport, $array['passport']);
        $this->assertEquals($identityCard, $array['identity_card']);
        $this->assertEquals($driverLicense, $array['driver_license']);
        $this->assertEquals($insuranceCard, $array['insurance_card']);
        $this->assertEquals($healthCard, $array['health_card']);
        $this->assertEquals($pass, $array['password']);
        $this->assertEquals($birth->format('Y-m-d'), $array['birthday']);
        $this->assertEquals($tel, $array['telephone']);
        $this->assertEquals($qq, $array['qq_num']);
        $this->assertEquals($note, $array['note']);
        $this->assertEquals($wechat, $array['wechat']);
    }

    /**
     * 測試從刪除使用者詳細資料備份設定使用者詳細資料
     */
    public function testSetFromRemoved()
    {
        $user = new User();
        $userDetail = new UserDetail($user);

        $userDetail->setNickName('MJ149');
        $userDetail->setBirthday(new \DateTime('1988-08-26'));
        $userDetail->setPassport('PA123456');

        $removedUser = new RemovedUser($user);
        $removedUserDetail = new RemovedUserDetail($removedUser, $userDetail);
        $userDetail2 = new UserDetail($user);

        $userDetail2->setFromRemoved($removedUserDetail);
        $this->assertEquals($userDetail->toArray(), $userDetail2->toArray());
    }

    /**
     * 測試從刪除使用者詳細資料備份設定使用者詳細資料，但指派錯誤
     */
    public function testSetFromRemovedButNotBelongToThisUser()
    {
        $this->setExpectedException(
            'RuntimeException',
            'UserDetail not belong to this user',
            150010134
        );

        $user1 = new User();
        $user1->setId(1);
        $userDetail1 = new UserDetail($user1);

        $user2 = new User();
        $user2->setId(2);
        $userDetail2 = new UserDetail($user2);

        $removedUser = new RemovedUser($user2);
        $removedUserDetail = new RemovedUserDetail($removedUser, $userDetail2);

        $userDetail1->setFromRemoved($removedUserDetail);
    }
}
