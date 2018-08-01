<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * CardDepositEntryRepository
 */
class CardDepositEntryRepository extends EntityRepository
{
    /**
     * 回傳租卡入款明細列表
     *
     * @param array $criteria 查詢條件
     * @param integer $firstResult 起始筆數
     * @param integer $maxResults 查詢筆數限制
     * @param array $orderBy 排序條件
     * @return array
     */
    public function getEntryBy($criteria, $firstResult, $maxResults, $orderBy = [])
    {
        $qb = $this->createQueryBuilder('cde');
        // 把查詢時間(at)先帶入已確保下入WHERE條件的時候會在最前面中index
        $qb->where('cde.at >= :start');
        $qb->andWhere('cde.at <= :end');
        $qb->setParameter('start', $criteria['start']);
        $qb->setParameter('end', $criteria['end']);

        unset($criteria['start']);
        unset($criteria['end']);

        if (isset($criteria['paymentGateway'])) {
            $qb->leftJoin(
                'BBDurianBundle:MerchantCard',
                'mc',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'cde.merchantCardId = mc.id'
            );

            $qb->andWhere("mc.paymentGateway = :paymentGateway");
            $qb->setParameter('paymentGateway', $criteria['paymentGateway']);

            unset($criteria['paymentGateway']);
        }

        if (key_exists('amountMin', $criteria)) {
            $qb->andWhere('cde.amount >= :amountMin');
            $qb->setParameter('amountMin', $criteria['amountMin']);

            unset($criteria['amountMin']);
        }

        if (key_exists('amountMax', $criteria)) {
            $qb->andWhere('cde.amount <= :amountMax');
            $qb->setParameter('amountMax', $criteria['amountMax']);

            unset($criteria['amountMax']);
        }

        foreach ($criteria as $key => $value) {
            $qb->andWhere("cde.$key = :$key");
            $qb->setParameter($key, $value);
        }

        if (!empty($orderBy)) {
            foreach ($orderBy as $sort => $order) {
                $qb->addOrderBy("cde.$sort", $order);
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
     * 回傳租卡入款明細總數
     *
     * @param array $criteria 查詢條件
     * @return integer
     */
    public function countEntryBy($criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('count(cde)');
        $qb->from('BBDurianBundle:CardDepositEntry', 'cde');
        // 把查詢時間(at)先帶入已確保下入WHERE條件的時候會在最前面中index
        $qb->where('cde.at >= :start');
        $qb->andWhere('cde.at <= :end');
        $qb->setParameter('start', $criteria['start']);
        $qb->setParameter('end', $criteria['end']);

        unset($criteria['start']);
        unset($criteria['end']);

        if (isset($criteria['paymentGateway'])) {
            $qb->leftJoin(
                'BBDurianBundle:MerchantCard',
                'mc',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'cde.merchantCardId = mc.id'
            );

            $qb->andWhere("mc.paymentGateway = :paymentGateway");
            $qb->setParameter('paymentGateway', $criteria['paymentGateway']);

            unset($criteria['paymentGateway']);
        }

        if (key_exists('amountMin', $criteria)) {
            $qb->andWhere('cde.amount >= :amountMin');
            $qb->setParameter('amountMin', $criteria['amountMin']);

            unset($criteria['amountMin']);
        }

        if (key_exists('amountMax', $criteria)) {
            $qb->andWhere('cde.amount <= :amountMax');
            $qb->setParameter('amountMax', $criteria['amountMax']);

            unset($criteria['amountMax']);
        }

        foreach ($criteria as $key => $value) {
            $qb->andWhere("cde.$key = :$key");
            $qb->setParameter($key, $value);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 回傳租卡入款明細總計
     *
     * @param array $criteria 查詢條件
     * @return array
     */
    public function sumEntryBy($criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('sum(cde.amount) as amount');
        $qb->addSelect('sum(cde.amountConvBasic) as amount_conv_basic');
        $qb->addSelect('sum(cde.amountConv) as amount_conv');
        $qb->addSelect('sum(cde.fee) as fee');
        $qb->addSelect('sum(cde.feeConvBasic) as fee_conv_basic');
        $qb->addSelect('sum(cde.feeConv) as fee_conv');
        $qb->from('BBDurianBundle:CardDepositEntry', 'cde');
        // 把查詢時間(at)先帶入已確保下入WHERE條件的時候會在最前面中index
        $qb->where('cde.at >= :start');
        $qb->andWhere('cde.at <= :end');
        $qb->setParameter('start', $criteria['start']);
        $qb->setParameter('end', $criteria['end']);

        unset($criteria['start']);
        unset($criteria['end']);

        if (isset($criteria['paymentGateway'])) {
            $qb->leftJoin(
                'BBDurianBundle:MerchantCard',
                'mc',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'cde.merchantCardId = mc.id'
            );

            $qb->andWhere("mc.paymentGateway = :paymentGateway");
            $qb->setParameter('paymentGateway', $criteria['paymentGateway']);

            unset($criteria['paymentGateway']);
        }

        if (key_exists('amountMin', $criteria)) {
            $qb->andWhere('cde.amount >= :amountMin');
            $qb->setParameter('amountMin', $criteria['amountMin']);

            unset($criteria['amountMin']);
        }

        if (key_exists('amountMax', $criteria)) {
            $qb->andWhere('cde.amount <= :amountMax');
            $qb->setParameter('amountMax', $criteria['amountMax']);

            unset($criteria['amountMax']);
        }

        foreach ($criteria as $key => $value) {
            $qb->andWhere("cde.$key = :$key");
            $qb->setParameter($key, $value);
        }

        return $qb->getQuery()->getSingleResult();
    }
}
