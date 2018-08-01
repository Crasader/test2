<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Util\Inflector;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * PetitionRepository
 */
class PetitionRepository extends EntityRepository
{
    /**
     * 根據搜尋條件回傳申請單資料
     *
     * @param array   $criteria    query條件
     * @param array   $orderBy     排序
     * @param integer $firstResult 資料開頭
     * @param integer $maxResults  資料筆數
     *
     * @return ArrayCollection
     */
    public function getListBy(
        $criteria = [],
        $orderBy = [],
        $firstResult = null,
        $maxResults = null
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('p');
        $qb->from('BBDurianBundle:Petition', 'p');

        if (isset($criteria['id'])) {
            $qb->andWhere('p.id = :id');
            $qb->setParameter('id', $criteria['id']);
        }

        if (isset($criteria['user_id'])) {
            $qb->andWhere('p.userId = :userId');
            $qb->setParameter('userId', $criteria['user_id']);
        }

        if (isset($criteria['domain'])) {
            $qb->andWhere('p.domain = :domain');
            $qb->setParameter('domain', $criteria['domain']);
        }

        if (isset($criteria['role'])) {
            $qb->andWhere('p.role = :role');
            $qb->setParameter('role', $criteria['role']);
        }

        if (isset($criteria['untreated'])) {
            $qb->andWhere('p.untreated = :untreated');
            $qb->setParameter('untreated', $criteria['untreated']);
        }

        if (isset($criteria['confirm'])) {
            $qb->andWhere('p.confirm = :confirm');
            $qb->setParameter('confirm', $criteria['confirm']);
        }

        if (isset($criteria['cancel'])) {
            $qb->andWhere('p.cancel = :cancel');
            $qb->setParameter('cancel', $criteria['cancel']);
        }

        if ($orderBy) {
            foreach ($orderBy as $sort => $order) {
                $sort = Inflector::camelize($sort);
                $qb->addOrderBy("p.$sort", $order);
            }
        }

        if (!is_null($firstResult)) {
            $qb->setFirstResult($firstResult);
        }

        if (!is_null($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 根據搜尋條件回傳申請單資料數量
     *
     * @param array $criteria query條件
     *
     * @return integer
     */
    public function countListBy($criteria = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('COUNT(p)');
        $qb->from('BBDurianBundle:Petition', 'p');

        if (isset($criteria['id'])) {
            $qb->andWhere('p.id = :id');
            $qb->setParameter('id', $criteria['id']);
        }

        if (isset($criteria['user_id'])) {
            $qb->andWhere('p.userId = :userId');
            $qb->setParameter('userId', $criteria['user_id']);
        }

        if (isset($criteria['domain'])) {
            $qb->andWhere('p.domain = :domain');
            $qb->setParameter('domain', $criteria['domain']);
        }

        if (isset($criteria['role'])) {
            $qb->andWhere('p.role = :role');
            $qb->setParameter('role', $criteria['role']);
        }

        if (isset($criteria['untreated'])) {
            $qb->andWhere('p.untreated = :untreated');
            $qb->setParameter('untreated', $criteria['untreated']);
        }

        if (isset($criteria['confirm'])) {
            $qb->andWhere('p.confirm = :confirm');
            $qb->setParameter('confirm', $criteria['confirm']);
        }

        if (isset($criteria['cancel'])) {
            $qb->andWhere('p.cancel = :cancel');
            $qb->setParameter('cancel', $criteria['cancel']);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}
