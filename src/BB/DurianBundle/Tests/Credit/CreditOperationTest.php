<?php

namespace BB\DurianBundle\Tests\Credit;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\CreditPeriod;

class CreditOperationTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCreditData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCreditPeriodData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCreditEntryData'
        ];

        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadExchangeData'
        ];

        $this->loadFixtures($classnames, 'share');
    }

    /**
     * 測試額度交易操作
     */
    public function testOperation()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $creditOp = $this->getContainer()->get('durian.credit_op');
        $cron = \Cron\CronExpression::factory('@daily');
        $now = new \DateTime;
        $exNow = $cron->getPreviousRunDate($now, 0, true);

        $options = [
            'group_num' => 2,
            'amount' => -100,
            'opcode' => 40000,
            'refId' => 0,
            'memo' => '',
            'force' => false,
            'at' => $now
        ];
        $creditInfo = $creditOp->operation(8, $options);

        $this->assertEquals(6, $creditInfo['id']);
        $this->assertEquals(8, $creditInfo['user_id']);
        $this->assertEquals(2, $creditInfo['group']);
        $this->assertEquals(3000, $creditInfo['line']);
        $this->assertEquals(2900, $creditInfo['balance']);
        $this->assertEquals($exNow->format('Y-m-d H:i:s'), $creditInfo['period']);

        $cmdParams = [
            '--entry' => 1,
            '--period' => 1
        ];
        $this->runCommand('durian:sync-credit', $cmdParams);

        $cp = $em->find('BBDurianBundle:CreditPeriod', 3);
        $this->assertEquals(100, $cp->getAmount());

        $result = $em->getRepository('BBDurianBundle:CreditEntry')
            ->findOneBy(['userId' => 8, 'groupNum' => 2]);

        $ce = $result->toArray();

        $this->assertEquals(2, $ce['id']);
        $this->assertEquals(6, $ce['credit_id']);
        $this->assertEquals(-100, $ce['amount']);
        $this->assertEquals(2900, $ce['balance']);
        $this->assertEquals(3000, $ce['line']);
    }

    /**
     * 測試額度交易操作，但沒有傳交易代碼
     */
    public function testOperationWithNoOpcodeSpecified()
    {
        $this->setExpectedException('InvalidArgumentException', 'No opcode specified', 150060032);

        $creditOp = $this->getContainer()->get('durian.credit_op');

        $options = [
            'group_num' => 2,
            'amount' => -100,
            'opcode' => null,
            'refId' => 0,
            'memo' => '',
            'force' => false,
            'at' => new \DateTime
        ];
        $creditOp->operation(8, $options);
    }

    /**
     * 測試額度交易操作，但交易代碼不合法
     */
    public function testOperationWithInvalidOpcode()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid opcode', 150060029);

        $creditOp = $this->getContainer()->get('durian.credit_op');

        $options = [
            'group_num' => 2,
            'amount' => -100,
            'opcode' => 'nxxxxx',
            'refId' => 0,
            'memo' => '',
            'force' => false,
            'at' => new \DateTime
        ];
        $creditOp->operation(8, $options);
    }

    /**
     * 測試額度交易操作，但沒有傳使用額度
     */
    public function testOperationWithNoAmountSpecified()
    {
        $this->setExpectedException('InvalidArgumentException', 'No amount specified', 150060033);

        $creditOp = $this->getContainer()->get('durian.credit_op');

        $options = [
            'group_num' => 2,
            'amount' => null,
            'opcode' => 5001,
            'refId' => 0,
            'memo' => '',
            'force' => false,
            'at' => new \DateTime
        ];
        $creditOp->operation(8, $options);
    }

    /**
     * 測試額度交易操作，但交易金額超過範圍最大值
     */
    public function testOperationWithAmountExceedsTheMax()
    {
        $this->setExpectedException('RangeException', 'Oversize amount given which exceeds the MAX', 150060042);

        $creditOp = $this->getContainer()->get('durian.credit_op');

        $options = [
            'group_num' => 2,
            'amount' => CreditPeriod::AMOUNT_MAX + 1,
            'opcode' => 5001,
            'refId' => 0,
            'memo' => '',
            'force' => false,
            'at' => new \DateTime
        ];
        $creditOp->operation(8, $options);
    }

    /**
     * 測試額度交易操作，但使用額度不能為零
     */
    public function testOperationWithAmountCannotBeZero()
    {
        $this->setExpectedException('InvalidArgumentException', 'Amount can not be zero', 150060036);

        $creditOp = $this->getContainer()->get('durian.credit_op');

        $options = [
            'group_num' => 2,
            'amount' => 0,
            'opcode' => 5001,
            'refId' => 0,
            'memo' => '',
            'force' => false,
            'at' => new \DateTime
        ];
        $creditOp->operation(8, $options);
    }

    /**
     * 測試額度交易操作，但累積金額小於零
     */
    public function testOperationWithNewAmountIsNegative()
    {
        $this->setExpectedException('RuntimeException', 'Amount of period can not be negative', 150060008);

        $creditOp = $this->getContainer()->get('durian.credit_op');

        $options = [
            'group_num' => 2,
            'amount' => 1000,
            'opcode' => 5001,
            'refId' => 0,
            'memo' => '',
            'force' => false,
            'at' => new \DateTime
        ];
        $creditOp->operation(8, $options);
    }

    /**
     * 測試額度交易操作，但餘額不足
     */
    public function testOperationWithNoEnoughBalance()
    {
        $this->setExpectedException('RuntimeException', 'Not enough balance', 150060034);

        $creditOp = $this->getContainer()->get('durian.credit_op');

        $options = [
            'group_num' => 2,
            'amount' => -5000,
            'opcode' => 5001,
            'refId' => 0,
            'memo' => '',
            'force' => false,
            'at' => new \DateTime
        ];
        $creditOp->operation(8, $options);
    }

    /**
     * 測試批次下單
     */
    public function testBunchOperation()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $creditOp = $this->getContainer()->get('durian.credit_op');
        $cron = \Cron\CronExpression::factory('@daily');
        $now = new \DateTime;
        $exNow = $cron->getPreviousRunDate($now, 0, true);

        $options = [
            'opcode' => 1001,
            'group_num' => 1,
            'amount' => -600,
            'at' => $now,
            'force' => false
        ];

        $orders = [];
        $orders[] = [
            'amount' => -200,
            'ref_id' => 0,
            'memo' => 'od1'
        ];
        $orders[] = [
            'amount' => -200,
            'ref_id' => 0,
            'memo' => 'od2'
        ];
        $orders[] = [
            'amount' => -200,
            'ref_id' => 0,
            'memo' => 'od3'
        ];

        $creditInfo = $creditOp->bunchOperation(8, $options, $orders);

        $this->assertEquals(5, $creditInfo['id']);
        $this->assertEquals(8, $creditInfo['user_id']);
        $this->assertEquals(1, $creditInfo['group']);
        $this->assertEquals(5000, $creditInfo['line']);
        $this->assertEquals(4400, $creditInfo['balance']);
        $this->assertEquals($exNow->format('Y-m-d H:i:s'), $creditInfo['period']);

        $creditOp->bunchConfirm();

        $cmdParams = [
            '--entry' => 1,
            '--period' => 1,
            '--credit' => 1
        ];
        $this->runCommand('durian:sync-credit', $cmdParams);

        $cp = $em->find('BBDurianBundle:CreditPeriod', 3);
        $this->assertEquals(600, $cp->getAmount());

        $result = $em->getRepository('BBDurianBundle:CreditEntry')
            ->findOneBy(['userId' => 8, 'groupNum' => 1]);

        $ce = $result->toArray();

        $this->assertEquals(2, $ce['id']);
        $this->assertEquals(5, $ce['credit_id']);
        $this->assertEquals(-200, $ce['amount']);
        $this->assertEquals(4800, $ce['balance']);
        $this->assertEquals(5000, $ce['line']);
    }

    /**
     * 測試批次下單，但訂單數量為零
     */
    public function testBunchOperationWithOrderCountIsZero()
    {
        $creditOp = $this->getContainer()->get('durian.credit_op');

        $options = [
            'opcode' => 1001,
            'group_num' => 1,
            'amount' => -600,
            'at' => new \DateTime,
            'force' => false
        ];

        $orders = [];

        $ret = $creditOp->bunchOperation(8, $options, $orders);
        $this->assertNull($ret);
    }

    /**
     * 測試批次下單，但沒有傳群組編號
     */
    public function testBunchOperationWithNoGroupNumSpecified()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No group_num specified',
            150060025
        );

        $creditOp = $this->getContainer()->get('durian.credit_op');

        $options = [
            'opcode' => 1001,
            'group_num' => null,
            'amount' => -200,
            'at' => new \DateTime,
            'force' => false
        ];

        $orders = [];
        $orders[] = [
            'amount' => -200,
            'ref_id' => 0,
            'memo' => ''
        ];

        $creditOp->bunchOperation(8, $options, $orders);
    }

    /**
     * 測試批次下單，但群組編號不是數字
     */
    public function testBunchOperationWithInvalidGroupNumber()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid group number', 150060011);

        $creditOp = $this->getContainer()->get('durian.credit_op');

        $options = [
            'opcode' => 1001,
            'group_num' => 'n',
            'amount' => -200,
            'at' => new \DateTime,
            'force' => false
        ];

        $orders = [];
        $orders[] = [
            'amount' => -200,
            'ref_id' => 0,
            'memo' => ''
        ];

        $creditOp->bunchOperation(8, $options, $orders);
    }

    /**
     * 測試批次下單，但沒有傳交易代碼
     */
    public function testBunchOperationWithNoOpcodeSpecified()
    {
        $this->setExpectedException('InvalidArgumentException', 'No opcode specified', 150060032);

        $creditOp = $this->getContainer()->get('durian.credit_op');

        $options = [
            'opcode' => null,
            'group_num' => 1,
            'amount' => -200,
            'at' => new \DateTime,
            'force' => false
        ];

        $orders = [];
        $orders[] = [
            'amount' => -200,
            'ref_id' => 0,
            'memo' => ''
        ];

        $creditOp->bunchOperation(8, $options, $orders);
    }

    /**
     * 測試批次下單，但交易代碼不合法
     */
    public function testBunchOperationWithInvalidOpcode()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid opcode', 150060029);

        $creditOp = $this->getContainer()->get('durian.credit_op');

        $options = [
            'opcode' => 12312312312321,
            'group_num' => 1,
            'amount' => -200,
            'at' => new \DateTime,
            'force' => false
        ];

        $orders = [];
        $orders[] = [
            'amount' => -200,
            'ref_id' => 0,
            'memo' => ''
        ];

        $creditOp->bunchOperation(8, $options, $orders);
    }

    /**
     * 測試批次下單，但沒有傳使用額度
     */
    public function testBunchOperationWithNoAmountSpecified()
    {
        $this->setExpectedException('InvalidArgumentException', 'No amount specified', 150060033);

        $creditOp = $this->getContainer()->get('durian.credit_op');

        $options = [
            'opcode' => 1001,
            'group_num' => 1,
            'amount' => null,
            'at' => new \DateTime,
            'force' => false
        ];

        $orders = [];
        $orders[] = [
            'amount' => -200,
            'ref_id' => 0,
            'memo' => ''
        ];

        $creditOp->bunchOperation(8, $options, $orders);
    }

    /**
     * 測試批次下單，但交易金額超過範圍最大值
     */
    public function testBunchOperationWithAmountExceedsTheMax()
    {
        $this->setExpectedException('RangeException', 'Oversize amount given which exceeds the MAX', 150060042);

        $creditOp = $this->getContainer()->get('durian.credit_op');

        $options = [
            'opcode' => 1001,
            'group_num' => 1,
            'amount' => CreditPeriod::AMOUNT_MAX + 1,
            'at' => new \DateTime,
            'force' => false
        ];

        $orders = [];
        $orders[] = [
            'amount' => CreditPeriod::AMOUNT_MAX + 1,
            'ref_id' => 0,
            'memo' => ''
        ];

        $creditOp->bunchOperation(8, $options, $orders);
    }

    /**
     * 測試批次下單，但備查編號不合法
     */
    public function testBunchOperationWithInvalidRefId()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid ref_id', 150060031);

        $creditOp = $this->getContainer()->get('durian.credit_op');

        $options = [
            'opcode' => 1001,
            'group_num' => 1,
            'amount' => -200,
            'at' => new \DateTime,
            'force' => false
        ];

        $orders = [];
        $orders[] = [
            'amount' => -200,
            'ref_id' => 'n',
            'memo' => ''
        ];

        $creditOp->bunchOperation(8, $options, $orders);
    }

    /**
     * 測試批次下單，但訂單內使用額度非數字
     */
    public function testBunchOperationWithAmountIsNotNumberInOrders()
    {
        $this->setExpectedException('InvalidArgumentException', 'Amount must be numeric', 150060035);

        $creditOp = $this->getContainer()->get('durian.credit_op');

        $options = [
            'opcode' => 1001,
            'group_num' => 1,
            'amount' => -200,
            'at' => new \DateTime,
            'force' => false
        ];

        $orders = [];
        $orders[] = [
            'amount' => 'ddd',
            'ref_id' => 0,
            'memo' => ''
        ];

        $creditOp->bunchOperation(8, $options, $orders);
    }

    /**
     * 測試批次下單，但使用額度為零
     */
    public function testBunchOperationWithAmounIsZero()
    {
        $this->setExpectedException('InvalidArgumentException', 'Amount can not be zero', 150060036);

        $creditOp = $this->getContainer()->get('durian.credit_op');

        $options = [
            'opcode' => 1001,
            'group_num' => 1,
            'amount' => 0,
            'at' => new \DateTime,
            'force' => false
        ];

        $orders = [];
        $orders[] = [
            'amount' => 0,
            'ref_id' => 0,
            'memo' => ''
        ];

        $creditOp->bunchOperation(8, $options, $orders);
    }

    /**
     * 測試批次下單，但信用額度已停用
     */
    public function testBunchOperationWithCreditIsDisabled()
    {
        $this->setExpectedException('RuntimeException', 'Credit is disabled', 150060012);

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $credit = $em->find('BBDurianBundle:Credit', 5);
        $credit->disable();
        $em->flush();

        $creditOp = $this->getContainer()->get('durian.credit_op');

        $options = [
            'opcode' => 1002,
            'group_num' => 1,
            'amount' => -200,
            'at' => new \DateTime,
            'force' => false
        ];

        $orders = [];
        $orders[] = [
            'amount' => -200,
            'ref_id' => 0,
            'memo' => ''
        ];

        $creditOp->bunchOperation(8, $options, $orders);
    }

    /**
     * 測試批次下單，但操作過期的累積金額
     */
    public function testBunchOperationWithExpiredCreditPeriodData()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Illegal operation for expired credit period data',
            150060022
        );

        $creditOp = $this->getContainer()->get('durian.credit_op');

        $options = [
            'opcode' => 1001,
            'group_num' => 1,
            'amount' => -200,
            'at' => new \DateTime('-1 year'),
            'force' => false
        ];

        $orders = [];
        $orders[] = [
            'amount' => -200,
            'ref_id' => 0,
            'memo' => ''
        ];

        $creditOp->bunchOperation(8, $options, $orders);
    }

    /**
     * 測試批次下單，但時間超過保留天數
     */
    public function testBunchOperationWithExpiredAt()
    {
        $creditOp = $this->getContainer()->get('durian.credit_op');

        $options = [
            'opcode' => 1010,
            'group_num' => 1,
            'amount' => -200,
            'at' => new \DateTime('-1 year'),
            'force' => false
        ];

        $orders = [];
        $orders[] = [
            'amount' => -200,
            'ref_id' => 0,
            'memo' => ''
        ];

        $creditInfo = $creditOp->bunchOperation(8, $options, $orders);

        $this->assertEquals(8, $creditInfo['user_id']);
        $this->assertEquals(1, $creditInfo['group']);
        $this->assertNull($creditInfo['line']);
        $this->assertNull($creditInfo['balance']);
    }

    /**
     * 測試批次下單，但累積金額為負數
     */
    public function testBunchOperationWithNewAmountIsNegative()
    {
        $this->setExpectedException('RuntimeException', 'Amount of period can not be negative', 150060008);

        $creditOp = $this->getContainer()->get('durian.credit_op');

        $options = [
            'opcode' => 1010,
            'group_num' => 1,
            'amount' => 10000,
            'at' => new \DateTime,
            'force' => false
        ];

        $orders = [];
        $orders[] = [
            'amount' => 10000,
            'ref_id' => 0,
            'memo' => ''
        ];

        $creditOp->bunchOperation(8, $options, $orders);
    }

    /**
     * 測試批次下單，但額額為負數
     */
    public function testBunchOperationWithNewBalanceIsNegative()
    {
        $this->setExpectedException('RuntimeException', 'Not enough balance', 150060034);

        $creditOp = $this->getContainer()->get('durian.credit_op');

        $options = [
            'opcode' => 1001,
            'group_num' => 1,
            'amount' => -60000,
            'at' => new \DateTime,
            'force' => false
        ];

        $orders = [];
        $orders[] = [
            'amount' => -60000,
            'ref_id' => 0,
            'memo' => ''
        ];

        $creditOp->bunchOperation(8, $options, $orders);
    }

    /**
     * 測試確認批次下單，但沒有明細與累積金額
     */
    public function testBunchConfirmWithoutEntryOrPeriod()
    {
        $creditOp = $this->getContainer()->get('durian.credit_op');
        $this->assertNull($creditOp->bunchConfirm());
    }

    /**
     * 測試取消批次下單
     */
    public function testBunchRollback()
    {
        $redis = $this->getContainer()->get('snc_redis.default');
        $redisWallet = $this->getContainer()->get('snc_redis.wallet4');
        $creditOp = $this->getContainer()->get('durian.credit_op');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $cron = \Cron\CronExpression::factory('@daily');
        $now = new \DateTime;
        $exNow = $cron->getPreviousRunDate($now, 0, true);

        $credit = $em->find('BBDurianBundle:Credit', 5);

        $creditPeriod = new CreditPeriod($credit, $exNow);
        $creditPeriod->addAmount(700);
        $em->persist($creditPeriod);
        $em->flush();

        $options = [
            'opcode' => 1010,
            'group_num' => 1,
            'amount' => -1000,
            'at' => $now,
            'force' => false
        ];

        $orders = [];
        $orders[] = [
            'amount' => -1000,
            'ref_id' => 0,
            'memo' => ''
        ];

        $creditOp->bunchOperation(8, $options, $orders);

        $periodKey = sprintf(
            'credit_period_8_1_%s',
            $exNow->format('Ymd')
        );
        $this->assertEquals(17000000, $redisWallet->hget($periodKey, 'amount'));

        $creditOp->bunchRollback();
        $this->assertEquals(7000000, $redisWallet->hget($periodKey, 'amount'));
        $periodQueue = '{"credit_id":"5","user_id":8,"group_num":1,"amount":700,"at":"' . $exNow->format('Ymd') . '","version":3}';
        $this->assertEquals([$periodQueue], $redis->lrange('credit_period_queue', 0, 1));

        // 執行背景
        $params = ['--period' => 1];
        $this->runCommand('durian:sync-credit', $params);

        // 測試資料庫
        $em->refresh($creditPeriod);

        $this->assertEquals(700, $creditPeriod->getAmount());
        $this->assertEquals(3, $creditPeriod->getVersion());
    }

    /**
     * 測試刪除 Redis 中信用額度資料
     */
    public function testRemoveAll()
    {
        $redisWallet = $this->getContainer()->get('snc_redis.wallet4');

        $creditOp = $this->getContainer()->get('durian.credit_op');
        $now = new \DateTime;
        $options = [
            'group_num' => 2,
            'amount' => -100,
            'opcode' => 40000,
            'refId' => 0,
            'memo' => '',
            'force' => false,
            'at' => $now
        ];
        $creditOp->operation(8, $options);

        $periodKey = sprintf(
            'credit_period_8_2_%s',
            $now->format('Ymd')
        );

        $this->assertTrue($redisWallet->exists('credit_8_2'));
        $this->assertTrue($redisWallet->exists('credit_period_index_8_2'));
        $this->assertTrue($redisWallet->exists($periodKey));

        $creditOp->removeAll(8, 2);
        $this->assertFalse($redisWallet->exists('credit_8_2'));
        $this->assertFalse($redisWallet->exists('credit_period_index_8_2'));
        $this->assertFalse($redisWallet->exists($periodKey));
    }

    /**
     * 測試把時間透過 CronExpression 轉換成新的時間
     */
    public function testToCronExpresssion()
    {
        $creditOp = $this->getContainer()->get('durian.credit_op');

        $now = new \DateTime();
        $intNow = $now->format('YmdHis');
        $intCronNow = $now->format('Ymd000000');

        $cronNow = $creditOp->toCronExpression($now, 1);

        $this->assertEquals($intCronNow, $cronNow->format('YmdHis'));

        // 經過 CronExpression 不應該變更時間
        $this->assertEquals($intNow, $now->format('YmdHis'));
    }

    /**
     * 測試無條件進位到指定小數位
     */
    public function testRoundUp()
    {
        $creditOp = $this->getContainer()->get('durian.credit_op');

        $amount = $creditOp->roundUp(123.456022, 4);

        $this->assertEquals(123.4561, $amount);
    }

    /**
     * 測試無條件捨去到指定小數位
     */
    public function testRoundDown()
    {
        $creditOp = $this->getContainer()->get('durian.credit_op');

        $amount = $creditOp->roundDown(123.456099, 4);

        $this->assertEquals(123.4560, $amount);
    }
}
