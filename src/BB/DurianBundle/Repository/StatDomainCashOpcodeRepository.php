<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * StatDomainCashOpcodeRepository
 */
class StatDomainCashOpcodeRepository extends EntityRepository
{
    /**
     * 根據條件回傳各opcode交易金額
     *
     * @param array $criteria 查詢條件
     *     integer $domain   廳ID
     *     integer $currency 幣別
     *     string  $start    起始時間
     *     string  $end      結束時間
     *     array   $opcode   交易代碼
     * @return array
     */
    public function sumDomainAmountByOpcode(array $criteria)
    {
        $qb = $this->createQueryBuilder('s');

        $qb->select('s.opcode, sum(s.amount) as total_amount, sum(s.count) as total_entry');
        $qb->where('s.domain = :domain');
        $qb->andWhere('s.currency = :currency');
        $qb->andWhere('s.at >= :start');
        $qb->andWhere('s.at <= :end');
        $qb->andWhere($qb->expr()->in('s.opcode', ':opcode'));
        $qb->setParameter('domain', $criteria['domain']);
        $qb->setParameter('currency', $criteria['currency']);
        $qb->setParameter('start', $criteria['start']);
        $qb->setParameter('end', $criteria['end']);
        $qb->setParameter('opcode', $criteria['opcode']);
        $qb->groupBy('s.opcode');

        return $qb->getQuery()->getArrayResult();
    }
}
