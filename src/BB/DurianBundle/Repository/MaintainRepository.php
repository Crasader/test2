<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * MaintainRepository
 */
class MaintainRepository extends EntityRepository
{
    /**
     * 回傳所有遊戲維護資訊
     *
     * @return array
     */
    public function getAllMaintain()
    {
        $qb = $this->createQueryBuilder('m');

        $qb->select('m.code')
            ->addSelect('m.beginAt')
            ->addSelect('m.endAt')
            ->addSelect('m.msg');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 回傳維護中的遊戲
     *
     * @param DateTime $at 時間
     * @return array
     */
    public function getIsMaintain($at)
    {
        $qb = $this->createQueryBuilder('m');

        $qb->select('m.code')
            ->where('m.beginAt <= :at')
            ->andWhere('m.endAt >= :at')
            ->setParameter('at', $at);

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 回傳遊戲維護總個數
     *
     * @return integer
     */
    public function countNumOf()
    {
        $qb = $this->createQueryBuilder('m');

        $qb->select('count(m)');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
