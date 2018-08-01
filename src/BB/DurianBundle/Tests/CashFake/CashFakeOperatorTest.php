<?php

namespace BB\DurianBundle\Tests\CashFake;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\CashFake;

class CashFakeOperatorTest extends WebTestCase
{
    /**
     * 處理 Redis 2.6.0 前不支援浮點數運算所採用的乘數
     *
     * @var integer
     */
    protected $plusNumber = 10000;

    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeData'
        ];

        $this->loadFixtures($classnames);

        $redis = $this->getRedis('sequence');

        $redis->set('cashfake_seq', 1000);
    }

    /**
     * 回傳 Redis 操作物件
     *
     * @param string $name Redis名稱
     * @return \Predis\Client
     */
    private function getRedis($name = 'default')
    {
        return $this->getContainer()->get("snc_redis.{$name}");
    }

    /**
     * 回傳 EntityManager 物件
     *
     * @param string $name EntityManager 名稱
     *
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager($name = 'default')
    {
        return $this->getContainer()->get("doctrine.orm.{$name}_entity_manager");
    }

    /**
     * 測試下注，但refId不合法
     */
    public function testOperationWithInvalidRefId()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid ref_id', 150050022);

        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $options = [
            'cash_fake_id' => 2,
            'currency'     => 156,
            'opcode'       => 1001,
            'amount'       => 1,
            'ref_id'       => -1,
            'operator'     => ''
        ];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->operation($user, $options);
    }

    /**
     * 測試下注，但沒有傳交易代碼
     */
    public function testOperationWithoutOpcode()
    {
        $this->setExpectedException('InvalidArgumentException', 'No opcode specified', 150050017);

        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $options = [
            'cash_fake_id' => 2,
            'currency'     => 156,
            'opcode'       => null,
            'amount'       => 1,
            'operator'     => ''
        ];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->operation($user, $options);
    }

    /**
     * 測試下注，但交易代碼不合法
     */
    public function testOperationWithInvalidOpcode()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid opcode', 150050021);

        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $options = [
            'cash_fake_id' => 2,
            'currency'     => 156,
            'opcode'       => 'not an opcode',
            'amount'       => 1,
            'operator'     => ''
        ];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->operation($user, $options);
    }

    /**
     * 測試下注，但沒有傳交易金額
     */
    public function testOperationWithoutAmount()
    {
        $this->setExpectedException('InvalidArgumentException', 'No amount specified', 150050016);

        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $options = [
            'cash_fake_id' => 2,
            'currency'     => 156,
            'opcode'       => 1001,
            'amount'       => null,
            'operator'     => ''
        ];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->operation($user, $options);
    }

    /**
     * 測試下注，但交易金額超過範圍最大值
     */
    public function testOperationWithOversizeAmount()
    {
        $this->setExpectedException('RangeException', 'Oversize amount given which exceeds the MAX', 150050028);

        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $options = [
            'cash_fake_id' => 2,
            'currency'     => 156,
            'opcode'       => 1001,
            'amount'       => 100000000000,
            'operator'     => ''
        ];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->operation($user, $options);
    }

    /**
     * 測試下注，但強制扣款交易金額為0
     */
    public function testOperationWithAmountZero()
    {
        $this->setExpectedException('InvalidArgumentException', 'Amount can not be zero', 150050027);

        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $options = [
            'cash_fake_id' => 2,
            'currency'     => 156,
            'opcode'       => 1001,
            'amount'       => 0,
            'operator'     => ''
        ];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->operation($user, $options);
    }

    /**
     * 測試下注，但快開額度已停用
     */
    public function testOperationWithCashFakeIsDisabled()
    {
        $this->setExpectedException('RuntimeException', 'CashFake is disabled', 150050007);

        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $repo = $em->getRepository('BBDurianBundle:CashFake');
        $cashFake = $repo->findOneBy(['user' => 8, 'currency' => 156]);
        $cashFake->disable();

        $options = [
            'cash_fake_id' => 2,
            'currency'     => 156,
            'opcode'       => 10002,
            'amount'       => 1,
            'operator'     => ''
        ];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->operation($user, $options);
    }

    /**
     * 測試下注，但上層快開額度已停用
     */
    public function testOperationWithParentCashFakeIsDisabled()
    {
        $this->setExpectedException('RuntimeException', 'CashFake is disabled', 150050007);

        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $repo = $em->getRepository('BBDurianBundle:CashFake');
        $cashFake = $repo->findOneBy(['user' => 7, 'currency' => 156]);
        $cashFake->disable();

        $options = [
            'cash_fake_id' => 2,
            'currency'     => 156,
            'opcode'       => 10002,
            'amount'       => 1,
            'operator'     => ''
        ];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->operation($user, $options);
    }

    /**
     * 測試下注，但使用者已停權
     */
    public function testOperationWithUserIsBankrupt()
    {
        $this->setExpectedException('RuntimeException', 'User is bankrupt', 150050036);

        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $options = [
            'cash_fake_id' => 2,
            'currency'     => 156,
            'opcode'       => 10002,
            'amount'       => 1,
            'operator'     => ''
        ];

        $user = $em->find('BBDurianBundle:User', 8);
        $user->setBankrupt(1);

        $fakeOp->operation($user, $options);
    }

    /**
     * 測試下注，但RefId重複
     */
    public function testOperationWithDuplicateRefId()
    {
        $this->setExpectedException('RuntimeException', 'Duplicate ref id', 150050008);

        $redisWallet = $this->getRedis('wallet1');
        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $time = \time();
        $redisWallet->zadd('duplicate_refid_2', $time + 604800, 1);

        $options = [
            'cash_fake_id' => 2,
            'currency'     => 156,
            'opcode'       => 1042,
            'amount'       => 1,
            'ref_id'       => 1,
            'operator'     => ''
        ];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->operation($user, $options);
    }

    /**
     * 測試下注，但快開額度餘額超過PHP整數最大值
     */
    public function testOperationWithCashFakeBalanceExceedsMaxInteger()
    {
        $this->setExpectedException('RangeException', 'Balance exceeds allowed MAX integer', 150050037);

        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $repo = $em->getRepository('BBDurianBundle:CashFake');
        $cashFake = $repo->findOneBy(['user' => 8, 'currency' => 156]);
        $cashFake->setBalance(PHP_INT_MAX / $this->plusNumber + 1);

        $options = [
            'cash_fake_id' => 2,
            'currency'     => 156,
            'opcode'       => 1001,
            'amount'       => 1,
            'operator'     => ''
        ];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->operation($user, $options);
    }

    /**
     * 測試下注，但快開額度預扣超過PHP整數最大值
     */
    public function testOperationWithCashFakePresubExceedsMaxInteger()
    {
        $this->setExpectedException('RangeException', 'Presub exceeds allowed MAX integer', 150050038);

        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $repo = $em->getRepository('BBDurianBundle:CashFake');
        $cashFake = $repo->findOneBy(['user' => 8, 'currency' => 156]);
        $cashFake->addPreSub(PHP_INT_MAX / $this->plusNumber + 1);

        $options = [
            'cash_fake_id' => 2,
            'currency'     => 156,
            'opcode'       => 1001,
            'amount'       => 1,
            'operator'     => ''
        ];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->operation($user, $options);
    }

    /**
     * 測試下注，但快開額度預存超過PHP整數最大值
     */
    public function testOperationWithCashFakePreaddExceedsMaxInteger()
    {
        $this->setExpectedException('RangeException', 'Preadd exceeds allowed MAX integer', 150050039);

        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $repo = $em->getRepository('BBDurianBundle:CashFake');
        $cashFake = $repo->findOneBy(['user' => 8, 'currency' => 156]);
        $cashFake->addPreAdd(PHP_INT_MAX / $this->plusNumber + 1);

        $options = [
            'cash_fake_id' => 2,
            'currency'     => 156,
            'opcode'       => 1001,
            'amount'       => 1,
            'operator'     => ''
        ];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->operation($user, $options);
    }

    /**
     * 測試DIRECT下注，但快開額度餘額超過PHP整數最大值
     */
    public function testDirectOperationWithCashFakeBalanceExceedsMaxInteger()
    {
        $this->setExpectedException('RangeException', 'Balance exceeds allowed MAX integer', 150050037);

        $redisWallet = $this->getRedis('wallet4');
        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        // 修改 Redis balance 的值，直接指定 PHP_INT_MAX 會超過 Redis 的限制，故先減去交易金額
        $redisWallet->hset('cash_fake_balance_8_156', 'balance', PHP_INT_MAX - CashFake::MAX_BALANCE * $this->plusNumber);

        $options = [
            'cash_fake_id' => 2,
            'currency'     => 156,
            'opcode'       => 1001,
            'amount'       => CashFake::MAX_BALANCE,
            'operator'     => ''
        ];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->setOperationType($fakeOp::OP_DIRECT);
        $fakeOp->operation($user, $options);
    }

    /**
     * 測試TRANSACTION下注，但餘額超過交易金額最大值
     */
    public function testTransactionOperationWithBalanceExceedsTheMax()
    {
        $this->setExpectedException('RangeException', 'The balance exceeds the MAX amount', 150050030);

        $redisWallet = $this->getRedis('wallet4');
        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        // 修改 Redis balance 的值為交易金額上限
        $redisWallet->hset('cash_fake_balance_8_156', 'balance', CashFake::MAX_BALANCE * $this->plusNumber);

        $options = [
            'cash_fake_id' => 2,
            'currency'     => 156,
            'opcode'       => 1001,
            'amount'       => 1,
            'ref_id'       => 1,
            'operator'     => ''
        ];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->setOperationType($fakeOp::OP_TRANSACTION);
        $fakeOp->operation($user, $options);
    }

    /**
     * 測試TRANSACTION下注，但餘額不足
     */
    public function testTransactionOperationWithNotEnoughBalance()
    {
        $this->setExpectedException('RuntimeException', 'Not enough balance', 150050031);

        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $options = [
            'cash_fake_id' => 2,
            'currency'     => 156,
            'opcode'       => 1042,
            'amount'       => -5000,
            'ref_id'       => 1,
            'operator'     => ''
        ];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->setOperationType($fakeOp::OP_TRANSACTION);
        $fakeOp->operation($user, $options);
    }

    /**
     * 測試DIRECT下注
     */
    public function testDirectOperation()
    {
        $redis = $this->getRedis();
        $redisWallet = $this->getRedis('wallet4');
        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $options = [
            'cash_fake_id' => 2,
            'currency'     => 156,
            'opcode'       => 1001,
            'amount'       => 1,
            'ref_id'       => 1,
            'operator'     => 'tester',
            'memo'         => 'test',
            'force'        => true
        ];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->setOperationType($fakeOp::OP_DIRECT);
        $result = $fakeOp->operation($user, $options);
        $at = (new \DateTime($result['entry'][0]['created_at']))->format('YmdHis');

        $this->assertEquals(2, $result['cash_fake']['id']);
        $this->assertEquals(8, $result['cash_fake']['user_id']);
        $this->assertEquals(1, $result['cash_fake']['balance']);
        $this->assertEquals(0, $result['cash_fake']['pre_sub']);
        $this->assertEquals(0, $result['cash_fake']['pre_add']);
        $this->assertEquals('CNY', $result['cash_fake']['currency']);
        $this->assertEquals(1, $result['cash_fake']['enable']);
        $this->assertEquals(1001, $result['entry'][0]['id']);
        $this->assertEquals(2, $result['entry'][0]['cash_fake_id']);
        $this->assertEquals(8, $result['entry'][0]['user_id']);
        $this->assertEquals('CNY', $result['entry'][0]['currency']);
        $this->assertEquals(1001, $result['entry'][0]['opcode']);
        $this->assertEquals(1, $result['entry'][0]['amount']);
        $this->assertEquals(1, $result['entry'][0]['balance']);
        $this->assertEquals(1001, $result['entry'][0]['operator']['entry_id']);
        $this->assertEquals('tester', $result['entry'][0]['operator']['username']);

        $balance = $redisWallet->hgetall('cash_fake_balance_8_156');

        $this->assertEquals(10000, $balance['balance']);
        $this->assertEquals(0, $balance['pre_sub']);
        $this->assertEquals(0, $balance['pre_add']);
        $this->assertEquals(2, $balance['version']);

        $fakeOp->confirm();

        $balanceQueue = '{"id":2,"user_id":8,"balance":1,"pre_sub":0,"pre_add":0,"version":2,"currency":156,'.
                        '"enable":true,"last_entry_at":"' . $at . '"}';
        $this->assertEquals($balanceQueue, $redis->rpop('cash_fake_balance_queue'));
        $this->assertEquals(1, $redis->llen('cash_fake_entry_queue'));
        $this->assertEquals(1, $redis->llen('cash_fake_transfer_queue'));
        $this->assertEquals(1, $redis->llen('cash_fake_operator_queue'));

        //測試opcode=1098時不更新交易時間
        $options = [
            'cash_fake_id' => 2,
            'currency'     => 156,
            'opcode'       => 1098,
            'amount'       => 1,
            'ref_id'       => 1,
            'operator'     => 'tester',
            'memo'         => 'test',
            'force'        => true
        ];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->setOperationType($fakeOp::OP_DIRECT);
        $result = $fakeOp->operation($user, $options);

        $this->assertEquals(2, $result['cash_fake']['id']);

        $fakeOp->confirm();

        $this->assertNotContains('last_entry_at', $redis->rpop('cash_fake_balance_queue'));
    }

    /**
     * 測試TRANSACTION下注
     */
    public function testTransationOperation()
    {
        $redis = $this->getRedis();
        $redisWallet = $this->getRedis('wallet4');
        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $options = [
            'cash_fake_id' => 2,
            'currency'     => 156,
            'opcode'       => 1001,
            'amount'       => 1,
            'operator'     => ''
        ];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->setOperationType($fakeOp::OP_TRANSACTION);
        $result = $fakeOp->operation($user, $options);

        $this->assertEquals(2, $result['cash_fake']['id']);
        $this->assertEquals(8, $result['cash_fake']['user_id']);
        $this->assertEquals(0, $result['cash_fake']['balance']);
        $this->assertEquals(0, $result['cash_fake']['pre_sub']);
        $this->assertEquals(1, $result['cash_fake']['pre_add']);
        $this->assertEquals('CNY', $result['cash_fake']['currency']);
        $this->assertEquals(1, $result['cash_fake']['enable']);
        $this->assertEquals(1001, $result['entry'][0]['id']);
        $this->assertEquals(2, $result['entry'][0]['cash_fake_id']);
        $this->assertEquals(8, $result['entry'][0]['user_id']);
        $this->assertEquals('CNY', $result['entry'][0]['currency']);
        $this->assertEquals(1001, $result['entry'][0]['opcode']);
        $this->assertEquals(1, $result['entry'][0]['amount']);

        $balance = $redisWallet->hgetall('cash_fake_balance_8_156');

        $this->assertEquals(0, $balance['balance']);
        $this->assertEquals(0, $balance['pre_sub']);
        $this->assertEquals(10000, $balance['pre_add']);
        $this->assertEquals(2, $balance['version']);

        $fakeOp->confirm();

        $this->assertEquals(1, $redis->llen('cash_fake_trans_queue'));
    }

    /**
     * 測試TRANSACTION下注帶負數交易金額
     */
    public function testTransationOperationWithNegativeAmount()
    {
        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $repo = $em->getRepository('BBDurianBundle:CashFake');
        $cashFake = $repo->findOneBy(['user' => 8, 'currency' => 156]);
        $cashFake->setBalance(1);

        $options = [
            'cash_fake_id' => 2,
            'currency'     => 156,
            'opcode'       => 1001,
            'amount'       => -1,
            'operator'     => ''
        ];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->setOperationType($fakeOp::OP_TRANSACTION);
        $result = $fakeOp->operation($user, $options);

        $this->assertEquals(2, $result['cash_fake']['id']);
        $this->assertEquals(8, $result['cash_fake']['user_id']);
        $this->assertEquals(1, $result['cash_fake']['balance']);
        $this->assertEquals(1, $result['cash_fake']['pre_sub']);
        $this->assertEquals(0, $result['cash_fake']['pre_add']);
        $this->assertEquals('CNY', $result['cash_fake']['currency']);
        $this->assertEquals(1, $result['cash_fake']['enable']);
        $this->assertEquals(1001, $result['entry'][0]['id']);
        $this->assertEquals(2, $result['entry'][0]['cash_fake_id']);
        $this->assertEquals(8, $result['entry'][0]['user_id']);
        $this->assertEquals('CNY', $result['entry'][0]['currency']);
        $this->assertEquals(1001, $result['entry'][0]['opcode']);
        $this->assertEquals(-1, $result['entry'][0]['amount']);
    }

    /**
     * 測試轉移快開額度，但refId不合法
     */
    public function testTransferWithInvalidRefId()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid ref_id', 150050022);

        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $options = [
            'source_id' => 7,
            'currency'  => 156,
            'opcode'    => 1001,
            'amount'    => 1,
            'ref_id'    => -1,
            'operator'  => ''
        ];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->transfer($user, $options);
    }

    /**
     * 測試轉移快開額度，但沒有傳交易代碼
     */
    public function testTransferWithoutOpcode()
    {
        $this->setExpectedException('InvalidArgumentException', 'No opcode specified', 150050017);

        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $options = [
            'source_id' => 7,
            'currency'  => 156,
            'opcode'    => null,
            'amount'    => 1,
            'operator'  => ''
        ];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->transfer($user, $options);
    }

    /**
     * 測試轉移快開額度，但交易代碼不合法
     */
    public function testTransferWithInvalidOpcode()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid opcode', 150050021);

        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $options = [
            'source_id' => 7,
            'currency'  => 156,
            'opcode'    => 'not an opcode',
            'amount'    => 1,
            'operator'  => ''
        ];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->transfer($user, $options);
    }

    /**
     * 測試轉移快開額度，但沒有傳交易金額
     */
    public function testTransferWithoutAmount()
    {
        $this->setExpectedException('InvalidArgumentException', 'No amount specified', 150050016);

        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $options = [
            'source_id' => 7,
            'currency'  => 156,
            'opcode'    => 1001,
            'amount'    => null,
            'operator'  => ''
        ];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->transfer($user, $options);
    }

    /**
     * 測試轉移快開額度，但交易金額超過範圍最大值
     */
    public function testTransferWithOversizeAmount()
    {
        $this->setExpectedException('RangeException', 'Oversize amount given which exceeds the MAX', 150050028);

        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $options = [
            'source_id' => 7,
            'currency'  => 156,
            'opcode'    => 1001,
            'amount'    => 100000000000,
            'operator'  => ''
        ];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->transfer($user, $options);
    }

    /**
     * 測試轉移快開額度，但強制扣款交易金額為0
     */
    public function testTransferWithAmountZero()
    {
        $this->setExpectedException('InvalidArgumentException', 'Amount can not be zero', 150050027);

        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $options = [
            'source_id' => 7,
            'currency'  => 156,
            'opcode'    => 1001,
            'amount'    => 0,
            'operator'  => ''
        ];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->transfer($user, $options);
    }

    /**
     * 測試轉移快開額度，但與上層不同幣別
     */
    public function testTransferWithDifferentCurrencyBetweenChildAndParent()
    {
        $this->setExpectedException('RuntimeException', 'Different currency between child and parent', 150050002);

        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $options = [
            'source_id' => 9,
            'currency'  => 156,
            'opcode'    => 1003,
            'amount'    => 1,
            'operator'  => ''
        ];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->transfer($user, $options);
    }

    /**
     * 測試DIRECT轉移快開額度，但快開額度餘額超過PHP整數最大值
     */
    public function testDirectTransferWithCashFakeBalanceExceedsMaxInteger()
    {
        $this->setExpectedException('RangeException', 'Balance exceeds allowed MAX integer', 150050037);

        $pRedisWallet = $this->getRedis('wallet3');
        $redisWallet = $this->getRedis('wallet4');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        // 修改 Redis balance 的值，直接指定 PHP_INT_MAX 會超過 Redis 的限制，故先減去交易金額

        $pRedisWallet->hset('cash_fake_balance_7_156', 'balance', CashFake::MAX_BALANCE * $this->plusNumber);
        $redisWallet->hset('cash_fake_balance_8_156', 'balance', PHP_INT_MAX - CashFake::MAX_BALANCE * $this->plusNumber);

        $options = [
            'source_id' => 7,
            'currency'  => 156,
            'opcode'    => 1003,
            'amount'    => CashFake::MAX_BALANCE,
            'operator'  => ''
        ];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->setOperationType($fakeOp::OP_DIRECT);
        $fakeOp->transfer($user, $options);
    }

    /**
     * 測試TRANSACTION轉移快開額度，但餘額超過交易金額最大值
     */
    public function testTransactionTransferWithBalanceExceedsTheMax()
    {
        $this->setExpectedException('RangeException', 'The balance exceeds the MAX amount', 150050030);

        $pRedisWallet = $this->getRedis('wallet3');
        $redisWallet = $this->getRedis('wallet4');
        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        // 修改 Redis balance 的值為交易金額上限
        $pRedisWallet->hset('cash_fake_balance_7_156', 'balance', CashFake::MAX_BALANCE * $this->plusNumber + 1);
        $redisWallet->hset('cash_fake_balance_8_156', 'balance', 1);

        $options = [
            'source_id' => 7,
            'currency'  => 156,
            'opcode'    => 1003,
            'amount'    => 1,
            'operator'  => ''
        ];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->setOperationType($fakeOp::OP_TRANSACTION);
        $fakeOp->transfer($user, $options);
    }

    /**
     * 測試TRANSACTION轉移快開額度，但餘額不足
     */
    public function testTransactionTransferWithNotEnoughBalance()
    {
        $this->setExpectedException('RuntimeException', 'Not enough balance', 150050031);

        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $options = [
            'source_id' => 7,
            'currency'  => 156,
            'opcode'    => 1003,
            'amount'    => -5000,
            'operator'  => ''
        ];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->setOperationType($fakeOp::OP_TRANSACTION);
        $fakeOp->transfer($user, $options);
    }

    /**
     * 測試DIRECT轉移快開額度
     */
    public function testDirectTransfer()
    {
        $redis = $this->getRedis();
        $pRedisWallet = $this->getRedis('wallet3');
        $redisWallet = $this->getRedis('wallet4');
        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $repo = $em->getRepository('BBDurianBundle:CashFake');
        $cashFake = $repo->findOneBy(['user' => 7, 'currency' => 156]);
        $cashFake->setBalance(1);

        $options = [
            'source_id' => 7,
            'currency'  => 156,
            'opcode'    => 1003,
            'amount'    => 1,
            'ref_id'    => 1,
            'operator'  => 'tester',
            'memo'      => 'test',
            'force'     => true
        ];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->setOperationType($fakeOp::OP_DIRECT);
        $result = $fakeOp->transfer($user, $options);
        $at = (new \DateTime($result['entry']['created_at']))->format('YmdHis');

        $this->assertEquals(1, $result['source_cash_fake']['id']);
        $this->assertEquals(7, $result['source_cash_fake']['user_id']);
        $this->assertEquals(0, $result['source_cash_fake']['balance']);
        $this->assertEquals(0, $result['source_cash_fake']['pre_sub']);
        $this->assertEquals(0, $result['source_cash_fake']['pre_add']);
        $this->assertEquals('CNY', $result['source_cash_fake']['currency']);
        $this->assertEquals(1, $result['source_cash_fake']['enable']);
        $this->assertEquals(1001, $result['source_entry']['id']);
        $this->assertEquals(1, $result['source_entry']['cash_fake_id']);
        $this->assertEquals(7, $result['source_entry']['user_id']);
        $this->assertEquals('CNY', $result['source_entry']['currency']);
        $this->assertEquals(1003, $result['source_entry']['opcode']);
        $this->assertEquals(-1, $result['source_entry']['amount']);
        $this->assertEquals(0, $result['source_entry']['balance']);
        $this->assertEquals(1001, $result['source_entry']['operator']['entry_id']);
        $this->assertEquals('tester', $result['source_entry']['operator']['username']);
        $this->assertEquals('tester', $result['source_entry']['flow']['whom']);
        $this->assertEquals(7, $result['source_entry']['flow']['level']);
        $this->assertEquals(1, $result['source_entry']['flow']['transfer_out']);

        $this->assertEquals(2, $result['cash_fake']['id']);
        $this->assertEquals(8, $result['cash_fake']['user_id']);
        $this->assertEquals(1, $result['cash_fake']['balance']);
        $this->assertEquals(0, $result['cash_fake']['pre_sub']);
        $this->assertEquals(0, $result['cash_fake']['pre_add']);
        $this->assertEquals('CNY', $result['cash_fake']['currency']);
        $this->assertEquals(1, $result['cash_fake']['enable']);
        $this->assertEquals(1002, $result['entry']['id']);
        $this->assertEquals(2, $result['entry']['cash_fake_id']);
        $this->assertEquals(8, $result['entry']['user_id']);
        $this->assertEquals('CNY', $result['entry']['currency']);
        $this->assertEquals(1003, $result['entry']['opcode']);
        $this->assertEquals(1, $result['entry']['amount']);
        $this->assertEquals(1, $result['entry']['balance']);
        $this->assertEquals(1002, $result['entry']['operator']['entry_id']);
        $this->assertEquals('tester', $result['entry']['operator']['username']);
        $this->assertEquals('ztester', $result['entry']['flow']['whom']);
        $this->assertEquals(7, $result['entry']['flow']['level']);
        $this->assertEquals(0, $result['entry']['flow']['transfer_out']);

        $balance1 = $pRedisWallet->hgetall('cash_fake_balance_7_156');

        $this->assertEquals(0, $balance1['balance']);
        $this->assertEquals(0, $balance1['pre_sub']);
        $this->assertEquals(0, $balance1['pre_add']);
        $this->assertEquals(2, $balance1['version']);

        $balance2 = $redisWallet->hgetall('cash_fake_balance_8_156');

        $this->assertEquals(10000, $balance2['balance']);
        $this->assertEquals(0, $balance2['pre_sub']);
        $this->assertEquals(0, $balance2['pre_add']);
        $this->assertEquals(2, $balance2['version']);

        $fakeOp->confirm();

        $balanceQueue1 = '{"id":1,"user_id":7,"balance":0,"pre_sub":0,"pre_add":0,"version":2,"currency":156,'.
                         '"enable":true,"last_entry_at":"' . $at . '"}';
        $this->assertEquals($balanceQueue1, $redis->rpop('cash_fake_balance_queue'));
        $balanceQueue2 = '{"id":2,"user_id":8,"balance":1,"pre_sub":0,"pre_add":0,"version":2,"currency":156,'.
                         '"enable":true,"last_entry_at":"' . $at . '"}';
        $this->assertEquals($balanceQueue2, $redis->rpop('cash_fake_balance_queue'));
        $this->assertEquals(2, $redis->llen('cash_fake_entry_queue'));
        $this->assertEquals(2, $redis->llen('cash_fake_transfer_queue'));
        $this->assertEquals(2, $redis->llen('cash_fake_operator_queue'));

        //測試opcode=1098時不更新交易時間
        $options = [
            'source_id' => 7,
            'currency'  => 156,
            'opcode'    => 1098,
            'amount'    => 1,
            'ref_id'    => 1,
            'operator'  => 'tester',
            'memo'      => 'test',
            'force'     => true
        ];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->setOperationType($fakeOp::OP_DIRECT);
        $result = $fakeOp->transfer($user, $options);

        $this->assertEquals(1, $result['source_cash_fake']['id']);

        $fakeOp->confirm();

        $this->assertNotContains('last_entry_at', $redis->rpop('cash_fake_balance_queue'));
    }

    /**
     * 測試TRANSACTION轉移快開額度
     */
    public function testTransactionTransfer()
    {
        $redis = $this->getRedis();
        $pRedisWallet = $this->getRedis('wallet3');
        $redisWallet = $this->getRedis('wallet4');
        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $repo = $em->getRepository('BBDurianBundle:CashFake');
        $cashFake = $repo->findOneBy(['user' => 7, 'currency' => 156]);
        $cashFake->setBalance(1);

        $options = [
            'source_id' => 7,
            'currency'  => 156,
            'opcode'    => 1003,
            'amount'    => 1,
            'operator'  => ''
        ];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->setOperationType($fakeOp::OP_TRANSACTION);
        $result = $fakeOp->transfer($user, $options);

        $this->assertEquals(1, $result['source_cash_fake']['id']);
        $this->assertEquals(7, $result['source_cash_fake']['user_id']);
        $this->assertEquals(1, $result['source_cash_fake']['balance']);
        $this->assertEquals(1, $result['source_cash_fake']['pre_sub']);
        $this->assertEquals(0, $result['source_cash_fake']['pre_add']);
        $this->assertEquals('CNY', $result['source_cash_fake']['currency']);
        $this->assertEquals(1, $result['source_cash_fake']['enable']);
        $this->assertEquals(1001, $result['source_entry']['id']);
        $this->assertEquals(1, $result['source_entry']['cash_fake_id']);
        $this->assertEquals(7, $result['source_entry']['user_id']);
        $this->assertEquals('CNY', $result['source_entry']['currency']);
        $this->assertEquals(1003, $result['source_entry']['opcode']);
        $this->assertEquals(-1, $result['source_entry']['amount']);
        $this->assertEmpty($result['source_entry']['operator']);
        $this->assertEquals('tester', $result['source_entry']['flow']['whom']);
        $this->assertEquals(7, $result['source_entry']['flow']['level']);
        $this->assertEquals(1, $result['source_entry']['flow']['transfer_out']);

        $this->assertEquals(2, $result['cash_fake']['id']);
        $this->assertEquals(8, $result['cash_fake']['user_id']);
        $this->assertEquals(0, $result['cash_fake']['balance']);
        $this->assertEquals(0, $result['cash_fake']['pre_sub']);
        $this->assertEquals(1, $result['cash_fake']['pre_add']);
        $this->assertEquals('CNY', $result['cash_fake']['currency']);
        $this->assertEquals(1, $result['cash_fake']['enable']);
        $this->assertEquals(1002, $result['entry']['id']);
        $this->assertEquals(2, $result['entry']['cash_fake_id']);
        $this->assertEquals(8, $result['entry']['user_id']);
        $this->assertEquals('CNY', $result['entry']['currency']);
        $this->assertEquals(1003, $result['entry']['opcode']);
        $this->assertEquals(1, $result['entry']['amount']);
        $this->assertEmpty($result['entry']['operator']);
        $this->assertEquals('ztester', $result['entry']['flow']['whom']);
        $this->assertEquals(7, $result['entry']['flow']['level']);
        $this->assertEquals(0, $result['entry']['flow']['transfer_out']);

        $balance1 = $pRedisWallet->hgetall('cash_fake_balance_7_156');

        $this->assertEquals(10000, $balance1['balance']);
        $this->assertEquals(10000, $balance1['pre_sub']);
        $this->assertEquals(0, $balance1['pre_add']);
        $this->assertEquals(2, $balance1['version']);

        $balance2 = $redisWallet->hgetall('cash_fake_balance_8_156');

        $this->assertEquals(0, $balance2['balance']);
        $this->assertEquals(0, $balance2['pre_sub']);
        $this->assertEquals(10000, $balance2['pre_add']);
        $this->assertEquals(2, $balance2['version']);

        $fakeOp->confirm();

        $this->assertEquals(2, $redis->llen('cash_fake_trans_queue'));
    }

    /**
     * 測試取得快開交易，但無快開額度交易記錄
     */
    public function testGetTransactionWithCashFakeTransNotFound()
    {
        $this->setExpectedException('RuntimeException', 'No cashFakeTrans found', 150050014);

        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $fakeOp->getTransaction(1);
    }

    /**
     * 測試取得快開交易
     */
    public function testGetTransaction()
    {
        $redisWallet = $this->getRedis('wallet1');
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $entry = [
            'id'           => 1,
            'cash_fake_id' => 1,
            'user_id'      => 7,
            'domain'       => 1,
            'currency'     => 156,
            'opcode'       => 1001,
            'amount'       => 1,
            'ref_id'       => 0,
            'operator'     => [],
            'flow'         => [],
            'memo'         => '',
            'created_at'   => date('2015-01-01 12:00:00'),
            'checked_at'   => null,
            'checked'      => false
        ];

        $redisWallet->hset('cash_fake_trans', 1, json_encode($entry));
        $redisWallet->hsetnx('cash_fake_trans_state', 1, 1);

        $result = $fakeOp->getTransaction(1);

        $this->assertEquals(1, $result['id']);
        $this->assertEquals(1, $result['cash_fake_id']);
        $this->assertEquals(7, $result['user_id']);
        $this->assertEquals('CNY', $result['currency']);
        $this->assertEquals(1001, $result['opcode']);
        $this->assertEquals('2015-01-01T12:00:00+0800', $result['created_at']);
        $this->assertEquals(1, $result['amount']);
    }

    /**
     * 測試確認快開交易，但已處理該筆交易記錄
     */
    public function testTransactionCommitWithAlreadyCheckStatus()
    {
        $this->setExpectedException('RuntimeException', 'Transaction already check status', 150050040);

        $redisWallet = $this->getRedis('wallet1');
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $entry = [
            'id'           => 1,
            'cash_fake_id' => 1,
            'user_id'      => 7,
            'domain'       => 1,
            'currency'     => 156,
            'opcode'       => 1001,
            'amount'       => 1,
            'ref_id'       => 0,
            'operator'     => [],
            'flow'         => [],
            'memo'         => '',
            'created_at'   => date('2015-01-01 12:00:00'),
            'checked_at'   => date('2015-01-01 12:00:00'),
            'checked'      => true
        ];

        $redisWallet->hset('cash_fake_trans', 1, json_encode($entry));
        $redisWallet->hsetnx('cash_fake_trans_state', 1, 3);

        $fakeOp->transactionCommit(1);
    }

    /**
     * 測試確認快開交易
     */
    public function testTransactionCommit()
    {
        $redis = $this->getRedis();
        $redisWallet = $this->getRedis('wallet1');
        $redisWallet2 = $this->getRedis('wallet2');
        $redisWallet3 = $this->getRedis('wallet3');
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $operator = [
            'entry_id' => 1,
            'username' => 'tester'
        ];

        $flow = [
            'whom'         => 'tester',
            'level'        => 1,
            'transfer_out' => 1
        ];

        $entry = [
            'id'           => 1,
            'cash_fake_id' => 1,
            'user_id'      => 7,
            'domain'       => 1,
            'currency'     => 156,
            'opcode'       => 1001,
            'amount'       => 2,
            'ref_id'       => 0,
            'operator'     => $operator,
            'flow'         => $flow,
            'memo'         => '',
            'created_at'   => date('2015-01-01 12:00:00'),
            'checked_at'   => null,
            'checked'      => false
        ];

        $redisWallet->hset('cash_fake_trans', 1, json_encode($entry));
        $redisWallet->hsetnx('cash_fake_trans_state', 1, 1);

        $entry1 = $fakeOp->transactionCommit(1);

        $this->assertEquals(1, $entry1['cash_fake']['id']);
        $this->assertEquals(7, $entry1['cash_fake']['user_id']);
        $this->assertEquals(2, $entry1['cash_fake']['balance']);
        $this->assertEquals('CNY', $entry1['cash_fake']['currency']);
        $this->assertEquals(1, $entry1['cash_fake']['enable']);
        $this->assertEquals(1, $entry1['entry']['id']);
        $this->assertEquals(1, $entry1['entry']['cash_fake_id']);
        $this->assertEquals(7, $entry1['entry']['user_id']);
        $this->assertEquals('CNY', $entry1['entry']['currency']);
        $this->assertEquals(1001, $entry1['entry']['opcode']);
        $this->assertEquals('2015-01-01T12:00:00+0800', $entry1['entry']['created_at']);
        $this->assertEquals(2, $entry1['entry']['amount']);
        $this->assertEquals(2, $entry1['entry']['balance']);
        $this->assertEquals(1, $entry1['entry']['operator']['entry_id']);
        $this->assertEquals('tester', $entry1['entry']['operator']['username']);

        $balance = $redisWallet3->hgetall('cash_fake_balance_7_156');

        $this->assertEquals(20000, $balance['balance']);
        $this->assertEquals(0, $balance['pre_sub']);
        $this->assertEquals(-20000, $balance['pre_add']);
        $this->assertEquals(2, $balance['version']);

        $this->assertEquals(0, $redis->llen('cash_fake_trans'));
        $this->assertEquals(1, $redis->llen('cash_fake_trans_update_queue'));

        $queue = json_decode($redis->rpop('cash_fake_trans_update_queue'), true);

        $this->assertEquals(1, $queue['id']);
        $this->assertEquals(1, $queue['checked']);
        $this->assertEquals(1, $queue['commited']);

        $operator = [
            'entry_id' => 2,
            'username' => ''
        ];
        $entry['id'] = 2;
        $entry['amount'] = -1;
        $entry['operator'] = $operator;

        $redisWallet2->hset('cash_fake_trans', 2, json_encode($entry));
        $redisWallet2->hsetnx('cash_fake_trans_state', 2, 1);

        $entry2 = $fakeOp->transactionCommit(2);

        $this->assertEquals(1, $entry2['cash_fake']['id']);
        $this->assertEquals(7, $entry2['cash_fake']['user_id']);
        $this->assertEquals(1, $entry2['cash_fake']['balance']);
        $this->assertEquals('CNY', $entry2['cash_fake']['currency']);
        $this->assertEquals(1, $entry2['cash_fake']['enable']);
        $this->assertEquals(2, $entry2['entry']['id']);
        $this->assertEquals(1, $entry2['entry']['cash_fake_id']);
        $this->assertEquals(7, $entry2['entry']['user_id']);
        $this->assertEquals('CNY', $entry2['entry']['currency']);
        $this->assertEquals(1001, $entry2['entry']['opcode']);
        $this->assertEquals('2015-01-01T12:00:00+0800', $entry2['entry']['created_at']);
        $this->assertEquals(-1, $entry2['entry']['amount']);
        $this->assertEquals(1, $entry2['entry']['balance']);
        $this->assertEmpty($entry2['entry']['operator']);

        $balance = $redisWallet3->hgetall('cash_fake_balance_7_156');

        $this->assertEquals(10000, $balance['balance']);
        $this->assertEquals(-10000, $balance['pre_sub']);
        $this->assertEquals(-20000, $balance['pre_add']);
        $this->assertEquals(3, $balance['version']);

        $queue = json_decode($redis->rpop('cash_fake_trans_update_queue'), true);

        $this->assertEquals(2, $queue['id']);
        $this->assertEquals(1, $queue['checked']);
        $this->assertEquals(1, $queue['commited']);

        //測試opcode=1098時不更新交易時間
        $operator = [
            'entry_id' => 3,
            'username' => 'tester'
        ];

        $entry['id'] = 3;
        $entry['amount'] = 1;
        $entry['opcode'] = 1098;
        $entry['operator'] = $operator;

        $redisWallet3->hset('cash_fake_trans', 3, json_encode($entry));
        $redisWallet3->hsetnx('cash_fake_trans_state', 3, 1);

        $entry3 = $fakeOp->transactionCommit(3);

        $this->assertEquals(1, $entry3['cash_fake']['id']);
        $this->assertNotContains('last_entry_at', $redis->lpop('cash_fake_balance_queue'));
    }

    /**
     * 測試取消快開交易，但已處理該筆交易記錄
     */
    public function testTransactionRollbackWithAlreadyCheckStatus()
    {
        $this->setExpectedException('RuntimeException', 'Transaction already check status', 150050040);

        $redisWallet = $this->getRedis('wallet1');
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $entry = [
            'id'           => 1,
            'cash_fake_id' => 1,
            'user_id'      => 7,
            'domain'       => 1,
            'currency'     => 156,
            'opcode'       => 1001,
            'amount'       => 1,
            'ref_id'       => 0,
            'operator'     => [],
            'flow'         => [],
            'memo'         => '',
            'created_at'   => date('2015-01-01 12:00:00'),
            'checked_at'   => date('2015-01-01 12:00:00'),
            'checked'      => -1
        ];

        $redisWallet->hset('cash_fake_trans', 1, json_encode($entry));
        $redisWallet->hsetnx('cash_fake_trans_state', 1, 3);

        $fakeOp->transactionRollback(1);
    }

    /**
     * 測試取消快開交易
     */
    public function testTransactionRollback()
    {
        $redis = $this->getRedis();
        $redisWallet = $this->getRedis('wallet1');
        $redisWallet2 = $this->getRedis('wallet2');
        $redisWallet3 = $this->getRedis('wallet3');
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $redisWallet3->hset('cash_fake_balance_7_156', 'balance', 10000);

        $operator = [
            'entry_id' => 1,
            'username' => 'tester'
        ];

        $flow = [
            'whom'         => 'tester',
            'level'        => 1,
            'transfer_out' => 1
        ];

        $entry = [
            'id'           => 1,
            'cash_fake_id' => 1,
            'user_id'      => 7,
            'domain'       => 1,
            'currency'     => 156,
            'opcode'       => 1001,
            'amount'       => 1,
            'ref_id'       => 0,
            'operator'     => $operator,
            'flow'         => $flow,
            'memo'         => '',
            'created_at'   => date('2015-01-01 12:00:00'),
            'checked_at'   => '',
            'checked'      => false
        ];

        $redisWallet->hset('cash_fake_trans', 1, json_encode($entry));
        $redisWallet->hsetnx('cash_fake_trans_state', 1, 1);

        $entry1 = $fakeOp->transactionRollback(1);

        $this->assertEquals(1, $entry1['cash_fake']['id']);
        $this->assertEquals(7, $entry1['cash_fake']['user_id']);
        $this->assertEquals(1, $entry1['cash_fake']['balance']);
        $this->assertEquals('CNY', $entry1['cash_fake']['currency']);
        $this->assertEquals(1, $entry1['cash_fake']['enable']);
        $this->assertEquals(1, $entry1['entry']['id']);
        $this->assertEquals(1, $entry1['entry']['cash_fake_id']);
        $this->assertEquals(7, $entry1['entry']['user_id']);
        $this->assertEquals('CNY', $entry1['entry']['currency']);
        $this->assertEquals(1001, $entry1['entry']['opcode']);
        $this->assertEquals('2015-01-01T12:00:00+0800', $entry1['entry']['created_at']);
        $this->assertEquals(1, $entry1['entry']['amount']);
        $this->assertEquals(1, $entry1['entry']['operator']['entry_id']);
        $this->assertEquals('tester', $entry1['entry']['operator']['username']);

        $balance = $redisWallet3->hgetall('cash_fake_balance_7_156');

        $this->assertEquals(10000, $balance['balance']);
        $this->assertEquals(0, $balance['pre_sub']);
        $this->assertEquals(-10000, $balance['pre_add']);
        $this->assertEquals(2, $balance['version']);

        $this->assertEquals(0, $redis->llen('cash_fake_trans'));
        $this->assertEquals(1, $redis->llen('cash_fake_trans_update_queue'));

        $queue = json_decode($redis->rpop('cash_fake_trans_update_queue'), true);

        $this->assertEquals(1, $queue['id']);
        $this->assertEquals(1, $queue['checked']);
        $this->assertEquals(0, $queue['commited']);

        $operator = [
            'entry_id' => 2,
            'username' => ''
        ];
        $entry['id'] = 2;
        $entry['amount'] = -2;
        $entry['operator'] = $operator;

        $redisWallet2->hset('cash_fake_trans', 2, json_encode($entry));
        $redisWallet2->hsetnx('cash_fake_trans_state', 2, 1);

        $entry2 = $fakeOp->transactionRollback(2);

        $this->assertEquals(1, $entry2['cash_fake']['id']);
        $this->assertEquals(7, $entry2['cash_fake']['user_id']);
        $this->assertEquals(1, $entry2['cash_fake']['balance']);
        $this->assertEquals('CNY', $entry2['cash_fake']['currency']);
        $this->assertEquals(1, $entry2['cash_fake']['enable']);
        $this->assertEquals(2, $entry2['entry']['id']);
        $this->assertEquals(1, $entry2['entry']['cash_fake_id']);
        $this->assertEquals(7, $entry2['entry']['user_id']);
        $this->assertEquals('CNY', $entry2['entry']['currency']);
        $this->assertEquals(1001, $entry2['entry']['opcode']);
        $this->assertEquals('2015-01-01T12:00:00+0800', $entry2['entry']['created_at']);
        $this->assertEquals(-2, $entry2['entry']['amount']);
        $this->assertEmpty($entry2['entry']['operator']);

        $balance = $redisWallet3->hgetall('cash_fake_balance_7_156');

        $this->assertEquals(10000, $balance['balance']);
        $this->assertEquals(-20000, $balance['pre_sub']);
        $this->assertEquals(-10000, $balance['pre_add']);
        $this->assertEquals(3, $balance['version']);

        $this->assertEquals(0, $redis->llen('cash_fake_trans'));
        $this->assertEquals(1, $redis->llen('cash_fake_trans_update_queue'));

        $queue = json_decode($redis->rpop('cash_fake_trans_update_queue'), true);

        $this->assertEquals(2, $queue['id']);
        $this->assertEquals(1, $queue['checked']);
        $this->assertEquals(0, $queue['commited']);
    }

    /**
     * 測試批次確認快開交易
     */
    public function testCashfakeMultiCommit()
    {
        $redis = $this->getRedis();
        $redisWallet = $this->getRedis('wallet1');
        $redisWallet2 = $this->getRedis('wallet2');
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $operator = [
            'entry_id' => 1,
            'username' => 'tester'
        ];

        $flow = [
            'whom'         => 'tester',
            'level'        => 1,
            'transfer_out' => 1
        ];

        $entry = [
            'id'           => 1,
            'cash_fake_id' => 1,
            'user_id'      => 7,
            'domain'       => 1,
            'currency'     => 156,
            'opcode'       => 1001,
            'amount'       => 1,
            'ref_id'       => 0,
            'operator'     => $operator,
            'flow'         => $flow,
            'memo'         => '',
            'created_at'   => date('2015-01-01 12:00:00'),
            'checked_at'   => null,
            'checked'      => false
        ];

        $redisWallet->hset('cash_fake_trans', 1, json_encode($entry));
        $redisWallet->hsetnx('cash_fake_trans_state', 1, 1);

        $entry['id'] = 2;
        $entry['memo'] = 'testMemo';

        $redisWallet2->hset('cash_fake_trans', 2, json_encode($entry));
        $redisWallet2->hsetnx('cash_fake_trans_state', 2, 1);

        $fakeOp->cashfakeMultiCommit([1, 2]);

        $this->assertEquals(0, $redis->llen('cash_fake_trans'));
        $this->assertEquals(2, $redis->llen('cash_fake_trans_update_queue'));

        $queue1 = json_decode($redis->rpop('cash_fake_trans_update_queue'), true);

        $this->assertEquals(1, $queue1['id']);
        $this->assertEquals(1, $queue1['checked']);
        $this->assertEquals(1, $queue1['commited']);

        $queue2 = json_decode($redis->rpop('cash_fake_trans_update_queue'), true);

        $this->assertEquals(2, $queue2['id']);
        $this->assertEquals(1, $queue2['checked']);
        $this->assertEquals(1, $queue2['commited']);
    }

    /**
     * 測試批次取消快開交易
     */
    public function testCashfakeMultiRollback()
    {
        $redis = $this->getRedis();
        $redisWallet = $this->getRedis('wallet1');
        $redisWallet2 = $this->getRedis('wallet2');
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $operator = [
            'entry_id' => 1,
            'username' => 'tester'
        ];

        $flow = [
            'whom'         => 'tester',
            'level'        => 1,
            'transfer_out' => 1
        ];

        $entry = [
            'id'           => 1,
            'cash_fake_id' => 1,
            'user_id'      => 7,
            'domain'       => 1,
            'currency'     => 156,
            'opcode'       => 1001,
            'amount'       => 1,
            'ref_id'       => 0,
            'operator'     => $operator,
            'flow'         => $flow,
            'memo'         => '',
            'created_at'   => date('2015-01-01 12:00:00'),
            'checked_at'   => '',
            'checked'      => false
        ];

        $redisWallet->hset('cash_fake_trans', 1, json_encode($entry));
        $redisWallet->hsetnx('cash_fake_trans_state', 1, 1);

        $entry['id'] = 2;
        $entry['memo'] = 'testMemo';

        $redisWallet2->hset('cash_fake_trans', 2, json_encode($entry));
        $redisWallet2->hsetnx('cash_fake_trans_state', 2, 1);

        $fakeOp->cashfakeMultiRollback([1, 2]);

        $this->assertEquals(0, $redis->llen('cash_fake_trans'));
        $this->assertEquals(2, $redis->llen('cash_fake_trans_update_queue'));

        $queue1 = json_decode($redis->rpop('cash_fake_trans_update_queue'), true);

        $this->assertEquals(1, $queue1['id']);
        $this->assertEquals(1, $queue1['checked']);
        $this->assertEquals(0, $queue1['commited']);

        $queue2 = json_decode($redis->rpop('cash_fake_trans_update_queue'), true);

        $this->assertEquals(2, $queue2['id']);
        $this->assertEquals(1, $queue2['checked']);
        $this->assertEquals(0, $queue2['commited']);
    }

    /**
     * 測試批次下注，但refId不合法
     */
    public function testBunchOperationWithInvalidRefId()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid ref_id', 150050022);

        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $options = [
            'cash_fake_id' => 2,
            'currency'     => 156,
            'opcode'       => 1001,
            'amount'       => 1,
            'operator'     => ''
        ];

        $orders[] = [
            'am'  => 1,
            'ref' => -1
        ];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->bunchOperation($user, $options, $orders);
    }

    /**
     * 測試批次下注，但沒有傳交易代碼
     */
    public function testBunchOperationWithoutOpcode()
    {
        $this->setExpectedException('InvalidArgumentException', 'No opcode specified', 150050017);

        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $options = [
            'cash_fake_id' => 2,
            'currency'     => 156,
            'opcode'       => null,
            'amount'       => 1,
            'operator'     => ''
        ];

        $orders[] = ['am' => 1];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->bunchOperation($user, $options, $orders);
    }

    /**
     * 測試批次下注，但交易代碼不合法
     */
    public function testBunchOperationWithInvalidOpcode()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid opcode', 150050021);

        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $options = [
            'cash_fake_id' => 2,
            'currency'     => 156,
            'opcode'       => 'not an opcode',
            'amount'       => 1,
            'operator'     => ''
        ];

        $orders[] = ['am' => 1];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->bunchOperation($user, $options, $orders);
    }

    /**
     * 測試批次下注，但沒有傳交易金額
     */
    public function testBunchOperationWithoutAmount()
    {
        $this->setExpectedException('InvalidArgumentException', 'No amount specified', 150050016);

        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $options = [
            'cash_fake_id' => 2,
            'currency'     => 156,
            'opcode'       => 1001,
            'amount'       => null,
            'operator'     => ''
        ];

        $orders[] = ['am' => null];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->bunchOperation($user, $options, $orders);
    }

    /**
     * 測試批次下注，但交易金額超過範圍最大值
     */
    public function testBunchOperationWithOversizeAmount()
    {
        $this->setExpectedException('RangeException', 'Oversize amount given which exceeds the MAX', 150050028);

        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $options = [
            'cash_fake_id' => 2,
            'currency'     => 156,
            'opcode'       => 1001,
            'amount'       => 100000000000,
            'operator'     => ''
        ];

        $orders[] = ['am' => 100000000000];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->bunchOperation($user, $options, $orders);
    }

    /**
     * 測試批次下注，但強制扣款交易金額為0
     */
    public function testBunchOperationWithAmountZero()
    {
        $this->setExpectedException('InvalidArgumentException', 'Amount can not be zero', 150050027);

        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $options = [
            'cash_fake_id' => 2,
            'currency'     => 156,
            'opcode'       => 1001,
            'amount'       => 0,
            'operator'     => ''
        ];

        $orders[] = ['am' => 0];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->bunchOperation($user, $options, $orders);
    }

    /**
     * 測試批次下注，但快開額度已停用
     */
    public function testBunchOperationWithCashFakeIsDisabled()
    {
        $this->setExpectedException('RuntimeException', 'CashFake is disabled', 150050007);

        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $repo = $em->getRepository('BBDurianBundle:CashFake');
        $cashFake = $repo->findOneBy(['user' => 8, 'currency' => 156]);
        $cashFake->disable();

        $options = [
            'cash_fake_id' => 2,
            'currency'     => 156,
            'opcode'       => 10002,
            'amount'       => 1,
            'operator'     => ''
        ];

        $orders[] = ['am' => 1];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->bunchOperation($user, $options, $orders);
    }

    /**
     * 測試批次下注，但使用者已停權
     */
    public function testBunchOperationWithUserIsBankrupt()
    {
        $this->setExpectedException('RuntimeException', 'User is bankrupt', 150050036);

        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $options = [
            'cash_fake_id' => 2,
            'currency'     => 156,
            'opcode'       => 10002,
            'amount'       => 1,
            'operator'     => ''
        ];

        $orders[] = ['am' => 1];

        $user = $em->find('BBDurianBundle:User', 8);
        $user->setBankrupt(1);

        $fakeOp->bunchOperation($user, $options, $orders);
    }

    /**
     * 測試批次下注，但餘額超過PHP整數最大值
     */
    public function testBunchOperationWithBalanceExceedsMaxInteger()
    {
        $this->setExpectedException('RangeException', 'Balance exceeds allowed MAX integer', 150050037);

        $redisWallet = $this->getRedis('wallet4');
        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        // 修改 Redis balance 的值，直接指定 PHP_INT_MAX 會超過 Redis 的限制，故先減去交易金額
        $redisWallet->hset('cash_fake_balance_8_156', 'balance', PHP_INT_MAX - CashFake::MAX_BALANCE * $this->plusNumber);

        $options = [
            'cash_fake_id' => 2,
            'currency'     => 156,
            'opcode'       => 1001,
            'amount'       => CashFake::MAX_BALANCE,
            'operator'     => ''
        ];

        $orders[] = ['am' => CashFake::MAX_BALANCE];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->bunchOperation($user, $options, $orders);
    }

    /**
     * 測試批次下注，但餘額超過交易金額最大值
     */
    public function testBunchOperationWithBalanceExceedsTheMax()
    {
        $this->setExpectedException('RangeException', 'The balance exceeds the MAX amount', 150050030);

        $redisWallet = $this->getRedis('wallet4');
        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        // 修改 Redis balance 的值為交易金額上限
        $redisWallet->hset('cash_fake_balance_8_156', 'balance', CashFake::MAX_BALANCE * $this->plusNumber);

        $options = [
            'cash_fake_id' => 2,
            'currency'     => 156,
            'opcode'       => 1001,
            'amount'       => 1,
            'operator'     => ''
        ];

        $orders[] = ['am' => 1];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->bunchOperation($user, $options, $orders);
    }

    /**
     * 測試批次下注，但餘額不足
     */
    public function testBunchOperationWithNotEnoughBalance()
    {
        $this->setExpectedException('RuntimeException', 'Not enough balance', 150050031);

        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $options = [
            'cash_fake_id' => 2,
            'currency'     => 156,
            'opcode'       => 1042,
            'amount'       => -5000,
            'operator'     => ''
        ];

        $orders[] = [
            'am'  => -5000,
            'ref' => 1
        ];

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->bunchOperation($user, $options, $orders);
    }

    /**
     * 測試批次下注
     */
    public function testBunchOperationWithConfirm()
    {
        $redis = $this->getRedis();
        $redisWallet = $this->getRedis('wallet4');
        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $options = [
            'cash_fake_id' => 2,
            'currency'     => 156,
            'opcode'       => 1001,
            'amount'       => 3,
            'operator'     => 'tester',
            'force'        => true
        ];

        $orders[0] = [
            'am'   => 1,
            'memo' => 'test',
            'ref'  => 1
        ];

        $orders[1] = [
            'am'   => 2,
            'memo' => 'test',
            'ref'  => 1
        ];

        $user = $em->find('BBDurianBundle:User', 8);

        $result = $fakeOp->bunchOperation($user, $options, $orders);
        $at = (new \DateTime($result['entry'][0]['created_at']))->format('YmdHis');

        $this->assertEquals(2, $result['cash_fake']['id']);
        $this->assertEquals(8, $result['cash_fake']['user_id']);
        $this->assertEquals(3, $result['cash_fake']['balance']);
        $this->assertEquals(0, $result['cash_fake']['pre_sub']);
        $this->assertEquals(0, $result['cash_fake']['pre_add']);
        $this->assertEquals('CNY', $result['cash_fake']['currency']);
        $this->assertEquals(1, $result['cash_fake']['enable']);
        $this->assertEquals(1001, $result['entry'][0]['id']);
        $this->assertEquals(1, $result['entry'][0]['amount']);
        $this->assertEquals(1001, $result['entry'][0]['operator']['entry_id']);
        $this->assertEquals('tester', $result['entry'][0]['operator']['username']);
        $this->assertEquals(1002, $result['entry'][1]['id']);
        $this->assertEquals(2, $result['entry'][1]['amount']);
        $this->assertEquals(1002, $result['entry'][1]['operator']['entry_id']);
        $this->assertEquals('tester', $result['entry'][1]['operator']['username']);

        $balance = $redisWallet->hgetall('cash_fake_balance_8_156');

        $this->assertEquals(30000, $balance['balance']);
        $this->assertEquals(0, $balance['pre_sub']);
        $this->assertEquals(0, $balance['pre_add']);
        $this->assertEquals(3, $balance['version']);

        $fakeOp->bunchConfirm();

        $balanceQueue = '{"id":2,"user_id":8,"balance":3,"pre_sub":0,"pre_add":0,"version":3,' .
            '"currency":156,"enable":true,"last_entry_at":"' . $at . '"}';
        $this->assertEquals($balanceQueue, $redis->rpop('cash_fake_balance_queue'));
        $this->assertEquals(2, $redis->llen('cash_fake_entry_queue'));
        $this->assertEquals(2, $redis->llen('cash_fake_transfer_queue'));
        $this->assertEquals(2, $redis->llen('cash_fake_operator_queue'));

        //測試opcode=1098時不更新交易時間
        $options = [
            'cash_fake_id' => 2,
            'currency'     => 156,
            'opcode'       => 1098,
            'amount'       => 3,
            'operator'     => 'tester',
            'force'        => true
        ];

        $result = $fakeOp->bunchOperation($user, $options, $orders);

        $this->assertEquals(2, $result['cash_fake']['id']);

        $fakeOp->bunchConfirm();

        $this->assertNotContains('last_entry_at', $redis->rpop('cash_fake_balance_queue'));
    }

    /**
     * 測試取消批次下注
     */
    public function testBunchOperationWithRollback()
    {
        $redis = $this->getRedis();
        $redisWallet = $this->getRedis('wallet4');
        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $options = [
            'cash_fake_id' => 2,
            'currency'     => 156,
            'opcode'       => 1001,
            'amount'       => 3,
            'operator'     => 'tester',
            'force'        => true
        ];

        $orders[0] = ['am' => 1];
        $orders[1] = ['am' => 2];

        $user = $em->find('BBDurianBundle:User', 8);

        $result = $fakeOp->bunchOperation($user, $options, $orders);

        $this->assertEquals(2, $result['cash_fake']['id']);
        $this->assertEquals(8, $result['cash_fake']['user_id']);
        $this->assertEquals(3, $result['cash_fake']['balance']);
        $this->assertEquals(0, $result['cash_fake']['pre_sub']);
        $this->assertEquals(0, $result['cash_fake']['pre_add']);
        $this->assertEquals('CNY', $result['cash_fake']['currency']);
        $this->assertEquals(1, $result['cash_fake']['enable']);
        $this->assertEquals(1001, $result['entry'][0]['id']);
        $this->assertEquals(1, $result['entry'][0]['amount']);
        $this->assertEquals(1001, $result['entry'][0]['operator']['entry_id']);
        $this->assertEquals('tester', $result['entry'][0]['operator']['username']);
        $this->assertEquals(1002, $result['entry'][1]['id']);
        $this->assertEquals(2, $result['entry'][1]['amount']);
        $this->assertEquals(1002, $result['entry'][1]['operator']['entry_id']);
        $this->assertEquals('tester', $result['entry'][1]['operator']['username']);

        $balance = $redisWallet->hgetall('cash_fake_balance_8_156');

        $this->assertEquals(30000, $balance['balance']);
        $this->assertEquals(0, $balance['pre_sub']);
        $this->assertEquals(0, $balance['pre_add']);
        $this->assertEquals(3, $balance['version']);

        $fakeOp->bunchRollback();

        $balance = $redisWallet->hgetall('cash_fake_balance_8_156');

        $this->assertEquals(0, $balance['balance']);
        $this->assertEquals(0, $balance['pre_sub']);
        $this->assertEquals(0, $balance['pre_add']);
        $this->assertEquals(4, $balance['version']);

        $balanceQueue = '{"user_id":8,"balance":0,"pre_sub":0,"pre_add":0,"version":4,"currency":156}';
        $this->assertEquals($balanceQueue, $redis->rpop('cash_fake_balance_queue'));
    }

    /**
     * 測試新增快開額度，但交易金額超過範圍最大值
     */
    public function testNewCashFakeWithOversizeAmount()
    {
        $this->setExpectedException('RangeException', 'Oversize amount given which exceeds the MAX', 150050028);

        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $repo = $em->getRepository('BBDurianBundle:CashFake');
        $cashFake = $repo->findOneBy(['user' => 7, 'currency' => 156]);

        $fakeOp->newCashFake($cashFake, 100000000000, '');
    }

    /**
     * 測試新增快開額度，但餘額為0
     */
    public function testNewCashFakeWithBalanceZero()
    {
        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $repo = $em->getRepository('BBDurianBundle:CashFake');
        $cashFake = $repo->findOneBy(['user' => 8, 'currency' => 156]);

        $result = $fakeOp->newCashFake($cashFake, 0, '');

        $this->assertNull($result['parent_entry']);
        $this->assertNull($result['entry']);
    }

    /**
     * 測試新增快開額度，但無上層
     */
    public function testNewCashFakeWithoutParent()
    {
        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $user = $em->find('BBDurianBundle:User', 9);

        $currency = 156;

        $cashFake = new CashFake($user, $currency);
        $em->persist($cashFake);
        $em->flush();

        $result = $fakeOp->newCashFake($cashFake, 1, '');

        $this->assertNull($result['parent_entry']);
        $this->assertEquals(1001, $result['entry'][0]['id']);
        $this->assertEquals(3, $result['entry'][0]['cash_fake_id']);
        $this->assertEquals(9, $result['entry'][0]['user_id']);
        $this->assertEquals('CNY', $result['entry'][0]['currency']);
        $this->assertEquals(1020, $result['entry'][0]['opcode']);
        $this->assertEquals(1, $result['entry'][0]['amount']);
    }

    /**
     * 測試新增快開額度
     */
    public function testNewCashFake()
    {
        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $repo = $em->getRepository('BBDurianBundle:CashFake');
        $cashFake7 = $repo->findOneBy(['user' => 7, 'currency' => 156]);
        $cashFake7->setBalance(100);

        $cashFake8 = $repo->findOneBy(['user' => 8, 'currency' => 156]);

        $result = $fakeOp->newCashFake($cashFake8, 1, '');

        $this->assertEquals(1001, $result['parent_entry'][0]['id']);
        $this->assertEquals(1, $result['parent_entry'][0]['cash_fake_id']);
        $this->assertEquals(7, $result['parent_entry'][0]['user_id']);
        $this->assertEquals('CNY', $result['parent_entry'][0]['currency']);
        $this->assertEquals(1003, $result['parent_entry'][0]['opcode']);
        $this->assertEquals(-1, $result['parent_entry'][0]['amount']);
        $this->assertEquals(1002, $result['entry'][0]['id']);
        $this->assertEquals(2, $result['entry'][0]['cash_fake_id']);
        $this->assertEquals(8, $result['entry'][0]['user_id']);
        $this->assertEquals('CNY', $result['entry'][0]['currency']);
        $this->assertEquals(1003, $result['entry'][0]['opcode']);
        $this->assertEquals(1, $result['entry'][0]['amount']);
    }

    /**
     * 測試修改快開額度，但快開額度不存在
     */
    public function testEditCashFakeWithoutCashFake()
    {
        $this->setExpectedException('RuntimeException', 'No cashFake found', 150050001);

        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $user = new User();

        $fakeOp->editCashFake($user, 1, '');
    }

    /**
     * 測試修改快開額度，但交易金額超過範圍最大值
     */
    public function testEditCashFakeWithOversizeAmount()
    {
        $this->setExpectedException('RuntimeException', 'Oversize amount given which exceeds the MAX', 150050028);

        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $repo = $em->getRepository('BBDurianBundle:CashFake');
        $cashFake = $repo->findOneBy(['user' => 8, 'currency' => 156]);
        $cashFake->setBalance(100000000000);

        $user = $em->find('BBDurianBundle:User', 8);

        $fakeOp->editCashFake($user, 1, '');
    }

    /**
     * 測試修改快開額度，但無上層
     */
    public function testEditCashFakeWithoutParent()
    {
        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $user = $em->find('BBDurianBundle:User', 9);

        $currency = 156;

        $cashFake = new CashFake($user, $currency);
        $em->persist($cashFake);
        $em->flush();

        $result = $fakeOp->editCashFake($user, 1, '');

        $this->assertEquals(1001, $result['entry'][0]['id']);
        $this->assertEquals(3, $result['entry'][0]['cash_fake_id']);
        $this->assertEquals(9, $result['entry'][0]['user_id']);
        $this->assertEquals('CNY', $result['entry'][0]['currency']);
        $this->assertEquals(1020, $result['entry'][0]['opcode']);
        $this->assertEquals(1, $result['entry'][0]['amount']);
    }

    /**
     * 測試修改快開額度，但無交易金額
     */
    public function testEditCashFakeWithoutAmount()
    {
        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $repo = $em->getRepository('BBDurianBundle:CashFake');
        $cashFake = $repo->findOneBy(['user' => 8, 'currency' => 156]);
        $cashFake->setBalance(1);

        $user = $em->find('BBDurianBundle:User', 8);

        $result = $fakeOp->editCashFake($user, 1, '');

        $this->assertNull($result['entry']);
    }

    /**
     * 測試修改快開額度
     */
    public function testEditCashFake()
    {
        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $repo = $em->getRepository('BBDurianBundle:CashFake');
        $cashFake = $repo->findOneBy(['user' => 8, 'currency' => 156]);
        $cashFake->setBalance(100);

        $user = $em->find('BBDurianBundle:User', 8);

        $result = $fakeOp->editCashFake($user, 1, '');

        $this->assertEquals(1001, $result['entry'][1]['id']);
        $this->assertEquals(1, $result['entry'][1]['cash_fake_id']);
        $this->assertEquals(7, $result['entry'][1]['user_id']);
        $this->assertEquals('CNY', $result['entry'][1]['currency']);
        $this->assertEquals(1003, $result['entry'][1]['opcode']);
        $this->assertEquals(99, $result['entry'][1]['amount']);
        $this->assertEquals(1002, $result['entry'][0]['id']);
        $this->assertEquals(2, $result['entry'][0]['cash_fake_id']);
        $this->assertEquals(8, $result['entry'][0]['user_id']);
        $this->assertEquals('CNY', $result['entry'][0]['currency']);
        $this->assertEquals(1003, $result['entry'][0]['opcode']);
        $this->assertEquals(-99, $result['entry'][0]['amount']);
    }

    /**
     * 測試已啟用使用者快開額度
     */
    public function testAlreadyEnable()
    {
        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $repo = $em->getRepository('BBDurianBundle:CashFake');
        $cashFake = $repo->findOneBy(['user' => 7, 'currency' => 156]);

        $fakeOp->enable($cashFake);

        $this->assertTrue($cashFake->isEnable());
    }

    /**
     * 測試啟用使用者快開額度但無 Redis key
     */
    public function testEnableButRedisKeyNotExists()
    {
        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $repo = $em->getRepository('BBDurianBundle:CashFake');
        $cashFake = $repo->findOneBy(['user' => 7, 'currency' => 156]);
        $cashFake->disable();

        $fakeOp->enable($cashFake);

        $this->assertTrue($cashFake->isEnable());
    }

    /**
     * 測試啟用使用者快開額度
     */
    public function testEnable()
    {
        $redisWallet = $this->getRedis('wallet3');
        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $redisWallet->hset('cash_fake_balance_7_156', 'enable', 0);

        $repo = $em->getRepository('BBDurianBundle:CashFake');
        $cashFake = $repo->findOneBy(['user' => 7, 'currency' => 156]);
        $cashFake->disable();

        $fakeOp->enable($cashFake);

        $this->assertTrue($cashFake->isEnable());
        $this->assertEquals(1, $redisWallet->hget('cash_fake_balance_7_156', 'enable'));
    }

    /**
     * 測試已停用使用者快開額度
     */
    public function testAlreadyDisable()
    {
        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $repo = $em->getRepository('BBDurianBundle:CashFake');
        $cashFake = $repo->findOneBy(['user' => 7, 'currency' => 156]);
        $cashFake->disable();

        $fakeOp->disable($cashFake);

        $this->assertFalse($cashFake->isEnable());
    }

    /**
     * 測試停用使用者快開額度但無 Redis key
     */
    public function testDisableButRedisKeyNotExists()
    {
        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $repo = $em->getRepository('BBDurianBundle:CashFake');
        $cashFake = $repo->findOneBy(['user' => 7, 'currency' => 156]);

        $fakeOp->disable($cashFake);

        $this->assertFalse($cashFake->isEnable());
    }

    /**
     * 測試停用使用者快開額度
     */
    public function testDisable()
    {
        $redisWallet = $this->getRedis('wallet3');
        $em = $this->getEntityManager();
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $redisWallet->hset('cash_fake_balance_7_156', 'enable', 1);

        $repo = $em->getRepository('BBDurianBundle:CashFake');
        $cashFake = $repo->findOneBy(['user' => 7, 'currency' => 156]);

        $fakeOp->disable($cashFake);

        $this->assertFalse($cashFake->isEnable());
        $this->assertEquals(0, $redisWallet->hget('cash_fake_balance_7_156', 'enable'));
    }

    /**
     * 測試清掉使用者快開額度資料
     */
    public function testClearUserCashFakeData()
    {
        $pRedisWallet = $this->getRedis('wallet3');
        $redisWallet = $this->getRedis('wallet4');
        $fakeOp = $this->getContainer()->get('durian.cashfake_op');

        $redisWallet->hset('cash_fake_balance_8_156', 'balance', 100);
        $redisWallet->hset('cash_fake_balance_8_156', 'pre_sub', 0);
        $redisWallet->hset('cash_fake_balance_8_156', 'pre_add', 0);

        $fakeOp->clearUserCashFakeData(8, 156);

        $this->assertEquals(0, $pRedisWallet->exists('cash_fake_balance_7_156'));
    }
}
