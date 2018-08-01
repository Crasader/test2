<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * DomainAutoRemitRepository
 */
class DomainAutoRemitRepository extends EntityRepository
{
    /**
     * 依傳入參數回傳符合的廳的設定
     *
     * $criteria 包括以下參數:
     *     integer $domain 廳
     * @param array $criteria 查詢條件
     * @return array
     */
    public function getDomainAutoRemitBy($criteria = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('dar.domain');
        $qb->addSelect('dar.autoRemitId as auto_remit_id');
        $qb->addSelect('dar.enable');
        $qb->addSelect('ar.name');
        $qb->from('BBDurianBundle:DomainAutoRemit', 'dar');
        $qb->innerJoin('BBDurianBundle:AutoRemit', 'ar', 'WITH', 'dar.autoRemitId = ar.id');

        if (isset($criteria['domain'])) {
            $qb->where('dar.domain = :domain');
            $qb->setParameter('domain', $criteria['domain']);
        }

        return $qb->getQuery()->getArrayResult();
    }
}
