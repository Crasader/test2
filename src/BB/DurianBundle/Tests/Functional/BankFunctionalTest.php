<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\Bank;
use BB\DurianBundle\Entity\UserAncestor;

class BankFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainWithdrawBankCurrencyData',
        ];

        $this->loadFixtures($classnames);
        $this->loadFixtures([], 'share');

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBlacklistData'
        ];

        $this->loadFixtures($classnames, 'share');

        $this->clearSensitiveLog();
        $this->clearPaymentOperationLog();

        $sensitiveData = 'entrance=6&operator=test&client_ip=127.0.0.1';
        $sensitiveData .= '&run_php=BankFunctionsTest.php&operator_id=&vendor=acc';

        $this->sensitiveData = $sensitiveData;
        $this->headerParam = array('HTTP_SENSITIVE_DATA' => $sensitiveData);
    }

    /**
     * 取得銀行資料
     */
    public function testGetBank()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $client->request('GET', '/api/bank/1', array(), array(), $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $bank = $em->find('BB\DurianBundle\Entity\Bank', 1);
        $this->assertEquals($bank->getId(), $output['ret']['id']);
        $this->assertEquals($bank->getCode(), $output['ret']['code']);
        $this->assertEquals($bank->getAccount(), $output['ret']['account']);
        $this->assertEquals($bank->getStatus(), $output['ret']['status']);
        $this->assertEquals($bank->getProvince(), $output['ret']['province']);
        $this->assertEquals($bank->getCity(), $output['ret']['city']);

        // check log file exists
        $logPath = $this->getLogfilePath('sensitive.log');
        $this->assertFileExists($logPath);

        //read log to check content
        $results = explode(PHP_EOL, file_get_contents($logPath));

        $line = "sensitive-data:$this->sensitiveData";
        $this->assertTrue(strpos($results[0], $line) !== false);
    }

    /**
     * 要取得的銀行資料不存在
     */
    public function testGetBankWithBankNotExist()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/bank/999');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('120004', $output['code']);
        $this->assertEquals('No Bank found', $output['msg']);
    }

    /**
     * 新增銀行資料
     */
    public function testNewBank()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = [
            'code'     => '2',
            'account'  => 'iloveN55- 66',
            'province' => 'asia',
            'city'     => 'taiwan',
            'mobile'   => true,
            'branch' => '北京学院路'
        ];

        $client->request('POST', '/api/user/8/bank', $parameters, array(), $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 操作紀錄檢查
        $msg = '@code:2, @account:iloveN55- 66, @province:asia, @city:taiwan, @mobile:true, @branch:北京学院路, @account_holder:';
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals("bank", $logOperation->getTableName());
        $this->assertEquals("@user_id:8", $logOperation->getMajorKey());
        $this->assertEquals($msg, $logOperation->getMessage());

        // 銀行資訊檢查
        $bank = $em->find('BBDurianBundle:Bank', $output['ret']['id']);

        $this->assertEquals(8, $bank->getUser()->getId());
        $this->assertEquals('2', $bank->getCode());
        $this->assertEquals('iloveN55- 66', $bank->getAccount());
        $this->assertEquals(Bank::IN_USE, $bank->getStatus());
        $this->assertEquals('asia', $bank->getProvince());
        $this->assertEquals('taiwan', $bank->getCity());
        $this->assertEquals('北京学院路', $bank->getBranch());
        $this->assertTrue($bank->isMobile());

        // check log file exists
        $logPath = $this->getLogfilePath('sensitive.log');
        $this->assertFileExists($logPath);

        //read log to check content
        $results = explode(PHP_EOL, file_get_contents($logPath));

        $line = "sensitive-data:$this->sensitiveData";
        $this->assertTrue(strpos($results[0], $line) !== false);

        // 檢查金流操作紀錄
        $paymentOperationLogPath = $this->getLogfilePath('payment_operation.log');
        $this->assertFileExists($paymentOperationLogPath);

        $paymentOperationLog = explode(PHP_EOL, file_get_contents($paymentOperationLogPath));
        $this->assertContains('result=ok', $paymentOperationLog[0]);
    }

    /**
     * 測試新增使用者銀行資訊輸入非UTF8
     */
    public function testNewBankInputNotUtf8()
    {
        $client = $this->createClient();

        $parameters = array(
            'code'     => '56',
            'account'  => 'ilove55-66',
            'province' =>  mb_convert_encoding('亞洲', 'GB2312', 'UTF-8'),
            'city'     => 'taiwan'
        );

        $client->request('POST', '/api/user/8/bank', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150610002, $output['code']);
        $this->assertEquals('String must use utf-8 encoding', $output['msg']);
    }

    /**
     * 新增銀行帳號有特殊字元
     */
    public function testNewBankWithSpecialAccount()
    {
        $client = $this->createClient();

        $parameters = array(
            'code'     => '56',
            'account'  => '卍!ilove5566#卍',
            'province' => 'asia',
            'city'     => 'taiwan'
            );

        $client->request('POST', '/api/user/8/bank', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('120006', $output['code']);
        $this->assertEquals('Illegal Bank account', $output['msg']);
    }

    /**
     * 測試新增銀行帳號但帳號在黑名單
     */
    public function testNewBankWithBlackListAccount()
    {
        $client = $this->createClient();

        $parameters = [
            'code' => '88',
            'account' => 'blackbank123'
        ];

        $client->request('POST', '/api/user/8/bank', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150650015, $output['code']);
        $this->assertEquals('This account has been blocked', $output['msg']);
    }

    /**
     * 測試新增銀行帳號，帳號在黑名單但不驗證黑名單
     */
    public function testNewBankWithBlackAccountButNotToVerifyBlackList()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $parameters = [
            'code' => '2',
            'account' => 'blackbank123',
            'verify_blacklist' => 0
        ];

        $client->request('POST', '/api/user/8/bank', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 銀行資訊檢查
        $bank = $em->find('BBDurianBundle:Bank', $output['ret']['id']);
        $this->assertEquals(8, $bank->getUser()->getId());
        $this->assertEquals('2', $bank->getCode());
        $this->assertEquals('blackbank123', $bank->getAccount());
    }

    /**
     * 新增銀行資料不傳account
     */
    public function testNewBankWithoutAccount()
    {
        $client = $this->createClient();

        // 不傳account
        $parameters = array('code' => 56);
        $client->request('POST', '/api/user/8/bank', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('120003', $output['code']);
        $this->assertEquals('No account specified', $output['msg']);

        // account傳空字串
        $parameters = array('account' => '');
        $client->request('POST', '/api/user/8/bank', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('120003', $output['code']);
        $this->assertEquals('No account specified', $output['msg']);

        // account傳空白
        $parameters = array('account' => ' ');
        $client->request('POST', '/api/user/8/bank', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('120003', $output['code']);
        $this->assertEquals('No account specified', $output['msg']);
    }

    /**
     * 新增銀行資料不傳code
     */
    public function testNewBankWithoutCode()
    {
        $client = $this->createClient();

        // 不傳code
        $parameters = array('account' => 'ilove5566');
        $client->request('POST', '/api/user/8/bank', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('120002', $output['code']);
        $this->assertEquals('No code specified', $output['msg']);

        // code傳空字串
        $parameters = array(
            'account' => 'ilove5566',
            'code'    => ''
            );

        $client->request('POST', '/api/user/8/bank', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('120002', $output['code']);
        $this->assertEquals('No code specified', $output['msg']);
    }

    /**
     * 新增銀行資料不傳其他參數
     */
    public function testNewBankWithoutOtherParameter()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = [
            'code' => '2',
            'account' => 'ilove5566',
        ];

        $client->request('POST', '/api/user/8/bank', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $bank = $em->find('BBDurianBundle:Bank', $output['ret']['id']);

        $this->assertEquals(8, $bank->getUser()->getId());
        $this->assertEquals('', $bank->getProvince());
        $this->assertEquals('', $bank->getCity());
    }

    /**
     * 新增重複的銀行資料(in same domain)
     */
    public function testNewDplicateBank()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $user = $em->find('BB\DurianBundle\Entity\User', 8);
        $user->setDomain(2);
        $em->flush();

        $parameters = [
            'code' => '2',
            'account' => '6221386170003601228',
        ];

        $client->request('POST', '/api/user/8/bank', $parameters);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEmpty($logOperation);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('120001', $output['code']);
        $this->assertEquals('This account already been used', $output['msg']);
    }

    /**
     * 測試同分秒重覆銀行帳號
     */
    public function testCreateBankWithDuplicatedEntry()
    {
        $client = $this->createClient();

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->find('BBDurianBundle:User', 6);
        $domainWithdrawBankCurrency = $em->find('BBDurianBundle:DomainWithdrawBankCurrency', 2);

        $repo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['getByDomain', 'getByLevel'])
            ->getMock();

        $repo->expects($this->at(0))
            ->method('getByDomain')
            ->will($this->returnValue([$domainWithdrawBankCurrency]));

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['beginTransaction', 'find', 'getRepository', 'persist', 'flush', 'rollback', 'clear'])
            ->getMock();

        $mockEm->expects($this->at(0))
            ->method('find')
            ->will($this->returnValue($user));

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($repo);

        $pdoExcep = new \PDOException('Duplicate', 23000);
        $pdoExcep->errorInfo[1] = 1062;
        $exception = new \Exception("Duplicate entry '6-56-ilove5566' for key 'uni_user_id_code_account'", 120009, $pdoExcep);

        $mockEm->expects($this->any())
            ->method('flush')
            ->will($this->throwException($exception));

        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);

        $parameters = [
            'code' => '56',
            'account' => 'ilove5566'
        ];

        $client->request('POST', '/api/user/8/bank', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(120009, $output['code']);
        $this->assertEquals('Database is busy', $output['msg']);
    }

    /**
     * 修改銀行資訊
     */
    public function testEditBank()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $user = $em->find('BB\DurianBundle\Entity\User', 8);

        $code = 27;
        $oldAccount = '62_8?3d5d4716#0*';
        $newAccount = 'C1431- 4595-5449-8425-97226';
        $bank = new Bank($user);
        $bank->setCode($code)->setAccount($oldAccount);

        $em->persist($bank);
        $em->flush();
        $em->clear();

        $parameters = [
            'old_account' => $oldAccount,
            'new_account' => $newAccount,
            'code'        => '2',
            'status'      => Bank::USED,
            'province'    => 'asia',
            'city'        => 'taiwan',
            'mobile'      => 1,
            'branch' => '北京学院路',
        ];

        $client->request('PUT', '/api/user/8/bank', $parameters, array(), $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals("bank", $logOperation->getTableName());
        $this->assertEquals("@user_id:8", $logOperation->getMajorKey());
        $msg = "@account:62_8?3d5d4716#0*=>C1431- 4595-5449-8425-97226, ".
               "@code:27=>2, ".
               "@status:1=>2, ".
               "@province:=>asia, ".
               "@city:=>taiwan, " .
               "@mobile:false=>true, ".
               "@branch:=>北京学院路";
        $this->assertEquals($msg, $logOperation->getMessage());

        $em->clear();

        // 銀行資訊檢查
        $criteria['user'] = 8;
        $criteria['account'] = $newAccount;
        $banks = $em->getRepository('BBDurianBundle:Bank')
                    ->findBy($criteria);

        $this->assertEquals($banks[0]->getId(), $output['ret']['id']);
        $this->assertEquals('2', $banks[0]->getCode());
        $this->assertEquals(Bank::USED, $banks[0]->getStatus());
        $this->assertEquals('asia', $banks[0]->getProvince());
        $this->assertEquals('taiwan', $banks[0]->getCity());
        $this->assertEquals('北京学院路', $banks[0]->getBranch());

        // check log file exists
        $logPath = $this->getLogfilePath('sensitive.log');
        $this->assertFileExists($logPath);

        //read log to check content
        $results = explode(PHP_EOL, file_get_contents($logPath));

        $string = "sensitive-data:$this->sensitiveData";
        $this->assertTrue(strpos($results[0], $string) !== false);

        // 檢查金流操作紀錄
        $paymentOperationLogPath = $this->getLogfilePath('payment_operation.log');
        $this->assertFileExists($paymentOperationLogPath);

        $paymentOperationLog = explode(PHP_EOL, file_get_contents($paymentOperationLogPath));
        $this->assertContains('result=ok', $paymentOperationLog[0]);
    }

    /**
     * 帶入bank_id修改銀行資訊
     */
    public function testEditBankById()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $user = $em->find('BB\DurianBundle\Entity\User', 8);

        $code = 27;
        $oldAccount = '62_8?3d5d4716#0*';
        $newAccount = 'C1431- 4595-5449-8425-97226';
        $bank = new Bank($user);
        $bank->setCode($code)->setAccount($oldAccount);

        $em->persist($bank);
        $em->flush();
        $em->refresh($bank);
        $em->clear();

        $parameters = [
            'bank_id' => $bank->getId(),
            'new_account' => $newAccount,
            'code' => '2',
            'status' => Bank::USED,
            'province' => 'asia',
            'city' => 'taiwan',
            'mobile' => 1,
            'branch' => '北京学院路',
        ];

        $client->request('PUT', '/api/user/8/bank', $parameters, array(), $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals("bank", $logOperation->getTableName());
        $this->assertEquals("@user_id:8", $logOperation->getMajorKey());
        $msg = "@account:62_8?3d5d4716#0*=>C1431- 4595-5449-8425-97226, ".
               "@code:27=>2, ".
               "@status:1=>2, ".
               "@province:=>asia, ".
               "@city:=>taiwan, " .
               "@mobile:false=>true, ".
               "@branch:=>北京学院路";
        $this->assertEquals($msg, $logOperation->getMessage());

        $em->clear();

        // 銀行資訊檢查
        $criteria['user'] = 8;
        $criteria['account'] = $newAccount;
        $banks = $em->getRepository('BBDurianBundle:Bank')
                    ->findBy($criteria);

        $this->assertEquals($banks[0]->getId(), $output['ret']['id']);
        $this->assertEquals('2', $banks[0]->getCode());
        $this->assertEquals(Bank::USED, $banks[0]->getStatus());
        $this->assertEquals('asia', $banks[0]->getProvince());
        $this->assertEquals('taiwan', $banks[0]->getCity());
        $this->assertEquals('北京学院路', $banks[0]->getBranch());

        // check log file exists
        $logPath = $this->getLogfilePath('sensitive.log');
        $this->assertFileExists($logPath);

        //read log to check content
        $results = explode(PHP_EOL, file_get_contents($logPath));

        $string = "sensitive-data:$this->sensitiveData";
        $this->assertTrue(strpos($results[0], $string) !== false);

        // 檢查金流操作紀錄
        $paymentOperationLogPath = $this->getLogfilePath('payment_operation.log');
        $this->assertFileExists($paymentOperationLogPath);

        $paymentOperationLog = explode(PHP_EOL, file_get_contents($paymentOperationLogPath));
        $this->assertContains('result=ok', $paymentOperationLog[0]);
    }

    /**
     * 僅修改銀行狀態
     */
    public function testEditBankStatus()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BB\DurianBundle\Entity\User', 8);

        $oldAccount = '62_8?3d5d4716#0*';
        $bank = new Bank($user);
        $bank->setAccount($oldAccount);

        $em->persist($bank);
        $em->flush();
        $em->clear();

        $parameters = array(
            'old_account' => $oldAccount,
            'status'      => Bank::USED,
        );

        $client->request('PUT', '/api/user/8/bank', $parameters, array(), $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
    }

    /**
     * 測試修改銀行狀態flush時丟錯誤訊息
     */
    public function testEditBankWithWithSomeErrorMessage()
    {
        $client = $this->createClient();

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->find('BBDurianBundle:User', 8);

        $oldAccount = '62_8?3d5d4716#0*';
        $bank = new Bank($user);
        $bank->setAccount($oldAccount);
        $em->persist($bank);
        $em->flush();

        $banks[0] = $bank;

        $repo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findBy'])
            ->getMock();

        $repo->expects($this->any())
            ->method('findBy')
            ->willReturn($banks);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['beginTransaction', 'find', 'getRepository', 'persist', 'flush', 'rollback', 'clear'])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('find')
            ->will($this->returnValue($user));

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($repo);

        $pdoExcep = new \PDOException('Duplicate', 23000);
        $pdoExcep->errorInfo[1] = 1062;
        $exception = new \Exception("Duplicate entry '6-56-ilove5566' for key 'uni_user_id_code_account'", 120009, $pdoExcep);

        $mockEm->expects($this->any())
            ->method('flush')
            ->will($this->throwException($exception));

        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);

        $parameters = [
            'old_account' => $oldAccount,
            'status' => Bank::USED
        ];

        $client->request('PUT', '/api/user/8/bank', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(120009, $output['code']);
        $this->assertEquals('Database is busy', $output['msg']);
    }

    /**
     * 測試修改銀行狀態帶入非法狀態
     */
    public function testEditBankStatusWitStatusInvalid()
    {
        $client = $this->createClient();

        $parameters = [
            'old_account' => 3141586254359,
            'status'      => 3
        ];

        $client->request('PUT', '/api/user/8/bank', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Invalid bank status', $output['msg']);
        $this->assertEquals('120008', $output['code']);
    }

    /**
     * 測試編輯使用者銀行資訊輸入非UTF8
     */
    public function testEditBankInputNotUtf8()
    {
        $client = $this->createClient();

        $parameters = array(
            'old_account' => '6221386170003601228',
            'new_account' => '12345678987654321',
            'province'    => mb_convert_encoding('亞洲', 'GB2312', 'UTF-8'),
            'city'        => '台灣'
        );

        $client->request('PUT', '/api/user/8/bank', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150610002, $output['code']);
        $this->assertEquals('String must use utf-8 encoding', $output['msg']);

        $parameters = array(
            'old_account' => '6221386170003601228',
            'new_account' => '12345678987654321',
            'province'    => '亞洲',
            'city'        => mb_convert_encoding('台中', 'GB2312', 'UTF-8')
        );

        $client->request('PUT', '/api/user/8/bank', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150610002, $output['code']);
        $this->assertEquals('String must use utf-8 encoding', $output['msg']);
    }

    /**
     * 修改銀行資訊時缺少帳號
     */
    public function testEditBankWithoutAccount()
    {
        $client = $this->createClient();

        // no account
        $parameters = array(
            'new_account' => '4561357946423156',
            'status'      => Bank::USED,
            'province'    => 'asia',
            'city'        => 'taiwan'
            );

        $client->request('PUT', '/api/user/8/bank', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 操作紀錄檢查
        $logOperation = $this->getContainer()
            ->get('doctrine.orm.share_entity_manager')
            ->find('BBDurianBundle:LogOperation', 1);
        $this->assertEmpty($logOperation);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('120003', $output['code']);
        $this->assertEquals('No account specified', $output['msg']);

        // account not exit
        $parameters = array(
            'old_account' => '658568',
            'status'      => Bank::USED,
            'province'    => 'asia',
            'city'        => 'taiwan'
            );

        $client->request('PUT', '/api/user/8/bank', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('120004', $output['code']);
        $this->assertEquals('No Bank found', $output['msg']);
    }

    /**
     * 修改銀行資訊帶入非法的帳號參數
     */
    public function testEditBankWitAccountInvalid()
    {
        $client = $this->createClient();

        $parameters = array(
            'old_account' => '6221386170003601228',
            'new_account' => 'ddf41d8s697\'^86fd7s54f6ds',
        );

        $client->request('PUT', '/api/user/8/bank', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Illegal Bank account', $output['msg']);
        $this->assertEquals('120006', $output['code']);
    }

    /**
     * 修改銀行資訊帶入在黑名單的銀行帳號
     */
    public function testEditBankWithBlackListAccount()
    {
        $client = $this->createClient();

        $parameters = [
            'old_account' => '6221386170003601228',
            'new_account' => 'blackbank123',
        ];

        $client->request('PUT', '/api/user/8/bank', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150650015, $output['code']);
        $this->assertEquals('This account has been blocked', $output['msg']);
    }

    /**
     * 修改銀行資訊帶入在黑名單的銀行帳號但不驗證黑名單
     */
    public function testEditBankWithBlackAccountButNotToVerifyBlackList()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = [
            'old_account' => '6221386170003601228',
            'new_account' => 'blackbank123',
            'verify_blacklist' => 0
        ];

        $client->request('PUT', '/api/user/8/bank', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 銀行帳號檢查
        $banks = $em->find('BBDurianBundle:Bank', $output['ret']['id']);
        $this->assertEquals(8, $banks->getUser()->getId());
        $this->assertEquals('blackbank123', $banks->getAccount());
    }

    /**
     * 測試修改銀行帳號前後有空白字元
     */
    public function testEditBankAccountSpace()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $user = $em->find('BBDurianBundle:User', 8);

        $oldAccount = '  55556666  ';
        $newAccount = '  123456789 ';

        $bank = new Bank($user);
        $bank->setAccount($oldAccount);
        $em->persist($bank);
        $em->flush();

        $parameters = [
            'old_account' => $oldAccount,
            'new_account' => $newAccount,
        ];

        $client->request('PUT', '/api/user/8/bank', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('123456789', $output['ret']['account']);

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals("bank", $logOperation->getTableName());
        $this->assertEquals("@user_id:8", $logOperation->getMajorKey());
        $this->assertEquals('@account:  55556666  =>123456789', $logOperation->getMessage());
    }

    /**
     * 修改銀行資料account傳入空字串
     */
    public function testEditBankWithEmptyAccount()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // account傳空字串
        $parameters = array(
            'old_account' => '6221386170003601228',
            'new_account' => '',
        );
        $client->request('PUT', '/api/user/8/bank', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('No account specified', $output['msg']);
        $this->assertEquals('120003', $output['code']);

        // 實際撈DB檢查
        $user = $em->find('BB\DurianBundle\Entity\User', 8);

        $criteria = [
            'user' => $user,
            'account' => '6221386170003601228'
        ];
        $banks = $em->getRepository('BBDurianBundle:Bank')->findBy($criteria);
        $this->assertEquals('6221386170003601228', $banks[0]->getAccount());

        // account傳空白
        $parameters = array(
            'old_account' => '6221386170003601228',
            'new_account' => ' ',
        );
        $client->request('POST', '/api/user/8/bank', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('120003', $output['code']);
        $this->assertEquals('No account specified', $output['msg']);
    }

    /**
     * 修改銀行資訊帶入重複的帳號參數
     */
    public function testEditBankWithAccountAlreadyExist()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user2 = $em->find('\BB\DurianBundle\Entity\User', 2);
        $user8 = $em->find('\BB\DurianBundle\Entity\User', 8);
        $user8->setDomain(2);

        $user99 = new User();
        $user99->setId(99)->setUsername('new_test')
                ->setAlias('test')->setPassword('q1q1q1')->setDomain(2);
        $em->persist($user99);

        $ua = new UserAncestor($user99, $user2, 6);
        $em->persist($ua);

        $bank = new Bank($user99);
        $em->persist($bank);
        $bank->setAccount('123');

        $em->flush();

        // 測試修改成已有帳號是否會回傳錯誤
        $parameters = array(
            'old_account' => '123',
            'new_account' => '6221386170003601228',
        );

        $client->request('PUT', '/api/user/99/bank', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('This account already been used', $output['msg']);
        $this->assertEquals('120001', $output['code']);
    }

    /**
     * 修改銀行資訊, 新舊帳號前面差一個0
     */
    public function testEditBankWithAlmostSameAccount()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user2 = $em->find('\BB\DurianBundle\Entity\User', 2);
        $user8 = $em->find('\BB\DurianBundle\Entity\User', 8);
        $user8->setDomain(2);

        $user99 = new User();
        $user99->setId(99)->setUsername('new_test')
                ->setAlias('test')->setPassword('q1q1q1')->setDomain(2);
        $em->persist($user99);

        $ua = new UserAncestor($user99, $user2, 6);
        $em->persist($ua);

        $bank = new Bank($user99);
        $em->persist($bank);
        $bank->setAccount('123');

        $em->flush();

        $parameters = array(
            'old_account' => '123',
            'new_account' => '0123',
        );

        $client->request('PUT', '/api/user/99/bank', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('0123', $output['ret']['account']);
    }

    /**
     * 修改最後出款帳號的銀行資訊
     */
    public function testEditLastBank()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('\BB\DurianBundle\Entity\User', 8);
        $user->setLastBank(1);

        $em->flush();
        $em->clear();

        $parameters = array(
            'old_account' => '6221386170003601228',
            'new_account' => '1234847618348674231',
            'code'        => '2',
            'status'      => Bank::USED,
            'province'    => 'asia',
            'city'        => 'taiwan'
        );

        $client->request('PUT', '/api/user/8/bank', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $user = $em->find('\BB\DurianBundle\Entity\User', 8);
        $bank = $em->find('\BB\DurianBundle\Entity\Bank', $user->getLastBank());
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($bank->getId(), $output['ret']['id']);
        $this->assertEquals($bank->getCode(), $output['ret']['code']);
        $this->assertEquals($bank->getAccount(), $output['ret']['account']);
        $this->assertEquals($bank->getStatus(), $output['ret']['status']);
        $this->assertEquals($bank->getProvince(), $output['ret']['province']);
        $this->assertEquals($bank->getCity(), $output['ret']['city']);
    }

    /**
     * 測試取得使用者銀行資訊
     */
    public function testGetBankByUser()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/8/bank', array(), array(), $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 以id作為index
        $banks = [];

        foreach ($output['ret'] as $bank) {
            $banks[$bank['id']] = $bank;
        }

        $this->assertEquals('ok', $output['result']);

        // Bank資訊
        $this->assertEquals(11, $banks[1]['code']);
        $this->assertEquals('6221386170003601228', $banks[1]['account']);

        $this->assertEquals(15, $banks[2]['code']);
        $this->assertEquals('', $banks[2]['province']);
        $this->assertEquals('ddf41d8s69786fd7s54f6ds', $banks[2]['account']);

        $this->assertEquals(2, $banks[3]['code']);
        $this->assertEquals('', $banks[3]['city']);
        $this->assertEquals('4', $banks[3]['account']);

        $this->assertEquals(1, $banks[4]['code']);
        $this->assertEquals('3141586254359', $banks[4]['account']);

        // check log file exists
        $logPath = $this->getLogfilePath('sensitive.log');
        $this->assertFileExists($logPath);

        //read log to check content
        $results = explode(PHP_EOL, file_get_contents($logPath));

        $line = "sensitive-data:$this->sensitiveData";
        $this->assertTrue(strpos($results[0], $line) !== false);
    }

    /**
     * 測試取得使用者銀行資訊不存在的情況
     */
    public function testGetBankByUserWithoutAnyBankData()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/user/7/bank');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(array(), $output['ret']);
    }

    /**
     * 測試取得使用者最後出款銀行資訊
     */
    public function testGetBankByUserWithLast()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BB\DurianBundle\Entity\User', 8);
        $banks = $em->getRepository('BBDurianBundle:Bank')->findBy(['user' => $user], ['id' => 'ASC']);
        $user->setLastBank($banks[2]->getId());
        $em->flush();

        $parameter = array('last' => '1');

        $client->request('GET', '/api/user/8/bank', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, count($output['ret']));

        // Bank資訊
        $this->assertEquals(2, $output['ret'][0]['code']);
        $this->assertEquals('', $output['ret'][0]['city']);
        $this->assertEquals('4', $output['ret'][0]['account']);
    }

    /**
     * 測試取得使用者最後出款銀行資訊不存在
     */
    public function testGetBankByUserWithoutLastData()
    {
        $client = $this->createClient();

        $parameter = array('last' => '1');

        $client->request('GET', '/api/user/8/bank', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(array(), $output['ret']);
    }

    /**
     * 測試指定銀行帳號取得使用者銀行資訊
     */
    public function testGetBankByUserWithAccount()
    {
        $client = $this->createClient();
        $parameter = array('account' => '4');

        $client->request('GET', '/api/user/8/bank', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // Bank資訊
        $this->assertEquals('', $output['ret'][0]['city']);
        $this->assertEquals('2', $output['ret'][0]['code']);
        $this->assertEquals('4', $output['ret'][0]['account']);

        $this->assertTrue(empty($output['ret'][1]));
    }

    /**
     * 測試取得使用者銀行資訊時指定銀行帳號不存在
     */
    public function testGetBankByUserWithErrorAccount()
    {
        $client = $this->createClient();
        $parameter = array('account' => '5566');

        $client->request('GET', '/api/user/8/bank', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // Bank資訊
        $this->assertEquals(array(), $output['ret']);
    }

    /**
     * 測試指定是否為電子錢包帳戶取得使用者銀行資訊
     */
    public function testGetBankByUserWithMobile()
    {
        $client = $this->createClient();
        $parameter = ['mobile' => '1'];

        $client->request('GET', '/api/user/8/bank', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // Bank資訊
        $this->assertEquals('', $output['ret'][0]['city']);
        $this->assertEquals('4', $output['ret'][0]['code']);
        $this->assertEquals('12345678', $output['ret'][0]['account']);

        $this->assertTrue(empty($output['ret'][1]));
    }

    /**
     * 測試檢查銀行帳號唯一
     *
     */
    public function testCheckUnique()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user8 = $em->find('\BB\DurianBundle\Entity\User', 8);
        $user8->setDomain(2);
        $em->flush();

        //測試同domain已有帳號'4'
        $parameter = array('domain'  => '2',
                           'depth'   => '6',
                           'account' => '4');

        $client->request('GET', '/api/bank/check_unique', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertFalse($output['ret']['unique']);


        //測試同domain已有帳號'4', 但有帶入該使用者user_id
        $parameter = array('domain'  => '2',
                           'depth'   => '6',
                           'account' => '4',
                           'user_id' => '8');

        $client->request('GET', '/api/bank/check_unique', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertTrue($output['ret']['unique']);

        //測試同domain不同depth
        $parameter = array('domain'  => '2',
                           'depth'   => '5',
                           'account' => '4');

        $client->request('GET', '/api/bank/check_unique', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertTrue($output['ret']['unique']);
    }

    /**
     * 測試刪除銀行帳號
     */
    public function testRemoveBank()
    {
        $client = $this->createClient();

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $user = $em->find('BB\DurianBundle\Entity\User', 8);

        $client->request('DELETE', '/api/bank/6');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('No Bank found', $output['msg']);
        $this->assertEquals('120004', $output['code']);

        $user->setLastBank(2);

        $em->flush();
        $em->clear();

        $client->request('DELETE', '/api/bank/2');

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals("bank", $logOperation->getTableName());
        $this->assertEquals("@user_id:8", $logOperation->getMajorKey());
        $this->assertEquals("", $logOperation->getMessage());

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals("user", $logOperation->getTableName());
        $this->assertEquals("@id:8", $logOperation->getMajorKey());
        $this->assertEquals("@last_bank:2=>null", $logOperation->getMessage());

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $user = $em->find('BB\DurianBundle\Entity\User', 8);

        $this->assertNull($user->getLastBank());

        $bank2 = $em->find('BB\DurianBundle\Entity\Bank', 2);

        $this->assertNull($bank2);

        // 檢查金流操作紀錄
        $paymentOperationLogPath = $this->getLogfilePath('payment_operation.log');
        $this->assertFileExists($paymentOperationLogPath);

        $paymentOperationLog = explode(PHP_EOL, file_get_contents($paymentOperationLogPath));
        $this->assertContains('result=ok', $paymentOperationLog[0]);
    }

    /**
     * 測試對不存在的使用者新增銀行資料
     */
    public function testNewBankWithNonExistUser()
    {
        $client = $this->createClient();

        $parameters = [
            'code' => '56',
            'account' => 'iloveN55- 66',
            'province' => 'asia',
            'city' => 'taiwan'
        ];

        $client->request('POST', '/api/user/999/bank', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(120011, $output['code']);
        $this->assertEquals('No such user', $output['msg']);
    }

    /**
     * 檢查銀行帳號是否重複時沒給登入站別與上層使用者id
     */
    public function testCheckUniqueWithoutDomainAndParentId()
    {
        $client = $this->createClient();

        $parameter = [
            'depth' => '6',
            'account' => '4'
        ];

        $client->request('GET', '/api/bank/check_unique', $parameter, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(120010, $output['code']);
        $this->assertEquals('No parent_id specified', $output['msg']);
    }

    /**
     * 檢查銀行帳號是否重複時沒給帳號
     */
    public function testCheckUniqueWithoutAccount()
    {
        $client = $this->createClient();

        $parameter = [
            'domain' => '2',
            'depth' => '6',
        ];

        $client->request('GET', '/api/bank/check_unique', $parameter, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(120003, $output['code']);
        $this->assertEquals('No account specified', $output['msg']);
    }
}
