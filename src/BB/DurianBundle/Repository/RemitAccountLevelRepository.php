<?php

namespace BB\DurianBundle\Repository;

use BB\DurianBundle\Entity\RemitAccountLevel;
use Doctrine\ORM\EntityRepository;

/**
 * RemitAccountLevelRepository
 */
class RemitAccountLevelRepository extends EntityRepository
{
    /**
     * 取得預設的排序
     *
     * @param integer $levelId 層級
     * @return integer
     */
    public function getDefaultOrder($levelId)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select($qb->expr()->max('ral.orderId'));
        $qb->from('BBDurianBundle:RemitAccount', 'ra');
        $qb->innerJoin('BBDurianBundle:RemitAccountLevel', 'ral', 'WITH', 'ra.id = ral.remitAccountId');
        $qb->where('ral.levelId = :levelId');
        $qb->andWhere('ra.enable = 1');
        $qb->setParameter('levelId', $levelId);

        $maxOrder = $qb->getQuery()->getSingleScalarResult();

        return ++$maxOrder;
    }

    /**
     * 取得重複的 RemitAccountLevel
     *
     * @param RemitAccountLevel $remitAccountLevel 帳號層級設定
     * @return array
     */
    public function getDuplicates(RemitAccountLevel $remitAccountLevel)
    {
        $qb = $this->createQueryBuilder('ral');

        $qb->select('ral');
        $qb->from('BBDurianBundle:RemitAccount', 'ra');
        $qb->where('ra.id = ral.remitAccountId');
        $qb->andWhere('ra.enable = 1');
        $qb->andWhere('ral.levelId = :levelId');
        $qb->setParameter('levelId', $remitAccountLevel->getLevelId());
        $qb->andWhere('ral.orderId = :orderId');
        $qb->setParameter('orderId', $remitAccountLevel->getOrderId());
        $qb->andWhere('ral.remitAccountId <> :remitAccountId');
        $qb->setParameter('remitAccountId', $remitAccountLevel->getRemitAccountId());

        return $qb->getQuery()->getResult();
    }

    /**
     * 檢查同層級是否有重複的排序
     *
     * @param integer $levelId 層級
     * @return boolean
     */
    public function hasDuplicates($levelId)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('count(ral.remitAccountId) as cnt');
        $qb->from('BBDurianBundle:RemitAccountLevel', 'ral');
        $qb->innerJoin('BBDurianBundle:RemitAccount', 'ra', 'WITH', 'ra.id = ral.remitAccountId');
        $qb->where('ra.enable = 1');
        $qb->andWhere('ral.levelId = :levelId');
        $qb->setParameter('levelId', $levelId);
        $qb->groupBy('ral.orderId');
        $qb->having('cnt > 1');

        return !empty($qb->getQuery()->getScalarResult());
    }
}
