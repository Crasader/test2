<?php

namespace BB\DurianBundle\Tests\EventListener;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use Doctrine\ORM\OptimisticLockException;


class ExceptionListenerTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeDataForTotalCalculate',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserPasswordData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashDepositEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantExtraData',
        ];

        $this->loadFixtures($classnames);
        $this->loadFixtures([], 'share');

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $redis->flushdb();

        //清除post log
        $logPath = $this->getLogfilePath('post.log');

        if (file_exists($logPath)) {
            unlink($logPath);
        }
    }

    /**
     * 測試api噴內部例外訊息不會送至italking, 並只會寫一筆紀錄
     */
    public function testInternalExceptionWillNotSendMessageToItalkingAndWritePostLog()
    {
        // clear postLog
        $logPath = $this->getLogfilePath('post.log');

        if (file_exists($logPath)) {
            unlink($logPath);
        }

        $client = $this->createClient();

        $param = ['currency' => 'CNY'];
        $client->request('POST', '/api/user/2/cash', $param);

        //read log to check content
        $results = explode(PHP_EOL, file_get_contents($logPath));
        $msg = 'result=error&code=150010007&msg=Cash entity for the user already exists';
        $this->assertGreaterThan(0, strpos($results[0], $msg));
        $this->assertEquals('', $results[1]);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $key = 'message_queue';

        $this->assertEquals(0, $redis->llen($key));

        // check host name
        $server = gethostname();
        $this->assertContains($server, $results[0]);

        unlink($logPath);
    }

    /**
     * 測試api跳NotFoundHttpException例外訊息會送至italking
     */
    public function testNotFoundHttpExceptionWillSendMessageToItalking()
    {
        $client = $this->createClient();

        $param = ['currency' => 'CNY'];
        $client->request('POST', '/api/user/cash/cash', $param);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $key = 'message_queue';
        $exception = 'Symfony\Component\HttpKernel\Exception\NotFoundHttpException';
        $msg = 'POST /api/user/cash/cash REQUEST: currency=CNY Failed, ErrorMessage: '.
               'No route found for "POST /api/user/cash/cash"';
        $msg = preg_quote($msg, '/');
        $server = gethostname();

        // 檢查server/client ip與時間格式 ex:[] [2014-11-12 10:50:23] [127.0.0.1]
        $pattern = "/\[$server\] \[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] $msg \[(\d{1,3}\.){3}\d{1,3}\]/";

        $queueMsg = json_decode($redis->rpop($key), true);
        $this->assertEquals('developer_acc', $queueMsg['content']['type']);
        $this->assertEquals($exception, $queueMsg['content']['exception']);
        $this->assertRegExp($pattern, $queueMsg['content']['message']);
    }

    /**
     * 測試api跳MethodNotAllowedHttpException例外訊息會送至italking
     */
    public function testMethodNotAllowedHttpExceptionWillSendMessageToItalking()
    {
        $client = $this->createClient();

        $param = ['currency' => 'CNY'];
        $client->request('DELETE', '/api/user/1/cash', $param);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $key = 'message_queue';
        $exception = 'Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException';
        $msg = 'DELETE /api/user/1/cash REQUEST: currency=CNY Failed, ErrorMessage: '.
               'No route found for "DELETE /api/user/1/cash": Method Not Allowed (Allow: POST, GET, HEAD)';
        $msg = preg_quote($msg, '/');
        $server = gethostname();

        // 檢查server/client ip與時間格式 ex:[] [2014-11-12 10:50:23] [127.0.0.1]
        $pattern = "/\[$server\] \[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] $msg \[(\d{1,3}\.){3}\d{1,3}\]/";

        $queueMsg = json_decode($redis->rpop($key), true);
        $this->assertEquals('developer_acc', $queueMsg['content']['type']);
        $this->assertEquals($exception, $queueMsg['content']['exception']);
        $this->assertRegExp($pattern, $queueMsg['content']['message']);
    }

    /**
     * 測試OnKernelException遇到非Json的request會回傳null
     */
    public function testOnKernelExceptionNotJson()
    {
        $event = $this->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent')
            ->disableOriginalConstructor()
            ->setMethods(['getRequest'])
            ->getMock();

        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->setMethods(['getPathInfo', 'getRequestFormat'])
            ->getMock();

        $event->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($request));

        $request->expects($this->any())
            ->method('getPathInfo')
            ->will($this->returnValue(''));

        $request->expects($this->any())
            ->method('getRequestFormat')
            ->will($this->returnValue('html'));

        $exceptionListener = new \BB\DurianBundle\EventListener\ExceptionListener();

        $result = $exceptionListener->onKernelException($event);
        $this->assertNull($result);
    }

    /**
     * 測試OnKernelResponse遇到非Json的request會回傳null
     */
    public function testOnKernelResponseNotJson()
    {
        $event = $this->getMockBuilder('Symfony\Component\HttpKernel\Event\FilterResponseEvent')
            ->disableOriginalConstructor()
            ->setMethods(['getRequest'])
            ->getMock();

        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->setMethods(['getPathInfo', 'getRequestFormat'])
            ->getMock();

        $event->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($request));

        $request->expects($this->any())
            ->method('getPathInfo')
            ->will($this->returnValue(''));

        $request->expects($this->any())
            ->method('getRequestFormat')
            ->will($this->returnValue('html'));

        $exceptionListener = new \BB\DurianBundle\EventListener\ExceptionListener();

        $result = $exceptionListener->onKernelResponse($event);
        $this->assertNull($result);
    }

    /**
     * 測試OptimisticLockException會出現資料庫忙碌中的訊息
     */
    public function testOptimisticLockException()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $cashFake = $em->find('BBDurianBundle:CashFake', 3);

        $mockEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods([
                'find',
                'persist',
                'flush',
                'clear'
            ])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('find')
            ->will($this->returnValue($cashFake));

        $mockEm->expects($this->any())
            ->method('flush')
            ->will($this->throwException(new OptimisticLockException('Database is busy', null)));

        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);

        $client->request('PUT', '/api/cash_fake/3/disable');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150010071, $ret['code']);
        $this->assertEquals('Database is busy', $ret['msg']);
    }

    /**
     * 測試DBALException會出現資料庫忙碌中的訊息
     */
    public function testDBALException()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $cashFake = $em->find('BBDurianBundle:CashFake', 3);

        $mockEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods([
                'find',
                'persist',
                'flush',
                'clear'
            ])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('find')
            ->will($this->returnValue($cashFake));

        $pdoExcep = new \PDOException('String data, right truncated', 23000);
        $pdoExcep->errorInfo[1] = 1406;
        $exception = new \Doctrine\DBAL\DBALException("Data too long for column 'memo' at row 1", 120009, $pdoExcep);

        $mockEm->expects($this->any())
            ->method('flush')
            ->will($this->throwException($exception));

        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);

        $client->request('PUT', '/api/cash_fake/3/disable');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150780001, $ret['code']);
        $this->assertEquals('Database is busy', $ret['msg']);
    }

    /**
     * 測試OnKernelException要推queue到redis時連線異常
     */
    public function testOnKernelExceptionPushQueueButRedisTimedOut()
    {
        $translator = $this->getContainer()->get('translator');
        $loggerManager = $this->getContainer()->get('durian.logger_manager');

        $italking = $this->getMockBuilder('BB\DurianBundle\Message\ITalkingWorker')
            ->disableOriginalConstructor()
            ->getMock();
        $italking->expects($this->any())
            ->method('push')
            ->will($this->throwException(new \Exception('Connection timed out', SOCKET_ETIMEDOUT)));

        $container = $this->getMockBuilder('\Symfony\Component\DependencyInjection\ContainerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $container->expects($this->at(0))
            ->method('get')
            ->with('translator')
            ->willReturn($translator);
        $container->expects($this->at(1))
            ->method('get')
            ->with('durian.italking_worker')
            ->willReturn($italking);
        $container->expects($this->at(2))
            ->method('get')
            ->with('durian.logger_manager')
            ->willReturn($loggerManager);

        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->setMethods(['getPathInfo', 'getRequestFormat', 'getPreferredLanguage', 'getMethod'])
            ->getMock();
        $request->expects($this->any())
            ->method('getPathInfo')
            ->will($this->returnValue(''));
        $request->expects($this->any())
            ->method('getRequestFormat')
            ->will($this->returnValue('json'));
        $request->expects($this->any())
            ->method('getPreferredLanguage')
            ->will($this->returnValue('tw'));

        $event = $this->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent')
            ->disableOriginalConstructor()
            ->setMethods(['getRequest', 'getException'])
            ->getMock();
        $event->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($request));
        $event->expects($this->any())
            ->method('getException')
            ->willReturn(new \Exception('SQLSTATE[28000] [1045]', 1234));

        $exceptionListener = new \BB\DurianBundle\EventListener\ExceptionListener();
        $exceptionListener->setContainer($container);
        $exceptionListener->onKernelException($event);

        $logDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $logPath = $logDir . DIRECTORY_SEPARATOR . 'test/event_listener_error.log';
        $results = explode(PHP_EOL, file_get_contents($logPath));

        $this->assertContains('Connection timed out', $results[0]);

        unlink($logPath);
    }

    /**
     * 測試遮罩密碼
     */
    public function testMaskPasswordOnKernelResponse()
    {
        $client = $this->createClient();

        //測試密碼含有特殊符號
        $parameters = [
            'username' => 'apple',
            'password' => 'ab&c&ser',
            'alias' => 'Amida'
        ];

        $client->request('PUT', '/api/user/7', $parameters);

        //檢查post log密碼是否被遮罩
        $logPath = $this->getLogfilePath('post.log');
        $results = explode(PHP_EOL, file_get_contents($logPath));
        $this->assertContains('REQUEST: username=apple&password=******&alias=Amida', $results[0]);

        // check host name
        $server = gethostname();
        $this->assertContains($server, $results[0]);
    }

    /**
     * 測試遮罩密鑰
     */
    public function testMaskPrivateKeyOnKernelResponse()
    {
        $client = $this->createClient();

        //測試密鑰含有特殊符號
        $parameters = ['private_key' => '54&3&21;0'];

        $client->request('PUT', '/api/merchant/1', $parameters);

        //檢查post log密鑰是否被遮罩
        $logPath = $this->getLogfilePath('post.log');
        $results = explode(PHP_EOL, file_get_contents($logPath));
        $this->assertContains('REQUEST: private_key=******', $results[0]);

        // check host name
        $server = gethostname();
        $this->assertContains($server, $results[0]);
    }

    /**
     * 測試遮罩商家公私鑰
     */
    public function testMaskMerchantKeyOnKernelResponse()
    {
        $client = $this->createClient();

        $parameters = [
            'public_key_content' => 'MTIzNA==',
            'private_key_content' => 'NTY3OA==',
        ];

        $client->request('PUT', '/api/merchant/1', $parameters);

        // 檢查post log商戶公私鑰是否被遮罩
        $logPath = $this->getLogfilePath('post.log');
        $results = explode(PHP_EOL, file_get_contents($logPath));

        $this->assertContains('REQUEST: public_key_content=******&private_key_content=*****', $results[0]);

        // check host name
        $server = gethostname();
        $this->assertContains($server, $results[0]);
    }

    /**
     * 測試遮罩api密鑰
     */
    public function testMaskApiKeyOnKernelResponse()
    {
        $client = $this->createClient();

        // 測試密鑰含有特殊符號
        $parameters = [
            'host' => 'payment.https.s04.tonglueyun.com',
            'api_key' => '54&3&21;0',
        ];
        $client->request('POST', '/api/remit/domain/2/auto_confirm_config', $parameters);

        // 檢查post log密鑰是否被遮罩
        $logPath = $this->getLogfilePath('post.log');
        $results = explode(PHP_EOL, file_get_contents($logPath));
        $this->assertContains('REQUEST: host=payment.https.s04.tonglueyun.com&api_key=******', $results[0]);

        // check host name
        $server = gethostname();
        $this->assertContains($server, $results[0]);
    }

    /**
     * 測試遮罩verifyKey密鑰
     */
    public function testMaskVerifyKeyOnKernelResponse()
    {
        $client = $this->createClient();

        $parameters = [
            'merchant_extra' => [
                0 => [
                    'name' => 'verifyKey',
                    'value' => 'testKey',
                ],
            ],
        ];

        $client->request('PUT', '/api/merchant/5/merchant_extra', $parameters);

        // 檢查post log密鑰是否被遮罩
        $logPath = $this->getLogfilePath('post.log');
        $results = explode(PHP_EOL, file_get_contents($logPath));

        $request = 'REQUEST: merchant_extra[0][name]=verifyKey&merchant_extra[0][value]=******';
        $response = 'RESPONSE: result=ok&ret[0][merchant_id]=5&ret[0][name]=verifyKey&ret[0][value]=******';

        $this->assertContains($request, $results[0]);
        $this->assertContains($response, $results[0]);

        // check host name
        $server = gethostname();
        $this->assertContains($server, $results[0]);
    }

    /**
     * 測試遮罩比特幣密碼類參數
     */
    public function testMaskBitcoinParamOnKernelResponse()
    {
        $client = $this->createClient();

        $parameters = [
            'wallet_code' => 'wallet',
            'password' => 'password',
            'second_password' => 'second_password',
            'api_code' => 'api_code',
            'xpub' => 'xpub',
        ];

        $client->request('POST', '/api/domain/2/bitcoin_wallet', $parameters);

        // 檢查post log密碼類參數是否被遮罩
        $logPath = $this->getLogfilePath('post.log');
        $results = explode(PHP_EOL, file_get_contents($logPath));

        $request = 'REQUEST: wallet_code=wallet&password=******&second_password=******&api_code=******&xpub=******';

        $this->assertContains($request, $results[0]);

        // check host name
        $server = gethostname();
        $this->assertContains($server, $results[0]);
    }

    /**
     * 測試status code
     */
    public function testStatusCode()
    {
        $client = $this->createClient();

        // 正常例外會丟出status code 200
        $client->request('GET', '/api/user/998899889898/cash');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('No such user', $output['msg']);

        // 內部錯誤例外會丟出status code 500
        $client->request('GET', '/api/iambad/123/wrong');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(500, $client->getResponse()->getStatusCode());
        $this->assertContains('No route found for', $output['msg']);
    }

    /**
     * 測試onKernelException送訊息至italking時是否有遮罩密碼
     */
    public function testOnKernelExceptionMaskPassword()
    {
        $translator = $this->getContainer()->get('translator');
        $italkingWorker = $this->getContainer()->get('durian.italking_worker');

        $container = $this->getMockBuilder('\Symfony\Component\DependencyInjection\ContainerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $container->expects($this->at(0))
            ->method('get')
            ->with('translator')
            ->willReturn($translator);
        $container->expects($this->at(1))
            ->method('get')
            ->with('durian.italking_worker')
            ->willReturn($italkingWorker);

        $event = $this->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent')
            ->disableOriginalConstructor()
            ->setMethods(['getRequest', 'getException'])
            ->getMock();

        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->setMethods(['getPathInfo', 'getRequestFormat', 'getPreferredLanguage', 'getMethod', 'getClientIp'])
            ->getMock();

        $request->request = $this->getMockBuilder('Symfony\Component\HttpFoundation\ParameterBag')
            ->disableOriginalConstructor()
            ->setMethods(['all'])
            ->getMock();

        $request->expects($this->any())
            ->method('getPathInfo')
            ->will($this->returnValue(''));

        $request->expects($this->any())
            ->method('getRequestFormat')
            ->will($this->returnValue('json'));

        $request->expects($this->any())
            ->method('getPreferredLanguage')
            ->will($this->returnValue('tw'));

        $request->expects($this->any())
            ->method('getMethod')
            ->willReturn('POST');

        $request->expects($this->any())
            ->method('getClientIp')
            ->willReturn('');

        $request->request
            ->expects($this->any())
            ->method('all')
            ->willReturn(['password'=>'123']);

        $event->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($request));

        $event->expects($this->any())
            ->method('getException')
            ->willReturn(new \Exception('SQLSTATE[28000] [1045]', 1234));

        $exceptionListener = new \BB\DurianBundle\EventListener\ExceptionListener();
        $exceptionListener->setContainer($container);
        $exceptionListener->onKernelException($event);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $messageQueue = $redis->lrange('message_queue', 0, -1);
        $message = json_decode($messageQueue[0], true);

        $this->assertContains('password=******', $message['content']['message']);
    }
}
