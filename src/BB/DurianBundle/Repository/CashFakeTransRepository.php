<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * CashFakeTransRepository
 */
class CashFakeTransRepository extends EntityRepository
{
    /**
     * 取得Id最大值
     *
     * @return integer
     */
    public function getMaxId()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('MAX(cft) as maxId')
            ->from('BBDurianBundle:CashFakeTrans', 'cft');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
