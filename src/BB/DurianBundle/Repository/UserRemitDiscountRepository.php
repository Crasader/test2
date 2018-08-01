<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * UserRemitDiscountRepository
 */
class UserRemitDiscountRepository extends EntityRepository
{
    /**
     * 回傳會員匯款優惠總金額
     *
     * $criteria
     *   start:  string  查詢區間開始時間
     *   end:    string  查詢區間結束時間
     *   userId: integer 使用者Id
     *
     * @param array $criteria
     * @return integer
     */
    public function getTotalRemitDiscount($criteria)
    {
        $qb = $this->createQueryBuilder('rd');

        $qb->select('sum(rd.discount) as discount');
        $qb->where('rd.periodAt >= :start');
        $qb->setParameter('start', $criteria['start']);
        $qb->andWhere('rd.periodAt <= :end');
        $qb->setParameter('end', $criteria['end']);
        $qb->andWhere('rd.userId = :userId');
        $qb->setParameter('userId', $criteria['userId']);

        return $qb->getQuery()->getSingleScalarResult();
    }
}
