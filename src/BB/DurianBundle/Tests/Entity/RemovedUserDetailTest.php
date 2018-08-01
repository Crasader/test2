<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\UserDetail;
use BB\DurianBundle\Entity\RemovedUser;
use BB\DurianBundle\Entity\RemovedUserDetail;

class RemovedUserDetailTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testRemovedUserDetailBasic()
    {
        $user = new User();
        $detail = new UserDetail($user);

        $removedUser = new RemovedUser($user);
        $removeUserDetail = new RemovedUserDetail($removedUser, $detail);

        $this->assertEquals($removedUser, $removeUserDetail->getRemovedUser());
        $this->assertEquals('', $removeUserDetail->getQQNum());
        $this->assertEquals('', $removeUserDetail->getTelephone());
        $this->assertEquals('', $removeUserDetail->getBirthday());
        $this->assertEquals('', $removeUserDetail->getPassword());
        $this->assertEquals('', $removeUserDetail->getPassport());
        $this->assertEquals('', $removeUserDetail->getIdentityCard());
        $this->assertEquals('', $removeUserDetail->getDriverLicense());
        $this->assertEquals('', $removeUserDetail->getInsuranceCard());
        $this->assertEquals('', $removeUserDetail->getHealthCard());
        $this->assertEquals('', $removeUserDetail->getCountry());
        $this->assertEquals('', $removeUserDetail->getNameEnglish());
        $this->assertEquals('', $removeUserDetail->getNameChinese());
        $this->assertEquals('', $removeUserDetail->getNameReal());
        $this->assertEquals('', $removeUserDetail->getNickname());
        $this->assertEquals('', $removeUserDetail->getNote());

        $qq = '485163154787';
        $detail->setQQNum($qq);

        $tel = '3345678';
        $detail->setTelephone($tel);

        $birth = new \DateTime('now');
        $detail->setBirthday($birth);

        $pass = '9527';
        $detail->setPassword($pass);

        $passport = 'PA123456';
        $detail->setPassport($passport);

        $identityCard = 'IC123456';
        $detail->setIdentityCard($identityCard);

        $driverLicense = 'DL123456';
        $detail->setDriverLicense($driverLicense);

        $insuranceCard = 'INC123456';
        $detail->setInsuranceCard($insuranceCard);

        $healthCard = 'HC123456';
        $detail->setHealthCard($healthCard);

        $country = 'Republic of China';
        $detail->setCountry($country);

        $eName = 'Da Vinci';
        $detail->setNameEnglish($eName);

        $cName = '甲級情報員';
        $detail->setNameChinese($cName);

        $rName = '達文西';
        $detail->setNameReal($rName);

        $nName = 'MJ149';
        $detail->setNickname($nName);

        $note = 'Hello Durian';
        $detail->setNote($note);

        $removedUser = new RemovedUser($user);
        $removeUserDetail = new RemovedUserDetail($removedUser, $detail);

        $this->assertEquals($removedUser, $removeUserDetail->getRemovedUser());
        $this->assertEquals($qq, $removeUserDetail->getQQNum());
        $this->assertEquals($tel, $removeUserDetail->getTelephone());
        $this->assertEquals($birth, $removeUserDetail->getBirthday());
        $this->assertEquals($pass, $removeUserDetail->getPassword());
        $this->assertEquals($passport, $removeUserDetail->getPassport());
        $this->assertEquals($identityCard, $removeUserDetail->getIdentityCard());
        $this->assertEquals($driverLicense, $removeUserDetail->getDriverLicense());
        $this->assertEquals($insuranceCard, $removeUserDetail->getInsuranceCard());
        $this->assertEquals($healthCard, $removeUserDetail->getHealthCard());
        $this->assertEquals($country, $removeUserDetail->getCountry());
        $this->assertEquals($eName, $removeUserDetail->getNameEnglish());
        $this->assertEquals($cName, $removeUserDetail->getNameChinese());
        $this->assertEquals($rName, $removeUserDetail->getNameReal());
        $this->assertEquals($nName, $removeUserDetail->getNickname());
        $this->assertEquals($note, $removeUserDetail->getNote());

        $array = $removeUserDetail->toArray();

        $this->assertEquals(0, $array['user_id']);
        $this->assertEquals($nName, $array['nickname']);
        $this->assertEquals($rName, $array['name_real']);
        $this->assertEquals($cName, $array['name_chinese']);
        $this->assertEquals($eName, $array['name_english']);
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
    }

    /**
     * 測試詳細設定指派錯誤
     */
    public function testUserDetailNotBelongToThisUser()
    {
        $this->setExpectedException(
            'RuntimeException',
            'UserDetail not belong to this user',
            150010134
        );

        $user1 = new User();
        $user1->setId(1);
        $detail = new UserDetail($user1);

        $user2 = new User();
        $user2->setId(2);
        $removeUser = new RemovedUser($user2);
        $removeCash = new RemovedUserDetail($removeUser, $detail);
    }
}
