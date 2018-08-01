<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use BB\DurianBundle\Entity\Card;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * CardRepository
 */
class CardRepository extends EntityRepository
{
    /**
     * 根據條件回傳租卡交易紀錄
     *
     * $orderBy = array(
     *     'created_at' => 'asc'
     * );
     *
     * $cardEntries = getEntriesBy($card, $orderBy, 0, 20);
     *
     * @param Card      $card        指定的租卡
     * @param array     $orderBy     排序
     * @param integer   $firstResult 資料開頭
     * @param integer   $maxResults  資料筆數
     * @param integer   $opc         交易種類
     * @param string    $startTime   查詢區間開始時間
     * @param string    $endTime     查詢區間結束時間
     * @return ArrayCollection
     */
    public function getEntriesBy(
        Card $card,
        $orderBy = array(),
        $firstResult = null,
        $maxResults = null,
        $opc = null,
        $startTime = null,
        $endTime = null
    ) {

        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->select('ce')
           ->from('BB\DurianBundle\Entity\CardEntry', 'ce')
           ->where('ce.card = :cid')
           ->setParameter('cid', $card->getId());

        if (!empty($orderBy)) {
            foreach ($orderBy as $sort => $order) {
                $qb->addOrderBy("ce.$sort", $order);
            }
        }

        if (!is_null($firstResult)) {
            $qb->setFirstResult($firstResult);
        }

        if (!is_null($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        if (null !== $opc) {
            if (is_array($opc)) {
                $qb->andWhere($qb->expr()->in('ce.opcode', ':opc'))
                   ->setParameter('opc', $opc);
            } else {
                $qb->andWhere("ce.opcode = :opc")
                   ->setParameter('opc', $opc);
            }
        }

        if ($startTime) {
            $qb->andWhere('ce.createdAt >= :start')
               ->setParameter('start', $startTime);
        }

        if ($endTime) {
            $qb->andWhere('ce.createdAt <= :end')
               ->setParameter('end', $endTime);
        }

        $query = $qb->getQuery();

        return $query->getResult();
    }

    /**
     * 根據條件回傳租卡交易紀錄筆數
     *
     *
     * $cardEntries = countEntriesof($card);
     *
     * @param Card      $card        指定的租卡
     * @param integer   $opc         交易種類
     * @param string    $startTime   查詢區間開始時間
     * @param string    $endTime     查詢區間開始時間
     * @return integer
     */
    public function countEntriesOf(
        Card $card,
        $opc = null,
        $startTime = null,
        $endTime = null
    ) {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->select('COUNT(ce.id)')
           ->from('BB\DurianBundle\Entity\CardEntry', 'ce')
           ->where('ce.card = :cid')
           ->setParameter('cid', $card->getId());

        if (null !== $opc) {
            if (is_array($opc)) {
                $qb->andWhere($qb->expr()->in('ce.opcode', ':opc'))
                   ->setParameter('opc', $opc);
            } else {
                $qb->andWhere("ce.opcode = :opc")
                   ->setParameter('opc', $opc);
            }
        }

        if ($startTime) {
            $qb->andWhere('ce.createdAt >= :start')
               ->setParameter('start', $startTime);
        }

        if ($endTime) {
            $qb->andWhere('ce.createdAt <= :end')
               ->setParameter('end', $endTime);
        }

        $query = $qb->getQuery();

        return $query->getSingleScalarResult();
    }

    /**
     * 刪除交易紀錄
     *
     * @param Card $card 指定的租卡
     * @return mixed
     */
    public function removeEntryOf(Card $card)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->delete('BB\DurianBundle\Entity\CardEntry', 'ce')
           ->where('ce.card = :cid')
           ->setParameter('cid', $card->getId());

        return $qb->getQuery()->execute();
    }

    /**
     * 回傳指定使用者的租卡資料
     *
     * @param array $userIds
     * @return array
     */
    public function getCardByUserIds(Array $userIds)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->where($qb->expr()->in('c.user', ':users'));
        $qb->setParameter('users', $userIds);

        return $qb->getQuery()->getResult();
    }
}
