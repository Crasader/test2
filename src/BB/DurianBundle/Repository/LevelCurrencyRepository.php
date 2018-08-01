<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * LevelCurrencyRepository
 */
class LevelCurrencyRepository extends EntityRepository
{
    /**
     * 更新複製體系層級數量
     *
     * $criteria
     *   level_id:   integer 層級編號
     *   currency:   integer 幣別
     *   user_count: integer 需更新數量
     *
     * @param array $criteria query 參數
     */
    public function updateLevelCount($criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->update('BBDurianBundle:LevelCurrency', 'lc');
        $qb->set('lc.userCount', 'lc.userCount + :count');
        $qb->where('lc.levelId = :levelId');
        $qb->andWhere('lc.currency = :currency');
        $qb->setParameter('count', $criteria['user_count']);
        $qb->setParameter('levelId', $criteria['level_id']);
        $qb->setParameter('currency', $criteria['currency']);

        $qb->getQuery()->execute();
    }
}
