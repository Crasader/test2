<?php
namespace BB\DurianBundle\Tests\Message;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Message\MobileWhitelistWorker;
use Buzz\Message\Response;

class MobileWhitelistWorkerTest extends WebTestCase
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
     * 測試推入mobile訊息至 redis
     */
    public function testPushMobileWhitelistMessageToRedis()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $mobileWhitelistWorker = $this->getService();

        $message = [
            'content' => [
                'ip1',
                'ip2'
            ]
        ];

        $mobileWhitelistWorker->push($message);

        $queueName = 'message_immediate_queue';
        $queueMessage = json_decode($redis->rpop($queueName), true);

        $this->assertEquals('mobile_whitelist', $queueMessage['target']);
        $this->assertEquals(0, $queueMessage['error_count']);
        $this->assertEquals('POST', $queueMessage['method']);
        $this->assertEquals('/UpdateWhitelist', $queueMessage['url']);
        $this->assertEquals($message['content'], $queueMessage['content']);
        $this->assertEquals(['Ekey' => 'mobile'], $queueMessage['header']);
    }

    /**
     * 測試推入 redis 訊息, 未指定 content
     */
    public function testPushMobileWhitelistMessageToRedisWithoutContent()
    {
        $this->setExpectedException('InvalidArgumentException', 'No content specified in Mobile whitelist message', 150660071);

        $httpCurlOperator = $this->getService();

        $message = [];

        $httpCurlOperator->push($message);
    }

    /**
     * 測試推入 redis 訊息, 帶入不合法的 content
     */
    public function testPushMobileWhitelistMessageToRedisWithInvalidContent()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid content in Mobile whitelist message', 150660072);

        $httpCurlOperator = $this->getService();

        $message = ['content' => ''];

        $httpCurlOperator->push($message);
    }

    /**
     * 測試傳送mobile訊息
     */
    public function testSendMobileWhitelistMessage()
    {
        $mobileWhitelistWorker = $this->getService();

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $mobileWhitelistWorker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = ['status' => '000'];
        $response->setContent(json_encode($responseContent));
        $mobileWhitelistWorker->setResponse($response);

        $message = [
            'target' => 'mobile_whitelist',
            'error_count' => 0,
            'method' => 'POST',
            'url' => '/UpdateWhitelist',
            'header' => [
                'Ekey' => 'mobile'
            ],
            'content' => [
                'ip1',
                'ip2'
            ]
        ];

        $mobileWhitelistWorker->send($message);

        $content = file_get_contents($this->httpLogPath);
        $results = explode(PHP_EOL, $content);

        $this->assertContains('POST /UpdateWhitelist', $results[1]);
        $this->assertEquals('Ekey: mobile', trim($results[4]));
        $this->assertEquals('HTTP/1.1 200 OK', trim($results[7]));
    }

    /**
     * 測試傳送mobile訊息, response 回傳非 200 錯誤
     */
    public function testSendMobileWhitelistMessageWithErrorResponse()
    {
        $this->setExpectedException('RuntimeException', 'Send Mobile whitelist message failed', 150660069);

        $mobileWhitelistWorker = $this->getService();

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $mobileWhitelistWorker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 404 Not Found');
        $mobileWhitelistWorker->setResponse($response);

        $message = [
            'target' => 'mobile_whitelist',
            'error_count' => 0,
            'method' => 'POST',
            'url' => '/UpdateWhitelist',
            'header' => [
                'Ekey' => 'mobile'
            ],
            'content' => [
                'ip1',
                'ip2'
            ]
        ];

        $mobileWhitelistWorker->send($message);
    }

    /**
     * 測試傳送mobile訊息, response 未回傳 result
     */
    public function testSendMobileWhitelistMessageWithouResultResponse()
    {
        $this->setExpectedException('RuntimeException', 'Send Mobile whitelist message failed with error response', 150660070);

        $mobileWhitelistWorker = $this->getService();

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $mobileWhitelistWorker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $mobileWhitelistWorker->setResponse($response);

        $message = [
            'target' => 'mobile_whitelist',
            'error_count' => 0,
            'method' => 'POST',
            'url' => '/UpdateWhitelist',
            'header' => [
                'Ekey' => 'mobile'
            ],
            'content' => [
                'ip1',
                'ip2'
            ]
        ];

        $mobileWhitelistWorker->send($message);
    }

    /**
     * 測試傳送mobile訊息, response 的 result 回傳錯誤
     */
    public function testSendMobileWhitelistMessageWithErrorResultResponse()
    {
        $this->setExpectedException('RuntimeException', 'Send Mobile whitelist message failed with error response', 150660070);

        $mobileWhitelistWorker = $this->getService();

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $mobileWhitelistWorker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = ['status' => '001'];
        $response->setContent(json_encode($responseContent));
        $mobileWhitelistWorker->setResponse($response);

        $message = [
            'target' => 'mobile_whitelist',
            'error_count' => 0,
            'method' => 'POST',
            'url' => '/UpdateWhitelist',
            'header' => [
                'Ekey' => 'mobile'
            ],
            'content' => [
                'ip1',
                'ip2'
            ]
        ];

        $mobileWhitelistWorker->send($message);
    }

    /**
     * 測試傳送mobile訊息, response 的 result 型態回傳錯誤
     */
    public function testSendMobileWhitelistMessageWithErrorResultTypeResponse()
    {
        $this->setExpectedException('RuntimeException', 'Send Mobile whitelist message failed with error response', 150660070);

        $mobileWhitelistWorker = $this->getService();

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $mobileWhitelistWorker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = 'error format';
        $response->setContent(json_encode($responseContent));
        $mobileWhitelistWorker->setResponse($response);

        $message = [
            'target' => 'mobile_whitelist',
            'error_count' => 0,
            'method' => 'POST',
            'url' => '/UpdateWhitelist',
            'header' => [
                'Ekey' => 'mobile'
            ],
            'content' => [
                'ip1',
                'ip2'
            ]
        ];

        $mobileWhitelistWorker->send($message);
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
            ['maintain_mobile_ip', '127.0.0.1'],
            ['maintain_mobile_domain', 'mobile_whitelist'],
            ['whitelist_mobile_url', '/UpdateWhitelist'],
            ['whitelist_mobile_key', 'mobile']
        ];

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->will($this->returnValueMap($getParameterMap));

        $mobileWhitelistWorker = new MobileWhitelistWorker();
        $mobileWhitelistWorker->setContainer($mockContainer);

        return $mobileWhitelistWorker;
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
