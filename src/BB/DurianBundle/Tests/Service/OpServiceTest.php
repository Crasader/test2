<?php

namespace BB\DurianBundle\Tests\Service;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\Cash;
use BB\DurianBundle\Service\OpService;

class OpServiceTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashDataTwo',
        ];

        $this->loadFixtures($classnames);

        $redis = $this->getContainer()->get('snc_redis.sequence');

        $redis->set('cash_seq', 1000);
    }

    /**
     * 測試現金從redisㄧ般交易但使用者被停權
     */
    public function testCashOpByRedisWhenUserBankrupt()
    {
        $this->setExpectedException('RuntimeException', 'User is bankrupt', 150580023);

        $user = new User();
        $user->setBankrupt(true);

        $cash = new Cash($user, 156); // CNY
        $options = array(
            'opcode'   => 50024,  // BETTING-5-5013 開分-機率-傳統
            'memo'     => 'this is memo',
            'refId'    => 12345678901,
            'auto_commit' => true
        );

        $service = new OpService();
        $service->cashOpByRedis($cash, 100, $options);
    }

    /**
     * 測試現金從redis直接交易但使用者被停權
     */
    public function testCashDirectOpByRedisWhenUserBankrupt()
    {
        $this->setExpectedException('RuntimeException', 'User is bankrupt', 150580023);

        $user = new User();
        $user->setBankrupt(true);

        $cash = new Cash($user, 156); // CNY
        $options = array(
            'opcode'   => 1007, // TRANSFER-4-OUT
            'memo'     => 'this is memo',
            'refId'    => 12345678901,
            'auto_commit' => true
        );

        $service = new OpService();
        $service->cashDirectOpByRedis($cash, 100, $options);
    }

    /**
     * 測試現金從redis直接交易但失敗
     */
    public function testCashDirectOpByRedisRollback()
    {
        $redis = $this->getContainer()->get('snc_redis.wallet4');
        $redis->hset('cash_balance_8_901', 'balance', 1000000);
        $redis->hset('cash_balance_8_901', 'version', 1);

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $cash = $em->find('BBDurianBundle:Cash', 1);

        $options = [
            'opcode'      => 50024,
            'memo'        => 'this is memo',
            'refId'       => 12345678901,
            'auto_commit' => true
        ];

        $service = $this->getContainer()->get('durian.op');
        $service->cashDirectOpByRedis($cash, -100, $options, true, 0);

        $balance = $redis->hgetall('cash_balance_8_901');
        $this->assertEquals(0, $balance['balance']);
        $this->assertEquals(2, $balance['version']);
    }

    /**
     * 測試現金從redis直接交易但連線逾時
     */
    public function testCashDirectOpByRedisWithRedisTimeOut()
    {
        $mockContainer = $this->getMockContainer();

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $cash = $em->find('BBDurianBundle:Cash', 1);

        $options = [
            'opcode'      => 50024,
            'auto_commit' => true
        ];

        try {
            $service = new OpService();
            $service->setContainer($mockContainer);
            $service->cashDirectOpByRedis($cash, -1, $options);
        } catch (\Exception $e) {
        }

        $redisWallet = $this->getContainer()->get('snc_redis.wallet4');
        $balance = $redisWallet->hgetall('cash_balance_8_901');
        $this->assertEquals(1000000, $balance['balance']);
        $this->assertEquals(4, $balance['version']);
    }

    /**
     * 測試現金從redis交易但連線逾時
     */
    public function testCashOpByRedisWithRedisTimeOut()
    {
        $mockContainer = $this->getMockContainer();

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $cash = $em->find('BBDurianBundle:Cash', 1);

        $options = [
            'opcode'      => 50024,
            'auto_commit' => false
        ];

        try {
            $service = new OpService();
            $service->setContainer($mockContainer);
            $service->cashOpByRedis($cash, -1, $options);
        } catch (\Exception $e) {
        }

        $redisWallet = $this->getContainer()->get('snc_redis.wallet4');
        $balance = $redisWallet->hgetall('cash_balance_8_901');
        $this->assertEquals(1000000, $balance['balance']);
        $this->assertEquals(0, $balance['pre_sub']);
        $this->assertEquals(4, $balance['version']);
    }

    /**
     * 測試確認現金交易但連線逾時
     */
    public function testCashTransCommitByRedisWithRedisTimeOut()
    {
        $mockContainer = $this->getMockContainer();

        $redisWallet = $this->getContainer()->get('snc_redis.wallet4');

        $redisWallet->hset('cash_balance_8_901', 'balance', 1000000);
        $redisWallet->hset('cash_balance_8_901', 'pre_add', 10000);
        $redisWallet->hset('cash_balance_8_901', 'version', 2);

        $tRedisWallet = $this->getContainer()->get('snc_redis.wallet1');

        $tRedisWallet->hset('en_cashtrans_id_1', 'merchant_id', 0);
        $tRedisWallet->hset('en_cashtrans_id_1', 'remit_account_id', 0);
        $tRedisWallet->hset('en_cashtrans_id_1', 'domain', 9);
        $tRedisWallet->hset('en_cashtrans_id_1', 'cash_id', 1);
        $tRedisWallet->hset('en_cashtrans_id_1', 'amount', 10000);
        $tRedisWallet->hset('en_cashtrans_id_1', 'opcode', 50024);
        $tRedisWallet->hset('en_cashtrans_id_1', 'memo', 'memo');
        $tRedisWallet->hset('en_cashtrans_id_1', 'ref_id', 1);
        $tRedisWallet->hset('en_cashtrans_id_1', 'operator', '');
        $tRedisWallet->hset('en_cashtrans_id_1', 'tag', '');
        $tRedisWallet->hset('en_cashtrans_id_1', 'user_id', 8);
        $tRedisWallet->hset('en_cashtrans_id_1', 'currency', 901);
        $tRedisWallet->hset('en_cashtrans_id_1', 'created_at', '2015-01-01 12:00:00');

        try {
            $service = new OpService();
            $service->setContainer($mockContainer);
            $service->cashTransCommitByRedis(1);
        } catch (\Exception $e) {
        }

        $balance = $redisWallet->hgetall('cash_balance_8_901');
        $this->assertEquals(1000000, $balance['balance']);
        $this->assertEquals(10000, $balance['pre_add']);
        $this->assertEquals(4, $balance['version']);

        $status = $tRedisWallet->hget('en_cashtrans_id_1', 'status');
        $this->assertEquals(0, $status);
    }

    /**
     * 測試取消現金交易但連線逾時
     */
    public function testCashRollBackByRedisWithRedisTimeOut()
    {
        $mockContainer = $this->getMockContainer();

        $redisWallet = $this->getContainer()->get('snc_redis.wallet4');

        $redisWallet->hset('cash_balance_8_901', 'balance', 1000000);
        $redisWallet->hset('cash_balance_8_901', 'pre_sub', 10000);
        $redisWallet->hset('cash_balance_8_901', 'version', 2);

        $tRedisWallet = $this->getContainer()->get('snc_redis.wallet1');

        $tRedisWallet->hset('en_cashtrans_id_1', 'merchant_id', 0);
        $tRedisWallet->hset('en_cashtrans_id_1', 'remit_account_id', 0);
        $tRedisWallet->hset('en_cashtrans_id_1', 'domain', 9);
        $tRedisWallet->hset('en_cashtrans_id_1', 'cash_id', 1);
        $tRedisWallet->hset('en_cashtrans_id_1', 'amount', -10000);
        $tRedisWallet->hset('en_cashtrans_id_1', 'opcode', 50024);
        $tRedisWallet->hset('en_cashtrans_id_1', 'memo', 'memo');
        $tRedisWallet->hset('en_cashtrans_id_1', 'ref_id', 1);
        $tRedisWallet->hset('en_cashtrans_id_1', 'operator', '');
        $tRedisWallet->hset('en_cashtrans_id_1', 'tag', '');
        $tRedisWallet->hset('en_cashtrans_id_1', 'user_id', 8);
        $tRedisWallet->hset('en_cashtrans_id_1', 'currency', 901);
        $tRedisWallet->hset('en_cashtrans_id_1', 'created_at', '2015-01-01 12:00:00');

        try {
            $service = new OpService();
            $service->setContainer($mockContainer);
            $service->cashRollBackByRedis(1);
        } catch (\Exception $e) {
        }

        $balance = $redisWallet->hgetall('cash_balance_8_901');
        $this->assertEquals(1000000, $balance['balance']);
        $this->assertEquals(10000, $balance['pre_sub']);
        $this->assertEquals(4, $balance['version']);

        $status = $tRedisWallet->hget('en_cashtrans_id_1', 'status');
        $this->assertEquals(0, $status);
    }

    /**
     * 取得 MockContainer
     *
     * @return Container
     */
    private function getMockContainer()
    {
        $redis = $this->getMockBuilder('Predis\Client')
            ->disableOriginalConstructor()
            ->setMethods(['ping', 'connect', 'lpush'])
            ->getMock();

        $redis->expects($this->any())
            ->method('ping')
            ->will($this->returnValue('pong'));

        $redis->expects($this->any())
            ->method('connect')
            ->will($this->returnValue('Predis\Connection\PhpiredisStreamConnection'));

        $redis->expects($this->any())
            ->method('lpush')
            ->will($this->throwException(new \Exception('Connection timed out', SOCKET_ETIMEDOUT)));

        $redisWallet1 = $this->getContainer()->get('snc_redis.wallet1');
        $redisWallet2 = $this->getContainer()->get('snc_redis.wallet2');
        $redisWallet3 = $this->getContainer()->get('snc_redis.wallet3');
        $redisWallet4 = $this->getContainer()->get('snc_redis.wallet4');

        $idGenerator = $this->getContainer()->get('durian.cash_entry_id_generator');

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();
        $getMap = [
            ['snc_redis.default', 1, $redis],
            ['snc_redis.wallet1', 1, $redisWallet1],
            ['snc_redis.wallet2', 1, $redisWallet2],
            ['snc_redis.wallet3', 1, $redisWallet3],
            ['snc_redis.wallet4', 1, $redisWallet4],
            ['durian.cash_entry_id_generator', 1, $idGenerator]
        ];
        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        return $mockContainer;
    }
}
