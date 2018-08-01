<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Util\Inflector;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * RmPlanRepository
 */
class RmPlanRepository extends EntityRepository
{
    /**
     * 根據搜尋條件回傳申請單資料
     *
     * criteria
     *    plan_id    integer   計畫編號
     *    parent_id  integer   上層ID
     *    depth      integer   要刪除的帳號與上層相差的層數
     *    level_id   integer   會員層級
     *    created_at \DateTime 使用者建立時間
     *    last_login \DateTime 最後登入時間
     *    creator    string    建立者
     *    untreated  boolean   是否未處理
     *    confirm    boolean   是否確認
     *    cancel     boolean   是否撤銷
     *    finished   boolean   是否完成
     *
     * @param array   $criteria    query條件
     * @param array   $orderBy     排序
     * @param integer $firstResult 資料開頭
     * @param integer $maxResults  資料筆數
     * @return ArrayCollection
     */
    public function getPlanBy(
        $criteria = [],
        $orderBy = [],
        $firstResult = null,
        $maxResults = null
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('rp');
        $qb->from('BBDurianBundle:RmPlan', 'rp');

        if (isset($criteria['level_id'])) {
            if (!is_numeric($criteria['level_id'])) {
                $criteria['level_id'] = null;
            }
            $qb->from('BBDurianBundle:RmPlanLevel', 'rplevel');
            $qb->andWhere('rp.id = rplevel.planId');
            $qb->andWhere('rplevel.levelId = :levelId');
            $qb->setParameter('levelId', $criteria['level_id']);
        }

        if (isset($criteria['plan_id'])) {
            $qb->andWhere('rp.id = :pid');
            $qb->setParameter('pid', $criteria['plan_id']);
        }

        if (isset($criteria['parent_id'])) {
            $qb->andWhere('rp.parentId = :parentId');
            $qb->setParameter('parentId', $criteria['parent_id']);
        }

        if (isset($criteria['depth'])) {
            $qb->andWhere('rp.depth = :depth');
            $qb->setParameter('depth', $criteria['depth']);
        }

        if (isset($criteria['created_at'])) {
            $qb->andWhere('rp.userCreatedAt = :createdAt');
            $qb->setParameter('createdAt', $criteria['created_at']);
        }

        if (isset($criteria['last_login'])) {
            $qb->andWhere('rp.lastLogin = :lastLogin');
            $qb->setParameter('lastLogin', $criteria['last_login']);
        }

        if (isset($criteria['creator'])) {
            $qb->andWhere('rp.creator = :creator');
            $qb->setParameter('creator', $criteria['creator']);
        }

        if (isset($criteria['untreated'])) {
            $qb->andWhere('rp.untreated = :untreated');
            $qb->setParameter('untreated', $criteria['untreated']);
        }

        if (isset($criteria['user_created'])) {
            $qb->andWhere('rp.userCreated = :userCreated');
            $qb->setParameter('userCreated', $criteria['user_created']);
        }

        if (isset($criteria['confirm'])) {
            $qb->andWhere('rp.confirm = :confirm');
            $qb->setParameter('confirm', $criteria['confirm']);
        }

        if (isset($criteria['cancel'])) {
            $qb->andWhere('rp.cancel = :cancel');
            $qb->setParameter('cancel', $criteria['cancel']);
        }

        if (isset($criteria['finished'])) {
            $qb->andWhere('rp.finished = :finished');
            $qb->setParameter('finished', $criteria['finished']);
        }

        if ($orderBy) {
            foreach ($orderBy as $sort => $order) {
                $sort = Inflector::camelize($sort);
                $qb->addOrderBy("rp.$sort", $order);
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
     * 根據搜尋條件回傳申請單數量
     *
     * criteria
     *    plan_id    integer   計畫編號
     *    parent_id  integer   上層ID
     *    depth      integer   要刪除的帳號與上層相差的層數
     *    level_id   integer   會員層級
     *    created_at \DateTime 使用者建立時間
     *    last_login \DateTime 最後登入時間
     *    creator    string    建立者
     *    untreated  boolean   是否未處理
     *    confirm    boolean   是否確認
     *    cancel     boolean   是否撤銷
     *    finished   boolean   是否完成
     *
     * @param array $criteria query條件
     * @return integer
     */
    public function countPlanBy($criteria = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('count(rp)');
        $qb->from('BBDurianBundle:RmPlan', 'rp');

        if (isset($criteria['level_id'])) {
            if (!is_numeric($criteria['level_id'])) {
                $criteria['level_id'] = null;
            }
            $qb->from('BBDurianBundle:RmPlanLevel', 'rplevel');
            $qb->andWhere('rp.id = rplevel.planId');
            $qb->andWhere('rplevel.levelId = :levelId');
            $qb->setParameter('levelId', $criteria['level_id']);
        }

        if (isset($criteria['plan_id'])) {
            $qb->andWhere('rp.id = :pid');
            $qb->setParameter('pid', $criteria['plan_id']);
        }

        if (isset($criteria['parent_id'])) {
            $qb->andWhere('rp.parentId = :parentId');
            $qb->setParameter('parentId', $criteria['parent_id']);
        }

        if (isset($criteria['depth'])) {
            $qb->andWhere('rp.depth = :depth');
            $qb->setParameter('depth', $criteria['depth']);
        }

        if (isset($criteria['created_at'])) {
            $qb->andWhere('rp.userCreatedAt = :createdAt');
            $qb->setParameter('createdAt', $criteria['created_at']);
        }

        if (isset($criteria['last_login'])) {
            $qb->andWhere('rp.lastLogin = :lastLogin');
            $qb->setParameter('lastLogin', $criteria['last_login']);
        }

        if (isset($criteria['creator'])) {
            $qb->andWhere('rp.creator = :creator');
            $qb->setParameter('creator', $criteria['creator']);
        }

        if (isset($criteria['untreated'])) {
            $qb->andWhere('rp.untreated = :untreated');
            $qb->setParameter('untreated', $criteria['untreated']);
        }

        if (isset($criteria['user_created'])) {
            $qb->andWhere('rp.userCreated = :userCreated');
            $qb->setParameter('userCreated', $criteria['user_created']);
        }

        if (isset($criteria['confirm'])) {
            $qb->andWhere('rp.confirm = :confirm');
            $qb->setParameter('confirm', $criteria['confirm']);
        }

        if (isset($criteria['cancel'])) {
            $qb->andWhere('rp.cancel = :cancel');
            $qb->setParameter('cancel', $criteria['cancel']);
        }

        if (isset($criteria['finished'])) {
            $qb->andWhere('rp.finished = :finished');
            $qb->setParameter('finished', $criteria['finished']);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 取得同廳底下未完成的刪除計畫
     *
     * criteria
     *    parent_id integer 上層編號
     *    untreated boolean 是否未處理
     *    confirm   boolean 是否確認
     *    finished  boolean 是否完成
     *
     * @param array $criteria query條件
     * @return ArrayCollection
     */
    public function getPlanWithSameDomain($criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('rp')
            ->from('BBDurianBundle:RmPlan', 'rp')
            ->where($qb->expr()->in('rp.parentId', ':userIds'))
            ->setParameter('userIds', $criteria['parent_id'])
            ->andWhere('rp.userCreatedAt is not null');

        if (isset($criteria['untreated'])) {
            $qb->andWhere('rp.untreated = :untreated');
            $qb->setParameter('untreated', $criteria['untreated']);
        }

        if (isset($criteria['confirm'])) {
            $qb->andWhere('rp.confirm = :confirm');
            $qb->setParameter('confirm', $criteria['confirm']);
        }

        if (isset($criteria['finished'])) {
            $qb->andWhere('rp.finished = :finished');
            $qb->setParameter('finished', $criteria['finished']);
        }

        return $qb->getQuery()->getResult();
    }
}
