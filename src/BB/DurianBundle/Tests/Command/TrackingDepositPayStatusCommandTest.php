<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Command\TrackingDepositPayStatusCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use BB\DurianBundle\Entity\Merchant;
use BB\DurianBundle\Entity\CashDepositEntry;

class TrackingDepositPayStatusCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashDepositEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayData',
        ];
        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigData'
        ];
        $this->loadFixtures($classnames, 'share');

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $sql = 'INSERT INTO payment_gateway (id, code, name, post_url, auto_reop, reop_url, verify_url, verify_ip, ' .
            'bind_ip, label, removed, withdraw, hot, order_id, upload_key, deposit, mobile, withdraw_url, ' .
            'withdraw_host, random_float) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $params = [
            8, // id
            'IPS', // code
            'IPS', // name
            '', //post_url
            1, // auto_reop
            '', // reop_url
            '', // verify_url
            '', // verify_ip
            '', // bind_ip
            'IPS', // label
            0, // removed
            0, // withdraw
            1, // hot
            1, // order_id
            0, // upload_key
            1, // deposit
            0, // mobile
            '', // withdraw_url
            '', // withdraw_host
            0, // random_float
        ];

        $em->getConnection()->executeUpdate($sql, $params);

        $paymentGateway8 = $em->getRepository('BBDurianBundle:PaymentGateway')->find(8);

        $merchant8 = new Merchant($paymentGateway8, CashDepositEntry::PAYWAY_CASH, 'IPS', '1234567890', 1, 156);
        $merchant8->setId(8);
        $em->persist($merchant8);

        $merchant9 = new Merchant($paymentGateway8, CashDepositEntry::PAYWAY_CASH, 'IPS', '123456789', 1, 156);
        $merchant9->setId(9);
        $em->persist($merchant9);

        $cash = $em->find('BBDurianBundle:Cash', 7);
        $paymentVendor = $em->find('BBDurianBundle:PaymentVendor', 1);

        $data = [
            'amount' => 100,
            'offer' => 10,
            'fee' => -1,
            'payway_rate' => 0.2,
            'rate' => 0.2,
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'payway_currency' => 156,
            'abandon_offer' => false,
            'web_shop' => true,
            'currency' => 901,
            'level_id' => 2,
            'telephone' => '123456789',
            'postcode' => 400,
            'address' => '地球',
            'email' => 'earth@gmail.com'
        ];

        $entry4 = new CashDepositEntry($cash, $merchant8, $paymentVendor, $data);
        $entry4->setId(201605230000000001);
        $entry4->setAt('20160523000000');
        $em->persist($entry4);

        $entry5 = new CashDepositEntry($cash, $merchant8, $paymentVendor, $data);
        $entry5->setId(201605230000000002);
        $entry5->setAt('20160523000000');
        $em->persist($entry5);

        $entry6 = new CashDepositEntry($cash, $merchant9, $paymentVendor, $data);
        $entry6->setId(201605230000000003);
        $entry6->setAt('20160523000000');
        $em->persist($entry6);

        $em->flush();
    }

    /**
     * 測試檢查已確認入款明細支付狀態缺少支付平台
     */
    public function testTrackingDepositPayStatusWithoutPaymentGateway()
    {
        $output = $this->runCommand('durian:tracking-deposit-pay-status');

        $results = explode(PHP_EOL, trim($output));
        $this->assertContains('InvalidArgumentException', $results[2]);
        $this->assertContains('Invalid PaymentGatewayId', $results[3]);
    }

    /**
     * 測試檢查已確認入款明細支付狀態缺少時間參數
     */
    public function testTrackingDepositPayStatusWithoutTimeParameter()
    {
        // 都沒帶入時間參數
        $param = ['--payment-gateway-id' => 77];
        $output = $this->runCommand('durian:tracking-deposit-pay-status', $param);

        $results = explode(PHP_EOL, trim($output));
        $this->assertContains('InvalidArgumentException', $results[2]);
        $this->assertContains('No start or end specified', $results[3]);

        // 只帶入參數 start
        $param = [
            '--payment-gateway-id' => 77,
            '--start' => '20141209000000',
        ];
        $output = $this->runCommand('durian:tracking-deposit-pay-status', $param);

        $results = explode(PHP_EOL, trim($output));
        $this->assertContains('InvalidArgumentException', $results[2]);
        $this->assertContains('No start or end specified', $results[3]);

        // 只帶入參數 end
        $param = [
            '--payment-gateway-id' => 77,
            '--end' => '20141209000010',
        ];
        $output = $this->runCommand('durian:tracking-deposit-pay-status', $param);

        $results = explode(PHP_EOL, trim($output));
        $this->assertContains('InvalidArgumentException', $results[2]);
        $this->assertContains('No start or end specified', $results[3]);
    }

    /**
     * 測試檢查已確認入款明細支付狀態交易失敗
     */
    public function testTrackingDepositPayStatusWithPaymentGatewayId()
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $emShare = $container->get('doctrine.orm.share_entity_manager');
        $italkingOperator = $container->get('durian.italking_operator');
        $redis = $container->get('snc_redis.default_client');

        $cdeEntry = $em->getRepository('BBDurianBundle:CashDepositEntry')
            ->findOneBy(['id' => '201304280000000001']);
        $cdeEntry->confirm();
        $cdeEntry->setEntryId(1);
        $em->flush();

        $exception = new \Exception('Some error message', 180035);

        $mockOperator = $this->getMockBuilder('BB\DurianBundle\Payment\Operator')
            ->disableOriginalConstructor()
            ->setMethods(['paymentTracking'])
            ->getMock();
        $mockOperator->expects($this->any())
            ->method('paymentTracking')
            ->will($this->throwException($exception));

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $getMap = [
            ['snc_redis.default_client', 1, $redis],
            ['durian.italking_operator', 1, $italkingOperator],
            ['durian.payment_operator', 1, $mockOperator],
            ['doctrine.orm.entity_manager', 1, $em],
            ['doctrine.orm.share_entity_manager', 1, $emShare],
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));
        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn(['172.26.54.42', '172.26.54.41']);

        $application = new Application();
        $command = new TrackingDepositPayStatusCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $params = [
            'command' => $command->getName(),
            '--payment-gateway-id' => 1,
            '--start' => '20151028000000',
            '--end' => $cdeEntry->getConfirmAt()->format('YmdHis'),
        ];

        $commandTester = new CommandTester($command);
        $commandTester->execute($params);

        $results = explode(PHP_EOL, $commandTester->getDisplay());

        $msg = '支付平台: BBPay, 廳: domain2@cm, 線上入款單號: 201304280000000001, 會員帳號: tester, 金額: ' .
            '100, 查詢結果: 交易失敗.';

        $this->assertContains($msg, $results[0]);

        // 檢查查詢異常是否寫入redis
        $entries = json_decode($redis->rpop('tracking_deposit_pay_status_queue'), true);

        $this->assertEquals('201304280000000001', $entries['entry_id']);
        $this->assertEquals('100', $entries['amount']);
        $this->assertEquals('tester', $entries['username']);
        $this->assertEquals('domain2@cm', $entries['domain']);
        $this->assertEquals(0, $entries['retry']);

        // 檢查italking訊息
        $italking = json_decode($redis->rpop('italking_message_queue'), true);

        $this->assertEquals('developer_acc', $italking['type']);
        $this->assertContains($msg, $italking['message']);
        $this->assertEquals(1, $italking['code']);
    }

    /**
     * 測試重覆檢查有問題的已確認入款明細支付狀態
     */
    public function testRetryTrackingDepositPayStatus()
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $emShare = $container->get('doctrine.orm.share_entity_manager');
        $italkingOperator = $container->get('durian.italking_operator');
        $redis = $container->get('snc_redis.default_client');

        $param = [
            'entry_id' => '201304280000000001',
            'amount' => '100',
            'username' => 'tester',
            'domain' => 'domain2@cm',
            'retry' => 1,
            'url' => 'http://test.com',
            'server_ip' => '127.0.0.1',
            'payment_gateway_id' => 1,
            'payment_gateway_name' => 'BBPay',
        ];

        $redis->rpush('tracking_deposit_pay_status_queue', json_encode($param));

        $exception = new \Exception('Some error message', 180035);

        $mockOperator = $this->getMockBuilder('BB\DurianBundle\Payment')
            ->disableOriginalConstructor()
            ->setMethods(['paymentTracking'])
            ->getMock();
        $mockOperator->expects($this->any())
            ->method('paymentTracking')
            ->will($this->throwException($exception));

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $getMap = [
            ['snc_redis.default_client', 1, $redis],
            ['durian.italking_operator', 1, $italkingOperator],
            ['durian.payment_operator', 1, $mockOperator],
            ['doctrine.orm.entity_manager', 1, $em],
            ['doctrine.orm.share_entity_manager', 1, $emShare],
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        $application = new Application();
        $command = new TrackingDepositPayStatusCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $params = [
            'command' => $command->getName(),
            '--retry' => true,
        ];

        $commandTester = new CommandTester($command);
        $commandTester->execute($params);

        // 檢查查詢異常是否寫入redis
        $entries = json_decode($redis->rpop('tracking_deposit_pay_status_queue'), true);

        $this->assertEquals('201304280000000001', $entries['entry_id']);
        $this->assertEquals('100', $entries['amount']);
        $this->assertEquals('tester', $entries['username']);
        $this->assertEquals('domain2@cm', $entries['domain']);
        $this->assertEquals(2, $entries['retry']);
        $this->assertEquals('http://test.com', $entries['url']);
        $this->assertEquals('127.0.0.1', $entries['server_ip']);
        $this->assertEquals('BBPay', $entries['payment_gateway_name']);
    }

    /**
     * 測試重覆檢查有問題的已確認入款明細支付狀態訂單已支付
     */
    public function testRetryTrackingDepositPayStatusConfirm()
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $emShare = $container->get('doctrine.orm.share_entity_manager');
        $italkingOperator = $container->get('durian.italking_operator');
        $redis = $container->get('snc_redis.default_client');

        $cdeEntry = $em->getRepository('BBDurianBundle:CashDepositEntry')
            ->findOneBy(['id' => '201304280000000001']);
        $cdeEntry->confirm();
        $cdeEntry->setEntryId(1);
        $em->flush();

        $param = [
            'entry_id' => '201304280000000001',
            'amount' => '100',
            'username' => 'tester',
            'domain' => 'domain2@cm',
            'retry' => 1,
            'url' => 'http://test.com',
            'server_ip' => '127.0.0.1',
            'payment_gateway_id' => 1,
            'payment_gateway_name' => 'BBPay',
        ];

        $redis->rpush('tracking_deposit_pay_status_queue', json_encode($param));

        $mockOperator = $this->getMockBuilder('BB\DurianBundle\Payment')
            ->disableOriginalConstructor()
            ->setMethods(['paymentTracking'])
            ->getMock();
        $mockOperator->expects($this->any())
            ->method('paymentTracking')
            ->willReturn('ok');

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $getMap = [
            ['snc_redis.default_client', 1, $redis],
            ['durian.italking_operator', 1, $italkingOperator],
            ['durian.payment_operator', 1, $mockOperator],
            ['doctrine.orm.entity_manager', 1, $em],
            ['doctrine.orm.share_entity_manager', 1, $emShare],
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        $application = new Application();
        $command = new TrackingDepositPayStatusCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $params = [
            'command' => $command->getName(),
            '--retry' => true,
        ];

        $commandTester = new CommandTester($command);
        $commandTester->execute($params);

        $results = explode(PHP_EOL, $commandTester->getDisplay());

        $msg = '支付平台: BBPay, 線上入款單號: 201304280000000001 訂單已支付.';

        $this->assertContains($msg, $results[0]);
    }

    /**
     * 測試重覆檢查有問題的已確認入款明細支付狀態超過5次
     */
    public function testRetryTrackingDepositPayStatusOverFiveTimes()
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $emShare = $container->get('doctrine.orm.share_entity_manager');
        $italkingOperator = $container->get('durian.italking_operator');
        $redis = $container->get('snc_redis.default_client');

        $param = [
            'entry_id' => '201304280000000001',
            'amount' => '100',
            'username' => 'tester',
            'domain' => 'domain2@cm',
            'retry' => 5,
            'url' => 'http://test.com',
            'server_ip' => '127.0.0.1',
            'payment_gateway_id' => 1,
            'payment_gateway_name' => 'BBPay',
        ];

        $redis->rpush('tracking_deposit_pay_status_queue', json_encode($param));

        $exception = new \Exception('Some error message', 180035);

        $mockOperator = $this->getMockBuilder('BB\DurianBundle\Payment')
            ->disableOriginalConstructor()
            ->setMethods(['paymentTracking'])
            ->getMock();
        $mockOperator->expects($this->any())
            ->method('paymentTracking')
            ->will($this->throwException($exception));

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $getMap = [
            ['snc_redis.default_client', 1, $redis],
            ['durian.italking_operator', 1, $italkingOperator],
            ['durian.payment_operator', 1, $mockOperator],
            ['doctrine.orm.entity_manager', 1, $em],
            ['doctrine.orm.share_entity_manager', 1, $emShare],
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        $application = new Application();
        $command = new TrackingDepositPayStatusCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $params = [
            'command' => $command->getName(),
            '--retry' => true,
        ];

        $commandTester = new CommandTester($command);
        $commandTester->execute($params);

        $results = explode(PHP_EOL, $commandTester->getDisplay());

        $msg = '支付平台: BBPay, 廳: domain2@cm, 線上入款單號: 201304280000000001, 會員帳號: tester, 金額: ' .
            '100, 查詢結果: 交易失敗.';

        $this->assertContains($msg, $results[0]);

        // 檢查italking訊息
        $italking = json_decode($redis->rpop('italking_message_queue'), true);

        $this->assertEquals('developer_acc', $italking['type']);
        $this->assertContains($msg, $italking['message']);
        $this->assertEquals(1, $italking['code']);

        $gmMsg = "查詢結果: 交易失敗.請依照下列流程處理：\n\n請客服至GM管理系統-系統客服管理-異常入款批次停權-現金異常入款-錯誤情況選擇：" .
            "「異常入款」，將會員帳號停權並寄發廳主訊息\n\n後續由業主自行判斷此筆入款是否正常，若異常業主可自行停用第三方支付。";
        $italking1 = json_decode($redis->rpop('italking_message_queue'), true);

        $this->assertEquals('acc_system', $italking1['type']);
        $this->assertContains($gmMsg, $italking1['message']);
        $this->assertEquals(1, $italking1['code']);

        // 檢查redis是否記錄異常入款
        $statusError = json_decode($redis->rpop('deposit_pay_status_error_queue'), true);

        $this->assertEquals($param['entry_id'], $statusError['entry_id']);
        $this->assertEquals($param['payment_gateway_id'], $statusError['payment_gateway_id']);
        $this->assertEquals('180035', $statusError['code']);
    }

    /**
     * 測試檢查已確認入款明細支付狀態訂單已支付
     */
    public function testTrackingDepositPayStatus()
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $emShare = $container->get('doctrine.orm.share_entity_manager');
        $italkingOperator = $container->get('durian.italking_operator');
        $redis = $container->get('snc_redis.default_client');

        $cdeEntry = $em->getRepository('BBDurianBundle:CashDepositEntry')
            ->findOneBy(['id' => '201304280000000001']);
        $cdeEntry->confirm();
        $cdeEntry->setEntryId(1);
        $em->flush();

        $mockOperator = $this->getMockBuilder('BB\DurianBundle\Payment\Operator')
            ->disableOriginalConstructor()
            ->setMethods(['paymentTracking'])
            ->getMock();
        $mockOperator->expects($this->any())
            ->method('paymentTracking')
            ->willReturn('ok');

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $getMap = [
            ['snc_redis.default_client', 1, $redis],
            ['durian.italking_operator', 1, $italkingOperator],
            ['durian.payment_operator', 1, $mockOperator],
            ['doctrine.orm.entity_manager', 1, $em],
            ['doctrine.orm.share_entity_manager', 1, $emShare],
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));
        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn(['172.26.54.42', '172.26.54.41']);

        $application = new Application();
        $command = new TrackingDepositPayStatusCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $params = [
            'command' => $command->getName(),
            '--payment-gateway-id' => 1,
            '--start' => '20151028000000',
            '--end' => $cdeEntry->getConfirmAt()->format('YmdHis'),
        ];

        $commandTester = new CommandTester($command);
        $commandTester->execute($params);

        $results = explode(PHP_EOL, $commandTester->getDisplay());

        $msg = '支付平台: BBPay, 線上入款單號: 201304280000000001 訂單已支付.';

        $this->assertContains($msg, $results[0]);
    }

    /**
     * 測試檢查已確認入款明細支付狀態連線失敗超過5次須送italking訊息
     */
    public function testTrackingDepositPayStatusTimeOutOverFiveTimes()
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $emShare = $container->get('doctrine.orm.share_entity_manager');
        $italkingOperator = $container->get('durian.italking_operator');
        $redis = $container->get('snc_redis.default_client');

        $param = [
            'entry_id' => '201304280000000001',
            'amount' => '100',
            'username' => 'tester',
            'domain' => 'domain2@cm',
            'retry' => 5,
            'url' => 'http://test.com',
            'server_ip' => '127.0.0.1',
            'payment_gateway_id' => 1,
            'payment_gateway_name' => 'BBPay',
        ];

        $redis->rpush('tracking_deposit_pay_status_queue', json_encode($param));

        $exception = new \Exception('Time Out', 180088);

        $mockOperator = $this->getMockBuilder('BB\DurianBundle\Payment')
            ->disableOriginalConstructor()
            ->setMethods(['paymentTracking'])
            ->getMock();
        $mockOperator->expects($this->any())
            ->method('paymentTracking')
            ->willThrowException($exception);

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $getMap = [
            ['snc_redis.default_client', 1, $redis],
            ['durian.italking_operator', 1, $italkingOperator],
            ['durian.payment_operator', 1, $mockOperator],
            ['doctrine.orm.entity_manager', 1, $em],
            ['doctrine.orm.share_entity_manager', 1, $emShare],
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->willReturnMap($getMap);

        $application = new Application();
        $command = new TrackingDepositPayStatusCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $params = [
            'command' => $command->getName(),
            '--retry' => true,
        ];

        $commandTester = new CommandTester($command);
        $commandTester->execute($params);

        $results = explode(PHP_EOL, $commandTester->getDisplay());

        $message = '支付平台: BBPay, 支付平台連線失敗. 請DC-OP-維護組協助檢查連線是否異常，若DC-OP-維護組調整後，警報器仍' .
            '持續跳出訊息，請通知RD5-電子商務部上線檢查，測試語法如下：';

        $this->assertContains($message, $results[0]);
        $this->assertEquals('Server: 127.0.0.1', $results[1]);
        $this->assertEquals("time curl 'http://test.com'", $results[2]);

        // 檢查redis是否記錄異常入款
        $this->assertNull($redis->rpop('deposit_pay_status_error_queue'));
    }

    /**
     * 測試重覆檢查有問題的已確認入款明細支付狀態為商戶簽名錯誤
     */
    public function testRetryTrackingDepositPayStatusWithSignError()
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $emShare = $container->get('doctrine.orm.share_entity_manager');
        $italkingOperator = $container->get('durian.italking_operator');
        $redis = $container->get('snc_redis.default_client');

        $param = [
            'entry_id' => '201304280000000001',
            'amount' => '100',
            'username' => 'tester',
            'domain' => 'domain2@cm',
            'retry' => 5,
            'url' => 'http://test.com',
            'server_ip' => '127.0.0.1',
            'payment_gateway_id' => 1,
            'payment_gateway_name' => 'BBPay',
        ];

        $redis->rpush('tracking_deposit_pay_status_queue', json_encode($param));

        $exception = new \Exception('PaymentGateway error, Merchant sign error', 180127);

        $mockOperator = $this->getMockBuilder('BB\DurianBundle\Payment')
            ->disableOriginalConstructor()
            ->setMethods(['paymentTracking'])
            ->getMock();
        $mockOperator->expects($this->any())
            ->method('paymentTracking')
            ->will($this->throwException($exception));

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $getMap = [
            ['snc_redis.default_client', 1, $redis],
            ['durian.italking_operator', 1, $italkingOperator],
            ['durian.payment_operator', 1, $mockOperator],
            ['doctrine.orm.entity_manager', 1, $em],
            ['doctrine.orm.share_entity_manager', 1, $emShare],
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        $application = new Application();
        $command = new TrackingDepositPayStatusCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $params = [
            'command' => $command->getName(),
            '--retry' => true,
        ];

        $commandTester = new CommandTester($command);
        $commandTester->execute($params);

        $results = explode(PHP_EOL, $commandTester->getDisplay());

        $msg = '支付平台: BBPay, 廳: domain2@cm, 線上入款單號: 201304280000000001, 會員帳號: tester, 金額: ' .
            '100, 查詢結果: 商戶簽名錯誤.';

        $this->assertContains($msg, $results[0]);

        // 檢查italking訊息
        $italking = json_decode($redis->rpop('italking_message_queue'), true);

        $this->assertEquals('developer_acc', $italking['type']);
        $this->assertContains($msg, $italking['message']);
        $this->assertEquals(1, $italking['code']);

        $gmMsg = "查詢結果: 商戶簽名錯誤.請依照下列流程處理：\n\n請客服至GM管理系統-系統客服管理-異常入款批次停權-現金異常入款-錯誤情況選" .
            "擇：「異常入款」，將會員帳號停權並寄發廳主訊息\n\n若業主收到廳主訊息後，反映未更改密鑰，請通知研五-電子商務工程師上線檢查。";

        $italking1 = json_decode($redis->rpop('italking_message_queue'), true);

        $this->assertEquals('acc_system', $italking1['type']);
        $this->assertContains($gmMsg, $italking1['message']);
        $this->assertEquals(1, $italking1['code']);

        // 檢查redis是否記錄異常入款
        $statusError = json_decode($redis->rpop('deposit_pay_status_error_queue'), true);

        $this->assertEquals($param['entry_id'], $statusError['entry_id']);
        $this->assertEquals($param['payment_gateway_id'], $statusError['payment_gateway_id']);
        $this->assertEquals('180127', $statusError['code']);
    }

    /**
     * 測試重覆檢查有問題的已確認入款明細支付平台返回格式不合法
     */
    public function testRetryTrackingDepositPayStatusWithReturnInvalidXMLFormat()
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $emShare = $container->get('doctrine.orm.share_entity_manager');
        $italkingOperator = $container->get('durian.italking_operator');
        $redis = $container->get('snc_redis.default_client');

        $param = [
            'entry_id' => '201304280000000001',
            'amount' => '100',
            'username' => 'tester',
            'domain' => 'domain2@cm',
            'retry' => 5,
            'url' => 'http://test.com',
            'server_ip' => '127.0.0.1',
            'payment_gateway_id' => 1,
            'payment_gateway_name' => 'BBPay',
        ];

        $redis->rpush('tracking_deposit_pay_status_queue', json_encode($param));

        $exception = new \Exception('Invalid XML format', 180121);

        $mockOperator = $this->getMockBuilder('BB\DurianBundle\Payment')
            ->disableOriginalConstructor()
            ->setMethods(['paymentTracking'])
            ->getMock();
        $mockOperator->expects($this->any())
            ->method('paymentTracking')
            ->will($this->throwException($exception));

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $getMap = [
            ['snc_redis.default_client', 1, $redis],
            ['durian.italking_operator', 1, $italkingOperator],
            ['durian.payment_operator', 1, $mockOperator],
            ['doctrine.orm.entity_manager', 1, $em],
            ['doctrine.orm.share_entity_manager', 1, $emShare],
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        $application = new Application();
        $command = new TrackingDepositPayStatusCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $params = [
            'command' => $command->getName(),
            '--retry' => true,
        ];

        $commandTester = new CommandTester($command);
        $commandTester->execute($params);

        $results = explode(PHP_EOL, $commandTester->getDisplay());

        $msg = '支付平台: BBPay, 廳: domain2@cm, 線上入款單號: 201304280000000001, 會員帳號: tester, 金額: ' .
            '100, 查詢結果: 支付平台返回格式不合法. 請通知研五-電子商務工程師上線檢查.';

        $this->assertContains($msg, $results[0]);

        // 檢查italking訊息
        $italking = json_decode($redis->rpop('italking_message_queue'), true);

        $this->assertEquals('developer_acc', $italking['type']);
        $this->assertContains($msg, $italking['message']);
        $this->assertEquals(1, $italking['code']);

        $gmMsg = "查詢結果: 支付平台返回格式不合法. 請通知研五-電子商務工程師上線檢查.請依照下列流程處理：\n\n請客服至GM管理系統-系統客" .
            "服管理-異常入款批次停權-現金異常入款-錯誤情況選擇：「異常入款」，將會員帳號停權並寄發廳主訊息";

        $italking1 = json_decode($redis->rpop('italking_message_queue'), true);

        $this->assertEquals('acc_system', $italking1['type']);
        $this->assertContains($gmMsg, $italking1['message']);
        $this->assertEquals(1, $italking1['code']);

        // 檢查redis是否記錄異常入款
        $statusError = json_decode($redis->rpop('deposit_pay_status_error_queue'), true);

        $this->assertEquals($param['entry_id'], $statusError['entry_id']);
        $this->assertEquals($param['payment_gateway_id'], $statusError['payment_gateway_id']);
        $this->assertEquals('180121', $statusError['code']);
    }

    /**
     * 測試檢查已確認入款明細支付狀態訂單已支付(帶入支援批次查詢的支付平台)
     */
    public function testTrackingDepositPayStatusWithPaymentGatewaySupportBatchTracking()
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $emShare = $container->get('doctrine.orm.share_entity_manager');
        $italkingOperator = $container->get('durian.italking_operator');
        $redis = $container->get('snc_redis.default_client');

        $cdeEntry1 = $em->getRepository('BBDurianBundle:CashDepositEntry')
            ->findOneBy(['id' => '201605230000000001']);
        $cdeEntry1->confirm();
        $cdeEntry1->setEntryId(1);

        $cdeEntry2 = $em->getRepository('BBDurianBundle:CashDepositEntry')
            ->findOneBy(['id' => '201605230000000002']);
        $cdeEntry2->confirm();
        $cdeEntry2->setEntryId(2);

        $cdeEntry3 = $em->getRepository('BBDurianBundle:CashDepositEntry')
            ->findOneBy(['id' => '201605230000000003']);
        $cdeEntry3->confirm();
        $cdeEntry3->setEntryId(3);

        $em->flush();

        $mockOperator = $this->getMockBuilder('BB\DurianBundle\Payment\Operator')
            ->disableOriginalConstructor()
            ->setMethods(['batchTracking'])
            ->getMock();

        $results1 = [
            '201605230000000001' => ['result' => 'ok'],
            '201605230000000002' => [
                'result' => 'error',
                'code' => '180035',
                'msg' => 'Payment failure',
            ],
        ];

        $mockOperator->expects($this->at(0))
            ->method('batchTracking')
            ->willReturn($results1);

        $results2 = [
            'result' => 'error',
            'code' => '180140',
            'msg' => 'No verify_url specified',
        ];

        $mockOperator->expects($this->at(1))
            ->method('batchTracking')
            ->willReturn($results2);

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $getMap = [
            ['snc_redis.default_client', 1, $redis],
            ['durian.italking_operator', 1, $italkingOperator],
            ['durian.payment_operator', 1, $mockOperator],
            ['doctrine.orm.entity_manager', 1, $em],
            ['doctrine.orm.share_entity_manager', 1, $emShare],
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));
        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn(['172.26.54.42', '172.26.54.41']);

        $application = new Application();
        $command = new TrackingDepositPayStatusCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $params = [
            'command' => $command->getName(),
            '--payment-gateway-id' => 8,
            '--start' => '20160526000000',
            '--end' => $cdeEntry3->getConfirmAt()->format('YmdHis'),
        ];

        $commandTester = new CommandTester($command);
        $commandTester->execute($params);

        $results = explode(PHP_EOL, $commandTester->getDisplay());

        $expMsg = [
            '支付平台: IPS, 線上入款單號: 201605230000000001 訂單已支付.',
            '支付平台: IPS, 廳: domain2@cm, 線上入款單號: 201605230000000002, 會員帳號: tester, 金額: 100,' .
            ' 查詢結果: 交易失敗.',
            '支付平台: IPS, 廳: domain2@cm, 線上入款單號: 201605230000000003, 會員帳號: tester, 金額: 100,' .
            ' No verify_url specified',
        ];

        $this->assertContains($expMsg[0], $results[0]);
        $this->assertContains($expMsg[1], $results[1]);
        $this->assertContains($expMsg[2], $results[2]);

        // 檢查italking訊息
        $message1 = '支付平台: IPS, 廳: domain2@cm, 線上入款單號: 201605230000000002, 會員帳號: tester, 金額: 100,' .
            ' 查詢結果: 交易失敗.';

        $italking1 = json_decode($redis->rpop('italking_message_queue'), true);

        $this->assertEquals('developer_acc', $italking1['type']);
        $this->assertContains($message1, $italking1['message']);
        $this->assertEquals(1, $italking1['code']);

        $message2 = '支付平台: IPS, 廳: domain2@cm, 線上入款單號: 201605230000000003, 會員帳號: tester, 金額: 100,' .
            ' No verify_url specified';

        $italking2 = json_decode($redis->rpop('italking_message_queue'), true);

        $this->assertEquals('developer_acc', $italking2['type']);
        $this->assertContains($message2, $italking2['message']);
        $this->assertEquals(1, $italking2['code']);
    }

    /**
     * 測試檢查已確認入款明細支付狀態, 帶入支援批次查詢的支付平台, 但支付平台回傳分頁檔大於一頁
     */
    public function testTrackingDepositPayStatusReturnEntriesExceedRestriction()
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $emShare = $container->get('doctrine.orm.share_entity_manager');
        $italkingOperator = $container->get('durian.italking_operator');
        $redis = $container->get('snc_redis.default_client');

        $cdeEntry1 = $em->getRepository('BBDurianBundle:CashDepositEntry')
            ->findOneBy(['id' => '201605230000000001']);
        $cdeEntry1->confirm();
        $cdeEntry1->setEntryId(1);

        $cdeEntry2 = $em->getRepository('BBDurianBundle:CashDepositEntry')
            ->findOneBy(['id' => '201605230000000002']);
        $cdeEntry2->confirm();
        $cdeEntry2->setEntryId(2);

        $cdeEntry3 = $em->getRepository('BBDurianBundle:CashDepositEntry')
            ->findOneBy(['id' => '201605230000000003']);
        $cdeEntry3->confirm();
        $cdeEntry3->setEntryId(3);

        $em->flush();

        $mockOperator = $this->getMockBuilder('BB\DurianBundle\Payment\Operator')
            ->disableOriginalConstructor()
            ->setMethods(['batchTracking'])
            ->getMock();

        $results1 = [
            'result' => 'error',
            'code' => '150180173',
            'msg' => 'The number of return entries exceed the restriction',
        ];

        $mockOperator->expects($this->at(0))
            ->method('batchTracking')
            ->willReturn($results1);

        $results2 = [
            'result' => 'error',
            'code' => '180088',
            'msg' => 'Payment Gateway connection failure',
        ];

        $mockOperator->expects($this->at(1))
            ->method('batchTracking')
            ->willReturn($results2);

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $getMap = [
            ['snc_redis.default_client', 1, $redis],
            ['durian.italking_operator', 1, $italkingOperator],
            ['durian.payment_operator', 1, $mockOperator],
            ['doctrine.orm.entity_manager', 1, $em],
            ['doctrine.orm.share_entity_manager', 1, $emShare]
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));
        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn(['172.26.54.42', '172.26.54.41']);

        $application = new Application();
        $command = new TrackingDepositPayStatusCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $params = [
            'command' => $command->getName(),
            '--payment-gateway-id' => 8,
            '--start' => '20160526000000',
            '--end' => $cdeEntry3->getConfirmAt()->format('YmdHis'),
        ];

        $commandTester = new CommandTester($command);
        $commandTester->execute($params);

        $results = explode(PHP_EOL, $commandTester->getDisplay());

        $expMsg = [
            '支付平台: IPS, 廳: domain2@cm, 線上入款單號: 201605230000000001, 會員帳號: tester, 金額: 100,' .
            ' The number of return entries exceed the restriction',
            '支付平台: IPS, 廳: domain2@cm, 線上入款單號: 201605230000000002, 會員帳號: tester, 金額: 100,' .
            ' The number of return entries exceed the restriction',
            '支付平台: IPS, 廳: domain2@cm, 線上入款單號: 201605230000000003, 會員帳號: tester, 金額: 100,' .
            ' 查詢結果: 支付平台連線失敗. 請通知研五-電子商務工程師上線檢查.',
        ];

        $this->assertContains($expMsg[0], $results[0]);
        $this->assertContains($expMsg[1], $results[1]);
        $this->assertContains($expMsg[2], $results[2]);

        // 檢查查詢異常是否寫入redis
        $entry1 = json_decode($redis->rpop('tracking_deposit_pay_status_queue'), true);
        $this->assertEquals('201605230000000002', $entry1['entry_id']);
        $this->assertEquals('100', $entry1['amount']);
        $this->assertEquals('tester', $entry1['username']);
        $this->assertEquals('domain2@cm', $entry1['domain']);
        $this->assertEquals(0, $entry1['retry']);

        $entry2 = json_decode($redis->rpop('tracking_deposit_pay_status_queue'), true);
        $this->assertEquals('201605230000000001', $entry2['entry_id']);
        $this->assertEquals('100', $entry2['amount']);
        $this->assertEquals('tester', $entry2['username']);
        $this->assertEquals('domain2@cm', $entry2['domain']);
        $this->assertEquals(0, $entry2['retry']);

        $entry3 = json_decode($redis->rpop('tracking_deposit_pay_status_batch_queue'), true);
        $this->assertEquals('201605230000000003', $entry3['entry_id']);
        $this->assertEquals('100', $entry3['amount']);
        $this->assertEquals('tester', $entry3['username']);
        $this->assertEquals('domain2@cm', $entry3['domain']);
        $this->assertEquals(0, $entry3['retry']);

        // 檢查italking訊息
        $message1 = '支付平台: IPS, 廳: domain2@cm, 線上入款單號: 201605230000000001, 會員帳號: tester, 金額: 100,' .
            ' The number of return entries exceed the restriction';

        $italking1 = json_decode($redis->rpop('italking_message_queue'), true);

        $this->assertEquals('developer_acc', $italking1['type']);
        $this->assertContains($message1, $italking1['message']);
        $this->assertEquals(1, $italking1['code']);

        $message2 = '支付平台: IPS, 廳: domain2@cm, 線上入款單號: 201605230000000002, 會員帳號: tester, 金額: 100,' .
            ' The number of return entries exceed the restriction';

        $italking2 = json_decode($redis->rpop('italking_message_queue'), true);

        $this->assertEquals('developer_acc', $italking2['type']);
        $this->assertContains($message2, $italking2['message']);
        $this->assertEquals(1, $italking2['code']);
    }

    /**
     * 測試批次重覆檢查有問題的已確認入款明細支付狀態
     */
    public function testRetryBatchTrackingDepositPayStatus()
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $emShare = $container->get('doctrine.orm.share_entity_manager');
        $italkingOperator = $container->get('durian.italking_operator');
        $redis = $container->get('snc_redis.default_client');

        $param1 = [
            'entry_id' => '201605230000000001',
            'amount' => '100',
            'username' => 'tester',
            'domain' => 'domain2@cm',
            'retry' => 1,
            'url' => 'http://test.com',
            'server_ip' => '127.0.0.1',
            'payment_gateway_id' => 8,
            'payment_gateway_name' => 'IPS',
        ];

        $redis->rpush('tracking_deposit_pay_status_batch_queue', json_encode($param1));

        $param2 = [
            'entry_id' => '201605230000000002',
            'amount' => '100',
            'username' => 'tester',
            'domain' => 'domain2@cm',
            'retry' => 1,
            'url' => 'http://test.com',
            'server_ip' => '127.0.0.1',
            'payment_gateway_id' => 8,
            'payment_gateway_name' => 'IPS',
        ];

        $redis->rpush('tracking_deposit_pay_status_batch_queue', json_encode($param2));

        $param3 = [
            'entry_id' => '201605230000000003',
            'amount' => '100',
            'username' => 'tester',
            'domain' => 'domain2@cm',
            'retry' => 5,
            'url' => 'http://test.com',
            'server_ip' => '127.0.0.1',
            'payment_gateway_id' => 8,
            'payment_gateway_name' => 'IPS',
        ];

        $redis->rpush('tracking_deposit_pay_status_batch_queue', json_encode($param3));

        $mockOperator = $this->getMockBuilder('BB\DurianBundle\Payment\Operator')
            ->disableOriginalConstructor()
            ->setMethods(['batchTracking'])
            ->getMock();

        $results1 = [
            'result' => 'error',
            'code' => '180140',
            'msg' => 'No verify_url specified',
        ];

        $mockOperator->expects($this->at(0))
            ->method('batchTracking')
            ->willReturn($results1);

        $results2 = [
            '201605230000000001' => ['result' => 'ok'],
            '201605230000000002' => [
                'result' => 'error',
                'code' => '180035',
                'msg' => 'Payment failure',
            ],
        ];

        $mockOperator->expects($this->at(1))
            ->method('batchTracking')
            ->willReturn($results2);

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $getMap = [
            ['snc_redis.default_client', 1, $redis],
            ['durian.italking_operator', 1, $italkingOperator],
            ['durian.payment_operator', 1, $mockOperator],
            ['doctrine.orm.entity_manager', 1, $em],
            ['doctrine.orm.share_entity_manager', 1, $emShare]
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        $application = new Application();
        $command = new TrackingDepositPayStatusCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $params = [
            'command' => $command->getName(),
            '--batch-retry' => true,
        ];

        $commandTester = new CommandTester($command);
        $commandTester->execute($params);

        $results = explode(PHP_EOL, $commandTester->getDisplay());

        $msg1 = '支付平台: IPS, 廳: domain2@cm, 線上入款單號: 201605230000000003, 會員帳號: tester, 金額: ' .
            '100, No verify_url specified';
        $msg2 = '支付平台: IPS, 線上入款單號: 201605230000000001 訂單已支付.';

        $this->assertContains($msg1, $results[0]);
        $this->assertContains($msg2, $results[1]);

        // 檢查查詢異常是否寫入redis
        $entries = json_decode($redis->rpop('tracking_deposit_pay_status_batch_queue'), true);

        $this->assertEquals('201605230000000002', $entries['entry_id']);
        $this->assertEquals('100', $entries['amount']);
        $this->assertEquals('tester', $entries['username']);
        $this->assertEquals('domain2@cm', $entries['domain']);
        $this->assertEquals(2, $entries['retry']);
        $this->assertEquals('http://test.com', $entries['url']);
        $this->assertEquals('127.0.0.1', $entries['server_ip']);
        $this->assertEquals('IPS', $entries['payment_gateway_name']);

        // 檢查查詢異常超過5次, 是否寫入redis italking訊息
        $message = '支付平台: IPS, 廳: domain2@cm, 線上入款單號: 201605230000000003, 會員帳號: tester, 金額: ' .
            '100, No verify_url specified';

        // 檢查italking訊息
        $italking = json_decode($redis->rpop('italking_message_queue'), true);

        $this->assertEquals('acc_system', $italking['type']);
        $this->assertContains($message, $italking['message']);
        $this->assertEquals(1, $italking['code']);
    }

    /**
     * 測試批次重覆檢查有問題的已確認入款明細支付狀態為特殊異常狀態
     */
    public function testRetryBatchTrackingDepositPayStatusError()
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $emShare = $container->get('doctrine.orm.share_entity_manager');
        $italkingOperator = $container->get('durian.italking_operator');
        $redis = $container->get('snc_redis.default_client');

        $param1 = [
            'entry_id' => '201605230000000001',
            'amount' => '100',
            'username' => 'tester',
            'domain' => 'domain2@cm',
            'retry' => 5,
            'url' => 'http://test.com',
            'server_ip' => '127.0.0.1',
            'payment_gateway_id' => 8,
            'payment_gateway_name' => 'IPS',
        ];

        $redis->rpush('tracking_deposit_pay_status_batch_queue', json_encode($param1));

        $param2 = [
            'entry_id' => '201605230000000002',
            'amount' => '100',
            'username' => 'tester',
            'domain' => 'domain2@cm',
            'retry' => 5,
            'url' => 'http://test.com',
            'server_ip' => '127.0.0.1',
            'payment_gateway_id' => 8,
            'payment_gateway_name' => 'IPS',
        ];

        $redis->rpush('tracking_deposit_pay_status_batch_queue', json_encode($param2));

        $param3 = [
            'entry_id' => '201605230000000003',
            'amount' => '100',
            'username' => 'tester',
            'domain' => 'domain2@cm',
            'retry' => 5,
            'url' => 'http://test.com',
            'server_ip' => '127.0.0.1',
            'payment_gateway_id' => 8,
            'payment_gateway_name' => 'IPS',
        ];

        $redis->rpush('tracking_deposit_pay_status_batch_queue', json_encode($param3));

        $mockOperator = $this->getMockBuilder('BB\DurianBundle\Payment\Operator')
            ->disableOriginalConstructor()
            ->setMethods(['batchTracking'])
            ->getMock();

        $results1 = [
            'result' => 'error',
            'code' => '180127',
            'msg' => 'PaymentGateway error, Merchant sign error',
        ];

        $mockOperator->expects($this->at(0))
            ->method('batchTracking')
            ->willReturn($results1);

        $results2 = [
            'result' => 'error',
            'code' => '180088',
            'msg' => 'Payment Gateway connection failure',
        ];

        $mockOperator->expects($this->at(1))
            ->method('batchTracking')
            ->willReturn($results2);

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $getMap = [
            ['snc_redis.default_client', 1, $redis],
            ['durian.italking_operator', 1, $italkingOperator],
            ['durian.payment_operator', 1, $mockOperator],
            ['doctrine.orm.entity_manager', 1, $em],
            ['doctrine.orm.share_entity_manager', 1, $emShare]
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        $application = new Application();
        $command = new TrackingDepositPayStatusCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $params = [
            'command' => $command->getName(),
            '--batch-retry' => true,
        ];

        $commandTester = new CommandTester($command);
        $commandTester->execute($params);

        $results = explode(PHP_EOL, $commandTester->getDisplay());

        $msg1 = '支付平台: IPS, 廳: domain2@cm, 線上入款單號: 201605230000000003, 會員帳號: tester, 金額: ' .
            '100, 查詢結果: 商戶簽名錯誤.';

        $this->assertContains($msg1, $results[0]);

        $msg2 = '支付平台: IPS, 支付平台連線失敗. 請DC-OP-維護組協助檢查連線是否異常，若DC-OP-維護組調整後，警報器仍' .
            '持續跳出訊息，請通知RD5-電子商務部上線檢查，測試語法如下：';

        $this->assertContains($msg2, $results[1]);
        $this->assertEquals('Server: 127.0.0.1', $results[2]);
        $this->assertEquals("time curl 'http://test.com'", $results[3]);

        $msg3 = '支付平台: IPS, 支付平台連線失敗. 請DC-OP-維護組協助檢查連線是否異常，若DC-OP-維護組調整後，警報器仍' .
            '持續跳出訊息，請通知RD5-電子商務部上線檢查，測試語法如下：';

        $this->assertContains($msg3, $results[4]);
        $this->assertEquals('Server: 127.0.0.1', $results[5]);
        $this->assertEquals("time curl 'http://test.com'", $results[6]);

        $message1 = sprintf(
            "支付平台: IPS, 廳: domain2@cm, 線上入款單號: 201605230000000003, 會員帳號: tester, 金額: " .
            "100, 查詢結果: 商戶簽名錯誤.(180127)"
        );

        // 檢查第一筆italking訊息
        $italking1 = json_decode($redis->rpop('italking_message_queue'), true);

        $this->assertEquals('developer_acc', $italking1['type']);
        $this->assertContains($message1, $italking1['message']);
        $this->assertEquals(1, $italking1['code']);

        // 檢查redis是否記錄異常入款
        $statusError = json_decode($redis->rpop('deposit_pay_status_error_queue'), true);

        $this->assertEquals($param3['entry_id'], $statusError['entry_id']);
        $this->assertEquals($param3['payment_gateway_id'], $statusError['payment_gateway_id']);
        $this->assertEquals('180127', $statusError['code']);

        $message2 = sprintf(
            "支付平台: IPS, 支付平台連線失敗. 請DC-OP-維護組協助檢查連線是否異常，若DC-OP-維護組調整後，警報器仍" .
            "持續跳出訊息，請通知RD5-電子商務部上線檢查，測試語法如下：\n" .
            "Server: 127.0.0.1\n" .
            "time curl 'http://test.com'"
        );

        // 檢查第二筆italking訊息
        $italking2 = json_decode($redis->rpop('italking_message_queue'), true);

        $this->assertEquals('acc_system', $italking2['type']);
        $this->assertContains($message2, $italking2['message']);
        $this->assertEquals(1, $italking2['code']);

        $message3 = sprintf(
            "支付平台: IPS, 支付平台連線失敗. 請DC-OP-維護組協助檢查連線是否異常，若DC-OP-維護組調整後，警報器仍" .
            "持續跳出訊息，請通知RD5-電子商務部上線檢查，測試語法如下：\n" .
            "Server: 127.0.0.1\n" .
            "time curl 'http://test.com'"
        );

        // 檢查第三筆italking訊息
        $italking3 = json_decode($redis->rpop('italking_message_queue'), true);

        $this->assertEquals('acc_system', $italking3['type']);
        $this->assertContains($message3, $italking3['message']);
        $this->assertEquals(1, $italking3['code']);

        // 檢查異常入款italking訊息
        $italking4 = json_decode($redis->rpop('italking_message_queue'), true);

        $gmMsg = "查詢結果: 商戶簽名錯誤.請依照下列流程處理：\n\n請客服至GM管理系統-系統客服管理-異常入款批次停權-現金異常入款-錯誤情況選" .
            "擇：「異常入款」，將會員帳號停權並寄發廳主訊息\n\n若業主收到廳主訊息後，反映未更改密鑰，請通知研五-電子商務工程師上線檢查。";

        $this->assertEquals('acc_system', $italking4['type']);
        $this->assertContains($gmMsg, $italking4['message']);
        $this->assertEquals(1, $italking4['code']);
    }

    /**
     * 測試批次重覆檢查有問題的已確認入款明細支付狀態, 但支付平台回傳分頁檔大於一頁
     */
    public function testRetryBatchTrackingDepositPayStatusReturnEntriesExceedRestriction()
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $emShare = $container->get('doctrine.orm.share_entity_manager');
        $italkingOperator = $container->get('durian.italking_operator');
        $redis = $container->get('snc_redis.default_client');

        $param1 = [
            'entry_id' => '201605230000000001',
            'amount' => '100',
            'username' => 'tester',
            'domain' => 'domain2@cm',
            'retry' => 1,
            'url' => 'http://test.com',
            'server_ip' => '127.0.0.1',
            'payment_gateway_id' => 8,
            'payment_gateway_name' => 'IPS',
        ];

        $redis->rpush('tracking_deposit_pay_status_batch_queue', json_encode($param1));

        $param2 = [
            'entry_id' => '201605230000000002',
            'amount' => '100',
            'username' => 'tester',
            'domain' => 'domain2@cm',
            'retry' => 1,
            'url' => 'http://test.com',
            'server_ip' => '127.0.0.1',
            'payment_gateway_id' => 8,
            'payment_gateway_name' => 'IPS',
        ];

        $redis->rpush('tracking_deposit_pay_status_batch_queue', json_encode($param2));

        $param3 = [
            'entry_id' => '201605230000000003',
            'amount' => '100',
            'username' => 'tester',
            'domain' => 'domain2@cm',
            'retry' => 5,
            'url' => 'http://test.com',
            'server_ip' => '127.0.0.1',
            'payment_gateway_id' => 8,
            'payment_gateway_name' => 'IPS',
        ];

        $redis->rpush('tracking_deposit_pay_status_batch_queue', json_encode($param3));

        $mockOperator = $this->getMockBuilder('BB\DurianBundle\Payment\Operator')
            ->disableOriginalConstructor()
            ->setMethods(['batchTracking'])
            ->getMock();

        $results1 = [
            'result' => 'error',
            'code' => '150180173',
            'msg' => 'The number of return entries exceed the restriction',
        ];

        $mockOperator->expects($this->at(0))
            ->method('batchTracking')
            ->willReturn($results1);

        $results2 = [
            'result' => 'error',
            'code' => '180088',
            'msg' => 'Payment Gateway connection failure',
        ];

        $mockOperator->expects($this->at(1))
            ->method('batchTracking')
            ->willReturn($results2);

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $getMap = [
            ['snc_redis.default_client', 1, $redis],
            ['durian.italking_operator', 1, $italkingOperator],
            ['durian.payment_operator', 1, $mockOperator],
            ['doctrine.orm.entity_manager', 1, $em],
            ['doctrine.orm.share_entity_manager', 1, $emShare]
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        $application = new Application();
        $command = new TrackingDepositPayStatusCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $params = [
            'command' => $command->getName(),
            '--batch-retry' => true,
        ];

        $commandTester = new CommandTester($command);
        $commandTester->execute($params);

        $results = explode(PHP_EOL, $commandTester->getDisplay());

        $this->assertContains('', $results);

        // 檢查查詢異常是否寫入redis
        $entry1 = json_decode($redis->rpop('tracking_deposit_pay_status_queue'), true);
        $this->assertEquals('201605230000000003', $entry1['entry_id']);
        $this->assertEquals('100', $entry1['amount']);
        $this->assertEquals('tester', $entry1['username']);
        $this->assertEquals('domain2@cm', $entry1['domain']);
        $this->assertEquals(6, $entry1['retry']);

        $entry2 = json_decode($redis->rpop('tracking_deposit_pay_status_batch_queue'), true);
        $this->assertEquals('201605230000000001', $entry2['entry_id']);
        $this->assertEquals('100', $entry2['amount']);
        $this->assertEquals('tester', $entry2['username']);
        $this->assertEquals('domain2@cm', $entry2['domain']);
        $this->assertEquals(2, $entry2['retry']);

        $entry3 = json_decode($redis->rpop('tracking_deposit_pay_status_batch_queue'), true);
        $this->assertEquals('201605230000000002', $entry3['entry_id']);
        $this->assertEquals('100', $entry3['amount']);
        $this->assertEquals('tester', $entry3['username']);
        $this->assertEquals('domain2@cm', $entry3['domain']);
        $this->assertEquals(2, $entry3['retry']);
    }

    /**
     * 測試重覆檢查有問題的已確認入款明細支付狀態返回未知例外
     */
    public function testRetryTrackingDepositPayStatusOverFiveTimesWithUnknownException()
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $emShare = $container->get('doctrine.orm.share_entity_manager');
        $italkingOperator = $container->get('durian.italking_operator');
        $redis = $container->get('snc_redis.default_client');

        $param = [
            'entry_id' => '201304280000000001',
            'amount' => '100',
            'username' => 'tester',
            'domain' => 'company',
            'retry' => 5,
            'url' => 'http://test.com',
            'server_ip' => '127.0.0.1',
            'payment_gateway_id' => 1,
            'payment_gateway_name' => 'BBPay',
        ];

        $redis->rpush('tracking_deposit_pay_status_queue', json_encode($param));

        $exception = new \Exception('Some error message', 180123);

        $mockOperator = $this->getMockBuilder('BB\DurianBundle\Payment')
            ->disableOriginalConstructor()
            ->setMethods(['paymentTracking'])
            ->getMock();
        $mockOperator->expects($this->any())
            ->method('paymentTracking')
            ->will($this->throwException($exception));

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $getMap = [
            ['snc_redis.default_client', 1, $redis],
            ['durian.italking_operator', 1, $italkingOperator],
            ['durian.payment_operator', 1, $mockOperator],
            ['doctrine.orm.entity_manager', 1, $em],
            ['doctrine.orm.share_entity_manager', 1, $emShare],
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        $application = new Application();
        $command = new TrackingDepositPayStatusCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $params = [
            'command' => $command->getName(),
            '--retry' => true,
        ];

        $commandTester = new CommandTester($command);
        $commandTester->execute($params);

        $results = explode(PHP_EOL, $commandTester->getDisplay());

        $msg = '支付平台: BBPay, 廳: company, 線上入款單號: 201304280000000001, 會員帳號: tester, 金額: ' .
            '100, Some error message. 請通知研五-電子商務工程師上線檢查.(180123)';

        $this->assertContains($msg, $results[0]);

        // 檢查italking訊息
        $italking = json_decode($redis->rpop('italking_message_queue'), true);

        $this->assertEquals('acc_system', $italking['type']);
        $this->assertContains($msg, $italking['message']);
        $this->assertEquals(1, $italking['code']);
    }

    /**
     * 測試批次重覆檢查有問題的已確認入款明細支付狀態回傳未知例外
     */
    public function testRetryBatchTrackingDepositPayStatusWithUnknownException()
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $emShare = $container->get('doctrine.orm.share_entity_manager');
        $italkingOperator = $container->get('durian.italking_operator');
        $redis = $container->get('snc_redis.default_client');

        $param1 = [
            'entry_id' => '201605230000000001',
            'amount' => '100',
            'username' => 'tester',
            'domain' => 'company',
            'retry' => 1,
            'url' => 'http://test.com',
            'server_ip' => '127.0.0.1',
            'payment_gateway_id' => 8,
            'payment_gateway_name' => 'IPS',
        ];

        $redis->rpush('tracking_deposit_pay_status_batch_queue', json_encode($param1));

        $param2 = [
            'entry_id' => '201605230000000002',
            'amount' => '100',
            'username' => 'tester',
            'domain' => 'company',
            'retry' => 1,
            'url' => 'http://test.com',
            'server_ip' => '127.0.0.1',
            'payment_gateway_id' => 8,
            'payment_gateway_name' => 'IPS',
        ];

        $redis->rpush('tracking_deposit_pay_status_batch_queue', json_encode($param2));

        $param3 = [
            'entry_id' => '201605230000000003',
            'amount' => '100',
            'username' => 'tester',
            'domain' => 'company',
            'retry' => 5,
            'url' => 'http://test.com',
            'server_ip' => '127.0.0.1',
            'payment_gateway_id' => 8,
            'payment_gateway_name' => 'IPS',
        ];

        $redis->rpush('tracking_deposit_pay_status_batch_queue', json_encode($param3));

        $mockOperator = $this->getMockBuilder('BB\DurianBundle\Payment\Operator')
            ->disableOriginalConstructor()
            ->setMethods(['batchTracking'])
            ->getMock();

        $results1 = [
            'result' => 'error',
            'code' => '180123',
            'msg' => 'Some error message',
        ];

        $mockOperator->expects($this->at(0))
            ->method('batchTracking')
            ->willReturn($results1);

        $results2 = [
            '201605230000000001' => ['result' => 'ok'],
            '201605230000000002' => [
                'result' => 'error',
                'code' => '180035',
                'msg' => 'Payment failure',
            ],
        ];

        $mockOperator->expects($this->at(1))
            ->method('batchTracking')
            ->willReturn($results2);

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $getMap = [
            ['snc_redis.default_client', 1, $redis],
            ['durian.italking_operator', 1, $italkingOperator],
            ['durian.payment_operator', 1, $mockOperator],
            ['doctrine.orm.entity_manager', 1, $em],
            ['doctrine.orm.share_entity_manager', 1, $emShare]
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        $application = new Application();
        $command = new TrackingDepositPayStatusCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $params = [
            'command' => $command->getName(),
            '--batch-retry' => true,
        ];

        $commandTester = new CommandTester($command);
        $commandTester->execute($params);

        $results = explode(PHP_EOL, $commandTester->getDisplay());

        $msg1 = '支付平台: IPS, 廳: company, 線上入款單號: 201605230000000003, 會員帳號: tester, 金額: ' .
            '100, Some error message. 請通知研五-電子商務工程師上線檢查.(180123)';
        $msg2 = '支付平台: IPS, 線上入款單號: 201605230000000001 訂單已支付.';

        $this->assertContains($msg1, $results[0]);
        $this->assertContains($msg2, $results[1]);

        // 檢查查詢異常是否寫入redis
        $entries = json_decode($redis->rpop('tracking_deposit_pay_status_batch_queue'), true);

        $this->assertEquals('201605230000000002', $entries['entry_id']);
        $this->assertEquals('100', $entries['amount']);
        $this->assertEquals('tester', $entries['username']);
        $this->assertEquals('company', $entries['domain']);
        $this->assertEquals(2, $entries['retry']);
        $this->assertEquals('http://test.com', $entries['url']);
        $this->assertEquals('127.0.0.1', $entries['server_ip']);
        $this->assertEquals('IPS', $entries['payment_gateway_name']);

        // 檢查查詢異常超過5次, 是否寫入redis italking訊息
        $message = '支付平台: IPS, 廳: company, 線上入款單號: 201605230000000003, 會員帳號: tester, 金額: ' .
            '100, Some error message. 請通知研五-電子商務工程師上線檢查.(180123)';

        // 檢查italking訊息
        $italking = json_decode($redis->rpop('italking_message_queue'), true);

        $this->assertEquals('acc_system', $italking['type']);
        $this->assertContains($message, $italking['message']);
        $this->assertEquals(1, $italking['code']);
    }
}
