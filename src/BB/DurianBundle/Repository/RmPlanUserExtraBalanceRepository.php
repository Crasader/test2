<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * RmPlanUserExtraBalanceRepository
 */
class RmPlanUserExtraBalanceRepository extends EntityRepository
{
    /**
     * 回傳RmPlanUserExtraBalance
     *
     * @param array $planUserIds 刪除使用者的陣列資料
     * @return ArrayCollection
     */
    public function getBalanceBy($planUserIds)
    {
        $qb = $this->createQueryBuilder('rpueb');
        $qb->where($qb->expr()->in('rpueb.id', ':planUserIds'))
            ->setParameter('planUserIds', $planUserIds);

        return $qb->getQuery()->getArrayResult();
    }
}
