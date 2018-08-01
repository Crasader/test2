<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * CashEntryDiffRepository
 */
class CashEntryDiffRepository extends EntityRepository
{

    /**
     * 計算CashEntryDiff數量
     *
     * @return integer
     */
    public function countNumOf()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('count(c)')
           ->from("BBDurianBundle:CashEntryDiff", 'c');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
