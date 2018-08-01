<?php

namespace BB\DurianBundle\Tests\Repository;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\RewardEntry;

class RewardEntryRepositoryTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRewardData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRewardEntryData'
        ];

        $this->loadFixtures($classnames, 'share');
    }

    /**
     * 測試取得Id最大值
     */
    public function testGetMaxId()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $reward = $emShare->find('BBDurianBundle:Reward', 2);

        $repo = $emShare->getRepository('BBDurianBundle:RewardEntry');
        $maxId = $repo->getMaxId();

        $entry = new RewardEntry(2, 10);
        $entry->setId($maxId + 1);
        $emShare->persist($entry);

        $emShare->flush();

        $this->assertEquals($entry->getId(), $repo->getMaxId());
    }

    /**
     * 測試根據紅包活動id回傳已派彩的紅包明細資料
     */
    public function testGetListByRewardId()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $repo = $emShare->getRepository('BBDurianBundle:RewardEntry');

        $criteria = [
            'reward_id' => 1,
            'obtain'    => 0,
            'payoff'    => 1
        ];

        $orderBy = ['id' => 'asc'];

        $entrys = $repo->getListByRewardId($criteria, $orderBy);

        $entry = $emShare->find('BBDurianBundle:RewardEntry', 2);
        $this->assertEquals($entry, $entrys[0]);
    }

    /**
     * 測試根據紅包活動id及搜尋條件回傳已派彩的紅包明細筆數
     */
    public function testCountListByRewardId()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $repo = $emShare->getRepository('BBDurianBundle:RewardEntry');

        $criteria = [
            'reward_id' => 1,
            'obtain'    => 0,
            'payoff'    => 1
        ];

        $count = $repo->countListByRewardId($criteria);

        $this->assertEquals(1, $count);
    }

    /**
     * 測試根據使用者id回傳已派彩的紅包明細資料
     */
    public function testGetListByUserId()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $now = new \DateTime('now');

        $entry = $emShare->find('BBDurianBundle:RewardEntry', 1);
        $entry->setpayoffAt($now);

        $emShare->flush();

        $repo = $emShare->getRepository('BBDurianBundle:RewardEntry');

        $criteria = [
            'user_id' => 8,
            'payoff'  => 1
        ];

        $orderBy = ['id' => 'asc'];

        $entrys = $repo->getListByUserId($criteria, $orderBy);

        $entry = $emShare->find('BBDurianBundle:RewardEntry', 1);

        $this->assertEquals($entry, $entrys[0]);
    }

    /**
     * 測試根據使用者id回傳已派彩的紅包明細筆數
     */
    public function testCountListByUserId()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $now = new \DateTime('now');

        $entry = $emShare->find('BBDurianBundle:RewardEntry', 1);
        $entry->setpayoffAt($now);

        $emShare->flush();

        $repo = $emShare->getRepository('BBDurianBundle:RewardEntry');

        $criteria = [
            'user_id' => 8,
            'payoff'  => 1
        ];

        $count = $repo->countListByUserId($criteria);

        $this->assertEquals(1, $count);
    }
}
