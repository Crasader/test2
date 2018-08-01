<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;

/**
 * 測試統計現金優惠命令
 *
 * @author Sweet 2014.11.18
 */
class StatCashOfferCommandTest extends WebTestCase
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
        $ret = $this->runCommand('durian:stat-cash-offer', $params);

        // 沒有出現錯誤訊息
        $results = explode(PHP_EOL, $ret);
        $this->assertCount(3, $results);
        $this->assertEmpty($results[2]);

        $em = $this->getContainer()->get('doctrine.orm.his_entity_manager');

        $stat1 = $em->find('BBDurianBundle:StatCashOffer', 1);
        $this->assertEquals('2014-10-10 12:00:00', $stat1->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(8, $stat1->getUserId());
        $this->assertEquals(156, $stat1->getCurrency());
        $this->assertEquals(2, $stat1->getDomain());
        $this->assertEquals(7, $stat1->getParentId());
        $this->assertEquals(50, $stat1->getOfferDepositAmount());
        $this->assertEquals(5, $stat1->getOfferDepositCount());
        $this->assertEquals(0, $stat1->getOfferBackCommissionAmount());
        $this->assertEquals(0, $stat1->getOfferBackCommissionCount());
        $this->assertEquals(0, $stat1->getOfferCompanyDepositAmount());
        $this->assertEquals(0, $stat1->getOfferCompanyDepositCount());
        $this->assertEquals(0, $stat1->getOfferOnlineDepositAmount());
        $this->assertEquals(0, $stat1->getOfferOnlineDepositCount());
        $this->assertEquals(0, $stat1->getOfferActiveAmount());
        $this->assertEquals(0, $stat1->getOfferActiveCount());
        $this->assertEquals(0, $stat1->getOfferRegisterAmount());
        $this->assertEquals(0, $stat1->getOfferRegisterCount());
        $this->assertEquals(50, $stat1->getOfferAmount());
        $this->assertEquals(5, $stat1->getOfferCount());

        // 加大時間範圍
        $params = [
            '--start-date' => '2014-10-10',
            '--end-date' => '2014-10-13',
            '--wait-sec' => 0.00001
        ];
        $this->runCommand('durian:stat-cash-offer', $params);

        $query = $em->createQuery('SELECT COUNT(s) FROM BBDurianBundle:StatCashOffer s WHERE s.at >= :start AND s.at <= :end');
        $query->setParameter('start', new \DateTime('2014-10-10 12:00:00'));
        $query->setParameter('end', new \DateTime('2014-10-13 12:00:00'));

        $count = $query->getSingleScalarResult();

        $this->assertEquals(4, $count);

        $stat1 = $em->find('BBDurianBundle:StatCashOffer', 1);
        $this->assertEquals('2014-10-10 12:00:00', $stat1->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(8, $stat1->getUserId());
        $this->assertEquals(156, $stat1->getCurrency());
        $this->assertEquals(2, $stat1->getDomain());
        $this->assertEquals(7, $stat1->getParentId());
        $this->assertEquals(50, $stat1->getOfferDepositAmount());
        $this->assertEquals(5, $stat1->getOfferDepositCount());
        $this->assertEquals(0, $stat1->getOfferBackCommissionAmount());
        $this->assertEquals(0, $stat1->getOfferBackCommissionCount());
        $this->assertEquals(0, $stat1->getOfferCompanyDepositAmount());
        $this->assertEquals(0, $stat1->getOfferCompanyDepositCount());
        $this->assertEquals(0, $stat1->getOfferOnlineDepositAmount());
        $this->assertEquals(0, $stat1->getOfferOnlineDepositCount());
        $this->assertEquals(0, $stat1->getOfferActiveAmount());
        $this->assertEquals(0, $stat1->getOfferActiveCount());
        $this->assertEquals(0, $stat1->getOfferRegisterAmount());
        $this->assertEquals(0, $stat1->getOfferRegisterCount());
        $this->assertEquals(50, $stat1->getOfferAmount());
        $this->assertEquals(5, $stat1->getOfferCount());

        $stat2 = $em->find('BBDurianBundle:StatCashOffer', 2);
        $this->assertEquals('2014-10-12 12:00:00', $stat2->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(7, $stat2->getUserId());
        $this->assertEquals(901, $stat2->getCurrency());
        $this->assertEquals(2, $stat2->getDomain());
        $this->assertEquals(6, $stat2->getParentId());
        $this->assertEquals(0, $stat2->getOfferDepositAmount());
        $this->assertEquals(0, $stat2->getOfferDepositCount());
        $this->assertEquals(0, $stat2->getOfferBackCommissionAmount());
        $this->assertEquals(0, $stat2->getOfferBackCommissionCount());
        $this->assertEquals(20, $stat2->getOfferCompanyDepositAmount());
        $this->assertEquals(2, $stat2->getOfferCompanyDepositCount());
        $this->assertEquals(0, $stat2->getOfferOnlineDepositAmount());
        $this->assertEquals(0, $stat2->getOfferOnlineDepositCount());
        $this->assertEquals(0, $stat2->getOfferActiveAmount());
        $this->assertEquals(0, $stat2->getOfferActiveCount());
        $this->assertEquals(0, $stat2->getOfferRegisterAmount());
        $this->assertEquals(0, $stat2->getOfferRegisterCount());
        $this->assertEquals(20, $stat2->getOfferAmount());
        $this->assertEquals(2, $stat2->getOfferCount());

        $stat3 = $em->find('BBDurianBundle:StatCashOffer', 3);
        $this->assertEquals('2014-10-12 12:00:00', $stat3->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(8, $stat3->getUserId());
        $this->assertEquals(156, $stat3->getCurrency());
        $this->assertEquals(2, $stat3->getDomain());
        $this->assertEquals(7, $stat3->getParentId());
        $this->assertEquals(0, $stat3->getOfferDepositAmount());
        $this->assertEquals(0, $stat3->getOfferDepositCount());
        $this->assertEquals(0, $stat3->getOfferBackCommissionAmount());
        $this->assertEquals(0, $stat3->getOfferBackCommissionCount());
        $this->assertEquals(0, $stat3->getOfferCompanyDepositAmount());
        $this->assertEquals(0, $stat3->getOfferCompanyDepositCount());
        $this->assertEquals(10, $stat3->getOfferOnlineDepositAmount());
        $this->assertEquals(1, $stat3->getOfferOnlineDepositCount());
        $this->assertEquals(100, $stat3->getOfferActiveAmount());
        $this->assertEquals(3, $stat3->getOfferActiveCount());
        $this->assertEquals(0, $stat3->getOfferRegisterAmount());
        $this->assertEquals(0, $stat3->getOfferRegisterCount());
        $this->assertEquals(110, $stat3->getOfferAmount());
        $this->assertEquals(4, $stat3->getOfferCount());

        $stat4 = $em->find('BBDurianBundle:StatCashOffer', 4);
        $this->assertEquals('2014-10-13 12:00:00', $stat4->getAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(8, $stat4->getUserId());
        $this->assertEquals(156, $stat4->getCurrency());
        $this->assertEquals(2, $stat4->getDomain());
        $this->assertEquals(7, $stat4->getParentId());
        $this->assertEquals(0, $stat4->getOfferDepositAmount());
        $this->assertEquals(0, $stat4->getOfferDepositCount());
        $this->assertEquals(0, $stat4->getOfferBackCommissionAmount());
        $this->assertEquals(0, $stat4->getOfferBackCommissionCount());
        $this->assertEquals(0, $stat4->getOfferCompanyDepositAmount());
        $this->assertEquals(0, $stat4->getOfferCompanyDepositCount());
        $this->assertEquals(0, $stat4->getOfferOnlineDepositAmount());
        $this->assertEquals(0, $stat4->getOfferOnlineDepositCount());
        $this->assertEquals(100, $stat4->getOfferActiveAmount());
        $this->assertEquals(1, $stat4->getOfferActiveCount());
        $this->assertEquals(200, $stat4->getOfferRegisterAmount());
        $this->assertEquals(1, $stat4->getOfferRegisterCount());
        $this->assertEquals(300, $stat4->getOfferAmount());
        $this->assertEquals(2, $stat4->getOfferCount());

        // Opcode 不在範圍，不會列入統計
        $repo = $em->getRepository('BBDurianBundle:StatCashOffer');
        $stat = $repo->findOneBy([
            'at' => new \DateTime('2014-10-10 12:00:00'),
            'userId' => 7
        ]);
        $this->assertEmpty($stat);

        $emDefault = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $repo = $emDefault->getRepository('BBDurianBundle:BackgroundProcess');
        $background = $repo->findOneBy(['name' => 'stat-cash-offer']);

        // 驗證補跑前背景最後成功執行時間
        $this->assertEquals('2014-10-13 12:00:00', $background->getLastEndTime()->format('Y-m-d H:i:s'));

        $params = [
            '--start-date' => '2014-8-13',
            '--end-date' => '2014-8-13',
            '--wait-sec' => 0.00001,
            '--recover' => true
        ];
        $this->runCommand('durian:stat-cash-offer', $params);

        $emDefault->refresh($background);
        // 驗證補跑之後背景最後成功執行時間
        $this->assertEquals(0, $background->getNum());
        $this->assertEquals('2014-10-13 12:00:00', $background->getLastEndTime()->format('Y-m-d H:i:s'));
    }
}
