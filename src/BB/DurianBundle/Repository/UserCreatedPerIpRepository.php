<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * UserCreatedPerIpRepository
 */
class UserCreatedPerIpRepository extends EntityRepository
{
    /**
     * 依條件回傳符合的新增使用者IP統計資訊
     *
     * @param array $criteria
     * @param integer $firstResult
     * @param integer $maxResults
     * @param array $orderBy
     * @return ArrayCollection
     */
    public function getUserCreatedPerIp(
        $criteria = array(),
        $firstResult = null,
        $maxResults = null,
        $orderBy = array()
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('ucpi');
        $qb->from('BB\DurianBundle\Entity\UserCreatedPerIp', 'ucpi');

        if (isset($criteria['startTime'])) {
            $qb->andWhere('ucpi.at >= :start');
            $qb->setParameter('start', $criteria['startTime']);
            unset($criteria['startTime']);
        }

        if (isset($criteria['endTime'])) {
            $qb->andWhere('ucpi.at <= :end');
            $qb->setParameter('end', $criteria['endTime']);
            unset($criteria['endTime']);
        }

        if (isset($criteria['count'])) {
            $qb->andWhere('ucpi.count >= :count');
            $qb->setParameter('count', $criteria['count']);
            unset($criteria['count']);
        }

        foreach ($criteria as $key => $value) {
            $qb->andWhere("ucpi.$key = :$key");
            $qb->setParameter($key, $value);
        }

        if (!is_null($firstResult)) {
            $qb->setFirstResult($firstResult);
        }

        if (!is_null($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        if (!empty($orderBy)) {
            foreach ($orderBy as $sort => $order) {
                $qb->addOrderBy("ucpi.$sort", $order);
            }
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 計算新增使用者統計IP數量
     *
     * @param array $criteria
     * @return integer
     */
    public function countUserCreatedPerIp($criteria = array())
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('count(ucpi)');
        $qb->from('BB\DurianBundle\Entity\UserCreatedPerIp', 'ucpi');

        if (isset($criteria['startTime'])) {
            $qb->andWhere('ucpi.at >= :start');
            $qb->setParameter('start', $criteria['startTime']);
            unset($criteria['startTime']);
        }

        if (isset($criteria['endTime'])) {
            $qb->andWhere('ucpi.at <= :end');
            $qb->setParameter('end', $criteria['endTime']);
            unset($criteria['endTime']);
        }

        if (isset($criteria['count'])) {
            $qb->andWhere('ucpi.count >= :count');
            $qb->setParameter('count', $criteria['count']);
            unset($criteria['count']);
        }

        foreach ($criteria as $key => $value) {
            $qb->andWhere("ucpi.$key = :$key");
            $qb->setParameter($key, $value);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 總計時間內特定廳ip新增數量
     *
     * @param array $criteria query條件
     * @return integer
     */
    public function sumUserCreatedPerIp($criteria = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('sum(ucpi.count)');
        $qb->from('BBDurianBundle:UserCreatedPerIp', 'ucpi');

        if (isset($criteria['startTime'])) {
            $qb->andWhere('ucpi.at >= :start');
            $qb->setParameter('start', $criteria['startTime']);
            unset($criteria['startTime']);
        }

        if (isset($criteria['endTime'])) {
            $qb->andWhere('ucpi.at <= :end');
            $qb->setParameter('end', $criteria['endTime']);
            unset($criteria['endTime']);
        }

        if (isset($criteria['domain'])) {
            $qb->andWhere('ucpi.domain = :domain');
            $qb->setParameter('domain', $criteria['domain']);
        }

        if (isset($criteria['ip'])) {
            $qb->andWhere('ucpi.ip = :ip');
            $qb->setParameter('ip', $criteria['ip']);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 增加次數，預設+1
     *
     * @param integer $id
     * @param integer $count 用於測試碼製作假資料，一般程式流程不需要帶此參數。
     */
    public function increaseCount($id, $count = 1)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->update('BBDurianBundle:UserCreatedPerIp', 'ucpi');
        $qb->set('ucpi.count', 'ucpi.count + :count');
        $qb->where('ucpi.id = :id');
        $qb->setParameter('id', $id);
        $qb->setParameter('count', $count);

        $qb->getQuery()->execute();
    }
}
