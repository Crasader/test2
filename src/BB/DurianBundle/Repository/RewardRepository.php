<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Util\Inflector;

/**
 * RewardRepository
 */
class RewardRepository extends EntityRepository
{
    /**
     * 根據搜尋條件回傳紅包活動列表
     *
     * $criteria
     *   domain:        integer 廳主id
     *   start:         string  搜尋區間起始
     *   end:           string  搜尋區間結束
     *   entry_created: boolean 紅包明細是否建立完成
     *   active:        boolean 活動是否正在進行中
     *   cancel:        boolean 活動是否被取消
     *
     * @param array   $criteria    query條件
     * @param array   $orderBy     排序
     * @param integer $firstResult 資料開頭
     * @param integer $maxResults  資料筆數
     *
     * @return array
     */
    public function getListBy(
        $criteria = [],
        $orderBy = [],
        $firstResult = null,
        $maxResults = null
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('r');
        $qb->from('BBDurianBundle:Reward', 'r');

        if (isset($criteria['domain'])) {
            $qb->andWhere('r.domain = :domain');
            $qb->setParameter('domain', $criteria['domain']);
        }

        // 這邊的搜尋區間會列出區間內舉辦過的活動
        if (isset($criteria['start'])) {
            $qb->andWhere('r.endAt >= :startAt');
            $qb->setParameter('startAt', $criteria['start']);
        }

        if (isset($criteria['end'])) {
            $qb->andWhere('r.beginAt <= :endAt');
            $qb->setParameter('endAt', $criteria['end']);
        }

        if (isset($criteria['entry_created'])) {
            $qb->andWhere('r.entryCreated = :entryCreated');
            $qb->setParameter('entryCreated', $criteria['entry_created']);
        }

        if (isset($criteria['cancel'])) {
            $qb->andWhere('r.cancel = :cancel');
            $qb->setParameter('cancel', $criteria['cancel']);
        }

        if (isset($criteria['active'])) {
            $now = new \DateTime();
            $now->format(\DateTime::ISO8601);

            if ($criteria['active']) {
                $qb->andWhere('r.beginAt <= :now AND r.endAt >= :now');
                $qb->setParameter('now', $now);
            } else {
                $qb->andWhere('r.beginAt > :now OR r.endAt < :now');
                $qb->setParameter('now', $now);
            }
        }

        if ($orderBy) {
            foreach ($orderBy as $sort => $order) {
                $sort = Inflector::camelize($sort);
                $qb->addOrderBy("r.$sort", $order);
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
     * 根據搜尋條件回傳紅包活動數量
     *
     * $criteria
     *   domain:        integer 廳主id
     *   start:         string  搜尋區間起始
     *   end:           string  搜尋區間結束
     *   entry_created: boolean 紅包明細是否建立完成
     *   active:        boolean 活動是否正在進行中
     *   cancel:        boolean 活動是否被取消
     *
     * @param array $criteria query條件
     *
     * @return integer
     */
    public function countListBy($criteria = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('COUNT(r)');
        $qb->from('BBDurianBundle:Reward', 'r');

        if (isset($criteria['domain'])) {
            $qb->andWhere('r.domain = :domain');
            $qb->setParameter('domain', $criteria['domain']);
        }

        // 這邊的搜尋區間會列出區間內舉辦過的活動
        if (isset($criteria['start'])) {
            $qb->andWhere('r.endAt >= :startAt');
            $qb->setParameter('startAt', $criteria['start']);
        }

        if (isset($criteria['end'])) {
            $qb->andWhere('r.beginAt <= :endAt');
            $qb->setParameter('endAt', $criteria['end']);
        }

        if (isset($criteria['entry_created'])) {
            $qb->andWhere('r.entryCreated = :entryCreated');
            $qb->setParameter('entryCreated', $criteria['entry_created']);
        }

        if (isset($criteria['cancel'])) {
            $qb->andWhere('r.cancel = :cancel');
            $qb->setParameter('cancel', $criteria['cancel']);
        }

         if (isset($criteria['active'])) {
            $now = new \DateTime();
            $now->format(\DateTime::ISO8601);

            if ($criteria['active']) {
                $qb->andWhere('r.beginAt <= :now AND r.endAt >= :now');
                $qb->setParameter('now', $now);
            } else {
                $qb->andWhere('r.beginAt > :now OR r.endAt < :now');
                $qb->setParameter('now', $now);
            }
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}
