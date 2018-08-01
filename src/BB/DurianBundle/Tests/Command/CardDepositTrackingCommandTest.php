<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Command\CardDepositTrackingCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use BB\DurianBundle\Entity\PaymentGateway;
use BB\DurianBundle\Entity\MerchantCard;
use BB\DurianBundle\Entity\CardDepositEntry;
use BB\DurianBundle\Entity\CardDepositTracking;

class CardDepositTrackingCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCardDepositTrackingData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCardDepositEntryData'
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
            0, // andom_float
        ];

        $em->getConnection()->executeUpdate($sql, $params);

        $paymentGateway8 = $em->getRepository('BBDurianBundle:PaymentGateway')->find('8');

        $merchantCard7 = new MerchantCard($paymentGateway8, 'IPS', '1234567890', 1, 156);
        $merchantCard7->setId(7);
        $em->persist($merchantCard7);

        $merchantCard8 = new MerchantCard($paymentGateway8, 'IPS', '123456789', 1, 156);
        $merchantCard8->setId(8);
        $em->persist($merchantCard8);

        $card = $em->find('BBDurianBundle:Card', 1);
        $paymentVendor = $em->find('BBDurianBundle:PaymentVendor', 1);

        $data = [
            'amount' => 100,
            'fee' => -5,
            'web_shop' => false,
            'currency' => 156,
            'rate' => 1,
            'payway_currency' => 156,
            'payway_rate' => 0.05,
            'postcode' => 123,
            'telephone' => '8825252',
            'address' => '地球',
            'email' => 'earth@gmail.com',
            'feeConvBasic' => -5,
            'amountConvBasic' => 100,
            'feeConv' => -5,
            'amountConv' => 100
        ];

        $entry5 = new CardDepositEntry($card, $merchantCard7, $paymentVendor, $data);
        $entry5->setId(201605230000000001);
        $entry5->setAt('20160523000000');
        $em->persist($entry5);

        $entry6 = new CardDepositEntry($card, $merchantCard7, $paymentVendor, $data);
        $entry6->setId(201605230000000002);
        $entry6->setAt('20160523000000');
        $em->persist($entry6);

        $entry7 = new CardDepositEntry($card, $merchantCard8, $paymentVendor, $data);
        $entry7->setId(201605230000000003);
        $entry7->setAt('20160523000000');
        $em->persist($entry7);

        $depositTrackingEntry4 = new CardDepositTracking(201605230000000001, 8, 7);
        $em->persist($depositTrackingEntry4);

        $depositTrackingEntry5 = new CardDepositTracking(201605230000000002, 8, 7);
        $depositTrackingEntry5->addRetry();
        $depositTrackingEntry5->addRetry();
        $em->persist($depositTrackingEntry5);

        $depositTrackingEntry6 = new CardDepositTracking(201605230000000003, 8, 8);
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
        $output = $this->runCommand('durian:card-deposit-tracking', $params);
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
        $cdtRepo = $em->getRepository('BBDurianBundle:CardDepositTracking');

        $cdeEntry = $em->getRepository('BBDurianBundle:CardDepositEntry')
            ->findOneBy(['id' => '201502010000000001']);
        $cdeEntry->confirm();

        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 1);
        $paymentGateway->setAutoReop(true);
        $paymentGateway->setLabel('CBPay');

        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 2);
        $paymentGateway->setAutoReop(true);
        $paymentGateway->setLabel('LLPay');
        $em->flush();

        $output = $this->runCommand('durian:card-deposit-tracking');
        $results = explode(PHP_EOL, $output);

        $expMsg = [
            'CardDepositTrackingCommand Start.',
            'Id:201501080000000001 Error:150720024, Message:PaymentGateway does not support order tracking',
            'Id:201502010000000002 Error:180142, Message:No privateKey specified',
            'Id:201605230000000001 Error:180142, Message:No privateKey specified',
            'Id:201605230000000002 Error:180142, Message:No privateKey specified',
            'Id:201605230000000003 Error:180142, Message:No privateKey specified',
            'CardDepositTrackingCommand finish.'
        ];

        $this->assertEquals($expMsg[0], $results[0]);
        $this->assertEquals($expMsg[1], $results[1]);
        $this->assertEquals($expMsg[2], $results[2]);
        $this->assertEquals($expMsg[3], $results[3]);
        $this->assertEquals($expMsg[4], $results[4]);
        $this->assertEquals($expMsg[5], $results[5]);
        $this->assertEquals($expMsg[6], $results[6]);

        // 檢查retry次數達到三次後需查詢資料被刪除
        $entry = $cdtRepo->findOneBy(['entryId' => '201501080000000001']);
        $this->assertNull($entry);

        // 檢查已確認的需查詢資料被刪除
        $entry = $cdtRepo->findOneBy(['entryId' => '201502010000000001']);
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
        $output = $this->runCommand('durian:card-deposit-tracking', $params);
        $results = explode(PHP_EOL, $output);

        $expMsg = [
            'CardDepositTrackingCommand Start.',
            'Id:201502010000000002 Error:180142, Message:No privateKey specified',
            'CardDepositTrackingCommand finish.'
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

        $mockPaymentOperator = $this->getMockBuilder('\BB\DurianBundle\Payment\Operator')
            ->disableOriginalConstructor()
            ->setMethods(['cardTracking', 'supportBatchTracking', 'cardBatchTracking'])
            ->getMock();

        $results1 = [
            '201605230000000001' => ['result' => 'ok'],
            '201605230000000002' => [
                'result' => 'error',
                'code' => '180035',
                'msg' => 'Payment failure',
            ],
        ];

        $mockPaymentOperator->expects($this->at(3))
            ->method('cardBatchTracking')
            ->willReturn($results1);

        $results2 = [
            'result' => 'error',
            'code' => '180140',
            'msg' => 'No verify_url specified',
        ];

        $mockPaymentOperator->expects($this->at(4))
            ->method('cardBatchTracking')
            ->willReturn($results2);

        $mockDepositOperator = $this->getMockBuilder('\BB\DurianBundle\Deposit')
            ->disableOriginalConstructor()
            ->setMethods(['cardDepositConfirm'])
            ->getMock();

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();
        $getMap = [
            ['doctrine.orm.entity_manager', 1, $em],
            ['durian.payment_operator', 1, $mockPaymentOperator],
            ['durian.deposit_operator', 1, $mockDepositOperator]
        ];
        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        $application = new Application();
        $command = new CardDepositTrackingCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $results = explode(PHP_EOL, $commandTester->getDisplay());

        $expMsg = [
            'CardDepositTrackingCommand Start.',
            'Id:201605230000000002 Error:180035, Message:Payment failure',
            'Id:201605230000000003 Error:180140, Message:No verify_url specified',
            'CardDepositTrackingCommand finish.'
        ];

        $this->assertEquals($expMsg[0], $results[0]);
        $this->assertEquals($expMsg[1], $results[1]);
        $this->assertEquals($expMsg[2], $results[2]);
        $this->assertEquals($expMsg[3], $results[3]);
    }
}

