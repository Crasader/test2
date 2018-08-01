<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * LoginErrorPerIpRepository
 */
class LoginErrorPerIpRepository extends EntityRepository
{
    /**
     * 總計時間內特定廳ip登入錯誤數量
     *
     * @param array $criteria query條件
     * @return integer
     *
     * @author petty 2014.11.10
     */
    public function sumLoginErrorPerIp($criteria = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('sum(lepi.count)');
        $qb->from('BBDurianBundle:LoginErrorPerIp', 'lepi');

        if (isset($criteria['startTime'])) {
            $qb->andWhere('lepi.at >= :start');
            $qb->setParameter('start', $criteria['startTime']);
        }

        if (isset($criteria['endTime'])) {
            $qb->andWhere('lepi.at <= :end');
            $qb->setParameter('end', $criteria['endTime']);
        }

        if (isset($criteria['domain'])) {
            $qb->andWhere('lepi.domain = :domain');
            $qb->setParameter('domain', $criteria['domain']);
        }

        if (isset($criteria['ip'])) {
            $qb->andWhere('lepi.ip = :ip');
            $qb->setParameter('ip', $criteria['ip']);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 根據搜尋條件回傳登入錯誤IP統計資料
     *
     * @param array $criteria query條件
     * @return ArrayCollection
     *
     * @author billy 2015.07.17
     */
    public function getListBy($criteria = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('lepi');
        $qb->from('BBDurianBundle:LoginErrorPerIp', 'lepi');

        if (isset($criteria['domain'])) {
            $qb->andWhere('lepi.domain = :domain');
            $qb->setParameter('domain', $criteria['domain']);
        }

        if (isset($criteria['ip'])) {
            $ipNumber = ip2long(trim($criteria['ip']));

            $qb->andWhere('lepi.ip = :ip');
            $qb->setParameter('ip', $ipNumber);
        }

        if (isset($criteria['startTime'])) {
            $qb->andWhere('lepi.at >= :start');
            $qb->setParameter('start', $criteria['startTime']);
        }

        if (isset($criteria['endTime'])) {
            $qb->andWhere('lepi.at <= :end');
            $qb->setParameter('end', $criteria['endTime']);
        }

        // 新的資料排前面
        $qb->addOrderBy("lepi.at", 'desc');

        return $qb->getQuery()->getResult();
    }
}
