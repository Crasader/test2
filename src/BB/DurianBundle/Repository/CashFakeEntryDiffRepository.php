<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * CashFakeEntryDiffRepository
 */
class CashFakeEntryDiffRepository extends EntityRepository
{

    /**
     * 計算CashFakeEntryDiff數量
     *
     * @return integer
     */
    public function countNumOf()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('count(c)')
           ->from("BBDurianBundle:CashFakeEntryDiff", 'c');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
