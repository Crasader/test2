<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * MerchantCardRecordRepository
 */
class MerchantCardRecordRepository extends EntityRepository
{
    /**
     * 取得租卡商家訊息
     *
     * @param integer $domain 廳
     * @param integer $start 起始時間
     * @param integer $end 結束時間
     * @param array $criteria 查詢條件
     * @return ArrayCollection
     */
    public function getRecords($domain, $start, $end, $criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('mcr');
        $qb->from('BBDurianBundle:MerchantCardRecord', 'mcr');
        $qb->where('mcr.domain = :domain');
        $qb->setParameter('domain', $domain);
        $qb->andWhere('mcr.createdAt >= :start');
        $qb->setParameter('start', $start);
        $qb->andWhere('mcr.createdAt <= :end');
        $qb->setParameter('end', $end);

        if ($criteria['firstResult']) {
            $qb->setFirstResult($criteria['firstResult']);
        }

        if ($criteria['maxResults']) {
            $qb->setMaxResults($criteria['maxResults']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 計算租卡商家訊息數量
     *
     * @param integer $domain 廳
     * @param integer $start 起始時間
     * @param integer $end 結束時間
     * @return integer
     */
    public function countRecords($domain, $start, $end)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('count(mcr)');
        $qb->from('BBDurianBundle:MerchantCardRecord', 'mcr');
        $qb->where('mcr.domain = :domain');
        $qb->setParameter('domain', $domain);
        $qb->andWhere('mcr.createdAt >= :start');
        $qb->setParameter('start', $start);
        $qb->andWhere('mcr.createdAt <= :end');
        $qb->setParameter('end', $end);

        return $qb->getQuery()->getSingleScalarResult();
    }
}
