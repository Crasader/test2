<?php

namespace BB\DurianBundle\Tests\User;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class IdGeneratorTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $redis = $this->getContainer()->get('snc_redis.sequence');

        $redis->set('user_seq', 20000000);
    }

    /**
     * 測試產生使用者ID 三次
     */
    public function testGenerateUserIdThreeTimes()
    {
        $idGenerator = $this->getContainer()->get('durian.user_id_generator');

        $this->assertEquals(20000001, $idGenerator->generate());
        $this->assertEquals(20000002, $idGenerator->generate());
        $this->assertEquals(20000003, $idGenerator->generate());
    }

    /**
     * 測試產生使用者ID 時 key 不存在
     */
    public function testGenerateUserIdWithNonExistKey()
    {
        $this->setExpectedException('RuntimeException', 'Cannot generate user sequence id', 150010087);

        $redis = $this->getContainer()->get('snc_redis.sequence');
        $redis->del('user_seq');

        $this->getContainer()->get('durian.user_id_generator')->generate();
    }

    /**
     * 測試取得使用者ID
     */
    public function testGetUserId()
    {
        $idGenerator = $this->getContainer()->get('durian.user_id_generator');

        $this->assertEquals(20000000, $idGenerator->getCurrentId());
    }

    /**
     * 測試取得使用者ID時 key 不存在
     */
    public function testGetUserIdWithNonExistKey()
    {
        $this->setExpectedException('RuntimeException', 'Cannot get user sequence id', 150010088);

        $redis = $this->getContainer()->get('snc_redis.sequence');
        $redis->del('user_seq');

        $this->getContainer()->get('durian.user_id_generator')->getCurrentId();
    }
}
