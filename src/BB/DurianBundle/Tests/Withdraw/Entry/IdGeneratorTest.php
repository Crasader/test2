<?php

namespace BB\DurianBundle\Tests\Withdraw\Entry;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Withdraw\Entry\IdGenerator;

class IdGeneratorTest extends WebTestCase
{
    /**
     * Cash withdraw entry id generator
     *
     * @var IdGenerator
     */
    private $idGenerator;

    public function setUp()
    {
        parent::setUp();

        $container = $this->getContainer();

        $this->idGenerator = $container->get('durian.withdraw_entry_id_generator');

        $redis = $container->get('snc_redis.sequence');

        $redis->set('cash_withdraw_seq', 1000);
    }

    /**
     * 測試回傳現金出款明細ID三次
     */
    public function testGenerateCashWithdrawEntryIdThreeTimes()
    {
        $idGenerator = $this->idGenerator;

        $this->assertEquals(1001, $idGenerator->generate());
        $this->assertEquals(1002, $idGenerator->generate());
        $this->assertEquals(1003, $idGenerator->generate());
    }

    /**
     * 測試產生CashWithdraw ID 時 key 不存在
     */
    public function testGenerateCashWithdrawIdWithNonExistKey()
    {
        $this->setExpectedException('RuntimeException', 'Cannot generate cash withdraw sequence id', 380012);

        $redis = $this->getContainer()->get('snc_redis.sequence');
        $redis->del('cash_withdraw_seq');

        $this->idGenerator->generate();
    }
}
