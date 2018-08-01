<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * PresetLevelRepository
 */
class PresetLevelRepository extends EntityRepository
{
    /**
     * 回傳最靠近的上層的預設層級
     *
     * @param integer $userId 使用者id
     * @return ArrayCollection
     */
    public function getAncestorPresetLevel($userId)
    {
        $qb = $this->createQueryBuilder('pl');

        $qb->innerJoin(
            'BBDurianBundle:UserAncestor',
            'ua',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            'pl.user = ua.ancestor'
        );

        $qb->where('ua.user = :user');
        $qb->orderBy('ua.depth', 'ASC');
        $qb->setMaxResults(1);
        $qb->setParameter('user', $userId);

        return $qb->getQuery()->getResult();
    }
}
