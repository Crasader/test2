<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * DepositPayStatusErrorRepository
 */
class DepositPayStatusErrorRepository extends EntityRepository
{
    /**
     * 回傳符合條件的異常明細列表
     *
     * @param array $criteria 查詢條件
     * @param integer $firstResult
     * @param integer $maxResults
     * @return ArrayCollection
     */
    public function getPayStatusErrorList($criteria, $firstResult = null, $maxResults = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('dpse');
        $qb->from('BBDurianBundle:DepositPayStatusError', 'dpse');
        $qb->where('dpse.checked = :checked');
        $qb->setParameter('checked', $criteria['checked']);

        $typeCriteria = $qb->expr()->orX();

        if (isset($criteria['deposit'])) {
            $typeCriteria->add('dpse.deposit = :deposit');
            $qb->setParameter('deposit', $criteria['deposit']);
        }

        if (isset($criteria['card'])) {
            $typeCriteria->add('dpse.card = :card');
            $qb->setParameter('card', $criteria['card']);
        }

        if (isset($criteria['remit'])) {
            $typeCriteria->add('dpse.remit = :remit');
            $qb->setParameter('remit', $criteria['remit']);
        }

        if (!empty($typeCriteria->getParts())) {
            $qb->andWhere($typeCriteria);
        }

        if (isset($criteria['duplicate_error'])) {
            $qb->andWhere('dpse.duplicateError = :duplicateError');
            $qb->setParameter('duplicateError', $criteria['duplicate_error']);
        }

        if (isset($criteria['confirm_start'])) {
            $qb->andWhere('dpse.confirmAt >= :confirmStart');
            $qb->setParameter('confirmStart', $criteria['confirm_start']);
        }

        if (isset($criteria['confirm_end'])) {
            $qb->andWhere('dpse.confirmAt <= :confirmEnd');
            $qb->setParameter('confirmEnd', $criteria['confirm_end']);
        }

        if (isset($criteria['checked_start'])) {
            $qb->andWhere('dpse.checkedAt >= :checkedStart');
            $qb->setParameter('checkedStart', $criteria['checked_start']);
        }

        if (isset($criteria['checked_end'])) {
            $qb->andWhere('dpse.checkedAt <= :checkedEnd');
            $qb->setParameter('checkedEnd', $criteria['checked_end']);
        }

        if (isset($criteria['domain'])) {
            $qb->andWhere('dpse.domain = :domain');
            $qb->setParameter('domain', $criteria['domain']);
        }

        if (isset($criteria['auto_remit_id'])) {
            $qb->andWhere('dpse.autoRemitId = :autoRemitId');
            $qb->setParameter('autoRemitId', $criteria['auto_remit_id']);
        }

        if (isset($criteria['payment_gateway_id'])) {
            $qb->andWhere('dpse.paymentGatewayId = :paymentGatewayId');
            $qb->setParameter('paymentGatewayId', $criteria['payment_gateway_id']);
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
     * 計算符合條件的PayStatusErrorList數量
     *
     * @param array $criteria
     * @return integer
     */
    public function countPayStatusErrorList($criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('count(dpse)');
        $qb->from('BBDurianBundle:DepositPayStatusError', 'dpse');
        $qb->where('dpse.checked = :checked');
        $qb->setParameter('checked', $criteria['checked']);

        $typeCriteria = $qb->expr()->orX();

        if (isset($criteria['deposit'])) {
            $typeCriteria->add('dpse.deposit = :deposit');
            $qb->setParameter('deposit', $criteria['deposit']);
        }

        if (isset($criteria['card'])) {
            $typeCriteria->add('dpse.card = :card');
            $qb->setParameter('card', $criteria['card']);
        }

        if (isset($criteria['remit'])) {
            $typeCriteria->add('dpse.remit = :remit');
            $qb->setParameter('remit', $criteria['remit']);
        }

        if (!empty($typeCriteria->getParts())) {
            $qb->andWhere($typeCriteria);
        }

        if (isset($criteria['duplicate_error'])) {
            $qb->andWhere('dpse.duplicateError = :duplicateError');
            $qb->setParameter('duplicateError', $criteria['duplicate_error']);
        }

        if (isset($criteria['confirm_start'])) {
            $qb->andWhere('dpse.confirmAt >= :confirmStart');
            $qb->setParameter('confirmStart', $criteria['confirm_start']);
        }

        if (isset($criteria['confirm_end'])) {
            $qb->andWhere('dpse.confirmAt <= :confirmEnd');
            $qb->setParameter('confirmEnd', $criteria['confirm_end']);
        }

        if (isset($criteria['checked_start'])) {
            $qb->andWhere('dpse.checkedAt >= :checkedStart');
            $qb->setParameter('checkedStart', $criteria['checked_start']);
        }

        if (isset($criteria['checked_end'])) {
            $qb->andWhere('dpse.checkedAt <= :checkedEnd');
            $qb->setParameter('checkedEnd', $criteria['checked_end']);
        }

        if (isset($criteria['domain'])) {
            $qb->andWhere('dpse.domain = :domain');
            $qb->setParameter('domain', $criteria['domain']);
        }

        if (isset($criteria['auto_remit_id'])) {
            $qb->andWhere('dpse.autoRemitId = :autoRemitId');
            $qb->setParameter('autoRemitId', $criteria['auto_remit_id']);
        }

        if (isset($criteria['payment_gateway_id'])) {
            $qb->andWhere('dpse.paymentGatewayId = :paymentGatewayId');
            $qb->setParameter('paymentGatewayId', $criteria['payment_gateway_id']);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}
