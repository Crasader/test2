<?php
namespace BB\DurianBundle\Tests\Message;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Message\RD1MaintainWorker;
use Buzz\Message\Response;

class RD1MaintainWorkerTest extends WebTestCase
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

        $this->loadFixtures(['BB\DurianBundle\Tests\DataFixtures\ORM\LoadMaintainData']);

        $logDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $fileName = 'send_message_http_detail.log';
        $this->httpLogPath = $logDir . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . $fileName;
    }

    /**
     * 測試推入研一維護訊息至 redis
     */
    public function testPushRD1MaintainMessageToRedis()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $RD1MaintainWorker = $this->getService();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $maintain = $em->find('BBDurianBundle:Maintain', 1);

        // 測試非維護期間
        $nowTime = new \Datetime('2015-01-01 00:00:00');

        $message = [
            'maintain' => $maintain,
            'now_time' => $nowTime
        ];

        $RD1MaintainWorker->push($message);

        $queueName = 'message_immediate_queue';
        $queueMessage = json_decode($redis->rpop($queueName), true);

        $this->assertEquals('rd1_maintain', $queueMessage['target']);
        $this->assertEquals(0, $queueMessage['error_count']);
        $this->assertEquals('GET', $queueMessage['method']);
        $this->assertEquals('/api/index.php?module=MaintainAPI&method=SetMaintain', $queueMessage['url']);
        $this->assertEquals($maintain->getCode(), $queueMessage['content']['code']);
        $this->assertEquals($maintain->getBeginAt()->format(\DateTime::ISO8601), $queueMessage['content']['begin_at']);
        $this->assertEquals($maintain->getEndAt()->format(\DateTime::ISO8601), $queueMessage['content']['end_at']);
        $this->assertEquals($maintain->getMsg(), $queueMessage['content']['msg']);
        $this->assertEquals('false', $queueMessage['content']['is_maintaining']);

        // 測試維護期間
        $nowTime = new \Datetime('2013-01-04 00:00:00');

        $message = [
            'maintain' => $maintain,
            'now_time' => $nowTime
        ];

        $RD1MaintainWorker->push($message);

        $queueMessage = json_decode($redis->rpop($queueName), true);

        $this->assertEquals('rd1_maintain', $queueMessage['target']);
        $this->assertEquals(0, $queueMessage['error_count']);
        $this->assertEquals('GET', $queueMessage['method']);
        $this->assertEquals('/api/index.php?module=MaintainAPI&method=SetMaintain', $queueMessage['url']);
        $this->assertEquals($maintain->getCode(), $queueMessage['content']['code']);
        $this->assertEquals($maintain->getBeginAt()->format(\DateTime::ISO8601), $queueMessage['content']['begin_at']);
        $this->assertEquals($maintain->getEndAt()->format(\DateTime::ISO8601), $queueMessage['content']['end_at']);
        $this->assertEquals($maintain->getMsg(), $queueMessage['content']['msg']);
        $this->assertEquals('true', $queueMessage['content']['is_maintaining']);
    }

    /**
     * 測試推入 redis 訊息, 未指定 maintain
     */
    public function testPushRD1MaintainMessageToRedisWithoutMaintain()
    {
        $this->setExpectedException('InvalidArgumentException', 'No maintain specified in RD1 maintain message', 150660031);

        $RD1MaintainWorker = $this->getService();

        $nowTime = new \Datetime('2015-01-01 00:00:00');

        $message = ['now_time' => $nowTime];

        $RD1MaintainWorker->push($message);
    }

    /**
     * 測試推入 redis 訊息, 帶入不合法的 maintain
     */
    public function testPushRD1MaintainMessageToRedisWithInvalidMaintain()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid maintain in RD1 maintain message', 150660032);

        $RD1MaintainWorker = $this->getService();

        $nowTime = new \Datetime('2015-01-01 00:00:00');

        $message = [
            'maintain' => '',
            'now_time' => $nowTime
        ];

        $RD1MaintainWorker->push($message);
    }

    /**
     * 測試推入 redis 訊息, 未指定 now_time
     */
    public function testPushRD1MaintainMessageToRedisWithoutNowTime()
    {
        $this->setExpectedException('InvalidArgumentException', 'No now_time specified in RD1 maintain message', 150660033);

        $RD1MaintainWorker = $this->getService();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $maintain = $em->find('BBDurianBundle:Maintain', 1);

        $message = ['maintain' => $maintain];

        $RD1MaintainWorker->push($message);
    }

    /**
     * 測試推入 redis 訊息, 帶入不合法的 now_time
     */
    public function testPushRD1MaintainMessageToRedisWithInvalidNowTime()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid now_time in RD1 maintain message', 150660034);

        $RD1MaintainWorker = $this->getService();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $maintain = $em->find('BBDurianBundle:Maintain', 1);

        $message = [
            'maintain' => $maintain,
            'now_time' => null
        ];

        $RD1MaintainWorker->push($message);
    }

    /**
     * 測試傳送 maintain 訊息
     */
    public function testSendRD1MaintainMessage()
    {
        $RD1MaintainWorker = $this->getService();

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $RD1MaintainWorker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = ['result' => 'true'];
        $response->setContent(json_encode($responseContent));
        $RD1MaintainWorker->setResponse($response);

        $beginAt = new \DateTime('2013-01-04 00:00:00');
        $endAt = new \DateTime('2013-01-04 20:13:14');

        $message = [
            'target' => 'rd1_maintain',
            'error_count' => 0,
            'method' => 'GET',
            'url' => 'maintain1/url',
            'content' => [
                'code' => 6,
                'begin_at' => $beginAt->format(\DateTime::ISO8601),
                'end_at' => $endAt->format(\DateTime::ISO8601),
                'msg' => 'error',
                'is_maintaining' => 'false'
            ]
        ];

        $RD1MaintainWorker->send($message);

        $content = file_get_contents($this->httpLogPath);
        $results = explode(PHP_EOL, $content);
        $urlContent = explode(' ', $results[1]);

        $urlQuery = parse_url($urlContent[4], PHP_URL_QUERY);
        $url = parse_url($urlContent[4], PHP_URL_PATH);

        $queueContent = [];
        parse_str($urlQuery, $queueContent);

        $this->assertEquals($message['method'], $urlContent[3]);
        $this->assertEquals($message['url'], $url);
        $this->assertEquals($message['content']['code'], $queueContent['code']);
        $this->assertEquals($message['content']['begin_at'], $queueContent['begin_at']);
        $this->assertEquals($message['content']['end_at'], $queueContent['end_at']);
        $this->assertEquals($message['content']['msg'], $queueContent['msg']);
        $this->assertEquals($message['content']['is_maintaining'], $queueContent['is_maintaining']);
        $this->assertEquals('HTTP/1.1 200 OK', trim($results[4]));
    }

    /**
     * 測試傳送 maintain 訊息, response 回傳非 200 錯誤
     */
    public function testSendRD1MaintainMessageWithErrorResponse()
    {
        $this->setExpectedException('RuntimeException', 'Send RD1 maintain message failed', 150660029);

        $RD1MaintainWorker = $this->getService();

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $RD1MaintainWorker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 404 Not Found');
        $RD1MaintainWorker->setResponse($response);

        $beginAt = new \DateTime('2013-01-04 00:00:00');
        $endAt = new \DateTime('2013-01-04 20:13:14');

        $message = [
            'target' => 'rd1_maintain',
            'error_count' => 0,
            'method' => 'GET',
            'url' => 'maintain1/url',
            'content' => [
                'code' => 6,
                'begin_at' => $beginAt->format(\DateTime::ISO8601),
                'end_at' => $endAt->format(\DateTime::ISO8601),
                'msg' => 'error',
                'is_maintaining' => 'false'
            ]
        ];

        $RD1MaintainWorker->send($message);
    }

    /**
     * 測試傳送 maintain 訊息, response 未回傳 result
     */
    public function testSendRD1MaintainMessageWithouResultResponse()
    {
        $this->setExpectedException('RuntimeException', 'Send RD1 maintain message failed with error response', 150660030);

        $RD1MaintainWorker = $this->getService();

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $RD1MaintainWorker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $RD1MaintainWorker->setResponse($response);

        $beginAt = new \DateTime('2013-01-04 00:00:00');
        $endAt = new \DateTime('2013-01-04 20:13:14');

        $message = [
            'target' => 'rd1_maintain',
            'error_count' => 0,
            'method' => 'GET',
            'url' => 'maintain1/url',
            'content' => [
                'code' => 6,
                'begin_at' => $beginAt->format(\DateTime::ISO8601),
                'end_at' => $endAt->format(\DateTime::ISO8601),
                'msg' => 'error',
                'is_maintaining' => 'false'
            ]
        ];

        $RD1MaintainWorker->send($message);
    }

    /**
     * 測試傳送 maintain 訊息, response 的 result 回傳錯誤
     */
    public function testSendRD1MaintainMessageWithErrorResultResponse()
    {
        $this->setExpectedException('RuntimeException', 'Send RD1 maintain message failed with error response', 150660030);

        $RD1MaintainWorker = $this->getService();

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $RD1MaintainWorker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = ['result' => 'false'];
        $response->setContent(json_encode($responseContent));
        $RD1MaintainWorker->setResponse($response);

        $beginAt = new \DateTime('2013-01-04 00:00:00');
        $endAt = new \DateTime('2013-01-04 20:13:14');

        $message = [
            'target' => 'rd1_maintain',
            'error_count' => 0,
            'method' => 'GET',
            'url' => 'maintain1/url',
            'content' => [
                'code' => 6,
                'begin_at' => $beginAt->format(\DateTime::ISO8601),
                'end_at' => $endAt->format(\DateTime::ISO8601),
                'msg' => 'error',
                'is_maintaining' => 'false'
            ]
        ];

        $RD1MaintainWorker->send($message);
    }

    /**
     * 測試傳送 maintain 訊息, response 的 result 型態回傳錯誤
     */
    public function testSendRD1MaintainMessageWithErrorResultTypeResponse()
    {
        $this->setExpectedException('RuntimeException', 'Send RD1 maintain message failed with error response', 150660030);

        $RD1MaintainWorker = $this->getService();

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $RD1MaintainWorker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = ['result' => true];
        $response->setContent(json_encode($responseContent));
        $RD1MaintainWorker->setResponse($response);

        $beginAt = new \DateTime('2013-01-04 00:00:00');
        $endAt = new \DateTime('2013-01-04 20:13:14');

        $message = [
            'target' => 'rd1_maintain',
            'error_count' => 0,
            'method' => 'GET',
            'url' => 'maintain1/url',
            'content' => [
                'code' => 6,
                'begin_at' => $beginAt->format(\DateTime::ISO8601),
                'end_at' => $endAt->format(\DateTime::ISO8601),
                'msg' => 'error',
                'is_maintaining' => 'false'
            ]
        ];

        $RD1MaintainWorker->send($message);
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
            ['rd1_maintain_ip', '127.0.0.1'],
            ['rd1_maintain_domain', 'maintain1']
        ];

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->will($this->returnValueMap($getParameterMap));

        $RD1MaintainWorker = new RD1MaintainWorker();
        $RD1MaintainWorker->setContainer($mockContainer);

        return $RD1MaintainWorker;
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
