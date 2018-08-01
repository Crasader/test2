<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class AuthFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserPasswordData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserEmailData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadEmailVerifyCodeData',
        ];
        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigData'
        ];
        $this->loadFixtures($classnames, 'share');

        $this->clearSensitiveLog();

        //清除post log
        $logPath = $this->getLogfilePath('post.log');

        if (file_exists($logPath)) {
            unlink($logPath);
        }
    }

    /**
     * 測試修改密碼
     */
    public function testSetPwdWithEntityExistInUserPassword()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $sensitiveData = 'entrance=3&operator=test&client_ip=127.0.0.1';
        $sensitiveData .= '&run_php=AuthFunctionsTest.php&operator_id=4&vendor=acc';

        $headerParam = ['HTTP_SENSITIVE_DATA' => $sensitiveData];

        $userPassword = $em->find('BBDurianBundle:UserPassword', 4);
        $verify = password_verify('123456', $userPassword->getHash());
        $this->assertTrue($verify);

        $client = $this->createClient();

        $param = [
            'old_password' => '123456',
            'new_password' => 'aaabbb',
            'confirm_password' => 'aaabbb',
            'password_expire_at' => '2015-11-19 08:44:43'
        ];

        $client->request('PUT', '/api/user/4/password', $param, [],  $headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        //檢查資料庫 user userpassword
        $em->clear();

        $user = $em->find('BBDurianBundle:User', 4);

        $this->assertEquals('aaabbb', $user->getPassword());
        $this->assertEquals('2015-11-19 08:44:43', $user->getPasswordExpireAt()->format('Y-m-d H:i:s'));
        $this->assertFalse($user->isPasswordReset());

        $userPassword = $em->find('BBDurianBundle:UserPassword', 4);

        $verify = password_verify('aaabbb', $userPassword->getHash());
        $this->assertTrue($verify);
        $this->assertEquals('2015-11-19 08:44:43', $userPassword->getExpireAt()->format('Y-m-d H:i:s'));
        $this->assertFalse($userPassword->isReset());

        $log = $emShare->find('BBDurianBundle:LogOperation', 1);

        $this->assertEquals('@password:updated', $log->getMessage());
    }

    /**
     * 測試新密碼與舊密碼相同
     */
    public function testOldPwdSameAsNewPwdWithEntityExistInUserPassword()
    {
        $client = $this->createClient();

        $sensitiveData = 'entrance=3&operator=test&client_ip=127.0.0.1';
        $sensitiveData .= '&run_php=AuthFunctionsTest.php&operator_id=4&vendor=acc';

        $headerParam = ['HTTP_SENSITIVE_DATA' => $sensitiveData];

        $param = [
            'old_password' => '123456',
            'new_password' => '123456',
            'confirm_password' => '123456',
            'password_expire_at' => '2015-11-19 08:44:43'
        ];

        $client->request('PUT', '/api/user/4/password', $param, [],  $headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150390006, $output['code']);
        $this->assertEquals('New password cannot be the same as old password', $output['msg']);
    }

    /**
     * 測試已停用密碼的使用者不能修改密碼
     */
    public function testSetPwdWithDisablePassword()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $sensitiveData = 'entrance=3&operator=test&client_ip=127.0.0.1';
        $sensitiveData .= '&run_php=AuthFunctionsTest.php&operator_id=3&vendor=acc';

        $headerParam = ['HTTP_SENSITIVE_DATA' => $sensitiveData];

        $param = [
            'new_password' => '123456',
            'confirm_password' => '123456',
            'password_expire_at' => '2015-11-19 08:44:43',
            'verify' => 0
        ];

        $userPassword = $em->find('BBDurianBundle:UserPassword', 3);
        $userPassword->setHash('');

        $em->flush();

        // UsePassword的Hash欄位為空
        $client->request('PUT', '/api/user/3/password', $param, [],  $headerParam);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150390015, $output['code']);
        $this->assertEquals('DisabledPassword user cannot change password', $output['msg']);
    }

    /**
     * 測試舊密碼輸入錯誤會跳例外
     */
    public function testSetPwdWithWrongOldPwdByEntityExistInUserPassword()
    {
        $sensitiveData = 'entrance=3&operator=test&client_ip=127.0.0.1';
        $sensitiveData .= '&run_php=AuthFunctionsTest.php&operator_id=4&vendor=acc';

        $headerParam = ['HTTP_SENSITIVE_DATA' => $sensitiveData];

        $client = $this->createClient();

        $param = [
            'old_password' => '0000000000',
            'new_password' => 'aaabbb',
            'confirm_password' => 'aaabbb',
            'password_expire_at' => '2015-11-19 08:44:43'
        ];

        $client->request('PUT', '/api/user/4/password', $param, [], $headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150390005, $output['code']);
        $this->assertEquals('Old password is not correct', $output['msg']);
    }

    /**
     * 測試不驗證舊密碼
     */
    public function testSetPwdWithNewPwdSameAsOldPw()
    {
        $sensitiveData = 'entrance=1&operator=test&client_ip=127.0.0.1';
        $sensitiveData .= '&run_php=AuthFunctionsTest.php&operator_id=4&vendor=acc';

        $headerParam = ['HTTP_SENSITIVE_DATA' => $sensitiveData];

        $client = $this->createClient();

        $param = [
            'new_password' => '123456a',
            'confirm_password' => '123456a',
            'password_expire_at' => '2015-11-19 08:44:43',
            'verify' => 0
        ];

        $client->request('PUT', '/api/user/4/password', $param, [], $headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
    }

    /**
     * 測試敏感資料錯誤會跳例外
     */
    public function testWrongSensitiveData()
    {
        $sensitiveData = 'entrance=3&operator=test&client_ip=127.0.0.1';
        $sensitiveData .= '&run_php=AuthFunctionsTest.php&operator_id=1&vendor=acc';

        $headerParam = ['HTTP_SENSITIVE_DATA' => $sensitiveData];

        $client = $this->createClient();

        $param = [
            'old_password' => '12345',
            'new_password' => '123456',
            'confirm_password' => '123456',
            'password_expire_at' => '2015-11-19 08:44:43'
        ];

        $client->request('PUT', '/api/user/4/password', $param, [], $headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150240005, $output['code']);
        $this->assertEquals('The request not allowed when operator_id is invalid', $output['msg']);
    }

    /**
     * 測試非正確使用者會跳例外
     */
    public function testWrongUser()
    {
        $sensitiveData = 'entrance=1&operator=test&client_ip=127.0.0.1';
        $sensitiveData .= '&run_php=AuthFunctionsTest.php&operator_id=999&vendor=acc';

        $headerParam = ['HTTP_SENSITIVE_DATA' => $sensitiveData];

        $client = $this->createClient();

        $param = [
            'old_password' => '12345',
            'new_password' => '123456',
            'confirm_password' => '123456',
            'password_expire_at' => '2015-11-19 08:44:43'
        ];

        $client->request('PUT', '/api/user/999/password', $param, [], $headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150390008, $output['code']);
        $this->assertEquals('No such user', $output['msg']);
    }

    /**
     * 測試post log中有密碼部分有遮罩
     */
    public function testMaskPasswordOnPostLog()
    {
        $sensitiveData = 'entrance=3&operator=test&client_ip=127.0.0.1';
        $sensitiveData .= '&run_php=AuthFunctionsTest.php&operator_id=3&vendor=acc';

        $headerParam = ['HTTP_SENSITIVE_DATA' => $sensitiveData];

        $client = $this->createClient();

        $param = [
            'old_password' => '123456',
            'new_password' => 'aaabbb',
            'confirm_password' => 'aaabbb',
            'password_expire_at' => '2015-11-19 08:44:43'
        ];

        $client->request('PUT', '/api/user/3/password', $param, [], $headerParam);

        //檢查post log密碼是否被遮罩
        $logPath = $this->getLogfilePath('post.log');
        $results = explode(PHP_EOL, file_get_contents($logPath));
        $this->assertContains(
            'REQUEST: old_password=******&new_password=******&confirm_password=******&',
            $results[0]
        );
    }

    /**
     * 測試產生email認證碼
     */
    public function testGenerateVerifyCode()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $client->request('POST', '/api/user/8/email_verify_code');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $emailVerify = $em->find('BBDurianBundle:EmailVerifyCode', 8);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($emailVerify->getCode(), $output['code']);

        //檢查操作紀錄
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('email_verify_code', $logOp->getTableName());
        $this->assertEquals('@user_id:8', $logOp->getMajorKey());
        $this->assertContains('@code:generated, @expire_at:', $logOp->getMessage());
    }

    /**
     * 測試產生email認證碼，若已存在會覆寫認證碼
     */
    public function testGenerateVerifyCodeIfExistsThenOverwriteCode()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $emailVerify = $em->find('BBDurianBundle:EmailVerifyCode', 8);
        $code = $emailVerify->getCode();
        $em->clear();

        $client->request('POST', '/api/user/8/email_verify_code');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertNotEquals($code, $output['code']);

        $emailVerify = $em->find('BBDurianBundle:EmailVerifyCode', 8);
        $this->assertNotEquals($code, $emailVerify->getCode());
    }

    /**
     * 測試產生email認證編碼，但找不到使用者
     */
    public function testGenerateVerifyCodeButNoSuchUser()
    {
        $client = $this->createClient();

        $client->request('POST', '/api/user/100/email_verify_code');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150390008, $output['code']);
        $this->assertEquals('No such user', $output['msg']);
    }

    /**
     * 測試產生email認證編碼，但email欄位為空
     */
    public function testGenerateVerifyCodeButUserEmailIsNull()
    {
        $client = $this->createClient();

        $client->request('POST', '/api/user/7/email_verify_code');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150390016, $output['code']);
        $this->assertEquals('Email can not be null', $output['msg']);
    }

    /**
     * 測試產生email認證編碼，但信箱已認證
     */
    public function testGenerateVerifyCodeButAlreadyConfirmed()
    {
        $client = $this->createClient();

        $client->request('POST', '/api/user/10/email_verify_code');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150390010, $output['code']);
        $this->assertEquals('This email has been confirmed', $output['msg']);
    }

    /**
     * 測試同分秒產生email認證碼
     */
    public function testGenerateVerifyCodeWithDuplicateEntry()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $mockUser = $this->getMockBuilder('BB\DurianBundle\Entity\User');
        $email = $em->find('BBDurianBundle:UserEmail', 8);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'persist', 'flush', 'rollback', 'clear'])
            ->getMock();

        $mockEm->expects($this->at(0))
            ->method('find')
            ->willReturn($mockUser);

        $mockEm->expects($this->at(1))
            ->method('find')
            ->will($this->onConsecutiveCalls($email));

        $pdoExcep = new \PDOException('Duplicate', 23000);
        $pdoExcep->errorInfo[1] = 1062;
        $exception = new \Exception("PRIMARY KEY must be unique", 23000, $pdoExcep);

        $mockEm->expects($this->any())
            ->method('flush')
            ->will($this->throwException($exception));

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);

        $client->request('POST', '/api/user/8/email_verify_code');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150390011, $output['code']);
        $this->assertEquals('Database is busy', $output['msg']);
    }

    /**
     * 測試產生email認證碼，flush時丟錯誤訊息
     */
    public function testGenerateVerifyCodeWithSomeErrorMessage()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $mockUser = $this->getMockBuilder('BB\DurianBundle\Entity\User');
        $email = $em->find('BBDurianBundle:UserEmail', 8);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'persist', 'flush', 'rollback', 'clear'])
            ->getMock();

        $mockEm->expects($this->at(0))
            ->method('find')
            ->willReturn($mockUser);

        $mockEm->expects($this->at(1))
            ->method('find')
            ->will($this->onConsecutiveCalls($email));

        $pdoExcep = new \PDOException('failed', 9910);
        $exception = new \Exception('Some error message', 9999, $pdoExcep);

        $mockEm->expects($this->any())
            ->method('flush')
            ->will($this->throwException($exception));

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);

        $client->request('POST', '/api/user/8/email_verify_code');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Some error message', $output['msg']);
    }

    /**
     * 測試email認證
     */
    public function testVerifyEmail()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        //先產生編碼
        $client->request('POST', '/api/user/8/email_verify_code');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $param = ['code' => $output['code']];

        //驗證編碼
        $client->request('PUT', '/api/user/email/verify', $param);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $email = $em->find('BBDurianBundle:UserEmail', 8);
        $this->assertNotNull($email->getConfirmAt());
        $this->assertTrue($email->isConfirm());

        //檢查操作紀錄
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('user_email', $logOp->getTableName());
        $this->assertEquals('@user_id:8', $logOp->getMajorKey());
        $this->assertContains('@confirm:false=>true, @confirm_at', $logOp->getMessage());
    }

    /**
     * 測試email認證，但帶入不存在的認證碼
     */
    public function testVerifyEmailButCodeNotExists()
    {
        $client = $this->createClient();

        $param = ['code' => 'abcd123'];

        $client->request('PUT', '/api/user/email/verify', $param);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150390013, $output['code']);
        $this->assertEquals('No emailVerifyCode found', $output['msg']);
    }

    /**
     * 測試email認證但超過時效
     */
    public function testVerifyEmailButOutOfDate()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $emailVerify = $em->find('BBDurianBundle:EmailVerifyCode', 8);

        $param = ['code' => $emailVerify->getCode()];

        $client->request('PUT', '/api/user/email/verify', $param);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150390013, $output['code']);
        $this->assertEquals('No emailVerifyCode found', $output['msg']);
    }

    /**
     * 測試email認證，但認證碼對應的使用者已被刪除
     */
    public function testVerifyEmailButUserIsRemoved()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $emailVerify = $em->find('BBDurianBundle:EmailVerifyCode', 8);
        $code = $emailVerify->getCode();
        $emailVerify->setExpireAt(new \DateTime('+1 day'));

        $user = $em->find('BBDurianBundle:User', 8);
        $em->remove($user);
        $em->flush();

        $param = ['code' => $code];

        $client->request('PUT', '/api/user/email/verify', $param);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150390008, $output['code']);
        $this->assertEquals('No such user', $output['msg']);
    }

    /**
     * 測試email認證但信箱已認證
     */
    public function testVerifyEmailButAlreadyConfirmed()
    {
        $client = $this->createClient();

        $client->request('POST', '/api/user/8/email_verify_code');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $param = ['code' => $output['code']];

        $client->request('PUT', '/api/user/email/verify', $param);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        //測試在時效內驗證兩次
        $client->request('PUT', '/api/user/email/verify', $param);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150390010, $output['code']);
        $this->assertEquals('This email has been confirmed', $output['msg']);
    }

    /**
     * 測試email認證，但認證碼對應的使用者信箱不相符
     */
    public function testVerifyEmailButMapToDifferentUserEmail()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $client->request('POST', '/api/user/8/email_verify_code');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $code = $output['code'];

        $userEmail = $em->find('BBDurianBundle:UserEmail', 8);
        $userEmail->setEmail('8derEmail@gg.com');
        $em->flush();

        $param = ['code' => $code];

        $client->request('PUT', '/api/user/email/verify', $param);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150390014, $output['code']);
        $this->assertEquals('Failed to verify email', $output['msg']);
    }

    /**
     * 測試新增臨時密碼
     */
    public function testCreateOncePassword()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $client = $this->createClient();

        $at = new \DateTime('now');

        $client->request('PUT', '/api/user/8/once_password', ['operator' => 'angelabobi']);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $oncePassword = $output['code'];

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, strlen($oncePassword));

        $userPassword = $em->find('BBDurianBundle:UserPassword', 8);
        $hash = $userPassword->getOncePassword();
        $expireAt = $userPassword->getOnceExpireAt();

        $this->assertTrue(password_verify($oncePassword, $hash));
        $this->assertFalse($userPassword->isUsed());
        $this->assertGreaterThan($at, $expireAt);

        //檢查操作紀錄
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('user_password', $logOp->getTableName());
        $this->assertEquals('@user_id:8', $logOp->getMajorKey());
        $this->assertEquals(
            '@once_password:created, @used:false, @once_expire_at:' . $expireAt->format('Y-m-d H:i:s'),
            $logOp->getMessage()
        );

        $key = 'italking_message_queue';
        $msg = '廳名: domain2，使用者帳號: tester 已產生臨時密碼，操作者為: angelabobi，建立時間: ';

        $queueMsg = json_decode($redis->rpop($key), true);

        $this->assertEquals('acc_system', $queueMsg['type']);
        $this->assertContains($msg, $queueMsg['message']);
    }
}
