<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * MaintainWhitelistRepository
 */
class MaintainWhitelistRepository extends EntityRepository
{
    /**
     * 回傳白名單個數
     *
     * @return integer
     */
    public function countNumOf()
    {
        $qb = $this->createQueryBuilder('m');

        $qb->select('count(m)');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
