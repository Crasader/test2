<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * CashTransRepository
 */
class CashTransRepository extends EntityRepository
{
    /**
     * 取得Id最大值
     *
     * @return integer
     */
    public function getMaxId()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('MAX(ct) as maxId')
            ->from('BBDurianBundle:CashTrans', 'ct');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 修改明細備註
     *
     * @param int $id 明細編號
     * @param string $memo 備註
     */
    public function setEntryMemo($id, $memo)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->update('BBDurianBundle:CashTrans', 'ct');
        $qb->set('ct.memo', ':memo');
        $qb->where('ct.id = :id');
        $qb->setParameter('memo', $memo);
        $qb->setParameter('id', $id);

        $qb->getQuery()->execute();
    }
}
