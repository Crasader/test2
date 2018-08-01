<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * UserStatRepository
 */
class UserStatRepository extends EntityRepository
{
    /**
     * 根據帶入的條件回傳可轉移的使用者
     *
     * @param array $criteria 搜尋條件
     * @param integer $firstResult 起始筆數
     * @param integer $maxResults 查詢筆數限制
     * @return array
     */
    public function getLevelTransferUser($criteria, $firstResult, $maxResults)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('u.id');
        $qb->from('BBDurianBundle:UserLevel', 'ul');
        $qb->join(
            'BBDurianBundle:User',
            'u',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            'u.id = ul.user'
        );
        $qb->leftJoin(
            'BBDurianBundle:UserStat',
            'us',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            'us.userId = u.id'
        );
        $qb->where('ul.levelId = :levelId');
        $qb->andWhere('ul.user > :finishedUser');
        $qb->andWhere('ul.locked = :locked');
        $qb->setParameter('levelId', $criteria['levelId']);
        $qb->setParameter('finishedUser', $criteria['finishedUser']);
        $qb->setParameter('locked', $criteria['locked']);

        if (isset($criteria['startAt'])) {
            $qb->andWhere('u.createdAt >= :startAt');
            $qb->setParameter('startAt', $criteria['startAt']);
        }

        if (isset($criteria['endAt'])) {
            $qb->andWhere('u.createdAt <= :endAt');
            $qb->setParameter('endAt', $criteria['endAt']);
        }

        if (isset($criteria['depositCount'])) {
            $qb->andWhere('(us.depositCount + us.remitCount + us.manualCount + us.sudaCount) >= :depositCount');
            $qb->setParameter('depositCount', $criteria['depositCount']);
        }

        if (isset($criteria['depositTotal'])) {
            $qb->andWhere('(us.depositTotal + us.remitTotal + us.manualTotal + us.sudaTotal) >= :depositTotal');
            $qb->setParameter('depositTotal', $criteria['depositTotal']);
        }

        if (isset($criteria['depositMax'])) {
            $qb->andWhere($qb->expr()->orX(
                'us.depositMax >= :depositMax',
                'us.remitMax >= :depositMax',
                'us.manualMax >= :depositMax',
                'us.sudaMax >= :depositMax'
            ));
            $qb->setParameter('depositMax', $criteria['depositMax']);
        }

        if (isset($criteria['withdrawCount'])) {
            $qb->andWhere('us.withdrawCount >= :withdrawCount');
            $qb->setParameter('withdrawCount', $criteria['withdrawCount']);
        }

        if (isset($criteria['withdrawTotal'])) {
            $qb->andWhere('us.withdrawTotal >= :withdrawTotal');
            $qb->setParameter('withdrawTotal', $criteria['withdrawTotal']);
        }

        if (!is_null($firstResult)) {
            $qb->setFirstResult($firstResult);
        }

        if (!is_null($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        return $qb->getQuery()->getArrayResult();
    }
}
