<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use BB\DurianBundle\Entity\MerchantCard;

/**
 * MerchantCardStatRepository
 */
class MerchantCardStatRepository extends EntityRepository
{
    /**
     * 新增租卡商家統計次數記錄，並回傳新增的id
     *
     * @param MerchantCard $merchantCard 租卡商家
     * @param integer $at 統計時間
     * @param integer $count 入款次數
     * @param integer $total 入款總金額
     * @return integer
     */
    public function insertMerchantCardStat($merchantCard, $at, $count, $total)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'INSERT INTO merchant_card_stat ';
        $sql .= '(merchant_card_id, domain, at, count, total) ';
        $sql .= 'VALUES (?, ?, ?, ?, ?)';

        $value = [
            $merchantCard->getId(),
            $merchantCard->getDomain(),
            $at,
            $count,
            $total,
        ];

        if ($conn->getDatabasePlatform()->getName() != 'sqlite') {
            $sql .= ' ON DUPLICATE KEY UPDATE `count` = `count` + ?, `total` = `total` + ?';

            $value = array_merge($value, [$count, $total]);
        }

        $conn->executeUpdate($sql, $value);

        return $conn->lastInsertId();
    }

    /**
     * 更新租卡商家統計次數記錄
     *
     * @param integer $statId 統計id
     * @param integer $count 入款次數
     * @param integer $total 入款總金額
     */
    public function updateMerchantCardStat($statId, $count, $total)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->update('BBDurianBundle:MerchantCardStat', 'mcs');
        $qb->set('mcs.count', 'mcs.count + :count');
        $qb->set('mcs.total', 'mcs.total + :total');
        $qb->where('mcs.id = :id');
        $qb->setParameter('count', $count);
        $qb->setParameter('total', $total);
        $qb->setParameter('id', $statId);

        $qb->getQuery()->execute();
    }
}
