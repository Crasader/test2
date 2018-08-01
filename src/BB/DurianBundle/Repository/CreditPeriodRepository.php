<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * CreditPeriodRepository
 */
class CreditPeriodRepository extends EntityRepository
{
    /**
     * 更新累積金額的資料
     *
     * @param array $periodInfo 使用者累積金額資料
     */
    public function updatePeriodData(Array $periodInfo)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->update('BBDurianBundle:CreditPeriod', 't')
            ->set('t.amount', ':amount')
            ->set('t.version', ':version')
            ->where('t.userId = :userId')
            ->andWhere('t.at = :at')
            ->andWhere('t.groupNum = :groupNum')
            ->andWhere('t.version < :version')
            ->setParameter('amount', $periodInfo['amount'])
            ->setParameter('version', $periodInfo['version'])
            ->setParameter('userId', $periodInfo['user_id'])
            ->setParameter('groupNum', $periodInfo['group_num'])
            ->setParameter('at', new \DateTime($periodInfo['at']));

        $qb->getQuery()->execute();
    }

    /**
     * 給定起始日期，回傳使用者的累積金額
     *
     * @param integer   $userId   使用者編號
     * @param integer   $groupNum 群組編號
     * @param \DateTime $beginAt  起始日期
     *
     * @return array
     */
    public function getPeriodsBy($userId, $groupNum, \DateTime $beginAt)
    {
        $qb = $this->createQueryBuilder('cp');

        $qb->where('cp.userId = :userId')
            ->andWhere('cp.at >= :at')
            ->andWhere('cp.groupNum = :groupNum')
            ->setParameter('userId', $userId)
            ->setParameter('groupNum', $groupNum)
            ->setParameter('at', $beginAt);

        return $qb->getQuery()->getResult();
    }
}
