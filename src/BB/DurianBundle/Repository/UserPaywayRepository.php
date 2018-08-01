<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\UserPayway;

/**
 * UserPaywayRepository
 */
class UserPaywayRepository extends EntityRepository
{
    /**
     * 回傳使用者支援的交易方式
     *
     * 1. 列出上層編號，按照 depth asc 排序
     * 2. 尋找自己加上上層是否有 payway
     * 3. 往上層尋找，若有 payway 則回傳，否則再往上層找
     *
     * @param User $user 使用者
     * @return UserPayway
     */
    public function getUserPayway(User $user)
    {
        $payway = $this->find($user->getId());

        if ($payway) {
            return $payway;
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('IDENTITY(ua.ancestor) as ancestor_id')
            ->from('BBDurianBundle:UserAncestor', 'ua')
            ->where('ua.user = :userId')
            ->orderBy('ua.depth')
            ->setParameter('userId', $user->getId());
        $ancestors = $qb->getQuery()->getScalarResult();

        foreach ($ancestors as $ancestor) {
            $userId = $ancestor['ancestor_id'];
            $ancestorPayway = $this->find($userId);

            if ($ancestorPayway) {
                return $ancestorPayway;
            }
        }

        return null;
    }

    /**
     * 檢查廳主是否支援多種交易方式
     *
     * @param integer $domain 廳主
     * @return UserPayway
     */
    public function checkMixedPayway($domain)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('SUM(up.cash + up.cashFake + up.credit)')
            ->from('BBDurianBundle:UserPayway', 'up')
            ->where('up.userId = :domain')
            ->setParameter('domain', $domain);
        $count = $qb->getQuery()->getSingleScalarResult();

        if ($count > 1) {
            return true;
        }

        return false;
    }
}
