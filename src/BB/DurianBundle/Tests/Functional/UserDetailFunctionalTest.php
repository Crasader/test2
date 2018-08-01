<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\UserEmail;

class UserDetailFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserDetailData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserEmailData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPromotionData'
        ];

        $this->loadFixtures($classnames);
        $this->loadFixtures([], 'share');

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBlacklistData'
        ];

        $this->loadFixtures($classnames, 'share');

        $this->clearSensitiveLog();

        $sensitiveData = 'entrance=2&operator=test&client_ip=127.0.0.1';
        $sensitiveData .= '&run_php=UserDetailFunctionsTest.php&operator_id=2&vendor=acc';

        $this->sensitiveData = $sensitiveData;
        $this->headerParam = array('HTTP_SENSITIVE_DATA' => $sensitiveData);
    }

    /**
     * 修改使用者詳細資料
     */
    public function testEditUserDetail()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();
        $user = $em->find('BB\DurianBundle\Entity\User', 8);
        $oriModifiedAtStamp = $user->getModifiedAt()->getTimestamp();
        $em->clear();

        $parameters = [
            'nickname'       => '柯P',
            'passport'       => 'MPA123456',
            'birthday'       => '2002-02-02',
            'telephone'      => '1234567',
            'password'       => '5566',
            'qq_num'         => '354895477237',
            'note'           => 'Hello Durian Again',
            'email'          => '-Ra_fael.@china.town-.com',
            'country'        => 'Republic of Durian',
        ];

        $client->request('PUT', '/api/user/8/detail', $parameters, array(), $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals('達文西', $output['ret']['name_real']);

        // 詳細設定檢查
        $user = $em->find('BBDurianBundle:User', 8);

        $userDetail = $em
            ->getRepository('BBDurianBundle:UserDetail')
            ->findOneByUser($user->getId());
        $userEmail = $em->find('BBDurianBundle:UserEmail', 8);

        // 修改後
        $this->assertEquals('354895477237', $userDetail->getQQNum());
        $this->assertEquals('Hello Durian Again', $userDetail->getNote());
        $this->assertEquals('1234567', $userDetail->getTelephone());
        $this->assertEquals('MPA123456', $userDetail->getPassport());
        $this->assertEquals('5566', $userDetail->getPassword());
        $this->assertEquals('2002-02-02', $userDetail->getBirthday()->format('Y-m-d'));
        $this->assertEquals('-Ra_fael.@china.town-.com', $userEmail->getEmail());
        $this->assertEquals('Republic of Durian', $userDetail->getCountry());
        $this->assertEquals('柯P', $userDetail->getNickname());

        // 未修改
        $this->assertEquals('Da Vinci', $userDetail->getNameEnglish());
        $this->assertEquals('甲級情報員', $userDetail->getNameChinese());
        $this->assertEquals('達文西', $userDetail->getNameReal());

        //驗證user的modifiedAt有更新
        $now = new \DateTime();
        $nowStamp = $now->getTimestamp();
        $modifiedAtStamp = $user->getModifiedAt()->getTimestamp();
        $this->assertGreaterThanOrEqual(0, $modifiedAtStamp - $oriModifiedAtStamp);//>=0 一定有更新
        $this->assertLessThanOrEqual(3, $nowStamp - $modifiedAtStamp);//容錯3秒

        // 使用者操作紀錄檢查
        $userEmailLogOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('user_email', $userEmailLogOperation->getTableName());
        $this->assertEquals('@user_id:8', $userEmailLogOperation->getMajorKey());
        $this->assertContains(
            '@email:Davinci@chinatown.com=>-Ra_fael.@china.town-.com',
            $userEmailLogOperation->getMessage()
        );

        $userLogOperation = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertEquals('user', $userLogOperation->getTableName());
        $this->assertEquals('@id:8', $userLogOperation->getMajorKey());
        $this->assertEquals('@modifiedAt', substr($userLogOperation->getMessage(), 0, 11));

        // 檢查user_email有寫入
        $userEmail = $em->find('BBDurianBundle:UserEmail', $user);
        $this->assertEquals('-Ra_fael.@china.town-.com', $userEmail->getEmail());

        // check log file exists
        $logPath = $this->getLogfilePath('sensitive.log');
        $this->assertFileExists($logPath);

        //read log to check content
        $results = explode(PHP_EOL, file_get_contents($logPath));

        $string = "sensitive-data:$this->sensitiveData";
        $this->assertTrue(strpos($results[0], $string) !== false);
    }

    /**
     * 修改使用者詳細資料前後email相同
     */
    public function testEditUserDetailWithSameUserEmail()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $userEmail = $em->find('BBDurianBundle:UserEmail', 8);

        $em->flush();
        $em->clear();

        $parameters = [
            'nickname'  => '柯P',
            'passport'  => 'MPA123456',
            'birthday'  => '2002-02-02',
            'telephone' => '1234567',
            'password'  => '5566',
            'qq_num'    => '354895477237',
            'note'      => 'Hello Durian Again',
            'email'     => 'Davinci@chinatown.com',
            'country'   => 'Republic of Durian',
        ];

        $client->request('PUT', '/api/user/8/detail', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals('達文西', $output['ret']['name_real']);

        // 檢查user_email資料沒重新寫入
        $userEmail = $em->find('BBDurianBundle:UserEmail', 8);
        $this->assertEquals('Davinci@chinatown.com', $userEmail->getEmail());
        $this->assertFalse($userEmail->isConfirm());
        $this->assertNull($userEmail->getConfirmAt());
    }

    /**
     * 修改使用者詳細資料遊戲暱稱，但遊戲暱稱含有空白
     */
    public function testEditUserDetailWithNicknameAndNicknameContainsBlanks()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $user = $em->find('BBDurianBundle:User', 8);
        $em->clear();

        $parameters = ['nickname' => ' 康培士 '];

        $client->request('PUT', '/api/user/8/detail', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();

        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals('康培士', $output['ret']['nickname']);

        $userDetail = $em->getRepository('BBDurianBundle:UserDetail')
            ->findOneByUser($user->getId());

        // 修改後
        $this->assertEquals('康培士', $userDetail->getNickname());
    }

    /**
     * 修改使用者詳細資料電話號碼帶文字
     */
    public function testEditUserDetailWithTelephoneInputCharacter()
    {
        $client = $this->createClient();

        $parameters = [
            'passport'  => 'MPA123456',
            'birthday'  => '2002-02-02',
            'telephone' => '123456x',
            'password'  => '5566',
            'qq_num'    => '354895477237',
            'note'      => 'Hello Durian Again'
        ];

        $client->request('PUT', '/api/user/8/detail', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150610001, $output['code']);
        $this->assertEquals('Invalid telephone', $output['msg']);
    }

    /**
     * 修改使用者詳細資料電話號碼帶+以外的特殊符號
     */
    public function testEditUserDetailWithTelephoneInputExceptPlusSpecialCharacter()
    {
        $client = $this->createClient();

        $parameters = [
            'passport'  => 'MPA123456',
            'birthday'  => '2002-02-02',
            'telephone' => '123*567',
            'password'  => '5566',
            'qq_num'    => '354895477237',
            'note'      => 'Hello Durian Again'
        ];

        $client->request('PUT', '/api/user/8/detail', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150610001, $output['code']);
        $this->assertEquals('Invalid telephone', $output['msg']);
    }

    /**
     * 修改使用者詳細資料電話號碼開頭以外帶+
     */
    public function testEditUserDetailWithTelephoneInputExceptStartWithPlus()
    {
        $client = $this->createClient();

        $parameters = [
            'passport'  => 'MPA123456',
            'birthday'  => '2002-02-02',
            'telephone' => '1234567+',
            'password'  => '5566',
            'qq_num'    => '354895477237',
            'note'      => 'Hello Durian Again'
        ];

        $client->request('PUT', '/api/user/8/detail', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150610001, $output['code']);
        $this->assertEquals('Invalid telephone', $output['msg']);
    }

    /**
     * 修改使用者詳細資料電話號碼開頭帶超過1個+
     */
    public function testEditUserDetailWithTelephoneInputStartWithOverOnePlus()
    {
        $client = $this->createClient();

        $parameters = [
            'passport'  => 'MPA123456',
            'birthday'  => '2002-02-02',
            'telephone' => '++1234567',
            'password'  => '5566',
            'qq_num'    => '354895477237',
            'note'      => 'Hello Durian Again'
        ];

        $client->request('PUT', '/api/user/8/detail', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150610001, $output['code']);
        $this->assertEquals('Invalid telephone', $output['msg']);
    }

    /**
     * 修改使用者詳細資料電話號碼開頭帶1個+
     */
    public function testEditUserDetailWithTelephoneInputStartWithOnePlus()
    {
        $client = $this->createClient();

        $parameters = [
            'passport'  => 'MPA123456',
            'birthday'  => '2002-02-02',
            'telephone' => '+1234567',
            'password'  => '5566',
            'qq_num'    => '354895477237',
            'note'      => 'Hello Durian Again'
        ];

        $client->request('PUT', '/api/user/8/detail', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('+1234567', $output['ret']['telephone']);
    }

    /**
     * 修改使用者詳細資料電話號碼帶純數字
     */
    public function testEditUserDetailWithTelephoneInputIntegerOnly()
    {
        $client = $this->createClient();

        $parameters = [
            'passport'  => 'MPA123456',
            'birthday'  => '2002-02-02',
            'telephone' => '1234567',
            'password'  => '5566',
            'qq_num'    => '354895477237',
            'note'      => 'Hello Durian Again'
        ];

        $client->request('PUT', '/api/user/8/detail', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('1234567', $output['ret']['telephone']);
    }

    /**
     * 修改使用者詳細資料電話號碼帶空字串
     */
    public function testEditUserDetailWithTelephoneInputNull()
    {
        $client = $this->createClient();

        $parameters = [
            'passport'  => 'MPA123456',
            'birthday'  => '2002-02-02',
            'telephone' => '',
            'password'  => '5566',
            'qq_num'    => '354895477237',
            'note'      => 'Hello Durian Again'
        ];

        $client->request('PUT', '/api/user/8/detail', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('', $output['ret']['telephone']);
    }

    /**
     * 測試修改使用者詳細資料，欲改變證件欄位時，需將原證件欄位帶入空字串
     */
    public function testEditUserDetailWithChangeCredentialColumn()
    {
        $client = $this->createClient();

        $parameters = [
            'passport'      => '',
            'identity_card' => 'MIC123456',
        ];

        $client->request('PUT', '/api/user/8/detail', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals('達文西', $output['ret']['name_real']);
        $this->assertEmpty($output['ret']['passport']);
        $this->assertEquals('MIC123456', $output['ret']['identity_card']);

        $parameters = [
            'identity_card' => '',
            'driver_license' => 'DL654321',
        ];

        $client->request('PUT', '/api/user/8/detail', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals('達文西', $output['ret']['name_real']);
        $this->assertEmpty($output['ret']['identity_card']);
        $this->assertEquals('DL654321', $output['ret']['driver_license']);

        $parameters = [
            'driver_license' => '',
            'insurance_card' => 'ISC654321',
        ];

        $client->request('PUT', '/api/user/8/detail', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals('達文西', $output['ret']['name_real']);
        $this->assertEmpty($output['ret']['driver_license']);
        $this->assertEquals('ISC654321', $output['ret']['insurance_card']);

        $parameters = [
            'insurance_card' => '',
            'health_card' => 'HC123456',
        ];

        $client->request('PUT', '/api/user/8/detail', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals('達文西', $output['ret']['name_real']);
        $this->assertEmpty($output['ret']['insurance_card']);
        $this->assertEquals('HC123456', $output['ret']['health_card']);
    }

    /**
     * 測試編輯使用者詳細資料時輸入非UTF8
     */
    public function testEditUserDetailInputNotUtf8()
    {
        $client = $this->createClient();

        $parameters = array(
            'name_real' => mb_convert_encoding('龜龍鱉', 'GB2312', 'UTF-8'),
            'name_chinese' => '我是誰',
            'name_english' => '亞洲',
            'note' => '亞洲'
        );

        //測試輸入字元非UTF8
        $client->request('PUT', '/api/user/8/detail', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150610002, $output['code']);
        $this->assertEquals('String must use utf-8 encoding', $output['msg']);

        $parameters = array(
            'name_chinese' => mb_convert_encoding('龜龍鱉', 'GB2312', 'UTF-8'),
            'name_english' => '亞洲',
            'note' => '亞洲'
        );

        //測試輸入字元非UTF8
        $client->request('PUT', '/api/user/8/detail', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150610002, $output['code']);
        $this->assertEquals('String must use utf-8 encoding', $output['msg']);

        $parameters = array(
            'name_chinese' => '龜龍鱉',
            'name_english' => mb_convert_encoding('龜龍鱉', 'GB2312', 'UTF-8'),
            'note' => '亞洲'
        );

        //測試輸入字元非UTF8
        $client->request('PUT', '/api/user/8/detail', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150610002, $output['code']);
        $this->assertEquals('String must use utf-8 encoding', $output['msg']);

        $parameters = array(
            'name_chinese' => '龜龍鱉',
            'name_english' => '龜龍鱉',
            'note' => mb_convert_encoding('龜龍鱉', 'GB2312', 'UTF-8')
        );

        //測試輸入字元非UTF8
        $client->request('PUT', '/api/user/8/detail', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150610002, $output['code']);
        $this->assertEquals('String must use utf-8 encoding', $output['msg']);
    }

    /**
     * 測試編輯使用者詳細資料時欄位開頭為0
     */
    public function testEditUserDetailStartWithZero()
    {
        $client = $this->createClient();

        //測試改護照號碼
        $parameters = ['passport'  => '0PA123456'];

        $client->request('PUT', '/api/user/8/detail', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals('0PA123456', $output['ret']['passport']);

        //測試改身分證字號
        $parameters = [
            'passport' => '',
            'identity_card' => '123456'
        ];

        $client->request('PUT', '/api/user/8/detail', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('123456', $output['ret']['identity_card']);

        $parameters = ['identity_card' => '0123456'];

        $client->request('PUT', '/api/user/8/detail', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('0123456', $output['ret']['identity_card']);

        //測試改駕照號碼
        $parameters = [
            'identity_card' => '',
            'driver_license' => '13524'
        ];

        $client->request('PUT', '/api/user/8/detail', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('13524', $output['ret']['driver_license']);

        $parameters = ['driver_license' => '013524'];

        $client->request('PUT', '/api/user/8/detail', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('013524', $output['ret']['driver_license']);

        //測試改保險證字號
        $parameters = [
            'driver_license' => '',
            'insurance_card' => '246810'
        ];

        $client->request('PUT', '/api/user/8/detail', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('246810', $output['ret']['insurance_card']);

        $parameters = ['insurance_card' => '0246810'];

        $client->request('PUT', '/api/user/8/detail', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('0246810', $output['ret']['insurance_card']);

        //測試改健保卡號碼
        $parameters = [
            'insurance_card' => '',
            'health_card' => '13579'
        ];

        $client->request('PUT', '/api/user/8/detail', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('13579', $output['ret']['health_card']);

        $parameters = ['health_card' => '013579'];

        $client->request('PUT', '/api/user/8/detail', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('013579', $output['ret']['health_card']);

        //測試修改聯絡電話
        $this->assertEquals('3345678',$output['ret']['telephone']);

        $parameters = ['telephone' => '03345678'];

        $client->request('PUT', '/api/user/8/detail', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('03345678', $output['ret']['telephone']);

        //測試修改QQ帳號
        $this->assertEquals('485163154787', $output['ret']['qq_num']);

        $parameters = ['qq_num' => '0485163154787'];

        $client->request('PUT', '/api/user/8/detail', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('0485163154787', $output['ret']['qq_num']);

    }

    /**
     * 測試修改使用者詳細資料，帶入超過一個有值的證件欄位
     */
    public function testEditUserDetailWithMoreThanOneCredentialValues()
    {
        $client = $this->createClient();

        $parameters = [
            'passport'      => 'MPA123456',
            'identity_card' => 'MIC123456',
        ];

        $client->request('PUT', '/api/user/8/detail', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150090011, $output['code']);
        $this->assertEquals('Cannot specify more than one credential fields', $output['msg']);
    }

    /**
     * 測試修改email帶空字串，可正確修改
     */
    public function testEditUserDetailWithEmptyEmail()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = ['email' => ''];

        $client->request('PUT', '/api/user/8/detail', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals('', $output['ret']['email']);

        $email = $em->find('BBDurianBundle:UserEmail', 8);

        $this->assertEquals('', $email->getEmail());
    }

    /**
     * 測試編輯使用者詳細資料真實姓名
     */
    public function testEditUserDetailWithNameReal()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        //測試輸入不符合規則真實姓名 達文西1
        $parameters = array(
            'name_real' => '達文西1'
        );

        $client->request('PUT', '/api/user/8/detail', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150090006, $output['code']);
        $this->assertEquals('Invalid name_real', $output['msg']);

        //測試輸入不符合規則真實姓名 達文西\'"
        $parameters = array(
            'name_real' => "達文西\'\""
        );

        $client->request('PUT', '/api/user/8/detail', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150090006, $output['code']);
        $this->assertEquals('Invalid name_real', $output['msg']);

        //測試輸入不符合規則真實姓名 達文-西
        $parameters = array(
            'name_real' => '達文-西'
        );

        $client->request('PUT', '/api/user/8/detail', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150090006, $output['code']);
        $this->assertEquals('Invalid name_real', $output['msg']);

        //測試輸入不符合規則真實姓名 達文西-
        $parameters = array(
            'name_real' => '達文西-'
        );

        $client->request('PUT', '/api/user/8/detail', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150090006, $output['code']);
        $this->assertEquals('Invalid name_real', $output['msg']);

        //測試輸入不符合規則真實姓名 -1達文西
        $parameters = array(
            'name_real' => '-1達文西'
        );

        $client->request('PUT', '/api/user/8/detail', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150090006, $output['code']);
        $this->assertEquals('Invalid name_real', $output['msg']);

        //測試輸入符合規則真實姓名 達文西 => 達文西
        $parameters = array(
            'name_real' => '達文西'
        );

        $client->request('PUT', '/api/user/8/detail', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('達文西', $output['ret']['name_real']);

        //測試輸入符合規則真實姓名 達文西 => 達文西-1
        $parameters = array(
            'name_real' => '達文西-1'
        );

        $client->request('PUT', '/api/user/8/detail', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('達文西-1', $output['ret']['name_real']);

        //測試輸入符合規則真實姓名 達文西-1 => 達文西-10
        $parameters = array(
            'name_real' => '達文西-10'
        );

        $client->request('PUT', '/api/user/8/detail', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('達文西-10', $output['ret']['name_real']);

        //測試輸入不符合規則真實姓名 達文西-123
        $parameters = array(
            'name_real' => '達文西-123'
        );

        $client->request('PUT', '/api/user/8/detail', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150090006, $output['code']);
        $this->assertEquals('Invalid name_real', $output['msg']);

        //測試輸入不符合規則真實姓名 達文西-1-2
        $parameters = array(
            'name_real' => '達文西-1-2'
        );

        $client->request('PUT', '/api/user/8/detail', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150090006, $output['code']);
        $this->assertEquals('Invalid name_real', $output['msg']);

        //測試輸入不符合規則真實姓名 達文西-0
        $parameters = array(
            'name_real' => '達文西-0'
        );

        $client->request('PUT', '/api/user/8/detail', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150090006, $output['code']);
        $this->assertEquals('Invalid name_real', $output['msg']);

        //測試輸入符合規則真實姓名 達文西-10 => 達文西-2
        $parameters = array(
            'name_real' => '達文西-2'
        );

        $client->request('PUT', '/api/user/8/detail', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('達文西-2', $output['ret']['name_real']);

        //測試輸入不符合規則真實姓名 頂-1
        $parameters = array(
            'name_real' => '頂-1'
        );

        $client->request('PUT', '/api/user/8/detail', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150090006, $output['code']);
        $this->assertEquals('Invalid name_real', $output['msg']);

        //測試輸入符合規則真實姓名 達文西-10 => 達文西
        $parameters = array(
            'name_real' => '達文西'
        );

        $client->request('PUT', '/api/user/8/detail', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('達文西', $output['ret']['name_real']);

        //測試輸入符合規則真實姓名 達文西 => 達文西-*
        $parameters = array(
            'name_real' => '達文西-*'
        );

        $client->request('PUT', '/api/user/8/detail', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('達文西-*', $output['ret']['name_real']);

        //測試輸入符合規則真實姓名 達文西-* => 達文西-1
        $parameters = array(
            'name_real' => '達文西-1'
        );

        $client->request('PUT', '/api/user/8/detail', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('達文西-1', $output['ret']['name_real']);

        //測試原本真實姓名為空字串可否任意修改成其他姓名
        $detail = $em->find('BBDurianBundle:UserDetail', 8);
        $detail->setNameReal('');
        $em->flush();

        //測試輸入符合規則真實姓名 '' => 李奧納多
        $parameters = array(
            'name_real' => '李奧納多'
        );

        $client->request('PUT', '/api/user/8/detail', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('李奧納多', $output['ret']['name_real']);

        // 測試修改測試帳號的真實姓名
        $detail = $em->find('BBDurianBundle:UserDetail', 8);
        $detail->setNameReal('Test User');
        $user = $em->find('BBDurianBundle:User', 8);
        $user->setTest(true);
        $em->flush();

        $parameters = ['name_real' => 'Test User-1'];

        $client->request('PUT', '/api/user/8/detail', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150090024, $output['code']);
        $this->assertEquals('Test user can not edit name_real', $output['msg']);
    }

    /**
     * 測試修改使用者真實姓名，有特殊字元會自動過濾
     */
    public function testEditUserDetailWithNameRealContainsSpecialCharacter()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $detail = $em->find('BBDurianBundle:UserDetail', 8);
        $detail->setNameReal('');

        $em->flush();

        $parameters = ['name_real' => '特殊字元'];

        $client->request('PUT', '/api/user/8/detail', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('特殊字元', $output['ret']['name_real']);
    }

    /**
     * 測試修改使用者詳細資料生日帶空字串
     */
    public function testEditUserDetailWhenBirthdayIsEmpty()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = array(
            'birthday' => ''
        );

        $client->request('PUT', '/api/user/8/detail', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals(null, $output['ret']['birthday']);

        // 詳細設定生日檢查
        $user = $em->find('BBDurianBundle:User', 8);
        $userDetail = $em
            ->getRepository('BBDurianBundle:UserDetail')
            ->findOneByUser($user->getId());

        $this->assertEquals(null, $userDetail->getBirthday());
    }

    /**
     * 測試由使用者取得詳細資訊
     */
    public function testGetUserDetailByUser()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = array('sub_ret' => 1);

        $client->request('GET', '/api/user/8/detail', $parameters, array(), $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $detail = $em->find('BBDurianBundle:UserDetail', 8);
        $email = $em->find('BBDurianBundle:UserEmail', 8);

        // 詳細資訊
        $this->assertEquals($email->getEmail(), $output['ret']['email']);
        $this->assertEquals($detail->getNickname(), $output['ret']['nickname']);
        $this->assertEquals($detail->getNameReal(), $output['ret']['name_real']);
        $this->assertEquals($detail->getNameChinese(), $output['ret']['name_chinese']);
        $this->assertEquals($detail->getNameEnglish(), $output['ret']['name_english']);
        $this->assertEquals($detail->getCountry(), $output['ret']['country']);
        $this->assertEquals($detail->getPassport(), $output['ret']['passport']);
        $this->assertEquals($detail->getTelephone(), $output['ret']['telephone']);
        $this->assertEquals($detail->getQQNum(), $output['ret']['qq_num']);
        $this->assertEquals($detail->getNote(), $output['ret']['note']);
        $this->assertEquals($detail->getBirthday()->format('Y-m-d'), $output['ret']['birthday']);
        $this->assertEquals($detail->getPassword(), $output['ret']['password']);

        $user = $em->find('BBDurianBundle:User', 8);

        //附屬資訊
        $this->assertEquals($user->getId(), $output['sub_ret']['user']['id']);
        $this->assertEquals($user->getUsername(), $output['sub_ret']['user']['username']);
        $this->assertEquals($user->getAlias(), $output['sub_ret']['user']['alias']);

        // check log file exists
        $logPath = $this->getLogfilePath('sensitive.log');
        $this->assertFileExists($logPath);

        //read log to check content
        $results = explode(PHP_EOL, file_get_contents($logPath));

        $line = "sensitive-data:$this->sensitiveData";
        $this->assertTrue(strpos($results[0], $line) !== false);
    }

    /**
     * 測試取得使用者詳細資料但使用者不存在
     */
    public function testGetUserDetailWithUserNotExist()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/user/99/detail');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150090013, $output['code']);
        $this->assertEquals('No such user', $output['msg']);
    }

    /**
     * 測試檢查指定的使用者詳細資料欄位唯一
     */
    public function testCheckUserDetailUnique()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $tester = $em->find('BBDurianBundle:User', 8);
        $tester->setDomain(2);

        $em->flush();

        // check duplicate
        $parameter = [
            'domain' => '2',
            'depth'  => '6',
            'fields' => [
                'email'        => 'Davinci@chinatown.com',
                'nickname'     => 'MJ149',
                'name_real'    => '達文西',
                'name_chinese' => '甲級情報員',
                'name_english' => 'Da Vinci',
                'country'      => 'Republic of China',
                'passport'     => 'PA123456',
                'telephone'    => '3345678',
                'qq_num'       => '485163154787'
            ]
        ];

        $client->request(
            'GET',
            '/api/user_detail/check_unique',
            $parameter,
            array(),
            $this->headerParam
        );

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertFalse($output['ret']['unique']);

        // check unique
        $parameter = array(
            'domain' => '2',
            'depth'  => '6',
            'fields' => array('name_real' => '達文7')
        );

        $client->request(
            'GET',
            '/api/user_detail/check_unique',
            $parameter,
            array(),
            $this->headerParam
        );

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertTrue($output['ret']['unique']);

        // check log file exists
        $logPath = $this->getLogfilePath('sensitive.log');
        $this->assertFileExists($logPath);

        //read log to check content
        $results = explode(PHP_EOL, file_get_contents($logPath));

        $line = "sensitive-data:$this->sensitiveData";
        $this->assertTrue(strpos($results[0], $line) !== false);
    }

    /**
     * 測試檢查指定的使用者真實姓名欄位唯一，會過濾特殊字元
     */
    public function testCheckUserDetailUniqueContainsSpecialCharacter()
    {
        $client = $this->createClient();

        $parameter = [
            'domain' => 2,
            'depth'  => 6,
            'fields' => ['name_real' => '達文西']
        ];

        $client->request('GET', '/api/user_detail/check_unique', $parameter, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertFalse($output['ret']['unique']);

        // check log file exists
        $logPath = $this->getLogfilePath('sensitive.log');
        $this->assertFileExists($logPath);

        //read log to check content
        $results = explode(PHP_EOL, file_get_contents($logPath));

        $line = "sensitive-data:$this->sensitiveData";
        $this->assertTrue(strpos($results[0], $line) !== false);
    }

    /**
     * 測試取得使用者詳細資料列表
     */
    public function testGetDetailList()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        //測試所有參數
        $parameter = [
            'parent_id'      => 7,
            'depth'          => 1,
            'name_real'      => '達文西',
            'nickname'       => 'MJ149',
            'name_chinese'   => '甲級情報員',
            'name_english'   => 'Da Vinci',
            'country'        => 'Republic of China',
            'passport'       => 'PA123456',
            'identity_card'  => '',
            'driver_license' => '',
            'insurance_card' => '',
            'health_card'    => '',
            'telephone'      => '3345678',
            'qq_num'         => '485163154787',
            'note'           => 'Hello Durian',
            'birthday'       => new \DateTime('2000-10-10'),
            'email'          => 'Davinci@chinatown.com',
            'username'       => 'tester',
            'alias'          => 'tester',
            'sub_ret'        => 1,
            'account'        => '3141586254359'
        ];

        $client->request('GET', '/api/user_detail/list', $parameter, array(), $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $detail = $em->find('BBDurianBundle:UserDetail', $output['ret'][0]['user_id']);
        $email = $em->find('BBDurianBundle:UserEmail', $output['ret'][0]['user_id']);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($email->getEmail(), $output['ret'][0]['email']);
        $this->assertEquals($detail->getNickname(), $output['ret'][0]['nickname']);
        $this->assertEquals($detail->getNameReal(), $output['ret'][0]['name_real']);
        $this->assertEquals('3141586254359', $output['ret'][0]['bank'][0]['account']);
        $this->assertEquals('1', $output['pagination']['total']);
        $this->assertNull($output['pagination']['first_result']);
        $this->assertNull($output['pagination']['max_results']);

        $user = $detail->getUser();

        //附屬資訊
        $this->assertEquals($user->getId(), $output['sub_ret']['user'][0]['id']);
        $this->assertEquals($user->getUsername(), $output['sub_ret']['user'][0]['username']);
        $this->assertEquals($user->getAlias(), $output['sub_ret']['user'][0]['alias']);
    }

    /**
     * 測試取得使用者詳細資料列表
     */
    public function testGetDetailListV2()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        //測試所有參數
        $parameter = [
            'parent_id'      => 7,
            'depth'          => 1,
            'name_real'      => '達文西',
            'nickname'       => 'MJ149',
            'name_chinese'   => '甲級情報員',
            'name_english'   => 'Da Vinci',
            'country'        => 'Republic of China',
            'passport'       => 'PA123456',
            'identity_card'  => '',
            'driver_license' => '',
            'insurance_card' => '',
            'health_card'    => '',
            'telephone'      => '3345678',
            'qq_num'         => '485163154787',
            'note'           => 'Hello Durian',
            'birthday'       => new \DateTime('2000-10-10'),
            'email'          => 'Davinci@chinatown.com',
            'username'       => 'tester',
            'alias'          => 'tester',
            'sub_ret'        => 1,
            'account'        => '3141586254359'
        ];

        $client->request('GET', '/api/v2/user_detail/list', $parameter, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $detail = $em->find('BBDurianBundle:UserDetail', $output['ret'][0]['user_id']);
        $email = $em->find('BBDurianBundle:UserEmail', $output['ret'][0]['user_id']);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($email->getEmail(), $output['ret'][0]['email']);
        $this->assertEquals($detail->getNickname(), $output['ret'][0]['nickname']);
        $this->assertEquals($detail->getNameReal(), $output['ret'][0]['name_real']);
        $this->assertEquals(4, $output['ret'][0]['bank'][0]['id']);
        $this->assertEquals(3141586254359, $output['ret'][0]['bank'][0]['account']);

        $user = $detail->getUser();

        //附屬資訊
        $this->assertEquals($user->getId(), $output['sub_ret']['user'][0]['id']);
        $this->assertEquals($user->getUsername(), $output['sub_ret']['user'][0]['username']);
        $this->assertEquals($user->getDomain(), $output['sub_ret']['user'][0]['domain']);
    }

    /**
     * 測試取得指定廳使用者詳細資料列表
     */
    public function testGetDetailListByDomain()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        //測試所有參數
        $parameter = [
            'parent_id'      => 7,
            'depth'          => 1,
            'name_real'      => '達文西',
            'nickname'       => 'MJ149',
            'name_chinese'   => '甲級情報員',
            'name_english'   => 'Da Vinci',
            'country'        => 'Republic of China',
            'passport'       => 'PA123456',
            'identity_card'  => '',
            'driver_license' => '',
            'insurance_card' => '',
            'health_card'    => '',
            'telephone'      => '3345678',
            'qq_num'         => '485163154787',
            'note'           => 'Hello Durian',
            'birthday'       => new \DateTime('2000-10-10'),
            'email'          => 'Davinci@chinatown.com',
            'username'       => 'tester',
            'alias'          => 'tester',
            'sub_ret'        => 1,
            'account'        => '3141586254359'
        ];

        $client->request('GET', '/api/user_detail/list_by_domain', $parameter, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $detail = $em->find('BBDurianBundle:UserDetail', $output['ret'][0]['user_id']);
        $email = $em->find('BBDurianBundle:UserEmail', $output['ret'][0]['user_id']);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($email->getEmail(), $output['ret'][0]['email']);
        $this->assertEquals($detail->getNickname(), $output['ret'][0]['nickname']);
        $this->assertEquals($detail->getNameReal(), $output['ret'][0]['name_real']);
        $this->assertEquals(4, $output['ret'][0]['bank'][0]['id']);
        $this->assertEquals(3141586254359, $output['ret'][0]['bank'][0]['account']);

        $user = $detail->getUser();

        //附屬資訊
        $this->assertEquals($user->getId(), $output['sub_ret']['user'][0]['id']);
        $this->assertEquals($user->getUsername(), $output['sub_ret']['user'][0]['username']);
        $this->assertEquals($user->getDomain(), $output['sub_ret']['user'][0]['domain']);
    }

    /**
     * 測試取得指定廳使用者詳細資料列表，但帶入不存在的上層編號
     */
    public function testGetDetailListByDomainWithNonExistParentId()
    {
        $client = $this->createClient();

        $parameter = ['parent_id' => 1000];
        $client->request('GET', '/api/user_detail/list_by_domain', $parameter, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150090041, $output['code']);
        $this->assertEquals('No parent found', $output['msg']);
    }

    /**
     * 測試修改使用者詳細資料，但真實姓名在黑名單
     */
    public function testEditUserDetailButNameRealInBlackList()
    {
        $client = $this->createClient();

        $parameters = [
            'name_real' => '控端指定廳人工新增黑名單',
            'verify_blacklist' => 1
        ];

        $client->request('PUT', '/api/user/8/detail', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150650017, $output['code']);
        $this->assertEquals('This name_real has been blocked', $output['msg']);

        // 測試帶後綴詞一樣會阻擋
        $parameters = [
            'name_real' => '控端指定廳人工新增黑名單-2',
            'verify_blacklist' => 1
        ];

        $client->request('PUT', '/api/user/8/detail', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150650017, $output['code']);
        $this->assertEquals('This name_real has been blocked', $output['msg']);
    }

    /**
     * 測試修改使用者詳細資料跳過檢查黑名單
     */
    public function testEditUserDetailWithoutCheckBlacklist()
    {
        $client = $this->createClient();

        $parameters = [
            'email' => 'blackemail@tmail.com',
            'verify_blacklist' => 0
        ];

        $client->request('PUT', '/api/user/8/detail', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals('blackemail@tmail.com', $output['ret']['email']);
    }

    /**
     * 修改email，需重新驗證
     */
    public function testEditUserDetailWithConfirmedEmail()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $userEmail = $em->find('BBDurianBundle:UserEmail', 10);
        $this->assertTrue($userEmail->isConfirm());

        $em->clear();

        $parameters = ['email' => 'Davinci10@chinatown.com'];

        $client->request('PUT', '/api/user/10/detail', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(10, $output['ret']['user_id']);
        $this->assertEquals('Davinci10@chinatown.com', $output['ret']['email']);

        $userEmail = $em->find('BBDurianBundle:UserEmail', 10);
        $this->assertEquals('Davinci10@chinatown.com', $userEmail->getEmail());
        $this->assertFalse($userEmail->isConfirm());
        $this->assertNull($userEmail->getConfirmAt());

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertContains('@confirm:true=>false, @confirm_at:2015-03-27 09:12:53=>null', $logOperation->getMessage());
    }

    /**
     * 測試新增推廣資料
     */
    public function testCreatePromotion()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $url = 'hop://promotion.la.com';
        $others = 'hop://theotherspromotion.la.tw';

        $parameters = [
            'domain' => 2,
            'url' => $url,
            'others' => $others
        ];

        $client->request('POST', '/api/user/6/promotion', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(6, $output['ret']['user_id']);
        $this->assertEquals($url, $output['ret']['url']);
        $this->assertEquals($others, $output['ret']['others']);

        $promotion = $em->find('BBDurianBundle:Promotion', 6);
        $this->assertEquals($url, $promotion->getUrl());
        $this->assertEquals($others, $promotion->getOthers());

        $log = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('promotion', $log->getTableName());
        $this->assertEquals('@user_id:6', $log->getMajorKey());
        $this->assertEquals('@url:hop://promotion.la.com, @others:hop://theotherspromotion.la.tw', $log->getMessage());
    }

    /**
     * 測試新增推廣資料但廳與使用者不符
     */
    public function testCreatePromotionButDomainNotMatch()
    {
        $client = $this->createClient();

        $parameters = [
            'domain' => 123,
            'url' => 'hop://promotion.la.com'
        ];

        $client->request('POST', '/api/user/8/promotion', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150090013, $output['code']);
        $this->assertEquals('No such user', $output['msg']);
    }

    /**
     * 測試新增推廣資料但資料已存在
     */
    public function testCreatePromotionButPromotionAlreadyExists()
    {
        $client = $this->createClient();

        $parameters = [
            'domain' => 2,
            'url' => 'hop://promotion.la.com'
        ];

        $client->request('POST', '/api/user/7/promotion', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150090021, $output['code']);
        $this->assertEquals('Promotion for the user already exists', $output['msg']);
    }

    /**
     * 測試同分秒新增推廣資料
     */
    public function testCreatePromotionWithDuplicatedEntry()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->find('BBDurianBundle:User', 8);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'persist', 'flush', 'clear'])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('find')
            ->will($this->onConsecutiveCalls($user, null));

        $pdoExcep = new \PDOException('Duplicate', 23000);
        $pdoExcep->errorInfo[1] = 1062;
        $exception = new \Exception("Duplicate entry '8' for key 'PRIMARY'", 150090022, $pdoExcep);

        $mockEm->expects($this->any())
            ->method('flush')
            ->will($this->throwException($exception));

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);

        $parameters = [
            'domain' => 2,
            'url' => 'hop://promotion.la.com'
        ];

        $client->request('POST', '/api/user/8/promotion', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150090022, $output['code']);
        $this->assertEquals('Database is busy', $output['msg']);
    }

    /**
     * 測試新增推廣資料，flush時丟錯誤訊息
     */
    public function testCreatePromotionWithSomeErrorMessage()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->find('BBDurianBundle:User', 8);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'persist', 'flush', 'clear'])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('find')
            ->will($this->onConsecutiveCalls($user, null));

        $pdoExcep = new \PDOException('failed', 9910);
        $exception = new \Exception('Some error message', 9999, $pdoExcep);

        $mockEm->expects($this->any())
            ->method('flush')
            ->will($this->throwException($exception));

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);

        $parameters = [
            'domain' => 2,
            'url' => 'hop://promotion.la.com'
        ];

        $client->request('POST', '/api/user/8/promotion', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(9999, $output['code']);
        $this->assertEquals('Some error message', $output['msg']);
    }

    /**
     * 測試修改推廣資料
     */
    public function testEditPromotion()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $url = 'poo://123.la.com';
        $others = '';

        $parameters = [
            'domain' => 2,
            'url' => $url,
            'others' => $others
        ];

        $client->request('PUT', '/api/user/7/promotion', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(7, $output['ret']['user_id']);
        $this->assertEquals($url, $output['ret']['url']);
        $this->assertEquals($others, $output['ret']['others']);

        $promotion = $em->find('BBDurianBundle:Promotion', 7);
        $this->assertEquals($url, $promotion->getUrl());
        $this->assertEquals($others, $promotion->getOthers());

        $log = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('promotion', $log->getTableName());
        $this->assertEquals('@user_id:7', $log->getMajorKey());
        $this->assertEquals('@url:http:123=>poo://123.la.com, @others:hoop:456=>', $log->getMessage());
    }

    /**
     * 測試修改推廣資料但廳與使用者不符
     */
    public function testEditPromotionButDomainNotMatch()
    {
        $client = $this->createClient();

        $parameters = [
            'domain' => 123,
            'url' => ''
        ];

        $client->request('PUT', '/api/user/7/promotion', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150090013, $output['code']);
        $this->assertEquals('No such user', $output['msg']);
    }

    /**
     * 測試修改推廣資料但資料不存在
     */
    public function testEditPromotionButNoPromotionFound()
    {
        $client = $this->createClient();

        $parameters = [
            'domain' => 2,
            'url' => ''
        ];

        $client->request('PUT', '/api/user/6/promotion', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150090023, $output['code']);
        $this->assertEquals('No promotion found', $output['msg']);
    }

    /**
     * 測試回傳推廣資料
     */
    public function testGetPromotion()
    {
        $client = $this->createClient();

        $parameters = ['domain' => 2];

        $client->request('GET', '/api/user/7/promotion', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(7, $output['ret']['user_id']);
        $this->assertEquals('http:123', $output['ret']['url']);
        $this->assertEquals('hoop:456', $output['ret']['others']);
    }

    /**
     * 測試回傳推廣資料但廳與使用者不符
     */
    public function testGetPromotionButDomainNotMatch()
    {
        $client = $this->createClient();

        $parameters = ['domain' => 123];

        $client->request('GET', '/api/user/7/promotion', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150090013, $output['code']);
        $this->assertEquals('No such user', $output['msg']);
    }

    /**
     * 測試刪除推廣資料
     */
    public function testDeletePromotion()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = ['domain' => 2];

        $client->request('DELETE', '/api/user/7/promotion', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $promotion = $em->find('BBDurianBundle:Promotion', 7);
        $this->assertNull($promotion);

        $log = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('promotion', $log->getTableName());
        $this->assertEquals('@user_id:7', $log->getMajorKey());
        $this->assertEquals('@promotion:removed', $log->getMessage());
    }

    /**
     * 測試刪除推廣資料但廳與使用者不符
     */
    public function testDeletePromotionButDomainNotMatch()
    {
        $client = $this->createClient();

        $parameters = ['domain' => 123];

        $client->request('DELETE', '/api/user/7/promotion', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150090013, $output['code']);
        $this->assertEquals('No such user', $output['msg']);
    }

    /**
     * 測試刪除推廣資料但資料不存在
     */
    public function testDeletePromotionButNoPromotionFound()
    {
        $client = $this->createClient();

        $parameters = ['domain' => 2];

        $client->request('DELETE', '/api/user/6/promotion', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150090023, $output['code']);
        $this->assertEquals('No promotion found', $output['msg']);
    }

    /**
     * 測試修改使用者參數長度過長
     */
    public function testEditDetailWithInvalidLength()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $userDetail = $em->find('BBDurianBundle:UserDetail', 8);
        $userDetail->setPassport('');
        $em->flush();

        $client = $this->createClient();

        //如果email長度過長
        $parameters = [
            'email' => 'Ra_fael.@china.town.com',
            'nickname' => '柯P',
            'name_real' => '達文西',
            'name_chinese' => '柯柯柯',
            'name_english' => 'professor',
            'country' => 'China',
            'telephone' => '0900234567',
            'qq_num' => '354895477237',
            'password' => '5566',
            'note' => 'Hello Durian',
            'birthday' => '2016-08-31'
        ];

        //測試過長的信箱
        $invalidParameters = $parameters;
        $invalidParameters['email'] = 'invalidinvalidinvalidinvalidinvalidinvalidemail@gmail.com'
            . 'invalidinvalidinvalidinvalidinvalidinvalidemail@gmail.com'
            . 'invalidinvalidinvalidinvalidinvalidinvalidemail@gmail.com'
            . 'invalidinvalidinvalidinvalidinvalidinvalidemail@gmail.com'
            . 'invalidinvalidinvalidinvalidinvalidinvalidemail@gmail.com'
            . 'invalidinvalidinvalidinvalidinvalidinvalidemail@gmail.com';
        $client->request('PUT', '/api/user/8/detail', $invalidParameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010146, $output['code']);
        $this->assertEquals('Invalid email length given', $output['msg']);

        //測試過長的暱稱
        $invalidParameters = $parameters;
        $invalidParameters['nickname'] = '柯PPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPP';
        $client->request('PUT', '/api/user/8/detail', $invalidParameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150090026, $output['code']);
        $this->assertEquals('Invalid nickname length given', $output['msg']);

        //測試過長的真實姓名
        $invalidParameters = $parameters;
        $invalidParameters['name_real'] = '柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯'
            . '柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯'
            . '柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯柯';
        $client->request('PUT', '/api/user/8/detail', $invalidParameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150090027, $output['code']);
        $this->assertEquals('Invalid name_real length given', $output['msg']);

        //測試過長的中文姓名
        $invalidParameters = $parameters;
        $invalidParameters['name_chinese'] = '中文中文中文中文中文中文中文中文中文中文中文-1'
            . '中文中文中文中文中文中文中文中文中文中文中文-1中文中文中文中文中文中文中文中文中文中文中文-1'
            . '中文中文中文中文中文中文中文中文中文中文中文-1';
        $client->request('PUT', '/api/user/8/detail', $invalidParameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150090028, $output['code']);
        $this->assertEquals('Invalid name_chinese length given', $output['msg']);

        //測試過長的英文名字
        $invalidParameters = $parameters;
        $invalidParameters['name_english'] = 'english name english name english name english name ';
        $client->request('PUT', '/api/user/8/detail', $invalidParameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150090029, $output['code']);
        $this->assertEquals('Invalid name_english length given', $output['msg']);

        //測試過長的國籍
        $invalidParameters = $parameters;
        $invalidParameters['country'] = 'english name english name english name english name ';
        $client->request('PUT', '/api/user/8/detail', $invalidParameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150090030, $output['code']);
        $this->assertEquals('Invalid country length given', $output['msg']);

        //測試過長的護照
        $invalidParameters = $parameters;
        $invalidParameters['passport'] = 'passportMPA123456789123456789123456789';
        $client->request('PUT', '/api/user/8/detail', $invalidParameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150090031, $output['code']);
        $this->assertEquals('Invalid passport length given', $output['msg']);

        //測試過長的身分證字號
        $invalidParameters = $parameters;
        $invalidParameters['identity_card'] = 'identity_cardMPA123456789123456789123456789';
        $client->request('PUT', '/api/user/8/detail', $invalidParameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150090032, $output['code']);
        $this->assertEquals('Invalid identity_card length given', $output['msg']);

        //測試過長的駕照號碼
        $invalidParameters = $parameters;
        $invalidParameters['driver_license'] = 'driver_licenseMPA123456789123456789123456789';
        $client->request('PUT', '/api/user/8/detail', $invalidParameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150090033, $output['code']);
        $this->assertEquals('Invalid driver_license length given', $output['msg']);

        //測試過長的保險證字號
        $invalidParameters = $parameters;
        $invalidParameters['insurance_card'] = 'insurance_cardMPA123456789123456789123456789';
        $client->request('PUT', '/api/user/8/detail', $invalidParameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150090034, $output['code']);
        $this->assertEquals('Invalid insurance_card length given', $output['msg']);

        //測試過長的健保卡號碼
        $invalidParameters = $parameters;
        $invalidParameters['health_card'] = 'health_cardMPA123456789123456789123456789';
        $client->request('PUT', '/api/user/8/detail', $invalidParameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150090035, $output['code']);
        $this->assertEquals('Invalid health_card length given', $output['msg']);

        //測試過長的電話號碼
        $invalidParameters = $parameters;
        $invalidParameters['telephone'] = '075677742858136984239';
        $client->request('PUT', '/api/user/8/detail', $invalidParameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150090036, $output['code']);
        $this->assertEquals('Invalid telephone length given', $output['msg']);

        //測試過長的QQ號碼
        $invalidParameters = $parameters;
        $invalidParameters['qq_num'] = '8989955123456789123456789123456789';
        $client->request('PUT', '/api/user/8/detail', $invalidParameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150090037, $output['code']);
        $this->assertEquals('Invalid qq_num length given', $output['msg']);

        //測試過長的密碼
        $invalidParameters = $parameters;
        $invalidParameters['password'] = 'driver_licenseMPA123456789123456789123456789';
        $client->request('PUT', '/api/user/8/detail', $invalidParameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150090038, $output['code']);
        $this->assertEquals('Invalid password length given', $output['msg']);

        //測試過長的備註
        $invalidParameters = $parameters;
        $invalidParameters['note'] = '測試note超過150字元會不會報錯。測試note超過150字元會不會報錯。'
            . '測試note超過150字元會不會報錯。測試note超過150字元會不會報錯。測試note超過150字元會不會報錯。'
            . '測試note超過150字元會不會報錯。測試note超過150字元會不會報錯。測試note超過150字元會不會報錯。'
            . '測試note超過150字元會不會報錯。測試note超過150字元會不會報錯。測試note超過150字元會不會報錯。'
            . '測試note超過150字元會不會報錯。測試note超過150字元會不會報錯。測試note超過150字元會不會報錯。';
        $client->request('PUT', '/api/user/8/detail', $invalidParameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150090039, $output['code']);
        $this->assertEquals('Invalid note length given', $output['msg']);
    }

    /**
     * 測試編輯詳細資料，不合格式的生日日期
     */
    public function testEditDetailInvalidBirthday()
    {
        $client = $this->createClient();

        $parameters = ['birthday' => '2016-05-32'];

        $client->request('PUT', '/api/user/8/detail', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150090025, $output['code']);
        $this->assertEquals('Invalid birthday given', $output['msg']);
    }
}
