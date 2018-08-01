<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Util\Inflector;

/**
 * RewardEntryRepository
 */
class RewardEntryRepository extends EntityRepository
{
    /**
     * 取得Id最大值
     *
     * @return integer
     */
    public function getMaxId()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('MAX(re) as maxId')
            ->from('BBDurianBundle:RewardEntry', 're');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 根據紅包活動id及搜尋條件回傳紅包明細資料
     *
     * $criteria
     *   reward_id: integer 紅包活動id(必填)
     *   user_id:   integer 使用者id
     *   obtain:    boolean 紅包明細是否已抽中
     *   payoff:    boolean 紅包明細是否已派彩
     *   start:     string  抽中紅包起始時間
     *   end:       string  抽中紅包結束時間
     *
     * @param array   $criteria    query條件
     * @param array   $orderBy     排序
     * @param integer $firstResult 資料開頭
     * @param integer $maxResults  資料筆數
     *
     * @return array
     */
    public function getListByRewardId(
        $criteria = [],
        $orderBy = [],
        $firstResult = null,
        $maxResults = null
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('re');
        $qb->from('BBDurianBundle:RewardEntry', 're');

        $qb->where('re.rewardId = :rewardId');
        $qb->setParameter('rewardId', $criteria['reward_id']);

        if (isset($criteria['user_id'])) {
            $qb->andWhere('re.userId = :userId');
            $qb->setParameter('userId', $criteria['user_id']);
        }

        if (isset($criteria['obtain'])) {
            if ($criteria['obtain']) {
                $qb->andWhere('re.obtainAt IS NOT NULL');
            } else {
                $qb->andWhere('re.obtainAt IS NULL');
            }
        }

        if (isset($criteria['payoff'])) {
            if ($criteria['payoff']) {
                $qb->andWhere('re.payOffAt IS NOT NULL');
            } else {
                $qb->andWhere('re.payOffAt IS NULL');
            }
        }

        if (isset($criteria['start'])) {
            $qb->andWhere('re.obtainAt >= :start');
            $qb->setParameter('start', $criteria['start']);
        }

        if (isset($criteria['end'])) {
            $qb->andWhere('re.obtainAt <= :end');
            $qb->setParameter('end', $criteria['end']);
        }

        if ($orderBy) {
            foreach ($orderBy as $sort => $order) {
                $sort = Inflector::camelize($sort);
                $qb->addOrderBy("re.$sort", $order);
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
     * 根據紅包活動id及搜尋條件回傳紅包明細資料數量
     *
     * $criteria
     *   reward_id: integer 紅包活動id(必填)
     *   user_id:   integer 使用者id
     *   obtain:    boolean 紅包明細是否已抽中
     *   payoff:    boolean 紅包明細是否已派彩
     *   start:     string  抽中紅包起始時間
     *   end:       string  抽中紅包結束時間
     *
     * @param array $criteria query條件
     *
     * @return integer
     */
    public function countListByRewardId($criteria = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('COUNT(re)');
        $qb->from('BBDurianBundle:RewardEntry', 're');

        $qb->where('re.rewardId = :rewardId');
        $qb->setParameter('rewardId', $criteria['reward_id']);

        if (isset($criteria['user_id'])) {
            $qb->andWhere('re.userId = :userId');
            $qb->setParameter('userId', $criteria['user_id']);
        }

        if (isset($criteria['obtain'])) {
            if ($criteria['obtain']) {
                $qb->andWhere('re.obtainAt IS NOT NULL');
            } else {
                $qb->andWhere('re.obtainAt IS NULL');
            }
        }

        if (isset($criteria['payoff'])) {
            if ($criteria['payoff']) {
                $qb->andWhere('re.payOffAt IS NOT NULL');
            } else {
                $qb->andWhere('re.payOffAt IS NULL');
            }
        }

        if (isset($criteria['start'])) {
            $qb->andWhere('re.obtainAt >= :start');
            $qb->setParameter('start', $criteria['start']);
        }

        if (isset($criteria['end'])) {
            $qb->andWhere('re.obtainAt <= :end');
            $qb->setParameter('end', $criteria['end']);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 根據使用者id及搜尋條件回傳紅包明細資料
     * 注意：排除已被取消活動的相關明細
     *
     * $criteria
     *   user_id: integer 使用者id(必填)
     *   start:   string  搜尋抽中紅包的起始時間
     *   end:     string  搜尋抽中紅包的結束時間
     *   payoff:  boolean 是否已派彩
     *
     * @param array   $criteria    query條件
     * @param array   $orderBy     排序
     * @param integer $firstResult 資料開頭
     * @param integer $maxResults  資料筆數
     *
     * @return array
     */
    public function getListByUserId(
        $criteria = [],
        $orderBy = [],
        $firstResult = null,
        $maxResults = null
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('re');
        $qb->from('BBDurianBundle:RewardEntry', 're');
        $qb->innerJoin('BBDurianBundle:Reward', 'r', 'WITH', 'r.id = re.rewardId');

        $qb->where('r.cancel = 0');
        $qb->andWhere('re.userId = :userId');
        $qb->setParameter('userId', $criteria['user_id']);

        if (isset($criteria['start'])) {
            $qb->andWhere('re.obtainAt >= :start');
            $qb->setParameter('start', $criteria['start']);
        }

        if (isset($criteria['end'])) {
            $qb->andWhere('re.obtainAt <= :end');
            $qb->setParameter('end', $criteria['end']);
        }

        if (isset($criteria['payoff'])) {
            if ($criteria['payoff']) {
                $qb->andWhere('re.payOffAt IS NOT NULL');
            } else {
                $qb->andWhere('re.payOffAt IS NULL');
            }
        }

        if ($orderBy) {
            foreach ($orderBy as $sort => $order) {
                $sort = Inflector::camelize($sort);
                $qb->addOrderBy("re.$sort", $order);
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
     * 根據使用者id及搜尋條件回傳紅包明細資料數量
     * 注意：排除已被取消活動的相關明細
     *
     * $criteria
     *   user_id: integer 使用者id(必填)
     *   start:   string  搜尋抽中紅包的起始時間
     *   end:     string  搜尋抽中紅包的結束時間
     *   payoff:  boolean 是否已派彩
     *
     * @param array $criteria query條件
     *
     * @return integer
     */
    public function countListByUserId($criteria = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('count(re)');
        $qb->from('BBDurianBundle:RewardEntry', 're');
        $qb->innerJoin('BBDurianBundle:Reward', 'r', 'WITH', 'r.id = re.rewardId');

        $qb->where('r.cancel = 0');
        $qb->andWhere('re.userId = :userId');
        $qb->setParameter('userId', $criteria['user_id']);

        if (isset($criteria['start'])) {
            $qb->andWhere('re.obtainAt >= :start');
            $qb->setParameter('start', $criteria['start']);
        }

        if (isset($criteria['end'])) {
            $qb->andWhere('re.obtainAt <= :end');
            $qb->setParameter('end', $criteria['end']);
        }

        if (isset($criteria['payoff'])) {
            if ($criteria['payoff']) {
                $qb->andWhere('re.payOffAt IS NOT NULL');
            } else {
                $qb->andWhere('re.payOffAt IS NULL');
            }
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}
