<?php
namespace BB\DurianBundle\Tests\Message;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Message\ITalkingWorker;
use Buzz\Message\Response;

class ITalkingWorkerTest extends WebTestCase
{
    /**
     * http log 檔路徑
     *
     * @var string
     */
    private $httpLogPath;

    /**
     * 初始化設定
     */
    public function setUp()
    {
        parent::setUp();

        $logDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $fileName = 'send_message_http_detail.log';
        $this->httpLogPath = $logDir . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . $fileName;
    }

    /**
     * 測試推入訊息至 redis
     */
    public function testPushMessageToRedis()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $italkingWorker = new ITalkingWorker();
        $mockContainer = $this->getMockContainer('127.0.0.1');
        $italkingWorker->setContainer($mockContainer);

        $message = [
            'type' => 'developer_acc',
            'message' => 'gagawa',
            'exception' => 'DBALException'
        ];

        $italkingWorker->push($message);

        $queueName = 'message_queue';
        $queueMessage = json_decode($redis->rpop($queueName), true);

        $user = $mockContainer->getParameter('italking_user');
        $password = $mockContainer->getParameter('italking_password');
        $code = $mockContainer->getParameter('italking_gm_code');

        $this->assertEquals('italking', $queueMessage['target']);
        $this->assertEquals(0, $queueMessage['error_count']);
        $this->assertEquals($user, $queueMessage['content']['user']);
        $this->assertEquals($password, $queueMessage['content']['password']);
        $this->assertEquals($message['type'], $queueMessage['content']['type']);
        $this->assertEquals($message['message'], $queueMessage['content']['message']);
        $this->assertEquals($code, $queueMessage['content']['code']);
        $this->assertEquals($message['exception'], $queueMessage['content']['exception']);
    }

    /**
     * 測試推入 esball 訊息至 redis
     */
    public function testPushEsballMessageToRedis()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $italkingWorker = new ITalkingWorker();
        $mockContainer = $this->getMockContainer('127.0.0.1');
        $italkingWorker->setContainer($mockContainer);

        $message = [
            'type' => 'payment_alarm',
            'message' => 'gagawa',
            'domain' => 6
        ];

        $italkingWorker->push($message);

        $queueName = 'message_queue';
        $queueMessage = json_decode($redis->rpop($queueName), true);

        $user = $mockContainer->getParameter('italking_user');
        $password = $mockContainer->getParameter('italking_password');
        $code = $mockContainer->getParameter('italking_esball_code');

        $this->assertEquals('italking', $queueMessage['target']);
        $this->assertEquals(0, $queueMessage['error_count']);
        $this->assertEquals($user, $queueMessage['content']['user']);
        $this->assertEquals($password, $queueMessage['content']['password']);
        $this->assertEquals($message['type'], $queueMessage['content']['type']);
        $this->assertEquals($message['message'], $queueMessage['content']['message']);
        $this->assertEquals($code, $queueMessage['content']['code']);
    }

    /**
     * 測試推入 bet9 訊息至 redis
     */
    public function testPushBet9MessageToRedis()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $italkingWorker = new ITalkingWorker();
        $mockContainer = $this->getMockContainer('127.0.0.1');
        $italkingWorker->setContainer($mockContainer);

        $message = [
            'type' => 'account_fail',
            'message' => 'gagawa',
            'domain' => 98
        ];

        $italkingWorker->push($message);

        $queueName = 'message_queue';
        $queueMessage = json_decode($redis->rpop($queueName), true);

        $user = $mockContainer->getParameter('italking_user');
        $password = $mockContainer->getParameter('italking_password');
        $code = $mockContainer->getParameter('italking_bet9_code');

        $this->assertEquals('italking', $queueMessage['target']);
        $this->assertEquals(0, $queueMessage['error_count']);
        $this->assertEquals($user, $queueMessage['content']['user']);
        $this->assertEquals($password, $queueMessage['content']['password']);
        $this->assertEquals($message['type'], $queueMessage['content']['type']);
        $this->assertEquals($message['message'], $queueMessage['content']['message']);
        $this->assertEquals($code, $queueMessage['content']['code']);
    }

    /**
     * 測試推入 kresball 訊息至 redis
     */
    public function testPushKresballMessageToRedis()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $italkingWorker = new ITalkingWorker();
        $mockContainer = $this->getMockContainer('127.0.0.1');
        $italkingWorker->setContainer($mockContainer);

        $message = [
            'type' => 'account_fail_kr',
            'message' => 'gagawa',
            'domain' => 3820175,
        ];

        $italkingWorker->push($message);

        $queueName = 'message_queue';
        $queueMessage = json_decode($redis->rpop($queueName), true);

        $user = $mockContainer->getParameter('italking_user');
        $password = $mockContainer->getParameter('italking_password');
        $code = $mockContainer->getParameter('italking_kresball_code');

        $this->assertEquals('italking', $queueMessage['target']);
        $this->assertEquals(0, $queueMessage['error_count']);
        $this->assertEquals($user, $queueMessage['content']['user']);
        $this->assertEquals($password, $queueMessage['content']['password']);
        $this->assertEquals($message['type'], $queueMessage['content']['type']);
        $this->assertEquals($message['message'], $queueMessage['content']['message']);
        $this->assertEquals($code, $queueMessage['content']['code']);
    }

    /**
     * 測試推入 esball global 訊息至 redis
     */
    public function testPushEsballGlobalMessageToRedis()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $italkingWorker = new ITalkingWorker();
        $mockContainer = $this->getMockContainer('127.0.0.1');
        $italkingWorker->setContainer($mockContainer);

        $message = [
            'type' => 'account_fail',
            'message' => 'gagawa',
            'domain' => 3819935,
        ];

        $italkingWorker->push($message);

        $queueName = 'message_queue';
        $queueMessage = json_decode($redis->rpop($queueName), true);

        $user = $mockContainer->getParameter('italking_user');
        $password = $mockContainer->getParameter('italking_password');
        $code = $mockContainer->getParameter('italking_esball_global_code');

        $this->assertEquals('italking', $queueMessage['target']);
        $this->assertEquals(0, $queueMessage['error_count']);
        $this->assertEquals($user, $queueMessage['content']['user']);
        $this->assertEquals($password, $queueMessage['content']['password']);
        $this->assertEquals($message['type'], $queueMessage['content']['type']);
        $this->assertEquals($message['message'], $queueMessage['content']['message']);
        $this->assertEquals($code, $queueMessage['content']['code']);
    }

    /**
     * 測試推入 eslot 訊息至 redis
     */
    public function testPushEslotMessageToRedis()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $italkingWorker = new ITalkingWorker();
        $mockContainer = $this->getMockContainer('127.0.0.1');
        $italkingWorker->setContainer($mockContainer);

        $message = [
            'type' => 'account_fail',
            'message' => 'gagawa',
            'domain' => 3820190,
        ];

        $italkingWorker->push($message);

        $queueName = 'message_queue';
        $queueMessage = json_decode($redis->rpop($queueName), true);

        $user = $mockContainer->getParameter('italking_user');
        $password = $mockContainer->getParameter('italking_password');
        $code = $mockContainer->getParameter('italking_eslot_code');

        $this->assertEquals('italking', $queueMessage['target']);
        $this->assertEquals(0, $queueMessage['error_count']);
        $this->assertEquals($user, $queueMessage['content']['user']);
        $this->assertEquals($password, $queueMessage['content']['password']);
        $this->assertEquals($message['type'], $queueMessage['content']['type']);
        $this->assertEquals($message['message'], $queueMessage['content']['message']);
        $this->assertEquals($code, $queueMessage['content']['code']);
    }

    /**
     * 測試推入 redis 訊息, 未指定 type
     */
    public function testPushMessageToRedisWithoutType()
    {
        $this->setExpectedException('InvalidArgumentException', 'No type specified in italking message', 150660018);

        $italkingWorker = new ITalkingWorker();
        $mockContainer = $this->getMockContainer('127.0.0.1');
        $italkingWorker->setContainer($mockContainer);

        $message = ['message' => 'gagawa'];

        $italkingWorker->push($message);
    }

    /**
     * 測試推入 redis 訊息, 帶入不合法的 type
     */
    public function testPushMessageToRedisWithInvalidType()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid type in italking message', 150660019);

        $italkingWorker = new ITalkingWorker();
        $mockContainer = $this->getMockContainer('127.0.0.1');
        $italkingWorker->setContainer($mockContainer);

        $message = [
            'type' => '',
            'message' => 'gagawa'
        ];

        $italkingWorker->push($message);
    }

    /**
     * 測試推入 redis 訊息, 未指定 message
     */
    public function testPushMessageToRedisWithoutMessage()
    {
        $this->setExpectedException('InvalidArgumentException', 'No message specified in italking message', 150660020);

        $italkingWorker = new ITalkingWorker();
        $mockContainer = $this->getMockContainer('127.0.0.1');
        $italkingWorker->setContainer($mockContainer);

        $message = ['type' => 'developer_acc'];

        $italkingWorker->push($message);
    }

    /**
     * 測試推入 redis 訊息, 帶入不合法的 message
     */
    public function testPushMessageToRedisWithInvalidMessage()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid message in italking message', 150660021);

        $italkingWorker = new ITalkingWorker();
        $mockContainer = $this->getMockContainer('127.0.0.1');
        $italkingWorker->setContainer($mockContainer);

        $message = [
            'type' => 'developer_acc',
            'message' => null
        ];

        $italkingWorker->push($message);
    }

    /**
     * 測試傳送 italking 訊息
     */
    public function testSendITalkingMessage()
    {
        $italkingWorker = new ITalkingWorker();
        $mockContainer = $this->getMockContainer('127.0.0.1');
        $italkingWorker->setContainer($mockContainer);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $italkingWorker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = ['code' => 0];
        $response->setContent(json_encode($responseContent));
        $italkingWorker->setResponse($response);

        $message = [
            'target' => 'italking',
            'error_count' => 0,
            'content' => [
                'user' => 'lala',
                'password' => 1234,
                'type' => 'developer_acc',
                'message' => 'gagawa',
                'code' => 3
            ]
        ];

        $italkingWorker->send($message);

        $content = file_get_contents($this->httpLogPath);
        $results = explode(PHP_EOL, $content);

        $queueContent = [];
        parse_str($results[6], $queueContent);

        $this->assertEquals($message['content']['user'], $queueContent['user']);
        $this->assertEquals($message['content']['password'], $queueContent['password']);
        $this->assertEquals($message['content']['type'], $queueContent['type']);
        $this->assertEquals($message['content']['message'], $queueContent['message']);
        $this->assertEquals($message['content']['code'], trim($queueContent['code']));
        $this->assertEquals('HTTP/1.1 200 OK', trim($results[7]));
    }

    /**
     * 測試傳送 italking 訊息, 但無 italking 可送
     */
    public function testSendITalkingMessageWithoutITalking()
    {
        $mockContainer = $this->getMockContainer();
        $italkingWorker = new ITalkingWorker();
        $italkingWorker->setContainer($mockContainer);

        $message = [
            'target' => 'italking',
            'error_count' => 0,
            'content' => [
                'user' => 'lala',
                'password' => 1234,
                'type' => 'developer_acc',
                'message' => 'gagawa',
                'code' => 3
            ]
        ];

        $italkingWorker->send($message);

        $exist = file_exists($this->httpLogPath);

        $this->assertFalse($exist);
    }

    /**
     * 測試傳送 italking 訊息, response 回傳非 200 錯誤
     */
    public function testSendITalkingMessageWithErrorResponse()
    {
        $this->setExpectedException('RuntimeException', 'Send italking message failed', 150660016);

        $italkingWorker = new ITalkingWorker();
        $mockContainer = $this->getMockContainer('127.0.0.1');
        $italkingWorker->setContainer($mockContainer);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $italkingWorker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 404 Not Found');
        $italkingWorker->setResponse($response);

        $message = [
            'target' => 'italking',
            'error_count' => 0,
            'content' => [
                'user' => 'lala',
                'password' => 1234,
                'type' => 'developer_acc',
                'message' => 'gagawa',
                'code' => 3
            ]
        ];

        $italkingWorker->send($message);
    }

    /**
     * 測試傳送 italking 訊息, response 未回傳 code
     */
    public function testSendITalkingMessageWithoutCodeResponse()
    {
        $this->setExpectedException('RuntimeException', 'Send italking message failed with error response', 150660017);

        $italkingWorker = new ITalkingWorker();
        $mockContainer = $this->getMockContainer('127.0.0.1');
        $italkingWorker->setContainer($mockContainer);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $italkingWorker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $italkingWorker->setResponse($response);

        $message = [
            'target' => 'italking',
            'error_count' => 0,
            'content' => [
                'user' => 'lala',
                'password' => 1234,
                'type' => 'developer_acc',
                'message' => 'gagawa',
                'code' => 3
            ]
        ];

        $italkingWorker->send($message);
    }

    /**
     * 測試傳送 italking 訊息, response 的 code 回傳錯誤
     */
    public function testSendITalkingMessageWithErrorCodeResponse()
    {
        $this->setExpectedException('RuntimeException', 'Send italking message failed with error response', 150660017);

        $italkingWorker = new ITalkingWorker();
        $mockContainer = $this->getMockContainer('127.0.0.1');
        $italkingWorker->setContainer($mockContainer);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $italkingWorker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = ['code' => 1];
        $response->setContent(json_encode($responseContent));
        $italkingWorker->setResponse($response);

        $message = [
            'target' => 'italking',
            'error_count' => 0,
            'content' => [
                'user' => 'lala',
                'password' => 1234,
                'type' => 'developer_acc',
                'message' => 'gagawa',
                'code' => 3
            ]
        ];

        $italkingWorker->send($message);
    }

    /**
     * 測試傳送 italking 訊息, response 的 code 型態回傳錯誤
     */
    public function testSendITalkingMessageWithErrorCodeTypeResponse()
    {
        $this->setExpectedException('RuntimeException', 'Send italking message failed with error response', 150660017);

        $italkingWorker = new ITalkingWorker();
        $mockContainer = $this->getMockContainer('127.0.0.1');
        $italkingWorker->setContainer($mockContainer);

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $italkingWorker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = ['code' => '0'];
        $response->setContent(json_encode($responseContent));
        $italkingWorker->setResponse($response);

        $message = [
            'target' => 'italking',
            'error_count' => 0,
            'content' => [
                'user' => 'lala',
                'password' => 1234,
                'type' => 'developer_acc',
                'message' => 'gagawa',
                'code' => 3
            ]
        ];

        $italkingWorker->send($message);
    }

    /**
     * 取得 MockContainer
     *
     * @param string $ip
     * @return Container
     */
    private function getMockContainer($ip = null)
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $logger = $this->getContainer()->get('monolog.logger.msg');
        $handler = $this->getContainer()->get('monolog.handler.send_message_http_detail');

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get', 'getParameter'])
            ->disableOriginalConstructor()
            ->getMock();

        $getMap = [
            ['snc_redis.default_client', 1, $redis],
            ['monolog.logger.msg', 1, $logger],
            ['monolog.handler.send_message_http_detail', 1, $handler]
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        $getParameterMap = [
            ['italking_method', 'POST'],
            ['italking_url', 'italking/url'],
            ['italking_ip', $ip],
            ['italking_domain', 'italking']
        ];

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->will($this->returnValueMap($getParameterMap));

        return $mockContainer;
    }

    /**
     * 清除產生的檔案
     */
    public function tearDown()
    {
        if (file_exists($this->httpLogPath)) {
            unlink($this->httpLogPath);
        }
    }
}
