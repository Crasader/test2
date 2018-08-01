<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Common\Util\Inflector;

/**
 * StatCashAllOfferRepository
 */
class StatCashAllOfferRepository extends AbstractStatCashRepository
{
    /**
     * 加總會員全部優惠統計總額
     *
     * @param array $criteria  查詢條件
     * @param array $limit     筆數限制
     * @param array $searchSet GroupHaving查詢條件
     * @param array $orderBy   排序條件
     * @return array
     */
    public function sumStatOfAllOfferByUser($criteria, $limit, $searchSet, $orderBy)
    {
        $qb = $this->createStatQueryBuilder($criteria, $limit, $searchSet, $orderBy);

        $qb->select('s.userId as user_id');
        $qb->addSelect('sum(s.offerRebateRemitAmount) as offer_rebate_remit_amount');
        $qb->addSelect('sum(s.offerRebateRemitCount) as offer_rebate_remit_count');
        $qb->groupBy('s.userId');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 回傳有全部優惠統計記錄的會員數
     *
     * @param array $criteria  查詢條件
     * @param array $searchSet GroupHaving查詢條件
     * @return integer
     */
    public function countNumOfAllOffer($criteria, $searchSet)
    {
        $qb = $this->createStatQueryBuilder($criteria, [], $searchSet);

        $qb->select('COUNT(s.userId)');
        $qb->groupBy('s.userId');

        return count($qb->getQuery()->getArrayResult());
    }

    /**
     * 小計會員全部優惠統計總額
     *
     * @param array $criteria  查詢條件
     * @param array $searchSet GroupHaving查詢條件
     * @return array
     */
    public function sumStatOfAllOffer($criteria, $searchSet)
    {
        $qb = $this->createStatQueryBuilder($criteria, null, $searchSet);

        $qb->select('sum(s.offerRebateRemitAmount) as offer_rebate_remit_amount');
        $qb->addSelect('sum(s.offerRebateRemitCount) as offer_rebate_remit_count');
        $qb->groupBy('s.userId');

        $arrayResults = $qb->getQuery()->getArrayResult();
        $ret = [];

        if ($arrayResults) {
            $ret = [
                'offer_rebate_remit_amount' => 0,
                'offer_rebate_remit_count' => 0
            ];

            foreach ($arrayResults as $arrayResult) {
                $ret['offer_rebate_remit_amount'] += $arrayResult['offer_rebate_remit_amount'];
                $ret['offer_rebate_remit_count'] += $arrayResult['offer_rebate_remit_count'];
            }
        }

        return $ret;
    }

    /**
     * 加總代理全部優惠統計總額
     *
     * @param array $criteria  查詢條件
     * @param array $limit     筆數限制
     * @param array $searchSet GroupHaving查詢條件
     * @param array $orderBy   排序條件
     * @return array
     */
    public function sumStatOfAllOfferByParentId($criteria, $limit, $searchSet, $orderBy)
    {
        $qb = $this->createStatQueryBuilder($criteria, $limit, $searchSet, $orderBy);

        $qb->select('s.parentId as user_id');
        $qb->addSelect('count(DISTINCT s.userId) as total_user');
        $qb->addSelect('sum(s.offerRebateRemitAmount) as offer_rebate_remit_amount');
        $qb->addSelect('sum(s.offerRebateRemitCount) as offer_rebate_remit_count');
        $qb->groupBy('s.parentId');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 回傳有全部優惠統計記錄的代理數
     *
     * @param array $criteria  查詢條件
     * @param array $searchSet GroupHaving查詢條件
     * @return integer
     */
    public function countNumOfAllOfferByParentId($criteria, $searchSet)
    {
        $qb = $this->createStatQueryBuilder($criteria, [], $searchSet);

        $qb->select('COUNT(s.parentId)');
        $qb->groupBy('s.parentId');

        return count($qb->getQuery()->getArrayResult());
    }
}
