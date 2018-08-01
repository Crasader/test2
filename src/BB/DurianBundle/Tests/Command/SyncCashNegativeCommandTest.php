<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Command\SyncCashNegativeCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class SyncCashNegativeCommandTest extends WebTestCase
{
    /**
     * 預先設定
     */
    public function setUp()
    {
        parent::setUp();

        $classNames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashNegativeData'
        ];
        $this->loadFixtures($classNames);
        $this->loadFixtures([], 'entry');

        $redis = $this->getContainer()->get('snc_redis.sequence');
        $redis->set('cash_seq', 1000);
    }

    /**
     * 測試原正數轉為負數
     */
    public function testPositiveToNegativeBalance()
    {
        $params = [
            'opcode' => 1019,
            'amount' => -1100,
            'ref_id' => 5678,
            'memo' => 'test-neg'
        ];
        $this->getResponse('PUT', '/api/user/4/cash/op', $params);

        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:sync-cash-negative');

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $neg = $em->find('BBDurianBundle:CashNegative', ['userId' => 4, 'currency' => 901]);

        $this->assertEquals($neg->getCashId(), 3);
        $this->assertEquals($neg->getBalance(), -100);
        $this->assertEquals($neg->getVersion(), 3);
        $this->assertEquals($neg->getEntryId(), 1001);
        $this->assertNotNull($neg->getAt());
        $this->assertEquals($neg->getAmount(), -1100);
        $this->assertEquals($neg->getEntryBalance(), -100);
        $this->assertEquals($neg->getOpcode(), 1019);
        $this->assertEquals($neg->getRefId(), 5678);
        $this->assertEquals($neg->getMemo(), 'test-neg');
        $this->assertTrue($neg->isNegative());
    }

    /**
     * 測試原負數轉為正數
     */
    public function testNegativeToPositiveBalance()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $cash = $em->find('BBDurianBundle:Cash', 1);
        $cash->setBalance(-1);
        $em->flush();

        $params = [
            'opcode' => 1010,
            'amount' => 100,
            'ref_id' => 8765,
            'memo' => 'test-pos'
        ];
        $this->getResponse('PUT', '/api/user/2/cash/op', $params);

        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:sync-cash-negative');

        $neg = $em->find('BBDurianBundle:CashNegative', ['userId' => 2, 'currency' => 901]);

        $this->assertEquals($neg->getCashId(), 1);
        $this->assertEquals($neg->getBalance(), 99);
        $this->assertEquals($neg->getVersion(), 4);
        $this->assertEquals($neg->getEntryId(), 1001);
        $this->assertNotNull($neg->getAt());
        $this->assertEquals($neg->getAmount(), 100);
        $this->assertEquals($neg->getEntryBalance(), 99);
        $this->assertEquals($neg->getOpcode(), 1010);
        $this->assertEquals($neg->getRefId(), 8765);
        $this->assertEquals($neg->getMemo(), 'test-pos');
        $this->assertFalse($neg->isNegative());
    }

    /**
     * 測試正負狀態轉換多次，額度是最後一次，但明細是第一筆導致為負數
     */
    public function testMultipleStateChanged()
    {
        $params = [
            'opcode' => 1019,
            'amount' => -10,
            'ref_id' => 5678,
            'memo' => 'test-neg'
        ];
        $this->getResponse('PUT', '/api/user/4/cash/op', $params);

        $params = [
            'opcode' => 1010,
            'amount' => 100,
            'ref_id' => 5679,
            'memo' => 'test-pos'
        ];
        $this->getResponse('PUT', '/api/user/4/cash/op', $params);

        // 第一次負數
        $params = [
            'opcode' => 1019,
            'amount' => -1100,
            'ref_id' => 5680,
            'memo' => 'test-neg-2'
        ];
        $this->getResponse('PUT', '/api/user/4/cash/op', $params);

        // 第二次負數
        $params = [
            'opcode' => 1017,
            'amount' => -1002,
            'ref_id' => 5681,
            'memo' => 'test-neg-3'
        ];
        $this->getResponse('PUT', '/api/user/4/cash/op', $params);

        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:sync-cash-negative');

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $neg = $em->find('BBDurianBundle:CashNegative', ['userId' => 4, 'currency' => 901]);

        $this->assertEquals($neg->getBalance(), -1012);
        $this->assertEquals($neg->getVersion(), 6);
        $this->assertEquals($neg->getEntryId(), 1003);
        $this->assertNotNull($neg->getAt());
        $this->assertEquals($neg->getAmount(), -1100);
        $this->assertEquals($neg->getEntryBalance(), -10);
        $this->assertEquals($neg->getOpcode(), 1019);
        $this->assertEquals($neg->getRefId(), 5680);
        $this->assertEquals($neg->getMemo(), 'test-neg-2');
        $this->assertTrue($neg->isNegative());
    }

    /**
     * 測試原正數轉為負數，採用兩階段交易機制
     */
    public function testPositiveToNegativeBalanceWithTransaction()
    {
        $params = [
            'opcode' => 1019,
            'amount' => -1100,
            'ref_id' => 5678,
            'memo' => 'test-neg',
            'auto_commit' => 0
        ];
        $res = $this->getResponse('PUT', '/api/user/4/cash/op', $params);
        $id = $res['ret']['entry']['id'];

        $this->getResponse('PUT', "/api/cash/transaction/{$id}/commit");

        $params = [
            'opcode' => 1019,
            'amount' => -1100,
            'ref_id' => 5679,
            'memo' => 'test-neg-2',
            'auto_commit' => 0
        ];
        $res = $this->getResponse('PUT', '/api/user/4/cash/op', $params);
        $id = $res['ret']['entry']['id'];

        $this->getResponse('PUT', "/api/cash/transaction/{$id}/commit");

        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:sync-cash-negative');

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $neg = $em->find('BBDurianBundle:CashNegative', ['userId' => 4, 'currency' => 901]);

        $this->assertEquals($neg->getCashId(), 3);
        $this->assertEquals($neg->getBalance(), -1200);
        $this->assertEquals($neg->getVersion(), 6);
        $this->assertEquals($neg->getEntryId(), 1001);
        $this->assertNotNull($neg->getAt());
        $this->assertEquals($neg->getAmount(), -1100);
        $this->assertEquals($neg->getEntryBalance(), -100);
        $this->assertEquals($neg->getOpcode(), 1019);
        $this->assertEquals($neg->getRefId(), 5678);
        $this->assertEquals($neg->getMemo(), 'test-neg');
        $this->assertTrue($neg->isNegative());
    }

    /**
     * 測試原正數轉為負數，採用 multi-order-bunch
     */
    public function testPositiveToNegativeBalanceWithMultiOrderBunch()
    {
        $opService = $this->getContainer()->get('durian.op');

        $params = [
            'pay_way' => 'cash',
            'opcode' => 1019,
            'od_count' => 3,
            'od' => [
                [
                    'am' => 10,
                    'ref' => 123,
                    'memo' => 'pos-1'
                ],
                [
                    'am' => -1100,
                    'ref' => 124,
                    'memo' => 'neg-1'
                ],
                [
                    'am' => -32,
                    'ref' => 135,
                    'memo' => 'neg-2'
                ]
            ]
        ];
        $this->getResponse('PUT', '/api/user/4/multi_order_bunch', $params);

        $this->runCommand('durian:run-cash-poper');
        $this->runCommand('durian:sync-cash-negative');

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $neg = $em->find('BBDurianBundle:CashNegative', ['userId' => 4, 'currency' => 901]);

        $this->assertEquals($neg->getCashId(), 3);
        $this->assertEquals($neg->getBalance(), -122);
        $this->assertEquals($neg->getVersion(), 5);
        $this->assertEquals($neg->getEntryId(), 1002);
        $this->assertNotNull($neg->getAt());
        $this->assertEquals($neg->getAmount(), -1100);
        $this->assertEquals($neg->getEntryBalance(), -90);
        $this->assertEquals($neg->getOpcode(), 1019);
        $this->assertEquals($neg->getRefId(), 124);
        $this->assertEquals($neg->getMemo(), 'neg-1');
        $this->assertTrue($neg->isNegative());
    }

    /**
     * 測試推入錯誤的佇列
     */
    public function testWrongQueueFormat()
    {
        $redis = $this->getContainer()->get('snc_redis.default');
        $redis->lpush('cash_negative_queue', json_encode(['user_id' => 5]));
        $redis->lpush('cash_negative_queue', json_encode(['currency' => 156]));
        $redis->lpush('cash_negative_queue',json_encode(['user_id' => 5, 'currency' => 156]));
        $this->assertEquals(3, $redis->llen('cash_negative_queue'));

        $this->runCommand('durian:sync-cash-negative');

        $this->assertEquals(0, $redis->llen('cash_negative_queue'));
    }

    /**
     * 測試出現例外情況，會將佇列重推回去
     */
    public function testEntityManagerFlushException()
    {
        $container = $this->getContainer();
        $redis = $container->get('snc_redis.default');

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->disableOriginalConstructor()
            ->getMock();

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $mockEm->expects($this->any())
            ->method('flush')
            ->will($this->throwException(new \Exception('Connection timed-out', 111)));

        $maps = [
            ['durian.monitor.background', 1, $container->get('durian.monitor.background')],
            ['snc_redis.default', 1, $redis],
            ['doctrine.orm.default_entity_manager', 1, $mockEm]
        ];
        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($maps));

        $params = [
            'opcode' => 1019,
            'amount' => -1100,
            'ref_id' => 5678,
            'memo' => 'test-neg'
        ];
        $this->getResponse('PUT', '/api/user/4/cash/op', $params);

        $this->runCommand('durian:run-cash-poper');
        $this->assertEquals(1, $redis->llen('cash_negative_queue'));

        $application = new Application();
        $command = new SyncCashNegativeCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $command = $application->find('durian:sync-cash-negative');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $output = $commandTester->getDisplay();
        $this->assertContains('Connection timed-out', $output);
        $this->assertContains('111', $output);

        $this->assertEquals(1, $redis->llen('cash_negative_queue'));
    }
}
