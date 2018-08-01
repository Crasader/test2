<?php

namespace BB\DurianBundle\Tests\Repository;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\CreditEntry;

/**
 * 測試CreditRepository
 */
class CreditRepositoryTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCreditData'
        ];

        $this->loadFixtures($classnames);
    }

    /**
     * 測試取得信用額度交易紀錄筆數
     */
    public function testCountNumOf()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:Credit');

        $periodAt = new \DateTime('2014-12-10 00:00:00');
        $creditEntry = new CreditEntry(8, 2, 40000, 100, 3000, $periodAt);
        $creditEntry->setAt(20141210000000);
        $creditEntry->setCreditId(6);
        $creditEntry->setLine(100);
        $creditEntry->setTotalLine(50);
        $em->persist($creditEntry);
        $em->flush();

        $credit = $em->find('BBDurianBundle:Credit', 6);

        $criteria = [
            'opcode'       => [40000, 40001],
            'at_start'     => 20141210000000,
            'at_end'       => 20141210000005,
            'period_start' => '2014-12-10 00:00:00',
            'period_end'   => '2014-12-10 00:00:05',
            'ref_id'       => ''
        ];

        $ret = $repo->countNumOf($credit, $criteria);

        $this->assertEquals(1, $ret);
    }

    /**
     * 測試取得信用額度交易紀錄
     */
    public function testGetEntriesBy()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:Credit');

        $periodAt = new \DateTime('2014-12-10 00:00:00');
        $creditEntry = new CreditEntry(8, 2, 40000, 100, 3000, $periodAt);
        $creditEntry->setAt(20141210000000);
        $creditEntry->setCreditId(6);
        $creditEntry->setLine(100);
        $creditEntry->setTotalLine(50);
        $em->persist($creditEntry);

        $creditEntry = new CreditEntry(8, 2, 40001, 100, 3000, $periodAt);
        $creditEntry->setAt(20141210000000);
        $creditEntry->setCreditId(6);
        $creditEntry->setLine(100);
        $creditEntry->setTotalLine(50);
        $em->persist($creditEntry);

        $em->flush();

        $credit = $em->find('BBDurianBundle:Credit', 6);

        $criteria = [
            'opcode'       => [40000, 40001],
            'at_start'     => 20141210000000,
            'at_end'       => 20141210000005,
            'period_start' => '2014-12-10 00:00:00',
            'period_end'   => '2014-12-10 00:00:05',
            'ref_id'       => '',
            'order_by'     => ['id' => 'desc'],
            'first_result' => 0,
            'max_results'  => 2
        ];

        $ret = $repo->getEntriesBy($credit, $criteria);
        $entry = $ret[0]->ToArray();

        $this->assertEquals(2, $entry['id']);
        $this->assertEquals(6, $entry['credit_id']);
        $this->assertEquals(8, $entry['user_id']);
        $this->assertEquals(2, $entry['group']);
        $this->assertEquals(40001, $entry['opcode']);
    }
}
