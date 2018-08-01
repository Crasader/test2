<?php

namespace BB\DurianBundle\Tests\Repository;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class AbstractStatCashRepositoryTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadStatCashDepositWithdrawData'
        ];

        $this->loadFixtures($classnames);
    }

    /**
     * 測試回傳統計用的 QueryBuilder
     */
    public function testCreateStatQueryBuilder()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:StatCashDepositWithdraw');
        $reflector = new \ReflectionClass('BB\DurianBundle\Repository\StatCashDepositWithdrawRepository');

        $method = $reflector->getMethod('createStatQueryBuilder');
        $method->setAccessible(true);

        $criteria = [
            'start'     => '2013-01-08 12:00:00',
            'end'       => '2013-01-11 12:00:00',
            'user_id'   => 6,
            'parent_id' => 5,
            'domain'    => 2,
            'currency'  => 156
        ];

        $limit = [
            'first_result' => 0,
            'max_results'  => 1
        ];

        $searchSet = [
            [
                'field' => 'withdrawAmount',
                'sign'  => '>=',
                'value' => 1
            ]
        ];

        $orderBy = ['withdraw_amount' => 'DESC'];

        $queryBuilder = $method->invokeArgs($repo, [$criteria, $limit, $searchSet, $orderBy]);
        $expected = 'SELECT s FROM BB\DurianBundle\Entity\StatCashDepositWithdraw s ' .
                    'WHERE s.at >= :start AND s.at <= :end ' .
                    'AND s.userId = :user_id AND s.parentId = :parent_id ' .
                    'AND s.domain = :domain AND s.currency = :currency ' .
                    'HAVING sum(s.withdrawAmount) >= 1 ORDER BY withdraw_amount DESC';

        $this->assertEquals($expected, $queryBuilder->getDQL());
        $this->assertEquals(0, $queryBuilder->getFirstResult());
        $this->assertEquals(1, $queryBuilder->getMaxResults());
    }
}
