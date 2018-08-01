<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Common\Util\Inflector;

/**
 * StatCashRemitRepository
 */
class StatCashRemitRepository extends AbstractStatCashRepository
{
    /**
     * 加總會員匯款優惠統計總額
     *
     * @param array $criteria  查詢條件
     * @param array $limit     筆數限制
     * @param array $searchSet GroupHaving查詢條件
     * @param array $orderBy   排序條件
     * @return array
     */
    public function sumStatOfRemitByUser($criteria, $limit, $searchSet, $orderBy)
    {
        $qb = $this->createStatQueryBuilder($criteria, $limit, $searchSet, $orderBy);

        $qb->select('s.userId as user_id');
        $qb->addSelect('sum(s.remitAmount) as remit_amount');
        $qb->addSelect('sum(s.remitCount) as remit_count');
        $qb->addSelect('sum(s.offerRemitAmount) as offer_remit_amount');
        $qb->addSelect('sum(s.offerRemitCount) as offer_remit_count');
        $qb->addSelect('sum(s.offerCompanyRemitAmount) as offer_company_remit_amount');
        $qb->addSelect('sum(s.offerCompanyRemitCount) as offer_company_remit_count');
        $qb->groupBy('s.userId');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 回傳有匯款優惠統計記錄的會員數
     *
     * @param array $criteria  查詢條件
     * @param array $searchSet GroupHaving查詢條件
     * @return integer
     */
    public function countNumOfRemit($criteria, $searchSet)
    {
        $qb = $this->createStatQueryBuilder($criteria, [], $searchSet);

        $qb->select('COUNT(s.userId)');
        $qb->groupBy('s.userId');

        return count($qb->getQuery()->getArrayResult());
    }

    /**
     * 小計會員匯款優惠統計總額
     *
     * @param array $criteria  查詢條件
     * @param array $searchSet GroupHaving查詢條件
     * @return array
     */
    public function sumStatOfRemit($criteria, $searchSet)
    {
        $qb = $this->createStatQueryBuilder($criteria, null, $searchSet);

        $qb->select('sum(s.remitAmount) as remit_amount');
        $qb->addSelect('sum(s.remitCount) as remit_count');
        $qb->addSelect('sum(s.offerRemitAmount) as offer_remit_amount');
        $qb->addSelect('sum(s.offerRemitCount) as offer_remit_count');
        $qb->addSelect('sum(s.offerCompanyRemitAmount) as offer_company_remit_amount');
        $qb->addSelect('sum(s.offerCompanyRemitCount) as offer_company_remit_count');
        $qb->groupBy('s.userId');

        $arrayResults = $qb->getQuery()->getArrayResult();
        $ret = [];

        if ($arrayResults) {
            $ret = [
                'remit_amount' => 0,
                'remit_count' => 0,
                'offer_remit_amount' => 0,
                'offer_remit_count' => 0,
                'offer_company_remit_amount' => 0,
                'offer_company_remit_count' => 0
            ];

            foreach ($arrayResults as $arrayResult) {
                foreach ($arrayResult as $key => $value) {
                    $ret[$key] += $value;
                }
            }
        }

        return $ret;
    }

    /**
     * 加總代理匯款優惠統計總額
     *
     * @param array $criteria  查詢條件
     * @param array $limit     筆數限制
     * @param array $searchSet GroupHaving查詢條件
     * @param array $orderBy   排序條件
     * @return array
     */
    public function sumStatOfRemitByParentId($criteria, $limit, $searchSet, $orderBy)
    {
        $qb = $this->createStatQueryBuilder($criteria, $limit, $searchSet, $orderBy);

        $qb->select('s.parentId as user_id');
        $qb->addSelect('count(DISTINCT s.userId) as total_user');
        $qb->addSelect('sum(s.remitAmount) as remit_amount');
        $qb->addSelect('sum(s.remitCount) as remit_count');
        $qb->addSelect('sum(s.offerRemitAmount) as offer_remit_amount');
        $qb->addSelect('sum(s.offerRemitCount) as offer_remit_count');
        $qb->addSelect('sum(s.offerCompanyRemitAmount) as offer_company_remit_amount');
        $qb->addSelect('sum(s.offerCompanyRemitCount) as offer_company_remit_count');
        $qb->groupBy('s.parentId');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 回傳有匯款優惠統計記錄的代理數
     *
     * @param array $criteria  查詢條件
     * @param array $searchSet GroupHaving查詢條件
     * @return integer
     */
    public function countNumOfRemitByParentId($criteria, $searchSet)
    {
        $qb = $this->createStatQueryBuilder($criteria, [], $searchSet);

        $qb->select('COUNT(s.parentId)');
        $qb->groupBy('s.parentId');

        return count($qb->getQuery()->getArrayResult());
    }
}
