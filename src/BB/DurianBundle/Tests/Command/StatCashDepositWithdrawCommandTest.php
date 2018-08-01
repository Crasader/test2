<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;

/**
 * 測試統計現金出入款命令
 *
 * @author Sweet 2014.10.30
 */
class StatCashDepositWithdrawCommandTest extends WebTestCase
{
    /**
     * 初始化
     */
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadStatCashOpcodeData'
        ];
        $this->loadFixtures($classnames, 'his');

        $classnames = ['BB\DurianBundle\Tests\DataFixtures\ORM\LoadBackgroundProcess'];
        $this->loadFixtures($classnames);
    }

    /**
     * 測試統計轉入資料正確
     */
    public function testStatSuccess()
    {
        $params = [
            '--start-date' => '2014-10-10',
            '--end-date' => '2014-10-10',
            '--wait-sec' => 0.00001
        ];
        $ret = $this->runCommand('durian:stat-cash-deposit-withdraw', $params);

        // 沒有出現錯誤訊息
        $results = explode(PHP_EOL, $ret);
        $this->assertCount(3, $results);
        $this->assertEmpty($results[2]);

        $em = $this->getContainer()->get('doctrine.orm.his_entity_manager');

        $stat1 = $em->find('BBDurianBundle:StatCashDepositWithdraw', 1);
        $this->assertEquals('2014-10-10 12:00:00', $stat1->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(7, $stat1->getUserId());
        $this->assertEquals(901, $stat1->getCurrency());
        $this->assertEquals(2, $stat1->getDomain());
        $this->assertEquals(6, $stat1->getParentId());
        $this->assertEquals(2000, $stat1->getDepositAmount());
        $this->assertEquals(2, $stat1->getDepositCount());
        $this->assertEquals(0, $stat1->getWithdrawAmount());
        $this->assertEquals(0, $stat1->getWithdrawCount());
        $this->assertEquals(2000, $stat1->getDepositWithdrawAmount());
        $this->assertEquals(2, $stat1->getDepositWithdrawCount());

        $stat2 = $em->find('BBDurianBundle:StatCashDepositWithdraw', 2);
        $this->assertEquals('2014-10-10 12:00:00', $stat2->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(8, $stat2->getUserId());
        $this->assertEquals(156, $stat2->getCurrency());
        $this->assertEquals(2, $stat2->getDomain());
        $this->assertEquals(7, $stat2->getParentId());
        $this->assertEquals(1000, $stat2->getDepositAmount());
        $this->assertEquals(5, $stat2->getDepositCount());
        $this->assertEquals(200, $stat2->getWithdrawAmount());
        $this->assertEquals(1, $stat2->getWithdrawCount());
        $this->assertEquals(1200, $stat2->getDepositWithdrawAmount());
        $this->assertEquals(6, $stat2->getDepositWithdrawCount());

        // 加大時間範圍
        $params = [
            '--start-date' => '2014-10-10',
            '--end-date' => '2014-10-12',
            '--wait-sec' => 0.00001
        ];
        $this->runCommand('durian:stat-cash-deposit-withdraw', $params);

        $query = $em->createQuery('SELECT COUNT(s) FROM BBDurianBundle:StatCashDepositWithdraw s WHERE s.at >= :start AND s.at <= :end');
        $query->setParameter('start', new \DateTime('2014-10-10 12:00:00'));
        $query->setParameter('end', new \DateTime('2014-10-12 12:00:00'));

        $count = $query->getSingleScalarResult();

        $this->assertEquals(4, $count);

        $stat1 = $em->find('BBDurianBundle:StatCashDepositWithdraw', 1);
        $this->assertEquals('2014-10-10 12:00:00', $stat1->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(7, $stat1->getUserId());
        $this->assertEquals(901, $stat1->getCurrency());
        $this->assertEquals(2, $stat1->getDomain());
        $this->assertEquals(6, $stat1->getParentId());
        $this->assertEquals(2000, $stat1->getDepositAmount());
        $this->assertEquals(2, $stat1->getDepositCount());
        $this->assertEquals(0, $stat1->getWithdrawAmount());
        $this->assertEquals(0, $stat1->getWithdrawCount());
        $this->assertEquals(2000, $stat1->getDepositWithdrawAmount());
        $this->assertEquals(2, $stat1->getDepositWithdrawCount());

        $stat2 = $em->find('BBDurianBundle:StatCashDepositWithdraw', 2);
        $this->assertEquals('2014-10-10 12:00:00', $stat2->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(8, $stat2->getUserId());
        $this->assertEquals(156, $stat2->getCurrency());
        $this->assertEquals(2, $stat2->getDomain());
        $this->assertEquals(7, $stat2->getParentId());
        $this->assertEquals(1000, $stat2->getDepositAmount());
        $this->assertEquals(5, $stat2->getDepositCount());
        $this->assertEquals(200, $stat2->getWithdrawAmount());
        $this->assertEquals(1, $stat2->getWithdrawCount());
        $this->assertEquals(1200, $stat2->getDepositWithdrawAmount());
        $this->assertEquals(6, $stat2->getDepositWithdrawCount());

        $stat3 = $em->find('BBDurianBundle:StatCashDepositWithdraw', 3);
        $this->assertEquals('2014-10-12 12:00:00', $stat3->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(7, $stat3->getUserId());
        $this->assertEquals(901, $stat3->getCurrency());
        $this->assertEquals(2, $stat3->getDomain());
        $this->assertEquals(6, $stat3->getParentId());
        $this->assertEquals(2000, $stat3->getDepositAmount());
        $this->assertEquals(2, $stat3->getDepositCount());
        $this->assertEquals(1000, $stat3->getWithdrawAmount());
        $this->assertEquals(1, $stat3->getWithdrawCount());
        $this->assertEquals(3000, $stat3->getDepositWithdrawAmount());
        $this->assertEquals(3, $stat3->getDepositWithdrawCount());

        $stat4 = $em->find('BBDurianBundle:StatCashDepositWithdraw', 4);
        $this->assertEquals('2014-10-12 12:00:00', $stat4->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(8, $stat4->getUserId());
        $this->assertEquals(156, $stat4->getCurrency());
        $this->assertEquals(2, $stat4->getDomain());
        $this->assertEquals(7, $stat4->getParentId());
        $this->assertEquals(1000, $stat4->getDepositAmount());
        $this->assertEquals(1, $stat4->getDepositCount());
        $this->assertEquals(0, $stat4->getWithdrawAmount());
        $this->assertEquals(0, $stat4->getWithdrawCount());
        $this->assertEquals(1000, $stat4->getDepositWithdrawAmount());
        $this->assertEquals(1, $stat4->getDepositWithdrawCount());

        // Opcode 不在範圍，不會列入統計
        $params = [
            '--start-date' => '2014-10-13',
            '--end-date' => '2014-10-13',
            '--wait-sec' => 0.00001
        ];
        $this->runCommand('durian:stat-cash-deposit-withdraw', $params);

        $stat5 = $em->find('BBDurianBundle:StatCashDepositWithdraw', 5);
        $this->assertEmpty($stat5);

        $emDefault = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $repo = $emDefault->getRepository('BBDurianBundle:BackgroundProcess');
        $background = $repo->findOneBy(['name' => 'stat-cash-deposit-withdraw']);

        // 驗證補跑前背景最後成功執行時間
        $this->assertEquals('2014-10-13 12:00:00', $background->getLastEndTime()->format('Y-m-d H:i:s'));

        $params = [
            '--start-date' => '2014-8-13',
            '--end-date' => '2014-8-13',
            '--wait-sec' => 0.00001,
            '--recover' => true
        ];
        $this->runCommand('durian:stat-cash-deposit-withdraw', $params);

        $emDefault->refresh($background);
        // 驗證補跑之後背景最後成功執行時間
        $this->assertEquals(0, $background->getNum());
        $this->assertEquals('2014-10-13 12:00:00', $background->getLastEndTime()->format('Y-m-d H:i:s'));
    }
}
