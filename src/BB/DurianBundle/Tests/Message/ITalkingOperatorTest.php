<?php
namespace BB\DurianBundle\Tests\Message;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use Buzz\Message\Response;

class ITalkingOperatorTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = array();

        $this->loadFixtures($classnames);

        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir') . '/test';
        $fileName = 'to_italking_http_detail.log';

        $filePath = $logsDir . DIRECTORY_SEPARATOR . $fileName;

        // 若原本有log檔，則刪除再產生新的log檔
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * 刪除產生的log檔
     */
    public function tearDown() {
        parent::tearDown();

        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir') . '/test';
        $fileName = 'to_italking_http_detail.log';

        $filePath = $logsDir . DIRECTORY_SEPARATOR . $fileName;

        // 若原本有log檔，則刪除再產生新的log檔
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * 測試iTalking連線狀態是否正常
     */
    public function testCheckITalkingStatus()
    {

        $client = $this->getMockBuilder('Buzz\Client\Curl')
                       ->getMock();

        $respone = new Response();
        $respone->addHeader('HTTP/1.1 200 OK');

        $italkingOperator = $this->getContainer()->get('durian.italking_operator');

        $italkingOperator->setClient($client);
        $italkingOperator->setResponse($respone);

        $result = $italkingOperator->checkITalkingStatus();

        $this->assertTrue($result);
    }

    /**
     * 測試iTalking連線狀態是否正常HTTP狀態錯誤
     */
    public function testCheckITalkingStatusWithHttpFail()
    {
        $this->setExpectedException('Buzz\Exception\ClientException', '<url> malformed');

        $italkingOperator = $this->getContainer()->get('durian.italking_operator');

        $italkingOperator->checkITalkingStatus();
    }

    /**
     * 測試iTalking連線，HTTP狀態錯誤
     */
    public function testCheckITalkingStatusWithErrorHttpStatus()
    {
        $this->setExpectedException('Exception', 'Fail to send message');

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 404');

        $italkingOperator = $this->getContainer()->get('durian.italking_operator');

        $italkingOperator->setClient($client);
        $italkingOperator->setResponse($response);

        $italkingOperator->checkITalkingStatus();
    }

    /**
     * 測試送訊息至iTalking
     */
    public function testSendMessageToITalking()
    {
        $client = $this->getMockBuilder('Buzz\Client\Curl')
            ->getMock();

        $content = [
            'code' => 0,
            'msg' => '傳送成功'
        ];

        $respone = new Response();
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->setContent(json_encode($content));

        $italkingOperator = $this->getContainer()->get('durian.italking_operator');

        $italkingOperator->setClient($client);
        $italkingOperator->setResponse($respone);

        $msgArray = array(
            'type'    => 'test',
            'message' => 'test message'
        );

        $result = $italkingOperator->sendMessageToITalking($msgArray);

        $this->assertTrue($result);
    }

    /**
     * 測試送訊息至iTalking，回傳JSON code不為0
     */
    public function testSendMessageToITalkingReturnCodeError()
    {
        $this->setExpectedException('Exception', 'Fail to send message');

        $client = $this->getMockBuilder('Buzz\Client\Curl')
            ->getMock();

        $content = [
            'code' => 1,
            'msg' => '傳送的參數格式錯誤'
        ];

        $respone = new Response();
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->setContent(json_encode($content));

        $italkingOperator = $this->getContainer()->get('durian.italking_operator');

        $italkingOperator->setClient($client);
        $italkingOperator->setResponse($respone);

        $msgArray = [
            'type'    => 'test',
            'message' => 'test message'
        ];

        $italkingOperator->sendMessageToITalking($msgArray);
    }

    /**
     * 測試送訊息至iTalking，沒有回傳code
     */
    public function testSendMessageToITalkingWithoutReturnCode()
    {
        $this->setExpectedException('Exception', 'Fail to send message');

        $client = $this->getMockBuilder('Buzz\Client\Curl')
            ->getMock();

        $respone = new Response();
        $respone->addHeader('HTTP/1.1 200 OK');

        $italkingOperator = $this->getContainer()->get('durian.italking_operator');

        $italkingOperator->setClient($client);
        $italkingOperator->setResponse($respone);

        $msgArray = [
            'type'    => 'test',
            'message' => 'test message'
        ];

        $italkingOperator->sendMessageToITalking($msgArray);
    }

    /**
     * 測試送訊息至iTalking
     */
    public function testSendMessageToITalkingWithHttpFail()
    {
        $this->setExpectedException('Buzz\Exception\ClientException', '<url> malformed');

        $italkingOperator = $this->getContainer()->get('durian.italking_operator');

        $msgArray = array(
            'type'    => 'test',
            'message' => 'test message'
        );

        $italkingOperator->sendMessageToITalking($msgArray);
    }

    /**
     * 測試送訊息至iTalking，雖連線成功但請求失敗
     */
    public function testSendMessageToITalkingWithErrorHttpStatus()
    {
        $this->setExpectedException('Exception', 'Fail to send message');

        $client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $response = new Response();
        $response->addHeader('HTTP/1.1 404');

        $italkingOperator = $this->getContainer()->get('durian.italking_operator');

        $italkingOperator->setClient($client);
        $italkingOperator->setResponse($response);

        $msgArray = [
            'type'    => 'test',
            'message' => 'test message'
        ];

        $italkingOperator->sendMessageToITalking($msgArray);
    }

    /**
     * 測試payment_alarm push博九的italking message到queue
     */
    public function testPushMessageToQueueWithDomainBet9()
    {
        $italkingOperator = $this->getContainer()->get('durian.italking_operator');
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $italkingOperator->pushMessageToQueue('payment_alarm', 'test', 98);
        $msgQueue = $redis->rpop('italking_message_queue');
        $msg = json_decode($msgQueue, true);

        $this->assertEquals('payment_alarm', $msg['type']);
        $this->assertEquals('test', $msg['message']);
        $this->assertEquals(98, $msg['code']);
    }

    /**
     * 測試payment_alarm push Esball的italking message到queue
     */
    public function testPushMessageToQueueWithDomainEsball()
    {
        $italkingOperator = $this->getContainer()->get('durian.italking_operator');
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $italkingOperator->pushMessageToQueue('payment_alarm', 'test', 6);
        $msgQueue = $redis->rpop('italking_message_queue');
        $msg = json_decode($msgQueue, true);

        $this->assertEquals('payment_alarm', $msg['type']);
        $this->assertEquals('test', $msg['message']);
        $this->assertEquals(6, $msg['code']);
    }

    /**
     * 測試account_fail push博九的italking message到queue
     */
    public function testAccountFailPushMessageToQueueWithDomainBet9()
    {
        $italkingOperator = $this->getContainer()->get('durian.italking_operator');
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $italkingOperator->pushMessageToQueue('account_fail', 'test', 98);
        $msgQueue = $redis->rpop('italking_message_queue');
        $msg = json_decode($msgQueue, true);

        $this->assertEquals('account_fail', $msg['type']);
        $this->assertEquals('test', $msg['message']);
        $this->assertEquals(98, $msg['code']);
    }

    /**
     * 測試account_fail push Esball的italking message到queue
     */
    public function testAccountFailPushMessageToQueueWithDomainEsball()
    {
        $italkingOperator = $this->getContainer()->get('durian.italking_operator');
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $italkingOperator->pushMessageToQueue('account_fail', 'test', 6);
        $msgQueue = $redis->rpop('italking_message_queue');
        $msg = json_decode($msgQueue, true);

        $this->assertEquals('account_fail', $msg['type']);
        $this->assertEquals('test', $msg['message']);
        $this->assertEquals(6, $msg['code']);
    }

    /**
     * 測試 account_fail push kresball 的 italking message 到 queue
     */
    public function testAccountFailPushMessageToQueueWithDomainKresball()
    {
        $italkingOperator = $this->getContainer()->get('durian.italking_operator');
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $italkingOperator->pushMessageToQueue('account_fail_kr', 'test', 3820175);
        $msgQueue = $redis->rpop('italking_message_queue');
        $msg = json_decode($msgQueue, true);

        $this->assertEquals('account_fail_kr', $msg['type']);
        $this->assertEquals('test', $msg['message']);
        $this->assertEquals(140502001, $msg['code']);
    }

    /**
     * 測試 account_fail push esball global 的 italking message 到 queue
     */
    public function testAccountFailPushMessageToQueueWithDomainEsballGlobal()
    {
        $italkingOperator = $this->getContainer()->get('durian.italking_operator');
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $italkingOperator->pushMessageToQueue('account_fail', 'test', 3819935);
        $msgQueue = $redis->rpop('italking_message_queue');
        $msg = json_decode($msgQueue, true);

        $this->assertEquals('account_fail', $msg['type']);
        $this->assertEquals('test', $msg['message']);
        $this->assertEquals(141023001, $msg['code']);
    }

    /**
     * 測試 account_fail push eslot 的 italking message 到 queue
     */
    public function testAccountFailPushMessageToQueueWithDomainEslot()
    {
        $italkingOperator = $this->getContainer()->get('durian.italking_operator');
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $italkingOperator->pushMessageToQueue('account_fail', 'test', 3820190);
        $msgQueue = $redis->rpop('italking_message_queue');
        $msg = json_decode($msgQueue, true);

        $this->assertEquals('account_fail', $msg['type']);
        $this->assertEquals('test', $msg['message']);
        $this->assertEquals(160810001, $msg['code']);
    }

    /**
     * 測試push GM 的italking message到queue
     */
    public function testPushMessageToQueueForGM()
    {
        $italkingOperator = $this->getContainer()->get('durian.italking_operator');
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $italkingOperator->pushMessageToQueue('developer_acc', 'test');
        $msgQueue = $redis->rpop('italking_message_queue');
        $msg = json_decode($msgQueue, true);

        $this->assertEquals('developer_acc', $msg['type']);
        $this->assertEquals('test', $msg['message']);
        $this->assertEquals(1, $msg['code']);
    }

    /**
     * 測試push italkingExceptionMessage到queue
     */
    public function testPushExceptionToQueue()
    {
        $italkingOperator = $this->getContainer()->get('durian.italking_operator');
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $italkingOperator->pushExceptionToQueue('developer_acc', 'Exception', 'test');
        $msgQueue = $redis->rpop('italking_exception_queue');
        $msg = json_decode($msgQueue, true);

        $this->assertEquals('developer_acc', $msg['type']);
        $this->assertEquals('Exception', $msg['exception']);
        $this->assertEquals('test', $msg['message']);
        $this->assertEquals(1, $msg['code']);
    }
}
