<?php
namespace BB\DurianBundle\Tests\Message;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Message\RD3Worker;
use Buzz\Message\Response;

class RD3WorkerTest extends WebTestCase
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
     * 測試推入研三訊息至 redis
     */
    public function testPushRD3MessageToRedis()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $RD3Worker = $this->getService();

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

        $RD3Worker->push($message);

        $queueName = 'message_immediate_queue';
        $queueMessage = json_decode($redis->rpop($queueName), true);
        $this->assertEquals('rd3', $queueMessage['target']);
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
        $this->setExpectedException('InvalidArgumentException', 'No method specified in RD3 message', 150660057);

        $httpCurlWorker = $this->getService();

        $message = [
            'target' => 'rd3',
            'url' => 'httpCurl/url',
            'content' => [
                'key1' => 'gagawa',
                'key2' => 'hahaha'
            ]
        ];

        $httpCurlWorker->push($message);
    }

    /**
     * 測試推入 redis 訊息, 帶入不合法的 method
     */
    public function testPushMessageToRedisWithInvalidMethod()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid method in RD3 message', 150660058);

        $httpCurlWorker = $this->getService();

        $message = [
            'target' => 'rd3',
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
        $this->setExpectedException('InvalidArgumentException', 'No url specified in RD3 message', 150660059);

        $httpCurlWorker = $this->getService();

        $message = [
            'target' => 'rd3',
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
        $this->setExpectedException('InvalidArgumentException', 'Invalid url in RD3 message', 150660060);

        $httpCurlWorker = $this->getService();

        $message = [
            'target' => 'rd3',
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
        $this->setExpectedException('InvalidArgumentException', 'No content specified in RD3 message', 150660061);

        $httpCurlWorker = $this->getService();

        $message = [
            'target' => 'rd3',
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
        $this->setExpectedException('InvalidArgumentException', 'Invalid content in RD3 message', 150660062);

        $httpCurlWorker = $this->getService();

        $message = [
            'target' => 'rd3',
            'method' => 'GET',
            'url' => 'httpCurl/url',
            'content' => ''
        ];

        $httpCurlWorker->push($message);
    }

    /**
     * 測試傳送研三訊息
     */
    public function testSendRD3Message()
    {
        $RD3Worker = $this->getService();

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $RD3Worker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = ['result' => 'ok'];
        $response->setContent(json_encode($responseContent));
        $RD3Worker->setResponse($response);

        $message = [
            'target' => 'rd3',
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

        $RD3Worker->send($message);

        $content = file_get_contents($this->httpLogPath);
        $results = explode(PHP_EOL, $content);

        $this->assertContains('GET httpCurl/url?hello1=error1&hello2=error2', $results[1]);
        $this->assertEquals('header1: blabla', trim($results[4]));
        $this->assertEquals('header2: hehehe', trim($results[5]));
        $this->assertEquals('HTTP/1.1 200 OK', trim($results[6]));
    }

    /**
     * 測試傳送研三訊息, response 回傳非 200 錯誤
     */
    public function testSendRD3MessageWithErrorResponse()
    {
        $this->setExpectedException('RuntimeException', 'Send RD3 message failed', 150660056);

        $RD3Worker = $this->getService();

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $RD3Worker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 404 Not Found');
        $RD3Worker->setResponse($response);

        $message = [
            'target' => 'rd3',
            'error_count' => 0,
            'method' => 'GET',
            'url' => 'httpCurl/url',
            'content' => [
                'hello1' => 'error1',
                'hello2' => 'error2'
            ]
        ];

        $RD3Worker->send($message);
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
            ['rd3_ip', '127.0.0.1'],
            ['rd3_domain', 'rd3']
        ];

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->will($this->returnValueMap($getParameterMap));

        $RD3Worker = new RD3Worker();
        $RD3Worker->setContainer($mockContainer);

        return $RD3Worker;
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
