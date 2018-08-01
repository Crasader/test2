<?php
namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Command\TransferUserCrossDomainCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use BB\DurianBundle\Entity\Bank;
use BB\DurianBundle\Entity\PresetLevel;
use BB\DurianBundle\Entity\Level;

class TransferUserCrossDomainCommandTest extends WebTestCase
{
    public function setUp()
    {
        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCardData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCreditData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashDepositEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashWithdrawEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareUpdateCronForControllerData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPresetLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelCurrencyData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainTotalTestData'
        ];
        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigData',
        ];
        $this->loadFixtures($classnames, 'share');

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $redis->flushdb();

        $redis = $this->getContainer()->get('snc_redis.sequence');

        $redis->set('user_seq', 20000000);
    }

    /**
     * 測試前置檢查輸入錯誤參數(沒有輸入大股東id)
     */
    public function testInputWithoutUserIdWithCheck()
    {
        $params = [
            '--targetDomain' => 6,
            '--suffix' => 'test',
            '--check' => true
        ];

        $output = $this->runCommand('durian:transfer-user-crossDomain', $params);

        $this->assertContains('[Exception]', $output);
        $this->assertContains('Invalid arguments input', $output);
    }

    /**
     * 測試前置檢查輸入錯誤參數(沒有輸入目標廳主id)
     */
    public function testInputWithoutTargetDomainWithCheck()
    {
        $params = [
            '--userId' => 4,
            '--suffix' => 'test',
            '--check' => true
        ];

        $output = $this->runCommand('durian:transfer-user-crossDomain', $params);

        $this->assertContains('[Exception]', $output);
        $this->assertContains('Invalid arguments input', $output);
    }

    /**
     * 測試前置檢查輸入錯誤參數(後綴詞非小寫)
     */
    public function testInputSuffixNotLowercaseWithCheck()
    {
        $params = [
            '--userId' => 4,
            '--targetDomain' => 6,
            '--suffix' => 'tesT',
            '--check' => true
        ];

        $output = $this->runCommand('durian:transfer-user-crossDomain', $params);

        $this->assertContains('[Exception]', $output);
        $this->assertContains('Invalid suffix', $output);
    }

    /**
     * 測試檢查輸入錯誤參數(重複後綴詞非小寫)
     */
    public function testInputDuplicateSuffixNotLowercase()
    {
        $params = [
            '--userId' => 4,
            '--targetDomain' => 6,
            '--suffix' => 'test',
            '--duplicateSuffix' => 'testT',
            '--updateUser' => true
        ];

        $output = $this->runCommand('durian:transfer-user-crossDomain', $params);

        $this->assertContains('[Exception]', $output);
        $this->assertContains('Invalid duplicateSuffix', $output);
    }

    /**
     * 測試更新明細時輸入錯誤參數(未帶來源廳主id)
     */
    public function testInputWithoutSourceDomainWithUpdateEntry()
    {
        $params = [
            '--userId' => 4,
            '--targetDomain' => 6,
            '--updateEntry' => true
        ];

        $output = $this->runCommand('durian:transfer-user-crossDomain', $params);

        $this->assertContains('[Exception]', $output);
        $this->assertContains('Invalid arguments input', $output);
    }

    /**
     * 測試前置檢查有無假現金部分
     */
    public function testCheckDataOfCashFake()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        //測試檢查出下層有假現金,但預存預扣皆為0
        $params = [
            '--userId' => 4,
            '--targetDomain' => 6,
            '--suffix' => 'test',
            '--check' => true
        ];

        $output = $this->runCommand('durian:transfer-user-crossDomain', $params);
        $output = explode("\n", $output);
        $this->assertEquals('大股東底下帳號有假現金資料，但預存預扣皆為0，請確認', $output[0]);

        $cashFake1 = $em->find('BBDurianBundle:CashFake', 1);
        $cashFake1->addPreAdd(10);
        $cashFake2 = $em->find('BBDurianBundle:CashFake', 2);
        $cashFake2->addPreSub(10);
        $em->persist($cashFake1);
        $em->persist($cashFake2);
        $em->flush();

        //測試檢查出下層有假現金,但預存預扣不為0
        $params = [
            '--userId' => 4,
            '--targetDomain' => 6,
            '--suffix' => 'test',
            '--check' => true
        ];

        $output = $this->runCommand('durian:transfer-user-crossDomain', $params);
        $output = explode("\n", $output);
        $this->assertEquals('cashFakeId:1預存或預扣不為0，請確認', $output[0]);
        $this->assertEquals('cashFakeId:2預存或預扣不為0，請確認', $output[1]);

        //測試下層皆無假現金
        $parameter = [
            'parent_id' => 3,
            'role' => 5,
            'username' => 'transfer',
            'password' => 'newpassword',
            'alias' => 'transfer'
        ];

        $client->request('POST', '/api/user', $parameter);

        $params = [
            '--userId' => 20000001,
            '--targetDomain' => 6,
            '--suffix' => 'test',
            '--check' => true
        ];

        $output = $this->runCommand('durian:transfer-user-crossDomain', $params);
        $output = explode("\n", $output);
        $this->assertEquals('大股東底下帳號皆無假現金資料', $output[0]);
    }

    /**
     * 測試前置檢查大股東有無信用額度
     */
    public function testCheckDataOfCredit()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        //測試檢查大股東本身沒有信用額度
        $params = [
            '--userId' => 4,
            '--targetDomain' => 6,
            '--suffix' => 'test',
            '--check' => true
        ];

        $output = $this->runCommand('durian:transfer-user-crossDomain', $params);
        $output = explode("\n", $output);
        $this->assertEquals('大股東無信用額度資料', $output[1]);

        //測試檢查大股東本身有信用額度，但line或total_line皆為0
        $client->request('POST', '/api/user/2/credit/1');
        $client->request('POST', '/api/user/3/credit/1');

        $parameter = [
            'parent_id' => 3,
            'role' => 5,
            'username' => 'transfer',
            'password' => 'newpassword',
            'alias' => 'transfer',
            'credit' => [1 => ['line' => 0]]
        ];

        $client->request('POST', '/api/user', $parameter);

        $params = [
            '--userId' => 20000001,
            '--targetDomain' => 6,
            '--suffix' => 'test',
            '--check' => true
        ];

        $output = $this->runCommand('durian:transfer-user-crossDomain', $params);
        $output = explode("\n", $output);
        $this->assertEquals('大股東有信用額度且line，total_line皆為0，請確認', $output[1]);

        //測試檢查大股東本身有信用額度，但line或total_line不為0
        $credit1 = $em->find('BBDurianBundle:Credit', 9);
        $credit1->setLine(1000);
        $em->persist($credit1);
        $credit2 = $em->find('BBDurianBundle:Credit', 10);
        $credit2->setLine(100);
        $em->persist($credit2);
        $credit3 = $em->find('BBDurianBundle:Credit', 11);
        $credit3->setLine(50);
        $em->persist($credit3);
        $em->flush();

        $params = [
            '--userId' => 20000001,
            '--targetDomain' => 6,
            '--suffix' => 'test',
            '--check' => true
        ];

        $output = $this->runCommand('durian:transfer-user-crossDomain', $params);
        $output = explode("\n", $output);
        $this->assertEquals('大股東有信用額度且line或total_line不為0，請確認', $output[1]);
    }

    /**
     * 測試前置檢查大股東有無租卡
     */
    public function testCheckDataOfCard()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        //測試檢查大股東本身沒有租卡
        $parameter = [
            'parent_id' => 3,
            'role' => 5,
            'username' => 'transfer',
            'password' => 'newpassword',
            'alias' => 'transfer'
        ];

        $client->request('POST', '/api/user', $parameter);

        $params = [
            '--userId' => 20000001,
            '--targetDomain' => 6,
            '--suffix' => 'test',
            '--check' => true
        ];

        $output = $this->runCommand('durian:transfer-user-crossDomain', $params);
        $output = explode("\n", $output);
        $this->assertEquals('大股東無租卡資料', $output[2]);

        //測試檢查大股東本身有租卡，但enable_num為0
        $params = [
            '--userId' => 4,
            '--targetDomain' => 6,
            '--suffix' => 'test',
            '--check' => true
        ];

        $output = $this->runCommand('durian:transfer-user-crossDomain', $params);
        $output = explode("\n", $output);
        $this->assertEquals('大股東有租卡且enable_num為0，請確認', $output[2]);

        //測試檢查大股東本身有租卡，但enable_num不為0
        $card = $em->find('BBDurianBundle:Card', 3);
        $card->addEnableNum();
        $em->persist($card);
        $em->flush();

        $params = [
            '--userId' => 4,
            '--targetDomain' => 6,
            '--suffix' => 'test',
            '--check' => true
        ];

        $output = $this->runCommand('durian:transfer-user-crossDomain', $params);
        $output = explode("\n", $output);
        $this->assertEquals('大股東有租卡且enable_num不為0，請確認', $output[2]);
    }

    /**
     * 測試前置檢查使用者帳號有無重複(有重複狀況)且檢查轉移使用者層級語法
     */
    public function testCheckDataOfUserDuplicateAndValidateUserLevelSql()
    {
        $client = $this->createClient();

        //新建一條體系，並故意讓使用者帳號重複
        $parameter = [
            'role' => 7,
            'username' => 'transfer',
            'password' => 'newpassword',
            'alias' => 'transfer',
            'login_code' => 'aaa',
            'name' => 'transfer',
            'currency' => 'CNY',
            'cash' => ['currency' => 'CNY']
        ];

        $client->request('POST', '/api/user', $parameter);

        //建立層級
        $parameter = [
            'domain' => 52,
            'alias' => '未分層',
            'order_strategy' => 0,
            'created_at_start' => '2015-10-13 00:00:00',
            'created_at_end' => '2015-10-13 00:00:00',
            'deposit_count' => 0,
            'deposit_total' => 0,
            'deposit_max' => 1000,
            'withdraw_count' => 0,
            'withdraw_total' => 0
        ];

        $client->request('POST', '/api/level', $parameter);

        $parameter = ['level_id' => 9];
        $client->request('POST', '/api/user/52/preset_level', $parameter);

        $parameter = [
            'parent_id' => 52,
            'role' => 5,
            'username' => 'ztest',
            'password' => 'newpassword',
            'alias' => 'ztest',
            'currency' => 'CNY',
            'cash' => ['currency' => 'CNY']
        ];

        $client->request('POST', '/api/user', $parameter);

        //將此大股東設定成大股東面板
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->find('BBDurianBundle:User', 20000001);
        $level = $em->find('BBDurianBundle:Level', 2);
        $presetLevel = new PresetLevel($user, $level);
        $em->persist($presetLevel);
        $em->flush();

        $parameter = [
            'parent_id' => 20000001,
            'role' => 4,
            'username' => 'wtest',
            'password' => 'newpassword',
            'alias' => 'wtest',
            'cash' => ['currency' => 'CNY']
        ];

        $client->request('POST', '/api/user', $parameter);

        $parameter = [
            'parent_id' => 20000002,
            'role' => 3,
            'username' => 'abtest',
            'password' => 'newpassword',
            'alias' => 'xtest',
            'cash' => ['currency' => 'CNY']
        ];

        $client->request('POST', '/api/user', $parameter);

        $parameter = [
            'parent_id' => 20000003,
            'role' => 2,
            'username' => 'actest',
            'password' => 'newpassword',
            'alias' => 'ctest',
            'cash' => ['currency' => 'CNY']
        ];

        $client->request('POST', '/api/user', $parameter);

        $parameter = [
            'parent_id' => 20000004,
            'role' => 1,
            'username' => 'aatest',
            'password' => 'newpassword',
            'alias' => 'vtest',
            'cash' => ['currency' => 'CNY']
        ];

        $client->request('POST', '/api/user', $parameter);

        $params = [
            '--userId' => 20000001,
            '--targetDomain' => 2,
            '--suffix' => 'er',
            '--presetLevel' => 1,
            '--check' => true
        ];

        $output = $this->runCommand('durian:transfer-user-crossDomain', $params);
        $output = explode("\n", $output);
        $this->assertEquals('加上後綴詞後，重複帳號名單:duplicateUser.csv', $output[3]);

        $outputFile = fopen('duplicateUser.csv', 'r');
        //驗證輸出csv檔中的重複名單的user_id，username
        $line = fgetcsv($outputFile, 1000);
        $this->assertEquals(20000002, $line[0]);
        $this->assertEquals('wtester', $line[1]);

        $line = fgetcsv($outputFile, 1000);
        $this->assertEquals(20000001, $line[0]);
        $this->assertEquals('ztester', $line[1]);

        //驗證轉移層級語法
        $outputFile = fopen('transferSql.csv', 'r');
        $line = fgetcsv($outputFile, 1000);
        $this->assertEquals('DELETE FROM `preset_level` WHERE user_id = 20000001;', $line[0]);

        $line = fgetcsv($outputFile, 1000);
        $this->assertEquals(
            'UPDATE `user_level` as ul INNER JOIN `user_ancestor` as ua on ul.user_id = ua.user_id '.
            'SET ul.level_id = 1, ul.last_level_id = 1 WHERE ua.ancestor_id = 20000001 AND ua.depth = 4;',
            "$line[0],$line[1]"
        );

        $line = fgetcsv($outputFile, 1000);
        $this->assertEquals(
            'UPDATE `level` SET `user_count` = user_count - 1 WHERE id = 2;',
            $line[0]
        );

        $line = fgetcsv($outputFile, 1000);
        $this->assertEquals(
            'UPDATE `level` SET `user_count` = user_count + 1 WHERE id = 1;',
            $line[0]
        );

        $line = fgetcsv($outputFile, 1000);
        $this->assertEquals(
            'UPDATE `level_currency` SET `user_count` = user_count - 1 WHERE level_id = 2 AND currency = 156;',
            $line[0]
        );

        $line = fgetcsv($outputFile, 1000);
        $this->assertEquals(
            'UPDATE `level_currency` SET `user_count` = user_count + 1 WHERE level_id = 1 AND currency = 156;',
            $line[0]
        );
    }

    /**
     * 測試前置檢查使用者帳號有無重複(無重複狀況)
     */
    public function testCheckDataOfUserNoDuplicate()
    {
        $client = $this->createClient();

        //新建一條體系
        $parameter = [
            'role' => 7,
            'username' => 'transfer',
            'password' => 'newpassword',
            'alias' => 'transfer',
            'name' => 'transfer',
            'login_code' => 'aaa'
        ];

        $client->request('POST', '/api/user', $parameter);

        $parameter = [
            'parent_id' => 52,
            'role' => 5,
            'username' => 'ztestaa',
            'password' => 'newpassword',
            'alias' => 'ztestaa',
        ];

        $client->request('POST', '/api/user', $parameter);

        $params = [
            '--userId' => 20000001,
            '--targetDomain' => 2,
            '--suffix' => 'er',
            '--check' => true
        ];

        $output = $this->runCommand('durian:transfer-user-crossDomain', $params);
        $output = explode("\n", $output);
        $this->assertEquals('加上後綴詞後，轉移過去目前帳號並無帳號重複問題', $output[3]);
    }

    /**
     * 測試前置檢查使用者帳號有無超過15碼(超過15碼狀況)
     */
    public function testCheckDataUsernameLengthOver()
    {
        $client = $this->createClient();

        //新建一條體系
        $parameter = [
            'role' => 7,
            'username' => 'transfer',
            'password' => 'newpassword',
            'alias' => 'transfer',
            'name' => 'transfer',
            'login_code' => 'aaa'
        ];

        $client->request('POST', '/api/user', $parameter);

        $parameter = [
            'parent_id' => 52,
            'role' => 5,
            'username' => 'ztestaaaaaaa',
            'password' => 'newpassword',
            'alias' => 'ztestaaaaaaa',
        ];

        $client->request('POST', '/api/user', $parameter);

        $parameter = [
            'parent_id' => 20000001,
            'role' => 4,
            'username' => 'ztestbbbbbbb',
            'password' => 'newpassword',
            'alias' => 'ztestbbbbbbb',
        ];

        $client->request('POST', '/api/user', $parameter);

        $params = [
            '--userId' => 20000001,
            '--targetDomain' => 2,
            '--suffix' => 'aaer',
            '--check' => true
        ];

        $output = $this->runCommand('durian:transfer-user-crossDomain', $params);
        $output = explode("\n", $output);
        $this->assertEquals('加上後綴詞後，帳號長度超過15碼名單:usernameOver.csv', $output[4]);

        $outputFile = fopen('usernameOver.csv', 'r');

        //驗證輸出csv檔中的重複名單的user_id，username
        $line = fgetcsv($outputFile, 1000);
        $this->assertEquals(20000002, $line[0]);
        $this->assertEquals('ztestbbbbbbb', $line[1]);

        $line = fgetcsv($outputFile, 1000);
        $this->assertEquals(20000001, $line[0]);
        $this->assertEquals('ztestaaaaaaa', $line[1]);
    }

    /**
     * 測試前置檢查使用者帳號有無超過15碼(沒有超過15碼狀況)
     */
    public function testCheckDataUsernameLengthNotOver()
    {
        $client = $this->createClient();

        //新建一條體系
        $parameter = [
            'role' => 7,
            'username' => 'transfer',
            'password' => 'newpassword',
            'alias' => 'transfer',
            'login_code' => 'aaa'
        ];

        $client->request('POST', '/api/user', $parameter);

        $parameter = [
            'parent_id' => 52,
            'role' => 5,
            'username' => 'ztest',
            'password' => 'newpassword',
            'alias' => 'ztest',
        ];

        $client->request('POST', '/api/user', $parameter);

        $params = [
            '--userId' => 20000001,
            '--targetDomain' => 2,
            '--suffix' => 'er',
            '--check' => true
        ];

        $output = $this->runCommand('durian:transfer-user-crossDomain', $params);
        $output = explode("\n", $output);
        $this->assertEquals('加上後綴詞後，沒有帳號長度超過15碼', $output[4]);
    }

    /**
     * 測試前置檢查同層使用者銀行帳號有無重複(有重複狀況)
     */
    public function testCheckDataOfAccountDuplicate()
    {
        $client = $this->createClient();

        //新建一條體系
        $parameter = [
            'role' => 7,
            'username' => 'transfer',
            'password' => 'newpassword',
            'alias' => 'transfer',
            'login_code' => 'aaa',
            'name' => 'transfer'
        ];

        $client->request('POST', '/api/user', $parameter);

        $parameter = [
            'parent_id' => 52,
            'role' => 5,
            'username' => 'zz1234567',
            'password' => 'newpassword',
            'alias' => 'ztest'
        ];

        $client->request('POST', '/api/user', $parameter);

        $parameter = [
            'parent_id' => 20000001,
            'role' => 4,
            'username' => 'yy1234567',
            'password' => 'newpassword',
            'alias' => 'ztestbbbbbbb',
        ];

        $client->request('POST', '/api/user', $parameter);

        $parameter = [
            'parent_id' => 20000002,
            'role' => 3,
            'username' => 'xx1234567',
            'password' => 'newpassword',
            'alias' => 'ztestbbbbbbb',
        ];

        $client->request('POST', '/api/user', $parameter);

        $parameter = [
            'parent_id' => 20000003,
            'role' => 2,
            'username' => 'ww1234567',
            'password' => 'newpassword',
            'alias' => 'ztestbbbbbbb',
        ];

        $client->request('POST', '/api/user', $parameter);

        $parameter = [
            'parent_id' => 20000004,
            'role' => 1,
            'username' => 'vv1234567',
            'password' => 'newpassword',
            'alias' => 'ztestbbbbbbb',
        ];

        $client->request('POST', '/api/user', $parameter);

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 新增銀行資料
        // role = 5
        $user = $em->find('BBDurianBundle:User', 20000001);
        $bank = new Bank($user);
        $bank->setAccount(666655544);
        $em->persist($bank);

        $user = $em->find('BBDurianBundle:User', 4);
        $bank = new Bank($user);
        $bank->setAccount(666655544);
        $em->persist($bank);

        // role = 4
        $user = $em->find('BBDurianBundle:User', 20000002);
        $bank = new Bank($user);
        $bank->setAccount(789456123);
        $em->persist($bank);

        $user = $em->find('BBDurianBundle:User', 5);
        $bank = new Bank($user);
        $bank->setAccount(789456123);
        $em->persist($bank);

        // role = 1
        $user = $em->find('BBDurianBundle:User', 20000005);
        $bank = new Bank($user);
        $bank->setAccount(111222333);
        $em->persist($bank);

        $user = $em->find('BBDurianBundle:User', 8);
        $bank = new Bank($user);
        $bank->setAccount(111222333);
        $em->persist($bank);

        $em->flush();

        $params = [
            '--userId' => 20000001,
            '--targetDomain' => 2,
            '--suffix' => 'er',
            '--check' => true
        ];

        $output = $this->runCommand('durian:transfer-user-crossDomain', $params);
        $output = explode(PHP_EOL, $output);
        $this->assertEquals('重複銀行帳號名單:duplicateAccountUser.csv', $output[5]);

        $outputFile = fopen('duplicateAccountUser.csv', 'r');

        //驗證輸出csv檔中的重複名單的user_id、username、role、account
        $line = fgetcsv($outputFile, 1000);
        $this->assertEquals(20000001, $line[0]);
        $this->assertEquals('zz1234567', $line[1]);
        $this->assertEquals(5, $line[2]);
        $this->assertEquals('666655544', $line[3]);

        $line = fgetcsv($outputFile, 1000);
        $this->assertEquals(20000002, $line[0]);
        $this->assertEquals('yy1234567', $line[1]);
        $this->assertEquals(4, $line[2]);
        $this->assertEquals('789456123', $line[3]);

        $line = fgetcsv($outputFile, 1000);
        $this->assertEquals(20000005, $line[0]);
        $this->assertEquals('vv1234567', $line[1]);
        $this->assertEquals(1, $line[2]);
        $this->assertEquals('111222333', $line[3]);
    }

    /**
     * 測試前置檢查同層使用者銀行帳號有無重複(無重複狀況)
     */
    public function testCheckDataOfAccountNoDuplicate()
    {
        $client = $this->createClient();

        //新建一條體系
        $parameter = [
            'role' => 7,
            'username' => 'transfer',
            'password' => 'newpassword',
            'alias' => 'transfer',
            'login_code' => 'aaa',
            'name' => 'transfer'
        ];

        $client->request('POST', '/api/user', $parameter);

        $parameter = [
            'parent_id' => 52,
            'role' => 5,
            'username' => 'zz1234567',
            'password' => 'newpassword',
            'alias' => 'ztest'
        ];

        $client->request('POST', '/api/user', $parameter);

        $parameter = [
            'parent_id' => 20000001,
            'role' => 4,
            'username' => 'yy1234567',
            'password' => 'newpassword',
            'alias' => 'ztestbbbbbbb',
        ];

        $client->request('POST', '/api/user', $parameter);

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 新增銀行資料
        // role = 5
        $user = $em->find('BBDurianBundle:User', 20000001);
        $bank = new Bank($user);
        $bank->setAccount(789456123);
        $em->persist($bank);

        $user = $em->find('BBDurianBundle:User', 4);
        $bank = new Bank($user);
        $bank->setAccount(666655544);
        $em->persist($bank);

        // role = 4
        $user = $em->find('BBDurianBundle:User', 20000002);
        $bank = new Bank($user);
        $bank->setAccount(666655544);
        $em->persist($bank);

        $user = $em->find('BBDurianBundle:User', 5);
        $bank = new Bank($user);
        $bank->setAccount(789456123);
        $em->persist($bank);

        $em->flush();

        $params = [
            '--userId' => 20000001,
            '--targetDomain' => 2,
            '--suffix' => 'er',
            '--check' => true
        ];

        $output = $this->runCommand('durian:transfer-user-crossDomain', $params);
        $output = explode(PHP_EOL, $output);
        $this->assertEquals('同層使用者並無銀行帳號重複問題', $output[5]);
        //測試轉移體系語法檔案有沒有正常產生
        $this->assertEquals('轉移體系更新語法:transferSql.csv', $output[6]);
    }

    /**
     * 測試更新使用者資料表(使用者帳號，domain)欄位與更新使用者層級
     */
    public function testUpdateUserAndUserLevel()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        //新建一條體系
        $parameter = [
            'role' => 7,
            'username' => 'transfer',
            'password' => 'newpassword',
            'alias' => 'transfer',
            'login_code' => 'aaa',
            'name' => 'transfer',
            'currency' => 'CNY',
            'cash' => ['currency' => 'CNY']
        ];

        $client->request('POST', '/api/user', $parameter);

        //建立層級
        $parameter = [
            'domain' => 52,
            'alias' => '未分層',
            'order_strategy' => 0,
            'created_at_start' => '2015-10-13 00:00:00',
            'created_at_end' => '2015-10-13 00:00:00',
            'deposit_count' => 0,
            'deposit_total' => 0,
            'deposit_max' => 1000,
            'withdraw_count' => 0,
            'withdraw_total' => 0
        ];

        $client->request('POST', '/api/level', $parameter);

        $parameter = ['level_id' => 9];
        $client->request('POST', '/api/user/52/preset_level', $parameter);

        $parameter = [
            'parent_id' => 52,
            'role' => 5,
            'username' => 'ztest',
            'password' => 'newpassword',
            'alias' => 'ztest',
            'currency' => 'CNY',
            'cash' => ['currency' => 'CNY']
        ];

        $client->request('POST', '/api/user', $parameter);

        $parameter = [
            'parent_id' => 20000001,
            'role' => 4,
            'username' => 'ytest',
            'password' => 'newpassword',
            'alias' => 'ytest',
            'cash' => ['currency' => 'CNY']
        ];

        $client->request('POST', '/api/user', $parameter);

        $parameter = [
            'parent_id' => 20000002,
            'role' => 3,
            'username' => 'vtest',
            'password' => 'newpassword',
            'alias' => 'vtest',
            'cash' => ['currency' => 'CNY']
        ];

        $client->request('POST', '/api/user', $parameter);

        $parameter = [
            'parent_id' => 20000003,
            'role' => 2,
            'username' => 'btest',
            'password' => 'newpassword',
            'alias' => 'btest',
            'cash' => ['currency' => 'CNY']
        ];

        $client->request('POST', '/api/user', $parameter);

        $parameter = [
            'parent_id' => 20000004,
            'role' => 1,
            'username' => 'ktest',
            'password' => 'newpassword',
            'alias' => 'ktest',
            'cash' => ['currency' => 'CNY']
        ];

        $client->request('POST', '/api/user', $parameter);

        $parameter = [
            'parent_id' => 20000004,
            'role' => 1,
            'username' => 'ctest',
            'password' => 'newpassword',
            'alias' => 'ctest',
            'cash' => ['currency' => 'TWD'],
            'test' => 1
        ];

        $client->request('POST', '/api/user', $parameter);

        //將大股東設定成大股東面板
        $createdAtStart = '2015-10-13 00:00:00';
        $createdAtEnd = '2015-10-13 00:00:00';
        $level = new Level(20000001, '分層10', 0, 1);
        $level->setCreatedAtStart(new \DateTime($createdAtStart));
        $level->setCreatedAtEnd(new \DateTime($createdAtEnd));
        $em->persist($level);

        $user = $em->find('BBDurianBundle:User', 20000001);
        $presetLevel = new PresetLevel($user, $level);
        $em->persist($presetLevel);
        $em->flush();
        $em->clear();

        //跑轉移使用者
        $params = [
            '--userId' => 20000001,
            '--sourceDomain' => 52,
            '--targetDomain' => 2,
            '--suffix' => 'aaa',
            '--presetLevel' => 1,
            '--updateUser' => true
        ];

        $this->runCommand('durian:transfer-user-crossDomain', $params);

        $userRole5 = $em->find('BBDurianBundle:User', 20000001);
        $userRole4 = $em->find('BBDurianBundle:User', 20000002);
        $userRole3 = $em->find('BBDurianBundle:User', 20000003);
        $userRole2 = $em->find('BBDurianBundle:User', 20000004);
        $userRole1 = $em->find('BBDurianBundle:User', 20000005);

        //驗證使用者資料
        $this->assertEquals('ztestaaa', $userRole5->getUsername());
        $this->assertEquals(2, $userRole5->getDomain());
        $this->assertEquals('ytestaaa', $userRole4->getUsername());
        $this->assertEquals(2, $userRole4->getDomain());
        $this->assertEquals('vtestaaa', $userRole3->getUsername());
        $this->assertEquals(2, $userRole3->getDomain());
        $this->assertEquals('btestaaa', $userRole2->getUsername());
        $this->assertEquals(2, $userRole2->getDomain());
        $this->assertEquals('ktestaaa', $userRole1->getUsername());
        $this->assertEquals(2, $userRole1->getDomain());

        //驗證原本的大股東面板有無被刪除
        $data = $em->find('BBDurianBundle:PresetLevel', 20000001);
        $this->assertNull($data);

        $this->runCommand('durian:update-level-count');
        $this->runCommand('durian:update-level-currency-count');

        //驗證來源廳的level中原本的兩個會員數量已經被轉移出去
        $level = $em->find('BBDurianBundle:Level', 9);
        $this->assertEquals(0, $level->getUserCount());

        //驗證目標廳未分層會員數量，原本7個，轉移體系後變9個
        $level = $em->find('BBDurianBundle:Level', 1);
        $this->assertEquals(9, $level->getUserCount());

        //驗證來源廳level_currency level:9 currency:156的會員已經被轉移出去
        $levelCurrency = $em->find('BBDurianBundle:LevelCurrency', ['levelId' => 9, 'currency' => 156]);
        $this->assertEquals(0, $levelCurrency->getUserCount());

        //驗證目標廳level_currency level:1 currency:156的會員總數原本為0個，轉移體系後應該要變1個
        $levelCurrency = $em->find('BBDurianBundle:LevelCurrency', ['levelId' => 1, 'currency' => 156]);
        $this->assertEquals(1, $levelCurrency->getUserCount());

        //驗證來源廳level_currency level:9 currency:901的會員已經被轉移出去
        $levelCurrency = $em->find('BBDurianBundle:LevelCurrency', ['levelId' => 9, 'currency' => 901]);
        $this->assertEquals(0, $levelCurrency->getUserCount());

        //驗證目標廳level_currency level:1 currency:901的會員總數原本為7個，轉移體系後應該要變8個
        $levelCurrency = $em->find('BBDurianBundle:LevelCurrency', ['levelId' => 1, 'currency' => 901]);
        $this->assertEquals(8, $levelCurrency->getUserCount());

        //驗證user_level上的level_id與last_level_id是否更新為目標廳的未分層
        $userLevel = $em->find('BBDurianBundle:UserLevel', 20000005);
        $this->assertEquals(1, $userLevel->getLevelId());
        $this->assertEquals(1, $userLevel->getLastLevelId());

        $userLevel = $em->find('BBDurianBundle:UserLevel', 20000006);
        $this->assertEquals(1, $userLevel->getLevelId());
        $this->assertEquals(1, $userLevel->getLastLevelId());

        //驗證domain_total_test的測試帳號數量是否更新
        $domainTotalTest = $em->find('BBDurianBundle:DomainTotalTest', 2);
        $this->assertEquals(1, $domainTotalTest->getTotalTest());
        $domainTotalTest = $em->find('BBDurianBundle:DomainTotalTest', 52);
        $this->assertEquals(0, $domainTotalTest->getTotalTest());
    }

    /**
     * 測試更新使用者資料表(使用者帳號，domain)欄位，沒有使用後綴詞
     */
    public function testUpdateUserWithNoSuffix()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        //新建一條體系
        $parameter = [
            'role' => 7,
            'username' => 'transfer',
            'password' => 'newpassword',
            'alias' => 'transfer',
            'login_code' => 'aaa',
            'name' => 'transfer'
        ];

        $client->request('POST', '/api/user', $parameter);

        $parameter = [
            'parent_id' => 52,
            'role' => 5,
            'username' => 'ztestaa',
            'password' => 'newpassword',
            'alias' => 'ztest'
        ];

        $client->request('POST', '/api/user', $parameter);

        $parameter = [
            'parent_id' => 20000001,
            'role' => 4,
            'username' => 'wtestbb',
            'password' => 'newpassword',
            'alias' => 'wtest'
        ];

        $client->request('POST', '/api/user', $parameter);

        //跑轉移使用者
        $params = [
            '--userId' => 20000001,
            '--sourceDomain' => 52,
            '--targetDomain' => 2,
            '--updateUser' => true
        ];

        $this->runCommand('durian:transfer-user-crossDomain', $params);

        $userRole5 = $em->find('BBDurianBundle:User', 20000001);
        $userRole4 = $em->find('BBDurianBundle:User', 20000002);

        $this->assertEquals('ztestaa', $userRole5->getUsername());
        $this->assertEquals(2, $userRole5->getDomain());
        $this->assertEquals('wtestbb', $userRole4->getUsername());
        $this->assertEquals(2, $userRole4->getDomain());
    }

    /**
     * 測試更新入款明細domain欄位
     */
    public function testUpdateDepositEntry()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $params = [
            '--userId' => 4,
            '--sourceDomain' => 2,
            '--targetDomain' => 6,
            '--updateEntry' => true
        ];

        $this->runCommand('durian:transfer-user-crossDomain', $params);

        $cashDepositEntry = $em->getRepository('BBDurianBundle:CashDepositEntry')
            ->findOneBy(['id' => 201304280000000001]);
        $this->assertEquals(6, $cashDepositEntry->getDomain());
        $cashDepositEntry = $em->getRepository('BBDurianBundle:CashDepositEntry')
            ->findOneBy(['id' => 201305280000000001]);
        $this->assertEquals(6, $cashDepositEntry->getDomain());
    }

    /**
     * 測試更新入款明細domain欄位，rollback情況
     */
    public function testUpdateDepositEntryRollback()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $params = [
            '--userId' => 4,
            '--sourceDomain' => 2,
            '--targetDomain' => 6,
            '--updateEntry' => true
        ];

        $application = new Application();
        $command = new TransferUserCrossDomainCommand();
        $command->setContainer($this->getMockContainer());
        $application->add($command);

        $command = $application->find('durian:transfer-user-crossDomain');
        $commandTester = new CommandTester($command);

        try {
            $commandTester->execute($params);
        } catch (\Exception $e) {
            $this->assertEquals('Connection timed out', $e->getMessage());

            $cashDepositEntry = $em->getRepository('BBDurianBundle:CashDepositEntry')
                ->findOneBy(['id' => 201304280000000001]);
            $this->assertEquals(2, $cashDepositEntry->getDomain());
            $cashDepositEntry = $em->getRepository('BBDurianBundle:CashDepositEntry')
                ->findOneBy(['id' => 201305280000000001]);
            $this->assertEquals(2, $cashDepositEntry->getDomain());
        }
    }

    /**
     * 測試更新出款明細domain欄位
     */
    public function testUpdateWithdrawEntry()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $params = [
            '--userId' => 4,
            '--sourceDomain' => 2,
            '--targetDomain' => 6,
            '--updateEntry' => true
        ];

        $this->runCommand('durian:transfer-user-crossDomain', $params);

        for ($i = 1; $i <= 8; $i++) {
            $cashWithdrawEntry = $em->getRepository('BBDurianBundle:CashWithdrawEntry')
                ->findOneBy(['id' => $i]);
            $this->assertEquals(6, $cashWithdrawEntry->getDomain());
        }
    }

    /**
     * 測試更新出款明細domain欄位，rollback情況
     * updateWithdrawEntry在updateDepositEntry之後呼叫，因此這邊用單元測試減少非必要的mock
     */
    public function testUpdateWithdrawEntryRollback()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $command = new TransferUserCrossDomainCommand();
        $command->setContainer($this->getMockContainer());
        $reflector = new \ReflectionClass('BB\DurianBundle\Command\TransferUserCrossDomainCommand');

        $method = $reflector->getMethod('updateWithdrawEntry');
        $method->setAccessible(true);

        try {
            $method->invokeArgs($command, []);
        } catch (\Exception $e) {
            $this->assertEquals('Connection timed out', $e->getMessage());

            for ($i = 1; $i <= 8; $i++) {
                $cashWithdrawEntry = $em->getRepository('BBDurianBundle:CashWithdrawEntry')
                    ->findOneBy(['id' => $i]);
                $this->assertEquals(2, $cashWithdrawEntry->getDomain());
            }
        }
    }

    /**
     * 測試備份sql語法
     */
    public function testBackupSql()
    {
        $client = $this->createClient();

        //新建一條體系
        $parameter = [
            'role' => 7,
            'username' => 'transfer',
            'password' => 'newpassword',
            'alias' => 'transfer',
            'login_code' => 'aaa',
            'name' => 'transfer',
            'currency' => 'CNY',
            'cash' => ['currency' => 'CNY']
        ];

        $client->request('POST', '/api/user', $parameter);

        //建立層級
        $parameter = [
            'domain' => 52,
            'alias' => '未分層',
            'order_strategy' => 0,
            'created_at_start' => '2015-10-13 00:00:00',
            'created_at_end' => '2015-10-13 00:00:00',
            'deposit_count' => 0,
            'deposit_total' => 0,
            'deposit_max' => 1000,
            'withdraw_count' => 0,
            'withdraw_total' => 0
        ];

        $client->request('POST', '/api/level', $parameter);

        $parameter = ['level_id' => 9];
        $client->request('POST', '/api/user/52/preset_level', $parameter);

        $parameter = [
            'parent_id' => 52,
            'role' => 5,
            'username' => 'ztest',
            'password' => 'newpassword',
            'alias' => 'ztest',
            'currency' => 'CNY',
            'cash' => ['currency' => 'CNY']
        ];

        $client->request('POST', '/api/user', $parameter);

        //將此大股東設定成大股東面板
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->find('BBDurianBundle:User', 20000001);
        $level = $em->find('BBDurianBundle:Level', 2);
        $presetLevel = new PresetLevel($user, $level);
        $em->persist($presetLevel);
        $em->flush();

        $parameter = [
            'parent_id' => 20000001,
            'role' => 4,
            'username' => 'wtest',
            'password' => 'newpassword',
            'alias' => 'wtest',
            'cash' => ['currency' => 'CNY']
        ];

        $client->request('POST', '/api/user', $parameter);

        $parameter = [
            'parent_id' => 20000002,
            'role' => 3,
            'username' => 'abtest',
            'password' => 'newpassword',
            'alias' => 'xtest',
            'cash' => ['currency' => 'CNY']
        ];

        $client->request('POST', '/api/user', $parameter);

        $parameter = [
            'parent_id' => 20000003,
            'role' => 2,
            'username' => 'actest',
            'password' => 'newpassword',
            'alias' => 'ctest',
            'cash' => ['currency' => 'CNY']
        ];

        $client->request('POST', '/api/user', $parameter);

        $parameter = [
            'parent_id' => 20000004,
            'role' => 1,
            'username' => 'aatest',
            'password' => 'newpassword',
            'alias' => 'vtest',
            'cash' => ['currency' => 'CNY'],
            'test' => 1
        ];

        $client->request('POST', '/api/user', $parameter);

        $params = [
            '--userId' => 20000001,
            '--sourceDomain' => 52,
            '--targetDomain' => 2,
            '--presetLevel' => 1,
            '--backupSql' => true
        ];

        $output = $this->runCommand('durian:transfer-user-crossDomain', $params);
        $output = explode("\n", $output);
        $this->assertEquals('轉移體系備份語法:transferBackupSql.csv', $output[0]);

        $outputFile = fopen('transferBackupSql.csv', 'r');
        //驗證輸出csv檔中的sql語法
        $line = fgetcsv($outputFile, 1000);
        $this->assertEquals("UPDATE `user` SET username = 'ztest' domain = 52 WHERE id = 20000001;", $line[0] . $line[1]);
        //跳過檔案內其他備份使用者的語法
        $line = fgetcsv($outputFile, 1000);
        $line = fgetcsv($outputFile, 1000);
        $line = fgetcsv($outputFile, 1000);
        $line = fgetcsv($outputFile, 1000);
        //驗證備份層級語法
        $line = fgetcsv($outputFile, 1000);
        $this->assertEquals("INSERT INTO `preset_level` (user_id, level_id) VALUES (20000001, 2);", "$line[0],$line[1],$line[2]");
        $line = fgetcsv($outputFile, 1000);
        $this->assertEquals("UPDATE `user_level` SET level_id = 2, last_level_id = 0 WHERE user_id = 20000005;", "$line[0],$line[1]");
        $line = fgetcsv($outputFile, 1000);
        $this->assertEquals("UPDATE `level` SET `user_count` = user_count + 1 WHERE id = 2;", $line[0]);
        $line = fgetcsv($outputFile, 1000);
        $this->assertEquals("UPDATE `level` SET `user_count` = user_count - 1 WHERE id = 1;", $line[0]);
        $line = fgetcsv($outputFile, 1000);
        $this->assertEquals("UPDATE `level_currency` SET `user_count` = user_count + 1 WHERE level_id = 2 AND currency = 156;", $line[0]);
        $line = fgetcsv($outputFile, 1000);
        $this->assertEquals("UPDATE `level_currency` SET `user_count` = user_count - 1 WHERE level_id = 1 AND currency = 156;", $line[0]);
        //驗證備份測試帳號數量語法
        $line = fgetcsv($outputFile, 1000);
        $this->assertEquals("UPDATE `domain_total_test` SET total_test = total_test - 1, at = '' WHERE domain = 2;", "$line[0],$line[1]");
        $line = fgetcsv($outputFile, 1000);
        $pattern = "/UPDATE `domain_total_test` SET total_test = total_test \+ 1, at = '\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}' WHERE domain = 52;/";
        $this->assertRegExp($pattern, "$line[0],$line[1]");
    }

    /**
     * 測試產生轉移體系的名單
     */
    public function testTransferList()
    {
        //產生出重複帳號名單檔案
        $outputPath = $this->getContainer()
            ->get('kernel')
            ->getRootDir() . "/../duplicateUser.csv";

        $duplicateUser[] = [
            'id' => 8,
            'username' => 'testeraaa'
        ];

        $line = implode(',', $duplicateUser[0]);
        file_put_contents($outputPath, "$line\n", FILE_APPEND);

        $params = [
            '--userId' => 4,
            '--suffix' => 'aaa',
            '--duplicateSuffix' => 'bbb',
            '--list' => true
        ];

        $output = $this->runCommand('durian:transfer-user-crossDomain', $params);
        $output = explode("\n", $output);

        $this->assertEquals(
            '提供給研一大球組，站台組名單已經產生:ballList.csv, platformList.csv',
            $output[0]
        );

        //驗證輸出csv檔中的內容
        $outputFile = fopen('ballList.csv', 'r');
        $line = fgetcsv($outputFile, 1000);
        $this->assertEquals("'1'", $line[0]);
        $this->assertEquals("'8'", $line[1]);
        $this->assertEquals("'testerbbb'", $line[2]);

        $line = fgetcsv($outputFile, 1000);
        $this->assertEquals("'1'", $line[0]);
        $this->assertEquals("'51'", $line[1]);
        $this->assertEquals("'oauthuseraaa'", $line[2]);

        $line = fgetcsv($outputFile, 1000);
        $this->assertEquals("'2'", $line[0]);
        $this->assertEquals("'7'", $line[1]);
        $this->assertEquals("'ztesteraaa'", $line[2]);

        $line = fgetcsv($outputFile, 1000);
        $this->assertEquals("'3'", $line[0]);
        $this->assertEquals("'6'", $line[1]);
        $this->assertEquals("'ytesteraaa'", $line[2]);

        $line = fgetcsv($outputFile, 1000);
        $this->assertEquals("'4'", $line[0]);
        $this->assertEquals("'5'", $line[1]);
        $this->assertEquals("'xtesteraaa'", $line[2]);

        $line = fgetcsv($outputFile, 1000);
        $this->assertEquals("'5'", $line[0]);
        $this->assertEquals("'4'", $line[1]);
        $this->assertEquals("'wtesteraaa'", $line[2]);

        $outputFile = fopen('platformList.csv', 'r');
        $line = fgetcsv($outputFile, 1000);
        $this->assertEquals("8", $line[0]);
    }

    /**
     * 取得 MockContainer
     *
     * @return \Symfony\Component\DependencyInjection\Container
     */
    private function getMockContainer()
    {
        $mockQuery = $this->getMockBuilder('\Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['setParameter', 'execute'])
            ->getMockForAbstractClass();

        $mockEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['createQuery'])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('createQuery')
            ->will($this->returnValue($mockQuery));

        $mockPltform = $this->getMockBuilder('Doctrine\DBAL\Platforms\SqlitePlatform')
            ->disableOriginalConstructor()
            ->getMock();

        $mockConn = $this->getMockBuilder('Doctrine\DBAL\Connections\MasterSlaveConnection')
            ->disableOriginalConstructor()
            ->getMock();

        $mockConn->expects($this->any())
            ->method('getDatabasePlatform')
            ->will($this->returnValue($mockPltform));

        $mockConn->expects($this->any())
            ->method('fetchAll')
            ->will($this->returnValue(['id' => 1]));

        $mockConn->expects($this->any())
            ->method('commit')
            ->will($this->throwException(new \Exception('Connection timed out', SOCKET_ETIMEDOUT)));

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();

        $getMap = [
            ['doctrine.dbal.default_connection', 1, $mockConn],
            ['doctrine.orm.entity_manager', 1, $mockEm]
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        return $mockContainer;
    }

    /**
     * 刪除跑完測試後產生的檔案
     */
    public function tearDown()
    {
        $fileRootDir = $this->getContainer()
            ->get('kernel')
            ->getRootDir();

        $filePath = $fileRootDir . "/../duplicateUser.csv";

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $filePath = $fileRootDir . "/../usernameOver.csv";

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $filePath = $fileRootDir . "/../transferSql.csv";

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $filePath = $fileRootDir . "/../transferBackupSql.csv";

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $filePath = $fileRootDir . '/../duplicateAccountUser.csv';

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $filePath = $fileRootDir . '/../ballList.csv';

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $filePath = $fileRootDir . '/../platformList.csv';

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $logDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $logFile = $logDir . DIRECTORY_SEPARATOR . 'updateEntrySql.log';

        if (file_exists($logFile)) {
            unlink($logFile);
        }

        parent::tearDown();
    }
}
