<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

/**
 * ChatRoomRepository
 */
class ChatRoomRepository extends EntityRepository
{
    /**
     * 取得使用者聊天室已禁言列表
     *
     * $criteria
     *   now: \DateTime 現在
     *
     * @param array $criteria query條件
     * @return array
     */
    public function getBanList($criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('cr');
        $qb->from('BBDurianBundle:ChatRoom', 'cr');
        $qb->where('cr.writable = 0 OR cr.banAt >= :now');
        $qb->setParameter('now', $criteria['now']);
        $qb->orderBy('cr.userId', 'ASC');

        return $qb->getQuery()->getResult();
    }
}
