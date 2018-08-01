<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Command\DepositTrackingCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use BB\DurianBundle\Entity\Merchant;
use BB\DurianBundle\Entity\CashDepositEntry;
use BB\DurianBundle\Entity\DepositTracking;

class DepositTrackingCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDepositTrackingData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashDepositEntryData'
        ];

        $this->loadFixtures($classnames);

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

        $paymentGateway8 = $em->getRepository('BBDurianBundle:PaymentGateway')->find('8');

        $merchant9 = new Merchant($paymentGateway8, CashDepositEntry::PAYWAY_CASH, 'IPS', '1234567890', 1, 156);
        $merchant9->setId(8);
        $em->persist($merchant9);

        $merchant10 = new Merchant($paymentGateway8, CashDepositEntry::PAYWAY_CASH, 'IPS', '123456789', 1, 156);
        $merchant10->setId(9);
        $em->persist($merchant10);

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

        $entry4 = new CashDepositEntry($cash, $merchant9, $paymentVendor, $data);
        $entry4->setId(201605230000000001);
        $entry4->setAt('20160523000000');
        $em->persist($entry4);

        $entry5 = new CashDepositEntry($cash, $merchant9, $paymentVendor, $data);
        $entry5->setId(201605230000000002);
        $entry5->setAt('20160523000000');
        $em->persist($entry5);

        $entry6 = new CashDepositEntry($cash, $merchant10, $paymentVendor, $data);
        $entry6->setId(201605230000000003);
        $entry6->setAt('20160523000000');
        $em->persist($entry6);

        $depositTrackingEntry4 = new DepositTracking(201605230000000001, 8, 8);
        $em->persist($depositTrackingEntry4);

        $depositTrackingEntry5 = new DepositTracking(201605230000000002, 8, 8);
        $depositTrackingEntry5->addRetry();
        $depositTrackingEntry5->addRetry();
        $em->persist($depositTrackingEntry5);

        $depositTrackingEntry6 = new DepositTracking(201605230000000003, 8, 9);
        $em->persist($depositTrackingEntry6);

        $em->flush();
    }

    /**
     * 回傳支援訂單查詢的支付平台id
     */
    public function testPaymentTrackingWithShow()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 1);
        $paymentGateway->setAutoReop(true);
        $paymentGateway->setLabel('CBPay');

        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 2);
        $paymentGateway->setAutoReop(true);
        $paymentGateway->setLabel('LLPay');
        $em->flush();

        $params = ['--show-id' => true];
        $output = $this->runCommand('durian:deposit-tracking', $params);
        $results = explode(PHP_EOL, $output);

        $expMsg = [
            '1',
            '2'
        ];

        $this->assertEquals($expMsg[0], $results[0]);
        $this->assertEquals($expMsg[1], $results[1]);
    }

    /**
     * 測試訂單查詢
     */
    public function testPaymentTracking()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $dtRepo = $em->getRepository('BBDurianBundle:DepositTracking');

        $cdeEntry = $em->getRepository('BBDurianBundle:CashDepositEntry')
            ->findOneBy(['id' => '201304280000000001']);
        $cdeEntry->confirm();

        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 1);
        $paymentGateway->setAutoReop(true);
        $paymentGateway->setLabel('CBPay');

        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 2);
        $paymentGateway->setAutoReop(true);
        $paymentGateway->setLabel('LLPay');
        $em->flush();

        $output = $this->runCommand('durian:deposit-tracking');
        $results = explode(PHP_EOL, $output);

        $expMsg = [
            'DepositTrackingCommand Start.',
            'Id:201305280000000001 Error:180140, Message:No verify_url specified',
            'Id:201605230000000001 Error:180142, Message:No privateKey specified',
            'Id:201605230000000002 Error:180142, Message:No privateKey specified',
            'Id:201605230000000003 Error:180142, Message:No privateKey specified',
            'DepositTrackingCommand finish.'
        ];

        $this->assertEquals($expMsg[0], $results[0]);
        $this->assertEquals($expMsg[1], $results[1]);
        $this->assertEquals($expMsg[2], $results[2]);
        $this->assertEquals($expMsg[3], $results[3]);
        $this->assertEquals($expMsg[4], $results[4]);
        $this->assertEquals($expMsg[5], $results[5]);

        // 檢查retry次數達到三次後需查詢資料被刪除
        $entry = $dtRepo->findOneBy(['entryId' => '201305280000000001']);
        $this->assertNull($entry);

        // 檢查已確認的需查詢資料被刪除
        $entry = $dtRepo->findOneBy(['entryId' => '201304280000000001']);
        $this->assertNull($entry);
    }

    /**
     * 測試訂單查詢帶入支付平台Id
     */
    public function testPaymentTrackingWithPaymentGatewayId()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 1);
        $paymentGateway->setAutoReop(true);
        $paymentGateway->setLabel('CBPay');

        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 2);
        $paymentGateway->setAutoReop(true);
        $paymentGateway->setLabel('LLPay');
        $em->flush();

        $params = ['--payment-gateway-id' => 2];
        $output = $this->runCommand('durian:deposit-tracking', $params);
        $results = explode(PHP_EOL, $output);

        $expMsg = [
            'DepositTrackingCommand Start.',
            'Id:201304280000000001 Error:180140, Message:No verify_url specified',
            'DepositTrackingCommand finish.'
        ];

        $this->assertEquals($expMsg[0], $results[0]);
        $this->assertEquals($expMsg[1], $results[1]);
        $this->assertEquals($expMsg[2], $results[2]);
    }

    /**
     * 測試訂單查詢成功
     */
    public function testPaymentTrackingSuccess()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $mockOperator = $this->getMockBuilder('\BB\DurianBundle\Payment\Operator')
            ->disableOriginalConstructor()
            ->setMethods(['paymentTracking', 'depositConfirm', 'supportBatchTracking', 'batchTracking'])
            ->getMock();

        $results1 = [
            '201605230000000001' => ['result' => 'ok'],
            '201605230000000002' => [
                'result' => 'error',
                'code' => '180035',
                'msg' => 'Payment failure',
            ],
        ];

        $mockOperator->expects($this->at(4))
            ->method('batchTracking')
            ->willReturn($results1);

        $results2 = [
            'result' => 'error',
            'code' => '180140',
            'msg' => 'No verify_url specified',
        ];

        $mockOperator->expects($this->at(6))
            ->method('batchTracking')
            ->willReturn($results2);

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();
        $getMap = [
            ['doctrine.orm.entity_manager', 1, $em],
            ['durian.payment_operator', 1, $mockOperator]
        ];
        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        $application = new Application();
        $command = new DepositTrackingCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $results = explode(PHP_EOL, $commandTester->getDisplay());

        $expMsg = [
            'DepositTrackingCommand Start.',
            'Id:201605230000000002 Error:180035, Message:Payment failure',
            'Id:201605230000000003 Error:180140, Message:No verify_url specified',
            'DepositTrackingCommand finish.'
        ];

        $this->assertEquals($expMsg[0], $results[0]);
        $this->assertEquals($expMsg[1], $results[1]);
        $this->assertEquals($expMsg[2], $results[2]);
        $this->assertEquals($expMsg[3], $results[3]);
    }
}

