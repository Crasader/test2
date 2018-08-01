<?php

namespace BB\DurianBundle\Tests\Cash\Entry;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Cash\Entry\IdGenerator;

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

        $this->idGenerator = $container->get('durian.cash_entry_id_generator');

        $redis = $container->get('snc_redis.sequence');

        $redis->set('cash_seq', 1000);
    }

    /**
     * 測試回傳現金明細ID三次
     */
    public function testGenerateCashEntryIdThreeTimes()
    {
        $idGenerator = $this->idGenerator;

        $this->assertEquals(1001, $idGenerator->generate());
        $this->assertEquals(1002, $idGenerator->generate());
        $this->assertEquals(1003, $idGenerator->generate());
    }

    /**
     * 測試產生Cash ID 時 key 不存在
     */
    public function testGenerateCashIdWithNonExistKey()
    {
        $this->setExpectedException('RuntimeException', 'Cannot generate cash sequence id', 150040009);

        $redis = $this->getContainer()->get('snc_redis.sequence');
        $redis->del('cash_seq');

        $this->idGenerator->generate();
    }
}
