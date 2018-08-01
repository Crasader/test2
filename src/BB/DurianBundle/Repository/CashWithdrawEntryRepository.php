<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use BB\DurianBundle\Entity\Cash;
use BB\DurianBundle\Entity\CashWithdrawEntry;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * CashWithdrawEntryRepository
 */
class CashWithdrawEntryRepository extends EntityRepository
{
    /**
     * 回傳出款明細列表
     *
     * @param array $criteria
     * @param string $startTime
     * @param string $endTime
     * @param string $confirmStartTime
     * @param string $confirmEndTime
     * @param array $orderBy
     * @param integer $firstResult
     * @param integer $maxResults
     * @return ArrayCollection
     */
    public function getWithdrawEntryList(
        $criteria = [],
        $startTime = null,
        $endTime = null,
        $confirmStartTime,
        $confirmEndTime,
        $orderBy,
        $firstResult,
        $maxResults
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('cwe')
            ->from('BBDurianBundle:CashWithdrawEntry', 'cwe')
            ->andWhere("cwe.domain = :domain")
            ->setParameter('domain', $criteria['domain']);

        if (key_exists('status', $criteria)) {
            $qb->andWhere($qb->expr()->in('cwe.status', ':status'))
                ->setParameter('status', $criteria['status']);
        }

        if (key_exists('level_id', $criteria)) {
            $qb->andWhere($qb->expr()->in('cwe.levelId', ':levelId'))
                ->setParameter('levelId', $criteria['level_id']);
        }

        //排除欄位加起來不為0: where cwe.fee + cwe.aduit_charge != 0
        if (key_exists('exclude_zero', $criteria)) {

            foreach ($criteria['exclude_zero'] as $columm) {
                $columm = \Doctrine\Common\Util\Inflector::camelize($columm);
                $columns[] = 'cwe.' . $columm;
            }

            $qb->andWhere(implode(' + ', $columns) . ' != 0');
        }

        if (key_exists('parent_id', $criteria)) {
            $qb->from('BBDurianBundle:UserAncestor', 'ua')
                ->andWhere("cwe.userId = ua.user")
                ->andWhere("ua.ancestor = :ancestor")
                ->setParameter('ancestor', $criteria['parent_id']);
        }

        if (key_exists('user_id', $criteria)) {
            $qb->andWhere('cwe.userId = :userId');
            $qb->setParameter('userId', $criteria['user_id']);
        }

        if (key_exists('currency', $criteria)) {
            $qb->andWhere('cwe.currency = :currency')
                ->setParameter('currency', $criteria['currency']);
        }

        if (key_exists('memo', $criteria)) {
            $qb->andWhere('cwe.memo = :memo')
                ->setParameter('memo', $criteria['memo']);
        }

        if (key_exists('amount_min', $criteria)) {
            $qb->andWhere('cwe.amount >= :amountMin')
                ->setParameter('amountMin', $criteria['amount_min']);
        }

        if (key_exists('amount_max', $criteria)) {
            $qb->andWhere('cwe.amount <= :amountMax')
                ->setParameter('amountMax', $criteria['amount_max']);
        }

        if (isset($criteria['auto_withdraw'])) {
            $qb->andWhere('cwe.autoWithdraw = :autoWithdraw');
            $qb->setParameter('autoWithdraw', $criteria['auto_withdraw']);
        }

        if (isset($criteria['merchant_withdraw_id'])) {
            $qb->andWhere('cwe.merchantWithdrawId = :merchantWithdrawId');
            $qb->setParameter('merchantWithdrawId', $criteria['merchant_withdraw_id']);
        }

        foreach ($orderBy as $sort => $order) {
            if ($sort == 'createdAt') {
                $sort = 'at';
            }

            $qb->addOrderBy("cwe.$sort", $order);
        }

        if ($startTime) {
            $qb->andWhere('cwe.at >= :start')
               ->setParameter('start', $startTime);
        }

        if ($endTime) {
            $qb->andWhere('cwe.at <= :end')
               ->setParameter('end', $endTime);
        }

        if ($confirmStartTime) {
            $qb->andWhere('cwe.confirmAt >= :confirmStart')
               ->setParameter('confirmStart', $confirmStartTime);
        }

        if ($confirmEndTime) {
            $qb->andWhere('cwe.confirmAt <= :confirmEnd')
               ->setParameter('confirmEnd', $confirmEndTime);
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
     * 計算出款明細
     *
     * @param boolean $isSumTotal 是否總計金額
     * @param array $criteria
     * @param string $startTime
     * @param string $endTime
     * @param string $confirmStartTime
     * @param string $confirmEndTime
     * @return integer
     */
    public function totalWithdrawEntryList(
        $isSumTotal = false,
        $criteria = [],
        $startTime = null,
        $endTime = null,
        $confirmStartTime = null,
        $confirmEndTime = null
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('count(cwe) as total');

        if ($isSumTotal) {
            // 出款總人數
            $qb->addSelect('count(distinct cwe.userId) as user_count');

            if (!key_exists('currency', $criteria) || $criteria['currency'] == null) {
                $qb->addSelect(
                    'sum(cwe.amount * cwe.rate) as amount,
                     sum(cwe.fee * cwe.rate) as fee,
                     sum(cwe.aduitFee * cwe.rate) as aduit_fee,
                     sum(cwe.aduitCharge * cwe.rate) as aduit_charge,
                     sum(cwe.deduction * cwe.rate) as deduction,
                     sum(cwe.realAmount * cwe.rate) as real_amount,
                     sum(cwe.autoWithdrawAmount * cwe.rate) as auto_withdraw_amount,
                     sum(cwe.paymentGatewayFee * cwe.rate) as payment_gateway_fee'
                );
            } else {
                $qb->addSelect(
                    'sum(cwe.amount) as amount,
                     sum(cwe.fee) as fee,
                     sum(cwe.aduitFee) as aduit_fee,
                     sum(cwe.aduitCharge) as aduit_charge,
                     sum(cwe.deduction) as deduction,
                     sum(cwe.realAmount) as real_amount,
                     sum(cwe.autoWithdrawAmount) as auto_withdraw_amount,
                     sum(cwe.paymentGatewayFee) as payment_gateway_fee'
                );
            }
        }

        $qb->from('BBDurianBundle:CashWithdrawEntry', 'cwe')
           ->andWhere("cwe.domain = :domain")
           ->setParameter('domain', $criteria['domain']);

        if (key_exists('status', $criteria)) {
            $qb->andWhere($qb->expr()->in('cwe.status', ':status'))
                ->setParameter('status', $criteria['status']);
        }

        if (key_exists('level_id', $criteria)) {
            $qb->andWhere($qb->expr()->in('cwe.levelId', ':levelId'))
                ->setParameter('levelId', $criteria['level_id']);
        }

        //排除欄位加起來不為0: where cwe.fee + cwe.aduit_charge != 0
        if (key_exists('exclude_zero', $criteria)) {

            foreach ($criteria['exclude_zero'] as $columm) {
                $columm = \Doctrine\Common\Util\Inflector::camelize($columm);
                $columns[] = 'cwe.' . $columm;
            }

            $qb->andWhere(implode(' + ', $columns) . ' != 0');
        }

        if (key_exists('parent_id', $criteria)) {
            $qb->from('BBDurianBundle:UserAncestor', 'ua')
                ->andWhere("cwe.userId = ua.user")
                ->andWhere("ua.ancestor = :ancestor")
                ->setParameter('ancestor', $criteria['parent_id']);
        }

        if (key_exists('user_id', $criteria)) {
            $qb->andWhere('cwe.userId = :userId');
            $qb->setParameter('userId', $criteria['user_id']);
        }

        if (key_exists('currency', $criteria)) {
            $qb->andWhere('cwe.currency = :currency')
                ->setParameter('currency', $criteria['currency']);
        }

        if (key_exists('memo', $criteria)) {
            $qb->andWhere('cwe.memo = :memo')
                ->setParameter('memo', $criteria['memo']);
        }

        if (key_exists('amount_min', $criteria)) {
            $qb->andWhere('cwe.amount >= :amountMin')
                ->setParameter('amountMin', $criteria['amount_min']);
        }

        if (key_exists('amount_max', $criteria)) {
            $qb->andWhere('cwe.amount <= :amountMax')
                ->setParameter('amountMax', $criteria['amount_max']);
        }

        if (isset($criteria['auto_withdraw'])) {
            $qb->andWhere('cwe.autoWithdraw = :autoWithdraw');
            $qb->setParameter('autoWithdraw', $criteria['auto_withdraw']);
        }

        if (isset($criteria['merchant_withdraw_id'])) {
            $qb->andWhere('cwe.merchantWithdrawId = :merchantWithdrawId');
            $qb->setParameter('merchantWithdrawId', $criteria['merchant_withdraw_id']);
        }

        if ($startTime) {
            $qb->andWhere('cwe.at >= :start')
               ->setParameter('start', $startTime);
        }

        if ($endTime) {
            $qb->andWhere('cwe.at <= :end')
               ->setParameter('end', $endTime);
        }

        if ($confirmStartTime) {
            $qb->andWhere('cwe.confirmAt >= :confirmStart')
               ->setParameter('confirmStart', $confirmStartTime);
        }

        if ($confirmEndTime) {
            $qb->andWhere('cwe.confirmAt <= :confirmEnd')
               ->setParameter('confirmEnd', $confirmEndTime);
        }

        $totals = $qb->getQuery()->getSingleResult();

        foreach ($totals as $key => $total) {
            if ($key != 'total') {
                $totals[$key] = number_format($total, 4, '.', '');
            }
        }

        return $totals;
    }

    /**
     * 回傳出款明細為陣列
     *
     * @param Cash $cash
     * @param array $criteria
     * @param string $startTime
     * @param string $endTime
     * @param array $orderBy
     * @param integer $firstResult
     * @param integer $maxResults
     * @return ArrayCollection
     */
    public function getWithdrawEntryArray(
        Cash $cash,
        $criteria = [],
        $startTime = null,
        $endTime = null,
        $orderBy = [],
        $firstResult = null,
        $maxResults = null
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('cwe');
        $qb->from('BBDurianBundle:CashWithdrawEntry', 'cwe');
        $qb->where("cwe.cashId = :cashId");
        $qb->setParameter('cashId', $cash->getId());

        if (isset($criteria['status'])) {
            $qb->andWhere($qb->expr()->in('cwe.status', ':status'));
            $qb->setParameter('status', $criteria['status']);
        }

        if (isset($criteria['auto_withdraw'])) {
            $qb->andWhere('cwe.autoWithdraw = :autoWithdraw');
            $qb->setParameter('autoWithdraw', $criteria['auto_withdraw']);
        }

        if (isset($criteria['merchant_withdraw_id'])) {
            $qb->andWhere('cwe.merchantWithdrawId = :merchantWithdrawId');
            $qb->setParameter('merchantWithdrawId', $criteria['merchant_withdraw_id']);
        }

        if (!empty($orderBy)) {
            foreach ($orderBy as $sort => $order) {
                if ($sort == 'createdAt') {
                    $sort = 'at';
                }

                $qb->addOrderBy("cwe.$sort", $order);
            }
        }

        if ($startTime) {
            $qb->andWhere('cwe.at >= :start')
               ->setParameter('start', $startTime);
        }

        if ($endTime) {
            $qb->andWhere('cwe.at <= :end')
               ->setParameter('end', $endTime);
        }

        if (!is_null($firstResult)) {
            $qb->setFirstResult($firstResult);
        }

        if (!is_null($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 計算出款明細
     *
     * @param Cash $cash
     * @param boolean $isSumTotal 是否總計金額
     * @param array $criteria
     * @param string $startTime
     * @param string $endTime
     * @return integer
     */
    public function totalWithdrawEntry(
        Cash $cash,
        $isSumTotal = false,
        $criteria = [],
        $startTime = null,
        $endTime = null
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('count(cwe) as total');

        if ($isSumTotal) {
            $qb->addSelect(
                'sum(cwe.amount) as amount,
                 sum(cwe.fee) as fee,
                 sum(cwe.aduitFee) as aduit_fee,
                 sum(cwe.aduitCharge) as aduit_charge,
                 sum(cwe.deduction) as deduction,
                 sum(cwe.realAmount) as real_amount'
            );
        }

        $qb->from('BBDurianBundle:CashWithdrawEntry', 'cwe');
        $qb->where("cwe.cashId = :cashId");
        $qb->setParameter('cashId', $cash->getId());

        if (isset($criteria['status'])) {
            $qb->andWhere($qb->expr()->in('cwe.status', ':status'));
            $qb->setParameter('status', $criteria['status']);
        }

        if (isset($criteria['auto_withdraw'])) {
            $qb->andWhere('cwe.autoWithdraw = :autoWithdraw');
            $qb->setParameter('autoWithdraw', $criteria['auto_withdraw']);
        }

        if (isset($criteria['merchant_withdraw_id'])) {
            $qb->andWhere('cwe.merchantWithdrawId = :merchantWithdrawId');
            $qb->setParameter('merchantWithdrawId', $criteria['merchant_withdraw_id']);
        }

        if ($startTime) {
            $qb->andWhere('cwe.at >= :start')
               ->setParameter('start', $startTime);
        }

        if ($endTime) {
            $qb->andWhere('cwe.at <= :end')
               ->setParameter('end', $endTime);
        }

        $totals = $qb->getQuery()->getSingleResult();

        return $totals;
    }

    /**
     * 回傳在時間區間內有更新出款明細的使用者
     *
     * @param array   $criteria 查詢條件，目前可指定'at'、'confirm_at'、'status'、'auto_withdraw'、'merchant_withdraw_id'
     * @param array   $orderBy  排序順序
     * @param integer $firstResult 起始筆數
     * @param integer $maxResults  查詢數量
     * @return ArrayCollection
     */
    public function getWithdrawConfirmedList(
        array $criteria,
        $orderBy,
        $firstResult = null,
        $maxResults = null
    ) {
        $status       = $criteria['status'];
        $atStart      = $criteria['at_start'];
        $atEnd        = $criteria['at_end'];
        $confirmStart = $criteria['confirm_start'];
        $confirmEnd   = $criteria['confirm_end'];

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('cwe.userId AS user_id');
        $qb->addSelect('cwe.id');
        $qb->addSelect('cwe.createdAt AS created_at');
        $qb->addSelect('cwe.confirmAt AS confirm_at');
        $qb->from('BBDurianBundle:CashWithdrawEntry', 'cwe');

        $qb->where('cwe.status = :status');
        $qb->setParameter('status', $status);

        if ($atStart) {
            $qb->andWhere('cwe.at >= :atStart');
            $qb->setParameter('atStart', $atStart);
        }

        if ($atEnd) {
            $qb->andWhere('cwe.at <= :atEnd');
            $qb->setParameter('atEnd', $atEnd);
        }

        if ($confirmStart) {
            $qb->andWhere('cwe.confirmAt >= :confirmStart');
            $qb->setParameter('confirmStart', $confirmStart);
        }

        if ($confirmEnd) {
            $qb->andWhere('cwe.confirmAt <= :confirmEnd');
            $qb->setParameter('confirmEnd', $confirmEnd);
        }

        if (isset($criteria['auto_withdraw'])) {
            $qb->andWhere('cwe.autoWithdraw = :autoWithdraw');
            $qb->setParameter('autoWithdraw', $criteria['auto_withdraw']);
        }

        if (isset($criteria['merchant_withdraw_id'])) {
            $qb->andWhere('cwe.merchantWithdrawId = :merchantWithdrawId');
            $qb->setParameter('merchantWithdrawId', $criteria['merchant_withdraw_id']);
        }

        if (!empty($orderBy)) {
            foreach ($orderBy as $sort => $order) {
                if ($sort == 'createdAt') {
                    $sort = 'at';
                }

                $qb->addOrderBy('cwe.'.$sort, $order);
            }
        }

        if (!is_null($firstResult)) {
            $qb->setFirstResult($firstResult);
        }

        if (!is_null($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 計算在時間區間內有更新出款明細的使用者
     *
     * @param array $criteria 查詢條件，目前可指定'at'、'confirm_at'、'status'、'auto_withdraw'、'merchant_withdraw_id'
     * @return ArrayCollection
     */
    public function countWithdrawConfirmedList(array $criteria)
    {
        $status       = $criteria['status'];
        $atStart      = $criteria['at_start'];
        $atEnd        = $criteria['at_end'];
        $confirmStart = $criteria['confirm_start'];
        $confirmEnd   = $criteria['confirm_end'];

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('COUNT(cwe.confirmAt) AS total');
        $qb->from('BBDurianBundle:CashWithdrawEntry', 'cwe');
        $qb->where('cwe.status = :status');
        $qb->setParameter('status', $status);

        if ($atStart) {
            $qb->andWhere('cwe.at >= :start');
            $qb->setParameter('start', $atStart);
        }

        if ($atEnd) {
            $qb->andWhere('cwe.at <= :end');
            $qb->setParameter('end', $atEnd);
        }

        if ($confirmStart) {
            $qb->andWhere('cwe.confirmAt >= :confirmStart');
            $qb->setParameter('confirmStart', $confirmStart);
        }

        if ($confirmEnd) {
            $qb->andWhere('cwe.confirmAt <= :confirmEnd');
            $qb->setParameter('confirmEnd', $confirmEnd);
        }

        if (isset($criteria['auto_withdraw'])) {
            $qb->andWhere('cwe.autoWithdraw = :autoWithdraw');
            $qb->setParameter('autoWithdraw', $criteria['auto_withdraw']);
        }

        if (isset($criteria['merchant_withdraw_id'])) {
            $qb->andWhere('cwe.merchantWithdrawId = :merchantWithdrawId');
            $qb->setParameter('merchantWithdrawId', $criteria['merchant_withdraw_id']);
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 回傳出款統計
     *
     * @param Array  $users
     * @param string $startTime
     * @param string $endTime
     * @param string $confirmStartTime
     * @param string $confirmEndTime
     * @param boolean $autoWithdraw
     * @param string $merchantWithdrawId
     * @return ArrayCollection
     */
    public function getWithdrawStats(
        $users = [],
        $startTime = null,
        $endTime = null,
        $confirmStartTime = null,
        $confirmEndTime = null,
        $autoWithdraw = null,
        $merchantWithdrawId = null
    ) {
        if (empty($users)) {
            return [];
        }

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('cwe.userId AS user_id');
        $qb->addSelect('cwe.cashId as id');
        $qb->addSelect('cwe.currency');
        $qb->addSelect('IDENTITY(u.parent) AS parent_id');
        $qb->addSelect('u.domain');
        $qb->addSelect('count(cwe.id) AS entry_total');
        $qb->addSelect('sum(cwe.realAmount * cwe.rate) AS basic_sum');
        $qb->addSelect('sum(cwe.realAmount) AS user_original_sum');

        $qb->from('BBDurianBundle:User', 'u');
        $qb->from('BBDurianBundle:CashWithdrawEntry', 'cwe');

        $qb->where('u.id = cwe.userId');
        $qb->andWhere($qb->expr()->in('cwe.userId', ':users'));
        $qb->setParameter('users', $users);
        $qb->andWhere('cwe.status = :status');
        $qb->setParameter('status', 1);

        if ($startTime) {
            $qb->andWhere('cwe.at >= :start')
               ->setParameter('start', $startTime);
        }

        if ($endTime) {
            $qb->andWhere('cwe.at <= :end')
               ->setParameter('end', $endTime);
        }

        if ($confirmStartTime) {
            $qb->andWhere('cwe.confirmAt >= :confirmStart')
               ->setParameter('confirmStart', $confirmStartTime);
        }

        if ($confirmEndTime) {
            $qb->andWhere('cwe.confirmAt <= :confirmEnd')
               ->setParameter('confirmEnd', $confirmEndTime);
        }

        if (isset($autoWithdraw)) {
            $qb->andWhere('cwe.autoWithdraw = :autoWithdraw');
            $qb->setParameter('autoWithdraw', $autoWithdraw);
        }

        if (isset($merchantWithdrawId)) {
            $qb->andWhere('cwe.merchantWithdrawId = :merchantWithdrawId');
            $qb->setParameter('merchantWithdrawId', $merchantWithdrawId);
        }

        $qb->groupBy('cwe.userId');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 回傳同使用者上一筆明細
     *
     * @param CashWithdrawEntry $entry
     * @return CashWithdrawEntry
     */
    public function getPreviousWithdrawEntry(CashWithdrawEntry $entry)
    {
        $at = $entry->getCreatedAt()->format('YmdHis');

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('cwe');
        $qb->from('BBDurianBundle:CashWithdrawEntry', 'cwe');
        $qb->where("cwe.userId = :userId");
        $qb->andWhere("cwe.at <= :at");
        $qb->andWhere("cwe.id != :entryId");
        $qb->setParameter('userId', $entry->getUserId());
        $qb->setParameter('at', $at);
        $qb->setParameter('entryId', $entry->getId());
        $qb->addOrderBy('cwe.at', 'desc');
        $qb->addOrderBy('cwe.id', 'desc');
        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * 統計出款扣除額的人數(fee、aduit_fee、aduit_charge、deuction有紀錄的)
     *
     * @param array $criteria
     * @param string $startTime
     * @param string $endTime
     * @param string $confirmStartTime
     * @param string $confirmEndTime
     * @return integer
     */
    public function countWithdrawDeductionList(
        $criteria = [],
        $startTime = null,
        $endTime = null,
        $confirmStartTime = null,
        $confirmEndTime = null
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('count(distinct cwe.userId) as deduction_user_count');
        $qb->from('BBDurianBundle:CashWithdrawEntry', 'cwe')
            ->andWhere("cwe.domain = :domain")
            ->setParameter('domain', $criteria['domain']);

        if (key_exists('status', $criteria)) {
            $qb->andWhere($qb->expr()->in('cwe.status', ':status'))
                ->setParameter('status', $criteria['status']);
        }

        if (key_exists('level_id', $criteria)) {
            $qb->andWhere($qb->expr()->in('cwe.levelId', ':levelId'))
                ->setParameter('levelId', $criteria['level_id']);
        }

        //排除欄位加起來不為0: where cwe.fee + cwe.aduit_charge != 0
        if (key_exists('exclude_zero', $criteria)) {

            foreach ($criteria['exclude_zero'] as $columm) {
                $columm = \Doctrine\Common\Util\Inflector::camelize($columm);
                $columns[] = 'cwe.' . $columm;
            }

            $qb->andWhere(implode(' + ', $columns) . ' != 0');
        }

        if (key_exists('parent_id', $criteria)) {
            $qb->from('BBDurianBundle:UserAncestor', 'ua')
                ->andWhere("cwe.userId = ua.user")
                ->andWhere("ua.ancestor = :ancestor")
                ->setParameter('ancestor', $criteria['parent_id']);
        }

        if (key_exists('user_id', $criteria)) {
            $qb->andWhere('cwe.userId = :userId');
            $qb->setParameter('userId', $criteria['user_id']);
        }

        if (key_exists('currency', $criteria)) {
            $qb->andWhere('cwe.currency = :currency')
                ->setParameter('currency', $criteria['currency']);
        }

        if (key_exists('memo', $criteria)) {
            $qb->andWhere('cwe.memo = :memo')
                ->setParameter('memo', $criteria['memo']);
        }

        if (key_exists('amount_min', $criteria)) {
            $qb->andWhere('cwe.amount >= :amountMin')
                ->setParameter('amountMin', $criteria['amount_min']);
        }

        if (key_exists('amount_max', $criteria)) {
            $qb->andWhere('cwe.amount <= :amountMax')
                ->setParameter('amountMax', $criteria['amount_max']);
        }

        if (isset($criteria['auto_withdraw'])) {
            $qb->andWhere('cwe.autoWithdraw = :autoWithdraw');
            $qb->setParameter('autoWithdraw', $criteria['auto_withdraw']);
        }

        if (isset($criteria['merchant_withdraw_id'])) {
            $qb->andWhere('cwe.merchantWithdrawId = :merchantWithdrawId');
            $qb->setParameter('merchantWithdrawId', $criteria['merchant_withdraw_id']);
        }

        if ($startTime) {
            $qb->andWhere('cwe.at >= :start')
                ->setParameter('start', $startTime);
        }

        if ($endTime) {
            $qb->andWhere('cwe.at <= :end')
                ->setParameter('end', $endTime);
        }

        if ($confirmStartTime) {
            $qb->andWhere('cwe.confirmAt >= :confirmStart')
                ->setParameter('confirmStart', $confirmStartTime);
        }

        if ($confirmEndTime) {
            $qb->andWhere('cwe.confirmAt <= :confirmEnd')
                ->setParameter('confirmEnd', $confirmEndTime);
        }

        $qb->andWhere($qb->expr()->orX(
            'cwe.fee != 0',
            'cwe.aduitFee != 0',
            'cwe.aduitCharge != 0',
            'cwe.deduction != 0'
        ));

        $result = $qb->getQuery()->getSingleResult();

        return $result['deduction_user_count'];
    }

    /**
     * 取得Id最大值
     *
     * @return integer
     */
    public function getMaxId()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('MAX(cwe) as maxId')
            ->from('BBDurianBundle:CashWithdrawEntry', 'cwe');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 取得指定明細之後尚未處理的出款明細
     *
     * @param CashWithdrawEntry $entry
     * @return ArrayCollection
     */
    public function getUntreatedAndLockEntriesAfter($entry)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $status = [
            CashWithdrawEntry::UNTREATED,
            CashWithdrawEntry::LOCK,
            CashWithdrawEntry::SYSTEM_LOCK,
            CashWithdrawEntry::PROCESSING,
        ];

        $qb->select('cwe');
        $qb->from('BBDurianBundle:CashWithdrawEntry', 'cwe');
        $qb->where('cwe.userId = :userId');
        $qb->andWhere('cwe.at >= :at');
        $qb->andWhere('cwe.autoWithdraw = :autoWithdraw');
        $qb->andwhere($qb->expr()->in('cwe.status', ':status'));
        $qb->andWhere('cwe.id >= :id');
        $qb->setParameter('userId', $entry->getUserId());
        $qb->setParameter('at', $entry->getAt()->format('YmdHis'));
        $qb->setParameter('autoWithdraw', $entry->isAutoWithdraw());
        $qb->setParameter('status', $status);
        $qb->setParameter('id', $entry->getId());

        return $qb->getQuery()->getResult();
    }
}
