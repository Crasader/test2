<?php

namespace BB\DurianBundle\Tests\Card\Entry;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Card\Entry\IdGenerator;

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

        $this->idGenerator = $container->get('durian.card_entry_id_generator');

        $redis = $container->get('snc_redis.sequence');

        $redis->set('card_seq', 1000);
    }

    /**
     * 測試產生三組租卡明細id
     */
    public function testGenerateCardIdThreeTimes()
    {
        $idGenerator = $this->idGenerator;

        $this->assertEquals(1001, $idGenerator->generate());
        $this->assertEquals(1002, $idGenerator->generate());
        $this->assertEquals(1003, $idGenerator->generate());
    }

    /**
     * 測試產生Card ID 時 key 不存在
     */
    public function testGenerateCardIdWithNonExistKey()
    {
        $this->setExpectedException('RuntimeException', 'Cannot generate card sequence id', 150030007);

        $redis = $this->getContainer()->get('snc_redis.sequence');
        $redis->del('card_seq');

        $this->idGenerator->generate();
    }
}
