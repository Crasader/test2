<?php
namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Consumer\SyncPoper;

class ReOpCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareUpdateCronForControllerData',
        ];
        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigData'
        ];
        $this->loadFixtures($classnames, 'share');

        $redis = $this->getContainer()->get('snc_redis.sequence');

        $redis->set('cash_seq', 0);
        $redis->set('cashfake_seq', 0);

        $logPath = $this->getContainer()->getParameter('kernel.logs_dir');
        $this->outputCsv = $logPath.'/reop/output-test.csv';
    }

    /**
     * 測試補單
     */
    public function testExecute()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // prepare csv file
        $fp = fopen('test.csv', 'w');
        fwrite($fp, '8,10,20001,123456,test');
        fclose($fp);

        $params = [
            'path' => 'test.csv',
            '--output' => 'output-test.csv'
        ];
        $output = $this->runCommand('durian:reop', $params);

        // sync cash balance
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        // check output roughly
        $results = explode(PHP_EOL, $output);
        $this->assertEquals('ReOpCommand Start.', $results[0]);
        $this->assertEquals('帳號ID, 參考編號, 交易金額, 餘額, 使用者帳號, 廳名, 廳主代碼, memo, 交易類別, 幣別, 明細id', $results[1]);
        $this->assertEquals('8,123456,10,1010,tester,domain2,cm,test,cash,TWD,1', $results[2]);

        // check cash balance
        $user = $em->find('BBDurianBundle:User', 8);
        $this->assertEquals(1010, $user->getCash()->getBalance());

        // remove csv file
        unlink('test.csv');
        unlink($this->outputCsv);
    }

    /**
     * 測試以自定義的CSV Title順序進行補單
     */
    public function testReopWithCustomCsvTitle()
    {
        //測試有自行定義欄位是否也可以正確抓到
        $fp = fopen("test.csv", 'w');
        fwrite($fp, "userId,opcode,amount,memo,refId\n");
        fwrite($fp, "8,1019,-19.5,視訊重新派彩扣回,703929916");
        fclose($fp);

        $params = [
            'path' => 'test.csv',
            '--output' => 'output-test.csv'
        ];
        $output = $this->runCommand('durian:reop', $params);

        // sync cash balance
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        // check cash balance
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->find('BBDurianBundle:User', 8);
        $this->assertEquals(980.5, $user->getCash()->getBalance());

        unlink('test.csv');
        unlink($this->outputCsv);

        //測試自行定義欄位memo放第一欄位
        $fp = fopen("test.csv", 'w');
        fwrite($fp, "memo,opcode,amount,userId,refId\n");
        fwrite($fp, "視訊重新派彩扣回,1019,-19.5,8,703929916");
        fclose($fp);

        $output = $this->runCommand('durian:reop', $params);

        // sync cash balance
        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        // check cash balance
        $em->refresh($user);
        $this->assertEquals(980.5, $user->getCash()->getBalance());

        unlink('test.csv');
        unlink($this->outputCsv);
    }

    /**
     * 測試補單時使用者無現金
     */
    public function testReopButUserHasNoCash()
    {
        $client = $this->createClient();

        $parameters = [
            'username' => 'allnewone',
            'password' => 'all_new_one',
            'alias' => 'AllNewOne23',
            'role'  => 7,
            'name' => 'all',
            'login_code' => 'no',
            'currency' => 'TWD',
        ];
        $client->request('POST', '/api/user', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $userId = $output['ret']['id'];

        // prepare csv file
        $fp = fopen('test.csv', 'w');
        fwrite($fp, $userId.',10,20001,123456,test');
        fclose($fp);

        $params = [
            'path' => 'test.csv',
            '--output' => 'output-test.csv'
        ];
        $output = $this->runCommand('durian:reop', $params);

        $handle = fopen($this->outputCsv, "r");
        $log = fread($handle, filesize($this->outputCsv));
        $this->assertContains('The user does not have cash', $log);

        unlink('test.csv');
        unlink($this->outputCsv);
    }

    /**
     * 測試補單時使用者有現金也有快開額度
     */
    public function testReopButUserHasBothCashAndCashfake()
    {
        $client = $this->createClient();

        $parameters = [
            'username' => 'allnewone',
            'password' => 'all_new_one',
            'alias' => 'AllNewOne23',
            'role' => 7,
            'name' => 'all',
            'login_code' => 'no',
            'currency' => 'TWD',
            'cash' => ['currency' => 'CNY'],
            'cash_fake' => [
                'currency' => 'CNY',
                'balance'  => 100,
            ],
        ];

        $client->request('POST', '/api/user', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $userId = $output['ret']['id'];

        // prepare csv file
        $fp = fopen('test.csv', 'w');
        fwrite($fp, $userId.',10,20001,123456,test');
        fclose($fp);

        $params = [
            'path' => 'test.csv',
            '--output' => 'output-test.csv'
        ];
        $output = $this->runCommand('durian:reop', $params);

        $handle = fopen($this->outputCsv, "r");
        $log = fread($handle, filesize($this->outputCsv));
        $this->assertContains('This user has both cash and cashFake', $log);

        unlink('test.csv');
        unlink($this->outputCsv);
    }
}
