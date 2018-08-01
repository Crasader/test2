<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * RemovedUserRepository
 */
class RemovedUserRepository extends EntityRepository
{
    /**
     * 取得指定廳某時點修改會員資料
     *
     * @param integer $domain     廳
     * @param string $beginAt     查詢的時間 format('Y-m-d H:i:s')
     * @param array $limit        筆數限制, ex.array('first' => 0, 'max' => 100)
     * @return Array
     */
    public function getRemovedUserByDomain($domain, $beginAt, $limit)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $first = $limit['first'];
        $max = $limit['max'];

        $qb->select('u.userId as user_id')
            ->addSelect('u.username')
            ->addSelect('u.domain')
            ->addSelect('u.alias')
            ->addSelect('u.sub')
            ->addSelect('u.enable')
            ->addSelect('u.block')
            ->addSelect('u.bankrupt')
            ->addSelect('u.test')
            ->addSelect('u.size')
            ->addSelect('u.errNum as err_num')
            ->addSelect('u.currency')
            ->addSelect('u.createdAt as created_at')
            ->addSelect('u.modifiedAt as modified_at')
            ->addSelect('u.lastLogin as last_login')
            ->addSelect('u.lastBank as last_bank')
            ->addSelect('u.role')
            ->addSelect('ue.email')
            ->addSelect('ud.nameReal as name_real')
            ->addSelect('ud.nameChinese as name_chinese')
            ->addSelect('ud.nameEnglish as name_english')
            ->addSelect('ud.country')
            ->addSelect('ud.passport')
            ->addSelect('ud.identityCard as identity_card')
            ->addSelect('ud.driverLicense as driver_license')
            ->addSelect('ud.insuranceCard as insurance_card')
            ->addSelect('ud.healthCard as health_card')
            ->addSelect('ud.birthday')
            ->addSelect('ud.telephone')
            ->addSelect('ud.qqNum as qq_num')
            ->addSelect('ud.wechat')
            ->addSelect('ud.note')
            ->from('BB\DurianBundle\Entity\RemovedUser', 'u')
            ->leftJoin(
                'BBDurianBundle:RemovedUserDetail',
                'ud',
                Join::WITH,
                'u.userId = ud.removedUser'
            )
            ->innerJoin(
                'BBDurianBundle:RemovedUserEmail',
                'ue',
                Join::WITH,
                'u.userId = ue.removedUser'
            )
            ->andWhere('u.modifiedAt >= :at')
            ->setParameter('at', $beginAt)
            ->andWhere('u.domain = :domain')
            ->setParameter('domain', $domain)
            ->setFirstResult($first)
            ->setMaxResults($max);

           return $qb->getQuery()->getArrayResult();
    }

    /**
     * 取得指定廳某時點後修改會員筆數
     *
     * @param integer $domain     廳
     * @param string $beginAt     查詢的時間 format('Y-m-d H:i:s')
     * @return ArrayCollection
     */
    public function countRemovedUserByDomain($domain, $beginAt)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('COUNT(u)')
           ->from('BB\DurianBundle\Entity\RemovedUser', 'u')
           ->andWhere('u.modifiedAt >= :at')
           ->setParameter('at', $beginAt)
           ->andWhere('u.domain = :domain')
           ->setParameter('domain', $domain);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 取指定時間點後刪除的會員
     *
     * @param string  $removedAt   查詢的時間
     * @param integer $firstResult 資料開頭
     * @param integer $maxResults  資料筆數
     * @return Array
     */
    public function getRemovedUserByTime($removedAt, $firstResult, $maxResults)
    {
        $qb = $this->createQueryBuilder('u');

        $qb->select('u.userId AS user_id')
            ->addSelect('u.role')
            ->addSelect('u.modifiedAt AS removed_at')
            ->where('u.modifiedAt >= :removedAt')
            ->setParameter('removedAt', $removedAt)
            ->orderBy('u.modifiedAt');

        if (isset($firstResult)) {
            $qb->setFirstResult($firstResult);
        }

        if (isset($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        $removeUserArray = $qb->getQuery()->getArrayResult();

        foreach ($removeUserArray as $idx => $removeUser) {
            $removeUserArray[$idx]['removed_at'] = $removeUser['removed_at']
                ->format(\DateTime::ISO8601);
        }

        return $removeUserArray;
    }

    /**
     * 取指定時間點後刪除的會員筆數
     *
     * @param string $removedAt 查詢的時間
     * @return integer
     */
    public function countRemovedUserByTime($removedAt)
    {
        $qb = $this->createQueryBuilder('u');

        $qb->select('COUNT(u)')
            ->andWhere('u.modifiedAt >= :removedAt')
            ->setParameter('removedAt', $removedAt);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 使用userId陣列回傳刪除使用者陣列
     *
     * @param array $userIds userId陣列
     * @param boolean $detail 是否須回傳詳細資料
     *
     * @return ArrayCollection
     */
    public function getRemovedUserByUserIds(Array $userIds, $detail)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('u.userId as user_id')
            ->addSelect('u.parentId as parent_id')
            ->addSelect('u.username')
            ->addSelect('u.domain')
            ->addSelect('u.alias')
            ->addSelect('u.sub')
            ->addSelect('u.enable')
            ->addSelect('u.block')
            ->addSelect('u.bankrupt')
            ->addSelect('u.test')
            ->addSelect('u.size')
            ->addSelect('u.errNum as err_num')
            ->addSelect('u.currency')
            ->addSelect('u.createdAt as created_at')
            ->addSelect('u.modifiedAt as modified_at')
            ->addSelect('u.lastLogin as last_login')
            ->addSelect('u.lastBank as last_bank')
            ->addSelect('u.role')
            ->from('BBDurianBundle:RemovedUser', 'u')
            ->where($qb->expr()->in('u.userId', ':userIds'))
            ->setParameter('userIds', $userIds);

        if ($detail) {
            $qb->addSelect('ue.email')
                ->addSelect('ud.nameReal as name_real')
                ->addSelect('ud.identityCard as identity_card')
                ->addSelect('ud.birthday')
                ->addSelect('ud.telephone')
                ->addSelect('ud.qqNum as qq_num')
                ->addSelect('ud.wechat')
                ->addSelect('ud.note')
                ->leftJoin(
                    'BBDurianBundle:RemovedUserDetail',
                    'ud',
                    Join::WITH,
                    'u.userId = ud.removedUser'
                )
                ->innerJoin(
                    'BBDurianBundle:RemovedUserEmail',
                    'ue',
                    Join::WITH,
                    'u.userId = ue.removedUser'
                );
        }

        return $qb->getQuery()->getArrayResult();
    }
}
