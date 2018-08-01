<?php
namespace BB\DurianBundle\Tests\Message;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Message\RD1Worker;
use Buzz\Message\Response;

class RD1WorkerTest extends WebTestCase
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
     * 測試推入研一訊息至 redis
     */
    public function testPushRD1MessageToRedis()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $RD1Worker = $this->getService();

        $message = [
            'method' => 'GET',
            'url' => 'httpCurl/url',
            'content' => [
                'key1' => 'gagawa',
                'key2' => 'hahaha'
            ],
            'header' => [
                'header1' => 'blabla',
                'header2' => 'hehehe'
            ],
            'target_param' => 'rd1_maintain'
        ];

        $RD1Worker->push($message);

        $queueName = 'message_immediate_queue';
        $queueMessage = json_decode($redis->rpop($queueName), true);

        $this->assertEquals(0, $queueMessage['error_count']);
        $this->assertEquals($message['method'], $queueMessage['method']);
        $this->assertEquals($message['url'], $queueMessage['url']);
        $this->assertEquals($message['content'], $queueMessage['content']);
        $this->assertEquals($message['header'], $queueMessage['header']);
        $this->assertEquals($message['target_param'], $queueMessage['target_param']);
    }

    /**
     * 測試推入 redis 訊息, 未指定 method
     */
    public function testPushMessageToRedisWithoutMethod()
    {
        $this->setExpectedException('InvalidArgumentException', 'No method specified in RD1 message', 150660023);

        $httpCurlWorker = $this->getService();

        $message = [
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

        $httpCurlWorker->push($message);
    }

    /**
     * 測試推入 redis 訊息, 帶入不合法的 method
     */
    public function testPushMessageToRedisWithInvalidMethod()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid method in RD1 message', 150660024);

        $httpCurlWorker = $this->getService();

        $message = [
            'method' => '',
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
     * 測試推入 redis 訊息, 未指定 url
     */
    public function testPushMessageToRedisWithoutUrl()
    {
        $this->setExpectedException('InvalidArgumentException', 'No url specified in RD1 message', 150660025);

        $httpCurlWorker = $this->getService();

        $message = [
            'method' => 'GET',
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
     * 測試推入 redis 訊息, 帶入不合法的 url
     */
    public function testPushMessageToRedisWithInvalidUrl()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid url in RD1 message', 150660026);

        $httpCurlWorker = $this->getService();

        $message = [
            'method' => 'GET',
            'url' => null,
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
     * 測試推入 redis 訊息, 未指定 content
     */
    public function testPushMessageToRedisWithoutContent()
    {
        $this->setExpectedException('InvalidArgumentException', 'No content specified in RD1 message', 150660027);

        $httpCurlWorker = $this->getService();

        $message = [
            'method' => 'GET',
            'url' => 'httpCurl/url',
            'header' => [
                'header1' => 'blabla',
                'header2' => 'hehehe'
            ]
        ];

        $httpCurlWorker->push($message);
    }

    /**
     * 測試推入 redis 訊息, 帶入不合法的 content
     */
    public function testPushMessageToRedisWithInvalidContent()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid content in RD1 message', 150660028);

        $httpCurlWorker = $this->getService();

        $message = [
            'method' => 'GET',
            'url' => 'httpCurl/url',
            'content' => '',
            'header' => [
                'header1' => 'blabla',
                'header2' => 'hehehe'
            ]
        ];

        $httpCurlWorker->push($message);
    }

    /**
     * 測試傳送研一訊息
     */
    public function testSendRD1Message()
    {
        $RD1Worker = $this->getService();

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $RD1Worker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = ['result' => 'ok'];
        $response->setContent(json_encode($responseContent));
        $RD1Worker->setResponse($response);

        $message = [
            'target' => 'rd1',
            'error_count' => 0,
            'method' => 'GET',
            'url' => 'httpCurl/url',
            'header' => [
                'Api-Key' => '123'
            ],
            'content' => [
                'hello1' => 'error1',
                'hello2' => 'error2'
            ],
            'target_param' => 'rd1_maintain'
        ];

        $RD1Worker->send($message);

        $content = file_get_contents($this->httpLogPath);
        $results = explode(PHP_EOL, $content);

        $this->assertContains('GET httpCurl/url?hello1=error1&hello2=error2', $results[1]);
        $this->assertEquals('Api-Key: 123', trim($results[3]));
        $this->assertEquals('HTTP/1.1 200 OK', trim($results[4]));
    }

    /**
     * 測試傳送研一訊息, response 回傳非 200 錯誤
     */
    public function testSendRD1MessageWithErrorResponse()
    {
        $this->setExpectedException('RuntimeException', 'Send RD1 message failed', 150660022);

        $RD1Worker = $this->getService();

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $RD1Worker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 404 Not Found');
        $RD1Worker->setResponse($response);

        $message = [
            'target' => 'rd1',
            'error_count' => 0,
            'method' => 'GET',
            'url' => 'httpCurl/url',
            'header' => [
                'api_key' => '123'
            ],
            'content' => [
                'hello1' => 'error1',
                'hello2' => 'error2'
            ]
        ];

        $RD1Worker->send($message);
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
            ['rd1_ip', '127.0.0.1'],
            ['rd1_domain', 'rd1']
        ];

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->will($this->returnValueMap($getParameterMap));

        $RD1Worker = new RD1Worker();
        $RD1Worker->setContainer($mockContainer);

        return $RD1Worker;
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
