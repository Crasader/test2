<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * CardEntryRepository
 */
class CardEntryRepository extends EntityRepository
{
    /**
     * 取得Id最大值
     *
     * @return integer
     */
    public function getMaxId()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('MAX(ce) as maxId')
            ->from('BBDurianBundle:CardEntry', 'ce');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 透過refId取得租卡明細
     *
     * @param integer $refId 參考編號
     * @param array $limit 筆數限制
     * @return array
     */
    public function getEntriesByRefId($refId, $limit = [])
    {
        $qb = $this->createQueryBuilder('ce');

        $qb->select('ce')
            ->where('ce.refId = :refId')
            ->setParameter('refId', $refId);

        if (!is_null($limit['first_result'])) {
            $qb->setFirstResult($limit['first_result']);
        }

        if (!is_null($limit['max_results'])) {
            $qb->setMaxResults($limit['max_results']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 回傳透過ref_id查詢有幾筆交易記錄
     *
     * @param integer $refId 參考編號
     * @return integer
     */
    public function countNumOfByRefId($refId)
    {
        $qb = $this->createQueryBuilder('ce');

        $qb->select('count(ce)')
            ->where('ce.refId = :refId')
            ->setParameter('refId', $refId);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 回傳時間區間內是否存在租卡明細
     *
     * @param integer   $cardId 租卡編號
     * @param \DateTime $start  開始時間
     * @param \DateTime $end    結束時間
     * @param integer   $opcode 要排除的交易代碼
     * @return boolean
     */
    public function hasEntry($cardId, $start, $end, $opcode = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('1')
            ->from('BBDurianBundle:CardEntry', 'ce')
            ->where('ce.card = :cardId')
            ->setParameter('cardId', $cardId)
            ->andWhere('ce.createdAt >= :startTime')
            ->setParameter('startTime', $start)
            ->andWhere('ce.createdAt <= :endTime')
            ->setParameter('endTime', $end)
            ->setMaxResults(1);

        if (isset($opcode)) {
            $qb->andWhere('ce.opcode != :opcode')
                ->setParameter('opcode', $opcode);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * 根據上層使用者條件回傳租卡交易紀錄
     *
     * $criteria 包括以下參數:
     *   integer $parent_id  上層使用者編號 (必要)
     *   integer $depth      搜尋層數
     *   array   $opcode     交易種類
     *   integer $start_time 開始時間 (必要)
     *   integer $end_time   結束時間 (必要)
     *
     * $limit 包括以下參數(非必要):
     *   integer $first_result 起始位置
     *   integer $max_results  回傳最大筆數
     *
     * @param array $criteria 查詢條件
     * @param array $orderBy  排序
     * @param array $limit    分頁參數
     * @return ArrayCollection
     */
    public function getEntriesByParent($criteria, $orderBy = [], $limit = [])
    {
        $qb = $this->createQueryBuilderByParent($criteria);
        $qb->select('ce');

        if (!empty($orderBy)) {
            foreach ($orderBy as $sort => $order) {
                $qb->addOrderBy("ce.$sort", $order);
            }
        }

        if (!is_null($limit['first_result'])) {
            $qb->setFirstResult($limit['first_result']);
        }

        if (!is_null($limit['max_results'])) {
            $qb->setMaxResults($limit['max_results']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 根據上層回傳租卡交易紀錄筆數
     *
     * $criteria 包括以下參數:
     *   integer $parent_id  上層使用者編號 (必要)
     *   integer $depth      搜尋層數
     *   array   $opcode     交易種類
     *   integer $start_time 開始時間 (必要)
     *   integer $end_time   結束時間 (必要)
     *
     * @return integer
     */
    public function countChildEntriesOf($criteria)
    {
        $qb = $this->createQueryBuilderByParent($criteria);
        $qb->select('COUNT(ce.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 根據條件回傳交易紀錄
     *
     * $criteria 包括以下參數:
     *   integer $parent_id  上層使用者編號 (必要)
     *   integer $depth      搜尋層數
     *   array   $opcode     交易種類
     *   integer $start_time 開始時間 (必要)
     *   integer $end_time   結束時間 (必要)
     *
     * @param array $criteria 查詢條件
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function createQueryBuilderByParent($criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->from('BBDurianBundle:CardEntry', 'ce')
            ->from('BBDurianBundle:UserAncestor', 'ua')
            ->where('ce.userId = ua.user')
            ->andWhere('ua.ancestor = :parentId')
            ->setParameter('parentId', $criteria['parent_id'])
            ->andWhere($qb->expr()->between('ce.createdAt', ':start', ':end'))
            ->setParameter('start', $criteria['start_time'])
            ->setParameter('end', $criteria['end_time']);

        if (!is_null($criteria['depth'])) {
            $qb->andWhere('ua.depth <= :depth')
                ->setParameter('depth', $criteria['depth']);
        }

        if (!is_null($criteria['opcode'])) {
            $qb->andWhere('ce.opcode in (:opcode)');
            $qb->setParameter('opcode', $criteria['opcode']);
        }

        return $qb;
    }
}
