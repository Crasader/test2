<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * SlideBindingRepository
 */
class SlideBindingRepository extends EntityRepository
{
    /**
     * 取得一筆手勢登入綁定
     *
     * @param integer $userId 使用者ID
     * @param integer $appId 裝置識別碼
     * @return SlideBinding | null
     */
    public function findOneByUserAndAppId($userId, $appId)
    {
        $qb = $this->createQueryBuilder('sb');
        $qb->innerJoin('BBDurianBundle:SlideDevice', 'sd', 'WITH', 'sd.id = sb.device')
            ->where('sd.appId = :appId')
            ->andWhere('sb.userId = :userId')
            ->setParameter('appId', $appId)
            ->setParameter('userId', $userId);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * 取得一使用者所有綁定的手勢登入裝置資料
     *
     * @param integer $userId 使用者
     * @return array
     */
    public function getBindingDeviceByUser($userId)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('sd.appId app_id')
            ->addSelect('sb.name device_name')
            ->addSelect('sd.os')
            ->addSelect('sd.brand')
            ->addSelect('sd.model')
            ->addSelect('sd.enabled')
            ->addSelect('sb.createdAt created_at')
            ->from('BBDurianBundle:SlideDevice', 'sd')
            ->innerJoin('BBDurianBundle:SlideBinding', 'sb', 'WITH', 'sb.device = sd.id')
            ->where('sb.userId = :userId')
            ->setParameter('userId', $userId);

        return $qb->getQuery()->getArrayResult();
    }
}
