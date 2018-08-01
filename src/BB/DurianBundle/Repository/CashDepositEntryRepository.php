<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * CashDepositEntryRepository
 */
class CashDepositEntryRepository extends EntityRepository
{
    /**
     * 回傳入款明細列表
     *
     * @param array   $criteria
     * @param integer $firstResult
     * @param integer $maxResults
     * @param array   $orderBy
     * @return ArrayCollection
     */
    public function getDepositEntryList(
        $criteria = [],
        $firstResult,
        $maxResults,
        $orderBy = []
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('cde');
        $qb->from('BBDurianBundle:CashDepositEntry', 'cde');

        if (isset($criteria['paymentGateway'])) {
            $qb->leftJoin(
                'BBDurianBundle:Merchant',
                'm',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'cde.merchantId = m.id'
            );

            $qb->where("m.paymentGateway = :paymentGateway");
            $qb->setParameter('paymentGateway', $criteria['paymentGateway']);
            unset($criteria['paymentGateway']);
        }

        if (key_exists('start', $criteria)) {
            $qb->andWhere('cde.at >= :start');
            $qb->setParameter('start', $criteria['start']);

            unset($criteria['start']);
        }

        if (key_exists('end', $criteria)) {
            $qb->andWhere('cde.at <= :end');
            $qb->setParameter('end', $criteria['end']);

            unset($criteria['end']);
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
     * 回傳入款明細總數
     *
     * @param array $criteria
     * @return integer
     */
    public function countDepositEntryList($criteria = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('count(cde)');
        $qb->from('BBDurianBundle:CashDepositEntry', 'cde');

        if (isset($criteria['paymentGateway'])) {
            $qb->leftJoin(
                'BBDurianBundle:Merchant',
                'm',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'cde.merchantId = m.id'
            );

            $qb->where("m.paymentGateway = :paymentGateway");
            $qb->setParameter('paymentGateway', $criteria['paymentGateway']);
            unset($criteria['paymentGateway']);
        }

        if (key_exists('start', $criteria)) {
            $qb->andWhere('cde.at >= :start');
            $qb->setParameter('start', $criteria['start']);

            unset($criteria['start']);
        }

        if (key_exists('end', $criteria)) {
            $qb->andWhere('cde.at <= :end');
            $qb->setParameter('end', $criteria['end']);

            unset($criteria['end']);
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
     * 回傳入款明細總計
     *
     * @param array $criteria
     * @return ArrayCollection
     */
    public function sumDepositEntryList($criteria = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('sum(cde.amount) as amount');
        $qb->addSelect('sum(cde.amountConvBasic) as amount_conv_basic');
        $qb->addSelect('sum(cde.amountConv) as amount_conv');
        $qb->addSelect('sum(cde.offer) as offer');
        $qb->addSelect('sum(cde.offerConvBasic) as offer_conv_basic');
        $qb->addSelect('sum(cde.offerConv) as offer_conv');
        $qb->addSelect('sum(cde.fee) as fee');
        $qb->addSelect('sum(cde.feeConvBasic) as fee_conv_basic');
        $qb->addSelect('sum(cde.feeConv) as fee_conv');
        $qb->from('BBDurianBundle:CashDepositEntry', 'cde');

        if (isset($criteria['paymentGateway'])) {
            $qb->leftJoin(
                'BBDurianBundle:Merchant',
                'm',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'cde.merchantId = m.id'
            );

            $qb->where("m.paymentGateway = :paymentGateway");
            $qb->setParameter('paymentGateway', $criteria['paymentGateway']);
            unset($criteria['paymentGateway']);
        }

        if (key_exists('start', $criteria)) {
            $qb->andWhere('cde.at >= :start');
            $qb->setParameter('start', $criteria['start']);

            unset($criteria['start']);
        }

        if (key_exists('end', $criteria)) {
            $qb->andWhere('cde.at <= :end');
            $qb->setParameter('end', $criteria['end']);

            unset($criteria['end']);
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
