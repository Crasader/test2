<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * CashFakeNegativeRepository
 */
class CashFakeNegativeRepository extends EntityRepository
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
        $qb = $this->createQueryBuilder('cfn');

        $qb->select('cfn, cf.balance')
            ->innerJoin('BBDurianBundle:CashFake', 'cf', 'WITH', 'cfn.userId = cf.user')
            ->where('cf.negative = :negative')
            ->andWhere('cfn.entryBalance < 0')
            ->setParameter('negative', true)
            ->orderBy('cfn.userId', 'ASC')
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
        $qb = $this->createQueryBuilder('cfn');

        $qb->select('COUNT(cfn)')
            ->innerJoin('BBDurianBundle:CashFake', 'cf', 'WITH', 'cfn.userId = cf.user')
            ->where('cf.negative = :negative')
            ->andWhere('cfn.entryBalance < 0')
            ->setParameter('negative', true);

        return $qb->getQuery()->getSingleScalarResult();
    }
}
