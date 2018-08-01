<?php

namespace BB\DurianBundle\Tests\Repository;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class RewardRepositoryTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRewardData'
        ];

        $this->loadFixtures($classnames, 'share');
    }

    /**
     * 測試取得廳主正在進中行的紅包活動
     */
    public function testGetListByActive()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $repo = $emShare->getRepository('BBDurianBundle:Reward');

        $criteria = [
            'domain' => 2,
            'active' => 1,
            'cancel' => 0
        ];

        $orderBy = ['id' => 'asc'];

        $rewards = $repo->getListBy($criteria, $orderBy);

        $reward = $emShare->find('BBDurianBundle:Reward', 2);
        $this->assertEquals($reward, $rewards[0]);
    }

    /**
     * 測試取得廳主非進行中的紅包活動
     */
    public function testGetListByNotActive()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $repo = $emShare->getRepository('BBDurianBundle:Reward');

        $criteria = [
            'domain' => 2,
            'active' => 0,
            'cancel' => 0
        ];

        $rewards = $repo->getListBy($criteria);

        $reward = $emShare->find('BBDurianBundle:Reward', 1);
        $this->assertEquals($reward, $rewards[0]);
    }

    /**
     * 測試回傳廳主正在進行中的活動筆數
     */
    public function testCountListByActive()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $repo = $emShare->getRepository('BBDurianBundle:Reward');

        $criteria = [
            'domain' => 2,
            'active' => 1,
            'cancel' => 0
        ];

        $count = $repo->countListBy($criteria);

        $this->assertEquals(1, $count);
    }

    /**
     * 測試取得廳主非進行中的紅包活動筆數
     */
    public function testCountListByNotActive()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $repo = $emShare->getRepository('BBDurianBundle:Reward');

        $criteria = [
            'domain' => 2,
            'active' => 0,
            'cancel' => 0
        ];

        $count = $repo->countListBy($criteria);

        $this->assertEquals(1, $count);
    }
}
