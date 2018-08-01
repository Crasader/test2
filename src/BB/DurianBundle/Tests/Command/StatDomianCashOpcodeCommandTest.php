<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\StatCashOpcode;
use BB\DurianBundle\Entity\RemovedUser;

/**
 * 測試統計廳的現金交易代碼命令
 *
 * @author Linda 2015.03.25
 */
class StatDomainCashOpcodeCommandTest extends WebTestCase
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
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadStatCashOpcodeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadStatCashOpcodeHKData'
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
        $em = $this->getContainer()->get('doctrine.orm.his_entity_manager');

        $params = [
            '--start-date' => '2014-10-12',
            '--end-date'   => '2014-10-12'
        ];
        $ret = $this->runCommand('durian:stat-domain-cash-opcode', $params);

        // 沒有出現錯誤訊息
        $results = explode(PHP_EOL, $ret);
        $this->assertCount(3, $results);
        $this->assertEmpty($results[2]);

        $stat1 = $em->find('BBDurianBundle:StatDomainCashOpcode', 1);
        $this->assertEquals('2014-10-12 12:00:00', $stat1->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(8, $stat1->getUserId());
        $this->assertEquals(156, $stat1->getCurrency());
        $this->assertEquals(2, $stat1->getDomain());
        $this->assertEquals(1039, $stat1->getOpcode());
        $this->assertEquals(1000, $stat1->getAmount());
        $this->assertEquals(1, $stat1->getCount());

        $stat2 = $em->find('BBDurianBundle:StatDomainCashOpcode', 2);
        $this->assertEquals(8, $stat1->getUserId());
        $this->assertEquals('2014-10-12 12:00:00', $stat2->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(156, $stat2->getCurrency());
        $this->assertEquals(2, $stat2->getDomain());
        $this->assertEquals(1041, $stat2->getOpcode());
        $this->assertEquals(10, $stat2->getAmount());
        $this->assertEquals(1, $stat2->getCount());

        $stat3 = $em->find('BBDurianBundle:StatDomainCashOpcode', 3);
        $this->assertEquals('2014-10-12 12:00:00', $stat3->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(8, $stat1->getUserId());
        $this->assertEquals(156, $stat3->getCurrency());
        $this->assertEquals(2, $stat3->getDomain());
        $this->assertEquals(1053, $stat3->getOpcode());
        $this->assertEquals(100, $stat3->getAmount());
        $this->assertEquals(3, $stat3->getCount());

        // 加大時間區間
        $params = [
            '--start-date' => '2014-10-10',
            '--end-date'   => '2014-10-13',
            '--wait-sec'   => 10,
            '--batch-size' => 10
        ];
        $ret = $this->runCommand('durian:stat-domain-cash-opcode', $params);

        // 沒有出現錯誤訊息
        $results = explode(PHP_EOL, $ret);
        $this->assertCount(3, $results);
        $this->assertEmpty($results[2]);

        $stat = $em->getRepository('BBDurianBundle:StatDomainCashOpcode')->findAll();
        $this->assertEquals(11, count($stat));

        // 非會員資料不會轉入
        $repo = $em->getRepository('BBDurianBundle:StatDomainCashOpcode');
        $stat4 = $repo->findOneBy(['userId' => 7]);
        $this->assertNull($stat4);

        $emDefault = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $repo = $emDefault->getRepository('BBDurianBundle:BackgroundProcess');
        $background = $repo->findOneBy(['name' => 'stat-domain-cash-opcode']);

        // 驗證背景最後執行時間
        $this->assertEquals('2014-10-13 12:00:00', $background->getLastEndTime()->format('Y-m-d H:i:s'));

        $params = [
            '--start-date' => '2014-8-13',
            '--end-date' => '2014-8-13',
            '--wait-sec' => 0.00001,
            '--recover' => true
        ];
        $this->runCommand('durian:stat-domain-cash-opcode', $params);

        $emDefault->refresh($background);
        $this->assertEquals(0, $background->getNum());
        $this->assertEquals('2014-10-13 12:00:00', $background->getLastEndTime()->format('Y-m-d H:i:s'));
    }

    /**
     * 測試已刪除的使用者也會正常統計
     */
    public function testStatRemovedUser()
    {
        // 將會員51轉到removed_user資料表
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $user = $em->find('BBDurianBundle:User', 51);
        $rmUser = new RemovedUser($user);

        $emShare->persist($rmUser);
        $em->remove($user);
        $em->flush();
        $emShare->flush();

        $emHis = $this->getContainer()->get('doctrine.orm.his_entity_manager');

        $at = new \DateTime('2014-04-16 12:00:00');
        $stat = new StatCashOpcode($at, 51, 156);
        $stat->setDomain(2);
        $stat->setParentId(7);
        $stat->setOpcode(1037);
        $stat->setAmount(10);
        $stat->setCount(1);

        $emHis->persist($stat);
        $emHis->flush();

        $emHis->clear();

        $params = [
            '--start-date' => '2014-04-16',
            '--end-date'   => '2014-04-16'
        ];
        $ret = $this->runCommand('durian:stat-domain-cash-opcode', $params);

        // 沒有出現錯誤訊息
        $results = explode(PHP_EOL, $ret);
        $this->assertCount(3, $results);
        $this->assertEmpty($results[2]);

        $stat1 = $emHis->find('BBDurianBundle:StatDomainCashOpcode', 1);
        $this->assertEquals('2014-04-16 12:00:00', $stat1->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(156, $stat1->getCurrency());
        $this->assertEquals(51, $stat1->getUserId());
        $this->assertEquals(2, $stat1->getDomain());
        $this->assertEquals(1037, $stat1->getOpcode());
        $this->assertEquals(10, $stat1->getAmount());
    }

    /**
     * 測試採用slow參數慢慢刪除
     */
    public function testStatWithSlow()
    {
        $params = [
            '--start-date' => '2014-10-12',
            '--end-date'   => '2014-10-12',
            '--slow'       => true
        ];
        $ret = $this->runCommand('durian:stat-domain-cash-opcode', $params);

        // 沒有出現錯誤訊息
        $results = explode(PHP_EOL, $ret);
        $this->assertCount(3, $results);
        $this->assertEmpty($results[2]);

        $em = $this->getContainer()->get('doctrine.orm.his_entity_manager');

        $stat1 = $em->find('BBDurianBundle:StatDomainCashOpcode', 1);
        $this->assertEquals('2014-10-12 12:00:00', $stat1->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(156, $stat1->getCurrency());
        $this->assertEquals(2, $stat1->getDomain());
        $this->assertEquals(1039, $stat1->getOpcode());
        $this->assertEquals(1000, $stat1->getAmount());

        $stat2 = $em->find('BBDurianBundle:StatDomainCashOpcode', 2);
        $this->assertEquals('2014-10-12 12:00:00', $stat2->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(156, $stat2->getCurrency());
        $this->assertEquals(2, $stat2->getDomain());
        $this->assertEquals(1041, $stat2->getOpcode());
        $this->assertEquals(10, $stat2->getAmount());

        $stat3 = $em->find('BBDurianBundle:StatDomainCashOpcode', 3);
        $this->assertEquals('2014-10-12 12:00:00', $stat3->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(156, $stat3->getCurrency());
        $this->assertEquals(2, $stat3->getDomain());
        $this->assertEquals(1053, $stat3->getOpcode());
        $this->assertEquals(100, $stat3->getAmount());
    }

    /**
     * 測試統計香港時區資料轉入正確
     */
    public function testStatSuccessInHongKong()
    {
        $em = $this->getContainer()->get('doctrine.orm.his_entity_manager');

        $params = [
            '--start-date' => '2014-10-10',
            '--end-date' => '2014-10-11',
            '--table-name' => 'stat_domain_cash_opcode_hk'
        ];
        $ret = $this->runCommand('durian:stat-domain-cash-opcode', $params);

        // 沒有出現錯誤訊息
        $results = explode(PHP_EOL, $ret);
        $this->assertCount(3, $results);
        $this->assertEmpty($results[2]);

        $stat = $em->find('BBDurianBundle:StatDomainCashOpcodeHK', 1);
        $this->assertEquals('2014-10-10 00:00:00', $stat->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(8, $stat->getUserId());
        $this->assertEquals(156, $stat->getCurrency());
        $this->assertEquals(2, $stat->getDomain());
        $this->assertEquals(1001, $stat->getOpcode());
        $this->assertEquals(1000, $stat->getAmount());

        $stat = $em->find('BBDurianBundle:StatDomainCashOpcodeHK', 2);
        $this->assertEquals('2014-10-10 00:00:00', $stat->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(8, $stat->getUserId());
        $this->assertEquals(156, $stat->getCurrency());
        $this->assertEquals(2, $stat->getDomain());
        $this->assertEquals(1011, $stat->getOpcode());
        $this->assertEquals(50, $stat->getAmount());
    }
}
