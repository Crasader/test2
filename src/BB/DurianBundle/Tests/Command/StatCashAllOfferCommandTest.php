<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;

/**
 * 測試統計現金優惠命令
 *
 * @author Sweet 2014.11.18
 */
class StatCashAllOfferCommandTest extends WebTestCase
{
    /**
     * 初始化
     */
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadStatCashOfferData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadStatCashRebateData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadStatCashRemitData'
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
        $ret = $this->runCommand('durian:stat-cash-all-offer', $params);

        // 沒有出現錯誤訊息
        $results = explode(PHP_EOL, $ret);
        $this->assertCount(3, $results);
        $this->assertEmpty($results[2]);

        $em = $this->getContainer()->get('doctrine.orm.his_entity_manager');

        $stat1 = $em->find('BBDurianBundle:StatCashAllOffer', 1);
        $this->assertEquals('2014-10-10 12:00:00', $stat1->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(7, $stat1->getUserId());
        $this->assertEquals(901, $stat1->getCurrency());
        $this->assertEquals(2, $stat1->getDomain());
        $this->assertEquals(6, $stat1->getParentId());
        $this->assertEquals(100, $stat1->getOfferRebateRemitAmount());
        $this->assertEquals(5, $stat1->getOfferRebateRemitCount());

        $stat2 = $em->find('BBDurianBundle:StatCashAllOffer', 2);
        $this->assertEquals('2014-10-10 12:00:00', $stat2->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(8, $stat2->getUserId());
        $this->assertEquals(156, $stat2->getCurrency());
        $this->assertEquals(2, $stat2->getDomain());
        $this->assertEquals(7, $stat2->getParentId());
        $this->assertEquals(55, $stat2->getOfferRebateRemitAmount());
        $this->assertEquals(10, $stat2->getOfferRebateRemitCount());

        // 加大時間範圍
        $params = [
            '--start-date' => '2014-10-10',
            '--end-date' => '2014-10-13',
            '--wait-sec' => 0.00001
        ];
        $this->runCommand('durian:stat-cash-all-offer', $params);

        $query = $em->createQuery('SELECT COUNT(s) FROM BBDurianBundle:StatCashAllOffer s WHERE s.at >= :start AND s.at <= :end');
        $query->setParameter('start', new \DateTime('2014-10-10 12:00:00'));
        $query->setParameter('end', new \DateTime('2014-10-13 12:00:00'));

        $count = $query->getSingleScalarResult();

        $this->assertEquals(5, $count);

        $stat1 = $em->find('BBDurianBundle:StatCashAllOffer', 1);
        $this->assertEquals('2014-10-10 12:00:00', $stat1->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(7, $stat1->getUserId());
        $this->assertEquals(901, $stat1->getCurrency());
        $this->assertEquals(2, $stat1->getDomain());
        $this->assertEquals(6, $stat1->getParentId());
        $this->assertEquals(100, $stat1->getOfferRebateRemitAmount());
        $this->assertEquals(5, $stat1->getOfferRebateRemitCount());

        $stat2 = $em->find('BBDurianBundle:StatCashAllOffer', 2);
        $this->assertEquals('2014-10-10 12:00:00', $stat2->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(8, $stat2->getUserId());
        $this->assertEquals(156, $stat2->getCurrency());
        $this->assertEquals(2, $stat2->getDomain());
        $this->assertEquals(7, $stat2->getParentId());
        $this->assertEquals(55, $stat2->getOfferRebateRemitAmount());
        $this->assertEquals(10, $stat2->getOfferRebateRemitCount());

        $stat3 = $em->find('BBDurianBundle:StatCashAllOffer', 3);
        $this->assertEquals('2014-10-12 12:00:00', $stat3->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(7, $stat3->getUserId());
        $this->assertEquals(901, $stat3->getCurrency());
        $this->assertEquals(2, $stat3->getDomain());
        $this->assertEquals(6, $stat3->getParentId());
        $this->assertEquals(22, $stat3->getOfferRebateRemitAmount());
        $this->assertEquals(4, $stat3->getOfferRebateRemitCount());

        $stat4 = $em->find('BBDurianBundle:StatCashAllOffer', 4);
        $this->assertEquals('2014-10-12 12:00:00', $stat4->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(8, $stat4->getUserId());
        $this->assertEquals(156, $stat4->getCurrency());
        $this->assertEquals(2, $stat4->getDomain());
        $this->assertEquals(7, $stat4->getParentId());
        $this->assertEquals(110, $stat4->getOfferRebateRemitAmount());
        $this->assertEquals(2, $stat4->getOfferRebateRemitCount());

        $stat5 = $em->find('BBDurianBundle:StatCashAllOffer', 5);
        $this->assertEquals('2014-10-13 12:00:00', $stat5->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(8, $stat5->getUserId());
        $this->assertEquals(156, $stat5->getCurrency());
        $this->assertEquals(2, $stat5->getDomain());
        $this->assertEquals(7, $stat5->getParentId());
        $this->assertEquals(120, $stat5->getOfferRebateRemitAmount());
        $this->assertEquals(2, $stat5->getOfferRebateRemitCount());

        // Opcode 不在範圍，不會列入統計
        $repo = $em->getRepository('BBDurianBundle:StatCashAllOffer');
        $stat = $repo->findOneBy([
            'at' => new \DateTime('2014-10-13 12:00:00'),
            'userId' => 7
        ]);
        $this->assertEmpty($stat);

        // 測試優惠表、返點表有相同資料仍會正確統計
        $em->remove($stat5);
        $statCashReate = $em->find('BBDurianBundle:StatCashRebate', 8);
        $statCashReate->setRebateAmount(100);
        $em->flush();

        $params = [
            '--start-date' => '2014-10-13',
            '--end-date' => '2014-10-13',
            '--wait-sec' => 0.00001
        ];
        $this->runCommand('durian:stat-cash-all-offer', $params);

        $stat5 = $em->find('BBDurianBundle:StatCashAllOffer', 5);

        $this->assertEquals('2014-10-13 12:00:00', $stat5->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(8, $stat5->getUserId());
        $this->assertEquals(200, $stat5->getOfferRebateRemitAmount());
        $this->assertEquals(2, $stat5->getOfferRebateRemitCount());

        $emDefault = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $repo = $emDefault->getRepository('BBDurianBundle:BackgroundProcess');
        $background = $repo->findOneBy(['name' => 'stat-cash-all-offer']);

        // 驗證背景最後執行時間
        $this->assertEquals('2014-10-13 12:00:00', $background->getLastEndTime()->format('Y-m-d H:i:s'));

        $params = [
            '--start-date' => '2014-8-13',
            '--end-date' => '2014-8-13',
            '--wait-sec' => 0.00001,
            '--recover' => true
        ];
        $this->runCommand('durian:stat-cash-all-offer', $params);

        $emDefault->refresh($background);
        $this->assertEquals(0, $background->getNum());
        $this->assertEquals('2014-10-13 12:00:00', $background->getLastEndTime()->format('Y-m-d H:i:s'));
    }
}
