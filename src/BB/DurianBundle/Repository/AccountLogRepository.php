<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * AccountLogRepository
 */
class AccountLogRepository extends EntityRepository
{
    /**
     * 依條件回傳符合的AccountLog
     *
     * @param array $criteria
     * @param integer $firstResult
     * @param integer $maxResults
     * @return ArrayCollection
     */
    public function getAccountLog(
        $criteria = [],
        $firstResult = null,
        $maxResults = null
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('log');
        $qb->from('BBDurianBundle:AccountLog', 'log');

        if (isset($criteria['status'])) {
            $qb->andWhere("log.status = :status")
                ->setParameter('status', $criteria['status']);
        }

        if (isset($criteria['count'])) {
            $qb->andWhere("log.count >= :count")
                ->setParameter('count', $criteria['count']);
        }

        if (isset($criteria['web'])) {
            $qb->andWhere("log.web = :web")
                ->setParameter('web', $criteria['web']);
        }

        if (!is_null($firstResult)) {
            $qb->setFirstResult($firstResult);
        }

        if (!is_null($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 計算AccountLog數量
     *
     * @param array $criteria
     * @return integer
     */
    public function countAccountLog($criteria = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('count(log)');

        $qb->from('BBDurianBundle:AccountLog', 'log');

        if (isset($criteria['status'])) {
            $qb->andWhere("log.status = :status")
                ->setParameter('status', $criteria['status']);
        }

        if (isset($criteria['count'])) {
            $qb->andWhere("log.count >= :count")
                ->setParameter('count', $criteria['count']);
        }

        if (isset($criteria['web'])) {
            $qb->andWhere("log.web = :web")
                ->setParameter('web', $criteria['web']);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}
