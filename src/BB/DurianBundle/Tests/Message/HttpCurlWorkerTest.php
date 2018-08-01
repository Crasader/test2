<?php
namespace BB\DurianBundle\Tests\Message;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Message\HttpCurlWorker;
use Buzz\Message\Response;

class HttpCurlWorkerTest extends WebTestCase
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
        $httpCurlWorker = $this->getService();

        $message = [
            'method' => 'GET',
            'url' => 'httpCurl/url',
            'ip' => '127.0.0.1',
            'domain' => 'httpCurl',
            'content' => [
                'key1' => 'gagawa',
                'key2' => 'hahaha'
            ],
            'header' => [
                'header1' => 'blabla',
                'header2' => 'hehehe'
            ],
            'interval' => 0.5,
            'allowed_times' => 3,
            'timeout' => 1
        ];

        $httpCurlWorker->push($message);

        $queueName = 'message_queue';
        $queueMessage = json_decode($redis->rpop($queueName), true);

        $this->assertEquals(0, $queueMessage['error_count']);
        $this->assertEquals($message['method'], $queueMessage['method']);
        $this->assertEquals($message['url'], $queueMessage['url']);
        $this->assertEquals($message['ip'], $queueMessage['ip']);
        $this->assertEquals($message['domain'], $queueMessage['domain']);
        $this->assertEquals($message['content'], $queueMessage['content']);
        $this->assertEquals($message['header'], $queueMessage['header']);
    }

    /**
     * 測試推入即時訊息至 redis
     */
    public function testPushImmediateMessageToRedis()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $httpCurlWorker = $this->getService();

        $message = [
            'method' => 'POST',
            'url' => 'httpCurl/url',
            'ip' => '127.0.0.1',
            'domain' => 'httpCurl',
            'content' => [
                'key1' => 'gagawa',
                'key2' => 'hahaha'
            ],
            'immediate' => true
        ];

        $httpCurlWorker->push($message);

        $queueName = 'message_immediate_queue';
        $queueMessage = json_decode($redis->rpop($queueName), true);

        $this->assertEquals(0, $queueMessage['error_count']);
        $this->assertEquals($message['method'], $queueMessage['method']);
        $this->assertEquals($message['url'], $queueMessage['url']);
        $this->assertEquals($message['ip'], $queueMessage['ip']);
        $this->assertEquals($message['domain'], $queueMessage['domain']);
        $this->assertEquals($message['content'], $queueMessage['content']);
    }

    /**
     * 測試推入 redis 訊息, 未指定 method
     */
    public function testPushMessageToRedisWithoutMethod()
    {
        $this->setExpectedException('InvalidArgumentException', 'No method specified', 150660006);

        $httpCurlWorker = $this->getService();

        $message = [
            'url' => 'httpCurl/url',
            'ip' => '127.0.0.1',
            'domain' => 'httpCurl',
            'content' => [
                'key1' => 'gagawa',
                'key2' => 'hahaha'
            ],
        ];

        $httpCurlWorker->push($message);
    }

    /**
     * 測試推入 redis 訊息, 帶入不合法的 method
     */
    public function testPushMessageToRedisWithInvalidMethod()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid method', 150660007);

        $httpCurlWorker = $this->getService();

        $message = [
            'method' => '',
            'url' => 'httpCurl/url',
            'ip' => '127.0.0.1',
            'domain' => 'httpCurl',
            'content' => [
                'key1' => 'gagawa',
                'key2' => 'hahaha'
            ],
        ];

        $httpCurlWorker->push($message);
    }

    /**
     * 測試推入 redis 訊息, 未指定 url
     */
    public function testPushMessageToRedisWithoutUrl()
    {
        $this->setExpectedException('InvalidArgumentException', 'No url specified', 150660008);

        $httpCurlWorker = $this->getService();

        $message = [
            'method' => 'GET',
            'ip' => '127.0.0.1',
            'domain' => 'httpCurl',
            'content' => [
                'key1' => 'gagawa',
                'key2' => 'hahaha'
            ],
        ];

        $httpCurlWorker->push($message);
    }

    /**
     * 測試推入 redis 訊息, 帶入不合法的 url
     */
    public function testPushMessageToRedisWithInvalidUrl()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid url', 150660009);

        $httpCurlWorker = $this->getService();

        $message = [
            'method' => 'GET',
            'url' => null,
            'ip' => '127.0.0.1',
            'domain' => 'httpCurl',
            'content' => [
                'key1' => 'gagawa',
                'key2' => 'hahaha'
            ],
        ];

        $httpCurlWorker->push($message);
    }

    /**
     * 測試推入 redis 訊息, 未指定 ip
     */
    public function testPushMessageToRedisWithoutIp()
    {
        $this->setExpectedException('InvalidArgumentException', 'No ip specified', 150660010);

        $httpCurlWorker = $this->getService();

        $message = [
            'method' => 'GET',
            'url' => 'httpCurl/url',
            'domain' => 'httpCurl',
            'content' => [
                'key1' => 'gagawa',
                'key2' => 'hahaha'
            ],
        ];

        $httpCurlWorker->push($message);
    }

    /**
     * 測試推入 redis 訊息, 帶入不合法的 ip
     */
    public function testPushMessageToRedisWithInvalidIp()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid ip', 150660011);

        $httpCurlWorker = $this->getService();

        $message = [
            'method' => 'GET',
            'url' => 'httpCurl/url',
            'ip' => '',
            'domain' => 'httpCurl',
            'content' => [
                'key1' => 'gagawa',
                'key2' => 'hahaha'
            ],
        ];

        $httpCurlWorker->push($message);
    }

    /**
     * 測試推入 redis 訊息, 未指定 domain
     */
    public function testPushMessageToRedisWithoutDomain()
    {
        $this->setExpectedException('InvalidArgumentException', 'No domain specified', 150660012);

        $httpCurlWorker = $this->getService();

        $message = [
            'method' => 'GET',
            'url' => 'httpCurl/url',
            'ip' => '127.0.0.1',
            'content' => [
                'key1' => 'gagawa',
                'key2' => 'hahaha'
            ],
        ];

        $httpCurlWorker->push($message);
    }

    /**
     * 測試推入 redis 訊息, 帶入不合法的 domain
     */
    public function testPushMessageToRedisWithInvalidDomain()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid domain', 150660013);

        $httpCurlWorker = $this->getService();

        $message = [
            'method' => 'GET',
            'url' => 'httpCurl/url',
            'ip' => '127.0.0.1',
            'domain' => null,
            'content' => [
                'key1' => 'gagawa',
                'key2' => 'hahaha'
            ],
        ];

        $httpCurlWorker->push($message);
    }

    /**
     * 測試推入 redis 訊息, 未指定 content
     */
    public function testPushMessageToRedisWithoutContent()
    {
        $this->setExpectedException('InvalidArgumentException', 'No content specified', 150660014);

        $httpCurlWorker = $this->getService();

        $message = [
            'method' => 'GET',
            'url' => 'httpCurl/url',
            'ip' => '127.0.0.1',
            'domain' => 'httpCurl'
        ];

        $httpCurlWorker->push($message);
    }

    /**
     * 測試推入 redis 訊息, 帶入不合法的 content
     */
    public function testPushMessageToRedisWithInvalidContent()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid content', 150660015);

        $httpCurlWorker = $this->getService();

        $message = [
            'method' => 'GET',
            'url' => 'httpCurl/url',
            'ip' => '127.0.0.1',
            'domain' => 'httpCurl',
            'content' => ''
        ];

        $httpCurlWorker->push($message);
    }

    /**
     * 測試傳送訊息
     */
    public function testSendMessage()
    {
        $httpCurlWorker = $this->getService();

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $httpCurlWorker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $httpCurlWorker->setResponse($response);

        $message = [
            'error_count' => 0,
            'method' => 'POST',
            'url' => 'httpCurl/url',
            'ip' => '127.0.0.1',
            'domain' => 'httpCurl',
            'content' => [
                'key1' => 'gagawa',
                'key2' => 'hahaha'
            ],
            'header' => [
                'header1' => 'blabla',
                'header2' => 'hehehe'
            ]
        ];

        $httpCurlWorker->send($message);

        $content = file_get_contents($this->httpLogPath);
        $results = explode(PHP_EOL, $content);

        $queueContent = [];
        parse_str($results[8], $queueContent);

        $this->assertEquals($message['content']['key1'], trim($queueContent['key1']));
        $this->assertEquals($message['content']['key2'], trim($queueContent['key2']));

        $this->assertEquals('header1: blabla', trim($results[4]));
        $this->assertEquals('header2: hehehe', trim($results[5]));

        $this->assertEquals('HTTP/1.1 200 OK', trim($results[9]));
    }

    /**
     * 測試傳送訊息, response 回傳非 200 錯誤
     */
    public function testSendITalkingMessageWithErrorResponse()
    {
        $this->setExpectedException('RuntimeException', 'Send message failed', 150660005);

        $httpCurlWorker = $this->getService();

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $httpCurlWorker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 404 Not Found');
        $httpCurlWorker->setResponse($response);

        $message = [
            'error_count' => 0,
            'method' => 'POST',
            'url' => 'httpCurl/url',
            'ip' => '127.0.0.1',
            'domain' => 'httpCurl',
            'content' => [
                'key1' => 'gagawa',
                'key2' => 'hahaha'
            ]
        ];

        $httpCurlWorker->send($message);
    }

    /**
     * 取得 Service
     *
     * @return Service
     */
    private function getService()
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

        $httpCurlWorker = new HttpCurlWorker();
        $httpCurlWorker->setContainer($mockContainer);

        return $httpCurlWorker;
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
