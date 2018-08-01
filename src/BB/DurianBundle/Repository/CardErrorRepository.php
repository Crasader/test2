<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * CardErrorRepository
 */
class CardErrorRepository extends EntityRepository
{

    /**
     * 計算CardError數量
     *
     * @return integer
     */
    public function countNumOf()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('count(c)')
           ->from("BBDurianBundle:CardError", 'c');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
