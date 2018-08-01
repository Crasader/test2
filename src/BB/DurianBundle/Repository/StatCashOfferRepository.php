<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Common\Util\Inflector;

/**
 * StatCashOfferRepository
 */
class StatCashOfferRepository extends AbstractStatCashRepository
{
    /**
     * 加總會員優惠統計總額
     *
     * @param array $criteria  查詢條件
     * @param array $limit     筆數限制
     * @param array $searchSet GroupHaving查詢條件
     * @param array $orderBy   排序條件
     * @return array
     */
    public function sumStatOfOfferByUser($criteria, $limit, $searchSet, $orderBy)
    {
        $qb = $this->createStatQueryBuilder($criteria, $limit, $searchSet, $orderBy);

        $qb->select('s.userId as user_id');
        $qb->addSelect('sum(s.offerAmount) as offer_amount');
        $qb->addSelect('sum(s.offerCount) as offer_count');
        $qb->addSelect('sum(s.offerDepositAmount) as offer_deposit_amount');
        $qb->addSelect('sum(s.offerDepositCount) as offer_deposit_count');
        $qb->addSelect('sum(s.offerBackCommissionAmount) as offer_back_commission_amount');
        $qb->addSelect('sum(s.offerBackCommissionCount) as offer_back_commission_count');
        $qb->addSelect('sum(s.offerCompanyDepositAmount) as offer_company_deposit_amount');
        $qb->addSelect('sum(s.offerCompanyDepositCount) as offer_company_deposit_count');
        $qb->addSelect('sum(s.offerOnlineDepositAmount) as offer_online_deposit_amount');
        $qb->addSelect('sum(s.offerOnlineDepositCount) as offer_online_deposit_count');
        $qb->addSelect('sum(s.offerActiveAmount) as offer_active_amount');
        $qb->addSelect('sum(s.offerActiveCount) as offer_active_count');
        $qb->addSelect('sum(s.offerRegisterAmount) as offer_register_amount');
        $qb->addSelect('sum(s.offerRegisterCount) as offer_register_count');
        $qb->groupBy('s.userId');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 回傳有優惠統計記錄的會員數
     *
     * @param array $criteria  查詢條件
     * @param array $searchSet GroupHaving查詢條件
     * @return integer
     */
    public function countNumOfOffer($criteria, $searchSet)
    {
        $qb = $this->createStatQueryBuilder($criteria, [], $searchSet);

        $qb->select('COUNT(s.userId)');
        $qb->groupBy('s.userId');

        return count($qb->getQuery()->getArrayResult());
    }

    /**
     * 小計會員優惠統計總額
     *
     * @param array $criteria  查詢條件
     * @param array $searchSet GroupHaving查詢條件
     * @return array
     */
    public function sumStatOfOffer($criteria, $searchSet)
    {
        $qb = $this->createStatQueryBuilder($criteria, null, $searchSet);

        $qb->select('sum(s.offerAmount) as offer_amount');
        $qb->addSelect('sum(s.offerCount) as offer_count');
        $qb->addSelect('sum(s.offerDepositAmount) as offer_deposit_amount');
        $qb->addSelect('sum(s.offerDepositCount) as offer_deposit_count');
        $qb->addSelect('sum(s.offerBackCommissionAmount) as offer_back_commission_amount');
        $qb->addSelect('sum(s.offerBackCommissionCount) as offer_back_commission_count');
        $qb->addSelect('sum(s.offerCompanyDepositAmount) as offer_company_deposit_amount');
        $qb->addSelect('sum(s.offerCompanyDepositCount) as offer_company_deposit_count');
        $qb->addSelect('sum(s.offerOnlineDepositAmount) as offer_online_deposit_amount');
        $qb->addSelect('sum(s.offerOnlineDepositCount) as offer_online_deposit_count');
        $qb->addSelect('sum(s.offerActiveAmount) as offer_active_amount');
        $qb->addSelect('sum(s.offerActiveCount) as offer_active_count');
        $qb->addSelect('sum(s.offerRegisterAmount) as offer_register_amount');
        $qb->addSelect('sum(s.offerRegisterCount) as offer_register_count');
        $qb->groupBy('s.userId');

        $arrayResults = $qb->getQuery()->getArrayResult();
        $ret = [];

        if ($arrayResults) {
            $ret = [
                'offer_amount'                 => 0,
                'offer_count'                  => 0,
                'offer_deposit_amount'         => 0,
                'offer_deposit_count'          => 0,
                'offer_back_commission_amount' => 0,
                'offer_back_commission_count'  => 0,
                'offer_company_deposit_amount' => 0,
                'offer_company_deposit_count'  => 0,
                'offer_online_deposit_amount'  => 0,
                'offer_online_deposit_count'   => 0,
                'offer_active_amount'          => 0,
                'offer_active_count'           => 0,
                'offer_register_amount'        => 0,
                'offer_register_count'         => 0
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
     * 加總代理優惠統計總額
     *
     * @param array $criteria  查詢條件
     * @param array $limit     筆數限制
     * @param array $searchSet GroupHaving查詢條件
     * @param array $orderBy   排序條件
     * @return array
     */
    public function sumStatOfOfferByParentId($criteria, $limit, $searchSet, $orderBy)
    {
        $qb = $this->createStatQueryBuilder($criteria, $limit, $searchSet, $orderBy);

        $qb->select('s.parentId as user_id');
        $qb->addSelect('count(DISTINCT s.userId) as total_user');
        $qb->addSelect('sum(s.offerAmount) as offer_amount');
        $qb->addSelect('sum(s.offerCount) as offer_count');
        $qb->addSelect('sum(s.offerDepositAmount) as offer_deposit_amount');
        $qb->addSelect('sum(s.offerDepositCount) as offer_deposit_count');
        $qb->addSelect('sum(s.offerBackCommissionAmount) as offer_back_commission_amount');
        $qb->addSelect('sum(s.offerBackCommissionCount) as offer_back_commission_count');
        $qb->addSelect('sum(s.offerCompanyDepositAmount) as offer_company_deposit_amount');
        $qb->addSelect('sum(s.offerCompanyDepositCount) as offer_company_deposit_count');
        $qb->addSelect('sum(s.offerOnlineDepositAmount) as offer_online_deposit_amount');
        $qb->addSelect('sum(s.offerOnlineDepositCount) as offer_online_deposit_count');
        $qb->addSelect('sum(s.offerActiveAmount) as offer_active_amount');
        $qb->addSelect('sum(s.offerActiveCount) as offer_active_count');
        $qb->addSelect('sum(s.offerRegisterAmount) as offer_register_amount');
        $qb->addSelect('sum(s.offerRegisterCount) as offer_register_count');
        $qb->groupBy('s.parentId');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 回傳有優惠統計記錄的代理數
     *
     * @param array $criteria  查詢條件
     * @param array $searchSet GroupHaving查詢條件
     * @return integer
     */
    public function countNumOfOfferByParentId($criteria, $searchSet)
    {
        $qb = $this->createStatQueryBuilder($criteria, [], $searchSet);

        $qb->select('COUNT(s.parentId)');
        $qb->groupBy('s.parentId');

        return count($qb->getQuery()->getArrayResult());
    }
}
