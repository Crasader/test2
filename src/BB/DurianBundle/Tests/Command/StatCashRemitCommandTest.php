<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;

/**
 * 測試統計現金匯款優惠命令
 *
 * @author Sweet 2014.11.18
 */
class StatCashRemitCommandTest extends WebTestCase
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
            '--batch-size' => 1000,
            '--wait-sec' => 0.00001,
            '--slow' => true
        ];
        $ret = $this->runCommand('durian:stat-cash-remit', $params);

        // 沒有出現錯誤訊息
        $results = explode(PHP_EOL, $ret);
        $this->assertCount(3, $results);
        $this->assertEmpty($results[2]);

        $em = $this->getContainer()->get('doctrine.orm.his_entity_manager');

        $stat1 = $em->find('BBDurianBundle:StatCashRemit', 1);
        $this->assertEquals('2014-10-10 12:00:00', $stat1->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(8, $stat1->getUserId());
        $this->assertEquals(156, $stat1->getCurrency());
        $this->assertEquals(2, $stat1->getDomain());
        $this->assertEquals(7, $stat1->getParentId());
        $this->assertEquals(5, $stat1->getOfferRemitAmount());
        $this->assertEquals(5, $stat1->getOfferRemitCount());
        $this->assertEquals(0, $stat1->getOfferCompanyRemitAmount());
        $this->assertEquals(0, $stat1->getOfferCompanyRemitCount());
        $this->assertEquals(5, $stat1->getRemitAmount());
        $this->assertEquals(5, $stat1->getRemitCount());

        // 加大時間範圍
        $params = [
            '--start-date' => '2014-10-10',
            '--end-date' => '2014-10-13',
            '--wait-sec' => 0.00001
        ];
        $this->runCommand('durian:stat-cash-remit', $params);

        $query = $em->createQuery('SELECT COUNT(s) FROM BBDurianBundle:StatCashRemit s WHERE s.at >= :start AND s.at <= :end');
        $query->setParameter('start', new \DateTime('2014-10-10 12:00:00'));
        $query->setParameter('end', new \DateTime('2014-10-13 12:00:00'));

        $count = $query->getSingleScalarResult();

        $this->assertEquals(2, $count);

        $stat1 = $em->find('BBDurianBundle:StatCashRemit', 1);
        $this->assertEquals('2014-10-10 12:00:00', $stat1->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(8, $stat1->getUserId());
        $this->assertEquals(156, $stat1->getCurrency());
        $this->assertEquals(2, $stat1->getDomain());
        $this->assertEquals(7, $stat1->getParentId());
        $this->assertEquals(5, $stat1->getOfferRemitAmount());
        $this->assertEquals(5, $stat1->getOfferRemitCount());
        $this->assertEquals(0, $stat1->getOfferCompanyRemitAmount());
        $this->assertEquals(0, $stat1->getOfferCompanyRemitCount());
        $this->assertEquals(5, $stat1->getRemitAmount());
        $this->assertEquals(5, $stat1->getRemitCount());

        $stat2 = $em->find('BBDurianBundle:StatCashRemit', 2);
        $this->assertEquals('2014-10-12 12:00:00', $stat2->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(7, $stat2->getUserId());
        $this->assertEquals(901, $stat2->getCurrency());
        $this->assertEquals(2, $stat2->getDomain());
        $this->assertEquals(6, $stat2->getParentId());
        $this->assertEquals(0, $stat2->getOfferRemitAmount());
        $this->assertEquals(0, $stat2->getOfferRemitCount());
        $this->assertEquals(2, $stat2->getOfferCompanyRemitAmount());
        $this->assertEquals(2, $stat2->getOfferCompanyRemitCount());
        $this->assertEquals(2, $stat2->getRemitAmount());
        $this->assertEquals(2, $stat2->getRemitCount());

        // Opcode 不在範圍，不會列入統計
        $repo = $em->getRepository('BBDurianBundle:StatCashRemit');
        $stat = $repo->findOneBy(['at' => new \DateTime('2014-10-13 12:00:00')]);
        $this->assertEmpty($stat);

        $emDefault = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $repo = $emDefault->getRepository('BBDurianBundle:BackgroundProcess');
        $background = $repo->findOneBy(['name' => 'stat-cash-remit']);

        // 驗證背景最後執行時間
        $this->assertEquals('2014-10-13 12:00:00', $background->getLastEndTime()->format('Y-m-d H:i:s'));

        $params = [
            '--start-date' => '2014-8-13',
            '--end-date' => '2014-8-13',
            '--wait-sec' => 0.00001,
            '--recover' => true
        ];
        $this->runCommand('durian:stat-cash-remit', $params);

        $emDefault->refresh($background);
        $this->assertEquals(0, $background->getNum());
        $this->assertEquals('2014-10-13 12:00:00', $background->getLastEndTime()->format('Y-m-d H:i:s'));
    }
}
