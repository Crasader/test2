<?php

namespace BB\DurianBundle\Tests\CashFake\Entry;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\CashFake\Entry\IdGenerator;

class IdGeneratorTest extends WebTestCase
{
    /**
     * Card entry id generator
     *
     * @var IdGenerator
     */
    private $idGenerator;

    public function setUp()
    {
        parent::setUp();

        $container = $this->getContainer();

        $this->idGenerator = $container->get('durian.cash_fake_entry_id_generator');

        $redis = $container->get('snc_redis.sequence');

        $redis->set('cashfake_seq', 1000);
    }

    /**
     * 測試產生三組租卡明細id
     */
    public function testGenerateCashFakeEntryIdThreeTimes()
    {
        $idGenerator = $this->idGenerator;

        $this->assertEquals(1001, $idGenerator->generate());
        $this->assertEquals(1002, $idGenerator->generate());
        $this->assertEquals(1003, $idGenerator->generate());
    }

    /**
     * 測試產生CashFake ID 時 key 不存在
     */
    public function testGenerateCashFakeIdWithNonExistKey()
    {
        $this->setExpectedException('RuntimeException', 'Cannot generate cashfake sequence id', 150050009);

        $redis = $this->getContainer()->get('snc_redis.sequence');
        $redis->del('cashfake_seq');

        $this->idGenerator->generate();
    }
}
