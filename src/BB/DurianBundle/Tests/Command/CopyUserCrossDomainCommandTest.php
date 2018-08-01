<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use Buzz\Message\Response;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use BB\DurianBundle\Entity\UserLevel;
use BB\DurianBundle\Command\CopyUserCrossDomainCommand;

class CopyUserCrossDomainCommandTest extends WebTestCase
{
    /**
     * 對應檔的路徑
     *
     * @var string
     */
    private $idMapPath;

    /**
     * 會員資料對應檔的路徑
     *
     * @var string
     */
    private $memberIdMapPath;

    /**
     * log檔的路徑
     *
     * @var string
     */
    private $logFilePath;

    /**
     * rd1重送檔的路徑
     *
     * @var string
     */
    private $rd1RetryFilePath;

    /**
     * rd2重送檔的路徑
     *
     * @var string
     */
    private $rd2RetryFilePath;

    /**
     * rd3重送檔的路徑
     *
     * @var string
     */
    private $rd3RetryFilePath;

    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserDetailData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserEmailData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserPasswordData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareLimitData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareLimitNextData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelCurrencyData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareUpdateCronForControllerData'
        ];
        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigData',
        ];
        $this->loadFixtures($classnames, 'share');

        $classnames = [];
        $this->loadFixtures($classnames, 'entry');
        $this->loadFixtures($classnames, 'his');

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->find('BBDurianBundle:User', 6);
        $user->setHiddenTest(false);

        $cash = $em->find('BBDurianBundle:Cash', 3);
        $cash->setBalance(0);

        $cash = $em->find('BBDurianBundle:Cash', 4);
        $cash->setBalance(0);

        $cash = $em->find('BBDurianBundle:Cash', 5);
        $cash->setBalance(0);

        $cash = $em->find('BBDurianBundle:Cash', 6);
        $cash->setBalance(0);

        $cash = $em->find('BBDurianBundle:Cash', 7);
        $cash->setBalance(0);

        $em->flush();

        $fileRootDir = $this->getContainer()->get('kernel')->getRootDir();

        $this->idMapPath = $fileRootDir . '/../idMap.csv';
        $this->memberIdMapPath = $fileRootDir . '/../memberIdMap.csv';
        $this->logFilePath = $fileRootDir . '/../send-copy-user-message.log';
        $this->rd1RetryFilePath = $fileRootDir . '/../rd1Retry.csv';
        $this->rd2RetryFilePath = $fileRootDir . '/../rd2Retry.csv';
        $this->rd3RetryFilePath = $fileRootDir . '/../rd3Retry.csv';

        // 設置idMap.csv的資料
        file_put_contents($this->idMapPath, "4,20000010,52,wtester2,2,52,5\n", FILE_APPEND);
        file_put_contents($this->idMapPath, "5,20000011,20000010,xtester2,2,52,4\n", FILE_APPEND);
        file_put_contents($this->idMapPath, "6,20000012,20000011,ytester2,2,52,3\n", FILE_APPEND);
        file_put_contents($this->idMapPath, "7,20000013,20000012,ztester2,2,52,2\n", FILE_APPEND);

        $redis = $this->getContainer()->get('snc_redis.sequence');
        $redis->set('cash_seq', 1000);
    }

    /**
     * 測試前置檢查輸入錯誤參數(後綴詞非小寫)
     */
    public function testInputSuffixNotLowercaseWithCheck()
    {
        $parameter = [
            '--userId' => 4,
            '--beginId' => 20000010,
            '--sourceDomain' => 2,
            '--targetDomain' => 52,
            '--suffix' => 'abC',
            '--getIdMap' => true
        ];

        $output = $this->runCommand('durian:copy-user-crossDomain', $parameter);

        $this->assertContains('[Exception]', $output);
        $this->assertContains('Invalid suffix', $output);
    }

    /**
     * 測試複製管理層帳號相關資料
     */
    public function testGetIdMap()
    {
        // 因為要測試取得idMap，所以先清空idMap.csv
        unlink($this->idMapPath);
        $this->createDomain();

        $parameter = [
            '--userId' => 4,
            '--beginId' => 20000010,
            '--sourceDomain' => 2,
            '--targetDomain' => 52,
            '--suffix' => 'abc',
            '--getIdMap' => true
        ];

        $this->runCommand('durian:copy-user-crossDomain', $parameter);

        $outputFile = fopen('idMap.csv', 'r');
        $data = fgetcsv($outputFile, 1000);
        $str = implode(',', $data);
        $this->assertEquals("'4','20000010','52','wtesterabc','2','52','5'", $str);

        //驗證redis對應表
        $redis = $this->getContainer()->get('snc_redis.map');

        $this->assertEquals(52, $redis->get('user:{2001}:20000010:domain'));
        $this->assertEquals('wtesterabc', $redis->get('user:{2001}:20000010:username'));
        $this->assertEquals(52, $redis->get('user:{2001}:20000011:domain'));
        $this->assertEquals('xtesterabc', $redis->get('user:{2001}:20000011:username'));
        $this->assertEquals(52, $redis->get('user:{2001}:20000012:domain'));
        $this->assertEquals('ytesterabc', $redis->get('user:{2001}:20000012:username'));
        $this->assertEquals(52, $redis->get('user:{2001}:20000013:domain'));
        $this->assertEquals('ztesterabc', $redis->get('user:{2001}:20000013:username'));
    }

    /**
     * 測試複製會員帳號相關資料
     */
    public function testGetMemberIdMap()
    {
        // 若要取得會員層的對應資料，需要有管理層資料
        $this->copyManagerData();

        $parameter = [
            'path' => 'idMap.csv',
            '--userId' => 4,
            '--beginId' => 20000014,
            '--sourceDomain' => 2,
            '--targetDomain' => 52,
            '--suffix' => 'abc',
            '--presetLevel' => 1,
            '--getIdMap' => true,
            '--onlyMember' => true
        ];

        $this->runCommand('durian:copy-user-crossDomain', $parameter);

        $outputFile = fopen('memberIdMap.csv', 'r');
        $data = fgetcsv($outputFile, 1000);
        $sql = implode(',', $data);
        $this->assertEquals("'8','20000014','20000013','testerabc','2','52'", $sql);

        //驗證redis對應表
        $redis = $this->getContainer()->get('snc_redis.map');

        $this->assertEquals(52, $redis->get('user:{2001}:20000014:domain'));
        $this->assertEquals('testerabc', $redis->get('user:{2001}:20000014:username'));
        $this->assertEquals(52, $redis->get('user:{2001}:20000015:domain'));
        $this->assertEquals('oauthuserabc', $redis->get('user:{2001}:20000015:username'));

        //測試檢查idMap格式功能
        $parameter = [
            'path' => 'memberIdMap.csv',
            '--checkMap' => true
        ];

        $output = $this->runCommand('durian:copy-user-crossDomain', $parameter);
        $output = explode(PHP_EOL, $output);

        $this->assertEquals('第 1 筆資料格式不正確', $output[0]);
        $this->assertEquals('第 2 筆資料格式不正確', $output[1]);
        $this->assertEmpty($output[4]);
    }

    /**
     * 測試呼叫api複製體系完成後，歸零管理層隱藏測試帳號及設定層級使用者數量，並測試檢查資料庫內複製資料功能
     */
    public function testSetHiddenTestAndUpdateUserCount()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');

        // 設置餘數大於零的現金資料並新增user_level
        $cash7 = $em->find('BBDurianBundle:Cash', 7);
        $cash7->setBalance(100.0000);
        $cash9 = $em->find('BBDurianBundle:Cash', 9);
        $cash9->setBalance(200.0000);

        $user51 = $em->find('BBDurianBundle:User', 51);
        $ul51 = new UserLevel($user51, 9);
        $em->persist($ul51);
        $em->flush();

        $this->createDomain();

        // 模擬已複製管理層資料、已產生會員層資料對應資料、會員層資料複製成功
        $this->copyManagerData();
        $this->getMemberMapFile();
        $this->copyMemberData();

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = ['result' => 'ok'];
        $response->setContent(json_encode($responseContent));

        $application = new Application();
        $command = new CopyUserCrossDomainCommand();
        $command->setContainer($this->getContainer());
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        // 驗證管理層帳號狀態是隱藏測試
        $user = $em->find('BBDurianBundle:User', 20000010);
        $this->assertTrue($user->isHiddenTest());

        // 確認原本 user count
        $level = $em->find('BBDurianBundle:Level', 9);
        $this->assertEquals(0, $level->getUserCount());

        $levelCurrency = $em->find('BBDurianBundle:LevelCurrency', ['levelId' => 9, 'currency' => 901]);
        $this->assertEquals(0, $levelCurrency->getUserCount());

        $command = $application->find('durian:copy-user-crossDomain');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
                'path' => 'memberIdMap.csv',
                '--durianApi' => true,
                '--presetLevel' => 9,
                '--setHidden' => 20000010
            ]
        );

        $this->runCommand('durian:update-level-count');
        $this->runCommand('durian:update-level-currency-count');

        // 驗證資料更新成功
        $em->refresh($user);
        $em->refresh($level);
        $em->refresh($levelCurrency);

        $this->assertFalse($user->isHiddenTest());
        $this->assertEquals(2, $level->getUserCount());
        $this->assertEquals(2, $levelCurrency->getUserCount());

        // 測試複寫體系檢查功能
        $output = $this->runCommand($command->getName(), ['path' => 'memberIdMap.csv', '--check' => true]);
        $output = explode(PHP_EOL, $output);

        $this->assertEquals('user : OK(共2筆)', $output[0]);
        $this->assertEquals('user_detail : OK(共2筆)', $output[1]);
        $this->assertEquals('user_email : OK(共2筆)', $output[2]);
        $this->assertEquals('user_password : OK(共2筆)', $output[3]);
        $this->assertEquals('user_level : OK(共2筆)', $output[4]);
        $this->assertEquals('bank : OK(共5筆)', $output[5]);
        $this->assertEquals('cash : OK(共2筆)', $output[6]);
        $this->assertEquals('cash_entry : OK(共2筆)', $output[7]);
        $this->assertEquals('payment_deposit_withdraw_entry : OK(共2筆)', $output[8]);
        $this->assertEquals('level & level_currecny：OK(共有2筆)', $output[9]);
        $this->assertEquals('hidden_test：OK(管理層無隱藏測試帳號)', $output[10]);
        $this->assertEmpty($output[13]);
    }

    /**
     * 測試呼叫api複製體系完成後，歸零管理層隱藏測試帳號及設定層級使用者數量，但connection time out
     */
    public function testSetHiddenTestAndUpdateUserCountRollback()
    {
        $this->createDomain();
        // 模擬已複製管理層資料、已產生會員層資料對應資料
        $this->copyManagerData();
        $this->getMemberMapFile();

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = ['result' => 'ok'];
        $response->setContent(json_encode($responseContent));

        $application = new Application();
        $command = new CopyUserCrossDomainCommand();
        $command->setContainer($this->getMockContainer());
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        // 驗證管理層帳號狀態是隱藏測試
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $user = $em->find('BBDurianBundle:User', 20000010);
        $this->assertTrue($user->isHiddenTest());

        // 確認原本 user count
        $level = $em->find('BBDurianBundle:Level', 9);
        $this->assertEquals(0, $level->getUserCount());

        $levelCurrency = $em->find('BBDurianBundle:LevelCurrency', ['levelId' => 9, 'currency' => 901]);
        $this->assertEquals(0, $levelCurrency->getUserCount());

        $command = $application->find('durian:copy-user-crossDomain');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
                'path' => 'memberIdMap.csv',
                '--durianApi' => true,
                '--presetLevel' => 9,
                '--setHidden' => 20000010
            ]
        );

        // 驗證輸出及log
        $display = explode(PHP_EOL, $commandTester->getDisplay());
        $this->assertContains('[WARNING]update user count or set hidden test failed, because Connection timed out', $display[2]);

        $fileRootDir = $this->getContainer()->get('kernel')->getRootDir();
        $filePath = $fileRootDir . '/logs/test/copy_user_crossDomain.log';

        $content = file_get_contents($filePath);
        $log = explode(PHP_EOL, $content);

        $this->assertContains('[WARNING]update user count or set hidden test failed, because Connection timed out', $log[2]);

        // 驗證資料沒有更新
        $em->refresh($user);
        $em->refresh($level);
        $em->refresh($levelCurrency);

        $this->assertTrue($user->isHiddenTest());
        $this->assertEquals(0, $levelCurrency->getUserCount());
        $this->assertEquals(0, $level->getUserCount());
    }

    /**
     * 測試呼叫api複製體系失敗，歸零管理層隱藏測試帳號及設定層級使用者數量沒執行
     */
    public function testSetHiddenTestAndUpdateUserCountWhenCallApiFailed()
    {
        $this->createDomain();
        // 模擬已複製管理層資料、已產生會員層資料對應資料
        $this->copyManagerData();
        $this->getMemberMapFile();

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = ['result' => 'error'];
        $response->setContent(json_encode($responseContent));

        $application = new Application();
        $command = new CopyUserCrossDomainCommand();
        $command->setContainer($this->getMockContainer());
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        // 驗證管理層帳號狀態是隱藏測試
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $user = $em->find('BBDurianBundle:User', 20000010);
        $this->assertTrue($user->isHiddenTest());

        // 確認原本 user count
        $level = $em->find('BBDurianBundle:Level', 9);
        $this->assertEquals(0, $level->getUserCount());

        $levelCurrency = $em->find('BBDurianBundle:LevelCurrency', ['levelId' => 9, 'currency' => 901]);
        $this->assertEquals(0, $levelCurrency->getUserCount());

        $command = $application->find('durian:copy-user-crossDomain');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
                'path' => 'memberIdMap.csv',
                '--durianApi' => true,
                '--presetLevel' => 9,
                '--setHidden' => 52
            ]
        );

        // 驗證資料沒有更新
        $em->refresh($user);
        $em->refresh($level);
        $em->refresh($levelCurrency);

        $this->assertTrue($user->isHiddenTest());
        $this->assertEquals(0, $levelCurrency->getUserCount());
        $this->assertEquals(0, $level->getUserCount());

        // 驗證複寫失敗時寫入failed.csv
        $outputFile = fopen('failed.csv', 'r');
        $data = fgetcsv($outputFile, 1000);
        $result = implode(',', $data);
        $this->assertEquals('8,20000014,20000013,tester2,2,52,1', $result);
    }

    /**
     * 測試複寫體系多線程呼叫歸零管理層隱藏測試帳號及設定層級使用者數量
     */
    public function testSetHiddenTestAndUpdateUserCountButNoCallApi()
    {
        $this->createDomain();
        // 模擬已複製管理層資料、已產生會員層資料對應資料、已複製會員資料
        $this->copyManagerData();
        $this->getMemberMapFile();
        $this->copyMemberData();

        $application = new Application();
        $command = new CopyUserCrossDomainCommand();
        $command->setContainer($this->getContainer());
        $application->add($command);

        // 驗證管理層帳號狀態是隱藏測試
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $user = $em->find('BBDurianBundle:User', 20000010);
        $this->assertTrue($user->isHiddenTest());

        // 確認原本 user count
        $level = $em->find('BBDurianBundle:Level', 9);
        $this->assertEquals(0, $level->getUserCount());

        $levelCurrency = $em->find('BBDurianBundle:LevelCurrency', ['levelId' => 9, 'currency' => 901]);
        $this->assertEquals(0, $levelCurrency->getUserCount());

        $command = $application->find('durian:copy-user-crossDomain');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
                'path' => 'memberIdMap.csv',
                '--presetLevel' => 9,
                '--setHidden' => 20000010
            ]
        );

        $this->runCommand('durian:update-level-count');
        $this->runCommand('durian:update-level-currency-count');

        // 驗證資料更新成功
        $em->refresh($user);
        $em->refresh($level);
        $em->refresh($levelCurrency);

        $this->assertFalse($user->isHiddenTest());
        $this->assertEquals(2, $levelCurrency->getUserCount());
        $this->assertEquals(2, $level->getUserCount());
    }

    /**
     * 測試呼叫api複製體系，並測試檢查功能
     */
    public function testCopyUserByDurianApi()
    {
        $this->createDomain();
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');

        // 塞入現金餘額資料
        $cash3 = $em->find('BBDurianBundle:Cash', 3);
        $cash3->setBalance(100);

        $cash4 = $em->find('BBDurianBundle:Cash', 4);
        $cash4->setBalance(-100);

        $cash2 = $em->find('BBDurianBundle:Cash', 5);
        $cash2->setBalance(100);

        $em->flush();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = ['result' => 'ok'];
        $response->setContent(json_encode($responseContent));

        $application = new Application();
        $command = new CopyUserCrossDomainCommand();
        $command->setContainer($this->getContainer());
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        // 測試呼叫api複製體系
        $command = $application->find('durian:copy-user-crossDomain');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), 'path' => 'idMap.csv', '--durianApi' => true]);

        $this->copyManagerData();

        // 測試檢查功能
        $commandTester->execute(['command' => $command->getName(), 'path' => 'idMap.csv', '--check' => true]);
        $output = explode(PHP_EOL, $commandTester->getDisplay());

        $this->assertEquals('user : OK(共4筆)', $output[0]);
        $this->assertEquals('user_detail : OK(共4筆)', $output[1]);
        $this->assertEquals('user_email : OK(共4筆)', $output[2]);
        $this->assertEquals('user_password : OK(共4筆)', $output[3]);
        $this->assertEquals('bank : OK(共0筆)', $output[4]);
        $this->assertEquals('cash : OK(共4筆)', $output[5]);
        $this->assertEquals('share_limit : OK(共4筆)', $output[6]);
        $this->assertEquals('share_limit_next : OK(共4筆)', $output[7]);
        $this->assertEquals('cash_entry : OK(共2筆)', $output[8]);
        $this->assertEquals('payment_deposit_withdraw_entry : OK(共2筆)', $output[9]);
        $this->assertEmpty($output[12]);
    }

    /**
     * 測試管理層呼叫api複製體系失敗情況，並測試檢查功能
     */
    public function testCopyUserByDurianApiWhenFailed()
    {
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = ['result' => 'error'];
        $response->setContent(json_encode($responseContent));

        $application = new Application();
        $command = new CopyUserCrossDomainCommand();
        $command->setContainer($this->getContainer());
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:copy-user-crossDomain');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
                'path' => 'idMap.csv',
                '--presetLevel' => 9,
                '--durianApi' => true,
                '--setHidden' => 20000010
            ]
        );

        $outputFile = fopen('failed.csv', 'r');
        $data = fgetcsv($outputFile, 1000);
        $result = implode(',', $data);
        $this->assertEquals("4,20000010,52,wtester2,2,52,5", $result);

        // 測試檢查功能
        $commandTester->execute(['command' => $command->getName(), 'path' => 'idMap.csv', '--check' => true]);
        $output = explode(PHP_EOL, $commandTester->getDisplay());

        $this->assertEquals('user : 原數量: 4 ，複寫數量: 0 資料量不相符', $output[0]);
        $this->assertEquals('user_detail : 原數量: 4 ，複寫數量: 0 資料量不相符', $output[1]);
        $this->assertEquals('user_email : 原數量: 4 ，複寫數量: 0 資料量不相符', $output[2]);
        $this->assertEquals('user_password : 原數量: 4 ，複寫數量: 0 資料量不相符', $output[3]);
        $this->assertEquals('bank : OK(共0筆)', $output[4]);
        $this->assertEquals('cash : 原數量: 4 ，複寫數量: 0 資料量不相符', $output[5]);
        $this->assertEquals('share_limit : 原數量: 4 ，複寫數量: 0 資料量不相符', $output[6]);
        $this->assertEquals('share_limit_next : 原數量: 4 ，複寫數量: 0 資料量不相符', $output[7]);
        $this->assertEquals('cash_entry : OK(共0筆)', $output[8]);
        $this->assertEquals('payment_deposit_withdraw_entry : OK(共0筆)', $output[9]);
        $this->assertEmpty($output[12]);
    }

    /**
     * 測試呼叫各組 api 設定複製體系詳細設定讀不到檔
     */
    public function testSendCopyUserMessageButFileNotExist()
    {
        $params = [
            '--target' => 'rd1',
            '--idMapFile' => 'notExist.csv'
        ];

        $output = $this->runCommand('durian:copy-user-crossDomain', $params);

        $output = explode("\n", $output);
        $this->assertEquals('資料檔案不存在', trim($output[0]));
    }

    /**
     * 測試呼叫 RD1 api 設定複製體系詳細設定時 Curl 失敗
     */
    public function testSendCopyUserMessageToRD1ButCurlFailed()
    {
        $params = [
            '--target' => 'rd1',
            '--idMapFile' => 'idMap.csv'
        ];

        $output = $this->runCommand('durian:copy-user-crossDomain', $params);

        $output = explode("\n", $output);

        $this->assertEquals('RD1設定成功筆數 : 0', $output[0]);
        $this->assertEquals('RD1設定失敗筆數 : 4', $output[1]);
        $this->assertEquals('RD1需重送名單 : rd1Retry.csv', $output[2]);
        $this->assertEmpty($output[3]);

        // 驗證 retry.csv
        $content = file_get_contents($this->rd1RetryFilePath);
        $results = explode(PHP_EOL, $content);

        $this->assertEquals('4,20000010,52,wtester2,2,52,5', $results[0]);
        $this->assertEquals('5,20000011,20000010,xtester2,2,52,4', $results[1]);
        $this->assertEquals('6,20000012,20000011,ytester2,2,52,3', $results[2]);
        $this->assertEquals('7,20000013,20000012,ztester2,2,52,2', $results[3]);
        $this->assertEmpty($results[4]);

        // 驗證 send-copy-user-message.log
        $content = file_get_contents($this->logFilePath);
        $results = explode(PHP_EOL, $content);

        $this->assertEquals("Exception : <url> malformed", $results[0]);
        $this->assertEquals("Exception : <url> malformed", $results[1]);
        $this->assertEquals("Exception : <url> malformed", $results[2]);
        $this->assertEquals("Exception : <url> malformed", $results[3]);
        $this->assertEmpty($results[4]);
    }

    /**
     * 測試呼叫 RD2 api 設定複製體系詳細設定時 Curl 失敗
     */
    public function testSendCopyUserMessageToRD2ButCurlFailed()
    {
        // 設定一筆子帳號
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $user = $em->find('BBDurianBundle:User', 7);
        $user->setSub(true);
        $em->flush();

        $params = [
            '--target' => 'rd2',
            '--idMapFile' => 'idMap.csv'
        ];

        $output = $this->runCommand('durian:copy-user-crossDomain', $params);

        $output = explode("\n", $output);

        $this->assertEquals('RD2設定成功筆數 : 0', $output[0]);
        $this->assertEquals('RD2設定失敗筆數 : 3', $output[1]);
        $this->assertEquals('RD2子帳號不須傳送筆數 : 1', $output[2]);
        $this->assertEquals('RD2需重送名單 : rd2Retry.csv', $output[3]);
        $this->assertEmpty($output[4]);

        // 驗證 retry.csv (會少一筆子帳號資料)
        $content = file_get_contents($this->rd2RetryFilePath);
        $results = explode(PHP_EOL, $content);

        $this->assertEquals('4,20000010,52,wtester2,2,52,5', $results[0]);
        $this->assertEquals('5,20000011,20000010,xtester2,2,52,4', $results[1]);
        $this->assertEquals('6,20000012,20000011,ytester2,2,52,3', $results[2]);
        $this->assertEmpty($results[3]);

        // 驗證 send-copy-user-message.log
        $content = file_get_contents($this->logFilePath);
        $results = explode(PHP_EOL, $content);

        $this->assertEquals("Exception : <url> malformed", $results[0]);
        $this->assertEquals("Exception : <url> malformed", $results[1]);
        $this->assertEquals("Exception : <url> malformed", $results[2]);
        $this->assertEmpty($results[3]);
    }

    /**
     * 測試呼叫 RD3 api 設定複製體系詳細設定時 Curl 失敗
     */
    public function testSendCopyUserMessageToRD3ButCurlFailed()
    {
        $params = [
            '--target' => 'rd3',
            '--idMapFile' => 'idMap.csv'
        ];

        $output = $this->runCommand('durian:copy-user-crossDomain', $params);
        $output = explode("\n", $output);

        $this->assertEquals('RD3設定成功筆數 : 0', $output[0]);
        $this->assertEquals('RD3設定失敗筆數 : 4', $output[1]);
        $this->assertEquals('RD3需重送名單 : rd3Retry.csv', $output[2]);
        $this->assertEmpty($output[3]);

        // 驗證 retry.csv
        $content = file_get_contents($this->rd3RetryFilePath);
        $results = explode(PHP_EOL, $content);

        $this->assertEquals('4,20000010,52,wtester2,2,52,5', $results[0]);
        $this->assertEquals('5,20000011,20000010,xtester2,2,52,4', $results[1]);
        $this->assertEquals('6,20000012,20000011,ytester2,2,52,3', $results[2]);
        $this->assertEquals('7,20000013,20000012,ztester2,2,52,2', $results[3]);
        $this->assertEmpty($results[4]);

        // 驗證 send-copy-user-message.log
        $content = file_get_contents($this->logFilePath);
        $results = explode(PHP_EOL, $content);

        $this->assertEquals("Exception : <url> malformed", $results[0]);
        $this->assertEquals("Exception : <url> malformed", $results[1]);
        $this->assertEquals("Exception : <url> malformed", $results[2]);
        $this->assertEquals("Exception : <url> malformed", $results[3]);
        $this->assertEmpty($results[4]);
    }

    /**
     * 測試重送 RD1 設定複製體系詳細設定 api 時 status code 500
     */
    public function testSendCopyUserMessageButStatusCodeNot200()
    {
        // 資料寫入重送檔案
        file_put_contents($this->rd1RetryFilePath, "4,20000010,52,wtester2,2,52,5\n", FILE_APPEND);
        file_put_contents($this->rd1RetryFilePath, "5,20000011,20000010,xtester2,2,52,4\n", FILE_APPEND);
        file_put_contents($this->rd1RetryFilePath, "6,20000012,20000011,ytester2,2,52,3\n", FILE_APPEND);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 500');
        $responseContent = ['message' => 'error'];
        $response->setContent(json_encode($responseContent));

        $application = new Application();
        $command = new CopyUserCrossDomainCommand();
        $command->setContainer($this->getContainer());
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:copy-user-crossDomain');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), '--retry' => true, '--target' => 'rd1']);

        $output = explode(PHP_EOL, $commandTester->getDisplay());

        $this->assertEquals('RD1設定成功筆數 : 0', $output[0]);
        $this->assertEquals('RD1設定失敗筆數 : 3', $output[1]);
        $this->assertEquals('RD1需重送名單 : rd1Retry.csv', $output[2]);
        $this->assertEmpty($output[3]);

        // 驗證 retry.csv
        $content = file_get_contents($this->rd1RetryFilePath);
        $results = explode(PHP_EOL, $content);

        $this->assertEquals('4,20000010,52,wtester2,2,52,5', $results[0]);
        $this->assertEquals('5,20000011,20000010,xtester2,2,52,4', $results[1]);
        $this->assertEquals('6,20000012,20000011,ytester2,2,52,3', $results[2]);
        $this->assertEmpty($results[3]);

        // 驗證 send-copy-user-message.log
        $content = file_get_contents($this->logFilePath);
        $results = explode(PHP_EOL, $content);

        $this->assertEquals("Status code not 200", $results[0]);
        $this->assertEquals("Status code not 200", $results[1]);
        $this->assertEquals("Status code not 200", $results[2]);
        $this->assertEmpty($results[3]);
    }

    /**
     * 測試重送 RD1 設定複製體系詳細設定 api 時沒有回傳內容
     */
    public function testSendCopyUserMessageButNoResponseContent()
    {
        // 資料寫入重送檔案
        file_put_contents($this->rd1RetryFilePath, "4,20000010,52,wtester2,2,52,5\n", FILE_APPEND);
        file_put_contents($this->rd1RetryFilePath, "5,20000011,20000010,xtester2,2,52,4\n", FILE_APPEND);
        file_put_contents($this->rd1RetryFilePath, "6,20000012,20000011,ytester2,2,52,3\n", FILE_APPEND);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent('');

        $application = new Application();
        $command = new CopyUserCrossDomainCommand();
        $command->setContainer($this->getContainer());
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:copy-user-crossDomain');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), '--retry' => true, '--target' => 'rd1']);

        $output = explode(PHP_EOL, $commandTester->getDisplay());

        $this->assertEquals('RD1設定成功筆數 : 0', $output[0]);
        $this->assertEquals('RD1設定失敗筆數 : 3', $output[1]);
        $this->assertEquals('RD1需重送名單 : rd1Retry.csv', $output[2]);
        $this->assertEmpty($output[3]);

        // 驗證 retry.csv
        $content = file_get_contents($this->rd1RetryFilePath);
        $results = explode(PHP_EOL, $content);

        $this->assertEquals('4,20000010,52,wtester2,2,52,5', $results[0]);
        $this->assertEquals('5,20000011,20000010,xtester2,2,52,4', $results[1]);
        $this->assertEquals('6,20000012,20000011,ytester2,2,52,3', $results[2]);
        $this->assertEmpty($results[3]);

        // 驗證 send-copy-user-message.log
        $content = file_get_contents($this->logFilePath);
        $results = explode(PHP_EOL, $content);

        $this->assertEquals("Decode error or no result with content : ", $results[0]);
        $this->assertEquals("Decode error or no result with content : ", $results[1]);
        $this->assertEquals("Decode error or no result with content : ", $results[2]);
        $this->assertEmpty($results[3]);
    }

    /**
     * 測試重送 RD1 設定複製體系詳細設定 api
     */
    public function testSendCopyUserMessageTargetRD1()
    {
        // 資料寫入重送檔案
        file_put_contents($this->rd1RetryFilePath, "4,20000010,52,wtester2,2,52,5\n", FILE_APPEND);
        file_put_contents($this->rd1RetryFilePath, "5,20000011,20000010,xtester2,2,52,4\n", FILE_APPEND);
        file_put_contents($this->rd1RetryFilePath, "6,20000012,20000011,ytester2,2,52,3\n", FILE_APPEND);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = ['message' => 'ok'];
        $response->setContent(json_encode($responseContent));

        $application = new Application();
        $command = new CopyUserCrossDomainCommand();
        $command->setContainer($this->getContainer());
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:copy-user-crossDomain');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), '--retry' => true, '--target' => 'rd1']);

        $output = explode(PHP_EOL, $commandTester->getDisplay());

        $this->assertEquals('RD1設定成功筆數 : 3', $output[0]);
        $this->assertEquals('RD1設定失敗筆數 : 0', $output[1]);
        $this->assertEmpty($output[2]);

        // 驗證 retry.csv 已刪除
        $this->assertFileNotExists($this->rd1RetryFilePath);

        // 驗證 send-copy-user-message.log
        $content = file_get_contents($this->logFilePath);
        $results = explode(PHP_EOL, $content);

        $this->assertEquals('{"message":"ok"}', $results[0]);
        $this->assertEquals('{"message":"ok"}', $results[1]);
        $this->assertEquals('{"message":"ok"}', $results[2]);
        $this->assertEmpty($results[3]);
    }

    /**
     * 測試重送 RD2 設定複製體系詳細設定 api
     */
    public function testSendCopyUserMessageTargetRD2()
    {
        // 資料寫入重送檔案
        file_put_contents($this->rd2RetryFilePath, "4,20000010,52,wtester2,2,52,5\n", FILE_APPEND);
        file_put_contents($this->rd2RetryFilePath, "5,20000011,20000010,xtester2,2,52,4\n", FILE_APPEND);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = ['result' => true];
        $response->setContent(json_encode($responseContent));

        $application = new Application();
        $command = new CopyUserCrossDomainCommand();
        $command->setContainer($this->getContainer());
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:copy-user-crossDomain');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), '--retry' => true, '--target' => 'rd2']);

        $output = explode(PHP_EOL, $commandTester->getDisplay());

        $this->assertEquals('RD2設定成功筆數 : 2', $output[0]);
        $this->assertEquals('RD2設定失敗筆數 : 0', $output[1]);
        $this->assertEquals('RD2子帳號不須傳送筆數 : 0', $output[2]);
        $this->assertEmpty($output[3]);

        // 驗證 retry.csv 已刪除
        $this->assertFileNotExists($this->rd2RetryFilePath);

        // 驗證 send-copy-user-message.log
        $content = file_get_contents($this->logFilePath);
        $results = explode(PHP_EOL, $content);

        $this->assertEquals('{"result":true}', $results[0]);
        $this->assertEquals('{"result":true}', $results[1]);
        $this->assertEmpty($results[2]);
    }

    /**
     * 測試重送 RD3 設定複製體系詳細設定 api
     */
    public function testSendCopyUserMessageTargetRD3()
    {
        // 資料寫入重送檔案
        file_put_contents($this->rd3RetryFilePath, "4,20000010,52,wtester2,2,52,5\n", FILE_APPEND);
        file_put_contents($this->rd3RetryFilePath, "5,20000011,20000010,xtester2,2,52,4\n", FILE_APPEND);
        file_put_contents($this->rd3RetryFilePath, "6,20000012,20000011,ytester2,2,52,3\n", FILE_APPEND);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = ['result' => true];
        $response->setContent(json_encode($responseContent));

        $application = new Application();
        $command = new CopyUserCrossDomainCommand();
        $command->setContainer($this->getContainer());
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:copy-user-crossDomain');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), '--retry' => true, '--target' => 'rd3']);

        $output = explode(PHP_EOL, $commandTester->getDisplay());

        $this->assertEquals('RD3設定成功筆數 : 3', $output[0]);
        $this->assertEquals('RD3設定失敗筆數 : 0', $output[1]);
        $this->assertEmpty($output[2]);

        // 驗證 retry.csv 已刪除
        $this->assertFileNotExists($this->rd3RetryFilePath);

        // 驗證 send-copy-user-message.log
        $content = file_get_contents($this->logFilePath);
        $results = explode(PHP_EOL, $content);

        $this->assertEquals('{"result":true}', $results[0]);
        $this->assertEquals('{"result":true}', $results[1]);
        $this->assertEquals('{"result":true}', $results[2]);
        $this->assertEmpty($results[3]);
    }

    /**
     * 測試檢查 idMap.csv 帶管理層資料
     */
    public function testCheckMapWithManager()
    {
        $application = new Application();
        $command = new CopyUserCrossDomainCommand();
        $command->setContainer($this->getContainer());
        $application->add($command);

        $command = $application->find('durian:copy-user-crossDomain');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), 'path' => 'idMap.csv', '--checkMap' => true]);
        $output = explode(PHP_EOL, $commandTester->getDisplay());

        $this->assertEquals('資料正確。共有 4 筆資料', $output[0]);
        $this->assertEmpty($output[3]);
    }

    /**
     * 測試檢查 memberIdMap.csv 帶會員層資料
     */
    public function testCheckMapWithMember()
    {
        $this->getMemberMapFile();

        $application = new Application();
        $command = new CopyUserCrossDomainCommand();
        $command->setContainer($this->getContainer());
        $application->add($command);

        $command = $application->find('durian:copy-user-crossDomain');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), 'path' => 'memberIdMap.csv', '--checkMap' => true]);
        $output = explode(PHP_EOL, $commandTester->getDisplay());

        $this->assertEquals('資料正確。共有 2 筆資料', $output[0]);
        $this->assertEmpty($output[3]);
    }

    /**
     * 測試檢查 memberIdMap.csv，但檔案不存在
     */
    public function testCheckMapButFileNotExist()
    {
        $application = new Application();
        $command = new CopyUserCrossDomainCommand();
        $command->setContainer($this->getContainer());
        $application->add($command);

        $command = $application->find('durian:copy-user-crossDomain');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), 'path' => 'memberIdMap.csv', '--checkMap' => true]);
        $output = explode(PHP_EOL, $commandTester->getDisplay());

        $this->assertEquals('資料檔案不存在', $output[0]);
        $this->assertEmpty($output[3]);
    }

    /**
     * 測試檢查 memberIdMap.csv，但資料有遺失
     */
    public function testCheckMapButMissingData()
    {
        file_put_contents($this->memberIdMapPath, "8,10008,10007,tester2,2,52\n", FILE_APPEND);

        $application = new Application();
        $command = new CopyUserCrossDomainCommand();
        $command->setContainer($this->getContainer());
        $application->add($command);

        $command = $application->find('durian:copy-user-crossDomain');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), 'path' => 'memberIdMap.csv', '--checkMap' => true]);
        $output = explode(PHP_EOL, $commandTester->getDisplay());

        $this->assertEquals('資料筆數不正確。預期筆數：2 實際筆數：1', $output[0]);
        $this->assertEmpty($output[3]);
    }

    /**
     * 測試檢查 memberIdMap.csv，但資料量超出預期
     */
    public function testCheckMapFileButDataTooMany()
    {
        file_put_contents($this->memberIdMapPath, "8,20000014,20000013,tester2,2,52\n", FILE_APPEND);
        file_put_contents($this->memberIdMapPath, "8,20000014,20000013,tester2,2,52\n", FILE_APPEND);
        file_put_contents($this->memberIdMapPath, "8,20000014,20000013,tester2,2,52\n", FILE_APPEND);
        file_put_contents($this->memberIdMapPath, "8,20000014,20000013,tester2,2,52\n", FILE_APPEND);

        $application = new Application();
        $command = new CopyUserCrossDomainCommand();
        $command->setContainer($this->getContainer());
        $application->add($command);

        $command = $application->find('durian:copy-user-crossDomain');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), 'path' => 'memberIdMap.csv', '--checkMap' => true]);
        $output = explode(PHP_EOL, $commandTester->getDisplay());

        $this->assertEquals('資料筆數不正確。預期筆數：2 實際筆數：4', $output[0]);
        $this->assertEmpty($output[3]);
    }

    /**
     * 測試檢查複寫管理層資料量
     */
    public function testCheckManagerDataCount()
    {
        $this->createDomain();
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.sequence');
        $redis->set('cash_seq', 10000);

        $cash3 = $em->find('BBDurianBundle:Cash', 3);
        $cash3->setBalance(100);

        $cash4 = $em->find('BBDurianBundle:Cash', 4);
        $cash4->setBalance(-100);

        $cash2 = $em->find('BBDurianBundle:Cash', 5);
        $cash2->setBalance(100);

        $em->flush();

        $this->copyManagerData();

        $application = new Application();
        $command = new CopyUserCrossDomainCommand();
        $command->setContainer($this->getContainer());
        $application->add($command);

        $command = $application->find('durian:copy-user-crossDomain');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), 'path' => 'idMap.csv', '--check' => true]);
        $output = explode(PHP_EOL, $commandTester->getDisplay());

        $this->assertEquals('user : OK(共4筆)', $output[0]);
        $this->assertEquals('user_detail : OK(共4筆)', $output[1]);
        $this->assertEquals('user_email : OK(共4筆)', $output[2]);
        $this->assertEquals('user_password : OK(共4筆)', $output[3]);
        $this->assertEquals('bank : OK(共0筆)', $output[4]);
        $this->assertEquals('cash : OK(共4筆)', $output[5]);
        $this->assertEquals('share_limit : OK(共4筆)', $output[6]);
        $this->assertEquals('share_limit_next : OK(共4筆)', $output[7]);
        $this->assertEquals('cash_entry : OK(共2筆)', $output[8]);
        $this->assertEquals('payment_deposit_withdraw_entry : OK(共2筆)', $output[9]);
        $this->assertEmpty($output[12]);
    }

    /**
     * 測試檢查複寫會員層資料量，但少執行一筆
     */
    public function testCheckMemberDataCountButMissingData()
    {
        $this->createDomain();
        $this->copyManagerData();
        file_put_contents($this->memberIdMapPath, "8,20000014,20000013,tester2,2,52\n", FILE_APPEND);

        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $level = $em->find('BBDurianBundle:Level', 9);
        $level->setUserCount(1);

        $repo = $em->getRepository('BBDurianBundle:LevelCurrency');
        $levelCurrs = $repo->findBy(['levelId' => 9, 'currency' => 901]);
        $levelCurrs[0]->setUserCount(1);

        $em->flush();

        $client = $this->createClient();
        $params = [
            'old_user_id' => 8,
            'new_user_id' => 20000014,
            'new_parent_id' => 20000013,
            'username' => 'tester2',
            'source_domain' => 2,
            'target_domain' => 52,
            'role' => 1,
            'preset_level' => 9
        ];
        $client->request('POST', '/api/customize/user/copy', $params);

        $application = new Application();
        $command = new CopyUserCrossDomainCommand();
        $command->setContainer($this->getContainer());
        $application->add($command);

        $command = $application->find('durian:copy-user-crossDomain');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), 'path' => 'memberIdMap.csv', '--check' => true]);
        $output = explode(PHP_EOL, $commandTester->getDisplay());

        $this->assertEquals('user : 原數量: 2 ，複寫數量: 1 資料量不相符', $output[0]);
        $this->assertEquals('user_detail : 原數量: 2 ，複寫數量: 1 資料量不相符', $output[1]);
        $this->assertEquals('user_email : 原數量: 2 ，複寫數量: 1 資料量不相符', $output[2]);
        $this->assertEquals('user_password : 原數量: 2 ，複寫數量: 1 資料量不相符', $output[3]);
        $this->assertEquals('user_level : 原數量: 2 ，複寫數量: 1 資料量不相符', $output[4]);
        $this->assertEquals('bank : OK(共5筆)', $output[5]);
        $this->assertEquals('cash : 原數量: 2 ，複寫數量: 1 資料量不相符', $output[6]);
        $this->assertEquals('cash_entry : OK(共0筆)', $output[7]);
        $this->assertEquals('payment_deposit_withdraw_entry : OK(共0筆)', $output[8]);
        $this->assertEquals('level & level_currecny：OK(共有1筆)', $output[9]);
        $this->assertEquals('hidden_test：仍有未解除隱藏測試帳號的使用者，共有4筆', $output[10]);
        $this->assertEmpty($output[13]);
    }

    /**
     * 測試檢查複寫會員層資料量，但層級的會員數量未成功更新
     */
    public function testCheckMemberButLevelUserCountNotUpdate()
    {
        $this->createDomain();
        $this->copyManagerData();
        $this->getMemberMapFile();
        $this->copyMemberData();

        $application = new Application();
        $command = new CopyUserCrossDomainCommand();
        $command->setContainer($this->getContainer());
        $application->add($command);

        $command = $application->find('durian:copy-user-crossDomain');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), 'path' => 'memberIdMap.csv', '--check' => true]);
        $output = explode(PHP_EOL, $commandTester->getDisplay());

        $this->assertEquals('level：共0名會員，預期應有2名會員，相差2名', $output[9]);
        $this->assertEquals('level_currency：共0名會員，預期應有2名會員，相差2名', $output[10]);
        $this->assertEquals('hidden_test：仍有未解除隱藏測試帳號的使用者，共有4筆', $output[11]);
        $this->assertEmpty($output[14]);
    }

    /**
     * 測試檢查複寫會員層資料量，但 level 的會員數與 level_currency 會員數不同
     */
    public function testCheckMemberButLevelAndLevelCurrencyNotEqual()
    {
        $this->createDomain();
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $level = $em->find('BBDurianBundle:Level', 9);
        $level->setUserCount(2);

        $repo = $em->getRepository('BBDurianBundle:LevelCurrency');
        $levelCurrs = $repo->findBy(['levelId' => 9, 'currency' => 901]);
        $levelCurrs[0]->setUserCount(1);

        $em->flush();

        $this->copyManagerData();
        $this->getMemberMapFile();
        $this->copyMemberData();

        $application = new Application();
        $command = new CopyUserCrossDomainCommand();
        $command->setContainer($this->getContainer());
        $application->add($command);

        $command = $application->find('durian:copy-user-crossDomain');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), 'path' => 'memberIdMap.csv', '--check' => true]);
        $output = explode(PHP_EOL, $commandTester->getDisplay());

        $this->assertEquals('level：共2名會員，level_currency：共1名會員，相差1名', $output[9]);
        $this->assertEquals('level_currency：共1名會員，預期應有2名會員，相差1名', $output[10]);
        $this->assertEquals('hidden_test：仍有未解除隱藏測試帳號的使用者，共有4筆', $output[11]);
        $this->assertEmpty($output[14]);
    }

    /**
     * 建立目標廳的相關資料
     */
    private function createDomain()
    {
        $client = $this->createClient();

        $parameters = [
            'role' => 7,
            'login_code' => 'bab',
            'username' => 'testremove7',
            'password' => 'testremove7',
            'alias' => 'testremove7',
            'name' => 'testremove7',
            'cash' => ['currency' => 'CNY']
        ];

        $client->request('POST', '/api/user', $parameters);

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

        $parameters = ['level_id' => 9];
        $client->request('POST', '/api/user/52/preset_level', $parameters);
    }

    /**
     * 取得會員層的idMap File資料
     */
    private function getMemberMapFile() {
        file_put_contents($this->memberIdMapPath, "8,20000014,20000013,tester2,2,52\n", FILE_APPEND);
        file_put_contents($this->memberIdMapPath, "51,20000015,20000013,oauthuser2,2,52\n", FILE_APPEND);
    }

    /**
     * 複寫管理層資料
     */
    private function copyManagerData()
    {
        $client = $this->createClient();

        $params = [
            'old_user_id' => 4,
            'new_user_id' => 20000010,
            'new_parent_id' => 52,
            'username' => 'wtester2',
            'source_domain' => 2,
            'target_domain' => 52,
            'role' => 5
        ];
        $client->request('POST', '/api/customize/user/copy', $params);

        $params = [
            'old_user_id' => 5,
            'new_user_id' => 20000011,
            'new_parent_id' => 20000010,
            'username' => 'xtester2',
            'source_domain' => 2,
            'target_domain' => 52,
            'role' => 4
        ];
        $client->request('POST', '/api/customize/user/copy', $params);

        $params = [
            'old_user_id' => 6,
            'new_user_id' => 20000012,
            'new_parent_id' => 20000011,
            'username' => 'ytester2',
            'source_domain' => 2,
            'target_domain' => 52,
            'role' => 3
        ];
        $client->request('POST', '/api/customize/user/copy', $params);

        $params = [
            'old_user_id' => 7,
            'new_user_id' => 20000013,
            'new_parent_id' => 20000012,
            'username' => 'ztester2',
            'source_domain' => 2,
            'target_domain' => 52,
            'role' => 2
        ];
        $client->request('POST', '/api/customize/user/copy', $params);
        $this->runCommand('durian:run-cash-poper');
    }

    /**
     * 複寫會員層資料
     */
    private function copyMemberData()
    {
        $client = $this->createClient();

        $params = [
            'old_user_id' => 8,
            'new_user_id' => 20000014,
            'new_parent_id' => 20000013,
            'username' => 'tester2',
            'source_domain' => 2,
            'target_domain' => 52,
            'role' => 1,
            'preset_level' => 9
        ];
        $client->request('POST', '/api/customize/user/copy', $params);

        $params = [
            'old_user_id' => 51,
            'new_user_id' => 20000015,
            'new_parent_id' => 20000013,
            'username' => 'oauthuser2',
            'source_domain' => 2,
            'target_domain' => 52,
            'role' => 1,
            'preset_level' => 9
        ];
        $client->request('POST', '/api/customize/user/copy', $params);
        $this->runCommand('durian:run-cash-poper');
    }

    /**
     * 取得 MockContainer
     *
     * @return \Symfony\Component\DependencyInjection\Container
     */
    private function getMockContainer()
    {
        $mockConn = $this->getMockBuilder('Doctrine\DBAL\Connections\MasterSlaveConnection')
            ->disableOriginalConstructor()
            ->setMethods(['executeQuery', 'connect'])
            ->getMock();

        $mockQuery = $this->getMockBuilder('\Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['fetchColumn', 'fetchAll'])
            ->getMockForAbstractClass();

        $results = [
            0 => [
                'level_id' => 2
            ]
        ];

        $mockQuery->expects($this->any())
            ->method('fetchAll')
            ->willReturn($results);

        $mockConn->expects($this->any())
            ->method('executeQuery')
            ->willReturn($mockQuery);

        $mockRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['setHiddenTestUserOffAllChildForCopyUser'])
            ->getMock();

        $mockEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository', 'beginTransaction', 'flush', 'rollback', 'find'])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockRepo);

        $mockUser = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();

        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($mockUser);

        $mockEm->expects($this->any())
            ->method('flush')
            ->will($this->throwException(new \Exception('Connection timed out', SOCKET_ETIMEDOUT)));

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->disableOriginalConstructor()
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $connEntry = $this->getContainer()->get('doctrine.dbal.entry_connection');
        $logger = $this->getContainer()->get('logger');
        $handler = $this->getContainer()->get('monolog.handler.copy_user_crossDomain');
        $kernel = $this->getContainer()->get('kernel');

        $getMap = [
            ['doctrine.orm.default_entity_manager', 1, $mockEm],
            ['doctrine.orm.entry_entity_manager', 1, $emEntry],
            ['doctrine.dbal.default_connection', 1, $mockConn],
            ['doctrine.dbal.entry_connection', 1, $connEntry],
            ['logger', 1, $logger],
            ['monolog.handler.copy_user_crossDomain', 1, $handler],
            ['kernel', 1, $kernel]
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

        $filePath = $fileRootDir . '/logs/test/copy_user_crossDomain.log';

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $filePath = $fileRootDir . "/../memberIdMap.csv";

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $filePath = $fileRootDir . "/../failed.csv";

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        if (file_exists($this->rd1RetryFilePath)) {
            unlink($this->rd1RetryFilePath);
        }

        if (file_exists($this->rd2RetryFilePath)) {
            unlink($this->rd2RetryFilePath);
        }

        if (file_exists($this->rd3RetryFilePath)) {
            unlink($this->rd3RetryFilePath);
        }

        if (file_exists($this->logFilePath)) {
            unlink($this->logFilePath);
        }

        if (file_exists($this->idMapPath)) {
            unlink($this->idMapPath);
        }

        if (file_exists($this->memberIdMapPath)) {
            unlink($this->memberIdMapPath);
        }
    }
}
