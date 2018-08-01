<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Command\ShopwebCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Buzz\Message\Response;

class ShopwebCommandTest extends WebTestCase
{
    /**
     * 初始化設定
     */
    public function setUp()
    {
        parent::setUp();

        $this->loadFixtures([]);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $redis->flushdb();

        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'test/shopweb.log';

        // 如果log檔已經存在就移除
        if (file_exists($logPath)) {
            unlink($logPath);
        }
    }

    /**
     * 測試發送購物網通知
     */
    public function testExecute()
    {
        // mock對外連線及返回
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $response = new Response();
        $response->setContent('{"result":"ok"}');
        $response->addHeader('HTTP/1.1 200 OK');

        $application = new Application();
        $command = new ShopwebCommand();
        $command->setContainer($this->getContainer());
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $commandTester = new CommandTester($command);

        // 測試當沒有購物網通知的資料可以正常執行
        $commandTester->execute(['command' => $command->getName()]);

        // 因沒有購物網通知的資料，所以不會產生log
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'test/shopweb.log';
        $this->assertFileNotExists($logPath);

        // 測試成功發送購物網通知
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $params = [
            'username' => 'test',
            'amount' => '10',
            'entry_id' => '201501230000000093'
        ];

        $shopWebInfo = [
            'url' => 'http://www.shopweb.com/',
            'params' => $params
        ];

        $redis->lpush('shopweb_queue', json_encode($shopWebInfo));

        $commandTester->execute(['command' => $command->getName()]);
        $this->assertFileExists($logPath);
        $contents = file_get_contents($logPath);

        $results = explode(PHP_EOL, $contents);

        $logContent = sprintf(
            '%s %s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            $this->getContainer()->getParameter('shopweb_ip'),
            '127.0.0.1',
            'GET',
            '/admin/auto_order.php?user_name=test&gold=10&order_sn=201501230000000093',
            '',
            '{"result":"ok"}'
        );

        $this->assertContains($logContent, $results[0]);
    }

    /**
     * 測試發送購物網通知對外連線timeout
     */
    public function testExecuteCrulTimeout()
    {
        // mock對外連線及返回
        $exceptionMsg = 'Operation timed out after 30000 milliseconds with 0 bytes received';
        $client = $this
            ->getMockBuilder('Buzz\Client\Curl')
            ->setMethods(['send'])
            ->getMock();
        $client->expects($this->any())
            ->method('send')
            ->will($this->throwException(new \Exception($exceptionMsg, 28)));

        $response = new Response();
        $response->setContent('{"result":"ok"}');
        $response->addHeader('HTTP/1.1 200 OK');

        $application = new Application();
        $command = new ShopwebCommand();
        $command->setContainer($this->getContainer());
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $commandTester = new CommandTester($command);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $params = [
            'username' => 'test',
            'amount' => '10',
            'entry_id' => '201501230000000093'
        ];

        $shopWebInfo = [
            'url' => 'http://www.shopweb.com/',
            'params' => $params
        ];

        $redis->lpush('shopweb_queue', json_encode($shopWebInfo));

        $commandTester->execute(['command' => $command->getName()]);

        // 檢查log
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'test/shopweb.log';
        $this->assertFileExists($logPath);
        $contents = file_get_contents($logPath);

        $results = explode(PHP_EOL, $contents);

        $ip = $this->getContainer()->getParameter('shopweb_ip');
        $url = $shopWebInfo['url'];
        $parseUrl = parse_url($url);

        $host = sprintf(
            'shop.%s.%s',
            $parseUrl['scheme'],
            $parseUrl['host']
        );
        $logContent = sprintf(
            '%s %s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            $this->getContainer()->getParameter('shopweb_ip'),
            '127.0.0.1',
            'GET',
            '/admin/auto_order.php?user_name=test&gold=10&order_sn=201501230000000093',
            '',
            "通知購物網失敗，請檢查 $ip 到 $host 的線路是否正常。"
        );

        $this->assertContains($logContent, $results[0]);
    }

    /**
     * 測試發送購物網通知對外連線結果為非200
     */
    public function testExecuteCurlHasNotSucceeded()
    {
        // mock對外連線及返回
        $exceptionMsg = 'Send shop web has not succeeded';
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->setContent('<h1>502 Bad Gateway</h1>');
        $response->addHeader('HTTP/1.1 502 Bad Gateway');

        $application = new Application();
        $command = new ShopwebCommand();
        $command->setContainer($this->getContainer());
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $commandTester = new CommandTester($command);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $params = [
            'username' => 'test',
            'amount' => '10',
            'entry_id' => '201501230000000093'
        ];

        $shopWebInfo = [
            'url' => 'http://www.shopweb.com/',
            'params' => $params
        ];

        $redis->lpush('shopweb_queue', json_encode($shopWebInfo));

        $commandTester->execute(['command' => $command->getName()]);

        // 檢查log
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'test/shopweb.log';
        $this->assertFileExists($logPath);
        $contents = file_get_contents($logPath);

        $results = explode(PHP_EOL, $contents);

        $ip = $this->getContainer()->getParameter('shopweb_ip');
        $url = $shopWebInfo['url'];
        $parseUrl = parse_url($url);

        $host = sprintf(
            'shop.%s.%s',
            $parseUrl['scheme'],
            $parseUrl['host']
        );
        $logContent = sprintf(
            '%s %s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            $this->getContainer()->getParameter('shopweb_ip'),
            '127.0.0.1',
            'GET',
            '/admin/auto_order.php?user_name=test&gold=10&order_sn=201501230000000093',
            '',
            "通知購物網失敗，請檢查 $ip 到 $host 的線路是否正常。"
        );

        $this->assertContains($logContent, $results[0]);
    }

    /**
     * 清除產生的檔案
     */
    public function tearDown()
    {
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'test/shopweb.log';

        if (file_exists($logPath)) {
            unlink($logPath);
        }
    }
}
