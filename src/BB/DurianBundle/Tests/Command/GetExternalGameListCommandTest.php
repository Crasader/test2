<?php
namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Command\GetExternalGameListCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use BB\DurianBundle\Tests\Functional\WebTestCase;

class GetExternalGameListCommandTest extends WebTestCase
{
    /**
     * 測試取得外接遊戲列表，並存入redis中
     */
    public function testGetExternalGameList()
    {
        $key = 'external_game_list';
        $container = $this->getContainer();
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $response = $this->getMockBuilder('Buzz\Message\Response')
            ->setMethods(['getStatusCode','getContent'])
            ->getMock();

        $response->expects($this->any())
            ->method('getStatusCode')
            ->willReturn(200);

        $ret = [
            'result' => 'ok',
            'ret' => [
                '4'  => '體育投注',
                '19' => 'AG視訊',
                '20' => 'PT電子'
            ]
        ];

        $response->expects($this->any())
            ->method('getContent')
            ->willReturn(json_encode($ret));

        $application = new Application();
        $command = new GetExternalGameListCommand();
        $command->setContainer($container);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:get-external-game-list');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $redis = $this->getContainer()->get('snc_redis.default');
        $list = json_decode($redis->get($key), true);
        $this->assertEquals('體育投注', $list['4']);
        $this->assertEquals('AG視訊', $list['19']);
        $this->assertEquals('PT電子', $list['20']);
    }

    /**
     * 測試取得外接遊戲列表，回傳錯誤
     */
    public function testGetExternalGameListWithErrorResponse()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Curl get external list api failed with error response',
            150960016
        );

        $key = 'external_game_list';
        $container = $this->getContainer();
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $response = $this->getMockBuilder('Buzz\Message\Response')
            ->setMethods(['getStatusCode','getContent'])
            ->getMock();

        $response->expects($this->any())
            ->method('getStatusCode')
            ->willReturn(200);

        $ret = ['result' => 'error'];

        $response->expects($this->any())
            ->method('getContent')
            ->willReturn(json_encode($ret));

        $application = new Application();
        $command = new GetExternalGameListCommand();
        $command->setContainer($container);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:get-external-game-list');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
    }

    /**
     * 測試取得外接遊戲列表，curl回傳非200
     */
    public function testGetExternalGameListWithCurlError()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Curl get external list api failed',
            150960013
        );

        $key = 'external_game_list';
        $container = $this->getContainer();
        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $response = $this->getMockBuilder('Buzz\Message\Response')
            ->setMethods(['getStatusCode','getContent'])
            ->getMock();

        $response->expects($this->any())
            ->method('getStatusCode')
            ->willReturn(500);

        $application = new Application();
        $command = new GetExternalGameListCommand();
        $command->setContainer($container);
        $command->setClient($client);
        $command->setResponse($response);
        $application->add($command);

        $command = $application->find('durian:get-external-game-list');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
    }
}
