<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;

/**
 * 測試統計現金交易代碼命令
 *
 * @author Chuck 2014.10.07
 */
class StatCashOpcodeCommandTest extends WebTestCase
{
    /**
     * 初始化
     */
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBackgroundProcess'
        ];
        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashEntryDataForStatCashOpcode',
        ];
        $this->loadFixtures($classnames, 'his');

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemovedUserData'
        ];
        $this->loadFixtures($classnames, 'share');
    }

    /**
     * 測試統計轉入資料正確
     */
    public function testStatSuccess()
    {
        $params = [
            '--start-date' => '2013-01-10',
            '--end-date' => '2013-01-10'
        ];
        $ret = $this->runCommand('durian:stat-cash-opcode', $params);

        // 沒有出現錯誤訊息
        $results = explode(PHP_EOL, $ret);
        $this->assertCount(3, $results);
        $this->assertEmpty($results[2]);

        $em = $this->getContainer()->get('doctrine.orm.his_entity_manager');

        $stat1 = $em->find('BBDurianBundle:StatCashOpcode', 1);
        $this->assertEquals('2013-01-10 12:00:00', $stat1->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(7, $stat1->getUserId());
        $this->assertEquals(901, $stat1->getCurrency());
        $this->assertEquals(2, $stat1->getDomain());
        $this->assertEquals(6, $stat1->getParentId());
        $this->assertEquals(1036, $stat1->getOpcode());
        $this->assertEquals(25, $stat1->getAmount());
        $this->assertEquals(2, $stat1->getCount());

        $stat2 = $em->find('BBDurianBundle:StatCashOpcode', 2);
        $this->assertEquals('2013-01-10 12:00:00', $stat2->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(8, $stat2->getUserId());
        $this->assertEquals(901, $stat2->getCurrency());
        $this->assertEquals(2, $stat2->getDomain());
        $this->assertEquals(7, $stat2->getParentId());
        $this->assertEquals(1036, $stat2->getOpcode());
        $this->assertEquals(45, $stat2->getAmount());
        $this->assertEquals(1, $stat2->getCount());

        // 加大時間範圍
        $params = [
            '--start-date' => '2013-01-10',
            '--end-date' => '2013-01-13'
        ];
        $this->runCommand('durian:stat-cash-opcode', $params);

        $em->clear();

        $query = $em->createQuery('SELECT COUNT(s) FROM BBDurianBundle:StatCashOpcode s WHERE s.at >= :start AND s.at <= :end');
        $query->setParameter('start', new \DateTime('2013-01-10 12:00:00'));
        $query->setParameter('end', new \DateTime('2013-01-13 12:00:00'));
        $count = $query->getSingleScalarResult();

        $this->assertEquals(7, $count);

        $stat1 = $em->find('BBDurianBundle:StatCashOpcode', 1);
        $this->assertEquals('2013-01-10 12:00:00', $stat1->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(7, $stat1->getUserId());
        $this->assertEquals(901, $stat1->getCurrency());
        $this->assertEquals(2, $stat1->getDomain());
        $this->assertEquals(6, $stat1->getParentId());
        $this->assertEquals(1036, $stat1->getOpcode());
        $this->assertEquals(25, $stat1->getAmount());
        $this->assertEquals(2, $stat1->getCount());

        $stat2 = $em->find('BBDurianBundle:StatCashOpcode', 2);
        $this->assertEquals('2013-01-10 12:00:00', $stat2->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(8, $stat2->getUserId());
        $this->assertEquals(901, $stat2->getCurrency());
        $this->assertEquals(2, $stat2->getDomain());
        $this->assertEquals(7, $stat2->getParentId());
        $this->assertEquals(1036, $stat2->getOpcode());
        $this->assertEquals(45, $stat2->getAmount());
        $this->assertEquals(1, $stat2->getCount());

        $stat3 = $em->find('BBDurianBundle:StatCashOpcode', 3);
        $this->assertEquals('2013-01-12 12:00:00', $stat3->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(2, $stat3->getUserId());
        $this->assertEquals(901, $stat3->getCurrency());
        $this->assertEquals(2, $stat3->getDomain());
        $this->assertEquals(0, $stat3->getParentId());
        $this->assertEquals(1039, $stat3->getOpcode());
        $this->assertEquals(1000, $stat3->getAmount());
        $this->assertEquals(1, $stat3->getCount());

        $stat4 = $em->find('BBDurianBundle:StatCashOpcode', 4);
        $this->assertEquals('2013-01-12 12:00:00', $stat4->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(7, $stat4->getUserId());
        $this->assertEquals(901, $stat4->getCurrency());
        $this->assertEquals(2, $stat4->getDomain());
        $this->assertEquals(6, $stat4->getParentId());
        $this->assertEquals(1036, $stat4->getOpcode());
        $this->assertEquals(10, $stat4->getAmount());
        $this->assertEquals(1, $stat4->getCount());

        $stat5 = $em->find('BBDurianBundle:StatCashOpcode', 5);
        $this->assertEquals('2013-01-12 12:00:00', $stat5->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(8, $stat5->getUserId());
        $this->assertEquals(901, $stat5->getCurrency());
        $this->assertEquals(2, $stat5->getDomain());
        $this->assertEquals(7, $stat5->getParentId());
        $this->assertEquals(1010, $stat5->getOpcode());
        $this->assertEquals(35, $stat5->getAmount());
        $this->assertEquals(1, $stat5->getCount());

        $stat6 = $em->find('BBDurianBundle:StatCashOpcode', 6);
        $this->assertEquals('2013-01-12 12:00:00', $stat6->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(8, $stat6->getUserId());
        $this->assertEquals(901, $stat6->getCurrency());
        $this->assertEquals(2, $stat6->getDomain());
        $this->assertEquals(7, $stat6->getParentId());
        $this->assertEquals(1037, $stat6->getOpcode());
        $this->assertEquals(45, $stat6->getAmount());
        $this->assertEquals(1, $stat6->getCount());

        $stat7 = $em->find('BBDurianBundle:StatCashOpcode', 7);
        $this->assertEquals('2013-01-12 12:00:00', $stat5->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(8, $stat7->getUserId());
        $this->assertEquals(901, $stat7->getCurrency());
        $this->assertEquals(2, $stat7->getDomain());
        $this->assertEquals(7, $stat7->getParentId());
        $this->assertEquals(1052, $stat7->getOpcode());
        $this->assertEquals(6, $stat7->getAmount());
        $this->assertEquals(1, $stat7->getCount());

        // Opcode 不在範圍，不會列入統計
        $repo = $em->getRepository('BBDurianBundle:StatCashOpcode');
        $stat8 = $repo->findOneBy([
            'userId' => 7,
            'opcode' => 9999
        ]);
        $this->assertNull($stat8);

        $emDefault = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $repo = $emDefault->getRepository('BBDurianBundle:BackgroundProcess');
        $background = $repo->findOneBy(['name' => 'stat-cash-opcode']);

        // 驗證背景最後執行時間
        $this->assertEquals('2013-01-13 12:00:00', $background->getLastEndTime()->format('Y-m-d H:i:s'));

        $params = [
            '--start-date' => '2014-8-13',
            '--end-date' => '2014-8-13',
            '--wait-sec' => 0.00001,
            '--recover' => true
        ];
        $this->runCommand('durian:stat-cash-opcode', $params);

        $emDefault->refresh($background);
        $this->assertEquals(0, $background->getNum());
        $this->assertEquals('2013-01-13 12:00:00', $background->getLastEndTime()->format('Y-m-d H:i:s'));
    }

    /**
     * 測試已刪除的使用者也會正常統計
     */
    public function testStatRemovedUser()
    {
        $hisConn = $this->getContainer()->get('doctrine.dbal.his_connection');

        $entry = [
            'id' => 100001,
            'at' => '20130113150000',
            'user_id' => 50,
            'currency' => 156,
            'opcode' => 1037,
            'created_at' => '2013-01-13 15:00:00',
            'amount' => 12,
            'balance' => 12,
            'ref_id' => 0,
            'memo' => '',
            'cash_id' => 999
        ];
        $hisConn->insert('cash_entry', $entry);


        $params = [
            '--start-date' => '2013-01-13',
            '--end-date' => '2013-01-13'
        ];
        $ret = $this->runCommand('durian:stat-cash-opcode', $params);

        // 沒有出現錯誤訊息
        $results = explode(PHP_EOL, $ret);
        $this->assertCount(3, $results);
        $this->assertEmpty($results[2]);

        $em = $this->getContainer()->get('doctrine.orm.his_entity_manager');

        $stat1 = $em->find('BBDurianBundle:StatCashOpcode', 1);
        $this->assertEquals('2013-01-13 12:00:00', $stat1->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(50, $stat1->getUserId());
        $this->assertEquals(156, $stat1->getCurrency());
        $this->assertEquals(2, $stat1->getDomain());
        $this->assertEquals(2, $stat1->getParentId());
        $this->assertEquals(1037, $stat1->getOpcode());
        $this->assertEquals(12, $stat1->getAmount());
        $this->assertEquals(1, $stat1->getCount());
    }

    /**
     * 測試採用slow參數慢慢刪除
     */
    public function testStatWithSlow()
    {
        $params = [
            '--start-date' => '2013-01-10',
            '--end-date' => '2013-01-10',
            '--slow' => true
        ];
        $ret = $this->runCommand('durian:stat-cash-opcode', $params);

        // 沒有出現錯誤訊息
        $results = explode(PHP_EOL, $ret);
        $this->assertCount(3, $results);
        $this->assertEmpty($results[2]);

        $em = $this->getContainer()->get('doctrine.orm.his_entity_manager');

        $stat1 = $em->find('BBDurianBundle:StatCashOpcode', 1);
        $this->assertEquals('2013-01-10 12:00:00', $stat1->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(7, $stat1->getUserId());
        $this->assertEquals(901, $stat1->getCurrency());
        $this->assertEquals(2, $stat1->getDomain());
        $this->assertEquals(6, $stat1->getParentId());
        $this->assertEquals(1036, $stat1->getOpcode());
        $this->assertEquals(25, $stat1->getAmount());
        $this->assertEquals(2, $stat1->getCount());

        $stat2 = $em->find('BBDurianBundle:StatCashOpcode', 2);
        $this->assertEquals('2013-01-10 12:00:00', $stat2->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(8, $stat2->getUserId());
        $this->assertEquals(901, $stat2->getCurrency());
        $this->assertEquals(2, $stat2->getDomain());
        $this->assertEquals(7, $stat2->getParentId());
        $this->assertEquals(1036, $stat2->getOpcode());
        $this->assertEquals(45, $stat2->getAmount());
        $this->assertEquals(1, $stat2->getCount());
    }

    /**
     * 測試統計香港時區資料轉入正確
     */
    public function testStatSuccessInHongKong()
    {
        $em = $this->getContainer()->get('doctrine.orm.his_entity_manager');

        $params = [
            '--start-date' => '2013-01-10',
            '--end-date' => '2013-01-11',
            '--table-name' => 'stat_cash_opcode_hk'
        ];
        $ret = $this->runCommand('durian:stat-cash-opcode', $params);

        // 沒有出現錯誤訊息
        $results = explode(PHP_EOL, $ret);
        $this->assertCount(3, $results);
        $this->assertEmpty($results[2]);

        $stat1 = $em->find('BBDurianBundle:StatCashOpcodeHK', 1);
        $this->assertEquals('2013-01-10 00:00:00', $stat1->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(7, $stat1->getUserId());
        $this->assertEquals(901, $stat1->getCurrency());
        $this->assertEquals(2, $stat1->getDomain());
        $this->assertEquals(6, $stat1->getParentId());
        $this->assertEquals(1036, $stat1->getOpcode());
        $this->assertEquals(25, $stat1->getAmount());
        $this->assertEquals(2, $stat1->getCount());

        $stat2 = $em->find('BBDurianBundle:StatCashOpcodeHK', 2);
        $this->assertEquals('2013-01-10 00:00:00', $stat2->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(8, $stat2->getUserId());
        $this->assertEquals(901, $stat2->getCurrency());
        $this->assertEquals(2, $stat2->getDomain());
        $this->assertEquals(7, $stat2->getParentId());
        $this->assertEquals(1036, $stat2->getOpcode());
        $this->assertEquals(45, $stat2->getAmount());
        $this->assertEquals(1, $stat2->getCount());
    }
}
