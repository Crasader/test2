<?php
namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * AutoConfirmEntryRepository
 */
class AutoConfirmEntryRepository extends EntityRepository
{
    /**
     * 回傳符合條件的匯款記錄筆數
     *
     * @param array $entryCriteria 匯款記錄的指定條件，用"="去查詢欄位資料
     * @param array $rangeCriteria 範圍區間的查詢條件，目前支援的參數有
     *     $rangeCriteria['createdStart']
     *     $rangeCriteria['createdEnd']
     *     $rangeCriteria['confirmStart']
     *     $rangeCriteria['confirmEnd']
     *     $rangeCriteria['tradeStart']
     *     $rangeCriteria['tradeEnd']
     *     $rangeCriteria['amountMin']
     *     $rangeCriteria['amountMax']
     *     $rangeCriteria['feeMin']
     *     $rangeCriteria['feeMax']
     *     $rangeCriteria['balanceMin']
     *     $rangeCriteria['balanceMax']
     * @return integer
     */
    public function countEntriesBy($entryCriteria, $rangeCriteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('COUNT(ace)');
        $qb->from('BBDurianBundle:AutoConfirmEntry', 'ace');

        if (isset($rangeCriteria['createdStart'])) {
            $qb->andWhere('ace.createdAt >= :createdStart');
            $qb->setParameter('createdStart', $rangeCriteria['createdStart']);
        }

        if (isset($rangeCriteria['createdEnd'])) {
            $qb->andWhere('ace.createdAt <= :createdEnd');
            $qb->setParameter('createdEnd', $rangeCriteria['createdEnd']);
        }

        if (isset($rangeCriteria['confirmStart'])) {
            $qb->andWhere('ace.confirmAt >= :confirmStart');
            $qb->setParameter('confirmStart', $rangeCriteria['confirmStart']);
        }

        if (isset($rangeCriteria['confirmEnd'])) {
            $qb->andWhere('ace.confirmAt <= :confirmEnd');
            $qb->setParameter('confirmEnd', $rangeCriteria['confirmEnd']);
        }

        if (isset($rangeCriteria['tradeStart'])) {
            $qb->andWhere('ace.tradeAt >= :tradeStart');
            $qb->setParameter('tradeStart', $rangeCriteria['tradeStart']);
        }

        if (isset($rangeCriteria['tradeEnd'])) {
            $qb->andWhere('ace.tradeAt <= :tradeEnd');
            $qb->setParameter('tradeEnd', $rangeCriteria['tradeEnd']);
        }

        if (isset($rangeCriteria['amountMin'])) {
            $qb->andWhere('ace.amount >= :amountMin');
            $qb->setParameter('amountMin', $rangeCriteria['amountMin']);
        }

        if (isset($rangeCriteria['amountMax'])) {
            $qb->andWhere('ace.amount <= :amountMax');
            $qb->setParameter('amountMax', $rangeCriteria['amountMax']);
        }

        if (isset($rangeCriteria['feeMin'])) {
            $qb->andWhere('ace.fee >= :feeMin');
            $qb->setParameter('feeMin', $rangeCriteria['feeMin']);
        }

        if (isset($rangeCriteria['feeMax'])) {
            $qb->andWhere('ace.fee <= :feeMax');
            $qb->setParameter('feeMax', $rangeCriteria['feeMax']);
        }

        if (isset($rangeCriteria['balanceMin'])) {
            $qb->andWhere('ace.balance >= :balanceMin');
            $qb->setParameter('balanceMin', $rangeCriteria['balanceMin']);
        }

        if (isset($rangeCriteria['balanceMax'])) {
            $qb->andWhere('ace.balance <= :balanceMax');
            $qb->setParameter('balanceMax', $rangeCriteria['balanceMax']);
        }

        foreach ($entryCriteria as $key => $value) {
            $qb->andWhere("ace.$key = :$key");
            $qb->setParameter($key, $value);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 回傳符合條件的匯款記錄
     *
     * @param array $entryCriteria 匯款記錄的指定條件，用"="去查詢欄位資料
     * @param array $rangeCriteria 範圍區間的查詢條件，目前支援的參數有
     *     $rangeCriteria['createdStart']
     *     $rangeCriteria['createdEnd']
     *     $rangeCriteria['confirmStart']
     *     $rangeCriteria['confirmEnd']
     *     $rangeCriteria['tradeStart']
     *     $rangeCriteria['tradeEnd']
     *     $rangeCriteria['amountMin']
     *     $rangeCriteria['amountMax']
     *     $rangeCriteria['feeMin']
     *     $rangeCriteria['feeMax']
     *     $rangeCriteria['balanceMin']
     *     $rangeCriteria['balanceMax']
     * @param array $orderBy 排序條件
     * @param integer $firstResult 分頁起始值
     * @param integer $maxResults 分頁數量
     * @return array
     */
    public function getEntriesBy(
        $entryCriteria,
        $rangeCriteria,
        $orderBy = [],
        $firstResult = null,
        $maxResults = null
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('ace');
        $qb->from('BBDurianBundle:AutoConfirmEntry', 'ace');

        if (isset($rangeCriteria['createdStart'])) {
            $qb->andWhere('ace.createdAt >= :createdStart');
            $qb->setParameter('createdStart', $rangeCriteria['createdStart']);
        }

        if (isset($rangeCriteria['createdEnd'])) {
            $qb->andWhere('ace.createdAt <= :createdEnd');
            $qb->setParameter('createdEnd', $rangeCriteria['createdEnd']);
        }

        if (isset($rangeCriteria['confirmStart'])) {
            $qb->andWhere('ace.confirmAt >= :confirmStart');
            $qb->setParameter('confirmStart', $rangeCriteria['confirmStart']);
        }

        if (isset($rangeCriteria['confirmEnd'])) {
            $qb->andWhere('ace.confirmAt <= :confirmEnd');
            $qb->setParameter('confirmEnd', $rangeCriteria['confirmEnd']);
        }

        foreach ($entryCriteria as $key => $value) {
            $qb->andWhere("ace.$key = :$key");
            $qb->setParameter($key, $value);
        }

        if (isset($rangeCriteria['tradeStart'])) {
            $qb->andWhere('ace.tradeAt >= :tradeStart');
            $qb->setParameter('tradeStart', $rangeCriteria['tradeStart']);
        }

        if (isset($rangeCriteria['tradeEnd'])) {
            $qb->andWhere('ace.tradeAt <= :tradeEnd');
            $qb->setParameter('tradeEnd', $rangeCriteria['tradeEnd']);
        }

        if (isset($rangeCriteria['amountMin'])) {
            $qb->andWhere('ace.amount >= :amountMin');
            $qb->setParameter('amountMin', $rangeCriteria['amountMin']);
        }

        if (isset($rangeCriteria['amountMax'])) {
            $qb->andWhere('ace.amount <= :amountMax');
            $qb->setParameter('amountMax', $rangeCriteria['amountMax']);
        }

        if (isset($rangeCriteria['feeMin'])) {
            $qb->andWhere('ace.fee >= :feeMin');
            $qb->setParameter('feeMin', $rangeCriteria['feeMin']);
        }

        if (isset($rangeCriteria['feeMax'])) {
            $qb->andWhere('ace.fee <= :feeMax');
            $qb->setParameter('feeMax', $rangeCriteria['feeMax']);
        }

        if (isset($rangeCriteria['balanceMin'])) {
            $qb->andWhere('ace.balance >= :balanceMin');
            $qb->setParameter('balanceMin', $rangeCriteria['balanceMin']);
        }

        if (isset($rangeCriteria['balanceMax'])) {
            $qb->andWhere('ace.balance <= :balanceMax');
            $qb->setParameter('balanceMax', $rangeCriteria['balanceMax']);
        }

        if (!is_null($firstResult)) {
            $qb->setFirstResult($firstResult);
        }

        if (!is_null($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        if (!empty($orderBy)) {
            foreach ($orderBy as $sort => $order) {
                $qb->addOrderBy("ace.$sort", $order);
            }
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 回傳符合條件的匯入記錄金額總合
     *
     * @param array $entryCriteria 匯款記錄的指定條件，用"="去查詢欄位資料
     * @param array $rangeCriteria 範圍區間的查詢條件，目前支援的參數有
     *     $rangeCriteria['createdStart']
     *     $rangeCriteria['createdEnd']
     *     $rangeCriteria['confirmStart']
     *     $rangeCriteria['confirmEnd']
     *     $rangeCriteria['tradeStart']
     *     $rangeCriteria['tradeEnd']
     *     $rangeCriteria['amountMin']
     *     $rangeCriteria['amountMax']
     *     $rangeCriteria['feeMin']
     *     $rangeCriteria['feeMax']
     *     $rangeCriteria['balanceMin']
     *     $rangeCriteria['balanceMax']
     * @return array
     */
    public function sumEntriesBy($entryCriteria, $rangeCriteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('COALESCE(sum(ace.amount), 0) as amount');
        $qb->from('BBDurianBundle:AutoConfirmEntry', 'ace');

        if (isset($rangeCriteria['createdStart'])) {
            $qb->andWhere('ace.createdAt >= :createdStart');
            $qb->setParameter('createdStart', $rangeCriteria['createdStart']);
        }

        if (isset($rangeCriteria['createdEnd'])) {
            $qb->andWhere('ace.createdAt <= :createdEnd');
            $qb->setParameter('createdEnd', $rangeCriteria['createdEnd']);
        }

        if (isset($rangeCriteria['confirmStart'])) {
            $qb->andWhere('ace.confirmAt >= :confirmStart');
            $qb->setParameter('confirmStart', $rangeCriteria['confirmStart']);
        }

        if (isset($rangeCriteria['confirmEnd'])) {
            $qb->andWhere('ace.confirmAt <= :confirmEnd');
            $qb->setParameter('confirmEnd', $rangeCriteria['confirmEnd']);
        }

        foreach ($entryCriteria as $key => $value) {
            $qb->andWhere("ace.$key = :$key");
            $qb->setParameter($key, $value);
        }

        if (isset($rangeCriteria['tradeStart'])) {
            $qb->andWhere('ace.tradeAt >= :tradeStart');
            $qb->setParameter('tradeStart', $rangeCriteria['tradeStart']);
        }

        if (isset($rangeCriteria['tradeEnd'])) {
            $qb->andWhere('ace.tradeAt <= :tradeEnd');
            $qb->setParameter('tradeEnd', $rangeCriteria['tradeEnd']);
        }

        if (isset($rangeCriteria['amountMin'])) {
            $qb->andWhere('ace.amount >= :amountMin');
            $qb->setParameter('amountMin', $rangeCriteria['amountMin']);
        }

        if (isset($rangeCriteria['amountMax'])) {
            $qb->andWhere('ace.amount <= :amountMax');
            $qb->setParameter('amountMax', $rangeCriteria['amountMax']);
        }

        if (isset($rangeCriteria['feeMin'])) {
            $qb->andWhere('ace.fee >= :feeMin');
            $qb->setParameter('feeMin', $rangeCriteria['feeMin']);
        }

        if (isset($rangeCriteria['feeMax'])) {
            $qb->andWhere('ace.fee <= :feeMax');
            $qb->setParameter('feeMax', $rangeCriteria['feeMax']);
        }

        if (isset($rangeCriteria['balanceMin'])) {
            $qb->andWhere('ace.balance >= :balanceMin');
            $qb->setParameter('balanceMin', $rangeCriteria['balanceMin']);
        }

        if (isset($rangeCriteria['balanceMax'])) {
            $qb->andWhere('ace.balance <= :balanceMax');
            $qb->setParameter('balanceMax', $rangeCriteria['balanceMax']);
        }

        return $qb->getQuery()->getScalarResult();
    }
}
