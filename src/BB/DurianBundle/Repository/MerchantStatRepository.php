<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use BB\DurianBundle\Entity\Merchant;

/**
 * MerchantStatRepository
 */
class MerchantStatRepository extends EntityRepository
{
    /**
     * 新增商家統計次數記錄，並回傳新增的id
     *
     * @param Merchant $merchant
     * @param int $count
     * @param int $total
     * @param int $at
     * @return int
     */
    public function insertMerchantStat($merchant, $count, $total, $at)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "INSERT INTO `merchant_stat` ".
               "(`merchant_id`, `domain`, `count`, `total`, `at`) ".
               "VALUES (?, ?, ?, ?, ?)";
        $valueArray = array(
            $merchant->getId(),
            $merchant->getDomain(),
            $count,
            $total,
            $at
        );

        if ($conn->getDatabasePlatform()->getName() != 'sqlite') {
            $sql .= " ON DUPLICATE KEY UPDATE `count` = `count` + ?, `total` = `total` + ?";

            $valueArray = array_merge($valueArray, array($count, $total));
        }

        $conn->executeUpdate($sql, $valueArray);

        return $conn->lastInsertId();
    }

    /**
     * 更新商家統計次數記錄
     *
     * @param int $statId
     * @param int $count
     * @param int $total
     */
    public function updateMerchantStat($statId, $count, $total)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->update('BBDurianBundle:MerchantStat', 'ms');
        $qb->set('ms.count', 'ms.count + :count');
        $qb->set('ms.total', 'ms.total + :total');
        $qb->where('ms.id = :msid');
        $qb->setParameter('count', $count);
        $qb->setParameter('total', $total);
        $qb->setParameter('msid', $statId);

        $qb->getQuery()->execute();
    }
}
