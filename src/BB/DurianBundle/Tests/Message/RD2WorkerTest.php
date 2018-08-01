<?php
namespace BB\DurianBundle\Tests\Message;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Message\RD2Worker;
use Buzz\Message\Response;

class RD2WorkerTest extends WebTestCase
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
     * 測試推入研二訊息至 redis
     */
    public function testPushRD2MessageToRedis()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $RD2Worker = $this->getService();

        $message = [
            'method' => 'GET',
            'url' => 'httpCurl/url',
            'content' => [
                'hello1' => 'error1',
                'hello2' => 'error2'
            ],
            'header' => [
                'header1' => 'blabla',
                'header2' => 'hehehe'
            ]
        ];

        $RD2Worker->push($message);

        $queueName = 'message_immediate_queue';
        $queueMessage = json_decode($redis->rpop($queueName), true);
        $this->assertEquals('rd2', $queueMessage['target']);
        $this->assertEquals(0, $queueMessage['error_count']);
        $this->assertEquals($message['method'], $queueMessage['method']);
        $this->assertEquals($message['url'], $queueMessage['url']);
        $this->assertEquals($message['content'], $queueMessage['content']);
    }

    /**
     * 測試推入 redis 訊息, 未指定 method
     */
    public function testPushMessageToRedisWithoutMethod()
    {
        $this->setExpectedException('InvalidArgumentException', 'No method specified in RD2 message', 150660040);

        $httpCurlWorker = $this->getService();

        $message = [
            'target' => 'rd2',
            'url' => 'httpCurl/url',
            'content' => [
                'key1' => 'gagawa',
                'key2' => 'hahaha'
            ],
            'header' => [
                'header1' => 'blabla',
                'header2' => 'hehehe'
            ]
        ];

        $httpCurlWorker->push($message);
    }

    /**
     * 測試推入 redis 訊息, 帶入不合法的 method
     */
    public function testPushMessageToRedisWithInvalidMethod()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid method in RD2 message', 150660041);

        $httpCurlWorker = $this->getService();

        $message = [
            'target' => 'rd2',
            'method' => '',
            'url' => 'httpCurl/url',
            'content' => [
                'key1' => 'gagawa',
                'key2' => 'hahaha'
            ]
        ];

        $httpCurlWorker->push($message);
    }

    /**
     * 測試推入 redis 訊息, 未指定 url
     */
    public function testPushMessageToRedisWithoutUrl()
    {
        $this->setExpectedException('InvalidArgumentException', 'No url specified in RD2 message', 150660042);

        $httpCurlWorker = $this->getService();

        $message = [
            'target' => 'rd2',
            'method' => 'GET',
            'content' => [
                'key1' => 'gagawa',
                'key2' => 'hahaha'
            ]
        ];

        $httpCurlWorker->push($message);
    }

    /**
     * 測試推入 redis 訊息, 帶入不合法的 url
     */
    public function testPushMessageToRedisWithInvalidUrl()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid url in RD2 message', 150660043);

        $httpCurlWorker = $this->getService();

        $message = [
            'target' => 'rd2',
            'method' => 'GET',
            'url' => null,
            'content' => [
                'key1' => 'gagawa',
                'key2' => 'hahaha'
            ]
        ];

        $httpCurlWorker->push($message);
    }

    /**
     * 測試推入 redis 訊息, 未指定 content
     */
    public function testPushMessageToRedisWithoutContent()
    {
        $this->setExpectedException('InvalidArgumentException', 'No content specified in RD2 message', 150660044);

        $httpCurlWorker = $this->getService();

        $message = [
            'target' => 'rd2',
            'method' => 'GET',
            'url' => 'httpCurl/url'
        ];

        $httpCurlWorker->push($message);
    }

    /**
     * 測試推入 redis 訊息, 帶入不合法的 content
     */
    public function testPushMessageToRedisWithInvalidContent()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid content in RD2 message', 150660045);

        $httpCurlWorker = $this->getService();

        $message = [
            'target' => 'rd2',
            'method' => 'GET',
            'url' => 'httpCurl/url',
            'content' => ''
        ];

        $httpCurlWorker->push($message);
    }

    /**
     * 測試傳送研二訊息
     */
    public function testSendRD2Message()
    {
        $RD2Worker = $this->getService();

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $RD2Worker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = ['result' => 'ok'];
        $response->setContent(json_encode($responseContent));
        $RD2Worker->setResponse($response);

        $message = [
            'target' => 'rd2',
            'error_count' => 0,
            'method' => 'GET',
            'url' => 'httpCurl/url',
            'content' => [
                'hello1' => 'error1',
                'hello2' => 'error2'
            ],
            'header' => [
                'header1' => 'blabla',
                'header2' => 'hehehe'
            ]
        ];

        $RD2Worker->send($message);

        $content = file_get_contents($this->httpLogPath);
        $results = explode(PHP_EOL, $content);

        $this->assertContains('GET httpCurl/url?hello1=error1&hello2=error2', $results[1]);
        $this->assertEquals('header1: blabla', trim($results[4]));
        $this->assertEquals('header2: hehehe', trim($results[5]));
        $this->assertEquals('HTTP/1.1 200 OK', trim($results[6]));
    }

    /**
     * 測試傳送研二訊息, response 回傳非 200 錯誤
     */
    public function testSendRD2MessageWithErrorResponse()
    {
        $this->setExpectedException('RuntimeException', 'Send RD2 message failed', 150660039);

        $RD2Worker = $this->getService();

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $RD2Worker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 404 Not Found');
        $RD2Worker->setResponse($response);

        $message = [
            'target' => 'rd2',
            'error_count' => 0,
            'method' => 'GET',
            'url' => 'httpCurl/url',
            'content' => [
                'hello1' => 'error1',
                'hello2' => 'error2'
            ]
        ];

        $RD2Worker->send($message);
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

        $getParameterMap = [
            ['rd2_ip', '127.0.0.1'],
            ['rd2_domain', 'rd2']
        ];

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->will($this->returnValueMap($getParameterMap));

        $RD2Worker = new RD2Worker();
        $RD2Worker->setContainer($mockContainer);

        return $RD2Worker;
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
