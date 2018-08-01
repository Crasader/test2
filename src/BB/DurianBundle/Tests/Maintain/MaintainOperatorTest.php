<?php
namespace BB\DurianBundle\Tests\Maintain;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Maintain\MaintainOperator;
use Buzz\Message\Response;

class MaintainOperatorTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMaintainData'
        );

        $this->loadFixtures($classnames);
    }

    /**
     * 測試送維護訊息
     */
    public function testSendMessageToMaintain()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $maintain = $em->find('BBDurianBundle:Maintain', 1);

        $client = $this->getMockBuilder('Buzz\Client\Curl')
                       ->getMock();

        $respone = new Response();
        $respone->addHeader('HTTP/1.1 200 OK');
        $responseContent = array(
            'result' => 'ok'
        );
        $respone->setContent(json_encode($responseContent));

        // 當env是test時不會判斷發送是否成功也不會發送，因此將kernel.environment mock成dev
        $options = [
            ['kernel.logs_dir', 'kernel.logs_dir'],
            ['maintain_1_ip', 'maintain_1_ip'],
            ['maintain_1_domain', 'maintain_1_domain'],
            ['kernel.environment', 'dev']
        ];
        $container = $this->mockContainer($options);

        $maintainOperator = new MaintainOperator($container);
        $maintainOperator->setClient($client);
        $maintainOperator->setResponse($respone);

        $nowTime = new \DateTime('2015-08-10 00:00:00');
        $beginAt = new \DateTime('2015-08-10 00:00:00');
        $endAt = new \DateTime('2015-08-10 00:00:00');
        $endAt->add(new \DateInterval('PT5M'));
        $maintain->setBeginAt($beginAt);
        $maintain->setEndAt($endAt);
        $whitelists = [
            '1.2.3.4',
            '5.6.7.8'
        ];

        // 測試is_maintaining是true
        $msgArray = $maintainOperator->prepareMessage($maintain, '1', $nowTime, 2, $whitelists);
        $this->assertEquals(1, $msgArray['msgContent']['code']);
        $this->assertEquals($beginAt->format(\DateTime::ISO8601), $msgArray['msgContent']['begin_at']);
        $this->assertEquals($endAt->format(\DateTime::ISO8601), $msgArray['msgContent']['end_at']);
        $this->assertEquals('球類', $msgArray['msgContent']['msg']);
        $this->assertEquals('true', $msgArray['msgContent']['is_maintaining']);
        $this->assertEquals($whitelists[0], $msgArray['msgContent']['whitelist'][0]);
        $this->assertEquals($whitelists[1], $msgArray['msgContent']['whitelist'][1]);

        // 測試is_maintaining是false
        $beginAt->add(new \DateInterval('PT2M'));

        $msgArray = $maintainOperator->prepareMessage($maintain, '1', $nowTime, 2);
        $this->assertEquals(1, $msgArray['msgContent']['code']);
        $this->assertEquals($beginAt->format(\DateTime::ISO8601), $msgArray['msgContent']['begin_at']);
        $this->assertEquals($endAt->format(\DateTime::ISO8601), $msgArray['msgContent']['end_at']);
        $this->assertEquals('球類', $msgArray['msgContent']['msg']);
        $this->assertEquals('false', $msgArray['msgContent']['is_maintaining']);

        // 測試 $beginAt = $nowTime, is_maintaining是true
        $beginAt = $nowTime;
        $maintain->setBeginAt($beginAt);

        $msgArray = $maintainOperator->prepareMessage($maintain, '1', $nowTime, 2);
        $this->assertEquals(1, $msgArray['msgContent']['code']);
        $this->assertEquals($beginAt->format(\DateTime::ISO8601), $msgArray['msgContent']['begin_at']);
        $this->assertEquals($endAt->format(\DateTime::ISO8601), $msgArray['msgContent']['end_at']);
        $this->assertEquals('球類', $msgArray['msgContent']['msg']);
        $this->assertEquals('true', $msgArray['msgContent']['is_maintaining']);

        // 測試 $endAt = $nowTime, is_maintaining是false
        $endAt = $nowTime;
        $maintain->setendAt($endAt);

        $msgArray = $maintainOperator->prepareMessage($maintain, '1', $nowTime, 2);
        $this->assertEquals(1, $msgArray['msgContent']['code']);
        $this->assertEquals($beginAt->format(\DateTime::ISO8601), $msgArray['msgContent']['begin_at']);
        $this->assertEquals($endAt->format(\DateTime::ISO8601), $msgArray['msgContent']['end_at']);
        $this->assertEquals('球類', $msgArray['msgContent']['msg']);
        $this->assertEquals('false', $msgArray['msgContent']['is_maintaining']);

        $maintainOperator->sendMessageToDestination($msgArray);

        // 歐博分項維護
        $maintain = $em->find('BBDurianBundle:Maintain', 22);

        $nowTime = new \Datetime('2015-08-10 00:00:00');
        $beginAt = new \Datetime('2015-08-10 00:00:00');
        $endAt = new \Datetime('2015-08-10 00:00:00');
        $endAt->add(new \DateInterval('PT5M'));
        $maintain->setBeginAt($beginAt);
        $maintain->setEndAt($endAt);

        $options = [
            ['kernel.logs_dir', 'kernel.logs_dir'],
            ['maintain_3_ip', 'maintain_3_ip'],
            ['maintain_3_domain', 'maintain_3_domain'],
            ['kernel.environment', 'dev']
        ];
        $container = $this->mockContainer($options);

        $maintainOperator = new MaintainOperator($container);
        $maintainOperator->setClient($client);
        $maintainOperator->setResponse($respone);

        $msgArray = $maintainOperator->prepareMessage($maintain, '3', $nowTime, 2);

        $this->assertEquals(22, $msgArray['msgContent']['gamekind']);
        $this->assertEquals('歐博視訊', $msgArray['msgContent']['message']);
        $this->assertEquals('y', $msgArray['msgContent']['state']);

        $maintainOperator->sendMessageToDestination($msgArray);

        // 捕魚大師分項維護
        $maintain = $em->find('BBDurianBundle:Maintain', 38);

        $nowTime = new \Datetime('2017-10-20 00:00:00');
        $beginAt = new \Datetime('2017-10-20 00:00:00');
        $endAt = new \Datetime('2017-10-21 00:00:00');
        $endAt->add(new \DateInterval('PT5M'));
        $maintain->setBeginAt($beginAt);
        $maintain->setEndAt($endAt);

        $options = [
            ['kernel.logs_dir', 'kernel.logs_dir'],
            ['maintain_mobile_ip', 'maintain_mobile_ip'],
            ['maintain_mobile_domain', 'maintain_mobile_domain'],
            ['kernel.environment', 'dev']
        ];
        $container = $this->mockContainer($options);

        $respone = new Response();
        $respone->addHeader('HTTP/1.1 200 OK');
        $responseContent = array(
            'status' => '000'
        );
        $respone->setContent(json_encode($responseContent));

        $maintainOperator = new MaintainOperator($container);
        $maintainOperator->setClient($client);
        $maintainOperator->setResponse($respone);

        $msgArray = $maintainOperator->prepareMessage($maintain, 'mobile', $nowTime, 2);

        $this->assertEquals(38, $msgArray['msgContent']['code']);
        $this->assertEquals($beginAt->format(\DateTime::ISO8601), $msgArray['msgContent']['begin_at']);
        $this->assertEquals($endAt->format(\DateTime::ISO8601), $msgArray['msgContent']['end_at']);
        $this->assertEquals('捕魚大師', $msgArray['msgContent']['msg']);
        $this->assertEquals('true', $msgArray['msgContent']['is_maintaining']);

        $maintainOperator->sendMessageToDestination($msgArray);
    }

    /**
     * 測試送維護訊息時ip為null
     */
    public function testSendMessageToMaintainButIpIsNull()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $maintain = $em->find('BBDurianBundle:Maintain', 1);

        // 先將log檔刪掉
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $dirPath = $logsDir . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'maintain';
        $filePath = $dirPath . DIRECTORY_SEPARATOR . 'send_message_http_detail.log';

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $options = [
            ['kernel.logs_dir', $logsDir],
            ['maintain_1_ip', null],
        ];
        $container = $this->mockContainer($options);

        $maintainOperator = new MaintainOperator($container);

        $nowTime = new \DateTime('2015-08-10 00:00:00');

        // 測試當ip為null時,並不會發送維護訊息
        $msgArray = $maintainOperator->prepareMessage($maintain, '1', $nowTime, 2);
        $maintainOperator->sendMessageToDestination($msgArray);

        // 因不會發送維護訊息,驗證log紀錄為空
        $contents = file_get_contents($filePath);
        $results = explode(PHP_EOL, $contents);

        $this->assertEmpty($results[0]);
    }

    /**
     * 測試送維護訊息時domain為空字串
     */
    public function testSendMessageToMaintainButDomainIsEmpty()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $maintain = $em->find('BBDurianBundle:Maintain', 1);

        // 先將log檔刪掉
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $dirPath = $logsDir . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'maintain';
        $filePath = $dirPath . DIRECTORY_SEPARATOR . 'send_message_http_detail.log';

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $options = [
            ['kernel.logs_dir', $logsDir],
            ['maintain_1_ip', '1.1.1.1'],
            ['maintain_1_domain', '']
        ];
        $container = $this->mockContainer($options);

        $maintainOperator = new MaintainOperator($container);

        $nowTime = new \DateTime('2015-08-10 00:00:00');

        // 測試當domain為空字串時,並不會發送維護訊息
        $msgArray = $maintainOperator->prepareMessage($maintain, '1', $nowTime, 2);
        $maintainOperator->sendMessageToDestination($msgArray);

        // 因不會發送維護訊息,驗證log紀錄為空
        $contents = file_get_contents($filePath);
        $results = explode(PHP_EOL, $contents);

        $this->assertEmpty($results[0]);
    }

    /**
     * 測試送失敗訊息至italking
     */
    public function testSendMessageToItalking()
    {
        $client = $this->getMockBuilder('Buzz\Client\Curl')
                       ->getMock();

        // 測試訊息傳到 italking ok
        $content = [
            'code' => 0,
            'msg' => '傳送成功'
        ];

        $respone = new Response();
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->setContent(json_encode($content));

        // 當env是test時不會判斷發送是否成功也不會發送，因此將kernel.environment mock成dev
        $options = [
            ['kernel.logs_dir', 'kernel.logs_dir'],
            ['italking_ip', 'italking_ip'],
            ['italking_domain', 'italking_domain'],
            ['kernel.environment', 'dev']
        ];
        $container = $this->mockContainer($options);

        $maintainOperator = new MaintainOperator($container);
        $maintainOperator->setClient($client);
        $maintainOperator->setResponse($respone);

        $msgArray = [
            'tag'        => 'italking',
            'method'     => 'POST',
            'msgContent' => [
                'type'     => 'acc_system',
                'message'  => "維護訊息發送錯誤 遊戲代碼:1 發送目標:A 狀態:1",
                'user'     => $this->getContainer()->getParameter('italking_user'),
                'password' => $this->getContainer()->getParameter('italking_password'),
                'code'     => $this->getContainer()->getParameter('italking_gm_code')
            ]
        ];

        $maintainOperator->sendMessageToDestination($msgArray);

        // 測試italking回傳的response是錯誤的status code
        $this->setExpectedException('RuntimeException', 'Send message to italking failed', 150100009);

        $respone = new Response();
        $respone->addHeader('HTTP/1.1 404');
        $container = $this->mockContainer($options);
        $maintainOperator = new MaintainOperator($container);
        $maintainOperator->setClient($client);
        $maintainOperator->setResponse($respone);

        $maintainOperator->sendMessageToDestination($msgArray);
    }

  /**
   * 測試italking回傳的JSON code不為0
   */
  public function testSendMessageToITalkingReturnCodeError()
  {
        $client = $this->getMockBuilder('Buzz\Client\Curl')
            ->getMock();

        $this->setExpectedException('RuntimeException', 'Send message to italking failed');

        $content = [
            'code' => 1,
            'msg' => '傳送的參數格式錯誤'
        ];

        $respone = new Response();
        $respone->addHeader('HTTP/1.1 200 OK');
        $respone->setContent(json_encode($content));

        // env是test不會判斷發送是否成功也不會發送，將kernel.environment mock成dev
        $options = [
            ['kernel.logs_dir', 'kernel.logs_dir'],
            ['italking_ip', 'italking_ip'],
            ['italking_domain', 'italking_domain'],
            ['kernel.environment', 'dev']
        ];
        $container = $this->mockContainer($options);

        $maintainOperator = new MaintainOperator($container);
        $maintainOperator->setClient($client);
        $maintainOperator->setResponse($respone);

        $msgArray = [
            'tag'        => 'italking',
            'method'     => 'POST',
            'msgContent' => [
                'type'     => 'acc_system',
                'message'  => "維護訊息發送錯誤 遊戲代碼:1 發送目標:A 狀態:1",
                'user'     => $this->getContainer()->getParameter('italking_user'),
                'password' => $this->getContainer()->getParameter('italking_password'),
                'code'     => $this->getContainer()->getParameter('italking_gm_code')
            ]
        ];

        $maintainOperator->sendMessageToDestination($msgArray);
  }

  /**
   * 測試italking, 沒有回傳code
   */
  public function testSendMessageToITalkingWithoutReturnCode()
  {
        $client = $this->getMockBuilder('Buzz\Client\Curl')
            ->getMock();

        $this->setExpectedException('RuntimeException', 'Send message to italking failed');

        $respone = new Response();
        $respone->addHeader('HTTP/1.1 200 OK');

        // env是test不會判斷發送是否成功也不會發送，將kernel.environment mock成dev
        $options = [
            ['kernel.logs_dir', 'kernel.logs_dir'],
            ['italking_ip', 'italking_ip'],
            ['italking_domain', 'italking_domain'],
            ['kernel.environment', 'dev']
        ];
        $container = $this->mockContainer($options);

        $maintainOperator = new MaintainOperator($container);
        $maintainOperator->setClient($client);
        $maintainOperator->setResponse($respone);

        $msgArray = [
            'tag'        => 'italking',
            'method'     => 'POST',
            'msgContent' => [
                'type'     => 'acc_system',
                'message'  => "維護訊息發送錯誤 遊戲代碼:1 發送目標:A 狀態:1",
                'user'     => $this->getContainer()->getParameter('italking_user'),
                'password' => $this->getContainer()->getParameter('italking_password'),
                'code'     => $this->getContainer()->getParameter('italking_gm_code')
            ]
        ];

        $maintainOperator->sendMessageToDestination($msgArray);
  }

    /**
     * 測試回傳的response是錯誤的回傳訊息
     */
    public function testCheckMaintainResponseIsFailMsg()
    {
        $this->setExpectedException('RuntimeException', 'Send maintain message failed', 150100010);

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $maintain = $em->find('BBDurianBundle:Maintain', 1);

        $client = $this->getMockBuilder('Buzz\Client\Curl')
                       ->getMock();

        $respone = new Response();
        $respone->addHeader('HTTP/1.1 200 OK');
        $responseContent = array(
            'result' => 'error'
        );
        $respone->setContent(json_encode($responseContent));

        // 當env是test時不會判斷發送是否成功也不會發送，因此將kernel.environment mock成dev
        $options = [
            ['kernel.logs_dir', 'kernel.logs_dir'],
            ['maintain_1_ip', 'maintain_1_ip'],
            ['maintain_1_domain', 'maintain_1_domain'],
            ['kernel.environment', 'dev']
        ];
        $container = $this->mockContainer($options);

        $maintainOperator = new MaintainOperator($container);
        $maintainOperator->setClient($client);
        $maintainOperator->setResponse($respone);

        $nowTime = new \DateTime('2015-08-10 00:00:00');
        $beginAt = new \DateTime('2015-08-10 00:00:00');
        $endAt = new \DateTime('2015-08-10 00:00:00');
        $endAt->add(new \DateInterval('PT5M'));
        $maintain->setBeginAt($beginAt);
        $maintain->setEndAt($endAt);

        $msgArray = $maintainOperator->prepareMessage($maintain, '1', $nowTime, 2);
        $this->assertEquals(1, $msgArray['msgContent']['code']);
        $this->assertEquals('球類', $msgArray['msgContent']['msg']);
        $this->assertEquals('true', $msgArray['msgContent']['is_maintaining']);

        $maintainOperator->sendMessageToDestination($msgArray);
    }

    /**
     * 測試回傳的response是沒有回傳訊息
     */
    public function testCheckMaintainResponseIsEmpty()
    {
        $this->setExpectedException('RuntimeException', 'Send maintain message failed', 150100010);

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $maintain = $em->find('BBDurianBundle:Maintain', 1);

        $client = $this->getMockBuilder('Buzz\Client\Curl')
                       ->getMock();

        $respone = new Response();
        $respone->addHeader('HTTP/1.1 200 OK');

        // 當env是test時不會判斷發送是否成功也不會發送，因此將kernel.environment mock成dev
        $options = [
            ['kernel.logs_dir', 'kernel.logs_dir'],
            ['maintain_3_ip', 'maintain_3_ip'],
            ['maintain_3_domain', 'maintain_3_domain'],
            ['kernel.environment', 'dev']
        ];
        $container = $this->mockContainer($options);

        $maintainOperator = new MaintainOperator($container);
        $maintainOperator->setClient($client);
        $maintainOperator->setResponse($respone);

        $nowTime = new \DateTime('2015-08-10 00:00:00');
        $beginAt = new \DateTime('2015-08-10 00:00:00');
        $endAt = new \DateTime('2015-08-10 00:00:00');
        $endAt->add(new \DateInterval('PT5M'));
        $maintain->setBeginAt($beginAt);
        $maintain->setEndAt($endAt);

        $msgArray = $maintainOperator->prepareMessage($maintain, '3', $nowTime, 2);
        $beginAt->setTimezone(new \DateTimeZone('Etc/GMT+4'));
        $endAt->setTimezone(new \DateTimeZone('Etc/GMT+4'));

        $this->assertEquals(1, $msgArray['msgContent']['gamekind']);
        $this->assertEquals($beginAt->format('Y-m-d'), $msgArray['msgContent']['start_date']);
        $this->assertEquals($beginAt->format('H:i:s'), $msgArray['msgContent']['starttime']);
        $this->assertEquals($endAt->format('Y-m-d'), $msgArray['msgContent']['end_date']);
        $this->assertEquals($endAt->format('H:i:s'), $msgArray['msgContent']['endtime']);
        $this->assertEquals('球類', $msgArray['msgContent']['message']);
        $this->assertEquals('y', $msgArray['msgContent']['state']);

        $maintainOperator->sendMessageToDestination($msgArray);
    }

    /**
     * 測試回傳的response是沒有回傳訊息
     */
    public function testCheckMobileResponseIsEmpty()
    {
        $this->setExpectedException('RuntimeException', 'Send maintain message failed', 150100021);

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $maintain = $em->find('BBDurianBundle:Maintain', 1);

        $client = $this->getMockBuilder('Buzz\Client\Curl')
                       ->getMock();

        $respone = new Response();
        $respone->addHeader('HTTP/1.1 200 OK');

        // 當env是test時不會判斷發送是否成功也不會發送，因此將kernel.environment mock成dev
        $options = [
            ['kernel.logs_dir', 'kernel.logs_dir'],
            ['maintain_mobile_ip', 'maintain_mobile_ip'],
            ['maintain_mobile_domain', 'maintain_mobile_domain'],
            ['kernel.environment', 'dev']
        ];

        $container = $this->mockContainer($options);

        $maintainOperator = new MaintainOperator($container);
        $maintainOperator->setClient($client);
        $maintainOperator->setResponse($respone);

        $nowTime = new \DateTime('2015-08-10 00:00:00');
        $beginAt = new \DateTime('2015-08-10 00:00:00');
        $endAt = new \DateTime('2015-08-10 00:00:00');
        $endAt->add(new \DateInterval('PT5M'));
        $maintain->setBeginAt($beginAt);
        $maintain->setEndAt($endAt);

        $msgArray = $maintainOperator->prepareMessage($maintain, 'mobile', $nowTime, 2);
        $this->assertEquals(1, $msgArray['msgContent']['code']);
        $this->assertEquals('球類', $msgArray['msgContent']['msg']);
        $this->assertEquals('true', $msgArray['msgContent']['is_maintaining']);

        $maintainOperator->sendMessageToDestination($msgArray);
    }

    /**
     * mock container
     *
     * @param array $options
     * @return Container $container
     */
    private function mockContainer($options)
    {
        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['getParameter', 'get'])
            ->getMock();

        $container->expects($this->any())
            ->method('getParameter')
            ->willReturnMap($options);

        $loggerManager = $this->getContainer()->get('durian.logger_manager');
        $map = [['durian.logger_manager', 1, $loggerManager]];

        $container->expects($this->any())
            ->method('get')
            ->willReturnMap($map);

        return $container;
    }
}
