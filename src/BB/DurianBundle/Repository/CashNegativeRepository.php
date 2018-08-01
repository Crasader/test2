<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * CashNegativeRepository
 */
class CashNegativeRepository extends EntityRepository
{
    /**
     * 回傳負數清單
     *
     * @param integer $firstResult 起始筆數
     * @param integer $maxResults 回傳筆數
     * @return array
     */
    public function getNegativeList($firstResult, $maxResults)
    {
        $qb = $this->createQueryBuilder('cn');

        $qb->select('cn, c.balance')
            ->innerJoin('BBDurianBundle:Cash', 'c', 'WITH', 'cn.userId = c.user')
            ->where('c.negative = :negative')
            ->andWhere('cn.entryBalance < 0')
            ->setParameter('negative', true)
            ->orderBy('cn.userId', 'ASC')
            ->setFirstResult($firstResult)
            ->setMaxResults($maxResults);

        return $qb->getQuery()->getResult();
    }

    /**
     * 回傳負數清單總筆數
     *
     * @return integer
     */
    public function countNegative()
    {
        $qb = $this->createQueryBuilder('cn');

        $qb->select('COUNT(cn)')
            ->innerJoin('BBDurianBundle:Cash', 'c', 'WITH', 'cn.userId = c.user')
            ->where('c.negative = :negative')
            ->andWhere('cn.entryBalance < 0')
            ->setParameter('negative', true);

        return $qb->getQuery()->getSingleScalarResult();
    }
}
