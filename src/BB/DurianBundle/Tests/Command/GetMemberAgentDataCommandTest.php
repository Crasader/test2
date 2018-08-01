<?php
namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use Buzz\Message\Response;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use BB\DurianBundle\Command\GetMemberAgentDataCommand;
use Buzz\Exception\ClientException;

class GetMemberAgentDataCommandTest extends WebTestCase
{
    public function setUp()
    {
        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserDetailData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashDepositEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashWithdrawEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankInfoData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankCurrencyData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserStatData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserEmailData',
        ];

        $this->loadFixtures($classnames);
    }

    /**
     * 輸入錯誤參數(沒有輸入 domain)
     */
    public function testInputWithoutDomain()
    {
        $output = $this->runCommand('durian:get-member-agent-data', []);

        $this->assertContains('[Exception]', $output);
        $this->assertContains('No domain specified', $output);
    }

    /**
     * 輸入錯誤參數(同時帶入 getAgentCode and role)
     */
    public function testInputWithBothListAndRole()
    {
        $params = [
            '--domain' => 6,
            '--getAgentCode' => true,
            '--role' => 1
        ];

        $output = $this->runCommand('durian:get-member-agent-data', $params);

        $this->assertContains('[Exception]', $output);
        $this->assertContains('--agentCode 不可與 --role 共用', $output);
    }

    /**
     * 取得代理推廣代碼時 Curl 失敗
     */
    public function testGetAgentCodeButCurlFailed()
    {
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $msg = 'Operation timed out after 5000 milliseconds with 0 bytes received';

        $client->expects($this->any())
            ->method('send')
            ->will($this->throwException(new ClientException($msg)));

        $application = new Application();
        $command = new GetMemberAgentDataCommand();
        $command->setContainer($this->getContainer());
        $command->setClient($client);
        $application->add($command);

        $command = $application->find('durian:get-member-agent-data');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), '--domain' => 3, '--getAgentCode' => true]);

        $output = explode(PHP_EOL, $commandTester->getDisplay());

        $this->assertEquals('取得代理推廣代碼失敗', $output[0]);
        $this->assertEmpty($output[1]);

        $logFile = $this->getContainer()->get('kernel')->getRootDir() . '/../get-member-agent-data.log';
        $this->assertFileExists($logFile);
        $content = file_get_contents($logFile);
        $data = explode(PHP_EOL, $content);

        $this->assertEquals("Exception : {$msg}", $data[0]);

        $logFile = $this->getContainer()->get('kernel')->getRootDir() . '/../rd1.csv';
        $this->assertFileNotExists($logFile);
    }

    /**
     * 取得代理推廣代碼時 status code 500
     */
    public function testGetAgentCodeButStatusCodeNot200()
    {
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 500');
        $response->setContent('');

        $application = new Application();
        $command = new GetMemberAgentDataCommand();
        $command->setContainer($this->getContainer());
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:get-member-agent-data');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), '--domain' => 3, '--getAgentCode' => true]);

        $output = explode(PHP_EOL, $commandTester->getDisplay());

        $this->assertEquals('取得代理推廣代碼失敗', $output[0]);
        $this->assertEmpty($output[1]);

        $logFile = $this->getContainer()->get('kernel')->getRootDir() . '/../get-member-agent-data.log';
        $this->assertFileExists($logFile);
        $content = file_get_contents($logFile);
        $data = explode(PHP_EOL, $content);

        $this->assertEquals('Status code not 200', $data[0]);

        $logFile = $this->getContainer()->get('kernel')->getRootDir() . '/../rd1.csv';
        $this->assertFileNotExists($logFile);
    }

    /**
     * 取得代理推廣代碼時沒有回傳內容
     */
    public function testGetAgentCodeButNoResponseContent()
    {
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent('');

        $application = new Application();
        $command = new GetMemberAgentDataCommand();
        $command->setContainer($this->getContainer());
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:get-member-agent-data');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), '--domain' => 3, '--getAgentCode' => true]);

        $output = explode(PHP_EOL, $commandTester->getDisplay());

        $this->assertEquals('取得代理推廣代碼失敗', $output[0]);
        $this->assertEmpty($output[1]);

        $logFile = $this->getContainer()->get('kernel')->getRootDir() . '/../get-member-agent-data.log';
        $this->assertFileExists($logFile);
        $content = file_get_contents($logFile);
        $data = explode(PHP_EOL, $content);

        $this->assertEquals('Decode error or no result with content : ', $data[0]);

        $logFile = $this->getContainer()->get('kernel')->getRootDir() . '/../rd1.csv';
        $this->assertFileNotExists($logFile);
    }

    /**
     * 取得代理推廣代碼
     */
    public function testGetAgentCode()
    {
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $responseContent = [
            'code' => 200,
            'message' => '',
            'data' => [7 => '3345678'],
            'other' => []
        ];

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $response->setContent(json_encode($responseContent));

        $application = new Application();
        $command = new GetMemberAgentDataCommand();
        $command->setContainer($this->getContainer());
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:get-member-agent-data');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), '--domain' => 3, '--getAgentCode' => true]);

        $output = explode(PHP_EOL, $commandTester->getDisplay());

        $this->assertEquals('研一的代理推廣代碼: rd1.csv', $output[0]);
        $this->assertEmpty($output[1]);

        $logFile = $this->getContainer()->get('kernel')->getRootDir() . '/../get-member-agent-data.log';
        $this->assertFileExists($logFile);
        $content = file_get_contents($logFile);
        $data = explode(PHP_EOL, $content);

        $this->assertEquals('{"code":200,"message":"","data":{"7":"3345678"},"other":[]}', $data[0]);

        $logFile = $this->getContainer()->get('kernel')->getRootDir() . '/../rd1.csv';
        $this->assertFileExists($logFile);
        $content = file_get_contents($logFile);
        $data = explode(PHP_EOL, $content);

        $this->assertEquals('代理ID,推廣代碼', $data[0]);
        $this->assertEquals('7,3345678', $data[1]);
    }

    /**
     * 測試出會員資料
     */
    public function testGetMemberDate()
    {
        // 產生出外接額度檔案
        $outputPath = $this->getContainer()->get('kernel')->getRootDir() . '/../extraBalance.csv';

        $extraBalance = [
            'UserID,ag,ab,og,mg,sp',
            '8,0.12,3.45,6.78,9.0,0',
            '51,10.12,23.45,36.78,49.0,0',
        ];

        foreach ($extraBalance as $data) {
            file_put_contents($outputPath, "$data\n", FILE_APPEND);
        }

        $outputPath = $this->getContainer()->get('kernel')->getRootDir() . '/../rd5ExtraBalance.csv';

        $extraBalance = [
            'UserID,pp',
            '8,0.13',
            '51,10.13'
        ];

        foreach ($extraBalance as $data) {
            file_put_contents($outputPath, "$data\n", FILE_APPEND);
        }

        $params = [
            '--domain' => 3,
            '--role' => 1,
            '--mergeFile' => 'extraBalance.csv',
            '--mergeRd5File' => 'rd5ExtraBalance.csv',
            '--limit' => 50000,
        ];

        $output = $this->runCommand('durian:get-member-agent-data', $params);
        $output = explode("\n", $output);

        // 為了避免變動欄位-註冊時間，故拆兩段驗證
        $member8DataPart1 = '1,8,tester,達文西,123456,,Davinci@chinatown.com,3345678,485163154787,abcde123,1000,0.12,3.45,6.78,9.0,0,0.13';
        $member8DataPart2 = ',ztester,,啟用,2000-10-10 00:00:00,,,,,TWD,0,3,0,255,第一層';

        $member51DataPart1 = '1,51,oauthuser,,,,,,,,0,10.12,23.45,36.78,49.0,0,10.13';
        $member51DataPart2 = ',ztester,,啟用,,,,,,TWD,0,0,0,0,';

        $this->assertContains($member8DataPart1, $output[0]);
        $this->assertContains($member8DataPart2, $output[0]);
        $this->assertContains($member51DataPart1, $output[1]);
        $this->assertContains($member51DataPart2, $output[1]);
        $this->assertEquals('', $output[2]);
    }

    /**
     * 測試出代理資料
     */
    public function testGetAgentDate()
    {
        // 產生出代理推廣代碼檔案
        $outputPath = $this->getContainer()
            ->get('kernel')
            ->getRootDir() . "/../agentCode.csv";

        $duplicateUser[] = [
            'id' => 7,
            'agent_code' => '3345678'
        ];

        $line = implode(',', $duplicateUser[0]);
        file_put_contents($outputPath, "$line\n", FILE_APPEND);

        $params = [
            '--domain' => 3,
            '--role' => 2,
            '--mergeFile' => 'agentCode.csv'
        ];

        $output = $this->runCommand('durian:get-member-agent-data', $params);
        $output = explode("\n", $output);

        $agentData = '2,7,ztester,123456,,,,,,2013-01-01 11:11:11,3345678,,,,,,啟用,TWD';

        $this->assertEquals($agentData, $output[0]);
        $this->assertEquals('', $output[1]);
    }

    /**
     * 刪除跑完測試後產生的檔案
     */
    public function tearDown()
    {
        $fileRootDir = $this->getContainer()->get('kernel')->getRootDir();

        $filePath = $fileRootDir . '/../get-member-agent-data.log';

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $filePath = $fileRootDir . '/../extraBalance.csv';

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $filePath = $fileRootDir . '/../rd5ExtraBalance.csv';

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $filePath = $fileRootDir . '/../agentCode.csv';

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $filePath = $fileRootDir . '/../rd1.csv';

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        parent::tearDown();
    }
}
