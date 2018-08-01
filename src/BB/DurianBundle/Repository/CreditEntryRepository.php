<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * CreditEntryRepository
 */
class CreditEntryRepository extends EntityRepository
{
    /**
     * 修改明細備註
     *
     * @param int $id
     * @param int $at
     * @param string $memo
     * @return int
     */
    public function setEntryMemo($id, $at, $memo)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->update('BBDurianBundle:CreditEntry', 'ce');
        $qb->set('ce.memo', ':memo');
        $qb->where('ce.id = :id');
        $qb->andWhere('ce.at = :at');
        $qb->setParameter('memo', $memo);
        $qb->setParameter('id', $id);
        $qb->setParameter('at', $at);

        return $qb->getQuery()->execute();
    }

    /**
     * 透過refId取得信用額度明細
     *
     * @param integer $refId 參考編號
     * @param array $limit 筆數限制
     * @return array
     */
    public function getEntriesByRefId($refId, $limit = [])
    {
        $qb = $this->createQueryBuilder('ce');

        $qb->select('ce')
            ->where('ce.refId = :refId')
            ->setParameter('refId', $refId);

        if (!is_null($limit['first_result'])) {
            $qb->setFirstResult($limit['first_result']);
        }

        if (!is_null($limit['max_results'])) {
            $qb->setMaxResults($limit['max_results']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 回傳透過ref_id查詢有幾筆交易記錄
     *
     * @param integer $refId 參考編號
     * @return integer
     */
    public function countNumOfByRefId($refId)
    {
        $qb = $this->createQueryBuilder('ce');

        $qb->select('count(ce)')
            ->where('ce.refId = :refId')
            ->setParameter('refId', $refId);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 回傳時間區間內是否存在信用額度交易記錄
     *
     * @param integer $creditId 信用額度帳號
     * @param string  $start    開始時間
     * @param string  $end      結束時間
     * @param integer $opcode   要排除的交易代碼
     * @return boolean
     */
    public function hasEntry($creditId, $start, $end, $opcode = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('1')
            ->from('BBDurianBundle:CreditEntry', 'ce')
            ->where('ce.creditId = :creditId')
            ->setParameter('creditId', $creditId)
            ->andWhere('ce.at >= :startTime')
            ->setParameter('startTime', $start)
            ->andWhere('ce.at <= :endTime')
            ->setParameter('endTime', $end)
            ->setMaxResults(1);

        if (isset($opcode)) {
            $qb->andWhere('ce.opcode != :opcode')
                ->setParameter('opcode', $opcode);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }
}
