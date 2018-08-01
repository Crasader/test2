<?php
namespace BB\DurianBundle\Tests\Message;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Message\RD1WhitelistWorker;
use Buzz\Message\Response;

class RD1WhitelistWorkerTest extends WebTestCase
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
    public function testPushRD1WhitelistMessageToRedis()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $RD1WhitelistWorker = $this->getService();

        $message = [
            'content' => [
                'key1' => 'gagawa',
                'key2' => 'hahaha'
            ]
        ];

        $RD1WhitelistWorker->push($message);

        $queueName = 'message_immediate_queue';
        $queueMessage = json_decode($redis->rpop($queueName), true);

        $this->assertEquals('rd1_maintain', $queueMessage['target_param']);
        $this->assertEquals('rd1_whitelist', $queueMessage['target']);
        $this->assertEquals(0, $queueMessage['error_count']);
        $this->assertEquals('POST', $queueMessage['method']);
        $this->assertEquals('/api/index.php?module=MaintainAPI&method=setWhiteList', $queueMessage['url']);
        $this->assertEquals($message['content'], $queueMessage['content']);
        $this->assertEquals(['Api-Key' => '123'], $queueMessage['header']);
    }

    /**
     * 測試推入 redis 訊息, 未指定 content
     */
    public function testPushRD1WhitelistMessageToRedisWithoutContent()
    {
        $this->setExpectedException('InvalidArgumentException', 'No content specified in RD1 whitelist message', 150660037);

        $httpCurlOperator = $this->getService();

        $message = [];

        $httpCurlOperator->push($message);
    }

    /**
     * 測試推入 redis 訊息, 帶入不合法的 content
     */
    public function testPushRD1WhitelistMessageToRedisWithInvalidContent()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid content in RD1 whitelist message', 150660038);

        $httpCurlOperator = $this->getService();

        $message = ['content' => ''];

        $httpCurlOperator->push($message);
    }

    /**
     * 測試傳送研一訊息
     */
    public function testSendRD1WhitelistMessage()
    {
        $RD1WhitelistWorker = $this->getService();

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $RD1WhitelistWorker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = ['result' => 'ok'];
        $response->setContent(json_encode($responseContent));
        $RD1WhitelistWorker->setResponse($response);

        $message = [
            'target' => 'rd1_whitelist',
            'error_count' => 0,
            'method' => 'GET',
            'url' => 'httpCurl/url',
            'header' => [
                'Api-Key' => '123'
            ],
            'content' => [
                'hello1' => 'error1',
                'hello2' => 'error2'
            ]
        ];

        $RD1WhitelistWorker->send($message);

        $content = file_get_contents($this->httpLogPath);
        $results = explode(PHP_EOL, $content);

        $this->assertContains('GET httpCurl/url?hello1=error1&hello2=error2', $results[1]);
        $this->assertEquals('Api-Key: 123', trim($results[4]));
        $this->assertEquals('HTTP/1.1 200 OK', trim($results[5]));
    }

    /**
     * 測試傳送研一訊息, response 回傳非 200 錯誤
     */
    public function testSendRD1WhitelistMessageWithErrorResponse()
    {
        $this->setExpectedException('RuntimeException', 'Send RD1 whitelist message failed', 150660035);

        $RD1WhitelistWorker = $this->getService();

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $RD1WhitelistWorker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 404 Not Found');
        $RD1WhitelistWorker->setResponse($response);

        $message = [
            'target' => 'rd1_whitelist',
            'error_count' => 0,
            'method' => 'GET',
            'url' => 'httpCurl/url',
            'header' => [
                'Api-Key' => '123'
            ],
            'content' => [
                'hello1' => 'error1',
                'hello2' => 'error2'
            ]
        ];

        $RD1WhitelistWorker->send($message);
    }

    /**
     * 測試傳送研一訊息, response 未回傳 result
     */
    public function testSendRD1WhitelistMessageWithouResultResponse()
    {
        $this->setExpectedException('RuntimeException', 'Send RD1 whitelist message failed with error response', 150660036);

        $RD1WhitelistWorker = $this->getService();

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $RD1WhitelistWorker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $RD1WhitelistWorker->setResponse($response);

        $message = [
            'target' => 'rd1_whitelist',
            'error_count' => 0,
            'method' => 'GET',
            'url' => 'httpCurl/url',
            'header' => [
                'Api-Key' => '123'
            ],
            'content' => [
                'hello1' => 'error1',
                'hello2' => 'error2'
            ]
        ];

        $RD1WhitelistWorker->send($message);
    }

    /**
     * 測試傳送研一訊息, response 的 result 回傳錯誤
     */
    public function testSendRD1WhitelistMessageWithErrorResultResponse()
    {
        $this->setExpectedException('RuntimeException', 'Send RD1 whitelist message failed with error response', 150660036);

        $RD1WhitelistWorker = $this->getService();

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $RD1WhitelistWorker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = ['result' => 'false'];
        $response->setContent(json_encode($responseContent));
        $RD1WhitelistWorker->setResponse($response);

        $message = [
            'target' => 'rd1_whitelist',
            'error_count' => 0,
            'method' => 'GET',
            'url' => 'httpCurl/url',
            'header' => [
                'Api-Key' => '123'
            ],
            'content' => [
                'hello1' => 'error1',
                'hello2' => 'error2'
            ]
        ];

        $RD1WhitelistWorker->send($message);
    }

    /**
     * 測試傳送研一訊息, response 的 result 型態回傳錯誤
     */
    public function testSendRD1WhitelistMessageWithErrorResultTypeResponse()
    {
        $this->setExpectedException('RuntimeException', 'Send RD1 whitelist message failed with error response', 150660036);

        $RD1WhitelistWorker = $this->getService();

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $RD1WhitelistWorker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = ['result' => true];
        $response->setContent(json_encode($responseContent));
        $RD1WhitelistWorker->setResponse($response);

        $message = [
            'target' => 'rd1_whitelist',
            'error_count' => 0,
            'method' => 'GET',
            'url' => 'httpCurl/url',
            'header' => [
                'Api-Key' => '123'
            ],
            'content' => [
                'hello1' => 'error1',
                'hello2' => 'error2'
            ]
        ];

        $RD1WhitelistWorker->send($message);
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
            ['rd1_domain', 'whitelist1'],
            ['rd1_api_key', '123']
        ];

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->will($this->returnValueMap($getParameterMap));

        $RD1WhitelistWorker = new RD1WhitelistWorker();
        $RD1WhitelistWorker->setContainer($mockContainer);

        return $RD1WhitelistWorker;
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
