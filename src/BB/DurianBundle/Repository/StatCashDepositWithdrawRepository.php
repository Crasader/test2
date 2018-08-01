<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Common\Util\Inflector;

/**
 * StatCashDepositWithdrawRepository
 */
class StatCashDepositWithdrawRepository extends AbstractStatCashRepository
{
    /**
     * 加總會員入款統計總額
     *
     * @param array $criteria  查詢條件
     * @param array $limit     筆數限制
     * @param array $searchSet GroupHaving查詢條件
     * @param array $orderBy   排序條件
     * @return array
     */
    public function sumStatOfDepositByUser($criteria, $limit, $searchSet, $orderBy)
    {
        $qb = $this->createStatQueryBuilder($criteria, $limit, $searchSet, $orderBy);

        $qb->select('s.userId as user_id');
        $qb->addSelect('s.currency as currency');
        $qb->addSelect('sum(s.depositAmount) as deposit_amount');
        $qb->addSelect('sum(s.depositCount) as deposit_count');
        $qb->andWhere('s.depositCount != 0');
        $qb->groupBy('s.userId');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 回傳有入款統計記錄的會員數
     *
     * @param array $criteria  查詢條件
     * @param array $searchSet GroupHaving查詢條件
     * @return integer
     */
    public function countNumOfDeposit($criteria, $searchSet)
    {
        $qb = $this->createStatQueryBuilder($criteria, [], $searchSet);

        $qb->select('COUNT(s.userId)');
        $qb->andWhere('s.depositCount != 0');
        $qb->groupBy('s.userId');

        return count($qb->getQuery()->getArrayResult());
    }

    /**
     * 小計會員入款統計總額
     *
     * @param array $criteria  查詢條件
     * @param array $searchSet GroupHaving查詢條件
     * @return array
     */
    public function sumStatOfDeposit($criteria, $searchSet)
    {
        $qb = $this->createStatQueryBuilder($criteria, null, $searchSet);

        $qb->select('sum(s.depositAmount) as deposit_amount');
        $qb->addSelect('sum(s.depositCount) as deposit_count');
        $qb->andWhere('s.depositCount != 0');
        $qb->groupBy('s.userId');

        $arrayResults = $qb->getQuery()->getArrayResult();
        $ret = [];

        if ($arrayResults) {
            $ret = [
                'deposit_amount' => 0,
                'deposit_count' => 0
            ];

            foreach ($arrayResults as $arrayResult) {
                $ret['deposit_amount'] += $arrayResult['deposit_amount'];
                $ret['deposit_count'] += $arrayResult['deposit_count'];
            }
        }

        return $ret;
    }

    /**
     * 加總會員出款統計總額
     *
     * @param array $criteria  查詢條件
     * @param array $limit     筆數限制
     * @param array $searchSet GroupHaving查詢條件
     * @param array $orderBy   排序條件
     * @return array
     */
    public function sumStatOfWithdrawByUser($criteria, $limit, $searchSet, $orderBy)
    {
        $qb = $this->createStatQueryBuilder($criteria, $limit, $searchSet, $orderBy);

        $qb->select('s.userId as user_id');
        $qb->addSelect('s.currency as currency');
        $qb->addSelect('sum(s.withdrawAmount) as withdraw_amount');
        $qb->addSelect('sum(s.withdrawCount) as withdraw_count');
        $qb->andWhere('s.withdrawCount != 0');
        $qb->groupBy('s.userId');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 回傳有出款統計記錄的會員數
     *
     * @param array $criteria  查詢條件
     * @param array $searchSet GroupHaving查詢條件
     * @return integer
     */
    public function countNumOfWithdraw($criteria, $searchSet)
    {
        $qb = $this->createStatQueryBuilder($criteria, [], $searchSet);

        $qb->select('COUNT(s.userId)');
        $qb->andWhere('s.withdrawCount != 0');
        $qb->groupBy('s.userId');

        return count($qb->getQuery()->getArrayResult());
    }

    /**
     * 小計會員出款統計總額
     *
     * @param array $criteria  查詢條件
     * @param array $searchSet GroupHaving查詢條件
     * @return array
     */
    public function sumStatOfWithdraw($criteria, $searchSet)
    {
        $qb = $this->createStatQueryBuilder($criteria, null, $searchSet);

        $qb->select('sum(s.withdrawAmount) as withdraw_amount');
        $qb->addSelect('sum(s.withdrawCount) as withdraw_count');
        $qb->andWhere('s.withdrawCount != 0');
        $qb->groupBy('s.userId');

        $arrayResults = $qb->getQuery()->getArrayResult();
        $ret = [];

        if ($arrayResults) {
            $ret = [
                'withdraw_amount' => 0,
                'withdraw_count' => 0
            ];

            foreach ($arrayResults as $arrayResult) {
                $ret['withdraw_amount'] += $arrayResult['withdraw_amount'];
                $ret['withdraw_count'] += $arrayResult['withdraw_count'];
            }
        }

        return $ret;
    }

    /**
     * 加總會員出入款統計總額
     *
     * @param array $criteria  查詢條件
     * @param array $limit     筆數限制
     * @param array $searchSet GroupHaving查詢條件
     * @param array $orderBy   排序條件
     * @return array
     */
    public function sumStatOfDepositWithdrawByUser($criteria, $limit, $searchSet, $orderBy)
    {
        $qb = $this->createStatQueryBuilder($criteria, $limit, $searchSet, $orderBy);

        $qb->select('s.userId as user_id');
        $qb->addSelect('s.currency as currency');
        $qb->addSelect('sum(s.depositWithdrawAmount) as deposit_withdraw_amount');
        $qb->addSelect('sum(s.depositWithdrawCount) as deposit_withdraw_count');
        $qb->addSelect('sum(s.depositAmount) as deposit_amount');
        $qb->addSelect('sum(s.depositCount) as deposit_count');
        $qb->addSelect('sum(s.withdrawAmount) as withdraw_amount');
        $qb->addSelect('sum(s.withdrawCount) as withdraw_count');
        $qb->groupBy('s.userId');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 回傳有出入款統計記錄的會員數
     *
     * @param array $criteria  查詢條件
     * @param array $searchSet GroupHaving查詢條件
     * @return integer
     */
    public function countNumOfDepositWithdraw($criteria, $searchSet)
    {
        $qb = $this->createStatQueryBuilder($criteria, [], $searchSet);

        $qb->select('COUNT(s.userId)');
        $qb->groupBy('s.userId');

        return count($qb->getQuery()->getArrayResult());
    }

    /**
     * 小計會員出入款統計總額
     *
     * @param array $criteria  查詢條件
     * @param array $searchSet GroupHaving查詢條件
     * @return array
     */
    public function sumStatOfDepositWithdraw($criteria, $searchSet)
    {
        $qb = $this->createStatQueryBuilder($criteria, null, $searchSet);

        $qb->select('sum(s.depositWithdrawAmount) as deposit_withdraw_amount');
        $qb->addSelect('sum(s.depositWithdrawCount) as deposit_withdraw_count');
        $qb->addSelect('sum(s.depositAmount) as deposit_amount');
        $qb->addSelect('sum(s.depositCount) as deposit_count');
        $qb->addSelect('sum(s.withdrawAmount) as withdraw_amount');
        $qb->addSelect('sum(s.withdrawCount) as withdraw_count');
        $qb->groupBy('s.userId');

        $arrayResults = $qb->getQuery()->getArrayResult();
        $ret = [];

        if ($arrayResults) {
            $ret = [
                'deposit_withdraw_amount' => 0,
                'deposit_withdraw_count'  => 0,
                'deposit_amount'          => 0,
                'deposit_count'           => 0,
                'withdraw_amount'         => 0,
                'withdraw_count'          => 0
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
     * 加總代理入款統計總額
     *
     * @param array $criteria  查詢條件
     * @param array $limit     筆數限制
     * @param array $searchSet GroupHaving查詢條件
     * @param array $orderBy   排序條件
     * @return array
     */
    public function sumStatOfDepositByParentId($criteria, $limit, $searchSet, $orderBy)
    {
        $qb = $this->createStatQueryBuilder($criteria, $limit, $searchSet, $orderBy);

        $qb->select('s.parentId as user_id');
        $qb->addSelect('s.currency as currency');
        $qb->addSelect('count(DISTINCT s.userId) as total_user');
        $qb->addSelect('sum(s.depositAmount) as deposit_amount');
        $qb->addSelect('sum(s.depositCount) as deposit_count');
        $qb->andWhere('s.depositCount != 0');
        $qb->groupBy('s.parentId');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 回傳有入款統計記錄的代理數
     *
     * @param array $criteria  查詢條件
     * @param array $searchSet GroupHaving查詢條件
     * @return integer
     */
    public function countNumOfDepositByParentId($criteria, $searchSet)
    {
        $qb = $this->createStatQueryBuilder($criteria, [], $searchSet);

        $qb->select('COUNT(s.parentId)');
        $qb->andWhere('s.depositCount != 0');
        $qb->groupBy('s.parentId');

        return count($qb->getQuery()->getArrayResult());
    }

    /**
     * 加總代理出款統計總額
     *
     * @param array $criteria  查詢條件
     * @param array $limit     筆數限制
     * @param array $searchSet GroupHaving查詢條件
     * @param array $orderBy   排序條件
     * @return array
     */
    public function sumStatOfWithdrawByParentId($criteria, $limit, $searchSet, $orderBy)
    {
        $qb = $this->createStatQueryBuilder($criteria, $limit, $searchSet, $orderBy);

        $qb->select('s.parentId as user_id');
        $qb->addSelect('s.currency as currency');
        $qb->addSelect('count(DISTINCT s.userId) as total_user');
        $qb->addSelect('sum(s.withdrawAmount) as withdraw_amount');
        $qb->addSelect('sum(s.withdrawCount) as withdraw_count');
        $qb->andWhere('s.withdrawCount != 0');
        $qb->groupBy('s.parentId');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 回傳有出款統計記錄的代理數
     *
     * @param array $criteria  查詢條件
     * @param array $searchSet GroupHaving查詢條件
     * @return integer
     */
    public function countNumOfWithdrawByParentId($criteria, $searchSet)
    {
        $qb = $this->createStatQueryBuilder($criteria, [], $searchSet);

        $qb->select('COUNT(s.parentId)');
        $qb->andWhere('s.withdrawCount != 0');
        $qb->groupBy('s.parentId');

        return count($qb->getQuery()->getArrayResult());
    }

    /**
     * 加總代理出入款統計總額
     *
     * @param array $criteria  查詢條件
     * @param array $limit     筆數限制
     * @param array $searchSet GroupHaving查詢條件
     * @param array $orderBy   排序條件
     * @return array
     */
    public function sumStatOfDepositWithdrawByParentId($criteria, $limit, $searchSet, $orderBy)
    {
        $qb = $this->createStatQueryBuilder($criteria, $limit, $searchSet, $orderBy);

        $qb->select('s.parentId as user_id');
        $qb->addSelect('s.currency as currency');
        $qb->addSelect('count(DISTINCT s.userId) as total_user');
        $qb->addSelect('sum(s.depositWithdrawAmount) as deposit_withdraw_amount');
        $qb->addSelect('sum(s.depositWithdrawCount) as deposit_withdraw_count');
        $qb->addSelect('sum(s.depositAmount) as deposit_amount');
        $qb->addSelect('sum(s.depositCount) as deposit_count');
        $qb->addSelect('sum(s.withdrawAmount) as withdraw_amount');
        $qb->addSelect('sum(s.withdrawCount) as withdraw_count');
        $qb->groupBy('s.parentId');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 回傳有出入款統計記錄的代理數
     *
     * @param array $criteria  查詢條件
     * @param array $searchSet GroupHaving查詢條件
     * @return integer
     */
    public function countNumOfDepositWithdrawByParentId($criteria, $searchSet)
    {
        $qb = $this->createStatQueryBuilder($criteria, [], $searchSet);

        $qb->select('COUNT(s.parentId)');
        $qb->groupBy('s.parentId');

        return count($qb->getQuery()->getArrayResult());
    }
}
