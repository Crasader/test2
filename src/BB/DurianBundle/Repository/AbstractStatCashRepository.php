<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Common\Util\Inflector;

/**
 * 抽象化統計現金Repository物件
 */
abstract class AbstractStatCashRepository extends EntityRepository
{
    /**
     * 回傳統計用的 QueryBuilder
     *
     * @param array $criteria  查詢條件
     * @param array $limit     筆數限制
     * @param array $searchSet GroupHaving查詢條件
     * @param array $orderBy   排序條件
     *
     * @return QueryBuilder
     */
    protected function createStatQueryBuilder($criteria = [], $limit = [], $searchSet = [], $orderBy = [])
    {
        $qb = $this->createQueryBuilder('s');

        if (isset($criteria['start'])) {
            $qb->andWhere('s.at >= :start');
            $qb->setParameter('start', $criteria['start']);
        }

        if (isset($criteria['end'])) {
            $qb->andWhere('s.at <= :end');
            $qb->setParameter('end', $criteria['end']);
        }

        if (isset($criteria['user_id'])) {
            $qb->andWhere('s.userId = :user_id');
            $qb->setParameter('user_id', $criteria['user_id']);
        }

        if (isset($criteria['parent_id'])) {
            $qb->andWhere('s.parentId = :parent_id');
            $qb->setparameter('parent_id', $criteria['parent_id']);
        }

        if (isset($criteria['domain'])) {
            $qb->andWhere('s.domain = :domain');
            $qb->setParameter('domain', $criteria['domain']);
        }

        if (isset($criteria['currency'])) {
            $qb->andWhere('s.currency = :currency');
            $qb->setParameter('currency', $criteria['currency']);
        }

        if (0 != count($searchSet)) {
            foreach ($searchSet as $search) {
                $qb->andHaving("sum(s.{$search['field']}) {$search['sign']} {$search['value']}");
            }
        }

        if (isset($limit['first_result'])) {
            $qb->setFirstResult($limit['first_result']);
        }

        if (isset($limit['max_results'])) {
            $qb->setMaxResults($limit['max_results']);
        }

        if (!empty($orderBy)) {
            foreach ($orderBy as $sort => $order) {
                $sort = Inflector::tableize($sort);
                $qb->addOrderBy($sort, $order);
            }
        }

        return $qb;
    }
}
