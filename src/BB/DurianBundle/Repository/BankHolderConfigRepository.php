<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * BankHolderConfigRepository
 */
class BankHolderConfigRepository extends EntityRepository
{
    /**
     * 取得符合條件的會員id
     *
     * $criteria 包括以下參數:
     *     array userIds 會員id
     *     integer domain 廳
     *
     * @param array $criteria 查詢條件
     * @param integer $firstResult 起始筆數
     * @param integer $maxResults  最大筆數
     * @return array
     */
    public function getUserIdBy($criteria, $firstResult, $maxResults)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('bhc.userId as user_id');
        $qb->addSelect('bhc.editHolder as edit_holder');
        $qb->from('BBDurianBundle:BankHolderConfig', 'bhc');

        if (isset($criteria['userIds'])) {
            $qb->andWhere($qb->expr()->in('bhc.userId', ':userIds'));
            $qb->setParameter('userIds', $criteria['userIds']);
        }

        if (isset($criteria['domain'])) {
            $qb->andWhere('bhc.domain = :domain');
            $qb->setParameter('domain', $criteria['domain']);
        }

        if (isset($criteria['edit_holder'])) {
            $qb->andWhere('bhc.editHolder = :editHolder');
            $qb->setParameter('editHolder', $criteria['edit_holder']);
        }

        if (!is_null($firstResult)) {
            $qb->setFirstResult($firstResult);
        }

        if (!is_null($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 取得符合條件的會員id
     *
     * $criteria 包括以下參數:
     *     array userIds 會員id
     *     integer domain 廳
     *
     * @param array $criteria 查詢條件
     * @return array
     */
    public function countUserIdBy($criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('count(bhc)');
        $qb->from('BBDurianBundle:BankHolderConfig', 'bhc');

        if (isset($criteria['userIds'])) {
            $qb->andWhere($qb->expr()->in('bhc.userId', ':userIds'));
            $qb->setParameter('userIds', $criteria['userIds']);
        }

        if (isset($criteria['domain'])) {
            $qb->andWhere('bhc.domain = :domain');
            $qb->setParameter('domain', $criteria['domain']);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}
