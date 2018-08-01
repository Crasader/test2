<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Command\SyncCashFakeNegativeCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use BB\DurianBundle\Entity\CashFake;

class SyncCashFakeNegativeCommandTest extends WebTestCase
{
    /**
     * 預先設定
     */
    public function setUp()
    {
        parent::setUp();

        $classNames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeNegativeData'
        ];
        $this->loadFixtures($classNames);

        $redis = $this->getContainer()->get('snc_redis.sequence');
        $redis->set('cashfake_seq', 1000);
    }

    /**
     * 測試原正數轉為負數
     */
    public function testPositiveToNegativeBalance()
    {
        $params = [
            'opcode' => 1019,
            'amount' => -100,
            'ref_id' => 5678,
            'memo' => 'test-neg'
        ];
        $this->getResponse('PUT', '/api/user/8/cash_fake/op', $params);

        $this->runCommand('durian:sync-cash-fake', ['--entry' => true]);
        $this->runCommand('durian:sync-cash-fake-negative');

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $neg = $em->find('BBDurianBundle:CashFakeNegative', ['userId' => 8, 'currency' => 156]);

        $this->assertEquals($neg->getCashFakeId(), 2);
        $this->assertEquals($neg->getBalance(), -100);
        $this->assertEquals($neg->getVersion(), 2);
        $this->assertEquals($neg->getEntryId(), 1001);
        $this->assertNotNull($neg->getAt());
        $this->assertEquals($neg->getAmount(), -100);
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
        $cashFake = $em->find('BBDurianBundle:CashFake', 2);
        $cashFake->setBalance(-1);
        $em->flush();

        $params = [
            'opcode' => 1010,
            'amount' => 100,
            'ref_id' => 8765,
            'memo' => 'test-pos'
        ];
        $this->getResponse('PUT', '/api/user/8/cash_fake/op', $params);

        $this->runCommand('durian:sync-cash-fake', ['--entry' => true]);
        $this->runCommand('durian:sync-cash-fake-negative');

        $neg = $em->find('BBDurianBundle:CashFakeNegative', ['userId' => 8, 'currency' => 156]);

        $this->assertEquals($neg->getCashFakeId(), 2);
        $this->assertEquals($neg->getBalance(), 99);
        $this->assertEquals($neg->getVersion(), 2);
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
        $this->getResponse('PUT', '/api/user/8/cash_fake/op', $params);

        $params = [
            'opcode' => 1010,
            'amount' => 100,
            'ref_id' => 5679,
            'memo' => 'test-pos'
        ];
        $this->getResponse('PUT', '/api/user/8/cash_fake/op', $params);

        // 第一次負數
        $params = [
            'opcode' => 1019,
            'amount' => -1000,
            'ref_id' => 5680,
            'memo' => 'test-neg-2'
        ];
        $this->getResponse('PUT', '/api/user/8/cash_fake/op', $params);

        // 第二次負數
        $params = [
            'opcode' => 1017,
            'amount' => -1002,
            'ref_id' => 5681,
            'memo' => 'test-neg-3'
        ];
        $this->getResponse('PUT', '/api/user/8/cash_fake/op', $params);

        $this->runCommand('durian:sync-cash-fake', ['--entry' => true]);
        $this->runCommand('durian:sync-cash-fake-negative');

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $neg = $em->find('BBDurianBundle:CashFakeNegative', ['userId' => 8, 'currency' => 156]);

        $this->assertEquals($neg->getBalance(), -1912);
        $this->assertEquals($neg->getVersion(), 5);
        $this->assertEquals($neg->getEntryId(), 1003);
        $this->assertNotNull($neg->getAt());
        $this->assertEquals($neg->getAmount(), -1000);
        $this->assertEquals($neg->getEntryBalance(), -910);
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
            'amount' => -100,
            'ref_id' => 5678,
            'memo' => 'test-neg',
            'auto_commit' => 0
        ];
        $res = $this->getResponse('PUT', '/api/user/8/cash_fake/op', $params);
        $id = $res['ret']['entries'][0]['id'];

        $this->getResponse('PUT', "/api/cash_fake/transaction/{$id}/commit");

        $params = [
            'opcode' => 1019,
            'amount' => -100,
            'ref_id' => 5679,
            'memo' => 'test-neg-2',
            'auto_commit' => 0
        ];
        $res = $this->getResponse('PUT', '/api/user/8/cash_fake/op', $params);
        $id = $res['ret']['entries'][0]['id'];

        $this->getResponse('PUT', "/api/cash_fake/transaction/{$id}/commit");

        $this->runCommand('durian:sync-cash-fake', ['--entry' => true]);
        $this->runCommand('durian:sync-cash-fake-negative');

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $neg = $em->find('BBDurianBundle:CashFakeNegative', ['userId' => 8, 'currency' => 156]);

        $this->assertEquals($neg->getCashFakeId(), 2);
        $this->assertEquals($neg->getBalance(), -200);
        $this->assertEquals($neg->getVersion(), 5);
        $this->assertEquals($neg->getEntryId(), 1001);
        $this->assertNotNull($neg->getAt());
        $this->assertEquals($neg->getAmount(), -100);
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
            'pay_way' => 'cash_fake',
            'opcode' => 1019,
            'od_count' => 3,
            'od' => [
                [
                    'am' => 10,
                    'ref' => 123,
                    'memo' => 'pos-1'
                ],
                [
                    'am' => -100,
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
        $this->getResponse('PUT', '/api/user/8/multi_order_bunch', $params);

        $this->runCommand('durian:sync-cash-fake', ['--entry' => true]);
        $this->runCommand('durian:sync-cash-fake-negative');

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $neg = $em->find('BBDurianBundle:CashFakeNegative', ['userId' => 8, 'currency' => 156]);

        $this->assertEquals($neg->getCashFakeId(), 2);
        $this->assertEquals($neg->getBalance(), -122);
        $this->assertEquals($neg->getVersion(), 4);
        $this->assertEquals($neg->getEntryId(), 1002);
        $this->assertNotNull($neg->getAt());
        $this->assertEquals($neg->getAmount(), -100);
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
        $redis->lpush('cash_fake_negative_queue', json_encode(['user_id' => 5]));
        $redis->lpush('cash_fake_negative_queue', json_encode(['currency' => 156]));
        $redis->lpush('cash_fake_negative_queue',json_encode(['user_id' => 5, 'currency' => 156]));
        $this->assertEquals(3, $redis->llen('cash_fake_negative_queue'));

        $this->runCommand('durian:sync-cash-fake-negative');

        $this->assertEquals(0, $redis->llen('cash_fake_negative_queue'));
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
            'amount' => -100,
            'ref_id' => 5678,
            'memo' => 'test-neg'
        ];
        $this->getResponse('PUT', '/api/user/8/cash_fake/op', $params);

        $this->runCommand('durian:sync-cash-fake', ['--entry' => true]);
        $this->assertEquals(1, $redis->llen('cash_fake_negative_queue'));

        $application = new Application();
        $command = new SyncCashFakeNegativeCommand();
        $command->setContainer($mockContainer);
        $application->add($command);

        $command = $application->find('durian:sync-cash-fake-negative');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $output = $commandTester->getDisplay();
        $this->assertContains('Connection timed-out', $output);
        $this->assertContains('111', $output);

        $this->assertEquals(1, $redis->llen('cash_fake_negative_queue'));
    }
}
