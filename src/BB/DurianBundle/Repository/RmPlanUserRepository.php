<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Util\Inflector;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * RmPlanUserRepository
 */
class RmPlanUserRepository extends EntityRepository
{
    /**
     * 根據搜尋條件回傳刪除計畫使用者
     *
     * criteria
     *    plan_id integer 計畫編號
     *    user_id array   使用者id
     *
     * @param array   $criteria    query條件
     * @param array   $orderBy     排序
     * @param integer $firstResult 資料開頭
     * @param integer $maxResults  資料筆數
     * @return ArrayCollection
     */
    public function getPlanUserBy(
        $criteria,
        $orderBy = [],
        $firstResult = null,
        $maxResults = null
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('rpu');
        $qb->from('BBDurianBundle:RmPlanUser', 'rpu');
        $qb->where('rpu.planId = :pid');
        $qb->setParameter('pid', $criteria['plan_id']);

        if (isset($criteria['user_id'])) {
            $qb->andWhere('rpu.userId IN (:userId)');
            $qb->setParameter('userId', $criteria['user_id']);
        }

        if ($orderBy) {
            foreach ($orderBy as $sort => $order) {
                $sort = Inflector::camelize($sort);
                $qb->addOrderBy("rpu.$sort", $order);
            }
        }

        if (isset($firstResult) && isset($maxResults)) {
            $qb->setFirstResult($firstResult);
            $qb->setMaxResults($maxResults);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 根據搜尋條件回傳申請單資料數量
     *
     * criteria
     *    plan_id          integer 計畫編號
     *    remove           boolean 是否刪除
     *    cancel           boolean 是否撤銷
     *    recover_fail     boolean 是否回收額度失敗
     *    get_balance_fail boolean 是否取得額度失敗
     *    user_id          array   使用者id
     *
     * @param array $criteria query條件
     * @return ArrayCollection
     */
    public function countPlanUserBy($criteria = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('COUNT(rpu) as total');
        $qb->addSelect('sum(rpu.remove) as remove');
        $qb->addSelect('sum(rpu.cancel) as cancel');
        $qb->addSelect('sum(rpu.recoverFail) as recover_fail');
        $qb->addSelect('sum(rpu.getBalanceFail) as get_balance_fail');
        $qb->from('BBDurianBundle:RmPlanUser', 'rpu');
        $qb->where('rpu.planId = :pid');
        $qb->setParameter('pid', $criteria['plan_id']);

        if (isset($criteria['remove'])) {
            $qb->andWhere('rpu.remove = :remove');
            $qb->setParameter('remove', $criteria['remove']);
        }

        if (isset($criteria['cancel'])) {
            $qb->andWhere('rpu.cancel = :cancel');
            $qb->setParameter('cancel', $criteria['cancel']);
        }

        if (isset($criteria['recover_fail'])) {
            $qb->andWhere('rpu.recoverFail = :recoverFail');
            $qb->setParameter('recoverFail', $criteria['recover_fail']);
        }

        if (isset($criteria['get_balance_fail'])) {
            $qb->andWhere('rpu.getBalanceFail = :getBalanceFail');
            $qb->setParameter('getBalanceFail', $criteria['get_balance_fail']);
        }

        if (isset($criteria['user_id'])) {
            $qb->andWhere('rpu.userId IN (:userId)');
            $qb->setParameter('userId', $criteria['user_id']);
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 搜尋尚未處理的申請單
     *
     * @param integer $limit 筆數限制
     * @param integer $planId 計畫編號
     * @param boolean $checkKue 檢查是否有發送req至kue
     * @return ArrayCollection
     */
    public function findPlanUser($limit, $planId = null, $checkKue = false)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->select('rpu')
            ->from('BBDurianBundle:RmPlanUser', 'rpu')
            ->innerJoin(
                'BBDurianBundle:RmPlan',
                'rp',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'rpu.planId = rp.id'
            )
            ->where('rp.confirm = 1')
            ->andWhere('rp.finished = 0')
            ->andWhere('rp.queueDone = 1')
            ->andWhere('rpu.remove = 0')
            ->andWhere('rpu.cancel = 0')
            ->andWhere('rpu.recoverFail = 0')
            ->andWhere('rpu.getBalanceFail = 0');

        //撈取尚未發req至kue處理的使用者
        if ($checkKue) {
            $qb->andWhere('rpu.curlKue = 0');
        }

        if ($planId) {
            $qb->andWhere('rpu.planId = :planId')
                ->setParameter('planId', $planId);
        }

        $qb->addOrderBy('rpu.planId', 'ASC')
            ->addOrderBy('rpu.timeoutCount', 'ASC');

        $qb->setMaxResults($limit);

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 撤銷指定刪除計畫下的申請單
     *
     * @param integer $planId 計畫編號
     * @param array   $userId 使用者id
     */
    public function cancelPlanUser($planId, $userId = null)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $now = new \DateTime();
        $at = $now->format('Y-m-d H:i:s');

        $qb->update('BBDurianBundle:RmPlanUser', 'rpu');
        $qb->set('rpu.cancel', 1);
        $qb->set('rpu.modifiedAt', ':now');
        $qb->where('rpu.planId = :planId');
        $qb->setParameter('now', $at);
        $qb->setParameter('planId', $planId);

        if (isset($userId)) {
            $qb->andWhere('rpu.userId IN (:userId)');
            $qb->setParameter('userId', $userId);
        }

        $qb->getQuery()->execute();
    }
}
