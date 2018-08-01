<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use BB\DurianBundle\Entity\Exchange;

/**
 * Repository of Exchange
 */
class ExchangeRepository extends EntityRepository
{
    /**
     * 回傳在指定時間點上有效的匯率物件
     * ps.用getSingleResult取到null則會直接跳exception, 使我們無法控制訊息內容
     * 故改用getResult
     *
     * @deprecated
     * @param integer $currency
     * @param \DateTime $date
     * @return Exchange || null
     */
    public function findByCurrencyAt($currency, \DateTime $date)
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.currency = ?1')
            ->andWhere('e.activeAt <= ?2')
            ->addOrderBy('e.activeAt', 'desc')
            ->setParameter(1, $currency)
            ->setParameter(2, $date, \Doctrine\DBAL\Types\Type::DATETIME)
            ->setMaxResults(1);

        $exchange = $qb->getQuery()->getResult();

        $output = null;
        if (0 != count($exchange)) {
            $output = $exchange[0];
        }

        return $output;
    }

    /**
     * 計算Exchange數量
     * @param integer $currency 指定幣別
     * @param string  $start    時間範圍(開始)
     * @param string  $end      時間範圍(結束)
     * @return integer
     */
    public function countNumOf($currency, $start = null, $end = null)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->select('count(e)')
           ->from('BB\DurianBundle\Entity\Exchange', 'e')
           ->where('e.currency = :currency')
           ->setParameter('currency', $currency);

        if (null !== $start) {
            $qb->andWhere('e.activeAt >= :start')
               ->setParameter('start', $start);
        }

        if (null !== $end) {
            $qb->andWhere('e.activeAt <= :end')
               ->setParameter('end', $end);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 計算匯率資料
     * @param integer $currency    指定幣別
     * @param array   $orderBy     排序
     * @param integer $firstResult 資料開頭
     * @param integer $maxResults  資料筆數
     * @param string  $start       時間範圍(開始)
     * @param string  $end         時間範圍(結束)
     * @return integer
     */
    public function getExchangeBy(
        $currency,
        $orderBy = array(),
        $firstResult = null,
        $maxResults = null,
        $start = null,
        $end = null
    ) {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->select('e')
           ->from('BB\DurianBundle\Entity\Exchange', 'e')
           ->where('e.currency = :currency')
           ->setParameter('currency', $currency);

        if (!empty($orderBy)) {
            foreach ($orderBy as $sort => $order) {
                $qb->addOrderBy("e.$sort", $order);
            }
        }

        if (!is_null($firstResult)) {
            $qb->setFirstResult($firstResult);
        }

        if (!is_null($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        if (null !== $start) {
            $qb->andWhere('e.activeAt >= :start')
               ->setParameter('start', $start);
        }

        if (null !== $end) {
            $qb->andWhere('e.activeAt <= :end')
               ->setParameter('end', $end);
        }

        return $qb->getQuery()->getResult();
    }
}
