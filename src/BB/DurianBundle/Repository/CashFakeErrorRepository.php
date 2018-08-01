<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * CashFakeErrorRepository
 */
class CashFakeErrorRepository extends EntityRepository
{

    /**
     * 計算CashFakeError數量
     *
     * @return integer
     */
    public function countNumOf()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('count(c)')
           ->from("BBDurianBundle:CashFakeError", 'c');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
