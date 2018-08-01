<?php
namespace BB\DurianBundle\Tests\Message;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Message\RD3MaintainWorker;
use Buzz\Message\Response;

class RD3MaintainWorkerTest extends WebTestCase
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
     * 測試推入研三維護訊息至 redis
     */
    public function testPushRD3MaintainMessageToRedis()
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $RD3MaintainWorker = $this->getService();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $maintain = $em->find('BBDurianBundle:Maintain', 1);

        // 測試非維護期間
        $nowTime = new \Datetime('2015-01-01 00:00:00');

        $message = [
            'maintain' => $maintain,
            'now_time' => $nowTime
        ];

        $RD3MaintainWorker->push($message);

        $queueName = 'message_immediate_queue';
        $queueMessage = json_decode($redis->rpop($queueName), true);

        $maintainBeginAt = $maintain->getBeginAt()->setTimezone(new \DateTimeZone('Etc/GMT+4'));
        $maintainEndAt = $maintain->getEndAt()->setTimezone(new \DateTimeZone('Etc/GMT+4'));

        $this->assertEquals('rd3_maintain', $queueMessage['target']);
        $this->assertEquals(0, $queueMessage['error_count']);
        $this->assertEquals('GET', $queueMessage['method']);
        $this->assertEquals('/app/WebService/view/display.php/GameRenovate', $queueMessage['url']);
        $this->assertEquals($maintain->getCode(), $queueMessage['content']['gamekind']);
        $this->assertEquals($maintainBeginAt->format('Y-m-d'), $queueMessage['content']['start_date']);
        $this->assertEquals($maintainBeginAt->format('H:i:s'), $queueMessage['content']['starttime']);
        $this->assertEquals($maintainEndAt->format('Y-m-d'), $queueMessage['content']['end_date']);
        $this->assertEquals($maintainEndAt->format('H:i:s'), $queueMessage['content']['endtime']);
        $this->assertEquals($maintain->getMsg(), $queueMessage['content']['message']);
        $this->assertEquals('n', $queueMessage['content']['state']);

        // 測試維護期間
        $nowTime = new \Datetime('2013-01-04 00:00:00');

        $message = [
            'maintain' => $maintain,
            'now_time' => $nowTime
        ];

        $RD3MaintainWorker->push($message);

        $queueMessage = json_decode($redis->rpop($queueName), true);

        $maintainBeginAt = $maintain->getBeginAt()->setTimezone(new \DateTimeZone('Etc/GMT+4'));
        $maintainEndAt = $maintain->getEndAt()->setTimezone(new \DateTimeZone('Etc/GMT+4'));

        $this->assertEquals('rd3_maintain', $queueMessage['target']);
        $this->assertEquals(0, $queueMessage['error_count']);
        $this->assertEquals('GET', $queueMessage['method']);
        $this->assertEquals('/app/WebService/view/display.php/GameRenovate', $queueMessage['url']);
        $this->assertEquals($maintain->getCode(), $queueMessage['content']['gamekind']);
        $this->assertEquals($maintainBeginAt->format('Y-m-d'), $queueMessage['content']['start_date']);
        $this->assertEquals($maintainBeginAt->format('H:i:s'), $queueMessage['content']['starttime']);
        $this->assertEquals($maintainEndAt->format('Y-m-d'), $queueMessage['content']['end_date']);
        $this->assertEquals($maintainEndAt->format('H:i:s'), $queueMessage['content']['endtime']);
        $this->assertEquals($maintain->getMsg(), $queueMessage['content']['message']);
        $this->assertEquals('y', $queueMessage['content']['state']);

        // 測試歐博分項維護
        $maintain = $em->find('BBDurianBundle:Maintain', 22);
        $nowTime = new \Datetime('2015-01-01 00:00:00');

        $message = [
            'maintain' => $maintain,
            'now_time' => $nowTime
        ];

        $RD3MaintainWorker->push($message);

        $queueName = 'message_immediate_queue';
        $queueMessage = json_decode($redis->rpop($queueName), true);

        $this->assertEquals('rd3_maintain', $queueMessage['target']);
        $this->assertEquals(0, $queueMessage['error_count']);
        $this->assertEquals('GET', $queueMessage['method']);
        $this->assertEquals('/app/WebService/view/display.php/GameRenovate', $queueMessage['url']);
        $this->assertEquals($maintain->getCode(), $queueMessage['content']['gamekind']);
        $this->assertEquals($maintainBeginAt->format('Y-m-d'), $queueMessage['content']['start_date']);
        $this->assertEquals($maintainBeginAt->format('H:i:s'), $queueMessage['content']['starttime']);
        $this->assertEquals($maintainEndAt->format('Y-m-d'), $queueMessage['content']['end_date']);
        $this->assertEquals($maintainEndAt->format('H:i:s'), $queueMessage['content']['endtime']);
        $this->assertEquals($maintain->getMsg(), $queueMessage['content']['message']);
        $this->assertEquals('n', $queueMessage['content']['state']);
        $this->assertEquals(30, $queueMessage['timeout']);
    }

    /**
     * 測試推入 redis 訊息, 未指定 maintain
     */
    public function testPushRD3MaintainMessageToRedisWithoutMaintain()
    {
        $this->setExpectedException('InvalidArgumentException', 'No maintain specified in RD3 maintain message', 150660065);

        $RD3MaintainWorker = $this->getService();

        $nowTime = new \Datetime('2015-01-01 00:00:00');
        $nowTime->setTimezone(new \DateTimeZone('Etc/GMT+4'));

        $message = ['now_time' => $nowTime];

        $RD3MaintainWorker->push($message);
    }

    /**
     * 測試推入 redis 訊息, 帶入不合法的 maintain
     */
    public function testPushRD3MaintainMessageToRedisWithInvalidMaintain()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid maintain in RD3 maintain message', 150660066);

        $RD3MaintainWorker = $this->getService();

        $nowTime = new \Datetime('2015-01-01 00:00:00');
        $nowTime->setTimezone(new \DateTimeZone('Etc/GMT+4'));

        $message = [
            'maintain' => '',
            'now_time' => $nowTime
        ];

        $RD3MaintainWorker->push($message);
    }

    /**
     * 測試推入 redis 訊息, 未指定 now_time
     */
    public function testPushRD3MaintainMessageToRedisWithoutNowTime()
    {
        $this->setExpectedException('InvalidArgumentException', 'No now_time specified in RD3 maintain message', 150660067);

        $RD3MaintainWorker = $this->getService();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $maintain = $em->find('BBDurianBundle:Maintain', 1);

        $message = ['maintain' => $maintain];

        $RD3MaintainWorker->push($message);
    }

    /**
     * 測試推入 redis 訊息, 帶入不合法的 now_time
     */
    public function testPushRD3MaintainMessageToRedisWithInvalidNowTime()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid now_time in RD3 maintain message', 150660068);

        $RD3MaintainWorker = $this->getService();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $maintain = $em->find('BBDurianBundle:Maintain', 1);

        $message = [
            'maintain' => $maintain,
            'now_time' => null
        ];

        $RD3MaintainWorker->push($message);
    }

    /**
     * 測試傳送 maintain 訊息
     */
    public function testSendRD3MaintainMessage()
    {
        $RD3MaintainWorker = $this->getService();

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $RD3MaintainWorker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = ['result' => 'true'];
        $response->setContent(json_encode($responseContent));
        $RD3MaintainWorker->setResponse($response);

        $beginAt = new \DateTime('2013-01-04 00:00:00');
        $endAt = new \DateTime('2013-01-04 20:13:14');

        $beginAt->setTimezone(new \DateTimeZone('Etc/GMT+4'));
        $endAt->setTimezone(new \DateTimeZone('Etc/GMT+4'));

        $message = [
            'target' => 'rd3_maintain',
            'error_count' => 0,
            'method' => 'GET',
            'url' => 'httpCurl/url',
            'content' => [
                'gamekind' => 6,
                'start_date' => $beginAt->format('Y-m-d'),
                'starttime' => $beginAt->format('H:i:s'),
                'end_date' => $endAt->format('Y-m-d'),
                'endtime' => $endAt->format('H:i:s'),
                'message' => 'error',
                'state' => 'n'
            ],
            'timeout' => 30
        ];

        $RD3MaintainWorker->send($message);

        $content = file_get_contents($this->httpLogPath);
        $results = explode(PHP_EOL, $content);

        $urlContent = explode(' ', $results[1]);

        $urlQuery = parse_url($urlContent[4], PHP_URL_QUERY);
        $url = parse_url($urlContent[4], PHP_URL_PATH);

        $queueContent = [];
        parse_str($urlQuery, $queueContent);

        $this->assertEquals($message['method'], $urlContent[3]);
        $this->assertEquals($message['url'], $url);
        $this->assertEquals($message['content']['gamekind'], $queueContent['gamekind']);
        $this->assertEquals($message['content']['start_date'], $queueContent['start_date']);
        $this->assertEquals($message['content']['starttime'], $queueContent['starttime']);
        $this->assertEquals($message['content']['end_date'], $queueContent['end_date']);
        $this->assertEquals($message['content']['endtime'], $queueContent['endtime']);
        $this->assertEquals($message['content']['message'], $queueContent['message']);
        $this->assertEquals($message['content']['state'], $queueContent['state']);
        $this->assertEquals('HTTP/1.1 200 OK', trim($results[4]));
    }

    /**
     * 測試傳送 maintain 訊息, response 回傳非 200 錯誤
     */
    public function testSendRD3MaintainMessageWithErrorResponse()
    {
        $this->setExpectedException('RuntimeException', 'Send RD3 maintain message failed', 150660063);

        $RD3MaintainWorker = $this->getService();

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $RD3MaintainWorker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 404 Not Found');
        $RD3MaintainWorker->setResponse($response);

        $beginAt = new \DateTime('2013-01-04 00:00:00');
        $endAt = new \DateTime('2013-01-04 20:13:14');

        $beginAt->setTimezone(new \DateTimeZone('Etc/GMT+4'));
        $endAt->setTimezone(new \DateTimeZone('Etc/GMT+4'));

        $message = [
            'target' => 'rd3_maintain',
            'error_count' => 0,
            'method' => 'GET',
            'url' => 'httpCurl/url',
            'content' => [
                'gamekind' => 6,
                'start_date' => $beginAt->format('Y-m-d'),
                'starttime' => $beginAt->format('H:i:s'),
                'end_date' => $endAt->format('Y-m-d'),
                'endtime' => $endAt->format('H:i:s'),
                'message' => 'error',
                'state' => 'n'
            ]
        ];

        $RD3MaintainWorker->send($message);
    }

    /**
     * 測試傳送 maintain 訊息, response 未回傳 result
     */
    public function testSendRD3MaintainMessageWithouResultResponse()
    {
        $this->setExpectedException('RuntimeException', 'Send RD3 maintain message failed with error response', 150660064);

        $RD3MaintainWorker = $this->getService();

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $RD3MaintainWorker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $RD3MaintainWorker->setResponse($response);

        $beginAt = new \DateTime('2013-01-04 00:00:00');
        $endAt = new \DateTime('2013-01-04 20:13:14');

        $beginAt->setTimezone(new \DateTimeZone('Etc/GMT+4'));
        $endAt->setTimezone(new \DateTimeZone('Etc/GMT+4'));

        $message = [
            'target' => 'rd3_maintain',
            'error_count' => 0,
            'method' => 'GET',
            'url' => 'httpCurl/url',
            'content' => [
                'gamekind' => 6,
                'start_date' => $beginAt->format('Y-m-d'),
                'starttime' => $beginAt->format('H:i:s'),
                'end_date' => $endAt->format('Y-m-d'),
                'endtime' => $endAt->format('H:i:s'),
                'message' => 'error',
                'state' => 'n'
            ]
        ];

        $RD3MaintainWorker->send($message);
    }

    /**
     * 測試傳送 maintain 訊息, response 的 result 回傳錯誤
     */
    public function testSendRD3MaintainMessageWithErrorResultResponse()
    {
        $this->setExpectedException('RuntimeException', 'Send RD3 maintain message failed with error response', 150660064);

        $RD3MaintainWorker = $this->getService();

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $RD3MaintainWorker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = ['result' => 'false'];
        $response->setContent(json_encode($responseContent));
        $RD3MaintainWorker->setResponse($response);

        $beginAt = new \DateTime('2013-01-04 00:00:00');
        $endAt = new \DateTime('2013-01-04 20:13:14');

        $beginAt->setTimezone(new \DateTimeZone('Etc/GMT+4'));
        $endAt->setTimezone(new \DateTimeZone('Etc/GMT+4'));

        $message = [
            'target' => 'rd3_maintain',
            'error_count' => 0,
            'method' => 'GET',
            'url' => 'httpCurl/url',
            'content' => [
                'gamekind' => 6,
                'start_date' => $beginAt->format('Y-m-d'),
                'starttime' => $beginAt->format('H:i:s'),
                'end_date' => $endAt->format('Y-m-d'),
                'endtime' => $endAt->format('H:i:s'),
                'message' => 'error',
                'state' => 'n'
            ]
        ];

        $RD3MaintainWorker->send($message);
    }

    /**
     * 測試傳送 maintain 訊息, response 的 result 型態回傳錯誤
     */
    public function testSendRD3MaintainMessageWithErrorResultTypeResponse()
    {
        $this->setExpectedException('RuntimeException', 'Send RD3 maintain message failed with error response', 150660064);

        $RD3MaintainWorker = $this->getService();

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $RD3MaintainWorker->setClient($client);

        $response = new Response();
        $response->addHeader('HTTP/1.1 200 OK');
        $responseContent = ['result' => true];
        $response->setContent(json_encode($responseContent));
        $RD3MaintainWorker->setResponse($response);

        $beginAt = new \DateTime('2013-01-04 00:00:00');
        $endAt = new \DateTime('2013-01-04 20:13:14');

        $beginAt->setTimezone(new \DateTimeZone('Etc/GMT+4'));
        $endAt->setTimezone(new \DateTimeZone('Etc/GMT+4'));

        $message = [
            'target' => 'rd3_maintain',
            'error_count' => 0,
            'method' => 'GET',
            'url' => 'httpCurl/url',
            'content' => [
                'gamekind' => 6,
                'start_date' => $beginAt->format('Y-m-d'),
                'starttime' => $beginAt->format('H:i:s'),
                'end_date' => $endAt->format('Y-m-d'),
                'endtime' => $endAt->format('H:i:s'),
                'message' => 'error',
                'state' => 'n'
            ]
        ];

        $RD3MaintainWorker->send($message);
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
            ['rd3_domain', 'maintain3']
        ];

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->will($this->returnValueMap($getParameterMap));

        $RD3MaintainWorker = new RD3MaintainWorker();
        $RD3MaintainWorker->setContainer($mockContainer);

        return $RD3MaintainWorker;
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
