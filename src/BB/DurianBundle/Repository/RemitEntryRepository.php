<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * RemitEntryRepository
 */
class RemitEntryRepository extends EntityRepository
{
    /**
     * 回傳符合條件的入款記錄數量
     *
     * @param array $entryCriteria 入款記錄的指定條件，用"="去查詢欄位資料
     * @param array $rangeCriteria 範圍區間的查詢條件，目前支援的參數有
     *              $rangeCriteria['remitAccountId']
     *              $rangeCriteria['amountMin']
     *              $rangeCriteria['amountMax']
     *              $rangeCriteria['createdStart']
     *              $rangeCriteria['createdEnd']
     *              $rangeCriteria['confirmStart']
     *              $rangeCriteria['confirmEnd']
     *              $rangeCriteria['levelId']
     * @return mixed
     */
    public function countEntriesBy(
        $entryCriteria,
        $rangeCriteria
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('count(re)');
        $qb->from('BBDurianBundle:RemitEntry', 're');

        if (isset($rangeCriteria['remitAccountId'])) {
            $qb->andWhere($qb->expr()->in('re.remitAccountId', ':remitAccountId'));
            $qb->setParameter('remitAccountId', $rangeCriteria['remitAccountId']);
        }

        if (isset($rangeCriteria['createdStart'])) {
            $qb->andWhere("re.createdAt >= :createdStart");
            $qb->setParameter('createdStart', $rangeCriteria['createdStart']);
        }

        if (isset($rangeCriteria['createdEnd'])) {
            $qb->andWhere("re.createdAt <= :createdEnd");
            $qb->setParameter('createdEnd', $rangeCriteria['createdEnd']);
        }

        if (isset($rangeCriteria['confirmStart'])) {
            $qb->andWhere("re.confirmAt >= :confirmStart");
            $qb->setParameter('confirmStart', $rangeCriteria['confirmStart']);
        }

        if (isset($rangeCriteria['confirmEnd'])) {
            $qb->andWhere("re.confirmAt <= :confirmEnd");
            $qb->setParameter('confirmEnd', $rangeCriteria['confirmEnd']);
        }

        foreach ($entryCriteria as $key => $value) {
            $qb->andWhere("re.$key = :$key");
            $qb->setParameter($key, $value);
        }

        if (isset($rangeCriteria['amountMin'])) {
            $qb->andWhere("re.amount >= :amountMin");
            $qb->setParameter('amountMin', $rangeCriteria['amountMin']);
        }

        if (isset($rangeCriteria['amountMax'])) {
            $qb->andWhere("re.amount <= :amountMax");
            $qb->setParameter('amountMax', $rangeCriteria['amountMax']);
        }

        if (isset($rangeCriteria['durationMin'])) {
            $qb->andWhere("re.duration >= :durationMin");
            $qb->setParameter('durationMin', $rangeCriteria['durationMin']);
        }

        if (isset($rangeCriteria['durationMax'])) {
            $qb->andWhere("re.duration <= :durationMax");
            $qb->setParameter('durationMax', $rangeCriteria['durationMax']);
        }

        if (isset($rangeCriteria['levelId'])) {
            $qb->andWhere($qb->expr()->in('re.levelId', ':levelId'));
            $qb->setParameter('levelId', $rangeCriteria['levelId']);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 回傳符合條件的入款記錄
     *
     * @param array $entryCriteria 入款記錄的指定條件，用"="去查詢欄位資料
     * @param array $rangeCriteria 範圍區間的查詢條件，目前支援的參數有
     *              $rangeCriteria['remitAccountId']
     *              $rangeCriteria['amountMin']
     *              $rangeCriteria['amountMax']
     *              $rangeCriteria['createdStart']
     *              $rangeCriteria['createdEnd']
     *              $rangeCriteria['confirmStart']
     *              $rangeCriteria['confirmEnd']
     *              $rangeCriteria['levelId']
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

        $qb->select('re');
        $qb->from('BBDurianBundle:RemitEntry', 're');

        if (isset($rangeCriteria['remitAccountId'])) {
            $qb->andWhere($qb->expr()->in('re.remitAccountId', ':remitAccountId'));
            $qb->setParameter('remitAccountId', $rangeCriteria['remitAccountId']);
        }

        if (isset($rangeCriteria['createdStart'])) {
            $qb->andWhere("re.createdAt >= :createdStart");
            $qb->setParameter('createdStart', $rangeCriteria['createdStart']);
        }

        if (isset($rangeCriteria['createdEnd'])) {
            $qb->andWhere("re.createdAt <= :createdEnd");
            $qb->setParameter('createdEnd', $rangeCriteria['createdEnd']);
        }

        if (isset($rangeCriteria['confirmStart'])) {
            $qb->andWhere("re.confirmAt >= :confirmStart");
            $qb->setParameter('confirmStart', $rangeCriteria['confirmStart']);
        }

        if (isset($rangeCriteria['confirmEnd'])) {
            $qb->andWhere("re.confirmAt <= :confirmEnd");
            $qb->setParameter('confirmEnd', $rangeCriteria['confirmEnd']);
        }

        foreach ($entryCriteria as $key => $value) {
            $qb->andWhere("re.$key = :$key");
            $qb->setParameter($key, $value);
        }

        if (isset($rangeCriteria['amountMin'])) {
            $qb->andWhere("re.amount >= :amountMin");
            $qb->setParameter('amountMin', $rangeCriteria['amountMin']);
        }

        if (isset($rangeCriteria['amountMax'])) {
            $qb->andWhere("re.amount <= :amountMax");
            $qb->setParameter('amountMax', $rangeCriteria['amountMax']);
        }

        if (isset($rangeCriteria['durationMin'])) {
            $qb->andWhere("re.duration >= :durationMin");
            $qb->setParameter('durationMin', $rangeCriteria['durationMin']);
        }

        if (isset($rangeCriteria['durationMax'])) {
            $qb->andWhere("re.duration <= :durationMax");
            $qb->setParameter('durationMax', $rangeCriteria['durationMax']);
        }

        if (isset($rangeCriteria['levelId'])) {
            $qb->andWhere($qb->expr()->in('re.levelId', ':levelId'));
            $qb->setParameter('levelId', $rangeCriteria['levelId']);
        }

        if (!is_null($firstResult)) {
            $qb->setFirstResult($firstResult);
        }

        if (!is_null($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        if (!empty($orderBy)) {
            foreach ($orderBy as $sort => $order) {
                $qb->addOrderBy("re.$sort", $order);
            }
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 回傳符合條件的入款記錄金額總記
     *
     * @param array $entryCriteria 入款記錄的指定條件，用"="去查詢欄位資料
     * @param array $userCriteria 入款者的指定條件，用"="去查詢欄位資料
     * @param array $rangeCriteria 範圍區間的查詢條件，目前支援的參數有
     *              $rangeCriteria['remitAccountId']
     *              $rangeCriteria['amountMin']
     *              $rangeCriteria['amountMax']
     *              $rangeCriteria['createdStart']
     *              $rangeCriteria['createdEnd']
     *              $rangeCriteria['confirmStart']
     *              $rangeCriteria['confirmEnd']
     *              $rangeCriteria['levelId']
     * @return array
     */
    public function sumEntriesBy(
        $entryCriteria,
        $rangeCriteria
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('sum(re.amount) as amount');
        $qb->addSelect('sum(re.discount) as discount');
        $qb->addSelect('sum(re.otherDiscount) as other_discount');
        $qb->addSelect('sum(re.actualOtherDiscount) as actual_other_discount');
        $qb->from('BBDurianBundle:RemitEntry', 're');

        if (isset($rangeCriteria['remitAccountId'])) {
            $qb->andWhere($qb->expr()->in('re.remitAccountId', ':remitAccountId'));
            $qb->setParameter('remitAccountId', $rangeCriteria['remitAccountId']);
        }

        if (isset($rangeCriteria['createdStart'])) {
            $qb->andWhere("re.createdAt >= :createdStart");
            $qb->setParameter('createdStart', $rangeCriteria['createdStart']);
        }

        if (isset($rangeCriteria['createdEnd'])) {
            $qb->andWhere("re.createdAt <= :createdEnd");
            $qb->setParameter('createdEnd', $rangeCriteria['createdEnd']);
        }

        if (isset($rangeCriteria['confirmStart'])) {
            $qb->andWhere("re.confirmAt >= :confirmStart");
            $qb->setParameter('confirmStart', $rangeCriteria['confirmStart']);
        }

        if (isset($rangeCriteria['confirmEnd'])) {
            $qb->andWhere("re.confirmAt <= :confirmEnd");
            $qb->setParameter('confirmEnd', $rangeCriteria['confirmEnd']);
        }

        foreach ($entryCriteria as $key => $value) {
            $qb->andWhere("re.$key = :$key");
            $qb->setParameter($key, $value);
        }

        if (isset($rangeCriteria['amountMin'])) {
            $qb->andWhere("re.amount >= :amountMin");
            $qb->setParameter('amountMin', $rangeCriteria['amountMin']);
        }

        if (isset($rangeCriteria['amountMax'])) {
            $qb->andWhere("re.amount <= :amountMax");
            $qb->setParameter('amountMax', $rangeCriteria['amountMax']);
        }

        if (isset($rangeCriteria['levelId'])) {
            $qb->andWhere($qb->expr()->in('re.levelId', ':levelId'));
            $qb->setParameter('levelId', $rangeCriteria['levelId']);
        }

        return $qb->getQuery()->getScalarResult();
    }
}
