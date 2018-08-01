<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * CashErrorRepository
 */
class CashErrorRepository extends EntityRepository
{

    /**
     * 計算CashError數量
     *
     * @return integer
     */
    public function countNumOf()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('count(c)')
           ->from("BBDurianBundle:CashError", 'c');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
